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

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$allocationId = sanitizeInput($data['id']);

if (empty($allocationId)) {
    echo json_encode(['success' => false, 'message' => 'Allocation ID is required']);
    exit;
}

$conn = getDbConnection();

// Get allocation details for logging
$stmt = $conn->prepare("
    SELECT 
        a.id, 
        r.room_number, 
        s.name as student_name 
    FROM 
        allocations a
    JOIN 
        rooms r ON a.room_id = r.id
    JOIN 
        students s ON a.user_id = s.id
    WHERE 
        a.id = ?
");
$stmt->bind_param("i", $allocationId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Allocation not found']);
    $stmt->close();
    closeDbConnection($conn);
    exit;
}

$allocation = $result->fetch_assoc();

// Delete allocation
$stmt = $conn->prepare("DELETE FROM allocations WHERE id = ?");
$stmt->bind_param("i", $allocationId);
$success = $stmt->execute();

if ($success) {
    // Log activity
    logUserActivity($_SESSION['user_id'], 'Deleted allocation for student: ' . $allocation['student_name'] . ' from room: ' . $allocation['room_number']);
    
    echo json_encode(['success' => true, 'message' => 'Allocation deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting allocation: ' . $conn->error]);
}

$stmt->close();
closeDbConnection($conn);
?>