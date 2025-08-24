<?php
/**
 * Database Configuration File - Community Connect
 * 
 * Pure PHP/MySQL volunteer coordination platform using MySQLi Procedural
 * Technology Stack: HTML, CSS, JavaScript, PHP, MySQL (NO external libraries)
 * Database Layer: MySQLi Procedural (NO PDO or ORM)
 * Design Theme: Blue and White color scheme
 */

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');          // Change this to your MySQL username
define('DB_PASSWORD', '');              // Change this to your MySQL password
define('DB_NAME', 'community_connect');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get database connection using MySQLi Procedural
 * 
 * @return mysqli Database connection resource
 * @throws Exception If connection fails
 */
function getDatabaseConnection() {
    $connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    if (!$connection) {
        error_log("Database connection failed: " . mysqli_connect_error());
        throw new Exception("Database connection failed. Please try again later.");
    }
    
    // Set charset
    mysqli_set_charset($connection, DB_CHARSET);
    
    return $connection;
}

/**
 * Test database connection
 * 
 * @return bool True if connection successful, false otherwise
 */
function testDatabaseConnection() {
    $connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($connection) {
        mysqli_close($connection);
        return true;
    }
    return false;
}

/**
 * Execute a prepared statement with parameters using MySQLi
 * 
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @param string $types Types string for bind_param (optional, auto-detected)
 * @return mysqli_result|bool Query result
 */
function executeQuery($sql, $params = [], $types = '') {
    $connection = getDatabaseConnection();
    
    if (empty($params)) {
        $result = mysqli_query($connection, $sql);
        if (!$result) {
            mysqli_close($connection);
            throw new Exception("Query failed: " . mysqli_error($connection));
        }
        return $result;
    }
    
    $stmt = mysqli_prepare($connection, $sql);
    if (!$stmt) {
        mysqli_close($connection);
        throw new Exception("Prepare failed: " . mysqli_error($connection));
    }
    
    // Auto-detect parameter types if not provided
    if (empty($types)) {
        $types = str_repeat('s', count($params)); // Default to string
        foreach ($params as $param) {
            if (is_int($param)) {
                $types = substr_replace($types, 'i', array_search($param, $params), 1);
            } elseif (is_float($param)) {
                $types = substr_replace($types, 'd', array_search($param, $params), 1);
            }
        }
    }
    
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        throw new Exception("Execute failed: " . $error);
    }
    
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

/**
 * Get a single record from database using MySQLi
 * 
 * @param string $sql SQL query
 * @param array $params Parameters to bind
 * @return array|null Single record or null if not found
 */
function getSingleRecord($sql, $params = []) {
    $result = executeQuery($sql, $params);
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    
    $connection = getDatabaseConnection();
    mysqli_close($connection);
    
    return $row;
}

/**
 * Get multiple records from database using MySQLi
 * 
 * @param string $sql SQL query
 * @param array $params Parameters to bind
 * @return array Array of records
 */
function getMultipleRecords($sql, $params = []) {
    $result = executeQuery($sql, $params);
    $rows = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    
    mysqli_free_result($result);
    
    $connection = getDatabaseConnection();
    mysqli_close($connection);
    
    return $rows;
}

/**
 * Insert a new record and return the ID using MySQLi
 * 
 * @param string $sql SQL insert query
 * @param array $params Parameters to bind
 * @return int Last inserted ID
 */
