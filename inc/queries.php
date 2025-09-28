<?php
/**
 * Query modifications for main loops
 * - Includes custom post types in the home/blog main query when using Query Loop with inherit
 */

/**
 * Include CPTs in main home query
 *
 * @param WP_Query $query
 * @return void
 */
function hrphoto_include_cpts_in_blog( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    // Home/blog posts index
    if ( $query->is_home() ) {
        $query->set( 'post_type', array( 'post', '1hrphoto', 'story' ) );
    }

    // Taxonomy archives (Categories/Tags): include CPTs attached to these taxonomies
    if ( $query->is_category() || $query->is_tag() ) {
        $query->set( 'post_type', array( 'post', '1hrphoto', 'story' ) );
    }

    // Optional: broaden search and author archives to include CPTs
    if ( $query->is_search() || $query->is_author() ) {
        $query->set( 'post_type', array( 'post', '1hrphoto', 'story' ) );
    }

    // When searching from a term archive, keep results scoped to that term
    // Search scoping via submitted hidden fields (works across templates)
    if ( $query->is_search() ) {
        $tax_scope = isset($_GET['tax_scope']) ? sanitize_text_field( wp_unslash( $_GET['tax_scope'] ) ) : '';
        $term_id   = isset($_GET['term_id']) ? (int) $_GET['term_id'] : 0;
        if ( $tax_scope && $term_id ) {
            $tax_query = array(
                array(
                    'taxonomy' => $tax_scope,
                    'field'    => 'term_id',
                    'terms'    => $term_id,
                ),
            );
            $query->set( 'tax_query', $tax_query );
        }

        // If not scoped to a specific taxonomy, broaden search to include posts tagged with terms
        // whose names match the search phrase (WordPress tags, including CPTs using post_tag)
        if ( empty( $tax_scope ) ) {
            $search_term = trim( (string) $query->get( 's' ) );
            if ( $search_term !== '' ) {
                $tag_ids = get_terms( array(
                    'taxonomy'   => 'post_tag',
                    'search'     => $search_term, // match tag names
                    'fields'     => 'ids',
                    'hide_empty' => false,
                ) );
                if ( ! is_wp_error( $tag_ids ) && ! empty( $tag_ids ) ) {
                    $existing = $query->get( 'tax_query' );
                    if ( ! is_array( $existing ) ) { $existing = array(); }
                    // Ensure OR relation so matches in content OR tag name are included
                    if ( ! isset( $existing['relation'] ) ) { $existing['relation'] = 'OR'; }
                    $existing[] = array(
                        'taxonomy'         => 'post_tag',
                        'field'            => 'term_id',
                        'terms'            => array_map( 'intval', $tag_ids ),
                        'include_children' => false,
                        'operator'         => 'IN',
                    );
                    $query->set( 'tax_query', $existing );
                }
            }
        }
    }
}
add_action( 'pre_get_posts', 'hrphoto_include_cpts_in_blog' );


/**
 * Feed modes and ranking for My Feed and explicit view requests
 * - Home (is_home) remains Latest only
 * - Modes (via ?view=... or user default on My Feed page):
 *   latest, following, following_first, cats_only, cats_first,
 *   likes, likes_cat, likes_type
 */
if ( ! function_exists( 'hrphoto_get_following_ids' ) ) {
    /**
     * Get IDs of authors the user is following
     *
     * @param int $user_id
     * @return int[]
     */
    function hrphoto_get_following_ids( $user_id ) {
        global $wpdb;
        if ( $user_id <= 0 ) { return array(); }
        if ( ! function_exists( 'hrphoto_follows_table' ) ) { return array(); }
        $t = hrphoto_follows_table();
        $sql = $wpdb->prepare( "SELECT following_id FROM {$t} WHERE follower_id=%d", (int) $user_id );
        $ids = (array) $wpdb->get_col( $sql );
        return array_map( 'intval', $ids );
    }
}

if ( ! function_exists( 'hrphoto_apply_most_liked_order' ) ) {
    /**
     * Add ORDER BY clause to sort by most liked (post likes only), then newest
     * Scoped to the provided query instance
     *
     * @param WP_Query $target_query
     * @return void
     */
    function hrphoto_apply_most_liked_order( $target_query ) {
        add_filter( 'posts_clauses', function ( $clauses, $query ) use ( $target_query ) {
            if ( $query !== $target_query ) { return $clauses; }
            global $wpdb;
            $likes_table = $wpdb->prefix . 'postpic_likes';
            // Count only post-level likes (comment_id IS NULL)
            $sub = "(SELECT COUNT(*) FROM {$likes_table} l WHERE l.post_id={$wpdb->posts}.ID AND l.comment_id IS NULL)";
            $orderby = $sub . ' DESC, ' . ( $clauses['orderby'] ? $clauses['orderby'] : ( $wpdb->posts . '.post_date DESC' ) );
            $clauses['orderby'] = $orderby;
            return $clauses;
        }, 10, 2 );
    }
}

