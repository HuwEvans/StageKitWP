<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Get image URL from attachment ID or direct URL
 * Handles both old format (direct URLs) and new format (attachment IDs from stagekitwp-sync)
 *
 * @param mixed $image_value Attachment ID (numeric) or URL string
 * @return string The image URL, or empty string if not found
 */
function stagekitwp_get_image_url($image_value) {
    if (empty($image_value)) {
        return '';
    }
    
    // If it's numeric, treat it as an attachment ID
    if (is_numeric($image_value)) {
        $url = wp_get_attachment_url($image_value);
        return $url ? $url : '';
    }
    
    // Otherwise, treat it as a direct URL
    return $image_value;
}

/**
 * Get image HTML or fallback
 */
function stagekitwp_get_image_html($url, $alt = '', $class = '', $fallback = '') {
    if ($url) {
        return '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" class="' . esc_attr($class) . '" />';
    } elseif ($fallback) {
        return '<img src="' . esc_url($fallback) . '" alt="' . esc_attr($alt) . '" class="' . esc_attr($class) . '" />';
    }
    return '';
}

/**
 * Sanitize a CSS color value used in inline styles.
 *
 * Accepts:
 *   - 3- or 6-digit hex  (#fff, #ffffff)
 *   - rgb()  / rgba()    (rgb(0,0,0) / rgba(0,0,0,0.5))
 *   - hsl()  / hsla()    (hsl(0,0%,0%) / hsla(0,0%,0%,0.5))
 *   - CSS named colors   (transparent, currentColor, red …)
 *
 * Returns a sanitized value, or $fallback when the input is empty / unrecognised.
 */
function stagekitwp_sanitize_css_color($value, $fallback = '#000000') {
    if ($value === null || $value === '') {
        return $fallback;
    }

    $value = trim($value);

    // 1. Hex colours (3 or 6 digits, with or without #)
    $hex = sanitize_hex_color($value);
    if ($hex) {
        return $hex;
    }

    // 2. rgb() / rgba() / hsl() / hsla() — allow digits, spaces, commas,
    //    dots, slashes, and % inside parens; no JS or expressions allowed.
    if (preg_match('/^(rgba?|hsla?)\([\d\s,\.\/%]+\)$/i', $value)) {
        return $value;
    }

    // 3. CSS named colors and keywords (transparent, currentColor, inherit, …)
    //    Only letters and hyphens — no expressions, no url(), nothing executable.
    if (preg_match('/^[a-zA-Z\-]+$/', $value)) {
        return $value;
    }

    // Unrecognised format — return the fallback.
    if ($fallback === '') {
        return '';
    }
    $fallback_sanitized = sanitize_hex_color($fallback);
    return $fallback_sanitized ? $fallback_sanitized : '#000000';
}

/**
 * Canonical show time-slot sort order for a July–June theatre season.
 *
 * Seasons run July – June, so the production year goes:
 *   Fall (Jul–Nov)  →  Winter (Dec–Mar)  →  Spring (Apr–Jun)
 *
 * Returns an associative array suitable for index lookup:
 *   array( 'Fall' => 0, 'Winter' => 1, 'Spring' => 2 )
 *
 * Usage in a usort callback:
 *   $order = stagekitwp_show_slot_order();
 *   $ia = isset( $order[$sa] ) ? $order[$sa] : 999;
 *   $ib = isset( $order[$sb] ) ? $order[$sb] : 999;
 *   return $ia - $ib;
 *
 * @return array
 */
function stagekitwp_show_slot_order() {
    return array(
        'Fall'   => 0,
        'Winter' => 1,
        'Spring' => 2,
    );
}

/**
 * Sort an array of show WP_Post objects by time slot (Fall, Winter, Spring).
 * Shows with no slot assigned sort last.
 *
 * @param  WP_Post[] $shows     Array of show post objects.
 * @return WP_Post[]            Sorted array (original array not modified).
 */
function stagekitwp_sort_shows_by_slot( $shows ) {
    $order = stagekitwp_show_slot_order();
    usort( $shows, function( $a, $b ) use ( $order ) {
        $sa = get_post_meta( $a->ID, '_stagekitwp_show_time_slot', true );
        $sb = get_post_meta( $b->ID, '_stagekitwp_show_time_slot', true );
        $ia = isset( $order[ $sa ] ) ? $order[ $sa ] : 999;
        $ib = isset( $order[ $sb ] ) ? $order[ $sb ] : 999;
        return $ia - $ib;
    } );
    return $shows;
}

/**
 * Return the canonical front-end URL for a show CPT post.
 *
 * Resolution order:
 *   1. The show's own CPT permalink (get_permalink), which routes through
 *      single-show.php and renders the Front-end Display (landing page etc.).
 *   2. Falls back to '' if the show has no published permalink.
 *
 * Use this everywhere a show name or image is linked so that all shortcodes
 * stay consistent and can benefit from the Front-end Display system.
 *
 * @param int|WP_Post $show  Show post ID or object.
 * @return string  Absolute URL, or '' when unavailable (draft, trashed, etc.).
 */
function stagekitwp_show_page_url( $show ) {
    $id  = ( $show instanceof WP_Post ) ? $show->ID : intval( $show );
    if ( ! $id ) { return ''; }
    $url = get_permalink( $id );
    return ( $url && $url !== false ) ? (string) $url : '';
}

/**
 * Default sponsorship/contribution level labels.
 *
 * @return array
 */
function stagekitwp_default_level_labels() {
    return array(
        'Platinum' => 'Platinum',
        'Gold'     => 'Gold',
        'Silver'   => 'Silver',
        'Bronze'   => 'Bronze',
    );
}

