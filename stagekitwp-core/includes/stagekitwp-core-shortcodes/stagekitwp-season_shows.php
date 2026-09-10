<?php
/**
 * Shortcode: [stagekitwp_season_shows]
 * Layouts: spotlight (default) | cards
 * Attrs: layout, season_id, which (current|next|all)
 */
defined( 'ABSPATH' ) || exit;

function stagekitwp_ss_resolve_seasons( $season_id, $which ) {
    if ( ! empty( $season_id ) ) {
        $s = get_post( intval( $season_id ) );
        return ( $s && 'season' === $s->post_type ) ? array( $s ) : array();
    }
    $all = get_posts( array( 'post_type'=>'season','posts_per_page'=>-1,'post_status'=>'publish' ) );
    foreach ( $all as $s ) {
        $r=$s->_ts_start=($v=get_post_meta($s->ID,'_stagekitwp_season_start_date',true))?strtotime($v):0;
        $s->_ts_start=$r;
        $s->_ts_end=($v=get_post_meta($s->ID,'_stagekitwp_season_end_date',true))?strtotime($v)+86399:0;
    }
    usort( $all, function($a,$b){return $a->_ts_start-$b->_ts_start;} );
    if ( 'all'===$which ) { return $all; }
    $now=current_time('timestamp'); $ci=null;
    foreach($all as $i=>$s){if($s->_ts_start&&$s->_ts_end&&$now>=$s->_ts_start&&$now<=$s->_ts_end){$ci=$i;break;}}
    if('current'===$which){return null!==$ci?array($all[$ci]):array();}
    if('next'===$which){
        if(null!==$ci){return isset($all[$ci+1])?array($all[$ci+1]):array();}
        foreach($all as $s){if($s->_ts_start>$now){return array($s);}}
        return array();
    }
    return $all;
}

function stagekitwp_ss_season_banner( $season ) {
    $sid=$season->ID;
    $name=get_post_meta($sid,'_stagekitwp_season_name',true)?:get_the_title($sid);
    $st=get_post_meta($sid,'_stagekitwp_season_start_date',true);
    $en=get_post_meta($sid,'_stagekitwp_season_end_date',true);
    $tix=get_post_meta($sid,'_stagekitwp_season_tickets_url',true);
    $braw=get_post_meta($sid,'_stagekitwp_season_social_banner',true)?:get_post_meta($sid,'_stagekitwp_season_image_front',true);
    $burl=$braw?stagekitwp_get_image_url($braw):'';
    $sd=$st?date_i18n('F j, Y',strtotime($st)):'';
    $ed=$en?date_i18n('F j, Y',strtotime($en)):'';
    $out='<div class="stagekitwp-ss-season-banner">';
    if($burl){$out.='<div class="stagekitwp-ss-banner-img" style="background-image:url('.esc_url($burl).')" role="img" aria-label="'.esc_attr($name).'"></div>';}
    $out.='<div class="stagekitwp-ss-banner-body"><h2 class="stagekitwp-ss-season-name">'.esc_html($name).'</h2>';
    if($sd||$ed){$out.='<p class="stagekitwp-ss-season-dates">'.esc_html($sd&&$ed?$sd.' – '.$ed:$sd.$ed).'</p>';}
    if($tix){$out.='<a class="stagekitwp-ss-tickets-btn" href="'.esc_url($tix).'" target="_blank" rel="noopener">Get Tickets</a>';}
    $out.='</div></div>';
    return $out;
}

function stagekitwp_ss_get_shows( $sid ) {
    $shows=get_posts(array('post_type'=>'show','posts_per_page'=>-1,'post_status'=>'publish',
        'meta_query'=>array(array('key'=>'_stagekitwp_show_season','value'=>$sid,'compare'=>'='))));
    $ord=stagekitwp_show_slot_order();
    usort($shows,function($a,$b) use($ord){
        $sa=get_post_meta($a->ID,'_stagekitwp_show_time_slot',true);$sb=get_post_meta($b->ID,'_stagekitwp_show_time_slot',true);
        return(isset($ord[$sa])?$ord[$sa]:999)-(isset($ord[$sb])?$ord[$sb]:999);
    });
    return $shows;
}

