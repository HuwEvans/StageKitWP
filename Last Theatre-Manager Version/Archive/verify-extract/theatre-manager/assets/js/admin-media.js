
jQuery(document).ready(function($) {
    $('.tm-media-upload').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var targetId = button.data('target');
        var inputField = $('#' + targetId);
        var previewImg = inputField.next('br').next('img');

        var frame = wp.media({
            title: 'Select or Upload Media',
            button: {
                text: 'Use this media'
            },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            inputField.val(attachment.url);
            if (previewImg.length) {
                previewImg.attr('src', attachment.url).show();
            } else {
                inputField.after('<br><img src="' + attachment.url + '" style="max-width:150px; margin-top:10px;" />');
            }
        });

        frame.open();
    });
});
