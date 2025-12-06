<?php
/**
 * IPV License REST API Endpoints
 *
 * Gestisce attivazione, deattivazione, validazione licenze via REST API
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_License_Endpoints {

    public function register_routes() {
        // POST /wp-json/ipv-vendor/v1/license/activate
        register_rest_route( 'ipv-vendor/v1', '/license/activate', [
            'methods' => 'POST',
            'callback' => [ $this, 'activate_license' ],
            'permission_callback' => '__return_true'
        ]);

        // POST /wp-json/ipv-vendor/v1/license/deactivate
        register_rest_route( 'ipv-vendor/v1', '/license/deactivate', [
            'methods' => 'POST',
            'callback' => [ $this, 'deactivate_license' ],
            'permission_callback' => '__return_true'
        ]);

        // POST /wp-json/ipv-vendor/v1/license/validate
        register_rest_route( 'ipv-vendor/v1', '/license/validate', [
            'methods' => 'POST',
            'callback' => [ $this, 'validate_license' ],
            'permission_callback' => '__return_true'
        ]);

        // GET /wp-json/ipv-vendor/v1/license/info
        register_rest_route( 'ipv-vendor/v1', '/license/info', [
            'methods' => 'GET',
            'callback' => [ $this, 'get_license_info' ],
            'permission_callback' => '__return_true'
        ]);
    }

    /**
     * POST /license/activate
     * Activate license on a specific site
     */
    public function activate_license( $request ) {
        $license_key = $request->get_param( 'license_key' );
        $site_url = $request->get_param( 'site_url' );
        $site_name = $request->get_param( 'site_name' ) ?: '';

        if ( empty( $license_key ) || empty( $site_url ) ) {
            return new WP_Error(
                'missing_params',
                'license_key e site_url sono obbligatori',
                [ 'status' => 400 ]
            );
        }

        $license_manager = IPV_Vendor_License_Manager::instance();
        $credits_manager = IPV_Vendor_Credits_Manager::instance();

        $license = $license_manager->activate_license(
            $license_key,
            $site_url,
            $site_name,
            $_SERVER['REMOTE_ADDR'] ?? ''
        );

        if ( is_wp_error( $license ) ) {
            return $license;
        }

        $credits_info = $credits_manager->get_credits_info( $license );

        return rest_ensure_response([
            'success' => true,
            'message' => 'License attivata con successo',
            'license' => [
                'key' => $license->license_key,
                'status' => $license->status,
                'variant' => $license->variant_slug,
                'email' => $license->email,
                'expires_at' => $license->expires_at,
                'activation_limit' => (int) $license->activation_limit,
                'activation_count' => (int) $license->activation_count,
                'credits' => $credits_info
            ]
        ]);
    }

    /**
     * POST /license/deactivate
     * Deactivate license from a site
     */
    public function deactivate_license( $request ) {
        $license_key = $request->get_param( 'license_key' );
        $site_url = $request->get_param( 'site_url' );

        if ( empty( $license_key ) || empty( $site_url ) ) {
            return new WP_Error(
                'missing_params',
                'license_key e site_url sono obbligatori',
                [ 'status' => 400 ]
            );
        }

        $license_manager = IPV_Vendor_License_Manager::instance();
        $result = $license_manager->deactivate_license( $license_key, $site_url );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'License deattivata con successo'
        ]);
    }

    /**
     * POST /license/validate
     * Validate a license key
     */
    public function validate_license( $request ) {
        $license_key = $request->get_param( 'license_key' );

        if ( empty( $license_key ) ) {
            return new WP_Error(
                'missing_params',
                'license_key è obbligatorio',
                [ 'status' => 400 ]
            );
        }

        $license_manager = IPV_Vendor_License_Manager::instance();
        $credits_manager = IPV_Vendor_Credits_Manager::instance();

        $license = $license_manager->validate_license( $license_key );

        if ( is_wp_error( $license ) ) {
            return $license;
        }

        $credits_info = $credits_manager->get_credits_info( $license );

        return rest_ensure_response([
            'success' => true,
            'valid' => true,
            'license' => [
                'key' => $license->license_key,
                'status' => $license->status,
                'variant' => $license->variant_slug,
                'credits' => $credits_info
            ]
        ]);
    }

    /**
     * GET /license/info?license_key=XXX
     * Get full license information
     */
    public function get_license_info( $request ) {
        $license_key = $request->get_param( 'license_key' );

        if ( empty( $license_key ) ) {
            return new WP_Error(
                'missing_params',
                'license_key è obbligatorio',
                [ 'status' => 400 ]
            );
        }

        $license_manager = IPV_Vendor_License_Manager::instance();
        $credits_manager = IPV_Vendor_Credits_Manager::instance();

        $license = $license_manager->get_license_by_key( $license_key );

        if ( ! $license ) {
            return new WP_Error(
                'invalid_license',
                'License non trovata',
                [ 'status' => 404 ]
            );
        }

        // Get activations
        global $wpdb;
        $activations = $wpdb->get_results( $wpdb->prepare(
            "SELECT site_url, site_name, activated_at, last_checked_at, is_active
            FROM {$wpdb->prefix}ipv_activations
            WHERE license_id = %d
            ORDER BY activated_at DESC",
            $license->id
        ), ARRAY_A );

        $credits_info = $credits_manager->get_credits_info( $license );
        $usage_stats = $credits_manager->get_usage_stats( $license->id, 30 );

        return rest_ensure_response([
            'success' => true,
            'license' => [
                'key' => $license->license_key,
                'status' => $license->status,
                'variant' => $license->variant_slug,
                'email' => $license->email,
                'created_at' => $license->created_at,
                'expires_at' => $license->expires_at,
                'activation_limit' => (int) $license->activation_limit,
                'activation_count' => (int) $license->activation_count,
                'credits' => $credits_info,
                'activations' => $activations,
                'usage_stats' => $usage_stats
            ]
        ]);
    }
}
