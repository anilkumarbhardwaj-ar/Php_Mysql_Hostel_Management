<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Check if user is logged in and has appropriate role
if (!isLoggedIn() || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'staff')) {
    die('Unauthorized access');
}

// Get parameters
$reportType = sanitizeInput($_GET['reportType']);
$dateRange = sanitizeInput($_GET['dateRange']);
$startDate = isset($_GET['startDate']) ? sanitizeInput($_GET['startDate']) : null;
$endDate = isset($_GET['endDate']) ? sanitizeInput($_GET['endDate']) : null;

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
} catch (Exception $e) {
    die('Error generating report: ' . $e->getMessage());
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . ucfirst($reportType) . '_Report.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add report header
fputcsv($output, [ucfirst($reportType) . ' Report']);
fputcsv($output, ['Period: ' . date('d M Y', strtotime($startDate)) . ' to ' . date('d M Y', strtotime($endDate))]);
fputcsv($output, []);

// Add summary section
fputcsv($output, ['Summary']);
foreach ($report['summary'] as $key => $value) {
    fputcsv($output, [ucwords(str_replace('_', ' ', $key)), is_numeric($value) ? number_format($value) : $value]);
}
fputcsv($output, []);

// Add data table
fputcsv($output, ['Details']);

// Get table headers and data based on report type
$headers = [];
$data = [];

switch ($reportType) {
    case 'occupancy':
        $headers = ['Building', 'Floor', 'Room', 'Type', 'Capacity', 'Occupied', 'Status'];
        $data = array_map(function($row) {
            return [
                $row['building'],
                $row['floor'],
                $row['room_number'],
                $row['type'],
                $row['capacity'],
                $row['occupied'],
                $row['status']
            ];
        }, $report['rooms']);
        break;
    case 'student':
        $headers = ['ID', 'Name', 'Course', 'Room', 'Fee Status', 'Join Date'];
        $data = array_map(function($row) {
            return [
                $row['id'],
                $row['name'],
                $row['course'],
                $row['room'],
                $row['fee_status'],
                date('d M Y', strtotime($row['join_date']))
            ];
        }, $report['students']);
        break;
    case 'course':
        $headers = ['Course', 'Total Students', 'Hostel Residents', 'Fee Paid', 'Fee Partial', 'Fee Unpaid'];
        $data = array_map(function($row) {
            return [
                $row['course'],
                $row['total_students'],
                $row['hostel_residents'],
                $row['fee_paid'],
                $row['fee_partial'],
                $row['fee_unpaid']
            ];
        }, $report['courses']);
        break;
    case 'fee':
        $headers = ['ID', 'Name', 'Total Amount', 'Paid Amount', 'Due Amount', 'Status', 'Payment Count', 'Last Payment'];
        $data = array_map(function($row) {
            return [
                $row['id'],
                $row['name'],
                $row['total_amount'],
                $row['paid_amount'],
                $row['due_amount'],
                $row['status'],
                $row['payment_count'],
                $row['last_payment_date'] ? date('d M Y', strtotime($row['last_payment_date'])) : '-'
            ];
        }, $report['fees']);
        break;
    case 'staff':
        $headers = ['ID', 'Name', 'Role', 'Email', 'Phone', 'Total Allocations', 'Total Payments'];
        $data = array_map(function($row) {
            return [
                $row['id'],
                $row['name'],
                $row['role'],
                $row['email'],
                $row['phone'],
                $row['total_allocations'],
                $row['total_payments']
            ];
        }, $report['staff']);
        break;
}

// Write headers
fputcsv($output, $headers);

// Write data
foreach ($data as $row) {
    fputcsv($output, $row);
}

// Close the output stream
fclose($output);
?> 