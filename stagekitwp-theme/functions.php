<?php
/**
 * StageKitWP Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package StageKitWP_Theme
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! defined( '_S_VERSION' ) ) {
    // Define theme version matching development cycle
    define( '_S_VERSION', '3.1.2' );
}

/**
 * Set the global content width in pixels, based on the theme's design and stylesheet.
 * Priority 0 so it is available to lower priority callbacks.
 *
 * @global int $content_width
 */
function stagekitwp_content_width() {
    $GLOBALS['content_width'] = apply_filters( 'stagekitwp_content_width', 1200 );
}
add_action( 'after_setup_theme', 'stagekitwp_content_width', 0 );

/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
function stagekitwp_theme_setup() {
    // Add default posts and comments RSS feed links to head.
    add_theme_support( 'automatic-feed-links' );

    // Let WordPress manage the document title.
    add_theme_support( 'title-tag' );

    // Enable support for Post Thumbnails on posts and pages (Crucial for Play Graphics).
    add_theme_support( 'post-thumbnails' );

    // Register Navigation Menus matching our required structural slots
    register_nav_menus(
        array(
            'primary-menu' => esc_html__( 'Primary Main Menu', 'stagekitwp-theme' ),
            'footer-menu'  => esc_html__( 'Footer Secondary Menu', 'stagekitwp-theme' ),
        )
    );

    /*
     * Switch default core markup for search form, comment form, and comments
     * to output valid HTML5.
     */
    add_theme_support(
        'html5',
        array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        )
    );
}
add_action( 'after_setup_theme', 'stagekitwp_theme_setup' );

/**
 * Register theme-specific block pattern category.
 */
function stagekitwp_register_block_pattern_categories() {
    if ( ! function_exists( 'register_block_pattern_category' ) ) {
        return;
    }

    register_block_pattern_category(
        'stagekitwp-theme',
        array(
			'label' => __( 'StageKitWP Posts', 'stagekitwp-theme' ),
        )
    );
}
add_action( 'init', 'stagekitwp_register_block_pattern_categories' );

/**
 * Include searchable custom post types in frontend search results.
 */
function stagekitwp_theme_include_cpts_in_search( $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
        return;
    }

    $searchable_post_types = get_post_types(
        array(
            'public'              => true,
            'exclude_from_search' => false,
        ),
        'names'
    );

    // Optional Theatre-only mode.
    if ( get_theme_mod( 'stagekitwp_search_only_theatre_cpts', false ) ) {
        $theatre_cpts = array(
            'show',
            'season',
            'testimonial',
            'venue',
            'cast',
            'award',
            'sponsor',
            'contributor',
            'board_member',
            'advertiser',
        );

        $searchable_post_types = array_values( array_intersect( $searchable_post_types, $theatre_cpts ) );
    }

    // Optional page include toggle.
    if ( ! get_theme_mod( 'stagekitwp_search_include_pages', true ) ) {
        $searchable_post_types = array_values( array_diff( $searchable_post_types, array( 'page' ) ) );
    } elseif ( ! in_array( 'page', $searchable_post_types, true ) ) {
        $searchable_post_types[] = 'page';
    }

    if ( ! empty( $searchable_post_types ) ) {
        $query->set( 'post_type', array_values( $searchable_post_types ) );
    }
}
add_action( 'pre_get_posts', 'stagekitwp_theme_include_cpts_in_search' );

/**
 * Flag frontend main search queries so we can expand SQL clauses safely.
 */
function stagekitwp_theme_flag_complete_search_query( $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
        return;
    }

    $query->set( 'stagekitwp_complete_search', true );
}
add_action( 'pre_get_posts', 'stagekitwp_theme_flag_complete_search_query', 20 );

/**
 * Join meta + taxonomy tables for complete search coverage.
 */
function stagekitwp_theme_complete_search_join( $join, $query ) {
    global $wpdb;

    if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() || ! $query->get( 'stagekitwp_complete_search' ) ) {
        return $join;
    }

    if ( ! preg_match( '/\bstagekitwp_search_pm\b/', $join ) ) {
        $join .= " LEFT JOIN {$wpdb->postmeta} stagekitwp_search_pm ON ({$wpdb->posts}.ID = stagekitwp_search_pm.post_id)";
    }
    if ( ! preg_match( '/\bstagekitwp_search_tr\b/', $join ) ) {
        $join .= " LEFT JOIN {$wpdb->term_relationships} stagekitwp_search_tr ON ({$wpdb->posts}.ID = stagekitwp_search_tr.object_id)";
    }
    if ( ! preg_match( '/\bstagekitwp_search_tt\b/', $join ) ) {
        $join .= " LEFT JOIN {$wpdb->term_taxonomy} stagekitwp_search_tt ON (stagekitwp_search_tr.term_taxonomy_id = stagekitwp_search_tt.term_taxonomy_id)";
    }
    if ( ! preg_match( '/\bstagekitwp_search_t\b/', $join ) ) {
        $join .= " LEFT JOIN {$wpdb->terms} stagekitwp_search_t ON (stagekitwp_search_tt.term_id = stagekitwp_search_t.term_id)";
    }

    return $join;
}
add_filter( 'posts_join', 'stagekitwp_theme_complete_search_join', 10, 2 );

/**
 * Expand search SQL to match custom field values and term names/slugs.
 */
function stagekitwp_theme_complete_search_sql( $search, $query ) {
    global $wpdb;

    if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() || ! $query->get( 'stagekitwp_complete_search' ) ) {
        return $search;
    }

    $term = trim( (string) $query->get( 's' ) );
    if ( '' === $term || '' === $search ) {
        return $search;
    }

    $search_terms = $query->get( 'search_terms' );
    if ( ! is_array( $search_terms ) || empty( $search_terms ) ) {
        $search_terms = array( $term );
    }

    $group_sql = array();
    $params    = array();

    foreach ( $search_terms as $search_term ) {
        $token = trim( (string) $search_term );
        if ( '' === $token ) {
            continue;
        }

        $like = '%' . $wpdb->esc_like( $token ) . '%';
        $group_sql[] = "((((SUBSTR(stagekitwp_search_pm.meta_key, 1, 4) = '_stagekitwp_') OR (SUBSTR(stagekitwp_search_pm.meta_key, 1, 1) <> '_')) AND stagekitwp_search_pm.meta_value LIKE %s) OR stagekitwp_search_t.name LIKE %s OR stagekitwp_search_t.slug LIKE %s)";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if ( empty( $group_sql ) ) {
        return $search;
    }

    $meta_tax_match = implode( ' AND ', $group_sql );
    $meta_tax_match = $wpdb->prepare( $meta_tax_match, ...$params );

    if ( preg_match( '/^\s*AND\s*\((.*)\)\s*$/s', $search, $matches ) ) {
        return ' AND (' . $matches[1] . ' OR (' . $meta_tax_match . ')) ';
    }

    return $search;
}
add_filter( 'posts_search', 'stagekitwp_theme_complete_search_sql', 10, 2 );

/**
 * De-duplicate posts when JOINs produce multiple matching rows.
 */
function stagekitwp_theme_complete_search_distinct( $distinct, $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() || ! $query->get( 'stagekitwp_complete_search' ) ) {
        return $distinct;
    }

    return 'DISTINCT';
}
add_filter( 'posts_distinct', 'stagekitwp_theme_complete_search_distinct', 10, 2 );

/**
 * Build weighted relevance SQL expression for complete search.
 */
function stagekitwp_theme_complete_search_score_sql( $query ) {
    global $wpdb;

    $term = trim( (string) $query->get( 's' ) );
    if ( '' === $term ) {
        return '0';
    }

    $search_terms = $query->get( 'search_terms' );
    if ( ! is_array( $search_terms ) || empty( $search_terms ) ) {
        $search_terms = array( $term );
    }

    $full_like = '%' . $wpdb->esc_like( $term ) . '%';
    $parts     = array();

    // Strong signals
    $parts[] = $wpdb->prepare( "(CASE WHEN {$wpdb->posts}.post_title = %s THEN 200 ELSE 0 END)", $term );
    $parts[] = $wpdb->prepare( "(CASE WHEN {$wpdb->posts}.post_title LIKE %s THEN 120 ELSE 0 END)", $full_like );
    $parts[] = $wpdb->prepare( "(CASE WHEN {$wpdb->posts}.post_excerpt LIKE %s THEN 60 ELSE 0 END)", $full_like );
    $parts[] = $wpdb->prepare( "(CASE WHEN {$wpdb->posts}.post_content LIKE %s THEN 40 ELSE 0 END)", $full_like );
    $parts[] = $wpdb->prepare(
        "(CASE WHEN EXISTS (
            SELECT 1
            FROM {$wpdb->postmeta} stagekitwp_score_pm
            WHERE stagekitwp_score_pm.post_id = {$wpdb->posts}.ID
              AND (((SUBSTR(stagekitwp_score_pm.meta_key, 1, 4) = '_stagekitwp_') OR (SUBSTR(stagekitwp_score_pm.meta_key, 1, 1) <> '_'))
              AND stagekitwp_score_pm.meta_value LIKE %s)
        ) THEN 70 ELSE 0 END)",
        $full_like
    );
    $parts[] = $wpdb->prepare(
        "(CASE WHEN EXISTS (
            SELECT 1
            FROM {$wpdb->term_relationships} stagekitwp_score_tr
            INNER JOIN {$wpdb->term_taxonomy} stagekitwp_score_tt
                ON stagekitwp_score_tr.term_taxonomy_id = stagekitwp_score_tt.term_taxonomy_id
            INNER JOIN {$wpdb->terms} stagekitwp_score_t
                ON stagekitwp_score_tt.term_id = stagekitwp_score_t.term_id
            WHERE stagekitwp_score_tr.object_id = {$wpdb->posts}.ID
              AND (stagekitwp_score_t.name LIKE %s OR stagekitwp_score_t.slug LIKE %s)
        ) THEN 50 ELSE 0 END)",
        $full_like,
        $full_like
    );

    // Token-level boosts for multi-word queries.
    foreach ( $search_terms as $search_term ) {
        $token = trim( (string) $search_term );
        if ( '' === $token ) {
            continue;
        }

        $token_like = '%' . $wpdb->esc_like( $token ) . '%';
        $parts[] = $wpdb->prepare( "(CASE WHEN {$wpdb->posts}.post_title LIKE %s THEN 20 ELSE 0 END)", $token_like );
        $parts[] = $wpdb->prepare( "(CASE WHEN {$wpdb->posts}.post_content LIKE %s THEN 8 ELSE 0 END)", $token_like );
        $parts[] = $wpdb->prepare(
            "(CASE WHEN EXISTS (
                SELECT 1
                FROM {$wpdb->postmeta} stagekitwp_score_pm
                WHERE stagekitwp_score_pm.post_id = {$wpdb->posts}.ID
                  AND (((SUBSTR(stagekitwp_score_pm.meta_key, 1, 4) = '_stagekitwp_') OR (SUBSTR(stagekitwp_score_pm.meta_key, 1, 1) <> '_'))
                  AND stagekitwp_score_pm.meta_value LIKE %s)
            ) THEN 14 ELSE 0 END)",
            $token_like
        );
        $parts[] = $wpdb->prepare(
            "(CASE WHEN EXISTS (
                SELECT 1
                FROM {$wpdb->term_relationships} stagekitwp_score_tr
                INNER JOIN {$wpdb->term_taxonomy} stagekitwp_score_tt
                    ON stagekitwp_score_tr.term_taxonomy_id = stagekitwp_score_tt.term_taxonomy_id
                INNER JOIN {$wpdb->terms} stagekitwp_score_t
                    ON stagekitwp_score_tt.term_id = stagekitwp_score_t.term_id
                WHERE stagekitwp_score_tr.object_id = {$wpdb->posts}.ID
                  AND (stagekitwp_score_t.name LIKE %s OR stagekitwp_score_t.slug LIKE %s)
            ) THEN 10 ELSE 0 END)",
            $token_like,
            $token_like
        );
    }

    return implode( ' + ', $parts );
}

