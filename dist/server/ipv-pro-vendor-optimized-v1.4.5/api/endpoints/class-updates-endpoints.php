<?php
/**
 * IPV Updates REST API Endpoints
 *
 * Gestisce informazioni versioni per remote updates
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Updates_Endpoints {

    public function register_routes() {
        // GET /wp-json/ipv-vendor/v1/plugin-info
        register_rest_route( 'ipv-vendor/v1', '/plugin-info', [
            'methods' => 'GET',
            'callback' => [ $this, 'get_plugin_info' ],
            'permission_callback' => '__return_true'
        ]);

        // POST /wp-json/ipv-vendor/v1/check-update
        register_rest_route( 'ipv-vendor/v1', '/check-update', [
            'methods' => 'POST',
            'callback' => [ $this, 'check_update' ],
            'permission_callback' => '__return_true'
        ]);
    }

    /**
     * GET /plugin-info
     * Return current plugin version info (for WordPress updater)
     */
    public function get_plugin_info( $request ) {
        $updates_server = IPV_Vendor_Remote_Updates_Server::instance();
        $current_version = $updates_server->get_current_version_info();

        if ( ! $current_version ) {
            return new WP_Error(
                'no_version',
                'Nessuna versione disponibile',
                [ 'status' => 404 ]
            );
        }

        // WordPress expects this format for updates
        return rest_ensure_response([
            'name' => 'IPV Production System Pro',
            'slug' => 'ipv-production-system-pro',
            'version' => $current_version['version'],
            'author' => 'IPV Team',
            'author_profile' => 'https://ipv-production-system.com',
            'requires' => '6.0',
            'tested' => '6.4',
            'requires_php' => '8.0',
            'download_url' => $current_version['download_url'],
            'last_updated' => $current_version['timestamp'],
            'sections' => [
                'description' => 'Sistema professionale per automatizzare la produzione di video YouTube',
                'changelog' => $current_version['changelog'] ?: 'Nessuna nota di rilascio'
            ],
            'banners' => [],
            'icons' => []
        ]);
    }

    /**
     * POST /check-update
     * Check if update is available for client
     */
    public function check_update( $request ) {
        $current_client_version = $request->get_param( 'version' );
        $license_key = $request->get_param( 'license_key' );

        if ( empty( $license_key ) ) {
            return new WP_Error(
                'missing_license',
                'license_key è obbligatorio',
                [ 'status' => 401 ]
            );
        }

        // Validate license
        $license_manager = IPV_Vendor_License_Manager::instance();
        $license = $license_manager->validate_license( $license_key );

        if ( is_wp_error( $license ) ) {
            return $license;
        }

        $updates_server = IPV_Vendor_Remote_Updates_Server::instance();
        $latest_version = $updates_server->get_current_version_info();

        if ( ! $latest_version ) {
            return rest_ensure_response([
                'update_available' => false,
                'message' => 'Nessuna versione disponibile sul server'
            ]);
        }

        // Check if update available
        $update_available = version_compare(
            $latest_version['version'],
            $current_client_version,
            '>'
        );

        if ( ! $update_available ) {
            return rest_ensure_response([
                'update_available' => false,
                'current_version' => $current_client_version,
                'latest_version' => $latest_version['version'],
                'message' => 'Plugin aggiornato all\'ultima versione'
            ]);
        }

        // Update available!
        $download_url = wp_nonce_url(
            home_url( '/?download-ipv-pro=1&license=' . $license_key . '&version=' . $latest_version['version'] ),
            'download_ipv_pro'
        );

        return rest_ensure_response([
            'update_available' => true,
            'current_version' => $current_client_version,
            'latest_version' => $latest_version['version'],
            'download_url' => $download_url,
            'changelog' => $latest_version['changelog'] ?: '',
            'package_size' => $latest_version['size'],
            'release_date' => $latest_version['date'],
            'message' => sprintf(
                'Nuova versione disponibile: %s (attuale: %s)',
                $latest_version['version'],
                $current_client_version
            )
        ]);
    }
}
