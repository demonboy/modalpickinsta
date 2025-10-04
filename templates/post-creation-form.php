<?php
/**
 * Post Creation Form Template
 * Used for both 1hrphoto and story post creation and editing
 */

$post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '1hrphoto';
$post_type_label = ($post_type === '1hrphoto') ? '1 Hour Photo' : 'Story';

// Prepare edit mode data
$editing_title = '';
$editing_excerpt = '';
$editing_content = '';
$editing_category = 0;
$editing_tags = '';
$editing_feedback = 'no';
$editing_featured_id = 0;
$editing_featured_caption = '';

if (isset($is_editing) && $is_editing && isset($editing_post) && $editing_post) {
    $editing_title = $editing_post->post_title;
    $editing_excerpt = $editing_post->post_excerpt;
    $editing_content = $editing_post->post_content;
    
    // Get category
    $cats = wp_get_post_categories($editing_post->ID);
    if (!empty($cats)) {
        $editing_category = (int) $cats[0];
    }
    
    // Get tags
    $post_tags = wp_get_post_tags($editing_post->ID, array('fields' => 'names'));
    if (!empty($post_tags)) {
        $editing_tags = implode(', ', $post_tags);
    }
    
    // Get constructive feedback meta for 1hrphoto
    if ($post_type === '1hrphoto') {
        $editing_feedback = get_post_meta($editing_post->ID, 'wants_constructive_feedback', true);
        if ($editing_feedback !== 'yes') {
            $editing_feedback = 'no';
        }
    }
    
    // Get featured image for story
    if ($post_type === 'story') {
        $editing_featured_id = get_post_thumbnail_id($editing_post->ID);
        if ($editing_featured_id) {
            $editing_featured_caption = get_post($editing_featured_id)->post_excerpt;
        }
    }
}

$action_verb = (isset($is_editing) && $is_editing) ? 'Edit' : 'Create';
$modal_title = (isset($is_editing) && $is_editing && isset($editing_post)) 
    ? $action_verb . ' ' . $editing_post->post_title 
    : $action_verb . ' ' . $post_type_label;
?>

