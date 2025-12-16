<?php
/**
 * Rate Limiter
 *
 * Protezione API con rate limiting basato su IP e License Key
 *
 * @version 1.5.0
 * @since 1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Rate_Limiter {

    private static $instance = null;
    private $table_name;

    // Rate limits (requests per minute)
    const LIMITS = [
        'license_info' => 60,      // 60 req/min per license info
        'gateway_youtube' => 100,  // 100 req/min per YouTube API
        'download' => 10,          // 10 req/min per downloads
        'default' => 120,          // 120 req/min for other endpoints
    ];

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ipv_rate_limits';

        add_action( 'rest_api_init', [ $this, 'register_middleware' ], 1 );
        add_action( 'ipv_cleanup_rate_limits', [ $this, 'cleanup_old_records' ] );

        // Schedule cleanup every hour
        if ( ! wp_next_scheduled( 'ipv_cleanup_rate_limits' ) ) {
            wp_schedule_event( time(), 'hourly', 'ipv_cleanup_rate_limits' );
        }
    }

    /**
     * Create rate limits table
     */
    public static function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'ipv_rate_limits';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            identifier varchar(191) NOT NULL,
            endpoint varchar(100) NOT NULL,
            request_count int(11) NOT NULL DEFAULT 1,
            window_start datetime NOT NULL,
            PRIMARY KEY (id),
            KEY identifier_endpoint (identifier, endpoint),
            KEY window_start (window_start)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Register middleware for REST API
     */
    public function register_middleware() {
        add_filter( 'rest_pre_dispatch', [ $this, 'check_rate_limit' ], 10, 3 );
    }

    /**
     * Check rate limit for incoming request
     */
    public function check_rate_limit( $result, $server, $request ) {
        // Only check IPV API endpoints
        $route = $request->get_route();
        if ( strpos( $route, '/ipv-vendor/v1/' ) === false ) {
            return $result;
        }

        // Get identifier (license key or IP address)
        $identifier = $this->get_identifier( $request );

        // Get endpoint type
        $endpoint = $this->get_endpoint_type( $route );

        // Get rate limit for this endpoint
        $limit = self::LIMITS[ $endpoint ] ?? self::LIMITS['default'];

        // Check if limit exceeded
        if ( $this->is_limit_exceeded( $identifier, $endpoint, $limit ) ) {
            return new WP_Error(
                'rate_limit_exceeded',
                sprintf(
                    'Rate limit exceeded. Maximum %d requests per minute allowed for this endpoint.',
                    $limit
                ),
                [ 'status' => 429 ]
            );
        }

        // Record request
        $this->record_request( $identifier, $endpoint );

        return $result;
    }

    /**
     * Get request identifier (license key or IP)
     */
    private function get_identifier( $request ) {
        // Try to get license key from request
        $license_key = $request->get_param( 'license_key' )
                    ?: $request->get_header( 'X-License-Key' );

        if ( $license_key ) {
            return 'license_' . sanitize_text_field( $license_key );
        }

        // Fallback to IP address
        return 'ip_' . $this->get_client_ip();
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP', // CloudFlare
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( $_SERVER[ $key ] );
                // Handle comma-separated IPs (proxies)
                if ( strpos( $ip, ',' ) !== false ) {
                    $ips = explode( ',', $ip );
                    $ip = trim( $ips[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get endpoint type from route
     */
    private function get_endpoint_type( $route ) {
        if ( strpos( $route, '/license/info' ) !== false ) {
            return 'license_info';
        }
        if ( strpos( $route, '/gateway/youtube' ) !== false ) {
            return 'gateway_youtube';
        }
        if ( strpos( $route, '/download' ) !== false || strpos( $route, '/golden-prompt' ) !== false ) {
            return 'download';
        }
        return 'default';
    }

    /**
     * Check if rate limit is exceeded
     */
    private function is_limit_exceeded( $identifier, $endpoint, $limit ) {
        global $wpdb;

        $window_start = gmdate( 'Y-m-d H:i:00' ); // Current minute

        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT request_count FROM {$this->table_name}
            WHERE identifier = %s
            AND endpoint = %s
            AND window_start = %s",
            $identifier,
            $endpoint,
            $window_start
        ) );

        return $count && $count >= $limit;
    }

    /**
     * Record a request
     */
    private function record_request( $identifier, $endpoint ) {
        global $wpdb;

        $window_start = gmdate( 'Y-m-d H:i:00' ); // Current minute

        // Try to increment existing record
        $updated = $wpdb->query( $wpdb->prepare(
            "UPDATE {$this->table_name}
            SET request_count = request_count + 1
            WHERE identifier = %s
            AND endpoint = %s
            AND window_start = %s",
            $identifier,
            $endpoint,
            $window_start
        ) );

        // If no record exists, insert new one
        if ( ! $updated ) {
            $wpdb->insert(
                $this->table_name,
                [
                    'identifier' => $identifier,
                    'endpoint' => $endpoint,
                    'request_count' => 1,
                    'window_start' => $window_start,
                ],
                [ '%s', '%s', '%d', '%s' ]
            );
        }
    }

    /**
     * Cleanup old records (older than 1 hour)
     */
    public function cleanup_old_records() {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$this->table_name}
            WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
    }

    /**
     * Get rate limit stats for identifier
     */
    public function get_stats( $identifier, $endpoint = null ) {
        global $wpdb;

        $window_start = gmdate( 'Y-m-d H:i:00' );

        if ( $endpoint ) {
            return $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$this->table_name}
                WHERE identifier = %s
                AND endpoint = %s
                AND window_start = %s",
                $identifier,
                $endpoint,
                $window_start
            ) );
        }

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$this->table_name}
            WHERE identifier = %s
            AND window_start = %s",
            $identifier,
            $window_start
        ) );
    }

    /**
     * Reset rate limit for identifier (admin only)
     */
    public function reset_limit( $identifier, $endpoint = null ) {
        global $wpdb;

        $where = [ 'identifier' => $identifier ];
        $where_format = [ '%s' ];

        if ( $endpoint ) {
            $where['endpoint'] = $endpoint;
            $where_format[] = '%s';
        }

        return $wpdb->delete( $this->table_name, $where, $where_format );
    }

    /**
     * Get current limits configuration
     */
    public static function get_limits() {
        return self::LIMITS;
    }
}

// Initialize
IPV_Vendor_Rate_Limiter::instance();
