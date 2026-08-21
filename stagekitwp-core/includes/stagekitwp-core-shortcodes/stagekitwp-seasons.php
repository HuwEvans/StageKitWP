<?php
/**
 * Shortcode: [stagekitwp_seasons]
 *
 * Layouts
 *   cards  (default) — season cards; each field individually togglable
 *   field  — returns raw/minimal HTML for exactly one named field of one season
 *
 * Attrs
 *   which        all|current|upcoming|next|past   default: all
 *   season_id    int — single season (overrides which)
 *   layout       cards|field                      default: cards
 *   order        ASC|DESC                         default: DESC
 *   orderby      start_date|end_date|title         default: start_date
 *   limit        int                              default: -1 (all)
 *
 *   Card field toggles (all default true):
 *     show_image, show_name, show_status, show_dates, show_tickets,
 *     show_description, show_image_back, show_social_banner,
 *     show_sm_square, show_sm_portrait
 *
 *   Field layout:
 *     field   name|start_date|end_date|dates_range|status|
 *             tickets_url|tickets_link|description|
 *             image_front|image_back|social_banner|sm_square|sm_portrait
 *             (images → <img>; tickets_link → <a>; dates_range → "Jan 1 – Dec 31")
 */
defined( 'ABSPATH' ) || exit;

// ── Helpers ───────────────────────────────────────────────────────────────────
if ( ! function_exists( 'stagekitwp_get_season_status_info' ) ) {
    function stagekitwp_get_season_status_info( $start_date, $end_date ) {
        if ( empty($start_date) || empty($end_date) ) {
            return array('status'=>'current','label'=>'Current','color'=>'#27ae60');
        }
        $now   = current_time('timestamp');
        $start = strtotime($start_date);
        $end   = strtotime($end_date) + 86399;
        if ( !$start || !$end ) { return array('status'=>'current','label'=>'Current','color'=>'#27ae60'); }
        if ( $now < $start )  { return array('status'=>'upcoming','label'=>'Upcoming','color'=>'#e67e22'); }
        if ( $now <= $end )   { return array('status'=>'current', 'label'=>'Current', 'color'=>'#27ae60'); }
        return array('status'=>'past','label'=>'Past','color'=>'#7f8c8d');
    }
}

if ( ! function_exists( 'stagekitwp_sort_seasons' ) ) {
    function stagekitwp_sort_seasons( $seasons, $orderby, $order ) {
        usort( $seasons, function($a,$b) use($orderby,$order) {
            switch($orderby) {
                case 'end_date':
                    $av=strtotime(get_post_meta($a->ID,'_stagekitwp_season_end_date',true))?:0;
                    $bv=strtotime(get_post_meta($b->ID,'_stagekitwp_season_end_date',true))?:0;
                    $r=$av-$bv; break;
                case 'title':
                    $r=strcmp($a->post_title,$b->post_title); break;
                default:
                    $av=strtotime(get_post_meta($a->ID,'_stagekitwp_season_start_date',true))?:0;
                    $bv=strtotime(get_post_meta($b->ID,'_stagekitwp_season_start_date',true))?:0;
                    $r=$av-$bv;
            }
            return 'DESC'===$order ? -$r : $r;
        });
        return $seasons;
    }
}

/** Resolve attachment ID or raw URL → URL string */
function stagekitwp_sn_img_url( $raw ) {
    if ( empty($raw) ) { return ''; }
    if ( is_numeric($raw) ) { return (string) wp_get_attachment_url( intval($raw) ); }
    return esc_url_raw($raw);
}

