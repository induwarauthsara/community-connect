<?php
// Start session
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Require user to be logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Get current user information
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    global $connection;
    $user_id = (int)$_SESSION['user_id'];
    $result = mysqli_query($connection, "SELECT * FROM users WHERE user_id = $user_id");
    return mysqli_fetch_assoc($result);
}

?>
