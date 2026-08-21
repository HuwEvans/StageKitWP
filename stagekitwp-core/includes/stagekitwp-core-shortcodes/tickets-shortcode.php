<?php
/**
 * Shortcode: [stagekitwp_tickets]
 *
 * Attributes:
 *   show_limit   int     Max shows. Default 4.
 *   layout       string  banner | cards | table | minimal | spotlight  Default: banner
 *   show_image   bool    Show poster image (cards, spotlight). Default true.
 *   show_dates   bool    Show dates. Default true.
 *   show_genre   bool    Show genre badge. Default false.
 *   label_season string  Override "Season Tickets" label.
 *   label_show   string  Override "Show Tickets" label.
 *   button_text  string  Override CTA text. Default "Get Tickets".
 *   season_button_text  string  Override CTA text. Default "Get Tickets". *
 * @package StageKitWP
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

if ( ! function_exists( 'stagekitwp_tickets_resolve_image' ) ) :
function stagekitwp_tickets_resolve_image( $value, $size = 'medium' ) {
    if ( empty( $value ) ) { return false; }
    if ( is_numeric( $value ) ) {
        $src = wp_get_attachment_image_url( intval( $value ), $size );
        return $src ? $src : false;
    }
    return esc_url( $value );
}
endif;

if ( ! function_exists( 'stagekitwp_tickets_format_date' ) ) :
function stagekitwp_tickets_format_date( $date ) {
    if ( empty( $date ) ) { return ''; }
    $ts = strtotime( $date );
    return $ts ? date_i18n( 'M j, Y', $ts ) : esc_html( $date );
}
endif;

// ---------------------------------------------------------------------------
// Main shortcode
// ---------------------------------------------------------------------------

function stagekitwp_tickets_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'show_limit'   => '4',
        'layout'       => 'banner',
        'show_image'   => 'true',
        'show_dates'   => 'true',
        'show_genre'   => 'false',
        'label_season' => '',
        'label_show'   => '',
        'button_text'  => '',
    ), $atts, 'stagekitwp_tickets' );

    $show_limit   = max( 1, intval( $atts['show_limit'] ) );
    $layout       = sanitize_key( $atts['layout'] );
    $show_image   = filter_var( $atts['show_image'],  FILTER_VALIDATE_BOOLEAN );
    $show_dates   = filter_var( $atts['show_dates'],  FILTER_VALIDATE_BOOLEAN );
    $show_genre   = filter_var( $atts['show_genre'],  FILTER_VALIDATE_BOOLEAN );
    $label_season = $atts['label_season'] ? sanitize_text_field( $atts['label_season'] ) : __( 'Season Tickets', 'stagekitwp-core' );
    $label_show   = $atts['label_show']   ? sanitize_text_field( $atts['label_show'] )   : __( 'Show Tickets',   'stagekitwp-core' );
    $button_text  = $atts['button_text']  ? sanitize_text_field( $atts['button_text'] )  : __( 'Get Tickets',    'stagekitwp-core' );
    $season_button_text  = 'Get Season Tickets';

    if ( ! in_array( $layout, array( 'banner', 'cards', 'table', 'minimal', 'spotlight' ), true ) ) {
        $layout = 'banner';
    }

    // Display Options — Light (primary) values
    $raw_bg          = get_option( 'stagekitwp_tickets_bg_color',          '' );
    $raw_text        = get_option( 'stagekitwp_tickets_text_color',         '' );
    $raw_btn         = get_option( 'stagekitwp_tickets_button_color',       '' );
    $raw_btn_hov     = get_option( 'stagekitwp_tickets_button_hover_color', '' );
    $raw_border      = get_option( 'stagekitwp_tickets_border_color',       '' );
    $bg_color           = $raw_bg      ? stagekitwp_sanitize_css_color( $raw_bg )      : '#f8f9fa';
    $text_color         = $raw_text    ? stagekitwp_sanitize_css_color( $raw_text )    : '#333333';
    $button_color       = $raw_btn     ? stagekitwp_sanitize_css_color( $raw_btn )     : '#0073aa';
    $button_hover_color = $raw_btn_hov ? stagekitwp_sanitize_css_color( $raw_btn_hov ) : '#005a87';
    $border_color       = $raw_border  ? stagekitwp_sanitize_css_color( $raw_border )  : '#e0e0e0';
    $rounded            = get_option( 'stagekitwp_tickets_rounded', '1' );
    $radius             = absint( get_option( 'stagekitwp_tickets_radius', '6' ) );
    $shadow             = get_option( 'stagekitwp_tickets_shadow', '0' );
    $base_font          = sanitize_text_field( get_option( 'stagekitwp_tickets_base_font', '' ) );
    $card_radius        = $rounded ? $radius . 'px' : '0';
    $card_shadow        = $shadow  ? '0 2px 8px rgba(0,0,0,0.12)' : 'none';

    // Display Options — Dark overrides
    $dark_active        = function_exists( 'stagekitwp_dark_mode_admin_enabled' ) && stagekitwp_dark_mode_admin_enabled();
    $raw_d_bg      = $dark_active ? get_option( 'stagekitwp_tickets_bg_color_dark',          '' ) : '';
    $raw_d_text    = $dark_active ? get_option( 'stagekitwp_tickets_text_color_dark',         '' ) : '';
    $raw_d_btn     = $dark_active ? get_option( 'stagekitwp_tickets_button_color_dark',       '' ) : '';
    $raw_d_hover   = $dark_active ? get_option( 'stagekitwp_tickets_button_hover_color_dark', '' ) : '';
    $raw_d_border  = $dark_active ? get_option( 'stagekitwp_tickets_border_color_dark',       '' ) : '';
    $dark_bg       = $raw_d_bg     ? stagekitwp_sanitize_css_color( $raw_d_bg )     : '';
    $dark_text     = $raw_d_text   ? stagekitwp_sanitize_css_color( $raw_d_text )   : '';
    $dark_button   = $raw_d_btn    ? stagekitwp_sanitize_css_color( $raw_d_btn )    : '';
    $dark_hover    = $raw_d_hover  ? stagekitwp_sanitize_css_color( $raw_d_hover )  : '';
    $dark_border   = $raw_d_border ? stagekitwp_sanitize_css_color( $raw_d_border ) : '';

    // Current season
    $seasons = get_posts( array(
        'post_type' => 'season', 'posts_per_page' => 1, 'post_status' => 'publish',
        'meta_query' => array( array( 'key' => '_stagekitwp_season_is_current', 'value' => 1, 'compare' => '=' ) ),
    ) );
    if ( empty( $seasons ) ) {
        return '<div class="stagekitwp-tickets-block stagekitwp-tickets-empty"><p>' . esc_html__( 'No current season found.', 'stagekitwp-core' ) . '</p></div>';
    }
    $season        = $seasons[0];
    $season_name   = get_post_meta( $season->ID, '_stagekitwp_season_name', true ) ?: get_the_title( $season->ID );
    $season_url    = get_post_meta( $season->ID, '_stagekitwp_season_tickets_url',   true );
    $season_img    = get_post_meta( $season->ID, '_stagekitwp_season_image_front',   true );
    $season_banner = get_post_meta( $season->ID, '_stagekitwp_season_social_banner', true );
    $season_start  = get_post_meta( $season->ID, '_stagekitwp_season_start_date',    true );
    $season_end    = get_post_meta( $season->ID, '_stagekitwp_season_end_date',      true );

    // Shows for current season
    $shows = get_posts( array(
        'post_type'      => 'show',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array( array( 'key' => '_stagekitwp_show_season', 'value' => $season->ID, 'compare' => '=' ) ),
    ) );
    $shows = stagekitwp_sort_shows_by_slot( $shows );
    if ( $show_limit > 0 ) {
        $shows = array_slice( $shows, 0, $show_limit );
    }

    stagekitwp_tickets_enqueue_styles( $button_color, $button_hover_color, $card_radius, $card_shadow, $bg_color, $text_color, $border_color, $base_font, $dark_bg, $dark_text, $dark_button, $dark_hover, $dark_border );

    $args = array( $season, $season_name, $season_url, $season_img, $season_banner, $season_start, $season_end, $shows, $show_image, $show_dates, $show_genre, $label_season, $label_show, $button_text, $button_color, $button_hover_color, $bg_color, $text_color, $border_color, $card_radius, $card_shadow, $base_font );

    switch ( $layout ) {
        case 'cards':    return call_user_func_array( 'stagekitwp_tickets_render_cards',    $args );
        case 'table':    return call_user_func_array( 'stagekitwp_tickets_render_table',    $args );
        case 'minimal':  return call_user_func_array( 'stagekitwp_tickets_render_minimal',  $args );
        case 'spotlight':return call_user_func_array( 'stagekitwp_tickets_render_spotlight',$args );
        default:         return call_user_func_array( 'stagekitwp_tickets_render_banner',   $args );
    }
}
add_shortcode( 'stagekitwp_tickets', 'stagekitwp_tickets_shortcode' );

// ---------------------------------------------------------------------------
// Styles
// ---------------------------------------------------------------------------

function stagekitwp_tickets_enqueue_styles( $btn, $btn_hover, $radius, $shadow, $bg, $text, $border, $font, $dark_bg = '', $dark_text = '', $dark_btn = '', $dark_hover = '', $dark_border = '' ) {
    $btn_fallback = ! empty($btn) ? esc_attr($btn) : '#0066cc';

    $css_vars = '
        --stagekitwp-tk-bg:      ' . esc_attr($bg)         . ';
        --stagekitwp-tk-text:    ' . esc_attr($text)        . ';
        --stagekitwp-tk-btn:     ' . esc_attr($btn)         . ';
        --stagekitwp-tk-btn-hov: ' . esc_attr($btn_hover)   . ';
        --stagekitwp-tk-border:  ' . esc_attr($border)      . ';
        --stagekitwp-tk-font:    ' . esc_attr($font)        . ';
        --stagekitwp-tk-radius:  ' . esc_attr($radius)      . ';
        --stagekitwp-tk-shadow:  ' . esc_attr($shadow)      . ';';

    stagekitwp_add_shortcode_inline_style( 'tickets', '
        /* --- [stagekitwp_tickets] structural layout --- */
        .stagekitwp-tickets-block {
            margin: 20px 0;
            font-family: var(--stagekitwp-tk-font);
            color: var(--stagekitwp-tk-text);' . $css_vars . '
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            position: relative;
            z-index: 1;
        }
        .stagekitwp-tickets-empty { padding:20px; background:#f5f5f5; border:1px solid #ddd; border-radius:4px; text-align:center; color:#666; }

        .stagekitwp-ticket-info-link {
            -webkit-appearance: none !important;
            appearance: none !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            text-align: center !important;
            padding: 8px 12px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            text-decoration: none !important;
            color: #ffffff !important;
            background-color: ' . $btn_fallback . ' !important;
            background-color: var(--stagekitwp-tk-btn) !important; 
            border: 1px solid var(--stagekitwp-tk-border) !important;
            border-radius: 5px !important;
            -webkit-transition: opacity .15s, background-color .15s !important;
            transition: opacity .15s, background-color .15s !important;
            position: relative !important;
            z-index: 2 !important;
            box-sizing: border-box !important;
            width: 100% !important;
            margin-top: 0 !important;
        }
        .stagekitwp-ticket-info-link:hover { 
            background-color: #f0f0f0 !important;
            opacity: 1 !important; 
        }

        /* Banner */
        .stagekitwp-ticket-btn-wrap { margin-bottom:12px; }
        .stagekitwp-ticket-btn-wrap:last-child { margin-bottom:0; }
        .stagekitwp-ticket-btn { 
            display:-webkit-flex !important; display:flex !important; 
            -webkit-flex-direction:column !important; flex-direction:column !important; 
            -webkit-align-items:center; align-items:center; 
            -webkit-justify-content:center; justify-content:center; 
            padding:20px 28px; background-color: ' . $btn_fallback . '; background-color:var(--stagekitwp-tk-btn) !important; 
            text-decoration:none !important; border-radius:var(--stagekitwp-tk-radius); 
            gap:4px; text-align:center; line-height:1.3; 
            -webkit-transition:-webkit-transform .15s, filter .15s, box-shadow .15s;
            transition:transform .15s, filter .15s, box-shadow .15s; 
            color:#ffffff !important; 
        }
        .stagekitwp-ticket-btn:hover { -webkit-filter:brightness(1.12); filter:brightness(1.12); -webkit-transform:translateY(-2px); transform:translateY(-2px); box-shadow:0 6px 18px rgba(0,0,0,.18); }
        .stagekitwp-ticket-btn-label { display:block !important; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1.2px; opacity:.82; color:inherit !important; }
        .stagekitwp-ticket-btn-title { display:block !important; font-size:20px; font-weight:700; color:inherit !important; }
        .stagekitwp-ticket-btn-dates { display:block !important; font-size:12px; font-weight:400; opacity:.72; color:inherit !important; }

        /* Cards */
        .stagekitwp-tickets-cards { display:-webkit-flex !important; display:flex !important; -webkit-flex-wrap:wrap; flex-wrap:wrap; gap:20px; }
        .stagekitwp-ticket-card { 
            -webkit-flex:1 1 220px; flex:1 1 220px; 
            min-width:200px; max-width:320px; 
            background-color:var(--stagekitwp-tk-bg); 
            border:1px solid var(--stagekitwp-tk-border); 
            border-radius:var(--stagekitwp-tk-radius); 
            box-shadow:var(--stagekitwp-tk-shadow); 
            overflow:hidden; 
            display:-webkit-flex !important; display:flex !important; 
            -webkit-flex-direction:column !important; flex-direction:column !important; 
            text-decoration:none !important; color:var(--stagekitwp-tk-text) !important; 
            -webkit-transition:-webkit-transform .2s, box-shadow .2s;
            transition:transform .2s, box-shadow .2s; 
        }
        .stagekitwp-ticket-card:hover { -webkit-transform:translateY(-3px); transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.14); }
        .stagekitwp-ticket-card-img-link { display:block; width:100%; text-decoration:none; }
        .stagekitwp-ticket-card-img { width:100%; aspect-ratio:2/3; -webkit-object-fit:cover; object-fit:cover; display:block !important; }
        .stagekitwp-ticket-card-img-ph { 
            width:100%; aspect-ratio:2/3; 
            background:rgba(0,0,0,0.05); 
            background:color-mix(in srgb,var(--stagekitwp-tk-btn) 13%,transparent); 
            display:-webkit-flex !important; display:flex !important; 
            -webkit-align-items:center; align-items:center; 
            -webkit-justify-content:center; justify-content:center; font-size:36px; 
        }
        .stagekitwp-ticket-card-body { 
            padding:16px; 
            display:-webkit-flex !important; display:flex !important; 
            -webkit-flex-direction:column !important; flex-direction:column !important; 
            gap:6px; -webkit-flex:1 0 auto; flex:1 0 auto; min-height:0; 
        }
        .stagekitwp-ticket-card-label { display:block !important; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--stagekitwp-tk-btn) !important; }
        .stagekitwp-ticket-card-title { display:block !important; font-size:16px; font-weight:700; line-height:1.3; color:var(--stagekitwp-tk-text) !important; }
        .stagekitwp-ticket-card-meta { display:block !important; font-size:12px; opacity:.68; color:var(--stagekitwp-tk-text) !important; }
        .stagekitwp-ticket-card-genre { 
            display:inline-block; font-size:11px; padding:2px 8px; border-radius:99px; 
            background:rgba(0,0,0,0.05); 
            background:color-mix(in srgb,var(--stagekitwp-tk-btn) 13%,transparent); 
            color:var(--stagekitwp-tk-btn); font-weight:600; 
        }
        .stagekitwp-ticket-card-footer { padding:0 16px 16px; margin-top:auto; display:flex; flex-direction:column; gap:8px; }
        .stagekitwp-ticket-card-cta { display:block !important; text-align:center; padding:10px 16px; border-radius:var(--stagekitwp-tk-radius); background-color: ' . $btn_fallback . '; background-color:var(--stagekitwp-tk-btn) !important; color:#fff !important; font-weight:700; font-size:13px; text-decoration:none !important; -webkit-transition:filter .2s; transition:filter .2s; }
        .stagekitwp-ticket-card-cta:hover { -webkit-filter:brightness(1.12); filter:brightness(1.12); }

        /* Table */
        .stagekitwp-tickets-table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
        .stagekitwp-tickets-table { width:100%; border-collapse:collapse; font-size:14px; }
        .stagekitwp-tickets-table thead th { text-align:left; padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; border-bottom:2px solid var(--stagekitwp-tk-border); color:var(--stagekitwp-tk-btn) !important; white-space:nowrap; }
        .stagekitwp-tickets-table tbody td { padding:12px 14px; border-bottom:1px solid var(--stagekitwp-tk-border); vertical-align:middle; color:var(--stagekitwp-tk-text); }
        .stagekitwp-tickets-table tbody tr:last-child td { border-bottom:none; }
        .stagekitwp-tickets-table tbody tr:hover td { 
            background:rgba(0,0,0,0.03); 
            background:color-mix(in srgb,var(--stagekitwp-tk-btn) 6%,transparent); 
        }
        .stagekitwp-ticket-table-name { font-weight:600; color:var(--stagekitwp-tk-text) !important; }
        .stagekitwp-ticket-table-tag { 
            display:inline-block; font-size:11px; padding:2px 8px; border-radius:99px; 
            background:rgba(0,0,0,0.05); 
            background:color-mix(in srgb,var(--stagekitwp-tk-btn) 13%,transparent); 
            color:var(--stagekitwp-tk-btn) !important; font-weight:600; white-space:nowrap; vertical-align:middle; margin-left:4px; 
        }
        .stagekitwp-ticket-table-badge { display:inline-block; font-size:11px; padding:2px 8px; border-radius:99px; background:#f0f0f0; color:#555; white-space:nowrap; vertical-align:middle; margin-left:4px; }
        .stagekitwp-ticket-season-row td { 
            background:rgba(0,0,0,0.04) !important; 
            background:color-mix(in srgb,var(--stagekitwp-tk-btn) 7%,transparent) !important; 
            font-weight:600; 
        }
        .stagekitwp-ticket-table-link { display:inline-block; padding:6px 16px; border-radius:var(--stagekitwp-tk-radius); background-color: ' . $btn_fallback . '; background-color:var(--stagekitwp-tk-btn) !important; color:#fff !important; font-weight:700; font-size:12px; text-decoration:none !important; white-space:nowrap; -webkit-transition:filter .2s; transition:filter .2s; }
        .stagekitwp-ticket-table-link:hover { -webkit-filter:brightness(1.12); filter:brightness(1.12); }

        /* Minimal */
        .stagekitwp-tickets-minimal { list-style:none !important; margin:0 !important; padding:0 !important; }
        .stagekitwp-tickets-minimal li { border-bottom:1px solid var(--stagekitwp-tk-border); }
        .stagekitwp-tickets-minimal li:first-child { border-top:1px solid var(--stagekitwp-tk-border); }
        .stagekitwp-ticket-min-row { 
            display:-webkit-flex !important; display:flex !important; 
            -webkit-align-items:center; align-items:center; 
            -webkit-justify-content:space-between; justify-content:space-between; 
            gap:16px; padding:16px 4px; text-decoration:none !important; color:var(--stagekitwp-tk-text) !important; 
            -webkit-transition:opacity .15s; transition:opacity .15s; 
        }
        .stagekitwp-ticket-min-row:hover { opacity:.8; }
        .stagekitwp-ticket-min-info { display:-webkit-flex !important; display:flex !important; -webkit-flex-direction:column !important; flex-direction:column !important; gap:3px; }
        .stagekitwp-ticket-min-type { display:block !important; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--stagekitwp-tk-btn) !important; }
        .stagekitwp-ticket-min-title { display:block !important; font-size:17px; font-weight:700; color:var(--stagekitwp-tk-text) !important; }
        .stagekitwp-ticket-min-dates { display:block !important; font-size:12px; opacity:.65; color:var(--stagekitwp-tk-text) !important; }
        .stagekitwp-ticket-min-arrow { 
            -webkit-flex-shrink:0; flex-shrink:0; width:28px; height:28px; 
            border-radius:50%; background-color: ' . $btn_fallback . '; background-color:var(--stagekitwp-tk-btn) !important; 
            display:-webkit-flex !important; display:flex !important; 
            -webkit-align-items:center; align-items:center; 
            -webkit-justify-content:center; justify-content:center; 
            -webkit-transition:-webkit-transform .2s; transition:transform .2s; 
        }
        .stagekitwp-ticket-min-row:hover .stagekitwp-ticket-min-arrow { -webkit-transform:translateX(3px); transform:translateX(3px); }

        /* Spotlight Layout */
        .stagekitwp-tickets-spotlight { display:-webkit-flex !important; display:flex !important; -webkit-flex-direction:column !important; flex-direction:column !important; gap:20px; }
        .stagekitwp-ticket-spotlight-hero { 
            position:relative; border-radius:var(--stagekitwp-tk-radius); overflow:hidden; min-height:280px; 
            display:-webkit-flex !important; display:flex !important; 
            -webkit-align-items:flex-end; align-items:flex-end; 
            text-decoration:none !important; color:#fff !important; 
        }
        .stagekitwp-ticket-spotlight-hero--color { background-color: ' . $btn_fallback . '; background-color:var(--stagekitwp-tk-btn) !important; }
        .stagekitwp-ticket-spotlight-hero-label,
        .stagekitwp-ticket-spotlight-hero-title,
        .stagekitwp-ticket-spotlight-hero-dates { color:#fff !important; }
        .stagekitwp-ticket-spotlight-hero-dates { display:block; font-size:13px; opacity:.8; margin-bottom:12px; }
        .stagekitwp-ticket-spotlight-hero-img { 
            position:absolute; top:0; left:0; width:100%; height:100%; 
            -webkit-object-fit:cover; object-fit:cover; display:block !important; 
        }
        .stagekitwp-ticket-spotlight-hero-overlay { position:absolute; top:0; left:0; right:0; bottom:0; background:-webkit-linear-gradient(bottom, rgba(0,0,0,.75) 0%, rgba(0,0,0,.1) 60%, transparent 100%); background:linear-gradient(to top, rgba(0,0,0,.75) 0%, rgba(0,0,0,.1) 60%, transparent 100%); }
        .stagekitwp-ticket-spotlight-hero-body { position:relative; z-index:1; padding:28px; width:100%; -webkit-box-sizing:border-box; box-sizing:border-box; }
        .stagekitwp-ticket-spotlight-hero-label { display:block !important; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1.2px; opacity:.85; margin-bottom:6px; }
        .stagekitwp-ticket-spotlight-hero-title { display:block !important; font-size:26px; font-weight:800; line-height:1.2; margin-bottom:14px; }
        .stagekitwp-ticket-spotlight-cta { display:inline-block; padding:10px 22px; border-radius:var(--stagekitwp-tk-radius); background:#fff; color:var(--stagekitwp-tk-btn) !important; font-weight:700; font-size:14px; text-decoration:none !important; -webkit-transition:opacity .2s; transition:opacity .2s; }
        .stagekitwp-ticket-spotlight-cta:hover { opacity:.88; }
        
        .stagekitwp-ticket-spotlight-grid { 
            display:grid !important; 
            grid-template-columns:repeat(var(--stagekitwp-spotlight-count,3),1fr); 
            gap:16px; 
            width:100%; 
            align-items: stretch !important;
        }
        
.stagekitwp-ticket-spotlight-tile-wrap { 
    display: -webkit-flex !important; 
    display: flex !important; 
    -webkit-flex-direction: column !important; 
    flex-direction: column !important; 
    -webkit-justify-content: space-between !important; 
    justify-content: space-between !important;
    min-width: 0; 
    width: 100%;
    position: relative !important;
    min-height: 100% !important; /* Changed from height: 100% */
    height: auto !important;     /* Allows container to expand if content needs space */
}        
        .stagekitwp-ticket-spotlight-tile { 
            display:-webkit-flex !important; display:flex !important; 
            -webkit-flex-direction:column !important; flex-direction:column !important; 
            border:1px solid var(--stagekitwp-tk-border); 
            border-radius:8px; 
            overflow:visible !important; 
            text-decoration:none !important; 
            color:var(--stagekitwp-tk-text) !important; 
            -webkit-transition:box-shadow .2s, -webkit-transform .2s; transition:box-shadow .2s, transform .2s; 
            background:var(--stagekitwp-tk-bg); 
            height:100% !important;
            box-sizing:border-box !important;
            position:relative !important;
            z-index:1 !important;
        }
        .stagekitwp-ticket-spotlight-tile:hover { -webkit-transform:translateY(-3px); transform:translateY(-3px); box-shadow:0 6px 20px rgba(0,0,0,.12); }
        .stagekitwp-ticket-spotlight-tile-img-wrap { 
            display:block; 
            width:100%; 
            aspect-ratio:4/3; 
            overflow:hidden; 
            border-top-left-radius:7px;
            border-top-right-radius:7px;
            -webkit-flex-shrink:0; flex-shrink:0; 
            background:var(--stagekitwp-tk-border,#eee); 
            text-decoration:none !important; 
        }
        .stagekitwp-ticket-spotlight-tile-img { width:100%; height:100%; -webkit-object-fit:cover; object-fit:cover; display:block; -webkit-transition:-webkit-transform .3s ease; transition:transform .3s ease; }
        .stagekitwp-ticket-spotlight-tile:hover .stagekitwp-ticket-spotlight-tile-img { -webkit-transform:scale(1.04); transform:scale(1.04); }
        .stagekitwp-ticket-spotlight-tile-img-ph { 
            display:-webkit-flex !important; display:flex !important; 
            -webkit-align-items:center; align-items:center; 
            -webkit-justify-content:center; justify-content:center; 
            font-size:40px; color:var(--stagekitwp-tk-btn); 
            background:rgba(0,0,0,0.05); 
            background:color-mix(in srgb,var(--stagekitwp-tk-btn) 10%,transparent); 
        }
        
        .stagekitwp-ticket-spotlight-tile-body { 
            display:-webkit-flex !important; display:flex !important; 
            -webkit-flex-direction:column !important; flex-direction:column !important; 
            padding:16px; 
            -webkit-flex:1 1 auto !important; flex:1 1 auto !important; 
            box-sizing:border-box !important;
            -webkit-justify-content:space-between !important;
            justify-content:space-between !important;
            height:100% !important;
        }
        .stagekitwp-ticket-spotlight-tile-label { display:block !important; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--stagekitwp-tk-btn) !important; margin-bottom:4px !important; }
        .stagekitwp-ticket-spotlight-tile-title { display:block !important; font-size:15px; font-weight:700; line-height:1.3; color:var(--stagekitwp-tk-text) !important; text-decoration:none !important; margin-bottom:4px !important; }
        .stagekitwp-ticket-spotlight-tile-dates { display:block !important; font-size:11px; opacity:.68; margin-bottom:6px !important; }
        
        /* Container for CTAs inside tile body */
        .stagekitwp-ticket-spotlight-actions {
            display: -webkit-flex !important;
            display: flex !important;
            -webkit-flex-direction: column !important;
            flex-direction: column !important;
            gap: 8px !important;
            margin-top: 16px !important;
            width: 100% !important;
            -webkit-flex-shrink: 0 !important;
            flex-shrink: 0 !important;
            position: relative !important;
            z-index: 5 !important;
        }

        /* Strict WebKit CTA Button Rule */
        .stagekitwp-ticket-spotlight-tile-cta { 
            -webkit-appearance: none !important;
            appearance: none !important;
            display: block !important; 
            visibility: visible !important;
            opacity: 1 !important;
            min-height: 36px !important;
            line-height: 1.2 !important;
            box-sizing: border-box !important;
            -webkit-flex-shrink: 0 !important;
            flex-shrink: 0 !important;
            padding: 10px 16px !important; 
            border-radius: 5px !important; 
            font-size: 12px !important; 
            font-weight: 700 !important; 
            text-align: center !important; 
            background-color: ' . $btn_fallback . ' !important;
            background-color: var(--stagekitwp-tk-btn) !important; 
            color: #ffffff !important; 
            text-decoration: none !important; 
            -webkit-transition: filter .2s; 
            transition: filter .2s; 
            position: relative !important;
            z-index: 5 !important;
            width: 100% !important;
        }
        .stagekitwp-ticket-spotlight-tile-cta span {
            color: #ffffff !important;
            display: inline-block !important;
        }
        .stagekitwp-ticket-spotlight-tile-cta:hover { -webkit-filter:brightness(1.14); filter:brightness(1.14); }

        @media (max-width:700px) {
            .stagekitwp-ticket-spotlight-grid { grid-template-columns:repeat(2,1fr) !important; gap:12px; }
        }
        @media (max-width:600px) {
            .stagekitwp-ticket-btn { padding:16px 20px; }
            .stagekitwp-ticket-btn-title { font-size:17px; }
            .stagekitwp-tickets-cards { -webkit-flex-direction:column; flex-direction:column; }
            .stagekitwp-ticket-card { max-width:100%; }
            .stagekitwp-ticket-spotlight-hero-title { font-size:20px; }
        }
        @media (max-width:400px) {
            .stagekitwp-ticket-spotlight-grid { grid-template-columns:1fr !important; }
        }
    ' );
}

// ---------------------------------------------------------------------------
// Minimal & Banner renderers
// ---------------------------------------------------------------------------

function stagekitwp_tickets_render_minimal( $season, $season_name, $season_url, $season_img, $season_banner, $season_start, $season_end, $shows, $show_image, $show_dates, $show_genre, $label_season, $label_show, $button_text, $btn, $btn_hover, $bg, $text, $border, $radius, $shadow, $font ) {
    $arrow     = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
    $arrow_btn = '<span class="stagekitwp-ticket-min-arrow">' . $arrow . '</span>';
    $out       = '<div class="stagekitwp-tickets-block"><ul class="stagekitwp-tickets-minimal">';
    if ( ! empty( $season_url ) ) {
        $out .= '<li><a href="' . esc_url($season_url) . '" class="stagekitwp-ticket-min-row" target="_blank" rel="noopener">';
        $out .= '<span class="stagekitwp-ticket-min-info"><span class="stagekitwp-ticket-min-type">' . esc_html($label_season) . '</span><span class="stagekitwp-ticket-min-title">' . esc_html($season_name) . '</span></span>';
        $out .= $arrow_btn . '</a></li>';
    }
    foreach ( $shows as $show ) {
        $url = get_post_meta( $show->ID, '_stagekitwp_show_tickets_url', true );
        if ( empty($url) ) { continue; }
        $title    = get_the_title( $show->ID );
        $show_url = stagekitwp_show_page_url( $show->ID );
        $dates = $show_dates ? get_post_meta( $show->ID, '_stagekitwp_show_show_dates', true ) : '';
        $slot  = get_post_meta( $show->ID, '_stagekitwp_show_time_slot', true );
        $type_label = ! empty($slot) ? esc_html($slot) : esc_html($label_show);
        $out .= '<li class="stagekitwp-ticket-min-item">';
        $out .= '<a href="' . esc_url($url) . '" class="stagekitwp-ticket-min-row" target="_blank" rel="noopener">';
        $out .= '<span class="stagekitwp-ticket-min-info">';
        $out .= '<span class="stagekitwp-ticket-min-type">' . $type_label . '</span>';
        $out .= '<span class="stagekitwp-ticket-min-title">' . esc_html($title) . '</span>';
        if ( ! empty($dates) ) { $out .= '<span class="stagekitwp-ticket-min-dates">' . esc_html($dates) . '</span>'; }
        $out .= '</span>' . $arrow_btn . '</a>';
        if ( $show_url ) {
            $out .= '<a href="' . esc_url($show_url) . '" class="stagekitwp-ticket-info-link" aria-label="' . esc_attr('Get Info: ' . $title) . '">Get Info</a>';
        }
        $out .= '</li>';
    }
    $out .= '</ul></div>';
    return $out;
}

function stagekitwp_tickets_render_banner( $season, $season_name, $season_url, $season_img, $season_banner, $season_start, $season_end, $shows, $show_image, $show_dates, $show_genre, $label_season, $label_show, $button_text, $btn, $btn_hover, $bg, $text, $border, $radius, $shadow, $font ) {
    $out  = '<div class="stagekitwp-tickets-block">';
    if ( ! empty($season_url) ) {
        $out .= '<div class="stagekitwp-ticket-btn-wrap"><a href="' . esc_url($season_url) . '" class="stagekitwp-ticket-btn" target="_blank" rel="noopener">';
        $out .= '<span class="stagekitwp-ticket-btn-label">' . esc_html($label_season) . '</span><span class="stagekitwp-ticket-btn-title">' . esc_html($season_name) . '</span></a></div>';
    }
    foreach ( $shows as $show ) {
        $url = get_post_meta( $show->ID, '_stagekitwp_show_tickets_url', true );
        if ( empty($url) ) { continue; }
        $title    = get_the_title( $show->ID );
        $show_url = stagekitwp_show_page_url( $show->ID );
        $dates = $show_dates ? get_post_meta( $show->ID, '_stagekitwp_show_show_dates', true ) : '';
        $out .= '<div class="stagekitwp-ticket-btn-wrap">';
        $out .= '<a href="' . esc_url($url) . '" class="stagekitwp-ticket-btn" target="_blank" rel="noopener">';
        $out .= '<span class="stagekitwp-ticket-btn-label">' . esc_html($label_show) . '</span><span class="stagekitwp-ticket-btn-title">' . esc_html($title) . '</span>';
        if ( ! empty($dates) ) { $out .= '<span class="stagekitwp-ticket-btn-dates">' . esc_html($dates) . '</span>'; }
        $out .= '</a>';
        if ( $show_url ) {
            $out .= '<a href="' . esc_url($show_url) . '" class="stagekitwp-ticket-info-link" aria-label="' . esc_attr('Get Info: ' . $title) . '">Get Info</a>';
        }
        $out .= '</div>';
    }
    $out .= '</div>';
    return $out;
}

// ---------------------------------------------------------------------------
// Cards & Table renderers
// ---------------------------------------------------------------------------

function stagekitwp_tickets_render_cards( $season, $season_name, $season_url, $season_img, $season_banner, $season_start, $season_end, $shows, $show_image, $show_dates, $show_genre, $label_season, $label_show, $button_text, $btn, $btn_hover, $bg, $text, $border, $radius, $shadow, $font ) {
    $out = '<div class="stagekitwp-tickets-block"><div class="stagekitwp-tickets-cards">';
    if ( ! empty($season_url) ) {
        $img_url = $show_image ? stagekitwp_tickets_resolve_image( $season_img, 'medium' ) : false;
        $out .= '<a href="' . esc_url($season_url) . '" class="stagekitwp-ticket-card" target="_blank" rel="noopener">';
        $out .= $img_url ? '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($season_name) . '" class="stagekitwp-ticket-card-img">' : '<div class="stagekitwp-ticket-card-img-ph">&#127917;</div>';
        $out .= '<div class="stagekitwp-ticket-card-body"><span class="stagekitwp-ticket-card-label">' . esc_html($label_season) . '</span><span class="stagekitwp-ticket-card-title">' . esc_html($season_name) . '</span>';
        if ( $show_dates && ( ! empty($season_start) || ! empty($season_end) ) ) {
            $d = array_filter( array( stagekitwp_tickets_format_date($season_start), stagekitwp_tickets_format_date($season_end) ) );
            $out .= '<span class="stagekitwp-ticket-card-meta">' . esc_html( implode(' – ',$d) ) . '</span>';
        }
        $out .= '</div><div class="stagekitwp-ticket-card-footer"><span class="stagekitwp-ticket-card-cta">' . esc_html($button_text) . '</span></div></a>';
    }
    foreach ( $shows as $show ) {
        $url = get_post_meta( $show->ID, '_stagekitwp_show_tickets_url', true );
        if ( empty($url) ) { continue; }
        $title    = get_the_title( $show->ID );
        $show_url = stagekitwp_show_page_url( $show->ID );
        $img_val = get_post_meta( $show->ID, '_stagekitwp_show_sm_image', true );
        $img_url = ( $show_image && ! empty($img_val) ) ? stagekitwp_tickets_resolve_image( $img_val, 'medium' ) : false;
        $dates   = $show_dates ? get_post_meta( $show->ID, '_stagekitwp_show_show_dates', true ) : '';
        $genre   = $show_genre ? get_post_meta( $show->ID, '_stagekitwp_show_genre',      true ) : '';

        $out .= '<div class="stagekitwp-ticket-card">';
        $poster = $img_url ? '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($title) . '" class="stagekitwp-ticket-card-img">' : '<div class="stagekitwp-ticket-card-img-ph">&#127917;</div>';
        $out .= '<a href="' . esc_url($url) . '" target="_blank" rel="noopener" class="stagekitwp-ticket-card-img-link" tabindex="-1" aria-hidden="true">' . $poster . '</a>';
        $out .= '<div class="stagekitwp-ticket-card-body"><span class="stagekitwp-ticket-card-label">' . esc_html($label_show) . '</span><span class="stagekitwp-ticket-card-title">' . esc_html($title) . '</span>';
        if ( ! empty($dates) ) { $out .= '<span class="stagekitwp-ticket-card-meta">' . esc_html($dates) . '</span>'; }
        if ( ! empty($genre) ) { $out .= '<span class="stagekitwp-ticket-card-genre">' . esc_html($genre) . '</span>'; }
        $out .= '</div>';
        $out .= '<div class="stagekitwp-ticket-card-footer">';
        $out .= '<a href="' . esc_url($url) . '" class="stagekitwp-ticket-card-cta" target="_blank" rel="noopener">' . esc_html($button_text) . '</a>';
        if ( $show_url ) {
            $out .= '<a href="' . esc_url($show_url) . '" class="stagekitwp-ticket-info-link" aria-label="' . esc_attr('Get Info: ' . $title) . '">Get Info</a>';
        }
        $out .= '</div>';
        $out .= '</div>';
    }
    $out .= '</div></div>';
    return $out;
}

function stagekitwp_tickets_render_table( $season, $season_name, $season_url, $season_img, $season_banner, $season_start, $season_end, $shows, $show_image, $show_dates, $show_genre, $label_season, $label_show, $button_text, $btn, $btn_hover, $bg, $text, $border, $radius, $shadow, $font ) {
    $out  = '<div class="stagekitwp-tickets-block"><div class="stagekitwp-tickets-table-wrap">';
    $out .= '<table class="stagekitwp-tickets-table"><thead><tr>';
    $out .= '<th>' . esc_html__('Production','stagekitwp-core') . '</th>';
    if ($show_dates) { $out .= '<th>' . esc_html__('Dates','stagekitwp-core') . '</th>'; }
    if ($show_genre) { $out .= '<th>' . esc_html__('Genre','stagekitwp-core') . '</th>'; }
    $out .= '<th>' . esc_html__('Tickets','stagekitwp-core') . '</th></tr></thead><tbody>';
    if ( ! empty($season_url) ) {
        $out .= '<tr class="stagekitwp-ticket-season-row"><td class="stagekitwp-ticket-table-name">' . esc_html($season_name) . ' <span class="stagekitwp-ticket-table-tag">' . esc_html($label_season) . '</span></td>';
        if ($show_dates) { $out .= '<td>—</td>'; }
        if ($show_genre) { $out .= '<td>—</td>'; }
        $out .= '<td><a href="' . esc_url($season_url) . '" class="stagekitwp-ticket-table-link" target="_blank" rel="noopener">' . esc_html($button_text) . '</a></td></tr>';
    }
    foreach ( $shows as $show ) {
        $url = get_post_meta( $show->ID, '_stagekitwp_show_tickets_url', true );
        if ( empty($url) ) { continue; }
        $title    = get_the_title( $show->ID );
        $show_url = stagekitwp_show_page_url( $show->ID );
        $dates = $show_dates ? get_post_meta( $show->ID, '_stagekitwp_show_show_dates', true ) : '';
        $genre = $show_genre ? get_post_meta( $show->ID, '_stagekitwp_show_genre', true ) : '';
        $slot  = get_post_meta( $show->ID, '_stagekitwp_show_time_slot', true );

        $out .= '<tr><td class="stagekitwp-ticket-table-name">' . esc_html($title);
        if (!empty($slot)) { $out .= ' <span class="stagekitwp-ticket-table-badge">' . esc_html($slot) . '</span>'; }
        $out .= '</td>';
        if ($show_dates) { $out .= '<td>' . (!empty($dates) ? esc_html($dates) : '—') . '</td>'; }
        if ($show_genre) { $out .= '<td>' . (!empty($genre) ? '<span class="stagekitwp-ticket-table-tag">' . esc_html($genre) . '</span>' : '—') . '</td>'; }
        $out .= '<td class="stagekitwp-ticket-table-actions">';
        $out .= '<a href="' . esc_url($url) . '" class="stagekitwp-ticket-table-link" target="_blank" rel="noopener">' . esc_html($button_text) . '</a>';
        if ( $show_url ) {
            $out .= '<a href="' . esc_url($show_url) . '" class="stagekitwp-ticket-info-link">Get Info</a>';
        }
        $out .= '</td></tr>';
    }
    $out .= '</tbody></table></div></div>';
    return $out;
}

// ---------------------------------------------------------------------------
// Spotlight renderer
// ---------------------------------------------------------------------------

function stagekitwp_tickets_render_spotlight( $season, $season_name, $season_url, $season_img, $season_banner, $season_start, $season_end, $shows, $show_image, $show_dates, $show_genre, $label_season, $label_show, $button_text, $btn, $btn_hover, $bg, $text, $border, $radius, $shadow, $font ) {
    $hero_img   = $show_image ? ( stagekitwp_tickets_resolve_image($season_banner,'large') ?: stagekitwp_tickets_resolve_image($season_img,'large') ) : false;
    $d          = array_filter( array( stagekitwp_tickets_format_date($season_start), stagekitwp_tickets_format_date($season_end) ) );
    $date_range = implode(' – ', $d);

    $hero_class = $hero_img ? 'stagekitwp-ticket-spotlight-hero' : 'stagekitwp-ticket-spotlight-hero stagekitwp-ticket-spotlight-hero--color';

    $out = '<div class="stagekitwp-tickets-block"><div class="stagekitwp-tickets-spotlight">';

    // Hero card
    $hero_tag   = ! empty($season_url) ? 'a href="' . esc_url($season_url) . '" target="_blank" rel="noopener"' : 'div';
    $hero_close = ! empty($season_url) ? 'a' : 'div';
    $out .= '<' . $hero_tag . ' class="' . esc_attr($hero_class) . '">';
    if ( $hero_img ) {
        $out .= '<img src="' . esc_url($hero_img) . '" alt="' . esc_attr($season_name) . '" class="stagekitwp-ticket-spotlight-hero-img">';
        $out .= '<div class="stagekitwp-ticket-spotlight-hero-overlay"></div>';
    }
    $out .= '<div class="stagekitwp-ticket-spotlight-hero-body">';
    $out .= '<span class="stagekitwp-ticket-spotlight-hero-label">' . esc_html($label_season) . '</span>';
    $out .= '<span class="stagekitwp-ticket-spotlight-hero-title">' . esc_html($season_name) . '</span>';
    if ( ! empty($date_range) ) { $out .= '<span class="stagekitwp-ticket-spotlight-hero-dates">' . esc_html($date_range) . '</span>'; }
    if ( ! empty($season_url) ) {
        $out .= '<span class="stagekitwp-ticket-spotlight-cta">' . esc_html('Get Season Tickets') . '</span>';
    }
    $out .= '</div></' . $hero_close . '>';

    // Show tiles
    $tiles = array_filter( $shows, function($show) {
        return ! empty( get_post_meta($show->ID, '_stagekitwp_show_tickets_url', true) );
    });
    if ( ! empty($tiles) ) {
        $count = count( $tiles );
        $out .= '<div class="stagekitwp-ticket-spotlight-grid" style="--stagekitwp-spotlight-count:' . $count . '">';
        foreach ( $tiles as $show ) {
            $url      = get_post_meta( $show->ID, '_stagekitwp_show_tickets_url', true );
            $title    = get_the_title( $show->ID );
            $show_url = stagekitwp_show_page_url( $show->ID );
            $img_val  = get_post_meta( $show->ID, '_stagekitwp_show_sm_image', true );
            $img_url  = ( $show_image && ! empty($img_val) ) ? stagekitwp_tickets_resolve_image($img_val,'medium') : false;
            $dates    = $show_dates ? get_post_meta( $show->ID, '_stagekitwp_show_show_dates', true ) : '';
            $genre    = $show_genre ? get_post_meta( $show->ID, '_stagekitwp_show_genre', true ) : '';
            $slot     = get_post_meta( $show->ID, '_stagekitwp_show_time_slot', true );
            $lbl      = ! empty($slot) ? $slot . ' · ' . $label_show : $label_show;

            // Tile wrapper
            $out .= '<div class="stagekitwp-ticket-spotlight-tile-wrap">';
            $out .= '<div class="stagekitwp-ticket-spotlight-tile">';
            
            // Poster image
            if ( $img_url ) {
                $out .= '<a href="' . esc_url($url) . '" class="stagekitwp-ticket-spotlight-tile-img-wrap" target="_blank" rel="noopener"><img src="' . esc_url($img_url) . '" alt="' . esc_attr($title) . '" class="stagekitwp-ticket-spotlight-tile-img" loading="lazy"></a>';
            } else {
                $out .= '<a href="' . esc_url($url) . '" class="stagekitwp-ticket-spotlight-tile-img-wrap stagekitwp-ticket-spotlight-tile-img-ph" target="_blank" rel="noopener">&#127917;</a>';
            }
            
            // Text body
            $out .= '<div class="stagekitwp-ticket-spotlight-tile-body">';
            $out .= '<div>';
            $out .= '<span class="stagekitwp-ticket-spotlight-tile-label">' . esc_html($lbl) . '</span>';
            $out .= '<a href="' . esc_url($url) . '" class="stagekitwp-ticket-spotlight-tile-title" target="_blank" rel="noopener">' . esc_html($title) . '</a>';
            if ( ! empty($dates) ) { $out .= '<span class="stagekitwp-ticket-spotlight-tile-dates">' . esc_html($dates) . '</span>'; }
            if ( ! empty($genre) ) { $out .= '<span class="stagekitwp-ticket-card-genre">' . esc_html($genre) . '</span>'; }
            $out .= '</div>';
            
            // Shared Action Buttons Container
            $out .= '<div class="stagekitwp-ticket-spotlight-actions">';
            $out .= '<a href="' . esc_url($url) . '" class="stagekitwp-ticket-spotlight-tile-cta" target="_blank" rel="noopener"><span>' . esc_html($button_text) . '</span></a>';
            if ( $show_url ) {
                $out .= '<a href="' . esc_url($show_url) . '" class="stagekitwp-ticket-info-link" aria-label="' . esc_attr('Get Info: ' . $title) . '">Get Info</a>';
            }
            $out .= '</div>'; // .stagekitwp-ticket-spotlight-actions

            $out .= '</div>'; // .stagekitwp-ticket-spotlight-tile-body
            $out .= '</div>'; // .stagekitwp-ticket-spotlight-tile
            $out .= '</div>'; // .stagekitwp-ticket-spotlight-tile-wrap
        }
        $out .= '</div>';
    }
    $out .= '</div></div>';
    return $out;
}