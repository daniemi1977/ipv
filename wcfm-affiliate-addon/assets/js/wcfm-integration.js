/**
 * WCFM Affiliate Pro - WCFM Integration JavaScript
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

(function($) {
    'use strict';

    var WCFMAffiliateWCFM = {
        init: function() {
            this.bindEvents();
            this.initCharts();
            this.initTabs();
        },

        bindEvents: function() {
            // Link generator
            $(document).on('click', '#wcfm-affiliate-wcfm-generate-link', this.generateLink);

            // Copy buttons
            $(document).on('click', '.wcfm-affiliate-wcfm-copy-btn', this.copyToClipboard);

            // Request payout
            $(document).on('click', '#wcfm-affiliate-wcfm-request-payout', this.requestPayout);

            // Save settings
            $(document).on('submit', '#wcfm-affiliate-wcfm-settings-form', this.saveSettings);

            // Payment method toggle
            $(document).on('change', '#wcfm-affiliate-payment-method', this.togglePaymentDetails);

            // Tab switching
            $(document).on('click', '.wcfm-affiliate-wcfm-tab', this.switchTab);

            // Period change
            $(document).on('change', '#wcfm-affiliate-wcfm-period', this.changePeriod);

            // Load more buttons
            $(document).on('click', '.wcfm-affiliate-wcfm-load-more', this.loadMore);
        },

        generateLink: function(e) {
            e.preventDefault();

            var $btn = $(this);
            var url = $('#wcfm-affiliate-wcfm-url').val();
            var campaign = $('#wcfm-affiliate-wcfm-campaign').val();

            if (!url) {
                url = window.location.origin;
            }

            $btn.prop('disabled', true).addClass('wcfm-affiliate-wcfm-loading');

            $.ajax({
                url: wcfm_affiliate_wcfm.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcfm_affiliate_generate_link',
                    nonce: wcfm_affiliate_wcfm.nonce,
                    url: url,
                    campaign: campaign
                },
                success: function(response) {
                    if (response.success) {
                        $('#wcfm-affiliate-wcfm-generated-link').val(response.data.link);
                        $('.wcfm-affiliate-wcfm-link-output').slideDown();
                        WCFMAffiliateWCFM.showNotice('success', 'Link generato con successo!');
                    } else {
                        WCFMAffiliateWCFM.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    WCFMAffiliateWCFM.showNotice('error', 'Si è verificato un errore');
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('wcfm-affiliate-wcfm-loading');
                }
            });
        },

        copyToClipboard: function(e) {
            e.preventDefault();

            var $btn = $(this);
            var $input = $btn.siblings('input').first();

            if (!$input.length) {
                $input = $btn.prev('input');
            }

            var text = $input.val();

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    WCFMAffiliateWCFM.showCopiedFeedback($btn);
                });
            } else {
                $input.select();
                document.execCommand('copy');
                WCFMAffiliateWCFM.showCopiedFeedback($btn);
            }
        },

        showCopiedFeedback: function($btn) {
            var originalText = $btn.text();
            $btn.text('Copiato!').addClass('copied');

            setTimeout(function() {
                $btn.text(originalText).removeClass('copied');
            }, 2000);
        },

        requestPayout: function(e) {
            e.preventDefault();

            if (!confirm('Sei sicuro di voler richiedere il pagamento?')) {
                return;
            }

            var $btn = $(this);

            $btn.prop('disabled', true).addClass('wcfm-affiliate-wcfm-loading');

            $.ajax({
                url: wcfm_affiliate_wcfm.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcfm_affiliate_request_payout',
                    nonce: wcfm_affiliate_wcfm.nonce
                },
                success: function(response) {
                    if (response.success) {
                        WCFMAffiliateWCFM.showNotice('success', response.data.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        WCFMAffiliateWCFM.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    WCFMAffiliateWCFM.showNotice('error', 'Si è verificato un errore');
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('wcfm-affiliate-wcfm-loading');
                }
            });
        },

        saveSettings: function(e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');

            $btn.prop('disabled', true).addClass('wcfm-affiliate-wcfm-loading');

            $.ajax({
                url: wcfm_affiliate_wcfm.ajax_url,
                type: 'POST',
                data: $form.serialize() + '&action=wcfm_affiliate_save_settings&nonce=' + wcfm_affiliate_wcfm.nonce,
                success: function(response) {
                    if (response.success) {
                        WCFMAffiliateWCFM.showNotice('success', 'Impostazioni salvate!');
                    } else {
                        WCFMAffiliateWCFM.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    WCFMAffiliateWCFM.showNotice('error', 'Si è verificato un errore');
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('wcfm-affiliate-wcfm-loading');
                }
            });
        },

        togglePaymentDetails: function() {
            var method = $(this).val();

            $('.wcfm-affiliate-wcfm-payment-details').hide();
            $('#wcfm-affiliate-wcfm-' + method + '-details').show();
        },

        switchTab: function(e) {
            e.preventDefault();

            var $tab = $(this);
            var target = $tab.data('tab');

            // Update tabs
            $('.wcfm-affiliate-wcfm-tab').removeClass('active');
            $tab.addClass('active');

            // Update content
            $('.wcfm-affiliate-wcfm-tab-content').removeClass('active');
            $('#wcfm-affiliate-wcfm-' + target).addClass('active');
        },

        changePeriod: function() {
            var period = $(this).val();
            var $container = $(this).closest('.wcfm-affiliate-wcfm-section');

            $container.find('.wcfm-affiliate-wcfm-section-content').addClass('wcfm-affiliate-wcfm-loading');

            $.ajax({
                url: wcfm_affiliate_wcfm.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcfm_affiliate_get_stats',
                    nonce: wcfm_affiliate_wcfm.nonce,
                    period: period
                },
                success: function(response) {
                    if (response.success) {
                        WCFMAffiliateWCFM.updateStats(response.data.stats);
                        if (response.data.chart) {
                            WCFMAffiliateWCFM.updateChart(response.data.chart);
                        }
                    }
                },
                complete: function() {
                    $container.find('.wcfm-affiliate-wcfm-section-content').removeClass('wcfm-affiliate-wcfm-loading');
                }
            });
        },

        updateStats: function(stats) {
            $.each(stats, function(key, value) {
                var $stat = $('.wcfm-affiliate-wcfm-stat[data-stat="' + key + '"]');
                if ($stat.length) {
                    $stat.find('.wcfm-affiliate-wcfm-stat-value').text(value);
                }
            });
        },

        loadMore: function(e) {
            e.preventDefault();

            var $btn = $(this);
            var type = $btn.data('type');
            var page = parseInt($btn.data('page')) || 1;
            var $table = $btn.prev('table').find('tbody');

            $btn.prop('disabled', true).addClass('wcfm-affiliate-wcfm-loading');

            $.ajax({
                url: wcfm_affiliate_wcfm.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcfm_affiliate_dashboard_action',
                    dashboard_action: 'get_' + type,
                    nonce: wcfm_affiliate_wcfm.nonce,
                    page: page + 1
                },
                success: function(response) {
                    if (response.success) {
                        $table.append(response.data.html);
                        $btn.data('page', page + 1);

                        if (page + 1 >= response.data.total_pages) {
                            $btn.remove();
                        }
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('wcfm-affiliate-wcfm-loading');
                }
            });
        },

        initCharts: function() {
            var $chart = $('#wcfm-affiliate-wcfm-chart');
            if (!$chart.length || typeof Chart === 'undefined') {
                return;
            }

            var ctx = $chart[0].getContext('2d');
            var chartData = $chart.data('chart');

            if (!chartData) {
                return;
            }

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels || [],
                    datasets: [{
                        label: 'Commissioni',
                        data: chartData.data || [],
                        borderColor: '#00897b',
                        backgroundColor: 'rgba(0, 137, 123, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#333',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return '€' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '€' + value;
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        },

        updateChart: function(chartData) {
            if (!this.chart || !chartData) {
                return;
            }

            this.chart.data.labels = chartData.labels;
            this.chart.data.datasets[0].data = chartData.data;
            this.chart.update('none');
        },

        initTabs: function() {
            // Check for hash in URL
            var hash = window.location.hash;
            if (hash) {
                var $tab = $('.wcfm-affiliate-wcfm-tab[data-tab="' + hash.substring(1) + '"]');
                if ($tab.length) {
                    $tab.trigger('click');
                }
            }
        },

        showNotice: function(type, message) {
            var $notice = $('<div class="wcfm-affiliate-wcfm-notice wcfm-affiliate-wcfm-notice-' + type + '">' +
                '<span class="dashicons dashicons-' + (type === 'success' ? 'yes-alt' : 'warning') + '"></span>' +
                message +
                '</div>');

            // Remove existing notices
            $('.wcfm-affiliate-wcfm-notice').remove();

            // Add new notice
            $('.wcfm-affiliate-wcfm-dashboard').prepend($notice);

            // Auto hide after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        WCFMAffiliateWCFM.init();
    });

    // Also initialize on WCFM dashboard load (for AJAX loaded content)
    $(document).on('wcfm_dashboard_loaded', function() {
        WCFMAffiliateWCFM.init();
    });

})(jQuery);
