<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/mail_helper.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    
    // Debug: Log password reset request
    error_log("Password reset requested for email: " . $email);
    
    if (empty($email)) {
        $error = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        try {
            $conn = getDbConnection();
            
            // Check if email exists
            $stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expiry = gmdate('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store reset token
                $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
                $updateStmt->bind_param("ssi", $token, $expiry, $user['id']);
                
                if ($updateStmt->execute()) {
                    // Send reset email
                    $resetLink = "http://{$_SERVER['HTTP_HOST']}/DynamicNewHostel/index.php?page=reset_password&token=" . $token;
                    
                    // Debug: Log reset link
                    error_log("Generated reset link: " . $resetLink);
                    
                    $emailResult = sendPasswordResetEmail($user['name'], $user['email'], $resetLink);
                    
                    if ($emailResult['success']) {
                        $message = "Password reset instructions have been sent to your email.";
                        // Debug: Log successful email send
                        error_log("Password reset email sent successfully to: " . $email);
                    } else {
                        $error = "Failed to send reset email. Please try again.";
                        // Debug: Log email send failure
                        error_log("Failed to send password reset email: " . $emailResult['message']);
                    }
                } else {
                    $error = "Failed to process reset request. Please try again.";
                    // Debug: Log token update failure
                    error_log("Failed to update reset token for user: " . $email);
                }
                
                $updateStmt->close();
            } else {
                $error = "No account found with this email address.";
                // Debug: Log email not found
                error_log("Password reset requested for non-existent email: " . $email);
            }
            
            $stmt->close();
            $conn->close();
        } catch (Exception $e) {
            // Debug: Log any errors
            error_log("Error in password reset process: " . $e->getMessage());
            $error = "An error occurred. Please try again.";
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-form">
        <h2>Forgot Password</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </div>
            
            <div class="form-group text-center">
                <a href="index.php?page=login">Back to Login</a>
            </div>
        </form>
    </div>
</div>

<style>
.auth-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
    background-color: #f8f9fa;
}

.auth-form {
    background: white;
    padding: 40px;
    border-radius: 8px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 400px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}

.form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.btn-primary {
    width: 100%;
    padding: 12px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.btn-primary:hover {
    background: #0056b3;
}

.text-center {
    text-align: center;
}

.alert {
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style> 