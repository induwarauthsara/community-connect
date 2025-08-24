<?php
/**
 * Community Connect - Admin Dashboard
 * Simple dashboard for administrators (MVP placeholder)
 */

require_once 'config/database.php';
require_once 'includes/common.php';

// Require admin login
requireRole('admin');

$user = getCurrentUser();
$page_title = 'Admin Dashboard - Community Connect';

// Get basic statistics
$total_users = getSingleRecord("SELECT COUNT(*) as count FROM users")['count'] ?? 0;
$total_projects = getSingleRecord("SELECT COUNT(*) as count FROM projects")['count'] ?? 0;
$pending_projects = getSingleRecord("SELECT COUNT(*) as count FROM projects WHERE status = 'pending'")['count'] ?? 0;

include 'includes/header.php';
?>

<div class="container">
    <div class="card">
        <h1>Welcome, <?php echo sanitizeInput($user['name']); ?>!</h1>
        <p class="text-muted">Administrator Dashboard</p>
        
        <div class="alert alert-info">
            <strong>MVP Notice:</strong> This is a simple placeholder dashboard for the login branch. 
            Full admin functionality will be implemented in the admin branch.
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
            <div class="card" style="background: #e3f2fd;">
                <h3 style="color: #1565c0;">Total Users</h3>
                <h2 style="color: #1565c0; margin: 10px 0;"><?php echo $total_users; ?></h2>
                <p class="text-muted">Registered users</p>
            </div>
            
            <div class="card" style="background: #f3e5f5;">
                <h3 style="color: #7b1fa2;">Total Projects</h3>
                <h2 style="color: #7b1fa2; margin: 10px 0;"><?php echo $total_projects; ?></h2>
                <p class="text-muted">All projects</p>
            </div>
            
            <div class="card" style="background: #fff3e0;">
                <h3 style="color: #f57c00;">Pending Approval</h3>
                <h2 style="color: #f57c00; margin: 10px 0;"><?php echo $pending_projects; ?></h2>
                <p class="text-muted">Projects awaiting review</p>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
            <div class="card">
                <h3>Quick Actions</h3>
                <p>Manage system users and approve projects.</p>
                <a href="#" class="btn-primary" onclick="alert('User management will be available in the admin branch.')">Manage Users</a>
            </div>
            
            <div class="card">
                <h3>Project Approval</h3>
                <p>Review and approve pending projects.</p>
                <a href="#" class="btn-primary" onclick="alert('Project approval will be available in the admin branch.')">Review Projects</a>
            </div>
            
            <div class="card">
                <h3>Your Profile</h3>
                <p><strong>Email:</strong> <?php echo sanitizeInput($user['email']); ?></p>
                <p><strong>Role:</strong> <?php echo getRoleBadge($user['role']); ?></p>
                <a href="#" class="btn-secondary" onclick="alert('Profile editing will be available in the functionalities branch.')">Edit Profile</a>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <a href="logout.php" class="btn-secondary" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
