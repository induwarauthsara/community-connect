<?php
/**
 * Community Connect - Database Configuration and Helper Functions
 * Pure MySQLi procedural implementation with security helpers
 */

// Database configuration
$DB_CONFIG = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'community_connect'
];

// Global connection variable
$conn = null;

/**
 * Establish database connection
 * @return mysqli|false Connection object or false on failure
 */
function connectDatabase() {
    global $DB_CONFIG, $conn;
    
    if ($conn && mysqli_ping($conn)) {
        return $conn;
    }
    
    $conn = mysqli_connect(
        $DB_CONFIG['host'], 
        $DB_CONFIG['username'], 
        $DB_CONFIG['password'], 
        $DB_CONFIG['database']
    );
    
    if (!$conn) {
        error_log("Database connection failed: " . mysqli_connect_error());
        return false;
    }
    
    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}

/**
 * Execute a prepared statement and return single record
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters for the query
 * @return array|null Single record as associative array or null
 */
function getSingleRecord($sql, $params = []) {
    $conn = connectDatabase();
    if (!$conn) return null;
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Prepare failed: " . mysqli_error($conn));
        return null;
    }
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $record = $result ? mysqli_fetch_assoc($result) : null;
    
    mysqli_stmt_close($stmt);
    return $record;
}

/**
 * Execute a prepared statement and return multiple records
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters for the query
 * @return array Array of records
 */
function getMultipleRecords($sql, $params = []) {
    $conn = connectDatabase();
    if (!$conn) return [];
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Prepare failed: " . mysqli_error($conn));
        return [];
    }
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $records = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $records[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
    return $records;
}

/**
 * Execute an INSERT query and return the inserted ID
 * @param string $sql SQL INSERT statement with placeholders
 * @param array $params Parameters for the query
 * @return int|false Inserted ID or false on failure
 */
function insertRecord($sql, $params = []) {
    $conn = connectDatabase();
    if (!$conn) return false;
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Prepare failed: " . mysqli_error($conn));
        return false;
    }
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    $success = mysqli_stmt_execute($stmt);
    $insertId = $success ? mysqli_insert_id($conn) : false;
    
    mysqli_stmt_close($stmt);
    return $insertId;
}

/**
 * Execute an UPDATE or DELETE query
 * @param string $sql SQL statement with placeholders
 * @param array $params Parameters for the query
 * @return bool Success status
 */
function executeQuery($sql, $params = []) {
    $conn = connectDatabase();
    if (!$conn) return false;
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Prepare failed: " . mysqli_error($conn));
        return false;
    }
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $success;
}

// ===== SECURITY HELPER FUNCTIONS =====

/**
 * Hash password using PHP's password_hash()
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password using PHP's password_verify()
 * @param string $password Plain text password
 * @param string $hash Hashed password from database
 * @return bool True if password matches
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Sanitize input for output (prevent XSS)
 * @param string $input Raw input
 * @return string Sanitized output
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 * @param string $email Email address
 * @return bool True if valid email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// ===== SESSION MANAGEMENT =====

/**
 * Start secure session with proper settings
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        session_start();
        
        // Regenerate session ID to prevent fixation
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
    }
}

/**
 * Check if user is logged in
 * @return bool True if user is logged in
 */
function isLoggedIn() {
    startSecureSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user data
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    return getSingleRecord(
        "SELECT user_id, name, email, role, organization_id FROM users WHERE user_id = ?",
        [$_SESSION['user_id']]
    );
}

/**
 * Require user to be logged in, redirect if not
 * @param string $redirect_to Redirect destination if not logged in
 */
function requireLogin($redirect_to = 'login.html') {
    if (!isLoggedIn()) {
        header("Location: $redirect_to");
        exit();
    }
}

/**
 * Check if current user has specific role
 * @param string $role Required role
 * @return bool True if user has the role
 */
function hasRole($role) {
    $user = getCurrentUser();
    return $user && $user['role'] === $role;
}

/**
 * Require specific role, redirect if user doesn't have it
 * @param string $role Required role
 * @param string $redirect_to Redirect destination if unauthorized
 */
function requireRole($role, $redirect_to = 'login.html') {
    requireLogin($redirect_to);
    if (!hasRole($role)) {
        header("Location: $redirect_to");
        exit();
    }
}

// ===== UTILITY FUNCTIONS =====

/**
 * Generate a secure random token
 * @param int $length Token length
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Redirect with message
 * @param string $location Redirect destination
 * @param string $message Optional message
 * @param string $type Message type (success, error, info)
 */
function redirectWithMessage($location, $message = '', $type = 'info') {
    startSecureSession();
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $location");
    exit();
}

/**
 * Get and clear flash message
 * @return array Message and type, or empty array
 */
function getFlashMessage() {
    startSecureSession();
    $message = $_SESSION['flash_message'] ?? '';
    $type = $_SESSION['flash_type'] ?? 'info';
    
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    
    return $message ? ['message' => $message, 'type' => $type] : [];
}

// Initialize connection on file include
connectDatabase();
?>
