<?php
/**
 * Credit Ledger Table - v1.4.1
 *
 * WP_List_Table for displaying credit transactions
 *
 * @package IPV_Pro_Vendor
 * @since 1.4.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class IPV_Ledger_Table extends WP_List_Table {

    private $license_key;

    public function __construct( $license_key = '' ) {
        parent::__construct( [
            'singular' => 'transaction',
            'plural'   => 'transactions',
            'ajax'     => false
        ] );
        $this->license_key = $license_key;
    }

    public function get_columns() {
        return [
            'created_at'    => 'Date',
            'type'          => 'Type',
            'amount'        => 'Amount',
            'balance_after' => 'Balance After',
            'ref'           => 'Reference',
            'note'          => 'Note'
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $table = $wpdb->prefix . 'ipv_credit_ledger';

        $per_page = 50;
        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;

        $where = $this->license_key ? $wpdb->prepare( " WHERE license_key = %s", $this->license_key ) : '';

        $total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" );

        $this->items = $wpdb->get_results(
            "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT {$per_page} OFFSET {$offset}",
            ARRAY_A
        );

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page )
        ] );
    }

    public function column_type( $item ) {
        $types = [
            'grant_monthly' => '📅 Monthly Grant',
            'grant_extra'   => '💳 Extra Purchase',
            'consume'       => '📉 Consumed',
            'adjust'        => '⚙️ Admin Adjust'
        ];
        return $types[$item['type']] ?? $item['type'];
    }

    public function column_amount( $item ) {
        $amount = intval( $item['amount'] );
        if ( $amount > 0 ) {
            return '<span style="color: green;">+' . $amount . '</span>';
        }
        return '<span style="color: red;">' . $amount . '</span>';
    }

    public function column_ref( $item ) {
        if ( $item['ref_type'] && $item['ref_id'] ) {
            return "{$item['ref_type']}#{$item['ref_id']}";
        }
        return '-';
    }

    public function column_default( $item, $column_name ) {
        return $item[$column_name] ?? '-';
    }
}
