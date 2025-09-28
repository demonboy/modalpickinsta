<?php
/**
 * Story Modal Content Partial
 * Expects $post to be set.
 */

if (!isset($post) || !($post instanceof WP_Post)) {
    $post = get_post(get_the_ID());
}

setup_postdata($post);

// Ensure global post context is set for dynamic core/post-* blocks in template part
$featured_id = get_post_thumbnail_id($post->ID);
$featured_url = $featured_id ? wp_get_attachment_image_url($featured_id, 'full') : '';
$featured_caption = $featured_id ? wp_get_attachment_caption($featured_id) : '';
?>

<div class="wp-site-blocks is-root-container">
<article class="story-modal-article" data-post-id="<?php echo esc_attr($post->ID); ?>">
    <?php
    // Render the Story Content template part as blocks with explicit post context
    $tpl_block = array(
        'blockName'   => 'core/template-part',
        'attrs'       => array('slug' => 'story-content', 'theme' => '1hrphoto'),
        'innerBlocks' => array(),
        'innerHTML'   => '',
    );
    if ( class_exists( 'WP_Block' ) ) {
        $wp_block = new WP_Block( $tpl_block, array( 'postId' => $post->ID, 'postType' => $post->post_type ) );
        $output = $wp_block->render();
        if ( is_string( $output ) && trim( $output ) !== '' ) {
            echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            // Minimal fallback: show featured image to avoid empty modal
            if ( has_post_thumbnail( $post ) ) {
                echo get_the_post_thumbnail( $post, 'full' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
        }
    }
    ?>
</article>
</div>

<?php wp_reset_postdata(); ?>


