<?php
/**
 * StageKitWP - Awards Beaver Builder Module
 *
 * @package StageKitWP
 * @subpackage Beaver Builder Modules
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FLBuilderModule' ) ) {
    return;
}

/**
 * Populate season dropdown for the awards module.
 */
function stagekitwp_get_bb_seasons_for_awards() {
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

class STAGEKITWP_Awards_BB_Module extends FLBuilderModule {
    public function __construct() {
        parent::__construct( array(
            'name'            => __( 'Awards', 'stagekitwp-core' ),
            'description'     => __( 'Display awards, optionally filtered by season', 'stagekitwp-core' ),
            'group'           => 'stagekitwp-core',
            'category'        => 'stagekitwp-core',
            'dir'             => plugin_dir_path( __FILE__ ),
            'url'             => plugin_dir_url( __FILE__ ),
            'enabled'         => true,
            'partial_refresh' => true,
        ) );
    }
}

FLBuilder::register_module( 'STAGEKITWP_Awards_BB_Module', array(
    'display' => array(
        'title'    => __( 'Display', 'stagekitwp-core' ),
        'sections' => array(
            'layout' => array(
                'title'  => __( 'Layout', 'stagekitwp-core' ),
                'fields' => array(
                    'layout' => array(
                        'type'    => 'select',
                        'label'   => __( 'Layout Style', 'stagekitwp-core' ),
                        'default' => 'table',
                        'options' => array(
                            'table'    => __( 'Table — season/category grouped table', 'stagekitwp-core' ),
                            'cards'    => __( 'Cards — award cards in a grid', 'stagekitwp-core' ),
                            'list'     => __( 'List — compact single-line rows', 'stagekitwp-core' ),
                            'showcase' => __( 'Showcase — winners hero + nominations list', 'stagekitwp-core' ),
                        ),
                    ),
                    'show_season' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Season Heading', 'stagekitwp-core' ),
                        'default' => 'true',
                        'options' => array( 'true' => __( 'Yes', 'stagekitwp-core' ), 'false' => __( 'No', 'stagekitwp-core' ) ),
                    ),
                    'show_category' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Category Heading', 'stagekitwp-core' ),
                        'default' => 'true',
                        'options' => array( 'true' => __( 'Yes', 'stagekitwp-core' ), 'false' => __( 'No', 'stagekitwp-core' ) ),
                    ),
                    'winners_only' => array(
                        'type'    => 'select',
                        'label'   => __( 'Winners Only', 'stagekitwp-core' ),
                        'default' => 'false',
                        'options' => array( 'false' => __( 'No — show all', 'stagekitwp-core' ), 'true' => __( 'Yes — hide nominations', 'stagekitwp-core' ) ),
                    ),
                ),
            ),
            'filtering' => array(
                'title'  => __( 'Filtering', 'stagekitwp-core' ),
                'fields' => array(
                    'season_id' => array(
                        'type'    => 'select',
                        'label'   => __( 'Season', 'stagekitwp-core' ),
                        'options' => 'stagekitwp_get_bb_seasons_for_awards',
                        'help'    => __( 'Leave empty to show all seasons', 'stagekitwp-core' ),
                    ),
                    'category' => array(
                        'type'        => 'text',
                        'label'       => __( 'Category Filter', 'stagekitwp-core' ),
                        'placeholder' => 'Musical / Drama / Comedy',
                        'help'        => __( 'Leave empty to show all award categories', 'stagekitwp-core' ),
                    ),
                ),
            ),
        ),
    ),
) );
