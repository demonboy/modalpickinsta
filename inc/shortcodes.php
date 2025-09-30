<?php
// Want feedback action icon (shows only if post has constructive feedback tag)
add_shortcode('want-feedback-icon', function($atts = []) {
    $atts = shortcode_atts(['id' => 0], $atts, 'want-feedback-icon');
    $post_id = (int) $atts['id'];
    if (!$post_id) { global $post; if ($post instanceof WP_Post) { $post_id = (int) $post->ID; } }
    if (!$post_id) { $the_id = (int) get_the_ID(); if ($the_id) { $post_id = $the_id; } }
    if (!$post_id) { $qo = get_queried_object(); if ($qo instanceof WP_Post) { $post_id = (int) $qo->ID; } }
    if ($post_id <= 0) return '';
    $ptype = get_post_type($post_id);
    if (!in_array($ptype, array('1hrphoto','story'), true)) return '';
    if (get_post_meta($post_id, 'wants_constructive_feedback', true) !== 'yes') return '';
    $svg = '<svg width="40px" height="40px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">'
         . '<path d="M8.24999 18L5.24999 20.25V15.75H2.25C1.85217 15.75 1.47064 15.5919 1.18934 15.3106C0.908034 15.0293 0.749999 14.6478 0.749999 14.25V2.25C0.749999 1.85217 0.908034 1.47064 1.18934 1.18934C1.47064 0.908034 1.85217 0.749999 2.25 0.749999H18.75C19.1478 0.749999 19.5293 0.908034 19.8106 1.18934C20.0919 1.47064 20.25 1.85217 20.25 2.25V6.71484" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
         . '<path d="M5.24999 5.24999H15.75" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
         . '<path d="M5.24999 9.74999H8.24999" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
         . '<path d="M23.25 18.75H20.25V23.25L15.75 18.75H11.25V9.74999H23.25V18.75Z" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
         . '<path d="M19.5 15H15" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
         . '</svg>';
    return '<div class="icon-item want-feedback-icon" title="Constructive feedback" aria-label="Constructive feedback">' . $svg . '</div>';
});

// Underscore alias per request: [want_feedback_icon]
add_shortcode('want_feedback_icon', function($atts = []) {
    $atts = shortcode_atts(['id' => 0], $atts, 'want_feedback_icon');
    $post_id = (int) $atts['id'];
    if (!$post_id) { global $post; if ($post instanceof WP_Post) { $post_id = (int) $post->ID; } }
    if (!$post_id) { $the_id = (int) get_the_ID(); if ($the_id) { $post_id = $the_id; } }
    if (!$post_id) { $qo = get_queried_object(); if ($qo instanceof WP_Post) { $post_id = (int) $qo->ID; } }
    if ($post_id <= 0) return '';
    $ptype = get_post_type($post_id);
    if (!in_array($ptype, array('1hrphoto','story'), true)) return '';
    if (get_post_meta($post_id, 'wants_constructive_feedback', true) !== 'yes') return '';
    $svg = '<svg width="40px" height="40px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">'
         . '<path d="M8.24999 18L5.24999 20.25V15.75H2.25C1.85217 15.75 1.47064 15.5919 1.18934 15.3106C0.908034 15.0293 0.749999 14.6478 0.749999 14.25V2.25C0.749999 1.85217 0.908034 1.47064 1.18934 1.18934C1.47064 0.908034 1.85217 0.749999 2.25 0.749999H18.75C19.1478 0.749999 19.5293 0.908034 19.8106 1.18934C20.0919 1.47064 20.25 1.85217 20.25 2.25V6.71484" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
         . '<path d="M5.24999 5.24999H15.75" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
         . '<path d="M5.24999 9.74999H8.24999" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
         . '<path d="M23.25 18.75H20.25V23.25L15.75 18.75H11.25V9.74999H23.25V18.75Z" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
         . '<path d="M19.5 15H15" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
         . '</svg>';
    return '<div class="icon-item want-feedback-icon" title="Constructive feedback" aria-label="Constructive feedback">' . $svg . '</div>';
});
// inc/shortcodes.php

//COMMENT ICON SHORTCODE

function comment_icon_shortcode($atts) {
    global $post;
    $post_id = $post->ID;
    $comment_count = get_comments_number($post_id);
    
    // Determine if viewer has commented on this post (approved only)
    $has_commented = false;
    $args_common = array('post_id' => $post_id, 'status' => 'approve', 'count' => true);
    if (is_user_logged_in()) {
        // Primary: check by user_id
        $args = $args_common;
        $args['user_id'] = get_current_user_id();
        $count_by_user = (int) get_comments($args);
        if ($count_by_user > 0) {
            $has_commented = true;
        } else {
            // Fallback: check by current user's email (in case user_id not stored)
            $user = wp_get_current_user();
            $user_email = is_a($user, 'WP_User') ? $user->user_email : '';
            if ($user_email && is_email($user_email)) {
                $args = $args_common;
                $args['author_email'] = $user_email;
                $has_commented = ( get_comments($args) > 0 );
            }
        }
    } else {
        // Logged-out: use wp_get_current_commenter() (WordPress-managed cookies)
        $commenter = function_exists('wp_get_current_commenter') ? wp_get_current_commenter() : array();
        $author_email = isset($commenter['comment_author_email']) ? $commenter['comment_author_email'] : '';
        if ($author_email && is_email($author_email)) {
            $args = $args_common;
            $args['author_email'] = $author_email;
            $has_commented = ( get_comments($args) > 0 );
        }
    }

    // 40x40 icons
    $svg_not_commented = '<svg fill="#000000" width="40" height="40" viewBox="0 0 1920 1920" xmlns="http://www.w3.org/2000/svg"><path d="M84 0v1423.143h437.875V1920l621.235-496.857h692.39V0H84Zm109.469 109.464H1726.03V1313.57h-621.235l-473.452 378.746V1313.57H193.469V109.464Z" fill-rule="evenodd"/></svg>';
    $svg_commented     = '<svg fill="#da5742" width="40" height="40" viewBox="0 0 1920 1920" xmlns="http://www.w3.org/2000/svg"><path d="M84 0v1423.143h437.875V1920l621.235-496.857h692.39V0z" fill-rule="evenodd"/></svg>';

    $svg = $has_commented ? $svg_commented : $svg_not_commented;

    return '<div class="icon-item comment-icon" data-post-id="' . $post_id . '" title="Comments" aria-label="Comments">' .
        $svg .
        '<span class="comment-count">' . $comment_count . '</span>' .
    '</div>';
}
add_shortcode('comment_icon', 'comment_icon_shortcode');