<form class="post-creation-form" data-post-type="<?php echo esc_attr($post_type); ?>" enctype="multipart/form-data">
    <?php if (isset($is_editing) && $is_editing && isset($editing_post)): ?>
        <input type="hidden" name="post_id" value="<?php echo esc_attr($editing_post->ID); ?>">
    <?php endif; ?>
    
    <div class="form-header">
        <h2><?php echo esc_html($modal_title); ?></h2>
        <?php if ($post_type === '1hrphoto'): ?>
        <p class="form-header-tip">Tip: Try to avoid different image dimensions. This works best when your photographs are at least the same height. You may reorder your images once uploaded.</p>
        <?php endif; ?>
    </div>
    
    <?php
    $is_1hrphoto = ($post_type === '1hrphoto');
    $skip_image_keys = array('field_68b8781160978','field_68b8785f98ed0','field_68b8786b3c09c');
    ?>
    
    <div class="form-fields">
        <?php if ($post_type === 'story'): ?>
            <div class="form-field">
                <label>Featured image <span class="required">*</span></label>
                <div id="story-featured-preview" class="story-featured-preview" aria-live="polite">
                    <?php if ($editing_featured_id): ?>
                        <div class="acf-image-preview" data-attachment-id="<?php echo esc_attr($editing_featured_id); ?>">
                            <?php echo wp_get_attachment_image($editing_featured_id, 'medium'); ?>
                            <button type="button" class="remove-image" data-attachment-id="<?php echo esc_attr($editing_featured_id); ?>" aria-label="Remove image">×</button>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" id="story-featured-uploader" class="button"><?php echo $editing_featured_id ? 'Replace featured image' : 'Add featured image'; ?></button>
                <input type="hidden" name="featured_image_id" id="featured-image-id" value="<?php echo esc_attr($editing_featured_id); ?>">
                <input type="hidden" id="featured-image-caption" name="featured_image_caption" value="<?php echo esc_attr($editing_featured_caption); ?>">
                <div class="field-error" id="featured_image_id-error"></div>
            </div>
        <?php elseif ($is_1hrphoto): ?>
            <div class="form-field">
                <label>Images <span class="required">*</span></label>
                <div id="thumbs-row" class="thumbs-row" aria-live="polite" 
                     data-edit-mode="<?php echo isset($is_editing) && $is_editing ? '1' : '0'; ?>"
                     data-existing-images="<?php 
                        // Pass existing image data as JSON for JavaScript to handle
                        if (isset($is_editing) && $is_editing && isset($editing_post)) {
                            $existing = array();
                            for ($i = 1; $i <= 3; $i++) {
                                $field_val = get_field('1hrpic' . $i, $editing_post->ID);
                                if ($field_val) {
                                    $img_id = is_array($field_val) ? (int) $field_val['ID'] : (int) $field_val;
                                    if ($img_id) {
                                        $img_url = wp_get_attachment_image_url($img_id, 'medium');
                                        if ($img_url) {
                                            $existing[] = array('id' => $img_id, 'url' => $img_url);
                                        }
                                    }
                                }
                            }
                            echo esc_attr(json_encode($existing));
                        }
                     ?>">
                    <!-- Thumbnails will be rendered by JavaScript -->
                </div>
                <button type="button" id="one-uploader" class="button">Add images</button>
                <?php
                // Get 1hrphoto image IDs for edit mode
                $pic1_val = '';
                $pic2_val = '';
                $pic3_val = '';
                if (isset($is_editing) && $is_editing && isset($editing_post)) {
                    for ($i = 1; $i <= 3; $i++) {
                        $field_val = get_field('1hrpic' . $i, $editing_post->ID);
                        $img_id = 0;
                        if ($field_val) {
                            $img_id = is_array($field_val) ? (int) $field_val['ID'] : (int) $field_val;
                        }
                        if ($i === 1) $pic1_val = $img_id;
                        if ($i === 2) $pic2_val = $img_id;
                        if ($i === 3) $pic3_val = $img_id;
                    }
                }
                ?>
                <input type="hidden" name="acf[1hrpic1]" id="acf-1hrpic1" value="<?php echo esc_attr($pic1_val); ?>">
                <input type="hidden" name="acf[1hrpic2]" id="acf-1hrpic2" value="<?php echo esc_attr($pic2_val); ?>">
                <input type="hidden" name="acf[1hrpic3]" id="acf-1hrpic3" value="<?php echo esc_attr($pic3_val); ?>">
                <div class="field-error" id="acf-1hrpics-error"></div>
            </div>
        <?php endif; ?>
        <!-- WordPress Standard Fields -->
        <div class="form-field">
            <label for="post-title">Title <span class="required">*</span></label>
            <input type="text" id="post-title" name="post_title" value="<?php echo esc_attr($editing_title); ?>" required>
            <div class="field-error" id="post-title-error"></div>
        </div>
        
        <div class="form-field">
            <label for="post-excerpt">Excerpt</label>
            <textarea id="post-excerpt" name="post_excerpt" rows="3"><?php echo esc_textarea($editing_excerpt); ?></textarea>
            <div class="field-error" id="post-excerpt-error"></div>
            <div class="story-image-count" id="excerpt-count">0/100 characters (max 500)</div>
        </div>

        <?php if ($post_type === 'story'): ?>
            <div class="form-field">
                <label for="story-editor">Main body</label>
                <div class="story-editor-toolbar" aria-label="Editor toolbar">
                    <button type="button" class="btn-story-heading" aria-label="Heading">Heading</button>
                    <button type="button" class="btn-story-bold" aria-label="Bold">B</button>
                    <button type="button" class="btn-story-italic" aria-label="Italic"><em>i</em></button>
                    <button type="button" class="btn-story-link" aria-label="Insert link">Link</button>
                    <button type="button" class="btn-story-insert-image" aria-label="Insert image">Image</button>
                </div>
                <div id="story-editor" class="story-editor" contenteditable="true" role="textbox" aria-multiline="true"><?php echo $editing_content; ?></div>
                <textarea id="post-content" name="post_content" style="display:none;"></textarea>
                <input type="hidden" id="story-images-json" name="story_images_json" value="[]">
                <div class="field-error" id="post_content-error"></div>
                <div class="field-error" id="story_images-error"></div>
                <div class="story-image-count" id="story-image-count" aria-live="polite">0/6 minimum images inserted (max 10)</div>
                <div class="story-image-count" id="story-word-count" aria-live="polite">0/500 words (max 2000)</div>
            </div>
        <?php endif; ?>

        <?php
        $categories = get_terms(array(
            'taxonomy'   => 'category',
            'hide_empty' => false,
        ));
        ?>
        <div class="form-field">
            <label for="category-id">Category <span class="required">*</span></label>
            <select id="category-id" name="category_id" required oninvalid="this.setCustomValidity('Please select a category')" oninput="this.setCustomValidity('')">
                <option value="">Select a category</option>
                <?php if (!is_wp_error($categories) && !empty($categories)) : ?>
                    <?php foreach ($categories as $cat) : ?>
                        <option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected($editing_category, $cat->term_id); ?>>
                            <?php echo esc_html($cat->name); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <div class="field-error" id="category_id-error"></div>
        </div>
        
        <?php if ($is_1hrphoto): ?>
            <div class="form-field">
                <label for="post-tags">Tags</label>
                <input type="text" id="post-tags" name="post_tags" value="<?php echo esc_attr($editing_tags); ?>" placeholder="Comma-separated, up to 10, no hash # required">
                <div class="field-error" id="post-tags-error"></div>
            </div>

            <div class="form-field">
                <label>Want constructive feedback?</label>
                <div class="feedback-options">
                    <label><input type="radio" name="constructive_feedback" value="yes" <?php checked($editing_feedback, 'yes'); ?>> Yes</label>
                    <label><input type="radio" name="constructive_feedback" value="no" <?php checked($editing_feedback, 'no'); ?>> No</label>
                </div>
            </div>
        <?php elseif ($post_type === 'story'): ?>
            <div class="form-field">
                <label for="post-tags">Tags</label>
                <input type="text" id="post-tags" name="post_tags" value="<?php echo esc_attr($editing_tags); ?>" placeholder="Comma-separated, up to 10, no hash # required">
                <div class="field-error" id="post-tags-error"></div>
            </div>
        <?php endif; ?>

        <!-- ACF Fields (skip for story; images are handled via editor) -->
        <?php if ($post_type !== 'story' && function_exists('acf_get_fields') && isset($filtered_fields) && !empty($filtered_fields)): ?>
            <?php foreach ($filtered_fields as $field): ?>
                <?php if ($is_1hrphoto && in_array($field['key'], $skip_image_keys, true)) { continue; } ?>
                <div class="form-field acf-field" data-field-name="<?php echo esc_attr($field['name']); ?>">
                    <label for="<?php echo esc_attr($field['name']); ?>">
                        <?php echo esc_html($field['label']); ?>
                        <?php if ($field['required']): ?>
                            <span class="required">*</span>
                        <?php endif; ?>
                    </label>
                    
                    <?php
                    // Render ACF field based on type
                    $field['value'] = '';
                    $field['id'] = $field['name'];
                    acf_render_field($field);
                    ?>
                    
                    <div class="field-error" id="<?php echo esc_attr($field['name']); ?>-error"></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="form-messages">
        <div class="success-message" id="success-message" style="display: none;"></div>
        <div class="error-message" id="error-message" style="display: none;"></div>
    </div>
    
    <div class="form-actions">
        <button type="button" class="btn-back">Back</button>
        <button type="submit" class="btn-submit">
            <span class="btn-text"><?php echo esc_html($action_verb . ' ' . $post_type_label); ?></span>
            <span class="btn-loading" style="display: none;"><?php echo $action_verb === 'Edit' ? 'Updating...' : 'Creating...'; ?></span>
        </button>
    </div>
</form>