/**
 * Context-aware level labels.
 *
 * Sponsors include Diamond as the top tier.
 * Contributors keep the original four levels.
 *
 * @param string $context Either 'sponsor' or 'contributor'.
 * @return array
 */
function stagekitwp_level_labels_for_context( $context ) {
    $context = sanitize_key( (string) $context );

    if ( $context === 'sponsor' ) {
        return array(
            'Diamond'  => 'Diamond',
            'Platinum' => 'Platinum',
            'Gold'     => 'Gold',
            'Silver'   => 'Silver',
            'Bronze'   => 'Bronze',
        );
    }

    return stagekitwp_default_level_labels();
}

/**
 * Resolve level display labels from options and optional shortcode overrides.
 *
 * Supported shortcode override attrs:
 * - diamond_label
 * - platinum_label
 * - gold_label
 * - silver_label
 * - bronze_label
 *
 * @param string $context Either 'sponsor' or 'contributor'.
 * @param array  $atts    Shortcode attributes.
 * @return array
 */
function stagekitwp_get_level_display_labels( $context, $atts = array() ) {
    $context = sanitize_key( (string) $context );
    $defaults = stagekitwp_level_labels_for_context( $context );

    $option_map = array(
        'Diamond'  => "stagekitwp_{$context}_level_label_diamond",
        'Platinum' => "stagekitwp_{$context}_level_label_platinum",
        'Gold'     => "stagekitwp_{$context}_level_label_gold",
        'Silver'   => "stagekitwp_{$context}_level_label_silver",
        'Bronze'   => "stagekitwp_{$context}_level_label_bronze",
    );

    $attr_map = array(
        'Diamond'  => 'diamond_label',
        'Platinum' => 'platinum_label',
        'Gold'     => 'gold_label',
        'Silver'   => 'silver_label',
        'Bronze'   => 'bronze_label',
    );

    $labels = array();
    foreach ( $defaults as $level => $fallback ) {
        $option_key = isset( $option_map[ $level ] ) ? $option_map[ $level ] : '';
        $saved = $option_key ? trim( sanitize_text_field( (string) get_option( $option_key, '' ) ) ) : '';
        $labels[ $level ] = ( $saved !== '' ) ? $saved : $fallback;

        $attr_key = isset( $attr_map[ $level ] ) ? $attr_map[ $level ] : '';
        if ( $attr_key && isset( $atts[ $attr_key ] ) ) {
            $override = trim( sanitize_text_field( (string) $atts[ $attr_key ] ) );
            if ( $override !== '' ) {
                $labels[ $level ] = $override;
            }
        }
    }

    return $labels;
}

/**
 * Get the configured Organization Name, falling back to Site Identity (blog name).
 *
 * @return string
 */
function stagekitwp_get_organization_name() {
    $org = get_option( 'stagekitwp_organization_name', '' );
    if ( is_string( $org ) && '' !== trim( $org ) ) {
        return trim( $org );
    }
    return get_bloginfo( 'name' );
}

/**
 * Get the configured Organization Address, used as a location fallback in schema markup.
 *
 * @return string
 */
function stagekitwp_get_organization_address() {
    $address = get_option( 'stagekitwp_organization_address', '' );
    return is_string( $address ) ? trim( $address ) : '';
}

/**
 * Resolve the "next" season: the season immediately after the current one
 * (by date range), or the earliest future season if no season is currently
 * running. Returns null when there is no next season.
 *
 * @return WP_Post|null
 */
function stagekitwp_get_next_season() {
    static $cached      = null;
    static $has_resolved = false;
    if ( $has_resolved ) {
        return $cached;
    }
    $has_resolved = true;

    $season_ids = get_posts( array(
        'post_type'      => 'season',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ) );
    if ( empty( $season_ids ) ) {
        return $cached;
    }

    $now     = current_time( 'timestamp' );
    $seasons = array();
    foreach ( $season_ids as $season_id ) {
        $start_raw = get_post_meta( $season_id, '_stagekitwp_season_start_date', true );
        $end_raw   = get_post_meta( $season_id, '_stagekitwp_season_end_date', true );
        $seasons[] = array(
            'id'    => $season_id,
            'start' => $start_raw ? strtotime( $start_raw ) : 0,
            'end'   => $end_raw ? strtotime( $end_raw ) + 86399 : 0,
        );
    }
    usort( $seasons, function ( $a, $b ) {
        return $a['start'] - $b['start'];
    } );

    $current_index = null;
    foreach ( $seasons as $index => $season ) {
        if ( $season['start'] && $season['end'] && $now >= $season['start'] && $now <= $season['end'] ) {
            $current_index = $index;
            break;
        }
    }

    if ( null !== $current_index ) {
        $next = isset( $seasons[ $current_index + 1 ] ) ? $seasons[ $current_index + 1 ] : null;
        return $cached = ( $next ? get_post( $next['id'] ) : null );
    }

    foreach ( $seasons as $season ) {
        if ( $season['start'] > $now ) {
            return $cached = get_post( $season['id'] );
        }
    }

    return $cached;
}

/**
 * Whether a given season has any published shows assigned to it.
 *
 * @param int $season_id
 * @return bool
 */
function stagekitwp_season_has_shows( $season_id ) {
    $season_id = absint( $season_id );
    if ( ! $season_id ) {
        return false;
    }
    $show_ids = get_posts( array(
        'post_type'      => 'show',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => '_stagekitwp_show_season',
        'meta_value'     => $season_id,
    ) );
    return ! empty( $show_ids );
}

/**
 * Whether the next season (if any) has any published shows. No next season
 * always resolves to false.
 *
 * @return bool
 */
function stagekitwp_next_season_has_shows() {
    $season = stagekitwp_get_next_season();
    return $season ? stagekitwp_season_has_shows( $season->ID ) : false;
}
