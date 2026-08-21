<?php
defined('ABSPATH') || exit;

if ( ! defined('STAGEKITWP_SYNC_CAP') ) {
    // Capability required to access TM Sync admin pages.
    define('STAGEKITWP_SYNC_CAP', 'manage_options');
}

    require_once STAGEKITWP_SYNC_PLUGIN_DIR . 'admin/stagekitwp-sync-auth-page.php';
    require_once STAGEKITWP_SYNC_PLUGIN_DIR . 'admin/stagekitwp-sync-details-page.php';
/**
 * Register the TM Sync admin menu and submenus.
 */
add_action('admin_menu', 'stagekitwp_sync_register_admin_menu');

/**
 * Render the TM Sync hub tabs.
 *
 * @param string $active Active tab key.
 * @return string
 */
function stagekitwp_sync_render_hub_tabs( $active ) {
    $tabs = array(
        'manual'   => array( 'label' => __( 'Manual Sync', 'stagekitwp-sync' ), 'page' => 'stagekitwp-sync', 'cap' => STAGEKITWP_SYNC_CAP ),
        'auth'     => array( 'label' => __( 'Authentication', 'stagekitwp-sync' ), 'page' => 'stagekitwp-sync-auth', 'cap' => STAGEKITWP_SYNC_CAP ),
        'logs'     => array( 'label' => __( 'Logs', 'stagekitwp-sync' ), 'page' => 'stagekitwp-sync-logs', 'cap' => STAGEKITWP_SYNC_CAP ),
        'details'  => array( 'label' => __( 'Details', 'stagekitwp-sync' ), 'page' => 'stagekitwp-sync-details', 'cap' => STAGEKITWP_SYNC_CAP ),
        'settings' => array( 'label' => __( 'Settings', 'stagekitwp-sync' ), 'page' => 'stagekitwp-sync-settings', 'cap' => STAGEKITWP_SYNC_CAP ),
    );

    $html = '<nav class="nav-tab-wrapper stagekitwp-sync-hub-tabs" style="margin-bottom:16px;">';
    foreach ( $tabs as $key => $tab ) {
        if ( isset( $tab['cap'] ) && ! current_user_can( $tab['cap'] ) ) {
            continue;
        }
        $classes = 'nav-tab' . ( $key === $active ? ' nav-tab-active' : '' );
        $html   .= sprintf(
            '<button type="button" class="%1$s" data-stagekitwp-sync-tab="%2$s">%3$s</button>',
            esc_attr( $classes ),
            esc_attr( $key ),
            esc_html( $tab['label'] )
        );
    }
    $html .= '</nav>';

    return $html;
}

/**
 * Determine where TM Sync should attach in the admin menu.
 *
 * @return string
 */
function stagekitwp_sync_admin_parent_slug() {
    return 'stagekitwp-core';
}

function stagekitwp_sync_register_admin_menu() {
    $parent_slug = stagekitwp_sync_admin_parent_slug();

    // Submenu: TM Sync (hub/main page)
    $parent_hook = add_submenu_page(
        $parent_slug,
        __('Sync', 'stagekitwp-sync'),
        __('Sync', 'stagekitwp-sync'),
        STAGEKITWP_SYNC_CAP,
        'stagekitwp-sync',
        'stagekitwp_sync_page_sync'
    );

    // Submenu: Auth
    $auth_hook = add_submenu_page(
        $parent_slug,
        __('Sync – Authentication', 'stagekitwp-sync'),
        __('Authentication', 'stagekitwp-sync'),
        STAGEKITWP_SYNC_CAP,
        'stagekitwp-sync-auth',
        'stagekitwp_sync_page_auth'
    );

    // Submenu: Logs
    $logs_hook = add_submenu_page(
        $parent_slug,
        __('Sync – Logs', 'stagekitwp-sync'),
        __('Logs', 'stagekitwp-sync'),
        STAGEKITWP_SYNC_CAP,
        'stagekitwp-sync-logs',
        'stagekitwp_sync_page_logs'
    );

    // Submenu: Details
    $details_hook = add_submenu_page(
        $parent_slug,
        __('Sync – Details', 'stagekitwp-sync'),
        __('Details', 'stagekitwp-sync'),
        STAGEKITWP_SYNC_CAP,
        'stagekitwp-sync-details',
        'stagekitwp_sync_page_details'
    );

    // Per-screen hooks for assets and help tabs
    add_action("load-$parent_hook", 'stagekitwp_sync_load_admin_screen');
    add_action("load-$auth_hook", 'stagekitwp_sync_load_auth_screen');
    add_action("load-$logs_hook", 'stagekitwp_sync_load_logs_screen');
    add_action("load-$details_hook", 'stagekitwp_sync_load_details_screen');
}

/** =========================
 *  Page callbacks (renderers)
 *  ========================= */

function stagekitwp_sync_cap_check() {
    if ( ! current_user_can(STAGEKITWP_SYNC_CAP) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'stagekitwp-sync' ) );
    }
}

function stagekitwp_sync_render_hub_shell( $active, $panels ) {
    $active = isset( $panels[ $active ] ) ? $active : array_key_first( $panels );

    echo '<div class="wrap stagekitwp-sync-hub-wrap">';
    echo '<h1>Sync</h1>';
    echo stagekitwp_sync_render_hub_tabs( $active );
    echo '<div class="stagekitwp-sync-hub-panels">';

    foreach ( $panels as $key => $callback ) {
        echo '<div class="stagekitwp-sync-hub-panel" data-stagekitwp-sync-panel="' . esc_attr( $key ) . '" style="' . ( $key === $active ? '' : 'display:none;' ) . '">';
        call_user_func( $callback, true );
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';

    echo '<script>(function(){const tabs=document.querySelectorAll("[data-stagekitwp-sync-tab]");const panels=document.querySelectorAll("[data-stagekitwp-sync-panel]");if(!tabs.length||!panels.length)return;function activate(tabKey){tabs.forEach(tab=>tab.classList.toggle("nav-tab-active",tab.dataset.stagekitwpSyncTab===tabKey));panels.forEach(panel=>panel.style.display=(panel.dataset.stagekitwpSyncPanel===tabKey)?"":"none");const url=new URL(window.location.href);url.searchParams.set("stagekitwp_sync_tab",tabKey);history.replaceState({},"",url.toString());}tabs.forEach(tab=>tab.addEventListener("click",function(){activate(this.dataset.stagekitwpSyncTab);}));})();</script>';
}


function stagekitwp_sync_page_sync() {
    stagekitwp_sync_cap_check();
    $panels = array(
        'manual'   => 'stagekitwp_sync_page_sync_panel',
        'auth'     => 'stagekitwp_sync_page_auth',
        'logs'     => 'stagekitwp_sync_page_logs',
        'details'  => 'stagekitwp_sync_page_details',
        'settings' => 'stagekitwp_sync_render_settings_page',
    );
    stagekitwp_sync_render_hub_shell( 'manual', $panels );
}




/** =========================
 *  load-{$hook_suffix} handlers
 *  (enqueue assets, add help tabs, screen options)
 *  ========================= */

function stagekitwp_sync_load_sync_screen() { /* ... */ }
function stagekitwp_sync_load_logs_screen() { /* ... */ }
function stagekitwp_sync_load_details_screen() { /* ... */ }
