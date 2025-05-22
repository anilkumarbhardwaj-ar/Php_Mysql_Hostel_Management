<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if course ID is provided
if (!isset($_GET['course_id']) || empty($_GET['course_id'])) {
    echo json_encode(['success' => false, 'message' => 'Course ID is required']);
    exit;
}

$courseId = sanitizeInput($_GET['course_id']);

// Get students for the course
$conn = getDbConnection();
$sql = "
    SELECT 
        u.id,
        u.name,
        u.email,
        CONCAT(b.name, ' - ', f.name, ' - ', r.room_number) as room
    FROM 
        users u
    JOIN 
        student_courses sc ON u.id = sc.student_id
    LEFT JOIN 
        allocations a ON u.id = a.user_id AND a.end_date >= CURDATE()
    LEFT JOIN 
        rooms r ON a.room_id = r.id
    LEFT JOIN 
        floors f ON r.floor_id = f.id
    LEFT JOIN 
        buildings b ON f.building_id = b.id
    WHERE 
        sc.course_id = ?
    ORDER BY 
        u.name
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $courseId);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

$stmt->close();
closeDbConnection($conn);

echo json_encode(['success' => true, 'students' => $students]);
?>
