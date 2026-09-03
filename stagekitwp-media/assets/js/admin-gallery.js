( function ( $ ) {
	'use strict';

	var cfg = window.skwpmGallery || { providers: [], folders: [], i18n: {} };
	var i18n = cfg.i18n || {};

	function loadInitialItems() {
		var raw = $( '#skwpm-gallery-editor' ).attr( 'data-items' ) || '[]';
		try {
			var parsed = JSON.parse( raw );
			return Array.isArray( parsed ) ? parsed : [];
		} catch ( e ) {
			return [];
		}
	}

	var selected = loadInitialItems();
	var gphotos  = { sessionId: null, timer: null };

	function card( item, buttonLabel, onClick ) {
		var $card = $( '<div class="skwpm-card"></div>' );
		if ( item.thumb ) {
			$card.append( $( '<img>' ).attr( 'src', item.thumb ).attr( 'alt', item.title || '' ) );
		}
		if ( 'video' === item.type ) {
			$card.append( $( '<span class="skwpm-badge">&#9654;</span>' ) );
		}
		if ( item.title ) {
			$card.append( $( '<div class="skwpm-card-title"></div>' ).text( item.title ) );
		}
		var $btn = $( '<button type="button" class="button"></button>' ).text( buttonLabel );
		$btn.on( 'click', function () {
			onClick( $btn );
		} );
		$card.append( $btn );
		return $card;
	}

	function renderSelected() {
		var $wrap = $( '#skwpm-selected-items' ).empty();
		if ( ! selected.length ) {
			$wrap.append( $( '<p class="description"></p>' ).text( i18n.noneSelected || 'No items added yet.' ) );
			return;
		}
		selected.forEach( function ( item, index ) {
			$wrap.append( card( item, i18n.remove || 'Remove', function () {
				selected.splice( index, 1 );
				sync();
			} ) );
		} );
	}

	function sync() {
		$( '#skwpm_items_json' ).val( JSON.stringify( selected ) );
		renderSelected();
	}

	function renderProviders() {
		var $select = $( '#skwpm-provider' ).empty();
		( cfg.providers || [] ).forEach( function ( p ) {
			var label = p.label + ( p.configured ? '' : ' (' + ( i18n.notConfigured || 'not configured' ) + ')' );
			$select.append( $( '<option></option>' ).val( p.slug ).text( label ).prop( 'disabled', ! p.configured ) );
		} );
		$select.find( 'option:enabled' ).first().prop( 'selected', true );
	}

	function isGooglePhotos() {
		return 'google-photos' === $( '#skwpm-provider' ).val();
	}

	function isStageKitMedia() {
		return 'stagekit-media' === $( '#skwpm-provider' ).val();
	}

	function populateFolderDropdown( nodes, $select, depth ) {
		var prefix = '— '.repeat( depth );
		nodes.forEach( function ( node ) {
			$select.append( $( '<option></option>' ).val( node.id ).text( prefix + node.name ) );
			if ( node.children && node.children.length ) {
				populateFolderDropdown( node.children, $select, depth + 1 );
			}
		} );
	}

	function updateProviderUI() {
		var $hint    = $( '#skwpm-provider-hint' );
		var provider = $( '#skwpm-provider' ).val();
		var hintKey  = { youtube: 'data-hint-youtube', 'google-drive': 'data-hint-google-drive' }[ provider ];
		if ( hintKey ) {
			$hint.text( $hint.attr( hintKey ) || '' ).show();
		} else {
			$hint.hide();
		}

		stopGPhotosPolling();
		$( '#skwpm-gphotos-status' ).text( '' );
		$( '#skwpm-search-row' ).toggle( ! isGooglePhotos() );
		$( '#skwpm-gphotos-row' ).toggle( isGooglePhotos() );
		$( '#skwpm-search-results' ).empty();

		// Add/toggle folder select for StageKit Media
		var $folderSelect = $( '#skwpm-gallery-folder-select' );
		if ( isStageKitMedia() ) {
			if ( ! $folderSelect.length ) {
				$folderSelect = $( '<select id="skwpm-gallery-folder-select" style="margin-right:6px;"></select>' );
				$folderSelect.append( $( '<option value="all"></option>' ).text( i18n.allFolders || 'All Folders' ) );
				populateFolderDropdown( cfg.folders || [], $folderSelect, 0 );
				$folderSelect.on( 'change', function () {
					runSearch();
				} );
				$( '#skwpm-provider' ).after( $folderSelect );
			}
			$folderSelect.show();
			// Auto search StageKit Media on select
			runSearch();
		} else {
			if ( $folderSelect.length ) {
				$folderSelect.hide();
			}
		}
	}

	function renderResults( items ) {
		var $wrap = $( '#skwpm-search-results' ).empty();
		if ( ! items.length ) {
			$wrap.append( $( '<p class="description"></p>' ).text( i18n.noResults || 'No results.' ) );
			return;
		}
		items.forEach( function ( item ) {
			if ( needsImport( item ) ) {
				$wrap.append( card( item, i18n.add || 'Add', function ( $btn ) {
					importPickedItem( item, $btn );
				} ) );
				return;
			}
			$wrap.append( card( item, i18n.add || 'Add', function () {
				selected.push( item );
				sync();
			} ) );
		} );
	}

	function showNotice( message ) {
		$( '#skwpm-notice' ).text( message || '' ).toggle( !! message );
	}

	function runSearch() {
		var provider = $( '#skwpm-provider' ).val();
		var query    = $.trim( $( '#skwpm-query' ).val() );

		showNotice( '' );

		if ( 'stagekit-media' === provider ) {
			var folderId = $( '#skwpm-gallery-folder-select' ).val() || 'all';
			var restParams = {
				folder_id: folderId,
				search: query,
				per_page: 40,
			};

			$.ajax( {
				url: ( cfg.restUrl || '/wp-json/stagekit-media/v1' ) + '/media',
				method: 'GET',
				data: restParams,
				headers: { 'X-WP-Nonce': cfg.restNonce || '' },
			} ).done( function ( response ) {
				if ( response && response.success ) {
					var mapped = ( response.items || [] ).map( function ( m ) {
						return {
							provider: 'stagekit-media',
							external_id: String( m.id ),
							type: m.type || 'image',
							url: m.file_url,
							thumb: m.thumb_url || m.file_url,
							title: m.title || m.filename,
							author: m.author || '',
							license: m.license || '',
						};
					} );
					renderResults( mapped );
				} else {
					renderResults( [] );
				}
			} ).fail( function () {
				showNotice( i18n.searchFailed || 'Search request failed.' );
			} );
			return;
		}

		// Google Drive can browse recent files with no query; other providers need a keyword.
		if ( ! provider || ( ! query && 'google-drive' !== provider ) ) {
			return;
		}

		$.post( cfg.ajaxUrl, {
			action: 'skwpm_search_media',
			nonce: cfg.nonce,
			provider: provider,
			query: query,
		} ).done( function ( response ) {
			if ( response && response.success ) {
				var items = ( response.data && response.data.items ) || [];
				if ( ! items.length && response.data && response.data.message ) {
					showNotice( response.data.message );
				}
				renderResults( items );
			} else {
				showNotice( ( response && response.data && response.data.message ) || i18n.searchFailed );
				renderResults( [] );
			}
		} ).fail( function () {
			showNotice( i18n.searchFailed || 'Search request failed.' );
		} );
	}

	function stopGPhotosPolling() {
		if ( gphotos.timer ) {
			clearTimeout( gphotos.timer );
			gphotos.timer = null;
		}
	}

	function pollGPhotosSession( pollInterval ) {
		$.post( cfg.ajaxUrl, {
			action: 'skwpm_gphotos_status',
			nonce: cfg.nonce,
			session_id: gphotos.sessionId,
		} ).done( function ( response ) {
			if ( ! response || ! response.success ) {
				showNotice( ( response && response.data && response.data.message ) || i18n.gphotosStartFailed );
				return;
			}
			if ( response.data.ready ) {
				$( '#skwpm-gphotos-status' ).text( i18n.gphotosReady || '' );
				renderResults( response.data.items || [] );
				return;
			}
			gphotos.timer = setTimeout( function () {
				pollGPhotosSession( pollInterval );
			}, pollInterval * 1000 );
		} ).fail( function () {
			showNotice( i18n.gphotosStartFailed || 'Could not start the Google Photos picker.' );
		} );
	}

	function startGPhotosPicker() {
		showNotice( '' );
		stopGPhotosPolling();
		$( '#skwpm-search-results' ).empty();
		$( '#skwpm-gphotos-status' ).text( '' );

		$.post( cfg.ajaxUrl, {
			action: 'skwpm_gphotos_start',
			nonce: cfg.nonce,
		} ).done( function ( response ) {
			if ( ! response || ! response.success ) {
				showNotice( ( response && response.data && response.data.message ) || i18n.gphotosStartFailed );
				return;
			}
			gphotos.sessionId = response.data.sessionId;
			window.open( response.data.pickerUri, '_blank', 'noopener' );
			$( '#skwpm-gphotos-status' ).text( i18n.gphotosWaiting || '' );
			pollGPhotosSession( response.data.pollInterval || 2 );
		} ).fail( function () {
			showNotice( i18n.gphotosStartFailed || 'Could not start the Google Photos picker.' );
		} );
	}

	/** Google Drive and Google Photos results can't be hot-linked; they must be imported via AJAX before they can be added. */
	function needsImport( item ) {
		return ! item.thumb && ( 'google-photos' === item.provider || 'google-drive' === item.provider );
	}

	function importPickedItem( item, $btn ) {
		$btn.prop( 'disabled', true ).text( i18n.gphotosImporting || 'Importing…' );

		var data = { nonce: cfg.nonce };
		if ( 'google-photos' === item.provider ) {
			data.action = 'skwpm_gphotos_import';
			data.session_id = gphotos.sessionId;
			data.media_item_id = item.external_id;
		} else {
			data.action = 'skwpm_drive_import';
			data.file_id = item.external_id;
		}

		$.post( cfg.ajaxUrl, data ).done( function ( response ) {
			if ( response && response.success && response.data.item ) {
				selected.push( response.data.item );
				sync();
				$btn.closest( '.skwpm-card' ).remove();
			} else {
				showNotice( ( response && response.data && response.data.message ) || i18n.gphotosImportFailed );
				$btn.prop( 'disabled', false ).text( i18n.add || 'Add' );
			}
		} ).fail( function () {
			showNotice( i18n.gphotosImportFailed || 'Could not import that item.' );
			$btn.prop( 'disabled', false ).text( i18n.add || 'Add' );
		} );
	}

	$( function () {
		if ( ! $( '#skwpm-gallery-editor' ).length ) {
			return;
		}
		renderProviders();
		renderSelected();
		updateProviderUI();

		$( '#skwpm-provider' ).on( 'change', updateProviderUI );
		$( '#skwpm-search-btn' ).on( 'click', function ( e ) {
			e.preventDefault();
			runSearch();
		} );
		$( '#skwpm-query' ).on( 'keydown', function ( e ) {
			if ( 13 === e.which ) {
				e.preventDefault();
				runSearch();
			}
		} );
		$( '#skwpm-gphotos-start-btn' ).on( 'click', function ( e ) {
			e.preventDefault();
			startGPhotosPicker();
		} );
	} );
} )( jQuery );
