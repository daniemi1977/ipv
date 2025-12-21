<?php
/**
 * IPV Client Upgrade Page
 *
 * Mostra opzioni di upgrade/downgrade direttamente nel plugin client
 *
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Prod_Upgrade_Page {

    private static $instance = null;
    private $vendor_url = '';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->vendor_url = get_option( 'ipv_vendor_url', 'https://aiedintorni.it' );

        // Add submenu page
        add_action( 'admin_menu', [ $this, 'add_submenu_page' ], 20 );

        // AJAX handlers
        add_action( 'wp_ajax_ipv_get_upgrade_plans', [ $this, 'ajax_get_plans' ] );

        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /**
     * Add submenu page
     */
    public function add_submenu_page() {
        add_submenu_page(
            'ipv-production', // Parent menu
            __( 'Upgrade Piano', 'ipv-production-system-pro' ),
            __( '🚀 Upgrade Piano', 'ipv-production-system-pro' ),
            'manage_options',
            'ipv-upgrade',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts( $hook ) {
        if ( strpos( $hook, 'ipv-upgrade' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'ipv-upgrade-page',
            IPV_PROD_PLUGIN_URL . 'assets/css/upgrade-page.css',
            [],
            IPV_PROD_VERSION
        );

        wp_enqueue_script(
            'ipv-upgrade-page',
            IPV_PROD_PLUGIN_URL . 'assets/js/upgrade-page.js',
            [ 'jquery' ],
            IPV_PROD_VERSION,
            true
        );

        wp_localize_script( 'ipv-upgrade-page', 'ipv_upgrade', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'ipv_upgrade_nonce' ),
            'vendor_url' => $this->vendor_url,
            'i18n' => [
                'loading' => __( 'Caricamento piani...', 'ipv-production-system-pro' ),
                'error' => __( 'Errore nel caricamento dei piani', 'ipv-production-system-pro' ),
                'upgrade' => __( 'Upgrade a', 'ipv-production-system-pro' ),
                'downgrade' => __( 'Passa a', 'ipv-production-system-pro' )
            ]
        ]);
    }

    /**
     * Render page
     */
    public function render_page() {
        $license_key = get_option( 'ipv_license_key', '' );
        $license_data = get_option( 'ipv_license_data', [] );

        if ( empty( $license_key ) ) {
            $this->render_no_license();
            return;
        }

        $current_plan = $license_data['license']['variant'] ?? 'unknown';
        $wallet = $license_data['wallet'] ?? [];

        ?>
        <div class="wrap ipv-upgrade-wrap">
            <h1>
                <span class="dashicons dashicons-superhero"></span>
                <?php esc_html_e( 'Gestione Piano IPV Pro', 'ipv-production-system-pro' ); ?>
            </h1>

            <!-- Current Plan Summary -->
            <div class="ipv-current-plan-box">
                <div class="ipv-plan-info">
                    <span class="ipv-plan-label"><?php esc_html_e( 'Piano Attuale:', 'ipv-production-system-pro' ); ?></span>
                    <span class="ipv-plan-name"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $current_plan ) ) ); ?></span>
                </div>
                <div class="ipv-credits-info">
                    <span class="ipv-credits-value"><?php echo esc_html( $wallet['credits_remaining'] ?? 0 ); ?></span>
                    <span class="ipv-credits-label"><?php esc_html_e( 'crediti disponibili', 'ipv-production-system-pro' ); ?></span>
                </div>
                <div class="ipv-quick-actions">
                    <a href="<?php echo esc_url( $this->vendor_url . '/my-account/ipv-upgrade/' ); ?>" class="button" target="_blank">
                        <span class="dashicons dashicons-external"></span>
                        <?php esc_html_e( 'Gestisci su My Account', 'ipv-production-system-pro' ); ?>
                    </a>
                </div>
            </div>

            <!-- Plans Container -->
            <div id="ipv-plans-container" class="ipv-plans-loading">
                <div class="ipv-loading-spinner">
                    <span class="spinner is-active"></span>
                    <span><?php esc_html_e( 'Caricamento piani disponibili...', 'ipv-production-system-pro' ); ?></span>
                </div>
            </div>

            <!-- Direct Links -->
            <div class="ipv-upgrade-links">
                <h3><?php esc_html_e( 'Link Rapidi', 'ipv-production-system-pro' ); ?></h3>
                <div class="ipv-links-grid">
                    <a href="<?php echo esc_url( $this->vendor_url . '/my-account/ipv-wallet/' ); ?>" class="ipv-link-card" target="_blank">
                        <span class="dashicons dashicons-money-alt"></span>
                        <span class="ipv-link-title"><?php esc_html_e( 'Portafoglio Crediti', 'ipv-production-system-pro' ); ?></span>
                        <span class="ipv-link-desc"><?php esc_html_e( 'Storico e acquisto crediti extra', 'ipv-production-system-pro' ); ?></span>
                    </a>
                    <a href="<?php echo esc_url( $this->vendor_url . '/my-account/ipv-licenses/' ); ?>" class="ipv-link-card" target="_blank">
                        <span class="dashicons dashicons-admin-network"></span>
                        <span class="ipv-link-title"><?php esc_html_e( 'Le Tue Licenze', 'ipv-production-system-pro' ); ?></span>
                        <span class="ipv-link-desc"><?php esc_html_e( 'Gestisci licenze e attivazioni', 'ipv-production-system-pro' ); ?></span>
                    </a>
                    <a href="<?php echo esc_url( $this->vendor_url . '/ipv-pro/#pricing' ); ?>" class="ipv-link-card" target="_blank">
                        <span class="dashicons dashicons-cart"></span>
                        <span class="ipv-link-title"><?php esc_html_e( 'Acquista', 'ipv-production-system-pro' ); ?></span>
                        <span class="ipv-link-desc"><?php esc_html_e( 'Vedi tutti i piani disponibili', 'ipv-production-system-pro' ); ?></span>
                    </a>
                    <a href="<?php echo esc_url( $this->vendor_url . '/support/' ); ?>" class="ipv-link-card" target="_blank">
                        <span class="dashicons dashicons-editor-help"></span>
                        <span class="ipv-link-title"><?php esc_html_e( 'Supporto', 'ipv-production-system-pro' ); ?></span>
                        <span class="ipv-link-desc"><?php esc_html_e( 'Hai bisogno di aiuto?', 'ipv-production-system-pro' ); ?></span>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render no license message
     */
    private function render_no_license() {
        ?>
        <div class="wrap ipv-upgrade-wrap">
            <h1>
                <span class="dashicons dashicons-superhero"></span>
                <?php esc_html_e( 'Upgrade Piano IPV Pro', 'ipv-production-system-pro' ); ?>
            </h1>

            <div class="ipv-no-license-box">
                <span class="dashicons dashicons-warning"></span>
                <h2><?php esc_html_e( 'Licenza Non Configurata', 'ipv-production-system-pro' ); ?></h2>
                <p><?php esc_html_e( 'Per gestire il tuo piano, devi prima configurare una license key valida.', 'ipv-production-system-pro' ); ?></p>
                <div class="ipv-actions">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-settings' ) ); ?>" class="button button-primary">
                        <?php esc_html_e( 'Configura Licenza', 'ipv-production-system-pro' ); ?>
                    </a>
                    <a href="<?php echo esc_url( $this->vendor_url . '/ipv-pro/' ); ?>" class="button" target="_blank">
                        <?php esc_html_e( 'Acquista Licenza', 'ipv-production-system-pro' ); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Get upgrade plans from vendor
     */
    public function ajax_get_plans() {
        check_ajax_referer( 'ipv_upgrade_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $license_key = get_option( 'ipv_license_key', '' );

        if ( empty( $license_key ) ) {
            wp_send_json_error( [ 'message' => 'Licenza non configurata' ] );
        }

        // Call vendor API
        $api_url = trailingslashit( $this->vendor_url ) . 'wp-json/ipv-vendor/v1/upgrade/plans';

        $response = wp_remote_get( $api_url, [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $license_key
            ]
        ]);

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( [ 'message' => $response->get_error_message() ] );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['success'] ) ) {
            wp_send_json_error( [ 'message' => 'Errore API vendor' ] );
        }

        // Add vendor URL for checkout links
        $body['vendor_url'] = $this->vendor_url;

        wp_send_json_success( $body );
    }
}

// Initialize
IPV_Prod_Upgrade_Page::instance();
