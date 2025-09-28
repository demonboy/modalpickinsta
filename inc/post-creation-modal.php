<?php
/**
 * Post Creation Modal functionality
 * Handles custom post types, AJAX endpoints, and ACF integration
 */

// Register custom post types
function register_post_creation_post_types() {
    // Check if post types already exist
    if (!post_type_exists('1hrphoto')) {
        register_post_type('1hrphoto', array(
            'labels' => array(
                'name' => '1 Hour Photos',
                'singular_name' => '1 Hour Photo',
                'add_new' => 'Add New 1 Hour Photo',
                'add_new_item' => 'Add New 1 Hour Photo',
                'edit_item' => 'Edit 1 Hour Photo',
                'new_item' => 'New 1 Hour Photo',
                'view_item' => 'View 1 Hour Photo',
                'search_items' => 'Search 1 Hour Photos',
                'not_found' => 'No 1 Hour Photos found',
                'not_found_in_trash' => 'No 1 Hour Photos found in Trash'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'comments', 'author'),
            'taxonomies' => array('category','post_tag'),
            'menu_icon' => 'dashicons-camera',
            'show_in_rest' => true
        ));
    }

    if (!post_type_exists('story')) {
        register_post_type('story', array(
            'labels' => array(
                'name' => 'Stories',
                'singular_name' => 'Story',
                'add_new' => 'Add New Story',
                'add_new_item' => 'Add New Story',
                'edit_item' => 'Edit Story',
                'new_item' => 'New Story',
                'view_item' => 'View Story',
                'search_items' => 'Search Stories',
                'not_found' => 'No Stories found',
                'not_found_in_trash' => 'No Stories found in Trash'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'comments', 'author'),
            'taxonomies' => array('category','post_tag'),
            'menu_icon' => 'dashicons-edit',
            'show_in_rest' => true
        ));
    }
}
add_action('init', 'register_post_creation_post_types');

// AJAX handler for create modal content
function ajax_get_create_modal() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_die('Unauthorized access');
    }

    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_die('Security check failed');
    }

    $content = '<div class="create-modal-content">
        <h2>What would you like to create?</h2>
        <div class="create-options">
            <button class="create-option" data-post-type="1hrphoto">
                <span class="option-icon">📷</span>
                <span class="option-title">1 Hour Photo</span>
            </button>
            <button class="create-option" data-post-type="story">
                <span class="option-icon">📝</span>
                <span class="option-title">Story</span>
            </button>
        </div>

        <p class="create-option-desc"><strong>1 Hour Photo</strong><br>
        Upload your three best photos from your recent photowalk and tell us a bit about them.</p>

        <p class="create-option-desc"><strong>Story</strong><br>
        Post a photo story with a minimum of seven photos and 500 words.</p>
        
        <p class="create-option-note"><em>Images must be JPG, a maximum of 1000 pixels in height and no more than 200KB in size.</em></p>
    </div>';

    wp_send_json_success($content);
}
add_action('wp_ajax_get_create_modal', 'ajax_get_create_modal');

// AJAX handler for post creation form
function ajax_get_post_creation_form() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_die('Unauthorized access');
    }

    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_die('Security check failed');
    }

    $post_type = sanitize_text_field($_POST['post_type']);
    
    if (!in_array($post_type, array('1hrphoto', 'story'))) {
        wp_send_json_error('Invalid post type');
    }

    // Get ACF field group
    $field_group = acf_get_field_group('group_68b878117b7bf');
    $fields = array();
    
    if ($field_group) {
        $fields = acf_get_fields($field_group);
    }

    // Filter fields based on post type (general filtering)
    $filtered_fields = array();
    if ($fields) {
        foreach ($fields as $field) {
            // Check if field should be shown for this post type
            if (should_show_field_for_post_type($field, $post_type)) {
                $filtered_fields[] = $field;
            }
        }
    }

    ob_start();
    include get_stylesheet_directory() . '/templates/post-creation-form.php';
    $form_html = ob_get_clean();

    wp_send_json_success($form_html);
}
add_action('wp_ajax_get_post_creation_form', 'ajax_get_post_creation_form');

