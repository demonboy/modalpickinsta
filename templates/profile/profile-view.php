<?php
/**
 * Public Profile View (modal content)
 * Variables available: $user (WP_User or null)
 */
if (!isset($user) || !($user instanceof WP_User)) {
    echo '<div class="profile-modal-content"><p>User not found.</p></div>';
    return;
}

$display_name = esc_html($user->display_name);
$user_nicename = esc_html($user->user_nicename);
$avatar = get_avatar($user->ID, 96, '', $display_name, array('class' => 'profile-modal-avatar'));
$description = get_user_meta($user->ID, 'description', true);

?>
<div class="profile-modal-content" data-user-id="<?php echo (int) $user->ID; ?>">
  <span class="screen-reader-text" data-user-display><?php echo esc_html( $user->display_name ); ?></span>
  <?php $active = ''; include get_stylesheet_directory() . '/templates/profile/tabs.php'; ?>
  <?php include get_stylesheet_directory() . '/templates/profile/profile-header.php'; ?>
  <?php
    $summary = function_exists('hrphoto_build_profile_summary') ? hrphoto_build_profile_summary((int) $user->ID) : '';
    if ($summary) : ?>
      <div class="profile-modal-bio"><?php echo wpautop(esc_html($summary)); ?></div>
  <?php endif; ?>
  <?php if (is_user_logged_in() && get_current_user_id() === (int) $user->ID) : ?>
    <div id="avatar-editor-slot" class="avatar-editor-slot"></div>
  <?php endif; ?>
  <div class="profile-modal-section" id="profile-modal-section" aria-live="polite">
    <div class="loading">Loading…</div>
  </div>
</div>


