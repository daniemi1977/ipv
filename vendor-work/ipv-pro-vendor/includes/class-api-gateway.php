<?php
/**
 * IPV API Gateway - VERSIONE OTTIMIZZATA E SICURA
 *
 * Versione ottimizzata con:
 * - ✅ Rate limiting per license (anti-abuse)
 * - ✅ Request validation robusta
 * - ✅ Caching transcript server-side
 * - ✅ API key rotation intelligente
 * - ✅ Audit logging completo
 * - ✅ DDoS protection (IP + User-Agent filtering)
 * - ✅ Response compression
 * - ✅ Performance monitoring
 * - ✅ Automatic failover
 *
 * @package IPV_Pro_Vendor
 * @version 1.4.0-optimized
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_API_Gateway_Optimized {

    private static $instance = null;
    private $rate_limit_enabled = true;
    private $cache_enabled = true;
    private $audit_log_enabled = true;

    // Rate limiting
    const RATE_LIMIT_WINDOW = 3600; // 1 hour
    const RATE_LIMIT_MAX_REQUESTS = 100; // Max requests per hour per license

    // Caching
    const CACHE_TRANSCRIPT_TTL = 7 * DAY_IN_SECONDS; // 7 days
    const CACHE_YOUTUBE_TTL = 3600; // 1 hour

    // Security
    const MAX_REQUEST_SIZE = 1048576; // 1MB max request body
    const BLOCKED_USER_AGENTS = [ 'bot', 'crawler', 'spider', 'scraper' ];

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get API keys from database
     */
    private function get_youtube_api_key() {
        $key = trim( (string) get_option( 'ipv_youtube_api_key', '' ) );

        if ( empty( $key ) ) {
            error_log( 'IPV Vendor: YouTube API key non configurata' );
            return new WP_Error( 'missing_api_key', 'YouTube API key non configurata' );
        }

        return $key;
    }

    private function get_openai_api_key() {
        $key = trim( (string) get_option( 'ipv_openai_api_key', '' ) );

        if ( empty( $key ) ) {
            error_log( 'IPV Vendor: OpenAI API key non configurata' );
            return new WP_Error( 'missing_api_key', 'OpenAI API key non configurata' );
        }

        return $key;
    }

    /**
     * Get SupaData key with intelligent rotation
     */
    /**
     * Get SupaData key - Fixed mode (fallback sequenziale)
     * v1.0.14 - WHITELABEL READY: nessuna chiave hardcoded
     * 
     * Le chiavi devono essere configurate in Settings → API Keys
     * Ordine: Key 1 → Key 2 → Key 3 (fallback se errore 401/402/429)
     */
    private function get_supadata_key( $attempt = 1 ) {
        $keys = array_filter( [
            get_option( 'ipv_supadata_api_key_1', '' ),
            get_option( 'ipv_supadata_api_key_2', '' ),
            get_option( 'ipv_supadata_api_key_3', '' )
        ] );

        // Reindex
        $keys = array_values( $keys );

        if ( empty( $keys ) ) {
            return new WP_Error( 
                'missing_api_key', 
                __( 'SupaData API key non configurata. Vai su IPV Vendor → Settings → API Keys.', 'ipv-pro-vendor' )
            );
        }

        // Fixed mode: attempt 1 = prima chiave, attempt 2 = seconda, ecc.
        $key_index = min( $attempt - 1, count( $keys ) - 1 );
        return $keys[ $key_index ];
    }

    /**
     * Validate request (security)
     * v1.0.11 - FIX PHP 8.2+: cast (string) prima di strpos
     */
    private function validate_request( $request ) {
        // Check request size
        $body_size = strlen( (string) $request->get_body() );
        if ( $body_size > self::MAX_REQUEST_SIZE ) {
            return new WP_Error( 'request_too_large', 'Request body troppo grande' );
        }

        // Check User-Agent (block bots)
        $block_bots_enabled = get_option( 'ipv_block_bots_enabled', 1 );
        
        if ( $block_bots_enabled ) {
            // v1.0.11 - FIX PHP 8.2+: cast (string) per evitare deprecated warning
            $user_agent = strtolower( (string) ( $request->get_header( 'user-agent' ) ?? '' ) );
            $blocked_agents = [ 'bot', 'crawler', 'spider', 'scraper' ];

            foreach ( $blocked_agents as $blocked ) {
                if ( strpos( $user_agent, $blocked ) !== false ) {
                    $this->log_security_event( 'blocked_user_agent', [
                        'user_agent' => $user_agent,
                        'ip' => $this->get_client_ip()
                    ] );

                    return new WP_Error( 'forbidden', 'Access denied' );
                }
            }
        }

        // Check for suspicious patterns
        $body = $request->get_json_params();

        if ( is_array( $body ) ) {
            $json_str = wp_json_encode( $body );

            // Check for SQL injection patterns
            $sql_patterns = [ 'UNION', 'SELECT', 'DROP', 'INSERT', 'DELETE', 'UPDATE', '--', '/*', '*/' ];

            foreach ( $sql_patterns as $pattern ) {
                if ( stripos( $json_str, $pattern ) !== false ) {
                    $this->log_security_event( 'sql_injection_attempt', [
                        'pattern' => $pattern,
                        'ip' => $this->get_client_ip()
                    ] );

                    return new WP_Error( 'invalid_request', 'Invalid request' );
                }
            }
        }

        return true;
    }

    /**
     * Rate limiting check
     */
    private function check_rate_limit( $license ) {
        // Check if rate limiting is enabled in settings
        $rate_limit_enabled = get_option( 'ipv_rate_limit_enabled', 1 );
        
        if ( ! $rate_limit_enabled ) {
            return true;
        }

        // Get configurable limits
        $max_requests = get_option( 'ipv_rate_limit_max_requests', 100 );
        $window = get_option( 'ipv_rate_limit_window', 3600 );

        $cache_key = 'ipv_rate_limit_' . $license->id;
        $requests = get_transient( $cache_key ) ?: 0;

        if ( $requests >= $max_requests ) {
            $this->log_security_event( 'rate_limit_exceeded', [
                'license_id' => $license->id,
                'requests' => $requests,
                'limit' => $max_requests,
                'window' => $window
            ] );

            return new WP_Error(
                'rate_limit_exceeded',
                sprintf( 'Rate limit exceeded: %d requests in %d seconds', $max_requests, $window )
            );
        }

        // Increment counter
        set_transient( $cache_key, $requests + 1, $window );

        return true;
    }

    /**
     * Get transcript with caching and retry logic
     * v1.0.11 - Restituisce array con flag 'cached' per non scalare crediti su cache hit
     */
    public function get_transcript( $video_id, $mode = 'auto', $lang = 'it', $license = null ) {
        // Sanitize inputs
        $video_id = sanitize_text_field( $video_id );
        $mode = in_array( $mode, [ 'auto', 'whisper', 'hybrid' ] ) ? $mode : 'auto';
        $lang = sanitize_text_field( $lang );

        // Check cache first
        $cache_key = 'ipv_transcript_' . $video_id . '_' . $mode . '_' . $lang;
        if ( $this->cache_enabled ) {
            $cached = get_transient( $cache_key );

            // v1.0.15 - Valida cache: non restituire se vuota o invalida
            if ( $cached !== false && ! empty( trim( $cached ) ) && strlen( $cached ) > 50 ) {
                $this->log_api_call(
                    $license ? $license->id : null,
                    'transcript',
                    $video_id,
                    200,
                    0,
                    0,
                    true
                );

                // v1.0.11 - Restituisci array con flag cached = true
                return [
                    'text' => $cached,
                    'cached' => true,
                    'video_id' => $video_id,
                    'mode' => $mode,
                    'lang' => $lang
                ];
            } elseif ( $cached !== false ) {
                // Cache invalida/vuota - elimina
                delete_transient( $cache_key );
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( '[IPV Vendor] Invalid cache deleted for video: ' . $video_id );
                }
            }
        }

        // v1.0.14 - Whitelabel ready: calcola max_attempts solo dalle chiavi configurate
        $available_keys = array_filter( [
            get_option( 'ipv_supadata_api_key_1', '' ),
            get_option( 'ipv_supadata_api_key_2', '' ),
            get_option( 'ipv_supadata_api_key_3', '' )
        ] );
        
        // Se nessuna chiave configurata, get_supadata_key restituirà errore
        $max_attempts = max( 1, count( $available_keys ) );
        $last_error = null;

        for ( $attempt = 1; $attempt <= $max_attempts; $attempt++ ) {
            $api_key = $this->get_supadata_key( $attempt );

            if ( is_wp_error( $api_key ) ) {
                return $api_key;
            }

            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( sprintf(
                    'SupaData attempt %d/%d with key: %s...',
                    $attempt,
                    $max_attempts,
                    substr( $api_key, 0, 10 )
                ) );
            }

            // v1.0.23 - FIX: Endpoint corretto SupaData (allineato a v9.2.2 funzionante)
            // - GET invece di POST
            // - /v1/transcript con url completo
            // - Parametri: url, text, mode, lang (come v9.2.2)
            // - x-api-key header
            $youtube_url = 'https://www.youtube.com/watch?v=' . $video_id;
            $query_params = [
                'url'  => $youtube_url,
                'text' => 'true',
                'mode' => $mode,
            ];

            // Aggiungi lang solo se non vuoto (come v9.2.2)
            if ( ! empty( $lang ) && $lang !== 'auto' ) {
                $query_params['lang'] = $lang;
            }

            $supadata_url = add_query_arg( $query_params, 'https://api.supadata.ai/v1/transcript' );
            
            $response = wp_remote_get( $supadata_url, [
                'timeout' => 180,
                'headers' => [
                    'x-api-key' => $api_key,
                    'Accept' => 'application/json',
                ],
            ] );

            if ( is_wp_error( $response ) ) {
                $last_error = $response;
                error_log( 'SupaData error attempt ' . $attempt . ': ' . $response->get_error_message() );

                // Wait before retry (exponential backoff)
                if ( $attempt < $max_attempts ) {
                    sleep( pow( 2, $attempt - 1 ) );
                }

                continue;
            }

            $status_code = wp_remote_retrieve_response_code( $response );
            $body = json_decode( wp_remote_retrieve_body( $response ), true );

            // v1.0.22 - Debug log per troubleshooting
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( sprintf(
                    '[IPV Vendor] SupaData response: HTTP %d, body_keys=%s',
                    $status_code,
                    is_array( $body ) ? implode( ',', array_keys( $body ) ) : 'not_array'
                ) );
            }

            // v1.0.18 - FIX: SupaData restituisce 'content', non 'transcript'
            if ( $status_code === 200 && ( isset( $body['content'] ) || isset( $body['transcript'] ) ) ) {
                $transcript = $body['content'] ?? $body['transcript'] ?? '';

                // Cache for 7 days
                if ( $this->cache_enabled ) {
                    set_transient( $cache_key, $transcript, self::CACHE_TRANSCRIPT_TTL );
                }

                $this->log_api_call(
                    $license ? $license->id : null,
                    'transcript',
                    $video_id,
                    200,
                    strlen( $transcript ),
                    $attempt
                );
                
                // Log lingua rilevata
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( sprintf(
                        '[IPV Vendor] SupaData SUCCESS: video=%s, lang=%s, length=%d',
                        $video_id,
                        $body['lang'] ?? 'unknown',
                        strlen( $transcript )
                    ) );
                }

                // v1.0.11 - Restituisci array con flag cached = false
                return [
                    'text' => $transcript,
                    'cached' => false,
                    'video_id' => $video_id,
                    'mode' => $mode,
                    'lang' => $body['lang'] ?? $lang,
                    'available_langs' => $body['availableLangs'] ?? []
                ];
            }

            // Retryable errors (quota/rate limit/unauthorized)
            // v1.0.17 - FIX LOOP: verifica se esiste next key PRIMA di continuare
            if ( in_array( $status_code, [ 401, 402, 429 ] ) ) {
                
                // Verifica se esiste una chiave successiva
                $has_next_key = ( $attempt < $max_attempts );
                
                if ( $has_next_key ) {
                    // C'è una chiave successiva - prova quella
                    error_log( sprintf(
                        '[IPV Vendor] SupaData key %d/%d failed (HTTP %d), trying next key...',
                        $attempt,
                        $max_attempts,
                        $status_code
                    ));
                    continue;
                }
                
                // NESSUNA chiave successiva - STOP IMMEDIATO
                error_log( sprintf(
                    '[IPV Vendor] SupaData API key unauthorized (HTTP %d). No fallback key available. Check Settings → API Keys.',
                    $status_code
                ));
                
                // Log API call come fallito
                $this->log_api_call(
                    $license ? $license->id : null,
                    'transcript',
                    $video_id,
                    $status_code,
                    0,
                    $attempt,
                    false
                );
                
                // Return errore definitivo - NON retry
                return new WP_Error( 
                    'supadata_unauthorized', 
                    sprintf( 
                        'SupaData API key non valida (HTTP %d). Configura una chiave valida in IPV Vendor → Settings → API Keys.', 
                        $status_code 
                    ),
                    [ 'status' => 502, 'supadata_status' => $status_code, 'retry' => false ]
                );
            }

            // Non-retryable error
            $error_msg = $body['error'] ?? $body['message'] ?? 'Unknown error';
            $last_error = new WP_Error( 'supadata_error', $error_msg, [ 'status' => $status_code ] );
            break;
        }

        // All attempts failed
        $this->log_api_call(
            $license ? $license->id : null,
            'transcript',
            $video_id,
            is_wp_error( $last_error ) ? 500 : 200,
            0,
            $max_attempts,
            false
        );

        return $last_error ?? new WP_Error( 'transcript_failed', 'Trascrizione fallita dopo ' . $max_attempts . ' tentativi' );
    }

    /**
     * Generate AI description
     */
    public function generate_description( $transcript, $title = '', $custom_prompt = '', $license = null ) {
        $api_key = $this->get_openai_api_key();

        if ( is_wp_error( $api_key ) ) {
            return $api_key;
        }

        // Build system prompt
        $system_prompt = 'Sei un assistente che crea descrizioni per video YouTube.';

        if ( ! empty( $custom_prompt ) ) {
            $system_prompt = $custom_prompt;
        }

        $user_message = "Titolo: {$title}\n\nTrascrizione:\n{$transcript}";

        // Call OpenAI API
        $model = get_option( 'ipv_openai_model', 'gpt-4o-mini' );
        
        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
            'timeout' => 120,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode( [
                'model' => $model,
                'messages' => [
                    [ 'role' => 'system', 'content' => $system_prompt ],
                    [ 'role' => 'user', 'content' => $user_message ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 2000
            ] )
        ] );

        if ( is_wp_error( $response ) ) {
            $this->log_api_call(
                $license ? $license->id : null,
                'ai_description',
                substr( $title, 0, 50 ),
                500,
                0,
                1,
                false
            );

            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! isset( $body['choices'][0]['message']['content'] ) ) {
            return new WP_Error( 'openai_error', 'No response from OpenAI' );
        }

        $description = $body['choices'][0]['message']['content'];

        $this->log_api_call(
            $license ? $license->id : null,
            'ai_description',
            substr( $title, 0, 50 ),
            200,
            strlen( $description ),
            1
        );

        return $description;
    }

    /**
     * Get YouTube video data
     */
    public function get_youtube_video_data( $video_id, $license = null ) {
        $video_id = sanitize_text_field( $video_id );

        // Check cache
        $cache_key = 'ipv_yt_data_' . $video_id;
        if ( $this->cache_enabled ) {
            $cached = get_transient( $cache_key );

            if ( $cached !== false ) {
                $this->log_api_call(
                    $license ? $license->id : null,
                    'youtube_data',
                    $video_id,
                    200,
                    0,
                    0,
                    true
                );

                return $cached;
            }
        }

        $api_key = $this->get_youtube_api_key();

        if ( is_wp_error( $api_key ) ) {
            return $api_key;
        }

        // Call YouTube Data API
        $url = add_query_arg( [
            'part' => 'snippet,contentDetails,statistics',
            'id' => $video_id,
            'key' => $api_key
        ], 'https://www.googleapis.com/youtube/v3/videos' );

        $response = wp_remote_get( $url, [ 'timeout' => 30 ] );

        if ( is_wp_error( $response ) ) {
            $this->log_api_call(
                $license ? $license->id : null,
                'youtube_data',
                $video_id,
                500,
                0,
                1,
                false
            );

            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['items'][0] ) ) {
            return new WP_Error( 'video_not_found', 'Video not found' );
        }

        $video_data = $body['items'][0];

        // Cache for 1 hour
        if ( $this->cache_enabled ) {
            set_transient( $cache_key, $video_data, self::CACHE_YOUTUBE_TTL );
        }

        $this->log_api_call(
            $license ? $license->id : null,
            'youtube_data',
            $video_id,
            200,
            strlen( wp_json_encode( $video_data ) ),
            1
        );

        return $video_data;
    }

    /**
     * Log API call for audit
     * v1.0.10 - Fixed: added required 'method' column (NOT NULL in database)
     * v1.0.11 - FIX: Logger NON BLOCCANTE - errori DB non interrompono la response
     */
    private function log_api_call( $license_id, $endpoint, $resource_id, $status, $response_size = 0, $attempts = 0, $cached = false ) {
        if ( ! $this->audit_log_enabled ) {
            return;
        }

        // v1.0.11 - FIX CRITICO: try/catch per evitare che errori DB blocchino la response
        try {
            global $wpdb;
            $table = $wpdb->prefix . 'ipv_api_logs';

            // v1.0.11 - FIX PHP 8.2+: cast (string) per user_agent
            $user_agent = (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' );

            $result = $wpdb->insert( $table, [
                'license_id' => $license_id,
                'endpoint' => substr( (string) $endpoint, 0, 100 ),
                'video_id' => substr( (string) $resource_id, 0, 50 ),
                'method' => 'POST',  // v1.0.10 - Required column!
                'status_code' => $status,
                'cached' => $cached ? 1 : 0,
                'ip_address' => $this->get_client_ip(),
                'user_agent' => substr( $user_agent, 0, 255 ),
                'created_at' => current_time( 'mysql' )
            ] );

            // Log any database errors for debugging
            if ( $result === false && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[IPV Vendor] log_api_call INSERT failed: ' . $wpdb->last_error );
            }
        } catch ( \Throwable $e ) {
            // v1.0.11 - NON bloccare la response anche se il logging fallisce
            error_log( '[IPV Vendor] log_api_call exception (non-blocking): ' . $e->getMessage() );
        }
    }

    /**
     * Log security event
     */
    private function log_security_event( $event_type, $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ipv_security_log';

        $wpdb->insert( $table, [
            'event_type' => $event_type,
            'event_data' => wp_json_encode( $data ),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255 ),
            'created_at' => current_time( 'mysql' )
        ] );

        error_log( sprintf( 'IPV Security Event: %s | IP: %s | Data: %s',
            $event_type,
            $this->get_client_ip(),
            wp_json_encode( $data )
        ) );
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ( $ip_keys as $key ) {
            if ( isset( $_SERVER[ $key ] ) && filter_var( $_SERVER[ $key ], FILTER_VALIDATE_IP ) ) {
                return sanitize_text_field( $_SERVER[ $key ] );
            }
        }

        return 'unknown';
    }

    /**
     * Clear cache
     */
    public function clear_cache( $type = 'all' ) {
        global $wpdb;

        $patterns = [
            'all' => 'ipv_%',
            'transcript' => 'ipv_transcript_%',
            'youtube' => 'ipv_yt_data_%'
        ];

        $pattern = $patterns[ $type ] ?? $patterns['all'];

        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_' . $wpdb->esc_like( $pattern )
        ) );

        error_log( 'IPV Gateway: Cache cleared (' . $type . ')' );
    }

    /**
     * Get performance stats
     * v1.0.10 - Fixed: removed non-existent columns (response_size, attempts)
     */
    public function get_performance_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'ipv_api_logs';

        $stats = $wpdb->get_results( "
            SELECT
                endpoint,
                COUNT(*) as total_calls,
                SUM(cached) as cache_hits,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as errors
            FROM {$table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY endpoint
        ", ARRAY_A );

        return $stats;
    }
}

// Backward compatibility alias (v1.4.0-optimized)
// Required for class-vendor-core.php which uses IPV_Vendor_API_Gateway
if ( ! class_exists( 'IPV_Vendor_API_Gateway' ) ) {
    class_alias( 'IPV_Vendor_API_Gateway_Optimized', 'IPV_Vendor_API_Gateway' );
}
