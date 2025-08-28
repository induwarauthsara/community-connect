<?php
require_once 'config/database.php';
require_once 'includes/common.php';

startSecureSession();
requireRole('organization');

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get organization data
$organization = getSingleRecord("SELECT * FROM organizations WHERE created_by = ?", [$user_id]);
$org_id = $organization ? (int)$organization['org_id'] : null;

// Handle organization create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_org') {
    if (($_POST['confirmed'] ?? 'false') !== 'true') {
        die('Error: Action requires confirmation');
    }
    
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description'] ?? '');
    $contact_email = sanitizeInput($_POST['contact_email'] ?? '');
    $contact_phone = sanitizeInput($_POST['contact_phone'] ?? '');
    $website = sanitizeInput($_POST['website'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $mission = sanitizeInput($_POST['mission'] ?? '');
    $established_year = (int)($_POST['established_year'] ?? 0);
    
    // Validation
    $required_fields = ['name' => $name];
    $missing = validateRequiredFields($required_fields);
    
    if (!empty($missing)) {
        $error = 'Organization name is required.';
    } elseif ($contact_email && !isValidEmail($contact_email)) {
        $error = 'Please enter a valid contact email address.';
    } elseif ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid website URL.';
    } elseif ($established_year && ($established_year < 1800 || $established_year > date('Y'))) {
        $error = 'Please enter a valid established year.';
    } else {
        try {
            $org_id = insertRecord(
                "INSERT INTO organizations (name, description, contact_email, contact_phone, website, address, mission, established_year, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$name, $description, $contact_email, $contact_phone, $website, $address, $mission, $established_year ?: null, $user_id]
            );
            $success = 'Organization created successfully! You can now start creating volunteer projects.';
            $organization = getSingleRecord("SELECT * FROM organizations WHERE org_id = ?", [$org_id]);
        } catch (Exception $e) {
            $error = 'Failed to create organization. Please try again.';
        }
    }
}
// Handle organization update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_org') {
    if (($_POST['confirmed'] ?? 'false') !== 'true') {
        die('Error: Action requires confirmation');
    }
    
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description'] ?? '');
    $contact_email = sanitizeInput($_POST['contact_email'] ?? '');
    $contact_phone = sanitizeInput($_POST['contact_phone'] ?? '');
    $website = sanitizeInput($_POST['website'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $mission = sanitizeInput($_POST['mission'] ?? '');
    $established_year = (int)($_POST['established_year'] ?? 0);
    
    // Validation
    $required_fields = ['name' => $name];
    $missing = validateRequiredFields($required_fields);
    
    if (!empty($missing)) {
        $error = 'Organization name is required.';
    } elseif ($contact_email && !isValidEmail($contact_email)) {
        $error = 'Please enter a valid contact email address.';
    } elseif ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid website URL.';
    } elseif ($established_year && ($established_year < 1800 || $established_year > date('Y'))) {
        $error = 'Please enter a valid established year.';
    } else {
        try {
            updateRecord(
                "UPDATE organizations SET name = ?, description = ?, contact_email = ?, contact_phone = ?, website = ?, address = ?, mission = ?, established_year = ? WHERE org_id = ?",
                [$name, $description, $contact_email, $contact_phone, $website, $address, $mission, $established_year ?: null, $org_id]
            );
            $success = 'Organization information updated successfully!';
            $organization = getSingleRecord("SELECT * FROM organizations WHERE org_id = ?", [$org_id]);
        } catch (Exception $e) {
            $error = 'Failed to update organization. Please try again.';
        }
    }
}

