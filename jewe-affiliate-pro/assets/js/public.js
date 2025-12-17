/**
 * JEWE Affiliate Pro - Public JavaScript
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        JEWE_Affiliate.init();
    });

    var JEWE_Affiliate = {
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initCopyButtons();
        },

        bindEvents: function() {
            // Registration form
            $(document).on('submit', '.jewe-register-form', this.handleRegister);

            // Payout request
            $(document).on('submit', '.jewe-payout-form', this.handlePayoutRequest);

            // Period selector
            $(document).on('change', '.jewe-period-selector', this.handlePeriodChange);

            // Export buttons
            $(document).on('click', '.jewe-export-btn', this.handleExport);
        },

        initTabs: function() {
            $('.jewe-tabs .jewe-tab').on('click', function() {
                var target = $(this).data('tab');

                // Update active tab
                $(this).siblings().removeClass('active');
                $(this).addClass('active');

                // Show target content
                $(this).closest('.jewe-dashboard').find('.jewe-tab-content').removeClass('active');
                $('#' + target).addClass('active');

                // Load data if needed
                JEWE_Affiliate.loadTabData(target);
            });
        },

        initCopyButtons: function() {
            $(document).on('click', '.jewe-copy-btn', function() {
                var url = $(this).data('url') || $(this).siblings('input').val();
                var btn = $(this);

                JEWE_Affiliate.copyToClipboard(url, function() {
                    btn.addClass('copied').text(jeweAffiliate.strings.copied);
                    setTimeout(function() {
                        btn.removeClass('copied').text('Copia');
                    }, 2000);
                });
            });
        },

        copyToClipboard: function(text, callback) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(callback);
            } else {
                // Fallback for older browsers
                var textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                if (callback) callback();
            }
        },

        handleRegister: function(e) {
            e.preventDefault();

            var form = $(this);
            var submitBtn = form.find('.jewe-submit-btn');
            var originalText = submitBtn.text();

            submitBtn.prop('disabled', true).text(jeweAffiliate.strings.loading);

            $.ajax({
                url: jeweAffiliate.ajaxurl,
                type: 'POST',
                data: {
                    action: 'jewe_register_affiliate',
                    nonce: jeweAffiliate.nonce,
                    name: form.find('[name="name"]').val(),
                    email: form.find('[name="email"]').val(),
                    password: form.find('[name="password"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Show success and redirect
                        form.html('<div class="jewe-notice jewe-notice-success">' + response.data.message + '</div>');
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1500);
                        }
                    } else {
                        JEWE_Affiliate.showError(form, response.data.message);
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    JEWE_Affiliate.showError(form, jeweAffiliate.strings.error);
                    submitBtn.prop('disabled', false).text(originalText);
                }
            });
        },

        handlePayoutRequest: function(e) {
            e.preventDefault();

            if (!confirm(jeweAffiliate.strings.confirm_payout)) {
                return;
            }

            var form = $(this);
            var submitBtn = form.find('.jewe-submit-btn');

            submitBtn.prop('disabled', true).text(jeweAffiliate.strings.loading);

            $.ajax({
                url: jeweAffiliate.ajaxurl,
                type: 'POST',
                data: {
                    action: 'jewe_request_payout',
                    nonce: jeweAffiliate.nonce,
                    amount: form.find('[name="amount"]').val(),
                    method: form.find('[name="method"]').val(),
                    details: form.find('[name="details"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        form.before('<div class="jewe-notice jewe-notice-success">' + response.data.message + '</div>');
                        form[0].reset();
                        submitBtn.prop('disabled', false).text('Richiedi Pagamento');
                    } else {
                        JEWE_Affiliate.showError(form, response.data.message);
                        submitBtn.prop('disabled', false).text('Richiedi Pagamento');
                    }
                },
                error: function() {
                    JEWE_Affiliate.showError(form, jeweAffiliate.strings.error);
                    submitBtn.prop('disabled', false).text('Richiedi Pagamento');
                }
            });
        },

        handlePeriodChange: function() {
            var period = $(this).val();
            var section = $('.jewe-tab.active').data('tab') || 'overview';

            JEWE_Affiliate.loadDashboardData(section, period);
        },

        loadTabData: function(section) {
            var period = $('.jewe-period-selector').val() || '30days';
            this.loadDashboardData(section, period);
        },

        loadDashboardData: function(section, period) {
            var container = $('#' + section);

            container.addClass('loading');

            $.ajax({
                url: jeweAffiliate.ajaxurl,
                type: 'POST',
                data: {
                    action: 'jewe_get_dashboard_data',
                    nonce: jeweAffiliate.nonce,
                    section: section,
                    period: period
                },
                success: function(response) {
                    container.removeClass('loading');
                    if (response.success) {
                        JEWE_Affiliate.renderSection(section, response.data);
                    }
                },
                error: function() {
                    container.removeClass('loading');
                }
            });
        },

        renderSection: function(section, data) {
            // This would render different sections based on the data
            // For simplicity, we'll just trigger a custom event
            $(document).trigger('jewe_section_loaded', [section, data]);
        },

        handleExport: function(e) {
            e.preventDefault();

            var btn = $(this);
            var type = btn.data('type');
            var format = btn.data('format') || 'csv';
            var period = $('.jewe-period-selector').val() || '30days';

            // Create form and submit
            var form = $('<form>', {
                method: 'POST',
                action: jeweAffiliate.ajaxurl
            });

            form.append($('<input>', { type: 'hidden', name: 'action', value: 'jewe_export_report' }));
            form.append($('<input>', { type: 'hidden', name: 'nonce', value: jeweAffiliate.nonce }));
            form.append($('<input>', { type: 'hidden', name: 'type', value: type }));
            form.append($('<input>', { type: 'hidden', name: 'format', value: format }));
            form.append($('<input>', { type: 'hidden', name: 'period', value: period }));

            $('body').append(form);
            form.submit();
            form.remove();
        },

        showError: function(container, message) {
            container.find('.jewe-notice').remove();
            container.prepend('<div class="jewe-notice jewe-notice-error">' + message + '</div>');
        },

        showSuccess: function(container, message) {
            container.find('.jewe-notice').remove();
            container.prepend('<div class="jewe-notice jewe-notice-success">' + message + '</div>');
        },

        formatCurrency: function(amount) {
            return '€' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        },

        formatNumber: function(num) {
            return parseInt(num).toLocaleString();
        }
    };

    // Expose globally
    window.JEWE_Affiliate = JEWE_Affiliate;

})(jQuery);
