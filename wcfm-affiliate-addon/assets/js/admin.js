/**
 * WCFM Affiliate Pro - Admin JavaScript
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

(function($) {
    'use strict';

    var WCFMAffiliateAdmin = {
        init: function() {
            this.bindEvents();
            this.initColorPickers();
            this.initTierTable();
        },

        bindEvents: function() {
            // Affiliate actions
            $(document).on('click', '.wcfm-affiliate-approve-btn', this.approveAffiliate);
            $(document).on('click', '.wcfm-affiliate-reject-btn', this.rejectAffiliate);
            $(document).on('click', '.wcfm-affiliate-suspend-btn', this.suspendAffiliate);
            $(document).on('click', '.wcfm-affiliate-delete-btn', this.deleteAffiliate);

            // Commission actions
            $(document).on('click', '.wcfm-commission-approve-btn', this.approveCommission);
            $(document).on('click', '.wcfm-commission-reject-btn', this.rejectCommission);

            // Payout actions
            $(document).on('click', '.wcfm-payout-cancel-btn', this.cancelPayout);
            $(document).on('submit', '#process-payout-form', this.processPayout);

            // Export
            $(document).on('click', '#wcfm-affiliate-export-btn', this.exportData);

            // Settings
            $(document).on('click', '.wcfm-affiliate-settings-tabs .nav-tab', this.switchSettingsTab);

            // MLM settings toggle
            $(document).on('change', '#wcfm_affiliate_enable_mlm', this.toggleMLMSettings);

            // Tier management
            $(document).on('click', '#wcfm-affiliate-add-tier', this.addTier);
            $(document).on('click', '.wcfm-affiliate-remove-tier', this.removeTier);

            // Select all checkbox
            $(document).on('change', '#cb-select-all-1', this.selectAll);

            // Bulk actions
            $(document).on('submit', '#commissions-form', this.handleBulkAction);
        },

        approveAffiliate: function(e) {
            e.preventDefault();

            var $btn = $(this);
            var id = $btn.data('id');

            if (!confirm(wcfm_affiliate_admin.i18n.confirm_approve)) {
                return;
            }

            WCFMAffiliateAdmin.doAjax('approve_affiliate', { affiliate_id: id }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            });
        },

        rejectAffiliate: function(e) {
            e.preventDefault();

            var $btn = $(this);
            var id = $btn.data('id');

            if (!confirm(wcfm_affiliate_admin.i18n.confirm_reject)) {
                return;
            }

            WCFMAffiliateAdmin.doAjax('reject_affiliate', { affiliate_id: id }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            });
        },

        suspendAffiliate: function(e) {
            e.preventDefault();

            var $btn = $(this);
            var id = $btn.data('id');

            if (!confirm(wcfm_affiliate_admin.i18n.confirm_suspend)) {
                return;
            }

            WCFMAffiliateAdmin.doAjax('suspend_affiliate', { affiliate_id: id }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            });
        },

        deleteAffiliate: function(e) {
            e.preventDefault();

            var $btn = $(this);
            var id = $btn.data('id');

            if (!confirm(wcfm_affiliate_admin.i18n.confirm_delete)) {
                return;
            }

            WCFMAffiliateAdmin.doAjax('delete_affiliate', { affiliate_id: id }, function(response) {
                if (response.success) {
                    window.location.href = wcfm_affiliate_admin.affiliates_url;
                } else {
                    alert(response.data.message);
                }
            });
        },

        approveCommission: function(e) {
            e.preventDefault();

            var $btn = $(this);
            var id = $btn.data('id');

            WCFMAffiliateAdmin.doAjax('approve_commission', { commission_id: id }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            });
        },

        rejectCommission: function(e) {
            e.preventDefault();

            var $btn = $(this);
            var id = $btn.data('id');

            if (!confirm(wcfm_affiliate_admin.i18n.confirm_reject_commission)) {
                return;
            }

            WCFMAffiliateAdmin.doAjax('reject_commission', { commission_id: id }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            });
        },

        cancelPayout: function(e) {
            e.preventDefault();

            var $btn = $(this);
            var id = $btn.data('id');

            if (!confirm(wcfm_affiliate_admin.i18n.confirm_cancel_payout)) {
                return;
            }

            WCFMAffiliateAdmin.doAjax('cancel_payout', { payout_id: id }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            });
        },

        processPayout: function(e) {
            e.preventDefault();

            var $form = $(this);
            var data = $form.serializeArray();
            var formData = {};

            $.each(data, function(i, field) {
                formData[field.name] = field.value;
            });

            WCFMAffiliateAdmin.doAjax('process_payout', formData, function(response) {
                if (response.success) {
                    window.location.href = wcfm_affiliate_admin.payouts_url;
                } else {
                    alert(response.data.message);
                }
            });
        },

        exportData: function(e) {
            e.preventDefault();

            var $btn = $(this);
            var type = $btn.data('type') || 'all';

            $btn.prop('disabled', true).text(wcfm_affiliate_admin.i18n.exporting);

            WCFMAffiliateAdmin.doAjax('export_data', { export_type: type }, function(response) {
                if (response.success) {
                    // Create download link
                    var link = document.createElement('a');
                    link.href = response.data.url;
                    link.download = response.data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    alert(response.data.message);
                }

                $btn.prop('disabled', false).text(wcfm_affiliate_admin.i18n.export);
            });
        },

        switchSettingsTab: function(e) {
            e.preventDefault();

            var $tab = $(this);
            var target = $tab.attr('href');

            // Update URL
            if (history.pushState) {
                history.pushState(null, null, target);
            }

            // Update tabs
            $('.wcfm-affiliate-settings-tabs .nav-tab').removeClass('nav-tab-active');
            $tab.addClass('nav-tab-active');

            // Update sections
            $('.wcfm-affiliate-settings-section').hide();
            $(target.substring(target.indexOf('#'))).show();
        },

        toggleMLMSettings: function() {
            var $mlmSettings = $('.wcfm-affiliate-mlm-settings');

            if ($(this).is(':checked')) {
                $mlmSettings.slideDown();
            } else {
                $mlmSettings.slideUp();
            }
        },

        initColorPickers: function() {
            if ($.fn.wpColorPicker) {
                $('.wcfm-affiliate-color-picker').wpColorPicker();
            }
        },

        initTierTable: function() {
            // Make tiers sortable if jQuery UI is available
            if ($.fn.sortable) {
                $('#wcfm-affiliate-tiers-table tbody').sortable({
                    handle: '.wcfm-affiliate-tier-handle',
                    update: function() {
                        WCFMAffiliateAdmin.updateTierOrder();
                    }
                });
            }
        },

        addTier: function(e) {
            e.preventDefault();

            var $table = $('#wcfm-affiliate-tiers-table tbody');
            var index = $table.find('tr').length;

            var row = '<tr>' +
                '<td><span class="wcfm-affiliate-tier-handle dashicons dashicons-menu"></span></td>' +
                '<td><input type="text" name="wcfm_affiliate_tiers[' + index + '][name]" placeholder="Nome tier"></td>' +
                '<td><input type="number" name="wcfm_affiliate_tiers[' + index + '][min_referrals]" min="0" value="0"></td>' +
                '<td><input type="number" name="wcfm_affiliate_tiers[' + index + '][rate]" min="0" step="0.01" value="10"></td>' +
                '<td class="wcfm-affiliate-tier-actions">' +
                '<button type="button" class="wcfm-affiliate-remove-tier"><span class="dashicons dashicons-trash"></span></button>' +
                '</td>' +
                '</tr>';

            $table.append(row);
        },

        removeTier: function(e) {
            e.preventDefault();

            var $row = $(this).closest('tr');

            if ($('#wcfm-affiliate-tiers-table tbody tr').length > 1) {
                $row.remove();
                WCFMAffiliateAdmin.updateTierOrder();
            } else {
                alert(wcfm_affiliate_admin.i18n.min_one_tier);
            }
        },

        updateTierOrder: function() {
            $('#wcfm-affiliate-tiers-table tbody tr').each(function(index) {
                $(this).find('input').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[\d+\]/, '[' + index + ']');
                        $(this).attr('name', name);
                    }
                });
            });
        },

        selectAll: function() {
            var checked = $(this).is(':checked');
            $('tbody input[type="checkbox"]').prop('checked', checked);
        },

        handleBulkAction: function(e) {
            var action = $('#bulk-action-selector-top').val();
            var checked = $('tbody input[type="checkbox"]:checked');

            if (!action) {
                e.preventDefault();
                alert(wcfm_affiliate_admin.i18n.select_action);
                return;
            }

            if (checked.length === 0) {
                e.preventDefault();
                alert(wcfm_affiliate_admin.i18n.select_items);
                return;
            }

            if (!confirm(wcfm_affiliate_admin.i18n.confirm_bulk_action)) {
                e.preventDefault();
                return;
            }
        },

        doAjax: function(action, data, callback) {
            data.action = 'wcfm_affiliate_admin_' + action;
            data.nonce = wcfm_affiliate_admin.nonce;

            $.ajax({
                url: wcfm_affiliate_admin.ajax_url,
                type: 'POST',
                data: data,
                success: callback,
                error: function() {
                    alert(wcfm_affiliate_admin.i18n.error);
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        WCFMAffiliateAdmin.init();
    });

})(jQuery);
