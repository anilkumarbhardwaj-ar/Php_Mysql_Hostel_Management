<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/mail_helper.php';

// Check if user is logged in and has appropriate role
if (!isLoggedIn() || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'staff')) {
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
$courseId = sanitizeInput($_POST['course']);
$roomId = sanitizeInput($_POST['room']);
$startDate = sanitizeInput($_POST['startDate']);
$endDate = sanitizeInput($_POST['endDate']);

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

if (empty($courseId)) {
    $errors[] = 'Course is required';
}

if (empty($roomId)) {
    $errors[] = 'Room is required';
}

if (empty($startDate)) {
    $errors[] = 'Start date is required';
}

if (empty($endDate)) {
    $errors[] = 'End date is required';
}

if (strtotime($endDate) <= strtotime($startDate)) {
    $errors[] = 'End date must be after start date';
}

// Check if room is available for the selected dates
if (!empty($roomId) && !empty($startDate) && !empty($endDate)) {
    if (!isRoomAvailable($roomId, $startDate, $endDate)) {
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
        $stmt->close();
        $conn->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Email already exists. Please use a different email address.']);
        exit;
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
    $stmt->bind_param("sss", $name, $email, $hashedPassword);
    $stmt->execute();
    
    $userId = $conn->insert_id;
    
    // Insert student course
    $stmt = $conn->prepare("INSERT INTO student_courses (student_id, course_id, join_date) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $userId, $courseId, $startDate);
    $stmt->execute();
    
    // Insert room allocation
    $stmt = $conn->prepare("INSERT INTO allocations (user_id, room_id, start_date, end_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $userId, $roomId, $startDate, $endDate);
    $stmt->execute();
    
    // Get room type and calculate fee
    $stmt = $conn->prepare("SELECT type FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $result = $stmt->get_result();
    $room = $result->fetch_assoc();
    
    $totalFee = calculateFee($userId, $startDate, $endDate);
    
    // Insert payment record
    $stmt = $conn->prepare("INSERT INTO payments (user_id, total_amount, paid_amount, status) VALUES (?, ?, 0, 'unpaid')");
    $stmt->bind_param("id", $userId, $totalFee);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Send welcome email after all data is committed
    $emailResult = sendWelcomeEmail($name, $email, $password, 'student');
    
    // Log activity
    logUserActivity($_SESSION['user_id'], 'Added student: ' . $name);
    
    $response = ['success' => true, 'message' => 'Student added successfully'];
    if (!$emailResult['success']) {
        $response['message'] .= ' (Note: Welcome email could not be sent)';
        error_log("Email Error: " . $emailResult['message']);
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
exit;
?>