<?php
defined( 'ABSPATH' ) || exit;

$season  = isset( $settings->season )  ? intval( $settings->season )  : 0;
$columns = isset( $settings->columns ) ? intval( $settings->columns ) : 3;
$size    = isset( $settings->size )    ? sanitize_key( $settings->size ) : 'medium';

$atts = array();
if ( $season > 0 )         { $atts[] = 'season="' . $season . '"'; }
if ( 3 !== $columns )      { $atts[] = 'columns="' . $columns . '"'; }
if ( 'medium' !== $size )  { $atts[] = 'size="' . esc_attr( $size ) . '"'; }

echo do_shortcode( '[stagekitwp_programs ' . implode( ' ', $atts ) . ']' );