//POST LIKE ICON SHORTCODE
function post_like_icon_shortcode($atts) {
    global $post;
    $post_id = $post->ID;
    
    // Get post like count
    global $wpdb;
    $table = $wpdb->prefix . 'postpic_likes';
    $like_count = 0;
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
        $like_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE post_id = %d AND comment_id IS NULL",
            $post_id
        ));
    }
    // Post like uses a thumb icon via CSS background on a span.like-icon (40x40)

    // Determine if current user has liked this post to set initial state after refresh
    $btn_class = 'like-button';
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND post_id = %d AND comment_id IS NULL LIMIT 1",
            $user_id,
            $post_id
        ));
        if (!empty($existing)) { $btn_class .= ' liked'; }
    }

    $count_class = ((int)$like_count === 0) ? 'like-count is-zero' : 'like-count';
    return '<div class="icon-item post-like-icon">'
         .   '<button class="' . $btn_class . '" data-post-id="' . $post_id . '" title="Like" aria-label="Like"><span class="like-icon" aria-hidden="true"></span><span class="' . $count_class . '">' . (int) $like_count . '</span></button>'
         . '</div>';
}
add_shortcode('post_like_icon', 'post_like_icon_shortcode');

//CRITIQUE WELCOME SHORTCODE
function critique_welcome_shortcode($atts) {
    global $post;
    $post_id = isset($atts['id']) ? (int) $atts['id'] : ($post ? $post->ID : 0);
    
    if (!$post_id) {
        return '';
    }
    
    // Only show if constructive feedback is wanted
    if (get_post_meta($post_id, 'wants_constructive_feedback', true) !== 'yes') {
        return '';
    }
    
    // Get post author's display name
    $post_obj = get_post($post_id);
    if (!$post_obj) {
        return '';
    }
    
    $author_display_name = get_the_author_meta('display_name', $post_obj->post_author);
    $alt_text = esc_attr($author_display_name . ' is looking for constructive feedback on their work');
    
    // Optimized inline SVG (20px size)
    $svg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="' . $alt_text . '">'
         . '<path d="M8.25 18L5.25 20.25V15.75H2.25C1.852 15.75 1.471 15.592 1.189 15.311C.908 15.029.75 14.648.75 14.25V2.25C.75 1.852.908 1.471 1.189 1.189C1.471.908 1.852.75 2.25.75H18.75C19.148.75 19.529.908 19.811 1.189C20.092 1.471 20.25 1.852 20.25 2.25V6.715" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
         . '<path d="M5.25 5.25H15.75M5.25 9.75H8.25" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
         . '<path d="M23.25 18.75H20.25V23.25L15.75 18.75H11.25V9.75H23.25V18.75Z" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
         . '<path d="M19.5 15H15" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
         . '</svg>';
    
    return '<div class="icon-item critique-welcome-icon">'
         . '<button class="critique-welcome-btn comment-icon" data-post-id="' . esc_attr($post_id) . '" title="' . $alt_text . '" aria-label="' . $alt_text . '">'
         . $svg
         . '<span class="critique-welcome-text">Critique welcome</span>'
         . '</button>'
         . '</div>';
}
add_shortcode('critique_welcome', 'critique_welcome_shortcode');

/**
 * Front-end ACF profile editor shortcode.
 *
 * Usage: [acf_frontend_profile_editor group="group_123abc"]
 *
 * @param array $atts Shortcode attributes.
 */
function acf_frontend_profile_editor_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to edit your profile.</p>';
    }

    // Extract shortcode attributes
    $atts = shortcode_atts([
        'group' => '', // ACF field group key
    ], $atts, 'acf_frontend_profile_editor');

    $group_key = $atts['group'];

    // Include the form template
    ob_start();
    include get_stylesheet_directory() . '/templates/profile-editor-form.php';
    return ob_get_clean();
}
add_shortcode('acf_frontend_profile_editor', 'acf_frontend_profile_editor_shortcode');

//CREATE POST SHORTCODE
function creat_post_shortcode($atts) {
    // Hide when not logged in
    if (!is_user_logged_in()) { return ''; }

    // Extract shortcode attributes
    $atts = shortcode_atts([
        'text' => 'Create',
        'class' => '',
        'style' => '',
        'icon' => 'false'
    ], $atts, 'create_post');

    $text = esc_html($atts['text']);
    $class = esc_attr($atts['class']);
    $style = esc_attr($atts['style']);
    $is_icon = ($atts['icon'] === 'true');

    // No styling: do not emit classes or inline styles

    // Always return a plain text link (no icon, no styling)
    return '<a href="#" data-create-post="1" aria-label="Create">' . $text . '</a>';
}
add_shortcode('create_post', 'creat_post_shortcode');

