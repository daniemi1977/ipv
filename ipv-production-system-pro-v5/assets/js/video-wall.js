/**
 * Video Wall JavaScript
 * IPV Production System Pro v5
 */

(function ($) {
    'use strict';

    class IPVVideoWall {
        constructor(element) {
            this.$el = $(element);
            this.$grid = this.$el.find('#ipv-video-grid');
            this.$pagination = this.$el.find('#ipv-pagination');
            this.$loading = this.$el.find('.ipv-loading');
            this.$resultsCount = this.$el.find('#ipv-results-count');

            this.perPage = parseInt(this.$el.data('per-page')) || 12;
            this.layout = this.$el.data('layout') || 'grid';
            this.columns = parseInt(this.$el.data('columns')) || 3;
            this.currentPage = 1;

            this.init();
        }

        init() {
            this.bindEvents();
            this.loadVideos();
        }

        bindEvents() {
            const self = this;

            // Filtri
            this.$el.on('change', '.ipv-filter-select', function () {
                self.currentPage = 1;
                self.loadVideos();
            });

            // Ricerca (con debounce)
            let searchTimeout;
            this.$el.on('keyup', '#ipv-search', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    self.currentPage = 1;
                    self.loadVideos();
                }, 500);
            });

            // Reset filtri
            this.$el.on('click', '#ipv-reset-filters', function () {
                self.resetFilters();
            });

            // Paginazione
            this.$el.on('click', '.ipv-page-btn', function () {
                const page = parseInt($(this).data('page'));
                if (page && page !== self.currentPage) {
                    self.currentPage = page;
                    self.loadVideos();
                    self.scrollToTop();
                }
            });
        }

        loadVideos() {
            const self = this;

            this.showLoading();

            const data = {
                action: 'ipv_load_videos',
                nonce: ipvVideoWall.nonce,
                page: this.currentPage,
                per_page: this.perPage,
                layout: this.layout,
                columns: this.columns,
                search: this.$el.find('#ipv-search').val(),
                anno: this.$el.find('#ipv-filter-anno').val(),
                relatore: this.$el.find('#ipv-filter-relatore').val(),
                argomento: this.$el.find('#ipv-filter-argomento').val()
            };

            $.post(ipvVideoWall.ajaxurl, data, function (response) {
                self.hideLoading();

                if (response.success) {
                    self.$grid.html(response.data.html);
                    self.$pagination.html(response.data.pagination);
                    self.$resultsCount.text(response.data.results_info);
                } else {
                    self.$grid.html('<div class="ipv-no-results"><p>Errore nel caricamento dei video.</p></div>');
                }
            }).fail(function () {
                self.hideLoading();
                self.$grid.html('<div class="ipv-no-results"><p>Errore di connessione.</p></div>');
            });
        }

        resetFilters() {
            this.$el.find('#ipv-search').val('');
            this.$el.find('.ipv-filter-select').val('');
            this.currentPage = 1;
            this.loadVideos();
        }

        showLoading() {
            this.$loading.show();
            this.$grid.css('opacity', '0.5');
        }

        hideLoading() {
            this.$loading.hide();
            this.$grid.css('opacity', '1');
        }

        scrollToTop() {
            $('html, body').animate({
                scrollTop: this.$el.offset().top - 100
            }, 500);
        }
    }

    // Inizializza
    $(document).ready(function () {
        $('.ipv-video-wall').each(function () {
            new IPVVideoWall(this);
        });
    });

})(jQuery);
