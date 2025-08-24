<?php
require_once 'config/database.php';
require_once 'includes/common.php';

startSecureSession();
requireRole('volunteer');

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get volunteer info with organization
$user = getSingleRecord("
    SELECT u.*, o.name as org_name, o.org_id
    FROM users u
    LEFT JOIN organizations o ON u.organization_id = o.org_id
    WHERE u.user_id = ?
", [$user_id]);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if (($_POST['confirmed'] ?? 'false') !== 'true') {
        die('Error: Action requires confirmation');
    }
    
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $skills = sanitizeInput($_POST['skills'] ?? '');
    $birth_date = sanitizeInput($_POST['birth_date'] ?? '');
    
    // Validation
    $required_fields = ['name' => $name, 'email' => $email];
    $missing = validateRequiredFields($required_fields);
    
    if (!empty($missing)) {
        $error = 'Please fill in all required fields: ' . implode(', ', $missing);
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } elseif ($birth_date && !isValidDate($birth_date)) {
        $error = 'Please enter a valid birth date.';
    } elseif ($birth_date && strtotime($birth_date) > strtotime('today')) {
        $error = 'Birth date cannot be in the future.';
    } else {
        // Check email uniqueness
        $existing = getSingleRecord("SELECT user_id FROM users WHERE email = ? AND user_id != ?", [$email, $user_id]);
        if ($existing) {
            $error = 'This email is already in use.';
        } else {
            try {
                updateRecord(
                    "UPDATE users SET name = ?, email = ?, phone = ?, address = ?, skills = ?, birth_date = ? WHERE user_id = ?",
                    [$name, $email, $phone, $address, $skills, $birth_date ?: null, $user_id]
                );
                $success = 'Profile updated successfully!';
                // Refresh user data
                $user = getSingleRecord("
                    SELECT u.*, o.name as org_name, o.org_id
                    FROM users u
                    LEFT JOIN organizations o ON u.organization_id = o.org_id
                    WHERE u.user_id = ?
                ", [$user_id]);
            } catch (Exception $e) {
                $error = 'Failed to update profile. Please try again.';
            }
        }
    }
}

// Handle leave organization
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'leave_organization') {
    if (($_POST['confirmed'] ?? 'false') !== 'true') {
        die('Error: Action requires confirmation');
    }
    
    try {
        // Remove from all projects first
        deleteRecord("DELETE FROM volunteer_projects WHERE volunteer_id = ?", [$user_id]);
        
        // Remove organization association
        updateRecord("UPDATE users SET organization_id = NULL WHERE user_id = ?", [$user_id]);
        
        $success = 'Successfully left organization and all associated projects.';
        // Refresh user data
        $user = getSingleRecord("
            SELECT u.*, o.name as org_name, o.org_id
            FROM users u
            LEFT JOIN organizations o ON u.organization_id = o.org_id
            WHERE u.user_id = ?
        ", [$user_id]);
    } catch (Exception $e) {
        $error = 'Failed to leave organization. Please try again.';
    }
}

// Handle leave project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'leave_project') {
    if (($_POST['confirmed'] ?? 'false') !== 'true') {
        die('Error: Action requires confirmation');
    }
    
    $project_id = (int)$_POST['project_id'];
    try {
        deleteRecord("DELETE FROM volunteer_projects WHERE volunteer_id = ? AND project_id = ?", [$user_id, $project_id]);
        $success = 'Successfully left the project.';
    } catch (Exception $e) {
        $error = 'Failed to leave project. Please try again.';
    }
}

