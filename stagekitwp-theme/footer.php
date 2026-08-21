<?php
/**
 * The template for displaying the theme footer
 *
 * Completely widgetized 3-Column Grid + Bottom Utility Framework. Zero hardcoded assets.
 *
 * @package StageKitWP_Theme
 */

// Fetch Alignment and Layout Vector Choices safely with fallbacks
$layout_mode  = get_theme_mod( 'stagekitwp_footer_layout_mode', 'three-column' );
$left_align   = get_theme_mod( 'stagekitwp_footer_align_left', 'left' );
$middle_align = get_theme_mod( 'stagekitwp_footer_align_middle', 'center' );
$right_align  = get_theme_mod( 'stagekitwp_footer_align_right', 'right' );

// Calculate Container Style Configuration Toggles
$container_styles = 'max-width: var(--stagekitwp-site-max-width, 1200px); margin: 0 auto; padding: 0 20px;';
$row_flex_styles  = 'display: flex; flex-wrap: wrap; gap: 40px; justify-content: space-between;';

if ( $layout_mode === 'full-width' ) {
    $container_styles = 'max-width: 100%; margin: 0; padding: 0 40px;';
} elseif ( $layout_mode === 'one-column' ) {
    $row_flex_styles  = 'display: flex; flex-direction: column; gap: 40px; justify-content: center; align-items: center;';
}
?>
    </div>
	<footer id="colophon" class="site-footer" style="background: var(--stagekitwp-bg); color: var(--stagekitwp-text-light); padding: 60px 0 20px 0; font-family: -apple-system, sans-serif; clear: both;">
        
        <div class="footer-columns-container" style="<?php echo esc_attr( $container_styles . $row_flex_styles ); ?> overflow: visible;">
            
            <div class="footer-column stagekitwp-col-1" style="flex: 1; min-width: 280px; max-width: 100%; display: block; overflow: visible; text-align: <?php echo esc_attr( $left_align ); ?>;">
                <?php if ( is_active_sidebar( 'stagekitwp-footer-col-1' ) ) : ?>
                    <?php dynamic_sidebar( 'stagekitwp-footer-col-1' ); ?>
                <?php endif; ?>
            </div>

            <div class="footer-column stagekitwp-col-2" style="flex: 1; min-width: 280px; max-width: 100%; display: block; overflow: visible; text-align: <?php echo esc_attr( $middle_align ); ?>;">
                <?php if ( is_active_sidebar( 'stagekitwp-footer-col-2' ) ) : ?>
                    <?php dynamic_sidebar( 'stagekitwp-footer-col-2' ); ?>
                <?php endif; ?>
            </div>

            <div class="footer-column stagekitwp-col-3" style="flex: 1; min-width: 280px; max-width: 100%; display: block; overflow: visible; text-align: <?php echo esc_attr( $right_align ); ?>;">
                <?php if ( is_active_sidebar( 'stagekitwp-footer-col-3' ) ) : ?>
                    <?php dynamic_sidebar( 'stagekitwp-footer-col-3' ); ?>
                <?php endif; ?>
            </div>

        </div>

        <?php if ( is_active_sidebar( 'stagekitwp-footer-socket' ) ) : ?>
            <hr style="border: 0; border-top: 1px solid #222222; <?php echo esc_attr( $layout_mode === 'full-width' ? 'max-width: 100%; margin: 40px 40px 20px 40px;' : 'max-width: var(--stagekitwp-site-max-width, 1200px); margin: 40px auto 20px auto;' ); ?> padding: 0 20px;">
            
            <div class="footer-socket-bar" style="<?php echo esc_attr( $layout_mode === 'full-width' ? 'max-width: 100%; margin: 0 40px;' : 'max-width: var(--stagekitwp-site-max-width, 1200px); margin: 0 auto;' ); ?> padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; font-size: 0.85rem; color: #888888; overflow: visible;">
                <div class="socket-left-data" style="flex: 1; min-width: 250px;">
                    <?php dynamic_sidebar( 'stagekitwp-footer-socket' ); ?>
                </div>
                <div class="socket-right-actions">
                    <a href="#page" class="stagekitwp-to-top-link" style="color: #e50914; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                        <?php esc_html_e( 'Back to Top', 'stagekitwp-theme' ); ?> ↑
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </footer></div><?php wp_footer(); ?>

    <style type="text/css">
        /* Dynamically force the Social Widget wrapper to align based on parent configurations */
        .stagekitwp-col-1 .stagekitwp-custom-social-wrap { justify-content: <?php echo $left_align === 'right' ? 'flex-end' : ($left_align === 'center' ? 'center' : 'flex-start'); ?> !important; }
        .stagekitwp-col-2 .stagekitwp-custom-social-wrap { justify-content: <?php echo $middle_align === 'right' ? 'flex-end' : ($middle_align === 'center' ? 'center' : 'flex-start'); ?> !important; }
        .stagekitwp-col-3 .stagekitwp-custom-social-wrap { justify-content: <?php echo $right_align === 'right' ? 'flex-end' : ($right_align === 'center' ? 'center' : 'flex-start'); ?> !important; }

        /* Responsive Mobile Behavior Fallbacks */
        @media (max-width: 767px) {
            .footer-columns-container {
                flex-direction: column !important;
                align-items: center !important;
            }
            .footer-column {
                text-align: center !important;
                width: 100% !important;
            }
            .footer-column .stagekitwp-custom-social-wrap {
                justify-content: center !important; /* Center icons on mobile screens */
            }
        }
    </style>
</body>
</html>