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
        max-width: 450px;
        margin: 80px auto;
        padding: 0 20px;
    }
    
    .login-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 25px;
        padding: 50px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.3);
        position: relative;
        overflow: hidden;
    }
    
    .login-card:before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, var(--primary-blue), var(--dark-blue));
        border-radius: 25px 25px 0 0;
    }
    
    .login-logo {
        width: 90px;
        height: 90px;
        margin: 0 auto 25px;
        border-radius: 50%;
        border: 3px solid var(--primary-blue);
        padding: 5px;
        transition: all 0.3s ease;
    }
    
    .login-logo:hover {
        transform: scale(1.1) rotate(10deg);
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }
    
    .login-title {
        color: var(--dark-blue);
        margin-bottom: 15px;
        font-size: 2.2rem;
        font-weight: 700;
        background: linear-gradient(45deg, var(--primary-blue), var(--dark-blue));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .login-subtitle {
        color: var(--gray);
        margin-bottom: 40px;
        font-size: 1.1rem;
        font-weight: 400;
    }
    
    .form-group {
        text-align: left;
        margin-bottom: 25px;
    }
    
    .login-btn {
        width: 100%;
        padding: 16px;
        margin-bottom: 25px;
        font-size: 16px;
        font-weight: 600;
    }
    
    .divider {
        margin: 40px 0;
        text-align: center;
        position: relative;
        color: var(--gray);
        font-weight: 500;
    }
    
    .divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, transparent, var(--border), transparent);
    }
    
    .divider span {
        background: white;
        padding: 0 20px;
        position: relative;
        z-index: 1;
    }
    
    .signup-link {
        color: var(--primary-blue);
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .signup-link:hover {
        text-decoration: underline;
        color: var(--dark-blue);
        transform: translateY(-1px);
    }
    
    .back-link {
        color: var(--gray);
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .back-link:hover {
        color: var(--primary-blue);
        transform: translateX(-3px);
    }
</style>

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
