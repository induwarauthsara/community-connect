<?php
/**
 * Community Connect - Database Setup Script
 * 
 * Pure PHP/MySQL volunteer coordination platform using MySQLi Procedural
 * Technology Stack: HTML, CSS, JavaScript, PHP, MySQL (NO external libraries)
 * Database Layer: MySQLi Procedural (NO PDO or ORM)
 * Design Theme: Blue and White color scheme
 * 
 * This script creates the database and all required tables for the
 * Community Connect Volunteer Coordinator Platform if they don't exist.
 * 
 * Run this file once to set up your database structure.
 */

// Database configuration
$host = 'localhost';
$username = 'root';  // Change this to your MySQL username
$password = '';      // Change this to your MySQL password
$database = 'community_connect';

// Create connection without selecting database first
$connection = mysqli_connect($host, $username, $password);

if (!$connection) {
    die("❌ Connection failed: " . mysqli_connect_error());
}

echo "✅ Connected to MySQL server successfully.<br>";

// Create database if it doesn't exist
$create_db_sql = "CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (mysqli_query($connection, $create_db_sql)) {
    echo "✅ Database '$database' created or already exists.<br>";
} else {
    die("❌ Error creating database: " . mysqli_error($connection));
}

// Close initial connection
mysqli_close($connection);

// Connect to the specific database
$connection = mysqli_connect($host, $username, $password, $database);
if (!$connection) {
    die("❌ Connection to database failed: " . mysqli_connect_error());
}

mysqli_set_charset($connection, 'utf8mb4');
echo "✅ Connected to database '$database' successfully.<br><br>";

