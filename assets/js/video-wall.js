/**
 * IPV Video Wall JavaScript
 * Handles AJAX filtering, pagination, and interactions
 */

(function($) {
    'use strict';

    let currentPage = 1;
    let totalPages = 1;
    let isLoading = false;

    const VideoWall = {
        init: function() {
            this.bindEvents();
            this.updatePaginationState();
        },

        bindEvents: function() {
            // Auto-apply filters on select change
            $(document).on('change', '.ipv-filter-select', function() {
                VideoWall.resetAndLoadVideos();
            });

            // Filter on Enter key in search input
            $(document).on('keypress', '#ipv-filter-search', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    VideoWall.resetAndLoadVideos();
                }
            });

            // Auto-apply search filter after typing (with debounce)
            let searchTimeout;
            $(document).on('input', '#ipv-filter-search', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    VideoWall.resetAndLoadVideos();
                }, 500); // Wait 500ms after user stops typing
            });

            // Load More button
            $(document).on('click', '.ipv-load-more-btn', function(e) {
                e.preventDefault();

                if (isLoading) {
                    return;
                }

                const $btn = $(this);
                const nextPage = parseInt($btn.data('page')) + 1;
                const totalPages = parseInt($btn.data('total-pages'));

                if (nextPage <= totalPages) {
                    VideoWall.loadMoreVideos(nextPage);
                }
            });
        },

        resetAndLoadVideos: function() {
            // Reset to first page and clear existing videos
            currentPage = 1;
            $('.ipv-video-grid').empty();
            $('.ipv-load-more-btn').data('page', 0);
            $('.ipv-videos-loaded').text('0');
            VideoWall.loadMoreVideos(1);
        },

        loadMoreVideos: function(page) {
            if (isLoading) {
                return;
            }

            isLoading = true;
            currentPage = page;

            const $container = $('.ipv-video-wall-container');
            const $grid = $('.ipv-video-grid');
            const $loading = $('.ipv-video-loading');
            const $loadMoreBtn = $('.ipv-load-more-btn');
            const perPage = $container.data('per-page') || 5;

            // Get filter values
            const categoria = $('#ipv-filter-categoria').val() || '';
            const relatore = $('#ipv-filter-relatore').val() || '';
            const search = $('#ipv-filter-search').val() || '';

            // Show loading state
            $loadMoreBtn.prop('disabled', true).find('.ipv-load-more-text').text('Caricamento...');
            $loading.show();

            $.ajax({
                url: ipvVideoWall.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ipv_load_videos',
                    nonce: ipvVideoWall.nonce,
                    page: page,
                    per_page: perPage,
                    categoria: categoria,
                    relatore: relatore,
                    search: search
                },
                success: function(response) {
                    if (response.success) {
                        // Append new videos instead of replacing
                        $grid.append(response.data.html);
                        currentPage = response.data.current_page;
                        totalPages = response.data.total_pages;

                        // Update button state and counter
                        $loadMoreBtn.data('page', currentPage);

                        const videosLoaded = $grid.find('.ipv-video-card').length;
                        $('.ipv-videos-loaded').text(videosLoaded);

                        // Hide button if no more pages
                        if (currentPage >= totalPages) {
                            $('.ipv-load-more-wrapper').fadeOut();
                        }

                        // Smooth fade in for new videos
                        $grid.find('.ipv-video-card:nth-last-child(-n+' + perPage + ')').css('opacity', '0').animate({opacity: 1}, 300);
                    } else {
                        console.error('Error loading videos:', response);
                        VideoWall.showError('Si è verificato un errore durante il caricamento dei video.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    VideoWall.showError('Errore di connessione. Riprova più tardi.');
                },
                complete: function() {
                    $loading.hide();
                    $loadMoreBtn.prop('disabled', false).find('.ipv-load-more-text').text('Carica altri 5 video');
                    isLoading = false;
                }
            });
        },

        updatePaginationState: function() {
            // Load more button state is managed in loadMoreVideos
            // No need for complex pagination state anymore
        },

        showError: function(message) {
            const $grid = $('.ipv-video-grid');
            $grid.html('<div class="ipv-no-videos"><p>' + message + '</p></div>');
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('.ipv-video-wall-container').length) {
            VideoWall.init();
        }
    });

})(jQuery);
