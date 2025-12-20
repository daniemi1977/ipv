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
        if ( ! IPV_Prod_API_Client_Optimized::is_license_active() ) {
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
        $api_client = IPV_Prod_API_Client_Optimized::instance();
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
     * PRIORITÀ: 1) Vendor (remoto) → 2) Locale → 3) Fallback
     */
    private static function build_full_prompt( $video_title, $transcript, $duration_formatted, $duration_seconds, $native_chapters ) {
        // PRIORITÀ 1: Golden Prompt remoto da licenza Golden Prompt (classe AI Prompt Config)
        $golden_prompt = '';
        
        if ( class_exists( 'IPV_Prod_AI_Prompt_Config' ) && IPV_Prod_AI_Prompt_Config::has_golden_prompt() ) {
            $golden_prompt = IPV_Prod_AI_Prompt_Config::get_golden_prompt_remote();
            
            if ( class_exists( 'IPV_Prod_Logger' ) ) {
                IPV_Prod_Logger::log( 'AI: Usando Golden Prompt remoto (licenza Golden Prompt attiva)' );
            }
        }
        
        // PRIORITÀ 2: Golden Prompt dal Vendor (licenza base - deprecato)
        if ( empty( $golden_prompt ) && function_exists( 'ipv_get_golden_prompt' ) && ipv_has_golden_prompt() ) {
            $golden_prompt = ipv_get_golden_prompt();
            
            if ( class_exists( 'IPV_Prod_Logger' ) ) {
                IPV_Prod_Logger::log( 'AI: Usando Golden Prompt dal Vendor (licenza base)' );
            }
        }
        
        // PRIORITÀ 3: Golden Prompt locale (configurato manualmente)
        if ( empty( $golden_prompt ) ) {
            $golden_prompt = get_option( 'ipv_golden_prompt', '' );
            
            if ( ! empty( $golden_prompt ) && class_exists( 'IPV_Prod_Logger' ) ) {
                IPV_Prod_Logger::log( 'AI: Usando Golden Prompt locale' );
            }
        }
        
        // PRIORITÀ 4: Fallback prompt base
        if ( empty( $golden_prompt ) ) {
            $golden_prompt = self::get_fallback_prompt();
            
            if ( class_exists( 'IPV_Prod_Logger' ) ) {
                IPV_Prod_Logger::log( 'AI: Usando Golden Prompt fallback' );
            }
        }

        // ✅ SOSTITUISCI PLACEHOLDER con configurazione canale
        if ( class_exists( 'IPV_Prod_AI_Prompt_Config' ) ) {
            $golden_prompt = IPV_Prod_AI_Prompt_Config::apply_placeholders( $golden_prompt );
            
            if ( class_exists( 'IPV_Prod_Logger' ) ) {
                IPV_Prod_Logger::log( 'AI: Placeholder sostituiti con configurazione canale' );
            }
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

Analizza la trascrizione del video e genera una descrizione completa usando questo formato:

### Descrizione
[150-200 parole che riassumono il contenuto del video in modo coinvolgente]

### Capitoli
[Se la durata lo permette, genera capitoli con timestamp nel formato:
00:00 — Introduzione
MM:SS — [Titolo capitolo descrittivo]
...]

### 🗂️ ARGOMENTI TRATTATI
[Lista degli argomenti principali discussi nel video, uno per riga, con formato:
• [Nome Argomento]: [breve descrizione]
Esempio:
• Intelligenza Artificiale: applicazioni pratiche nel business
• Machine Learning: tecniche di addestramento
Questi diventeranno categorie, quindi usa termini chiari e cercabili]

### 👤 OSPITI
[Se ci sono ospiti/relatori nel video, elenca i loro nomi:
• Nome Cognome — Ruolo/Professione
Se non ci sono ospiti, scrivi: Nessun ospite]

### Hashtag
[20-25 hashtag rilevanti su una riga, separati da spazi]

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

        // Aggiorna contenuto post con formattazione corretta
        // wpautop() converte line breaks in <p> e <br> tags
        wp_update_post( [
            'ID' => $post_id,
            'post_content' => wpautop( $description ), // ✅ Preserva a capo!
        ] );

        // Estrai e salva metadata
        self::extract_and_save_metadata( $post_id, $description );

        return $description;
    }

    /**
     * Estrae metadata dalla descrizione generata
     * v10.0.22 - Rispetta i flag di estrazione globali e per singolo video
     * v10.0.23 - Logica relatori migliorata: cerca nel titolo, NON prende hashtag
     */
    private static function extract_and_save_metadata( $post_id, $description ) {
        // Controlla flag globali e override per video
        $extract_tags       = self::should_extract( $post_id, 'tags' );
        $extract_categories = self::should_extract( $post_id, 'categories' );
        $extract_speakers   = self::should_extract( $post_id, 'speakers' );

        // Estrai hashtag → Tags
        if ( $extract_tags && preg_match_all( '/#([A-Za-z0-9À-ÿ_]+)/u', $description, $matches ) ) {
            $hashtags = array_unique( $matches[1] );
            update_post_meta( $post_id, '_ipv_hashtags', $hashtags );

            // Aggiungi come tag WordPress
            wp_set_post_tags( $post_id, $hashtags, true );
            
            if ( class_exists( 'IPV_Prod_Logger' ) ) {
                IPV_Prod_Logger::log( 'AI: Tags estratti', [ 'post_id' => $post_id, 'count' => count( $hashtags ) ] );
            }
        }

        // Estrai relatori → v10.0.23 Logica migliorata
        if ( $extract_speakers ) {
            $speakers = self::extract_speakers_smart( $post_id, $description );
            
            if ( ! empty( $speakers ) ) {
                wp_set_object_terms( $post_id, $speakers, 'ipv_relatore', true );
                
                if ( class_exists( 'IPV_Prod_Logger' ) ) {
                    IPV_Prod_Logger::log( 'AI: Relatori estratti', [ 'post_id' => $post_id, 'speakers' => $speakers ] );
                }
            }
        }

        // Estrai argomenti → Categorie (sezione 🗂️ ARGOMENTI)
        if ( $extract_categories && preg_match( '/🗂️\s*ARGOMENTI\s*TRATTATI?\s*\n(.*?)(?=\n[👤🏛📊🤝💬🔧🏷]|###|$)/su', $description, $match ) ) {
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
                
                if ( class_exists( 'IPV_Prod_Logger' ) ) {
                    IPV_Prod_Logger::log( 'AI: Categorie estratte', [ 'post_id' => $post_id, 'categories' => $topics ] );
                }
            }
        }
    }

    /**
     * v10.0.23 - Estrazione intelligente dei relatori
     * 
     * Regole:
     * 1. MAI prendere hashtag (iniziano con #)
     * 2. Cercare nomi nel titolo del video
     * 3. Verificare che il nome appaia anche nella trascrizione
     * 4. Se non c'è certezza, non inserire nulla
     * 
     * @param int    $post_id Post ID
     * @param string $description AI description
     * @return array Lista nomi validi
     */
    private static function extract_speakers_smart( $post_id, $description ) {
        $speakers = [];
        
        // Ottieni titolo e trascrizione per cross-reference
        $video_title = get_the_title( $post_id );
        $transcript = get_post_meta( $post_id, '_ipv_transcript', true );
        
        // 1. Cerca nella sezione 👤 OSPITI della descrizione AI
        if ( preg_match( '/👤\s*OSPITI?\s*\n(.*?)(?=\n[🏛📊🤝💬🔧🏷🗂]|###|$)/su', $description, $match ) ) {
            $guests_section = trim( $match[1] );
            
            foreach ( explode( "\n", $guests_section ) as $line ) {
                $line = trim( $line );
                if ( empty( $line ) ) continue;
                
                // Skip se contiene "nessun" o simili
                if ( preg_match( '/nessun|niente|none|n\/a|---/i', $line ) ) {
                    continue;
                }
                
                // Skip se inizia con # (hashtag)
                if ( strpos( $line, '#' ) === 0 ) {
                    continue;
                }
                
                // Rimuovi bullet points
                $clean = preg_replace( '/^[\-•\*]\s*/', '', $line );
                
                // Estrai solo il nome (prima del trattino che indica ruolo)
                $clean = preg_replace( '/\s*[\-–—:]\s*.*$/', '', $clean );
                $clean = trim( $clean );
                
                // Validazione nome
                if ( ! self::is_valid_speaker_name( $clean ) ) {
                    continue;
                }
                
                // Cross-reference: verifica che appaia nel titolo O nella trascrizione
                $name_lower = mb_strtolower( $clean );
                $title_lower = mb_strtolower( (string) $video_title );
                $transcript_lower = mb_strtolower( (string) $transcript );
                
                $in_title = ! empty( $title_lower ) && strpos( $title_lower, $name_lower ) !== false;
                $in_transcript = ! empty( $transcript_lower ) && strpos( $transcript_lower, $name_lower ) !== false;
                
                // Accetta se appare nel titolo O nella trascrizione
                if ( $in_title || $in_transcript ) {
                    $speakers[] = $clean;
                }
            }
        }
        
        // 2. Fallback: cerca pattern "con [Nome]" o "intervista a [Nome]" nel titolo
        if ( empty( $speakers ) ) {
            $patterns = [
                '/\bcon\s+([A-Z][a-zàèéìòù]+(?:\s+[A-Z][a-zàèéìòù]+)+)/u',
                '/\bintervista\s+(?:a|con)\s+([A-Z][a-zàèéìòù]+(?:\s+[A-Z][a-zàèéìòù]+)+)/iu',
                '/\bospite[:\s]+([A-Z][a-zàèéìòù]+(?:\s+[A-Z][a-zàèéìòù]+)+)/iu',
            ];
            
            foreach ( $patterns as $pattern ) {
                if ( preg_match( $pattern, $video_title, $m ) ) {
                    $name = trim( $m[1] );
                    if ( self::is_valid_speaker_name( $name ) ) {
                        // Verifica in trascrizione
                        if ( ! empty( $transcript ) && stripos( $transcript, $name ) !== false ) {
                            $speakers[] = $name;
                        }
                    }
                }
            }
        }
        
        return array_unique( $speakers );
    }

    /**
     * v10.0.23 - Valida se una stringa è un nome di persona valido
     */
    private static function is_valid_speaker_name( $name ) {
        // Null safety
        $name = (string) $name;
        
        // Troppo corto o troppo lungo
        if ( strlen( $name ) < 3 || strlen( $name ) > 60 ) {
            return false;
        }
        
        // Contiene hashtag
        if ( strpos( $name, '#' ) !== false ) {
            return false;
        }
        
        // Contiene solo numeri
        if ( preg_match( '/^\d+$/', $name ) ) {
            return false;
        }
        
        // Contiene URL
        if ( preg_match( '/https?:|www\./i', $name ) ) {
            return false;
        }
        
        // Contiene emoji (comuni)
        if ( preg_match( '/[\x{1F300}-\x{1F9FF}]/u', $name ) ) {
            return false;
        }
        
        // Deve contenere almeno una lettera
        if ( ! preg_match( '/[a-zA-ZÀ-ÿ]/u', $name ) ) {
            return false;
        }
        
        // Pattern tipico nome persona: Almeno una maiuscola seguita da minuscole
        // O tutto maiuscolo (acronimi tipo "NVIDIA")
        if ( ! preg_match( '/^[A-ZÀ-Ý][a-zà-ÿ]/u', $name ) && ! preg_match( '/^[A-ZÀ-Ý\s]+$/u', $name ) ) {
            // Potrebbe essere lowercase, accettiamo comunque se sembra un nome
            if ( ! preg_match( '/^[a-zà-ÿ]+\s+[a-zà-ÿ]+/iu', $name ) ) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * v10.0.22 - Determina se estrarre un tipo di metadata
     * Controlla prima il meta del video, poi il default globale
     * 
     * @param int    $post_id Post ID
     * @param string $type    Tipo: 'tags', 'categories', 'speakers'
     * @return bool
     */
    private static function should_extract( $post_id, $type ) {
        $meta_key = '_ipv_extract_' . $type;
        $option_key = 'ipv_extract_' . $type;
        
        // Controlla override per video
        $video_override = get_post_meta( $post_id, $meta_key, true );
        
        if ( $video_override === 'yes' ) {
            return true;
        }
        if ( $video_override === 'no' ) {
            return false;
        }
        
        // Usa default globale (default: attivo)
        return get_option( $option_key, '1' ) === '1';
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
