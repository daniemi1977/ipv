<?php
/**
 * PATCH COMPLETA: Fix Descrizione Prodotti WooCommerce Corrotta
 *
 * File da modificare: /includes/class-plans-manager.php (server)
 *
 * PROBLEMA IDENTIFICATO:
 * - Le descrizioni generate con emoji vengono corrotte durante il salvataggio
 * - Causa: Double-encoding + filtri WordPress + possibile charset database
 *
 * SOLUZIONE IMPLEMENTATA:
 * - Conversione emoji in HTML entities (compatibilità UTF8 standard)
 * - Rimozione temporanea filtri WordPress durante salvataggio
 * - Salvataggio diretto via update_post_meta come fallback
 *
 * VERSIONE: 1.0
 * DATA: 2025-12-11
 */

// ============================================================================
// STEP 1: AGGIUNGI METODO HELPER PER EMOJI → HTML ENTITIES
// Inserire dopo il metodo get_period_label() (circa riga 1040)
// ============================================================================

/**
 * Converte emoji Unicode in HTML entities
 *
 * Necessario per compatibilità con database che non supportano UTF8MB4
 * o quando i filtri WordPress corrompono le emoji durante il salvataggio
 *
 * @param string $string Stringa con emoji Unicode
 * @return string Stringa con HTML entities
 */
private function emoji_to_html_entities( $string ) {
    // Mappa emoji comuni → HTML entities
    $emoji_map = [
        // Video & Media
        '🎬' => '&#127916;', // Film clapper
        '🎥' => '&#127909;', // Movie camera
        '📹' => '&#128249;', // Video camera
        '🎞️' => '&#127902;', // Film frames

        // Business & Charts
        '📊' => '&#128202;', // Bar chart
        '📈' => '&#128200;', // Chart increasing
        '📉' => '&#128201;', // Chart decreasing
        '💼' => '&#128188;', // Briefcase

        // Tech & Tools
        '🚀' => '&#128640;', // Rocket
        '⚡' => '&#9889;',   // Lightning
        '⚙️' => '&#9881;',   // Gear
        '🔧' => '&#128295;', // Wrench
        '🛠️' => '&#128736;', // Hammer and wrench

        // Writing & Communication
        '📝' => '&#128221;', // Memo
        '✍️' => '&#9997;',   // Writing hand
        '📧' => '&#128231;', // Email
        '💬' => '&#128172;', // Speech balloon

        // AI & Robot
        '🤖' => '&#129302;', // Robot
        '🧠' => '&#129504;', // Brain
        '💡' => '&#128161;', // Light bulb

        // Media & Images
        '🖼️' => '&#128444;', // Framed picture
        '🎨' => '&#127912;', // Artist palette
        '📷' => '&#128247;', // Camera
        '🖌️' => '&#128396;', // Paintbrush

        // Download & Upload
        '📥' => '&#128229;', // Inbox tray
        '📤' => '&#128228;', // Outbox tray
        '⬇️' => '&#11015;',  // Down arrow
        '⬆️' => '&#11014;',  // Up arrow

        // Success & Quality
        '✅' => '&#9989;',   // Check mark
        '✨' => '&#10024;',  // Sparkles
        '⭐' => '&#11088;',  // Star
        '🌟' => '&#127775;', // Glowing star
        '💎' => '&#128142;', // Gem

        // Security & Access
        '🔑' => '&#128273;', // Key
        '🔒' => '&#128274;', // Lock
        '🔓' => '&#128275;', // Unlock
        '🛡️' => '&#128737;', // Shield

        // Web & Global
        '🌐' => '&#127760;', // Globe
        '🌍' => '&#127757;', // Earth Europe
        '🌎' => '&#127758;', // Earth Americas

        // Misc
        '👤' => '&#128100;', // Bust in silhouette
        '👥' => '&#128101;', // Busts in silhouette
        '📋' => '&#128203;', // Clipboard
        '📁' => '&#128193;', // Folder
    ];

    // Sostituisci tutte le emoji con HTML entities
    $converted = str_replace( array_keys( $emoji_map ), array_values( $emoji_map ), $string );

    // Log per debug (solo in sviluppo)
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        $emoji_found = array_intersect(
            mb_str_split( $string ),
            array_keys( $emoji_map )
        );
        if ( ! empty( $emoji_found ) ) {
            error_log( '[IPV Plans] Emoji convertite in HTML entities: ' . implode( ', ', $emoji_found ) );
        }
    }

    return $converted;
}


// ============================================================================
// STEP 2: METODO GENERATE_PRODUCT_DESCRIPTION AGGIORNATO
// Sostituire il metodo esistente (righe 1003-1035 circa)
// ============================================================================

