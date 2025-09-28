<?php
if (!is_user_logged_in()) {
    echo '<p>You must be logged in to edit your profile.</p>';
    return;
}

// Current user ID
$user_id = get_current_user_id();

// The field group key passed via shortcode or include
$group_key = $group_key ?? '';
$fields = $group_key ? acf_get_fields($group_key) : [];

if (empty($fields)) {
    echo '<p>No fields found for this group.</p>';
    return;
}
?>

<form id="acf-frontend-profile-form" class="acf-frontend-profile-form" data-group="<?php echo esc_attr($group_key); ?>">
    <?php
    // Provide ACF with form data so it can hydrate and save reliably
    if (function_exists('acf_form_data')) {
        acf_form_data([
            'post_id' => 'user_' . $user_id,
        ]);
    }
    // Native WP profile fields at top for the Profile tab only
    if ($group_key === 'group_68c5645993692') :
        $first_name_val = get_user_meta($user_id, 'first_name', true);
        $last_name_val = get_user_meta($user_id, 'last_name', true);
        $nickname_val = get_user_meta($user_id, 'nickname', true);
        $user_obj = get_userdata($user_id);
        $display_name_current = $user_obj ? $user_obj->display_name : '';
        $username_current = $user_obj ? $user_obj->user_login : '';
        // Infer current display choice
        $choice = 'nickname';
        if ($display_name_current === $username_current) {
            $choice = 'username';
        } elseif ($display_name_current === (string) $first_name_val) {
            $choice = 'first_name';
        } elseif ($display_name_current === (string) $last_name_val) {
            $choice = 'last_name';
        } elseif (trim($display_name_current) === trim($first_name_val . ' ' . $last_name_val)) {
            $choice = 'first_last';
        } elseif ($display_name_current === (string) $nickname_val) {
            $choice = 'nickname';
        }
    ?>
    <script>
    // Ensure the top avatar in the edit profile modal becomes editable with overlay
    (function(){
      try {
        var headerWrap = document.querySelector('#universal-modal .profile-modal-avatar-wrap');
        if (headerWrap) {
          var img = headerWrap.querySelector('img');
          if (img) { img.classList.add('avatar-clickable'); }
          if (!headerWrap.querySelector('.avatar-overlay')) {
            var span = document.createElement('span');
            span.className = 'avatar-overlay no-radius';
            span.textContent = 'Change Avatar';
            headerWrap.appendChild(span);
          }
        }
      } catch(e) {}
    })();
    </script>
    <div class="acf-fields" style="display:flex; gap:20px; margin-bottom:20px;">
        <div style="width:50%;">
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($first_name_val); ?>" />
        </div>
        <div style="width:50%;">
            <label for="last_name">Surname</label>
            <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($last_name_val); ?>" />
        </div>
    </div>
    <div class="acf-fields" style="display:flex; gap:20px; margin-bottom:20px;">
        <div style="width:50%;">
            <label for="nickname">Nickname</label>
            <input type="text" id="nickname" name="nickname" value="<?php echo esc_attr($nickname_val); ?>" />
        </div>
        <div style="width:50%;">
            <label for="display_choice">Display name publicly as</label>
            <select id="display_choice" name="display_choice">
                <option value="nickname" <?php selected($choice, 'nickname'); ?>><?php echo esc_html( $nickname_val !== '' ? $nickname_val : 'Nickname' ); ?></option>
                <option value="username" <?php selected($choice, 'username'); ?>><?php echo esc_html( $username_current !== '' ? $username_current : 'Username' ); ?></option>
                <option value="first_name" <?php selected($choice, 'first_name'); ?>><?php echo esc_html( $first_name_val !== '' ? $first_name_val : 'First Name' ); ?></option>
                <option value="last_name" <?php selected($choice, 'last_name'); ?>><?php echo esc_html( $last_name_val !== '' ? $last_name_val : 'Surname' ); ?></option>
                <option value="first_last" <?php selected($choice, 'first_last'); ?>><?php echo esc_html( trim($first_name_val . ' ' . $last_name_val) !== '' ? trim($first_name_val . ' ' . $last_name_val) : 'First name + surname' ); ?></option>
            </select>
        </div>
    </div>
    <?php endif; ?>
    <?php
    // Render entire group with values from user_<id>; ensure inputs post under acf[...] prefix
    if (function_exists('acf_render_fields')) {
        $render_fields = array_map(function($f){ $f['prefix'] = 'acf'; return $f; }, $fields);
        acf_render_fields($render_fields, 'user_' . $user_id, 'div', 'label');
    } else {
        // Fallback to per-field rendering if acf_render_fields is unavailable
        foreach ($fields as $field) {
            $field['prefix'] = 'acf';
            acf_render_field_wrap($field);
        }
    }
    ?>


    <div id="acf-frontend-profile-message" class="acf-frontend-profile-message" aria-live="polite" role="status" style="display:none;"></div>

    <!-- Hidden fields for AJAX -->
    <input type="hidden" name="action" value="save_acf_frontend_profile">
    <input type="hidden" name="acf_frontend_profile_nonce_field" value="<?php echo wp_create_nonce('acf_frontend_profile_nonce'); ?>">

    <button type="submit">Save Changes</button>
</form>

