<?php
require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDbConnection();
    
    // SQL to create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'staff', 'student', 'intern', 'others') NOT NULL,
        reset_token VARCHAR(64) NULL,
        reset_token_expiry DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Users table created successfully\n";
        
        // Insert default admin user
        $adminSql = "INSERT INTO users (name, email, password, role) VALUES 
            ('Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')";
        
        if ($conn->query($adminSql) === TRUE) {
            echo "Default admin user created successfully\n";
        } else {
            echo "Error creating default admin user: " . $conn->error . "\n";
        }
    } else {
        echo "Error creating users table: " . $conn->error . "\n";
    }
    
    closeDbConnection($conn);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 