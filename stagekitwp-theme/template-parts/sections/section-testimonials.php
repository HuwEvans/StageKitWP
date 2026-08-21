<?php
/**
 * Template part for rendering the Homepage Testimonials Section via Block Registry
 *
 * @package StageKitWP_Theme
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Safety check: Exit if the core plugin isn't active
if ( ! class_exists( 'StageKitWP_Core' ) ) {
    return;
}

// Fetch Customizer settings with smart standard defaults
$testimonial_width = get_theme_mod( 'stagekitwp_testimonials_width', '100' ); // Default to 100%
$testimonial_align = get_theme_mod( 'stagekitwp_testimonials_alignment', 'center' ); // Default to center
$show_rating       = get_theme_mod( 'stagekitwp_testimonials_show_rating', true );

$testimonial_mode   = get_theme_mod( 'stagekitwp_testimonials_mode', 'slider' );
$testimonial_layout = get_theme_mod( 'stagekitwp_testimonials_layout', 'classic' );
$testimonial_cols   = absint( get_theme_mod( 'stagekitwp_testimonials_columns', 3 ) );
$testimonial_limit  = absint( get_theme_mod( 'stagekitwp_testimonials_limit', 6 ) );
$testimonial_image_width = absint( get_theme_mod( 'stagekitwp_testimonials_image_width', 520 ) );
$testimonial_image_height = absint( get_theme_mod( 'stagekitwp_testimonials_image_height', 280 ) );
$testimonial_image_fit = get_theme_mod( 'stagekitwp_testimonials_image_fit', 'cover' );
$testimonial_image_position = get_theme_mod( 'stagekitwp_testimonials_image_position', 'center' );
$testimonial_image_focus = get_theme_mod( 'stagekitwp_testimonials_image_focus', 'center_center' );
$testimonial_text_overlay = get_theme_mod( 'stagekitwp_testimonials_text_overlay', false );
$testimonial_image_opacity = get_theme_mod( 'stagekitwp_testimonials_image_opacity', '0.45' );
$testimonial_reviews_per_show = absint( get_theme_mod( 'stagekitwp_testimonials_reviews_per_show', 4 ) );
$testimonial_review_align = get_theme_mod( 'stagekitwp_testimonials_review_align', 'left' );

if ( $testimonial_cols < 1 ) {
    $testimonial_cols = 1;
} elseif ( $testimonial_cols > 4 ) {
    $testimonial_cols = 4;
}

if ( $testimonial_limit < 1 ) {
    $testimonial_limit = 6;
}
if ( $testimonial_image_width < 200 ) {
    $testimonial_image_width = 520;
}
if ( $testimonial_image_height < 120 ) {
    $testimonial_image_height = 280;
}
if ( ! in_array( $testimonial_image_fit, array( 'cover', 'contain' ), true ) ) {
    $testimonial_image_fit = 'cover';
}
if ( $testimonial_reviews_per_show < 1 ) {
    $testimonial_reviews_per_show = 4;
}
if ( ! in_array( $testimonial_review_align, array( 'left', 'center', 'right', 'alternating', 'alternating_lr' ), true ) ) {
    $testimonial_review_align = 'left';
}

$show_name    = get_theme_mod( 'stagekitwp_testimonials_show_name', true );
$show_comment = get_theme_mod( 'stagekitwp_testimonials_show_comment', true );
$show_show    = get_theme_mod( 'stagekitwp_testimonials_show_show', true );
$show_name_placement = get_theme_mod( 'stagekitwp_testimonials_show_name_placement', 'meta' );
$tag_icon_source = get_theme_mod( 'stagekitwp_testimonials_tag_icon_source', 'none' );
$tag_icon_url = get_theme_mod( 'stagekitwp_testimonials_tag_icon_url', '' );
$tag_icon_size = absint( get_theme_mod( 'stagekitwp_testimonials_tag_icon_size', 18 ) );
$show_date    = get_theme_mod( 'stagekitwp_testimonials_show_date', true );
$show_media   = get_theme_mod( 'stagekitwp_testimonials_show_media', true );

// Calculate layout margin rules based on alignment choice
$margin_style = 'margin: 0 auto;'; // center fallback
if ( 'left' === $testimonial_align ) {
    $margin_style = 'margin: 0 auto 0 0;';
} elseif ( 'right' === $testimonial_align ) {
    $margin_style = 'margin: 0 0 0 auto;';
}
?>

<section class="stagekitwp-testimonials-section <?php echo $show_rating ? '' : 'stagekitwp-hide-testimonial-rating'; ?>" style="background-color: #1a1a1a; padding: 80px 0; color: #ffffff; border-top: 1px solid #222222;">
    <div class="stagekitwp-container">
        
        <div class="section-title-wrap" style="text-align: center; margin-bottom: 50px;">
            <h2 class="section-main-title" style="font-size: 2.2rem; text-transform: uppercase; letter-spacing: 1px; color: #ffffff; margin-bottom: 10px;">
                <?php esc_html_e( 'What Our Audience Says', 'stagekitwp-theme' ); ?>
            </h2>
            <div class="title-divider" style="width: 60px; height: 3px; background-color: #e50914; margin: 0 auto;"></div>
        </div>

        <!-- Customizable wrapper box governing width restrictions and layout alignment paths -->
        <div class="stagekitwp-testimonials-block-wrapper" style="width: <?php echo esc_attr( $testimonial_width ); ?>%; <?php echo esc_attr( $margin_style ); ?>">
            <?php 
            // 1. Look up the registration data for the core plugin block
            $registry = WP_Block_Type_Registry::get_instance();
            $target_block = $registry->get_registered( 'stagekitwp/stagekitwp-testimonials' );

            $block_atts = array(
                'limit'        => $testimonial_limit,
                'columns'      => $testimonial_cols,
                'mode'         => $testimonial_mode,
                'layout'       => $testimonial_layout,
                'image_width'  => $testimonial_image_width,
                'image_height' => $testimonial_image_height,
                'image_fit'    => $testimonial_image_fit,
                'image_position' => $testimonial_image_position,
                'image_focus'  => $testimonial_image_focus,
                'text_overlay' => (bool) $testimonial_text_overlay,
                'image_opacity'=> $testimonial_image_opacity,
                'show_name'    => (bool) $show_name,
                'show_comment' => (bool) $show_comment,
                'show_rating'  => (bool) $show_rating,
                'show_show'    => (bool) $show_show,
                'show_name_placement' => $show_name_placement,
                'tag_icon_source' => $tag_icon_source,
                'tag_icon_url' => $tag_icon_url,
                'tag_icon_size' => $tag_icon_size,
                'show_date'    => (bool) $show_date,
                'show_media'   => (bool) $show_media,
                'reviews_per_show' => $testimonial_reviews_per_show,
                'review_align' => $testimonial_review_align,
            );

            if ( $target_block && method_exists( $target_block, 'render' ) ) {
                // 2. Execute block directly with custom attribute arguments
                echo $target_block->render( $block_atts );
            } else {
                // 3. Fallback: If registry rendering fails, check if the standard shortcode exists
                if ( shortcode_exists( 'stagekitwp_testimonials' ) ) {
                    $shortcode_atts = sprintf(
                        ' mode="%1$s" layout="%2$s" limit="%3$d" columns="%4$d" image_width="%5$d" image_height="%6$d" image_fit="%7$s" image_position="%8$s" image_focus="%9$s" text_overlay="%10$s" image_opacity="%11$s" show_name="%12$s" show_comment="%13$s" show_rating="%14$s" show_show="%15$s" show_name_placement="%16$s" tag_icon_source="%17$s" tag_icon_url="%18$s" tag_icon_size="%19$d" show_date="%20$s" show_media="%21$s" reviews_per_show="%22$d" review_align="%23$s"',
                        esc_attr( $testimonial_mode ),
                        esc_attr( $testimonial_layout ),
                        intval( $testimonial_limit ),
                        intval( $testimonial_cols ),
                        intval( $testimonial_image_width ),
                        intval( $testimonial_image_height ),
                        esc_attr( $testimonial_image_fit ),
                        esc_attr( $testimonial_image_position ),
                        esc_attr( $testimonial_image_focus ),
                        $testimonial_text_overlay ? 'true' : 'false',
                        esc_attr( $testimonial_image_opacity ),
                        $show_name ? 'true' : 'false',
                        $show_comment ? 'true' : 'false',
                        $show_rating ? 'true' : 'false',
                        $show_show ? 'true' : 'false',
                        esc_attr( $show_name_placement ),
                        esc_attr( $tag_icon_source ),
                        esc_attr( $tag_icon_url ),
                        intval( $tag_icon_size ),
                        $show_date ? 'true' : 'false',
                        $show_media ? 'true' : 'false',
                        intval( $testimonial_reviews_per_show ),
                        esc_attr( $testimonial_review_align )
                    );
                    echo do_shortcode( '[stagekitwp_testimonials' . $shortcode_atts . ']' );
                } else {
                    // Friendly administrative notice if no data can be fetched
                    echo '<p style="text-align:center; color:#777;">' . esc_html__( 'Add testimonials via StageKitWP Core to display them here.', 'stagekitwp-theme' ) . '</p>';
                }
            }
            ?>
        </div>

    </div>
</section>

<style type="text/css">
.stagekitwp-testimonials-block-wrapper .testimonial-item,
.stagekitwp-testimonials-block-wrapper blockquote {
    background: #111111 !important;
    border-left: 3px solid #e50914 !important;
    padding: 25px !important;
    border-radius: 4px;
    color: #eeeeee !important;
    font-style: italic;
    line-height: 1.6;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

.stagekitwp-testimonials-block-wrapper .testimonial-author {
    display: block;
    margin-top: 15px;
    font-style: normal;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.85rem;
    color: #e50914;
    letter-spacing: 0.5px;
}

.stagekitwp-testimonials-section.stagekitwp-hide-testimonial-rating .stagekitwp-rating {
    display: none !important;
}
</style>