/**
 * Select a relevance score field so templates can display it.
 */
function stagekitwp_theme_complete_search_fields( $fields, $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() || ! $query->get( 'stagekitwp_complete_search' ) ) {
        return $fields;
    }

    if ( false !== strpos( $fields, 'stagekitwp_search_relevance' ) ) {
        return $fields;
    }

    $score_sql = stagekitwp_theme_complete_search_score_sql( $query );
    return $fields . ', (' . $score_sql . ') AS stagekitwp_search_relevance';
}
add_filter( 'posts_fields', 'stagekitwp_theme_complete_search_fields', 10, 2 );

/**
 * Order by weighted relevance score first, then newest post.
 */
function stagekitwp_theme_complete_search_orderby( $orderby, $query ) {
    global $wpdb;

    if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() || ! $query->get( 'stagekitwp_complete_search' ) ) {
        return $orderby;
    }

    return "stagekitwp_search_relevance DESC, {$wpdb->posts}.post_date DESC";
}
add_filter( 'posts_orderby', 'stagekitwp_theme_complete_search_orderby', 10, 2 );

/**
 * Register Configurable Multi-Column Footer Widget Areas
 */
function stagekitwp_theme_register_footer_widgets() {
    // Column 1 Registration
    register_sidebar( array(
        'name'          => __( 'Footer - Column 1', 'stagekitwp-theme' ),
        'id'            => 'stagekitwp-footer-col-1',
        'description'   => __( 'Populate with your Footer Menu and a Sponsor Link/Image widget.', 'stagekitwp-theme' ),
        'before_widget' => '<div id="%1$s" class="widget stagekitwp-footer-widget %2$s" style="margin-bottom: 20px;">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title" style="color: #ffffff; margin-bottom: 15px; font-size: 1.1rem; text-transform: uppercase;">',
        'after_title'   => '</h4>',
    ) );

    // Column 2 Registration
    register_sidebar( array(
        'name'          => __( 'Footer - Column 2', 'stagekitwp-theme' ),
        'id'            => 'stagekitwp-footer-col-2',
        'description'   => __( 'Populate with "Thanks to our Sponsors or Advertisers" content panels.', 'stagekitwp-theme' ),
        'before_widget' => '<div id="%1$s" class="widget stagekitwp-footer-widget %2$s" style="margin-bottom: 20px;">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title" style="color: #ffffff; margin-bottom: 15px; font-size: 1.1rem; text-transform: uppercase;">',
        'after_title'   => '</h4>',
    ) );

    // Column 3 Registration
    register_sidebar( array(
        'name'          => __( 'Footer - Column 3', 'stagekitwp-theme' ),
        'id'            => 'stagekitwp-footer-col-3',
        'description'   => __( 'Populate with Community Links and a second Sponsor Link/Image widget.', 'stagekitwp-theme' ),
        'before_widget' => '<div id="%1$s" class="widget stagekitwp-footer-widget %2$s" style="margin-bottom: 20px;">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title" style="color: #ffffff; margin-bottom: 15px; font-size: 1.1rem; text-transform: uppercase;">',
        'after_title'   => '</h4>',
    ) );

    // Bottom Socket Area Registration (Copyright & To-Top Action Link)
    register_sidebar( array(
        'name'          => __( 'Footer - Bottom Socket Bar', 'stagekitwp-theme' ),
        'id'            => 'stagekitwp-footer-socket',
        'description'   => __( 'Add a text/HTML widget here for your Custom Copyright Details and Back-to-Top link anchor.', 'stagekitwp-theme' ),
        'before_widget' => '<div id="%1$s" class="widget stagekitwp-socket-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h5 class="screen-reader-text">',
        'after_title'   => '</h5>',
    ) );
}
add_action( 'widgets_init', 'stagekitwp_theme_register_footer_widgets' );

/**
 * Enqueue scripts and styles.
 */
function stagekitwp_theme_scripts() {
    // Enqueue primary stylesheet
    wp_enqueue_style( 'stagekitwp-style', get_stylesheet_uri(), array(), _S_VERSION );

    // Enqueue native Dashicons to support backend fallback rendering indicators
    wp_enqueue_style( 'dashicons' );

    // Enqueue frontend Light/Dark mode switcher (only when the switcher is enabled)
    if ( get_theme_mod( 'stagekitwp_enable_frontend_switcher', false ) ) {
        wp_enqueue_script(
            'stagekitwp-color-mode-switcher',
            get_template_directory_uri() . '/assets/js/color-mode-switcher.js',
            array(),
            _S_VERSION,
            false // load in <head> so mode is applied before paint
        );
        // Inject the PHP-side default mode BEFORE the script body executes.
        // This ensures syncHtmlClass() in <head> uses the correct server default
        // even before DOMContentLoaded fires (fixes the serverDefault=light bug).
        $stagekitwp_color_mode_default = get_theme_mod( 'stagekitwp_color_mode', 'light' );
        wp_add_inline_script(
            'stagekitwp-color-mode-switcher',
            'var stagekitwpColorModeDefault = ' . wp_json_encode( $stagekitwp_color_mode_default ) . ';',
            'before'
        );
    }
}
add_action( 'wp_enqueue_scripts', 'stagekitwp_theme_scripts' );

/**
 * Enqueue Customizer live-preview JS (postMessage bindings).
 */
function stagekitwp_theme_customize_preview_js() {
    wp_enqueue_script(
        'stagekitwp-customizer-preview',
        get_template_directory_uri() . '/assets/js/customizer-preview.js',
        array( 'customize-preview', 'jquery' ),
        _S_VERSION,
        true
    );
    // Pass current PHP-side values as a reliable data object.
    // wp_localize_script converts PHP booleans to "1"/"" strings, breaking
    // JS boolean checks. Use wp_add_inline_script + json_encode instead so
    // true/false are proper JSON booleans.
    $stagekitwp_preview_data = array(
        'taglineText'     => get_bloginfo( 'description' ),
        'showTagline'     => (bool) get_theme_mod( 'stagekitwp_show_tagline', false ),
        'taglinePosition' => get_theme_mod( 'stagekitwp_tagline_position', 'below-logo' ),
        'showBadge'       => (bool) get_theme_mod( 'stagekitwp_show_term_badge', true ),
        'badgePosition'   => get_theme_mod( 'stagekitwp_term_badge_position', 'top-bar' ),
    );
    wp_add_inline_script(
        'stagekitwp-customizer-preview',
        'var stagekitwpPreviewData = ' . wp_json_encode( $stagekitwp_preview_data ) . ';',
        'before'
    );
}
add_action( 'customize_preview_init', 'stagekitwp_theme_customize_preview_js' );

/**
 * Enqueue Customizer CONTROLS pane JS (left panel — not the preview iframe).
 * Handles side-by-side light/dark colour pairing and brightness warnings.
 */
function stagekitwp_theme_customize_controls_js() {
    wp_enqueue_script(
        'stagekitwp-customizer-controls',
        get_template_directory_uri() . '/assets/js/customizer-controls.js',
        array( 'customize-controls', 'jquery' ),
        _S_VERSION,
        true
    );
}
add_action( 'customize_controls_enqueue_scripts', 'stagekitwp_theme_customize_controls_js' );

/**
 * Filter to request an increase in the maximum upload file size for video assets.
 */
function stagekitwp_theme_raise_upload_size_limit( $size ) {
    $target_size = 15728640; // 15 Megabytes in bytes
    if ( $size < $target_size ) {
        return $target_size;
    }
    return $size;
}
add_filter( 'upload_size_limit', 'stagekitwp_theme_raise_upload_size_limit' );

/**
 * Helper function to locate or dynamically generate core content pages
 */
function stagekitwp_get_or_create_page( $title ) {
    $existing = get_posts(
        array(
            'post_type'              => 'page',
            'post_status'            => 'publish',
            'title'                  => $title,
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        )
    );

    if ( empty( $existing ) ) {
        $page_id = wp_insert_post( array(
            'post_title'   => $title,
            'post_content' => sprintf( __( 'Welcome to the %s portal. Content for this production area coming soon.', 'stagekitwp-theme' ), $title ),
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ) );
        return $page_id;
    }
    return (int) $existing[0];
}

