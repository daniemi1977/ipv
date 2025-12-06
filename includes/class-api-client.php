<?php
/**
 * IPV API Client
 *
 * Client per chiamare l'API Gateway su bissolomarket.com
 * ZERO API KEYS hardcoded - tutto remotizzato!
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Prod_API_Client {

    private static $instance = null;

    // Server URL (modifica questo per il tuo server)
    const API_SERVER = 'https://bissolomarket.com';
    const API_NAMESPACE = 'ipv-vendor/v1';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get stored license key
     */
    private function get_license_key() {
        return get_option( 'ipv_license_key', '' );
    }

    /**
     * Get API endpoint URL
     */
    private function get_endpoint_url( $endpoint ) {
        return trailingslashit( self::API_SERVER ) . 'wp-json/' . self::API_NAMESPACE . '/' . ltrim( $endpoint, '/' );
    }

    /**
     * Make API request
     */
    private function request( $endpoint, $method = 'GET', $body = [], $timeout = 60 ) {
        $license_key = $this->get_license_key();

        if ( empty( $license_key ) && ! in_array( $endpoint, [ 'health' ] ) ) {
            return new WP_Error(
                'no_license',
                'License key non configurata. Vai su Video IPV → Licenza per attivare.'
            );
        }

        $url = $this->get_endpoint_url( $endpoint );

        $args = [
            'method' => $method,
            'timeout' => $timeout,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $license_key,
                'X-License-Key' => $license_key
            ]
        ];

        if ( ! empty( $body ) && in_array( $method, [ 'POST', 'PUT', 'PATCH' ] ) ) {
            $args['body'] = json_encode( $body );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code >= 400 ) {
            $message = $body['message'] ?? $body['error'] ?? 'Errore API sconosciuto';
            return new WP_Error(
                'api_error',
                $message,
                [ 'status' => $status_code ]
            );
        }

        return $body;
    }

    /**
     * Get transcript via server API Gateway
     * NO API KEYS NEEDED! Server gestisce tutto.
     */
    public function get_transcript( $video_id, $mode = 'auto', $lang = 'it' ) {
        $response = $this->request( 'transcript', 'POST', [
            'video_id' => $video_id,
            'mode' => $mode,
            'lang' => $lang
        ], 180 ); // 3 minuti timeout per video lunghi

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( ! isset( $response['transcript'] ) ) {
            return new WP_Error( 'no_transcript', 'Nessuna trascrizione ricevuta' );
        }

        return $response['transcript'];
    }

    /**
     * Generate AI description via server
     * NO OpenAI KEY NEEDED!
     */
    public function generate_description( $transcript, $title = '', $custom_prompt = '' ) {
        $response = $this->request( 'description', 'POST', [
            'transcript' => $transcript,
            'title' => $title,
            'custom_prompt' => $custom_prompt
        ], 60 );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( ! isset( $response['description'] ) ) {
            return new WP_Error( 'no_description', 'Nessuna descrizione ricevuta' );
        }

        return $response['description'];
    }

    /**
     * Get credits info
     */
    public function get_credits_info() {
        $response = $this->request( 'credits', 'GET' );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return $response['credits'] ?? [];
    }

    /**
     * Validate license
     */
    public function validate_license( $license_key = '' ) {
        if ( empty( $license_key ) ) {
            $license_key = $this->get_license_key();
        }

        if ( empty( $license_key ) ) {
            return new WP_Error( 'no_license', 'License key mancante' );
        }

        // Temporarily set license for validation
        $old_key = $this->get_license_key();
        update_option( 'ipv_license_key', $license_key );

        $response = $this->request( 'license/validate', 'POST', [
            'license_key' => $license_key
        ]);

        // Restore old key if validation failed
        if ( is_wp_error( $response ) ) {
            update_option( 'ipv_license_key', $old_key );
        }

        return $response;
    }

    /**
     * Activate license
     */
    public function activate_license( $license_key, $site_url = '', $site_name = '' ) {
        if ( empty( $site_url ) ) {
            $site_url = home_url();
        }

        if ( empty( $site_name ) ) {
            $site_name = get_bloginfo( 'name' );
        }

        // Set license temporarily
        update_option( 'ipv_license_key', $license_key );

        $response = $this->request( 'license/activate', 'POST', [
            'license_key' => $license_key,
            'site_url' => $site_url,
            'site_name' => $site_name
        ]);

        if ( is_wp_error( $response ) ) {
            // Remove invalid license
            delete_option( 'ipv_license_key' );
            return $response;
        }

        // Store license info
        update_option( 'ipv_license_info', $response['license'] ?? [] );
        update_option( 'ipv_license_activated_at', time() );

        return $response;
    }

    /**
     * Deactivate license
     */
    public function deactivate_license() {
        $license_key = $this->get_license_key();
        $site_url = home_url();

        if ( empty( $license_key ) ) {
            return new WP_Error( 'no_license', 'Nessuna license da deattivare' );
        }

        $response = $this->request( 'license/deactivate', 'POST', [
            'license_key' => $license_key,
            'site_url' => $site_url
        ]);

        if ( ! is_wp_error( $response ) ) {
            // Remove local license data
            delete_option( 'ipv_license_key' );
            delete_option( 'ipv_license_info' );
            delete_option( 'ipv_license_activated_at' );
        }

        return $response;
    }

    /**
     * Get license info (detailed)
     */
    public function get_license_info() {
        $license_key = $this->get_license_key();

        if ( empty( $license_key ) ) {
            return new WP_Error( 'no_license', 'License key non configurata' );
        }

        $response = $this->request( 'license/info?license_key=' . $license_key, 'GET' );

        if ( ! is_wp_error( $response ) && isset( $response['license'] ) ) {
            // Update cached license info
            update_option( 'ipv_license_info', $response['license'] );
        }

        return $response;
    }

    /**
     * Check server health
     */
    public function health_check() {
        $response = $this->request( 'health', 'GET' );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        return $response['status'] === 'ok';
    }

    /**
     * Check for updates
     */
    public function check_update( $current_version ) {
        $license_key = $this->get_license_key();

        if ( empty( $license_key ) ) {
            return false;
        }

        $response = $this->request( 'check-update', 'POST', [
            'version' => $current_version,
            'license_key' => $license_key
        ]);

        if ( is_wp_error( $response ) ) {
            return false;
        }

        return $response;
    }

    /**
     * Get plugin info for updates
     */
    public function get_plugin_info() {
        $response = $this->request( 'plugin-info', 'GET' );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        return $response;
    }
}
