<?php
/**
 * Licenses List Table - v1.4.1
 *
 * WP_List_Table for displaying licenses with search and pagination
 *
 * @package IPV_Pro_Vendor
 * @since 1.4.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class IPV_Licenses_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => 'license',
            'plural'   => 'licenses',
            'ajax'     => false
        ] );
    }

    public function get_columns() {
        return [
            'license_key' => 'License Key',
            'user'        => 'User',
            'site_lock'   => 'Site Lock',
            'site_url'    => 'Site URL',
            'plan'        => 'Plan',
            'period'      => 'Period',
            'status'      => 'Status',
            'credits'     => 'Credits',
            'updated_at'  => 'Last Update'
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $table = $wpdb->prefix . 'ipv_licenses';

        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;

        // Search
        $search = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '';
        $where = '';
        if ( $search ) {
            $where = $wpdb->prepare( " WHERE license_key LIKE %s OR user_email LIKE %s OR site_url LIKE %s",
                '%' . $wpdb->esc_like( $search ) . '%',
                '%' . $wpdb->esc_like( $search ) . '%',
                '%' . $wpdb->esc_like( $search ) . '%'
            );
        }

        $total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" );

        $this->items = $wpdb->get_results(
            "SELECT * FROM {$table} {$where} ORDER BY id DESC LIMIT {$per_page} OFFSET {$offset}",
            ARRAY_A
        );

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page )
        ] );
    }

    public function column_default( $item, $column_name ) {
        return $item[$column_name] ?? '-';
    }

    public function column_license_key( $item ) {
        $url = add_query_arg( [ 'page' => 'ipv-licenses', 'id' => $item['id'] ], admin_url( 'admin.php' ) );
        return '<a href="' . esc_url( $url ) . '"><strong>' . esc_html( $item['license_key'] ) . '</strong></a>';
    }

    public function column_user( $item ) {
        $user = get_user_by( 'id', $item['user_id'] );
        return $user ? $user->user_email : 'ID: ' . $item['user_id'];
    }

    public function column_site_lock( $item ) {
        if ( ! empty( $item['site_url'] ) ) {
            return '<span class="dashicons dashicons-lock" style="color: green;" title="Locked"></span> <strong>LOCK</strong>';
        }
        return '<span class="dashicons dashicons-unlock" style="color: red;" title="Unlocked"></span> UNLOCK';
    }

    public function column_credits( $item ) {
        $monthly = $item['credits_monthly'] ?? 0;
        $extra = $item['credits_extra'] ?? 0;
        $total = $monthly + $extra;
        return "<strong>{$total}</strong> <small>({$monthly}m + {$extra}e)</small>";
    }

    public function column_status( $item ) {
        $status = $item['status'];
        $colors = [
            'active'   => 'green',
            'inactive' => 'orange',
            'expired'  => 'red'
        ];
        $color = $colors[$status] ?? 'gray';
        return "<span style='color: {$color};'>●</span> " . ucfirst( $status );
    }
}
