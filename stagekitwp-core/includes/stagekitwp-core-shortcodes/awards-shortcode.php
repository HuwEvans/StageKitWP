<?php
/**
 * Shortcode: [stagekitwp_awards]
 * Layouts: table (default) | cards | list | showcase
 * Attrs: layout, season_id, category, show_season, show_category, show_recipient, winners_only
 */
defined( 'ABSPATH' ) || exit;

function stagekitwp_awards_css( $bg, $text, $border, $bw, $radius, $shadow, $font, $h2c, $h3c ) {
    $sv = $shadow ? '0 2px 8px rgba(0,0,0,.18)' : 'none';
    return "
.stagekitwp-awards-block{--stagekitwp-aw-bg:{$bg};--stagekitwp-aw-text:{$text};--stagekitwp-aw-border:{$border};--stagekitwp-aw-radius:{$radius}px;--stagekitwp-aw-shadow:{$sv};--stagekitwp-aw-font:{$font};--stagekitwp-aw-h2:{$h2c};--stagekitwp-aw-h3:{$h3c};font-family:var(--stagekitwp-aw-font);color:var(--stagekitwp-aw-text)}
.stagekitwp-aw-season{margin-bottom:32px}
.stagekitwp-aw-season-title{font-size:1.25em;font-weight:700;color:var(--stagekitwp-aw-h2)!important;margin:0 0 8px;padding-bottom:8px;border-bottom:2px solid var(--stagekitwp-aw-border)}
.stagekitwp-aw-cat-title{font-size:.9em;font-weight:600;color:var(--stagekitwp-aw-h3)!important;margin:16px 0 8px;text-transform:uppercase;letter-spacing:.06em}
.stagekitwp-aw-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
.stagekitwp-aw-table{width:100%;border-collapse:collapse;min-width:360px;font-family:var(--stagekitwp-aw-font)}
.stagekitwp-aw-table th{background:var(--stagekitwp-aw-border);color:var(--stagekitwp-aw-h3)!important;padding:9px 12px;text-align:left;font-size:.8em;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap}
.stagekitwp-aw-table td{background:var(--stagekitwp-aw-bg);color:var(--stagekitwp-aw-text)!important;padding:9px 12px;border-bottom:1px solid var(--stagekitwp-aw-border);vertical-align:middle;font-size:.92em}
.stagekitwp-aw-table tr:last-child td{border-bottom:none}
.stagekitwp-aw-table tr.stagekitwp-aw-winner td{background:rgba(255,200,0,.07)}
.stagekitwp-aw-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:18px;margin-top:10px}
.stagekitwp-aw-card{background:var(--stagekitwp-aw-bg);color:var(--stagekitwp-aw-text);border:{$bw}px solid var(--stagekitwp-aw-border);border-radius:var(--stagekitwp-aw-radius);box-shadow:var(--stagekitwp-aw-shadow);padding:16px;display:flex;flex-direction:column;gap:5px}
.stagekitwp-aw-card.stagekitwp-aw-winner-card{border-color:rgba(200,160,0,.55);box-shadow:0 0 0 2px rgba(200,160,0,.2),var(--stagekitwp-aw-shadow)}
.stagekitwp-aw-card-icon{font-size:1.5em;line-height:1;margin-bottom:2px}
.stagekitwp-aw-card-award,.stagekitwp-aw-card-recipient,.stagekitwp-aw-card-show{color:var(--stagekitwp-aw-text)!important;margin:0}
.stagekitwp-aw-card-award{font-weight:700;font-size:.95em}
.stagekitwp-aw-card-recipient{font-size:.87em;opacity:.8}
.stagekitwp-aw-card-show{font-size:.8em;opacity:.6}
.stagekitwp-aw-list{display:flex;flex-direction:column}
.stagekitwp-aw-list-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--stagekitwp-aw-border);flex-wrap:wrap}
.stagekitwp-aw-list-row:last-child{border-bottom:none}
.stagekitwp-aw-list-award,.stagekitwp-aw-list-recipient,.stagekitwp-aw-list-show{color:var(--stagekitwp-aw-text)!important}
.stagekitwp-aw-list-award{flex:1 1 150px;font-weight:600;font-size:.92em}
.stagekitwp-aw-list-recipient{flex:1 1 110px;font-size:.87em;opacity:.8}
.stagekitwp-aw-list-show{flex:1 1 90px;font-size:.82em;opacity:.6}
.stagekitwp-aw-showcase-winners{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:16px;margin-bottom:20px}
.stagekitwp-aw-showcase-card{background:var(--stagekitwp-aw-bg);border:{$bw}px solid rgba(200,160,0,.45);border-radius:var(--stagekitwp-aw-radius);box-shadow:0 0 0 2px rgba(200,160,0,.12),var(--stagekitwp-aw-shadow);padding:16px;display:flex;flex-direction:column;gap:4px}
.stagekitwp-aw-showcase-trophy{font-size:1.8em;line-height:1}
.stagekitwp-aw-showcase-award,.stagekitwp-aw-showcase-recipient,.stagekitwp-aw-showcase-show{color:var(--stagekitwp-aw-text)!important;margin:0}
.stagekitwp-aw-showcase-award{font-weight:700;font-size:.92em}
.stagekitwp-aw-showcase-recipient{font-size:.86em;opacity:.8}
.stagekitwp-aw-showcase-show{font-size:.79em;opacity:.6}
.stagekitwp-aw-showcase-noms-title{font-size:.8em;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--stagekitwp-aw-h3)!important;margin:0 0 6px;opacity:.75}
.stagekitwp-aw-badge{display:inline-block;padding:2px 8px;border-radius:12px;font-size:.75em;font-weight:700;letter-spacing:.04em;white-space:nowrap;flex-shrink:0}
.stagekitwp-aw-badge-winner{background:rgba(200,160,0,.18);color:#6b5200;border:1px solid rgba(200,160,0,.4)}
.stagekitwp-aw-badge-nom{background:rgba(100,100,100,.1);color:var(--stagekitwp-aw-text);border:1px solid var(--stagekitwp-aw-border);opacity:.85}
.stagekitwp-aw-badge-not-eligible{background:rgba(215,40,40,.08);color:#9e2a2a;border:1px solid rgba(215,40,40,.3);opacity:.8}
html.stagekitwp-dark-mode .stagekitwp-aw-badge-winner{color:#f0c040;background:rgba(200,160,0,.15);border-color:rgba(200,160,0,.35)}
html.stagekitwp-dark-mode .stagekitwp-aw-badge-not-eligible{color:#ff6b6b;background:rgba(215,40,40,.12);border-color:rgba(215,40,40,.4)}
@media(max-width:600px){.stagekitwp-aw-list-show{display:none}.stagekitwp-aw-cards,.stagekitwp-aw-showcase-winners{grid-template-columns:1fr!important}}
";
}

function stagekitwp_awards_badge( $status ) {
    if ( 'THEA Winner' === $status ) {
        return '<span class="stagekitwp-aw-badge stagekitwp-aw-badge-winner">&#11088; Winner</span>';
    } elseif ( 'Not Eligible' === $status ) {
        return '<span class="stagekitwp-aw-badge stagekitwp-aw-badge-not-eligible">&#10060; Not Eligible</span>';
    }
    return '<span class="stagekitwp-aw-badge stagekitwp-aw-badge-nom">Nominated</span>';
}

function stagekitwp_awards_render_table( $sd, $sc, $show_recipient ) {
    foreach ( $sd['categories'] as $cat => $awards ) {
        if ( $sc ) { echo '<h3 class="stagekitwp-aw-cat-title">' . esc_html( $cat ) . '</h3>'; }
        echo '<div class="stagekitwp-aw-table-wrap"><table class="stagekitwp-aw-table"><thead><tr><th>Status</th><th>Award</th>';
        if ( $show_recipient ) {
            echo '<th>Recipient</th>';
        }
        echo '<th>Show</th></tr></thead><tbody>';
        foreach ( $awards as $aw ) {
            $w = ( 'THEA Winner' === $aw['status'] );
            echo '<tr class="' . ( $w ? 'stagekitwp-aw-winner' : '' ) . '">';
            $show_url = ( ! empty($aw['show_id']) ) ? stagekitwp_show_page_url( $aw['show_id'] ) : '';
            $show_cell = $show_url ? '<a href="'.esc_url($show_url).'" style="color:inherit;">'.esc_html($aw['show_title']).'</a>' : esc_html($aw['show_title']);
            echo '<td>' . stagekitwp_awards_badge( $aw['status'] ) . '</td><td>' . esc_html( $aw['award_name'] ) . '</td>';
            if ( $show_recipient ) {
                echo '<td>' . esc_html( $aw['recipient'] ) . '</td>';
            }
            echo '<td>' . $show_cell . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
}

function stagekitwp_awards_render_cards( $sd, $sc, $show_recipient ) {
    foreach ( $sd['categories'] as $cat => $awards ) {
        if ( $sc ) { echo '<h3 class="stagekitwp-aw-cat-title">' . esc_html( $cat ) . '</h3>'; }
        echo '<div class="stagekitwp-aw-cards">';
        foreach ( $awards as $aw ) {
            $w = ( 'THEA Winner' === $aw['status'] );
            echo '<div class="stagekitwp-aw-card' . ( $w ? ' stagekitwp-aw-winner-card' : '' ) . '">';
            echo '<div class="stagekitwp-aw-card-icon">' . ( $w ? '&#127942;' : '&#127917;' ) . '</div>';
            echo '<p class="stagekitwp-aw-card-award">' . esc_html( $aw['award_name'] ) . '</p>';
            if ( $show_recipient ) {
                echo '<p class="stagekitwp-aw-card-recipient">' . esc_html( $aw['recipient'] ) . '</p>';
            }
            if ( $aw['show_title'] ) {
                $show_url = ( ! empty($aw['show_id']) ) ? stagekitwp_show_page_url( $aw['show_id'] ) : '';
                $show_txt = $show_url ? '<a href="'.esc_url($show_url).'" style="color:inherit;">'.esc_html($aw['show_title']).'</a>' : esc_html($aw['show_title']);
                echo '<p class="stagekitwp-aw-card-show">' . $show_txt . '</p>';
            }
            echo stagekitwp_awards_badge( $aw['status'] ) . '</div>';
        }
        echo '</div>';
    }
}

function stagekitwp_awards_render_list( $sd, $sc, $show_recipient ) {
    foreach ( $sd['categories'] as $cat => $awards ) {
        if ( $sc ) { echo '<h3 class="stagekitwp-aw-cat-title">' . esc_html( $cat ) . '</h3>'; }
        echo '<div class="stagekitwp-aw-list">';
        foreach ( $awards as $aw ) {
            echo '<div class="stagekitwp-aw-list-row">' . stagekitwp_awards_badge( $aw['status'] );
            echo '<span class="stagekitwp-aw-list-award">' . esc_html( $aw['award_name'] ) . '</span>';
            if ( $show_recipient ) {
                echo '<span class="stagekitwp-aw-list-recipient">' . esc_html( $aw['recipient'] ) . '</span>';
            }
            if ( $aw['show_title'] ) {
                $show_url = ( ! empty($aw['show_id']) ) ? stagekitwp_show_page_url( $aw['show_id'] ) : '';
                $show_txt = $show_url ? '<a href="'.esc_url($show_url).'" style="color:inherit;">'.esc_html($aw['show_title']).'</a>' : esc_html($aw['show_title']);
                echo '<span class="stagekitwp-aw-list-show">' . $show_txt . '</span>';
            }
            echo '</div>';
        }
        echo '</div>';
    }
}

function stagekitwp_awards_render_showcase( $sd, $sc, $show_recipient ) {
    $winners = array_values( array_filter( $sd['awards'], function( $a ) { return $a['status'] === 'THEA Winner'; } ) );
    $non_winners = array_values( array_filter( $sd['awards'], function( $a ) { return $a['status'] !== 'THEA Winner'; } ) );
    
    if ( ! empty( $winners ) ) {
        echo '<div class="stagekitwp-aw-showcase-winners">';
        foreach ( $winners as $aw ) {
            echo '<div class="stagekitwp-aw-showcase-card"><div class="stagekitwp-aw-showcase-trophy">&#127942;</div>';
            if ( $sc ) { echo '<p class="stagekitwp-aw-cat-title" style="margin:0 0 4px;font-size:.75em">' . esc_html( $aw['category'] ) . '</p>'; }
            echo '<p class="stagekitwp-aw-showcase-award">' . esc_html( $aw['award_name'] ) . '</p>';
            if ( $show_recipient ) {
                echo '<p class="stagekitwp-aw-showcase-recipient">' . esc_html( $aw['recipient'] ) . '</p>';
            }
            if ( $aw['show_title'] ) {
                $show_url = ( ! empty($aw['show_id']) ) ? stagekitwp_show_page_url( $aw['show_id'] ) : '';
                $show_txt = $show_url ? '<a href="'.esc_url($show_url).'" style="color:inherit;">'.esc_html($aw['show_title']).'</a>' : esc_html($aw['show_title']);
                echo '<p class="stagekitwp-aw-showcase-show">' . $show_txt . '</p>';
            }
            echo '</div>';
        }
        echo '</div>';
    }
    
    if ( ! empty( $non_winners ) ) {
        echo '<p class="stagekitwp-aw-showcase-noms-title">Nominations & Statuses</p><div class="stagekitwp-aw-list">';
        foreach ( $non_winners as $aw ) {
            echo '<div class="stagekitwp-aw-list-row">';
            echo stagekitwp_awards_badge( $aw['status'] );
            echo '<span class="stagekitwp-aw-list-award">' . esc_html( $aw['award_name'] ) . '</span>';
            if ( $show_recipient ) {
                echo '<span class="stagekitwp-aw-list-recipient">' . esc_html( $aw['recipient'] ) . '</span>';
            }
            if ( $aw['show_title'] ) {
                $show_url = ( ! empty($aw['show_id']) ) ? stagekitwp_show_page_url( $aw['show_id'] ) : '';
                $show_txt = $show_url ? '<a href="'.esc_url($show_url).'" style="color:inherit;">'.esc_html($aw['show_title']).'</a>' : esc_html($aw['show_title']);
                echo '<span class="stagekitwp-aw-list-show">' . $show_txt . '</span>';
            }
            echo '</div>';
        }
        echo '</div>';
    }
}

function stagekitwp_shortcode_awards( $atts ) {
    $bg   = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_awards_bg_color', '' ), '#ffffff' );
    $text = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_awards_text_color', '' ), '#1a1a1a' );
    $brd  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_awards_border_color', '' ), '#dddddd' );
    $bw   = absint( get_option( 'stagekitwp_awards_border_width', '1' ) );
    $rnd  = (bool) get_option( 'stagekitwp_awards_rounded', false );
    $bdr  = $rnd ? absint( get_option( 'stagekitwp_awards_radius', '8' ) ) : 0;
    $shd  = (bool) get_option( 'stagekitwp_awards_shadow', false );
    $fnt  = sanitize_text_field( get_option( 'stagekitwp_awards_base_font', 'inherit' ) );
    $h2c  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_awards_h2_color', '' ), '#1a1a1a' );
    $h3c  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_awards_h3_color', '' ), '#444444' );

    $atts = shortcode_atts( array(
        'layout'        => 'table',
        'season_id'     => '',
        'category'      => '',
        'show_season'   => 'true',
        'show_category' => 'true',
        'show_recipient'=> 'true',
        'winners_only'  => 'false',
    ), $atts, 'stagekitwp_awards' );

    $layout   = in_array( $atts['layout'], array('table','cards','list','showcase'), true ) ? $atts['layout'] : 'table';
    $flt_s    = absint( $atts['season_id'] );
    $flt_c    = sanitize_text_field( $atts['category'] );
    $show_s   = ( 'false' !== $atts['show_season'] );
    $show_c   = ( 'false' !== $atts['show_category'] );
    $show_r   = ( 'false' !== $atts['show_recipient'] );
    $win_only = ( 'true'  === $atts['winners_only'] );

    stagekitwp_add_shortcode_inline_style( 'awards', stagekitwp_awards_css( $bg,$text,$brd,$bw,$bdr,$shd,$fnt,$h2c,$h3c ) );

    if ( function_exists( 'stagekitwp_dark_mode_admin_enabled' ) && stagekitwp_dark_mode_admin_enabled() ) {
        $d_bg  = stagekitwp_sanitize_css_color( (string) get_option( 'stagekitwp_awards_bg_color_dark',     '' ) );
        $d_tx  = stagekitwp_sanitize_css_color( (string) get_option( 'stagekitwp_awards_text_color_dark',   '' ) );
        $d_bd  = stagekitwp_sanitize_css_color( (string) get_option( 'stagekitwp_awards_border_color_dark', '' ) );
        $d_h2  = stagekitwp_sanitize_css_color( (string) get_option( 'stagekitwp_awards_h2_color_dark',     '' ) );
        $d_h3  = stagekitwp_sanitize_css_color( (string) get_option( 'stagekitwp_awards_h3_color_dark',     '' ) );
        
        $aw_valid_dark = function( $col ) {
            if ( ! $col ) { return ''; }
            $hex = ltrim( $col, '#' );
            if ( strlen( $hex ) === 3 ) { $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
            if ( strlen( $hex ) !== 6 ) { return ''; }
            $lum = 0.2126*hexdec(substr($hex,0,2))/255 + 0.7152*hexdec(substr($hex,2,2))/255 + 0.0722*hexdec(substr($hex,4,2))/255;
            return ( $lum >= 0.04 ) ? $col : '';
        };
        $d_bg = $aw_valid_dark( $d_bg );
        $d_tx = $aw_valid_dark( $d_tx );
        $d_bd = $aw_valid_dark( $d_bd );
        $d_h2 = $aw_valid_dark( $d_h2 );
        $d_h3 = $aw_valid_dark( $d_h3 );
        if ( $d_bg || $d_tx || $d_bd || $d_h2 || $d_h3 ) {
            $dc = "html.stagekitwp-dark-mode .stagekitwp-awards-block{\n";
            if ( $d_bg ) { $dc .= "  --stagekitwp-aw-bg:{$d_bg}!important;\n"; }
            if ( $d_tx ) { $dc .= "  --stagekitwp-aw-text:{$d_tx}!important;\n"; }
            if ( $d_bd ) { $dc .= "  --stagekitwp-aw-border:{$d_bd}!important;\n"; }
            if ( $d_h2 ) { $dc .= "  --stagekitwp-aw-h2:{$d_h2}!important;\n"; }
            if ( $d_h3 ) { $dc .= "  --stagekitwp-aw-h3:{$d_h3}!important;\n"; }
            $dc .= "}\n";
            stagekitwp_add_shortcode_inline_style( 'awards-dark', $dc );
        }
    }

    $ids = get_posts( array( 'post_type'=>'award','posts_per_page'=>-1,'orderby'=>'title','order'=>'ASC','fields'=>'ids' ) );
    if ( empty( $ids ) ) { return '<p class="stagekitwp-awards-empty">No awards found.</p>'; }

    $data = array();
    foreach ( $ids as $aid ) {
        $sid = intval( get_post_meta( $aid, '_stagekitwp_award_show_id',   true ) );
        $cat = sanitize_text_field( get_post_meta( $aid, '_stagekitwp_award_category', true ) );
        $anm = sanitize_text_field( get_post_meta( $aid, '_stagekitwp_award_name',     true ) );
        $rec = sanitize_text_field( get_post_meta( $aid, '_stagekitwp_award_recipient',true ) );
        $sta = sanitize_text_field( get_post_meta( $aid, '_stagekitwp_award_status',   true ) );
        $stl = $sid ? get_the_title( $sid ) : '';
        $sea = $sid ? intval( get_post_meta( $sid, '_stagekitwp_show_season', true ) ) : 0;
        if ( $flt_s && $sea !== $flt_s ) { continue; }
        if ( $flt_c && strcasecmp( $cat, $flt_c ) !== 0 ) { continue; }
        if ( $win_only && $sta !== 'THEA Winner' ) { continue; }
        $data[] = array( 'aid'=>$aid,'show_id'=>$sid,'show_title'=>$stl,'season_id'=>$sea,'category'=>$cat,'award_name'=>$anm,'recipient'=>$rec,'status'=>$sta );
    }
    if ( empty( $data ) ) { return '<p class="stagekitwp-awards-empty">No awards found matching your criteria.</p>'; }

    usort( $data, function( $a, $b ) {
        $wa = ( $a['status']==='THEA Winner' ) ? 0 : 1;
        $wb = ( $b['status']==='THEA Winner' ) ? 0 : 1;
        return ( $wa !== $wb ) ? $wa-$wb : strcasecmp( $a['award_name'], $b['award_name'] );
    } );

    $seasons = array();
    foreach ( $data as $aw ) {
        $sid = $aw['season_id'];
        if ( ! isset( $seasons[$sid] ) ) {
            $st = $sid ? get_post_meta( $sid, '_stagekitwp_season_start_date', true ) : '';
            $seasons[$sid] = array( 'title'=>$sid?get_the_title($sid):'Unassigned','start_date'=>$st,'awards'=>array(),'categories'=>array() );
        }
        $seasons[$sid]['awards'][] = $aw;
        if ( ! isset( $seasons[$sid]['categories'][$aw['category']] ) ) { $seasons[$sid]['categories'][$aw['category']] = array(); }
        $seasons[$sid]['categories'][$aw['category']][] = $aw;
    }
    uasort( $seasons, function($a,$b){ $da=$a['start_date']?strtotime($a['start_date']):0; $db=$b['start_date']?strtotime($b['start_date']):0; return $db-$da; } );

    ob_start();
    echo '<div class="stagekitwp-awards-block">';
    foreach ( $seasons as $sd ) {
        echo '<div class="stagekitwp-aw-season">';
        if ( $show_s ) { echo '<h2 class="stagekitwp-aw-season-title">' . esc_html( $sd['title'] ) . '</h2>'; }
        switch ( $layout ) {
            case 'cards':    stagekitwp_awards_render_cards(    $sd, $show_c, $show_r ); break;
            case 'list':     stagekitwp_awards_render_list(     $sd, $show_c, $show_r ); break;
            case 'showcase': stagekitwp_awards_render_showcase( $sd, $show_c, $show_r ); break;
            default:         stagekitwp_awards_render_table(    $sd, $show_c, $show_r ); break;
        }
        echo '</div>';
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode( 'stagekitwp_awards', 'stagekitwp_shortcode_awards' );