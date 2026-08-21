<?php
defined( 'ABSPATH' ) || exit;

$show_name    = isset( $settings->show_name ) ? $settings->show_name : 'true';
$show_comment = isset( $settings->show_comment ) ? $settings->show_comment : 'true';
$show_rating  = isset( $settings->show_rating ) ? $settings->show_rating : 'true';
$show_show    = isset( $settings->show_show ) ? $settings->show_show : 'true';
$show_name_placement = isset( $settings->show_name_placement ) ? $settings->show_name_placement : 'meta';
$tag_icon_source = isset( $settings->tag_icon_source ) ? $settings->tag_icon_source : 'none';
$tag_icon_url = isset( $settings->tag_icon_url ) ? $settings->tag_icon_url : '';
$tag_icon_size = isset( $settings->tag_icon_size ) ? $settings->tag_icon_size : '18';
$show_date    = isset( $settings->show_date ) ? $settings->show_date : 'true';
$show_media   = isset( $settings->show_media ) ? $settings->show_media : 'true';
$mode         = isset( $settings->mode ) ? $settings->mode : 'slider';
$layout       = isset( $settings->layout ) ? $settings->layout : 'classic';
$columns      = isset( $settings->columns ) ? $settings->columns : '3';
$limit        = isset( $settings->limit ) ? $settings->limit : '-1';
$image_width  = isset( $settings->image_width ) ? $settings->image_width : '520';
$image_height = isset( $settings->image_height ) ? $settings->image_height : '280';
$image_fit    = isset( $settings->image_fit ) ? $settings->image_fit : 'cover';
$image_position = isset( $settings->image_position ) ? $settings->image_position : 'center';
$image_focus = isset( $settings->image_focus ) ? $settings->image_focus : 'center_center';
$text_overlay = isset( $settings->text_overlay ) ? $settings->text_overlay : 'false';
$image_opacity = isset( $settings->image_opacity ) ? $settings->image_opacity : '0.45';
$reviews_per_show = isset( $settings->reviews_per_show ) ? $settings->reviews_per_show : '4';
$review_align = isset( $settings->review_align ) ? $settings->review_align : 'left';

$atts = array();
if ( 'true' !== $show_name )    { $atts[] = 'show_name="false"'; }
if ( 'true' !== $show_comment ) { $atts[] = 'show_comment="false"'; }
if ( 'true' !== $show_rating )  { $atts[] = 'show_rating="false"'; }
if ( 'true' !== $show_show )    { $atts[] = 'show_show="false"'; }
if ( ! empty( $show_name_placement ) ) { $atts[] = 'show_name_placement="' . esc_attr( $show_name_placement ) . '"'; }
if ( ! empty( $tag_icon_source ) )     { $atts[] = 'tag_icon_source="' . esc_attr( $tag_icon_source ) . '"'; }
if ( ! empty( $tag_icon_url ) )        { $atts[] = 'tag_icon_url="' . esc_attr( $tag_icon_url ) . '"'; }
if ( '' !== (string) $tag_icon_size )  { $atts[] = 'tag_icon_size="' . esc_attr( $tag_icon_size ) . '"'; }
if ( 'true' !== $show_date )    { $atts[] = 'show_date="false"'; }
if ( 'true' !== $show_media )   { $atts[] = 'show_media="false"'; }
if ( ! empty( $mode ) )         { $atts[] = 'mode="' . esc_attr( $mode ) . '"'; }
if ( ! empty( $layout ) )       { $atts[] = 'layout="' . esc_attr( $layout ) . '"'; }
if ( '' !== (string) $columns ) { $atts[] = 'columns="' . esc_attr( $columns ) . '"'; }
if ( '' !== (string) $limit )   { $atts[] = 'limit="' . esc_attr( $limit ) . '"'; }
if ( '' !== (string) $image_width )  { $atts[] = 'image_width="' . esc_attr( $image_width ) . '"'; }
if ( '' !== (string) $image_height ) { $atts[] = 'image_height="' . esc_attr( $image_height ) . '"'; }
if ( ! empty( $image_fit ) )         { $atts[] = 'image_fit="' . esc_attr( $image_fit ) . '"'; }
if ( ! empty( $image_position ) )    { $atts[] = 'image_position="' . esc_attr( $image_position ) . '"'; }
if ( ! empty( $image_focus ) )       { $atts[] = 'image_focus="' . esc_attr( $image_focus ) . '"'; }
if ( 'true' === $text_overlay )      { $atts[] = 'text_overlay="true"'; }
if ( '' !== (string) $image_opacity ){ $atts[] = 'image_opacity="' . esc_attr( $image_opacity ) . '"'; }
if ( '' !== (string) $reviews_per_show ) { $atts[] = 'reviews_per_show="' . esc_attr( $reviews_per_show ) . '"'; }
if ( ! empty( $review_align ) )           { $atts[] = 'review_align="' . esc_attr( $review_align ) . '"'; }

echo do_shortcode( '[stagekitwp_testimonials ' . implode( ' ', $atts ) . ']' );
