<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is logged in and has admin role
if (!isLoggedIn() || $_SESSION['user_role'] != 'admin') {
    header('Location: login.php');
    exit;
}

// Check if room ID is provided
if (!isset($_GET['room_id']) || empty($_GET['room_id'])) {
    header('Location: rooms.php');
    exit;
}

$roomId = sanitizeInput($_GET['room_id']);
$conn = getDbConnection();

// Check if room exists and has not reached capacity
$checkCapacitySql = "
    SELECT 
        r.id,
        r.room_number,
        r.capacity,
        COUNT(DISTINCT a.user_id) as current_occupants
    FROM 
        rooms r
    LEFT JOIN 
        allocations a ON r.id = a.room_id AND a.end_date >= CURDATE()
    WHERE 
        r.id = ?
    GROUP BY 
        r.id, r.room_number, r.capacity
";

$stmt = $conn->prepare($checkCapacitySql);
$stmt->bind_param("i", $roomId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Room not found';
    header('Location: rooms.php');
    $stmt->close();
    closeDbConnection($conn);
    exit;
}

$room = $result->fetch_assoc();

// Only prevent allocation if room is at capacity
if ($room['current_occupants'] >= $room['capacity']) {
    $_SESSION['error'] = 'Room has reached maximum capacity';
    header('Location: view_room.php?id=' . $roomId);
    $stmt->close();
    closeDbConnection($conn);
    exit;
}

// Continue with the rest of your add_allocation.php code
// ...

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Your existing code for processing the allocation
    // ...
    
    // Double check capacity before creating allocation
    $checkCapacitySql = "
        SELECT 
            r.capacity,
            COUNT(DISTINCT a.user_id) as current_occupants
        FROM 
            rooms r
        LEFT JOIN 
            allocations a ON r.id = a.room_id AND a.end_date >= CURDATE()
        WHERE 
            r.id = ?
        GROUP BY 
            r.id, r.capacity
    ";

    $stmt = $conn->prepare($checkCapacitySql);
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $result = $stmt->get_result();
    $roomData = $result->fetch_assoc();

    // Only prevent allocation if room is at capacity
    if ($roomData['current_occupants'] >= $roomData['capacity']) {
        $_SESSION['error'] = 'Room has reached maximum capacity';
        header('Location: view_room.php?id=' . $roomId);
        $stmt->close();
        closeDbConnection($conn);
        exit;
    }
    
    // Continue with creating the allocation
    // ...
}

// Rest of your add_allocation.php code
// ...

?>