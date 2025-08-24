    <!-- JavaScript Confirmation Functions -->
    <script>
        /**
         * Generic confirmation dialog for actions
         * @param {string} action Description of action
         * @param {function} callback Function to execute if confirmed
         */
        function confirmAction(action, callback) {
            if (confirm(`Are you sure you want to ${action}? This action cannot be undone.`)) {
                callback();
            }
        }
        
        /**
         * Confirmation for form submissions
         * @param {HTMLFormElement} form The form element
         * @param {string} action Description of action
         * @returns {boolean} True if confirmed
         */
        function confirmFormSubmission(form, action) {
            if (confirm(`Are you sure you want to ${action}?`)) {
                // Set confirmed field to true
                let confirmedField = form.querySelector('input[name="confirmed"]');
                if (confirmedField) {
                    confirmedField.value = 'true';
                }
                return true;
            }
            return false;
        }
        
        /**
         * Confirmation for delete operations (double confirmation)
         * @param {string} itemName Name of item being deleted
         * @param {function} callback Function to execute if confirmed
         */
        function confirmDelete(itemName, callback) {
            if (confirm(`Are you sure you want to delete "${itemName}"?`)) {
                if (confirm(`This will permanently delete "${itemName}". Are you absolutely sure?`)) {
                    callback();
                }
            }
        }
        
        /**
         * Form validation helper
         * @param {HTMLFormElement} form The form to validate
         * @returns {boolean} True if form is valid
         */
        function validateForm(form) {
            const requiredFields = form.querySelectorAll('input[required], textarea[required], select[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    isValid = false;
                } else {
                    field.style.borderColor = '#ced4da';
                }
            });
            
            return isValid;
        }
        
        /**
         * Email validation
         * @param {string} email Email address to validate
         * @returns {boolean} True if email is valid
         */
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        /**
         * Password strength indicator
         * @param {string} password Password to check
         * @returns {string} Strength level (weak, medium, strong)
         */
        function checkPasswordStrength(password) {
            if (password.length < 6) return 'weak';
            if (password.length < 8) return 'medium';
            if (/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(password)) return 'strong';
            return 'medium';
        }
        
        /**
         * Auto-hide alerts after 5 seconds
         */
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
        
        /**
         * Add loading state to buttons
         * @param {HTMLButtonElement} button Button element
         * @param {string} loadingText Text to show while loading
         */
        function setButtonLoading(button, loadingText = 'Loading...') {
            button.disabled = true;
            button.dataset.originalText = button.textContent;
            button.textContent = loadingText;
        }
        
        /**
         * Remove loading state from button
         * @param {HTMLButtonElement} button Button element
         */
        function removeButtonLoading(button) {
            button.disabled = false;
            button.textContent = button.dataset.originalText || button.textContent;
        }
    </script>
</body>
</html>
