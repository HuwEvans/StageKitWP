<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }
// =============================================================================
// STAGEKITWP: REFRESH-SAFE BRANDED ROUND SOCIAL ICONS WIDGET
// =============================================================================

class STAGEKITWP_Custom_Social_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'stagekitwp_custom_social_widget',
            __( '🎭 StageKitWP: Custom Social Icons', 'stagekitwp-theme' ),
            array( 'description' => __( 'A refresh-safe social profile block featuring circular branded Dashicons and display layout options.', 'stagekitwp-theme' ) )
        );
    }

    private function get_svg_icon_markup( $network ) {
        $icons = array(
            'tiktok'  => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false" style="display:block;width:18px;height:18px;"><path fill="currentColor" d="M14.5 3c.3 1.7 1.3 3 2.8 3.8 1 .5 2 .8 3 .8v3.2c-1.6 0-3-.4-4.4-1.2v6.1c0 3.4-2.8 6.1-6.1 6.1S3.7 19 3.7 15.6c0-3.3 2.6-5.9 5.9-6.1v3.2a2.9 2.9 0 1 0 3.2 2.9V3h1.7z"/></svg>',
            'threads' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false" style="display:block;width:18px;height:18px;"><path fill="currentColor" d="M16.4 11.2c-.2-2.5-1.8-3.9-4.3-3.9-2.7 0-4.6 1.7-4.6 4.4 0 3 2 4.8 5 4.8 2.3 0 3.9-.9 4.6-2.5.2-.5.3-1 .3-1.5 0-.2 0-.4-.1-.6.7.4 1.1 1.1 1.1 2.1 0 2.3-2 3.9-5.2 3.9-3.8 0-6.2-2.2-6.2-6.1 0-3.8 2.5-6.1 6.2-6.1 3.4 0 5.7 1.8 6.2 5l-3 .5zm-3.8 2.6c-.9 0-1.5-.5-1.5-1.3 0-.9.7-1.5 1.8-1.5.5 0 1 .1 1.5.3v.2c0 1.3-.7 2.3-1.8 2.3z"/></svg>',
        );

        return isset( $icons[ $network ] ) ? $icons[ $network ] : '';
    }

    // Frontend rendering layout block
    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }

        $display_style = ! empty( $instance['display_style'] ) ? $instance['display_style'] : 'both';
        // Accept legacy values from older widget saves.
        if ( 'icon' === $display_style ) {
            $display_style = 'icons';
        }
        if ( ! in_array( $display_style, array( 'both', 'icons', 'text' ), true ) ) {
            $display_style = 'both';
        }

        // Official Brand Specifications
        $profiles = array(
            'facebook'  => array( 'icon' => 'dashicons-facebook',  'color' => '#1877F2', 'label' => 'Facebook', 'icon_type' => 'dashicon' ),
            'x'         => array( 'icon' => 'dashicons-twitter',   'color' => '#111111', 'label' => 'X',        'icon_type' => 'dashicon' ),
            'instagram' => array( 'icon' => 'dashicons-instagram', 'color' => '#E1306C', 'label' => 'Instagram','icon_type' => 'dashicon' ),
            'youtube'   => array( 'icon' => 'dashicons-youtube',   'color' => '#FF0000', 'label' => 'YouTube',  'icon_type' => 'dashicon' ),
            'tiktok'    => array( 'icon' => 'tiktok',              'color' => '#000000', 'label' => 'TikTok',   'icon_type' => 'svg' ),
            'threads'   => array( 'icon' => 'threads',             'color' => '#101010', 'label' => 'Threads',  'icon_type' => 'svg' ),
        );
        
        // Dynamically looks for the --column-align variable we set up in footer.php
        echo '<div class="stagekitwp-custom-social-wrap" style="display: flex; gap: 12px; align-items: center; justify-content: var(--column-align, flex-start); flex-wrap: wrap; margin-top: 15px;">';
        
        foreach ( $profiles as $key => $data ) {
            $profile_url = '';
            if ( 'x' === $key ) {
                $profile_url = ! empty( $instance['x'] ) ? $instance['x'] : ( ! empty( $instance['twitter'] ) ? $instance['twitter'] : '' );
            } else {
                $profile_url = ! empty( $instance[ $key ] ) ? $instance[ $key ] : '';
            }

            if ( ! empty( $profile_url ) ) {
                $url = esc_url( $profile_url );
                
                // Build layout based on selected display style using global CSS variables
                echo sprintf(
                    '<a href="%1$s" target="_blank" rel="noopener" class="stagekitwp-social-link" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: var(--stagekitwp-text-light); font-family: -apple-system, sans-serif; font-size: 0.9rem; font-weight: 600; transition: opacity 0.2s;">',
                    $url
                );

                // Render Circular Dashicon if profile layout asks for it
                if ( 'text' !== $display_style ) {
                    if ( 'svg' === $data['icon_type'] ) {
                        echo sprintf(
                            '<span class="stagekitwp-social-svg-icon stagekitwp-social-svg-%1$s" style="background-color: %2$s; color: #ffffff; width: 34px; height: 34px; line-height: 34px; border-radius: 50%%; text-align: center; font-size: 18px; display: inline-flex; align-items: center; justify-content: center;">%3$s</span>',
                            esc_attr( $data['icon'] ),
                            esc_attr( $data['color'] ),
                            $this->get_svg_icon_markup( $data['icon'] )
                        );
                    } else {
                        echo sprintf(
                            '<span class="stagekitwp-social-icon-badge" style="background-color: %2$s; color: #ffffff; width: 34px; height: 34px; border-radius: 50%%; text-align: center; display: inline-flex; align-items: center; justify-content: center;"><span class="dashicons %1$s" style="color:#ffffff; font-size:18px; line-height:1; width:18px; height:18px;"></span></span>',
                            esc_attr( $data['icon'] ),
                            esc_attr( $data['color'] )
                        );
                    }
                }

                // Render Text Label if profile layout asks for it
                if ( 'icons' !== $display_style ) {
                    echo sprintf( '<span class="stagekitwp-social-text-label" style="text-transform: capitalize;">%1$s</span>', esc_html( $data['label'] ) );
                }

                echo '</a>';
            }
        }
        echo '</div>';

        echo $args['after_widget'];
    }

    // Admin Customizer Control Form Layout
    public function form( $instance ) {
        $title         = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Follow Us', 'stagekitwp-theme' );
        $display_style = ! empty( $instance['display_style'] ) ? $instance['display_style'] : 'both';
        $facebook      = ! empty( $instance['facebook'] ) ? $instance['facebook'] : '';
        $x             = ! empty( $instance['x'] ) ? $instance['x'] : ( ! empty( $instance['twitter'] ) ? $instance['twitter'] : '' );
        $instagram     = ! empty( $instance['instagram'] ) ? $instance['instagram'] : '';
        $youtube       = ! empty( $instance['youtube'] ) ? $instance['youtube'] : '';
        $tiktok        = ! empty( $instance['tiktok'] ) ? $instance['tiktok'] : '';
        $threads       = ! empty( $instance['threads'] ) ? $instance['threads'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Widget Title:', 'stagekitwp-theme' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'display_style' ) ); ?>"><?php esc_html_e( 'Display Options:', 'stagekitwp-theme' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'display_style' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'display_style' ) ); ?>">
                <option value="both" <?php selected( $display_style, 'both' ); ?>><?php _e( 'Icons and Text Labels', 'stagekitwp-theme' ); ?></option>
                <option value="icons" <?php selected( $display_style, 'icons' ); ?>><?php _e( 'Icons Only (Circular Badges)', 'stagekitwp-theme' ); ?></option>
                <option value="text" <?php selected( $display_style, 'text' ); ?>><?php _e( 'Text Only', 'stagekitwp-theme' ); ?></option>
            </select>
        </p>
        <hr style="border: 0; border-top: 1px solid #dfdfdf; margin: 15px 0;">
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'facebook' ) ); ?>"><?php esc_html_e( 'Facebook Link:', 'stagekitwp-theme' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'facebook' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'facebook' ) ); ?>" type="url" value="<?php echo esc_url( $facebook ); ?>" placeholder="https://facebook.com/...">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'x' ) ); ?>"><?php esc_html_e( 'X Link:', 'stagekitwp-theme' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'x' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'x' ) ); ?>" type="url" value="<?php echo esc_url( $x ); ?>" placeholder="https://x.com/...">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'instagram' ) ); ?>"><?php esc_html_e( 'Instagram Link:', 'stagekitwp-theme' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'instagram' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'instagram' ) ); ?>" type="url" value="<?php echo esc_url( $instagram ); ?>" placeholder="https://instagram.com/...">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'youtube' ) ); ?>"><?php esc_html_e( 'YouTube Link:', 'stagekitwp-theme' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'youtube' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'youtube' ) ); ?>" type="url" value="<?php echo esc_url( $youtube ); ?>" placeholder="https://youtube.com/...">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'tiktok' ) ); ?>"><?php esc_html_e( 'TikTok Link:', 'stagekitwp-theme' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'tiktok' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'tiktok' ) ); ?>" type="url" value="<?php echo esc_url( $tiktok ); ?>" placeholder="https://www.tiktok.com/@...">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'threads' ) ); ?>"><?php esc_html_e( 'Threads Link:', 'stagekitwp-theme' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'threads' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'threads' ) ); ?>" type="url" value="<?php echo esc_url( $threads ); ?>" placeholder="https://www.threads.net/@...">
        </p>
        <?php
    }

    // Sanitize and save data options
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title']         = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $allowed_styles = array( 'both', 'icons', 'text' );
        $instance['display_style'] = ( ! empty( $new_instance['display_style'] ) && in_array( $new_instance['display_style'], $allowed_styles, true ) )
            ? $new_instance['display_style']
            : 'both';
        $instance['facebook']      = ( ! empty( $new_instance['facebook'] ) ) ? esc_url_raw( $new_instance['facebook'] ) : '';
        $instance['x']             = ( ! empty( $new_instance['x'] ) ) ? esc_url_raw( $new_instance['x'] ) : '';
        // Backward-compatible storage for widgets previously using twitter.
        $instance['twitter']       = $instance['x'];
        $instance['instagram']     = ( ! empty( $new_instance['instagram'] ) ) ? esc_url_raw( $new_instance['instagram'] ) : '';
        $instance['youtube']       = ( ! empty( $new_instance['youtube'] ) ) ? esc_url_raw( $new_instance['youtube'] ) : '';
        $instance['tiktok']        = ( ! empty( $new_instance['tiktok'] ) ) ? esc_url_raw( $new_instance['tiktok'] ) : '';
        $instance['threads']       = ( ! empty( $new_instance['threads'] ) ) ? esc_url_raw( $new_instance['threads'] ) : '';
        return $instance;
    }
}

// Register the custom control setup into the core loop
function stagekitwp_register_custom_social_widget() {
    register_widget( 'STAGEKITWP_Custom_Social_Widget' );
}
add_action( 'widgets_init', 'stagekitwp_register_custom_social_widget' );