<?php
/**
 * Admin Actions Handler - v1.4.1
 *
 * Handles admin_post actions for license management:
 * - Add credits (monthly or extra)
 * - Reset monthly credits manually
 * - Unlock site (with 7-day cooldown check)
 * - Rebind site to new URL
 *
 * @package IPV_Pro_Vendor
 * @since 1.4.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Admin_Actions {

    /**
     * Site unlock cooldown (days)
     */
    const COOLDOWN_DAYS = 7;

    /**
     * Initialize
     */
    public static function init() {
        add_action( 'admin_post_ipv_add_credits', [ __CLASS__, 'handle_add_credits' ] );
        add_action( 'admin_post_ipv_reset_monthly', [ __CLASS__, 'handle_reset_monthly' ] );
        add_action( 'admin_post_ipv_unlock_site', [ __CLASS__, 'handle_unlock_site' ] );
        add_action( 'admin_post_ipv_rebind_site', [ __CLASS__, 'handle_rebind_site' ] );
    }

    /**
     * Handle add credits action
     */
    public static function handle_add_credits() {
        check_admin_referer( 'ipv_add_credits' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $license_id = intval( $_POST['license_id'] ?? 0 );
        $amount = intval( $_POST['amount'] ?? 0 );
        $type = sanitize_text_field( $_POST['type'] ?? 'extra' ); // monthly or extra
        $note = sanitize_textarea_field( $_POST['note'] ?? '' );

        if ( ! $license_id || ! $amount ) {
            wp_die( 'Invalid parameters' );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ipv_licenses';
        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $license_id
        ), ARRAY_A );

        if ( ! $license ) {
            wp_die( 'License not found' );
        }

        // Add credits
        if ( $type === 'monthly' ) {
            $new_monthly = $license['credits_monthly'] + $amount;
            $wpdb->update(
                $table,
                [ 'credits_monthly' => $new_monthly ],
                [ 'id' => $license_id ]
            );
            $balance_after = $new_monthly + $license['credits_extra'];
        } else {
            $new_extra = $license['credits_extra'] + $amount;
            $wpdb->update(
                $table,
                [ 'credits_extra' => $new_extra ],
                [ 'id' => $license_id ]
            );
            $balance_after = $license['credits_monthly'] + $new_extra;
        }

        // Log to ledger
        self::log_ledger(
            $license['license_key'],
            'grant_' . $type,
            $amount,
            $balance_after,
            'admin',
            get_current_user_id(),
            $note ?: "Admin manual grant ({$type})"
        );

        // Redirect back
        wp_redirect( add_query_arg(
            [ 'page' => 'ipv-licenses', 'id' => $license_id, 'updated' => 'credits' ],
            admin_url( 'admin.php' )
        ) );
        exit;
    }

    /**
     * Handle reset monthly credits action
     */
    public static function handle_reset_monthly() {
        check_admin_referer( 'ipv_reset_monthly' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $license_id = intval( $_POST['license_id'] ?? 0 );

        if ( ! $license_id ) {
            wp_die( 'Invalid parameters' );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ipv_licenses';
        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $license_id
        ), ARRAY_A );

        if ( ! $license ) {
            wp_die( 'License not found' );
        }

        // Reset to credits_total
        $wpdb->update(
            $table,
            [
                'credits_monthly' => $license['credits_total'],
                'credits_used_month' => 0
            ],
            [ 'id' => $license_id ]
        );

        $balance_after = $license['credits_total'] + $license['credits_extra'];

        // Log to ledger
        self::log_ledger(
            $license['license_key'],
            'grant_monthly',
            $license['credits_total'],
            $balance_after,
            'admin',
            get_current_user_id(),
            'Admin manual reset monthly credits'
        );

        // Redirect back
        wp_redirect( add_query_arg(
            [ 'page' => 'ipv-licenses', 'id' => $license_id, 'updated' => 'reset' ],
            admin_url( 'admin.php' )
        ) );
        exit;
    }

    /**
     * Handle unlock site action
     */
    public static function handle_unlock_site() {
        check_admin_referer( 'ipv_unlock_site' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $license_id = intval( $_POST['license_id'] ?? 0 );

        if ( ! $license_id ) {
            wp_die( 'Invalid parameters' );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ipv_licenses';
        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $license_id
        ), ARRAY_A );

        if ( ! $license ) {
            wp_die( 'License not found' );
        }

        // Check cooldown
        if ( self::COOLDOWN_DAYS > 0 && ! empty( $license['site_unlock_at'] ) ) {
            $last_unlock = strtotime( $license['site_unlock_at'] );
            $next_allowed = $last_unlock + ( self::COOLDOWN_DAYS * DAY_IN_SECONDS );

            if ( time() < $next_allowed ) {
                $days_remaining = ceil( ( $next_allowed - time() ) / DAY_IN_SECONDS );
                wp_die( "Cooldown attivo. Potrai sbloccare il sito tra {$days_remaining} giorni." );
            }
        }

        // Unlock site
        $wpdb->update(
            $table,
            [
                'site_url' => null,
                'site_unlock_at' => current_time( 'mysql' )
            ],
            [ 'id' => $license_id ]
        );

        // Redirect back
        wp_redirect( add_query_arg(
            [ 'page' => 'ipv-licenses', 'id' => $license_id, 'updated' => 'unlocked' ],
            admin_url( 'admin.php' )
        ) );
        exit;
    }

    /**
     * Handle rebind site action
     */
    public static function handle_rebind_site() {
        check_admin_referer( 'ipv_rebind_site' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $license_id = intval( $_POST['license_id'] ?? 0 );
        $new_url = sanitize_text_field( $_POST['new_url'] ?? '' );

        if ( ! $license_id || ! $new_url ) {
            wp_die( 'Invalid parameters' );
        }

        // Validate URL
        $parsed = parse_url( $new_url );
        if ( ! $parsed || empty( $parsed['host'] ) ) {
            wp_die( 'Invalid URL format' );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ipv_licenses';

        // Update site binding
        $wpdb->update(
            $table,
            [ 'site_url' => $new_url ],
            [ 'id' => $license_id ]
        );

        // Redirect back
        wp_redirect( add_query_arg(
            [ 'page' => 'ipv-licenses', 'id' => $license_id, 'updated' => 'rebound' ],
            admin_url( 'admin.php' )
        ) );
        exit;
    }

    /**
     * Log transaction to ledger
     */
    private static function log_ledger( $license_key, $type, $amount, $balance_after, $ref_type = null, $ref_id = null, $note = null ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ipv_credit_ledger';

        $wpdb->insert( $table, [
            'license_key' => $license_key,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $balance_after,
            'ref_type' => $ref_type,
            'ref_id' => $ref_id,
            'note' => $note,
            'created_at' => current_time( 'mysql' )
        ] );
    }
}
