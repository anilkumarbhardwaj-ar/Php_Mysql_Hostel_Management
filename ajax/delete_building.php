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
$buildingId = sanitizeInput($data['id']);

if (empty($buildingId)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Building ID is required']);
    exit;
}

$conn = getDbConnection();

// Start transaction
$conn->begin_transaction();

try {
    // Get building name for logging
    $stmt = $conn->prepare("SELECT name FROM buildings WHERE id = ?");
    $stmt->bind_param("i", $buildingId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Building not found');
    }
    
    $building = $result->fetch_assoc();
    $buildingName = $building['name'];
    
    // Check if there are active allocations in any rooms of this building
    $stmt = $conn->prepare("
        SELECT a.id 
        FROM allocations a
        JOIN rooms r ON a.room_id = r.id
        JOIN floors f ON r.floor_id = f.id
        WHERE f.building_id = ? AND a.end_date >= CURDATE()
        LIMIT 1
    ");
    $stmt->bind_param("i", $buildingId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception('Cannot delete building with active allocations');
    }
    
    // Delete allocations for rooms in this building
    $stmt = $conn->prepare("
        DELETE a FROM allocations a
        JOIN rooms r ON a.room_id = r.id
        JOIN floors f ON r.floor_id = f.id
        WHERE f.building_id = ?
    ");
    $stmt->bind_param("i", $buildingId);
    $stmt->execute();
    
    // Delete rooms in this building
    $stmt = $conn->prepare("
        DELETE r FROM rooms r
        JOIN floors f ON r.floor_id = f.id
        WHERE f.building_id = ?
    ");
    $stmt->bind_param("i", $buildingId);
    $stmt->execute();
    
    // Delete floors in this building
    $stmt = $conn->prepare("DELETE FROM floors WHERE building_id = ?");
    $stmt->bind_param("i", $buildingId);
    $stmt->execute();
    
    // Delete building
    $stmt = $conn->prepare("DELETE FROM buildings WHERE id = ?");
    $stmt->bind_param("i", $buildingId);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Log activity
    logUserActivity($_SESSION['user_id'], 'Deleted building: ' . $buildingName);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Building deleted successfully']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>