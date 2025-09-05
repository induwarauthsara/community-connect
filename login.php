  <?php
require_once 'config/database.php';
require_once 'includes/common.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user) {
        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } elseif ($user['role'] === 'organization') {
            header("Location: organization_dashboard.php");
        } else {
            header("Location: volunteer_dashboard.php");
        }
        exit();
    }
}

$page_title = 'Login - Community Connect';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        $username = mysqli_real_escape_string($connection, $username);
        $result = mysqli_query($connection, "SELECT * FROM users WHERE username = '$username'");
        $user = mysqli_fetch_assoc($result);
        
        if ($user && $user['password'] === $password) {
            // Login successful
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];

            // Redirect to appropriate dashboard
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($user['role'] === 'organization') {
                header("Location: organization_dashboard.php");
            } else {
                header("Location: volunteer_dashboard.php");
            }
            exit();
        } else {
            $error_message = 'Invalid username or password.';
        }
    }
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/login.css">

<div class="login-container fade-in">
    <div class="login-card slide-in-left">
        <img src="assets/images/logo.png" alt="Community Connect Logo" class="login-logo">
        <h1 class="login-title">Welcome Back</h1>
        <p class="login-subtitle">Sign in to your account</p>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required
                       value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>"
                       placeholder="Enter your username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required
                       placeholder="Enter your password">
            </div>

            <button type="submit" class="btn btn-primary login-btn pulse">
                🚀 Sign In
            </button>
        </form>

        <div class="divider">
            <span>or</span>
        </div>

        <p>
            Don't have an account? 
            <a href="signup.php" class="signup-link">Create one here</a>
        </p>
        
        <p class="mt-3">
            <a href="index.php" class="back-link">← Back to Home</a>
        </p>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
