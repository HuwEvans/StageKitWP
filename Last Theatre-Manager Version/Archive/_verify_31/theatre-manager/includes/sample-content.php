<?php
/**
 * Functions for creating sample content pages
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create sample pages for each CPT with appropriate shortcodes
 */
function tm_create_sample_pages() {
    // Try to pick sensible real IDs where possible so generated sample pages work out of the box.
    $first_show = get_posts(array('post_type' => 'show', 'numberposts' => 1));
    $first_show_id = !empty($first_show) ? intval($first_show[0]->ID) : 0;
    $first_season = get_posts(array('post_type' => 'season', 'numberposts' => 1));
    $first_season_id = !empty($first_season) ? intval($first_season[0]->ID) : 0;
    $sample_pages = array(
        'TM_Shows' => array(
            'content' => '<!-- wp:paragraph -->
<p>Our upcoming and past theatre productions.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[tm_shows]
<!-- /wp:shortcode -->',
            'template' => 'default'
        ),
        'TM_Current_Season' => array(
            'content' => '<!-- wp:paragraph -->
<p>Welcome to our current season!</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[tm_season_banner season_id="current"]
<!-- /wp:shortcode -->

<!-- wp:shortcode -->
[tm_season_shows season_id="current"]
<!-- /wp:shortcode -->

<!-- wp:shortcode -->
[tm_season_cast season_id="current"]
<!-- /wp:shortcode -->',
            'template' => 'default'
        ),
        'TM_Cast_Members' => array(
            'content' => '<!-- wp:paragraph -->
<p>Meet our talented cast members.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[tm_cast]
<!-- /wp:shortcode -->',
            'template' => 'default'
        ),
        'TM_Board_Members' => array(
            'content' => '<!-- wp:paragraph -->
<p>Meet our dedicated board members.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[tm_board_members]
<!-- /wp:shortcode -->',
            'template' => 'default'
        ),
        'TM_Sponsors' => array(
            'content' => '<!-- wp:paragraph -->
<p>Thank you to our generous sponsors!</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[tm_sponsors]
<!-- /wp:shortcode -->

<!-- wp:heading {"level":3} -->
<h3>Featured Sponsors</h3>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[tm_sponsor_slider]
<!-- /wp:shortcode -->',
            'template' => 'default'
        ),
        'TM_Advertisers' => array(
            'content' => '<!-- wp:paragraph -->
<p>Support our advertisers who help make our productions possible.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[tm_advertisers]
<!-- /wp:shortcode -->',
            'template' => 'default'
        ),
        'TM_Contributors' => array(
            'content' => '<!-- wp:paragraph -->
<p>Thank you to all our contributors and donors.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[tm_contributors]
<!-- /wp:shortcode -->',
            'template' => 'default'
        ),
        'TM_Seasons' => array(
            'content' => '<!-- wp:paragraph -->
<p>Browse our seasons.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[tm_seasons count="6" layout="grid"]
<!-- /wp:shortcode -->',
            'template' => 'default'
        ),
        'TM_Cast_Grouped' => array(
            'content' => '<!-- wp:paragraph -->
<p>Cast grouped by show name.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[tm_cast group_by="show"]
<!-- /wp:shortcode -->',
            'template' => 'default'
        ),
        'TM_Programs' => array(
            'content' => '<!-- wp:paragraph -->
<p>View our show programs and playbills.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[tm_programs]
<!-- /wp:shortcode -->',
            'template' => 'default'
        ),
        'TM_Testimonials' => array(
            'content' => '<!-- wp:paragraph -->
<p>What people are saying about our productions.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[tm_testimonials]
<!-- /wp:shortcode -->',
            'template' => 'default'
        ),
        'TM_Show_Cast' => array(
            'content' => '<!-- wp:paragraph -->\n<p>Cast for a single show (uses first available Show ID' . ($first_show_id ? '' : ' — placeholder shown because no Show exists') . ').</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:shortcode -->\n' . '[tm_show_cast show_id="' . ($first_show_id ? $first_show_id : '123') . '"]' . '\n<!-- /wp:shortcode -->',
            'template' => 'default'
        ),
        'TM_Season_Images' => array(
            'content' => '<!-- wp:paragraph -->\n<p>Image gallery for a season (uses first available Season ID' . ($first_season_id ? '' : ' — placeholder shown because no Season exists') . ').</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:shortcode -->\n' . '[tm_season_images season_id="' . ($first_season_id ? $first_season_id : '456') . '" layout="grid"]' . '\n<!-- /wp:shortcode -->',
            'template' => 'default'
        ),
        'TM_Season_Shows' => array(
            'content' => '<!-- wp:paragraph -->
<p>Shows for current and next seasons.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[tm_season_shows which="current_and_next" layout="grid"]
<!-- /wp:shortcode -->',
            'template' => 'default'
        ),
    );

    // Create a parent page for theatre content
    $parent_page_id = wp_insert_post(array(
        'post_title' => 'TM_Theatre',
        'post_content' => '<!-- wp:paragraph -->
<p>Welcome to our theatre section. Explore our shows, meet our cast, and learn about our supporters.</p>
<!-- /wp:paragraph -->',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_name' => 'theatre'
    ));

    if (is_wp_error($parent_page_id)) {
        return false;
    }

    // Create each sample page
    foreach ($sample_pages as $title => $details) {
        // Check if page already exists
        $existing_page = get_page_by_title($title, OBJECT, 'page');
        if ($existing_page) {
            continue;
        }

        // Create the page
        $page_id = wp_insert_post(array(
            'post_title' => $title,
            'post_content' => $details['content'],
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_parent' => $parent_page_id,
            'page_template' => $details['template']
        ));

        if (is_wp_error($page_id)) {
            continue;
        }
    }

    // Create a menu item for the parent page
    $menu_name = 'primary';
    $locations = get_nav_menu_locations();
    
    if (isset($locations[$menu_name])) {
        $menu = wp_get_nav_menu_object($locations[$menu_name]);
        
            if ($menu) {
            wp_update_nav_menu_item($menu->term_id, 0, array(
                'menu-item-title' => 'TM_Theatre',
                'menu-item-object-id' => $parent_page_id,
                'menu-item-object' => 'page',
                'menu-item-status' => 'publish',
                'menu-item-type' => 'post_type',
                'menu-item-position' => -1
            ));
        }
    }

    return true;
}

