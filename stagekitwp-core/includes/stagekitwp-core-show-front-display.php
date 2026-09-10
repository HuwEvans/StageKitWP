<?php
/**
 * Show Front-end Display
 *
 * Controls what renders when a visitor opens a single Show CPT URL.
 *
 * Meta keys saved on each Show post:
 *   _stagekitwp_show_front_view        string  landing_page|season_shows|tickets|none
 *   _stagekitwp_show_lp_field_list     string  comma-separated field names
 *   _stagekitwp_show_lp_castcols       int     1-6
 *   _stagekitwp_show_lp_urlbutton      string  true|false
 *   _stagekitwp_show_lp_program_button string  true|false
 *   _stagekitwp_show_lp_buttonformat   string  default|modern|minimal|outline|gradient|
 *                                      prominent|success|ghost|glass
 *   _stagekitwp_show_lp_font           string  font slug (body font)
 *   _stagekitwp_show_lp_heading_font   string  font slug (heading font)
 *   _stagekitwp_show_lp_cast_font      string  font slug (cast names/roles/bios + heading)
 *   _stagekitwp_show_lp_heading_size   string  xs|sm|md|lg|xl|xxl
 *   _stagekitwp_show_lp_text_size      string  xs|sm|md|lg|xl|xxl
 *   _stagekitwp_show_lp_align          string  left|center|right|justify
 *   _stagekitwp_show_lp_season_banner  string  true|false
 *   _stagekitwp_show_lp_cast_size      string  xs|sm|md|lg|xl|xxl or raw CSS size
 *   _stagekitwp_show_lp_cast_style     string  normal|italic|bold|bold-italic
 *
 * Global fallback (wp_options):
 *   stagekitwp_default_show_view       string  landing_page|season_shows|tickets|none
 *
 * @package StageKitWP
 */

defined( 'ABSPATH' ) || exit;

// ─────────────────────────────────────────────────────────────────────────────
// 1. Constants / helpers
// ─────────────────────────────────────────────────────────────────────────────

define( 'STAGEKITWP_LP_DEFAULT_FIELDS',
    'show_name,show_image,author,director,producer,stage_manager,synopsis,show_dates,ticket_url,program_pdf,cast,venue'
);

/** All recognised field_list tokens with human labels (canonical order). */
function stagekitwp_lp_all_fields() {
    return [
        'show_name'          => 'Show Name',
        'show_image'         => 'Show Image',
        'author'             => 'Author / Playwright',
        'sub_authors'        => 'Sub-authors (Music / Lyrics / Book)',
        'director'           => 'Director',
        'associate_director' => 'Associate Director',
        'producer'           => 'Producer',
        'stage_manager'      => 'Stage Manager',
        'synopsis'           => 'Synopsis',
        'show_dates'         => 'Show Dates',
        'ticket_url'         => 'Ticket Link / Button',
        'program_pdf'         => 'Program PDF Link',
        'cast'               => 'Cast List (simple)',
        'castwithbio'        => 'Cast with Photos',
        'venue'              => 'Venue',
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. Meta box registration
// ─────────────────────────────────────────────────────────────────────────────

add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'stagekitwp_show_front_display',
        '&#x1F5A5; Front-end Display',
        'stagekitwp_show_front_display_meta_box',
        'show',
        'side',
        'high'
    );
} );

// ─────────────────────────────────────────────────────────────────────────────
// 3. Meta box render
// ─────────────────────────────────────────────────────────────────────────────

