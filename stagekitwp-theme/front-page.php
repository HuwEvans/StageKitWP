<?php
/**
 * The template for displaying the main landing page layout.
 *
 * @package StageKitWP_Theme
 */

get_header();

// 1. HERO BANNER CONTENT BLOCK
if ( get_theme_mod( 'stagekitwp_enable_hero', true ) ) {
    get_template_part( 'template-parts/sections/section', 'hero' );
}

// Core activation and CPT registration are separate health checks.
$core_active = defined( 'STAGEKITWP_CORE_VERSION' ) || class_exists( 'StageKitWP_Core' );
$cpts_registered = post_type_exists( 'show' ) && post_type_exists( 'season' );
?>

<div id="primary" class="site-main-homepage" style="background-color: #ffffff; padding: 20px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;">

    <?php if ( ! $core_active ) : ?>
        <div class="stagekitwp-container" style="max-width: var(--stagekitwp-site-max-width, 1200px); margin: 50px auto; padding: 25px; background: #fff5f5; border-left: 4px solid #d63638; border-radius: 4px; color: #1d2327;">
            <p style="margin: 0; font-weight: 700; font-size: 1.1rem;">⚠️ <?php _e( 'StageKitWP Core Plugin Inactive', 'stagekitwp-theme' ); ?></p>
            <p style="margin: 5px 0 0 0; color: #50575e;"><?php _e( 'The StageKitWP Core plugin is not active. Please enable it in the dashboard to display seasonal productions.', 'stagekitwp-theme' ); ?></p>
        </div>
    <?php elseif ( ! $cpts_registered ) : ?>
        <div class="stagekitwp-container" style="max-width: var(--stagekitwp-site-max-width, 1200px); margin: 50px auto; padding: 25px; background: #fff8e5; border-left: 4px solid #dba617; border-radius: 4px; color: #1d2327;">
            <p style="margin: 0; font-weight: 700; font-size: 1.1rem;">⚠️ <?php _e( 'StageKitWP Core Content Types Unavailable', 'stagekitwp-theme' ); ?></p>
            <p style="margin: 5px 0 0 0; color: #50575e;"><?php _e( 'StageKitWP Core is active, but the Show and Season content types are not registered yet.', 'stagekitwp-theme' ); ?></p>
        </div>
    <?php else : ?>

        <?php
        /**
         * Dynamic Rendering Helper for Seasonal Production Grids
         */
        function stagekitwp_render_homepage_season_grid( $title, $meta_key, $badge_text ) {
            // Find the single season ID that has this meta flag checked (true/1)
            $season_lookup = get_posts( array(
                'post_type'      => 'season',
                'posts_per_page' => 1,
                'meta_key'       => $meta_key,
                'meta_value'     => '1',
                'fields'         => 'ids'
            ) );

            $season_id = ! empty( $season_lookup ) ? $season_lookup[0] : 0;
            $slots     = array( 'Fall', 'Winter', 'Spring' ); // Rigid chronological columns
            ?>
            
            <div style="text-align: center; margin-top: 50px; margin-bottom: 40px;">
                <h2 style="font-size: 2rem; text-transform: uppercase; letter-spacing: 0.5px; color: #111111; margin-bottom: 8px; font-weight: 800;">
                    <?php echo esc_html( $title ); ?>
                </h2>
                <div style="width: 50px; height: 3px; background: #e50914; margin: 0 auto 10px;"></div>
                <?php if ( $season_id ) : ?>
                    <p style="font-size: 1.2rem; font-weight: 500; color: #555555; margin: 0; letter-spacing: 0.3px;">
                        <?php echo esc_html( get_the_title( $season_id ) ); ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="stagekitwp-season-3-column-row" style="display: flex; flex-wrap: wrap; gap: 30px; justify-content: center; margin-bottom: 50px;">
                <?php
                foreach ( $slots as $slot_term ) :
                    $show_query = new WP_Query( array(
                        'post_type'      => 'show',
                        'posts_per_page' => 1,
                        'meta_query'     => array(
                            'relation' => 'AND',
                            array(
                                'key'     => '_stagekitwp_show_time_slot',
                                'value'   => $slot_term,
                                'compare' => '='
                            ),
                            array(
                                'key'     => '_stagekitwp_show_season',
                                'value'   => $season_id,
                                'compare' => '='
                            )
                        )
                    ) );
                    ?>
                    <?php
                        $badge_show = get_theme_mod( 'stagekitwp_show_term_badge', true );
                        $badge_pos  = get_theme_mod( 'stagekitwp_term_badge_position', 'top-bar' );
                        $badge_html = '<div class="term-badge stagekitwp-badge-' . esc_attr( $badge_pos ) . '" style="background: #111111; color: #ffffff; padding: 8px 15px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">'
                            . esc_html( $slot_term ) . ' ' . esc_html( $badge_text )
                            . '</div>';
                    ?>
                    <div class="stagekitwp-show-card-column stagekitwp-badge-pos-<?php echo esc_attr( $badge_pos ); ?>" style="flex: 1 1 300px; max-width: 360px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                        <div>
                            <?php if ( $badge_show && $badge_pos === 'top-bar' ) : ?>
                                <?php echo $badge_html; ?>
                            <?php endif; ?>

                            <?php if ( $show_query->have_posts() ) : $show_query->the_post(); 
                                $show_id = get_the_ID();
                                
                                // Process image meta via the plugin utility function
                                $raw_img_meta = get_post_meta( $show_id, '_stagekitwp_show_sm_image', true );
                                $resolved_img = function_exists( 'stagekitwp_get_show_image_url' ) ? stagekitwp_get_show_image_url( $raw_img_meta ) : $raw_img_meta;
                                
                                // Fetch all plugin-specific metadata details
                                $genre       = get_post_meta( $show_id, '_stagekitwp_show_genre', true );
                                $director    = get_post_meta( $show_id, '_stagekitwp_show_director', true );
                                $show_dates  = get_post_meta( $show_id, '_stagekitwp_show_show_dates', true );
                                $synopsis    = get_post_meta( $show_id, '_stagekitwp_show_synopsis', true );
                                $tickets_url = get_post_meta( $show_id, '_stagekitwp_show_tickets_url', true );
                                
                                if ( empty( $synopsis ) ) { $synopsis = get_the_excerpt(); }
                                ?>
                                <div class="show-card-media" style="aspect-ratio: 16/10; background: #e9ecef; overflow: hidden; position: relative;">
                                    <?php if ( ! empty( $resolved_img ) ) : ?>
                                        <img src="<?php echo esc_url( $resolved_img ); ?>" alt="<?php the_title_attribute(); ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                                    <?php else : ?>
                                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #adb5bd; font-size: 2.5rem;">🎭</div>
                                    <?php endif; ?>
                                    <?php if ( $badge_show && $badge_pos === 'over-image' ) : ?>
                                        <?php echo $badge_html; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="show-card-body" style="padding: 20px;">
                                    <?php if ( $badge_show && $badge_pos === 'inside-card' ) : ?>
                                        <?php echo $badge_html; ?>
                                    <?php endif; ?>
                                    <h3 style="margin: 0 0 5px 0; font-size: 1.3rem; font-weight: 700; text-transform: uppercase;">
                                        <a href="<?php the_permalink(); ?>" style="color: #111111; text-decoration: none;"><?php the_title(); ?></a>
                                    </h3>

                                    <div style="margin-bottom: 12px; font-size: 0.8rem; color: #6c757d;">
                                        <?php if ( ! empty( $genre ) ) : ?>
                                            <span style="background: #e9ecef; color: #495057; padding: 2px 8px; border-radius: 4px; font-weight: 600; margin-right: 5px;"><?php echo esc_html( $genre ); ?></span>
                                        <?php endif; ?>
                                        <?php if ( ! empty( $director ) ) : ?>
                                            <span><?php printf( __( 'Dir: %s', 'stagekitwp-theme' ), esc_html( $director ) ); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ( ! empty( $show_dates ) ) : ?>
                                        <div style="margin-bottom: 12px; font-size: 0.85rem; color: #e50914; font-weight: 700;">
                                            📅 <?php echo esc_html( $show_dates ); ?>
                                        </div>
                                    <?php endif; ?>

                                    <p style="margin: 0 0 15px 0; color: #495057; font-size: 0.9rem; line-height: 1.5;"><?php echo wp_trim_words( esc_html( $synopsis ), 18 ); ?></p>
                                    
                                    <?php if ( ! empty( $tickets_url ) ) : ?>
                                        <a href="<?php echo esc_url( $tickets_url ); ?>" target="_blank" class="button" style="display: inline-block; background: #e50914; color: #ffffff; text-decoration: none; padding: 8px 16px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; border-radius: 4px; text-align: center; letter-spacing: 0.5px;"><?php _e( 'Get Tickets', 'stagekitwp-theme' ); ?></a>
                                    <?php else : ?>
                                        <a href="<?php the_permalink(); ?>" class="button" style="display: inline-block; background: #111111; color: #ffffff; text-decoration: none; padding: 8px 16px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; border-radius: 4px; text-align: center; letter-spacing: 0.5px;"><?php _e( 'Show Details', 'stagekitwp-theme' ); ?></a>
                                    <?php endif; ?>
                                </div>
                            <?php else : ?>
                                <div style="padding: 70px 20px; text-align: center; color: #adb5bd; font-style: italic;">
                                    <p style="margin: 0; font-size: 1.8rem;">🎟️</p>
                                    <p style="margin: 5px 0 0 0; font-size: 0.85rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;"><?php printf( __( '%s Slot Open', 'stagekitwp-theme' ), esc_html( $slot_term ) ); ?></p>
                                </div>
                            <?php endif; wp_reset_postdata(); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php
        }
        ?>

        <?php if ( get_theme_mod( 'stagekitwp_enable_current_season', true ) ) : ?>
            <section class="stagekitwp-season-grid-section">
                <div class="stagekitwp-container" style="max-width: var(--stagekitwp-site-max-width, 1200px); margin: 0 auto; padding: 0 20px;">
                    <?php stagekitwp_render_homepage_season_grid( __( 'Now Playing', 'stagekitwp-theme' ), '_stagekitwp_season_is_current', __( 'Production', 'stagekitwp-theme' ) ); ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ( stagekitwp_theme_should_display_upcoming_season() ) : ?>
            <section class="stagekitwp-upcoming-grid-section" style="background: #fafafa; padding: 10px 0; border-top: 1px solid #f0f0f1; border-bottom: 1px solid #f0f0f1;">
                <div class="stagekitwp-container" style="max-width: var(--stagekitwp-site-max-width, 1200px); margin: 0 auto; padding: 0 20px;">
                    <?php stagekitwp_render_homepage_season_grid( __( 'Coming Soon', 'stagekitwp-theme' ), '_stagekitwp_season_is_upcoming', __( 'Preview', 'stagekitwp-theme' ) ); ?>
                </div>
            </section>
        <?php endif; ?>

    <?php endif; ?>

    <?php if ( get_theme_mod( 'stagekitwp_enable_news', true ) ) : ?>
        <section class="stagekitwp-homepage-news-feed" style="padding: 60px 0;">
            <div class="stagekitwp-container" style="max-width: var(--stagekitwp-site-max-width, 1200px); margin: 0 auto; padding: 0 20px;">
                <div style="text-align: center; margin-bottom: 40px;">
                    <h2 style="font-size: 2rem; text-transform: uppercase; letter-spacing: 0.5px; color: #111111; margin-bottom: 8px; font-weight: 800;"><?php _e( 'News & Audition Updates', 'stagekitwp-theme' ); ?></h2>
                    <div style="width: 50px; height: 3px; background: #e50914; margin: 0 auto;"></div>
                </div>

                <?php
                $news_limit = absint( get_theme_mod( 'stagekitwp_news_items_limit', 6 ) );
                if ( $news_limit < 3 ) {
                    $news_limit = 3;
                } elseif ( $news_limit > 10 ) {
                    $news_limit = 10;
                }
                ?>
                <div class="stagekitwp-news-carousel-shell" style="display: flex; align-items: center; gap: 12px; max-width: 100%;">
                    <button type="button" class="stagekitwp-news-carousel-nav stagekitwp-news-carousel-prev" aria-label="<?php esc_attr_e( 'Previous news items', 'stagekitwp-theme' ); ?>" style="flex: 0 0 auto; width: 38px; height: 38px; border-radius: 50%; border: 1px solid #d7dce1; background: #ffffff; color: #111111; font-size: 1.25rem; line-height: 1; cursor: pointer;">&#8249;</button>
                    <div class="stagekitwp-news-carousel-viewport" style="overflow: hidden; width: 100%; min-width: 0;">
                        <div class="stagekitwp-news-carousel-track" style="display: flex; flex-wrap: nowrap; gap: 20px; overflow-x: auto; scroll-behavior: smooth; scroll-snap-type: x mandatory; -ms-overflow-style: none; scrollbar-width: none; padding: 4px 2px 10px;">
                    <?php
                    $news_items_rendered      = 0;
                    $selected_category_slugs  = get_theme_mod( 'stagekitwp_news_post_categories', '' );
                    $include_show_auditions   = get_theme_mod( 'stagekitwp_news_include_auditions', true );

                    if ( ! function_exists( 'stagekitwp_news_first_image_src' ) ) {
                        // Fallback feature image: first <img> found inside the (HTML) audition details.
                        function stagekitwp_news_first_image_src( $html ) {
                            if ( empty( $html ) || ! preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches ) ) {
                                return '';
                            }
                            return esc_url_raw( $matches[1] );
                        }
                    }

                    // ── Sticky audition notices: lead the feed until 3 days after the audition date ──
                    if ( $include_show_auditions && post_type_exists( 'show' ) ) :
                        $audition_cutoff = date( 'Y-m-d', strtotime( '-3 days', current_time( 'timestamp' ) ) );
                        $auditions_query = new WP_Query( array(
                            'post_type'      => 'show',
                            'post_status'    => 'publish',
                            'posts_per_page' => $news_limit,
                            'meta_key'       => '_stagekitwp_show_audition_date',
                            'orderby'        => 'meta_value',
                            'order'          => 'ASC',
                            'meta_query'     => array(
                                array(
                                    'key'     => '_stagekitwp_show_audition_date',
                                    'value'   => $audition_cutoff,
                                    'compare' => '>=',
                                    'type'    => 'DATE',
                                ),
                            ),
                        ) );

                        if ( $auditions_query->have_posts() ) :
                            while ( $auditions_query->have_posts() && $news_items_rendered < $news_limit ) : $auditions_query->the_post();
                                $news_items_rendered++;
                                $show_id        = get_the_ID();
                                $audition_date  = get_post_meta( $show_id, '_stagekitwp_show_audition_date', true );
                                $audition_label = '';
                                if ( ! empty( $audition_date ) ) {
                                    $audition_label = date_i18n( get_option( 'date_format' ), strtotime( $audition_date ) );
                                }

                                $audition_details = get_post_meta( $show_id, '_stagekitwp_show_audition_details', true );
                                if ( empty( $audition_details ) ) {
                                    $audition_details = get_post_meta( $show_id, '_stagekitwp_show_synopsis', true );
                                }

                                // Feature image: prefer an image inside the audition details, then fall back to the show's SM image.
                                $resolved_img = stagekitwp_news_first_image_src( $audition_details );
                                if ( empty( $resolved_img ) ) {
                                    $raw_img_meta  = get_post_meta( $show_id, '_stagekitwp_show_sm_image', true );
                                    $resolved_img  = function_exists( 'stagekitwp_get_show_image_url' ) ? stagekitwp_get_show_image_url( $raw_img_meta ) : $raw_img_meta;
                                }

                                // Link to the Core plugin's configured Auditions Page setting; fall back to the show itself.
                                $audition_page_id  = intval( get_option( 'stagekitwp_auditions_page_id', 0 ) );
                                $audition_link_url = $audition_page_id ? get_permalink( $audition_page_id ) : get_permalink( $show_id );
                                ?>
                                <div style="flex: 0 0 calc((100% - 40px) / 3); min-width: 280px; background: #ffffff; border: 1px solid #e9ecef; border-radius: 6px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.02); scroll-snap-align: start;">
                                    <?php if ( ! empty( $resolved_img ) ) : ?>
                                        <a href="<?php echo esc_url( $audition_link_url ); ?>" style="display: block;">
                                            <div style="aspect-ratio: 16/9; overflow: hidden;">
                                                <img src="<?php echo esc_url( $resolved_img ); ?>" alt="<?php the_title_attribute(); ?>" style="width:100%; height:100%; object-fit:cover;">
                                            </div>
                                        </a>
                                    <?php endif; ?>
                                    <div style="padding: 20px;">
                                        <span style="font-size: 0.75rem; text-transform: uppercase; color: #e50914; font-weight: 700;"><?php echo esc_html__( 'Audition', 'stagekitwp-theme' ); ?><?php echo $audition_label ? ' • ' . esc_html( $audition_label ) : ''; ?></span>
                                        <h3 style="margin: 5px 0 10px 0; font-size: 1.15rem; font-weight: 700;">
                                            <a href="<?php echo esc_url( $audition_link_url ); ?>" style="color: #111111; text-decoration: none;"><?php the_title(); ?></a>
                                        </h3>
                                        <p style="margin: 0; color: #6c757d; font-size: 0.9rem; line-height: 1.5;"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $audition_details ), 15 ) ); ?></p>
                                    </div>
                                </div>
                            <?php endwhile;
                        endif;
                        wp_reset_postdata();
                    endif;

                    // ── Regular news posts fill any slots remaining after sticky auditions ──
                    $news_remaining_slots = $news_limit - $news_items_rendered;
                    if ( $news_remaining_slots > 0 ) :
                        $news_query_args = array(
                            'post_type'      => 'post',
                            'posts_per_page' => $news_remaining_slots,
                            'ignore_sticky_posts' => true,
                        );

                        if ( ! empty( $selected_category_slugs ) ) {
                            $category_slugs = array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', $selected_category_slugs ) ) ) );
                            if ( ! empty( $category_slugs ) ) {
                                $news_query_args['category_name'] = implode( ',', $category_slugs );
                            }
                        }

                        $news_query = new WP_Query( $news_query_args );

                        if ( $news_query->have_posts() ) :
                            while ( $news_query->have_posts() && $news_items_rendered < $news_limit ) : $news_query->the_post();
                                $news_items_rendered++; ?>
                                <div style="flex: 0 0 calc((100% - 40px) / 3); min-width: 280px; background: #ffffff; border: 1px solid #e9ecef; border-radius: 6px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.02); scroll-snap-align: start;">
                                    <?php if ( has_post_thumbnail() ) : ?>
                                        <div style="aspect-ratio: 16/9; overflow: hidden;">
                                            <?php the_post_thumbnail( 'medium_large', array( 'style' => 'width:100%; height:100%; object-fit:cover;' ) ); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div style="padding: 20px;">
                                        <span style="font-size: 0.75rem; text-transform: uppercase; color: #e50914; font-weight: 700;"><?php echo esc_html( get_the_date() ); ?></span>
                                        <h3 style="margin: 5px 0 10px 0; font-size: 1.15rem; font-weight: 700;">
                                            <a href="<?php the_permalink(); ?>" style="color: #111111; text-decoration: none;"><?php the_title(); ?></a>
                                        </h3>
                                        <p style="margin: 0; color: #6c757d; font-size: 0.9rem; line-height: 1.5;"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 15 ) ); ?></p>
                                    </div>
                                </div>
                            <?php endwhile;
                        endif;
                        wp_reset_postdata();
                    endif;

                    if ( 0 === $news_items_rendered ) : ?>
                        <p style="color: #6c757d; font-style: italic; text-align: center; width: 100%;"><?php _e( 'No recent announcements or audition notices posted.', 'stagekitwp-theme' ); ?></p>
                    <?php endif; ?>
                        </div>
                    </div>
                    <button type="button" class="stagekitwp-news-carousel-nav stagekitwp-news-carousel-next" aria-label="<?php esc_attr_e( 'Next news items', 'stagekitwp-theme' ); ?>" style="flex: 0 0 auto; width: 38px; height: 38px; border-radius: 50%; border: 1px solid #d7dce1; background: #ffffff; color: #111111; font-size: 1.25rem; line-height: 1; cursor: pointer;">&#8250;</button>
                </div>

                <style type="text/css">
                    .stagekitwp-news-carousel-track::-webkit-scrollbar { display: none; }
                    @media (max-width: 1024px) {
                        .stagekitwp-news-carousel-track > div { flex-basis: calc((100% - 20px) / 2) !important; }
                    }
                    @media (max-width: 680px) {
                        .stagekitwp-news-carousel-track > div { flex-basis: 100% !important; min-width: 100% !important; }
                        .stagekitwp-news-carousel-nav { width: 34px !important; height: 34px !important; }
                    }
                </style>

                <script type="text/javascript">
                    document.addEventListener('DOMContentLoaded', function() {
                        var shell = document.querySelector('.stagekitwp-news-carousel-shell');
                        if (!shell) { return; }

                        var track = shell.querySelector('.stagekitwp-news-carousel-track');
                        var prev  = shell.querySelector('.stagekitwp-news-carousel-prev');
                        var next  = shell.querySelector('.stagekitwp-news-carousel-next');
                        if (!track || !prev || !next) { return; }

                        var scrollAmount = function() {
                            return Math.max(280, Math.round(track.clientWidth * 0.9));
                        };

                        prev.addEventListener('click', function() {
                            track.scrollBy({ left: -scrollAmount(), behavior: 'smooth' });
                        });
                        next.addEventListener('click', function() {
                            track.scrollBy({ left: scrollAmount(), behavior: 'smooth' });
                        });
                    });
                </script>

                <?php
                $all_news_page_id = absint( get_theme_mod( 'stagekitwp_news_all_page_id', 0 ) );
                if ( $all_news_page_id > 0 ) :
                    $all_news_url = get_permalink( $all_news_page_id );
                    if ( $all_news_url ) :
                        ?>
                        <div style="text-align: center; margin-top: 28px;">
                            <a href="<?php echo esc_url( $all_news_url ); ?>" class="button" style="display: inline-block; background: #111111; color: #ffffff; text-decoration: none; padding: 10px 18px; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; border-radius: 4px; letter-spacing: 0.5px;">
                                <?php esc_html_e( 'View All News', 'stagekitwp-theme' ); ?>
                            </a>
                        </div>
                        <?php
                    endif;
                endif;
                ?>
            </div>
        </section>
    <?php endif; ?>

</div>

</div>

<?php
// 5. TESTIMONIALS CONTENT BLOCK - Passing 'true' explicitly ensures fallback loading on fresh installations
if ( get_theme_mod( 'stagekitwp_enable_testimonials', true ) ) {
    get_template_part( 'template-parts/sections/section', 'testimonials' );
}

get_footer();