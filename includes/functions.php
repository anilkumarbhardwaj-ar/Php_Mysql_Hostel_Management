<?php
// Get all allowed pages based on user role
function getAllowedPages($role) {
    $commonPages = ['dashboard', 'profile', 'logout', 'room_details'];
    
    switch ($role) {
        case 'admin':
            return array_merge($commonPages, [
                'users', 'courses', 'students', 'staff', 'guests',
                'rooms', 'buildings', 'fees', 'reports', 'settings',
                'room_management', 'user_management', 'payment_management'
            ]);
        case 'staff':
            return array_merge($commonPages, [
                'students', 'courses', 'rooms', 'reports',
                'room_management', 'payment_management'
            ]);
        case 'student':
            return array_merge($commonPages, [
                'room_details', 'fee_details', 'course_details',
                'book_room', 'view_bookings', 'make_payment'
            ]);
        case 'guest':
            return array_merge($commonPages, [
                'room_details', 'book_room', 'view_bookings',
                'make_payment'
            ]);
        case 'others':
            return array_merge($commonPages, [
                'room_details', 'book_room', 'view_bookings',
                'make_payment'
            ]);
        default:
            return $commonPages;
    }
}

// Sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Generate a random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// Format date
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

// Calculate fee based on user role and duration
function calculateFee($userId, $startDate, $endDate) {
    $conn = getDbConnection();
    
    // Get user role
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $role = $user['role'];
        
        // Calculate number of days
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = $start->diff($end);
        $days = $interval->days;
        
        // Calculate total fee based on role
        $totalFee = 0;
        switch ($role) {
            case 'student':
                // ₹2,500 per month for students
                $months = ceil($days / 30);
                $totalFee = $months * 2500;
                break;
                
            case 'intern':
                // ₹3,000 per month for interns
                $months = ceil($days / 30);
                $totalFee = $months * 3000;
                break;
                
            case 'staff':
            case 'trainer':
                // ₹5,000 per month for staff/trainers
                $months = ceil($days / 30);
                $totalFee = $months * 5000;
                break;
                
            case 'guest':
                // ₹1,250 per day for guests (including GST)
                $totalFee = $days * 1250;
                break;
                
            default:
                $totalFee = 0;
        }
        
        $stmt->close();
        closeDbConnection($conn);
        
        return $totalFee;
    }
    
    $stmt->close();
    closeDbConnection($conn);
    
    return 0;
}

// Check room availability
function isRoomAvailable($roomId, $startDate, $endDate) {
    $conn = getDbConnection();
    
    // First check if room exists and get its capacity
    $stmt = $conn->prepare("SELECT capacity FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        closeDbConnection($conn);
        return false;
    }
    
    $room = $result->fetch_assoc();
    $capacity = $room['capacity'];
    
    // Check current occupancy for the date range
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT user_id) as current_occupants
        FROM allocations 
        WHERE room_id = ? 
        AND end_date >= CURDATE()
        AND (
            (start_date <= ? AND end_date >= ?) OR
            (start_date <= ? AND end_date >= ?) OR
            (start_date >= ? AND end_date <= ?)
        )
    ");
    
    $stmt->bind_param("issssss", $roomId, $endDate, $startDate, $startDate, $startDate, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    closeDbConnection($conn);
    
    // Room is available if current occupants are less than capacity
    return $row['current_occupants'] < $capacity;
}

// Get room occupancy status
function getRoomOccupancy() {
    $conn = getDbConnection();
    
    $sql = "
        SELECT 
            b.name as building,
            f.name as floor,
            r.room_number,
            r.id as room_id,
            r.capacity,
            COUNT(a.id) as occupied
        FROM 
            rooms r
        LEFT JOIN 
            floors f ON r.floor_id = f.id
        LEFT JOIN 
            buildings b ON f.building_id = b.id
        LEFT JOIN 
            allocations a ON r.id = a.room_id AND a.end_date >= CURDATE()
        GROUP BY 
            r.id
        ORDER BY 
            b.name, f.name, r.room_number
    ";
    
    $result = $conn->query($sql);
    $occupancy = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $occupancy[] = $row;
        }
    }
    
    closeDbConnection($conn);
    
    return $occupancy;
}
?>
