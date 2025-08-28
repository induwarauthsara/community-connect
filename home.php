<?php
/**
 * Community Connect - Home Page
 * Redirects to main landing page
 */

// Redirect to main index page
header("Location: index.php");
exit();
?>
            );
            $stats['user_projects'] = $user_projects['count'];
        }
        
        // Get recent announcements
        $recent_announcements = getMultipleRecords(
            "SELECT title, content, type FROM announcements 
             WHERE is_active = TRUE AND (target_audience = 'all' OR target_audience = ?) 
             AND (start_date IS NULL OR start_date <= CURDATE()) 
             AND (end_date IS NULL OR end_date >= CURDATE()) 
             ORDER BY created_at DESC LIMIT 3", 
            [$userRole]
        );
        $stats['announcements'] = $recent_announcements;
        
    } catch (Exception $e) {
        // If there's an error getting stats, continue without them
        error_log("Error getting home page stats: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Connect - Home</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>Community Connect</h1>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <div class="content-box">
            <?php if (!$isLoggedIn): ?>
                <!-- Content for Non-Logged In Users -->
                <div class="welcome-message">
                    <h2>Welcome to Community Connect!</h2>
                    <p>Join our community platform to connect with volunteers, participate in events, and make a difference in your community.</p>
                    <p>Please log in to access all features or register if you're new to our platform.</p>
                </div>

                <div class="auth-links">
                    <a href="login.html" class="auth-link">Login</a>
                    <a href="signup.html" class="auth-link secondary">Register</a>
                </div>

            <?php else: ?>
                <!-- Content for Logged In Users -->
                <div class="welcome-message">
                    <h2>Welcome back, <?php echo htmlspecialchars($username); ?>!</h2>
                    <p>Great to see you again. Explore our features and stay connected with your community.</p>
                </div>

                <!-- User Info Section -->
                <div class="user-info">
                    <p>
                        Logged in as: <strong><?php echo htmlspecialchars($username); ?></strong>
                        <?php if ($userRole): ?>
                            <span class="role-badge <?php echo $userRole === 'admin' ? 'admin-badge' : ''; ?>">
                                <?php echo htmlspecialchars($userRole); ?>
                            </span>
                        <?php endif; ?>
                    </p>
                    <p style="font-size: 0.9rem; color: #666; margin-top: 5px;">
                        <?php echo htmlspecialchars($userEmail); ?>
                    </p>
                </div>

                <!-- Statistics Dashboard -->
                <?php if (!empty($stats)): ?>
                <div class="stats-section">
                    <h3>Dashboard Overview</h3>
                    <div class="stats-grid">
                        <?php if (isset($stats['active_projects'])): ?>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['active_projects']; ?></div>
                            <div class="stat-label">Active Projects</div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($stats['total_organizations'])): ?>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['total_organizations']; ?></div>
                            <div class="stat-label">Organizations</div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($stats['user_projects']) && $userRole === 'volunteer'): ?>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['user_projects']; ?></div>
                            <div class="stat-label">My Projects</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Announcements -->
                <?php if (!empty($stats['announcements'])): ?>
                <div class="announcements-section">
                    <h3>📢 Recent Announcements</h3>
                    <?php foreach ($stats['announcements'] as $announcement): ?>
                    <div class="announcement-card <?php echo $announcement['type']; ?>">
                        <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                        <p><?php echo htmlspecialchars(substr($announcement['content'], 0, 150)); ?>
                        <?php echo strlen($announcement['content']) > 150 ? '...' : ''; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Navigation Bar -->
                <div class="nav-bar">
                    <div class="nav-links">
                        <a href="home.php" class="nav-link active">🏠 Home</a>
                        <a href="projects.php" class="nav-link">📋 Projects</a>
                        <a href="organizations.php" class="nav-link">🏢 Organizations</a>
                        <a href="profile.php" class="nav-link">👤 Profile</a>
                        <?php if ($userRole === 'admin'): ?>
                            <a href="admin_panel.php" class="nav-link admin-link">⚙️ Admin Panel</a>
                        <?php endif; ?>
                        <a href="logout.php" class="nav-link logout-link">🚪 Logout</a>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <script>
        // Pass PHP data to JavaScript
        window.userSessionData = {
            isLoggedIn: <?php echo $isLoggedIn ? 'true' : 'false'; ?>,
            username: <?php echo $isLoggedIn ? "'" . addslashes($username) . "'" : 'null'; ?>,
            role: <?php echo ($isLoggedIn && $userRole) ? "'" . addslashes($userRole) . "'" : 'null'; ?>
        };
    </script>
    <script src="home.js"></script>
</body>
</html>
