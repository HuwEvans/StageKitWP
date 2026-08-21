/**
 * Board Member CPT — WP Media Library uploader
 * Stores attachment ID in the hidden input (preferred over URL).
 */
jQuery( document ).ready( function ( $ ) {
	var frame;

	$( '#stagekitwp-bm-photo-select' ).on( 'click', function ( e ) {
		e.preventDefault();

		if ( frame ) {
			frame.open();
			return;
		}

		frame = wp.media( {
			title    : 'Select Board Member Photo',
			button   : { text: 'Use this photo' },
			multiple : false,
			library  : { type: 'image' },
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();

			// Prefer attachment ID so the site can serve resized images
			$( '#stagekitwp_photo' ).val( attachment.id );

			// Update preview
			var preview = $( '#stagekitwp-bm-photo-preview' );
			preview.find( 'img' ).remove();
			preview.prepend(
				$( '<img>' )
					.attr( 'src', attachment.url )
					.css( { maxWidth: '200px', height: 'auto', display: 'block', marginBottom: '8px' } )
			);

			$( '#stagekitwp-bm-photo-remove' ).show();
		} );

		frame.open();
	} );

	$( '#stagekitwp-bm-photo-remove' ).on( 'click', function ( e ) {
		e.preventDefault();
		$( '#stagekitwp_photo' ).val( '' );
		$( '#stagekitwp-bm-photo-preview' ).find( 'img' ).remove();
		$( this ).hide();
	} );
} );
