<?php
defined( 'ABSPATH' ) || exit;

$view       = isset( $settings->view )       ? sanitize_key( $settings->view )       : 'grid';
$columns    = isset( $settings->columns )    ? intval( $settings->columns )           : 3;
$category   = isset( $settings->category )  ? sanitize_key( $settings->category )    : '';
$image_type = isset( $settings->image_type ) ? sanitize_key( $settings->image_type ) : 'banner';

$atts = array();
if ( 'grid' !== $view )     { $atts[] = 'view="' . esc_attr( $view ) . '"'; }
if ( 3 !== $columns )       { $atts[] = 'columns="' . $columns . '"'; }
if ( ! empty( $category ) ) { $atts[] = 'category="' . esc_attr( $category ) . '"'; }
if ( 'banner' !== $image_type ) { $atts[] = 'image_type="' . esc_attr( $image_type ) . '"'; }

echo do_shortcode( '[stagekitwp_advertisers ' . implode( ' ', $atts ) . ']' );