/** Return sorted, filtered seasons array */
function stagekitwp_sn_collect( $season_id, $which, $orderby, $order, $limit ) {
    if ( ! empty($season_id) ) {
        $s = get_post( intval($season_id) );
        return ( $s && 'season' === $s->post_type && 'publish' === $s->post_status ) ? array($s) : array();
    }
    $all = get_posts(array('post_type'=>'season','posts_per_page'=>-1,'post_status'=>'publish'));

    if ( 'all' !== $which ) {
        // Attach timestamps for next-detection
        foreach ( $all as $s ) {
            $s->_ts_start = ($v=get_post_meta($s->ID,'_stagekitwp_season_start_date',true))?strtotime($v):0;
            $s->_ts_end   = ($v=get_post_meta($s->ID,'_stagekitwp_season_end_date',  true))?strtotime($v)+86399:0;
        }
        usort($all,function($a,$b){return $a->_ts_start-$b->_ts_start;});
        if ( 'next' === $which ) {
            $now=current_time('timestamp'); $ci=null;
            foreach($all as $i=>$s){
                if($s->_ts_start&&$s->_ts_end&&$now>=$s->_ts_start&&$now<=$s->_ts_end){$ci=$i;break;}
            }
            if(null!==$ci){return isset($all[$ci+1])?array($all[$ci+1]):array();}
            foreach($all as $s){if($s->_ts_start>$now){return array($s);}}
            return array();
        }
        $all = array_values(array_filter($all,function($s) use($which){
            $st=get_post_meta($s->ID,'_stagekitwp_season_start_date',true);
            $en=get_post_meta($s->ID,'_stagekitwp_season_end_date',  true);
            return stagekitwp_get_season_status_info($st,$en)['status']===$which;
        }));
    }

    $all = stagekitwp_sort_seasons($all,$orderby,strtoupper($order));
    if ( $limit > 0 ) { $all = array_slice($all,0,$limit); }
    return $all;
}