// Helper: block list (authors to exclude) for current viewer
if ( ! function_exists( 'hrphoto_get_blocklist_author_ids' ) ) {
    /**
     * Return unique user IDs who are blocked by or have blocked the viewer
     *
     * @param int $user_id
     * @return int[]
     */
    function hrphoto_get_blocklist_author_ids( $user_id ) {
        if ( $user_id <= 0 ) { return array(); }
        if ( ! function_exists( 'hrphoto_blocks_table' ) ) { return array(); }
        global $wpdb; $bt = hrphoto_blocks_table();
        $ids = array();
        // I blocked them
        $ids1 = (array) $wpdb->get_col( $wpdb->prepare( "SELECT blocked_id FROM {$bt} WHERE blocker_id=%d", (int) $user_id ) );
        // They blocked me
        $ids2 = (array) $wpdb->get_col( $wpdb->prepare( "SELECT blocker_id FROM {$bt} WHERE blocked_id=%d", (int) $user_id ) );
        foreach ( array_merge( $ids1, $ids2 ) as $id ) { $ids[(int) $id] = true; }
        return array_map( 'intval', array_keys( $ids ) );
    }
}

// New param-based feed logic (priority 19 so it runs before legacy view-based logic)
add_action( 'pre_get_posts', function( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) { return; }
    if ( $query->is_home() ) { return; }

    // Detect new params or My Feed without params
    $u_scope = isset($_GET['u_scope']) ? sanitize_key( wp_unslash($_GET['u_scope']) ) : '';
    $likes   = isset($_GET['likes']) ? sanitize_key( wp_unslash($_GET['likes']) ) : '';
    $date    = isset($_GET['date']) ? sanitize_key( wp_unslash($_GET['date']) ) : '';
    $cats_s  = isset($_GET['cats']) ? (string) $_GET['cats'] : '';
    $cats_ex = isset($_GET['cats_exclude']) ? (string) $_GET['cats_exclude'] : '';
    $ptype   = isset($_GET['ptype']) ? sanitize_key( wp_unslash($_GET['ptype']) ) : '';

    $has_any = ( $u_scope || $likes || $date || $cats_s || $cats_ex || $ptype );
    $is_my_feed = is_page( 'my-feed' );
    $current_user_id = get_current_user_id();

    if ( ! $has_any && ! ( $is_my_feed && is_user_logged_in() ) ) {
        return; // nothing to do; legacy handler may still apply if view=...
    }

    // Defaults
    if ( $u_scope === '' ) { $u_scope = 'everyone'; }
    if ( $date === '' ) { $date = 'latest'; }

    // Apply saved basics on My Feed when params absent
    if ( $is_my_feed && is_user_logged_in() && ! $has_any ) {
        $saved_cats = (array) get_user_meta( $current_user_id, 'hrphoto_feed_view_cats', true );
        if ( $saved_cats ) { $cats_s = implode( ',', array_map( 'intval', $saved_cats ) ); }
        $saved_pt = (string) get_user_meta( $current_user_id, 'hrphoto_feed_view_ptype', true );
        if ( in_array( $saved_pt, array('1hrphoto','story'), true ) ) { $ptype = $saved_pt; }
    }

    // Filters: author scope
    if ( $u_scope === 'following' ) {
        if ( ! is_user_logged_in() ) { $query->set( 'author__in', array(0) ); return; }
        $ids = hrphoto_get_following_ids( $current_user_id );
        $query->set( 'author__in', ! empty( $ids ) ? $ids : array(0) );
    } elseif ( $u_scope === 'not_following' ) {
        if ( is_user_logged_in() ) {
            $ids = hrphoto_get_following_ids( $current_user_id );
            $not_in = ! empty( $ids ) ? $ids : array();
            $not_in[] = (int) $current_user_id; // exclude self
            $query->set( 'author__not_in', array_map( 'intval', $not_in ) );
        }
    }

    // Always exclude blocked authors for logged-in viewers
    if ( is_user_logged_in() ) {
        $blocked = hrphoto_get_blocklist_author_ids( $current_user_id );
        if ( ! empty( $blocked ) ) {
            $existing = $query->get( 'author__not_in' );
            $existing = is_array( $existing ) ? $existing : array();
            $query->set( 'author__not_in', array_values( array_unique( array_map( 'intval', array_merge( $existing, $blocked ) ) ) ) );
        }
    }

    // Filters: post type (only 1hrphoto/story). If neither, show both.
    if ( in_array( $ptype, array('1hrphoto','story'), true ) ) {
        $query->set( 'post_type', $ptype );
    } else {
        $query->set( 'post_type', array( '1hrphoto', 'story' ) );
    }

    // Filters: category exclusions
    $cats_ex_ids = $cats_ex !== '' ? array_filter( array_map( 'intval', explode( ',', $cats_ex ) ) ) : array();
    if ( ! empty( $cats_ex_ids ) ) {
        $tax = $query->get( 'tax_query' ); if ( ! is_array( $tax ) ) { $tax = array(); }
        $tax[] = array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => $cats_ex_ids, 'operator' => 'NOT IN' );
        $query->set( 'tax_query', $tax );
    }

    // DEBUG: capture main-query vars for My Feed
    if ( $is_my_feed ) {
        $GLOBALS['hrphoto_my_feed_debug'] = array(
            'hook'           => 'pre_get_posts:param19',
            'uri'            => isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '',
            'is_main'        => true,
            'is_page'        => is_page(),
            'vars'           => array(
                'post_type'      => $query->get( 'post_type' ),
                'author__in'     => $query->get( 'author__in' ),
                'author__not_in' => $query->get( 'author__not_in' ),
                'tax_query'      => $query->get( 'tax_query' ),
            ),
            'params'         => array(
                'u_scope'      => $u_scope,
                'likes'        => $likes,
                'date'         => $date,
                'cats'         => $cats_s,
                'cats_exclude' => $cats_ex,
                'ptype'        => $ptype,
            ),
        );
    }

    // Ranking: preferred categories first (boost)
    $cat_ids = $cats_s !== '' ? array_filter( array_map( 'intval', explode( ',', $cats_s ) ) ) : array();
    if ( ! empty( $cat_ids ) ) {
        add_filter( 'posts_clauses', function( $clauses, $q ) use ( $cat_ids, $query ) {
            if ( $q !== $query ) { return $clauses; }
            global $wpdb;
            $in = implode( ',', array_map( 'intval', $cat_ids ) );
            $exists = "EXISTS (SELECT 1 FROM {$wpdb->term_relationships} tr JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy='category' WHERE tr.object_id={$wpdb->posts}.ID AND tt.term_id IN ({$in}))";
            $rank = 'CASE WHEN ' . $exists . ' THEN 0 ELSE 1 END ASC';
            $clauses['orderby'] = $rank . ( $clauses['orderby'] ? ', ' . $clauses['orderby'] : '' );
            return $clauses;
        }, 10, 2 );
    }

    // Ranking: likes order
    if ( $likes === 'most' || $likes === 'least' ) {
        add_filter( 'posts_clauses', function( $clauses, $q ) use ( $likes, $query ) {
            if ( $q !== $query ) { return $clauses; }
            global $wpdb; $likes_table = $wpdb->prefix . 'postpic_likes';
            $sub = "(SELECT COUNT(*) FROM {$likes_table} l WHERE l.post_id={$wpdb->posts}.ID AND l.comment_id IS NULL)";
            $dir = ( $likes === 'least' ) ? 'ASC' : 'DESC';
            $ord = $sub . ' ' . $dir;
            // Make likes primary, with stable date tie-breaker
            $clauses['orderby'] = $ord . ', ' . $wpdb->posts . '.post_date DESC';
            return $clauses;
        }, 10, 2 );
        if ( $date === 'random' ) { $date = 'latest'; }
    }

    // Date ordering when likes OFF
    if ( empty( $likes ) ) {
        if ( empty( $cat_ids ) ) {
            // No boost → replace orderby directly
            if ( $date === 'random' ) {
                $query->set( 'orderby', 'rand' );
            } else {
                $query->set( 'orderby', 'date' );
                $query->set( 'order', ( $date === 'oldest' ) ? 'ASC' : 'DESC' );
            }
        } else {
            // Boost present → append date as tie-breaker
            add_filter( 'posts_clauses', function( $clauses, $q ) use ( $date, $query ) {
                if ( $q !== $query ) { return $clauses; }
                global $wpdb;
                $final = ($date === 'oldest') ? ($wpdb->posts . '.post_date ASC') : (($date === 'random') ? 'RAND()' : ($wpdb->posts . '.post_date DESC'));
                $clauses['orderby'] = ! empty( $clauses['orderby'] ) ? ( $clauses['orderby'] . ', ' . $final ) : $final;
                return $clauses;
            }, 10, 2 );
        }
    }

}, 19 );