// PROFILE MODAL SHORTCODE
function profile_modal_shortcode($atts) {
    $atts = shortcode_atts([
        'text' => 'Profile',
        'class' => '',
        'style' => '',
        'icon' => 'false',
        'user' => 'me'
    ], $atts, 'profile_modal');

    $text = esc_html($atts['text']);
    $class = esc_attr($atts['class']);
    $style = esc_attr($atts['style']);
    $is_icon = ($atts['icon'] === 'true');
    $user = esc_attr($atts['user']); // 'me' or nicename

    $css_classes = 'profile-modal-link';
    if (!empty($class)) { $css_classes .= ' ' . $class; }
    $inline_styles = !empty($style) ? ' style="' . $style . '"' : '';

    // If not logged in, this acts as Login button opening auth view
    $is_logged_in = is_user_logged_in();
    $label = $is_logged_in ? 'You' : 'Login';
    $dataUser = $is_logged_in ? $user : 'auth';
    if ($is_icon) {
        return '<button class="' . $css_classes . '" data-user="' . $dataUser . '"' . $inline_styles . ' title="' . esc_attr($label) . '">+</button>';
    }
    if ($is_logged_in) {
        $uid = get_current_user_id();
        $avatar = get_avatar($uid, 40, '', $label, array('class' => 'menu-avatar-circle'));
        return '<a href="#" class="' . $css_classes . ' profile-menu-link" data-no-profile-modal data-user="' . $dataUser . '"' . $inline_styles . '>'
            . '<span class="profile-menu-avatar">' . $avatar . '</span>'
            . '</a>';
    }
    return '<a href="#" class="' . $css_classes . '" data-user="' . $dataUser . '"' . $inline_styles . '>' . esc_html($label) . '</a>';
}
add_shortcode('profile_modal', 'profile_modal_shortcode');

// FOLLOW AUTHOR SHORTCODE
function hrphoto_follow_author_shortcode($atts) {
    $atts = shortcode_atts([
        'author_id' => 0,
    ], $atts, 'follow_author');

    $author_id = (int) $atts['author_id'];
    if ($author_id <= 0) {
        global $post;
        if (!empty($post) && !empty($post->post_author)) {
            $author_id = (int) $post->post_author;
        } else {
            return '';
        }
    }
    // Hide self-follow button
    if (is_user_logged_in() && get_current_user_id() === $author_id) {
        return '';
    }

    $count = function_exists('hrphoto_get_followers_count') ? (int) hrphoto_get_followers_count($author_id) : 0;
    // Determine initial following state for current viewer
    $btn_class = 'follow-author-btn';
    if (is_user_logged_in() && function_exists('hrphoto_is_following')) {
        if (hrphoto_is_following(get_current_user_id(), $author_id)) {
            $btn_class .= ' is-following';
        }
    }

    // Two SVGs (40px): black for not following; filled for following (thumb filled color #e74c3c)
    $svg_unfollow = '<svg class="icon-unfollow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 500" width="40" height="40" aria-hidden="true">'
                  .   '<path fill="#000000" d="M494.64,486.4H.9v-88.1c0-57.25,46.41-103.66,103.66-103.66h286.42c57.25,0,103.66,46.41,103.66,103.66v88.1Z"/>'
                  .   '<circle fill="#000000" cx="247.77" cy="131.02" r="122"/>'
                  . '</svg>';
    $svg_follow = '<svg class="icon-follow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 500" width="40" height="40" aria-hidden="true">'
                .   '<path fill="#e74c3c" d="M494.64,486.4H.9v-88.1c0-57.25,46.41-103.66,103.66-103.66h286.42c57.25,0,103.66,46.41,103.66,103.66v88.1Z"/>'
                .   '<circle fill="#e74c3c" cx="247.77" cy="131.02" r="122"/>'
                . '</svg>';

    $count_class = ($count === 0) ? 'follow-count is-zero' : 'follow-count';
    $author_name = get_the_author_meta('display_name', $author_id);
    $label_text = 'Follow ' . $author_name;
    $btn = '<button class="' . $btn_class . '" data-author-id="' . $author_id . '" title="' . esc_attr($label_text) . '" aria-label="' . esc_attr($label_text) . '">' . $svg_unfollow . $svg_follow . '<span class="' . $count_class . '">' . $count . '</span></button>';
    return '<div class="icon-item follow-author-icon">' . $btn . '</div>';
}
add_shortcode('follow_author', 'hrphoto_follow_author_shortcode');

