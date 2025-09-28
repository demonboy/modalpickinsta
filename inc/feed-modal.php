<?php
/**
 * Feed Settings Modal: AJAX endpoints
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Render feed settings modal
add_action('wp_ajax_nopriv_get_feed_modal', 'hrphoto_get_feed_modal');
add_action('wp_ajax_get_feed_modal', 'hrphoto_get_feed_modal');
function hrphoto_get_feed_modal() {
    // Provide category data and current defaults to the template
    $defaults = array(
        'u_scope'       => 'everyone',
        'likes_order'   => '',
        'date_order'    => 'latest',
        'cats'          => array(),
        'cats_exclude'  => array(),
        'ptype'         => '',
        'ptype_enabled' => false,
    );
    if ( is_user_logged_in() ) {
        $uid = get_current_user_id();
        $saved_mode = (string) get_user_meta( $uid, 'hrphoto_feed_mode', true ); // legacy
        $saved_cats = (array) get_user_meta( $uid, 'hrphoto_feed_view_cats', true );
        $saved_ex   = (array) get_user_meta( $uid, 'hrphoto_feed_exclude_cats', true );
        $saved_pt   = (string) get_user_meta( $uid, 'hrphoto_feed_view_ptype', true );
        $saved_pt_on= (int) get_user_meta( $uid, 'hrphoto_feed_ptype_enabled', true );
        $saved_u    = (string) get_user_meta( $uid, 'hrphoto_feed_u_scope', true );
        $saved_l    = (string) get_user_meta( $uid, 'hrphoto_feed_likes', true );
        $saved_d    = (string) get_user_meta( $uid, 'hrphoto_feed_date', true );
        if ( $saved_cats ) { $defaults['cats'] = array_map( 'intval', $saved_cats ); }
        if ( $saved_ex )   { $defaults['cats_exclude'] = array_map( 'intval', $saved_ex ); }
        if ( in_array( $saved_pt, array('1hrphoto','story'), true ) ) { $defaults['ptype'] = $saved_pt; }
        $defaults['ptype_enabled'] = $saved_pt_on ? true : false;
        if ( in_array( $saved_u, array('everyone','following','not_following'), true ) ) { $defaults['u_scope'] = $saved_u; }
        if ( in_array( $saved_l, array('most','least'), true ) ) { $defaults['likes_order'] = $saved_l; }
        if ( in_array( $saved_d, array('latest','oldest','random'), true ) ) { $defaults['date_order'] = $saved_d; }
        // Map legacy simple modes to defaults if present
        if ( $saved_mode === 'following' ) { $defaults['u_scope'] = 'following'; }
        if ( $saved_mode === 'following_first' ) { $defaults['u_scope'] = 'everyone'; $defaults['date_order'] = 'latest'; }
    }

    // Categories list (id + name)
    $cats = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) );
    $cat_list = array();
    if ( ! is_wp_error( $cats ) ) {
        foreach ( $cats as $t ) {
            $cat_list[] = array( 'id' => (int) $t->term_id, 'name' => (string) $t->name );
        }
    }

    ob_start();
    $tpl = get_stylesheet_directory() . '/templates/feed-modal.php';
    if ( file_exists( $tpl ) ) {
        // Make data available
        $FEED_DEFAULTS = $defaults;
        $FEED_CATEGORIES = $cat_list;
        include $tpl;
    } else {
        echo '<div class="error">Template missing.</div>';
    }
    $html = ob_get_clean();
    wp_send_json_success( array( 'html' => $html, 'defaults' => $defaults, 'categories' => $cat_list ) );
}

// Save user defaults (mode + viewing categories + post type)
add_action('wp_ajax_save_feed_defaults', 'hrphoto_save_feed_defaults');
function hrphoto_save_feed_defaults() {
    if ( ! is_user_logged_in() ) { wp_send_json_error( array( 'message' => 'Login required' ), 401 ); }
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ajax_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Bad nonce' ), 403 );
    }
    $user_id = get_current_user_id();
    $mode    = isset($_POST['mode']) ? sanitize_key( wp_unslash($_POST['mode']) ) : '';
    $u       = isset($_POST['u_scope']) ? sanitize_key( wp_unslash($_POST['u_scope']) ) : '';
    $likes   = isset($_POST['likes']) ? sanitize_key( wp_unslash($_POST['likes']) ) : '';
    $date    = isset($_POST['date']) ? sanitize_key( wp_unslash($_POST['date']) ) : '';
    $cats    = isset($_POST['cats']) ? (string) $_POST['cats'] : ( isset($_POST['cat_ids']) ? (string) $_POST['cat_ids'] : '' );
    $catsEx  = isset($_POST['cats_exclude']) ? (string) $_POST['cats_exclude'] : '';
    $ptype   = isset($_POST['ptype']) ? sanitize_key( wp_unslash($_POST['ptype']) ) : '';

    // Persist
    update_user_meta( $user_id, 'hrphoto_feed_mode', $mode );
    $cat_ids = $cats !== '' ? array_filter( array_map( 'intval', explode( ',', $cats ) ) ) : array();
    update_user_meta( $user_id, 'hrphoto_feed_view_cats', $cat_ids );
    $cat_ex_ids = $catsEx !== '' ? array_filter( array_map( 'intval', explode( ',', $catsEx ) ) ) : array();
    update_user_meta( $user_id, 'hrphoto_feed_exclude_cats', $cat_ex_ids );
    if ( in_array( $ptype, array('1hrphoto','story'), true ) ) {
        update_user_meta( $user_id, 'hrphoto_feed_view_ptype', $ptype );
        update_user_meta( $user_id, 'hrphoto_feed_ptype_enabled', 1 );
    } else {
        delete_user_meta( $user_id, 'hrphoto_feed_view_ptype' );
        update_user_meta( $user_id, 'hrphoto_feed_ptype_enabled', 0 );
    }
    if ( in_array( $u, array('everyone','following','not_following'), true ) ) {
        update_user_meta( $user_id, 'hrphoto_feed_u_scope', $u );
    }
    if ( in_array( $likes, array('most','least'), true ) ) {
        update_user_meta( $user_id, 'hrphoto_feed_likes', $likes );
    } else { delete_user_meta( $user_id, 'hrphoto_feed_likes' ); }
    if ( in_array( $date, array('latest','oldest','random'), true ) ) {
        update_user_meta( $user_id, 'hrphoto_feed_date', $date );
    }

    wp_send_json_success( array( 'saved' => true ) );
}


