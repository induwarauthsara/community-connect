CREATE DATABASE IF NOT EXISTS `community_connect` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `community_connect`;

CREATE TABLE IF NOT EXISTS organizations (
    org_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    website VARCHAR(255),
    address TEXT,
    mission TEXT,
    established_year YEAR,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'organization', 'volunteer') NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    skills TEXT,
    availability TEXT,
    birth_date DATE,
    emergency_contact VARCHAR(100),
    emergency_phone VARCHAR(20),
    organization_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    FOREIGN KEY (organization_id) REFERENCES organizations(org_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE organizations 
ADD CONSTRAINT fk_org_created_by 
FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL;

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
    created_by INT NULL,
    organization_id INT,
    status ENUM('pending','approved','active','completed','cancelled') DEFAULT 'pending',
    priority ENUM('low','medium','high') DEFAULT 'medium',
    image_url VARCHAR(255),
    submitted_by_name VARCHAR(100) NULL,
    submitted_by_email VARCHAR(100) NULL,
    submitted_by_phone VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_organization (organization_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(org_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

INSERT INTO users (
    name, 
    username, 
    email, 
    password, 
    role, 
    is_active, 
    email_verified
) VALUES (
    'System Administrator',
    'ucsc',
    'admin@communityconnect.com',
    'ucsc',
    'admin',
    TRUE,
    TRUE
) ON DUPLICATE KEY UPDATE 
    name = VALUES(name),
    email = VALUES(email),
    password = VALUES(password),
    role = VALUES(role),
    is_active = VALUES(is_active),
    email_verified = VALUES(email_verified);

INSERT INTO users (
    name, 
    username, 
    email, 
    password, 
    role, 
    is_active, 
    email_verified
) VALUES (
    'Sample Organization User',
    'orguser',
    'org@communityconnect.com',
    'orgpass',
    'organization',
    TRUE,
    TRUE
) ON DUPLICATE KEY UPDATE 
    name = VALUES(name),
    email = VALUES(email),
    password = VALUES(password),
    role = VALUES(role),
    is_active = VALUES(is_active),
    email_verified = VALUES(email_verified);

INSERT INTO users (
    name, 
    username, 
    email, 
    password, 
    role, 
    is_active, 
    email_verified
) VALUES (
    'Sample Volunteer User',
    'user',
    'volunteer@communityconnect.com',
    'user',
    'volunteer',
    TRUE,
    TRUE
) ON DUPLICATE KEY UPDATE 
    name = VALUES(name),
    email = VALUES(email),
    password = VALUES(password),
    role = VALUES(role),
    is_active = VALUES(is_active),
    email_verified = VALUES(email_verified);
