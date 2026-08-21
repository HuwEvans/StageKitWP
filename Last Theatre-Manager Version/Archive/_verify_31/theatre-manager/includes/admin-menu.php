<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Register Theatre Manager menu and Display Options submenu
 */
function tm_register_plugin_menu() {
    add_menu_page(
        'Theatre Manager',
        'Theatre Manager',
        'manage_options',
        'theatre-manager',
        'tm_display_options_page',
        'dashicons-admin-multisite',
        20
    );

    add_submenu_page(
        'theatre-manager',
        'Display Options',
        'Display Options',
        'manage_options',
        'tm-display-options',
        'tm_display_options_page'
    );
	
	add_submenu_page(
		'theatre-manager',
		'Instructions',
		'Instructions',
		'manage_options',
		'tm_instructions',
		'tm_instructions_page'
	);

	add_submenu_page(
		'theatre-manager',
		'Regenerate PDF Previews',
		'Regenerate PDF Previews',
		'manage_options',
		'tm_regenerate_previews',
		'tm_regenerate_previews_page'
	);
}
add_action('admin_menu', 'tm_register_plugin_menu');


/**
 * Admin page: Regenerate PDF previews for attachments
 */
function tm_regenerate_previews_page() {
	if (!current_user_can('manage_options')) {
		wp_die('Insufficient permissions');
	}

	echo '<div class="wrap">';
	echo '<h1>Regenerate PDF Previews</h1>';

	if (isset($_POST['tm_regenerate_previews_nonce'])) {
		if (!wp_verify_nonce($_POST['tm_regenerate_previews_nonce'], 'tm_regenerate_previews_action')) {
			echo '<div class="notice notice-error"><p>Nonce verification failed.</p></div>';
		} else {
			// Run regeneration
			$args = array(
				'post_type' => 'attachment',
				'post_mime_type' => 'application/pdf',
				'numberposts' => -1
			);
			$pdfs = get_posts($args);
			$count = 0;
			$failed = 0;
			$messages = array();
			foreach ($pdfs as $p) {
				// Clear existing meta so generator re-writes
				delete_post_meta($p->ID, '_tm_pdf_preview');
				// Call the generator directly and collect diagnostics
				if (function_exists('tm_generate_pdf_preview')) {
					$res = tm_generate_pdf_preview($p->ID);
					if (is_array($res) && !empty($res['success'])) {
						$count++;
						$messages[] = sprintf('%s: OK (%s)', esc_html($p->post_title), esc_html($res['message']));
					} else {
						$failed++;
						$msg = is_array($res) && !empty($res['message']) ? $res['message'] : 'Unknown error';
						$messages[] = sprintf('%s: FAILED (%s)', esc_html($p->post_title), esc_html($msg));
					}
				} else {
					$failed++;
					$messages[] = sprintf('%s: FAILED (generator not available)', esc_html($p->post_title));
				}
			}

			echo '<div class="notice notice-success"><p>Processed ' . intval(count($pdfs)) . ' PDFs. Previews generated: ' . intval($count) . '. Failed: ' . intval($failed) . '.</p></div>';
			if (!empty($messages)) {
				echo '<div class="notice"><ul style="max-height:300px;overflow:auto;">';
				foreach ($messages as $m) {
					echo '<li>' . esc_html($m) . '</li>';
				}
				echo '</ul></div>';
			}
		}
	}

	echo '<form method="post">';
	wp_nonce_field('tm_regenerate_previews_action', 'tm_regenerate_previews_nonce');
	echo '<p>This will attempt to generate a first-page JPEG preview for every PDF in the media library. Your server must have Imagick and PDF support installed for previews to be created.</p>';
	submit_button('Regenerate PDF Previews', 'primary', 'tm_regenerate_previews_submit');
	echo '</form>';

	echo '</div>';
}

/**
 * Display Options Page with Tabs
 */
