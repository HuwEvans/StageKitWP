<?php
defined('ABSPATH') || exit;

// Include required files
require_once STAGEKITWP_SYNC_PLUGIN_DIR . 'includes/sync/stagekitwp-sync-folder-discovery.php';
require_once STAGEKITWP_SYNC_PLUGIN_DIR . 'includes/sync/stagekitwp-sync-generic-image-sync.php';
require_once STAGEKITWP_SYNC_PLUGIN_DIR . 'includes/sync/stagekitwp-sync-advertiser-sync.php';
require_once STAGEKITWP_SYNC_PLUGIN_DIR . 'includes/sync/stagekitwp-sync-board-member-sync.php';
require_once STAGEKITWP_SYNC_PLUGIN_DIR . 'includes/sync/stagekitwp-sync-cast-sync.php';
require_once STAGEKITWP_SYNC_PLUGIN_DIR . 'includes/sync/stagekitwp-sync-sponsors-sync.php';
require_once STAGEKITWP_SYNC_PLUGIN_DIR . 'includes/sync/stagekitwp-sync-seasons-sync.php';
require_once STAGEKITWP_SYNC_PLUGIN_DIR . 'includes/sync/stagekitwp-sync-shows-sync.php';
require_once STAGEKITWP_SYNC_PLUGIN_DIR . 'includes/sync/stagekitwp-sync-contributors-sync.php';
require_once STAGEKITWP_SYNC_PLUGIN_DIR . 'includes/sync/stagekitwp-sync-testimonials-sync.php';

/**
 * Display the manual sync panel.
 *
 * @param bool $embedded When true, render panel content only.
 */
