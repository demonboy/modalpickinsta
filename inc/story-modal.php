<?php
/**
 * Story Modal AJAX rendering
 */

// AJAX handlers (logged-in and public)
add_action('wp_ajax_get_story_modal', 'hrphoto_get_story_modal');
add_action('wp_ajax_nopriv_get_story_modal', 'hrphoto_get_story_modal');

function hrphoto_get_story_modal() {
    // Nonce check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'), 403);
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post'), 400);
    }

    $post = get_post($post_id);
    if (!$post || 'story' !== $post->post_type) {
        wp_send_json_error(array('message' => 'Post not found'), 404);
    }

    // Render within a singular-like context so core/post-* blocks resolve
    global $wp_query, $post;
    $prev_q   = $wp_query;
    $prev_post = $post;

    $q = new WP_Query(array(
        'p'                   => $post_id,
        'post_type'           => 'story',
        'posts_per_page'      => 1,
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
    ));

    if ( $q->have_posts() ) {
        $GLOBALS['wp_query'] = $q;
        $q->the_post();

        ob_start();
        if ( function_exists( 'block_template_part' ) ) {
            // Render the block template part registered for the child theme
            block_template_part( 'story-content', '1hrphoto' );
        } else {
            echo do_blocks( '<!-- wp:template-part {"slug":"story-content","theme":"1hrphoto"} /-->' );
        }
        $html = ob_get_clean();

        wp_reset_postdata();
        $GLOBALS['wp_query'] = $prev_q;
        $post = $prev_post;

        wp_send_json_success(array('html' => $html));
    } else {
        wp_send_json_error(array('message' => 'Post not found'), 404);
    }
}


