<?php
/**
 * IPV API Gateway
 *
 * Protegge le API keys server-side e gestisce tutte le chiamate a servizi esterni
 * IMPORTANTE: Questo file deve rimanere sul server! Mai distribuire nel plugin client!
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_API_Gateway {

    private static $instance = null;

    // ==========================================
    // API KEYS (PROTETTE - SOLO SERVER-SIDE!)
    // ==========================================
    // TODO: Configurare queste chiavi prima del deploy!
    // Le chiavi NON devono MAI essere incluse nel plugin client

    const YOUTUBE_API_KEY = 'YOUR_YOUTUBE_API_KEY_HERE';
    const SUPADATA_API_KEY_1 = 'sd_YOUR_SUPADATA_KEY_1_HERE';
    const SUPADATA_API_KEY_2 = 'sd_YOUR_SUPADATA_KEY_2_HERE';
    const SUPADATA_API_KEY_3 = 'sd_YOUR_SUPADATA_KEY_3_HERE';
    const OPENAI_API_KEY = 'sk-proj-YOUR_OPENAI_KEY_HERE';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get transcript via SupaData
     */
    public function get_transcript( $video_id, $mode = 'auto', $lang = 'it', $license = null ) {

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

        // Call SupaData
        $start_time = microtime( true );

        $api_key = $this->get_supadata_key();

        $response = wp_remote_post( 'https://api.supadata.ai/v1/transcribe', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'video_id' => $video_id,
                'mode' => $mode,
                'language' => $lang
            ]),
            'timeout' => 180
        ]);

        $response_time = round( ( microtime( true ) - $start_time ) * 1000 );

        if ( is_wp_error( $response ) ) {
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

        if ( $status_code !== 200 ) {
            $this->log_api_call(
                $license ? $license->id : null,
                'transcript',
                $video_id,
                $status_code,
                $response_time
            );
            return new WP_Error(
                'supadata_error',
                $body['error'] ?? 'Errore SupaData',
                [ 'status' => $status_code ]
            );
        }

        // Check if async job
        if ( isset( $body['job_id'] ) ) {
            $transcript = $this->poll_supadata_job( $body['job_id'], $api_key );
            if ( is_wp_error( $transcript ) ) {
                return $transcript;
            }
        } else {
            $transcript = $body['transcript'] ?? '';
        }

        if ( empty( $transcript ) ) {
            return new WP_Error( 'no_transcript', 'Nessuna trascrizione ricevuta da SupaData' );
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
        for ( $i = 0; $i < $max_attempts; $i++ ) {
            sleep( 5 );

            $response = wp_remote_get(
                'https://api.supadata.ai/v1/jobs/' . $job_id,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $api_key
                    ]
                ]
            );

            if ( is_wp_error( $response ) ) {
                continue;
            }

            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            $status = $body['status'] ?? 'unknown';

            if ( $status === 'completed' ) {
                return $body['transcript'] ?? '';
            }

            if ( $status === 'failed' ) {
                return new WP_Error(
                    'job_failed',
                    $body['error'] ?? 'Job SupaData fallito'
                );
            }
        }

        return new WP_Error( 'timeout', 'Timeout polling job SupaData' );
    }

    /**
     * Generate AI description via OpenAI
     */
    public function generate_description( $transcript, $title = '', $custom_prompt = '', $license = null ) {

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
                'Authorization' => 'Bearer ' . self::OPENAI_API_KEY,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Sei un esperto copywriter per YouTube specializzato in esoterismo, spiritualità, misteri e geopolitica per il canale "Il Punto di Vista". Scrivi in italiano.'
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
                'openai_error',
                $body['error']['message'] ?? 'Errore OpenAI',
                [ 'status' => $status_code ]
            );
        }

        $description = $body['choices'][0]['message']['content'] ?? '';

        if ( empty( $description ) ) {
            return new WP_Error( 'no_description', 'Nessuna descrizione generata da OpenAI' );
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
     * Get SupaData API key with rotation
     */
    private function get_supadata_key() {
        $rotation_mode = get_option( 'ipv_supadata_rotation_mode', 'fixed' );

        if ( $rotation_mode === 'round-robin' ) {
            $current_key = get_option( 'ipv_supadata_current_key', 1 );
            $next_key = $current_key >= 3 ? 1 : $current_key + 1;
            update_option( 'ipv_supadata_current_key', $next_key );

            $keys = [
                1 => self::SUPADATA_API_KEY_1,
                2 => self::SUPADATA_API_KEY_2,
                3 => self::SUPADATA_API_KEY_3
            ];
            return $keys[ $current_key ];
        }

        // Fixed mode - use key 1
        return self::SUPADATA_API_KEY_1;
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
                'endpoint' => $endpoint,
                'video_id' => $video_id,
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'POST',
                'status_code' => $status_code,
                'response_time' => $response_time,
                'credits_used' => $credits_used,
                'cached' => $cached ? 1 : 0,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255 )
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

        $wpdb->query(
            "DELETE FROM {$wpdb->prefix}ipv_transcript_cache
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
    }
}
