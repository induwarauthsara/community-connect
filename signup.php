<?php
// Include database configuration
require_once 'config/database.php';

// Start secure session
startSecureSession();

// Get POST data
$name = sanitizeInput($_POST['username']);
$email = sanitizeInput($_POST['email']);
$phone = sanitizeInput($_POST['phone']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

// Basic validation
if($password !== $confirm_password){
    die("Passwords do not match!");
}

// Validate email format
if (!isValidEmail($email)) {
    die("Invalid email format!");
}

// Validate password strength (minimum 6 characters)
if (strlen($password) < 6) {
    die("Password must be at least 6 characters long!");
}

try {
    // Check if email exists
    $check_sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
    $result = getSingleRecord($check_sql, [$email]);
    
    if($result['count'] > 0){
        die("Email already registered! <a href='login.html'>Login here</a>");
    }

    // Hash password securely
    $hashed_password = hashPassword($password);

    // Insert into database with role 'volunteer' as default
    $insert_sql = "INSERT INTO users (name, email, phone, password, role, is_active, email_verified) 
                   VALUES (?, ?, ?, ?, 'volunteer', TRUE, FALSE)";
    
    $user_id = insertRecord($insert_sql, [$name, $email, $phone, $hashed_password]);
    
    if($user_id > 0){
        // Log the registration activity
        logActivity('user_registration', 'users', $user_id);
        
        echo "<div style='text-align: center; padding: 50px; font-family: Arial, sans-serif;'>";
        echo "<h2 style='color: #2d7ade;'>✅ Registration Successful!</h2>";
        echo "<p>Welcome to Community Connect, " . htmlspecialchars($name) . "!</p>";
        echo "<p>Your account has been created successfully.</p>";
        echo "<a href='login.html' style='background: #2d7ade; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Login Now</a>";
        echo "</div>";
    } else {
        throw new Exception("Registration failed. Please try again.");
    }
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    echo "<h2>Registration failed: " . $e->getMessage() . "</h2>";
    echo "<a href='signup.html'>Try Again</a>";
}
?>
