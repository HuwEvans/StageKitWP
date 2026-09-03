<?php
/**
 * Plugin Name: StageKit WP Media
 * Description: High-performance media asset manager, hierarchical folder management, isolated storage outside core WordPress media, and galleries sourced from external media APIs (Pexels, Unsplash, Flickr, YouTube, Vimeo, Dropbox, Google Drive, Google Photos).
 * Version: 1.0.0
 * Author: Huw Evans
 * Author URI: https://github.com/HuwEvans
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Text Domain: stagekitwp-media
 */

defined( 'ABSPATH' ) || exit;

define( 'SKWPM_VERSION', '1.0.0' );
define( 'SKWPM_PATH', plugin_dir_path( __FILE__ ) );
define( 'SKWPM_URL', plugin_dir_url( __FILE__ ) );

require_once SKWPM_PATH . 'includes/class-schema.php';
require_once SKWPM_PATH . 'includes/class-storage.php';
require_once SKWPM_PATH . 'includes/class-folder-repository.php';
require_once SKWPM_PATH . 'includes/class-media-repository.php';
require_once SKWPM_PATH . 'includes/class-diagnostics.php';
require_once SKWPM_PATH . 'includes/class-provider.php';
require_once SKWPM_PATH . 'includes/class-stagekit-provider.php';
require_once SKWPM_PATH . 'includes/class-http.php';
require_once SKWPM_PATH . 'includes/class-debug.php';
require_once SKWPM_PATH . 'includes/class-registry.php';
require_once SKWPM_PATH . 'includes/class-oauth.php';
require_once SKWPM_PATH . 'includes/class-pexels.php';
require_once SKWPM_PATH . 'includes/class-unsplash.php';
require_once SKWPM_PATH . 'includes/class-flickr.php';
require_once SKWPM_PATH . 'includes/class-youtube.php';
require_once SKWPM_PATH . 'includes/class-vimeo.php';
require_once SKWPM_PATH . 'includes/class-dropbox.php';
require_once SKWPM_PATH . 'includes/class-media-importer.php';
require_once SKWPM_PATH . 'includes/class-google-drive.php';
require_once SKWPM_PATH . 'includes/class-google-photos.php';
require_once SKWPM_PATH . 'includes/class-rest-folders.php';
require_once SKWPM_PATH . 'includes/class-rest-media.php';
require_once SKWPM_PATH . 'includes/class-cpt.php';
require_once SKWPM_PATH . 'includes/class-settings.php';
require_once SKWPM_PATH . 'includes/class-shortcode.php';
require_once SKWPM_PATH . 'includes/class-admin-media-manager.php';
require_once SKWPM_PATH . 'includes/class-admin-gallery.php';
require_once SKWPM_PATH . 'includes/class-ajax.php';

add_action( 'plugins_loaded', function () {
	SKWPM\Schema::maybe_update();
	SKWPM\Storage::init_storage();

	SKWPM\Registry::register( new SKWPM\StageKitProvider() );
	SKWPM\Registry::register( new SKWPM\Pexels() );
	SKWPM\Registry::register( new SKWPM\Unsplash() );
	SKWPM\Registry::register( new SKWPM\Flickr() );
	SKWPM\Registry::register( new SKWPM\YouTube() );
	SKWPM\Registry::register( new SKWPM\Vimeo() );
	SKWPM\Registry::register( new SKWPM\Dropbox() );
	SKWPM\Registry::register( new SKWPM\GoogleDrive() );
	SKWPM\Registry::register( new SKWPM\GooglePhotos() );

	SKWPM\OAuth::init();
	SKWPM\RESTFolders::init();
	SKWPM\RESTMedia::init();
	SKWPM\CPT::init();
	SKWPM\Settings::init();
	SKWPM\Shortcode::init();
	SKWPM\AdminMediaManager::init();
	SKWPM\AdminGallery::init();
	SKWPM\Ajax::init();
} );

// Registering the CPT alone doesn't add its rewrite rules to the DB; flush once on activation.
register_activation_hook( __FILE__, function () {
	SKWPM\Schema::install();
	SKWPM\Storage::init_storage();
	SKWPM\CPT::register();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
