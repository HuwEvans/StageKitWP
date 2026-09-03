( function ( $ ) {
	'use strict';

	var cfg = window.skwpmMediaManager || { restUrl: '', nonce: '', i18n: {} };
	var i18n = cfg.i18n || {};

	var state = {
		folderId: 0,
		filter: 'all',
		search: '',
		sortBy: 'created_at',
		sortOrder: 'DESC',
		viewMode: 'grid',
		page: 1,
		perPage: 36,
		selectedIds: [],
		activeItem: null,
		folders: [],
		folderMap: {},
		items: [],
		total: 0,
		pages: 1,
		isDragging: false,
	};

	function init() {
		bindEvents();
		loadFolders();
		loadMedia();
	}

	function apiRequest( endpoint, method, data ) {
		return $.ajax( {
			url: cfg.restUrl + '/' + endpoint.replace( /^\//, '' ),
			method: method || 'GET',
			data: method === 'GET' ? data : JSON.stringify( data ),
			contentType: method === 'GET' ? undefined : 'application/json',
			headers: { 'X-WP-Nonce': cfg.nonce },
		} );
	}

	function uploadFile( file, folderId, onProgress ) {
		var formData = new FormData();
		formData.append( 'file', file );
		formData.append( 'folder_id', folderId || state.folderId || 0 );

		return $.ajax( {
			url: cfg.restUrl + '/media/upload',
			method: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			headers: { 'X-WP-Nonce': cfg.nonce },
			xhr: function () {
				var xhr = new window.XMLHttpRequest();
				xhr.upload.addEventListener( 'progress', function ( evt ) {
					if ( evt.lengthComputable && onProgress ) {
						var percent = Math.round( ( evt.loaded / evt.total ) * 100 );
						onProgress( percent );
					}
				}, false );
				return xhr;
			},
		} );
	}

	/* Folders */
	function loadFolders() {
		apiRequest( 'folders?tree=true', 'GET' ).done( function ( res ) {
			if ( res && res.success ) {
				state.folders = res.folders || [];
				state.folderMap = {};
				flattenFolders( state.folders, state.folderMap );
				renderFolderTree();
				updateBreadcrumbs();
			}
		} );
	}

	function flattenFolders( nodes, map ) {
		nodes.forEach( function ( node ) {
			map[ node.id ] = node;
			if ( node.children && node.children.length ) {
				flattenFolders( node.children, map );
			}
		} );
	}

	function renderFolderTree() {
		var $wrap = $( '#skwpm-folder-tree' ).empty();
		var $rootList = $( '<ul class="skwpm-tree-list"></ul>' );

		// Root "Home" node
		var $rootItem = $( '<div class="skwpm-tree-item"></div>' )
			.toggleClass( 'active', state.folderId === 0 && state.filter === 'all' )
			.append( '<span class="dashicons dashicons-admin-home skwpm-tree-icon"></span>' )
			.append( $( '<span class="skwpm-tree-label"></span>' ).text( i18n.rootFolder || 'Home / Root' ) )
			.on( 'click', function () {
				state.folderId = 0;
				state.filter = 'all';
				state.page = 1;
				$( '#skwpm-quick-filters .skwpm-nav-item' ).removeClass( 'active' );
				$( '#skwpm-quick-filters [data-filter="all"]' ).addClass( 'active' );
				renderFolderTree();
				loadMedia();
			} );

		$rootList.append( $( '<li class="skwpm-tree-node"></li>' ).append( $rootItem ) );

		// User folder hierarchy
		state.folders.forEach( function ( node ) {
			$rootList.append( buildTreeNode( node ) );
		} );

		$wrap.append( $rootList );
	}

	function buildTreeNode( node ) {
		var $li = $( '<li class="skwpm-tree-node"></li>' );
		var $item = $( '<div class="skwpm-tree-item"></div>' )
			.toggleClass( 'active', state.folderId === node.id );

		var hasChildren = node.children && node.children.length > 0;
		var $toggle = $( '<span class="skwpm-tree-toggle"></span>' );
		if ( hasChildren ) {
			$toggle.html( '&#9662;' );
		}
		$item.append( $toggle );

		if ( node.color ) {
			$item.append( $( '<span class="skwpm-color-dot"></span>' ).css( 'background-color', node.color ) );
		} else {
			$item.append( '<span class="dashicons dashicons-category skwpm-tree-icon"></span>' );
		}

		$item.append( $( '<span class="skwpm-tree-label"></span>' ).text( node.name ) );

		if ( node.item_count > 0 ) {
			$item.append( $( '<span class="skwpm-tree-badge"></span>' ).text( node.item_count ) );
		}

		$item.on( 'click', function ( e ) {
			if ( $( e.target ).hasClass( 'skwpm-tree-toggle' ) ) {
				$li.children( '.skwpm-tree-children' ).toggle();
				$toggle.html( $li.children( '.skwpm-tree-children' ).is( ':visible' ) ? '&#9662;' : '&#9656;' );
				return;
			}
			state.folderId = node.id;
			state.filter = 'folder';
			state.page = 1;
			$( '#skwpm-quick-filters .skwpm-nav-item' ).removeClass( 'active' );
			renderFolderTree();
			loadMedia();
		} );

		$li.append( $item );

		if ( hasChildren ) {
			var $childList = $( '<ul class="skwpm-tree-children"></ul>' );
			node.children.forEach( function ( child ) {
				$childList.append( buildTreeNode( child ) );
			} );
			$li.append( $childList );
		}

		return $li;
	}

	function updateBreadcrumbs() {
		var $wrap = $( '#skwpm-breadcrumbs' ).empty();

		var $root = $( '<span class="skwpm-crumb"></span>' )
			.text( i18n.allFiles || 'All Files' )
			.toggleClass( 'active', state.folderId === 0 )
			.on( 'click', function () {
				state.folderId = 0;
				state.filter = 'all';
				renderFolderTree();
				loadMedia();
			} );
		$wrap.append( $root );

		if ( state.folderId > 0 && state.folderMap[ state.folderId ] ) {
			var crumbs = getBreadcrumbChain( state.folderId );
			crumbs.forEach( function ( folder, idx ) {
				$wrap.append( '<span class="dashicons dashicons-arrow-right-alt2" style="font-size:12px;width:12px;height:12px;color:#8c8f94;"></span>' );
				var isLast = idx === crumbs.length - 1;
				var $crumb = $( '<span class="skwpm-crumb"></span>' )
					.text( folder.name )
					.toggleClass( 'active', isLast );

				if ( ! isLast ) {
					$crumb.on( 'click', function () {
						state.folderId = folder.id;
						renderFolderTree();
						loadMedia();
					} );
				}
				$wrap.append( $crumb );
			} );
		}
	}

	function getBreadcrumbChain( folderId ) {
		var chain = [];
		var current = state.folderMap[ folderId ];
		while ( current ) {
			chain.unshift( current );
			current = state.folderMap[ current.parent_id ];
		}
		return chain;
	}

	/* Media Fetch & Render */
	function loadMedia() {
		var params = {
			page: state.page,
			per_page: state.perPage,
			orderby: state.sortBy,
			order: state.sortOrder,
		};

		if ( state.search ) {
			params.search = state.search;
		}

		if ( state.folderId > 0 ) {
			params.folder_id = state.folderId;
		} else if ( 'all' !== state.filter ) {
			if ( 'images' === state.filter ) params.type = 'image';
			else if ( 'videos' === state.filter ) params.type = 'video';
			else if ( 'documents' === state.filter ) params.type = 'document';
			else if ( 'synced' === state.filter ) params.provider = 'google-drive,google-photos,dropbox';
		}

		apiRequest( 'media', 'GET', params ).done( function ( res ) {
			if ( res && res.success ) {
				state.items = res.items || [];
				state.total = res.total || 0;
				state.pages = res.pages || 1;
				state.selectedIds = [];
				updateBulkBar();
				renderMediaItems();
				updateBreadcrumbs();
			}
		} );
	}

	function renderMediaItems() {
		var $container = $( '#skwpm-items-container' ).empty();
		var $empty = $( '#skwpm-empty-state' );

		if ( ! state.items.length ) {
			$empty.show();
			$container.hide();
			return;
		}

		$empty.hide();
		$container.show();

		if ( 'grid' === state.viewMode ) {
			$container.removeClass( 'skwpm-items-list' ).addClass( 'skwpm-items-grid' );
			state.items.forEach( function ( item ) {
				$container.append( buildGridCard( item ) );
			} );
		} else {
			$container.removeClass( 'skwpm-items-grid' ).addClass( 'skwpm-items-list' );
			$container.append( buildListTable( state.items ) );
		}
	}

	function buildGridCard( item ) {
		var isSelected = state.selectedIds.indexOf( item.id ) !== -1;
		var $card = $( '<div class="skwpm-card"></div>' )
			.attr( 'data-id', item.id )
			.toggleClass( 'selected', isSelected );

		var $checkbox = $( '<input type="checkbox" class="skwpm-card-checkbox">' )
			.prop( 'checked', isSelected )
			.on( 'click', function ( e ) {
				e.stopPropagation();
				toggleSelection( item.id );
			} );
		$card.append( $checkbox );

		var $preview = $( '<div class="skwpm-card-preview"></div>' );
		if ( 'image' === item.type ) {
			$preview.append( $( '<img>' ).attr( 'src', item.thumb_url || item.file_url ).attr( 'alt', item.title || '' ) );
		} else if ( 'video' === item.type ) {
			$preview.append( $( '<img>' ).attr( 'src', item.thumb_url || item.file_url ) );
			$preview.append( '<span class="skwpm-type-badge">&#9654; Video</span>' );
		} else {
			$preview.append( '<span class="dashicons dashicons-media-document"></span>' );
			$preview.append( '<span class="skwpm-type-badge">' + ( item.type || 'file' ) + '</span>' );
		}
		$card.append( $preview );

		var $info = $( '<div class="skwpm-card-info"></div>' );
		$info.append( $( '<div class="skwpm-card-title"></div>' ).text( item.title || item.filename ) );
		$info.append( $( '<div class="skwpm-card-meta"></div>' ).text( formatBytes( item.file_size ) ) );
		$card.append( $info );

		$card.on( 'click', function ( e ) {
			if ( $( e.target ).is( 'input[type="checkbox"]' ) ) return;
			openInspector( item );
		} );

		return $card;
	}

	function buildListTable( items ) {
		var $table = $( '<table class="skwpm-items-list"></table>' );
		$table.append( '<thead><tr><th style="width:30px;"><input type="checkbox" id="skwpm-table-select-all"></th><th>Preview</th><th>Title / Filename</th><th>Type</th><th>Size</th><th>Date</th></tr></thead>' );

		var $tbody = $( '<tbody></tbody>' );
		items.forEach( function ( item ) {
			var isSelected = state.selectedIds.indexOf( item.id ) !== -1;
			var $tr = $( '<tr></tr>' ).attr( 'data-id', item.id ).toggleClass( 'selected', isSelected );

			var $chk = $( '<input type="checkbox">' ).prop( 'checked', isSelected ).on( 'click', function ( e ) {
				e.stopPropagation();
				toggleSelection( item.id );
			} );
			$tr.append( $( '<td></td>' ).append( $chk ) );

			var $thumb = $( '<td></td>' );
			if ( item.thumb_url ) {
				$thumb.append( $( '<img class="skwpm-list-thumb">' ).attr( 'src', item.thumb_url ) );
			} else {
				$thumb.append( '<span class="dashicons dashicons-media-default"></span>' );
			}
			$tr.append( $thumb );

			$tr.append( $( '<td></td>' ).append( $( '<strong></strong>' ).text( item.title || item.filename ) ) );
			$tr.append( $( '<td></td>' ).text( item.mime_type ) );
			$tr.append( $( '<td></td>' ).text( formatBytes( item.file_size ) ) );
			$tr.append( $( '<td></td>' ).text( item.created_at.substring( 0, 10 ) ) );

			$tr.on( 'click', function () {
				openInspector( item );
			} );

			$tbody.append( $tr );
		} );

		$table.append( $tbody );
		return $table;
	}

	function toggleSelection( id ) {
		var idx = state.selectedIds.indexOf( id );
		if ( idx === -1 ) {
			state.selectedIds.push( id );
		} else {
			state.selectedIds.splice( idx, 1 );
		}
		updateBulkBar();
		$( '.skwpm-card[data-id="' + id + '"], tr[data-id="' + id + '"]' )
			.toggleClass( 'selected', state.selectedIds.indexOf( id ) !== -1 )
			.find( 'input[type="checkbox"]' )
			.prop( 'checked', state.selectedIds.indexOf( id ) !== -1 );
	}

	function updateBulkBar() {
		var count = state.selectedIds.length;
		if ( count > 0 ) {
			$( '#skwpm-bulk-bar' ).show();
			$( '#skwpm-bulk-count' ).text( count + ' ' + ( i18n.itemsSelected || 'selected' ) );
		} else {
			$( '#skwpm-bulk-bar' ).hide();
		}
	}

	/* Inspector Drawer */
	function openInspector( item ) {
		state.activeItem = item;
		var $drawer = $( '#skwpm-inspector' ).show();
		var $content = $( '#skwpm-inspector-content' ).empty();

		// Preview
		var $preview = $( '<div class="skwpm-inspector-preview"></div>' );
		if ( 'image' === item.type ) {
			$preview.append( $( '<img>' ).attr( 'src', item.file_url ) );
		} else if ( 'video' === item.type ) {
			$preview.append( $( '<video controls></video>' ).attr( 'src', item.file_url ) );
		} else {
			$preview.append( '<span class="dashicons dashicons-media-document" style="font-size:64px;width:64px;height:64px;color:#8c8f94;"></span>' );
		}
		$content.append( $preview );

		// Metadata Info Table
		var $meta = $( '<table class="skwpm-meta-table"></table>' );
		$meta.append( '<tr><td>Filename:</td><td>' + escapeHtml( item.filename ) + '</td></tr>' );
		$meta.append( '<tr><td>Filesize:</td><td>' + formatBytes( item.file_size ) + '</td></tr>' );
		if ( item.width && item.height ) {
			$meta.append( '<tr><td>Dimensions:</td><td>' + item.width + ' &times; ' + item.height + ' px</td></tr>' );
		}
		$meta.append( '<tr><td>Type:</td><td>' + escapeHtml( item.mime_type ) + '</td></tr>' );
		$meta.append( '<tr><td>Provider:</td><td>' + escapeHtml( item.provider ) + '</td></tr>' );
		$content.append( $meta );

		// Editable Form
		var $form = $( '<div class="skwpm-form-group"></div>' );
		$form.append( '<label>Title</label><input type="text" id="skwpm-edit-title" value="' + escapeHtml( item.title || '' ) + '">' );
		$form.append( '<label style="margin-top:8px;">Alt Text</label><input type="text" id="skwpm-edit-alt" value="' + escapeHtml( item.alt_text || '' ) + '">' );
		$form.append( '<label style="margin-top:8px;">Caption</label><textarea id="skwpm-edit-caption" rows="2">' + escapeHtml( item.caption || '' ) + '</textarea>' );

		// Folder select dropdown
		var $folderSelect = $( '<select id="skwpm-edit-folder" style="margin-top:4px;"></select>' );
		$folderSelect.append( $( '<option value="0"></option>' ).text( i18n.rootFolder || 'Home / Root' ) );
		populateFolderOptions( state.folders, $folderSelect, item.folder_id, 0 );
		$form.append( $( '<label style="margin-top:8px;">Folder</label>' ) ).append( $folderSelect );
		$content.append( $form );

		// Actions
		var $actions = $( '<div class="skwpm-inspector-actions"></div>' );
		var $saveBtn = $( '<button type="button" class="button button-primary"></button>' )
			.text( i18n.save || 'Save Changes' )
			.on( 'click', function () {
				saveItemChanges( item.id, $saveBtn );
			} );
		var $copyBtn = $( '<button type="button" class="button"></button>' )
			.text( i18n.copyLink || 'Copy URL' )
			.on( 'click', function () {
				navigator.clipboard.writeText( item.file_url ).then( function () {
					$copyBtn.text( i18n.copied || 'Copied!' );
					setTimeout( function () { $copyBtn.text( i18n.copyLink || 'Copy URL' ); }, 2000 );
				} );
			} );
		var $exportBtn = $( '<button type="button" class="button"></button>' )
			.html( '<span class="dashicons dashicons-wordpress" style="font-size:14px;width:14px;height:14px;vertical-align:middle;margin-right:3px;"></span> Export to WP Media' )
			.on( 'click', function () {
				$exportBtn.prop( 'disabled', true ).text( 'Exporting...' );
				apiRequest( 'media/' + item.id + '/export-wp', 'POST' ).done( function ( res ) {
					if ( res && res.success ) {
						$exportBtn.prop( 'disabled', false ).text( 'Exported to WP Media!' );
						setTimeout( function () {
							$exportBtn.html( '<span class="dashicons dashicons-wordpress" style="font-size:14px;width:14px;height:14px;vertical-align:middle;margin-right:3px;"></span> Export to WP Media' );
						}, 2500 );
					} else {
						$exportBtn.prop( 'disabled', false ).text( 'Export Failed' );
					}
				} ).fail( function () {
					$exportBtn.prop( 'disabled', false ).text( 'Export Failed' );
				} );
			} );

		$actions.append( $saveBtn ).append( $copyBtn ).append( $exportBtn ).append( $delBtn );
		$content.append( $actions );
	}

	function populateFolderOptions( nodes, $select, currentFolderId, depth ) {
		var prefix = '— '.repeat( depth );
		nodes.forEach( function ( node ) {
			var $opt = $( '<option></option>' )
				.val( node.id )
				.text( prefix + node.name )
				.prop( 'selected', node.id === currentFolderId );
			$select.append( $opt );
			if ( node.children && node.children.length ) {
				populateFolderOptions( node.children, $select, currentFolderId, depth + 1 );
			}
		} );
	}

	function saveItemChanges( id, $btn ) {
		$btn.prop( 'disabled', true ).text( 'Saving...' );
		var data = {
			title: $( '#skwpm-edit-title' ).val(),
			alt_text: $( '#skwpm-edit-alt' ).val(),
			caption: $( '#skwpm-edit-caption' ).val(),
			folder_id: parseInt( $( '#skwpm-edit-folder' ).val(), 10 ) || 0,
		};

		apiRequest( 'media/' + id, 'PUT', data ).done( function ( res ) {
			$btn.prop( 'disabled', false ).text( i18n.saved || 'Saved!' );
			setTimeout( function () { $btn.text( i18n.save || 'Save Changes' ); }, 1500 );
			loadMedia();
			loadFolders();
		} );
	}

	function deleteItem( id ) {
		apiRequest( 'media/' + id, 'DELETE' ).done( function () {
			$( '#skwpm-inspector' ).hide();
			loadMedia();
			loadFolders();
		} );
	}

	/* Upload Handlers */
	function handleFilesUpload( files ) {
		if ( ! files || ! files.length ) return;

		var $list = $( '#skwpm-upload-progress-list' ).empty().show();
		var completed = 0;
		var total = files.length;

		Array.from( files ).forEach( function ( file ) {
			var $card = $( '<div class="skwpm-progress-card"></div>' )
				.append( $( '<span></span>' ).text( file.name ) )
				.append( '<div class="skwpm-progress-bar"><div class="skwpm-progress-fill"></div></div>' );
			$list.append( $card );

			uploadFile( file, state.folderId, function ( percent ) {
				$card.find( '.skwpm-progress-fill' ).css( 'width', percent + '%' );
			} ).always( function () {
				completed++;
				if ( completed >= total ) {
					setTimeout( function () {
						$list.slideUp();
						loadMedia();
						loadFolders();
					}, 1000 );
				}
			} );
		} );
	}

	/* Events & Bindings */
	function bindEvents() {
		// Upload Buttons
		$( '#skwpm-btn-upload' ).on( 'click', function () {
			$( '#skwpm-file-input' ).trigger( 'click' );
		} );
		$( '#skwpm-file-input' ).on( 'change', function () {
			handleFilesUpload( this.files );
			$( this ).val( '' );
		} );

		// Quick Filters
		$( '#skwpm-quick-filters .skwpm-nav-item' ).on( 'click', function ( e ) {
			e.preventDefault();
			$( '#skwpm-quick-filters .skwpm-nav-item' ).removeClass( 'active' );
			$( this ).addClass( 'active' );
			state.filter = $( this ).data( 'filter' );
			state.folderId = 0;
			state.page = 1;
			renderFolderTree();
			loadMedia();
		} );

		// New Folder Modal
		$( '#skwpm-btn-new-folder, #skwpm-add-folder-icon' ).on( 'click', function () {
			openNewFolderModal();
		} );

		$( '#skwpm-modal-save-folder' ).on( 'click', function () {
			saveNewFolder();
		} );

		$( '.skwpm-modal-close, .skwpm-modal-cancel' ).on( 'click', function () {
			$( '.skwpm-modal-backdrop' ).hide();
		} );

		// Search & Sorting
		$( '#skwpm-search' ).on( 'keyup', debounce( function () {
			state.search = $( this ).val();
			state.page = 1;
			loadMedia();
		}, 300 ) );

		$( '#skwpm-sort-by' ).on( 'change', function () {
			var parts = $( this ).val().split( '-' );
			state.sortBy = parts[0];
			state.sortOrder = parts[1] || 'DESC';
			loadMedia();
		} );

		// View Mode Switcher
		$( '.skwpm-view-btn' ).on( 'click', function () {
			$( '.skwpm-view-btn' ).removeClass( 'active' );
			$( this ).addClass( 'active' );
			state.viewMode = $( this ).data( 'view' );
			renderMediaItems();
		} );

		// Inspector Close
		$( '#skwpm-close-inspector' ).on( 'click', function () {
			$( '#skwpm-inspector' ).hide();
		} );

		// Bulk Actions
		$( '#skwpm-bulk-clear-btn' ).on( 'click', function () {
			state.selectedIds = [];
			updateBulkBar();
			renderMediaItems();
		} );

		$( '#skwpm-bulk-delete-btn' ).on( 'click', function () {
			if ( ! state.selectedIds.length ) return;
			if ( confirm( i18n.deleteConfirm || 'Delete selected items permanently?' ) ) {
				apiRequest( 'media/batch', 'POST', { action: 'delete', ids: state.selectedIds } ).done( function () {
					state.selectedIds = [];
					updateBulkBar();
					loadMedia();
					loadFolders();
				} );
			}
		} );

		$( '#skwpm-bulk-move-btn' ).on( 'click', function () {
			openMoveModal();
		} );

		$( '#skwpm-modal-confirm-move' ).on( 'click', function () {
			var targetFolderId = parseInt( $( '#skwpm-move-select' ).val(), 10 ) || 0;
			apiRequest( 'media/batch', 'POST', { action: 'move', ids: state.selectedIds, folder_id: targetFolderId } ).done( function () {
				$( '#skwpm-move-modal' ).hide();
				state.selectedIds = [];
				updateBulkBar();
				loadMedia();
				loadFolders();
			} );
		} );

		// Drag & Drop
		var $dropzone = $( '#skwpm-manager-app' );
		$dropzone.on( 'dragenter dragover', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			$( '#skwpm-drop-overlay' ).css( 'display', 'flex' );
		} );

		$( '#skwpm-drop-overlay' ).on( 'dragleave', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			$( '#skwpm-drop-overlay' ).hide();
		} );

		$dropzone.on( 'drop', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			$( '#skwpm-drop-overlay' ).hide();
			var dt = e.originalEvent.dataTransfer;
			if ( dt && dt.files && dt.files.length ) {
				handleFilesUpload( dt.files );
			}
		} );

		// Color chips in modal
		$( '#skwpm-color-palette .skwpm-color-chip' ).on( 'click', function () {
			$( '#skwpm-color-palette .skwpm-color-chip' ).removeClass( 'active' );
			$( this ).addClass( 'active' );
		} );
	}

	function openNewFolderModal() {
		$( '#skwpm-folder-name-input' ).val( '' );
		var $parentSelect = $( '#skwpm-folder-parent-select' ).empty();
		$parentSelect.append( $( '<option value="0"></option>' ).text( i18n.rootFolder || 'Home / Root' ) );
		populateFolderOptions( state.folders, $parentSelect, state.folderId, 0 );
		$( '#skwpm-color-palette .skwpm-color-chip' ).removeClass( 'active' ).first().addClass( 'active' );
		$( '#skwpm-folder-modal' ).css( 'display', 'flex' );
		$( '#skwpm-folder-name-input' ).trigger( 'focus' );
	}

	function saveNewFolder() {
		var name = $.trim( $( '#skwpm-folder-name-input' ).val() );
		if ( ! name ) return;

		var parentId = parseInt( $( '#skwpm-folder-parent-select' ).val(), 10 ) || 0;
		var color = $( '#skwpm-color-palette .skwpm-color-chip.active' ).data( 'color' ) || null;

		apiRequest( 'folders', 'POST', { name: name, parent_id: parentId, color: color } ).done( function ( res ) {
			$( '#skwpm-folder-modal' ).hide();
			if ( res && res.success && res.folder ) {
				state.folderId = res.folder.id;
			}
			loadFolders();
			loadMedia();
		} );
	}

	function openMoveModal() {
		var $container = $( '#skwpm-move-folder-tree' ).empty();
		var $select = $( '<select id="skwpm-move-select" class="widefat"></select>' );
		$select.append( $( '<option value="0"></option>' ).text( i18n.rootFolder || 'Home / Root' ) );
		populateFolderOptions( state.folders, $select, state.folderId, 0 );
		$container.append( $select );
		$( '#skwpm-move-modal' ).css( 'display', 'flex' );
	}

	function formatBytes( bytes ) {
		if ( ! bytes ) return '0 B';
		var k = 1024;
		var sizes = [ 'B', 'KB', 'MB', 'GB' ];
		var i = Math.floor( Math.log( bytes ) / Math.log( k ) );
		return parseFloat( ( bytes / Math.pow( k, i ) ).toFixed( 1 ) ) + ' ' + sizes[i];
	}

	function escapeHtml( str ) {
		return ( str || '' ).toString().replace( /&/g, '&amp;' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' ).replace( /"/g, '&quot;' );
	}

	function debounce( func, wait ) {
		var timeout;
		return function () {
			var context = this, args = arguments;
			clearTimeout( timeout );
			timeout = setTimeout( function () { func.apply( context, args ); }, wait );
		};
	}

	$( function () {
		if ( $( '#skwpm-manager-app' ).length ) {
			init();
		}
	} );
} )( jQuery );
