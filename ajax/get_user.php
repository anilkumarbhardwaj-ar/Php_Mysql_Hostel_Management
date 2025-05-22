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

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and sanitize input data
$userId = sanitizeInput($_GET['id']);
$detailed = isset($_GET['detailed']) ? true : false;

// Validate input data
if (empty($userId)) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

$conn = getDbConnection();

try {
    // Get user details
    $sql = "
        SELECT 
            id,
            name,
            email,
            role,
            phone,
            address,
            created_at
        FROM 
            users
        WHERE 
            id = ?
    ";
    
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
    
    $user = $result->fetch_assoc();
    
    echo json_encode(['success' => true, 'user' => $user]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?> 