// FOLLOW BUTTON SHORTCODE (text-only, parent theme styles)
function hrphoto_follow_button_shortcode($atts) {
    $atts = shortcode_atts([
        'user' => '', // me | numeric ID | nicename/login (but we resolve to viewed author)
    ], $atts, 'follow_button');

    // Resolve target author ID (viewed user)
    $user_param = trim((string) $atts['user']);
    $author_id = 0;
    if ($user_param !== '') {
        if (is_numeric($user_param)) {
            $author_id = (int) $user_param;
        } else {
            $u = get_user_by('slug', $user_param);
            if (!$u) { $u = get_user_by('login', $user_param); }
            if ($u instanceof WP_User) { $author_id = (int) $u->ID; }
        }
    }
    if ($author_id <= 0 && is_author()) { $author_id = (int) get_queried_object_id(); }
    if ($author_id <= 0) { global $post; if (!empty($post) && !empty($post->post_author)) { $author_id = (int) $post->post_author; } }
    if ($author_id <= 0) { return ''; }
    // Hide self-follow button
    if (is_user_logged_in() && get_current_user_id() === $author_id) { return ''; }

    // Initial following state (class + label + aria)
    $is_following = false;
    if (is_user_logged_in() && function_exists('hrphoto_is_following')) {
        $is_following = (bool) hrphoto_is_following(get_current_user_id(), $author_id);
    }
    $btn_class = 'follow-author-btn' . ($is_following ? ' is-following' : '');
    $label = $is_following ? 'FOLLOWING' : 'FOLLOW';
    $aria_pressed = $is_following ? 'true' : 'false';

    return '<div class="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex"><div class="wp-block-button is-style-fill"><button type="button" class="wp-block-button__link wp-element-button ' . esc_attr($btn_class) . '" data-author-id="' . (int) $author_id . '" aria-pressed="' . $aria_pressed . '"><span class="follow-label">' . esc_html($label) . '</span></button></div></div>';
}
add_shortcode('follow_button', 'hrphoto_follow_button_shortcode');

// FOLLOWERS COUNT SHORTCODE (plain number)
function hrphoto_followers_count_shortcode($atts){
    $atts = shortcode_atts([
        'user' => '',
    ], $atts, 'followers_count');

    // Resolve author ID: explicit → author archive → current post author
    $user_param = trim((string) $atts['user']);
    $author_id = 0;
    if ($user_param !== '') {
        if (is_numeric($user_param)) {
            $author_id = (int) $user_param;
        } else {
            $u = get_user_by('slug', $user_param);
            if (!$u) { $u = get_user_by('login', $user_param); }
            if ($u instanceof WP_User) { $author_id = (int) $u->ID; }
        }
    }
    if ($author_id <= 0 && is_author()) { $author_id = (int) get_queried_object_id(); }
    if ($author_id <= 0) { global $post; if (!empty($post) && !empty($post->post_author)) { $author_id = (int) $post->post_author; } }
    if ($author_id <= 0) { return ''; }

    $count = function_exists('hrphoto_get_followers_count') ? (int) hrphoto_get_followers_count($author_id) : 0;
    return number_format_i18n($count);
}
add_shortcode('followers_count', 'hrphoto_followers_count_shortcode');

// COMMENT COUNT SHORTCODE (plain number)
function hrphoto_comment_count_shortcode($atts){
    $atts = shortcode_atts([
        'id' => 0,
    ], $atts, 'comment_count');

    $post_id = (int) $atts['id'];
    if ($post_id <= 0) {
        global $post;
        if ($post instanceof WP_Post) {
            $post_id = (int) $post->ID;
        }
    }
    if ($post_id <= 0) { return '0'; }

    $count = get_comments_number($post_id);
    return '<span class="comment-count-number" data-post-id="' . esc_attr($post_id) . '">' . number_format_i18n($count) . '</span>';
}
add_shortcode('comment_count', 'hrphoto_comment_count_shortcode');

// FOLLOW BUTTON SMALL SHORTCODE (inline, smaller)
function hrphoto_follow_button_small_shortcode($atts) {
    $atts = shortcode_atts([
        'user' => '',
    ], $atts, 'follow_button_small');

    // Resolve target author ID (viewed user), same as hrphoto_follow_button_shortcode
    $user_param = trim((string) $atts['user']);
    $author_id = 0;
    if ($user_param !== '') {
        if (is_numeric($user_param)) {
            $author_id = (int) $user_param;
        } else {
            $u = get_user_by('slug', $user_param);
            if (!$u) { $u = get_user_by('login', $user_param); }
            if ($u instanceof WP_User) { $author_id = (int) $u->ID; }
        }
    }
    if ($author_id <= 0 && is_author()) { $author_id = (int) get_queried_object_id(); }
    if ($author_id <= 0) { global $post; if (!empty($post) && !empty($post->post_author)) { $author_id = (int) $post->post_author; } }
    if ($author_id <= 0) { return ''; }
    // Hide self-follow button
    if (is_user_logged_in() && get_current_user_id() === $author_id) { return ''; }

    // Initial following state
    $is_following = false;
    if (is_user_logged_in() && function_exists('hrphoto_is_following')) {
        $is_following = (bool) hrphoto_is_following(get_current_user_id(), $author_id);
    }
    $btn_class = 'follow-author-btn' . ($is_following ? ' is-following' : '') . ' is-small';
    $label = $is_following ? 'FOLLOWING' : 'FOLLOW';
    $aria_pressed = $is_following ? 'true' : 'false';

    // Ensure small shortcode CSS is available (register+enqueue if needed)
    if (function_exists('wp_style_is') && function_exists('wp_enqueue_style')) {
        if (!wp_style_is('profile-shortcodes-css', 'enqueued')) {
            wp_enqueue_style(
                'profile-shortcodes-css',
                get_stylesheet_directory_uri() . '/assets/css/profile-shortcodes.css',
                array('1hrphoto-style'),
                filemtime(get_stylesheet_directory() . '/assets/css/profile-shortcodes.css')
            );
        }
    }

    // Inline-friendly: output just the button element (no block wrappers)
    return '<button type="button" class="wp-block-button__link wp-element-button ' . esc_attr($btn_class) . '" data-author-id="' . (int) $author_id . '" aria-pressed="' . $aria_pressed . '"><span class="follow-label">' . esc_html($label) . '</span></button>';
}
add_shortcode('follow_button_small', 'hrphoto_follow_button_small_shortcode');

