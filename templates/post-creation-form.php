<?php
/**
 * Post Creation Form Template
 * Used for both 1hrphoto and story post creation
 */

$post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '1hrphoto';
$post_type_label = ($post_type === '1hrphoto') ? '1 Hour Photo' : 'Story';
?>

<form class="post-creation-form" data-post-type="<?php echo esc_attr($post_type); ?>" enctype="multipart/form-data">
    <div class="form-header">
        <h2>Create <?php echo esc_html($post_type_label); ?></h2>
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
                <div id="story-featured-preview" class="story-featured-preview" aria-live="polite"></div>
                <button type="button" id="story-featured-uploader" class="button">Add featured image</button>
                <input type="hidden" name="featured_image_id" id="featured-image-id">
                <input type="hidden" id="featured-image-caption" name="featured_image_caption" value="">
                <div class="field-error" id="featured_image_id-error"></div>
            </div>
        <?php elseif ($is_1hrphoto): ?>
            <div class="form-field">
                <label>Images <span class="required">*</span></label>
                <div id="thumbs-row" class="thumbs-row" aria-live="polite"></div>
                <button type="button" id="one-uploader" class="button">Add images</button>
                <input type="hidden" name="acf[1hrpic1]" id="acf-1hrpic1">
                <input type="hidden" name="acf[1hrpic2]" id="acf-1hrpic2">
                <input type="hidden" name="acf[1hrpic3]" id="acf-1hrpic3">
                <div class="field-error" id="acf-1hrpics-error"></div>
            </div>
        <?php endif; ?>
        <!-- WordPress Standard Fields -->
        <div class="form-field">
            <label for="post-title">Title <span class="required">*</span></label>
            <input type="text" id="post-title" name="post_title" required>
            <div class="field-error" id="post-title-error"></div>
        </div>
        
        <div class="form-field">
            <label for="post-excerpt">Excerpt</label>
            <textarea id="post-excerpt" name="post_excerpt" rows="3"></textarea>
            <div class="field-error" id="post-excerpt-error"></div>
            <div class="story-image-count" id="excerpt-count">0/100 characters (max 500)</div>
        </div>

        <?php if ($post_type === 'story'): ?>
            <div class="form-field">
                <label for="story-editor">Main body</label>
                <div class="story-editor-toolbar" aria-label="Editor toolbar">
                    <button type="button" class="btn-story-heading" aria-label="Heading">Heading</button>
                    <button type="button" class="btn-story-bold" aria-label="Bold">B</button>
                    <button type="button" class="btn-story-italic" aria-label="Italic"><em>I</em></button>
                    <button type="button" class="btn-story-link" aria-label="Insert link">Link</button>
                    <button type="button" class="btn-story-insert-image" aria-label="Insert image">Image</button>
                </div>
                <div id="story-editor" class="story-editor" contenteditable="true" role="textbox" aria-multiline="true"></div>
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
                        <option value="<?php echo esc_attr($cat->term_id); ?>">
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
                <input type="text" id="post-tags" name="post_tags" placeholder="Comma-separated, up to 10, no hash # required">
                <div class="field-error" id="post-tags-error"></div>
            </div>

            <div class="form-field">
                <label>Want constructive feedback?</label>
                <div class="feedback-options">
                    <label><input type="radio" name="constructive_feedback" value="yes"> Yes</label>
                    <label><input type="radio" name="constructive_feedback" value="no" checked> No</label>
                </div>
            </div>
        <?php elseif ($post_type === 'story'): ?>
            <div class="form-field">
                <label for="post-tags">Tags</label>
                <input type="text" id="post-tags" name="post_tags" placeholder="Comma-separated, up to 10, no hash # required">
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
    
    <div class="form-actions">
        <button type="button" class="btn-back">Back</button>
        <button type="submit" class="btn-submit">
            <span class="btn-text">Create <?php echo esc_html($post_type_label); ?></span>
            <span class="btn-loading" style="display: none;">Creating...</span>
        </button>
    </div>
    
    <div class="form-messages">
        <div class="success-message" id="success-message" style="display: none;"></div>
        <div class="error-message" id="error-message" style="display: none;"></div>
    </div>
</form>
