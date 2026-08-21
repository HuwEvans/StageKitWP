<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Shortcode: [stagekitwp_auditions]
 *
 * Displays upcoming auditions (shows with an audition date no older than
 * `days_past` days ago), ordered by audition date ascending.
 *
 * Attributes:
 *   days_past    (int,  default 7)      Days back to still include an audition.
 *   layout       (str,  default list)   list | cards | compact
 *   show_details (bool, default true)   Include the audition details body.
 *   heading      (str,  default '')     Optional H2 heading above the list.
 *   title        (str,  default '')     Optional compact-layout heading.
 *
 * Light/dark mode:
 *   Colours come from the Auditions tab in Display Options. Light keys:
 *     stagekitwp_auditions_bg_color / _text_color / _border_color / _h2_color / _h3_color
 *   Dark keys (used only when the TM theme dark integration is active):
 *     the same keys suffixed with _dark. Output is driven by CSS custom
 *     properties on .stagekitwp-auditions, overridden under html.stagekitwp-dark-mode so the
 *     theme's toggle swaps palettes with no reload.
 */
function stagekitwp_auditions_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'days_past'    => 7,
        'layout'       => 'list',
        'show_details' => 'true',
        'heading'      => '',
        'title'        => '',
    ), $atts, 'stagekitwp_auditions' );

    $days_past = absint( $atts['days_past'] );
    if ( $days_past < 1 ) {
        $days_past = 7;
    }

    $layout = strtolower( trim( $atts['layout'] ) );
    if ( ! in_array( $layout, array( 'list', 'cards', 'compact' ), true ) ) {
        $layout = 'list';
    }
    $show_details = ( 'false' !== strtolower( (string) $atts['show_details'] ) );

    $shows = stagekitwp_auditions_query( $days_past );

    // Always emit the palette CSS so an empty state still themes correctly.
    stagekitwp_auditions_enqueue_css();

    if ( empty( $shows ) ) {
        return '<div class="stagekitwp-auditions stagekitwp-auditions--empty"><p class="stagekitwp-aud-empty"><em>'
            . esc_html__( 'No upcoming auditions at this time.', 'stagekitwp-core' )
            . '</em></p></div>';
    }

    $cutoff = stagekitwp_auditions_cutoff( $days_past );

    ob_start();
    echo '<div class="stagekitwp-auditions stagekitwp-auditions--' . esc_attr( $layout ) . '">';

    if ( 'compact' !== $layout && '' !== trim( (string) $atts['heading'] ) ) {
        echo '<h2 class="stagekitwp-aud-heading">' . esc_html( $atts['heading'] ) . '</h2>';
    }

    switch ( $layout ) {
        case 'cards':
            stagekitwp_auditions_render_cards( $shows, $cutoff, $show_details );
            break;
        case 'compact':
            stagekitwp_auditions_render_compact( $shows, $cutoff, $atts['title'] );
            break;
        case 'list':
        default:
            stagekitwp_auditions_render_list( $shows, $cutoff, $show_details );
            break;
    }

    echo '</div>';
    return ob_get_clean();
}
add_shortcode( 'stagekitwp_auditions', 'stagekitwp_auditions_shortcode' );
add_shortcode( 'STAGEKITWP_Auditions', 'stagekitwp_auditions_shortcode' );

/* ===================================================================== */
/*  Data                                                                  */
/* ===================================================================== */

function stagekitwp_auditions_cutoff( $days_past ) {
    return wp_date( 'Y-m-d', strtotime( '-' . absint( $days_past ) . ' days', current_time( 'timestamp' ) ) );
}

