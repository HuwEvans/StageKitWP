<?php
/**
 * Shortcode: [stagekitwp_past_shows]
 * Layouts: list (default) | cards
 * Sort: seasons newest-first always
 *   list  — shows Spring→Winter→Fall (descending — most recent show first)
 *   cards — shows Fall→Winter→Spring (ascending — chronological order within season)
 * Attrs: layout, limit, show_author, show_program, show_awards
 */
defined( 'ABSPATH' ) || exit;

// ── Inline SVG PDF icon ───────────────────────────────────────────────────────
function stagekitwp_ps_pdf_icon() {
    return '<svg class="stagekitwp-ps-pdf-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
         . '<path fill="#e53935" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>'
         . '<polyline fill="none" stroke="#fff" stroke-width="1.5" points="14 2 14 8 20 8"/>'
         . '<text x="12" y="17" text-anchor="middle" font-size="5.5" font-family="Arial,sans-serif" font-weight="700" fill="#fff" letter-spacing=".3">PDF</text>'
         . '</svg>';
}

// ── CSS ───────────────────────────────────────────────────────────────────────
function stagekitwp_past_shows_css( $bg, $text, $border, $bw, $radius, $shadow, $font, $h2c, $h3c ) {
    $sv = $shadow ? '0 2px 6px rgba(0,0,0,.14)' : 'none';
    return "
.stagekitwp-past-shows-block{--stagekitwp-ps-bg:{$bg};--stagekitwp-ps-text:{$text};--stagekitwp-ps-border:{$border};--stagekitwp-ps-bw:{$bw}px;--stagekitwp-ps-radius:{$radius}px;--stagekitwp-ps-shadow:{$sv};--stagekitwp-ps-font:{$font};--stagekitwp-ps-h2:{$h2c};--stagekitwp-ps-h3:{$h3c};font-family:var(--stagekitwp-ps-font);color:var(--stagekitwp-ps-text)}
.stagekitwp-ps-pdf-icon{width:18px;height:18px;display:inline-block;vertical-align:middle;flex-shrink:0;margin-right:4px}
.stagekitwp-ps-list{display:grid;grid-template-columns:auto auto 1fr auto auto auto;gap:0;width:100%}
.stagekitwp-ps-row{display:contents}
.stagekitwp-ps-cell{padding:8px 10px;border-bottom:1px solid var(--stagekitwp-ps-border);color:var(--stagekitwp-ps-text)!important;font-size:.9em;background:var(--stagekitwp-ps-bg)}
.stagekitwp-ps-season-mobile{display:none}
.stagekitwp-ps-row.stagekitwp-ps-season-alt .stagekitwp-ps-cell{background:rgba(0,0,0,.025)}
html.stagekitwp-dark-mode .stagekitwp-ps-row.stagekitwp-ps-season-alt .stagekitwp-ps-cell{background:rgba(255,255,255,.04)}
.stagekitwp-ps-col-season{font-weight:700;color:var(--stagekitwp-ps-h2)!important;white-space:nowrap;font-size:.85em}
.stagekitwp-ps-col-slot{color:var(--stagekitwp-ps-h3)!important;white-space:nowrap;font-size:.8em;text-transform:uppercase;letter-spacing:.05em;opacity:.8}
.stagekitwp-ps-col-title{font-weight:600}
.stagekitwp-ps-col-author{opacity:.7;font-size:.85em}
.stagekitwp-ps-col-program{font-size:.82em;white-space:nowrap}
.stagekitwp-ps-col-program a{display:inline-flex;align-items:center;gap:0;color:var(--stagekitwp-ps-h2)!important;text-decoration:none}
.stagekitwp-ps-col-program a span{border-bottom:1px solid transparent;transition:border-color .15s}
.stagekitwp-ps-col-program a:hover span{border-bottom-color:var(--stagekitwp-ps-h2)}
.stagekitwp-ps-col-awards{white-space:nowrap}
.stagekitwp-ps-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px}
.stagekitwp-ps-card{background:var(--stagekitwp-ps-bg);border:var(--stagekitwp-ps-bw) solid var(--stagekitwp-ps-border);border-radius:var(--stagekitwp-ps-radius);box-shadow:var(--stagekitwp-ps-shadow);overflow:hidden}
.stagekitwp-ps-card-header{background:var(--stagekitwp-ps-h2);padding:10px 14px}
.stagekitwp-ps-card-season{font-weight:700;font-size:.95em;margin:0;color:#fff!important}
html.stagekitwp-dark-mode .stagekitwp-ps-card-header{background:rgba(255,255,255,.1)}
html.stagekitwp-dark-mode .stagekitwp-ps-card-season{color:var(--stagekitwp-ps-text)!important}
.stagekitwp-ps-card-show{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:8px;padding:8px 14px;border-bottom:1px solid var(--stagekitwp-ps-border)}
.stagekitwp-ps-card-show:last-child{border-bottom:none}
.stagekitwp-ps-card-slot{font-size:.72em;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--stagekitwp-ps-h3)!important;opacity:.8;white-space:nowrap}
.stagekitwp-ps-card-info{min-width:0;display:flex;flex-direction:column}
.stagekitwp-ps-card-title{font-weight:600;font-size:.9em;color:var(--stagekitwp-ps-text)!important;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.stagekitwp-ps-card-author{font-size:.78em;color:var(--stagekitwp-ps-text)!important;opacity:.65;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.stagekitwp-ps-card-meta{display:flex;align-items:center;gap:5px;flex-shrink:0}
.stagekitwp-ps-card-pdf a{display:inline-flex;align-items:center;color:var(--stagekitwp-ps-h2)!important;text-decoration:none}
.stagekitwp-ps-badge{display:inline-flex;align-items:center;gap:2px;padding:2px 6px;border-radius:10px;font-size:.72em;font-weight:700;white-space:nowrap;line-height:1.4;margin-right:3px}
.stagekitwp-ps-badge-win{background:rgba(200,160,0,.15);color:#6b5200;border:1px solid rgba(200,160,0,.35)}
.stagekitwp-ps-badge-nom{background:rgba(100,100,100,.1);color:var(--stagekitwp-ps-text);border:1px solid var(--stagekitwp-ps-border);opacity:.85}
.stagekitwp-ps-badge-not-elig{background:rgba(215,40,40,.08);color:#9e2a2a;border:1px solid rgba(215,40,40,.3);opacity:.8}
html.stagekitwp-dark-mode .stagekitwp-ps-badge-win{color:#f0c040;background:rgba(200,160,0,.13);border-color:rgba(200,160,0,.3)}
html.stagekitwp-dark-mode .stagekitwp-ps-badge-nom{opacity:.7}
html.stagekitwp-dark-mode .stagekitwp-ps-badge-not-elig{color:#ff6b6b;background:rgba(215,40,40,.12);border-color:rgba(215,40,40,.4)}
.stagekitwp-ps-empty{color:var(--stagekitwp-ps-text);opacity:.6;font-style:italic;padding:12px 0}
@media(max-width:640px){.stagekitwp-ps-list{display:block}.stagekitwp-ps-row{display:block;margin-bottom:14px;border:var(--stagekitwp-ps-bw) solid var(--stagekitwp-ps-border);border-radius:var(--stagekitwp-ps-radius);box-shadow:var(--stagekitwp-ps-shadow);overflow:hidden}.stagekitwp-ps-row:last-child{margin-bottom:0}.stagekitwp-ps-row .stagekitwp-ps-cell{display:block;border-bottom:1px solid var(--stagekitwp-ps-border);padding:8px 12px}.stagekitwp-ps-row .stagekitwp-ps-cell:last-child{border-bottom:none}.stagekitwp-ps-col-season{background:rgba(0,0,0,.04)!important}.stagekitwp-ps-season-desktop{display:none}.stagekitwp-ps-season-mobile{display:inline}.stagekitwp-ps-col-title{font-size:1rem}.stagekitwp-ps-col-slot,.stagekitwp-ps-col-author,.stagekitwp-ps-col-program,.stagekitwp-ps-col-awards{white-space:normal}.stagekitwp-ps-col-slot::before,.stagekitwp-ps-col-author::before,.stagekitwp-ps-col-program::before,.stagekitwp-ps-col-awards::before{content:attr(data-label) ': ';font-weight:700;color:var(--stagekitwp-ps-h3)!important}.stagekitwp-ps-col-program:empty,.stagekitwp-ps-col-awards:empty,.stagekitwp-ps-col-author:empty{display:none}}
@media(max-width:480px){.stagekitwp-ps-cards{grid-template-columns:1fr}}
";
}
// ── Shared helpers ───────────────────────────────────────────────────────────
function stagekitwp_ps_prog_link( $shid ) {
    $prog_id  = get_post_meta( $shid, '_stagekitwp_show_program',     true );
    $prog_url = get_post_meta( $shid, '_stagekitwp_show_program_url', true );
    if ( ! $prog_url && $prog_id ) { $prog_url = wp_get_attachment_url( $prog_id ); }
    if ( ! $prog_url ) { return ''; }
    return '<a href="' . esc_url( $prog_url ) . '" target="_blank" rel="noopener">'
         . stagekitwp_ps_pdf_icon() . '<span>Programme</span></a>';
}
function stagekitwp_ps_badges( $shid, $award_map ) {
    if ( ! isset( $award_map[ $shid ] ) ) { return ''; }
    $out = '';
    if ( $award_map[ $shid ]['win'] ) { $out .= '<span class="stagekitwp-ps-badge stagekitwp-ps-badge-win">&#11088;&nbsp;Winner</span>'; }
    if ( $award_map[ $shid ]['elig'] ) { $out .= '<span class="stagekitwp-ps-badge stagekitwp-ps-badge-not-elig">&#10060;&nbsp;Not Elig.</span>'; }
    if ( $award_map[ $shid ]['nom'] ) { $out .= '<span class="stagekitwp-ps-badge stagekitwp-ps-badge-nom">&#127917;&nbsp;Nom.</span>'; }
    return $out;
}
function stagekitwp_ps_author_str( $shid ) {
    $a = sanitize_text_field( get_post_meta( $shid, '_stagekitwp_show_author',      true ) );
    $s = sanitize_text_field( get_post_meta( $shid, '_stagekitwp_show_sub_authors', true ) );
    return $a . ( $s ? ( $a ? ' / ' . $s : $s ) : '' );
}
// ── Shortcode handler ─────────────────────────────────────────────────────
function stagekitwp_shortcode_past_shows( $atts ) {
    $atts = shortcode_atts( array(
        'layout'       => 'list',
        'limit'        => '-1',
        'show_author'  => 'true',
        'show_program' => 'true',
        'show_awards'  => 'true',
    ), $atts, 'stagekitwp_past_shows' );
    $layout       = in_array( $atts['layout'], array('list','cards'), true ) ? $atts['layout'] : 'list';
    $limit        = intval( $atts['limit'] );
    $show_author  = ( 'false' !== $atts['show_author'] );
    $show_program = ( 'false' !== $atts['show_program'] );
    $show_awards  = ( 'false' !== $atts['show_awards'] );

    $bg  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_bg_color', '' ), '#ffffff' );
    $tx  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_text_color', '' ), '#1a1a1a' );
    $brd = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_border_color', '' ), '#dddddd' );
    $bw  = absint( get_option('stagekitwp_show_border_width','1') );
    $bdr = (bool) get_option('stagekitwp_show_rounded',false) ? absint( get_option('stagekitwp_show_radius','6') ) : 0;
    $shd = (bool) get_option('stagekitwp_show_shadow',false);
    $fnt = sanitize_text_field( get_option('stagekitwp_show_base_font','inherit') );
    $h2c = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_h2_color', '' ), '#1a1a1a' );
    $h3c = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_h3_color', '' ), '#555555' );
    stagekitwp_add_shortcode_inline_style('past-shows', stagekitwp_past_shows_css($bg,$tx,$brd,$bw,$bdr,$shd,$fnt,$h2c,$h3c));

    if ( function_exists('stagekitwp_dark_mode_admin_enabled') && stagekitwp_dark_mode_admin_enabled() ) {
        $ok = function($c){
            if(!$c){return '';} $h=ltrim($c,'#');
            if(strlen($h)===3){$h=$h[0].$h[0].$h[1].$h[1].$h[2].$h[2];}
            if(strlen($h)!==6){return '';}
            $l=0.2126*hexdec(substr($h,0,2))/255+0.7152*hexdec(substr($h,2,2))/255+0.0722*hexdec(substr($h,4,2))/255;
            return $l>=0.04?$c:'';
        };
        $d_bg=$ok(stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_bg_color_dark', '' ), '' ));
        $d_tx=$ok(stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_text_color_dark', '' ), '' ));
        $d_bd=$ok(stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_border_color_dark', '' ), '' ));
        $d_h2=$ok(stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_h2_color_dark', '' ), '' ));
        $d_h3=$ok(stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_h3_color_dark', '' ), '' ));
        if($d_bg||$d_tx||$d_bd||$d_h2||$d_h3){
            $dc="html.stagekitwp-dark-mode .stagekitwp-past-shows-block{\n";
            if($d_bg){$dc.="  --stagekitwp-ps-bg:{$d_bg}!important;\n";}
            if($d_tx){$dc.="  --stagekitwp-ps-text:{$d_tx}!important;\n";}
            if($d_bd){$dc.="  --stagekitwp-ps-border:{$d_bd}!important;\n";}
            if($d_h2){$dc.="  --stagekitwp-ps-h2:{$d_h2}!important;\n";}
            if($d_h3){$dc.="  --stagekitwp-ps-h3:{$d_h3}!important;\n";}
            $dc.="}\n";
            stagekitwp_add_shortcode_inline_style('past-shows-dark',$dc);
        }
    }

    // Load past seasons
    $all_ids = get_posts(array('post_type'=>'season','posts_per_page'=>-1,'post_status'=>'publish','fields'=>'ids'));
    if(empty($all_ids)){return '<div class="stagekitwp-past-shows-block"><p class="stagekitwp-ps-empty">No past seasons found.</p></div>';}
    $today_ts=$past_seasons=array();
    $today_ts=current_time('timestamp');
    foreach($all_ids as $sid){
        $st=get_post_meta($sid,'_stagekitwp_season_start_date',true);
        $en=get_post_meta($sid,'_stagekitwp_season_end_date',true);
        $s_ts=$st?strtotime($st):0; $e_ts=$en?strtotime($en)+86399:0;
        if($s_ts&&$s_ts>$today_ts){continue;}
        if($s_ts&&$e_ts&&$today_ts>=$s_ts&&$today_ts<=$e_ts){continue;}
        $past_seasons[]=array('id'=>$sid,'title'=>get_the_title($sid),'start_ts'=>$s_ts);
    }
    if(empty($past_seasons)){return '<div class="stagekitwp-past-shows-block"><p class="stagekitwp-ps-empty">No past seasons to display.</p></div>';}
    usort($past_seasons,function($a,$b){return $b['start_ts']-$a['start_ts'];});
    if($limit>0){$past_seasons=array_slice($past_seasons,0,$limit);}

    // Pre-load shows
    $sids=array_column($past_seasons,'id');
    $all_shows=get_posts(array('post_type'=>'show','posts_per_page'=>-1,'post_status'=>'publish',
        'meta_query'=>array(array('key'=>'_stagekitwp_show_season','value'=>$sids,'compare'=>'IN'))));

    // Pre-load awards
    $award_map=array();
    if($show_awards&&!empty($all_shows)){
        $sids2=wp_list_pluck($all_shows,'ID');
        $aw=get_posts(array('post_type'=>'award','posts_per_page'=>-1,'post_status'=>'publish',
            'meta_query'=>array(array('key'=>'_stagekitwp_award_show_id','value'=>$sids2,'compare'=>'IN'))));
        foreach($aw as $ap){
            $shid=intval(get_post_meta($ap->ID,'_stagekitwp_award_show_id',true));
            $stat=get_post_meta($ap->ID,'_stagekitwp_award_status',true);
            if(!isset($award_map[$shid])){$award_map[$shid]=array('win'=>false,'nom'=>false,'elig'=>false);}
            if('THEA Winner'===$stat){
                $award_map[$shid]['win']=true;
            } elseif('Not Eligible'===$stat) {
                $award_map[$shid]['elig']=true;
            } else {
                $award_map[$shid]['nom']=true;
            }
        }
    }

    // Index by season → slot
    $slot_order=stagekitwp_show_slot_order();
    $by_season=array();
    foreach($all_shows as $show){
        $sea=intval(get_post_meta($show->ID,'_stagekitwp_show_season',true));
        $slot=sanitize_text_field(get_post_meta($show->ID,'_stagekitwp_show_time_slot',true));
        $idx=isset($slot_order[$slot])?$slot_order[$slot]:999;
        $by_season[$sea][$idx][]=$show;
    }

    ob_start();
    echo '<div class="stagekitwp-past-shows-block">';
    if('cards'===$layout){
        echo '<div class="stagekitwp-ps-cards">';
        foreach($past_seasons as $season){
            $sid=$season['id'];
            if(empty($by_season[$sid])){continue;}
            ksort($by_season[$sid]); // ascending: Fall→Winter→Spring
            echo '<div class="stagekitwp-ps-card"><div class="stagekitwp-ps-card-header"><p class="stagekitwp-ps-card-season">'.esc_html($season['title']).'</p></div><div class="stagekitwp-ps-card-body">';
            foreach($by_season[$sid] as $slot_idx=>$shows){
                $sl=array_search($slot_idx,$slot_order,true);
                foreach($shows as $show){
                    $shid=$show->ID;
                    $pl=$show_program?stagekitwp_ps_prog_link($shid):'';
                    $bd=$show_awards?stagekitwp_ps_badges($shid,$award_map):'';
                    echo '<div class="stagekitwp-ps-card-show">';
                    echo '<span class="stagekitwp-ps-card-slot">'.(false!==$sl?esc_html($sl):'').'</span>';
                    $show_url = stagekitwp_show_page_url( $shid );
                    $t_esc = esc_html(get_the_title($shid));
                    $t_linked = $show_url ? '<a href="'.esc_url($show_url).'" style="color:inherit;text-decoration:none;">'.$t_esc.'</a>' : $t_esc;
                    echo '<span class="stagekitwp-ps-card-info"><span class="stagekitwp-ps-card-title">'.$t_linked.'</span>';
                    if($show_author){$as=stagekitwp_ps_author_str($shid);if($as){echo '<span class="stagekitwp-ps-card-author">'.esc_html($as).'</span>';}}
                    echo '</span><span class="stagekitwp-ps-card-meta">';
                    if($pl){echo '<span class="stagekitwp-ps-card-pdf">'.$pl.'</span>';}
                    echo $bd.'</span></div>';
                }
            }
            echo '</div></div>';
        }
        echo '</div>';
    } else {
        echo '<div class="stagekitwp-ps-list">';
        $alt=0;
        foreach($past_seasons as $season){
            $sid=$season['id'];
            if(empty($by_season[$sid])){continue;}
            krsort($by_season[$sid]);
            $ac=($alt%2===1)?' stagekitwp-ps-season-alt':'';
            $first=false; $alt++;
            foreach($by_season[$sid] as $slot_idx=>$shows){
                $sl=array_search($slot_idx,$slot_order,true);
                foreach($shows as $show){
                    $shid=$show->ID;
                    echo '<div class="stagekitwp-ps-row'.$ac.'">';
                    echo '<span class="stagekitwp-ps-cell stagekitwp-ps-col-season"><span class="stagekitwp-ps-season-desktop">'.($first?'':esc_html($season['title'])).'</span><span class="stagekitwp-ps-season-mobile">'.esc_html($season['title']).'</span></span>';
                    $first=true;
                    echo '<span class="stagekitwp-ps-cell stagekitwp-ps-col-slot" data-label="'.esc_attr__( 'Slot', 'stagekitwp-core' ).'">'.(false!==$sl?esc_html($sl):'').'</span>';
                    $show_url = stagekitwp_show_page_url( $shid );
                    $t_esc = esc_html(get_the_title($shid));
                    $t_linked = $show_url ? '<a href="'.esc_url($show_url).'" style="color:inherit;text-decoration:none;">'.$t_esc.'</a>' : $t_esc;
                    echo '<span class="stagekitwp-ps-cell stagekitwp-ps-col-title">'.$t_linked.'</span>';
                    if($show_author){echo '<span class="stagekitwp-ps-cell stagekitwp-ps-col-author" data-label="'.esc_attr__( 'Author', 'stagekitwp-core' ).'">'.esc_html(stagekitwp_ps_author_str($shid)).'</span>';}
                    if($show_program){echo '<span class="stagekitwp-ps-cell stagekitwp-ps-col-program" data-label="'.esc_attr__( 'Programme', 'stagekitwp-core' ).'">'.stagekitwp_ps_prog_link($shid).'</span>';}
                    if($show_awards){echo '<span class="stagekitwp-ps-cell stagekitwp-ps-col-awards" data-label="'.esc_attr__( 'Awards', 'stagekitwp-core' ).'">'.stagekitwp_ps_badges($shid,$award_map).'</span>';}
                    echo '</div>';
                }
            }
        }
        echo '</div>';
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode( 'stagekitwp_past_shows', 'stagekitwp_shortcode_past_shows' );