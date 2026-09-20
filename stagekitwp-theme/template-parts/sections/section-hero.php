<?php
/**
 * Template part for rendering the Homepage Hero Section with Image/Video switches
 *
 * @package StageKitWP_Theme
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$headline    = get_theme_mod( 'stagekitwp_hero_headline', __( 'Experience Live Performance', 'stagekitwp-theme' ) );
$subheading  = get_theme_mod( 'stagekitwp_hero_subheading', __( 'Discover a thrilling season of award-winning dramas, comedies, and musicals.', 'stagekitwp-theme' ) );
$btn_url     = get_theme_mod( 'stagekitwp_hero_btn_url', home_url( '/tickets' ) );
$btn_placement = get_theme_mod( 'stagekitwp_hero_btn_placement', 'content' );
$btn_pad_x   = absint( get_theme_mod( 'stagekitwp_hero_btn_pad_x', 24 ) );
$btn_pad_y   = absint( get_theme_mod( 'stagekitwp_hero_btn_pad_y', 24 ) );
$btn_safe_area = get_theme_mod( 'stagekitwp_hero_btn_safe_area', true );
$media_type_saved = get_theme_mod( 'stagekitwp_hero_media_type', null );
$uploaded_img = get_theme_mod( 'stagekitwp_hero_bg_image', '' );
$uploaded_vid = get_theme_mod( 'stagekitwp_hero_bg_video', '' );
$uploaded_mobile_vid = get_theme_mod( 'stagekitwp_hero_bg_video_mobile', '' );
$overlay_color = get_theme_mod( 'stagekitwp_hero_overlay_color', '#000000' );
$overlay_opacity = absint( get_theme_mod( 'stagekitwp_hero_overlay_opacity', 60 ) );
if ( $overlay_opacity > 100 ) {
    $overlay_opacity = 100;
}

// The media type control defaults to "image" and is easy to leave untouched
// after uploading a video directly into the video control. When it was never
// explicitly saved, infer it from what was actually uploaded instead of
// silently discarding the video.
if ( null === $media_type_saved ) {
    $media_type = ( ! empty( $uploaded_vid ) && empty( $uploaded_img ) ) ? 'video' : 'image';
} else {
    $media_type = $media_type_saved;
}

// Set a default theme image fallback if the administrator hasn't uploaded an image yet
$final_bg_image = !empty( $uploaded_img ) ? $uploaded_img : get_template_directory_uri() . '/assets/images/hero-bg.jpg';
$hero_classes = array( 'stagekitwp-hero-banner', 'media-type-' . $media_type );

if ( $btn_safe_area ) {
    $hero_classes[] = 'stagekitwp-hero-safe-area-enabled';
}

$hero_style = sprintf(
    '--stagekitwp-hero-cta-pad-x:%1$dpx; --stagekitwp-hero-cta-pad-y:%2$dpx; --stagekitwp-hero-overlay-color:%3$s; --stagekitwp-hero-overlay-opacity:%4$s;',
    $btn_pad_x,
    $btn_pad_y,
    esc_attr( $overlay_color ),
    esc_attr( number_format( $overlay_opacity / 100, 2, '.', '' ) )
);
?>

<section class="<?php echo esc_attr( implode( ' ', $hero_classes ) ); ?>" style="<?php echo esc_attr( $hero_style ); ?>">
    
    <?php if ( 'image' === $media_type && ! empty( $final_bg_image ) ) : ?>
        <div class="stagekitwp-hero-image-wrapper">
            <img
                src="<?php echo esc_url( $final_bg_image ); ?>"
                alt="<?php echo esc_attr( $headline ); ?>"
                class="stagekitwp-hero-image"
                loading="lazy"
                decoding="async"
            />
        </div>
    <?php endif; ?>

    <?php if ( 'video' === $media_type && ! empty( $uploaded_vid ) ) : ?>
        <div class="stagekitwp-video-wrapper">
            <video
                autoplay
                muted
                loop
                playsinline
                preload="metadata"
                class="stagekitwp-hero-video"
                data-desktop-src="<?php echo esc_url( $uploaded_vid ); ?>"
                data-mobile-src="<?php echo esc_url( $uploaded_mobile_vid ); ?>"
            >
                <source src="<?php echo esc_url( $uploaded_vid ); ?>" type="video/mp4">
            </video>
        </div>
    <?php endif; ?>

    <div class="stagekitwp-hero-overlay"></div>

    <div class="stagekitwp-container stagekitwp-hero-container">
        <div class="stagekitwp-hero-content">
            
            <h1 class="stagekitwp-hero-title">
                <?php echo esc_html( $headline ); ?>
            </h1>
            
            <p class="stagekitwp-hero-subtitle">
                <?php echo esc_html( $subheading ); ?>
            </p>

        </div>
    </div>

    <div class="stagekitwp-hero-actions stagekitwp-hero-actions-<?php echo esc_attr( $btn_placement ); ?>">
        <a href="<?php echo esc_url( $btn_url ); ?>" class="stagekitwp-hero-btn">
            <?php esc_html_e( 'Book Tickets Now', 'stagekitwp-theme' ); ?>
        </a>
    </div>
</section>

<style type="text/css">
.stagekitwp-hero-banner {
    --stagekitwp-hero-cta-pad-x: 24px;
    --stagekitwp-hero-cta-pad-y: 24px;
    --stagekitwp-hero-safe-text-width: min( 800px, calc( 100% - var(--stagekitwp-hero-cta-pad-x) - var(--stagekitwp-hero-cta-pad-x) - 240px ) );
    --stagekitwp-hero-safe-gap: 24px;
    position: relative;
    width: 100%;
    min-height: 550px;
    padding: 140px 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    color: #ffffff;
    text-align: center;
}

/* Handling for standard Image backgrounds */
.stagekitwp-hero-banner.media-type-image {
    background-color: #111111;
}