// Handle project create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_project') {
    if (($_POST['confirmed'] ?? 'false') !== 'true') {
        die('Error: Action requires confirmation');
    }
    
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $start_date = sanitizeInput($_POST['start_date'] ?? '');
    $end_date = sanitizeInput($_POST['end_date'] ?? '');
    $max_volunteers = (int)($_POST['max_volunteers'] ?? 0);
    $required_skills = sanitizeInput($_POST['required_skills'] ?? '');
    
    // Validation
    $required_fields = ['title' => $title];
    $missing = validateRequiredFields($required_fields);
    
    if (!empty($missing)) {
        $error = 'Project title is required.';
    } elseif ($start_date && !isValidDate($start_date)) {
        $error = 'Please enter a valid start date.';
    } elseif ($end_date && !isValidDate($end_date)) {
        $error = 'Please enter a valid end date.';
    } elseif (!isValidDateRange($start_date, $end_date)) {
        $error = 'End date must be after start date.';
    } elseif ($max_volunteers < 0) {
        $error = 'Maximum volunteers must be a positive number.';
    } else {
        try {
            insertRecord(
                "INSERT INTO projects (title, description, location, start_date, end_date, max_volunteers, required_skills, created_by, organization_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
                [$title, $description, $location, $start_date ?: null, $end_date ?: null, $max_volunteers ?: null, $required_skills, $user_id, $org_id]
            );
            $success = 'Project created successfully! It will be visible to volunteers after admin approval.';
        } catch (Exception $e) {
            $error = 'Failed to create project. Please try again.';
        }
    }
}

// Handle project update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_project') {
    if (($_POST['confirmed'] ?? 'false') !== 'true') {
        die('Error: Action requires confirmation');
    }
    
    $project_id = (int)$_POST['project_id'];
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $start_date = sanitizeInput($_POST['start_date'] ?? '');
    $end_date = sanitizeInput($_POST['end_date'] ?? '');
    $max_volunteers = (int)($_POST['max_volunteers'] ?? 0);
    $required_skills = sanitizeInput($_POST['required_skills'] ?? '');
    
    // Validation  
    $required_fields = ['title' => $title];
    $missing = validateRequiredFields($required_fields);
    
    if (!empty($missing)) {
        $error = 'Project title is required.';
    } elseif ($start_date && !isValidDate($start_date)) {
        $error = 'Please enter a valid start date.';
    } elseif ($end_date && !isValidDate($end_date)) {
        $error = 'Please enter a valid end date.';
    } elseif (!isValidDateRange($start_date, $end_date)) {
        $error = 'End date must be after start date.';
    } elseif ($max_volunteers < 0) {
        $error = 'Maximum volunteers must be a positive number.';
    } else {
        try {
            updateRecord(
                "UPDATE projects SET title = ?, description = ?, location = ?, start_date = ?, end_date = ?, max_volunteers = ?, required_skills = ? WHERE project_id = ? AND created_by = ?",
                [$title, $description, $location, $start_date ?: null, $end_date ?: null, $max_volunteers ?: null, $required_skills, $project_id, $user_id]
            );
            $success = 'Project updated successfully!';
        } catch (Exception $e) {
            $error = 'Failed to update project. Please try again.';
        }
    }
}

// Handle project delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_project') {
    if (($_POST['confirmed'] ?? 'false') !== 'true') {
        die('Error: Action requires confirmation');
    }
    
    $project_id = (int)$_POST['project_id'];
    try {
        // First remove volunteer assignments
        deleteRecord("DELETE FROM volunteer_projects WHERE project_id = ?", [$project_id]);
        // Then delete the project
        deleteRecord("DELETE FROM projects WHERE project_id = ? AND created_by = ?", [$project_id, $user_id]);
        $success = 'Project and all volunteer assignments deleted successfully!';
    } catch (Exception $e) {
        $error = 'Failed to delete project. Please try again.';
    }
}

