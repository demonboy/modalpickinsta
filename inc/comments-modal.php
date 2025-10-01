<?php
//COMMENTS IN MODAL

// Add AJAX handlers for comments modal
add_action('wp_ajax_get_comments_modal', 'handle_get_comments_modal');
add_action('wp_ajax_nopriv_get_comments_modal', 'handle_get_comments_modal');

// AJAX handler for getting current user avatar
add_action('wp_ajax_get_current_user_avatar', 'get_current_user_avatar');
add_action('wp_ajax_nopriv_get_current_user_avatar', 'get_current_user_avatar');

function get_current_user_avatar() {
    if (is_user_logged_in()) {
        echo get_avatar(get_current_user_id(), 32);
    } else {
        echo get_avatar(0, 32); // Default avatar for non-logged-in users
    }
    wp_die();
}

function handle_get_comments_modal() {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $post_id = intval($_POST['post_id']);
    
    // Set the global post object
    global $post;
    $post = get_post($post_id);
    setup_postdata($post);
    
    $comments = get_comments(array(
        'post_id' => $post_id,
        'status' => 'approve',
        'order' => 'ASC',
        'parent' => 0 // Only get top-level comments
    ));
    
    $comments_html = '';
    if ($comments) {
        foreach ($comments as $comment) {
            $comments_html .= render_comment_with_replies($comment);
        }
    } else {
        // No filler text; allow caller to inject contextual messaging
        $comments_html = '';
    }

    // Reset global post

    // Add comment form or login message
    if (is_user_logged_in()) {
        ob_start();
        comment_form(array(
            'post_id' => $post_id,
            'logged_in_as' => '', // Remove "logged in as" text
            'comment_notes_before' => '', // Remove "Your email address will not be published..."
            'comment_notes_after' => '', // Remove any notes after
            'title_reply' => '', // Remove "Leave a Reply"
            'comment_field' => '<div class="sticky-comment-form"><div class="user-avatar" id="current-user-avatar"></div><input type="text" id="comment" name="comment" placeholder="Add a comment..." required><button type="submit" class="comment-submit-btn">Post</button></div>',
            'submit_button' => '', // Completely remove WordPress submit button
            'fields' => array(
                'cookies' => ''  // Remove cookies field
            )
        ));
        $comment_form = ob_get_clean();
    } else {
        // Show login message for logged-out users
        $comment_form = '<div class="sticky-comment-form"><p style="text-align: center; padding: 20px; color: #666;">You must be <a href="' . wp_login_url(get_permalink()) . '">logged in</a> to leave a comment.</p></div>';
    }
    wp_reset_postdata();
    // Constructive feedback context (meta only)
    $wants_cf = (get_post_meta($post_id, 'wants_constructive_feedback', true) === 'yes');
    $author_display = is_object($post) ? get_the_author_meta('display_name', (int) $post->post_author) : '';
    
	// Send JSON response using WordPress AJAX
    wp_send_json_success(array(
        'comments' => $comments_html . $comment_form,
        'count' => get_comments_number($post_id), // This counts ALL comments including replies
        'wants_cf' => (bool) $wants_cf,
        'author_display' => (string) $author_display,
    ));
}

// Enable 'save details for commenting' by default
add_filter('comment_form_default_fields', 'auto_enable_comment_cookies');
function auto_enable_comment_cookies($fields) {
    // Automatically set the cookie consent to true
    $_POST['wp-comment-cookies-consent'] = 'yes';
    return $fields;
}

