<?php
/**
 * IPV Customer Portal
 *
 * Gestisce integrazione My Account WooCommerce per clienti IPV Pro
 * Versione con Tailwind CSS
 *
 * @version 2.0.0
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
        add_action( 'init', [ $this, 'add_endpoints' ] );
        add_filter( 'woocommerce_account_menu_items', [ $this, 'add_menu_items' ] );
        add_action( 'woocommerce_account_ipv-licenses_endpoint', [ $this, 'licenses_content' ] );
        add_action( 'woocommerce_account_ipv-wallet_endpoint', [ $this, 'wallet_content' ] );
        add_action( 'woocommerce_account_dashboard', [ $this, 'dashboard_widget' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_ajax_ipv_claim_license', [ $this, 'ajax_claim_license' ] );
        register_activation_hook( IPV_VENDOR_FILE, [ $this, 'flush_rewrite_rules' ] );
    }

    public function add_endpoints() {
        add_rewrite_endpoint( 'ipv-licenses', EP_ROOT | EP_PAGES );
        add_rewrite_endpoint( 'ipv-wallet', EP_ROOT | EP_PAGES );
    }

    public function flush_rewrite_rules() {
        $this->add_endpoints();
        flush_rewrite_rules();
    }

    public function add_menu_items( $items ) {
        $new_items = [];
        foreach ( $items as $key => $value ) {
            $new_items[ $key ] = $value;
            if ( $key === 'dashboard' ) {
                $new_items['ipv-licenses'] = __( 'Licenze IPV Pro', 'ipv-pro-vendor' );
                $new_items['ipv-wallet'] = __( 'Portafoglio Crediti', 'ipv-pro-vendor' );
            }
        }
        return $new_items;
    }

    private function get_user_licenses( $user_id = null ) {
        global $wpdb;
        if ( ! $user_id ) $user_id = get_current_user_id();
        if ( ! $user_id ) return [];

        $user = get_userdata( $user_id );
        $user_email = $user ? $user->user_email : '';

        $licenses = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses
            WHERE user_id = %d OR (user_id = 0 AND email = %s)
            ORDER BY created_at DESC",
            $user_id, $user_email
        ) );

        foreach ( $licenses as $license ) {
            if ( $license->user_id == 0 && $license->email === $user_email ) {
                $wpdb->update(
                    $wpdb->prefix . 'ipv_licenses',
                    [ 'user_id' => $user_id ],
                    [ 'id' => $license->id ]
                );
            }
        }

        return $licenses;
    }

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

    private function get_credit_ledger( $user_id = null, $limit = 50 ) {
        global $wpdb;
        $licenses = $this->get_user_licenses( $user_id );
        if ( empty( $licenses ) ) return [];

        $license_keys = array_column( $licenses, 'license_key' );
        $placeholders = implode( ',', array_fill( 0, count( $license_keys ), '%s' ) );

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_credit_ledger
            WHERE license_key IN ($placeholders)
            ORDER BY created_at DESC LIMIT %d",
            array_merge( $license_keys, [ $limit ] )
        ) );
    }

    public function enqueue_scripts() {
        if ( ! is_account_page() ) return;

        // Tailwind CDN
        wp_enqueue_script( 'tailwindcss', 'https://cdn.tailwindcss.com', [], null );

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
            <div class="bg-gray-100 rounded-xl p-6 mb-6">
                <h3 class="text-lg font-bold text-indigo-600 mb-3 flex items-center gap-2">
                    <span>🎬</span> IPV Production System
                </h3>
                <p class="text-gray-600 mb-4"><?php esc_html_e( 'Non hai ancora una licenza IPV Pro.', 'ipv-pro-vendor' ); ?></p>
                <a href="<?php echo esc_url( home_url( '/ipv-pro/' ) ); ?>"
                   class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    <?php esc_html_e( 'Acquista Ora', 'ipv-pro-vendor' ); ?>
                </a>

                <div class="mt-6 pt-4 border-t border-gray-300">
                    <p class="font-semibold mb-2"><?php esc_html_e( 'Hai già una licenza?', 'ipv-pro-vendor' ); ?></p>
                    <form id="ipv-claim-license-form" class="flex gap-2">
                        <input type="text" name="license_key"
                               placeholder="<?php esc_attr_e( 'Inserisci license key', 'ipv-pro-vendor' ); ?>"
                               class="flex-1 px-3 py-2 border rounded-lg" required />
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">
                            <?php esc_html_e( 'Associa', 'ipv-pro-vendor' ); ?>
                        </button>
                    </form>
                    <p class="ipv-claim-result mt-2 text-sm"></p>
                </div>
            </div>
            <?php
            return;
        }

        ?>
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl p-6 mb-6 text-white">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                <span>🎬</span> IPV Production System
            </h3>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="bg-white/20 rounded-lg p-4 text-center">
                    <span class="block text-3xl font-bold"><?php echo count( $active_licenses ); ?></span>
                    <span class="text-sm opacity-90"><?php esc_html_e( 'Licenze Attive', 'ipv-pro-vendor' ); ?></span>
                </div>
                <div class="bg-white/20 rounded-lg p-4 text-center">
                    <span class="block text-3xl font-bold"><?php echo esc_html( $wallet['credits_remaining'] ); ?></span>
                    <span class="text-sm opacity-90"><?php esc_html_e( 'Crediti Disponibili', 'ipv-pro-vendor' ); ?></span>
                </div>
            </div>

            <div class="flex gap-3">
                <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'ipv-licenses' ) ); ?>"
                   class="flex-1 text-center px-4 py-2 bg-white text-indigo-600 rounded-lg font-semibold hover:bg-gray-100">
                    <?php esc_html_e( 'Gestisci Licenze', 'ipv-pro-vendor' ); ?>
                </a>
                <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'ipv-wallet' ) ); ?>"
                   class="flex-1 text-center px-4 py-2 bg-white text-indigo-600 rounded-lg font-semibold hover:bg-gray-100">
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
        <div class="max-w-4xl">
            <h2 class="text-2xl font-bold mb-6"><?php esc_html_e( 'Le Tue Licenze IPV Pro', 'ipv-pro-vendor' ); ?></h2>

            <?php if ( empty( $licenses ) ) : ?>
                <div class="bg-gray-100 rounded-xl p-8 text-center">
                    <p class="text-gray-600 mb-6"><?php esc_html_e( 'Non hai ancora licenze IPV Pro.', 'ipv-pro-vendor' ); ?></p>

                    <div class="bg-white rounded-lg p-6 max-w-md mx-auto">
                        <h3 class="font-semibold mb-3"><?php esc_html_e( 'Hai già una licenza?', 'ipv-pro-vendor' ); ?></h3>
                        <form id="ipv-claim-license-form">
                            <input type="text" name="license_key"
                                   placeholder="<?php esc_attr_e( 'Inserisci license key', 'ipv-pro-vendor' ); ?>"
                                   class="w-full px-4 py-3 border rounded-lg mb-3" required />
                            <button type="submit" class="w-full px-4 py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700">
                                <?php esc_html_e( 'Associa Licenza', 'ipv-pro-vendor' ); ?>
                            </button>
                        </form>
                        <p class="ipv-claim-result mt-3 text-sm"></p>
                    </div>

                    <a href="<?php echo esc_url( home_url( '/ipv-pro/' ) ); ?>"
                       class="inline-block mt-6 px-6 py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700">
                        <?php esc_html_e( 'Acquista una Licenza', 'ipv-pro-vendor' ); ?>
                    </a>
                </div>
            <?php else : ?>

                <?php foreach ( $licenses as $license ) :
                    $credits_manager = class_exists( 'IPV_Vendor_Credits_Manager' ) ? IPV_Vendor_Credits_Manager::instance() : null;
                    $credits_info = $credits_manager ? $credits_manager->get_credits_info( $license ) : [
                        'credits_remaining' => $license->credits_remaining,
                        'credits_total' => $license->credits_total,
                        'percentage' => $license->credits_total > 0 ? round( ( $license->credits_remaining / $license->credits_total ) * 100 ) : 0,
                        'status' => 'ok',
                        'reset_date_formatted' => '-',
                        'days_until_reset' => 0
                    ];

                    global $wpdb;
                    $activations = $wpdb->get_results( $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}ipv_activations
                        WHERE license_id = %d AND is_active = 1",
                        $license->id
                    ) );

                    $status_colors = [
                        'active' => 'border-green-500 bg-green-50',
                        'expired' => 'border-red-500 bg-red-50',
                        'cancelled' => 'border-gray-400 bg-gray-50'
                    ];
                    $status_class = $status_colors[ $license->status ] ?? 'border-gray-400';
                    ?>

                    <div class="border-l-4 <?php echo $status_class; ?> rounded-xl shadow-md mb-6 overflow-hidden">
                        <!-- Header -->
                        <div class="bg-gray-100 px-6 py-4 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <span class="font-bold text-lg"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $license->variant_slug ) ) ); ?></span>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    <?php echo $license->status === 'active' ? 'bg-green-200 text-green-800' : 'bg-gray-200 text-gray-600'; ?>">
                                    <?php echo esc_html( ucfirst( $license->status ) ); ?>
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <code class="bg-white px-3 py-1 rounded text-sm">
                                    <?php echo esc_html( substr( $license->license_key, 0, 8 ) . '...' . substr( $license->license_key, -4 ) ); ?>
                                </code>
                                <button class="ipv-copy-key p-2 hover:bg-gray-200 rounded" data-key="<?php echo esc_attr( $license->license_key ); ?>" title="Copia">
                                    📋
                                </button>
                            </div>
                        </div>

                        <div class="p-6">
                            <!-- Credits -->
                            <div class="mb-6">
                                <div class="flex justify-between text-sm text-gray-600 mb-2">
                                    <span><?php esc_html_e( 'Crediti Mensili', 'ipv-pro-vendor' ); ?></span>
                                    <span>
                                        <strong class="text-gray-900"><?php echo esc_html( $credits_info['credits_remaining'] ); ?></strong>
                                        / <?php echo esc_html( $credits_info['credits_total'] ); ?>
                                    </span>
                                </div>
                                <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                                    <?php
                                    $bar_color = match( $credits_info['status'] ?? 'ok' ) {
                                        'ok' => 'bg-green-500',
                                        'low' => 'bg-yellow-500',
                                        'critical' => 'bg-orange-500',
                                        'depleted' => 'bg-red-500',
                                        default => 'bg-green-500'
                                    };
                                    ?>
                                    <div class="h-full <?php echo $bar_color; ?> transition-all"
                                         style="width: <?php echo esc_attr( $credits_info['percentage'] ); ?>%"></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    <?php esc_html_e( 'Reset:', 'ipv-pro-vendor' ); ?>
                                    <?php echo esc_html( $credits_info['reset_date_formatted'] ); ?>
                                    (<?php echo esc_html( $credits_info['days_until_reset'] ); ?> <?php esc_html_e( 'giorni', 'ipv-pro-vendor' ); ?>)
                                </p>
                            </div>

                            <!-- Activations -->
                            <div class="mb-6">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-semibold text-sm"><?php esc_html_e( 'Siti Attivati', 'ipv-pro-vendor' ); ?></span>
                                    <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-semibold">
                                        <?php echo esc_html( $license->activation_count ); ?> / <?php echo esc_html( $license->activation_limit ); ?>
                                    </span>
                                </div>

                                <?php if ( ! empty( $activations ) ) : ?>
                                    <ul class="space-y-2">
                                        <?php foreach ( $activations as $activation ) : ?>
                                            <li class="flex justify-between bg-gray-50 px-3 py-2 rounded text-sm">
                                                <span class="text-indigo-600"><?php echo esc_html( $activation->site_url ); ?></span>
                                                <span class="text-gray-400"><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $activation->activated_at ) ) ); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else : ?>
                                    <p class="text-gray-500 text-sm italic"><?php esc_html_e( 'Nessun sito ancora attivato.', 'ipv-pro-vendor' ); ?></p>
                                <?php endif; ?>
                            </div>

                            <!-- Details -->
                            <div class="grid grid-cols-3 gap-4 text-sm border-t pt-4">
                                <div>
                                    <span class="text-gray-500 block"><?php esc_html_e( 'Email:', 'ipv-pro-vendor' ); ?></span>
                                    <span class="font-medium"><?php echo esc_html( $license->email ); ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block"><?php esc_html_e( 'Creata:', 'ipv-pro-vendor' ); ?></span>
                                    <span class="font-medium"><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $license->created_at ) ) ); ?></span>
                                </div>
                                <?php if ( $license->expires_at ) : ?>
                                    <div>
                                        <span class="text-gray-500 block"><?php esc_html_e( 'Scadenza:', 'ipv-pro-vendor' ); ?></span>
                                        <span class="font-medium"><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $license->expires_at ) ) ); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t">
                            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'ipv-wallet' ) ); ?>"
                               class="px-4 py-2 border rounded-lg hover:bg-gray-100">
                                <?php esc_html_e( 'Storico Crediti', 'ipv-pro-vendor' ); ?>
                            </a>
                            <a href="<?php echo esc_url( home_url( '/ipv-pro/#downloads' ) ); ?>"
                               class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                <?php esc_html_e( 'Download Plugin', 'ipv-pro-vendor' ); ?>
                            </a>
                        </div>
                    </div>

                <?php endforeach; ?>

                <!-- Buy More -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl p-8 text-white text-center">
                    <h3 class="text-xl font-bold mb-2"><?php esc_html_e( 'Hai bisogno di più crediti?', 'ipv-pro-vendor' ); ?></h3>
                    <p class="opacity-90 mb-4"><?php esc_html_e( 'Acquista crediti extra o fai upgrade del tuo piano.', 'ipv-pro-vendor' ); ?></p>
                    <a href="<?php echo esc_url( home_url( '/ipv-pro/#pricing' ) ); ?>"
                       class="inline-block px-6 py-3 bg-white text-indigo-600 rounded-lg font-semibold hover:bg-gray-100">
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
        <div class="max-w-5xl">
            <h2 class="text-2xl font-bold mb-6"><?php esc_html_e( 'Portafoglio Crediti', 'ipv-pro-vendor' ); ?></h2>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white border rounded-xl p-5 flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center text-2xl">💰</div>
                    <div>
                        <span class="block text-2xl font-bold"><?php echo esc_html( $wallet['credits_remaining'] ); ?></span>
                        <span class="text-sm text-gray-500"><?php esc_html_e( 'Disponibili', 'ipv-pro-vendor' ); ?></span>
                    </div>
                </div>
                <div class="bg-white border rounded-xl p-5 flex items-center gap-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center text-2xl">📊</div>
                    <div>
                        <span class="block text-2xl font-bold"><?php echo esc_html( $wallet['credits_used'] ); ?></span>
                        <span class="text-sm text-gray-500"><?php esc_html_e( 'Utilizzati', 'ipv-pro-vendor' ); ?></span>
                    </div>
                </div>
                <div class="bg-white border rounded-xl p-5 flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center text-2xl">🗄️</div>
                    <div>
                        <span class="block text-2xl font-bold"><?php echo esc_html( $wallet['total_credits'] ); ?></span>
                        <span class="text-sm text-gray-500"><?php esc_html_e( 'Totali', 'ipv-pro-vendor' ); ?></span>
                    </div>
                </div>
                <div class="bg-white border rounded-xl p-5 flex items-center gap-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center text-2xl">🔑</div>
                    <div>
                        <span class="block text-2xl font-bold"><?php echo count( $active_licenses ); ?></span>
                        <span class="text-sm text-gray-500"><?php esc_html_e( 'Licenze', 'ipv-pro-vendor' ); ?></span>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="bg-white border rounded-xl p-6 mb-8">
                <h3 class="font-semibold mb-3"><?php esc_html_e( 'Utilizzo Crediti', 'ipv-pro-vendor' ); ?></h3>
                <div class="h-5 bg-gray-200 rounded-full overflow-hidden mb-2">
                    <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all"
                         style="width: <?php echo esc_attr( $wallet['percentage'] ); ?>%"></div>
                </div>
                <p class="text-sm text-gray-600">
                    <?php printf(
                        esc_html__( 'Hai utilizzato %1$d di %2$d crediti (%3$d%% disponibile)', 'ipv-pro-vendor' ),
                        $wallet['credits_used'],
                        $wallet['total_credits'],
                        $wallet['percentage']
                    ); ?>
                </p>
            </div>

            <!-- Buy Credits CTA -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl p-8 text-white text-center mb-8">
                <h3 class="text-xl font-bold mb-2"><?php esc_html_e( 'Ricarica il tuo Portafoglio', 'ipv-pro-vendor' ); ?></h3>
                <p class="opacity-90 mb-4"><?php esc_html_e( 'Acquista crediti extra che non scadono mai.', 'ipv-pro-vendor' ); ?></p>
                <div class="flex justify-center gap-3 flex-wrap">
                    <a href="<?php echo esc_url( home_url( '/prodotto/ipv-pro-crediti-extra-10/' ) ); ?>"
                       class="px-5 py-2 bg-white/20 border-2 border-white/50 rounded-lg hover:bg-white/30">
                        10 Crediti - €5
                    </a>
                    <a href="<?php echo esc_url( home_url( '/prodotto/ipv-pro-crediti-extra-100/' ) ); ?>"
                       class="px-5 py-2 bg-white text-indigo-600 rounded-lg font-semibold hover:bg-gray-100">
                        100 Crediti - €45
                    </a>
                </div>
            </div>

            <!-- Transaction History -->
            <div class="bg-white border rounded-xl p-6">
                <h3 class="font-semibold mb-4"><?php esc_html_e( 'Storico Transazioni', 'ipv-pro-vendor' ); ?></h3>

                <?php if ( empty( $ledger ) ) : ?>
                    <p class="text-gray-500 text-center py-8"><?php esc_html_e( 'Nessuna transazione registrata.', 'ipv-pro-vendor' ); ?></p>
                <?php else : ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"><?php esc_html_e( 'Data', 'ipv-pro-vendor' ); ?></th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"><?php esc_html_e( 'Tipo', 'ipv-pro-vendor' ); ?></th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"><?php esc_html_e( 'Crediti', 'ipv-pro-vendor' ); ?></th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"><?php esc_html_e( 'Saldo', 'ipv-pro-vendor' ); ?></th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"><?php esc_html_e( 'Note', 'ipv-pro-vendor' ); ?></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php foreach ( $ledger as $tx ) :
                                    $is_positive = $tx->amount > 0;
                                    $type_labels = [
                                        'transcript' => 'Trascrizione',
                                        'purchase' => 'Acquisto',
                                        'reset' => 'Reset Mensile',
                                        'bonus' => 'Bonus',
                                        'upgrade' => 'Upgrade',
                                        'downgrade' => 'Downgrade',
                                        'extra' => 'Crediti Extra',
                                    ];
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm"><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $tx->created_at ) ) ); ?></td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 rounded text-xs font-medium
                                                <?php echo $is_positive ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'; ?>">
                                                <?php echo esc_html( $type_labels[ $tx->type ] ?? ucfirst( $tx->type ) ); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 font-semibold <?php echo $is_positive ? 'text-green-600' : 'text-red-600'; ?>">
                                            <?php echo $is_positive ? '+' : ''; ?><?php echo esc_html( $tx->amount ); ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm"><?php echo esc_html( $tx->balance_after ); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-500"><?php echo esc_html( $tx->note ?: '-' ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Claim license
     */
    public function ajax_claim_license() {
        check_ajax_referer( 'ipv_portal_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => __( 'Devi essere loggato.', 'ipv-pro-vendor' ) ] );
        }

        $license_key = sanitize_text_field( $_POST['license_key'] ?? '' );
        if ( empty( $license_key ) ) {
            wp_send_json_error( [ 'message' => __( 'License key obbligatoria.', 'ipv-pro-vendor' ) ] );
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $user = get_userdata( $user_id );

        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE license_key = %s",
            $license_key
        ) );

        if ( ! $license ) {
            wp_send_json_error( [ 'message' => __( 'License key non trovata.', 'ipv-pro-vendor' ) ] );
        }

        if ( $license->user_id > 0 && $license->user_id !== $user_id ) {
            wp_send_json_error( [ 'message' => __( 'Licenza già associata a un altro account.', 'ipv-pro-vendor' ) ] );
        }

        $wpdb->update(
            $wpdb->prefix . 'ipv_licenses',
            [ 'user_id' => $user_id, 'email' => $user->user_email ],
            [ 'id' => $license->id ]
        );

        wp_send_json_success( [
            'message' => __( 'Licenza associata con successo!', 'ipv-pro-vendor' ),
            'redirect' => wc_get_account_endpoint_url( 'ipv-licenses' )
        ] );
    }
}

IPV_Vendor_Customer_Portal::instance();