/**
 * Drive the core Query Loop block on the My Feed page using URL/default params
 * without affecting the global Home posts index.
 */
add_filter( 'query_loop_block_query_vars', function( $vars, $block ) {
    // Front-end only
    if ( is_admin() ) { return $vars; }
    // Allow targeting either by page slug OR a block class marker on the Query Loop
    $has_marker = ( isset( $block['attrs'], $block['attrs']['className'] ) && is_string( $block['attrs']['className'] ) && strpos( $block['attrs']['className'], 'my-feed-loop' ) !== false );
    if ( ! $has_marker && ! is_page( 'my-feed' ) ) { return $vars; }

    $current_user_id = get_current_user_id();

    // TEMP: Force a permissive query to verify the loop wiring (no author/category filters, include core + CPTs)
    // This should make /my-feed show content if any exists across these types.
    $vars['post_type'] = array( 'post', '1hrphoto', 'story' );
    unset( $vars['author__in'], $vars['author__not_in'] );
    unset( $vars['tax_query'] );
    $vars['orderby'] = 'date';
    $vars['order']   = 'DESC';
    // Mark loop filter fired (for diagnostics)
    $GLOBALS['hrphoto_my_feed_loop_filter'] = array(
        'fired'       => true,
        'vars_forced' => $vars,
        'attrs'       => isset( $block['attrs'] ) ? $block['attrs'] : array(),
    );
    return $vars;

    // Parse URL params
    $u_scope = isset($_GET['u_scope']) ? sanitize_key( wp_unslash($_GET['u_scope']) ) : '';
    $likes   = isset($_GET['likes']) ? sanitize_key( wp_unslash($_GET['likes']) ) : '';
    $date    = isset($_GET['date']) ? sanitize_key( wp_unslash($_GET['date']) ) : '';
    $cats_s  = isset($_GET['cats']) ? (string) $_GET['cats'] : '';
    $cats_ex = isset($_GET['cats_exclude']) ? (string) $_GET['cats_exclude'] : '';
    $ptype   = isset($_GET['ptype']) ? sanitize_key( wp_unslash($_GET['ptype']) ) : '';

    // Defaults / saved fallbacks when missing
    if ( $u_scope === '' ) { $u_scope = 'everyone'; }
    if ( $date === '' ) { $date = 'latest'; }
    if ( $cats_s === '' && is_user_logged_in() ) {
        $saved_cats = (array) get_user_meta( $current_user_id, 'hrphoto_feed_view_cats', true );
        if ( $saved_cats ) { $cats_s = implode( ',', array_map( 'intval', $saved_cats ) ); }
    }
    if ( $ptype === '' && is_user_logged_in() ) {
        $saved_pt = (string) get_user_meta( $current_user_id, 'hrphoto_feed_view_ptype', true );
        if ( in_array( $saved_pt, array('1hrphoto','story'), true ) ) { $ptype = $saved_pt; }
    }

    // Base post types: only 1hrphoto/story unless explicitly narrowed
    if ( in_array( $ptype, array('1hrphoto','story'), true ) ) {
        $vars['post_type'] = $ptype;
    } else {
        $vars['post_type'] = array( '1hrphoto', 'story' );
    }

    // Author scope
    if ( $u_scope === 'following' ) {
        if ( ! is_user_logged_in() ) {
            $vars['author__in'] = array( 0 );
        } else {
            $ids = hrphoto_get_following_ids( $current_user_id );
            $vars['author__in'] = ! empty( $ids ) ? array_map( 'intval', $ids ) : array( 0 );
        }
    } elseif ( $u_scope === 'not_following' && is_user_logged_in() ) {
        $ids = hrphoto_get_following_ids( $current_user_id );
        $not_in = ! empty( $ids ) ? $ids : array();
        $not_in[] = (int) $current_user_id; // exclude self
        $vars['author__not_in'] = array_map( 'intval', $not_in );
    }

    // Always exclude blocked authors for logged-in viewers
    if ( is_user_logged_in() ) {
        $blocked = hrphoto_get_blocklist_author_ids( $current_user_id );
        if ( ! empty( $blocked ) ) {
            $existing = isset( $vars['author__not_in'] ) && is_array( $vars['author__not_in'] ) ? $vars['author__not_in'] : array();
            $vars['author__not_in'] = array_values( array_unique( array_map( 'intval', array_merge( $existing, $blocked ) ) ) );
        }
    }

    // Category exclusions
    $cats_ex_ids = $cats_ex !== '' ? array_filter( array_map( 'intval', explode( ',', $cats_ex ) ) ) : array();
    if ( ! empty( $cats_ex_ids ) ) {
        $tax = isset( $vars['tax_query'] ) && is_array( $vars['tax_query'] ) ? $vars['tax_query'] : array();
        $tax[] = array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => $cats_ex_ids, 'operator' => 'NOT IN' );
        $vars['tax_query'] = $tax;
    }

    // Mark this loop so we can add ordering in pre_get_posts
    $vars['hrphoto_feed'] = 1;
    $vars['hrphoto_feed_params'] = array(
        'cats'  => $cats_s,
        'likes' => $likes,
        'date'  => $date,
    );

    return $vars;
}, 10, 2 );