function stagekitwp_ss_css($bg,$text,$brd,$bw,$r,$shd,$font,$h2,$h3,$acc){
    $sv=$shd?'0 3px 10px rgba(0,0,0,.15)':'none';
    $sv2=$shd?'0 2px 6px rgba(0,0,0,.12)':'none';
    return "
.stagekitwp-season-shows-block{--stagekitwp-ss-bg:{$bg};--stagekitwp-ss-text:{$text};--stagekitwp-ss-border:{$brd};--stagekitwp-ss-bw:{$bw}px;--stagekitwp-ss-radius:{$r}px;--stagekitwp-ss-font:{$font};--stagekitwp-ss-h2:{$h2};--stagekitwp-ss-h3:{$h3};--stagekitwp-ss-acc:{$acc};--stagekitwp-ss-btn-bg:{$acc};--stagekitwp-ss-btn-text:#ffffff;font-family:var(--stagekitwp-ss-font);color:var(--stagekitwp-ss-text)}
/* Season banner */
.stagekitwp-ss-season-banner{margin-bottom:28px}
.stagekitwp-ss-banner-img{width:100%;height:220px;background-size:cover;background-position:center;border-radius:var(--stagekitwp-ss-radius);margin-bottom:14px}
.stagekitwp-ss-banner-body{display:flex;flex-wrap:wrap;align-items:baseline;gap:10px 18px}
.stagekitwp-ss-season-name{font-size:1.35em;font-weight:700;color:var(--stagekitwp-ss-h2)!important;margin:0}
.stagekitwp-ss-season-dates{margin:0;font-size:.88em;opacity:.75;color:var(--stagekitwp-ss-text)!important}
.stagekitwp-ss-tickets-btn{display:inline-block;padding:7px 18px;background:var(--stagekitwp-ss-acc);color:#fff!important;border-radius:4px;text-decoration:none;font-size:.85em;font-weight:600;transition:opacity .15s}
.stagekitwp-ss-tickets-btn:hover{opacity:.85}
/* Buttons — separate bg/text vars so dark mode can swap independently */
.stagekitwp-ss-tickets-btn,.stagekitwp-ss-show-tix-btn,.stagekitwp-ss-card-btn-tix{background:var(--stagekitwp-ss-btn-bg)!important;color:var(--stagekitwp-ss-btn-text)!important}
.stagekitwp-ss-card-btn-aud{color:var(--stagekitwp-ss-btn-bg)!important;border-color:var(--stagekitwp-ss-btn-bg)!important}
/* Spotlight grid — portrait cards */
.stagekitwp-ss-spotlight-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px;margin-bottom:32px}
.stagekitwp-ss-spotlight-card{background:var(--stagekitwp-ss-bg);border:var(--stagekitwp-ss-bw) solid var(--stagekitwp-ss-border);border-radius:var(--stagekitwp-ss-radius);box-shadow:{$sv2};overflow:hidden;display:flex;flex-direction:column}
.stagekitwp-ss-spotlight-img-wrap{width:100%;aspect-ratio:4/3;background:rgba(0,0,0,.04);overflow:hidden;display:flex;align-items:center;justify-content:center}
html.stagekitwp-dark-mode .stagekitwp-ss-spotlight-img-wrap{background:rgba(255,255,255,.05)}
.stagekitwp-ss-spotlight-img{width:100%;height:100%;object-fit:contain;display:block}
.stagekitwp-ss-spotlight-placeholder{width:100%;aspect-ratio:4/3;background:rgba(0,0,0,.06);display:flex;align-items:center;justify-content:center;font-size:2.5em}
html.stagekitwp-dark-mode .stagekitwp-ss-spotlight-placeholder{background:rgba(255,255,255,.06)}
.stagekitwp-ss-spotlight-body{padding:12px 14px;display:flex;flex-direction:column;gap:4px;flex:1}
.stagekitwp-ss-spotlight-slot{font-size:.72em;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--stagekitwp-ss-h3)!important;opacity:.8;margin:0}
.stagekitwp-ss-spotlight-title{font-size:1em;font-weight:700;color:var(--stagekitwp-ss-h2)!important;margin:0}
.stagekitwp-ss-spotlight-dates{font-size:.82em;color:var(--stagekitwp-ss-text)!important;opacity:.75;margin:0}
.stagekitwp-ss-spotlight-meta{margin-top:4px}
.stagekitwp-ss-spotlight-meta p{font-size:.82em;color:var(--stagekitwp-ss-text)!important;margin:2px 0}
.stagekitwp-ss-spotlight-meta strong{opacity:.7;font-weight:600}
.stagekitwp-ss-spotlight-actions{margin-top:auto;padding-top:8px}
.stagekitwp-ss-show-tix-btn{display:inline-block;padding:5px 14px;background:var(--stagekitwp-ss-acc);color:#fff!important;border-radius:4px;text-decoration:none;font-size:.8em;font-weight:600;transition:opacity .15s}
.stagekitwp-ss-show-tix-btn:hover{opacity:.85}
.stagekitwp-ss-spotlight-actions{display:flex;flex-wrap:wrap;gap:6px;margin-top:auto;padding-top:8px}
.stagekitwp-ss-aud-link{display:inline-block;padding:5px 14px;border-radius:4px;font-size:.8em;font-weight:600;text-decoration:none;transition:opacity .15s;background:transparent;color:var(--stagekitwp-ss-btn-bg)!important;border:1px solid var(--stagekitwp-ss-btn-bg)}
.stagekitwp-ss-aud-link:hover{opacity:.82}
.stagekitwp-ss-aud-inline{font-size:.8em;color:var(--stagekitwp-ss-text)!important;opacity:.8;line-height:1.5}
.stagekitwp-ss-aud-inline small{display:block;font-size:.9em;opacity:.75}
/* Cards layout — full-width stacked */
.stagekitwp-ss-cards-list{display:flex;flex-direction:column;gap:20px;margin-bottom:32px}
.stagekitwp-ss-card{background:var(--stagekitwp-ss-bg);border:var(--stagekitwp-ss-bw) solid var(--stagekitwp-ss-border);border-radius:var(--stagekitwp-ss-radius);box-shadow:{$sv2};overflow:hidden;display:grid;grid-template-columns:280px 1fr;width:100%}
.stagekitwp-ss-card-img-wrap{width:100%;height:100%;min-height:200px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.04)}
html.stagekitwp-dark-mode .stagekitwp-ss-card-img-wrap{background:rgba(255,255,255,.05)}
.stagekitwp-ss-card-img{width:100%;height:100%;object-fit:contain;display:block}
.stagekitwp-ss-card-body{padding:18px 22px;display:flex;flex-direction:column;gap:6px}
.stagekitwp-ss-card-slot{font-size:.72em;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--stagekitwp-ss-h3)!important;opacity:.8}
.stagekitwp-ss-card-title{font-size:1.15em;font-weight:700;color:var(--stagekitwp-ss-h2)!important;margin:0}
.stagekitwp-ss-card-field{font-size:.88em;color:var(--stagekitwp-ss-text)!important;margin:0}
.stagekitwp-ss-card-field strong{opacity:.7;font-weight:600}
.stagekitwp-ss-card-synopsis{font-size:.86em;color:var(--stagekitwp-ss-text)!important;opacity:.85;line-height:1.6;margin:4px 0 0}
.stagekitwp-ss-card-venue{font-size:.82em;color:var(--stagekitwp-ss-text)!important;opacity:.7}
.stagekitwp-ss-card-venue a{color:var(--stagekitwp-ss-acc)!important;text-decoration:none}
.stagekitwp-ss-card-venue a:hover{text-decoration:underline}
.stagekitwp-ss-card-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:auto;padding-top:10px}
.stagekitwp-ss-card-btn{display:inline-block;padding:7px 18px;border-radius:4px;font-size:.85em;font-weight:600;text-decoration:none;transition:opacity .15s;cursor:pointer}
.stagekitwp-ss-card-btn-tix{background:var(--stagekitwp-ss-acc);color:#fff!important}
.stagekitwp-ss-card-btn-aud{background:transparent;color:var(--stagekitwp-ss-acc)!important;border:1px solid var(--stagekitwp-ss-acc)}
.stagekitwp-ss-card-btn:hover{opacity:.82}
.stagekitwp-ss-aud-detail{font-size:.84em;color:var(--stagekitwp-ss-text)!important;opacity:.8;flex-basis:100%}
.stagekitwp-ss-empty{color:var(--stagekitwp-ss-text);font-style:italic;padding:10px 0}
.stagekitwp-ss-empty img,.stagekitwp-ss-empty svg{opacity:1!important;filter:none!important}
@media(max-width:700px){.stagekitwp-ss-card{grid-template-columns:1fr}.stagekitwp-ss-card-img-wrap{min-height:180px;aspect-ratio:4/3;height:auto}}
@media(max-width:540px){.stagekitwp-ss-spotlight-grid{grid-template-columns:1fr}.stagekitwp-ss-banner-img{height:140px}}
";}
function stagekitwp_ss_render_spotlight($shows,$show_aud=true,$aud_url=''){
    echo '<div class="stagekitwp-ss-spotlight-grid">';
    foreach($shows as $show){
        $id=$show->ID;
        $g=function($k)use($id){return sanitize_text_field(get_post_meta($id,'_stagekitwp_show_'.$k,true));};
        $slot=$g('time_slot');$dates=$g('show_dates');$auth=$g('author');$sub=$g('sub_authors');
        $dir=$g('director');$adir=$g('associate_director');$prod=$g('producer');$sm=$g('stage_manager');
        $show_tix=esc_url(get_post_meta($id,'_stagekitwp_show_tickets_url',true));
        $aud_d=$g('audition_date');$aud_det=wp_strip_all_tags(get_post_meta($id,'_stagekitwp_show_audition_details',true));
        $img=stagekitwp_get_image_url(get_post_meta($id,'_stagekitwp_show_sm_image',true));
        $show_url = stagekitwp_show_page_url( $id );
        echo '<div class="stagekitwp-ss-spotlight-card">';
        if($img){
            $img_el = '<img class="stagekitwp-ss-spotlight-img" src="'.esc_url($img).'" alt="'.esc_attr(get_the_title($id)).'" loading="lazy">';
            echo '<div class="stagekitwp-ss-spotlight-img-wrap">';
            echo $show_url ? '<a href="'.esc_url($show_url).'">'.$img_el.'</a>' : $img_el;
            echo '</div>';
        } else {
            echo '<div class="stagekitwp-ss-spotlight-placeholder" aria-hidden="true">&#127917;</div>';
        }
        echo '<div class="stagekitwp-ss-spotlight-body">';
        if($slot){echo '<p class="stagekitwp-ss-spotlight-slot">'.esc_html($slot).' Show</p>';}
        $title_esc = esc_html(get_the_title($id));
        echo '<h3 class="stagekitwp-ss-spotlight-title">';
        echo $show_url ? '<a href="'.esc_url($show_url).'" style="color:inherit;text-decoration:none;">'.$title_esc.'</a>' : $title_esc;
        echo '</h3>';
        if($dates){echo '<p class="stagekitwp-ss-spotlight-dates">'.esc_html($dates).'</p>';}
        echo '<div class="stagekitwp-ss-spotlight-meta">';
        if($auth){echo '<p><strong>Author:</strong> '.esc_html($auth).'</p>';}
        if($sub) {echo '<p><strong>Music/Lyrics/Book:</strong> '.esc_html($sub).'</p>';}
        $dl=trim($dir.($adir?' / Asst. Dir. '.$adir:''));
        if($dl)  {echo '<p><strong>Directed by:</strong> '.esc_html($dl).'</p>';}
        if($prod){echo '<p><strong>Produced by:</strong> '.esc_html($prod).'</p>';}
        if($sm)  {echo '<p><strong>Stage Managed by:</strong> '.esc_html($sm).'</p>';}
        echo '</div>';
        $has_actions = $show_tix || ($show_aud && ($aud_d||$aud_det));
        if($has_actions){
            echo '<div class="stagekitwp-ss-spotlight-actions">';
            if($show_tix){echo '<a class="stagekitwp-ss-show-tix-btn" href="'.$show_tix.'" target="_blank" rel="noopener">Get Tickets</a>';}
            if($show_aud&&($aud_d||$aud_det)){
                $al=$aud_d?'Auditions: '.date_i18n('M j, Y',strtotime($aud_d)):'Audition Info';
                if($aud_url){
                    echo '<a class="stagekitwp-ss-aud-link" href="'.esc_url($aud_url).'">'.esc_html($al).'</a>';
                } else {
                    echo '<span class="stagekitwp-ss-aud-inline">'.esc_html($al);
                    if($aud_det){echo '<br><small>'.esc_html($aud_det).'</small>';}
                    echo '</span>';
                }
            }
            echo '</div>';
        }
        echo '</div></div>';
    }
    echo '</div>';
}

function stagekitwp_ss_render_cards($shows,$show_aud=true,$aud_url=''){
    echo '<div class="stagekitwp-ss-cards-list">';
    foreach($shows as $show){
        $id=$show->ID;
        $g=function($k)use($id){return sanitize_text_field(get_post_meta($id,'_stagekitwp_show_'.$k,true));};
        $slot=$g('time_slot');$dates=$g('show_dates');$auth=$g('author');$sub=$g('sub_authors');
        $dir=$g('director');$adir=$g('associate_director');$prod=$g('producer');$sm=$g('stage_manager');
        $genre=$g('genre');$synopsis=wp_strip_all_tags(get_post_meta($id,'_stagekitwp_show_synopsis',true));
        $tix=esc_url(get_post_meta($id,'_stagekitwp_show_tickets_url',true));
        $aud_d=$g('audition_date');$aud_det=wp_strip_all_tags(get_post_meta($id,'_stagekitwp_show_audition_details',true));
        $vid=intval(get_post_meta($id,'_stagekitwp_show_venue',true));
        $vname=$vid?(sanitize_text_field(get_post_meta($vid,'_stagekitwp_venue_name',true))?:get_the_title($vid)):'';
        $vweb=$vid?esc_url(get_post_meta($vid,'_stagekitwp_venue_website',true)):'';
        $img=stagekitwp_get_image_url(get_post_meta($id,'_stagekitwp_show_sm_image',true));
        $show_url = stagekitwp_show_page_url( $id );
        echo '<div class="stagekitwp-ss-card">';
        if($img){
            $img_el = '<img class="stagekitwp-ss-card-img" src="'.esc_url($img).'" alt="'.esc_attr(get_the_title($id)).'" loading="lazy">';
            echo '<div class="stagekitwp-ss-card-img-wrap">';
            echo $show_url ? '<a href="'.esc_url($show_url).'">'.$img_el.'</a>' : $img_el;
            echo '</div>';
        }
        echo '<div class="stagekitwp-ss-card-body">';
        if($slot)    {echo '<p class="stagekitwp-ss-card-slot">'.esc_html($slot).' Show</p>';}
        $title_esc = esc_html(get_the_title($id));
        echo '<h3 class="stagekitwp-ss-card-title">';
        echo $show_url ? '<a href="'.esc_url($show_url).'" style="color:inherit;text-decoration:none;">'.$title_esc.'</a>' : $title_esc;
        echo '</h3>';
        if($genre)   {echo '<p class="stagekitwp-ss-card-field"><strong>Genre:</strong> '.esc_html($genre).'</p>';}
        if($dates)   {echo '<p class="stagekitwp-ss-card-field"><strong>Dates:</strong> '.esc_html($dates).'</p>';}
        if($auth)    {echo '<p class="stagekitwp-ss-card-field"><strong>Author:</strong> '.esc_html($auth).'</p>';}
        if($sub)     {echo '<p class="stagekitwp-ss-card-field"><strong>Music/Lyrics/Book:</strong> '.esc_html($sub).'</p>';}
        $dl=trim($dir.($adir?' / Asst. Dir. '.$adir:''));
        if($dl)      {echo '<p class="stagekitwp-ss-card-field"><strong>Directed by:</strong> '.esc_html($dl).'</p>';}
        if($prod)    {echo '<p class="stagekitwp-ss-card-field"><strong>Produced by:</strong> '.esc_html($prod).'</p>';}
        if($sm)      {echo '<p class="stagekitwp-ss-card-field"><strong>Stage Managed by:</strong> '.esc_html($sm).'</p>';}
        if($synopsis){echo '<p class="stagekitwp-ss-card-synopsis">'.esc_html($synopsis).'</p>';}
        if($vname)   {echo '<p class="stagekitwp-ss-card-venue">&#128205;&nbsp;'.($vweb?'<a href="'.esc_url($vweb).'" target="_blank" rel="noopener">'.esc_html($vname).'</a>':esc_html($vname)).'</p>';}
        $has_aud = $show_aud && ($aud_d||$aud_det);
        if($tix||$has_aud){
            echo '<div class="stagekitwp-ss-card-actions">';
            if($tix){echo '<a class="stagekitwp-ss-card-btn stagekitwp-ss-card-btn-tix" href="'.$tix.'" target="_blank" rel="noopener">Get Tickets</a>';}
            if($has_aud){
                $al=$aud_d?'Auditions: '.date_i18n('M j, Y',strtotime($aud_d)):'Audition Info';
                if($aud_url){
                    echo '<a class="stagekitwp-ss-card-btn stagekitwp-ss-card-btn-aud" href="'.esc_url($aud_url).'">'.esc_html($al).'</a>';
                } else {
                    echo '<span class="stagekitwp-ss-card-btn stagekitwp-ss-card-btn-aud">'.esc_html($al).'</span>';
                    if($aud_det){echo '<p class="stagekitwp-ss-aud-detail">'.esc_html($aud_det).'</p>';}
                }
            }
            echo '</div>';
        }
        echo '</div></div>';
    }
    echo '</div>';
}
function stagekitwp_season_shows_shortcode($atts){
    $atts=shortcode_atts(array(
        'layout'          => 'spotlight',
        'season_id'       => '',
        'which'           => 'current',
        'show_auditions'  => 'true',
    ),$atts,'stagekitwp_season_shows');
    $layout=in_array($atts['layout'],array('spotlight','cards'),true)?$atts['layout']:'spotlight';
    $which =in_array($atts['which'], array('current','next','all'), true)?$atts['which']:'current';
    $bg  =stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_bg_color', '' ), '#ffffff' );
    $tx  =stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_text_color', '' ), '#1a1a1a' );
    $brd =stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_border_color', '' ), '#dddddd' );
    $bw  =absint(get_option('stagekitwp_show_border_width','1'));
    $bdr =(bool)get_option('stagekitwp_show_rounded',false)?absint(get_option('stagekitwp_show_radius','8')):0;
    $shd =(bool)get_option('stagekitwp_show_shadow',false);
    $fnt =sanitize_text_field(get_option('stagekitwp_show_base_font','inherit'));
    $h2c =stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_h2_color', '' ), '#1a1a1a' );
    $h3c =stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_h3_color', '' ), '#555555' );
    // Accent: use h2 colour as accent (matches rest of plugin convention; h3 is subtitle)
    $acc = $h2c ?: '#1a3a6b';
    $css = stagekitwp_ss_css($bg,$tx,$brd,$bw,$bdr,$shd,$fnt,$h2c,$h3c,$acc);
    // Dark mode DO overrides — only injected when TM theme dark integration is active
    if ( function_exists('stagekitwp_dark_mode_admin_enabled') && stagekitwp_dark_mode_admin_enabled() ) {
        $ok = function($v,$fb){ $v2=stagekitwp_sanitize_css_color($v); if(!$v2){return $fb;}
            $h=ltrim($v2,'#'); if(strlen($h)===3){$h=$h[0].$h[0].$h[1].$h[1].$h[2].$h[2];}
            if(strlen($h)!==6){return $fb;}
            $lum=0.2126*hexdec(substr($h,0,2))/255+0.7152*hexdec(substr($h,2,2))/255+0.0722*hexdec(substr($h,4,2))/255;
            return($lum<0.04)?$fb:$v2; };
        $d_bg  =$ok(get_option('stagekitwp_show_bg_color_dark',  ''),'#1e1e1e');
        $d_tx  =$ok(get_option('stagekitwp_show_text_color_dark',''),'#e0e0e0');
        $d_brd_raw=stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_border_color_dark', '' ), '' )?:'';
        $d_brd_hex=ltrim($d_brd_raw,'#'); if(strlen($d_brd_hex)===3){$d_brd_hex=$d_brd_hex[0].$d_brd_hex[0].$d_brd_hex[1].$d_brd_hex[1].$d_brd_hex[2].$d_brd_hex[2];}
        $d_brd_lum=(strlen($d_brd_hex)===6)?(0.2126*hexdec(substr($d_brd_hex,0,2))/255+0.7152*hexdec(substr($d_brd_hex,2,2))/255+0.0722*hexdec(substr($d_brd_hex,4,2))/255):0;
        $d_brd =($d_brd_lum<0.04)?'rgba(255,255,255,0.15)':($d_brd_raw?:'rgba(255,255,255,0.15)');
        $ok_tx = function($v,$fb) use($ok){ $v2=stagekitwp_sanitize_css_color($v); if(!$v2){return $fb;}
            $h=ltrim($v2,'#'); if(strlen($h)===3){$h=$h[0].$h[0].$h[1].$h[1].$h[2].$h[2];}
            if(strlen($h)!==6){return $fb;}
            $lum=0.2126*hexdec(substr($h,0,2))/255+0.7152*hexdec(substr($h,2,2))/255+0.0722*hexdec(substr($h,4,2))/255;
            return($lum>0.85||$lum<0.04)?$fb:$v2; };
        $d_h2  =$ok_tx(get_option('stagekitwp_show_h2_color_dark',''),'#e0e0e0');
        $d_h3  =$ok_tx(get_option('stagekitwp_show_h3_color_dark',''),'#b0b0b0');
        // Dark-mode button: use a mid-blue that reads well on dark backgrounds with white text
        // and stays distinct from the h2 heading colour.
        $d_btn_bg   = '#1a78c2'; // WCAG AA vs #fff (4.6:1)
        $d_btn_text = '#ffffff';
        $css  .= "\nhtml.stagekitwp-dark-mode .stagekitwp-season-shows-block{--stagekitwp-ss-bg:{$d_bg}!important;--stagekitwp-ss-text:{$d_tx}!important;--stagekitwp-ss-border:{$d_brd}!important;--stagekitwp-ss-h2:{$d_h2}!important;--stagekitwp-ss-h3:{$d_h3}!important;--stagekitwp-ss-acc:{$d_h2}!important;--stagekitwp-ss-btn-bg:{$d_btn_bg}!important;--stagekitwp-ss-btn-text:{$d_btn_text}!important}";
    }
    stagekitwp_add_shortcode_inline_style('season-shows',$css);
    $show_aud = ('false' !== strtolower($atts['show_auditions']));
    // Auditions page URL: use saved setting, fall back to empty string.
    $aud_page_id = intval( get_option('stagekitwp_auditions_page_id', 0) );
    $aud_url     = $aud_page_id ? get_permalink($aud_page_id) : '';
    $seasons=stagekitwp_ss_resolve_seasons($atts['season_id'],$which);
    if(empty($seasons)){
        $no_seasons_html = function_exists( 'stagekitwp_get_shortcode_message_html' )
            ? stagekitwp_get_shortcode_message_html( 'stagekitwp_msg_no_seasons_html', 'We are currently in the process of selecting the plays for our next seasons. Check back here later.' )
            : 'We are currently in the process of selecting the plays for our next seasons. Check back here later.';
        return '<div class="stagekitwp-season-shows-block"><p class="stagekitwp-ss-empty">' . $no_seasons_html . '</p></div>';
    }
    ob_start();
    echo '<div class="stagekitwp-season-shows-block">';
    $no_shows_html = function_exists( 'stagekitwp_get_shortcode_message_html' )
        ? stagekitwp_get_shortcode_message_html( 'stagekitwp_msg_no_shows_in_season_html', 'No shows scheduled for this season.' )
        : 'No shows scheduled for this season.';
    foreach($seasons as $season){
        $shows=stagekitwp_ss_get_shows($season->ID);
        echo stagekitwp_ss_season_banner($season);
        if(empty($shows)){echo '<p class="stagekitwp-ss-empty">' . $no_shows_html . '</p>';continue;}
        if('cards'===$layout){stagekitwp_ss_render_cards($shows,$show_aud,$aud_url);}else{stagekitwp_ss_render_spotlight($shows,$show_aud,$aud_url);}
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('stagekitwp_season_shows','stagekitwp_season_shows_shortcode');
