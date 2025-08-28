<?php
?>
    </main>
    
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Community Connect. Building stronger communities through volunteer coordination.</p>
        </div>
    </footer>
    
    <script>
        // Confirmation functions for database operations
        function confirmAction(message) {
            return confirm(message + ' This action cannot be undone.');
        }
        
        function confirmDelete() {
            return confirmAction('Are you absolutely sure you want to delete this?');
        }
        
        function confirmUpdate() {
            return confirmAction('Are you sure you want to update this?');
        }
        
        function confirmCreate() {
            return confirmAction('Are you sure you want to create this?');
        }
        
        function confirmJoin() {
            return confirm('Are you sure you want to join this project?');
        }
        
        function confirmLeave() {
            return confirm('Are you sure you want to leave this project?');
        }
    </script>
</body>
</html>