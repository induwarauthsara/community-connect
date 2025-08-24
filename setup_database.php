<?php
/**
 * Community Connect - Database Setup Script (Simple UI)
 *
 * Actions:
 * - Drop Database
 * - Create Database & Table Structure
 * - Add Sample Data
 * - Clear Database Sample Data
 */

// Database configuration
$host = 'localhost';
$username = 'root';   // Change this to your MySQL username
$password = '';       // Change this to your MySQL password
$database = 'community_connect';

// 1) Connect to MySQL server (without choosing a database)
function connectServer($host, $username, $password) {
    $conn = mysqli_connect($host, $username, $password);
    if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
    }
    return $conn;
}

// 2) Connect to a specific database
function connectDatabase($host, $username, $password, $database) {
    $conn = mysqli_connect($host, $username, $password, $database);
    if (!$conn) {
    die("Connection to database failed: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}

// 3) Check if a database exists
function databaseExists($host, $username, $password, $database) {
    $conn = @mysqli_connect($host, $username, $password);
    if (!$conn) { return false; }
    $db_safe = mysqli_real_escape_string($conn, $database);
    $res = mysqli_query($conn, "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db_safe'");
    $exists = $res && mysqli_num_rows($res) > 0;
    if ($res) { mysqli_free_result($res); }
    mysqli_close($conn);
    return $exists;
}

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
    
    // Projects table (also stores guest submissions as pending)
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
            created_by INT NULL,
            organization_id INT,
            status ENUM('pending','approved','active','completed','cancelled') DEFAULT 'pending',
            priority ENUM('low','medium','high') DEFAULT 'medium',
            image_url VARCHAR(255),
            -- Guest submission metadata (for non-logged users)
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
    
    // Removed: project_suggestions and announcements (feature deprecated)
    
    // (Activity logs removed)
];

// Create the database and all tables
function createStructure($host, $username, $password, $database, $tables) {
    // Step A: Create database if it doesn't exist
    $serverConn = connectServer($host, $username, $password);
    $create_db_sql = "CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (mysqli_query($serverConn, $create_db_sql)) {
        echo "Database '$database' is ready.<br>";
    } else {
        die("Error creating database: " . mysqli_error($serverConn));
    }
    mysqli_close($serverConn);

    // Step B: Connect to that database
    $connection = connectDatabase($host, $username, $password, $database);

    // Step C: Create tables
    $success_count = 0;
    $total_tables = count($tables);
    echo "<h2>Creating Tables</h2>";
    foreach ($tables as $table_name => $sql) {
        if (mysqli_query($connection, $sql)) {
            echo "Table '$table_name' OK.<br>";
            $success_count++;
        } else {
            echo "Error creating table '$table_name': " . mysqli_error($connection) . "<br>";
        }
    }
    echo "<br>";

    // Step D: Add a foreign key (simple attempt; if it already exists, we just show a note)
    echo "<h2>Adding Foreign Key</h2>";
    $fk_sql = "ALTER TABLE organizations ADD CONSTRAINT fk_org_created_by 
               FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE";
    if (mysqli_query($connection, $fk_sql)) {
        echo "Foreign key added.<br>";
    } else {
        echo "Note: " . htmlspecialchars(mysqli_error($connection)) . "<br>";
    }

    // Step E: Make sure a default admin exists
    echo "<br><h2>Default Admin User</h2>";
    $check_admin_sql = "SELECT COUNT(*) as count FROM users WHERE role = 'admin'";
    $result = mysqli_query($connection, $check_admin_sql);
    $row = $result ? mysqli_fetch_assoc($result) : ['count' => 0];
    $admin_count = (int)($row['count'] ?? 0);
    if ($result) { mysqli_free_result($result); }

    if ($admin_count == 0) {
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $insert_admin_sql = "INSERT INTO users (name, email, password, role, is_active, email_verified) 
                             VALUES ('System Administrator', 'admin@communityconnect.com', ?, 'admin', TRUE, TRUE)";
        $stmt = mysqli_prepare($connection, $insert_admin_sql);
        mysqli_stmt_bind_param($stmt, 's', $admin_password);
        if (mysqli_stmt_execute($stmt)) {
            echo "Admin user created.<br>";
            echo "Email: admin@communityconnect.com<br>";
            echo "Password: admin123 (please change after first login)<br>";
        } else {
            echo "Error creating admin user: " . mysqli_stmt_error($stmt) . "<br>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Admin user already exists.<br>";
    }

    // Step F: Summary
    echo "<br><hr>";
    echo "<h2>Summary</h2>";
    echo "Database: " . htmlspecialchars($database) . "<br>";
    echo "Host: " . htmlspecialchars($host) . "<br>";
    echo "<br>Done.";

    mysqli_close($connection);
}

// Show a simple database summary (only if DB exists)
function showDatabaseSummary($host, $username, $password, $database, $tables) {
    $conn = @connectDatabase($host, $username, $password, $database);
    if (!$conn) { return; }
    echo '<div class="card" style="grid-column:1 / -1;padding:20px">';
    echo '<h3>Database Summary</h3>';
    // Basic info
    $res = mysqli_query($conn, "SELECT DATABASE() AS db_name, VERSION() AS version");
    $info = $res ? mysqli_fetch_assoc($res) : ['db_name' => $database, 'version' => 'unknown'];
    if ($res) { mysqli_free_result($res); }
    echo '<p class="muted">Name: ' . htmlspecialchars($info['db_name']) . '</p>';
    echo '<p class="muted">MySQL: ' . htmlspecialchars($info['version']) . '</p>';

    // Tables status
    echo '<div class="sp"></div>';
    echo '<strong>Tables</strong>';
    echo '<ul style="margin-top:8px; padding-left:18px">';
    foreach (array_keys($tables) as $t) {
        $exists = mysqli_query($conn, "SHOW TABLES LIKE '" . mysqli_real_escape_string($conn, $t) . "'");
        $ok = ($exists && mysqli_num_rows($exists) > 0);
        if ($exists) { mysqli_free_result($exists); }
        echo '<li>' . htmlspecialchars($t) . ': ' . ($ok ? 'OK' : 'Missing') . '</li>';
    }
    echo '</ul>';

    // Simple row counts for key tables if they exist
    $keyTables = ['users','organizations','projects'];
    echo '<div class="sp"></div>';
    echo '<strong>Row Counts</strong>';
    echo '<ul style="margin-top:8px; padding-left:18px">';
    foreach ($keyTables as $t) {
        $exists = mysqli_query($conn, "SHOW TABLES LIKE '" . mysqli_real_escape_string($conn, $t) . "'");
        $ok = ($exists && mysqli_num_rows($exists) > 0);
        if ($exists) { mysqli_free_result($exists); }
        if ($ok) {
            $cntRes = mysqli_query($conn, "SELECT COUNT(*) AS c FROM `$t`");
            $cnt = $cntRes ? mysqli_fetch_assoc($cntRes)['c'] : 0;
            if ($cntRes) { mysqli_free_result($cntRes); }
            echo '<li>' . htmlspecialchars($t) . ': ' . (int)$cnt . '</li>';
        } else {
            echo '<li>' . htmlspecialchars($t) . ': n/a</li>';
        }
    }
    echo '</ul>';
    echo '</div>';

    mysqli_close($conn);
}

// Handle actions
$action = $_POST['action'] ?? null;
$confirmed = $_POST['confirmed'] ?? 'false';

// Simple UI header
echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>Community Connect - Setup</title>';
echo '<style>body{font-family:Arial, sans-serif;background:#f8f9fa;color:#333;padding:20px} .container{max-width:900px;margin:0 auto;background:#fff;border:1px solid #e9ecef;border-radius:8px;padding:20px} h1{color:#007bff} .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin:16px 0} .card{border:1px solid #e9ecef;border-radius:8px;padding:16px;background:#f8faff} button{background:#007bff;color:#fff;border:none;border-radius:6px;padding:10px 14px;cursor:pointer;width:100%} button:hover{background:#0056b3} .muted{color:#666;font-size:0.9em} form{margin:0} .danger{background:#dc3545} .danger:hover{background:#b02a37} .note{background:#eef6ff;border-left:4px solid #007bff;padding:10px;border-radius:6px;margin:10px 0}</style>';
echo '</head><body><div class="container">';
echo '<h1>Community Connect - Database Setup</h1>';

if ($action) {
    // Backend confirmation check for all CUD actions
    if ($confirmed !== 'true') {
        echo '<p style="color:#dc3545"><strong>Error:</strong> Action requires confirmation.</p>';
    } else {
        if ($action === 'drop_db') {
            echo '<h2>Drop Database</h2>';
            $conn = connectServer($host, $username, $password);
            $sql = "DROP DATABASE IF EXISTS `$database`";
            if (mysqli_query($conn, $sql)) {
                echo "Database '$database' dropped (if it existed).<br>";
            } else {
                echo "Error dropping database: " . mysqli_error($conn) . "<br>";
            }
            mysqli_close($conn);
    } elseif ($action === 'create') {
            echo '<h2>Create Database & Table Structure</h2>';
            createStructure($host, $username, $password, $database, $tables);
        } else {
            echo '<p class="muted">Unknown action.</p>';
        }
    }

    echo '<div class="note">Reload this page to perform another action.</div>';
}

// UI: forms grid
echo '<div class="grid">';
$db_exists = databaseExists($host, $username, $password, $database);
if ($db_exists) {
    // Summary card first (large)
    showDatabaseSummary($host, $username, $password, $database, $tables);

    // Drop DB (with confirmation) - compact card
    echo '<div class="card" style="max-width:220px;padding:8px;font-size:12px;align-self:start;justify-self:start">';
    echo '<h3 style="margin:0 0 6px 0;font-size:14px">Drop Database</h3><p class="muted" style="margin:0 0 8px 0;font-size:12px">Remove the entire database.</p>';
    echo '<form method="POST" onsubmit="return confirmDrop(this)">';
    echo '<input type="hidden" name="action" value="drop_db">';
    echo '<input type="hidden" name="confirmed" value="false">';
    echo '<button type="submit" class="danger" style="padding:6px 8px;font-size:12px">Drop Database</button>';
    echo '</form></div>';
} else {
    // Create DB & Tables only
    echo '<div class="card"><h3>Create Database & Table Structure</h3><p class="muted">Creates DB and all tables.</p>';
    echo '<form method="POST" onsubmit="return confirmCreate(this)">';
    echo '<input type="hidden" name="action" value="create">';
    echo '<input type="hidden" name="confirmed" value="false">';
    echo '<button type="submit">Create DB & Tables</button>';
    echo '</form></div>';
}

echo '</div>'; // grid

// Footer/help
echo '<p class="muted">DB: ' . htmlspecialchars($database) . ' @ ' . htmlspecialchars($host) . '</p>';

echo '<script>
function setConfirmed(form){ form.querySelector("input[name=confirmed]").value = "true"; }
function confirmDrop(form){ if(confirm("Are you absolutely sure you want to DROP the entire database? This cannot be undone.")){ setConfirmed(form); return true;} return false; }
function confirmCreate(form){ if(confirm("Create or update the database structure?")){ setConfirmed(form); return true;} return false; }
// Only drop/create actions are available
</script>';

echo '</div></body></html>';
?>
