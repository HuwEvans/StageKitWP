<?php
/**
 * The template for displaying search results pages.
 *
 * @package StageKitWP_Theme
 */

get_header();
?>

<main id="primary" class="site-main">
    <style>
        .stagekitwp-search-wrap {
            max-width: var(--stagekitwp-site-max-width, 1200px);
            margin: 0 auto;
            padding: 60px 20px;
        }
        .stagekitwp-search-header {
            margin-bottom: 40px;
            text-align: center;
        }
        .stagekitwp-search-title {
            font-size: 2rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 8px;
            color: var(--stagekitwp-heading-text, #111111);
        }
        .stagekitwp-search-divider {
            width: 50px;
            height: 3px;
            background: var(--stagekitwp-accent, #e50914);
            margin: 0 auto 16px;
        }
        .stagekitwp-search-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 32px;
        }
        .stagekitwp-search-result {
            border: 1px solid color-mix(in srgb, var(--stagekitwp-heading-text, #111111) 16%, transparent);
            border-radius: 14px;
            overflow: hidden;
            background: var(--stagekitwp-surface-bg, #ffffff);
            color: var(--stagekitwp-body-text, #333333);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }
        .stagekitwp-search-result:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.14);
            border-color: color-mix(in srgb, var(--stagekitwp-primary, #e50914) 35%, transparent);
        }
        .stagekitwp-search-result-thumb {
            width: 100%;
            height: 180px;
            object-fit: cover;
            display: block;
            background: color-mix(in srgb, var(--stagekitwp-heading-text, #111111) 10%, var(--stagekitwp-surface-bg, #ffffff));
        }
        .stagekitwp-search-result-body {
            padding: 20px;
        }
        .stagekitwp-search-result-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0 0 8px;
        }
        .stagekitwp-search-result-title a {
            color: var(--stagekitwp-heading-text, #111111);
            text-decoration: none;
        }
        .stagekitwp-search-result-title a:hover {
            color: var(--stagekitwp-link-hover, var(--stagekitwp-primary, #e50914));
        }
        .stagekitwp-search-meta {
            font-size: 0.9rem;
            color: var(--stagekitwp-muted-text, #666666);
            margin: 0 0 12px;
        }
        .stagekitwp-search-relevance {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            margin: 0 0 14px;
            font-size: 0.82rem;
            color: var(--stagekitwp-heading-text, #111111);
            background: color-mix(in srgb, var(--stagekitwp-primary, #e50914) 12%, var(--stagekitwp-surface-bg, #ffffff));
            border: 1px solid color-mix(in srgb, var(--stagekitwp-primary, #e50914) 28%, transparent);
        }
        .stagekitwp-search-result-body .entry-summary,
        .stagekitwp-search-result-body .entry-summary p,
        .stagekitwp-search-result-body p {
            color: var(--stagekitwp-body-text, #333333);
        }
        .stagekitwp-search-empty {
            text-align: center;
            padding: 40px 0;
        }
        .stagekitwp-search-empty p {
            font-size: 1.1rem;
            color: var(--stagekitwp-muted-text, #666666);
            margin-bottom: 24px;
        }
    </style>

    <div class="stagekitwp-container stagekitwp-search-wrap">

        <header class="stagekitwp-search-header">
            <h1 class="stagekitwp-search-title">
                <?php
                printf(
                    /* translators: %s: search query */
                    esc_html__( 'Search Results for: %s', 'stagekitwp-theme' ),
                    '<span style="color:var(--stagekitwp-accent,#e50914);">' . get_search_query() . '</span>'
                );
                ?>
            </h1>
            <div class="stagekitwp-search-divider"></div>
        </header>

        <?php if ( have_posts() ) : ?>

            <div class="stagekitwp-search-grid">
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php
                    $relevance_score = isset( $post->stagekitwp_search_relevance ) ? (int) $post->stagekitwp_search_relevance : 0;
                    if ( $relevance_score >= 220 ) {
                        $relevance_label = __( 'Very High', 'stagekitwp-theme' );
                    } elseif ( $relevance_score >= 120 ) {
                        $relevance_label = __( 'High', 'stagekitwp-theme' );
                    } elseif ( $relevance_score >= 60 ) {
                        $relevance_label = __( 'Medium', 'stagekitwp-theme' );
                    } else {
                        $relevance_label = __( 'Low', 'stagekitwp-theme' );
                    }
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'stagekitwp-search-result' ); ?>>
                        <?php if ( has_post_thumbnail() ) : ?>
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail( 'medium', array( 'class' => 'stagekitwp-search-result-thumb' ) ); ?>
                            </a>
                        <?php endif; ?>
                        <div class="stagekitwp-search-result-body">
                            <h2 class="stagekitwp-search-result-title">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h2>
                            <p class="stagekitwp-search-meta">
                                <?php echo esc_html( get_the_date() ); ?>
                            </p>
                            <p class="stagekitwp-search-relevance">
                                <?php
                                printf(
                                    /* translators: 1: relevance label 2: relevance score */
                                    esc_html__( 'Relevance: %1$s (%2$d)', 'stagekitwp-theme' ),
                                    esc_html( $relevance_label ),
                                    (int) $relevance_score
                                );
                                ?>
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

            <div class="stagekitwp-search-empty">
                <p>
                    <?php esc_html_e( 'No results found. Try a different search term.', 'stagekitwp-theme' ); ?>
                </p>
                <?php get_search_form(); ?>
            </div>

        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>
