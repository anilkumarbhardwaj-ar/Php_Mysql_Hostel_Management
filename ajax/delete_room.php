<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is logged in and has appropriate role
if (!isLoggedIn() || $_SESSION['user_role'] != 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$roomId = sanitizeInput($data['id']);

if (empty($roomId)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Room ID is required']);
    exit;
}

$conn = getDbConnection();

// Check if room has allocations
$stmt = $conn->prepare("SELECT id FROM allocations WHERE room_id = ? AND end_date >= CURDATE()");
$stmt->bind_param("i", $roomId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Cannot delete room with active allocations.']);
    $stmt->close();
    closeDbConnection($conn);
    exit;
}

// Delete room
$stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
$stmt->bind_param("i", $roomId);
$success = $stmt->execute();

if ($success) {
    // Log activity
    logUserActivity($_SESSION['user_id'], 'Deleted room ID: ' . $roomId);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Room deleted successfully']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error deleting room: ' . $conn->error]);
}

$stmt->close();
closeDbConnection($conn);
?>