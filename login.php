<?php
// Include database configuration
require_once 'config/database.php';

// Start secure session
startSecureSession();

// Form submission
if(isset($_POST['email'], $_POST['password'])){
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    // Validate email format
    if (!isValidEmail($email)) {
        echo "<h2>Invalid email format!</h2>";
        echo '<a href="login.html">Back to Login</a>';
        exit();
    }

    try {
        // Fetch user using prepared statement
        $sql = "SELECT user_id, name, email, password, role, is_active FROM users WHERE email = ? LIMIT 1";
        $user = getSingleRecord($sql, [$email]);

        if($user){
            // Check if user is active
            if(!$user['is_active']){
                echo "<h2>Account is inactive. Contact admin.</h2>";
                echo '<a href="login.html">Back to Login</a>';
                exit();
            }

            // Verify password
            if(verifyPassword($password, $user['password'])){
                // Login successful - set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Log the login activity
                logActivity('user_login', 'users', $user['user_id']);
                
                // Redirect to home page
                header("Location: home.php");
                exit();
            } else {
                echo "<h2>Incorrect password!</h2>";
                echo '<a href="login.html">Back to Login</a>';
            }
        } else {
            // User not registered
            echo "<h2>Email not found!</h2>";
            echo '<a href="signup.html">Sign Up</a>';
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        echo "<h2>Login failed. Please try again later.</h2>";
        echo '<a href="login.html">Back to Login</a>';
    }
}
?>
