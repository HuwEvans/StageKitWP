<?php
/**
 * StageKitWP - Venues Beaver Builder Module
 *
 * @package StageKitWP
 * @subpackage Beaver Builder Modules
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FLBuilderModule' ) ) {
    return;
}

/**
 * Populate show dropdown for the venues module.
 */
function stagekitwp_get_bb_shows_for_venues() {
    if ( function_exists( 'stagekitwp_get_bb_shows' ) ) {
        return stagekitwp_get_bb_shows();
    }
    $options = array( '' => __( '— All Shows —', 'stagekitwp-core' ) );
    $shows = get_posts( array( 'post_type' => 'show', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
    foreach ( $shows as $s ) {
        $options[ $s->ID ] = esc_html( $s->post_title );
    }
    return $options;
}

class STAGEKITWP_Venues_BB_Module extends FLBuilderModule {
    public function __construct() {
        parent::__construct( array(
            'name'            => __( 'Venues', 'stagekitwp-core' ),
            'description'     => __( 'Display venue details, optionally filtered by show', 'stagekitwp-core' ),
            'group'           => 'stagekitwp-core',
            'category'        => 'stagekitwp-core',
            'dir'             => plugin_dir_path( __FILE__ ),
            'url'             => plugin_dir_url( __FILE__ ),
            'enabled'         => true,
            'partial_refresh' => true,
        ) );
    }
}

FLBuilder::register_module( 'STAGEKITWP_Venues_BB_Module', array(
    'data' => array(
        'title'    => __( 'Data', 'stagekitwp-core' ),
        'sections' => array(
            'filtering' => array(
                'title'  => __( 'Filtering', 'stagekitwp-core' ),
                'fields' => array(
                    'show_id' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show', 'stagekitwp-core' ),
                        'options' => 'stagekitwp_get_bb_shows_for_venues',
                        'help'    => __( 'Leave empty to show all venues', 'stagekitwp-core' ),
                    ),
                ),
            ),
        ),
    ),
) );
