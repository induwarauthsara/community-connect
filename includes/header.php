<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current user if logged in
$current_user = null;
if (isset($_SESSION['user_id'])) {
    $current_user = getCurrentUser();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Community Connect'); ?></title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="icon" href="assets/images/logo.png" type="image/png">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <img src="assets/images/logo.png" alt="Community Connect Logo">
                    <h1>Community Connect</h1>
                </a>
                
                <nav class="nav">
                    <?php if ($current_user): ?>
                        <a href="index.php">Home</a>
                        <?php if ($current_user['role'] === 'admin'): ?>
                            <a href="admin_dashboard.php">Dashboard</a>
                        <?php elseif ($current_user['role'] === 'organization'): ?>
                            <a href="organization_dashboard.php">Dashboard</a>
                        <?php elseif ($current_user['role'] === 'volunteer'): ?>
                            <a href="volunteer_dashboard.php">Dashboard</a>
                            <a href="browse_projects.php">Projects</a>
                        <?php endif; ?>
                        <a href="help.php">Help</a>
                        <a href="logout.php" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
                    <?php else: ?>
                        <a href="index.php">Home</a>
                        <a href="browse_projects.php">Projects</a>
                        <a href="help.php">Help</a>
                        <a href="login.php">Login</a>
                        <a href="signup.php">Sign Up</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="container fade-in">
