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
        'Season Builder',
        'Season Builder',
        'edit_posts',
        'tm-season-builder',
        'tm_render_season_builder_page'
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

	add_submenu_page(
		'theatre-manager',
		'Settings',
		'Settings',
		'manage_options',
		'tm-settings',
		'tm_settings_page'
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
 * Settings Page
 */
function tm_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions' );
	}

	echo '<div class="wrap">';
	echo '<h1>Theatre Manager Settings</h1>';
	echo '<form method="post" action="options.php">';
	settings_fields( 'tm_settings_group' );
	do_settings_sections( 'tm-settings' );
	submit_button();
	echo '</form>';

	// Sample pages section
	echo '<hr>';
	echo '<h2>Sample Pages</h2>';
	echo '<p>Create demonstration pages for each shortcode with examples and documentation. Sample pages are managed from the <strong>Tools</strong> menu.</p>';
	echo '<p><a href="' . esc_url(admin_url('tools.php?page=tm-sample-content')) . '" class="button button-primary">Manage Sample Pages</a></p>';

	echo '</div>';
}

function tm_register_settings() {
	add_settings_section(
		'tm_season_builder_section',
		'Season Builder',
		null,
		'tm-settings'
	);

	add_settings_field(
		'tm_show_builder_cpt_menus',
		'Show CPT Menus',
		'tm_show_builder_cpt_menus_callback',
		'tm-settings',
		'tm_season_builder_section'
	);
	register_setting( 'tm_settings_group', 'tm_show_builder_cpt_menus' );

	add_settings_section(
		'tm_google_maps_section',
		'Google Maps Integration',
		'tm_google_maps_section_callback',
		'tm-settings'
	);

	add_settings_field(
		'tm_google_maps_api_key',
		'Google Maps API Key',
		'tm_google_maps_api_key_callback',
		'tm-settings',
		'tm_google_maps_section'
	);
	register_setting( 'tm_settings_group', 'tm_google_maps_api_key' );
}
add_action( 'admin_init', 'tm_register_settings' );

function tm_google_maps_section_callback() {
	echo '<p>Configure Google Maps integration for venue map thumbnails in the [tm_venues] shortcode.</p>';
}

function tm_google_maps_api_key_callback() {
	$value = get_option( 'tm_google_maps_api_key', '' );
	echo '<input type="password" id="tm_google_maps_api_key" name="tm_google_maps_api_key" value="' . esc_attr($value) . '" size="50" />';
	echo '<p class="description">Obtain a free API key from <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>. Required for map thumbnail display in the Venues shortcode.</p>';
}

function tm_show_builder_cpt_menus_callback() {
	$value = get_option( 'tm_show_builder_cpt_menus', '1' );
	echo '<input type="checkbox" id="tm_show_builder_cpt_menus" name="tm_show_builder_cpt_menus" value="1"' . checked( '1', $value, false ) . ' />';
	echo '<label for="tm_show_builder_cpt_menus"> Show Seasons, Shows, Cast, and Awards in the admin sidebar menu</label>';
	echo '<p class="description">When unchecked, the Season Builder CPT menus (Seasons, Shows, Cast, Awards) are hidden from the sidebar. Use the Season Builder page to manage them instead.</p>';
}

/**
 * Display Options Page with Tabs
 */
