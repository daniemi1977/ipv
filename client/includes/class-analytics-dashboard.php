<?php
/**
 * Analytics Dashboard - CLIENT
 *
 * Dashboard statistiche utilizzo crediti, video processati, AI usage
 *
 * @version 10.4.0
 * @since 10.4.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Prod_Analytics_Dashboard {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ], 20 );
        add_action( 'wp_ajax_ipv_get_client_stats', [ $this, 'ajax_get_client_stats' ] );
    }

    /**
     * Add Analytics menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            __( 'Analytics', 'ipv-production-system-pro' ),
            __( '📊 Analytics', 'ipv-production-system-pro' ),
            'manage_options',
            'ipv-analytics',
            [ $this, 'render_analytics_page' ]
        );
    }

    /**
     * Render modern analytics dashboard
     */
    public function render_analytics_page() {
        global $wpdb;

        // Get current stats
        $stats = $this->get_current_stats();

        // Get license info
        $license_manager = IPV_Prod_License_Manager_Client::instance();
        $license_info = $license_manager->get_cached_license_info();

        ?>
        <div class="ipv-modern-page bg-gray-50 min-h-screen -ml-5 -mt-2 p-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">📊 Analytics Dashboard</h1>
                <p class="text-gray-600">Panoramica utilizzo crediti e statistiche produzione video</p>
            </div>

            <!-- Stats Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8" id="ipv-dashboard-analytics">
                <!-- Crediti Disponibili Card -->
                <div class="ipv-stat-card">
                    <div class="ipv-stat-icon <?php echo $stats['credits_percentage'] > 20 ? 'success' : 'danger'; ?>">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="ipv-stat-label">Crediti Disponibili</div>
                    <div class="ipv-stat-value" id="ipv-stat-credits"><?php echo number_format( $stats['credits_available'], 0, ',', '.' ); ?></div>
                    <div class="text-sm text-gray-600 mt-2">
                        <?php echo $stats['credits_percentage']; ?>% del totale assegnato
                    </div>
                </div>

                <!-- Video Processati Card -->
                <div class="ipv-stat-card">
                    <div class="ipv-stat-icon primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ipv-stat-label">Video Processati</div>
                    <div class="ipv-stat-value" id="ipv-stat-videos"><?php echo number_format( $stats['videos_total'], 0, ',', '.' ); ?></div>
                    <div class="text-sm text-gray-600 mt-2">
                        <?php echo $stats['videos_month']; ?> questo mese
                    </div>
                </div>

                <!-- Descrizioni AI Card -->
                <div class="ipv-stat-card">
                    <div class="ipv-stat-icon warning">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <div class="ipv-stat-label">Descrizioni AI Generate</div>
                    <div class="ipv-stat-value" id="ipv-stat-ai"><?php echo number_format( $stats['ai_descriptions'], 0, ',', '.' ); ?></div>
                    <div class="text-sm text-gray-600 mt-2">
                        <?php echo $stats['ai_month']; ?> questo mese
                    </div>
                </div>

                <!-- Template Type Card -->
                <div class="ipv-stat-card">
                    <div class="ipv-stat-icon <?php echo $stats['has_golden_prompt'] ? 'success' : 'gray'; ?>">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                    </div>
                    <div class="ipv-stat-label">Template Attivo</div>
                    <div class="ipv-stat-value text-2xl" id="ipv-stat-template">
                        <?php echo $stats['has_golden_prompt'] ? 'GOLDEN' : 'BASE'; ?>
                    </div>
                    <div class="text-sm text-gray-600 mt-2">
                        <?php echo $stats['has_golden_prompt'] ? 'Premium personalizzato' : 'Gratuito standard'; ?>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Credits Usage Trend Chart -->
                <div class="ipv-card">
                    <div class="ipv-card-header">
                        <h3 class="ipv-card-title">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                            Utilizzo Crediti (Ultimi 30 Giorni)
                        </h3>
                        <span class="ipv-badge ipv-badge-primary"><?php echo $stats['credits_used_month']; ?> usati</span>
                    </div>
                    <div class="ipv-chart-container">
                        <canvas id="ipv-credits-chart"></canvas>
                    </div>
                </div>

                <!-- Video Production Chart -->
                <div class="ipv-card">
                    <div class="ipv-card-header">
                        <h3 class="ipv-card-title">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Video Processati (Ultimi 7 Giorni)
                        </h3>
                        <span class="text-sm text-gray-600"><?php echo $stats['videos_week']; ?> totali</span>
                    </div>
                    <div class="ipv-chart-container">
                        <canvas id="ipv-videos-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- License Info Card -->
            <?php if ( $license_info && isset( $license_info['data'] ) ): ?>
            <div class="ipv-card mb-8">
                <div class="ipv-card-header">
                    <h3 class="ipv-card-title">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                        Informazioni Licenza
                    </h3>
                    <span class="ipv-badge ipv-badge-<?php echo $license_info['data']['status'] === 'active' ? 'success' : 'warning'; ?>">
                        <?php echo strtoupper( $license_info['data']['status'] ?? 'unknown' ); ?>
                    </span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <h4 class="text-sm font-medium text-gray-600 mb-1">Piano</h4>
                        <p class="text-lg font-semibold text-gray-900">
                            <?php echo ucfirst( $license_info['data']['variant'] ?? 'N/A' ); ?>
                        </p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-600 mb-1">Crediti Assegnati</h4>
                        <p class="text-lg font-semibold text-gray-900">
                            <?php echo number_format( $license_info['data']['credits_balance'] ?? 0, 0, ',', '.' ); ?>
                        </p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-600 mb-1">Attivazioni</h4>
                        <p class="text-lg font-semibold text-gray-900">
                            <?php echo ( $license_info['data']['activation_count'] ?? 0 ); ?> / <?php echo ( $license_info['data']['activation_limit'] ?? 0 ); ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video' ); ?>" class="ipv-card hover:shadow-lg transition-shadow">
                    <div class="flex items-center gap-4">
                        <div class="ipv-stat-icon primary">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">Gestisci Video</h4>
                            <p class="text-sm text-gray-600">Visualizza e modifica tutti i tuoi video</p>
                        </div>
                    </div>
                </a>

                <a href="<?php echo admin_url( 'admin.php?page=ipv-settings' ); ?>" class="ipv-card hover:shadow-lg transition-shadow">
                    <div class="flex items-center gap-4">
                        <div class="ipv-stat-icon warning">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">Impostazioni</h4>
                            <p class="text-sm text-gray-600">Configura API e preferenze</p>
                        </div>
                    </div>
                </a>

                <a href="<?php echo admin_url( 'admin.php?page=ipv-plan-upgrade' ); ?>" class="ipv-card hover:shadow-lg transition-shadow">
                    <div class="flex items-center gap-4">
                        <div class="ipv-stat-icon success">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">Upgrade Piano</h4>
                            <p class="text-sm text-gray-600">Aumenta crediti e funzionalità</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <script>
        // Initialize charts on page load
        jQuery(document).ready(function($) {
            if (typeof ipvInitClientCharts === 'function') {
                ipvInitClientCharts();
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

        // Get license info
        $license_manager = IPV_Prod_License_Manager_Client::instance();
        $license_info = $license_manager->get_cached_license_info();

        // Credits available
        $stats['credits_available'] = isset( $license_info['data']['credits_balance'] ) ? intval( $license_info['data']['credits_balance'] ) : 0;
        $stats['credits_total'] = isset( $license_info['data']['credits_balance'] ) ? intval( $license_info['data']['credits_balance'] ) : 1;
        $stats['credits_percentage'] = $stats['credits_total'] > 0 ? round( ( $stats['credits_available'] / $stats['credits_total'] ) * 100 ) : 0;

        // Credits used this month (from options/transients if tracked)
        $stats['credits_used_month'] = get_option( 'ipv_credits_used_month', 0 );

        // Video counts
        $stats['videos_total'] = wp_count_posts( 'ipv_video' )->publish ?? 0;

        $stats['videos_month'] = $wpdb->get_var( "
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_type = 'ipv_video'
            AND post_status = 'publish'
            AND post_date >= DATE_FORMAT(NOW(), '%Y-%m-01')
        " ) ?: 0;

        $stats['videos_week'] = $wpdb->get_var( "
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_type = 'ipv_video'
            AND post_status = 'publish'
            AND post_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        " ) ?: 0;

        // AI descriptions count (meta_key: _ai_description or similar)
        $stats['ai_descriptions'] = $wpdb->get_var( "
            SELECT COUNT(DISTINCT post_id)
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_ai_generated'
            AND meta_value = '1'
        " ) ?: 0;

        $stats['ai_month'] = $wpdb->get_var( "
            SELECT COUNT(DISTINCT pm.post_id)
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_ai_generated'
            AND pm.meta_value = '1'
            AND p.post_date >= DATE_FORMAT(NOW(), '%Y-%m-01')
        " ) ?: 0;

        // Template type
        $stats['has_golden_prompt'] = isset( $license_info['data']['template_type'] ) && $license_info['data']['template_type'] === 'golden_premium';

        return $stats;
    }

    /**
     * AJAX: Get client stats (for live updates)
     */
    public function ajax_get_client_stats() {
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
IPV_Prod_Analytics_Dashboard::instance();
