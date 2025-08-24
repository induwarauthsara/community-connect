<?php
/**
 * Community Connect - Organization Dashboard
 * Simple dashboard for organizations (MVP placeholder)
 */

require_once 'config/database.php';
require_once 'includes/common.php';

// Require organization login
requireRole('organization');

$user = getCurrentUser();
$page_title = 'Organization Dashboard - Community Connect';

include 'includes/header.php';
?>

<div class="container">
    <div class="card">
        <h1>Welcome, <?php echo sanitizeInput($user['name']); ?>!</h1>
        <p class="text-muted">Organization Dashboard</p>
        
        <div class="alert alert-info">
            <strong>MVP Notice:</strong> This is a simple placeholder dashboard for the login branch. 
            Full dashboard functionality will be implemented in the functionalities branch.
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
            <div class="card">
                <h3>Quick Actions</h3>
                <p>Create and manage your volunteer projects.</p>
                <a href="#" class="btn-primary" onclick="alert('Project creation will be available in the functionalities branch.')">Create Project</a>
            </div>
            
            <div class="card">
                <h3>Your Profile</h3>
                <p><strong>Email:</strong> <?php echo sanitizeInput($user['email']); ?></p>
                <p><strong>Role:</strong> <?php echo getRoleBadge($user['role']); ?></p>
                <a href="#" class="btn-secondary" onclick="alert('Profile editing will be available in the functionalities branch.')">Edit Profile</a>
            </div>
            
            <div class="card">
                <h3>Project Management</h3>
                <p>View and manage your submitted projects.</p>
                <a href="#" class="btn-secondary" onclick="alert('Project management will be available in the functionalities branch.')">Manage Projects</a>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <a href="logout.php" class="btn-secondary" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
