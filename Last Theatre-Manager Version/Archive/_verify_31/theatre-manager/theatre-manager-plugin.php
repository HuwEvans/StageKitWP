<?php
/**
 * Plugin Name: Theatre Manager
 * Plugin URI: https://miltonplayers.com/plugin
 * Description: Manage theatre-related content including board members, shows, and more.
 * Version: 3.1
 * Requires at least: 6.8.2
 * Requires PHP: 
 * Author: Huw Evans
 * Author URI: http://github.com/HuwEvans
 * License: GPL2
 * License URI: http://github.com/HuwEvans
 */

defined('ABSPATH') || exit;

// Define plugin paths
define('TM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TM_PLUGIN_URL', plugin_dir_url(__FILE__));
// Public version constant used by other plugins
if ( ! defined('THEATRE_MANAGER_VERSION') ) {
    define('THEATRE_MANAGER_VERSION', '3.1');
}

// Include core files
require_once TM_PLUGIN_DIR . 'includes/functions.php';
require_once TM_PLUGIN_DIR . 'includes/helpers.php';
require_once TM_PLUGIN_DIR . 'includes/admin-menu.php';
require_once TM_PLUGIN_DIR . 'includes/sample-content.php';
require_once TM_PLUGIN_DIR . 'includes/season-builder.php';

// Load CPTs
foreach (glob(TM_PLUGIN_DIR . 'cpt/*.php') as $cpt_file) {
    require_once $cpt_file;
}

// Load Shortcodes
foreach (glob(TM_PLUGIN_DIR . 'includes/shortcodes/*.php') as $shortcode_file) {
    require_once $shortcode_file;
}
// Script and style enqueuing is handled in includes/functions.php