function tm_display_options_page() {
	$tabs = ['board_member', 'advertiser', 'sponsor', 'contributor', 'testimonials', 'season', 'show', 'cast'];
	$active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'board_member';
	if (!in_array($active_tab, $tabs, true)) {
		$active_tab = 'board_member';
	}

    echo '<div class="wrap">';
    echo '<h1>Display Options</h1>';
    echo '<h2 class="nav-tab-wrapper">';
    foreach ($tabs as $tab) {
        $label = ucfirst(str_replace('_', ' ', $tab));
        $active = ($active_tab === $tab) ? 'nav-tab-active' : '';
        echo "<a href=\"?page=tm-display-options&tab={$tab}\" class=\"nav-tab {$active}\">{$label}</a>";
    }
    echo '</h2>';

    echo '<form method="post" action="options.php">';
    settings_fields('tm_display_options_' . $active_tab);
    do_settings_sections('tm-display-options-' . $active_tab);
    submit_button();
    echo '</form>';
    echo '</div>';
}

/**
 * Register settings for each tab
 */
function tm_register_display_settings() {
    $tabs = ['board_member', 'advertiser', 'sponsor', 'contributor', 'testimonials', 'season', 'show', 'cast'];
    foreach ($tabs as $tab) {
        $section_id = "tm_{$tab}_section";
        $page = "tm-display-options-{$tab}";
        $group = "tm_display_options_{$tab}";

        add_settings_section($section_id, ucfirst(str_replace('_', ' ', $tab)) . ' Display Settings', null, $page);

        add_settings_field("tm_{$tab}_bg_color", 'Background Color', 'tm_color_picker_callback', $page, $section_id, ['label_for' => "tm_{$tab}_bg_color"]);
        register_setting($group, "tm_{$tab}_bg_color");

        add_settings_field("tm_{$tab}_text_color", 'Text Color', 'tm_color_picker_callback', $page, $section_id, ['label_for' => "tm_{$tab}_text_color"]);
        register_setting($group, "tm_{$tab}_text_color");

        add_settings_field("tm_{$tab}_border_color", 'Border Color', 'tm_color_picker_callback', $page, $section_id, ['label_for' => "tm_{$tab}_border_color"]);
        register_setting($group, "tm_{$tab}_border_color");

        add_settings_field("tm_{$tab}_border_width", 'Border Width', 'tm_text_input_callback', $page, $section_id, ['label_for' => "tm_{$tab}_border_width"]);
        register_setting($group, "tm_{$tab}_border_width");

        add_settings_field("tm_{$tab}_rounded", 'Rounded Corners', 'tm_checkbox_callback', $page, $section_id, ['label_for' => "tm_{$tab}_rounded"]);
        register_setting($group, "tm_{$tab}_rounded");

        add_settings_field("tm_{$tab}_radius", 'Border Radius', 'tm_text_input_callback', $page, $section_id, ['label_for' => "tm_{$tab}_radius"]);
        register_setting($group, "tm_{$tab}_radius");

        add_settings_field("tm_{$tab}_shadow", 'Border Shadow', 'tm_checkbox_callback', $page, $section_id, ['label_for' => "tm_{$tab}_shadow"]);
        register_setting($group, "tm_{$tab}_shadow");

        add_settings_field("tm_{$tab}_h1_color", 'H1 Text Color', 'tm_color_picker_callback', $page, $section_id, ['label_for' => "tm_{$tab}_h1_color"]);
        register_setting($group, "tm_{$tab}_h1_color");

        add_settings_field("tm_{$tab}_h2_color", 'H2 Text Color', 'tm_color_picker_callback', $page, $section_id, ['label_for' => "tm_{$tab}_h2_color"]);
        register_setting($group, "tm_{$tab}_h2_color");

        add_settings_field("tm_{$tab}_h3_color", 'H3 Text Color', 'tm_color_picker_callback', $page, $section_id, ['label_for' => "tm_{$tab}_h3_color"]);
        register_setting($group, "tm_{$tab}_h3_color");

        add_settings_field("tm_{$tab}_h4_color", 'H4 Text Color', 'tm_color_picker_callback', $page, $section_id, ['label_for' => "tm_{$tab}_h4_color"]);
        register_setting($group, "tm_{$tab}_h4_color");

        add_settings_field("tm_{$tab}_h5_color", 'H5 Text Color', 'tm_color_picker_callback', $page, $section_id, ['label_for' => "tm_{$tab}_h5_color"]);
        register_setting($group, "tm_{$tab}_h5_color");

        add_settings_field("tm_{$tab}_h6_color", 'H6 Text Color', 'tm_color_picker_callback', $page, $section_id, ['label_for' => "tm_{$tab}_h6_color"]);
        register_setting($group, "tm_{$tab}_h6_color");

        if ($tab === 'testimonials') {
            add_settings_field("tm_{$tab}_rating_symbol", 'Rating Symbol', 'tm_rating_symbol_callback', $page, $section_id, ['label_for' => "tm_{$tab}_rating_symbol"]);
            register_setting($group, "tm_{$tab}_rating_symbol");
        }
		
		if ($tab === 'board_member' || $tab === 'advertiser') {
			add_settings_field("tm_{$tab}_grid_columns", 'Grid Columns', 'tm_grid_columns_callback', $page, $section_id, ['label_for' => "tm_{$tab}_grid_columns"]);
			register_setting($group, "tm_{$tab}_grid_columns");
		}
		
		add_settings_field("tm_{$tab}_disable_border", 'Disable Border', 'tm_checkbox_callback', $page, $section_id, ['label_for' => "tm_{$tab}_disable_border"]);
        register_setting($group, "tm_{$tab}_disable_border");
    }
}
add_action('admin_init', 'tm_register_display_settings');

