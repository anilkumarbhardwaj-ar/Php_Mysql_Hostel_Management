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

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate required fields
$requiredFields = ['user_id', 'total_amount', 'amount', 'payment_method', 'payment_date'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
        exit;
    }
}

$userId = sanitizeInput($_POST['user_id']);
$totalAmount = sanitizeInput($_POST['total_amount']);
$amount = sanitizeInput($_POST['amount']);
$paymentMethod = sanitizeInput($_POST['payment_method']);
$paymentDate = sanitizeInput($_POST['payment_date']);
$referenceNumber = isset($_POST['reference_number']) ? sanitizeInput($_POST['reference_number']) : '';
$notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : '';

$conn = getDbConnection();

// Start transaction
$conn->begin_transaction();

try {
    // Check if payment record already exists
    $sql = "SELECT id FROM payments WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing payment record
        $sql = "
            UPDATE payments 
            SET 
                total_amount = ?,
                paid_amount = paid_amount + ?,
                status = CASE 
                    WHEN (? - (paid_amount + ?)) <= 0 THEN 'paid'
                    ELSE 'partial'
                END,
                updated_at = NOW()
            WHERE 
                user_id = ?
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ddddi", $totalAmount, $amount, $totalAmount, $amount, $userId);
    } else {
        // Create new payment record
        $sql = "
            INSERT INTO payments (
                user_id,
                total_amount,
                paid_amount,
                status,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, NOW(), NOW())
        ";
        
        $status = ($amount >= $totalAmount) ? 'paid' : 'partial';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("idds", $userId, $totalAmount, $amount, $status);
    }
    
    $stmt->execute();
    
    // Add payment history record
    $sql = "
        INSERT INTO payment_history (
            user_id,
            payment_date,
            amount,
            payment_method,
            reference_number,
            notes,
            recorded_by,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdsssi", $userId, $paymentDate, $amount, $paymentMethod, $referenceNumber, $notes, $_SESSION['user_id']);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Payment added successfully']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
closeDbConnection($conn);
?> 