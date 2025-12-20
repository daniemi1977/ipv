<?php
/**
 * IPV Golden Prompt REST API (Vendor Side)
 *
 * Endpoint REST per fornire il Golden Prompt ai client
 * Il Golden Prompt viene trasmesso in modo sicuro e NON è visibile al client finale
 *
 * @package IPV_Pro_Vendor
 * @since 1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Golden_Prompt_API {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Endpoint per ottenere il Golden Prompt (chiamato dal client)
        register_rest_route( 'ipv-vendor/v1', '/golden-prompt', [
            'methods' => 'GET',
            'callback' => [ $this, 'get_golden_prompt' ],
            'permission_callback' => [ $this, 'verify_license' ],
        ]);

        // ✅ NEW: Endpoint per compilare Golden Prompt (client invia dati + flags)
        register_rest_route( 'ipv-vendor/v1', '/golden-prompt/compile', [
            'methods' => 'POST',
            'callback' => [ $this, 'compile_golden_prompt' ],
            'permission_callback' => [ $this, 'verify_license' ],
            'args' => [
                'config' => [
                    'required' => true,
                    'type' => 'object',
                    'description' => 'Configuration data + flags from client',
                ],
            ],
        ]);

        // Endpoint per verificare se esiste un Golden Prompt
        register_rest_route( 'ipv-vendor/v1', '/golden-prompt/check', [
            'methods' => 'GET',
            'callback' => [ $this, 'check_golden_prompt' ],
            'permission_callback' => [ $this, 'verify_license' ],
        ]);

        // Endpoint per ottenere l'hash del Golden Prompt (per sync check)
        register_rest_route( 'ipv-vendor/v1', '/golden-prompt/hash', [
            'methods' => 'GET',
            'callback' => [ $this, 'get_golden_prompt_hash' ],
            'permission_callback' => [ $this, 'verify_license' ],
        ]);
    }

    /**
     * Verify license from request header
     */
    public function verify_license( $request ) {
        $license_key = $request->get_header( 'X-License-Key' );

        if ( empty( $license_key ) ) {
            return new WP_Error( 'missing_license', 'License key mancante', [ 'status' => 401 ] );
        }

        // Validate license
        $license_manager = IPV_Vendor_License_Manager::instance();
        $license = $license_manager->validate_license( $license_key );

        if ( is_wp_error( $license ) ) {
            return $license;
        }

        // Store license in request for later use
        $request->set_param( '_license', $license );

        return true;
    }

    /**
     * GET /golden-prompt - Ottieni il Golden Prompt per la licenza
     */
    public function get_golden_prompt( $request ) {
        $license = $request->get_param( '_license' );

        $manager = IPV_Vendor_Golden_Prompt_Manager::instance();
        $config = $manager->get_license_config( $license->id );

        if ( ! $config || ! $config['is_active'] ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Golden Prompt non configurato per questa licenza',
                'has_golden_prompt' => false
            ], 200 );
        }

        // Log the access
        if ( class_exists( 'IPV_Vendor_Audit_Log' ) ) {
            IPV_Vendor_Audit_Log::log( 'golden_prompt_fetched', [
                'license_id' => $license->id,
                'license_key' => $license->license_key
            ]);
        }

        return new WP_REST_Response( [
            'success' => true,
            'has_golden_prompt' => true,
            'golden_prompt' => $config['golden_prompt'],
            'updated_at' => $config['updated_at'],
            'hash' => md5( $config['golden_prompt'] )
        ], 200 );
    }

    /**
     * GET /golden-prompt/check - Verifica se esiste un Golden Prompt
     */
    public function check_golden_prompt( $request ) {
        $license = $request->get_param( '_license' );

        $manager = IPV_Vendor_Golden_Prompt_Manager::instance();
        $config = $manager->get_license_config( $license->id );

        $has_prompt = $config && $config['is_active'] && ! empty( $config['golden_prompt'] );

        return new WP_REST_Response( [
            'success' => true,
            'has_golden_prompt' => $has_prompt,
            'updated_at' => $has_prompt ? $config['updated_at'] : null,
            'hash' => $has_prompt ? md5( $config['golden_prompt'] ) : null
        ], 200 );
    }

    /**
     * GET /golden-prompt/hash - Ottieni solo l'hash (per sync check rapido)
     */
    public function get_golden_prompt_hash( $request ) {
        $license = $request->get_param( '_license' );

        $manager = IPV_Vendor_Golden_Prompt_Manager::instance();
        $config = $manager->get_license_config( $license->id );

        if ( ! $config || ! $config['is_active'] ) {
            return new WP_REST_Response( [
                'hash' => null,
                'updated_at' => null
            ], 200 );
        }

        return new WP_REST_Response( [
            'hash' => md5( $config['golden_prompt'] ),
            'updated_at' => $config['updated_at']
        ], 200 );
    }

    /**
     * POST /golden-prompt/compile - Compila Golden Prompt con dati cliente
     * 
     * Il client invia:
     * - config: { dati + flags }
     * 
     * Vendor:
     * 1. Prende template universale
     * 2. Sostituisce placeholder con dati
     * 3. Applica flags per rimuovere sezioni
     * 4. Salva Golden Prompt compilato
     * 5. Risponde success
     */
    public function compile_golden_prompt( $request ) {
        $license = $request->get_param( '_license' );
        $config = $request->get_param( 'config' );

        if ( empty( $config ) || ! is_array( $config ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Configuration data required'
            ], 400 );
        }

        error_log( sprintf(
            'IPV Vendor: Compiling Golden Prompt for license %s',
            $license->license_key
        ));

        // 1. Get Golden Prompt Manager
        $manager = IPV_Vendor_Golden_Prompt_Manager::instance();

        // 2. Save config + compile template
        $result = $manager->save_license_config( $license->id, $config );

        if ( ! $result ) {
            error_log( 'IPV Vendor: ERROR - Failed to compile Golden Prompt' );
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Failed to compile Golden Prompt'
            ], 500 );
        }

        error_log( sprintf(
            'IPV Vendor: ✅ Golden Prompt compiled for license %s (channel: %s)',
            $license->license_key,
            $config['nome_canale'] ?? 'Unknown'
        ));

        return new WP_REST_Response( [
            'success' => true,
            'message' => 'Golden Prompt compiled successfully',
            'channel' => $config['nome_canale'] ?? 'Unknown'
        ], 200 );
    }
}