function insertRecord($sql, $params = []) {
    $connection = getDatabaseConnection();
    
    if (empty($params)) {
        $result = mysqli_query($connection, $sql);
        if (!$result) {
            $error = mysqli_error($connection);
            mysqli_close($connection);
            throw new Exception("Insert failed: " . $error);
        }
        $insert_id = mysqli_insert_id($connection);
        mysqli_close($connection);
        return $insert_id;
    }
    
    $stmt = mysqli_prepare($connection, $sql);
    if (!$stmt) {
        mysqli_close($connection);
        throw new Exception("Prepare failed: " . mysqli_error($connection));
    }
    
    // Auto-detect parameter types
    $types = '';
    foreach ($params as $param) {
        if (is_int($param)) {
            $types .= 'i';
        } elseif (is_float($param)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        throw new Exception("Execute failed: " . $error);
    }
    
    $insert_id = mysqli_insert_id($connection);
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    
    return $insert_id;
}

/**
 * Update records and return affected row count using MySQLi
 * 
 * @param string $sql SQL update query
 * @param array $params Parameters to bind
 * @return int Number of affected rows
 */
function updateRecord($sql, $params = []) {
    $connection = getDatabaseConnection();
    
    if (empty($params)) {
        $result = mysqli_query($connection, $sql);
        if (!$result) {
            $error = mysqli_error($connection);
            mysqli_close($connection);
            throw new Exception("Update failed: " . $error);
        }
        $affected_rows = mysqli_affected_rows($connection);
        mysqli_close($connection);
        return $affected_rows;
    }
    
    $stmt = mysqli_prepare($connection, $sql);
    if (!$stmt) {
        mysqli_close($connection);
        throw new Exception("Prepare failed: " . mysqli_error($connection));
    }
    
    // Auto-detect parameter types
    $types = '';
    foreach ($params as $param) {
        if (is_int($param)) {
            $types .= 'i';
        } elseif (is_float($param)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        throw new Exception("Execute failed: " . $error);
    }
    
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    
    return $affected_rows;
}

/**
 * Delete records and return affected row count using MySQLi
 * 
 * @param string $sql SQL delete query
 * @param array $params Parameters to bind
 * @return int Number of affected rows
 */
function deleteRecord($sql, $params = []) {
    $connection = getDatabaseConnection();
    
    if (empty($params)) {
        $result = mysqli_query($connection, $sql);
        if (!$result) {
            $error = mysqli_error($connection);
            mysqli_close($connection);
            throw new Exception("Delete failed: " . $error);
        }
        $affected_rows = mysqli_affected_rows($connection);
        mysqli_close($connection);
        return $affected_rows;
    }
    
    $stmt = mysqli_prepare($connection, $sql);
    if (!$stmt) {
        mysqli_close($connection);
        throw new Exception("Prepare failed: " . mysqli_error($connection));
    }
    
    // Auto-detect parameter types
    $types = '';
    foreach ($params as $param) {
        if (is_int($param)) {
            $types .= 'i';
        } elseif (is_float($param)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
        throw new Exception("Execute failed: " . $error);
    }
    
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    
    return $affected_rows;
}

/**
 * Begin a database transaction using MySQLi
 * 
 * @return mysqli Database connection with active transaction
 */
function beginTransaction() {
    $connection = getDatabaseConnection();
    mysqli_autocommit($connection, FALSE);
    return $connection;
}

/**
 * Sanitize input for database queries
 * 
 * @param string $input User input to sanitize
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 * 
 * @param string $email Email to validate
 * @return bool True if valid email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Hash password securely
 * 
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 * 
 * @param string $password Plain text password
 * @param string $hash Stored password hash
 * @return bool True if password matches
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

/**
 * Start secure session
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Check if user has specific role
 * 
 * @param string $required_role Role to check for
 * @return bool True if user has the required role
 */
function hasRole($required_role) {
    return isLoggedIn() && $_SESSION['user_role'] === $required_role;
}

/**
 * Redirect to login page if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Require specific role or redirect
 * 
 * @param string $required_role Required role
 * @param string $redirect_url Where to redirect if role check fails
 */
function requireRole($required_role, $redirect_url = 'index.php') {
    requireLogin();
    if (!hasRole($required_role)) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Log user activity using MySQLi
 * 
 * @param string $action Action performed
 * @param string $table_name Table affected (optional)
 * @param int $record_id Record ID affected (optional)
 * @param array $old_values Old values (optional)
 * @param array $new_values New values (optional)
 */
function logActivity($action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
    // Activity logging disabled (no activity_logs table). No-op for simplicity.
    return;
}
?>
