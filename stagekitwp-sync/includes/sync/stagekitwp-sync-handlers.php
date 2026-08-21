<?php
if (!is_admin()) return;

require_once plugin_dir_path(__FILE__) . '../api/class-stagekitwp-sync-graph-client.php';
require_once plugin_dir_path(__FILE__) . 'stagekitwp-sync-folder-discovery.php';
require_once plugin_dir_path(__FILE__) . 'stagekitwp-sync-generic-image-sync.php';
require_once plugin_dir_path(__FILE__) . 'stagekitwp-sync-advertiser-sync.php';
require_once plugin_dir_path(__FILE__) . 'stagekitwp-sync-board-member-sync.php';
require_once plugin_dir_path(__FILE__) . 'stagekitwp-sync-cast-sync.php';
require_once plugin_dir_path(__FILE__) . 'stagekitwp-sync-sponsors-sync.php';
require_once plugin_dir_path(__FILE__) . 'stagekitwp-sync-seasons-sync.php';
require_once plugin_dir_path(__FILE__) . 'stagekitwp-sync-shows-sync.php';
require_once plugin_dir_path(__FILE__) . 'stagekitwp-sync-contributors-sync.php';
require_once plugin_dir_path(__FILE__) . 'stagekitwp-sync-testimonials-sync.php';


stagekitwp_sync_log('INFO','Sync Handlers Loading');

add_action('wp_ajax_stagekitwp_sync_run', function() {
    stagekitwp_sync_log('info', 'AJAX handler triggered for sync');
    check_ajax_referer('stagekitwp_sync_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        stagekitwp_sync_log('error', 'Unauthorized sync attempt');
        wp_send_json_error('Unauthorized');
        return;
    }
    
    $cpt = isset($_POST['cpt']) ? sanitize_text_field($_POST['cpt']) : '';
    $dry_run = isset($_POST['dry_run']) ? filter_var($_POST['dry_run'], FILTER_VALIDATE_BOOLEAN) : false;
    
    stagekitwp_sync_log('info', 'Processing sync', ['cpt' => $cpt, 'dry_run' => $dry_run]);
    
    switch ($cpt) {
        case 'advertiser':
            $summary = stagekitwp_sync_advertisers($dry_run);
            break;
        case 'board_member':
            $summary = stagekitwp_sync_board_members($dry_run);
            break;
        case 'cast':
            $summary = stagekitwp_sync_cast($dry_run);
            break;
        case 'sponsor':
            $summary = stagekitwp_sync_sponsors($dry_run);
            break;
        case 'season':
            $summary = stagekitwp_sync_seasons($dry_run);
            break;
        case 'show':
            $summary = stagekitwp_sync_shows($dry_run);
            break;
        case 'contributor':
            $summary = stagekitwp_sync_contributors($dry_run);
            break;
        case 'testimonial':
            $summary = stagekitwp_sync_testimonials($dry_run);
            break;
        default:
            $summary = 'Unknown CPT type: ' . $cpt;
            stagekitwp_sync_log('error', 'Unknown CPT type', ['cpt' => $cpt]);
            break;
    }
    
    stagekitwp_sync_log('info', 'AJAX sync complete', ['cpt' => $cpt, 'summary' => $summary]);
    wp_send_json_success($summary);
});

/**
 * AJAX handler for syncing Seasons -> Shows -> Cast in specific order
 * This ensures lookup fields are kept in sync
 */
add_action('wp_ajax_stagekitwp_sync_ordered', function() {
    stagekitwp_sync_log('info', 'Ordered sync handler triggered');
    check_ajax_referer('stagekitwp_sync_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        stagekitwp_sync_log('error', 'Unauthorized ordered sync attempt');
        wp_send_json_error('Unauthorized');
        return;
    }
    
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


add_action('wp_ajax_stagekitwp_sync_log_event', function() {
    check_ajax_referer('stagekitwp_sync_nonce');
    $level = sanitize_text_field($_POST['level'] ?? 'info');
    $message = sanitize_text_field($_POST['message'] ?? '');
    $context = isset($_POST['context']) ? (array) $_POST['context'] : [];

    if (function_exists('stagekitwp_sync_log')) {
        stagekitwp_sync_log($level, $message, $context);
    }

    wp_send_json_success(['logged' => true]);
});


add_action('init', function() {
    if (function_exists('stagekitwp_sync_advertisers')) {
        stagekitwp_sync_log('INFO','stagekitwp_sync_advertisers is available');
    } else {
        stagekitwp_sync_log('INFO','stagekitwp_sync_advertisers is NOT available');
    }
});

