<?php
/**
 * TM Members Area – Navigation Menu Integration
 *
 * Adds a "TM Members" panel to Appearance → Menus so editors can drag
 * permission-aware links into any menu — exactly like Pages or Custom Links.
 * Each item type stores a visibility rule in post meta (_stagekitwp_members_type).
 * At render time wp_nav_menu_objects removes items the visitor cannot see
 * and rewrites placeholder URLs to real ones.
 *
 * LINK TYPES
 *   stagekitwp_login         → Log In             visible: logged-out only
 *   stagekitwp_logout        → Log Out            visible: logged-in only
 *   stagekitwp_profile       → My Profile         requires: CAP_MANAGE_PROFILE
 *   stagekitwp_events        → Events             requires: CAP_RSVP_EVENTS
 *   stagekitwp_anns → Announcements      requires: CAP_RSVP_EVENTS
 *   stagekitwp_availability  → Availability       requires: CAP_RSVP_EVENTS
 *   stagekitwp_messages      → Messages           requires: CAP_MESSAGE_MEMBERS
 *   stagekitwp_directory     → Directory          requires: CAP_SEARCH_MEMBERS
 *   stagekitwp_dashboard     → Producer Dashboard requires: CAP_MANAGE_EVENTS
 */

if (!defined('ABSPATH')) exit;

class STAGEKITWP_MEMBERS_Nav_Menu {

    const META_KEY   = '_stagekitwp_members_type';
    const URL_PREFIX = '#stagekitwp_';

    public static function init() {
        add_action('admin_init',              [__CLASS__, 'register_nav_menu_meta_box']);
        add_action('wp_update_nav_menu_item', [__CLASS__, 'save_menu_item_meta'], 10, 3);
        add_filter('wp_nav_menu_objects',     [__CLASS__, 'filter_item_urls'],  5,  2);
        add_filter('wp_nav_menu_objects',     [__CLASS__, 'filter_menu_items'], 10, 2);
        add_filter('wp_setup_nav_menu_item',  [__CLASS__, 'setup_menu_item']);
        add_action('wp_enqueue_scripts',      [__CLASS__, 'enqueue']);
    }

    /* ------------------------------------------------------------------ */
    /*  Link-type definitions                                               */
    /* ------------------------------------------------------------------ */

    public static function link_types(): array {
        return [
            'stagekitwp_login'         => ['label' => __('Log In',             'stagekitwp-members-area'), 'visible' => [__CLASS__, 'visible_logged_out']],
            'stagekitwp_logout'        => ['label' => __('Log Out',            'stagekitwp-members-area'), 'visible' => [__CLASS__, 'visible_logged_in']],  // any logged-in user
            'stagekitwp_profile'       => ['label' => __('My Profile',         'stagekitwp-members-area'), 'visible' => [__CLASS__, 'visible_manage_profile']],
            'stagekitwp_events'        => ['label' => __('Events',             'stagekitwp-members-area'), 'visible' => [__CLASS__, 'visible_rsvp_events']],
            'stagekitwp_anns' => ['label' => __('Announcements',      'stagekitwp-members-area'), 'visible' => [__CLASS__, 'visible_rsvp_events']],
            'stagekitwp_availability'  => ['label' => __('Availability',       'stagekitwp-members-area'), 'visible' => [__CLASS__, 'visible_rsvp_events']],
            'stagekitwp_messages'      => ['label' => __('Messages',           'stagekitwp-members-area'), 'visible' => [__CLASS__, 'visible_message_members']],
            'stagekitwp_directory'     => ['label' => __('Directory',          'stagekitwp-members-area'), 'visible' => [__CLASS__, 'visible_search_members']],
            'stagekitwp_dashboard'     => ['label' => __('Producer Dashboard', 'stagekitwp-members-area'), 'visible' => [__CLASS__, 'visible_manage_events']],
        ];
    }