// SQL statements for creating tables
$tables = [
    // Organizations table
    'organizations' => "
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    // Users table
    'users' => "
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    // Projects table
    'projects' => "
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    // Volunteer project assignments
    'volunteer_projects' => "
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    // Project suggestions from non-logged users
    'project_suggestions' => "
        CREATE TABLE IF NOT EXISTS project_suggestions (
            suggestion_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            project_title VARCHAR(150) NOT NULL,
            project_description TEXT,
            location VARCHAR(200),
            proposed_start DATE,
            proposed_end DATE,
            requirements TEXT,
            expected_volunteers INT DEFAULT 1,
            status ENUM('pending','under_review','approved','rejected') DEFAULT 'pending',
            admin_notes TEXT,
            reviewed_by INT NULL,
            reviewed_at TIMESTAMP NULL,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_email (email),
            FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    // Announcements
    'announcements' => "
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    // User sessions for login management
    'user_sessions' => "
        CREATE TABLE IF NOT EXISTS user_sessions (
            session_id VARCHAR(128) PRIMARY KEY,
            user_id INT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            INDEX idx_user (user_id),
            INDEX idx_expires (expires_at),
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    // Activity logs for tracking system changes
    'activity_logs' => "
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    "
];

// Create tables using MySQLi
$success_count = 0;
$total_tables = count($tables);

echo "<h2>Creating Tables:</h2>";

foreach ($tables as $table_name => $sql) {
    if (mysqli_query($connection, $sql)) {
        echo "✅ Table '$table_name' created successfully.<br>";
        $success_count++;
    } else {
        echo "❌ Error creating table '$table_name': " . mysqli_error($connection) . "<br>";
    }
}

echo "<br>";

// Add foreign key constraints that couldn't be added during table creation
echo "<h2>Adding Foreign Key Constraints:</h2>";

$foreign_keys = [
    "ALTER TABLE organizations ADD CONSTRAINT fk_org_created_by 
     FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE",
];

foreach ($foreign_keys as $fk_sql) {
    if (mysqli_query($connection, $fk_sql)) {
        echo "✅ Foreign key constraint added successfully.<br>";
    } else {
        // This might fail if the constraint already exists, which is okay
        if (strpos(mysqli_error($connection), 'Duplicate key name') === false) {
            echo "⚠️ Note: " . mysqli_error($connection) . "<br>";
        }
    }
}

// Create default admin user if it doesn't exist
echo "<br><h2>Creating Default Admin User:</h2>";

// Check if admin user exists
$check_admin_sql = "SELECT COUNT(*) as count FROM users WHERE role = 'admin'";
$result = mysqli_query($connection, $check_admin_sql);
$row = mysqli_fetch_assoc($result);
$admin_count = $row['count'];

if ($admin_count == 0) {
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $insert_admin_sql = "INSERT INTO users (name, email, password, role, is_active, email_verified) 
                         VALUES ('System Administrator', 'admin@communityconnect.com', ?, 'admin', TRUE, TRUE)";
    
    $stmt = mysqli_prepare($connection, $insert_admin_sql);
    mysqli_stmt_bind_param($stmt, 's', $admin_password);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ Default admin user created successfully.<br>";
        echo "📧 Email: admin@communityconnect.com<br>";
        echo "🔑 Password: admin123<br>";
        echo "⚠️ <strong>Please change the admin password after first login!</strong><br>";
    } else {
        echo "❌ Error creating admin user: " . mysqli_stmt_error($stmt) . "<br>";
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "ℹ️ Admin user already exists.<br>";
}

// Create some sample data (optional)
echo "<br><h2>Creating Sample Data:</h2>";

// Check if sample organization exists
$check_org_sql = "SELECT COUNT(*) as count FROM organizations";
$result = mysqli_query($connection, $check_org_sql);
$row = mysqli_fetch_assoc($result);
$org_count = $row['count'];

if ($org_count == 0) {
    // Create sample organization
    $insert_org_sql = "INSERT INTO organizations (name, description, contact_email, created_by) 
                       VALUES ('Community Helpers', 'A local organization dedicated to community service and volunteer coordination.', 'contact@communityhelpers.org', 1)";
    
    if (mysqli_query($connection, $insert_org_sql)) {
        echo "✅ Sample organization created.<br>";
    } else {
        echo "❌ Error creating sample organization: " . mysqli_error($connection) . "<br>";
    }
    
    // Create sample announcement
    $insert_announcement_sql = "INSERT INTO announcements (title, content, type, created_by) 
                               VALUES ('Welcome to Community Connect!', 'Thank you for joining our volunteer coordination platform. Start by exploring available projects and connecting with local organizations.', 'general', 1)";
    
    if (mysqli_query($connection, $insert_announcement_sql)) {
        echo "✅ Welcome announcement created.<br>";
    } else {
        echo "❌ Error creating announcement: " . mysqli_error($connection) . "<br>";
    }
} else {
    echo "ℹ️ Sample data already exists.<br>";
}

// Summary
echo "<br><hr>";
echo "<h2>Setup Summary:</h2>";
echo "📊 Tables created: $success_count/$total_tables<br>";
echo "🗄️ Database: $database<br>";
echo "🌐 Host: $host<br>";

if ($success_count == $total_tables) {
    echo "<br>🎉 <strong style='color: green;'>Database setup completed successfully!</strong><br>";
    echo "✅ Your Community Connect platform is ready to use.<br><br>";
    
    echo "<div style='background-color: #f0f8ff; padding: 15px; border: 1px solid #ccc; border-radius: 5px;'>";
    echo "<h3>Next Steps:</h3>";
    echo "1. Configure your database connection in your main application files<br>";
    echo "2. Set up your web server to point to your project directory<br>";
    echo "3. Test the login with the admin credentials above<br>";
    echo "4. Create additional users and organizations as needed<br>";
    echo "5. <strong>Remember to change the default admin password!</strong><br>";
    echo "</div>";
} else {
    echo "<br>⚠️ <strong style='color: orange;'>Setup completed with some issues.</strong><br>";
    echo "Please check the error messages above and resolve any problems.<br>";
}

// Close connection
mysqli_close($connection);
echo "<br><em>Connection closed.</em>";
?>
