<?php
/**
 * Follow modal wrapper. Expects $_POST['user_id'] and optional view in AJAX caller
 */

$current_user_id = get_current_user_id();
$profile_user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$view = isset($_POST['view']) ? sanitize_key($_POST['view']) : 'followers';
if (!in_array($view, array('followers','following','blocked'), true)) { $view = 'followers'; }

// Simple stats header (reuse profile-header-stats classes)
$posts_total = (int) count_user_posts($profile_user_id, '1hrphoto', true) + (int) count_user_posts($profile_user_id, 'story', true);
$followers   = function_exists('hrphoto_get_followers_count') ? (int) hrphoto_get_followers_count($profile_user_id) : 0;
$following   = function_exists('hrphoto_get_following_count') ? (int) hrphoto_get_following_count($profile_user_id) : 0;
$author_url  = get_author_posts_url($profile_user_id);
?>
<div class="follow-modal-content">
  <?php $active = ''; include get_stylesheet_directory() . '/templates/profile/tabs.php'; ?>
  

  <div id="follows-list">
    <section class="follow-section" aria-labelledby="followers-toggle">
      <button id="followers-toggle" class="follow-toggle" type="button" aria-controls="follow-list-followers" aria-expanded="false">+ Followers</button>
      <div id="follow-list-followers" class="follow-list-body" aria-live="polite" hidden>
        <div class="loading">Loading…</div>
      </div>
      <div class="follow-actions">
        <button id="follow-load-more-followers" class="button" style="display:none;">Load more</button>
      </div>
    </section>

    <section class="follow-section" aria-labelledby="following-toggle">
      <button id="following-toggle" class="follow-toggle" type="button" aria-controls="follow-list-following" aria-expanded="false">+ Following</button>
      <div id="follow-list-following" class="follow-list-body" aria-live="polite" hidden>
        <div class="loading">Loading…</div>
      </div>
      <div class="follow-actions">
        <button id="follow-load-more-following" class="button" style="display:none;">Load more</button>
      </div>
    </section>
  </div>
</div>


