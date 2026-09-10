<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package StageKitWP_Theme
 */

get_header();
?>

<main id="primary" class="site-main">
    <div class="stagekitwp-container" style="max-width: var(--stagekitwp-site-max-width, 1200px); margin: 40px auto; padding: 0 20px;">

        <?php if ( have_posts() ) : ?>

            <?php if ( ! is_home() ) : ?>
                <header class="page-header" style="margin-bottom: 30px;">
                    <h1 class="page-title" style="font-size: 2rem; text-transform: uppercase; color: #111111;">
                        <?php the_archive_title(); ?>
                    </h1>
                </header>
            <?php endif; ?>

            <div class="stagekitwp-posts-loop-grid" style="display: flex; flex-direction: column; gap: 40px;">
                <?php
                while ( have_posts() ) :
                    the_post();
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?> style="border-bottom: 1px solid #f0f0f1; padding-bottom: 30px;">
                        <header class="entry-header" style="margin-bottom: 15px;">
                            <?php
                            if ( is_singular() ) :
                                if ( stagekitwp_theme_display_title() ) :
                                    the_title( '<h1 class="entry-title" style="font-size: 2.25rem; margin: 0 0 10px 0; color: #111111;">', '</h1>' );
                                endif;
                            else :
                                the_title( '<h2 class="entry-title" style="font-size: 1.75rem; margin: 0 0 10px 0; text-transform: uppercase;"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark" style="color: #111111; text-decoration: none;">', '</a></h2>' );
                            endif;
                            ?>

                            <div class="entry-meta" style="font-size: 0.85rem; color: #888888; margin-bottom: 15px;">
                                <span class="posted-on" style="margin-right: 15px;">📅 <?php echo esc_html( get_the_date() ); ?></span>
                                <span class="posted-by">✍️ <?php the_author(); ?></span>
                            </div>
                        </header>

                        <?php if ( has_post_thumbnail() ) : ?>
                            <div class="post-thumbnail" style="margin-bottom: 20px; max-width: 100%; height: auto; overflow: hidden; border-radius: 4px;">
                                <?php the_post_thumbnail( 'large', array( 'style' => 'width: 100%; height: auto; display: block;' ) ); ?>
                            </div>
                        <?php endif; ?>

                        <div class="entry-content" style="line-height: 1.6; color: #333333; font-size: 1rem;">
                            <?php
                            if ( is_single() ) {
                                the_content();
                            } else {
                                the_excerpt();
                            }
                            ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <div class="stagekitwp-pagination" style="margin-top: 40px; display: flex; gap: 10px;">
                <?php
                the_posts_pagination( array(
                    'mid_size'  => 2,
                    'prev_text' => __( '← Previous', 'stagekitwp-theme' ),
                    'next_text' => __( 'Next →', 'stagekitwp-theme' ),
                ) );
                ?>
            </div>

        <?php else : ?>

            <section class="no-results not-found" style="text-align: center; padding: 60px 20px;">
                <header class="page-header">
                    <h1 class="page-title" style="font-size: 2rem; color: #111111; margin-bottom: 15px;"><?php esc_html_e( 'Nothing Found', 'stagekitwp-theme' ); ?></h1>
                </header>
                <div class="page-content" style="color: #666666;">
                    <p><?php esc_html_e( 'It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', 'stagekitwp-theme' ); ?></p>
                    <div style="margin-top: 20px;"><?php get_search_form(); ?></div>
                </div>
            </section>

        <?php endif; ?>

    </div>
</main>

<?php
get_footer();