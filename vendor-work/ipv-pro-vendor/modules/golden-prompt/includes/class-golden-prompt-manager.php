<?php
/**
 * IPV Golden Prompt Manager
 *
 * Gestisce la configurazione e distribuzione dei Golden Prompts per ogni licenza.
 * Il Golden Prompt è memorizzato lato server e sincronizzato con il client.
 *
 * @package IPV_Pro_Vendor
 * @since 1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Golden_Prompt_Manager {

    private static $instance = null;

    /**
     * Template universale del Golden Prompt
     */
    private $universal_template = '';

    /**
     * Campi configurabili
     */
    private $config_fields = [
        'nome_canale' => [
            'label' => 'Nome Canale',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Es: Your Channel Name'
        ],
        'handle_youtube' => [
            'label' => 'Handle YouTube',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Es: your_channel_handle (senza @)'
        ],
        'nicchia' => [
            'label' => 'Nicchia/Settore',
            'type' => 'text',
            'required' => true,
            'placeholder' => 'Es: esoterismo, spiritualità, misteri'
        ],
        'link_telegram' => [
            'label' => 'Link Telegram',
            'type' => 'url',
            'required' => false,
            'placeholder' => 'https://t.me/...'
        ],
        'link_facebook' => [
            'label' => 'Link Facebook',
            'type' => 'url',
            'required' => false,
            'placeholder' => 'https://facebook.com/...'
        ],
        'link_instagram' => [
            'label' => 'Link Instagram',
            'type' => 'url',
            'required' => false,
            'placeholder' => 'https://instagram.com/...'
        ],
        'link_sito' => [
            'label' => 'Sito Web',
            'type' => 'url',
            'required' => false,
            'placeholder' => 'https://...'
        ],
        'link_donazioni' => [
            'label' => 'Link Donazioni',
            'type' => 'url',
            'required' => false,
            'placeholder' => 'https://example.com/donate'
        ],
        'sponsor_nome' => [
            'label' => 'Nome Sponsor',
            'type' => 'text',
            'required' => false,
            'placeholder' => 'Es: Sponsor Name'
        ],
        'sponsor_descrizione' => [
            'label' => 'Descrizione Sponsor',
            'type' => 'textarea',
            'required' => false,
            'placeholder' => 'Breve descrizione dello sponsor...'
        ],
        'sponsor_link' => [
            'label' => 'Link Sponsor',
            'type' => 'url',
            'required' => false,
            'placeholder' => 'https://...'
        ],
        'email_business' => [
            'label' => 'Email Business',
            'type' => 'email',
            'required' => false,
            'placeholder' => 'info@tuosito.com'
        ],
        'bio_canale' => [
            'label' => 'Bio Canale',
            'type' => 'textarea',
            'required' => false,
            'placeholder' => 'Descrizione del canale in 2-3 righe...'
        ],
        'hashtag_canale' => [
            'label' => 'Hashtag Canale',
            'type' => 'text',
            'required' => false,
            'placeholder' => 'Es: YourChannelName (senza #)'
        ]
    ];

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->load_universal_template();
    }

    /**
     * Carica il template universale dal database o file
     */
    private function load_universal_template() {
        $this->universal_template = get_option( 'ipv_golden_prompt_template', $this->get_default_template() );
    }

    /**
     * Template di default
     */
    private function get_default_template() {
        return '# GOLDEN PROMPT v6.5 - "{NOME_CANALE}"

---

## IDENTITÀ

Sei un SEO Copywriter esperto per il canale YouTube "{NOME_CANALE}" (@{HANDLE_YOUTUBE}).
Nicchia: {NICCHIA}.

---

## STRATEGIA SEO (CRITICA!)

**YouTube indicizza principalmente le PRIME PAROLE. Il punteggio SEO dipende da questo!**

La KEYWORD PRIMARIA (il tema centrale del video) deve essere:
- LA PRIMA PAROLA del titolo
- LA PRIMA PAROLA dell\'hook
- Nella PRIMA FRASE della descrizione

**❌ ERRORI CHE ABBASSANO IL SEO:**
- "Notiziario Speciale: Nome Ospite..." → keyword assente all\'inizio
- "In questo video parliamo di..." → troppo generico
- "Con la partecipazione di..." → keyword non in prima posizione

**✅ ESEMPI CORRETTI (SEO 80-90/100):**
- "Marketing digitale: strategie 2025 con Mario Rossi" → MARKETING prima parola
- "Investimenti immobiliari: come iniziare da zero" → INVESTIMENTI prima parola

**REGOLA D\'ORO:** Identifica il TEMA PRINCIPALE del video e mettilo come PRIMA PAROLA.

---

## REGOLE OUTPUT

NON SCRIVERE MAI:
- Testo tra parentesi quadre [come questo]
- Placeholder o istruzioni
- Inizi generici: "In questo video", "Speciale:", "Con la partecipazione di"

SCRIVI SEMPRE:
- Contenuto REALE dal transcript
- KEYWORD PRIMARIA come PRIMA PAROLA di titolo e hook

---

## STRUTTURA SEPARATORI

USA `●▬▬▬▬ TITOLO ▬▬▬▬●` per:
- RISORSE MENZIONATE (se presenti)
- DESCRIZIONE
- INDICE

USA `▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼` per:
- CTA iscrizione (dopo l\'hook)
- Box SPONSOR (prima e dopo)

TUTTO IL RESTO = semplici titoli in MAIUSCOLO con emoji

---

## FORMATO OUTPUT

```
[Titolo con KEYWORD PRIMARIA come prima parola]
[Hook 2-3 righe con KEYWORD PRIMARIA come prima parola]

▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼
⏰ Iscriviti Al Canale + 🔔: https://www.youtube.com/@{HANDLE_YOUTUBE}?sub_confirmation=1

Vuoi rimanere aggiornato sui contenuti?
Seguici su Telegram: {LINK_TELEGRAM}

●▬▬▬▬ RISORSE MENZIONATE ▬▬▬▬●
[Solo se ci sono risorse citate nel video, altrimenti ometti]
📖 Titolo Libro — Autore
🎬 Documentario/Film
🔗 Nome Sito: URL
👤 Nome Esperto — Chi è

●▬▬▬▬ DESCRIZIONE ▬▬▬▬●
[Paragrafo 1: KEYWORD PRIMARIA all\'inizio, presenta tema e ospite, terza persona]

[Paragrafo 2: sviluppa contenuti, keyword secondarie]

[Paragrafo 3: "Le parole chiave come \'X\', \'Y\', \'Z\' guidano la ricerca verso una comprensione più profonda."]

🗂️ ARGOMENTI TRATTATI:
• Argomento 1 (max 5 parole): spiegazione
• Argomento 2 (max 5 parole): spiegazione
[8-12 argomenti]

👤 OSPITI:
Nome Cognome — Ruolo
[Se nessuno: "Nessun ospite presente"]

🏛️ PERSONE MENZIONATE:
• Nome Cognome — Ruolo/contesto

▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼
🌟 SPONSOR DEL VIDEO:
🌿 {SPONSOR_NOME}
{SPONSOR_DESCRIZIONE}
✨ Scopri di più: {SPONSOR_LINK}
💚 Usa il link per sostenere il canale!
▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼

👥 UNISCITI ALLA COMMUNITY:
💬 Telegram: {LINK_TELEGRAM}
👥 Facebook: {LINK_FACEBOOK}
📸 Instagram: {LINK_INSTAGRAM}
🌐 Sito: {LINK_SITO}

💝 SUPPORTA IL CANALE:
☕ Donazioni: {LINK_DONAZIONI}
👍 Lascia un like
💬 Commenta la tua opinione
🔄 Condividi con chi potrebbe apprezzare
🔔 Iscriviti e attiva la campanella

👤 CHI SIAMO:
{BIO_CANALE}

📩 COLLABORAZIONI & CONTATTI:
Business Mail: {EMAIL_BUSINESS}

●▬▬▬▬ INDICE ▬▬▬▬●
00:00 Introduzione
[15-20 timestamp REALI con titoli che contengono keyword]
[Ultimo timestamp vicino alla fine]

#{HASHTAG_CANALE} [+ 20-25 hashtag rilevanti]
```

---

## REGOLE TIMESTAMP

- Video > 60 min: 15-20 timestamp
- Video 30-60 min: 10-15 timestamp
- Video < 30 min: 5-8 timestamp
- Formato: MM:SS per <1h, H:MM:SS per ≥1h
- UN SOLO timestamp per conclusione

---

## CHECKLIST SEO

☐ La KEYWORD PRIMARIA è la PRIMA PAROLA del titolo?
☐ La KEYWORD PRIMARIA è la PRIMA PAROLA dell\'hook?
☐ La KEYWORD PRIMARIA è nella PRIMA FRASE della descrizione?
☐ Nomi propri presenti?
☐ Timestamp fino alla fine?
☐ 20-25 hashtag?
☐ NESSUN placeholder?

---

## OUTPUT

Genera SOLO la descrizione.
USA il formato ESATTO indicato.
NESSUN placeholder nell\'output finale.';
    }

    /**
     * Get config fields
     */
    public function get_config_fields() {
        return $this->config_fields;
    }

    /**
     * Salva la configurazione Golden Prompt per una licenza
     */
    public function save_license_config( $license_id, $config ) {
        global $wpdb;

        // Sanitize config
        $sanitized = [];
        foreach ( $this->config_fields as $key => $field ) {
            if ( isset( $config[ $key ] ) ) {
                switch ( $field['type'] ) {
                    case 'url':
                        $sanitized[ $key ] = esc_url_raw( $config[ $key ] );
                        break;
                    case 'email':
                        $sanitized[ $key ] = sanitize_email( $config[ $key ] );
                        break;
                    case 'textarea':
                        $sanitized[ $key ] = sanitize_textarea_field( $config[ $key ] );
                        break;
                    default:
                        $sanitized[ $key ] = sanitize_text_field( $config[ $key ] );
                }
            }
        }

        // Genera il Golden Prompt personalizzato
        $golden_prompt = $this->generate_personalized_prompt( $sanitized );

        // Salva configurazione
        $table = $wpdb->prefix . 'ipv_golden_prompts';
        
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE license_id = %d",
            $license_id
        ));

        $data = [
            'license_id' => $license_id,
            'config_json' => wp_json_encode( $sanitized ),
            'golden_prompt' => $golden_prompt,
            'is_active' => 1,
            'updated_at' => current_time( 'mysql' )
        ];

        if ( $existing ) {
            $wpdb->update( $table, $data, [ 'license_id' => $license_id ] );
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $table, $data );
        }

        // Log
        if ( class_exists( 'IPV_Vendor_Audit_Log' ) ) {
            IPV_Vendor_Audit_Log::log( 'golden_prompt_saved', [
                'license_id' => $license_id,
                'config_keys' => array_keys( $sanitized )
            ]);
        }

        return true;
    }

    /**
     * Genera il Golden Prompt personalizzato sostituendo i placeholder
     * e applicando TUTTI i 18 flags per rimuovere sezioni deselezionate
     */
    public function generate_personalized_prompt( $config ) {
        $prompt = $this->universal_template;

        // Mapping placeholder -> config key
        $replacements = [
            '{NOME_CANALE}' => $config['nome_canale'] ?? '',
            '{HANDLE_YOUTUBE}' => $config['handle_youtube'] ?? $config['handle'] ?? '',
            '{NICCHIA}' => $config['nicchia'] ?? '',
            '{LINK_TELEGRAM}' => $config['link_telegram'] ?? '',
            '{LINK_FACEBOOK}' => $config['link_facebook'] ?? '',
            '{LINK_INSTAGRAM}' => $config['link_instagram'] ?? '',
            '{LINK_SITO}' => $config['link_sito'] ?? '',
            '{LINK_DONAZIONI}' => $config['link_donazioni'] ?? '',
            '{SPONSOR_NOME}' => $config['sponsor_nome'] ?? '',
            '{SPONSOR_DESCRIZIONE}' => $config['sponsor_descrizione'] ?? '',
            '{SPONSOR_LINK}' => $config['sponsor_link'] ?? '',
            '{EMAIL_BUSINESS}' => $config['email_business'] ?? '',
            '{BIO_CANALE}' => $config['bio_canale'] ?? '',
            '{HASHTAG_CANALE}' => $config['hashtag_canale'] ?? '',
        ];

        foreach ( $replacements as $placeholder => $value ) {
            $prompt = str_replace( $placeholder, $value, $prompt );
        }

        // ═══════════════════════════════════════════════════════════════
        // APPLICA TUTTI I 18 FLAGS - Rimuove sezioni deselezionate
        // ═══════════════════════════════════════════════════════════════

        // 1. RISORSE MENZIONATE
        if ( isset( $config['show_risorse'] ) && ! $config['show_risorse'] ) {
            $prompt = preg_replace(
                '/●▬▬▬▬ RISORSE MENZIONATE ▬▬▬▬●.*?(?=●▬▬▬▬|▼▼▼|👥 UNISCITI|💝 SUPPORTA|👤 CHI SIAMO|📩 COLLABORAZIONI|$)/s',
                '',
                $prompt
            );
        }

        // 2. DESCRIZIONE (sezione principale)
        if ( isset( $config['show_descrizione'] ) && ! $config['show_descrizione'] ) {
            $prompt = preg_replace(
                '/●▬▬▬▬ DESCRIZIONE ▬▬▬▬●.*?(?=🗂️ ARGOMENTI|👤 OSPITI|🏛️ PERSONE|▼▼▼|👥 UNISCITI|$)/s',
                '●▬▬▬▬ DESCRIZIONE ▬▬▬▬●' . "\n\n",
                $prompt
            );
        }

        // 3. ARGOMENTI TRATTATI
        if ( isset( $config['show_argomenti'] ) && ! $config['show_argomenti'] ) {
            $prompt = preg_replace(
                '/🗂️ ARGOMENTI TRATTATI:.*?(?=👤 OSPITI|🏛️ PERSONE|▼▼▼|👥 UNISCITI|$)/s',
                '',
                $prompt
            );
        }

        // 4. OSPITI
        if ( isset( $config['show_ospiti'] ) && ! $config['show_ospiti'] ) {
            $prompt = preg_replace(
                '/👤 OSPITI:.*?(?=🏛️ PERSONE|▼▼▼|👥 UNISCITI|$)/s',
                '',
                $prompt
            );
        }

        // 5. PERSONE MENZIONATE
        if ( isset( $config['show_persone'] ) && ! $config['show_persone'] ) {
            $prompt = preg_replace(
                '/🏛️ PERSONE MENZIONATE:.*?(?=▼▼▼|👥 UNISCITI|$)/s',
                '',
                $prompt
            );
        }

        // 6. SPONSOR (supporta multipli)
        $sponsors = $config['sponsors'] ?? [];
        $has_sponsors = ! empty( $sponsors ) && is_array( $sponsors );

        // Retrocompatibilità: se ci sono i vecchi campi singoli, convertili
        if ( ! $has_sponsors && ! empty( $config['sponsor_nome'] ) ) {
            $sponsors = [[
                'nome' => $config['sponsor_nome'],
                'descrizione' => $config['sponsor_descrizione'] ?? '',
                'link' => $config['sponsor_link'] ?? ''
            ]];
            $has_sponsors = true;
        }

        if ( ( isset( $config['show_sponsor'] ) && ! $config['show_sponsor'] ) || ! $has_sponsors ) {
            // Rimuovi sezione sponsor se disabilitata o vuota
            $prompt = preg_replace(
                '/▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼\s*🌟 SPONSOR DEL VIDEO:.*?▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼/s',
                '',
                $prompt
            );
        } else {
            // Genera sezione sponsor multipli
            $sponsors_html = "▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼\n🌟 SPONSOR DEL VIDEO:\n\n";

            foreach ( $sponsors as $index => $sponsor ) {
                if ( empty( $sponsor['nome'] ) ) continue;

                $sponsors_html .= "🏆 " . $sponsor['nome'];
                if ( ! empty( $sponsor['descrizione'] ) ) {
                    $sponsors_html .= "\n" . $sponsor['descrizione'];
                }
                if ( ! empty( $sponsor['link'] ) ) {
                    $sponsors_html .= "\n🔗 " . $sponsor['link'];
                }
                $sponsors_html .= "\n\n";
            }

            $sponsors_html .= "▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼";

            // Sostituisci la sezione sponsor nel template
            $prompt = preg_replace(
                '/▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼\s*🌟 SPONSOR DEL VIDEO:.*?▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼/s',
                $sponsors_html,
                $prompt
            );
        }

        // 7. CHI SIAMO
        if ( isset( $config['show_chi_siamo'] ) && ! $config['show_chi_siamo'] ) {
            $prompt = preg_replace(
                '/👤 CHI SIAMO:.*?(?=📩 COLLABORAZIONI|●▬▬▬▬ INDICE|#|$)/s',
                '',
                $prompt
            );
        }

        // 8. CONTATTI
        if ( isset( $config['show_contatti'] ) && ! $config['show_contatti'] ) {
            $prompt = preg_replace(
                '/📩 COLLABORAZIONI & CONTATTI:.*?(?=●▬▬▬▬ INDICE|#|$)/s',
                '',
                $prompt
            );
        }

        // 9. INDICE TIMESTAMP
        if ( isset( $config['show_indice'] ) && ! $config['show_indice'] ) {
            $prompt = preg_replace(
                '/●▬▬▬▬ INDICE ▬▬▬▬●.*?(?=#|\z)/s',
                '',
                $prompt
            );
        }

        // 10. HASHTAG
        if ( isset( $config['show_hashtag'] ) && ! $config['show_hashtag'] ) {
            $prompt = preg_replace(
                '/#{HASHTAG_CANALE}.*$/s',
                '',
                $prompt
            );
            // Rimuovi anche la riga hashtag già compilata
            $prompt = preg_replace(
                '/\n#[A-Za-z0-9_]+ \[.*?\]$/s',
                '',
                $prompt
            );
        }

        // 11. UNISCITI COMMUNITY
        if ( isset( $config['show_community'] ) && ! $config['show_community'] ) {
            $prompt = preg_replace(
                '/👥 UNISCITI ALLA COMMUNITY:.*?(?=💝 SUPPORTA|👤 CHI SIAMO|📩 COLLABORAZIONI|●▬▬▬▬ INDICE|$)/s',
                '',
                $prompt
            );
        }

        // 12. SUPPORTA CANALE
        if ( isset( $config['show_supporta'] ) && ! $config['show_supporta'] ) {
            $prompt = preg_replace(
                '/💝 SUPPORTA IL CANALE:.*?(?=👤 CHI SIAMO|📩 COLLABORAZIONI|●▬▬▬▬ INDICE|$)/s',
                '',
                $prompt
            );
        }

        // ═══════════════════════════════════════════════════════════════
        // FLAGS LINK SOCIAL INDIVIDUALI (6 flags)
        // ═══════════════════════════════════════════════════════════════

        // 13. Link Telegram
        if ( isset( $config['show_link_telegram'] ) && ! $config['show_link_telegram'] ) {
            $prompt = preg_replace( '/💬 Telegram:.*?\n/s', '', $prompt );
            $prompt = preg_replace( '/📱 Telegram:.*?\n/s', '', $prompt );
        }

        // 14. Link Facebook
        if ( isset( $config['show_link_facebook'] ) && ! $config['show_link_facebook'] ) {
            $prompt = preg_replace( '/👥 Facebook:.*?\n/s', '', $prompt );
        }

        // 15. Link Instagram
        if ( isset( $config['show_link_instagram'] ) && ! $config['show_link_instagram'] ) {
            $prompt = preg_replace( '/📸 Instagram:.*?\n/s', '', $prompt );
        }

        // 16. Link Sito
        if ( isset( $config['show_link_sito'] ) && ! $config['show_link_sito'] ) {
            $prompt = preg_replace( '/🌐 Sito:.*?\n/s', '', $prompt );
        }

        // 17. Link Donazioni
        if ( isset( $config['show_link_donazioni'] ) && ! $config['show_link_donazioni'] ) {
            $prompt = preg_replace( '/☕ Donazioni:.*?\n/s', '', $prompt );
        }

        // 18. Link YouTube (nella sezione community)
        if ( isset( $config['show_link_youtube'] ) && ! $config['show_link_youtube'] ) {
            $prompt = preg_replace( '/📺 YouTube:.*?\n/s', '', $prompt );
        }

        // ═══════════════════════════════════════════════════════════════
        // PULIZIA FINALE
        // ═══════════════════════════════════════════════════════════════

        // Rimuovi righe vuote multiple
        $prompt = preg_replace( '/\n{3,}/', "\n\n", $prompt );

        // Rimuovi spazi extra
        $prompt = trim( $prompt );

        return $prompt;
    }

    /**
     * Ottieni la configurazione per una licenza
     */
    public function get_license_config( $license_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ipv_golden_prompts';

        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE license_id = %d",
            $license_id
        ));

        if ( ! $row ) {
            return null;
        }

        return [
            'config' => json_decode( $row->config_json, true ),
            'golden_prompt' => $row->golden_prompt,
            'is_active' => (bool) $row->is_active,
            'updated_at' => $row->updated_at
        ];
    }

    /**
     * Ottieni il Golden Prompt per una licenza (per API)
     */
    public function get_golden_prompt_for_license( $license_key ) {
        global $wpdb;

        // Get license ID from key
        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ipv_licenses WHERE license_key = %s AND status = 'active'",
            $license_key
        ));

        if ( ! $license ) {
            return new WP_Error( 'invalid_license', 'Licenza non valida' );
        }

        $config = $this->get_license_config( $license->id );

        if ( ! $config || ! $config['is_active'] ) {
            return new WP_Error( 'no_golden_prompt', 'Golden Prompt non configurato per questa licenza' );
        }

        return $config['golden_prompt'];
    }

    /**
     * Attiva/Disattiva Golden Prompt per una licenza
     */
    public function toggle_license_activation( $license_id, $active = true ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ipv_golden_prompts';

        // Check if record exists
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE license_id = %d",
            $license_id
        ));

        if ( $exists ) {
            // Update existing record
            return $wpdb->update(
                $table,
                [ 
                    'is_active' => $active ? 1 : 0,
                    'updated_at' => current_time( 'mysql' )
                ],
                [ 'license_id' => $license_id ],
                [ '%d', '%s' ],
                [ '%d' ]
            );
        } else {
            // Create new record (attivato ma vuoto - cliente configurerà)
            return $wpdb->insert(
                $table,
                [
                    'license_id' => $license_id,
                    'config_json' => '{}',
                    'golden_prompt' => '',
                    'is_active' => $active ? 1 : 0,
                    'updated_at' => current_time( 'mysql' )
                ],
                [ '%d', '%s', '%s', '%d', '%s' ]
            );
        }
    }

    /**
     * Salva il Golden Prompt personalizzato direttamente (da textarea)
     */
    public function save_custom_prompt( $license_id, $custom_prompt ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ipv_golden_prompts';

        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE license_id = %d",
            $license_id
        ));

        $data = [
            'license_id' => $license_id,
            'golden_prompt' => wp_kses_post( $custom_prompt ),
            'is_active' => 1,
            'updated_at' => current_time( 'mysql' )
        ];

        if ( $existing ) {
            $wpdb->update( $table, $data, [ 'license_id' => $license_id ] );
        } else {
            $data['config_json'] = '{}';
            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $table, $data );
        }

        return true;
    }

    /**
     * Salva il template universale
     */
    public function save_universal_template( $template ) {
        update_option( 'ipv_golden_prompt_template', wp_kses_post( $template ) );
        $this->universal_template = $template;
        return true;
    }

    /**
     * Get universal template
     */
    public function get_universal_template() {
        return $this->universal_template;
    }

    /**
     * Crea la tabella database
     */
    public static function create_table() {
        global $wpdb;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $table_name = $wpdb->prefix . 'ipv_golden_prompts';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            license_id BIGINT UNSIGNED NOT NULL,
            config_json LONGTEXT NULL,
            golden_prompt LONGTEXT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY idx_license (license_id),
            INDEX idx_active (is_active)
        ) {$charset_collate};";

        dbDelta( $sql );
    }
}
