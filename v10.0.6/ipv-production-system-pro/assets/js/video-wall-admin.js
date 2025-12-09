/**
 * Video Wall Admin Panel JS
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize color pickers
        $('.ipv-color-picker').wpColorPicker({
            change: function(event, ui) {
                updateColorPreview();
            }
        });

        // Update color preview on load
        updateColorPreview();

        // Copy shortcode buttons
        $('#ipv-copy-shortcode').on('click', function() {
            const text = $('#ipv-shortcode-output').text();
            copyToClipboard(text, $(this));
        });

        $('#ipv-copy-custom').on('click', function() {
            const text = $('#ipv-shortcode-custom').text();
            copyToClipboard(text, $(this));
        });

        // Reset to defaults
        $('#ipv-reset-defaults').on('click', function(e) {
            e.preventDefault();

            if (!confirm('Sei sicuro di voler ripristinare tutte le impostazioni predefinite?')) {
                return;
            }

            // Reset all fields to defaults
            $('select[name="ipv_wall_layout"]').val('2+3');
            $('input[name="ipv_wall_per_page"]').val('5');
            $('select[name="ipv_wall_columns"]').val('3');
            $('input[name="ipv_wall_show_filters"]').prop('checked', true);
            $('input[name="ipv_wall_show_search"]').prop('checked', true);
            $('input[name="ipv_wall_show_date"]').prop('checked', true);
            $('input[name="ipv_wall_show_category"]').prop('checked', true);
            $('input[name="ipv_wall_show_speaker"]').prop('checked', true);
            $('input[name="ipv_wall_show_views"]').prop('checked', true);
            $('input[name="ipv_wall_show_duration"]').prop('checked', true);
            $('input[name="ipv_wall_excerpt_length"]').val('0');

            // Reset colors
            $('input[name="ipv_wall_accent_color"]').wpColorPicker('color', '#FB0F5A');
            $('input[name="ipv_wall_card_bg"]').wpColorPicker('color', '#F5F5F5');
            $('input[name="ipv_wall_meta_bg"]').wpColorPicker('color', '#EAEAEA');
            $('input[name="ipv_wall_text_color"]').wpColorPicker('color', '#555');

            $('select[name="ipv_wall_orderby"]').val('date');
            $('select[name="ipv_wall_order"]').val('DESC');
            $('select[name="ipv_wall_hover_effect"]').val('lift');
            $('select[name="ipv_wall_load_animation"]').val('fade');

            updateColorPreview();

            alert('Impostazioni ripristinate! Clicca "Salva Impostazioni" per applicare le modifiche.');
        });

        // Update shortcode on settings change
        $('input[name="ipv_wall_per_page"], select[name="ipv_wall_columns"]').on('change', function() {
            updateShortcode();
        });

        function updateColorPreview() {
            const accentColor = $('input[name="ipv_wall_accent_color"]').val();
            const cardBg = $('input[name="ipv_wall_card_bg"]').val();
            const metaBg = $('input[name="ipv_wall_meta_bg"]').val();
            const textColor = $('input[name="ipv_wall_text_color"]').val();

            $('.ipv-preview-badge').css('background', accentColor);
            $('.ipv-preview-card').css('background', cardBg);
            $('.ipv-preview-meta').css({
                'background': metaBg,
                'color': textColor
            });
        }

        function updateShortcode() {
            const perPage = $('input[name="ipv_wall_per_page"]').val();
            const columns = $('select[name="ipv_wall_columns"]').val();

            const shortcode = `[ipv_video_wall per_page="${perPage}" columns="${columns}"]`;
            $('#ipv-shortcode-custom').text(shortcode);
        }

        function copyToClipboard(text, $button) {
            // Create temporary textarea
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();

            try {
                document.execCommand('copy');

                // Visual feedback
                const originalText = $button.text();
                $button.text('✓ Copiato!').addClass('ipv-copy-success');

                setTimeout(function() {
                    $button.text(originalText).removeClass('ipv-copy-success');
                }, 2000);
            } catch(err) {
                alert('Errore durante la copia: ' + err);
            }

            $temp.remove();
        }

        // Live preview tooltip
        $('.ipv-admin-section select, .ipv-admin-section input[type="checkbox"]').on('change', function() {
            const $section = $(this).closest('.ipv-admin-section');
            $section.addClass('ipv-section-changed');

            setTimeout(function() {
                $section.removeClass('ipv-section-changed');
            }, 500);
        });
    });

})(jQuery);
