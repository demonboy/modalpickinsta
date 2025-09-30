<?php


function hrphoto_enqueue_styles() {
    // Parent theme style with version
    wp_enqueue_style(
        'twentytwentyfour-style',
        get_template_directory_uri() . '/style.css',
        array(),
        filemtime(get_template_directory() . '/style.css')
    );

    // Child theme style with version
    wp_enqueue_style(
        '1hrphoto-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('twentytwentyfour-style'),
        filemtime(get_stylesheet_directory() . '/style.css')
    );

    // Modal style with version
    wp_enqueue_style(
        'modal-style',
        get_stylesheet_directory_uri() . '/assets/css/modal.css',
        array('1hrphoto-style'),
        filemtime(get_stylesheet_directory() . '/assets/css/modal.css')
    );

    // Post creation modal style with version
    wp_enqueue_style(
        'post-creation-modal-style',
        get_stylesheet_directory_uri() . '/assets/css/post-creation-modal.css',
        array('modal-style'),
        filemtime(get_stylesheet_directory() . '/assets/css/post-creation-modal.css')
    );

    // Register profile shortcodes CSS (enqueued on demand by shortcode)
    if (!wp_style_is('profile-shortcodes-css', 'registered')) {
        wp_register_style(
            'profile-shortcodes-css',
            get_stylesheet_directory_uri() . '/assets/css/profile-shortcodes.css',
            array('1hrphoto-style'),
            filemtime(get_stylesheet_directory() . '/assets/css/profile-shortcodes.css')
        );
    }

	// Register author profile URL CSS (loaded on author pages and by shortcode)
	if (!wp_style_is('author-profile-url-css', 'registered')) {
		wp_register_style(
			'author-profile-url-css',
			get_stylesheet_directory_uri() . '/assets/css/author-profile-url.css',
			array('1hrphoto-style'),
			filemtime(get_stylesheet_directory() . '/assets/css/author-profile-url.css')
		);
	}
}
add_action('wp_enqueue_scripts', 'hrphoto_enqueue_styles');


//BOTTOM MENU
function hrphoto_register_patterns() {
    register_block_pattern(
        '1hrphoto/bottom-menu',
        [
            'title'       => __('Floating Bottom Menu', '1hrphoto'),
            'description' => __('A four-icon floating bottom menu', '1hrphoto'),
            'content'     => file_get_contents(get_stylesheet_directory() . '/patterns/bottom-menu.json'),
        ]
    );
}
add_action('init', 'hrphoto_register_patterns');