// Get organization projects with volunteer details
$projects = [];
$volunteers = [];
if ($org_id) {
    $projects = getMultipleRecords("
        SELECT p.*, 
               (SELECT COUNT(*) FROM volunteer_projects vp WHERE vp.project_id = p.project_id) as volunteer_count
        FROM projects p
        WHERE p.organization_id = ?
        ORDER BY p.created_at DESC
    ", [$org_id]);
    
    // Get volunteers in this organization
    $volunteers = getMultipleRecords("
        SELECT u.*, 
               (SELECT COUNT(*) FROM volunteer_projects vp JOIN projects p ON vp.project_id = p.project_id WHERE vp.volunteer_id = u.user_id AND p.organization_id = ?) as project_count
        FROM users u
        WHERE u.organization_id = ? AND u.role = 'volunteer'
        ORDER BY u.name
    ", [$org_id, $org_id]);
}

$page_title = 'Organization Dashboard - Enhanced';
include 'includes/header.php';
?>

<div class="container">
    <div class="dashboard-header">
        <div class="header-content">
            <h1><i class="fas fa-building"></i> Organization Dashboard - Enhanced</h1>
            <div class="header-actions">
                <span class="user-info">Welcome, <strong><?= htmlspecialchars($user['name']) ?></strong></span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-grid">
        <!-- Organization Information Card -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-info-circle"></i> Organization Information</h2>
                <button type="button" onclick="toggleEdit('org-edit')" class="btn-secondary">
                    <i class="fas fa-edit"></i> Edit Information
                </button>
            </div>
            <div class="card-content">
                <?php if ($organization): ?>
                    <div id="org-display" class="info-grid">
                        <div class="info-item">
                            <label>Organization Name:</label>
                            <span><?= htmlspecialchars($organization['name']) ?></span>
                        </div>
                        <div class="info-item">
                            <label>Description:</label>
                            <span><?= htmlspecialchars($organization['description'] ?: 'Not provided') ?></span>
                        </div>
                        <div class="info-item">
                            <label>Contact Email:</label>
                            <span><?= htmlspecialchars($organization['contact_email'] ?: 'Not provided') ?></span>
                        </div>
                        <div class="info-item">
                            <label>Contact Phone:</label>
                            <span><?= htmlspecialchars($organization['contact_phone'] ?: 'Not provided') ?></span>
                        </div>
                        <div class="info-item">
                            <label>Website:</label>
                            <span><?= $organization['website'] ? '<a href="' . htmlspecialchars($organization['website']) . '" target="_blank">' . htmlspecialchars($organization['website']) . '</a>' : 'Not provided' ?></span>
                        </div>
                        <div class="info-item">
                            <label>Address:</label>
                            <span><?= htmlspecialchars($organization['address'] ?: 'Not provided') ?></span>
                        </div>
                        <div class="info-item">
                            <label>Mission:</label>
                            <span><?= htmlspecialchars($organization['mission'] ?: 'Not provided') ?></span>
                        </div>
                        <div class="info-item">
                            <label>Established Year:</label>
                            <span><?= $organization['established_year'] ? htmlspecialchars($organization['established_year']) : 'Not provided' ?></span>
                        </div>
                        <div class="info-item">
                            <label>Member Since:</label>
                            <span><?= formatDate($organization['created_at']) ?></span>
                        </div>
                    </div>

                    <div id="org-edit" class="edit-form" style="display: none;">
                        <form method="POST" onsubmit="return confirmUpdate('organization information')">
                            <input type="hidden" name="action" value="update_org">
                            <input type="hidden" name="confirmed" value="true">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="name">Organization Name *:</label>
                                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($organization['name']) ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="contact_email">Contact Email:</label>
                                    <input type="email" id="contact_email" name="contact_email" value="<?= htmlspecialchars($organization['contact_email'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="contact_phone">Contact Phone:</label>
                                    <input type="tel" id="contact_phone" name="contact_phone" value="<?= htmlspecialchars($organization['contact_phone'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="website">Website:</label>
                                    <input type="url" id="website" name="website" value="<?= htmlspecialchars($organization['website'] ?? '') ?>" placeholder="https://">
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="description">Description:</label>
                                    <textarea id="description" name="description" rows="3"><?= htmlspecialchars($organization['description'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="address">Address:</label>
                                    <textarea id="address" name="address" rows="2"><?= htmlspecialchars($organization['address'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="mission">Mission Statement:</label>
                                    <textarea id="mission" name="mission" rows="3"><?= htmlspecialchars($organization['mission'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="established_year">Established Year:</label>
                                    <input type="number" id="established_year" name="established_year" 
                                           value="<?= $organization['established_year'] ?? '' ?>" 
                                           min="1800" max="<?= date('Y') ?>">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                                <button type="button" onclick="toggleEdit('org-edit')" class="btn-secondary">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-info-circle"></i>
                        <p>Organization information not found. Please contact an administrator.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Organization Statistics -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-chart-bar"></i> Organization Statistics</h2>
            </div>
            <div class="card-content">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?= count($projects) ?></div>
                        <div class="stat-label">Total Projects</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= array_sum(array_column($projects, 'volunteer_count')) ?></div>
                        <div class="stat-label">Total Volunteers</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= count(array_filter($projects, fn($p) => $p['status'] === 'approved')) ?></div>
                        <div class="stat-label">Active Projects</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= count($volunteers) ?></div>
                        <div class="stat-label">Organization Members</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Management Section -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-tasks"></i> Project Management</h2>
            <button type="button" onclick="toggleSection('create-project')" class="btn-primary">
                <i class="fas fa-plus"></i> Create New Project
            </button>
        </div>
        <div class="card-content">
            <!-- Create Project Form -->
            <div id="create-project" class="form-section" style="display: none;">
                <h3>Create New Project</h3>
                <form method="POST" onsubmit="return confirmCreate('project')">
                    <input type="hidden" name="action" value="create_project">
                    <input type="hidden" name="confirmed" value="true">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="project_title">Project Title *:</label>
                            <input type="text" id="project_title" name="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="project_location">Location:</label>
                            <input type="text" id="project_location" name="location">
                        </div>
                        
                        <div class="form-group">
                            <label for="start_date">Start Date:</label>
                            <input type="date" id="start_date" name="start_date" min="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date">End Date:</label>
                            <input type="date" id="end_date" name="end_date" min="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="max_volunteers">Maximum Volunteers:</label>
                            <input type="number" id="max_volunteers" name="max_volunteers" min="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="required_skills">Required Skills:</label>
                            <input type="text" id="required_skills" name="required_skills" placeholder="e.g., Communication, Teamwork, Computer Skills">
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="project_description">Description:</label>
                            <textarea id="project_description" name="description" rows="4" placeholder="Describe the project goals, activities, and requirements..."></textarea>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-plus"></i> Create Project
                        </button>
                        <button type="button" onclick="toggleSection('create-project')" class="btn-secondary">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Projects List -->
            <div class="projects-section">
                <?php if (!empty($projects)): ?>
                    <div class="projects-grid">
                        <?php foreach ($projects as $project): ?>
                            <div class="project-card">
                                <div class="project-header">
                                    <h3><?= htmlspecialchars($project['title']) ?></h3>
                                    <div class="project-actions">
                                        <?= getStatusBadge($project['status']) ?>
                                        <button type="button" onclick="toggleEdit('project-<?= $project['project_id'] ?>')" class="btn-icon" title="Edit Project">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirmDelete('project')">
                                            <input type="hidden" name="action" value="delete_project">
                                            <input type="hidden" name="project_id" value="<?= $project['project_id'] ?>">
                                            <input type="hidden" name="confirmed" value="true">
                                            <button type="submit" class="btn-icon btn-danger" title="Delete Project">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                <div class="project-content">
                                    <div id="project-display-<?= $project['project_id'] ?>">
                                        <div class="project-info">
                                            <?php if ($project['description']): ?>
                                                <p class="project-description"><?= htmlspecialchars($project['description']) ?></p>
                                            <?php endif; ?>
                                            
                                            <div class="project-details">
                                                <?php if ($project['location']): ?>
                                                    <span class="detail-item">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                        <?= htmlspecialchars($project['location']) ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if ($project['start_date']): ?>
                                                    <span class="detail-item">
                                                        <i class="fas fa-calendar-alt"></i>
                                                        <?= formatDate($project['start_date']) ?>
                                                        <?php if ($project['end_date']): ?>
                                                            - <?= formatDate($project['end_date']) ?>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if ($project['max_volunteers']): ?>
                                                    <span class="detail-item">
                                                        <i class="fas fa-users"></i>
                                                        <?= $project['volunteer_count'] ?>/<?= $project['max_volunteers'] ?> volunteers
                                                    </span>
                                                <?php else: ?>
                                                    <span class="detail-item">
                                                        <i class="fas fa-users"></i>
                                                        <?= $project['volunteer_count'] ?> volunteers
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if ($project['required_skills']): ?>
                                                    <span class="detail-item">
                                                        <i class="fas fa-tools"></i>
                                                        <?= htmlspecialchars($project['required_skills']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Edit Form -->
                                    <div id="project-<?= $project['project_id'] ?>" class="edit-form" style="display: none;">
                                        <form method="POST" onsubmit="return confirmUpdate('project')">
                                            <input type="hidden" name="action" value="update_project">
                                            <input type="hidden" name="project_id" value="<?= $project['project_id'] ?>">
                                            <input type="hidden" name="confirmed" value="true">
                                            
                                            <div class="form-grid">
                                                <div class="form-group">
                                                    <label>Project Title *:</label>
                                                    <input type="text" name="title" value="<?= htmlspecialchars($project['title']) ?>" required>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Location:</label>
                                                    <input type="text" name="location" value="<?= htmlspecialchars($project['location'] ?? '') ?>">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Start Date:</label>
                                                    <input type="date" name="start_date" value="<?= $project['start_date'] ?>" min="<?= date('Y-m-d') ?>">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>End Date:</label>
                                                    <input type="date" name="end_date" value="<?= $project['end_date'] ?>" min="<?= date('Y-m-d') ?>">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Maximum Volunteers:</label>
                                                    <input type="number" name="max_volunteers" value="<?= $project['max_volunteers'] ?>" min="1">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Required Skills:</label>
                                                    <input type="text" name="required_skills" value="<?= htmlspecialchars($project['required_skills'] ?? '') ?>">
                                                </div>
                                                
                                                <div class="form-group full-width">
                                                    <label>Description:</label>
                                                    <textarea name="description" rows="3"><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
                                                </div>
                                            </div>
                                            
                                            <div class="form-actions">
                                                <button type="submit" class="btn-primary">
                                                    <i class="fas fa-save"></i> Save Changes
                                                </button>
                                                <button type="button" onclick="toggleEdit('project-<?= $project['project_id'] ?>')" class="btn-secondary">
                                                    Cancel
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-tasks"></i>
                        <p>No projects created yet. Create your first project to get started!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Volunteers Section -->
    <?php if (!empty($volunteers)): ?>
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-users"></i> Organization Volunteers</h2>
            </div>
            <div class="card-content">
                <div class="volunteers-grid">
                    <?php foreach ($volunteers as $volunteer): ?>
                        <div class="volunteer-card">
                            <div class="volunteer-info">
                                <h4><?= htmlspecialchars($volunteer['name']) ?></h4>
                                <p><?= htmlspecialchars($volunteer['email']) ?></p>
                                <?php if ($volunteer['phone']): ?>
                                    <p><i class="fas fa-phone"></i> <?= htmlspecialchars($volunteer['phone']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="volunteer-stats">
                                <span class="stat-badge"><?= $volunteer['project_count'] ?> projects</span>
                                <span class="detail-text">Joined <?= formatDate($volunteer['created_at']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleEdit(elementId) {
    const editForm = document.getElementById(elementId);
    const displayDiv = document.getElementById(elementId.replace('-edit', '-display').replace('project-', 'project-display-'));
    
    if (editForm.style.display === 'none') {
        editForm.style.display = 'block';
        if (displayDiv) displayDiv.style.display = 'none';
    } else {
        editForm.style.display = 'none';
        if (displayDiv) displayDiv.style.display = 'block';
    }
}

function toggleSection(sectionId) {
    const section = document.getElementById(sectionId);
    section.style.display = section.style.display === 'none' ? 'block' : 'none';
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});
</script>

<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 2px solid #e9ecef;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #007bff;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.9rem;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.projects-grid {
    display: grid;
    gap: 20px;
    margin-top: 20px;
}

.project-card {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    background: white;
    transition: all 0.3s ease;
}

.project-card:hover {
    border-color: #007bff;
    box-shadow: 0 4px 12px rgba(0,123,255,0.1);
}

.project-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.project-header h3 {
    margin: 0;
    color: #2c3e50;
    font-size: 1.2rem;
}

.project-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.project-description {
    margin: 10px 0;
    color: #666;
    line-height: 1.5;
}

.project-details {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 15px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9rem;
    color: #666;
}

.detail-item i {
    color: #007bff;
    width: 16px;
}

.volunteers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 15px;
}

.volunteer-card {
    padding: 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    background: white;
    transition: border-color 0.3s ease;
}

.volunteer-card:hover {
    border-color: #007bff;
}

.volunteer-info h4 {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.volunteer-info p {
    margin: 3px 0;
    color: #666;
    font-size: 0.9rem;
}

.volunteer-stats {
    margin-top: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stat-badge {
    background: #007bff;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.detail-text {
    font-size: 0.8rem;
    color: #999;
}

.form-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 2px solid #e9ecef;
}

.form-section h3 {
    margin-top: 0;
    color: #2c3e50;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .project-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .project-details {
        flex-direction: column;
        gap: 8px;
    }
    
    .volunteers-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
