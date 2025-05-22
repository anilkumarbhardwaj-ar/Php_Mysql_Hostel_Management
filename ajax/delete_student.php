<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = sanitizeInput($_POST['id']);

if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit;
}

$conn = getDbConnection();
$conn->begin_transaction();

try {
    // Optional: Get student name for logging
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ? AND role = 'student'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $studentName = $student ? $student['name'] : 'Unknown';

    // Delete dependencies
    $conn->query("DELETE FROM student_courses WHERE student_id = $id");
    $conn->query("DELETE FROM allocations WHERE user_id = $id");
    $conn->query("DELETE FROM payments WHERE user_id = $id");

    // Delete user
    $conn->query("DELETE FROM users WHERE id = $id AND role = 'student'");

    $conn->commit();
    logUserActivity($_SESSION['user_id'], 'Deleted student: ' . $studentName);

    echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>