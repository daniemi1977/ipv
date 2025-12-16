<?php
/**
 * Analytics Dashboard
 *
 * Modern dashboard con statistiche MRR, ARR, grafici e metriche SaaS
 *
 * @version 1.5.0
 * @since 1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Analytics_Dashboard {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ], 20 );
        add_action( 'wp_ajax_ipv_get_dashboard_stats', [ $this, 'ajax_get_dashboard_stats' ] );
    }

    /**
     * Add Analytics menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'ipv-vendor-dashboard',
            __( 'Analytics', 'ipv-vendor' ),
            __( '📊 Analytics', 'ipv-vendor' ),
            'manage_options',
            'ipv-vendor-analytics',
            [ $this, 'render_analytics_page' ]
        );
    }

    /**
     * Render modern analytics dashboard
     */
    public function render_analytics_page() {
        global $wpdb;

        // Get current month stats
        $stats = $this->get_current_stats();

        ?>
        <div class="ipv-modern-page bg-gray-50 min-h-screen -ml-5 -mt-2 p-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">📊 Dashboard Analytics</h1>
                <p class="text-gray-600">Panoramica completa delle metriche SaaS e performance</p>
            </div>

            <!-- Stats Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8" id="ipv-dashboard-analytics">
                <!-- MRR Card -->
                <div class="ipv-stat-card">
                    <div class="ipv-stat-icon primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ipv-stat-label">MRR (Monthly Recurring Revenue)</div>
                    <div class="ipv-stat-value" id="ipv-stat-mrr">€<?php echo number_format( $stats['mrr'], 2, ',', '.' ); ?></div>
                    <div class="ipv-stat-change up">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        +<?php echo $stats['mrr_growth']; ?>% vs mese scorso
                    </div>
                </div>

                <!-- ARR Card -->
                <div class="ipv-stat-card">
                    <div class="ipv-stat-icon success">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="ipv-stat-label">ARR (Annual Recurring Revenue)</div>
                    <div class="ipv-stat-value" id="ipv-stat-arr">€<?php echo number_format( $stats['arr'], 2, ',', '.' ); ?></div>
                    <div class="text-sm text-gray-600 mt-2">MRR × 12 mesi</div>
                </div>

                <!-- Active Licenses Card -->
                <div class="ipv-stat-card">
                    <div class="ipv-stat-icon warning">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                    </div>
                    <div class="ipv-stat-label">Licenze Attive</div>
                    <div class="ipv-stat-value" id="ipv-stat-licenses"><?php echo number_format( $stats['active_licenses'], 0, ',', '.' ); ?></div>
                    <div class="text-sm text-gray-600 mt-2">
                        <?php echo $stats['trial_licenses']; ?> Trial | <?php echo $stats['paid_licenses']; ?> Paid
                    </div>
                </div>

                <!-- Total Credits Card -->
                <div class="ipv-stat-card">
                    <div class="ipv-stat-icon danger">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="ipv-stat-label">Crediti Totali Attivi</div>
                    <div class="ipv-stat-value" id="ipv-stat-credits"><?php echo number_format( $stats['total_credits'], 0, ',', '.' ); ?></div>
                    <div class="text-sm text-gray-600 mt-2">
                        <?php echo number_format( $stats['credits_used_month'], 0, ',', '.' ); ?> usati questo mese
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- MRR Trend Chart -->
                <div class="ipv-card">
                    <div class="ipv-card-header">
                        <h3 class="ipv-card-title">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                            </svg>
                            MRR Trend (12 Mesi)
                        </h3>
                        <span class="ipv-badge ipv-badge-success">+<?php echo $stats['mrr_growth']; ?>%</span>
                    </div>
                    <div class="ipv-chart-container">
                        <canvas id="ipv-mrr-chart"></canvas>
                    </div>
                </div>

                <!-- Plans Distribution Chart -->
                <div class="ipv-card">
                    <div class="ipv-card-header">
                        <h3 class="ipv-card-title">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                            </svg>
                            Distribuzione Piani
                        </h3>
                        <span class="text-sm text-gray-600"><?php echo $stats['active_licenses']; ?> totali</span>
                    </div>
                    <div class="ipv-chart-container">
                        <canvas id="ipv-plans-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Credits Usage Chart (Full Width) -->
            <div class="ipv-card mb-8">
                <div class="ipv-card-header">
                    <h3 class="ipv-card-title">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Utilizzo Crediti (Ultimi 7 Giorni)
                    </h3>
                    <div class="flex gap-4">
                        <span class="ipv-badge ipv-badge-primary">Usati: <?php echo number_format( $stats['credits_used_week'], 0, ',', '.' ); ?></span>
                        <span class="ipv-badge ipv-badge-success">Acquistati: <?php echo number_format( $stats['credits_purchased_week'], 0, ',', '.' ); ?></span>
                    </div>
                </div>
                <div class="ipv-chart-container">
                    <canvas id="ipv-credits-chart"></canvas>
                </div>
            </div>

            <!-- Additional Metrics Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Churn Rate -->
                <div class="ipv-card">
                    <h4 class="text-sm font-medium text-gray-600 mb-2">Churn Rate</h4>
                    <div class="text-2xl font-bold text-gray-900"><?php echo number_format( $stats['churn_rate'], 1 ); ?>%</div>
                    <p class="text-sm text-gray-600 mt-1">Cancellazioni questo mese</p>
                </div>

                <!-- Customer LTV -->
                <div class="ipv-card">
                    <h4 class="text-sm font-medium text-gray-600 mb-2">Customer LTV (Lifetime Value)</h4>
                    <div class="text-2xl font-bold text-gray-900">€<?php echo number_format( $stats['customer_ltv'], 2, ',', '.' ); ?></div>
                    <p class="text-sm text-gray-600 mt-1">Valore medio cliente</p>
                </div>

                <!-- ARPU -->
                <div class="ipv-card">
                    <h4 class="text-sm font-medium text-gray-600 mb-2">ARPU (Average Revenue Per User)</h4>
                    <div class="text-2xl font-bold text-gray-900">€<?php echo number_format( $stats['arpu'], 2, ',', '.' ); ?></div>
                    <p class="text-sm text-gray-600 mt-1">Revenue medio per utente/mese</p>
                </div>
            </div>
        </div>

        <script>
        // Initialize charts on page load
        jQuery(document).ready(function($) {
            if (typeof ipvInitCharts === 'function') {
                ipvInitCharts();
            }
        });
        </script>
        <?php
    }

    /**
     * Get current statistics
     */
    private function get_current_stats() {
        global $wpdb;

        $stats = [];

        // MRR (Monthly Recurring Revenue)
        $mrr_query = $wpdb->get_var( "
            SELECT SUM(pm.meta_value)
            FROM {$wpdb->prefix}ipv_licenses l
            INNER JOIN {$wpdb->prefix}ipv_license_meta pm ON l.id = pm.license_id
            WHERE l.status = 'active'
            AND pm.meta_key = '_monthly_price'
        " );
        $stats['mrr'] = floatval( $mrr_query );

        // ARR (Annual Recurring Revenue)
        $stats['arr'] = $stats['mrr'] * 12;

        // MRR Growth (vs last month)
        $stats['mrr_growth'] = 15.2; // TODO: Calculate from historical data

        // Active Licenses
        $stats['active_licenses'] = $wpdb->get_var( "
            SELECT COUNT(*)
            FROM {$wpdb->prefix}ipv_licenses
            WHERE status = 'active'
        " );

        // Trial vs Paid
        $stats['trial_licenses'] = $wpdb->get_var( "
            SELECT COUNT(*)
            FROM {$wpdb->prefix}ipv_licenses
            WHERE status = 'active'
            AND variant_slug = 'trial'
        " );
        $stats['paid_licenses'] = $stats['active_licenses'] - $stats['trial_licenses'];

        // Total Credits
        $stats['total_credits'] = $wpdb->get_var( "
            SELECT SUM(credits_balance)
            FROM {$wpdb->prefix}ipv_licenses
            WHERE status = 'active'
        " ) ?: 0;

        // Credits used this month
        $stats['credits_used_month'] = abs( $wpdb->get_var( "
            SELECT SUM(amount)
            FROM {$wpdb->prefix}ipv_credits_ledger
            WHERE transaction_type = 'usage'
            AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
        " ) ?: 0 );

        // Credits used this week
        $stats['credits_used_week'] = abs( $wpdb->get_var( "
            SELECT SUM(amount)
            FROM {$wpdb->prefix}ipv_credits_ledger
            WHERE transaction_type = 'usage'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        " ) ?: 0 );

        // Credits purchased this week
        $stats['credits_purchased_week'] = $wpdb->get_var( "
            SELECT SUM(amount)
            FROM {$wpdb->prefix}ipv_credits_ledger
            WHERE transaction_type IN ('purchase', 'renewal')
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        " ) ?: 0;

        // Churn Rate (simplified)
        $stats['churn_rate'] = 2.5; // TODO: Calculate from cancellations

        // Customer LTV (Lifetime Value)
        $stats['customer_ltv'] = $stats['active_licenses'] > 0 ? ($stats['arr'] / $stats['active_licenses']) : 0;

        // ARPU (Average Revenue Per User)
        $stats['arpu'] = $stats['active_licenses'] > 0 ? ($stats['mrr'] / $stats['active_licenses']) : 0;

        return $stats;
    }

    /**
     * AJAX: Get dashboard stats (for live updates)
     */
    public function ajax_get_dashboard_stats() {
        check_ajax_referer( 'ipv_modern_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Permesso negato' ] );
        }

        $stats = $this->get_current_stats();

        wp_send_json_success( [
            'stats' => $stats,
            'timestamp' => current_time( 'mysql' )
        ] );
    }
}

// Initialize
IPV_Vendor_Analytics_Dashboard::instance();
