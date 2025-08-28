<?php
/**
 * Community Connect - User Login
 * Simple authentication without password hashing
 */

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
        $sql = "SELECT * FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($connection, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
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
            mysqli_stmt_close($stmt);
        } else {
            $error_message = 'Database error. Please try again.';
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
                <?php echo sanitizeInput($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required
                       value="<?php echo isset($email) ? sanitizeInput($email) : ''; ?>"
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

                // Log activity
                logUserActivity($user['user_id'], 'login', 'User logged in');

                // Redirect to appropriate dashboard
                $dashboard_url = getDashboardUrl($user['role']);
                redirectWithMessage($dashboard_url, 'Welcome back, ' . sanitizeInput($user['name']) . '!', 'success');
            } else {
                $error_message = 'Incorrect password. Please try again.';
            }
        } else {
            $error_message = 'No account found with this email address.';
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">

                    <!-- Logo -->
                    <div class="text-center mb-4">
                        <img src="image/logo.png" alt="Community Connect Logo" 
                        style="width: 250px; height: auto;">
                        <p class="text-muted">Sign in to continue</p>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="alert alert-error">
                            <?php echo sanitizeInput($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <?php echo sanitizeInput($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php" onsubmit="return validateLoginForm(this)">
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?php echo isset($email) ? sanitizeInput($email) : ''; ?>"
                                   placeholder="Enter your email address">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required
                                   placeholder="Enter your password">
                        </div>

                        <button type="submit" class="btn-primary" style="width: 100%;">
                            Login
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="signup.html">Sign Up</a></p>
                        <p><a href="forgot.html">Forgot Password?</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function validateLoginForm(form) {
    const email = form.querySelector('input[name="email"]').value.trim();
    const password = form.querySelector('input[name="password"]').value;

    if (!email || !password) {
        alert('Please fill in all fields.');
        return false;
    }

    if (!isValidEmail(email)) {
        alert('Please enter a valid email address.');
        return false;
    }

    // Set loading state on submit button
    const submitBtn = form.querySelector('button[type="submit"]');
    setButtonLoading(submitBtn, 'Logging in...');
    
    return true;
}
</script>

<?php include 'includes/footer.php'; ?>
