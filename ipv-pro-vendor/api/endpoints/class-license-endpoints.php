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

        // POST /wp-json/ipv-vendor/v1/license/download-asset
        register_rest_route( 'ipv-vendor/v1', '/license/download-asset', [
            'methods' => 'POST',
            'callback' => [ $this, 'download_digital_asset' ],
            'permission_callback' => [ $this, 'validate_request' ]
        ]);

        // GET /wp-json/ipv-vendor/v1/license/download-golden-prompt
        register_rest_route( 'ipv-vendor/v1', '/license/download-golden-prompt', [
            'methods' => 'GET',
            'callback' => [ $this, 'download_golden_prompt' ],
            'permission_callback' => [ $this, 'validate_request' ]
        ]);

        // GET /wp-json/ipv-vendor/v1/license/download-template-base
        register_rest_route( 'ipv-vendor/v1', '/license/download-template-base', [
            'methods' => 'GET',
            'callback' => [ $this, 'download_template_base' ],
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
        $license = $license_manager->get_by_key( $license_key );
        
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
        try {
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

        } catch ( Exception $e ) {
            error_log( 'IPV Vendor License Activation Error: ' . $e->getMessage() );
            return new WP_Error(
                'activation_error',
                'Errore durante attivazione: ' . $e->getMessage(),
                [ 'status' => 500 ]
            );
        } catch ( Error $e ) {
            error_log( 'IPV Vendor License Activation Fatal Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
            return new WP_Error(
                'activation_error',
                'Errore fatale: ' . $e->getMessage(),
                [ 'status' => 500 ]
            );
        }
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

        $result = $license_manager->validate( $license_key, '' );

        if ( ! $result['valid'] ) {
            return new WP_Error(
                'invalid_license',
                'License non valida: ' . ( $result['error'] ?? 'unknown' ),
                [ 'status' => 401 ]
            );
        }

        $license = $result['license'];

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

        $license = $license_manager->get_by_key( $license_key );

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

        // Build license data
        $license_data = [
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
        ];

        // Add Golden prompt data if license is golden_prompt
        if ( $license->variant_slug === 'golden_prompt' ) {
            // Get Golden prompt enabled status
            $golden_enabled = $wpdb->get_var( $wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
                WHERE license_id = %d AND meta_key = '_golden_prompt_enabled'",
                $license->id
            ));

            // Get Golden prompt file path
            $golden_file = $wpdb->get_var( $wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
                WHERE license_id = %d AND meta_key = '_golden_prompt_file'",
                $license->id
            ));

            // Check if file exists
            $has_file = !empty($golden_file) && file_exists($golden_file);

            // Get file info if available
            $file_info = [];
            if ( $has_file ) {
                $file_info['size'] = filesize($golden_file);
                $file_info['size_formatted'] = size_format($file_info['size']);

                // Get original filename
                $original_filename = $wpdb->get_var( $wpdb->prepare(
                    "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
                    WHERE license_id = %d AND meta_key = '_golden_prompt_original_filename'",
                    $license->id
                ));
                if ( $original_filename ) {
                    $file_info['filename'] = $original_filename;
                }

                // Get upload timestamp
                $uploaded_at = $wpdb->get_var( $wpdb->prepare(
                    "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
                    WHERE license_id = %d AND meta_key = '_golden_prompt_uploaded_at'",
                    $license->id
                ));
                if ( $uploaded_at ) {
                    $file_info['uploaded_at'] = $uploaded_at;
                }
            }

            // Add Golden prompt data to response
            $license_data['golden_prompt'] = [
                'enabled' => (bool) $golden_enabled,
                'has_file' => $has_file,
                'can_download' => (bool) $golden_enabled && $has_file,
                'file_info' => $has_file ? $file_info : null
            ];

            // Set template type
            if ( (bool) $golden_enabled && $has_file ) {
                $license_data['template_type'] = 'golden_premium';
                $license_data['template_description'] = 'Template personalizzato Golden Prompt con formato completo';
            } else {
                $license_data['template_type'] = 'base';
                $license_data['template_description'] = 'Template BASE gratuito (Descrizione, Capitoli, Hashtag)';
            }
        } else {
            // All other licenses use BASE template
            $license_data['template_type'] = 'base';
            $license_data['template_description'] = 'Template BASE gratuito (Descrizione, Capitoli, Hashtag)';
        }

        return rest_ensure_response([
            'success' => true,
            'license' => $license_data
        ]);
    }

    /**
     * Download Digital Asset (Golden Prompt)
     * Gestisce il download sicuro di asset digitali legati alla licenza
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function download_digital_asset( $request ) {
        $license_key = $this->extract_license_key( $request );
        $asset_slug = $request->get_param( 'asset_slug' );

        // Validate params
        if ( empty( $license_key ) ) {
            return new WP_Error(
                'missing_params',
                'license_key è obbligatorio',
                [ 'status' => 400 ]
            );
        }

        if ( empty( $asset_slug ) ) {
            return new WP_Error(
                'missing_params',
                'asset_slug è obbligatorio (es: golden_prompt)',
                [ 'status' => 400 ]
            );
        }

        $license_manager = IPV_Vendor_License_Manager::instance();
        $license = $license_manager->get_by_key( $license_key );

        if ( ! $license ) {
            return new WP_Error(
                'invalid_license',
                'License non trovata',
                [ 'status' => 404 ]
            );
        }

        // Check license status
        if ( $license->status !== 'active' ) {
            return new WP_Error(
                'license_inactive',
                'Licenza non attiva. Stato corrente: ' . $license->status,
                [ 'status' => 403 ]
            );
        }

        // Check if license variant matches asset
        if ( $license->variant_slug !== $asset_slug ) {
            return new WP_Error(
                'variant_mismatch',
                'Questa licenza non ha accesso a questo asset digitale. Licenza: ' . $license->variant_slug,
                [ 'status' => 403 ]
            );
        }

        // Get product metadata to check if it's a digital asset
        global $wpdb;
        $product_id = $license->product_id;

        $product_type = get_post_meta( $product_id, '_ipv_product_type', true );
        $download_limit = get_post_meta( $product_id, '_ipv_download_limit', true );
        $is_remote_download = get_post_meta( $product_id, '_ipv_remote_download', true );

        if ( $product_type !== 'digital_asset' || ! $is_remote_download ) {
            return new WP_Error(
                'not_digital_asset',
                'Questo prodotto non è un asset digitale scaricabile',
                [ 'status' => 400 ]
            );
        }

        // Check download count
        $download_count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
            WHERE license_id = %d AND meta_key = '_asset_download_count'",
            $license->id
        ));

        $max_downloads = $download_limit ? (int) $download_limit : 1;

        if ( $download_count >= $max_downloads ) {
            return new WP_Error(
                'download_limit_reached',
                sprintf( 'Limite download raggiunto (%d/%d). Non è possibile scaricare nuovamente questo asset.', $download_count, $max_downloads ),
                [ 'status' => 403 ]
            );
        }

        // Generate secure download token (expires in 5 minutes)
        $token = wp_generate_password( 64, false );
        $expires_at = time() + 300; // 5 minutes

        // Store token in transient
        set_transient( 'ipv_download_token_' . $token, [
            'license_id' => $license->id,
            'asset_slug' => $asset_slug,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ], 300 );

        // Increment download count
        $wpdb->query( $wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}ipv_license_meta (license_id, meta_key, meta_value)
            VALUES (%d, '_asset_download_count', %d)
            ON DUPLICATE KEY UPDATE meta_value = meta_value + 1",
            $license->id,
            1
        ));

        // Log download request
        $wpdb->insert(
            $wpdb->prefix . 'ipv_license_meta',
            [
                'license_id' => $license->id,
                'meta_key' => '_asset_download_requested_at',
                'meta_value' => current_time( 'mysql' )
            ],
            [ '%d', '%s', '%s' ]
        );

        // Generate download URL
        $download_url = add_query_arg([
            'ipv_download_token' => $token,
            'asset' => $asset_slug
        ], home_url( '/ipv-download-asset/' ));

        return rest_ensure_response([
            'success' => true,
            'message' => 'Token di download generato con successo',
            'download_url' => $download_url,
            'expires_in' => 300, // seconds
            'downloads_remaining' => max( 0, $max_downloads - $download_count - 1 ),
            'warning' => 'Questo link scade tra 5 minuti e può essere usato una sola volta. Conserva il file scaricato in un luogo sicuro.'
        ]);
    }

    /**
     * Download Golden Prompt File
     * Endpoint sicuro per CLIENT plugin per scaricare il file Golden prompt
     * quando è abilitato dall'admin
     *
     * @param WP_REST_Request $request
     * @return void (serves file directly) or WP_Error
     */
    public function download_golden_prompt( $request ) {
        global $wpdb;

        $license_key = $this->extract_license_key( $request );

        // Validate license key
        if ( empty( $license_key ) ) {
            return new WP_Error(
                'missing_license',
                'license_key è obbligatorio',
                [ 'status' => 400 ]
            );
        }

        $license_manager = IPV_Vendor_License_Manager::instance();
        $license = $license_manager->get_by_key( $license_key );

        if ( ! $license ) {
            return new WP_Error(
                'invalid_license',
                'License non trovata',
                [ 'status' => 404 ]
            );
        }

        // Check license status
        if ( $license->status !== 'active' ) {
            return new WP_Error(
                'license_inactive',
                'Licenza non attiva. Stato corrente: ' . $license->status,
                [ 'status' => 403 ]
            );
        }

        // Check if license is Golden prompt
        if ( $license->variant_slug !== 'golden_prompt' ) {
            return new WP_Error(
                'not_golden_prompt',
                'Questa licenza non è di tipo Golden prompt. Tipo corrente: ' . $license->variant_slug,
                [ 'status' => 403 ]
            );
        }

        // Check if Golden prompt is enabled
        $golden_enabled = $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
            WHERE license_id = %d AND meta_key = '_golden_prompt_enabled'",
            $license->id
        ));

        if ( ! $golden_enabled ) {
            return new WP_Error(
                'golden_prompt_disabled',
                'Golden prompt non abilitato per questa licenza. Contatta l\'amministratore.',
                [ 'status' => 403 ]
            );
        }

        // Get file path
        $file_path = $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
            WHERE license_id = %d AND meta_key = '_golden_prompt_file'",
            $license->id
        ));

        if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
            return new WP_Error(
                'file_not_found',
                'File Golden prompt non trovato. Contatta l\'amministratore.',
                [ 'status' => 404 ]
            );
        }

        // Get original filename
        $original_filename = $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
            WHERE license_id = %d AND meta_key = '_golden_prompt_original_filename'",
            $license->id
        ));

        if ( empty( $original_filename ) ) {
            $original_filename = 'golden-prompt.zip';
        }

        // Log download attempt
        $wpdb->insert(
            $wpdb->prefix . 'ipv_license_meta',
            [
                'license_id' => $license->id,
                'meta_key' => '_golden_prompt_downloaded_at',
                'meta_value' => current_time( 'mysql' )
            ],
            [ '%d', '%s', '%s' ]
        );

        // Log download with IP
        error_log( sprintf(
            'IPV Vendor: Golden prompt downloaded - License: %s, IP: %s, File: %s',
            substr( $license_key, 0, 8 ) . '...',
            $this->get_client_ip(),
            $original_filename
        ));

        // Serve file
        header( 'Content-Type: application/zip' );
        header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $original_filename ) . '"' );
        header( 'Content-Length: ' . filesize( $file_path ) );
        header( 'Cache-Control: no-cache, no-store, must-revalidate' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        // Prevent WordPress from adding any additional output
        @ob_clean();
        flush();

        readfile( $file_path );
        exit;
    }

    /**
     * Download Template BASE
     * Template gratuito per tutti gli utenti - genera solo Descrizione, Capitoli, Hashtag
     * Disponibile per tutte le licenze attive
     *
     * @param WP_REST_Request $request
     * @return void (serves file directly) or WP_Error
     */
    public function download_template_base( $request ) {
        $license_key = $this->extract_license_key( $request );

        // Validate license key
        if ( empty( $license_key ) ) {
            return new WP_Error(
                'missing_license',
                'license_key è obbligatorio',
                [ 'status' => 400 ]
            );
        }

        $license_manager = IPV_Vendor_License_Manager::instance();
        $license = $license_manager->get_by_key( $license_key );

        if ( ! $license ) {
            return new WP_Error(
                'invalid_license',
                'License non trovata',
                [ 'status' => 404 ]
            );
        }

        // Check license status
        if ( $license->status !== 'active' ) {
            return new WP_Error(
                'license_inactive',
                'Licenza non attiva. Stato corrente: ' . $license->status,
                [ 'status' => 403 ]
            );
        }

        // Get template BASE path
        $template_path = IPV_VENDOR_DIR . 'templates/youtube-description-base.txt';

        if ( ! file_exists( $template_path ) ) {
            return new WP_Error(
                'template_not_found',
                'Template BASE non trovato sul server',
                [ 'status' => 500 ]
            );
        }

        // Log download
        error_log( sprintf(
            'IPV Vendor: Template BASE downloaded - License: %s, IP: %s',
            substr( $license_key, 0, 8 ) . '...',
            $this->get_client_ip()
        ));

        // Serve file
        header( 'Content-Type: text/plain; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="youtube-description-template-base.txt"' );
        header( 'Content-Length: ' . filesize( $template_path ) );
        header( 'Cache-Control: public, max-age=3600' ); // Cache per 1 ora (il template non cambia spesso)
        header( 'Pragma: public' );

        // Prevent WordPress from adding any additional output
        @ob_clean();
        flush();

        readfile( $template_path );
        exit;
    }
}
