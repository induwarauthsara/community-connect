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
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // Simple database query without password hashing
        $email = mysqli_real_escape_string($connection, $email);
        $result = mysqli_query($connection, "SELECT * FROM users WHERE email = '$email'");
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
            $error_message = 'Invalid email or password.';
        }
    }
}

include 'includes/header.php';
?>

<style>
    .login-container {
        max-width: 400px;
        margin: 50px auto;
        padding: 0 20px;
    }
    
    .login-card {
        background: white;
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .login-logo {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        border-radius: 50%;
    }
    
    .login-title {
        color: var(--dark-blue);
        margin-bottom: 10px;
        font-size: 1.8rem;
    }
    
    .login-subtitle {
        color: var(--gray);
        margin-bottom: 30px;
    }
    
    .form-group {
        text-align: left;
        margin-bottom: 20px;
    }
    
    .login-btn {
        width: 100%;
        padding: 12px;
        margin-bottom: 20px;
    }
    
    .divider {
        margin: 30px 0;
        text-align: center;
        position: relative;
        color: var(--gray);
    }
    
    .divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: var(--border);
    }
    
    .divider span {
        background: white;
        padding: 0 15px;
    }
    
    .signup-link {
        color: var(--primary-blue);
        text-decoration: none;
        font-weight: 500;
    }
    
    .signup-link:hover {
        text-decoration: underline;
    }
</style>

<div class="login-container">
    <div class="login-card">
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
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                       placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required
                       placeholder="Enter your password">
            </div>

            <button type="submit" class="btn btn-primary login-btn">
                Sign In
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
            <a href="index.php" class="text-muted">← Back to Home</a>
        </p>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
