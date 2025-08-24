<?php
/**
 * Community Connect - User Login Processing
 * Handles user authentication with role-based redirection
 */

require_once 'config/database.php';
require_once 'includes/common.php';

// Start secure session
startSecureSession();

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user) {
        redirectWithMessage(getDashboardUrl($user['role']), '', 'info');
    }
}

$page_title = 'Login - Community Connect';
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!isValidEmail($email)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        $user = getSingleRecord(
            "SELECT user_id, name, email, password, role, is_active 
             FROM users WHERE email = ? LIMIT 1",
            [$email]
        );

        if ($user) {
            if (!$user['is_active']) {
                $error_message = 'Account is inactive. Please contact administrator.';
            } elseif (verifyPassword($password, $user['password'])) {
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];

                logUserActivity($user['user_id'], 'login', 'User logged in');

                redirectWithMessage(
                    getDashboardUrl($user['role']),
                    'Welcome back, ' . sanitizeInput($user['name']) . '!',
                    'success'
                );
            } else {
                $error_message = 'Incorrect password. Please try again.';
            }
        } else {
            $error_message = 'No account found with this email.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
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
                        <div class="alert alert-danger">
                            <?php echo sanitizeInput($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <?php echo sanitizeInput($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php" onsubmit="return validateLoginForm(this)">
                        <div class="form-group mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?php echo isset($email) ? sanitizeInput($email) : ''; ?>"
                                   placeholder="Enter your email">
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required
                                   placeholder="Enter your password">
                        </div>

                        <button type="submit" class="btn btn-primary"style="width:100%;">
                            Login
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
                        <p><a href="forgot.php">Forgot Password?</a></p>
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

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address.');
        return false;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Logging in...';
    return true;
}
</script>

<?php include 'includes/footer.php'; ?>