//MODAL
function enqueue_modal_js() {
    wp_enqueue_script(
        'modal-js',
        get_stylesheet_directory_uri() . '/assets/js/universal-modal.js',
        array(),
        filemtime(get_stylesheet_directory() . '/assets/js/universal-modal.js'),
        true
    );
    
    wp_enqueue_script(
        'comments-modal-js',
        get_stylesheet_directory_uri() . '/assets/js/comments-modal.js',
        array('modal-js'),
        filemtime(get_stylesheet_directory() . '/assets/js/comments-modal.js'),
        true
    );
    
    // Pass user role info to comments modal JS
    wp_localize_script('comments-modal-js', 'comments_modal_data', array(
        'is_admin' => current_user_can('moderate_comments')
    ));
    
    // Keep global WordPress media UI disabled to avoid large template payload
    // wp_enqueue_media();
    // Re-enable ACF global enqueue to ensure field UIs (e.g., taxonomy selects) initialize reliably
    if (function_exists('acf_enqueue_scripts')) { acf_enqueue_scripts(); }
    
    wp_enqueue_script(
        'post-creation-modal-js',
        get_stylesheet_directory_uri() . '/assets/js/post-creation-modal.js',
        array('modal-js', 'acf-input'),
        filemtime(get_stylesheet_directory() . '/assets/js/post-creation-modal.js'),
        true
    );
    // Ensure ACF/Select2 CSS present for ACF fields in Post Creation modal
    if ( wp_style_is('acf-input', 'registered') ) { wp_enqueue_style('acf-input'); }
    if ( wp_style_is('select2', 'registered') ) { wp_enqueue_style('select2'); }
    
    // Story modal assets
    wp_enqueue_script(
        'story-modal-js',
        get_stylesheet_directory_uri() . '/assets/js/story-modal.js',
        array('modal-js'),
        filemtime(get_stylesheet_directory() . '/assets/js/story-modal.js'),
        true
    );
    wp_enqueue_style(
        'story-modal-css',
        get_stylesheet_directory_uri() . '/assets/css/story-modal.css',
        array('modal-style'),
        filemtime(get_stylesheet_directory() . '/assets/css/story-modal.css')
    );

    // Profile modal assets
    wp_enqueue_script(
        'profile-modal-js',
        get_stylesheet_directory_uri() . '/assets/js/profile-modal.js',
        array('modal-js', 'acf-input'),
        filemtime(get_stylesheet_directory() . '/assets/js/profile-modal.js'),
        true
    );
    wp_enqueue_script(
        'avatar-modal-js',
        get_stylesheet_directory_uri() . '/assets/js/avatar-modal.js',
        array('modal-js'),
        filemtime(get_stylesheet_directory() . '/assets/js/avatar-modal.js'),
        true
    );
    wp_enqueue_style(
        'profile-modal-css',
        get_stylesheet_directory_uri() . '/assets/css/profile-modal.css',
        array('modal-style'),
        filemtime(get_stylesheet_directory() . '/assets/css/profile-modal.css')
    );
    // Ensure ACF/Select2 CSS present for ACF fields in Profile modal
    if ( wp_style_is('acf-input', 'registered') ) { wp_enqueue_style('acf-input'); }
    if ( wp_style_is('select2', 'registered') ) { wp_enqueue_style('select2'); }
    // Follow modal assets
    wp_enqueue_script(
        'follow-modal-js',
        get_stylesheet_directory_uri() . '/assets/js/follow-modal.js',
        array('modal-js'),
        filemtime(get_stylesheet_directory() . '/assets/js/follow-modal.js'),
        true
    );
    wp_enqueue_style(
        'follow-modal-css',
        get_stylesheet_directory_uri() . '/assets/css/follow-modal.css',
        array('modal-style'),
        filemtime(get_stylesheet_directory() . '/assets/css/follow-modal.css')
    );
    wp_enqueue_style(
        'avatar-modal-css',
        get_stylesheet_directory_uri() . '/assets/css/avatar-modal.css',
        array('modal-style'),
        filemtime(get_stylesheet_directory() . '/assets/css/avatar-modal.css')
    );

    // Social modal assets
    wp_enqueue_script(
        'social-modal-js',
        get_stylesheet_directory_uri() . '/assets/js/social-modal.js',
        array('modal-js', 'acf-input'),
        filemtime(get_stylesheet_directory() . '/assets/js/social-modal.js'),
        true
    );
    // Minimal CSS to stabilize icon rows during live updates (prevent bounce)
    wp_enqueue_style(
        'social-modal-css',
        get_stylesheet_directory_uri() . '/assets/css/social-modal.css',
        array('modal-style'),
        filemtime(get_stylesheet_directory() . '/assets/css/social-modal.css')
    );
    // Ensure ACF/Select2 CSS present for ACF fields in Social modal
    if ( wp_style_is('acf-input', 'registered') ) { wp_enqueue_style('acf-input'); }
    if ( wp_style_is('select2', 'registered') ) { wp_enqueue_style('select2'); }
    
    // Add AJAX nonce for authentication
    wp_localize_script('modal-js', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax_nonce'),
        'home_url' => home_url('/')
    ));

    // Ensure post-creation script also has localized ajax settings
    wp_localize_script('post-creation-modal-js', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax_nonce'),
        'home_url' => home_url('/')
    ));
    wp_localize_script('story-modal-js', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax_nonce'),
        'home_url' => home_url('/')
    ));
    wp_localize_script('profile-modal-js', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax_nonce'),
        'home_url' => home_url('/'),
        'current_user_id' => get_current_user_id(),
        'current_user_nicename' => ( is_user_logged_in() ? wp_get_current_user()->user_nicename : '' ),
    ));
    wp_localize_script('social-modal-js', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax_nonce'),
        'home_url' => home_url('/'),
        'current_user_id' => get_current_user_id(),
        'theme_uri' => get_stylesheet_directory_uri(),
    ));
    wp_localize_script('follow-modal-js', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax_nonce'),
        'home_url' => home_url('/'),
        'current_user_id' => get_current_user_id(),
    ));

    // Share modal (standalone floating modal)
    wp_enqueue_script(
        'onehr-share-modal',
        get_stylesheet_directory_uri() . '/assets/js/share-modal.js',
        array(),
        filemtime(get_stylesheet_directory() . '/assets/js/share-modal.js'),
        true
    );
    wp_enqueue_style(
        'onehr-share-modal',
        get_stylesheet_directory_uri() . '/assets/css/share-modal.css',
        array('1hrphoto-style'),
        filemtime(get_stylesheet_directory() . '/assets/css/share-modal.css')
    );
    wp_localize_script('onehr-share-modal', 'ONEHR_SHARE', array(
        'home_url' => home_url('/'),
        'strings' => array(
            'share' => __('Share', '1hrphoto'),
            'copy' => __('Copy', '1hrphoto'),
            'copied' => __('Copied', '1hrphoto'),
            'close' => __('Close', '1hrphoto'),
        ),
    ));

    // Feed settings modal assets
    wp_enqueue_script(
        'feed-modal-js',
        get_stylesheet_directory_uri() . '/assets/js/feed-modal.js',
        array('modal-js'),
        filemtime(get_stylesheet_directory() . '/assets/js/feed-modal.js'),
        true
    );
    wp_enqueue_style(
        'feed-modal-css',
        get_stylesheet_directory_uri() . '/assets/css/feed-modal.css',
        array('modal-style'),
        filemtime(get_stylesheet_directory() . '/assets/css/feed-modal.css')
    );
    wp_localize_script('feed-modal-js', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax_nonce'),
        'home_url' => home_url('/'),
        'current_user_id' => get_current_user_id(),
        'theme_uri' => get_stylesheet_directory_uri(),
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_modal_js');

//POST LIKES
function enqueue_post_likes_js() {
    wp_enqueue_script(
        'post-likes-js',
        get_stylesheet_directory_uri() . '/assets/js/post-likes.js',
        array(),
        filemtime(get_stylesheet_directory() . '/assets/js/post-likes.js'),
        true
    );
    
    // Add AJAX nonce for authentication
    wp_localize_script('post-likes-js', 'post_likes_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_post_likes_js');

// Query Loop Infinite Scroll (progressive enhancement; no template edits)
add_action('wp_enqueue_scripts', function(){
    if (is_home() || is_archive() || is_search() || is_author()) {
        wp_enqueue_script(
            'infinite-scroll-queryloop-js',
            get_stylesheet_directory_uri() . '/assets/js/infinite-scroll-queryloop.js',
            array(),
            filemtime(get_stylesheet_directory() . '/assets/js/infinite-scroll-queryloop.js'),
            true
        );
    }
});

// Follow list layout
add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style(
        'follow-css',
        get_stylesheet_directory_uri() . '/assets/css/follow.css',
        array('1hrphoto-style'),
        filemtime(get_stylesheet_directory() . '/assets/css/follow.css')
    );
});

