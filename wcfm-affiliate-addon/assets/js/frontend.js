/**
 * WCFM Affiliate Pro - Frontend JavaScript
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Main frontend object
    var WCFMAffiliateFrontend = {
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initCharts();
        },

        bindEvents: function() {
            // Generate link
            $(document).on('click', '#wcfm-affiliate-generate-link', this.generateLink);

            // Copy to clipboard
            $(document).on('click', '.wcfm-affiliate-copy-btn', this.copyToClipboard);

            // Request payout
            $(document).on('click', '#wcfm-affiliate-request-payout', this.requestPayout);

            // Save settings
            $(document).on('submit', '#wcfm-affiliate-settings-form', this.saveSettings);

            // Payment method change
            $(document).on('change', '#payment_method', this.toggleBankDetails);

            // Load more referrals
            $(document).on('click', '.wcfm-affiliate-load-more-referrals', this.loadMoreReferrals);

            // Load more payouts
            $(document).on('click', '.wcfm-affiliate-load-more-payouts', this.loadMorePayouts);

            // Tab navigation
            $(document).on('click', '.wcfm-affiliate-tab', this.switchTab);

            // Period selector
            $(document).on('change', '#wcfm-affiliate-period', this.changePeriod);

            // Become affiliate form
            $(document).on('submit', '#wcfm-affiliate-become-form', this.becomeAffiliate);
        },

        generateLink: function(e) {
            e.preventDefault();

            var $btn = $(this);
            var url = $('#wcfm-affiliate-url').val();
            var campaign = $('#wcfm-affiliate-campaign').val();

            if (!url) {
                url = window.location.origin;
            }

            $btn.prop('disabled', true).text(wcfm_affiliate_frontend.i18n.loading);

            $.ajax({
                url: wcfm_affiliate_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcfm_affiliate_generate_link',
                    nonce: wcfm_affiliate_frontend.nonce,
                    url: url,
                    campaign: campaign
                },
                success: function(response) {
                    if (response.success) {
                        $('#wcfm-affiliate-generated-link').val(response.data.link);
                        $('.wcfm-affiliate-generated-link').slideDown();
                    } else {
                        WCFMAffiliateFrontend.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    WCFMAffiliateFrontend.showNotice('error', wcfm_affiliate_frontend.i18n.error);
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Genera Link');
                }
            });
        },

        copyToClipboard: function(e) {
            e.preventDefault();

            var $btn = $(this);
            var $input = $btn.siblings('input');
            var text = $input.val();

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    WCFMAffiliateFrontend.showCopiedFeedback();
                }).catch(function() {
                    WCFMAffiliateFrontend.fallbackCopy($input);
                });
            } else {
                WCFMAffiliateFrontend.fallbackCopy($input);
            }
        },

        fallbackCopy: function($input) {
            $input.select();
            document.execCommand('copy');
            WCFMAffiliateFrontend.showCopiedFeedback();
        },

        showCopiedFeedback: function() {
            var $feedback = $('<div class="wcfm-affiliate-copied">' + wcfm_affiliate_frontend.i18n.copied + '</div>');
            $('body').append($feedback);

            setTimeout(function() {
                $feedback.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 2000);
        },

        requestPayout: function(e) {
            e.preventDefault();

            if (!confirm(wcfm_affiliate_frontend.i18n.confirm_payout)) {
                return;
            }

            var $btn = $(this);
            var amount = $btn.data('amount') || 0;
            var method = $btn.data('method') || '';

            $btn.prop('disabled', true).text(wcfm_affiliate_frontend.i18n.loading);

            $.ajax({
                url: wcfm_affiliate_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcfm_affiliate_request_payout',
                    nonce: wcfm_affiliate_frontend.nonce,
                    amount: amount,
                    method: method
                },
                success: function(response) {
                    if (response.success) {
                        WCFMAffiliateFrontend.showNotice('success', response.data.message);
                        location.reload();
                    } else {
                        WCFMAffiliateFrontend.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    WCFMAffiliateFrontend.showNotice('error', wcfm_affiliate_frontend.i18n.error);
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Richiedi Pagamento');
                }
            });
        },

        saveSettings: function(e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var data = $form.serialize();

            $btn.prop('disabled', true).text(wcfm_affiliate_frontend.i18n.loading);

            $.ajax({
                url: wcfm_affiliate_frontend.ajax_url,
                type: 'POST',
                data: data + '&action=wcfm_affiliate_save_settings&nonce=' + wcfm_affiliate_frontend.nonce,
                success: function(response) {
                    if (response.success) {
                        WCFMAffiliateFrontend.showNotice('success', response.data.message);
                    } else {
                        WCFMAffiliateFrontend.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    WCFMAffiliateFrontend.showNotice('error', wcfm_affiliate_frontend.i18n.error);
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Salva Impostazioni');
                }
            });
        },

        toggleBankDetails: function() {
            var method = $(this).val();
            var $bankDetails = $('.wcfm-affiliate-bank-details');

            if (method === 'bank_transfer') {
                $bankDetails.removeClass('hidden').slideDown();
            } else {
                $bankDetails.slideUp().addClass('hidden');
            }
        },

        loadMoreReferrals: function(e) {
            e.preventDefault();

            var $btn = $(this);
            var page = $btn.data('page') || 1;
            var $container = $('#wcfm-affiliate-referrals-table tbody');

            $btn.prop('disabled', true).text(wcfm_affiliate_frontend.i18n.loading);

            $.ajax({
                url: wcfm_affiliate_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcfm_affiliate_dashboard_action',
                    dashboard_action: 'get_referrals',
                    nonce: wcfm_affiliate_frontend.nonce,
                    page: page + 1
                },
                success: function(response) {
                    if (response.success) {
                        $container.append(response.data.html);
                        $btn.data('page', page + 1);

                        if (page + 1 >= response.data.total_pages) {
                            $btn.remove();
                        }
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Carica altri');
                }
            });
        },

        loadMorePayouts: function(e) {
            e.preventDefault();

            var $btn = $(this);
            var page = $btn.data('page') || 1;
            var $container = $('#wcfm-affiliate-payouts-table tbody');

            $btn.prop('disabled', true).text(wcfm_affiliate_frontend.i18n.loading);

            $.ajax({
                url: wcfm_affiliate_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcfm_affiliate_dashboard_action',
                    dashboard_action: 'get_payouts',
                    nonce: wcfm_affiliate_frontend.nonce,
                    page: page + 1
                },
                success: function(response) {
                    if (response.success) {
                        $container.append(response.data.html);
                        $btn.data('page', page + 1);

                        if (page + 1 >= response.data.total_pages) {
                            $btn.remove();
                        }
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Carica altri');
                }
            });
        },

        initTabs: function() {
            var hash = window.location.hash;
            if (hash) {
                var $tab = $('.wcfm-affiliate-tab[data-tab="' + hash.substring(1) + '"]');
                if ($tab.length) {
                    $tab.trigger('click');
                }
            }
        },

        switchTab: function(e) {
            e.preventDefault();

            var $tab = $(this);
            var target = $tab.data('tab');

            // Update tabs
            $('.wcfm-affiliate-tab').removeClass('active');
            $tab.addClass('active');

            // Update content
            $('.wcfm-affiliate-tab-content').removeClass('active');
            $('#' + target).addClass('active');

            // Update URL hash
            if (history.pushState) {
                history.pushState(null, null, '#' + target);
            }
        },

        changePeriod: function() {
            var period = $(this).val();

            $.ajax({
                url: wcfm_affiliate_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcfm_affiliate_get_stats',
                    nonce: wcfm_affiliate_frontend.nonce,
                    period: period
                },
                success: function(response) {
                    if (response.success) {
                        WCFMAffiliateFrontend.updateStats(response.data.stats);
                        WCFMAffiliateFrontend.updateChart(response.data.chart);
                    }
                }
            });
        },

        updateStats: function(stats) {
            // Update stat cards if they exist
            if (stats.earnings) {
                $('.wcfm-affiliate-stat-card[data-stat="earnings"] .wcfm-affiliate-stat-card-value').text(stats.earnings);
            }
            if (stats.referrals) {
                $('.wcfm-affiliate-stat-card[data-stat="referrals"] .wcfm-affiliate-stat-card-value').text(stats.referrals);
            }
            if (stats.visits) {
                $('.wcfm-affiliate-stat-card[data-stat="visits"] .wcfm-affiliate-stat-card-value').text(stats.visits);
            }
            if (stats.conversion) {
                $('.wcfm-affiliate-stat-card[data-stat="conversion"] .wcfm-affiliate-stat-card-value').text(stats.conversion + '%');
            }
        },

        initCharts: function() {
            var $chart = $('#wcfm-affiliate-chart');
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
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '€' + value.toFixed(2);
                                }
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
            this.chart.update();
        },

        becomeAffiliate: function(e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $form.find('#wcfm-affiliate-become-btn');
            var data = $form.serialize();

            $btn.prop('disabled', true).text(wcfm_affiliate_frontend.i18n.loading);

            $.ajax({
                url: wcfm_affiliate_frontend.ajax_url,
                type: 'POST',
                data: data + '&action=wcfm_affiliate_become_affiliate',
                success: function(response) {
                    if (response.success) {
                        WCFMAffiliateFrontend.showNotice('success', response.data.message);
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        }
                    } else {
                        WCFMAffiliateFrontend.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    WCFMAffiliateFrontend.showNotice('error', wcfm_affiliate_frontend.i18n.error);
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Diventa Affiliato');
                }
            });
        },

        showNotice: function(type, message) {
            var $notice = $('<div class="wcfm-affiliate-notice wcfm-affiliate-notice-' + type + '">' + message + '</div>');

            // Remove existing notices
            $('.wcfm-affiliate-notice').remove();

            // Add new notice at the top of the dashboard
            $('.wcfm-affiliate-dashboard, .wcfm-affiliate-register-form').prepend($notice);

            // Auto hide after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);

            // Scroll to notice
            $('html, body').animate({
                scrollTop: $notice.offset().top - 100
            }, 300);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        WCFMAffiliateFrontend.init();
    });

})(jQuery);
