jQuery(document).ready(function($) {

    function addMimeTypeLabelsFromJS() {
        // Option B: Use pre-embedded data from wp_prepare_attachment_for_js
        if (typeof wp !== 'undefined' && wp.media && wp.media.frame) {
            var attachments = wp.media.frame.state().get('selection') || wp.media.frame.content.get().collection;
            if (attachments && attachments.models) {
                attachments.models.forEach(function(model) {
                    var attachment = model.attributes;
                    if (attachment.andw_mime_label && attachment.andw_mime_class) {
                        var $attachment = $('.attachment[data-id="' + attachment.id + '"]');
                        if ($attachment.length && !$attachment.find('.andw-mime-label').length) {
                            var $label = $('<div class="andw-mime-label ' + attachment.andw_mime_class + '">' + attachment.andw_mime_label + '</div>');
                            var $thumbnail = $attachment.find('.thumbnail').first();
                            if ($thumbnail.length) {
                                $thumbnail.append($label);
                            }
                        }
                    }
                });
            }
        }
    }

    function addMimeTypeLabelsFromBackend() {
        // Option A: Batch processing for media list table and other cases
        var attachmentIds = [];
        var $elementsToProcess = [];

        // Collect attachment IDs from media modal
        $('.attachment').each(function() {
            var $attachment = $(this);
            if ($attachment.find('.andw-mime-label').length > 0) {
                return;
            }
            var attachmentId = $attachment.data('id');
            if (attachmentId) {
                attachmentIds.push(attachmentId);
                $elementsToProcess.push({
                    id: attachmentId,
                    element: $attachment,
                    type: 'modal'
                });
            }
        });

        // Collect attachment IDs from media list table (improved selectors)
        $('.wp-list-table .media-icon, .wp-list-table .column-title .media-icon').each(function() {
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
            if (matches) {
                var attachmentId = parseInt(matches[1]);
                attachmentIds.push(attachmentId);
                $elementsToProcess.push({
                    id: attachmentId,
                    element: $mediaIcon,
                    type: 'list'
                });
            }
        });

        // Additional check for list view table rows
        $('.wp-list-table .type-attachment').each(function() {
            var $row = $(this);
            var $mediaIcon = $row.find('.media-icon');
            if (!$mediaIcon.length || $mediaIcon.find('.andw-mime-label').length > 0) {
                return;
            }

            // Try to get attachment ID from row id
            var rowId = $row.attr('id');
            var attachmentId = null;
            if (rowId) {
                var matches = rowId.match(/post-(\d+)/);
                if (matches) {
                    attachmentId = parseInt(matches[1]);
                }
            }

            // Fallback to link method
            if (!attachmentId) {
                var $link = $mediaIcon.find('a');
                if ($link.length) {
                    var href = $link.attr('href');
                    var matches = href.match(/post=(\d+)/);
                    if (matches) {
                        attachmentId = parseInt(matches[1]);
                    }
                }
            }

            if (attachmentId) {
                attachmentIds.push(attachmentId);
                $elementsToProcess.push({
                    id: attachmentId,
                    element: $mediaIcon,
                    type: 'list'
                });
            }
        });

        // Remove duplicates
        attachmentIds = [...new Set(attachmentIds)];

        if (attachmentIds.length === 0) {
            return;
        }

        // Single batch AJAX request
        $.ajax({
            url: andwMediaUI.ajaxurl,
            type: 'POST',
            data: {
                action: 'andw_get_mime_types_batch',
                attachment_ids: attachmentIds,
                nonce: andwMediaUI.nonce
            },
            success: function(response) {
                if (response.success) {
                    $elementsToProcess.forEach(function(item) {
                        var mimeData = response.data[item.id];
                        if (mimeData) {
                            var $label = $('<div class="andw-mime-label ' + mimeData.class + '">' + mimeData.label + '</div>');

                            if (item.type === 'modal') {
                                var $thumbnail = item.element.find('.thumbnail').first();
                                if ($thumbnail.length) {
                                    $thumbnail.append($label);
                                }
                            } else if (item.type === 'list') {
                                item.element.css('position', 'relative').append($label);
                            }
                        }
                    });
                }
            }
        });
    }

    function addMimeTypeLabels() {
        // Try Option B first (pre-embedded data), fallback to Option A (batch AJAX)
        addMimeTypeLabelsFromJS();
        setTimeout(addMimeTypeLabelsFromBackend, 100);
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

    // WordPress Media Library のイベントフック（最適化版）
    if (typeof wp !== 'undefined' && wp.media) {
        // メディアフレームが開かれた時
        $(document).on('wp-media-frame-open', function() {
            setTimeout(addMimeTypeLabels, 50);
        });

        // メディアフレームが更新された時
        $(document).on('wp-media-frame-content', function() {
            setTimeout(addMimeTypeLabels, 50);
        });

        // 従来の方法も併用（互換性のため）
        if (wp.media.frame && wp.media.frame.on) {
            wp.media.frame.on('open', function() {
                setTimeout(addMimeTypeLabels, 50);
            });

            wp.media.frame.on('content:create:browse', function() {
                setTimeout(addMimeTypeLabels, 100);
            });

            wp.media.frame.on('content:render:browse', function() {
                setTimeout(addMimeTypeLabels, 50);
            });
        }
    }

    $(window).on('load', function() {
        setTimeout(addMimeTypeLabels, 200);
    });
});