<?php
/**
 * Extend core search to include matches on tag names/slugs (post_tag) in addition to content.
 * - Scoped to front-end main search queries only
 * - Uses EXISTS subquery; no global JOINs; low risk of duplicate rows
 */

if (!function_exists('hrphoto_search_include_tags')) {
    function hrphoto_search_include_tags($search, $wp_query) {
        if (is_admin() || !$wp_query->is_main_query() || !$wp_query->is_search()) {
            return $search;
        }
        global $wpdb;
        $s = (string) $wp_query->get('s');
        if ($s === '') {
            return $search;
        }
        // Prepare LIKE string safely
        $like = '%' . $wpdb->esc_like($s) . '%';

        // EXISTS subquery against post_tag to OR into the core search clause
        $exists = $wpdb->prepare(
            " OR EXISTS (
                SELECT 1
                FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'post_tag'
                INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
                WHERE tr.object_id = {$wpdb->posts}.ID
                  AND (t.name LIKE %s OR t.slug LIKE %s)
            )",
            $like,
            $like
        );

        // $search looks like:  AND ( (posts.post_title LIKE ...) OR ... )
        // Inject our OR EXISTS before the final closing paren to keep it inside the search clause
        $modified = preg_replace('/\)\s*$/', $exists . ')', $search, 1);
        return $modified ? $modified : $search . $exists; // fallback append if pattern fails
    }
    add_filter('posts_search', 'hrphoto_search_include_tags', 10, 2);
}




