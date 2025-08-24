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
    
    // Validation
    $required_fields = ['name' => $name];
    $missing = validateRequiredFields($required_fields);
    
    if (!empty($missing)) {
        $error = 'Organization name is required.';
    } elseif ($contact_email && !isValidEmail($contact_email)) {
        $error = 'Please enter a valid contact email address.';
    } elseif ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid website URL.';
    } else {
        try {
            $org_id = insertRecord(
                "INSERT INTO organizations (name, description, contact_email, contact_phone, website, address, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$name, $description, $contact_email, $contact_phone, $website, $address, $user_id]
            );
            $success = 'Organization created successfully!';
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
    
    // Validation
    $required_fields = ['name' => $name];
    $missing = validateRequiredFields($required_fields);
    
    if (!empty($missing)) {
        $error = 'Organization name is required.';
    } elseif ($contact_email && !isValidEmail($contact_email)) {
        $error = 'Please enter a valid contact email address.';
    } elseif ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid website URL.';
    } else {
        try {
            updateRecord(
                "UPDATE organizations SET name = ?, description = ?, contact_email = ?, contact_phone = ?, website = ?, address = ? WHERE org_id = ?",
                [$name, $description, $contact_email, $contact_phone, $website, $address, $org_id]
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
                "INSERT INTO projects (title, description, location, start_date, end_date, max_volunteers, created_by, organization_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
                [$title, $description, $location, $start_date ?: null, $end_date ?: null, $max_volunteers ?: null, $user_id, $org_id]
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
                "UPDATE projects SET title = ?, description = ?, location = ?, start_date = ?, end_date = ?, max_volunteers = ? WHERE project_id = ? AND created_by = ?",
                [$title, $description, $location, $start_date ?: null, $end_date ?: null, $max_volunteers ?: null, $project_id, $user_id]
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

$page_title = 'Organization Dashboard';
include 'includes/header.php';
?>
<?php if ($success): ?>
    <div class="success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if (!$organization): ?>
<div class="card">
    <div class="section-divider">
        <h2>Create Your Organization</h2>
    </div>
    <p>Welcome! To get started, please create your organization profile:</p>
    
    <form method="POST" onsubmit="return confirmCreate(this)">
        <input type="hidden" name="action" value="create_org">
        <input type="hidden" name="confirmed" value="false">
        
        <div class="info-grid">
            <div class="form-group">
                <label>Organization Name *</label>
                <input type="text" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Contact Email</label>
                <input type="email" name="contact_email">
            </div>
            
            <div class="form-group">
                <label>Contact Phone</label>
                <input type="tel" name="contact_phone">
            </div>
            
            <div class="form-group">
                <label>Website</label>
                <input type="url" name="website" placeholder="https://example.com">
            </div>
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3" placeholder="Describe your organization's mission and activities..."></textarea>
        </div>
        
        <div class="form-group">
            <label>Address</label>
            <textarea name="address" rows="2" placeholder="Organization's physical address..."></textarea>
        </div>
        
        <button type="submit" class="btn">Create Organization</button>
    </form>
</div>
<?php else: ?>

<div class="card">
    <div class="section-divider">
        <h2>Organization: <?php echo htmlspecialchars($organization['name']); ?></h2>
    </div>
    
    <div class="org-info">
        <div class="info-grid">
            <div class="info-item">
                <strong>Total Projects:</strong><br>
                <?php echo count($projects); ?>
            </div>
            <div class="info-item">
                <strong>Total Volunteers:</strong><br>
                <?php echo count($volunteers); ?>
            </div>
            <div class="info-item">
                <strong>Created:</strong><br>
                <?php echo formatDate($organization['created_at']); ?>
            </div>
        </div>
    </div>
    
    <form method="POST" onsubmit="return confirmUpdate(this)">
        <input type="hidden" name="action" value="update_org">
        <input type="hidden" name="confirmed" value="false">
        
        <div class="info-grid">
            <div class="form-group">
                <label>Organization Name *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($organization['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Contact Email</label>
                <input type="email" name="contact_email" value="<?php echo htmlspecialchars($organization['contact_email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Contact Phone</label>
                <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($organization['contact_phone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Website</label>
                <input type="url" name="website" value="<?php echo htmlspecialchars($organization['website'] ?? ''); ?>" placeholder="https://example.com">
            </div>
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3"><?php echo htmlspecialchars($organization['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>Address</label>
            <textarea name="address" rows="2"><?php echo htmlspecialchars($organization['address'] ?? ''); ?></textarea>
        </div>
        
        <button type="submit" class="btn">Update Organization</button>
    </form>
</div>

<?php if (!empty($volunteers)): ?>
<div class="card">
    <div class="section-divider">
        <h3>Your Volunteers (<?php echo count($volunteers); ?>)</h3>
    </div>
    <div class="info-grid">
        <?php foreach ($volunteers as $volunteer): ?>
        <div class="volunteer-info">
            <h4><?php echo htmlspecialchars($volunteer['name']); ?></h4>
            <?php if ($volunteer['email']): ?>
                <p><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($volunteer['email']); ?>"><?php echo htmlspecialchars($volunteer['email']); ?></a></p>
            <?php endif; ?>
            <?php if ($volunteer['phone']): ?>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($volunteer['phone']); ?></p>
            <?php endif; ?>
            <?php if ($volunteer['skills']): ?>
                <p><strong>Skills:</strong> <?php echo htmlspecialchars(truncateText($volunteer['skills'], 80)); ?></p>
            <?php endif; ?>
            <p><strong>Projects Joined:</strong> <?php echo (int)$volunteer['project_count']; ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="section-divider">
        <h3>Create New Project</h3>
    </div>
    <form method="POST" onsubmit="return confirmCreate(this)">
        <input type="hidden" name="action" value="create_project">
        <input type="hidden" name="confirmed" value="false">
        
        <div class="info-grid">
            <div class="form-group">
                <label>Project Title *</label>
                <input type="text" name="title" required>
            </div>
            
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" placeholder="City, State or specific address">
            </div>
            
            <div class="form-group">
                <label>Start Date</label>
                <input type="date" name="start_date" min="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label>End Date</label>
                <input type="date" name="end_date" min="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>Maximum Volunteers</label>
            <input type="number" name="max_volunteers" min="0" placeholder="Leave empty for unlimited">
        </div>
        
        <div class="form-group">
            <label>Project Description</label>
            <textarea name="description" rows="4" placeholder="Describe the volunteer opportunity, requirements, and what volunteers will be doing..."></textarea>
        </div>
        
        <button type="submit" class="btn">Create Project</button>
    </form>
</div>

<div class="card">
    <div class="section-divider">
        <h3>Your Projects (<?php echo count($projects); ?>)</h3>
    </div>
    <?php if (empty($projects)): ?>
        <p>No projects created yet. Create your first project above!</p>
    <?php else: ?>
        <?php foreach ($projects as $project): ?>
        <div class="card project-card">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                <h4><?php echo htmlspecialchars($project['title']); ?></h4>
                <?php echo getStatusBadge($project['status']); ?>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <strong>Current Volunteers:</strong><br>
                    <?php echo (int)$project['volunteer_count']; ?>
                    <?php if ($project['max_volunteers']): ?>
                        / <?php echo (int)$project['max_volunteers']; ?>
                    <?php endif; ?>
                </div>
                <div class="info-item">
                    <strong>Created:</strong><br>
                    <?php echo formatDate($project['created_at']); ?>
                </div>
                <div class="info-item">
                    <strong>Duration:</strong><br>
                    <?php echo formatDate($project['start_date']); ?>
                    <?php if ($project['end_date']): ?>
                        to <?php echo formatDate($project['end_date']); ?>
                    <?php endif; ?>
                </div>
                <?php if ($project['location']): ?>
                <div class="info-item">
                    <strong>Location:</strong><br>
                    <?php echo htmlspecialchars($project['location']); ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($project['description']): ?>
                <p><strong>Description:</strong> <?php echo htmlspecialchars(truncateText($project['description'], 200)); ?></p>
            <?php endif; ?>
            
            <div class="action-buttons">
                <button type="button" class="btn" onclick="toggleEditForm(<?php echo $project['project_id']; ?>)">Edit Project</button>
                <form method="POST" class="form-inline" onsubmit="return confirmAction('delete this project and all volunteer assignments', this)">
                    <input type="hidden" name="action" value="delete_project">
                    <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                    <input type="hidden" name="confirmed" value="false">
                    <button type="submit" class="btn btn-danger">Delete Project</button>
                </form>
            </div>
            
            <!-- Edit Form (initially hidden) -->
            <div id="edit-form-<?php echo $project['project_id']; ?>" class="card" style="display: none; margin-top: 15px; background: #f8f9fa;">
                <h4>Edit Project</h4>
                <form method="POST" onsubmit="return confirmUpdate(this)">
                    <input type="hidden" name="action" value="update_project">
                    <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                    <input type="hidden" name="confirmed" value="false">
                    
                    <div class="info-grid">
                        <div class="form-group">
                            <label>Project Title *</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" value="<?php echo htmlspecialchars($project['location'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" value="<?php echo htmlspecialchars($project['start_date'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" value="<?php echo htmlspecialchars($project['end_date'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Maximum Volunteers</label>
                        <input type="number" name="max_volunteers" value="<?php echo htmlspecialchars($project['max_volunteers'] ?? ''); ?>" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Project Description</label>
                        <textarea name="description" rows="3"><?php echo htmlspecialchars($project['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn">Update Project</button>
                        <button type="button" class="btn" onclick="toggleEditForm(<?php echo $project['project_id']; ?>)">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php endif; ?>

<script>
function toggleEditForm(projectId) {
    var form = document.getElementById('edit-form-' + projectId);
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
        form.scrollIntoView({ behavior: 'smooth' });
    } else {
        form.style.display = 'none';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
