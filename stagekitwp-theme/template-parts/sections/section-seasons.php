<?php
/**
 * Template part for displaying a 3-column Season layout
 *
 * @package StageKitWP_Theme
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Retrieve configuration variables passed from index.php
$season_type = isset( $args['type'] ) ? $args['type'] : 'current'; // 'current' or 'upcoming'
$section_title = isset( $args['title'] ) ? $args['title'] : __( 'On Stage', 'stagekitwp-theme' );

// Query events matching our target season tag/slug
$season_args = array(
    'category_name'  => 'events',
    'posts_per_page' => 3,
    'post_status'    => 'publish',
    'tag'            => $season_type, // Separates 'current' season shows from 'upcoming' shows
    'orderby'        => 'meta_value',
    'meta_key'       => 'show_start_date', // Optional custom field tracking show runs
    'order'          => 'ASC'
);

// Fallback loop configuration if custom tags aren't populated yet
$season_query = new WP_Query( $season_args );
?>

<section class="stagekitwp-season-section stagekitwp-season-<?php echo esc_attr( $season_type ); ?>">
    <div class="stagekitwp-container">
        
        <div class="stagekitwp-section-header">
            <h2><?php echo esc_html( $section_title ); ?></h2>
            <div class="stagekitwp-decorator-line"></div>
        </div>

        <div class="stagekitwp-grid-3-col">
            <?php if ( $season_query->have_posts() ) : ?>
                <?php while ( $season_query->have_posts() ) : $season_query->the_post(); ?>
                    
                    <div class="stagekitwp-season-card">
                        <div class="stagekitwp-season-thumbnail">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail( 'medium_large' ); ?>
                            <?php else : ?>
                                <div class="stagekitwp-season-fallback">
                                    <span class="dashicons dashicons-theater"></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="stagekitwp-season-badge">
                                <?php echo esc_html( ucfirst( $season_type ) . ' Season' ); ?>
                            </div>
                        </div>

                        <div class="stagekitwp-season-meta-box">
                            <h3 class="stagekitwp-show-title"><?php the_title(); ?></h3>
                            
                            <?php 
                            // Extract show run date if custom metadata exists
                            $run_dates = get_post_meta( get_the_ID(), 'show_run_dates', true );
                            if ( $run_dates ) : ?>
                                <p class="stagekitwp-show-dates">🗓️ <?php echo esc_html( $run_dates ); ?></p>
                            <?php endif; ?>

                            <div class="stagekitwp-show-excerpt">
                                <?php echo wp_kses_post( wp_trim_words( get_the_excerpt(), 15, '...' ) ); ?>
                            </div>

                            <div class="stagekitwp-show-actions">
                                <a href="<?php the_permalink(); ?>" class="stagekitwp-btn-primary">
                                    <?php $season_type === 'current' ? esc_html_e( 'Buy Tickets', 'stagekitwp-theme' ) : esc_html_e( 'Learn More', 'stagekitwp-theme' ); ?>
                                </a>
                            </div>
                        </div>
                    </div>

                <?php endwhile; wp_reset_postdata(); ?>
            <?php else : ?>
                <?php for ( $i = 1; $i <= 3; $i++ ) : ?>
                    <div class="stagekitwp-season-card stagekitwp-placeholder-card">
                        <div class="stagekitwp-season-fallback"><span class="dashicons dashicons-plus"></span></div>
                        <div class="stagekitwp-season-meta-box" style="text-align:center;">
                            <h3 style="color:#bbb;"><?php printf( esc_html__( 'Add %s Production %d', 'stagekitwp-theme' ), esc_html( ucfirst( $season_type ) ), $i ); ?></h3>
                            <p style="font-size:0.85rem; color:#ccc;"><?php esc_html_e( 'Assign category "Events" and tag it with your season name.', 'stagekitwp-theme' ); ?></p>
                        </div>
                    </div>
                <?php endfor; ?>
            <?php endif; ?>
        </div>

    </div>
</section>