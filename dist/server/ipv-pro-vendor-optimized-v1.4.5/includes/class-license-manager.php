<?php
/**
 * IPV License Manager
 *
 * Handles license generation, validation, activation, and deactivation
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_License_Manager {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Generate unique license key
     */
    public function generate_license_key() {
        $key = strtoupper( sprintf(
            '%s-%s-%s-%s',
            $this->random_string( 4 ),
            $this->random_string( 4 ),
            $this->random_string( 4 ),
            $this->random_string( 4 )
        ));

        // Ensure unique
        global $wpdb;
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ipv_licenses WHERE license_key = %s",
            $key
        ));

        return $exists ? $this->generate_license_key() : $key;
    }

    private function random_string( $length = 4 ) {
        // No I, O per evitare confusione con 1, 0
        $characters = '0123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $string = '';
        for ( $i = 0; $i < $length; $i++ ) {
            $string .= $characters[ random_int( 0, strlen( $characters ) - 1 ) ];
        }
        return $string;
    }

    /**
     * Create license from WooCommerce order
     */
    public function create_license_from_order( $order_id, $product_id ) {
        global $wpdb;

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            error_log( 'IPV Vendor: Order not found: ' . $order_id );
            return false;
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            error_log( 'IPV Vendor: Product not found: ' . $product_id );
            return false;
        }

        // Get plan details from product meta (with PHP 8.x safe type casting)
        $variant_slug_raw = get_post_meta( $product_id, '_ipv_plan_slug', true );
        $variant_slug = ( ! empty( $variant_slug_raw ) ) ? (string) $variant_slug_raw : 'trial';

        $credits_raw = get_post_meta( $product_id, '_ipv_credits_total', true );
        $credits_total = ( $credits_raw !== '' && $credits_raw !== null ) ? (int) $credits_raw : 10;

        $activation_raw = get_post_meta( $product_id, '_ipv_activation_limit', true );
        $activation_limit = ( $activation_raw !== '' && $activation_raw !== null ) ? (int) $activation_raw : 1;

        // Calculate expiry for one-time purchases
        $expires_at = null;
        if ( ! $product->is_type( 'subscription' ) ) {
            // One-time = 1 year
            $expires_at = date( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
        }

        // Generate license
        $license_key = $this->generate_license_key();

        // Calculate next reset date (first day of next month)
        $credits_reset_date = date( 'Y-m-01', strtotime( '+1 month' ) );

        // Insert license
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'ipv_licenses',
            [
                'license_key' => $license_key,
                'order_id' => $order_id,
                'product_id' => $product_id,
                'user_id' => $order->get_user_id(),
                'email' => $order->get_billing_email(),
                'status' => 'active',
                'variant_slug' => $variant_slug,
                'credits_total' => $credits_total,
                'credits_remaining' => $credits_total,
                'credits_reset_date' => $credits_reset_date,
                'activation_limit' => $activation_limit,
                'expires_at' => $expires_at
            ],
            [ '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%s' ]
        );

        if ( ! $inserted ) {
            error_log( 'IPV Vendor: Failed to create license for order ' . $order_id );
            return false;
        }

        $license_id = $wpdb->insert_id;

        // Store license key in order meta
        $order->update_meta_data( '_ipv_license_key', $license_key );
        $order->update_meta_data( '_ipv_license_id', $license_id );
        $order->save();

        // Send email with license
        $this->send_license_email( $order, $license_key );

        error_log( sprintf(
            'IPV Vendor: License %s created for order %d (plan: %s, credits: %d)',
            $license_key,
            $order_id,
            $variant_slug,
            $credits_total
        ));

        return $license_key;
    }

    /**
     * Validate license
     */
    public function validate_license( $license_key, $site_url = '' ) {
        global $wpdb;

        // v1.3.4 - Enhanced debug logging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '=== LICENSE VALIDATION START ===' );
            error_log( 'License Key: ' . $license_key );
            error_log( 'Site URL: ' . $site_url );
        }

        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE license_key = %s",
            $license_key
        ));

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'License found in DB: ' . ( $license ? 'YES' : 'NO' ) );
            if ( $license ) {
                error_log( 'License status: ' . $license->status );
                error_log( 'License expires_at: ' . ( $license->expires_at ?? 'NULL' ) );
                error_log( 'License activation_limit: ' . ( $license->activation_limit ?? 'NULL' ) );
            }
        }

        if ( ! $license ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '❌ VALIDATION FAILED: License key not found in database' );
            }
            return new WP_Error( 'invalid_license', 'License key non valida' );
        }

        // Check status
        if ( 'active' !== $license->status ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '❌ VALIDATION FAILED: License status is not active: ' . $license->status );
            }
            return new WP_Error( 'inactive_license', 'License non attiva. Status: ' . $license->status );
        }

        // Check expiry
        if ( $license->expires_at && strtotime( $license->expires_at ) < time() ) {
            // Update status
            $wpdb->update(
                $wpdb->prefix . 'ipv_licenses',
                [ 'status' => 'expired' ],
                [ 'id' => $license->id ],
                [ '%s' ],
                [ '%d' ]
            );
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '❌ VALIDATION FAILED: License expired on ' . $license->expires_at );
            }
            return new WP_Error( 'expired_license', 'License scaduta il ' . date_i18n( 'd/m/Y', strtotime( $license->expires_at ) ) );
        }

        // v1.3.4 - SKIP activation check if site_url is empty (for API calls)
        // This allows the license to work even without explicit activation
        if ( empty( $site_url ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '✅ VALIDATION SUCCESS: License valid (no site_url check)' );
            }
            return $license;
        }

        // Check activation limit if site_url provided
        if ( ! empty( $site_url ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Checking activation for site_url: ' . $site_url );
            }

            $activation = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ipv_activations
                WHERE license_id = %d AND site_url = %s AND is_active = 1",
                $license->id,
                $site_url
            ));

            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Activation found: ' . ( $activation ? 'YES' : 'NO' ) );
            }

            if ( ! $activation ) {
                // Check if can activate
                $active_count = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_activations
                    WHERE license_id = %d AND is_active = 1",
                    $license->id
                ));

                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'Active count: ' . $active_count . ' / ' . $license->activation_limit );
                }

                if ( $active_count >= $license->activation_limit ) {
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( '❌ VALIDATION FAILED: Activation limit reached' );
                    }
                    return new WP_Error(
                        'activation_limit_reached',
                        sprintf(
                            'Limite attivazioni raggiunto (%d/%d). Disattiva un sito esistente.',
                            $active_count,
                            $license->activation_limit
                        )
                    );
                }
            }
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '✅ VALIDATION SUCCESS: License fully valid' );
        }

        return $license;
    }

    /**
     * Activate license on site
     */
    public function activate_license( $license_key, $site_url, $site_name = '', $ip_address = '' ) {
        global $wpdb;

        // Validate license
        $license = $this->validate_license( $license_key, $site_url );
        if ( is_wp_error( $license ) ) {
            return $license;
        }

        // Check if already activated
        $existing = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_activations
            WHERE license_id = %d AND site_url = %s",
            $license->id,
            $site_url
        ));

        if ( $existing ) {
            // Reactivate if inactive
            if ( ! $existing->is_active ) {
                $wpdb->update(
                    $wpdb->prefix . 'ipv_activations',
                    [
                        'is_active' => 1,
                        'last_checked_at' => current_time( 'mysql' )
                    ],
                    [ 'id' => $existing->id ],
                    [ '%d', '%s' ],
                    [ '%d' ]
                );

                error_log( sprintf(
                    'IPV Vendor: License %s reactivated for %s',
                    $license_key,
                    $site_url
                ));
            }
            return $license;
        }

        // Create activation
        $wpdb->insert(
            $wpdb->prefix . 'ipv_activations',
            [
                'license_id' => $license->id,
                'site_url' => $site_url,
                'site_name' => $site_name,
                'ip_address' => $ip_address
            ],
            [ '%d', '%s', '%s', '%s' ]
        );

        // Update activation count
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}ipv_licenses
            SET activation_count = (
                SELECT COUNT(*) FROM {$wpdb->prefix}ipv_activations
                WHERE license_id = %d AND is_active = 1
            )
            WHERE id = %d",
            $license->id,
            $license->id
        ));

        error_log( sprintf(
            'IPV Vendor: License %s activated for %s',
            $license_key,
            $site_url
        ));

        return $license;
    }

    /**
     * Deactivate license from site
     */
    public function deactivate_license( $license_key, $site_url ) {
        global $wpdb;

        $license = $this->get_license_by_key( $license_key );
        if ( ! $license ) {
            return new WP_Error( 'invalid_license', 'License key non valida' );
        }

        $wpdb->update(
            $wpdb->prefix . 'ipv_activations',
            [ 'is_active' => 0 ],
            [
                'license_id' => $license->id,
                'site_url' => $site_url
            ],
            [ '%d' ],
            [ '%d', '%s' ]
        );

        // Update activation count
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}ipv_licenses
            SET activation_count = (
                SELECT COUNT(*) FROM {$wpdb->prefix}ipv_activations
                WHERE license_id = %d AND is_active = 1
            )
            WHERE id = %d",
            $license->id,
            $license->id
        ));

        error_log( sprintf(
            'IPV Vendor: License %s deactivated from %s',
            $license_key,
            $site_url
        ));

        return true;
    }

    /**
     * Get license by key
     */
    public function get_license_by_key( $license_key ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE license_key = %s",
            $license_key
        ));
    }

    /**
     * Send license email
     */
    private function send_license_email( $order, $license_key ) {
        $to = $order->get_billing_email();
        $subject = '🔑 La tua License Key per IPV Production System Pro';

        $items = $order->get_items();
        $first_item = reset( $items );
        $product = $first_item->get_product();
        $plan_name = $product->get_name();

        $download_url = wp_nonce_url(
            home_url( '/?download-ipv-pro=1&license=' . $license_key ),
            'download_ipv_pro'
        );

        $message = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #667eea;'>🎉 Grazie per aver acquistato IPV Pro!</h2>

                <p>Ciao {$order->get_billing_first_name()},</p>

                <p>Il tuo ordine è stato completato con successo. Ecco la tua <strong>License Key</strong>:</p>

                <div style='background: #f5f5f5; padding: 20px; margin: 20px 0; text-align: center; border-radius: 5px;'>
                    <h1 style='color: #667eea; font-size: 24px; letter-spacing: 2px; margin: 0;'>{$license_key}</h1>
                </div>

                <p><strong>Piano:</strong> {$plan_name}</p>

                <div style='margin: 30px 0;'>
                    <a href='{$download_url}' style='background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>📥 Scarica Plugin</a>
                </div>

                <h3 style='color: #667eea;'>🚀 Installazione Rapida (5 minuti):</h3>
                <ol style='line-height: 2;'>
                    <li>Scarica il plugin dal link sopra</li>
                    <li>WordPress → Plugin → Aggiungi nuovo → Carica</li>
                    <li>Attiva plugin</li>
                    <li>Video IPV → 🔑 Licenza</li>
                    <li>Inserisci la License Key: <code style='background: #f5f5f5; padding: 2px 6px;'>{$license_key}</code></li>
                    <li>Inizia a importare video!</li>
                </ol>

                <h3 style='color: #667eea;'>📖 Risorse Utili:</h3>
                <ul>
                    <li><a href='" . home_url( '/docs/ipv-pro/' ) . "'>Documentazione Completa</a></li>
                    <li><a href='" . home_url( '/docs/ipv-pro/quick-start/' ) . "'>Quick Start Guide</a></li>
                    <li><a href='" . home_url( '/my-account/' ) . "'>Il Mio Account</a></li>
                </ul>

                <h3 style='color: #667eea;'>💬 Hai Bisogno di Aiuto?</h3>
                <p>Contatta il supporto per assistenza.</p>

                <div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e5e5; color: #666; font-size: 14px;'>
                    <p>Buon lavoro!<br>
                    <strong>Team IPV Production System</strong></p>
                </div>
            </div>
        </body>
        </html>
        ";

        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

        wp_mail( $to, $subject, $message, $headers );
    }
}
