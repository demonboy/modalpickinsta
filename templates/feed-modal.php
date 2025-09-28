<?php
/**
 * Feed settings modal template (minimal markup; JS enhances)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="feed-modal">
  <?php $active = 'feed-settings'; include get_stylesheet_directory() . '/templates/profile/tabs.php'; ?>
  <div class="feed-modal-header">
    <h2>Feed settings</h2>
  </div>
  <div class="feed-modal-body">
    <form class="feed-form" action="#" method="post" onsubmit="return false;">
      <!-- Step 1: Sort by user (scope) -->
      <fieldset class="feed-step" data-step="1">
        <legend>1. Sort by user</legend>
        <label><input type="radio" name="u_yesno" value="no" checked> No</label>
        <label><input type="radio" name="u_yesno" value="yes"> Yes</label>
        <div class="step-body" data-body="user" hidden>
          <label><input type="radio" name="u_scope" value="everyone" checked> everyone</label>
          <label><input type="radio" name="u_scope" value="following"> everyone I follow</label>
          <label><input type="radio" name="u_scope" value="not_following"> everyone I don't follow</label>
        </div>
      </fieldset>

      <!-- Step 2: Sort by likes -->
      <fieldset class="feed-step" data-step="2">
        <legend>2. Sort by likes</legend>
        <label><input type="radio" name="likes_yesno" value="no" checked> No</label>
        <label><input type="radio" name="likes_yesno" value="yes"> Yes</label>
        <div class="step-body" data-body="likes" hidden>
          <label><input type="radio" name="likes_order" value="most" checked> Most likes</label>
          <label><input type="radio" name="likes_order" value="least"> Least likes</label>
        </div>
      </fieldset>

      <div class="or-sep" aria-hidden="true">-- OR --</div>

      <!-- Step 3: Sort by date -->
      <fieldset class="feed-step" data-step="3">
        <legend>3. Sort by date</legend>
        <label><input type="radio" name="date_order" value="latest" checked> Latest first</label>
        <label><input type="radio" name="date_order" value="oldest"> Oldest first</label>
        <label><input type="radio" name="date_order" value="random"> Random</label>
      </fieldset>

      <!-- Step 4: Sort by preferred category (boost) -->
      <fieldset class="feed-step" data-step="4">
        <legend>4. Sort by preferred category</legend>
        <label><input type="radio" name="cats_yesno" value="no" checked> No</label>
        <label><input type="radio" name="cats_yesno" value="yes"> Yes</label>
        <div class="step-body" data-body="cats" hidden>
          <div class="cats-picker">
            <p class="hint">Drag categories into Preferred or Never show. Tap to move back to Available.</p>
            <div class="cats-columns" role="group" aria-label="Category selection">
              <div class="cats-column" data-list="available" aria-label="Available categories" tabindex="0">
                <div class="cats-column-title">Available</div>
                <div class="cats-bucket" id="cats-available" aria-live="polite"></div>
              </div>
              <div class="cats-column" data-list="preferred" aria-label="Preferred categories" tabindex="0">
                <div class="cats-column-title">Preferred</div>
                <div class="cats-bucket" id="cats-preferred" aria-live="polite"></div>
              </div>
              <div class="cats-column" data-list="excluded" aria-label="Never show categories" tabindex="0">
                <div class="cats-column-title">Never show</div>
                <div class="cats-bucket" id="cats-excluded" aria-live="polite"></div>
              </div>
            </div>
            <input type="hidden" id="feed-cats-selected" name="cats" value="">
            <input type="hidden" id="feed-cats-exclude-selected" name="cats_exclude" value="">
          </div>
        </div>
      </fieldset>

      <!-- Step 5: Never show these categories (exclude) -->
      <fieldset class="feed-step" data-step="5">
        <legend>5. Never show these categories</legend>
        <label><input type="radio" name="exclude_yesno" value="no" checked> No</label>
        <label><input type="radio" name="exclude_yesno" value="yes"> Yes</label>
        <div class="step-body" data-body="exclude" hidden>
          <p class="hint">Use the "Never show" column above.</p>
        </div>
      </fieldset>

      <!-- Step 6: Sort by post type (filter) -->
      <fieldset class="feed-step" data-step="6">
        <legend>5. Sort by post type</legend>
        <label><input type="radio" name="ptype_yesno" value="no" checked> No</label>
        <label><input type="radio" name="ptype_yesno" value="yes"> Yes</label>
        <div class="step-body" data-body="ptype" hidden>
          <label><input type="radio" name="ptype" value="1hrphoto"> 1hrphoto</label>
          <label><input type="radio" name="ptype" value="story"> Stories</label>
        </div>
      </fieldset>
    </form>
  </div>
  <div class="feed-modal-footer">
    <div class="summary" aria-live="polite"></div>
    <button type="button" class="btn-apply">Save</button>
  </div>
  <div class="sr-only" aria-live="polite"></div>
  <script>/* JS populates initial state and handles interactions */</script>
</div>


