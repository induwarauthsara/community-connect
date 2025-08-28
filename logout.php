<?php
require_once 'config/database.php';
require_once 'includes/common.php';

// Destroy session completely
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

// Start a new session for flash message
session_start();
$_SESSION['flash_message'] = 'You have been logged out successfully.';
$_SESSION['flash_type'] = 'success';

// Redirect to login page
header("Location: login.html");
exit();
?>