/**
 * Generate product description HTML
 *
 * v1.1 - FIX: Converte emoji in HTML entities per evitare corruzione
 *
 * @param array $plan Piano con configurazione (name, description, features, etc.)
 * @return string HTML description (emoji come HTML entities)
 */
private function generate_product_description( $plan ) {
    $features = $this->get_available_features();

    // Costruisci HTML con emoji Unicode (leggibile)
    $html = '';

    // ===== HEADER =====
    $html .= '<h3>';
    $html .= '🎬 IPV Production System Pro - Piano ' . esc_html( $plan['name'] );
    $html .= '</h3>' . "\n\n";

    // ===== DESCRIZIONE =====
    $description = $plan['description'] ?? 'Il sistema professionale per automatizzare la produzione video.';
    $html .= '<p>' . esc_html( $description ) . '</p>' . "\n\n";

    // ===== COSA INCLUDE =====
    $html .= '<h4>📊 Cosa Include:</h4>' . "\n";
    $html .= '<ul>' . "\n";

    // Crediti mensili/annuali
    $html .= '<li><strong>' . intval( $plan['credits'] ) . ' video</strong> importabili al ';
    $html .= esc_html( $this->get_period_label( $plan['credits_period'] ) ) . '</li>' . "\n";

    // Attivazioni
    $html .= '<li><strong>' . intval( $plan['activations'] ) . ' sito/i</strong> WordPress attivabili</li>' . "\n";

    // Features con icone
    foreach ( $features as $key => $feature ) {
        if ( ! empty( $plan['features'][ $key ] ) ) {
            $html .= '<li>' . $feature['icon'] . ' <strong>' . esc_html( $feature['name'] ) . '</strong></li>' . "\n";
        }
    }

    $html .= '</ul>' . "\n\n";

    // ===== FUNZIONALITÀ PRINCIPALI =====
    $html .= '<h4>🚀 Funzionalità Principali:</h4>' . "\n";
    $html .= '<ul>' . "\n";
    $html .= '<li>📝 <strong>Trascrizione automatica</strong> video YouTube</li>' . "\n";
    $html .= '<li>🤖 <strong>Generazione descrizioni</strong> con AI (GPT-4)</li>' . "\n";
    $html .= '<li>🖼️ <strong>Download automatico</strong> thumbnail HD</li>' . "\n";
    $html .= '<li>📥 <strong>Import</strong> singolo e massivo</li>' . "\n";
    $html .= '<li>🎨 <strong>Video Wall</strong> personalizzabile con filtri AJAX</li>' . "\n";
    $html .= '<li>📊 <strong>Dashboard analytics</strong> completa</li>' . "\n";
    $html .= '<li>⚙️ <strong>Golden Prompt</strong> personalizzabile</li>' . "\n";
    $html .= '<li>🔑 <strong>Sistema licenze</strong> integrato</li>' . "\n";
    $html .= '</ul>' . "\n\n";

    // ===== SUPPORTO & GARANZIE =====
    $html .= '<h4>✨ Supporto & Garanzie:</h4>' . "\n";
    $html .= '<ul>' . "\n";
    $html .= '<li>📧 <strong>Supporto email</strong> prioritario</li>' . "\n";
    $html .= '<li>🔄 <strong>Aggiornamenti automatici</strong> inclusi</li>' . "\n";
    $html .= '<li>🛡️ <strong>Garanzia 30 giorni</strong> soddisfatti o rimborsati</li>' . "\n";
    $html .= '<li>🌐 <strong>Server SaaS</strong> dedicato per API</li>' . "\n";
    $html .= '</ul>';

    // ✅ FIX CRITICO: Converte emoji in HTML entities
    // Questo previene la corruzione durante il salvataggio
    $html_with_entities = $this->emoji_to_html_entities( $html );

    // Log per debug
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[IPV Plans] Description generated - Length: ' . strlen( $html_with_entities ) );
        error_log( '[IPV Plans] First 200 chars: ' . substr( $html_with_entities, 0, 200 ) );
    }

    return $html_with_entities;
}


// ============================================================================
// STEP 3: FIX METODO CREATE_OR_UPDATE_PRODUCT
// Modificare il salvataggio della descrizione (righe 915-920 circa)
// ============================================================================

/**
 * Create or update WooCommerce product
 *
 * v1.1 - FIX: Rimuove filtri WordPress temporaneamente per evitare corruzione
 */
