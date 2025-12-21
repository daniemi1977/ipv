<?php
/**
 * IPV Landing Page Import Wizard
 *
 * Wizard per importazione automatica landing page
 *
 * @package IPV_Pro_Vendor
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Landing_Import_Wizard {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ], 99 );
        add_action( 'admin_post_ipv_import_landing', [ $this, 'handle_import' ] );
        add_action( 'admin_notices', [ $this, 'show_setup_notice' ] );
        add_action( 'wp_ajax_ipv_dismiss_landing_notice', [ $this, 'dismiss_notice' ] );
    }

    /**
     * Show setup notice if pages not imported
     */
    public function show_setup_notice() {
        if ( get_option( 'ipv_landing_notice_dismissed' ) ) {
            return;
        }

        $imported = get_option( 'ipv_landing_pages_imported', [] );
        if ( ! empty( $imported ) ) {
            return;
        }

        $screen = get_current_screen();
        if ( strpos( $screen->id, 'ipv' ) === false ) {
            return;
        }
        ?>
        <div class="notice notice-info is-dismissible" id="ipv-landing-notice">
            <p>
                <strong><?php esc_html_e( 'IPV Pro:', 'ipv-pro-vendor' ); ?></strong>
                <?php esc_html_e( 'Vuoi importare le landing page pronte all\'uso?', 'ipv-pro-vendor' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-landing-wizard' ) ); ?>" class="button button-primary" style="margin-left: 10px;">
                    <?php esc_html_e( 'Importa Ora', 'ipv-pro-vendor' ); ?>
                </a>
            </p>
        </div>
        <script>
        jQuery(document).on('click', '#ipv-landing-notice .notice-dismiss', function() {
            jQuery.post(ajaxurl, { action: 'ipv_dismiss_landing_notice' });
        });
        </script>
        <?php
    }

    /**
     * Dismiss notice
     */
    public function dismiss_notice() {
        update_option( 'ipv_landing_notice_dismissed', true );
        wp_die();
    }

    /**
     * Aggiunge pagina menu
     */
    public function add_menu_page() {
        add_submenu_page(
            'ipv-vendor',
            __( 'Landing Page', 'ipv-pro-vendor' ),
            __( '🚀 Landing Page', 'ipv-pro-vendor' ),
            'manage_options',
            'ipv-landing-wizard',
            [ $this, 'render_wizard_page' ]
        );
    }

    /**
     * Render wizard page
     */
    public function render_wizard_page() {
        ?>
        <style>
            .ipv-wizard-wrap { max-width: 900px; margin: 20px auto; }
            .ipv-wizard-header { background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 100%); color: #fff; padding: 30px; border-radius: 12px 12px 0 0; }
            .ipv-wizard-header h1 { margin: 0 0 10px; font-size: 28px; }
            .ipv-wizard-header p { margin: 0; opacity: 0.9; font-size: 16px; }
            .ipv-wizard-body { background: #fff; padding: 30px; border: 1px solid #ddd; border-top: 0; border-radius: 0 0 12px 12px; }
            .ipv-page-card { display: flex; align-items: center; padding: 20px; border: 2px solid #e5e7eb; border-radius: 8px; margin-bottom: 15px; transition: all 0.2s; }
            .ipv-page-card:hover { border-color: #8B5CF6; }
            .ipv-page-card.selected { border-color: #8B5CF6; background: #faf5ff; }
            .ipv-page-card input[type="checkbox"] { width: 20px; height: 20px; margin-right: 15px; }
            .ipv-page-card-content { flex: 1; }
            .ipv-page-card-title { font-weight: 600; font-size: 16px; margin-bottom: 5px; }
            .ipv-page-card-desc { color: #6b7280; font-size: 14px; }
            .ipv-page-card-slug { background: #f3f4f6; padding: 2px 8px; border-radius: 4px; font-family: monospace; font-size: 12px; }
            .ipv-page-card-status { margin-left: 15px; }
            .ipv-status-imported { color: #10b981; }
            .ipv-status-pending { color: #9ca3af; }
            .ipv-options { background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .ipv-options label { display: flex; align-items: center; margin-bottom: 10px; cursor: pointer; }
            .ipv-options input[type="checkbox"] { margin-right: 10px; }
            .ipv-btn-import { background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 100%); color: #fff; border: 0; padding: 15px 40px; font-size: 16px; font-weight: 600; border-radius: 8px; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
            .ipv-btn-import:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(139, 92, 246, 0.3); }
            .ipv-shortcodes { margin-top: 30px; }
            .ipv-shortcodes table { width: 100%; border-collapse: collapse; }
            .ipv-shortcodes th, .ipv-shortcodes td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
            .ipv-shortcodes th { background: #f9fafb; font-weight: 600; }
            .ipv-shortcodes code { background: #1e1b4b; color: #a5b4fc; padding: 4px 8px; border-radius: 4px; }
        </style>

        <div class="wrap">
            <div class="ipv-wizard-wrap">
                <div class="ipv-wizard-header">
                    <h1>🚀 <?php esc_html_e( 'Importa Landing Page', 'ipv-pro-vendor' ); ?></h1>
                    <p><?php esc_html_e( 'Crea le tue pagine di vendita in un click. Design professionale, pronto all\'uso.', 'ipv-pro-vendor' ); ?></p>
                </div>

                <div class="ipv-wizard-body">
                    <?php if ( isset( $_GET['imported'] ) ) : ?>
                    <div class="notice notice-success inline" style="margin: 0 0 20px;">
                        <p><strong>✅ <?php esc_html_e( 'Landing page importate con successo!', 'ipv-pro-vendor' ); ?></strong></p>
                    </div>
                    <?php endif; ?>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( 'ipv_import_landing', 'ipv_nonce' ); ?>
                        <input type="hidden" name="action" value="ipv_import_landing">

                        <h3><?php esc_html_e( 'Seleziona le pagine da importare', 'ipv-pro-vendor' ); ?></h3>

                        <?php
                        $pages_data = $this->get_pages_data();
                        foreach ( $pages_data as $slug => $data ) :
                            $existing = get_page_by_path( $slug );
                            $is_imported = (bool) $existing;
                        ?>
                        <div class="ipv-page-card <?php echo $is_imported ? '' : 'selected'; ?>">
                            <input type="checkbox" name="pages[]" value="<?php echo esc_attr( $slug ); ?>" <?php echo $is_imported ? '' : 'checked'; ?>>
                            <div class="ipv-page-card-content">
                                <div class="ipv-page-card-title"><?php echo esc_html( $data['title'] ); ?></div>
                                <div class="ipv-page-card-desc">
                                    <?php echo esc_html( $data['description'] ); ?>
                                    <span class="ipv-page-card-slug">/<?php echo esc_html( $slug ); ?>/</span>
                                </div>
                            </div>
                            <div class="ipv-page-card-status">
                                <?php if ( $is_imported ) : ?>
                                    <span class="ipv-status-imported">✓ Importata</span>
                                    <br><small><a href="<?php echo esc_url( get_edit_post_link( $existing->ID ) ); ?>">Modifica</a> | <a href="<?php echo esc_url( get_permalink( $existing->ID ) ); ?>" target="_blank">Vedi</a></small>
                                <?php else : ?>
                                    <span class="ipv-status-pending">○ Non importata</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="ipv-options">
                            <label>
                                <input type="checkbox" name="set_homepage" value="1">
                                <strong><?php esc_html_e( 'Imposta "IPV Pro" come Homepage del sito', 'ipv-pro-vendor' ); ?></strong>
                            </label>
                            <label>
                                <input type="checkbox" name="overwrite" value="1">
                                <?php esc_html_e( 'Sovrascrivi pagine esistenti con lo stesso slug', 'ipv-pro-vendor' ); ?>
                            </label>
                        </div>

                        <p style="text-align: center;">
                            <button type="submit" class="ipv-btn-import">
                                <?php esc_html_e( 'Importa Pagine Selezionate', 'ipv-pro-vendor' ); ?> →
                            </button>
                        </p>
                    </form>

                    <div class="ipv-shortcodes">
                        <h3><?php esc_html_e( 'Shortcode Disponibili', 'ipv-pro-vendor' ); ?></h3>
                        <p><?php esc_html_e( 'Puoi usare questi shortcode per creare pagine personalizzate:', 'ipv-pro-vendor' ); ?></p>
                        <table>
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Shortcode', 'ipv-pro-vendor' ); ?></th>
                                    <th><?php esc_html_e( 'Descrizione', 'ipv-pro-vendor' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>[ipv_hero]</code></td>
                                    <td><?php esc_html_e( 'Sezione Hero con titolo, sottotitolo e pulsanti CTA', 'ipv-pro-vendor' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code>[ipv_features]</code></td>
                                    <td><?php esc_html_e( 'Griglia funzionalità con icone', 'ipv-pro-vendor' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code>[ipv_how_it_works]</code></td>
                                    <td><?php esc_html_e( 'I 3 step del processo', 'ipv-pro-vendor' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code>[ipv_pricing]</code></td>
                                    <td><?php esc_html_e( 'Tabella prezzi dinamica (legge i piani da WooCommerce)', 'ipv-pro-vendor' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code>[ipv_cta]</code></td>
                                    <td><?php esc_html_e( 'Sezione Call to Action finale', 'ipv-pro-vendor' ); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.ipv-page-card input[type="checkbox"]').on('change', function() {
                $(this).closest('.ipv-page-card').toggleClass('selected', this.checked);
            });
        });
        </script>
        <?php
    }

    /**
     * Handle import
     */
    public function handle_import() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Permessi insufficienti', 'ipv-pro-vendor' ) );
        }

        if ( ! wp_verify_nonce( $_POST['ipv_nonce'], 'ipv_import_landing' ) ) {
            wp_die( __( 'Nonce non valido', 'ipv-pro-vendor' ) );
        }

        $pages_to_import = isset( $_POST['pages'] ) ? array_map( 'sanitize_text_field', $_POST['pages'] ) : [];
        $set_homepage = ! empty( $_POST['set_homepage'] );
        $overwrite = ! empty( $_POST['overwrite'] );

        if ( empty( $pages_to_import ) ) {
            wp_redirect( admin_url( 'admin.php?page=ipv-landing-wizard&error=no_pages' ) );
            exit;
        }

        $imported = [];
        $landing_page_id = null;

        foreach ( $pages_to_import as $slug ) {
            $result = $this->import_page( $slug, $overwrite );
            if ( $result ) {
                $imported[] = $slug;
                if ( $slug === 'ipv-pro' ) {
                    $landing_page_id = $result;
                }
            }
        }

        if ( $set_homepage && $landing_page_id ) {
            update_option( 'show_on_front', 'page' );
            update_option( 'page_on_front', $landing_page_id );
        }

        update_option( 'ipv_landing_pages_imported', $imported );
        update_option( 'ipv_landing_notice_dismissed', true );

        wp_redirect( admin_url( 'admin.php?page=ipv-landing-wizard&imported=1' ) );
        exit;
    }

    /**
     * Import single page
     */
    private function import_page( $slug, $overwrite = false ) {
        $pages_data = $this->get_pages_data();

        if ( ! isset( $pages_data[ $slug ] ) ) {
            return false;
        }

        $data = $pages_data[ $slug ];
        $existing = get_page_by_path( $slug );

        if ( $existing && ! $overwrite ) {
            return $existing->ID;
        }

        $page_data = [
            'post_title'     => $data['title'],
            'post_name'      => $slug,
            'post_content'   => $data['content'],
            'post_excerpt'   => $data['excerpt'] ?? '',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'post_author'    => get_current_user_id(),
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
        ];

        if ( $existing && $overwrite ) {
            $page_data['ID'] = $existing->ID;
            return wp_update_post( $page_data );
        }

        return wp_insert_post( $page_data );
    }

    /**
     * Get pages data
     */
    private function get_pages_data() {
        return [
            'ipv-pro' => [
                'title' => 'IPV Pro - Automatizza i tuoi Video',
                'description' => 'Landing page completa con Hero, Features, How It Works, Pricing e CTA',
                'content' => '<!-- Hero Section -->
[ipv_hero title="Automatizza i tuoi Video YouTube con l\'AI" subtitle="Trascrizioni automatiche, descrizioni SEO ottimizzate e molto altro. Risparmia ore di lavoro con IPV Pro." button_text="Inizia Gratis" button_url="#pricing" button2_text="Scopri di più" button2_url="#features"]

<!-- Features Section -->
[ipv_features title="Le Nostre Soluzioni" subtitle="FUNZIONALITÀ"]

<!-- How It Works Section -->
[ipv_how_it_works title="Come Funziona" subtitle="PROCESSO"]

<!-- Pricing Section -->
<div id="pricing">
[ipv_pricing title="Prezzi Trasparenti" subtitle="PRICING"]
</div>

<!-- CTA Section -->
[ipv_cta title="Pronto a Risparmiare Tempo?" subtitle="Inizia oggi con la prova gratuita. Nessuna carta di credito richiesta." button_text="Inizia Gratis" button_url="#pricing"]',
                'excerpt' => 'Automatizza la produzione di contenuti video con l\'intelligenza artificiale.',
            ],
            'prezzi' => [
                'title' => 'Prezzi',
                'description' => 'Pagina con tabella prezzi e CTA',
                'content' => '[ipv_pricing title="Scegli il Piano Perfetto per Te" subtitle="PREZZI"]

[ipv_cta title="Hai Domande?" subtitle="Contattaci per una consulenza gratuita." button_text="Contattaci" button_url="/contatti/"]',
                'excerpt' => 'Scopri i piani e i prezzi di IPV Pro.',
            ],
            'come-funziona' => [
                'title' => 'Come Funziona',
                'description' => 'Pagina esplicativa del processo',
                'content' => '[ipv_how_it_works title="Come Funziona IPV Pro" subtitle="IL PROCESSO"]

[ipv_features title="Funzionalità Principali" subtitle="CARATTERISTICHE"]

[ipv_cta title="Prova IPV Pro Gratuitamente" subtitle="Inizia subito con il piano Trial gratuito." button_text="Inizia Ora" button_url="/prezzi/"]',
                'excerpt' => 'Scopri come funziona IPV Pro in 3 semplici passaggi.',
            ],
        ];
    }
}

// Initialize
IPV_Vendor_Landing_Import_Wizard::instance();
