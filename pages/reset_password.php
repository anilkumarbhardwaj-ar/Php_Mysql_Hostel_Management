<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$error = '';
$validToken = false;
$token = '';

if (isset($_GET['token'])) {
    $token = sanitizeInput($_GET['token']);
    
    // Debug: Log token verification attempt
    error_log("Reset token from URL: " . $token);
    error_log("Current server time (UTC): " . gmdate('Y-m-d H:i:s'));
    
    try {
        // Verify token
        $conn = getDbConnection();
        
        // First, get the token and expiry from database
        $checkStmt = $conn->prepare("SELECT id, reset_token, reset_token_expiry FROM users WHERE reset_token = ?");
        $checkStmt->bind_param("s", $token);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $userData = $checkResult->fetch_assoc();
            error_log("Token found in database:");
            error_log("Database token: " . $userData['reset_token']);
            error_log("Database expiry: " . $userData['reset_token_expiry']);
            
            // Now check if token is expired using UTC time
            $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > UTC_TIMESTAMP()");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $validToken = true;
                error_log("Token is valid and not expired");
            } else {
                $error = "Reset token has expired.";
                error_log("Token is expired. Current time: " . date('Y-m-d H:i:s'));
            }
            
            $stmt->close();
        } else {
            $error = "Invalid reset token.";
            error_log("Token not found in database");
        }
        
        $checkStmt->close();
        $conn->close();
    } catch (Exception $e) {
        error_log("Error verifying reset token: " . $e->getMessage());
        $error = "An error occurred. Please try again.";
    }
} else {
    $error = "Reset token is required.";
    error_log("Reset token not provided in URL");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $validToken) {
    $password = sanitizeInput($_POST['password']);
    $confirmPassword = sanitizeInput($_POST['confirm_password']);
    
    // Debug: Log password reset attempt
    error_log("Password reset attempt for token: " . $token);
    
    if (empty($password)) {
        $error = "Password is required";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match";
    } else {
        try {
            $conn = getDbConnection();
            
            // Update password and clear reset token
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?");
            $updateStmt->bind_param("ss", $hashedPassword, $token);
            
            if ($updateStmt->execute()) {
                $message = "Password has been reset successfully. You can now login with your new password.";
                $validToken = false; // Prevent form from showing again
                // Debug: Log successful password reset
                error_log("Password reset successful for token: " . $token);
            } else {
                $error = "Failed to reset password. Please try again.";
                // Debug: Log password reset failure
                error_log("Failed to reset password for token: " . $token);
            }
            
            $updateStmt->close();
            $conn->close();
        } catch (Exception $e) {
            // Debug: Log any errors
            error_log("Error resetting password: " . $e->getMessage());
            $error = "An error occurred. Please try again.";
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-form">
        <h2>Reset Password</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo $message; ?>
                <div class="mt-3">
                    <a href="index.php?page=login" class="btn btn-primary">Go to Login</a>
                </div>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($validToken): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div> 