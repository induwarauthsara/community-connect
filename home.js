// Simple JavaScript for enhanced user experience
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth scrolling for anchor links
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Add click animation to nav links
    const navLinks = document.querySelectorAll('.nav-link, .auth-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });

    // Get user data from PHP (if available)
    const userData = window.userSessionData || {};
    
    // Console log for debugging (remove in production)
    if (userData.isLoggedIn) {
        console.log('User logged in:', userData.username);
        if (userData.role) {
            console.log('User role:', userData.role);
        }
    } else {
        console.log('User not logged in');
    }

    // Add fade-in animation to content box
    const contentBox = document.querySelector('.content-box');
    if (contentBox) {
        contentBox.style.opacity = '0';
        contentBox.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            contentBox.style.transition = 'all 0.6s ease';
            contentBox.style.opacity = '1';
            contentBox.style.transform = 'translateY(0)';
        }, 100);
    }

    // Add hover effects to role badges
    const roleBadges = document.querySelectorAll('.role-badge');
    roleBadges.forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // Add ripple effect to buttons
    function createRipple(event) {
        const button = event.currentTarget;
        const circle = document.createElement('span');
        const diameter = Math.max(button.clientWidth, button.clientHeight);
        const radius = diameter / 2;

        circle.style.width = circle.style.height = `${diameter}px`;
        circle.style.left = `${event.clientX - button.offsetLeft - radius}px`;
        circle.style.top = `${event.clientY - button.offsetTop - radius}px`;
        circle.classList.add('ripple');

        const ripple = button.getElementsByClassName('ripple')[0];
        if (ripple) {
            ripple.remove();
        }

        button.appendChild(circle);
    }

    // Apply ripple effect to nav links and auth links
    const rippleButtons = document.querySelectorAll('.nav-link, .auth-link');
    rippleButtons.forEach(button => {
        button.addEventListener('click', createRipple);
        
        // Add ripple CSS if not already present
        if (!document.getElementById('ripple-styles')) {
            const style = document.createElement('style');
            style.id = 'ripple-styles';
            style.textContent = `
                .ripple {
                    position: absolute;
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple-animation 0.6s linear;
                    background-color: rgba(255, 255, 255, 0.3);
                    pointer-events: none;
                }
                
                @keyframes ripple-animation {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
                
                .nav-link, .auth-link {
                    position: relative;
                    overflow: hidden;
                }
            `;
            document.head.appendChild(style);
        }
    });
});