function stagekitwp_auditions_query( $days_past ) {
    $cutoff = stagekitwp_auditions_cutoff( $days_past );

    $shows = get_posts( array(
        'post_type'   => 'show',
        'numberposts' => -1,
        'meta_key'    => '_stagekitwp_show_audition_date',
        'orderby'     => 'meta_value',
        'order'       => 'ASC',
        'meta_query'  => array(
            array(
                'key'     => '_stagekitwp_show_audition_date',
                'value'   => $cutoff,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        ),
    ) );

    usort( $shows, function ( $left, $right ) {
        $l = strtotime( get_post_meta( $left->ID, '_stagekitwp_show_audition_date', true ) ) ?: 0;
        $r = strtotime( get_post_meta( $right->ID, '_stagekitwp_show_audition_date', true ) ) ?: 0;
        if ( $l === $r ) {
            return strcmp( get_the_title( $left->ID ), get_the_title( $right->ID ) );
        }
        return $l <=> $r;
    } );

    return $shows;
}

/* ===================================================================== */
/*  Renderers (filled below)                                              */
/* ===================================================================== */

/**
 * Pull normalised audition data for one show, or null if it should be skipped.
 * Returns: [ 'id', 'title', 'raw', 'date', 'day', 'month', 'details_html' ]
 */
function stagekitwp_auditions_item( $show, $cutoff, $want_details = true ) {
    $raw = get_post_meta( $show->ID, '_stagekitwp_show_audition_date', true );
    if ( empty( $raw ) || $raw < $cutoff ) {
        return null;
    }
    $ts = strtotime( $raw );
    $details_html = '';
    if ( $want_details ) {
        $details = get_post_meta( $show->ID, '_stagekitwp_show_audition_details', true );
        if ( ! empty( $details ) ) {
            $details_html = wp_kses_post( force_balance_tags( wpautop( $details ) ) );
        }
    }
    return array(
        'id'           => $show->ID,
        'title'        => get_the_title( $show->ID ),
        'raw'          => $raw,
        'date'         => date_i18n( get_option( 'date_format' ), $ts ),
        'day'          => date_i18n( 'j', $ts ),
        'month'        => date_i18n( 'M', $ts ),
        'details_html' => $details_html,
    );
}

/* ---- Layout: list (default) — stacked article cards ---- */
function stagekitwp_auditions_render_list( $shows, $cutoff, $show_details ) {
    foreach ( $shows as $show ) {
        $it = stagekitwp_auditions_item( $show, $cutoff, $show_details );
        if ( ! $it ) { continue; }
        echo '<article class="stagekitwp-audition-item">';
        echo '<h3 class="stagekitwp-audition-show-title">' . esc_html( $it['title'] ) . '</h3>';
        echo '<p class="stagekitwp-audition-date"><span class="stagekitwp-aud-label">'
            . esc_html__( 'Audition Date:', 'stagekitwp-core' ) . '</span> '
            . esc_html( $it['date'] ) . '</p>';
        if ( $show_details && $it['details_html'] ) {
            echo '<div class="stagekitwp-audition-details">' . $it['details_html'] . '</div>';
        }
        echo '</article>';
    }
}

/* ---- Layout: cards — responsive grid with date badge ---- */
function stagekitwp_auditions_render_cards( $shows, $cutoff, $show_details ) {
    echo '<div class="stagekitwp-aud-grid">';
    foreach ( $shows as $show ) {
        $it = stagekitwp_auditions_item( $show, $cutoff, $show_details );
        if ( ! $it ) { continue; }
        echo '<article class="stagekitwp-aud-card">';
        echo '<div class="stagekitwp-aud-badge" aria-hidden="true">'
            . '<span class="stagekitwp-aud-badge-day">' . esc_html( $it['day'] ) . '</span>'
            . '<span class="stagekitwp-aud-badge-month">' . esc_html( $it['month'] ) . '</span>'
            . '</div>';
        echo '<div class="stagekitwp-aud-card-body">';
        echo '<h3 class="stagekitwp-aud-card-title">' . esc_html( $it['title'] ) . '</h3>';
        echo '<p class="stagekitwp-aud-card-date">' . esc_html( $it['date'] ) . '</p>';
        if ( $show_details && $it['details_html'] ) {
            echo '<div class="stagekitwp-aud-card-details">' . $it['details_html'] . '</div>';
        }
        echo '</div></article>';
    }
    echo '</div>';
}

/* ---- Layout: compact — tight table for many auditions ---- */
function stagekitwp_auditions_render_compact( $shows, $cutoff, $title = '' ) {
    $title = trim( (string) $title );
    if ( '' !== $title ) {
        echo '<h2 class="stagekitwp-events-heading">' . esc_html( $title ) . '</h2>';
    }

    echo '<table class="stagekitwp-aud-table"><thead><tr>';
    echo '<th scope="col">' . esc_html__( 'Date', 'stagekitwp-core' ) . '</th>';
    echo '<th scope="col">' . esc_html__( 'Production', 'stagekitwp-core' ) . '</th>';
    echo '</tr></thead><tbody>';
    foreach ( $shows as $show ) {
        $it = stagekitwp_auditions_item( $show, $cutoff, false );
        if ( ! $it ) { continue; }
        echo '<tr>';
        echo '<td class="stagekitwp-aud-td-date"><time datetime="' . esc_attr( $it['raw'] ) . '">'
            . esc_html( $it['date'] ) . '</time></td>';
        echo '<td class="stagekitwp-aud-td-title">' . esc_html( $it['title'] ) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}

/* ===================================================================== */
/*  CSS (filled below)                                                    */
/* ===================================================================== */

/**
 * Build and inject the auditions palette + layout CSS. Light values come from
 * the Auditions Display Options; dark values (when the TM theme dark
 * integration is active) are read from the *_dark keys and applied under
 * html.stagekitwp-dark-mode so the theme toggle swaps palettes with no reload.
 */
function stagekitwp_auditions_enqueue_css() {
    // ---- Light palette ----
    $bg   = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_auditions_bg_color', '' ),     '#ffffff' );
    $tx   = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_auditions_text_color', '' ),   '#1a1a1a' );
    $brd  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_auditions_border_color', '' ), '#e2e2e2' );
    $h2   = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_auditions_h2_color', '' ),     $tx );
    $h3   = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_auditions_h3_color', '' ),     '#1a3a6b' );
    $font = sanitize_text_field( get_option( 'stagekitwp_auditions_base_font', 'inherit' ) );
    $acc  = $h3 ?: '#1a3a6b';

    $css  = ".stagekitwp-auditions{"
        . "--stagekitwp-aud-bg:{$bg};--stagekitwp-aud-text:{$tx};--stagekitwp-aud-border:{$brd};"
        . "--stagekitwp-aud-h2:{$h2};--stagekitwp-aud-h3:{$h3};--stagekitwp-aud-acc:{$acc};"
        . "--stagekitwp-aud-badge-bg:{$acc};--stagekitwp-aud-badge-text:#ffffff;"
        . "--stagekitwp-aud-font:{$font};font-family:var(--stagekitwp-aud-font);color:var(--stagekitwp-aud-text)}";

    // Shared structural CSS (palette-independent, uses the vars above).
    $css .= stagekitwp_auditions_layout_css();

    // ---- Dark overrides ----
    if ( function_exists( 'stagekitwp_dark_mode_admin_enabled' ) && stagekitwp_dark_mode_admin_enabled() ) {
        // Luminance-guarded readers: reject near-black text / near-white bg mistakes.
        $lum = function ( $hex ) {
            $h = ltrim( $hex, '#' );
            if ( strlen( $h ) === 3 ) { $h = $h[0].$h[0].$h[1].$h[1].$h[2].$h[2]; }
            if ( strlen( $h ) !== 6 ) { return -1; }
            return 0.2126 * hexdec( substr( $h, 0, 2 ) ) / 255
                 + 0.7152 * hexdec( substr( $h, 2, 2 ) ) / 255
                 + 0.0722 * hexdec( substr( $h, 4, 2 ) ) / 255;
        };
        $ok_bg = function ( $v, $fb ) use ( $lum ) {
            $v2 = stagekitwp_sanitize_css_color( $v ); if ( ! $v2 ) { return $fb; }
            return ( $lum( $v2 ) < 0.04 || $lum( $v2 ) > 0.85 ) ? $fb : $v2; };
        $ok_tx = function ( $v, $fb ) use ( $lum ) {
            $v2 = stagekitwp_sanitize_css_color( $v ); if ( ! $v2 ) { return $fb; }
            $l = $lum( $v2 ); return ( $l > 0.85 || $l < 0.04 ) ? $fb : $v2; };

        $d_bg  = $ok_bg( get_option( 'stagekitwp_auditions_bg_color_dark', '' ),   '#1e1e1e' );
        $d_tx  = $ok_tx( get_option( 'stagekitwp_auditions_text_color_dark', '' ), '#e0e0e0' );
        $d_h2  = $ok_tx( get_option( 'stagekitwp_auditions_h2_color_dark', '' ),   '#e0e0e0' );
        $d_h3  = $ok_tx( get_option( 'stagekitwp_auditions_h3_color_dark', '' ),   '#7fb2e6' );

        // Border: reject near-black so it stays visible on the dark card.
        $d_brd_raw = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_auditions_border_color_dark', '' ), '' ) ?: '';
        $d_brd = ( $d_brd_raw && $lum( $d_brd_raw ) >= 0.04 ) ? $d_brd_raw : 'rgba(255,255,255,0.15)';

        $d_acc = $d_h3 ?: '#1a78c2';

        $css .= "html.stagekitwp-dark-mode .stagekitwp-auditions{"
            . "--stagekitwp-aud-bg:{$d_bg}!important;--stagekitwp-aud-text:{$d_tx}!important;"
            . "--stagekitwp-aud-border:{$d_brd}!important;--stagekitwp-aud-h2:{$d_h2}!important;"
            . "--stagekitwp-aud-h3:{$d_h3}!important;--stagekitwp-aud-acc:{$d_acc}!important;"
            . "--stagekitwp-aud-badge-bg:{$d_acc}!important;--stagekitwp-aud-badge-text:#ffffff!important}";
    }

    stagekitwp_add_shortcode_inline_style( 'auditions', $css );
}

/**
 * Palette-independent structural CSS for all three layouts. Every colour is a
 * var(--stagekitwp-aud-*) so the same rules serve light and dark.
 */
function stagekitwp_auditions_layout_css() {
    return <<<CSS

.stagekitwp-auditions{max-width:900px;margin:0 auto}
.stagekitwp-auditions .stagekitwp-aud-heading{color:var(--stagekitwp-aud-h2)!important;margin:0 0 18px;font-size:1.6em}
.stagekitwp-auditions .stagekitwp-aud-empty{opacity:.75}
.stagekitwp-auditions .stagekitwp-aud-label{color:var(--stagekitwp-aud-h3)!important;font-weight:700}

/* Layout: list */
.stagekitwp-auditions--list .stagekitwp-audition-item{background:var(--stagekitwp-aud-bg);color:var(--stagekitwp-aud-text);border:1px solid var(--stagekitwp-aud-border);border-radius:8px;padding:18px 20px;margin-bottom:16px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
html.stagekitwp-dark-mode .stagekitwp-auditions--list .stagekitwp-audition-item{box-shadow:0 1px 6px rgba(0,0,0,.4)}
.stagekitwp-auditions--list .stagekitwp-audition-show-title{color:var(--stagekitwp-aud-h2)!important;margin:0 0 6px;font-size:1.2em}
.stagekitwp-auditions--list .stagekitwp-audition-date{margin:0 0 10px;font-size:.95em}
.stagekitwp-auditions--list .stagekitwp-audition-details{font-size:.92em;line-height:1.6}
.stagekitwp-auditions--list .stagekitwp-audition-details :last-child{margin-bottom:0}

/* Layout: cards */
.stagekitwp-aud-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px}
.stagekitwp-aud-card{display:flex;background:var(--stagekitwp-aud-bg);color:var(--stagekitwp-aud-text);border:1px solid var(--stagekitwp-aud-border);border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)}
html.stagekitwp-dark-mode .stagekitwp-aud-card{box-shadow:0 2px 10px rgba(0,0,0,.45)}
.stagekitwp-aud-badge{flex:0 0 68px;display:flex;flex-direction:column;align-items:center;justify-content:center;background:var(--stagekitwp-aud-badge-bg);color:var(--stagekitwp-aud-badge-text)!important;padding:14px 8px}
.stagekitwp-aud-badge-day{font-size:1.7em;font-weight:800;line-height:1}
.stagekitwp-aud-badge-month{font-size:.78em;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-top:2px}
.stagekitwp-aud-card-body{padding:14px 16px;flex:1;min-width:0}
.stagekitwp-aud-card-title{color:var(--stagekitwp-aud-h2)!important;margin:0 0 4px;font-size:1.1em}
.stagekitwp-aud-card-date{margin:0 0 8px;font-size:.85em;opacity:.8}
.stagekitwp-aud-card-details{font-size:.88em;line-height:1.55}
.stagekitwp-aud-card-details :last-child{margin-bottom:0}

/* Layout: compact table */
.stagekitwp-aud-table{width:100%;border-collapse:collapse;font-size:.95em;color:var(--stagekitwp-aud-text)}
.stagekitwp-aud-table th,.stagekitwp-aud-table td{text-align:left;padding:10px 14px;border-bottom:1px solid var(--stagekitwp-aud-border)}
.stagekitwp-aud-table thead th{color:var(--stagekitwp-aud-h3)!important;font-size:.78em;text-transform:uppercase;letter-spacing:.06em;border-bottom-width:2px}
.stagekitwp-aud-table .stagekitwp-aud-td-date{white-space:nowrap;font-weight:600;color:var(--stagekitwp-aud-h2)!important}
.stagekitwp-aud-table tbody tr:hover{background:rgba(0,0,0,.03)}
html.stagekitwp-dark-mode .stagekitwp-aud-table tbody tr:hover{background:rgba(255,255,255,.05)}

@media (max-width:480px){
.stagekitwp-aud-badge{flex-basis:56px}
.stagekitwp-aud-badge-day{font-size:1.4em}
}
CSS;
}
