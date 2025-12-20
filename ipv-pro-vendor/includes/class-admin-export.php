<?php
/**
 * Admin Export - v1.4.1
 *
 * CSV export functionality for licenses and ledger
 *
 * @package IPV_Pro_Vendor
 * @since 1.4.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Admin_Export {

    public static function init() {
        add_action( 'admin_post_ipv_export_licenses', [ __CLASS__, 'export_licenses' ] );
        add_action( 'admin_post_ipv_export_ledger', [ __CLASS__, 'export_ledger' ] );
    }

    public static function export_licenses() {
        check_admin_referer( 'ipv_export_licenses' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ipv_licenses';
        $licenses = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=ipv-licenses-' . date( 'Y-m-d' ) . '.csv' );

        $output = fopen( 'php://output', 'w' );

        // Header
        fputcsv( $output, [
            'ID', 'License Key', 'User ID', 'User Email', 'Order ID',
            'Variant', 'Period', 'Status', 'Credits Monthly', 'Credits Extra',
            'Site URL', 'Created', 'Expires'
        ] );

        // Rows
        foreach ( $licenses as $lic ) {
            fputcsv( $output, [
                $lic['id'],
                $lic['license_key'],
                $lic['user_id'],
                $lic['user_email'],
                $lic['order_id'],
                $lic['variant_slug'],
                $lic['period'],
                $lic['status'],
                $lic['credits_monthly'] ?? 0,
                $lic['credits_extra'] ?? 0,
                $lic['site_url'] ?? '',
                $lic['created_at'],
                $lic['expires_at'] ?? ''
            ] );
        }

        fclose( $output );
        exit;
    }

    public static function export_ledger() {
        check_admin_referer( 'ipv_export_ledger' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $license_key = sanitize_text_field( $_GET['license_key'] ?? '' );

        global $wpdb;
        $table = $wpdb->prefix . 'ipv_credit_ledger';

        if ( $license_key ) {
            $ledger = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$table} WHERE license_key = %s ORDER BY created_at DESC",
                $license_key
            ), ARRAY_A );
            $filename = "ipv-ledger-{$license_key}-" . date( 'Y-m-d' ) . '.csv';
        } else {
            $ledger = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC", ARRAY_A );
            $filename = 'ipv-ledger-all-' . date( 'Y-m-d' ) . '.csv';
        }

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );

        $output = fopen( 'php://output', 'w' );

        // Header
        fputcsv( $output, [
            'ID', 'License Key', 'Type', 'Amount', 'Balance After',
            'Ref Type', 'Ref ID', 'Note', 'Created'
        ] );

        // Rows
        foreach ( $ledger as $entry ) {
            fputcsv( $output, [
                $entry['id'],
                $entry['license_key'],
                $entry['type'],
                $entry['amount'],
                $entry['balance_after'],
                $entry['ref_type'] ?? '',
                $entry['ref_id'] ?? '',
                $entry['note'] ?? '',
                $entry['created_at']
            ] );
        }

        fclose( $output );
        exit;
    }
}