// AJAX: direct image upload (device to library, no media frame)
add_action('wp_ajax_upload_acf_image', 'hrphoto_upload_acf_image');
function hrphoto_upload_acf_image() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Not logged in'), 401);
    }

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'), 403);
    }

    if (empty($_FILES['file']) || !isset($_FILES['file']['tmp_name'])) {
        wp_send_json_error(array('message' => 'No file received'), 400);
    }

    // Only allow JPG/JPEG
    $check = wp_check_filetype( $_FILES['file']['name'] );
    if ( empty($check['ext']) || !in_array( strtolower($check['ext']), array('jpg','jpeg'), true ) || $check['type'] !== 'image/jpeg' ) {
        wp_send_json_error(array('message' => 'Only JPG/JPEG files are allowed.'), 400);
    }

    // Enforce max file size 200KB
    if ( isset($_FILES['file']['size']) && (int) $_FILES['file']['size'] > 200 * 1024 ) {
        wp_send_json_error(array('message' => 'Maximum file size must not exceed 200KB'), 400);
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $uploaded = wp_handle_upload($_FILES['file'], array('test_form' => false));
    if (isset($uploaded['error'])) {
        wp_send_json_error(array('message' => $uploaded['error']), 400);
    }

    $attachment_id = wp_insert_attachment(array(
        'post_mime_type' => $uploaded['type'],
        'post_title'     => sanitize_file_name($_FILES['file']['name']),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ), $uploaded['file']);

    if (is_wp_error($attachment_id)) {
        wp_send_json_error(array('message' => $attachment_id->get_error_message()), 500);
    }

    $metadata = wp_generate_attachment_metadata($attachment_id, $uploaded['file']);
    wp_update_attachment_metadata($attachment_id, $metadata);

    // Enforce max height 1000px via metadata
    if ( isset($metadata['height']) && (int) $metadata['height'] > 1000 ) {
        // Clean up the just-created attachment/file to avoid orphaned media
        wp_delete_attachment($attachment_id, true);
        wp_send_json_error(array('message' => 'Maximum height must not exceed 1,000 pixels'), 400);
    }

    $thumb_url = wp_get_attachment_image_url($attachment_id, 'medium');

    wp_send_json_success(array(
        'attachment_id' => $attachment_id,
        'thumbnail_url' => $thumb_url ? $thumb_url : $uploaded['url'],
        // no full_url in previous stable behavior
    ));
}

// Helper function to determine if field should be shown for post type
function should_show_field_for_post_type($field, $post_type) {
    // Define allowed fields for each post type
    if ($post_type === '1hrphoto') {
        $allowed_fields = array(
            'field_68b8781160978',
            'field_68b8785f98ed0',
            'field_68b8786b3c09c'
        );
        return in_array($field['key'], $allowed_fields);
    }
    
    if ($post_type === 'story') {
        $allowed_fields = array(
            'field_68b883a8f6d2f',
            'field_68b88438e44b6',
            'field_68b885d90108e',
            'field_68b885e90108f',
            'field_68b885f401090',
            'field_68b8860201091',
            'field_68b8861001092',
            'field_68b8861e01093',
            'field_68b8862601094',
            'field_68b8862e01095'
        );
        return in_array($field['key'], $allowed_fields);
    }
    
    // Hide fields by default if post type doesn't match
    return false;
}

/**
 * Convert Story editor figures to core/image blocks with alignment.
 *
 * @param string $html Raw sanitized editor HTML (figures with data-attachment-id)
 * @param string $align 'wide' or 'full'
 * @return string Block-serialized content for images; other HTML unchanged
 */
