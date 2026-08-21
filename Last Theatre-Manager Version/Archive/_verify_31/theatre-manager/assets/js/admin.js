jQuery(document).ready(function($) {
    // General media selector for multiple images/videos
    $('#tm_media_button').click(function(e) {
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
            var previewContainer = $('#tm_media_preview');
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

            $('#tm_media_urls').val(urls.join(','));
        });

        frame.open();
    });

    // Logo selector
    $('#tm_logo_button').click(function(e) {
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
            $('#tm_logo').val(attachment.url);
            $('#tm_logo_preview').attr('src', attachment.url).show();
        });

        frame.open();
    });

    // Banner selector
    $('#tm_banner_button').click(function(e) {
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
            $('#tm_banner').val(attachment.url);
            $('#tm_banner_preview').attr('src', attachment.url).show();
        });

        frame.open();
    });
	
    function initMediaSelector() {
        $('.tm-media-selector').off('click').on('click', function(e) {
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
                // set hidden id if present (convention: input id + '_id' or legacy tm_show_program_id)
                var inputId = input.attr('id');
                if (inputId) {
                    var hid = $('#' + inputId + '_id');
                    if (hid.length) hid.val(attachment.id);
                }
                var legacyId2 = $('#tm_show_program_id');
                if (legacyId2.length && attachment.id) legacyId2.val(attachment.id);
                if (previewId) {
                    $('#' + previewId).attr('src', previewUrl).show();
                }
            }).open();
        });
    }

    initMediaSelector();

    // Media selector for image fields
    $('.tm-media-selector').on('click', function(e) {
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
            var legacyId3 = $('#tm_show_program_id');
            if (legacyId3.length && attachment.id) legacyId3.val(attachment.id);
            if (previewId) {
                $('#' + previewId).attr('src', attachment.url);
            }
        }).open();
    });

    // Initialize color picker
    if ($('.tm-color-picker').length > 0) {
        $('.tm-color-picker').wpColorPicker();
    }

    // Initialize date picker
    if ($('.tm-datepicker').length > 0) {
        $('.tm-datepicker').datepicker({
            dateFormat: 'yy-mm-dd'
        });
    }


    // Initialize WordPress color pickers
    $('.tm-color-picker').wpColorPicker();

});

jQuery(document).ready(function($) {
    $('.tm-media-button').on('click', function (e) {
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
            // also set legacy hidden field name tm_show_program_id if present
            const legacyId = $('#tm_show_program_id');
            if (legacyId.length && attachment.id) {
                legacyId.val(attachment.id);
            }
            if (previewId) {
                $('#' + previewId).attr('src', previewUrl).show();
            }
        });

        customUploader.open();
    });
});
