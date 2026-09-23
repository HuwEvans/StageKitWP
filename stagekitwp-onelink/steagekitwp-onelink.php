<?php
/**
 * Plugin Name: StageKitWP OneLink
 * Plugin URI:  https://miltonplayers.com
 * Description: Multi-style link tree aggregator for Milton Players Theatre Group with unlimited custom links, image icon support, and comprehensive social media controls.
 * Version:     1.6.0
 * Author:      Milton Players Theatre Group
 * Text Domain: stagekitwp-onelink
 */

if (!defined('ABSPATH')) {
    exit;
}

class StageKitWP_OneLink {

    private $dashicons = array(
        'tickets-alt' => 'Tickets',
        'calendar-alt' => 'Calendar',
        'groups' => 'People',
        'format-gallery' => 'Gallery',
        'megaphone' => 'Megaphone',
        'star-filled' => 'Star',
        'admin-home' => 'Home',
        'external-link-alt' => 'External link'
    );

    private $social_platforms = array(
        'facebook'  => array('label' => 'Facebook', 'placeholder' => 'https://facebook.com/yourpage or page slug'),
        'x'         => array('label' => 'X (Twitter)', 'placeholder' => 'username or full URL'),
        'instagram' => array('label' => 'Instagram', 'placeholder' => 'username or full URL'),
        'tiktok'    => array('label' => 'TikTok', 'placeholder' => '@username or full URL'),
        'threads'   => array('label' => 'Threads', 'placeholder' => '@username or full URL'),
        'bluesky'   => array('label' => 'Bluesky', 'placeholder' => 'handle.bsky.social or full URL'),
        'spotify'   => array('label' => 'Spotify', 'placeholder' => 'Artist/User/Playlist URL'),
        'pinterest' => array('label' => 'Pinterest', 'placeholder' => 'username or full URL'),
        'youtube'   => array('label' => 'YouTube', 'placeholder' => '@channel or full URL'),
        'linkedin'  => array('label' => 'LinkedIn', 'placeholder' => 'company/name or full URL'),
        'snapchat'  => array('label' => 'Snapchat', 'placeholder' => 'username or full URL'),
        'whatsapp'  => array('label' => 'WhatsApp', 'placeholder' => 'Phone number or wa.me link')
    );

