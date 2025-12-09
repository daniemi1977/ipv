<?php
/**
 * IPV Production System Pro - Centralized Helpers
 *
 * Funzioni utility centralizzate per evitare duplicazioni.
 * Tutte le classi devono usare questi metodi invece di reimplementarli.
 *
 * @package IPV_Production_System_Pro
 * @version 9.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Helpers {

    /**
     * Meta key standardizzate - USARE SEMPRE QUESTE COSTANTI
     */
    const META_VIDEO_ID         = '_ipv_video_id';
    const META_YOUTUBE_URL      = '_ipv_youtube_url';
    const META_VIDEO_SOURCE     = '_ipv_video_source';
    const META_TRANSCRIPT       = '_ipv_transcript';
    const META_AI_DESCRIPTION   = '_ipv_ai_description';
    const META_AI_SUMMARY       = '_ipv_ai_summary';
    
    // YouTube specific
    const META_YT_TITLE         = '_ipv_yt_title';
    const META_YT_DESCRIPTION   = '_ipv_yt_description';
    const META_YT_PUBLISHED_AT  = '_ipv_yt_published_at';
    const META_YT_CHANNEL_TITLE = '_ipv_yt_channel_title';
    const META_YT_CHANNEL_ID    = '_ipv_yt_channel_id';
    const META_YT_THUMBNAIL_URL = '_ipv_yt_thumbnail_url';
    const META_YT_DURATION      = '_ipv_yt_duration';          // ISO 8601
    const META_YT_DURATION_SEC  = '_ipv_yt_duration_seconds';  // Secondi
    const META_YT_DURATION_FMT  = '_ipv_yt_duration_formatted'; // HH:MM:SS
    const META_YT_VIEW_COUNT    = '_ipv_yt_view_count';
    const META_YT_LIKE_COUNT    = '_ipv_yt_like_count';
    const META_YT_COMMENT_COUNT = '_ipv_yt_comment_count';
    const META_YT_DEFINITION    = '_ipv_yt_definition';
    const META_YT_TAGS          = '_ipv_yt_tags';
    const META_YT_DATA_UPDATED  = '_ipv_yt_data_updated';
    
    // Queue/Processing status
    const META_QUEUE_STATUS     = '_ipv_queue_status';
    const META_PREMIERE_PENDING = '_ipv_premiere_pending';
    const META_IMPORT_DATE      = '_ipv_import_date';

    /**
     * Estrai YouTube Video ID da URL
     * 
     * Supporta tutti i formati:
     * - youtube.com/watch?v=VIDEO_ID
     * - youtu.be/VIDEO_ID
     * - youtube.com/embed/VIDEO_ID
     * - youtube.com/v/VIDEO_ID
     * - youtube.com/shorts/VIDEO_ID
     *
     * @param string $url URL YouTube o video ID diretto
     * @return string|false Video ID o false se non trovato
     */
    public static function extract_youtube_id( $url ) {
        if ( empty( $url ) ) {
            return false;
        }

        $url = trim( $url );

        // Se è già un video ID (11 caratteri alfanumerici con - e _)
        if ( preg_match( '/^[a-zA-Z0-9_-]{11}$/', $url ) ) {
            return $url;
        }

        // Pattern per tutti i formati YouTube URL
        $patterns = [
            // youtube.com/watch?v=VIDEO_ID
            '/(?:youtube\.com\/watch\?.*v=|youtube\.com\/watch\?v=)([a-zA-Z0-9_-]{11})/',
            // youtu.be/VIDEO_ID
            '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
            // youtube.com/embed/VIDEO_ID
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
            // youtube.com/v/VIDEO_ID
            '/youtube\.com\/v\/([a-zA-Z0-9_-]{11})/',
            // youtube.com/shorts/VIDEO_ID
            '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/',
            // youtube.com/live/VIDEO_ID
            '/youtube\.com\/live\/([a-zA-Z0-9_-]{11})/',
        ];

        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $url, $matches ) ) {
                return $matches[1];
            }
        }

        return false;
    }

    /**
     * Verifica se un video YouTube esiste già nel database
     *
     * @param string $video_id YouTube Video ID
     * @return int|false Post ID se esiste, false altrimenti
     */
    public static function video_exists( $video_id ) {
        global $wpdb;

        if ( empty( $video_id ) ) {
            return false;
        }

        $post_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                 WHERE meta_key = %s AND meta_value = %s
                 LIMIT 1",
                self::META_VIDEO_ID,
                $video_id
            )
        );

        return $post_id ? (int) $post_id : false;
    }

    /**
     * Ottieni Post ID da Video ID
     * 
     * Alias di video_exists() per semantica più chiara
     *
     * @param string $video_id YouTube Video ID
     * @return int|false Post ID se esiste, false altrimenti
     */
    public static function get_post_id_by_video_id( $video_id ) {
        return self::video_exists( $video_id );
    }

    /**
     * Converti durata ISO 8601 in secondi
     *
     * @param string $duration Durata ISO 8601 (es. PT1H30M15S)
     * @return int Secondi
     */
    public static function duration_to_seconds( $duration ) {
        if ( empty( $duration ) ) {
            return 0;
        }

        $pattern = '/^PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$/';
        if ( ! preg_match( $pattern, $duration, $matches ) ) {
            return 0;
        }

        $hours   = isset( $matches[1] ) ? (int) $matches[1] : 0;
        $minutes = isset( $matches[2] ) ? (int) $matches[2] : 0;
        $seconds = isset( $matches[3] ) ? (int) $matches[3] : 0;

        return ( $hours * 3600 ) + ( $minutes * 60 ) + $seconds;
    }

    /**
     * Formatta secondi in stringa leggibile
     *
     * @param int $seconds Durata in secondi
     * @return string Durata formattata (es. "1:30:15" o "30:15")
     */
    public static function format_duration( $seconds ) {
        $seconds = (int) $seconds;
        
        if ( $seconds <= 0 ) {
            return '0:00';
        }

        $hours   = floor( $seconds / 3600 );
        $minutes = floor( ( $seconds % 3600 ) / 60 );
        $secs    = $seconds % 60;

        if ( $hours > 0 ) {
            return sprintf( '%d:%02d:%02d', $hours, $minutes, $secs );
        }

        return sprintf( '%d:%02d', $minutes, $secs );
    }

    /**
     * Ottieni durata formattata da post ID
     *
     * @param int $post_id Post ID
     * @return string Durata formattata
     */
    public static function get_formatted_duration( $post_id ) {
        // Prima prova la durata già formattata
        $duration_formatted = get_post_meta( $post_id, self::META_YT_DURATION_FMT, true );
        if ( ! empty( $duration_formatted ) ) {
            return $duration_formatted;
        }

        // Poi prova i secondi
        $duration_sec = get_post_meta( $post_id, self::META_YT_DURATION_SEC, true );
        if ( ! empty( $duration_sec ) ) {
            return self::format_duration( (int) $duration_sec );
        }

        // Infine prova ISO 8601
        $duration_iso = get_post_meta( $post_id, self::META_YT_DURATION, true );
        if ( ! empty( $duration_iso ) ) {
            $seconds = self::duration_to_seconds( $duration_iso );
            return self::format_duration( $seconds );
        }

        return '';
    }

    /**
     * Rileva la piattaforma video da URL
     *
     * @param string $url URL video
     * @return string|false 'youtube', 'vimeo', 'dailymotion' o false
     */
    public static function detect_video_source( $url ) {
        if ( empty( $url ) ) {
            return false;
        }

        if ( preg_match( '/youtube\.com|youtu\.be/', $url ) ) {
            return 'youtube';
        }

        if ( preg_match( '/vimeo\.com/', $url ) ) {
            return 'vimeo';
        }

        if ( preg_match( '/dailymotion\.com|dai\.ly/', $url ) ) {
            return 'dailymotion';
        }

        return false;
    }

    /**
     * Sanitizza e valida URL YouTube
     *
     * @param string $url URL da validare
     * @return string|WP_Error URL pulito o errore
     */
    public static function sanitize_youtube_url( $url ) {
        $url = esc_url_raw( trim( $url ) );
        
        if ( empty( $url ) ) {
            return new WP_Error( 'empty_url', __( 'URL is empty', 'ipv-production-system-pro' ) );
        }

        $video_id = self::extract_youtube_id( $url );
        
        if ( ! $video_id ) {
            return new WP_Error( 'invalid_url', __( 'Invalid YouTube URL', 'ipv-production-system-pro' ) );
        }

        // Restituisci URL normalizzato
        return 'https://www.youtube.com/watch?v=' . $video_id;
    }

    /**
     * Scarica e imposta thumbnail da YouTube
     *
     * @param int    $post_id       Post ID
     * @param string $video_id      YouTube Video ID
     * @param string $thumbnail_url URL thumbnail preferito (opzionale)
     * @return int|false Attachment ID o false se fallito
     */
    public static function set_youtube_thumbnail( $post_id, $video_id, $thumbnail_url = null ) {
        // Skip se ha già thumbnail
        if ( has_post_thumbnail( $post_id ) ) {
            return get_post_thumbnail_id( $post_id );
        }

        // Qualità thumbnail da provare (migliore prima)
        $urls = [];
        
        if ( $thumbnail_url ) {
            $urls[] = $thumbnail_url;
        }
        
        $urls[] = 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg';
        $urls[] = 'https://img.youtube.com/vi/' . $video_id . '/sddefault.jpg';
        $urls[] = 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg';
        $urls[] = 'https://img.youtube.com/vi/' . $video_id . '/mqdefault.jpg';

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        foreach ( $urls as $url ) {
            // Verifica disponibilità URL
            $response = wp_remote_head( $url, [ 'timeout' => 5 ] );
            if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
                continue;
            }

            // Download
            $tmp = download_url( $url );
            if ( is_wp_error( $tmp ) ) {
                continue;
            }

            $file_array = [
                'name'     => $video_id . '.jpg',
                'tmp_name' => $tmp,
            ];

            $attach_id = media_handle_sideload( $file_array, $post_id, get_the_title( $post_id ) );

            if ( ! is_wp_error( $attach_id ) ) {
                set_post_thumbnail( $post_id, $attach_id );
                return $attach_id;
            }

            // Cleanup on error
            @unlink( $tmp );
        }

        return false;
    }

    /**
     * Formatta numero grande in formato leggibile
     *
     * @param int $number Numero
     * @return string Numero formattato (es. "1.2M", "500K")
     */
    public static function format_number( $number ) {
        $number = (int) $number;

        if ( $number >= 1000000 ) {
            return round( $number / 1000000, 1 ) . 'M';
        }

        if ( $number >= 1000 ) {
            return round( $number / 1000, 1 ) . 'K';
        }

        return number_format_i18n( $number );
    }

    /**
     * Log message se WP_DEBUG è attivo
     *
     * @param string $message Messaggio
     * @param array  $context Contesto aggiuntivo
     */
    public static function log( $message, $context = [] ) {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return;
        }

        $prefix = '[IPV Production] ';
        
        if ( ! empty( $context ) && function_exists( 'wp_json_encode' ) ) {
            $message .= ' ' . wp_json_encode( $context );
        }
        
        error_log( $prefix . $message );
    }
}
