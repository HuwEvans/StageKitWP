<?php
/**
 * StageKitWP - Tickets Beaver Builder Module
 *
 * @package StageKitWP
 * @subpackage Beaver Builder Modules
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FLBuilderModule' ) ) {
    return;
}

class STAGEKITWP_Tickets_BB_Module extends FLBuilderModule {
    public function __construct() {
        parent::__construct( array(
            'name'            => __( 'Ticket Info', 'stagekitwp-core' ),
            'description'     => __( 'Display ticket purchase buttons for the current season', 'stagekitwp-core' ),
            'group'           => 'stagekitwp-core',
            'category'        => 'stagekitwp-core',
            'dir'             => plugin_dir_path( __FILE__ ),
            'url'             => plugin_dir_url( __FILE__ ),
            'enabled'         => true,
            'partial_refresh' => true,
        ) );
    }
}

FLBuilder::register_module( 'STAGEKITWP_Tickets_BB_Module', array(
    'display' => array(
        'title'    => __( 'Display Options', 'stagekitwp-core' ),
        'sections' => array(
            'layout' => array(
                'title'  => __( 'Layout', 'stagekitwp-core' ),
                'fields' => array(
                    'layout' => array(
                        'type'    => 'select',
                        'label'   => __( 'Layout Style', 'stagekitwp-core' ),
                        'options' => array(
                            'banner'    => __( 'Banner — stacked full-width buttons', 'stagekitwp-core' ),
                            'cards'     => __( 'Cards — poster art + CTA button', 'stagekitwp-core' ),
                            'table'     => __( 'Table — horizontal rows with links', 'stagekitwp-core' ),
                            'minimal'   => __( 'Minimal — text list with arrow', 'stagekitwp-core' ),
                            'spotlight' => __( 'Spotlight — hero image + show tiles', 'stagekitwp-core' ),
                        ),
                        'default' => 'banner',
                    ),
                    'show_limit' => array(
                        'type'    => 'text',
                        'label'   => __( 'Max Shows', 'stagekitwp-core' ),
                        'default' => 4,
                        'help'    => __( 'Maximum number of shows to display', 'stagekitwp-core' ),
                    ),
                ),
            ),
            'visibility' => array(
                'title'  => __( 'Visibility', 'stagekitwp-core' ),
                'fields' => array(
                    'show_image' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Images', 'stagekitwp-core' ),
                        'options' => array( 'true' => __( 'Yes', 'stagekitwp-core' ), 'false' => __( 'No', 'stagekitwp-core' ) ),
                        'default' => 'true',
                        'help'    => __( 'Cards and Spotlight layouts only', 'stagekitwp-core' ),
                    ),
                    'show_dates' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Dates', 'stagekitwp-core' ),
                        'options' => array( 'true' => __( 'Yes', 'stagekitwp-core' ), 'false' => __( 'No', 'stagekitwp-core' ) ),
                        'default' => 'true',
                    ),
                    'show_genre' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Genre', 'stagekitwp-core' ),
                        'options' => array( 'true' => __( 'Yes', 'stagekitwp-core' ), 'false' => __( 'No', 'stagekitwp-core' ) ),
                        'default' => 'false',
                    ),
                ),
            ),
            'labels' => array(
                'title'  => __( 'Labels', 'stagekitwp-core' ),
                'fields' => array(
                    'label_season' => array(
                        'type'        => 'text',
                        'label'       => __( 'Season Label', 'stagekitwp-core' ),
                        'placeholder' => __( 'Season Tickets', 'stagekitwp-core' ),
                    ),
                    'label_show' => array(
                        'type'        => 'text',
                        'label'       => __( 'Show Label', 'stagekitwp-core' ),
                        'placeholder' => __( 'Show Tickets', 'stagekitwp-core' ),
                    ),
                    'button_text' => array(
                        'type'        => 'text',
                        'label'       => __( 'Button / Link Text', 'stagekitwp-core' ),
                        'placeholder' => __( 'Get Tickets', 'stagekitwp-core' ),
                    ),
                ),
            ),
        ),
    ),
) );
