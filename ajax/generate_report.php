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

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

// Get and sanitize input data
$reportType = sanitizeInput($data['reportType']);
$dateRange = sanitizeInput($data['dateRange']);
$startDate = sanitizeInput($data['startDate']);
$endDate = sanitizeInput($data['endDate']);

// Set date range based on selection
$today = date('Y-m-d');
$firstDayOfMonth = date('Y-m-01');
$lastDayOfMonth = date('Y-m-t');
$firstDayOfWeek = date('Y-m-d', strtotime('monday this week'));
$lastDayOfWeek = date('Y-m-d', strtotime('sunday this week'));
$firstDayOfYear = date('Y-01-01');
$lastDayOfYear = date('Y-12-31');

switch ($dateRange) {
    case 'today':
        $startDate = $today;
        $endDate = $today;
        break;
    case 'week':
        $startDate = $firstDayOfWeek;
        $endDate = $lastDayOfWeek;
        break;
    case 'month':
        $startDate = $firstDayOfMonth;
        $endDate = $lastDayOfMonth;
        break;
    case 'year':
        $startDate = $firstDayOfYear;
        $endDate = $lastDayOfYear;
        break;
    case 'custom':
        // Use provided dates
        break;
    default:
        $startDate = $firstDayOfMonth;
        $endDate = $lastDayOfMonth;
}

// Generate report based on type
$conn = getDbConnection();

switch ($reportType) {
    case 'occupancy':
        generateOccupancyReport($conn, $startDate, $endDate);
        break;
    case 'student':
        generateStudentReport($conn, $startDate, $endDate);
        break;
    case 'course':
        generateCourseReport($conn, $startDate, $endDate);
        break;
    case 'fee':
        generateFeeReport($conn, $startDate, $endDate);
        break;
    case 'staff':
        generateStaffReport($conn, $startDate, $endDate);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid report type']);
}

closeDbConnection($conn);

// Function to generate occupancy report
function generateOccupancyReport($conn, $startDate, $endDate) {
    // Get room occupancy data
    $sql = "
        SELECT 
            b.name as building,
            f.name as floor,
            r.room_number,
            r.type,
            r.capacity,
            COUNT(a.id) as occupied,
            CASE 
                WHEN COUNT(a.id) = 0 THEN 'Vacant'
                WHEN COUNT(a.id) = r.capacity THEN 'Full'
                ELSE 'Occupied'
            END as status
        FROM 
            rooms r
        JOIN 
            floors f ON r.floor_id = f.id
        JOIN 
            buildings b ON f.building_id = b.id
        LEFT JOIN 
            allocations a ON r.id = a
