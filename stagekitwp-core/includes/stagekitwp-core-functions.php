<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Enqueue admin scripts and styles
 */
/**
 * Single unified admin enqueue handler.
 * Merged from two separate hooks that both registered 'stagekitwp-admin-js' with
 * conflicting dependency arrays — the duplicate caused wp-color-picker to
 * never load on the Display Options page.
 */
function stagekitwp_enqueue_admin_assets($hook) {
    global $post_type;
    $is_stagekitwp_builder      = strpos($hook, 'stagekitwp-season-builder') !== false;
    $is_display_options = strpos($hook, 'stagekitwp-display-options') !== false;
    $is_dashboard       = ($hook === 'toplevel_page_stagekitwp-core');
    // Any StageKitWP admin screen (Dashboard, Season Content, People/Places/
    // Partners, RC Library, Import-Export, Members, Theme Dashboard, etc.).
    // Hub pages like Season Content and People/Places/Partners render a full
    // in-page CPT editor (with "Select Image" media buttons) but previously
    // only matched the exact Dashboard hook, so wp.media() and the click
    // handler never loaded there and the buttons silently did nothing.
    $is_stagekitwp_hub          = $is_dashboard || strpos($hook, 'stagekitwp') !== false;

    // CPT list for selective loading
    $cpts = array('board_member', 'advertiser', 'sponsors', 'testimonials', 'contributors', 'season', 'show', 'cast', 'award');
    $is_stagekitwp_cpt = in_array($post_type, $cpts, true);

    // --- Media uploader (CPTs + builder + post editors) ---
    if ($is_stagekitwp_cpt || $is_stagekitwp_builder || $is_stagekitwp_hub || strpos($hook, 'post.php') !== false || strpos($hook, 'post-new.php') !== false) {
        wp_enqueue_media();
    }

    // --- Admin media JS (post editors + builder) ---
    if (strpos($hook, 'post.php') !== false || strpos($hook, 'post-new.php') !== false || $is_stagekitwp_builder || $is_stagekitwp_hub) {
        wp_enqueue_script('stagekitwp-admin-media', STAGEKITWP_CORE_URL . 'assets/js/admin-media.js', array('jquery'), '1.0.0', true);
    }

    // --- Admin CSS (dashboard + CPTs + builder + Display Options) ---
    if ($is_dashboard || $is_stagekitwp_cpt || $is_stagekitwp_builder || $is_display_options) {
        wp_enqueue_style('stagekitwp-admin-css', STAGEKITWP_CORE_URL . 'assets/css/admin.css', array(), STAGEKITWP_CORE_VERSION);
    }

    // --- Admin JS: include wp-color-picker when on Display Options ---
    if ($is_stagekitwp_cpt || $is_stagekitwp_builder || $is_stagekitwp_hub || $is_display_options) {
        $deps = array('jquery');
        if ($is_display_options) {
            wp_enqueue_style('wp-color-picker');
            $deps[] = 'wp-color-picker';
            // Inline override: force .stagekitwp-color-pair-row to flex AFTER WP admin CSS loads.
            // wp_add_inline_style appends after the enqueued stylesheet, winning specificity.
            wp_add_inline_style( 'stagekitwp-admin-css',
                '.form-table td .stagekitwp-color-pair-row{display:flex!important;flex-direction:row!important;align-items:flex-start!important;gap:48px!important;flex-wrap:wrap!important;}'
                . '.form-table td .stagekitwp-color-pair-cell{display:flex!important;flex-direction:column!important;align-items:flex-start!important;gap:6px!important;flex:0 0 auto!important;}'
            );
        }
        wp_enqueue_script('stagekitwp-admin-js', STAGEKITWP_CORE_URL . 'assets/js/admin.js', $deps, STAGEKITWP_CORE_VERSION, true);
    }
}
add_action('admin_enqueue_scripts', 'stagekitwp_enqueue_admin_assets');
/**
 * Enqueue shortcodes CSS on the frontend
 */
