<?php
require_once 'config/database.php';
require_once 'includes/common.php';

$page_title = 'Community Connect - Volunteer Platform';

// Handle guest project submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_project'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $capacity = (int)($_POST['capacity'] ?? 0);
    
    if ($title && $description && $location && $start_date && $end_date) {
        $title = mysqli_real_escape_string($connection, $title);
        $description = mysqli_real_escape_string($connection, $description);
        $location = mysqli_real_escape_string($connection, $location);
        $start_date = mysqli_real_escape_string($connection, $start_date);
        $end_date = mysqli_real_escape_string($connection, $end_date);
        
        $sql = "INSERT INTO projects (title, description, location, start_date, end_date, capacity, created_by, status) 
                VALUES ('$title', '$description', '$location', '$start_date', '$end_date', $capacity, NULL, 'pending')";
        
        if (mysqli_query($connection, $sql)) {
            // Redirect to prevent double submission on refresh (Post-Redirect-Get pattern)
            header('Location: index.php?success=1');
            exit();
        } else {
            $error = "Failed to submit project. Error: " . mysqli_error($connection);
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Handle success message from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = "Thank you! Your project has been submitted for admin review.";
}

// Get approved projects for showcase
$projects_query = "SELECT p.*, u.name as creator_name FROM projects p 
                   LEFT JOIN users u ON p.created_by = u.user_id 
                   WHERE p.status IN ('approved', 'active') 
                   ORDER BY p.created_at DESC LIMIT 6";
$projects_result = mysqli_query($connection, $projects_query);

// Get statistics
$stats = [];

// Total projects
$result = mysqli_query($connection, "SELECT COUNT(*) as count FROM projects WHERE status IN ('approved', 'active')");
$row = mysqli_fetch_assoc($result);
$stats['total_projects'] = $row['count'] ?? 0;

// Total volunteers
$result = mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE role = 'volunteer'");
$row = mysqli_fetch_assoc($result);
$stats['total_volunteers'] = $row['count'] ?? 0;

// Active assignments
$result = mysqli_query($connection, "SELECT COUNT(*) as count FROM volunteer_projects");
$row = mysqli_fetch_assoc($result);
$stats['active_assignments'] = $row['count'] ?? 0;

include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/index.css">

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <?php if (isLoggedIn()): ?>
            <?php 
            $current_user = getCurrentUser();
            $user_name = htmlspecialchars($current_user['name'] ?? 'User');
            ?>
            <h1>Welcome back, <?php echo $user_name; ?>!</h1>
            <p>Ready to make a difference in your community today?</p>
            <div class="cta-buttons">
                <?php if ($current_user['role'] === 'admin'): ?>
                    <a href="admin_dashboard.php" class="btn btn-outline pulse">Go to Dashboard</a>
                    <a href="browse_projects.php" class="btn btn-outline">Browse Projects</a>
                <?php elseif ($current_user['role'] === 'organization'): ?>
                    <a href="organization_dashboard.php" class="btn btn-outline pulse">Manage Projects</a>
                    <a href="browse_projects.php" class="btn btn-outline">Browse Projects</a>
                <?php elseif ($current_user['role'] === 'volunteer'): ?>
                    <a href="volunteer_dashboard.php" class="btn btn-outline pulse">My Dashboard</a>
                    <a href="browse_projects.php" class="btn btn-outline">Find Projects</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <h1>Community Connect</h1>
            <p>Building stronger communities through volunteer coordination</p>
            <div class="cta-buttons">
                <a href="signup.php" class="btn btn-outline pulse">Join as Volunteer</a>
                <a href="login.php" class="btn btn-outline">Login</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Statistics Section -->
<section class="stats-section slide-in-left">
    <h2>Making an Impact Together</h2>
    <p class="text-muted">Join thousands of volunteers making a difference in communities worldwide</p>
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-number"><?php echo $stats['total_projects']; ?></div>
            <div class="stat-label">Active Projects</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $stats['total_volunteers']; ?></div>
            <div class="stat-label">Volunteers</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $stats['active_assignments']; ?></div>
            <div class="stat-label">Project Assignments</div>
        </div>
    </div>
</section>

<!-- Projects Showcase -->
<section class="projects-showcase slide-in-right">
    <h2>Current Volunteer Opportunities</h2>
    <p class="text-center text-muted">Discover amazing projects where you can make a real difference</p>
    
    <?php if (mysqli_num_rows($projects_result) > 0): ?>
        <div class="projects-grid">
            <?php while ($project = mysqli_fetch_assoc($projects_result)): ?>
                <div class="project-card fade-in">
                    <h3 class="project-title"><?php echo htmlspecialchars($project['title']); ?></h3>
                    <div class="project-meta">
                        📍 <?php echo htmlspecialchars($project['location']); ?> • 
                        📅 <?php echo date('M j, Y', strtotime($project['start_date'])); ?>
                        <?php if ($project['capacity'] > 0): ?>
                            • 👥 <?php echo $project['capacity']; ?> volunteers needed
                        <?php endif; ?>
                    </div>
                    <div class="project-description">
                        <?php echo htmlspecialchars(substr($project['description'], 0, 150)); ?>...
                    </div>
                    <div class="project-footer">
                        <small class="text-muted">
                            By: <?php echo htmlspecialchars($project['creator_name'] ?? 'Community'); ?></small>
                        <a href="login.php" class="btn btn-primary">Join Project</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="browse_projects.php" class="btn btn-outline">View All Projects</a>
        </div>
    <?php else: ?>
        <div class="card text-center">
            <h3>No projects available yet</h3>
            <p class="text-muted">Be the first to submit a volunteer project!</p>
        </div>
    <?php endif; ?>
</section>

<!-- Guest Project Submission -->
<section class="guest-submission">
    <div class="text-center mb-4">
        <h2>Have a Volunteer Project Idea?</h2>
        <p class="text-muted">Submit your project idea and we'll review it for our community.</p>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" class="submission-form">
        <div class="form-group">
            <label for="title">Project Title *</label>
            <input type="text" id="title" name="title" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description *</label>
            <textarea id="description" name="description" class="form-control" rows="4" required 
                      placeholder="Describe your volunteer project, what help is needed, and the impact it will make..."></textarea>
        </div>
        
        <div class="form-group">
            <label for="location">Location *</label>
            <input type="text" id="location" name="location" class="form-control" required
                   placeholder="Where will this project take place?">
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="start_date">Start Date *</label>
                <input type="date" id="start_date" name="start_date" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="end_date">End Date *</label>
                <input type="date" id="end_date" name="end_date" class="form-control" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="capacity">Volunteers Needed</label>
            <input type="number" id="capacity" name="capacity" class="form-control" min="1" 
                   placeholder="How many volunteers do you need?">
        </div>
        
        <div class="text-center">
            <button type="submit" name="submit_project" class="btn btn-primary pulse">
                🚀 Submit Project for Review
            </button>
        </div>
    </form>
</section>

<!-- Call to Action -->
<section class="cta-section fade-in">
    <h2>Ready to Make a Difference?</h2>
    <p>Join our community of volunteers and organizations working together to create positive change.</p>
    <div class="cta-buttons">
        <a href="signup.php" class="btn btn-primary bounce">🌟 Sign Up Now</a>
        <a href="help.php" class="btn btn-secondary">📚 Learn More</a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
