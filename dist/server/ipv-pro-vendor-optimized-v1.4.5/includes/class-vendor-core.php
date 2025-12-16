<?php
/**
 * IPV Vendor Core Class - VERSIONE CORRETTA
 *
 * Main initialization and setup for the vendor system
 * CON FIX per schedule 'monthly' e miglioramenti
 * 
 * @version 1.1.2-fixed
 * @since 1.1.2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Core {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function includes() {
        require_once IPV_VENDOR_DIR . 'includes/class-license-manager.php';
        require_once IPV_VENDOR_DIR . 'includes/class-api-gateway.php';
        require_once IPV_VENDOR_DIR . 'includes/class-credits-manager.php';
        require_once IPV_VENDOR_DIR . 'includes/class-plans-manager.php';
        require_once IPV_VENDOR_DIR . 'includes/class-woocommerce-integration.php';
        require_once IPV_VENDOR_DIR . 'includes/class-remote-updates-server.php';
        require_once IPV_VENDOR_DIR . 'includes/class-webhook-handler.php';
        require_once IPV_VENDOR_DIR . 'includes/class-admin-dashboard.php';
        require_once IPV_VENDOR_DIR . 'includes/class-customer-portal.php';
    }

    private function init_hooks() {
        // ==========================================
        // FIX: Registra schedule 'monthly' PRIMA di usarla!
        // WordPress non ha 'monthly' di default
        // ==========================================
        add_filter( 'cron_schedules', [ $this, 'register_custom_schedules' ] );

        // Initialize components
        IPV_Vendor_License_Manager::instance();
        IPV_Vendor_API_Gateway::instance();
        IPV_Vendor_Credits_Manager::instance();
        IPV_Vendor_Plans_Manager::instance();
        IPV_Vendor_WooCommerce_Integration::instance();
        IPV_Vendor_Remote_Updates_Server::instance();
        IPV_Vendor_Webhook_Handler::instance();
        IPV_Vendor_Admin_Dashboard::instance();
        IPV_Vendor_Customer_Portal::instance();

        // REST API
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

        // Cron hooks
        add_action( 'ipv_vendor_reset_credits', [ $this, 'reset_monthly_credits' ] );
        add_action( 'ipv_vendor_clear_cache', [ 'IPV_Vendor_API_Gateway', 'clear_old_cache' ] );
        add_action( 'ipv_vendor_cleanup_logs', [ $this, 'cleanup_old_logs' ] );

        // Admin enqueue
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

        // Health check endpoint (non autenticato per monitoring)
        add_action( 'rest_api_init', [ $this, 'register_health_endpoint' ] );
    }

    /**
     * FIX: Registra schedules personalizzate
     * WordPress non ha 'monthly', 'weekly' etc di default
     */
    public function register_custom_schedules( $schedules ) {
        // Monthly - ogni 30 giorni
        $schedules['monthly'] = [
            'interval' => 30 * DAY_IN_SECONDS,
            'display' => __( 'Una volta al mese', 'ipv-pro-vendor' )
        ];

        // Weekly - ogni 7 giorni
        $schedules['weekly'] = [
            'interval' => 7 * DAY_IN_SECONDS,
            'display' => __( 'Una volta a settimana', 'ipv-pro-vendor' )
        ];

        // Bi-weekly - ogni 14 giorni
        $schedules['biweekly'] = [
            'interval' => 14 * DAY_IN_SECONDS,
            'display' => __( 'Ogni due settimane', 'ipv-pro-vendor' )
        ];

        return $schedules;
    }

    /**
     * Register health check endpoint
     */
    public function register_health_endpoint() {
        register_rest_route( 'ipv-vendor/v1', '/health', [
            'methods' => 'GET',
            'callback' => function() {
                $api_gateway = IPV_Vendor_API_Gateway::instance();
                $api_keys_status = $api_gateway->check_api_keys_status();
                
                return rest_ensure_response([
                    'status' => 'ok',
                    'service' => 'IPV Pro Vendor API',
                    'version' => IPV_VENDOR_VERSION,
                    'timestamp' => current_time( 'mysql' ),
                    'php_version' => PHP_VERSION,
                    'wp_version' => get_bloginfo( 'version' ),
                    'api_keys_configured' => $api_keys_status,
                    'endpoints' => [
                        'license' => rest_url( 'ipv-vendor/v1/license/*' ),
                        'gateway' => rest_url( 'ipv-vendor/v1/{transcript,description,credits}' ),
                        'updates' => rest_url( 'ipv-vendor/v1/plugin-info' )
                    ]
                ]);
            },
            'permission_callback' => '__return_true'
        ]);
    }

    public function register_rest_routes() {
        // License endpoints
        require_once IPV_VENDOR_DIR . 'api/endpoints/class-license-endpoints.php';
        $license_endpoints = new IPV_Vendor_License_Endpoints();
        $license_endpoints->register_routes();

        // Gateway endpoints (transcript, description, etc)
        require_once IPV_VENDOR_DIR . 'api/endpoints/class-gateway-endpoints.php';
        $gateway_endpoints = new IPV_Vendor_Gateway_Endpoints();
        $gateway_endpoints->register_routes();

        // YouTube endpoints (v1.3.1)
        require_once IPV_VENDOR_DIR . 'api/endpoints/class-youtube-endpoints.php';
        $youtube_endpoints = new IPV_Vendor_YouTube_Endpoints();
        $youtube_endpoints->register_routes();

        // Updates endpoints
        require_once IPV_VENDOR_DIR . 'api/endpoints/class-updates-endpoints.php';
        $updates_endpoints = new IPV_Vendor_Updates_Endpoints();
        $updates_endpoints->register_routes();
    }

    public function reset_monthly_credits() {
        $count = IPV_Vendor_Credits_Manager::reset_all_credits();
        error_log( sprintf( 'IPV Vendor Cron: Reset mensile completato - %d licenze', $count ) );
    }

    /**
     * Cleanup old API logs (keep 90 days)
     */
    public function cleanup_old_logs() {
        global $wpdb;
        
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->prefix}ipv_api_logs
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
        );

        if ( $deleted > 0 ) {
            error_log( sprintf( 'IPV Vendor Cron: Rimossi %d log vecchi', $deleted ) );
        }
    }

    public function admin_enqueue_scripts( $hook ) {
        // Only on IPV Vendor pages
        if ( strpos( $hook, 'ipv-vendor' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'ipv-vendor-admin',
            IPV_VENDOR_URL . 'admin/assets/css/admin.css',
            [],
            IPV_VENDOR_VERSION
        );

        wp_enqueue_script(
            'ipv-vendor-admin',
            IPV_VENDOR_URL . 'admin/assets/js/admin.js',
            [ 'jquery' ],
            IPV_VENDOR_VERSION,
            true
        );

        wp_localize_script( 'ipv-vendor-admin', 'ipvVendor', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'ipv_vendor_admin' ),
            'restUrl' => rest_url( 'ipv-vendor/v1/' )
        ]);
    }

    // ==========================================
    // ACTIVATION / DEACTIVATION
    // ==========================================

    public static function activate() {
        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        // Create tables
        self::create_tables();

        // ==========================================
        // FIX: Schedula cron DOPO che la schedule è registrata
        // Usa timestamp primo giorno prossimo mese alle 02:00
        // ==========================================
        
        // Cancella eventuali schedule precedenti
        wp_clear_scheduled_hook( 'ipv_vendor_reset_credits' );
        wp_clear_scheduled_hook( 'ipv_vendor_clear_cache' );
        wp_clear_scheduled_hook( 'ipv_vendor_cleanup_logs' );

        // Calcola timestamp primo giorno del prossimo mese alle 02:00 UTC
        $first_day_next_month = strtotime( 'first day of next month 02:00:00' );

        // Schedule reset crediti mensile
        if ( ! wp_next_scheduled( 'ipv_vendor_reset_credits' ) ) {
            wp_schedule_event(
                $first_day_next_month,
                'monthly',
                'ipv_vendor_reset_credits'
            );
        }

        // Schedule pulizia cache settimanale
        if ( ! wp_next_scheduled( 'ipv_vendor_clear_cache' ) ) {
            wp_schedule_event(
                time() + DAY_IN_SECONDS,
                'weekly',
                'ipv_vendor_clear_cache'
            );
        }

        // Schedule pulizia log settimanale
        if ( ! wp_next_scheduled( 'ipv_vendor_cleanup_logs' ) ) {
            wp_schedule_event(
                time() + DAY_IN_SECONDS + 3600, // 1 ora dopo clear_cache
                'weekly',
                'ipv_vendor_cleanup_logs'
            );
        }

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set activation flag
        update_option( 'ipv_vendor_activated', time() );
        update_option( 'ipv_vendor_version', IPV_VENDOR_VERSION );

        // Genera Server API Key se non esiste (per autenticazione sicura)
        if ( empty( get_option( 'ipv_vendor_server_api_key', '' ) ) ) {
            update_option( 'ipv_vendor_server_api_key', wp_generate_password( 64, false ) );
        }

        error_log( 'IPV Vendor: Plugin attivato con successo v' . IPV_VENDOR_VERSION );
    }

    public static function deactivate() {
        // Clear scheduled cron
        wp_clear_scheduled_hook( 'ipv_vendor_reset_credits' );
        wp_clear_scheduled_hook( 'ipv_vendor_clear_cache' );
        wp_clear_scheduled_hook( 'ipv_vendor_cleanup_logs' );

        // Flush rewrite rules
        flush_rewrite_rules();

        error_log( 'IPV Vendor: Plugin disattivato' );
    }

    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // ==========================================
        // TABLE: Licenses
        // ==========================================
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_licenses (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            license_key VARCHAR(100) UNIQUE NOT NULL,
            order_id BIGINT(20) UNSIGNED NOT NULL,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            email VARCHAR(255) NOT NULL,
            status VARCHAR(20) DEFAULT 'active',
            variant_slug VARCHAR(50) NOT NULL,
            credits_total INT UNSIGNED NOT NULL DEFAULT 10,
            credits_remaining INT UNSIGNED NOT NULL DEFAULT 10,
            credits_reset_date DATE NOT NULL,
            activation_limit INT UNSIGNED DEFAULT 1,
            activation_count INT UNSIGNED DEFAULT 0,
            expires_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_license_key (license_key),
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_product_id (product_id),
            INDEX idx_email (email)
        ) $charset_collate;";
        dbDelta( $sql );

        // ==========================================
        // TABLE: Activations
        // ==========================================
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_activations (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            license_id BIGINT(20) UNSIGNED NOT NULL,
            site_url VARCHAR(255) NOT NULL,
            site_name VARCHAR(255),
            ip_address VARCHAR(45),
            activated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_checked_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active TINYINT(1) DEFAULT 1,
            INDEX idx_license_id (license_id),
            INDEX idx_site_url (site_url),
            INDEX idx_is_active (is_active)
        ) $charset_collate;";
        dbDelta( $sql );

        // ==========================================
        // TABLE: API Logs
        // ==========================================
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_api_logs (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            license_id BIGINT(20) UNSIGNED,
            endpoint VARCHAR(100) NOT NULL,
            video_id VARCHAR(50),
            method VARCHAR(10) NOT NULL,
            status_code INT UNSIGNED,
            response_time INT UNSIGNED,
            credits_used INT UNSIGNED DEFAULT 0,
            cached TINYINT(1) DEFAULT 0,
            ip_address VARCHAR(45),
            user_agent VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_license_id (license_id),
            INDEX idx_endpoint (endpoint),
            INDEX idx_created_at (created_at),
            INDEX idx_video_id (video_id),
            INDEX idx_status_code (status_code)
        ) $charset_collate;";
        dbDelta( $sql );

        // ==========================================
        // TABLE: Transcript Cache
        // ==========================================
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_transcript_cache (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            video_id VARCHAR(50) NOT NULL,
            mode VARCHAR(20) NOT NULL,
            lang VARCHAR(10) NOT NULL,
            transcript MEDIUMTEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_video (video_id, mode, lang),
            INDEX idx_created_at (created_at),
            INDEX idx_video_id (video_id)
        ) $charset_collate;";
        dbDelta( $sql );

        // ==========================================
        // TABLE: Usage Stats (aggregated daily)
        // ==========================================
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ipv_usage_stats (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            date DATE NOT NULL,
            license_id BIGINT(20) UNSIGNED,
            transcripts_count INT UNSIGNED DEFAULT 0,
            descriptions_count INT UNSIGNED DEFAULT 0,
            credits_used INT UNSIGNED DEFAULT 0,
            cache_hits INT UNSIGNED DEFAULT 0,
            UNIQUE KEY unique_daily (date, license_id),
            INDEX idx_date (date),
            INDEX idx_license_id (license_id)
        ) $charset_collate;";
        dbDelta( $sql );
    }
}
