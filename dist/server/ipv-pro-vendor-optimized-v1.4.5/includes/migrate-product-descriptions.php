<?php
/**
 * IPV Pro Vendor - Migration Script: Product Descriptions
 *
 * Aggiorna le descrizioni dei prodotti esistenti con formato pulito
 * per risolvere il problema del testo bianco su sfondo bianco
 *
 * v1.4.2-FIXED9 - Descrizioni senza emoji/caratteri speciali
 *
 * @package IPV_Pro_Vendor
 * @version 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Vendor_Migrate_Descriptions {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu_page' ], 20 );
        add_action( 'admin_post_ipv_migrate_descriptions', [ __CLASS__, 'handle_migration' ] );
    }

    public static function add_menu_page() {
        add_submenu_page(
            'ipv-vendor-dashboard', // Fixed: correct parent menu
            'Migrazione Descrizioni',
            'Migra Descrizioni',
            'manage_options',
            'ipv-migrate-descriptions',
            [ __CLASS__, 'render_page' ]
        );
    }

    /**
     * Generate clean description based on plan data
     * v1.4.2-FIXED10 - Gestisce Trial, Extra Credits e Subscription
     */
    private static function generate_clean_description( $plan_slug, $plan_data ) {
        $is_once = ($plan_data['credits_period'] === 'once');
        $is_extra_credits = ($plan_slug === 'extra_credits');
        $is_trial = ($plan_slug === 'trial');
        
        // === TRIAL ===
        if ($is_trial) {
            $short = 'Piano Trial gratuito - 10 crediti di benvenuto. Non scade mai.';
            
            $long = '<h3>IPV Production System Pro - Piano Trial</h3>';
            $long .= '<p>Prova gratuita per testare tutte le funzionalita del sistema.</p>';
            $long .= '<h4>Cosa Include:</h4>';
            $long .= '<ul>';
            $long .= '<li><strong>10 crediti di benvenuto</strong> (una tantum, non si rinnovano)</li>';
            $long .= '<li><strong>Non scade mai</strong> - usa i crediti quando vuoi</li>';
            $long .= '<li><strong>1 sito</strong> WordPress attivabile</li>';
            $long .= '<li>Trascrizioni automatiche incluse</li>';
            $long .= '<li>Descrizioni AI automatiche</li>';
            $long .= '</ul>';
            $long .= '<h4>Dopo i 10 crediti:</h4>';
            $long .= '<p>Puoi acquistare <strong>Crediti Extra</strong> a pacchetti di 10 (0,50 EUR/credito) oppure passare a un piano con crediti mensili.</p>';
            $long .= '<p><strong>Prezzo:</strong> Gratuito</p>';
            
            return ['short' => $short, 'long' => $long];
        }
        
        // === EXTRA CREDITS ===
        if ($is_extra_credits) {
            $short = 'Pacchetto 10 crediti extra - 0,50 EUR/credito. Non scadono mai.';
            
            $long = '<h3>IPV Production System Pro - Crediti Extra</h3>';
            $long .= '<p>Acquista crediti aggiuntivi per il tuo account IPV Pro.</p>';
            $long .= '<h4>Dettagli:</h4>';
            $long .= '<ul>';
            $long .= '<li><strong>10 crediti</strong> per pacchetto</li>';
            $long .= '<li><strong>0,50 EUR</strong> per credito</li>';
            $long .= '<li><strong>Non scadono mai</strong> - usali quando vuoi</li>';
            $long .= '<li>Si sommano ai crediti del tuo piano attivo</li>';
            $long .= '<li>Ideale per picchi di utilizzo occasionali</li>';
            $long .= '</ul>';
            $long .= '<p><strong>Prezzo:</strong> 5,00 EUR (10 crediti)</p>';
            
            return ['short' => $short, 'long' => $long];
        }
        
        // === SUBSCRIPTION PLANS (Starter, Professional, Business) ===
        $period_label = ($plan_data['credits_period'] === 'year') ? 'anno' : 'mese';
        $credits_label = $plan_data['credits'] . ' crediti/' . $period_label;
        
        $short = 'Piano ' . $plan_data['name'] . ' - ' . $credits_label . '. ' . $plan_data['description'];

        $long = '<h3>IPV Production System Pro - Piano ' . esc_html($plan_data['name']) . '</h3>';
        $long .= '<p>' . esc_html($plan_data['description']) . '</p>';
        
        $long .= '<h4>Cosa Include:</h4>';
        $long .= '<ul>';
        $long .= '<li><strong>' . intval($plan_data['credits']) . ' crediti/' . $period_label . '</strong> (si rinnovano automaticamente)</li>';
        $long .= '<li><strong>' . intval($plan_data['activations']) . ' sito/i</strong> WordPress attivabili</li>';

        if (!empty($plan_data['features']['transcription'])) {
            $long .= '<li>Trascrizioni automatiche incluse</li>';
        }
        if (!empty($plan_data['features']['ai_description'])) {
            $long .= '<li>Descrizioni AI automatiche</li>';
        }
        if (!empty($plan_data['features']['priority_support'])) {
            $long .= '<li>Supporto prioritario</li>';
        }
        if (!empty($plan_data['features']['api_access'])) {
            $long .= '<li>Accesso API completo</li>';
        }
        $long .= '</ul>';

        $long .= '<h4>Funzionalita Principali:</h4>';
        $long .= '<ul>';
        $long .= '<li>Trascrizione automatica video YouTube</li>';
        $long .= '<li>Generazione descrizioni con AI (GPT-4)</li>';
        $long .= '<li>Download automatico thumbnail HD</li>';
        $long .= '<li>Import singolo e massivo</li>';
        $long .= '<li>Video Wall personalizzabile</li>';
        $long .= '<li>Dashboard analytics completa</li>';
        $long .= '</ul>';

        // Price info
        $price_period_label = ($plan_data['price_period'] === 'year') ? 'anno' : 'mese';
        $price_info = number_format($plan_data['price'], 2, ',', '.') . ' EUR/' . $price_period_label;
        $long .= '<p><strong>Prezzo:</strong> ' . $price_info . '</p>';

        return [
            'short' => $short,
            'long' => $long
        ];
    }

    /**
     * Migrate all products
     */
    public static function migrate_all_products() {
        $results = [
            'updated' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        // Get plans from Plans Manager
        if ( class_exists( 'IPV_Vendor_Plans_Manager' ) ) {
            $plans_manager = IPV_Vendor_Plans_Manager::instance();
            $plans = $plans_manager->get_plans();
        } else {
            $plans = get_option( 'ipv_saas_plans', [] );
        }

        if ( empty( $plans ) ) {
            $results['errors'][] = 'Nessun piano trovato nel database';
            return $results;
        }

        // Get all IPV products
        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => '_ipv_is_license_product',
                    'value' => 'yes',
                    'compare' => '='
                ]
            ]
        ];

        $products = get_posts( $args );

        foreach ( $products as $post ) {
            $product = wc_get_product( $post->ID );
            if ( ! $product ) {
                $results['skipped']++;
                continue;
            }

            // Get plan slug from meta
            $plan_slug = $product->get_meta( '_ipv_plan_slug' );
            if ( empty( $plan_slug ) ) {
                $plan_slug = $product->get_meta( '_ipv_variant_slug' );
            }

            if ( empty( $plan_slug ) || ! isset( $plans[$plan_slug] ) ) {
                $results['errors'][] = sprintf( 
                    'Piano non trovato per prodotto ID %d (slug: %s)', 
                    $post->ID, 
                    $plan_slug ?: 'N/A' 
                );
                $results['skipped']++;
                continue;
            }

            // Generate clean descriptions
            $desc = self::generate_clean_description( $plan_slug, $plans[$plan_slug] );

            // Update product descriptions
            $product->set_short_description( $desc['short'] );
            $product->set_description( $desc['long'] );

            try {
                $product->save();
                $results['updated']++;
            } catch ( Exception $e ) {
                $results['errors'][] = sprintf( 
                    'Errore aggiornamento prodotto %d: %s', 
                    $post->ID, 
                    $e->getMessage() 
                );
            }
        }

        return $results;
    }

    /**
     * Handle migration POST request
     */
    public static function handle_migration() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        check_admin_referer( 'ipv_migrate_descriptions' );

        $results = self::migrate_all_products();

        $redirect_url = add_query_arg( [
            'page' => 'ipv-migrate-descriptions',
            'migrated' => 1,
            'updated' => $results['updated'],
            'skipped' => $results['skipped'],
            'errors' => count( $results['errors'] )
        ], admin_url( 'admin.php' ) );

        // Store errors in transient
        if ( ! empty( $results['errors'] ) ) {
            set_transient( 'ipv_migration_errors', $results['errors'], 300 );
        }

        wp_redirect( $redirect_url );
        exit;
    }

    /**
     * Render migration page
     */
    public static function render_page() {
        // Count products to migrate
        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => '_ipv_is_license_product',
                    'value' => 'yes',
                    'compare' => '='
                ]
            ]
        ];

        $product_count = count( get_posts( $args ) );

        ?>
        <div class="wrap">
            <h1>Migrazione Descrizioni Prodotti</h1>

            <?php if ( isset( $_GET['migrated'] ) ) : ?>
                <div class="notice notice-success">
                    <p>
                        <strong>Migrazione completata!</strong><br>
                        Prodotti aggiornati: <?php echo intval( $_GET['updated'] ); ?><br>
                        Prodotti saltati: <?php echo intval( $_GET['skipped'] ); ?><br>
                        Errori: <?php echo intval( $_GET['errors'] ); ?>
                    </p>
                </div>

                <?php
                $errors = get_transient( 'ipv_migration_errors' );
                if ( $errors ) :
                    delete_transient( 'ipv_migration_errors' );
                    ?>
                    <div class="notice notice-error">
                        <p><strong>Errori riscontrati:</strong></p>
                        <ul>
                            <?php foreach ( $errors as $error ) : ?>
                                <li><?php echo esc_html( $error ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; max-width: 800px;">
                <h2>Informazioni</h2>
                <p>
                    Questo script aggiorna le descrizioni di tutti i prodotti IPV Pro esistenti
                    con un formato HTML pulito <strong>senza emoji o caratteri speciali</strong>,
                    risolvendo il problema del testo bianco su sfondo bianco nell'editor WooCommerce.
                </p>

                <h3>Cosa fa lo script:</h3>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li>Trova tutti i prodotti con <code>_ipv_is_license_product</code> = yes</li>
                    <li>Legge la configurazione del piano dal database</li>
                    <li>Genera descrizioni HTML pulite senza emoji</li>
                    <li>Aggiorna sia short_description che description</li>
                </ul>

                <h3>Prodotti da migrare:</h3>
                <p style="font-size: 18px; font-weight: bold; color: #2271b1;">
                    <?php echo $product_count; ?> prodotti trovati
                </p>

                <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;">
                    <p style="margin: 0;">
                        <strong>Attenzione:</strong> Questa operazione modifichera permanentemente
                        le descrizioni dei prodotti. Si consiglia di fare un backup del database prima di procedere.
                    </p>
                </div>

                <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                    <?php wp_nonce_field( 'ipv_migrate_descriptions' ); ?>
                    <input type="hidden" name="action" value="ipv_migrate_descriptions">

                    <p>
                        <button type="submit" class="button button-primary button-hero"
                                onclick="return confirm('Sei sicuro di voler aggiornare le descrizioni di <?php echo $product_count; ?> prodotti?');">
                            Aggiorna Descrizioni
                        </button>
                    </p>
                </form>

                <hr style="margin: 30px 0;">

                <h3>Dopo la migrazione:</h3>
                <ol>
                    <li>Verifica che le descrizioni siano leggibili nell'editor WooCommerce</li>
                    <li>Controlla che i prodotti siano visualizzati correttamente nel frontend</li>
                    <li>Se tutto funziona, puoi eliminare questo file per sicurezza</li>
                </ol>
            </div>
        </div>

        <style>
            .wrap h2 { margin-top: 20px; }
            .wrap h3 { margin-top: 15px; }
            .wrap ul, .wrap ol { margin: 10px 0; }
            .wrap code { background: #f0f0f1; padding: 2px 5px; border-radius: 3px; }
        </style>
        <?php
    }
}

// Initialize
IPV_Vendor_Migrate_Descriptions::init();
