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

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and sanitize input data
$guestId = sanitizeInput($_GET['id']);
$detailed = isset($_GET['detailed']) ? true : false;

// Validate input data
if (empty($guestId)) {
    echo json_encode(['success' => false, 'message' => 'Guest ID is required']);
    exit;
}

$conn = getDbConnection();

try {
    // Get basic guest details
    $sql = "
        SELECT 
            u.id,
            u.name,
            u.email,
            u.phone,
            u.address,
            CONCAT(b.name, ' - ', f.name, ' - ', r.room_number) as room,
            a.start_date as check_in,
            a.end_date as check_out,
            a.status
        FROM 
            users u
        JOIN 
            allocations a ON u.id = a.user_id
        JOIN 
            rooms r ON a.room_id = r.id
        JOIN 
            floors f ON r.floor_id = f.id
        JOIN 
            buildings b ON f.building_id = b.id
        WHERE 
            u.id = ?
            AND u.role = 'guest'
        ORDER BY 
            a.start_date DESC
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $guestId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Guest not found']);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    $guest = $result->fetch_assoc();
    
    $response = ['success' => true, 'guest' => $guest];
    
    // Get detailed information if requested
    if ($detailed) {
        // Get payment history
        $sql = "
            SELECT 
                id,
                total_amount,
                paid_amount,
                status,
                created_at as payment_date
            FROM 
                payments
            WHERE 
                user_id = ?
            ORDER BY 
                created_at DESC
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $guestId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        
        $response['payments'] = $payments;
    }
    
    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>