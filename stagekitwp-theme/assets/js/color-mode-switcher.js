/**
 * StageKitWP Theme — Frontend Light / Dark Mode Switcher
 *
 * Because many theme elements have hardcoded inline style attributes (set in PHP),
 * CSS !important cannot override them. This script directly patches element.style
 * on every dark-mode-affected element when toggling, then restores originals on
 * light mode. Originals are cached on first run so a full refresh restores them.
 */
( function () {
	'use strict';

	var STORAGE_KEY = 'stagekitwp_color_mode';
	var html        = document.documentElement;

	// ── Read a CSS variable from the html element (after stagekitwp-dark-mode class is set) ──
	function cssVar( name ) {
		return getComputedStyle( html ).getPropertyValue( name ).trim();
	}

	// Build the DARK map from live CSS variables so Customizer dark settings
	// are always respected — no hardcoded hex here.
	function buildDarkMap() {
		var baseBg    = cssVar( '--stagekitwp-base-bg'    ) || '#121212';
		var surfaceBg = cssVar( '--stagekitwp-surface-bg' ) || '#1e1e1e';
		var bodyText  = cssVar( '--stagekitwp-body-text'  ) || '#e0e0e0';
		var headText  = cssVar( '--stagekitwp-heading-text') || '#ffffff';
		var mutedText = cssVar( '--stagekitwp-muted-text' ) || '#9e9e9e';
		var headerBg  = cssVar( '--stagekitwp-header-bg'  ) || '#1e1e1e';
		var primary   = cssVar( '--stagekitwp-primary'    ) || '#e50914';
		return {
			header:          { bg: headerBg },
			canvas:          { bg: baseBg,    color: bodyText  },
			card:            { bg: surfaceBg, color: bodyText,  border: '1px solid color-mix(in srgb,' + bodyText + ' 20%,transparent)' },
			cardBodyText:    { color: mutedText },
			upcomingSection: { bg: surfaceBg },
			newsCard:        { bg: surfaceBg },
			heading:         { color: headText  },
			headingLink:     { color: headText  },
			mutedText:       { color: mutedText },
			badgeLight:      { bg: 'color-mix(in srgb,' + surfaceBg + ' 60%,' + bodyText + ' 40%)', color: mutedText },
			termBadge:       { bg: primary, color: '#ffffff' },
			testimonialCard: { bg: surfaceBg, color: bodyText, border: '1px solid color-mix(in srgb,' + bodyText + ' 20%,transparent)' },
		};
	}

	var DARK = {}; // populated in applyDark() after class is applied

	// Cache of original inline styles, keyed by a unique element index
	var originals = {};
	var indexed   = false;

	function indexElements() {
		if ( indexed ) { return; }
		indexed = true;
		document.querySelectorAll( '[data-stagekitwp-idx]' ).forEach( function ( el ) {
			originals[ el.dataset.stagekitwpIdx ] = {
				background:      el.style.background      || '',
				backgroundColor: el.style.backgroundColor || '',
				color:           el.style.color           || '',
				border:          el.style.border          || '',
				borderTop:       el.style.borderTop       || '',
				borderBottom:    el.style.borderBottom    || '',
			};
		} );
	}

	function tagEl( el, idx ) {
		el.setAttribute( 'data-stagekitwp-idx', idx );
		originals[ idx ] = {
			background:      el.style.background      || '',
			backgroundColor: el.style.backgroundColor || '',
			color:           el.style.color           || '',
			border:          el.style.border          || '',
			borderTop:       el.style.borderTop       || '',
			borderBottom:    el.style.borderBottom    || '',
			vars:            {}, // CSS custom property names patched onto this el
		};
	}

	var elIdx = 0;

	function patchEl( el, props ) {
		if ( ! el.dataset.stagekitwpIdx ) { tagEl( el, 'tm' + ( elIdx++ ) ); }
		if ( props.bg !== undefined )          { el.style.background      = props.bg; el.style.backgroundColor = props.bg; }
		if ( props.color !== undefined )       { el.style.color           = props.color; }
		if ( props.border !== undefined )      { el.style.border          = props.border; }
		if ( props.borderTop !== undefined )   { el.style.borderTop       = props.borderTop; }
		if ( props.borderBottom !== undefined ){ el.style.borderBottom    = props.borderBottom; }
		// CSS custom properties — needed for var()-based architectures
		if ( props.vars ) {
			if ( ! el.dataset.stagekitwpIdx ) { tagEl( el, 'tm' + ( elIdx++ ) ); } // ensure tagged
			var oIdx = el.dataset.stagekitwpIdx;
			Object.keys( props.vars ).forEach( function( k ) {
				el.style.setProperty( k, props.vars[ k ], 'important' );
				if ( originals[ oIdx ] ) { originals[ oIdx ].vars[ k ] = true; }
			} );
		}
	}

	function restoreEl( el ) {
		var idx = el.dataset.stagekitwpIdx;
		if ( ! idx || ! originals[ idx ] ) { return; }
		var o = originals[ idx ];
		// Remove any !important priority set via setProperty before restoring
		el.style.removeProperty( 'background' );
		el.style.removeProperty( 'background-color' );
		el.style.background      = o.background;
		el.style.backgroundColor = o.backgroundColor;
		el.style.color           = o.color;
		el.style.border          = o.border;
		el.style.borderTop       = o.borderTop;
		el.style.borderBottom    = o.borderBottom;
		// Restore CSS custom properties
		if ( o.vars ) {
			Object.keys( o.vars ).forEach( function( k ) {
				el.style.removeProperty( k );
			} );
		}
	}

	// ── Logo swap helper ─────────────────────────────────────────────────────
	function applyLogoMode( mode ) {
		var lightLogo = document.querySelector( '.stagekitwp-logo-light' );
		var darkLogo  = document.querySelector( '.stagekitwp-logo-dark-img' );

		if ( ! lightLogo ) { return; }

		if ( darkLogo && darkLogo.src && darkLogo.src !== window.location.href ) {
			// Separate dark logo uploaded — swap visibility
			if ( mode === 'dark' ) {
				lightLogo.style.display = 'none';
				darkLogo.style.display  = 'block';
			} else {
				lightLogo.style.display = 'block';
				darkLogo.style.display  = 'none';
			}
		} else {
			// No dark logo — apply CSS auto-invert class as fallback
			lightLogo.style.display = 'block';
			if ( mode === 'dark' ) {
				lightLogo.classList.add( 'stagekitwp-logo-no-dark' );
			} else {
				lightLogo.classList.remove( 'stagekitwp-logo-no-dark' );
				lightLogo.style.filter = '';
			}
		}
	}

	// Returns true if the element is inside a zone that is always dark or
	// has its own dark overlay (footer, notification bar, hero with media,
	// testimonials section). These zones must never be broadly patched —
	// only their specific child elements (e.g. slide cards) get targeted patches.
	function inAlwaysDarkZone( el ) {
		// Footer and notification bar are always dark regardless of mode.
		if ( el.closest( '.site-footer, .stagekitwp-top-notification-bar' ) ) { return true; }
		// Hero banner with a video or image already has a dark overlay — leave text alone.
		var hero = el.closest( '.stagekitwp-hero-banner' );
		if ( hero && ( hero.classList.contains( 'media-type-video' ) || hero.classList.contains( 'media-type-image' ) ) ) { return true; }
		// Testimonials section has a dark background and white text already —
		// only the individual slide cards (patched explicitly below) need changing.
		if ( el.closest( '.stagekitwp-testimonials-section' ) && ! el.classList.contains( 'stagekitwp-testimonial' ) ) { return true; }
		return false;
	}

	// Like querySelectorAll but silently skips always-dark-zone matches.
	function queryContent( selector ) {
		var results = [];
		document.querySelectorAll( selector ).forEach( function ( el ) {
			if ( ! inAlwaysDarkZone( el ) ) { results.push( el ); }
		} );
		return results;
	}

	function applyDark() {
		// Rebuild DARK map from live CSS vars (class already on html at this point)
		DARK = buildDarkMap();

		// Site header — may have inline style="background: #xxxxxx" (shorthand).
		// We must set BOTH background and backgroundColor inline to win,
		// and also clear any conflicting border-bottom that may leak the light colour.
		var headerBg = DARK.header.bg;
		document.querySelectorAll( '.site-header, #masthead' ).forEach( function ( el ) {
			if ( ! el.dataset.stagekitwpIdx ) { tagEl( el, 'tm' + ( elIdx++ ) ); }
			el.style.setProperty( 'background',      headerBg, 'important' );
			el.style.setProperty( 'background-color', headerBg, 'important' );
		} );

		// Canvas / main wrapper
		queryContent( '#primary, .site-main-homepage, .site-content, #page' ).forEach( function ( el ) {
			patchEl( el, { bg: DARK.canvas.bg, color: DARK.canvas.color } );
		} );

		// Show cards
		queryContent( '.stagekitwp-show-card-column' ).forEach( function ( el ) {
			patchEl( el, { bg: DARK.card.bg, border: DARK.card.border, color: DARK.card.color } );
		} );

		// Term badges — after cards so card bg doesn't bleed in
		queryContent( '.term-badge' ).forEach( function ( el ) {
			patchEl( el, { bg: DARK.termBadge.bg, color: DARK.termBadge.color } );
		} );

		// Card body text paragraphs & meta divs
		queryContent( '.show-card-body p, .show-card-body > div' ).forEach( function ( el ) {
			patchEl( el, { color: DARK.cardBodyText.color } );
		} );

		// Card title links
		queryContent( '.show-card-body h3 a' ).forEach( function ( el ) {
			patchEl( el, { color: DARK.headingLink.color } );
		} );

		// Genre/director badge spans with a light background
		queryContent( '.show-card-body span' ).forEach( function ( el ) {
			var bg = el.style.backgroundColor || el.style.background || '';
			if ( bg && bg !== 'transparent' && bg !== '' ) {
				patchEl( el, { bg: DARK.badgeLight.bg, color: DARK.badgeLight.color } );
			}
		} );

		// Upcoming section
		queryContent( '.stagekitwp-upcoming-grid-section' ).forEach( function ( el ) {
			patchEl( el, { bg: DARK.upcomingSection.bg, borderTop: DARK.upcomingSection.borderTop, borderBottom: DARK.upcomingSection.borderBottom } );
		} );

		// Season grid / news headings
		queryContent( '.stagekitwp-season-grid-section h2, .stagekitwp-upcoming-grid-section h2, .stagekitwp-homepage-news-feed h2' ).forEach( function ( el ) {
			patchEl( el, { color: DARK.heading.color } );
		} );

		// News section wrapper
		queryContent( '.stagekitwp-homepage-news-feed' ).forEach( function ( el ) {
			patchEl( el, { bg: DARK.canvas.bg } );
		} );

		// News cards
		queryContent( '.stagekitwp-homepage-news-feed .stagekitwp-container > div > div' ).forEach( function ( el ) {
			if ( el.style.background || el.style.backgroundColor ) {
				patchEl( el, { bg: DARK.newsCard.bg, border: DARK.newsCard.border } );
			}
		} );

		// News card title links and text
		queryContent( '.stagekitwp-homepage-news-feed h3 a' ).forEach( function ( el ) {
			patchEl( el, { color: DARK.headingLink.color } );
		} );
		queryContent( '.stagekitwp-homepage-news-feed p' ).forEach( function ( el ) {
			patchEl( el, { color: DARK.cardBodyText.color } );
		} );

		// Remaining inline-colored headings in main content only
		queryContent( '.site-main-homepage h2, .site-main-homepage h3' ).forEach( function ( el ) {
			patchEl( el, { color: DARK.heading.color } );
		} );

		// Testimonial slide cards — explicitly patched even though their parent
		// section is in the always-dark zone. The cards have white bg + black text
		// inline so they must be individually flipped to a dark card style.
		document.querySelectorAll( '.stagekitwp-testimonial' ).forEach( function ( el ) {
			patchEl( el, { bg: DARK.testimonialCard.bg, color: DARK.testimonialCard.color, border: DARK.testimonialCard.border } );
		} );

		// ── Plugin shortcode wrappers (TM theme integration) ─────────────────
		// stagekitwpShortcodeSelectors and stagekitwpShortcodeDark are injected by
		// theme-integration.php via wp_add_inline_script.
		if ( typeof stagekitwpShortcodeSelectors !== 'undefined' && typeof stagekitwpShortcodeDark !== 'undefined' ) {
			var scBg     = stagekitwpShortcodeDark.surface_bg   || '#1e1e1e';
			var scText   = stagekitwpShortcodeDark.body_text    || '#e0e0e0';
			var scBorder = stagekitwpShortcodeDark.border       || 'rgba(255,255,255,0.12)';
			var scHead   = stagekitwpShortcodeDark.heading_text || '#ffffff';

			// Landing page dark values (PHP injects these into stagekitwpShortcodeDark)
			var lpBg     = stagekitwpShortcodeDark.lp_bg       || scBg;
			var lpText   = stagekitwpShortcodeDark.lp_text     || scText;
			var lpHead   = stagekitwpShortcodeDark.lp_heading  || scHead;
			var lpAccent = stagekitwpShortcodeDark.lp_accent   || '#c0392b';
			var lpBorder = stagekitwpShortcodeDark.lp_border   || scBorder;
			var lpMetaBg = stagekitwpShortcodeDark.lp_meta_bg  || 'rgba(255,255,255,0.05)';
			var lpBtnBg  = stagekitwpShortcodeDark.lp_btn_bg   || lpAccent;
			var lpBtnTxt = stagekitwpShortcodeDark.lp_btn_text || '#ffffff';

			stagekitwpShortcodeSelectors.forEach( function ( selector ) {
				queryContent( selector ).forEach( function ( el ) {
					var varPatch = {};
					// For CSS-var-based shortcodes, also set the custom properties
					// so child elements that use var() get the correct dark value.
					if ( el.classList.contains( 'stagekitwp-board-members-block' ) ) {
						varPatch = {
							'--stagekitwp-bm-bg':     scBg,
							'--stagekitwp-bm-text':   scText,
							'--stagekitwp-bm-border': scBorder,
						};
					} else if ( el.classList.contains( 'stagekitwp-awards-block' ) ) {
						varPatch = {
							'--stagekitwp-aw-bg':     scBg,
							'--stagekitwp-aw-text':   scText,
							'--stagekitwp-aw-border': scBorder,
							'--stagekitwp-aw-h2':     scHead,
							'--stagekitwp-aw-h3':     scText,
						};
					} else if ( el.classList.contains( 'stagekitwp-past-shows-block' ) ) {
						varPatch = {
							'--stagekitwp-ps-bg':     scBg,
							'--stagekitwp-ps-text':   scText,
							'--stagekitwp-ps-border': scBorder,
							'--stagekitwp-ps-h2':     scHead,
							'--stagekitwp-ps-h3':     scText,
						};
					} else if ( el.classList.contains( 'stagekitwp-landingpage-wrapper' ) ) {
						// Landing page is CSS-var-first: ALL colours live on --stagekitwp-lp-*.
						// Use setProperty( important ) so dark vars beat any inline
						// style="" already on the element (e.g. font/align vars).
						patchEl( el, { vars: {
							'--stagekitwp-lp-bg':       lpBg,
							'--stagekitwp-lp-text':     lpText,
							'--stagekitwp-lp-heading':  lpHead,
							'--stagekitwp-lp-accent':   lpAccent,
							'--stagekitwp-lp-border':   lpBorder,
							'--stagekitwp-lp-meta-bg':  lpMetaBg,
							'--stagekitwp-lp-btn-bg':   lpBtnBg,
							'--stagekitwp-lp-btn-text': lpBtnTxt,
						} } );
						return; // vars-only patch done—skip generic patchEl below
					} else if ( el.classList.contains( 'stagekitwp-tickets-block' ) ) {
						// Tickets block is CSS-var-first: all colours live on --stagekitwp-tk-*.
						// Patching vars directly means restoreEl simply removes them and
						// the light-mode defaults defined in the shortcode <style> block
						// take over cleanly — no stale inline background/color fighting them.
						var tkLink = stagekitwpShortcodeDark.tk_btn || scBg; // fallback to surface if no dedicated dark btn
						patchEl( el, { vars: {
							'--stagekitwp-tk-bg':      scBg,
							'--stagekitwp-tk-text':    scText,
							'--stagekitwp-tk-btn':     tkLink,
							'--stagekitwp-tk-btn-hov': tkLink,
							'--stagekitwp-tk-border':  scBorder,
						} } );
						return; // vars-only patch done—skip generic patchEl below
					}
					patchEl( el, { bg: scBg, color: scText, border: '1px solid ' + scBorder, vars: varPatch } );
				} );
			} );

			// Headings inside shortcode wrappers
			var scHeadSelectors = [
				'.stagekitwp-board-members-block h3, .stagekitwp-board-members-block h4',
				'.stagekitwp-sponsor-card h3, .stagekitwp-sponsor-card h4',
				'.stagekitwp-contributor-entry h3, .stagekitwp-contributor-entry h4',
				'.stagekitwp-shortcode-wrapper h2, .stagekitwp-shortcode-wrapper h3, .stagekitwp-shortcode-wrapper h4',
				'.stagekitwp-venue-card h3, .stagekitwp-venue-card h4',
				'.stagekitwp-cast-entry h3',
				'.stagekitwp-season-title, .stagekitwp-slot-title, .stagekitwp-show-title, .stagekitwp-program-header',
			].join( ', ' );
			queryContent( scHeadSelectors ).forEach( function ( el ) {
				patchEl( el, { color: scHead } );
			} );

			// Paragraph / muted text inside shortcode wrappers
			var scTextSelectors = [
				'.stagekitwp-bm-card .stagekitwp-bm-name, .stagekitwp-bm-card .stagekitwp-bm-position, .stagekitwp-bm-bio',
				'.stagekitwp-bm-list-row .stagekitwp-bm-name, .stagekitwp-bm-list-row .stagekitwp-bm-position, .stagekitwp-bm-list-row .stagekitwp-bm-bio',
				'.stagekitwp-bm-spotlight-card .stagekitwp-bm-name, .stagekitwp-bm-spotlight-card .stagekitwp-bm-position, .stagekitwp-bm-spotlight-card .stagekitwp-bm-bio',
				'.stagekitwp-sponsor-card p, .stagekitwp-sponsor-card a',
				'.stagekitwp-contributor-entry p',
				'.stagekitwp-shortcode-wrapper p, .stagekitwp-shortcode-wrapper li',
				'.stagekitwp-venue-card p, .stagekitwp-venue-card li',
				'.stagekitwp-cast-entry p',
			].join( ', ' );
			queryContent( scTextSelectors ).forEach( function ( el ) {
				patchEl( el, { color: scText } );
			} );
		}
	}

	function applyLight() {
		document.querySelectorAll( '[data-stagekitwp-idx]' ).forEach( restoreEl );
	}

	// ── Determine initial mode ────────────────────────────────────────────────
	// Read the server default injected by PHP as a global before this script.
	// stagekitwpColorModeDefault is set via wp_add_inline_script 'before' this handle,
	// so it is always available even when the script runs in <head>.
	var serverDefault = ( typeof stagekitwpColorModeDefault === 'string' &&
	                      ( stagekitwpColorModeDefault === 'dark' || stagekitwpColorModeDefault === 'light' ) )
	                    ? stagekitwpColorModeDefault : 'light';
	var storedRaw     = localStorage.getItem( STORAGE_KEY );
	// Validate — only accept the two known values; discard anything else.
	var stored        = ( storedRaw === 'dark' || storedRaw === 'light' ) ? storedRaw : null;
	var currentMode   = stored || serverDefault;

	// serverDefault is now resolved before DOMContentLoaded; body data-attr
	// read in DOMContentLoaded is kept as a secondary confirmation only.

	function syncHtmlClass( mode ) {
		if ( mode === 'dark' ) {
			html.classList.add( 'stagekitwp-dark-mode' );
		} else {
			html.classList.remove( 'stagekitwp-dark-mode' );
		}
	}

	function updateButton( mode ) {
		var btn = document.getElementById( 'stagekitwp-color-mode-toggle' );
		if ( ! btn ) { return; }
		if ( mode === 'dark' ) {
			btn.innerHTML = '☀️';
			btn.setAttribute( 'aria-label', 'Switch to Light Mode' );
			btn.setAttribute( 'title',      'Switch to Light Mode' );
		} else {
			btn.innerHTML = '🌙';
			btn.setAttribute( 'aria-label', 'Switch to Dark Mode' );
			btn.setAttribute( 'title',      'Switch to Dark Mode' );
		}
	}

	function applyMode( mode, patch ) {
		syncHtmlClass( mode );
		if ( patch !== false ) {
			if ( mode === 'dark' ) {
				applyDark();
			} else {
				applyLight();
			}
			applyLogoMode( mode );
		}
		currentMode = mode;
		updateButton( mode );
	}

	// Apply html class immediately (before paint) — no inline patching yet, DOM not ready
	syncHtmlClass( currentMode );

	document.addEventListener( 'DOMContentLoaded', function () {
		// Confirm server default from body data-attr (secondary check).
		// Only update currentMode if no localStorage preference was set.
		var bodyDefault = ( document.body && document.body.dataset.stagekitwpColorMode &&
		                    ( document.body.dataset.stagekitwpColorMode === 'dark' || document.body.dataset.stagekitwpColorMode === 'light' ) )
		                  ? document.body.dataset.stagekitwpColorMode : null;
		if ( ! stored && bodyDefault ) { currentMode = bodyDefault; }

		// Apply full mode (including inline patches) now that DOM is ready
		applyMode( currentMode, true );

		// Wire up the toggle button
		var btn = document.getElementById( 'stagekitwp-color-mode-toggle' );
		if ( btn ) {
			btn.addEventListener( 'click', function () {
				var next = ( currentMode === 'dark' ) ? 'light' : 'dark';
				localStorage.setItem( STORAGE_KEY, next );
				applyMode( next, true );
			} );
		}
	} );

} )();
