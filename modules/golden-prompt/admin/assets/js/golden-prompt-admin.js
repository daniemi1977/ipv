/**
 * IPV Golden Prompt Admin JavaScript
 * @package IPV_Pro_Vendor
 * @since 1.6.0
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        IPVGoldenPrompt.init();
    });

    // Main object
    var IPVGoldenPrompt = {

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initTabs();
        },

        /**
         * Bind all events
         */
        bindEvents: function() {
            // Tabs
            $(document).on('click', '.tab-btn', this.handleTabClick);
            
            // Auto form submit
            $(document).on('submit', '#ipv-gp-auto-form', this.handleAutoFormSubmit);
            
            // Manual form submit
            $(document).on('submit', '#ipv-gp-manual-form', this.handleManualFormSubmit);
            
            // Push to client
            $(document).on('click', '.ipv-push-to-client-btn, .ipv-push-btn', this.handlePushToClient);
            
            // ✅ Toggle Switch (checkbox)
            $(document).on('change', '.ipv-gp-toggle-input', this.handleToggleSwitch);
            
            // Universal template form
            $(document).on('submit', '#ipv-universal-template-form', this.handleTemplateSubmit);
            
            // Reset template
            $(document).on('click', '#reset-template-btn', this.handleResetTemplate);
            
            // Preview button
            $(document).on('click', '#preview-auto-btn', this.handlePreview);
        },

        /**
         * Initialize tabs
         */
        initTabs: function() {
            // Show first tab by default
            if ($('.tab-content.active').length === 0) {
                $('.tab-content').first().addClass('active');
                $('.tab-btn').first().addClass('active');
            }
        },

        /**
         * Handle tab click
         */
        handleTabClick: function(e) {
            e.preventDefault();
            var tab = $(this).data('tab');
            
            // Update buttons
            $('.tab-btn').removeClass('active');
            $(this).addClass('active');
            
            // Update content
            $('.tab-content').removeClass('active');
            $('#tab-' + tab).addClass('active');
        },

        /**
         * Handle auto form submit
         */
        handleAutoFormSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var originalText = $btn.text();
            
            $btn.prop('disabled', true).html('<span class="loading-spinner"></span> Generando...');

            $.ajax({
                url: ipvGoldenPrompt.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ipv_save_golden_prompt',
                    nonce: ipvGoldenPrompt.nonce,
                    mode: 'auto',
                    data: $form.serialize()
                },
                success: function(response) {
                    $btn.prop('disabled', false).text(originalText);
                    
                    if (response.success) {
                        IPVGoldenPrompt.showNotice('success', '✅ Golden Prompt generato e salvato con successo!');
                        
                        // Update preview and manual textarea
                        $('#prompt-preview').text(response.data.prompt);
                        $('#custom_prompt').val(response.data.prompt);
                        
                        // Switch to preview tab
                        $('.tab-btn[data-tab="preview"]').click();
                    } else {
                        IPVGoldenPrompt.showNotice('error', '❌ Errore: ' + response.data);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(originalText);
                    IPVGoldenPrompt.showNotice('error', '❌ Errore di connessione');
                }
            });
        },

        /**
         * Handle manual form submit
         */
        handleManualFormSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var originalText = $btn.text();
            
            $btn.prop('disabled', true).html('<span class="loading-spinner"></span> Salvando...');

            $.ajax({
                url: ipvGoldenPrompt.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ipv_save_golden_prompt',
                    nonce: ipvGoldenPrompt.nonce,
                    mode: 'manual',
                    license_id: $form.find('input[name="license_id"]').val(),
                    custom_prompt: $('#custom_prompt').val()
                },
                success: function(response) {
                    $btn.prop('disabled', false).text(originalText);
                    
                    if (response.success) {
                        IPVGoldenPrompt.showNotice('success', '✅ Golden Prompt salvato con successo!');
                        $('#prompt-preview').text($('#custom_prompt').val());
                    } else {
                        IPVGoldenPrompt.showNotice('error', '❌ Errore: ' + response.data);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(originalText);
                    IPVGoldenPrompt.showNotice('error', '❌ Errore di connessione');
                }
            });
        },

        /**
         * Handle push to client
         */
        handlePushToClient: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var licenseId = $btn.data('license-id');
            var siteUrl = $btn.data('site-url');
            var originalText = $btn.text();

            if (!siteUrl) {
                IPVGoldenPrompt.showNotice('error', '❌ Nessun sito attivato per questa licenza');
                return;
            }

            if (!confirm('Vuoi inviare il Golden Prompt a ' + siteUrl + '?')) {
                return;
            }

            $btn.prop('disabled', true).html('<span class="loading-spinner"></span> Invio...');

            $.ajax({
                url: ipvGoldenPrompt.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ipv_push_golden_prompt',
                    nonce: ipvGoldenPrompt.nonce,
                    license_id: licenseId,
                    site_url: siteUrl
                },
                success: function(response) {
                    $btn.prop('disabled', false).text(originalText);
                    
                    if (response.success) {
                        IPVGoldenPrompt.showNotice('success', '✅ Golden Prompt inviato con successo!');
                    } else {
                        IPVGoldenPrompt.showNotice('error', '❌ Errore: ' + response.data);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(originalText);
                    IPVGoldenPrompt.showNotice('error', '❌ Errore di connessione');
                }
            });
        },

        /**
         * Handle toggle activation
         */
        handleToggle: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var licenseId = $btn.data('license-id');
            var currentActive = $btn.data('active');

            $.ajax({
                url: ipvGoldenPrompt.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ipv_toggle_golden_prompt',
                    nonce: ipvGoldenPrompt.nonce,
                    license_id: licenseId,
                    active: currentActive ? 0 : 1
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        IPVGoldenPrompt.showNotice('error', '❌ Errore: ' + response.data);
                    }
                },
                error: function() {
                    IPVGoldenPrompt.showNotice('error', '❌ Errore di connessione');
                }
            });
        },

        /**
         * ✅ Handle Toggle Switch (checkbox)
         */
        handleToggleSwitch: function(e) {
            var $checkbox = $(this);
            var $slider = $checkbox.next('.ipv-gp-toggle-slider');
            var $thumb = $slider.find('.ipv-gp-toggle-thumb');
            var $label = $checkbox.closest('td').find('.ipv-gp-toggle-label');
            var licenseId = $checkbox.data('license-id');
            var enabled = $checkbox.is(':checked') ? 1 : 0;
            
            // Disable durante call
            $checkbox.prop('disabled', true);
            
            $.ajax({
                url: ipvGoldenPrompt.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ipv_toggle_golden_prompt',
                    nonce: ipvGoldenPrompt.nonce,
                    license_id: licenseId,
                    active: enabled
                },
                success: function(response) {
                    if (response.success) {
                        // Update UI
                        var color = enabled ? '#4caf50' : '#ccc';
                        var left = enabled ? '28px' : '3px';
                        var textColor = enabled ? '#4caf50' : '#999';
                        var text = enabled ? 'ON' : 'OFF';
                        
                        $slider.css('background-color', color);
                        $thumb.css('left', left);
                        $label.text(text).css('color', textColor);
                        
                        // Toast notification
                        var message = enabled ? '✅ Golden Prompt ENABLED' : '⚪ Golden Prompt DISABLED';
                        IPVGoldenPrompt.showToast(message);
                        
                        // Reload dopo 1 secondo per aggiornare bottone Push
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        // Revert checkbox
                        $checkbox.prop('checked', !enabled);
                        IPVGoldenPrompt.showNotice('error', '❌ Errore: ' + response.data);
                    }
                    $checkbox.prop('disabled', false);
                },
                error: function() {
                    $checkbox.prop('checked', !enabled);
                    $checkbox.prop('disabled', false);
                    IPVGoldenPrompt.showNotice('error', '❌ Errore di connessione');
                }
            });
        },

        /**
         * Handle template submit
         */
        handleTemplateSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var originalText = $btn.text();
            
            $btn.prop('disabled', true).html('<span class="loading-spinner"></span> Salvando...');

            $.ajax({
                url: ipvGoldenPrompt.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ipv_save_universal_template',
                    nonce: ipvGoldenPrompt.nonce,
                    template: $('#universal_template').val()
                },
                success: function(response) {
                    $btn.prop('disabled', false).text(originalText);
                    
                    if (response.success) {
                        IPVGoldenPrompt.showNotice('success', '✅ Template salvato!');
                    } else {
                        IPVGoldenPrompt.showNotice('error', '❌ Errore: ' + response.data);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(originalText);
                    IPVGoldenPrompt.showNotice('error', '❌ Errore di connessione');
                }
            });
        },

        /**
         * Handle reset template
         */
        handleResetTemplate: function(e) {
            e.preventDefault();
            
            if (confirm('Vuoi ripristinare il template di default? Le modifiche andranno perse.')) {
                location.reload();
            }
        },

        /**
         * Handle preview button
         */
        handlePreview: function(e) {
            e.preventDefault();
            
            // Generate preview based on current form values
            var $form = $('#ipv-gp-auto-form');
            var formData = {};
            
            $form.find('input, textarea').each(function() {
                var name = $(this).attr('name');
                if (name && name !== 'license_id') {
                    formData[name] = $(this).val();
                }
            });

            // Switch to preview tab
            $('.tab-btn[data-tab="preview"]').click();
            
            // Show message
            $('#prompt-preview').text('Salva prima il Golden Prompt per vedere l\'anteprima completa.\n\nDati inseriti:\n' + JSON.stringify(formData, null, 2));
        },

        /**
         * Show notice
         */
        showNotice: function(type, message) {
            // Remove existing notices
            $('.ipv-gp-notice').remove();
            
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible ipv-gp-notice"><p>' + message + '</p></div>');
            
            $('.ipv-golden-prompt-wrap h1').after($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * ✅ Show Toast Notification
         */
        showToast: function(message) {
            var $toast = $('<div class="ipv-gp-toast">' + message + '</div>');
            
            $toast.css({
                position: 'fixed',
                bottom: '20px',
                right: '20px',
                background: '#323232',
                color: 'white',
                padding: '12px 24px',
                borderRadius: '4px',
                zIndex: 9999,
                fontSize: '14px',
                boxShadow: '0 4px 6px rgba(0,0,0,0.3)',
                opacity: 0,
                transition: 'opacity 0.3s'
            });
            
            $('body').append($toast);
            
            // Fade in
            setTimeout(function() {
                $toast.css('opacity', 1);
            }, 100);
            
            // Fade out after 3 seconds
            setTimeout(function() {
                $toast.css('opacity', 0);
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, 3000);
        }
    };

    // Expose to global scope
    window.IPVGoldenPrompt = IPVGoldenPrompt;

})(jQuery);
