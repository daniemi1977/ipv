<?php
/**
 * IPV Credits Manager
 *
 * Gestisce crediti, deduzioni e informazioni sul saldo
 *
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Credits_Manager {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Cron job per reset crediti periodico
        add_action( 'ipv_daily_credits_check', [ $this, 'process_credits_reset' ] );

        // Schedula cron se non esiste
        if ( ! wp_next_scheduled( 'ipv_daily_credits_check' ) ) {
            wp_schedule_event( time(), 'daily', 'ipv_daily_credits_check' );
        }
    }

    /**
     * Get credits info for a license
     */
    public function get_credits_info( $license ) {
        if ( ! $license ) {
            return [
                'credits_remaining' => 0,
                'credits_total' => 0,
                'credits_used' => 0,
                'percentage_used' => 0,
                'percentage_remaining' => 0,
                'days_until_reset' => 0,
                'next_reset_date' => null,
            ];
        }

        $remaining = (int) ( $license->credits_remaining ?? 0 );
        $total = (int) ( $license->credits_total ?? 0 );
        $used = $total - $remaining;

        $percentage_used = $total > 0 ? ( $used / $total ) * 100 : 0;
        $percentage_remaining = $total > 0 ? ( $remaining / $total ) * 100 : 0;

        // Calculate days until reset based on credits_period
        $days_until_reset = $this->calculate_days_until_reset( $license );

        return [
            'credits_remaining' => $remaining,
            'credits_total' => $total,
            'credits_used' => $used,
            'percentage_used' => round( $percentage_used, 1 ),
            'percentage_remaining' => round( $percentage_remaining, 1 ),
            'days_until_reset' => $days_until_reset,
            'next_reset_date' => $days_until_reset > 0 ? date( 'Y-m-d', strtotime( "+{$days_until_reset} days" ) ) : null,
        ];
    }

    /**
     * Calculate days until credits reset
     */
    private function calculate_days_until_reset( $license ) {
        if ( ! $license || empty( $license->created_at ) ) {
            return 0;
        }

        $plans_manager = IPV_Vendor_Plans_Manager::instance();
        $plan = $plans_manager->get_plan( $license->variant_slug );
        $period = $plan['credits_period'] ?? 'year';

        $created = strtotime( $license->created_at );
        $now = time();

        switch ( $period ) {
            case 'day':
                $interval = DAY_IN_SECONDS;
                break;
            case 'week':
                $interval = WEEK_IN_SECONDS;
                break;
            case 'month':
                $interval = MONTH_IN_SECONDS;
                break;
            case 'year':
            default:
                $interval = YEAR_IN_SECONDS;
                break;
        }

        // Find the next reset date
        $periods_elapsed = floor( ( $now - $created ) / $interval );
        $next_reset = $created + ( ( $periods_elapsed + 1 ) * $interval );

        return max( 0, ceil( ( $next_reset - $now ) / DAY_IN_SECONDS ) );
    }

    /**
     * Deduct credits from a license
     *
     * @param object|string $license License object or license key
     * @param int $amount Amount of credits to deduct
     * @param string $reason Reason for deduction
     * @param string $ref_type Reference type (e.g., 'generation', 'api_call')
     * @param string $ref_id Reference ID
     * @return bool|WP_Error Success or error
     */
    public function deduct_credits( $license, $amount, $reason = '', $ref_type = 'usage', $ref_id = '' ) {
        global $wpdb;

        // Get license if string passed
        if ( is_string( $license ) ) {
            $license = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE license_key = %s",
                $license
            ) );
        }

        if ( ! $license ) {
            return new WP_Error( 'invalid_license', __( 'Licenza non valida', 'ipv-pro-vendor' ) );
        }

        $amount = absint( $amount );
        if ( $amount <= 0 ) {
            return new WP_Error( 'invalid_amount', __( 'Importo non valido', 'ipv-pro-vendor' ) );
        }

        $current_credits = (int) $license->credits_remaining;

        if ( $current_credits < $amount ) {
            return new WP_Error( 'insufficient_credits', __( 'Crediti insufficienti', 'ipv-pro-vendor' ) );
        }

        $new_balance = $current_credits - $amount;

        // Update license credits
        $wpdb->update(
            $wpdb->prefix . 'ipv_licenses',
            [ 'credits_remaining' => $new_balance ],
            [ 'id' => $license->id ],
            [ '%d' ],
            [ '%d' ]
        );

        // Log transaction
        $wpdb->insert(
            $wpdb->prefix . 'ipv_credit_ledger',
            [
                'license_key' => $license->license_key,
                'type' => 'debit',
                'amount' => -$amount,
                'balance_after' => $new_balance,
                'ref_type' => $ref_type,
                'ref_id' => $ref_id,
                'note' => $reason,
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s' ]
        );

        // Update license object
        $license->credits_remaining = $new_balance;

        // Trigger credits used event for notifications
        do_action( 'ipv_credits_used', $license, $amount );

        return true;
    }

    /**
     * Add credits to a license
     *
     * @param object|string $license License object or license key
     * @param int $amount Amount of credits to add
     * @param string $reason Reason for addition
     * @param string $ref_type Reference type (e.g., 'purchase', 'bonus')
     * @param string $ref_id Reference ID (e.g., order ID)
     * @return bool|WP_Error Success or error
     */
    public function add_credits( $license, $amount, $reason = '', $ref_type = 'credit', $ref_id = '' ) {
        global $wpdb;

        // Get license if string passed
        if ( is_string( $license ) ) {
            $license = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE license_key = %s",
                $license
            ) );
        }

        if ( ! $license ) {
            return new WP_Error( 'invalid_license', __( 'Licenza non valida', 'ipv-pro-vendor' ) );
        }

        $amount = absint( $amount );
        if ( $amount <= 0 ) {
            return new WP_Error( 'invalid_amount', __( 'Importo non valido', 'ipv-pro-vendor' ) );
        }

        $current_credits = (int) $license->credits_remaining;
        $new_balance = $current_credits + $amount;

        // Update license credits
        $wpdb->update(
            $wpdb->prefix . 'ipv_licenses',
            [ 'credits_remaining' => $new_balance ],
            [ 'id' => $license->id ],
            [ '%d' ],
            [ '%d' ]
        );

        // Log transaction
        $wpdb->insert(
            $wpdb->prefix . 'ipv_credit_ledger',
            [
                'license_key' => $license->license_key,
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $new_balance,
                'ref_type' => $ref_type,
                'ref_id' => $ref_id,
                'note' => $reason,
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s' ]
        );

        return true;
    }

    /**
     * Process daily credits reset for eligible licenses
     */
    public function process_credits_reset() {
        global $wpdb;

        $plans_manager = IPV_Vendor_Plans_Manager::instance();
        $all_plans = $plans_manager->get_plans();

        // Get all active licenses
        $licenses = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE status = 'active'"
        );

        foreach ( $licenses as $license ) {
            $plan = $all_plans[ $license->variant_slug ] ?? null;
            if ( ! $plan ) {
                continue;
            }

            if ( $this->should_reset_credits( $license, $plan ) ) {
                $new_credits = $plan['credits'];

                // Reset credits
                $wpdb->update(
                    $wpdb->prefix . 'ipv_licenses',
                    [
                        'credits_remaining' => $new_credits,
                        'credits_total' => $new_credits,
                    ],
                    [ 'id' => $license->id ],
                    [ '%d', '%d' ],
                    [ '%d' ]
                );

                // Log reset
                $wpdb->insert(
                    $wpdb->prefix . 'ipv_credit_ledger',
                    [
                        'license_key' => $license->license_key,
                        'type' => 'reset',
                        'amount' => $new_credits,
                        'balance_after' => $new_credits,
                        'ref_type' => 'period_reset',
                        'ref_id' => $plan['credits_period'],
                        'note' => sprintf( 'Reset crediti periodico (%s)', $plan['credits_period'] ),
                        'created_at' => current_time( 'mysql' ),
                    ],
                    [ '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s' ]
                );

                // Update license object for notification
                $license->credits_remaining = $new_credits;

                // Trigger reset notification
                do_action( 'ipv_credits_reset', $license, $new_credits );
            }
        }
    }

    /**
     * Check if credits should be reset for a license
     */
    private function should_reset_credits( $license, $plan ) {
        if ( empty( $license->created_at ) ) {
            return false;
        }

        $period = $plan['credits_period'] ?? 'year';
        $created = strtotime( $license->created_at );
        $now = time();

        switch ( $period ) {
            case 'day':
                $interval = DAY_IN_SECONDS;
                break;
            case 'week':
                $interval = WEEK_IN_SECONDS;
                break;
            case 'month':
                $interval = MONTH_IN_SECONDS;
                break;
            case 'year':
            default:
                $interval = YEAR_IN_SECONDS;
                break;
        }

        // Check if we're at a period boundary (within 1 hour window)
        $periods_elapsed = floor( ( $now - $created ) / $interval );
        $current_period_start = $created + ( $periods_elapsed * $interval );
        $time_since_period_start = $now - $current_period_start;

        // Reset if within first hour of new period
        return $time_since_period_start < HOUR_IN_SECONDS;
    }

    /**
     * Get credit ledger for a license
     */
    public function get_ledger( $license_key, $limit = 50, $offset = 0 ) {
        global $wpdb;

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_credit_ledger
            WHERE license_key = %s
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d",
            $license_key,
            $limit,
            $offset
        ) );
    }

    /**
     * Get usage statistics for a license
     */
    public function get_stats( $license_key, $period = 'month' ) {
        global $wpdb;

        $date_condition = '';
        switch ( $period ) {
            case 'day':
                $date_condition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                break;
            case 'week':
                $date_condition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $date_condition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $date_condition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
        }

        $stats = $wpdb->get_row( $wpdb->prepare(
            "SELECT
                SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as total_used,
                SUM(CASE WHEN amount > 0 AND type = 'credit' THEN amount ELSE 0 END) as total_added,
                COUNT(CASE WHEN type = 'debit' THEN 1 END) as transaction_count
            FROM {$wpdb->prefix}ipv_credit_ledger
            WHERE license_key = %s {$date_condition}",
            $license_key
        ) );

        return [
            'total_used' => (int) ( $stats->total_used ?? 0 ),
            'total_added' => (int) ( $stats->total_added ?? 0 ),
            'transaction_count' => (int) ( $stats->transaction_count ?? 0 ),
            'period' => $period,
        ];
    }
}

// Initialize
IPV_Vendor_Credits_Manager::instance();
