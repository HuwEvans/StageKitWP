<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

class STAGEKITWP_Donate_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'stagekitwp_donate_widget',
            __( '🎭 StageKitWP: Donate Button', 'stagekitwp-theme' ),
            array( 'description' => __( 'Displays the global donate icon banner with the configured donate link.', 'stagekitwp-theme' ) )
        );
    }

    public function widget( $args, $instance ) {
        $image_url = get_theme_mod( 'stagekitwp_donate_button_image' );
        $link_url  = get_theme_mod( 'stagekitwp_donate_button_url', home_url( '/donate' ) );

        if ( empty( $image_url ) ) {
            return;
        }

        echo $args['before_widget'];

        $title = ! empty( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';
        if ( ! empty( $title ) ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        printf(
            '<a class="stagekitwp-donate-widget-link" href="%1$s" aria-label="%2$s" style="display:inline-flex;align-items:center;justify-content:flex-start;text-decoration:none;"><img src="%3$s" alt="%2$s" style="display:block;max-width:100%%;height:auto;"></a>',
            esc_url( $link_url ),
            esc_attr__( 'Donate', 'stagekitwp-theme' ),
            esc_url( $image_url )
        );

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Donate', 'stagekitwp-theme' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Widget Title:', 'stagekitwp-theme' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p><?php esc_html_e( 'Uses the donate image and link configured in StageKitWP Global Elements.', 'stagekitwp-theme' ); ?></p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
        return $instance;
    }
}

function stagekitwp_register_donate_widget() {
    register_widget( 'STAGEKITWP_Donate_Widget' );
}
add_action( 'widgets_init', 'stagekitwp_register_donate_widget' );
