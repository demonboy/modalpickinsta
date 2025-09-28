<?php
/**
 * Shared modal tabs for Profile-related modals
 * Expected optional: $active = 'onehour'|'stories'|'gear'|'profile'|'likes'|'social'|''
 */
if (!isset($active)) { $active = ''; }
function tab_btn($slug, $label, $active) {
    $classes = 'profile-tab' . ($active === $slug ? ' is-active' : '');
    echo '<button class="' . esc_attr($classes) . '" data-section="' . esc_attr($slug) . '">' . esc_html($label) . '</button>';
}
?>
<div class="profile-modal-tabs">
  <button class="profile-tab" type="button" onclick="window.location.href='<?php echo esc_url( get_author_posts_url( get_current_user_id() ) ); ?>'">View Profile</button>
  <?php tab_btn('profile', 'Edit Profile', $active); ?>
  <?php tab_btn('gear', 'Gear', $active); ?>
  <?php
    $classes = 'profile-tab' . ($active === 'social' ? ' is-active' : '') . ' profile-social-link';
    echo '<button class="' . esc_attr($classes) . '" data-section="social">Social</button>';
  ?>
  <button class="profile-tab" type="button" data-section="feed-settings">Feed</button>
  <button class="profile-tab" type="button" data-section="follows">Follows</button>
</div>