// ARCHIVE SCOPED SEARCH SHORTCODE
function hrphoto_archive_scoped_search_shortcode($atts){
    $action = esc_url( home_url('/') );

    // Site-wide (non taxonomy context): keep collapsible UI and classes; replace icon with text
    if (!(is_category() || is_tag())) {
        $id = 'arch-search-form-sitewide';
        $toggle_label = 'Search';
        return '<div class="arch-search arch-search--collapsed">'
             .   '<a href="#" data-arch-toggle="1" aria-label="Open search" aria-expanded="false" aria-controls="' . esc_attr($id) . '">' . esc_html($toggle_label) . '</a>'
             .   '<form id="' . esc_attr($id) . '" role="search" method="get" action="' . $action . '" class="arch-search__form">'
             .     '<input class="arch-search__input" name="s" type="search" placeholder="Search the whole site" />'
             .     '<input type="hidden" name="post_type[]" value="post" />'
             .     '<input type="hidden" name="post_type[]" value="1hrphoto" />'
             .     '<input type="hidden" name="post_type[]" value="story" />'
             .     '<button class="arch-search__submit" type="submit" aria-label="Search">&rsaquo;</button>'
             .   '</form>'
             . '</div>';
    }

    // Taxonomy-scoped search (category or tag): keep collapsible UI and classes; replace icon with text
    $term = get_queried_object();
    if (!$term || is_wp_error($term)) {
        return '';
    }
    $taxonomy = $term->taxonomy === 'post_tag' ? 'tag' : 'category';
    $label = sprintf(
        'Search in the %s %s',
        esc_html($term->name),
        esc_html($taxonomy)
    );
    $id = 'arch-search-form-' . (int) $term->term_id;
    $toggle_label = 'Search';

    return '<div class="arch-search arch-search--collapsed">'
         .   '<a href="#" data-arch-toggle="1" aria-label="Open search" aria-expanded="false" aria-controls="' . esc_attr($id) . '">' . esc_html($toggle_label) . '</a>'
         .   '<form id="' . esc_attr($id) . '" role="search" method="get" action="' . $action . '" class="arch-search__form">'
         .     '<input class="arch-search__input" name="s" type="search" placeholder="' . esc_attr($label) . '" />'
         .     '<input type="hidden" name="tax_scope" value="' . esc_attr($taxonomy === 'tag' ? 'post_tag' : 'category') . '" />'
         .     '<input type="hidden" name="term_id" value="' . esc_attr( (string) $term->term_id ) . '" />'
         .     '<input type="hidden" name="post_type[]" value="post" />'
         .     '<input type="hidden" name="post_type[]" value="1hrphoto" />'
         .     '<input type="hidden" name="post_type[]" value="story" />'
         .     '<button class="arch-search__submit" type="submit" aria-label="Search">&rsaquo;</button>'
         .   '</form>'
         . '</div>';
}
add_shortcode('archive_scoped_search', 'hrphoto_archive_scoped_search_shortcode');

// PHOTOGRAPHER STATS SHORTCODE
function hrphoto_photographer_stats_shortcode($atts){
    $atts = shortcode_atts(array(
        'user' => 'me', // me | numeric ID | nicename/login
    ), $atts, 'photographer_stats');

    // Resolve user ID
    $user_param = trim((string) $atts['user']);
    $user_id = 0;
    // 1) Explicit attribute: numeric ID or slug/login (but not 'me')
    if (is_numeric($user_param)) {
        $user_id = (int) $user_param;
    } elseif ($user_param !== '' && $user_param !== 'me') {
        $u = get_user_by('slug', $user_param);
        if (!$u) { $u = get_user_by('login', $user_param); }
        if ($u instanceof WP_User) { $user_id = (int) $u->ID; }
    }
    // 2) Author archive context
    if ($user_id <= 0 && is_author()) { $user_id = (int) get_queried_object_id(); }
    // 3) Current post author context
    if ($user_id <= 0) { global $post; if (!empty($post) && !empty($post->post_author)) { $user_id = (int) $post->post_author; } }
    // 4) 'me' fallback when logged in
    if ($user_id <= 0 && $user_param === 'me' && is_user_logged_in()) { $user_id = get_current_user_id(); }
    if ($user_id <= 0) { return ''; }

    // Counts
    $posts_total = (int) count_user_posts($user_id, '1hrphoto', true) + (int) count_user_posts($user_id, 'story', true);
    $followers   = function_exists('hrphoto_get_followers_count') ? (int) hrphoto_get_followers_count($user_id) : 0;
    $following   = function_exists('hrphoto_get_following_count') ? (int) hrphoto_get_following_count($user_id) : 0;

    // Reuse modal classes for layout (no extra CSS needed)
    $author_url = get_author_posts_url($user_id);
    $html  = '<div class="profile-header-stats" data-user-id="' . esc_attr((string) $user_id) . '">';
    // Posts → author archive (no modal intercept)
    $html .=   '<a href="' . esc_url($author_url) . '" data-no-profile-modal class="profile-head-link" aria-label="' . esc_attr__('Posts','1hrphoto') . '">'
            .     '<span class="stat-stack"><span class="stat-label">' . esc_html__('Posts','1hrphoto') . '</span><span class="stat-count">' . number_format_i18n($posts_total) . '</span></span>'
            .   '</a>';
    // Followers → open follow modal (followers)
    $html .=   '<a href="#" class="profile-head-link" data-no-profile-modal data-section="followers" data-user-id="' . (int) $user_id . '" aria-label="' . esc_attr__('Followers','1hrphoto') . '">'
            .     '<span class="stat-stack"><span class="stat-label">' . esc_html__('Followers','1hrphoto') . '</span><span class="stat-count">' . number_format_i18n($followers) . '</span></span>'
            .   '</a>';
    // Following → open follow modal (following)
    $html .=   '<a href="#" class="profile-head-link" data-no-profile-modal data-section="following" data-user-id="' . (int) $user_id . '" aria-label="' . esc_attr__('Following','1hrphoto') . '">'
            .     '<span class="stat-stack"><span class="stat-label">' . esc_html__('Following','1hrphoto') . '</span><span class="stat-count">' . number_format_i18n($following) . '</span></span>'
            .   '</a>';
    $html .= '</div>';
    return $html;
}
add_shortcode('photographer_stats', 'hrphoto_photographer_stats_shortcode');

