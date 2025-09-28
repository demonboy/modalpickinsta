<?php
/**
 * Profile Modal (public view + scaffolding for management sections)
 */

// AJAX: Public profile modal (nopriv + priv)
add_action('wp_ajax_get_profile_modal', 'hrphoto_get_profile_modal');
add_action('wp_ajax_nopriv_get_profile_modal', 'hrphoto_get_profile_modal');

function hrphoto_get_profile_modal() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'), 403);
    }

    $slug = isset($_POST['user']) ? sanitize_title_for_query((string) $_POST['user']) : '';
    $user = null;
    if ($slug === 'me' && is_user_logged_in()) {
        $user = wp_get_current_user();
    } elseif ($slug !== '') {
        $user = get_user_by('slug', $slug);
    }
    // Fallback: if no slug, but logged in, show current user; else 404-ish view
    if (!$user && is_user_logged_in()) {
        $user = wp_get_current_user();
    }

    ob_start();
    include get_stylesheet_directory() . '/templates/profile/profile-view.php';
    $html = ob_get_clean();

    wp_send_json_success(array('html' => $html));
}

// AJAX: Load profile sections (Gear, etc.)
add_action('wp_ajax_get_profile_section', 'hrphoto_get_profile_section');
add_action('wp_ajax_nopriv_get_profile_section', 'hrphoto_get_profile_section');
function hrphoto_get_profile_section() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'), 403);
    }
    $section = isset($_POST['section']) ? sanitize_key($_POST['section']) : '';
    $slug = isset($_POST['user']) ? sanitize_title_for_query((string) $_POST['user']) : '';
    $user = null;
    if ($slug === 'me' && is_user_logged_in()) { $user = wp_get_current_user(); }
    elseif ($slug !== '') { $user = get_user_by('slug', $slug); }
    if (!$user) { wp_send_json_error(array('message' => 'User not found'), 404); }

    ob_start();
    if ($section === 'gear') {
        // Render our custom ACF profile editor template (AJAX save)
        $group_key = 'group_68bfd6c849f61';
        include get_stylesheet_directory() . '/templates/profile-editor-form.php';
    } elseif ($section === 'profile') {
        // Profile details editor (AJAX save)
        $group_key = 'group_68c5645993692';
        include get_stylesheet_directory() . '/templates/profile-editor-form.php';
    } else {
        echo '<div class="loading">Section coming soon…</div>';
    }
    $html = ob_get_clean();
    wp_send_json_success(array('html' => $html));
}

// AUTH VIEW (Login/Register placeholder)
add_action('wp_ajax_get_auth_modal', 'hrphoto_get_auth_modal');
add_action('wp_ajax_nopriv_get_auth_modal', 'hrphoto_get_auth_modal');
function hrphoto_get_auth_modal() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'), 403);
    }
    ob_start(); ?>
    <div class="auth-modal-content">
      <h2>Login/Register</h2>
      <p>Welcome back. Log in to create 1hrphoto posts and stories, manage your profile, and more.</p>
      <form class="auth-login-form" method="post" action="#">
        <label>Username or Email<input type="text" name="log" required></label>
        <label>Password<input type="password" name="pwd" required></label>
        <button type="submit" class="button">Log In</button>
        <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce('ajax_nonce') ); ?>">
      </form>
      <div class="auth-secondary">
        <a href="#" class="auth-forgot">Forgot password?</a>
        <span class="auth-register" style="display:none;">Register</span>
      </div>
    </div>
    <?php
    $html = ob_get_clean();
    wp_send_json_success(array('html' => $html));
}

add_action('wp_ajax_nopriv_auth_login', 'hrphoto_auth_login');
function hrphoto_auth_login() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'), 403);
    }
    $creds = array(
        'user_login' => isset($_POST['log']) ? sanitize_text_field($_POST['log']) : '',
        'user_password' => isset($_POST['pwd']) ? (string) $_POST['pwd'] : '',
        'remember' => true,
    );
    $user = wp_signon($creds, false);
    if (is_wp_error($user)) {
        wp_send_json_error(array('message' => $user->get_error_message()), 401);
    }
    wp_send_json_success(array('redirect' => home_url('/')));
}

// Ensure logging out from anywhere redirects to the public homepage
add_action('wp_logout', function(){
    wp_safe_redirect( home_url('/') );
    exit;
});


