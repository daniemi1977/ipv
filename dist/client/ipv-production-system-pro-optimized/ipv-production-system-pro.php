<?php
/**
 * Plugin Name: IPV Production System Pro
 * Plugin URI: https://github.com/daniemi1977/ipv
 * Description: Professional video production system for YouTube content creators: multi-source imports, AI-powered transcriptions, automated descriptions with Golden Prompt, video wall with AJAX filters, and Elementor integration.
 * Version: 10.4.0
 * Author: Daniele Milone
 * Author URI: https://github.com/daniemi1977
 * Text Domain: ipv-production-system-pro
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License: GPL v2 or later
 *
 * CHANGELOG v10.4.0 (2025-12-16) - MAJOR UI/UX UPDATE:
 * 🎨 UI/UX MODERNIZATION:
 * - ✨ Integrato Tailwind CSS v3.4 per design moderno e responsive
 * - 🎨 Redesign completo pagina Licenza con UI/UX professionale
 * - 📱 Mobile-first responsive design ottimizzato
 * - 🌈 Card colorate per stati licenza (verde=attiva, giallo=non attiva)
 * - 📊 Barra crediti moderna con colori dinamici
 * - 🔘 Bottoni Tailwind per tutte le azioni
 * - ✅ Benefici licenza con icone SVG e layout moderno
 * - 🎯 Grid responsive per info licenza
 *
 * 🎨 CSS AGGIORNATO:
 * - Aggiunte classi .ipv-btn-warning (amber)
 * - Aggiunte classi .ipv-btn-purple
 * - Classi ipv-card, ipv-input, ipv-label modernizzate
 *
 * 📦 FILES MODIFICATI:
 * - includes/class-license-manager-client.php (render_license_page)
 * - admin/assets/css/modern-client.css (nuove classi bottoni)
 *
 * 📱 RISULTATO:
 * - UI consistente e moderna
 * - Tailwind CSS applicato uniformemente
 * - Mobile responsive su tutte le pagine
 * - UX professionale e intuitiva
 *
 * CHANGELOG v10.3.2-FIXED2 (2025-12-15):
 * - FIX: Errore permessi sui link upgrade/acquisto licenza
 *   - Problema: "Non hai il permesso di accedere a questa pagina" quando si clicca "Compra una licenza"
 *   - Causa: Link con URL errati in class-plan-upgrade.php
 *   - Fix 1: Link attivazione licenza (riga 93)
 *     * Prima: admin.php?page=ipv-production-license (pagina non esistente)
 *     * Dopo: admin.php?page=ipv-settings&tab=license (pagina corretta)
 *   - Fix 2: Link crediti extra (riga 255)
 *     * Prima: {server_url} (senza path)
 *     * Dopo: {server_url}/shop/ (con path WooCommerce)
 *   - Fix 3: Link upgrade piani (righe 307-321)
 *     * Prima: {server_url}/shop/ (generico)
 *     * Dopo: {server_url}/product/ipv-pro-{variant}/ (URL prodotto specifico)
 *     * URL costruito dinamicamente da variant del piano
 *     * Esempi: /product/ipv-pro-starter/, /product/ipv-pro-professional/
 *   - Tutti i link ora puntano a pagine esistenti
 *   - ERRORE PERMESSI RISOLTO
 *
 * CHANGELOG v10.3.2-FIXED (2025-12-15):
 * - FIX CRITICO: Metodi license mancanti in IPV_Prod_API_Client_Optimized
 *   - Problema: "Call to undefined method IPV_Prod_API_Client_Optimized::deactivate_license()"
 *   - Fatal error quando si tenta di deattivare la licenza dal CLIENT
 *   - Mancavano 3 metodi nella versione ottimizzata:
 *     1. activate_license() - Attivazione licenza
 *     2. deactivate_license() - Deattivazione licenza (crash line 116)
 *     3. get_license_info() - Info dettagliate licenza
 *   - Fix: Aggiunti tutti e 3 i metodi alla classe ottimizzata
 *   - Gestione corretta di attivazione/deattivazione licenza
 *   - Cache disabilitata per operazioni di license (sempre fresh data)
 *   - ERRORE FATALE RISOLTO
 *
 * CHANGELOG v10.3.2 (2025-12-15):
 * - FEATURE: Gestione Upgrade Piano direttamente dal CLIENT
 *   - Nuova pagina "⬆️ Upgrade Piano" nel menu IPV Videos
 *   - Visualizzazione piano attuale con dettagli crediti (mensili + extra)
 *   - Card piani disponibili con prezzi mensili e annuali
 *   - Link diretto al checkout WooCommerce del server
 *   - Tabella comparativa completa di tutti i piani
 *   - Design moderno con gradients e animazioni hover
 *   - Calcolo automatico risparmio piano annuale
 *   - Gestione caso "piano massimo" (Business)
 *
 * CHANGELOG v10.3.1-FIXED3 (2025-12-15):
 * - FIX CRITICO GLOBALE: Class "IPV_Prod_API_Client" not found - RISOLTO DEFINITIVAMENTE
 *   - Problema: 20 riferimenti a IPV_Prod_API_Client trovati in 8 file
 *   - Fatal errors in: youtube-api.php:49, dashboard.php:40, diagnostics.php:269
 *   - File corretti (20 occorrenze totali):
 *     1. class-settings.php (1)
 *     2. class-ai-generator.php (2)
 *     3. class-youtube-api.php (5)
 *     4. class-supadata.php (4)
 *     5. class-bulk-import.php (1)
 *     6. class-dashboard.php (1)
 *     7. class-remote-updater.php (2)
 *     8. class-diagnostics.php (4)
 *   - Fix: Sostituiti TUTTI i riferimenti con IPV_Prod_API_Client_Optimized
 *   - TUTTI gli errori fatali risolti
 *
 * CHANGELOG v10.3.1-FIXED2 (2025-12-15):
 * - FIX CRITICO: Class "IPV_Prod_API_Client" not found in license-manager-client.php
 *   - Problema: 6 riferimenti a IPV_Prod_API_Client in class-license-manager-client.php
 *   - Righe: 62 (is_license_active), 95 (instance), 137 (is_license_active),
 *            387 (DEFAULT_SERVER), 492 (instance), 512 (instance)
 *   - Fatal error: "Class IPV_Prod_API_Client not found at line 62"
 *   - Fix: Sostituiti tutti i riferimenti con IPV_Prod_API_Client_Optimized
 *   - Aggiunto class_exists() check per sicurezza extra alla riga 63
 *
 * CHANGELOG v10.3.1-FIXED (2025-12-15):
 * - FIX CRITICO: Class "IPV_Prod_API_Client" not found in credits UX
 *   - Problema: credits-widget.php e credits-notices.php chiamavano IPV_Prod_API_Client::is_license_active()
 *   - Ma la classe nel CLIENT si chiama IPV_Prod_API_Client_Optimized
 *   - Fatal error alla riga 44 (widget) e 99 (notices)
 *   - Fix: Rimossa dipendenza dalla classe API_Client
 *   - Ora usa check diretto su option 'ipv_license_info'
 *   - Più robusto e senza dipendenze circolari
 *
 * CHANGELOG v10.3.1:
 * - UX ENHANCEMENT: Dashboard widget con crediti rimanenti
 *   - Widget visibile nella dashboard WordPress
 *   - Mostra crediti rimanenti con barra di progresso
 *   - Colore dinamico in base alla percentuale (verde/giallo/rosso)
 *   - Link diretto per acquisto crediti extra
 *   - Visualizza data reset e piano corrente
 * - UX ENHANCEMENT: Notifiche admin per crediti bassi
 *   - Avviso quando crediti < 20% (giallo)
 *   - Allerta quando crediti < 10% (rosso)
 *   - Notifica critica quando crediti = 0 (rosso intenso)
 *   - Barra di progresso visuale nelle notifiche
 *   - Link rapido per acquisto crediti extra
 * - FEATURE: Link diretto a "Acquista Crediti Extra"
 *   - Punta a /my-account/ipv-credits/ del server SaaS
 *   - Bottone "Aggiorna Licenza" per refresh manuale
 *
 * CHANGELOG v10.2.14:
 * - UX ENHANCEMENT: Pannello Video Wall completamente rinnovato
 *   - Libreria Shortcodes completa con tutti i 7 shortcode disponibili
 *   - Ogni shortcode con parametri dettagliati ed esempi pratici
 *   - Pulsanti "Copia" con feedback visivo per copiare gli shortcode
 *   - Sezioni espandibili per parametri opzionali
 * - UX ENHANCEMENT: Sistema Live Help con tooltip
 *   - Icone ℹ️ hover su ogni opzione con spiegazioni contestuali
 *   - Tooltip informativi per tutte le sezioni: Layout, Display, Colori, Ordinamento, Animazioni
 *   - Descrizioni migliorate con emoji e consigli pratici
 *   - Guida rapida per utilizzo icone help
 * - Shortcode documentati:
 *   - [ipv_video_wall] - Video wall completo con filtri AJAX
 *   - [ipv_coming_soon] / [ipv_in_programma] - Video in anteprima/premiere
 *   - [ipv_video] - Player singolo responsive
 *   - [ipv_grid] - Griglia video semplice
 *   - [ipv_search] - Form ricerca avanzata
 *   - [ipv_stats] - Box statistiche
 *
 * CHANGELOG v10.2.13:
 * - FIX CRITICO: Import singolo ora usa la coda di produzione
 *   - class-simple-import.php: modificato import_video() per usare IPV_Prod_Queue::enqueue()
 *   - I video importati singolarmente ora ricevono automaticamente trascrizione e descrizione AI
 *   - Processo unificato con import bulk/RSS/canale
 *   - Tempo processing: ~34 secondi (con system cron: 39 secondi totali)
 * - OTTIMIZZAZIONE: AI Queue frequenza ridotta da 1 minuto a 5 minuti
 *   - class-ai-queue.php: cambiato da 'minute' a 'ipv_every_5_minutes'
 *   - Riduzione esecuzioni cron: da 60/ora a 12/ora (80% in meno)
 *   - Minor carico server, stessa efficienza
 * - OTTIMIZZAZIONE: AI Queue batch size aumentato da 1 a 3 video
 *   - class-ai-queue.php: modificato run() per processare 3 video per batch
 *   - Throughput aumentato: da 12 a 36 video/ora
 *   - Allineato con coda principale per performance ottimali
 * - PERFORMANCE: Processing reale misurato 34s per video (20s trascrizione + 12s AI)
 * - RACCOMANDAZIONE: Implementare System Cron per eliminare WordPress Cron delay (3 minuti)
 *
 * CHANGELOG v10.0.17:
 * - FIX CRITICO: Corretti tutti i parent menu (ipv-production → edit.php?post_type=ipv_video)
 *   - class-analytics.php, class-bulk-operations.php, class-bulk-tools.php
 *   - class-language-manager.php, class-speaker-rules.php, class-taxonomy-manager.php
 *   - class-unified-importer.php, class-elementor-widgets.php
 * - FIX: Corretti link tab navigation in tutte le pagine admin
 *   - page=ipv-production → page=ipv-dashboard
 *   - page=ipv-production-import → page=ipv-import
 *   - page=ipv-production-settings → page=ipv-settings
 * - Risolve errore "Non hai il permesso di accedere a questa pagina"
 *
 * CHANGELOG v10.0.16:
 * - FIX CRITICO: YouTube Data API v3 - Risolto formato risposta server
 *   - Problema: Server ritorna { success: true, video_data: {...} }
 *   - Client si aspettava: { items: [{...}] } (formato raw YouTube API)
 *   - Fix: Aggiunto parse_server_video_data() in class-youtube-api.php
 *   - Converte formato custom server in formato interno atteso
 *   - Risolve: titoli video mostrano ID invece di titolo reale
 *   - Risolve: dati strutturali video (thumbnails, duration, views) mancanti
 *   - Lista video ora completa con tutti i metadati
 *
 * CHANGELOG v10.0.15:
 * - FIX CRITICO: RSS Auto-Import non funzionava - cron job mai attivato
 *   - Problema: mismatch nomi cron job (ipv_rss_check_feed vs ipv_prod_rss_import)
 *   - Fix: allineato class-rss-importer.php a class-import-unified.php
 *   - RSS ora schedula correttamente e importa video automaticamente
 * - FIX CRITICO: Import dal canale YouTube non implementato
 *   - Implementato usando RSS feed del canale YouTube
 *   - Fetch video dal feed XML, aggiunge alla coda, mostra risultati
 *   - Supporta max_results personalizzato, skip video già importati
 *
 * CHANGELOG v10.0.14:
 * - FIX CRITICO FATALE: Risolto errore PHP Fatal "Call to undefined method IPV_Prod_API_Client::post()"
 *   - Aggiunto metodo pubblico get_youtube_video_data() in class-api-client.php
 *   - Corretto class-youtube-api.php per usare il metodo corretto
 *   - Fix blocca import video e processing coda (errori 500)
 *   - Risolve: titoli video mancanti, dati strutturali video mancanti, lista video incompleta
 *
 * CHANGELOG v10.0.13:
 * - FIX CRITICO: Corretti tutti i link menu admin
 *   - Cambiati da admin.php?page=ipv-production-* a edit.php?post_type=ipv_video&page=ipv-production-*
 *   - Risolve errore "Non hai il permesso di accedere a questa pagina"
 *   - Fix in: ipv-production-system-pro.php, class-language-manager.php, class-taxonomy-manager.php,
 *     class-bulk-import.php, class-queue.php, class-youtube-importer.php, views/rss-settings.php
 *
 * CHANGELOG v10.0.12:
 * - AI PROMPTS: Rimosso "SEO-friendly" da tutti i prompt (UX improvement)
 *   - class-ai-generator.php: "Una descrizione" invece di "Una descrizione SEO-friendly"
 *   - class-ai-enhancements.php: "summary" invece di "summary SEO-friendly"
 *   - class-golden-prompt-manager.php: "tag rilevanti" invece di "tag SEO-friendly"
 * - AI METADATA FIX: Prompt fallback ora include sezioni 🗂️ ARGOMENTI e 👤 OSPITI
 *   - Fix: categorie e relatori ora vengono assegnati correttamente dall'AI
 *   - Il prompt fallback ora richiede esplicitamente le sezioni necessarie per l'estrazione metadata
 *   - Compatibile con extract_and_save_metadata() che cerca emoji sections
 *
 * CHANGELOG v10.0.11:
 * - UX: Aggiornamento licenza via AJAX (no page reload)
 * - AJAX handler ritorna dati aggiornati
 * - Update real-time di: crediti, scadenza, piano, email
 * - Feedback visivo immediato "Aggiornato!"
 * - FIX CRITICO: YouTube API ora usa server SaaS
 *   - Titolo video importato correttamente (non più solo ID)
 *   - Thumbnails, durata, descrizione dal server
 *   - class-youtube-api.php chiama /youtube/video-data server
 * - FIX CRITICO: Allineato enqueue_admin_assets a v9.2.2 funzionante
 *   - Handle 'bootstrap' invece di 'bootstrap-5' (compatibilità dipendenze)
 *   - Handle 'ipv-prod-admin' invece di 'ipv-admin' (compatibilità)
 *   - Dipendenze corrette: admin.js dipende da ['jquery', 'bootstrap']
 *   - Localize script 'ipv_admin' + 'ipvProdAjax' (doppia compatibilità)
 *   - Condizione hook semplificata come v9.2.2
 * - FIX: Pagina Coda - Bootstrap JS ora caricato correttamente
 * - FIX: Pagina Coda - Pulsante "Processa Ora" funzionante
 * - BRANDING: Rimosso riferimento "SupaData" nel CPT
 *
 * CHANGELOG v10.0.10:
 * - FIX: Auto-crea tabella queue se mancante (per upgrade da versioni precedenti)
 * - Elimina errori "Table 'wp_ipv_prod_queue' doesn't exist" dopo aggiornamento
 *
 * CHANGELOG v10.0.0:
 * - Architettura SaaS: API keys gestite dal server, non più locali
 * - Sistema licenze integrato
 * - Golden Prompt inseribile manualmente dall'utente
 * - Pannello Server Settings per configurazione endpoint
 * - Crediti mensili con tracking
 * - Aggiornamenti automatici dal server
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ============================================
// CONSTANTS
// ============================================

define( 'IPV_PROD_VERSION', '10.3.2-FIXED' );
define( 'IPV_PROD_PLUGIN_FILE', __FILE__ );
define( 'IPV_PROD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IPV_PROD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ============================================
// LOAD TEXT DOMAIN
// ============================================

function ipv_prod_load_textdomain() {
    $plugin_locale = get_option( 'ipv_plugin_language', 'auto' );
    
    if ( $plugin_locale !== 'auto' ) {
        return;
    }
    
    load_plugin_textdomain(
        'ipv-production-system-pro',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}
add_action( 'plugins_loaded', 'ipv_prod_load_textdomain', 5 );

// ============================================
// LOAD CORE FILES (Prima dell'autoloader)
// ============================================

// Logger - DEVE essere caricato per primo (usato da tutti)
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-logger.php';

// API Client - Gestisce comunicazione server (usa logger)
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-api-client.php';

// SupaData - Gestisce trascrizioni (usa API client)
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-supadata.php';

// AI Generator - Gestisce descrizioni AI (usa API client)
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-ai-generator.php';

// YouTube API - Gestisce dati video (usa API client)
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-youtube-api.php';

// License Manager - Gestisce attivazione licenza
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-license-manager-client.php';

// v10.3.1 - Credits UX Enhancements
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-credits-widget.php';
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-credits-notices.php';

// v10.3.2 - Plan Upgrade Manager
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-plan-upgrade.php';

// Helpers
if ( file_exists( IPV_PROD_PLUGIN_DIR . 'includes/class-helpers.php' ) ) {
    require_once IPV_PROD_PLUGIN_DIR . 'includes/class-helpers.php';
}

// Language Manager
if ( file_exists( IPV_PROD_PLUGIN_DIR . 'includes/class-language-manager.php' ) ) {
    require_once IPV_PROD_PLUGIN_DIR . 'includes/class-language-manager.php';
}

// CPT
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-cpt.php';

// Video List Columns
if ( file_exists( IPV_PROD_PLUGIN_DIR . 'includes/class-video-list-columns.php' ) ) {
    require_once IPV_PROD_PLUGIN_DIR . 'includes/class-video-list-columns.php';
}

// Video Row Actions (v10.3.1-FIXED3)
if ( file_exists( IPV_PROD_PLUGIN_DIR . 'includes/class-video-row-actions.php' ) ) {
    require_once IPV_PROD_PLUGIN_DIR . 'includes/class-video-row-actions.php';
}

// Taxonomy Manager
if ( file_exists( IPV_PROD_PLUGIN_DIR . 'includes/class-taxonomy-manager.php' ) ) {
    require_once IPV_PROD_PLUGIN_DIR . 'includes/class-taxonomy-manager.php';
}

// ============================================
// v10.0.4 - UNIFIED INTERFACES
// ============================================

// Dashboard - Panoramica crediti e stats
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-dashboard.php';

// Import Unificato - Singolo/Batch/RSS/Canale
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-import-unified.php';

// Settings Unificato - Server/Golden/Lingua/Generale
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-settings-unified.php';

// Tools Unificato - Bulk/Duplicati/Pulizia
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-tools.php';

// Diagnostics - v10.0.8 - Tool diagnostica connessione
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-diagnostics.php';

// ============================================
// v10.2.12 - FRONTEND CLASSES (embed YouTube nel contenuto)
// ============================================

// Video Frontend - Embed YouTube nel contenuto del CPT
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-video-frontend.php';

// Theme Compatibility - Template system con override support
require_once IPV_PROD_PLUGIN_DIR . 'includes/class-theme-compatibility.php';

// ============================================
// AUTOLOAD CLASSES
// ============================================

spl_autoload_register( function( $class ) {
    $prefix_map = [
        'IPV_Prod_' => 'class-',
        'IPV_'      => 'class-ipv-',
    ];

    foreach ( $prefix_map as $prefix => $file_prefix ) {
        if ( strpos( $class, $prefix ) === 0 ) {
            $class_name = str_replace( $prefix, '', $class );
            $class_file = strtolower( str_replace( '_', '-', $class_name ) );
            
            $file = IPV_PROD_PLUGIN_DIR . 'includes/' . $file_prefix . $class_file . '.php';
            
            if ( file_exists( $file ) ) {
                require_once $file;
                return;
            }
            
            if ( $prefix === 'IPV_' ) {
                $file = IPV_PROD_PLUGIN_DIR . 'includes/class-' . $class_file . '.php';
                if ( file_exists( $file ) ) {
                    require_once $file;
                    return;
                }
            }
        }
    }
} );

// ============================================
// LEGACY HELPER FUNCTIONS
// ============================================

if ( ! function_exists( 'ipv_duration_to_seconds' ) ) {
    function ipv_duration_to_seconds( $duration ) {
        if ( class_exists( 'IPV_Prod_Helpers' ) ) {
            return IPV_Prod_Helpers::duration_to_seconds( $duration );
        }
        return 0;
    }
}

if ( ! function_exists( 'ipv_get_formatted_duration' ) ) {
    function ipv_get_formatted_duration( $post_id ) {
        if ( class_exists( 'IPV_Prod_Helpers' ) ) {
            return IPV_Prod_Helpers::get_formatted_duration( $post_id );
        }
        return '';
    }
}

// ============================================
// MAIN PLUGIN CLASS
// ============================================

class IPV_Production_System_Pro {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // v10.0.24 - NEW CENTRALIZED MENU SYSTEM
        // Registra il CPT (senza menu - gestito da Menu Manager)
        IPV_Prod_CPT::init();
        
        // Menu Manager centralizzato
        IPV_Prod_Menu_Manager::init();
        
        // Form handlers (senza registrazione menu)
        IPV_Prod_Import_Unified::init();
        IPV_Prod_Settings_Unified::init();
        IPV_Prod_Tools::init();
        IPV_Prod_Simple_Import::init();
        IPV_Prod_Bulk_Import::init();
        IPV_Prod_Diagnostics::instance()->init();
        IPV_Prod_License_Manager_Client::init();
        
        // Dashboard data (senza menu)
        IPV_Prod_Dashboard::init();

        // Video Wall (frontend)
        if ( class_exists( 'IPV_Prod_Video_Wall' ) ) {
            IPV_Prod_Video_Wall::init();
        }

        // Coming Soon
        if ( class_exists( 'IPV_Prod_Coming_Soon' ) ) {
            IPV_Prod_Coming_Soon::init();
        }

        // Admin assets
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        // Cron Schedules
        add_filter( 'cron_schedules', [ $this, 'add_cron_schedules' ] );
        
        // Cron Actions
        add_action( 'ipv_prod_process_queue', [ 'IPV_Prod_Queue', 'process_queue' ] );
        add_action( 'ipv_prod_update_youtube_data', [ 'IPV_Prod_Queue', 'update_all_youtube_data' ] );

        // Auto-schedule CRON
        add_action( 'admin_init', [ $this, 'ensure_cron_scheduled' ] );

        // v10.0.10 - Auto-create queue table if missing (for upgrades from pre-v10.0.7)
        add_action( 'admin_init', [ $this, 'ensure_queue_table_exists' ] );

        // AJAX
        add_action( 'wp_ajax_ipv_prod_get_stats', [ $this, 'ajax_get_stats' ] );
        add_action( 'wp_ajax_ipv_prod_process_queue', [ $this, 'ajax_process_queue' ] );
        add_action( 'wp_ajax_ipv_prod_start_cron', [ $this, 'ajax_start_cron' ] );

        // Activation/Deactivation
        register_activation_hook( IPV_PROD_PLUGIN_FILE, [ $this, 'activate' ] );
        register_deactivation_hook( IPV_PROD_PLUGIN_FILE, [ $this, 'deactivate' ] );

        // Save duration on post save
        add_action( 'save_post_ipv_video', [ $this, 'save_duration_seconds' ] );

        // Markdown filter
        add_filter( 'the_content', [ $this, 'filter_video_content' ] );
    }

    /**
     * Add custom cron schedules
     */
    public function add_cron_schedules( $schedules ) {
        $schedules['ipv_every_5_minutes'] = [
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display'  => __( 'Every 5 Minutes', 'ipv-production-system-pro' ),
        ];
        $schedules['ipv_every_15_minutes'] = [
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display'  => __( 'Every 15 Minutes', 'ipv-production-system-pro' ),
        ];
        return $schedules;
    }

    /**
     * Enqueue admin assets
     * v10.2.2 - Unified Bootstrap loading (removed duplicate)
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on plugin pages
        if ( strpos( $hook, 'ipv-production' ) === false && strpos( $hook, 'ipv_video' ) === false && get_post_type() !== 'ipv_video' ) {
            return;
        }

        // Bootstrap CSS (unified version 5.3.3)
        wp_enqueue_style(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
            [],
            '5.3.3'
        );

        // Bootstrap Icons
        wp_enqueue_style(
            'bootstrap-icons',
            'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css',
            [],
            '1.11.1'
        );

        // Plugin CSS (dipende da bootstrap come v9.2.2)
        if ( file_exists( IPV_PROD_PLUGIN_DIR . 'assets/css/admin.css' ) ) {
            wp_enqueue_style(
                'ipv-prod-admin',
                IPV_PROD_PLUGIN_URL . 'assets/css/admin.css',
                [ 'bootstrap', 'bootstrap-icons' ],
                IPV_PROD_VERSION
            );
        }

        // Bootstrap JS (unified version 5.3.3)
        wp_enqueue_script(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
            [ 'jquery' ],
            '5.3.3',
            true
        );

        // Admin JS (dipende da jquery + bootstrap come v9.2.2)
        if ( file_exists( IPV_PROD_PLUGIN_DIR . 'assets/js/admin.js' ) ) {
            wp_enqueue_script(
                'ipv-prod-admin',
                IPV_PROD_PLUGIN_URL . 'assets/js/admin.js',
                [ 'jquery', 'bootstrap' ],
                IPV_PROD_VERSION,
                true
            );
        }

        // v10.0.11 - Localize come v9.2.2 (ipv_admin)
        wp_localize_script( 'ipv-prod-admin', 'ipv_admin', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'ipv_admin_nonce' ),
            'i18n'     => [
                'confirm_delete' => __( 'Are you sure?', 'ipv-production-system-pro' ),
                'processing'     => __( 'Processing...', 'ipv-production-system-pro' ),
                'success'        => __( 'Success!', 'ipv-production-system-pro' ),
                'error'          => __( 'Error occurred', 'ipv-production-system-pro' ),
            ],
        ] );

        // v10.0.11 - Alias ipvProdAjax per compatibilità admin.js
        wp_localize_script( 'ipv-prod-admin', 'ipvProdAjax', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ipv_admin_nonce' ),
        ] );
    }

    /**
     * Render import page
     */
    public function render_import_page() {
        if ( class_exists( 'IPV_Prod_Simple_Import' ) ) {
            IPV_Prod_Simple_Import::render_page();
        } else {
            echo '<div class="wrap"><h1>Import</h1><p>Modulo import non disponibile.</p></div>';
        }
    }

    /**
     * Render bulk import page
     */
    public function render_bulk_import_page() {
        if ( class_exists( 'IPV_Prod_Bulk_Import' ) ) {
            IPV_Prod_Bulk_Import::render_page();
        } else {
            echo '<div class="wrap"><h1>Bulk Import</h1><p>Modulo bulk import non disponibile.</p></div>';
        }
    }

    /**
     * Render queue page
     */
    public function render_queue_page() {
        if ( class_exists( 'IPV_Prod_Queue_Dashboard' ) ) {
            IPV_Prod_Queue_Dashboard::render_page();
        } elseif ( class_exists( 'IPV_Prod_Queue' ) ) {
            IPV_Prod_Queue::render_admin_page();
        } else {
            echo '<div class="wrap"><h1>Queue</h1><p>Modulo coda non disponibile.</p></div>';
        }
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        $total_videos = wp_count_posts( 'ipv_video' );
        $published = $total_videos->publish ?? 0;
        $draft = $total_videos->draft ?? 0;
        
        $license_info = get_option( 'ipv_license_info', [] );
        $credits = $license_info['credits'] ?? [];
        $is_licensed = IPV_Prod_API_Client::is_license_active();

        ?>
        <div class="wrap">
            <h1>📊 <?php _e( 'Dashboard', 'ipv-production-system-pro' ); ?></h1>

            <div class="row mt-4" style="max-width: 1200px;">
                <!-- License Status -->
                <div class="col-md-4 mb-4">
                    <div class="card h-100 <?php echo $is_licensed ? 'border-success' : 'border-warning'; ?>">
                        <div class="card-header <?php echo $is_licensed ? 'bg-success text-white' : 'bg-warning'; ?>">
                            <h5 class="mb-0">🔑 <?php _e( 'Licenza', 'ipv-production-system-pro' ); ?></h5>
                        </div>
                        <div class="card-body">
                            <?php if ( $is_licensed ) : ?>
                                <p class="text-success"><strong>✅ <?php _e( 'Attiva', 'ipv-production-system-pro' ); ?></strong></p>
                                <?php if ( ! empty( $license_info['variant'] ) ) : ?>
                                    <p><?php _e( 'Piano:', 'ipv-production-system-pro' ); ?> <strong><?php echo esc_html( ucfirst( $license_info['variant'] ) ); ?></strong></p>
                                <?php endif; ?>
                            <?php else : ?>
                                <p class="text-warning"><strong>⚠️ <?php _e( 'Non attiva', 'ipv-production-system-pro' ); ?></strong></p>
                                <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video&page=ipv-license' ); ?>" class="btn btn-primary btn-sm">
                                    <?php _e( 'Attiva ora', 'ipv-production-system-pro' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Credits -->
                <?php if ( $is_licensed && ! empty( $credits ) ) : 
                    $percentage = $credits['percentage'] ?? 0;
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">📊 <?php _e( 'Crediti', 'ipv-production-system-pro' ); ?></h5>
                        </div>
                        <div class="card-body">
                            <h3><?php echo esc_html( $credits['credits_remaining'] ?? 0 ); ?> / <?php echo esc_html( $credits['credits_total'] ?? 0 ); ?></h3>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar <?php echo $percentage > 50 ? 'bg-success' : ( $percentage > 20 ? 'bg-warning' : 'bg-danger' ); ?>" 
                                     style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <?php if ( ! empty( $credits['reset_date_formatted'] ) ) : ?>
                                <small class="text-muted"><?php _e( 'Reset:', 'ipv-production-system-pro' ); ?> <?php echo esc_html( $credits['reset_date_formatted'] ); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Videos Stats -->
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">🎬 <?php _e( 'Video', 'ipv-production-system-pro' ); ?></h5>
                        </div>
                        <div class="card-body">
                            <h3><?php echo number_format_i18n( $published ); ?></h3>
                            <p class="text-muted mb-0"><?php _e( 'Video pubblicati', 'ipv-production-system-pro' ); ?></p>
                            <?php if ( $draft > 0 ) : ?>
                                <small><?php echo number_format_i18n( $draft ); ?> <?php _e( 'bozze', 'ipv-production-system-pro' ); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-4" style="max-width: 800px;">
                <div class="card-header">
                    <h5 class="mb-0">⚡ <?php _e( 'Azioni rapide', 'ipv-production-system-pro' ); ?></h5>
                </div>
                <div class="card-body">
                    <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video&page=ipv-import' ); ?>" class="btn btn-primary me-2">
                        📥 <?php _e( 'Import Video', 'ipv-production-system-pro' ); ?>
                    </a>
                    <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video' ); ?>" class="btn btn-outline-secondary me-2">
                        📋 <?php _e( 'Gestisci Video', 'ipv-production-system-pro' ); ?>
                    </a>
                    <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video&page=ipv-settings' ); ?>" class="btn btn-outline-secondary">
                        ⚙️ <?php _e( 'Impostazioni', 'ipv-production-system-pro' ); ?>
                    </a>
                </div>
            </div>

            <div class="mt-4 text-muted">
                <strong>IPV Production System Pro</strong> v<?php echo IPV_PROD_VERSION; ?> |
                <strong>Shortcode:</strong> <code>[ipv_video_wall]</code>
            </div>
        </div>
        <?php
    }

    /**
     * Filter video content for markdown
     */
    public function filter_video_content( $content ) {
        if ( get_post_type() !== 'ipv_video' ) {
            return $content;
        }

        if ( class_exists( 'IPV_Prod_Helpers' ) ) {
            $md = get_post_meta( get_the_ID(), IPV_Prod_Helpers::META_AI_DESCRIPTION, true );
        } else {
            $md = get_post_meta( get_the_ID(), '_ipv_ai_description', true );
        }
        
        if ( $md && class_exists( 'IPV_Markdown_Full' ) ) {
            return IPV_Markdown_Full::parse( $md );
        }

        return $content;
    }

    /**
     * Save duration in seconds
     */
    public function save_duration_seconds( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        $meta_key_sec = class_exists( 'IPV_Prod_Helpers' ) ? IPV_Prod_Helpers::META_YT_DURATION_SEC : '_ipv_yt_duration_seconds';
        $meta_key_dur = class_exists( 'IPV_Prod_Helpers' ) ? IPV_Prod_Helpers::META_YT_DURATION : '_ipv_yt_duration';

        $duration_seconds = get_post_meta( $post_id, $meta_key_sec, true );
        if ( empty( $duration_seconds ) ) {
            $duration_iso = get_post_meta( $post_id, $meta_key_dur, true );
            if ( $duration_iso ) {
                $seconds = ipv_duration_to_seconds( $duration_iso );
                update_post_meta( $post_id, $meta_key_sec, $seconds );
            }
        }
    }

    /**
     * AJAX: Get stats
     */
    public function ajax_get_stats() {
        check_ajax_referer( 'ipv_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $total = wp_count_posts( 'ipv_video' );
        wp_send_json_success( [
            'total_videos' => isset( $total->publish ) ? $total->publish : 0,
            'pending'      => isset( $total->pending ) ? $total->pending : 0,
        ] );
    }

    /**
     * AJAX: Process queue
     */
    public function ajax_process_queue() {
        check_ajax_referer( 'ipv_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        if ( class_exists( 'IPV_Prod_Queue' ) ) {
            IPV_Prod_Queue::process_queue();
            // v10.0.11 - Ritorna stats aggiornati per update AJAX
            $stats = IPV_Prod_Queue::get_stats();
            wp_send_json_success( [
                'message' => __( 'Queue processed', 'ipv-production-system-pro' ),
                'stats'   => $stats,
            ] );
        }

        wp_send_json_success( [ 'message' => __( 'Queue processed', 'ipv-production-system-pro' ) ] );
    }

    /**
     * Ensure CRON is scheduled
     */
    public function ensure_cron_scheduled() {
        if ( ! wp_next_scheduled( 'ipv_prod_process_queue' ) ) {
            wp_schedule_event( time(), 'ipv_every_5_minutes', 'ipv_prod_process_queue' );
        }

        if ( ! wp_next_scheduled( 'ipv_prod_update_youtube_data' ) ) {
            wp_schedule_event( time(), 'hourly', 'ipv_prod_update_youtube_data' );
        }
    }

    /**
     * v10.0.10 - Ensure queue table exists (auto-create on upgrade)
     * Fixes issue where users upgrading from pre-v10.0.7 don't have the table
     */
    public function ensure_queue_table_exists() {
        // Only check once per version to avoid unnecessary DB queries
        $checked_version = get_option( 'ipv_queue_table_checked', '' );
        if ( $checked_version === IPV_PROD_VERSION ) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'ipv_prod_queue';

        // Check if table exists
        $table_exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ) );

        if ( ! $table_exists && class_exists( 'IPV_Prod_Queue' ) ) {
            IPV_Prod_Queue::create_table();

            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[IPV Production] Auto-created missing queue table during upgrade to v' . IPV_PROD_VERSION );
            }
        }

        // Mark as checked for this version
        update_option( 'ipv_queue_table_checked', IPV_PROD_VERSION );
    }

    /**
     * AJAX: Start/Restart CRON
     */
    public function ajax_start_cron() {
        check_ajax_referer( 'ipv_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $timestamp = wp_next_scheduled( 'ipv_prod_process_queue' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'ipv_prod_process_queue' );
        }

        wp_schedule_event( time(), 'ipv_every_5_minutes', 'ipv_prod_process_queue' );
        
        if ( class_exists( 'IPV_Prod_Queue' ) ) {
            IPV_Prod_Queue::process_queue();
        }

        $next = wp_next_scheduled( 'ipv_prod_process_queue' );
        
        wp_send_json_success( [ 
            'message'  => __( 'CRON started!', 'ipv-production-system-pro' ),
            'next_run' => $next ? date_i18n( 'H:i:s', $next ) : 'N/A',
        ] );
    }

    /**
     * Activation
     */
    public function activate() {
        // v10.3.0 - Auto-installer (database tables, defaults, CRON, setup wizard)
        require_once IPV_PROD_PLUGIN_DIR . 'includes/class-auto-installer.php';
        IPV_Prod_Auto_Installer::install();

        // Legacy CRON setup (compatibility)
        if ( ! wp_next_scheduled( 'ipv_prod_process_queue' ) ) {
            wp_schedule_event( time(), 'ipv_every_5_minutes', 'ipv_prod_process_queue' );
        }

        if ( ! wp_next_scheduled( 'ipv_prod_update_youtube_data' ) ) {
            wp_schedule_event( time(), 'hourly', 'ipv_prod_update_youtube_data' );
        }

        // Legacy queue table (compatibility)
        if ( class_exists( 'IPV_Prod_Queue' ) ) {
            IPV_Prod_Queue::create_table();
        }

        flush_rewrite_rules();
    }

    /**
     * Deactivation
     */
    public function deactivate() {
        $timestamp = wp_next_scheduled( 'ipv_prod_process_queue' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'ipv_prod_process_queue' );
        }

        $timestamp = wp_next_scheduled( 'ipv_prod_update_youtube_data' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'ipv_prod_update_youtube_data' );
        }
    }
}

// ============================================
// INIT
// ============================================

add_action( 'plugins_loaded', function() {
    IPV_Production_System_Pro::get_instance();

    // Initialize Setup Wizard (load only in admin)
    if ( is_admin() ) {
        require_once IPV_PROD_PLUGIN_DIR . 'includes/class-setup-wizard.php';
        IPV_Prod_Setup_Wizard::init();

        // v10.3.1 - Credits UX Enhancements
        IPV_Prod_Credits_Widget::init();
        IPV_Prod_Credits_Notices::init();
    }
}, 10 );
