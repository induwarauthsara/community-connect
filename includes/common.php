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

// Require user to have specific role
function requireRole($required_role) {
    requireLogin(); // First ensure user is logged in
    
    $user = getCurrentUser();
    if (!$user || $user['role'] !== $required_role) {
        // Redirect to appropriate dashboard based on actual role
        if ($user && $user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } elseif ($user && $user['role'] === 'organization') {
            header("Location: organization_dashboard.php");
        } elseif ($user && $user['role'] === 'volunteer') {
            header("Location: volunteer_dashboard.php");
        } else {
            header("Location: login.php");
        }
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
        // Log the error for debugging
        error_log("Database query error: " . mysqli_error($connection));
        error_log("Failed query: " . $query);
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

// Truncate text to specified length
function truncateText($text, $length = 100, $suffix = '...') {
    if (!$text) return '';
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . $suffix;
}

// Get status badge HTML
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="status-badge" style="background: #ffc107; color: #000;">⏳ Pending</span>',
        'approved' => '<span class="status-badge" style="background: #28a745; color: white;">✅ Approved</span>',
        'active' => '<span class="status-badge" style="background: #007bff; color: white;">🚀 Active</span>',
        'completed' => '<span class="status-badge" style="background: #6c757d; color: white;">✅ Completed</span>',
        'cancelled' => '<span class="status-badge" style="background: #dc3545; color: white;">❌ Cancelled</span>',
        'registered' => '<span class="status-badge" style="background: #17a2b8; color: white;">📝 Registered</span>'
    ];
    
    return $badges[$status] ?? '<span class="status-badge" style="background: #6c757d; color: white;">' . htmlspecialchars(ucfirst($status)) . '</span>';
}

// Delete record from database
function deleteRecord($query, $params = []) {
    return insertRecord($query, $params); // Same logic as insert/update
}

// Validate required fields
function validateRequiredFields($fields) {
    $missing = [];
    foreach ($fields as $field_name => $value) {
        if (empty(trim($value))) {
            $missing[] = ucwords(str_replace('_', ' ', $field_name));
        }
    }
    return $missing;
}

// Validate email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate date format
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Validate date range (end date must be after start date)
function isValidDateRange($start_date, $end_date) {
    // If either date is empty, the range is considered valid
    if (empty($start_date) || empty($end_date)) {
        return true;
    }
    
    // Both dates must be valid
    if (!isValidDate($start_date) || !isValidDate($end_date)) {
        return false;
    }
    
    // End date must be after or equal to start date
    return strtotime($end_date) >= strtotime($start_date);
}

?>
