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

// Check if building ID is provided
if (!isset($_GET['building_id']) || empty($_GET['building_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Building ID is required']);
    exit;
}

$buildingId = sanitizeInput($_GET['building_id']);

// Get floors for the building
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT id, name FROM floors WHERE building_id = ? ORDER BY name");
$stmt->bind_param("i", $buildingId);
$stmt->execute();
$result = $stmt->get_result();

$floors = [];
while ($row = $result->fetch_assoc()) {
    $floors[] = $row;
}

$stmt->close();
closeDbConnection($conn);

header('Content-Type: application/json');
echo json_encode(['success' => true, 'floors' => $floors]);
?>