/**
 * JEWE Affiliate Pro - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        JEWE_Admin.init();
    });

    var JEWE_Admin = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Approve affiliate
            $(document).on('click', '.jewe-approve-btn', this.approveAffiliate);

            // Reject affiliate
            $(document).on('click', '.jewe-reject-btn', this.rejectAffiliate);

            // Process payout
            $(document).on('click', '.jewe-process-payout-btn', this.processPayout);

            // Period change
            $(document).on('change', '#jewe-period-select', this.loadDashboardData);
        },

        approveAffiliate: function() {
            var id = $(this).data('id');

            if (!confirm(jeweAffiliateAdmin.strings.confirm_approve)) {
                return;
            }

            $.post(jeweAffiliateAdmin.ajaxurl, {
                action: 'jewe_approve_affiliate',
                nonce: jeweAffiliateAdmin.nonce,
                affiliate_id: id
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || jeweAffiliateAdmin.strings.error);
                }
            });
        },

        rejectAffiliate: function() {
            var id = $(this).data('id');

            if (!confirm(jeweAffiliateAdmin.strings.confirm_reject)) {
                return;
            }

            $.post(jeweAffiliateAdmin.ajaxurl, {
                action: 'jewe_reject_affiliate',
                nonce: jeweAffiliateAdmin.nonce,
                affiliate_id: id
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || jeweAffiliateAdmin.strings.error);
                }
            });
        },

        processPayout: function() {
            var btn = $(this);
            var id = btn.data('id');
            var action = btn.data('action');

            var confirmMsg = action === 'approve' ? jeweAffiliateAdmin.strings.confirm_payout : jeweAffiliateAdmin.strings.confirm_reject;

            if (!confirm(confirmMsg)) {
                return;
            }

            $.post(jeweAffiliateAdmin.ajaxurl, {
                action: 'jewe_process_payout',
                nonce: jeweAffiliateAdmin.nonce,
                payout_id: id,
                payout_action: action
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || jeweAffiliateAdmin.strings.error);
                }
            });
        },

        loadDashboardData: function() {
            var period = $(this).val();

            $.post(jeweAffiliateAdmin.ajaxurl, {
                action: 'jewe_get_dashboard_data',
                nonce: jeweAffiliateAdmin.nonce,
                period: period
            }, function(response) {
                if (response.success && response.data.chart_data) {
                    JEWE_Admin.updateChart(response.data.chart_data);
                    if (response.data.overview) {
                        JEWE_Admin.updateKPIs(response.data.overview);
                    }
                }
            });
        },

        updateChart: function(data) {
            if (window.jeweChart) {
                window.jeweChart.data.labels = data.labels;
                window.jeweChart.data.datasets[0].data = data.commissions;
                window.jeweChart.data.datasets[1].data = data.revenue;
                window.jeweChart.update();
            }
        },

        updateKPIs: function(data) {
            // Update KPI values dynamically
            $('.jewe-kpi-card').each(function() {
                var type = $(this).data('type');
                if (type && data[type] !== undefined) {
                    $(this).find('.jewe-kpi-value').text(
                        typeof data[type] === 'number' && type.includes('earnings')
                            ? '€' + data[type].toFixed(2)
                            : data[type]
                    );
                }
            });
        }
    };

    window.JEWE_Admin = JEWE_Admin;

})(jQuery);
