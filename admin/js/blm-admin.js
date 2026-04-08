(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize color pickers
        $('.blm-color-picker').wpColorPicker();

        // Media upload
        var mediaFrame;

        $('#blm_upload_image').on('click', function(e) {
            e.preventDefault();

            if (mediaFrame) {
                mediaFrame.open();
                return;
            }

            mediaFrame = wp.media({
                title: 'Wybierz obrazek CTA',
                button: { text: 'Użyj tego obrazka' },
                multiple: false
            });

            mediaFrame.on('select', function() {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                $('#blm_image_id').val(attachment.id);
                var url = attachment.sizes && attachment.sizes.medium
                    ? attachment.sizes.medium.url
                    : attachment.url;
                $('#blm_image_preview').html('<img src="' + url + '" style="max-width:300px;height:auto;">');
                $('#blm_remove_image').show();
            });

            mediaFrame.open();
        });

        $('#blm_remove_image').on('click', function(e) {
            e.preventDefault();
            $('#blm_image_id').val(0);
            $('#blm_image_preview').html('');
            $(this).hide();
        });
    });

})(jQuery);
