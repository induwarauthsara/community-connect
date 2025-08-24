<?php
/**
 * Community Connect - Common Utility Functions
 * Shared utility functions used across the application
 */

/**
 * Format date for display
 * @param string $date Date string
 * @param string $format Output format (default: 'M j, Y')
 * @return string Formatted date
 */
function formatDate($date, $format = 'M j, Y') {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : 'Invalid Date';
}

/**
 * Format time for display
 * @param string $time Time string
 * @return string Formatted time
 */
function formatTime($time) {
    if (empty($time) || $time === '00:00:00') {
        return 'N/A';
    }
    
    return date('g:i A', strtotime($time));
}

/**
 * Truncate text to specified length
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix for truncated text
 * @return string Truncated text
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length - strlen($suffix)) . $suffix;
}

/**
 * Generate project status badge HTML
 * @param string $status Project status
 * @return string HTML badge
 */
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span style="background: #ffc107; color: #212529; padding: 4px 8px; border-radius: 3px; font-size: 12px;">Pending</span>',
        'approved' => '<span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px;">Approved</span>',
        'active' => '<span style="background: #007bff; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px;">Active</span>',
        'completed' => '<span style="background: #6c757d; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px;">Completed</span>',
        'cancelled' => '<span style="background: #dc3545; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px;">Cancelled</span>'
    ];
    
    return $badges[$status] ?? '<span style="background: #6c757d; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px;">Unknown</span>';
}

/**
 * Generate role badge HTML
 * @param string $role User role
 * @return string HTML badge
 */
function getRoleBadge($role) {
    $badges = [
        'admin' => '<span style="background: #dc3545; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px;">Admin</span>',
        'organization' => '<span style="background: #007bff; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px;">Organization</span>',
        'volunteer' => '<span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px;">Volunteer</span>'
    ];
    
    return $badges[$role] ?? '<span style="background: #6c757d; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px;">Unknown</span>';
}

/**
 * Check if user can join a project
 * @param int $project_id Project ID
 * @param int $user_id User ID
 * @return bool True if user can join
 */
function canJoinProject($project_id, $user_id) {
    // Check if already joined
    $existing = getSingleRecord(
        "SELECT id FROM volunteer_projects WHERE volunteer_id = ? AND project_id = ?",
        [$user_id, $project_id]
    );
    
    if ($existing) {
        return false; // Already joined
    }
    
    // Check project status
    $project = getSingleRecord(
        "SELECT status, capacity, current_volunteers FROM projects WHERE project_id = ?",
        [$project_id]
    );
    
    if (!$project || $project['status'] !== 'approved') {
        return false; // Project not approved
    }
    
    // Check capacity
    if ($project['capacity'] > 0 && $project['current_volunteers'] >= $project['capacity']) {
        return false; // Project full
    }
    
    return true;
}

/**
 * Get user's dashboard URL based on role
 * @param string $role User role
 * @return string Dashboard URL
 */
function getDashboardUrl($role) {
    switch ($role) {
        case 'admin':
            return 'admin_dashboard.php';
        case 'organization':
            return 'organization_dashboard.php';
        case 'volunteer':
            return 'volunteer_dashboard.php';
        default:
            return 'login.html';
    }
}

/**
 * Generate pagination HTML
 * @param int $current_page Current page number
 * @param int $total_pages Total number of pages
 * @param string $base_url Base URL for pagination links
 * @return string Pagination HTML
 */
function generatePagination($current_page, $total_pages, $base_url) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<div class="pagination" style="text-align: center; margin: 20px 0;">';
    
    // Previous button
    if ($current_page > 1) {
        $prev_url = $base_url . '?page=' . ($current_page - 1);
        $html .= '<a href="' . $prev_url . '" class="btn-secondary" style="margin: 0 2px;">Previous</a>';
    }
    
    // Page numbers (show up to 5 pages around current)
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i === $current_page) {
            $html .= '<span style="background: #007bff; color: white; padding: 10px 15px; margin: 0 2px; border-radius: 3px;">' . $i . '</span>';
        } else {
            $page_url = $base_url . '?page=' . $i;
            $html .= '<a href="' . $page_url . '" class="btn-secondary" style="margin: 0 2px;">' . $i . '</a>';
        }
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $next_url = $base_url . '?page=' . ($current_page + 1);
        $html .= '<a href="' . $next_url . '" class="btn-secondary" style="margin: 0 2px;">Next</a>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Validate required fields in POST data
 * @param array $required_fields Array of required field names
 * @param array $post_data POST data (default: $_POST)
 * @return array Array of missing fields
 */
function validateRequiredFields($required_fields, $post_data = null) {
    if ($post_data === null) {
        $post_data = $_POST;
    }
    
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($post_data[$field]) || empty(trim($post_data[$field]))) {
            $missing_fields[] = $field;
        }
    }
    
    return $missing_fields;
}

/**
 * Log user activity (simple file-based logging)
 * @param int $user_id User ID
 * @param string $action Action performed
 * @param string $details Additional details
 */
function logUserActivity($user_id, $action, $details = '') {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $user_id,
        'action' => $action,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    $log_line = json_encode($log_entry) . "\n";
    file_put_contents(__DIR__ . '/../logs/activity.log', $log_line, FILE_APPEND | LOCK_EX);
}

/**
 * Create logs directory if it doesn't exist
 */
function ensureLogsDirectory() {
    $logs_dir = __DIR__ . '/../logs';
    if (!is_dir($logs_dir)) {
        mkdir($logs_dir, 0755, true);
    }
}

// Ensure logs directory exists when this file is included
ensureLogsDirectory();
?>
