<?php
/**
 * The template for displaying archive pages (categories, tags, dates, authors).
 *
 * @package StageKitWP_Theme
 */

get_header();
?>

<main id="primary" class="site-main">
    <div class="stagekitwp-container" style="max-width:var(--stagekitwp-site-max-width, 1200px);margin:0 auto;padding:60px 20px;">

        <header style="margin-bottom:40px;text-align:center;">
            <?php the_archive_title(
                '<h1 style="font-size:2rem;font-weight:800;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 8px;">',
                '</h1>'
            ); ?>
            <div style="width:50px;height:3px;background:var(--stagekitwp-accent,#e50914);margin:0 auto 16px;"></div>
            <?php the_archive_description(
                '<p style="font-size:1rem;color:var(--stagekitwp-muted-text,#666);max-width:600px;margin:0 auto;">',
                '</p>'
            ); ?>
        </header>

        <?php if ( have_posts() ) : ?>

            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:32px;">
                <?php while ( have_posts() ) : the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'stagekitwp-archive-entry' ); ?>
                             style="border:1px solid #e0e0e0;border-radius:4px;overflow:hidden;background:#fff;">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail( 'medium', array( 'style' => 'width:100%;height:180px;object-fit:cover;display:block;' ) ); ?>
                            </a>
                        <?php endif; ?>
                        <div style="padding:20px;">
                            <h2 style="font-size:1.1rem;font-weight:700;margin:0 0 8px;">
                                <a href="<?php the_permalink(); ?>" style="color:var(--stagekitwp-heading-text,#111);text-decoration:none;">
                                    <?php the_title(); ?>
                                </a>
                            </h2>
                            <p style="font-size:0.9rem;color:var(--stagekitwp-muted-text,#666);margin:0 0 12px;">
                                <?php echo esc_html( get_the_date() ); ?>
                            </p>
                            <?php the_excerpt(); ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <div style="margin-top:48px;text-align:center;">
                <?php the_posts_navigation(); ?>
            </div>

        <?php else : ?>

            <div style="text-align:center;padding:40px 0;">
                <p style="font-size:1.1rem;color:var(--stagekitwp-muted-text,#666);">
                    <?php esc_html_e( 'No posts found in this archive.', 'stagekitwp-theme' ); ?>
                </p>
            </div>

        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>
