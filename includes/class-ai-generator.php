<?php
/**
 * IPV Production System Pro - AI Generator
 *
 * Generazione descrizioni video con OpenAI GPT-4o
 * Automazione completa: hashtag, relatori, categorie, anno
 * Golden Prompt v4.0: timestamp completi, SEO lungo, categorie da logica
 *
 * @package IPV_Production_System_Pro
 * @version 7.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_AI_Generator {

    // Link fissi del canale (hardcoded per evitare problemi)
    const TELEGRAM_LINK  = 'https://t.me/il_punto_divista';
    const FACEBOOK_LINK  = 'https://facebook.com/groups/4102938329737588';
    const INSTAGRAM_LINK = 'https://instagram.com/_ilpuntodivista._';
    const WEBSITE_LINK   = 'https://ilpuntodivistachannel.com';
    const PAYPAL_LINK    = 'https://paypal.me/adrianfiorelli';
    const SPONSOR_LINK   = 'https://biovital-italia.com/?bio=17';
    const SPONSOR_NAME   = 'Biovital – Progetto Italia';

    /**
     * Genera descrizione completa per un video
     *
     * @param string $video_title Titolo del video
     * @param string $transcript Trascrizione
     * @param string $duration_formatted Durata formattata (es: "1:47:45")
     * @param int $duration_seconds Durata in secondi
     * @param string $native_chapters Capitoli nativi YouTube (opzionale)
     */
    public static function generate_description( $video_title, $transcript, $duration_formatted = '', $duration_seconds = 0, $native_chapters = '' ) {
        $api_key = get_option( 'ipv_openai_api_key', '' );
        if ( empty( $api_key ) ) {
            return new WP_Error( 'ipv_openai_no_key', 'OpenAI API Key non configurata.' );
        }

        $custom_prompt = get_option( 'ipv_ai_prompt', '' );
        $system_prompt = ! empty( $custom_prompt ) ? $custom_prompt : self::get_default_prompt( $duration_formatted, $duration_seconds, ! empty( $native_chapters ) );

        $user_content  = "TITOLO VIDEO: " . $video_title . "\n\n";

        // Aggiungi info durata
        if ( ! empty( $duration_formatted ) ) {
            $user_content .= "DURATA VIDEO: " . $duration_formatted . " (" . $duration_seconds . " secondi)\n\n";
        }

        // Se ci sono capitoli nativi, includili
        if ( ! empty( $native_chapters ) ) {
            $user_content .= "⚠️ CAPITOLI NATIVI YOUTUBE (USA QUESTI):\n";
            $user_content .= $native_chapters . "\n\n";
            $user_content .= "➡️ IMPORTANTE: Usa ESATTAMENTE questi timestamp nella sezione ⏱️ MINUTAGGIO.\n\n";
        }

        $user_content .= "TRASCRIZIONE:\n";
        // Aumentato limite trascrizione per video lunghi: 14k → 30k caratteri
        $user_content .= mb_substr( $transcript, 0, 30000 );

        $body = [
            'model'    => 'gpt-4o',
            'messages' => [
                [
                    'role'    => 'system',
                    'content' => $system_prompt,
                ],
                [
                    'role'    => 'user',
                    'content' => $user_content,
                ],
            ],
            'temperature' => 0.7,
            'max_tokens'  => 3000,
        ];

        $args = [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body'    => wp_json_encode( $body ),
            'timeout' => 120,
        ];

        IPV_Prod_Logger::log( 'OpenAI: Richiesta generazione', [ 'title' => $video_title ] );

        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', $args );
        
        if ( is_wp_error( $response ) ) {
            IPV_Prod_Logger::log( 'OpenAI: Errore', [ 'error' => $response->get_error_message() ] );
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code < 200 || $code >= 300 ) {
            $error_msg = isset( $data['error']['message'] ) ? $data['error']['message'] : 'HTTP ' . $code;
            return new WP_Error( 'ipv_openai_http_error', 'Errore OpenAI: ' . $error_msg );
        }

        if ( empty( $data['choices'][0]['message']['content'] ) ) {
            return new WP_Error( 'ipv_openai_no_content', 'Risposta OpenAI senza contenuto.' );
        }

        $description = trim( $data['choices'][0]['message']['content'] );
        
        IPV_Prod_Logger::log( 'OpenAI: Descrizione generata', [ 'length' => strlen( $description ) ] );

        return $description;
    }

    /**
     * Genera descrizione e salva nel post con estrazione COMPLETA metadata
     * Questo è il metodo principale che automatizza tutto il processo editoriale
     */
    public static function generate_and_save( $post_id ) {
        $video_title = get_the_title( $post_id );
        $transcript  = get_post_meta( $post_id, '_ipv_transcript', true );

        if ( empty( $transcript ) ) {
            return new WP_Error( 'no_transcript', 'Trascrizione mancante' );
        }

        // Ottieni durata video - prova vari meta
        $duration_formatted = get_post_meta( $post_id, '_ipv_yt_duration_formatted', true );
        $duration_seconds   = (int) get_post_meta( $post_id, '_ipv_yt_duration_seconds', true );

        // Fallback: prova _ipv_yt_duration (formato ISO8601 o formattato)
        if ( empty( $duration_formatted ) ) {
            $duration_formatted = get_post_meta( $post_id, '_ipv_yt_duration', true );
        }

        // Fallback: calcola secondi da durata formattata
        if ( ! $duration_seconds && ! empty( $duration_formatted ) ) {
            $duration_seconds = self::parse_duration_to_seconds( $duration_formatted );
        }

        // Log per debug
        IPV_Prod_Logger::log( 'AI: Durata video', [
            'post_id'   => $post_id,
            'formatted' => $duration_formatted,
            'seconds'   => $duration_seconds
        ] );

        // === TENTATIVO 1: Recupera capitoli nativi YouTube ===
        $video_id = get_post_meta( $post_id, '_ipv_video_id', true );
        $native_chapters = null;
        $native_chapters_text = '';

        if ( ! empty( $video_id ) ) {
            $chapters_result = IPV_Prod_YouTube_Chapters::get_chapters( $video_id );

            if ( ! is_wp_error( $chapters_result ) ) {
                $native_chapters = $chapters_result;
                $native_chapters_text = IPV_Prod_YouTube_Chapters::format_chapters_text( $chapters_result );

                IPV_Prod_Logger::log( 'AI: Capitoli nativi YouTube trovati', [
                    'post_id' => $post_id,
                    'count'   => count( $chapters_result ),
                ] );
            } else {
                IPV_Prod_Logger::log( 'AI: Nessun capitolo nativo, AI genererà i timestamp', [
                    'post_id' => $post_id,
                    'reason'  => $chapters_result->get_error_message(),
                ] );
            }
        }

        // === GENERAZIONE AI ===
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

        // === VERIFICA TIMESTAMP COVERAGE (solo se NON ha usato capitoli nativi) ===
        if ( empty( $native_chapters ) && $duration_seconds > 600 ) { // Solo per video > 10 min
            $coverage_ok = IPV_Prod_YouTube_Chapters::verify_timestamp_coverage(
                $description,
                $duration_seconds,
                75 // Richiedi almeno 75% di copertura
            );

            if ( ! $coverage_ok ) {
                IPV_Prod_Logger::log( 'AI: Copertura timestamp insufficiente, richiedo continuazione', [
                    'post_id' => $post_id,
                ] );

                // RETRY: Richiesta di continuazione timestamp
                $description = self::continue_timestamps( $video_title, $transcript, $description, $duration_formatted, $duration_seconds );
            }
        }

        // Salva descrizione
        update_post_meta( $post_id, '_ipv_ai_description', $description );

        // Aggiorna contenuto post
        wp_update_post( [
            'ID'           => $post_id,
            'post_content' => $description,
        ] );

        // === AUTOMAZIONE EDITORIALE COMPLETA ===
        
        // 1. Estrai e salva HASHTAG come Tag WordPress
        self::extract_and_save_hashtags( $post_id, $description );

        // 2. Estrai e salva RELATORI nella tassonomia ipv_relatore
        //    (dal titolo + sezione Ospiti + sezione Persone menzionate)
        self::extract_and_save_speakers( $post_id, $video_title, $description );

        // 3. Estrai e salva CATEGORIE nella tassonomia ipv_categoria
        //    (dalla sezione Argomenti trattati)
        self::extract_and_save_categories( $post_id, $description );

        IPV_Prod_Logger::log( 'AI: Processo editoriale completato', [ 
            'post_id' => $post_id,
            'title'   => $video_title 
        ] );

        return $description;
    }

    /**
     * Estrae hashtag dalla descrizione e li salva come tag WordPress
     */
    public static function extract_and_save_hashtags( $post_id, $description ) {
        $hashtag_line = '';
        
        // Pattern 1: 🏷️ **Hashtag** seguito da hashtag (stessa riga o riga successiva)
        if ( preg_match( '/(?:#️⃣|🏷️)\s*\*?\*?Hashtag\*?\*?\s*[:\n]?\s*(#.+?)(?=\n\n|\n[A-Z🔧📣]|$)/is', $description, $match ) ) {
            $hashtag_line = $match[1];
        }
        // Pattern 2: Cerca riga che inizia con molti hashtag consecutivi
        elseif ( preg_match( '/((?:#[A-Za-zÀ-ÿ0-9_]+\s*){5,})/u', $description, $match ) ) {
            $hashtag_line = $match[1];
        }
        // Fallback: cerca tutti gli hashtag nell'ultima parte della descrizione
        else {
            // Prendi solo l'ultimo 20% della descrizione
            $hashtag_line = mb_substr( $description, -( mb_strlen( $description ) * 0.2 ) );
        }

        // Estrai tutti gli hashtag
        preg_match_all( '/#([A-Za-zÀ-ÿ0-9_]+)/u', $hashtag_line, $matches );

        if ( ! empty( $matches[1] ) ) {
            $tags = array_map( 'trim', $matches[1] );
            $tags = array_unique( $tags );
            $tags = array_slice( $tags, 0, 25 );

            // Filtra tag troppo corti
            $tags = array_filter( $tags, function( $tag ) {
                return strlen( $tag ) >= 3;
            } );

            if ( ! empty( $tags ) ) {
                // Usa wp_set_object_terms per maggiore affidabilità con CPT
                wp_set_object_terms( $post_id, $tags, 'post_tag', false );
                IPV_Prod_Logger::log( 'Hashtag salvati come tag', [ 
                    'post_id' => $post_id, 
                    'count'   => count( $tags ),
                    'tags'    => array_slice( $tags, 0, 5 ) // Log primi 5
                ] );
            }
        }
    }

    /**
     * Estrae relatori/ospiti dal TITOLO e dalla DESCRIZIONE
     * Li salva nella tassonomia ipv_relatore
     */
    public static function extract_and_save_speakers( $post_id, $video_title, $description ) {
        $speakers = [];

        // === PRIORITÀ 0: REGOLE MANUALI (hanno precedenza su tutto) ===
        if ( class_exists( 'IPV_Prod_Speaker_Rules' ) ) {
            $rule_speaker = IPV_Prod_Speaker_Rules::find_speaker_by_title( $video_title );
            if ( $rule_speaker ) {
                $speakers[] = $rule_speaker;
                IPV_Prod_Logger::log( 'Relatore da regola manuale', [ 
                    'post_id' => $post_id, 
                    'speaker' => $rule_speaker,
                    'title'   => $video_title
                ] );
            }
        }

        // === PRIORITÀ 1: ESTRAI DAL TITOLO (solo se nessuna regola trovata) ===
        if ( empty( $speakers ) ) {
            // Pattern per "con Nome Cognome" (anche multipli con "e")
            if ( preg_match( '/\bcon\s+(.+?)(?:\s*#|\s*$)/iu', $video_title, $match ) ) {
                $names_part = $match[1];
                
                // Splitta per "e" o ","
                $parts = preg_split( '/\s+e\s+|\s*,\s*/iu', $names_part );
                
                foreach ( $parts as $name ) {
                    $name = self::normalize_speaker_name( $name );
                    if ( $name && self::is_valid_speaker_name( $name ) ) {
                        $speakers[] = $name;
                    }
                }
            }
            
            // Pattern alternativi se non trovato con "con"
            if ( empty( $speakers ) ) {
                // "TITOLO - Nome Cognome" alla fine
                if ( preg_match( '/[–—-]\s*([A-Z][a-zà-ÿ]+(?:\s+[A-Z][a-zà-ÿ]+)+)\s*$/u', $video_title, $match ) ) {
                    $name = self::normalize_speaker_name( $match[1] );
                    if ( $name && self::is_valid_speaker_name( $name ) ) {
                        $speakers[] = $name;
                    }
                }
            }
        }

        // === PRIORITÀ 2: SOLO SEZIONE "Ospiti" (non "Persone menzionate"!) ===
        if ( empty( $speakers ) ) {
            if ( preg_match( '/👤\s*\*?\*?Ospiti?\*?\*?\s*\n(.+?)(?=\n(?:📌|🗂️|🏛️|🏢|📢|🔗|#️⃣|🏷️)|\n\n)/is', $description, $match ) ) {
                $ospiti_section = trim( $match[1] );
                
                // Verifica che non sia "Nessun ospite"
                if ( stripos( $ospiti_section, 'nessun ospite' ) === false && 
                     stripos( $ospiti_section, 'nessuno' ) === false &&
                     ! empty( $ospiti_section ) ) {
                    
                    // Estrai solo il PRIMO nome trovato per riga (Nome Cognome)
                    $lines = explode( "\n", $ospiti_section );
                    foreach ( $lines as $line ) {
                        // Cerca "Nome Cognome" all'inizio della riga (prima di — o -)
                        if ( preg_match( '/^[-•]?\s*([A-Z][a-zà-ÿ]+\s+[A-Z][a-zà-ÿ]+)/u', trim( $line ), $m ) ) {
                            $name = self::normalize_speaker_name( $m[1] );
                            if ( $name && self::is_valid_speaker_name( $name ) ) {
                                $speakers[] = $name;
                            }
                        }
                    }
                }
            }
        }

        // === FALLBACK: "Il Punto di Vista" se non trovato nulla ===
        if ( empty( $speakers ) ) {
            $speakers[] = 'Il Punto di Vista';
        }

        // === LIMITA A MASSIMO 3 RELATORI ===
        $speakers = array_unique( $speakers );
        $speakers = array_slice( $speakers, 0, 3 );

        // === SALVA ===
        $term_ids = [];
        foreach ( $speakers as $speaker ) {
            $term = term_exists( $speaker, 'ipv_relatore' );
            if ( ! $term ) {
                $term = wp_insert_term( $speaker, 'ipv_relatore' );
            }
            if ( ! is_wp_error( $term ) ) {
                $term_ids[] = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
            }
        }

        if ( ! empty( $term_ids ) ) {
            wp_set_object_terms( $post_id, $term_ids, 'ipv_relatore', false );
            IPV_Prod_Logger::log( 'Relatori salvati', [ 
                'post_id'  => $post_id, 
                'speakers' => $speakers 
            ] );
        }
    }

    /**
     * Estrae categorie dalla sezione "Argomenti trattati"
     * Li salva nella tassonomia ipv_categoria
     */
    public static function extract_and_save_categories( $post_id, $description ) {
        $categories = [];

        // Cerca sezione Argomenti trattati (varie emoji possibili: 📌, 🗂️)
        if ( preg_match( '/(?:📌|🗂️)\s*\*?\*?Argomenti[^*\n]*\*?\*?\s*\n(.+?)(?=\n(?:👤|🏛️|🏢|📢|🔗|#️⃣|🏷️)|\n\n)/is', $description, $match ) ) {
            $argomenti_section = trim( $match[1] );
            
            // Pattern: "- Argomento: spiegazione" oppure "- Argomento"
            preg_match_all( '/[-•]\s*\*?\*?([^:\n*]+?)\*?\*?(?::|—|\n|$)/m', $argomenti_section, $matches );
            
            foreach ( $matches[1] as $cat ) {
                $cat = trim( $cat );
                // Pulisci e normalizza
                $cat = preg_replace( '/\*+/', '', $cat ); // Rimuovi asterischi markdown
                $cat = trim( $cat );
                
                if ( strlen( $cat ) >= 3 && strlen( $cat ) <= 100 ) {
                    $categories[] = $cat;
                }
            }
        }

        // Limita a max 5 categorie principali
        $categories = array_slice( array_unique( $categories ), 0, 5 );

        if ( ! empty( $categories ) ) {
            $term_ids = [];
            foreach ( $categories as $category ) {
                $term = term_exists( $category, 'ipv_categoria' );
                if ( ! $term ) {
                    $term = wp_insert_term( $category, 'ipv_categoria' );
                }
                if ( ! is_wp_error( $term ) ) {
                    $term_ids[] = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
                }
            }

            if ( ! empty( $term_ids ) ) {
                wp_set_object_terms( $post_id, $term_ids, 'ipv_categoria', false );
                IPV_Prod_Logger::log( 'Categorie salvate', [ 
                    'post_id'    => $post_id, 
                    'categories' => $categories 
                ] );
            }
        }
    }

    /**
     * Normalizza nome relatore (capitalizzazione corretta)
     */
    private static function normalize_speaker_name( $name ) {
        $name = trim( $name );
        // Rimuovi caratteri speciali
        $name = preg_replace( '/[#@\d]+/', '', $name );
        $name = trim( $name );
        
        if ( empty( $name ) ) {
            return '';
        }

        // Capitalizza correttamente ogni parola
        $words = explode( ' ', mb_strtolower( $name ) );
        $words = array_map( function( $word ) {
            return mb_convert_case( $word, MB_CASE_TITLE, 'UTF-8' );
        }, $words );

        return implode( ' ', $words );
    }

    /**
     * Verifica se è un nome valido di persona
     */
    private static function is_valid_speaker_name( $name ) {
        // Deve avere almeno nome e cognome (2 parole)
        $words = explode( ' ', $name );
        if ( count( $words ) < 2 ) {
            return false;
        }

        // Filtra termini generici
        $blacklist = [
            'nessun ospite', 'ospite presente', 'canale youtube', 
            'punto vista', 'progetto italia', 'video youtube',
            'argomento', 'introduzione', 'conclusione', 'discussione'
        ];

        $name_lower = mb_strtolower( $name );
        foreach ( $blacklist as $term ) {
            if ( strpos( $name_lower, $term ) !== false ) {
                return false;
            }
        }

        // Deve essere lungo tra 5 e 50 caratteri
        if ( strlen( $name ) < 5 || strlen( $name ) > 50 ) {
            return false;
        }

        return true;
    }

    /**
     * Richiede continuazione timestamp se copertura insufficiente
     *
     * @param string $video_title Titolo video
     * @param string $transcript Trascrizione completa
     * @param string $current_description Descrizione attuale con timestamp incompleti
     * @param string $duration_formatted Durata formattata
     * @param int $duration_seconds Durata in secondi
     * @return string Descrizione aggiornata con timestamp completi
     */
    protected static function continue_timestamps( $video_title, $transcript, $current_description, $duration_formatted, $duration_seconds ) {
        $api_key = get_option( 'ipv_openai_api_key', '' );
        if ( empty( $api_key ) ) {
            // Fallback: restituisci descrizione originale
            return $current_description;
        }

        // Estrai sezione timestamp attuale
        preg_match( '/⏱️ MINUTAGGIO.*?(?=🗂️|$)/s', $current_description, $matches );
        $current_timestamps = isset( $matches[0] ) ? trim( $matches[0] ) : '';

        // Trova ultimo timestamp
        preg_match_all( '/(\d+:\d+(?::\d+)?)\s*—/', $current_timestamps, $time_matches );
        $last_timestamp = ! empty( $time_matches[1] ) ? end( $time_matches[1] ) : '0:00';

        // Prompt di continuazione
        $continuation_prompt = <<<PROMPT
# CONTINUAZIONE TIMESTAMP - "Il Punto di Vista"

## SITUAZIONE
Hai generato una descrizione video ma i timestamp si sono fermati a {$last_timestamp} su un video di {$duration_formatted}.

## TUO COMPITO
Genera SOLO la continuazione dei timestamp dalla posizione {$last_timestamp} fino alla FINE del video ({$duration_formatted}).

⚠️ IMPORTANTE:
- NON riscrivere i timestamp già esistenti
- Parti da DOPO {$last_timestamp}
- Continua fino a {$duration_formatted}
- Genera almeno 8-12 timestamp aggiuntivi
- Segui i cambi di argomento nella trascrizione

## FORMATO OUTPUT
Genera SOLO i timestamp aggiuntivi, uno per riga:
{$last_timestamp} — [ultimo esistente, non ripetere]
[nuovo1] — Titolo argomento
[nuovo2] — Titolo argomento
...
[ultimo vicino a {$duration_formatted}] — Conclusioni

TRASCRIZIONE (dalla parte non ancora coperta):
PROMPT;

        $user_content = $continuation_prompt . "\n\n" . mb_substr( $transcript, 15000, 25000 );

        $body = [
            'model'    => 'gpt-4o',
            'messages' => [
                [
                    'role'    => 'user',
                    'content' => $user_content,
                ],
            ],
            'temperature' => 0.5,
            'max_tokens'  => 1500,
        ];

        $args = [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body'    => wp_json_encode( $body ),
            'timeout' => 60,
        ];

        IPV_Prod_Logger::log( 'AI: Richiesta continuazione timestamp', [
            'last_timestamp'   => $last_timestamp,
            'target_duration'  => $duration_formatted,
        ] );

        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', $args );

        if ( is_wp_error( $response ) ) {
            IPV_Prod_Logger::log( 'AI: Errore continuazione', [ 'error' => $response->get_error_message() ] );
            return $current_description; // Fallback
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code < 200 || $code >= 300 ) {
            return $current_description; // Fallback
        }

        $additional_timestamps = trim( $data['choices'][0]['message']['content'] );

        // Merge timestamp nella descrizione
        $updated_description = preg_replace(
            '/(⏱️ MINUTAGGIO.*?)(?=🗂️)/s',
            '$1' . "\n" . $additional_timestamps . "\n\n",
            $current_description
        );

        IPV_Prod_Logger::log( 'AI: Timestamp aggiornati con successo' );

        return $updated_description;
    }

    /**
     * Converte durata formattata in secondi
     * Gestisce: "1:47:45", "15:30", "PT1H47M45S" (ISO8601)
     */
    protected static function parse_duration_to_seconds( $duration ) {
        if ( empty( $duration ) ) {
            return 0;
        }

        // Formato ISO8601: PT1H47M45S
        if ( preg_match( '/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $matches ) ) {
            $hours   = isset( $matches[1] ) ? (int) $matches[1] : 0;
            $minutes = isset( $matches[2] ) ? (int) $matches[2] : 0;
            $seconds = isset( $matches[3] ) ? (int) $matches[3] : 0;
            return ( $hours * 3600 ) + ( $minutes * 60 ) + $seconds;
        }

        // Formato H:MM:SS o MM:SS
        $parts = array_reverse( explode( ':', $duration ) );
        $seconds = 0;

        if ( isset( $parts[0] ) ) {
            $seconds += (int) $parts[0]; // secondi
        }
        if ( isset( $parts[1] ) ) {
            $seconds += (int) $parts[1] * 60; // minuti
        }
        if ( isset( $parts[2] ) ) {
            $seconds += (int) $parts[2] * 3600; // ore
        }

        return $seconds;
    }

    /**
     * Formatta secondi in H:MM:SS o MM:SS
     */
    protected static function format_duration( $seconds ) {
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
     * Genera istruzioni timestamp basate sulla durata
     */
    protected static function get_timestamp_instructions( $duration_formatted, $duration_seconds ) {
        if ( $duration_seconds <= 0 ) {
            return "Genera timestamp in base ai cambi di argomento nella trascrizione.";
        }

        // Se manca duration_formatted, creala dai secondi
        if ( empty( $duration_formatted ) ) {
            $duration_formatted = self::format_duration( $duration_seconds );
        }

        $duration_minutes = floor( $duration_seconds / 60 );
        $hours = floor( $duration_minutes / 60 );

        // Calcola timestamp finale suggerito (2-3 minuti prima della fine)
        $end_buffer = min( 180, floor( $duration_seconds * 0.05 ) ); // 5% o max 3 min
        $suggested_end_seconds = $duration_seconds - $end_buffer;
        $suggested_end = self::format_duration( $suggested_end_seconds );

        $instructions = "⚠️⚠️⚠️ DURATA TOTALE VIDEO: {$duration_formatted} ({$duration_minutes} minuti) ⚠️⚠️⚠️\n";
        $instructions .= "🚨 ATTENZIONE CRITICA: I timestamp DEVONO ASSOLUTAMENTE coprire TUTTA la durata fino alla FINE del video!\n";
        $instructions .= "🚨 NON FERMARTI A METÀ! Continua fino alla fine della trascrizione!\n\n";
        $instructions .= "ISTRUZIONI TIMESTAMP:\n";
        $instructions .= "- Timestamp iniziale OBBLIGATORIO: 00:00 — Introduzione\n";
        $instructions .= "- Timestamp finale DEVE essere tra {$suggested_end} e {$duration_formatted}\n";
        $instructions .= "- Genera timestamp ad OGNI CAMBIO DI ARGOMENTO nella trascrizione\n";
        $instructions .= "- Distribuisci i timestamp UNIFORMEMENTE lungo TUTTA la trascrizione\n";
        $instructions .= "- Leggi la trascrizione FINO ALLA FINE prima di generare i timestamp\n";
        $instructions .= "- Conta MINIMO 15-20 timestamp per video > 60 minuti\n";
        $instructions .= "- NON usare intervalli fissi - segui il flusso naturale della discussione\n";
        $instructions .= "- ⚠️ IMPORTANTE: Quando arrivi a 'Saluti finali' o 'Ringraziamenti', FERMATI LÌ. NON creare timestamp separati per 'Chiusura', 'Fine', 'Call to Action' dopo i saluti.\n";
        $instructions .= "- L'ultimo timestamp deve essere UNICO e chiamarsi 'Saluti finali' o simile, NON spacchettarlo in più timestamp.\n";

        if ( $hours >= 1 ) {
            $instructions .= "- FORMATO OBBLIGATORIO: H:MM:SS (es: 1:23:45) perché il video supera 1 ora\n";
        } else {
            $instructions .= "- FORMATO: MM:SS (es: 25:30)\n";
        }

        $instructions .= "\n✅ VERIFICA FINALE: L'ultimo timestamp è vicino a {$duration_formatted}? Se no, CONTINUA!\n";

        return $instructions;
    }

    /**
     * Golden Prompt v4.0 - Sistema Editoriale Completo
     * Ottimizzato per: timestamp completi, SEO lungo, categorie da logica titolo+descrizione
     */
    protected static function get_default_prompt( $duration_formatted = '', $duration_seconds = 0, $has_native_chapters = false ) {
        $timestamp_instructions = $has_native_chapters
            ? "⚠️ USA I CAPITOLI NATIVI YOUTUBE FORNITI NEL MESSAGGIO UTENTE.\nCopiali ESATTAMENTE nella sezione ⏱️ MINUTAGGIO."
            : self::get_timestamp_instructions( $duration_formatted, $duration_seconds );

        return <<<PROMPT
# GOLDEN PROMPT v4.4 - "Il Punto di Vista" - Sistema Editoriale YouTube-Friendly

## IDENTITÀ
Sei un copywriter esperto per il canale YouTube italiano **"Il Punto di Vista"** (@ilpuntodivista_official).
Temi: esoterismo, spiritualità, misteri, geopolitica alternativa, disclosure.

## LINK FISSI DEL CANALE (USA SEMPRE QUESTI)
- Telegram: https://t.me/il_punto_divista
- Facebook: https://facebook.com/groups/4102938329737588
- Instagram: https://instagram.com/_ilpuntodivista._
- Sito: https://ilpuntodivistachannel.com
- Donazioni: https://paypal.me/adrianfiorelli
- Sponsor Biovital: https://biovital-italia.com/?bio=17

## FORMATO OUTPUT OBBLIGATORIO

Genera la descrizione ESATTAMENTE in questo formato (YouTube-friendly, senza Markdown):

[TITOLO VIDEO ESATTO]

✨ INTRODUZIONE
[150-200 parole. Descrizione coinvolgente ottimizzata SEO. Struttura in 3 paragrafi:
1° paragrafo (50-60 parole): Presenta "Il Punto di Vista" e il tema principale del video. Menziona gli ospiti se presenti.
2° paragrafo (50-70 parole): Approfondisci i contenuti chiave, i misteri trattati, le teorie discusse. Usa parole chiave specifiche.
3° paragrafo (40-50 parole): Invito all'azione e keywords. Termina con: "Le parole chiave come 'X', 'Y', 'Z' guidano la ricerca verso una comprensione più profonda."
IMPORTANTE: Testo SEMPLICE senza markdown, usa termini specifici e keywords rilevanti per la SEO YouTube]

⏱️ MINUTAGGIO
{$timestamp_instructions}
00:00 — Introduzione
[...genera timestamp ad ogni CAMBIO DI ARGOMENTO fino alla FINE del video...]

🗂️ ARGOMENTI TRATTATI
• [Argomento 1]: [spiegazione 1-2 frasi]
• [Argomento 2]: [spiegazione 1-2 frasi]
• [Argomento 3]: [spiegazione 1-2 frasi]
[8-12 argomenti. IMPORTANTE: questi diventeranno le CATEGORIE del video. Usa nomi CHIARI e SPECIFICI come "Energia libera", "Disclosure UFO", "Misteri antichi", "Geopolitica occulta", etc]

👤 OSPITI
[Nome e Cognome dell'ospite/i che PARLANO nel video]
[Se nessuno parla oltre al conduttore: "Nessun ospite presente"]

🏛️ PERSONE / ENTI MENZIONATI
• [Nome Cognome] — [Chi è: ruolo, canale, professione]
• [Nome Ente] — [Descrizione]
[TUTTE le persone CITATE nella trascrizione, anche se non ospiti]

🤝 SPONSOR
Biovital – Progetto Italia
Sostieni il progetto 👉 https://biovital-italia.com/?bio=17

📣 CALL TO ACTION
• Iscriviti al canale
• Commenta
• Condividi il video

🔧 LINK UTILI
📱 Telegram: https://t.me/il_punto_divista
👥 Facebook: https://facebook.com/groups/4102938329737588
📸 Instagram: https://instagram.com/_ilpuntodivista._
🌐 Sito ufficiale: https://ilpuntodivistachannel.com
💝 Donazioni: https://paypal.me/adrianfiorelli

🏷️ HASHTAG
#Hashtag1 #Hashtag2 #Hashtag3 ... #IlPuntoDiVista #PuntiDiVista
[20-25 hashtag su UNA RIGA, includi sempre #IlPuntoDiVista #PuntiDiVista]

## REGOLE CRITICHE

### 🚨 TIMESTAMP (PRIORITÀ ASSOLUTA)
- 🔴 I timestamp DEVONO coprire TUTTA la durata del video FINO ALLA FINE
- 🔴 L'ULTIMO timestamp deve essere vicino alla fine (entro gli ultimi 2-3 minuti)
- 🔴 Se il video dura 1:47:45, l'ultimo timestamp deve essere tra 1:43:00 e 1:47:00
- 🔴 NON FERMARTI A METÀ VIDEO! Questa è la priorità #1!
- Posiziona i timestamp in base ai CAMBI DI ARGOMENTO nella trascrizione
- Leggi TUTTA la trascrizione prima di generare i timestamp
- Distribuisci i timestamp UNIFORMEMENTE lungo tutta la durata
- Video > 60 minuti: MINIMO 15-20 timestamp
- Video 30-60 minuti: MINIMO 10-15 timestamp
- NON usare intervalli fissi - segui il flusso naturale della discussione
- Formato: MM:SS per video < 1 ora, H:MM:SS per video ≥ 1 ora

### 📊 ARGOMENTI TRATTATI (per CATEGORIE)
- Scrivi 8-12 argomenti CHIARI e SPECIFICI
- Saranno usati come CATEGORIE del video nella piattaforma WordPress
- Usa nomi concisi ma descrittivi (max 3-4 parole)
- Esempi BUONI:
  * "Energia libera"
  * "Disclosure UFO"
  * "Tartaria e architettura antica"
  * "Geopolitica occulta"
  * "Simbolismo esoterico"
  * "Misteri storici"
  * "Tecnologia nascosta"
- Esempi CATTIVI:
  * "Discussione generale" (troppo vago)
  * "Parlano di cose interessanti" (non specifico)
  * "Tema principale del video" (generico)
- Le categorie devono essere estraibili dal TITOLO + CONTENUTO del video
- Crea un mix tra argomenti generali (es: "UFO") e specifici (es: "Incidente di Roswell")

### 👤 OSPITI E RELATORI
- OSPITI = chi PARLA nel video (oltre al conduttore)
- PERSONE MENZIONATE = chi viene CITATO ma non parla
- Includi SEMPRE Nome e Cognome completi
- Se estrai un nome dal TITOLO, mettilo negli Ospiti
- Se NON ci sono ospiti, scrivi: "Nessun ospite presente"
- Il sistema assegnerà "Il Punto di Vista" come relatore di default se non trovi ospiti

### 🔗 LINK UTILI
- USA SEMPRE i link ESATTI forniti sopra
- Formato: "📱 Telegram: URL" (emoji + testo + URL bare)
- YouTube auto-linkifica gli URL
- NON usare sintassi Markdown [testo](url)
- NON inventare link
- Verifica che TUTTI i link siano presenti nella sezione Link Utili

### 🏷️ HASHTAG
- 20-25 hashtag
- TUTTI su UNA SOLA RIGA
- Includi hashtag per ogni persona menzionata (#NomeCognome senza spazi)
- Includi hashtag per ogni argomento principale (#EnergiLibera #UFO etc)
- Sempre OBBLIGATORI: #IlPuntoDiVista #PuntiDiVista
- Esempi: #Disclosure #UFO #Tartaria #EnergiaLibera #GeopoliticaOcculta

## OUTPUT
Genera SOLO la descrizione formattata.
NESSUN commento aggiuntivo.
USA I LINK ESATTI forniti.

⚠️ IMPORTANTE FORMATTAZIONE:
- NON usare Markdown: niente **bold**, niente [link](url), niente # heading
- Usa SOLO: MAIUSCOLO per enfasi, emoji per separatori, URL bare
- Formato YouTube-friendly: copia/incolla diretta su YouTube
- Gli URL saranno auto-linkificati da YouTube
PROMPT;
    }
}