function tm_display_options_page() {
	$tabs = ['board_member', 'advertiser', 'sponsor', 'contributor', 'testimonials', 'season', 'show', 'cast', 'auditions', 'awards', 'venues', 'tickets'];
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
    $tabs = ['board_member', 'advertiser', 'sponsor', 'contributor', 'testimonials', 'season', 'show', 'cast', 'auditions', 'awards', 'venues', 'tickets'];
    foreach ($tabs as $tab) {
        $section_id = "tm_{$tab}_section";
        $page = "tm-display-options-{$tab}";
        $group = "tm_display_options_{$tab}";

        add_settings_section($section_id, ucfirst(str_replace('_', ' ', $tab)) . ' Display Settings', null, $page);

        add_settings_field("tm_{$tab}_bg_color", 'Background Color', 'tm_color_picker_callback', $page, $section_id, ['label_for' => "tm_{$tab}_bg_color"]);
        register_setting($group, "tm_{$tab}_bg_color");

        add_settings_field("tm_{$tab}_text_color", 'Text Color', 'tm_color_picker_callback', $page, $section_id, ['label_for' => "tm_{$tab}_text_color"]);
        register_setting($group, "tm_{$tab}_text_color");

        add_settings_field("tm_{$tab}_base_font", 'Base Font Family', 'tm_font_family_callback', $page, $section_id, ['label_for' => "tm_{$tab}_base_font"]);
        register_setting($group, "tm_{$tab}_base_font");

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
		
		if ($tab === 'tickets') {
			add_settings_field("tm_{$tab}_button_color", 'Button Color', 'tm_color_picker_callback', $page, $section_id, ['label_for' => "tm_{$tab}_button_color"]);
			register_setting($group, "tm_{$tab}_button_color");
			
			add_settings_field("tm_{$tab}_button_hover_color", 'Button Hover Color', 'tm_color_picker_callback', $page, $section_id, ['label_for' => "tm_{$tab}_button_hover_color"]);
			register_setting($group, "tm_{$tab}_button_hover_color");
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

function tm_font_family_callback($args) {
	$option = get_option($args['label_for'], 'Arial, sans-serif');
	$fonts = array(
		'Arial, sans-serif' => 'Arial',
		'Georgia, serif' => 'Georgia',
		'Times New Roman, serif' => 'Times New Roman',
		'Courier New, monospace' => 'Courier New',
		'Verdana, sans-serif' => 'Verdana',
		'Trebuchet MS, sans-serif' => 'Trebuchet MS',
		'Comic Sans MS, cursive' => 'Comic Sans MS',
		'Palatino Linotype, serif' => 'Palatino Linotype',
		'Lucida Console, monospace' => 'Lucida Console'
	);
	$select_id = esc_attr($args['label_for']);
	echo '<select id="' . $select_id . '" name="' . $select_id . '" onchange="updateFontPreview(this)">';
	foreach ($fonts as $value => $label) {
		echo '<option value="' . esc_attr($value) . '"' . selected($option, $value, false) . '>' . esc_html($label) . '</option>';
	}
	echo '</select>';
	
	// Add preview div and styling
	echo '<div style="margin-top: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">';
	echo '<p style="margin-top: 0; margin-bottom: 5px; font-size: 12px; color: #666;"><strong>Preview:</strong></p>';
	echo '<div id="' . $select_id . '_preview" style="font-family: ' . esc_attr($option) . '; font-size: 16px; line-height: 1.6; color: #333;">The quick brown fox jumps over the lazy dog</div>';
	echo '</div>';
	
	// Add JavaScript for live preview
	echo '<script>
	function updateFontPreview(select) {
		var previewId = select.id + "_preview";
		var preview = document.getElementById(previewId);
		if (preview) {
			preview.style.fontFamily = select.value;
		}
	}
	</script>';
}

function tm_instructions_page() {
	echo "<div class='wrap'>";
	echo "<h1>Theatre Manager Instructions</h1>";
	
	echo "<h2>Overview</h2>";
	echo "<p>Theatre Manager is a comprehensive WordPress plugin designed to help theatre groups manage and display information about their productions, cast, seasons, sponsors, and more. The plugin combines Custom Post Types (CPTs) with easy-to-use shortcodes to provide complete control over how information is displayed on your website.</p>";
	
	echo "<h2>Core Features</h2>";
	echo "<h3>Custom Post Types (CPTs)</h3>";
	echo "<p>Theatre Manager includes the following custom post types for organizing your theatre data:</p>";
	echo "<ul>";
	echo "<li><strong>Seasons</strong> - Track theatre seasons with dates, images, and status (Current/Upcoming/Past)</li>";
	echo "<li><strong>Shows</strong> - Manage shows within seasons including genre, director, and audition information</li>";
	echo "<li><strong>Venues</strong> - Manage performance venues with addresses, phone numbers, websites, and Google Maps integration</li>";
	echo "<li><strong>Cast</strong> - Display cast members with photos and role information for each show</li>";
	echo "<li><strong>Awards</strong> - Track awards and nominations for shows (Musical/Drama/Comedy categories)</li>";
	echo "<li><strong>Board Members</strong> - Maintain board member information with positions and photos</li>";
	echo "<li><strong>Sponsors</strong> - Manage sponsor information and logos</li>";
	echo "<li><strong>Advertisers</strong> - Track advertisers and local businesses (with restaurant flagging)</li>";
	echo "<li><strong>Contributors</strong> - Acknowledge donors and contributors</li>";
	echo "<li><strong>Testimonials</strong> - Display audience reviews and testimonials with ratings</li>";
	echo "</ul>";
	
	echo "<h2>Shortcodes Reference</h2>";
	echo "<p>Use these shortcodes in pages and posts to display your theatre information. All parameters are optional unless otherwise noted.</p>";
	
	echo "<h3>1. [tm_auditions]</h3>";
	echo "<p><strong>Purpose:</strong> Display upcoming auditions sorted by date (earliest first).</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>days_past</code> (integer, default: 7) - Number of days back to include auditions</li>";
	echo "</ul>";
	echo "<p><strong>Example:</strong> <code>[tm_auditions days_past=\"14\"]</code></p>";
	
	echo "<h3>2. [tm_season_shows]</h3>";
	echo "<p><strong>Purpose:</strong> Display shows organized by season with headers for Current, Upcoming, and Past seasons.</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>season_id</code> (integer) - Show only shows from a specific season</li>";
	echo "<li><code>which</code> (string, default: \"all\") - Controls season selection: all | current | next | current_and_next</li>";
	echo "</ul>";
	echo "<p><strong>Example:</strong> <code>[tm_season_shows which=\"current_and_next\"]</code></p>";
	
	echo "<h3>3. [tm_seasons]</h3>";
	echo "<p><strong>Purpose:</strong> Display all seasons in table format.</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>exclude</code> (string) - Comma-separated fields to hide (e.g., \"start_date,end_date\")</li>";
	echo "</ul>";
	echo "<p><strong>Example:</strong> <code>[tm_seasons exclude=\"start_date,end_date\"]</code></p>";
	
	echo "<h3>4. [TM_Current_Season] / [tm_current_season]</h3>";
	echo "<p><strong>Purpose:</strong> Display only the current season.</p>";
	echo "<p><strong>Example:</strong> <code>[tm_current_season]</code></p>";
	
	echo "<h3>5. [tm_season_cast]</h3>";
	echo "<p><strong>Purpose:</strong> Display cast members for selected season(s).</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>season_id</code> (integer) - Show cast for specific season</li>";
	echo "<li><code>which</code> (string, default: \"all\") - all | current | next | current_and_next</li>";
	echo "<li><code>show_cast_images</code> (boolean, default: true) - Show/hide cast headshots</li>";
	echo "<li><code>cast_layout</code> (string, default: \"grid\") - Layout style</li>";
	echo "</ul>";
	echo "<p><strong>Example:</strong> <code>[tm_season_cast which=\"current\" show_cast_images=\"true\"]</code></p>";
	
	echo "<h3>6. [tm_shows]</h3>";
	echo "<p><strong>Purpose:</strong> Display shows from selected season(s).</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>season_id</code> (integer) - Display shows for specific season</li>";
	echo "<li><code>which</code> (string, default: \"all\") - all | current | next | current_and_next</li>";
	echo "<li><code>exclude</code> (string) - Comma-separated fields to hide</li>";
	echo "</ul>";
	echo "<p><strong>Example:</strong> <code>[tm_shows which=\"current\" exclude=\"genre,director\"]</code></p>";
	
	echo "<h3>7. [tm_cast]</h3>";
	echo "<p><strong>Purpose:</strong> Display cast members with optional filtering and grouping.</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>show_id</code> (integer) - Display cast for specific show</li>";
	echo "<li><code>exclude</code> (string) - Comma-separated fields to hide</li>";
	echo "<li><code>orderby</code> (string) - Sort field (default: \"title\")</li>";
	echo "<li><code>order</code> (string) - ASC or DESC</li>";
	echo "</ul>";
	echo "<p><strong>Example:</strong> <code>[tm_cast show_id=\"123\" orderby=\"title\"]</code></p>";
	
	echo "<h3>8. [tm_show_cast]</h3>";
	echo "<p><strong>Purpose:</strong> Display cast for a specific show.</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>show_id</code> (integer, required) - ID of the show</li>";
	echo "</ul>";
	echo "<p><strong>Example:</strong> <code>[tm_show_cast show_id=\"123\"]</code></p>";
	
	echo "<h3>9. [tm_season_banner]</h3>";
	echo "<p><strong>Purpose:</strong> Display season banner image.</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>season_id</code> (integer, required) - ID of the season</li>";
	echo "</ul>";
	echo "<p><strong>Example:</strong> <code>[tm_season_banner season_id=\"177\"]</code></p>";
	
	echo "<h3>10. [tm_season_images]</h3>";
	echo "<p><strong>Purpose:</strong> Display all season images (social banner, front, back cover).</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>season_id</code> (integer, required) - ID of the season</li>";
	echo "</ul>";
	echo "<p><strong>Example:</strong> <code>[tm_season_images season_id=\"177\"]</code></p>";
	
	echo "<h3>11. [tm_sponsors]</h3>";
	echo "<p><strong>Purpose:</strong> Display sponsor listings.</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>show_name</code> (boolean, default: true) - Display sponsor name</li>";
	echo "<li><code>show_company</code> (boolean, default: true) - Display company name</li>";
	echo "<li><code>show_logo</code> (boolean, default: true) - Display logo</li>";
	echo "<li><code>show_website</code> (boolean, default: true) - Display website link</li>";
	echo "</ul>";
	echo "<p><strong>Example:</strong> <code>[tm_sponsors show_logo=\"true\" show_website=\"false\"]</code></p>";
	
	echo "<h3>12. [tm_sponsor_slider]</h3>";
	echo "<p><strong>Purpose:</strong> Display sponsors in an automated carousel/slider.</p>";
	echo "<p><strong>Example:</strong> <code>[tm_sponsor_slider]</code></p>";
	
	echo "<h3>13. [tm_testimonials]</h3>";
	echo "<p><strong>Purpose:</strong> Display testimonials in carousel with configurable rating symbols (Stars, Thumbs Up, Rockets, Hearts, Theatre Masks).</p>";
	echo "<p><strong>Example:</strong> <code>[tm_testimonials]</code></p>";
	
	echo "<h3>14. [tm_awards]</h3>";
	echo "<p><strong>Purpose:</strong> Display awards and nominations organized by season and category in a table format.</p>";
	echo "<p><strong>Display:</strong> Structured table showing Show Name, Award Status, Award Name, and Recipient.</p>";
	echo "<p><strong>Sorting:</strong></p>";
	echo "<ul>";
	echo "<li>Primary: Award Status (THEA Winner displays first with ⭐ indicator, followed by Nominations)</li>";
	echo "<li>Secondary: Award Name (alphabetically A-Z)</li>";
	echo "</ul>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>season_id</code> (integer, optional) - Filter awards for specific season</li>";
	echo "<li><code>category</code> (string, optional) - Filter by category (Musical, Drama, Comedy)</li>";
	echo "</ul>";
	echo "<p><strong>Example:</strong> <code>[tm_awards]</code> or <code>[tm_awards category=\"Musical\"]</code></p>";
	echo "<p><strong>Note:</strong> Awards are entered through the Season Builder Media tab or Awards admin menu.</p>";
	
	echo "<h3>15. [tm_venues]</h3>";
	echo "<p><strong>Purpose:</strong> Display venue information with addresses, phone numbers, websites, Google Maps links, and interactive map thumbnails.</p>";
	echo "<p><strong>Display:</strong> Each venue shows name, address, contact details, optional photo, Google Maps map thumbnail, and clickable Google Maps link.</p>";
	echo "<p><strong>Google Maps Integration:</strong></p>";
	echo "<ul>";
	echo "<li>If latitude and longitude are provided for a venue, a direct link to Google Maps is displayed</li>";
	echo "<li>If latitude/longitude provided AND a Google Maps API key is configured, a map thumbnail image is displayed showing the venue location with a red marker</li>";
	echo "<li>Otherwise, the address is used for the maps link</li>";
	echo "<li>To enable map thumbnails, add your Google Maps API key in Theatre Manager → Settings → Google Maps API Key</li>";
	echo "</ul>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>show_id</code> (integer, optional) - Display the specific venue assigned to a show</li>";
	echo "</ul>";
	echo "<p><strong>Example:</strong> <code>[tm_venues]</code> or <code>[tm_venues show_id=\"123\"]</code></p>";
	echo "<p><strong>Note:</strong> Venues are managed through the Venues admin menu. Shows are linked to venues in the Season Builder (Details tab) or Show Details meta box.</p>";
	
	echo "<h3>16. [tm_board_members]</h3>";
	echo "<p><strong>Purpose:</strong> Display board members sorted by role priority (President, Vice-President, Treasurer, Secretary, then alphabetically).</p>";
	echo "<p><strong>Example:</strong> <code>[tm_board_members]</code></p>";
	
	echo "<h3>17. [tm_contributors]</h3>";
	echo "<p><strong>Purpose:</strong> Display contributor/donor listings.</p>";
	echo "<p><strong>Example:</strong> <code>[tm_contributors]</code></p>";
	
	echo "<h3>18. [tm_advertisers]</h3>";
	echo "<p><strong>Purpose:</strong> Display advertiser listings.</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>category</code> (string) - Filter by category (e.g., \"restaurant\")</li>";
	echo "</ul>";
	echo "<p><strong>Example:</strong> <code>[tm_advertisers category=\"restaurant\"]</code></p>";
	
	echo "<h3>19. [tm_programs]</h3>";
	echo "<p><strong>Purpose:</strong> Display downloadable program PDFs grouped by season.</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>season</code> (integer or slug) - Display programs for specific season</li>";
	echo "<li><code>columns</code> (integer, default: 3) - Number of columns in gallery</li>";
	echo "<li><code>size</code> (string, default: \"medium\") - Thumbnail size</li>";
	echo "</ul>";
	echo "<p><strong>Example:</strong> <code>[tm_programs season=\"177\" columns=\"2\"]</code></p>";
	
	echo "<h2>Display Options</h2>";
	echo "<p>All shortcodes reference the Display Options settings in <strong>Theatre Manager → Display Options</strong>. You can customize:</p>";
	echo "<ul>";
	echo "<li><strong>Base Font Family</strong> - Set the default font for each section</li>";
	echo "<li><strong>Colors</strong> - Background, text, border, and heading colors</li>";
	echo "<li><strong>Styling</strong> - Border width, radius, and shadow effects</li>";
	echo "<li><strong>Layouts</strong> - Grid or list layouts where applicable</li>";
	echo "</ul>";
	
	echo "<h2>Season Status Logic</h2>";
	echo "<p>Several shortcodes use season filtering. Here's how statuses are determined:</p>";
	echo "<ul>";
	echo "<li><strong>Current:</strong> Today's date falls between season start and end dates</li>";
	echo "<li><strong>Upcoming:</strong> Season start date is in the future</li>";
	echo "<li><strong>Past:</strong> Season end date is in the past</li>";
	echo "<li><strong>Next:</strong> First upcoming season after the current one (or first upcoming if no current)</li>";
	echo "</ul>";
	
	echo "<h2>Season Builder</h2>";
	echo "<p>Use <strong>Theatre Manager → Season Builder</strong> to manage seasons, shows, cast, and awards in one unified interface.</p>";
	echo "<p><strong>Features:</strong></p>";
	echo "<ul>";
	echo "<li>Two-tab interface: Details and Media</li>";
	echo "<li>Add/edit multiple shows and their cast in one form</li>";
	echo "<li>Assign venues to each show for performance location information</li>";
	echo "<li>Upload season images and show media</li>";
	echo "<li>Manage show audition dates and details</li>";
	echo "<li>Set season status (Past, Current, Upcoming) with automatic constraint enforcement</li>";
	echo "</ul>";
	
	echo "<h2>Display Options</h2>";
	echo "<p>Customize the appearance of all shortcodes with display options. Access this via <strong>Theatre Manager → Display Options</strong>.</p>";
	echo "<p><strong>Settings by Section:</strong></p>";
	echo "<ul>";
	echo "<li><strong>Board Members, Advertisers, Sponsors, Contributors, Testimonials, Awards, Venues, Seasons, Shows, Cast, Auditions</strong> - Each has independent display settings</li>";
	echo "<li><strong>Base Font Family</strong> - Choose font (Arial, Georgia, Times New Roman, Courier, Verdana, Trebuchet MS, Comic Sans, Palatino, Lucida Console)</li>";
	echo "<li><strong>Colors</strong> - Background, text, border, and heading colors (H1-H6)</li>";
	echo "<li><strong>Styling</strong> - Border width, border radius, and shadow effects</li>";
	echo "<li><strong>Grid Columns</strong> - Number of columns for grid layouts (1-6)</li>";
	echo "<li><strong>Special Options</strong> - Testimonials support custom rating symbols (Stars, Thumbs Up, Rockets, Hearts, Theatre Masks)</li>";
	echo "</ul>";
	
	echo "<h2>Settings</h2>";
	echo "<p>Access plugin-wide settings via <strong>Theatre Manager → Settings</strong>.</p>";
	echo "<p><strong>Current Settings:</strong></p>";
	echo "<ul>";
	echo "<li><strong>Season Builder CPT Menus</strong> - Toggle visibility of Seasons, Shows, Cast, and Awards in the WordPress admin sidebar. When disabled, use the Season Builder to manage these items.</li>";
	echo "</ul>";
	echo "<p><strong>Sample Pages</strong> - Create demonstration pages for each shortcode:</p>";
	echo "<ul>";
	echo "<li>Click \"Create Sample Pages\" to automatically generate 17 pages, one for each shortcode</li>";
	echo "<li>Each sample page includes the shortcode with example parameters and helpful documentation</li>";
	echo "<li>View pages in <strong>Pages</strong> menu and customize them as needed</li>";
	echo "<li>Click \"Delete Sample Pages\" to remove all generated sample pages</li>";
	echo "</ul>";
	
	echo "<h2>Tips & Best Practices</h2>";
	echo "<ul>";
	echo "<li>Always set season dates for accurate current/upcoming/past filtering</li>";
	echo "<li>Use descriptive show and character names for better searchability</li>";
	echo "<li>Upload high-quality images for best visual presentation</li>";
	echo "<li>Create sample pages when first setting up the plugin to understand shortcode usage</li>";
	echo "<li>Test shortcodes on a staging site before deploying to production</li>";
	echo "<li>The plugin respects your WordPress security features and sanitizes all user input</li>";
	echo "</ul>";
	
	echo "</div>";
}
?>
