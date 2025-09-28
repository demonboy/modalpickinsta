<?php
/**
 * Follow / Followers / Blocks - data and AJAX
 */

if (!defined('ABSPATH')) { exit; }

/**
 * Create tables on theme switch for scalability
 */
function hrphoto_install_follow_tables() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();
    $follows = $wpdb->prefix . 'user_follows';
    $blocks  = $wpdb->prefix . 'user_blocks';

    $sql1 = "CREATE TABLE {$follows} (
      follower_id BIGINT(20) UNSIGNED NOT NULL,
      following_id BIGINT(20) UNSIGNED NOT NULL,
      created_at DATETIME NOT NULL,
      PRIMARY KEY  (follower_id, following_id),
      KEY following_id (following_id),
      KEY follower_id (follower_id)
    ) {$charset_collate};";

    $sql2 = "CREATE TABLE {$blocks} (
      blocker_id BIGINT(20) UNSIGNED NOT NULL,
      blocked_id BIGINT(20) UNSIGNED NOT NULL,
      created_at DATETIME NOT NULL,
      PRIMARY KEY  (blocker_id, blocked_id),
      KEY blocked_id (blocked_id),
      KEY blocker_id (blocker_id)
    ) {$charset_collate};";

    // Create only if missing
    $have_follows = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $follows)) === $follows);
    $have_blocks  = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $blocks)) === $blocks);
    if (!$have_follows) { dbDelta($sql1); }
    if (!$have_blocks)  { dbDelta($sql2); }
}

add_action('after_switch_theme', 'hrphoto_install_follow_tables');
add_action('admin_init', function () {
    global $wpdb;
    $follows = $wpdb->prefix . 'user_follows';
    $blocks  = $wpdb->prefix . 'user_blocks';
    $need = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $follows)) !== $follows)
         || ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $blocks)) !== $blocks);
    if ($need) { hrphoto_install_follow_tables(); }
});

/** Helpers **/
function hrphoto_follows_table() { global $wpdb; return $wpdb->prefix . 'user_follows'; }
function hrphoto_blocks_table() { global $wpdb; return $wpdb->prefix . 'user_blocks'; }

function hrphoto_is_blocked($blocker_id, $blocked_id) {
    global $wpdb; $t = hrphoto_blocks_table();
    return (bool) $wpdb->get_var($wpdb->prepare("SELECT 1 FROM {$t} WHERE blocker_id=%d AND blocked_id=%d LIMIT 1", $blocker_id, $blocked_id));
}

function hrphoto_is_following($follower_id, $following_id) {
    global $wpdb; $t = hrphoto_follows_table();
    return (bool) $wpdb->get_var($wpdb->prepare("SELECT 1 FROM {$t} WHERE follower_id=%d AND following_id=%d LIMIT 1", $follower_id, $following_id));
}

function hrphoto_get_followers_count($user_id) {
    $key = 'hrphoto_followers_count_' . (int) $user_id;
    $cached = get_transient($key);
    if ($cached !== false) { return (int) $cached; }
    global $wpdb; $t = hrphoto_follows_table();
    $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE following_id=%d", $user_id));
    set_transient($key, $count, 10 * MINUTE_IN_SECONDS);
    return $count;
}

function hrphoto_get_following_count($user_id) {
    $key = 'hrphoto_following_count_' . (int) $user_id;
    $cached = get_transient($key);
    if ($cached !== false) { return (int) $cached; }
    global $wpdb; $t = hrphoto_follows_table();
    $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE follower_id=%d", $user_id));
    set_transient($key, $count, 10 * MINUTE_IN_SECONDS);
    return $count;
}

function hrphoto_invalidate_follow_counts($user_id) {
    delete_transient('hrphoto_followers_count_' . (int) $user_id);
    delete_transient('hrphoto_following_count_' . (int) $user_id);
}

