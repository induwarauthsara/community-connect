<?php
require_once 'config/database.php';
require_once 'includes/common.php';

// Ensure user is logged in and is organization
requireRole('organization');
$current_user = getCurrentUser();

$page_title = 'Organization Dashboard - Community Connect';
$error_message = '';
$success_message = '';

$user_id = $_SESSION['user_id'];

// Get organization data - check both created_by and organization_id
$organization_query = "SELECT * FROM organizations WHERE created_by = $user_id OR org_id = (SELECT organization_id FROM users WHERE user_id = $user_id)";
$organization_result = mysqli_query($connection, $organization_query);
$organization = mysqli_fetch_assoc($organization_result);
$org_id = $organization ? (int)$organization['org_id'] : null;

// If no organization exists, we need to create one
$needs_organization_setup = !$organization;

// Handle organization create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_org') {
    $name = mysqli_real_escape_string($connection, trim($_POST['name'] ?? ''));
    $description = mysqli_real_escape_string($connection, trim($_POST['description'] ?? ''));
    $contact_email = mysqli_real_escape_string($connection, trim($_POST['contact_email'] ?? ''));
    $contact_phone = mysqli_real_escape_string($connection, trim($_POST['contact_phone'] ?? ''));
    $website = mysqli_real_escape_string($connection, trim($_POST['website'] ?? ''));
    $address = mysqli_real_escape_string($connection, trim($_POST['address'] ?? ''));
    $mission = mysqli_real_escape_string($connection, trim($_POST['mission'] ?? ''));
    $established_year = !empty($_POST['established_year']) ? (int)$_POST['established_year'] : null;

    if (empty($name)) {
        $error_message = 'Organization name is required.';
    } elseif ($contact_email && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid contact email address.';
    } elseif ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
        $error_message = 'Please enter a valid website URL.';
    } elseif ($established_year && ($established_year < 1800 || $established_year > date('Y'))) {
        $error_message = 'Please enter a valid established year.';
    } else {
        $established_year_value = $established_year ? $established_year : 'NULL';
        $sql = "INSERT INTO organizations (name, description, contact_email, contact_phone, website, address, mission, established_year, created_by) 
                VALUES ('$name', '$description', '$contact_email', '$contact_phone', '$website', '$address', '$mission', $established_year_value, $user_id)";
        
        if (mysqli_query($connection, $sql)) {
            $org_id = mysqli_insert_id($connection);
            
            // Update user's organization_id to link them to this organization
            $update_user_sql = "UPDATE users SET organization_id = $org_id WHERE user_id = $user_id";
            mysqli_query($connection, $update_user_sql);
            
            $success_message = 'Organization created successfully! You can now start creating volunteer projects.';
            $needs_organization_setup = false;
            
            // Refresh organization data
            $organization_result = mysqli_query($connection, "SELECT * FROM organizations WHERE org_id = $org_id");
            $organization = mysqli_fetch_assoc($organization_result);
        } else {
            $error_message = 'Failed to create organization: ' . mysqli_error($connection);
        }
    }
}
// Handle organization update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_org') {
    $name = mysqli_real_escape_string($connection, trim($_POST['name'] ?? ''));
    $description = mysqli_real_escape_string($connection, trim($_POST['description'] ?? ''));
    $contact_email = mysqli_real_escape_string($connection, trim($_POST['contact_email'] ?? ''));
    $contact_phone = mysqli_real_escape_string($connection, trim($_POST['contact_phone'] ?? ''));
    $website = mysqli_real_escape_string($connection, trim($_POST['website'] ?? ''));
    $address = mysqli_real_escape_string($connection, trim($_POST['address'] ?? ''));
    $mission = mysqli_real_escape_string($connection, trim($_POST['mission'] ?? ''));
    $established_year = !empty($_POST['established_year']) ? (int)$_POST['established_year'] : null;

    if (empty($name)) {
        $error_message = 'Organization name is required.';
    } elseif ($contact_email && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid contact email address.';
    } elseif ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
        $error_message = 'Please enter a valid website URL.';
    } elseif ($established_year && ($established_year < 1800 || $established_year > date('Y'))) {
        $error_message = 'Please enter a valid established year.';
    } else {
        $established_year_value = $established_year ? $established_year : 'NULL';
        $sql = "UPDATE organizations SET name = '$name', description = '$description', contact_email = '$contact_email', 
                contact_phone = '$contact_phone', website = '$website', address = '$address', mission = '$mission', 
                established_year = $established_year_value WHERE org_id = $org_id";
        
        if (mysqli_query($connection, $sql)) {
            $success_message = 'Organization information updated successfully!';
            $needs_organization_setup = false;
            
            // Refresh organization data
            $organization_result = mysqli_query($connection, "SELECT * FROM organizations WHERE org_id = $org_id");
            $organization = mysqli_fetch_assoc($organization_result);
        } else {
            $error_message = 'Failed to update organization: ' . mysqli_error($connection);
        }
    }
}

