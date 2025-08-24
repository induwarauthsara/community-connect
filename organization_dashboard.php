<?php
require_once 'config/database.php';

// Start secure session and require organization role
startSecureSession();
requireRole('organization');

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get organization data
$organization = getSingleRecord("
    SELECT o.*, u.name as creator_name, u.email
    FROM organizations o
    JOIN users u ON o.created_by = u.user_id
    WHERE o.created_by = ?
", [$user_id]);

if (!$organization) {
    // Create organization profile if doesn't exist
    header('Location: organization_setup.php');
    exit;
}

$org_id = $organization['org_id'];

// Get organization's projects
$projects = getMultipleRecords("
    SELECT p.*, 
           (SELECT COUNT(*) FROM volunteer_projects vp WHERE vp.project_id = p.project_id AND vp.status = 'active') as current_volunteers
    FROM projects p
    WHERE p.organization_id = ?
    ORDER BY p.created_at DESC
", [$org_id]);

// Handle project creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_project') {
    if ($_POST['confirmed'] !== 'true') {
        die('Error: Action requires confirmation');
    }
    
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $category = sanitizeInput($_POST['category']);
    $location = sanitizeInput($_POST['location']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $volunteers_needed = (int)$_POST['volunteers_needed'];
    $skills_needed = sanitizeInput($_POST['skills_needed']);
    $time_commitment = sanitizeInput($_POST['time_commitment']);
    $requirements = sanitizeInput($_POST['requirements']);
    
    // Validation
    if (empty($title) || empty($description) || empty($location) || empty($start_date) || empty($end_date)) {
        $error = 'Please fill in all required fields.';
    } elseif ($volunteers_needed < 1) {
        $error = 'Number of volunteers needed must be at least 1.';
    } elseif (strtotime($start_date) >= strtotime($end_date)) {
        $error = 'End date must be after start date.';
    } elseif (strtotime($start_date) < strtotime(date('Y-m-d'))) {
        $error = 'Start date cannot be in the past.';
    } else {
        try {
            $project_id = insertRecord("
                INSERT INTO projects (
                    title, description, category, location, start_date, end_date,
                    volunteers_needed, skills_needed, time_commitment, requirements,
                    organization_id, created_by, status, is_approved, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 1, NOW())
            ", [$title, $description, $category, $location, $start_date, $end_date,
                $volunteers_needed, $skills_needed, $time_commitment, $requirements,
                $org_id, $user_id]);
            
            if ($project_id) {
                logActivity('created_project', 'projects', $project_id);
                $success = 'Project created successfully!';
                // Refresh projects list
                $projects = getMultipleRecords("
                    SELECT p.*, 
                           (SELECT COUNT(*) FROM volunteer_projects vp WHERE vp.project_id = p.project_id AND vp.status = 'active') as current_volunteers
                    FROM projects p
                    WHERE p.organization_id = ?
                    ORDER BY p.created_at DESC
                ", [$org_id]);
            } else {
                $error = 'Failed to create project. Please try again.';
            }
        } catch (Exception $e) {
            error_log("Create project error: " . $e->getMessage());
            $error = 'An error occurred while creating the project.';
        }
    }
}

// Handle project update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_project') {
    if ($_POST['confirmed'] !== 'true') {
        die('Error: Action requires confirmation');
    }
    
    $project_id = (int)$_POST['project_id'];
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $category = sanitizeInput($_POST['category']);
    $location = sanitizeInput($_POST['location']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $volunteers_needed = (int)$_POST['volunteers_needed'];
    $skills_needed = sanitizeInput($_POST['skills_needed']);
    $time_commitment = sanitizeInput($_POST['time_commitment']);
    $requirements = sanitizeInput($_POST['requirements']);
    
    // Validation
    if (empty($title) || empty($description) || empty($location) || empty($start_date) || empty($end_date)) {
        $error = 'Please fill in all required fields.';
    } elseif ($volunteers_needed < 1) {
        $error = 'Number of volunteers needed must be at least 1.';
    } elseif (strtotime($start_date) >= strtotime($end_date)) {
        $error = 'End date must be after start date.';
    } else {
        try {
            $updated = updateRecord("
                UPDATE projects SET 
                title = ?, description = ?, category = ?, location = ?, 
                start_date = ?, end_date = ?, volunteers_needed = ?, 
                skills_needed = ?, time_commitment = ?, requirements = ?
                WHERE project_id = ? AND organization_id = ?
            ", [$title, $description, $category, $location, $start_date, $end_date,
                $volunteers_needed, $skills_needed, $time_commitment, $requirements,
                $project_id, $org_id]);
            
            if ($updated) {
                logActivity('updated_project', 'projects', $project_id);
                $success = 'Project updated successfully!';
                // Refresh projects list
                $projects = getMultipleRecords("
                    SELECT p.*, 
                           (SELECT COUNT(*) FROM volunteer_projects vp WHERE vp.project_id = p.project_id AND vp.status = 'active') as current_volunteers
                    FROM projects p
                    WHERE p.organization_id = ?
                    ORDER BY p.created_at DESC
                ", [$org_id]);
            } else {
                $error = 'Failed to update project. Please try again.';
            }
        } catch (Exception $e) {
            error_log("Update project error: " . $e->getMessage());
            $error = 'An error occurred while updating the project.';
        }
    }
}

// Handle project deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_project') {
    if ($_POST['confirmed'] !== 'true') {
        die('Error: Action requires confirmation');
    }
    
    $project_id = (int)$_POST['project_id'];
    
    try {
        // First delete volunteer assignments
        deleteRecord("DELETE FROM volunteer_projects WHERE project_id = ?", [$project_id]);
        
        // Then delete the project
        $deleted = deleteRecord("DELETE FROM projects WHERE project_id = ? AND organization_id = ?", [$project_id, $org_id]);
        
        if ($deleted) {
            logActivity('deleted_project', 'projects', $project_id);
            $success = 'Project deleted successfully!';
            // Refresh projects list
            $projects = getMultipleRecords("
                SELECT p.*, 
                       (SELECT COUNT(*) FROM volunteer_projects vp WHERE vp.project_id = p.project_id AND vp.status = 'active') as current_volunteers
                FROM projects p
                WHERE p.organization_id = ?
                ORDER BY p.created_at DESC
            ", [$org_id]);
        } else {
            $error = 'Failed to delete project. Please try again.';
        }
    } catch (Exception $e) {
        error_log("Delete project error: " . $e->getMessage());
        $error = 'An error occurred while deleting the project.';
    }
}

// Get project for editing if requested
$edit_project = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_project = getSingleRecord("
        SELECT * FROM projects 
        WHERE project_id = ? AND organization_id = ?
    ", [(int)$_GET['edit'], $org_id]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Dashboard - Community Connect</title>
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

        .org-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
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

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
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

        .required {
            color: #dc3545;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
        }

        .stat-label {
            color: #666;
            margin-top: 0.5rem;
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

            .org-info {
                grid-template-columns: 1fr;
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
                <a href="organization_dashboard.php">Dashboard</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="dashboard-header">
            <h1><?php echo htmlspecialchars($organization['name']); ?></h1>
            <p><?php echo htmlspecialchars($organization['description'] ?? 'Organization Dashboard'); ?></p>
            
            <div class="org-info">
                <div><strong>Email:</strong> <?php echo htmlspecialchars($organization['email']); ?></div>
                <div><strong>Phone:</strong> <?php echo htmlspecialchars($organization['phone'] ?? 'Not provided'); ?></div>
                <div><strong>Website:</strong> 
                    <?php if ($organization['website']): ?>
                        <a href="<?php echo htmlspecialchars($organization['website']); ?>" target="_blank">
                            <?php echo htmlspecialchars($organization['website']); ?>
                        </a>
                    <?php else: ?>
                        Not provided
                    <?php endif; ?>
                </div>
                <div><strong>Member Since:</strong> <?php echo date('M Y', strtotime($organization['created_at'])); ?></div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($projects); ?></div>
                <div class="stat-label">Total Projects</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php echo count(array_filter($projects, function($p) { return $p['status'] === 'active'; })); ?>
                </div>
                <div class="stat-label">Active Projects</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php echo array_sum(array_column($projects, 'current_volunteers')); ?>
                </div>
                <div class="stat-label">Total Volunteers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php echo array_sum(array_column($projects, 'volunteers_needed')); ?>
                </div>
                <div class="stat-label">Volunteers Needed</div>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="dashboard-tabs">
            <button class="tab-button active" onclick="showTab('projects')">My Projects</button>
            <button class="tab-button" onclick="showTab('create')">
                <?php echo $edit_project ? 'Edit Project' : 'Create Project'; ?>
            </button>
        </div>

        <!-- Projects Tab -->
        <div id="projects" class="tab-content active">
            <h2>My Projects (<?php echo count($projects); ?>)</h2>
            
            <?php if (empty($projects)): ?>
                <p>You haven't created any projects yet. Create your first project to start engaging volunteers!</p>
            <?php else: ?>
                <?php foreach ($projects as $project): ?>
                    <div class="project-card">
                        <div class="project-header">
                            <div>
                                <div class="project-title"><?php echo htmlspecialchars($project['title']); ?></div>
                                <?php if ($project['category']): ?>
                                    <div style="color: #6c757d; font-size: 0.9rem;"><?php echo htmlspecialchars($project['category']); ?></div>
                                <?php endif; ?>
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
                            <div><strong>Location:</strong> <?php echo htmlspecialchars($project['location']); ?></div>
                            <div><strong>Volunteers:</strong> <?php echo $project['current_volunteers']; ?> / <?php echo $project['volunteers_needed']; ?></div>
                        </div>
                        
                        <div class="project-actions">
                            <a href="?edit=<?php echo $project['project_id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                            <a href="project_volunteers.php?id=<?php echo $project['project_id']; ?>" class="btn btn-primary btn-sm">View Volunteers</a>
                            <form method="POST" style="display: inline;" onsubmit="return confirmDeleteProject('<?php echo htmlspecialchars($project['title']); ?>')">
                                <input type="hidden" name="action" value="delete_project">
                                <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                                <input type="hidden" name="confirmed" value="false" class="delete-confirmed">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Create/Edit Project Tab -->
        <div id="create" class="tab-content">
            <h2><?php echo $edit_project ? 'Edit Project' : 'Create New Project'; ?></h2>
            
            <form method="POST" onsubmit="return confirmProjectSave()">
                <input type="hidden" name="action" value="<?php echo $edit_project ? 'update_project' : 'create_project'; ?>">
                <?php if ($edit_project): ?>
                    <input type="hidden" name="project_id" value="<?php echo $edit_project['project_id']; ?>">
                <?php endif; ?>
                <input type="hidden" id="project_confirmed" name="confirmed" value="false">
                
                <div class="form-group">
                    <label for="title">Project Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required 
                           value="<?php echo $edit_project ? htmlspecialchars($edit_project['title']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description <span class="required">*</span></label>
                    <textarea id="description" name="description" required rows="5"><?php echo $edit_project ? htmlspecialchars($edit_project['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="">Select Category</option>
                            <option value="Education" <?php echo ($edit_project && $edit_project['category'] === 'Education') ? 'selected' : ''; ?>>Education</option>
                            <option value="Environment" <?php echo ($edit_project && $edit_project['category'] === 'Environment') ? 'selected' : ''; ?>>Environment</option>
                            <option value="Health" <?php echo ($edit_project && $edit_project['category'] === 'Health') ? 'selected' : ''; ?>>Health</option>
                            <option value="Community" <?php echo ($edit_project && $edit_project['category'] === 'Community') ? 'selected' : ''; ?>>Community</option>
                            <option value="Arts & Culture" <?php echo ($edit_project && $edit_project['category'] === 'Arts & Culture') ? 'selected' : ''; ?>>Arts & Culture</option>
                            <option value="Technology" <?php echo ($edit_project && $edit_project['category'] === 'Technology') ? 'selected' : ''; ?>>Technology</option>
                            <option value="Sports & Recreation" <?php echo ($edit_project && $edit_project['category'] === 'Sports & Recreation') ? 'selected' : ''; ?>>Sports & Recreation</option>
                            <option value="Other" <?php echo ($edit_project && $edit_project['category'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location <span class="required">*</span></label>
                        <input type="text" id="location" name="location" required 
                               value="<?php echo $edit_project ? htmlspecialchars($edit_project['location']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="start_date">Start Date <span class="required">*</span></label>
                        <input type="date" id="start_date" name="start_date" required 
                               value="<?php echo $edit_project ? $edit_project['start_date'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date <span class="required">*</span></label>
                        <input type="date" id="end_date" name="end_date" required 
                               value="<?php echo $edit_project ? $edit_project['end_date'] : ''; ?>">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="volunteers_needed">Volunteers Needed <span class="required">*</span></label>
                        <input type="number" id="volunteers_needed" name="volunteers_needed" required min="1" 
                               value="<?php echo $edit_project ? $edit_project['volunteers_needed'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="time_commitment">Time Commitment</label>
                        <select id="time_commitment" name="time_commitment">
                            <option value="">Select Time Commitment</option>
                            <option value="one-time" <?php echo ($edit_project && $edit_project['time_commitment'] === 'one-time') ? 'selected' : ''; ?>>One-time</option>
                            <option value="weekly" <?php echo ($edit_project && $edit_project['time_commitment'] === 'weekly') ? 'selected' : ''; ?>>Weekly</option>
                            <option value="monthly" <?php echo ($edit_project && $edit_project['time_commitment'] === 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                            <option value="ongoing" <?php echo ($edit_project && $edit_project['time_commitment'] === 'ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="skills_needed">Skills Needed</label>
                    <textarea id="skills_needed" name="skills_needed" rows="3" 
                              placeholder="List the skills or qualifications needed for this project"><?php echo $edit_project ? htmlspecialchars($edit_project['skills_needed']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="requirements">Requirements</label>
                    <textarea id="requirements" name="requirements" rows="3" 
                              placeholder="Any special requirements, background checks, training, etc."><?php echo $edit_project ? htmlspecialchars($edit_project['requirements']) : ''; ?></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $edit_project ? 'Update Project' : 'Create Project'; ?>
                    </button>
                    <?php if ($edit_project): ?>
                        <a href="organization_dashboard.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
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

        function confirmProjectSave() {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            const location = document.getElementById('location').value.trim();
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const volunteersNeeded = document.getElementById('volunteers_needed').value;
            
            if (!title || !description || !location || !startDate || !endDate || !volunteersNeeded) {
                alert('Please fill in all required fields.');
                return false;
            }
            
            if (parseInt(volunteersNeeded) < 1) {
                alert('Number of volunteers needed must be at least 1.');
                return false;
            }
            
            if (new Date(startDate) >= new Date(endDate)) {
                alert('End date must be after start date.');
                return false;
            }
            
            if (new Date(startDate) < new Date()) {
                alert('Start date cannot be in the past.');
                return false;
            }
            
            const action = <?php echo $edit_project ? '"Update"' : '"Create"'; ?>;
            if (confirm(`${action} project "${title}"?`)) {
                document.getElementById('project_confirmed').value = 'true';
                return true;
            }
            
            return false;
        }

        function confirmDeleteProject(projectTitle) {
            if (confirm(`Are you absolutely sure you want to delete the project "${projectTitle}"? This will also remove all volunteer assignments and cannot be undone.`)) {
                event.target.querySelector('.delete-confirmed').value = 'true';
                return true;
            }
            return false;
        }
    </script>
</body>
</html>
