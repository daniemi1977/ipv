<?php
/**
 * Admin Dashboard Template
 *
 * @package JEWE_Affiliate_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap jewe-admin-dashboard">
    <h1><?php _e('Dashboard JEWE Affiliate Pro', 'jewe-affiliate-pro'); ?></h1>

    <div class="jewe-dashboard-header">
        <div class="jewe-period-selector">
            <select id="jewe-period-select">
                <option value="7days"><?php _e('Ultimi 7 giorni', 'jewe-affiliate-pro'); ?></option>
                <option value="30days" selected><?php _e('Ultimi 30 giorni', 'jewe-affiliate-pro'); ?></option>
                <option value="90days"><?php _e('Ultimi 90 giorni', 'jewe-affiliate-pro'); ?></option>
                <option value="year"><?php _e('Ultimo anno', 'jewe-affiliate-pro'); ?></option>
            </select>
        </div>
    </div>

    <!-- KPIs -->
    <div class="jewe-kpi-grid">
        <div class="jewe-kpi-card">
            <span class="jewe-kpi-icon dashicons dashicons-groups"></span>
            <div class="jewe-kpi-content">
                <span class="jewe-kpi-value"><?php echo number_format($overview['total_affiliates']); ?></span>
                <span class="jewe-kpi-label"><?php _e('Affiliati Totali', 'jewe-affiliate-pro'); ?></span>
                <span class="jewe-kpi-sub"><?php echo $overview['active_affiliates']; ?> <?php _e('attivi', 'jewe-affiliate-pro'); ?></span>
            </div>
        </div>

        <div class="jewe-kpi-card">
            <span class="jewe-kpi-icon dashicons dashicons-chart-line"></span>
            <div class="jewe-kpi-content">
                <span class="jewe-kpi-value"><?php echo number_format($overview['total_sales']); ?></span>
                <span class="jewe-kpi-label"><?php _e('Vendite', 'jewe-affiliate-pro'); ?></span>
            </div>
        </div>

        <div class="jewe-kpi-card">
            <span class="jewe-kpi-icon dashicons dashicons-money-alt"></span>
            <div class="jewe-kpi-content">
                <span class="jewe-kpi-value">€<?php echo number_format($overview['total_commissions'], 2); ?></span>
                <span class="jewe-kpi-label"><?php _e('Commissioni', 'jewe-affiliate-pro'); ?></span>
            </div>
        </div>

        <div class="jewe-kpi-card">
            <span class="jewe-kpi-icon dashicons dashicons-cart"></span>
            <div class="jewe-kpi-content">
                <span class="jewe-kpi-value">€<?php echo number_format($overview['total_revenue'], 2); ?></span>
                <span class="jewe-kpi-label"><?php _e('Revenue Generato', 'jewe-affiliate-pro'); ?></span>
            </div>
        </div>
    </div>

    <div class="jewe-dashboard-grid">
        <!-- Chart -->
        <div class="jewe-card jewe-chart-card">
            <h3><?php _e('Andamento', 'jewe-affiliate-pro'); ?></h3>
            <canvas id="jewe-chart" height="300"></canvas>
        </div>

        <!-- Pending Affiliates -->
        <div class="jewe-card">
            <h3><?php _e('Affiliati in Attesa', 'jewe-affiliate-pro'); ?></h3>
            <?php if (!empty($pending_affiliates)): ?>
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <?php foreach ($pending_affiliates as $affiliate):
                        $user = get_userdata($affiliate->user_id);
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo $user ? esc_html($user->display_name) : 'N/A'; ?></strong><br>
                            <small><?php echo $user ? esc_html($user->user_email) : ''; ?></small>
                        </td>
                        <td><?php echo esc_html($affiliate->created_at); ?></td>
                        <td>
                            <button class="button button-primary jewe-approve-btn" data-id="<?php echo $affiliate->id; ?>">
                                <?php _e('Approva', 'jewe-affiliate-pro'); ?>
                            </button>
                            <button class="button jewe-reject-btn" data-id="<?php echo $affiliate->id; ?>">
                                <?php _e('Rifiuta', 'jewe-affiliate-pro'); ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="jewe-no-data"><?php _e('Nessun affiliato in attesa', 'jewe-affiliate-pro'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Top Affiliates -->
        <div class="jewe-card">
            <h3><?php _e('Top Affiliati', 'jewe-affiliate-pro'); ?></h3>
            <?php if (!empty($top_affiliates)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php _e('Affiliato', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Vendite', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Guadagni', 'jewe-affiliate-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_affiliates as $i => $leader):
                        $user = get_userdata($leader->user_id);
                    ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo $user ? esc_html($user->display_name) : 'N/A'; ?></td>
                        <td><?php echo intval($leader->period_sales); ?></td>
                        <td>€<?php echo number_format($leader->period_earnings, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="jewe-no-data"><?php _e('Nessun dato disponibile', 'jewe-affiliate-pro'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Recent Commissions -->
        <div class="jewe-card">
            <h3><?php _e('Commissioni Recenti', 'jewe-affiliate-pro'); ?></h3>
            <?php if (!empty($recent_commissions)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Affiliato', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Ordine', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Importo', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Stato', 'jewe-affiliate-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_commissions as $commission):
                        $user = get_userdata($commission->user_id);
                    ?>
                    <tr>
                        <td><?php echo $user ? esc_html($user->display_name) : 'N/A'; ?></td>
                        <td>#<?php echo esc_html($commission->order_id); ?></td>
                        <td>€<?php echo number_format($commission->commission_amount, 2); ?></td>
                        <td>
                            <span class="jewe-status jewe-status-<?php echo esc_attr($commission->status); ?>">
                                <?php echo esc_html($commission->status); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="jewe-no-data"><?php _e('Nessuna commissione recente', 'jewe-affiliate-pro'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Load chart
    function loadChart() {
        var period = $('#jewe-period-select').val();

        $.post(jeweAffiliateAdmin.ajaxurl, {
            action: 'jewe_get_dashboard_data',
            nonce: jeweAffiliateAdmin.nonce,
            period: period
        }, function(response) {
            if (response.success) {
                renderChart(response.data.chart_data);
            }
        });
    }

    function renderChart(data) {
        var ctx = document.getElementById('jewe-chart').getContext('2d');

        if (window.jeweChart) {
            window.jeweChart.destroy();
        }

        window.jeweChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: '<?php _e("Commissioni", "jewe-affiliate-pro"); ?>',
                    data: data.commissions,
                    borderColor: '#4F46E5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    fill: true
                }, {
                    label: '<?php _e("Revenue", "jewe-affiliate-pro"); ?>',
                    data: data.revenue,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    loadChart();

    $('#jewe-period-select').on('change', loadChart);

    // Approve/Reject buttons
    $('.jewe-approve-btn').on('click', function() {
        var id = $(this).data('id');
        if (confirm(jeweAffiliateAdmin.strings.confirm_approve)) {
            $.post(jeweAffiliateAdmin.ajaxurl, {
                action: 'jewe_approve_affiliate',
                nonce: jeweAffiliateAdmin.nonce,
                affiliate_id: id
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            });
        }
    });

    $('.jewe-reject-btn').on('click', function() {
        var id = $(this).data('id');
        if (confirm(jeweAffiliateAdmin.strings.confirm_reject)) {
            $.post(jeweAffiliateAdmin.ajaxurl, {
                action: 'jewe_reject_affiliate',
                nonce: jeweAffiliateAdmin.nonce,
                affiliate_id: id
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            });
        }
    });
});
</script>
