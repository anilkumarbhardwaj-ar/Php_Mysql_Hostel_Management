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

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo 'Student ID not provided';
    exit;
}

$id = sanitizeInput($_GET['id']);

$conn = getDbConnection();

$stmt = $conn->prepare("
    SELECT u.id, u.name, u.email, sc.course_id, a.room_id, a.start_date, a.end_date
    FROM users u
    LEFT JOIN student_courses sc ON u.id = sc.student_id
    LEFT JOIN allocations a ON u.id = a.user_id
    WHERE u.id = ? AND u.role = 'student'
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $student = $result->fetch_assoc()) {
    // Normalize NULL dates to empty string
    $student['start_date'] = $student['start_date'] ?? '';
    $student['end_date'] = $student['end_date'] ?? '';

    // Generate HTML form fields
    echo '
        <input type="hidden" name="id" value="' . htmlspecialchars($student['id']) . '">
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" value="' . htmlspecialchars($student['name']) . '" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="' . htmlspecialchars($student['email']) . '" required>
        </div>
        <div class="form-group">
            <label>Course</label>
            <select name="course_id" required>
                <option value="">Select Course</option>';
                // Fetch courses
                $course_stmt = $conn->prepare("SELECT id, name FROM courses ORDER BY name");
                $course_stmt->execute();
                $courses = $course_stmt->get_result();
                while ($course = $courses->fetch_assoc()) {
                    $selected = ($course['id'] == $student['course_id']) ? 'selected' : '';
                    echo '<option value="' . $course['id'] . '" ' . $selected . '>' . htmlspecialchars($course['name']) . '</option>';
                }
                $course_stmt->close();
    echo '
            </select>
        </div>
        <div class="form-group">
            <label>Room</label>
            <select name="room_id">
                <option value="">Select Room</option>';
                // Fetch rooms
                $room_stmt = $conn->prepare("
                    SELECT r.id, CONCAT(b.name, ' - ', f.name, ' - ', r.room_number) as room_name
                    FROM rooms r
                    JOIN floors f ON r.floor_id = f.id
                    JOIN buildings b ON f.building_id = b.id
                    ORDER BY b.name, f.name, r.room_number
                ");
                $room_stmt->execute();
                $rooms = $room_stmt->get_result();
                while ($room = $rooms->fetch_assoc()) {
                    $selected = ($room['id'] == $student['room_id']) ? 'selected' : '';
                    echo '<option value="' . $room['id'] . '" ' . $selected . '>' . htmlspecialchars($room['room_name']) . '</option>';
                }
                $room_stmt->close();
    echo '
            </select>
        </div>
        <div class="form-group">
            <label>Start Date</label>
            <input type="date" name="start_date" value="' . htmlspecialchars($student['start_date']) . '">
        </div>
        <div class="form-group">
            <label>End Date</label>
            <input type="date" name="end_date" value="' . htmlspecialchars($student['end_date']) . '">
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Update Student</button>
        </div>
    ';
} else {
    http_response_code(404);
    echo 'Student not found';
}

$stmt->close();
$conn->close();
?>