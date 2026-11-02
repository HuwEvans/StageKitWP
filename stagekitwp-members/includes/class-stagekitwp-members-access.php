<?php
/**
 * Access control: "Who can see this?" for posts, pages, CPTs and menu items.
 *
 * Rules are stored as an array in post meta `_stagekitwp_members_access`:
 *   [ 'type' => everyone|logged_in|logged_out|members|level|roles,
 *     'roles' => [slug,...], 'min_level' => role slug, 'behavior' => ''|login|message|404 ]
 * Every decision goes through stagekitwp_members_user_can_access().
 */

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Access {

    const META_KEY  = '_stagekitwp_members_access';
    const OPT_TYPES = 'stagekitwp_members_access_post_types';
    const OPT_BEHAV = 'stagekitwp_members_access_default_behavior';
    const OPT_MSG   = 'stagekitwp_members_access_message';

    private static $excluded_cache = [];
    private static $in_query       = false;

    public static function init() {
        add_action('add_meta_boxes',        [__CLASS__, 'add_meta_boxes']);
        add_action('save_post',             [__CLASS__, 'save_post_meta'], 10, 2);
        add_action('template_redirect',     [__CLASS__, 'enforce'], 1);
        add_action('pre_get_posts',         [__CLASS__, 'exclude_from_queries']);
        add_filter('wp_sitemaps_posts_query_args', [__CLASS__, 'sitemap_args'], 10, 2);
        add_filter('rest_post_dispatch',    [__CLASS__, 'filter_rest'], 10, 3);
        add_shortcode('stagekitwp_members_only', [__CLASS__, 'shortcode']);

        // Menu items.
        add_action('wp_nav_menu_item_custom_fields', [__CLASS__, 'menu_item_fields'], 10, 2);
        add_action('wp_update_nav_menu_item',        [__CLASS__, 'save_menu_item'], 10, 2);
        add_filter('wp_nav_menu_objects',            [__CLASS__, 'filter_menu_items'], 9);

        // Admin list column.
        add_action('admin_init', [__CLASS__, 'register_columns']);
    }

    /* ------------------------------------------------------------------ */
    /*  Settings helpers                                                   */
    /* ------------------------------------------------------------------ */

    public static function protected_types(): array {
        $types = get_option(self::OPT_TYPES, ['page', 'post']);
        return is_array($types) ? array_values(array_filter(array_map('sanitize_key', $types))) : ['page', 'post'];
    }

    public static function default_behavior(): string {
        $b = (string) get_option(self::OPT_BEHAV, 'login');
        return in_array($b, ['login', 'message', '404'], true) ? $b : 'login';
    }

    public static function block_message(): string {
        $m = (string) get_option(self::OPT_MSG, '');
        return $m !== '' ? $m : __('This content is available to members only.', 'stagekitwp-members-area');
    }

    /* ------------------------------------------------------------------ */
    /*  Rule storage                                                       */
    /* ------------------------------------------------------------------ */

    public static function normalize_rule($raw): array {
        $raw   = is_array($raw) ? $raw : [];
        $valid = ['everyone', 'logged_in', 'logged_out', 'members', 'paid', 'level', 'roles'];
        $type  = isset($raw['type']) && in_array($raw['type'], $valid, true) ? $raw['type'] : 'everyone';
        $roles = isset($raw['roles']) ? array_values(array_filter(array_map('sanitize_key', (array) $raw['roles']))) : [];
        $levels = stagekitwp_members_levels();
        $min   = isset($raw['min_level']) && isset($levels[$raw['min_level']]) ? $raw['min_level'] : 'stagekitwp_member';
        $beh   = isset($raw['behavior']) && in_array($raw['behavior'], ['login', 'message', '404'], true) ? $raw['behavior'] : '';
        return ['type' => $type, 'roles' => $roles, 'min_level' => $min, 'behavior' => $beh];
    }

    public static function get_rule(int $post_id): array {
        return self::normalize_rule(get_post_meta($post_id, self::META_KEY, true));
    }

    private static function save_rule(int $post_id, $raw): void {
        $rule = self::normalize_rule($raw);
        if ($rule['type'] === 'everyone') {
            delete_post_meta($post_id, self::META_KEY);
        } else {
            update_post_meta($post_id, self::META_KEY, $rule);
        }
    }

    public static function user_can_view_post(int $post_id, int $user_id = 0): bool {
        $rule = self::get_rule($post_id);
        if ($rule['type'] === 'everyone') {
            return true;
        }
        if (current_user_can('edit_post', $post_id) && (!$user_id || $user_id === get_current_user_id())) {
            return true;
        }
        return stagekitwp_members_user_can_access($rule, $user_id, ['post_id' => $post_id]);
    }

    /* ------------------------------------------------------------------ */
    /*  Rule editor (shared by meta box and menu items)                    */
    /* ------------------------------------------------------------------ */

    private static function render_fields(string $name, array $rule, bool $behavior = true): void {
        $types = [
            'everyone'   => __('Everyone', 'stagekitwp-members-area'),
            'logged_in'  => __('Any logged-in user', 'stagekitwp-members-area'),
            'logged_out' => __('Logged-out visitors only', 'stagekitwp-members-area'),
            'members'    => __('Any member (member, producer, executive)', 'stagekitwp-members-area'),
            'paid'       => __('Paid / active membership', 'stagekitwp-members-area'),
            'level'      => __('Minimum member level…', 'stagekitwp-members-area'),
            'roles'      => __('Specific roles…', 'stagekitwp-members-area'),
        ];
        echo '<p><select name="' . esc_attr($name) . '[type]" class="widefat">';
        foreach ($types as $k => $label) {
            printf('<option value="%s"%s>%s</option>', esc_attr($k), selected($rule['type'], $k, false), esc_html($label));
        }
        echo '</select></p>';

        echo '<p><label>' . esc_html__('Minimum level', 'stagekitwp-members-area') . '<br><select name="' . esc_attr($name) . '[min_level]" class="widefat">';
        foreach (array_keys(stagekitwp_members_levels()) as $slug) {
            printf('<option value="%s"%s>%s</option>', esc_attr($slug), selected($rule['min_level'], $slug, false), esc_html(ucwords(str_replace(['stagekitwp_', '_'], ['', ' '], $slug))));
        }
        echo '</select></label></p>';

        echo '<p>' . esc_html__('Roles (used for "Specific roles")', 'stagekitwp-members-area') . '<br>';
        foreach (wp_roles()->get_names() as $slug => $label) {
            printf(
                '<label style="display:block"><input type="checkbox" name="%s[roles][]" value="%s"%s> %s</label>',
                esc_attr($name), esc_attr($slug), checked(in_array($slug, $rule['roles'], true), true, false), esc_html(translate_user_role($label))
            );
        }
        echo '</p>';

        if ($behavior) {
            echo '<p><label>' . esc_html__('When blocked', 'stagekitwp-members-area') . '<br><select name="' . esc_attr($name) . '[behavior]" class="widefat">';
            $opts = [
                ''        => __('Use site default', 'stagekitwp-members-area'),
                'login'   => __('Redirect to login', 'stagekitwp-members-area'),
                'message' => __('Show a message', 'stagekitwp-members-area'),
                '404'     => __('Show 404', 'stagekitwp-members-area'),
            ];
            foreach ($opts as $k => $label) {
                printf('<option value="%s"%s>%s</option>', esc_attr($k), selected($rule['behavior'], $k, false), esc_html($label));
            }
            echo '</select></label></p>';
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Meta box                                                           */
    /* ------------------------------------------------------------------ */

    public static function add_meta_boxes(): void {
        foreach (self::protected_types() as $type) {
            if (post_type_exists($type)) {
                add_meta_box('stagekitwp_members_access', __('Who can see this?', 'stagekitwp-members-area'), [__CLASS__, 'render_meta_box'], $type, 'side', 'default');
            }
        }
    }

    public static function render_meta_box($post): void {
        wp_nonce_field('stagekitwp_members_access_save', 'stagekitwp_members_access_nonce');
        self::render_fields('stagekitwp_members_access', self::get_rule((int) $post->ID));
    }

    public static function save_post_meta(int $post_id, $post): void {
        if (!isset($_POST['stagekitwp_members_access_nonce'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stagekitwp_members_access_nonce'])), 'stagekitwp_members_access_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id) || !in_array($post->post_type, self::protected_types(), true)) {
            return;
        }
        $raw = isset($_POST['stagekitwp_members_access']) ? wp_unslash($_POST['stagekitwp_members_access']) : [];
        self::save_rule($post_id, $raw);
    }

    /* ------------------------------------------------------------------ */
    /*  Menu items                                                         */
    /* ------------------------------------------------------------------ */

    public static function menu_item_fields($item_id, $item): void {
        $item_id = (int) $item_id;
        echo '<div class="field-stagekitwp-access description-wide" style="margin:8px 0;"><strong>' . esc_html__('Who can see this?', 'stagekitwp-members-area') . '</strong>';
        wp_nonce_field('stagekitwp_members_menu_access', 'stagekitwp_members_menu_nonce_' . $item_id);
        self::render_fields('stagekitwp_members_menu_access[' . $item_id . ']', self::get_rule($item_id), false);
        echo '</div>';
    }

    public static function save_menu_item($menu_id, $item_id): void {
        $item_id = (int) $item_id;
        $nonce   = 'stagekitwp_members_menu_nonce_' . $item_id;
        if (!isset($_POST[$nonce]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$nonce])), 'stagekitwp_members_menu_access')) {
            return;
        }
        if (!current_user_can('edit_theme_options')) {
            return;
        }
        $all = isset($_POST['stagekitwp_members_menu_access']) ? wp_unslash($_POST['stagekitwp_members_menu_access']) : [];
        self::save_rule($item_id, isset($all[$item_id]) ? $all[$item_id] : []);
    }

    public static function filter_menu_items(array $items): array {
        if (is_admin()) {
            return $items;
        }
        $hidden = [];
        foreach ($items as $k => $item) {
            $rule = self::get_rule((int) $item->ID);
            $parent_hidden = $item->menu_item_parent && isset($hidden[(int) $item->menu_item_parent]);
            if ($parent_hidden || ($rule['type'] !== 'everyone' && !stagekitwp_members_user_can_access($rule, 0, ['menu_item' => (int) $item->ID]))) {
                $hidden[(int) $item->ID] = true;
                unset($items[$k]);
            }
        }
        return array_values($items);
    }

    /* ------------------------------------------------------------------ */
    /*  Enforcement                                                        */
    /* ------------------------------------------------------------------ */

    public static function enforce(): void {
        if (!is_singular()) {
            return;
        }
        $post = get_queried_object();
        if (!$post instanceof WP_Post || !in_array($post->post_type, self::protected_types(), true)) {
            return;
        }
        if (self::user_can_view_post((int) $post->ID)) {
            return;
        }

        $rule     = self::get_rule((int) $post->ID);
        $behavior = $rule['behavior'] !== '' ? $rule['behavior'] : self::default_behavior();

        if ($behavior === 'login' && !is_user_logged_in()) {
            $login = (int) get_option('stagekitwp_members_login_page_id', 0);
            $base  = $login && get_option('stagekitwp_members_custom_login_enabled') ? get_permalink($login) : wp_login_url();
            wp_safe_redirect(add_query_arg('redirect_to', rawurlencode(get_permalink($post)), $base));
            exit;
        }

        if ($behavior === '404') {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
            return;
        }

        status_header(403);
        nocache_headers();
        $msg = self::block_message();
        add_filter('the_content', static function () use ($msg) {
            return '<div class="stagekitwp-members-restricted">' . wp_kses_post(wpautop($msg)) . '</div>';
        }, 9999);
        add_filter('get_the_excerpt', '__return_empty_string', 9999);
        add_filter('comments_open', '__return_false', 9999);
    }

    /** IDs of posts the current user may not see. */
    public static function excluded_ids(array $types = []): array {
        $types = $types ? array_values(array_intersect($types, self::protected_types())) : self::protected_types();
        if (!$types || self::$in_query) {
            return [];
        }
        $key = get_current_user_id() . '|' . implode(',', $types);
        if (isset(self::$excluded_cache[$key])) {
            return self::$excluded_cache[$key];
        }

        self::$in_query = true;
        $ids = get_posts([
            'post_type'        => $types,
            'post_status'      => 'any',
            'posts_per_page'   => -1,
            'fields'           => 'ids',
            'meta_key'         => self::META_KEY,
            'meta_compare'     => 'EXISTS',
            'suppress_filters' => true,
            'no_found_rows'    => true,
        ]);
        self::$in_query = false;

        $blocked = [];
        foreach ($ids as $id) {
            if (!self::user_can_view_post((int) $id)) {
                $blocked[] = (int) $id;
            }
        }
        return self::$excluded_cache[$key] = $blocked;
    }

    public static function exclude_from_queries($q): void {
        if (is_admin() && !wp_doing_ajax() && !(defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }
        if (self::$in_query || ($q->is_main_query() && $q->is_singular())) {
            return;
        }
        if ($q->get('post_type') === 'nav_menu_item' || $q->get('p') || $q->get('page_id') || $q->get('name') || $q->get('pagename')) {
            return;
        }
        $pt    = $q->get('post_type');
        $types = $pt && $pt !== 'any' ? (array) $pt : [];
        if (!$types) {
            $types = $q->is_search() || $q->is_main_query() ? self::protected_types() : ['post'];
        }
        $ids = self::excluded_ids($types);
        if ($ids) {
            $q->set('post__not_in', array_unique(array_merge((array) $q->get('post__not_in'), $ids)));
        }
    }

    public static function sitemap_args(array $args, string $post_type): array {
        $ids = self::excluded_ids([$post_type]);
        if ($ids) {
            $args['post__not_in'] = array_unique(array_merge(isset($args['post__not_in']) ? (array) $args['post__not_in'] : [], $ids));
        }
        return $args;
    }

    /** Block single restricted objects in the REST API. */
    public static function filter_rest($result, $server, $request) {
        if (!($result instanceof WP_REST_Response) || $result->is_error()) {
            return $result;
        }
        $data = $result->get_data();
        if (!is_array($data)) {
            return $result;
        }
        $types   = self::protected_types();
        $blocked = false;
        $check   = static function ($row) use ($types) {
            return is_array($row) && isset($row['id'], $row['type']) && in_array($row['type'], $types, true)
                && !self::user_can_view_post((int) $row['id']);
        };
        if ($check($data)) {
            return new WP_REST_Response(['code' => 'rest_forbidden', 'message' => __('Sorry, you are not allowed to view this.', 'stagekitwp-members-area'), 'data' => ['status' => 403]], 403);
        }
        if (isset($data[0]) && is_array($data[0])) {
            $filtered = array_values(array_filter($data, static function ($row) use ($check) { return !$check($row); }));
            if (count($filtered) !== count($data)) {
                $result->set_data($filtered);
            }
        }
        return $result;
    }

    /* ------------------------------------------------------------------ */
    /*  Shortcode                                                          */
    /* ------------------------------------------------------------------ */

    public static function shortcode($atts, $content = ''): string {
        $a = shortcode_atts(['rule' => '', 'roles' => '', 'level' => '', 'message' => ''], $atts, 'stagekitwp_members_only');
        if ($a['roles'] !== '') {
            $rule = ['type' => 'roles', 'roles' => array_map('trim', explode(',', $a['roles']))];
        } elseif ($a['level'] !== '') {
            $slug = strpos($a['level'], 'stagekitwp_') === 0 ? $a['level'] : 'stagekitwp_' . $a['level'];
            $rule = ['type' => 'level', 'min_level' => $slug];
        } elseif (in_array($a['rule'], ['everyone', 'logged_in', 'logged_out', 'members'], true)) {
            $rule = $a['rule'];
        } else {
            $rule = 'members';
        }
        if (stagekitwp_members_user_can_access($rule, 0, ['shortcode' => true])) {
            return do_shortcode($content);
        }
        return $a['message'] !== '' ? '<div class="stagekitwp-members-restricted">' . wp_kses_post($a['message']) . '</div>' : '';
    }

    /* ------------------------------------------------------------------ */
    /*  Admin list column                                                  */
    /* ------------------------------------------------------------------ */

    public static function register_columns(): void {
        foreach (self::protected_types() as $type) {
            add_filter("manage_{$type}_posts_columns", static function ($cols) {
                $cols['stagekitwp_access'] = __('Access', 'stagekitwp-members-area');
                return $cols;
            });
            add_action("manage_{$type}_posts_custom_column", static function ($col, $post_id) {
                if ($col !== 'stagekitwp_access') {
                    return;
                }
                $rule = self::get_rule((int) $post_id);
                echo $rule['type'] === 'everyone' ? '&mdash;' : esc_html(ucwords(str_replace('_', ' ', $rule['type'])));
            }, 10, 2);
        }
    }
}
