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

// Get payment history
$sql = "
    SELECT 
        p.id,
        p.payment_date,
        p.amount,
        p.payment_method,
        p.reference_number,
        p.status,
        p.notes,
        u.username as recorded_by
    FROM 
        payment_history p
        LEFT JOIN users u ON p.recorded_by = u.id
    WHERE 
        p.user_id = ?
    ORDER BY 
        p.payment_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}

echo json_encode(['success' => true, 'history' => $history]);

$stmt->close();
closeDbConnection($conn);
?>
