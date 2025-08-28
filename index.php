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
                VALUES ('$title', '$description', '$location', '$start_date', '$end_date', $capacity, 0, 'pending')";
        
        if (mysqli_query($connection, $sql)) {
            $message = "Thank you! Your project has been submitted for admin review.";
        } else {
            $error = "Failed to submit project. Please try again.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
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

<style>
    .hero {
        background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
        color: white;
        text-align: center;
        padding: 80px 0;
        margin: -20px -20px 40px -20px;
        border-radius: 0 0 20px 20px;
    }
    
    .hero h1 {
        font-size: 3rem;
        margin-bottom: 20px;
        font-weight: 700;
    }
    
    .hero p {
        font-size: 1.3rem;
        margin-bottom: 30px;
        opacity: 0.9;
    }
    
    .stats-section {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        padding: 40px;
        margin: 40px 0;
        text-align: center;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 30px;
        margin-top: 30px;
    }
    
    .stat-item {
        padding: 20px;
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: var(--primary-blue);
        margin-bottom: 10px;
    }
    
    .stat-label {
        color: var(--gray);
        font-size: 1.1rem;
    }
    
    .projects-showcase {
        margin: 50px 0;
    }
    
    .projects-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }
    
    .project-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .project-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .project-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--dark-blue);
        margin-bottom: 10px;
    }
    
    .project-meta {
        color: var(--gray);
        font-size: 0.9rem;
        margin-bottom: 15px;
    }
    
    .project-description {
        color: #555;
        line-height: 1.6;
        margin-bottom: 15px;
    }
    
    .project-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid var(--border);
    }
    
    .guest-submission {
        background: linear-gradient(135deg, #f8f9fa, white);
        border: 2px solid var(--border);
        border-radius: 15px;
        padding: 40px;
        margin: 50px 0;
    }
    
    .submission-form {
        max-width: 600px;
        margin: 0 auto;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    .cta-section {
        background: var(--light-blue);
        border-radius: 15px;
        padding: 50px;
        text-align: center;
        margin: 50px 0;
    }
    
    .cta-buttons {
        display: flex;
        gap: 20px;
        justify-content: center;
        margin-top: 30px;
    }
    
    @media (max-width: 768px) {
        .hero h1 { font-size: 2rem; }
        .hero p { font-size: 1.1rem; }
        .form-row { grid-template-columns: 1fr; }
        .cta-buttons { flex-direction: column; align-items: center; }
    }
</style>

<!-- Hero Section -->
<section class="hero">
    <h1>Community Connect</h1>
    <p>Building stronger communities through volunteer coordination</p>
    <div class="cta-buttons">
        <a href="signup.php" class="btn btn-outline">Join as Volunteer</a>
        <a href="login.php" class="btn btn-outline">Login</a>
    </div>
</section>

<!-- Statistics Section -->
<section class="stats-section">
    <h2>Making an Impact Together</h2>
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
<section class="projects-showcase">
    <h2 class="text-center">Current Volunteer Opportunities</h2>
    
    <?php if (mysqli_num_rows($projects_result) > 0): ?>
        <div class="projects-grid">
            <?php while ($project = mysqli_fetch_assoc($projects_result)): ?>
                <div class="project-card">
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
            <button type="submit" name="submit_project" class="btn btn-primary" 
                    onclick="return confirm('Submit this project for review?')">
                Submit Project for Review
            </button>
        </div>
    </form>
</section>

<!-- Call to Action -->
<section class="cta-section">
    <h2>Ready to Make a Difference?</h2>
    <p>Join our community of volunteers and organizations working together to create positive change.</p>
    <div class="cta-buttons">
        <a href="signup.php" class="btn btn-primary">Sign Up Now</a>
        <a href="help.php" class="btn btn-secondary">Learn More</a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