function stagekitwp_enqueue_shortcodes_css() {
    wp_enqueue_style(
        'stagekitwp-shortcodes-css',
        STAGEKITWP_CORE_URL . 'assets/css/shortcodes.css',
        array(),
        STAGEKITWP_CORE_VERSION
    );
}
add_action('wp_enqueue_scripts', 'stagekitwp_enqueue_shortcodes_css');

/**
 * Queue per-shortcode inline CSS via wp_add_inline_style() instead of echoing
 * a raw <style> block inside the shortcode output.
 *
 * Shortcodes call:  stagekitwp_add_shortcode_inline_style( 'board-members', $css );
 * The CSS is attached to the already-enqueued stagekitwp-shortcodes-css handle so it
 * appears in <head> and is deduplicated — calling with the same $handle twice
 * only appends once.
 *
 * @param string $handle  Short slug, e.g. 'board-members', 'sponsors'.
 * @param string $css     Raw CSS string (already sanitized).
 */
function stagekitwp_add_shortcode_inline_style( $handle, $css ) {
    static $added = array();
    if ( isset( $added[ $handle ] ) ) {
        return; // already output for this request
    }
    $added[ $handle ] = true;

    // Skip on REST API requests (no HTML page to inject into).
    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        return;
    }

    // wp_add_inline_style() only works before wp_head fires.
    // Shortcodes run during the_content (after wp_head), so we echo a
    // deduped <style> block directly into the page body instead.
    // This is safe — browsers accept <style> in <body> — and is the only
    // reliable way to inject shortcode CSS when wp_head is long past.
    // NOTE: headers_sent() must NOT be used here — it returns true once any
    // HTML output has started (which it has by the_content time), so checking
    // it would silently suppress every style block on cached/buffered sites.
    echo '<style id="stagekitwp-sc-' . esc_attr( $handle ) . '">' . $css . '</style>' . "\n";
}

// Note: admin scripts are enqueued in stagekitwp_enqueue_admin_assets above when needed
/**
 * Swiper is only enqueued when [stagekitwp_sponsor_slider] appears on the page.
 * The shortcode callback calls stagekitwp_enqueue_swiper_assets() directly so
 * scripts are registered before wp_footer flushes the queue.
 */