    public function __construct() {
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_shortcode('stagekit_onelink', array($this, 'render_onelink_tree'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function register_admin_menu() {
        add_submenu_page(
            'stagekitwp',
            'StageKitWP OneLink',
            'OneLink Aggregator',
            'manage_options',
            'stagekit-onelink',
            array($this, 'render_admin_page')
        );

        add_menu_page(
            'OneLink Settings',
            'StageKit OneLink',
            'manage_options',
            'stagekit-onelink-standalone',
            array($this, 'render_admin_page'),
            'dashicons-share',
            30
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'stagekit-onelink') !== false) {
            wp_enqueue_media();
            wp_enqueue_script('jquery-ui-sortable');
        }
    }

    public function register_settings() {
        register_setting('sk_onelink_group', 'sk_onelink_settings', array(
            'sanitize_callback' => array($this, 'sanitize_and_merge_settings')
        ));
    }

    private function sanitize_icon_type($type) {
        return 'image' === $type ? 'image' : 'dashicon';
    }

    private function sanitize_icon_value($value, $type) {
        if ('image' === $this->sanitize_icon_type($type)) {
            return esc_url_raw($value);
        }
        $value = sanitize_key($value);
        return isset($this->dashicons[$value]) ? $value : 'tickets-alt';
    }

    private function dependency_available($dependency) {
        if ('core' === $dependency) {
            return class_exists('StageKitWP_Core') && post_type_exists('season') && post_type_exists('show');
        }
        if ('members' === $dependency) {
            return class_exists('STAGEKITWP_Member_Area');
        }
        if ('media' === $dependency) {
            return defined('SKWPM_VERSION');
        }
        return false;
    }

    private function custom_link_order_key($custom, $index) {
        return 'custom:' . strtolower($custom['id'] ?? $index);
    }

    /**
     * Single source of truth for the 8 individually-toggleable season/show ticket links.
     */
    private function get_ticket_toggle_map() {
        return array(
            'enable_current_season_ticket' => 'Current Season - Ticket Link',
            'enable_current_fall_show'     => 'Current Season - Fall Show',
            'enable_current_winter_show'   => 'Current Season - Winter Show',
            'enable_current_spring_show'   => 'Current Season - Spring Show',
            'enable_next_season_ticket'    => 'Next Season - Ticket Link',
            'enable_next_fall_show'        => 'Next Season - Fall Show',
            'enable_next_winter_show'      => 'Next Season - Winter Show',
            'enable_next_spring_show'      => 'Next Season - Spring Show',
        );
    }

    /**
     * Every orderable block currently available, in default (unordered) sequence,
     * then re-sorted per the saved link_order. Used only for the admin Order tab.
     */
    private function get_order_blocks($options) {
        $blocks = array();

        if (!empty($options['enable_subscription']) && !empty($options['subscription_url'])) {
            $blocks[] = array('key' => 'subscription', 'label' => 'Season Subscription', 'source' => 'Ecosystem');
        }
        if ($this->dependency_available('core')) {
            foreach ($this->get_ticket_toggle_map() as $flag => $label) {
                if (!empty($options[$flag] ?? ($options['enable_tickets'] ?? 0))) {
                    $blocks[] = array('key' => substr($flag, strlen('enable_')), 'label' => $label, 'source' => 'Ecosystem');
                }
            }
        }
        if (!empty($options['enable_auditions']) && $this->dependency_available('members')) {
            $blocks[] = array('key' => 'auditions', 'label' => 'Auditions & Casting', 'source' => 'Ecosystem');
        }
        if (!empty($options['enable_events']) && $this->dependency_available('members')) {
            $blocks[] = array('key' => 'events', 'label' => 'Full Events Calendar', 'source' => 'Ecosystem');
        }
        if (!empty($options['enable_media']) && !empty($options['media_url']) && $this->dependency_available('media')) {
            $blocks[] = array('key' => 'media', 'label' => 'Photos & Media Gallery', 'source' => 'Ecosystem');
        }
        if (!empty($options['custom_links']) && is_array($options['custom_links'])) {
            foreach ($options['custom_links'] as $i => $custom) {
                if (!empty($custom['title']) && !empty($custom['url'])) {
                    $blocks[] = array('key' => $this->custom_link_order_key($custom, $i), 'label' => $custom['title'], 'source' => 'Custom Link');
                }
            }
        }
        if (!empty($options['social']) && is_array($options['social'])) {
            foreach ($options['social'] as $key => $data) {
                $placement = $data['placement'] ?? ($options['social_placement'] ?? 'icons');
                if (!empty($data['enable']) && !empty($data['value']) && $placement === 'tree') {
                    $blocks[] = array('key' => 'social:' . $key, 'label' => $this->social_platforms[$key]['label'] ?? ucfirst($key), 'source' => 'Social');
                }
            }
        }

        $saved_order = $options['link_order'] ?? array();
        if (!empty($saved_order)) {
            $position = array_flip($saved_order);
            $max = count($position);
            usort($blocks, function ($a, $b) use ($position, $max) {
                $pos_a = $position[$a['key']] ?? $max;
                $pos_b = $position[$b['key']] ?? $max;
                return $pos_a <=> $pos_b;
            });
        }

        return $blocks;
    }

    private function render_icon_controls($name, $value, $type = 'dashicon') {
        $type = $this->sanitize_icon_type($type);
        $value = $this->sanitize_icon_value($value, $type);
        ?>
        <select name="sk_onelink_settings[<?php echo esc_attr($name); ?>_type]" class="sk-icon-type">
            <option value="dashicon" <?php selected($type, 'dashicon'); ?>>Dashicon</option>
            <option value="image" <?php selected($type, 'image'); ?>>Image</option>
        </select>
        <select name="sk_onelink_settings[<?php echo esc_attr($name); ?>]" class="sk-icon-dashicon" <?php disabled($type, 'image'); ?>>
            <?php foreach ($this->dashicons as $icon => $label) : ?>
                <option value="<?php echo esc_attr($icon); ?>" <?php selected($value, $icon); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" class="regular-text sk-icon-url" name="sk_onelink_settings[<?php echo esc_attr($name); ?>]" value="<?php echo 'image' === $type ? esc_attr($value) : ''; ?>" placeholder="Icon image URL" <?php disabled($type, 'dashicon'); ?>>
        <button type="button" class="button sk-upload-icon-btn" <?php disabled($type, 'dashicon'); ?>>Upload Icon</button>
        <?php
    }

    public function sanitize_and_merge_settings($input) {
        $existing = get_option('sk_onelink_settings', array());
        if (!is_array($existing)) {
            $existing = array();
        }

        $active_tab = isset($_POST['sk_onelink_active_tab']) ? sanitize_key($_POST['sk_onelink_active_tab']) : 'styles';

        // 1. STYLES TAB
        if ($active_tab === 'styles') {
            $existing['display_style'] = sanitize_text_field($input['display_style'] ?? 'classic');
            $existing['header_title'] = sanitize_text_field($input['header_title'] ?? 'Milton Players Theatre Group');
            $existing['header_subtitle'] = sanitize_text_field($input['header_subtitle'] ?? 'Live Theatre & Season Content Hub');
            $font_choices = array('system', 'inter', 'dm-sans', 'space-grotesk', 'source-sans', 'merriweather');
            $existing['font_family'] = in_array($input['font_family'] ?? 'system', $font_choices, true) ? $input['font_family'] : 'system';
            $existing['primary_color'] = sanitize_hex_color($input['primary_color'] ?? '#b42318');
            $existing['btn_bg_color']  = sanitize_hex_color($input['btn_bg_color'] ?? '#eef2f6');
            $existing['highlight_bg_color']   = sanitize_hex_color($input['highlight_bg_color'] ?? '#b42318');
            $existing['highlight_text_color'] = sanitize_hex_color($input['highlight_text_color'] ?? '#ffffff');
            $existing['hero_bg_color']        = sanitize_hex_color($input['hero_bg_color'] ?? '#b42318');
            $existing['hero_text_color']      = sanitize_hex_color($input['hero_text_color'] ?? '#ffffff');
            $existing['hero_button_bg_color'] = sanitize_hex_color($input['hero_button_bg_color'] ?? '#ffffff');
            $existing['hero_button_text_color'] = sanitize_hex_color($input['hero_button_text_color'] ?? '#111111');
            $existing['link_text_color'] = sanitize_hex_color($input['link_text_color'] ?? '#182230');
            $existing['link_color'] = sanitize_hex_color($input['link_color'] ?? '#0073aa');
            $existing['link_hover_color'] = sanitize_hex_color($input['link_hover_color'] ?? '#e50914');
            $existing['primary_color_dark'] = sanitize_hex_color($input['primary_color_dark'] ?? '#e50914');
            $existing['btn_bg_color_dark'] = sanitize_hex_color($input['btn_bg_color_dark'] ?? '#1e1e1e');
            $existing['highlight_bg_color_dark'] = sanitize_hex_color($input['highlight_bg_color_dark'] ?? '#e50914');
            $existing['highlight_text_color_dark'] = sanitize_hex_color($input['highlight_text_color_dark'] ?? '#ffffff');
            $existing['hero_bg_color_dark'] = sanitize_hex_color($input['hero_bg_color_dark'] ?? '#1e1e1e');
            $existing['hero_text_color_dark'] = sanitize_hex_color($input['hero_text_color_dark'] ?? '#ffffff');
            $existing['hero_button_bg_color_dark'] = sanitize_hex_color($input['hero_button_bg_color_dark'] ?? '#ffffff');
            $existing['hero_button_text_color_dark'] = sanitize_hex_color($input['hero_button_text_color_dark'] ?? '#111111');
            $existing['link_text_color_dark'] = sanitize_hex_color($input['link_text_color_dark'] ?? '#ffffff');
            $existing['link_color_dark'] = sanitize_hex_color($input['link_color_dark'] ?? '#90caf9');
            $existing['link_hover_color_dark'] = sanitize_hex_color($input['link_hover_color_dark'] ?? '#ff6b6b');
            $style_colors = array(
                'canvas_bg' => '#ffffff', 'canvas_text' => '#333333', 'heading_text' => '#111111', 'surface_bg' => '#f8f9fa',
                'surface_text' => '#1f2937', 'border' => '#dfe3e8', 'muted_text' => '#6c757d',
                'shadow' => '#182230', 'spotlight_bg' => '#f8f9fa', 'spotlight_text' => '#1f2937',
                'badge_bg' => '#182230'
            );
            $style_colors_dark = array(
                'canvas_bg' => '#121212', 'canvas_text' => '#e0e0e0', 'heading_text' => '#ffffff', 'surface_bg' => '#1e1e1e',
                'surface_text' => '#f3f4f6', 'border' => '#3f3f46', 'muted_text' => '#9e9e9e',
                'shadow' => '#000000', 'spotlight_bg' => '#0d0d11', 'spotlight_text' => '#f3f4f6',
                'badge_bg' => '#000000'
            );
            foreach ($style_colors as $key => $default) {
                $existing[$key . '_color'] = sanitize_hex_color($input[$key . '_color'] ?? $default);
                $existing[$key . '_color_dark'] = sanitize_hex_color($input[$key . '_color_dark'] ?? $style_colors_dark[$key]);
            }
        }

        // 2. ECOSYSTEM TAB
        if ($active_tab === 'ecosystem') {
            $core_ok = $this->dependency_available('core');
            foreach (array_keys($this->get_ticket_toggle_map()) as $ticket_flag) {
                $existing[$ticket_flag] = isset($input[$ticket_flag]) && $core_ok ? 1 : 0;
            }
            $existing['tickets_icon_type']   = $this->sanitize_icon_type($input['tickets_icon_type'] ?? 'dashicon');
            $existing['tickets_icon']        = $this->sanitize_icon_value($input['tickets_icon'] ?? '', $existing['tickets_icon_type']);
            $existing['enable_subscription'] = isset($input['enable_subscription']) ? 1 : 0;
            $existing['subscription_url']    = esc_url_raw($input['subscription_url'] ?? '');
            $existing['subscription_icon_type'] = $this->sanitize_icon_type($input['subscription_icon_type'] ?? 'dashicon');
            $existing['subscription_icon']   = $this->sanitize_icon_value($input['subscription_icon'] ?? '', $existing['subscription_icon_type']);
            $existing['enable_auditions']   = isset($input['enable_auditions']) && $this->dependency_available('members') ? 1 : 0;
            $existing['auditions_icon_type'] = $this->sanitize_icon_type($input['auditions_icon_type'] ?? 'dashicon');
            $existing['auditions_icon']      = $this->sanitize_icon_value($input['auditions_icon'] ?? '', $existing['auditions_icon_type']);
            $existing['enable_events']      = isset($input['enable_events']) && $this->dependency_available('members') ? 1 : 0;
            $existing['events_icon_type']    = $this->sanitize_icon_type($input['events_icon_type'] ?? 'dashicon');
            $existing['events_icon']         = $this->sanitize_icon_value($input['events_icon'] ?? '', $existing['events_icon_type']);
            $existing['enable_media']       = isset($input['enable_media']) && $this->dependency_available('media') ? 1 : 0;
            $existing['media_url']          = esc_url_raw($input['media_url'] ?? '');
            $existing['media_icon_type']     = $this->sanitize_icon_type($input['media_icon_type'] ?? 'dashicon');
            $existing['media_icon']          = $this->sanitize_icon_value($input['media_icon'] ?? '', $existing['media_icon_type']);
        }

        // 3. EXTRA UNLIMITED CUSTOM LINKS TAB
        if ($active_tab === 'custom') {
            $clean_custom = array();
            if (!empty($input['custom_links']) && is_array($input['custom_links'])) {
                foreach ($input['custom_links'] as $link) {
                    if (!empty($link['title']) && !empty($link['url'])) {
                        $clean_custom[] = array(
                            'id'    => preg_replace('/[^a-z0-9]/', '', sanitize_key($link['id'] ?? '')) ?: substr(md5(uniqid('', true)), 0, 8),
                            'title' => sanitize_text_field($link['title']),
                            'url'   => esc_url_raw($link['url']),
                            'icon_type' => $this->sanitize_icon_type($link['icon_type'] ?? 'image'),
                            'icon'  => $this->sanitize_icon_value($link['icon'] ?? '', $link['icon_type'] ?? 'image')
                        );
                    }
                }
            }
            $existing['custom_links'] = $clean_custom;
        }

        // 5. ORDER TAB
        if ($active_tab === 'order') {
            $order_raw = isset($input['link_order']) ? sanitize_text_field(wp_unslash($input['link_order'])) : '';
            $order_keys = array_filter(array_map(function ($key) {
                return strtolower(preg_replace('/[^a-zA-Z0-9:_-]/', '', $key));
            }, explode(',', $order_raw)));
            $existing['link_order'] = array_values($order_keys);
        }

        // 4. SOCIAL MEDIA TAB
        if ($active_tab === 'social') {
            $social_placement = sanitize_key($input['social_placement'] ?? ($existing['social_placement'] ?? 'icons'));
            $existing['social_placement'] = in_array($social_placement, array('icons', 'tree'), true) ? $social_placement : 'icons';
            $social_data = array();
            foreach ($this->social_platforms as $key => $platform) {
                $enabled = isset($input['social'][$key]['enable']) ? 1 : 0;
                $val     = sanitize_text_field($input['social'][$key]['value'] ?? '');
                $placement = sanitize_key($input['social'][$key]['placement'] ?? ($existing['social'][$key]['placement'] ?? $existing['social_placement']));
                $social_data[$key] = array(
                    'enable' => $enabled,
                    'value'  => $val,
                    'placement' => in_array($placement, array('icons', 'tree'), true) ? $placement : 'icons'
                );
            }
            $existing['social'] = $social_data;
        }

        return $existing;
    }

    public function render_admin_page() {
        $options = get_option('sk_onelink_settings', array());
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'styles';
        $current_style = $options['display_style'] ?? 'classic';
        $page_slug = isset($_GET['page']) ? sanitize_key($_GET['page']) : 'stagekit-onelink';
        $font_preview_stacks = array(
            'system' => '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            'inter' => 'Inter, sans-serif',
            'dm-sans' => '"DM Sans", sans-serif',
            'space-grotesk' => '"Space Grotesk", sans-serif',
            'source-sans' => '"Source Sans 3", sans-serif',
            'merriweather' => 'Merriweather, Georgia, serif'
        );
        $selected_font = $options['font_family'] ?? 'system';
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">StageKitWP OneLink Settings</h1>
            <hr class="wp-header-end">

            <h2 class="nav-tab-wrapper">
                <a href="?page=<?php echo $page_slug; ?>&tab=styles" class="nav-tab <?php echo $active_tab == 'styles' ? 'nav-tab-active' : ''; ?>">🎨 Style & Display</a>
                <a href="?page=<?php echo $page_slug; ?>&tab=ecosystem" class="nav-tab <?php echo $active_tab == 'ecosystem' ? 'nav-tab-active' : ''; ?>">⚡ StageKit Ecosystem</a>
                <a href="?page=<?php echo $page_slug; ?>&tab=custom" class="nav-tab <?php echo $active_tab == 'custom' ? 'nav-tab-active' : ''; ?>">🔗 Extra Links (Unlimited)</a>
                <a href="?page=<?php echo $page_slug; ?>&tab=social" class="nav-tab <?php echo $active_tab == 'social' ? 'nav-tab-active' : ''; ?>">📱 Social Networks</a>
                <a href="?page=<?php echo $page_slug; ?>&tab=order" class="nav-tab <?php echo $active_tab == 'order' ? 'nav-tab-active' : ''; ?>">🔀 Order</a>
            </h2>

            <form method="post" action="options.php" style="margin-top:20px;">
                <?php settings_fields('sk_onelink_group'); ?>
                <input type="hidden" name="sk_onelink_active_tab" value="<?php echo esc_attr($active_tab); ?>">

                <!-- TAB 1: STYLES -->
                <?php if ($active_tab == 'styles') : ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="sk_display_style">Default View Layout</label></th>
                            <td>
                                <select name="sk_onelink_settings[display_style]" id="sk_display_style">
                                    <option value="classic" <?php selected($current_style, 'classic'); ?>>Classic List (Linktree Style)</option>
                                    <option value="grid" <?php selected($current_style, 'grid'); ?>>Compact 2-Column Grid</option>
                                    <option value="hero" <?php selected($current_style, 'hero'); ?>>Featured Hero Card + List</option>
                                    <option value="glass" <?php selected($current_style, 'glass'); ?>>Minimal Glassmorphism</option>
                                    <option value="spotlight" <?php selected($current_style, 'spotlight'); ?>>Stage Spotlight (Dark Theatre Theme)</option>
                                    <option value="editorial" <?php selected($current_style, 'editorial'); ?>>Editorial Programme</option>
                                    <option value="ticket-booth" <?php selected($current_style, 'ticket-booth'); ?>>Ticket Booth</option>
                                    <option value="marquee" <?php selected($current_style, 'marquee'); ?>>Marquee Board</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="sk_font_family">OneLink Font</label></th>
                            <td>
                                <select name="sk_onelink_settings[font_family]" id="sk_font_family">
                                    <option value="system" <?php selected($options['font_family'] ?? 'system', 'system'); ?>>System Sans</option>
                                    <option value="inter" <?php selected($options['font_family'] ?? 'system', 'inter'); ?>>Inter</option>
                                    <option value="dm-sans" <?php selected($options['font_family'] ?? 'system', 'dm-sans'); ?>>DM Sans</option>
                                    <option value="space-grotesk" <?php selected($options['font_family'] ?? 'system', 'space-grotesk'); ?>>Space Grotesk</option>
                                    <option value="source-sans" <?php selected($options['font_family'] ?? 'system', 'source-sans'); ?>>Source Sans 3</option>
                                    <option value="merriweather" <?php selected($options['font_family'] ?? 'system', 'merriweather'); ?>>Merriweather</option>
                                </select>
                                <p id="sk-font-preview" style="font-family: <?php echo esc_attr($font_preview_stacks[$selected_font] ?? $font_preview_stacks['system']); ?>; font-size:1.15rem; margin:12px 0 4px; padding:12px 14px; border-left:3px solid #b42318; background:#f8f9fa;">The quick brown fox jumps over the lazy dog.</p>
                                <p class="description">The selected font is loaded for the OneLink shortcode only.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Header Text</th>
                            <td>
                                <label for="sk_header_title">Title:<br>
                                    <input type="text" class="regular-text" id="sk_header_title" name="sk_onelink_settings[header_title]" value="<?php echo esc_attr($options['header_title'] ?? 'Milton Players Theatre Group'); ?>">
                                </label><br><br>
                                <label for="sk_header_subtitle">Subtitle:<br>
                                    <input type="text" class="regular-text" id="sk_header_subtitle" name="sk_onelink_settings[header_subtitle]" value="<?php echo esc_attr($options['header_subtitle'] ?? 'Live Theatre & Season Content Hub'); ?>">
                                </label>
                                <p class="description">These values are used by the OneLink shortcode header.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Preset Surface Palette</th>
                            <td>
                                <p class="description">Each row provides the light value first and its dark-mode pair second.</p>
                                <table class="widefat striped" style="max-width:720px;">
                                    <thead><tr><th>Color role</th><th>Light theme</th><th>Dark theme</th></tr></thead>
                                    <?php
                                    $palette_fields = array(
                                        'primary' => array('Accent color', '#b42318', '#e50914'),
                                        'btn_bg' => array('Button background', '#eef2f6', '#1e1e1e'),
                                        'highlight_bg' => array('Highlight background', '#b42318', '#e50914'),
                                        'highlight_text' => array('Highlight text', '#ffffff', '#ffffff'),
                                        'hero_bg' => array('Hero background', '#b42318', '#1e1e1e'),
                                        'hero_text' => array('Hero text', '#ffffff', '#ffffff'),
                                        'hero_button_bg' => array('Hero button background', '#ffffff', '#ffffff'),
                                        'hero_button_text' => array('Hero button text', '#111111', '#111111'),
                                        'link_text' => array('Main link text', '#182230', '#ffffff'),
                                        'link' => array('Link color', '#0073aa', '#90caf9'),
                                        'link_hover' => array('Link hover color', '#e50914', '#ff6b6b'),
                                        'canvas_bg' => array('Canvas background', '#ffffff', '#121212'),
                                        'canvas_text' => array('Canvas text', '#333333', '#e0e0e0'),
                                        'heading_text' => array('Heading text', '#111111', '#ffffff'),
                                        'surface_bg' => array('Surface background', '#f8f9fa', '#1e1e1e'),
                                        'surface_text' => array('Surface text', '#1f2937', '#f3f4f6'),
                                        'border' => array('Borders', '#dfe3e8', '#3f3f46'),
                                        'muted_text' => array('Muted text', '#6c757d', '#9e9e9e'),
                                        'shadow' => array('Shadow tint', '#182230', '#000000'),
                                        'spotlight_bg' => array('Spotlight background', '#f8f9fa', '#0d0d11'),
                                        'spotlight_text' => array('Spotlight text', '#1f2937', '#f3f4f6'),
                                        'badge_bg' => array('Hero badge background', '#182230', '#000000')
                                    );
                                    foreach ($palette_fields as $palette_key => $palette_field) : ?>
                                        <tr>
                                            <td><?php echo esc_html($palette_field[0]); ?></td>
                                            <td><input type="color" name="sk_onelink_settings[<?php echo esc_attr($palette_key); ?>_color]" value="<?php echo esc_attr($options[$palette_key . '_color'] ?? $palette_field[1]); ?>"></td>
                                            <td><input type="color" name="sk_onelink_settings[<?php echo esc_attr($palette_key); ?>_color_dark]" value="<?php echo esc_attr($options[$palette_key . '_color_dark'] ?? $palette_field[2]); ?>"></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            </td>
                        </tr>
                    </table>
                    <section id="sk-onelink-admin-preview" class="sk-admin-preview sk-preview-light" aria-label="OneLink style preview">
                        <div class="sk-admin-preview-toolbar">
                            <strong>Live Style Preview</strong>
                            <span>
                                <button type="button" class="button sk-preview-mode is-active" data-preview-mode="light">Light</button>
                                <button type="button" class="button sk-preview-mode" data-preview-mode="dark">Dark</button>
                            </span>
                        </div>
                        <div class="sk-admin-preview-stage">
                            <div class="sk-admin-preview-card">
                                <div class="sk-admin-preview-heading" id="sk-preview-header-title"></div>
                                <div class="sk-admin-preview-subtitle" id="sk-preview-header-subtitle"></div>
                                <div class="sk-admin-preview-hero">
                                    <span class="sk-admin-preview-badge">Featured</span>
                                    <strong>Season Tickets</strong>
                                    <button type="button">Explore Now</button>
                                </div>
                                <div class="sk-admin-preview-links">
                                    <a href="#">Current Season</a>
                                    <a href="#">Upcoming Show</a>
                                    <a href="#">Photos &amp; Media</a>
                                </div>
                                <div class="sk-admin-preview-socials"><span>f</span><span>◎</span><span>▶</span></div>
                            </div>
                        </div>
                    </section>
                    <style>
                        #sk-onelink-admin-preview { max-width: 760px; margin: 24px 0 8px; border: 1px solid #ccd0d4; background: #fff; }
                        #sk-onelink-admin-preview .sk-admin-preview-toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:12px 14px; border-bottom:1px solid #ccd0d4; background:#f6f7f7; }
                        #sk-onelink-admin-preview .sk-preview-mode.is-active { background:#2271b1; color:#fff; border-color:#2271b1; }
                        #sk-onelink-admin-preview .sk-admin-preview-stage { padding:24px; background:var(--sk-preview-canvas); color:var(--sk-preview-canvas-text); }
                        #sk-onelink-admin-preview .sk-admin-preview-card { max-width:480px; margin:auto; padding:24px; text-align:center; font-family:var(--sk-preview-font); background:var(--sk-preview-canvas); color:var(--sk-preview-canvas-text); border:1px solid var(--sk-preview-border); }
                        #sk-onelink-admin-preview .sk-admin-preview-heading { color:var(--sk-preview-heading); font-size:22px; font-weight:700; }
                        #sk-onelink-admin-preview .sk-admin-preview-subtitle { color:var(--sk-preview-muted); font-size:13px; margin:4px 0 18px; }
                        #sk-onelink-admin-preview .sk-admin-preview-hero { display:flex; flex-direction:column; align-items:flex-start; gap:9px; padding:18px; margin-bottom:12px; text-align:left; background:var(--sk-preview-hero); color:var(--sk-preview-hero-text); border-radius:12px; }
                        #sk-onelink-admin-preview .sk-admin-preview-badge { padding:3px 7px; border-radius:4px; background:var(--sk-preview-badge); color:var(--sk-preview-hero-text); font-size:11px; text-transform:uppercase; }
                        #sk-onelink-admin-preview .sk-admin-preview-hero button { padding:7px 12px; border:0; border-radius:5px; background:var(--sk-preview-hero-button-bg); color:var(--sk-preview-hero-button-text); font-weight:600; }
                        #sk-onelink-admin-preview .sk-admin-preview-links { display:flex; flex-direction:column; gap:9px; }
                        #sk-onelink-admin-preview .sk-admin-preview-links a { display:block; padding:13px 15px; border-radius:9px; background:var(--sk-preview-btn-bg); color:var(--sk-preview-link-text); text-decoration:none; border:1px solid var(--sk-preview-border); }
                        #sk-onelink-admin-preview .sk-admin-preview-links a:hover { color:var(--sk-preview-link); }
                        #sk-onelink-admin-preview .sk-admin-preview-socials { display:flex; justify-content:center; gap:14px; margin-top:18px; color:var(--sk-preview-link); }
                        #sk-onelink-admin-preview.sk-preview-dark .sk-admin-preview-stage { background:var(--sk-preview-canvas); }
                        #sk-onelink-admin-preview.sk-preview-grid .sk-admin-preview-links { display:grid; grid-template-columns:repeat(2,1fr); }
                        #sk-onelink-admin-preview.sk-preview-grid .sk-admin-preview-links a:first-child { grid-column:span 2; background:var(--sk-preview-highlight); color:var(--sk-preview-highlight-text); }
                        #sk-onelink-admin-preview.sk-preview-editorial .sk-admin-preview-card { max-width:620px; text-align:left; border-top:5px solid var(--sk-preview-primary); }
                        #sk-onelink-admin-preview.sk-preview-editorial .sk-admin-preview-links a { padding-left:4px; background:transparent; border:0; border-bottom:1px solid var(--sk-preview-border); border-radius:0; color:var(--sk-preview-surface-text); }
                        #sk-onelink-admin-preview.sk-preview-ticket-booth .sk-admin-preview-card { background:var(--sk-preview-surface); }
                        #sk-onelink-admin-preview.sk-preview-ticket-booth .sk-admin-preview-hero { margin:-24px -24px 18px; border-radius:0; }
                        #sk-onelink-admin-preview.sk-preview-marquee .sk-admin-preview-card { background:var(--sk-preview-surface); border:3px solid var(--sk-preview-highlight); box-shadow:0 0 0 5px var(--sk-preview-canvas); }
                        #sk-onelink-admin-preview.sk-preview-marquee .sk-admin-preview-links a { background:var(--sk-preview-highlight); color:var(--sk-preview-highlight-text); border-radius:3px; text-transform:uppercase; }
                        #sk-onelink-admin-preview.sk-preview-spotlight .sk-admin-preview-card { background:var(--sk-preview-spotlight); color:var(--sk-preview-spotlight-text); border-color:var(--sk-preview-border); }
                        #sk-onelink-admin-preview.sk-preview-spotlight .sk-admin-preview-links a { background:var(--sk-preview-surface); color:var(--sk-preview-surface-text); }
                        #sk-onelink-admin-preview.sk-preview-glass .sk-admin-preview-card { background:color-mix(in srgb,var(--sk-preview-surface) 72%,transparent); backdrop-filter:blur(8px); }
                        #sk-onelink-admin-preview.sk-preview-glass .sk-admin-preview-links a { background:color-mix(in srgb,var(--sk-preview-surface) 90%,var(--sk-preview-canvas)); }
                        @media (max-width:600px) { #sk-onelink-admin-preview .sk-admin-preview-toolbar { align-items:flex-start; flex-direction:column; } }
                    </style>

                <!-- TAB 2: ECOSYSTEM LINKS -->
                <?php elseif ($active_tab == 'ecosystem') : ?>
                    <p>Toggle and customize icons for content generated automatically from StageKitWP CPTs.</p>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Season &amp; Show Tickets</th>
                            <td>
                                <?php $core_ok = $this->dependency_available('core'); ?>
                                <p class="description" style="margin-top:0;">Individually control the season ticket link and each time-slot show, for the current and next season.</p>
                                <div style="display:flex; gap:32px; flex-wrap:wrap; margin-bottom:12px;">
                                    <?php
                                    $ticket_season_groups = array('current' => 'Current Season', 'next' => 'Next Season');
                                    $ticket_slot_labels = array('season_ticket' => 'Season Ticket Link', 'fall_show' => 'Fall Show', 'winter_show' => 'Winter Show', 'spring_show' => 'Spring Show');
                                    foreach ($ticket_season_groups as $group_key => $group_label) : ?>
                                        <div>
                                            <strong><?php echo esc_html($group_label); ?></strong><br>
                                            <?php foreach ($ticket_slot_labels as $slot_key => $slot_label) :
                                                $flag = 'enable_' . $group_key . '_' . $slot_key;
                                            ?>
                                                <label>
                                                    <input type="checkbox" name="sk_onelink_settings[<?php echo esc_attr($flag); ?>]" value="1" <?php checked(1, $options[$flag] ?? ($options['enable_tickets'] ?? 0)); ?> <?php disabled(!$core_ok); ?>>
                                                    <?php echo esc_html($slot_label); ?>
                                                </label><br>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php $this->render_icon_controls('tickets_icon', $options['tickets_icon'] ?? 'tickets-alt', $options['tickets_icon_type'] ?? (filter_var($options['tickets_icon'] ?? '', FILTER_VALIDATE_URL) ? 'image' : 'dashicon')); ?>
                                <?php if (!$core_ok) : ?><p class="description">StageKitWP Core must be active.</p><?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Season Subscription</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="sk_onelink_settings[enable_subscription]" value="1" <?php checked(1, $options['enable_subscription'] ?? 0); ?>>
                                    Auto-include Season Subscription Link
                                </label><br><br>
                                <input type="url" class="regular-text" name="sk_onelink_settings[subscription_url]" placeholder="https://example.com/subscriptions" value="<?php echo esc_attr($options['subscription_url'] ?? ''); ?>"><br><br>
                                <?php $this->render_icon_controls('subscription_icon', $options['subscription_icon'] ?? 'tickets-alt', $options['subscription_icon_type'] ?? (filter_var($options['subscription_icon'] ?? '', FILTER_VALIDATE_URL) ? 'image' : 'dashicon')); ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Auditions Listing</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="sk_onelink_settings[enable_auditions]" value="1" <?php checked(1, $options['enable_auditions'] ?? 0); ?> <?php disabled(!$this->dependency_available('members')); ?>>
                                    Auto-include Auditions & Casting Page Link
                                </label><br><br>
                                <?php $this->render_icon_controls('auditions_icon', $options['auditions_icon'] ?? 'megaphone', $options['auditions_icon_type'] ?? (filter_var($options['auditions_icon'] ?? '', FILTER_VALIDATE_URL) ? 'image' : 'dashicon')); ?>
                                <?php if (!$this->dependency_available('members')) : ?><p class="description">StageKitWP Members must be active.</p><?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Events Page</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="sk_onelink_settings[enable_events]" value="1" <?php checked(1, $options['enable_events'] ?? 0); ?> <?php disabled(!$this->dependency_available('members')); ?>>
                                    Auto-include Events & Shows Calendar Link
                                </label><br><br>
                                <?php $this->render_icon_controls('events_icon', $options['events_icon'] ?? 'calendar-alt', $options['events_icon_type'] ?? (filter_var($options['events_icon'] ?? '', FILTER_VALIDATE_URL) ? 'image' : 'dashicon')); ?>
                                <?php if (!$this->dependency_available('members')) : ?><p class="description">StageKitWP Members must be active.</p><?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Media Section</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="sk_onelink_settings[enable_media]" value="1" <?php checked(1, $options['enable_media'] ?? 0); ?> <?php disabled(!$this->dependency_available('media')); ?>>
                                    Include Media Gallery Link
                                </label><br><br>
                                <input type="url" class="regular-text" name="sk_onelink_settings[media_url]" placeholder="https://example.com/media" value="<?php echo esc_attr($options['media_url'] ?? ''); ?>"><br><br>
                                <?php $this->render_icon_controls('media_icon', $options['media_icon'] ?? 'format-gallery', $options['media_icon_type'] ?? (filter_var($options['media_icon'] ?? '', FILTER_VALIDATE_URL) ? 'image' : 'dashicon')); ?>
                                <?php if (!$this->dependency_available('media')) : ?><p class="description">StageKitWP Media must be active.</p><?php endif; ?>
                            </td>
                        </tr>
                    </table>

                <!-- TAB 3: UNLIMITED CUSTOM EXTRA LINKS -->
                <?php elseif ($active_tab == 'custom') : ?>
                    <p>Add unlimited custom links with title, URL, and an optional image icon.</p>
                    <div id="sk-custom-repeater-container">
                        <?php
                        $custom_links = $options['custom_links'] ?? array();
                        if (empty($custom_links)) {
                            $custom_links = array(array('title' => '', 'url' => '', 'icon' => ''));
                        }
                        foreach ($custom_links as $i => $link) : ?>
                            <div class="sk-link-row" style="background:#f9f9f9; border:1px solid #ddd; padding:12px; margin-bottom:10px; border-radius:6px;">
                                <input type="hidden" name="sk_onelink_settings[custom_links][<?php echo $i; ?>][id]" value="<?php echo esc_attr($link['id'] ?? ''); ?>">
                                <p style="margin:0 0 8px 0; display:flex; gap:10px; align-items:center;">
                                    <strong>Link #<span class="sk-row-num"><?php echo $i + 1; ?></span></strong>
                                    <button type="button" class="button button-link-delete sk-remove-row-btn" style="color:#b32d2e; margin-left:auto;">Remove</button>
                                </p>
                                <p style="margin:0 0 8px 0;">
                                    <input type="text" style="width:48%;" name="sk_onelink_settings[custom_links][<?php echo $i; ?>][title]" value="<?php echo esc_attr($link['title']); ?>" placeholder="Title (e.g. Donate / Store)">
                                    <input type="url" style="width:48%;" name="sk_onelink_settings[custom_links][<?php echo $i; ?>][url]" value="<?php echo esc_attr($link['url']); ?>" placeholder="https://example.com">
                                </p>
                                <p style="margin:0;">
                                    <select name="sk_onelink_settings[custom_links][<?php echo $i; ?>][icon_type]" class="sk-icon-type">
                                        <option value="dashicon" <?php selected($link['icon_type'] ?? 'image', 'dashicon'); ?>>Dashicon</option>
                                        <option value="image" <?php selected($link['icon_type'] ?? 'image', 'image'); ?>>Image</option>
                                    </select>
                                    <select name="sk_onelink_settings[custom_links][<?php echo $i; ?>][icon]" class="sk-icon-dashicon" <?php disabled(($link['icon_type'] ?? 'image'), 'image'); ?>>
                                        <?php foreach ($this->dashicons as $icon => $label) : ?><option value="<?php echo esc_attr($icon); ?>" <?php selected($link['icon'] ?? '', $icon); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?>
                                    </select>
                                    <input type="text" class="regular-text sk-icon-url" name="sk_onelink_settings[custom_links][<?php echo $i; ?>][icon]" value="<?php echo esc_attr(($link['icon_type'] ?? 'image') === 'image' ? ($link['icon'] ?? '') : ''); ?>" placeholder="Icon image URL" <?php disabled(($link['icon_type'] ?? 'image'), 'dashicon'); ?> >
                                    <button type="button" class="button sk-upload-icon-btn" <?php disabled(($link['icon_type'] ?? 'image'), 'dashicon'); ?>>Upload Icon</button>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="sk-add-link-btn" class="button button-secondary">+ Add Another Custom Link</button>

                <!-- TAB 4: SOCIAL NETWORKS -->
                <?php elseif ($active_tab == 'social') : ?>
                    <p>Enable social channels and enter profile details. <strong>Channels will only display on the page if enabled AND populated.</strong> Choose the placement for each network below.</p>
                    <table class="form-table">
                        <?php foreach ($this->social_platforms as $key => $platform) : 
                            $is_enabled = $options['social'][$key]['enable'] ?? 0;
                            $val = $options['social'][$key]['value'] ?? '';
                        ?>
                            <tr>
                                <th scope="row" style="width:200px;">
                                    <label for="sk_social_<?php echo $key; ?>">
                                        <input type="checkbox" id="sk_social_<?php echo $key; ?>" name="sk_onelink_settings[social][<?php echo $key; ?>][enable]" value="1" <?php checked(1, $is_enabled); ?>>
                                        <strong><?php echo esc_html($platform['label']); ?></strong>
                                    </label>
                                </th>
                                <td>
                                    <input type="text" class="regular-text" name="sk_onelink_settings[social][<?php echo $key; ?>][value]" value="<?php echo esc_attr($val); ?>" placeholder="<?php echo esc_attr($platform['placeholder']); ?>">
                                    <select name="sk_onelink_settings[social][<?php echo $key; ?>][placement]" aria-label="<?php echo esc_attr($platform['label']); ?> placement">
                                        <option value="icons" <?php selected($options['social'][$key]['placement'] ?? ($options['social_placement'] ?? 'icons'), 'icons'); ?>>Bottom icons</option>
                                        <option value="tree" <?php selected($options['social'][$key]['placement'] ?? ($options['social_placement'] ?? 'icons'), 'tree'); ?>>Link tree</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>

                <!-- TAB 5: ORDER -->
                <?php elseif ($active_tab == 'order') : ?>
                    <p>Drag to arrange how Ecosystem, Custom, and tree-placed Social links appear together in the OneLink tree. The Featured/Hero item (if any) always leads, per the selected style.</p>
                    <?php $order_blocks = $this->get_order_blocks($options); ?>
                    <?php if (empty($order_blocks)) : ?>
                        <p class="description">Nothing to order yet &mdash; enable an Ecosystem link, add a Custom Link, or place a Social network in the tree.</p>
                    <?php else : ?>
                        <ul id="sk-order-list" style="max-width:520px; margin:0;">
                            <?php foreach ($order_blocks as $block) : ?>
                                <li class="sk-order-row" data-key="<?php echo esc_attr($block['key']); ?>" style="display:flex; align-items:center; gap:10px; background:#f9f9f9; border:1px solid #ddd; padding:10px 14px; margin-bottom:6px; border-radius:6px; cursor:move;">
                                    <span class="dashicons dashicons-menu" aria-hidden="true" style="opacity:.5;"></span>
                                    <strong style="flex:1;"><?php echo esc_html($block['label']); ?></strong>
                                    <span class="description"><?php echo esc_html($block['source']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <input type="hidden" id="sk_link_order_field" name="sk_onelink_settings[link_order]" value="<?php echo esc_attr(implode(',', wp_list_pluck($order_blocks, 'key'))); ?>">
                    <?php endif; ?>
                <?php endif; ?>

                <?php submit_button(); ?>
            </form>

            <script>
            jQuery(document).ready(function($) {
                var fontPreviewStacks = <?php echo wp_json_encode($font_preview_stacks); ?>;
                var previewPalette = {
                    primary: ['#b42318', '#e50914'], btn_bg: ['#eef2f6', '#1e1e1e'],
                    highlight_bg: ['#b42318', '#e50914'], highlight_text: ['#ffffff', '#ffffff'],
                    hero_bg: ['#b42318', '#1e1e1e'], hero_text: ['#ffffff', '#ffffff'],
                    hero_button_bg: ['#ffffff', '#ffffff'], hero_button_text: ['#111111', '#111111'],
                    link_text: ['#182230', '#ffffff'], link: ['#0073aa', '#90caf9'],
                    link_hover: ['#e50914', '#ff6b6b'], canvas_bg: ['#ffffff', '#121212'],
                    canvas_text: ['#333333', '#e0e0e0'], heading_text: ['#111111', '#ffffff'],
                    surface_bg: ['#f8f9fa', '#1e1e1e'], surface_text: ['#1f2937', '#f3f4f6'],
                    border: ['#dfe3e8', '#3f3f46'], muted_text: ['#6c757d', '#9e9e9e'],
                    spotlight_bg: ['#f8f9fa', '#0d0d11'], spotlight_text: ['#1f2937', '#f3f4f6'],
                    badge_bg: ['#182230', '#000000']
                };
                function updateStylePreview() {
                    var preview = $('#sk-onelink-admin-preview');
                    if (!preview.length) return;
                    var mode = preview.hasClass('sk-preview-dark') ? 1 : 0;
                    var style = $('#sk_display_style').val() || 'classic';
                    $('#sk-preview-header-title').text($('#sk_header_title').val() || 'Milton Players Theatre Group');
                    $('#sk-preview-header-subtitle').text($('#sk_header_subtitle').val() || 'Live Theatre & Season Content Hub');
                    preview.removeClass('sk-preview-classic sk-preview-grid sk-preview-hero sk-preview-glass sk-preview-spotlight sk-preview-editorial sk-preview-ticket-booth sk-preview-marquee').addClass('sk-preview-' + style);
                    var previewVariableMap = {
                        primary: 'primary', btn_bg: 'btn-bg', highlight_bg: 'highlight', highlight_text: 'highlight-text',
                        hero_bg: 'hero', hero_text: 'hero-text', hero_button_bg: 'hero-button-bg', hero_button_text: 'hero-button-text',
                        link_text: 'link-text', link: 'link', link_hover: 'link-hover', canvas_bg: 'canvas', canvas_text: 'canvas-text',
                        heading_text: 'heading', surface_bg: 'surface', surface_text: 'surface-text', border: 'border', muted_text: 'muted',
                        spotlight_bg: 'spotlight', spotlight_text: 'spotlight-text', badge_bg: 'badge'
                    };
                    $.each(previewPalette, function(key, defaults) {
                        var field = $('input[name="sk_onelink_settings[' + key + '_color' + (mode ? '_dark' : '') + ']"]');
                        preview[0].style.setProperty('--sk-preview-' + previewVariableMap[key], field.val() || defaults[mode]);
                    });
                    preview[0].style.setProperty('--sk-preview-font', fontPreviewStacks[$('#sk_font_family').val()] || fontPreviewStacks.system);
                }
                $('#sk_font_family').on('change', function() {
                    $('#sk-font-preview').css('font-family', fontPreviewStacks[$(this).val()] || fontPreviewStacks.system);
                    updateStylePreview();
                });
                $('#sk_display_style').on('change', updateStylePreview);
                $('#sk_header_title, #sk_header_subtitle').on('input change', updateStylePreview);
                $('#sk-onelink-admin-preview input[type="color"]').on('input change', updateStylePreview);
                $('.sk-preview-mode').on('click', function() {
                    $('.sk-preview-mode').removeClass('is-active');
                    $(this).addClass('is-active');
                    $('#sk-onelink-admin-preview').toggleClass('sk-preview-dark', $(this).data('preview-mode') === 'dark').toggleClass('sk-preview-light', $(this).data('preview-mode') === 'light');
                    updateStylePreview();
                });
                updateStylePreview();

                $(document).on('change', '.sk-icon-type', function() {
                    var isImage = $(this).val() === 'image';
                    var row = $(this).closest('td, .sk-link-row');
                    row.find('.sk-icon-dashicon').prop('disabled', isImage);
                    row.find('.sk-icon-url, .sk-upload-icon-btn').prop('disabled', !isImage);
                });

                $(document).on('click', '.sk-upload-icon-btn', function(e) {
                    e.preventDefault();
                    var btn = $(this);
                    var inputField = btn.siblings('.sk-icon-url');
                    var customUploader = wp.media({
                        title: 'Select or Upload Link Icon',
                        button: { text: 'Use this Icon' },
                        multiple: false
                    }).on('select', function() {
                        var attachment = customUploader.state().get('selection').first().toJSON();
                        inputField.val(attachment.url);
                    }).open();
                });

                $('#sk-add-link-btn').on('click', function() {
                    var container = $('#sk-custom-repeater-container');
                    var count = container.find('.sk-link-row').length;
                    var newRow = `
                        <div class="sk-link-row" style="background:#f9f9f9; border:1px solid #ddd; padding:12px; margin-bottom:10px; border-radius:6px;">
                            <input type="hidden" name="sk_onelink_settings[custom_links][${count}][id]" value="">
                            <p style="margin:0 0 8px 0; display:flex; gap:10px; align-items:center;">
                                <strong>Link #<span class="sk-row-num">${count + 1}</span></strong>
                                <button type="button" class="button button-link-delete sk-remove-row-btn" style="color:#b32d2e; margin-left:auto;">Remove</button>
                            </p>
                            <p style="margin:0 0 8px 0;">
                                <input type="text" style="width:48%;" name="sk_onelink_settings[custom_links][${count}][title]" placeholder="Title (e.g. Donate / Store)">
                                <input type="url" style="width:48%;" name="sk_onelink_settings[custom_links][${count}][url]" placeholder="https://example.com">
                            </p>
                            <p style="margin:0;">
                                <select name="sk_onelink_settings[custom_links][${count}][icon_type]" class="sk-icon-type">
                                    <option value="dashicon">Dashicon</option><option value="image" selected>Image</option>
                                </select>
                                <select name="sk_onelink_settings[custom_links][${count}][icon]" class="sk-icon-dashicon" disabled>
                                    <?php foreach ($this->dashicons as $icon => $label) : ?><option value="<?php echo esc_attr($icon); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?>
                                </select>
                                <input type="text" class="regular-text sk-icon-url" name="sk_onelink_settings[custom_links][${count}][icon]" placeholder="Icon image URL" >
                                <button type="button" class="button sk-upload-icon-btn">Upload Icon</button>
                            </p>
                        </div>`;
                    container.append(newRow);
                });

                $(document).on('click', '.sk-remove-row-btn', function() {
                    $(this).closest('.sk-link-row').remove();$('#sk-custom-repeater-container .sk-link-row').each(function(index) {
                        $(this).find('.sk-row-num').text(index + 1);
                    });
                });

                var orderList = $('#sk-order-list');
                if (orderList.length) {
                    function syncOrderField() {
                        var keys = orderList.find('.sk-order-row').map(function() { return $(this).data('key'); }).get();
                        $('#sk_link_order_field').val(keys.join(','));
                    }
                    orderList.sortable({ axis: 'y', update: syncOrderField });
                    syncOrderField();
                }
            });
            </script>
        </div>
        <?php
    }

    private function render_link_icon($link) {
        $type = $this->sanitize_icon_type($link['icon_type'] ?? (filter_var($link['icon'] ?? '', FILTER_VALIDATE_URL) ? 'image' : 'dashicon'));
        $value = $this->sanitize_icon_value($link['icon'] ?? '', $type);
        if ('image' === $type && $value) {
            return '<img src="' . esc_url($value) . '" class="sk-btn-icon" alt="">';
        }
        if ('dashicon' === $type && $value) {
            return '<span class="dashicons dashicons-' . esc_attr($value) . ' sk-btn-icon" aria-hidden="true"></span>';
        }
        return '';
    }

    private function build_social_url($key,$value) {
        $value = trim($value);
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        $value = ltrim($value, '@');

        switch ($key) {
            case 'facebook':
                return 'https://facebook.com/' . $value;
            case 'x':
                return 'https://x.com/' . $value;
            case 'instagram':
                return 'https://instagram.com/' . $value;
            case 'tiktok':
                return 'https://tiktok.com/@' . $value;
            case 'threads':
                return 'https://threads.net/@' . $value;
            case 'bluesky':
                return 'https://bsky.app/profile/' . $value;
            case 'spotify':
                return 'https://open.spotify.com/user/' . $value;
            case 'pinterest':
                return 'https://pinterest.com/' . $value;
            case 'youtube':
                return 'https://youtube.com/@' . $value;
            case 'linkedin':
                return 'https://linkedin.com/' . $value;
            case 'snapchat':
                return 'https://snapchat.com/add/' . $value;
            case 'whatsapp':
                return 'https://wa.me/' . preg_replace('/[^0-9]/', '', $value);
            default:
                return '#';
        }
    }

    private function get_social_svg($key) {$svgs = array(
            'facebook'  => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
            'x'         => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
            'instagram' => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>',
            'tiktok'    => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12.525 0h3.08c.024 1.341.488 2.65 1.332 3.687.843 1.038 2.015 1.776 3.328 2.091v3.23c-1.393-.032-2.754-.428-3.957-1.15-.316-.192-.612-.412-.885-.658v8.623c.002 1.636-.457 3.238-1.325 4.621a8.497 8.497 0 0 1-3.56 3.125c-1.57.732-3.322.956-5.02.642a8.558 8.558 0 0 1-4.46-2.247 8.41 8.41 0 0 1-2.484-4.321c-.426-1.684-.236-3.46.545-5.022a8.513 8.513 0 0 1 3.298-3.385c1.47-.84 3.155-1.18 4.832-.976v3.3a5.2 5.2 0 0 0-2.61.737c-.803.465-1.442 1.139-1.838 1.939a5.19 5.19 0 0 0-.27 2.651c.189.962.693 1.83 1.433 2.467a5.253 5.253 0 0 0 2.63 1.077c1.002.132 2.022-.075 2.888-.588a5.21 5.21 0 0 0 2.046-2.115 5.18 5.18 0 0 0 .61-2.474V0z"/></svg>',
            'threads'   => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12.002 0C5.373 0 0 5.373 0 12s5.373 12 12.002 12c6.627 0 11.998-5.373 11.998-12S18.629 0 12.002 0zm5.176 15.531c-.443.911-1.071 1.688-1.88 2.298-1.096.827-2.41 1.246-3.882 1.246-1.512 0-2.85-.436-3.957-1.303-1.112-.871-1.782-2.091-1.992-3.626h2.203c.187.957.625 1.71 1.312 2.261.688.551 1.516.826 2.484.826.984 0 1.801-.271 2.453-.812.652-.542 1.043-1.282 1.172-2.221H8.847v-1.932h8.318c.086.372.129.76.129 1.163 0 .768-.116 1.468-.348 2.102z"/></svg>',
            'bluesky'   => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 10.8c-1.087-2.114-4.046-6.012-6.761-7.992C2.478.81 1.37 1.322.68 1.957c-.77.708-.87 1.835-.87 2.871 0 3.332 1.82 10.024 3.754 12.08 2.057 2.188 4.707 2.113 6.136 1.258a11.16 11.16 0 0 0 2.3-1.935 11.16 11.16 0 0 0 2.3 1.935c1.429.855 4.079.93 6.136-1.258 1.934-2.056 3.754-8.748 3.754-12.08 0-1.036-.1-2.163-.87-2.871-.69-.635-1.798-1.147-4.559.851C16.046 4.788 13.087 8.686 12 10.8z"/></svg>',
            'spotify'   => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 0C5.376 0 0 5.377 0 12s5.376 12 12 12 12-5.377 12-12S18.624 0 12 0zm5.521 17.341c-.217.357-.681.469-1.038.252-2.846-1.739-6.429-2.133-10.65-1.168-.403.093-.805-.16-.898-.563-.093-.404.16-.805.563-.898 4.623-1.057 8.577-.611 11.77 1.34.357.217.469.681.253 1.039zm1.472-3.28c-.273.443-.852.584-1.295.312-3.257-2.002-8.223-2.583-12.076-1.414-.5.152-1.026-.131-1.178-.631-.152-.5.131-1.026.631-1.178 4.405-1.337 9.892-.7 13.606 1.583.443.273.584.852.312 1.295zm.135-3.411c-3.906-2.319-10.347-2.533-14.108-1.391-.6.183-1.235-.164-1.418-.764-.183-.6.164-1.235.764-1.418 4.316-1.31 11.423-1.054 15.908 1.608.541.321.72 1.02.399 1.561-.322.541-1.02.72-1.545.398z"/></svg>',
            'pinterest' => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026z"/></svg>',
            'youtube'   => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
            'linkedin'  => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.762-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>',
            'snapchat'  => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12.001 0c-4.973 0-8.053 3.558-8.053 6.945 0 2.268 1.082 3.864 1.954 4.887.217.256.326.471.258.74-.117.461-.645 2.12-.862 2.784-.108.331-.027.608.232.748.258.14 1.637.766 3.125.766.425 0 .861-.05 1.285-.152.887 1.096 1.272 1.263 2.059 1.263.788 0 1.173-.167 2.06-1.263.424.102.86.152 1.285.152 1.488 0 2.867-.626 3.125-.766.259-.14.34-.417.232-.748-.217-.664-.745-2.323-.862-2.784-.068-.269.041-.484.258-.74.872-1.023 1.954-2.619 1.954-4.887 0-3.387-3.08-6.945-8.053-6.945z"/></svg>',
            'whatsapp'  => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>'
        );

        return $svgs[$key] ?? '';
    }

    public function render_onelink_tree($atts) {
        $options = get_option('sk_onelink_settings', array());$atts = shortcode_atts(array(
            'style' => $options['display_style'] ?? 'classic'
        ), $atts, 'stagekit_onelink');

        $style = sanitize_html_class($atts['style']);$links = array();

        if (!empty($options['enable_subscription']) && !empty($options['subscription_url'])) {$links[] = array(
                'title' => 'Season Subscriptions',
                'url'   => $options['subscription_url'],
                'icon'  => $options['subscription_icon'] ?? 'tickets-alt',
                'icon_type' => $options['subscription_icon_type'] ?? 'dashicon',
                'type'  => 'featured',
                'order_key' => 'subscription'
            );
        }

        if ($this->dependency_available('core')) {
            $current_seasons = get_posts(array(
                'post_type' => 'season', 'posts_per_page' => 1, 'post_status' => 'publish',
                'meta_query' => array(array('key' => '_stagekitwp_season_is_current', 'value' => 1, 'compare' => '='))
            ));
            $season_groups = array(
                'current' => !empty($current_seasons) ? $current_seasons[0] : null,
                'next'    => function_exists('stagekitwp_get_next_season') ? stagekitwp_get_next_season() : null
            );
            $ticket_slots = array('fall_show' => 'Fall', 'winter_show' => 'Winter', 'spring_show' => 'Spring');

            foreach ($season_groups as $group_key => $season) {
                if (!$season) {
                    continue;
                }

                if (!empty($options['enable_' . $group_key . '_season_ticket'] ?? ($options['enable_tickets'] ?? 0))) {
                    $season_url = get_post_meta($season->ID, '_stagekitwp_season_tickets_url', true) ?: get_permalink($season->ID);
                    $links[] = array(
                        'title' => 'Season Tickets: ' . get_the_title($season->ID),
                        'url' => $season_url,
                        'icon' => $options['tickets_icon'] ?? 'tickets-alt',
                        'icon_type' => $options['tickets_icon_type'] ?? 'dashicon',
                        'type' => 'featured',
                        'order_key' => $group_key . '_season_ticket'
                    );
                }

                foreach ($ticket_slots as $slot_key => $slot_value) {
                    if (empty($options['enable_' . $group_key . '_' . $slot_key] ?? ($options['enable_tickets'] ?? 0))) {
                        continue;
                    }
                    // Only one show per time slot per season, per StageKitWP Core's slot convention.
                    $slot_shows = get_posts(array(
                        'post_type' => 'show', 'posts_per_page' => 1, 'post_status' => 'publish',
                        'meta_query' => array(
                            array('key' => '_stagekitwp_show_season', 'value' => $season->ID, 'compare' => '='),
                            array('key' => '_stagekitwp_show_time_slot', 'value' => $slot_value, 'compare' => '=')
                        ),
                        'orderby' => 'date', 'order' => 'ASC'
                    ));
                    if (empty($slot_shows)) {
                        continue;
                    }
                    $show_url = get_post_meta($slot_shows[0]->ID, '_stagekitwp_show_tickets_url', true) ?: get_permalink($slot_shows[0]->ID);
                    $links[] = array(
                        'title' => 'Tickets: ' . get_the_title($slot_shows[0]->ID),
                        'url' => $show_url,
                        'icon' => $options['tickets_icon'] ?? 'tickets-alt',
                        'icon_type' => $options['tickets_icon_type'] ?? 'dashicon',
                        'type' => 'show',
                        'order_key' => $group_key . '_' . $slot_key
                    );
                }
            }
        }

        if (!empty($options['enable_auditions']) && $this->dependency_available('members')) {
            $links[] = array('title' => 'Auditions & Casting', 'url' => home_url('/auditions/'), 'icon' =>$options['auditions_icon'] ?? 'megaphone', 'icon_type' => $options['auditions_icon_type'] ?? 'dashicon', 'type' => 'standard', 'order_key' => 'auditions');
        }

        if (!empty($options['enable_events']) && $this->dependency_available('members')) {
            $links[] = array('title' => 'Full Events Calendar', 'url' => home_url('/events/'), 'icon' =>$options['events_icon'] ?? 'calendar-alt', 'icon_type' => $options['events_icon_type'] ?? 'dashicon', 'type' => 'standard', 'order_key' => 'events');
        }

        if (!empty($options['enable_media']) && !empty($options['media_url']) && $this->dependency_available('media')) {$links[] = array('title' => 'Photos & Media Gallery', 'url' => $options['media_url'], 'icon' =>$options['media_icon'] ?? 'format-gallery', 'icon_type' => $options['media_icon_type'] ?? 'dashicon', 'type' => 'standard', 'order_key' => 'media');
        }

        if (!empty($options['custom_links']) && is_array($options['custom_links'])) {
            foreach ($options['custom_links'] as $ci => $custom) {
                if (!empty($custom['title']) && !empty($custom['url'])) {
                    $links[] = array('title' =>$custom['title'], 'url' => $custom['url'], 'icon' =>$custom['icon'] ?? '', 'icon_type' => $custom['icon_type'] ?? 'image', 'type' => 'custom', 'order_key' => $this->custom_link_order_key($custom, $ci));
                }
            }
        }

        // Active social networks are split per setting: tree entries are links,
        // while the rest remain in the compact footer icon row.
        $active_socials = array();
        $tree_socials = array();
        $bottom_socials = array();
        if (!empty($options['social']) && is_array($options['social'])) {
            foreach ($options['social'] as $key =>$data) {
                if (!empty($data['enable']) && !empty($data['value'])) {
                    $social_item = array(
                        'url'  => $this->build_social_url($key,$data['value']),
                        'svg'  => $this->get_social_svg($key),
                        'name' => $this->social_platforms[$key]['label'] ?? ucfirst($key)
                    );
                    $active_socials[$key] = $social_item;
                    if (($data['placement'] ?? ($options['social_placement'] ?? 'icons')) === 'tree') {
                        $tree_socials[$key] = $social_item;
                    } else {
                        $bottom_socials[$key] = $social_item;
                    }
                }
            }
        }

        if (!empty($tree_socials)) {
            foreach ($tree_socials as $s_key => $s_item) {
                $links[] = array(
                    'title' => $s_item['name'],
                    'url' => $s_item['url'],
                    'type' => 'social',
                    'social_svg' => $s_item['svg'],
                    'order_key' => 'social:' . $s_key
                );
            }
        }

        $saved_order = $options['link_order'] ?? array();
        if (!empty($saved_order) && count($links) > 1) {
            $position = array_flip($saved_order);
            $unranked = count($position);
            // Decorate with original index so ties (new/unordered items) keep insertion order regardless of PHP version.
            $decorated = array();
            foreach ($links as $seq => $link) {
                $decorated[] = array($position[$link['order_key'] ?? ''] ?? $unranked, $seq, $link);
            }
            usort($decorated, function ($a, $b) {
                return $a[0] <=> $b[0] ?: $a[1] <=> $b[1];
            });
            $links = array_column($decorated, 2);
        }

        ob_start();
        ?>
        <div class="sk-onelink-wrapper sk-view-<?php echo esc_attr($style); ?>">
            <div class="sk-onelink-header">
                <h2><?php echo esc_html($options['header_title'] ?? 'Milton Players Theatre Group'); ?></h2>
                <p class="sk-subtitle"><?php echo esc_html($options['header_subtitle'] ?? 'Live Theatre & Season Content Hub'); ?></p>
            </div>

            <?php if ($style === 'hero' && !empty($links)) : 
                $featured = array_shift($links); ?>
                <div class="sk-hero-card">
                    <span class="sk-hero-badge">Featured</span>
                    <h3>
                        <?php echo $this->render_link_icon($featured); ?>
                        <?php echo esc_html($featured['title']); ?>
                    </h3>
                    <a href="<?php echo esc_url($featured['url']); ?>" class="sk-hero-btn" target="_blank" rel="noopener">Explore Now</a>
                </div>
            <?php endif; ?>

            <div class="sk-onelink-tree">
                <?php foreach ($links as$link) : ?>
                    <a href="<?php echo esc_url($link['url']); ?>" class="sk-link-card sk-type-<?php echo esc_attr($link['type']); ?>" target="_blank" rel="noopener">
                        <?php if (!empty($link['social_svg'])) : ?>
                            <span class="sk-tree-social-icon" aria-hidden="true"><?php echo $link['social_svg']; ?></span>
                        <?php else : ?>
                            <?php echo $this->render_link_icon($link); ?>
                        <?php endif; ?>
                        <span class="sk-link-title"><?php echo esc_html($link['title']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($bottom_socials)) : ?>
                <div class="sk-onelink-socials">
                    <?php foreach ($bottom_socials as $s_key =>$s_item) : ?>
                        <a href="<?php echo esc_url($s_item['url']); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr($s_item['name']); ?>" title="<?php echo esc_attr($s_item['name']); ?>" class="sk-social-icon-link">
                            <?php echo $s_item['svg']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function enqueue_styles() {
        $options = get_option('sk_onelink_settings', array());
        $primary = esc_attr($options['primary_color'] ?? '#b42318');
        $btn_bg  = esc_attr($options['btn_bg_color'] ?? '#eef2f6');
        $highlight_bg = esc_attr($options['highlight_bg_color'] ?? $options['primary_color'] ?? '#b42318');
        $highlight_text = esc_attr($options['highlight_text_color'] ?? '#ffffff');
        $hero_bg = esc_attr($options['hero_bg_color'] ?? $options['primary_color'] ?? '#b42318');
        $hero_text = esc_attr($options['hero_text_color'] ?? '#ffffff');
        $hero_button_bg = esc_attr($options['hero_button_bg_color'] ?? '#ffffff');
        $hero_button_text = esc_attr($options['hero_button_text_color'] ?? '#111111');
        $link_color = esc_attr($options['link_color'] ?? '#0073aa');
        $link_hover = esc_attr($options['link_hover_color'] ?? '#e50914');
        $primary_dark = esc_attr($options['primary_color_dark'] ?? '#e50914');
        $btn_bg_dark = esc_attr($options['btn_bg_color_dark'] ?? '#1e1e1e');
        $highlight_bg_dark = esc_attr($options['highlight_bg_color_dark'] ?? '#e50914');
        $highlight_text_dark = esc_attr($options['highlight_text_color_dark'] ?? '#ffffff');
        $hero_bg_dark = esc_attr($options['hero_bg_color_dark'] ?? '#1e1e1e');
        $hero_text_dark = esc_attr($options['hero_text_color_dark'] ?? '#ffffff');
        $hero_button_bg_dark = esc_attr($options['hero_button_bg_color_dark'] ?? '#ffffff');
        $hero_button_text_dark = esc_attr($options['hero_button_text_color_dark'] ?? '#111111');
        $link_color_dark = esc_attr($options['link_color_dark'] ?? '#90caf9');
        $link_hover_dark = esc_attr($options['link_hover_color_dark'] ?? '#ff6b6b');
        $link_text = esc_attr($options['link_text_color'] ?? '#182230');
        $link_text_dark = esc_attr($options['link_text_color_dark'] ?? '#ffffff');
        $canvas_bg = esc_attr($options['canvas_bg_color'] ?? '#ffffff');
        $canvas_text = esc_attr($options['canvas_text_color'] ?? '#333333');
        $heading_text = esc_attr($options['heading_text_color'] ?? '#111111');
        $surface_bg = esc_attr($options['surface_bg_color'] ?? '#f8f9fa');
        $surface_text = esc_attr($options['surface_text_color'] ?? '#1f2937');
        $border = esc_attr($options['border_color'] ?? '#dfe3e8');
        $muted_text = esc_attr($options['muted_text_color'] ?? '#6c757d');
        $shadow = esc_attr($options['shadow_color'] ?? '#182230');
        $spotlight_bg = esc_attr($options['spotlight_bg_color'] ?? '#f8f9fa');
        $spotlight_text = esc_attr($options['spotlight_text_color'] ?? '#1f2937');
        $badge_bg = esc_attr($options['badge_bg_color'] ?? '#182230');
        $canvas_bg_dark = esc_attr($options['canvas_bg_color_dark'] ?? '#121212');
        $canvas_text_dark = esc_attr($options['canvas_text_color_dark'] ?? '#e0e0e0');
        $heading_text_dark = esc_attr($options['heading_text_color_dark'] ?? '#ffffff');
        $surface_bg_dark = esc_attr($options['surface_bg_color_dark'] ?? '#1e1e1e');
        $surface_text_dark = esc_attr($options['surface_text_color_dark'] ?? '#f3f4f6');
        $border_dark = esc_attr($options['border_color_dark'] ?? '#3f3f46');
        $muted_text_dark = esc_attr($options['muted_text_color_dark'] ?? '#9e9e9e');
        $shadow_dark = esc_attr($options['shadow_color_dark'] ?? '#000000');
        $spotlight_bg_dark = esc_attr($options['spotlight_bg_color_dark'] ?? '#0d0d11');
        $spotlight_text_dark = esc_attr($options['spotlight_text_color_dark'] ?? '#f3f4f6');
        $badge_bg_dark = esc_attr($options['badge_bg_color_dark'] ?? '#000000');
        $font_stacks = array(
            'system' => '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            'inter' => 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            'dm-sans' => '"DM Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            'space-grotesk' => '"Space Grotesk", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            'source-sans' => '"Source Sans 3", "Segoe UI", sans-serif',
            'merriweather' => 'Merriweather, Georgia, serif'
        );
        $font_family = $font_stacks[$options['font_family'] ?? 'system'] ?? $font_stacks['system'];

        wp_enqueue_style('dashicons');
        $web_fonts = array(
            'inter' => 'Inter:wght@400;500;600;700',
            'dm-sans' => 'DM+Sans:wght@400;500;600;700',
            'space-grotesk' => 'Space+Grotesk:wght@400;500;600;700',
            'source-sans' => 'Source+Sans+3:wght@400;600;700',
            'merriweather' => 'Merriweather:wght@400;700'
        );
        $selected_font = $options['font_family'] ?? 'system';
        if (isset($web_fonts[$selected_font])) {
            wp_enqueue_style('sk-onelink-font-' . $selected_font, 'https://fonts.googleapis.com/css2?family=' . $web_fonts[$selected_font] . '&display=swap', array(), null);
        }
        wp_register_style('sk-onelink-style', false);
        wp_enqueue_style('sk-onelink-style');

        $custom_css = "
            .sk-onelink-wrapper { --sk-primary: {$primary}; --sk-btn-bg: {$btn_bg}; --sk-highlight-bg: {$highlight_bg}; --sk-highlight-text: {$highlight_text}; --sk-hero-bg: {$hero_bg}; --sk-hero-text: {$hero_text}; --sk-hero-button-bg: {$hero_button_bg}; --sk-hero-button-text: {$hero_button_text}; --sk-link: {$link_color}; --sk-link-hover: {$link_hover}; --sk-link-text: {$link_text}; --sk-canvas-bg: {$canvas_bg}; --sk-canvas-text: {$canvas_text}; --sk-heading-text: {$heading_text}; --sk-surface-bg: {$surface_bg}; --sk-surface-text: {$surface_text}; --sk-border: {$border}; --sk-muted-text: {$muted_text}; --sk-shadow: {$shadow}; --sk-spotlight-bg: {$spotlight_bg}; --sk-spotlight-text: {$spotlight_text}; --sk-badge-bg: {$badge_bg}; --sk-font: {$font_family}; max-width: 480px; margin: 0 auto; padding: 28px 24px; text-align: center; font-family: var(--sk-font); color: var(--sk-canvas-text); background: var(--sk-canvas-bg); }
            html.stagekitwp-dark-mode .sk-onelink-wrapper { --sk-primary: {$primary_dark}; --sk-btn-bg: {$btn_bg_dark}; --sk-highlight-bg: {$highlight_bg_dark}; --sk-highlight-text: {$highlight_text_dark}; --sk-hero-bg: {$hero_bg_dark}; --sk-hero-text: {$hero_text_dark}; --sk-hero-button-bg: {$hero_button_bg_dark}; --sk-hero-button-text: {$hero_button_text_dark}; --sk-link: {$link_color_dark}; --sk-link-hover: {$link_hover_dark}; --sk-link-text: {$link_text_dark}; --sk-canvas-bg: {$canvas_bg_dark}; --sk-canvas-text: {$canvas_text_dark}; --sk-heading-text: {$heading_text_dark}; --sk-surface-bg: {$surface_bg_dark}; --sk-surface-text: {$surface_text_dark}; --sk-border: {$border_dark}; --sk-muted-text: {$muted_text_dark}; --sk-shadow: {$shadow_dark}; --sk-spotlight-bg: {$spotlight_bg_dark}; --sk-spotlight-text: {$spotlight_text_dark}; --sk-badge-bg: {$badge_bg_dark}; }
            .sk-onelink-header h2 { margin: 0 0 4px; font-size: 1.5rem; font-weight: 700; color: var(--sk-heading-text); }
            .sk-subtitle { margin: 0 0 20px; color: var(--sk-muted-text); font-size: 0.9rem; }
            .sk-link-card { display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: 650; transition: transform .2s ease, box-shadow .2s ease, background-color .2s ease, color .2s ease; gap: 10px; color: var(--sk-link-text); }
            .sk-btn-icon { width: 22px; height: 22px; object-fit: contain; flex-shrink: 0; }
            .sk-btn-icon.dashicons { font-size: 22px; line-height: 22px; width: 22px; height: 22px; }
            .sk-tree-social-icon { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; flex-shrink: 0; }
            .sk-tree-social-icon svg { display: block; width: 20px; height: 20px; fill: currentColor; }
            .sk-onelink-socials { margin-top: 24px; display: flex; flex-wrap: wrap; justify-content: center; gap: 14px; }
            .sk-social-icon-link { display: inline-flex; align-items: center; justify-content: center; color: currentColor; opacity: 0.85; transition: all 0.2s ease; text-decoration: none; }
            .sk-social-icon-link:hover { opacity: 1; transform: scale(1.15); }

            .sk-view-classic .sk-link-card { background: var(--sk-btn-bg); color: var(--sk-link-text); padding: 15px 20px; margin-bottom: 12px; border-radius: 10px; box-shadow: 0 4px 14px color-mix(in srgb, var(--sk-shadow) 10%, transparent); }
            .sk-view-classic .sk-link-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px color-mix(in srgb, var(--sk-shadow) 16%, transparent); }
            .sk-view-classic .sk-type-featured { background: var(--sk-highlight-bg); color: var(--sk-highlight-text); }

            .sk-view-grid .sk-onelink-tree { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .sk-view-grid .sk-link-card { background: var(--sk-btn-bg); color: var(--sk-link-text); padding: 18px 12px; border-radius: 12px; min-height: 70px; text-align: center; font-size: 0.88rem; box-shadow: 0 4px 14px color-mix(in srgb, var(--sk-shadow) 10%, transparent); }
            .sk-view-grid .sk-type-featured { grid-column: span 2; background: var(--sk-highlight-bg); color: var(--sk-highlight-text); }

            .sk-view-hero .sk-hero-card { background: var(--sk-hero-bg); color: var(--sk-hero-text); padding: 20px; border-radius: 14px; margin-bottom: 16px; text-align: left; }
            .sk-view-hero .sk-hero-badge { background: var(--sk-badge-bg); color: var(--sk-hero-text); font-size: 0.75rem; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
            .sk-view-hero .sk-hero-card h3 { margin: 8px 0 12px; font-size: 1.25rem; display: flex; align-items: center; gap: 8px; }
            .sk-view-hero .sk-hero-btn { display: inline-block; background: var(--sk-hero-button-bg); color: var(--sk-hero-button-text); padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; }
            .sk-view-hero .sk-link-card { background: var(--sk-surface-bg); color: var(--sk-surface-text); padding: 12px 16px; margin-bottom: 8px; border-radius: 8px; border: 1px solid color-mix(in srgb, var(--sk-border) 70%, transparent); }

            .sk-view-glass { background: color-mix(in srgb, var(--sk-surface-bg) 72%, transparent); backdrop-filter: blur(12px); border-radius: 16px; border: 1px solid var(--sk-border); }
            .sk-view-glass .sk-link-card { background: color-mix(in srgb, var(--sk-surface-bg) 90%, var(--sk-canvas-bg)); color: var(--sk-surface-text); padding: 14px 20px; margin-bottom: 10px; border-radius: 12px; border: 1px solid var(--sk-border); }
            .sk-view-glass .sk-link-card:hover { background: var(--sk-surface-bg); }

            .sk-view-spotlight { background: var(--sk-spotlight-bg); color: var(--sk-spotlight-text); border-radius: 14px; border: 1px solid var(--sk-border); box-shadow: 0 12px 30px color-mix(in srgb, var(--sk-shadow) 24%, transparent); }
            .sk-view-spotlight .sk-onelink-header h2 { color: var(--sk-highlight-bg); }
            .sk-view-spotlight .sk-link-card { background: var(--sk-surface-bg); color: var(--sk-surface-text); padding: 14px 20px; margin-bottom: 12px; border-radius: 9px; border: 1px solid var(--sk-border); }
            .sk-view-spotlight .sk-link-card:hover { border-color: var(--sk-highlight-bg); color: var(--sk-highlight-text); box-shadow: 0 0 12px color-mix(in srgb, var(--sk-highlight-bg) 28%, transparent); }
            .sk-view-spotlight .sk-type-featured { background: linear-gradient(135deg, var(--sk-highlight-bg), var(--sk-primary)); border: none; color: var(--sk-highlight-text); }

            .sk-view-editorial { max-width: 620px; text-align: left; border-top: 6px solid var(--sk-primary); }
            .sk-view-editorial .sk-onelink-header { padding: 8px 4px 18px; border-bottom: 1px solid var(--sk-border); }
            .sk-view-editorial .sk-onelink-header h2 { font-family: Georgia, serif; font-size: 2rem; }
            .sk-view-editorial .sk-link-card { justify-content: flex-start; padding: 16px 4px; color: var(--sk-surface-text); border-bottom: 1px solid var(--sk-border); }
            .sk-view-editorial .sk-link-card:hover { color: var(--sk-link); padding-left: 10px; }

            .sk-view-ticket-booth { background: var(--sk-surface-bg); border: 1px solid var(--sk-border); border-radius: 4px; }
            .sk-view-ticket-booth .sk-onelink-header { background: var(--sk-hero-bg); color: var(--sk-hero-text); margin: -24px -24px 20px; padding: 22px 18px; }
            .sk-view-ticket-booth .sk-onelink-header h2, .sk-view-ticket-booth .sk-subtitle { color: var(--sk-hero-text); }
            .sk-view-ticket-booth .sk-link-card { background: var(--sk-canvas-bg); color: var(--sk-surface-text); padding: 15px 16px; margin-bottom: 10px; border-left: 5px solid var(--sk-highlight-bg); border-radius: 2px; box-shadow: 0 2px 8px color-mix(in srgb, var(--sk-shadow) 12%, transparent); }

            .sk-view-marquee { background: var(--sk-surface-bg); color: var(--sk-surface-text); border: 3px solid var(--sk-highlight-bg); box-shadow: 0 0 0 5px var(--sk-canvas-bg), 0 0 0 8px var(--sk-highlight-bg); }
            .sk-view-marquee .sk-onelink-header h2 { color: var(--sk-surface-text); letter-spacing: .04em; text-transform: uppercase; }
            .sk-view-marquee .sk-subtitle { color: var(--sk-surface-text); opacity: .8; }
            .sk-view-marquee .sk-link-card { background: var(--sk-highlight-bg); color: var(--sk-highlight-text); padding: 15px 18px; margin-bottom: 10px; border-radius: 2px; text-transform: uppercase; letter-spacing: .03em; }
            .sk-view-marquee .sk-link-card:hover { background: var(--sk-link-hover); transform: scale(1.02); }

            html.stagekitwp-dark-mode .sk-view-ticket-booth .sk-link-card { box-shadow: 0 2px 8px color-mix(in srgb, var(--sk-shadow) 35%, transparent); }
            html.stagekitwp-dark-mode .sk-view-marquee { box-shadow: 0 0 0 5px var(--sk-canvas-bg), 0 0 0 8px var(--sk-highlight-bg); }

            /* Keep the theme's broad content-link rule from overriding OneLink's palette. */
            main .sk-onelink-wrapper a.sk-link-card { color: var(--sk-link-text) !important; }
            main .sk-onelink-wrapper a.sk-link-card:hover,
            main .sk-onelink-wrapper a.sk-link-card:focus { color: var(--sk-link-hover) !important; }
            main .sk-onelink-wrapper a.sk-type-featured,
            main .sk-onelink-wrapper .sk-view-marquee a.sk-link-card { color: var(--sk-highlight-text) !important; }
            main .sk-onelink-wrapper a.sk-type-featured:hover,
            main .sk-onelink-wrapper a.sk-type-featured:focus,
            main .sk-onelink-wrapper .sk-view-marquee a.sk-link-card:hover,
            main .sk-onelink-wrapper .sk-view-marquee a.sk-link-card:focus { color: var(--sk-highlight-text) !important; }
            main .sk-onelink-wrapper .sk-view-spotlight a.sk-link-card:hover { color: var(--sk-highlight-text) !important; }
            main .sk-onelink-wrapper .sk-view-editorial a.sk-link-card:hover { color: var(--sk-link) !important; }
            html.stagekitwp-dark-mode main .entry-content .sk-onelink-wrapper a.sk-link-card,
            html.stagekitwp-dark-mode main .entry-content .sk-onelink-wrapper a.sk-link-card:visited { color: var(--sk-link-text) !important; }
            html.stagekitwp-dark-mode main .entry-content .sk-onelink-wrapper a.sk-link-card:hover,
            html.stagekitwp-dark-mode main .entry-content .sk-onelink-wrapper a.sk-link-card:focus { color: var(--sk-link-hover) !important; }
            html.stagekitwp-dark-mode main .entry-content .sk-onelink-wrapper a.sk-type-featured,
            html.stagekitwp-dark-mode main .entry-content .sk-onelink-wrapper .sk-view-marquee a.sk-link-card,
            html.stagekitwp-dark-mode main .entry-content .sk-onelink-wrapper .sk-view-spotlight a.sk-link-card:hover { color: var(--sk-highlight-text) !important; }
            html.stagekitwp-dark-mode main .entry-content .sk-onelink-wrapper .sk-view-editorial a.sk-link-card:hover { color: var(--sk-link) !important; }
        ";
        wp_add_inline_style('sk-onelink-style', $custom_css);
    }
}

new StageKitWP_OneLink();