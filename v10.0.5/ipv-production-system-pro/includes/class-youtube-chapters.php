<?php
/**
 * IPV Production System Pro - YouTube Chapters API
 *
 * Recupera i capitoli nativi di YouTube usando API third-party
 * Fallback: genera con AI se non disponibili
 *
 * @package IPV_Production_System_Pro
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_YouTube_Chapters {

    /**
     * API endpoint per recuperare i capitoli
     * Usa yt.lemnoslife.com API (free, no auth required)
     */
    const API_ENDPOINT = 'https://yt.lemnoslife.com/videos';

    /**
     * Timeout per richieste HTTP
     */
    const REQUEST_TIMEOUT = 15;

    /**
     * Recupera i capitoli di un video YouTube
     *
     * @param string $video_id YouTube Video ID
     * @return array|WP_Error Array di capitoli o errore
     *                         Format: [['time' => '0:00', 'title' => 'Intro'], ...]
     */
    public static function get_chapters( $video_id ) {
        if ( empty( $video_id ) ) {
            return new WP_Error( 'ipv_chapters_invalid_id', 'Video ID non valido.' );
        }

        // Costruisci URL API
        $url = add_query_arg(
            [
                'part' => 'chapters',
                'id'   => $video_id,
            ],
            self::API_ENDPOINT
        );

        IPV_Prod_Logger::log( 'YouTube Chapters API: Recupero capitoli', [
            'video_id' => $video_id,
            'url'      => $url,
        ] );

        // Chiamata HTTP
        $response = wp_remote_get( $url, [
            'timeout' => self::REQUEST_TIMEOUT,
            'headers' => [
                'User-Agent' => 'IPV-Production-System-Pro/7.8.0; WordPress',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            IPV_Prod_Logger::log( 'YouTube Chapters API: Errore HTTP', [
                'error' => $response->get_error_message(),
            ] );
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body        = wp_remote_retrieve_body( $response );

        if ( $status_code !== 200 ) {
            IPV_Prod_Logger::log( 'YouTube Chapters API: Status code non 200', [
                'status' => $status_code,
                'body'   => substr( $body, 0, 200 ),
            ] );
            return new WP_Error( 'ipv_chapters_api_error', "API error: HTTP $status_code" );
        }

        // Decodifica JSON
        $data = json_decode( $body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'ipv_chapters_json_error', 'Errore parsing JSON: ' . json_last_error_msg() );
        }

        // Verifica struttura risposta
        if ( ! isset( $data['items'][0]['chapters']['chapters'] ) ) {
            IPV_Prod_Logger::log( 'YouTube Chapters API: Nessun capitolo trovato', [
                'video_id' => $video_id,
            ] );
            return new WP_Error( 'ipv_chapters_not_found', 'Video non ha capitoli nativi.' );
        }

        $raw_chapters = $data['items'][0]['chapters']['chapters'];

        if ( empty( $raw_chapters ) || ! is_array( $raw_chapters ) ) {
            return new WP_Error( 'ipv_chapters_empty', 'Lista capitoli vuota.' );
        }

        // Formatta i capitoli
        $chapters = [];
        foreach ( $raw_chapters as $chapter ) {
            if ( ! isset( $chapter['time'], $chapter['title'] ) ) {
                continue;
            }

            $chapters[] = [
                'time'  => self::format_timestamp( $chapter['time'] ),
                'title' => sanitize_text_field( $chapter['title'] ),
            ];
        }

        if ( empty( $chapters ) ) {
            return new WP_Error( 'ipv_chapters_parse_error', 'Errore parsing capitoli.' );
        }

        IPV_Prod_Logger::log( 'YouTube Chapters API: Capitoli recuperati con successo', [
            'video_id' => $video_id,
            'count'    => count( $chapters ),
        ] );

        return $chapters;
    }

    /**
     * Formatta timestamp da secondi a MM:SS o H:MM:SS
     *
     * @param int $seconds Secondi
     * @return string Timestamp formattato
     */
    protected static function format_timestamp( $seconds ) {
        $seconds = (int) $seconds;
        $hours   = floor( $seconds / 3600 );
        $minutes = floor( ( $seconds % 3600 ) / 60 );
        $secs    = $seconds % 60;

        if ( $hours > 0 ) {
            return sprintf( '%d:%02d:%02d', $hours, $minutes, $secs );
        } else {
            return sprintf( '%d:%02d', $minutes, $secs );
        }
    }

    /**
     * Converte array di capitoli in formato testo per descrizione YouTube
     *
     * @param array $chapters Array capitoli
     * @return string Testo formattato
     */
    public static function format_chapters_text( $chapters ) {
        if ( empty( $chapters ) || ! is_array( $chapters ) ) {
            return '';
        }

        $lines = [];
        foreach ( $chapters as $chapter ) {
            $lines[] = $chapter['time'] . ' — ' . $chapter['title'];
        }

        return implode( "\n", $lines );
    }

    /**
     * Verifica se i timestamp coprono almeno X% della durata video
     *
     * @param string $timestamps_text Testo con timestamp (formato: "12:45 — Titolo\n23:10 — Titolo2")
     * @param int    $duration_seconds Durata totale video in secondi
     * @param int    $min_coverage Copertura minima richiesta in percentuale (default: 80)
     * @return bool True se copertura sufficiente
     */
    public static function verify_timestamp_coverage( $timestamps_text, $duration_seconds, $min_coverage = 80 ) {
        if ( empty( $timestamps_text ) || $duration_seconds <= 0 ) {
            return false;
        }

        // Estrai tutti i timestamp
        preg_match_all( '/(\d+):(\d+)(?::(\d+))?/', $timestamps_text, $matches, PREG_SET_ORDER );

        if ( empty( $matches ) ) {
            return false;
        }

        // Trova l'ultimo timestamp
        $last_match = end( $matches );
        $hours      = isset( $last_match[3] ) ? (int) $last_match[1] : 0;
        $minutes    = isset( $last_match[3] ) ? (int) $last_match[2] : (int) $last_match[1];
        $seconds    = isset( $last_match[3] ) ? (int) $last_match[3] : (int) $last_match[2];

        $last_timestamp_seconds = ( $hours * 3600 ) + ( $minutes * 60 ) + $seconds;

        // Calcola percentuale di copertura
        $coverage_percent = ( $last_timestamp_seconds / $duration_seconds ) * 100;

        IPV_Prod_Logger::log( 'Verifica copertura timestamp', [
            'last_timestamp'   => $last_timestamp_seconds . 's',
            'total_duration'   => $duration_seconds . 's',
            'coverage_percent' => round( $coverage_percent, 2 ) . '%',
            'min_required'     => $min_coverage . '%',
            'passed'           => $coverage_percent >= $min_coverage,
        ] );

        return $coverage_percent >= $min_coverage;
    }
}