// Handle project create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_project') {
    $title = mysqli_real_escape_string($connection, trim($_POST['title'] ?? ''));
    $description = mysqli_real_escape_string($connection, trim($_POST['description'] ?? ''));
    $location = mysqli_real_escape_string($connection, trim($_POST['location'] ?? ''));
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');
    $capacity = !empty($_POST['capacity']) ? (int)$_POST['capacity'] : null;
    $skills_needed = mysqli_real_escape_string($connection, trim($_POST['skills_needed'] ?? ''));
    $requirements = mysqli_real_escape_string($connection, trim($_POST['requirements'] ?? ''));
    $priority = mysqli_real_escape_string($connection, trim($_POST['priority'] ?? 'medium'));
    
    if (empty($title)) {
        $error_message = 'Project title is required.';
    } elseif (!$org_id) {
        $error_message = 'Please complete your organization setup before creating projects.';
    } else {
        $start_date_value = !empty($start_date) ? "'$start_date'" : 'NULL';
        $end_date_value = !empty($end_date) ? "'$end_date'" : 'NULL';
        $start_time_value = !empty($start_time) ? "'$start_time'" : 'NULL';
        $end_time_value = !empty($end_time) ? "'$end_time'" : 'NULL';
        $capacity_value = $capacity ? $capacity : 'NULL';
        
        $sql = "INSERT INTO projects (title, description, location, start_date, end_date, start_time, end_time, 
                capacity, skills_needed, requirements, priority, organization_id, created_by, status) 
                VALUES ('$title', '$description', '$location', $start_date_value, $end_date_value, 
                $start_time_value, $end_time_value, $capacity_value, '$skills_needed', '$requirements', 
                '$priority', $org_id, {$current_user['user_id']}, 'approved')";
        
        if (mysqli_query($connection, $sql)) {
            $success_message = 'Project created successfully! It is now visible to volunteers.';
        } else {
            $error_message = 'Error creating project: ' . mysqli_error($connection);
        }
    }
}

// Handle project update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_project') {
    $project_id = (int)$_POST['project_id'];
    $title = mysqli_real_escape_string($connection, trim($_POST['title'] ?? ''));
    $description = mysqli_real_escape_string($connection, trim($_POST['description'] ?? ''));
    $location = mysqli_real_escape_string($connection, trim($_POST['location'] ?? ''));
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');
    $capacity = !empty($_POST['capacity']) ? (int)$_POST['capacity'] : null;
    $skills_needed = mysqli_real_escape_string($connection, trim($_POST['skills_needed'] ?? ''));
    $requirements = mysqli_real_escape_string($connection, trim($_POST['requirements'] ?? ''));
    $priority = mysqli_real_escape_string($connection, trim($_POST['priority'] ?? 'medium'));

    if (empty($title)) {
        $error_message = 'Project title is required.';
    } else {
        $start_date_value = !empty($start_date) ? "'$start_date'" : 'NULL';
        $end_date_value = !empty($end_date) ? "'$end_date'" : 'NULL';
        $start_time_value = !empty($start_time) ? "'$start_time'" : 'NULL';
        $end_time_value = !empty($end_time) ? "'$end_time'" : 'NULL';
        $capacity_value = $capacity ? $capacity : 'NULL';
        
        $sql = "UPDATE projects SET title = '$title', description = '$description', location = '$location', 
                start_date = $start_date_value, end_date = $end_date_value, start_time = $start_time_value, 
                end_time = $end_time_value, capacity = $capacity_value, skills_needed = '$skills_needed', 
                requirements = '$requirements', priority = '$priority' 
                WHERE project_id = $project_id AND created_by = {$current_user['user_id']}";
        
        if (mysqli_query($connection, $sql)) {
            $success_message = 'Project updated successfully!';
        } else {
            $error_message = 'Error updating project: ' . mysqli_error($connection);
        }
    }
}

