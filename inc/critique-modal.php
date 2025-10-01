<?php
/**
 * Critique Modal - Handlers for critique comment submission
 */

if (!defined('ABSPATH')) { exit; }

// Check if user already has a critique on this post
add_action('wp_ajax_get_user_critique', 'handle_get_user_critique');
add_action('wp_ajax_nopriv_get_user_critique', 'handle_get_user_critique');

function handle_get_user_critique() {
    if (!wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Must be logged in');
        return;
    }
    
    $post_id = intval($_POST['post_id']);
    if ($post_id <= 0) {
        wp_send_json_error('Invalid post ID');
        return;
    }
    
    $user_id = get_current_user_id();
    
    // Find existing critique by this user on this post
    $existing_critique = get_comments([
        'post_id' => $post_id,
        'user_id' => $user_id,
        'status' => 'approve',
        'meta_query' => [
            [
                'key' => '_is_critique',
                'value' => '1'
            ]
        ],
        'number' => 1
    ]);
    
    if (!empty($existing_critique)) {
        $critique = $existing_critique[0];
        wp_send_json_success([
            'has_critique' => true,
            'comment_id' => $critique->comment_ID,
            'content' => $critique->comment_content
        ]);
    } else {
        wp_send_json_success([
            'has_critique' => false
        ]);
    }
}

// Submit or update critique
add_action('wp_ajax_submit_critique', 'handle_submit_critique');
add_action('wp_ajax_nopriv_submit_critique', 'handle_submit_critique');

function handle_submit_critique() {
    if (!wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Must be logged in');
        return;
    }
    
    $post_id = intval($_POST['post_id']);
    $critique_content = sanitize_textarea_field($_POST['critique']);
    $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
    
    // Validate
    if ($post_id <= 0) {
        wp_send_json_error('Invalid post ID');
        return;
    }
    
    if (mb_strlen($critique_content) < 100) {
        wp_send_json_error('Critique must be at least 100 characters');
        return;
    }
    
    if (mb_strlen($critique_content) > 5000) {
        wp_send_json_error('Critique cannot exceed 5000 characters');
        return;
    }
    
    $user_id = get_current_user_id();
    $user = wp_get_current_user();
    $post = get_post($post_id);
    
    if (!$post) {
        wp_send_json_error('Post not found');
        return;
    }
    
    $post_author_id = $post->post_author;
    
    // Check if editing existing critique
    if ($comment_id > 0) {
        $existing = get_comment($comment_id);
        if (!$existing || $existing->user_id != $user_id) {
            wp_send_json_error('Cannot edit this critique');
            return;
        }
        
        // Update existing critique
        $result = wp_update_comment([
            'comment_ID' => $comment_id,
            'comment_content' => $critique_content
        ]);
        
        if ($result) {
            // Re-fetch comment for rendering
            $comment = get_comment($comment_id);
            if (function_exists('render_comment_with_replies')) {
                $comment_html = render_comment_with_replies($comment);
            } else {
                $comment_html = '';
            }
            
            wp_send_json_success([
                'message' => 'Critique updated',
                'comment_id' => $comment_id,
                'comment_html' => $comment_html,
                'is_new' => false
            ]);
        } else {
            wp_send_json_error('Failed to update critique');
        }
        return;
    }
    
    // Create new critique
    $comment_data = [
        'comment_post_ID' => $post_id,
        'comment_content' => $critique_content,
        'comment_author' => $user->display_name,
        'comment_author_email' => $user->user_email,
        'comment_approved' => 1,
        'comment_parent' => 0,
        'user_id' => $user_id
    ];
    
    $new_comment_id = wp_insert_comment($comment_data);
    
    if ($new_comment_id) {
        // Mark as critique
        add_comment_meta($new_comment_id, '_is_critique', '1', true);
        
        // Update commenter's meta (critiques given)
        $given_count = (int) get_user_meta($user_id, '_critiques_given_count', true);
        $given_posts = get_user_meta($user_id, '_critiques_given_posts', true);
        if (!is_array($given_posts)) {
            $given_posts = [];
        }
        
        $given_count++;
        if (!in_array($post_id, $given_posts)) {
            $given_posts[] = $post_id;
        }
        
        update_user_meta($user_id, '_critiques_given_count', $given_count);
        update_user_meta($user_id, '_critiques_given_posts', $given_posts);
        
        // Update post author's meta (critiques received)
        $received_count = (int) get_user_meta($post_author_id, '_critiques_received_count', true);
        $received_posts = get_user_meta($post_author_id, '_critiques_received_posts', true);
        if (!is_array($received_posts)) {
            $received_posts = [];
        }
        
        $received_count++;
        if (!in_array($post_id, $received_posts)) {
            $received_posts[] = $post_id;
        }
        
        update_user_meta($post_author_id, '_critiques_received_count', $received_count);
        update_user_meta($post_author_id, '_critiques_received_posts', $received_posts);
        
        // Render comment HTML
        $comment = get_comment($new_comment_id);
        if (function_exists('render_comment_with_replies')) {
            $comment_html = render_comment_with_replies($comment);
        } else {
            $comment_html = '';
        }
        
        wp_send_json_success([
            'message' => 'Critique submitted',
            'comment_id' => $new_comment_id,
            'comment_html' => $comment_html,
            'is_new' => true
        ]);
    } else {
        wp_send_json_error('Failed to submit critique');
    }
}

