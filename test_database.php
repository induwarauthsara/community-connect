<?php
/**
 * Database Test File - Community Connect
 * 
 * Pure PHP/MySQL volunteer coordination platform using MySQLi Procedural
 * Technology Stack: HTML, CSS, JavaScript, PHP, MySQL (NO external libraries)
 * Database Layer: MySQLi Procedural (NO PDO or ORM)
 * Design Theme: Blue and White color scheme
 * 
 * This file tests the database connection and verifies that all tables
 * were created successfully. Run this after running setup_database.php
 */

require_once 'config/database.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Connect - Database Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        .warning { color: #ffc107; }
        .table-status {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .table-card {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #28a745;
        }
        .table-card.error {
            border-left-color: #dc3545;
        }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Community Connect - Database Test</h1>
        
        <?php
        echo "<h2>Connection Test</h2>";
        
        // Test database connection using MySQLi
        if (testDatabaseConnection()) {
            echo "<p class='success'>✅ Database connection successful!</p>";
            
            // Get database info using MySQLi
            $connection = getDatabaseConnection();
            $result = mysqli_query($connection, "SELECT DATABASE() as db_name, VERSION() as version");
            $info = mysqli_fetch_assoc($result);
            echo "<p class='info'>📊 Database: " . $info['db_name'] . "</p>";
            echo "<p class='info'>🏷️ MySQL Version: " . $info['version'] . "</p>";
            mysqli_close($connection);
            
        } else {
            echo "<p class='error'>❌ Database connection failed</p>";
            echo "</div></body></html>";
            exit;
        }
        
        echo "<h2>Table Status</h2>";
        
        // List of expected tables
    $expected_tables = [
            'users' => 'User accounts (Admin, Organization, Volunteer)',
            'organizations' => 'Organization information',
            'projects' => 'Volunteer projects',
            'volunteer_projects' => 'Volunteer-project assignments'
        ];
        
        echo "<div class='table-status'>";
        
        $all_tables_exist = true;
        foreach ($expected_tables as $table => $description) {
            try {
                $connection = getDatabaseConnection();
                $result = mysqli_query($connection, "DESCRIBE `$table`");
                
                if ($result) {
                    $column_count = mysqli_num_rows($result);
                    
                    echo "<div class='table-card'>";
                    echo "<strong>✅ $table</strong><br>";
                    echo "<small>$description</small><br>";
                    echo "<em>Columns: $column_count</em>";
                    echo "</div>";
                    
                    mysqli_free_result($result);
                } else {
                    $all_tables_exist = false;
                    echo "<div class='table-card error'>";
                    echo "<strong>❌ $table</strong><br>";
                    echo "<small class='error'>Table not found</small>";
                    echo "</div>";
                }
                
                mysqli_close($connection);
                
            } catch (Exception $e) {
                $all_tables_exist = false;
                echo "<div class='table-card error'>";
                echo "<strong>❌ $table</strong><br>";
                echo "<small class='error'>Table not found</small>";
                echo "</div>";
            }
        }
        
        echo "</div>";
        
        if ($all_tables_exist) {
            echo "<p class='success'><strong>🎉 All tables exist and are accessible!</strong></p>";
        } else {
            echo "<p class='error'><strong>⚠️ Some tables are missing. Please run setup_database.php</strong></p>";
        }
        
        // Test some basic queries using MySQLi
        echo "<h2>Data Test</h2>";
        
        try {
            // Check for admin user
            $admin_result = getSingleRecord("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
            
            if ($admin_result && $admin_result['admin_count'] > 0) {
                echo "<p class='success'>✅ Admin user exists</p>";
            } else {
                echo "<p class='warning'>⚠️ No admin user found. Please run setup_database.php to create one.</p>";
            }
            
            // Check for sample organization
            $org_result = getSingleRecord("SELECT COUNT(*) as org_count FROM organizations");
            echo "<p class='info'>📊 Organizations in database: " . ($org_result['org_count'] ?? 0) . "</p>";
            
            // Check for projects
            $project_result = getSingleRecord("SELECT COUNT(*) as project_count FROM projects");
            echo "<p class='info'>📊 Projects in database: " . ($project_result['project_count'] ?? 0) . "</p>";
            
            // Check for volunteers
            $volunteer_result = getSingleRecord("SELECT COUNT(*) as volunteer_count FROM users WHERE role = 'volunteer'");
            echo "<p class='info'>📊 Volunteers in database: " . ($volunteer_result['volunteer_count'] ?? 0) . "</p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error testing data: " . $e->getMessage() . "</p>";
        }
        
        // Test helper functions
        echo "<h2>Helper Functions Test</h2>";
        
        try {
            // Test email validation
            if (isValidEmail("test@example.com")) {
                echo "<p class='success'>✅ Email validation function working</p>";
            }
            
            // Test password hashing
            $test_password = "testpass123";
            $hashed = hashPassword($test_password);
            if (verifyPassword($test_password, $hashed)) {
                echo "<p class='success'>✅ Password hashing functions working</p>";
            }
            
            // Test input sanitization
            $test_input = "<script>alert('test')</script>";
            $sanitized = sanitizeInput($test_input);
            if ($sanitized !== $test_input) {
                echo "<p class='success'>✅ Input sanitization function working</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error testing helper functions: " . $e->getMessage() . "</p>";
        }
        
        echo "<hr>";
        echo "<h2>Summary</h2>";
        
        if ($all_tables_exist) {
            echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;'>";
            echo "<h3 class='success'>🎉 Database Setup Complete!</h3>";
            echo "<p>Your Community Connect database is ready to use. You can now:</p>";
            echo "<ul>";
            echo "<li>Start building your application pages</li>";
            echo "<li>Test the admin login functionality</li>";
            echo "<li>Create additional users and organizations</li>";
            echo "<li>Begin developing the main features</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
            echo "<h3 class='error'>⚠️ Setup Incomplete</h3>";
            echo "<p>Please run <code>setup_database.php</code> to complete the database setup.</p>";
            echo "</div>";
        }
        
        echo "<br><p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
        ?>
    </div>
</body>
</html>
