-- Create database
CREATE DATABASE IF NOT EXISTS DynamicNewHostel;
USE DynamicNewHostel;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'student', 'guest') NOT NULL DEFAULT 'student',
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Staff details table
CREATE TABLE IF NOT EXISTS staff_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role VARCHAR(50) NOT NULL,
    department VARCHAR(100),
    joining_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Buildings table
CREATE TABLE IF NOT EXISTS buildings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Floors table
CREATE TABLE IF NOT EXISTS floors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    building_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (building_id) REFERENCES buildings(id) ON DELETE CASCADE
);

-- Room types table
CREATE TABLE IF NOT EXISTS room_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    description TEXT,
    rate DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Rooms table
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    floor_id INT NOT NULL,
    room_number VARCHAR(20) NOT NULL,
    type VARCHAR(50) NOT NULL,
    capacity INT NOT NULL DEFAULT 1,
    price_per_day DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (floor_id) REFERENCES floors(id) ON DELETE CASCADE
);

-- Courses table
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    duration INT NOT NULL COMMENT 'Duration in months',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Course schedule table
CREATE TABLE IF NOT EXISTS course_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    day ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    subject VARCHAR(100) NOT NULL,
    instructor_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Student courses table
CREATE TABLE IF NOT EXISTS student_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    join_date DATE NOT NULL,
    end_date DATE,
    status ENUM('active', 'completed', 'dropped') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Allocations table
CREATE TABLE IF NOT EXISTS allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    bed_number INT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    paid_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    status ENUM('paid', 'partial', 'unpaid') NOT NULL DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Payment transactions table
CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash', 'card', 'upi', 'bank_transfer') NOT NULL,
    reference_number VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
);

-- User logs table
CREATE TABLE IF NOT EXISTS user_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Staff attendance table
CREATE TABLE IF NOT EXISTS staff_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    check_in DATETIME NOT NULL,
    check_out DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Room issues table
CREATE TABLE IF NOT EXISTS room_issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    issue_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'pending',
    reported_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_date TIMESTAMP NULL,
    resolved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Extension requests table