// PHOTOGRAPHER SUMMARY SHORTCODE
function hrphoto_photographer_summary_shortcode($atts){
    if (!function_exists('hrphoto_build_profile_summary')) { return ''; }
    $atts = shortcode_atts(array(
        'user' => 'me',
    ), $atts, 'photographer_summary');

    // Resolve user ID
    $user_param = trim((string) $atts['user']);
    $user_id = 0;
    // 1) Explicit attribute: numeric ID or slug/login (but not 'me')
    if (is_numeric($user_param)) {
        $user_id = (int) $user_param;
    } elseif ($user_param !== '' && $user_param !== 'me') {
        $u = get_user_by('slug', $user_param);
        if (!$u) { $u = get_user_by('login', $user_param); }
        if ($u instanceof WP_User) { $user_id = (int) $u->ID; }
    }
    // 2) Author archive context
    if ($user_id <= 0 && is_author()) { $user_id = (int) get_queried_object_id(); }
    // 3) Current post author context
    if ($user_id <= 0) { global $post; if (!empty($post) && !empty($post->post_author)) { $user_id = (int) $post->post_author; } }
    // 4) 'me' fallback when logged in
    if ($user_id <= 0 && $user_param === 'me' && is_user_logged_in()) { $user_id = get_current_user_id(); }
    if ($user_id <= 0) { return ''; }

    $paragraph = hrphoto_build_profile_summary($user_id);
    if ($paragraph === '') { return ''; }
    return '<p class="photographer-summary">' . esc_html($paragraph) . '</p>';
}
add_shortcode('photographer_summary', 'hrphoto_photographer_summary_shortcode');

// SHARE SHORTCODES
// [share_post id="optional"]
function onehr_share_post_shortcode($atts = array()) {
    $atts = shortcode_atts(array('id' => 0), $atts, 'share_post');
    $post_id = (int) $atts['id'];
    if ($post_id <= 0) { global $post; if ($post instanceof WP_Post) { $post_id = (int) $post->ID; } }
    if ($post_id <= 0) { return ''; }
    $ptype = get_post_type($post_id);
    if (!in_array($ptype, array('post','1hrphoto','story'), true)) { return ''; }

    $url   = onehr_share_home_url_for_post($post_id);
    $title = onehr_share_post_title($post_id);

    // Inline SVG provided by user, sized 40x40
    $svg = '<svg fill="#000000" width="24" height="24" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M385 464Q357 464 339 445 320 426 320 399 320 390 321 388L171 303Q154 320 129 320 102 320 83 301 64 282 64 255 64 229 83 211 102 192 129 192 154 192 171 209L321 125Q320 122 320 111 320 85 339 67 357 48 384 48 410 48 429 67 447 85 448 111 448 138 429 157 410 176 384 176 361 176 341 159L191 244Q192 246 192 255 192 265 191 268L341 353Q361 336 385 336 415 336 431 355 447 374 447 400 447 426 431 445 415 464 385 464Z"/></svg>';

    $btn = '<button type="button" class="onehr-share-open" data-no-profile-modal data-share-type="post" data-share-url="' . esc_url($url) . '" data-share-title="' . esc_attr($title) . '">' . $svg . '</button>';
    return '<div class="icon-item share-icon">' . $btn . '</div>';
}
add_shortcode('share_post', 'onehr_share_post_shortcode');

// [share_author id="optional|me"] → default current context author
function onehr_share_author_shortcode($atts = array()) {
    $atts = shortcode_atts(array('id' => 0), $atts, 'share_author');
    $author_id = (int) $atts['id'];
    if ($author_id <= 0) {
        if (is_author()) { $author_id = (int) get_queried_object_id(); }
        if ($author_id <= 0) { global $post; if ($post instanceof WP_Post) { $author_id = (int) $post->post_author; } }
    }
    if ($author_id <= 0) { return ''; }

    $data = onehr_share_author_data($author_id);
    $url   = $data['url'];
    $name  = $data['title'];
    $label = sprintf("share %s's profile", $name);

    // 20x20 icon then text
    $svg = '<svg fill="#000000" width="20" height="20" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M385 464Q357 464 339 445 320 426 320 399 320 390 321 388L171 303Q154 320 129 320 102 320 83 301 64 282 64 255 64 229 83 211 102 192 129 192 154 192 171 209L321 125Q320 122 320 111 320 85 339 67 357 48 384 48 410 48 429 67 447 85 448 111 448 138 429 157 410 176 384 176 361 176 341 159L191 244Q192 246 192 255 192 265 191 268L341 353Q361 336 385 336 415 336 431 355 447 374 447 400 447 426 431 445 415 464 385 464Z"/></svg>';
    $html = '<a href="#" class="onehr-share-open onehr-share-author" data-no-profile-modal data-share-type="author" data-share-url="' . esc_url($url) . '" data-share-title="' . esc_attr($name) . '">' . $svg . '<span class="onehr-share-author-label">' . esc_html($label) . '</span></a>';
    return $html;
}
add_shortcode('share_author', 'onehr_share_author_shortcode');