/**
 * Callback functions
 */
function tm_color_picker_callback($args) {
    $option = get_option($args['label_for']);
    echo '<input type="text" class="tm-color-picker" id="' . esc_attr($args['label_for']) . '" name="' . esc_attr($args['label_for']) . '" value="' . esc_attr($option) . '" />';
}

function tm_text_input_callback($args) {
    $option = get_option($args['label_for']);
    echo '<input type="text" id="' . esc_attr($args['label_for']) . '" name="' . esc_attr($args['label_for']) . '" value="' . esc_attr($option) . '" />';
}

function tm_checkbox_callback($args) {
    $option = get_option($args['label_for']);
    echo '<input type="checkbox" id="' . esc_attr($args['label_for']) . '" name="' . esc_attr($args['label_for']) . '" value="1"' . checked(1, $option, false) . ' />';
}

function tm_rating_symbol_callback($args) {
    $option = get_option($args['label_for']);
    $symbols = ['Stars', 'Thumbs Up', 'Rockets', 'Hearts', 'Theatre Masks'];
    echo '<select id="' . esc_attr($args['label_for']) . '" name="' . esc_attr($args['label_for']) . '">';
    foreach ($symbols as $symbol) {
        echo '<option value="' . esc_attr($symbol) . '"' . selected($option, $symbol, false) . '>' . esc_html($symbol) . '</option>';
    }
    echo '</select>';
}
function tm_grid_columns_callback($args) {
	$option = get_option($args['label_for']);
	$numbers = ['1','2','3','4','5','6'];
	echo '<select id="' . esc_attr($args['label_for']) . '" name="' . esc_attr($args['label_for']) . '">';
	foreach ($numbers as $number) {
		echo '<option value="' . esc_attr($number) . '"' . selected($option, $number, false) . '>' . esc_html($number) . '</option>';
	}
	echo '</select>';
}