// Apply ordering to the marked Query Loop only
add_action( 'pre_get_posts', function( $q ) {
    if ( is_admin() || ! $q instanceof WP_Query ) { return; }
    if ( ! $q->get( 'hrphoto_feed' ) ) { return; }

    $params = (array) $q->get( 'hrphoto_feed_params' );
    $date   = isset( $params['date'] ) ? (string) $params['date'] : 'latest';
    $likes  = isset( $params['likes'] ) ? (string) $params['likes'] : '';
    $cats_s = isset( $params['cats'] ) ? (string) $params['cats'] : '';

    // Category boost first
    $cat_ids = $cats_s !== '' ? array_filter( array_map( 'intval', explode( ',', $cats_s ) ) ) : array();
    if ( ! empty( $cat_ids ) ) {
        add_filter( 'posts_clauses', function( $clauses, $query ) use ( $cat_ids, $q ) {
            if ( $query !== $q ) { return $clauses; }
            global $wpdb;
            $in = implode( ',', array_map( 'intval', $cat_ids ) );
            $exists = "EXISTS (SELECT 1 FROM {$wpdb->term_relationships} tr JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy='category' WHERE tr.object_id={$wpdb->posts}.ID AND tt.term_id IN ({$in}))";
            $rank = 'CASE WHEN ' . $exists . ' THEN 0 ELSE 1 END ASC';
            $clauses['orderby'] = $rank . ( $clauses['orderby'] ? ', ' . $clauses['orderby'] : '' );
            return $clauses;
        }, 10, 2 );
    }

    // Likes ordering next
    if ( $likes === 'most' || $likes === 'least' ) {
        add_filter( 'posts_clauses', function( $clauses, $query ) use ( $likes, $q ) {
            if ( $query !== $q ) { return $clauses; }
            global $wpdb; $likes_table = $wpdb->prefix . 'postpic_likes';
            $sub = "(SELECT COUNT(*) FROM {$likes_table} l WHERE l.post_id={$wpdb->posts}.ID AND l.comment_id IS NULL)";
            $dir = ( $likes === 'least' ) ? 'ASC' : 'DESC';
            $ord = $sub . ' ' . $dir;
            // Make likes primary, with stable date tie-breaker
            $clauses['orderby'] = $ord . ', ' . $wpdb->posts . '.post_date DESC';
            return $clauses;
        }, 10, 2 );
        if ( $date === 'random' ) { $date = 'latest'; }
    }

    // Date ordering for marked Query Loop (only when likes OFF). Append to boosts; else replace.
    if ( empty( $likes ) ) {
        add_filter( 'posts_clauses', function( $clauses, $q2 ) use ( $date, $q ) {
            if ( $q2 !== $q ) { return $clauses; }
            global $wpdb;
            $final = ($date === 'oldest') ? ($wpdb->posts . '.post_date ASC') : (($date === 'random') ? 'RAND()' : ($wpdb->posts . '.post_date DESC'));
            $clauses['orderby'] = !empty($clauses['orderby']) ? ($clauses['orderby'] . ', ' . $final) : $final;
            return $clauses;
        }, 10, 2 );
    }
}, 20 );

