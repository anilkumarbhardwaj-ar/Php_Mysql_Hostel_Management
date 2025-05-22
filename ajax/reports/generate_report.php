<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Check if user is logged in and has appropriate role
if (!isLoggedIn() || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'staff')) {
    die(json_encode(['error' => 'Unauthorized access']));
}

// Get parameters
$reportType = sanitizeInput($_POST['reportType']);
$dateRange = sanitizeInput($_POST['dateRange']);
$startDate = isset($_POST['startDate']) ? sanitizeInput($_POST['startDate']) : null;
$endDate = isset($_POST['endDate']) ? sanitizeInput($_POST['endDate']) : null;

// Calculate date range
$dateRangeData = calculateDateRange($dateRange, $startDate, $endDate);
$startDate = $dateRangeData['start'];
$endDate = $dateRangeData['end'];

// Get report data
$conn = getDbConnection();
$report = null;

try {
    switch ($reportType) {
        case 'occupancy':
            $report = generateOccupancyReport($conn, $startDate, $endDate);
            break;
        case 'student':
            $report = generateStudentReport($conn, $startDate, $endDate);
            break;
        case 'course':
            $report = generateCourseReport($conn, $startDate, $endDate);
            break;
        case 'fee':
            $report = generateFeeReport($conn, $startDate, $endDate);
            break;
        case 'staff':
            $report = generateStaffReport($conn, $startDate, $endDate);
            break;
        default:
            throw new Exception('Invalid report type');
    }
    
    echo json_encode([
        'success' => true,
        'report' => $report,
        'dateRange' => [
            'start' => $startDate,
            'end' => $endDate
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error generating report: ' . $e->getMessage()
    ]);
}

// Helper functions for report generation
function generateOccupancyReport($conn, $startDate, $endDate) {
    $query = "SELECT 
                b.name as building,
                f.name as floor,
                r.room_number,
                r.type,
                r.capacity,
                COUNT(DISTINCT a.user_id) as occupied,
                CASE 
                    WHEN COUNT(DISTINCT a.user_id) = 0 THEN 'vacant'
                    WHEN COUNT(DISTINCT a.user_id) >= r.capacity THEN 'full'
                    ELSE 'occupied'
                END as status
              FROM rooms r
              LEFT JOIN floors f ON r.floor_id = f.id
              LEFT JOIN buildings b ON f.building_id = b.id
              LEFT JOIN allocations a ON r.id = a.room_id 
                AND a.start_date <= ? AND a.end_date >= ?
              GROUP BY r.id, r.room_number, r.capacity, r.type, f.id, b.id, b.name, f.name";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $endDate, $startDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rooms = [];
    $totalRooms = 0;
    $totalOccupied = 0;
    $totalVacant = 0;
    $totalFull = 0;
    
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
        $totalRooms++;
        $totalOccupied += $row['occupied'];
        if ($row['status'] == 'vacant') $totalVacant++;
        if ($row['status'] == 'full') $totalFull++;
    }
    
    return [
        'summary' => [
            'total_rooms' => $totalRooms,
            'total_occupied' => $totalOccupied,
            'total_vacant' => $totalVacant,
            'total_full' => $totalFull,
            'occupancy_rate' => $totalRooms > 0 ? round(($totalOccupied / $totalRooms) * 100, 2) : 0
        ],
        'rooms' => $rooms
    ];
}

function generateStudentReport($conn, $startDate, $endDate) {
    $query = "SELECT 
                u.id,
                u.name,
                c.name as course,
                CONCAT(b.name, ' - ', f.name, ' - ', r.room_number) as room,
                CASE 
                    WHEN f.total_amount = f.paid_amount THEN 'Paid'
                    WHEN f.paid_amount > 0 THEN 'Partial'
                    ELSE 'Unpaid'
                END as fee_status,
                u.created_at as join_date
              FROM users u
              LEFT JOIN courses c ON u.course_id = c.id
              LEFT JOIN allocations a ON u.id = a.user_id 
                AND a.start_date <= ? AND a.end_date >= ?
              LEFT JOIN rooms r ON a.room_id = r.id
              LEFT JOIN floors f ON r.floor_id = f.id
              LEFT JOIN buildings b ON f.building_id = b.id
              LEFT JOIN fees f ON u.id = f.user_id
              WHERE u.role = 'student'
              GROUP BY u.id";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $endDate, $startDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    $totalStudents = 0;
    $totalPaid = 0;
    $totalPartial = 0;
    $totalUnpaid = 0;
    
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
        $totalStudents++;
        if ($row['fee_status'] == 'Paid') $totalPaid++;
        if ($row['fee_status'] == 'Partial') $totalPartial++;
        if ($row['fee_status'] == 'Unpaid') $totalUnpaid++;
    }
    
    return [
        'summary' => [
            'total_students' => $totalStudents,
            'total_paid' => $totalPaid,
            'total_partial' => $totalPartial,
            'total_unpaid' => $totalUnpaid
        ],
        'students' => $students
    ];
}

