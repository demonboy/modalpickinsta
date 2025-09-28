<?php
/**
 * Infinite Scroll functionality
 * Handles AJAX requests for loading more posts
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler for infinite scroll
 */
function hrphoto_infinite_scroll_handler() {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_die('Security check failed');
    }

    $page = intval($_POST['page']);
    $post_type = sanitize_text_field($_POST['post_type']);
    $posts_per_page = intval($_POST['posts_per_page']);
    
    // Set up query arguments
    $args = array(
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => $posts_per_page,
        'paged' => $page,
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => 'visibility',
                'value' => 'public',
                'compare' => '='
            ),
            array(
                'key' => 'visibility',
                'compare' => 'NOT EXISTS'
            )
        )
    );

    // Handle different query types
    if (isset($_POST['query_type'])) {
        $query_type = sanitize_text_field($_POST['query_type']);
        
        switch ($query_type) {
            case 'home':
                // Include post, 1hrphoto, and story post types
                $args['post_type'] = array('post', '1hrphoto', 'story');
                break;
            case 'archive':
                // Handle archive-specific queries
                if (isset($_POST['category'])) {
                    $args['category_name'] = sanitize_text_field($_POST['category']);
                }
                if (isset($_POST['tag'])) {
                    $args['tag'] = sanitize_text_field($_POST['tag']);
                }
                if (isset($_POST['post_type_archive'])) {
                    $args['post_type'] = sanitize_text_field($_POST['post_type_archive']);
                }
                break;
            case 'author':
                $author_id = intval($_POST['author_id']);
                $args['author'] = $author_id;
                break;
            case 'search':
                if (isset($_POST['search_query'])) {
                    $args['s'] = sanitize_text_field($_POST['search_query']);
                }
                break;
        }
    }

    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        $posts_html = '';
        
        while ($query->have_posts()) {
            $query->the_post();
            
            // Start output buffering to capture post content
            ob_start();
            ?>
            <article class="post-item infinite-scroll-post" data-post-id="<?php echo get_the_ID(); ?>">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="post-featured-image">
                        <a href="<?php the_permalink(); ?>">
                            <?php the_post_thumbnail('large'); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="post-content">
                    <h2 class="post-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h2>
                    
                    <div class="post-meta">
                        <span class="post-date"><?php echo get_the_date(); ?></span>
                        <span class="post-author">by <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>"><?php the_author(); ?></a></span>
                    </div>
                    
                    <?php if (has_excerpt()) : ?>
                        <div class="post-excerpt"><?php the_excerpt(); ?></div>
                    <?php endif; ?>
                    
                    <!-- Action buttons -->
                    <div class="postpic-feed-actions">
                        <div class="action-icons-left">
                            <?php
                            // Like button
                            $like_count = get_post_meta(get_the_ID(), 'like_count', true) ?: 0;
                            ?>
                            <button class="feed-like-btn" data-post-id="<?php echo get_the_ID(); ?>">
                                <img src="<?php echo get_stylesheet_directory_uri(); ?>/img/like.gif" alt="Like" class="feed-icon">
                                <span class="like-count"><?php echo $like_count; ?></span>
                            </button>
                            
                            <?php
                            // Comments button
                            $comments_count = get_comments_number();
                            ?>
                            <button class="feed-comments-btn" data-post-id="<?php echo get_the_ID(); ?>">
                                <img src="<?php echo get_stylesheet_directory_uri(); ?>/img/comment.gif" alt="Comments" class="feed-icon">
                                <span class="comments-count"><?php echo $comments_count; ?></span>
                            </button>
                            
                            <!-- Share button -->
                            <button class="feed-share-btn" data-post-id="<?php echo get_the_ID(); ?>">
                                <img src="<?php echo get_stylesheet_directory_uri(); ?>/img/share.gif" alt="Share" class="feed-icon">
                            </button>
                        </div>
                    </div>
                </div>
            </article>
            <?php
            $posts_html .= ob_get_clean();
        }
        
        wp_reset_postdata();
        
        // Return success response with posts HTML
        wp_send_json_success(array(
            'posts_html' => $posts_html,
            'has_more' => $query->max_num_pages > $page,
            'next_page' => $page + 1,
            'total_pages' => $query->max_num_pages
        ));
    } else {
        // No more posts
        wp_send_json_success(array(
            'posts_html' => '',
            'has_more' => false,
            'next_page' => $page,
            'total_pages' => $query->max_num_pages
        ));
    }
}

// Hook for logged-in users
add_action('wp_ajax_infinite_scroll', 'hrphoto_infinite_scroll_handler');
// Hook for non-logged-in users
add_action('wp_ajax_nopriv_infinite_scroll', 'hrphoto_infinite_scroll_handler');

/**
 * Add infinite scroll data attributes to the main content area
 */
function hrphoto_add_infinite_scroll_data() {
    if (is_home() || is_archive() || is_author()) {
        global $wp_query;
        
        $post_types = array('post');
        if (is_home()) {
            $post_types = array('post', '1hrphoto', 'story');
        } elseif (is_post_type_archive()) {
            $post_types = array(get_post_type());
        } elseif (is_author()) {
            $post_types = array('post', '1hrphoto', 'story');
        }
        
        $query_type = 'home';
        if (is_archive()) {
            $query_type = 'archive';
        } elseif (is_author()) {
            $query_type = 'author';
        }
        
        $author_id = '';
        if (is_author()) {
            $author_id = get_queried_object_id();
        }
        
        echo '<script type="text/javascript">';
        echo 'window.infiniteScrollData = {';
        echo 'postTypes: ' . json_encode($post_types) . ',';
        echo 'queryType: "' . $query_type . '",';
        echo 'authorId: "' . $author_id . '",';
        echo 'postsPerPage: ' . get_option('posts_per_page') . ',';
        echo 'maxPages: ' . $wp_query->max_num_pages . ',';
        echo 'currentPage: ' . max(1, get_query_var('paged')) . ',';
        echo 'ajaxUrl: "' . admin_url('admin-ajax.php') . '",';
        echo 'nonce: "' . wp_create_nonce('ajax_nonce') . '"';
        echo '};';
        echo '</script>';
    }
}
add_action('wp_footer', 'hrphoto_add_infinite_scroll_data');
