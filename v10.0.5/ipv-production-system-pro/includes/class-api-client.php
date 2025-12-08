<?php
/**
 * IPV Production System Pro - API Client
 *
 * Client per comunicare con il server IPV Pro Cloud
 * Tutte le API keys sono sul server, il client usa solo la licenza
 *
 * @package IPV_Production_System_Pro
 * @version 10.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_API_Client {

    private static $instance = null;

    /**
     * Default server URL (must be configured in settings)
     * No default domain - users must configure their own server
     */
    const DEFAULT_SERVER = '';
    const API_NAMESPACE = 'ipv-vendor/v1';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get configured server URL
     */
    private function get_server_url() {
        $server = get_option( 'ipv_api_server_url', '' );
        if ( ! empty( $server ) ) {
            return rtrim( $server, '/' );
        }
        return self::DEFAULT_SERVER;
    }

    /**
     * Get stored license key
     */
    private function get_license_key() {
        return get_option( 'ipv_license_key', '' );
    }

    /**
     * Check if license is active
     */
    public static function is_license_active() {
        $license_info = get_option( 'ipv_license_info', [] );
        return ! empty( $license_info ) && isset( $license_info['status'] ) && $license_info['status'] === 'active';
    }

    /**
     * Get API endpoint URL
     */
    private function get_endpoint_url( $endpoint ) {
        return $this->get_server_url() . '/wp-json/' . self::API_NAMESPACE . '/' . ltrim( $endpoint, '/' );
    }

    /**
     * Make API request
     */
    private function request( $endpoint, $method = 'GET', $body = [], $timeout = 60 ) {
        $license_key = $this->get_license_key();

        // Alcuni endpoint non richiedono licenza
        $public_endpoints = [ 'health' ];
        
        if ( empty( $license_key ) && ! in_array( $endpoint, $public_endpoints ) ) {
            return new WP_Error(
                'no_license',
                __( 'Licenza non configurata. Vai su IPV Videos → Licenza per attivare.', 'ipv-production-system-pro' )
            );
        }

        $url = $this->get_endpoint_url( $endpoint );

        $args = [
            'method' => $method,
            'timeout' => $timeout,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $license_key,
                'X-License-Key' => $license_key,
                'X-Site-URL' => home_url(),
            ]
        ];

        if ( ! empty( $body ) && in_array( $method, [ 'POST', 'PUT', 'PATCH' ] ) ) {
            $args['body'] = wp_json_encode( $body );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            IPV_Prod_Logger::log( 'API Client Error', [
                'endpoint' => $endpoint,
                'error' => $response->get_error_message()
            ]);
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code >= 400 ) {
            $message = $body['message'] ?? $body['error'] ?? __( 'Errore server sconosciuto', 'ipv-production-system-pro' );
            
            IPV_Prod_Logger::log( 'API Client HTTP Error', [
                'endpoint' => $endpoint,
                'status' => $status_code,
                'message' => $message
            ]);
            
            return new WP_Error(
                'api_error',
                $message,
                [ 'status' => $status_code ]
            );
        }

        return $body;
    }

    /**
     * Get video transcript via server
     * Le API keys sono sul server - il client non le vede mai!
     *
     * @param string $video_id YouTube Video ID
     * @param string $mode Modalità: 'auto', 'native', 'whisper'
     * @param string $lang Lingua (default: 'it')
     * @return string|WP_Error Trascrizione o errore
     */
    public function get_transcript( $video_id, $mode = 'auto', $lang = 'it' ) {
        if ( ! self::is_license_active() ) {
            return new WP_Error(
                'license_required',
                __( 'Licenza non attiva. Attiva la licenza per usare questa funzione.', 'ipv-production-system-pro' )
            );
        }

        IPV_Prod_Logger::log( 'Richiesta trascrizione', [
            'video_id' => $video_id,
            'mode' => $mode,
            'lang' => $lang
        ]);

        $response = $this->request( 'transcript', 'POST', [
            'video_id' => $video_id,
            'mode' => $mode,
            'lang' => $lang
        ], 180 ); // 3 minuti timeout per video lunghi

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( ! isset( $response['transcript'] ) ) {
            return new WP_Error( 'no_transcript', __( 'Nessuna trascrizione ricevuta dal server', 'ipv-production-system-pro' ) );
        }

        // Aggiorna info crediti se presenti
        if ( isset( $response['credits_info'] ) ) {
            $license_info = get_option( 'ipv_license_info', [] );
            $license_info['credits'] = $response['credits_info'];
            update_option( 'ipv_license_info', $license_info );
        }

        IPV_Prod_Logger::log( 'Trascrizione ricevuta', [
            'video_id' => $video_id,
            'length' => strlen( $response['transcript'] )
        ]);

        return $response['transcript'];
    }

    /**
     * Generate AI description via server
     * Le API keys sono sul server - il client non le vede mai!
     *
     * @param string $transcript Trascrizione del video
     * @param string $title Titolo del video
     * @param string $custom_prompt Prompt personalizzato (Golden Prompt)
     * @return string|WP_Error Descrizione generata o errore
     */
    public function generate_description( $transcript, $title = '', $custom_prompt = '' ) {
        if ( ! self::is_license_active() ) {
            return new WP_Error(
                'license_required',
                __( 'Licenza non attiva. Attiva la licenza per usare questa funzione.', 'ipv-production-system-pro' )
            );
        }

        // Usa il Golden Prompt salvato se non fornito
        if ( empty( $custom_prompt ) ) {
            $custom_prompt = get_option( 'ipv_golden_prompt', '' );
        }

        IPV_Prod_Logger::log( 'Richiesta generazione AI', [
            'title' => $title,
            'transcript_length' => strlen( $transcript ),
            'has_custom_prompt' => ! empty( $custom_prompt )
        ]);

        $response = $this->request( 'description', 'POST', [
            'transcript' => $transcript,
            'title' => $title,
            'custom_prompt' => $custom_prompt
        ], 120 );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( ! isset( $response['description'] ) ) {
            return new WP_Error( 'no_description', __( 'Nessuna descrizione ricevuta dal server', 'ipv-production-system-pro' ) );
        }

        IPV_Prod_Logger::log( 'Descrizione AI generata', [
            'length' => strlen( $response['description'] )
        ]);

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
            return new WP_Error( 'no_license', __( 'License key mancante', 'ipv-production-system-pro' ) );
        }

        // Temporaneamente imposta la licenza per validazione
        $old_key = $this->get_license_key();
        update_option( 'ipv_license_key', $license_key );

        $response = $this->request( 'license/validate', 'POST', [
            'license_key' => $license_key
        ]);

        // Ripristina vecchia key se validazione fallita
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

        // Imposta licenza temporaneamente
        update_option( 'ipv_license_key', $license_key );

        $response = $this->request( 'license/activate', 'POST', [
            'license_key' => $license_key,
            'site_url' => $site_url,
            'site_name' => $site_name
        ]);

        if ( is_wp_error( $response ) ) {
            // Rimuovi licenza invalida
            delete_option( 'ipv_license_key' );
            return $response;
        }

        // Salva info licenza
        update_option( 'ipv_license_info', $response['license'] ?? [] );
        update_option( 'ipv_license_activated_at', time() );

        IPV_Prod_Logger::log( 'Licenza attivata', [
            'license' => substr( $license_key, 0, 8 ) . '...',
            'site' => $site_url
        ]);

        return $response;
    }

    /**
     * Deactivate license
     */
    public function deactivate_license() {
        $license_key = $this->get_license_key();
        $site_url = home_url();

        if ( empty( $license_key ) ) {
            return new WP_Error( 'no_license', __( 'Nessuna licenza da deattivare', 'ipv-production-system-pro' ) );
        }

        $response = $this->request( 'license/deactivate', 'POST', [
            'license_key' => $license_key,
            'site_url' => $site_url
        ]);

        if ( ! is_wp_error( $response ) ) {
            // Rimuovi dati licenza locali
            delete_option( 'ipv_license_key' );
            delete_option( 'ipv_license_info' );
            delete_option( 'ipv_license_activated_at' );

            IPV_Prod_Logger::log( 'Licenza deattivata' );
        }

        return $response;
    }

    /**
     * Get license info (detailed)
     */
    public function get_license_info() {
        $license_key = $this->get_license_key();

        if ( empty( $license_key ) ) {
            return new WP_Error( 'no_license', __( 'License key non configurata', 'ipv-production-system-pro' ) );
        }

        $response = $this->request( 'license/info?license_key=' . $license_key, 'GET' );

        if ( ! is_wp_error( $response ) && isset( $response['license'] ) ) {
            // Aggiorna cache locale
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
     * Test connection to server
     */
    public function test_connection() {
        $start = microtime( true );
        $result = $this->health_check();
        $time = round( ( microtime( true ) - $start ) * 1000 );

        return [
            'success' => $result,
            'server' => $this->get_server_url(),
            'response_time' => $time . 'ms'
        ];
    }
}
