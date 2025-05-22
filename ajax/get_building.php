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
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Building ID is required']);
    exit;
}

$buildingId = sanitizeInput($_GET['id']);

// Get building details
$conn = getDbConnection();
$stmt = $conn->prepare("
    SELECT 
        id,
        name,
        location,
        description
    FROM 
        buildings
    WHERE 
        id = ?
");
$stmt->bind_param("i", $buildingId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Building not found']);
    $stmt->close();
    closeDbConnection($conn);
    exit;
}

$building = $result->fetch_assoc();

$stmt->close();
closeDbConnection($conn);

header('Content-Type: application/json');
echo json_encode(['success' => true, 'building' => $building]);
?>