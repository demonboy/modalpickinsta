<?php
/**
 * Social Icons selection (ACF) + shortcode renderer
 */

if (!defined('ABSPATH')) { exit; }

// Register a small ACF group for selecting up to 6 platforms (checkbox-based; works with ACF Free)
add_action('acf/init', function(){
    if (!function_exists('acf_add_local_field_group')) { return; }

    $platform_choices = array(
        'instagram' => 'Instagram',
        'threads' => 'Threads',
        'x' => 'X (Twitter)',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'vimeo' => 'Vimeo',
        'flickr' => 'Flickr',
        'fivehundredpx' => '500px',
        'pinterest' => 'Pinterest',
        'reddit' => 'Reddit',
        'linkedin' => 'LinkedIn',
        'behance' => 'Behance',
        'dribbble' => 'Dribbble',
        'deviantart' => 'DeviantArt',
        'tumblr' => 'Tumblr',
        'bluesky' => 'Bluesky',
        'mastodon' => 'Mastodon', // will require instance + username; skip if incomplete
        'vsco' => 'VSCO',
        'substack' => 'Substack',
    );

    acf_add_local_field_group(array(
        'key' => 'group_hrphoto_social_icons',
        'title' => 'Selected Social Icons',
        'fields' => array(
            array(
                'key' => 'field_hrphoto_social_icons_selected',
                'label' => 'Select icons (max 6)',
                'name' => 'hrphoto_social_icons_selected',
                'type' => 'checkbox',
                'choices' => $platform_choices,
                'return_format' => 'value',
                'layout' => 'vertical',
            ),
            array(
                'key' => 'field_hrphoto_social_icons_order',
                'label' => 'Order (optional)',
                'name' => 'hrphoto_social_icons_order',
                'type' => 'text',
                'instructions' => 'Comma-separated order, e.g. instagram,x,youtube',
                'placeholder' => 'instagram,x,youtube',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'user_form',
                    'operator' => '==',
                    'value' => 'edit',
                ),
            ),
        ),
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
        'show_in_rest' => 0,
    ));
});

/**
 * Shortcode: [social_icons user="me|ID|slug" size="24"]
 */
