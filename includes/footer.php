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
        
        // Modern UI enhancements
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading effect to forms
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn && !submitBtn.disabled) {
                        // Add a loading class instead of changing innerHTML
                        submitBtn.classList.add('loading');
                        
                        // Don't disable immediately - let form submit first
                        setTimeout(() => {
                            submitBtn.disabled = true;
                        }, 100);
                        
                        // Re-enable after a delay to prevent stuck state
                        setTimeout(() => {
                            submitBtn.classList.remove('loading');
                            submitBtn.disabled = false;
                        }, 3000);
                    }
                });
            });
            
            // Add ripple effect to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple-effect');
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
            
            // Smooth scrolling for anchor links
            const links = document.querySelectorAll('a[href^="#"]');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
            
            // Add intersection observer for animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);
            
            // Observe elements for animation
            const animatedElements = document.querySelectorAll('.fade-in, .slide-in-left, .slide-in-right, .project-card');
            animatedElements.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
                observer.observe(el);
            });
        });
        
        // Add ripple effect styles
        const style = document.createElement('style');
        style.textContent = `
            .ripple-effect {
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.6);
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
            }
            
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            .btn.loading {
                opacity: 0.7;
                cursor: not-allowed;
                position: relative;
            }
            
            .btn.loading:after {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                margin-left: -10px;
                margin-top: -10px;
                width: 20px;
                height: 20px;
                border: 2px solid rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                border-top-color: white;
                animation: spin 1s ease-in-out infinite;
            }
            
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>