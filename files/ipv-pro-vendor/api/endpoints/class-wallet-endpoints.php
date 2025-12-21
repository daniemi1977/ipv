<?php
/**
 * IPV Wallet REST API Endpoints
 *
 * Gestisce API per portafoglio crediti e sincronizzazione client
 *
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Wallet_Endpoints {

    public function register_routes() {
        // GET /wp-json/ipv-vendor/v1/wallet/balance
        register_rest_route( 'ipv-vendor/v1', '/wallet/balance', [
            'methods' => 'GET',
            'callback' => [ $this, 'get_wallet_balance' ],
            'permission_callback' => [ $this, 'validate_license_request' ]
        ]);

        // GET /wp-json/ipv-vendor/v1/wallet/ledger
        register_rest_route( 'ipv-vendor/v1', '/wallet/ledger', [
            'methods' => 'GET',
            'callback' => [ $this, 'get_wallet_ledger' ],
            'permission_callback' => [ $this, 'validate_license_request' ]
        ]);

        // GET /wp-json/ipv-vendor/v1/wallet/stats
        register_rest_route( 'ipv-vendor/v1', '/wallet/stats', [
            'methods' => 'GET',
            'callback' => [ $this, 'get_usage_stats' ],
            'permission_callback' => [ $this, 'validate_license_request' ]
        ]);

        // POST /wp-json/ipv-vendor/v1/wallet/sync
        register_rest_route( 'ipv-vendor/v1', '/wallet/sync', [
            'methods' => 'POST',
            'callback' => [ $this, 'sync_client_data' ],
            'permission_callback' => [ $this, 'validate_license_request' ]
        ]);

        // GET /wp-json/ipv-vendor/v1/license/find-by-email
        register_rest_route( 'ipv-vendor/v1', '/license/find-by-email', [
            'methods' => 'GET',
            'callback' => [ $this, 'find_license_by_email' ],
            'permission_callback' => '__return_true' // Public endpoint
        ]);

        // GET /wp-json/ipv-vendor/v1/products/credits
        register_rest_route( 'ipv-vendor/v1', '/products/credits', [
            'methods' => 'GET',
            'callback' => [ $this, 'get_credit_products' ],
            'permission_callback' => '__return_true' // Public endpoint
        ]);
    }

    /**
     * Validate license request
     */
    public function validate_license_request( $request ) {
        $license_key = $this->extract_license_key( $request );

        if ( empty( $license_key ) ) {
            return new WP_Error(
                'missing_license',
                'license_key è obbligatorio',
                [ 'status' => 401 ]
            );
        }

        $license_manager = IPV_Vendor_License_Manager::instance();
        $license = $license_manager->get_by_key( $license_key );

        if ( ! $license ) {
            return new WP_Error(
                'invalid_license',
                'License key non valida',
                [ 'status' => 401 ]
            );
        }

        return true;
    }

    /**
     * Extract license key from request
     */
    private function extract_license_key( $request ) {
        // Check Authorization header
        $auth_header = $request->get_header( 'Authorization' );
        if ( $auth_header && preg_match( '/Bearer\s+(.+)$/i', $auth_header, $matches ) ) {
            return sanitize_text_field( $matches[1] );
        }

        // Check X-License-Key header
        $header_key = $request->get_header( 'X-License-Key' );
        if ( $header_key ) {
            return sanitize_text_field( $header_key );
        }

        // Check query parameter
        $query_key = $request->get_param( 'license_key' );
        if ( $query_key ) {
            return sanitize_text_field( $query_key );
        }

        return '';
    }

    /**
     * GET /wallet/balance
     * Get current wallet balance for a license
     */
    public function get_wallet_balance( $request ) {
        $license_key = $this->extract_license_key( $request );

        $license_manager = IPV_Vendor_License_Manager::instance();
        $credits_manager = IPV_Vendor_Credits_Manager::instance();

        $license = $license_manager->get_by_key( $license_key );
        $credits_info = $credits_manager->get_credits_info( $license );

        return rest_ensure_response([
            'success' => true,
            'wallet' => [
                'credits_remaining' => $credits_info['credits_remaining'],
                'credits_total' => $credits_info['credits_total'],
                'credits_used' => $credits_info['credits_used'],
                'percentage' => $credits_info['percentage'],
                'status' => $credits_info['status'],
                'reset_date' => $credits_info['reset_date'],
                'reset_date_formatted' => $credits_info['reset_date_formatted'],
                'days_until_reset' => $credits_info['days_until_reset']
            ],
            'license' => [
                'key' => $license->license_key,
                'status' => $license->status,
                'variant' => $license->variant_slug,
                'email' => $license->email,
                'expires_at' => $license->expires_at,
                'activation_count' => (int) $license->activation_count,
                'activation_limit' => (int) $license->activation_limit
            ],
            'server_url' => home_url(),
            'portal_url' => wc_get_account_endpoint_url( 'ipv-wallet' )
        ]);
    }

    /**
     * GET /wallet/ledger
     * Get transaction history for a license
     */
    public function get_wallet_ledger( $request ) {
        global $wpdb;

        $license_key = $this->extract_license_key( $request );
        $limit = (int) $request->get_param( 'limit' ) ?: 50;
        $offset = (int) $request->get_param( 'offset' ) ?: 0;

        $limit = min( 100, max( 1, $limit ) );
        $offset = max( 0, $offset );

        $ledger = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_credit_ledger
            WHERE license_key = %s
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d",
            $license_key,
            $limit,
            $offset
        ), ARRAY_A );

        $total = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_credit_ledger WHERE license_key = %s",
            $license_key
        ) );

        return rest_ensure_response([
            'success' => true,
            'ledger' => $ledger,
            'pagination' => [
                'total' => (int) $total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ( $offset + $limit ) < $total
            ]
        ]);
    }

    /**
     * GET /wallet/stats
     * Get usage statistics for a license
     */
    public function get_usage_stats( $request ) {
        $license_key = $this->extract_license_key( $request );
        $days = (int) $request->get_param( 'days' ) ?: 30;
        $days = min( 90, max( 7, $days ) );

        $license_manager = IPV_Vendor_License_Manager::instance();
        $credits_manager = IPV_Vendor_Credits_Manager::instance();

        $license = $license_manager->get_by_key( $license_key );
        $stats = $credits_manager->get_usage_stats( $license->id, $days );

        return rest_ensure_response([
            'success' => true,
            'stats' => $stats,
            'period' => [
                'days' => $days,
                'from' => date( 'Y-m-d', strtotime( "-{$days} days" ) ),
                'to' => date( 'Y-m-d' )
            ]
        ]);
    }

    /**
     * POST /wallet/sync
     * Sync client data with vendor (heartbeat)
     */
    public function sync_client_data( $request ) {
        global $wpdb;

        $license_key = $this->extract_license_key( $request );
        $site_url = sanitize_text_field( $request->get_param( 'site_url' ) ?: '' );
        $client_version = sanitize_text_field( $request->get_param( 'client_version' ) ?: '' );
        $wp_version = sanitize_text_field( $request->get_param( 'wp_version' ) ?: '' );
        $php_version = sanitize_text_field( $request->get_param( 'php_version' ) ?: '' );

        $license_manager = IPV_Vendor_License_Manager::instance();
        $credits_manager = IPV_Vendor_Credits_Manager::instance();

        $license = $license_manager->get_by_key( $license_key );
        $credits_info = $credits_manager->get_credits_info( $license );

        // Update activation last_checked_at
        if ( $site_url ) {
            $wpdb->update(
                $wpdb->prefix . 'ipv_activations',
                [
                    'last_checked_at' => current_time( 'mysql' ),
                    'client_version' => $client_version,
                    'wp_version' => $wp_version,
                    'php_version' => $php_version
                ],
                [
                    'license_id' => $license->id,
                    'site_url' => $site_url
                ],
                [ '%s', '%s', '%s', '%s' ],
                [ '%d', '%s' ]
            );
        }

        // Check for updates (compare with latest version)
        $latest_version = get_option( 'ipv_client_latest_version', '9.2.2' );
        $update_available = version_compare( $client_version, $latest_version, '<' );

        return rest_ensure_response([
            'success' => true,
            'wallet' => [
                'credits_remaining' => $credits_info['credits_remaining'],
                'credits_total' => $credits_info['credits_total'],
                'status' => $credits_info['status'],
                'days_until_reset' => $credits_info['days_until_reset']
            ],
            'license' => [
                'status' => $license->status,
                'variant' => $license->variant_slug
            ],
            'update' => [
                'available' => $update_available,
                'latest_version' => $latest_version,
                'download_url' => $update_available ? home_url( '/ipv-pro/#downloads' ) : null
            ],
            'alerts' => $this->get_license_alerts( $license, $credits_info ),
            'synced_at' => current_time( 'mysql' )
        ]);
    }

    /**
     * Get alerts for a license
     */
    private function get_license_alerts( $license, $credits_info ) {
        $alerts = [];

        // Low credits alert
        if ( $credits_info['status'] === 'critical' ) {
            $alerts[] = [
                'type' => 'warning',
                'code' => 'low_credits',
                'message' => sprintf(
                    'Hai solo %d crediti rimanenti! Reset tra %d giorni.',
                    $credits_info['credits_remaining'],
                    $credits_info['days_until_reset']
                ),
                'action_url' => home_url( '/ipv-pro/#pricing' ),
                'action_label' => 'Acquista Crediti'
            ];
        }

        // Depleted credits alert
        if ( $credits_info['status'] === 'depleted' ) {
            $alerts[] = [
                'type' => 'error',
                'code' => 'credits_depleted',
                'message' => sprintf(
                    'Crediti esauriti! Reset tra %d giorni.',
                    $credits_info['days_until_reset']
                ),
                'action_url' => home_url( '/ipv-pro/#pricing' ),
                'action_label' => 'Acquista Crediti Extra'
            ];
        }

        // Expiring license alert
        if ( $license->expires_at ) {
            $days_until_expiry = ( strtotime( $license->expires_at ) - time() ) / DAY_IN_SECONDS;
            if ( $days_until_expiry <= 7 && $days_until_expiry > 0 ) {
                $alerts[] = [
                    'type' => 'warning',
                    'code' => 'license_expiring',
                    'message' => sprintf(
                        'La tua licenza scade tra %d giorni!',
                        ceil( $days_until_expiry )
                    ),
                    'action_url' => wc_get_account_endpoint_url( 'ipv-licenses' ),
                    'action_label' => 'Rinnova Licenza'
                ];
            }
        }

        return $alerts;
    }

    /**
     * GET /license/find-by-email
     * Find licenses associated with an email
     */
    public function find_license_by_email( $request ) {
        global $wpdb;

        $email = sanitize_email( $request->get_param( 'email' ) );

        if ( empty( $email ) || ! is_email( $email ) ) {
            return new WP_Error(
                'invalid_email',
                'Email non valida',
                [ 'status' => 400 ]
            );
        }

        // Rate limit by IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rate_key = 'ipv_find_email_' . md5( $ip );
        $rate_count = (int) get_transient( $rate_key );

        if ( $rate_count >= 10 ) {
            return new WP_Error(
                'rate_limited',
                'Troppe richieste. Riprova tra un\'ora.',
                [ 'status' => 429 ]
            );
        }

        set_transient( $rate_key, $rate_count + 1, HOUR_IN_SECONDS );

        // Find licenses
        $licenses = $wpdb->get_results( $wpdb->prepare(
            "SELECT license_key, status, variant_slug, created_at
            FROM {$wpdb->prefix}ipv_licenses
            WHERE email = %s
            ORDER BY created_at DESC",
            $email
        ), ARRAY_A );

        if ( empty( $licenses ) ) {
            return rest_ensure_response([
                'success' => true,
                'found' => false,
                'message' => 'Nessuna licenza trovata per questa email.'
            ]);
        }

        // Mask license keys for security
        $masked_licenses = array_map( function( $license ) {
            return [
                'key_masked' => substr( $license['license_key'], 0, 4 ) . '****' . substr( $license['license_key'], -4 ),
                'status' => $license['status'],
                'variant' => $license['variant_slug'],
                'created_at' => $license['created_at']
            ];
        }, $licenses );

        return rest_ensure_response([
            'success' => true,
            'found' => true,
            'count' => count( $licenses ),
            'licenses' => $masked_licenses,
            'message' => 'Licenze trovate. Controlla la tua email per la license key completa.'
        ]);
    }

    /**
     * GET /products/credits
     * Get available credit products for purchase
     */
    public function get_credit_products( $request ) {
        $products = [];

        // Query WooCommerce products with IPV credits
        $args = [
            'post_type' => 'product',
            'posts_per_page' => 10,
            'meta_query' => [
                [
                    'key' => '_ipv_product_type',
                    'value' => 'extra_credits',
                    'compare' => '='
                ]
            ]
        ];

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $product = wc_get_product( get_the_ID() );

                if ( $product && $product->is_purchasable() ) {
                    $credits = get_post_meta( get_the_ID(), '_ipv_credits_amount', true ) ?: 0;

                    $products[] = [
                        'id' => $product->get_id(),
                        'name' => $product->get_name(),
                        'credits' => (int) $credits,
                        'price' => $product->get_price(),
                        'price_formatted' => $product->get_price_html(),
                        'url' => $product->get_permalink(),
                        'add_to_cart_url' => $product->add_to_cart_url()
                    ];
                }
            }
            wp_reset_postdata();
        }

        // Sort by credits amount
        usort( $products, fn( $a, $b ) => $a['credits'] - $b['credits'] );

        return rest_ensure_response([
            'success' => true,
            'products' => $products,
            'shop_url' => home_url( '/ipv-pro/#pricing' ),
            'wallet_url' => wc_get_account_endpoint_url( 'ipv-wallet' )
        ]);
    }
}

// Register routes
add_action( 'rest_api_init', function() {
    $endpoints = new IPV_Vendor_Wallet_Endpoints();
    $endpoints->register_routes();
} );
