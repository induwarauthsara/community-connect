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
    // Debug: Log the POST data
    error_log("Profile update form submitted");
    error_log("POST data: " . print_r($_POST, true));
    
    if (($_POST['confirmed'] ?? 'false') !== 'true') {
        $error = 'Error: Action requires confirmation. Please confirm your changes.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $skills = trim($_POST['skills'] ?? '');
        $birth_date = trim($_POST['birth_date'] ?? '');
        $emergency_contact = trim($_POST['emergency_contact'] ?? '');
        $emergency_phone = trim($_POST['emergency_phone'] ?? '');
        
        // Validation
        if (empty($name)) {
            $error = 'Name is required.';
        } elseif (empty($email)) {
            $error = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif ($birth_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
            $error = 'Please enter a valid birth date.';
        } elseif ($birth_date && strtotime($birth_date) > strtotime('today')) {
            $error = 'Birth date cannot be in the future.';
        } else {
            // Check email uniqueness
            $email_escaped = mysqli_real_escape_string($connection, $email);
            $existing_query = "SELECT user_id FROM users WHERE email = '$email_escaped' AND user_id != $user_id";
            $existing_result = mysqli_query($connection, $existing_query);
            
            if ($existing_result && mysqli_num_rows($existing_result) > 0) {
                $error = 'This email is already in use.';
            } else {
                // Escape all input
                $name_escaped = mysqli_real_escape_string($connection, $name);
                $email_escaped = mysqli_real_escape_string($connection, $email);
                $phone_escaped = mysqli_real_escape_string($connection, $phone);
                $address_escaped = mysqli_real_escape_string($connection, $address);
                $skills_escaped = mysqli_real_escape_string($connection, $skills);
                $birth_date_escaped = $birth_date ? "'" . mysqli_real_escape_string($connection, $birth_date) . "'" : 'NULL';
                $emergency_contact_escaped = mysqli_real_escape_string($connection, $emergency_contact);
                $emergency_phone_escaped = mysqli_real_escape_string($connection, $emergency_phone);
                
                // Update query
                $update_query = "UPDATE users SET 
                    name = '$name_escaped', 
                    email = '$email_escaped', 
                    phone = '$phone_escaped', 
                    address = '$address_escaped', 
                    skills = '$skills_escaped', 
                    birth_date = $birth_date_escaped, 
                    emergency_contact = '$emergency_contact_escaped', 
                    emergency_phone = '$emergency_phone_escaped' 
                    WHERE user_id = $user_id";
                
                error_log("Update query: " . $update_query);
                
                if (mysqli_query($connection, $update_query)) {
                    $success = 'Profile updated successfully!';
                    // Refresh user data
                    $user_query = "SELECT u.*, o.name as org_name, o.org_id
                                   FROM users u
                                   LEFT JOIN organizations o ON u.organization_id = o.org_id
                                   WHERE u.user_id = $user_id";
                    $user_result = mysqli_query($connection, $user_query);
                    $user = mysqli_fetch_assoc($user_result);
                } else {
                    $error = 'Failed to update profile: ' . mysqli_error($connection);
                    error_log("Update failed: " . mysqli_error($connection));
                }
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
    // First check if the organizations table has the expected columns
    $test_query = "SHOW COLUMNS FROM organizations";
    $columns = getMultipleRecords($test_query);
    $has_website = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'website') {
            $has_website = true;
            break;
        }
    }
    
    // Use appropriate query based on table schema
    if ($has_website) {
        $available_orgs = getMultipleRecords("
            SELECT org_id, name, description, contact_email, website,
                   (SELECT COUNT(*) FROM users WHERE organization_id = o.org_id) as member_count,
                   (SELECT COUNT(*) FROM projects WHERE organization_id = o.org_id AND status = 'approved') as active_projects
            FROM organizations o
            ORDER BY name
        ");
    } else {
        $available_orgs = getMultipleRecords("
            SELECT org_id, name, description, contact_email,
                   (SELECT COUNT(*) FROM users WHERE organization_id = o.org_id) as member_count,
                   (SELECT COUNT(*) FROM projects WHERE organization_id = o.org_id AND status = 'approved') as active_projects
            FROM organizations o
            ORDER BY name
        ");
    }
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

<script>
function confirmAction(actionText, form) {
    if (confirm('Are you sure you want to ' + actionText + '? This action cannot be undone.')) {
        // Set the confirmed field to true
        const confirmedInput = form.querySelector('input[name="confirmed"]');
        if (confirmedInput) {
            confirmedInput.value = 'true';
        }
        // Now submit the form
        form.submit();
    }
    return false;
}

function confirmUpdate(event, form) {
    // Prevent the default form submission
    if (event) {
        event.preventDefault();
    }
    
    if (confirm('Are you sure you want to update your profile with these changes?')) {
        // Set the confirmed field to true
        const confirmedInput = form.querySelector('input[name="confirmed"]');
        if (confirmedInput) {
            confirmedInput.value = 'true';
        }
        // Now submit the form
        form.submit();
    }
    return false;
}

function confirmProfileUpdate() {
    // Validate required fields before confirmation
    const nameField = document.querySelector('#profileForm input[name="name"]');
    const emailField = document.querySelector('#profileForm input[name="email"]');
    
    if (!nameField.value.trim()) {
        alert('❌ Please enter your full name. This field is required.');
        nameField.focus();
        return false;
    }
    
    if (nameField.value.trim().length < 2) {
        alert('❌ Name must be at least 2 characters long.');
        nameField.focus();
        return false;
    }
    
    if (!emailField.value.trim()) {
        alert('❌ Please enter your email address. This field is required.');
        emailField.focus();
        return false;
    }
    
    // Basic email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(emailField.value.trim())) {
        alert('❌ Please enter a valid email address.');
        emailField.focus();
        return false;
    }
    
    // Validate birth date if provided
    const birthDateField = document.querySelector('#profileForm input[name="birth_date"]');
    if (birthDateField.value) {
        const birthDate = new Date(birthDateField.value);
        const today = new Date();
        const age = Math.floor((today - birthDate) / (1000 * 60 * 60 * 24 * 365.25));
        
        if (birthDate > today) {
            alert('❌ Birth date cannot be in the future.');
            birthDateField.focus();
            return false;
        }
        
        if (age > 120) {
            alert('❌ Please enter a valid birth date.');
            birthDateField.focus();
            return false;
        }
    }
    
    if (confirm('Are you sure you want to update your profile with these changes?')) {
        // Set the confirmed field to true
        const confirmedInput = document.querySelector('#profileForm input[name="confirmed"]');
        if (confirmedInput) {
            confirmedInput.value = 'true';
        }
        return true; // Allow form submission
    }
    return false; // Prevent form submission
}
</script>

<link rel="stylesheet" href="assets/css/volunteer_dashboard.css">

<div class="volunteer-dashboard">
<?php if ($success): ?>
    <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <strong>Success!</strong> <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <strong>Error!</strong> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Welcome Section -->
<div class="card">
    <div class="section-header">
        <h2>Welcome back, <?php echo htmlspecialchars($user['name']); ?>! 👋</h2>
        <div class="text-muted">Member since <?php echo formatDate($user['created_at']); ?></div>
    </div>
    
    <!-- Enhanced Statistics Dashboard -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total_projects']; ?></div>
            <div class="stat-label">Total Projects</div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #28a745, #1e7e34);">
            <div class="stat-number"><?php echo $stats['active_projects']; ?></div>
            <div class="stat-label">Active Projects</div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #6c757d, #495057);">
            <div class="stat-number"><?php echo $stats['completed_projects']; ?></div>
            <div class="stat-label">Completed Projects</div>
        </div>
    </div>
    
    <div class="project-meta" style="grid-template-columns: 1fr 1fr;">
        <div class="meta-item">
            <span class="meta-label">Current Organization</span>
            <div class="meta-value">
                <?php if ($user['org_name']): ?>
                    <?php echo htmlspecialchars($user['org_name']); ?>
                    <form method="POST" style="display: inline; margin-left: 10px;" id="leaveOrgForm">
                        <input type="hidden" name="action" value="leave_organization">
                        <input type="hidden" name="confirmed" value="false">
                        <button type="button" class="btn btn-sm" style="background: #dc3545; color: white; font-size: 11px; padding: 4px 8px;" 
                                onclick="confirmAction('leave this organization and all associated projects', document.getElementById('leaveOrgForm'))">Leave</button>
                    </form>
                <?php else: ?>
                    <em style="color: #666;">Not associated with any organization</em>
                <?php endif; ?>
            </div>
        </div>
        <div class="meta-item">
            <span class="meta-label">Quick Actions</span>
            <div class="meta-value">
                <a href="browse_projects.php" class="btn btn-sm" style="margin-right: 10px;">Browse Projects</a>
                <?php if (!$user['org_name']): ?>
                    <a href="#available-organizations" class="btn btn-sm btn-outline" onclick="document.getElementById('available-organizations').scrollIntoView({ behavior: 'smooth' });">View Organizations</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Profile Management -->
<div class="card">
    <div class="section-header">
        <h3>Manage Your Profile 👤</h3>
        <small class="text-muted">Keep your information up to date</small>
    </div>
    
    <form method="POST" id="profileForm">
        <input type="hidden" name="action" value="update_profile">
        <input type="hidden" name="confirmed" value="false">
        
        <!-- Basic Information -->
        <div class="form-section">
            <h4 class="form-section-title">📋 Basic Information</h4>
            <div class="project-meta">
                <div class="form-group">
                    <label class="meta-label">Full Name *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required 
                           minlength="2" maxlength="100"
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;"
                           placeholder="Enter your full name">
                </div>
                
                <div class="form-group">
                    <label class="meta-label">Email Address *</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required
                           maxlength="100"
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;"
                           placeholder="your.email@example.com">
                </div>
                
                <div class="form-group">
                    <label class="meta-label">Phone Number</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                           maxlength="20"
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;"
                           placeholder="(123) 456-7890">
                </div>
                
                <div class="form-group">
                    <label class="meta-label">Date of Birth</label>
                    <input type="date" name="birth_date" value="<?php echo htmlspecialchars($user['birth_date'] ?? ''); ?>" 
                           max="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d', strtotime('-120 years')); ?>"
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
            </div>
        </div>
        
        <!-- Contact Information -->
        <div class="form-section">
            <h4 class="form-section-title">📍 Contact & Emergency Information</h4>
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="meta-label">Home Address</label>
                <textarea name="address" rows="3" maxlength="500"
                          placeholder="Street address, city, state, ZIP code"
                          style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; resize: vertical;"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="project-meta">
                <div class="form-group">
                    <label class="meta-label">Emergency Contact Name</label>
                    <input type="text" name="emergency_contact" value="<?php echo htmlspecialchars($user['emergency_contact'] ?? ''); ?>" 
                           maxlength="100"
                           placeholder="Full name of emergency contact"
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                
                <div class="form-group">
                    <label class="meta-label">Emergency Contact Phone</label>
                    <input type="tel" name="emergency_phone" value="<?php echo htmlspecialchars($user['emergency_phone'] ?? ''); ?>" 
                           maxlength="20"
                           placeholder="Emergency contact phone number"
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
            </div>
        </div>
        
        <!-- Skills and Interests -->
        <div class="form-section">
            <h4 class="form-section-title">🛠️ Skills & Interests</h4>
            <div class="form-group">
                <label class="meta-label">Skills, Experience & Interests</label>
                <textarea name="skills" rows="4" maxlength="1000"
                          placeholder="List your skills, experience, and interests. Examples: Teaching, Event Management, Social Media, Programming, First Aid, Fundraising, etc."
                          style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; resize: vertical;"><?php echo htmlspecialchars($user['skills'] ?? ''); ?></textarea>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 25px;">
            <button type="submit" class="btn" style="background: #007bff; color: white; padding: 12px 30px; font-size: 16px;" 
                    onclick="return confirmProfileUpdate()">
                ✅ Update Profile
            </button>
        </div>
    </form>
</div>

<!-- Enhanced Projects Management -->
<div class="card">
    <div class="section-header">
        <h3>Your Volunteer Projects 🚀</h3>
        <div class="badge badge-active"><?php echo count($joined_projects); ?> total projects</div>
    </div>
    
    <?php if (empty($joined_projects)): ?>
        <div class="empty-state">
            <h4>Ready to make a difference? 🌟</h4>
            <p>You haven't joined any projects yet. There are many organizations looking for volunteers like you!</p>
            <a href="browse_projects.php" class="btn" style="background: #007bff; color: white; padding: 12px 24px; margin-top: 15px;">
                🔍 Explore Volunteer Opportunities
            </a>
        </div>
    <?php else: ?>
        <!-- Active Projects -->
        <?php $active_projects = array_filter($joined_projects, function($p) { 
            return !$p['end_date'] || strtotime($p['end_date']) >= time(); 
        }); ?>
        
        <?php if (!empty($active_projects)): ?>
            <div class="form-section">
                <h4 style="color: #28a745; margin-bottom: 20px;">🔄 Active Projects (<?php echo count($active_projects); ?>)</h4>
                <?php foreach ($active_projects as $project): ?>
                <div class="project-card" style="border-left-color: #28a745;">
                    <div class="project-header">
                        <h4 class="project-title"><?php echo htmlspecialchars($project['title']); ?></h4>
                        <?php echo getStatusBadge($project['status']); ?>
                    </div>
                    
                    <div class="project-meta">
                        <div class="meta-item">
                            <span class="meta-label">🏢 Organization</span>
                            <div class="meta-value"><?php echo htmlspecialchars($project['org_name']); ?></div>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">📅 You Joined</span>
                            <div class="meta-value"><?php echo formatDate($project['assigned_at']); ?></div>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">👥 Team Size</span>
                            <div class="meta-value"><?php echo (int)$project['total_volunteers']; ?> volunteers</div>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">⏰ Timeline</span>
                            <div class="meta-value">
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
                    </div>
                    
                    <?php if ($project['description']): ?>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
                            <strong>About this project:</strong><br>
                            <?php echo htmlspecialchars(truncateText($project['description'], 200)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($project['location']): ?>
                        <p><strong>📍 Location:</strong> <?php echo htmlspecialchars($project['location']); ?></p>
                    <?php endif; ?>
                    
                    <div style="text-align: right; margin-top: 20px;">
                        <form method="POST" class="form-inline" id="leaveProjectForm_<?php echo $project['project_id']; ?>">
                            <input type="hidden" name="action" value="leave_project">
                            <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                            <input type="hidden" name="confirmed" value="false">
                            <button type="button" class="btn" style="background: #dc3545; color: white; padding: 8px 16px;" 
                                    onclick="confirmAction('leave this project', document.getElementById('leaveProjectForm_<?php echo $project['project_id']; ?>'))">
                                ❌ Leave Project
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Completed Projects -->
        <?php $completed_projects = array_filter($joined_projects, function($p) { 
            return $p['end_date'] && strtotime($p['end_date']) < time(); 
        }); ?>
        
        <?php if (!empty($completed_projects)): ?>
            <div class="form-section">
                <h4 style="color: #6c757d; margin-bottom: 20px;">✅ Completed Projects (<?php echo count($completed_projects); ?>)</h4>
                <?php foreach ($completed_projects as $project): ?>
                <div class="project-card" style="border-left-color: #6c757d; opacity: 0.9;">
                    <div class="project-header">
                        <h4 class="project-title"><?php echo htmlspecialchars($project['title']); ?></h4>
                        <span class="badge badge-completed">✅ Completed</span>
                    </div>
                    
                    <div class="project-meta">
                        <div class="meta-item">
                            <span class="meta-label">🏢 Organization</span>
                            <div class="meta-value"><?php echo htmlspecialchars($project['org_name']); ?></div>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">📅 Duration</span>
                            <div class="meta-value">
                                <?php echo formatDate($project['start_date']); ?> to <?php echo formatDate($project['end_date']); ?>
                            </div>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">⏱️ Your Contribution</span>
                            <div class="meta-value">
                                <?php 
                                $days = ceil((strtotime($project['end_date']) - strtotime($project['assigned_at'])) / (60 * 60 * 24));
                                echo $days > 0 ? $days . ' days' : 'Less than 1 day';
                                ?>
                            </div>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">🏆 Impact</span>
                            <div class="meta-value">Project Completed</div>
                        </div>
                    </div>
                    
                    <?php if ($project['description']): ?>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
                            <strong>About this project:</strong><br>
                            <?php echo htmlspecialchars(truncateText($project['description'], 150)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Enhanced Organization Discovery -->
<?php if (!$user['org_name'] && !empty($available_orgs)): ?>
<div class="card" id="available-organizations">
    <div class="section-header">
        <h3>Discover Organizations 🌍</h3>
        <small class="text-muted">Find your perfect volunteer match</small>
    </div>
    
    <div style="background: #e7f3ff; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
        <h4 style="color: #007bff; margin-bottom: 10px;">🤝 How It Works</h4>
        <p style="margin: 0;">Browse projects from any organization and join ones that interest you. 
        When you join a project, you automatically become part of that organization and can participate in all their activities.</p>
    </div>
    
    <div class="project-meta">
        <?php foreach ($available_orgs as $org): ?>
        <div class="organization-card">
            <h4 style="color: #007bff; margin-bottom: 10px;"><?php echo htmlspecialchars($org['name']); ?></h4>
            
            <?php if ($org['description']): ?>
                <p style="color: #666; margin-bottom: 15px;"><?php echo htmlspecialchars(truncateText($org['description'], 120)); ?></p>
            <?php endif; ?>
            
            <div style="margin: 15px 0;">
                <div class="project-meta" style="grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="meta-item">
                        <span class="meta-label">👥 Members</span>
                        <div class="meta-value"><?php echo (int)$org['member_count']; ?></div>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">🚀 Active Projects</span>
                        <div class="meta-value"><?php echo (int)$org['active_projects']; ?></div>
                    </div>
                </div>
            </div>
            
            <?php if ($org['contact_email']): ?>
                <p style="font-size: 14px; margin: 10px 0;">
                    <strong>📧 Contact:</strong> 
                    <a href="mailto:<?php echo htmlspecialchars($org['contact_email']); ?>" style="color: #007bff;">
                        <?php echo htmlspecialchars($org['contact_email']); ?>
                    </a>
                </p>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="browse_projects.php?organization=<?php echo urlencode($org['name']); ?>" 
                   class="btn" style="background: #007bff; color: white; padding: 10px 20px;">
                    🔍 View Their Projects
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

</div> <!-- Close volunteer-dashboard div -->

<?php include 'includes/footer.php'; ?>
