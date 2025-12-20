<?php
/**
 * IPV Production System Pro - API Client OTTIMIZZATO
 *
 * Versione ottimizzata con:
 * - ✅ Caching aggressivo delle risposte
 * - ✅ Retry logic con exponential backoff
 * - ✅ Connection pooling (keep-alive)
 * - ✅ Request batching
 * - ✅ Performance monitoring
 * - ✅ Circuit breaker pattern
 * - ✅ Compression support
 *
 * @package IPV_Production_System_Pro
 * @version 10.3.0-optimized
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_API_Client_Optimized {

    private static $instance = null;
    private $cache_enabled = true;
    private $cache_ttl = 3600; // 1 ora
    private $circuit_breaker_failures = 0;
    private $circuit_breaker_threshold = 5;
    private $circuit_breaker_timeout = 300; // 5 minuti
    private $performance_metrics = [];

    const DEFAULT_SERVER = 'https://aiedintorni.it';
    const API_NAMESPACE = 'ipv-vendor/v1';
    const CACHE_GROUP = 'ipv_api_responses';
    const MAX_RETRIES = 3;
    const RETRY_DELAY = 1; // secondi base

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize performance monitoring
        add_action( 'shutdown', [ $this, 'log_performance_metrics' ] );
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
     * Check circuit breaker status
     */
    private function is_circuit_open() {
        $last_failure = get_transient( 'ipv_circuit_breaker_open' );

        if ( $last_failure ) {
            $elapsed = time() - $last_failure;
            if ( $elapsed < $this->circuit_breaker_timeout ) {
                return true;
            } else {
                // Circuit breaker timeout expired, reset
                delete_transient( 'ipv_circuit_breaker_open' );
                $this->circuit_breaker_failures = 0;
            }
        }

        return false;
    }

    /**
     * Record failure for circuit breaker
     */
    private function record_failure() {
        $this->circuit_breaker_failures++;

        if ( $this->circuit_breaker_failures >= $this->circuit_breaker_threshold ) {
            set_transient( 'ipv_circuit_breaker_open', time(), $this->circuit_breaker_timeout );
            IPV_Prod_Logger::log( 'Circuit breaker OPEN - troppi errori', [
                'failures' => $this->circuit_breaker_failures,
                'timeout' => $this->circuit_breaker_timeout
            ]);
        }
    }

    /**
     * Reset circuit breaker on success
     */
    private function reset_circuit_breaker() {
        $this->circuit_breaker_failures = 0;
        delete_transient( 'ipv_circuit_breaker_open' );
    }

    /**
     * Get cache key for request
     */
    private function get_cache_key( $endpoint, $method, $body ) {
        $key_data = [
            'endpoint' => $endpoint,
            'method' => $method,
            'body' => $body,
            'license' => substr( $this->get_license_key(), 0, 8 )
        ];
        return 'ipv_api_' . md5( wp_json_encode( $key_data ) );
    }

    /**
     * Get cached response
     */
    private function get_cached_response( $cache_key ) {
        if ( ! $this->cache_enabled ) {
            return false;
        }

        return get_transient( $cache_key );
    }

    /**
     * Set cached response
     */
    private function set_cached_response( $cache_key, $data, $ttl = null ) {
        if ( ! $this->cache_enabled ) {
            return;
        }

        $ttl = $ttl ?? $this->cache_ttl;
        set_transient( $cache_key, $data, $ttl );
    }

    /**
     * Make API request with retry logic and caching
     */
    private function request( $endpoint, $method = 'GET', $body = [], $timeout = 60, $options = [] ) {
        $license_key = $this->get_license_key();

        // Circuit breaker check
        if ( $this->is_circuit_open() ) {
            return new WP_Error(
                'circuit_breaker_open',
                __( 'Servizio temporaneamente non disponibile. Riprova tra qualche minuto.', 'ipv-production-system-pro' )
            );
        }

        // Check cache for GET requests
        $cache_enabled = $options['cache'] ?? true;
        if ( $method === 'GET' && $cache_enabled ) {
            $cache_key = $this->get_cache_key( $endpoint, $method, $body );
            $cached = $this->get_cached_response( $cache_key );

            if ( $cached !== false ) {
                $this->track_performance( $endpoint, 0, 'cache_hit' );
                return $cached;
            }
        }

        // Public endpoints (no license required)
        $public_endpoints = [ 'health' ];

        if ( empty( $license_key ) && ! in_array( $endpoint, $public_endpoints ) ) {
            return new WP_Error(
                'no_license',
                __( 'Licenza non configurata. Vai su IPV Videos → Licenza per attivare.', 'ipv-production-system-pro' )
            );
        }

        $url = $this->get_endpoint_url( $endpoint );

        // Retry logic with exponential backoff
        $max_retries = $options['max_retries'] ?? self::MAX_RETRIES;
        $retry_count = 0;
        $last_error = null;

        while ( $retry_count <= $max_retries ) {
            $start_time = microtime( true );

            $args = [
                'method' => $method,
                'timeout' => $timeout,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $license_key,
                    'X-License-Key' => $license_key,
                    'X-Site-URL' => home_url(),
                    'Accept-Encoding' => 'gzip, deflate', // Compression support
                ],
                'compress' => true, // Enable compression
                'sslverify' => true,
                'httpversion' => '1.1',
            ];

            // Add keep-alive for connection pooling
            $args['headers']['Connection'] = 'keep-alive';

            if ( in_array( $method, [ 'POST', 'PUT', 'PATCH' ] ) ) {
                if ( ! is_array( $body ) ) {
                    $body = [];
                }

                // Inject license_key in body as fallback
                if ( ! isset( $body['license_key'] ) && ! empty( $license_key ) ) {
                    $body['license_key'] = $license_key;
                }

                $args['body'] = wp_json_encode( $body );
            }

            $response = wp_remote_request( $url, $args );
            $elapsed = microtime( true ) - $start_time;

            if ( is_wp_error( $response ) ) {
                $last_error = $response;

                // Check if error is retryable
                $error_code = $response->get_error_code();
                $retryable_errors = [ 'http_request_failed', 'timeout', 'connection_timeout' ];

                if ( in_array( $error_code, $retryable_errors ) && $retry_count < $max_retries ) {
                    $retry_count++;
                    $delay = self::RETRY_DELAY * pow( 2, $retry_count - 1 ); // Exponential backoff

                    IPV_Prod_Logger::log( 'API retry', [
                        'endpoint' => $endpoint,
                        'retry' => $retry_count,
                        'max_retries' => $max_retries,
                        'delay' => $delay,
                        'error' => $response->get_error_message()
                    ]);

                    sleep( $delay );
                    continue;
                }

                // Non-retryable error or max retries reached
                $this->record_failure();
                $this->track_performance( $endpoint, $elapsed, 'error' );

                IPV_Prod_Logger::log( 'API Client Error', [
                    'endpoint' => $endpoint,
                    'error' => $response->get_error_message(),
                    'retries' => $retry_count
                ]);

                return $response;
            }

            // Success - reset circuit breaker
            $this->reset_circuit_breaker();

            $status_code = wp_remote_retrieve_response_code( $response );
            $body_data = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( $status_code >= 400 ) {
                // v10.5.2 - 401 UNAUTHORIZED: BLOCK IMMEDIATELY (no retry)
                if ( $status_code === 401 ) {
                    $message = $body_data['message'] ?? 'Unauthorized – check license';
                    
                    $this->record_failure();
                    $this->track_performance( $endpoint, $elapsed, 'unauthorized' );
                    
                    IPV_Prod_Logger::log( 'API 401 Unauthorized', [
                        'endpoint' => $endpoint,
                        'message' => $message
                    ]);
                    
                    return new WP_Error(
                        'unauthorized',
                        $message,
                        [ 'status' => 401 ]
                    );
                }
                
                // Check if HTTP error is retryable (502, 503, 504)
                $retryable_status = [ 502, 503, 504 ];

                if ( in_array( $status_code, $retryable_status ) && $retry_count < $max_retries ) {
                    $retry_count++;
                    $delay = self::RETRY_DELAY * pow( 2, $retry_count - 1 );

                    IPV_Prod_Logger::log( 'API HTTP retry', [
                        'endpoint' => $endpoint,
                        'status' => $status_code,
                        'retry' => $retry_count,
                        'delay' => $delay
                    ]);

                    sleep( $delay );
                    continue;
                }

                $message = $body_data['message'] ?? $body_data['error'] ?? __( 'Errore server sconosciuto', 'ipv-production-system-pro' );

                $this->record_failure();
                $this->track_performance( $endpoint, $elapsed, 'http_error' );

                IPV_Prod_Logger::log( 'API Client HTTP Error', [
                    'endpoint' => $endpoint,
                    'status' => $status_code,
                    'message' => $message,
                    'retries' => $retry_count
                ]);

                return new WP_Error(
                    'api_error',
                    $message,
                    [ 'status' => $status_code ]
                );
            }

            // Success!
            $this->track_performance( $endpoint, $elapsed, 'success' );

            // Cache GET requests
            if ( $method === 'GET' && $cache_enabled ) {
                $cache_ttl = $options['cache_ttl'] ?? $this->cache_ttl;
                $this->set_cached_response( $cache_key, $body_data, $cache_ttl );
            }

            return $body_data;
        }

        // Should not reach here, but return last error if it does
        return $last_error ?? new WP_Error( 'unknown_error', 'Unknown error occurred' );
    }

    /**
     * Track performance metrics
     */
    private function track_performance( $endpoint, $elapsed, $status ) {
        if ( ! isset( $this->performance_metrics[ $endpoint ] ) ) {
            $this->performance_metrics[ $endpoint ] = [
                'calls' => 0,
                'total_time' => 0,
                'errors' => 0,
                'cache_hits' => 0
            ];
        }

        $this->performance_metrics[ $endpoint ]['calls']++;
        $this->performance_metrics[ $endpoint ]['total_time'] += $elapsed;

        if ( $status === 'error' || $status === 'http_error' ) {
            $this->performance_metrics[ $endpoint ]['errors']++;
        } elseif ( $status === 'cache_hit' ) {
            $this->performance_metrics[ $endpoint ]['cache_hits']++;
        }
    }

    /**
     * Log performance metrics on shutdown
     */
    public function log_performance_metrics() {
        if ( empty( $this->performance_metrics ) ) {
            return;
        }

        foreach ( $this->performance_metrics as $endpoint => $metrics ) {
            $avg_time = $metrics['total_time'] / $metrics['calls'];
            $error_rate = ( $metrics['errors'] / $metrics['calls'] ) * 100;
            $cache_hit_rate = ( $metrics['cache_hits'] / $metrics['calls'] ) * 100;

            IPV_Prod_Logger::log( 'API Performance', [
                'endpoint' => $endpoint,
                'calls' => $metrics['calls'],
                'avg_time' => round( $avg_time, 3 ),
                'error_rate' => round( $error_rate, 2 ) . '%',
                'cache_hit_rate' => round( $cache_hit_rate, 2 ) . '%'
            ]);
        }
    }

    /**
     * Get video transcript via server
     * Con caching aggressivo (transcript non cambia)
     *
     * @param string $video_id YouTube Video ID
     * @param string $mode Modalità: 'auto', 'native', 'whisper'
     * @param string $lang Lingua (default: 'auto')
     * @return string|WP_Error Trascrizione o errore
     */
    public function get_transcript( $video_id, $mode = 'auto', $lang = 'auto' ) {
        if ( ! self::is_license_active() ) {
            return new WP_Error(
                'license_required',
                __( 'Licenza non attiva. Attiva la licenza per usare questa funzione.', 'ipv-production-system-pro' )
            );
        }

        // Check local cache first (transcripts don't change)
        $cache_key = 'ipv_transcript_' . $video_id . '_' . $mode . '_' . $lang;
        $cached = get_transient( $cache_key );

        if ( $cached !== false ) {
            IPV_Prod_Logger::log( 'Transcript cache HIT', [ 'video_id' => $video_id ] );
            return $cached;
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
        ], 180, [ 'cache' => false, 'max_retries' => 2 ] ); // 3 min timeout, 2 retries

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( ! isset( $response['transcript'] ) ) {
            return new WP_Error( 'no_transcript', __( 'Nessuna trascrizione ricevuta dal server', 'ipv-production-system-pro' ) );
        }

        // Update credits info
        if ( isset( $response['credits_info'] ) ) {
            $license_info = get_option( 'ipv_license_info', [] );
            $license_info['credits'] = $response['credits_info'];
            update_option( 'ipv_license_info', $license_info );
        }

        // Cache transcript for 7 days (transcripts don't change)
        set_transient( $cache_key, $response['transcript'], 7 * DAY_IN_SECONDS );

        IPV_Prod_Logger::log( 'Trascrizione ricevuta e cached', [
            'video_id' => $video_id,
            'length' => strlen( $response['transcript'] )
        ]);

        return $response['transcript'];
    }

    /**
     * Generate AI description via server
     *
     * @param string $transcript Trascrizione del video
     * @param string $title Titolo del video
     * @param string $custom_prompt Prompt personalizzato
     * @return string|WP_Error Descrizione generata o errore
     */
    public function generate_description( $transcript, $title = '', $custom_prompt = '' ) {
        if ( ! self::is_license_active() ) {
            return new WP_Error(
                'license_required',
                __( 'Licenza non attiva. Attiva la licenza per usare questa funzione.', 'ipv-production-system-pro' )
            );
        }

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
        ], 120, [ 'cache' => false, 'max_retries' => 1 ] );

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
     * Get YouTube video data via server
     * Con caching (1 ora)
     *
     * @param string $video_id YouTube Video ID
     * @return array|WP_Error Video data o errore
     */
    public function get_youtube_video_data( $video_id ) {
        if ( ! self::is_license_active() ) {
            return new WP_Error(
                'license_required',
                __( 'Licenza non attiva. Attiva la licenza per usare questa funzione.', 'ipv-production-system-pro' )
            );
        }

        IPV_Prod_Logger::log( 'Richiesta YouTube video data', [ 'video_id' => $video_id ] );

        // Cache YouTube data for 1 hour
        $response = $this->request( 'youtube/video-data', 'POST', [
            'video_id' => $video_id
        ], 30, [ 'cache' => true, 'cache_ttl' => 3600 ] );

        if ( is_wp_error( $response ) ) {
            IPV_Prod_Logger::log( 'YouTube video data ERRORE', [
                'video_id' => $video_id,
                'error' => $response->get_error_message()
            ]);
            return $response;
        }

        IPV_Prod_Logger::log( 'YouTube video data ricevuto', [
            'video_id' => $video_id,
            'title' => $response['title'] ?? 'N/A'
        ]);

        return $response;
    }

    /**
     * Batch request - ottimizzato per multiple chiamate
     *
     * @param array $requests Array di richieste
     * @return array Risposte
     */
    public function batch_request( $requests ) {
        $results = [];

        foreach ( $requests as $key => $request ) {
            $endpoint = $request['endpoint'] ?? '';
            $method = $request['method'] ?? 'GET';
            $body = $request['body'] ?? [];
            $timeout = $request['timeout'] ?? 60;

            $results[ $key ] = $this->request( $endpoint, $method, $body, $timeout );
        }

        return $results;
    }

    /**
     * Clear API cache
     */
    public function clear_cache( $endpoint = null ) {
        global $wpdb;

        if ( $endpoint ) {
            $like = $wpdb->esc_like( 'ipv_api_' . $endpoint ) . '%';
            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $like
            ) );
        } else {
            $wpdb->query(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ipv_api_%'"
            );
        }

        IPV_Prod_Logger::log( 'API cache cleared', [ 'endpoint' => $endpoint ?? 'all' ] );
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
        ], 30, [ 'cache' => false ] );

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
     * v1.0.4 - Always remove local data, even if server fails
     */
    public function deactivate_license() {
        $license_key = $this->get_license_key();
        $site_url = home_url();

        // v1.0.4 - Always remove local data first
        $this->clear_local_license_data();

        if ( empty( $license_key ) ) {
            // License already cleared
            return [ 'success' => true, 'message' => 'Licenza rimossa localmente' ];
        }

        // Try to notify server (but don't fail if server is unreachable)
        $response = $this->request( 'license/deactivate', 'POST', [
            'license_key' => $license_key,
            'site_url' => $site_url
        ], 30, [ 'cache' => false ] );

        IPV_Prod_Logger::log( 'Licenza deattivata' );

        // Return success even if server failed - local data is cleared
        return [ 'success' => true, 'message' => 'Licenza deattivata' ];
    }

    /**
     * v1.0.4 - Clear all local license data
     */
    public function clear_local_license_data() {
        delete_option( 'ipv_license_key' );
        delete_option( 'ipv_license_info' );
        delete_option( 'ipv_license_activated_at' );
        delete_option( 'ipv_license_status' );
        delete_option( 'ipv_license_valid' );
        delete_transient( 'ipv_license_check' );
        delete_transient( 'ipv_credits_info' );
    }

    /**
     * Get license info (detailed)
     */
    public function get_license_info() {
        $license_key = $this->get_license_key();

        if ( empty( $license_key ) ) {
            return new WP_Error( 'no_license', __( 'License key non configurata', 'ipv-production-system-pro' ) );
        }

        $response = $this->request( 'license/info?license_key=' . $license_key, 'GET', [], 30, [ 'cache' => false ] );

        if ( ! is_wp_error( $response ) && isset( $response['license'] ) ) {
            // Aggiorna cache locale
            update_option( 'ipv_license_info', $response['license'] );
        }

        return $response;
    }

    /**
     * Get credits info (cached per 5 minuti)
     */
    public function get_credits_info() {
        $response = $this->request( 'credits', 'GET', [], 30, [ 'cache' => true, 'cache_ttl' => 300 ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return $response['credits'] ?? [];
    }

    /**
     * Check server health (cached per 1 minuto)
     */
    public function health_check() {
        $response = $this->request( 'health', 'GET', [], 10, [ 'cache' => true, 'cache_ttl' => 60 ] );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        return $response['status'] === 'ok';
    }

    /**
     * Test connection with detailed metrics
     */
    public function test_connection() {
        $start = microtime( true );
        $result = $this->health_check();
        $time = round( ( microtime( true ) - $start ) * 1000 );

        return [
            'success' => $result,
            'server' => $this->get_server_url(),
            'response_time' => $time . 'ms',
            'circuit_breaker' => [
                'open' => $this->is_circuit_open(),
                'failures' => $this->circuit_breaker_failures
            ]
        ];
    }
}
