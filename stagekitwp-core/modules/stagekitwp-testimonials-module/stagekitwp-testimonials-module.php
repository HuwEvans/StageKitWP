<?php
/**
 * StageKitWP - Testimonials Beaver Builder Module
 *
 * @package StageKitWP
 * @subpackage Beaver Builder Modules
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FLBuilderModule' ) ) {
    return;
}

class STAGEKITWP_Testimonials_BB_Module extends FLBuilderModule {
    public function __construct() {
        parent::__construct( array(
            'name'            => __( 'Testimonials', 'stagekitwp-core' ),
            'description'     => __( 'Display testimonials in slider, grid, or full-page layouts', 'stagekitwp-core' ),
            'group'           => 'stagekitwp-core',
            'category'        => 'stagekitwp-core',
            'dir'             => plugin_dir_path( __FILE__ ),
            'url'             => plugin_dir_url( __FILE__ ),
            'enabled'         => true,
            'partial_refresh' => true,
        ) );
    }
}

FLBuilder::register_module( 'STAGEKITWP_Testimonials_BB_Module', array(
    'display' => array(
        'title'    => __( 'Display Options', 'stagekitwp-core' ),
        'sections' => array(
            'visibility' => array(
                'title'  => __( 'Visibility', 'stagekitwp-core' ),
                'fields' => array(
                    'show_name' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Name', 'stagekitwp-core' ),
                        'options' => array( 'true' => __( 'Yes', 'stagekitwp-core' ), 'false' => __( 'No', 'stagekitwp-core' ) ),
                        'default' => 'true',
                    ),
                    'show_comment' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Comment', 'stagekitwp-core' ),
                        'options' => array( 'true' => __( 'Yes', 'stagekitwp-core' ), 'false' => __( 'No', 'stagekitwp-core' ) ),
                        'default' => 'true',
                    ),
                    'show_rating' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Rating', 'stagekitwp-core' ),
                        'options' => array( 'true' => __( 'Yes', 'stagekitwp-core' ), 'false' => __( 'No', 'stagekitwp-core' ) ),
                        'default' => 'true',
                    ),
                    'show_show' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Associated Show', 'stagekitwp-core' ),
                        'options' => array( 'true' => __( 'Yes', 'stagekitwp-core' ), 'false' => __( 'No', 'stagekitwp-core' ) ),
                        'default' => 'true',
                    ),
                    'show_name_placement' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Name Placement', 'stagekitwp-core' ),
                        'options' => array(
                            'meta'         => __( 'Meta Line', 'stagekitwp-core' ),
                            'header'       => __( 'Header', 'stagekitwp-core' ),
                            'slug'         => __( 'Bullet Slug', 'stagekitwp-core' ),
                            'image_indent' => __( 'Indented Tag On Image', 'stagekitwp-core' ),
                        ),
                        'default' => 'meta',
                    ),
                    'tag_icon_source' => array(
                        'type'    => 'select',
                        'label'   => __( 'Tag Icon Source', 'stagekitwp-core' ),
                        'options' => array(
                            'none'      => __( 'Fallback Icon', 'stagekitwp-core' ),
                            'site_icon' => __( 'Site Icon', 'stagekitwp-core' ),
                            'miltonman' => __( 'Miltonman Image', 'stagekitwp-core' ),
                            'custom'    => __( 'Custom URL', 'stagekitwp-core' ),
                        ),
                        'default' => 'none',
                    ),
                    'tag_icon_url' => array(
                        'type'    => 'text',
                        'label'   => __( 'Custom Tag Icon URL', 'stagekitwp-core' ),
                    ),
                    'tag_icon_size' => array(
                        'type'    => 'unit',
                        'label'   => __( 'Tag Icon Size (px)', 'stagekitwp-core' ),
                        'default' => '18',
                    ),
                    'show_date' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Date', 'stagekitwp-core' ),
                        'options' => array( 'true' => __( 'Yes', 'stagekitwp-core' ), 'false' => __( 'No', 'stagekitwp-core' ) ),
                        'default' => 'true',
                    ),
                    'show_media' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Photo', 'stagekitwp-core' ),
                        'options' => array( 'true' => __( 'Yes', 'stagekitwp-core' ), 'false' => __( 'No', 'stagekitwp-core' ) ),
                        'default' => 'true',
                    ),
                ),
            ),
            'layout' => array(
                'title'  => __( 'Layout', 'stagekitwp-core' ),
                'fields' => array(
                    'mode' => array(
                        'type'    => 'select',
                        'label'   => __( 'Mode', 'stagekitwp-core' ),
                        'options' => array(
                            'slider'          => __( 'Slider', 'stagekitwp-core' ),
                            'grid'            => __( 'Grid', 'stagekitwp-core' ),
                            'full'            => __( 'Full Page List', 'stagekitwp-core' ),
                            'per_show'        => __( 'Per Show', 'stagekitwp-core' ),
                            'per_show_slider' => __( 'Per Show Slider', 'stagekitwp-core' ),
                        ),
                        'default' => 'slider',
                    ),
                    'reviews_per_show' => array(
                        'type'    => 'unit',
                        'label'   => __( 'Per Show: Reviews Per Show', 'stagekitwp-core' ),
                        'default' => '4',
                    ),
                    'review_align' => array(
                        'type'    => 'select',
                        'label'   => __( 'Per Show: Review Alignment', 'stagekitwp-core' ),
                        'options' => array(
                            'left'        => __( 'Left', 'stagekitwp-core' ),
                            'center'      => __( 'Center', 'stagekitwp-core' ),
                            'right'       => __( 'Right', 'stagekitwp-core' ),
                            'alternating' => __( 'Alternating (right, left, center)', 'stagekitwp-core' ),
                            'alternating_lr' => __( 'Alternating (left, right)', 'stagekitwp-core' ),
                        ),
                        'default' => 'left',
                    ),
                    'layout' => array(
                        'type'    => 'select',
                        'label'   => __( 'Card Layout', 'stagekitwp-core' ),
                        'options' => array(
                            'classic'   => __( 'Classic', 'stagekitwp-core' ),
                            'quote'     => __( 'Quote', 'stagekitwp-core' ),
                            'minimal'   => __( 'Minimal', 'stagekitwp-core' ),
                            'spotlight' => __( 'Spotlight', 'stagekitwp-core' ),
                            'overlay'   => __( 'Overlay', 'stagekitwp-core' ),
                        ),
                        'default' => 'classic',
                    ),
                    'columns' => array(
                        'type'    => 'unit',
                        'label'   => __( 'Grid Columns', 'stagekitwp-core' ),
                        'default' => '3',
                    ),
                    'limit' => array(
                        'type'    => 'unit',
                        'label'   => __( 'Limit', 'stagekitwp-core' ),
                        'default' => '-1',
                    ),
                    'image_width' => array(
                        'type'    => 'unit',
                        'label'   => __( 'Image Width (px)', 'stagekitwp-core' ),
                        'default' => '520',
                    ),
                    'image_height' => array(
                        'type'    => 'unit',
                        'label'   => __( 'Image Height (px)', 'stagekitwp-core' ),
                        'default' => '280',
                    ),
                    'image_fit' => array(
                        'type'    => 'select',
                        'label'   => __( 'Image Fit', 'stagekitwp-core' ),
                        'options' => array(
                            'cover'   => __( 'Cover', 'stagekitwp-core' ),
                            'contain' => __( 'Contain', 'stagekitwp-core' ),
                        ),
                        'default' => 'cover',
                    ),
                    'image_position' => array(
                        'type'    => 'select',
                        'label'   => __( 'Image Position', 'stagekitwp-core' ),
                        'options' => array(
                            'center' => __( 'Centered', 'stagekitwp-core' ),
                            'left'   => __( 'Left', 'stagekitwp-core' ),
                            'right'  => __( 'Right', 'stagekitwp-core' ),
                            'top'    => __( 'Top', 'stagekitwp-core' ),
                        ),
                        'default' => 'center',
                    ),
                    'image_focus' => array(
                        'type'    => 'select',
                        'label'   => __( 'Image Focus', 'stagekitwp-core' ),
                        'options' => array(
                            'center_center' => __( 'Center', 'stagekitwp-core' ),
                            'center_top'    => __( 'Top', 'stagekitwp-core' ),
                            'center_bottom' => __( 'Bottom', 'stagekitwp-core' ),
                            'left_center'   => __( 'Left', 'stagekitwp-core' ),
                            'right_center'  => __( 'Right', 'stagekitwp-core' ),
                        ),
                        'default' => 'center_center',
                    ),
                    'text_overlay' => array(
                        'type'    => 'select',
                        'label'   => __( 'Text Overlay On Image', 'stagekitwp-core' ),
                        'options' => array(
                            'false' => __( 'No', 'stagekitwp-core' ),
                            'true'  => __( 'Yes', 'stagekitwp-core' ),
                        ),
                        'default' => 'false',
                    ),
                    'image_opacity' => array(
                        'type'    => 'text',
                        'label'   => __( 'Image Opacity (0.1 - 1)', 'stagekitwp-core' ),
                        'default' => '0.45',
                    ),
                ),
            ),
        ),
    ),
) );