/**
 * Personalize Home feed for logged-in users only (bypass with ?latest=1)
 * - Applies URL params if present; else uses saved defaults
 * - Filters: author scope, post type (1hrphoto/story), category excludes
 * - Ranking: preferred categories boost, likes, then date
 */
add_action( 'pre_get_posts', function( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) { return; }
    if ( ! $query->is_home() ) { return; }
    // Bypass personalization
    if ( isset( $_GET['latest'] ) && (string) $_GET['latest'] === '1' ) { return; }
    if ( ! is_user_logged_in() ) { return; }

    $current_user_id = get_current_user_id();

    // Parse params (URL has priority)
    $u_scope = isset($_GET['u_scope']) ? sanitize_key( wp_unslash($_GET['u_scope']) ) : '';
    $likes   = isset($_GET['likes']) ? sanitize_key( wp_unslash($_GET['likes']) ) : '';
    $date    = isset($_GET['date']) ? sanitize_key( wp_unslash($_GET['date']) ) : '';
    $cats_s  = isset($_GET['cats']) ? (string) $_GET['cats'] : '';
    $cats_ex = isset($_GET['cats_exclude']) ? (string) $_GET['cats_exclude'] : '';
    $ptype   = isset($_GET['ptype']) ? sanitize_key( wp_unslash($_GET['ptype']) ) : '';

    // Defaults from user meta if missing
    if ( $u_scope === '' ) { $u_scope = (string) get_user_meta( $current_user_id, 'hrphoto_feed_u_scope', true ); }
    if ( $u_scope !== 'following' && $u_scope !== 'not_following' ) { $u_scope = 'everyone'; }
    if ( $likes === '' ) { $likes = (string) get_user_meta( $current_user_id, 'hrphoto_feed_likes', true ); }
    if ( $date === '' ) { $date = (string) get_user_meta( $current_user_id, 'hrphoto_feed_date', true ); }
    if ( $date !== 'oldest' && $date !== 'random' ) { $date = 'latest'; }
    if ( $cats_s === '' ) {
        $saved_cats = (array) get_user_meta( $current_user_id, 'hrphoto_feed_view_cats', true );
        if ( $saved_cats ) { $cats_s = implode( ',', array_map( 'intval', $saved_cats ) ); }
    }
    if ( $cats_ex === '' ) {
        $saved_ex = (array) get_user_meta( $current_user_id, 'hrphoto_feed_exclude_cats', true );
        if ( $saved_ex ) { $cats_ex = implode( ',', array_map( 'intval', $saved_ex ) ); }
    }
    if ( $ptype === '' ) {
        $ptype_enabled = (int) get_user_meta( $current_user_id, 'hrphoto_feed_ptype_enabled', true );
        if ( $ptype_enabled ) {
            $saved_pt = (string) get_user_meta( $current_user_id, 'hrphoto_feed_view_ptype', true );
            if ( in_array( $saved_pt, array('1hrphoto','story'), true ) ) { $ptype = $saved_pt; }
        }
    }

    // Author scope
    if ( $u_scope === 'following' ) {
        $ids = hrphoto_get_following_ids( $current_user_id );
        $query->set( 'author__in', ! empty( $ids ) ? $ids : array(0) );
    } elseif ( $u_scope === 'not_following' ) {
        $ids = hrphoto_get_following_ids( $current_user_id );
        $not_in = ! empty( $ids ) ? $ids : array();
        $not_in[] = (int) $current_user_id; // exclude self
        $query->set( 'author__not_in', array_map( 'intval', $not_in ) );
    }

    // Always exclude blocked authors for logged-in viewers
    $blocked = hrphoto_get_blocklist_author_ids( $current_user_id );
    if ( ! empty( $blocked ) ) {
        $existing = $query->get( 'author__not_in' );
        $existing = is_array( $existing ) ? $existing : array();
        $query->set( 'author__not_in', array_values( array_unique( array_map( 'intval', array_merge( $existing, $blocked ) ) ) ) );
    }

    // Post types: personalized Home shows only 1hrphoto/story by default
    if ( in_array( $ptype, array('1hrphoto','story'), true ) ) {
        $query->set( 'post_type', $ptype );
    } else {
        $query->set( 'post_type', array( '1hrphoto', 'story' ) );
    }

    // Category exclusions
    $cats_ex_ids = $cats_ex !== '' ? array_filter( array_map( 'intval', explode( ',', $cats_ex ) ) ) : array();
    if ( ! empty( $cats_ex_ids ) ) {
        $tax = $query->get( 'tax_query' ); if ( ! is_array( $tax ) ) { $tax = array(); }
        $tax[] = array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => $cats_ex_ids, 'operator' => 'NOT IN' );
        $query->set( 'tax_query', $tax );
    }

    // Preferred categories boost
    $cat_ids = $cats_s !== '' ? array_filter( array_map( 'intval', explode( ',', $cats_s ) ) ) : array();
    if ( ! empty( $cat_ids ) ) {
        add_filter( 'posts_clauses', function( $clauses, $q ) use ( $cat_ids, $query ) {
            if ( $q !== $query ) { return $clauses; }
            global $wpdb;
            $in = implode( ',', array_map( 'intval', $cat_ids ) );
            $exists = "EXISTS (SELECT 1 FROM {$wpdb->term_relationships} tr JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy='category' WHERE tr.object_id={$wpdb->posts}.ID AND tt.term_id IN ({$in}))";
            $rank = 'CASE WHEN ' . $exists . ' THEN 0 ELSE 1 END ASC';
            $clauses['orderby'] = $rank . ( $clauses['orderby'] ? ', ' . $clauses['orderby'] : '' );
            return $clauses;
        }, 10, 2 );
    }

    // Likes ordering
    if ( $likes === 'most' || $likes === 'least' ) {
        add_filter( 'posts_clauses', function( $clauses, $q ) use ( $likes, $query ) {
            if ( $q !== $query ) { return $clauses; }
            global $wpdb; $likes_table = $wpdb->prefix . 'postpic_likes';
            $sub = "(SELECT COUNT(*) FROM {$likes_table} l WHERE l.post_id={$wpdb->posts}.ID AND l.comment_id IS NULL)";
            $dir = ( $likes === 'least' ) ? 'ASC' : 'DESC';
            $ord = $sub . ' ' . $dir;
            // Make likes primary, with stable date tie-breaker
            $clauses['orderby'] = $ord . ', ' . $wpdb->posts . '.post_date DESC';
            return $clauses;
        }, 10, 2 );
        if ( $date === 'random' ) { $date = 'latest'; }
    }

    // Date ordering when likes OFF
    if ( empty( $likes ) ) {
        if ( empty( $cat_ids ) ) {
            // No boost → replace orderby directly
            if ( $date === 'random' ) {
                $query->set( 'orderby', 'rand' );
            } else {
                $query->set( 'orderby', 'date' );
                $query->set( 'order', ( $date === 'oldest' ) ? 'ASC' : 'DESC' );
            }
        } else {
            // Boost present → append date as tie-breaker
            add_filter( 'posts_clauses', function( $clauses, $q ) use ( $date, $query ) {
                if ( $q !== $query ) { return $clauses; }
                global $wpdb;
                $final = ($date === 'oldest') ? ($wpdb->posts . '.post_date ASC') : (($date === 'random') ? 'RAND()' : ($wpdb->posts . '.post_date DESC'));
                $clauses['orderby'] = ! empty( $clauses['orderby'] ) ? ( $clauses['orderby'] . ', ' . $final ) : $final;
                return $clauses;
            }, 10, 2 );
        }
    }

}, 18 );

