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

// Get and sanitize input data
$data = json_decode(file_get_contents('php://input'), true);
$userId = sanitizeInput($data['id']);

// Validate input data
if (empty($userId)) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

$conn = getDbConnection();

try {
    // Check if user exists
    $sql = "SELECT id FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    // Check if user has any associated records
    $sql = "
        SELECT 
            (SELECT COUNT(*) FROM allocations WHERE user_id = ?) as allocation_count,
            (SELECT COUNT(*) FROM payments WHERE user_id = ?) as payment_count
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $counts = $result->fetch_assoc();
    
    if ($counts['allocation_count'] > 0 || $counts['payment_count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete user with associated records']);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    // Delete user
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting user']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?> 