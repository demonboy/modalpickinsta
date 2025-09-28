<?php
/**
 * Social Profiles editor (modal body)
 * Uses ACF render/save via AJAX
 * Expects: $group_key (string) and current user context
 */
if (!is_user_logged_in()) { echo '<div class="error">Login required.</div>'; return; }
$user_id = get_current_user_id();

echo '<div class="social-modal-content">';
// Modal menu tabs (shared)
$active = 'social';
include get_stylesheet_directory() . '/templates/profile/tabs.php';

// Begin ACF context and render fields
if (function_exists('acf_form_data') && function_exists('acf_get_fields') && function_exists('acf_render_fields')) {
    acf_form_data(array('post_id' => 'user_' . $user_id));
    $fields = acf_get_fields($group_key);
    if (is_array($fields)) {
        echo '<form id="acf-social-form" method="post" action="#">';
        // WordPress standard Website field (user_url) above social fields
        $current_url = get_userdata($user_id)->user_url;
        echo '<div class="form-field">';
        echo '  <label for="profile-website">' . esc_html__('Website', '1hrphoto') . '</label>';
        echo '  <input type="url" id="profile-website" name="user_url" value="' . esc_attr($current_url) . '" placeholder="https://">';
        echo '</div>';

        // Live, draggable preview ABOVE fields + hidden selected CSV
        $saved_selected = (string) get_user_meta($user_id, 'hrphoto_social_selected', true);
        echo '<h3 class="social-reorder-heading" hidden>' . esc_html__('Drag to reorder', '1hrphoto') . '</h3>';
        echo '<div id="social-icons-active" class="social-icons-active" aria-live="polite"></div>';
        echo '<div id="social-icons-inactive" class="social-icons-inactive" aria-live="polite"></div>';
        echo '<div id="social-fields-wrap" data-selected="' . esc_attr($saved_selected) . '">';
        acf_render_fields($fields, 'user_' . $user_id, 'div', 'label', array('prefix' => 'acf'));
        echo '</div>';
        echo '<input type="hidden" name="social_selected" id="social-selected" value="' . esc_attr( $saved_selected ) . '">';
        echo '<div id="acf-social-message" class="acf-social-message" style="display:none"></div>';
        echo '<button type="submit" class="button">Save</button>';
        echo '</form>';
    } else {
        echo '<div class="error">Fields not found.</div>';
    }
} else {
    echo '<div class="error">ACF not available.</div>';
}

echo '</div>';


