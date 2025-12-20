<?php
/**
 * IPV Gateway REST API Endpoints
 *
 * API Gateway che protegge le chiamate a SupaData, OpenAI, YouTube
 * Tutte le API keys sono sul server, il client non le vede mai!
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Gateway_Endpoints {

    public function register_routes() {
        // POST /wp-json/ipv-vendor/v1/transcript
        register_rest_route( 'ipv-vendor/v1', '/transcript', [
            'methods' => 'POST',
            'callback' => [ $this, 'get_transcript' ],
            'permission_callback' => '__return_true'
        ]);

        // POST /wp-json/ipv-vendor/v1/description
        register_rest_route( 'ipv-vendor/v1', '/description', [
            'methods' => 'POST',
            'callback' => [ $this, 'generate_description' ],
            'permission_callback' => '__return_true'
        ]);

        // GET /wp-json/ipv-vendor/v1/credits
        register_rest_route( 'ipv-vendor/v1', '/credits', [
            'methods' => 'GET',
            'callback' => [ $this, 'get_credits' ],
            'permission_callback' => '__return_true'
        ]);

        // NOTE: /health endpoint is registered in class-vendor-core.php (more complete version)
    }

    /**
     * Validate license from request header or body
     */
    private function validate_request_license( $request = null ) {
        $license_key = '';

        // v1.3.3 - Debug logging per troubleshooting
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $body_params = $request ? $request->get_json_params() : [];
            error_log( '=== IPV VENDOR DEBUG - License Validation ===' );
            error_log( 'HTTP_AUTHORIZATION: ' . ( $_SERVER['HTTP_AUTHORIZATION'] ?? 'NULL' ) );
            error_log( 'HTTP_X_LICENSE_KEY: ' . ( $_SERVER['HTTP_X_LICENSE_KEY'] ?? 'NULL' ) );
            error_log( 'REDIRECT_HTTP_AUTHORIZATION: ' . ( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 'NULL' ) );
            error_log( 'Body Param license_key: ' . ( $body_params['license_key'] ?? 'NULL' ) );
            error_log( 'Request Param license_key: ' . ( $request ? ( $request->get_param( 'license_key' ) ?: 'NULL' ) : 'NULL' ) );
        }

        // v1.3.3 - PRIORITÀ #1: Body parameter (PIANO B - bypassa hosting che bloccano header)
        // Questo metodo funziona SEMPRE perché i dati del body non vengono mai bloccati
        if ( $request ) {
            $body_license = $request->get_param( 'license_key' );
            if ( ! empty( $body_license ) ) {
                $license_key = $body_license;
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( '✅ License key trovata nel BODY parameter (metodo infallibile!)' );
                }
            }
        }

        // Fallback: Check Authorization header (Bearer token)
        if ( empty( $license_key ) && isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
            if ( preg_match( '/Bearer\s+(.+)$/i', $auth, $matches ) ) {
                $license_key = $matches[1];
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( '✅ License key trovata in HTTP_AUTHORIZATION header' );
                }
            }
        }

        // Fallback: REDIRECT_HTTP_AUTHORIZATION (per alcuni hosting)
        if ( empty( $license_key ) && isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
            $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            if ( preg_match( '/Bearer\s+(.+)$/i', $auth, $matches ) ) {
                $license_key = $matches[1];
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( '✅ License key trovata in REDIRECT_HTTP_AUTHORIZATION' );
                }
            }
        }

        // Fallback: X-License-Key header
        if ( empty( $license_key ) && isset( $_SERVER['HTTP_X_LICENSE_KEY'] ) ) {
            $license_key = $_SERVER['HTTP_X_LICENSE_KEY'];
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '✅ License key trovata in X-License-Key header' );
            }
        }

        if ( empty( $license_key ) ) {
            // v1.3.2 - Log dettagliato per troubleshooting
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'IPV VENDOR ERROR: License key mancante dopo tutti i fallback!' );
                error_log( 'Possibile causa: .htaccess non configurato per preservare Authorization header' );
            }

            return new WP_Error(
                'missing_license',
                'License key mancante. Usa header: Authorization: Bearer YOUR_KEY oppure X-License-Key: YOUR_KEY',
                [ 'status' => 401 ]
            );
        }

        // v1.3.2 - Log license key trovata
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'IPV VENDOR: License key trovata: ' . substr( $license_key, 0, 8 ) . '...' . substr( $license_key, -4 ) );
        }

        $license_manager = IPV_Vendor_License_Manager::instance();
        $license = $license_manager->validate_license( $license_key );

        if ( is_wp_error( $license ) ) {
            return $license;
        }

        return $license;
    }

    /**
     * POST /transcript
     * Get video transcript via SupaData (server-side API call)
     */
    public function get_transcript( $request ) {
        // v1.3.6 - Enhanced logging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '=== GET_TRANSCRIPT START ===' );
        }

        // Validate license
        $license = $this->validate_request_license( $request );

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'validate_request_license returned: ' . ( is_wp_error( $license ) ? 'WP_Error' : 'License Object' ) );
            if ( is_wp_error( $license ) ) {
                error_log( 'WP_Error code: ' . $license->get_error_code() );
                error_log( 'WP_Error message: ' . $license->get_error_message() );
            } else {
                error_log( 'License ID: ' . ( $license->id ?? 'NULL' ) );
                error_log( 'License credits_remaining: ' . ( $license->credits_remaining ?? 'NULL' ) );
            }
        }

        if ( is_wp_error( $license ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '❌ Returning WP_Error from validate_request_license' );
            }
            return $license;
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '✅ License validation passed, checking credits...' );
        }

        // Check credits
        $credits_manager = IPV_Vendor_Credits_Manager::instance();
        $has_credits = $credits_manager->has_credits( $license, 1 );

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'has_credits() returned: ' . ( $has_credits ? 'TRUE' : 'FALSE' ) );
        }

        if ( ! $has_credits ) {
            // Send low credits warning email
            $credits_manager->send_low_credits_warning( $license );

            return new WP_Error(
                'insufficient_credits',
                sprintf(
                    'Crediti insufficienti. Disponibili: %d/%d. Reset: %s',
                    $license->credits_remaining,
                    $license->credits_total,
                    date_i18n( 'd/m/Y', strtotime( $license->credits_reset_date ) )
                ),
                [ 'status' => 402 ]
            );
        }

        // Get params
        $video_id = $request->get_param( 'video_id' );
        $mode = $request->get_param( 'mode' ) ?: 'auto';
        $lang = $request->get_param( 'lang' ) ?: 'it';

        if ( empty( $video_id ) ) {
            return new WP_Error(
                'missing_params',
                'video_id è obbligatorio',
                [ 'status' => 400 ]
            );
        }

        // Validate mode
        $allowed_modes = [ 'auto', 'whisper', 'hybrid' ];
        if ( ! in_array( $mode, $allowed_modes ) ) {
            $mode = 'auto';
        }

        // Call API Gateway (server-side - API keys protette!)
        $api_gateway = IPV_Vendor_API_Gateway::instance();
        $transcript = $api_gateway->get_transcript( $video_id, $mode, $lang, $license );

        if ( is_wp_error( $transcript ) ) {
            return $transcript;
        }

        // Use credit (only if NOT cached)
        $credits_manager->use_credits( $license->id, 1 );

        // Get updated credits info
        $credits_info = $credits_manager->get_credits_info( $license );

        return rest_ensure_response([
            'success' => true,
            'transcript' => $transcript,
            'video_id' => $video_id,
            'mode' => $mode,
            'lang' => $lang,
            'credits_remaining' => $credits_info['credits_remaining'],
            'credits_info' => $credits_info
        ]);
    }

    /**
     * POST /description
     * Generate AI description via OpenAI (server-side)
     */
    public function generate_description( $request ) {
        // Validate license
        $license = $this->validate_request_license( $request );
        if ( is_wp_error( $license ) ) {
            return $license;
        }

        // Get params
        $transcript = $request->get_param( 'transcript' );
        $title = $request->get_param( 'title' ) ?: '';
        $custom_prompt = $request->get_param( 'custom_prompt' ) ?: '';

        if ( empty( $transcript ) ) {
            return new WP_Error(
                'missing_params',
                'transcript è obbligatorio',
                [ 'status' => 400 ]
            );
        }

        // Call API Gateway (server-side - OpenAI key protetta!)
        $api_gateway = IPV_Vendor_API_Gateway::instance();
        $description = $api_gateway->generate_description(
            $transcript,
            $title,
            $custom_prompt,
            $license
        );

        if ( is_wp_error( $description ) ) {
            return $description;
        }

        // AI description is FREE (included in transcript credits)
        // No credits deducted

        return rest_ensure_response([
            'success' => true,
            'description' => $description
        ]);
    }

    /**
     * GET /credits
     * Get credits information for license
     */
    public function get_credits( $request ) {
        // Validate license
        $license = $this->validate_request_license( $request );
        if ( is_wp_error( $license ) ) {
            return $license;
        }

        $credits_manager = IPV_Vendor_Credits_Manager::instance();
        $credits_info = $credits_manager->get_credits_info( $license );

        return rest_ensure_response([
            'success' => true,
            'credits' => $credits_info
        ]);
    }

    // NOTE: health_check() method removed - endpoint is now in class-vendor-core.php
}
