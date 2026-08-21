<?php
/**
 * StageKitWP - Show Cast Beaver Builder Module
 *
 * Displays the cast list for a specific show — wraps [stagekitwp_show_cast].
 *
 * @package StageKitWP
 * @subpackage Beaver Builder Modules
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FLBuilderModule' ) ) {
    return;
}

/**
 * Populate show dropdown for the show cast module.
 */
function stagekitwp_get_bb_shows_for_show_cast() {
    if ( function_exists( 'stagekitwp_get_bb_shows' ) ) {
        return stagekitwp_get_bb_shows();
    }
    $options = array( '' => __( '— Select a Show —', 'stagekitwp-core' ) );
    $shows = get_posts( array( 'post_type' => 'show', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
    foreach ( $shows as $s ) {
        $options[ $s->ID ] = esc_html( $s->post_title );
    }
    return $options;
}

class STAGEKITWP_Show_Cast_BB_Module extends FLBuilderModule {
    public function __construct() {
        parent::__construct( array(
            'name'            => __( 'Show Cast', 'stagekitwp-core' ),
            'description'     => __( 'Display the cast list for a specific show', 'stagekitwp-core' ),
            'group'           => 'stagekitwp-core',
            'category'        => 'stagekitwp-core',
            'dir'             => plugin_dir_path( __FILE__ ),
            'url'             => plugin_dir_url( __FILE__ ),
            'enabled'         => true,
            'partial_refresh' => true,
        ) );
    }
}

FLBuilder::register_module( 'STAGEKITWP_Show_Cast_BB_Module', array(
    'data' => array(
        'title'    => __( 'Data', 'stagekitwp-core' ),
        'sections' => array(
            'show' => array(
                'title'  => __( 'Show', 'stagekitwp-core' ),
                'fields' => array(
                    'show_id' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show', 'stagekitwp-core' ),
                        'options' => 'stagekitwp_get_bb_shows_for_show_cast',
                        'help'    => __( 'Required — select a show to display its cast', 'stagekitwp-core' ),
                    ),
                ),
            ),
        ),
    ),
) );
