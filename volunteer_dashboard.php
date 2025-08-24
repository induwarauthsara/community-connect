<?php
require_once 'config/database.php';

// Start secure session and require volunteer role
startSecureSession();
requireRole('volunteer');

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get user data
$user = getSingleRecord("SELECT * FROM users WHERE user_id = ?", [$user_id]);
$user_projects = getMultipleRecords("
    SELECT p.*, o.name as org_name, vp.joined_date, vp.status
    FROM volunteer_projects vp
    JOIN projects p ON vp.project_id = p.project_id
    JOIN organizations o ON p.organization_id = o.org_id
    WHERE vp.volunteer_id = ?
    ORDER BY vp.joined_date DESC
", [$user_id]);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if ($_POST['confirmed'] !== 'true') {
        die('Error: Action requires confirmation');
    }
    
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    $skills = sanitizeInput($_POST['skills']);
    $bio = sanitizeInput($_POST['bio']);
    $availability = sanitizeInput($_POST['availability']);
    
    // Validation
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required.';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email exists for another user
        $existing_user = getSingleRecord("SELECT user_id FROM users WHERE email = ? AND user_id != ?", [$email, $user_id]);
        
        if ($existing_user) {
            $error = 'This email is already in use by another account.';
        } else {
            try {
                $updated = updateRecord("
                    UPDATE users SET 
                    name = ?, email = ?, phone = ?, address = ?, 
                    skills = ?, bio = ?, availability = ?
                    WHERE user_id = ?
                ", [$name, $email, $phone, $address, $skills, $bio, $availability, $user_id]);
                
                if ($updated) {
                    logActivity('updated_profile', 'users', $user_id);
                    $success = 'Profile updated successfully!';
                    // Refresh user data
                    $user = getSingleRecord("SELECT * FROM users WHERE user_id = ?", [$user_id]);
                } else {
                    $error = 'Failed to update profile. Please try again.';
                }
            } catch (Exception $e) {
                error_log("Profile update error: " . $e->getMessage());
                $error = 'An error occurred while updating your profile.';
            }
        }
    }
}

