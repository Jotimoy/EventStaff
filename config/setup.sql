-- ====================================
-- EventStaff Database Setup Script
-- ====================================
-- This script creates the database and all required tables
-- Run this in phpMyAdmin or MySQL command line

-- Create Database
CREATE DATABASE IF NOT EXISTS eventstaff_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eventstaff_db;

-- ====================================
-- Table 1: users
-- Main authentication table for all user types
-- ====================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'organizer', 'employee') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- Table 2: employee_profiles
-- Profile information for employees/workers
-- ====================================
CREATE TABLE IF NOT EXISTS employee_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    skills TEXT,
    experience TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- Table 3: organizer_profiles
-- Profile information for event organizers
-- ====================================
CREATE TABLE IF NOT EXISTS organizer_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- Table 4: events
-- Events created by organizers
-- ====================================
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizer_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    event_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_organizer_id (organizer_id),
    INDEX idx_event_date (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- Table 5: event_shifts
-- Shifts/jobs within events
-- ====================================
CREATE TABLE IF NOT EXISTS event_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    shift_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    required_staff INT NOT NULL DEFAULT 1,
    payment_per_shift DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('open', 'filled', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_event_id (event_id),
    INDEX idx_shift_date (shift_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- Table 6: shift_applications
-- Employee applications for shifts
-- ====================================
CREATE TABLE IF NOT EXISTS shift_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shift_id INT NOT NULL,
    employee_id INT NOT NULL,
    application_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (shift_id) REFERENCES event_shifts(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_shift_id (shift_id),
    INDEX idx_employee_id (employee_id),
    INDEX idx_status (application_status),
    UNIQUE KEY unique_application (shift_id, employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- Table 7: payments
-- Payment tracking for approved applications
-- ====================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES shift_applications(id) ON DELETE CASCADE,
    INDEX idx_application_id (application_id),
    INDEX idx_payment_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- Insert Default Admin User
-- Email: admin@eventstaff.com
-- Password: admin123 (MD5 hashed)
-- ====================================
INSERT INTO users (email, password, role) VALUES 
('admin@eventstaff.com', MD5('admin123'), 'admin');

-- ====================================
-- Insert Sample Data (Optional - for testing)
-- ====================================

-- Sample Organizer
INSERT INTO users (email, password, role) VALUES 
('organizer@test.com', MD5('password123'), 'organizer');

SET @organizer_user_id = LAST_INSERT_ID();

INSERT INTO organizer_profiles (user_id, company_name, contact_person, phone, address) VALUES 
(@organizer_user_id, 'Tech Events Ltd', 'John Doe', '+880-1234567890', 'Dhaka, Bangladesh');

-- Sample Employee
INSERT INTO users (email, password, role) VALUES 
('employee@test.com', MD5('password123'), 'employee');

SET @employee_user_id = LAST_INSERT_ID();

INSERT INTO employee_profiles (user_id, full_name, phone, skills, experience) VALUES 
(@employee_user_id, 'Jane Smith', '+880-9876543210', 'Event Management, Customer Service', '2 years in promotional events');

-- Sample Event
INSERT INTO events (organizer_id, title, description, location, event_date) VALUES 
(@organizer_user_id, 'Tech Conference 2026', 'Annual technology conference with exhibitions and seminars', 'Dhaka International Convention Center', '2026-04-15');

SET @event_id = LAST_INSERT_ID();

-- Sample Shift
INSERT INTO event_shifts (event_id, shift_date, start_time, end_time, required_staff, payment_per_shift, status) VALUES 
(@event_id, '2026-04-15', '09:00:00', '17:00:00', 5, 1500.00, 'open');

-- ====================================
-- Database Setup Complete!
-- ====================================
-- You can now login with:
-- Admin: admin@eventstaff.com / admin123
-- Organizer: organizer@test.com / password123
-- Employee: employee@test.com / password123
-- ====================================

SELECT 'Database setup completed successfully!' AS status;
