<?php
/**
 * Reusable Profile Header
 * Expects: $user (WP_User)
 * - Preserves existing avatar change trigger via .profile-modal-avatar-wrap and .avatar-overlay
 * - Shows Firstname Surname (fallback to display_name)
 * - Stats: Posts (1hrphoto + story), Followers, Following
 */

if (!isset($user) || !($user instanceof WP_User)) {
    return;
}

$first = trim((string) get_user_meta($user->ID, 'first_name', true));
$last  = trim((string) get_user_meta($user->ID, 'last_name', true));
$full_name = trim($first . ' ' . $last);
if ($full_name === '') {
    $full_name = (string) $user->display_name;
}

// Count only published 1hrphoto + story
$posts_1hr  = (int) count_user_posts((int) $user->ID, '1hrphoto', true);
$posts_story= (int) count_user_posts((int) $user->ID, 'story', true);
$posts_total= $posts_1hr + $posts_story;

$followers = function_exists('hrphoto_get_followers_count') ? (int) hrphoto_get_followers_count((int) $user->ID) : 0;
$following = function_exists('hrphoto_get_following_count') ? (int) hrphoto_get_following_count((int) $user->ID) : 0;

$avatar_html = get_avatar($user->ID, 96, '', esc_attr($full_name), array('class' => 'profile-modal-avatar'));
$author_url  = get_author_posts_url((int) $user->ID, $user->user_nicename);
?>

<div class="profile-modal-header">
  <div class="profile-modal-avatar-wrap">
    <?php echo $avatar_html; ?><span class="avatar-overlay"><?php echo esc_html__('Change Avatar', '1hrphoto'); ?></span>
  </div>
  <div class="profile-header-body">
    <div class="profile-public-name"><?php echo esc_html( $user->display_name ); ?></div>
    <h2 class="profile-modal-name"><?php echo esc_html($full_name); ?></h2>
    <div class="profile-header-stats">
      <a href="<?php echo esc_url($author_url); ?>" class="profile-head-link" data-action="open-posts" data-user-id="<?php echo (int) $user->ID; ?>" aria-label="<?php echo esc_attr__('Posts', '1hrphoto'); ?>">
        <span class="stat-stack">
          <span class="stat-label"><?php echo esc_html__('Posts', '1hrphoto'); ?></span>
          <span class="stat-count" data-posts-count><?php echo number_format_i18n($posts_total); ?></span>
        </span>
      </a>
      <a href="#" class="profile-head-link" data-section="followers" data-user-id="<?php echo (int) $user->ID; ?>" aria-label="<?php echo esc_attr__('Followers', '1hrphoto'); ?>">
        <span class="stat-stack">
          <span class="stat-label"><?php echo esc_html__('Followers', '1hrphoto'); ?></span>
          <span class="stat-count" data-followers-count><?php echo number_format_i18n($followers); ?></span>
        </span>
      </a>
      <a href="#" class="profile-head-link" data-section="following" data-user-id="<?php echo (int) $user->ID; ?>" aria-label="<?php echo esc_attr__('Following', '1hrphoto'); ?>">
        <span class="stat-stack">
          <span class="stat-label"><?php echo esc_html__('Following', '1hrphoto'); ?></span>
          <span class="stat-count" data-following-count><?php echo number_format_i18n($following); ?></span>
        </span>
      </a>
    </div>
  </div>
</div>