private function create_or_update_product( $plan, $product_id = null ) {
    try {
        // Crea o recupera prodotto
        if ( $product_id ) {
            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                throw new Exception( 'Product not found: ' . $product_id );
            }
        } else {
            $product = new WC_Product_Simple();
        }

        // Set basic data
        $product->set_name( 'IPV Production System Pro - ' . $plan['name'] );
        $product->set_regular_price( $plan['price'] );
        $product->set_status( 'publish' );
        $product->set_catalog_visibility( 'visible' );
        $product->set_sold_individually( true );

        // ✅ FIX CRITICO: Genera descrizione con emoji → HTML entities
        $description = $this->generate_product_description( $plan );

        // ✅ FIX: Rimuovi filtri WordPress che potrebbero corrompere il contenuto
        $removed_filters = [];

        // Salva riferimenti ai filtri da rimuovere temporaneamente
        $filters_to_remove = [
            'content_save_pre',
            'wp_insert_post_data',
            'content_filtered_save_pre',
        ];

        foreach ( $filters_to_remove as $filter_name ) {
            if ( has_filter( $filter_name ) ) {
                $removed_filters[ $filter_name ] = $GLOBALS['wp_filter'][ $filter_name ] ?? null;
                remove_all_filters( $filter_name );
            }
        }

        // Log per debug
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[IPV Plans] Removed filters: ' . implode( ', ', array_keys( $removed_filters ) ) );
        }

        // Salva descrizione
        $product->set_description( $description );

        // Set meta data
        $product->update_meta_data( '_ipv_plan_id', $plan['id'] );
        $product->update_meta_data( '_ipv_credits', $plan['credits'] );
        $product->update_meta_data( '_ipv_credits_period', $plan['credits_period'] );
        $product->update_meta_data( '_ipv_activations', $plan['activations'] );
        $product->update_meta_data( '_ipv_features', wp_json_encode( $plan['features'] ) );

        // Salva prodotto
        $saved_product_id = $product->save();

        // ✅ FIX AGGIUNTIVO: Verifica che la descrizione sia stata salvata correttamente
        // Se corrotta, salva direttamente via SQL
        $saved_product = wc_get_product( $saved_product_id );
        $saved_description = $saved_product->get_description();

        // Verifica se la descrizione è stata corrotta
        $is_corrupted = (
            empty( $saved_description ) ||
            strlen( $saved_description ) < 100 ||
            strpos( $saved_description, 'IPV Production System Pro' ) === false
        );

        if ( $is_corrupted ) {
            error_log( '[IPV Plans] WARNING: Description corrupted, saving directly via SQL' );

            // Salva direttamente via SQL come fallback
            global $wpdb;
            $updated = $wpdb->update(
                $wpdb->posts,
                [ 'post_content' => $description ],
                [ 'ID' => $saved_product_id ],
                [ '%s' ],
                [ '%d' ]
            );

            if ( $updated !== false ) {
                clean_post_cache( $saved_product_id );
                wp_cache_delete( $saved_product_id, 'posts' );
                error_log( '[IPV Plans] Description saved via SQL successfully' );
            } else {
                error_log( '[IPV Plans] ERROR: Failed to save description via SQL' );
            }
        } else {
            error_log( '[IPV Plans] Description saved correctly via WooCommerce API' );
        }

        // Ripristina filtri rimossi
        foreach ( $removed_filters as $filter_name => $filter_callbacks ) {
            if ( $filter_callbacks !== null ) {
                $GLOBALS['wp_filter'][ $filter_name ] = $filter_callbacks;
            }
        }

        // Assign to category
        if ( ! empty( $plan['category_id'] ) ) {
            wp_set_object_terms( $saved_product_id, [ (int) $plan['category_id'] ], 'product_cat' );
        }

        return $saved_product_id;

    } catch ( Exception $e ) {
        error_log( '[IPV Plans] Error creating/updating product: ' . $e->getMessage() );
        return false;
    }
}


// ============================================================================
// STEP 4: METODO DI SYNC AGGIORNATO
// Modificare sync_product() (righe 970-980 circa)
// ============================================================================

/**
 * Sync single product
 *
 * v1.1 - FIX: Usa metodo aggiornato con gestione emoji corretta
 */
private function sync_product( $plan ) {
    $product_id = $this->get_product_id_by_plan( $plan['id'] );

    if ( $product_id ) {
        // Update existing
        $result = $this->create_or_update_product( $plan, $product_id );
        error_log( '[IPV Plans] Product UPDATED: ' . $plan['name'] . ' (ID: ' . $result . ')' );
    } else {
        // Create new
        $result = $this->create_or_update_product( $plan );
        error_log( '[IPV Plans] Product CREATED: ' . $plan['name'] . ' (ID: ' . $result . ')' );
    }

    return $result;
}


// ============================================================================
// STEP 5: HOOK DI DEBUG (OPZIONALE)
// Aggiungere all'inizio del file o nel metodo __construct()
// Utile per monitorare cosa succede durante il salvataggio
// ============================================================================