/**
 * Automatically seed the Theatre's multi-tier menus and pages safely upon activation.
 */
function stagekitwp_theme_setup_menus_and_pages() {
    // 1. Define the structural site hierarchy
    $menu_structure = array(
        'About Us' => array(
            'History',
            'Contact Us',
            'Board of Directors',
            'Get Involved',
            'By-laws and Code of Conduct'
        ),
        'Shows' => array(
            'Now Playing',
            'Coming Soon',
            'Past Shows',
            'Awards',
            'Media'
        ),
        'News' => array(
            'Auditions',
            'Upcoming Events',
            'Blog',
            'Get Involved'
        ),
        'Tickets' => array(),
    );

    $primary_menu_name = 'Main Menu';
    $footer_menu_name  = 'Footer Menu';

    // 2. Safe Menu Verification Engine (Checks objects to avoid WP_Error crashes)
    $primary_menu_obj = wp_get_nav_menu_object( $primary_menu_name );
    if ( ! $primary_menu_obj ) {
        $primary_menu_id = wp_create_nav_menu( $primary_menu_name );
    } else {
        $primary_menu_id = $primary_menu_obj->term_id;
    }

    $footer_menu_obj = wp_get_nav_menu_object( $footer_menu_name );
    if ( ! $footer_menu_obj ) {
        $footer_menu_id = wp_create_nav_menu( $footer_menu_name );
    } else {
        $footer_menu_id = $footer_menu_obj->term_id;
    }

    // Stop execution completely if either menu reference generated an error object
    if ( is_wp_error( $primary_menu_id ) || is_wp_error( $footer_menu_id ) ) {
        return;
    }

    // 3. Populate Main Menu if empty
    $primary_items = wp_get_nav_menu_items( $primary_menu_id );
    if ( empty( $primary_items ) ) {
        foreach ( $menu_structure as $parent_title => $children ) {
            
            $parent_page_id = stagekitwp_get_or_create_page( $parent_title );
            
            $parent_menu_item_id = wp_update_nav_menu_item( $primary_menu_id, 0, array(
                'menu-item-title'     => $parent_title,
                'menu-item-object'    => 'page',
                'menu-item-object-id' => $parent_page_id,
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
            ) );

            foreach ( $children as $child_title ) {
                $child_page_id = stagekitwp_get_or_create_page( $child_title );
                
                wp_update_nav_menu_item( $primary_menu_id, 0, array(
                    'menu-item-title'     => $child_title,
                    'menu-item-object'    => 'page',
                    'menu-item-object-id' => $child_page_id,
                    'menu-item-parent-id' => $parent_menu_item_id,
                    'menu-item-type'      => 'post_type',
                    'menu-item-status'    => 'publish',
                ) );
            }
        }

        // Append stylable Main Menu Donate Action Link
        wp_update_nav_menu_item( $primary_menu_id, 0, array(
            'menu-item-title'   => __( '🎁 Donate Now', 'stagekitwp-theme' ),
            'menu-item-url'     => get_theme_mod( 'stagekitwp_donate_button_url', home_url( '/donate' ) ),
            'menu-item-type'    => 'custom',
            'menu-item-classes' => 'menu-item-donate-btn',
            'menu-item-status'  => 'publish',
        ) );
    }

    // 4. Populate Footer Menu if empty
    $footer_items = wp_get_nav_menu_items( $footer_menu_id );
    if ( empty( $footer_items ) ) {
        $footer_links = array( 'Tickets', 'Auditions', 'Now Playing', 'Get Involved' );
        
        foreach ( $footer_links as $item_title ) {
            $page_id = stagekitwp_get_or_create_page( $item_title );
            wp_update_nav_menu_item( $footer_menu_id, 0, array(
                'menu-item-title'     => $item_title,
                'menu-item-object'    => 'page',
                'menu-item-object-id' => $page_id,
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
            ) );
        }

        // Append Footer Donate Button
        wp_update_nav_menu_item( $footer_menu_id, 0, array(
            'menu-item-title'   => __( '🎁 Support the Arts', 'stagekitwp-theme' ),
            'menu-item-url'     => get_theme_mod( 'stagekitwp_donate_button_url', home_url( '/donate' ) ),
            'menu-item-type'    => 'custom',
            'menu-item-classes' => 'footer-item-donate-btn',
            'menu-item-status'  => 'publish',
        ) );
    }

    // 5. Explicitly assign structural layout locations with type validation
    $locations = get_theme_mod( 'nav_menu_locations' );
    if ( ! is_array( $locations ) ) {
        $locations = array();
    }

    if ( is_numeric( $primary_menu_id ) ) {
        $locations['primary-menu'] = intval( $primary_menu_id );
    }
    if ( is_numeric( $footer_menu_id ) ) {
        $locations['footer-menu']  = intval( $footer_menu_id );
    }

    set_theme_mod( 'nav_menu_locations', $locations );
}
add_action( 'after_switch_theme', 'stagekitwp_theme_setup_menus_and_pages' );


// =============================================================================
// MODULAR COMPONENT INCLUDE LIFECYCLES
// =============================================================================

// Safe loader for theme setting fields inside Customizer Panel
if ( file_exists( get_template_directory() . '/inc/customizer.php' ) ) {
    require_once get_template_directory() . '/inc/customizer.php';
}

// Safe loader for System Health integration metrics panel inside Theme Details modal
if ( file_exists( get_template_directory() . '/inc/dashboard-health.php' ) ) {
    require_once get_template_directory() . '/inc/dashboard-health.php';
}

/**
 * Customizer Performance Fix: Prevent frontend block engine import map script conflicts 
 * from hanging or freezing the administration preview frame.
 */
function stagekitwp_theme_fix_customizer_import_maps() {
    if ( is_customize_preview() ) {
        remove_action( 'wp_enqueue_scripts', 'wp_enqueue_stored_styles', 1 );
        wp_dequeue_script( 'stagekitwp-board-members-block-editor' );
    }
}
add_action( 'wp_enqueue_scripts', 'stagekitwp_theme_fix_customizer_import_maps', 5 );

// Include the custom theme administration dashboard layout interface
require_once get_template_directory() . '/inc/theme-dashboard.php';

// Per-post / per-page "Show Title" toggle (Post Options / Page Options meta boxes)
require_once get_template_directory() . '/inc/post-page-options.php';


// =============================================================================
// DYNAMIC LIVE MENU OVERRIDES & CUSTOMIZER GENERATORS
// =============================================================================

/**
 * Forcefully Inject CSS Classes into Menus by scanning item labels
 */
function stagekitwp_theme_force_donate_menu_classes( $classes, $item, $args ) {
    // Check if the menu item title contains the word "Donate" or "Support"
    if ( false !== stripos( $item->title, 'Donate' ) || false !== stripos( $item->title, 'Support' ) ) {
        if ( isset( $args->theme_location ) && 'primary-menu' === $args->theme_location ) {
            $classes[] = 'menu-item-donate-btn';
        } else {
            $classes[] = 'footer-item-donate-btn';
        }
    }
    return $classes;
}
add_filter( 'nav_menu_css_class', 'stagekitwp_theme_force_donate_menu_classes', 10, 3 );

/**
 * Use Customizer donate URL for placeholder/default links, but keep admin-set custom links.
 */
function stagekitwp_theme_force_donate_menu_url( $atts, $item, $args ) {
    $item_classes = isset( $item->classes ) && is_array( $item->classes ) ? $item->classes : array();
    $is_donate    = in_array( 'menu-item-donate-btn', $item_classes, true );

    if ( $is_donate && isset( $args->theme_location ) && 'primary-menu' === $args->theme_location ) {
        $configured_url = get_theme_mod( 'stagekitwp_donate_button_url', home_url( '/donate' ) );
        $current_href   = isset( $atts['href'] ) ? trim( (string) $atts['href'] ) : '';
        $default_url    = home_url( '/donate' );

        // Only override legacy placeholders/defaults. Preserve admin-provided custom menu URLs.
        if ( '' === $current_href || '#donate' === $current_href || $default_url === $current_href ) {
            $atts['href'] = esc_url( $configured_url );
        }
    }

    return $atts;
}
add_filter( 'nav_menu_link_attributes', 'stagekitwp_theme_force_donate_menu_url', 10, 3 );

/**
 * Ensure the primary menu always has a donate item even on pre-existing menus.
 */
function stagekitwp_theme_append_primary_donate_item( $items, $args ) {
    if ( ! isset( $args->theme_location ) || 'primary-menu' !== $args->theme_location ) {
        return $items;
    }

    // Respect manually added donate/support items and avoid duplicates.
    foreach ( $items as $existing_item ) {
        $title   = isset( $existing_item->title ) ? wp_strip_all_tags( (string) $existing_item->title ) : '';
        $classes = isset( $existing_item->classes ) && is_array( $existing_item->classes ) ? $existing_item->classes : array();

        if (
            in_array( 'menu-item-donate-btn', $classes, true ) ||
            false !== stripos( $title, 'Donate' ) ||
            false !== stripos( $title, 'Support' )
        ) {
            return $items;
        }
    }

    $donate_url = get_theme_mod( 'stagekitwp_donate_button_url', home_url( '/donate' ) );
    if ( empty( $donate_url ) ) {
        return $items;
    }

    $donate_item = (object) array(
        'ID'                    => 0,
        'db_id'                 => 0,
        'menu_item_parent'      => 0,
        'object_id'             => 0,
        'object'                => 'custom',
        'type'                  => 'custom',
        'type_label'            => __( 'Custom Link', 'stagekitwp-theme' ),
        'title'                 => __( 'Donate', 'stagekitwp-theme' ),
        'url'                   => esc_url( $donate_url ),
        'target'                => '',
        'attr_title'            => '',
        'description'           => '',
        'classes'               => array( 'menu-item', 'menu-item-type-custom', 'menu-item-object-custom', 'menu-item-donate-btn' ),
        'xfn'                   => '',
        'status'                => 'publish',
        'menu_order'            => count( $items ) + 1,
        'post_name'             => 'menu-item-0',
        'post_type'             => 'nav_menu_item',
        'filter'                => 'raw',
        'current'               => false,
        'current_item_ancestor' => false,
        'current_item_parent'   => false,
    );

    $position = get_theme_mod( 'stagekitwp_donate_menu_position', 'end' );
    if ( 'start' === $position ) {
        array_unshift( $items, $donate_item );
        return $items;
    }

    if ( preg_match( '/^after_(\d+)$/', (string) $position, $matches ) ) {
        $after_id = (int) $matches[1];
        foreach ( $items as $index => $existing_item ) {
            if ( isset( $existing_item->ID ) && (int) $existing_item->ID === $after_id ) {
                array_splice( $items, $index + 1, 0, array( $donate_item ) );
                return $items;
            }
        }
    }

    $items[] = $donate_item;

    return $items;
}
add_filter( 'wp_nav_menu_objects', 'stagekitwp_theme_append_primary_donate_item', 20, 2 );

