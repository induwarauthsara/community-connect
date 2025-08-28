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

$page_title = 'Sign Up - Community Connect';
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'volunteer';
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($password) < 4) {
        $error_message = 'Password must be at least 4 characters long.';
    } else {
        // Escape data for simple queries
        $name = mysqli_real_escape_string($connection, $name);
        $email = mysqli_real_escape_string($connection, $email);
        $password = mysqli_real_escape_string($connection, $password);
        $role = mysqli_real_escape_string($connection, $role);
        
        // Check if email already exists
        $check_sql = "SELECT user_id FROM users WHERE email = '$email'";
        $check_result = mysqli_query($connection, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error_message = 'An account with this email already exists.';
        } else {
            // Create new user (simple password storage)
            $insert_sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')";
            if (mysqli_query($connection, $insert_sql)) {
                $success_message = 'Account created successfully! You can now login.';
                // Clear form data
                $name = $email = '';
            } else {
                $error_message = 'Failed to create account. Please try again.';
            }
        }
    }
}

include 'includes/header.php';
?>

<style>
    .signup-container {
        max-width: 500px;
        margin: 30px auto;
        padding: 0 20px;
    }
    
    .signup-card {
        background: white;
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .signup-logo {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        border-radius: 50%;
    }
    
    .signup-title {
        color: var(--dark-blue);
        margin-bottom: 10px;
        font-size: 1.8rem;
    }
    
    .signup-subtitle {
        color: var(--gray);
        margin-bottom: 30px;
    }
    
    .form-group {
        text-align: left;
        margin-bottom: 20px;
    }
    
    .role-selection {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 10px;
        margin-top: 10px;
    }
    
    .role-option {
        padding: 12px;
        border: 2px solid var(--border);
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .role-option:hover {
        border-color: var(--primary-blue);
        background: var(--light-blue);
    }
    
    .role-option input[type="radio"] {
        margin-right: 5px;
    }
    
    .role-option.selected {
        border-color: var(--primary-blue);
        background: var(--light-blue);
    }
    
    .signup-btn {
        width: 100%;
        padding: 12px;
        margin-bottom: 20px;
    }
    
    .login-link {
        color: var(--primary-blue);
        text-decoration: none;
        font-weight: 500;
    }
    
    .login-link:hover {
        text-decoration: underline;
    }
</style>

<div class="signup-container">
    <div class="signup-card">
        <img src="assets/images/logo.png" alt="Community Connect Logo" class="signup-logo">
        <h1 class="signup-title">Join Community Connect</h1>
        <p class="signup-subtitle">Create your account to get started</p>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
                <p class="mt-2">
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                </p>
            </div>
        <?php endif; ?>

        <?php if (!$success_message): ?>
            <form method="POST" action="signup.php">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" required
                           value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>"
                           placeholder="Enter your full name">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required
                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                           placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required
                           placeholder="Choose a password (min 4 characters)">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required
                           placeholder="Confirm your password">
                </div>

                <div class="form-group">
                    <label>I am joining as:</label>
                    <div class="role-selection">
                        <div class="role-option">
                            <label>
                                <input type="radio" name="role" value="volunteer" checked>
                                <div>🙋‍♂️ Volunteer</div>
                                <small>Individual volunteer</small>
                            </label>
                        </div>
                        <div class="role-option">
                            <label>
                                <input type="radio" name="role" value="organization">
                                <div>🏢 Organization</div>
                                <small>Create projects</small>
                            </label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary signup-btn" 
                        onclick="return confirm('Create your Community Connect account?')">
                    Create Account
                </button>
            </form>
        <?php endif; ?>

        <div class="divider">
            <span>or</span>
        </div>

        <p>
            Already have an account? 
            <a href="login.php" class="login-link">Sign in here</a>
        </p>
        
        <p class="mt-3">
            <a href="index.php" class="text-muted">← Back to Home</a>
        </p>
    </div>
</div>

<script>
    // Handle role selection styling
    document.querySelectorAll('.role-option input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.role-option').forEach(option => {
                option.classList.remove('selected');
            });
            this.closest('.role-option').classList.add('selected');
        });
    });
    
    // Set initial selection
    document.querySelector('.role-option input[checked]').closest('.role-option').classList.add('selected');
</script>

<?php include 'includes/footer.php'; ?>
