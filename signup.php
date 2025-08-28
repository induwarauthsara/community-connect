<?php
/**
 * Community Connect - User Registration Processing
 * Handles user registration with role selection and proper validation
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

$page_title = 'Sign Up - Community Connect';
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    $confirmed = $_POST['confirmed'] ?? 'false';
    
    // Backend confirmation check (MANDATORY for registration)
    if ($confirmed !== 'true') {
        $error_message = 'Registration requires confirmation.';
    } else {
        // Validate required fields
        $missing_fields = validateRequiredFields(['name', 'email', 'password', 'confirm_password', 'role']);
        if (!empty($missing_fields)) {
            $error_message = 'Please fill in all required fields.';
        } elseif (!isValidEmail($email)) {
            $error_message = 'Please enter a valid email address.';
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error_message = 'Password must be at least 6 characters long.';
        } elseif (!in_array($role, ['admin', 'organization', 'volunteer'])) {
            $error_message = 'Please select a valid role.';
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