// Social icons minimal layout
add_action('wp_enqueue_scripts', function(){
    wp_add_inline_style('1hrphoto-style', '.social-icons{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap}.social-links{display:flex;gap:.5rem;flex-wrap:wrap}.social-icon,.social-link{width:40px;height:40px;background:#333;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;color:#fff;font-size:12px;padding:0 6px}.social-icon svg{width:24px;height:24px;fill:#fff}.social-link{width:auto;height:auto;background:transparent;color:#0073aa}.social-reorder-heading{margin:12px 0 6px}.social-icons-active{display:flex;gap:.5rem;flex-wrap:nowrap;overflow-x:auto;padding-bottom:.25rem}.social-icons-inactive{display:flex;gap:.5rem;flex-wrap:wrap;opacity:.4;margin-top:.5rem}.social-icons-active .social-icon,.social-icons-inactive .social-icon{cursor:grab}');
});

// AUTH GUARD (site-wide, intercepts internal links for logged-out users)
add_action('wp_enqueue_scripts', function(){
    $is_logged_in = is_user_logged_in();
    wp_enqueue_script(
        'auth-guard-js',
        get_stylesheet_directory_uri() . '/assets/js/auth-guard.js',
        array('modal-js'),
        filemtime(get_stylesheet_directory() . '/assets/js/auth-guard.js'),
        true
    );
    wp_localize_script('auth-guard-js', 'AUTH_GUARD', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax_nonce'),
        'isLoggedIn' => $is_logged_in,
    ));
});

// HEADER CATEGORIES NAV (desktop arrows; tiny)
add_action('wp_enqueue_scripts', function(){
    wp_enqueue_script(
        'header-cats-nav-js',
        get_stylesheet_directory_uri() . '/assets/js/header-cats-nav.js',
        array(),
        filemtime(get_stylesheet_directory() . '/assets/js/header-cats-nav.js'),
        true
    );
});

