<?php
/**
 * Avatar editing: upload/crop/encode to WebP 256x256 (<=75KB), delete, and global display via get_avatar.
 */

// Add data attribute to all avatar <img> for live updates and ensure square style class
add_filter('get_avatar', function($avatar, $id_or_email, $size, $default, $alt, $args){
    $user = false;
    if (is_numeric($id_or_email)) {
        $user = get_user_by('id', (int) $id_or_email);
    } elseif (is_object($id_or_email) && isset($id_or_email->user_id)) {
        $user = get_user_by('id', (int) $id_or_email->user_id);
    } elseif (is_string($id_or_email)) {
        $user = get_user_by('email', $id_or_email);
    }
    $user_id = $user ? (int) $user->ID : 0;
    if ($user_id > 0) {
        // Add data-avatar-user-id
        if (strpos($avatar, 'data-avatar-user-id=') === false) {
            $avatar = preg_replace('/<img\s+/i', '<img data-avatar-user-id="' . esc_attr($user_id) . '" ', $avatar, 1);
        }
        // Append avatar-square to existing class attribute (preserve existing classes)
        if (preg_match('/class="([^"]*)"/i', $avatar)) {
            $avatar = preg_replace('/class="([^"]*)"/i', 'class="$1 avatar-square"', $avatar, 1);
        } else {
            $avatar = preg_replace('/<img\s+/i', '<img class="avatar-square" ', $avatar, 1);
        }
    }
    return $avatar;
}, 10, 6);

// Replace avatar URL globally if a custom avatar is set
add_filter('get_avatar_data', function($args, $id_or_email){
    $user_id = 0;
    if (is_numeric($id_or_email)) {
        $user_id = (int) $id_or_email;
    } elseif (is_object($id_or_email) && isset($id_or_email->user_id)) {
        $user_id = (int) $id_or_email->user_id;
    } elseif (is_string($id_or_email)) {
        $u = get_user_by('email', $id_or_email); if ($u) $user_id = (int) $u->ID;
    }
    if ($user_id) {
        $att_id = (int) get_user_meta($user_id, 'profile_avatar_id', true);
        if ($att_id) {
            $url = wp_get_attachment_image_url($att_id, array( (int) $args['size'], (int) $args['size'] ));
            if ($url) {
                // Cache-bust with attachment modified time
                $args['url'] = add_query_arg('v', (int) get_post_modified_time('U', true, $att_id), $url);
            }
        }
    }
    return $args;
}, 10, 2);

// AJAX: Open edit avatar modal
add_action('wp_ajax_get_edit_avatar_modal', function(){
    if (!is_user_logged_in() || !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error(['message' => 'Not allowed'], 403);
    }
    $user_id = get_current_user_id();
    $html = '';
    ob_start(); ?>
    <div class="edit-avatar-modal">
        <h3 style="margin-bottom:8px;">Edit avatar</h3>
        <div class="avatar-actions">
            <button type="button" class="button button-primary avatar-upload-btn">Change / Upload</button>
            <button type="button" class="button avatar-delete-btn">Delete</button>
            <input type="file" id="avatar-file" accept="image/jpeg,image/jpg,image/gif,image/webp" style="display:none;" />
            <p class="avatar-notes">Accepted: JPG, JPEG, GIF (de-animated), WebP. Max upload 500KB. Will be center-cropped to 256×256 and optimized to ≤75KB.</p>
            <div class="field-error" id="avatar-error" style="display:none;"></div>
        </div>
    </div>
    <?php $html = ob_get_clean();
    wp_send_json_success(['html' => $html]);
});

// AJAX: Upload avatar
add_action('wp_ajax_upload_avatar', function(){
    if (!is_user_logged_in() || !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error(['message' => 'Not allowed'], 403);
    }
    if (empty($_FILES['file']) || !isset($_FILES['file']['tmp_name'])) {
        wp_send_json_error(['message' => 'No file provided'], 400);
    }
    $user_id = get_current_user_id();
    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(['message' => 'Upload failed'], 400);
    }
    $allowed = array('image/jpeg','image/jpg','image/gif','image/webp');
    $type = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
    if (empty($type['type']) || !in_array($type['type'], $allowed, true)) {
        wp_send_json_error(['message' => 'Invalid file type'], 400);
    }
    if ($file['size'] > 500 * 1024) {
        wp_send_json_error(['message' => 'File exceeds 500KB'], 400);
    }

    // Sideload into uploads directory
    require_once ABSPATH . 'wp-admin/includes/file.php';
    $overrides = array('test_form' => false);
    $move = wp_handle_sideload($file, $overrides);
    if (empty($move['file'])) {
        wp_send_json_error(['message' => 'Upload failed'], 400);
    }
    $path = $move['file'];

    // Open editor (loads first frame for GIF)
    $editor = wp_get_image_editor($path);
    if (is_wp_error($editor)) {
        @unlink($path);
        wp_send_json_error(['message' => 'Image processing not available'], 500);
    }
    // Correct orientation, crop to square center 256x256
    if (method_exists($editor, 'rotate')) {
        // wp_image_editor auto-orients on load when possible
    }
    $editor->resize(256, 256, true);

    // Save as WebP with iterative quality to ≤75KB
    $quality_steps = array(80, 70, 60, 50, 40);
    $saved = false; $dest_file = '';
    foreach ($quality_steps as $q) {
        if (method_exists($editor, 'set_quality')) { $editor->set_quality($q); }
        $res = $editor->save(null, 'image/webp');
        if (is_wp_error($res)) { continue; }
        $dest_file = $res['path'];
        if (filesize($dest_file) <= 75 * 1024) { $saved = true; break; }
    }
    if (!$saved) {
        if ($dest_file && file_exists($dest_file)) { @unlink($dest_file); }
        @unlink($path);
        wp_send_json_error(['message' => 'Could not optimize avatar to ≤75KB'], 400);
    }
    // Remove original upload
    @unlink($path);

    // Insert attachment
    $upload_dir = wp_upload_dir();
    $attachment = array(
        'guid' => $upload_dir['url'] . '/' . basename($dest_file),
        'post_mime_type' => 'image/webp',
        'post_title' => 'Avatar ' . $user_id,
        'post_content' => '',
        'post_status' => 'inherit',
    );
    $attach_id = wp_insert_attachment($attachment, $dest_file);
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attach_data = wp_generate_attachment_metadata($attach_id, $dest_file);
    wp_update_attachment_metadata($attach_id, $attach_data);

    // Delete previous avatar if any
    $old = (int) get_user_meta($user_id, 'profile_avatar_id', true);
    update_user_meta($user_id, 'profile_avatar_id', $attach_id);
    if ($old && $old !== $attach_id) { wp_delete_attachment($old, true); }

    $url = wp_get_attachment_image_url($attach_id, array(96,96));
    $url = add_query_arg('v', time(), $url);
    wp_send_json_success(['url' => $url, 'attachment_id' => $attach_id]);
});

// AJAX: Delete avatar
add_action('wp_ajax_delete_avatar', function(){
    if (!is_user_logged_in() || !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error(['message' => 'Not allowed'], 403);
    }
    $user_id = get_current_user_id();
    $old = (int) get_user_meta($user_id, 'profile_avatar_id', true);
    if ($old) { wp_delete_attachment($old, true); delete_user_meta($user_id, 'profile_avatar_id'); }
    wp_send_json_success(['message' => 'Avatar removed']);
});


