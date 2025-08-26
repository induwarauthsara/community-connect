<?php
// Include database configuration
require_once 'config/database.php';

// Start secure session
startSecureSession();

// Log logout activity if user is logged in
if (isLoggedIn()) {
    logActivity('user_logout', 'users', $_SESSION['user_id']);
}

// Destroy all session data
session_unset();
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to home page
header("Location: home.php");
exit();
?>
