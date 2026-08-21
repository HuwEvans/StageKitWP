<?php
/**
 * Template part for displaying the 3-column News & Blog section
 *
 * @package StageKitWP_Theme
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Fetch posts belonging to 'news' or 'blog' slugs
$news_args = array(
    'category_name'  => 'news,blog', 
    'posts_per_page' => 3,
    'post_status'    => 'publish',
    'ignore_sticky_posts' => true
);

$news_query = new WP_Query( $news_args );
?>

<section class="stagekitwp-news-section">
    <div class="stagekitwp-container">
        
        <div class="stagekitwp-section-header">
            <h2><?php esc_html_e( 'Latest News & Blog Updates', 'stagekitwp-theme' ); ?></h2>
            <p><?php esc_html_e( 'Stay up to date with everything happening behind the scenes.', 'stagekitwp-theme' ); ?></p>
        </div>

        <div class="stagekitwp-grid-3-col">
            <?php if ( $news_query->have_posts() ) : ?>
                <?php while ( $news_query->have_posts() ) : $news_query->the_post(); ?>
                    
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'stagekitwp-post-card' ); ?>>
                        
                        <div class="stagekitwp-post-thumbnail">
                            <a href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <?php the_post_thumbnail( 'medium_large', array( 'alt' => esc_attr( get_the_title() ) ) ); ?>
                                <?php else : ?>
                                    <div class="stagekitwp-fallback-image">
                                        <span class="dashicons dashicons-tickets-alt"></span>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </div>

                        <div class="stagekitwp-post-content">
                            <div class="stagekitwp-post-meta">
                                <span class="stagekitwp-post-date"><?php echo esc_html( get_the_date() ); ?></span>
                            </div>
                            
                            <h3 class="stagekitwp-post-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>

                            <div class="stagekitwp-post-excerpt">
                                <?php echo wp_kses_post( wp_trim_words( get_the_excerpt(), 18, '...' ) ); ?>
                            </div>

                            <a href="<?php the_permalink(); ?>" class="stagekitwp-read-more">
                                <?php esc_html_e( 'Read Article →', 'stagekitwp-theme' ); ?>
                            </a>
                        </div>

                    </article>

                <?php endwhile; ?>
                <?php wp_reset_postdata(); // Essential rule: clean up global post data loop after custom queries ?>
            <?php else : ?>
                <div class="stagekitwp-no-posts">
                    <p><?php esc_html_e( 'No recent announcements found. Check back close to opening night!', 'stagekitwp-theme' ); ?></p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>