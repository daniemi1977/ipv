<?php
/**
 * IPV API Gateway - VERSIONE SICURA
 *
 * Protegge le API keys server-side e gestisce tutte le chiamate a servizi esterni
 * NESSUNA API KEY HARDCODED - Tutto da wp_options!
 * 
 * @version 1.1.2-secure
 * @since 1.1.2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_API_Gateway {

    private static $instance = null;

    /**
     * API Keys - SOLO DA DATABASE!
     * Nessuna costante hardcoded per sicurezza
     */

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Restituisce la YouTube API key da database
     * 
     * @return string|WP_Error
     */
    private function get_youtube_api_key() {
        $key = trim( (string) get_option( 'ipv_youtube_api_key', '' ) );
        
        if ( empty( $key ) ) {
            error_log( 'IPV Vendor: YouTube API key non configurata in Impostazioni' );
            return new WP_Error(
                'missing_api_key',
                'YouTube API key non configurata. Vai su IPV Pro Vendor → Impostazioni.'
            );
        }
        
        return $key;
    }

    /**
     * Restituisce la OpenAI API key da database
     * 
     * @return string|WP_Error
     */
    private function get_openai_api_key() {
        $key = trim( (string) get_option( 'ipv_openai_api_key', '' ) );
        
        if ( empty( $key ) ) {
            error_log( 'IPV Vendor: AI API key non configurata in Impostazioni' );
            return new WP_Error(
                'missing_api_key',
                'AI API key non configurata. Vai su IPV Pro Vendor → Impostazioni.'
            );
        }
        
        return $key;
    }

    /**
     * Restituisce SupaData API key (v1.3.7 - Hardcoded con fallback)
     *
     * @return string|WP_Error
     */
    private function get_supadata_key() {
        // v1.3.7 - HARDCODED API Key (priorità massima)
        // Modifica questa costante per cambiare la chiave
        $hardcoded_key = 'sd_7183c8f8648e5f63ae3b758d2a950ef1';

        // Fallback: controlla wp_options (per flessibilità futura)
        $db_key = trim( (string) get_option( 'ipv_supadata_api_key_1', '' ) );

        // Priorità: hardcoded > database
        $api_key = ! empty( $hardcoded_key ) ? $hardcoded_key : $db_key;

        if ( empty( $api_key ) ) {
            error_log( 'IPV Vendor: Nessuna Transcription API key configurata' );
            return new WP_Error(
                'missing_api_key',
                'Transcription API key non configurata. Vai su IPV Pro Vendor → Impostazioni.'
            );
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'IPV Vendor: Using SupaData API key: ' . substr( $api_key, 0, 10 ) . '...' );
        }

        return $api_key;
    }

    /**
     * Check if API keys are configured
     * 
     * @return array Status of each API key
     */
    public function check_api_keys_status() {
        return [
            'youtube' => ! empty( get_option( 'ipv_youtube_api_key', '' ) ),
            'openai' => ! empty( get_option( 'ipv_openai_api_key', '' ) ),
            'supadata_1' => ! empty( get_option( 'ipv_supadata_api_key_1', '' ) ),
            'supadata_2' => ! empty( get_option( 'ipv_supadata_api_key_2', '' ) ),
            'supadata_3' => ! empty( get_option( 'ipv_supadata_api_key_3', '' ) ),
        ];
    }

    /**
     * Get transcript via SupaData
     */
    public function get_transcript( $video_id, $mode = 'auto', $lang = 'it', $license = null ) {
        // Sanitize inputs
        $video_id = sanitize_text_field( $video_id );
        $mode = in_array( $mode, [ 'auto', 'whisper', 'hybrid' ] ) ? $mode : 'auto';
        $lang = sanitize_text_field( $lang );

        // Check cache first
        $cached = $this->get_cached_transcript( $video_id, $mode, $lang );
        if ( $cached ) {
            $this->log_api_call(
                $license ? $license->id : null,
                'transcript',
                $video_id,
                200,
                0,
                0,
                true
            );
            return $cached;
        }

        // Get API key
        $api_key = $this->get_supadata_key();
        if ( is_wp_error( $api_key ) ) {
            return $api_key;
        }

        // v1.3.8 - Enhanced SupaData logging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '=== SUPADATA API CALL START ===' );
            error_log( 'Video ID: ' . $video_id );
            error_log( 'Mode: ' . $mode );
            error_log( 'Language: ' . $lang );
            error_log( 'SupaData API Key: ' . substr( $api_key, 0, 15 ) . '...' . substr( $api_key, -5 ) );
        }

        // Call SupaData
        $start_time = microtime( true );

        // v1.3.10 - Mappa le modalità interne su quelle accettate da SupaData
        // - 'auto'    -> 'auto'
        // - 'whisper' (forza AI) -> 'generate'
        // - 'hybrid'  (auto + fallback) -> 'auto'
        $supadata_mode = 'auto';
        if ( $mode === 'whisper' ) {
            $supadata_mode = 'generate';
        } elseif ( $mode === 'hybrid' ) {
            $supadata_mode = 'auto';
        }

        // Costruisci la URL YouTube a partire dal video_id
        $video_url = 'https://www.youtube.com/watch?v=' . $video_id;

        // v1.3.10 - Allineamento completo alla nuova API SupaData /v1/transcript
        // Parametri corretti: url, lang, text, mode
        $url = add_query_arg( [
            'url'  => rawurlencode( $video_url ),
            'lang' => $lang,
            'mode' => $supadata_mode,
            'text' => 'true', // vogliamo testo pulito direttamente
        ], 'https://api.supadata.ai/v1/transcript' );

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'SupaData Request URL: ' . $url );
        }

        $response = wp_remote_get( $url, [
            'headers' => [
                'x-api-key' => $api_key,
                'Accept' => 'application/json'
            ],
            'timeout' => 180
        ]);

        $response_time = round( ( microtime( true ) - $start_time ) * 1000 );

        if ( is_wp_error( $response ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '❌ SUPADATA ERROR (WP_Error): ' . $response->get_error_message() );
            }
            $this->log_api_call(
                $license ? $license->id : null,
                'transcript',
                $video_id,
                500,
                $response_time
            );
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        // v1.3.8 - Log SupaData response
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'SupaData Response Status: ' . $status_code );
            error_log( 'SupaData Response Body: ' . wp_json_encode( $body ) );
            error_log( 'Response Time: ' . $response_time . 'ms' );
        }

        // v1.3.10 - Gestire 200 (immediato) e 202 (asincrono)
        if ( $status_code !== 200 && $status_code !== 202 ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '❌ SUPADATA RETURNED ERROR STATUS: ' . $status_code );
                error_log( 'Error Message: ' . ( $body['error'] ?? $body['message'] ?? 'Unknown error' ) );
            }
            $this->log_api_call(
                $license ? $license->id : null,
                'transcript',
                $video_id,
                $status_code,
                $response_time
            );
            return new WP_Error(
                'transcription_error',
                $body['error'] ?? $body['message'] ?? 'Errore servizio trascrizione',
                [ 'status' => $status_code ]
            );
        }

        // v1.3.10 - Gestione asincrona/sincrona secondo la nuova API SupaData
        // 202 + jobId => polling, 200 => content diretto
        if ( $status_code === 202 && isset( $body['jobId'] ) ) {
            $transcript = $this->poll_supadata_job( $body['jobId'], $api_key );
            if ( is_wp_error( $transcript ) ) {
                return $transcript;
            }
        } else {
            // Risposta immediata 200: il testo sta in "content"
            $transcript = $body['content'] ?? '';
        }

        if ( empty( $transcript ) ) {
            return new WP_Error( 'no_transcript', 'Nessuna trascrizione ricevuta dal servizio' );
        }

        // Cache transcript
        $this->cache_transcript( $video_id, $mode, $lang, $transcript );

        // Log API call
        $this->log_api_call(
            $license ? $license->id : null,
            'transcript',
            $video_id,
            $status_code,
            $response_time,
            1
        );

        return $transcript;
    }

    /**
     * Poll SupaData job (for long videos)
     */
    private function poll_supadata_job( $job_id, $api_key, $max_attempts = 30 ) {
        $job_id = sanitize_text_field( $job_id );
        
        for ( $i = 0; $i < $max_attempts; $i++ ) {
            sleep( 5 );

            $response = wp_remote_get(
                'https://api.supadata.ai/v1/transcript/' . $job_id,  // v1.3.10 - Endpoint corretto
                [
                    'headers' => [
                        'x-api-key' => $api_key,  // v1.3.9 - Fix per SupaData
                        'Accept' => 'application/json'
                    ],
                    'timeout' => 30
                ]
            );

            if ( is_wp_error( $response ) ) {
                continue;
            }

            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            $status = $body['status'] ?? 'unknown';

            if ( $status === 'completed' ) {
                // v1.3.10 - La nuova API usa "content" al posto di "transcript"
                if ( isset( $body['content'] ) ) {
                    // Può essere stringa pura o array di chunk
                    if ( is_array( $body['content'] ) ) {
                        // Casi in cui content è un array di { text, offset, duration, lang }
                        $pieces = [];
                        foreach ( $body['content'] as $chunk ) {
                            if ( isset( $chunk['text'] ) ) {
                                $pieces[] = trim( (string) $chunk['text'] );
                            }
                        }
                        return implode( "\n", $pieces );
                    }
                    return (string) $body['content'];
                }
                return '';
            }

            if ( $status === 'failed' ) {
                return new WP_Error(
                    'job_failed',
                    $body['error'] ?? 'Job trascrizione fallito'
                );
            }
        }

        return new WP_Error( 'timeout', 'Timeout polling job trascrizione' );
    }

    /**
     * Generate AI description via OpenAI
     */
    public function generate_description( $transcript, $title = '', $custom_prompt = '', $license = null ) {
        // Get API key
        $api_key = $this->get_openai_api_key();
        if ( is_wp_error( $api_key ) ) {
            return $api_key;
        }

        // Sanitize inputs
        $title = sanitize_text_field( $title );
        
        // Truncate if too long (OpenAI has token limits)
        if ( strlen( $transcript ) > 12000 ) {
            $transcript = substr( $transcript, 0, 12000 ) . '...';
        }

        // Build prompt
        if ( empty( $custom_prompt ) ) {
            $prompt = $this->get_default_prompt( $transcript, $title );
        } else {
            $prompt = str_replace(
                [ '{transcript}', '{title}' ],
                [ $transcript, $title ],
                $custom_prompt
            );
        }

        $start_time = microtime( true );

        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => wp_json_encode([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Sei un esperto copywriter per YouTube. Scrivi descrizioni professionali, coinvolgenti e SEO-friendly. Scrivi in italiano.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 500,
                'temperature' => 0.7
            ]),
            'timeout' => 60
        ]);

        $response_time = round( ( microtime( true ) - $start_time ) * 1000 );

        if ( is_wp_error( $response ) ) {
            $this->log_api_call(
                $license ? $license->id : null,
                'description',
                '',
                500,
                $response_time
            );
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $this->log_api_call(
                $license ? $license->id : null,
                'description',
                '',
                $status_code,
                $response_time
            );
            return new WP_Error(
                'ai_error',
                $body['error']['message'] ?? 'Errore servizio AI',
                [ 'status' => $status_code ]
            );
        }

        $description = $body['choices'][0]['message']['content'] ?? '';

        if ( empty( $description ) ) {
            return new WP_Error( 'no_description', 'Nessuna descrizione generata dal servizio AI' );
        }

        // Log (AI non costa crediti, incluso nel servizio)
        $this->log_api_call(
            $license ? $license->id : null,
            'description',
            '',
            $status_code,
            $response_time,
            0
        );

        return $description;
    }

    /**
     * Cache transcript
     */
    private function cache_transcript( $video_id, $mode, $lang, $transcript ) {
        global $wpdb;

        $wpdb->replace(
            $wpdb->prefix . 'ipv_transcript_cache',
            [
                'video_id' => $video_id,
                'mode' => $mode,
                'lang' => $lang,
                'transcript' => $transcript
            ],
            [ '%s', '%s', '%s', '%s' ]
        );
    }

    /**
     * Get cached transcript
     */
    private function get_cached_transcript( $video_id, $mode, $lang ) {
        global $wpdb;

        $cached = $wpdb->get_row( $wpdb->prepare(
            "SELECT transcript FROM {$wpdb->prefix}ipv_transcript_cache
            WHERE video_id = %s AND mode = %s AND lang = %s
            AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)",
            $video_id,
            $mode,
            $lang
        ));

        return $cached ? $cached->transcript : null;
    }

    /**
     * Log API call
     */
    private function log_api_call(
        $license_id,
        $endpoint,
        $video_id,
        $status_code,
        $response_time,
        $credits_used = 0,
        $cached = false
    ) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'ipv_api_logs',
            [
                'license_id' => $license_id,
                'endpoint' => sanitize_text_field( $endpoint ),
                'video_id' => sanitize_text_field( $video_id ),
                'method' => isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( $_SERVER['REQUEST_METHOD'] ) : 'POST',
                'status_code' => (int) $status_code,
                'response_time' => (int) $response_time,
                'credits_used' => (int) $credits_used,
                'cached' => $cached ? 1 : 0,
                'ip_address' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '',
                'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ), 0, 255 ) : ''
            ],
            [ '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s' ]
        );
    }

    /**
     * Default AI prompt for descriptions
     */
    private function get_default_prompt( $transcript, $title = '' ) {
        $title_context = $title ? "Titolo video: \"{$title}\"\n\n" : '';

        return $title_context .
               "Basandoti su questa trascrizione, scrivi una descrizione YouTube professionale (2-3 paragrafi):\n\n" .
               "{$transcript}\n\n" .
               "La descrizione deve:\n" .
               "1. Iniziare con un hook accattivante\n" .
               "2. Riassumere i punti chiave del video\n" .
               "3. Usare tono informativo ma accessibile\n" .
               "4. Call-to-action finale per iscriversi\n" .
               "5. NO emoji\n" .
               "6. SEO-friendly con keywords naturali\n\n" .
               "Scrivi SOLO la descrizione, senza titolo o prefissi.";
    }

    /**
     * Clear old cache (run via cron)
     */
    public static function clear_old_cache() {
        global $wpdb;

        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->prefix}ipv_transcript_cache
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        if ( $deleted > 0 ) {
            error_log( sprintf( 'IPV Vendor: Cleared %d old transcript cache entries', $deleted ) );
        }
    }
}