// Handle leaving project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'leave_project') {
    if ($_POST['confirmed'] !== 'true') {
        die('Error: Action requires confirmation');
    }
    
    $project_id = (int)$_POST['project_id'];
    
    try {
        $deleted = deleteRecord("
            DELETE FROM volunteer_projects 
            WHERE volunteer_id = ? AND project_id = ?
        ", [$user_id, $project_id]);
        
        if ($deleted) {
            logActivity('left_project', 'volunteer_projects', $project_id);
            $success = 'You have left the project successfully.';
            // Refresh project data
            $user_projects = getMultipleRecords("
                SELECT p.*, o.name as org_name, vp.joined_date, vp.status
                FROM volunteer_projects vp
                JOIN projects p ON vp.project_id = p.project_id
                JOIN organizations o ON p.organization_id = o.org_id
                WHERE vp.volunteer_id = ?
                ORDER BY vp.joined_date DESC
            ", [$user_id]);
        } else {
            $error = 'Failed to leave project. Please try again.';
        }
    } catch (Exception $e) {
        error_log("Leave project error: " . $e->getMessage());
        $error = 'An error occurred while leaving the project.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Dashboard - Community Connect</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header .container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .nav {
            display: flex;
            gap: 2rem;
        }

        .nav a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s;
        }

        .nav a:hover {
            opacity: 0.8;
        }

        .main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .dashboard-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .dashboard-header h1 {
            color: #007bff;
            margin-bottom: 0.5rem;
        }

        .dashboard-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .tab-button {
            padding: 0.75rem 1.5rem;
            background-color: white;
            color: #007bff;
            border: 2px solid #007bff;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .tab-button.active {
            background-color: #007bff;
            color: white;
        }

        .tab-content {
            display: none;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .tab-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .project-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: border-color 0.3s;
        }

        .project-card:hover {
            border-color: #007bff;
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .project-title {
            color: #007bff;
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .project-org {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .project-status {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-completed {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .project-description {
            color: #333;
            margin-bottom: 1rem;
        }

        .project-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .project-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .project-meta {
                grid-template-columns: 1fr;
            }
            
            .project-actions {
                flex-direction: column;
            }
            
            .dashboard-tabs {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">Community Connect</div>
            <nav class="nav">
                <a href="browse_projects.php">Browse Projects</a>
                <a href="volunteer_dashboard.php">Dashboard</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h1>
            <p>Manage your volunteer profile and track your projects</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="dashboard-tabs">
            <button class="tab-button active" onclick="showTab('profile')">My Profile</button>
            <button class="tab-button" onclick="showTab('projects')">My Projects</button>
        </div>

        <!-- Profile Tab -->
        <div id="profile" class="tab-content active">
            <h2>Profile Information</h2>
            <form method="POST" onsubmit="return confirmProfileUpdate()">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" id="profile_confirmed" name="confirmed" value="false">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required 
                               value="<?php echo htmlspecialchars($user['name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="availability">Availability</label>
                        <select id="availability" name="availability">
                            <option value="">Select availability</option>
                            <option value="weekdays" <?php echo ($user['availability'] === 'weekdays') ? 'selected' : ''; ?>>Weekdays</option>
                            <option value="weekends" <?php echo ($user['availability'] === 'weekends') ? 'selected' : ''; ?>>Weekends</option>
                            <option value="flexible" <?php echo ($user['availability'] === 'flexible') ? 'selected' : ''; ?>>Flexible</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="skills">Skills & Interests</label>
                    <textarea id="skills" name="skills" rows="3" 
                              placeholder="e.g., Teaching, IT Support, Event Management"><?php echo htmlspecialchars($user['skills'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="bio">About Me</label>
                    <textarea id="bio" name="bio" rows="4" 
                              placeholder="Tell organizations about yourself and why you volunteer"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>

        <!-- Projects Tab -->
        <div id="projects" class="tab-content">
            <h2>My Projects (<?php echo count($user_projects); ?>)</h2>
            
            <?php if (empty($user_projects)): ?>
                <p>You haven't joined any projects yet. <a href="browse_projects.php">Browse available projects</a> to get started!</p>
            <?php else: ?>
                <?php foreach ($user_projects as $project): ?>
                    <div class="project-card">
                        <div class="project-header">
                            <div>
                                <div class="project-title"><?php echo htmlspecialchars($project['title']); ?></div>
                                <div class="project-org">by <?php echo htmlspecialchars($project['org_name']); ?></div>
                            </div>
                            <div class="project-status status-<?php echo strtolower($project['status']); ?>">
                                <?php echo ucfirst($project['status']); ?>
                            </div>
                        </div>
                        
                        <div class="project-description">
                            <?php echo htmlspecialchars(substr($project['description'], 0, 200)) . (strlen($project['description']) > 200 ? '...' : ''); ?>
                        </div>
                        
                        <div class="project-meta">
                            <div><strong>Start Date:</strong> <?php echo date('M j, Y', strtotime($project['start_date'])); ?></div>
                            <div><strong>End Date:</strong> <?php echo date('M j, Y', strtotime($project['end_date'])); ?></div>
                            <div><strong>Joined:</strong> <?php echo date('M j, Y', strtotime($project['joined_date'])); ?></div>
                            <div><strong>Location:</strong> <?php echo htmlspecialchars($project['location']); ?></div>
                        </div>
                        
                        <div class="project-actions">
                            <?php if ($project['status'] === 'active'): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirmLeaveProject('<?php echo htmlspecialchars($project['title']); ?>')">
                                    <input type="hidden" name="action" value="leave_project">
                                    <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                                    <input type="hidden" name="confirmed" value="false" class="leave-confirmed">
                                    <button type="submit" class="btn btn-danger">Leave Project</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        function confirmProfileUpdate() {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            
            if (!name || !email) {
                alert('Name and email are required.');
                return false;
            }
            
            if (!isValidEmail(email)) {
                alert('Please enter a valid email address.');
                return false;
            }
            
            if (confirm('Update your profile information?')) {
                document.getElementById('profile_confirmed').value = 'true';
                return true;
            }
            
            return false;
        }

        function confirmLeaveProject(projectTitle) {
            if (confirm(`Are you sure you want to leave the project "${projectTitle}"? This action cannot be undone.`)) {
                event.target.querySelector('.leave-confirmed').value = 'true';
                return true;
            }
            return false;
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    </script>
</body>
</html>
