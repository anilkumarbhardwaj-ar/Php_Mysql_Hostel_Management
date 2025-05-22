<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    $role = sanitizeInput($_POST['role']);
    
    // Debug: Log login attempt
    error_log("Login attempt - Email: " . $email . ", Role: " . $role);
    
    if (empty($email) || empty($password) || empty($role)) {
        $errors[] = "All fields are required";
    } else {
        try {
            if (authenticateUser($email, $password, $role)) {
                // Debug: Log successful login
                error_log("Login successful for user: " . $email . " with role: " . $role);
                
                // Redirect based on role
                switch ($role) {
                    case 'admin':
                        header("Location: index.php?page=dashboard");
                        break;
                    case 'staff':
                        header("Location: index.php?page=staff_dashboard");
                        break;
                    case 'intern':
                        header("Location: index.php?page=intern_dashboard");
                        break;
                    case 'student':
                        header("Location: index.php?page=student_dashboard");
                        break;
                    case 'others':
                        header("Location: index.php?page=others_dashboard");
                        break;
                    default:
                        header("Location: index.php?page=dashboard");
                }
                exit;
            } else {
                // Debug: Log failed login
                error_log("Login failed for user: " . $email . " with role: " . $role);
                $errors[] = "Invalid email, password, or role";
            }
        } catch (Exception $e) {
            // Debug: Log any errors
            error_log("Login error: " . $e->getMessage());
            $errors[] = "An error occurred during login. Please try again.";
        }
    }
}
?>

<style>
:root {
    --primary-color: #007bff;
    --primary-dark: #0056b3;
    --text-color: #333;
    --text-light: #555;
    --border-color: #ddd;
    --error-color: #721c24;
    --success-color: #155724;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: var(--text-color);
}

.auth-container {
    display: flex;
    min-height: 100vh;
    background-color: #f8f9fa;
    width: 100%;
    flex-direction: row-reverse;
}

.auth-form-container {
    flex: 0 0 50%; /* Fixed width of 50% */
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    background-color: #fff;
}

.auth-form-content {
    width: 100%;
    max-width: 400px;
    padding: 20px;
}

.auth-logo {
    text-align: left;
    margin-bottom: 40px;
}

.auth-logo svg {
    width: 50px; /* Adjust size as needed */
    height: auto;
    fill: var(--primary-color);
}

.auth-header {
    margin-bottom: 30px;
}

.auth-header h2 {
    font-size: 2rem;
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 10px;
}

.auth-header p {
    font-size: 1rem;
    color: var(--text-light);
}

.auth-form .form-group {
    margin-bottom: 20px;
}

.auth-form .form-group label {
    display: block;
    margin-bottom: 8px;
    color: var(--text-light);
    font-weight: 500;
    font-size: 0.95rem;
}

.auth-form .form-group input,
.auth-form .form-group select {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid var(--border-color);
    border-radius: 5px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
    background-color: #fff;
}

.auth-form .form-group input:focus,
.auth-form .form-group select:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
}

.btn-primary {
    width: 100%;
    padding: 12px;
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.btn-primary:hover {
    background-color: var(--primary-dark);
}

.text-center {
    text-align: center;
    margin-top: 20px;
}

.text-center p {
    margin: 10px 0;
    color: var(--text-light);
}

.text-center a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.text-center a:hover {
    color: var(--primary-dark);
    text-decoration: underline;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    font-size: 0.95rem;
}

.alert-danger {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: var(--error-color);
}

.auth-image-section {
    flex: 0 0 50%;
    background-image: url('assets/hostel.jpg');
    background-size: cover;
    background-position: center;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: white;
    min-height: 100vh;
}

.auth-image-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.8)); /* Dark overlay */
}

.auth-image-text {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 500px;
    margin: 0 auto;
}

.auth-image-text h3 {
    font-size: 2.5rem;
    margin-bottom: 15px;
    font-weight: 700;
    text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.4);
}

.auth-image-text p {
    font-size: 1.1rem;
    line-height: 1.8;
    opacity: 0.95;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .auth-container {
        flex-direction: column;
    }
    
    .auth-form-container,
    .auth-image-section {
        flex: 0 0 100%; /* Full width on mobile */
        min-height: auto; /* Remove fixed height on mobile */
    }
    
    .auth-image-section {
        min-height: 300px;
    }
    
    .auth-form-container {
        padding: 20px;
    }
    
    .auth-form-content {
        padding: 0;
    }
    
    .auth-image-text {
        text-align: center;
        margin: 0 auto;
        padding: 20px;
    }
    
    .auth-image-text h3 {
        font-size: 2rem;
    }
    
    .auth-image-text p {
        font-size: 1rem;
    }
}
</style>

<div class="auth-container">
    <div class="auth-form-container">
        <div class="auth-form-content">
            <div class="auth-logo">
                <!-- Placeholder for Logo -->
                <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="#007bff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 L2 7 L12 12 L22 7 L12 2 Z"></path><path d="M2 17 L12 22 L22 17"></path><path d="M2 12 L12 17 L22 12"></path></svg>
            </div>
            
            <div class="auth-header">
                <h2>Login</h2>
                <p>Enter your details to log in</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <!-- Retained 'Login As' functionality -->
                <div class="form-group">
                    <label for="role">Login As</label>
                    <select id="role" name="role" required>
                        <option value="">Select your role</option>
                        <option value="admin">Administrator</option>
                        <option value="staff">Staff</option>
                        <option value="intern">Intern</option>
                        <option value="student">Student</option>
                        <option value="others">Others</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
                
                <div class="text-center">
                    <p><a href="index.php?page=forgot_password">Forgot your password?</a></p>
                </div>
            </form>
            
            <div class="text-center">
                 <p>Don't have an account? <a href="index.php?page=register">Create an account</a></p>
            </div>
        </div>
    </div>
    
    <div class="auth-image-section">
        <div class="auth-image-text">
            <h3>Sleep well to explore new avenues</h3>
            <p>Varius dictumst interdum dolor lorem. Hendrerit at quisque purus non posuere.</p>
        </div>
    </div>
</div>
