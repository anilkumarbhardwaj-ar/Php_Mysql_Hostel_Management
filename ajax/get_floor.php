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

// Check if floor ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Floor ID is required']);
    exit;
}

$floorId = sanitizeInput($_GET['id']);

// Get floor details
$conn = getDbConnection();
$stmt = $conn->prepare("
    SELECT 
        f.id,
        f.name,
        f.building_id
    FROM 
        floors f
    WHERE 
        f.id = ?
");
$stmt->bind_param("i", $floorId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Floor not found']);
    $stmt->close();
    closeDbConnection($conn);
    exit;
}

$floor = $result->fetch_assoc();

$stmt->close();
closeDbConnection($conn);

header('Content-Type: application/json');
echo json_encode(['success' => true, 'floor' => $floor]);
?> 