/**
 * Add menu item to Tools menu
 */
function tm_add_sample_content_page() {
    add_management_page(
        'Theatre Manager Sample Content',
        'TM Sample Content',
        'manage_options',
        'tm-sample-content',
        'tm_render_sample_content_page'
    );
}
add_action('admin_menu', 'tm_add_sample_content_page');

/**
 * Render the sample content admin page
 */
function tm_render_sample_content_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $message = '';
    if (isset($_POST['tm_create_sample_pages']) && check_admin_referer('tm_create_sample_pages')) {
        if (tm_create_sample_pages()) {
            $message = '<div class="notice notice-success"><p>Sample pages have been created successfully!</p></div>';
        } else {
            $message = '<div class="notice notice-error"><p>There was an error creating the sample pages.</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php echo $message; ?>
        <div class="card">
            <h2>Create Sample Pages</h2>
            <p>This will create a set of sample pages for each custom post type in the Theatre Manager plugin. Each page will include appropriate shortcodes and basic content.</p>
            <p>The following pages will be created:</p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li>TM_Theatre (parent page)</li>
                <li>TM_Shows</li>
                <li>TM_Current_Season</li>
                <li>TM_Cast_Members</li>
                <li>TM_Board_Members</li>
                <li>TM_Sponsors</li>
                <li>TM_Advertisers</li>
                <li>TM_Contributors</li>
                <li>TM_Programs</li>
                <li>TM_Testimonials</li>
                <li>TM_Seasons</li>
                <li>TM_Cast_Grouped</li>
                <li>TM_Show_Cast</li>
                <li>TM_Season_Images</li>
                <li>TM_Season_Shows</li>
            </ul>
            <p><strong>Note on placeholder IDs:</strong> A few sample pages (for example <em>TM_Show_Cast</em> and <em>TM_Season_Images</em>) include placeholder IDs such as <code>show_id="123"</code> or <code>season_id="456"</code>. These are intentional — replace the placeholder ID with a real Show or Season post ID after the pages are created. To find a real ID, go to the Shows or Seasons list in the admin and hover over the item; the URL shown in your browser status bar will contain <code>post=ID</code>. Alternatively, edit the created page and paste a valid ID into the shortcode attribute.</p>
            <p><strong>Note:</strong> Existing pages with the same titles will not be overwritten.</p>
            <form method="post">
                <?php wp_nonce_field('tm_create_sample_pages'); ?>
                <p>
                    <input type="submit" name="tm_create_sample_pages" class="button button-primary" value="Create Sample Pages">
                </p>
            </form>
        </div>
    </div>
    <?php
}