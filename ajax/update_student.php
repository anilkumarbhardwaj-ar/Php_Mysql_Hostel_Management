<?php
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'staff')) {
    http_response_code(403);
    echo 'Unauthorized access';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Invalid request method';
    exit;
}

// Retrieve and sanitize POST data
$id = sanitizeInput($_POST['id'] ?? '');
$name = sanitizeInput($_POST['name'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$course_id = sanitizeInput($_POST['course_id'] ?? '');
$room_id = sanitizeInput($_POST['room_id'] ?? '');
$start_date = sanitizeInput($_POST['start_date'] ?? '');
$end_date = sanitizeInput($_POST['end_date'] ?? '');

// Validate required fields
if (empty($id) || empty($name) || empty($email)) {
    http_response_code(400);
    echo 'ID, Name, and Email are required';
    exit;
}

// Convert to appropriate types
$course_id = is_numeric($course_id) ? (int)$course_id : null;
$room_id = is_numeric($room_id) ? (int)$room_id : null;
$start_date = $start_date === '' ? null : $start_date;
$end_date = $end_date === '' ? null : $end_date;

$conn = getDbConnection();
$conn->begin_transaction();

try {
    // Update users table
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ? AND role = 'student'");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->bind_param("ssi", $name, $email, $id);
    if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
    $stmt->close();

    // Update or insert into student_courses
    if ($course_id !== null) {
        $stmt = $conn->prepare("SELECT 1 FROM student_courses WHERE student_id = ?");
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
        $result = $stmt->get_result();
        $exists = ($result && $result->num_rows > 0);
        $stmt->close();

        if ($exists) {
            $stmt_update = $conn->prepare("UPDATE student_courses SET course_id = ? WHERE student_id = ?");
            if (!$stmt_update) throw new Exception("Prepare failed: " . $conn->error);
            $stmt_update->bind_param("ii", $course_id, $id);
            if (!$stmt_update->execute()) throw new Exception("Execute failed: " . $stmt_update->error);
            $stmt_update->close();
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)");
            if (!$stmt_insert) throw new Exception("Prepare failed: " . $conn->error);
            $stmt_insert->bind_param("ii", $id, $course_id);
            if (!$stmt_insert->execute()) throw new Exception("Execute failed: " . $stmt_insert->error);
            $stmt_insert->close();
        }
    }

    // Update or insert into allocations
    if ($room_id !== null && $start_date !== null && $end_date !== null) {
        // Check if room is available for the selected dates
        if (!isRoomAvailable($room_id, $start_date, $end_date, $id)) {
            throw new Exception("Room is not available for the selected dates");
        }

        $stmt = $conn->prepare("SELECT 1 FROM allocations WHERE user_id = ?");
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
        $result = $stmt->get_result();
        $exists = ($result && $result->num_rows > 0);
        $stmt->close();

        if ($exists) {
            $stmt_update = $conn->prepare("UPDATE allocations SET room_id = ?, start_date = ?, end_date = ? WHERE user_id = ?");
            if (!$stmt_update) throw new Exception("Prepare failed: " . $conn->error);
            $stmt_update->bind_param("issi", $room_id, $start_date, $end_date, $id);
            if (!$stmt_update->execute()) throw new Exception("Execute failed: " . $stmt_update->error);
            $stmt_update->close();
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO allocations (user_id, room_id, start_date, end_date) VALUES (?, ?, ?, ?)");
            if (!$stmt_insert) throw new Exception("Prepare failed: " . $conn->error);
            $stmt_insert->bind_param("iiss", $id, $room_id, $start_date, $end_date);
            if (!$stmt_insert->execute()) throw new Exception("Execute failed: " . $stmt_insert->error);
            $stmt_insert->close();
        }

        // Update payment record based on new room and dates
        $stmt = $conn->prepare("SELECT type FROM rooms WHERE id = ?");
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        $stmt->bind_param("i", $room_id);
        if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
        $result = $stmt->get_result();
        if ($room = $result->fetch_assoc()) {
            $totalFee = calculateFee($id, $start_date, $end_date);
            
            $stmt_payment = $conn->prepare("UPDATE payments SET total_amount = ? WHERE user_id = ?");
            if (!$stmt_payment) throw new Exception("Prepare failed: " . $conn->error);
            $stmt_payment->bind_param("di", $totalFee, $id);
            if (!$stmt_payment->execute()) throw new Exception("Execute failed: " . $stmt_payment->error);
            $stmt_payment->close();
        }
        $stmt->close();
    }

    // Commit transaction
    $conn->commit();
    
    // Log activity
    logUserActivity($_SESSION['user_id'], 'Updated student: ' . $name);
    
    echo json_encode(['success' => true, 'message' => 'Student updated successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>