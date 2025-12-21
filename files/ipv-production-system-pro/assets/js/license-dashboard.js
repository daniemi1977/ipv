/**
 * IPV License Dashboard JavaScript
 * Admin dashboard widget interactions
 */

(function($) {
    'use strict';

    // Sync license button
    $(document).on('click', '#ipv-sync-license', function(e) {
        e.preventDefault();

        var button = $(this);
        var widget = button.closest('.ipv-license-widget');

        if (button.hasClass('syncing')) {
            return;
        }

        button.addClass('syncing');

        $.ajax({
            url: ipv_license.ajax_url,
            type: 'POST',
            data: {
                action: 'ipv_sync_license',
                nonce: ipv_license.nonce
            },
            success: function(response) {
                button.removeClass('syncing');

                if (response.success) {
                    // Update credits display
                    var wallet = response.data.wallet || {};
                    var credits = wallet.credits_remaining || 0;
                    var total = wallet.credits_total || 0;
                    var percentage = wallet.percentage || 0;
                    var status = wallet.status || 'ok';

                    widget.find('.ipv-credits-value strong').text(credits);
                    widget.find('.ipv-credits-value').contents().last()[0].textContent = ' / ' + total;

                    widget.find('.ipv-credits-progress')
                        .removeClass('ipv-credits-ok ipv-credits-low ipv-credits-critical ipv-credits-depleted')
                        .addClass('ipv-credits-' + status)
                        .css('width', percentage + '%');

                    // Update reset info
                    var resetText = 'Reset: ' + (wallet.reset_date_formatted || '-') +
                                    ' (' + (wallet.days_until_reset || 0) + ' giorni)';
                    widget.find('.ipv-credits-reset').text(resetText);

                    // Update footer
                    widget.find('.ipv-widget-footer small').text('Ultimo sync: ' + (response.data.synced_at || '-'));

                    // Show success feedback
                    showSyncFeedback(button, 'success');
                } else {
                    showSyncFeedback(button, 'error');
                }
            },
            error: function() {
                button.removeClass('syncing');
                showSyncFeedback(button, 'error');
            }
        });
    });

    function showSyncFeedback(button, status) {
        var icon = button.find('.dashicons');
        var originalClass = 'dashicons-update';

        if (status === 'success') {
            icon.removeClass(originalClass).addClass('dashicons-yes');
            setTimeout(function() {
                icon.removeClass('dashicons-yes').addClass(originalClass);
            }, 2000);
        } else {
            icon.removeClass(originalClass).addClass('dashicons-no');
            setTimeout(function() {
                icon.removeClass('dashicons-no').addClass(originalClass);
            }, 2000);
        }
    }

    // Animate progress bars on load
    $(document).ready(function() {
        $('.ipv-credits-progress').each(function() {
            var $this = $(this);
            var width = $this.css('width');
            $this.css('width', '0').animate({ width: width }, 800);
        });
    });

    // Auto-refresh every 5 minutes (if widget is visible)
    setInterval(function() {
        var widget = $('#ipv_license_widget .ipv-license-widget');
        if (widget.length && widget.is(':visible')) {
            $('#ipv-sync-license').trigger('click');
        }
    }, 300000); // 5 minutes

})(jQuery);
