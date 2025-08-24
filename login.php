<?php
/**
 * Community Connect - User Login Processing
 * Handles user authentication with role-based redirection
 */

require_once 'config/database.php';
require_once 'includes/common.php';

// Start secure session
startSecureSession();

// If already logged in, redirect to appropriate dashboard
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
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate required fields
    $missing_fields = validateRequiredFields(['email', 'password']);
    if (!empty($missing_fields)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!isValidEmail($email)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // Fetch user from database
        $user = getSingleRecord(
            "SELECT user_id, name, email, password, role, is_active FROM users WHERE email = ? LIMIT 1",
            [$email]
        );
        
        if ($user) {
            // Check if account is active
            if (!$user['is_active']) {
                $error_message = 'Account is inactive. Please contact admin.';
            } elseif (verifyPassword($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];
                
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
                    <h2 class="text-center">Login to Community Connect</h2>
                    
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
