<?php
// Guest project submission -> insert into projects as 'pending'
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    // Frontend must pass confirmation
    if (!isset($_POST['confirmed']) || $_POST['confirmed'] !== 'true') {
        echo json_encode(['success' => false, 'message' => 'Action requires confirmation']);
        exit;
    }

    $name = sanitizeInput($_POST['suggester_name'] ?? '');
    $email = sanitizeInput($_POST['suggester_email'] ?? '');
    $title = sanitizeInput($_POST['project_title'] ?? '');
    $description = sanitizeInput($_POST['project_description'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');

    if (!$name || !$email || !$title || !$description) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    if (!isValidEmail($email)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    // Insert into projects as pending; created_by NULL for guest
    $sql = "INSERT INTO projects (
                title, description, location, status,
                submitted_by_name, submitted_by_email
            ) VALUES (?, ?, ?, 'pending', ?, ?)";
    $project_id = insertRecord($sql, [$title, $description, $location, $name, $email]);

    // Log activity
    logActivity('guest_submitted_project', 'projects', $project_id, null, [
        'title' => $title,
        'submitted_by_email' => $email
    ]);

    echo json_encode(['success' => true, 'project_id' => $project_id]);
} catch (Exception $e) {
    error_log('Submit project error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
