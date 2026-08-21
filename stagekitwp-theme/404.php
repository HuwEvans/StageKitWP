<?php
/**
 * The template for displaying 404 pages (not found).
 *
 * @package StageKitWP_Theme
 */

get_header();
?>

<main id="primary" class="site-main">
    <div class="stagekitwp-container" style="max-width:var(--stagekitwp-site-max-width, 1200px);margin:0 auto;padding:60px 20px 80px;text-align:center;">

        <!-- Stage Graphic SVG Header -->
        <div style="max-width:480px;margin:0 auto 24px;">
            <svg viewBox="0 0 600 200" xmlns="http://www.w3.org/2000/svg" style="width:100%;height:auto;display:block;">
                <defs>
                    <linearGradient id="curtainGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#7a0000" />
                        <stop offset="20%" stop-color="#b80d0d" />
                        <stop offset="40%" stop-color="#800000" />
                        <stop offset="60%" stop-color="#c62828" />
                        <stop offset="80%" stop-color="#800000" />
                        <stop offset="100%" stop-color="#7a0000" />
                    </linearGradient>
                </defs>

                <!-- Soft Spotlight Cone -->
                <polygon points="300,0 180,200 420,200" fill="#ffffff" opacity="0.04"/>

                <!-- Top Drapes / Valance -->
                <path d="M0,0 L600,0 L600,60 Q525,100 450,60 Q375,100 300,60 Q225,100 150,60 Q75,100 0,60 Z" fill="url(#curtainGrad)" />
                
                <!-- Side Drapes -->
                <path d="M0,0 Q50,100 0,200 L60,200 Q90,100 30,0 Z" fill="#800000" />
                <path d="M600,0 Q550,100 600,200 L540,200 Q510,100 570,0 Z" fill="#800000" />

                <!-- Gold Trim Line -->
                <path d="M0,60 Q75,100 150,60 Q225,100 300,60 Q375,100 450,60 Q525,100 600,60" fill="none" stroke="#fbc02d" stroke-width="3" />

                <!-- Comedy Mask -->
                <g transform="translate(240, 80)">
                    <circle cx="20" cy="20" r="18" fill="#fbc02d"/>
                    <circle cx="13" cy="15" r="2.5" fill="#121212"/>
                    <circle cx="27" cy="15" r="2.5" fill="#121212"/>
                    <path d="M 12 23 Q 20 33 28 23" fill="none" stroke="#121212" stroke-width="2.5" stroke-linecap="round"/>
                </g>

                <!-- Tragedy Mask -->
                <g transform="translate(320, 80)">
                    <circle cx="20" cy="20" r="18" fill="#fbc02d"/>
                    <circle cx="13" cy="15" r="2.5" fill="#121212"/>
                    <circle cx="27" cy="15" r="2.5" fill="#121212"/>
                    <path d="M 12 28 Q 20 20 28 28" fill="none" stroke="#121212" stroke-width="2.5" stroke-linecap="round"/>
                </g>
            </svg>
        </div>

        <h1 style="font-size:5.5rem;font-weight:900;color:var(--stagekitwp-accent,#e50914);margin:0;line-height:1;letter-spacing:-1px;">404</h1>
        
        <h2 style="font-size:2rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin:16px 0 12px;color:#ffffff;">
            <?php esc_html_e( 'Looks Like You Missed Your Cue!', 'stagekitwp-theme' ); ?>
        </h2>
        
        <p style="font-size:1.1rem;color:#d0d0d0;max-width:520px;margin:0 auto 16px;line-height:1.6;">
            <?php esc_html_e( "The page you're looking for seems to have exited stage left—or perhaps it was just a phantom of the opera all along.", 'stagekitwp-theme' ); ?>
        </p>

        <p style="font-size:1rem;color:#a0a0a0;max-width:520px;margin:0 auto 32px;line-height:1.6;">
            <?php esc_html_e( "If you believe this script had a typo or a page went missing in error, let our stage crew know!", 'stagekitwp-theme' ); ?>
        </p>

        <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin-bottom:48px;">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>"
               style="display:inline-block;padding:12px 28px;background:var(--stagekitwp-accent,#e50914);color:#fff;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;text-decoration:none;border-radius:2px;transition:opacity 0.2s;">
                <?php esc_html_e( 'Back to Main Stage', 'stagekitwp-theme' ); ?>
            </a>

            <a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>"
               style="display:inline-block;padding:12px 28px;background:transparent;border:2px solid var(--stagekitwp-accent,#e50914);color:#ffffff;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;text-decoration:none;border-radius:2px;transition:background 0.2s;">
                <?php esc_html_e( 'Contact the Technical Manager', 'stagekitwp-theme' ); ?>
            </a>
        </div>

        <div style="max-width:400px;margin:0 auto;">
            <?php get_search_form(); ?>
        </div>

    </div>
</main>

<?php get_footer(); ?>