-- Community Connect Database Setup
-- Run this in phpMyAdmin SQL tab

-- Create database
CREATE DATABASE IF NOT EXISTS community_connect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE community_connect;

-- Create organizations table
CREATE TABLE IF NOT EXISTS organizations (
    org_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    address TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'organization', 'volunteer') NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    skills TEXT,
    availability TEXT,
    organization_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    FOREIGN KEY (organization_id) REFERENCES organizations(org_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create projects table
CREATE TABLE IF NOT EXISTS projects (
    project_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    location VARCHAR(200),
    start_date DATE,
    end_date DATE,
    start_time TIME,
    end_time TIME,
    requirements TEXT,
    skills_needed TEXT,
    capacity INT DEFAULT 0,
    current_volunteers INT DEFAULT 0,
    created_by INT NOT NULL,
    organization_id INT,
    status ENUM('pending','approved','active','completed','cancelled') DEFAULT 'pending',
    priority ENUM('low','medium','high') DEFAULT 'medium',
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_organization (organization_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(org_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create volunteer_projects table
CREATE TABLE IF NOT EXISTS volunteer_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    volunteer_id INT NOT NULL,
    project_id INT NOT NULL,
    status ENUM('registered','confirmed','completed','cancelled') DEFAULT 'registered',
    notes TEXT,
    hours_contributed DECIMAL(5,2) DEFAULT 0.00,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    UNIQUE KEY unique_volunteer_project (volunteer_id, project_id),
    INDEX idx_volunteer (volunteer_id),
    INDEX idx_project (project_id),
    INDEX idx_status (status),
    FOREIGN KEY (volunteer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create announcements table
CREATE TABLE IF NOT EXISTS announcements (
    announcement_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    content TEXT,
    type ENUM('general','urgent','event','maintenance') DEFAULT 'general',
    target_audience ENUM('all','volunteers','organizations','admins') DEFAULT 'all',
    is_active BOOLEAN DEFAULT TRUE,
    start_date DATE,
    end_date DATE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_type (type),
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create activity_logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_table (table_name),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key for organizations
ALTER TABLE organizations ADD CONSTRAINT fk_org_created_by 
FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE;

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO users (name, email, password, role, is_active, email_verified) 
VALUES ('System Administrator', 'admin@communityconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE, TRUE);

-- Insert sample organization
INSERT IGNORE INTO organizations (name, description, contact_email, created_by) 
VALUES ('Community Helpers', 'A local organization dedicated to community service and volunteer coordination.', 'contact@communityhelpers.org', 1);

-- Insert welcome announcement
INSERT IGNORE INTO announcements (title, content, type, created_by) 
VALUES ('Welcome to Community Connect!', 'Thank you for joining our volunteer coordination platform. Start by exploring available projects and connecting with local organizations.', 'general', 1);

-- Show success message
SELECT 'Database setup completed successfully!' as Status;
