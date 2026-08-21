<?php
/**
 * Shortcode: [stagekitwp_board_members]
 *
 * Layouts: grid (default), list, table, spotlight
 *
 * Attributes:
 *   layout      grid | list | table | spotlight  (default: grid)
 *   columns     int 1–6                          (default: Display Options or 3)
 *   show_photos true | false                     (default: true)
 *   show_bio    true | false                     (default: false)
 *   photo_size  int 60-220                       (grid only, default: Display Options or 120)
 *   circle_photos true | false                   (default: true; applies to layouts with rounded photos)
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Helper: resolve photo URL from stored value (ID or URL)
// ---------------------------------------------------------------------------
function stagekitwp_bm_photo_url( $value, $size = 'medium' ) {
    if ( empty( $value ) ) { return ''; }
    if ( is_numeric( $value ) ) {
        $src = wp_get_attachment_image_src( intval( $value ), $size );
        return $src ? $src[0] : wp_get_attachment_url( intval( $value ) );
    }
    return esc_url( $value );
}

// ---------------------------------------------------------------------------
// CSS generator — all colours via CSS custom properties
// ---------------------------------------------------------------------------
function stagekitwp_board_members_css( $bg, $text, $border, $border_w, $radius, $shadow, $font, $grid_photo_size, $photo_radius ) {
    $shadow_val = $shadow ? '0 2px 8px rgba(0,0,0,.18)' : 'none';
    return "
.stagekitwp-board-members-block{--stagekitwp-bm-bg:{$bg};--stagekitwp-bm-text:{$text};--stagekitwp-bm-border:{$border};--stagekitwp-bm-radius:{$radius}px;--stagekitwp-bm-shadow:{$shadow_val};--stagekitwp-bm-font:{$font};--stagekitwp-bm-grid-photo:{$grid_photo_size}px;--stagekitwp-bm-photo-radius:{$photo_radius};font-family:var(--stagekitwp-bm-font);color:var(--stagekitwp-bm-text)}
.stagekitwp-bm-grid{display:grid;grid-template-columns:repeat(var(--stagekitwp-bm-cols,3),1fr);gap:24px}
.stagekitwp-bm-card{background:var(--stagekitwp-bm-bg);color:var(--stagekitwp-bm-text);border:{$border_w}px solid var(--stagekitwp-bm-border);border-radius:var(--stagekitwp-bm-radius);box-shadow:var(--stagekitwp-bm-shadow);padding:20px 16px;display:flex;flex-direction:column;align-items:center;text-align:center;gap:8px}
.stagekitwp-bm-card img{width:var(--stagekitwp-bm-grid-photo);height:var(--stagekitwp-bm-grid-photo);object-fit:cover;border-radius:var(--stagekitwp-bm-photo-radius);margin-bottom:8px;border:2px solid var(--stagekitwp-bm-border)}
.stagekitwp-bm-card .stagekitwp-bm-name,.stagekitwp-bm-card .stagekitwp-bm-position{color:var(--stagekitwp-bm-text);margin:0}
.stagekitwp-bm-card .stagekitwp-bm-name{font-size:1.05em;font-weight:700}
.stagekitwp-bm-card .stagekitwp-bm-position{font-size:.9em;opacity:.8}
.stagekitwp-bm-card .stagekitwp-bm-bio{font-size:.88em;color:var(--stagekitwp-bm-text)!important;margin-top:6px;text-align:left}
.stagekitwp-bm-card .stagekitwp-bm-bio p,.stagekitwp-bm-card .stagekitwp-bm-bio li,.stagekitwp-bm-card .stagekitwp-bm-bio span,.stagekitwp-bm-card .stagekitwp-bm-bio a{color:var(--stagekitwp-bm-text)!important}
.stagekitwp-bm-list{display:flex;flex-direction:column;gap:16px}
.stagekitwp-bm-list-row{background:var(--stagekitwp-bm-bg);color:var(--stagekitwp-bm-text);border:{$border_w}px solid var(--stagekitwp-bm-border);border-radius:var(--stagekitwp-bm-radius);box-shadow:var(--stagekitwp-bm-shadow);display:flex;align-items:center;gap:20px;padding:14px 18px}
.stagekitwp-bm-list-row img{width:64px;height:64px;object-fit:cover;border-radius:var(--stagekitwp-bm-photo-radius);flex-shrink:0;border:2px solid var(--stagekitwp-bm-border)}
.stagekitwp-bm-list-row .stagekitwp-bm-info{display:flex;flex-direction:column;gap:2px}
.stagekitwp-bm-list-row .stagekitwp-bm-name,.stagekitwp-bm-list-row .stagekitwp-bm-position{color:var(--stagekitwp-bm-text);margin:0}
.stagekitwp-bm-list-row .stagekitwp-bm-name{font-size:1em;font-weight:700}
.stagekitwp-bm-list-row .stagekitwp-bm-position{font-size:.88em;opacity:.75}
.stagekitwp-bm-list-row .stagekitwp-bm-bio{font-size:.85em;color:var(--stagekitwp-bm-text)!important;margin-top:4px}
.stagekitwp-bm-list-row .stagekitwp-bm-bio p,.stagekitwp-bm-list-row .stagekitwp-bm-bio li,.stagekitwp-bm-list-row .stagekitwp-bm-bio span,.stagekitwp-bm-list-row .stagekitwp-bm-bio a{color:var(--stagekitwp-bm-text)!important}
.stagekitwp-bm-table-wrap{overflow-x:auto}
.stagekitwp-bm-table{width:100%;border-collapse:collapse;font-family:var(--stagekitwp-bm-font);color:var(--stagekitwp-bm-text)}
.stagekitwp-bm-table th{background:var(--stagekitwp-bm-border);color:var(--stagekitwp-bm-text);padding:10px 14px;text-align:left;font-size:.85em;text-transform:uppercase;letter-spacing:.05em}
.stagekitwp-bm-table td{background:var(--stagekitwp-bm-bg);color:var(--stagekitwp-bm-text);padding:10px 14px;border-bottom:1px solid var(--stagekitwp-bm-border);vertical-align:middle}
.stagekitwp-bm-table tr:last-child td{border-bottom:none}
.stagekitwp-bm-table img{width:44px;height:44px;object-fit:cover;border-radius:var(--stagekitwp-bm-photo-radius);border:1px solid var(--stagekitwp-bm-border);display:block}
.stagekitwp-bm-spotlight{display:grid;grid-template-columns:repeat(var(--stagekitwp-bm-cols,3),1fr);gap:28px}
.stagekitwp-bm-spotlight-card{background:var(--stagekitwp-bm-bg);color:var(--stagekitwp-bm-text);border:{$border_w}px solid var(--stagekitwp-bm-border);border-radius:var(--stagekitwp-bm-radius);box-shadow:var(--stagekitwp-bm-shadow);overflow:hidden;display:flex;flex-direction:column}
.stagekitwp-bm-spotlight-card .stagekitwp-bm-sp-photo{width:100%;aspect-ratio:3/4;object-fit:cover;display:block}
.stagekitwp-bm-spotlight-card .stagekitwp-bm-sp-body{padding:16px;display:flex;flex-direction:column;gap:4px;flex:1}
.stagekitwp-bm-spotlight-card .stagekitwp-bm-name{font-size:1.05em;font-weight:700;color:var(--stagekitwp-bm-text);margin:0}
.stagekitwp-bm-spotlight-card .stagekitwp-bm-position{font-size:.85em;text-transform:uppercase;letter-spacing:.06em;opacity:.7;color:var(--stagekitwp-bm-text);margin:0}
.stagekitwp-bm-spotlight-card .stagekitwp-bm-bio{font-size:.88em;color:var(--stagekitwp-bm-text)!important;margin-top:8px}
.stagekitwp-bm-spotlight-card .stagekitwp-bm-bio p,.stagekitwp-bm-spotlight-card .stagekitwp-bm-bio li,.stagekitwp-bm-spotlight-card .stagekitwp-bm-bio span,.stagekitwp-bm-spotlight-card .stagekitwp-bm-bio a{color:var(--stagekitwp-bm-text)!important}
.stagekitwp-bm-spotlight-nophoto{width:100%;aspect-ratio:3/4;background:var(--stagekitwp-bm-border);display:flex;align-items:center;justify-content:center;font-size:3em;color:var(--stagekitwp-bm-text);opacity:.35}
@media(max-width:600px){.stagekitwp-bm-grid,.stagekitwp-bm-spotlight{grid-template-columns:1fr!important}}
@media(min-width:601px) and (max-width:900px){.stagekitwp-bm-grid,.stagekitwp-bm-spotlight{grid-template-columns:repeat(2,1fr)!important}}
";
}

// ---------------------------------------------------------------------------
// Shortcode handler
// ---------------------------------------------------------------------------
function stagekitwp_board_member_shortcode( $atts ) {

    $bg_color      = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_board_member_bg_color', '' ), '#ffffff' );
    $text_color    = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_board_member_text_color', '' ), '#1a1a1a' );
    $border_color  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_board_member_border_color', '' ), '#dddddd' );
    $border_width  = absint( get_option( 'stagekitwp_board_member_border_width', '1' ) );
    $rounded       = (bool) get_option( 'stagekitwp_board_member_rounded', false );
    $border_radius = $rounded ? absint( get_option( 'stagekitwp_board_member_radius', '12' ) ) : 0;
    $shadow        = (bool) get_option( 'stagekitwp_board_member_shadow', false );
    $grid_columns  = absint( get_option( 'stagekitwp_board_member_grid_columns', '3' ) ) ?: 3;
    $grid_photo    = absint( get_option( 'stagekitwp_board_member_photo_size', '120' ) ) ?: 120;
    $base_font     = sanitize_text_field( get_option( 'stagekitwp_board_member_base_font', 'inherit' ) );

    if ( $grid_photo < 60 || $grid_photo > 220 ) {
        $grid_photo = 120;
    }

    $atts = shortcode_atts( array(
        'layout'      => 'grid',
        'columns'     => $grid_columns,
        'show_photos' => 'true',
        'show_bio'    => 'false',
        'photo_size'  => '',
        'circle_photos' => 'true',
    ), $atts, 'stagekitwp_board_members' );

    $layout      = in_array( $atts['layout'], array( 'grid', 'list', 'table', 'spotlight' ), true )
                    ? $atts['layout'] : 'grid';
    $columns     = max( 1, min( 6, intval( $atts['columns'] ) ) );
    $show_photos = ( 'false' !== $atts['show_photos'] );
    $show_bio    = ( 'true'  === $atts['show_bio'] );
    $circle_photos = ( 'false' !== $atts['circle_photos'] );
    $photo_size  = absint( $atts['photo_size'] );
    if ( $photo_size < 60 || $photo_size > 220 ) {
        $photo_size = $grid_photo;
    }
    $photo_radius = $circle_photos ? '50%' : '8px';

    stagekitwp_add_shortcode_inline_style(
        'board-members',
        stagekitwp_board_members_css( $bg_color, $text_color, $border_color, $border_width, $border_radius, $shadow, $base_font, $photo_size, $photo_radius )
    );

    // Dark mode Display Options override
    if ( function_exists( 'stagekitwp_dark_mode_admin_enabled' ) && stagekitwp_dark_mode_admin_enabled() ) {
        $d_bg     = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_board_member_bg_color_dark', '' ), '' );
        $d_text   = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_board_member_text_color_dark', '' ), '' );
        $d_border = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_board_member_border_color_dark', '' ), '' );
        if ( $d_bg || $d_text || $d_border ) {
            $dark_css  = "html.stagekitwp-dark-mode .stagekitwp-board-members-block{\n";
            if ( $d_bg )     { $dark_css .= "  --stagekitwp-bm-bg:{$d_bg}!important;\n"; }
            if ( $d_text )   { $dark_css .= "  --stagekitwp-bm-text:{$d_text}!important;\n"; }
            if ( $d_border ) { $dark_css .= "  --stagekitwp-bm-border:{$d_border}!important;\n"; }
            $dark_css .= "}\n";
            stagekitwp_add_shortcode_inline_style( 'board-members-dark', $dark_css );
        }
    }

    // Query
    $query = new WP_Query( array(
        'post_type' => 'board_member', 'posts_per_page' => -1, 'post_status' => 'publish',
    ) );
    if ( ! $query->have_posts() ) {
        return '<p class="stagekitwp-board-members-empty">No board members found.</p>';
    }

    // Sort: priority roles first, then alphabetical by position
    $priority_roles = array( 'President', 'Vice-President', 'Vice President', 'Treasurer', 'Secretary' );
    usort( $query->posts, function ( $a, $b ) use ( $priority_roles ) {
        $pa = get_post_meta( $a->ID, '_stagekitwp_position', true );
        $pb = get_post_meta( $b->ID, '_stagekitwp_position', true );
        $ia = array_search( $pa, $priority_roles, true );
        $ib = array_search( $pb, $priority_roles, true );
        if ( false !== $ia && false !== $ib ) { return $ia - $ib; }
        if ( false !== $ia ) { return -1; }
        if ( false !== $ib ) { return 1; }
        return strcmp( $pa, $pb );
    } );

    ob_start();
    $wrapper_vars = sprintf(
        '--stagekitwp-bm-cols:%1$d;--stagekitwp-bm-grid-photo:%2$dpx;--stagekitwp-bm-photo-radius:%3$s;',
        $columns,
        $photo_size,
        esc_attr( $photo_radius )
    );
    echo '<div class="stagekitwp-board-members-block" style="' . $wrapper_vars . '">';
    switch ( $layout ) {
        case 'grid':
            echo '<div class="stagekitwp-bm-grid">';
            foreach ( $query->posts as $post ) {
                $name = esc_html( get_the_title( $post->ID ) );
                $pos  = esc_html( get_post_meta( $post->ID, '_stagekitwp_position', true ) );
                $bio  = wp_kses_post( get_post_meta( $post->ID, '_stagekitwp_bio', true ) );
                $photo = get_post_meta( $post->ID, '_stagekitwp_photo', true );
                echo '<div class="stagekitwp-bm-card">';
                if ( $show_photos && $photo ) {
                    $url = stagekitwp_bm_photo_url( $photo, 'medium' );
                    if ( $url ) { echo '<img src="' . esc_url( $url ) . '" alt="' . $name . '" loading="lazy" />'; }
                }
                echo '<p class="stagekitwp-bm-name">' . $name . '</p>';
                if ( $pos ) { echo '<p class="stagekitwp-bm-position">' . $pos . '</p>'; }
                if ( $show_bio && $bio ) { echo '<div class="stagekitwp-bm-bio">' . $bio . '</div>'; }
                echo '</div>';
            }
            echo '</div>';
            break;
        case 'list':
            echo '<div class="stagekitwp-bm-list">';
            foreach ( $query->posts as $post ) {
                $name = esc_html( get_the_title( $post->ID ) );
                $pos  = esc_html( get_post_meta( $post->ID, '_stagekitwp_position', true ) );
                $bio  = wp_kses_post( get_post_meta( $post->ID, '_stagekitwp_bio', true ) );
                $photo = get_post_meta( $post->ID, '_stagekitwp_photo', true );
                echo '<div class="stagekitwp-bm-list-row">';
                if ( $show_photos && $photo ) {
                    $url = stagekitwp_bm_photo_url( $photo, 'thumbnail' );
                    if ( $url ) { echo '<img src="' . esc_url( $url ) . '" alt="' . $name . '" loading="lazy" />'; }
                }
                echo '<div class="stagekitwp-bm-info">';
                echo '<p class="stagekitwp-bm-name">' . $name . '</p>';
                if ( $pos ) { echo '<p class="stagekitwp-bm-position">' . $pos . '</p>'; }
                if ( $show_bio && $bio ) { echo '<div class="stagekitwp-bm-bio">' . $bio . '</div>'; }
                echo '</div></div>';
            }
            echo '</div>';
            break;
        case 'table':
            echo '<div class="stagekitwp-bm-table-wrap"><table class="stagekitwp-bm-table"><thead><tr>';
            if ( $show_photos ) { echo '<th></th>'; }
            echo '<th>Name</th><th>Position</th>';
            if ( $show_bio ) { echo '<th>About</th>'; }
            echo '</tr></thead><tbody>';
            foreach ( $query->posts as $post ) {
                $name = esc_html( get_the_title( $post->ID ) );
                $pos  = esc_html( get_post_meta( $post->ID, '_stagekitwp_position', true ) );
                $bio  = wp_kses_post( get_post_meta( $post->ID, '_stagekitwp_bio', true ) );
                $photo = get_post_meta( $post->ID, '_stagekitwp_photo', true );
                echo '<tr>';
                if ( $show_photos ) {
                    echo '<td>';
                    if ( $photo ) { $url = stagekitwp_bm_photo_url( $photo, 'thumbnail' ); if ( $url ) { echo '<img src="' . esc_url( $url ) . '" alt="' . $name . '" loading="lazy" />'; } }
                    echo '</td>';
                }
                echo '<td><strong>' . $name . '</strong></td><td>' . $pos . '</td>';
                if ( $show_bio ) { echo '<td>' . $bio . '</td>'; }
                echo '</tr>';
            }
            echo '</tbody></table></div>';
            break;
        case 'spotlight':
            echo '<div class="stagekitwp-bm-spotlight">';
            foreach ( $query->posts as $post ) {
                $name = esc_html( get_the_title( $post->ID ) );
                $pos  = esc_html( get_post_meta( $post->ID, '_stagekitwp_position', true ) );
                $bio  = wp_kses_post( get_post_meta( $post->ID, '_stagekitwp_bio', true ) );
                $photo = get_post_meta( $post->ID, '_stagekitwp_photo', true );
                echo '<div class="stagekitwp-bm-spotlight-card">';
                if ( $show_photos ) {
                    if ( $photo ) {
                        $url = stagekitwp_bm_photo_url( $photo, 'large' );
                        if ( $url ) { echo '<img class="stagekitwp-bm-sp-photo" src="' . esc_url( $url ) . '" alt="' . $name . '" loading="lazy" />'; }
                        else { echo '<div class="stagekitwp-bm-spotlight-nophoto">&#128100;</div>'; }
                    } else { echo '<div class="stagekitwp-bm-spotlight-nophoto">&#128100;</div>'; }
                }
                echo '<div class="stagekitwp-bm-sp-body"><p class="stagekitwp-bm-name">' . $name . '</p>';
                if ( $pos ) { echo '<p class="stagekitwp-bm-position">' . $pos . '</p>'; }
                if ( $show_bio && $bio ) { echo '<div class="stagekitwp-bm-bio">' . $bio . '</div>'; }
                echo '</div></div>';
            }
            echo '</div>';
            break;
    }
    echo '</div>';
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode( 'stagekitwp_board_members', 'stagekitwp_board_member_shortcode' );
