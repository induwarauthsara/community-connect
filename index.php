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

<style>
    .hero {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        color: white;
        text-align: center;
        padding: 100px 0;
        margin: -20px -20px 60px -20px;
        border-radius: 0 0 50px 50px;
        position: relative;
        overflow: hidden;
    }
    
    .hero:before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.1)"><polygon points="0,100 1000,0 1000,100"/></svg>');
        background-size: cover;
        background-position: bottom;
    }
    
    .hero-content {
        position: relative;
        z-index: 2;
    }
    
    .hero h1 {
        font-size: 4rem;
        margin-bottom: 25px;
        font-weight: 800;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        animation: bounce 3s ease-in-out infinite;
        color: white;
    }
    
    .hero .welcome-message {
        font-size: 3.5rem;
        margin-bottom: 25px;
        font-weight: 800;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        color: white;
        animation: fadeInUp 1s ease-out;
    }
    
    .hero .user-name {
        color: #FFD700;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
    }
    
    .hero p {
        font-size: 1.4rem;
        margin-bottom: 40px;
        opacity: 0.95;
        font-weight: 300;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        color: white;
    }
    
    .hero .cta-buttons {
        display: flex;
        gap: 25px;
        justify-content: center;
        margin-top: 40px;
    }
    
    .hero .btn-outline {
        background: transparent;
        border: 3px solid white;
        color: white;
        font-weight: 700;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }
    
    .hero .btn-outline:hover {
        background: white;
        color: var(--primary-blue);
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(255, 255, 255, 0.3);
    }
    
    .stats-section {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 25px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        padding: 60px 40px;
        margin: 60px 0;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    .stats-section h2 {
        font-size: 2.5rem;
        margin-bottom: 20px;
        color: var(--dark-blue);
        font-weight: 700;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 40px;
        margin-top: 50px;
    }
    
    .stat-item {
        padding: 30px;
        background: white;
        border: 3px solid var(--primary-blue);
        border-radius: 20px;
        color: var(--dark-blue);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .stat-item:before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(0,123,255,0.05), transparent);
        transition: left 0.5s ease;
    }
    
    .stat-item:hover:before {
        left: 100%;
    }
    
    .stat-item:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: 0 15px 40px rgba(0, 123, 255, 0.4);
        background: var(--light-blue);
    }
    
    .stat-number {
        font-size: 3rem;
        font-weight: 900;
        margin-bottom: 15px;
        color: var(--primary-blue);
        text-shadow: none;
    }
    
    .stat-label {
        font-size: 1.2rem;
        font-weight: 500;
        opacity: 0.95;
    }
    
    .projects-showcase {
        margin: 80px 0;
    }
    
    .projects-showcase h2 {
        font-size: 2.8rem;
        margin-bottom: 20px;
        color: var(--dark-blue);
        font-weight: 700;
        text-align: center;
    }
    
    .projects-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
        gap: 30px;
        margin-top: 50px;
    }
    
    .project-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 20px;
        padding: 35px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.3);
        position: relative;
        overflow: hidden;
    }
    
    .project-card:before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: var(--primary-blue);
    }
    
    .project-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 25px 60px rgba(0,0,0,0.15);
    }
    
    .project-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark-blue);
        margin-bottom: 15px;
        line-height: 1.3;
    }
    
    .project-meta {
        color: var(--gray);
        font-size: 1rem;
        margin-bottom: 20px;
        font-weight: 500;
    }
    
    .project-description {
        color: #555;
        line-height: 1.7;
        margin-bottom: 25px;
        font-size: 15px;
    }
    
    .project-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid var(--border);
    }
    
    .guest-submission {
        background: linear-gradient(135deg, #fff 80%, #e3f2fd 100%);
        backdrop-filter: blur(20px);
        border: 2px solid var(--primary-blue);
        border-radius: 25px;
        padding: 60px;
        margin: 80px 0;
        box-shadow: 0 20px 60px rgba(0,0,0,0.1);
    }
    
    .guest-submission h2 {
        font-size: 2.5rem;
        color: var(--dark-blue);
        font-weight: 700;
        margin-bottom: 15px;
    }
    
    .submission-form {
        max-width: 700px;
        margin: 0 auto;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }
    
    .cta-section {
        background: linear-gradient(135deg, #e3f2fd 60%, #fff 100%);
        backdrop-filter: blur(20px);
        border-radius: 25px;
        padding: 80px 60px;
        text-align: center;
        margin: 80px 0;
        border: 1px solid var(--primary-blue);
    }
    
    .cta-section h2 {
        font-size: 2.8rem;
        color: var(--dark-blue);
        font-weight: 700;
        margin-bottom: 20px;
    }
    
    .cta-section p {
        font-size: 1.3rem;
        color: var(--gray);
        margin-bottom: 40px;
        font-weight: 400;
    }
    
    .cta-buttons {
        display: flex;
        gap: 25px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    /* Icon styles for meta information */
    .project-meta:before {
        content: "🌟";
        margin-right: 8px;
    }
    
    @media (max-width: 768px) {
        .hero h1 { font-size: 2.5rem; }
        .hero .welcome-message { font-size: 2.2rem; }
        .hero p { font-size: 1.2rem; }
        .hero { padding: 60px 20px; }
        .form-row { grid-template-columns: 1fr; }
        .cta-buttons { flex-direction: column; align-items: center; }
        .stats-grid { grid-template-columns: 1fr; gap: 30px; }
        .projects-grid { grid-template-columns: 1fr; }
        .cta-section { padding: 50px 30px; }
        .guest-submission { padding: 40px 30px; }
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

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