add_shortcode('social_icons', function($atts){
    $atts = shortcode_atts(array(
        'user' => '',
        'size' => '24',
    ), $atts, 'social_icons');

    $size = max(12, (int) $atts['size']);

    // Resolve user ID
    $user_param = trim((string) $atts['user']);
    $user_id = 0;
    if ($user_param === '' && is_author()) { $user_id = (int) get_queried_object_id(); }
    if ($user_id <= 0 && $user_param === 'me' && is_user_logged_in()) { $user_id = get_current_user_id(); }
    if ($user_id <= 0 && is_numeric($user_param)) { $user_id = (int) $user_param; }
    if ($user_id <= 0 && $user_param !== '' && $user_param !== 'me') {
        $u = get_user_by('slug', $user_param); if (!$u) { $u = get_user_by('login', $user_param); }
        if ($u instanceof WP_User) { $user_id = (int) $u->ID; }
    }
    if ($user_id <= 0) { global $post; if (!empty($post) && !empty($post->post_author)) { $user_id = (int) $post->post_author; } }
    if ($user_id <= 0) { return ''; }

    // Map platform -> username field + URL builder (define BEFORE selection logic)
    $get = function($key) use ($user_id){
        $try = array(
            // ACF subfield stored directly by name
            $key,
            // ACF group prefix as seen in user_meta dump
            'social_profiles_' . $key,
        );
        foreach ($try as $name) {
            if (function_exists('get_field')) {
                $v = get_field($name, 'user_' . (int) $user_id);
                if (is_string($v) && $v !== '') { return trim($v); }
            }
            if (function_exists('get_user_meta')) {
                $v = get_user_meta((int) $user_id, $name, true);
                if (is_string($v) && $v !== '') { return trim($v); }
            }
        }
        // Fallback: read entire group array and pick subkey
        if (function_exists('get_field')) {
            $grp = get_field('social_profiles', 'user_' . (int) $user_id);
            if (is_array($grp) && isset($grp[$key]) && is_string($grp[$key]) && $grp[$key] !== '') {
                return trim($grp[$key]);
            }
        }
        return '';
    };
    $platforms = array(
        'instagram' => function() use ($get){ $u = $get('instagram'); return $u ? 'https://instagram.com/' . rawurlencode($u) : ''; },
        'threads'   => function() use ($get){ $u = $get('threads'); return $u ? 'https://www.threads.net/@' . rawurlencode($u) : ''; },
        'x'         => function() use ($get){ $u = $get('x'); return $u ? 'https://x.com/' . rawurlencode($u) : ''; },
        'facebook'  => function() use ($get){ $u = $get('facebook'); return $u ? 'https://facebook.com/' . rawurlencode($u) : ''; },
        'tiktok'    => function() use ($get){ $u = $get('tiktok'); return $u ? 'https://www.tiktok.com/@' . rawurlencode($u) : ''; },
        'youtube'   => function() use ($get){ $u = $get('youtube'); return $u ? 'https://youtube.com/' . rawurlencode($u) : ''; },
        'vimeo'     => function() use ($get){ $u = $get('vimeo'); return $u ? 'https://vimeo.com/' . rawurlencode($u) : ''; },
        'flickr'    => function() use ($get){ $u = $get('flickr'); return $u ? 'https://flickr.com/people/' . rawurlencode($u) : ''; },
        'fivehundredpx' => function() use ($get){ $u = $get('fivehundredpx'); return $u ? 'https://500px.com/' . rawurlencode($u) : ''; },
        'pinterest' => function() use ($get){ $u = $get('pinterest'); return $u ? 'https://www.pinterest.com/' . rawurlencode($u) : ''; },
        'reddit'    => function() use ($get){ $u = $get('reddit'); return $u ? 'https://www.reddit.com/user/' . rawurlencode($u) : ''; },
        'linkedin'  => function() use ($get){ $u = $get('linkedin'); return $u ? 'https://www.linkedin.com/in/' . rawurlencode($u) : ''; },
        'behance'   => function() use ($get){ $u = $get('behance'); return $u ? 'https://www.behance.net/' . rawurlencode($u) : ''; },
        'dribbble'  => function() use ($get){ $u = $get('dribbble'); return $u ? 'https://dribbble.com/' . rawurlencode($u) : ''; },
        'deviantart'=> function() use ($get){ $u = $get('deviantart'); return $u ? 'https://www.deviantart.com/' . rawurlencode($u) : ''; },
        'tumblr'    => function() use ($get){ $u = $get('tumblr'); return $u ? 'https://' . rawurlencode($u) . '.tumblr.com/' : ''; },
        'bluesky'   => function() use ($get){ $u = $get('bluesky'); return $u ? 'https://bsky.app/profile/' . rawurlencode($u) : ''; },
        'mastodon'  => function() use ($get){ $inst = $get('mastodon_instance'); $u = $get('mastodon'); return ($inst && $u) ? 'https://' . rawurlencode($inst) . '/@' . rawurlencode($u) : ''; },
        'vsco'      => function() use ($get){ $u = $get('vsco'); return $u ? 'https://vsco.co/' . rawurlencode($u) : ''; },
        'substack'  => function() use ($get){ $u = $get('substack'); return $u ? 'https://' . rawurlencode($u) . '.substack.com/' : ''; },
    );

    // Read selected platforms CSV (from social modal). If empty, auto-detect.
    $selected_csv = (string) get_user_meta($user_id, 'hrphoto_social_selected', true);
    $selected = array_filter(array_map('trim', explode(',', strtolower($selected_csv))));
    $order_str = $selected_csv; // selection already encodes order
    $order = array();
    if ($order_str !== '') {
        $order = array_filter(array_map('trim', explode(',', strtolower($order_str))));
    }
    // Build map of available URLs first (robust auto-detect from stored values)
    $available = array();
    foreach (array_keys($platforms) as $k) {
        $url = (string) call_user_func($platforms[$k]);
        if ($url !== '') { $available[$k] = $url; }
    }
    if (empty($available)) { return ''; }

    // Apply saved order/selection if present, else take first 6 available in defined order
    $selected_lc = array_map('strtolower', $selected);
    $final = array();
    if (!empty($selected_lc)) {
        // Respect explicit order from CSV, then top-up with remaining available until 9
        foreach ($selected_lc as $k) {
            if (isset($available[$k]) && !in_array($k, $final, true)) { $final[] = $k; if (count($final) >= 9) break; }
        }
        if (count($final) < 9) {
            foreach (array_keys($available) as $k) {
                if (!in_array($k, $final, true)) { $final[] = $k; if (count($final) >= 9) break; }
            }
        }
    }
    if (empty($final)) {
        foreach (array_keys($available) as $k) { $final[] = $k; if (count($final) >= 9) break; }
    }


    // Minimal inline SVGs (generic link glyph; one style for all to keep code tidy)
    $generic_svg = function($sz) {
        $w = (int) $sz; $h = (int) $sz;
        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $h . '" viewBox="0 0 24 24" aria-hidden="true"><path fill="#000" d="M10.586 13.414a2 2 0 0 1 0-2.828l4-4a2 2 0 1 1 2.828 2.828l-1.172 1.172a1 1 0 0 1-1.414-1.414l1.172-1.172-2.828-2.828-4 4 2.828 2.828zm2.828-2.828a2 2 0 0 1 0 2.828l-4 4a2 2 0 1 1-2.828-2.828l1.172-1.172a1 1 0 0 1 1.414 1.414L7 16l2.828 2.828 4-4-2.828-2.828z"/></svg>';
    };

    // Build output (40x40, dark background, white glyph)
    $out = array();
    foreach ($final as $key) {
        if (!isset($platforms[$key])) continue;
        $url = (string) call_user_func($platforms[$key]);
        if ($url === '') continue;
        $icon_path = get_stylesheet_directory() . '/assets/icons/social/' . $key . '.svg';
        $svg = '';
        if (file_exists($icon_path)) {
            $svg = file_get_contents($icon_path);
            // Minify to avoid wpautop inserting stray nodes
            $svg = str_replace(array("\n","\r","\t"), '', $svg);
        } else {
            // Fallback generic link glyph
            $svg = $generic_svg(24);
        }
        $out[] = '<a class="social-icon social-icon--' . esc_attr($key) . '" href="' . esc_url($url) . '" target="_blank" rel="noopener" aria-label="' . esc_attr(ucfirst($key)) . '">' . $svg . '</a>';
    }

    if (empty($out)) {
        // Temporary visible debug: user id and instagram key reads
        $ig_direct = function_exists('get_field') ? (string) get_field('instagram', 'user_' . (int) $user_id) : '';
        $ig_pref   = function_exists('get_user_meta') ? (string) get_user_meta((int) $user_id, 'social_profiles_instagram', true) : '';
        $ig_group  = '';
        if (function_exists('get_field')) {
            $grp = get_field('social_profiles', 'user_' . (int) $user_id);
            if (is_array($grp) && isset($grp['instagram'])) { $ig_group = (string) $grp['instagram']; }
        }
        return '<div class="social-icons-debug">user=' . (int) $user_id . ' | instagram=' . esc_html($ig_direct) . ' | social_profiles_instagram=' . esc_html($ig_pref) . ' | group.instagram=' . esc_html($ig_group) . '</div>';
    }
    return '<div class="social-icons" data-social-icons="1" data-user-id="' . (int) $user_id . '">' . implode('', $out) . '</div>';
});

// AJAX: render social icons HTML for live refresh
add_action('wp_ajax_render_social_icons', function(){
    $uid = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    if ($uid <= 0) { $uid = is_user_logged_in() ? get_current_user_id() : 0; }
    // Reuse shortcode rendering with explicit user param
    $html = do_shortcode('[social_icons user="' . ($uid>0 ? $uid : '') . '"]');
    wp_send_json_success(array('html' => $html));
});


