<?php
/**
 * StageKitWP - Programs Beaver Builder Module
 *
 * @package StageKitWP
 * @subpackage Beaver Builder Modules
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FLBuilderModule' ) ) {
    return;
}

/**
 * Populate season dropdown for the programs module.
 */
function stagekitwp_get_bb_seasons_for_programs() {
    if ( function_exists( 'stagekitwp_get_bb_seasons' ) ) {
        return stagekitwp_get_bb_seasons();
    }
    $options = array( '' => __( '— All Seasons —', 'stagekitwp-core' ) );
    $seasons = get_posts( array( 'post_type' => 'season', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
    foreach ( $seasons as $s ) {
        $options[ $s->ID ] = esc_html( $s->post_title );
    }
    return $options;
}

class STAGEKITWP_Programs_BB_Module extends FLBuilderModule {
    public function __construct() {
        parent::__construct( array(
            'name'            => __( 'Program Downloads', 'stagekitwp-core' ),
            'description'     => __( 'Display downloadable show programs', 'stagekitwp-core' ),
            'group'           => 'stagekitwp-core',
            'category'        => 'stagekitwp-core',
            'dir'             => plugin_dir_path( __FILE__ ),
            'url'             => plugin_dir_url( __FILE__ ),
            'enabled'         => true,
            'partial_refresh' => true,
        ) );
    }
}

FLBuilder::register_module( 'STAGEKITWP_Programs_BB_Module', array(
    'display' => array(
        'title'    => __( 'Display Options', 'stagekitwp-core' ),
        'sections' => array(
            'layout' => array(
                'title'  => __( 'Layout', 'stagekitwp-core' ),
                'fields' => array(
                    'season' => array(
                        'type'    => 'select',
                        'label'   => __( 'Season', 'stagekitwp-core' ),
                        'options' => 'stagekitwp_get_bb_seasons_for_programs',
                        'help'    => __( 'Leave empty to show all programs', 'stagekitwp-core' ),
                    ),
                    'columns' => array(
                        'type'    => 'text',
                        'label'   => __( 'Columns', 'stagekitwp-core' ),
                        'default' => 3,
                    ),
                    'size' => array(
                        'type'    => 'select',
                        'label'   => __( 'Thumbnail Size', 'stagekitwp-core' ),
                        'options' => array(
                            'thumbnail' => __( 'Thumbnail', 'stagekitwp-core' ),
                            'medium'    => __( 'Medium', 'stagekitwp-core' ),
                            'large'     => __( 'Large', 'stagekitwp-core' ),
                        ),
                        'default' => 'medium',
                    ),
                ),
            ),
        ),
    ),
) );
