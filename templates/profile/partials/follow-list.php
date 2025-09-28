<?php
/**
 * Partials: follow list items
 * $context: ['type','rows','page','per','total']
 */
if (!isset($context) || !is_array($context)) { $context = array(); }
$type = isset($context['type']) ? $context['type'] : 'followers';
$rows = isset($context['rows']) ? $context['rows'] : array();
$page = isset($context['page']) ? (int) $context['page'] : 1;
$total= isset($context['total']) ? (int) $context['total'] : 0;

if (empty($rows)) {
    echo '<div class="follow-empty">No users found.</div>';
    return;
}

foreach ($rows as $r) {
    $uid = (int) $r->ID;
    $name = esc_html($r->display_name);
    $avatar = get_avatar($uid, 40, '', $name);
    $url    = get_author_posts_url($uid);
    $genre = function_exists('hrphoto_get_user_fave_genre_name') ? hrphoto_get_user_fave_genre_name($uid) : '';
    $followers = function_exists('hrphoto_get_followers_count') ? (int) hrphoto_get_followers_count($uid) : 0;
    $action_label = $type === 'following' ? 'Unfollow' : ($type === 'followers' ? 'Block' : 'Unblock');
    $action_class = $type === 'following' ? 'btn-unfollow' : ($type === 'followers' ? 'btn-block' : 'btn-unblock');
    echo '<div class="follow-row" data-user-id="' . $uid . '">';
    echo '  <div class="follow-user">'
          . '<a href="' . esc_url($url) . '" data-no-profile-modal>' . $avatar . '</a>'
          . '<div class="follow-user-meta"><div class="follow-name"><a href="' . esc_url($url) . '" data-no-profile-modal>' . $name . '</a></div>';
    if ($genre) { echo '<div class="follow-genre">' . esc_html($genre) . '</div>'; }
    echo '  </div></div>';
    echo '  <div class="follow-actions">' . do_shortcode('[follow_author author_id="' . $uid . '"]') . '</div>';
    echo '</div>';
}



