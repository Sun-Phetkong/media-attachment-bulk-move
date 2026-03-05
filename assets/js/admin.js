/**
 * Media Attachment Bulk Move - Admin JavaScript
 * Version 1.2.2
 */

(function($) {
    'use strict';

    var searchTimeout = null;
    var selectedPostId = null;
    var selectedPostTitle = '';

    $(document).ready(function() {

        // ==========================================
        // Selection Management
        // ==========================================

        // Select All checkbox - use click event for better compatibility
        $(document).on('click', '#mabm-select-all', function(e) {
            e.stopPropagation();
            var isChecked = $(this).is(':checked');
            $('.mabm-attachment-select').prop('checked', isChecked);
            $('.mabm-attachment-item').toggleClass('mabm-selected', isChecked);
            updateSelectedCount();
        });

        // Individual checkbox - use click event directly
        $(document).on('click', '.mabm-attachment-select', function(e) {
            e.stopPropagation();
            var $item = $(this).closest('.mabm-attachment-item');
            $item.toggleClass('mabm-selected', $(this).is(':checked'));
            updateSelectAllState();
            updateSelectedCount();
        });

        // Click on checkbox container (label area)
        $(document).on('click', '.mabm-attachment-checkbox', function(e) {
            e.stopPropagation();
            var $checkbox = $(this).find('.mabm-attachment-select');
            if (!$(e.target).is('.mabm-attachment-select')) {
                $checkbox.prop('checked', !$checkbox.is(':checked'));
                var $item = $(this).closest('.mabm-attachment-item');
                $item.toggleClass('mabm-selected', $checkbox.is(':checked'));
                updateSelectAllState();
                updateSelectedCount();
            }
        });

        // Click on header row (filename area) to toggle selection
        $(document).on('click', '.mabm-attachment-header', function(e) {
            if ($(e.target).closest('.mabm-attachment-checkbox, input').length) {
                return;
            }
            var $item = $(this).closest('.mabm-attachment-item');
            var $checkbox = $item.find('.mabm-attachment-select');
            $checkbox.prop('checked', !$checkbox.is(':checked'));
            $item.toggleClass('mabm-selected', $checkbox.is(':checked'));
            updateSelectAllState();
            updateSelectedCount();
        });

        // Click on body row to toggle selection (excluding actions)
        $(document).on('click', '.mabm-attachment-body', function(e) {
            if ($(e.target).closest('.mabm-attachment-actions, a, button').length) {
                return;
            }
            var $item = $(this).closest('.mabm-attachment-item');
            var $checkbox = $item.find('.mabm-attachment-select');
            $checkbox.prop('checked', !$checkbox.is(':checked'));
            $item.toggleClass('mabm-selected', $checkbox.is(':checked'));
            updateSelectAllState();
            updateSelectedCount();
        });

        // ==========================================
        // Post Search Autocomplete
        // ==========================================

        var $searchInput = $('#mabm-post-search');
        var $searchResults = $('#mabm-search-results');
        var $targetPostId = $('#mabm-target-post-id');
        var $moveBtn = $('#mabm-move-selected');

        // Search input handler with debounce
        $searchInput.on('input', function() {
            var query = $(this).val().trim();

            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            if (selectedPostTitle && query !== selectedPostTitle) {
                clearPostSelection();
            }

            if (query.length < 2) {
                $searchResults.removeClass('active').empty();
                return;
            }

            $searchResults.addClass('active').html(
                '<div class="mabm-search-loading">' + mabmManager.i18n.searching + '</div>'
            );

            searchTimeout = setTimeout(function() {
                searchPosts(query);
            }, 300);
        });

        // Focus handler
        $searchInput.on('focus', function() {
            if ($(this).val().length >= 2 && $searchResults.children().length > 0) {
                $searchResults.addClass('active');
            }
        });

        // Click outside to close dropdown
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.mabm-move-controls').length) {
                $searchResults.removeClass('active');
            }
        });

        // Search posts via AJAX
        function searchPosts(query) {
            $.ajax({
                url: mabmManager.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mabm_search_posts',
                    nonce: mabmManager.nonce,
                    search: query,
                    current_post_id: mabmManager.currentPostId
                },
                success: function(response) {
                    if (response.success && response.data.posts) {
                        renderSearchResults(response.data.posts);
                    } else {
                        $searchResults.html(
                            '<div class="mabm-search-no-results">' + mabmManager.i18n.noResults + '</div>'
                        );
                    }
                },
                error: function() {
                    $searchResults.removeClass('active').empty();
                }
            });
        }

        // Render search results
        function renderSearchResults(posts) {
            if (posts.length === 0) {
                $searchResults.html(
                    '<div class="mabm-search-no-results">' + mabmManager.i18n.noResults + '</div>'
                );
                return;
            }

            var html = '';
            posts.forEach(function(post) {
                var statusClass = post.status || 'publish';
                html += '<div class="mabm-search-result-item" data-id="' + post.id + '" data-title="' + escapeHtml(post.title) + '">';
                html += '<span class="mabm-result-title">' + escapeHtml(post.title) + '</span>';
                html += '<div class="mabm-result-meta">';
                html += '<span class="mabm-result-type">' + escapeHtml(post.post_type) + '</span>';
                html += '<span class="mabm-result-status ' + statusClass + '">' + statusClass + '</span>';
                html += '<span class="mabm-result-id">ID: ' + post.id + '</span>';
                html += '</div>';
                html += '</div>';
            });

            $searchResults.html(html);
        }

        // Select a post from results
        $(document).on('click', '.mabm-search-result-item', function() {
            var postId = $(this).data('id');
            var postTitle = $(this).data('title');

            selectedPostId = postId;
            selectedPostTitle = postTitle;

            $targetPostId.val(postId);
            $searchInput.val(postTitle).addClass('has-selection');
            $searchResults.removeClass('active');

            updateMoveButtonState();
        });

        // Clear post selection
        function clearPostSelection() {
            selectedPostId = null;
            selectedPostTitle = '';
            $targetPostId.val('');
            $searchInput.removeClass('has-selection');
            updateMoveButtonState();
        }

        // ==========================================
        // Move Selected Files
        // ==========================================

        $moveBtn.on('click', function() {
            var selectedIds = getSelectedAttachmentIds();

            if (selectedIds.length === 0) {
                alert(mabmManager.i18n.selectFiles);
                return;
            }

            if (!selectedPostId) {
                alert(mabmManager.i18n.selectPost);
                return;
            }

            if (!confirm(mabmManager.i18n.confirmMove)) {
                return;
            }

            var $btn = $(this);
            var originalText = $btn.html();
            $btn.prop('disabled', true).html(mabmManager.i18n.moving);

            selectedIds.forEach(function(id) {
                $('.mabm-attachment-item[data-attachment-id="' + id + '"]').addClass('mabm-removing');
            });

            $.ajax({
                url: mabmManager.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mabm_move_media',
                    nonce: mabmManager.nonce,
                    attachment_ids: selectedIds,
                    target_post_id: selectedPostId
                },
                success: function(response) {
                    if (response.success) {
                        selectedIds.forEach(function(id) {
                            $('.mabm-attachment-item[data-attachment-id="' + id + '"]').slideUp(300, function() {
                                $(this).remove();
                                updateAttachmentCount();
                            });
                        });

                        var $container = $('.mabm-attachments-container');
                        var successHtml = '<div class="mabm-move-success">' + response.data.message;
                        if (response.data.target_url) {
                            successHtml += ' <a href="' + response.data.target_url + '">' + mabmManager.i18n.viewTarget + ' &rarr;</a>';
                        }
                        successHtml += '</div>';
                        $container.prepend(successHtml);

                        clearPostSelection();
                        $searchInput.val('');
                        $('#mabm-select-all').prop('checked', false);

                        setTimeout(function() {
                            $('.mabm-move-success').fadeOut(300, function() {
                                $(this).remove();
                            });
                        }, 5000);
                    } else {
                        alert(response.data.message || 'An error occurred.');
                        $('.mabm-attachment-item').removeClass('mabm-removing');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $('.mabm-attachment-item').removeClass('mabm-removing');
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalText);
                    updateMoveButtonState();
                }
            });
        });

        // ==========================================
        // Detach Single File
        // ==========================================

        $(document).on('click', '.mabm-detach-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $button = $(this);
            var $item = $button.closest('.mabm-attachment-item');
            var attachmentId = $button.data('id');

            if (!confirm(mabmManager.i18n.confirmDetach)) {
                return;
            }

            $item.addClass('mabm-removing');

            $.ajax({
                url: mabmManager.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mabm_detach_media',
                    nonce: mabmManager.nonce,
                    attachment_id: attachmentId
                },
                success: function(response) {
                    if (response.success) {
                        $item.slideUp(300, function() {
                            $(this).remove();
                            updateAttachmentCount();
                            updateSelectAllState();
                        });
                    } else {
                        alert(response.data.message || 'An error occurred.');
                        $item.removeClass('mabm-removing');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $item.removeClass('mabm-removing');
                }
            });
        });

        // ==========================================
        // Delete Single File
        // ==========================================

        $(document).on('click', '.mabm-delete-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $button = $(this);
            var $item = $button.closest('.mabm-attachment-item');
            var attachmentId = $button.data('id');

            if (!confirm(mabmManager.i18n.confirmDelete)) {
                return;
            }

            $item.addClass('mabm-removing');

            $.ajax({
                url: mabmManager.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mabm_delete_media',
                    nonce: mabmManager.nonce,
                    attachment_id: attachmentId
                },
                success: function(response) {
                    if (response.success) {
                        $item.slideUp(300, function() {
                            $(this).remove();
                            updateAttachmentCount();
                            updateSelectAllState();
                        });
                    } else {
                        alert(response.data.message || 'An error occurred.');
                        $item.removeClass('mabm-removing');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $item.removeClass('mabm-removing');
                }
            });
        });

        // ==========================================
        // Helper Functions
        // ==========================================

        function getSelectedAttachmentIds() {
            var ids = [];
            $('.mabm-attachment-select:checked').each(function() {
                ids.push($(this).val());
            });
            return ids;
        }

        function updateSelectedCount() {
            var count = $('.mabm-attachment-select:checked').length;
            $('.mabm-selected-count').text(count + ' ' + mabmManager.i18n.selected);
            updateMoveButtonState();
        }

        function updateSelectAllState() {
            var total = $('.mabm-attachment-select').length;
            var checked = $('.mabm-attachment-select:checked').length;

            if (total === 0) {
                $('#mabm-select-all').prop('checked', false).prop('indeterminate', false);
            } else if (checked === 0) {
                $('#mabm-select-all').prop('checked', false).prop('indeterminate', false);
            } else if (checked === total) {
                $('#mabm-select-all').prop('checked', true).prop('indeterminate', false);
            } else {
                $('#mabm-select-all').prop('checked', false).prop('indeterminate', true);
            }

            updateSelectedCount();
        }

        function updateMoveButtonState() {
            var hasSelection = $('.mabm-attachment-select:checked').length > 0;
            var hasTarget = selectedPostId !== null;
            $('#mabm-move-selected').prop('disabled', !(hasSelection && hasTarget));
        }

        function updateAttachmentCount() {
            var $container = $('.mabm-attachments-container');
            var $grid = $container.find('.mabm-attachments-grid');
            var count = $grid.find('.mabm-attachment-item').length;

            if (count === 0) {
                $container.find('.mabm-bulk-actions-bar, .mabm-attachment-count').remove();
                $container.append('<p class="mabm-no-attachments">' + mabmManager.i18n.noAttachments + '</p>');
            } else {
                var countText = count + ' ' + (count === 1 ? mabmManager.i18n.attachedFile : mabmManager.i18n.attachedFiles);
                $container.find('.mabm-attachment-count').text(countText);
            }
        }

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

    });

})(jQuery);
