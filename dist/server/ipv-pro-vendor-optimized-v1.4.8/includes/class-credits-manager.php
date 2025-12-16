<?php
/**
 * IPV Credits Manager
 *
 * Gestisce sistema crediti mensili, reset automatico e statistiche usage
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

    /**
     * Check if license has enough credits
     */
    public function has_credits( $license, $required = 1 ) {
        return (int) $license->credits_remaining >= $required;
    }

    /**
     * Use credits
     */
    public function use_credits( $license_id, $amount = 1 ) {
        global $wpdb;

        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}ipv_licenses
            SET credits_remaining = GREATEST(0, credits_remaining - %d)
            WHERE id = %d",
            $amount,
            $license_id
        ));

        // Update daily stats
        $this->update_daily_stats( $license_id, 'credits_used', $amount );
        $this->update_daily_stats( $license_id, 'transcripts_count', 1 );

        error_log( sprintf(
            'IPV Vendor: Credits used - License ID %d, Amount: %d',
            $license_id,
            $amount
        ));

        return true;
    }

    /**
     * Get credits info for license
     */
    public function get_credits_info( $license ) {
        $credits_remaining = (int) $license->credits_remaining;
        $credits_total = (int) $license->credits_total;
        $credits_used = $credits_total - $credits_remaining;

        // Calculate percentage
        $percentage = $credits_total > 0 ? round( ( $credits_remaining / $credits_total ) * 100 ) : 0;

        return [
            'credits_total' => $credits_total,
            'credits_remaining' => $credits_remaining,
            'credits_used' => $credits_used,
            'percentage' => $percentage,
            'reset_date' => $license->credits_reset_date,
            'reset_date_formatted' => date_i18n( 'd/m/Y', strtotime( $license->credits_reset_date ) ),
            'days_until_reset' => $this->days_until_reset( $license->credits_reset_date ),
            'status' => $this->get_credit_status( $credits_remaining, $credits_total )
        ];
    }

    /**
     * Get credit status (ok, low, critical, depleted)
     */
    private function get_credit_status( $remaining, $total ) {
        if ( $remaining <= 0 ) {
            return 'depleted';
        }

        $percentage = ( $remaining / $total ) * 100;

        if ( $percentage <= 10 ) {
            return 'critical';
        } elseif ( $percentage <= 25 ) {
            return 'low';
        }

        return 'ok';
    }

    /**
     * Days until reset
     */
    private function days_until_reset( $reset_date ) {
        $today = strtotime( 'today' );
        $reset = strtotime( $reset_date );
        $diff = $reset - $today;
        return max( 0, floor( $diff / DAY_IN_SECONDS ) );
    }

    /**
     * Reset license credits (monthly)
     */
    public function reset_license_credits( $license_id ) {
        global $wpdb;

        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE id = %d",
            $license_id
        ));

        if ( ! $license ) {
            return false;
        }

        // Check if subscription is still active via WooCommerce
        if ( ! $this->is_subscription_active( $license->order_id, $license->product_id ) ) {
            // Subscription cancelled/expired - don't reset, mark as cancelled
            $wpdb->update(
                $wpdb->prefix . 'ipv_licenses',
                [ 'status' => 'cancelled' ],
                [ 'id' => $license_id ],
                [ '%s' ],
                [ '%d' ]
            );

            error_log( sprintf(
                'IPV Vendor: License %s NOT reset - subscription inactive',
                $license->license_key
            ));

            return false;
        }

        // Reset credits
        $wpdb->update(
            $wpdb->prefix . 'ipv_licenses',
            [
                'credits_remaining' => $license->credits_total,
                'credits_reset_date' => date( 'Y-m-01', strtotime( '+1 month' ) )
            ],
            [ 'id' => $license_id ],
            [ '%d', '%s' ],
            [ '%d' ]
        );

        // Send email notification
        $this->send_credits_reset_email( $license );

        error_log( sprintf(
            'IPV Vendor: Credits reset for license %s (%s) - %d credits',
            $license->license_key,
            $license->email,
            $license->credits_total
        ));

        return true;
    }

    /**
     * Reset all active licenses credits (called by monthly cron)
     */
    public static function reset_all_credits() {
        global $wpdb;

        // Get all licenses that need reset
        $licenses = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses
            WHERE status = 'active'
            AND credits_reset_date <= CURDATE()"
        );

        $manager = self::instance();
        $count = 0;

        foreach ( $licenses as $license ) {
            if ( $manager->reset_license_credits( $license->id ) ) {
                $count++;
            }
        }

        error_log( sprintf(
            'IPV Vendor: Monthly credits reset completed - %d licenses reset',
            $count
        ));

        return $count;
    }

    /**
     * Check if WooCommerce subscription is active
     */
    private function is_subscription_active( $order_id, $product_id ) {
        // Get product to check if it's a subscription
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return false;
        }

        // If not a subscription product, check expiry date
        if ( ! $product->is_type( 'subscription' ) ) {
            return true; // One-time purchase, expiry handled separately
        }

        // Check WooCommerce Subscriptions
        if ( ! function_exists( 'wcs_order_contains_subscription' ) ) {
            return true; // Subscriptions not installed
        }

        if ( ! wcs_order_contains_subscription( $order_id ) ) {
            return true; // Not a subscription order
        }

        $subscriptions = wcs_get_subscriptions_for_order( $order_id );

        foreach ( $subscriptions as $subscription ) {
            // Active or pending-cancel are OK for reset
            if ( $subscription->has_status( [ 'active', 'pending-cancel' ] ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update daily stats
     */
    private function update_daily_stats( $license_id, $field, $increment = 1 ) {
        global $wpdb;

        $date = current_time( 'Y-m-d' );

        // Validate field
        $allowed_fields = [ 'credits_used', 'transcripts_count', 'descriptions_count', 'cache_hits' ];
        if ( ! in_array( $field, $allowed_fields ) ) {
            return;
        }

        $wpdb->query( $wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}ipv_usage_stats
            (date, license_id, {$field})
            VALUES (%s, %d, %d)
            ON DUPLICATE KEY UPDATE
            {$field} = {$field} + %d",
            $date,
            $license_id,
            $increment,
            $increment
        ));
    }

    /**
     * Send credits reset email
     */
    private function send_credits_reset_email( $license ) {
        $to = $license->email;
        $subject = '✅ Crediti IPV Pro Resettati!';

        $credits_info = $this->get_credits_info( $license );

        $message = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #667eea;'>🔄 Reset Crediti Mensile</h2>

                <p>Ciao,</p>

                <p>I tuoi crediti mensili IPV Pro sono stati resettati con successo!</p>

                <div style='background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                    <h3 style='margin-top: 0; color: #667eea;'>📊 Riepilogo Crediti</h3>
                    <p><strong>Crediti disponibili:</strong> {$license->credits_total}/{$license->credits_total}</p>
                    <p><strong>Piano:</strong> " . ucfirst( $license->variant_slug ) . "</p>
                    <p><strong>Prossimo reset:</strong> {$credits_info['reset_date_formatted']}</p>
                </div>

                <p>Continua a creare contenuti fantastici! 🚀</p>

                <div style='margin-top: 30px;'>
                    <a href='" . home_url( '/my-account/' ) . "' style='background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>Vai al Tuo Account</a>
                </div>

                <div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e5e5; color: #666; font-size: 14px;'>
                    <p>Team IPV Production System<br>
                    <a href='mailto:support@yourdomain.com (configure in settings)'>support@yourdomain.com (configure in settings)</a></p>
                </div>
            </div>
        </body>
        </html>
        ";

        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

        wp_mail( $to, $subject, $message, $headers );
    }

    /**
     * Send low credits warning email
     */
    public function send_low_credits_warning( $license ) {
        $credits_info = $this->get_credits_info( $license );

        // Only send if critical (10% or less)
        if ( $credits_info['status'] !== 'critical' ) {
            return;
        }

        // Check if we already sent warning this month
        $sent_key = 'ipv_low_credits_warning_sent_' . $license->id . '_' . date( 'Y-m' );
        if ( get_transient( $sent_key ) ) {
            return; // Already sent this month
        }

        $to = $license->email;
        $subject = '⚠️ Crediti IPV Pro in Esaurimento';

        $message = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #ff6b6b;'>⚠️ Attenzione: Crediti in Esaurimento</h2>

                <p>Ciao,</p>

                <p>I tuoi crediti IPV Pro stanno per esaurirsi:</p>

                <div style='background: #fff3cd; border: 2px solid #ff6b6b; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                    <h3 style='margin-top: 0; color: #ff6b6b;'>📊 Stato Crediti</h3>
                    <p><strong>Crediti rimanenti:</strong> {$credits_info['credits_remaining']}/{$credits_info['credits_total']} ({$credits_info['percentage']}%)</p>
                    <p><strong>Reset tra:</strong> {$credits_info['days_until_reset']} giorni ({$credits_info['reset_date_formatted']})</p>
                </div>

                <h3 style='color: #667eea;'>💡 Opzioni Disponibili:</h3>
                <ol>
                    <li><strong>Aspetta il reset mensile</strong> tra {$credits_info['days_until_reset']} giorni</li>
                    <li><strong>Upgrade al piano superiore</strong> per più crediti mensili</li>
                </ol>

                <div style='margin-top: 30px;'>
                    <a href='" . home_url( '/ipv-pro/' ) . "' style='background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>Vedi Piani Disponibili</a>
                </div>

                <div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e5e5; color: #666; font-size: 14px;'>
                    <p>Team IPV Production System<br>
                    <a href='mailto:support@yourdomain.com (configure in settings)'>support@yourdomain.com (configure in settings)</a></p>
                </div>
            </div>
        </body>
        </html>
        ";

        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

        if ( wp_mail( $to, $subject, $message, $headers ) ) {
            // Mark as sent for this month
            set_transient( $sent_key, true, MONTH_IN_SECONDS );
        }
    }

    /**
     * Get usage stats for license
     */
    public function get_usage_stats( $license_id, $days = 30 ) {
        global $wpdb;

        $stats = $wpdb->get_results( $wpdb->prepare(
            "SELECT date, transcripts_count, descriptions_count, credits_used, cache_hits
            FROM {$wpdb->prefix}ipv_usage_stats
            WHERE license_id = %d
            AND date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            ORDER BY date DESC",
            $license_id,
            $days
        ), ARRAY_A );

        // Calculate totals
        $totals = [
            'transcripts' => 0,
            'descriptions' => 0,
            'credits_used' => 0,
            'cache_hits' => 0
        ];

        foreach ( $stats as $day ) {
            $totals['transcripts'] += $day['transcripts_count'];
            $totals['descriptions'] += $day['descriptions_count'];
            $totals['credits_used'] += $day['credits_used'];
            $totals['cache_hits'] += $day['cache_hits'];
        }

        // Calculate cache hit rate
        $total_requests = $totals['transcripts'];
        $cache_hit_rate = $total_requests > 0 ? round( ( $totals['cache_hits'] / $total_requests ) * 100 ) : 0;

        return [
            'daily_stats' => $stats,
            'totals' => $totals,
            'cache_hit_rate' => $cache_hit_rate,
            'days' => $days
        ];
    }
}
