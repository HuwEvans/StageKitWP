<?php
defined( 'ABSPATH' ) || exit;

$layout      = isset( $settings->layout )      ? $settings->layout      : 'grid';
$columns     = isset( $settings->columns )     ? intval( $settings->columns ) : 3;
$photo_size  = isset( $settings->photo_size )  ? intval( $settings->photo_size ) : 120;
$show_photos = isset( $settings->show_photos ) ? $settings->show_photos : 'true';
$show_bio    = isset( $settings->show_bio )    ? $settings->show_bio    : 'false';

$atts = array();
if ( 'grid'  !== $layout )      { $atts[] = 'layout="'      . esc_attr( $layout )      . '"'; }
if ( 3       !== $columns )     { $atts[] = 'columns="'     . $columns                 . '"'; }
if ( 120     !== $photo_size )  { $atts[] = 'photo_size="'  . $photo_size              . '"'; }
if ( 'true'  !== $show_photos ) { $atts[] = 'show_photos="' . esc_attr( $show_photos ) . '"'; }
if ( 'false' !== $show_bio )    { $atts[] = 'show_bio="'    . esc_attr( $show_bio )    . '"'; }

echo do_shortcode( '[stagekitwp_board_members ' . implode( ' ', $atts ) . ']' );
