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
$userId = sanitizeInput($_POST['userId']);
$name = sanitizeInput($_POST['name']);
$email = sanitizeInput($_POST['email']);
$role = sanitizeInput($_POST['role']);
$phone = sanitizeInput($_POST['phone']);
$address = sanitizeInput($_POST['address']);

// Validate input data
if (empty($userId) || empty($name) || empty($email) || empty($role)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

$conn = getDbConnection();

try {
    // Check if email already exists for another user
    $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $email, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    // Update user
    $sql = "
        UPDATE users 
        SET 
            name = ?,
            email = ?,
            role = ?,
            phone = ?,
            address = ?
        WHERE 
            id = ?
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $name, $email, $role, $phone, $address, $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating user']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?> 