// Handle project delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_project') {
    $project_id = (int)$_POST['project_id'];
    
    // First remove volunteer assignments
    if (mysqli_query($connection, "DELETE FROM volunteer_projects WHERE project_id = $project_id")) {
        // Then delete the project
        if (mysqli_query($connection, "DELETE FROM projects WHERE project_id = $project_id AND created_by = $user_id")) {
            $success_message = 'Project and all volunteer assignments deleted successfully!';
        } else {
            $error_message = 'Failed to delete project. Please try again.';
        }
    } else {
        $error_message = 'Failed to delete project assignments. Please try again.';
    }
}

// Get organization projects with volunteer details
$projects = [];
$volunteers = [];
if ($org_id) {
    $projects_query = "SELECT p.*, 
                      (SELECT COUNT(*) FROM volunteer_projects vp WHERE vp.project_id = p.project_id) as volunteer_count
                      FROM projects p
                      WHERE p.organization_id = $org_id
                      ORDER BY p.created_at DESC";
    $projects_result = mysqli_query($connection, $projects_query);
    if ($projects_result) {
        while ($project = mysqli_fetch_assoc($projects_result)) {
            $projects[] = $project;
        }
    }

    // Get volunteers in this organization
    $volunteers_query = "SELECT u.*, 
                        (SELECT COUNT(*) FROM volunteer_projects vp JOIN projects p ON vp.project_id = p.project_id 
                         WHERE vp.volunteer_id = u.user_id AND p.organization_id = $org_id) as project_count
                        FROM users u
                        WHERE u.organization_id = $org_id AND u.role = 'volunteer'
                        ORDER BY u.name";
    $volunteers_result = mysqli_query($connection, $volunteers_query);
    if ($volunteers_result) {
        while ($volunteer = mysqli_fetch_assoc($volunteers_result)) {
            $volunteers[] = $volunteer;
        }
    }
}

$page_title = 'Organization Dashboard - Community Connect';
include 'includes/header.php';
?>

