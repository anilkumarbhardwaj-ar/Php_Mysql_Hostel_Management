<div class="content">
    <h2>My Course Details</h2>
    
    <?php
    // Check if user is student
    if ($_SESSION['user_role'] != 'student') {
        echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
        exit;
    }
    
    $conn = getDbConnection();
    $userId = $_SESSION['user_id'];
    
    // Get course details
    $sql = "
        SELECT 
            c.id,
            c.name,
            c.duration,
            c.description,
            sc.join_date,
            DATE_ADD(sc.join_date, INTERVAL c.duration MONTH) as end_date,
            sc.status
        FROM 
            student_courses sc
        JOIN 
            courses c ON sc.course_id = c.id
        WHERE 
            sc.student_id = ?
        ORDER BY 
            sc.join_date DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $course = $result->fetch_assoc();
        ?>
        
        <div class="course-details-container">
            <div class="course-info">
                <h3><?php echo $course['name']; ?></h3>
                
                <div class="course-meta">
                    <div class="meta-item">
                        <span class="label">Duration:</span>
                        <span class="value"><?php echo $course['duration']; ?> months</span>
                    </div>
                    
                    <div class="meta-item">
                        <span class="label">Start Date:</span>
                        <span class="value"><?php echo formatDate($course['join_date']); ?></span>
                    </div>
                    
                    <div class="meta-item">
                        <span class="label">End Date:</span>
                        <span class="value"><?php echo formatDate($course['end_date']); ?></span>
                    </div>
                    
                    <div class="meta-item">
                        <span class="label">Status:</span>
                        <span class="value status-<?php echo strtolower($course['status']); ?>"><?php echo ucfirst($course['status']); ?></span>
                    </div>
                </div>
                
                <div class="course-description">
                    <h4>Course Description</h4>
                    <p><?php echo $course['description']; ?></p>
                </div>
            </div>
            
            <?php
            // Get course schedule
            $scheduleSql = "
                SELECT 
                    cs.day,
                    cs.start_time,
                    cs.end_time,
                    cs.subject,
                    u.name as instructor_name
                FROM 
                    course_schedule cs
                LEFT JOIN 
                    users u ON cs.instructor_id = u.id
                WHERE 
                    cs.course_id = ?
                ORDER BY 
                    FIELD(cs.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                    cs.start_time
            ";
            
            $scheduleStmt = $conn->prepare($scheduleSql);
            $scheduleStmt->bind_param("i", $course['id']);
            $scheduleStmt->execute();
            $scheduleResult = $scheduleStmt->get_result();
            
            if ($scheduleResult->num_rows > 0) {
                ?>
                <div class="course-schedule">
                    <h3>Course Schedule</h3>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Subject</th>
                                <th>Instructor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while ($schedule = $scheduleResult->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $schedule['day'] . "</td>";
                                echo "<td>" . date('h:i A', strtotime($schedule['start_time'])) . " - " . date('h:i A', strtotime($schedule['end_time'])) . "</td>";
                                echo "<td>" . $schedule['subject'] . "</td>";
                                echo "<td>" . ($schedule['instructor_name'] ?? 'Not assigned') . "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php
            } else {
                echo '<div class="alert alert-info">No course schedule available.</div>';
            }
            
            $scheduleStmt->close();
            
            // Get classmates
            $classmatesSql = "
                SELECT 
                    u.id,
                    u.name
                FROM 
                    student_courses sc
                JOIN 
                    users u ON sc.student_id = u.id
                WHERE 
                    sc.course_id = ? AND sc.student_id != ?
                ORDER BY 
                    u.name
            ";
            
            $classmatesStmt = $conn->prepare($classmatesSql);
            $classmatesStmt->bind_param("ii", $course['id'], $userId);
            $classmatesStmt->execute();
            $classmatesResult = $classmatesStmt->get_result();
            
            if ($classmatesResult->num_rows > 0) {
                ?>
                <div class="classmates">
                    <h3>Classmates</h3>
                    
                    <div class="classmates-list">
                        <?php
                        while ($classmate = $classmatesResult->fetch_assoc()) {
                            echo '<div class="classmate-card">';
                            echo '<div class="classmate-name">' . $classmate['name'] . '</div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                <?php
            }
            
            $classmatesStmt->close();
            ?>
        </div>
        
        <?php
    } else {
        echo '<div class="alert alert-info">You are not enrolled in any course. Please contact the administrator.</div>';
    }
    
    $stmt->close();
    closeDbConnection($conn);
    ?>
</div>
