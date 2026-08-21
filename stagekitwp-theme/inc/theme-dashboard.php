<?php
/**
 * StageKitWP Theme Dashboard Administration Screen with System Health Metrics
 * And Automated Mock Production Data & Menu Architecture Provisioner.
 *
 * @package StageKitWP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Register the custom Admin Menu Page
 */
function stagekitwp_theme_add_dashboard_menu() {
    global $menu;

    $parent_slug = '';
    if ( is_array( $menu ) ) {
        foreach ( $menu as $menu_item ) {
            if ( ! isset( $menu_item[2] ) ) {
                continue;
            }

            if ( 'stagekitwp-core' === $menu_item[2] ) {
                $parent_slug = 'stagekitwp-core';
                break;
            }

            if ( 'stagekitwp' === $menu_item[2] ) {
                $parent_slug = 'stagekitwp';
            }

            if ( 'stagekitwp-theme' === $menu_item[2] && '' === $parent_slug ) {
                $parent_slug = 'stagekitwp-theme';
            }
        }
    }

    if ( '' === $parent_slug ) {
        return;
    }

    add_submenu_page(
        $parent_slug,
        __( 'StageKitWP Theme Dashboard', 'stagekitwp-theme' ),
        __( 'Theme Dashboard', 'stagekitwp-theme' ),
        'edit_theme_options',
        'stagekitwp-theme-dashboard',
        'stagekitwp_theme_render_dashboard_page'
    );
}
add_action( 'admin_menu', 'stagekitwp_theme_add_dashboard_menu' );

/**
 * Collect installation and activation status for the theme and StageKitWP plugins.
 */
function stagekitwp_theme_dashboard_health_components() {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';

    $active_plugins = (array) get_option( 'active_plugins', array() );
    $plugin_status = static function( $plugin_file ) use ( $active_plugins ) {
        $plugin_file = (string) $plugin_file;
        $plugin_name = basename( $plugin_file );
        $installed = file_exists( WP_PLUGIN_DIR . '/' . $plugin_file );
        $active = in_array( $plugin_file, $active_plugins, true );

        foreach ( $active_plugins as $active_plugin ) {
            if ( basename( $active_plugin ) === $plugin_name ) {
                $active = true;
                $installed = file_exists( WP_PLUGIN_DIR . '/' . $active_plugin );
                break;
            }
        }

        return array(
            'installed' => $installed,
            'active'    => $active,
        );
    };

    $current_theme = wp_get_theme();
    $theme_directory = basename( get_stylesheet_directory() );
    $is_stagekitwp_theme = $current_theme->exists() && (
        'stagekitwp-theme' === $theme_directory ||
        'stagekitwp-theme' === $current_theme->get( 'TextDomain' ) ||
        false !== stripos( (string) $current_theme->get( 'Name' ), 'StageKitWP' )
    );

    $plugin_components = array(
        'core' => array( 'name' => __( 'StageKitWP Core', 'stagekitwp-theme' ), 'file' => 'stagekitwp-core/stagekitwp-core.php' ),
        'blocks' => array( 'name' => __( 'StageKitWP Blocks', 'stagekitwp-theme' ), 'file' => 'stagekitwp-blocks/stagekitwp-blocks.php' ),
        'seo' => array( 'name' => __( 'StageKitWP SEO', 'stagekitwp-theme' ), 'file' => 'stagekitwp-seo/stagekitwp-seo.php' ),
        'import_export' => array( 'name' => __( 'Import / Export', 'stagekitwp-theme' ), 'file' => 'stagekitwp-import-export/stagekitwp-import-export.php' ),
        'members' => array( 'name' => __( 'Members', 'stagekitwp-theme' ), 'file' => 'stagekitwp-members/stagekitwp-members.php' ),
        'rc_library' => array( 'name' => __( 'RC Library', 'stagekitwp-theme' ), 'file' => 'stagekitwp-rc-library/stagekitwp-rc-library.php' ),
        'sync' => array( 'name' => __( 'Sync', 'stagekitwp-theme' ), 'file' => 'stagekitwp-sync/stagekitwp-sync.php' ),
    );

    $components = array(
        'theme' => array(
            'name'      => __( 'StageKitWP Theme', 'stagekitwp-theme' ),
            'type'      => 'theme',
            'installed' => $is_stagekitwp_theme,
            'active'    => $is_stagekitwp_theme && ( get_stylesheet() === $theme_directory || get_template() === $theme_directory ),
        ),
    );

    foreach ( $plugin_components as $key => $component ) {
        $status = $plugin_status( $component['file'] );
        $components[ $key ] = array_merge( $component, array( 'type' => 'plugin' ), $status );
    }

    return $components;
}

/**
 * Render the Theme Dashboard UI
 */
