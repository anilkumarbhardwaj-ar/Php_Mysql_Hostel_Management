<?php
// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Authenticate user
function authenticateUser($email, $password, $role) {
    try {
        $conn = getDbConnection();
        
        // Debug: Log authentication attempt
        error_log("Authenticating user - Email: " . $email . ", Role: " . $role);
        
        // Special handling for admin login
        if ($role === 'admin') {
            $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? AND role = 'admin'");
            $stmt->bind_param("s", $email);
        } else {
            $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? AND role = ?");
            $stmt->bind_param("ss", $email, $role);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Debug: Log user found
            error_log("User found in database: " . print_r($user, true));
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Debug: Log password verification success
                error_log("Password verification successful for user: " . $email);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Log login activity
                logUserActivity($user['id'], 'login');
                
                $stmt->close();
                closeDbConnection($conn);
                
                return true;
            } else {
                // Debug: Log password verification failure
                error_log("Password verification failed for user: " . $email);
            }
        } else {
            // Debug: Log user not found
            error_log("No user found with email: " . $email . " and role: " . $role);
        }
        
        $stmt->close();
        closeDbConnection($conn);
        
        return false;
    } catch (Exception $e) {
        // Debug: Log any errors
        error_log("Authentication error: " . $e->getMessage());
        return false;
    }
}

// Register new user
function registerUser($name, $email, $password, $role = 'student') {
    $conn = getDbConnection();
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        closeDbConnection($conn);
        return false;
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);
    $success = $stmt->execute();
    
    if ($success) {
        $userId = $conn->insert_id;
        
        // Log registration activity
        logUserActivity($userId, 'register');
    }
    
    $stmt->close();
    closeDbConnection($conn);
    
    return $success;
}

// Log user out
function logoutUser() {
    // Log logout activity
    if (isset($_SESSION['user_id'])) {
        logUserActivity($_SESSION['user_id'], 'logout');
    }
    
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    return true;
}

// Log user activity
function logUserActivity($userId, $action) {
    $conn = getDbConnection();
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $conn->prepare("INSERT INTO user_logs (user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $action, $ip, $userAgent);
    $stmt->execute();
    
    $stmt->close();
    closeDbConnection($conn);
}
?>