// Get joined projects with organization details
$joined_projects = getMultipleRecords("
    SELECT p.*, o.name as org_name, vp.assigned_at, vp.status as volunteer_status,
           (SELECT COUNT(*) FROM volunteer_projects vp2 WHERE vp2.project_id = p.project_id) as total_volunteers
    FROM volunteer_projects vp
    JOIN projects p ON vp.project_id = p.project_id
    JOIN organizations o ON p.organization_id = o.org_id
    WHERE vp.volunteer_id = ?
    ORDER BY vp.assigned_at DESC
", [$user_id]);

// Get all available organizations for joining
$available_orgs = [];
if (!$user['organization_id']) {
    $available_orgs = getMultipleRecords("
        SELECT org_id, name, description, contact_email, 
               (SELECT COUNT(*) FROM users WHERE organization_id = o.org_id) as member_count
        FROM organizations o
        ORDER BY name
    ");
}

$page_title = 'Volunteer Dashboard';
include 'includes/header.php';
?>

<?php if ($success): ?>
    <div class="success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="section-divider">
        <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
    </div>
    <div class="volunteer-info">
        <p><strong>Role:</strong> Volunteer</p>
        <p><strong>Current Organization:</strong> 
            <?php if ($user['org_name']): ?>
                <?php echo htmlspecialchars($user['org_name']); ?>
                <form method="POST" style="display: inline; margin-left: 10px;" onsubmit="return confirmAction('leave this organization and all associated projects', this)">
                    <input type="hidden" name="action" value="leave_organization">
                    <input type="hidden" name="confirmed" value="false">
                    <button type="submit" class="btn btn-danger" style="font-size: 12px; padding: 2px 8px;">Leave Organization</button>
                </form>
            <?php else: ?>
                <em>Not associated with any organization</em>
            <?php endif; ?>
        </p>
        <p><strong>Active Projects:</strong> <?php echo count($joined_projects); ?></p>
    </div>
    <div class="action-buttons">
        <a href="browse_projects.php" class="btn">Browse Projects</a>
        <?php if (!$user['org_name']): ?>
            <a href="#available-organizations" class="btn" onclick="document.getElementById('available-organizations').scrollIntoView();">View Organizations</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="section-divider">
        <h3>Your Profile</h3>
    </div>
    <form method="POST" onsubmit="return confirmUpdate(this)">
        <input type="hidden" name="action" value="update_profile">
        <input type="hidden" name="confirmed" value="false">
        
        <div class="info-grid">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Birth Date</label>
                <input type="date" name="birth_date" value="<?php echo htmlspecialchars($user['birth_date'] ?? ''); ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>Address</label>
            <textarea name="address" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>Skills & Interests</label>
            <textarea name="skills" rows="3" placeholder="e.g., Teaching, Event Management, Social Media, Programming..."><?php echo htmlspecialchars($user['skills'] ?? ''); ?></textarea>
        </div>
        
        <button type="submit" class="btn">Update Profile</button>
    </form>
</div>

<div class="card">
    <div class="section-divider">
        <h3>Your Projects (<?php echo count($joined_projects); ?>)</h3>
    </div>
    <?php if (empty($joined_projects)): ?>
        <p>You haven't joined any projects yet.</p>
        <div class="text-small">
            <p><a href="browse_projects.php">Browse available projects</a> to get started with volunteer opportunities!</p>
        </div>
    <?php else: ?>
        <?php foreach ($joined_projects as $project): ?>
        <div class="card project-card">
            <h4><?php echo htmlspecialchars($project['title']); ?></h4>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Organization:</strong><br>
                    <?php echo htmlspecialchars($project['org_name']); ?>
                </div>
                <div class="info-item">
                    <strong>Status:</strong><br>
                    <?php echo getStatusBadge($project['status']); ?>
                </div>
                <div class="info-item">
                    <strong>Joined:</strong><br>
                    <?php echo formatDate($project['assigned_at']); ?>
                </div>
                <div class="info-item">
                    <strong>Total Volunteers:</strong><br>
                    <?php echo (int)$project['total_volunteers']; ?>
                </div>
            </div>
            
            <?php if ($project['description']): ?>
                <p><strong>Description:</strong> <?php echo htmlspecialchars(truncateText($project['description'], 200)); ?></p>
            <?php endif; ?>
            
            <?php if ($project['location']): ?>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($project['location']); ?></p>
            <?php endif; ?>
            
            <?php if ($project['start_date'] || $project['end_date']): ?>
                <p><strong>Duration:</strong> 
                    <?php echo formatDate($project['start_date']); ?> 
                    <?php if ($project['end_date']): ?>
                        to <?php echo formatDate($project['end_date']); ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
            
            <div class="action-buttons">
                <form method="POST" class="form-inline" onsubmit="return confirmAction('leave this project', this)">
                    <input type="hidden" name="action" value="leave_project">
                    <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                    <input type="hidden" name="confirmed" value="false">
                    <button type="submit" class="btn btn-danger">Leave Project</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (!$user['org_name'] && !empty($available_orgs)): ?>
<div class="card" id="available-organizations">
    <div class="section-divider">
        <h3>Available Organizations</h3>
    </div>
    <p class="text-small">Join an organization to access their volunteer projects:</p>
    
    <?php foreach ($available_orgs as $org): ?>
    <div class="org-info">
        <h4><?php echo htmlspecialchars($org['name']); ?></h4>
        <?php if ($org['description']): ?>
            <p><?php echo htmlspecialchars(truncateText($org['description'], 150)); ?></p>
        <?php endif; ?>
        <div class="info-grid">
            <?php if ($org['contact_email']): ?>
            <div class="info-item">
                <strong>Contact:</strong><br>
                <a href="mailto:<?php echo htmlspecialchars($org['contact_email']); ?>"><?php echo htmlspecialchars($org['contact_email']); ?></a>
            </div>
            <?php endif; ?>
            <div class="info-item">
                <strong>Current Members:</strong><br>
                <?php echo (int)$org['member_count']; ?>
            </div>
        </div>
        <p class="text-small">
            <em>To join this organization, browse and join one of their approved projects on the <a href="browse_projects.php">Browse Projects</a> page.</em>
        </p>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
