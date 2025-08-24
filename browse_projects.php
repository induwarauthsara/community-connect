<?php
require_once 'config/database.php';

// Start secure session
startSecureSession();

// Get search parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$location = isset($_GET['location']) ? sanitizeInput($_GET['location']) : '';
$availability = isset($_GET['availability']) ? sanitizeInput($_GET['availability']) : '';

// Get user info if logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Get user's joined projects if volunteer
$joined_projects = [];
if ($user_id && $user_role === 'volunteer') {
    $joined_projects = getMultipleRecords("
        SELECT project_id FROM volunteer_projects WHERE volunteer_id = ?
    ", [$user_id]);
    $joined_projects = array_column($joined_projects, 'project_id');
}

// Build search query
$sql = "
    SELECT p.*, o.name as org_name, o.email as org_email,
           (SELECT COUNT(*) FROM volunteer_projects vp WHERE vp.project_id = p.project_id AND vp.status = 'active') as current_volunteers
    FROM projects p
    JOIN organizations org ON p.organization_id = org.org_id
    JOIN users o ON org.created_by = o.user_id
    WHERE p.status = 'active' AND p.is_approved = 1
";

$params = [];

if ($search) {
    $sql .= " AND (p.title LIKE ? OR p.description LIKE ? OR p.skills_needed LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($category) {
    $sql .= " AND p.category = ?";
    $params[] = $category;
}

if ($location) {
    $sql .= " AND p.location LIKE ?";
    $params[] = "%$location%";
}

if ($availability) {
    $sql .= " AND p.time_commitment LIKE ?";
    $params[] = "%$availability%";
}

$sql .= " ORDER BY p.created_at DESC";

$projects = getMultipleRecords($sql, $params);

// Get categories for filter
$categories = getMultipleRecords("
    SELECT DISTINCT category FROM projects 
    WHERE category IS NOT NULL AND category != '' AND status = 'active' AND is_approved = 1
    ORDER BY category
");

// Handle project join
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'join_project') {
    if (!$user_id || $user_role !== 'volunteer') {
        $error = 'Please login as a volunteer to join projects.';
    } elseif ($_POST['confirmed'] !== 'true') {
        die('Error: Action requires confirmation');
    } else {
        $project_id = (int)$_POST['project_id'];
        
        try {
            // Check if already joined
            $existing = getSingleRecord("
                SELECT * FROM volunteer_projects 
                WHERE volunteer_id = ? AND project_id = ?
            ", [$user_id, $project_id]);
            
            if ($existing) {
                $error = 'You have already joined this project.';
            } else {
                // Check if project is still accepting volunteers
                $project = getSingleRecord("SELECT * FROM projects WHERE project_id = ?", [$project_id]);
                $current_count = getSingleRecord("
                    SELECT COUNT(*) as count FROM volunteer_projects 
                    WHERE project_id = ? AND status = 'active'
                ", [$project_id])['count'];
                
                if ($current_count >= $project['volunteers_needed']) {
                    $error = 'This project has reached its volunteer capacity.';
                } else {
                    $join_id = insertRecord("
                        INSERT INTO volunteer_projects (volunteer_id, project_id, status, joined_date)
                        VALUES (?, ?, 'active', NOW())
                    ", [$user_id, $project_id]);
                    
                    if ($join_id) {
                        logActivity('joined_project', 'volunteer_projects', $project_id);
                        $success = 'Successfully joined the project!';
                        $joined_projects[] = $project_id; // Add to local array
                    } else {
                        $error = 'Failed to join project. Please try again.';
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Join project error: " . $e->getMessage());
            $error = 'An error occurred while joining the project.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Projects - Community Connect</title>
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

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            color: #007bff;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #666;
            font-size: 1.1rem;
        }

        .filters {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .filters h3 {
            color: #007bff;
            margin-bottom: 1rem;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
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

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        .project-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .project-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
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
            margin-bottom: 0.5rem;
        }

        .project-category {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .project-body {
            padding: 1.5rem;
        }

        .project-description {
            color: #333;
            margin-bottom: 1rem;
        }

        .project-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .meta-icon {
            width: 16px;
            height: 16px;
            background-color: #007bff;
            border-radius: 50%;
        }

        .project-skills {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }

        .project-skills h4 {
            color: #007bff;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .skills-list {
            color: #666;
            font-size: 0.9rem;
        }

        .project-footer {
            padding: 1.5rem;
            background-color: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .volunteer-count {
            color: #666;
            font-size: 0.9rem;
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

        .no-results {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-results h3 {
            color: #007bff;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .projects-grid {
                grid-template-columns: 1fr;
            }
            
            .project-meta {
                grid-template-columns: 1fr;
            }
            
            .project-footer {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">Community Connect</div>
            <nav class="nav">
                <a href="index.php">Home</a>
                <a href="browse_projects.php">Browse Projects</a>
                <?php if ($user_id): ?>
                    <?php if ($user_role === 'volunteer'): ?>
                        <a href="volunteer_dashboard.php">Dashboard</a>
                    <?php elseif ($user_role === 'organization'): ?>
                        <a href="organization_dashboard.php">Dashboard</a>
                    <?php elseif ($user_role === 'admin'): ?>
                        <a href="admin_dashboard.php">Admin</a>
                    <?php endif; ?>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="page-header">
            <h1>Browse Projects</h1>
            <p>Find volunteer opportunities that match your interests and skills</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="filters">
            <h3>Filter Projects</h3>
            <form class="filter-form" method="GET">
                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Keywords, skills, titles...">
                </div>
                
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>"
                                    <?php echo ($category === $cat['category']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" 
                           value="<?php echo htmlspecialchars($location); ?>"
                           placeholder="City, state...">
                </div>
                
                <div class="form-group">
                    <label for="availability">Time Commitment</label>
                    <select id="availability" name="availability">
                        <option value="">Any</option>
                        <option value="ongoing" <?php echo ($availability === 'ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                        <option value="weekly" <?php echo ($availability === 'weekly') ? 'selected' : ''; ?>>Weekly</option>
                        <option value="monthly" <?php echo ($availability === 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                        <option value="one-time" <?php echo ($availability === 'one-time') ? 'selected' : ''; ?>>One-time</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Search Projects</button>
                </div>
            </form>
        </div>

        <?php if (empty($projects)): ?>
            <div class="no-results">
                <h3>No Projects Found</h3>
                <p>Try adjusting your search criteria or check back later for new opportunities.</p>
                <a href="browse_projects.php" class="btn btn-primary">View All Projects</a>
            </div>
        <?php else: ?>
            <div class="projects-grid">
                <?php foreach ($projects as $project): ?>
                    <div class="project-card">
                        <div class="project-header">
                            <div class="project-title"><?php echo htmlspecialchars($project['title']); ?></div>
                            <div class="project-org">by <?php echo htmlspecialchars($project['org_name']); ?></div>
                            <?php if ($project['category']): ?>
                                <div class="project-category"><?php echo htmlspecialchars($project['category']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="project-body">
                            <div class="project-description">
                                <?php echo htmlspecialchars(substr($project['description'], 0, 150)) . (strlen($project['description']) > 150 ? '...' : ''); ?>
                            </div>
                            
                            <div class="project-meta">
                                <div class="meta-item">
                                    <div class="meta-icon"></div>
                                    <span><?php echo date('M j, Y', strtotime($project['start_date'])); ?></span>
                                </div>
                                <div class="meta-item">
                                    <div class="meta-icon"></div>
                                    <span><?php echo htmlspecialchars($project['location']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <div class="meta-icon"></div>
                                    <span><?php echo htmlspecialchars($project['time_commitment']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <div class="meta-icon"></div>
                                    <span><?php echo date('M j, Y', strtotime($project['end_date'])); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($project['skills_needed']): ?>
                                <div class="project-skills">
                                    <h4>Skills Needed:</h4>
                                    <div class="skills-list"><?php echo htmlspecialchars($project['skills_needed']); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="project-footer">
                            <div class="volunteer-count">
                                <?php echo $project['current_volunteers']; ?> / <?php echo $project['volunteers_needed']; ?> volunteers
                            </div>
                            
                            <?php if (!$user_id): ?>
                                <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary">Login to Join</a>
                            <?php elseif ($user_role !== 'volunteer'): ?>
                                <span class="btn btn-secondary" style="cursor: not-allowed;">Volunteers Only</span>
                            <?php elseif (in_array($project['project_id'], $joined_projects)): ?>
                                <span class="btn btn-success">Already Joined</span>
                            <?php elseif ($project['current_volunteers'] >= $project['volunteers_needed']): ?>
                                <span class="btn btn-secondary" style="cursor: not-allowed;">Full</span>
                            <?php else: ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirmJoinProject('<?php echo htmlspecialchars($project['title']); ?>')">
                                    <input type="hidden" name="action" value="join_project">
                                    <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                                    <input type="hidden" name="confirmed" value="false" class="join-confirmed">
                                    <button type="submit" class="btn btn-primary">Join Project</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        function confirmJoinProject(projectTitle) {
            if (confirm(`Join the project "${projectTitle}"? You'll be able to coordinate with the organization and other volunteers.`)) {
                event.target.querySelector('.join-confirmed').value = 'true';
                return true;
            }
            return false;
        }
    </script>
</body>
</html>
