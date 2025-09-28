<?php
/**
 * Share helpers for posts and authors.
 * - Build canonical share URLs and titles
 * - Provide encoded network URLs
 */

if (!defined('ABSPATH')) { exit; }

/**
 * Build a homepage URL that can locate a card for a post shown only on Home.
 * Uses /?post={ID}; JS will scroll to the matching card and highlight it.
 */
function onehr_share_home_url_for_post($post_id) {
    $post_id = (int) $post_id;
    if ($post_id <= 0) { return home_url('/'); }
    // Prefer an anchor if present; we still include query fallback for JS resolver.
    $url = home_url('/');
    $url = add_query_arg('post', $post_id, $url);
    return $url;
}

/**
 * Get share title for a post.
 */
function onehr_share_post_title($post_id) {
    $post = get_post($post_id);
    if (!$post) { return get_bloginfo('name'); }
    $title = get_the_title($post);
    if ($title === '') { $title = get_bloginfo('name'); }
    return wp_strip_all_tags($title);
}

/**
 * Build author profile URL and title (display_name).
 */
function onehr_share_author_data($author_id) {
    $author_id = (int) $author_id;
    $url = get_author_posts_url($author_id);
    $display = get_the_author_meta('display_name', $author_id);
    if ($display === '') { $display = __('Author', '1hrphoto'); }
    return array(
        'url' => $url,
        'title' => wp_strip_all_tags($display),
    );
}

/**
 * Return an array of share network links for given title+url.
 * Keys: id, label, href
 */
function onehr_share_network_links($title, $url) {
    $enc_url   = rawurlencode($url);
    $enc_title = rawurlencode($title);
    return array(
        array(
            'id'    => 'facebook',
            'label' => 'Facebook',
            'href'  => 'https://www.facebook.com/sharer/sharer.php?u=' . $enc_url,
        ),
        array(
            'id'    => 'x',
            'label' => 'X',
            'href'  => 'https://twitter.com/intent/tweet?url=' . $enc_url . '&text=' . $enc_title,
        ),
        array(
            'id'    => 'whatsapp',
            'label' => 'WhatsApp',
            'href'  => 'https://wa.me/?text=' . $enc_title . '%20' . $enc_url,
        ),
        array(
            'id'    => 'linkedin',
            'label' => 'LinkedIn',
            'href'  => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $enc_url,
        ),
        array(
            'id'    => 'reddit',
            'label' => 'Reddit',
            'href'  => 'https://www.reddit.com/submit?url=' . $enc_url . '&title=' . $enc_title,
        ),
        array(
            'id'    => 'telegram',
            'label' => 'Telegram',
            'href'  => 'https://t.me/share/url?url=' . $enc_url . '&text=' . $enc_title,
        ),
        array(
            'id'    => 'email',
            'label' => 'Email',
            'href'  => 'mailto:?subject=' . $enc_title . '&body=' . $enc_url,
        ),
        // Copy is handled in JS; include a placeholder id for UI ordering
        array(
            'id'    => 'copy',
            'label' => 'Copy',
            'href'  => $url,
        ),
    );
}