/** AJAX: Toggle follow (follow/unfollow) */
add_action('wp_ajax_toggle_follow', 'hrphoto_ajax_toggle_follow');
function hrphoto_ajax_toggle_follow() {
    if (!is_user_logged_in()) { wp_send_json_error(array('message' => 'Login required'), 401); }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error(array('message' => 'Bad nonce'), 403);
    }
    $follower_id  = get_current_user_id();
    $following_id = isset($_POST['following_id']) ? (int) $_POST['following_id'] : 0;
    if ($following_id <= 0 || $follower_id === $following_id) {
        wp_send_json_error(array('message' => 'Invalid target'));
    }
    // Prevent follow if either has blocked the other
    if (hrphoto_is_blocked($following_id, $follower_id) || hrphoto_is_blocked($follower_id, $following_id)) {
        wp_send_json_error(array('message' => 'Action not allowed')); // do not leak which direction
    }

    global $wpdb; $t = hrphoto_follows_table();
    $now = current_time('mysql');
    $is_following = hrphoto_is_following($follower_id, $following_id);
    if ($is_following) {
        $wpdb->delete($t, array('follower_id' => $follower_id, 'following_id' => $following_id), array('%d','%d'));
    } else {
        $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$t} (follower_id, following_id, created_at) VALUES (%d,%d,%s)", $follower_id, $following_id, $now));
    }

    hrphoto_invalidate_follow_counts($follower_id);
    hrphoto_invalidate_follow_counts($following_id);

    wp_send_json_success(array(
        'following' => !$is_following,
        'followers_count' => hrphoto_get_followers_count($following_id),
        'following_count' => hrphoto_get_following_count($follower_id),
    ));
}

/** AJAX: Get follow counts for a user (public) */
add_action('wp_ajax_nopriv_get_follow_counts', 'hrphoto_ajax_get_follow_counts');
add_action('wp_ajax_get_follow_counts', 'hrphoto_ajax_get_follow_counts');
function hrphoto_ajax_get_follow_counts() {
    $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    if ($user_id <= 0) { wp_send_json_error(array('message' => 'Invalid user')); }
    wp_send_json_success(array(
        'followers' => hrphoto_get_followers_count($user_id),
        'following' => hrphoto_get_following_count($user_id),
    ));
}

/** Lists (following/followers/blocked) */
add_action('wp_ajax_nopriv_get_follow_list', 'hrphoto_ajax_get_follow_list');
add_action('wp_ajax_get_follow_list', 'hrphoto_ajax_get_follow_list');
function hrphoto_ajax_get_follow_list() {
    $type   = isset($_POST['type']) ? sanitize_key($_POST['type']) : '';
    $user_id= isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $page   = max(1, isset($_POST['page']) ? (int) $_POST['page'] : 1);
    $per    = 50;
    if (!in_array($type, array('following','followers','blocked'), true) || $user_id <= 0) {
        wp_send_json_error(array('message' => 'Invalid request'));
    }

    global $wpdb;
    $users_table = $wpdb->users;
    $where = '';
    $join = '';
    $params = array();

    if ($type === 'following') {
        $t = hrphoto_follows_table();
        $join = "JOIN {$t} f ON f.following_id = {$users_table}.ID";
        $where = 'WHERE f.follower_id = %d';
        $params[] = $user_id;
    } elseif ($type === 'followers') {
        $t = hrphoto_follows_table();
        $join = "JOIN {$t} f ON f.follower_id = {$users_table}.ID";
        $where = 'WHERE f.following_id = %d';
        $params[] = $user_id;
    } else { // blocked
        $t = hrphoto_blocks_table();
        $join = "JOIN {$t} b ON b.blocked_id = {$users_table}.ID";
        $where = 'WHERE b.blocker_id = %d';
        $params[] = $user_id;
    }

    if ($search !== '') {
        $where .= ' AND ' . $users_table . '.display_name LIKE %s';
        $params[] = '%' . $wpdb->esc_like($search) . '%';
    }

    $offset = ($page - 1) * $per;
    $sql = "SELECT SQL_CALC_FOUND_ROWS {$users_table}.ID, {$users_table}.display_name FROM {$users_table} {$join} {$where} ORDER BY {$users_table}.display_name ASC LIMIT %d OFFSET %d";
    $params[] = $per; $params[] = $offset;
    $prepared = $wpdb->prepare($sql, $params);
    $rows = $wpdb->get_results($prepared);
    $total = (int) $wpdb->get_var('SELECT FOUND_ROWS()');

    ob_start();
    $context = array('type' => $type, 'rows' => $rows, 'page' => $page, 'per' => $per, 'total' => $total);
    $tpl = get_stylesheet_directory() . '/templates/profile/partials/follow-list.php';
    if (file_exists($tpl)) {
        include $tpl;
    } else {
        echo '<div class="error">Template missing.</div>';
    }
    $html = ob_get_clean();

    wp_send_json_success(array(
        'html' => $html,
        'page' => $page,
        'per' => $per,
        'total' => $total,
        'has_more' => ($offset + $per) < $total,
    ));
}

