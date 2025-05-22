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
$id = sanitizeInput($_POST['id']);

// Validate input data
if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Course ID is required']);
    exit;
}

$conn = getDbConnection();

// Check if course exists
$stmt = $conn->prepare("SELECT name FROM courses WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt->close();
    closeDbConnection($conn);
    echo json_encode(['success' => false, 'message' => 'Course not found']);
    exit;
}

$courseName = $result->fetch_assoc()['name'];

// Check if students are enrolled in this course
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM student_courses WHERE course_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$studentCount = $result->fetch_assoc()['count'];

if ($studentCount > 0) {
    $stmt->close();
    closeDbConnection($conn);
    echo json_encode(['success' => false, 'message' => 'Cannot delete course with enrolled students. Please remove students first.']);
    exit;
}

// Delete course
$stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
$stmt->bind_param("i", $id);
$success = $stmt->execute();

if ($success) {
    // Log activity
    logUserActivity($_SESSION['user_id'], 'Deleted course: ' . $courseName);
    
    echo json_encode(['success' => true, 'message' => 'Course deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting course: ' . $conn->error]);
}

$stmt->close();
closeDbConnection($conn);
?>