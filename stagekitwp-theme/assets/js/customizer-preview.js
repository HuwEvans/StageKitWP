/**
 * StageKitWP Theme — Customizer Live Preview (postMessage bindings)
 *
 * Each wp.customize() binding listens for a setting value change and updates
 * the matching CSS custom property on :root instantly, without a page reload.
 */
( function ( $ ) {
	'use strict';

	// ── Helper: update a CSS variable on :root ──────────────────────────────
	function setCSSVar( name, value ) {
		document.documentElement.style.setProperty( name, value );
	}

	// ── Helper: is dark mode currently active in the preview? ────────────────
	function isDark() {
		return document.documentElement.classList.contains( 'stagekitwp-dark-mode' );
	}

	// ── Helper: bind a LIGHT setting → CSS variable.
	//    Only applies the value when NOT in dark mode, so dark mode vars win.
	function bindColor( settingId, cssVar ) {
		wp.customize( settingId, function ( value ) {
			value.bind( function ( newVal ) {
				if ( ! isDark() ) { setCSSVar( cssVar, newVal ); }
			} );
		} );
	}

	// ── Helper: bind a DARK setting → same CSS variable, but only in dark mode.
	function bindDarkColor( settingId, cssVar ) {
		wp.customize( settingId, function ( value ) {
			value.bind( function ( newVal ) {
				if ( isDark() ) { setCSSVar( cssVar, newVal ); }
			} );
		} );
	}

	// ── Apply all current dark-colour settings to CSS vars (called on mode switch) ──
	function applyDarkVars() {
		var map = {
			'stagekitwp_color_base_bg_dark':      '--stagekitwp-base-bg',
			'stagekitwp_color_surface_bg_dark':   '--stagekitwp-surface-bg',
			'stagekitwp_color_heading_text_dark': '--stagekitwp-heading-text',
			'stagekitwp_color_body_text_dark':    '--stagekitwp-body-text',
			'stagekitwp_color_muted_text_dark':   '--stagekitwp-muted-text',
			'stagekitwp_color_link_dark':         '--stagekitwp-link',
			'stagekitwp_color_link_hover_dark':   '--stagekitwp-link-hover',
			'stagekitwp_color_notif_bg_dark':     '--stagekitwp-notif-bg',
			'stagekitwp_color_notif_text_dark':   '--stagekitwp-notif-text',
			'stagekitwp_color_header_bg_dark':    '--stagekitwp-header-bg',
			'stagekitwp_color_header_text_dark':  '--stagekitwp-header-text',
			'stagekitwp_color_header_hover_dark': '--stagekitwp-header-hover',
			'stagekitwp_color_footer_bg_dark':    '--stagekitwp-footer-bg',
			'stagekitwp_color_footer_text_dark':  '--stagekitwp-footer-text',
		};
		Object.keys( map ).forEach( function ( id ) {
			var setting = wp.customize( id );
			if ( setting && setting.get() ) { setCSSVar( map[ id ], setting.get() ); }
		} );
	}

	// ── Apply all current light-colour settings (called when switching to light) ──
	function applyLightVars() {
		var map = {
			'stagekitwp_color_base_bg':          '--stagekitwp-base-bg',
			'stagekitwp_color_surface_bg':       '--stagekitwp-surface-bg',
			'stagekitwp_color_heading_text':     '--stagekitwp-heading-text',
			'stagekitwp_color_body_text':        '--stagekitwp-body-text',
			'stagekitwp_color_muted_text':       '--stagekitwp-muted-text',
			'stagekitwp_color_link':             '--stagekitwp-link',
			'stagekitwp_color_link_hover':       '--stagekitwp-link-hover',
			'stagekitwp_color_notif_bg':         '--stagekitwp-notif-bg',
			'stagekitwp_color_notif_text':       '--stagekitwp-notif-text',
			'stagekitwp_color_header_bg':        '--stagekitwp-header-bg',
			'stagekitwp_color_header_text':      '--stagekitwp-header-text',
			'stagekitwp_color_header_hover':     '--stagekitwp-header-hover',
			'stagekitwp_color_footer_bg':        '--stagekitwp-footer-bg',
			'stagekitwp_color_footer_text':      '--stagekitwp-footer-text',
		};
		Object.keys( map ).forEach( function ( id ) {
			var setting = wp.customize( id );
			if ( setting && setting.get() ) { setCSSVar( map[ id ], setting.get() ); }
		} );
	}

	// =========================================================================
	// SECTION 2: Global Branding & Palette — light + dark bindings
	// =========================================================================
	bindColor( 'stagekitwp_color_primary_accent',   '--stagekitwp-primary' );
	bindColor( 'stagekitwp_color_secondary_accent', '--stagekitwp-secondary' );
	bindColor( 'stagekitwp_color_base_bg',          '--stagekitwp-base-bg' );
	bindColor( 'stagekitwp_color_surface_bg',       '--stagekitwp-surface-bg' );
	bindDarkColor( 'stagekitwp_color_base_bg_dark',    '--stagekitwp-base-bg' );
	bindDarkColor( 'stagekitwp_color_surface_bg_dark', '--stagekitwp-surface-bg' );

	// =========================================================================
	// SECTION 3: Global Typography — light + dark bindings
	// =========================================================================
	bindColor( 'stagekitwp_color_heading_text', '--stagekitwp-heading-text' );
	bindColor( 'stagekitwp_color_body_text',    '--stagekitwp-body-text' );
	bindColor( 'stagekitwp_color_muted_text',   '--stagekitwp-muted-text' );
	bindDarkColor( 'stagekitwp_color_heading_text_dark', '--stagekitwp-heading-text' );
	bindDarkColor( 'stagekitwp_color_body_text_dark',    '--stagekitwp-body-text' );
	bindDarkColor( 'stagekitwp_color_muted_text_dark',   '--stagekitwp-muted-text' );

	// =========================================================================
	// SECTION 4: Interactive / Links — light + dark bindings
	// =========================================================================
	bindColor( 'stagekitwp_color_link',       '--stagekitwp-link' );
	bindColor( 'stagekitwp_color_link_hover', '--stagekitwp-link-hover' );
	bindDarkColor( 'stagekitwp_color_link_dark',       '--stagekitwp-link' );
	bindDarkColor( 'stagekitwp_color_link_hover_dark', '--stagekitwp-link-hover' );

	// =========================================================================
	// SECTION 5: Zone Overrides — light + dark bindings
	// =========================================================================
	bindColor( 'stagekitwp_color_notif_bg',     '--stagekitwp-notif-bg' );
	bindColor( 'stagekitwp_color_notif_text',   '--stagekitwp-notif-text' );
	bindColor( 'stagekitwp_color_header_bg',    '--stagekitwp-header-bg' );
	bindColor( 'stagekitwp_color_header_text',  '--stagekitwp-header-text' );
	bindColor( 'stagekitwp_color_header_hover', '--stagekitwp-header-hover' );
	bindColor( 'stagekitwp_color_footer_bg',    '--stagekitwp-footer-bg' );
	bindColor( 'stagekitwp_color_footer_text',  '--stagekitwp-footer-text' );
	bindDarkColor( 'stagekitwp_color_notif_bg_dark',     '--stagekitwp-notif-bg' );
	bindDarkColor( 'stagekitwp_color_notif_text_dark',   '--stagekitwp-notif-text' );
	bindDarkColor( 'stagekitwp_color_header_bg_dark',    '--stagekitwp-header-bg' );
	bindDarkColor( 'stagekitwp_color_header_text_dark',  '--stagekitwp-header-text' );
	bindDarkColor( 'stagekitwp_color_header_hover_dark', '--stagekitwp-header-hover' );
	bindDarkColor( 'stagekitwp_color_footer_bg_dark',    '--stagekitwp-footer-bg' );
	bindDarkColor( 'stagekitwp_color_footer_text_dark',  '--stagekitwp-footer-text' );

	// ── Zone DOM helpers ────────────────────────────────────────────────────────────────
	function applyNotifBg(v)   { $('.stagekitwp-top-notification-bar').css('background-color',v); }
	function applyNotifText(v) { $('.stagekitwp-top-notification-bar,.stagekitwp-top-notification-bar *').css('color',v); }
	function applyHeaderBg(v)  { $('.site-header,.stagekitwp-primary-menu-list li ul').css('background-color',v); }
	function applyHeaderText(v){ $('.site-header .site-title a,.stagekitwp-primary-menu-list li a').css('color',v); }
	function applyFooterBg(v)  { $('.site-footer').css('background-color',v); }
	function applyFooterText(v){ $('.site-footer,.stagekitwp-footer-widget a,.widget_nav_menu a').css('color',v); }
	function applyBaseBg(v)    { $('body,#page,.site-content,#primary').css('background-color',v); }
	function applyBodyText(v)  { $('body,.entry-content,.entry-content p,.entry-content li').css('color',v); }
	function applyHeadingText(v){ $('h1,h2,h3,h4,h5,h6,.entry-title').css('color',v); }
	function applySurfaceBg(v) { $('.stagekitwp-show-card-column,.stagekitwp-upcoming-grid-section').css('background-color',v); }

	// Light DOM bindings — skip when dark mode is active.
	wp.customize('stagekitwp_color_notif_bg',    function(s){ s.bind(function(v){ if(!isDark()) applyNotifBg(v);    }); });
	wp.customize('stagekitwp_color_notif_text',  function(s){ s.bind(function(v){ if(!isDark()) applyNotifText(v);  }); });
	wp.customize('stagekitwp_color_header_bg',   function(s){ s.bind(function(v){ if(!isDark()) applyHeaderBg(v);   }); });
	wp.customize('stagekitwp_color_header_text', function(s){ s.bind(function(v){ if(!isDark()) applyHeaderText(v); }); });
	wp.customize('stagekitwp_color_footer_bg',   function(s){ s.bind(function(v){ if(!isDark()) applyFooterBg(v);   }); });
	wp.customize('stagekitwp_color_footer_text', function(s){ s.bind(function(v){ if(!isDark()) applyFooterText(v); }); });
	wp.customize('stagekitwp_color_base_bg',     function(s){ s.bind(function(v){ if(!isDark()) applyBaseBg(v);     }); });
	wp.customize('stagekitwp_color_body_text',   function(s){ s.bind(function(v){ if(!isDark()) applyBodyText(v);   }); });
	wp.customize('stagekitwp_color_heading_text',function(s){ s.bind(function(v){ if(!isDark()) applyHeadingText(v);}); });
	wp.customize('stagekitwp_color_surface_bg',  function(s){ s.bind(function(v){ if(!isDark()) applySurfaceBg(v);  }); });
	wp.customize('stagekitwp_color_primary_accent', function(s){
		s.bind(function(v){
			$('.stagekitwp-top-notification-bar').css('border-bottom-color',v);
			$('.stagekitwp-primary-menu-list li ul').css('border-top-color',v);
		});
	});

	// Dark DOM bindings — only fire when dark mode is active.
	wp.customize('stagekitwp_color_notif_bg_dark',    function(s){ s.bind(function(v){ if(isDark()) applyNotifBg(v);    }); });
	wp.customize('stagekitwp_color_notif_text_dark',  function(s){ s.bind(function(v){ if(isDark()) applyNotifText(v);  }); });
	wp.customize('stagekitwp_color_header_bg_dark',   function(s){ s.bind(function(v){ if(isDark()) applyHeaderBg(v);   }); });
	wp.customize('stagekitwp_color_header_text_dark', function(s){ s.bind(function(v){ if(isDark()) applyHeaderText(v); }); });
	wp.customize('stagekitwp_color_footer_bg_dark',   function(s){ s.bind(function(v){ if(isDark()) applyFooterBg(v);   }); });
	wp.customize('stagekitwp_color_footer_text_dark', function(s){ s.bind(function(v){ if(isDark()) applyFooterText(v); }); });
	wp.customize('stagekitwp_color_base_bg_dark',     function(s){ s.bind(function(v){ if(isDark()) applyBaseBg(v);     }); });
	wp.customize('stagekitwp_color_body_text_dark',   function(s){ s.bind(function(v){ if(isDark()) applyBodyText(v);   }); });
	wp.customize('stagekitwp_color_heading_text_dark',function(s){ s.bind(function(v){ if(isDark()) applyHeadingText(v);}); });
	wp.customize('stagekitwp_color_surface_bg_dark',  function(s){ s.bind(function(v){ if(isDark()) applySurfaceBg(v);  }); });


	// =========================================================================
	// Notification / Countdown Bar
	// =========================================================================

	// Toggle visibility of the entire bar.
	wp.customize( 'stagekitwp_enable_countdown', function ( value ) {
		value.bind( function ( v ) {
			var bar = document.querySelector( '.stagekitwp-top-notification-bar' );
			if ( ! bar ) { return; }
			bar.style.display = ( v === true || v === '1' || v === 1 ) ? '' : 'none';
		} );
	} );

	// Text alignment — translates select value to flex justify-content.
	wp.customize( 'stagekitwp_notification_alignment', function ( value ) {
		value.bind( function ( v ) {
			var inner = document.querySelector( '.stagekitwp-top-notification-bar .stagekitwp-container' );
			if ( ! inner ) { return; }
			var map = { left: 'flex-start', center: 'center', right: 'flex-end' };
			inner.style.justifyContent = map[ v ] || 'center';
		} );
	} );

	// Fallback static target date — restart the countdown timer in-place.
	// (Automated show calculation is PHP-side; this only updates the
	//  fallback field, which is the one the Customizer control edits.)
	wp.customize( 'stagekitwp_next_show_timestamp', function ( value ) {
		value.bind( function ( v ) {
			var label = document.getElementById( 'stagekitwp-countdown-label' );
			if ( ! label ) { return; }
			if ( ! v ) {
				label.textContent = 'No date set.';
				return;
			}
			var targetDate = new Date( v ).getTime();
			if ( isNaN( targetDate ) ) {
				label.textContent = 'Invalid date format.';
				return;
			}
			// Clear any existing timer stored on the element.
			if ( label._tmTimer ) { clearInterval( label._tmTimer ); }
			function tick() {
				var diff = targetDate - new Date().getTime();
				if ( diff < 0 ) {
					clearInterval( label._tmTimer );
					label.innerHTML = 'Performance Live! Visit Box Office for Entry.';
					return;
				}
				var d = Math.floor( diff / 86400000 );
				var h = Math.floor( ( diff % 86400000 ) / 3600000 );
				var m = Math.floor( ( diff % 3600000 )  / 60000 );
				var s = Math.floor( ( diff % 60000 )     / 1000 );
				label.innerHTML = 'Curtain rises in: <strong>' + d + 'd ' + h + 'h ' + m + 'm ' + s + 's</strong>';
			}
			tick();
			label._tmTimer = setInterval( tick, 1000 );
		} );
	} );

	// =========================================================================
	// Hero section
	// =========================================================================
	wp.customize( 'stagekitwp_hero_headline', function ( value ) {
		value.bind( function ( v ) {
			var el = document.querySelector( '.stagekitwp-hero-title' );
			if ( el ) { el.textContent = v; }
		} );
	} );

	wp.customize( 'stagekitwp_hero_subheading', function ( value ) {
		value.bind( function ( v ) {
			var el = document.querySelector( '.stagekitwp-hero-subtitle' );
			if ( el ) { el.textContent = v; }
		} );
	} );

	wp.customize( 'stagekitwp_hero_btn_url', function ( value ) {
		value.bind( function ( v ) {
			var el = document.querySelector( '.stagekitwp-hero-btn' );
			if ( el ) { el.href = v; }
		} );
	} );

	wp.customize( 'stagekitwp_hero_btn_placement', function ( value ) {
		value.bind( function ( v ) {
			var el = document.querySelector( '.stagekitwp-hero-actions' );
			if ( ! el ) { return; }

			el.className = el.className.replace( /stagekitwp-hero-actions-\S+/g, '' ).trim();
			el.classList.add( 'stagekitwp-hero-actions-' + v );
		} );
	} );

	wp.customize( 'stagekitwp_hero_btn_pad_x', function ( value ) {
		value.bind( function ( v ) {
			var el = document.querySelector( '.stagekitwp-hero-banner' );
			if ( el ) { el.style.setProperty( '--stagekitwp-hero-cta-pad-x', v + 'px' ); }
		} );
	} );

	wp.customize( 'stagekitwp_hero_btn_pad_y', function ( value ) {
		value.bind( function ( v ) {
			var el = document.querySelector( '.stagekitwp-hero-banner' );
			if ( el ) { el.style.setProperty( '--stagekitwp-hero-cta-pad-y', v + 'px' ); }
		} );
	} );

	wp.customize( 'stagekitwp_hero_btn_safe_area', function ( value ) {
		value.bind( function ( v ) {
			var el = document.querySelector( '.stagekitwp-hero-banner' );
			if ( ! el ) { return; }

			el.classList.toggle( 'stagekitwp-hero-safe-area-enabled', !! v );
		} );
	} );

	wp.customize( 'stagekitwp_hero_bg_image', function ( value ) {
		value.bind( function ( v ) {
			var banner = document.querySelector( '.stagekitwp-hero-banner.media-type-image' );
			var img = document.querySelector( '.stagekitwp-hero-image' );
			if ( img && v ) {
				img.src = v;
			} else if ( banner && v ) {
				banner.style.backgroundImage = 'url(' + v + ')';
			}
		} );
	} );

	wp.customize( 'stagekitwp_hero_bg_video', function ( value ) {
		value.bind( function ( v ) {
			var src = document.querySelector( '.stagekitwp-hero-video source' );
			var vid = document.querySelector( '.stagekitwp-hero-video' );
			if ( src && vid ) {
				vid.setAttribute( 'data-desktop-src', v || '' );
				if ( window.stagekitwpSyncHeroVideoSource ) {
					window.stagekitwpSyncHeroVideoSource();
				} else if ( v ) {
					src.src = v;
					vid.load();
				}
			}
		} );
	} );

	wp.customize( 'stagekitwp_hero_bg_video_mobile', function ( value ) {
		value.bind( function ( v ) {
			var vid = document.querySelector( '.stagekitwp-hero-video' );
			if ( vid ) {
				vid.setAttribute( 'data-mobile-src', v || '' );
				if ( window.stagekitwpSyncHeroVideoSource ) {
					window.stagekitwpSyncHeroVideoSource();
				}
			}
		} );
	} );

	wp.customize( 'stagekitwp_hero_media_type', function ( value ) {
		value.bind( function ( v ) {
			var el = document.querySelector( '.stagekitwp-hero-banner' );
			if ( ! el ) { return; }
			el.className = el.className.replace( /media-type-\S+/, '' ).trim();
			el.classList.add( 'media-type-' + v );
		} );
	} );

	// =========================================================================
	// Tagline — show/hide toggle and position
	//
	// wp.customize( setting ).get() is unreliable in the preview iframe —
	// the setting object may not be registered yet. We use stagekitwpPreviewData
	// (injected by wp_localize_script from PHP) as the authoritative initial
	// state, and keep a local mirror that each binding updates.
	// =========================================================================

	// Local mirror of current tagline state.
	// Primary source: stagekitwpPreviewData injected by wp_localize_script from PHP.
	// Fallback: read any already-rendered .stagekitwp-site-tagline in the DOM.
	var stagekitwpData     = window.stagekitwpPreviewData || {};
	var domTagline = document.querySelector( '.stagekitwp-site-tagline' );
	var stagekitwpTagline  = {
		show : stagekitwpData.showTagline !== undefined
			? !! stagekitwpData.showTagline
			: !! domTagline,
		pos  : stagekitwpData.taglinePosition
			|| ( domTagline && domTagline.className.replace( /.*stagekitwp-site-tagline--/, '' ).trim() )
			|| 'below-logo',
		text : stagekitwpData.taglineText
			|| ( domTagline && domTagline.textContent.trim() )
			|| ''
	};

	// Build a tagline <p> element.
	function stagekitwpBuildTagline( text, pos ) {
		var p = document.createElement( 'p' );
		p.className   = 'stagekitwp-site-tagline stagekitwp-site-tagline--' + pos;
		p.style.margin = '0';
		p.textContent  = text;
		return p;
	}

	// Remove all existing tagline nodes from the DOM.
	function stagekitwpRemoveTaglines() {
		document.querySelectorAll( '.stagekitwp-site-tagline' ).forEach( function ( el ) {
			el.parentNode.removeChild( el );
		} );
	}

	// Full re-render: remove old nodes, inject new one if needed.
	function stagekitwpSyncTagline() {
		var show = stagekitwpTagline.show;
		var pos  = stagekitwpTagline.pos;
		var text = stagekitwpTagline.text;

		stagekitwpRemoveTaglines();

		if ( ! show || ! text ) { return; }

		var branding = document.querySelector( '.site-branding' );
		if ( ! branding ) { return; }

		var tagEl = stagekitwpBuildTagline( text, pos );

		if ( pos === 'header-end' ) {
			branding.parentNode.insertBefore( tagEl, branding.nextSibling );
		} else {
			branding.appendChild( tagEl );
			branding.style.flexDirection = ( pos === 'below-logo' ) ? 'column' : 'row';
			branding.style.alignItems    = ( pos === 'below-logo' ) ? 'flex-start' : 'center';
		}
	}

	wp.customize( 'stagekitwp_show_tagline', function ( value ) {
		value.bind( function ( show ) {
			// Checkbox arrives as boolean true/false from the controls pane.
			stagekitwpTagline.show = !! show;
			stagekitwpSyncTagline();
		} );
	} );

	wp.customize( 'stagekitwp_tagline_position', function ( value ) {
		value.bind( function ( pos ) {
			stagekitwpTagline.pos = pos || 'below-logo';
			stagekitwpSyncTagline();
		} );
	} );

	// blogdescription: update text live if user edits it in Site Identity.
	wp.customize( 'blogdescription', function ( value ) {
		value.bind( function ( text ) {
			stagekitwpTagline.text = text || '';
			if ( stagekitwpTagline.show ) {
				// Update existing nodes in-place — no full re-render needed.
				var els = document.querySelectorAll( '.stagekitwp-site-tagline' );
				if ( els.length ) {
					els.forEach( function ( el ) { el.textContent = text; } );
				} else {
					stagekitwpSyncTagline();
				}
			}
		} );
	} );

	// =========================================================================
	// Term Badge — show/hide toggle and position (DOM manipulation, no reload)
	// =========================================================================

	// Mirror initialised from PHP-injected data.
	var stagekitwpBadge = {
		show : window.stagekitwpPreviewData ? !! stagekitwpPreviewData.showBadge : true,
		pos  : window.stagekitwpPreviewData ? ( stagekitwpPreviewData.badgePosition || 'top-bar' ) : 'top-bar'
	};

	// Move all badges to the correct DOM slot and update visibility + class.
	function stagekitwpSyncBadges() {
		document.querySelectorAll( '.stagekitwp-show-card-column' ).forEach( function ( card ) {
			var badge = card.querySelector( '.term-badge' );
			if ( ! badge ) { return; }

			// Update visibility.
			badge.style.display = stagekitwpBadge.show ? '' : 'none';
			if ( ! stagekitwpBadge.show ) { return; }

			// Update position class.
			badge.className = badge.className
				.replace( /stagekitwp-badge-\S+/g, '' ).trim();
			badge.classList.add( 'stagekitwp-badge-' + stagekitwpBadge.pos );

			// Move to correct DOM parent.
			var inner  = card.querySelector( ':scope > div' );
			var media  = card.querySelector( '.show-card-media' );
			var body   = card.querySelector( '.show-card-body' );

			if ( stagekitwpBadge.pos === 'top-bar' && inner && badge.parentNode !== inner ) {
				inner.insertBefore( badge, inner.firstChild );
			} else if ( stagekitwpBadge.pos === 'over-image' && media && badge.parentNode !== media ) {
				media.appendChild( badge );
			} else if ( stagekitwpBadge.pos === 'inside-card' && body && badge.parentNode !== body ) {
				body.insertBefore( badge, body.firstChild );
			}
		} );
	}

	wp.customize( 'stagekitwp_show_term_badge', function ( value ) {
		value.bind( function ( show ) {
			stagekitwpBadge.show = !! show;
			stagekitwpSyncBadges();
		} );
	} );

	wp.customize( 'stagekitwp_term_badge_position', function ( value ) {
		value.bind( function ( pos ) {
			stagekitwpBadge.pos = pos || 'top-bar';
			stagekitwpSyncBadges();
		} );
	} );

	// =========================================================================
	// Hero text colours — live postMessage bindings
	// =========================================================================
	wp.customize( 'stagekitwp_hero_title_color_light', function ( value ) {
		value.bind( function ( v ) {
			document.documentElement.style.setProperty( '--stagekitwp-hero-title-light', v );
		} );
	} );

	wp.customize( 'stagekitwp_hero_title_color_dark', function ( value ) {
		value.bind( function ( v ) {
			document.documentElement.style.setProperty( '--stagekitwp-hero-title-dark', v );
		} );
	} );

	wp.customize( 'stagekitwp_hero_subtitle_color_light', function ( value ) {
		value.bind( function ( v ) {
			document.documentElement.style.setProperty( '--stagekitwp-hero-subtitle-light', v );
		} );
	} );

	wp.customize( 'stagekitwp_hero_subtitle_color_dark', function ( value ) {
		value.bind( function ( v ) {
			document.documentElement.style.setProperty( '--stagekitwp-hero-subtitle-dark', v );
		} );
	} );

	// =========================================================================
	// Hero overlay — live postMessage bindings
	// =========================================================================
	wp.customize( 'stagekitwp_hero_overlay_color', function ( value ) {
		value.bind( function ( v ) {
			document.documentElement.style.setProperty( '--stagekitwp-hero-overlay-color', v );
			var el = document.querySelector( '.stagekitwp-hero-banner' );
			if ( el ) { el.style.setProperty( '--stagekitwp-hero-overlay-color', v ); }
		} );
	} );

	wp.customize( 'stagekitwp_hero_overlay_opacity', function ( value ) {
		value.bind( function ( v ) {
			var opacityVal = ( parseFloat( v ) / 100 ).toFixed( 2 );
			document.documentElement.style.setProperty( '--stagekitwp-hero-overlay-opacity', opacityVal );
			var el = document.querySelector( '.stagekitwp-hero-banner' );
			if ( el ) { el.style.setProperty( '--stagekitwp-hero-overlay-opacity', opacityVal ); }
		} );
	} );

	// =========================================================================
	// Footer layout & column alignment
	// =========================================================================
	wp.customize( 'stagekitwp_footer_layout_mode', function ( value ) {
		value.bind( function ( v ) {
			var cols = document.querySelector( '.footer-columns-container' );
			if ( ! cols ) { return; }
			if ( v === 'full-width' ) {
				cols.style.maxWidth = '100%';
				cols.style.padding  = '0 40px';
				cols.style.flexDirection = 'row';
			} else if ( v === 'one-column' ) {
				cols.style.maxWidth = '1200px';
				cols.style.padding  = '0 20px';
				cols.style.flexDirection = 'column';
				cols.style.alignItems    = 'center';
			} else {
				cols.style.maxWidth = '1200px';
				cols.style.padding  = '0 20px';
				cols.style.flexDirection = 'row';
				cols.style.alignItems    = '';
			}
		} );
	} );

	wp.customize( 'stagekitwp_footer_align_left', function ( value ) {
		value.bind( function ( v ) {
			var el = document.querySelector( '.stagekitwp-col-1' );
			if ( el ) { el.style.textAlign = v; }
		} );
	} );

	wp.customize( 'stagekitwp_footer_align_middle', function ( value ) {
		value.bind( function ( v ) {
			var el = document.querySelector( '.stagekitwp-col-2' );
			if ( el ) { el.style.textAlign = v; }
		} );
	} );

	wp.customize( 'stagekitwp_footer_align_right', function ( value ) {
		value.bind( function ( v ) {
			var el = document.querySelector( '.stagekitwp-col-3' );
			if ( el ) { el.style.textAlign = v; }
		} );
	} );

	// =========================================================================
	// Header sticky toggle
	// =========================================================================
	wp.customize( 'stagekitwp_enable_sticky_header', function ( value ) {
		value.bind( function ( v ) {
			var header = document.getElementById( 'masthead' );
			if ( ! header ) { return; }

			if ( v === true || v === 1 || v === '1' ) {
				header.style.position = 'sticky';
				header.style.top      = '0';
				header.style.zIndex   = '9990';
			} else {
				header.style.position = '';
				header.style.top      = '';
				header.style.zIndex   = '';
			}
		} );
	} );

	// =========================================================================
	// Testimonials width & alignment
	// =========================================================================
	wp.customize( 'stagekitwp_testimonials_width', function ( value ) {
		value.bind( function ( v ) {
			var el = document.querySelector( '.stagekitwp-testimonials-section-wrapper, .stagekitwp-testimonials-section, [class*="testimonial"]' );
			if ( el ) { el.style.maxWidth = v + '%'; }
		} );
	} );

	wp.customize( 'stagekitwp_testimonials_alignment', function ( value ) {
		value.bind( function ( v ) {
			var el = document.querySelector( '.stagekitwp-testimonials-section-wrapper, .stagekitwp-testimonials-section, [class*="testimonial"]' );
			if ( el ) {
				if ( v === 'left' )       { el.style.marginLeft = '0'; el.style.marginRight = 'auto'; }
				else if ( v === 'right' ) { el.style.marginLeft = 'auto'; el.style.marginRight = '0'; }
				else                      { el.style.marginLeft = 'auto'; el.style.marginRight = 'auto'; }
			}
		} );
	} );

	wp.customize( 'stagekitwp_testimonials_show_rating', function ( value ) {
		value.bind( function ( v ) {
			var section = document.querySelector( '.stagekitwp-testimonials-section' );
			if ( ! section ) { return; }
			if ( v ) {
				section.classList.remove( 'stagekitwp-hide-testimonial-rating' );
			} else {
				section.classList.add( 'stagekitwp-hide-testimonial-rating' );
			}
		} );
	} );

	// =========================================================================
	// Color mode & frontend switcher
	// =========================================================================
	wp.customize( 'stagekitwp_color_mode', function ( value ) {
		value.bind( function ( v ) {
			if ( v === 'dark' ) {
				document.documentElement.classList.add( 'stagekitwp-dark-mode' );
				// Re-apply all dark CSS vars so pickers already changed are live.
				applyDarkVars();
			} else {
				document.documentElement.classList.remove( 'stagekitwp-dark-mode' );
				// Re-apply light vars so the canvas reverts correctly.
				applyLightVars();
			}
		} );
	} );

	wp.customize( 'stagekitwp_enable_frontend_switcher', function ( value ) {
		value.bind( function ( v ) {
			var btn = document.getElementById( 'stagekitwp-color-mode-toggle' );
			if ( btn ) {
				btn.style.display = v ? 'flex' : 'none';
			}
		} );
	} );

	// =========================================================================
	// Logo: light + dark swap
	// =========================================================================
	wp.customize( 'stagekitwp_custom_logo', function ( value ) {
		value.bind( function ( newVal ) {
			var el = document.querySelector( '.stagekitwp-logo-light' );
			if ( el ) { el.src = newVal; }
		} );
	} );

	wp.customize( 'stagekitwp_logo_dark', function ( value ) {
		value.bind( function ( newVal ) {
			var existing = document.querySelector( '.stagekitwp-logo-dark-img' );
			var lightEl  = document.querySelector( '.stagekitwp-logo-light' );
			var wrap     = document.querySelector( '.stagekitwp-logo-wrap' );
			if ( newVal ) {
				if ( existing ) {
					existing.src = newVal;
				} else if ( wrap && lightEl ) {
					// Inject the dark logo img dynamically
					var img = document.createElement( 'img' );
					img.src       = newVal;
					img.alt       = lightEl.alt;
					img.className = 'stagekitwp-logo-dark-img';
					img.style.cssText = 'max-height:60px;width:auto;display:none;';
					wrap.appendChild( img );
				}
				// Remove auto-invert fallback class now that we have a real dark logo
				if ( lightEl ) { lightEl.classList.remove( 'stagekitwp-logo-no-dark' ); }
				// If currently in dark mode, swap immediately
				var darkImg = document.querySelector( '.stagekitwp-logo-dark-img' );
				if ( document.documentElement.classList.contains( 'stagekitwp-dark-mode' ) ) {
					if ( lightEl ) { lightEl.style.display = 'none'; }
					if ( darkImg ) { darkImg.style.display = 'block'; }
				}
			} else {
				// Dark logo cleared — fall back to auto-invert
				if ( existing ) { existing.style.display = 'none'; }
				if ( lightEl ) {
					lightEl.style.display = 'block';
					if ( document.documentElement.classList.contains( 'stagekitwp-dark-mode' ) ) {
						lightEl.classList.add( 'stagekitwp-logo-no-dark' );
					}
				}
			}
		} );
	} );

	// =========================================================================
	// Legacy / existing postMessage settings already in customizer.php
	// =========================================================================
	wp.customize( 'stagekitwp_donate_button_image', function ( value ) {
		value.bind( function ( newval ) {
			if ( newval ) {
				$( '.menu-item-donate-btn a, .footer-item-donate-btn a' ).css( 'background-image', 'url(' + newval + ')' );
				$( '.stagekitwp-donate-widget-link img' ).attr( 'src', newval );
			} else {
				$( '.menu-item-donate-btn a, .footer-item-donate-btn a' ).css( 'background-image', 'none' );
				$( '.stagekitwp-donate-widget-link img' ).attr( 'src', '' );
			}
		} );
	} );

	wp.customize( 'stagekitwp_donate_button_url', function ( value ) {
		value.bind( function ( newval ) {
			$( '.menu-item-donate-btn a, .footer-item-donate-btn a, .stagekitwp-donate-widget-link' ).attr( 'href', newval || '' );
		} );
	} );

	// =========================================================================
	// Site Max Width
	// =========================================================================
	wp.customize( 'stagekitwp_site_max_width', function ( value ) {
		value.bind( function ( v ) {
			setCSSVar( '--stagekitwp-site-max-width', v + 'px' );
		} );
	} );

} )( jQuery );
