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

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and sanitize input data
$name = sanitizeInput($_POST['name']);
$duration = sanitizeInput($_POST['duration']);
$description = sanitizeInput($_POST['description']);

// Validate input data
$errors = [];

if (empty($name)) {
    $errors[] = 'Course name is required';
}

if (empty($duration) || $duration < 1) {
    $errors[] = 'Duration must be at least 1 month';
}

// Check if course name already exists
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT id FROM courses WHERE name = ?");
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $errors[] = 'Course name already exists';
}

// If there are errors, return them
if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    $stmt->close();
    closeDbConnection($conn);
    exit;
}

// Insert course
$stmt = $conn->prepare("INSERT INTO courses (name, duration, description) VALUES (?, ?, ?)");
$stmt->bind_param("sis", $name, $duration, $description);
$success = $stmt->execute();

if ($success) {
    // Log activity
    logUserActivity($_SESSION['user_id'], 'Added course: ' . $name);
    
    echo json_encode(['success' => true, 'message' => 'Course added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error adding course: ' . $conn->error]);
}

$stmt->close();
closeDbConnection($conn);
?>
