<?php
/**
 * IPV License REST API Endpoints - VERSIONE SICURA
 *
 * Gestisce attivazione, deattivazione, validazione licenze via REST API
 * CON AUTENTICAZIONE E RATE LIMITING
 * 
 * @version 1.1.2-secure
 * @since 1.1.2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_License_Endpoints {

    /**
     * Rate limit settings
     */
    private const RATE_LIMIT_WINDOW = 3600; // 1 ora
    private const RATE_LIMIT_ACTIVATE = 20;  // Max attivazioni/ora
    private const RATE_LIMIT_DEACTIVATE = 20;
    private const RATE_LIMIT_VALIDATE = 100;
    private const RATE_LIMIT_INFO = 50;

    public function register_routes() {
        // POST /wp-json/ipv-vendor/v1/license/activate
        register_rest_route( 'ipv-vendor/v1', '/license/activate', [
            'methods' => 'POST',
            'callback' => [ $this, 'activate_license' ],
            'permission_callback' => [ $this, 'validate_request' ]
        ]);

        // POST /wp-json/ipv-vendor/v1/license/deactivate
        register_rest_route( 'ipv-vendor/v1', '/license/deactivate', [
            'methods' => 'POST',
            'callback' => [ $this, 'deactivate_license' ],
            'permission_callback' => [ $this, 'validate_request' ]
        ]);

        // POST /wp-json/ipv-vendor/v1/license/validate
        register_rest_route( 'ipv-vendor/v1', '/license/validate', [
            'methods' => 'POST',
            'callback' => [ $this, 'validate_license' ],
            'permission_callback' => [ $this, 'validate_request' ]
        ]);

        // GET /wp-json/ipv-vendor/v1/license/info
        register_rest_route( 'ipv-vendor/v1', '/license/info', [
            'methods' => 'GET',
            'callback' => [ $this, 'get_license_info' ],
            'permission_callback' => [ $this, 'validate_request' ]
        ]);
    }

    /**
     * Validate incoming request
     * Verifica API Key del server O license key valida
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function validate_request( $request ) {
        // Metodo 1: Server API Key (per chiamate server-to-server)
        $server_api_key = $request->get_header( 'X-IPV-Server-Key' );
        if ( ! empty( $server_api_key ) ) {
            $valid_key = get_option( 'ipv_vendor_server_api_key', '' );
            
            if ( empty( $valid_key ) ) {
                // Genera chiave se non esiste
                $valid_key = wp_generate_password( 64, false );
                update_option( 'ipv_vendor_server_api_key', $valid_key );
            }
            
            if ( hash_equals( $valid_key, $server_api_key ) ) {
                return true;
            }
        }

        // Metodo 2: License Key valida (per chiamate client-to-server)
        $license_key = $this->extract_license_key( $request );
        
        if ( empty( $license_key ) ) {
            return new WP_Error(
                'missing_authentication',
                'Autenticazione richiesta. Fornire X-IPV-Server-Key o license_key valida.',
                [ 'status' => 401 ]
            );
        }

        // Verifica che la license key esista (senza controllare lo status)
        $license_manager = IPV_Vendor_License_Manager::instance();
        $license = $license_manager->get_license_by_key( $license_key );
        
        if ( ! $license ) {
            return new WP_Error(
                'invalid_license',
                'License key non valida',
                [ 'status' => 401 ]
            );
        }

        // Check rate limit basato su IP
        $rate_check = $this->check_rate_limit( $request );
        if ( is_wp_error( $rate_check ) ) {
            return $rate_check;
        }

        return true;
    }

    /**
     * Extract license key from request
     */
    private function extract_license_key( $request ) {
        // Check Authorization header (Bearer token)
        $auth_header = $request->get_header( 'Authorization' );
        if ( $auth_header && preg_match( '/Bearer\s+(.+)$/i', $auth_header, $matches ) ) {
            return sanitize_text_field( $matches[1] );
        }

        // Check X-License-Key header
        $header_key = $request->get_header( 'X-License-Key' );
        if ( $header_key ) {
            return sanitize_text_field( $header_key );
        }

        // Check body parameter
        $body_key = $request->get_param( 'license_key' );
        if ( $body_key ) {
            return sanitize_text_field( $body_key );
        }

        return '';
    }

    /**
     * Check rate limit
     */
    private function check_rate_limit( $request ) {
        $ip = $this->get_client_ip();
        $endpoint = $request->get_route();
        
        // Determina il limite basato sull'endpoint
        $limit = self::RATE_LIMIT_VALIDATE;
        if ( strpos( $endpoint, 'activate' ) !== false ) {
            $limit = self::RATE_LIMIT_ACTIVATE;
        } elseif ( strpos( $endpoint, 'deactivate' ) !== false ) {
            $limit = self::RATE_LIMIT_DEACTIVATE;
        } elseif ( strpos( $endpoint, 'info' ) !== false ) {
            $limit = self::RATE_LIMIT_INFO;
        }

        $key = 'ipv_rate_' . md5( $ip . $endpoint );
        $count = (int) get_transient( $key );

        if ( $count >= $limit ) {
            return new WP_Error(
                'rate_limit_exceeded',
                sprintf(
                    'Limite richieste superato (%d/ora). Riprova più tardi.',
                    $limit
                ),
                [ 'status' => 429 ]
            );
        }

        set_transient( $key, $count + 1, self::RATE_LIMIT_WINDOW );
        return true;
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = $_SERVER[ $key ];
                // Se ci sono più IP (proxy chain), prendi il primo
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Validate site URL format
     */
    private function validate_site_url( $url ) {
        $url = esc_url_raw( $url );
        
        if ( empty( $url ) ) {
            return new WP_Error( 'invalid_url', 'URL sito non valido' );
        }

        // Deve avere schema http/https
        if ( ! preg_match( '/^https?:\/\//', $url ) ) {
            return new WP_Error( 'invalid_url', 'URL deve iniziare con http:// o https://' );
        }

        // Verifica dominio valido
        $parsed = parse_url( $url );
        if ( empty( $parsed['host'] ) ) {
            return new WP_Error( 'invalid_url', 'URL non contiene un dominio valido' );
        }

        // Blocca localhost in produzione (opzionale - commentare per dev)
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            if ( in_array( $parsed['host'], [ 'localhost', '127.0.0.1', '::1' ] ) ) {
                return new WP_Error( 'invalid_url', 'URL localhost non permesso in produzione' );
            }
        }

        return $url;
    }

    /**
     * POST /license/activate
     * Activate license on a specific site
     */
    public function activate_license( $request ) {
        $license_key = sanitize_text_field( $request->get_param( 'license_key' ) );
        $site_url = $request->get_param( 'site_url' );
        $site_name = sanitize_text_field( $request->get_param( 'site_name' ) ?: '' );

        if ( empty( $license_key ) || empty( $site_url ) ) {
            return new WP_Error(
                'missing_params',
                'license_key e site_url sono obbligatori',
                [ 'status' => 400 ]
            );
        }

        // Valida URL
        $site_url = $this->validate_site_url( $site_url );
        if ( is_wp_error( $site_url ) ) {
            return $site_url;
        }

        $license_manager = IPV_Vendor_License_Manager::instance();
        $credits_manager = IPV_Vendor_Credits_Manager::instance();

        $license = $license_manager->activate_license(
            $license_key,
            $site_url,
            $site_name,
            $this->get_client_ip()
        );

        if ( is_wp_error( $license ) ) {
            return $license;
        }

        $credits_info = $credits_manager->get_credits_info( $license );

        // Log successful activation
        error_log( sprintf(
            'IPV Vendor: License %s activated for %s from IP %s',
            substr( $license_key, 0, 8 ) . '...',
            $site_url,
            $this->get_client_ip()
        ));

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
        $license_key = sanitize_text_field( $request->get_param( 'license_key' ) );
        $site_url = $request->get_param( 'site_url' );

        if ( empty( $license_key ) || empty( $site_url ) ) {
            return new WP_Error(
                'missing_params',
                'license_key e site_url sono obbligatori',
                [ 'status' => 400 ]
            );
        }

        // Valida URL
        $site_url = $this->validate_site_url( $site_url );
        if ( is_wp_error( $site_url ) ) {
            return $site_url;
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
        $license_key = sanitize_text_field( $request->get_param( 'license_key' ) );

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
        $license_key = sanitize_text_field( $request->get_param( 'license_key' ) );

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
