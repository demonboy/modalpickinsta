<?php
/**
 * Author base → Photographer
 * - Uses documented WP_Rewrite API to change the author archive base
 * - After deploying, visit Settings → Permalinks and click Save once to flush
 */

add_action('init', function() {
    global $wp_rewrite;
    if (!isset($wp_rewrite) || !is_object($wp_rewrite)) {
        return;
    }
    // Set new base and structure for author archives
    $desired_base = 'photographer';
    if ($wp_rewrite->author_base !== $desired_base) {
        $wp_rewrite->author_base = $desired_base;
        $wp_rewrite->author_structure = '/' . trim($desired_base, '/') . '/%author%';
        // Do NOT flush rules programmatically here; user will flush via Permalinks screen
    }
});


