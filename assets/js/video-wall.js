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
                VideoWall.loadVideos(1);
            });

            // Filter on Enter key in search input
            $(document).on('keypress', '#ipv-filter-search', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    VideoWall.loadVideos(1);
                }
            });

            // Auto-apply search filter after typing (with debounce)
            let searchTimeout;
            $(document).on('input', '#ipv-filter-search', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    VideoWall.loadVideos(1);
                }, 500); // Wait 500ms after user stops typing
            });

            // Pagination buttons
            $(document).on('click', '.ipv-page-btn', function(e) {
                e.preventDefault();

                if ($(this).is(':disabled') || isLoading) {
                    return;
                }

                const action = $(this).data('page');
                let newPage = currentPage;

                if (action === 'next') {
                    newPage = Math.min(currentPage + 1, totalPages);
                } else if (action === 'prev') {
                    newPage = Math.max(currentPage - 1, 1);
                }

                if (newPage !== currentPage) {
                    VideoWall.loadVideos(newPage);
                    VideoWall.scrollToTop();
                }
            });
        },

        loadVideos: function(page) {
            if (isLoading) {
                return;
            }

            isLoading = true;
            currentPage = page;

            const $container = $('.ipv-video-wall-container');
            const $grid = $('.ipv-video-grid');
            const $loading = $('.ipv-video-loading');
            const perPage = $container.data('per-page') || 5;

            // Get filter values
            const categoria = $('#ipv-filter-categoria').val() || '';
            const relatore = $('#ipv-filter-relatore').val() || '';
            const search = $('#ipv-filter-search').val() || '';

            // Show loading state
            $grid.css('opacity', '0.3');
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
                        $grid.html(response.data.html);
                        currentPage = response.data.current_page;
                        totalPages = response.data.total_pages;

                        VideoWall.updatePaginationState();

                        // Smooth fade in
                        $grid.css('opacity', '0');
                        setTimeout(function() {
                            $grid.css('opacity', '1');
                        }, 50);
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
                    $grid.css('opacity', '1');
                    isLoading = false;
                }
            });
        },

        updatePaginationState: function() {
            const $prevBtn = $('.ipv-page-prev');
            const $nextBtn = $('.ipv-page-next');
            const $currentPageSpan = $('.ipv-current-page');

            // Update current page display
            $currentPageSpan.text(currentPage);

            // Update button states
            if (currentPage <= 1) {
                $prevBtn.prop('disabled', true);
            } else {
                $prevBtn.prop('disabled', false);
            }

            if (currentPage >= totalPages) {
                $nextBtn.prop('disabled', true);
            } else {
                $nextBtn.prop('disabled', false);
            }

            // Hide pagination if only one page
            if (totalPages <= 1) {
                $('.ipv-video-pagination').hide();
            } else {
                $('.ipv-video-pagination').show();
            }
        },

        scrollToTop: function() {
            const $container = $('.ipv-video-wall-container');
            if ($container.length) {
                $('html, body').animate({
                    scrollTop: $container.offset().top - 100
                }, 400);
            }
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
