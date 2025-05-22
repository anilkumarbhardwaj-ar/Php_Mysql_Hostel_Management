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
$guestId = sanitizeInput($_POST['guestId']);
$name = sanitizeInput($_POST['name']);
$email = sanitizeInput($_POST['email']);
$phone = sanitizeInput($_POST['phone']);
$address = sanitizeInput($_POST['address'] ?? '');
$status = sanitizeInput($_POST['status']);
$checkOut = sanitizeInput($_POST['checkOut'] ?? '');

// Validate input data
$errors = [];

if (empty($guestId)) {
    $errors[] = 'Guest ID is required';
}

if (empty($name)) {
    $errors[] = 'Name is required';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

if (empty($phone)) {
    $errors[] = 'Phone is required';
}

if (empty($status)) {
    $errors[] = 'Status is required';
}

// If there are errors, return them
if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Start transaction
$conn = getDbConnection();
$conn->begin_transaction();

try {
    // Check if email already exists for another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $guestId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists for another user']);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    // Update user
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $name, $email, $phone, $address, $guestId);
    $stmt->execute();
    
    // Update allocation status
    $stmt = $conn->prepare("UPDATE allocations SET status = ? WHERE user_id = ? AND end_date >= CURDATE()");
    $stmt->bind_param("si", $status, $guestId);
    $stmt->execute();
    
    // Update check-out date if provided
    if (!empty($checkOut)) {
        $stmt = $conn->prepare("UPDATE allocations SET end_date = ? WHERE user_id = ? AND end_date >= CURDATE()");
        $stmt->bind_param("si", $checkOut, $guestId);
        $stmt->execute();
        
        // Recalculate payment amount if check-out date changed
        $stmt = $conn->prepare("
            SELECT 
                a.start_date,
                r.price_per_day,
                p.id as payment_id
            FROM 
                allocations a
            JOIN 
                rooms r ON a.room_id = r.id
            LEFT JOIN 
                payments p ON a.user_id = p.user_id
            WHERE 
                a.user_id = ?
                AND a.end_date >= CURDATE()
            ORDER BY 
                p.created_at DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $guestId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Calculate total fee using the new calculateFee function
            $totalFee = calculateFee($guestId, $row['start_date'], $checkOut);
            
            // Update payment amount
            $stmt = $conn->prepare("UPDATE payments SET total_amount = ? WHERE id = ?");
            $stmt->bind_param("di", $totalFee, $row['payment_id']);
            $stmt->execute();
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Log activity
    logUserActivity($_SESSION['user_id'], 'Updated guest: ' . $name);
    
    echo json_encode(['success' => true, 'message' => 'Guest updated successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>