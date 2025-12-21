/**
 * IPV Customer Portal JavaScript
 * WooCommerce My Account Integration
 */

(function($) {
    'use strict';

    // Copy license key to clipboard
    $(document).on('click', '.ipv-copy-key', function(e) {
        e.preventDefault();
        var key = $(this).data('key');
        var button = $(this);

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(key).then(function() {
                button.find('.dashicons').removeClass('dashicons-clipboard').addClass('dashicons-yes');
                setTimeout(function() {
                    button.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-clipboard');
                }, 2000);
            });
        } else {
            // Fallback for older browsers
            var textarea = document.createElement('textarea');
            textarea.value = key;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);

            button.find('.dashicons').removeClass('dashicons-clipboard').addClass('dashicons-yes');
            setTimeout(function() {
                button.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-clipboard');
            }, 2000);
        }
    });

    // Claim license form
    $(document).on('submit', '#ipv-claim-license-form', function(e) {
        e.preventDefault();

        var form = $(this);
        var button = form.find('button[type="submit"]');
        var resultEl = form.siblings('.ipv-claim-result');
        var licenseKey = form.find('input[name="license_key"]').val();

        if (!licenseKey) {
            resultEl.text('Inserisci una license key valida').addClass('error').removeClass('success');
            return;
        }

        button.prop('disabled', true).text(ipv_portal.i18n.claiming);
        resultEl.text('').removeClass('error success');

        $.ajax({
            url: ipv_portal.ajax_url,
            type: 'POST',
            data: {
                action: 'ipv_claim_license',
                nonce: ipv_portal.nonce,
                license_key: licenseKey
            },
            success: function(response) {
                if (response.success) {
                    resultEl.text(response.data.message).addClass('success').removeClass('error');

                    // Redirect after success
                    if (response.data.redirect) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 1500);
                    }
                } else {
                    resultEl.text(response.data.message || ipv_portal.i18n.error).addClass('error').removeClass('success');
                    button.prop('disabled', false).text('Associa Licenza');
                }
            },
            error: function() {
                resultEl.text(ipv_portal.i18n.error).addClass('error').removeClass('success');
                button.prop('disabled', false).text('Associa Licenza');
            }
        });
    });

    // Animate progress bars on page load
    $(document).ready(function() {
        $('.ipv-credits-progress, .ipv-wallet-progress').each(function() {
            var $this = $(this);
            var width = $this.css('width');
            $this.css('width', '0').animate({ width: width }, 1000);
        });
    });

})(jQuery);
