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

    // MutationObserver を使用（DOMNodeInserted の現代的な代替）
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function(mutations) {
            var shouldUpdate = false;
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            if ($(node).hasClass('attachment') || $(node).find('.attachment').length) {
                                shouldUpdate = true;
                            }
                        }
                    });
                }
            });
            if (shouldUpdate) {
                setTimeout(addMimeTypeLabels, 50);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // WordPress Media Library のイベントフック
    if (typeof wp !== 'undefined' && wp.media) {
        // メディアフレームが開かれた時
        $(document).on('wp-media-frame-open', function() {
            setTimeout(addMimeTypeLabels, 300);
        });

        // メディアフレームが更新された時
        $(document).on('wp-media-frame-content', function() {
            setTimeout(addMimeTypeLabels, 200);
        });

        // 従来の方法も併用（互換性のため）
        if (wp.media.frame && wp.media.frame.on) {
            wp.media.frame.on('open', function() {
                setTimeout(addMimeTypeLabels, 300);
            });

            wp.media.frame.on('content:create:browse', function() {
                setTimeout(addMimeTypeLabels, 400);
            });

            wp.media.frame.on('content:render:browse', function() {
                setTimeout(addMimeTypeLabels, 200);
            });
        }
    }

    $(window).on('load', function() {
        setTimeout(addMimeTypeLabels, 800);
    });

    // 定期的チェック（確実性のため）
    setInterval(function() {
        if ($('.media-modal:visible .attachment').length > 0) {
            addMimeTypeLabels();
        }
    }, 2000);
});