CREATE TABLE IF NOT EXISTS extension_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    allocation_id INT NOT NULL,
    current_end_date DATE NOT NULL,
    requested_end_date DATE NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    processed_by INT,
    processed_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (allocation_id) REFERENCES allocations(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user
INSERT INTO users (name, email, password, role) VALUES 
('Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert room types
INSERT INTO room_types (type, description, rate) VALUES
('AC', 'Air conditioned room with attached bathroom', 1000),
('Non-AC', 'Non-air conditioned room with attached bathroom', 750);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Hostel Management System'),
('site_email', 'contact@example.com'),
('site_phone', '+91 9876543210'),
('site_address', '123 Main Street, City, State, Country'),
('currency', 'INR'),
('tax_rate', '18'),
('ac_room_rate', '1000'),
('non_ac_room_rate', '750'),
('maintenance_mode', '0'),
('default_pagination', '10'),
('date_format', 'd/m/Y');

-- Insert sample data for testing
-- Sample buildings
INSERT INTO buildings (name, location) VALUES
('Main Building', 'North Campus'),
('Girls Hostel', 'East Campus'),
('Boys Hostel', 'West Campus');

-- Sample floors
INSERT INTO floors (building_id, name) VALUES
(1, 'Ground Floor'),
(1, 'First Floor'),
(1, 'Second Floor'),
(2, 'Ground Floor'),
(2, 'First Floor'),
(3, 'Ground Floor'),
(3, 'First Floor');

-- Sample rooms
INSERT INTO rooms (floor_id, room_number, type, capacity, price_per_day) VALUES
(1, '101', 'AC', 2, 1000.00),
(1, '102', 'AC', 2, 1000.00),
(1, '103', 'Non-AC', 3, 750.00),
(2, '201', 'AC', 2, 1000.00),
(2, '202', 'Non-AC', 4, 750.00),
(3, '301', 'AC', 1, 1000.00),
(4, '101', 'AC', 2, 1000.00),
(4, '102', 'Non-AC', 3, 750.00),
(5, '201', 'AC', 2, 1000.00),
(6, '101', 'AC', 2, 1000.00),
(6, '102', 'Non-AC', 4, 750.00),
(7, '201', 'AC', 2, 1000.00);

-- Sample courses
INSERT INTO courses (name, duration, description) VALUES
('Computer Science', 36, 'Bachelor of Computer Science program covering programming, algorithms, and software development.'),
('Business Administration', 24, 'Master of Business Administration program focusing on management and leadership.'),
('Electrical Engineering', 48, 'Bachelor of Electrical Engineering program covering circuits, electronics, and power systems.');

-- Sample course schedule
INSERT INTO course_schedule (course_id, day, start_time, end_time, subject, instructor_id) VALUES
(1, 'Monday', '09:00:00', '11:00:00', 'Programming Fundamentals', NULL),
(1, 'Wednesday', '09:00:00', '11:00:00', 'Data Structures', NULL),
(1, 'Friday', '09:00:00', '11:00:00', 'Algorithms', NULL),
(2, 'Tuesday', '10:00:00', '12:00:00', 'Management Principles', NULL),
(2, 'Thursday', '10:00:00', '12:00:00', 'Financial Accounting', NULL),
(3, 'Monday', '13:00:00', '15:00:00', 'Circuit Theory', NULL),
(3, 'Wednesday', '13:00:00', '15:00:00', 'Digital Electronics', NULL);

-- Sample users (password: password)
INSERT INTO users (name, email, password, role, phone, address) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '9876543210', '123 Student St'),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '9876543211', '456 Student Ave'),
('Robert Johnson', 'robert@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '9876543212', '789 Staff Rd'),
('Mary Williams', 'mary@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '9876543213', '101 Staff Ln'),
('David Brown', 'david@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guest', '9876543214', '202 Guest Blvd');

-- Sample staff details
INSERT INTO staff_details (user_id, role, department, joining_date) VALUES
(3, 'trainer', 'Computer Science', '2022-01-15'),
(4, 'maintenance', 'Facilities', '2022-03-10');

-- Sample student courses
INSERT INTO student_courses (student_id, course_id, join_date, status) VALUES
(2, 1, '2022-08-01', 'active'),
(3, 2, '2022-07-15', 'active');

-- Sample allocations
INSERT INTO allocations (user_id, room_id, bed_number, start_date, end_date, status) VALUES
(2, 1, 1, '2022-08-01', '2023-07-31', 'active'),
(3, 4, 1, '2022-07-15', '2023-01-14', 'active'),
(5, 6, 1, '2022-09-01', '2022-09-10', 'active');

-- Sample payments
INSERT INTO payments (user_id, total_amount, paid_amount, status) VALUES
(2, 24000, 12000, 'partial'),
(3, 18000, 18000, 'paid'),
(5, 5000, 0, 'unpaid');

-- Sample payment transactions
INSERT INTO payment_transactions (payment_id, amount, payment_date, payment_method, reference_number, notes) VALUES
(1, 12000, '2022-08-01', 'bank_transfer', 'TRX123456', 'First semester payment'),
(2, 9000, '2022-07-15', 'cash', NULL, 'First installment'),
(2, 9000, '2022-10-15', 'cash', NULL, 'Second installment');

-- Sample staff attendance
INSERT INTO staff_attendance (staff_id, check_in, check_out) VALUES
(3, '2022-10-01 09:00:00', '2022-10-01 17:00:00'),
(4, '2022-10-01 08:30:00', '2022-10-01 16:30:00'),
(3, '2022-10-02 09:15:00', NULL);

-- Sample room issues
INSERT INTO room_issues (user_id, room_id, issue_type, description, priority, status) VALUES
(2, 1, 'plumbing', 'Leaking tap in bathroom', 'medium', 'pending'),
(3, 4, 'electrical', 'Light fixture not working', 'high', 'in_progress');