.stagekitwp-hero-image-wrapper {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.stagekitwp-hero-image {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    object-position: center;
}

.stagekitwp-hero-banner.media-type-video {
    background: #000000;
}

/* absolute centering constraints for background loops */
.stagekitwp-video-wrapper {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}
.stagekitwp-hero-video {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: contain;
    object-position: center;
}

/* Translucent sheet over the media so the text is readable */
.stagekitwp-hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: var(--stagekitwp-hero-overlay-color, #000000);
    opacity: var(--stagekitwp-hero-overlay-opacity, 0.6);
    z-index: 2;
}

.stagekitwp-hero-container {
    position: relative;
    z-index: 3; /* Places textual layer cleanly on top of the elements */
}

.stagekitwp-hero-content {
    max-width: 800px;
    margin: 0 auto;
}

.stagekitwp-hero-title {
    font-size: 3.5rem;
    font-weight: 800;
    text-transform: uppercase;
    margin-bottom: 20px;
    letter-spacing: 1px;
    line-height: 1.1;
}

.stagekitwp-hero-subtitle {
    font-size: 1.3rem;
    line-height: 1.6;
    color: #dddddd;
    margin-bottom: 35px;
    font-weight: 300;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
}

.stagekitwp-hero-actions {
    display: flex;
    gap: 12px;
}

.stagekitwp-hero-actions-content {
    position: relative;
    z-index: 4;
    justify-content: center;
    margin-top: 35px;
}

.stagekitwp-hero-actions-top-left,
.stagekitwp-hero-actions-top-center,
.stagekitwp-hero-actions-top-right,
.stagekitwp-hero-actions-center-left,
.stagekitwp-hero-actions-center,
.stagekitwp-hero-actions-center-right,
.stagekitwp-hero-actions-bottom-left,
.stagekitwp-hero-actions-bottom-center,
.stagekitwp-hero-actions-bottom-right {
    position: absolute;
    z-index: 4;
    max-width: calc(100% - var(--stagekitwp-hero-cta-pad-x) - var(--stagekitwp-hero-cta-pad-x));
}

.stagekitwp-hero-actions-top-left {
    top: var(--stagekitwp-hero-cta-pad-y);
    left: var(--stagekitwp-hero-cta-pad-x);
}

.stagekitwp-hero-actions-top-center {
    top: var(--stagekitwp-hero-cta-pad-y);
    left: 50%;
    transform: translateX( -50% );
}

.stagekitwp-hero-actions-top-right {
    top: var(--stagekitwp-hero-cta-pad-y);
    right: var(--stagekitwp-hero-cta-pad-x);
}

.stagekitwp-hero-actions-center-left {
    top: 50%;
    left: var(--stagekitwp-hero-cta-pad-x);
    transform: translateY( -50% );
}

.stagekitwp-hero-actions-center {
    top: 50%;
    left: 50%;
    transform: translate( -50%, -50% );
}

.stagekitwp-hero-actions-center-right {
    top: 50%;
    right: var(--stagekitwp-hero-cta-pad-x);
    transform: translateY( -50% );
}

.stagekitwp-hero-actions-bottom-left {
    bottom: var(--stagekitwp-hero-cta-pad-y);
    left: var(--stagekitwp-hero-cta-pad-x);
}

.stagekitwp-hero-actions-bottom-center {
    bottom: var(--stagekitwp-hero-cta-pad-y);
    left: 50%;
    transform: translateX( -50% );
}

.stagekitwp-hero-actions-bottom-right {
    bottom: var(--stagekitwp-hero-cta-pad-y);
    right: var(--stagekitwp-hero-cta-pad-x);
}

@media (min-width: 1100px) {
    .stagekitwp-hero-safe-area-enabled .stagekitwp-hero-actions-top-left,
    .stagekitwp-hero-safe-area-enabled .stagekitwp-hero-actions-center-left,
    .stagekitwp-hero-safe-area-enabled .stagekitwp-hero-actions-bottom-left {
        max-width: max( 180px, calc( 50% - ( var(--stagekitwp-hero-safe-text-width) / 2 ) - var(--stagekitwp-hero-cta-pad-x) - var(--stagekitwp-hero-safe-gap) ) );
    }

    .stagekitwp-hero-safe-area-enabled .stagekitwp-hero-actions-top-right,
    .stagekitwp-hero-safe-area-enabled .stagekitwp-hero-actions-center-right,
    .stagekitwp-hero-safe-area-enabled .stagekitwp-hero-actions-bottom-right {
        max-width: max( 180px, calc( 50% - ( var(--stagekitwp-hero-safe-text-width) / 2 ) - var(--stagekitwp-hero-cta-pad-x) - var(--stagekitwp-hero-safe-gap) ) );
    }
}

.stagekitwp-hero-btn {
    display: inline-block;
    background-color: #e50914;
    color: #ffffff;
    text-decoration: none;
    padding: 15px 35px;
    font-weight: 700;
    border-radius: 4px;
    text-transform: uppercase;
    font-size: 0.95rem;
    letter-spacing: 1px;
    transition: background 0.2s ease, transform 0.2s ease;
}

.stagekitwp-hero-btn:hover {
    background-color: #b8070f;
    transform: scale(1.03);
}

@media (max-width: 768px) {
    .stagekitwp-hero-title { font-size: 2.3rem; }
    .stagekitwp-hero-subtitle { font-size: 1.1rem; }
    .stagekitwp-hero-banner { min-height: 450px; padding: 80px 0; }
}
</style>

<?php if ( 'video' === $media_type && ! empty( $uploaded_vid ) ) : ?>
<script type="text/javascript">
( function () {
    var heroVideo = document.querySelector( '.stagekitwp-hero-video' );

    if ( ! heroVideo ) {
        return;
    }

    var source = heroVideo.querySelector( 'source' );

    if ( ! source ) {
        return;
    }

    function syncHeroVideoSource() {
        var desktopSrc = heroVideo.getAttribute( 'data-desktop-src' ) || '';
        var mobileSrc = heroVideo.getAttribute( 'data-mobile-src' ) || '';
        var useMobile = window.matchMedia( '(max-width: 768px)' ).matches && mobileSrc;
        var nextSrc = useMobile ? mobileSrc : desktopSrc;

        if ( ! nextSrc || source.getAttribute( 'src' ) === nextSrc ) {
            return;
        }

        source.setAttribute( 'src', nextSrc );
        heroVideo.load();
    }

    if ( 'IntersectionObserver' in window ) {
        var videoObserver = new IntersectionObserver( function ( entries ) {
            entries.forEach( function ( entry ) {
                if ( entry.isIntersecting ) {
                    syncHeroVideoSource();
                    if ( heroVideo.paused ) {
                        var playPromise = heroVideo.play();
                        if ( playPromise !== undefined ) {
                            playPromise.catch( function () {} );
                        }
                    }
                } else {
                    if ( ! heroVideo.paused ) {
                        heroVideo.pause();
                    }
                }
            } );
        }, { rootMargin: '200px 0px' } );

        videoObserver.observe( heroVideo );
    } else {
        syncHeroVideoSource();
    }

    window.stagekitwpSyncHeroVideoSource = syncHeroVideoSource;
    window.addEventListener( 'resize', syncHeroVideoSource );
}() );
</script>
<?php endif; ?>