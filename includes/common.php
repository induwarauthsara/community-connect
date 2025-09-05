<?php
// Start session
session_start();

// Start secure session (alias for session_start with additional security)
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

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

// Get multiple records from database
function getMultipleRecords($query, $params = []) {
    global $connection;
    
    if (empty($params)) {
        $result = mysqli_query($connection, $query);
    } else {
        // For parameterized queries, we'll use simple string replacement for this simplified system
        foreach ($params as $param) {
            $escaped_param = mysqli_real_escape_string($connection, $param);
            $query = preg_replace('/\?/', "'$escaped_param'", $query, 1);
        }
        $result = mysqli_query($connection, $query);
    }
    
    if (!$result) {
        return [];
    }
    
    $records = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $records[] = $row;
    }
    return $records;
}

// Get single record from database
function getSingleRecord($query, $params = []) {
    $records = getMultipleRecords($query, $params);
    return !empty($records) ? $records[0] : null;
}

// Insert record into database
function insertRecord($query, $params = []) {
    global $connection;
    
    if (empty($params)) {
        return mysqli_query($connection, $query);
    } else {
        foreach ($params as $param) {
            $escaped_param = mysqli_real_escape_string($connection, $param);
            $query = preg_replace('/\?/', "'$escaped_param'", $query, 1);
        }
        return mysqli_query($connection, $query);
    }
}

// Update record in database
function updateRecord($query, $params = []) {
    return insertRecord($query, $params); // Same logic
}

// Generate registration code
function generateRegistrationCode($length = 8) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

// Format date for display
function formatDate($date) {
    if (!$date) return 'Not specified';
    return date('M j, Y', strtotime($date));
}

?>