function stagekitwp_show_front_display_meta_box( $post ) {
    wp_nonce_field( 'stagekitwp_save_front_display', 'stagekitwp_front_display_nonce' );

    $view       = get_post_meta( $post->ID, '_stagekitwp_show_front_view',      true ) ?: 'landing_page';
    $lp_layout  = get_post_meta( $post->ID, '_stagekitwp_show_lp_layout',       true ) ?: 'card';
    $field_list = get_post_meta( $post->ID, '_stagekitwp_show_lp_field_list',   true ) ?: STAGEKITWP_LP_DEFAULT_FIELDS;
    $castcols   = (int) ( get_post_meta( $post->ID, '_stagekitwp_show_lp_castcols', true ) ?: 3 );
    $urlbutton  = get_post_meta( $post->ID, '_stagekitwp_show_lp_urlbutton',    true ) ?: 'false';
    $progbutton = get_post_meta( $post->ID, '_stagekitwp_show_lp_program_button', true ) ?: 'false';
    $btnfmt     = get_post_meta( $post->ID, '_stagekitwp_show_lp_buttonformat', true ) ?: 'default';
    $show_banner = get_post_meta( $post->ID, '_stagekitwp_show_lp_season_banner', true ) ?: 'false';
    $lp_font         = get_post_meta( $post->ID, '_stagekitwp_show_lp_font',         true ) ?: '';
    $lp_heading_font = get_post_meta( $post->ID, '_stagekitwp_show_lp_heading_font', true ) ?: '';
    $lp_cast_font    = get_post_meta( $post->ID, '_stagekitwp_show_lp_cast_font',    true ) ?: '';
    $lp_heading_size = get_post_meta( $post->ID, '_stagekitwp_show_lp_heading_size', true ) ?: '';
    $lp_text_size    = get_post_meta( $post->ID, '_stagekitwp_show_lp_text_size',    true ) ?: '';
    $lp_cast_size    = get_post_meta( $post->ID, '_stagekitwp_show_lp_cast_size',    true ) ?: '';
    $lp_cast_bio_size= get_post_meta( $post->ID, '_stagekitwp_show_lp_cast_bio_size',true ) ?: '';
    $lp_cast_style   = get_post_meta( $post->ID, '_stagekitwp_show_lp_cast_style',   true ) ?: '';
    $lp_align        = get_post_meta( $post->ID, '_stagekitwp_show_lp_align',        true ) ?: '';
    $active_fields = array_map( 'trim', explode( ',', $field_list ) );

    // Per-show heading overrides
    $heading_keys = [ 'author','sub_authors','director','assoc_dir','producer','stage_manager','synopsis','show_dates','program_pdf','venue','cast' ];
    $headings = [];
    foreach ( $heading_keys as $hk ) {
        $headings[ $hk ] = get_post_meta( $post->ID, '_stagekitwp_lp_heading_' . $hk, true ) ?: '';
    }

    $views = [
        'landing_page'  => '&#x1F3AD; Landing Page',
        'season_shows'  => '&#x1F4C5; Season Shows',
        'tickets'       => '&#x1F3DF; Tickets',
        'none'          => '&mdash; WordPress default',
    ];
    $button_formats = [
        'default' => 'Default', 'modern' => 'Modern', 'minimal' => 'Minimal',
        'outline' => 'Outline', 'gradient' => 'Gradient', 'prominent' => 'Prominent',
        'success' => 'Success (green)', 'ghost' => 'Ghost', 'glass' => 'Glass',
    ];
    $gd = get_option( 'stagekitwp_default_show_view', 'landing_page' );
    ?>
    <style>
    #stagekitwp_show_front_display .stagekitwp-fd-view{display:block;margin:4px 0;cursor:pointer;}
    #stagekitwp_show_front_display .stagekitwp-fd-box{margin-top:10px;border-top:1px solid #ddd;padding-top:10px;}
    #stagekitwp_show_front_display .stagekitwp-fd-hint{margin:0 0 8px;color:#666;font-size:11px;}
    #stagekitwp_show_front_display .stagekitwp-fd-fields{columns:2;margin:4px 0 10px;}
    #stagekitwp_show_front_display .stagekitwp-fd-fields label{display:block;font-size:12px;margin:2px 0;break-inside:avoid;}
    #stagekitwp_show_front_display .stagekitwp-fd-row{display:flex;align-items:center;gap:8px;margin:6px 0;font-size:12px;}
    #stagekitwp_show_front_display .stagekitwp-fd-row > label:first-child{min-width:88px;}
    #stagekitwp_show_front_display .stagekitwp-fd-note{margin-top:10px;border-top:1px solid #ddd;padding-top:10px;font-size:11px;color:#666;font-style:italic;}
    #stagekitwp_show_front_display .stagekitwp-fd-headings-grid{display:grid;grid-template-columns:1fr 1fr;gap:4px 10px;margin:6px 0;}
    #stagekitwp_show_front_display .stagekitwp-fd-headings-grid label{font-size:11px;color:#555;display:block;margin:0;}
    #stagekitwp_show_front_display .stagekitwp-fd-headings-grid input{width:100%;font-size:11px;padding:2px 4px;margin:1px 0 4px;box-sizing:border-box;}
    </style>

    <p style="margin:0 0 8px;font-size:12px;color:#555;">
        What visitors see at this show&#8217;s URL.<br>
        Global default: <strong><?php echo esc_html( strip_tags( $views[ $gd ] ?? $gd ) ); ?></strong>
    </p>

    <?php foreach ( $views as $val => $label ) : ?>
        <label class="stagekitwp-fd-view">
            <input type="radio" name="stagekitwp_show_front_view"
                   value="<?php echo esc_attr( $val ); ?>" <?php checked( $view, $val ); ?>>
            <?php echo wp_kses_post( $label ); ?>
        </label>
    <?php endforeach; ?>

    <?php /* Landing Page options */ ?>
    <div class="stagekitwp-fd-box" id="stagekitwp-fd-lp"<?php echo $view !== 'landing_page' ? ' style="display:none"' : ''; ?>>

        <div class="stagekitwp-fd-row">
            <label for="stagekitwp_lp_layout" style="min-width:54px;">Layout:</label>
            <select id="stagekitwp_lp_layout" name="stagekitwp_show_lp_layout" style="flex:1">
            <?php
            $layouts = [ 'card'=>'Two-Column Card', 'hero'=>'Hero Banner', 'programme'=>'Programme', 'minimal'=>'Minimal' ];
            foreach ( $layouts as $lv => $ll ) :
            ?><option value="<?php echo esc_attr($lv); ?>" <?php selected($lp_layout,$lv); ?>><?php echo esc_html($ll); ?></option><?php endforeach; ?>
            </select>
        </div>

        <div class="stagekitwp-fd-row" id="stagekitwp-fd-banner-row"<?php echo in_array($lp_layout,['hero','programme'],true) ? '' : ' style="display:none"'; ?>>
            <label style="min-width:54px;">Season banner:</label>
            <label><input type="checkbox" name="stagekitwp_show_lp_season_banner" value="true"
                <?php checked( $show_banner, 'true' ); ?>> Show at bottom</label>
        </div>

        <div class="stagekitwp-fd-row">
            <label for="stagekitwp_lp_font" style="min-width:54px;">Font:</label>
            <select id="stagekitwp_lp_font" name="stagekitwp_show_lp_font" style="flex:1" onchange="stagekitwpFdUpdateFontPreview(this)">
            <option value=""><?php echo esc_html( '(Global / theme default)' ); ?></option>
            <?php
            if ( function_exists( 'stagekitwp_lp_full_font_list' ) ) {
                foreach ( stagekitwp_lp_full_font_list() as $fslug => $fdata ) :
                    if ( $fslug === 'inherit' ) continue;
                    $fstack = esc_attr( $fdata['stack'] );
            ?><option value="<?php echo esc_attr($fslug); ?>" <?php selected($lp_font,$fslug); ?>
                      style="font-family:<?php echo $fstack; ?>" data-stack="<?php echo $fstack; ?>"><?php echo esc_html($fdata['label']); ?></option><?php
                endforeach;
            }
            ?>
            </select>
        </div>
        <?php
        $preview_stack = '';
        if ( $lp_font && function_exists('stagekitwp_lp_font_stack') ) { $preview_stack = stagekitwp_lp_font_stack($lp_font); }
        ?>
        <div id="stagekitwp-fd-font-preview" style="margin:4px 0 8px;padding:5px 8px;background:#f9f9f9;border:1px solid #ddd;border-radius:3px;font-size:13px;font-family:<?php echo esc_attr($preview_stack ?: 'inherit'); ?>">
            A Night at the Theatre &mdash; <em>The Show Must Go On</em>
        </div>
        <script>
        function stagekitwpFdUpdateFontPreview(sel) {
            var preview = document.getElementById('stagekitwp-fd-font-preview');
            if (!preview) return;
            var opt = sel.options[sel.selectedIndex];
            var stack = (opt && opt.dataset.stack) ? opt.dataset.stack : 'inherit';
            preview.style.fontFamily = stack;
        }
        </script>

        <div class="stagekitwp-fd-row">
            <label for="stagekitwp_lp_heading_font" style="min-width:54px;">H Font:</label>
            <select id="stagekitwp_lp_heading_font" name="stagekitwp_show_lp_heading_font" style="flex:1" title="Heading font (overrides body font for titles only)">
            <option value=""><?php echo esc_html( '(Same as body font)' ); ?></option>
            <?php
            if ( function_exists( 'stagekitwp_lp_full_font_list' ) ) {
                foreach ( stagekitwp_lp_full_font_list() as $fslug => $fdata ) :
                    if ( $fslug === 'inherit' ) continue;
                    $fstack = esc_attr( $fdata['stack'] );
            ?><option value="<?php echo esc_attr($fslug); ?>" <?php selected($lp_heading_font,$fslug); ?>
                      style="font-family:<?php echo $fstack; ?>" data-stack="<?php echo $fstack; ?>"><?php echo esc_html($fdata['label']); ?></option><?php
                endforeach;
            }
            ?>
            </select>
        </div>

        <div class="stagekitwp-fd-row">
            <label for="stagekitwp_lp_cast_font" style="min-width:54px;">Cast Font:</label>
            <select id="stagekitwp_lp_cast_font" name="stagekitwp_show_lp_cast_font" style="flex:1" title="Font applied to cast names, roles, bios, and the cast section heading">
            <option value=""><?php echo esc_html( '(Same as body font)' ); ?></option>
            <?php
            if ( function_exists( 'stagekitwp_lp_full_font_list' ) ) {
                foreach ( stagekitwp_lp_full_font_list() as $fslug => $fdata ) :
                    if ( $fslug === 'inherit' ) continue;
                    $fstack = esc_attr( $fdata['stack'] );
            ?><option value="<?php echo esc_attr($fslug); ?>" <?php selected($lp_cast_font,$fslug); ?>
                      style="font-family:<?php echo $fstack; ?>" data-stack="<?php echo $fstack; ?>"><?php echo esc_html($fdata['label']); ?></option><?php
                endforeach;
            }
            ?>
            </select>
        </div>

        <?php
        $size_opts = [ '' => 'Default', 'xs' => 'XS', 'sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large', 'xl' => 'XL', 'xxl' => 'XXL' ];
        ?>
        <div class="stagekitwp-fd-row">
            <label style="min-width:54px;">H Size:</label>
            <div style="display:flex;gap:4px;flex-wrap:wrap;flex:1">
            <?php foreach ( $size_opts as $sv => $sl ) : ?>
                <label style="display:flex;align-items:center;gap:3px;cursor:pointer;">
                    <input type="radio" name="stagekitwp_show_lp_heading_size" value="<?php echo esc_attr($sv); ?>" <?php checked($lp_heading_size,$sv); ?>>
                    <span><?php echo esc_html($sl); ?></span>
                </label>
            <?php endforeach; ?>
            </div>
        </div>

        <div class="stagekitwp-fd-row">
            <label style="min-width:54px;">Text Size:</label>
            <div style="display:flex;gap:4px;flex-wrap:wrap;flex:1">
            <?php foreach ( $size_opts as $sv => $sl ) : ?>
                <label style="display:flex;align-items:center;gap:3px;cursor:pointer;">
                    <input type="radio" name="stagekitwp_show_lp_text_size" value="<?php echo esc_attr($sv); ?>" <?php checked($lp_text_size,$sv); ?>>
                    <span><?php echo esc_html($sl); ?></span>
                </label>
            <?php endforeach; ?>
            </div>
        </div>

        <div class="stagekitwp-fd-row">
            <label style="min-width:54px;">Cast Size:</label>
            <div style="display:flex;gap:4px;flex-wrap:wrap;flex:1">
            <?php foreach ( $size_opts as $sv => $sl ) : ?>
                <label style="display:flex;align-items:center;gap:3px;cursor:pointer;">
                    <input type="radio" name="stagekitwp_show_lp_cast_size" value="<?php echo esc_attr($sv); ?>" <?php checked($lp_cast_size,$sv); ?>>
                    <span><?php echo esc_html($sl); ?></span>
                </label>
            <?php endforeach; ?>
            </div>
        </div>

        <div class="stagekitwp-fd-row">
            <label style="min-width:54px;">Bio Size:</label>
            <div style="display:flex;gap:4px;flex-wrap:wrap;flex:1">
            <?php foreach ( $size_opts as $sv => $sl ) : ?>
                <label style="display:flex;align-items:center;gap:3px;cursor:pointer;">
                    <input type="radio" name="stagekitwp_show_lp_cast_bio_size" value="<?php echo esc_attr($sv); ?>" <?php checked($lp_cast_bio_size,$sv); ?>>
                    <span><?php echo esc_html($sl); ?></span>
                </label>
            <?php endforeach; ?>
            </div>
        </div>

        <div class="stagekitwp-fd-row">
            <label style="min-width:54px;">Cast Style:</label>
            <div style="display:flex;gap:6px;flex-wrap:wrap;flex:1">
            <?php
            $cast_style_opts = [ '' => 'Default', 'normal' => 'Normal', 'italic' => 'Italic', 'bold' => 'Bold', 'bold-italic' => 'Bold Italic' ];
            foreach ( $cast_style_opts as $csv => $csl ) :
            ?><label style="display:flex;align-items:center;gap:3px;cursor:pointer;">
                <input type="radio" name="stagekitwp_show_lp_cast_style" value="<?php echo esc_attr($csv); ?>" <?php checked($lp_cast_style,$csv); ?>>
                <span><?php echo esc_html($csl); ?></span>
            </label>
            <?php endforeach; ?>
            </div>
        </div>

        <div class="stagekitwp-fd-row" style="margin-top:8px;">
            <label for="stagekitwp_lp_align" style="min-width:54px;">Align:</label>
            <div style="display:flex;gap:6px;flex:1">
            <?php
            $align_opts = [
                ''        => [ 'icon' => '&#x2014;',     'title' => 'Default (left)' ],
                'left'    => [ 'icon' => '&#x2630;',     'title' => 'Left' ],
                'center'  => [ 'icon' => '&#x2261;',     'title' => 'Centre' ],
                'right'   => [ 'icon' => '&#x2630;',     'title' => 'Right' ],
                'justify' => [ 'icon' => '&#x2630;',     'title' => 'Justify' ],
            ];
            foreach ( $align_opts as $av => $ao ) :
            ?><label style="display:flex;align-items:center;gap:4px;cursor:pointer;">
                <input type="radio" name="stagekitwp_show_lp_align" value="<?php echo esc_attr($av); ?>" <?php checked($lp_align,$av); ?>>
                <span title="<?php echo esc_attr($ao['title']); ?>"><?php echo esc_html($ao['title']); ?></span>
            </label>
            <?php endforeach; ?>
            </div>
        </div>

        <p class="stagekitwp-fd-hint" style="margin-top:8px;">Tick the fields to display. Order follows the canonical sequence.</p>
        <strong style="font-size:12px;">Fields:</strong>
        <div class="stagekitwp-fd-fields">
        <?php foreach ( stagekitwp_lp_all_fields() as $key => $lbl ) : ?>
            <label>
                <input type="checkbox" name="stagekitwp_show_lp_fields[]"
                       value="<?php echo esc_attr( $key ); ?>"
                       <?php checked( in_array( $key, $active_fields, true ) ); ?>>
                <?php echo esc_html( $lbl ); ?>
            </label>
        <?php endforeach; ?>
        </div>

        <div class="stagekitwp-fd-row">
            <label for="stagekitwp_lp_castcols">Cast columns:</label>
            <input type="number" id="stagekitwp_lp_castcols" name="stagekitwp_show_lp_castcols"
                   value="<?php echo esc_attr( $castcols ); ?>" min="1" max="6" style="width:48px;">
        </div>

        <div class="stagekitwp-fd-row">
            <label>Ticket link:</label>
            <label><input type="radio" name="stagekitwp_show_lp_urlbutton" value="false"
                   <?php checked( $urlbutton, 'false' ); ?>> Plain link</label>
            <label><input type="radio" name="stagekitwp_show_lp_urlbutton" value="true"
                   <?php checked( $urlbutton, 'true' ); ?>> Button</label>
        </div>

         <div class="stagekitwp-fd-row">
             <label>Program button:</label>
             <label><input type="radio" name="stagekitwp_show_lp_program_button" value="false"
                 <?php checked( $progbutton, 'false' ); ?>> Off</label>
             <label><input type="radio" name="stagekitwp_show_lp_program_button" value="true"
                 <?php checked( $progbutton, 'true' ); ?>> On</label>
         </div>

        <div class="stagekitwp-fd-row" id="stagekitwp-fd-btnfmt"<?php echo $urlbutton !== 'true' ? ' style="display:none"' : ''; ?>>
            <label for="stagekitwp_lp_btnfmt">Button style:</label>
            <select id="stagekitwp_lp_btnfmt" name="stagekitwp_show_lp_buttonformat" style="flex:1">
            <?php foreach ( $button_formats as $bv => $bl ) : ?>
                <option value="<?php echo esc_attr( $bv ); ?>" <?php selected( $btnfmt, $bv ); ?>>
                    <?php echo esc_html( $bl ); ?></option>
            <?php endforeach; ?>
            </select>
        </div>

        <div class="stagekitwp-fd-box" id="stagekitwp-fd-headings">
            <strong style="font-size:12px;">Section Headings <span style="font-weight:400;color:#666;">(leave blank to use global/default)</span></strong>
            <?php
            $heading_labels = [
                'author'        => 'Written by',
                'sub_authors'   => 'Music / Lyrics / Book',
                'director'      => 'Directed by',
                'assoc_dir'     => 'Associate Director',
                'producer'      => 'Produced by',
                'stage_manager' => 'Stage Manager',
                'synopsis'      => 'Synopsis',
                'show_dates'    => 'Performances',
                'program_pdf'   => 'Programme',
                'venue'         => 'Venue',
                'cast'          => 'Cast',
            ];
            ?>
            <div class="stagekitwp-fd-headings-grid">
            <?php foreach ( $heading_labels as $hk => $default ) :
                $ph = esc_attr( get_option( 'stagekitwp_lp_heading_' . $hk, $default ) );
            ?>
                <div>
                    <label for="stagekitwp_lp_h_<?php echo esc_attr($hk); ?>"><?php echo esc_html($default); ?></label>
                    <input type="text" id="stagekitwp_lp_h_<?php echo esc_attr($hk); ?>"
                           name="stagekitwp_lp_headings[<?php echo esc_attr($hk); ?>]"
                           value="<?php echo esc_attr( $headings[$hk] ?? '' ); ?>"
                           placeholder="<?php echo $ph; ?>">
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>

    <p class="stagekitwp-fd-note" id="stagekitwp-fd-ss"<?php echo $view !== 'season_shows' ? ' style="display:none"' : ''; ?>>
        Renders <code>[stagekitwp_season_shows]</code> for the season this show belongs to.
        Colours: <strong>Display Options &rarr; Season / Show</strong>.
    </p>
    <p class="stagekitwp-fd-note" id="stagekitwp-fd-tk"<?php echo $view !== 'tickets' ? ' style="display:none"' : ''; ?>>
        Renders <code>[stagekitwp_tickets]</code> for the current season.
        Colours: <strong>Display Options &rarr; Tickets</strong>.
    </p>

    <script>
    (function(){
        var radios     = document.querySelectorAll('[name="stagekitwp_show_front_view"]');
        var lpBox      = document.getElementById('stagekitwp-fd-lp');
        var ssNote     = document.getElementById('stagekitwp-fd-ss');
        var tkNote     = document.getElementById('stagekitwp-fd-tk');
        var btnR       = document.querySelectorAll('[name="stagekitwp_show_lp_urlbutton"]');
        var fmtRow     = document.getElementById('stagekitwp-fd-btnfmt');
        var layoutSel  = document.getElementById('stagekitwp_lp_layout');
        var bannerRow  = document.getElementById('stagekitwp-fd-banner-row');
        function showView(v){
            lpBox.style.display  = v==='landing_page' ? '' : 'none';
            ssNote.style.display = v==='season_shows' ? '' : 'none';
            tkNote.style.display = v==='tickets'      ? '' : 'none';
        }
        function showLayout(v){
            if (bannerRow) bannerRow.style.display = (v==='hero'||v==='programme') ? '' : 'none';
        }
        radios.forEach(function(r){r.addEventListener('change',function(){showView(this.value);});});
        btnR.forEach(function(r){r.addEventListener('change',function(){
            fmtRow.style.display = this.value==='true' ? '' : 'none';
        });});
        if (layoutSel) layoutSel.addEventListener('change',function(){showLayout(this.value);});
    })();
    </script>
    <?php
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. Save meta
// ─────────────────────────────────────────────────────────────────────────────

add_action( 'save_post_show', function( $post_id ) {
    if ( ! isset( $_POST['stagekitwp_front_display_nonce'] ) ) { return; }
    $nonce = sanitize_text_field( wp_unslash( $_POST['stagekitwp_front_display_nonce'] ) );
    if ( ! $nonce || ! wp_verify_nonce( $nonce, 'stagekitwp_save_front_display' ) ) { return; }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
    if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

    $post_data = wp_unslash( $_POST );

    $allowed_views   = [ 'landing_page', 'season_shows', 'tickets', 'none' ];
    $allowed_layouts = [ 'card', 'hero', 'programme', 'minimal' ];
    $allowed_fmts    = [ 'default','modern','minimal','outline','gradient','prominent','success','ghost','glass' ];
    $allowed_fields  = array_keys( stagekitwp_lp_all_fields() );
    $allowed_hkeys   = [ 'author','sub_authors','director','assoc_dir','producer','stage_manager','synopsis','show_dates','program_pdf','venue','cast' ];

    $view = sanitize_key( $post_data['stagekitwp_show_front_view'] ?? 'landing_page' );
    if ( ! in_array( $view, $allowed_views, true ) ) { $view = 'landing_page'; }
    update_post_meta( $post_id, '_stagekitwp_show_front_view', $view );

    $lp_layout = sanitize_key( $post_data['stagekitwp_show_lp_layout'] ?? 'card' );
    if ( ! in_array( $lp_layout, $allowed_layouts, true ) ) { $lp_layout = 'card'; }
    update_post_meta( $post_id, '_stagekitwp_show_lp_layout', $lp_layout );

    $show_banner = ( ( $post_data['stagekitwp_show_lp_season_banner'] ?? '' ) === 'true' ) ? 'true' : 'false';
    update_post_meta( $post_id, '_stagekitwp_show_lp_season_banner', $show_banner );

    // Field list — filter against whitelist, preserve canonical order
    $submitted = isset( $post_data['stagekitwp_show_lp_fields'] ) ? (array) $post_data['stagekitwp_show_lp_fields'] : [];
    $clean = [];
    foreach ( $allowed_fields as $f ) {
        if ( in_array( $f, $submitted, true ) ) { $clean[] = $f; }
    }
    update_post_meta( $post_id, '_stagekitwp_show_lp_field_list',
        ! empty( $clean ) ? implode( ',', $clean ) : STAGEKITWP_LP_DEFAULT_FIELDS );

    $castcols = max( 1, min( 6, intval( $post_data['stagekitwp_show_lp_castcols'] ?? 3 ) ) );
    update_post_meta( $post_id, '_stagekitwp_show_lp_castcols', $castcols );

    $urlbutton = ( ( $post_data['stagekitwp_show_lp_urlbutton'] ?? '' ) === 'true' ) ? 'true' : 'false';
    update_post_meta( $post_id, '_stagekitwp_show_lp_urlbutton', $urlbutton );

    $progbutton = ( ( $post_data['stagekitwp_show_lp_program_button'] ?? '' ) === 'true' ) ? 'true' : 'false';
    update_post_meta( $post_id, '_stagekitwp_show_lp_program_button', $progbutton );

    $btnfmt = sanitize_key( $post_data['stagekitwp_show_lp_buttonformat'] ?? 'default' );
    if ( ! in_array( $btnfmt, $allowed_fmts, true ) ) { $btnfmt = 'default'; }
    update_post_meta( $post_id, '_stagekitwp_show_lp_buttonformat', $btnfmt );

    // Body font
    $allowed_fonts = function_exists( 'stagekitwp_lp_full_font_list' ) ? array_keys( stagekitwp_lp_full_font_list() ) : ( function_exists( 'stagekitwp_lp_font_list' ) ? array_keys( stagekitwp_lp_font_list() ) : [] );
    $lp_font = sanitize_key( $post_data['stagekitwp_show_lp_font'] ?? '' );
    if ( $lp_font !== '' && ! in_array( $lp_font, $allowed_fonts, true ) ) { $lp_font = ''; }
    if ( $lp_font !== '' ) {
        update_post_meta( $post_id, '_stagekitwp_show_lp_font', $lp_font );
    } else {
        delete_post_meta( $post_id, '_stagekitwp_show_lp_font' );
    }

    // Heading font
    $lp_hfont = sanitize_key( $post_data['stagekitwp_show_lp_heading_font'] ?? '' );
    if ( $lp_hfont !== '' && ! in_array( $lp_hfont, $allowed_fonts, true ) ) { $lp_hfont = ''; }
    if ( $lp_hfont !== '' ) {
        update_post_meta( $post_id, '_stagekitwp_show_lp_heading_font', $lp_hfont );
    } else {
        delete_post_meta( $post_id, '_stagekitwp_show_lp_heading_font' );
    }

    // Cast font
    $lp_cfont = sanitize_key( $post_data['stagekitwp_show_lp_cast_font'] ?? '' );
    if ( $lp_cfont !== '' && ! in_array( $lp_cfont, $allowed_fonts, true ) ) { $lp_cfont = ''; }
    if ( $lp_cfont !== '' ) {
        update_post_meta( $post_id, '_stagekitwp_show_lp_cast_font', $lp_cfont );
    } else {
        delete_post_meta( $post_id, '_stagekitwp_show_lp_cast_font' );
    }

    // Cast size, cast bio size, cast style
    $allowed_sizes = [ '', 'xs', 'sm', 'md', 'lg', 'xl', 'xxl' ];
    $lp_csize = sanitize_key( $post_data['stagekitwp_show_lp_cast_size'] ?? '' );
    if ( ! in_array( $lp_csize, $allowed_sizes, true ) ) { $lp_csize = ''; }
    if ( $lp_csize !== '' ) { update_post_meta( $post_id, '_stagekitwp_show_lp_cast_size', $lp_csize ); }
    else                    { delete_post_meta( $post_id, '_stagekitwp_show_lp_cast_size' ); }

    $lp_cbsize = sanitize_key( $post_data['stagekitwp_show_lp_cast_bio_size'] ?? '' );
    if ( ! in_array( $lp_cbsize, $allowed_sizes, true ) ) { $lp_cbsize = ''; }
    if ( $lp_cbsize !== '' ) { update_post_meta( $post_id, '_stagekitwp_show_lp_cast_bio_size', $lp_cbsize ); }
    else                     { delete_post_meta( $post_id, '_stagekitwp_show_lp_cast_bio_size' ); }

    $allowed_cast_styles = [ 'normal', 'italic', 'bold', 'bold-italic' ];
    $lp_cstyle = sanitize_key( $post_data['stagekitwp_show_lp_cast_style'] ?? '' );
    if ( $lp_cstyle !== '' && ! in_array( $lp_cstyle, $allowed_cast_styles, true ) ) { $lp_cstyle = ''; }
    if ( $lp_cstyle !== '' ) { update_post_meta( $post_id, '_stagekitwp_show_lp_cast_style', $lp_cstyle ); }
    else                     { delete_post_meta( $post_id, '_stagekitwp_show_lp_cast_style' ); }

    // Heading + text sizes ($allowed_sizes already defined above)
    $lp_hsize = sanitize_key( $post_data['stagekitwp_show_lp_heading_size'] ?? '' );
    if ( ! in_array( $lp_hsize, $allowed_sizes, true ) ) { $lp_hsize = ''; }
    if ( $lp_hsize !== '' ) { update_post_meta( $post_id, '_stagekitwp_show_lp_heading_size', $lp_hsize ); }
    else                    { delete_post_meta( $post_id, '_stagekitwp_show_lp_heading_size' ); }

    $lp_tsize = sanitize_key( $post_data['stagekitwp_show_lp_text_size'] ?? '' );
    if ( ! in_array( $lp_tsize, $allowed_sizes, true ) ) { $lp_tsize = ''; }
    if ( $lp_tsize !== '' ) { update_post_meta( $post_id, '_stagekitwp_show_lp_text_size', $lp_tsize ); }
    else                    { delete_post_meta( $post_id, '_stagekitwp_show_lp_text_size' ); }

    // Alignment
    $allowed_aligns = [ 'left', 'center', 'right', 'justify' ];
    $lp_align = sanitize_key( $post_data['stagekitwp_show_lp_align'] ?? '' );
    if ( $lp_align !== '' && ! in_array( $lp_align, $allowed_aligns, true ) ) { $lp_align = ''; }
    if ( $lp_align !== '' ) {
        update_post_meta( $post_id, '_stagekitwp_show_lp_align', $lp_align );
    } else {
        delete_post_meta( $post_id, '_stagekitwp_show_lp_align' );
    }

    // Per-show heading overrides
    $submitted_headings = isset( $post_data['stagekitwp_lp_headings'] ) ? (array) $post_data['stagekitwp_lp_headings'] : [];
    foreach ( $allowed_hkeys as $hk ) {
        $val = sanitize_text_field( $submitted_headings[ $hk ] ?? '' );
        if ( $val !== '' ) {
            update_post_meta( $post_id, '_stagekitwp_lp_heading_' . $hk, $val );
        } else {
            delete_post_meta( $post_id, '_stagekitwp_lp_heading_' . $hk );
        }
    }
} );

// ─────────────────────────────────────────────────────────────────────────────
// 5. Settings — Global default
// ─────────────────────────────────────────────────────────────────────────────

add_action( 'admin_init', function() {
    add_settings_section(
        'stagekitwp_show_front_display_section',
        'Show Front-end Display',
        function() {
            echo '<p class="description">Default view when a visitor opens a Show URL '
               . 'and the show has no per-show override set.</p>';
        },
        'stagekitwp-settings'
    );
    add_settings_field(
        'stagekitwp_default_show_view', 'Default Show View',
        'stagekitwp_default_show_view_callback',
        'stagekitwp-settings', 'stagekitwp_show_front_display_section'
    );
    register_setting( 'stagekitwp_settings_group', 'stagekitwp_default_show_view', 'sanitize_key' );
} );

function stagekitwp_default_show_view_callback() {
    $val  = get_option( 'stagekitwp_default_show_view', 'landing_page' );
    $opts = [
        'landing_page' => 'Landing Page',
        'season_shows' => 'Season Shows',
        'tickets'      => 'Tickets',
        'none'         => 'WordPress default (show post content)',
    ];
    echo '<select name="stagekitwp_default_show_view" id="stagekitwp_default_show_view">';
    foreach ( $opts as $k => $lbl ) {
        printf( '<option value="%s"%s>%s</option>',
            esc_attr( $k ), selected( $val, $k, false ), esc_html( $lbl ) );
    }
    echo '</select>';
    echo '<p class="description">Per-show overrides (set on each Show edit screen) take priority.</p>';
}

// ─────────────────────────────────────────────────────────────────────────────
// 6. Template redirect — intercept single Show URLs
// ─────────────────────────────────────────────────────────────────────────────

add_action( 'template_redirect', 'stagekitwp_show_front_display_redirect' );

function stagekitwp_show_front_display_redirect() {
    if ( is_admin() || ! is_singular( 'show' ) ) { return; }

    // get_the_ID() is 0 at template_redirect; use get_queried_object_id() instead.
    $post_id = get_queried_object_id();
    if ( ! $post_id ) { return; }

    // Resolve view: per-show meta > global option > factory default 'landing_page'
    $view = get_post_meta( $post_id, '_stagekitwp_show_front_view', true );
    if ( empty( $view ) ) {
        $view = get_option( 'stagekitwp_default_show_view', 'landing_page' );
    }
    if ( $view === 'none' || empty( $view ) ) { return; }

    $shortcode = stagekitwp_show_front_build_shortcode( $view, $post_id );
    if ( ! $shortcode ) { return; }

    // Replace the_content for this request only.
    add_filter( 'the_content', function( $content ) use ( $shortcode, $post_id ) {
        if ( ! in_the_loop() || ! is_main_query() ) { return $content; }
        if ( get_the_ID() !== $post_id ) { return $content; }
        return do_shortcode( $shortcode );
    }, 1 );

    // NOTE: no 'the_title' filter here — single-show.php renders no title of its own,
    // and filtering get_the_title() during the loop also blanked the shortcode's own
    // internal show_name lookup, causing the landing page title to disappear.
}

/**
 * Build the shortcode string for a given view + show post ID.
 *
 * @param string $view    One of: landing_page, season_shows, tickets
 * @param int    $show_id Show post ID
 * @return string  Ready-to-pass-to-do_shortcode string, or '' to abort.
 */
function stagekitwp_show_front_build_shortcode( $view, $show_id ) {
    switch ( $view ) {

        case 'landing_page':
            $field_list  = get_post_meta( $show_id, '_stagekitwp_show_lp_field_list',   true ) ?: STAGEKITWP_LP_DEFAULT_FIELDS;
            $castcols    = (int) ( get_post_meta( $show_id, '_stagekitwp_show_lp_castcols', true ) ?: 3 );
            $urlbutton   = get_post_meta( $show_id, '_stagekitwp_show_lp_urlbutton',    true ) ?: 'false';
            $progbutton  = get_post_meta( $show_id, '_stagekitwp_show_lp_program_button', true ) ?: 'false';
            $btnfmt      = get_post_meta( $show_id, '_stagekitwp_show_lp_buttonformat', true ) ?: 'default';
            $lp_layout   = get_post_meta( $show_id, '_stagekitwp_show_lp_layout',        true ) ?: 'card';
            $show_banner = get_post_meta( $show_id, '_stagekitwp_show_lp_season_banner', true ) ?: 'false';
            $lp_font      = get_post_meta( $show_id, '_stagekitwp_show_lp_font',         true ) ?: '';
            $lp_hfont     = get_post_meta( $show_id, '_stagekitwp_show_lp_heading_font',  true ) ?: '';
            $lp_cfont     = get_post_meta( $show_id, '_stagekitwp_show_lp_cast_font',     true ) ?: '';
            $lp_hsize     = get_post_meta( $show_id, '_stagekitwp_show_lp_heading_size',  true ) ?: '';
            $lp_tsize     = get_post_meta( $show_id, '_stagekitwp_show_lp_text_size',     true ) ?: '';
            $lp_align     = get_post_meta( $show_id, '_stagekitwp_show_lp_align',         true ) ?: '';
            $lp_csize     = get_post_meta( $show_id, '_stagekitwp_show_lp_cast_size',     true ) ?: '';
            $lp_cbsize    = get_post_meta( $show_id, '_stagekitwp_show_lp_cast_bio_size',  true ) ?: '';
            $lp_cstyle    = get_post_meta( $show_id, '_stagekitwp_show_lp_cast_style',     true ) ?: '';
            $font_part    = $lp_font   ? ' font="'          . esc_attr( $lp_font )   . '"' : '';
            $hfont_part   = $lp_hfont  ? ' heading_font="'  . esc_attr( $lp_hfont )  . '"' : '';
            $cfont_part   = $lp_cfont  ? ' cast_font="'     . esc_attr( $lp_cfont )  . '"' : '';
            $hsize_part   = $lp_hsize  ? ' heading_size="'  . esc_attr( $lp_hsize )  . '"' : '';
            $tsize_part   = $lp_tsize  ? ' text_size="'     . esc_attr( $lp_tsize )  . '"' : '';
            $align_part   = $lp_align  ? ' align="'         . esc_attr( $lp_align )  . '"' : '';
            $csize_part   = $lp_csize  ? ' cast_size="'     . esc_attr( $lp_csize )  . '"' : '';
            $cbsize_part  = $lp_cbsize ? ' cast_bio_size="' . esc_attr( $lp_cbsize ) . '"' : '';
            $cstyle_part  = $lp_cstyle ? ' cast_style="'    . esc_attr( $lp_cstyle ) . '"' : '';
            return sprintf(
                '[stagekitwp_landingpage show_id="%d" layout="%s" field_list="%s" castcols="%d" urlbutton="%s" program_button="%s" buttonformat="%s" show_season_banner="%s"%s%s%s%s%s%s%s%s%s]',
                $show_id,
                esc_attr( $lp_layout ),
                esc_attr( $field_list ),
                $castcols,
                esc_attr( $urlbutton ),
                esc_attr( $progbutton ),
                esc_attr( $btnfmt ),
                esc_attr( $show_banner ),
                $font_part,
                $hfont_part,
                $cfont_part,
                $hsize_part,
                $tsize_part,
                $align_part,
                $csize_part,
                $cbsize_part,
                $cstyle_part
            );

        case 'season_shows':
            $season_id = (int) get_post_meta( $show_id, '_stagekitwp_show_season', true );
            if ( $season_id > 0 ) {
                return sprintf( '[stagekitwp_season_shows season_id="%d" layout="cards"]', $season_id );
            }
            return '[stagekitwp_season_shows which="current" layout="cards"]';

        case 'tickets':
            return '[stagekitwp_tickets layout="banner"]';

        default:
            return '';
    }
}