function hrphoto_convert_story_figures_to_image_blocks($html, $align = 'wide') {
    $pattern = '/<figure[^>]*data-attachment-id="(\d+)"[^>]*>([\s\S]*?)<\/figure>/i';
    return preg_replace_callback($pattern, function($m) use ($align) {
        $id = (int) $m[1];
        $inner = $m[2];

        // Extract caption text only; rebuild a clean figcaption to satisfy block validation
        $caption_html = '';
        $caption_text = '';
        if (preg_match('/<figcaption[\s\S]*?<\/figcaption>/i', $inner, $capm)) {
            $caption_text = trim( wp_strip_all_tags( $capm[0] ) );
            if ($caption_text !== '') {
                $caption_html = '<figcaption class="wp-element-caption">' . esc_html( $caption_text ) . '</figcaption>';
            }
        }

        // Build canonical inner HTML for core/image to avoid editor validation warnings
        $src    = wp_get_attachment_image_url($id, 'full');
        if (!$src) {
            return $m[0]; // fallback: keep original figure if source missing
        }

        // Alt precedence: attachment alt > empty if caption exists > empty fallback
        $attachment_alt = (string) get_post_meta($id, '_wp_attachment_image_alt', true);
        if ($attachment_alt !== '') {
            $alt_attr = esc_attr($attachment_alt);
        } elseif ($caption_text !== '') {
            $alt_attr = '';
        } else {
            $alt_attr = '';
        }

        // Canonical inner <img>: src, alt, class only (no width/height/srcset/sizes/loading/decoding)
        $img_html = '<img src="' . esc_url($src) . '" alt="' . $alt_attr . '" class="wp-image-' . esc_attr($id) . '"/>';

        $align = ($align === 'full') ? 'full' : 'wide';
        $align_class = ($align === 'full') ? 'alignfull' : 'alignwide';
        // JSON key order to match editor recovery: id, sizeSlug, linkDestination, align
        $attrs = array(
            'id' => $id,
            'sizeSlug' => 'full',
            'linkDestination' => 'none',
            'align' => $align,
        );
        $comment_open = '<!-- wp:image ' . wp_json_encode($attrs) . ' -->';
        // Order classes as Gutenberg serializes: wp-block-image align* size-full
        $figure = '<figure class="wp-block-image ' . esc_attr($align_class) . ' size-full">' . $img_html . $caption_html . '</figure>';
        $comment_close = '<!-- /wp:image -->';

        return $comment_open . $figure . $comment_close;
    }, $html);
}

/**
 * Normalize Story editor HTML to top-level block markup.
 * - Converts figures to core/image blocks (align wide/full)
 * - Wraps non-figure text runs into core/paragraph blocks
 * - Removes wrapper divs to avoid Classic fallback
 */
function hrphoto_normalize_story_content_to_blocks($html, $align = 'wide') {
    // Remove outer wrappers while keeping inner content
    $html = preg_replace('/^\s*<div[^>]*>([\s\S]*)<\/div>\s*$/i', '$1', $html);

    $blocks = array();
    $cursor = 0;
    $pattern = '/<figure[^>]*data-attachment-id="(\d+)"[^>]*>[\s\S]*?<\/figure>/i';
    // Find all figure matches to split text in-between into paragraphs
    if (preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $idx => $match) {
            $match_str = $match[0];
            $start = $match[1];
            // Text before this figure
            $before = substr($html, $cursor, $start - $cursor);
            $before = trim($before);
            if ($before !== '') {
                $para_text = wp_strip_all_tags($before);
                $para_text = trim(preg_replace('/\s+/', ' ', $para_text));
                if ($para_text !== '') {
                    $blocks[] = '<!-- wp:paragraph --><p>' . esc_html($para_text) . '</p><!-- /wp:paragraph -->';
                }
            }
            // Convert the figure itself
            $figure_html = $match_str;
            $blocks[] = hrphoto_convert_story_figures_to_image_blocks($figure_html, $align);
            // Advance cursor
            $cursor = $start + strlen($match_str);
        }
        // Trailing text after last figure
        $after = trim(substr($html, $cursor));
        if ($after !== '') {
            $para_text = wp_strip_all_tags($after);
            $para_text = trim(preg_replace('/\s+/', ' ', $para_text));
            if ($para_text !== '') {
                $blocks[] = '<!-- wp:paragraph --><p>' . esc_html($para_text) . '</p><!-- /wp:paragraph -->';
            }
        }
    } else {
        // No figures: treat the whole content as a paragraph if non-empty
        $para_text = wp_strip_all_tags($html);
        $para_text = trim(preg_replace('/\s+/', ' ', $para_text));
        if ($para_text !== '') {
            $blocks[] = '<!-- wp:paragraph --><p>' . esc_html($para_text) . '</p><!-- /wp:paragraph -->';
        }
    }

    // Join blocks separated by blank lines
    return implode("\n\n", $blocks);
}

