jQuery(document).ready(function($) {
    // Delegated bindings: these CPT edit screens use the block editor (show_in_rest),
    // which can re-render meta box markup after load, detaching directly-bound handlers.
    // General media selector for multiple images/videos
    $(document).on('click', '#stagekitwp_media_button', function(e) {
        e.preventDefault();

        var frame = wp.media({
            title: 'Select Media',
            button: {
                text: 'Use selected media'
            },
            multiple: true
        });

        frame.on('select', function() {
            var selection = frame.state().get('selection');
            var urls = [];
            var previewContainer = $('#stagekitwp_media_preview');
            previewContainer.empty();

            selection.each(function(attachment) {
                attachment = attachment.toJSON();
                urls.push(attachment.url);

                if (attachment.type === 'image') {
                    previewContainer.append('<img src="' + attachment.url + '" style="max-width:150px; margin:5px;" />');
                } else if (attachment.type === 'video') {
                    previewContainer.append('<video src="' + attachment.url + '" controls style="max-width:150px; margin:5px;"></video>');
                }
            });

            $('#stagekitwp_media_urls').val(urls.join(','));
        });

        frame.open();
    });

    // Logo selector
    $(document).on('click', '#stagekitwp_logo_button', function(e) {
        e.preventDefault();

        var frame = wp.media({
            title: 'Select Logo Image',
            button: {
                text: 'Use this logo'
            },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#stagekitwp_logo').val(attachment.url);
            $('#stagekitwp_logo_preview').attr('src', attachment.url).show();
        });

        frame.open();
    });

    // Banner selector
    $(document).on('click', '#stagekitwp_banner_button', function(e) {
        e.preventDefault();

        var frame = wp.media({
            title: 'Select Banner Image',
            button: {
                text: 'Use this banner'
            },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#stagekitwp_banner').val(attachment.url);
            $('#stagekitwp_banner_preview').attr('src', attachment.url).show();
        });

        frame.open();
    });
	
    function initMediaSelector() {
        $('.stagekitwp-media-selector').off('click').on('click', function(e) {
            e.preventDefault();
            const input = $(this);
            const previewId = input.data('preview');
            const customUploader = wp.media({
                title: 'Select Image',
                button: { text: 'Use this image' },
                multiple: false
            }).on('select', function() {
                const attachment = customUploader.state().get('selection').first().toJSON();
                // determine a sensible preview URL for images/PDFs/icons
                let previewUrl = '';
                if (attachment.type === 'image') {
                    previewUrl = attachment.url;
                } else if (attachment.sizes) {
                    if (attachment.sizes.medium) previewUrl = attachment.sizes.medium.url;
                    else if (attachment.sizes.thumbnail) previewUrl = attachment.sizes.thumbnail.url;
                    else {
                        const keys = Object.keys(attachment.sizes);
                        if (keys.length) previewUrl = attachment.sizes[keys[0]].url;
                    }
                } else if (attachment.icon) {
                    previewUrl = attachment.icon;
                } else {
                    // inline small SVG fallback
                    previewUrl = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 24 24"><rect width="24" height="24" fill="%23f3f4f6"/><text x="12" y="16" font-size="8" text-anchor="middle" fill="%23000">PDF</text></svg>';
                }

                input.val(attachment.url);
                // set hidden id if present (convention: input id + '_id' or legacy stagekitwp_show_program_id)
                var inputId = input.attr('id');
                if (inputId) {
                    var hid = $('#' + inputId + '_id');
                    if (hid.length) hid.val(attachment.id);
                }
                var legacyId2 = $('#stagekitwp_show_program_id');
                if (legacyId2.length && attachment.id) legacyId2.val(attachment.id);
                if (previewId) {
                    $('#' + previewId).attr('src', previewUrl).show();
                }
            }).open();
        });
    }

    initMediaSelector();

    // Media selector for image fields
    $('.stagekitwp-media-selector').on('click', function(e) {
        e.preventDefault();
        var input = $(this);
        var previewId = input.data('preview');
        var customUploader = wp.media({
            title: 'Select Image',
            button: { text: 'Use this image' },
            multiple: false
        }).on('select', function() {
            var attachment = customUploader.state().get('selection').first().toJSON();
            input.val(attachment.url);
            // set hidden id if present
            var inputId = input.attr('id');
            if (inputId) {
                var hid = $('#' + inputId + '_id');
                if (hid.length) hid.val(attachment.id);
            }
            var legacyId3 = $('#stagekitwp_show_program_id');
            if (legacyId3.length && attachment.id) legacyId3.val(attachment.id);
            if (previewId) {
                $('#' + previewId).attr('src', attachment.url);
            }
        }).open();
    });

    // ── Colour picker clear buttons ──────────────────────────────────────────
    //
    // Each colour picker input is wrapped in .stagekitwp-color-picker-wrap alongside a
    // .stagekitwp-color-clear button. Clicking it empties the input and resets the WP
    // iris picker to "no colour", so on the next Save the option is stored as ''
    // and shortcodes fall back to the theme / hard-coded defaults.

    /**
     * Mark the wrapper as .has-value when the input is non-empty so the ×
     * button is visible without requiring hover.
     */
    function stagekitwpSyncClearVisibility( $input ) {
        var $wrap = $input.closest( '.stagekitwp-color-picker-wrap' );
        if ( ! $wrap.length ) { return; }
        if ( $input.val() && $input.val() !== '' ) {
            $wrap.addClass( 'has-value' );
        } else {
            $wrap.removeClass( 'has-value' );
        }
    }

    // Initialize all colour pickers (light + dark pairs) with a change callback
    // so the clear-button visibility stays in sync when a colour is picked.
    function stagekitwpNormalizeColorValue( value ) {
        var normalized = ( value || '' ).toString().trim();
        if ( ! normalized ) {
            return '#ffffff';
        }
        if ( normalized.charAt(0) !== '#' ) {
            if ( /^[0-9a-fA-F]{3}$/.test( normalized ) || /^[0-9a-fA-F]{6}$/.test( normalized ) ) {
                normalized = '#' + normalized;
            } else {
                return '#ffffff';
            }
        }
        if ( normalized.length === 4 ) {
            normalized = '#' + normalized[1] + normalized[1] + normalized[2] + normalized[2] + normalized[3] + normalized[3];
        }
        return normalized;
    }

    function stagekitwpInitNativeColorFallback( $input ) {
        if ( $input.data( 'stagekitwpNativeColorFallback' ) ) {
            return;
        }

        var $wrap = $input.closest( '.stagekitwp-color-picker-wrap' );
        if ( ! $wrap.length ) {
            return;
        }

        var $fallback = $('<input type="color" class="stagekitwp-color-picker-fallback" />');
        $fallback.val( stagekitwpNormalizeColorValue( $input.val() ) );
        $fallback.attr( 'aria-label', 'Select color' );
        $fallback.on( 'input change', function() {
            $input.val( $( this ).val() );
            stagekitwpSyncClearVisibility( $input );
        } );

        if ( $input.siblings( '.stagekitwp-color-picker-fallback' ).length === 0 ) {
            $input.after( $fallback );
        }
        $input.addClass( 'stagekitwp-color-picker-hidden' );
        $input.data( 'stagekitwpNativeColorFallback', true );
    }

    if ( $('.stagekitwp-color-picker').length > 0 ) {
        $('.stagekitwp-color-picker').each(function() {
            var $input = $(this);
            if ($input.data('wpColorPickerInitialized') || $input.data('stagekitwpNativeColorFallback')) {
                return;
            }

            if ( typeof $.fn.wpColorPicker === 'function' ) {
                try {
                    $input.wpColorPicker({
                        change: function( event, ui ) {
                            var $currentInput = $( event.target );
                            setTimeout( function() { stagekitwpSyncClearVisibility( $currentInput ); }, 10 );
                        },
                        clear: function() {
                            var $currentInput = $( this );
                            setTimeout( function() { stagekitwpSyncClearVisibility( $currentInput ); }, 10 );
                        }
                    });
                    $input.data('wpColorPickerInitialized', true);
                } catch ( err ) {
                    stagekitwpInitNativeColorFallback( $input );
                }
            } else {
                stagekitwpInitNativeColorFallback( $input );
            }
        });
    }

    // Set initial has-value state on page load
    $( '.stagekitwp-color-picker' ).each( function() {
        stagekitwpSyncClearVisibility( $( this ) );
    });

    // Delegated click: clear button ×
    $( document ).on( 'click', '.stagekitwp-color-clear', function( e ) {
        e.preventDefault();
        var targetId = $( this ).data( 'target' );
        var $input   = $( '#' + targetId );
        if ( ! $input.length ) { return; }

        // 1. Empty the underlying <input> value
        $input.val( '' );

        var $fallback = $input.siblings( '.stagekitwp-color-picker-fallback' );
        if ( $fallback.length ) {
            $fallback.val( '#ffffff' );
        }

        // 2. Tell the WP iris colour picker to clear itself.
        //    wpColorPicker attaches to the input; we trigger its internal clear.
        if ( $input.wpColorPicker && $input.wpColorPicker( 'instance' ) ) {
            try {
                $input.wpColorPicker( 'color', '' );
            } catch ( err ) { /* older WP versions may not support this */ }
        }

        // 3. Iris stores state on the .wp-picker-container parent; collapse it
        //    and reset the colour button swatch to the "no colour" state.
        var $container = $input.closest( '.wp-picker-container' );
        if ( $container.length ) {
            // Hide the picker popover if open
            $container.find( '.wp-picker-open' ).removeClass( 'wp-picker-open' );
            $container.find( '.wp-picker-holder' ).hide();
            // Reset the swatch button background
            $container.find( '.wp-color-result' ).css( 'background-color', '' );
            // Reset the swatch button text to the default "Select Color" label
            $container.find( '.wp-color-result-text' ).text(
                $container.find( '.wp-color-result-text' ).data( 'default-label' ) ||
                'Select Color'
            );
        }

        // 4. Update the clear-button visibility
        stagekitwpSyncClearVisibility( $input );
    });

    // Initialize date picker
    if ($('.stagekitwp-datepicker').length > 0) {
        $('.stagekitwp-datepicker').datepicker({
            dateFormat: 'yy-mm-dd'
        });
    }


    // (duplicate wpColorPicker init removed — handled above with change/clear callbacks)

});