/** Block / Unblock */
add_action('wp_ajax_block_user', 'hrphoto_ajax_block_user');
function hrphoto_ajax_block_user() {
    if (!is_user_logged_in()) { wp_send_json_error(array('message' => 'Login required'), 401); }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) { wp_send_json_error(array('message' => 'Bad nonce'), 403); }
    $blocker = get_current_user_id();
    $blocked = isset($_POST['blocked_id']) ? (int) $_POST['blocked_id'] : 0;
    if ($blocked <= 0 || $blocked === $blocker) { wp_send_json_error(array('message' => 'Invalid target')); }

    global $wpdb; $bt = hrphoto_blocks_table(); $ft = hrphoto_follows_table();
    $now = current_time('mysql');
    // Insert block
    $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$bt} (blocker_id, blocked_id, created_at) VALUES (%d,%d,%s)", $blocker, $blocked, $now));
    // Force them to unfollow me if currently following
    $wpdb->delete($ft, array('follower_id' => $blocked, 'following_id' => $blocker), array('%d','%d'));
    hrphoto_invalidate_follow_counts($blocker);
    hrphoto_invalidate_follow_counts($blocked);
    wp_send_json_success(array(
        'followers_count' => hrphoto_get_followers_count($blocker),
        'following_count' => hrphoto_get_following_count($blocked),
    ));
}

add_action('wp_ajax_unblock_user', 'hrphoto_ajax_unblock_user');
function hrphoto_ajax_unblock_user() {
    if (!is_user_logged_in()) { wp_send_json_error(array('message' => 'Login required'), 401); }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) { wp_send_json_error(array('message' => 'Bad nonce'), 403); }
    $blocker = get_current_user_id();
    $blocked = isset($_POST['blocked_id']) ? (int) $_POST['blocked_id'] : 0;
    if ($blocked <= 0 || $blocked === $blocker) { wp_send_json_error(array('message' => 'Invalid target')); }

    global $wpdb; $bt = hrphoto_blocks_table();
    $wpdb->delete($bt, array('blocker_id' => $blocker, 'blocked_id' => $blocked), array('%d','%d'));
    wp_send_json_success(array('ok' => true));
}

/** Utility: favourite genre (preserve case) */
function hrphoto_get_user_fave_genre_name($user_id) {
    if (!function_exists('get_field')) { return ''; }
    $val = get_field('fave_genre', 'user_' . (int) $user_id);
    if (!$val) { $val = get_field('field_68c567a24b1a5', 'user_' . (int) $user_id); }
    if (!$val) { return ''; }
    if (is_object($val) && isset($val->name)) { return (string) $val->name; }
    if (is_numeric($val)) { $t = get_term((int) $val); return ($t && !is_wp_error($t)) ? (string) $t->name : ''; }
    if (is_array($val)) {
        if (isset($val['name'])) { return (string) $val['name']; }
        if (isset($val['term_id'])) { $t = get_term((int) $val['term_id']); return ($t && !is_wp_error($t)) ? (string) $t->name : ''; }
    }
    return is_string($val) ? $val : '';
}