function render_comment_with_replies($comment) {
    // Check if comment was soft-deleted
    $is_soft_deleted = get_comment_meta($comment->comment_ID, '_comment_soft_deleted', true);
    
    // Use grey circle for soft-deleted comments, otherwise normal avatar
    if ($is_soft_deleted) {
        $avatar = '<div class="comment-avatar-deleted"></div>';
    } else {
        $avatar = get_avatar($comment->comment_author_email, 40);
    }
    
    // Use "Comment deleted" for soft-deleted comments, otherwise normal content
    if ($is_soft_deleted) {
        $comment_display_text = '<em>Comment deleted</em>';
    } else {
        $comment_display_text = esc_html($comment->comment_content);
    }
    
    $profile_url = get_author_posts_url($comment->user_id);
    $comment_time = human_time_diff(strtotime($comment->comment_date), current_time('timestamp'));
    
    // Get comment like count and check if current user has liked - with error handling
    global $wpdb;
    $table = $wpdb->prefix . 'postpic_likes';
    $like_count = 0;
    $user_has_liked = false;
    
    // Check if table exists before querying
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
        $like_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE post_id = %d AND comment_id = %d",
            $comment->comment_post_ID, $comment->comment_ID
        ));
        
        // Check if current user has liked this comment
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $user_has_liked = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE post_id = %d AND comment_id = %d AND user_id = %d",
                $comment->comment_post_ID, $comment->comment_ID, $user_id
            )) > 0;
            
            // Debug: Add to HTML for testing
            $debug_info = "<!-- Comment {$comment->comment_ID}: User {$user_id} has liked: " . ($user_has_liked ? 'YES' : 'NO') . " -->";
        } else {
            $debug_info = "<!-- User not logged in -->";
        }
    }
    
    // Check permissions for edit/delete
    $current_user_id = get_current_user_id();
    $can_edit = ($comment->user_id && $current_user_id == $comment->user_id) || current_user_can('moderate_comments');
    
    // Check if this is a critique comment
    $is_critique = get_comment_meta($comment->comment_ID, '_is_critique', true);
    $critique_badge = '';
    if ($is_critique) {
        // Same SVG as critique_welcome shortcode (20px)
        $critique_badge = '<span class="critique-badge" title="Critique"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">'
                        . '<path d="M8.25 18L5.25 20.25V15.75H2.25C1.852 15.75 1.471 15.592 1.189 15.311C.908 15.029.75 14.648.75 14.25V2.25C.75 1.852.908 1.471 1.189 1.189C1.471.908 1.852.75 2.25.75H18.75C19.148.75 19.529.908 19.811 1.189C20.092 1.471 20.25 1.852 20.25 2.25V6.715" stroke="#16a34a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
                        . '<path d="M5.25 5.25H15.75M5.25 9.75H8.25" stroke="#16a34a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
                        . '<path d="M23.25 18.75H20.25V23.25L15.75 18.75H11.25V9.75H23.25V18.75Z" stroke="#16a34a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
                        . '<path d="M19.5 15H15" stroke="#16a34a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
                        . '</svg></span>';
    }
    
    // Check if this is a reply
    if ($comment->comment_parent > 0) {
        // Generate simpler HTML for replies (replies never get critique styling, only parent comments)
        $html = '<div class="reply-item" id="comment-' . $comment->comment_ID . '" data-comment-id="' . $comment->comment_ID . '">';
        $html .= '<div class="reply-row">';
        $html .= '<div class="reply-avatar"><a href="' . esc_url($profile_url) . '">' . $avatar . '</a></div>';
        $html .= '<div class="reply-content">';
        $html .= '<div class="reply-author"><a href="' . esc_url($profile_url) . '">' . esc_html($comment->comment_author) . '</a> <span class="reply-time">' . $comment_time . ' ago</span></div>';
        $html .= '<div class="reply-text" data-original-text="' . esc_attr($comment->comment_content) . '">' . $comment_display_text . '</div>';
        
        // Action wrapper for like button and three-dot menu
        $html .= '<div class="comment-actions-wrapper">';
        
        // Add like button to replies
        $like_button_class = $user_has_liked ? 'like-button liked' : 'like-button';
        $html .= $debug_info; // Add debug info
        $html .= '<button class="' . $like_button_class . '" data-post-id="' . $comment->comment_post_ID . '" data-comment-id="' . $comment->comment_ID . '">';
        $html .= '<span class="like-icon"></span>';
        $html .= $like_count > 0 ? '<span class="like-count">' . $like_count . '</span>' : '<span class="like-count"></span>';
        $html .= '</button>';
        
        // Three-dot menu
        if ($can_edit) {
            $html .= '<div class="comment-options">';
            $html .= '<button class="comment-options-btn" data-comment-id="' . $comment->comment_ID . '" aria-label="Comment options">⋯</button>';
            $html .= '<div class="comment-options-menu" style="display:none;">';
            $html .= '<button class="comment-option-item edit-comment" data-comment-id="' . $comment->comment_ID . '"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Edit</button>';
            $html .= '<button class="comment-option-item delete-comment" data-comment-id="' . $comment->comment_ID . '"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg> Delete</button>';
            $html .= '<button class="comment-option-item share-comment" data-comment-id="' . $comment->comment_ID . '"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg> Share</button>';
            $html .= '<button class="comment-option-item report-comment" disabled><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Report</button>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>'; // close comment-actions-wrapper
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    } else {
        // Generate full HTML for top-level comments
        $comment_class = $is_critique ? 'comment-item critique-comment' : 'comment-item';
        $html = '<div class="' . $comment_class . '" id="comment-' . $comment->comment_ID . '" data-comment-id="' . $comment->comment_ID . '">';
        $html .= '<div class="comment-row">';
        $html .= '<div class="comment-avatar"><a href="' . esc_url($profile_url) . '">' . $avatar . '</a></div>';
        $html .= '<div class="comment-content">';
        $html .= '<div class="comment-author"><a href="' . esc_url($profile_url) . '">' . esc_html($comment->comment_author) . '</a> ' . $critique_badge . ' <span class="comment-time">' . $comment_time . ' ago</span></div>';
        $html .= '<div class="comment-text" data-original-text="' . esc_attr($comment->comment_content) . '">' . $comment_display_text . '</div>';
        $html .= '<div class="comment-reply-wrapper">';
        $like_button_class = $user_has_liked ? 'like-button liked' : 'like-button';
        $html .= $debug_info; // Add debug info
        $html .= '<button class="' . $like_button_class . '" data-post-id="' . $comment->comment_post_ID . '" data-comment-id="' . $comment->comment_ID . '">';
        $html .= '<span class="like-icon"></span>';
        $html .= $like_count > 0 ? '<span class="like-count">' . $like_count . '</span>' : '<span class="like-count"></span>';
        $html .= '</button>';
        $html .= '<a href="#" class="comment-reply-link" data-comment-id="' . $comment->comment_ID . '">Reply</a>';
        
        // Three-dot menu for top-level comments
        if ($can_edit) {
            $html .= '<div class="comment-options">';
            $html .= '<button class="comment-options-btn" data-comment-id="' . $comment->comment_ID . '" aria-label="Comment options">⋯</button>';
            $html .= '<div class="comment-options-menu" style="display:none;">';
            $html .= '<button class="comment-option-item edit-comment" data-comment-id="' . $comment->comment_ID . '"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Edit</button>';
            $html .= '<button class="comment-option-item delete-comment" data-comment-id="' . $comment->comment_ID . '"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg> Delete</button>';
            $html .= '<button class="comment-option-item share-comment" data-comment-id="' . $comment->comment_ID . '"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg> Share</button>';
            $html .= '<button class="comment-option-item report-comment" disabled><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Report</button>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        // Reply form
        $html .= '<div class="reply-form" id="reply-form-' . $comment->comment_ID . '" style="display:none;">';
        $html .= '<input type="text" placeholder="Write a reply..." />';
        $html .= '<button class="submit-reply" data-comment-id="' . $comment->comment_ID . '">Submit</button>';
        $html .= '<button class="cancel-reply">Cancel</button>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        // Get replies for this comment
        $replies = get_comments(array(
            'parent' => $comment->comment_ID,
            'status' => 'approve',
            'order' => 'ASC'
        ));
        
        if ($replies) {
            $reply_count = count($replies);
            $html .= '<div class="comment-replies-wrapper">';
            $html .= '<div class="replies-spacer"></div>';
            $html .= '<div class="comment-replies">';
            $html .= '<div class="replies-toggle" data-comment-id="' . $comment->comment_ID . '">';
            $html .= '<span class="replies-count">' . $reply_count . ' ' . ($reply_count === 1 ? 'reply' : 'replies') . '</span>';
            $html .= '<span class="replies-arrow">></span>';
            $html .= '</div>';
            $html .= '<div class="replies-content" style="display: none;">';
            foreach ($replies as $reply) {
                $html .= render_comment_with_replies($reply); // Recursive call for nested replies
            }
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
}

// AJAX comment submission
add_action('wp_ajax_submit_comment', 'handle_ajax_comment');
add_action('wp_ajax_nopriv_submit_comment', 'handle_ajax_comment');

function handle_ajax_comment() {
    $post_id = intval($_POST['post_id']);
    $comment_content = sanitize_textarea_field($_POST['comment']);
    $comment_parent = isset($_POST['comment_parent']) ? intval($_POST['comment_parent']) : 0;
    
    $comment_data = array(
        'comment_post_ID' => $post_id,
        'comment_content' => $comment_content,
        'comment_author' => wp_get_current_user()->display_name,
        'comment_author_email' => wp_get_current_user()->user_email,
        'comment_approved' => 1,
        'comment_parent' => $comment_parent,
        'user_id' => get_current_user_id()
    );
    
    $comment_id = wp_insert_comment($comment_data);
    
    if ($comment_id) {
        // Get the new comment object
        $new_comment = get_comment($comment_id);
        
        // Render the comment HTML
        $comment_html = render_comment_with_replies($new_comment);
        
        wp_send_json_success(array(
            'message' => 'Comment submitted',
            'comment_html' => $comment_html,
            'comment_id' => $comment_id,
            'is_reply' => $comment_parent > 0
        ));
    } else {
        wp_send_json_error('Failed to submit comment');
    }
}



// Create likes table
function create_likes_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'postpic_likes';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        post_id bigint(20) NOT NULL,
        comment_id bigint(20) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_like (user_id, post_id, comment_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}


// Toggle like - WordPress standards with nonce and auth check
add_action('wp_ajax_toggle_like', 'handle_toggle_like');
add_action('wp_ajax_nopriv_toggle_like', 'handle_toggle_like');

function handle_toggle_like() {
    if (!wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Must be logged in to like');
        return;
    }
    
    $user_id = get_current_user_id();
    $post_id = intval($_POST['post_id']);
    $comment_id = isset($_POST['comment_id']) && $_POST['comment_id'] ? intval($_POST['comment_id']) : null;
    
    global $wpdb;
    $table = $wpdb->prefix . 'postpic_likes';
    
    // Debug: Check if table exists and has data
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
    error_log('Table exists: ' . ($table_exists ? 'YES' : 'NO'));
    
    if ($table_exists) {
        $total_records = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        error_log('Total records in table: ' . $total_records);
    }
    
    // Check if already liked
    if ($comment_id) {
        // For comment likes: look for specific comment_id
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND post_id = %d AND comment_id = %d",
            $user_id, $post_id, $comment_id
        ));
    } else {
        // For post likes: ONLY look for NULL comment_id (not 0)
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND post_id = %d AND comment_id IS NULL",
            $user_id, $post_id
        ));
    }
    
    error_log('Existing like found: ' . ($existing ? 'YES (ID: ' . $existing . ')' : 'NO'));
    
    if ($existing) {
        // Unlike
        $wpdb->delete($table, ['id' => $existing]);
        $liked = false;
        error_log('Deleted existing like');
    } else {
        // Like
        $insert_data = [
            'user_id' => $user_id,
            'post_id' => $post_id
        ];
        
        if ($comment_id) {
            $insert_data['comment_id'] = $comment_id;
        }
        
        $result = $wpdb->insert($table, $insert_data);
        
        if ($result === false) {
            error_log('Insert failed: ' . $wpdb->last_error);
            wp_send_json_error('Database insert failed');
            return;
        }
        
        $liked = true;
        error_log('Inserted new like');
    }
    
    // Get updated count
    if ($comment_id) {
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE post_id = %d AND comment_id = %d",
            $post_id, $comment_id
        ));
    } else {
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE post_id = %d AND comment_id IS NULL",
            $post_id
        ));
    }
    
    error_log('Final count query result: ' . ($count === null ? 'NULL' : $count));
    
    // Ensure count is never null
    $count = $count ? $count : 0;
    
    wp_send_json_success([
        'liked' => $liked,
        'count' => $count
    ]);
}

