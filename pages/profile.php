<div class="content">
    <h2>My Profile</h2>
    
    <?php
    // Get user data
    $conn = getDbConnection();
    $userId = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $name = sanitizeInput($_POST['name']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address']);
        $currentPassword = sanitizeInput($_POST['current_password']);
        $newPassword = sanitizeInput($_POST['new_password']);
        $confirmPassword = sanitizeInput($_POST['confirm_password']);
        
        $errors = [];
        $success = [];
        
        // Update profile information
        if (!empty($name) && !empty($email)) {
            // Check if email is already used by another user
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $checkStmt->bind_param("si", $email, $userId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                $errors[] = "Email is already in use by another account";
            } else {
                $updateStmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                $updateStmt->bind_param("ssssi", $name, $email, $phone, $address, $userId);
                
                if ($updateStmt->execute()) {
                    $success[] = "Profile information updated successfully";
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                } else {
                    $errors[] = "Failed to update profile information";
                }
                
                $updateStmt->close();
            }
            
            $checkStmt->close();
        }
        
        // Change password
        if (!empty($currentPassword) && !empty($newPassword) && !empty($confirmPassword)) {
            // Verify current password
            if (password_verify($currentPassword, $user['password'])) {
                // Check if new passwords match
                if ($newPassword === $confirmPassword) {
                    // Update password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $passwordStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $passwordStmt->bind_param("si", $hashedPassword, $userId);
                    
                    if ($passwordStmt->execute()) {
                        $success[] = "Password changed successfully";
                    } else {
                        $errors[] = "Failed to change password";
                    }
                    
                    $passwordStmt->close();
                } else {
                    $errors[] = "New passwords do not match";
                }
            } else {
                $errors[] = "Current password is incorrect";
            }
        }
        
        // Display errors or success messages
        if (!empty($errors)) {
            echo '<div class="alert alert-danger">';
            foreach ($errors as $error) {
                echo '<p>' . $error . '</p>';
            }
            echo '</div>';
        }
        
        if (!empty($success)) {
            echo '<div class="alert alert-success">';
            foreach ($success as $message) {
                echo '<p>' . $message . '</p>';
            }
            echo '</div>';
        }
        
        // Refresh user data
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    }
    
    $stmt->close();
    closeDbConnection($conn);
    ?>
    
    <div class="profile-container">
        <div class="profile-section">
            <h3>Profile Information</h3>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="role">Role</label>
                    <input type="text" id="role" value="<?php echo ucfirst($user['role']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" value="<?php echo $user['phone']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"><?php echo $user['address']; ?></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </div>
            </form>
        </div>
        
        <div class="profile-section">
            <h3>Change Password</h3>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
        
        <div class="profile-section">
            <h3>Account Activity</h3>
            
            <table>
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Date</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conn = getDbConnection();
                    
                    $stmt = $conn->prepare("
                        SELECT action, created_at, ip_address 
                        FROM user_logs 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT 10
                    ");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . ucfirst($row['action']) . "</td>";
                            echo "<td>" . formatDate($row['created_at']) . "</td>";
                            echo "<td>" . $row['ip_address'] . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>No activity found</td></tr>";
                    }
                    
                    $stmt->close();
                    closeDbConnection($conn);
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