function generateCourseReport($conn, $startDate, $endDate) {
    $query = "SELECT 
                c.name as course,
                COUNT(DISTINCT u.id) as total_students,
                COUNT(DISTINCT CASE WHEN a.id IS NOT NULL THEN u.id END) as hostel_residents,
                COUNT(DISTINCT CASE WHEN f.total_amount = f.paid_amount THEN u.id END) as fee_paid,
                COUNT(DISTINCT CASE WHEN f.paid_amount > 0 AND f.total_amount > f.paid_amount THEN u.id END) as fee_partial,
                COUNT(DISTINCT CASE WHEN f.paid_amount = 0 OR f.paid_amount IS NULL THEN u.id END) as fee_unpaid
              FROM courses c
              LEFT JOIN users u ON c.id = u.course_id AND u.role = 'student'
              LEFT JOIN allocations a ON u.id = a.user_id 
                AND a.start_date <= ? AND a.end_date >= ?
              LEFT JOIN fees f ON u.id = f.user_id
              GROUP BY c.id";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $endDate, $startDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $courses = [];
    $totalStudents = 0;
    $totalResidents = 0;
    
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
        $totalStudents += $row['total_students'];
        $totalResidents += $row['hostel_residents'];
    }
    
    return [
        'summary' => [
            'total_courses' => count($courses),
            'total_students' => $totalStudents,
            'total_residents' => $totalResidents,
            'residency_rate' => $totalStudents > 0 ? round(($totalResidents / $totalStudents) * 100, 2) : 0
        ],
        'courses' => $courses
    ];
}

function generateFeeReport($conn, $startDate, $endDate) {
    $query = "SELECT 
                u.id,
                u.name,
                f.total_amount,
                f.paid_amount,
                f.total_amount - f.paid_amount as due_amount,
                CASE 
                    WHEN f.total_amount = f.paid_amount THEN 'Paid'
                    WHEN f.paid_amount > 0 THEN 'Partial'
                    ELSE 'Unpaid'
                END as status,
                COUNT(DISTINCT p.id) as payment_count,
                MAX(p.payment_date) as last_payment_date
              FROM users u
              LEFT JOIN fees f ON u.id = f.user_id
              LEFT JOIN payments p ON f.id = p.fee_id
                AND p.payment_date BETWEEN ? AND ?
              WHERE u.role = 'student'
              GROUP BY u.id";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $fees = [];
    $totalAmount = 0;
    $totalPaid = 0;
    $totalDue = 0;
    $totalPaidCount = 0;
    $totalPartialCount = 0;
    $totalUnpaidCount = 0;
    
    while ($row = $result->fetch_assoc()) {
        $fees[] = $row;
        $totalAmount += $row['total_amount'];
        $totalPaid += $row['paid_amount'];
        $totalDue += $row['due_amount'];
        if ($row['status'] == 'Paid') $totalPaidCount++;
        if ($row['status'] == 'Partial') $totalPartialCount++;
        if ($row['status'] == 'Unpaid') $totalUnpaidCount++;
    }
    
    return [
        'summary' => [
            'total_amount' => $totalAmount,
            'total_paid' => $totalPaid,
            'total_due' => $totalDue,
            'total_paid_count' => $totalPaidCount,
            'total_partial_count' => $totalPartialCount,
            'total_unpaid_count' => $totalUnpaidCount,
            'collection_rate' => $totalAmount > 0 ? round(($totalPaid / $totalAmount) * 100, 2) : 0
        ],
        'fees' => $fees
    ];
}

function generateStaffReport($conn, $startDate, $endDate) {
    $query = "SELECT 
                u.id,
                u.name,
                u.role,
                u.email,
                u.phone,
                COUNT(DISTINCT a.id) as total_allocations,
                COUNT(DISTINCT p.id) as total_payments
              FROM users u
              LEFT JOIN allocations a ON u.id = a.created_by 
                AND a.created_at BETWEEN ? AND ?
              LEFT JOIN payments p ON u.id = p.created_by 
                AND p.payment_date BETWEEN ? AND ?
              WHERE u.role IN ('admin', 'staff')
              GROUP BY u.id";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $startDate, $endDate, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $staff = [];
    $totalStaff = 0;
    $totalAllocations = 0;
    $totalPayments = 0;
    
    while ($row = $result->fetch_assoc()) {
        $staff[] = $row;
        $totalStaff++;
        $totalAllocations += $row['total_allocations'];
        $totalPayments += $row['total_payments'];
    }
    
    return [
        'summary' => [
            'total_staff' => $totalStaff,
            'total_allocations' => $totalAllocations,
            'total_payments' => $totalPayments,
            'avg_allocations' => $totalStaff > 0 ? round($totalAllocations / $totalStaff, 2) : 0,
            'avg_payments' => $totalStaff > 0 ? round($totalPayments / $totalStaff, 2) : 0
        ],
        'staff' => $staff
    ];
}

// Helper function to calculate date range
function calculateDateRange($range, $customStart = null, $customEnd = null) {
    $today = new DateTime();
    $start = new DateTime();
    $end = new DateTime();
    
    switch ($range) {
        case 'today':
            // Start and end are already set to today
            break;
        case 'week':
            $start->modify('monday this week');
            $end->modify('sunday this week');
            break;
        case 'month':
            $start->modify('first day of this month');
            $end->modify('last day of this month');
            break;
        case 'year':
            $start->modify('first day of january this year');
            $end->modify('last day of december this year');
            break;
        case 'custom':
            if (!$customStart || !$customEnd) {
                throw new Exception('Custom date range requires both start and end dates');
            }
            $start = new DateTime($customStart);
            $end = new DateTime($customEnd);
            break;
        default:
            throw new Exception('Invalid date range');
    }
    
    return [
        'start' => $start->format('Y-m-d'),
        'end' => $end->format('Y-m-d')
    ];
}
?> 