/**
 * Debug hook per monitorare salvataggio descrizioni
 * Aggiungere nel metodo __construct() o all'init del plugin
 */
public function add_debug_hooks() {
    if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
        return; // Solo in modalità debug
    }

    // Hook pre-save
    add_filter( 'content_save_pre', function( $content ) {
        if ( strpos( $content, 'IPV Production System Pro' ) !== false ) {
            error_log( '=== IPV DESCRIPTION PRE-SAVE ===' );
            error_log( 'Length: ' . strlen( $content ) );
            error_log( 'Has HTML entities: ' . ( strpos( $content, '&#' ) !== false ? 'YES' : 'NO' ) );
            error_log( 'Has emoji: ' . ( preg_match( '/[\x{1F300}-\x{1F9FF}]/u', $content ) ? 'YES (PROBLEM!)' : 'NO' ) );
            error_log( 'First 300 chars: ' . substr( $content, 0, 300 ) );
        }
        return $content;
    }, 1 );

    // Hook insert post data
    add_filter( 'wp_insert_post_data', function( $data, $postarr ) {
        if ( isset( $data['post_content'] ) && strpos( $data['post_content'], 'IPV Production System Pro' ) !== false ) {
            error_log( '=== IPV DESCRIPTION INSERT POST DATA ===' );
            error_log( 'Length: ' . strlen( $data['post_content'] ) );
            error_log( 'First 300 chars: ' . substr( $data['post_content'], 0, 300 ) );
        }
        return $data;
    }, 1, 2 );

    // Hook post-save
    add_action( 'save_post_product', function( $post_id, $post, $update ) {
        $product = wc_get_product( $post_id );
        if ( $product ) {
            $description = $product->get_description();
            if ( strpos( $description, 'IPV Production System Pro' ) !== false ) {
                error_log( '=== IPV DESCRIPTION POST-SAVE ===' );
                error_log( 'Product ID: ' . $post_id );
                error_log( 'Length: ' . strlen( $description ) );
                error_log( 'Is corrupted: ' . ( strlen( $description ) < 100 ? 'YES (TOO SHORT!)' : 'NO' ) );
            }
        }
    }, 10, 3 );
}


// ============================================================================
// STEP 6: VERIFICA CHARSET DATABASE (ESEGUIRE UNA TANTUM)
// Query SQL da eseguire per verificare se il database supporta emoji
// ============================================================================

/*
-- Verifica charset della tabella wp_posts
SHOW CREATE TABLE wp_posts;

-- Se il charset è utf8 (non utf8mb4), converti con:
ALTER TABLE wp_posts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Verifica charset della colonna post_content
SELECT
    TABLE_NAME,
    COLUMN_NAME,
    CHARACTER_SET_NAME,
    COLLATION_NAME
FROM
    INFORMATION_SCHEMA.COLUMNS
WHERE
    TABLE_SCHEMA = 'nome_database'
    AND TABLE_NAME = 'wp_posts'
    AND COLUMN_NAME = 'post_content';

-- Se necessario, modifica solo la colonna:
ALTER TABLE wp_posts
    MODIFY post_content LONGTEXT
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
*/


// ============================================================================
// RIEPILOGO MODIFICHE
// ============================================================================

/*
MODIFICHE DA APPLICARE:

1. ✅ Aggiungere metodo emoji_to_html_entities() dopo get_period_label()
   - Converte tutte le emoji in HTML entities
   - Compatibile con database UTF8 standard

2. ✅ Sostituire metodo generate_product_description()
   - Usa emoji_to_html_entities() prima di ritornare
   - Descrizioni più ricche e strutturate

3. ✅ Aggiornare metodo create_or_update_product()
   - Rimuove filtri WordPress temporaneamente
   - Verifica se descrizione salvata correttamente
   - Fallback su SQL diretto se corrotta

4. ✅ Aggiornare metodo sync_product()
   - Usa nuova versione create_or_update_product()

5. ⚠️ OPZIONALE: Aggiungere debug hooks
   - Solo per sviluppo/troubleshooting
   - Monitora cosa succede durante il salvataggio

6. ⚠️ OPZIONALE: Verificare charset database
   - Se possibile, convertire a UTF8MB4
   - Ma non necessario con HTML entities

TESTING:
1. Applicare modifiche
2. Eseguire sync dei piani: /wp-admin/admin.php?page=ipv-plans&action=sync
3. Verificare descrizioni prodotti in /wp-admin/post.php?post=XXX&action=edit
4. Se ancora corrotto, controllare log debug (Step 5)

ROLLBACK:
Se qualcosa va storto, i metodi originali sono ancora presenti nel file.
Basta commentare i nuovi e decommentare i vecchi.
*/