function stagekitwp_sync_page_sync_panel( $embedded = false ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'stagekitwp-sync' ) );
    }

    $sync_post_types = [ 'advertiser', 'board_member', 'cast', 'contributor', 'season', 'show', 'sponsor', 'testimonial' ];
    stagekitwp_sync_log( 'info', 'Manual sync page loaded' );
    if ( $embedded ) {
        ?>
        <div class="card">
            <h2><?php _e('Sync Options', 'stagekitwp-sync'); ?></h2>
            <p>
                <label>
                    <input type="checkbox" id="stagekitwp-sync-dry-run" />
                    <?php _e('Dry Run (Preview changes without making updates)', 'stagekitwp-sync'); ?>
                </label>
            </p>

            <div style="margin-bottom: 20px;">
                <p style="font-weight: bold; color: #0073aa; margin-bottom: 10px;">
                    <?php _e('⭐ Recommended Sync Order:', 'stagekitwp-sync'); ?>
                </p>
                <button id="stagekitwp-sync-ordered" class="button button-primary" style="background-color: #0073aa; border-color: #0073aa;">
                    <?php _e('Sync Seasons → Shows → Cast', 'stagekitwp-sync'); ?>
                </button>
                <p style="font-size: 0.9em; color: #666; margin-top: 8px;">
                    <?php _e('This syncs in the correct order to maintain lookup field relationships', 'stagekitwp-sync'); ?>
                </p>
                <div id="stagekitwp-sync-ordered-status" class="notice inline" style="display:none;margin-top:10px;"></div>
            </div>

            <hr>

            <p style="font-weight: bold; margin-top: 20px; margin-bottom: 10px;">
                <?php _e('Or sync all types:', 'stagekitwp-sync'); ?>
            </p>
            <button id="stagekitwp-sync-all" class="button button-secondary">
                <?php _e('Run All Syncs', 'stagekitwp-sync'); ?>
            </button>
            <div id="stagekitwp-sync-global-status" class="notice inline" style="display:none;margin-top:10px;"></div>
        </div>

        <div class="sync-sections">
            <?php foreach ( $sync_post_types as $post_type_slug ) : ?>
                <div class="card">
					<h3><?php echo esc_html( ucfirst( str_replace( '_', ' ', $post_type_slug ) ) ); ?></h3>
					<button class="stagekitwp-sync-button button" data-cpt="<?php echo esc_attr( $post_type_slug ); ?>">
                        <?php _e('Run Sync', 'stagekitwp-sync'); ?>
                    </button>
					<div id="stagekitwp-sync-status-<?php echo esc_attr( $post_type_slug ); ?>" class="notice inline" style="display:none;margin-top:10px;"></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div class="card">
            <h2><?php _e('Sync Options', 'stagekitwp-sync'); ?></h2>
            <p>
                <label>
                    <input type="checkbox" id="stagekitwp-sync-dry-run" />
                    <?php _e('Dry Run (Preview changes without making updates)', 'stagekitwp-sync'); ?>
                </label>
            </p>

            <div style="margin-bottom: 20px;">
                <p style="font-weight: bold; color: #0073aa; margin-bottom: 10px;">
                    <?php _e('⭐ Recommended Sync Order:', 'stagekitwp-sync'); ?>
                </p>
                <button id="stagekitwp-sync-ordered" class="button button-primary" style="background-color: #0073aa; border-color: #0073aa;">
                    <?php _e('Sync Seasons → Shows → Cast', 'stagekitwp-sync'); ?>
                </button>
                <p style="font-size: 0.9em; color: #666; margin-top: 8px;">
                    <?php _e('This syncs in the correct order to maintain lookup field relationships', 'stagekitwp-sync'); ?>
                </p>
                <div id="stagekitwp-sync-ordered-status" class="notice inline" style="display:none;margin-top:10px;"></div>
            </div>

            <hr>

            <p style="font-weight: bold; margin-top: 20px; margin-bottom: 10px;">
                <?php _e('Or sync all types:', 'stagekitwp-sync'); ?>
            </p>
            <button id="stagekitwp-sync-all" class="button button-secondary">
                <?php _e('Run All Syncs', 'stagekitwp-sync'); ?>
            </button>
            <div id="stagekitwp-sync-global-status" class="notice inline" style="display:none;margin-top:10px;"></div>
        </div>

        <div class="sync-sections">
            <?php foreach ( $sync_post_types as $post_type_slug ) : ?>
                <div class="card">
					<h3><?php echo esc_html( ucfirst( str_replace( '_', ' ', $post_type_slug ) ) ); ?></h3>
					<button class="stagekitwp-sync-button button" data-cpt="<?php echo esc_attr( $post_type_slug ); ?>">
                        <?php _e('Run Sync', 'stagekitwp-sync'); ?>
                    </button>
					<div id="stagekitwp-sync-status-<?php echo esc_attr( $post_type_slug ); ?>" class="notice inline" style="display:none;margin-top:10px;"></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <style>
        .sync-sections {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .card {
            padding: 15px;
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin-top: 20px;
        }
    </style>
    <?php
}

// Register AJAX handler
add_action('wp_ajax_stagekitwp_sync_run', function() {
    check_ajax_referer('stagekitwp_sync_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
        return;
    }

    stagekitwp_sync_log('debug', 'AJAX sync handler started');

    $requested_post_type = isset( $_POST['cpt'] ) ? sanitize_text_field( wp_unslash( $_POST['cpt'] ) ) : '';
    $dry_run = isset($_POST['dry_run']) ? filter_var($_POST['dry_run'], FILTER_VALIDATE_BOOLEAN) : false;

    stagekitwp_sync_log('info', 'Starting AJAX sync.', [
        'cpt' => $requested_post_type,
        'dry_run' => $dry_run,
        'post_data' => $_POST
    ]);

    $result = '';
    switch ( $requested_post_type ) {
        case 'advertiser':
            $result = stagekitwp_sync_advertisers($dry_run);
            break;
        case 'board_member':
            $result = stagekitwp_sync_board_members($dry_run);
            break;
        case 'cast':
            $result = stagekitwp_sync_cast($dry_run);
            break;
        case 'sponsor':
            $result = stagekitwp_sync_sponsors($dry_run);
            break;
        case 'season':
            $result = stagekitwp_sync_seasons($dry_run);
            break;
        case 'show':
            $result = stagekitwp_sync_shows($dry_run);
            break;
        case 'contributor':
            $result = stagekitwp_sync_contributors($dry_run);
            break;
        case 'testimonial':
            $result = stagekitwp_sync_testimonials($dry_run);
            break;
        default:
            $result = 'Unknown CPT type: ' . $requested_post_type;
            stagekitwp_sync_log('error', 'Unknown CPT type', ['cpt' => $requested_post_type]);
            break;
    }

    stagekitwp_sync_log('info', 'AJAX sync complete.', [
        'cpt' => $requested_post_type,
        'result' => $result
    ]);

    wp_send_json_success($result);
});

/**
 * AJAX handler for syncing Seasons -> Shows -> Cast in specific order
 * This ensures lookup fields are kept in sync
 */
add_action('wp_ajax_stagekitwp_sync_ordered', function() {
    check_ajax_referer('stagekitwp_sync_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
        return;
    }

    stagekitwp_sync_log('debug', 'Ordered sync handler started');

    $dry_run = isset($_POST['dry_run']) ? filter_var($_POST['dry_run'], FILTER_VALIDATE_BOOLEAN) : false;

    stagekitwp_sync_log('info', 'Starting ordered sync (Seasons -> Shows -> Cast)', ['dry_run' => $dry_run]);

    $results = [];

    // Step 1: Sync Seasons first (dependencies: none)
    stagekitwp_sync_log('info', 'Step 1: Syncing Seasons');
    $results['season'] = stagekitwp_sync_seasons($dry_run);

    // Step 2: Sync Shows (depends on Seasons for lookup field)
    stagekitwp_sync_log('info', 'Step 2: Syncing Shows');
    $results['show'] = stagekitwp_sync_shows($dry_run);

    // Step 3: Sync Cast (depends on Shows for lookup field)
    stagekitwp_sync_log('info', 'Step 3: Syncing Cast');
    $results['cast'] = stagekitwp_sync_cast($dry_run);

    stagekitwp_sync_log('info', 'Ordered sync complete', ['results' => $results]);

    $summary = "Seasons: " . $results['season'] . " | Shows: " . $results['show'] . " | Cast: " . $results['cast'];
    wp_send_json_success($summary);
});

function stagekitwp_sync_enqueue_admin_scripts( $hook_suffix ) {
    if ( $hook_suffix !== 'toplevel_page_stagekitwp-sync' ) {
        return;
    }

    $version = defined('STAGEKITWP_CORE_SYNC_VERSION') ? STAGEKITWP_CORE_SYNC_VERSION : '3.0.0';
    
    wp_enqueue_script(
        'stagekitwp-sync-admin',
        plugin_dir_url(__FILE__) . '../assets/js/admin-sync.js',
        ['jquery'],
        $version,
        true
    );

    wp_localize_script('stagekitwp-sync-admin', 'stagekitwp_sync_vars', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('stagekitwp_sync_nonce'),
        'debug'    => WP_DEBUG
    ]);

    stagekitwp_sync_log('debug', 'Admin scripts enqueued for sync page');
}
add_action('admin_enqueue_scripts', 'stagekitwp_sync_enqueue_admin_scripts');

/**
 * Load screen assets for the admin page.
 */
function stagekitwp_sync_load_admin_screen() {
    stagekitwp_sync_log('debug', 'Admin page loaded');
}