function stagekitwp_enqueue_swiper_assets() {
    if ( wp_style_is( 'swiper-css', 'enqueued' ) ) { return; } // guard against double-call
    wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css');
    wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js', array(), null, true);
    wp_add_inline_script('swiper-js', '
        document.addEventListener("DOMContentLoaded", function () {
            new Swiper(".stagekitwp-testimonials-slider", {
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
// NOT hooked to wp_enqueue_scripts globally — called on demand by shortcode.

function stagekitwp_enqueue_font_awesome() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css');
}
add_action('wp_enqueue_scripts', 'stagekitwp_enqueue_font_awesome');

/**
 * Slick Slider is only enqueued when [stagekitwp_testimonials] appears on the page.
 * Called on demand by the shortcode callback.
 */
function stagekitwp_enqueue_slick_slider_assets() {
    if ( wp_style_is( 'slick-css', 'enqueued' ) ) { return; } // guard against double-call
    // Slick CSS
    wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css');
    wp_enqueue_style('slick-theme-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css');

    // Slick JS
    wp_enqueue_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', ['jquery'], null, true);

    // Custom init script — use STAGEKITWP_CORE_URL constant (not fragile relative path)
    wp_enqueue_script('stagekitwp-slick-init', STAGEKITWP_CORE_URL . 'assets/js/stagekitwp-slick-init.js', ['jquery', 'slick-js'], null, true);
}
// NOT hooked to wp_enqueue_scripts globally — called on demand by shortcode.


function stagekitwp_enqueue_cast_styles() {
    // Use STAGEKITWP_CORE_URL constant — plugin_dir_url(__FILE__) from inside includes/
    // produced a fragile relative path traversal (../assets/...).
    wp_enqueue_style('stagekitwp-season-cast-table', STAGEKITWP_CORE_URL . 'assets/css/stagekitwp_season_cast_table.css');
}
add_action('wp_enqueue_scripts', 'stagekitwp_enqueue_cast_styles');


/**
 * PDF.js and the preview renderer are only enqueued when [stagekitwp_programs] appears
 * on the page. Called on demand by the shortcode callback.
 */
function stagekitwp_enqueue_pdf_preview_assets() {
    if ( wp_script_is( 'stagekitwp-pdfjs', 'enqueued' ) ) { return; } // guard against double-call
    // PDF.js from CDN
    wp_enqueue_script('stagekitwp-pdfjs', 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js', array(), null, true);
    // Our preview renderer
    wp_enqueue_script('stagekitwp-pdf-preview', STAGEKITWP_CORE_URL . 'assets/js/pdf-preview.js', array('stagekitwp-pdfjs', 'jquery'), '1.0.0', true);
}
// NOT hooked to wp_enqueue_scripts globally — called on demand by shortcode.


/**
 * Generate a JPEG preview for uploaded PDF attachments when possible.
 * Stores the preview URL in attachment meta key '_stagekitwp_pdf_preview'.
 */
function stagekitwp_generate_pdf_preview_on_upload($attachment_id) {
    // Delegate to generator that returns diagnostics
    $result = stagekitwp_generate_pdf_preview($attachment_id);
    // If generator returned WP_Error or failure, just exit (we don't want to surface errors during normal upload)
    return;
}
add_action('add_attachment', 'stagekitwp_generate_pdf_preview_on_upload');


/**
 * Generate a JPEG preview for a PDF attachment and return diagnostics.
 * Returns array: ['success' => bool, 'message' => string]
 */
function stagekitwp_generate_pdf_preview($attachment_id) {
    $mime = get_post_mime_type($attachment_id);
    if ($mime !== 'application/pdf') {
        return array('success' => false, 'message' => 'Not a PDF.');
    }

    if (!class_exists('Imagick')) {
        return array('success' => false, 'message' => 'Imagick PHP extension not available.');
    }

    $file = get_attached_file($attachment_id);
    if (!$file || !file_exists($file)) {
        return array('success' => false, 'message' => 'File not found');
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
        
        // Security: Validate destination path to prevent directory traversal
        $dest_path = realpath($dest_path);
        if (false === $dest_path || strpos($dest_path, realpath($upload_dir['path'])) !== 0) {
            $imagick->clear();
            $imagick->destroy();
            return array('success' => false, 'message' => 'Invalid destination path');
        }

        if ($imagick->writeImage($dest_path)) {
            $preview_url = trailingslashit($upload_dir['url']) . $thumb_filename;
            // Security: Validate preview URL to prevent stored XSS
            if (!wp_http_validate_url($preview_url)) {
                $imagick->clear();
                $imagick->destroy();
                return array('success' => false, 'message' => 'Invalid preview URL');
            }
            update_post_meta($attachment_id, '_stagekitwp_pdf_preview', esc_url_raw($preview_url));
            $imagick->clear();
            $imagick->destroy();
            return array('success' => true, 'message' => 'Preview generated');
        } else {
            $imagick->clear();
            $imagick->destroy();
            return array('success' => false, 'message' => 'Failed to write preview image.');
        }
    } catch (Exception $e) {
        return array('success' => false, 'message' => 'Exception: ' . $e->getMessage());
    }
}

// Removed: stagekitwp_register_header_text_color_options() registered h1-h6 color
// settings under the global 'stagekitwp_display_options' group which has no
// corresponding settings page or UI field. The per-tab settings
// (stagekitwp_{tab}_h1_color … stagekitwp_{tab}_h6_color) in stagekitwp_register_display_settings()
// are the correct, functional equivalents. Orphaned settings removed in v3.7.20.