/**
 * Inject Customizer Donate Image CSS Styles into Frontend Head Hook
 */
function stagekitwp_theme_output_customizer_css() {
    $donate_image_url = get_theme_mod( 'stagekitwp_donate_button_image' );

    if ( empty( $donate_image_url ) ) {
        return;
    }
    ?>
    <style type="text/css" id="stagekitwp-customizer-dynamic-css">
        /* ==========================================================================
           MAIN NAVIGATION DONATE REPLACEMENT (Header Background Matching Layout)
           ========================================================================== */
        li.menu-item-donate-btn,
        .menu-item-donate-btn,
        #main-menu li.menu-item-donate-btn,
        .nav-menu li.menu-item-donate-btn,
        .main-navigation ul li.menu-item-donate-btn {
            background: inherit !important;
            background-color: inherit !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            height: 100% !important;
        }

        li.menu-item-donate-btn a,
        .menu-item-donate-btn a,
        li.menu-item-donate-btn > a,
        .main-navigation ul li.menu-item-donate-btn a {
            display: inline-block !important;
            text-indent: -9999px !important;
            background-image: url('<?php echo esc_url( $donate_image_url ); ?>') !important;
            background-size: contain !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            
            height: 1.2em !important; 
            width: 110px !important;  
            
            background-color: inherit !important;
            border: 0 !important;
            border-style: none !important;
            box-shadow: none !important;
            outline: none !important;
            text-decoration: none !important;
            
            padding: 0 !important;
            margin-left: 15px !important;
            margin-right: 15px !important;
            vertical-align: middle !important;
            transition: transform 0.2s ease !important;
        }

        li.menu-item-donate-btn a:hover,
        .menu-item-donate-btn a:hover,
        li.menu-item-donate-btn:hover a,
        .main-navigation ul li.menu-item-donate-btn a:hover {
            background: url('<?php echo esc_url( $donate_image_url ); ?>') no-repeat center/contain !important;
            background-color: inherit !important;
            border: none !important;
            box-shadow: none !important;
            transform: scale(1.05) !important;
        }

        /* ==========================================================================
           FOOTER NAVIGATION DONATE REPLACEMENT
           ========================================================================== */
        li.footer-item-donate-btn,
        .footer-item-donate-btn {
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
        }

        li.footer-item-donate-btn a,
        .footer-item-donate-btn a {
            display: inline-block !important;
            text-indent: -9999px !important;
            background-image: url('<?php echo esc_url( $donate_image_url ); ?>') !important;
            background-size: contain !important;
            background-repeat: no-repeat !important;
            background-position: left center !important;
            
            width: 120px !important;
            height: 30px !important;
            
            background-color: transparent !important;
            border: none !important;
            box-shadow: none !important;
            outline: none !important;
            
            padding: 0 !important;
            margin-top: 10px !important;
            transition: transform 0.2s ease !important;
        }

        .footer-item-donate-btn a:hover {
            transform: scale(1.05) !important;
            background-color: transparent !important;
            box-shadow: none !important;
        }
    </style>
    <?php
}
add_action( 'wp_head', 'stagekitwp_theme_output_customizer_css' );

/**
 * Optional sticky header output from Customizer setting.
 */
function stagekitwp_output_sticky_header_css() {
    if ( ! get_theme_mod( 'stagekitwp_enable_sticky_header', false ) ) {
        return;
    }
    ?>
    <style type="text/css" id="stagekitwp-sticky-header-css">
        #masthead.site-header {
            position: sticky;
            top: 0;
            z-index: 9990;
        }

        .admin-bar #masthead.site-header {
            top: 32px;
        }

        @media (max-width: 782px) {
            .admin-bar #masthead.site-header {
                top: 46px;
            }
        }
    </style>
    <?php
}
add_action( 'wp_head', 'stagekitwp_output_sticky_header_css' );

// postMessage preview bindings are now handled via assets/js/customizer-preview.js
// (enqueued by stagekitwp_theme_customize_preview_js above).

/**
 * Include and Register Custom Theme Widgets
 */

// 1. Safely pull the PHP widget class file into memory
require_once get_template_directory() . '/inc/widgets/class-stagekitwp-theme-social-icons-widget.php';
require_once get_template_directory() . '/inc/widgets/class-stagekitwp-theme-donate-widget.php';

// Color customizer registration is handled in inc/customizer.php via stagekitwp_customize_register_color_panel().
/**
 * Register Testimonial Section Controls inside Customizer
 */
