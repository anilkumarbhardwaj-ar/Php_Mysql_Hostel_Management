<?php
// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465); // Changed to 465 for SSL
define('SMTP_USERNAME', 'faryadk311@gmail.com'); // Your Gmail address
define('SMTP_PASSWORD', 'mybb kkpv niio jpxd'); // Your Gmail App Password
define('SMTP_FROM_EMAIL', 'faryadk311@gmail.com'); // Your Gmail address
define('SMTP_FROM_NAME', 'Hostel Management System');

// Email Templates
function getWelcomeEmailTemplate($name, $email, $password, $role) {
    $subject = "Welcome to Hostel Management System";
    
    // Get room and building details if available
    $roomDetails = '';
    $courseDetails = '';
    
    $conn = getDbConnection();
    
    // Get user's room allocation details
    $stmt = $conn->prepare("
        SELECT 
            r.room_number,
            r.type as room_type,
            f.name as floor_name,
            b.name as building_name,
            b.location,
            a.start_date,
            a.end_date
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
            u.email = ?
        ORDER BY 
            a.start_date DESC
        LIMIT 1
    ");
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $room = $result->fetch_assoc();
        $roomDetails = "
            <h3>Room Allocation Details:</h3>
            <ul>
                <li><strong>Building:</strong> " . htmlspecialchars($room['building_name']) . "</li>
                <li><strong>Floor:</strong> " . htmlspecialchars($room['floor_name']) . "</li>
                <li><strong>Room Number:</strong> " . htmlspecialchars($room['room_number']) . "</li>
                <li><strong>Room Type:</strong> " . htmlspecialchars($room['room_type']) . "</li>
                <li><strong>Location:</strong> " . htmlspecialchars($room['location']) . "</li>
                <li><strong>Check-in Date:</strong> " . date('d M Y', strtotime($room['start_date'])) . "</li>
                <li><strong>Check-out Date:</strong> " . date('d M Y', strtotime($room['end_date'])) . "</li>
            </ul>
        ";
    }
    
    // Get course details for students
    if ($role === 'student') {
        $stmt = $conn->prepare("
            SELECT 
                c.name as course_name,
                c.duration,
                sc.join_date
            FROM 
                users u
            JOIN 
                student_courses sc ON u.id = sc.student_id
            JOIN 
                courses c ON sc.course_id = c.id
            WHERE 
                u.email = ?
            ORDER BY 
                sc.join_date DESC
            LIMIT 1
        ");
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $course = $result->fetch_assoc();
            $courseDetails = "
                <h3>Course Details:</h3>
                <ul>
                    <li><strong>Course Name:</strong> " . htmlspecialchars($course['course_name']) . "</li>
                    <li><strong>Duration:</strong> " . htmlspecialchars($course['duration']) . "</li>
                    <li><strong>Start Date:</strong> " . date('d M Y', strtotime($course['join_date'])) . "</li>
                </ul>
            ";
        }
    }
    
    $stmt->close();
    closeDbConnection($conn);
    
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            h3 { color: #333; margin-top: 20px; }
            ul { list-style-type: none; padding-left: 0; }
            li { margin-bottom: 10px; }
            .strong { font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Welcome to Humming Bird Tower</h2>
            </div>
            <div class='content'>
                <p>Dear " . htmlspecialchars($name) . ",</p>
                <p>Welcome to the IIT Mandi iHub & HCi Foundation. Your account has been created successfully.</p>
                
                <h3>Your Login Credentials:</h3>
                <ul>
                    <li><strong>Email:</strong> " . htmlspecialchars($email) . "</li>
                    <li><strong>Password:</strong> " . htmlspecialchars($password) . "</li>
                    <li><strong>Role:</strong> " . ucfirst(htmlspecialchars($role)) . "</li>
                </ul>
                
                <p>Please login to the system using these credentials and change your password for security reasons.</p>
                <p>If you have any questions or need assistance, please contact the administrator.</p>
            </div>
            <div class='footer'>
                <p>This is an automated message, please do not reply.</p>
            </div>
        </div>
    </body>
    </html>";
    
    return ['subject' => $subject, 'body' => $body];
}

/*
TESTING INSTRUCTIONS:

1. Update the SMTP settings above:
   - Replace 'your-gmail@gmail.com' with your Gmail address
   - Replace 'your-16-digit-app-password' with your Gmail App Password

2. Run the test script:
   - Open test_email.php
   - Replace 'your-test-email@gmail.com' with your email address
   - Run the script in your browser or command line

3. Check your email:
   - You should receive a welcome email
   - If you don't receive it, check your spam folder
   - If there's an error, check the error message

4. Common issues:
   - Make sure 2-Step Verification is enabled
   - Verify the App Password is correct
   - Check if your Gmail account has any security restrictions
   - Ensure the PHP zip extension is enabled
*/
?>