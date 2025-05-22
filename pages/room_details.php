<div class="content">
    <h2>My Room Details</h2>
    
    <?php
    // Check if user is student or guest
    if ($_SESSION['user_role'] != 'student' && $_SESSION['user_role'] != 'guest') {
        echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
        exit;
    }
    
    $conn = getDbConnection();
    $userId = $_SESSION['user_id'];
    
    // Get room allocation details
    $sql = "
        SELECT 
            a.id as allocation_id,
            a.start_date,
            a.end_date,
            a.status,
            r.room_number,
            r.type,
            f.name as floor_name,
            b.name as building_name,
            b.location
        FROM 
            allocations a
        JOIN 
            rooms r ON a.room_id = r.id
        JOIN 
            floors f ON r.floor_id = f.id
        JOIN 
            buildings b ON f.building_id = b.id
        WHERE 
            a.user_id = ? AND a.end_date >= CURDATE()
        ORDER BY 
            a.start_date DESC
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $room = $result->fetch_assoc();
        ?>
        
        <div class="room-details-container">
            <div class="room-info">
                <h3>Room Information</h3>
                
                <div class="info-card">
                    <div class="info-item">
                        <span class="label">Room Number:</span>
                        <span class="value"><?php echo $room['room_number']; ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="label">Room Type:</span>
                        <span class="value"><?php echo $room['type']; ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="label">Floor:</span>
                        <span class="value"><?php echo $room['floor_name']; ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="label">Building:</span>
                        <span class="value"><?php echo $room['building_name']; ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="label">Location:</span>
                        <span class="value"><?php echo $room['location']; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="stay-info">
                <h3>Stay Information</h3>
                
                <div class="info-card">
                    <div class="info-item">
                        <span class="label">Check-in Date:</span>
                        <span class="value"><?php echo formatDate($room['start_date']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="label">Check-out Date:</span>
                        <span class="value"><?php echo formatDate($room['end_date']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="label">Status:</span>
                        <span class="value status-<?php echo strtolower($room['status']); ?>"><?php echo ucfirst($room['status']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="label">Duration:</span>
                        <?php
                        $startDate = new DateTime($room['start_date']);
                        $endDate = new DateTime($room['end_date']);
                        $interval = $startDate->diff($endDate);
                        $days = $interval->days;
                        ?>
                        <span class="value"><?php echo $days; ?> days</span>
                    </div>
                </div>
            </div>
            
            <?php
            // Get roommates
            $roommatesSql = "
                SELECT 
                    u.id,
                    u.name,
                    u.role
                FROM 
                    allocations a1
                JOIN 
                    allocations a2 ON a1.room_id = a2.room_id
                JOIN 
                    users u ON a2.user_id = u.id
                WHERE 
                    a1.id = ? AND a2.user_id != ? AND a2.end_date >= CURDATE()
            ";
            
            $roommatesStmt = $conn->prepare($roommatesSql);
            $roommatesStmt->bind_param("ii", $room['allocation_id'], $userId);
            $roommatesStmt->execute();
            $roommatesResult = $roommatesStmt->get_result();
            
            if ($roommatesResult->num_rows > 0) {
                ?>
                <div class="roommates-info">
                    <h3>Roommates</h3>
                    
                    <div class="roommates-list">
                        <?php
                        while ($roommate = $roommatesResult->fetch_assoc()) {
                            echo '<div class="roommate-card">';
                            echo '<div class="roommate-name">' . $roommate['name'] . '</div>';
                            echo '<div class="roommate-role">' . ucfirst($roommate['role']) . '</div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                <?php
            }
            
            $roommatesStmt->close();
            ?>
            
            <div class="room-actions">
                <button class="btn btn-primary" id="reportIssueBtn">Report Issue</button>
                <button class="btn btn-secondary" id="requestExtensionBtn">Request Extension</button>
            </div>
        </div>
        
        <!-- Report Issue Modal -->
        <div id="reportIssueModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3>Report Room Issue</h3>
                
                <form id="reportIssueForm" method="POST" action="ajax/report_issue.php">
                    <div class="form-group">
                        <label for="issueType">Issue Type</label>
                        <select id="issueType" name="issueType" required>
                            <option value="">Select Issue Type</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="plumbing">Plumbing</option>
                            <option value="electrical">Electrical</option>
                            <option value="furniture">Furniture</option>
                            <option value="cleanliness">Cleanliness</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="issueDescription">Description</label>
                        <textarea id="issueDescription" name="issueDescription" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select id="priority" name="priority" required>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Submit Issue</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Request Extension Modal -->
        <div id="requestExtensionModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3>Request Stay Extension</h3>
                
                <form id="requestExtensionForm" method="POST" action="ajax/request_extension.php">
                    <div class="form-group">
                        <label for="currentEndDate">Current End Date</label>
                        <input type="text" id="currentEndDate" value="<?php echo formatDate($room['end_date']); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="newEndDate">New End Date</label>
                        <input type="date" id="newEndDate" name="newEndDate" min="<?php echo date('Y-m-d', strtotime($room['end_date'] . ' +1 day')); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="extensionReason">Reason for Extension</label>
                        <textarea id="extensionReason" name="extensionReason" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
            // Show/hide report issue modal
            const reportIssueBtn = document.getElementById('reportIssueBtn');
            const reportIssueModal = document.getElementById('reportIssueModal');
            const reportIssueCloseBtn = reportIssueModal.querySelector('.close');
            
            reportIssueBtn.addEventListener('click', function() {
                reportIssueModal.style.display = 'block';
            });
            
            reportIssueCloseBtn.addEventListener('click', function() {
                reportIssueModal.style.display = 'none';
            });
            
            window.addEventListener('click', function(event) {
                if (event.target == reportIssueModal) {
                    reportIssueModal.style.display = 'none';
                }
            });
            
            // Show/hide request extension modal
            const requestExtensionBtn = document.getElementById('requestExtensionBtn');
            const requestExtensionModal = document.getElementById('requestExtensionModal');
            const requestExtensionCloseBtn = requestExtensionModal.querySelector('.close');
            
            requestExtensionBtn.addEventListener('click', function() {
                requestExtensionModal.style.display = 'block';
            });
            
            requestExtensionCloseBtn.addEventListener('click', function() {
                requestExtensionModal.style.display = 'none';
            });
            
            window.addEventListener('click', function(event) {
                if (event.target == requestExtensionModal) {
                    requestExtensionModal.style.display = 'none';
                }
            });
            
            // Form submission via AJAX - Report Issue
            const reportIssueForm = document.getElementById('reportIssueForm');
            
            reportIssueForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('ajax/report_issue.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Issue reported successfully!');
                        reportIssueModal.style.display = 'none';
                        this.reset();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            });
            
            // Form submission via AJAX - Request Extension
            const requestExtensionForm = document.getElementById('requestExtensionForm');
            
            requestExtensionForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('ajax/request_extension.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Extension request submitted successfully!');
                        requestExtensionModal.style.display = 'none';
                        this.reset();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            });
        </script>
        
        <?php
    } else {
        echo '<div class="alert alert-info">You do not have any active room allocation. Please contact the administrator.</div>';
    }
    
    $stmt->close();
    closeDbConnection($conn);
    ?>
</div>
