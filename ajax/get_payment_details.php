<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is logged in and has appropriate role
if (!isLoggedIn() || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if user ID is provided
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

$userId = sanitizeInput($_GET['user_id']);
$conn = getDbConnection();

// Get payment details
$sql = "
    SELECT 
        p.total_amount,
        p.paid_amount,
        (p.total_amount - p.paid_amount) as due_amount,
        p.status
    FROM 
        payments p
    WHERE 
        p.user_id = ?
    ORDER BY 
        p.id DESC
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $payment = $result->fetch_assoc();
    echo json_encode(['success' => true, 'payment' => $payment]);
} else {
    echo json_encode(['success' => false, 'message' => 'No payment record found']);
}

$stmt->close();
closeDbConnection($conn);
?> 