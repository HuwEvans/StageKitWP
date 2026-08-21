<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Enqueue admin scripts and styles
 */
function tm_enqueue_admin_assets($hook) {
    global $post_type;
    $is_tm_builder = strpos($hook, 'tm-season-builder') !== false;

    // Load only for Theatre Manager CPTs
    $cpts = array('board_member', 'advertiser', 'sponsors', 'testimonials', 'contributors', 'season', 'show', 'cast', 'award'); // Add more CPTs here as needed

    if (in_array($post_type, $cpts, true) || $is_tm_builder) {
        wp_enqueue_media();
        wp_enqueue_script(
            'tm-admin-js',
            TM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        wp_enqueue_style(
            'tm-admin-css',
            TM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            '1.0.0'
        );
    }

    if (strpos($hook, 'post.php') !== false || strpos($hook, 'post-new.php') !== false || $is_tm_builder) {
        wp_enqueue_media();
        wp_enqueue_script('tm-admin-media', TM_PLUGIN_URL . 'assets/js/admin-media.js', array('jquery'), '1.0.0' , true);
    }

}
add_action('admin_enqueue_scripts', 'tm_enqueue_admin_assets');

function tm_enqueue_admin_scripts($hook) {
    // Only load on Theatre Manager admin pages
    if (strpos($hook, 'tm-display-options') === false) {
        return;
    }

    // Enqueue WordPress color picker
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');

    // Enqueue custom admin JS
    wp_enqueue_script('tm-admin-js', TM_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'wp-color-picker'), null, true);
}
add_action('admin_enqueue_scripts', 'tm_enqueue_admin_scripts');
/**
 * Enqueue shortcodes CSS on the frontend
 */
function tm_enqueue_shortcodes_css() {
    wp_enqueue_style(
        'tm-shortcodes-css',
        TM_PLUGIN_URL . 'assets/css/shortcodes.css',
        array(),
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'tm_enqueue_shortcodes_css');

// Note: admin scripts are enqueued in tm_enqueue_admin_assets above when needed
function tm_enqueue_swiper_assets() {
    wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css');
    wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js', array(), null, true);
    wp_add_inline_script('swiper-js', '
        document.addEventListener("DOMContentLoaded", function () {
            new Swiper(".tm-testimonials-slider", {
                loop: true,
                pagination: {
                    el: ".swiper-pagination",
                    clickable: true,
                },
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
                autoplay: {
                    delay: 5000,
                },
            });
        });
    ');
}
add_action('wp_enqueue_scripts', 'tm_enqueue_swiper_assets');

function tm_enqueue_font_awesome() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css');
}
add_action('wp_enqueue_scripts', 'tm_enqueue_font_awesome');

function tm_enqueue_slick_slider_assets() {
    // Slick CSS
    wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css');
    wp_enqueue_style('slick-theme-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css');

    // Slick JS
    wp_enqueue_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', ['jquery'], null, true);

    // Custom init script
    wp_enqueue_script('tm-slick-init', plugin_dir_url(__FILE__) . '../assets/js/tm-slick-init.js', ['jquery', 'slick-js'], null, true);
}
add_action('wp_enqueue_scripts', 'tm_enqueue_slick_slider_assets');


function tm_enqueue_cast_styles() {
    wp_enqueue_style('tm-season-cast-table', plugin_dir_url(__FILE__) . '../assets/css/tm_season_cast_table.css');
}
add_action('wp_enqueue_scripts', 'tm_enqueue_cast_styles');


/**
 * Enqueue PDF.js and our PDF preview script for front-end rendering fallback
 */
function tm_enqueue_pdf_preview_assets() {
    // PDF.js from CDN
    wp_enqueue_script('tm-pdfjs', 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js', array(), null, true);
    // Our preview renderer
    wp_enqueue_script('tm-pdf-preview', TM_PLUGIN_URL . 'assets/js/pdf-preview.js', array('tm-pdfjs', 'jquery'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'tm_enqueue_pdf_preview_assets');


/**
 * Generate a JPEG preview for uploaded PDF attachments when possible.
 * Stores the preview URL in attachment meta key '_tm_pdf_preview'.
 */
function tm_generate_pdf_preview_on_upload($attachment_id) {
    // Delegate to generator that returns diagnostics
    $result = tm_generate_pdf_preview($attachment_id);
    // If generator returned WP_Error or failure, just exit (we don't want to surface errors during normal upload)
    return;
}
add_action('add_attachment', 'tm_generate_pdf_preview_on_upload');


/**
 * Generate a JPEG preview for a PDF attachment and return diagnostics.
 * Returns array: ['success' => bool, 'message' => string]
 */
function tm_generate_pdf_preview($attachment_id) {
    $mime = get_post_mime_type($attachment_id);
    if ($mime !== 'application/pdf') {
        return array('success' => false, 'message' => 'Not a PDF.');
    }

    if (!class_exists('Imagick')) {
        return array('success' => false, 'message' => 'Imagick PHP extension not available.');
    }

    $file = get_attached_file($attachment_id);
    if (!$file || !file_exists($file)) {
        return array('success' => false, 'message' => 'File not found: ' . $file);
    }

    $upload_dir = wp_upload_dir();

    try {
        $imagick = new Imagick();
        // read first page
        $imagick->setResolution(150,150);
        $imagick->readImage($file . '[0]');
        $imagick->setImageFormat('jpeg');
        $imagick->setImageCompressionQuality(80);

        $max_width = 1200;
        if ($imagick->getImageWidth() > $max_width) {
            $imagick->thumbnailImage($max_width, 0);
        }

        $orig_filename = wp_basename($file);
        $thumb_filename = pathinfo($orig_filename, PATHINFO_FILENAME) . '-preview.jpg';
        $dest_path = trailingslashit($upload_dir['path']) . $thumb_filename;

        if ($imagick->writeImage($dest_path)) {
            $preview_url = trailingslashit($upload_dir['url']) . $thumb_filename;
            update_post_meta($attachment_id, '_tm_pdf_preview', esc_url_raw($preview_url));
            $imagick->clear();
            $imagick->destroy();
            return array('success' => true, 'message' => 'Preview generated: ' . $preview_url);
        } else {
            $imagick->clear();
            $imagick->destroy();
            return array('success' => false, 'message' => 'Failed to write preview image.');
        }
    } catch (Exception $e) {
        return array('success' => false, 'message' => 'Exception: ' . $e->getMessage());
    }
}

/**
 * Register header text color options for h1 through h6
 */
function tm_register_header_text_color_options() {
    $header_levels = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

    foreach ($header_levels as $header) {
        register_setting('tm_display_options', "tm_{$header}_text_color");
    }
}
add_action('admin_init', 'tm_register_header_text_color_options');
