<?php
/**
 * IPV Upgrade/Downgrade REST API Endpoints
 *
 * Gestisce API per upgrade e downgrade piani licenza
 *
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Upgrade_Endpoints {

    public function register_routes() {
        // GET /wp-json/ipv-vendor/v1/upgrade/plans
        register_rest_route( 'ipv-vendor/v1', '/upgrade/plans', [
            'methods' => 'GET',
            'callback' => [ $this, 'get_available_plans' ],
            'permission_callback' => [ $this, 'validate_license_request' ]
        ]);

        // GET /wp-json/ipv-vendor/v1/upgrade/preview
        register_rest_route( 'ipv-vendor/v1', '/upgrade/preview', [
            'methods' => 'GET',
            'callback' => [ $this, 'preview_upgrade' ],
            'permission_callback' => [ $this, 'validate_license_request' ]
        ]);

        // POST /wp-json/ipv-vendor/v1/upgrade/execute
        register_rest_route( 'ipv-vendor/v1', '/upgrade/execute', [
            'methods' => 'POST',
            'callback' => [ $this, 'execute_upgrade' ],
            'permission_callback' => [ $this, 'validate_license_request' ]
        ]);

        // GET /wp-json/ipv-vendor/v1/upgrade/checkout-url
        register_rest_route( 'ipv-vendor/v1', '/upgrade/checkout-url', [
            'methods' => 'GET',
            'callback' => [ $this, 'get_checkout_url' ],
            'permission_callback' => [ $this, 'validate_license_request' ]
        ]);

        // GET /wp-json/ipv-vendor/v1/upgrade/history
        register_rest_route( 'ipv-vendor/v1', '/upgrade/history', [
            'methods' => 'GET',
            'callback' => [ $this, 'get_upgrade_history' ],
            'permission_callback' => [ $this, 'validate_license_request' ]
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
        $auth_header = $request->get_header( 'Authorization' );
        if ( $auth_header && preg_match( '/Bearer\s+(.+)$/i', $auth_header, $matches ) ) {
            return sanitize_text_field( $matches[1] );
        }

        $header_key = $request->get_header( 'X-License-Key' );
        if ( $header_key ) {
            return sanitize_text_field( $header_key );
        }

        $query_key = $request->get_param( 'license_key' );
        if ( $query_key ) {
            return sanitize_text_field( $query_key );
        }

        return '';
    }

    /**
     * Get license from request
     */
    private function get_license_from_request( $request ) {
        $license_key = $this->extract_license_key( $request );
        $license_manager = IPV_Vendor_License_Manager::instance();
        return $license_manager->get_by_key( $license_key );
    }

    /**
     * GET /upgrade/plans
     * Get available plans for upgrade/downgrade
     */
    public function get_available_plans( $request ) {
        $license = $this->get_license_from_request( $request );
        $current_plan_slug = $license->variant_slug;

        $plans_manager = IPV_Vendor_Plans_Manager::instance();
        $all_plans = $plans_manager->get_plans();

        // Get current plan details
        $current_plan = $all_plans[ $current_plan_slug ] ?? null;
        $current_credits = $current_plan ? $current_plan['credits'] : (int) $license->credits_total;
        $current_price = $current_plan ? $current_plan['price'] : 0;

        $available_plans = [];

        foreach ( $all_plans as $slug => $plan ) {
            // Skip inactive plans
            if ( empty( $plan['is_active'] ) ) {
                continue;
            }

            // Skip current plan
            if ( $slug === $current_plan_slug ) {
                continue;
            }

            // Trial è SOLO un piano di benvenuto - non può essere selezionato
            // Gli utenti Trial possono solo fare UPGRADE, non esistono downgrade verso Trial
            if ( $slug === 'trial' ) {
                continue;
            }

            // Skip extra credits and golden prompt (not upgrade plans)
            if ( strpos( $slug, 'extra_credits' ) !== false || $slug === 'golden_prompt' ) {
                continue;
            }

            $plan_credits = $plan['credits'];
            $plan_price = $plan['price'];

            // Per utenti Trial: tutti i piani sono upgrade
            // Per altri utenti: confronta crediti/prezzo
            if ( $current_plan_slug === 'trial' ) {
                $type = 'upgrade'; // Dal Trial si può solo salire
            } else {
                $is_upgrade = $plan_credits > $current_credits || $plan_price > $current_price;
                $type = $is_upgrade ? 'upgrade' : 'downgrade';
            }

            // Calculate price difference (pro-rata for upgrades)
            $price_diff = $plan_price - $current_price;
            $credits_diff = $plan_credits - $current_credits;

            // Get WooCommerce product for this plan
            $product_id = $this->get_product_for_plan( $slug );
            $product = $product_id ? wc_get_product( $product_id ) : null;

            $available_plans[] = [
                'slug' => $slug,
                'name' => $plan['name'],
                'credits' => $plan_credits,
                'credits_period' => $plan['credits_period'],
                'price' => $plan_price,
                'price_period' => $plan['price_period'],
                'activations' => $plan['activations'],
                'features' => $plan['features'],
                'description' => $plan['description'] ?? '',
                'type' => $type,
                'price_diff' => $price_diff,
                'credits_diff' => $credits_diff,
                'product_id' => $product_id,
                'product_url' => $product ? $product->get_permalink() : null,
                'checkout_url' => $product ? wc_get_checkout_url() . '?add-to-cart=' . $product_id : null
            ];
        }

        // Sort by credits (ascending)
        usort( $available_plans, fn( $a, $b ) => $a['credits'] - $b['credits'] );

        return rest_ensure_response([
            'success' => true,
            'current_plan' => [
                'slug' => $current_plan_slug,
                'name' => $current_plan['name'] ?? ucfirst( $current_plan_slug ),
                'credits' => $current_credits,
                'credits_remaining' => (int) $license->credits_remaining,
                'price' => $current_price
            ],
            'available_plans' => $available_plans,
            'upgrade_url' => home_url( '/ipv-pro/#pricing' )
        ]);
    }

    /**
     * GET /upgrade/preview
     * Preview upgrade/downgrade effects before executing
     */
    public function preview_upgrade( $request ) {
        $license = $this->get_license_from_request( $request );
        $target_plan_slug = sanitize_text_field( $request->get_param( 'target_plan' ) );

        if ( empty( $target_plan_slug ) ) {
            return new WP_Error(
                'missing_target_plan',
                'target_plan è obbligatorio',
                [ 'status' => 400 ]
            );
        }

        $plans_manager = IPV_Vendor_Plans_Manager::instance();
        $target_plan = $plans_manager->get_plan( $target_plan_slug );

        if ( ! $target_plan ) {
            return new WP_Error(
                'invalid_plan',
                'Piano non trovato',
                [ 'status' => 404 ]
            );
        }

        $current_credits_remaining = (int) $license->credits_remaining;
        $current_credits_total = (int) $license->credits_total;
        $target_credits_total = $target_plan['credits'];

        // Calculate new credits after upgrade
        $credits_diff = $target_credits_total - $current_credits_total;
        $is_upgrade = $credits_diff > 0;

        if ( $is_upgrade ) {
            // Upgrade: add the difference immediately
            $new_credits_remaining = $current_credits_remaining + $credits_diff;
        } else {
            // Downgrade: keep current remaining but cap at new total
            $new_credits_remaining = min( $current_credits_remaining, $target_credits_total );
        }

        // Calculate price difference
        $current_plan = $plans_manager->get_plan( $license->variant_slug );
        $current_price = $current_plan ? $current_plan['price'] : 0;
        $price_diff = $target_plan['price'] - $current_price;

        // Get WooCommerce product
        $product_id = $this->get_product_for_plan( $target_plan_slug );

        return rest_ensure_response([
            'success' => true,
            'preview' => [
                'type' => $is_upgrade ? 'upgrade' : 'downgrade',
                'current_plan' => [
                    'slug' => $license->variant_slug,
                    'name' => $current_plan['name'] ?? ucfirst( $license->variant_slug ),
                    'credits_total' => $current_credits_total,
                    'credits_remaining' => $current_credits_remaining,
                    'price' => $current_price
                ],
                'target_plan' => [
                    'slug' => $target_plan_slug,
                    'name' => $target_plan['name'],
                    'credits_total' => $target_credits_total,
                    'price' => $target_plan['price'],
                    'activations' => $target_plan['activations']
                ],
                'changes' => [
                    'credits_diff' => $credits_diff,
                    'new_credits_remaining' => $new_credits_remaining,
                    'price_diff' => $price_diff,
                    'new_activations' => $target_plan['activations']
                ],
                'requires_payment' => $price_diff > 0,
                'payment_amount' => max( 0, $price_diff ),
                'product_id' => $product_id,
                'checkout_url' => $price_diff > 0 && $product_id ?
                    add_query_arg([
                        'add-to-cart' => $product_id,
                        'ipv_upgrade_license' => $license->license_key
                    ], wc_get_checkout_url()) : null
            ]
        ]);
    }

    /**
     * POST /upgrade/execute
     * Execute upgrade/downgrade (for free downgrades or admin API)
     */
    public function execute_upgrade( $request ) {
        global $wpdb;

        $license = $this->get_license_from_request( $request );
        $target_plan_slug = sanitize_text_field( $request->get_param( 'target_plan' ) );
        $confirm = (bool) $request->get_param( 'confirm' );

        if ( empty( $target_plan_slug ) ) {
            return new WP_Error(
                'missing_target_plan',
                'target_plan è obbligatorio',
                [ 'status' => 400 ]
            );
        }

        if ( ! $confirm ) {
            return new WP_Error(
                'confirmation_required',
                'Conferma richiesta. Invia confirm=true per procedere.',
                [ 'status' => 400 ]
            );
        }

        $plans_manager = IPV_Vendor_Plans_Manager::instance();
        $target_plan = $plans_manager->get_plan( $target_plan_slug );
        $current_plan = $plans_manager->get_plan( $license->variant_slug );

        if ( ! $target_plan ) {
            return new WP_Error(
                'invalid_plan',
                'Piano non trovato',
                [ 'status' => 404 ]
            );
        }

        // Check if this requires payment (only allow free downgrades or admin)
        $current_price = $current_plan ? $current_plan['price'] : 0;
        $price_diff = $target_plan['price'] - $current_price;

        if ( $price_diff > 0 ) {
            // Requires payment - redirect to checkout
            $product_id = $this->get_product_for_plan( $target_plan_slug );
            return rest_ensure_response([
                'success' => false,
                'requires_payment' => true,
                'payment_amount' => $price_diff,
                'checkout_url' => add_query_arg([
                    'add-to-cart' => $product_id,
                    'ipv_upgrade_license' => $license->license_key
                ], wc_get_checkout_url()),
                'message' => 'Upgrade richiede pagamento. Usa il checkout URL per procedere.'
            ]);
        }

        // Execute free downgrade
        $old_plan = $license->variant_slug;
        $old_credits_total = (int) $license->credits_total;
        $current_credits_remaining = (int) $license->credits_remaining;
        $new_credits_total = $target_plan['credits'];

        // Cap remaining credits at new total
        $new_credits_remaining = min( $current_credits_remaining, $new_credits_total );

        // Update license
        $update_data = [
            'variant_slug' => $target_plan_slug,
            'credits_total' => $new_credits_total,
            'credits_monthly' => $new_credits_total,
            'credits_remaining' => $new_credits_remaining,
            'activation_limit' => max( (int) $license->activation_limit, $target_plan['activations'] )
        ];

        $wpdb->update(
            $wpdb->prefix . 'ipv_licenses',
            $update_data,
            [ 'id' => $license->id ],
            [ '%s', '%d', '%d', '%d', '%d' ],
            [ '%d' ]
        );

        // Log the change
        $wpdb->insert(
            $wpdb->prefix . 'ipv_credit_ledger',
            [
                'license_key' => $license->license_key,
                'type' => 'downgrade',
                'amount' => $new_credits_remaining - $current_credits_remaining,
                'balance_after' => $new_credits_remaining,
                'ref_type' => 'plan_change',
                'ref_id' => $target_plan_slug,
                'note' => sprintf( 'Downgrade da %s a %s', $old_plan, $target_plan_slug ),
                'created_at' => current_time( 'mysql' )
            ],
            [ '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s' ]
        );

        // Log
        error_log( sprintf(
            'IPV Vendor: License %s downgraded from %s to %s',
            $license->license_key,
            $old_plan,
            $target_plan_slug
        ));

        return rest_ensure_response([
            'success' => true,
            'message' => sprintf(
                'Piano cambiato da %s a %s',
                $current_plan['name'] ?? $old_plan,
                $target_plan['name']
            ),
            'license' => [
                'key' => $license->license_key,
                'old_plan' => $old_plan,
                'new_plan' => $target_plan_slug,
                'credits_total' => $new_credits_total,
                'credits_remaining' => $new_credits_remaining,
                'activation_limit' => $update_data['activation_limit']
            ]
        ]);
    }

    /**
     * GET /upgrade/checkout-url
     * Get WooCommerce checkout URL for upgrade
     */
    public function get_checkout_url( $request ) {
        $license = $this->get_license_from_request( $request );
        $target_plan_slug = sanitize_text_field( $request->get_param( 'target_plan' ) );

        if ( empty( $target_plan_slug ) ) {
            return new WP_Error(
                'missing_target_plan',
                'target_plan è obbligatorio',
                [ 'status' => 400 ]
            );
        }

        $product_id = $this->get_product_for_plan( $target_plan_slug );

        if ( ! $product_id ) {
            return new WP_Error(
                'product_not_found',
                'Prodotto WooCommerce non trovato per questo piano',
                [ 'status' => 404 ]
            );
        }

        $checkout_url = add_query_arg([
            'add-to-cart' => $product_id,
            'ipv_upgrade_license' => $license->license_key,
            'ipv_upgrade_from' => $license->variant_slug
        ], wc_get_checkout_url());

        return rest_ensure_response([
            'success' => true,
            'checkout_url' => $checkout_url,
            'product_id' => $product_id,
            'target_plan' => $target_plan_slug
        ]);
    }

    /**
     * GET /upgrade/history
     * Get upgrade/downgrade history for a license
     */
    public function get_upgrade_history( $request ) {
        global $wpdb;

        $license = $this->get_license_from_request( $request );

        $history = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_credit_ledger
            WHERE license_key = %s
            AND (type = 'upgrade' OR type = 'downgrade')
            ORDER BY created_at DESC
            LIMIT 20",
            $license->license_key
        ), ARRAY_A );

        return rest_ensure_response([
            'success' => true,
            'history' => $history,
            'current_plan' => $license->variant_slug
        ]);
    }

    /**
     * Get WooCommerce product ID for a plan slug
     */
    private function get_product_for_plan( $plan_slug ) {
        global $wpdb;

        return $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_ipv_plan_slug' AND meta_value = %s
            LIMIT 1",
            $plan_slug
        ) );
    }
}

// Register routes
add_action( 'rest_api_init', function() {
    $endpoints = new IPV_Vendor_Upgrade_Endpoints();
    $endpoints->register_routes();
} );
