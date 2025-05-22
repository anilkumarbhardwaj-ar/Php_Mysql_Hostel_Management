<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get parameters
$reportType = $_GET['reportType'] ?? '';
$dateRange = $_GET['dateRange'] ?? 'month';
$startDate = $_GET['startDate'] ?? '';
$endDate = $_GET['endDate'] ?? '';

// Calculate date range
$dateRangeObj = calculateDateRange($dateRange, $startDate, $endDate);

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Hostel Management System');
$pdf->SetTitle(ucfirst($reportType) . ' Report');

// Set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Hostel Management System', 'Report Generation');

// Set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Get report data
$report = generateReport($reportType, $dateRangeObj);

// Add report title
$pdf->Cell(0, 10, ucfirst($reportType) . ' Report', 0, 1, 'C');
$pdf->Cell(0, 10, 'Period: ' . $dateRangeObj['start'] . ' to ' . $dateRangeObj['end'], 0, 1, 'C');
$pdf->Ln(10);

// Add summary section
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Summary', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);

foreach ($report['summary'] as $key => $value) {
    $pdf->Cell(60, 10, formatLabel($key), 0, 0);
    $pdf->Cell(0, 10, formatValue($value), 0, 1);
}
$pdf->Ln(10);

// Add details section
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Details', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

// Get the first detail array
$details = reset($report['details']);
if (!empty($details)) {
    // Add table headers
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 10);
    $colWidths = array_fill(0, count($details[0]), 30);
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $i = 0;
    foreach ($details[0] as $key => $value) {
        $pdf->MultiCell($colWidths[$i], 10, formatLabel($key), 1, 'C', true, 0, $x + array_sum(array_slice($colWidths, 0, $i)), $y);
        $i++;
    }
    $pdf->Ln();

    // Add table data
    $pdf->SetFont('helvetica', '', 10);
    foreach ($details as $row) {
        $i = 0;
        foreach ($row as $value) {
            $pdf->MultiCell($colWidths[$i], 10, formatValue($value), 1, 'L', false, 0, $x + array_sum(array_slice($colWidths, 0, $i)), $pdf->GetY());
            $i++;
        }
        $pdf->Ln();
    }
}

// Output PDF
$pdf->Output(ucfirst($reportType) . '_Report.pdf', 'D');

// Helper function to format label
function formatLabel($label) {
    return ucwords(str_replace('_', ' ', $label));
}

// Helper function to format value
function formatValue($value) {
    if (is_numeric($value)) {
        return number_format($value, 2);
    }
    return $value;
}

// Helper function to calculate date range
function calculateDateRange($range, $startDate, $endDate) {
    $today = new DateTime();
    $start = new DateTime();
    $end = new DateTime();

    switch ($range) {
        case 'today':
            $start->setTime(0, 0, 0);
            $end->setTime(23, 59, 59);
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
            if ($startDate && $endDate) {
                $start = new DateTime($startDate);
                $end = new DateTime($endDate);
            }
            break;
    }

    return [
        'start' => $start->format('Y-m-d'),
        'end' => $end->format('Y-m-d')
    ];
}

