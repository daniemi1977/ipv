<?php
/**
 * Admin Reports
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_Admin_Reports {

    public function render(): void {
        $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : '30days';
        $stats = wcfm_affiliate_pro()->reports->get_overview($period);
        ?>
        <div class="wrap wcfm-affiliate-admin">
            <h1><?php _e('Report Affiliati', 'wcfm-affiliate-pro'); ?></h1>

            <!-- Period Selector -->
            <div class="wcfm-affiliate-admin-toolbar">
                <select id="wcfm-affiliate-period-selector" onchange="location.href='?page=wcfm-affiliate-reports&period='+this.value">
                    <option value="7days" <?php selected($period, '7days'); ?>><?php _e('Ultimi 7 giorni', 'wcfm-affiliate-pro'); ?></option>
                    <option value="30days" <?php selected($period, '30days'); ?>><?php _e('Ultimi 30 giorni', 'wcfm-affiliate-pro'); ?></option>
                    <option value="90days" <?php selected($period, '90days'); ?>><?php _e('Ultimi 90 giorni', 'wcfm-affiliate-pro'); ?></option>
                    <option value="year" <?php selected($period, 'year'); ?>><?php _e('Quest\'anno', 'wcfm-affiliate-pro'); ?></option>
                    <option value="all" <?php selected($period, 'all'); ?>><?php _e('Tutto', 'wcfm-affiliate-pro'); ?></option>
                </select>
                <button class="button" id="wcfm-affiliate-export-btn">
                    <?php _e('Esporta CSV', 'wcfm-affiliate-pro'); ?>
                </button>
            </div>

            <!-- Overview Stats -->
            <div class="wcfm-affiliate-admin-stats">
                <div class="wcfm-affiliate-admin-stat">
                    <span class="wcfm-affiliate-admin-stat-value"><?php echo number_format($stats['referrals']['count']); ?></span>
                    <span class="wcfm-affiliate-admin-stat-label"><?php _e('Referral', 'wcfm-affiliate-pro'); ?></span>
                </div>
                <div class="wcfm-affiliate-admin-stat">
                    <span class="wcfm-affiliate-admin-stat-value"><?php echo wc_price($stats['referrals']['total']); ?></span>
                    <span class="wcfm-affiliate-admin-stat-label"><?php _e('Commissioni Totali', 'wcfm-affiliate-pro'); ?></span>
                </div>
                <div class="wcfm-affiliate-admin-stat">
                    <span class="wcfm-affiliate-admin-stat-value"><?php echo number_format($stats['visits']); ?></span>
                    <span class="wcfm-affiliate-admin-stat-label"><?php _e('Visite', 'wcfm-affiliate-pro'); ?></span>
                </div>
                <div class="wcfm-affiliate-admin-stat">
                    <span class="wcfm-affiliate-admin-stat-value"><?php echo number_format($stats['clicks']); ?></span>
                    <span class="wcfm-affiliate-admin-stat-label"><?php _e('Click', 'wcfm-affiliate-pro'); ?></span>
                </div>
                <div class="wcfm-affiliate-admin-stat">
                    <span class="wcfm-affiliate-admin-stat-value"><?php echo $stats['conversion_rate']; ?>%</span>
                    <span class="wcfm-affiliate-admin-stat-label"><?php _e('Tasso Conversione', 'wcfm-affiliate-pro'); ?></span>
                </div>
                <div class="wcfm-affiliate-admin-stat">
                    <span class="wcfm-affiliate-admin-stat-value"><?php echo wc_price($stats['payouts']['total']); ?></span>
                    <span class="wcfm-affiliate-admin-stat-label"><?php _e('Pagamenti Effettuati', 'wcfm-affiliate-pro'); ?></span>
                </div>
            </div>

            <!-- Commission Status Breakdown -->
            <div class="wcfm-affiliate-admin-row">
                <div class="wcfm-affiliate-admin-col">
                    <div class="wcfm-affiliate-admin-card">
                        <h3><?php _e('Stato Commissioni', 'wcfm-affiliate-pro'); ?></h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Stato', 'wcfm-affiliate-pro'); ?></th>
                                    <th><?php _e('Quantità', 'wcfm-affiliate-pro'); ?></th>
                                    <th><?php _e('Totale', 'wcfm-affiliate-pro'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <span class="wcfm-affiliate-status wcfm-affiliate-status-pending">
                                            <?php _e('In Attesa', 'wcfm-affiliate-pro'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($stats['commissions']['pending']['count']); ?></td>
                                    <td><?php echo wc_price($stats['commissions']['pending']['total']); ?></td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="wcfm-affiliate-status wcfm-affiliate-status-approved">
                                            <?php _e('Approvate', 'wcfm-affiliate-pro'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($stats['commissions']['approved']['count']); ?></td>
                                    <td><?php echo wc_price($stats['commissions']['approved']['total']); ?></td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="wcfm-affiliate-status wcfm-affiliate-status-paid">
                                            <?php _e('Pagate', 'wcfm-affiliate-pro'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($stats['commissions']['paid']['count']); ?></td>
                                    <td><?php echo wc_price($stats['commissions']['paid']['total']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="wcfm-affiliate-admin-col">
                    <div class="wcfm-affiliate-admin-card">
                        <h3><?php _e('Andamento Commissioni', 'wcfm-affiliate-pro'); ?></h3>
                        <canvas id="wcfm-affiliate-chart" height="250"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Affiliates and Products -->
            <div class="wcfm-affiliate-admin-row">
                <div class="wcfm-affiliate-admin-col">
                    <div class="wcfm-affiliate-admin-card">
                        <h3><?php _e('Top Affiliati', 'wcfm-affiliate-pro'); ?></h3>
                        <?php
                        $top_affiliates = wcfm_affiliate_pro()->reports->get_top_affiliates($period, 10);
                        if (!empty($top_affiliates)):
                            ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php _e('Affiliato', 'wcfm-affiliate-pro'); ?></th>
                                        <th><?php _e('Referral', 'wcfm-affiliate-pro'); ?></th>
                                        <th><?php _e('Guadagni', 'wcfm-affiliate-pro'); ?></th>
                                        <th><?php _e('Conv.', 'wcfm-affiliate-pro'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_affiliates as $aff): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-affiliates&action=view&id=' . $aff->id); ?>">
                                                    <?php echo esc_html($aff->display_name); ?>
                                                </a>
                                            </td>
                                            <td><?php echo number_format($aff->referrals_count); ?></td>
                                            <td><?php echo wc_price($aff->total_earnings); ?></td>
                                            <td><?php echo round($aff->conversion_rate ?? 0, 1); ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p><?php _e('Nessun dato disponibile', 'wcfm-affiliate-pro'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="wcfm-affiliate-admin-col">
                    <div class="wcfm-affiliate-admin-card">
                        <h3><?php _e('Top Prodotti', 'wcfm-affiliate-pro'); ?></h3>
                        <?php
                        $top_products = wcfm_affiliate_pro()->reports->get_top_products($period, 10);
                        if (!empty($top_products)):
                            ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php _e('Prodotto', 'wcfm-affiliate-pro'); ?></th>
                                        <th><?php _e('Referral', 'wcfm-affiliate-pro'); ?></th>
                                        <th><?php _e('Commissioni', 'wcfm-affiliate-pro'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_products as $prod): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo get_edit_post_link($prod->product_id); ?>">
                                                    <?php echo esc_html($prod->product_name); ?>
                                                </a>
                                            </td>
                                            <td><?php echo number_format($prod->referrals_count); ?></td>
                                            <td><?php echo wc_price($prod->total_commissions); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p><?php _e('Nessun dato disponibile', 'wcfm-affiliate-pro'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Load chart data
            var ctx = document.getElementById('wcfm-affiliate-chart');
            if (ctx) {
                $.post(ajaxurl, {
                    action: 'wcfm_affiliate_get_report',
                    report_type: 'chart',
                    metric: 'commissions',
                    period: '<?php echo esc_js($period); ?>',
                    nonce: wcfm_affiliate_admin.nonce
                }, function(response) {
                    if (response.success) {
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: response.data.labels,
                                datasets: [{
                                    label: '<?php _e('Commissioni', 'wcfm-affiliate-pro'); ?>',
                                    data: response.data.data,
                                    borderColor: '#00897b',
                                    backgroundColor: 'rgba(0, 137, 123, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: { display: false }
                                },
                                scales: {
                                    y: { beginAtZero: true }
                                }
                            }
                        });
                    }
                });
            }
        });
        </script>
        <?php
    }
}
