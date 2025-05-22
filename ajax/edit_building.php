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
$buildingId = isset($_POST['buildingId']) ? sanitizeInput($_POST['buildingId']) : '';
$buildingName = isset($_POST['buildingName']) ? sanitizeInput($_POST['buildingName']) : '';
$location = isset($_POST['location']) ? sanitizeInput($_POST['location']) : '';

// Log received data for debugging
error_log("Received POST data: " . print_r($_POST, true));

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

// If there are validation errors, return them
if (!empty($errors)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    // Check if building name already exists for a different building
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id FROM buildings WHERE name = ? AND id != ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("si", $buildingName, $buildingId);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        throw new Exception('Building name already exists');
    }

    // Update building
    $stmt = $conn->prepare("UPDATE buildings SET name = ?, location = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ssi", $buildingName, $location, $buildingId);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    // Log activity
    logUserActivity($_SESSION['user_id'], 'Updated building: ' . $buildingName);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Building updated successfully']);

} catch (Exception $e) {
    error_log("Error in edit_building.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        closeDbConnection($conn);
    }
}
?>