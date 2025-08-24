<?php
/**
 * Community Connect - Database Test Script
 * Test database connectivity and helper functions
 */

// Include the database configuration
require_once 'config/database.php';

echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>Community Connect - Database Test</title>';
echo '<style>
    body { font-family: Arial, sans-serif; background: #f8f9fa; color: #333; padding: 20px; }
    .container { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #007bff; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #e9ecef; border-radius: 8px; }
    .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
    .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
    .info { background: #d1ecf1; border-color: #b8daff; color: #0c5460; }
    .code { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
</style></head><body>';

echo '<div class="container">';
echo '<h1>Community Connect - Database Test</h1>';

// Test 1: Database Connection
echo '<div class="test-section">';
echo '<h2>1. Database Connection Test</h2>';

$conn = connectDatabase();
if ($conn) {
    echo '<div class="success">✓ Database connection successful!</div>';
    echo '<div class="info">Connected to database: ' . htmlspecialchars($GLOBALS['DB_CONFIG']['database']) . '</div>';
} else {
    echo '<div class="error">✗ Database connection failed!</div>';
    echo '<div class="info">Please check your database configuration in config/database.php</div>';
    echo '</div></div></body></html>';
    exit;
}
echo '</div>';

// Test 2: Table Existence
echo '<div class="test-section">';
echo '<h2>2. Table Structure Test</h2>';

$required_tables = ['users', 'organizations', 'projects', 'volunteer_projects'];
$missing_tables = [];

foreach ($required_tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<div class='success'>✓ Table '$table' exists</div>";
        mysqli_free_result($result);
    } else {
        echo "<div class='error'>✗ Table '$table' missing</div>";
        $missing_tables[] = $table;
    }
}

if (!empty($missing_tables)) {
    echo '<div class="info">Run setup_database.php to create missing tables.</div>';
}
echo '</div>';

// Test 3: Helper Functions
echo '<div class="test-section">';
echo '<h2>3. Helper Function Tests</h2>';

// Test getSingleRecord
echo '<h3>Testing getSingleRecord()</h3>';
$test_user = getSingleRecord("SELECT COUNT(*) as count FROM users");
if ($test_user && isset($test_user['count'])) {
    echo '<div class="success">✓ getSingleRecord() working - Found ' . $test_user['count'] . ' users</div>';
} else {
    echo '<div class="error">✗ getSingleRecord() failed</div>';
}

// Test email validation
echo '<h3>Testing Email Validation</h3>';
$valid_email = isValidEmail('test@example.com');
$invalid_email = isValidEmail('invalid-email');
if ($valid_email && !$invalid_email) {
    echo '<div class="success">✓ Email validation working correctly</div>';
} else {
    echo '<div class="error">✗ Email validation failed</div>';
}

// Test password hashing
echo '<h3>Testing Password Functions</h3>';
$test_password = 'testpassword123';
$hashed = hashPassword($test_password);
$verified = verifyPassword($test_password, $hashed);
if ($verified) {
    echo '<div class="success">✓ Password hashing and verification working</div>';
} else {
    echo '<div class="error">✗ Password functions failed</div>';
}

// Test input sanitization
echo '<h3>Testing Input Sanitization</h3>';
$test_input = '<script>alert("test")</script>';
$sanitized = sanitizeInput($test_input);
if ($sanitized !== $test_input && strpos($sanitized, '<script>') === false) {
    echo '<div class="success">✓ Input sanitization working</div>';
    echo '<div class="code">Input: ' . htmlspecialchars($test_input) . '<br>Sanitized: ' . htmlspecialchars($sanitized) . '</div>';
} else {
    echo '<div class="error">✗ Input sanitization failed</div>';
}

echo '</div>';

// Test 4: Database Operations
echo '<div class="test-section">';
echo '<h2>4. Database Operations Test</h2>';

// Test insert and delete
echo '<h3>Testing Insert/Delete Operations</h3>';

// Try to insert a test record
$test_email = 'test_' . time() . '@example.com';
$test_user_id = insertRecord(
    "INSERT INTO users (name, email, password, role, is_active, created_at) VALUES (?, ?, ?, ?, TRUE, NOW())",
    ['Test User', $test_email, hashPassword('testpassword'), 'volunteer']
);

if ($test_user_id) {
    echo '<div class="success">✓ Insert operation successful - Created user ID: ' . $test_user_id . '</div>';
    
    // Test select
    $retrieved_user = getSingleRecord("SELECT * FROM users WHERE user_id = ?", [$test_user_id]);
    if ($retrieved_user && $retrieved_user['email'] === $test_email) {
        echo '<div class="success">✓ Select operation successful</div>';
    } else {
        echo '<div class="error">✗ Select operation failed</div>';
    }
    
    // Clean up - delete the test record
    $deleted = executeQuery("DELETE FROM users WHERE user_id = ?", [$test_user_id]);
    if ($deleted) {
        echo '<div class="success">✓ Delete operation successful - Test record cleaned up</div>';
    } else {
        echo '<div class="error">✗ Delete operation failed - Manual cleanup may be needed</div>';
    }
} else {
    echo '<div class="error">✗ Insert operation failed</div>';
}

echo '</div>';

// Test 5: Admin User Check
echo '<div class="test-section">';
echo '<h2>5. Admin User Check</h2>';

$admin_user = getSingleRecord("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
if ($admin_user) {
    echo '<div class="success">✓ Admin user exists</div>';
    echo '<div class="info">Admin email: ' . htmlspecialchars($admin_user['email']) . '</div>';
    echo '<div class="info">Default login: admin@communityconnect.com / admin123</div>';
} else {
    echo '<div class="error">✗ No admin user found</div>';
    echo '<div class="info">Run setup_database.php to create default admin user</div>';
}

echo '</div>';

// Test 6: Session Functions
echo '<div class="test-section">';
echo '<h2>6. Session Function Test</h2>';

// Test session start
startSecureSession();
if (session_status() === PHP_SESSION_ACTIVE) {
    echo '<div class="success">✓ Session management working</div>';
} else {
    echo '<div class="error">✗ Session management failed</div>';
}

// Test flash message functions
$_SESSION['flash_message'] = 'Test message';
$_SESSION['flash_type'] = 'success';
$flash = getFlashMessage();
if ($flash && $flash['message'] === 'Test message') {
    echo '<div class="success">✓ Flash message functions working</div>';
} else {
    echo '<div class="error">✗ Flash message functions failed</div>';
}

echo '</div>';

// Summary
echo '<div class="test-section info">';
echo '<h2>Test Summary</h2>';
echo '<p>All core functions have been tested. If you see any errors above, please:</p>';
echo '<ol>';
echo '<li>Run setup_database.php to create/update database structure</li>';
echo '<li>Check database credentials in config/database.php</li>';
echo '<li>Ensure MySQL server is running</li>';
echo '<li>Verify PHP MySQLi extension is enabled</li>';
echo '</ol>';

echo '<h3>Next Steps</h3>';
echo '<ul>';
echo '<li><a href="setup_database.php">Run Database Setup</a></li>';
echo '<li><a href="login.html">Test Login Page</a></li>';
echo '<li><a href="signup.html">Test Signup Page</a></li>';
echo '</ul>';
echo '</div>';

echo '</div></body></html>';

// Close database connection
mysqli_close($conn);
?>
