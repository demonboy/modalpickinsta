<?php
/**
 * Social Modal: render & save ACF Social Profiles for current user
 */

// Load Social modal content (current user only)
add_action('wp_ajax_get_social_modal', function(){
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'), 403);
    }
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Login required'), 401);
    }
    $user_id = get_current_user_id();

    ob_start();
    $group_key = 'group_social_profiles_1';
    include get_stylesheet_directory() . '/templates/profile/social-modal.php';
    $html = ob_get_clean();
    wp_send_json_success(array('html' => $html));
});

// Save Social Profiles (AJAX)
add_action('wp_ajax_save_social_profiles', function(){
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'), 403);
    }
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Login required'), 401);
    }
    $user_id = get_current_user_id();
    if (!current_user_can('edit_user', $user_id)) {
        wp_send_json_error(array('message' => 'Permission denied'), 403);
    }

    // Normalize usernames: trim, strip leading @ and protocol/domain remnants
    if (isset($_POST['acf']) && is_array($_POST['acf'])) {
        array_walk_recursive($_POST['acf'], function (&$value, $key) {
            if (!is_string($value)) return;
            $v = trim($value);
            $v = preg_replace('/^@+/', '', $v);
            $v = preg_replace('#^https?://[^/]+/#i', '', $v);
            $value = $v;
        });
    }

    // Update WordPress standard Website (user_url) if present
    if (isset($_POST['user_url'])) {
        $url = esc_url_raw(trim((string) $_POST['user_url']));
        wp_update_user(array('ID' => $user_id, 'user_url' => $url));
    }

    // Persist selection/order as a single CSV (limit 6 on server)
    $selected_csv = isset($_POST['social_selected']) ? sanitize_text_field((string) $_POST['social_selected']) : '';
    $selected = array_filter(array_map('trim', explode(',', strtolower($selected_csv))));
    $selected = array_values(array_unique($selected));
    if (count($selected) > 9) { $selected = array_slice($selected, 0, 9); }
    update_user_meta($user_id, 'hrphoto_social_selected', implode(',', $selected));

    // Save via ACF canonical API
    if (function_exists('acf_update_values')) {
        acf_update_values($_POST['acf'], 'user_' . $user_id);
        wp_send_json_success(array('message' => 'Social profiles saved'));
    }
    wp_send_json_error(array('message' => 'ACF not available'));
});

// ACF: Adjust Social Profiles field rendering (placeholders + 3-column layout)
add_filter('acf/load_field', function($field){
    if (empty($field) || !is_array($field) || empty($field['name'])) return $field;
    $names = array(
        'instagram','threads','x','facebook','tiktok','youtube','vimeo','flickr',
        'fivehundredpx','pinterest','reddit','linkedin','behance','dribbble',
        'deviantart','tumblr','bluesky','mastodon_instance','snapchat','telegram',
        'medium','vsco','substack'
    );
    if (!in_array($field['name'], $names, true)) return $field;

    // 3-column layout via wrapper width
    if (!isset($field['wrapper']) || !is_array($field['wrapper'])) $field['wrapper'] = array();
    $field['wrapper']['width'] = '33';

    // Replace headings with placeholders showing the channel name
    $labels = array(
        'instagram' => 'Instagram',
        'threads' => 'Threads',
        'x' => 'X',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'vimeo' => 'Vimeo',
        'flickr' => 'Flickr',
        'fivehundredpx' => '500px',
        'pinterest' => 'Pinterest',
        'reddit' => 'Reddit',
        'linkedin' => 'LinkedIn',
        'behance' => 'Behance',
        'dribbble' => 'Dribbble',
        'deviantart' => 'DeviantArt',
        'tumblr' => 'Tumblr',
        'bluesky' => 'Bluesky',
        'mastodon_instance' => 'Mastodon instance',
        'snapchat' => 'Snapchat',
        'telegram' => 'Telegram',
        'medium' => 'Medium',
        'vsco' => 'VSCO',
        'substack' => 'Substack'
    );
    $field['instructions'] = '';
    // Hide label text and use placeholder instead
    $field['label'] = '';
    if (isset($labels[$field['name']])) {
        $field['placeholder'] = $labels[$field['name']];
    }
    return $field;
}, 20);


