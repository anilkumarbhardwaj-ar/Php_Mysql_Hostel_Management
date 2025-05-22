<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/mail_helper.php';

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
$name = sanitizeInput($_POST['name']);
$email = sanitizeInput($_POST['email']);
$password = sanitizeInput($_POST['password']);
$phone = sanitizeInput($_POST['phone']);
$address = sanitizeInput($_POST['address'] ?? '');
$roomId = sanitizeInput($_POST['room']);
$checkIn = sanitizeInput($_POST['checkIn']);
$checkOut = sanitizeInput($_POST['checkOut']);
$paymentMethod = sanitizeInput($_POST['paymentMethod']);

// Validate input data
$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

if (empty($password)) {
    $errors[] = 'Password is required';
} elseif (strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters';
}

if (empty($phone)) {
    $errors[] = 'Phone is required';
}

if (empty($roomId)) {
    $errors[] = 'Room is required';
}

if (empty($checkIn)) {
    $errors[] = 'Check-in date is required';
}

if (empty($checkOut)) {
    $errors[] = 'Check-out date is required';
}

if (!empty($checkIn) && !empty($checkOut) && strtotime($checkOut) <= strtotime($checkIn)) {
    $errors[] = 'Check-out date must be after check-in date';
}

if (empty($paymentMethod)) {
    $errors[] = 'Payment method is required';
}

// Check if room is available for the selected dates
if (!empty($roomId) && !empty($checkIn) && !empty($checkOut)) {
    if (!isRoomAvailable($roomId, $checkIn, $checkOut)) {
        $errors[] = 'Room is not available for the selected dates';
    }
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
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, phone, address) VALUES (?, ?, ?, 'guest', ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $hashedPassword, $phone, $address);
    $stmt->execute();
    
    $userId = $conn->insert_id;
    
    // Send welcome email
    $emailResult = sendWelcomeEmail($name, $email, $password, 'guest');
    
    // Insert allocation
    $status = 'active';
    $stmt = $conn->prepare("INSERT INTO allocations (user_id, room_id, start_date, end_date, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $userId, $roomId, $checkIn, $checkOut, $status);
    $stmt->execute();
    
    // Get room type and calculate fee
    $stmt = $conn->prepare("SELECT type, price_per_day FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $result = $stmt->get_result();
    $room = $result->fetch_assoc();
    
    // Calculate total fee using the new calculateFee function
    $totalFee = calculateFee($userId, $checkIn, $checkOut);
    
    // Insert payment record
    $paymentDate = date('Y-m-d');
    $paymentStatus = 'pending';
    $stmt = $conn->prepare("INSERT INTO payments (user_id, total_amount, paid_amount, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idds", $userId, $totalFee, $totalFee, $paymentStatus);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Log activity
    logUserActivity($_SESSION['user_id'], 'Added guest: ' . $name);
    
    $response = ['success' => true, 'message' => 'Guest added successfully'];
    if (!$emailResult['success']) {
        $response['message'] .= ' (Note: Welcome email could not be sent)';
        error_log("Email Error: " . $emailResult['message']);
    }
    
    echo json_encode($response);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>