function stagekitwp_customize_register_testimonials( $wp_customize ) {

    // Create a new section if you don't already have a 'Homepage Settings' section
    $wp_customize->add_section( 'stagekitwp_testimonials_section', array(
        'title'    => __( '🎭 Homepage Testimonials', 'stagekitwp-theme' ),
        'priority' => 45,
    ) );

    // 1. Width Setting (Percentage Slider/Number Control)
    $wp_customize->add_setting( 'stagekitwp_testimonials_width', array(
        'default'           => '100',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( 'stagekitwp_testimonials_width', array(
        'label'       => __( 'Section Maximum Width (%)', 'stagekitwp-theme' ),
        'description' => __( 'Shrink the block width container relative to the viewport container.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_testimonials_section',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => '30',
            'max'  => '100',
            'step' => '5',
        ),
    ) );

    // 2. Alignment Setting (Dropdown Control)
    $wp_customize->add_setting( 'stagekitwp_testimonials_alignment', array(
        'default'           => 'center',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( 'stagekitwp_testimonials_alignment', array(
        'label'   => __( 'Section Horizontal Block Alignment', 'stagekitwp-theme' ),
        'section' => 'stagekitwp_testimonials_section',
        'type'    => 'select',
        'choices' => array(
            'left'   => __( 'Align Left', 'stagekitwp-theme' ),
            'center' => __( 'Align Center', 'stagekitwp-theme' ),
            'right'  => __( 'Align Right', 'stagekitwp-theme' ),
        ),
    ) );

    // 3. Rating display toggle
    $wp_customize->add_setting( 'stagekitwp_testimonials_show_rating', array(
        'default'           => true,
        'sanitize_callback' => 'stagekitwp_sanitize_checkbox',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( 'stagekitwp_testimonials_show_rating', array(
        'label'       => __( 'Display Testimonial Rating Symbols', 'stagekitwp-theme' ),
        'description' => __( 'Hide or show stars/rating symbols in testimonial slides.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_testimonials_section',
        'type'        => 'checkbox',
    ) );

    // 4. Rendering mode and layout defaults for homepage testimonials.
    $wp_customize->add_setting( 'stagekitwp_testimonials_mode', array(
        'default'           => 'slider',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_testimonials_mode', array(
        'label'       => __( 'Display Mode', 'stagekitwp-theme' ),
        'description' => __( 'Choose slider, grid, full-page list, per-show, or per-show slider mode.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_testimonials_section',
        'type'        => 'select',
        'choices'     => array(
            'slider'          => __( 'Slider', 'stagekitwp-theme' ),
            'grid'            => __( 'Grid', 'stagekitwp-theme' ),
            'full'            => __( 'Full Page List', 'stagekitwp-theme' ),
            'per_show'        => __( 'Per Show', 'stagekitwp-theme' ),
            'per_show_slider' => __( 'Per Show Slider', 'stagekitwp-theme' ),
        ),
    ) );

    $wp_customize->add_setting( 'stagekitwp_testimonials_reviews_per_show', array(
        'default'           => 4,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_testimonials_reviews_per_show', array(
        'label'       => __( 'Per Show: Reviews Per Show', 'stagekitwp-theme' ),
        'description' => __( 'Used only when Display Mode is set to Per Show. Max reviews shown for each show.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_testimonials_section',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 1,
            'max'  => 20,
            'step' => 1,
        ),
    ) );

    $wp_customize->add_setting( 'stagekitwp_testimonials_review_align', array(
        'default'           => 'left',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_testimonials_review_align', array(
        'label'       => __( 'Per Show: Review Alignment', 'stagekitwp-theme' ),
        'description' => __( 'Used only when Display Mode is set to Per Show. Alternating cycles right, left, center.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_testimonials_section',
        'type'        => 'select',
        'choices'     => array(
            'left'        => __( 'Left', 'stagekitwp-theme' ),
            'center'      => __( 'Center', 'stagekitwp-theme' ),
            'right'       => __( 'Right', 'stagekitwp-theme' ),
            'alternating' => __( 'Alternating (right, left, center)', 'stagekitwp-theme' ),
            'alternating_lr' => __( 'Alternating (left, right)', 'stagekitwp-theme' ),
        ),
    ) );

    $wp_customize->add_setting( 'stagekitwp_testimonials_layout', array(
        'default'           => 'classic',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_testimonials_layout', array(
        'label'       => __( 'Card Layout', 'stagekitwp-theme' ),
        'description' => __( 'Select the testimonial card style.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_testimonials_section',
        'type'        => 'select',
        'choices'     => array(
            'classic'   => __( 'Classic', 'stagekitwp-theme' ),
            'quote'     => __( 'Quote', 'stagekitwp-theme' ),
            'minimal'   => __( 'Minimal', 'stagekitwp-theme' ),
            'spotlight' => __( 'Spotlight', 'stagekitwp-theme' ),
        ),
    ) );

    $wp_customize->add_setting( 'stagekitwp_testimonials_columns', array(
        'default'           => 3,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_testimonials_columns', array(
        'label'       => __( 'Grid Columns', 'stagekitwp-theme' ),
        'description' => __( 'Used when mode is set to grid. Allowed range: 1-4.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_testimonials_section',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 1,
            'max'  => 4,
            'step' => 1,
        ),
    ) );

    $wp_customize->add_setting( 'stagekitwp_testimonials_limit', array(
        'default'           => 6,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_testimonials_limit', array(
        'label'       => __( 'Number of Testimonials', 'stagekitwp-theme' ),
        'description' => __( 'Set how many testimonials to show on the homepage section.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_testimonials_section',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 1,
            'max'  => 30,
            'step' => 1,
        ),
    ) );

    // 5. Strict testimonial image controls and optional text overlay mode.
    $wp_customize->add_setting( 'stagekitwp_testimonials_image_width', array(
        'default'           => 520,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_testimonials_image_width', array(
        'label'       => __( 'Image Width (px)', 'stagekitwp-theme' ),
        'description' => __( 'Maximum media width used by testimonial layouts.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_testimonials_section',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 200,
            'max'  => 1400,
            'step' => 10,
        ),
    ) );

    $wp_customize->add_setting( 'stagekitwp_testimonials_image_height', array(
        'default'           => 280,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_testimonials_image_height', array(
        'label'       => __( 'Image Height (px)', 'stagekitwp-theme' ),
        'description' => __( 'Fixed media height used by testimonial layouts.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_testimonials_section',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 120,
            'max'  => 1200,
            'step' => 10,
        ),
    ) );

    $wp_customize->add_setting( 'stagekitwp_testimonials_image_fit', array(
        'default'           => 'cover',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_testimonials_image_fit', array(
        'label'   => __( 'Image Fit', 'stagekitwp-theme' ),
        'section' => 'stagekitwp_testimonials_section',
        'type'    => 'select',
        'choices' => array(
            'cover'   => __( 'Cover', 'stagekitwp-theme' ),
            'contain' => __( 'Contain', 'stagekitwp-theme' ),
        ),
    ) );

    $wp_customize->add_setting( 'stagekitwp_testimonials_image_position', array(
        'default'           => 'center',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_testimonials_image_position', array(
        'label'   => __( 'Image Position', 'stagekitwp-theme' ),
        'section' => 'stagekitwp_testimonials_section',
        'type'    => 'select',
        'choices' => array(
            'center' => __( 'Centered', 'stagekitwp-theme' ),
            'left'   => __( 'Left', 'stagekitwp-theme' ),
            'right'  => __( 'Right', 'stagekitwp-theme' ),
            'top'    => __( 'Top', 'stagekitwp-theme' ),
        ),
    ) );

    $wp_customize->add_setting( 'stagekitwp_testimonials_image_focus', array(
        'default'           => 'center_center',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_testimonials_image_focus', array(
        'label'   => __( 'Image Focus', 'stagekitwp-theme' ),
        'section' => 'stagekitwp_testimonials_section',
        'type'    => 'select',
        'choices' => array(
            'center_center' => __( 'Center', 'stagekitwp-theme' ),
            'center_top'    => __( 'Top', 'stagekitwp-theme' ),
            'center_bottom' => __( 'Bottom', 'stagekitwp-theme' ),
            'left_center'   => __( 'Left', 'stagekitwp-theme' ),
            'right_center'  => __( 'Right', 'stagekitwp-theme' ),
        ),
    ) );

    $wp_customize->add_setting( 'stagekitwp_testimonials_text_overlay', array(
        'default'           => false,
        'sanitize_callback' => 'stagekitwp_sanitize_checkbox',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_testimonials_text_overlay', array(
        'label'       => __( 'Overlay Text On Image', 'stagekitwp-theme' ),
        'description' => __( 'When enabled, testimonial content is rendered over the image.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_testimonials_section',
        'type'        => 'checkbox',
    ) );

    $wp_customize->add_setting( 'stagekitwp_testimonials_image_opacity', array(
        'default'           => '0.45',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_testimonials_image_opacity', array(
        'label'       => __( 'Overlay Image Opacity (0.1 - 1)', 'stagekitwp-theme' ),
        'description' => __( 'Used only when text overlay mode is enabled.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_testimonials_section',
        'type'        => 'text',
    ) );

    // 6. Field visibility toggles.
    $visibility_settings = array(
        'stagekitwp_testimonials_show_name'    => __( 'Show Name', 'stagekitwp-theme' ),
        'stagekitwp_testimonials_show_comment' => __( 'Show Comment', 'stagekitwp-theme' ),
        'stagekitwp_testimonials_show_show'    => __( 'Show Linked Show', 'stagekitwp-theme' ),
        'stagekitwp_testimonials_show_date'    => __( 'Show Date', 'stagekitwp-theme' ),
        'stagekitwp_testimonials_show_media'   => __( 'Show Media', 'stagekitwp-theme' ),
    );

    $wp_customize->add_setting( 'stagekitwp_testimonials_show_name_placement', array(
        'default'           => 'meta',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_testimonials_show_name_placement', array(
        'label'       => __( 'Show Name Placement', 'stagekitwp-theme' ),
        'description' => __( 'Choose where the linked show title appears in each testimonial card.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_testimonials_section',
        'type'        => 'select',
        'choices'     => array(
            'meta'         => __( 'Meta Line', 'stagekitwp-theme' ),
            'header'       => __( 'Header Above Content', 'stagekitwp-theme' ),
            'slug'         => __( 'Bullet Slug Above Content', 'stagekitwp-theme' ),
            'image_indent' => __( 'Indented Tag On Image', 'stagekitwp-theme' ),
        ),
    ) );

    $wp_customize->add_setting( 'stagekitwp_testimonials_tag_icon_source', array(
        'default'           => 'none',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_testimonials_tag_icon_source', array(
        'label'       => __( 'Tag Icon Source', 'stagekitwp-theme' ),
        'description' => __( 'Use fallback icon, site icon, Miltonman image, or custom URL for show-name tags.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_testimonials_section',
        'type'        => 'select',
        'choices'     => array(
            'none'      => __( 'Fallback Icon', 'stagekitwp-theme' ),
            'site_icon' => __( 'Site Icon', 'stagekitwp-theme' ),
            'miltonman' => __( 'Miltonman Image', 'stagekitwp-theme' ),
            'custom'    => __( 'Custom URL', 'stagekitwp-theme' ),
        ),
    ) );

    $wp_customize->add_setting( 'stagekitwp_testimonials_tag_icon_url', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_testimonials_tag_icon_url', array(
        'label'       => __( 'Custom Tag Icon URL', 'stagekitwp-theme' ),
        'description' => __( 'Used only when Tag Icon Source is set to Custom URL.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_testimonials_section',
        'type'        => 'text',
    ) );

    $wp_customize->add_setting( 'stagekitwp_testimonials_tag_icon_size', array(
        'default'           => 18,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_testimonials_tag_icon_size', array(
        'label'       => __( 'Tag Icon Size (px)', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_testimonials_section',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 12,
            'max'  => 48,
            'step' => 1,
        ),
    ) );

    foreach ( $visibility_settings as $setting_id => $label ) {
        $wp_customize->add_setting( $setting_id, array(
            'default'           => true,
            'sanitize_callback' => 'stagekitwp_sanitize_checkbox',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( $setting_id, array(
            'label'   => $label,
            'section' => 'stagekitwp_testimonials_section',
            'type'    => 'checkbox',
        ) );
    }
}
add_action( 'customize_register', 'stagekitwp_customize_register_testimonials' );

/**
 * Inject all customizer color tokens as CSS custom properties and targeted rules.
 * Covers: global palette, typography, interactive elements, and per-zone overrides.
 */
function stagekitwp_inject_customizer_css_variables() {
    // Only output on frontend. Customizer preview also needs it for live-preview.
    if ( is_admin() && ! is_customize_preview() ) { return; }

    // ── Global Branding & Palette ─────────────────────────────────────────────
    $primary_accent   = get_theme_mod( 'stagekitwp_color_primary_accent',   '#e50914' );
    $secondary_accent = get_theme_mod( 'stagekitwp_color_secondary_accent', '#b8070f' );
    $base_bg          = get_theme_mod( 'stagekitwp_color_base_bg',          '#ffffff' );
    $surface_bg       = get_theme_mod( 'stagekitwp_color_surface_bg',       '#f8f9fa' );

    // ── Typography Colors ────────────────────────────────────────────────────
    $heading_text     = get_theme_mod( 'stagekitwp_color_heading_text', '#111111' );
    $body_text        = get_theme_mod( 'stagekitwp_color_body_text',    '#333333' );
    $muted_text       = get_theme_mod( 'stagekitwp_color_muted_text',   '#6c757d' );

    // ── Interactive / Link Colors ────────────────────────────────────────────
    $link_color       = get_theme_mod( 'stagekitwp_color_link',       '#0073aa' );
    $link_hover       = get_theme_mod( 'stagekitwp_color_link_hover', '#e50914' );

    // ── Zone Overrides ───────────────────────────────────────────────────────
    $notif_bg         = get_theme_mod( 'stagekitwp_color_notif_bg',     '#111111' );
    $notif_text       = get_theme_mod( 'stagekitwp_color_notif_text',   '#ffffff' );
    $header_bg        = get_theme_mod( 'stagekitwp_color_header_bg',    '#ffffff' );
    $header_text      = get_theme_mod( 'stagekitwp_color_header_text',  '#444444' );
    $header_hover     = get_theme_mod( 'stagekitwp_color_header_hover', '#e50914' );
    $footer_bg        = get_theme_mod( 'stagekitwp_color_footer_bg',    '#111111' );
    $footer_text      = get_theme_mod( 'stagekitwp_color_footer_text',  '#cccccc' );

    // ── Hero text colours ────────────────────────────────────────────────────
    $hero_title_light    = get_theme_mod( 'stagekitwp_hero_title_color_light',    '#ffffff' );
    $hero_title_dark     = get_theme_mod( 'stagekitwp_hero_title_color_dark',     '#ffffff' );
    $hero_subtitle_light = get_theme_mod( 'stagekitwp_hero_subtitle_color_light', '#dddddd' );
    $hero_subtitle_dark  = get_theme_mod( 'stagekitwp_hero_subtitle_color_dark',  '#dddddd' );

    // ── Layout ────────────────────────────────────────────────────────────────
    $site_max_width   = absint( get_theme_mod( 'stagekitwp_site_max_width', 1200 ) );
    if ( $site_max_width < 960 || $site_max_width > 1920 ) {
        $site_max_width = 1200;
    }

    // ── Legacy compat: keep old tokens pointing to new values ─────────────────
    $legacy_primary    = get_theme_mod( 'stagekitwp_primary_color',    $primary_accent );
    $legacy_accent     = get_theme_mod( 'stagekitwp_accent_color',     $link_color );
    $legacy_bg         = get_theme_mod( 'stagekitwp_bg_color',         $footer_bg );
    $legacy_text_light = get_theme_mod( 'stagekitwp_text_light_color', $notif_text );
    ?>
    <style type="text/css" id="stagekitwp-customizer-variables">
        /* ============================================================
           CSS CUSTOM PROPERTIES — generated by StageKitWP Theme
           ============================================================ */
        :root {
            /* Global palette */
            --stagekitwp-primary:          <?php echo esc_attr( $primary_accent ); ?>;
            --stagekitwp-secondary:        <?php echo esc_attr( $secondary_accent ); ?>;
            --stagekitwp-base-bg:          <?php echo esc_attr( $base_bg ); ?>;
            --stagekitwp-surface-bg:       <?php echo esc_attr( $surface_bg ); ?>;

            /* Typography */
            --stagekitwp-heading-text:     <?php echo esc_attr( $heading_text ); ?>;
            --stagekitwp-body-text:        <?php echo esc_attr( $body_text ); ?>;
            --stagekitwp-muted-text:       <?php echo esc_attr( $muted_text ); ?>;

            /* Interactive */
            --stagekitwp-link:             <?php echo esc_attr( $link_color ); ?>;
            --stagekitwp-link-hover:       <?php echo esc_attr( $link_hover ); ?>;

            /* Zone overrides */
            --stagekitwp-notif-bg:         <?php echo esc_attr( $notif_bg ); ?>;
            --stagekitwp-notif-text:       <?php echo esc_attr( $notif_text ); ?>;
            --stagekitwp-header-bg:        <?php echo esc_attr( $header_bg ); ?>;
            --stagekitwp-header-text:      <?php echo esc_attr( $header_text ); ?>;
            --stagekitwp-header-hover:     <?php echo esc_attr( $header_hover ); ?>;
            --stagekitwp-footer-bg:        <?php echo esc_attr( $footer_bg ); ?>;
            --stagekitwp-footer-text:      <?php echo esc_attr( $footer_text ); ?>;

            /* Hero text colours */
            --stagekitwp-hero-title-light:    <?php echo esc_attr( $hero_title_light ); ?>;
            --stagekitwp-hero-title-dark:     <?php echo esc_attr( $hero_title_dark ); ?>;
            --stagekitwp-hero-subtitle-light: <?php echo esc_attr( $hero_subtitle_light ); ?>;
            --stagekitwp-hero-subtitle-dark:  <?php echo esc_attr( $hero_subtitle_dark ); ?>;

            /* Legacy aliases so existing code keeps working */
            --stagekitwp-accent:           <?php echo esc_attr( $legacy_accent ); ?>;
            --stagekitwp-bg:               <?php echo esc_attr( $legacy_bg ); ?>;
            --stagekitwp-text-light:       <?php echo esc_attr( $legacy_text_light ); ?>;

            /* Layout */
            --stagekitwp-site-max-width:   <?php echo esc_attr( $site_max_width ); ?>px;
        }

        /* ── Global canvas ── */
        body {
            background-color: var(--stagekitwp-base-bg);
            color: var(--stagekitwp-body-text);
        }

        /* ── Headings ── */
        h1, h2, h3, h4, h5, h6 {
            color: var(--stagekitwp-heading-text);
        }

        /* ── Inline links — scoped to content areas only.
           Bare "a" reset was removed to prevent bleeding into the header,
           admin bar, and widget areas. Nav/header links are handled below
           with explicit selectors. ── */
        .entry-content a,
        .widget-area a,
        .wp-block-group a,
        .wp-block-column a,
        main a:not(.site-header a):not(.site-footer a):not(.wp-block-button__link) {
            color: var(--stagekitwp-link);
        }
        .entry-content a:hover,
        .widget-area a:hover,
        .wp-block-group a:hover,
        .wp-block-column a:hover,
        main a:not(.site-header a):not(.site-footer a):not(.wp-block-button__link):hover,
        main a:not(.site-header a):not(.site-footer a):not(.wp-block-button__link):focus {
            color: var(--stagekitwp-link-hover);
        }

        /* ── Notification bar ── */
        .stagekitwp-top-notification-bar {
            background-color: var(--stagekitwp-notif-bg) !important;
            color: var(--stagekitwp-notif-text) !important;
            border-bottom-color: var(--stagekitwp-primary) !important;
        }
        .stagekitwp-top-notification-bar .notification-text {
            display: inline-grid;
            grid-template-columns: auto minmax(0, 1fr);
            align-items: center;
            column-gap: 8px;
            row-gap: 4px;
            width: auto;
            max-width: 100%;
        }
        .stagekitwp-top-notification-bar .stagekitwp-notification-label {
            display: inline-flex;
            justify-self: start;
            align-items: center;
            white-space: nowrap;
        }
        .stagekitwp-top-notification-bar .stagekitwp-countdown-copy {
            min-width: 0;
            text-align: left;
            overflow-wrap: anywhere;
        }
        @media (max-width: 520px) {
            .stagekitwp-top-notification-bar .notification-text {
                display: grid;
                width: 100%;
                grid-template-columns: 1fr;
                justify-items: center;
                text-align: center;
            }
            .stagekitwp-top-notification-bar .stagekitwp-notification-label {
                justify-self: center;
            }
            .stagekitwp-top-notification-bar .stagekitwp-countdown-copy {
                text-align: center;
            }
        }
        .stagekitwp-top-notification-bar * {
            color: var(--stagekitwp-notif-text) !important;
        }

        /* ── Site header ── */
        .site-header {
            background-color: var(--stagekitwp-header-bg) !important;
        }
        .site-header .site-title a,
        .site-header .stagekitwp-primary-menu-list li a,
        .site-header .site-branding a,
        .site-header .custom-theme-logo-link,
        .site-header .stagekitwp-logo-wrap {
            color: var(--stagekitwp-header-text) !important;
        }
        .site-header .stagekitwp-primary-menu-list li a:hover,
        .site-header .stagekitwp-primary-menu-list li.current-menu-item > a,
        .site-header .stagekitwp-primary-menu-list li.current-menu-ancestor > a {
            color: var(--stagekitwp-header-hover) !important;
        }
        /* Dropdown sub-menus inherit header bg */
        .stagekitwp-primary-menu-list li ul {
            background-color: var(--stagekitwp-header-bg) !important;
            border-top-color: var(--stagekitwp-primary) !important;
        }
        .stagekitwp-primary-menu-list li ul li a {
            color: var(--stagekitwp-header-text) !important;
        }
        .stagekitwp-primary-menu-list li ul li a:hover {
            color: var(--stagekitwp-header-hover) !important;
        }

        /* ── Site footer ── */
        .site-footer {
            background-color: var(--stagekitwp-footer-bg) !important;
            color: var(--stagekitwp-footer-text) !important;
        }
        .site-footer a,
        .stagekitwp-footer-widget a,
        .widget_nav_menu a {
            color: var(--stagekitwp-footer-text) !important;
        }
        .site-footer a:hover,
        .stagekitwp-footer-widget a:hover,
        .widget_nav_menu a:hover {
            color: var(--stagekitwp-primary) !important;
        }
        /* All heading levels inside footer widgets — no inline colour set,
           so they fall to browser default black without this rule. */
        .site-footer h1, .site-footer h2, .site-footer h3,
        .site-footer h4, .site-footer h5, .site-footer h6,
        .site-footer p, .site-footer li, .site-footer label,
        .site-footer .wp-block-heading {
            color: var(--stagekitwp-footer-text) !important;
        }
        .site-footer h4.widget-title {
            color: var(--stagekitwp-footer-text) !important;
            opacity: 0.85;
        }
        .site-footer hr {
            border-top-color: rgba(204, 204, 204, 0.20) !important;
        }
        .stagekitwp-to-top-link {
            color: var(--stagekitwp-primary) !important;
        }
        /* Socket bar copyright — the h3 has no inline colour so it falls to
           browser default black; pin it to the footer text colour instead. */
        .stagekitwp-socket-widget,
        .stagekitwp-socket-widget h1,
        .stagekitwp-socket-widget h2,
        .stagekitwp-socket-widget h3,
        .stagekitwp-socket-widget h4,
        .stagekitwp-socket-widget h5,
        .stagekitwp-socket-widget h6,
        .stagekitwp-socket-widget p {
            color: var(--stagekitwp-footer-text) !important;
        }
        /* Advertiser tiles have background-color: #111111 hardcoded inline;
           override to transparent so they sit cleanly on the footer bg. */
        .stagekitwp-advertiser-entry {
            background-color: transparent !important;
            border: 1px solid rgba(204, 204, 204, 0.20) !important;
        }

        /* ── Surface-background areas (cards, upcoming section) ── */
        .stagekitwp-upcoming-grid-section {
            background-color: var(--stagekitwp-surface-bg) !important;
            border-top-color: rgba(51, 51, 51, 0.10) !important;
            border-bottom-color: rgba(51, 51, 51, 0.10) !important;
        }
        .stagekitwp-show-card-column {
            background-color: var(--stagekitwp-surface-bg) !important;
        }

        /* ── Muted / meta text ── */
        .show-card-body p,
        .stagekitwp-homepage-news-feed p {
            color: var(--stagekitwp-muted-text) !important;
        }

        /* ── Hero text colours (light mode) ── */
        .stagekitwp-hero-banner .stagekitwp-hero-title   { color: var(--stagekitwp-hero-title-light)    !important; }
        .stagekitwp-hero-banner .stagekitwp-hero-subtitle { color: var(--stagekitwp-hero-subtitle-light) !important; }

        /* ── Primary accent: buttons, badges, dividers ── */
        .button[style*="background: #e50914"],
        .button[style*="background:#e50914"] {
            background-color: var(--stagekitwp-primary) !important;
        }
        .term-badge[style*="background: #111111"] {
            background-color: var(--stagekitwp-heading-text) !important;
        }
        .stagekitwp-social-link:hover {
            color: var(--stagekitwp-primary) !important;
            opacity: 0.85 !important;
        }
    </style>
    <?php
}
add_action( 'wp_head', 'stagekitwp_inject_customizer_css_variables' );

/**
 * Output dark-mode CSS.
 * ALL 14 tokens set in html.stagekitwp-dark-mode {} from Customizer *_dark settings.
 * Every rule below references var(--stagekitwp-*) only — never hardcoded hex.
 */
function stagekitwp_output_dark_mode_css() {
    if ( is_admin() && ! is_customize_preview() ) { return; }

    $mode = get_theme_mod( 'stagekitwp_color_mode', 'light' );

    $d = array(
        'base_bg'      => get_theme_mod( 'stagekitwp_color_base_bg_dark',      '#121212' ),
        'surface_bg'   => get_theme_mod( 'stagekitwp_color_surface_bg_dark',   '#1e1e1e' ),
        'heading_text' => get_theme_mod( 'stagekitwp_color_heading_text_dark', '#ffffff'  ),
        'body_text'    => get_theme_mod( 'stagekitwp_color_body_text_dark',    '#e0e0e0' ),
        'muted_text'   => get_theme_mod( 'stagekitwp_color_muted_text_dark',   '#9e9e9e' ),
        'link'         => get_theme_mod( 'stagekitwp_color_link_dark',         '#90caf9' ),
        'link_hover'   => get_theme_mod( 'stagekitwp_color_link_hover_dark',   '#e50914' ),
        'notif_bg'     => get_theme_mod( 'stagekitwp_color_notif_bg_dark',     '#111111' ),
        'notif_text'   => get_theme_mod( 'stagekitwp_color_notif_text_dark',   '#ffffff' ),
        'header_bg'    => get_theme_mod( 'stagekitwp_color_header_bg_dark',    '#1e1e1e' ),
        'header_text'  => get_theme_mod( 'stagekitwp_color_header_text_dark',  '#e0e0e0' ),
        'header_hover' => get_theme_mod( 'stagekitwp_color_header_hover_dark', '#e50914' ),
        'footer_bg'    => get_theme_mod( 'stagekitwp_color_footer_bg_dark',    '#111111' ),
        'footer_text'  => get_theme_mod( 'stagekitwp_color_footer_text_dark',  '#cccccc' ),
    );
    ?>
    <style type="text/css" id="stagekitwp-dark-mode-css">

        /* Smooth transitions */
        *, *::before, *::after {
            transition: background-color 0.25s ease, background 0.25s ease,
                        color 0.2s ease, border-color 0.2s ease !important;
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition: none !important; }
        }

        /* ALL 14 tokens — every dark rule below uses var(--stagekitwp-*) only */
        html.stagekitwp-dark-mode {
            --stagekitwp-base-bg:      <?php echo esc_attr( $d['base_bg']      ); ?>;
            --stagekitwp-surface-bg:   <?php echo esc_attr( $d['surface_bg']   ); ?>;
            --stagekitwp-heading-text: <?php echo esc_attr( $d['heading_text'] ); ?>;
            --stagekitwp-body-text:    <?php echo esc_attr( $d['body_text']    ); ?>;
            --stagekitwp-muted-text:   <?php echo esc_attr( $d['muted_text']   ); ?>;
            --stagekitwp-link:         <?php echo esc_attr( $d['link']         ); ?>;
            --stagekitwp-link-hover:   <?php echo esc_attr( $d['link_hover']   ); ?>;
            --stagekitwp-notif-bg:     <?php echo esc_attr( $d['notif_bg']     ); ?>;
            --stagekitwp-notif-text:   <?php echo esc_attr( $d['notif_text']   ); ?>;
            --stagekitwp-header-bg:    <?php echo esc_attr( $d['header_bg']    ); ?>;
            --stagekitwp-header-text:  <?php echo esc_attr( $d['header_text']  ); ?>;
            --stagekitwp-header-hover: <?php echo esc_attr( $d['header_hover'] ); ?>;
            --stagekitwp-footer-bg:    <?php echo esc_attr( $d['footer_bg']    ); ?>;
            --stagekitwp-footer-text:  <?php echo esc_attr( $d['footer_text']  ); ?>;
        }

        /* Canvas */
        html.stagekitwp-dark-mode body, html.stagekitwp-dark-mode #page,
        html.stagekitwp-dark-mode .site-content, html.stagekitwp-dark-mode #primary,
        html.stagekitwp-dark-mode .site-main-homepage, html.stagekitwp-dark-mode .stagekitwp-season-grid-section,
        html.stagekitwp-dark-mode .stagekitwp-homepage-news-feed, html.stagekitwp-dark-mode .stagekitwp-homepage-news-feed[style] {
            background-color: var(--stagekitwp-base-bg)  !important;
            color:            var(--stagekitwp-body-text) !important;
        }

        /* Headings — scoped to content areas; never bleeds into admin bar,
           header, footer, or hero zones. */
        html.stagekitwp-dark-mode .entry-content h1, html.stagekitwp-dark-mode .entry-content h2,
        html.stagekitwp-dark-mode .entry-content h3, html.stagekitwp-dark-mode .entry-content h4,
        html.stagekitwp-dark-mode .entry-content h5, html.stagekitwp-dark-mode .entry-content h6,
        html.stagekitwp-dark-mode .widget-area h1, html.stagekitwp-dark-mode .widget-area h2,
        html.stagekitwp-dark-mode .widget-area h3, html.stagekitwp-dark-mode .widget-area h4,
        html.stagekitwp-dark-mode .widget-area h5, html.stagekitwp-dark-mode .widget-area h6,
        html.stagekitwp-dark-mode .wp-block-group h1, html.stagekitwp-dark-mode .wp-block-group h2,
        html.stagekitwp-dark-mode .wp-block-group h3, html.stagekitwp-dark-mode .wp-block-group h4,
        html.stagekitwp-dark-mode .wp-block-group h5, html.stagekitwp-dark-mode .wp-block-group h6,
        html.stagekitwp-dark-mode main h1, html.stagekitwp-dark-mode main h2,
        html.stagekitwp-dark-mode main h3, html.stagekitwp-dark-mode main h4,
        html.stagekitwp-dark-mode main h5, html.stagekitwp-dark-mode main h6 {
            color: var(--stagekitwp-heading-text) !important;
        }

        /* Body text — scoped to content areas; never bleeds into admin bar,
           header, footer, or hero zones. */
        html.stagekitwp-dark-mode .entry-content p,
        html.stagekitwp-dark-mode .entry-content li,
        html.stagekitwp-dark-mode .widget-area p,
        html.stagekitwp-dark-mode .widget-area li,
        html.stagekitwp-dark-mode .wp-block-group p,
        html.stagekitwp-dark-mode .wp-block-group li,
        html.stagekitwp-dark-mode .wp-block-column p,
        html.stagekitwp-dark-mode .wp-block-column li,
        html.stagekitwp-dark-mode main p,
        html.stagekitwp-dark-mode main li {
            color: var(--stagekitwp-body-text) !important;
        }
        /* Dark-mode inline links — scoped to content areas only, never
           bleeding into the admin bar, site header, footer, or hero zones.
           Mirrors the light-mode selector set; no :not(X descendant) tricks. */
        html.stagekitwp-dark-mode .entry-content a,
        html.stagekitwp-dark-mode .widget-area a,
        html.stagekitwp-dark-mode .wp-block-group a,
        html.stagekitwp-dark-mode .wp-block-column a,
        html.stagekitwp-dark-mode main a {
            color: var(--stagekitwp-link) !important;
        }
        html.stagekitwp-dark-mode .entry-content a:hover,
        html.stagekitwp-dark-mode .widget-area a:hover,
        html.stagekitwp-dark-mode .wp-block-group a:hover,
        html.stagekitwp-dark-mode .wp-block-column a:hover,
        html.stagekitwp-dark-mode main a:hover,
        html.stagekitwp-dark-mode main a:focus {
            color: var(--stagekitwp-link-hover) !important;
        }

        /* Page template */
        html.stagekitwp-dark-mode .entry-title { color: var(--stagekitwp-heading-text) !important; }
        html.stagekitwp-dark-mode .entry-content,
        html.stagekitwp-dark-mode .entry-content p, html.stagekitwp-dark-mode .entry-content li,
        html.stagekitwp-dark-mode .entry-content td, html.stagekitwp-dark-mode .entry-content th {
            color: var(--stagekitwp-body-text) !important;
        }
        html.stagekitwp-dark-mode .entry-content h1, html.stagekitwp-dark-mode .entry-content h2,
        html.stagekitwp-dark-mode .entry-content h3, html.stagekitwp-dark-mode .entry-content h4,
        html.stagekitwp-dark-mode .entry-content h5, html.stagekitwp-dark-mode .entry-content h6 {
            color: var(--stagekitwp-heading-text) !important;
        }
        html.stagekitwp-dark-mode .entry-content a       { color: var(--stagekitwp-link)       !important; }
        html.stagekitwp-dark-mode .entry-content a:hover { color: var(--stagekitwp-link-hover) !important; }

        /* Contact Form 7 — dark mode overrides
           In light mode, CF7 elements already read var(--stagekitwp-body-text) from style.css.
           In dark mode the --stagekitwp-* variables are re-declared on html.stagekitwp-dark-mode
           so these rules simply re-assert them with !important to beat any
           plugin stylesheet or hardcoded colour the user may have added. */
        html.stagekitwp-dark-mode .wpcf7 form label,
        html.stagekitwp-dark-mode .wpcf7 form .wpcf7-form-control-wrap {
            color: var(--stagekitwp-body-text) !important;
        }
        html.stagekitwp-dark-mode .wpcf7 input[type="text"],
        html.stagekitwp-dark-mode .wpcf7 input[type="email"],
        html.stagekitwp-dark-mode .wpcf7 input[type="tel"],
        html.stagekitwp-dark-mode .wpcf7 input[type="url"],
        html.stagekitwp-dark-mode .wpcf7 input[type="number"],
        html.stagekitwp-dark-mode .wpcf7 input[type="date"],
        html.stagekitwp-dark-mode .wpcf7 textarea,
        html.stagekitwp-dark-mode .wpcf7 select {
            background-color: var(--stagekitwp-surface-bg) !important;
            color:            var(--stagekitwp-body-text)  !important;
            border-color: rgba(224, 224, 224, 0.30) !important;
        }
        html.stagekitwp-dark-mode .wpcf7 input[type="submit"] {
            background-color: var(--stagekitwp-primary) !important;
            color: #ffffff !important;
        }

        /* Header — override both background-color and background shorthand
           to defeat the inline style="background: #xxxxxx" attribute */
        html.stagekitwp-dark-mode .site-header, html.stagekitwp-dark-mode #masthead {
            background:       var(--stagekitwp-header-bg) !important;
            background-color: var(--stagekitwp-header-bg) !important;
            border-bottom-color: rgba(224, 224, 224, 0.15) !important;
        }
        html.stagekitwp-dark-mode .site-title a, html.stagekitwp-dark-mode .site-title a[style] {
            color: var(--stagekitwp-header-text) !important;
        }
        /* Hamburger and dropdown arrow: always pure white in dark mode
           so they remain visible regardless of header background colour */
        html.stagekitwp-dark-mode .menu-toggle,
        html.stagekitwp-dark-mode .dropdown-toggle-btn {
            color: #ffffff !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-primary-menu-list li a {
            color: var(--stagekitwp-header-text) !important;
            border-bottom-color: rgba(255,255,255,0.08) !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-primary-menu-list li a:hover,
        html.stagekitwp-dark-mode .stagekitwp-primary-menu-list li.current-menu-item > a,
        html.stagekitwp-dark-mode .stagekitwp-primary-menu-list li.current-menu-ancestor > a {
            color: var(--stagekitwp-header-hover) !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-primary-menu-list li ul {
            background-color: var(--stagekitwp-header-bg) !important;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4) !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-primary-menu-list li ul li a {
            color: var(--stagekitwp-body-text) !important;
            border-bottom-color: rgba(255,255,255,0.06) !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-primary-menu-list li ul li a:hover {
            color: var(--stagekitwp-header-hover) !important;
        }

        /* Show cards */
        html.stagekitwp-dark-mode .stagekitwp-show-card-column, html.stagekitwp-dark-mode .stagekitwp-show-card-column[style] {
            background: var(--stagekitwp-surface-bg) !important;
            background-color: var(--stagekitwp-surface-bg) !important;
            border-color: rgba(224, 224, 224, 0.20) !important;
        }
        html.stagekitwp-dark-mode .show-card-body h3 a, html.stagekitwp-dark-mode .show-card-body h3 a[style] {
            color: var(--stagekitwp-heading-text) !important;
        }
        html.stagekitwp-dark-mode .show-card-body p[style], html.stagekitwp-dark-mode .show-card-body p,
        html.stagekitwp-dark-mode .show-card-body div[style] { color: var(--stagekitwp-muted-text) !important; }
        html.stagekitwp-dark-mode .show-card-body span[style*="background"] {
            background: rgba(224, 224, 224, 0.12) !important;
            color: var(--stagekitwp-muted-text) !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-show-card-column div[style*="padding: 70px"],
        html.stagekitwp-dark-mode .stagekitwp-show-card-column div[style*="padding: 70px"] p {
            color: var(--stagekitwp-muted-text) !important;
        }

        /* Upcoming section */
        html.stagekitwp-dark-mode .stagekitwp-upcoming-grid-section, html.stagekitwp-dark-mode .stagekitwp-upcoming-grid-section[style] {
            background-color: var(--stagekitwp-surface-bg) !important;
            border-top-color:    rgba(224, 224, 224, 0.15) !important;
            border-bottom-color: rgba(224, 224, 224, 0.15) !important;
        }

        /* News cards */
        html.stagekitwp-dark-mode .stagekitwp-homepage-news-feed > div > div,
        html.stagekitwp-dark-mode .stagekitwp-homepage-news-feed div[style*="background"] {
            background: var(--stagekitwp-surface-bg) !important;
            background-color: var(--stagekitwp-surface-bg) !important;
            border-color: rgba(224, 224, 224, 0.20) !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-homepage-news-feed h3 a[style],
        html.stagekitwp-dark-mode .stagekitwp-homepage-news-feed h3 a { color: var(--stagekitwp-heading-text) !important; }
        html.stagekitwp-dark-mode .stagekitwp-homepage-news-feed p[style],
        html.stagekitwp-dark-mode .stagekitwp-homepage-news-feed p    { color: var(--stagekitwp-muted-text)   !important; }

        /* Testimonial cards */
        html.stagekitwp-dark-mode .stagekitwp-testimonial .stagekitwp-symbol-filled,
        html.stagekitwp-dark-mode .stagekitwp-testimonial .stagekitwp-symbol-empty { color: #e8c84a !important; }
        html.stagekitwp-dark-mode .stagekitwp-testimonial .stagekitwp-testimonial-author { color: var(--stagekitwp-muted-text) !important; }

        /* Footer — always-dark zone */
        html.stagekitwp-dark-mode .site-footer, html.stagekitwp-dark-mode #colophon {
            background-color: var(--stagekitwp-footer-bg)  !important;
            color:            var(--stagekitwp-footer-text) !important;
        }
        html.stagekitwp-dark-mode .site-footer a       { color: var(--stagekitwp-footer-text) !important; }
        html.stagekitwp-dark-mode .site-footer a:hover { color: var(--stagekitwp-primary)     !important; }

        /* Notification bar — always-dark zone */
        html.stagekitwp-dark-mode .stagekitwp-top-notification-bar {
            background-color: var(--stagekitwp-notif-bg)  !important;
            color:            var(--stagekitwp-notif-text) !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-top-notification-bar * { color: var(--stagekitwp-notif-text) !important; }

        /* Hero banner — always-dark zone with media */
        html.stagekitwp-dark-mode .stagekitwp-hero-banner.media-type-video .stagekitwp-hero-title,
        html.stagekitwp-dark-mode .stagekitwp-hero-banner.media-type-image .stagekitwp-hero-title {
            color: var(--stagekitwp-hero-title-dark) !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-hero-banner.media-type-video .stagekitwp-hero-subtitle,
        html.stagekitwp-dark-mode .stagekitwp-hero-banner.media-type-image .stagekitwp-hero-subtitle {
            color: var(--stagekitwp-hero-subtitle-dark) !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-hero-banner.media-type-video .stagekitwp-hero-btn,
        html.stagekitwp-dark-mode .stagekitwp-hero-banner.media-type-image .stagekitwp-hero-btn { color: #ffffff !important; }

        /* Mode toggle button */
        html.stagekitwp-dark-mode #stagekitwp-color-mode-toggle {
            background: var(--stagekitwp-surface-bg) !important;
            color:      var(--stagekitwp-body-text)  !important;
        }

    </style>
    <?php
    if ( $mode === 'dark' ) {
        echo '<script>document.documentElement.classList.add("stagekitwp-dark-mode");</script>';
    }
}
add_action( 'wp_head', 'stagekitwp_output_dark_mode_css' );

/**
 * Render the frontend Light/Dark toggle button and pass the server default
 * to JS via <body data-stagekitwp-color-mode>.
 */
function stagekitwp_render_color_mode_switcher() {
    if ( ! get_theme_mod( 'stagekitwp_enable_frontend_switcher', false ) ) {
        return;
    }
    $mode = get_theme_mod( 'stagekitwp_color_mode', 'light' );
    // Inject data attribute onto body for the JS to read
    add_filter( 'body_class', function( $classes ) { return $classes; } ); // ensure body_class fires
    add_action( 'wp_body_open', function() use ( $mode ) {
        // Patch body tag with data attribute via inline script (body_class filter can't add data attrs)
    } );
    // Output the floating toggle button via wp_footer.
    // Icon/label reflect the PHP server default; the JS updateButton() call in
    // DOMContentLoaded corrects this if localStorage holds a different preference.
    $label = ( $mode === 'dark' ) ? 'Switch to Light Mode' : 'Switch to Dark Mode';
    $icon  = ( $mode === 'dark' ) ? '☀️' : '🌙';
    ?>
    <button
        id="stagekitwp-color-mode-toggle"
        aria-label="<?php echo esc_attr( $label ); ?>"
        title="<?php echo esc_attr( $label ); ?>"
        style="
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 2px solid rgba(128,128,128,0.3);
            background: var(--stagekitwp-surface-bg, #f8f9fa);
            color: var(--stagekitwp-body-text, #333);
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        "
        onmouseover="this.style.transform='scale(1.1)';this.style.boxShadow='0 4px 14px rgba(0,0,0,0.25)';"
        onmouseout="this.style.transform='scale(1)';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.2)';"
    ><?php echo $icon; ?></button>
    <script>
        // Pass server default to the switcher script
        document.body.dataset.stagekitwpColorMode = '<?php echo esc_js( $mode ); ?>';
    </script>
    <?php
}
add_action( 'wp_footer', 'stagekitwp_render_color_mode_switcher' );

