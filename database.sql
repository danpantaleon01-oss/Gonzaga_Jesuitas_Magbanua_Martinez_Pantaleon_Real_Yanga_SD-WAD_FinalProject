-- Campus Event Management System Database Schema
-- Create Database
CREATE DATABASE IF NOT EXISTS campus_events CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campus_events;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'organizer', 'participant') DEFAULT 'participant',
    course VARCHAR(100),
    year_level VARCHAR(50),
    contact_number VARCHAR(20),
    profile_image VARCHAR(255) DEFAULT 'default-avatar.png',
    status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_student_id (student_id),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Events Table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizer_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    venue VARCHAR(200) NOT NULL,
    event_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    category ENUM('seminar', 'workshop', 'sports_fest', 'cultural_event', 'student_org', 'academic', 'other') NOT NULL,
    participant_limit INT DEFAULT NULL,
    poster_image VARCHAR(255) DEFAULT NULL,
    status ENUM('draft', 'pending_approval', 'approved', 'ongoing', 'completed', 'cancelled') DEFAULT 'pending_approval',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_date (event_date),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Registrations Table
CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    attendance_status ENUM('absent', 'present') DEFAULT 'absent',
    qr_code VARCHAR(100) UNIQUE DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (event_id, user_id),
    INDEX idx_event_status (event_id, status),
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password Resets Table
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity Logs Table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_action (user_id, action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Default Admin Account (password: admin123)
INSERT INTO users (student_id, first_name, last_name, email, password, role, course, year_level, contact_number, status)
VALUES ('ADMIN001', 'System', 'Administrator', 'admin@campus.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administration', 'N/A', '000-000-0000', 'active')
ON DUPLICATE KEY UPDATE email=email;

-- Insert Sample Organizer Account (password: organizer123)
INSERT INTO users (student_id, first_name, last_name, email, password, role, course, year_level, contact_number, status)
VALUES ('ORG001', 'John', 'Organizer', 'organizer@campus.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'organizer', 'Computer Science', '4th Year', '123-456-7890', 'active')
ON DUPLICATE KEY UPDATE email=email;

-- Insert Sample Participant Account (password: participant123)
INSERT INTO users (student_id, first_name, last_name, email, password, role, course, year_level, contact_number, status)
VALUES ('2024-0001', 'Jane', 'Student', 'student@campus.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'participant', 'Information Technology', '2nd Year', '098-765-4321', 'active')
ON DUPLICATE KEY UPDATE email=email;
