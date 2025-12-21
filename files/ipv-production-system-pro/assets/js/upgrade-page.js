/**
 * IPV Client Upgrade Page JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        loadUpgradePlans();
    });

    function loadUpgradePlans() {
        $.ajax({
            url: ipv_upgrade.ajax_url,
            type: 'POST',
            data: {
                action: 'ipv_get_upgrade_plans',
                nonce: ipv_upgrade.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderPlans(response.data);
                } else {
                    renderError(response.data.message || ipv_upgrade.i18n.error);
                }
            },
            error: function() {
                renderError(ipv_upgrade.i18n.error);
            }
        });
    }

    function renderPlans(data) {
        var container = $('#ipv-plans-container');
        var html = '';

        // Separate upgrades and downgrades
        var upgrades = [];
        var downgrades = [];

        if (data.available_plans) {
            data.available_plans.forEach(function(plan) {
                if (plan.type === 'upgrade') {
                    upgrades.push(plan);
                } else {
                    downgrades.push(plan);
                }
            });
        }

        // Render upgrades
        if (upgrades.length > 0) {
            html += '<div class="ipv-upgrade-plans">';
            html += '<h2><span class="dashicons dashicons-arrow-up-alt"></span> ' + ipv_upgrade.i18n.upgrade + '</h2>';
            html += '<div class="ipv-plans-grid">';

            upgrades.forEach(function(plan) {
                html += renderPlanCard(plan, 'upgrade', data.vendor_url);
            });

            html += '</div></div>';
        }

        // Render downgrades
        if (downgrades.length > 0) {
            html += '<div class="ipv-downgrade-plans">';
            html += '<h2><span class="dashicons dashicons-arrow-down-alt"></span> ' + ipv_upgrade.i18n.downgrade + '</h2>';
            html += '<div class="ipv-downgrade-warning">';
            html += '<span class="dashicons dashicons-warning"></span>';
            html += 'Il downgrade ridurrà i tuoi crediti mensili. I crediti rimanenti verranno adeguati al nuovo piano.';
            html += '</div>';
            html += '<div class="ipv-plans-grid">';

            downgrades.forEach(function(plan) {
                html += renderPlanCard(plan, 'downgrade', data.vendor_url);
            });

            html += '</div></div>';
        }

        // No plans available
        if (upgrades.length === 0 && downgrades.length === 0) {
            html = '<div class="ipv-plans-error">';
            html += '<p>Nessun piano disponibile per upgrade o downgrade.</p>';
            html += '</div>';
        }

        container.removeClass('ipv-plans-loading').html(html);
    }

    function renderPlanCard(plan, type, vendorUrl) {
        var diffClass = plan.credits_diff > 0 ? 'positive' : 'negative';
        var diffSign = plan.credits_diff > 0 ? '+' : '';

        var checkoutUrl = plan.checkout_url || (vendorUrl + '/my-account/ipv-upgrade/');
        var buttonLabel = type === 'upgrade'
            ? ipv_upgrade.i18n.upgrade + ' ' + plan.name
            : ipv_upgrade.i18n.downgrade + ' ' + plan.name;

        var html = '<div class="ipv-plan-card ' + type + '">';

        html += '<div class="plan-name">' + plan.name + '</div>';

        html += '<div class="plan-price">';
        if (plan.price > 0) {
            html += '€' + parseFloat(plan.price).toFixed(2).replace('.', ',');
            html += '<small>/' + getPeriodLabel(plan.price_period) + '</small>';
        } else {
            html += 'Gratuito';
        }
        html += '</div>';

        html += '<div class="plan-credits">';
        html += '<strong>' + plan.credits + '</strong> crediti/' + getPeriodLabel(plan.credits_period);
        html += '<span class="credits-diff ' + diffClass + '">' + diffSign + plan.credits_diff + '</span>';
        html += '</div>';

        // Features
        html += '<ul class="plan-features">';
        html += '<li><span class="dashicons dashicons-yes"></span> ' + plan.activations + ' siti attivabili</li>';
        if (plan.features && plan.features.priority_support) {
            html += '<li><span class="dashicons dashicons-yes"></span> Supporto prioritario</li>';
        }
        if (plan.features && plan.features.api_access) {
            html += '<li><span class="dashicons dashicons-yes"></span> Accesso API</li>';
        }
        html += '</ul>';

        html += '<div class="plan-action">';
        html += '<a href="' + checkoutUrl + '" class="button button-primary" target="_blank">';
        html += buttonLabel;
        if (type === 'upgrade' && plan.price_diff > 0) {
            html += ' (+€' + parseFloat(plan.price_diff).toFixed(2).replace('.', ',') + ')';
        }
        html += '</a>';
        html += '</div>';

        html += '</div>';

        return html;
    }

    function getPeriodLabel(period) {
        var labels = {
            'day': 'giorno',
            'week': 'settimana',
            'month': 'mese',
            'year': 'anno',
            'once': 'una tantum'
        };
        return labels[period] || period;
    }

    function renderError(message) {
        var container = $('#ipv-plans-container');
        var html = '<div class="ipv-plans-error">';
        html += '<span class="dashicons dashicons-warning"></span>';
        html += '<p>' + message + '</p>';
        html += '<button type="button" class="button" onclick="location.reload();">Riprova</button>';
        html += '</div>';

        container.removeClass('ipv-plans-loading').html(html);
    }

})(jQuery);
