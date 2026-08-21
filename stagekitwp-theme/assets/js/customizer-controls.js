/**
 * StageKitWP Theme — Customizer Controls Pane JS
 *
 * Runs in the LEFT PANEL (controls pane), not the preview iframe.
 *
 * 1. Side-by-side layout: pairs .stagekitwp-color-pair-light + .stagekitwp-color-pair-dark
 *    controls into a single visual row so Light and Dark sit next to each other.
 *
 * 2. Brightness warning: any dark-mode background picker whose chosen colour
 *    has a relative luminance above 0.25 shows a warning badge.
 */
( function ( $ ) {
	'use strict';

	// ── 1. Side-by-side pairing ──────────────────────────────────────────────
	// After the controls render, find consecutive light/dark colour pair rows
	// and wrap them in a flex container so they appear on one line.

	function pairColorRows() {
		// Each control li has a data attribute we set via description HTML.
		// Light control:  data-stagekitwp-pair="light" data-stagekitwp-pair-key="KEYNAME"
		// Dark  control:  data-stagekitwp-pair="dark"  data-stagekitwp-pair-key="KEYNAME"
		var lights = document.querySelectorAll( '[data-stagekitwp-pair="light"]' );

		lights.forEach( function ( lightLi ) {
			var key     = lightLi.dataset.stagekitwpPairKey;
			var darkLi  = document.querySelector( '[data-stagekitwp-pair="dark"][data-stagekitwp-pair-key="' + key + '"]' );
			if ( ! darkLi || lightLi.parentNode !== darkLi.parentNode ) { return; }

			// Already wrapped?
			if ( lightLi.parentNode.classList.contains( 'stagekitwp-pair-wrapper' ) ) { return; }

			// Create wrapper row.
			var wrapper = document.createElement( 'div' );
			wrapper.className = 'stagekitwp-pair-wrapper';
			wrapper.style.cssText = [
				'display:grid',
				'grid-template-columns:1fr 1fr',
				'gap:8px',
				'align-items:start',
				'margin-bottom:4px'
			].join(';');

			lightLi.parentNode.insertBefore( wrapper, lightLi );
			wrapper.appendChild( lightLi );
			wrapper.appendChild( darkLi );

			// Tighten each cell.
			[ lightLi, darkLi ].forEach( function ( li ) {
				li.style.marginBottom = '0';
				li.style.width        = '100%';
			} );
		} );
	}

	// ── 2. Brightness warning ────────────────────────────────────────────────
	// Relative luminance (WCAG formula).
	function luminance( hex ) {
		hex = hex.replace( '#', '' );
		if ( hex.length === 3 ) {
			hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
		}
		var r = parseInt( hex.slice(0,2), 16 ) / 255;
		var g = parseInt( hex.slice(2,4), 16 ) / 255;
		var b = parseInt( hex.slice(4,6), 16 ) / 255;
		function lin(c) { return c <= 0.03928 ? c/12.92 : Math.pow((c+0.055)/1.055, 2.4); }
		return 0.2126*lin(r) + 0.7152*lin(g) + 0.0722*lin(b);
	}

	// Threshold: luminance > 0.25 is too bright for a dark-mode background.
	var BRIGHT_THRESHOLD = 0.25;

	function getOrCreateWarning( container ) {
		var w = container.querySelector( '.stagekitwp-bright-warning' );
		if ( ! w ) {
			w = document.createElement( 'p' );
			w.className = 'stagekitwp-bright-warning';
			w.style.cssText = [
				'display:none',
				'margin:4px 0 0',
				'padding:5px 8px',
				'background:#fff3cd',
				'border-left:3px solid #e6a817',
				'color:#7d5a00',
				'font-size:11px',
				'line-height:1.4',
				'border-radius:2px'
			].join(';');
			w.textContent = '⚠ Too bright for a dark background — may reduce readability. Consider a value below #404040.';
			container.appendChild( w );
		}
		return w;
	}

	function checkBrightness( settingId, hex ) {
		// Only warn on dark-mode background settings.
		if ( ! settingId.match( /_dark$/ ) ) { return; }

		var controlEl = document.querySelector(
			'[id="accordion-section-stagekitwp_color_palette_section"] [data-customize-setting-link="' + settingId + '"],' +
			'[id="accordion-section-stagekitwp_color_zones_section"] [data-customize-setting-link="' + settingId + '"]'
		);
		if ( ! controlEl ) { return; }

		var li = controlEl.closest( 'li' ) || controlEl.closest( '.customize-control' );
		if ( ! li ) { return; }

		var warning = getOrCreateWarning( li );

		if ( ! hex || hex.length < 4 ) { warning.style.display = 'none'; return; }

		var lum = luminance( hex );
		warning.style.display = lum > BRIGHT_THRESHOLD ? 'block' : 'none';
	}

	// Bind to all dark-bg settings once the panel is ready.
	var DARK_BG_SETTINGS = [
		'stagekitwp_color_base_bg_dark',
		'stagekitwp_color_surface_bg_dark',
		'stagekitwp_color_notif_bg_dark',
		'stagekitwp_color_header_bg_dark',
		'stagekitwp_color_footer_bg_dark',
	];

	function bindBrightnessWarnings() {
		DARK_BG_SETTINGS.forEach( function ( id ) {
			if ( wp.customize( id ) ) {
				wp.customize( id, function ( setting ) {
					// Check on current value immediately.
					checkBrightness( id, setting.get() );
					// Re-check whenever the picker changes.
					setting.bind( function ( val ) {
						checkBrightness( id, val );
					} );
				} );
			}
		} );
	}

	// ── Boot ────────────────────────────────────────────────────────────────
	wp.customize.bind( 'ready', function () {
		// Small delay so all control HTML is in the DOM.
		setTimeout( function () {
			pairColorRows();
			bindBrightnessWarnings();
		}, 300 );

		// Re-pair after section expand (controls render lazily).
		wp.customize.section.each( function ( section ) {
			section.expanded.bind( function ( isExpanded ) {
				if ( isExpanded ) {
					setTimeout( pairColorRows, 150 );
					setTimeout( bindBrightnessWarnings, 150 );
				}
			} );
		} );
	} );

} )( jQuery );
