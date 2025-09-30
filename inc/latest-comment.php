<?php
// Latest comment display in query loop

// Shortcode: [latest_comment]
function hrphoto_latest_comment_shortcode($atts = array()) {
    global $post;
    if (!$post) return '';
    
    $post_id = $post->ID;
    
    // Get latest approved comment
    $comments = get_comments(array(
        'post_id' => $post_id,
        'status' => 'approve',
        'number' => 1,
        'order' => 'DESC',
        'orderby' => 'comment_date'
    ));
    
    if (empty($comments)) {
        return ''; // Completely hidden if no comments
    }
    
    $comment = $comments[0];
    $avatar = get_avatar($comment->comment_author_email, 30, '', '', array('class' => 'latest-comment-avatar'));
    $comment_text = esc_html($comment->comment_content);
    
    // Truncate at word boundary with ellipsis
    $max_chars = 150;
    if (mb_strlen($comment_text) > $max_chars) {
        // Find last space before character limit
        $truncated = mb_substr($comment_text, 0, $max_chars);
        $last_space = mb_strrpos($truncated, ' ');
        if ($last_space !== false) {
            $comment_text = mb_substr($comment_text, 0, $last_space) . '...';
        } else {
            $comment_text = $truncated . '...';
        }
    }
    
    $html = '<div class="latest-comment-display" data-post-id="' . esc_attr($post_id) . '">';
    $html .= $avatar;
    $html .= '<div class="latest-comment-text">' . $comment_text . '</div>';
    $html .= '</div>';
    
    return $html;
}
add_shortcode('latest_comment', 'hrphoto_latest_comment_shortcode');

// AJAX handler to get latest comment
add_action('wp_ajax_get_latest_comment', 'hrphoto_get_latest_comment');
add_action('wp_ajax_nopriv_get_latest_comment', 'hrphoto_get_latest_comment');

function hrphoto_get_latest_comment() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $post_id = intval($_POST['post_id']);
    
    // Get latest approved comment
    $comments = get_comments(array(
        'post_id' => $post_id,
        'status' => 'approve',
        'number' => 1,
        'order' => 'DESC',
        'orderby' => 'comment_date'
    ));
    
    if (empty($comments)) {
        wp_send_json_success(array(
            'has_comment' => false,
            'html' => ''
        ));
        return;
    }
    
    $comment = $comments[0];
    $avatar = get_avatar($comment->comment_author_email, 30, '', '', array('class' => 'latest-comment-avatar'));
    $comment_text = esc_html($comment->comment_content);
    
    // Truncate at word boundary with ellipsis
    $max_chars = 150;
    if (mb_strlen($comment_text) > $max_chars) {
        // Find last space before character limit
        $truncated = mb_substr($comment_text, 0, $max_chars);
        $last_space = mb_strrpos($truncated, ' ');
        if ($last_space !== false) {
            $comment_text = mb_substr($comment_text, 0, $last_space) . '...';
        } else {
            $comment_text = $truncated . '...';
        }
    }
    
    $html = '<div class="latest-comment-display" data-post-id="' . esc_attr($post_id) . '">';
    $html .= $avatar;
    $html .= '<div class="latest-comment-text">' . $comment_text . '</div>';
    $html .= '</div>';
    
    wp_send_json_success(array(
        'has_comment' => true,
        'html' => $html
    ));
}
