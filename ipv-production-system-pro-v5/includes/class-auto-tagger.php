<?php
/**
 * Auto-Tagger intelligente per IPV Production System Pro v5
 *
 * Popola automaticamente le tassonomie Relatori e Argomenti
 * analizzando titolo, descrizione e trascrizione del video
 *
 * @package IPV_Production_System_Pro
 * @version 5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Auto_Tagger {

    /**
     * Pattern comuni per identificare relatori nei titoli
     */
    private static $relatori_patterns = [
        '/(?:con|ospite|intervista a?)\s+([A-ZÀÈÌÒÙ][a-zàèéìòù]+(?:\s+[A-ZÀÈÌÒÙ][a-zàèéìòù]+)+)/iu',
        '/([A-ZÀÈÌÒÙ][a-zàèéìòù]+\s+[A-ZÀÈÌÒÙ][a-zàèéìòù]+)\s+[:-]/iu',
        '/^([A-ZÀÈÌÒÙ][a-zàèéìòù]+\s+[A-ZÀÈÌÒÙ][a-zàèéìòù]+)\s*[|–-]/iu',
    ];

    /**
     * Argomenti predefiniti con parole chiave
     */
    private static $argomenti_keywords = [
        'UFO' => ['ufo', 'extraterresti', 'alieni', 'disclosure', 'uap', 'fenomeni uap', 'avvistamenti'],
        'Spiritualità' => ['spiritualità', 'meditazione', 'coscienza', 'risveglio', 'illuminazione', 'chakra', 'energia'],
        'Esoterismo' => ['esoterismo', 'alchimia', 'tarocchi', 'cabala', 'ermetismo', 'simbolismo'],
        'Geopolitica' => ['geopolitica', 'economia', 'politica', 'guerra', 'conflitto', 'governo', 'élite'],
        'Salute' => ['salute', 'medicina', 'cure', 'benessere', 'alimentazione', 'detox', 'natural'],
        'Storia Alternativa' => ['storia', 'civiltà antiche', 'archeologia', 'misteri', 'antichi', 'sumeri', 'egizi'],
        'Scienza' => ['scienza', 'fisica', 'quantistica', 'tecnologia', 'ricerca', 'scoperta'],
        'Cospirazione' => ['complotto', 'verità', 'nascosta', 'segreto', 'cover-up', 'insabbiamento'],
        'Religione' => ['religione', 'bibbia', 'vangelo', 'chiesa', 'cattolica', 'cristian', 'islam'],
        'Economia' => ['economia', 'finanza', 'cripto', 'bitcoin', 'moneta', 'banche', 'sistema monetario'],
    ];

    /**
     * Auto-tag del video al salvataggio
     */
    public static function auto_tag_video( $post_id, $post ) {
        // Evita loop infiniti
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Verifica permessi
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Solo per video_ipv
        if ( $post->post_type !== 'video_ipv' ) {
            return;
        }

        // Ottieni i contenuti
        $title       = $post->post_title;
        $content     = $post->post_content;
        $description = get_post_meta( $post_id, '_ipv_yt_description', true );
        $transcript  = get_post_meta( $post_id, '_ipv_transcript', true );

        // Estrai relatori
        $relatori = self::extract_relatori( $title, $description, $transcript );
        if ( ! empty( $relatori ) ) {
            wp_set_object_terms( $post_id, $relatori, 'ipv_relatore', false );
            IPV_Prod_Logger::log( 'Auto-Tagger: Relatori identificati', [
                'post_id'   => $post_id,
                'relatori'  => $relatori,
            ] );
        }

        // Estrai argomenti
        $argomenti = self::extract_argomenti( $title, $description, $transcript, $content );
        if ( ! empty( $argomenti ) ) {
            wp_set_object_terms( $post_id, $argomenti, 'ipv_argomento', false );
            IPV_Prod_Logger::log( 'Auto-Tagger: Argomenti identificati', [
                'post_id'   => $post_id,
                'argomenti' => $argomenti,
            ] );
        }

        // Estrai anno
        $anno = self::extract_anno( $post );
        if ( $anno ) {
            wp_set_object_terms( $post_id, [ $anno ], 'ipv_anno', false );
        }
    }

    /**
     * Estrae relatori da titolo, descrizione e trascrizione
     */
    private static function extract_relatori( $title, $description = '', $transcript = '' ) {
        $relatori = [];
        $text = $title . ' ' . $description;

        // Cerca pattern nei titoli
        foreach ( self::$relatori_patterns as $pattern ) {
            if ( preg_match_all( $pattern, $text, $matches ) ) {
                foreach ( $matches[1] as $match ) {
                    $match = trim( $match );
                    // Verifica che sia un nome proprio (almeno 2 parole, capitalizzate)
                    if ( self::is_valid_name( $match ) ) {
                        $relatori[] = $match;
                    }
                }
            }
        }

        // Cerca pattern "Dr./Prof." nel testo
        if ( preg_match_all( '/(Dr\.|Prof\.|Dott\.)\s+([A-ZÀÈÌÒÙ][a-zàèéìòù]+(?:\s+[A-ZÀÈÌÒÙ][a-zàèéìòù]+)+)/iu', $text, $matches ) ) {
            foreach ( $matches[0] as $match ) {
                $match = trim( $match );
                if ( self::is_valid_name( $match ) ) {
                    $relatori[] = $match;
                }
            }
        }

        // Rimuovi duplicati e normalizza
        $relatori = array_unique( $relatori );
        $relatori = array_map( [ __CLASS__, 'normalize_name' ], $relatori );

        return $relatori;
    }

    /**
     * Estrae argomenti basandosi su keywords
     */
    private static function extract_argomenti( $title, $description = '', $transcript = '', $content = '' ) {
        $argomenti = [];
        $text = strtolower( $title . ' ' . $description . ' ' . $content . ' ' . substr( $transcript, 0, 2000 ) );

        foreach ( self::$argomenti_keywords as $argomento => $keywords ) {
            foreach ( $keywords as $keyword ) {
                if ( stripos( $text, $keyword ) !== false ) {
                    $argomenti[] = $argomento;
                    break; // Una keyword basta per assegnare l'argomento
                }
            }
        }

        return array_unique( $argomenti );
    }

    /**
     * Estrae l'anno dalla data di pubblicazione
     */
    private static function extract_anno( $post ) {
        // Prima prova dalla meta YouTube
        $yt_published = get_post_meta( $post->ID, '_ipv_yt_published_at', true );
        if ( $yt_published ) {
            return date( 'Y', strtotime( $yt_published ) );
        }

        // Fallback: data del post
        return date( 'Y', strtotime( $post->post_date ) );
    }

    /**
     * Verifica se una stringa è un nome valido
     */
    private static function is_valid_name( $name ) {
        // Almeno 2 parole
        $words = explode( ' ', trim( $name ) );
        if ( count( $words ) < 2 ) {
            return false;
        }

        // Ogni parola deve iniziare con maiuscola
        foreach ( $words as $word ) {
            if ( empty( $word ) || ! preg_match( '/^[A-ZÀÈÌÒÙ]/', $word ) ) {
                return false;
            }
        }

        // Lunghezza ragionevole (tra 5 e 50 caratteri)
        $len = strlen( $name );
        if ( $len < 5 || $len > 50 ) {
            return false;
        }

        // Esclude pattern comuni non-nomi
        $excluded = ['Il Punto', 'La Verità', 'Nuovo Ordine', 'Video', 'Mondo'];
        foreach ( $excluded as $exclude ) {
            if ( stripos( $name, $exclude ) !== false ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Normalizza un nome
     */
    private static function normalize_name( $name ) {
        // Rimuovi titoli accademici duplicati
        $name = preg_replace( '/(Dr\.|Prof\.|Dott\.)\s+/i', '', $name );
        $name = trim( $name );

        // Capitalizza correttamente
        return ucwords( strtolower( $name ) );
    }

    /**
     * Aggiungi manualmente un relatore
     */
    public static function add_relatore( $post_id, $relatore_name ) {
        $relatore_name = self::normalize_name( $relatore_name );

        if ( ! self::is_valid_name( $relatore_name ) ) {
            return new WP_Error( 'invalid_name', 'Nome relatore non valido' );
        }

        wp_set_object_terms( $post_id, [ $relatore_name ], 'ipv_relatore', true );

        return true;
    }

    /**
     * Aggiungi manualmente un argomento
     */
    public static function add_argomento( $post_id, $argomento_name ) {
        wp_set_object_terms( $post_id, [ $argomento_name ], 'ipv_argomento', true );

        return true;
    }
}
