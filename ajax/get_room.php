<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if room ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Room ID is required']);
    exit;
}

$roomId = sanitizeInput($_GET['id']);

// Get room details
$conn = getDbConnection();
$stmt = $conn->prepare("
    SELECT 
        r.id,
        r.room_number,
        r.floor_id,
        r.type,
        r.capacity,
        r.description,
        f.building_id
    FROM 
        rooms r
    JOIN 
        floors f ON r.floor_id = f.id
    WHERE 
        r.id = ?
");
$stmt->bind_param("i", $roomId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Room not found']);
    $stmt->close();
    closeDbConnection($conn);
    exit;
}

$room = $result->fetch_assoc();

$stmt->close();
closeDbConnection($conn);

header('Content-Type: application/json');
echo json_encode(['success' => true, 'room' => $room]);
?>