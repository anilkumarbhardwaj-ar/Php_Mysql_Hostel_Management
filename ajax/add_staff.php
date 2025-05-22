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
$staffRole = sanitizeInput($_POST['staffRole']);
$department = sanitizeInput($_POST['department'] ?? '');
$joiningDate = sanitizeInput($_POST['joiningDate'] ?? '');
$assignRoom = sanitizeInput($_POST['assignRoom']);
$roomId = sanitizeInput($_POST['room'] ?? '');
$startDate = sanitizeInput($_POST['startDate'] ?? '');
$endDate = sanitizeInput($_POST['endDate'] ?? '');

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

if (empty($staffRole)) {
    $errors[] = 'Staff role is required';
}

// Check if room assignment is required
if ($assignRoom == 'yes') {
    if (empty($roomId)) {
        $errors[] = 'Room is required';
    }
    
    if (empty($startDate)) {
        $errors[] = 'Start date is required';
    }
    
    // End date is now optional
    
    // If end date is provided, validate it
    if (!empty($endDate) && strtotime($endDate) <= strtotime($startDate)) {
        $errors[] = 'End date must be after start date';
    }
    
    // Check if room is available for the selected dates
    if (!empty($roomId) && !empty($startDate)) {
        // If end date is not provided, use a far future date for availability check
        $checkEndDate = !empty($endDate) ? $endDate : '2099-12-31';
        
        if (!isRoomAvailable($roomId, $startDate, $checkEndDate)) {
            $errors[] = 'Room is not available for the selected dates';
        }
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
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, 'staff', ?)");
    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $phone);
    $stmt->execute();
    
    $userId = $conn->insert_id;
    
    // Send welcome email
    $emailResult = sendWelcomeEmail($name, $email, $password, 'staff');
    
    // Insert staff details
    $stmt = $conn->prepare("INSERT INTO staff_details (user_id, role, department, joining_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $staffRole, $department, $joiningDate);
    $stmt->execute();
    
    // Assign room if required
    if ($assignRoom == 'yes' && !empty($roomId) && !empty($startDate)) {
        // If end date is not provided, use NULL in the database
        if (empty($endDate)) {
            $stmt = $conn->prepare("INSERT INTO allocations (user_id, room_id, start_date, end_date) VALUES (?, ?, ?, NULL)");
            $stmt->bind_param("iis", $userId, $roomId, $startDate);
        } else {
            $stmt = $conn->prepare("INSERT INTO allocations (user_id, room_id, start_date, end_date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $userId, $roomId, $startDate, $endDate);
        }
        $stmt->execute();
        
        // Get room type and calculate fee
        $stmt = $conn->prepare("SELECT type FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $result = $stmt->get_result();
        $room = $result->fetch_assoc();
        
        // Calculate fee based on room type and dates
        // If end date is not provided, calculate for 1 month by default
        $feeEndDate = !empty($endDate) ? $endDate : date('Y-m-d', strtotime($startDate . ' + 1 month'));
        $totalFee = calculateFee($userId, $startDate, $feeEndDate);
        
        // Insert payment record
        $stmt = $conn->prepare("INSERT INTO payments (user_id, total_amount, paid_amount, status) VALUES (?, ?, 0, 'unpaid')");
        $stmt->bind_param("id", $userId, $totalFee);
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Log activity
    logUserActivity($_SESSION['user_id'], 'Added staff: ' . $name);
    
    $response = ['success' => true, 'message' => 'Staff added successfully'];
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