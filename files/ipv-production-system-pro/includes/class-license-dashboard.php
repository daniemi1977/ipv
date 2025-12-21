<?php
/**
 * IPV License Dashboard
 *
 * Gestisce la visualizzazione dello stato licenza e crediti nell'admin
 * Con sincronizzazione automatica con il server vendor
 *
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Prod_License_Dashboard {

    private static $instance = null;

    /**
     * Vendor API base URL
     */
    private $vendor_url = '';

    /**
     * Cache duration in seconds (5 minutes)
     */
    private const CACHE_DURATION = 300;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Get vendor URL from settings or license
        $this->vendor_url = get_option( 'ipv_vendor_url', 'https://aiedintorni.it' );

        // Add dashboard widget
        add_action( 'wp_dashboard_setup', [ $this, 'add_dashboard_widget' ] );

        // Add admin bar item
        add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_item' ], 100 );

        // Add settings page section
        add_action( 'ipv_settings_license_section', [ $this, 'render_license_section' ] );

        // AJAX endpoints
        add_action( 'wp_ajax_ipv_sync_license', [ $this, 'ajax_sync_license' ] );
        add_action( 'wp_ajax_ipv_refresh_credits', [ $this, 'ajax_refresh_credits' ] );

        // Admin scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );

        // Cron sync
        add_action( 'ipv_daily_license_sync', [ $this, 'sync_license_data' ] );

        // Schedule daily sync
        if ( ! wp_next_scheduled( 'ipv_daily_license_sync' ) ) {
            wp_schedule_event( time(), 'daily', 'ipv_daily_license_sync' );
        }

        // Admin notices for alerts
        add_action( 'admin_notices', [ $this, 'display_license_alerts' ] );
    }

    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'ipv_license_widget',
            '🎬 IPV Production System - Licenza & Crediti',
            [ $this, 'render_dashboard_widget' ]
        );
    }

    /**
     * Render dashboard widget
     */
    public function render_dashboard_widget() {
        $license_data = $this->get_license_data();

        if ( ! $license_data || ! isset( $license_data['license'] ) ) {
            $this->render_no_license_widget();
            return;
        }

        $license = $license_data['license'];
        $wallet = $license_data['wallet'] ?? [];
        $alerts = $license_data['alerts'] ?? [];

        $status_class = $license['status'] === 'active' ? 'active' : 'inactive';
        $credits_status = $wallet['status'] ?? 'ok';
        ?>
        <div class="ipv-license-widget">
            <!-- License Status -->
            <div class="ipv-widget-header ipv-status-<?php echo esc_attr( $status_class ); ?>">
                <div class="ipv-license-info">
                    <span class="ipv-license-variant"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $license['variant'] ?? 'Pro' ) ) ); ?></span>
                    <span class="ipv-license-status"><?php echo esc_html( ucfirst( $license['status'] ?? 'active' ) ); ?></span>
                </div>
                <button type="button" class="ipv-sync-btn" id="ipv-sync-license" title="<?php esc_attr_e( 'Sincronizza', 'ipv-production-system-pro' ); ?>">
                    <span class="dashicons dashicons-update"></span>
                </button>
            </div>

            <!-- Credits Section -->
            <div class="ipv-widget-credits">
                <div class="ipv-credits-header">
                    <span class="ipv-credits-label"><?php esc_html_e( 'Crediti Disponibili', 'ipv-production-system-pro' ); ?></span>
                    <span class="ipv-credits-value">
                        <strong><?php echo esc_html( $wallet['credits_remaining'] ?? 0 ); ?></strong>
                        / <?php echo esc_html( $wallet['credits_total'] ?? 0 ); ?>
                    </span>
                </div>

                <div class="ipv-credits-bar">
                    <div class="ipv-credits-progress ipv-credits-<?php echo esc_attr( $credits_status ); ?>"
                         style="width: <?php echo esc_attr( $wallet['percentage'] ?? 0 ); ?>%"></div>
                </div>

                <div class="ipv-credits-meta">
                    <span class="ipv-credits-reset">
                        <?php esc_html_e( 'Reset:', 'ipv-production-system-pro' ); ?>
                        <?php echo esc_html( $wallet['reset_date_formatted'] ?? '-' ); ?>
                        (<?php echo esc_html( $wallet['days_until_reset'] ?? 0 ); ?> <?php esc_html_e( 'giorni', 'ipv-production-system-pro' ); ?>)
                    </span>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ( ! empty( $alerts ) ) : ?>
                <div class="ipv-widget-alerts">
                    <?php foreach ( $alerts as $alert ) : ?>
                        <div class="ipv-alert ipv-alert-<?php echo esc_attr( $alert['type'] ); ?>">
                            <span class="ipv-alert-message"><?php echo esc_html( $alert['message'] ); ?></span>
                            <?php if ( ! empty( $alert['action_url'] ) ) : ?>
                                <a href="<?php echo esc_url( $alert['action_url'] ); ?>" class="ipv-alert-action" target="_blank">
                                    <?php echo esc_html( $alert['action_label'] ?? 'Dettagli' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="ipv-widget-actions">
                <a href="<?php echo esc_url( $license_data['portal_url'] ?? '#' ); ?>" class="button" target="_blank">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php esc_html_e( 'Il Mio Account', 'ipv-production-system-pro' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-import' ) ); ?>" class="button button-primary">
                    <span class="dashicons dashicons-upload"></span>
                    <?php esc_html_e( 'Importa Video', 'ipv-production-system-pro' ); ?>
                </a>
            </div>

            <div class="ipv-widget-footer">
                <small>
                    <?php esc_html_e( 'Ultimo sync:', 'ipv-production-system-pro' ); ?>
                    <?php echo esc_html( $license_data['synced_at'] ?? '-' ); ?>
                </small>
            </div>
        </div>
        <?php
    }

    /**
     * Render widget when no license is configured
     */
    private function render_no_license_widget() {
        ?>
        <div class="ipv-license-widget ipv-no-license">
            <div class="ipv-widget-header ipv-status-inactive">
                <span class="dashicons dashicons-warning"></span>
                <span><?php esc_html_e( 'Licenza non configurata', 'ipv-production-system-pro' ); ?></span>
            </div>

            <div class="ipv-widget-content">
                <p><?php esc_html_e( 'Per utilizzare tutte le funzionalità di IPV Production System, configura la tua license key.', 'ipv-production-system-pro' ); ?></p>
            </div>

            <div class="ipv-widget-actions">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-settings#license' ) ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Configura Licenza', 'ipv-production-system-pro' ); ?>
                </a>
                <a href="<?php echo esc_url( $this->vendor_url . '/ipv-pro/' ); ?>" class="button" target="_blank">
                    <?php esc_html_e( 'Acquista', 'ipv-production-system-pro' ); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Add admin bar item
     */
    public function add_admin_bar_item( $admin_bar ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $license_data = $this->get_license_data();
        $credits_remaining = $license_data['wallet']['credits_remaining'] ?? 0;
        $credits_status = $license_data['wallet']['status'] ?? 'ok';

        $status_color = '#28a745';
        if ( $credits_status === 'low' ) $status_color = '#ffc107';
        if ( $credits_status === 'critical' ) $status_color = '#fd7e14';
        if ( $credits_status === 'depleted' ) $status_color = '#dc3545';

        $admin_bar->add_node([
            'id' => 'ipv-credits',
            'title' => sprintf(
                '<span class="ab-icon dashicons dashicons-video-alt3" style="margin-top: 2px;"></span> <span style="color: %s; font-weight: bold;">%d</span> crediti',
                $status_color,
                $credits_remaining
            ),
            'href' => admin_url( 'admin.php?page=ipv-production' ),
            'meta' => [
                'title' => __( 'Crediti IPV disponibili', 'ipv-production-system-pro' )
            ]
        ]);

        // Sub-menu items
        $admin_bar->add_node([
            'id' => 'ipv-credits-import',
            'parent' => 'ipv-credits',
            'title' => __( 'Importa Video', 'ipv-production-system-pro' ),
            'href' => admin_url( 'admin.php?page=ipv-production-import' )
        ]);

        $admin_bar->add_node([
            'id' => 'ipv-credits-portal',
            'parent' => 'ipv-credits',
            'title' => __( 'Portafoglio Crediti', 'ipv-production-system-pro' ),
            'href' => $license_data['portal_url'] ?? $this->vendor_url . '/my-account/ipv-wallet/'
        ]);

        $admin_bar->add_node([
            'id' => 'ipv-credits-buy',
            'parent' => 'ipv-credits',
            'title' => __( 'Acquista Crediti', 'ipv-production-system-pro' ),
            'href' => $this->vendor_url . '/ipv-pro/#pricing',
            'meta' => [ 'target' => '_blank' ]
        ]);
    }

    /**
     * Get license data (with caching)
     */
    public function get_license_data( $force_refresh = false ) {
        $cache_key = 'ipv_license_data';

        if ( ! $force_refresh ) {
            $cached = get_transient( $cache_key );
            if ( $cached !== false ) {
                return $cached;
            }
        }

        // Get stored data
        $license_data = get_option( 'ipv_license_data', [] );

        // If no data or stale, sync from vendor
        if ( empty( $license_data ) || $force_refresh ) {
            $synced_data = $this->sync_license_data();
            if ( $synced_data ) {
                $license_data = $synced_data;
            }
        }

        // Cache for 5 minutes
        set_transient( $cache_key, $license_data, self::CACHE_DURATION );

        return $license_data;
    }

    /**
     * Sync license data from vendor
     */
    public function sync_license_data() {
        $license_key = get_option( 'ipv_license_key', '' );

        if ( empty( $license_key ) ) {
            return false;
        }

        $api_url = trailingslashit( $this->vendor_url ) . 'wp-json/ipv-vendor/v1/wallet/sync';

        $response = wp_remote_post( $api_url, [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $license_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'site_url' => home_url(),
                'client_version' => IPV_PROD_VERSION,
                'wp_version' => get_bloginfo( 'version' ),
                'php_version' => phpversion()
            ])
        ]);

        if ( is_wp_error( $response ) ) {
            error_log( 'IPV License Sync Error: ' . $response->get_error_message() );
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['success'] ) ) {
            error_log( 'IPV License Sync Failed: ' . print_r( $body, true ) );
            return false;
        }

        // Add portal URL and vendor URL
        $body['portal_url'] = trailingslashit( $this->vendor_url ) . 'my-account/ipv-wallet/';
        $body['vendor_url'] = $this->vendor_url;

        // Store license data
        update_option( 'ipv_license_data', $body );

        // Clear cache
        delete_transient( 'ipv_license_data' );

        return $body;
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( $hook !== 'index.php' && strpos( $hook, 'ipv-production' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'ipv-license-dashboard',
            IPV_PROD_PLUGIN_URL . 'assets/css/license-dashboard.css',
            [],
            IPV_PROD_VERSION
        );

        wp_enqueue_script(
            'ipv-license-dashboard',
            IPV_PROD_PLUGIN_URL . 'assets/js/license-dashboard.js',
            [ 'jquery' ],
            IPV_PROD_VERSION,
            true
        );

        wp_localize_script( 'ipv-license-dashboard', 'ipv_license', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'ipv_license_nonce' ),
            'i18n' => [
                'syncing' => __( 'Sincronizzazione...', 'ipv-production-system-pro' ),
                'synced' => __( 'Sincronizzato!', 'ipv-production-system-pro' ),
                'error' => __( 'Errore di sincronizzazione', 'ipv-production-system-pro' ),
            ]
        ]);
    }

    /**
     * AJAX: Sync license
     */
    public function ajax_sync_license() {
        check_ajax_referer( 'ipv_license_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $data = $this->get_license_data( true );

        if ( $data ) {
            wp_send_json_success( $data );
        } else {
            wp_send_json_error( [ 'message' => __( 'Impossibile sincronizzare la licenza', 'ipv-production-system-pro' ) ] );
        }
    }

    /**
     * AJAX: Refresh credits
     */
    public function ajax_refresh_credits() {
        check_ajax_referer( 'ipv_license_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $data = $this->get_license_data( true );

        if ( $data && isset( $data['wallet'] ) ) {
            wp_send_json_success( $data['wallet'] );
        } else {
            wp_send_json_error( [ 'message' => __( 'Impossibile aggiornare i crediti', 'ipv-production-system-pro' ) ] );
        }
    }

    /**
     * Display license alerts as admin notices
     */
    public function display_license_alerts() {
        // Only on IPV pages
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'ipv-production' ) === false ) {
            return;
        }

        $license_data = $this->get_license_data();
        $alerts = $license_data['alerts'] ?? [];

        foreach ( $alerts as $alert ) {
            $class = 'notice ';
            $class .= $alert['type'] === 'error' ? 'notice-error' : 'notice-warning';

            printf(
                '<div class="%s"><p><strong>IPV:</strong> %s %s</p></div>',
                esc_attr( $class ),
                esc_html( $alert['message'] ),
                ! empty( $alert['action_url'] ) ? sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    esc_url( $alert['action_url'] ),
                    esc_html( $alert['action_label'] ?? 'Dettagli' )
                ) : ''
            );
        }
    }

    /**
     * Render license section in settings
     */
    public function render_license_section() {
        $license_key = get_option( 'ipv_license_key', '' );
        $license_data = $this->get_license_data();
        ?>
        <div class="ipv-settings-section" id="license">
            <h3><?php esc_html_e( 'Licenza e Crediti', 'ipv-production-system-pro' ); ?></h3>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'License Key', 'ipv-production-system-pro' ); ?></th>
                    <td>
                        <input type="text"
                               name="ipv_license_key"
                               value="<?php echo esc_attr( $license_key ); ?>"
                               class="regular-text"
                               placeholder="IPV-XXXX-XXXX-XXXX-XXXX" />
                        <p class="description">
                            <?php esc_html_e( 'Inserisci la tua license key per sbloccare tutte le funzionalità.', 'ipv-production-system-pro' ); ?>
                            <a href="<?php echo esc_url( $this->vendor_url . '/ipv-pro/' ); ?>" target="_blank">
                                <?php esc_html_e( 'Acquista licenza', 'ipv-production-system-pro' ); ?>
                            </a>
                        </p>
                    </td>
                </tr>

                <?php if ( ! empty( $license_data['license'] ) ) : ?>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Stato Licenza', 'ipv-production-system-pro' ); ?></th>
                        <td>
                            <span class="ipv-license-badge ipv-status-<?php echo esc_attr( $license_data['license']['status'] ); ?>">
                                <?php echo esc_html( ucfirst( $license_data['license']['status'] ) ); ?>
                            </span>
                            <span style="margin-left: 10px;">
                                <?php echo esc_html( ucfirst( str_replace( '_', ' ', $license_data['license']['variant'] ?? '' ) ) ); ?>
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Crediti', 'ipv-production-system-pro' ); ?></th>
                        <td>
                            <strong><?php echo esc_html( $license_data['wallet']['credits_remaining'] ?? 0 ); ?></strong>
                            / <?php echo esc_html( $license_data['wallet']['credits_total'] ?? 0 ); ?>
                            <span style="margin-left: 10px; color: #666;">
                                (<?php esc_html_e( 'Reset:', 'ipv-production-system-pro' ); ?>
                                <?php echo esc_html( $license_data['wallet']['reset_date_formatted'] ?? '-' ); ?>)
                            </span>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
        <?php
    }

    /**
     * Check if license is valid
     */
    public function is_license_valid() {
        $license_data = $this->get_license_data();
        return ! empty( $license_data['license'] ) && $license_data['license']['status'] === 'active';
    }

    /**
     * Get remaining credits
     */
    public function get_remaining_credits() {
        $license_data = $this->get_license_data();
        return $license_data['wallet']['credits_remaining'] ?? 0;
    }

    /**
     * Check if user has credits
     */
    public function has_credits( $required = 1 ) {
        return $this->get_remaining_credits() >= $required;
    }
}

// Initialize
IPV_Prod_License_Dashboard::instance();
