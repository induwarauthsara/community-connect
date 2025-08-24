<?php
/**
 * Common Helper Functions - Community Connect
 * Shared utilities to avoid code repetition
 */

/**
 * Format date for display
 * @param string $date Date string
 * @return string Formatted date
 */
function formatDate($date) {
    if (!$date) return 'Not specified';
    return date('M j, Y', strtotime($date));
}

/**
 * Format date and time for display
 * @param string $datetime DateTime string
 * @return string Formatted datetime
 */
function formatDateTime($datetime) {
    if (!$datetime) return 'Not specified';
    return date('M j, Y \a\t g:i A', strtotime($datetime));
}

/**
 * Get status badge HTML
 * @param string $status Status value
 * @return string HTML for status badge
 */
function getStatusBadge($status) {
    $class = 'status-badge';
    switch (strtolower($status)) {
        case 'approved':
            $class .= ' status-success';
            break;
        case 'pending':
            $class .= ' status-warning';
            break;
        case 'rejected':
            $class .= ' status-danger';
            break;
        case 'registered':
            $class .= ' status-info';
            break;
        default:
            $class .= ' status-default';
    }
    
    return "<span class=\"{$class}\">" . htmlspecialchars(ucfirst($status)) . "</span>";
}

/**
 * Validate date format and logical date constraints
 * @param string $date Date string
 * @return bool True if valid
 */
function isValidDate($date) {
    if (empty($date)) return true; // Optional dates are valid if empty
    
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Check if end date is after start date
 * @param string $start_date Start date
 * @param string $end_date End date
 * @return bool True if valid or either date is empty
 */
function isValidDateRange($start_date, $end_date) {
    if (empty($start_date) || empty($end_date)) return true;
    return strtotime($end_date) >= strtotime($start_date);
}

/**
 * Truncate text to specified length
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @return string Truncated text
 */
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length - 3) . '...';
}

/**
 * Generate success message HTML
 * @param string $message Success message
 * @return string HTML for success message
 */
function showSuccess($message) {
    return "<div class=\"success\">" . htmlspecialchars($message) . "</div>";
}

/**
 * Generate error message HTML
 * @param string $message Error message
 * @return string HTML for error message
 */
function showError($message) {
    return "<div class=\"error\">" . htmlspecialchars($message) . "</div>";
}

/**
 * Check if user can edit project (organization role and owns the project)
 * @param int $project_user_id Project creator user ID
 * @param int $current_user_id Current user ID
 * @param string $user_role Current user role
 * @return bool True if can edit
 */
function canEditProject($project_user_id, $current_user_id, $user_role) {
    return $user_role === 'organization' && (int)$project_user_id === (int)$current_user_id;
}

/**
 * Validate required fields
 * @param array $fields Associative array of field_name => value
 * @return array Array of missing field names
 */
function validateRequiredFields($fields) {
    $missing = [];
    foreach ($fields as $name => $value) {
        if (empty(trim($value))) {
            $missing[] = $name;
        }
    }
    return $missing;
}

/**
 * Generate unique volunteer registration code
 * @return string 8-character alphanumeric code
 */
function generateRegistrationCode() {
    return strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
}
?>
