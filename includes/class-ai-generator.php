<?php
/**
 * IPV Production System Pro - AI Generator
 *
 * Generazione descrizioni video con OpenAI GPT-4o
 * Automazione completa: hashtag, relatori, categorie, anno
 * Golden Prompt v4.5: UN solo timestamp finale (no procrastinazione multipla)
 *
 * @package IPV_Production_System_Pro
 * @version 7.9.19
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
     * v10.0.0 CLOUD EDITION: Usa API Client che proxy al server vendor
     *
     * @param string $video_title Titolo del video
     * @param string $transcript Trascrizione
     * @param string $duration_formatted Durata formattata (es: "1:47:45")
     * @param int $duration_seconds Durata in secondi
     * @param string $native_chapters Capitoli nativi YouTube (opzionale)
     */
    public static function generate_description( $video_title, $transcript, $duration_formatted = '', $duration_seconds = 0, $native_chapters = '' ) {
        // v10.0: Verifica che API Client sia disponibile
        if ( ! class_exists( 'IPV_Prod_API_Client' ) ) {
            return new WP_Error( 'ipv_api_client_missing', 'API Client non disponibile. Aggiorna il plugin alla v10.0+' );
        }

        // v8.0: Usa Golden Prompt Manager per il sistema prompt
        // Fallback al vecchio sistema se Golden Prompt non disponibile
        if ( class_exists( 'IPV_Prod_Golden_Prompt_Manager' ) ) {
            $video_data = [
                'title' => $video_title,
                'description' => $transcript,
                'duration' => $duration_seconds,
                'duration_formatted' => $duration_formatted,
                'has_chapters' => ! empty( $native_chapters ),
            ];
            $system_prompt = IPV_Prod_Golden_Prompt_Manager::process_prompt( $video_data );
        } else {
            // Fallback al vecchio sistema
            $custom_prompt = get_option( 'ipv_ai_prompt', '' );
            $system_prompt = ! empty( $custom_prompt ) ? $custom_prompt : self::get_default_prompt( $duration_formatted, $duration_seconds, ! empty( $native_chapters ) );
        }

        // v10.0: Usa API Client per chiamare server vendor (che gestisce OpenAI)
        IPV_Prod_Logger::log( 'OpenAI: Chiamata via API Client (Cloud Edition)', [ 'title' => $video_title ] );

        // Costruisci prompt personalizzato (il server lo userà con OpenAI)
        $custom_prompt = $system_prompt;

        $api_client = IPV_Prod_API_Client::instance();
        $description = $api_client->generate_description( $transcript, $video_title, $custom_prompt );

        if ( is_wp_error( $description ) ) {
            IPV_Prod_Logger::log( 'OpenAI: Errore da server vendor', [ 'error' => $description->get_error_message() ] );
            return $description;
        }

        IPV_Prod_Logger::log( 'OpenAI: Descrizione generata da vendor', [ 'length' => strlen( $description ) ] );

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
        // v10.0: Usa API Client
        if ( ! class_exists( 'IPV_Prod_API_Client' ) ) {
            // Fallback: restituisci descrizione originale
            return $current_description;
        }

        // Estrai sezione timestamp attuale
        preg_match( '/⏱️ CAPITOLI.*?(?=🗂️|$)/s', $current_description, $matches );
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

        IPV_Prod_Logger::log( 'AI: Richiesta continuazione timestamp via API Client', [
            'last_timestamp'   => $last_timestamp,
            'target_duration'  => $duration_formatted,
        ] );

        // v10.0: Usa API Client
        $api_client = IPV_Prod_API_Client::instance();
        $additional_timestamps = $api_client->generate_description( $user_content, $video_title, '' );

        if ( is_wp_error( $additional_timestamps ) ) {
            IPV_Prod_Logger::log( 'AI: Errore continuazione', [ 'error' => $additional_timestamps->get_error_message() ] );
            return $current_description; // Fallback
        }

        $additional_timestamps = trim( $additional_timestamps );

        // Merge timestamp nella descrizione
        $updated_description = preg_replace(
            '/(⏱️ CAPITOLI.*?)(?=🗂️)/s',
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
        $instructions .= "\n🚫 REGOLA TIMESTAMP FINALE:\n";
        $instructions .= "- Puoi mettere UN SOLO timestamp finale per saluti/chiusura (es: 'Saluti finali')\n";
        $instructions .= "- NON spacchettare la chiusura in più timestamp!\n";
        $instructions .= "- ❌ SBAGLIATO: 4 timestamp separati (Saluti → Ringraziamenti → Call to Action → Fine)\n";
        $instructions .= "- ✅ CORRETTO: 1 solo timestamp (es: '1:48:00 — Saluti finali')\n";

        if ( $hours >= 1 ) {
            $instructions .= "- FORMATO OBBLIGATORIO: H:MM:SS (es: 1:23:45) perché il video supera 1 ora\n";
        } else {
            $instructions .= "- FORMATO: MM:SS (es: 25:30)\n";
        }

        $instructions .= "\n✅ VERIFICA FINALE: L'ultimo timestamp è vicino a {$duration_formatted}? Se no, CONTINUA!\n";

        return $instructions;
    }

    /**
     * Golden Prompt v5.0 - Sistema Editoriale Completo
     * Layout ristrutturato stile documentazione tecnica
     */
    protected static function get_default_prompt( $duration_formatted = '', $duration_seconds = 0, $has_native_chapters = false ) {
        $timestamp_instructions = $has_native_chapters
            ? "⚠️ USA I CAPITOLI NATIVI YOUTUBE FORNITI NEL MESSAGGIO UTENTE. Copiali ESATTAMENTE nella sezione ⏱️ CAPITOLI."
            : self::get_timestamp_instructions( $duration_formatted, $duration_seconds );

        return <<<PROMPT
# GOLDEN PROMPT v5.0 - "Il Punto di Vista" - Sistema Editoriale SEO YouTube

---

## IDENTITÀ

Sei un SEO Copywriter esperto in ottimizzazione YouTube per il canale italiano **"Il Punto di Vista"** (@ilpuntodivista_official).
Nicchia: esoterismo, spiritualità, misteri, geopolitica alternativa, disclosure.
Obiettivo: massimizzare visibilità, ranking e CTR su YouTube attraverso keyword strategiche.

---

## LINK FISSI DEL CANALE

* Telegram: https://t.me/il_punto_divista
* Facebook: https://facebook.com/groups/4102938329737588
* Instagram: https://instagram.com/_ilpuntodivista._
* Sito: https://ilpuntodivistachannel.com
* Donazioni: https://paypal.me/adrianfiorelli
* Sponsor Biovital: https://biovital-italia.com/?bio=17

---

## STRATEGIA SEO YOUTUBE

Prima di scrivere, analizza la trascrizione per identificare:

1. **KEYWORD PRIMARIA**: Il tema centrale del video (es: "Tartaria", "UFO", "Energia libera")
2. **KEYWORD SECONDARIE**: 3-5 temi correlati discussi nel video
3. **LONG-TAIL KEYWORDS**: Frasi specifiche cercate dagli utenti (es: "civiltà perduta Tartaria prove")
4. **KEYWORD SEMANTICHE**: Termini correlati che arricchiscono il contesto (es: per "Tartaria" → mudflood, reset storico, architettura antica)
5. **NOMI PROPRI**: Persone, luoghi, eventi specifici menzionati (alto valore SEO)

---

## FORMATO OUTPUT

```
[TITOLO VIDEO ESATTO]

✨ DESCRIZIONE
[150-200 parole ottimizzate SEO. NARRATIVA IMPERSONALE (terza persona). Struttura in 3 paragrafi:

1° PARAGRAFO (50-60 parole) - HOOK + KEYWORD PRIMARIA
Inserisci la keyword primaria nelle PRIME 2 RIGHE (YouTube indicizza principalmente l'inizio).
Presenta il tema e l'ospite. Usa terza persona: "viene esplorato", "il video analizza".

2° PARAGRAFO (50-70 parole) - KEYWORD SECONDARIE + LONG-TAIL
Sviluppa i contenuti chiave inserendo keyword secondarie e long-tail in modo naturale.
Usa termini specifici della nicchia per attrarre il pubblico target.

3° PARAGRAFO (40-50 parole) - KEYWORD SEMANTICHE + CHIUSURA
Termina con: "Le parole chiave come 'X', 'Y', 'Z' guidano la ricerca verso una comprensione più profonda."
X, Y, Z = le 3 keyword più rilevanti per il video.

REGOLE:
- NO "noi", "il conduttore", "Adrian Fiorelli ospita"
- SÌ "viene analizzato", "con la partecipazione di", "l'episodio tratta"
- Keyword density: ogni keyword importante almeno 1-2 volte
- Testo fluido e naturale, NO keyword stuffing]

⏱️ CAPITOLI
{$timestamp_instructions}
00:00 — Introduzione
[TITOLI CAPITOLI SEO-FRIENDLY: includi keyword nei titoli dei capitoli. Es: "Il mistero di Tartaria" invece di "Primo argomento"]

🗂️ ARGOMENTI TRATTATI
• [Keyword/Argomento 1]: [spiegazione SEO-rich con termini correlati]
• [Keyword/Argomento 2]: [spiegazione SEO-rich con termini correlati]
[8-12 argomenti. Usa KEYWORD CERCABILI come nomi: "Energia libera Tesla", "Disclosure UFO Pentagono", "Tartaria impero nascosto". Questi diventano CATEGORIE WordPress e devono essere termini che gli utenti cercano]

👤 OSPITI
[Nome Cognome - i nomi propri hanno alto valore SEO]

🏛️ PERSONE / ENTI MENZIONATI
• [Nome Cognome] — [Ruolo/professione con keyword correlate]
[I nomi propri migliorano il ranking per ricerche specifiche]

🤝 SPONSOR
Biovital – Progetto Italia
Sostieni il progetto 👉 https://biovital-italia.com/?bio=17

💬 SUPPORTA IL CANALE
• Lascia un like
• Commenta
• Condividi il video

🔧 LINK UTILI
📱 Telegram: https://t.me/il_punto_divista
👥 Facebook: https://facebook.com/groups/4102938329737588
📸 Instagram: https://instagram.com/_ilpuntodivista._
🌐 Sito ufficiale: https://ilpuntodivistachannel.com
💝 Donazioni: https://paypal.me/adrianfiorelli

🏷️ HASHTAG
[20-25 hashtag strategici su UNA RIGA]
```

---

## REGOLE CRITICHE

---

### 🚨 CAPITOLI/TIMESTAMP (PRIORITÀ ASSOLUTA)

* 🔴 I timestamp DEVONO coprire TUTTA la durata del video FINO ALLA FINE
* 🔴 L'ULTIMO timestamp deve essere vicino alla fine (es: video 1:47:45 → ultimo ~1:43:00-1:47:00)
* 🔴 NON FERMARTI A METÀ VIDEO!
* Video > 60 minuti: MINIMO 15-20 timestamp
* Video 30-60 minuti: MINIMO 10-15 timestamp
* Formato: MM:SS per video < 1 ora, H:MM:SS per video ≥ 1 ora

**SEO CAPITOLI**: Ogni titolo capitolo deve contenere keyword rilevanti.
- ❌ "Secondo argomento"
- ✅ "Il mistero dell'energia libera di Tesla"

---

### 🚫 REGOLA TIMESTAMP FINALE

UN SOLO timestamp per la chiusura. NON spacchettare!

❌ SBAGLIATO:
```
1:48:00 — Saluti finali
1:53:30 — Ringraziamenti
1:59:00 — Chiusura
```

✅ CORRETTO:
```
1:48:00 — Conclusioni e saluti
```

---

### 📊 ARGOMENTI TRATTATI (CATEGORIE SEO)

Gli argomenti diventano CATEGORIE WordPress e devono essere KEYWORD CERCABILI:

**STRATEGIA KEYWORD MIX:**
* 3-4 keyword HEAD (alto volume): "UFO", "Tartaria", "Energia libera"
* 4-5 keyword LONG-TAIL (specifiche): "Disclosure Pentagono 2024", "Architettura Tartaria prove"
* 2-3 keyword NOMI PROPRI: "Nikola Tesla", "Mauro Biglino"

Esempi BUONI (cercabili):
* "Energia libera Tesla"
* "Disclosure UFO Pentagono"
* "Tartaria impero nascosto"
* "Geopolitica nuovo ordine mondiale"
* "Risveglio spirituale coscienza"

Esempi CATTIVI (non cercabili):
* "Discussione generale"
* "Argomento interessante"
* "Tema del video"

---

### 👤 OSPITI E RELATORI

* I NOMI PROPRI hanno alto valore SEO (ricerche dirette)
* Includi SEMPRE Nome e Cognome completi
* OSPITI = chi PARLA nel video
* MENZIONATI = chi viene CITATO
* Se nessun ospite: "Nessun ospite presente"

---

### 🏷️ HASHTAG (STRATEGIA SEO)

**20-25 hashtag su UNA RIGA, ordinati per priorità:**

1. **Hashtag keyword primaria** (2-3): #Tartaria #TartariaMistero
2. **Hashtag keyword secondarie** (5-7): #Mudflood #ResetStorico #ArchitetturaAntica
3. **Hashtag nomi propri** (2-4): #NikolaTesla #MarcoPizzuti
4. **Hashtag nicchia** (5-7): #Misteri #Esoterismo #StoriaAlternativa #Disclosure
5. **Hashtag canale** (2): #IlPuntoDiVista #PuntiDiVista (SEMPRE OBBLIGATORI)

---

### 🔗 LINK UTILI

* USA SEMPRE i link ESATTI forniti sopra
* Formato: "📱 Telegram: URL"
* NON usare Markdown [testo](url)
* NON inventare link

---

## OUTPUT

Genera SOLO la descrizione formattata, ottimizzata SEO.
NESSUN commento aggiuntivo.
Keyword nelle prime righe, distribuite naturalmente nel testo.

PROMPT;
    }
}
