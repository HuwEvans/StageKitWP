<?php
defined( 'ABSPATH' ) || exit;

$show_id = isset( $settings->show_id ) ? intval( $settings->show_id ) : 0;

if ( ! $show_id ) {
    echo '<p class="stagekitwp-bb-notice">' . esc_html__( 'Please select a show in the module settings.', 'stagekitwp-core' ) . '</p>';
    return;
}

echo do_shortcode( '[stagekitwp_show_cast show_id="' . $show_id . '"]' );
