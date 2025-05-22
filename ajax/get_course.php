<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is logged in and has appropriate role
if (!isLoggedIn() || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and sanitize input data
$id = sanitizeInput($_GET['id']);

// Validate input data
if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Course ID is required']);
    exit;
}

$conn = getDbConnection();

// Get course details
$stmt = $conn->prepare("SELECT id, name, duration, description FROM courses WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Course not found']);
} else {
    $course = $result->fetch_assoc();
    echo json_encode(['success' => true, 'course' => $course]);
}

$stmt->close();
closeDbConnection($conn);
?>