// Helper function to generate report data
function generateReport($type, $dateRange) {
    global $conn;
    
    $report = [
        'summary' => [],
        'details' => []
    ];

    switch ($type) {
        case 'occupancy':
            // Get room occupancy summary
            $sql = "SELECT 
                    COUNT(DISTINCT r.id) as total_rooms,
                    COUNT(DISTINCT CASE WHEN COUNT(DISTINCT a.user_id) > 0 THEN r.id END) as occupied_rooms,
                    COUNT(DISTINCT CASE WHEN COUNT(DISTINCT a.user_id) = 0 THEN r.id END) as vacant_rooms,
                    ROUND(COUNT(DISTINCT CASE WHEN COUNT(DISTINCT a.user_id) > 0 THEN r.id END) * 100.0 / COUNT(DISTINCT r.id), 2) as occupancy_rate
                FROM rooms r
                LEFT JOIN allocations a ON r.id = a.room_id
                WHERE a.start_date <= ? AND a.end_date >= ?
                GROUP BY r.id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $dateRange['end'], $dateRange['start']);
            $stmt->execute();
            $result = $stmt->get_result();
            $report['summary'] = $result->fetch_assoc();

            // Get room details
            $sql = "SELECT 
                    b.name as building,
                    fl.name as floor,
                    r.room_number,
                    r.type,
                    r.capacity,
                    COUNT(DISTINCT a.user_id) as occupied,
                    CASE 
                        WHEN COUNT(DISTINCT a.user_id) = 0 THEN 'Vacant'
                        WHEN COUNT(DISTINCT a.user_id) = r.capacity THEN 'Full'
                        ELSE 'Occupied'
                    END as status
                FROM rooms r
                LEFT JOIN allocations a ON r.id = a.room_id
                LEFT JOIN floors fl ON r.floor_id = fl.id
                LEFT JOIN buildings b ON fl.building_id = b.id
                WHERE a.start_date <= ? AND a.end_date >= ?
                GROUP BY r.id, b.name, fl.name, r.room_number, r.type, r.capacity";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $dateRange['end'], $dateRange['start']);
            $stmt->execute();
            $result = $stmt->get_result();
            $report['details']['rooms'] = $result->fetch_all(MYSQLI_ASSOC);
            break;

        case 'student':
            // Get student summary
            $sql = "SELECT 
                    COUNT(DISTINCT s.id) as total_students,
                    COUNT(DISTINCT CASE WHEN a.id IS NOT NULL THEN s.id END) as hostel_residents,
                    COUNT(DISTINCT CASE WHEN fee.status = 'paid' THEN s.id END) as fee_paid,
                    COUNT(DISTINCT CASE WHEN fee.status = 'pending' THEN s.id END) as fee_pending
                FROM students s
                LEFT JOIN allocations a ON s.id = a.user_id
                LEFT JOIN fees fee ON s.id = fee.student_id
                WHERE a.start_date <= ? AND a.end_date >= ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $dateRange['end'], $dateRange['start']);
            $stmt->execute();
            $result = $stmt->get_result();
            $report['summary'] = $result->fetch_assoc();

            // Get student details
            $sql = "SELECT 
                    s.id,
                    s.name,
                    c.name as course,
                    CONCAT(b.name, ' - ', fl.name, ' - ', r.room_number) as room,
                    fee.status as fee_status,
                    a.start_date as join_date
                FROM students s
                LEFT JOIN allocations a ON s.id = a.user_id
                LEFT JOIN rooms r ON a.room_id = r.id
                LEFT JOIN floors fl ON r.floor_id = fl.id
                LEFT JOIN buildings b ON fl.building_id = b.id
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN fees fee ON s.id = fee.student_id
                WHERE a.start_date <= ? AND a.end_date >= ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $dateRange['end'], $dateRange['start']);
            $stmt->execute();
            $result = $stmt->get_result();
            $report['details']['students'] = $result->fetch_all(MYSQLI_ASSOC);
            break;

        case 'course':
            // Get course summary
            $sql = "SELECT 
                    COUNT(DISTINCT c.id) as total_courses,
                    COUNT(DISTINCT s.id) as total_students,
                    COUNT(DISTINCT CASE WHEN a.id IS NOT NULL THEN s.id END) as hostel_residents,
                    ROUND(AVG(CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END) * 100, 2) as hostel_percentage
                FROM courses c
                LEFT JOIN students s ON c.id = s.course_id
                LEFT JOIN allocations a ON s.id = a.user_id
                WHERE a.start_date <= ? AND a.end_date >= ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $dateRange['end'], $dateRange['start']);
            $stmt->execute();
            $result = $stmt->get_result();
            $report['summary'] = $result->fetch_assoc();

            // Get course details
            $sql = "SELECT 
                    c.id,
                    c.name as course_name,
                    COUNT(DISTINCT s.id) as total_students,
                    COUNT(DISTINCT CASE WHEN a.id IS NOT NULL THEN s.id END) as hostel_residents,
                    ROUND(AVG(CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END) * 100, 2) as hostel_percentage
                FROM courses c
                LEFT JOIN students s ON c.id = s.course_id
                LEFT JOIN allocations a ON s.id = a.user_id
                WHERE a.start_date <= ? AND a.end_date >= ?
                GROUP BY c.id, c.name";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $dateRange['end'], $dateRange['start']);
            $stmt->execute();
            $result = $stmt->get_result();
            $report['details']['courses'] = $result->fetch_all(MYSQLI_ASSOC);
            break;

        case 'fee':
            // Get fee summary
            $sql = "SELECT 
                    COUNT(DISTINCT fee.id) as total_payments,
                    SUM(fee.amount) as total_amount,
                    COUNT(DISTINCT CASE WHEN fee.status = 'paid' THEN fee.id END) as paid_payments,
                    SUM(CASE WHEN fee.status = 'paid' THEN fee.amount ELSE 0 END) as paid_amount,
                    COUNT(DISTINCT CASE WHEN fee.status = 'pending' THEN fee.id END) as pending_payments,
                    SUM(CASE WHEN fee.status = 'pending' THEN fee.amount ELSE 0 END) as pending_amount
                FROM fees fee
                WHERE fee.payment_date BETWEEN ? AND ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $dateRange['start'], $dateRange['end']);
            $stmt->execute();
            $result = $stmt->get_result();
            $report['summary'] = $result->fetch_assoc();

            // Get fee details
            $sql = "SELECT 
                    fee.id,
                    s.name as student_name,
                    fee.amount,
                    fee.payment_date,
                    fee.status,
                    fee.payment_method
                FROM fees fee
                JOIN students s ON fee.student_id = s.id
                WHERE fee.payment_date BETWEEN ? AND ?
                ORDER BY fee.payment_date DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $dateRange['start'], $dateRange['end']);
            $stmt->execute();
            $result = $stmt->get_result();
            $report['details']['fees'] = $result->fetch_all(MYSQLI_ASSOC);
            break;

        case 'staff':
            // Get staff summary
            $sql = "SELECT 
                    COUNT(DISTINCT st.id) as total_staff,
                    COUNT(DISTINCT CASE WHEN st.role = 'admin' THEN st.id END) as admin_count,
                    COUNT(DISTINCT CASE WHEN st.role = 'manager' THEN st.id END) as manager_count,
                    COUNT(DISTINCT CASE WHEN st.role = 'staff' THEN st.id END) as staff_count
                FROM staff st";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            $report['summary'] = $result->fetch_assoc();

            // Get staff details
            $sql = "SELECT 
                    st.id,
                    st.name,
                    st.email,
                    st.role,
                    st.phone,
                    st.join_date
                FROM staff st
                ORDER BY st.role, st.name";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            $report['details']['staff'] = $result->fetch_all(MYSQLI_ASSOC);
            break;
    }

    return $report;
}
?> 