<?php
/**
 * Shared Footer Component - Community Connect
 */
?>
    </div>
    <script>
        // Confirmation functions for all CUD operations
        function confirmAction(message, form) {
            if (confirm(message + ' This action cannot be undone.')) {
                form.querySelector('input[name="confirmed"]').value = 'true';
                return true;
            }
            return false;
        }
        
        function confirmDelete(form) {
            return confirmAction('Are you absolutely sure you want to delete this?', form);
        }
        
        function confirmUpdate(form) {
            return confirmAction('Are you sure you want to update this?', form);
        }
        
        function confirmCreate(form) {
            return confirmAction('Are you sure you want to create this?', form);
        }
        
        function confirmJoin(form) {
            return confirmAction('Are you sure you want to join this project?', form);
        }
        
        function confirmLeave(form) {
            return confirmAction('Are you sure you want to leave this project?', form);
        }
    </script>
</body>
</html>
