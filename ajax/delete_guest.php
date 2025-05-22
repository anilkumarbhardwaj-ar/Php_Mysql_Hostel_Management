<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is logged in and has appropriate role
if (!isLoggedIn() || $_SESSION['user_role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

// Get and sanitize input data
$guestId = sanitizeInput($data['id']);

// Validate input data
if (empty($guestId)) {
    echo json_encode(['success' => false, 'message' => 'Guest ID is required']);
    exit;
}

// Start transaction
$conn = getDbConnection();
$conn->begin_transaction();

try {
    // Get guest name for logging
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ? AND role = 'guest'");
    $stmt->bind_param("i", $guestId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Guest not found']);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    $guestName = $result->fetch_assoc()['name'];
    
    // Delete allocations
    $stmt = $conn->prepare("DELETE FROM allocations WHERE user_id = ?");
    $stmt->bind_param("i", $guestId);
    $stmt->execute();
    
    // Delete payments
    $stmt = $conn->prepare("DELETE FROM payments WHERE user_id = ?");
    $stmt->bind_param("i", $guestId);
    $stmt->execute();
    
    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'guest'");
    $stmt->bind_param("i", $guestId);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Log activity
    logUserActivity($_SESSION['user_id'], 'Deleted guest: ' . $guestName);
    
    echo json_encode(['success' => true, 'message' => 'Guest deleted successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>