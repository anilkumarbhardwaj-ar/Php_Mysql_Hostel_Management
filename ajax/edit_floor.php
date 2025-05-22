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
$floorId = sanitizeInput($_POST['floorId']);
$buildingId = sanitizeInput($_POST['building']);
$floorName = sanitizeInput($_POST['floorName']);

// Validate input data
$errors = [];

if (empty($floorId)) {
    $errors[] = 'Floor ID is required';
}

if (empty($buildingId)) {
    $errors[] = 'Building is required';
}

if (empty($floorName)) {
    $errors[] = 'Floor name is required';
}

// Check if floor name already exists in the building for a different floor
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT id FROM floors WHERE building_id = ? AND name = ? AND id != ?");
$stmt->bind_param("isi", $buildingId, $floorName, $floorId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $errors[] = 'Floor name already exists in this building';
}

// If there are errors, return them
if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    $stmt->close();
    closeDbConnection($conn);
    exit;
}

// Update floor
$stmt = $conn->prepare("UPDATE floors SET building_id = ?, name = ? WHERE id = ?");
$stmt->bind_param("isi", $buildingId, $floorName, $floorId);
$success = $stmt->execute();

if ($success) {
    // Log activity
    logUserActivity($_SESSION['user_id'], 'Updated floor: ' . $floorName);
    
    echo json_encode(['success' => true, 'message' => 'Floor updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating floor: ' . $conn->error]);
}

$stmt->close();
closeDbConnection($conn);
?>