function stagekitwp_theme_render_dashboard_page() {
    global $wp_version;

    $requested_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
    $active_tab    = in_array( $requested_tab, array( 'overview', 'health' ), true ) ? $requested_tab : 'overview';
    $health_components = stagekitwp_theme_dashboard_health_components();

    // =========================================================================
    // POST REQUEST CONTROLLER: DATA ENGINE PIPELINE ROUTING
    // =========================================================================
    $action_notice = '';
    
    $blueprint_nonce = isset( $_POST['stagekitwp_blueprint_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['stagekitwp_blueprint_nonce'] ) ) : '';
    if (
        current_user_can( 'edit_theme_options' ) &&
        $blueprint_nonce &&
        wp_verify_nonce( $blueprint_nonce, 'stagekitwp_blueprint_action' )
    ) {
        
        if ( isset( $_POST['stagekitwp_add_blueprints'] ) ) {
            // --- 1. PROVISION MOCK POST DATA FOR CORE THEATRE CPTs ---
            $season_ids = array();
            if ( post_type_exists( 'season' ) ) {
                $season_ids['current'] = wp_insert_post( array(
                    'post_title'  => '2026 Continental Mainstage Season',
                    'post_status' => 'publish',
                    'post_type'   => 'season',
                    'meta_input'  => array( '_stagekitwp_season_is_current' => '1', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31' )
                ) );
                $season_ids['next'] = wp_insert_post( array(
                    'post_title'  => '2027 Avant-Garde Horizon Lineup',
                    'post_status' => 'publish',
                    'post_type'   => 'season',
                    'meta_input'  => array( '_stagekitwp_season_is_current' => '0', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31' )
                ) );
            }

            if ( post_type_exists( 'show' ) ) {
                wp_insert_post( array(
                    'post_title'  => 'The Phantom Playbill',
                    'post_status' => 'publish',
                    'post_type'   => 'show',
                    'meta_input'  => array( '_stagekitwp_show_time_slot' => 'Winter', '_stagekitwp_show_season' => isset($season_ids['current']) ? $season_ids['current'] : '', '_stagekitwp_show_show_dates' => '2026-12-15' )
                ) );
                wp_insert_post( array(
                    'post_title'  => 'Curtain Call Catastrophe',
                    'post_status' => 'publish',
                    'post_type'   => 'show',
                    'meta_input'  => array( '_stagekitwp_show_time_slot' => 'Spring', '_stagekitwp_show_season' => isset($season_ids['next']) ? $season_ids['next'] : '', '_stagekitwp_show_show_dates' => '2027-04-10' )
                ) );
            }

            // --- 2. CREATE MAP PLOT PAGES ---
            $pages_to_build = array(
                'about-us'          => array( 'title' => 'About Us', 'content' => '<p>Our structural theatre mission pipeline.</p>' ),
                'history'           => array( 'title' => 'History', 'content' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Founded over half a century ago, our playhouse remains a historical pillar.</p>' ),
                'contact-us'        => array( 'title' => 'Contact Us', 'content' => '<p>Get in touch via our admin box office avenues.</p>' ),
                'board-of-directors'=> array( 'title' => 'Board of Directors', 'content' => '[stagekitwp_board_members]' ),
                'get-involved'      => array( 'title' => 'Get Involved', 'content' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Lend your backstage talents.</p>' ),
                'code-of-conduct'   => array( 'title' => 'By-laws and Code of Conduct', 'content' => '<p>Lorem ipsum dolor sit amet. Operational governance terms.</p>' ),
                'shows'             => array( 'title' => 'Shows', 'content' => '<p>Annual operational portfolio.</p>' ),
                'now-playing'       => array( 'title' => 'Now Playing', 'content' => '[stagekitwp_shows which="current"]' ),
                'coming-soon'       => array( 'title' => 'Coming Soon', 'content' => '[stagekitwp_shows which="upcoming"]' ),
                'past-shows'        => array( 'title' => 'Past Shows', 'content' => '<p>Historical production log matrix.</p>' ),
                'awards'            => array( 'title' => 'Awards', 'content' => '[stagekitwp_awards]' ),
                'media'             => array( 'title' => 'Media', 'content' => '<figure class="wp-block-gallery has-nested-images columns-3"></figure>' ),
                'news'              => array( 'title' => 'News', 'content' => '<p>Updates from backstage.</p>' ),
                'auditions'         => array( 'title' => 'Auditions', 'content' => '[stagekitwp_auditions]' ),
                'upcoming-events'   => array( 'title' => 'Upcoming Events', 'content' => '<p>Lorem Ipsum events tracking array.</p>' ),
                'blog'              => array( 'title' => 'Blog', 'content' => '<p>Regular blog entries index.</p>' ),
                'tickets'           => array( 'title' => 'Tickets', 'content' => '[stagekitwp_ticket_url]' )
            );

            $page_map = array();
            foreach ( $pages_to_build as $slug => $data ) {
                $check = get_page_by_path( $slug, OBJECT, 'page' );
                if ( null === $check ) {
                    $pid = wp_insert_post( array(
                        'post_title'   => $data['title'],
                        'post_content' => $data['content'],
                        'post_status'  => 'publish',
                        'post_type'    => 'page',
                        'post_name'    => $slug,
                    ) );
                    $page_map[$slug] = $pid;
                } else {
                    $page_map[$slug] = $check->ID;
                }
            }

            // --- 3. GENERATE CATEGORIES & STANDARD POST ENTRIES ---
            $cat_event = wp_create_category( 'Events' );
            $cat_blog  = wp_create_category( 'Blog' );
            wp_insert_post( array( 'post_title' => 'Annual Gala Night', 'post_content' => 'Lorem Ipsum production event.', 'post_status' => 'publish', 'post_category' => array($cat_event) ) );
            wp_insert_post( array( 'post_title' => 'Director Roundtable Notebook', 'post_content' => 'Behind the curtain writing entry.', 'post_status' => 'publish', 'post_category' => array($cat_blog) ) );

            // --- 4. ASSEMBLE NAVIGATION MENUS ---
            $main_menu_id = wp_create_nav_menu( 'Main Menu' );
            $foot_menu_id = wp_create_nav_menu( 'Footer Menu' );

            if ( ! is_wp_error( $main_menu_id ) ) {
                // About Us Tree
                $p_about = wp_update_nav_menu_item( $main_menu_id, 0, array( 'menu-item-title' => 'About Us', 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['about-us'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
                wp_update_nav_menu_item( $main_menu_id, 0, array( 'menu-item-title' => 'History', 'menu-item-parent-id' => $p_about, 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['history'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
                wp_update_nav_menu_item( $main_menu_id, 0, array( 'menu-item-title' => 'Contact Us', 'menu-item-parent-id' => $p_about, 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['contact-us'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
                wp_update_nav_menu_item( $main_menu_id, 0, array( 'menu-item-title' => 'Board of Directors', 'menu-item-parent-id' => $p_about, 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['board-of-directors'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
                wp_update_nav_menu_item( $main_menu_id, 0, array( 'menu-item-title' => 'Get Involved', 'menu-item-parent-id' => $p_about, 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['get-involved'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
                wp_update_nav_menu_item( $main_menu_id, 0, array( 'menu-item-title' => 'By-laws and Code of Conduct', 'menu-item-parent-id' => $p_about, 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['code-of-conduct'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );

                // Shows Tree
                $p_shows = wp_update_nav_menu_item( $main_menu_id, 0, array( 'menu-item-title' => 'Shows', 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['shows'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
                wp_update_nav_menu_item( $main_menu_id, 0, array( 'menu-item-title' => 'Now Playing', 'menu-item-parent-id' => $p_shows, 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['now-playing'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
                wp_update_nav_menu_item( $main_menu_id, 0, array( 'menu-item-title' => 'Coming Soon', 'menu-item-parent-id' => $p_shows, 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['coming-soon'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
                wp_update_nav_menu_item( $main_menu_id, 0, array( 'menu-item-title' => 'Past Shows', 'menu-item-parent-id' => $p_shows, 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['past-shows'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
                wp_update_nav_menu_item( $main_menu_id, 0, array( 'menu-item-title' => 'Awards', 'menu-item-parent-id' => $p_shows, 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['awards'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
                wp_update_nav_menu_item( $main_menu_id, 0, array( 'menu-item-title' => 'Media', 'menu-item-parent-id' => $p_shows, 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['media'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );

                // News Tree
                $p_news = wp_update_nav_menu_item( $main_menu_id, 0, array( 'menu-item-title' => 'News', 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['news'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
                wp_update_nav_menu_item( $main_menu_id, 0, array( 'menu-item-title' => 'Auditions', 'menu-item-parent-id' => $p_news, 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['auditions'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
                wp_update_nav_menu_item( $main_menu_id, 0, array( 'menu-item-title' => 'Upcoming Events', 'menu-item-parent-id' => $p_news, 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['upcoming-events'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
                wp_update_nav_menu_item( $main_menu_id, 0, array( 'menu-item-title' => 'Blog', 'menu-item-parent-id' => $p_news, 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['blog'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
                wp_update_nav_menu_item( $main_menu_id, 0, array( 'menu-item-title' => 'Get Involved', 'menu-item-parent-id' => $p_news, 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['get-involved'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );

                // Direct Links
                wp_update_nav_menu_item( $main_menu_id, 0, array( 'menu-item-title' => 'Tickets', 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['tickets'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
                wp_update_nav_menu_item( $main_menu_id, 0, array( 'menu-item-title' => 'Donate', 'menu-item-url' => get_theme_mod( 'stagekitwp_donate_button_url', home_url( '/donate' ) ), 'menu-item-type' => 'custom', 'menu-item-status' => 'publish' ) );
            }

            if ( ! is_wp_error( $foot_menu_id ) ) {
                wp_update_nav_menu_item( $foot_menu_id, 0, array( 'menu-item-title' => 'Tickets', 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['tickets'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
                wp_update_nav_menu_item( $foot_menu_id, 0, array( 'menu-item-title' => 'Auditions', 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['auditions'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
                wp_update_nav_menu_item( $foot_menu_id, 0, array( 'menu-item-title' => 'Now Playing', 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['now-playing'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
                wp_update_nav_menu_item( $foot_menu_id, 0, array( 'menu-item-title' => 'Get Involved', 'menu-item-object' => 'page', 'menu-item-object-id' => $page_map['get-involved'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
                wp_update_nav_menu_item( $foot_menu_id, 0, array( 'menu-item-title' => 'Donate', 'menu-item-url' => get_theme_mod( 'stagekitwp_donate_button_url', home_url( '/donate' ) ), 'menu-item-type' => 'custom', 'menu-item-status' => 'publish' ) );
            }

            $action_notice = '<div class="notice notice-success is-dismissible" style="margin:20px 0 0 0;"><p>✅ ' . esc_html__( 'Success! Full StageKitWP mock database, structural hierarchy layers, and multi-tier menus provisioned.', 'stagekitwp-theme' ) . '</p></div>';
        }

        if ( isset( $_POST['stagekitwp_remove_blueprints'] ) ) {
            // Flush built shell navigation parameters out of database rows
            wp_delete_nav_menu( 'Main Menu' );
            wp_delete_nav_menu( 'Footer Menu' );

            $slugs_to_wipe = array( 'about-us', 'history', 'contact-us', 'board-of-directors', 'get-involved', 'code-of-conduct', 'shows', 'now-playing', 'coming-soon', 'past-shows', 'awards', 'media', 'news', 'auditions', 'upcoming-events', 'blog', 'tickets' );
            foreach ( $slugs_to_wipe as $slug ) {
                $check = get_page_by_path( $slug, OBJECT, 'page' );
                if ( $check ) {
                    wp_delete_post( $check->ID, true );
                }
            }
            $action_notice = '<div class="notice notice-warning is-dismissible" style="margin:20px 0 0 0;"><p>🗑️ ' . esc_html__( 'Purged custom mock structure assets, terms, categories, and custom page templates.', 'stagekitwp-theme' ) . '</p></div>';
        }
    }

    // =========================================================================
    // ENGINE MARSHALLING: PRODUCTION CALCULATION MATRIX
    // =========================================================================
    $countdown_enabled     = get_theme_mod( 'stagekitwp_enable_countdown', true );
    $fallback_timestamp    = get_theme_mod( 'stagekitwp_next_show_timestamp' );
    $automated_show_title  = '';
    $calculated_timestamp  = '';
    $target_slot           = '';
    $season_title          = __( 'None Flagged as Active', 'stagekitwp-theme' );
    $diagnostic_logs       = array();

    $current_month_day = date( 'md' );

    if ( $current_month_day >= '0701' && $current_month_day <= '1115' ) {
        $target_slot = 'Fall';
    } elseif ( ( $current_month_day >= '1116' && $current_month_day <= '1231' ) || ( $current_month_day >= '0101' && $current_month_day <= '0217' ) ) {
        $target_slot = 'Winter';
    } elseif ( $current_month_day >= '0218' && $current_month_day <= '0630' ) {
        $target_slot = 'Spring';
    }

    if ( post_type_exists( 'show' ) && post_type_exists( 'season' ) ) {
        $season_lookup = get_posts( array(
            'post_type'      => 'season',
            'posts_per_page' => 1,
            'meta_key'       => '_stagekitwp_season_is_current',
            'meta_value'     => '1',
            'fields'         => 'ids'
        ) );
        $season_id = ! empty( $season_lookup ) ? $season_lookup[0] : 0;

        if ( $season_id ) {
            $season_title = get_the_title( $season_id );
            $show_query = new WP_Query( array(
                'post_type'      => 'show',
                'posts_per_page' => 1,
                'meta_query'     => array(
                    'relation' => 'AND',
                    array( 'key' => '_stagekitwp_show_time_slot', 'value' => $target_slot, 'compare' => '=' ),
                    array( 'key' => '_stagekitwp_show_season', 'value' => $season_id, 'compare' => '=' )
                )
            ) );

            if ( $show_query->have_posts() ) {
                $show_query->the_post();
                $automated_show_title = get_the_title();
                $raw_show_date        = get_post_meta( get_the_ID(), '_stagekitwp_show_show_dates', true );
                if ( ! empty( $raw_show_date ) ) {
                    $parsed_time = strtotime( $raw_show_date );
                    if ( $parsed_time ) {
                        $calculated_timestamp = date( 'Y-m-d H:i:s', $parsed_time );
                    } else {
                        $diagnostic_logs[] = __( 'Show found, but the date text field value could not be successfully parsed into a standard timestamp string.', 'stagekitwp-theme' );
                    }
                } else {
                    $diagnostic_logs[] = __( 'Show found, but the custom date meta tracking field (_stagekitwp_show_show_dates) is empty.', 'stagekitwp-theme' );
                }
            } else {
                $diagnostic_logs[] = sprintf( __( 'No "Show" entry is mapped to the current operational slot (%s) inside the active season.', 'stagekitwp-theme' ), esc_html( $target_slot ) );
            }
            wp_reset_postdata();
        } else {
            $diagnostic_logs[] = __( 'No "Season" post entry has been flagged as the active calendar season using the configuration dashboard parameters.', 'stagekitwp-theme' );
        }
    } else {
        $diagnostic_logs[] = __( 'The required Custom Post Type identifiers ("show" or "season") are missing or unregistered on this instance.', 'stagekitwp-theme' );
    }

    $final_active_target = ! empty( $calculated_timestamp ) ? $calculated_timestamp : $fallback_timestamp;

    // =========================================================================
    // MATRIX TILE 1: STAGEKITWP COMPONENT EXTENSIONS
    // =========================================================================
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    $component_scores = array();

    $active_plugins = (array) get_option( 'active_plugins', array() );
    $is_stagekitwp_plugin_active = static function( $plugin_file ) use ( $active_plugins ) {
        $plugin_name = basename( $plugin_file );
        foreach ( $active_plugins as $active_plugin ) {
            if ( basename( $active_plugin ) === $plugin_name ) {
                return true;
            }
        }

        return false;
    };

    if ( defined( 'STAGEKITWP_CORE_VERSION' ) || class_exists( 'StageKitWP_Core' ) || $is_stagekitwp_plugin_active( 'stagekitwp-core/stagekitwp-core.php' ) ) {
        $component_scores['core'] = array( 'status' => 'good', 'label' => __( 'Active', 'stagekitwp-theme' ), 'msg' => __( 'StageKitWP Core engine is running and managing content protocols.', 'stagekitwp-theme' ) );
    } else {
        $component_scores['core'] = array( 'status' => 'critical', 'label' => __( 'Inactive', 'stagekitwp-theme' ), 'msg' => __( 'StageKitWP Core plugin is inactive or uninstalled.', 'stagekitwp-theme' ) );
    }

    if ( defined( 'STAGEKITWP_RC_LIBRARY_VERSION' ) || class_exists( 'STAGEKITWP_RC_LIBRARY_CPTs' ) || $is_stagekitwp_plugin_active( 'stagekitwp-rc-library/stagekitwp-rc-library.php' ) ) {
        $component_scores['rc_library'] = array( 'status' => 'good', 'label' => __( 'Active', 'stagekitwp-theme' ), 'msg' => __( 'StageKitWP RC Library extension is initialized successfully.', 'stagekitwp-theme' ) );
    } else {
        $component_scores['rc_library'] = array( 'status' => 'warning', 'label' => __( 'Offline', 'stagekitwp-theme' ), 'msg' => __( 'StageKitWP RC Library extension is not detected.', 'stagekitwp-theme' ) );
    }

    if ( defined( 'STAGEKITWP_SYNC_VERSION' ) || class_exists( 'STAGEKITWP_Sync' ) || $is_stagekitwp_plugin_active( 'stagekitwp-sync/stagekitwp-sync.php' ) ) {
        $component_scores['sync'] = array( 'status' => 'good', 'label' => __( 'Active', 'stagekitwp-theme' ), 'msg' => __( 'Sync extension pipeline is active and monitoring data relays.', 'stagekitwp-theme' ) );
    } else {
        $component_scores['sync'] = array( 'status' => 'warning', 'label' => __( 'Offline', 'stagekitwp-theme' ), 'msg' => __( 'Sync extension is disabled. Remote syncing options are locked.', 'stagekitwp-theme' ) );
    }

    if ( defined( 'STAGEKITWPMA_VERSION' ) || class_exists( 'STAGEKITWP_Member_Area' ) || $is_stagekitwp_plugin_active( 'stagekitwp-members/stagekitwp-members.php' ) ) {
        $component_scores['members'] = array( 'status' => 'good', 'label' => __( 'Active', 'stagekitwp-theme' ), 'msg' => __( 'Members extension portal validation check succeeded.', 'stagekitwp-theme' ) );
    } else {
        $component_scores['members'] = array( 'status' => 'warning', 'label' => __( 'Offline', 'stagekitwp-theme' ), 'msg' => __( 'Members extension is offline. Patron portals are unavailable.', 'stagekitwp-theme' ) );
    }

    if ( function_exists( 'stagekitwp_register_blocks' ) || $is_stagekitwp_plugin_active( 'stagekitwp-blocks/stagekitwp-blocks.php' ) ) {
        $component_scores['blocks'] = array( 'status' => 'good', 'label' => __( 'Active', 'stagekitwp-theme' ), 'msg' => __( 'StageKitWP Blocks extension is registered and available.', 'stagekitwp-theme' ) );
    } else {
        $component_scores['blocks'] = array( 'status' => 'warning', 'label' => __( 'Offline', 'stagekitwp-theme' ), 'msg' => __( 'StageKitWP Blocks extension is not detected.', 'stagekitwp-theme' ) );
    }

    if ( $is_stagekitwp_plugin_active( 'stagekitwp-seo/stagekitwp-seo.php' ) ) {
        $component_scores['seo'] = array( 'status' => 'good', 'label' => __( 'Active', 'stagekitwp-theme' ), 'msg' => __( 'StageKitWP SEO extension is active.', 'stagekitwp-theme' ) );
    } else {
        $component_scores['seo'] = array( 'status' => 'warning', 'label' => __( 'Offline', 'stagekitwp-theme' ), 'msg' => __( 'StageKitWP SEO extension is not detected.', 'stagekitwp-theme' ) );
    }

    // =========================================================================
    // MATRIX TILE 2: CORE SERVER & WORDPRESS ENVIRONMENT METRICS
    // =========================================================================
    $server_scores = array();

    if ( version_compare( $wp_version, '6.2', '>=' ) ) {
        $server_scores['wp'] = array( 'status' => 'good', 'label' => 'v' . $wp_version, 'msg' => __( 'WordPress core installation is optimized and current.', 'stagekitwp-theme' ) );
    } else {
        $server_scores['wp'] = array( 'status' => 'warning', 'label' => 'v' . $wp_version, 'msg' => __( 'Legacy WordPress detected. Please upgrade core for security stability.', 'stagekitwp-theme' ) );
    }

    $php_version = phpversion();
    if ( version_compare( $php_version, '8.0.0', '>=' ) ) {
        $server_scores['php'] = array( 'status' => 'good', 'label' => 'v' . $php_version, 'msg' => __( 'PHP runtime execution environment engine is performing optimally.', 'stagekitwp-theme' ) );
    } else {
        $server_scores['php'] = array( 'status' => 'warning', 'label' => 'v' . $php_version, 'msg' => __( 'Running legacy engine. Update to PHP 8.0+ recommended for speeds.', 'stagekitwp-theme' ) );
    }

    if ( function_exists( 'mail' ) || ( defined( 'SMTP_HOST' ) && SMTP_HOST ) ) {
        $server_scores['mail'] = array( 'status' => 'good', 'label' => __( 'Operational', 'stagekitwp-theme' ), 'msg' => __( 'wp_mail wrapper endpoints are online. Order transaction receipts route normally.', 'stagekitwp-theme' ) );
    } else {
        $server_scores['mail'] = array( 'status' => 'critical', 'label' => __( 'Disabled / Blocked', 'stagekitwp-theme' ), 'msg' => __( 'The server execution loop has native mail triggers disabled. Patrons will not receive emails.', 'stagekitwp-theme' ) );
    }

    $memory_limit = ini_get( 'memory_limit' );
    $memory_bytes = wp_convert_hr_to_bytes( $memory_limit );
    if ( $memory_bytes >= wp_convert_hr_to_bytes( '256M' ) ) {
        $server_scores['memory'] = array( 'status' => 'good', 'label' => $memory_limit, 'msg' => __( 'Memory allocation limits are spacious enough to process simultaneous tickets queries.', 'stagekitwp-theme' ) );
    } else {
        $server_scores['memory'] = array( 'status' => 'warning', 'label' => $memory_limit, 'msg' => __( 'Memory ceiling targets are low. Heavy system reports could run out of memory resources.', 'stagekitwp-theme' ) );
    }

    $max_upload_size_bytes = wp_max_upload_size();
    $max_upload_size_mb    = round( $max_upload_size_bytes / ( 1024 * 1024 ) );
    if ( $max_upload_size_mb >= 2000 ) {
        $server_scores['upload_size'] = array( 'status' => 'good', 'label' => $max_upload_size_mb . 'MB', 'msg' => __( 'Satisfies requirements for 2000MB high-definition production media banners.', 'stagekitwp-theme' ) );
    } else {
        $server_scores['upload_size'] = array( 'status' => 'warning', 'label' => $max_upload_size_mb . 'MB', 'msg' => __( 'Upload thresholds drop under 2000MB target footprint. Media sync allocations limited.', 'stagekitwp-theme' ) );
    }

    $max_execution_time = ini_get( 'max_execution_time' );
    if ( (int) $max_execution_time >= 120 || (int) $max_execution_time === 0 ) {
        $server_scores['timeout'] = array( 'status' => 'good', 'label' => $max_execution_time . 's', 'msg' => __( 'Script timeout limit parameters accommodate heavy synchronizations easily.', 'stagekitwp-theme' ) );
    } else {
        $server_scores['timeout'] = array( 'status' => 'warning', 'label' => $max_execution_time . 's', 'msg' => __( 'Low script timeout ceiling. Remote API fetches risk hitting premature dropoffs.', 'stagekitwp-theme' ) );
    }

    echo wp_kses_post( $action_notice );
    ?>
    
    <div class="wrap stagekitwp-dashboard-wrap" style="margin: 20px 20px 0 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        
        <div class="stagekitwp-dashboard-header" style="background: #111111; color: #ffffff; padding: 0; border-radius: 8px 8px 0 0; border-bottom: 4px solid #e50914; overflow: hidden;">
            <img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/svg/dashboard-header.svg' ); ?>" alt="StageKitWP dashboard banner" style="display: block; width: 100%; height: auto; min-height: 150px; background: #111111;" />
        </div>

        <nav style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px;">
            <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'stagekitwp-theme-dashboard', 'tab' => 'overview' ), admin_url( 'admin.php' ) ) ); ?>" class="button <?php echo 'overview' === $active_tab ? 'button-primary' : 'button-secondary'; ?>">
                <?php esc_html_e( 'Overview', 'stagekitwp-theme' ); ?>
            </a>
            <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'stagekitwp-theme-dashboard', 'tab' => 'health' ), admin_url( 'admin.php' ) ) ); ?>" class="button <?php echo 'health' === $active_tab ? 'button-primary' : 'button-secondary'; ?>">
                <?php esc_html_e( 'Health', 'stagekitwp-theme' ); ?>
            </a>
        </nav>

        <?php if ( 'health' === $active_tab ) : ?>
            <div class="stagekitwp-dashboard-grid" style="display: grid; grid-template-columns: 1fr; gap: 20px; margin-top: 20px;">
                <div class="stagekitwp-dashboard-panel" style="background: #ffffff; border: 1px solid #ccd0d4; padding: 25px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                    <h2 style="margin-top: 0; font-size: 1.4rem; font-weight: 700; padding-bottom: 12px; border-bottom: 1px solid #f0f0f1; color: #111111;">
                        🩺 <?php esc_html_e( 'Health Check', 'stagekitwp-theme' ); ?>
                    </h2>
                    <p style="margin: 0 0 16px 0; color: #646970;">
                        <?php esc_html_e( 'Review the install state and active state of the StageKitWP theme and each supporting plugin.', 'stagekitwp-theme' ); ?>
                    </p>
                    <table class="widefat striped" style="border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Component', 'stagekitwp-theme' ); ?></th>
                                <th><?php esc_html_e( 'Install Status', 'stagekitwp-theme' ); ?></th>
                                <th><?php esc_html_e( 'Activated Status', 'stagekitwp-theme' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $health_components as $component ) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $component['name'] ); ?></strong></td>
                                    <td>
                                        <?php if ( ! empty( $component['installed'] ) ) : ?>
                                            <span style="color: #1d7f3d; font-weight: 700;">✅ <?php esc_html_e( 'Installed', 'stagekitwp-theme' ); ?></span>
                                        <?php else : ?>
                                            <span style="color: #b32d16; font-weight: 700;">❌ <?php esc_html_e( 'Not Installed', 'stagekitwp-theme' ); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ( ! empty( $component['active'] ) ) : ?>
                                            <span style="color: #1d7f3d; font-weight: 700;">✅ <?php esc_html_e( 'Activated', 'stagekitwp-theme' ); ?></span>
                                        <?php else : ?>
                                            <span style="color: #b32d16; font-weight: 700;">❌ <?php esc_html_e( 'Inactive', 'stagekitwp-theme' ); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else : ?>
            <div class="stagekitwp-dashboard-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
            
            <div class="stagekitwp-dashboard-main">
                
                <div class="stagekitwp-dashboard-panel" style="background: #ffffff; border: 1px solid #ccd0d4; padding: 25px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 20px;">
                    <h2 style="margin-top: 0; font-size: 1.4rem; font-weight: 700; padding-bottom: 12px; border-bottom: 1px solid #f0f0f1; color: #111111; display:flex; justify-content:space-between; align-items:center;">
                        <span>📺 <?php esc_html_e( 'Notification Bar Front-End Live Preview', 'stagekitwp-theme' ); ?></span>
                        <span style="font-size:0.8rem; padding: 4px 10px; border-radius:12px; font-weight:600; <?php echo $countdown_enabled ? 'background:#e2f5e4; color:#276f32;' : 'background:#fbeae5; color:#b32d16;'; ?>">
                            <?php echo $countdown_enabled ? '● ' . esc_html__( 'Global Display Hook Enabled', 'stagekitwp-theme' ) : '○ ' . esc_html__( 'Global Display Hook Disabled', 'stagekitwp-theme' ); ?>
                        </span>
                    </h2>

                    <?php if ( ! empty( $final_active_target ) ) : ?>
                    <div style="background: #111111; color: #ffffff; padding: 15px; margin-top: 20px; border-radius: 4px; border-left: 4px solid #e50914; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.9rem;">
                            <span style="background: #e50914; padding: 2px 6px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; margin-right: 8px; border-radius: 2px; color:#fff;">
                                <?php esc_html_e( 'Next Production', 'stagekitwp-theme' ); ?>
                            </span>
                            <strong><?php echo ! empty( $automated_show_title ) ? esc_html( $automated_show_title ) : esc_html__( 'Static Fallback Asset', 'stagekitwp-theme' ); ?></strong> &mdash;
                            <span id="stagekitwp-dashboard-countdown-label" style="color: #cccccc;"><?php esc_html_e( 'Loading…', 'stagekitwp-theme' ); ?></span>
                        </span>
                    </div>
                    <script type="text/javascript">
                    (function() {
                        var targetDate = new Date( <?php echo wp_json_encode( $final_active_target ); ?> ).getTime();
                        if ( isNaN( targetDate ) ) {
                            document.getElementById( 'stagekitwp-dashboard-countdown-label' ).textContent = <?php echo wp_json_encode( __( 'Invalid date — check the Fallback Static Target Date/Time field.', 'stagekitwp-theme' ) ); ?>;
                            return;
                        }
                        function tick() {
                            var now  = new Date().getTime();
                            var diff = targetDate - now;
                            var el   = document.getElementById( 'stagekitwp-dashboard-countdown-label' );
                            if ( ! el ) { return; }
                            if ( diff < 0 ) {
                                el.innerHTML = <?php echo wp_json_encode( __( 'Performance Live! Visit Box Office for Entry.', 'stagekitwp-theme' ) ); ?>;
                                return;
                            }
                            var days    = Math.floor( diff / ( 1000 * 60 * 60 * 24 ) );
                            var hours   = Math.floor( ( diff % ( 1000 * 60 * 60 * 24 ) ) / ( 1000 * 60 * 60 ) );
                            var minutes = Math.floor( ( diff % ( 1000 * 60 * 60 ) ) / ( 1000 * 60 ) );
                            var seconds = Math.floor( ( diff % ( 1000 * 60 ) ) / 1000 );
                            el.innerHTML = <?php echo wp_json_encode( __( 'Curtain rises in:', 'stagekitwp-theme' ) ); ?>
                                + ' <strong>' + days + 'd ' + hours + 'h ' + minutes + 'm ' + seconds + 's</strong>';
                        }
                        tick();
                        setInterval( tick, 1000 );
                    })();
                    </script>
                    <?php else : ?>
                    <div style="background: #1e1e1e; color: #cccccc; padding: 15px; margin-top: 20px; border-radius: 4px; border-left: 4px solid #cca300; font-size: 0.9rem;">
                        ⚠️ <?php esc_html_e( 'No target date found. Assign a show to the active season, or enter a Fallback Static Target Date/Time in the Notification &amp; Countdown Bar settings.', 'stagekitwp-theme' ); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="stagekitwp-dashboard-panel" style="background: #ffffff; border: 1px solid #ccd0d4; padding: 25px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 20px;">
                    <h2 style="margin-top: 0; font-size: 1.4rem; font-weight: 700; padding-bottom: 12px; border-bottom: 1px solid #f0f0f1; color: #111111;">
                        ❤️ <?php esc_html_e( 'Required Component Extension Status', 'stagekitwp-theme' ); ?>
                    </h2>
                    <div class="stagekitwp-health-grid-rows" style="display: flex; flex-direction: column; gap: 12px; margin-top:15px;">
                        <?php foreach ( $component_scores as $key => $metric ) : 
                            $bg_color     = '#edf7ed'; $border_color = '#46b450'; $text_color   = '#1e4620';
                            if ( $metric['status'] === 'warning' ) {
                                $bg_color = '#fff8e5'; $border_color = '#cca300'; $text_color = '#806600';
                            } elseif ( $metric['status'] === 'critical' ) {
                                $bg_color = '#fcf0f1'; $border_color = '#d63638'; $text_color = '#d63638';
                            }
                            ?>
                            <div style="display: grid; grid-template-columns: 1.5fr 2fr; align-items: center; padding: 14px 20px; background: <?php echo esc_attr( $bg_color ); ?>; border-left: 4px solid <?php echo esc_attr( $border_color ); ?>; border-radius: 4px;">
                                <div>
                                    <span style="font-weight:700; font-size:1rem; color:<?php echo esc_attr( $text_color ); ?>;">
                                        <?php echo ( $metric['status'] === 'good' ) ? '✅ ' : '❌ '; ?><?php echo esc_html( $metric['label'] ); ?>
                                    </span>
                                </div>
                                <div style="color:<?php echo esc_attr( $text_color ); ?>; font-size:0.95rem; font-weight:500;">
                                    <?php echo esc_html( $metric['msg'] ); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="stagekitwp-dashboard-panel" style="background: #ffffff; border: 1px solid #ccd0d4; padding: 25px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 20px;">
                    <h2 style="margin-top: 0; font-size: 1.4rem; font-weight: 700; padding-bottom: 12px; border-bottom: 1px solid #f0f0f1; color: #111111;">
                        🖥️ <?php esc_html_e( 'WordPress & Server Environment Health', 'stagekitwp-theme' ); ?>
                    </h2>
                    <div class="stagekitwp-health-grid-rows" style="display: flex; flex-direction: column; gap: 12px; margin-top:15px;">
                        <?php foreach ( $server_scores as $key => $metric ) : 
                            $bg_color     = '#edf7ed'; $border_color = '#46b450'; $text_color   = '#1e4620';
                            if ( $metric['status'] === 'warning' ) {
                                $bg_color = '#fff8e5'; $border_color = '#cca300'; $text_color = '#806600';
                            } elseif ( $metric['status'] === 'critical' ) {
                                $bg_color = '#fcf0f1'; $border_color = '#d63638'; $text_color = '#d63638';
                            }
                            ?>
                            <div style="display: grid; grid-template-columns: 1.5fr 2fr; align-items: center; padding: 14px 20px; background: <?php echo esc_attr( $bg_color ); ?>; border-left: 4px solid <?php echo esc_attr( $border_color ); ?>; border-radius: 4px;">
                                <div>
                                    <span style="font-weight:700; font-size:1rem; color:<?php echo esc_attr( $text_color ); ?>;">
                                        <?php echo ( $metric['status'] === 'good' ) ? '✅ ' : '⚠️ '; ?><?php echo esc_html( $metric['label'] ); ?>
                                    </span>
                                </div>
                                <div style="color:<?php echo esc_attr( $text_color ); ?>; font-size:0.95rem; font-weight:500;">
                                    <?php echo esc_html( $metric['msg'] ); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <div class="stagekitwp-dashboard-sidebar">
                
                <div class="stagekitwp-dashboard-panel" style="background: #ffffff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 20px;">
                    <h3 style="margin-top: 0; font-size: 1.1rem; font-weight: 700; padding-bottom: 10px; border-bottom: 1px solid #f0f0f1; color: #111111;">
                        📄 <?php esc_html_e( 'Theatre Sample Data Engine', 'stagekitwp-theme' ); ?>
                    </h3>
                    <p style="font-size: 0.85rem; color: #646970; line-height: 1.4; margin: 10px 0 15px 0;">
                        <?php esc_html_e( 'Generate mock production assets for custom post types, structural hierarchy layout templates, and nested menus.', 'stagekitwp-theme' ); ?>
                    </p>
                    
                    <form method="post" action="" style="display: flex; flex-direction: column; gap: 10px;">
                        <input type="hidden" name="stagekitwp_blueprint_nonce" value="<?php echo esc_attr( wp_create_nonce('stagekitwp_blueprint_action') ); ?>" />
                        
                        <button type="submit" name="stagekitwp_add_blueprints" class="button button-primary" style="background: #46b450; border-color: #349a3b; box-shadow: none; text-shadow: none; font-weight: 600; text-align: center; justify-content: center; display: flex; align-items: center; gap: 5px; padding: 5px;">
                            ➕ <?php esc_html_e( 'Generate Demo Dataset & Menus', 'stagekitwp-theme' ); ?>
                        </button>
                        
                        <button type="submit" name="stagekitwp_remove_blueprints" class="button button-link-delete" style="color: #b32d16; text-align: center; font-size: 0.85rem; text-decoration: none; padding: 5px 0; margin-top: 5px;" onclick="return confirm('<?php echo esc_js( __( 'Are you completely sure you want to purge all mock post logs, pages, and menus from database blocks?', 'stagekitwp-theme' ) ); ?>');">
                            🗑️ <?php esc_html_e( 'Wipe Demo Assets', 'stagekitwp-theme' ); ?>
                        </button>
                    </form>
                </div>

                <div class="stagekitwp-dashboard-panel" style="background: #ffffff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 20px;">
                    <h3 style="margin-top: 0; font-size: 1.1rem; font-weight: 700; padding-bottom: 10px; border-bottom: 1px solid #f0f0f1; color: #111111;">
                        ⚙️ <?php esc_html_e( 'Quick System Links', 'stagekitwp-theme' ); ?>
                    </h3>
                    <div style="display:flex; flex-direction:column; gap:10px; margin-top:15px;">
                        <a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>" class="button button-secondary" style="text-align:center;">
                            <?php esc_html_e( 'Manage Menus Panel', 'stagekitwp-theme' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=show' ) ); ?>" class="button button-secondary" style="text-align:center;">
                            <?php esc_html_e( 'Edit Shows (CPT)', 'stagekitwp-theme' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=season' ) ); ?>" class="button button-secondary" style="text-align:center;">
                            <?php esc_html_e( 'Edit Seasons (CPT)', 'stagekitwp-theme' ); ?>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <?php endif; ?>
    <?php
}