// Emit a one-time HTML comment in head on My Feed to inspect vars
add_action( 'wp_head', function() {
    if ( ! is_page( 'my-feed' ) ) { return; }
    $dbg_main = isset( $GLOBALS['hrphoto_my_feed_debug'] ) ? $GLOBALS['hrphoto_my_feed_debug'] : array();
    $dbg_loop = isset( $GLOBALS['hrphoto_my_feed_loop_filter'] ) ? $GLOBALS['hrphoto_my_feed_loop_filter'] : array( 'fired' => false );
    $out = array( 'main' => $dbg_main, 'loop_filter' => $dbg_loop );
    if ( function_exists( 'wp_json_encode' ) ) {
        echo "\n<!-- MY_FEED_DEBUG " . esc_html( wp_json_encode( $out ) ) . " -->\n";
    }
}, 99 );

add_action( 'pre_get_posts', function( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    // Home/blog stays Latest (no view modes applied)
    if ( $query->is_home() ) {
        return;
    }

    // Do not alter the main query for Pages except the dedicated My Feed page
    if ( $query->is_page() && ! is_page( 'my-feed' ) ) {
        return;
    }

    // Only apply legacy view=... on My Feed
    $view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';
    $is_my_feed = is_page( 'my-feed' );
    if ( ! $is_my_feed ) { return; }

    // Resolve shared inputs
    $current_user_id = get_current_user_id();
    $cat_ids_param   = isset( $_GET['cat_ids'] ) ? (string) $_GET['cat_ids'] : '';
    $cat_ids         = $cat_ids_param !== '' ? array_filter( array_map( 'intval', explode( ',', $cat_ids_param ) ) ) : array();
    if ( empty( $cat_ids ) && is_user_logged_in() ) {
        $saved = get_user_meta( $current_user_id, 'hrphoto_feed_view_cats', true );
        if ( is_array( $saved ) ) {
            $cat_ids = array_filter( array_map( 'intval', $saved ) );
        }
    }

    // Following only
    if ( $view === 'following' ) {
        if ( ! is_user_logged_in() ) {
            $query->set( 'author__in', array( 0 ) );
            return;
        }
        $ids = hrphoto_get_following_ids( $current_user_id );
        $query->set( 'author__in', ! empty( $ids ) ? $ids : array( 0 ) );
        $query->set( 'ignore_sticky_posts', true );
        return;
    }

    // Following first (rank followed authors' posts to top, then newest)
    if ( $view === 'following_first' && is_user_logged_in() ) {
        $ids = hrphoto_get_following_ids( $current_user_id );
        if ( ! empty( $ids ) ) {
            add_filter( 'posts_orderby', function( $orderby, $q ) use ( $ids, $query ) {
                if ( $q !== $query ) { return $orderby; }
                global $wpdb;
                $in = implode( ',', array_map( 'intval', $ids ) );
                $rank = "CASE WHEN {$wpdb->posts}.post_author IN ({$in}) THEN 0 ELSE 1 END ASC";
                return $rank . ', ' . ( $orderby ? $orderby : ( $wpdb->posts . '.post_date DESC' ) );
            }, 10, 2 );
        }
        return;
    }

    // Categories only (viewer-selected viewing categories)
    if ( $view === 'cats_only' ) {
        if ( ! empty( $cat_ids ) ) {
            $query->set( 'tax_query', array( array(
                'taxonomy' => 'category',
                'field'    => 'term_id',
                'terms'    => $cat_ids,
                'operator' => 'IN',
            ) ) );
        } else {
            $query->set( 'post__in', array( 0 ) ); // none selected → empty
        }
        return;
    }

    // Categories first (rank matches first)
    if ( $view === 'cats_first' && ! empty( $cat_ids ) ) {
        add_filter( 'posts_clauses', function( $clauses, $q ) use ( $cat_ids, $query ) {
            if ( $q !== $query ) { return $clauses; }
            global $wpdb;
            $in = implode( ',', array_map( 'intval', $cat_ids ) );
            $exists = "EXISTS (SELECT 1 FROM {$wpdb->term_relationships} tr JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy='category' WHERE tr.object_id={$wpdb->posts}.ID AND tt.term_id IN ({$in}))";
            $rank = 'CASE WHEN ' . $exists . ' THEN 0 ELSE 1 END ASC';
            $clauses['orderby'] = $rank . ', ' . ( $clauses['orderby'] ? $clauses['orderby'] : ( $wpdb->posts . '.post_date DESC' ) );
            return $clauses;
        }, 10, 2 );
        return;
    }

    // Most liked (global)
    if ( $view === 'likes' ) {
        hrphoto_apply_most_liked_order( $query );
        return;
    }

    // Most liked by category
    if ( $view === 'likes_cat' ) {
        if ( ! empty( $cat_ids ) ) {
            $query->set( 'tax_query', array( array(
                'taxonomy' => 'category',
                'field'    => 'term_id',
                'terms'    => $cat_ids,
                'operator' => 'IN',
            ) ) );
        } else {
            $query->set( 'post__in', array( 0 ) );
            return;
        }
        hrphoto_apply_most_liked_order( $query );
        return;
    }

    // Most liked by post type (only 1hrphoto or story)
    if ( $view === 'likes_type' ) {
        $ptype = isset( $_GET['ptype'] ) ? sanitize_key( wp_unslash( $_GET['ptype'] ) ) : '';
        if ( in_array( $ptype, array( '1hrphoto', 'story' ), true ) ) {
            $query->set( 'post_type', $ptype );
        }
        hrphoto_apply_most_liked_order( $query );
        return;
    }

    // Latest: no changes
}, 20 );

