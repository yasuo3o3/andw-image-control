jQuery(document).ready(function($) {

    function addMimeTypeLabels() {
        $('.attachment').each(function() {
            var $attachment = $(this);

            if ($attachment.find('.andw-mime-label').length > 0) {
                return;
            }

            var attachmentId = $attachment.data('id');
            if (!attachmentId) {
                return;
            }

            $.ajax({
                url: andwMediaUI.ajaxurl,
                type: 'POST',
                data: {
                    action: 'andw_get_mime_type',
                    attachment_id: attachmentId,
                    nonce: andwMediaUI.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var $label = $('<div class="andw-mime-label ' + response.data.class + '">' + response.data.label + '</div>');
                        var $thumbnail = $attachment.find('.thumbnail').first();
                        if ($thumbnail.length) {
                            $thumbnail.append($label);
                        }
                    }
                }
            });
        });

        $('.wp-list-table .media-icon').each(function() {
            var $mediaIcon = $(this);

            if ($mediaIcon.find('.andw-mime-label').length > 0) {
                return;
            }

            var $link = $mediaIcon.find('a');
            if (!$link.length) {
                return;
            }

            var href = $link.attr('href');
            var matches = href.match(/post=(\d+)/);
            if (!matches) {
                return;
            }

            var attachmentId = matches[1];

            $.ajax({
                url: andwMediaUI.ajaxurl,
                type: 'POST',
                data: {
                    action: 'andw_get_mime_type',
                    attachment_id: attachmentId,
                    nonce: andwMediaUI.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var $label = $('<div class="andw-mime-label ' + response.data.class + '">' + response.data.label + '</div>');
                        $mediaIcon.css('position', 'relative').append($label);
                    }
                }
            });
        });
    }

    addMimeTypeLabels();

    $(document).on('DOMNodeInserted', function(e) {
        if ($(e.target).hasClass('attachment') || $(e.target).find('.attachment').length) {
            setTimeout(addMimeTypeLabels, 100);
        }
    });

    if (typeof wp !== 'undefined' && wp.media) {
        var originalOpen = wp.media.frame.open;
        wp.media.frame.open = function() {
            originalOpen.apply(this, arguments);
            setTimeout(addMimeTypeLabels, 500);
        };
    }

    $(window).on('load', function() {
        setTimeout(addMimeTypeLabels, 1000);
    });
});