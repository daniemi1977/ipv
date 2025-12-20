<?php
/**
 * Audit Log
 *
 * Sistema di logging per azioni critiche (delete, refund, config changes)
 *
 * @version 1.5.0
 * @since 1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Audit_Log {

    private static $instance = null;
    private $table_name;

    // Event types
    const EVENTS = [
        'license_created' => 'Licenza creata',
        'license_deleted' => 'Licenza eliminata',
        'license_activated' => 'Licenza attivata',
        'license_deactivated' => 'Licenza disattivata',
        'license_suspended' => 'Licenza sospesa',
        'license_renewed' => 'Licenza rinnovata',
        'credits_added' => 'Crediti aggiunti',
        'credits_removed' => 'Crediti rimossi',
        'credits_reset' => 'Crediti azzerati',
        'plan_changed' => 'Piano cambiato',
        'refund_issued' => 'Rimborso emesso',
        'golden_prompt_uploaded' => 'Golden Prompt caricato',
        'golden_prompt_deleted' => 'Golden Prompt eliminato',
        'config_changed' => 'Configurazione modificata',
        'api_key_created' => 'API Key creata',
        'api_key_deleted' => 'API Key eliminata',
        'security_alert' => 'Alert di sicurezza',
    ];

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ipv_audit_log';

        add_action( 'ipv_cleanup_audit_log', [ $this, 'cleanup_old_records' ] );

        // Schedule cleanup every day
        if ( ! wp_next_scheduled( 'ipv_cleanup_audit_log' ) ) {
            wp_schedule_event( time(), 'daily', 'ipv_cleanup_audit_log' );
        }
    }

    /**
     * Create audit log table
     */
    public static function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'ipv_audit_log';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            event_description text NOT NULL,
            user_id bigint(20) UNSIGNED NULL,
            user_login varchar(60) NULL,
            user_ip varchar(45) NULL,
            license_id bigint(20) UNSIGNED NULL,
            license_key varchar(100) NULL,
            metadata longtext NULL COMMENT 'JSON data',
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY user_id (user_id),
            KEY license_id (license_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Log an event
     */
    public function log( $event_type, $description, $license_id = null, $license_key = null, $metadata = [] ) {
        global $wpdb;

        $user = wp_get_current_user();

        $data = [
            'event_type' => $event_type,
            'event_description' => $description,
            'user_id' => $user->ID ?: null,
            'user_login' => $user->user_login ?: null,
            'user_ip' => $this->get_client_ip(),
            'license_id' => $license_id,
            'license_key' => $license_key,
            'metadata' => ! empty( $metadata ) ? wp_json_encode( $metadata ) : null,
            'created_at' => current_time( 'mysql' ),
        ];

        $wpdb->insert( $this->table_name, $data, [
            '%s', // event_type
            '%s', // event_description
            '%d', // user_id
            '%s', // user_login
            '%s', // user_ip
            '%d', // license_id
            '%s', // license_key
            '%s', // metadata
            '%s', // created_at
        ] );

        return $wpdb->insert_id;
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( $_SERVER[ $key ] );
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
     * Get audit logs with filters
     */
    public function get_logs( $args = [] ) {
        global $wpdb;

        $defaults = [
            'event_type' => null,
            'user_id' => null,
            'license_id' => null,
            'start_date' => null,
            'end_date' => null,
            'limit' => 100,
            'offset' => 0,
            'order_by' => 'created_at',
            'order' => 'DESC',
        ];

        $args = wp_parse_args( $args, $defaults );

        $where = [ '1=1' ];
        $where_values = [];

        if ( $args['event_type'] ) {
            $where[] = 'event_type = %s';
            $where_values[] = $args['event_type'];
        }

        if ( $args['user_id'] ) {
            $where[] = 'user_id = %d';
            $where_values[] = $args['user_id'];
        }

        if ( $args['license_id'] ) {
            $where[] = 'license_id = %d';
            $where_values[] = $args['license_id'];
        }

        if ( $args['start_date'] ) {
            $where[] = 'created_at >= %s';
            $where_values[] = $args['start_date'];
        }

        if ( $args['end_date'] ) {
            $where[] = 'created_at <= %s';
            $where_values[] = $args['end_date'];
        }

        $where_clause = implode( ' AND ', $where );

        $order_by = in_array( $args['order_by'], [ 'id', 'created_at', 'event_type' ] )
            ? $args['order_by']
            : 'created_at';

        $order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        $limit = absint( $args['limit'] );
        $offset = absint( $args['offset'] );

        $query = "SELECT * FROM {$this->table_name}
                  WHERE {$where_clause}
                  ORDER BY {$order_by} {$order}
                  LIMIT {$limit} OFFSET {$offset}";

        if ( ! empty( $where_values ) ) {
            $query = $wpdb->prepare( $query, $where_values );
        }

        return $wpdb->get_results( $query );
    }

    /**
     * Get total count with filters
     */
    public function get_count( $args = [] ) {
        global $wpdb;

        $where = [ '1=1' ];
        $where_values = [];

        if ( ! empty( $args['event_type'] ) ) {
            $where[] = 'event_type = %s';
            $where_values[] = $args['event_type'];
        }

        if ( ! empty( $args['user_id'] ) ) {
            $where[] = 'user_id = %d';
            $where_values[] = $args['user_id'];
        }

        if ( ! empty( $args['license_id'] ) ) {
            $where[] = 'license_id = %d';
            $where_values[] = $args['license_id'];
        }

        $where_clause = implode( ' AND ', $where );

        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";

        if ( ! empty( $where_values ) ) {
            $query = $wpdb->prepare( $query, $where_values );
        }

        return (int) $wpdb->get_var( $query );
    }

    /**
     * Get logs for specific license
     */
    public function get_license_history( $license_id ) {
        return $this->get_logs( [
            'license_id' => $license_id,
            'limit' => 1000,
        ] );
    }

    /**
     * Get recent activity
     */
    public function get_recent_activity( $limit = 50 ) {
        return $this->get_logs( [
            'limit' => $limit,
            'order_by' => 'created_at',
            'order' => 'DESC',
        ] );
    }

    /**
     * Cleanup old records (older than 90 days)
     */
    public function cleanup_old_records() {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$this->table_name}
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
        );
    }

    /**
     * Export logs to CSV
     */
    public function export_csv( $args = [] ) {
        $logs = $this->get_logs( array_merge( $args, [ 'limit' => 10000 ] ) );

        $csv = "ID,Event Type,Description,User,User IP,License Key,Created At\n";

        foreach ( $logs as $log ) {
            $csv .= sprintf(
                "%d,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $log->id,
                $log->event_type,
                addslashes( $log->event_description ),
                $log->user_login ?: 'System',
                $log->user_ip,
                $log->license_key ?: '-',
                $log->created_at
            );
        }

        return $csv;
    }

    /**
     * Get event types list
     */
    public static function get_event_types() {
        return self::EVENTS;
    }
}

// Initialize
IPV_Vendor_Audit_Log::instance();
