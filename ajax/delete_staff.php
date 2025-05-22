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
$staffId = sanitizeInput($data['id']);

// Validate input data
if (empty($staffId)) {
    echo json_encode(['success' => false, 'message' => 'Staff ID is required']);
    exit;
}

// Start transaction
$conn = getDbConnection();
$conn->begin_transaction();

try {
    // Get staff name for logging
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param("i", $staffId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Staff not found']);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    $staffName = $result->fetch_assoc()['name'];
    
    // Delete staff details
    $stmt = $conn->prepare("DELETE FROM staff_details WHERE user_id = ?");
    $stmt->bind_param("i", $staffId);
    $stmt->execute();
    
    // Delete allocations
    $stmt = $conn->prepare("DELETE FROM allocations WHERE user_id = ?");
    $stmt->bind_param("i", $staffId);
    $stmt->execute();
    
    // Delete payments
    $stmt = $conn->prepare("DELETE FROM payments WHERE user_id = ?");
    $stmt->bind_param("i", $staffId);
    $stmt->execute();
    
    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $staffId);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Log activity
    logUserActivity($_SESSION['user_id'], 'Deleted staff: ' . $staffName);
    
    echo json_encode(['success' => true, 'message' => 'Staff deleted successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
