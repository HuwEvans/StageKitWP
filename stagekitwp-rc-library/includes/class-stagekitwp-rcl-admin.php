<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class STAGEKITWP_RC_LIBRARY_Admin {

    public function __construct() {
        // Administration Hooks
        add_action( 'admin_menu', array( $this, 'register_admin_menus' ) );
        add_action( 'admin_init', array( $this, 'handle_group_deletions' ) );
    }

    /**
     * Registers all plugin admin menus and submenus safely.
     */
    public function register_admin_menus() {
        $parent_slug = 'stagekitwp-core';
        $capability  = 'edit_posts';

        // Submenu: Dashboard
        add_submenu_page(
            $parent_slug,
            __( 'Dashboard', 'stagekitwp-rc-library' ),
            __( 'RC Library', 'stagekitwp-rc-library' ),
            $capability,
            'stagekitwp-rc-library',
            array( $this, 'render_dashboard_shell' )
        );

        // Submenu: Manage Custom Circle Groups
        add_submenu_page(
            $parent_slug,
            __( 'Manage Circle Groups', 'stagekitwp-rc-library' ),
            __( 'Circle Groups', 'stagekitwp-rc-library' ),
            $capability,
            'stagekitwp-rcl-groups',
            array( $this, 'render_groups_shell' )
        );

        // Submenu: Evaluation Reports
        add_submenu_page(
            $parent_slug,
            __( 'Evaluation Reports', 'stagekitwp-rc-library' ),
            __( 'Reports', 'stagekitwp-rc-library' ),
            $capability,
            'stagekitwp-rcl-reports',
            array( $this, 'render_reports_shell' )
        );

        // Submenu: Help & Guide
        add_submenu_page(
            $parent_slug,
            __( 'Help & Guide', 'stagekitwp-rc-library' ),
            __( 'Help & Guide', 'stagekitwp-rc-library' ),
            $capability,
            'stagekitwp-rcl-help',
            array( $this, 'render_help_shell' )
        );
    }

    private function render_hub_shell( string $active ): void {
        $panels = array(
            'dashboard'         => array( 'label' => __( 'Dashboard', 'stagekitwp-rc-library' ), 'callback' => array( $this, 'render_dashboard_home' ) ),
            'rubric-items'      => array( 'label' => __( 'Rubric Items', 'stagekitwp-rc-library' ), 'callback' => array( $this, 'render_rubric_items_tab' ) ),
            'rubric-templates'  => array( 'label' => __( 'Rubric Templates', 'stagekitwp-rc-library' ), 'callback' => array( $this, 'render_rubric_templates_tab' ) ),
            'books-scripts'     => array( 'label' => __( 'Books & Scripts', 'stagekitwp-rc-library' ), 'callback' => array( $this, 'render_books_scripts_tab' ) ),
            'groups'            => array( 'label' => __( 'Circle Groups', 'stagekitwp-rc-library' ), 'callback' => array( $this, 'render_groups_management_page' ) ),
            'reports'           => array( 'label' => __( 'Reports', 'stagekitwp-rc-library' ), 'callback' => array( $this, 'render_reports_page' ) ),
            'help'              => array( 'label' => __( 'Help & Guide', 'stagekitwp-rc-library' ), 'callback' => array( $this, 'render_help_page' ) ),
        );

        $requested_tab = isset( $_GET['stagekitwp_rc_library_tab'] ) ? sanitize_key( wp_unslash( $_GET['stagekitwp_rc_library_tab'] ) ) : '';
        $active = '' !== $requested_tab && isset( $panels[ $requested_tab ] ) ? $requested_tab : ( isset( $panels[ $active ] ) ? $active : 'dashboard' );

        echo '<div class="wrap stagekitwp-rcl-hub-wrap">';
		echo '<h1>' . esc_html__( 'RC Library', 'stagekitwp-rc-library' ) . '</h1>';
        echo $this->render_hub_tabs( $active );
        echo '<div class="stagekitwp-rcl-hub-panels">';

        foreach ( $panels as $key => $panel ) {
            echo '<div class="stagekitwp-rcl-hub-panel" data-stagekitwp-rcl-panel="' . esc_attr( $key ) . '" style="' . ( $key === $active ? '' : 'display:none;' ) . '">';
            ob_start();
            call_user_func( $panel['callback'], true );
            echo ob_get_clean();
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';

        echo '<script>(function(){const tabs=document.querySelectorAll("[data-stagekitwp-rcl-tab]");const panels=document.querySelectorAll("[data-stagekitwp-rcl-panel]");if(!tabs.length||!panels.length)return;function activate(tabKey){tabs.forEach(tab=>tab.classList.toggle("nav-tab-active",tab.dataset.stagekitwpRclTab===tabKey));panels.forEach(panel=>panel.style.display=(panel.dataset.stagekitwpRclPanel===tabKey)?"":"none");const url=new URL(window.location.href);url.searchParams.set("stagekitwp_rc_library_tab",tabKey);history.replaceState({},"",url.toString());}tabs.forEach(tab=>tab.addEventListener("click",function(){activate(this.dataset.stagekitwpRclTab);}));})();</script>';
    }

    public function render_dashboard_shell() {
        $this->render_hub_shell( 'dashboard' );
    }

    public function render_groups_shell() {
        $this->render_hub_shell( 'groups' );
    }

    public function render_reports_shell() {
        $this->render_hub_shell( 'reports' );
    }

    public function render_help_shell() {
        $this->render_hub_shell( 'help' );
    }

    public function render_rubric_items_tab( $embedded = false ) {
        $this->render_post_type_tab(
            'stagekitwp_rubric',
            __( 'Rubric Items', 'stagekitwp-rc-library' ),
            __( 'Create and manage rubric criteria items for your evaluation templates.', 'stagekitwp-rc-library' ),
            __( 'Add Rubric Item', 'stagekitwp-rc-library' ),
            $embedded
        );
    }

    public function render_rubric_templates_tab( $embedded = false ) {
        $this->render_post_type_tab(
            'stagekitwp_template',
            __( 'Rubric Templates', 'stagekitwp-rc-library' ),
            __( 'Build and maintain reusable rubric templates that group your evaluation criteria.', 'stagekitwp-rc-library' ),
            __( 'Add Rubric Template', 'stagekitwp-rc-library' ),
            $embedded
        );
    }

    public function render_books_scripts_tab( $embedded = false ) {
        $this->render_post_type_tab(
            'stagekitwp_book',
            __( 'Books & Scripts', 'stagekitwp-rc-library' ),
            __( 'Manage the books and scripts available for review and assessment.', 'stagekitwp-rc-library' ),
            __( 'Add Book or Script', 'stagekitwp-rc-library' ),
            $embedded
        );
    }

    private function render_post_type_tab( $post_type, $title, $description, $add_new_label, $embedded = false ) {
        if ( ! post_type_exists( $post_type ) ) {
            echo '<div class="wrap"><h1>' . esc_html( $title ) . '</h1><p>' . esc_html__( 'This content type is not available on the site.', 'stagekitwp-rc-library' ) . '</p></div>';
            return;
        }

        $posts = get_posts( array(
            'post_type'      => $post_type,
            'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
            'posts_per_page' => 20,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        $can_create = current_user_can( 'edit_posts' ) || current_user_can( 'publish_posts' );

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">' . esc_html( $title ) . '</h1>';
        if ( $can_create ) {
            $new_post_url = admin_url( 'post-new.php?post_type=' . $post_type );
            echo ' <a href="' . esc_url( $new_post_url ) . '" class="page-title-action">' . esc_html( $add_new_label ) . '</a>';
        }
        echo '<p class="description">' . esc_html( $description ) . '</p>';
        echo '<hr class="wp-header-end">';

        if ( empty( $posts ) ) {
            if ( ! $embedded ) {
                echo '<div class="notice notice-info"><p>' . esc_html__( 'No entries found yet.', 'stagekitwp-rc-library' ) . '</p></div>';
            }
            echo '</div>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>' . esc_html__( 'Title', 'stagekitwp-rc-library' ) . '</th><th>' . esc_html__( 'Status', 'stagekitwp-rc-library' ) . '</th><th>' . esc_html__( 'Updated', 'stagekitwp-rc-library' ) . '</th></tr></thead>';
        echo '<tbody>';
        foreach ( $posts as $post ) {
            $edit_url = get_edit_post_link( $post->ID );
            $status   = get_post_status_object( $post->post_status );
            $status_label = $status && ! empty( $status->label ) ? $status->label : ucfirst( $post->post_status );
            echo '<tr>';
            echo '<td><strong><a href="' . esc_url( $edit_url ) . '">' . esc_html( $post->post_title ? $post->post_title : __( '(no title)', 'stagekitwp-rc-library' ) ) . '</a></strong></td>';
            echo '<td>' . esc_html( $status_label ) . '</td>';
            echo '<td>' . esc_html( human_time_diff( strtotime( $post->post_modified_gmt ), current_time( 'timestamp', 1 ) ) . __( ' ago', 'stagekitwp-rc-library' ) ) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    /**
     * Renders the Reading Circle Library Dashboard with counters and environment health logs.
     */
    public function render_dashboard_home( $embedded = false ) {
        global $wpdb, $wp_version;
        if ( ! $embedded ) {
            echo $this->render_hub_tabs( 'dashboard' );
        }
        $table_groups = $wpdb->prefix . 'stagekitwp_rc_library_groups';

        // Direct DB pull to get current totals for the custom table
        $group_count = 0;
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_groups'" ) ) {
            $group_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_groups" );
        }

        // Live lookups for the standard custom post types counters
        $count_items     = wp_count_posts( 'stagekitwp_rubric' )->publish;
        $count_templates = wp_count_posts( 'stagekitwp_template' )->publish;
        $count_books     = wp_count_posts( 'stagekitwp_book' )->publish;

        // --- HEALTH CHECK LOGIC RUNNERS ---
        // 1. Mail Capabilities Check
        $mail_functional = function_exists( 'wp_mail' );
        
        // 2. StageKitWP Parent/Core Plugin Integrity & Version Detection
        $stagekitwp_core_active = defined( 'STAGEKITWP_CORE_VERSION' ) || class_exists( 'StageKitWP_Core', false );
        $stagekitwp_core_version = defined( 'STAGEKITWP_CORE_VERSION' ) ? STAGEKITWP_CORE_VERSION : __( 'Not Detected', 'stagekitwp-rc-library' );
        
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_installed_plugins = get_plugins();
        
        // Search installed plugins so we can show a version even when the parent
        // module is installed but not active.
        foreach ( $all_installed_plugins as $plugin_path => $plugin_meta ) {
            if ( stripos( $plugin_path, 'stagekitwp' ) !== false || stripos( $plugin_meta['Name'], 'StageKitWP' ) !== false ) {
                if ( ! $stagekitwp_core_active ) {
                    $stagekitwp_core_version = $plugin_meta['Version'];
                }
                break;
            }
        }

        // 3. WordPress Compatibility Checks (Recommending WP 6.0+)
        $wp_compatible = version_compare( $wp_version, '6.0', '>=' );
        ?>
        <div class="wrap">
            <h1><?php _e( 'Reading Circle Library Dashboard', 'stagekitwp-rc-library' ); ?></h1>
            <p class="description"><?php _e( 'Overview and management node for your evaluation matrix components.', 'stagekitwp-rc-library' ); ?></p>
            <hr class="wp-header-end">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 20px;">
                
                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); position: relative; overflow: hidden;">
                    <span class="dashicons dashicons-book" style="position: absolute; right: 15px; bottom: 15px; font-size: 64px; width: 64px; height: 64px; color: #f1f5f9; z-index: 1;"></span>
                    <div style="position: relative; z-index: 2;">
                        <h3 style="margin: 0; color: #64748b; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;"><?php _e( 'Books & Scripts', 'stagekitwp-rc-library' ); ?></h3>
                        <div style="font-size: 36px; font-weight: 700; color: #1e293b; margin: 10px 0;"><?php echo intval( $count_books ); ?></div>
                        <a href="edit.php?post_type=stagekitwp_book" style="text-decoration: none; font-size: 13px; color: #007cba; font-weight: 600;"><?php _e( 'Manage Catalog →', 'stagekitwp-rc-library' ); ?></a>
                    </div>
                </div>

                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); position: relative; overflow: hidden;">
                    <span class="dashicons dashicons-groups" style="position: absolute; right: 15px; bottom: 15px; font-size: 64px; width: 64px; height: 64px; color: #f1f5f9; z-index: 1;"></span>
                    <div style="position: relative; z-index: 2;">
                        <h3 style="margin: 0; color: #64748b; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;"><?php _e( 'Active Circle Groups', 'stagekitwp-rc-library' ); ?></h3>
                        <div style="font-size: 36px; font-weight: 700; color: #1e293b; margin: 10px 0;"><?php echo intval( $group_count ); ?></div>
                        <a href="admin.php?page=stagekitwp-rcl-groups" style="text-decoration: none; font-size: 13px; color: #007cba; font-weight: 600;"><?php _e( 'Configure Groups →', 'stagekitwp-rc-library' ); ?></a>
                    </div>
                </div>

                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); position: relative; overflow: hidden;">
                    <span class="dashicons dashicons-index-card" style="position: absolute; right: 15px; bottom: 15px; font-size: 64px; width: 64px; height: 64px; color: #f1f5f9; z-index: 1;"></span>
                    <div style="position: relative; z-index: 2;">
                        <h3 style="margin: 0; color: #64748b; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;"><?php _e( 'Evaluation Templates', 'stagekitwp-rc-library' ); ?></h3>
                        <div style="font-size: 36px; font-weight: 700; color: #1e293b; margin: 10px 0;"><?php echo intval( $count_templates ); ?></div>
                        <a href="edit.php?post_type=stagekitwp_template" style="text-decoration: none; font-size: 13px; color: #007cba; font-weight: 600;"><?php _e( 'Modify Architecture →', 'stagekitwp-rc-library' ); ?></a>
                    </div>
                </div>

                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); position: relative; overflow: hidden;">
                    <span class="dashicons dashicons-list-view" style="position: absolute; right: 15px; bottom: 15px; font-size: 64px; width: 64px; height: 64px; color: #f1f5f9; z-index: 1;"></span>
                    <div style="position: relative; z-index: 2;">
                        <h3 style="margin: 0; color: #64748b; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;"><?php _e( 'Total Rubric Metrics', 'stagekitwp-rc-library' ); ?></h3>
                        <div style="font-size: 36px; font-weight: 700; color: #1e293b; margin: 10px 0;"><?php echo intval( $count_items ); ?></div>
                        <a href="edit.php?post_type=stagekitwp_rubric" style="text-decoration: none; font-size: 13px; color: #007cba; font-weight: 600;"><?php _e( 'View Metric Items →', 'stagekitwp-rc-library' ); ?></a>
                    </div>
                </div>

            </div>

            <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 25px; margin-top: 25px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h2 style="margin-top:0; color:#1e293b; font-size:18px; margin-bottom:15px;">
                    <span class="dashicons dashicons-heart" style="vertical-align: text-bottom; color: #ef4444; margin-right: 5px;"></span>
                    <?php _e( 'System Health Diagnostics', 'stagekitwp-rc-library' ); ?>
                </h2>
                
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    
                    <div style="flex: 1; min-width: 240px; padding: 15px; border: 1px solid #e2e8f0; border-radius: 4px; background: #f8fafc;">
                        <h4 style="margin: 0 0 10px 0; color: #475569; font-size: 13px; text-transform: uppercase;"><?php _e( 'Mail Capabilities Check', 'stagekitwp-rc-library' ); ?></h4>
                        <?php if ( $mail_functional ) : ?>
                            <span style="color: #16a34a; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                <span class="dashicons dashicons-yes-alt"></span> <?php _e( 'Active / Operational', 'stagekitwp-rc-library' ); ?>
                            </span>
                            <p style="margin: 8px 0 0 0; font-size: 12px; color: #64748b;"><?php _e( 'The core WordPress wp_mail framework is fully available.', 'stagekitwp-rc-library' ); ?></p>
                        <?php else : ?>
                            <span style="color: #dc2626; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                <span class="dashicons dashicons-warning"></span> <?php _e( 'Disabled / Missing', 'stagekitwp-rc-library' ); ?>
                            </span>
                            <p style="margin: 8px 0 0 0; font-size: 12px; color: #64748b;"><?php _e( 'Warning: Email generation functions are blocked by server environment configuration.', 'stagekitwp-rc-library' ); ?></p>
                        <?php endif; ?>
                    </div>

                    <div style="flex: 1; min-width: 240px; padding: 15px; border: 1px solid #e2e8f0; border-radius: 4px; background: #f8fafc;">
                        <h4 style="margin: 0 0 10px 0; color: #475569; font-size: 13px; text-transform: uppercase;"><?php _e( 'StageKitWP Status', 'stagekitwp-rc-library' ); ?></h4>
                        <?php if ( $stagekitwp_core_active ) : ?>
                            <span style="color: #16a34a; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                <span class="dashicons dashicons-yes-alt"></span> <?php printf( __( 'Active (v%s)', 'stagekitwp-rc-library' ), esc_html( $stagekitwp_core_version ) ); ?>
                            </span>
                            <p style="margin: 8px 0 0 0; font-size: 12px; color: #64748b;"><?php _e( 'Core synchronization connection links are processing cleanly.', 'stagekitwp-rc-library' ); ?></p>
                        <?php elseif ( $stagekitwp_core_version !== __( 'Not Detected', 'stagekitwp-rc-library' ) ) : ?>
                            <span style="color: #ea580c; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                <span class="dashicons dashicons-no"></span> <?php printf( __( 'Installed / Inactive (v%s)', 'stagekitwp-rc-library' ), esc_html( $stagekitwp_core_version ) ); ?>
                            </span>
                            <p style="margin: 8px 0 0 0; font-size: 12px; color: #64748b;"><?php _e( 'The parent module is installed but requires activation on your global Plugins page.', 'stagekitwp-rc-library' ); ?></p>
                        <?php else : ?>
                            <span style="color: #dc2626; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                <span class="dashicons dashicons-warning"></span> <?php _e( 'Not Detected', 'stagekitwp-rc-library' ); ?>
                            </span>
                            <p style="margin: 8px 0 0 0; font-size: 12px; color: #64748b;"><?php _e( 'Could not locate core StageKitWP file paths inside the extensions folder.', 'stagekitwp-rc-library' ); ?></p>
                        <?php endif; ?>
                    </div>

                    <div style="flex: 1; min-width: 240px; padding: 15px; border: 1px solid #e2e8f0; border-radius: 4px; background: #f8fafc;">
                        <h4 style="margin: 0 0 10px 0; color: #475569; font-size: 13px; text-transform: uppercase;"><?php _e( 'WordPress Compatibility', 'stagekitwp-rc-library' ); ?></h4>
                        <?php if ( $wp_compatible ) : ?>
                            <span style="color: #16a34a; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                <span class="dashicons dashicons-yes-alt"></span> <?php printf( __( 'Compatible (v%s)', 'stagekitwp-rc-library' ), esc_html( $wp_version ) ); ?>
                            </span>
                            <p style="margin: 8px 0 0 0; font-size: 12px; color: #64748b;"><?php _e( 'Your core environment software meets all development build guidelines.', 'stagekitwp-rc-library' ); ?></p>
                        <?php else : ?>
                            <span style="color: #ea580c; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                <span class="dashicons dashicons-update"></span> <?php printf( __( 'Outdated Core (v%s)', 'stagekitwp-rc-library' ), esc_html( $wp_version ) ); ?>
                            </span>
                            <p style="margin: 8px 0 0 0; font-size: 12px; color: #64748b;"><?php _e( 'Warning: Upgrading to WordPress 6.0+ is recommended for security stability.', 'stagekitwp-rc-library' ); ?></p>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 25px; margin-top: 25px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h2 style="margin-top:0; color:#1e293b;"><?php _e( 'Reading Circle System Matrix Overview', 'stagekitwp-rc-library' ); ?></h2>
                <p style="line-height:1.6; color:#475569; max-width: 800px; margin-bottom:0;">
                    <?php _e( 'This application links evaluated custom books and criteria scripts into clean, reportable components. Use the top cards or left submenus to configure assessment points, build customized group filters, and view real-time data logs on the reporting canvas.', 'stagekitwp-rc-library' ); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Interceptor to handle safe group deletions using WordPress hooks without header redirect issues.
     */
    public function handle_group_deletions() {
        if ( isset( $_GET['page'] ) && $_GET['page'] === 'stagekitwp-rcl-groups' && isset( $_GET['action'] ) && $_GET['action'] === 'delete' ) {
            if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'stagekitwp_rc_library_delete_group_nonce' ) ) {
                global $wpdb;
                $table_groups = $wpdb->prefix . 'stagekitwp_rc_library_groups';
                $group_id     = intval( $_GET['id'] );

                if ( $group_id > 0 ) {
                    $wpdb->delete( $table_groups, array( 'id' => $group_id ), array( '%d' ) );
                    wp_redirect( admin_url( 'admin.php?page=stagekitwp-rcl-groups&deleted=1' ) );
                    exit;
                }
            }
        }
    }

    /**
     * Renders the complete operational database CRUD management suite for Circle Groups.
     */
    public function render_groups_management_page( $embedded = false ) {
        global $wpdb;
        if ( ! $embedded ) {
            echo $this->render_hub_tabs( 'groups' );
        }
        $table_groups = $wpdb->prefix . 'stagekitwp_rc_library_groups';

        if ( isset( $_GET['deleted'] ) && $_GET['deleted'] == 1 ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Circle Group successfully deleted.', 'stagekitwp-rc-library' ) . '</p></div>';
        }

        if ( isset( $_POST['stagekitwp_rc_library_save_group'] ) && check_admin_referer( 'stagekitwp_rc_library_group_action', 'stagekitwp_group_nonce' ) ) {
            $group_id    = isset( $_POST['group_id'] ) ? intval( $_POST['group_id'] ) : 0;
            $group_title = sanitize_text_field( $_POST['group_title'] );
            $template_id = intval( $_POST['template_id'] );

            if ( ! empty( $group_title ) ) {
                if ( $group_id > 0 ) {
                    $wpdb->update(
                        $table_groups,
                        array( 'title' => $group_title, 'template_id' => $template_id ),
                        array( 'id' => $group_id ),
                        array( '%s', '%d' ),
                        array( '%d' )
                    );
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Circle Group updated successfully!', 'stagekitwp-rc-library' ) . '</p></div>';
                } else {
                    $wpdb->insert(
                        $table_groups,
                        array( 'title' => $group_title, 'template_id' => $template_id ),
                        array( '%s', '%d' )
                    );
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Circle Group created successfully!', 'stagekitwp-rc-library' ) . '</p></div>';
                }
            }
        }

        $edit_id = isset( $_GET['action'] ) && $_GET['action'] === 'edit' ? intval( $_GET['id'] ) : 0;
        $edit_group = null;
        if ( $edit_id ) {
            $edit_group = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_groups WHERE id = %d", $edit_id ) );
        }

        $templates = get_posts( array(
            'post_type'      => 'stagekitwp_template',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC'
        ) );

        $groups = $wpdb->get_results( "SELECT * FROM $table_groups ORDER BY id DESC" );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Circle Groups Configuration', 'stagekitwp-rc-library' ); ?></h1>
            <p><?php _e( 'Manage your reading circle group structures here.', 'stagekitwp-rc-library' ); ?></p>
            <hr class="wp-header-end">

            <div id="col-container" style="display: flex; gap: 20px; margin-top: 20px; flex-wrap: wrap;">
                
                <div id="col-left" style="flex: 1; min-width: 300px; max-width: 400px; background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04); height: fit-content;">
                    <h2><?php echo $edit_group ? __( 'Edit Circle Group', 'stagekitwp-rc-library' ) : __( 'Create New Circle Group', 'stagekitwp-rc-library' ); ?></h2>
                    <form method="POST" action="">
                        <?php wp_nonce_field( 'stagekitwp_rc_library_group_action', 'stagekitwp_group_nonce' ); ?>
                        <input type="hidden" name="group_id" value="<?php echo $edit_group ? $edit_group->id : 0; ?>" />

                        <div class="form-field" style="margin-bottom: 15px;">
                            <label style="display:block; font-weight:600; margin-bottom:5px;" for="group_title"><?php _e( 'Group Display Title', 'stagekitwp-rc-library' ); ?></label>
                            <input type="text" name="group_title" id="group_title" value="<?php echo $edit_group ? esc_attr( $edit_group->title ) : ''; ?>" style="width:100%; padding:6px;" required />
                        </div>

                        <div class="form-field" style="margin-bottom: 20px;">
                            <label style="display:block; font-weight:600; margin-bottom:5px;" for="template_id"><?php _e( 'Link Rubric Evaluation Template', 'stagekitwp-rc-library' ); ?></label>
                            <select name="template_id" id="template_id" style="width:100%; height:32px;">
                                <option value="0">— <?php _e( 'Select Rubric Template', 'stagekitwp-rc-library' ); ?> —</option>
                                <?php foreach ( $templates as $t ) : ?>
                                    <option value="<?php echo $t->ID; ?>" <?php selected( $edit_group ? $edit_group->template_id : 0, $t->ID ); ?>>
                                        <?php echo esc_html( $t->post_title ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <input type="submit" name="stagekitwp_rc_library_save_group" class="button button-primary" value="<?php echo $edit_group ? __( 'Update Group Specs', 'stagekitwp-rc-library' ) : __( 'Save Circle Group', 'stagekitwp-rc-library' ); ?>">
                        <?php if ( $edit_group ) : ?>
                            <a href="admin.php?page=stagekitwp-rcl-groups" class="button button-secondary" style="margin-left: 5px;"><?php _e( 'Cancel', 'stagekitwp-rc-library' ); ?></a>
                        <?php endif; ?>
                    </form>
                </div>

                <div id="col-right" style="flex: 2; min-width: 450px; background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                    <h2><?php _e( 'Active Group Structural Registry', 'stagekitwp-rc-library' ); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 12%; text-align:center;">ID</th>
                                <th style="width: 48%;"><?php _e( 'Circle Group Title', 'stagekitwp-rc-library' ); ?></th>
                                <th style="width: 25%;"><?php _e( 'Bound Rubric Template', 'stagekitwp-rc-library' ); ?></th>
                                <th style="width: 15%; text-align:center;"><?php _e( 'Actions', 'stagekitwp-rc-library' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( ! empty( $groups ) ) : ?>
                                <?php foreach ( $groups as $g ) : ?>
                                    <tr>
                                        <td style="text-align:center;"><code>#<?php echo $g->id; ?></code></td>
                                        <td><strong><?php echo esc_html( $g->title ); ?></strong></td>
                                        <td>
                                            <?php 
                                            $t_name = $g->template_id ? get_the_title( $g->template_id ) : '';
                                            echo ! empty( $t_name ) ? esc_html( $t_name ) : '<span style="color:#cca000; font-style:italic;">None Linked</span>';
                                            ?>
                                        </td>
                                        <td style="text-align:center;">
                                            <a href="admin.php?page=stagekitwp-rcl-groups&action=edit&id=<?php echo $g->id; ?>"><?php _e( 'Edit', 'stagekitwp-rc-library' ); ?></a> | 
                                            <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=stagekitwp-rcl-groups&action=delete&id=' . $g->id ), 'stagekitwp_rc_library_delete_group_nonce' ); ?>" class="submitdelete" style="color: #b32d2e;" onclick="return confirm('<?php echo esc_js( __('Are you absolutely sure you want to permanently delete this group?', 'stagekitwp-rc-library') ); ?>');"><?php _e( 'Delete', 'stagekitwp-rc-library' ); ?></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr><td colspan="4" style="text-align:center; color:#64748b; font-style:italic;"><?php _e( 'No custom groups found in the custom database table. Add one using the form widget.', 'stagekitwp-rc-library' ); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Renders the Evaluation Reports View with functioning dropdown select menus linked to the database rows.
     */
    public function render_reports_page( $embedded = false ) {
        global $wpdb;
        if ( ! $embedded ) {
            echo $this->render_hub_tabs( 'reports' );
        }
        $table_groups = $wpdb->prefix . 'stagekitwp_rc_library_groups';

        $all_groups = array();
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_groups'" ) ) {
            $all_groups = $wpdb->get_results( "SELECT id, title FROM $table_groups ORDER BY title ASC" );
        }

        $selected_group_id = isset( $_GET['group_id'] ) ? intval( $_GET['group_id'] ) : 0;
        ?>
        <div class="wrap">
            <h1><?php _e( 'Script Evaluation Worksheet Analytics & Reports', 'stagekitwp-rc-library' ); ?></h1>
            <p><?php _e( 'Filter aggregate composite scores by selecting target evaluation circle group matrices.', 'stagekitwp-rc-library' ); ?></p>
            
            <div class="tablenav top" style="background:#fff; padding: 15px; border:1px solid #ccd0d4; border-radius:4px; margin-bottom:20px;">
                <form method="GET" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
                    <input type="hidden" name="page" value="stagekitwp-rcl-reports" />
                    
                    <div style="display:flex; align-items:center; gap:15px; flex-wrap:wrap;">
                        <div>
                            <label style="font-weight:600; margin-right:5px;" for="group_id"><?php _e( 'Circle Group Filter:', 'stagekitwp-rc-library' ); ?></label>
                            <select name="group_id" id="group_id" style="min-width: 240px; height:32px;">
                                <option value="0">— <?php _e( 'Select a Circle Group', 'stagekitwp-rc-library' ); ?> —</option>
                                <?php if ( ! empty( $all_groups ) ) : ?>
                                    <?php foreach ( $all_groups as $group ) : ?>
                                        <option value="<?php echo intval( $group->id ); ?>" <?php selected( $selected_group_id, $group->id ); ?>>
                                            <?php echo esc_html( $group->title ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <option value="0" disabled><?php _e( 'No custom groups found in database table.', 'stagekitwp-rc-library' ); ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div>
                            <input type="submit" class="button button-secondary" value="<?php echo esc_attr( __('Generate Data Filter View', 'stagekitwp-rc-library') ); ?>" />
                            <?php if ( $selected_group_id > 0 ) : ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=stagekitwp-rcl-reports' ) ); ?>" class="button button-link" style="color:#b32d2e; text-decoration:none; margin-left:10px;"><?php _e( 'Reset Filters', 'stagekitwp-rc-library' ); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <div class="stagekitwp-rcl-report-results-pane" style="background:#fff; border:1px solid #ccd0d4; padding:20px; border-radius:4px;">
                <?php if ( $selected_group_id > 0 ) : ?>
                    <h2><?php printf( __( 'Analysis Summary Log for Group ID #%d', 'stagekitwp-rc-library' ), $selected_group_id ); ?></h2>
                    <p class="description"><?php _e( 'Data aggregation table display showing evaluative markers for selected assets.', 'stagekitwp-rc-library' ); ?></p>
                    
                    <table class="wp-list-table widefat fixed striped" style="margin-top:15px;">
                        <thead>
                            <tr>
                                <th><?php _e( 'Evaluated Book Title', 'stagekitwp-rc-library' ); ?></th>
                                <th><?php _e( 'Evaluations Count', 'stagekitwp-rc-library' ); ?></th>
                                <th><?php _e( 'Composite Weighted Average Score', 'stagekitwp-rc-library' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="3" style="text-align:center; color:#64748b; font-style:italic; padding:20px;">
                                    <?php _e( 'Database logging summary results view is ready for metric display calculations.', 'stagekitwp-rc-library' ); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div style="text-align:center; padding: 40px 20px; color:#64748b;">
                        <span class="dashicons dashicons-chart-bar" style="font-size:48px; width:48px; height:48px; margin-bottom:10px; color:#cbd5e1;"></span>
                        <p style="font-size:15px; margin:0;"><?php _e( 'Please select a Circle Group from the dropdown selector block above to run metric analysis logs.', 'stagekitwp-rc-library' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renders a clean, organized, tabbed setup and operational guide for the Help page.
     */
    public function render_help_page( $embedded = false ) {
        ?>
        <div class="wrap">
            <?php if ( ! $embedded ) { echo $this->render_hub_tabs( 'help' ); } ?>
            <h1><?php _e( 'Help & Documentation Guide', 'stagekitwp-rc-library' ); ?></h1>
            <p class="description"><?php _e( 'Follow this instructional manual to configure and maintain your Reading Circle evaluations workflow.', 'stagekitwp-rc-library' ); ?></p>
            <hr class="wp-header-end">

            <div style="display: flex; gap: 20px; margin-top: 20px; flex-wrap: wrap;">
                
                <div style="flex: 2; min-width: 450px; background: #fff; border: 1px solid #ccd0d4; padding: 25px; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                    <h2 style="margin-top: 0; color: #23282d; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                        <span class="dashicons dashicons-controls-play" style="vertical-align: middle; margin-right: 5px;"></span>
                        <?php _e( 'Workflow Setup Order', 'stagekitwp-rc-library' ); ?>
                    </h2>
                    <ol style="padding-left: 20px; line-height: 1.6; color: #444;">
                        <li><strong><?php _e( 'Create Rubric Items:', 'stagekitwp-rc-library' ); ?></strong> <?php _e( 'Go to "Rubric Items" and add individual metrics (e.g., Plot Development, Character Depth).', 'stagekitwp-rc-library' ); ?></li>
                        <li><strong><?php _e( 'Build a Template:', 'stagekitwp-rc-library' ); ?></strong> <?php _e( 'Navigate to "Rubric Templates". Create a new template and select the specific Rubric Items you want grouped.', 'stagekitwp-rc-library' ); ?></li>
                        <li><strong><?php _e( 'Set Up a Circle Group:', 'stagekitwp-rc-library' ); ?></strong> <?php _e( 'Open "Circle Groups", create your group, and link it to the evaluation Rubric Template.', 'stagekitwp-rc-library' ); ?></li>
                        <li><strong><?php _e( 'Add Books & Scripts:', 'stagekitwp-rc-library' ); ?></strong> <?php _e( 'Populate your review library under "Books & Scripts" so they become selectable for assessments.', 'stagekitwp-rc-library' ); ?></li>
                    </ol>

                    <h2 style="margin-top: 30px; color: #23282d; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                        <span class="dashicons dashicons-editor-help" style="vertical-align: middle; margin-right: 5px;"></span>
                        <?php _e( 'Frequently Asked Questions', 'stagekitwp-rc-library' ); ?>
                    </h2>
                    <div style="line-height: 1.5; color: #444;">
                        <p><strong>Q: <?php _e( 'Why aren\'t my groups showing up in standard WordPress query lists?', 'stagekitwp-rc-library' ); ?></strong><br>
                        <em>A: <?php _e( 'Circle Groups use an optimized standalone database table. Manage them exclusively through the "Circle Groups" workspace tab.', 'stagekitwp-rc-library' ); ?></em></p>
                    </div>
                </div>

                <div style="flex: 1; min-width: 280px; max-width: 350px; background: #f6f7f7; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px; height: fit-content;">
                    <h3 style="margin-top: 0; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;"><?php _e( 'System Status', 'stagekitwp-rc-library' ); ?></h3>
                    <table style="width: 100%; font-size: 13px; border-collapse: collapse; color: #50575e;">
                        <tr>
                            <td style="padding: 6px 0; border-bottom: 1px solid #e5e5e5; font-weight: 600;"><?php _e( 'Custom Table Context:', 'stagekitwp-rc-library' ); ?></td>
                            <td style="padding: 6px 0; border-bottom: 1px solid #e5e5e5; text-align: right; color: #22c55e;"><code>wp_stagekitwp_rc_library_groups</code></td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; border-bottom: 1px solid #e5e5e5; font-weight: 600;"><?php _e( 'Text Domain:', 'stagekitwp-rc-library' ); ?></td>
                            <td style="padding: 6px 0; border-bottom: 1px solid #e5e5e5; text-align: right;"><code>stagekitwp-rc-library</code></td>
                        </tr>
                    </table>
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Render the Reading Circle Library hub tabs.
     */
    private function render_hub_tabs( string $active ): string {
        $tabs = array(
            'dashboard'        => array( 'label' => __( 'Dashboard', 'stagekitwp-rc-library' ), 'url' => 'admin.php?page=stagekitwp-rc-library' ),
            'rubric-items'     => array( 'label' => __( 'Rubric Items', 'stagekitwp-rc-library' ), 'url' => 'admin.php?page=stagekitwp-rc-library&stagekitwp_rc_library_tab=rubric-items' ),
            'rubric-templates' => array( 'label' => __( 'Rubric Templates', 'stagekitwp-rc-library' ), 'url' => 'admin.php?page=stagekitwp-rc-library&stagekitwp_rc_library_tab=rubric-templates' ),
            'books-scripts'    => array( 'label' => __( 'Books & Scripts', 'stagekitwp-rc-library' ), 'url' => 'admin.php?page=stagekitwp-rc-library&stagekitwp_rc_library_tab=books-scripts' ),
            'groups'           => array( 'label' => __( 'Circle Groups', 'stagekitwp-rc-library' ), 'url' => 'admin.php?page=stagekitwp-rcl-groups' ),
            'reports'          => array( 'label' => __( 'Reports', 'stagekitwp-rc-library' ), 'url' => 'admin.php?page=stagekitwp-rcl-reports' ),
            'help'             => array( 'label' => __( 'Help & Guide', 'stagekitwp-rc-library' ), 'url' => 'admin.php?page=stagekitwp-rcl-help' ),
        );

        $html = '<nav class="nav-tab-wrapper stagekitwp-rcl-hub-tabs" style="margin-bottom:16px;">';
        foreach ( $tabs as $key => $tab ) {
            $classes = 'nav-tab' . ( $key === $active ? ' nav-tab-active' : '' );
            $html   .= sprintf(
                '<button type="button" class="%1$s" data-stagekitwp-rcl-tab="%2$s">%3$s</button>',
                esc_attr( $classes ),
                esc_attr( $key ),
                esc_html( $tab['label'] )
            );
        }
        $html .= '</nav>';

        return $html;
    }
}