// ── CSS ─────────────────────────────────────────────────────────────────────
function stagekitwp_seasons_css($bg,$text,$brd,$bw,$radius,$shadow,$font,$h2c,$h3c,$acc) {
    $sv  = $shadow ? '0 2px 10px rgba(0,0,0,.14)' : 'none';
    $sv2 = $shadow ? '0 1px 4px rgba(0,0,0,.10)'  : 'none';
    return "
.stagekitwp-seasons-block{--stagekitwp-sn-bg:{$bg};--stagekitwp-sn-text:{$text};--stagekitwp-sn-border:{$brd};--stagekitwp-sn-bw:{$bw}px;--stagekitwp-sn-radius:{$radius}px;--stagekitwp-sn-shadow:{$sv};--stagekitwp-sn-font:{$font};--stagekitwp-sn-h2:{$h2c};--stagekitwp-sn-h3:{$h3c};--stagekitwp-sn-acc:{$acc};--stagekitwp-sn-btn-bg:{$acc};--stagekitwp-sn-btn-text:#ffffff;font-family:var(--stagekitwp-sn-font);color:var(--stagekitwp-sn-text)}
.stagekitwp-seasons-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:22px}
/* Card */
.stagekitwp-sn-card{background:var(--stagekitwp-sn-bg);border:var(--stagekitwp-sn-bw) solid var(--stagekitwp-sn-border);border-radius:var(--stagekitwp-sn-radius);box-shadow:{$sv2};overflow:hidden;display:flex;flex-direction:column}
/* Hero image wrap: 16:9, contain so full poster is shown */
.stagekitwp-sn-img-wrap{width:100%;aspect-ratio:16/9;background:rgba(0,0,0,.05);display:flex;align-items:center;justify-content:center;overflow:hidden}
html.stagekitwp-dark-mode .stagekitwp-sn-img-wrap{background:rgba(255,255,255,.06)}
.stagekitwp-sn-card-img{width:100%;height:100%;object-fit:contain;display:block}
/* Card body */
.stagekitwp-sn-card-body{padding:14px 16px;display:flex;flex-direction:column;gap:7px;flex:1}
.stagekitwp-sn-card-status{display:inline-block;padding:2px 10px;border-radius:12px;font-size:.72em;font-weight:700;color:#fff!important;width:fit-content;line-height:1.6}
.stagekitwp-sn-card-name{font-size:1.08em;font-weight:700;color:var(--stagekitwp-sn-h2)!important;margin:0;line-height:1.3}
.stagekitwp-sn-card-dates{font-size:.85em;color:var(--stagekitwp-sn-text)!important;opacity:.78;margin:0}
.stagekitwp-sn-card-desc{font-size:.86em;color:var(--stagekitwp-sn-text)!important;line-height:1.55;opacity:.9;margin:0}
/* Thumbnail strip */
.stagekitwp-sn-thumb-strip{display:flex;gap:6px;flex-wrap:wrap;margin-top:2px}
.stagekitwp-sn-thumb{width:64px;height:64px;object-fit:cover;border-radius:4px;border:1px solid var(--stagekitwp-sn-border);opacity:.88}
.stagekitwp-sn-thumb:hover{opacity:1}
/* Ticket button */
.stagekitwp-sn-card-tickets{margin-top:auto;padding-top:10px}
.stagekitwp-sn-card-tickets a{display:inline-block;padding:7px 20px;background:var(--stagekitwp-sn-btn-bg);color:var(--stagekitwp-sn-btn-text)!important;border-radius:4px;text-decoration:none;font-size:.85em;font-weight:600;transition:opacity .15s}
.stagekitwp-sn-card-tickets a:hover{opacity:.85}
/* Dark mode button override (set via theme-integration or inline) */
html.stagekitwp-dark-mode .stagekitwp-seasons-block .stagekitwp-sn-card-tickets a{background:var(--stagekitwp-sn-btn-bg)!important;color:var(--stagekitwp-sn-btn-text)!important}
.stagekitwp-seasons-empty{color:var(--stagekitwp-sn-text);font-style:italic;padding:12px 0}
.stagekitwp-seasons-empty img,.stagekitwp-seasons-empty svg{opacity:1!important;filter:none!important}
@media(max-width:540px){.stagekitwp-seasons-grid{grid-template-columns:1fr}}
";}
// ── FIELD layout ────────────────────────────────────────────────────────────
function stagekitwp_sn_render_field( $season, $field ) {
    $sid  = $season->ID;
    $name = get_post_meta($sid,'_stagekitwp_season_name',true) ?: get_the_title($sid);
    $img_meta = array(
        'image_front'   => '_stagekitwp_season_image_front',
        'image_back'    => '_stagekitwp_season_image_back',
        'social_banner' => '_stagekitwp_season_social_banner',
        'sm_square'     => '_stagekitwp_season_sm_square',
        'sm_portrait'   => '_stagekitwp_season_sm_portrait',
    );
    switch ( $field ) {
        case 'name':
            return esc_html($name);
        case 'start_date':
            $v = get_post_meta($sid,'_stagekitwp_season_start_date',true);
            return $v ? esc_html(date_i18n('F j, Y',strtotime($v))) : '';
        case 'end_date':
            $v = get_post_meta($sid,'_stagekitwp_season_end_date',true);
            return $v ? esc_html(date_i18n('F j, Y',strtotime($v))) : '';
        case 'dates_range':
            $s=get_post_meta($sid,'_stagekitwp_season_start_date',true);
            $e=get_post_meta($sid,'_stagekitwp_season_end_date',  true);
            $sd=$s?date_i18n('F j, Y',strtotime($s)):'';
            $ed=$e?date_i18n('F j, Y',strtotime($e)):'';
            if($sd&&$ed){ return esc_html($sd).' &ndash; '.esc_html($ed); }
            return esc_html($sd.$ed);
        case 'status':
            $s=get_post_meta($sid,'_stagekitwp_season_start_date',true);
            $e=get_post_meta($sid,'_stagekitwp_season_end_date',  true);
            $info=stagekitwp_get_season_status_info($s,$e);
            return '<span class="stagekitwp-sn-card-status" style="background:'.esc_attr($info['color']).'">'.
                   esc_html($info['label']).'</span>';
        case 'description':
            $v = get_post_field('post_content',$sid);
            return $v ? wpautop(wp_kses_post($v)) : '';
        case 'tickets_url':
            $v = get_post_meta($sid,'_stagekitwp_season_tickets_url',true);
            return $v ? esc_url($v) : '';
        case 'tickets_link':
            $v = get_post_meta($sid,'_stagekitwp_season_tickets_url',true);
            return $v ? '<a href="'.esc_url($v).'" target="_blank" rel="noopener">Get Tickets</a>' : '';
    }
    // Image fields
    if ( isset($img_meta[$field]) ) {
        $raw = get_post_meta($sid,$img_meta[$field],true);
        $url = stagekitwp_sn_img_url($raw);
        if ( !$url ) { return ''; }
        return '<img src="'.esc_url($url).'" alt="'.esc_attr($name).'" style="max-width:100%;height:auto;display:block">';
    }
    return '';
}
// ── CARDS renderer ──────────────────────────────────────────────────────
function stagekitwp_sn_render_cards( $seasons, $show ) {
    echo '<div class="stagekitwp-seasons-grid">';
    foreach ( $seasons as $season ) {
        $sid   = $season->ID;
        $name  = get_post_meta($sid,'_stagekitwp_season_name',true) ?: get_the_title($sid);
        $st    = get_post_meta($sid,'_stagekitwp_season_start_date',true);
        $en    = get_post_meta($sid,'_stagekitwp_season_end_date',  true);
        $tix   = get_post_meta($sid,'_stagekitwp_season_tickets_url',true);
        $desc  = get_post_field('post_content',$sid);
        $info  = stagekitwp_get_season_status_info($st,$en);
        $sd    = $st ? date_i18n('F j, Y',strtotime($st)) : '';
        $ed    = $en ? date_i18n('F j, Y',strtotime($en)) : '';

        // Images
        $front_raw   = get_post_meta($sid,'_stagekitwp_season_image_front',  true);
        $back_raw    = get_post_meta($sid,'_stagekitwp_season_image_back',   true);
        $banner_raw  = get_post_meta($sid,'_stagekitwp_season_social_banner',true);
        $sq_raw      = get_post_meta($sid,'_stagekitwp_season_sm_square',    true);
        $port_raw    = get_post_meta($sid,'_stagekitwp_season_sm_portrait',  true);
        $front_url   = stagekitwp_sn_img_url($front_raw);
        $back_url    = stagekitwp_sn_img_url($back_raw);
        $banner_url  = stagekitwp_sn_img_url($banner_raw);
        $sq_url      = stagekitwp_sn_img_url($sq_raw);
        $port_url    = stagekitwp_sn_img_url($port_raw);

        // Hero image: prefer social_banner (wide), fall back to image_front
        $hero_url = $banner_url ?: $front_url;

        echo '<div class="stagekitwp-sn-card">';

        // Hero image
        if ( $show['image'] && $hero_url ) {
            echo '<div class="stagekitwp-sn-img-wrap">';
            echo '<img class="stagekitwp-sn-card-img" src="'.esc_url($hero_url).'" alt="'.esc_attr($name).'" loading="lazy">';
            echo '</div>';
        }

        echo '<div class="stagekitwp-sn-card-body">';

        // Status badge
        if ( $show['status'] ) {
            echo '<span class="stagekitwp-sn-card-status" style="background:'.esc_attr($info['color']).'">'.
                 esc_html($info['label']).'</span>';
        }

        // Name
        if ( $show['name'] ) {
            echo '<h3 class="stagekitwp-sn-card-name">'.esc_html($name).'</h3>';
        }

        // Date range
        if ( $show['dates'] && ($sd||$ed) ) {
            echo '<p class="stagekitwp-sn-card-dates">';
            if($sd&&$ed){ echo esc_html($sd).' &ndash; '.esc_html($ed); }
            else { echo esc_html($sd.$ed); }
            echo '</p>';
        }

        // Description (post content)
        if ( $show['description'] && $desc ) {
            echo '<div class="stagekitwp-sn-card-desc">'.wpautop(wp_kses_post($desc)).'</div>';
        }

        // Thumbnail strip: back, sm_square, sm_portrait (extras)
        $thumbs = array();
        if ( $show['image_back']    && $back_url  ) { $thumbs[] = array($back_url, $name.' (back)');}  
        if ( $show['social_banner'] && $banner_url && !$show['image'] ) { $thumbs[] = array($banner_url,$name.' (banner)'); }
        if ( $show['sm_square']     && $sq_url    ) { $thumbs[] = array($sq_url,   $name.' (square)');}
        if ( $show['sm_portrait']   && $port_url  ) { $thumbs[] = array($port_url, $name.' (portrait)');}
        if ( $thumbs ) {
            echo '<div class="stagekitwp-sn-thumb-strip">';
            foreach($thumbs as $t){
                echo '<img class="stagekitwp-sn-thumb" src="'.esc_url($t[0]).'" alt="'.esc_attr($t[1]).'" loading="lazy">';
            }
            echo '</div>';
        }

        // Tickets
        if ( $show['tickets'] && $tix ) {
            echo '<div class="stagekitwp-sn-card-tickets"><a href="'.esc_url($tix).'" target="_blank" rel="noopener">Get Tickets</a></div>';
        }

        echo '</div></div>'; // card-body / card
    }
    echo '</div>'; // grid
}
// ── Shortcode handler ────────────────────────────────────────────────────
function stagekitwp_seasons_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'which'           => 'all',
        'season_id'       => '',
        'layout'          => 'cards',
        'order'           => 'DESC',
        'orderby'         => 'start_date',
        'limit'           => '-1',
        'field'           => 'name',
        // card toggles
        'show_image'      => 'true',
        'show_name'       => 'true',
        'show_status'     => 'true',
        'show_dates'      => 'true',
        'show_tickets'    => 'true',
        'show_description'=> 'false',
        'show_image_back' => 'false',
        'show_social_banner' => 'false',
        'show_sm_square'  => 'false',
        'show_sm_portrait'=> 'false',
    ), $atts, 'stagekitwp_seasons' );

    $layout  = in_array($atts['layout'],array('cards','field'),true) ? $atts['layout'] : 'cards';
    $which   = in_array($atts['which'],array('all','current','upcoming','next','past'),true) ? $atts['which'] : 'all';
    $t = function($k) use($atts){ return 'false' !== strtolower($atts[$k]); };
    $show = array(
        'image'         => $t('show_image'),
        'name'          => $t('show_name'),
        'status'        => $t('show_status'),
        'dates'         => $t('show_dates'),
        'tickets'       => $t('show_tickets'),
        'description'   => $t('show_description'),
        'image_back'    => $t('show_image_back'),
        'social_banner' => $t('show_social_banner'),
        'sm_square'     => $t('show_sm_square'),
        'sm_portrait'   => $t('show_sm_portrait'),
    );

    // Display Options — Season tab
    $bg  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_season_bg_color', '' ), '#ffffff' );
    $tx  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_season_text_color', '' ), '#1a1a1a' );
    $brd = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_season_border_color', '' ), '#dddddd' );
    $bw  = absint(get_option('stagekitwp_season_border_width','1'));
    $bdr = (bool)get_option('stagekitwp_season_rounded',false) ? absint(get_option('stagekitwp_season_radius','8')) : 0;
    $shd = (bool)get_option('stagekitwp_season_shadow',false);
    $fnt = sanitize_text_field(get_option('stagekitwp_season_base_font','inherit'));
    $h2c = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_season_h2_color', '' ), '#1a1a1a' );
    $h3c = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_season_h3_color', '' ), '#555555' );
    $acc = $h2c ?: '#1a3a6b';

    $css = stagekitwp_seasons_css($bg,$tx,$brd,$bw,$bdr,$shd,$fnt,$h2c,$h3c,$acc);

    // Dark mode overrides
    if ( function_exists('stagekitwp_dark_mode_admin_enabled') && stagekitwp_dark_mode_admin_enabled() ) {
        $ok = function($v,$fb){
            $v2=stagekitwp_sanitize_css_color($v); if(!$v2){return $fb;}
            $h=ltrim($v2,'#'); if(strlen($h)===3){$h=$h[0].$h[0].$h[1].$h[1].$h[2].$h[2];}
            if(strlen($h)!==6){return $fb;}
            $l=0.2126*hexdec(substr($h,0,2))/255+0.7152*hexdec(substr($h,2,2))/255+0.0722*hexdec(substr($h,4,2))/255;
            return($l<0.04)?$fb:$v2;
        };
        $ok_tx=function($v,$fb) use($ok){
            $v2=stagekitwp_sanitize_css_color($v); if(!$v2){return $fb;}
            $h=ltrim($v2,'#'); if(strlen($h)===3){$h=$h[0].$h[0].$h[1].$h[1].$h[2].$h[2];}
            if(strlen($h)!==6){return $fb;}
            $l=0.2126*hexdec(substr($h,0,2))/255+0.7152*hexdec(substr($h,2,2))/255+0.0722*hexdec(substr($h,4,2))/255;
            return($l>0.85||$l<0.04)?$fb:$v2;
        };
        $d_bg  = $ok(get_option('stagekitwp_season_bg_color_dark',   ''),'#1e1e1e');
        $d_tx  = $ok(get_option('stagekitwp_season_text_color_dark', ''),'#e0e0e0');
        $d_brd_raw = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_season_border_color_dark', '' ), '' ) ?: '';
        $d_brd_hex = ltrim($d_brd_raw,'#');
        if(strlen($d_brd_hex)===3){$d_brd_hex=$d_brd_hex[0].$d_brd_hex[0].$d_brd_hex[1].$d_brd_hex[1].$d_brd_hex[2].$d_brd_hex[2];}
        $d_brd_lum=(strlen($d_brd_hex)===6)?(0.2126*hexdec(substr($d_brd_hex,0,2))/255+0.7152*hexdec(substr($d_brd_hex,2,2))/255+0.0722*hexdec(substr($d_brd_hex,4,2))/255):0;
        $d_brd=($d_brd_lum<0.04)?'rgba(255,255,255,0.15)':($d_brd_raw?:'rgba(255,255,255,0.15)');
        $d_h2  = $ok_tx(get_option('stagekitwp_season_h2_color_dark',''),'#e0e0e0');
        $d_h3  = $ok_tx(get_option('stagekitwp_season_h3_color_dark',''),'#b0b0b0');
        $css  .= "\nhtml.stagekitwp-dark-mode .stagekitwp-seasons-block{--stagekitwp-sn-bg:{$d_bg}!important;--stagekitwp-sn-text:{$d_tx}!important;--stagekitwp-sn-border:{$d_brd}!important;--stagekitwp-sn-h2:{$d_h2}!important;--stagekitwp-sn-h3:{$d_h3}!important;--stagekitwp-sn-acc:{$d_h2}!important;--stagekitwp-sn-btn-bg:#1a78c2!important;--stagekitwp-sn-btn-text:#ffffff!important}";
    }
    stagekitwp_add_shortcode_inline_style('seasons',$css);

    $seasons = stagekitwp_sn_collect($atts['season_id'],$which,$atts['orderby'],$atts['order'],intval($atts['limit']));
    if ( empty($seasons) ) {
        $empty_html = function_exists( 'stagekitwp_get_shortcode_message_html' )
            ? stagekitwp_get_shortcode_message_html( 'stagekitwp_msg_no_seasons_html', 'No seasons found.' )
            : 'No seasons found.';
        return '<div class="stagekitwp-seasons-block"><p class="stagekitwp-seasons-empty">' . $empty_html . '</p></div>';
    }

    // FIELD layout: return one field value for the first matched season
    if ( 'field' === $layout ) {
        return stagekitwp_sn_render_field($seasons[0], sanitize_key($atts['field']));
    }

    // CARDS layout
    ob_start();
    echo '<div class="stagekitwp-seasons-block">';
    stagekitwp_sn_render_cards($seasons,$show);
    echo '</div>';
    return ob_get_clean();
}
if ( ! shortcode_exists('stagekitwp_seasons') ) {
    add_shortcode('stagekitwp_seasons','stagekitwp_seasons_shortcode');
} else {
    // Re-register to pick up the new implementation after reload
    remove_shortcode('stagekitwp_seasons');
    add_shortcode('stagekitwp_seasons','stagekitwp_seasons_shortcode');
}
