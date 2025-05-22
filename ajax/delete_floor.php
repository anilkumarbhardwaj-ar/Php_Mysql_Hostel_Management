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
$floorId = sanitizeInput($data['id']);

if (empty($floorId)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Floor ID is required']);
    exit;
}

$conn = getDbConnection();

// Start transaction
$conn->begin_transaction();

try {
    // Get floor name for logging
    $stmt = $conn->prepare("SELECT name FROM floors WHERE id = ?");
    $stmt->bind_param("i", $floorId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Floor not found');
    }
    
    $floor = $result->fetch_assoc();
    $floorName = $floor['name'];
    
    // Check if there are active allocations in any rooms on this floor
    $stmt = $conn->prepare("
        SELECT a.id 
        FROM allocations a
        JOIN rooms r ON a.room_id = r.id
        WHERE r.floor_id = ? AND a.end_date >= CURDATE()
        LIMIT 1
    ");
    $stmt->bind_param("i", $floorId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception('Cannot delete floor with active allocations');
    }
    
    // Delete allocations for rooms on this floor
    $stmt = $conn->prepare("
        DELETE a FROM allocations a
        JOIN rooms r ON a.room_id = r.id
        WHERE r.floor_id = ?
    ");
    $stmt->bind_param("i", $floorId);
    $stmt->execute();
    
    // Delete rooms on this floor
    $stmt = $conn->prepare("DELETE FROM rooms WHERE floor_id = ?");
    $stmt->bind_param("i", $floorId);
    $stmt->execute();
    
    // Delete floor
    $stmt = $conn->prepare("DELETE FROM floors WHERE id = ?");
    $stmt->bind_param("i", $floorId);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Log activity
    logUserActivity($_SESSION['user_id'], 'Deleted floor: ' . $floorName);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Floor deleted successfully']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>