/**
 * Render <figure data-attachment-id="ID"> blocks in Story content by injecting
 * wp_get_attachment_image('full') and preserving any figcaption. Ensures figure has alignwide class.
 *
 * @param string $html Raw HTML from client editor
 * @return string Processed HTML
 */
function hrphoto_render_story_figures_with_images($html) {
    return preg_replace_callback(
        '/<figure([^>]*)\sdata-attachment-id="(\d+)"([^>]*)>([\s\S]*?)<\/figure>/i',
        function ($m) {
            $before_attrs = trim($m[1]);
            $id           = (int) $m[2];
            $after_attrs  = trim($m[3]);
            $inner        = $m[4];

            // Extract existing figcaption (if any)
            $caption = '';
            if (preg_match('/<figcaption[\s\S]*?<\/figcaption>/i', $inner, $cap_match)) {
                $caption = $cap_match[0];
            }

            // Build image HTML using WP API (includes srcset/sizes)
            $image_html = wp_get_attachment_image($id, 'full');

            // Merge classes and ensure alignwide
            $combined_attrs = trim($before_attrs . ' ' . $after_attrs);
            $class_value = 'alignwide';
            if (preg_match('/class\s*=\s*(["\'])(.*?)\1/i', $combined_attrs, $class_match)) {
                $existing = trim($class_match[2]);
                if (stripos($existing, 'alignwide') === false) {
                    $existing = trim($existing . ' alignwide');
                }
                $class_value = $existing;
            }

            return '<figure class="' . esc_attr($class_value) . '" data-attachment-id="' . esc_attr($id) . '">' . $image_html . $caption . '</figure>';
        },
        $html
    );
}

