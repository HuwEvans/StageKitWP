<?php
/**
 * The template for displaying single production show listings.
 *
 * @package StageKitWP_Theme
 */

get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>
    <?php $thumb_url = get_the_post_thumbnail_url( get_the_ID(), 'full' ); ?>
    <header class="show-hero-banner" style="position: relative; overflow: hidden; background-color: #111111; padding: 120px 0 60px 0; color: #ffffff;">
        <?php if ( $thumb_url ) : ?>
            <div class="show-hero-image-wrapper" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1;">
                <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" class="show-hero-image" loading="lazy" decoding="async" style="width: 100%; height: 100%; object-fit: cover; object-position: center; display: block;" />
            </div>
            <div class="show-hero-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(180deg, rgba(0,0,0,0.4) 0%, #111111 100%); z-index: 2;"></div>
        <?php endif; ?>
        <div class="stagekitwp-container" style="position: relative; z-index: 3; max-width: var(--stagekitwp-site-max-width, 1200px); margin: 0 auto; padding: 0 20px;">
            <span class="show-badge" style="background-color: #e50914; color: #fff; padding: 5px 12px; font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; border-radius: 2px;">
                <?php _e( 'On Stage', 'stagekitwp-theme' ); ?>
            </span>
            <h1 class="show-title" style="font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 900; margin: 15px 0 5px 0; text-transform: uppercase; letter-spacing: -1px; line-height: 1.1;">
                <?php the_title(); ?>
            </h1>
            <p class="show-meta-tagline" style="font-size: 1.2rem; color: #cccccc; font-style: italic; max-width: 800px;">
                <?php echo esc_html( get_post_meta( get_the_ID(), '_stagekitwp_show_tagline', true ) ); ?>
            </p>
        </div>
    </header>

    <div class="show-body-wrapper" style="background-color: #ffffff; padding: 60px 0;">
        <div class="stagekitwp-container" style="max-width: var(--stagekitwp-site-max-width, 1200px); margin: 0 auto; padding: 0 20px; display: flex; flex-wrap: wrap; gap: 40px;">
            
            <main id="primary" class="show-main-content" style="flex: 1 1 650px;">
                
                <section class="show-synopsis" style="margin-bottom: 45px;">
                    <h2 style="font-size: 1.5rem; text-transform: uppercase; border-bottom: 2px solid #f0f0f1; padding-bottom: 10px; margin-bottom: 20px; color: #111111;">
                        <?php _e( 'Synopsis & Details', 'stagekitwp-theme' ); ?>
                    </h2>
                    <div class="entry-content" style="line-height: 1.8; color: #333333; font-size: 1.1rem;">
                        <?php the_content(); ?>
                    </div>
                </section>

                <?php if ( class_exists( 'StageKitWP_Core' ) ) : ?>
                    <section class="show-cast-block" style="margin-bottom: 45px;">
                        <h2 style="font-size: 1.5rem; text-transform: uppercase; border-bottom: 2px solid #f0f0f1; padding-bottom: 10px; margin-bottom: 20px; color: #111111;">
                            <?php _e( 'Cast & Creative Team', 'stagekitwp-theme' ); ?>
                        </h2>
                        <div class="stagekitwp-plugin-cast-injection">
                            <?php 
                            // Call the core plugin block engine layout dynamically
                            $registry = WP_Block_Type_Registry::get_instance();
                            $cast_block = $registry->get_registered( 'stagekitwp/stagekitwp-season-cast' );
                            if ( $cast_block && method_exists( $cast_block, 'render' ) ) {
                                echo $cast_block->render( array( 'show_id' => get_the_ID() ) );
                            } else {
                                echo '<p style="color:#777; font-style:italic;">' . __( 'Cast registers loaded via manager extensions.', 'stagekitwp-theme' ) . '</p>';
                            }
                            ?>
                        </div>
                    </section>
                <?php endif; ?>

            </main>

            <aside class="show-sidebar-booking" style="flex: 0 1 350px; width: 100%;">
                <div class="sticky-booking-box" style="position: sticky; top: 30px; background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
                    
                    <h2 style="margin-top: 0; font-size: 1.2rem; text-transform: uppercase; letter-spacing: 0.5px; color: #111111; margin-bottom: 20px;">
                        🗓️ <?php _e( 'Performance Information', 'stagekitwp-theme' ); ?>
                    </h2>

                    <ul class="show-info-meta-list" style="list-style: none; padding: 0; margin: 0 0 30px 0; font-size: 1rem; color: #495057;">
                        <li style="padding: 10px 0; border-bottom: 1px dashed #dee2e6;">
                            <strong><?php _e( 'Run Dates:', 'stagekitwp-theme' ); ?></strong> <br>
                            <?php echo esc_html( get_post_meta( get_the_ID(), '_stagekitwp_start_date', true ) ); ?> - <?php echo esc_html( get_post_meta( get_the_ID(), '_stagekitwp_end_date', true ) ); ?>
                        </li>
                        <li style="padding: 10px 0; border-bottom: 1px dashed #dee2e6;">
                            <strong><?php _e( 'Running Time:', 'stagekitwp-theme' ); ?></strong> <br>
                            <?php echo esc_html( get_post_meta( get_the_ID(), '_stagekitwp_run_time', true ) ? get_post_meta( get_the_ID(), '_stagekitwp_run_time', true ) : __( 'Approx. 2 hours with intermission', 'stagekitwp-theme' ) ); ?>
                        </li>
                        <li style="padding: 10px 0; border-bottom: 1px dashed #dee2e6;">
                            <strong><?php _e( 'Venue Stage:', 'stagekitwp-theme' ); ?></strong> <br>
                            <?php echo esc_html( get_post_meta( get_the_ID(), '_stagekitwp_venue_name', true ) ? get_post_meta( get_the_ID(), '_stagekitwp_venue_name', true ) : __( 'Main Marquee Stage', 'stagekitwp-theme' ) ); ?>
                        </li>
                    </ul>

                    <?php 
                    $booking_url = get_post_meta( get_the_ID(), '_stagekitwp_booking_url', true );
                    if ( ! empty( $booking_url ) ) : ?>
                        <a href="<?php echo esc_url( $booking_url ); ?>" class="btn-book-tickets" style="display: block; text-align: center; background-color: #e50914; color: #ffffff; text-decoration: none; padding: 15px 20px; font-weight: 700; text-transform: uppercase; font-size: 1.1rem; border-radius: 4px; letter-spacing: 0.5px; transition: background 0.2s ease-in-out; box-shadow: 0 4px 12px rgba(229,9,20,0.3);">
                            🎟️ <?php _e( 'Book Tickets Online', 'stagekitwp-theme' ); ?>
                        </a>
                    <?php else : ?>
                        <a href="<?php echo esc_url( home_url( '/tickets' ) ); ?>" class="btn-book-tickets" style="display: block; text-align: center; background-color: #111111; color: #ffffff; text-decoration: none; padding: 15px 20px; font-weight: 700; text-transform: uppercase; font-size: 1.1rem; border-radius: 4px; letter-spacing: 0.5px;">
                            🎟️ <?php _e( 'View Ticket Info', 'stagekitwp-theme' ); ?>
                        </a>
                    <?php endif; ?>

                </div>
            </aside>

        </div>
    </div>
<?php endwhile; ?>

<?php
get_footer();