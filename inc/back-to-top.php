<?php
/**
 * Back to Top functionality
 * Provides smooth scrolling back to top functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add back to top button to footer
 */
function hrphoto_add_back_to_top_button() {
    // Only show on pages that have scrollable content
    if (is_home() || is_archive() || is_author() || is_search() || is_single() || is_page()) {
        ?>
        <button 
            id="back-to-top" 
            class="back-to-top-btn" 
            type="button"
            aria-label="<?php esc_attr_e('Back to top', '1hrphoto'); ?>"
            title="<?php esc_attr_e('Back to top', '1hrphoto'); ?>"
            style="display: none;"
        >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M12 4L4 12H8V20H16V12H20L12 4Z" fill="currentColor"/>
            </svg>
        </button>
        <?php
    }
}
add_action('wp_footer', 'hrphoto_add_back_to_top_button');

/**
 * Add smooth scroll behavior to theme
 */
function hrphoto_add_smooth_scroll_support() {
    ?>
    <style>
        html {
            scroll-behavior: smooth;
        }
        
        /* Fallback for browsers that don't support scroll-behavior */
        @media (prefers-reduced-motion: no-preference) {
            html {
                scroll-behavior: smooth;
            }
        }
        
        /* Respect user's motion preferences */
        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'hrphoto_add_smooth_scroll_support');

/**
 * Add back to top configuration data
 */
function hrphoto_add_back_to_top_config() {
    if (is_home() || is_archive() || is_author() || is_search() || is_single() || is_page()) {
        $config = array(
            'scrollOffset' => 300, // Show button after scrolling 300px
            'scrollDuration' => 800, // Animation duration in milliseconds
            'easing' => 'easeInOutCubic', // Easing function
            'position' => array(
                'bottom' => '20px',
                'right' => '20px'
            ),
            'size' => array(
                'width' => '50px',
                'height' => '50px'
            ),
            'colors' => array(
                'background' => '#007cba',
                'backgroundHover' => '#005a87',
                'icon' => '#ffffff'
            ),
            'borderRadius' => '50%',
            'boxShadow' => '0 4px 12px rgba(0, 124, 186, 0.3)',
            'zIndex' => 9999
        );
        
        // Allow theme customization
        $config = apply_filters('hrphoto_back_to_top_config', $config);
        
        echo '<script type="text/javascript">';
        echo 'window.backToTopConfig = ' . json_encode($config) . ';';
        echo '</script>';
    }
}
add_action('wp_footer', 'hrphoto_add_back_to_top_config');
