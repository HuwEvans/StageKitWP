<?php
/**
 * Shortcode: [stagekitwp_sponsors]
 *
 * Displays sponsor entries grouped by level (grid layout) or as an
 * auto-scrolling logo carousel (slider layout — Swiper JS, loaded on demand).
 *
 * Attrs:
 *   layout         grid|slider  (default: grid)
 *   show_headers   true|false   (grid only, default: true)
 *   show_name      true|false   (grid only, default: true)
 *   show_company   true|false   (grid only, default: true)
 *   show_logo      true|false   (grid only, default: true)
 *   show_website   true|false   (grid only, default: true)
 *   logo_size      text         (grid only, optional: e.g. 60%, 180px, 180)
 *   diamond_label  text         (grid only, optional)
 *   platinum_label text         (grid only, optional)
 *   gold_label     text         (grid only, optional)
 *   silver_label   text         (grid only, optional)
 *   bronze_label   text         (grid only, optional)
 *   slides_visible integer      (slider only, default: 4)
 *   autoplay       true|false   (slider only, default: true)
 *   speed          integer ms   (slider only, default: 3000)
 */

defined( 'ABSPATH' ) || exit;

function stagekitwp_sponsor_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'layout'        => 'grid',
        'show_headers'  => 'true',
        'show_name'     => 'true',
        'show_company'  => 'true',
        'show_logo'     => 'true',
        'show_website'  => 'true',
        'logo_size'      => '',
        'diamond_label'  => '',
        'platinum_label' => '',
        'gold_label'     => '',
        'silver_label'   => '',
        'bronze_label'   => '',
        'slides_visible' => '4',
        'autoplay'      => 'true',
        'speed'         => '3000',
    ], $atts, 'stagekitwp_sponsors' );

    $layout = ( $atts['layout'] === 'slider' ) ? 'slider' : 'grid';

    // ── Display Options ───────────────────────────────────────────────────
    $bg_color     = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_sponsor_bg_color', '' ), '#ffffff' );
    $text_color   = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_sponsor_text_color', '' ), '#000000' );
    $border_color = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_sponsor_border_color', '' ), '#000000' );
    $border_width = absint( get_option( 'stagekitwp_sponsor_border_width', '0' ) );
    $rounded      = get_option( 'stagekitwp_sponsor_rounded' ) ? 'true' : 'false';
    $border_radius = absint( get_option( 'stagekitwp_sponsor_radius', '20' ) );
    $shadow       = get_option( 'stagekitwp_sponsor_shadow' ) ? 'true' : 'false';
    $base_font    = sanitize_text_field( get_option( 'stagekitwp_sponsor_base_font', 'Arial, sans-serif' ) );

    $card_style = "background-color:{$bg_color};color:{$text_color};font-family:{$base_font};border:{$border_width}px solid {$border_color};";
    if ( $rounded === 'true' ) { $card_style .= "border-radius:{$border_radius}px;"; }
    if ( $shadow  === 'true' ) { $card_style .= 'box-shadow:0 2px 6px rgba(0,0,0,0.2);'; }

    // ── Query ─────────────────────────────────────────────────────────────
    $query = new WP_Query( [
        'post_type'      => 'sponsor',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ] );

    if ( ! $query->have_posts() ) {
        return '<p>No sponsors found.</p>';
    }

    // ── SLIDER layout ─────────────────────────────────────────────────────
    if ( $layout === 'slider' ) {
        stagekitwp_enqueue_swiper_assets();

        $slides_visible = max( 1, intval( $atts['slides_visible'] ) );
        $autoplay_bool  = ( $atts['autoplay'] !== 'false' );
        $speed          = max( 500, intval( $atts['speed'] ) );

        $slides_html = '';
        while ( $query->have_posts() ) {
            $query->the_post();
            $banner  = get_post_meta( get_the_ID(), '_stagekitwp_banner',  true );
            $website = get_post_meta( get_the_ID(), '_stagekitwp_website', true );
            if ( ! $banner ) { continue; }
            $banner_url = stagekitwp_get_image_url( $banner );
            if ( ! $banner_url ) { continue; }
            $slides_html .= '<div class="swiper-slide stagekitwp-sp-slide">';
            if ( $website ) {
                $slides_html .= '<a href="' . esc_url( $website ) . '" target="_blank" rel="noopener">';
                $slides_html .= '<img src="' . esc_url( $banner_url ) . '" alt="' . esc_attr( get_the_title() ) . '">';
                $slides_html .= '</a>';
            } else {
                $slides_html .= '<img src="' . esc_url( $banner_url ) . '" alt="' . esc_attr( get_the_title() ) . '">';
            }
            $slides_html .= '</div>';
        }
        wp_reset_postdata();

        if ( ! $slides_html ) {
            return '<p>No sponsor banners found.</p>';
        }

        static $slider_id = 0;
        $slider_id++;
        $uid = 'stagekitwp-sp-slider-' . $slider_id;

        $autoplay_js = $autoplay_bool
            ? "autoplay:{ delay:{$speed}, disableOnInteraction:false },"
            : '';

        $output  = '<div id="' . esc_attr( $uid ) . '" class="swiper stagekitwp-sp-slider-wrap">';
        $output .= '<div class="swiper-wrapper">' . $slides_html . '</div>';
        $output .= '<div class="swiper-pagination"></div>';
        $output .= '</div>';

        $output .= '<style>
#' . esc_attr( $uid ) . '{width:100%;overflow:hidden;}
#' . esc_attr( $uid ) . ' .stagekitwp-sp-slide{text-align:center;padding:0 12px;box-sizing:border-box;}
#' . esc_attr( $uid ) . ' .stagekitwp-sp-slide img{max-width:100%;height:auto;max-height:80px;object-fit:contain;display:inline-block;}
</style>';

        $output .= '<script>
document.addEventListener("DOMContentLoaded",function(){
  if(typeof Swiper==="undefined"){return;}
  new Swiper("#' . esc_js( $uid ) . '",{
    slidesPerView:' . $slides_visible . ',
    spaceBetween:20,
    loop:true,
    ' . $autoplay_js . '
    pagination:{el:"#' . esc_js( $uid ) . ' .swiper-pagination",clickable:true},
    breakpoints:{
      0:{slidesPerView:1},
      480:{slidesPerView:Math.min(2,' . $slides_visible . ')},
      768:{slidesPerView:Math.min(3,' . $slides_visible . ')},
      1024:{slidesPerView:' . $slides_visible . '}
    }
  });
});
</script>';

        return $output;
    }

    // ── GRID layout ───────────────────────────────────────────────────────
    $levels = [ 'Diamond' => [], 'Platinum' => [], 'Gold' => [], 'Silver' => [], 'Bronze' => [] ];
    while ( $query->have_posts() ) {
        $query->the_post();
        $level = get_post_meta( get_the_ID(), '_stagekitwp_level', true ) ?: 'Bronze';
        if ( ! isset( $levels[ $level ] ) ) { $levels[ $level ] = []; }
        $levels[ $level ][] = [
            'title'   => get_the_title(),
            'company' => get_post_meta( get_the_ID(), '_stagekitwp_company', true ),
            'website' => get_post_meta( get_the_ID(), '_stagekitwp_website', true ),
            'logo'    => get_post_meta( get_the_ID(), '_stagekitwp_logo',    true ),
        ];
    }
    wp_reset_postdata();

    stagekitwp_add_shortcode_inline_style( 'sponsors', '
        .stagekitwp-sponsor-section h2{text-align:center;margin-bottom:20px;}
        .stagekitwp-sponsor-grid{display:flex;flex-wrap:wrap;gap:20px;margin-bottom:40px;justify-content:center;}
        .stagekitwp-sponsor-card{box-sizing:border-box;padding:5px;text-align:center;display:flex;flex-direction:column;align-items:center;flex:1 1 auto;}
        .stagekitwp-sponsor-card h3,.stagekitwp-sponsor-card h4,.stagekitwp-sponsor-card p,.stagekitwp-sponsor-card a{color:' . esc_attr( $text_color ) . ';margin:5px 0;}
        .stagekitwp-sponsor-card img{width:auto;height:auto;max-width:100%;margin-bottom:10px;}
        @media(max-width:767px){.diamond .stagekitwp-sponsor-card,.platinum .stagekitwp-sponsor-card,.gold .stagekitwp-sponsor-card,.silver .stagekitwp-sponsor-card,.bronze .stagekitwp-sponsor-card{flex:0 0 100%!important;}}
        @media(min-width:768px) and (max-width:1024px){.gold .stagekitwp-sponsor-card,.silver .stagekitwp-sponsor-card,.bronze .stagekitwp-sponsor-card{flex:0 0 calc(50% - 10px)!important;}}
        @media(min-width:1025px){.diamond .stagekitwp-sponsor-card{flex:0 0 100%;}.platinum .stagekitwp-sponsor-card{flex:0 0 75%;}.gold .stagekitwp-sponsor-card{flex:0 0 calc(50% - 10px);}.silver .stagekitwp-sponsor-card{flex:0 0 calc(33.333% - 13.33px);}.bronze .stagekitwp-sponsor-card{flex:0 0 calc(25% - 15px);}}
    ' );

    $show_name    = ( $atts['show_name']    !== 'false' );
    $show_company = ( $atts['show_company'] !== 'false' );
    $show_logo    = ( $atts['show_logo']    !== 'false' );
    $show_website = ( $atts['show_website'] !== 'false' );
    $show_headers = ( $atts['show_headers'] !== 'false' );
    $level_labels = stagekitwp_get_level_display_labels( 'sponsor', $atts );

    // Optional logo-size override: accepts 60%, 180px, or plain 180 (treated as px).
    $logo_size = trim( (string) $atts['logo_size'] );
    $logo_img_style = '';
    if ( $logo_size !== '' ) {
        if ( preg_match( '/^\d+(?:\.\d+)?%$/', $logo_size ) ) {
            $logo_img_style = ' style="width:' . esc_attr( $logo_size ) . ';height:auto;max-width:100%;"';
        } elseif ( preg_match( '/^\d+(?:\.\d+)?px$/i', $logo_size ) ) {
            $logo_img_style = ' style="width:' . esc_attr( strtolower( $logo_size ) ) . ';height:auto;max-width:100%;"';
        } elseif ( preg_match( '/^\d+(?:\.\d+)?$/', $logo_size ) ) {
            $logo_img_style = ' style="width:' . esc_attr( $logo_size ) . 'px;height:auto;max-width:100%;"';
        }
    }

    ob_start();
    foreach ( $levels as $level => $entries ) {
        if ( empty( $entries ) ) { continue; }
        $display_label = isset( $level_labels[ $level ] ) ? $level_labels[ $level ] : $level;
        echo '<div class="stagekitwp-sponsor-section">';
        if ( $show_headers ) {
            echo '<h2>' . esc_html( $display_label ) . ' Sponsors</h2>';
        }
        echo '<div class="stagekitwp-sponsor-grid ' . esc_attr( strtolower( $level ) ) . '">';
        foreach ( $entries as $entry ) {
            echo '<div class="stagekitwp-sponsor-card" style="' . esc_attr( $card_style ) . '">';
            if ( $show_logo && ! empty( $entry['logo'] ) ) {
                $logo_url = stagekitwp_get_image_url( $entry['logo'] );
                if ( $logo_url ) {
                    echo '<div>';
                    if ( $show_website && ! empty( $entry['website'] ) ) {
                        echo '<a href="' . esc_url( $entry['website'] ) . '" target="_blank" rel="noopener">';
                        echo '<img src="' . esc_url( $logo_url ) . '" alt="Logo"' . $logo_img_style . '>';
                        echo '</a>';
                    } else {
                        echo '<img src="' . esc_url( $logo_url ) . '" alt="Logo"' . $logo_img_style . '>';
                    }
                    echo '</div>';
                }
            }
            if ( $show_name    ) { echo '<h4>' . esc_html( $entry['title']   ) . '</h4>'; }
            if ( $show_company && $entry['company'] ) { echo '<h4>' . esc_html( $entry['company'] ) . '</h4>'; }
            if ( $show_website && $entry['website'] ) {
                echo '<p><a href="' . esc_url( $entry['website'] ) . '" target="_blank" rel="noopener">' . esc_html( $entry['website'] ) . '</a></p>';
            }
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }
    return ob_get_clean();
}
add_shortcode( 'stagekitwp_sponsors', 'stagekitwp_sponsor_shortcode' );

// Backward-compat alias so any existing [stagekitwp_sponsor_slider] usage keeps working.
add_shortcode( 'stagekitwp_sponsor_slider', function( $atts ) {
    $atts = (array) $atts;
    $atts['layout'] = 'slider';
    return stagekitwp_sponsor_shortcode( $atts );
} );