// Font preview live-update (called by onchange on the font <select>)
function stagekitwpUpdateFontPreview( select ) {
    var previewId = select.id + '_preview';
    var preview   = document.getElementById( previewId );
    if ( preview ) {
        preview.style.fontFamily = select.value;
        // Also update the parent .stagekitwp-font-preview container
        var container = preview.closest ? preview.closest( '.stagekitwp-font-preview' ) : preview.parentNode;
        if ( container ) { container.style.fontFamily = select.value; }
    }
}

jQuery(document).ready(function($) {
    $(document).on('click', '.stagekitwp-media-button', function (e) {
        e.preventDefault();
        const button = $(this);
        const targetId = button.data('target');
        const previewId = button.data('preview');

        const customUploader = wp.media({
            title: 'Select Image',
            button: { text: 'Use this image' },
            multiple: false
        });

        customUploader.on('select', function () {
            const attachment = customUploader.state().get('selection').first().toJSON();
            let previewUrl = '';
            if (attachment.type === 'image') {
                previewUrl = attachment.url;
            } else if (attachment.sizes) {
                if (attachment.sizes.medium) previewUrl = attachment.sizes.medium.url;
                else if (attachment.sizes.thumbnail) previewUrl = attachment.sizes.thumbnail.url;
                else {
                    const keys = Object.keys(attachment.sizes);
                    if (keys.length) previewUrl = attachment.sizes[keys[0]].url;
                }
            } else if (attachment.icon) {
                previewUrl = attachment.icon;
            } else {
                previewUrl = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 24 24"><rect width="24" height="24" fill="%23f3f4f6"/><text x="12" y="16" font-size="8" text-anchor="middle" fill="%23000">PDF</text></svg>';
            }

            // set the visible URL input
            $('#' + targetId).val(attachment.url);
            // also set a hidden id field if present (convention: targetId + '_id')
            const idField = $('#' + targetId + '_id');
            if (idField.length) {
                idField.val(attachment.id);
            }
            // also set legacy hidden field name stagekitwp_show_program_id if present
            const legacyId = $('#stagekitwp_show_program_id');
            if (legacyId.length && attachment.id) {
                legacyId.val(attachment.id);
            }
            if (previewId) {
                $('#' + previewId).attr('src', previewUrl).show();
            }
        });

        customUploader.open();
    });

    $(document).on('click', '.stagekitwp-media-clear-button', function (e) {
        e.preventDefault();
        const button = $(this);
        const targetId = button.data('target');
        const previewId = button.data('preview');
        const idTargetId = button.data('id-target');

        // Clear the URL input field
        $('#' + targetId).val('');

        // Clear the ID field if present
        if (idTargetId) {
            $('#' + idTargetId).val('');
        }

        // Hide the preview image
        if (previewId) {
            $('#' + previewId).hide();
        }
    });
});


