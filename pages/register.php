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
    // Debug: Log POST data
    error_log("Registration POST data: " . print_r($_POST, true));
    
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    $confirmPassword = sanitizeInput($_POST['confirm_password']);
    $role = sanitizeInput($_POST['role']);
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    
    // Debug: Log sanitized data
    error_log("Sanitized data: " . print_r([
        'name' => $name,
        'email' => $email,
        'role' => $role,
        'phone' => $phone
    ], true));
    
    // Validate input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($role)) {
        $errors[] = "Role is required";
    } elseif (!in_array($role, ['staff', 'intern', 'student', 'others'])) {
        $errors[] = "Invalid role selected";
    }
    
    // Debug: Log validation errors
    if (!empty($errors)) {
        error_log("Validation errors: " . print_r($errors, true));
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            $conn = getDbConnection();
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email already exists";
            } else {
                // Register user
                if (registerUser($name, $email, $password, $role)) {
                    $message = "Registration successful! Please check your email for login credentials.";
                    // Debug: Log successful registration
                    error_log("User registered successfully: " . $email);
                } else {
                    $error = "Registration failed. Please try again.";
                    // Debug: Log registration failure
                    error_log("Registration failed for: " . $email);
                }
            }
            
            $stmt->close();
            $conn->close();
        } catch (Exception $e) {
            // Debug: Log any database errors
            error_log("Database error during registration: " . $e->getMessage());
            $error = "An error occurred during registration. Please try again.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

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
    max-width: 1200px;
    display: flex;
    flex-direction: column;
    gap: 40px;
}

.auth-form h2 {
    margin-bottom: 30px;
    color: #333;
    text-align: center;
    width: 100%;
    font-size: 28px;
    font-weight: 600;
}

.form-sections-container {
    display: flex;
    flex-direction: row;
    gap: 40px;
    align-items: flex-start;
}

.form-section {
    flex: 1;
    padding: 20px;
    min-width: 0;
}

.form-group {
    margin-bottom: 25px;
    width: 100%;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #555;
    font-weight: 500;
    font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
    transition: border-color 0.3s, box-shadow 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #007bff;
    outline: none;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.btn-primary {
    background-color: #007bff;
    color: white;
    padding: 14px 28px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    width: 100%;
    transition: background-color 0.3s;
    font-weight: 500;
    margin-top: 10px;
}

.btn-primary:hover {
    background-color: #0056b3;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
    font-size: 14px;
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

.text-center {
    text-align: center;
}

.mt-3 {
    margin-top: 1rem;
}

.form-group.text-center {
    margin-top: 20px;
}

.form-group.text-center a {
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
}

.form-group.text-center a:hover {
    text-decoration: underline;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .form-sections-container {
        flex-direction: column;
    }
    
    .auth-form {
        padding: 20px;
    }
    
    .form-section {
        padding: 10px;
    }
}

/* Ensure form sections are equal width */
.form-group + .form-group {
    margin-top: 20px;
}

/* Style the select dropdown */
.form-group select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 16px;
    padding-right: 40px;
}
</style>

<div class="auth-container">
    <div class="auth-form">
        <h2>Create Account</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo $message; ?>
                <div class="mt-3">
                    <a href="index.php?page=login" class="btn btn-primary">Go to Login</a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" id="registerForm">
            <div class="form-sections-container">
                <div class="form-section">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Register As</label>
                        <select id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="staff">Staff</option>
                            <option value="intern">Intern</option>
                            <option value="student">Student</option>
                            <option value="others">Others</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="form-group">
                        <label for="phone">Phone Number (Optional)</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address (Optional)</label>
                        <textarea id="address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" id="registerBtn" form="registerForm">Register</button>
                    </div>
                    
                    <div class="form-group text-center">
                        <p>Already have an account? <a href="index.php?page=login">Login here</a></p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Get form data
    const formData = new FormData(this);
    
    // Validate form
    let isValid = true;
    const errors = [];
    
    // Check required fields
    const requiredFields = ['name', 'email', 'password', 'confirm_password', 'role'];
    requiredFields.forEach(field => {
        if (!formData.get(field)) {
            errors.push(`${field.charAt(0).toUpperCase() + field.slice(1)} is required`);
            isValid = false;
        }
    });
    
    // Validate email
    const email = formData.get('email');
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errors.push('Invalid email format');
        isValid = false;
    }
    
    // Validate password
    const password = formData.get('password');
    if (password && password.length < 6) {
        errors.push('Password must be at least 6 characters long');
        isValid = false;
    }
    
    // Check password match
    if (formData.get('password') !== formData.get('confirm_password')) {
        errors.push('Passwords do not match');
        isValid = false;
    }
    
    if (!isValid) {
        // Display errors
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger';
        errorDiv.innerHTML = errors.join('<br>');
        
        // Remove any existing error messages
        const existingError = document.querySelector('.alert-danger');
        if (existingError) {
            existingError.remove();
        }
        
        // Insert new error message
        this.insertBefore(errorDiv, this.firstChild);
        return;
    }
    
    // If validation passes, submit the form
    this.submit();
});
</script> 