// AJAX handler for editing comments
add_action('wp_ajax_edit_comment', 'handle_edit_comment');
add_action('wp_ajax_nopriv_edit_comment', 'handle_edit_comment');

function handle_edit_comment() {
    if (!wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Must be logged in');
        return;
    }
    
    $comment_id = intval($_POST['comment_id']);
    $new_content = sanitize_textarea_field($_POST['comment_content']);
    
    $comment = get_comment($comment_id);
    
    if (!$comment) {
        wp_send_json_error('Comment not found');
        return;
    }
    
    $current_user_id = get_current_user_id();
    
    // Check permissions
    if ($comment->user_id != $current_user_id && !current_user_can('moderate_comments')) {
        wp_send_json_error('Permission denied');
        return;
    }
    
    // Update comment
    $result = wp_update_comment([
        'comment_ID' => $comment_id,
        'comment_content' => $new_content
    ]);
    
    if ($result) {
        wp_send_json_success([
            'message' => 'Comment updated',
            'content' => esc_html($new_content)
        ]);
    } else {
        wp_send_json_error('Failed to update comment');
    }
}

// AJAX handler for deleting comments
add_action('wp_ajax_delete_comment', 'handle_delete_comment');
add_action('wp_ajax_nopriv_delete_comment', 'handle_delete_comment');

