<?php
/**
 * IPV Production System Pro - AI Generator
 *
 * Generazione descrizioni video tramite server remoto
 * Le API keys sono sul server - il client non le vede mai!
 * Il Golden Prompt è inserito manualmente dall'utente
 *
 * @package IPV_Production_System_Pro
 * @version 10.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_AI_Generator {

    /**
     * Get social links from options
     */
    private static function get_social_links() {
        return [
            'telegram'  => get_option( 'ipv_telegram_link', '' ),
            'facebook'  => get_option( 'ipv_facebook_link', '' ),
            'instagram' => get_option( 'ipv_instagram_link', '' ),
            'website'   => get_option( 'ipv_website_link', '' ),
            'paypal'    => get_option( 'ipv_paypal_link', '' ),
            'sponsor'   => get_option( 'ipv_sponsor_link', '' ),
            'sponsor_name' => get_option( 'ipv_sponsor_name', '' ),
        ];
    }

    /**
     * Genera descrizione completa per un video
     *
     * @param string $video_title Titolo del video
     * @param string $transcript Trascrizione
     * @param string $duration_formatted Durata formattata
     * @param int $duration_seconds Durata in secondi
     * @param string $native_chapters Capitoli nativi YouTube
     * @return string|WP_Error
     */
    public static function generate_description( $video_title, $transcript, $duration_formatted = '', $duration_seconds = 0, $native_chapters = '' ) {
        // Verifica licenza
        if ( ! IPV_Prod_API_Client::is_license_active() ) {
            return new WP_Error(
                'ipv_license_required',
                __( 'Licenza non attiva. Attiva la licenza per usare la generazione AI.', 'ipv-production-system-pro' )
            );
        }

        // Costruisci il prompt personalizzato con il Golden Prompt dell'utente
        $custom_prompt = self::build_full_prompt( $video_title, $transcript, $duration_formatted, $duration_seconds, $native_chapters );

        if ( class_exists( 'IPV_Prod_Logger' ) ) {
            IPV_Prod_Logger::log( 'AI: Richiesta generazione', [ 
                'title' => $video_title,
                'transcript_length' => strlen( $transcript )
            ] );
        }

        // Chiama API remota
        $api_client = IPV_Prod_API_Client::instance();
        $result = $api_client->generate_description( $transcript, $video_title, $custom_prompt );

        if ( is_wp_error( $result ) ) {
            if ( class_exists( 'IPV_Prod_Logger' ) ) {
                IPV_Prod_Logger::log( 'AI: Errore', [ 'error' => $result->get_error_message() ] );
            }
            return $result;
        }

        if ( class_exists( 'IPV_Prod_Logger' ) ) {
            IPV_Prod_Logger::log( 'AI: Descrizione generata', [ 'length' => strlen( $result ) ] );
        }

        return $result;
    }

    /**
     * Costruisce il prompt completo con il Golden Prompt dell'utente
     */
    private static function build_full_prompt( $video_title, $transcript, $duration_formatted, $duration_seconds, $native_chapters ) {
        // Ottieni il Golden Prompt dall'utente
        $golden_prompt = get_option( 'ipv_golden_prompt', '' );
        
        // Se non c'è Golden Prompt, usa un prompt base
        if ( empty( $golden_prompt ) ) {
            $golden_prompt = self::get_fallback_prompt();
        }

        // Costruisci il messaggio utente
        $user_content = "TITOLO VIDEO: " . $video_title . "\n\n";

        if ( ! empty( $duration_formatted ) ) {
            $user_content .= "DURATA VIDEO: " . $duration_formatted . " (" . $duration_seconds . " secondi)\n\n";
        }

        if ( ! empty( $native_chapters ) ) {
            $user_content .= "⚠️ CAPITOLI YOUTUBE (USA QUESTI TIMESTAMP):\n";
            $user_content .= $native_chapters . "\n\n";
        }

        $user_content .= "TRASCRIZIONE:\n";
        $user_content .= mb_substr( $transcript, 0, 30000 );

        // Combina Golden Prompt + Contenuto
        return $golden_prompt . "\n\n---\n\n" . $user_content;
    }

    /**
     * Prompt di fallback se Golden Prompt non configurato
     */
    private static function get_fallback_prompt() {
        return <<<PROMPT
Sei un esperto copywriter per YouTube.

Analizza la trascrizione del video e genera:
1. Una descrizione SEO-friendly (150-200 parole)
2. Capitoli con timestamp (se la durata lo permette)
3. 20-25 hashtag rilevanti

Scrivi in italiano. Tono professionale ma accessibile.
PROMPT;
    }

    /**
     * Genera descrizione e salva nel post con estrazione metadata
     */
    public static function generate_and_save( $post_id ) {
        $video_title = get_the_title( $post_id );
        $transcript = get_post_meta( $post_id, '_ipv_transcript', true );

        if ( empty( $transcript ) ) {
            return new WP_Error( 'no_transcript', __( 'Trascrizione mancante', 'ipv-production-system-pro' ) );
        }

        // Ottieni durata video
        $duration_formatted = get_post_meta( $post_id, '_ipv_yt_duration_formatted', true );
        $duration_seconds = (int) get_post_meta( $post_id, '_ipv_yt_duration_seconds', true );

        if ( empty( $duration_formatted ) ) {
            $duration_formatted = get_post_meta( $post_id, '_ipv_yt_duration', true );
        }

        if ( ! $duration_seconds && ! empty( $duration_formatted ) ) {
            $duration_seconds = self::parse_duration_to_seconds( $duration_formatted );
        }

        // Recupera capitoli nativi YouTube se disponibili
        $video_id = get_post_meta( $post_id, '_ipv_video_id', true );
        $native_chapters_text = '';

        if ( ! empty( $video_id ) && class_exists( 'IPV_Prod_YouTube_Chapters' ) ) {
            $chapters_result = IPV_Prod_YouTube_Chapters::get_chapters( $video_id );

            if ( ! is_wp_error( $chapters_result ) ) {
                $native_chapters_text = IPV_Prod_YouTube_Chapters::format_chapters_text( $chapters_result );
            }
        }

        // Genera descrizione
        $description = self::generate_description(
            $video_title,
            $transcript,
            $duration_formatted,
            $duration_seconds,
            $native_chapters_text
        );

        if ( is_wp_error( $description ) ) {
            return $description;
        }

        // Salva descrizione
        update_post_meta( $post_id, '_ipv_ai_description', $description );

        // Aggiorna contenuto post
        wp_update_post( [
            'ID' => $post_id,
            'post_content' => $description,
        ] );

        // Estrai e salva metadata
        self::extract_and_save_metadata( $post_id, $description );

        return $description;
    }

    /**
     * Estrae metadata dalla descrizione generata
     */
    private static function extract_and_save_metadata( $post_id, $description ) {
        // Estrai hashtag
        if ( preg_match_all( '/#([A-Za-z0-9À-ÿ_]+)/u', $description, $matches ) ) {
            $hashtags = array_unique( $matches[1] );
            update_post_meta( $post_id, '_ipv_hashtags', $hashtags );

            // Aggiungi come tag WordPress
            wp_set_post_tags( $post_id, $hashtags, true );
        }

        // Estrai ospiti (sezione 👤 OSPITI)
        if ( preg_match( '/👤\s*OSPITI?\s*\n(.*?)(?=\n[🏛📊🤝💬🔧🏷]|$)/su', $description, $match ) ) {
            $guests_section = trim( $match[1] );
            $guests = array_filter( array_map( 'trim', explode( "\n", $guests_section ) ) );
            $guests = array_filter( $guests, function( $g ) {
                return ! empty( $g ) && stripos( $g, 'nessun' ) === false;
            });

            if ( ! empty( $guests ) ) {
                // Assegna tassonomia ipv_relatore
                $guest_names = [];
                foreach ( $guests as $guest ) {
                    $clean = preg_replace( '/^[\-•\*]\s*/', '', $guest );
                    $clean = preg_replace( '/\s*[\-–—]\s*.*$/', '', $clean );
                    if ( ! empty( $clean ) ) {
                        $guest_names[] = trim( $clean );
                    }
                }
                if ( ! empty( $guest_names ) ) {
                    wp_set_object_terms( $post_id, $guest_names, 'ipv_relatore', true );
                }
            }
        }

        // Estrai argomenti (sezione 🗂️ ARGOMENTI)
        if ( preg_match( '/🗂️\s*ARGOMENTI\s*TRATTATI?\s*\n(.*?)(?=\n[👤🏛📊🤝💬🔧🏷]|$)/su', $description, $match ) ) {
            $topics_section = trim( $match[1] );
            $topics = [];

            foreach ( explode( "\n", $topics_section ) as $line ) {
                $line = trim( $line );
                if ( empty( $line ) ) continue;

                $line = preg_replace( '/^[\-•\*]\s*/', '', $line );

                if ( preg_match( '/^([^:]+):/u', $line, $m ) ) {
                    $topic = trim( $m[1] );
                    if ( strlen( $topic ) > 3 && strlen( $topic ) < 100 ) {
                        $topics[] = $topic;
                    }
                }
            }

            if ( ! empty( $topics ) ) {
                wp_set_object_terms( $post_id, $topics, 'ipv_categoria', true );
            }
        }
    }

    /**
     * Parse duration string to seconds
     */
    public static function parse_duration_to_seconds( $duration ) {
        // Formato H:MM:SS o MM:SS
        if ( preg_match( '/^(\d+):(\d{2}):(\d{2})$/', $duration, $m ) ) {
            return ( (int)$m[1] * 3600 ) + ( (int)$m[2] * 60 ) + (int)$m[3];
        }
        if ( preg_match( '/^(\d+):(\d{2})$/', $duration, $m ) ) {
            return ( (int)$m[1] * 60 ) + (int)$m[2];
        }

        // Formato ISO8601 (PT1H23M45S)
        if ( preg_match( '/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $m ) ) {
            $hours = isset( $m[1] ) ? (int)$m[1] : 0;
            $minutes = isset( $m[2] ) ? (int)$m[2] : 0;
            $seconds = isset( $m[3] ) ? (int)$m[3] : 0;
            return ( $hours * 3600 ) + ( $minutes * 60 ) + $seconds;
        }

        return 0;
    }

    /**
     * Metodo stub per compatibilità - non più usato
     */
    protected static function get_default_prompt( $duration_formatted = '', $duration_seconds = 0, $has_native_chapters = false ) {
        return self::get_fallback_prompt();
    }
}
