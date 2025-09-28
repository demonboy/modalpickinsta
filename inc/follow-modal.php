<?php
/**
 * Follow Modal AJAX: wrapper rendering for following/followers/blocked
 */

if (!defined('ABSPATH')) { exit; }

add_action('wp_ajax_nopriv_get_follow_modal', 'hrphoto_get_follow_modal');
add_action('wp_ajax_get_follow_modal', 'hrphoto_get_follow_modal');
function hrphoto_get_follow_modal() {
    $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    $view    = isset($_POST['view']) ? sanitize_key($_POST['view']) : 'followers';
    if ($user_id <= 0) { wp_send_json_error(array('message' => 'Invalid user')); }

    ob_start();
    $tpl = get_stylesheet_directory() . '/templates/profile/follow-modal.php';
    if (file_exists($tpl)) {
        include $tpl;
    } else {
        echo '<div class="error">Template missing.</div>';
    }
    $html = ob_get_clean();

    wp_send_json_success(array('html' => $html));
}





