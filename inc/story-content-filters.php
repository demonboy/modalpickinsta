<?php
/**
 * Filters for Story post content rendering.
 * - Make inline body images (figures inserted by editor with data-attachment-id)
 *   use larger responsive markup and behave like "wide" images on larger screens.
 */

// Helper to transform figures with data-attachment-id into responsive, wide images
function hrphoto_transform_story_body_images( $html ) {
    $pattern = '/<figure([^>]*)\sdata-attachment-id="(\d+)"([^>]*)>([\s\S]*?)<\/figure>/i';
    return preg_replace_callback( $pattern, function( $m ) {
        $attr_left  = trim( $m[1] );
        $attachment = intval( $m[2] );
        $attr_right = trim( $m[3] );
        $inner_html = $m[4];

        // Build attributes and ensure alignwide
        $attrs = trim( $attr_left . ' ' . $attr_right );
        if ( preg_match( '/class="([^"]*)"/i', $attrs, $cm ) ) {
            $classes = $cm[1];
            if ( stripos( $classes, 'alignwide' ) === false ) {
                $new_classes = trim( $classes . ' alignwide' );
                $attrs = preg_replace( '/class="([^"]*)"/i', 'class="' . esc_attr( $new_classes ) . '"', $attrs, 1 );
            }
        } else {
            $attrs .= ' class="alignwide"';
        }

        // Ensure the figure retains data-attachment-id
        if ( ! preg_match( '/data-attachment-id="/i', $attrs ) ) {
            $attrs .= ' data-attachment-id="' . esc_attr( $attachment ) . '"';
        }

        // Preserve figcaption if present
        $caption_html = '';
        if ( preg_match( '/<figcaption[\s\S]*?<\/figcaption>/i', $inner_html, $capm ) ) {
            $caption_html = $capm[0];
        }

        // Prefer responsive full image (srcset). Fallback to direct URL if needed
        $img_html = wp_get_attachment_image( $attachment, 'full', false, array( 'class' => 'wp-image-' . $attachment ) );
        if ( ! $img_html ) {
            $url = wp_get_attachment_url( $attachment );
            if ( $url ) {
                $img_html = '<img src="' . esc_url( $url ) . '" alt="" class="wp-image-' . esc_attr( $attachment ) . '" loading="lazy" />';
            }
        }
        if ( ! $img_html ) {
            return $m[0];
        }

        return '<figure ' . $attrs . '>' . $img_html . $caption_html . '</figure>';
    }, $html );
}

// Render-time transform for Story content inside core/post-content
add_filter( 'render_block', function( $block_content, $block ) {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return $block_content;
    }
    if ( empty( $block['blockName'] ) || $block['blockName'] !== 'core/post-content' ) {
        return $block_content;
    }
    global $post;
    if ( ! ( $post instanceof WP_Post ) || $post->post_type !== 'story' ) {
        return $block_content;
    }
    return hrphoto_transform_story_body_images( $block_content );
}, 12, 2 );

// Also hook the dedicated core/post-content render filter for reliability
add_filter( 'render_block_core/post-content', function( $block_content, $block ) {
    if ( is_admin() && ! wp_doing_ajax() ) { return $block_content; }
    global $post;
    if ( ! ( $post instanceof WP_Post ) || $post->post_type !== 'story' ) { return $block_content; }
    return hrphoto_transform_story_body_images( $block_content );
}, 12, 2 );

// Fallback: also apply on the_content to cover any render paths that bypass block hooks
add_filter( 'the_content', function( $content ) {
    if ( is_admin() && ! wp_doing_ajax() ) { return $content; }
    global $post;
    if ( ! ( $post instanceof WP_Post ) || $post->post_type !== 'story' ) { return $content; }
    return hrphoto_transform_story_body_images( $content );
}, 12 );


