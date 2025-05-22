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
$userId = sanitizeInput($_POST['user']);
$amount = sanitizeInput($_POST['amount']);
$paymentDate = sanitizeInput($_POST['paymentDate']);
$paymentMethod = sanitizeInput($_POST['paymentMethod']);
$reference = sanitizeInput($_POST['reference']);
$notes = sanitizeInput($_POST['notes']);

// Validate input data
$errors = [];

if (empty($userId)) {
    $errors[] = 'User is required';
}

if (empty($amount) || $amount <= 0) {
    $errors[] = 'Amount must be greater than 0';
}

if (empty($paymentDate)) {
    $errors[] = 'Payment date is required';
}

if (empty($paymentMethod)) {
    $errors[] = 'Payment method is required';
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
    // Check if payment record exists for user
    $stmt = $conn->prepare("SELECT id, total_amount, paid_amount FROM payments WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing payment record
        $payment = $result->fetch_assoc();
        $paymentId = $payment['id'];
        $totalAmount = $payment['total_amount'];
        $paidAmount = $payment['paid_amount'] + $amount;
        
        // Determine status
        $status = 'partial';
        if ($paidAmount >= $totalAmount) {
            $status = 'paid';
        }
        
        // Update payment record
        $stmt = $conn->prepare("UPDATE payments SET paid_amount = ?, status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("dsi", $paidAmount, $status, $paymentId);
        $stmt->execute();
    } else {
        // Create new payment record
        $stmt = $conn->prepare("INSERT INTO payments (user_id, total_amount, paid_amount, status) VALUES (?, ?, ?, ?)");
        $status = ($amount >= 0) ? 'paid' : 'partial';
        $stmt->bind_param("idds", $userId, $amount, $amount, $status);
        $stmt->execute();
        
        $paymentId = $conn->insert_id;
    }
    
    // Insert payment transaction
    $stmt = $conn->prepare("INSERT INTO payment_transactions (payment_id, amount, payment_date, payment_method, reference_number, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("idssss", $paymentId, $amount, $paymentDate, $paymentMethod, $reference, $notes);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Log activity
    logUserActivity($_SESSION['user_id'], 'Added payment for user ID: ' . $userId);
    
    echo json_encode(['success' => true, 'message' => 'Payment added successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
