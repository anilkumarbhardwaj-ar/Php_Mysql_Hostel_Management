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

// Get and sanitize input data
$roomNumber = sanitizeInput($_POST['roomNumber']);
$floorId = sanitizeInput($_POST['floor']);
$type = sanitizeInput($_POST['type']);
$capacity = sanitizeInput($_POST['capacity']);
$description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : '';

// Validate input data
$errors = [];

if (empty($roomNumber)) {
    $errors[] = 'Room number is required';
}

if (empty($floorId)) {
    $errors[] = 'Floor is required';
}

if (empty($type)) {
    $errors[] = 'Room type is required';
}

if (empty($capacity) || $capacity < 1) {
    $errors[] = 'Capacity must be at least 1';
}

// Check if room number already exists on the same floor
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT id FROM rooms WHERE room_number = ? AND floor_id = ?");
$stmt->bind_param("si", $roomNumber, $floorId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $errors[] = 'Room number already exists on this floor';
}

// If there are errors, return them
if (!empty($errors)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    $stmt->close();
    closeDbConnection($conn);
    exit;
}

// Insert room
$stmt = $conn->prepare("INSERT INTO rooms (floor_id, room_number, type, capacity, description) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issis", $floorId, $roomNumber, $type, $capacity, $description);
$success = $stmt->execute();

if ($success) {
    // Log activity
    logUserActivity($_SESSION['user_id'], 'Added room: ' . $roomNumber);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Room added successfully']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error adding room: ' . $conn->error]);
}

$stmt->close();
closeDbConnection($conn);
?>