function tm_instructions_page() {
	echo "<h1>Theatre Manager Instructions</h1>";
	echo "<h2>Theatre Manager Wordpress Plugin</h2>
This plugin is designed to work with Wordpress and was developed to help theatre groups manage important objects<br/>
on their websites.  It was designed after serveral years of maintaining a website for this purpose, and combines<br/>
simpliying the management of common changes that are required, and making it simple for non-technical people to <br/>
update the information.  Once it is up and running, it should be very sraight forward to make changes and have <br/>
these reflected on the appropriate pages with very little technical know how.<br/>
<br/>
The plugin has 2 main aspects.  The Custom Post Types (CPTs) and the shortcodes to go along with the CPTs.  This<br/>
combination of tools should allow someone to easily inplement these on a wordpress website, and provide a modcum<br/>
of control over how the elements are displayed.<br/>
<br/>
<h2>The Custom Post Types</h2>
<h3>Advertisers</h3>
	This CPT allows for entering advertisers that have paid the group to help prmot there business.  It will keep <br/>
	track of the name, logo, banner and website of each advertiser.  There is also a flag to designate an advertiser<br/>
	as a restaurant.  This was done to allow for the ability to create a 'Diner's Guide'.  Helping to promote<br/>
	local restaurants that patrons might what to frequent before or after a show.  It allows the advertisers to <br/>
	supply a link to a promotion that they can offer to people who get to them via advertising with the group.<br/>
<h3>Board Members</h3>
	This CPT tracks information about the board members or creative team for the group.  It has the names and <br/>
	positions of each board member, and allows for a picture to be added for each board member.<br/>
<h3>Contributors</h3>
	This CPT track infomation about donors to the group and provides a means of acknowlegding them on the website. <br/>
	It provides an easy way to manage the donors and see them in one place.<br/>
<h3>Sponsors</h3>
	This CPT provides a means of tracking sponsors to the group.  This will allow for generating a of people and <br/>
	organizations that have taken part in the sponsorship program with the group.<br/>
<h3>Testimonials</h3>
	This CPT allows for tracking of testimonials that have been provided by indivuals who have seen the shows and<br/>
	have gone out of there way to provide feedback.<br/>
<h3>Seasons -> Shows -> Casts</h3>
	These CPTs are probable the most ussful of all the CPTs.  It provides a means to easily track the information <br/>
	about a season, including the shows and cast members for each show.  When maintaining this information for the<br/>
	group that I was a part of, it was the area that required the most updating, and had several components that <br/>
	were difficult to maintain.  Providing these CPTs, I wanted to provide an easy way to link all of this data <br/>
	together for easy display, and	 provide a simple way see and modify the data.<br/>
<br/>
<h3>Display Options</h3>
This section of the plugin admin area will help with customizing the way that the shortcodes display the CPTs. <br/>
Primarily, you will be able to control the background color, the text color, the border color and width, whether<br/>
or not there are rounded corners, and then some other specifics to the individual CPT.  Please note that not all<br/>
settings apply to all shortcode display types.  For instance the slider types typically do not use any cusomizations<br/>
from the display options.<br/>
<br/>
<h3>The Shortcodes</h3>
<p><h4>Theatre Manager Plugin Shortcode Reference</h4></p>
<p>This document lists the shortcodes provided by the Theatre Manager plugin, their parameters, admin options (where applicable), and example usage. Each shortcode outputs markup with CSS classes you can override in your theme.</p>
<p>Guidelines<br />- Dates: seasons use post meta keys &apos;_tm_season_start_date&apos; and &apos;_tm_season_end_date&apos; (strings parsed with PHP&apos;s &apos;strtotime&apos;). The plugin sorts seasons by parsed start date.<br />- Season selector: Several shortcodes accept a &apos;which&apos; attribute with values: &apos;all&apos; (default), &apos;current&apos;, &apos;next&apos;, &apos;current_and_next&apos;. &apos;current&apos; is strictly where today &gt; start AND today &lt; end.</p>
<p><h4>Available Shortcodes</h4></p>
<p>1)<b> [tm_advertisers]</b><br />Purpose: Displays a grid/list of advertisers (CPT &apos;advertiser&apos;).<br />Parameters:<br />- title (optional): Section title to display above the list<br />- category (optional): Filter by advertiser category slug or name<br />- layout (optional): &apos;grid&apos; (default) or &apos;list&apos;<br />- count (optional): number of advertisers to return (default: all)<br />Example:<br />[tm_advertisers title=&quot;Our Supporters&quot; category=&quot;platinum&quot; layout=&quot;grid&quot; count=&quot;6&quot;]</p>
<p>2)<b> [tm_board_members]</b><br />Purpose: Shows board members CPT in a grid/list.<br />Parameters:<br />- title (optional)<br />- layout (optional): &apos;grid&apos; or &apos;list&apos;<br />Example:<br />[tm_board_members title=&quot;Board&quot; layout=&quot;grid&quot;]</p>
<p>3)<b> [tm_cast]</b><br />Purpose: Displays cast members (CPT &apos;cast&apos;). Supports grouping by show.<br />Parameters:<br />- show_id (optional): integer show ID to limit cast to a single show<br />- exclude (optional): comma-separated list of cast fields to hide, e.g. &apos;picture,actor_name,show&apos;<br />- orderby (optional): WP query orderby value (default &apos;title&apos;)<br />- order (optional): &apos;ASC&apos; or &apos;DESC&apos; (default &apos;ASC&apos;)<br />- group_by (optional): &apos;none&apos; (default) or &apos;show&apos; &mdash; when &apos;show&apos; the cast is rendered grouped under show titles<br />Examples:<br />- All cast: [tm_cast]<br />- Cast for show 123: [tm_cast show_id=&quot;123&quot;]<br />- Grouped by show, hide pictures: [tm_cast group_by=&quot;show&quot; exclude=&quot;picture&quot;]</p>
<p>4)<b> [tm_contributors]</b><br />Purpose: Lists contributors/donors (CPT &apos;contributor&apos;).<br />Parameters:<br />- layout (optional): &apos;grid&apos; or &apos;list&apos;<br />- category (optional)<br />Example:<br />[tm_contributors layout=&quot;grid&quot;]</p>
<p>5)<b> [tm_programs]</b><br />Purpose: Displays program/playbill information. Uses program attachment post meta on shows.<br />Parameters:<br />- show_id (optional): specific show<br />- season_id (optional)<br />Example:<br />[tm_programs show_id=&quot;123&quot;]</p>
<p>6)<b> [tm_seasons]</b><br />Purpose: Shows season posts.<br />Parameters:<br />- count (optional): Number of seasons to display (default: all)<br />- layout (optional): &apos;list&apos; or &apos;grid&apos;<br />Example:<br />[tm_seasons count=&quot;4&quot; layout=&quot;grid&quot;]</p>
<p>7)<b> [tm_shows]</b><br />Purpose: Displays shows listing, grouped by season and time slot.<br />Parameters:<br />- season_id (optional): ID of a specific season. If omitted, the shortcode may display multiple seasons sorted by start date.<br />- which (optional): One of &apos;all&apos; (default), &apos;current&apos;, &apos;next&apos;, &apos;current_and_next&apos;. Only used when &apos;season_id&apos; is omitted.<br />- exclude (optional): comma-separated list of show fields to exclude (e.g., &apos;sm_image,program&apos;)<br />- layout (optional): &apos;grid&apos; (default) or &apos;list&apos;<br />- count (optional): number of shows to display (default: all)<br />Behavior:<br />- Shows are sorted by the season start date (ascending). Within a season shows are grouped/ordered by time slot: &apos;Fall&apos;, &apos;Winter&apos;, &apos;Spring&apos;. Shows without a season appear last under &quot;Other Shows&quot;.<br />Example:<br />[tm_shows which=&quot;current&quot; exclude=&quot;synopsis&quot; layout=&quot;grid&quot;]</p>
<p>8)<b> [tm_sponsor_slider]</b><br />Purpose: Creates a sliding showcase of sponsors (CPT &apos;sponsor&apos;).<br />Parameters:<br />- category (optional)<br />- speed (optional): milliseconds for autoplay speed (default depends on slider settings)<br />Example:<br />[tm_sponsor_slider category=&quot;platinum&quot; speed=&quot;4000&quot;]</p>
<p>9)<b> [tm_sponsors]</b><br />Purpose: Displays sponsors list.<br />Parameters:<br />- category (optional)<br />- layout (optional): &apos;grid&apos; or &apos;list&apos;<br />- title (optional)<br />Example:<br />[tm_sponsors category=&quot;gold&quot; layout=&quot;grid&quot; title=&quot;Our Sponsors&quot;]</p>
<p>10)<b> [tm_testimonials]</b><br />Purpose: Shows testimonials (CPT &apos;testimonial&apos;) with configurable rating symbol.<br />Parameters:<br />- count (optional): Number of testimonials to display<br />- layout (optional): &apos;slider&apos; or &apos;list&apos; (the shortcode uses Slick slider by default)<br />Admin option (Display &rarr; Testimonials):<br />- Option name: &apos;tm_testimonials_rating_symbol&apos;<br />- Default: &apos;Stars&apos;<br />- Available values and behavior:<br />- &apos;Stars&apos; &mdash; filled: ★, empty: ☆ (different glyphs)<br />- &apos;Thumbs Up&apos; &mdash; filled: 👍, empty: same glyph (displayed faded via CSS filters)<br />- &apos;Rockets&apos; &mdash; filled: 🚀, empty: same glyph (displayed faded via CSS filters)<br />- &apos;Hearts&apos; &mdash; filled: ❤️, empty: 🤍 (different glyphs)<br />- &apos;Theatre Masks&apos; &mdash; filled: 🎭, empty: same glyph (displayed faded via CSS filters)<br />Notes:<br />- Each testimonial stores an integer rating in post meta &apos;_tm_rating&apos; (0&ndash;5). The shortcode renders five symbols and uses the selected symbol set.<br />- The &apos;Thumbs Up&apos;, &apos;Rockets&apos; and &apos;Theatre Masks&apos; options use the same glyph for filled and empty and rely on CSS (opacity/grayscale) to make the empty glyph visually distinct.<br />Example:<br />[tm_testimonials count=&quot;5&quot; layout=&quot;slider&quot;]</p>
<p>11)<b> [tm_show_cast]</b><br />Purpose: Displays cast for a specific show (wrapper around cast entries filtered by show).<br />Parameters:<br />- show_id (required): ID of the show to display cast for<br />Example:<br />[tm_show_cast show_id=&quot;123&quot;]</p>
<p>12)<b> [tm_season_banner]</b><br />Purpose: Displays the season banner/header (uses season featured image/meta).<br />Parameters:<br />- season_id (required): ID of the season<br />Example:<br />[tm_season_banner season_id=&quot;456&quot;]</p>
<p>13)<b> [tm_season_cast]</b><br />Purpose: Shows cast members grouped by season and show (when &apos;season_id&apos; omitted the &apos;which&apos; selector applies).<br />Parameters:<br />- season_id (optional): ID of the season (omit to use &apos;which&apos;)<br />- which (optional): &apos;all&apos; (default) | &apos;current&apos; | &apos;next&apos; | &apos;current_and_next&apos;<br />- show_cast_images (optional): &apos;true&apos; (default) or &apos;false&apos; &mdash; whether to show cast pictures<br />- cast_layout (optional): &apos;grid&apos; (default) or &apos;list&apos;<br />Notes:<br />- After each show&apos;s cast the shortcode will render a &quot;Program Preview&quot; when a program attachment or URL exists. The preview prefers a generated thumbnail (&apos;_tm_pdf_preview&apos;), falls back to the attachment image, and finally to a client-side canvas rendering (PDF.js).<br />Example:<br />[tm_season_cast which=&quot;current&quot; cast_layout=&quot;list&quot; show_cast_images=&quot;true&quot;]</p>
<p>14)<b> [tm_season_images]</b><br />Purpose: Displays an image gallery tied to a season.<br />Parameters:<br />- season_id (required): ID of the season<br />- layout (optional): &apos;grid&apos; or &apos;slider&apos;<br />Example:<br />[tm_season_images season_id=&quot;456&quot; layout=&quot;grid&quot;]</p>
<p>15)<b> [tm_season_shows]</b><br />Purpose: Lists shows grouped by season (or filtered to specific seasons).<br />Parameters:<br />- season_id (optional): ID of the season to show (omit to list multiple seasons)<br />- which (optional): &apos;all&apos; (default) | &apos;current&apos; | &apos;next&apos; | &apos;current_and_next&apos;<br />- layout (optional): &apos;grid&apos; or &apos;list&apos;<br />Notes:<br />- Seasons are sorted by &apos;_tm_season_start_date&apos; (earliest first). Current/next selection is based on strict comparisons (today &gt; start AND today &lt; end).<br />Example:<br />[tm_season_shows which=&quot;current_and_next&quot; layout=&quot;grid&quot;]</p>
<p><h3>Common Parameter Types</h3>- layout: accepts values like &apos;grid&apos;, &apos;list&apos;, &apos;slider&apos; depending on shortcode<br />- count: integer number of items to return<br />- category: string matching a category slug or name<br />- *_id: integer ID of the resource (show_id, season_id, etc.)</p>
<p><h3>Styling and Hooks</h3>- All shortcodes add semantic CSS classes (for example &apos;.tm-show-card&apos;, &apos;.tm-cast-grid&apos;, &apos;.tm-program-preview&apos;) so you can override styles in your theme or enqueue your own stylesheet.<br />- Filters: many shortcodes use WordPress filters in their templates; see the shortcode functions in &apos;includes/shortcodes/&apos; if you need to hook or modify output programmatically.</p>
<p><h3>Examples (copy/paste)</h3>- All shows for current season: [tm_shows which=&quot;current&quot;]<br />- Cast grouped by show (for a specific season): [tm_season_cast season_id=&quot;456&quot; group_by=&quot;show&quot;]<br />- Testimonials slider (5 items): [tm_testimonials count=&quot;5&quot; layout=&quot;slider&quot;]<br />- Show a program preview after each cast: the &apos;tm_season_cast&apos; shortcode does this by default when a program attachment or URL exists.</p>";
}
?>
