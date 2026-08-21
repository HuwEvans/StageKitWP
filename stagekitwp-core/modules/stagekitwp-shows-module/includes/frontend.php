<?php
defined( 'ABSPATH' ) || exit;

$season_id = isset( $settings->season_id ) ? intval( $settings->season_id ) : 0;
$which     = isset( $settings->which )     ? sanitize_key( $settings->which ) : 'all';
$exclude   = isset( $settings->exclude )   ? sanitize_text_field( $settings->exclude ) : '';

$atts = array();
if ( $season_id > 0 )     { $atts[] = 'season_id="' . $season_id . '"'; }
if ( 'all' !== $which )   { $atts[] = 'which="' . esc_attr( $which ) . '"'; }
if ( ! empty( $exclude ) ) { $atts[] = 'exclude="' . esc_attr( $exclude ) . '"'; }

echo do_shortcode( '[stagekitwp_shows ' . implode( ' ', $atts ) . ']' );
