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
$buildingId = sanitizeInput($_POST['buildingId']);
$buildingName = sanitizeInput($_POST['buildingName']);
$location = sanitizeInput($_POST['location']);
$description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : '';

// Validate input data
$errors = [];

if (empty($buildingId)) {
    $errors[] = 'Building ID is required';
}

if (empty($buildingName)) {
    $errors[] = 'Building name is required';
}

if (empty($location)) {
    $errors[] = 'Location is required';
}

// Check if building name already exists for a different building
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT id FROM buildings WHERE name = ? AND id != ?");
$stmt->bind_param("si", $buildingName, $buildingId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $errors[] = 'Building name already exists';
}

// If there are errors, return them
if (!empty($errors)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    $stmt->close();
    closeDbConnection($conn);
    exit;
}

// Update building
$stmt = $conn->prepare("UPDATE buildings SET name = ?, location = ?, description = ? WHERE id = ?");
$stmt->bind_param("sssi", $buildingName, $location, $description, $buildingId);
$success = $stmt->execute();

if ($success) {
    // Log activity
    logUserActivity($_SESSION['user_id'], 'Updated building: ' . $buildingName);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Building updated successfully']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error updating building: ' . $conn->error]);
}

$stmt->close();
closeDbConnection($conn);
?>