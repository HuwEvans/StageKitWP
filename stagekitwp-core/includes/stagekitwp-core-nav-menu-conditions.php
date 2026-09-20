<?php
/**
 * Adds a "Display" condition to the WordPress Menu editor (Appearance > Menus)
 * so front-end menu items can be shown conditionally based on season data:
 *   - Show always (default)
 *   - Show if there is a next season
 *   - Show if there are shows in the next season
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'STAGEKITWP_MENU_VISIBILITY_META', '_stagekitwp_menu_visibility' );

/**
 * Allowed visibility option values and their menu-editor labels.
 *
 * @return array<string,string>
 */
function stagekitwp_menu_visibility_options() {
    return array(
        'always'             => 'Show always (default)',
        'next_season'        => 'Show if there is a next season',
        'next_season_shows'  => 'Show if there are shows in the next season',
    );
}

/**
 * Evaluate a stored visibility option against current season data.
 *
 * @param string $visibility
 * @return bool
 */
function stagekitwp_menu_item_visibility_passes( $visibility ) {
    switch ( $visibility ) {
        case 'next_season':
            return (bool) stagekitwp_get_next_season();
        case 'next_season_shows':
            return stagekitwp_next_season_has_shows();
        default:
            return true;
    }
}

/**
 * Render the visibility control on each menu item in the admin menu editor.
 *
 * @param int     $item_id
 * @param WP_Post $item
 */
function stagekitwp_menu_item_visibility_field( $item_id, $item ) {
    $current = get_post_meta( $item_id, STAGEKITWP_MENU_VISIBILITY_META, true );
    if ( ! $current ) {
        $current = 'always';
    }
    ?>
    <p class="field-stagekitwp-visibility description description-wide">
        <label for="edit-menu-item-stagekitwp-visibility-<?php echo esc_attr( $item_id ); ?>">
            <?php esc_html_e( 'Display', 'stagekitwp-core' ); ?><br />
            <select id="edit-menu-item-stagekitwp-visibility-<?php echo esc_attr( $item_id ); ?>"
                name="menu-item-stagekitwp-visibility[<?php echo esc_attr( $item_id ); ?>]"
                class="widefat edit-menu-item-stagekitwp-visibility">
                <?php foreach ( stagekitwp_menu_visibility_options() as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </p>
    <?php
}
add_action( 'wp_nav_menu_item_custom_fields', 'stagekitwp_menu_item_visibility_field', 10, 2 );

/**
 * Save the visibility choice when a menu item is saved.
 *
 * @param int $menu_id
 * @param int $menu_item_db_id
 */
function stagekitwp_save_menu_item_visibility( $menu_id, $menu_item_db_id ) {
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        return;
    }
    if ( ! isset( $_POST['menu-item-stagekitwp-visibility'][ $menu_item_db_id ] ) ) {
        return;
    }
    $value   = sanitize_key( wp_unslash( $_POST['menu-item-stagekitwp-visibility'][ $menu_item_db_id ] ) );
    $allowed = array_keys( stagekitwp_menu_visibility_options() );
    if ( ! in_array( $value, $allowed, true ) ) {
        $value = 'always';
    }
    if ( 'always' === $value ) {
        delete_post_meta( $menu_item_db_id, STAGEKITWP_MENU_VISIBILITY_META );
    } else {
        update_post_meta( $menu_item_db_id, STAGEKITWP_MENU_VISIBILITY_META, $value );
    }
}
add_action( 'wp_update_nav_menu_item', 'stagekitwp_save_menu_item_visibility', 10, 2 );

/**
 * Filter the rendered front-end menu, removing items (and their children)
 * whose visibility condition doesn't currently pass.
 *
 * @param array $items
 * @return array
 */
function stagekitwp_filter_nav_menu_items( $items ) {
    $hidden_ids = array();
    foreach ( $items as $item ) {
        $visibility = get_post_meta( $item->ID, STAGEKITWP_MENU_VISIBILITY_META, true );
        if ( $visibility && ! stagekitwp_menu_item_visibility_passes( $visibility ) ) {
            $hidden_ids[] = $item->ID;
        }
    }

    if ( empty( $hidden_ids ) ) {
        return $items;
    }

    // Cascade: children of a hidden item are hidden too.
    $changed = true;
    while ( $changed ) {
        $changed = false;
        foreach ( $items as $item ) {
            if ( in_array( (int) $item->menu_item_parent, $hidden_ids, true ) && ! in_array( $item->ID, $hidden_ids, true ) ) {
                $hidden_ids[] = $item->ID;
                $changed      = true;
            }
        }
    }

    return array_values( array_filter( $items, function ( $item ) use ( $hidden_ids ) {
        return ! in_array( $item->ID, $hidden_ids, true );
    } ) );
}
add_filter( 'wp_nav_menu_objects', 'stagekitwp_filter_nav_menu_items' );
