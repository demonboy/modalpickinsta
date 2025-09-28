<?php
function save_acf_frontend_profile() {
    if (!isset($_POST['acf_frontend_profile_nonce_field']) ||
        !wp_verify_nonce($_POST['acf_frontend_profile_nonce_field'], 'acf_frontend_profile_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token.']);
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in.']);
    }

    $user_id = get_current_user_id();

    // Save native WP profile fields if present
    if (isset($_POST['first_name'])) {
        update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['first_name']));
    }
    if (isset($_POST['last_name'])) {
        update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['last_name']));
    }
    if (isset($_POST['nickname'])) {
        update_user_meta($user_id, 'nickname', sanitize_text_field($_POST['nickname']));
    }
    if (isset($_POST['display_choice'])) {
        $choice = sanitize_text_field($_POST['display_choice']);
        $first = get_user_meta($user_id, 'first_name', true);
        $last  = get_user_meta($user_id, 'last_name', true);
        $nick  = get_user_meta($user_id, 'nickname', true);
        $user  = get_userdata($user_id);
        $login = $user ? $user->user_login : '';
        $display = $nick;
        if ($choice === 'username') { $display = $login; }
        elseif ($choice === 'first_name') { $display = (string) $first; }
        elseif ($choice === 'last_name') { $display = (string) $last; }
        elseif ($choice === 'first_last') { $display = trim(($first ?: '') . ' ' . ($last ?: '')); }
        // Update display_name using core API
        wp_update_user(array('ID' => $user_id, 'display_name' => $display));
    }

    if (isset($_POST['acf']) && is_array($_POST['acf'])) {
        // Let ACF handle nested structures (canonical approach)
        if (function_exists('acf_update_values')) {
            acf_update_values($_POST['acf'], 'user_' . $user_id);
        } else {
            foreach ($_POST['acf'] as $field_key => $value) {
                update_field($field_key, $value, 'user_' . $user_id);
            }
        }
    }

    $user_after = get_userdata($user_id);
    $response = array(
        'message' => 'Profile updated successfully!',
        'display_name' => $user_after ? $user_after->display_name : '',
        'first_name' => get_user_meta($user_id, 'first_name', true),
        'last_name' => get_user_meta($user_id, 'last_name', true),
        'nickname' => get_user_meta($user_id, 'nickname', true),
    );
    wp_send_json_success($response);
}
add_action('wp_ajax_save_acf_frontend_profile', 'save_acf_frontend_profile');