function handle_delete_comment() {
    if (!wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Must be logged in');
        return;
    }
    
    $comment_id = intval($_POST['comment_id']);
    $comment = get_comment($comment_id);
    
    if (!$comment) {
        wp_send_json_error('Comment not found');
        return;
    }
    
    $current_user_id = get_current_user_id();
    $is_admin = current_user_can('moderate_comments');
    $is_owner = ($comment->user_id == $current_user_id);
    
    // Check permissions
    if (!$is_owner && !$is_admin) {
        wp_send_json_error('Permission denied');
        return;
    }
    
    $post_id = $comment->comment_post_ID;
    $is_reply = ($comment->comment_parent > 0);
    $post = get_post($post_id);
    $post_author_id = $post ? $post->post_author : 0;
    
    // Check if this is a critique comment
    $is_critique = get_comment_meta($comment_id, '_is_critique', true);
    
    // Count replies to this comment
    $replies = get_comments([
        'parent' => $comment_id,
        'count' => true,
        'status' => 'approve'
    ]);
    $reply_count = (int) $replies;
    
    // SCENARIO 1: Admin deletes - always force delete everything
    if ($is_admin) {
        // Get all reply IDs to delete them manually (wp_delete_comment doesn't cascade)
        $reply_ids = get_comments([
            'parent' => $comment_id,
            'status' => 'approve',
            'fields' => 'ids'
        ]);
        
        // Delete all replies first
        $deleted_count = 0;
        foreach ($reply_ids as $reply_id) {
            if (wp_delete_comment($reply_id, true)) {
                $deleted_count++;
            }
        }
        
        // Then delete the parent comment
        $result = wp_delete_comment($comment_id, true);
        
        if ($result) {
            $deleted_count++; // Add parent to count
            
            // If critique, decrement counters
            if ($is_critique && $post_author_id > 0) {
                // Decrement commenter's given count
                $given_count = (int) get_user_meta($current_user_id, '_critiques_given_count', true);
                $given_posts = get_user_meta($current_user_id, '_critiques_given_posts', true);
                if (!is_array($given_posts)) {
                    $given_posts = [];
                }
                
                if ($given_count > 0) {
                    update_user_meta($current_user_id, '_critiques_given_count', $given_count - 1);
                }
                if (($key = array_search($post_id, $given_posts)) !== false) {
                    unset($given_posts[$key]);
                    update_user_meta($current_user_id, '_critiques_given_posts', array_values($given_posts));
                }
                
                // Decrement post author's received count
                $received_count = (int) get_user_meta($post_author_id, '_critiques_received_count', true);
                $received_posts = get_user_meta($post_author_id, '_critiques_received_posts', true);
                if (!is_array($received_posts)) {
                    $received_posts = [];
                }
                
                if ($received_count > 0) {
                    update_user_meta($post_author_id, '_critiques_received_count', $received_count - 1);
                }
                if (($key = array_search($post_id, $received_posts)) !== false) {
                    unset($received_posts[$key]);
                    update_user_meta($post_author_id, '_critiques_received_posts', array_values($received_posts));
                }
            }
            
            wp_send_json_success([
                'message' => 'Comment deleted',
                'post_id' => $post_id,
                'deleted' => $deleted_count,
                'soft_deleted' => false
            ]);
        } else {
            wp_send_json_error('Failed to delete comment');
        }
        return;
    }
    
    // SCENARIO 2 & 4: User deletes own reply OR comment with no replies - full delete
    if ($is_reply || $reply_count == 0) {
        $result = wp_delete_comment($comment_id, true); // Force delete
        
        if ($result) {
            // If critique, decrement counters
            if ($is_critique && $post_author_id > 0) {
                // Decrement commenter's given count
                $given_count = (int) get_user_meta($current_user_id, '_critiques_given_count', true);
                $given_posts = get_user_meta($current_user_id, '_critiques_given_posts', true);
                if (!is_array($given_posts)) {
                    $given_posts = [];
                }
                
                if ($given_count > 0) {
                    update_user_meta($current_user_id, '_critiques_given_count', $given_count - 1);
                }
                if (($key = array_search($post_id, $given_posts)) !== false) {
                    unset($given_posts[$key]);
                    update_user_meta($current_user_id, '_critiques_given_posts', array_values($given_posts));
                }
                
                // Decrement post author's received count
                $received_count = (int) get_user_meta($post_author_id, '_critiques_received_count', true);
                $received_posts = get_user_meta($post_author_id, '_critiques_received_posts', true);
                if (!is_array($received_posts)) {
                    $received_posts = [];
                }
                
                if ($received_count > 0) {
                    update_user_meta($post_author_id, '_critiques_received_count', $received_count - 1);
                }
                if (($key = array_search($post_id, $received_posts)) !== false) {
                    unset($received_posts[$key]);
                    update_user_meta($post_author_id, '_critiques_received_posts', array_values($received_posts));
                }
            }
            
            wp_send_json_success([
                'message' => 'Comment deleted',
                'post_id' => $post_id,
                'deleted' => 1,
                'soft_deleted' => false
            ]);
        } else {
            wp_send_json_error('Failed to delete comment');
        }
        return;
    }
    
    // SCENARIO 3: User deletes own parent comment that has replies - soft delete
    if ($is_owner && !$is_reply && $reply_count > 0) {
        $result = wp_update_comment([
            'comment_ID' => $comment_id,
            'comment_content' => '[deleted]',
            'comment_author' => '---'
        ]);
        
        if ($result) {
            // Add meta flag to indicate this was soft-deleted
            add_comment_meta($comment_id, '_comment_soft_deleted', '1', true);
            
            wp_send_json_success([
                'message' => 'Comment deleted',
                'post_id' => $post_id,
                'deleted' => 0,
                'soft_deleted' => true
            ]);
        } else {
            wp_send_json_error('Failed to delete comment');
        }
        return;
    }
    
    wp_send_json_error('Unable to process delete request');
}

