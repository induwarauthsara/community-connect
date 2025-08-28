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
    
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone'] ?? '');
    $address = htmlspecialchars($_POST['address'] ?? '');
    $skills = htmlspecialchars($_POST['skills'] ?? '');
    $birth_date = htmlspecialchars($_POST['birth_date'] ?? '');
    $emergency_contact = htmlspecialchars($_POST['emergency_contact'] ?? '');
    $emergency_phone = htmlspecialchars($_POST['emergency_phone'] ?? '');
    
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
                    "UPDATE users SET name = ?, email = ?, phone = ?, address = ?, skills = ?, birth_date = ?, emergency_contact = ?, emergency_phone = ? WHERE user_id = ?",
                    [$name, $email, $phone, $address, $skills, $birth_date ?: null, $emergency_contact, $emergency_phone, $user_id]
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

// Get joined projects with detailed information
$joined_projects = getMultipleRecords("
    SELECT p.*, o.name as org_name, vp.assigned_at, vp.status as volunteer_status,
           (SELECT COUNT(*) FROM volunteer_projects vp2 WHERE vp2.project_id = p.project_id) as total_volunteers
    FROM volunteer_projects vp
    JOIN projects p ON vp.project_id = p.project_id
    JOIN organizations o ON p.organization_id = o.org_id
    WHERE vp.volunteer_id = ?
    ORDER BY vp.assigned_at DESC
", [$user_id]);

// Get available organizations for joining
$available_orgs = [];
if (!$user['organization_id']) {
    $available_orgs = getMultipleRecords("
        SELECT org_id, name, description, contact_email, website,
               (SELECT COUNT(*) FROM users WHERE organization_id = o.org_id) as member_count,
               (SELECT COUNT(*) FROM projects WHERE organization_id = o.org_id AND status = 'approved') as active_projects
        FROM organizations o
        ORDER BY name
    ");
}

// Get volunteer statistics
$stats = [
    'total_projects' => count($joined_projects),
    'active_projects' => count(array_filter($joined_projects, function($p) { 
        return !$p['end_date'] || strtotime($p['end_date']) >= time(); 
    })),
    'completed_projects' => count(array_filter($joined_projects, function($p) { 
        return $p['end_date'] && strtotime($p['end_date']) < time(); 
    }))
];

$page_title = 'Volunteer Dashboard - Enhanced';
include 'includes/header.php';
?>
<?php if ($success): ?>
    <div class="success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Welcome Section -->
<div class="card">
    <div class="section-divider">
        <h2>Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h2>
    </div>
    
    <!-- Statistics Dashboard -->
    <div class="info-grid">
        <div class="info-item" style="text-align: center;">
            <strong style="font-size: 24px; color: #007bff;"><?php echo $stats['total_projects']; ?></strong><br>
            <span>Total Projects</span>
        </div>
        <div class="info-item" style="text-align: center;">
            <strong style="font-size: 24px; color: #28a745;"><?php echo $stats['active_projects']; ?></strong><br>
            <span>Active Projects</span>
        </div>
        <div class="info-item" style="text-align: center;">
            <strong style="font-size: 24px; color: #6c757d;"><?php echo $stats['completed_projects']; ?></strong><br>
            <span>Completed</span>
        </div>
    </div>
    
    <div class="volunteer-info">
        <div class="info-grid">
            <div>
                <strong>Current Organization:</strong><br>
                <?php if ($user['org_name']): ?>
                    <?php echo htmlspecialchars($user['org_name']); ?>
                    <form method="POST" style="display: inline; margin-left: 10px;" onsubmit="return confirmAction('leave this organization and all associated projects', this)">
                        <input type="hidden" name="action" value="leave_organization">
                        <input type="hidden" name="confirmed" value="false">
                        <button type="submit" class="btn btn-danger" style="font-size: 11px; padding: 2px 6px;">Leave</button>
                    </form>
                <?php else: ?>
                    <em style="color: #666;">Not associated with any organization</em>
                <?php endif; ?>
            </div>
            <div>
                <strong>Member Since:</strong><br>
                <?php echo formatDate($user['created_at']); ?>
            </div>
        </div>
    </div>
    
    <div class="action-buttons">
        <a href="browse_projects_new.php" class="btn">Browse New Projects</a>
        <?php if (!$user['org_name']): ?>
            <a href="#available-organizations" class="btn" onclick="document.getElementById('available-organizations').scrollIntoView({ behavior: 'smooth' });">View Organizations</a>
        <?php endif; ?>
    </div>
</div>

<!-- Enhanced Profile Management -->
<div class="card">
    <div class="section-divider">
        <h3>Manage Your Profile</h3>
    </div>
    
    <form method="POST" onsubmit="return confirmUpdate(this)">
        <input type="hidden" name="action" value="update_profile">
        <input type="hidden" name="confirmed" value="false">
        
        <!-- Basic Information -->
        <h4 style="color: #007bff; margin-top: 20px;">Basic Information</h4>
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
                <label>Date of Birth</label>
                <input type="date" name="birth_date" value="<?php echo htmlspecialchars($user['birth_date'] ?? ''); ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>
        
        <!-- Contact Information -->
        <h4 style="color: #007bff; margin-top: 20px;">Contact & Emergency</h4>
        <div class="form-group">
            <label>Home Address</label>
            <textarea name="address" rows="2" placeholder="Street address, city, state, ZIP code"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
        </div>
        
        <div class="info-grid">
            <div class="form-group">
                <label>Emergency Contact Name</label>
                <input type="text" name="emergency_contact" value="<?php echo htmlspecialchars($user['emergency_contact'] ?? ''); ?>" placeholder="Full name">
            </div>
            
            <div class="form-group">
                <label>Emergency Contact Phone</label>
                <input type="tel" name="emergency_phone" value="<?php echo htmlspecialchars($user['emergency_phone'] ?? ''); ?>" placeholder="Phone number">
            </div>
        </div>
        
        <!-- Skills and Interests -->
        <h4 style="color: #007bff; margin-top: 20px;">Skills & Interests</h4>
        <div class="form-group">
            <label>Skills, Experience & Interests</label>
            <textarea name="skills" rows="4" placeholder="List your skills, experience, and interests. Examples: Teaching, Event Management, Social Media, Programming, First Aid, Fundraising, etc."><?php echo htmlspecialchars($user['skills'] ?? ''); ?></textarea>
            <small style="color: #666; display: block; margin-top: 5px;">This helps organizations match you with suitable volunteer opportunities.</small>
        </div>
        
        <button type="submit" class="btn" style="margin-top: 15px;">Update Profile</button>
    </form>
</div>

<!-- Enhanced Projects Management -->
<div class="card">
    <div class="section-divider">
        <h3>Your Volunteer Projects (<?php echo count($joined_projects); ?>)</h3>
    </div>
    
    <?php if (empty($joined_projects)): ?>
        <div style="text-align: center; padding: 40px; color: #666;">
            <h4>Ready to make a difference?</h4>
            <p>You haven't joined any projects yet. There are many organizations looking for volunteers like you!</p>
            <a href="browse_projects_new.php" class="btn">Explore Volunteer Opportunities</a>
        </div>
    <?php else: ?>
        <!-- Active Projects -->
        <?php $active_projects = array_filter($joined_projects, function($p) { 
            return !$p['end_date'] || strtotime($p['end_date']) >= time(); 
        }); ?>
        
        <?php if (!empty($active_projects)): ?>
            <h4 style="color: #28a745;">🔄 Active Projects</h4>
            <?php foreach ($active_projects as $project): ?>
            <div class="card project-card" style="border-left-color: #28a745;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <h4><?php echo htmlspecialchars($project['title']); ?></h4>
                    <?php echo getStatusBadge($project['status']); ?>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Organization:</strong><br>
                        <?php echo htmlspecialchars($project['org_name']); ?>
                    </div>
                    <div class="info-item">
                        <strong>You Joined:</strong><br>
                        <?php echo formatDate($project['assigned_at']); ?>
                    </div>
                    <div class="info-item">
                        <strong>Team Size:</strong><br>
                        <?php echo (int)$project['total_volunteers']; ?> volunteers
                    </div>
                    <div class="info-item">
                        <strong>Timeline:</strong><br>
                        <?php if ($project['start_date']): ?>
                            <?php echo formatDate($project['start_date']); ?>
                            <?php if ($project['end_date']): ?>
                                to <?php echo formatDate($project['end_date']); ?>
                            <?php else: ?>
                                (ongoing)
                            <?php endif; ?>
                        <?php else: ?>
                            Flexible timing
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($project['description']): ?>
                    <p><strong>About:</strong> <?php echo htmlspecialchars(truncateText($project['description'], 200)); ?></p>
                <?php endif; ?>
                
                <?php if ($project['location']): ?>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($project['location']); ?></p>
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
        
        <!-- Completed Projects -->
        <?php $completed_projects = array_filter($joined_projects, function($p) { 
            return $p['end_date'] && strtotime($p['end_date']) < time(); 
        }); ?>
        
        <?php if (!empty($completed_projects)): ?>
            <h4 style="color: #6c757d; margin-top: 30px;">✅ Completed Projects</h4>
            <?php foreach ($completed_projects as $project): ?>
            <div class="card project-card" style="border-left-color: #6c757d; opacity: 0.8;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <h4><?php echo htmlspecialchars($project['title']); ?></h4>
                    <span class="status-badge status-default">Completed</span>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Organization:</strong><br>
                        <?php echo htmlspecialchars($project['org_name']); ?>
                    </div>
                    <div class="info-item">
                        <strong>Duration:</strong><br>
                        <?php echo formatDate($project['start_date']); ?> to <?php echo formatDate($project['end_date']); ?>
                    </div>
                    <div class="info-item">
                        <strong>Your Contribution:</strong><br>
                        <?php 
                        $days = ceil((strtotime($project['end_date']) - strtotime($project['assigned_at'])) / (60 * 60 * 24));
                        echo $days > 0 ? $days . ' days' : 'Less than 1 day';
                        ?>
                    </div>
                </div>
                
                <?php if ($project['description']): ?>
                    <p><strong>About:</strong> <?php echo htmlspecialchars(truncateText($project['description'], 150)); ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Organization Discovery -->
<?php if (!$user['org_name'] && !empty($available_orgs)): ?>
<div class="card" id="available-organizations">
    <div class="section-divider">
        <h3>Discover Organizations</h3>
    </div>
    <p>Explore organizations and their volunteer opportunities. Joining a project automatically connects you with the organization.</p>
    
    <div class="info-grid">
        <?php foreach ($available_orgs as $org): ?>
        <div class="org-info" style="position: relative;">
            <h4><?php echo htmlspecialchars($org['name']); ?></h4>
            
            <?php if ($org['description']): ?>
                <p><?php echo htmlspecialchars(truncateText($org['description'], 120)); ?></p>
            <?php endif; ?>
            
            <div style="margin: 10px 0;">
                <div class="info-item" style="display: inline-block; margin-right: 15px;">
                    <strong>Members:</strong> <?php echo (int)$org['member_count']; ?>
                </div>
                <div class="info-item" style="display: inline-block;">
                    <strong>Active Projects:</strong> <?php echo (int)$org['active_projects']; ?>
                </div>
            </div>
            
            <?php if ($org['contact_email']): ?>
                <p style="font-size: 12px;"><strong>Contact:</strong> 
                <a href="mailto:<?php echo htmlspecialchars($org['contact_email']); ?>"><?php echo htmlspecialchars($org['contact_email']); ?></a></p>
            <?php endif; ?>
            
            <?php if ($org['website']): ?>
                <p style="font-size: 12px;"><strong>Website:</strong> 
                <a href="<?php echo htmlspecialchars($org['website']); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($org['website']); ?></a></p>
            <?php endif; ?>
            
            <div style="margin-top: 15px;">
                <a href="browse_projects_new.php?organization=<?php echo urlencode($org['name']); ?>" class="btn" style="font-size: 12px; padding: 5px 10px;">
                    View Their Projects
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="text-small" style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 4px;">
        <strong>How it works:</strong> Browse projects from any organization and join ones that interest you. 
        When you join a project, you automatically become part of that organization and can participate in all their activities.
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
