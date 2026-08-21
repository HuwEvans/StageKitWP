<?php
/**
 * StageKitWP - Shows Beaver Builder Module
 *
 * @package StageKitWP
 * @subpackage Beaver Builder Modules
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FLBuilderModule' ) ) {
    return;
}

/**
 * Populate season dropdown (reuses shared helper when available).
 */
function stagekitwp_get_bb_seasons_for_shows() {
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

class STAGEKITWP_Shows_BB_Module extends FLBuilderModule {
    public function __construct() {
        parent::__construct( array(
            'name'            => __( 'Shows', 'stagekitwp-core' ),
            'description'     => __( 'Display shows with optional season filtering', 'stagekitwp-core' ),
            'group'           => 'stagekitwp-core',
            'category'        => 'stagekitwp-core',
            'dir'             => plugin_dir_path( __FILE__ ),
            'url'             => plugin_dir_url( __FILE__ ),
            'enabled'         => true,
            'partial_refresh' => true,
        ) );
    }
}

FLBuilder::register_module( 'STAGEKITWP_Shows_BB_Module', array(
    'data' => array(
        'title'    => __( 'Data', 'stagekitwp-core' ),
        'sections' => array(
            'filtering' => array(
                'title'  => __( 'Filtering', 'stagekitwp-core' ),
                'fields' => array(
                    'season_id' => array(
                        'type'    => 'select',
                        'label'   => __( 'Season', 'stagekitwp-core' ),
                        'options' => 'stagekitwp_get_bb_seasons_for_shows',
                        'help'    => __( 'Leave empty to show all seasons', 'stagekitwp-core' ),
                    ),
                    'which' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Which', 'stagekitwp-core' ),
                        'options' => array(
                            'all'              => __( 'All Shows', 'stagekitwp-core' ),
                            'current'          => __( 'Current Season Only', 'stagekitwp-core' ),
                            'next'             => __( 'Next Season Only', 'stagekitwp-core' ),
                            'current_and_next' => __( 'Current & Next', 'stagekitwp-core' ),
                        ),
                        'default' => 'all',
                    ),
                ),
            ),
            'visibility' => array(
                'title'  => __( 'Visibility', 'stagekitwp-core' ),
                'fields' => array(
                    'exclude' => array(
                        'type'        => 'text',
                        'label'       => __( 'Exclude Fields', 'stagekitwp-core' ),
                        'placeholder' => 'genre,director',
                        'help'        => __( 'Comma-separated field names to hide', 'stagekitwp-core' ),
                    ),
                ),
            ),
        ),
    ),
) );
