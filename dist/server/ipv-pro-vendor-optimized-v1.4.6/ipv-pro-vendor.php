<?php
/**
 * Plugin Name: IPV Pro Vendor System
 * Plugin URI: https://ipv-production-system.com
 * Description: Sistema completo per vendere IPV Pro Plugin via WooCommerce con API Gateway integrato
 * Version: 1.4.6
 * Author: IPV Team
 * Author URI: https://ipv-production-system.com
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * WC tested up to: 9.5
 * Text Domain: ipv-pro-vendor
 * Domain Path: /languages
 * License: GPL v2 or later
 *
 * CHANGELOG v1.4.6 (2025-12-16):
 * - FEATURE: Golden Prompt come Digital Asset Sicuro
 *   - Golden prompt convertito da piano subscription a digital asset
 *   - Modificato a: €59 una tantum, 0 crediti, 1 solo sito
 *   - Nuovo tipo prodotto: "digital_asset" con download remoto sicuro
 *   - Sistema anti-pirateria: 1 solo download consentito per licenza
 *   - Download legato alla licenza attivata (non copiabile)
 * - FEATURE: Endpoint API Download Sicuro
 *   - Nuovo endpoint: POST /wp-json/ipv-vendor/v1/license/download-asset
 *   - Genera token sicuro valido 5 minuti
 *   - Verifica licenza attiva e variant_slug corretto
 *   - Tracking download count in ipv_license_meta
 *   - Log timestamp download request
 *   - Limite download configurabile per prodotto (_ipv_download_limit)
 * - METADATA: Nuovi campi prodotto WooCommerce
 *   - _ipv_product_type: 'digital_asset'
 *   - _ipv_download_limit: numero massimo download (default: 1)
 *   - _ipv_remote_download: true (download gestito dal server)
 * - METADATA: Nuovi campi licenza
 *   - _asset_download_count: numero download effettuati
 *   - _asset_download_requested_at: timestamp ultima richiesta
 *
 * CHANGELOG v1.4.5 (2025-12-16):
 * - FEATURE: Sistema Billing Ibrido Mensile/Annuale + Cambio Piano
 *   - Piani "once" (Trial, Extra Credits): creano 1 solo prodotto
 *   - Piani "month" (Subscription): creano 2 prodotti (Mensile + Annuale)
 *   - Annuale: prezzo × 10 mesi (sconto 16.67%), crediti × 12
 *   - Metadata: _ipv_billing_type (once/monthly/yearly), _ipv_credits_period
 *   - Descrizioni prodotti con calcolo risparmio per piani annuali
 * - FEATURE: 3 Nuovi Piani SaaS
 *   - Executive: €499/mese, 2000 crediti/mese, 50 attivazioni
 *   - Golden prompt: €59/mese, 150 crediti/mese, 5 attivazioni (MODIFICATO in v1.4.6)
 *   - IPV Pro - 100: €49 una tantum, 100 crediti extra che non scadono
 *   - Rinominato "Crediti Extra" in "IPV Pro - 10" per coerenza
 * - FEATURE: Sistema Cambio Piano con Anti-Frode (da v1.4.4)
 *   - Pulsante "🔄 Cambia Piano" in tabella licenze
 *   - Validazione automatica con 4 regole
 *   - UI con card colorate (blu=permesso, rosso=bloccato)
 *   - Creazione ordine WooCommerce per tracciabilità
 * - MIGLIORAMENTO: Setup Wizard Step 4 mostra tabella piani con entrambe le varianti
 * - MIGLIORAMENTO: Compatibilità completa con FIXED10 (Trial una tantum, Extra Credits)
 *
 * CHANGELOG v1.4.2-FIXED10 (2025-12-15):
 * - FIX CRITICO: Logica piani Trial e Crediti Extra completamente rivista
 *   - Trial: 10 crediti di BENVENUTO (una tantum, NON si rinnovano, NON scade mai)
 *   - Starter/Professional/Business: crediti MENSILI (si rinnovano ogni mese)
 *   - Extra Credits: pacchetto 10 crediti a 5,00 EUR (0,50 EUR/credito), NON scadono
 * - Aggiunto piano 'extra_credits' nei default plans
 * - Descrizioni prodotti completamente riscritte per ogni tipo:
 *   - Trial: spiega che sono crediti di benvenuto non rinnovabili
 *   - Extra Credits: spiega prezzo per credito e che non scadono
 *   - Subscription: spiega che i crediti si rinnovano mensilmente
 * - Etichetta WooCommerce cambiata da "Crediti Mensili" a "Crediti Totali"
 * - Product type differenziato: 'trial', 'extra_credits', 'subscription'
 * - credits_period: 'once' per Trial e Extra Credits, 'month' per subscription
 *
 * CHANGELOG v1.4.2-FIXED9 (2025-12-15):
 * - FIX CRITICO: "Sorry, you are not allowed to access this page" nel wizard
 *   - Problema: Errore di permessi quando si accede alla pagina Setup Wizard
 *   - WordPress non riconosceva la pagina come valida perché registrata con parent null
 *   - Causa: add_submenu_page() usava null come parent, rendendo la pagina orfana
 *   - Fix: Registrata pagina wizard con parent 'ipv-vendor-dashboard'
 *   - Ora la pagina del wizard è correttamente collegata al menu principale
 *   - Corretti tutti i link che puntavano a 'page=ipv-vendor' → 'page=ipv-vendor-dashboard'
 *   - Link "Vai alla Dashboard" nel wizard ora funziona correttamente
 *   - Link "IPV Pro Vendor Dashboard" nei prossimi passi corretto
 *   - Migliorato redirect legacy con priority 1 per eseguire prima di altri hook
 *   - ERRORE PERMESSI RISOLTO - Wizard ora accessibile al 100%
 *
 * CHANGELOG v1.4.2-FIXED8 (2025-12-15):
 * - FIX CRITICO: "Sorry, you are not allowed to access this page" al Step 4
 *   - Problema: Errore di permessi quando si clicca "Crea Prodotti Automaticamente"
 *   - WordPress non trovava l'azione admin_post registrata
 *   - Causa: Setup Wizard caricato solo dentro blocco is_admin()
 *   - Admin-post.php viene eseguito prima che is_admin() sia valutato
 *   - Fix: Spostato caricamento Setup Wizard fuori da is_admin()
 *   - Ora Setup Wizard si carica sempre in plugins_loaded
 *   - Hook admin_post_ipv_vendor_create_products sempre disponibile
 *   - Migliorati messaggi di errore per debug (nonce, permessi, WooCommerce)
 *   - ERRORE PERMESSI RISOLTO - Creazione prodotti ora funziona
 *
 * CHANGELOG v1.4.2-FIXED7 (2025-12-15):
 * - FIX CRITICO: Metodo render_step_complete() mancante in Setup Wizard
 *   - Problema: "Call to undefined method IPV_Pro_Vendor_Setup_Wizard::render_step_complete()"
 *   - Fatal error quando si completa il wizard allo Step 5
 *   - Causa: Metodo completamente mancante dalla classe
 *   - Fix: Aggiunto metodo render_step_complete() completo
 *   - Step 5 ora mostra:
 *     * Messaggio di successo configurazione
 *     * Riepilogo prodotti creati/saltati
 *     * Lista prossimi passi (Piani SaaS, Prodotti, Gateway, API Test, Dashboard)
 *     * Link rapidi a tutte le sezioni principali
 *     * Nota importante su aggiornamento prodotti dopo modifica piani
 *   - ERRORE FATALE RISOLTO - Wizard completabile al 100%
 *
 * CHANGELOG v1.4.2-FIXED6 (2025-12-15):
 * - FIX CRITICO: Parse error in class-setup-wizard.php
 *   - Problema: "Unmatched '}' in class-setup-wizard.php on line 530"
 *   - Fatal error PHP parse impediva caricamento plugin
 *   - Causa: Metodo save_settings() aveva signature mancante e brace orfana
 *   - Codice POST processing per API keys era orfano (righe 517-530)
 *   - Fix: Ricreato metodo save_settings() completo
 *   - Aggiunto handling per youtube_api_key (era mancante)
 *   - Metodo ora gestisce correttamente tutti i campi Step 2:
 *     * youtube_api_key
 *     * openai_api_key
 *     * supadata_key
 *     * supadata_secret
 *   - Rimossa brace orfana alla riga 516
 *   - PARSE ERROR RISOLTO - Plugin ora si carica correttamente
 *
 * CHANGELOG v1.4.2-FIXED5 (2025-12-15):
 * - REIMPLEMENTAZIONE: Creazione prodotti basata sui Piani SaaS
 *   - Problema: Prodotti creati con sistema mensile/annuale hardcoded
 *   - Sistema precedente non leggeva i piani dal database
 *   - Prezzi e configurazioni erano duplicate nel codice
 *   - Fix: Riscritta completamente funzione create_products()
 *   - Ora legge i piani da Plans Manager (option ipv_saas_plans)
 *   - Prodotti creati dinamicamente basati sui piani attivi
 *   - Nome prodotto: "IPV Pro - [Nome Piano]" (es. "IPV Pro - Trial")
 *   - Rimosso sistema billing_type (mensile/annuale) dallo step 4 wizard
 *   - Wizard mostra tabella piani dal database in tempo reale
 *   - Descrizioni plain text con crediti, attivazioni e features
 *   - Metadata: _ipv_plan_slug, _ipv_variant_slug, _ipv_credits_total, _ipv_activation_limit
 *   - Link a "Gestisci Piani SaaS" nello step 4
 *   - Prodotti ora allineati ai piani configurati in IPV Pro Vendor → Piani SaaS
 *
 * CHANGELOG v1.4.2-FIXED4 (2025-12-15):
 * - FIX CRITICO: Fatal error nei campi prodotto WooCommerce
 *   - Problema: "Call to undefined function woocommerce_wp_number()" in class-woocommerce-integration.php:267
 *   - Causa: La funzione woocommerce_wp_number() non esiste in WooCommerce
 *   - Fatal error quando si modifica un prodotto nell'admin WooCommerce
 *   - Fix: Sostituito woocommerce_wp_number() con woocommerce_wp_text_input() con type='number'
 *   - Campi fixati: Crediti Mensili (_ipv_credits_total) e Limite Attivazioni (_ipv_activation_limit)
 *   - Rimosso controllo function_exists per woocommerce_wp_number (non esiste)
 *   - Aggiunto controllo per woocommerce_wp_text_input invece
 *   - ERRORE FATALE RISOLTO - Ora i prodotti si possono modificare senza crash
 *
 * CHANGELOG v1.4.2-FIXED3 (2025-12-15):
 * - TOOL: Script di migrazione per aggiornare prodotti esistenti
 *   - Nuovo menu: "🔄 Migra Descrizioni" sotto IPV Pro Vendor
 *   - Trova automaticamente tutti i prodotti IPV (subscription + extra_credits)
 *   - Identifica variant (trial, starter, professional, business) da metadata
 *   - Rileva automaticamente se mensile o annuale dal nome prodotto
 *   - Applica le nuove descrizioni plain text con ✓ caratteri
 *   - Aggiorna sia short_description che description
 *   - Rimuove tutto l'HTML con wp_strip_all_tags()
 *   - Gestione errori completa con feedback dettagliato
 *   - Conferma prima dell'esecuzione + warning backup
 *   - File può essere eliminato dopo l'uso per sicurezza
 *   - Risolve il problema del testo bianco sui prodotti ESISTENTI
 *
 * CHANGELOG v1.4.2-FIXED2 (2025-12-15):
 * - FIX: Descrizioni prodotti con testo bianco non leggibile
 *   - Problema: Editor WooCommerce mostrava testo bianco su sfondo bianco
 *   - Aggiunta short_description separata per ogni prodotto
 *   - Descrizioni ora in plain text con caratteri ✓ invece di HTML
 *   - Usato wp_strip_all_tags() per rimuovere formattazione
 *   - Descrizioni più dettagliate con features elencate
 *   - Calcolo risparmio annuale mostrato nella descrizione
 *   - Crediti Extra: descrizione migliorata e più chiara
 *
 * CHANGELOG v1.4.2-FIXED (2025-12-15):
 * - FIX: Trial non più gratuito - Misura anti-spam
 *   - Trial passa da €0 a €1.99 (sia mensile che annuale)
 *   - Costo simbolico per prevenire abusi e account spam
 *   - Descrizione aggiornata: "Costo simbolico di €1.99 come misura anti-spam"
 *   - Target aggiornato: "Test e demo (anti-spam)"
 *   - Mantiene 10 crediti mensili per testing completo
 *
 * CHANGELOG v1.4.2 (2025-12-15):
 * - FEATURE: Scelta fatturazione mensile/annuale per prodotti
 *   - Aggiunto campo radio nello step 4 del wizard: Mensile o Annuale
 *   - Fatturazione mensile: prezzi standard (€19.95/mese, €49.95/mese, €99.95/mese)
 *   - Fatturazione annuale: sconto 2 mesi (€199.50/anno, €499.50/anno, €999.50/anno)
 *   - Annuale = Paghi 10 mesi, ricevi 12 mesi (sconto ~17%)
 *   - Prodotti creati con suffisso: "IPV Pro - Starter - Mensile" o "IPV Pro - Starter - Annuale"
 *   - Metadata aggiuntivi: _ipv_billing_type (monthly/yearly), _ipv_billing_period (month/year)
 *   - Salvataggio preferenza billing type in option: ipv_vendor_billing_type
 *   - Tabella prezzi con evidenziazione colonna selezionata (jQuery)
 *
 * CHANGELOG v1.4.1-FIXED4 (2025-12-15):
 * - FIX: WooCommerce incompatibility warning
 *   - Dichiarata compatibilità WooCommerce HPOS (High-Performance Order Storage)
 *   - Aggiunto hook before_woocommerce_init per FeaturesUtil
 *   - Compatibilità dichiarata per: custom_order_tables, orders_cache
 *   - Risolve: "WooCommerce has detected that some of your active plugins are incompatible"
 *   - Plugin ora pienamente compatibile con WooCommerce 8.0+
 *
 * CHANGELOG v1.4.1-FIXED3 (2025-12-15):
 * - FIX: Creazione automatica prodotti nel wizard non funzionava
 *   - Aggiunto handler admin_post_ipv_vendor_create_products in setup-wizard
 *   - Implementato metodo create_products() che crea 5 prodotti WooCommerce
 *   - Prodotti: Trial (€0, 10 credits), Starter (€19.95, 25 credits),
 *              Professional (€49.95, 100 credits), Business (€99.95, 500 credits),
 *              Crediti Extra (€0.35/credit, min 10)
 *   - Fix: pulsante "Crea Prodotti Automaticamente" ora funzionante
 * - FIX: Pagina admin inaccessibile (page=ipv-vendor)
 *   - Aggiunto redirect automatico da page=ipv-vendor a page=ipv-vendor-dashboard
 *   - Risolve errore "Sorry, you are not allowed to access this page"
 *   - URL corretto: admin.php?page=ipv-vendor-dashboard
 *
 * CHANGELOG v1.4.1-FIXED2 (2025-12-14):
 * - FIX CRITICO: Class "IPV_Pro_Vendor_Auto_Installer" not found (righe 73, 504)
 *   - Aggiunto metodo interno get_setup_progress() in setup-wizard
 *   - Rimossi tutti i riferimenti a IPV_Pro_Vendor_Auto_Installer::get_setup_progress()
 *   - Setup wizard ora completamente indipendente
 *   - Risolve fatal error in class-setup-wizard.php:73 e :504
 *
 * CHANGELOG v1.4.1-FIXED (2025-12-14):
 * - FIX CRITICO: Class "IPV_Vendor_API_Gateway" not found
 *   - Precaricamento classi core PRIMA dell'autoloader
 *   - require_once per class-api-gateway.php, class-license-manager.php, class-credits-manager.php
 *   - Risolve fatal error in class-vendor-core.php:51
 * - FIX CRITICO: Database migration "Unknown column 'site_url'"
 *   - Aggiunta verifica esistenza colonna site_url prima di ALTER TABLE
 *   - Se mancante, viene creata automaticamente prima di site_unlock_at
 *   - Previene errori SQL su upgrade da versioni vecchie
 * - FIX: Class "IPV_Pro_Vendor_Auto_Installer" not found in redirect (riga 34)
 *   - Rimossa dipendenza da auto-installer class
 *   - Check diretto su option 'ipv_vendor_setup_complete'
 *
 * CHANGELOG v1.4.1 (2025-12-14):
 * - CREDITS SYSTEM: Credits split in monthly + extra
 *   - credits_monthly: Reset ogni mese al valore del piano
 *   - credits_extra: Persistenti, acquistabili separatamente
 *   - credits_used_month: Counter per usage mensile
 * - DATABASE: Nuova tabella ipv_credit_ledger per audit trail
 *   - Log completo di tutte le transazioni crediti
 *   - Supporto per grant_monthly, grant_extra, consume, adjust
 * - ADMIN: Interfaccia completa gestione licenze
 *   - WP_List_Table per licenze con search e paginazione
 *   - WP_List_Table per ledger transazioni
 *   - Admin actions: add credits, reset monthly, unlock site, rebind
 *   - Site unlock con cooldown 7 giorni
 *   - CSV export per licenze e ledger
 * - WOOCOMMERCE: Metabox ordini con info licenza
 * - MY ACCOUNT: Endpoint acquisto crediti extra (ipv-credits)
 * - COMPATIBILITÀ: Fix class alias IPV_Vendor_API_Gateway
 *
 * CHANGELOG v1.3.18 (2025-12-11):
 * - FIX CRITICO: Compatibilità PHP 8.x - Risolti deprecation warnings
 *   - Fix: strpos() e str_replace() ricevevano null invece di string
 *   - Aggiunto type-safe casting per tutti i get_post_meta()
 *   - class-woocommerce-integration.php: Validazione crediti e activation limit
 *   - class-license-manager.php: Safe casting per variant_slug, credits, activation
 *   - class-plans-manager.php: Protezione display prodotti WooCommerce
 *   - Eliminati PHP Deprecated warnings nel log
 *   - Compatibilità totale con PHP 8.0, 8.1, 8.2, 8.3
 *
 * CHANGELOG v1.3.17 (2025-12-11):
 * - FIX CRITICO: Descrizioni prodotti WooCommerce corrotte
 *   - Nuovo metodo emoji_to_html_entities() in class-plans-manager.php
 *   - Converti emoji Unicode in HTML entities (compatibile UTF8)
 *   - Descrizioni prodotti ora strutturate in 4 sezioni
 *   - Fix: emoji 🎬📊🚀 ora renderizzate correttamente
 *
 * CHANGELOG v1.3.11:
 * - PERFORMANCE: Ottimizzato polling SupaData
 *   - Intervallo ridotto da 5s a 2s
 *   - Max attempts ridotto da 30 a 20
 *   - Tempo massimo polling: 40s invece di 150s
 *   - Velocità generazione trascrizioni migliorata ~70%
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Constants
define( 'IPV_VENDOR_VERSION', '1.4.2-FIXED10' );
define( 'IPV_VENDOR_FILE', __FILE__ );
define( 'IPV_VENDOR_DIR', plugin_dir_path( __FILE__ ) );
define( 'IPV_VENDOR_URL', plugin_dir_url( __FILE__ ) );

// Load core classes BEFORE autoloader to avoid class not found errors
require_once IPV_VENDOR_DIR . 'includes/class-auto-configurator.php';
require_once IPV_VENDOR_DIR . 'includes/class-api-gateway.php'; // CRITICAL: Load before vendor-core
require_once IPV_VENDOR_DIR . 'includes/class-license-manager.php';
require_once IPV_VENDOR_DIR . 'includes/class-credits-manager.php';

// Declare WooCommerce HPOS compatibility (v1.4.1-FIXED4)
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'orders_cache', __FILE__, true );
    }
} );

// Check WooCommerce
add_action( 'admin_init', 'ipv_vendor_check_woocommerce' );
function ipv_vendor_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p><strong>IPV Pro Vendor</strong> richiede WooCommerce attivo!</p></div>';
        });
        deactivate_plugins( plugin_basename( __FILE__ ) );
        return;
    }
}

// Autoloader
spl_autoload_register( function( $class ) {
    if ( strpos( $class, 'IPV_Vendor_' ) === 0 ) {
        $file = strtolower( str_replace( ['IPV_Vendor_', '_'], ['', '-'], $class ) );
        $path = IPV_VENDOR_DIR . 'includes/class-' . $file . '.php';
        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
});

// Load core
require_once IPV_VENDOR_DIR . 'includes/class-vendor-core.php';

// Init
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'WooCommerce' ) ) {
        // v1.4.1 - Database migration
        require_once IPV_VENDOR_DIR . 'includes/class-db.php';
        if ( IPV_Vendor_DB::needs_migration() ) {
            IPV_Vendor_DB::migrate();
        }

        // Core
        IPV_Vendor_Core::instance();

        // Setup Wizard (must be loaded before is_admin check for admin-post.php)
        require_once IPV_VENDOR_DIR . 'includes/class-setup-wizard.php';
        IPV_Pro_Vendor_Setup_Wizard::init();

        // v1.4.1 - Admin features
        if ( is_admin() ) {

            require_once IPV_VENDOR_DIR . 'includes/class-admin-actions.php';
            IPV_Vendor_Admin_Actions::init();

            require_once IPV_VENDOR_DIR . 'includes/class-admin-export.php';
            IPV_Vendor_Admin_Export::init();

            require_once IPV_VENDOR_DIR . 'includes/class-order-metabox.php';
            IPV_Vendor_Order_Metabox::init();

            // v1.4.2-FIXED3 - Migration tool for product descriptions
            if ( file_exists( IPV_VENDOR_DIR . 'includes/migrate-product-descriptions.php' ) ) {
                require_once IPV_VENDOR_DIR . 'includes/migrate-product-descriptions.php';
            }
        }
    }
});

// Activation
register_activation_hook( __FILE__, function() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( 'IPV Pro Vendor richiede WooCommerce. Installa e attiva WooCommerce prima di attivare questo plugin.' );
    }

    // v1.4.0 - Auto-installer (database tables, defaults, CRON)
    require_once IPV_VENDOR_DIR . 'includes/class-auto-installer.php';
    IPV_Pro_Vendor_Auto_Installer::install();

    // v1.3.3 - Auto-configurazione sistema
    require_once IPV_VENDOR_DIR . 'includes/class-auto-configurator.php';
    $auto_config = IPV_Vendor_Auto_Configurator::instance();
    $auto_config->activate();

    // Core activation
    require_once IPV_VENDOR_DIR . 'includes/class-vendor-core.php';
    IPV_Vendor_Core::activate();
});

// Deactivation
register_deactivation_hook( __FILE__, function() {
    IPV_Vendor_Core::deactivate();
});

// Uninstall
register_uninstall_hook( __FILE__, 'ipv_vendor_uninstall' );
function ipv_vendor_uninstall() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-auto-installer.php';
    IPV_Pro_Vendor_Auto_Installer::uninstall();
}

// Admin notices for auto-configuration
add_action( 'admin_notices', function() {
    IPV_Vendor_Auto_Configurator::instance()->show_activation_notice();
});

// Admin Status Page
require_once IPV_VENDOR_DIR . 'includes/class-admin-status-page.php';
add_action( 'plugins_loaded', function() {
    if ( is_admin() ) {
        IPV_Vendor_Admin_Status_Page::instance()->init();
    }
});

// Periodic health check (ogni 12 ore)
add_action( 'init', function() {
    if ( ! wp_next_scheduled( 'ipv_vendor_health_check' ) ) {
        wp_schedule_event( time(), 'twicedaily', 'ipv_vendor_health_check' );
    }
});

add_action( 'ipv_vendor_health_check', function() {
    IPV_Vendor_Auto_Configurator::instance()->health_check();
});
