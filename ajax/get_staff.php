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
$staffId = sanitizeInput($_GET['id']);
$detailed = isset($_GET['detailed']) ? true : false;

// Validate input data
if (empty($staffId)) {
    echo json_encode(['success' => false, 'message' => 'Staff ID is required']);
    exit;
}

$conn = getDbConnection();

try {
    // Get basic staff details
    $sql = "
        SELECT 
            u.id,
            u.name,
            u.email,
            u.phone,
            sd.role as staff_role,
            sd.department,
            sd.joining_date,
            CONCAT(b.name, ' - ', f.name, ' - ', r.room_number) as room
        FROM 
            users u
        LEFT JOIN 
            staff_details sd ON u.id = sd.user_id
        LEFT JOIN 
            allocations a ON u.id = a.user_id AND (a.end_date >= CURDATE() OR a.end_date IS NULL)
        LEFT JOIN 
            rooms r ON a.room_id = r.id
        LEFT JOIN 
            floors f ON r.floor_id = f.id
        LEFT JOIN 
            buildings b ON f.building_id = b.id
        WHERE 
            u.id = ?
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $staffId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Staff not found']);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    $staff = $result->fetch_assoc();
    
    $response = ['success' => true, 'staff' => $staff];
    
    // Get detailed information if requested
    if ($detailed) {
        // Get attendance history
        $sql = "
            SELECT 
                check_in,
                check_out
            FROM 
                staff_attendance
            WHERE 
                staff_id = ?
            ORDER BY 
                check_in DESC
            LIMIT 10
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $staffId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $attendance = [];
        while ($row = $result->fetch_assoc()) {
            $attendance[] = $row;
        }
        
        $response['attendance'] = $attendance;
        
        // Get allocation history
        $sql = "
            SELECT 
                a.start_date,
                a.end_date,
                CONCAT(b.name, ' - ', f.name, ' - ', r.room_number) as room
            FROM 
                allocations a
            JOIN 
                rooms r ON a.room_id = r.id
            JOIN 
                floors f ON r.floor_id = f.id
            JOIN 
                buildings b ON f.building_id = b.id
            WHERE 
                a.user_id = ?
            ORDER BY 
                a.start_date DESC
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $staffId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $allocations = [];
        while ($row = $result->fetch_assoc()) {
            $allocations[] = $row;
        }
        
        $response['allocations'] = $allocations;
    }
    
    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>