// INFINITE SCROLL
function enqueue_infinite_scroll_assets() {
    // Only enqueue on pages that need infinite scroll
    if (is_home() || is_archive() || is_author()) {
        wp_enqueue_script(
            'infinite-scroll-js',
            get_stylesheet_directory_uri() . '/assets/js/infinite-scroll.js',
            array(),
            filemtime(get_stylesheet_directory() . '/assets/js/infinite-scroll.js'),
            true
        );
        
        wp_enqueue_style(
            'infinite-scroll-css',
            get_stylesheet_directory_uri() . '/assets/css/infinite-scroll.css',
            array('1hrphoto-style'),
            filemtime(get_stylesheet_directory() . '/assets/css/infinite-scroll.css')
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_infinite_scroll_assets');

// BACK TO TOP
function enqueue_back_to_top_assets() {
    // Enqueue on all pages that might have scrollable content
    if (is_home() || is_archive() || is_author() || is_search() || is_single() || is_page()) {
        wp_enqueue_script(
            'back-to-top-js',
            get_stylesheet_directory_uri() . '/assets/js/back-to-top.js',
            array(),
            filemtime(get_stylesheet_directory() . '/assets/js/back-to-top.js'),
            true
        );
        
        wp_enqueue_style(
            'back-to-top-css',
            get_stylesheet_directory_uri() . '/assets/css/back-to-top.css',
            array('1hrphoto-style'),
            filemtime(get_stylesheet_directory() . '/assets/css/back-to-top.css')
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_back_to_top_assets');

// ARCHIVE SEARCH TOGGLE (magnifier → slide-out form)
add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style(
        'search-toggle-css',
        get_stylesheet_directory_uri() . '/assets/css/search-toggle.css',
        array(),
        filemtime(get_stylesheet_directory() . '/assets/css/search-toggle.css')
    );
    wp_enqueue_script(
        'search-toggle-js',
        get_stylesheet_directory_uri() . '/assets/js/search-toggle.js',
        array(),
        filemtime(get_stylesheet_directory() . '/assets/js/search-toggle.js'),
        true
    );
});

// LATEST COMMENT (query loop display with real-time updates)
add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style(
        'latest-comment-css',
        get_stylesheet_directory_uri() . '/assets/css/latest-comment.css',
        array('1hrphoto-style'),
        filemtime(get_stylesheet_directory() . '/assets/css/latest-comment.css')
    );
    wp_enqueue_script(
        'latest-comment-js',
        get_stylesheet_directory_uri() . '/assets/js/latest-comment.js',
        array('post-likes-js'),
        filemtime(get_stylesheet_directory() . '/assets/js/latest-comment.js'),
        true
    );
});

// Enqueue author profile URL CSS only on author pages (lean)
add_action('wp_enqueue_scripts', function(){
	if (is_author()) {
		if (!wp_style_is('author-profile-url-css', 'enqueued')) {
			wp_enqueue_style('author-profile-url-css');
		}
	}
});

// Add captions to Featured Image block in Query Loop
function add_featured_image_caption( $block_content, $block ) {
    if ( $block['blockName'] === 'core/post-featured-image' && is_main_query() ) {
        global $post;
        $thumbnail_id = get_post_thumbnail_id( $post->ID );
        $caption = wp_get_attachment_caption( $thumbnail_id );

        if ( $caption ) {
            // Wrap image and caption in figure/figcaption
            $block_content = '<figure class="wp-block-post-featured-image-with-caption">'
                           . $block_content
                           . '<figcaption>' . esc_html( $caption ) . '</figcaption>'
                           . '</figure>';
        }
    }
    return $block_content;
}
add_filter( 'render_block', 'add_featured_image_caption', 10, 2 );

//PROFILE EDITOR
function my_enqueue_profile_editor_assets() {
    // JS
    wp_enqueue_script(
        'profile-editor-js',
        get_stylesheet_directory_uri() . '/assets/js/profile-editor.js',
        ['jquery'],
        null,
        true
    );

    wp_localize_script('profile-editor-js', 'profileEditorAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('acf_frontend_profile_nonce'),
    ]);

    // CSS
    wp_enqueue_style(
        'profile-editor-css',
        get_stylesheet_directory_uri() . '/assets/css/profile-editor.css',
        [],
        null
    );
}
add_action('wp_enqueue_scripts', 'my_enqueue_profile_editor_assets');

