/* global stagekitwpImportExport, jQuery */
/**
 * TM IO Admin JS – export, ZIP/JSON import, CSV import, dry-run, async polling, remap save.
 */
( function ( $, cfg ) {
	'use strict';

	// ── Helpers ────────────────────────────────────────────────────────────────

	function setStatus( $el, msg, type ) {
		$el.show().removeClass( 'is-error is-success' )
			.addClass( type === 'error' ? 'is-error' : type === 'success' ? 'is-success' : '' );
		$el.find( 'p:first-child, #stagekitwp-io-status-message, #stagekitwp-io-csv-message' ).first().text( msg );
	}

	function escHtml( s ) {
		return String( s ).replace( /[&<>"']/g, function ( c ) {
			return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[ c ];
		} );
	}

	function buildDryRunTable( data ) {
		if ( ! data.previews || ! data.previews.length ) {
			return '<p>' + escHtml( data.manifest || '' ) + '</p>';
		}
		var badges = { create:'<span class="stagekitwp-io-badge stagekitwp-io-badge--ok">CREATE</span>', update:'<span class="stagekitwp-io-badge stagekitwp-io-badge--warn">UPDATE</span>', skip:'<span class="stagekitwp-io-badge">SKIP</span>' };
		var rows = data.previews.map( function ( p ) {
			return '<tr><td>' + p.row + '</td><td>' + escHtml( p.title ) + '</td><td>' + ( badges[ p.action ] || p.action ) + '</td><td>' + escHtml( p.season || '—' ) + '</td><td>' + escHtml( p.time_slot || '' ) + '</td><td>' + escHtml( p.show_dates || '' ) + '</td></tr>';
		} ).join( '' );
		return '<table class="widefat striped" style="margin-top:12px;"><thead><tr><th>#</th><th>Title</th><th>Action</th><th>Season</th><th>Slot</th><th>Dates</th></tr></thead><tbody>' + rows + '</tbody></table><p><strong>Would create: ' + ( data.would_create || 0 ) + ' | update: ' + ( data.would_update || 0 ) + ' | skip: ' + ( data.would_skip || 0 ) + '</strong></p>';
	}

	// ── Tab switching ──────────────────────────────────────────────────────────

	$( document ).on( 'click', '#stagekitwp-io-source-tabs > .stagekitwp-io-tab', function () {
		var tab = $( this ).data( 'tab' );
		$( '#stagekitwp-io-source-tabs > .stagekitwp-io-tab' ).removeClass( 'stagekitwp-io-tab--active' );
		$( this ).addClass( 'stagekitwp-io-tab--active' );
		$( '#stagekitwp-io-tab-bundle, #stagekitwp-io-tab-csv' ).removeClass( 'stagekitwp-io-panel--active' );
		$( '#stagekitwp-io-tab-' + tab ).addClass( 'stagekitwp-io-panel--active' );
	} );

	$( document ).on( 'click', '.stagekitwp-io-tabs--inner .stagekitwp-io-tab', function () {
		var tab = $( this ).data( 'tab' );
		$( this ).closest( '.stagekitwp-io-tabs--inner' ).find( '.stagekitwp-io-tab' ).removeClass( 'stagekitwp-io-tab--active' );
		$( this ).addClass( 'stagekitwp-io-tab--active' );
		$( '#stagekitwp-io-tab-upload, #stagekitwp-io-tab-url' ).removeClass( 'stagekitwp-io-panel--active' );
		$( '#stagekitwp-io-tab-' + tab ).addClass( 'stagekitwp-io-panel--active' );
	} );

	$( document ).on( 'change', 'input[name="modules"]', function () {
		$( '#stagekitwp-io-module-checkboxes' ).toggle( $( '#stagekitwp-io-modules-custom' ).is( ':checked' ) );
	} );

	// Auto-switch to tab from URL param: ?tab=csv
	var urlTab = new URLSearchParams( window.location.search ).get( 'tab' );
	if ( urlTab ) {
		$( '#stagekitwp-io-source-tabs > .stagekitwp-io-tab[data-tab="' + urlTab + '"]' ).trigger( 'click' );
	}

	$( '#stagekitwp-io-select-all' ).on( 'click', function ( e ) {
		e.preventDefault();
		$( '.stagekitwp-io-module-table input[type="checkbox"]:not(:disabled)' ).prop( 'checked', true );
	} );

	// ── Drop zones ─────────────────────────────────────────────────────────────

	function initDropZone( $zone, $input, $lbl ) {
		$zone.on( 'click', function () { $input.trigger( 'click' ); } );
		$input.on( 'change', function () { if ( this.files[0] ) { $lbl.text( this.files[0].name ); } } );
		$zone.on( 'dragover', function ( e ) { e.preventDefault(); $( this ).addClass( 'drag-over' ); } )
			.on( 'dragleave drop', function ( e ) {
				e.preventDefault(); $( this ).removeClass( 'drag-over' );
				if ( e.type === 'drop' && e.originalEvent.dataTransfer.files[0] ) {
					var dt = new DataTransfer(); dt.items.add( e.originalEvent.dataTransfer.files[0] );
					$input[0].files = dt.files; $lbl.text( dt.files[0].name );
				}
			} );
	}

	initDropZone( $( '#stagekitwp-io-file-input' ).closest( 'label' ), $( '#stagekitwp-io-file-input' ), $( '#stagekitwp-io-file-label' ) );
	initDropZone( $( '#stagekitwp-io-csv-input'  ).closest( 'label' ), $( '#stagekitwp-io-csv-input'  ), $( '#stagekitwp-io-csv-label'  ) );

	// Enable buttons and show file info once a CSV is chosen.
	$( '#stagekitwp-io-csv-input' ).on( 'change', function () {
		var file = this.files[0];
		if ( file ) {
			$( '#stagekitwp-io-csv-dry-run-btn, #stagekitwp-io-csv-btn' ).prop( 'disabled', false );
			$( '#stagekitwp-io-csv-file-info' ).text(
				file.name + '  —  ' + ( file.size > 1024 ? Math.round( file.size / 1024 ) + ' KB' : file.size + ' B' )
			).show();
			csvSetStep( 1 );
			csvClearResults();
			csvHideRollback();
		}
	} );

	// ── Export ─────────────────────────────────────────────────────────────────

	function plural( n, one, many ) { return n + ' ' + ( n === 1 ? one : many ); }

	function buildExportSummary( data ) {
		var s = data && data.stats;
		if ( ! s ) { return cfg.i18n.done; }
		var parts = [ '✅ Export complete' ];
		parts.push( plural( s.post_count || 0, 'record', 'records' ) );
		if ( s.include_media ) {
			if ( s.media_count > 0 ) {
				var media = plural( s.media_count, 'image', 'images' );
				if ( s.media_files && s.media_files !== s.media_count ) {
					media += ' (' + s.media_files + ' files incl. sizes)';
				}
				parts.push( media );
			} else {
				parts.push( 'no media referenced' );
			}
		} else {
			parts.push( 'media not included' );
		}
		var msg = parts.join( '  •  ' );
		if ( s.filesize_h ) { msg += '  —  ' + s.filesize_h; }
		return msg;
	}

	function renderRecentDownloads( downloads ) {
		var $wrap = $( '#stagekitwp-io-recent-downloads' );
		if ( ! $wrap.length ) { return; }
		if ( ! downloads || ! downloads.length ) {
			$wrap.html( '<h2>Recent Downloads</h2><p class="description">Exports you generate here will appear here so you can download them again later.</p>' );
			return;
		}
		var items = downloads.map( function ( item ) {
			var label = item.filename || 'download';
			var date = item.created_at ? ' — ' + new Date( item.created_at * 1000 ).toLocaleString() : '';
			return '<li><a href="' + escHtml( item.download_url || '' ) + '" target="_blank" rel="noopener">' + escHtml( label ) + '</a><span class="description">' + escHtml( date ) + '</span></li>';
		} ).join( '' );
		$wrap.html( '<h2>Recent Downloads</h2><ul id="stagekitwp-io-recent-download-list">' + items + '</ul>' );
	}

	$( '#stagekitwp-io-export-form' ).on( 'submit', function ( e ) {
		e.preventDefault();
		var $s = $( '#stagekitwp-io-export-status' ), $b = $( '#stagekitwp-io-export-btn' );
		var checked = $( '.stagekitwp-io-module-table input[type="checkbox"]:checked' );
		if ( ! checked.length ) { setStatus( $s, 'Please select at least one module.', 'error' ); return; }
		$b.prop( 'disabled', true ).text( cfg.i18n.exporting );
		setStatus( $s, cfg.i18n.exporting, '' );
		var includeMedia = $( '#stagekitwp-io-include-media' ).length ? ( $( '#stagekitwp-io-include-media' ).is( ':checked' ) ? '1' : '0' ) : '1';
		$.post( cfg.ajaxUrl, { action:'stagekitwp_import_export_export', nonce:cfg.nonce, include_media: includeMedia, modules: checked.map( function () { return this.value; } ).get().join( ',' ) } )
		.done( function ( r ) {
			if ( r.success ) {
				setStatus( $s, buildExportSummary( r.data ), 'success' );
				var downloadUrl = ( r.data && r.data.download_url ) ? r.data.download_url : '';
				if ( downloadUrl ) {
					window.open( downloadUrl, '_blank', 'noopener,noreferrer' );
				}
				if ( r.data && r.data.history ) {
					renderRecentDownloads( r.data.history );
				}
			} else { setStatus( $s, ( r.data && r.data.message ) || cfg.i18n.error, 'error' ); }
		} )
		.fail( function () { setStatus( $s, cfg.i18n.error, 'error' ); } )
		.always( function () { $b.prop( 'disabled', false ).text( 'Export Selected Modules' ); } );
	} );

	// ── Bundle helpers ────────────────────────────────────────────────────────

	function buildBundleFD( isDryRun ) {
		var fd = new FormData();
		fd.append( 'nonce', cfg.nonce ); fd.append( 'conflict', $( '#stagekitwp-io-conflict' ).val() );
		fd.append( 'dry_run', isDryRun ? '1' : '' );
		var mv = 'all';
		if ( $( '#stagekitwp-io-modules-custom' ).is( ':checked' ) ) {
			mv = $( 'input[name="module_ids[]"]:checked' ).map( function () { return this.value; } ).get().join( ',' );
		}
		fd.append( 'modules', mv );
		var inner = $( '.stagekitwp-io-tabs--inner .stagekitwp-io-tab--active' ).data( 'tab' );
		if ( inner === 'upload' ) {
			var fi = $( '#stagekitwp-io-file-input' )[0];
			if ( ! fi.files.length ) { return null; }
			fd.append( 'stagekitwp_import_export_file', fi.files[0] );
		} else {
			var url = $( '#stagekitwp-io-remote-url' ).val().trim();
			if ( ! url ) { return null; }
			fd.append( 'remote_url', url );
		}
		return fd;
	}

	// ── Dry-run (bundle) ───────────────────────────────────────────────────────

	$( '#stagekitwp-io-dry-run-btn' ).on( 'click', function () {
		var $s = $( '#stagekitwp-io-import-status' ), $r = $( '#stagekitwp-io-dry-run-results' );
		var fd = buildBundleFD( true ); if ( ! fd ) { setStatus( $s, 'Please choose a file or URL.', 'error' ); return; }
		fd.append( 'action', 'stagekitwp_import_export_dry_run' );
		$( this ).prop( 'disabled', true ).text( 'Analysing…' );
		setStatus( $s, 'Analysing bundle…', '' ); $r.hide();
		$.ajax( { url:cfg.ajaxUrl, type:'POST', data:fd, processData:false, contentType:false } )
		.done( function ( r ) {
			if ( r.success ) { $( '#stagekitwp-io-status-message' ).text( '🔍 Dry-run preview – no data was changed.' ); $r.html( buildDryRunTable( r.data ) ).show(); $s.show(); }
			else { setStatus( $s, ( r.data && r.data.message ) || cfg.i18n.error, 'error' ); }
		} )
		.fail( function () { setStatus( $s, cfg.i18n.error, 'error' ); } )
		.always( function () { $( '#stagekitwp-io-dry-run-btn' ).prop( 'disabled', false ).text( '🔍 Dry Run (preview only)' ); } );
	} );

	// ── Bundle import ──────────────────────────────────────────────────────────

	$( '#stagekitwp-io-import-form' ).on( 'submit', function ( e ) {
		e.preventDefault();
		var $s = $( '#stagekitwp-io-import-status' ), $b = $( '#stagekitwp-io-import-btn' );
		var fd = buildBundleFD( false ); if ( ! fd ) { setStatus( $s, 'Please choose a file or URL.', 'error' ); return; }
		fd.append( 'action', 'stagekitwp_import_export_import' );
		$b.prop( 'disabled', true ).text( cfg.i18n.importing );
		setStatus( $s, cfg.i18n.importing, '' );
		$( '#stagekitwp-io-progress-wrap, #stagekitwp-io-dry-run-results' ).hide();
		$.ajax( { url:cfg.ajaxUrl, type:'POST', data:fd, processData:false, contentType:false } )
		.done( function ( r ) {
			if ( ! r.success ) { setStatus( $s, ( r.data && r.data.message ) || cfg.i18n.error, 'error' ); $b.prop( 'disabled', false ).text( 'Start Import' ); return; }
			if ( r.data.async ) { $( '#stagekitwp-io-progress-wrap' ).show(); pollProgress( r.data.job_id, $b, $s ); }
			else { var d = r.data.summary; setStatus( $s, '✅ Done. Created: ' + ( d.imported||0 ) + ' | Skipped: ' + ( d.skipped||0 ) + ' | Errors: ' + ( d.errors ? d.errors.length : 0 ), 'success' ); $b.prop( 'disabled', false ).text( 'Start Import' ); }
		} )
		.fail( function () { setStatus( $s, cfg.i18n.error, 'error' ); $b.prop( 'disabled', false ).text( 'Start Import' ); } );
	} );

	// ── CSV helpers ──────────────────────────────────────────────────────────

	function csvSetStep( n ) {
		$( '#stagekitwp-io-csv-steps li' ).each( function ( i ) {
			$( this ).removeClass( 'is-active is-done' );
			if ( i + 1 < n )       { $( this ).addClass( 'is-done' ); }
			else if ( i + 1 === n ) { $( this ).addClass( 'is-active' ); }
		} );
	}

	function csvSpinner( show, msg ) {
		var $s = $( '#stagekitwp-io-csv-spinner' );
		if ( show ) { $( '#stagekitwp-io-csv-spinner-msg' ).text( msg || 'Processing…' ); $s.addClass( 'is-visible' ); }
		else        { $s.removeClass( 'is-visible' ); }
	}

	function csvClearResults() {
		$( '#stagekitwp-io-csv-banner' ).hide().removeClass( 'is-preview is-success is-error' );
		$( '#stagekitwp-io-csv-summary' ).hide().empty();
		$( '#stagekitwp-io-csv-preview-wrap' ).hide();
		$( '#stagekitwp-io-csv-preview-tbody' ).empty();
		$( '#stagekitwp-io-csv-errors' ).hide().empty();
	}

	function csvShowBanner( type, title, sub ) {
		$( '#stagekitwp-io-csv-banner' ).removeClass( 'is-preview is-success is-error' ).addClass( 'is-' + type ).show();
		$( '#stagekitwp-io-csv-banner-title' ).text( title );
		$( '#stagekitwp-io-csv-banner-sub'   ).text( sub || '' );
	}

	function csvStat( n, label, mod ) {
		return '<div class="stagekitwp-io-stat stagekitwp-io-stat--' + mod + '"><span class="stagekitwp-io-stat__number">' + n +
			'</span><span class="stagekitwp-io-stat__label">' + escHtml( label ) + '</span></div>';
	}

	function csvShowStats( d, isDryRun ) {
		var created = isDryRun ? ( d.would_create || 0 ) : ( d.imported || 0 );
		var updated = isDryRun ? ( d.would_update || 0 ) : ( d.updated  || 0 );
		var skipped = isDryRun ? ( d.would_skip   || 0 ) : ( d.skipped  || 0 );
		var seasons = d.seasons_created || 0;
		var errors  = d.errors ? d.errors.length : 0;
		var html = csvStat( created, isDryRun ? 'Would Create' : 'Created', 'create' );
		if ( updated ) { html += csvStat( updated, isDryRun ? 'Would Update' : 'Updated', 'update' ); }
		if ( skipped ) { html += csvStat( skipped, isDryRun ? 'Would Skip'   : 'Skipped',  'skip'   ); }
		if ( seasons ) {
			var seasonLabel = ( seasons === 1 ? 'Season' : 'Seasons' ) + ( isDryRun ? ' to Create' : ' Created' );
			var seasonNames = d.seasons_to_create || [];
			var namesTooltip = seasonNames.length
				? ' title="' + seasonNames.map( function(s){ return escHtml(s); } ).join( '&#10;' ) + '"'
				: '';
			var namesList = seasonNames.length
				? '<ul class="stagekitwp-io-stat__list">' + seasonNames.map( function(s){ return '<li>' + escHtml(s) + '</li>'; } ).join('') + '</ul>'
				: '';
			html += '<div class="stagekitwp-io-stat stagekitwp-io-stat--season"' + namesTooltip + '>' +
				'<span class="stagekitwp-io-stat__number">' + seasons + '</span>' +
				'<span class="stagekitwp-io-stat__label">' + seasonLabel + '</span>' +
				namesList +
				'</div>';
		}
		if ( errors  ) { html += csvStat( errors,  errors === 1 ? 'Error' : 'Errors', 'error' ); }
		$( '#stagekitwp-io-csv-summary' ).html( html ).show();
	}

	function csvShowPreviewTable( previews ) {
		var pills = { create:'<span class="stagekitwp-io-pill stagekitwp-io-pill--create">Create</span>', update:'<span class="stagekitwp-io-pill stagekitwp-io-pill--update">Update</span>', skip:'<span class="stagekitwp-io-pill stagekitwp-io-pill--skip">Skip</span>' };
		var rows = previews.map( function ( p ) {
			var sLabel = p.season || '—';
			var seasonHtml;
			if ( sLabel.indexOf( '⚠' ) !== -1 ) {
				seasonHtml = escHtml( sLabel.replace( /⚠️.*$/, '' ).trim() ) + ' <span class="stagekitwp-io-pill stagekitwp-io-pill--season">New</span>';
			} else {
				seasonHtml = escHtml( sLabel );
			}
			return '<tr class="row--' + ( p.action || 'create' ) + '">' +
				'<td class="col-row">' + p.row + '</td>' +
				'<td><strong>' + escHtml( p.title ) + '</strong></td>' +
				'<td class="col-action">' + ( pills[ p.action ] || escHtml( p.action ) ) + '</td>' +
				'<td class="col-season">' + seasonHtml + '</td>' +
				'<td>' + escHtml( p.time_slot  || '' ) + '</td>' +
				'<td>' + escHtml( p.show_dates || '' ) + '</td>' +
				'<td>' + escHtml( p.venue      || '' ) + '</td></tr>';
		} ).join( '' );
		$( '#stagekitwp-io-csv-preview-tbody' ).html( rows );
		$( '#stagekitwp-io-csv-preview-wrap' ).show();
	}

	function csvShowErrors( errors, extraItems ) {
		var all = ( errors || [] ).slice();
		if ( extraItems ) { all = all.concat( extraItems ); }
		if ( ! all.length ) { return; }
		$( '#stagekitwp-io-csv-errors' ).html(
			all.map( function ( e ) { return '<li>' + escHtml( e ) + '</li>'; } ).join( '' )
		).show();
	}

	// Extract a useful error message from a failed AJAX response (may be JSON or raw HTML).
	function csvParseError( r, jqXHR ) {
		// r is the parsed response object when jQuery parses JSON successfully.
		if ( r && typeof r === 'object' ) {
			var msg  = ( r.data && r.data.message ) || cfg.i18n.error;
			var detail = ( r.data && r.data.detail ) ? r.data.detail : '';
			var code   = ( r.data && r.data.code   ) ? '[' + r.data.code + '] ' : '';
			return { title: code + msg, detail: detail };
		}
		// r is a raw string (non-JSON response — PHP fatal, HTML error page, etc.)
		if ( jqXHR ) {
			var status = jqXHR.status ? 'HTTP ' + jqXHR.status + ': ' : '';
			var raw    = jqXHR.responseText || '';
			// Strip HTML tags to get at any embedded PHP error message.
			var stripped = raw.replace( /<[^>]*>/g, ' ' ).replace( /\s+/g, ' ' ).trim().slice( 0, 300 );
			return {
				title : status + cfg.i18n.error,
				detail: stripped || 'No response body returned from server.',
			};
		}
		return { title: cfg.i18n.error, detail: '' };
	}

	function csvBuildFD( isDryRun ) {
		var fi = $( '#stagekitwp-io-csv-input' )[0];
		if ( ! fi || ! fi.files.length ) { return null; }
		var fd = new FormData();
		fd.append( 'action',    'stagekitwp_import_export_import_csv' );
		fd.append( 'nonce',     cfg.nonce );
		fd.append( 'conflict',  $( '#stagekitwp-io-csv-conflict' ).val() );
		fd.append( 'season_id', $( '#stagekitwp-io-csv-season' ).val() );
		fd.append( 'dry_run',   isDryRun ? '1' : '' );
		fd.append( 'stagekitwp_import_export_csv', fi.files[0] );
		return fd;
	}

	// ── CSV dry run ───────────────────────────────────────────────────────────

	$( '#stagekitwp-io-csv-dry-run-btn' ).on( 'click', function () {
		var fd = csvBuildFD( true );
		if ( ! fd ) { return; }
		var $btn = $( this );
		$btn.prop( 'disabled', true );
		csvClearResults(); csvSpinner( true, 'Analysing CSV rows…' ); csvSetStep( 2 );
		$.ajax( { url:cfg.ajaxUrl, type:'POST', data:fd, processData:false, contentType:false } )
		.done( function ( r, _status, jqXHR ) {
			csvSpinner( false );
			if ( r && r.success ) {
				var d = r.data;
				csvShowBanner( 'is-preview', '🔍 Preview only — no data was changed',
					d.total + ' row' + ( d.total !== 1 ? 's' : '' ) + ' analysed. Review below, then click “Import CSV” to proceed.' );
				csvShowStats( d, true );
				if ( d.previews && d.previews.length ) { csvShowPreviewTable( d.previews ); }
				csvShowErrors( d.errors );
			} else {
				var err = csvParseError( r, jqXHR );
				csvShowBanner( 'is-error', '❌ Preview failed', err.title );
				if ( err.detail ) { csvShowErrors( [], [ err.detail ] ); }
				csvSetStep( 1 );
			}
		} )
		.fail( function ( jqXHR ) {
			csvSpinner( false );
			var err = csvParseError( null, jqXHR );
			csvShowBanner( 'is-error', '❌ Preview failed', err.title );
			if ( err.detail ) { csvShowErrors( [], [ err.detail ] ); }
			csvSetStep( 1 );
		} )
		.always( function () { $btn.prop( 'disabled', false ); } );
	} );

	// ── CSV import ─────────────────────────────────────────────────────────────

	$( '#stagekitwp-io-csv-form' ).on( 'submit', function ( e ) {
		e.preventDefault();
		var fd = csvBuildFD( false );
		if ( ! fd ) { return; }
		var $btn = $( '#stagekitwp-io-csv-btn' );
		$btn.prop( 'disabled', true );
		csvClearResults(); csvSpinner( true, 'Importing shows…' ); csvSetStep( 3 );
		$.ajax( { url:cfg.ajaxUrl, type:'POST', data:fd, processData:false, contentType:false } )
		.done( function ( r, _status, jqXHR ) {
			csvSpinner( false );
			if ( r && r.success ) {
				var d = r.data;
				var hasErrors = d.errors && d.errors.length;
				csvShowBanner(
					hasErrors ? 'is-error' : 'is-success',
					hasErrors ? '⚠️ Import finished with errors' : '✅ Import complete',
					d.total + ' row' + ( d.total !== 1 ? 's' : '' ) + ' processed.' );
				csvShowStats( d, false );
				csvShowErrors( d.errors );
				if ( d.rollback_token ) { csvShowRollback( d.rollback_token ); }
				else { csvHideRollback(); }
			} else {
				var err = csvParseError( r, jqXHR );
				csvShowBanner( 'is-error', '❌ Import failed', err.title );
				if ( err.detail ) { csvShowErrors( [], [ err.detail ] ); }
				csvSetStep( 1 );
			}
		} )
		.fail( function ( jqXHR ) {
			csvSpinner( false );
			var err = csvParseError( null, jqXHR );
			csvShowBanner( 'is-error', '❌ Import failed', err.title );
			if ( err.detail ) { csvShowErrors( [], [ err.detail ] ); }
			csvSetStep( 1 );
		} )
		.always( function () { $btn.prop( 'disabled', false ); } );
	} );

	// ── CSV rollback ───────────────────────────────────────────────────────────

	var _rollbackToken   = null;
	var _rollbackTimer   = null;
	var _rollbackExpires = 0;

	function csvShowRollback( token ) {
		_rollbackToken   = token;
		_rollbackExpires = Date.now() + 3600 * 1000;
		$( '#stagekitwp-io-csv-rollback-panel' ).show();
		csvTickRollback();
		if ( _rollbackTimer ) { clearInterval( _rollbackTimer ); }
		_rollbackTimer = setInterval( csvTickRollback, 30000 );
	}

	function csvHideRollback() {
		$( '#stagekitwp-io-csv-rollback-panel' ).hide();
		_rollbackToken = null;
		if ( _rollbackTimer ) { clearInterval( _rollbackTimer ); _rollbackTimer = null; }
	}

	function csvTickRollback() {
		var remaining = Math.max( 0, Math.round( ( _rollbackExpires - Date.now() ) / 60000 ) );
		if ( remaining <= 0 ) { csvHideRollback(); }
		else { $( '#stagekitwp-io-rollback-countdown' ).text( 'Expires in ' + remaining + ' min' ); }
	}

	$( '#stagekitwp-io-csv-rollback-btn' ).on( 'click', function () {
		if ( ! _rollbackToken ) { return; }
		if ( ! confirm( 'Undo this import? Created shows and seasons will be permanently deleted. Updated shows will be restored.' ) ) { return; }
		var $btn = $( this ).prop( 'disabled', true ).text( 'Undoing…' );
		$.post( cfg.ajaxUrl, { action: 'stagekitwp_import_export_csv_rollback', nonce: cfg.nonce, rollback_token: _rollbackToken } )
		.done( function ( r ) {
			if ( r && r.success ) {
				var d = r.data;
				csvHideRollback();
				csvShowBanner( 'is-preview', '↩ Import undone',
					'Deleted ' + d.deleted_shows + ' show' + ( d.deleted_shows !== 1 ? 's' : '' ) +
					' and ' + d.deleted_seasons + ' season' + ( d.deleted_seasons !== 1 ? 's' : '' ) +
					( d.restored_shows ? ', restored ' + d.restored_shows + ' show' + ( d.restored_shows !== 1 ? 's' : '' ) : '' ) + '.'
				);
				$( '#stagekitwp-io-csv-summary' ).hide();
				if ( d.errors && d.errors.length ) { csvShowErrors( d.errors ); }
			} else {
				var err = csvParseError( r, null );
				alert( 'Rollback failed: ' + err.title + ( err.detail ? '\n' + err.detail : '' ) );
				$btn.prop( 'disabled', false ).text( 'Undo Import' );
			}
		} )
		.fail( function ( jqXHR ) {
			var err = csvParseError( null, jqXHR );
			alert( 'Rollback failed: ' + err.title );
			$btn.prop( 'disabled', false ).text( 'Undo Import' );
		} );
	} );

	// ── Remap form ─────────────────────────────────────────────────────────────

	$( '#stagekitwp-io-remap-form' ).on( 'submit', function ( e ) {
		e.preventDefault();
		var $s = $( '#stagekitwp-io-remap-status' ), $b = $( '#stagekitwp-io-remap-btn' );
		var data = $( this ).serializeArray(); data.push( { name:'action', value:'stagekitwp_import_export_save_remap' } );
		$b.prop( 'disabled', true ).text( 'Saving…' );
		$.post( cfg.ajaxUrl, data )
		.done( function ( r ) {
			if ( r.success ) { setStatus( $s, '✅ ' + r.data.message, 'success' ); setTimeout( function () { location.reload(); }, 1200 ); }
			else { setStatus( $s, ( r.data && r.data.message ) || cfg.i18n.error, 'error' ); }
		} )
		.fail( function () { setStatus( $s, cfg.i18n.error, 'error' ); } )
		.always( function () { $b.prop( 'disabled', false ).text( 'Save Page Mappings' ); } );
	} );

	// ── Async progress polling ─────────────────────────────────────────────────

	function pollProgress( jobId, $btn, $status ) {
		var $bar = $( '#stagekitwp-io-progress-inner' ), $lbl = $( '#stagekitwp-io-progress-label' );
		var poll = setInterval( function () {
			$.get( cfg.ajaxUrl, { action:'stagekitwp_import_export_import_progress', nonce:cfg.nonce, job_id:jobId } )
			.done( function ( r ) {
				if ( ! r.success ) { return; } var p = r.data;
				$bar.css( 'width', ( p.percent||0 ) + '%' );
				var processed = ( p.processed != null ) ? p.processed : ( ( p.imported||0 ) + ( p.skipped||0 ) );
				var totalTxt  = p.total ? ( ' of ' + p.total ) : '';
				$lbl.text( ( p.percent||0 ) + '%  •  ' + processed + totalTxt + ' records  (imported ' + ( p.imported||0 ) + ', skipped ' + ( p.skipped||0 ) + ')' );
				if ( p.status === 'complete' ) {
					clearInterval( poll ); $( '#stagekitwp-io-status-message' ).text( cfg.i18n.done );
					$bar.css( 'width','100%' ); $status.addClass( 'is-success' ); $btn.prop( 'disabled', false ).text( 'Start Import' );
				} else if ( p.status === 'failed' ) {
					clearInterval( poll ); setStatus( $status, 'Import failed: ' + ( p.errors[0] || 'Unknown' ), 'error' ); $btn.prop( 'disabled', false ).text( 'Start Import' );
				}
			} );
		}, 1200 );
	}

	// ── Clear Data (Purge) page ───────────────────────────────────────────────────

	if ( $( '#stagekitwp-io-purge-tabs' ).length ) {

		// Tab switching.
		$( '#stagekitwp-io-purge-tabs' ).on( 'click', '.stagekitwp-io-tab', function () {
			var tab = $( this ).data( 'purge-tab' );
			$( '#stagekitwp-io-purge-tabs .stagekitwp-io-tab' ).removeClass( 'stagekitwp-io-tab--active' );
			$( this ).addClass( 'stagekitwp-io-tab--active' );
			$( '.stagekitwp-io-purge-panel' ).removeClass( 'is-active' );
			$( '#stagekitwp-io-purge-tab-' + tab ).addClass( 'is-active' );
		} );

		// — Helpers —

		function purgeSpinner( show, msg ) {
			var $s = $( '#stagekitwp-io-purge-spinner' );
			if ( show ) { $( '#stagekitwp-io-purge-spinner-msg' ).text( msg || 'Working…' ); $s.addClass( 'is-visible' ); }
			else        { $s.removeClass( 'is-visible' ); }
		}

		// Update every count-badge and count-cell that matches a CPT slug.
		function applyCountResults( results ) {
			var total = 0;
			$.each( results, function ( cpt, n ) {
				total += n;
				var cls = n > 0 ? 'has-posts' : 'no-posts';
				$( '.stagekitwp-io-count-badge[data-cpt="' + cpt + '"]' ).each( function () {
					$( this )
						.text( n )
						.removeClass( 'has-posts no-posts' )
						.addClass( cls );
				} );
				// count-cell variant (full-ecosystem table).
				$( '#stagekitwp-io-purge-all-counts tr[data-cpt="' + cpt + '"] .stagekitwp-io-count-cell' ).html(
					'<span class="stagekitwp-io-count-badge ' + cls + '">' + n + '</span>'
				);
			} );
			return total;
		}

		// Show a result message inside a container.
		function purgeResult( $el, type, msg ) {
			$el.removeClass( 'is-success is-error is-info' ).addClass( 'is-' + type ).html( msg );
		}

		// Generic AJAX call for count or delete.
		function purgeRequest( scope, dryRun, onDone, onFail ) {
			$.post( cfg.ajaxUrl, {
				action:  'stagekitwp_import_export_purge',
				nonce:   cfg.nonce,
				scope:   scope,
				dry_run: dryRun ? '1' : ''
			} )
			.done( function ( r ) {
				if ( r && r.success ) { onDone( r.data ); }
				else {
					var msg = ( r && r.data && r.data.message ) || cfg.i18n.error;
					onFail( msg );
				}
			} )
			.fail( function ( jqXHR ) {
				onFail( 'HTTP ' + jqXHR.status + ': ' + cfg.i18n.error );
			} );
		}

		// Format deleted-posts summary string from a results map.
		function deleteSummary( results, errors ) {
			var lines = [];
			$.each( results, function ( cpt, n ) {
				if ( n > 0 ) { lines.push( n + ' ' + cpt ); }
			} );
			var msg = lines.length
				? '✅ Deleted: ' + lines.join( ', ' ) + '.'
				: '✅ Nothing to delete — all CPTs were already empty.';
			if ( errors && errors.length ) {
				msg += '<br><span style="color:#d63638">⚠️ ' + errors.map( escHtml ).join( '; ' ) + '</span>';
			}
			return msg;
		}

		// — Progress bar helpers (batched delete) —

		// Resolve the batch size for a delete run: the on-page override field if
		// present and valid, otherwise the site-configured default from settings.
		function purgeBatchSize() {
			var $f = $( '#stagekitwp-io-purge-batch-size' );
			var n  = $f.length ? parseInt( $f.val(), 10 ) : NaN;
			if ( isNaN( n ) || n <= 0 ) {
				n = parseInt( cfg.batchSize, 10 ) || 50;
			}
			return Math.max( 10, Math.min( 500, n ) );
		}

		function progressShow( show, label ) {
			var $p = $( '#stagekitwp-io-purge-progress' );
			if ( show ) {
				$( '#stagekitwp-io-purge-progress-label' ).text( label || 'Deleting…' );
				progressUpdate( 0, 0, 0 );
				$( '#stagekitwp-io-purge-progress-stats' ).empty();
				$p.prop( 'hidden', false );
			} else {
				$p.prop( 'hidden', true );
			}
		}

		function progressUpdate( done, total, batch ) {
			var pct = total > 0 ? Math.min( 100, Math.round( ( done / total ) * 100 ) ) : 0;
			$( '#stagekitwp-io-purge-progress-bar' ).css( 'width', pct + '%' );
			$( '#stagekitwp-io-purge-progress-pct' ).text( pct + '%' );
			$( '#stagekitwp-io-purge-progress-track' ).attr( 'aria-valuenow', pct );
			if ( total > 0 ) {
				$( '#stagekitwp-io-purge-progress-stats' ).text(
					done + ' of ' + total + ' deleted' +
					( batch ? '  (–' + batch + ' this batch)' : '' )
				);
			}
		}

		// Run a full batched delete for a scope: count first, then loop batches
		// until the server reports done. Keeps each request short so PHP never
		// times out, and drives the progress bar the whole way.
		// accumulated = running per-CPT deleted totals for the final summary.
		function runBatchedPurge( scope, label, onDone, onFail ) {
			var total       = 0;
			var deleted     = 0;
			var accumulated = {};
			var allErrors   = [];

			progressShow( true, label );

			// Step 1 — count (dry run) so we know the denominator.
			purgeRequest( scope, true,
				function ( d ) {
					total = 0;
					$.each( d.results || {}, function ( k, n ) { total += n; } );
					if ( total === 0 ) {
						progressShow( false );
						onDone( { results: {}, errors: [], total: 0 } );
						return;
					}
					progressUpdate( 0, total, 0 );
					nextBatch();
				},
				function ( msg ) { progressShow( false ); onFail( msg ); }
			);

			// Step 2 — delete one batch, then recurse until done.
			function nextBatch() {
				$.post( cfg.ajaxUrl, {
					action:     'stagekitwp_import_export_purge_batch',
					nonce:      cfg.nonce,
					scope:      scope,
					batch_size: purgeBatchSize()
				} )
				.done( function ( r ) {
					if ( ! r || ! r.success ) {
						var em = ( r && r.data && r.data.message ) || cfg.i18n.error;
						progressShow( false );
						onFail( em );
						return;
					}
					var d = r.data;
					deleted += d.deleted_this_batch || 0;
					$.each( d.deleted_by_type || {}, function ( k, n ) {
						accumulated[ k ] = ( accumulated[ k ] || 0 ) + n;
					} );
					if ( d.errors && d.errors.length ) { allErrors = d.errors; }

					// Prefer the exact remaining count from the server.
					var shownDeleted = total - ( d.remaining || 0 );
					if ( shownDeleted < deleted ) { shownDeleted = deleted; }
					progressUpdate( shownDeleted, total, d.deleted_this_batch || 0 );

					// Live-update the on-page count badges from remaining_by_type.
					if ( d.remaining_by_type ) { applyCountResults( d.remaining_by_type ); }

					if ( d.done ) {
						progressUpdate( total, total, 0 );
						setTimeout( function () {
							progressShow( false );
							onDone( { results: accumulated, errors: allErrors, total: deleted } );
						}, 400 );
					} else {
						nextBatch(); // keep going — request stays short each time
					}
				} )
				.fail( function ( jqXHR ) {
					progressShow( false );
					onFail( 'HTTP ' + jqXHR.status + ': ' + cfg.i18n.error );
				} );
			}
		}

		// Require user to type 'DELETE' to confirm a destructive operation.
		function confirmDelete( label ) {
			var answer = window.prompt(
				'This will PERMANENTLY delete all "' + label + '" posts.\n\nType DELETE to confirm:'
			);
			return answer !== null && answer.trim().toUpperCase() === 'DELETE';
		}

		// — Full Ecosystem —

		$( '#stagekitwp-io-purge-all-preview-btn' ).on( 'click', function () {
			purgeSpinner( true, 'Counting all TM posts…' );
			purgeRequest( 'all', true,
				function ( d ) {
					purgeSpinner( false );
					var total = applyCountResults( d.results );
					$( '#stagekitwp-io-purge-all-total' ).text( total );
					$( '#stagekitwp-io-purge-all-btn' ).prop( 'disabled', total === 0 );
					purgeResult( $( '#stagekitwp-io-purge-all-result' ), 'info',
						total + ' posts found across all TM CPTs. Click “Delete All TM Data” to remove them.' );
				},
				function ( msg ) { purgeSpinner( false ); purgeResult( $( '#stagekitwp-io-purge-all-result' ), 'error', msg ); }
			);
		} );

		$( '#stagekitwp-io-purge-all-btn' ).on( 'click', function () {
			if ( ! confirmDelete( 'ALL StageKitWP ecosystem' ) ) { return; }
			var $btn = $( this ).prop( 'disabled', true );
			runBatchedPurge( 'all', 'Deleting all TM posts…',
				function ( d ) {
					$( '#stagekitwp-io-purge-all-total' ).text( 0 );
					purgeResult( $( '#stagekitwp-io-purge-all-result' ), 'success', deleteSummary( d.results, d.errors ) );
				},
				function ( msg ) { $btn.prop( 'disabled', false ); purgeResult( $( '#stagekitwp-io-purge-all-result' ), 'error', msg ); }
			);
		} );

		// — By Plugin —

		$( document ).on( 'click', '[data-plugin-preview]', function () {
			var plugin = $( this ).data( 'plugin-preview' );
			var $card  = $( this ).closest( '.stagekitwp-io-purge-plugin-card' );
			purgeSpinner( true, 'Counting…' );
			purgeRequest( plugin, true,
				function ( d ) {
					purgeSpinner( false );
					var total = applyCountResults( d.results );
					$card.find( '.stagekitwp-io-plugin-total-num' ).text( total );
					$card.find( '[data-plugin-delete]' ).prop( 'disabled', total === 0 );
					purgeResult( $card.find( '.stagekitwp-io-purge-plugin-result' ), 'info',
						total + ' posts. Click Delete to remove.' );
				},
				function ( msg ) { purgeSpinner( false ); purgeResult( $card.find( '.stagekitwp-io-purge-plugin-result' ), 'error', msg ); }
			);
		} );

		$( document ).on( 'click', '[data-plugin-delete]', function () {
			var plugin = $( this ).data( 'plugin-delete' );
			var label  = $( this ).closest( '.stagekitwp-io-purge-plugin-card' ).find( '.stagekitwp-io-purge-plugin-card__title' ).text();
			if ( ! confirmDelete( label ) ) { return; }
			var $card = $( this ).closest( '.stagekitwp-io-purge-plugin-card' );
			var $btn  = $( this ).prop( 'disabled', true );
			runBatchedPurge( plugin, 'Deleting ' + label + ' posts…',
				function ( d ) {
					$card.find( '.stagekitwp-io-plugin-total-num' ).text( 0 );
					purgeResult( $card.find( '.stagekitwp-io-purge-plugin-result' ), 'success', deleteSummary( d.results, d.errors ) );
				},
				function ( msg ) { $btn.prop( 'disabled', false ); purgeResult( $card.find( '.stagekitwp-io-purge-plugin-result' ), 'error', msg ); }
			);
		} );

		// — By CPT —

		$( document ).on( 'click', '[data-cpt-preview]', function () {
			var cpt   = $( this ).data( 'cpt-preview' );
			var $row  = $( this ).closest( 'tr' );
			purgeSpinner( true, 'Counting ' + cpt + '…' );
			purgeRequest( cpt, true,
				function ( d ) {
					purgeSpinner( false );
					applyCountResults( d.results );
					$row.find( '[data-cpt-delete]' ).prop( 'disabled', ( d.results[ cpt ] || 0 ) === 0 );
					purgeResult( $( '#stagekitwp-io-purge-cpt-result' ), 'info',
						( d.results[ cpt ] || 0 ) + ' posts in <code>' + escHtml( cpt ) + '</code>. Click Delete to remove.' );
				},
				function ( msg ) { purgeSpinner( false ); purgeResult( $( '#stagekitwp-io-purge-cpt-result' ), 'error', msg ); }
			);
		} );

		$( document ).on( 'click', '[data-cpt-delete]', function () {
			var cpt = $( this ).data( 'cpt-delete' );
			if ( ! confirmDelete( cpt ) ) { return; }
			var $row = $( this ).closest( 'tr' );
			var $btn = $( this ).prop( 'disabled', true );
			runBatchedPurge( cpt, 'Deleting ' + cpt + '…',
				function ( d ) {
					applyCountResults( { [cpt]: 0 } );
					$row.find( '[data-cpt-delete]' ).prop( 'disabled', true );
					purgeResult( $( '#stagekitwp-io-purge-cpt-result' ), 'success', deleteSummary( d.results, d.errors ) );
				},
				function ( msg ) { $btn.prop( 'disabled', false ); purgeResult( $( '#stagekitwp-io-purge-cpt-result' ), 'error', msg ); }
			);
		} );

	} // end purge page guard

} )( jQuery, window.stagekitwpImportExport || {} );
