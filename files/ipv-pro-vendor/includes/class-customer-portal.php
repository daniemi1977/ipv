<?php
/**
 * IPV Customer Portal
 *
 * Gestisce integrazione My Account WooCommerce per clienti IPV Pro
 *
 * v1.1.0 - Aggiunto sistema Wallet/Portafoglio crediti
 * v1.0.5 - Fix ricerca licenze anche per email
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Customer_Portal {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Add custom endpoint
        add_action( 'init', [ $this, 'add_endpoints' ] );

        // Add menu items
        add_filter( 'woocommerce_account_menu_items', [ $this, 'add_menu_items' ] );

        // Add content for endpoints
        add_action( 'woocommerce_account_ipv-licenses_endpoint', [ $this, 'licenses_content' ] );
        add_action( 'woocommerce_account_ipv-wallet_endpoint', [ $this, 'wallet_content' ] );

        // Dashboard widget
        add_action( 'woocommerce_account_dashboard', [ $this, 'dashboard_widget' ] );

        // Enqueue styles
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );

        // Claim license AJAX
        add_action( 'wp_ajax_ipv_claim_license', [ $this, 'ajax_claim_license' ] );

        // Flush rewrite rules on activation
        register_activation_hook( IPV_VENDOR_FILE, [ $this, 'flush_rewrite_rules' ] );
    }

    /**
     * Add custom endpoints
     */
    public function add_endpoints() {
        add_rewrite_endpoint( 'ipv-licenses', EP_ROOT | EP_PAGES );
        add_rewrite_endpoint( 'ipv-wallet', EP_ROOT | EP_PAGES );
    }

    /**
     * Flush rewrite rules
     */
    public function flush_rewrite_rules() {
        $this->add_endpoints();
        flush_rewrite_rules();
    }

    /**
     * Add menu items to My Account
     */
    public function add_menu_items( $items ) {
        $new_items = [];

        foreach ( $items as $key => $value ) {
            $new_items[ $key ] = $value;

            // Add after dashboard
            if ( $key === 'dashboard' ) {
                $new_items['ipv-licenses'] = __( 'Licenze IPV Pro', 'ipv-pro-vendor' );
                $new_items['ipv-wallet'] = __( 'Portafoglio Crediti', 'ipv-pro-vendor' );
            }
        }

        return $new_items;
    }

    /**
     * Get licenses for current user
     * v1.0.5 - Cerca anche per email se non trova per user_id
     */
    private function get_user_licenses( $user_id = null ) {
        global $wpdb;

        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        if ( ! $user_id ) {
            return [];
        }

        $user = get_userdata( $user_id );
        $user_email = $user ? $user->user_email : '';

        // Cerca licenze per user_id O per email
        $licenses = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses
            WHERE user_id = %d
            OR (user_id = 0 AND email = %s)
            ORDER BY created_at DESC",
            $user_id,
            $user_email
        ) );

        // Auto-associa licenze trovate per email all'user_id
        foreach ( $licenses as $license ) {
            if ( $license->user_id == 0 && $license->email === $user_email ) {
                $wpdb->update(
                    $wpdb->prefix . 'ipv_licenses',
                    [ 'user_id' => $user_id ],
                    [ 'id' => $license->id ],
                    [ '%d' ],
                    [ '%d' ]
                );
                $license->user_id = $user_id;
            }
        }

        return $licenses;
    }

    /**
     * Get total wallet balance for user
     */
    private function get_wallet_balance( $user_id = null ) {
        $licenses = $this->get_user_licenses( $user_id );

        $total_credits = 0;
        $total_remaining = 0;

        foreach ( $licenses as $license ) {
            if ( $license->status === 'active' ) {
                $total_credits += (int) $license->credits_total;
                $total_remaining += (int) $license->credits_remaining;
            }
        }

        return [
            'total_credits' => $total_credits,
            'credits_remaining' => $total_remaining,
            'credits_used' => $total_credits - $total_remaining,
            'percentage' => $total_credits > 0 ? round( ( $total_remaining / $total_credits ) * 100 ) : 0
        ];
    }

    /**
     * Get credit ledger for user
     */
    private function get_credit_ledger( $user_id = null, $limit = 50 ) {
        global $wpdb;

        $licenses = $this->get_user_licenses( $user_id );

        if ( empty( $licenses ) ) {
            return [];
        }

        $license_keys = array_column( $licenses, 'license_key' );
        $placeholders = implode( ',', array_fill( 0, count( $license_keys ), '%s' ) );

        $ledger = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_credit_ledger
            WHERE license_key IN ($placeholders)
            ORDER BY created_at DESC
            LIMIT %d",
            array_merge( $license_keys, [ $limit ] )
        ) );

        return $ledger;
    }

    /**
     * Enqueue styles
     */
    public function enqueue_styles() {
        if ( ! is_account_page() ) {
            return;
        }

        wp_enqueue_style(
            'ipv-customer-portal',
            IPV_VENDOR_URL . 'assets/css/customer-portal.css',
            [],
            IPV_VENDOR_VERSION
        );

        wp_enqueue_script(
            'ipv-customer-portal',
            IPV_VENDOR_URL . 'assets/js/customer-portal.js',
            [ 'jquery' ],
            IPV_VENDOR_VERSION,
            true
        );

        wp_localize_script( 'ipv-customer-portal', 'ipv_portal', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'ipv_portal_nonce' ),
            'i18n' => [
                'claiming' => __( 'Associazione in corso...', 'ipv-pro-vendor' ),
                'claimed' => __( 'Licenza associata!', 'ipv-pro-vendor' ),
                'error' => __( 'Errore durante l\'associazione', 'ipv-pro-vendor' ),
            ]
        ] );
    }

    /**
     * Dashboard widget
     */
    public function dashboard_widget() {
        $licenses = $this->get_user_licenses();
        $wallet = $this->get_wallet_balance();
        $active_licenses = array_filter( $licenses, fn( $l ) => $l->status === 'active' );

        if ( empty( $licenses ) ) {
            ?>
            <div class="ipv-dashboard-widget ipv-no-license">
                <h3><span class="dashicons dashicons-admin-network"></span> IPV Production System</h3>
                <p><?php esc_html_e( 'Non hai ancora una licenza IPV Pro.', 'ipv-pro-vendor' ); ?></p>
                <a href="<?php echo esc_url( home_url( '/ipv-pro/' ) ); ?>" class="button">
                    <?php esc_html_e( 'Acquista Ora', 'ipv-pro-vendor' ); ?>
                </a>

                <!-- Claim License Form -->
                <div class="ipv-claim-license-form" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <p><strong><?php esc_html_e( 'Hai già una licenza?', 'ipv-pro-vendor' ); ?></strong></p>
                    <form id="ipv-claim-license-form">
                        <input type="text" name="license_key" placeholder="<?php esc_attr_e( 'Inserisci la tua license key', 'ipv-pro-vendor' ); ?>" required />
                        <button type="submit" class="button"><?php esc_html_e( 'Associa Licenza', 'ipv-pro-vendor' ); ?></button>
                    </form>
                    <p class="ipv-claim-result"></p>
                </div>
            </div>
            <?php
            return;
        }

        ?>
        <div class="ipv-dashboard-widget">
            <h3><span class="dashicons dashicons-admin-network"></span> IPV Production System</h3>

            <div class="ipv-stats-grid">
                <div class="ipv-stat">
                    <span class="ipv-stat-value"><?php echo count( $active_licenses ); ?></span>
                    <span class="ipv-stat-label"><?php esc_html_e( 'Licenze Attive', 'ipv-pro-vendor' ); ?></span>
                </div>
                <div class="ipv-stat">
                    <span class="ipv-stat-value"><?php echo esc_html( $wallet['credits_remaining'] ); ?></span>
                    <span class="ipv-stat-label"><?php esc_html_e( 'Crediti Disponibili', 'ipv-pro-vendor' ); ?></span>
                </div>
            </div>

            <div class="ipv-widget-actions">
                <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'ipv-licenses' ) ); ?>" class="button">
                    <?php esc_html_e( 'Gestisci Licenze', 'ipv-pro-vendor' ); ?>
                </a>
                <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'ipv-wallet' ) ); ?>" class="button">
                    <?php esc_html_e( 'Portafoglio', 'ipv-pro-vendor' ); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Licenses page content
     */
    public function licenses_content() {
        $licenses = $this->get_user_licenses();
        ?>
        <div class="ipv-licenses-page">
            <h2><?php esc_html_e( 'Le Tue Licenze IPV Pro', 'ipv-pro-vendor' ); ?></h2>

            <?php if ( empty( $licenses ) ) : ?>
                <div class="ipv-no-licenses">
                    <p><?php esc_html_e( 'Non hai ancora licenze IPV Pro associate al tuo account.', 'ipv-pro-vendor' ); ?></p>

                    <!-- Claim License Form -->
                    <div class="ipv-claim-license-section">
                        <h3><?php esc_html_e( 'Hai già una licenza?', 'ipv-pro-vendor' ); ?></h3>
                        <p><?php esc_html_e( 'Se hai acquistato una licenza con un\'altra email, puoi associarla qui.', 'ipv-pro-vendor' ); ?></p>
                        <form id="ipv-claim-license-form" class="ipv-claim-form">
                            <input type="text" name="license_key" placeholder="<?php esc_attr_e( 'Inserisci la tua license key', 'ipv-pro-vendor' ); ?>" required />
                            <button type="submit" class="button button-primary"><?php esc_html_e( 'Associa Licenza', 'ipv-pro-vendor' ); ?></button>
                        </form>
                        <p class="ipv-claim-result"></p>
                    </div>

                    <p style="margin-top: 30px;">
                        <a href="<?php echo esc_url( home_url( '/ipv-pro/' ) ); ?>" class="button button-primary">
                            <?php esc_html_e( 'Acquista una Licenza', 'ipv-pro-vendor' ); ?>
                        </a>
                    </p>
                </div>
            <?php else : ?>

                <?php foreach ( $licenses as $license ) :
                    $credits_manager = IPV_Vendor_Credits_Manager::instance();
                    $credits_info = $credits_manager->get_credits_info( $license );

                    // Get activations
                    global $wpdb;
                    $activations = $wpdb->get_results( $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}ipv_activations
                        WHERE license_id = %d AND is_active = 1
                        ORDER BY activated_at DESC",
                        $license->id
                    ) );
                    ?>

                    <div class="ipv-license-card ipv-status-<?php echo esc_attr( $license->status ); ?>">
                        <div class="ipv-license-header">
                            <div class="ipv-license-info">
                                <span class="ipv-license-variant"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $license->variant_slug ) ) ); ?></span>
                                <span class="ipv-license-status ipv-status-<?php echo esc_attr( $license->status ); ?>">
                                    <?php echo esc_html( ucfirst( $license->status ) ); ?>
                                </span>
                            </div>
                            <div class="ipv-license-key">
                                <code><?php echo esc_html( substr( $license->license_key, 0, 8 ) . '...' . substr( $license->license_key, -4 ) ); ?></code>
                                <button class="ipv-copy-key" data-key="<?php echo esc_attr( $license->license_key ); ?>" title="<?php esc_attr_e( 'Copia', 'ipv-pro-vendor' ); ?>">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                        </div>

                        <div class="ipv-license-body">
                            <!-- Credits Section -->
                            <div class="ipv-credits-section">
                                <h4><?php esc_html_e( 'Crediti Mensili', 'ipv-pro-vendor' ); ?></h4>
                                <div class="ipv-credits-bar">
                                    <div class="ipv-credits-progress ipv-credits-<?php echo esc_attr( $credits_info['status'] ); ?>"
                                         style="width: <?php echo esc_attr( $credits_info['percentage'] ); ?>%">
                                    </div>
                                </div>
                                <div class="ipv-credits-stats">
                                    <span class="ipv-credits-remaining">
                                        <strong><?php echo esc_html( $credits_info['credits_remaining'] ); ?></strong> / <?php echo esc_html( $credits_info['credits_total'] ); ?> <?php esc_html_e( 'crediti', 'ipv-pro-vendor' ); ?>
                                    </span>
                                    <span class="ipv-credits-reset">
                                        <?php esc_html_e( 'Reset:', 'ipv-pro-vendor' ); ?> <?php echo esc_html( $credits_info['reset_date_formatted'] ); ?>
                                        (<?php echo esc_html( $credits_info['days_until_reset'] ); ?> <?php esc_html_e( 'giorni', 'ipv-pro-vendor' ); ?>)
                                    </span>
                                </div>
                            </div>

                            <!-- Activations Section -->
                            <div class="ipv-activations-section">
                                <h4>
                                    <?php esc_html_e( 'Siti Attivati', 'ipv-pro-vendor' ); ?>
                                    <span class="ipv-activation-count">
                                        <?php echo esc_html( $license->activation_count ); ?> / <?php echo esc_html( $license->activation_limit ); ?>
                                    </span>
                                </h4>

                                <?php if ( ! empty( $activations ) ) : ?>
                                    <ul class="ipv-activations-list">
                                        <?php foreach ( $activations as $activation ) : ?>
                                            <li>
                                                <span class="ipv-site-url"><?php echo esc_html( $activation->site_url ); ?></span>
                                                <span class="ipv-site-date"><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $activation->activated_at ) ) ); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else : ?>
                                    <p class="ipv-no-activations"><?php esc_html_e( 'Nessun sito ancora attivato.', 'ipv-pro-vendor' ); ?></p>
                                <?php endif; ?>
                            </div>

                            <!-- License Details -->
                            <div class="ipv-license-details">
                                <div class="ipv-detail">
                                    <span class="ipv-detail-label"><?php esc_html_e( 'Email:', 'ipv-pro-vendor' ); ?></span>
                                    <span class="ipv-detail-value"><?php echo esc_html( $license->email ); ?></span>
                                </div>
                                <div class="ipv-detail">
                                    <span class="ipv-detail-label"><?php esc_html_e( 'Creata:', 'ipv-pro-vendor' ); ?></span>
                                    <span class="ipv-detail-value"><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $license->created_at ) ) ); ?></span>
                                </div>
                                <?php if ( $license->expires_at ) : ?>
                                    <div class="ipv-detail">
                                        <span class="ipv-detail-label"><?php esc_html_e( 'Scadenza:', 'ipv-pro-vendor' ); ?></span>
                                        <span class="ipv-detail-value"><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $license->expires_at ) ) ); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="ipv-license-footer">
                            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'ipv-wallet' ) ); ?>" class="button">
                                <?php esc_html_e( 'Storico Crediti', 'ipv-pro-vendor' ); ?>
                            </a>
                            <a href="<?php echo esc_url( home_url( '/ipv-pro/#downloads' ) ); ?>" class="button button-primary">
                                <?php esc_html_e( 'Download Plugin', 'ipv-pro-vendor' ); ?>
                            </a>
                        </div>
                    </div>

                <?php endforeach; ?>

                <!-- Buy More Credits -->
                <div class="ipv-buy-more">
                    <h3><?php esc_html_e( 'Hai bisogno di più crediti?', 'ipv-pro-vendor' ); ?></h3>
                    <p><?php esc_html_e( 'Acquista crediti extra o fai upgrade del tuo piano per aumentare i crediti mensili.', 'ipv-pro-vendor' ); ?></p>
                    <a href="<?php echo esc_url( home_url( '/ipv-pro/#pricing' ) ); ?>" class="button button-primary">
                        <?php esc_html_e( 'Acquista Crediti', 'ipv-pro-vendor' ); ?>
                    </a>
                </div>

            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Wallet page content
     */
    public function wallet_content() {
        $wallet = $this->get_wallet_balance();
        $licenses = $this->get_user_licenses();
        $ledger = $this->get_credit_ledger( null, 100 );
        $active_licenses = array_filter( $licenses, fn( $l ) => $l->status === 'active' );

        ?>
        <div class="ipv-wallet-page">
            <h2><?php esc_html_e( 'Portafoglio Crediti', 'ipv-pro-vendor' ); ?></h2>

            <!-- Wallet Summary -->
            <div class="ipv-wallet-summary">
                <div class="ipv-wallet-card ipv-wallet-balance">
                    <div class="ipv-wallet-icon">
                        <span class="dashicons dashicons-money-alt"></span>
                    </div>
                    <div class="ipv-wallet-info">
                        <span class="ipv-wallet-value"><?php echo esc_html( $wallet['credits_remaining'] ); ?></span>
                        <span class="ipv-wallet-label"><?php esc_html_e( 'Crediti Disponibili', 'ipv-pro-vendor' ); ?></span>
                    </div>
                </div>

                <div class="ipv-wallet-card ipv-wallet-used">
                    <div class="ipv-wallet-icon">
                        <span class="dashicons dashicons-chart-bar"></span>
                    </div>
                    <div class="ipv-wallet-info">
                        <span class="ipv-wallet-value"><?php echo esc_html( $wallet['credits_used'] ); ?></span>
                        <span class="ipv-wallet-label"><?php esc_html_e( 'Crediti Utilizzati', 'ipv-pro-vendor' ); ?></span>
                    </div>
                </div>

                <div class="ipv-wallet-card ipv-wallet-total">
                    <div class="ipv-wallet-icon">
                        <span class="dashicons dashicons-database"></span>
                    </div>
                    <div class="ipv-wallet-info">
                        <span class="ipv-wallet-value"><?php echo esc_html( $wallet['total_credits'] ); ?></span>
                        <span class="ipv-wallet-label"><?php esc_html_e( 'Crediti Totali Mensili', 'ipv-pro-vendor' ); ?></span>
                    </div>
                </div>

                <div class="ipv-wallet-card ipv-wallet-licenses">
                    <div class="ipv-wallet-icon">
                        <span class="dashicons dashicons-admin-network"></span>
                    </div>
                    <div class="ipv-wallet-info">
                        <span class="ipv-wallet-value"><?php echo count( $active_licenses ); ?></span>
                        <span class="ipv-wallet-label"><?php esc_html_e( 'Licenze Attive', 'ipv-pro-vendor' ); ?></span>
                    </div>
                </div>
            </div>

            <!-- Credits Progress -->
            <div class="ipv-wallet-progress-section">
                <h3><?php esc_html_e( 'Utilizzo Crediti', 'ipv-pro-vendor' ); ?></h3>
                <div class="ipv-wallet-progress-bar">
                    <div class="ipv-wallet-progress" style="width: <?php echo esc_attr( $wallet['percentage'] ); ?>%"></div>
                </div>
                <p class="ipv-wallet-progress-text">
                    <?php printf(
                        esc_html__( 'Hai utilizzato %1$d di %2$d crediti (%3$d%% disponibile)', 'ipv-pro-vendor' ),
                        $wallet['credits_used'],
                        $wallet['total_credits'],
                        $wallet['percentage']
                    ); ?>
                </p>
            </div>

            <!-- Buy Credits CTA -->
            <div class="ipv-wallet-cta">
                <h3><?php esc_html_e( 'Ricarica il tuo Portafoglio', 'ipv-pro-vendor' ); ?></h3>
                <p><?php esc_html_e( 'Acquista crediti extra per continuare a generare trascrizioni anche dopo aver esaurito i crediti mensili.', 'ipv-pro-vendor' ); ?></p>
                <div class="ipv-wallet-cta-buttons">
                    <a href="<?php echo esc_url( home_url( '/prodotto/ipv-extra-credits-50/' ) ); ?>" class="button">
                        <?php esc_html_e( '50 Crediti Extra', 'ipv-pro-vendor' ); ?>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/prodotto/ipv-extra-credits-100/' ) ); ?>" class="button">
                        <?php esc_html_e( '100 Crediti Extra', 'ipv-pro-vendor' ); ?>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/prodotto/ipv-extra-credits-200/' ) ); ?>" class="button button-primary">
                        <?php esc_html_e( '200 Crediti Extra', 'ipv-pro-vendor' ); ?>
                    </a>
                </div>
            </div>

            <!-- Credit Ledger / History -->
            <div class="ipv-wallet-history">
                <h3><?php esc_html_e( 'Storico Transazioni', 'ipv-pro-vendor' ); ?></h3>

                <?php if ( empty( $ledger ) ) : ?>
                    <p class="ipv-no-transactions"><?php esc_html_e( 'Nessuna transazione registrata.', 'ipv-pro-vendor' ); ?></p>
                <?php else : ?>
                    <table class="ipv-ledger-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Data', 'ipv-pro-vendor' ); ?></th>
                                <th><?php esc_html_e( 'Tipo', 'ipv-pro-vendor' ); ?></th>
                                <th><?php esc_html_e( 'Crediti', 'ipv-pro-vendor' ); ?></th>
                                <th><?php esc_html_e( 'Saldo', 'ipv-pro-vendor' ); ?></th>
                                <th><?php esc_html_e( 'Note', 'ipv-pro-vendor' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $ledger as $transaction ) :
                                $is_positive = $transaction->amount > 0;
                                ?>
                                <tr class="<?php echo $is_positive ? 'ipv-credit' : 'ipv-debit'; ?>">
                                    <td><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $transaction->created_at ) ) ); ?></td>
                                    <td>
                                        <span class="ipv-transaction-type ipv-type-<?php echo esc_attr( $transaction->type ); ?>">
                                            <?php echo esc_html( $this->get_transaction_type_label( $transaction->type ) ); ?>
                                        </span>
                                    </td>
                                    <td class="ipv-transaction-amount <?php echo $is_positive ? 'positive' : 'negative'; ?>">
                                        <?php echo $is_positive ? '+' : ''; ?><?php echo esc_html( $transaction->amount ); ?>
                                    </td>
                                    <td><?php echo esc_html( $transaction->balance_after ); ?></td>
                                    <td><?php echo esc_html( $transaction->note ?: '-' ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Per-License Breakdown -->
            <?php if ( count( $active_licenses ) > 1 ) : ?>
                <div class="ipv-wallet-breakdown">
                    <h3><?php esc_html_e( 'Dettaglio per Licenza', 'ipv-pro-vendor' ); ?></h3>
                    <table class="ipv-breakdown-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Licenza', 'ipv-pro-vendor' ); ?></th>
                                <th><?php esc_html_e( 'Piano', 'ipv-pro-vendor' ); ?></th>
                                <th><?php esc_html_e( 'Crediti Disponibili', 'ipv-pro-vendor' ); ?></th>
                                <th><?php esc_html_e( 'Prossimo Reset', 'ipv-pro-vendor' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $active_licenses as $license ) :
                                $credits_manager = IPV_Vendor_Credits_Manager::instance();
                                $credits_info = $credits_manager->get_credits_info( $license );
                                ?>
                                <tr>
                                    <td><code><?php echo esc_html( substr( $license->license_key, 0, 8 ) . '...' ); ?></code></td>
                                    <td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $license->variant_slug ) ) ); ?></td>
                                    <td>
                                        <?php echo esc_html( $credits_info['credits_remaining'] ); ?> / <?php echo esc_html( $credits_info['credits_total'] ); ?>
                                    </td>
                                    <td><?php echo esc_html( $credits_info['reset_date_formatted'] ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get transaction type label
     */
    private function get_transaction_type_label( $type ) {
        $labels = [
            'transcript' => __( 'Trascrizione', 'ipv-pro-vendor' ),
            'purchase' => __( 'Acquisto', 'ipv-pro-vendor' ),
            'reset' => __( 'Reset Mensile', 'ipv-pro-vendor' ),
            'bonus' => __( 'Bonus', 'ipv-pro-vendor' ),
            'refund' => __( 'Rimborso', 'ipv-pro-vendor' ),
            'upgrade' => __( 'Upgrade Piano', 'ipv-pro-vendor' ),
            'extra' => __( 'Crediti Extra', 'ipv-pro-vendor' ),
        ];

        return $labels[ $type ] ?? ucfirst( $type );
    }

    /**
     * AJAX: Claim license
     */
    public function ajax_claim_license() {
        check_ajax_referer( 'ipv_portal_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => __( 'Devi essere loggato per associare una licenza.', 'ipv-pro-vendor' ) ] );
        }

        $license_key = sanitize_text_field( $_POST['license_key'] ?? '' );

        if ( empty( $license_key ) ) {
            wp_send_json_error( [ 'message' => __( 'License key obbligatoria.', 'ipv-pro-vendor' ) ] );
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $user = get_userdata( $user_id );

        // Find license
        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE license_key = %s",
            $license_key
        ) );

        if ( ! $license ) {
            wp_send_json_error( [ 'message' => __( 'License key non trovata.', 'ipv-pro-vendor' ) ] );
        }

        // Check if already associated to another user
        if ( $license->user_id > 0 && $license->user_id !== $user_id ) {
            wp_send_json_error( [ 'message' => __( 'Questa licenza è già associata a un altro account.', 'ipv-pro-vendor' ) ] );
        }

        // Associate license to current user
        $wpdb->update(
            $wpdb->prefix . 'ipv_licenses',
            [
                'user_id' => $user_id,
                'email' => $user->user_email
            ],
            [ 'id' => $license->id ],
            [ '%d', '%s' ],
            [ '%d' ]
        );

        wp_send_json_success( [
            'message' => __( 'Licenza associata con successo!', 'ipv-pro-vendor' ),
            'redirect' => wc_get_account_endpoint_url( 'ipv-licenses' )
        ] );
    }
}

// Initialize
IPV_Vendor_Customer_Portal::instance();
