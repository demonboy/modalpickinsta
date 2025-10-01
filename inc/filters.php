<?php
/**
 * Front-end wording adjustments from "Author" → "Photographer"
 * - Archive titles (author archives)
 * - Core author blocks (post author name/byline)
 */

// Archive title: Author → Photographer
add_filter('get_the_archive_title', function($title) {
    if (is_author()) {
        // Typically "Author: {name}" → replace the label only
        $title = preg_replace('/^' . preg_quote(__('Author:', 'default'), '/') . '\s*/i', __('Photographer:', '1hrphoto') . ' ', $title);
        // Fallback if pattern not matched: build explicitly
        if (stripos($title, __('Photographer:', '1hrphoto')) !== 0) {
            $title = __('Photographer:', '1hrphoto') . ' ' . get_the_author();
        }
    }
    return $title;
});

// Removed byline label injection to keep post meta minimal (no "Photographer:" prefix)


// Hide "constructive feedback" tag from front-end display (kept in DB for filtering)
add_filter( 'get_the_terms', function ( $terms, $post_id, $taxonomy ) {
    if ( is_admin() ) {
        return $terms;
    }

    if ( 'post_tag' !== $taxonomy || ! is_array( $terms ) || empty( $terms ) ) {
        return $terms;
    }

    $hidden_slugs = array( 'constructive-feedback' );
    $hidden_names = array( 'constructive feedback' );

    // Filter out the constructive feedback tag
    $filtered = array();
    foreach ( $terms as $term ) {
        if ( ! in_array( $term->slug, $hidden_slugs, true ) && ! in_array( strtolower( $term->name ), $hidden_names, true ) ) {
            $filtered[] = $term;
        }
    }

    return $filtered;
}, 10, 3 );

// Hide default category ("Uncategorized") across front-end term lists
add_filter( 'get_terms_args', function ( $args, $taxonomies ) {
    // Skip only in real wp-admin screens; allow during AJAX requests
    if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
        return $args;
    }

    if ( ! is_array( $taxonomies ) || ! in_array( 'category', $taxonomies, true ) ) {
        return $args;
    }

    $uncat_id = (int) get_option( 'default_category' );
    if ( ! $uncat_id ) {
        return $args;
    }

    $exclude = isset( $args['exclude'] ) ? (array) $args['exclude'] : array();
    if ( ! in_array( $uncat_id, $exclude, true ) ) {
        $exclude[] = $uncat_id;
    }
    $args['exclude'] = $exclude;
    return $args;
}, 10, 2 );

// Remove default category from nav menus if present
add_filter( 'wp_get_nav_menu_items', function ( $items ) {
    if ( is_admin() ) {
        return $items;
    }
    $uncat_id = (int) get_option( 'default_category' );
    if ( ! $uncat_id ) {
        return $items;
    }
    return array_values( array_filter( (array) $items, function ( $item ) use ( $uncat_id ) {
        return ! ( isset( $item->object, $item->object_id ) && $item->object === 'category' && (int) $item->object_id === $uncat_id );
    } ) );
}, 10, 1 );

// PREPEND CPT LINKS to the header categories chip list only
add_filter( 'render_block', function ( $block_content, $block ) {
	// Only front-end
	if ( is_admin() ) {
		return $block_content;
	}
	if ( ! is_string( $block_content ) || $block_content === '' ) {
		return $block_content;
	}
	if ( empty( $block['blockName'] ) || $block['blockName'] !== 'core/categories' ) {
		return $block_content;
	}

	$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
	$class = isset( $attrs['className'] ) ? (string) $attrs['className'] : '';
	if ( strpos( $class, 'cat-chips' ) === false ) {
		return $block_content;
	}

	// Build CPT archive links
	$one_hr_slug = '1hrphoto';
	$story_slug  = 'story';
	$one_hr_url  = get_post_type_archive_link( $one_hr_slug );
	$story_url   = get_post_type_archive_link( $story_slug );
	if ( ! $one_hr_url && ! $story_url ) {
		return $block_content;
	}

	$is_one_hr_active = is_post_type_archive( '1hrphoto' );
	$is_story_active  = is_post_type_archive( 'story' );

	$prepend = '';
	if ( $one_hr_url ) {
		$li_class = 'cat-item cat-item-1hrphoto' . ( $is_one_hr_active ? ' current-cat' : '' );
		$aria     = $is_one_hr_active ? ' aria-current="page"' : '';
		$prepend .= '<li class="' . $li_class . '"><a href="' . esc_url( $one_hr_url ) . '"' . $aria . '>1hrphoto</a></li>';
	}
	if ( $story_url ) {
		$li_class = 'cat-item cat-item-story' . ( $is_story_active ? ' current-cat' : '' );
		$aria     = $is_story_active ? ' aria-current="page"' : '';
		$prepend .= '<li class="' . $li_class . '"><a href="' . esc_url( $story_url ) . '"' . $aria . '>Stories</a></li>';
	}
	if ( $prepend === '' ) {
		return $block_content;
	}

	// Insert immediately after the opening <ul ...>
	$updated = preg_replace( '/(<ul[^>]*>)/i', '$1' . $prepend, $block_content, 1 );
	return $updated ? $updated : $block_content;
}, 10, 2 );

// Hide archive title heading only on 1hrphoto and story CPT archives
add_filter( 'render_block', function( $block_content, $block ) {
	if ( is_admin() ) {
		return $block_content;
	}
	if ( ! is_string( $block_content ) || $block_content === '' ) {
		return $block_content;
	}
	if ( empty( $block['blockName'] ) || $block['blockName'] !== 'core/archive-title' ) {
		return $block_content;
	}

	if ( is_post_type_archive( '1hrphoto' ) || is_post_type_archive( 'story' ) ) {
		return '';
	}
	return $block_content;
}, 10, 2 );

// Hide query title heading only on 1hrphoto and story CPT archives
add_filter( 'render_block', function( $block_content, $block ) {
	if ( is_admin() ) {
		return $block_content;
	}
	if ( ! is_string( $block_content ) || $block_content === '' ) {
		return $block_content;
	}
	if ( empty( $block['blockName'] ) || $block['blockName'] !== 'core/query-title' ) {
		return $block_content;
	}

	if ( is_post_type_archive( '1hrphoto' ) || is_post_type_archive( 'story' ) ) {
		return '';
	}
	return $block_content;
}, 10, 2 );

// Remove leading label paragraph from CPT archive post header groups
add_filter( 'render_block', function( $block_content, $block ) {
	if ( is_admin() ) {
		return $block_content;
	}
	if ( ! is_string( $block_content ) || $block_content === '' ) {
		return $block_content;
	}
	if ( empty( $block['blockName'] ) || $block['blockName'] !== 'core/group' ) {
		return $block_content;
	}
	// Target only CPT archives and only groups that include a post title
	if ( strpos( $block_content, 'wp-block-post-title' ) === false ) {
		return $block_content;
	}

	if ( is_post_type_archive( '1hrphoto' ) ) {
		$updated = preg_replace( '/<p>\s*1\s*Hour\s*Photo\s*<\/p>\s*/i', '', $block_content, 1 );
		return $updated ?: $block_content;
	}
	if ( is_post_type_archive( 'story' ) ) {
		$updated = preg_replace( '/<p>\s*Story\s*<\/p>\s*/i', '', $block_content, 1 );
		return $updated ?: $block_content;
	}

	return $block_content;
}, 10, 2 );