// POSTPIC TEST IMAGES SHORTCODE - Main feed display
function postpic_test_images_shortcode($atts) {
    $atts = shortcode_atts([
        'posts_per_page' => get_option('posts_per_page'),
        'post_type' => 'post,1hrphoto,story',
        'orderby' => 'date',
        'order' => 'DESC'
    ], $atts, 'postpic_test_images');

    $posts_per_page = intval($atts['posts_per_page']);
    $post_types = array_map('trim', explode(',', $atts['post_type']));
    $orderby = sanitize_text_field($atts['orderby']);
    $order = sanitize_text_field($atts['order']);

    // Query arguments
    $args = array(
        'post_type' => $post_types,
        'post_status' => 'publish',
        'posts_per_page' => $posts_per_page,
        'paged' => max(1, get_query_var('paged')),
        'orderby' => $orderby,
        'order' => $order,
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

    // Handle different query contexts
    if (is_archive()) {
        if (is_category()) {
            $args['category_name'] = get_queried_object()->slug;
        } elseif (is_tag()) {
            $args['tag'] = get_queried_object()->slug;
        } elseif (is_post_type_archive()) {
            $args['post_type'] = get_post_type();
        }
    } elseif (is_author()) {
        $args['author'] = get_queried_object_id();
    } elseif (is_search()) {
        $args['s'] = get_search_query();
    }

    $query = new WP_Query($args);
    
    if (!$query->have_posts()) {
        return '<div class="no-posts-found"><p>No posts found.</p></div>';
    }

    ob_start();
    ?>
    <div class="postpic-feed" data-infinite-scroll="true" data-page="1" data-max-pages="<?php echo $query->max_num_pages; ?>">
        <?php while ($query->have_posts()) : $query->the_post(); ?>
            <article class="post-item" data-post-id="<?php echo get_the_ID(); ?>">
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
        <?php endwhile; ?>
    </div>
    <?php
    
    // Store query info for infinite scroll
    echo '<script type="text/javascript">';
    echo 'window.postpicQueryInfo = {';
    echo 'maxPages: ' . $query->max_num_pages . ',';
    echo 'currentPage: ' . max(1, get_query_var('paged')) . ',';
    echo 'postsPerPage: ' . $posts_per_page . ',';
    echo 'postTypes: ' . json_encode($post_types) . ',';
    echo 'orderby: "' . $orderby . '",';
    echo 'order: "' . $order . '",';
    
    // Add context-specific information
    if (is_archive()) {
        echo 'queryType: "archive",';
        if (is_category()) {
            echo 'category: "' . get_queried_object()->slug . '",';
        } elseif (is_tag()) {
            echo 'tag: "' . get_queried_object()->slug . '",';
        } elseif (is_post_type_archive()) {
            echo 'postTypeArchive: "' . get_post_type() . '",';
        }
    } elseif (is_author()) {
        echo 'queryType: "author",';
        echo 'authorId: "' . get_queried_object_id() . '",';
    } elseif (is_search()) {
        echo 'queryType: "search",';
        echo 'searchQuery: "' . esc_js(get_search_query()) . '",';
    } else {
        echo 'queryType: "home",';
    }
    
    echo '};';
    echo '</script>';
    
    wp_reset_postdata();
    
    return ob_get_clean();
}
add_shortcode('postpic_test_images', 'postpic_test_images_shortcode');

// MY FEED URL SHORTCODE
// [my_feed] → outputs the URL to the viewer’s latest My Feed (saved settings)
function hrphoto_my_feed_shortcode() {
    // Resolve base My Feed URL
    $base = '';
    $p = get_page_by_path('my-feed');
    if ($p && !is_wp_error($p)) {
        $base = get_permalink($p->ID);
    }
    if ($base === '') {
        $base = home_url('/my-feed/');
    }

    // Logged-out: return base (auth guard handles login)
    if (!is_user_logged_in()) {
        return '<a href="' . esc_url(home_url('/')) . '">My Feed</a>';
    }

    $uid   = get_current_user_id();
    $scope = (string) get_user_meta($uid, 'hrphoto_feed_u_scope', true);
    if ($scope !== 'following' && $scope !== 'not_following') {
        $scope = 'everyone';
    }

    $likes = (string) get_user_meta($uid, 'hrphoto_feed_likes', true);
    $date  = (string) get_user_meta($uid, 'hrphoto_feed_date', true);
    if ($date !== 'oldest' && $date !== 'random') {
        $date = 'latest';
    }

    $ptype = (string) get_user_meta($uid, 'hrphoto_feed_view_ptype', true);
    $ptype = in_array($ptype, array('1hrphoto','story'), true) ? $ptype : '';

    $cats  = (array) get_user_meta($uid, 'hrphoto_feed_view_cats', true);
    $cex   = (array) get_user_meta($uid, 'hrphoto_feed_exclude_cats', true);

    $args = array(
        'u_scope' => $scope,
        'date'    => $date,
    );
    if ($likes === 'most' || $likes === 'least') {
        $args['likes'] = $likes;
    }
    if ($ptype !== '') {
        $args['ptype'] = $ptype;
    }
    if (!empty($cats)) {
        $args['cats'] = implode(',', array_map('intval', $cats));
    }
    if (!empty($cex))  {
        $args['cats_exclude'] = implode(',', array_map('intval', $cex));
    }

    $url = add_query_arg($args, $base);
    // Point to Home with params so personalization runs on Home
    $home = home_url('/');
    $home_url = add_query_arg($args, $home);
    return '<a class="my-feed-link" href="' . esc_url($home_url) . '">My Feed</a>';
}
add_shortcode('my_feed', 'hrphoto_my_feed_shortcode');

// VIEW LATEST SHORTCODE
function hrphoto_view_latest_shortcode($atts = array()) {
    $url = add_query_arg('latest', '1', home_url('/'));
    return '<a href="' . esc_url($url) . '">View Latest</a>';
}
add_shortcode('view_latest', 'hrphoto_view_latest_shortcode');

// AUTHOR WEBSITE URL SHORTCODE
// [author_url] → outputs clickable website link (user_url) with icon
add_shortcode('author_url', function($atts = array()){
	// Resolve author ID: author archive → post author → none
	$author_id = 0;
	if (is_author()) {
		$author_id = (int) get_queried_object_id();
	} else {
		global $post;
		if ($post instanceof WP_Post && !empty($post->post_author)) {
			$author_id = (int) $post->post_author;
		}
	}
	if ($author_id <= 0) { return ''; }

	$url = (string) get_the_author_meta('user_url', $author_id);
	if ($url === '') { return ''; }

	$escaped_url = esc_url($url);
	$parts = wp_parse_url($url);
	$host  = isset($parts['host']) ? $parts['host'] : '';
	if ($host === '') { return ''; }

	// Inline SVG (inherits link color via currentColor)
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" aria-hidden="true">'
		. '<g>'
		. '<path d="M27.2,41.7c-0.7-0.1-1.4-0.2-2.1-0.3s-1.4-0.3-2.1-0.6c-0.4-0.1-0.9,0-1.2,0.3l-0.5,0.5 c-2.9,2.9-7.6,3.2-10.6,0.6c-3.4-2.9-3.5-8.1-0.4-11.2l7.6-7.6c1-1,2.2-1.6,3.4-2c1.6-0.4,3.3-0.3,4.8,0.3c0.9,0.4,1.8,0.9,2.6,1.7 c0.4,0.4,0.7,0.8,1,1.3c0.4,0.7,1.3,0.8,1.8,0.2c0.9-0.9,2.1-2.1,2.8-2.8c0.4-0.4,0.4-1,0.1-1.5C34,20,33.5,19.5,33,19 c-0.7-0.7-1.5-1.4-2.4-1.9c-1.4-0.9-3-1.5-4.7-1.8c-3.1-0.6-6.5-0.1-9.3,1.4c-1.1,0.6-2.2,1.4-3.1,2.3l-7.3,7.3 c-5.3,5.3-5.7,13.9-0.6,19.3c5.3,5.8,14.3,5.9,19.8,0.4l2.5-2.5C28.6,43,28.1,41.8,27.2,41.7z"/>'
		. '<path d="M45.6,5.8c-5.5-5.1-14.1-4.7-19.3,0.6L24,8.6c-0.7,0.7-0.2,1.9,0.7,2c1.4,0.1,2.8,0.4,4.2,0.8 c0.4,0.1,0.9,0,1.2-0.3l0.5-0.5c2.9-2.9,7.6-3.2,10.6-0.6c3.4,2.9,3.5,8.1,0.4,11.2L34,28.8c-1,1-2.2,1.6-3.4,2 c-1.6,0.4-3.3,0.3-4.8-0.3c-0.9-0.4-1.8-0.9-2.6-1.7c-0.4-0.4-0.7-0.8-1-1.3c-0.4-0.7-1.3-0.8-1.8-0.2l-2.8,2.8 c-0.4,0.4-0.4,1-0.1,1.5c0.4,0.6,0.9,1.1,1.4,1.6c0.7,0.7,1.6,1.4,2.4,1.9c1.4,0.9,3,1.5,4.6,1.8c3.1,0.6,6.5,0.1,9.3-1.4 c1.1-0.6,2.2-1.4,3.1-2.3l7.6-7.6C51.5,20.1,51.3,11.1,45.6,5.8z"/>'
		 . '</g>'
		 . '</svg>';

	$html  = '<a class="author-url-link" href="' . $escaped_url . '" target="_blank" rel="noopener nofollow">';
	$html .= '<span class="author-url-icon" aria-hidden="true">' . str_replace('<svg', '<svg fill="currentColor"', $svg) . '</span>';
	$html .= '<span class="author-url-text">' . esc_html($host) . '</span>';
	$html .= '</a>';

	// Enqueue minimal CSS on demand if registered
	if (function_exists('wp_enqueue_style')) {
		if (wp_style_is('author-profile-url-css', 'registered')) {
			wp_enqueue_style('author-profile-url-css');
		}
	}

	return $html;
});

// VIEWER AVATAR SHORTCODE (plain <img> of current viewer)
function hrphoto_viewer_avatar_shortcode($atts){
    $atts = shortcode_atts([
        'size'  => 32,
        'class' => '',
        'alt'   => 'Your avatar',
    ], $atts, 'viewer_avatar');

    $size  = (int) $atts['size'];
    $class = sanitize_html_class($atts['class']);
    $alt   = sanitize_text_field($atts['alt']);

    $uid = is_user_logged_in() ? get_current_user_id() : 0;
    $args = array();
    if ($class !== '') { $args['class'] = $class; }

    return get_avatar($uid, $size, '', $alt, $args);
}
add_shortcode('viewer_avatar', 'hrphoto_viewer_avatar_shortcode');