<div class="container">
    <div class="dashboard-header">
        <div class="header-content">
            <h1><i class="fas fa-building"></i> Organization Dashboard</h1>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if ($needs_organization_setup): ?>
        <!-- Organization Setup Required -->
        <div class="card setup-required">
            <div class="card-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Organization Setup Required</h2>
            </div>
            <div class="card-content">
                <div class="setup-message">
                    <p><strong>Welcome to Community Connect!</strong></p>
                    <p>To start creating volunteer projects, you need to set up your organization profile first. This information will be visible to volunteers when they browse your projects.</p>
                </div>
                
                <form method="POST" onsubmit="return confirmCreate('organization')">
                    <input type="hidden" name="action" value="create_org">

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="setup_name">Organization Name *:</label>
                            <input type="text" id="setup_name" name="name" required placeholder="Enter your organization name">
                        </div>

                        <div class="form-group">
                            <label for="setup_contact_email">Contact Email:</label>
                            <input type="email" id="setup_contact_email" name="contact_email" value="<?= htmlspecialchars($current_user['email']) ?>" placeholder="Contact email for volunteers">
                        </div>

                        <div class="form-group">
                            <label for="setup_contact_phone">Contact Phone:</label>
                            <input type="tel" id="setup_contact_phone" name="contact_phone" placeholder="Phone number for contact">
                        </div>

                        <div class="form-group">
                            <label for="setup_website">Website:</label>
                            <input type="url" id="setup_website" name="website" placeholder="https://yourorganization.com">
                        </div>

                        <div class="form-group">
                            <label for="setup_established_year">Established Year:</label>
                            <input type="number" id="setup_established_year" name="established_year"
                                min="1800" max="<?= date('Y') ?>" placeholder="<?= date('Y') ?>">
                        </div>

                        <div class="form-group full-width">
                            <label for="setup_description">Description:</label>
                            <textarea id="setup_description" name="description" rows="3" placeholder="Brief description of your organization..."></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label for="setup_address">Address:</label>
                            <textarea id="setup_address" name="address" rows="2" placeholder="Organization address..."></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label for="setup_mission">Mission Statement:</label>
                            <textarea id="setup_mission" name="mission" rows="3" placeholder="Your organization's mission and goals..."></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary btn-large">
                            <i class="fas fa-rocket"></i> Complete Organization Setup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>

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
                            <label for="start_time">Start Time:</label>
                            <input type="time" id="start_time" name="start_time">
                        </div>

                        <div class="form-group">
                            <label for="end_time">End Time:</label>
                            <input type="time" id="end_time" name="end_time">
                        </div>

                        <div class="form-group">
                            <label for="capacity">Maximum Volunteers:</label>
                            <input type="number" id="capacity" name="capacity" min="1">
                        </div>

                        <div class="form-group">
                            <label for="skills_needed">Required Skills:</label>
                            <input type="text" id="skills_needed" name="skills_needed" placeholder="e.g., Communication, Teamwork, Computer Skills">
                        </div>

                        <div class="form-group">
                            <label for="priority">Priority:</label>
                            <select id="priority" name="priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="project_description">Description:</label>
                            <textarea id="project_description" name="description" rows="4" placeholder="Describe the project goals, activities, and requirements..."></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label for="requirements">Requirements:</label>
                            <textarea id="requirements" name="requirements" rows="3" placeholder="Any specific requirements or qualifications needed..."></textarea>
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
                                            Edit
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirmDelete('project')">
                                            <input type="hidden" name="action" value="delete_project"> 
                                            <input type="hidden" name="project_id" value="<?= $project['project_id'] ?>"> 
                                            <button type="submit" class="btn-icon btn-danger" title="Delete Project">
                                                Delete
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
                                                        <?php if ($project['start_time'] || $project['end_time']): ?>
                                                            <br>
                                                            <?php if ($project['start_time']): ?>
                                                                <?= date('g:i A', strtotime($project['start_time'])) ?>
                                                            <?php endif; ?>
                                                            <?php if ($project['end_time']): ?>
                                                                - <?= date('g:i A', strtotime($project['end_time'])) ?>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($project['capacity']): ?>
                                                    <span class="detail-item">
                                                        <i class="fas fa-users"></i>
                                                        <?= $project['volunteer_count'] ?>/<?= $project['capacity'] ?> volunteers
                                                    </span>
                                                <?php else: ?>
                                                    <span class="detail-item">
                                                        <i class="fas fa-users"></i>
                                                        <?= $project['volunteer_count'] ?> volunteers
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($project['skills_needed']): ?>
                                                    <span class="detail-item">
                                                        <i class="fas fa-tools"></i>
                                                        <?= htmlspecialchars($project['skills_needed']) ?>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($project['priority']): ?>
                                                    <span class="detail-item">
                                                        <i class="fas fa-flag"></i>
                                                        Priority: <?= ucfirst(htmlspecialchars($project['priority'])) ?>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($project['requirements']): ?>
                                                    <span class="detail-item">
                                                        <i class="fas fa-list-ul"></i>
                                                        Requirements: <?= htmlspecialchars($project['requirements']) ?>
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
                                                    <label>Start Time:</label>
                                                    <input type="time" name="start_time" value="<?= $project['start_time'] ?? '' ?>">
                                                </div>

                                                <div class="form-group">
                                                    <label>End Time:</label>
                                                    <input type="time" name="end_time" value="<?= $project['end_time'] ?? '' ?>">
                                                </div>

                                                <div class="form-group">
                                                    <label>Maximum Volunteers:</label>
                                                    <input type="number" name="capacity" value="<?= $project['capacity'] ?? '' ?>" min="1">
                                                </div>

                                                <div class="form-group">
                                                    <label>Required Skills:</label>
                                                    <input type="text" name="skills_needed" value="<?= htmlspecialchars($project['skills_needed'] ?? '') ?>">
                                                </div>

                                                <div class="form-group">
                                                    <label>Priority:</label>
                                                    <select name="priority">
                                                        <option value="low" <?= ($project['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
                                                        <option value="medium" <?= ($project['priority'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Medium</option>
                                                        <option value="high" <?= ($project['priority'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
                                                    </select>
                                                </div>

                                                <div class="form-group full-width">
                                                    <label>Description:</label>
                                                    <textarea name="description" rows="3"><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
                                                </div>

                                                <div class="form-group full-width">
                                                    <label>Requirements:</label>
                                                    <textarea name="requirements" rows="3"><?= htmlspecialchars($project['requirements'] ?? '') ?></textarea>
                                                </div>
                                            </div>

                                            <div class="form-actions">
                                                <button type="submit" class="btn-primary">
                                                    <i class="fas fa-save"></i> Save Changes
                                                </button>
                                                <button type="button" onclick="toggleEdit('project-<?= $project['project_id'] ?>')" class="btn-secondary">Cancel</button>
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
    
    <?php endif; // End of organization setup check ?>
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

    function confirmUpdate(itemType) {
        return confirm(`Are you sure you want to update this ${itemType}?`);
    }

    function confirmDelete(itemType) {
        return confirm(`Are you sure you want to delete this ${itemType}? This action cannot be undone.`);
    }

    function confirmCreate(itemType) {
        return confirm(`Are you sure you want to create this ${itemType}?`);
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

<link rel="stylesheet" href="assets/css/organization_dashboard.css">

<?php include 'includes/footer.php'; ?>