    public static function visible_logged_out():      bool { return !is_user_logged_in(); }
    public static function visible_logged_in():       bool { return  is_user_logged_in(); }
    public static function visible_manage_profile():  bool { return current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_PROFILE); }
    public static function visible_rsvp_events():     bool { return current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_RSVP_EVENTS); }
    public static function visible_message_members(): bool { return current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_MESSAGE_MEMBERS); }
    public static function visible_search_members():  bool { return current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_SEARCH_MEMBERS); }
    public static function visible_manage_events():   bool { return current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_EVENTS); }

    /* ------------------------------------------------------------------ */
    /*  URL resolution                                                      */
    /* ------------------------------------------------------------------ */

    private static function resolve_url(string $type): string {
        switch ($type) {
            case 'stagekitwp_login':         return class_exists('STAGEKITWP_MEMBERS_Auth') ? STAGEKITWP_MEMBERS_Auth::login_url()  : wp_login_url();
            case 'stagekitwp_logout':        return class_exists('STAGEKITWP_MEMBERS_Auth') ? STAGEKITWP_MEMBERS_Auth::logout_url() : wp_logout_url();
            case 'stagekitwp_profile':       return self::page_url('stagekitwp_members_page_profile',       'profile');
            case 'stagekitwp_events':        return self::page_url('stagekitwp_members_page_events',        'events');
            case 'stagekitwp_anns': return self::page_url('stagekitwp_members_page_announcements', 'announcements');
            case 'stagekitwp_availability':  return self::page_url('stagekitwp_members_page_availability',  'availability');
            case 'stagekitwp_messages':      return self::page_url('stagekitwp_members_page_messages',      'messages');
            case 'stagekitwp_directory':     return self::page_url('stagekitwp_members_page_directory',     'directory');
            case 'stagekitwp_dashboard':     return self::page_url('stagekitwp_members_page_dashboard',     'producer');
        }
        return '#';
    }

    /**
     * Resolve a page URL: Settings option first, then slug fallback.
     * This means items work even before Settings are configured.
     */
    private static function page_url(string $option, string $slug_fallback): string {
        $id = (int) get_option($option, 0);
        if ($id) {
            $url = get_permalink($id);
            if ($url) return (string) $url;
        }
        $page = get_page_by_path($slug_fallback);
        if ($page) return (string) get_permalink($page->ID);
        return '#';
    }

    /* ------------------------------------------------------------------ */
    /*  Appearance → Menus: meta box                                        */
    /* ------------------------------------------------------------------ */

    public static function register_nav_menu_meta_box() {
        add_meta_box(
            'stagekitwp-ma-nav-links',
			__('Members', 'stagekitwp-members-area'),
            [__CLASS__, 'render_meta_box'],
            'nav-menus',
            'side',
            'default'
        );
    }

    public static function render_meta_box(): void {
        $types = self::link_types();
        ?>
        <div id="stagekitwp-ma-nav-links-div">
            <div id="tabs-panel-stagekitwp-ma-links" class="tabs-panel tabs-panel-active">
                <ul id="stagekitwp-ma-checklist" class="categorychecklist form-no-clear">
                    <?php
                    $i = -1; // negative IDs = new (unsaved) items
                    foreach ($types as $type => $def):
                        $slug    = str_replace('_', '-', $type);
                        $placeholder = self::URL_PREFIX . substr($type, 3); // strip 'stagekitwp_'
                        ?>
                        <li>
                            <label class="menu-item-title">
                                <input type="checkbox" class="menu-item-checkbox"
                                       name="menu-item[<?php echo $i; ?>][menu-item-type]" value="custom">
                                <?php echo esc_html($def['label']); ?>
                            </label>
                            <input type="hidden" name="menu-item[<?php echo $i; ?>][menu-item-url]"
                                   value="<?php echo esc_attr($placeholder); ?>">
                            <input type="hidden" name="menu-item[<?php echo $i; ?>][menu-item-title]"
                                   value="<?php echo esc_attr($def['label']); ?>">
                            <input type="hidden" name="menu-item[<?php echo $i; ?>][menu-item-classes]"
                                   value="stagekitwp-ma-nav-item stagekitwp-ma-<?php echo esc_attr($slug); ?>">
                        </li>
                        <?php
                        $i--;
                    endforeach;
                    ?>
                </ul>
            </div>
            <p class="button-controls wp-clearfix">
                <span class="list-controls">
                    <a href="#" class="select-all"><?php esc_html_e('Select All', 'stagekitwp-members-area'); ?></a>
                </span>
                <span class="add-to-menu">
                    <input type="submit" class="button submit-add-to-menu right"
                           value="<?php esc_attr_e('Add to Menu', 'stagekitwp-members-area'); ?>"
                           name="add-stagekitwp-ma-menu-item"
                           id="submit-stagekitwp-ma-nav-links">
                    <span class="spinner"></span>
                </span>
            </p>
            <p class="description" style="margin-top:.5em;font-size:.8em;color:#666;">
                <?php esc_html_e('Items show or hide automatically based on the visitor\'s login status and role. URLs are resolved dynamically — no page configuration needed.', 'stagekitwp-members-area'); ?>
            </p>
        </div>
        <?php
    }

    /* ------------------------------------------------------------------ */
    /*  Save _stagekitwp_members_type meta on item create/update                         */
    /* ------------------------------------------------------------------ */

    public static function save_menu_item_meta(int $menu_id, int $item_id, array $args): void {
        $url = $args['menu-item-url'] ?? '';
        if (!str_starts_with($url, self::URL_PREFIX)) return;
        $type = 'stagekitwp_' . substr($url, strlen(self::URL_PREFIX));
        if (array_key_exists($type, self::link_types())) {
            update_post_meta($item_id, self::META_KEY, $type);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Filter: rewrite placeholder URLs → real URLs                        */
    /* ------------------------------------------------------------------ */

    public static function filter_item_urls(array $items, $args): array {
        $types = self::link_types();
        foreach ($items as $item) {
            $type = get_post_meta($item->ID, self::META_KEY, true);
            if (!$type && str_starts_with($item->url ?? '', self::URL_PREFIX)) {
                $type = 'stagekitwp_' . substr($item->url, strlen(self::URL_PREFIX));
            }
            if ($type && isset($types[$type])) {
                $item->url = self::resolve_url($type);
            }
        }
        return $items;
    }

    /* ------------------------------------------------------------------ */
    /*  Filter: hide items the current visitor cannot see                   */
    /* ------------------------------------------------------------------ */

    public static function filter_menu_items(array $items, $args): array {
        if (is_admin()) return $items;
        $types = self::link_types();
        foreach ($items as $k => $item) {
            $type = get_post_meta($item->ID, self::META_KEY, true);
            if (!$type && str_starts_with($item->url ?? '', self::URL_PREFIX)) {
                $type = 'stagekitwp_' . substr($item->url, strlen(self::URL_PREFIX));
            }
            if (!$type || !isset($types[$type])) continue;
            if (!call_user_func($types[$type]['visible'])) {
                unset($items[$k]);
            }
        }
        return array_values($items);
    }

    /* ------------------------------------------------------------------ */
    /*  Admin: label TM items clearly in the menu editor                    */
    /* ------------------------------------------------------------------ */

    public static function setup_menu_item(object $item): object {
        $type = get_post_meta($item->ID, self::META_KEY, true);
        if (!$type) return $item;
        $types = self::link_types();
        if (!isset($types[$type])) return $item;
        $item->type_label = __('Members', 'stagekitwp-members-area');
        if (str_starts_with($item->url ?? '', self::URL_PREFIX)) {
            $item->url = '#';
        }
        return $item;
    }

    /* ------------------------------------------------------------------ */
    /*  Enqueue nav CSS                                                      */
    /* ------------------------------------------------------------------ */

    public static function enqueue(): void {
        wp_enqueue_style('stagekitwp-ma-nav', STAGEKITWPMA_URL . 'assets/css/nav-menu.css', [], STAGEKITWPMA_VERSION);
    }
}
