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
$staffId = sanitizeInput($_POST['staffId']);
$name = sanitizeInput($_POST['name']);
$email = sanitizeInput($_POST['email']);
$phone = sanitizeInput($_POST['phone']);
$staffRole = sanitizeInput($_POST['staffRole']);
$department = sanitizeInput($_POST['department'] ?? '');
$updateRoom = sanitizeInput($_POST['updateRoom'] ?? 'no');
$roomId = sanitizeInput($_POST['room'] ?? '');
$startDate = sanitizeInput($_POST['startDate'] ?? '');
$endDate = sanitizeInput($_POST['endDate'] ?? '');

// Validate input data
$errors = [];

if (empty($staffId)) {
    $errors[] = 'Staff ID is required';
}

if (empty($name)) {
    $errors[] = 'Name is required';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

if (empty($staffRole)) {
    $errors[] = 'Staff role is required';
}

// Check if room update is required
if ($updateRoom == 'yes') {
    if (empty($roomId)) {
        $errors[] = 'Room is required';
    }
    
    if (empty($startDate)) {
        $errors[] = 'Start date is required';
    }
    
    // End date is optional
    
    // If end date is provided, validate it
    if (!empty($endDate) && strtotime($endDate) <= strtotime($startDate)) {
        $errors[] = 'End date must be after start date';
    }
    
    // Check if room is available for the selected dates
    if (!empty($roomId) && !empty($startDate)) {
        // If end date is not provided, use a far future date for availability check
        $checkEndDate = !empty($endDate) ? $endDate : '2099-12-31';
        
        if (!isRoomAvailableForUpdate($roomId, $startDate, $checkEndDate, $staffId)) {
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
    // Check if email already exists for another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $staffId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists for another user']);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    // Update user
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $email, $phone, $staffId);
    $stmt->execute();
    
    // Check if staff details exist
    $stmt = $conn->prepare("SELECT id FROM staff_details WHERE user_id = ?");
    $stmt->bind_param("i", $staffId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update staff details
        $stmt = $conn->prepare("UPDATE staff_details SET role = ?, department = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $staffRole, $department, $staffId);
        $stmt->execute();
    } else {
        // Insert staff details
        $stmt = $conn->prepare("INSERT INTO staff_details (user_id, role, department) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $staffId, $staffRole, $department);
        $stmt->execute();
    }
    
    // Update room assignment if required
    if ($updateRoom == 'yes' && !empty($roomId) && !empty($startDate)) {
        // End any current allocations
        $stmt = $conn->prepare("UPDATE allocations SET end_date = CURDATE() WHERE user_id = ? AND end_date > CURDATE()");
        $stmt->bind_param("i", $staffId);
        $stmt->execute();
        
        // Insert new allocation
        if (empty($endDate)) {
            $stmt = $conn->prepare("INSERT INTO allocations (user_id, room_id, start_date, end_date) VALUES (?, ?, ?, NULL)");
            $stmt->bind_param("iis", $staffId, $roomId, $startDate);
        } else {
            $stmt = $conn->prepare("INSERT INTO allocations (user_id, room_id, start_date, end_date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $staffId, $roomId, $startDate, $endDate);
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
        $totalFee = calculateFee($staffId, $startDate, $feeEndDate);
        
        // Insert payment record
        $stmt = $conn->prepare("INSERT INTO payments (user_id, total_amount, paid_amount, status) VALUES (?, ?, 0, 'unpaid')");
        $stmt->bind_param("id", $staffId, $totalFee);
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Log activity
    logUserActivity($_SESSION['user_id'], 'Updated staff: ' . $name);
    
    echo json_encode(['success' => true, 'message' => 'Staff updated successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();

// Function to check if room is available for update (excluding current user's allocation)
function isRoomAvailableForUpdate($roomId, $startDate, $endDate, $userId) {
    $conn = getDbConnection();
    
    $sql = "
        SELECT 
            COUNT(*) as count,
            r.capacity
        FROM 
            allocations a
        JOIN 
            rooms r ON a.room_id = r.id
        WHERE 
            a.room_id = ? 
            AND a.user_id != ?
            AND (
                (a.start_date <= ? AND (a.end_date >= ? OR a.end_date IS NULL))
                OR (a.start_date <= ? AND (a.end_date >= ? OR a.end_date IS NULL))
                OR (a.start_date >= ? AND (a.end_date <= ? OR a.end_date IS NULL))
            )
        GROUP BY 
            r.id
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissssss", $roomId, $userId, $startDate, $startDate, $endDate, $endDate, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // No overlapping allocations found
        $stmt->close();
        
        // Get room capacity
        $sql = "SELECT capacity FROM rooms WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $result = $stmt->get_result();
        $room = $result->fetch_assoc();
        
        $stmt->close();
        $conn->close();
        
        return true;
    }
    
    $row = $result->fetch_assoc();
    $isAvailable = $row['count'] < $row['capacity'];
    
    $stmt->close();
    $conn->close();
    
    return $isAvailable;
}
?>