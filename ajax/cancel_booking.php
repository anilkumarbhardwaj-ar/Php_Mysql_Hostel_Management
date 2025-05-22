<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is logged in and has appropriate role
if (!isLoggedIn() || !in_array($_SESSION['user_role'], ['student', 'guest', 'others'])) {
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
$bookingId = sanitizeInput($data['booking_id']);

if (empty($bookingId)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
    exit;
}

$conn = getDbConnection();

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Check if booking exists and belongs to the user
    $stmt = $conn->prepare("
        SELECT id, status 
        FROM bookings 
        WHERE id = ? AND user_id = ? AND status = 'pending'
    ");
    $stmt->bind_param("ii", $bookingId, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Booking not found or cannot be cancelled');
    }
    
    // Update booking status
    $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    
    // Update payment status if exists
    $stmt = $conn->prepare("UPDATE payments SET status = 'cancelled' WHERE booking_id = ?");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    
    // Log activity
    logUserActivity($_SESSION['user_id'], 'Cancelled booking ID: ' . $bookingId);
    
    // Commit transaction
    $conn->commit();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$stmt->close();
closeDbConnection($conn);
?> 