// AJAX handler for post creation
function ajax_create_post() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_die('Unauthorized access');
    }

    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_die('Security check failed');
    }

    $post_type = sanitize_text_field($_POST['post_type']);
    $post_title = sanitize_text_field($_POST['post_title']);
    $post_excerpt = sanitize_textarea_field($_POST['post_excerpt']);
    // Capture body HTML for story/editor content; convert to core/image blocks for Story
    $post_content = isset($_POST['post_content']) ? (string) $_POST['post_content'] : '';
    if ($post_type === 'story' && $post_content !== '') {
        // Sanitize user HTML first, then normalize to top-level blocks
        $sanitized_input = wp_kses_post($post_content);
        $post_content = hrphoto_normalize_story_content_to_blocks($sanitized_input, 'wide');
    } else {
        $post_content = wp_kses_post($post_content);
    }
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $post_tags_raw = isset($_POST['post_tags']) ? sanitize_text_field($_POST['post_tags']) : '';

    // Validate required fields
    $errors = array();
    
    if (empty($post_title)) {
        $errors['post_title'] = 'Title is required';
    }

    if (!in_array($post_type, array('1hrphoto', 'story'))) {
        $errors['post_type'] = 'Invalid post type';
    }
    if (!$category_id || is_wp_error(get_term($category_id, 'category'))) {
        $errors['category_id'] = 'Category is required';
    }

    // Server-side excerpt character count validation (100–500 characters)
    $excerpt_chars = strlen( wp_strip_all_tags( (string) $post_excerpt ) );
    if ($excerpt_chars < 100 || $excerpt_chars > 500) {
        $errors['post_excerpt'] = 'Excerpt must be between 100 and 500 characters.';
    }

    if ($post_type === '1hrphoto') {
        $pic1 = isset($_POST['acf']['1hrpic1']) ? intval($_POST['acf']['1hrpic1']) : 0;
        $pic2 = isset($_POST['acf']['1hrpic2']) ? intval($_POST['acf']['1hrpic2']) : 0;
        $pic3 = isset($_POST['acf']['1hrpic3']) ? intval($_POST['acf']['1hrpic3']) : 0;
        if (!$pic1 || !$pic2 || !$pic3) {
            // Use a key that maps to element id 'acf-1hrpics-error'
            $errors['acf-1hrpics'] = 'You need to upload three images.';
        }
    } elseif ($post_type === 'story') {
        $featured_id = isset($_POST['featured_image_id']) ? intval($_POST['featured_image_id']) : 0;
        if (!$featured_id) {
            $errors['featured_image_id'] = 'Featured image is required.';
        }
        // Validate story images count from JSON
        $story_images_json = isset($_POST['story_images_json']) ? wp_unslash((string) $_POST['story_images_json']) : '[]';
        $imgs = json_decode($story_images_json, true);
        $count = is_array($imgs) ? count($imgs) : 0;
        if ($count < 6 || $count > 10) {
            $errors['story_images'] = 'Please add between 6 and 10 images.';
        }
    }

    // Parse tags for both types (limit 10)
    $tag_names = array();
    if ($post_tags_raw !== '') {
        $pieces = array_map('trim', explode(',', $post_tags_raw));
        // Strip leading # (one or more) from each tag
        $pieces = array_map(function($t){ return preg_replace('/^#+/', '', $t); }, $pieces);
        $pieces = array_filter($pieces, function($t){ return $t !== ''; });
        $pieces = array_values(array_unique($pieces));
        if (count($pieces) > 10) {
            $errors['post_tags'] = 'You can add up to 10 tags.';
        } else {
            $tag_names = $pieces;
        }
    }

    // 1hrphoto-only: capture wants_constructive_feedback flag from form
    if ($post_type === '1hrphoto') {
        $wants_constructive = (isset($_POST['constructive_feedback']) && sanitize_text_field($_POST['constructive_feedback']) === 'yes') ? 'yes' : 'no';
        // Tag behavior retained as legacy (no display dependency)
        if ($wants_constructive === 'yes') {
            $tag_names[] = 'constructive feedback';
            $tag_names = array_values(array_unique($tag_names));
        }
    }

    if (!empty($errors)) {
        wp_send_json_error($errors);
    }

    // Normalize title case (apply on create/edit)
    $normalized_title = ucwords( strtolower( (string) $post_title ) );

    // Create the post
    $post_data = array(
        'post_title' => $normalized_title,
        'post_excerpt' => $post_excerpt,
        'post_content' => $post_content,
        'post_type' => $post_type,
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
        'post_category' => array($category_id),
    );

    $post_id = wp_insert_post($post_data);

    // wp_insert_post returns 0 or WP_Error on failure
    if (is_wp_error($post_id) || 0 === (int) $post_id) {
        wp_send_json_error('Failed to create post');
    }

    // Debug: Log what we received
    // Persist wants_constructive_feedback meta for 1hrphoto
    if ($post_type === '1hrphoto') {
        update_post_meta($post_id, 'wants_constructive_feedback', isset($wants_constructive) ? $wants_constructive : 'no');
    }
    error_log('POST data: ' . print_r($_POST, true));
    error_log('FILES data: ' . print_r($_FILES, true));
    
    // Handle file uploads first (required for ACF image fields)
    if (!empty($_FILES)) {
        // Include WordPress file handling functions
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Process uploaded files
        foreach ($_FILES as $key => $file) {
            error_log("Processing file: $key - " . print_r($file, true));
            
            if ($file['error'] === 0) {
                $uploaded_file = wp_handle_upload($file, array('test_form' => false));
                if (!isset($uploaded_file['error'])) {
                    // Create attachment
                    $attachment = array(
                        'post_mime_type' => $uploaded_file['type'],
                        'post_title' => sanitize_file_name($file['name']),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    );
                    
                    $attachment_id = wp_insert_attachment($attachment, $uploaded_file['file'], $post_id);
                    if (!is_wp_error($attachment_id)) {
                        // Generate attachment metadata
                        $attachment_data = wp_generate_attachment_metadata($attachment_id, $uploaded_file['file']);
                        wp_update_attachment_metadata($attachment_id, $attachment_data);
                        
                        // Store attachment ID for ACF field processing
                        // The key should match the ACF field name
                        $_POST['acf'][$key] = $attachment_id;
                        error_log("Created attachment ID: $attachment_id for field: $key");
                    } else {
                        error_log("Failed to create attachment: " . $attachment_id->get_error_message());
                    }
                } else {
                    error_log("Upload error: " . $uploaded_file['error']);
                }
            } else {
                error_log("File error: " . $file['error']);
            }
        }
    } else {
        error_log('No files received in $_FILES');
    }

    // Save Story or 1hrphoto specifics
    if (function_exists('acf_get_fields')) {
        $field_group = acf_get_field_group('group_68b878117b7bf');
        if ($field_group) {
            $fields = acf_get_fields($field_group);
            if ($fields) {
                foreach ($fields as $field) {
                    if (should_show_field_for_post_type($field, $post_type)) {
                        $field_name = $field['name'];
                        if (isset($_POST['acf'][$field_name])) {
                            $field_value = $_POST['acf'][$field_name];
                            update_field($field_name, $field_value, $post_id);
                        }
                    }
                }
            }
        }
    }

    if ($post_type === 'story') {
        // Set featured image and caption
        $featured_id = isset($_POST['featured_image_id']) ? intval($_POST['featured_image_id']) : 0;
        if ($featured_id) {
            set_post_thumbnail($post_id, $featured_id);
            if (isset($_POST['featured_image_caption'])) {
                wp_update_post(array('ID' => $featured_id, 'post_excerpt' => sanitize_text_field($_POST['featured_image_caption'])));
            }
        }
        // Story images mapping and captions
        $story_images_json = isset($_POST['story_images_json']) ? wp_unslash((string) $_POST['story_images_json']) : '[]';
        $imgs = json_decode($story_images_json, true);
        if (is_array($imgs) && !empty($imgs)) {
            $max = min(10, count($imgs));
            for ($i = 0; $i < $max; $i++) {
                $id = intval($imgs[$i]['id']);
                $cap = isset($imgs[$i]['caption']) ? sanitize_text_field($imgs[$i]['caption']) : '';
                if ($id) {
                    // Update attachment caption
                    wp_update_post(array('ID' => $id, 'post_excerpt' => $cap));
                    // Save to ACF fields storypic1..storypic10
                    $field_name = 'storypic' . ($i + 1);
                    update_field($field_name, $id, $post_id);
                }
            }
        }
        // Tags for story (same as 1hrphoto)
        if (!empty($tag_names)) {
            wp_set_post_terms($post_id, $tag_names, 'post_tag', false);
        }
    }

    // Assign tags if provided
    if (in_array($post_type, array('1hrphoto','story'), true) && !empty($tag_names)) {
        wp_set_post_terms($post_id, $tag_names, 'post_tag', false);
    }

    // Prepare success message
    $post_type_label = ($post_type === '1hrphoto') ? '1 Hour Photo' : 'Story';
    $success_message = $post_type_label . ' created successfully!';

    wp_send_json_success(array(
        'message' => $success_message,
        'post_id' => $post_id
    ));
}
add_action('wp_ajax_create_post', 'ajax_create_post');
