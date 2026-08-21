<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Parse boolean-like shortcode values.
 */
function stagekitwp_testimonials_parse_bool($value, $default = true) {
    if ($value === null || $value === '') {
        return (bool) $default;
    }

    if (is_bool($value)) {
        return $value;
    }

    $value = strtolower(trim((string) $value));
    if (in_array($value, array('1', 'true', 'yes', 'on'), true)) {
        return true;
    }
    if (in_array($value, array('0', 'false', 'no', 'off'), true)) {
        return false;
    }

    return (bool) $default;
}

/**
 * Resolve tag icon URL for testimonial show-name badges.
 */
function stagekitwp_testimonials_resolve_tag_icon_url($source, $custom_url = '') {
    $source = strtolower(sanitize_key($source));

    if ($source === 'site_icon') {
        return get_site_icon_url(128);
    }

    if ($source === 'custom') {
        return esc_url_raw($custom_url);
    }

    if ($source === 'miltonman') {
        $attachment_ids = get_posts(array(
            'post_type' => 'attachment',
            'name' => 'miltonman',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'fields' => 'ids',
        ));

        if (empty($attachment_ids)) {
            $attachment_ids = get_posts(array(
                'post_type' => 'attachment',
                's' => 'miltonman',
                'post_status' => 'inherit',
                'posts_per_page' => 1,
                'fields' => 'ids',
            ));
        }

        if (!empty($attachment_ids)) {
            $thumb = wp_get_attachment_image_url($attachment_ids[0], 'thumbnail');
            if ($thumb) {
                return $thumb;
            }
            return wp_get_attachment_url($attachment_ids[0]);
        }
    }

    return '';
}

/**
 * Shortcode to display testimonials with multiple layouts and render modes.
 */
