(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize color pickers
        $('.blm-color-picker').wpColorPicker();

        // Generic media upload — works for any .blm-media-upload button
        // Expects: data-target="input_id" data-preview="preview_id" data-remove="remove_id"
        $(document).on('click', '.blm-media-upload', function(e) {
            e.preventDefault();
            var $btn     = $(this);
            var targetId = $btn.data('target');
            var previewId = $btn.data('preview');
            var removeId = $btn.data('remove');
            var mode     = $btn.data('mode') || 'id'; // 'id' stores attachment ID, 'url' stores URL

            var frame = wp.media({
                title: 'Wybierz obrazek',
                button: { text: 'Użyj tego obrazka' },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                var url = attachment.sizes && attachment.sizes.medium
                    ? attachment.sizes.medium.url
                    : attachment.url;

                if (mode === 'url') {
                    $('#' + targetId).val(attachment.url);
                } else {
                    $('#' + targetId).val(attachment.id);
                }

                if (previewId) {
                    $('#' + previewId).html('<img src="' + url + '" style="max-width:300px;height:auto;">');
                }
                if (removeId) {
                    $('#' + removeId).show();
                }
            });

            frame.open();
        });

        // Generic remove image
        $(document).on('click', '.blm-media-remove', function(e) {
            e.preventDefault();
            var $btn     = $(this);
            var targetId = $btn.data('target');
            var previewId = $btn.data('preview');

            $('#' + targetId).val( $btn.data('mode') === 'url' ? '' : '0' );
            if (previewId) {
                $('#' + previewId).html('');
            }
            $btn.hide();
        });
    });

})(jQuery);
