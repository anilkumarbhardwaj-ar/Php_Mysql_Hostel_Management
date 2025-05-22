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
    SELECT 
        u.id, u.name, u.email, u.created_at,
        c.name as course_name, sc.join_date,
        CONCAT(b.name, ' - ', f.name, ' - ', r.room_number) as room,
        r.type as room_type, a.start_date, a.end_date,
        p.total_amount, p.paid_amount, p.status as payment_status
    FROM users u
    LEFT JOIN student_courses sc ON u.id = sc.student_id
    LEFT JOIN courses c ON sc.course_id = c.id
    LEFT JOIN allocations a ON u.id = a.user_id
    LEFT JOIN rooms r ON a.room_id = r.id
    LEFT JOIN floors f ON r.floor_id = f.id
    LEFT JOIN buildings b ON f.building_id = b.id
    LEFT JOIN payments p ON u.id = p.user_id
    WHERE u.id = ? AND u.role = 'student'
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $student = $result->fetch_assoc()) {
    // Format dates
    $created_at = !empty($student['created_at']) ? date('M d, Y', strtotime($student['created_at'])) : 'N/A';
    $join_date = !empty($student['join_date']) ? date('M d, Y', strtotime($student['join_date'])) : 'N/A';
    $start_date = !empty($student['start_date']) ? date('M d, Y', strtotime($student['start_date'])) : 'N/A';
    $end_date = !empty($student['end_date']) ? date('M d, Y', strtotime($student['end_date'])) : 'N/A';
    
    // Calculate payment status
    $payment_status = $student['payment_status'] ?? 'N/A';
    $payment_class = '';
    switch ($payment_status) {
        case 'paid':
            $payment_class = 'status-paid';
            break;
        case 'partial':
            $payment_class = 'status-partial';
            break;
        case 'unpaid':
            $payment_class = 'status-unpaid';
            break;
    }
    
    // Calculate remaining amount
    $total_amount = $student['total_amount'] ?? 0;
    $paid_amount = $student['paid_amount'] ?? 0;
    $remaining_amount = $total_amount - $paid_amount;
    
    echo '
    <div class="student-details">
        <h3>' . htmlspecialchars($student['name']) . '</h3>
        <div class="detail-row">
            <div class="detail-label">Email:</div>
            <div class="detail-value">' . htmlspecialchars($student['email']) . '</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Registered:</div>
            <div class="detail-value">' . $created_at . '</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Course:</div>
            <div class="detail-value">' . htmlspecialchars($student['course_name'] ?? 'N/A') . '</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Join Date:</div>
            <div class="detail-value">' . $join_date . '</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Room:</div>
            <div class="detail-value">' . htmlspecialchars($student['room'] ?? 'N/A') . '</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Room Type:</div>
            <div class="detail-value">' . htmlspecialchars($student['room_type'] ?? 'N/A') . '</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Start Date:</div>
            <div class="detail-value">' . $start_date . '</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">End Date:</div>
            <div class="detail-value">' . $end_date . '</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Total Fee:</div>
            <div class="detail-value">$' . number_format($total_amount, 2) . '</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Paid Amount:</div>
            <div class="detail-value">$' . number_format($paid_amount, 2) . '</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Remaining:</div>
            <div class="detail-value">$' . number_format($remaining_amount, 2) . '</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Payment Status:</div>
            <div class="detail-value"><span class="status-badge ' . $payment_class . '">' . ucfirst($payment_status) . '</span></div>
        </div>
    </div>
    ';
} else {
    echo '<div class="error-message">Student not found</div>';
}

$stmt->close();
$conn->close();
?>