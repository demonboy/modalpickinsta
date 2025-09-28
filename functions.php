<?php
/**
 * 1hrphoto child theme functions
 */


/*ENQUEUE FUNCTIONS*/
require get_stylesheet_directory() . '/inc/enqueue.php';

/*SHORTCODES*/
require get_stylesheet_directory() . '/inc/shortcodes.php';

/*LIGHTBOX*/
require get_stylesheet_directory() . '/inc/lightbox.php';

/* PROFILE EDITOR*/
require get_stylesheet_directory() . '/inc/profile-editor.php';

/* COMMENTS MODAL */
require get_stylesheet_directory() . '/inc/comments-modal.php';

/* POST CREATION MODAL */
require get_stylesheet_directory() . '/inc/post-creation-modal.php';

/* QUERY MODS */
require get_stylesheet_directory() . '/inc/queries.php';

/* STORY MODAL */
require get_stylesheet_directory() . '/inc/story-modal.php';

/* STORY CONTENT FILTERS */
require get_stylesheet_directory() . '/inc/story-content-filters.php';

/* PROFILE MODAL */
require get_stylesheet_directory() . '/inc/profile-modal.php';

/* AVATAR */
require get_stylesheet_directory() . '/inc/avatar.php';

/* SOCIAL MODAL */
require get_stylesheet_directory() . '/inc/social-modal.php';

/* PROFILE SUMMARY */
require get_stylesheet_directory() . '/inc/profile-bio.php';

/* FOLLOW / FOLLOWERS */
require get_stylesheet_directory() . '/inc/follow.php';
require get_stylesheet_directory() . '/inc/follow-modal.php';

/* FEED SETTINGS MODAL */
require get_stylesheet_directory() . '/inc/feed-modal.php';

/* SOCIAL ICONS (selector + shortcode) */
require get_stylesheet_directory() . '/inc/social-icons.php';

/* INFINITE SCROLL */
require get_stylesheet_directory() . '/inc/infinite-scroll.php';

/* BACK TO TOP */
require get_stylesheet_directory() . '/inc/back-to-top.php';

/* SHARE (standalone modal helpers & shortcodes support) */
require get_stylesheet_directory() . '/inc/share.php';

/* REWRITES (author → photographer) */
require get_stylesheet_directory() . '/inc/rewrite.php';

/* FRONT-END FILTERS (Author → Photographer labels) */
require get_stylesheet_directory() . '/inc/filters.php';

/* SEARCH EXTENSIONS (include tag name matches in search) */
require get_stylesheet_directory() . '/inc/search-filters.php';

// Using custom AJAX forms in modal; no acf_form_head needed

/* ACF FIELD POPULATORS (removed for manual country field) */

/* ACF SAVE FILTERS (none; using standard ACF behavior) */

/* Add data-story-id to Story links emitted by core blocks */
add_filter('render_block', function($block_content, $block){
    if (!is_string($block_content) || empty($block_content)) return $block_content;
    if (empty($block['blockName'])) return $block_content;
    // Only target post title and featured image blocks
    if (!in_array($block['blockName'], array('core/post-title', 'core/post-featured-image'), true)) return $block_content;

    global $post;
    if (!$post || 'story' !== $post->post_type) return $block_content;

    // Add data-story-id="{ID}" to the first anchor in the block output
    $updated = preg_replace(
        '/<a\s+/i',
        '<a data-story-id="' . esc_attr($post->ID) . '" ',
        $block_content,
        1
    );
    return $updated ?: $block_content;
}, 10, 2);


// Mark core/post-author-name output so we can live-update the displayed name after profile edits
add_filter('render_block', function($block_content, $block){
    if (!is_string($block_content) || empty($block_content)) return $block_content;
    if (empty($block['blockName']) || $block['blockName'] !== 'core/post-author-name') return $block_content;

    global $post;
    if (!$post || empty($post->post_author)) return $block_content;
    $author_id = (int) $post->post_author;

    // Add data-post-author-id to wrapper div
    $updated = preg_replace(
        '/class="wp-block-post-author-name"/i',
        'class="wp-block-post-author-name" data-post-author-id="' . esc_attr($author_id) . '"',
        $block_content,
        1
    );
    if ($updated) {
        // Add data-author-display to the anchor element
        $updated = preg_replace(
            '/class="wp-block-post-author-name__link"/i',
            'class="wp-block-post-author-name__link" data-author-display',
            $updated,
            1
        );
        return $updated;
    }
    return $block_content;
}, 10, 2);


add_action('wp_footer', function() {
    if (function_exists('the_block_pattern')) {
        the_block_pattern('1hrphoto/bottom-menu');
    }
});

