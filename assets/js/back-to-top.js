/**
 * Back to Top functionality
 * Provides smooth scrolling and button visibility management
 */

(function() {
    'use strict';

    // Check if back to top config is available
    if (typeof window.backToTopConfig === 'undefined') {
        return;
    }

    const config = window.backToTopConfig;
    let backToTopBtn;
    let isScrolling = false;
    let scrollTimeout;

    // Easing functions for smooth scrolling
    const easingFunctions = {
        easeInOutCubic: function(t) {
            return t < 0.5 ? 4 * t * t * t : (t - 1) * (2 * t - 2) * (2 * t - 2) + 1;
        },
        easeOutCubic: function(t) {
            return (--t) * t * t + 1;
        },
        easeInOutQuad: function(t) {
            return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
        }
    };

    // Smooth scroll to top function
    function smoothScrollToTop() {
        if (isScrolling) {
            return;
        }

        isScrolling = true;
        backToTopBtn.setAttribute('aria-pressed', 'true');
        backToTopBtn.disabled = true;

        const startTime = performance.now();
        const startPosition = window.pageYOffset;
        const targetPosition = 0;
        const distance = startPosition - targetPosition;
        const duration = config.scrollDuration;

        function animateScroll(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Use the specified easing function
            const easingFunction = easingFunctions[config.easing] || easingFunctions.easeInOutCubic;
            const easedProgress = easingFunction(progress);
            
            const currentPosition = startPosition - (distance * easedProgress);
            window.scrollTo(0, currentPosition);

            if (progress < 1) {
                requestAnimationFrame(animateScroll);
            } else {
                // Animation complete
                isScrolling = false;
                backToTopBtn.setAttribute('aria-pressed', 'false');
                backToTopBtn.disabled = false;
                
                // Focus management for accessibility
                backToTopBtn.blur();
                
                // Announce to screen readers
                announceToScreenReader('Returned to top of page');
            }
        }

        requestAnimationFrame(animateScroll);
    }

    // Handle scroll events with throttling
    function handleScroll() {
        if (scrollTimeout) {
            clearTimeout(scrollTimeout);
        }

        scrollTimeout = setTimeout(() => {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > config.scrollOffset) {
                showBackToTopButton();
            } else {
                hideBackToTopButton();
            }
        }, 10); // Throttle to 10ms
    }

    // Show back to top button
    function showBackToTopButton() {
        if (backToTopBtn && backToTopBtn.style.display === 'none') {
            backToTopBtn.style.display = 'flex';
            backToTopBtn.setAttribute('aria-hidden', 'false');
            
            // Add entrance animation
            requestAnimationFrame(() => {
                backToTopBtn.classList.add('back-to-top-visible');
            });
        }
    }

    // Hide back to top button
    function hideBackToTopButton() {
        if (backToTopBtn && backToTopBtn.style.display !== 'none') {
            backToTopBtn.classList.remove('back-to-top-visible');
            
            // Wait for animation to complete before hiding
            setTimeout(() => {
                if (!backToTopBtn.classList.contains('back-to-top-visible')) {
                    backToTopBtn.style.display = 'none';
                    backToTopBtn.setAttribute('aria-hidden', 'true');
                }
            }, 300);
        }
    }

    // Announce to screen readers
    function announceToScreenReader(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.textContent = message;
        
        document.body.appendChild(announcement);
        
        // Remove after announcement
        setTimeout(() => {
            document.body.removeChild(announcement);
        }, 1000);
    }

    // Handle keyboard navigation
    function handleKeyDown(event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            smoothScrollToTop();
        }
    }

    // Initialize back to top functionality
    function init() {
        backToTopBtn = document.getElementById('back-to-top');
        
        if (!backToTopBtn) {
            return;
        }

        // Apply configuration styles
        applyConfigurationStyles();

        // Add event listeners
        backToTopBtn.addEventListener('click', smoothScrollToTop);
        backToTopBtn.addEventListener('keydown', handleKeyDown);
        
        // Use passive scroll listener for better performance
        window.addEventListener('scroll', handleScroll, { passive: true });
        
        // Handle resize events to maintain proper positioning
        window.addEventListener('resize', debounce(() => {
            applyConfigurationStyles();
        }, 250));

        // Initial check for scroll position
        handleScroll();

        // Handle focus management
        document.addEventListener('keydown', function(event) {
            // If user presses Tab and back to top button is visible, ensure it's focusable
            if (event.key === 'Tab' && backToTopBtn.style.display !== 'none') {
                backToTopBtn.setAttribute('tabindex', '0');
            }
        });
    }

    // Apply configuration styles to the button
    function applyConfigurationStyles() {
        if (!backToTopBtn) {
            return;
        }

        const styles = `
            position: fixed;
            bottom: ${config.position.bottom};
            right: ${config.position.right};
            width: ${config.size.width};
            height: ${config.size.height};
            background-color: ${config.colors.background};
            color: ${config.colors.icon};
            border: none;
            border-radius: ${config.borderRadius};
            box-shadow: ${config.boxShadow};
            z-index: ${config.zIndex};
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
            outline: none;
        `;

        // Apply styles
        Object.assign(backToTopBtn.style, {
            position: 'fixed',
            bottom: config.position.bottom,
            right: config.position.right,
            width: config.size.width,
            height: config.size.height,
            backgroundColor: config.colors.background,
            color: config.colors.icon,
            border: 'none',
            borderRadius: config.borderRadius,
            boxShadow: config.boxShadow,
            zIndex: config.zIndex,
            cursor: 'pointer',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            transition: 'all 0.3s ease',
            opacity: '0',
            transform: 'translateY(20px)',
            outline: 'none'
        });

        // Add hover styles via CSS class
        const styleSheet = document.createElement('style');
        styleSheet.textContent = `
            .back-to-top-btn:hover {
                background-color: ${config.colors.backgroundHover} !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 6px 16px rgba(0, 124, 186, 0.4) !important;
            }
            
            .back-to-top-btn:focus {
                outline: 2px solid ${config.colors.background} !important;
                outline-offset: 2px !important;
            }
            
            .back-to-top-btn:active {
                transform: translateY(0) !important;
            }
            
            .back-to-top-visible {
                opacity: 1 !important;
                transform: translateY(0) !important;
            }
            
            .back-to-top-btn:disabled {
                opacity: 0.6 !important;
                cursor: not-allowed !important;
            }
            
            .sr-only {
                position: absolute !important;
                width: 1px !important;
                height: 1px !important;
                padding: 0 !important;
                margin: -1px !important;
                overflow: hidden !important;
                clip: rect(0, 0, 0, 0) !important;
                white-space: nowrap !important;
                border: 0 !important;
            }
        `;
        
        if (!document.querySelector('style[data-back-to-top]')) {
            styleSheet.setAttribute('data-back-to-top', 'true');
            document.head.appendChild(styleSheet);
        }
    }

    // Debounce utility function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Start when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
