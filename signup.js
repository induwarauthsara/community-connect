// Show/hide password
function togglePassword(){
    const pass = document.getElementById('password');
    const toggleBtn = document.querySelector('[onclick="togglePassword()"]');
    
    if (pass.type === 'password') {
        pass.type = 'text';
        toggleBtn.textContent = 'Hide';
    } else {
        pass.type = 'password';
        toggleBtn.textContent = 'Show';
    }
}

function toggleConfirm(){
    const pass = document.getElementById('confirm_password');
    const toggleBtn = document.querySelector('[onclick="toggleConfirm()"]');
    
    if (pass.type === 'password') {
        pass.type = 'text';
        toggleBtn.textContent = 'Hide';
    } else {
        pass.type = 'password';
        toggleBtn.textContent = 'Show';
    }
}

// Email validation function
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Password strength check
function checkPasswordStrength(password) {
    if (password.length < 6) return 'weak';
    if (password.length < 8) return 'medium';
    if (/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(password)) return 'strong';
    return 'medium';
}

// Real-time form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('signupForm');
    if (form) {
        // Password matching validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        function checkPasswordMatch() {
            if (confirmPassword.value && password.value !== confirmPassword.value) {
                confirmPassword.style.borderColor = '#dc3545';
            } else if (confirmPassword.value) {
                confirmPassword.style.borderColor = '#28a745';
            } else {
                confirmPassword.style.borderColor = '#ccc';
            }
        }
        
        if (password && confirmPassword) {
            password.addEventListener('input', checkPasswordMatch);
            confirmPassword.addEventListener('input', checkPasswordMatch);
        }
        
        // Email validation
        const emailInput = form.querySelector('input[name="email"]');
        if (emailInput) {
            emailInput.addEventListener('blur', function() {
                if (this.value && !isValidEmail(this.value)) {
                    this.style.borderColor = '#dc3545';
                } else if (this.value) {
                    this.style.borderColor = '#28a745';
                } else {
                    this.style.borderColor = '#ccc';
                }
            });
        }
    }
});
