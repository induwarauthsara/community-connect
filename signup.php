<?php
/**
 * Community Connect - User Registration
 * Simple registration without password hashing
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
        // Check if email already exists
        $check_sql = "SELECT user_id FROM users WHERE email = ?";
        if ($check_stmt = mysqli_prepare($connection, $check_sql)) {
            mysqli_stmt_bind_param($check_stmt, "s", $email);
            if (mysqli_stmt_execute($check_stmt)) {
                $check_result = mysqli_stmt_get_result($check_stmt);
                if (mysqli_num_rows($check_result) > 0) {
                    $error_message = 'An account with this email already exists.';
                } else {
                    // Create new user (simple password storage)
                    $insert_sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
                    if ($insert_stmt = mysqli_prepare($connection, $insert_sql)) {
                        mysqli_stmt_bind_param($insert_stmt, "ssss", $name, $email, $password, $role);
                        if (mysqli_stmt_execute($insert_stmt)) {
                            $success_message = 'Account created successfully! You can now login.';
                            // Clear form data
                            $name = $email = '';
                        } else {
                            $error_message = 'Failed to create account. Please try again.';
                        }
                        mysqli_stmt_close($insert_stmt);
                    }
                }
            }
            mysqli_stmt_close($check_stmt);
        } else {
            $error_message = 'Database error. Please try again.';
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
                <?php echo sanitizeInput($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo sanitizeInput($success_message); ?>
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
                           value="<?php echo isset($name) ? sanitizeInput($name) : ''; ?>"
                           placeholder="Enter your full name">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required
                           value="<?php echo isset($email) ? sanitizeInput($email) : ''; ?>"
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
        } else {
            // Check if email already exists
            $existing_user = getSingleRecord(
                "SELECT user_id FROM users WHERE email = ?",
                [$email]
            );
            
            if ($existing_user) {
                $error_message = 'An account with this email address already exists.';
            } else {
                // Hash password
                $hashed_password = hashPassword($password);
                
                // Insert new user
                $user_id = insertRecord(
                    "INSERT INTO users (name, email, phone, password, role, is_active, email_verified, created_at) 
                     VALUES (?, ?, ?, ?, ?, TRUE, FALSE, NOW())",
                    [$name, $email, $phone, $hashed_password, $role]
                );
                
                if ($user_id) {
                    // Log activity
                    logUserActivity($user_id, 'registration', 'New user registered with role: ' . $role);
                    
                    $success_message = 'Registration successful! You can now log in.';
                    
                    // Clear form data on success
                    $name = $email = $phone = $role = '';
                } else {
                    $error_message = 'Registration failed. Please try again.';
                }
            }
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
                    <h2 class="text-center">Create Your Account</h2>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-error">
                            <?php echo sanitizeInput($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <?php echo sanitizeInput($success_message); ?>
                            <div class="text-center mt-3">
                                <a href="login.html" class="btn-primary">Login Now</a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!$success_message): ?>
                        <form method="POST" action="signup.php" onsubmit="return confirmSignup(this)">
                            <input type="hidden" name="confirmed" value="false">
                            
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" required 
                                       value="<?php echo isset($name) ? sanitizeInput($name) : ''; ?>"
                                       placeholder="Enter your full name">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" required 
                                       value="<?php echo isset($email) ? sanitizeInput($email) : ''; ?>"
                                       placeholder="Enter your email address">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?php echo isset($phone) ? sanitizeInput($phone) : ''; ?>"
                                       placeholder="Enter your phone number">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-control" required>
                                    <option value="">Select your role</option>
                                    <option value="volunteer" <?php echo (isset($role) && $role === 'volunteer') ? 'selected' : ''; ?>>
                                        Volunteer - Join and participate in community projects
                                    </option>
                                    <option value="organization" <?php echo (isset($role) && $role === 'organization') ? 'selected' : ''; ?>>
                                        Organization - Create and manage volunteer projects
                                    </option>
                                    <option value="admin" <?php echo (isset($role) && $role === 'admin') ? 'selected' : ''; ?>>
                                        Administrator - System management and oversight
                                    </option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required 
                                       placeholder="Enter your password (min. 6 characters)"
                                       onkeyup="updatePasswordStrength(this)">
                                <small id="password-strength" class="text-muted"></small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required 
                                       placeholder="Confirm your password">
                            </div>
                            
                            <div class="form-group">
                                <label style="display: flex; align-items: center;">
                                    <input type="checkbox" required style="margin-right: 8px;">
                                    I agree to the <a href="#" onclick="alert('Terms and conditions would be displayed here.'); return false;">Terms & Conditions</a>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn-primary" style="width: 100%;">
                                Create Account
                            </button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p>Already have an account? <a href="login.html">Login</a></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmSignup(form) {
    const name = form.querySelector('input[name="name"]').value.trim();
    const email = form.querySelector('input[name="email"]').value.trim();
    const password = form.querySelector('input[name="password"]').value;
    const confirmPassword = form.querySelector('input[name="confirm_password"]').value;
    const role = form.querySelector('select[name="role"]').value;
    
    // Validate form
    if (!name || !email || !password || !confirmPassword || !role) {
        alert('Please fill in all required fields.');
        return false;
    }
    
    if (!isValidEmail(email)) {
        alert('Please enter a valid email address.');
        return false;
    }
    
    if (password.length < 6) {
        alert('Password must be at least 6 characters long.');
        return false;
    }
    
    if (password !== confirmPassword) {
        alert('Passwords do not match.');
        return false;
    }
    
    // Confirmation dialog
    const roleText = form.querySelector(`select[name="role"] option[value="${role}"]`).textContent;
    if (confirm(`Create account for ${name} as ${roleText}?`)) {
        form.querySelector('input[name="confirmed"]').value = 'true';
        
        // Set loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        setButtonLoading(submitBtn, 'Creating account...');
        
        return true;
    }
    
    return false;
}

function updatePasswordStrength(input) {
    const password = input.value;
    const strengthElement = document.getElementById('password-strength');
    
    if (password.length === 0) {
        strengthElement.textContent = '';
        return;
    }
    
    const strength = checkPasswordStrength(password);
    const colors = {
        weak: '#dc3545',
        medium: '#ffc107', 
        strong: '#28a745'
    };
    
    strengthElement.textContent = `Password strength: ${strength.charAt(0).toUpperCase() + strength.slice(1)}`;
    strengthElement.style.color = colors[strength];
}
</script>

<?php include 'includes/footer.php'; ?>