function stagekitwp_testimonials_shortcode($atts) {
    ob_start();

    // Get display options
    $bg_color = get_option('stagekitwp_testimonials_bg_color', '#ffffff');
    $text_color = get_option('stagekitwp_testimonials_text_color', '#000000');
    $border_color = get_option('stagekitwp_testimonials_border_color', '#000000');
    $border_width = get_option('stagekitwp_testimonials_border_width', '1');
    $rounded = get_option('stagekitwp_testimonials_rounded') ? '10px' : '0';
    $radius = get_option('stagekitwp_testimonials_radius', '10');
    $shadow = get_option('stagekitwp_testimonials_shadow') ? '0 4px 8px rgba(0,0,0,0.1)' : 'none';
    $rating_symbol = get_option('stagekitwp_testimonials_rating_symbol', 'Stars');
    $base_font = get_option('stagekitwp_testimonials_base_font', 'Arial, sans-serif');

    // Display defaults configurable in Display Options.
    $default_layout = get_option('stagekitwp_testimonials_layout', 'classic');
    $default_mode = get_option('stagekitwp_testimonials_mode', 'slider');
    $default_show_name = get_option('stagekitwp_testimonials_show_name', '1');
    $default_show_comment = get_option('stagekitwp_testimonials_show_comment', '1');
    $default_show_rating = get_option('stagekitwp_testimonials_show_rating', '1');
    $default_show_show = get_option('stagekitwp_testimonials_show_show', '1');
    $default_show_date = get_option('stagekitwp_testimonials_show_date', '1');
    $default_show_media = get_option('stagekitwp_testimonials_show_media', '1');
    $default_show_name_placement = get_option('stagekitwp_testimonials_show_name_placement', 'meta');
    $default_image_width = get_option('stagekitwp_testimonials_image_width', '520');
    $default_image_height = get_option('stagekitwp_testimonials_image_height', '280');
    $default_image_fit = get_option('stagekitwp_testimonials_image_fit', 'cover');
    $default_image_position = get_option('stagekitwp_testimonials_image_position', 'center');
    $default_image_focus = get_option('stagekitwp_testimonials_image_focus', 'center_center');
    $default_text_overlay = get_option('stagekitwp_testimonials_text_overlay', '0');
    $default_image_opacity = get_option('stagekitwp_testimonials_image_opacity', '0.45');
    $default_tag_icon_source = get_option('stagekitwp_testimonials_tag_icon_source', 'none');
    $default_tag_icon_size = get_option('stagekitwp_testimonials_tag_icon_size', '18');
    $default_tag_icon_url = get_option('stagekitwp_testimonials_tag_icon_url', '');
    $default_reviews_per_show = get_option('stagekitwp_testimonials_reviews_per_show', '4');
    $default_review_align = get_option('stagekitwp_testimonials_review_align', 'left');

    $atts = shortcode_atts(array(
        'limit' => -1,
        'count' => '',
        'layout' => $default_layout,
        'mode' => $default_mode,
        'columns' => 3,
        'show_id' => 0,
        'show_name' => $default_show_name,
        'show_comment' => $default_show_comment,
        'show_rating' => $default_show_rating,
        'show_show' => $default_show_show,
        'show_date' => $default_show_date,
        'show_media' => $default_show_media,
        'image_width' => $default_image_width,
        'image_height' => $default_image_height,
        'image_fit' => $default_image_fit,
        'image_position' => $default_image_position,
        'image_focus' => $default_image_focus,
        'text_overlay' => $default_text_overlay,
        'image_opacity' => $default_image_opacity,
        'show_name_placement' => $default_show_name_placement,
        'tag_icon_source' => $default_tag_icon_source,
        'tag_icon_size' => $default_tag_icon_size,
        'tag_icon_url' => $default_tag_icon_url,
        'reviews_per_show' => $default_reviews_per_show,
        'review_align' => $default_review_align,
        // Backward compatible aliases.
        'show_author' => '',
        'show_photo' => '',
    ), $atts, 'stagekitwp_testimonials');

    if ($atts['show_author'] !== '') {
        $atts['show_name'] = $atts['show_author'];
    }
    if ($atts['show_photo'] !== '') {
        $atts['show_media'] = $atts['show_photo'];
    }
    if ($atts['count'] !== '' && $atts['limit'] === -1) {
        $atts['limit'] = $atts['count'];
    }

    $mode = strtolower(sanitize_key($atts['mode']));
    $layout_input = strtolower(sanitize_key($atts['layout']));
    if (in_array($layout_input, array('slider', 'grid', 'full'), true)) {
        $mode = $layout_input;
        $atts['layout'] = 'classic';
    }
    if (!in_array($mode, array('slider', 'grid', 'full', 'per_show', 'per_show_slider'), true)) {
        $mode = 'slider';
    }

    $reviews_per_show = intval($atts['reviews_per_show']);
    if ($reviews_per_show < 1) {
        $reviews_per_show = 4;
    }
    $reviews_per_show = min(20, $reviews_per_show);

    $review_align = strtolower(sanitize_key($atts['review_align']));
    if (!in_array($review_align, array('left', 'center', 'right', 'alternating', 'alternating_lr'), true)) {
        $review_align = 'left';
    }

    $layout = strtolower(sanitize_key($atts['layout']));
    if (!in_array($layout, array('classic', 'quote', 'minimal', 'spotlight', 'overlay'), true)) {
        $layout = 'classic';
    }

    $columns = max(1, min(4, intval($atts['columns'])));
    $limit = intval($atts['limit']);
    $show_id_filter = absint($atts['show_id']);

    $show_name = stagekitwp_testimonials_parse_bool($atts['show_name'], true);
    $show_comment = stagekitwp_testimonials_parse_bool($atts['show_comment'], true);
    $show_rating = stagekitwp_testimonials_parse_bool($atts['show_rating'], true);
    $show_show = stagekitwp_testimonials_parse_bool($atts['show_show'], true);
    $show_date = stagekitwp_testimonials_parse_bool($atts['show_date'], true);
    $show_media = stagekitwp_testimonials_parse_bool($atts['show_media'], true);
    $show_name_placement = strtolower(sanitize_key($atts['show_name_placement']));
    if (!in_array($show_name_placement, array('meta', 'header', 'slug', 'image_indent'), true)) {
        $show_name_placement = 'meta';
    }
    $text_overlay = stagekitwp_testimonials_parse_bool($atts['text_overlay'], false);

    if ($layout === 'overlay') {
        $text_overlay = true;
    }

    $image_width = max(200, min(1400, intval($atts['image_width'])));
    $image_height = max(120, min(1200, intval($atts['image_height'])));
    $image_fit = strtolower(sanitize_key($atts['image_fit']));
    if (!in_array($image_fit, array('cover', 'contain'), true)) {
        $image_fit = 'cover';
    }
    $image_position = strtolower(sanitize_key($atts['image_position']));
    if (!in_array($image_position, array('center', 'left', 'right', 'top'), true)) {
        $image_position = 'center';
    }
    $image_focus = strtolower(str_replace(' ', '_', sanitize_text_field($atts['image_focus'])));
    $focus_map = array(
        'center_center' => 'center center',
        'center_top' => 'center top',
        'center_bottom' => 'center bottom',
        'left_center' => 'left center',
        'right_center' => 'right center',
    );
    if (!isset($focus_map[$image_focus])) {
        $image_focus = 'center_center';
    }
    $image_focus_css = $focus_map[$image_focus];

    $image_opacity = floatval($atts['image_opacity']);
    if ($image_opacity < 0.1) {
        $image_opacity = 0.1;
    }
    if ($image_opacity > 1) {
        $image_opacity = 1;
    }

    $tag_icon_source = strtolower(sanitize_key($atts['tag_icon_source']));
    if (!in_array($tag_icon_source, array('none', 'site_icon', 'miltonman', 'custom'), true)) {
        $tag_icon_source = 'none';
    }
    $tag_icon_size = max(12, min(48, intval($atts['tag_icon_size'])));
    $tag_icon_url = stagekitwp_testimonials_resolve_tag_icon_url($tag_icon_source, $atts['tag_icon_url']);
    $has_tag_icon_image = !empty($tag_icon_url);

    // Symbol sets. For some sets we will use the same glyph for filled and empty
    // and rely on CSS filters/opacity to show the difference.
    $symbols = array(
        'Stars' => array('filled' => '★', 'empty' => '☆'),
        'Thumbs Up' => array('filled' => '👍', 'empty' => '👍'),
        'Rockets' => array('filled' => '🚀', 'empty' => '🚀'),
        'Hearts' => array('filled' => '❤️', 'empty' => '🤍'),
        'Theatre Masks' => array('filled' => '🎭', 'empty' => '🎭')
    );

    $symbol_set = isset($symbols[$rating_symbol]) ? $symbols[$rating_symbol] : $symbols['Stars'];
    $use_filter_for_empty = in_array($rating_symbol, array('Theatre Masks', 'Thumbs Up', 'Rockets'));

    $instance_id = 'stagekitwp-testimonials-' . wp_rand(1000, 99999);

    // Base CSS for rating symbols
    $css = '';
    $css .= '.stagekitwp-symbol-filled { color: ' . esc_attr($text_color) . '; opacity: 1; }';
    $css .= '.stagekitwp-symbol-empty { color: ' . esc_attr($text_color) . '; text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000; opacity: 1; }';
    $css .= '#' . $instance_id . ' { width:100%; margin:0 auto; --stagekitwp-tag-icon-size:' . intval($tag_icon_size) . 'px; }';
    $css .= '#' . $instance_id . ' .stagekitwp-testimonials-track { width:100%; }';
    $css .= '#' . $instance_id . ' .stagekitwp-testimonial { box-sizing:border-box; width:100%; margin:0 0 20px; padding:20px; overflow:hidden; transition:transform .25s ease, box-shadow .25s ease; }';
    $css .= '#' . $instance_id . ' .stagekitwp-testimonial:hover { transform: translateY(-3px); }';
    $css .= '#' . $instance_id . ' .stagekitwp-testimonial-media { width:min(100%,' . intval($image_width) . 'px); height:' . intval($image_height) . 'px; overflow:hidden; border-radius:8px; margin:0 auto 14px; background:#111; position:relative; }';
    $css .= '#' . $instance_id . ' .stagekitwp-testimonial-media img { width:100%; height:100%; object-fit:' . esc_attr($image_fit) . '; object-position:' . esc_attr($image_focus_css) . '; display:block; }';
    $css .= '#' . $instance_id . ' .stagekitwp-rating { margin-bottom:10px; }';
    $css .= '#' . $instance_id . ' .stagekitwp-comment { margin-bottom:12px; line-height:1.6; }';
    $css .= '#' . $instance_id . ' .stagekitwp-meta { font-style:italic; opacity:0.9; font-size:.92rem; }';
    $css .= '#' . $instance_id . ' .stagekitwp-content { display:flex; flex-direction:column; gap:8px; min-width:0; }';
    $css .= '#' . $instance_id . ' .stagekitwp-content .stagekitwp-show-name-header, #' . $instance_id . ' .stagekitwp-content .stagekitwp-show-name-slug, #' . $instance_id . ' .stagekitwp-content .stagekitwp-meta { margin:0; padding-left:8px; padding-right:8px; }';
    $css .= '#' . $instance_id . ' .stagekitwp-testimonial.stagekitwp-has-media .stagekitwp-content { width:min(100%,' . intval($image_width) . 'px); margin:0 auto; }';
        $css .= '#' . $instance_id . ' .stagekitwp-show-name-header { margin:0 0 12px; font-size:1.05rem; line-height:1.3; letter-spacing:.01em; font-weight:700; }';
        $css .= '#' . $instance_id . ' .stagekitwp-show-name-slug { display:block; margin:0 0 10px; min-width:0; max-width:100%; }';
        $css .= '#' . $instance_id . ' .stagekitwp-show-name-media-indent { position:absolute; left:10px; bottom:12px; max-width:calc(100% - 20px); color:#fff; z-index:2; }';
        $css .= '#' . $instance_id . ' .stagekitwp-show-tag-badge { display:inline-flex; align-items:center; gap:8px; width:fit-content; max-width:100%; background:rgba(17,17,17,.08); border:1px solid rgba(17,17,17,.16); border-radius:999px; padding:5px 11px 5px 7px; font-size:.78rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; box-sizing:border-box; color:inherit; }';
        $css .= '#' . $instance_id . ' .stagekitwp-show-tag-icon { width:var(--stagekitwp-tag-icon-size); height:var(--stagekitwp-tag-icon-size); min-width:var(--stagekitwp-tag-icon-size); border-radius:50%; background-size:cover; background-position:center; background-repeat:no-repeat; display:inline-block; }';
        $css .= '#' . $instance_id . ' .stagekitwp-show-tag-icon-fallback { display:inline-flex; align-items:center; justify-content:center; font-size:calc(var(--stagekitwp-tag-icon-size) - 6px); background:rgba(17,17,17,.10); }';
        $css .= '#' . $instance_id . ' .stagekitwp-show-tag-text { min-width:0; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }';
        $css .= '#' . $instance_id . ' .stagekitwp-show-name-header .stagekitwp-show-tag-badge { font-size:.82rem; }';
        $css .= '#' . $instance_id . ' .stagekitwp-show-name-header .stagekitwp-show-tag-text { white-space:normal; }';
        $css .= '#' . $instance_id . '.stagekitwp-image-pos-left .stagekitwp-testimonial-media { margin:0 auto 14px 0; }';
        $css .= '#' . $instance_id . '.stagekitwp-image-pos-right .stagekitwp-testimonial-media { margin:0 0 14px auto; }';
        $css .= '#' . $instance_id . '.stagekitwp-image-pos-top .stagekitwp-testimonial-media { margin:0 auto 18px; }';
    $css .= '#' . $instance_id . '.stagekitwp-mode-grid .stagekitwp-testimonials-track { display:grid; grid-template-columns:repeat(' . intval($columns) . ', minmax(0, 1fr)); gap:20px; }';
    $css .= '#' . $instance_id . '.stagekitwp-mode-full .stagekitwp-testimonials-track { display:flex; flex-direction:column; gap:20px; }';
    $css .= '#' . $instance_id . '.stagekitwp-layout-classic .stagekitwp-testimonial { border-top:3px solid ' . esc_attr($border_color) . '; }';
    $css .= '#' . $instance_id . '.stagekitwp-layout-quote .stagekitwp-testimonial { position:relative; padding-top:32px; }';
    $css .= '#' . $instance_id . '.stagekitwp-layout-quote .stagekitwp-testimonial:before { content:"\\201C"; position:absolute; top:4px; left:16px; font-size:2.6rem; opacity:.18; line-height:1; }';
    $css .= '#' . $instance_id . '.stagekitwp-layout-quote .stagekitwp-comment { font-size:1.08em; letter-spacing:.01em; }';
    $css .= '#' . $instance_id . '.stagekitwp-layout-minimal .stagekitwp-testimonial { border-left:4px solid ' . esc_attr($border_color) . '; border-top:none; border-right:none; border-bottom:none; box-shadow:none; border-radius:0; margin-left:0; margin-right:0; padding-left:24px; }';
    $css .= '#' . $instance_id . '.stagekitwp-layout-minimal .stagekitwp-testimonial-media { border-radius:4px; }';
    // Spotlight: the image box is fixed at the configured image_width x image_height on every
    // position variant (left/right/center/top) instead of stretching to match the sibling content's
    // height — a short image_height must render short even when the review text runs long.
    $css .= '#' . $instance_id . '.stagekitwp-layout-spotlight .stagekitwp-testimonial { display:grid; grid-template-columns:minmax(220px,' . intval($image_width) . 'px) 1fr; gap:20px; align-items:center; padding:22px; background:linear-gradient(180deg,rgba(0,0,0,0.03),rgba(0,0,0,0)); }';
    $css .= '#' . $instance_id . '.stagekitwp-layout-spotlight.stagekitwp-image-pos-right .stagekitwp-testimonial { grid-template-columns:1fr minmax(220px,' . intval($image_width) . 'px); }';
    $css .= '#' . $instance_id . '.stagekitwp-layout-spotlight.stagekitwp-image-pos-right .stagekitwp-testimonial .stagekitwp-testimonial-media { order:2; }';
    $css .= '#' . $instance_id . '.stagekitwp-layout-spotlight.stagekitwp-image-pos-right .stagekitwp-testimonial .stagekitwp-content { order:1; }';
    $css .= '#' . $instance_id . '.stagekitwp-layout-spotlight.stagekitwp-image-pos-top .stagekitwp-testimonial { grid-template-columns:1fr; }';
    $css .= '#' . $instance_id . '.stagekitwp-layout-spotlight .stagekitwp-testimonial-media { width:100%; height:' . intval($image_height) . 'px; align-self:start; margin:0; }';
    $css .= '#' . $instance_id . '.stagekitwp-layout-spotlight .stagekitwp-content { align-self:center; }';
    $css .= '#' . $instance_id . '.stagekitwp-text-overlay .stagekitwp-testimonial { position:relative; padding:0; min-height:' . intval($image_height) . 'px; }';
    $css .= '#' . $instance_id . '.stagekitwp-text-overlay .stagekitwp-testimonial-media { width:100%; height:' . intval($image_height) . 'px; margin:0; border-radius:0; }';
    $css .= '#' . $instance_id . '.stagekitwp-text-overlay .stagekitwp-testimonial-media img { opacity:' . esc_attr($image_opacity) . '; }';
    $css .= '#' . $instance_id . '.stagekitwp-text-overlay .stagekitwp-overlay-content { position:absolute; inset:0; z-index:3; display:flex; flex-direction:column; justify-content:flex-end; gap:8px; padding:22px; overflow:hidden; background:linear-gradient(180deg,rgba(0,0,0,.08) 20%,rgba(0,0,0,.78) 100%); color:#fff; }';
    $css .= '#' . $instance_id . '.stagekitwp-text-overlay .stagekitwp-overlay-content .stagekitwp-meta { color:rgba(255,255,255,.92); opacity:1; }';
    $css .= '#' . $instance_id . '.stagekitwp-text-overlay .stagekitwp-show-name-header, #' . $instance_id . '.stagekitwp-text-overlay .stagekitwp-show-name-slug { color:#fff; opacity:1; }';
    $css .= '#' . $instance_id . '.stagekitwp-text-overlay .stagekitwp-show-tag-badge { background:rgba(0,0,0,.62); border-color:rgba(255,255,255,.35); }';
    $css .= '#' . $instance_id . '.stagekitwp-text-overlay .stagekitwp-testimonial.stagekitwp-no-image { min-height:0; }';
    $css .= '#' . $instance_id . '.stagekitwp-text-overlay .stagekitwp-testimonial.stagekitwp-no-image .stagekitwp-overlay-content { position:relative; inset:auto; background:none; color:' . esc_attr($text_color) . '; padding:20px; overflow:visible; }';
    $css .= '#' . $instance_id . '.stagekitwp-text-overlay .stagekitwp-testimonial.stagekitwp-no-image .stagekitwp-overlay-content .stagekitwp-meta { color:' . esc_attr($text_color) . '; opacity:.9; }';
    $css .= '#' . $instance_id . '.stagekitwp-text-overlay .stagekitwp-testimonial.stagekitwp-no-image .stagekitwp-show-name-header, #' . $instance_id . '.stagekitwp-text-overlay .stagekitwp-testimonial.stagekitwp-no-image .stagekitwp-show-name-slug { color:' . esc_attr($text_color) . '; }';
    $css .= '#' . $instance_id . '.stagekitwp-text-overlay .stagekitwp-testimonial.stagekitwp-no-image .stagekitwp-show-tag-badge { background:rgba(17,17,17,.08); border-color:rgba(17,17,17,.16); }';
    $css .= '#' . $instance_id . '.stagekitwp-text-overlay .stagekitwp-symbol-filled, #' . $instance_id . '.stagekitwp-text-overlay .stagekitwp-symbol-empty { color:#fff; text-shadow:none; }';
    $css .= '#' . $instance_id . '.stagekitwp-mode-slider .slick-list { overflow:hidden; max-width:100%; }';
    $css .= '#' . $instance_id . '.stagekitwp-mode-slider .slick-list { margin:0 !important; padding:0 !important; }';
    $css .= '#' . $instance_id . '.stagekitwp-mode-slider .slick-slide { height:auto; min-width:0; box-sizing:border-box; }';
    $css .= '#' . $instance_id . '.stagekitwp-mode-slider .slick-slide { padding:0 !important; margin:0 !important; }';
    $css .= '#' . $instance_id . '.stagekitwp-mode-slider .slick-slide > div { width:100%; max-width:100%; box-sizing:border-box; }';
    $css .= '#' . $instance_id . '.stagekitwp-mode-slider .slick-track { margin-left:0 !important; margin-right:0 !important; }';
    $css .= '#' . $instance_id . '.stagekitwp-mode-slider .slick-slide .stagekitwp-testimonial { margin-bottom:0; }';
    $css .= '@media (max-width: 960px){#' . $instance_id . '.stagekitwp-mode-grid .stagekitwp-testimonials-track{grid-template-columns:repeat(2,minmax(0,1fr));}}';
    $css .= '@media (max-width: 840px){#' . $instance_id . '.stagekitwp-layout-spotlight .stagekitwp-testimonial{grid-template-columns:1fr;}#' . $instance_id . '.stagekitwp-layout-spotlight .stagekitwp-testimonial-media{height:' . intval($image_height) . 'px;min-height:0;}}';
    $css .= '@media (max-width: 640px){#' . $instance_id . '.stagekitwp-mode-grid .stagekitwp-testimonials-track{grid-template-columns:1fr;}#' . $instance_id . ' .stagekitwp-testimonial{margin-left:0;margin-right:0;}}';
    $css .= '#' . $instance_id . '.stagekitwp-mode-per_show:not(.stagekitwp-per-show-slider) .stagekitwp-testimonials-track { display:flex; flex-direction:column; gap:30px; }';
    $css .= '#' . $instance_id . '.stagekitwp-per-show-slider .stagekitwp-testimonial { margin-bottom:0; }';
    $css .= '#' . $instance_id . ' .stagekitwp-per-show-title { margin:0 0 14px; font-size:1.2rem; font-weight:700; }';
    $css .= '#' . $instance_id . ' .stagekitwp-per-show-reviews { display:flex; flex-direction:column; gap:14px; }';
    $css .= '#' . $instance_id . ' .stagekitwp-per-show-review { width:100%; max-width:88%; box-sizing:border-box; padding-top:14px; border-top:1px solid rgba(127,127,127,.25); }';
    $css .= '#' . $instance_id . ' .stagekitwp-per-show-review:first-child { padding-top:0; border-top:none; }';
    $css .= '#' . $instance_id . ' .stagekitwp-per-show-review.stagekitwp-review-align-left { margin-right:auto; text-align:left; }';
    $css .= '#' . $instance_id . ' .stagekitwp-per-show-review.stagekitwp-review-align-center { margin-left:auto; margin-right:auto; text-align:center; }';
    $css .= '#' . $instance_id . ' .stagekitwp-per-show-review.stagekitwp-review-align-right { margin-left:auto; text-align:right; }';
    // The global stylesheet hardcodes .stagekitwp-rating and .stagekitwp-comment to text-align:center, which
    // overrides the inherited alignment above. Re-assert the review's own alignment on every
    // child (rating stars, comment text, meta line) so left/right/center/alternating fully match.
    $css .= '#' . $instance_id . ' .stagekitwp-per-show-review.stagekitwp-review-align-left .stagekitwp-rating, #' . $instance_id . ' .stagekitwp-per-show-review.stagekitwp-review-align-left .stagekitwp-comment, #' . $instance_id . ' .stagekitwp-per-show-review.stagekitwp-review-align-left .stagekitwp-meta { text-align:left; }';
    $css .= '#' . $instance_id . ' .stagekitwp-per-show-review.stagekitwp-review-align-center .stagekitwp-rating, #' . $instance_id . ' .stagekitwp-per-show-review.stagekitwp-review-align-center .stagekitwp-comment, #' . $instance_id . ' .stagekitwp-per-show-review.stagekitwp-review-align-center .stagekitwp-meta { text-align:center; }';
    $css .= '#' . $instance_id . ' .stagekitwp-per-show-review.stagekitwp-review-align-right .stagekitwp-rating, #' . $instance_id . ' .stagekitwp-per-show-review.stagekitwp-review-align-right .stagekitwp-comment, #' . $instance_id . ' .stagekitwp-per-show-review.stagekitwp-review-align-right .stagekitwp-meta { text-align:right; }';
    $css .= '@media (max-width: 640px){#' . $instance_id . ' .stagekitwp-per-show-review{max-width:100%;}}';
    // Per-show cards stack multiple reviews, so the overlay content must flow below the image
    // instead of being absolutely pinned over a fixed-height box (which would overflow/clip).
    $css .= '#' . $instance_id . '.stagekitwp-mode-per_show.stagekitwp-text-overlay .stagekitwp-testimonial { min-height:0; }';
    $css .= '#' . $instance_id . '.stagekitwp-mode-per_show.stagekitwp-text-overlay .stagekitwp-overlay-content { position:relative; inset:auto; background:' . esc_attr($bg_color) . '; color:' . esc_attr($text_color) . '; padding:22px; }';
    $css .= '#' . $instance_id . '.stagekitwp-mode-per_show.stagekitwp-text-overlay .stagekitwp-overlay-content .stagekitwp-meta { color:' . esc_attr($text_color) . '; opacity:.9; }';
    $css .= '#' . $instance_id . '.stagekitwp-mode-per_show.stagekitwp-text-overlay .stagekitwp-per-show-title { color:' . esc_attr($text_color) . '; }';
    $css .= '#' . $instance_id . '.stagekitwp-mode-per_show.stagekitwp-text-overlay .stagekitwp-symbol-filled, #' . $instance_id . '.stagekitwp-mode-per_show.stagekitwp-text-overlay .stagekitwp-symbol-empty { color:' . esc_attr($text_color) . '; text-shadow:none; }';

    if ($use_filter_for_empty) {
        $css .= '.stagekitwp-symbol-filled { transform: scale(1.05); }';
        $css .= '.stagekitwp-symbol-empty { opacity: 0.35; filter: grayscale(100%) contrast(80%) brightness(90%); }';
        $css .= '.stagekitwp-symbol-filled, .stagekitwp-symbol-empty { font-size: 1.2em; display: inline-block; margin: 0 2px; }';
    }

    echo '<style>' . $css . '</style>';

    if ($mode === 'slider' || $mode === 'per_show_slider') {
        stagekitwp_enqueue_slick_slider_assets();
        wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css');
        wp_enqueue_style('slick-theme-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css');
        wp_enqueue_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', array('jquery'), null, true);
        wp_add_inline_script('slick-js', '
            jQuery(document).ready(function($) {
                var $slider = $("#' . esc_js($instance_id) . ' .stagekitwp-testimonials-track");
                if ($slider.length && !$slider.hasClass("slick-initialized")) {
                    $slider.slick({
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        centerMode: false,
                        centerPadding: "0px",
                        variableWidth: false,
                        arrows: true,
                        dots: true,
                        adaptiveHeight: true,
                        autoplay: true,
                        autoplaySpeed: 5000
                    });
                }
            });
        ');
    }

    if ($mode === 'per_show' || $mode === 'per_show_slider') {
        $testimonial_ids_with_show = get_posts(array(
            'post_type' => 'testimonial',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_stagekitwp_testimonial_show_id',
                    'value' => 0,
                    'compare' => '>',
                    'type' => 'NUMERIC',
                ),
            ),
        ));

        $show_ids = array();
        foreach ($testimonial_ids_with_show as $t_id) {
            $sid = absint(get_post_meta($t_id, '_stagekitwp_testimonial_show_id', true));
            if ($sid && !in_array($sid, $show_ids, true)) {
                $show_ids[] = $sid;
            }
        }

        if ($show_id_filter > 0) {
            $show_ids = in_array($show_id_filter, $show_ids, true) ? array($show_id_filter) : array();
        }

        if (empty($show_ids)) {
            echo '<p>No testimonials found.</p>';
            return ob_get_clean();
        }

        $shows_query = new WP_Query(array(
            'post_type' => 'show',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'post__in' => $show_ids,
            'orderby' => 'title',
            'order' => 'ASC',
        ));

        $wrapper_classes = 'stagekitwp-testimonials stagekitwp-mode-per_show stagekitwp-layout-' . esc_attr($layout) . ' stagekitwp-image-pos-' . esc_attr($image_position);
        if ($mode === 'per_show_slider') {
            $wrapper_classes .= ' stagekitwp-mode-slider stagekitwp-per-show-slider';
        }
        if ($text_overlay) {
            $wrapper_classes .= ' stagekitwp-text-overlay';
        }
        echo '<div id="' . esc_attr($instance_id) . '" class="' . esc_attr($wrapper_classes) . '">';
        echo '<div class="stagekitwp-testimonials-track">';

        if ($shows_query->have_posts()) {
            while ($shows_query->have_posts()) {
                $shows_query->the_post();
                $show_post_id = get_the_ID();
                $show_title = get_the_title();

                $media_url = stagekitwp_get_show_image_url(get_post_meta($show_post_id, '_stagekitwp_show_testimonial_media', true));
                if (!$media_url) {
                    $media_url = stagekitwp_get_show_image_url(get_post_meta($show_post_id, '_stagekitwp_show_sm_image', true));
                }

                $use_overlay = ($text_overlay && $show_media && !empty($media_url));
                $has_media = ($show_media && !empty($media_url));

                echo '<div class="stagekitwp-testimonial' . ( $has_media ? ' stagekitwp-has-media' : ' stagekitwp-no-image' ) . '" style="';
                echo 'background-color:' . esc_attr($bg_color) . ';';
                echo 'color:' . esc_attr($text_color) . ';';
                echo 'font-family:' . esc_attr($base_font) . ';';
                echo 'border:' . intval($border_width) . 'px solid ' . esc_attr($border_color) . ';';
                echo 'border-radius:' . intval($radius) . 'px;';
                echo 'box-shadow:' . esc_attr($shadow) . ';';
                echo 'padding:' . ( $use_overlay ? '0' : '20px' ) . '; margin-bottom: 20px;';
                echo '">';

                if ($has_media) {
                    echo '<div class="stagekitwp-testimonial-media"><img src="' . esc_url($media_url) . '" alt="' . esc_attr($show_title) . '" loading="lazy" /></div>';
                }

                echo '<div class="' . ( $use_overlay ? 'stagekitwp-overlay-content' : 'stagekitwp-content' ) . '">';

                echo '<h3 class="stagekitwp-per-show-title">' . esc_html($show_title) . '</h3>';

                $reviews_query = new WP_Query(array(
                    'post_type' => 'testimonial',
                    'post_status' => 'publish',
                    'posts_per_page' => $reviews_per_show,
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'meta_query' => array(
                        array(
                            'key' => '_stagekitwp_testimonial_show_id',
                            'value' => $show_post_id,
                            'compare' => '=',
                            'type' => 'NUMERIC',
                        ),
                    ),
                ));

                echo '<div class="stagekitwp-per-show-reviews">';

                $review_index = 0;
                while ($reviews_query->have_posts()) {
                    $reviews_query->the_post();

                    if ($review_align === 'alternating') {
                        $sequence = array('right', 'left', 'center');
                        $align_class = $sequence[$review_index % 3];
                    } elseif ($review_align === 'alternating_lr') {
                        $sequence = array('left', 'right');
                        $align_class = $sequence[$review_index % 2];
                    } else {
                        $align_class = $review_align;
                    }

                    $rating = max(0, min(5, intval(get_post_meta(get_the_ID(), '_stagekitwp_rating', true))));
                    $comment = get_post_meta(get_the_ID(), '_stagekitwp_comment', true);
                    $author = get_post_meta(get_the_ID(), '_stagekitwp_name', true);
                    $testimonial_date = get_post_meta(get_the_ID(), '_stagekitwp_testimonial_date', true);
                    $display_date = $testimonial_date ? $testimonial_date : get_the_date();

                    echo '<div class="stagekitwp-per-show-review stagekitwp-review-align-' . esc_attr($align_class) . '">';

                    if ($show_rating) {
                        echo '<div class="stagekitwp-rating">';
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<span class="stagekitwp-symbol-filled">' . $symbol_set['filled'] . '</span>';
                            } else {
                                echo '<span class="stagekitwp-symbol-empty">' . $symbol_set['empty'] . '</span>';
                            }
                        }
                        echo '</div>';
                    }

                    if ($show_comment && $comment) {
                        echo '<div class="stagekitwp-comment">' . esc_html($comment) . '</div>';
                    }

                    $meta_parts = array();
                    if ($show_name && $author) {
                        $meta_parts[] = $author;
                    }
                    if ($show_date && $display_date) {
                        $meta_parts[] = $display_date;
                    }
                    if (!empty($meta_parts)) {
                        echo '<div class="stagekitwp-meta">' . esc_html(implode(' - ', $meta_parts)) . '</div>';
                    }

                    echo '</div>';

                    $review_index++;
                }
                wp_reset_postdata();

                echo '</div>'; // .stagekitwp-per-show-reviews
                echo '</div>'; // .stagekitwp-content / .stagekitwp-overlay-content
                echo '</div>'; // .stagekitwp-testimonial
            }
        } else {
            echo '<p>No testimonials found.</p>';
        }
        wp_reset_postdata();

        echo '</div>'; // .stagekitwp-testimonials-track
        echo '</div>'; // wrapper

        return ob_get_clean();
    }

    $args = array(
        'post_type' => 'testimonial',
        'posts_per_page' => $limit > 0 ? $limit : -1,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
    );

    if ($show_id_filter > 0) {
        $args['meta_query'] = array(
            array(
                'key' => '_stagekitwp_testimonial_show_id',
                'value' => $show_id_filter,
                'compare' => '=',
                'type' => 'NUMERIC',
            ),
        );
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $wrapper_classes = 'stagekitwp-testimonials stagekitwp-mode-' . esc_attr($mode) . ' stagekitwp-layout-' . esc_attr($layout) . ' stagekitwp-image-pos-' . esc_attr($image_position);
        if ( $text_overlay ) {
            $wrapper_classes .= ' stagekitwp-text-overlay';
        }
        echo '<div id="' . esc_attr($instance_id) . '" class="' . esc_attr($wrapper_classes) . '">';
        echo '<div class="stagekitwp-testimonials-track">';
        while ($query->have_posts()) {
            $query->the_post();

            $rating = max(0, min(5, intval(get_post_meta(get_the_ID(), '_stagekitwp_rating', true))));
            $comment = get_post_meta(get_the_ID(), '_stagekitwp_comment', true);
            $author = get_post_meta(get_the_ID(), '_stagekitwp_name', true);
            $testimonial_show_id = absint(get_post_meta(get_the_ID(), '_stagekitwp_testimonial_show_id', true));
            $show_title = $testimonial_show_id ? get_the_title($testimonial_show_id) : '';
            $testimonial_date = get_post_meta(get_the_ID(), '_stagekitwp_testimonial_date', true);
            $display_date = $testimonial_date ? $testimonial_date : get_the_date();

            $media_url = '';
            if ($testimonial_show_id) {
                $media_url = stagekitwp_get_show_image_url(get_post_meta($testimonial_show_id, '_stagekitwp_show_testimonial_media', true));
            }
            if (!$media_url && has_post_thumbnail(get_the_ID())) {
                $media_url = get_the_post_thumbnail_url(get_the_ID(), 'medium');
            }

            $use_overlay = ($text_overlay && $show_media && !empty($media_url));
            $has_media = ($show_media && !empty($media_url));

            echo '<div class="stagekitwp-testimonial' . ( $has_media ? ' stagekitwp-has-media' : ' stagekitwp-no-image' ) . '" style="';
            echo 'background-color:' . esc_attr($bg_color) . ';';
            echo 'color:' . esc_attr($text_color) . ';';
            echo 'font-family:' . esc_attr($base_font) . ';';
            echo 'border:' . intval($border_width) . 'px solid ' . esc_attr($border_color) . ';';
            echo 'border-radius:' . intval($radius) . 'px;';
            echo 'box-shadow:' . esc_attr($shadow) . ';';
            echo 'padding:' . ( $use_overlay ? '0' : '20px' ) . '; margin-bottom: 20px;';
            echo '">';

            $content_html = '';
            if ($show_rating) {
                $content_html .= '<div class="stagekitwp-rating">';
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= $rating) {
                        $content_html .= '<span class="stagekitwp-symbol-filled">' . $symbol_set['filled'] . '</span>';
                    } else {
                        $content_html .= '<span class="stagekitwp-symbol-empty">' . $symbol_set['empty'] . '</span>';
                    }
                }
                $content_html .= '</div>';
            }

            if ($show_comment && $comment) {
                $content_html .= '<div class="stagekitwp-comment">' . esc_html($comment) . '</div>';
            }

            $meta_parts = array();
            if ($show_name && $author) {
                $meta_parts[] = $author;
            }
            if ($show_show && $show_title && $show_name_placement === 'meta') {
                $meta_parts[] = $show_title;
            }
            if ($show_date && $display_date) {
                $meta_parts[] = $display_date;
            }

            if (!empty($meta_parts)) {
                $content_html .= '<div class="stagekitwp-meta">' . esc_html(implode(' - ', $meta_parts)) . '</div>';
            }

            $tag_icon_markup = $has_tag_icon_image
                ? '<span class="stagekitwp-show-tag-icon" style="background-image:url(' . esc_url($tag_icon_url) . ')"></span>'
                : '<span class="stagekitwp-show-tag-icon stagekitwp-show-tag-icon-fallback" aria-hidden="true">🏷</span>';

            $show_name_html = '';
            $show_name_media_badge = '';
            if ($show_show && $show_title) {
                if ($show_name_placement === 'header') {
                    $show_name_html = '<div class="stagekitwp-show-name-header"><span class="stagekitwp-show-tag-badge">' . $tag_icon_markup . '<span class="stagekitwp-show-tag-text">' . esc_html($show_title) . '</span></span></div>';
                } elseif ($show_name_placement === 'slug') {
                    $show_name_html = '<div class="stagekitwp-show-name-slug"><span class="stagekitwp-show-tag-badge">' . $tag_icon_markup . '<span class="stagekitwp-show-tag-text">' . esc_html($show_title) . '</span></span></div>';
                } elseif ($show_name_placement === 'image_indent') {
                    $show_name_media_badge = '<span class="stagekitwp-show-name-media-indent"><span class="stagekitwp-show-tag-badge">' . $tag_icon_markup . '<span class="stagekitwp-show-tag-text">' . esc_html($show_title) . '</span></span></span>';
                }
            }

            if ($use_overlay && $show_name_placement === 'image_indent' && !empty($show_name_media_badge)) {
                // In overlay mode, keep tag inside readable overlay content instead of behind/under gradient layers.
                $show_name_html = str_replace('stagekitwp-show-name-media-indent', 'stagekitwp-show-name-slug', $show_name_media_badge);
                $show_name_media_badge = '';
            }

            if ($show_media && $media_url) {
                echo '<div class="stagekitwp-testimonial-media"><img src="' . esc_url($media_url) . '" alt="' . esc_attr($show_title ? $show_title : $author) . '" loading="lazy" />' . $show_name_media_badge . '</div>';
            } elseif ( $show_name_placement === 'image_indent' && $show_show && $show_title ) {
                $show_name_html = '<div class="stagekitwp-show-name-slug"><span class="stagekitwp-show-tag-badge">' . $tag_icon_markup . '<span class="stagekitwp-show-tag-text">' . esc_html($show_title) . '</span></span></div>';
            }

            if ($show_name_html) {
                $content_html = $show_name_html . $content_html;
            }

            if ($use_overlay) {
                echo '<div class="stagekitwp-overlay-content">' . $content_html . '</div>';
            } else {
                echo '<div class="stagekitwp-content">' . $content_html . '</div>';
            }

            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p>No testimonials found.</p>';
    }

    return ob_get_clean();
}
add_shortcode('stagekitwp_testimonials', 'stagekitwp_testimonials_shortcode');
?>
