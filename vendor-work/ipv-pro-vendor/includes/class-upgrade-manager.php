<?php
/**
 * IPV Upgrade Manager
 *
 * Gestisce upgrade/downgrade piani nel My Account WooCommerce
 *
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Upgrade_Manager {

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

        // Add menu item
        add_filter( 'woocommerce_account_menu_items', [ $this, 'add_menu_item' ] );

        // Add content for endpoint
        add_action( 'woocommerce_account_ipv-upgrade_endpoint', [ $this, 'upgrade_content' ] );

        // AJAX handlers
        add_action( 'wp_ajax_ipv_preview_upgrade', [ $this, 'ajax_preview_upgrade' ] );
        add_action( 'wp_ajax_ipv_execute_downgrade', [ $this, 'ajax_execute_downgrade' ] );

        // Handle upgrade from checkout
        add_action( 'woocommerce_thankyou', [ $this, 'handle_upgrade_order' ], 5 );
    }

    /**
     * Add custom endpoint
     */
    public function add_endpoints() {
        add_rewrite_endpoint( 'ipv-upgrade', EP_ROOT | EP_PAGES );
    }

    /**
     * Add menu item to My Account
     */
    public function add_menu_item( $items ) {
        $new_items = [];

        foreach ( $items as $key => $value ) {
            $new_items[ $key ] = $value;

            // Add after ipv-wallet
            if ( $key === 'ipv-wallet' ) {
                $new_items['ipv-upgrade'] = __( 'Upgrade/Downgrade Piano', 'ipv-pro-vendor' );
            }
        }

        return $new_items;
    }

    /**
     * Get user's active license
     */
    private function get_user_license() {
        global $wpdb;

        $user_id = get_current_user_id();
        $user = get_userdata( $user_id );

        if ( ! $user ) {
            return null;
        }

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses
            WHERE (user_id = %d OR email = %s) AND status = 'active'
            ORDER BY created_at DESC LIMIT 1",
            $user_id,
            $user->user_email
        ) );
    }

    /**
     * Upgrade page content
     */
    public function upgrade_content() {
        // Load Tailwind CSS CDN
        wp_enqueue_script( 'tailwindcss', 'https://cdn.tailwindcss.com', [], null );

        $license = $this->get_user_license();

        if ( ! $license ) {
            $this->render_no_license();
            return;
        }

        $plans_manager = IPV_Vendor_Plans_Manager::instance();
        $all_plans = $plans_manager->get_plans();
        $current_plan = $all_plans[ $license->variant_slug ] ?? null;
        $current_credits = $current_plan ? $current_plan['credits'] : (int) $license->credits_total;
        $current_price = $current_plan ? $current_plan['price'] : 0;

        // Get available plans
        // NOTA: Trial è SOLO un piano di benvenuto - non può mai essere selezionato come downgrade
        // Gli utenti Trial possono solo fare UPGRADE verso piani a pagamento
        $upgrades = [];
        $downgrades = [];
        $is_trial_user = ( $license->variant_slug === 'trial' );

        foreach ( $all_plans as $slug => $plan ) {
            if ( empty( $plan['is_active'] ) ) continue;
            if ( $slug === $license->variant_slug ) continue;
            // Trial non è mai un'opzione selezionabile
            if ( $slug === 'trial' ) continue;
            if ( strpos( $slug, 'extra_credits' ) !== false || $slug === 'golden_prompt' ) continue;

            $plan['slug'] = $slug;
            $plan['is_current'] = false;
            $plan['price_diff'] = $plan['price'] - $current_price;
            $plan['credits_diff'] = $plan['credits'] - $current_credits;
            $plan['product_id'] = $this->get_product_for_plan( $slug );

            // Utenti Trial: possono solo fare upgrade (non esistono downgrade)
            if ( $is_trial_user ) {
                $upgrades[] = $plan;
            } elseif ( $plan['credits'] > $current_credits || $plan['price'] > $current_price ) {
                $upgrades[] = $plan;
            } else {
                $downgrades[] = $plan;
            }
        }

        // Sort
        usort( $upgrades, fn( $a, $b ) => $a['credits'] - $b['credits'] );
        usort( $downgrades, fn( $a, $b ) => $b['credits'] - $a['credits'] );

        $credits_manager = IPV_Vendor_Credits_Manager::instance();
        $credits_info = $credits_manager->get_credits_info( $license );

        ?>
        <div class="max-w-5xl mx-auto py-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <?php esc_html_e( 'Gestione Piano IPV Pro', 'ipv-pro-vendor' ); ?>
            </h2>

            <!-- Current Plan -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-700 mb-4"><?php esc_html_e( 'Il Tuo Piano Attuale', 'ipv-pro-vendor' ); ?></h3>

                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl p-6 text-white relative overflow-hidden">
                    <div class="absolute top-4 right-4 bg-white/20 px-3 py-1 rounded-full text-sm font-medium">
                        <?php esc_html_e( 'Piano Attivo', 'ipv-pro-vendor' ); ?>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <div>
                            <span class="text-3xl font-bold"><?php echo esc_html( $current_plan['name'] ?? ucfirst( $license->variant_slug ) ); ?></span>
                            <div class="text-white/80 mt-1">
                                <?php if ( $current_price > 0 ) : ?>
                                    €<?php echo number_format( $current_price, 2, ',', '.' ); ?>
                                    <span class="text-sm">/<?php echo esc_html( $this->get_period_label( $current_plan['price_period'] ?? 'year' ) ); ?></span>
                                <?php else : ?>
                                    <?php esc_html_e( 'Gratuito', 'ipv-pro-vendor' ); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white/10 rounded-lg p-4 text-center">
                            <span class="block text-2xl font-bold"><?php echo esc_html( $current_credits ); ?></span>
                            <span class="text-sm text-white/80"><?php esc_html_e( 'Crediti/periodo', 'ipv-pro-vendor' ); ?></span>
                        </div>
                        <div class="bg-white/10 rounded-lg p-4 text-center">
                            <span class="block text-2xl font-bold"><?php echo esc_html( $credits_info['credits_remaining'] ); ?></span>
                            <span class="text-sm text-white/80"><?php esc_html_e( 'Crediti rimasti', 'ipv-pro-vendor' ); ?></span>
                        </div>
                        <div class="bg-white/10 rounded-lg p-4 text-center">
                            <span class="block text-2xl font-bold"><?php echo esc_html( $license->activation_count ); ?>/<?php echo esc_html( $license->activation_limit ); ?></span>
                            <span class="text-sm text-white/80"><?php esc_html_e( 'Siti attivi', 'ipv-pro-vendor' ); ?></span>
                        </div>
                        <div class="bg-white/10 rounded-lg p-4 text-center">
                            <span class="block text-2xl font-bold"><?php echo esc_html( $credits_info['days_until_reset'] ); ?></span>
                            <span class="text-sm text-white/80"><?php esc_html_e( 'Giorni al reset', 'ipv-pro-vendor' ); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upgrade Options -->
            <?php if ( ! empty( $upgrades ) ) : ?>
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                    <?php esc_html_e( 'Upgrade - Più Crediti e Funzionalità', 'ipv-pro-vendor' ); ?>
                </h3>

                <div class="grid md:grid-cols-3 gap-6">
                    <?php foreach ( $upgrades as $plan ) : ?>
                        <div class="bg-white border-2 border-green-500 rounded-xl p-6 relative hover:shadow-lg transition-shadow">
                            <?php if ( $plan['slug'] === 'professional' ) : ?>
                                <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-green-500 text-white px-4 py-1 rounded-full text-sm font-semibold">
                                    <?php esc_html_e( 'Popolare', 'ipv-pro-vendor' ); ?>
                                </div>
                            <?php endif; ?>

                            <div class="text-center mb-4">
                                <h4 class="text-xl font-bold text-gray-800"><?php echo esc_html( $plan['name'] ); ?></h4>
                                <div class="text-3xl font-bold text-indigo-600 mt-2">
                                    €<?php echo number_format( $plan['price'], 2, ',', '.' ); ?>
                                    <span class="text-sm font-normal text-gray-500">/<?php echo esc_html( $this->get_period_label( $plan['price_period'] ?? 'year' ) ); ?></span>
                                </div>
                            </div>

                            <div class="bg-gray-100 rounded-lg p-3 text-center mb-4">
                                <span class="text-2xl font-bold text-indigo-600"><?php echo esc_html( $plan['credits'] ); ?></span>
                                <span class="text-gray-600"><?php esc_html_e( 'crediti/', 'ipv-pro-vendor' ); ?><?php echo esc_html( $this->get_period_label( $plan['credits_period'] ?? 'year' ) ); ?></span>
                                <span class="inline-block ml-2 bg-green-100 text-green-700 px-2 py-1 rounded text-sm font-semibold">+<?php echo esc_html( $plan['credits_diff'] ); ?></span>
                            </div>

                            <ul class="space-y-2 mb-6 text-sm text-gray-600">
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    <?php echo esc_html( $plan['activations'] ); ?> <?php esc_html_e( 'siti attivabili', 'ipv-pro-vendor' ); ?>
                                </li>
                                <?php if ( ! empty( $plan['features']['priority_support'] ) ) : ?>
                                    <li class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        <?php esc_html_e( 'Supporto prioritario', 'ipv-pro-vendor' ); ?>
                                    </li>
                                <?php endif; ?>
                                <?php if ( ! empty( $plan['features']['api_access'] ) ) : ?>
                                    <li class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        <?php esc_html_e( 'Accesso API', 'ipv-pro-vendor' ); ?>
                                    </li>
                                <?php endif; ?>
                            </ul>

                            <div>
                                <?php if ( $plan['product_id'] ) : ?>
                                    <a href="<?php echo esc_url( add_query_arg([
                                        'add-to-cart' => $plan['product_id'],
                                        'ipv_upgrade_license' => $license->license_key
                                    ], wc_get_checkout_url()) ); ?>" class="block w-full text-center bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors">
                                        <?php esc_html_e( 'Upgrade a', 'ipv-pro-vendor' ); ?> <?php echo esc_html( $plan['name'] ); ?>
                                        <span class="block text-sm font-normal opacity-90">+€<?php echo number_format( $plan['price_diff'], 2, ',', '.' ); ?></span>
                                    </a>
                                <?php else : ?>
                                    <a href="<?php echo esc_url( home_url( '/ipv-pro/#pricing' ) ); ?>" class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors">
                                        <?php esc_html_e( 'Vedi Offerta', 'ipv-pro-vendor' ); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Downgrade Options -->
            <?php if ( ! empty( $downgrades ) ) : ?>
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                    <?php esc_html_e( 'Downgrade - Riduci Piano', 'ipv-pro-vendor' ); ?>
                </h3>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6 flex items-center gap-3 text-yellow-800">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span><?php esc_html_e( 'Il downgrade ridurrà i tuoi crediti mensili. I crediti rimanenti verranno adeguati al nuovo piano.', 'ipv-pro-vendor' ); ?></span>
                </div>

                <div class="grid md:grid-cols-3 gap-6">
                    <?php foreach ( $downgrades as $plan ) : ?>
                        <div class="bg-gray-50 border-2 border-yellow-400 rounded-xl p-6 hover:shadow-lg transition-shadow">
                            <div class="text-center mb-4">
                                <h4 class="text-xl font-bold text-gray-800"><?php echo esc_html( $plan['name'] ); ?></h4>
                                <div class="text-2xl font-bold text-gray-600 mt-2">
                                    <?php if ( $plan['price'] > 0 ) : ?>
                                        €<?php echo number_format( $plan['price'], 2, ',', '.' ); ?>
                                        <span class="text-sm font-normal text-gray-500">/<?php echo esc_html( $this->get_period_label( $plan['price_period'] ?? 'year' ) ); ?></span>
                                    <?php else : ?>
                                        <?php esc_html_e( 'Gratuito', 'ipv-pro-vendor' ); ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg p-3 text-center mb-4">
                                <span class="text-2xl font-bold text-gray-700"><?php echo esc_html( $plan['credits'] ); ?></span>
                                <span class="text-gray-600"><?php esc_html_e( 'crediti/', 'ipv-pro-vendor' ); ?><?php echo esc_html( $this->get_period_label( $plan['credits_period'] ?? 'year' ) ); ?></span>
                                <span class="inline-block ml-2 bg-red-100 text-red-700 px-2 py-1 rounded text-sm font-semibold"><?php echo esc_html( $plan['credits_diff'] ); ?></span>
                            </div>

                            <div>
                                <button type="button"
                                        class="w-full text-center bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors ipv-downgrade-btn"
                                        data-plan="<?php echo esc_attr( $plan['slug'] ); ?>"
                                        data-name="<?php echo esc_attr( $plan['name'] ); ?>"
                                        data-credits="<?php echo esc_attr( $plan['credits'] ); ?>">
                                    <?php esc_html_e( 'Passa a', 'ipv-pro-vendor' ); ?> <?php echo esc_html( $plan['name'] ); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Extra Credits CTA -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6 text-center">
                <h3 class="text-xl font-bold text-gray-800 mb-2"><?php esc_html_e( 'Hai bisogno di crediti extra senza cambiare piano?', 'ipv-pro-vendor' ); ?></h3>
                <p class="text-gray-600 mb-4"><?php esc_html_e( 'Acquista pacchetti di crediti aggiuntivi che non scadono mai.', 'ipv-pro-vendor' ); ?></p>
                <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'ipv-wallet' ) ); ?>" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                    <?php esc_html_e( 'Vai al Portafoglio Crediti', 'ipv-pro-vendor' ); ?>
                </a>
            </div>
        </div>

        <!-- Downgrade Confirmation Modal -->
        <div id="ipv-downgrade-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display: none;">
            <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4 shadow-2xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-800"><?php esc_html_e( 'Conferma Downgrade', 'ipv-pro-vendor' ); ?></h3>
                    <button type="button" class="ipv-modal-close text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <p id="ipv-downgrade-message" class="text-gray-600 mb-6"></p>
                <div id="ipv-downgrade-preview" class="mb-6"></div>
                <div class="flex gap-3">
                    <button type="button" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-4 rounded-lg transition-colors ipv-modal-cancel">
                        <?php esc_html_e( 'Annulla', 'ipv-pro-vendor' ); ?>
                    </button>
                    <button type="button" class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors ipv-confirm-downgrade">
                        <?php esc_html_e( 'Conferma Downgrade', 'ipv-pro-vendor' ); ?>
                    </button>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var licenseKey = '<?php echo esc_js( $license->license_key ); ?>';

            // Downgrade button click
            $('.ipv-downgrade-btn').on('click', function() {
                var plan = $(this).data('plan');
                var planName = $(this).data('name');
                var credits = $(this).data('credits');

                $('#ipv-downgrade-message').text(
                    'Stai per passare al piano ' + planName + ' con ' + credits + ' crediti. ' +
                    'I crediti rimanenti verranno adeguati al nuovo limite.'
                );

                $('#ipv-downgrade-modal').data('target-plan', plan).show();
            });

            // Modal close
            $('.ipv-modal-close, .ipv-modal-cancel').on('click', function() {
                $('#ipv-downgrade-modal').hide();
            });

            // Confirm downgrade
            $('.ipv-confirm-downgrade').on('click', function() {
                var targetPlan = $('#ipv-downgrade-modal').data('target-plan');
                var button = $(this);

                button.prop('disabled', true).text('<?php esc_html_e( 'Elaborazione...', 'ipv-pro-vendor' ); ?>');

                $.ajax({
                    url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                    type: 'POST',
                    data: {
                        action: 'ipv_execute_downgrade',
                        nonce: '<?php echo wp_create_nonce( 'ipv_downgrade_nonce' ); ?>',
                        license_key: licenseKey,
                        target_plan: targetPlan
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php esc_html_e( 'Piano aggiornato con successo!', 'ipv-pro-vendor' ); ?>');
                            location.reload();
                        } else {
                            alert(response.data.message || '<?php esc_html_e( 'Errore durante il downgrade', 'ipv-pro-vendor' ); ?>');
                            button.prop('disabled', false).text('<?php esc_html_e( 'Conferma Downgrade', 'ipv-pro-vendor' ); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php esc_html_e( 'Errore di connessione', 'ipv-pro-vendor' ); ?>');
                        button.prop('disabled', false).text('<?php esc_html_e( 'Conferma Downgrade', 'ipv-pro-vendor' ); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render no license message
     */
    private function render_no_license() {
        // Load Tailwind CSS CDN
        wp_enqueue_script( 'tailwindcss', 'https://cdn.tailwindcss.com', [], null );
        ?>
        <div class="max-w-2xl mx-auto py-12 text-center">
            <div class="bg-white border-2 border-yellow-400 rounded-xl p-8">
                <svg class="w-16 h-16 mx-auto text-yellow-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <h2 class="text-2xl font-bold text-gray-800 mb-3"><?php esc_html_e( 'Nessuna Licenza Attiva', 'ipv-pro-vendor' ); ?></h2>
                <p class="text-gray-600 mb-6"><?php esc_html_e( 'Non hai ancora una licenza IPV Pro attiva. Acquista una licenza per iniziare.', 'ipv-pro-vendor' ); ?></p>
                <a href="<?php echo esc_url( home_url( '/ipv-pro/' ) ); ?>" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-8 rounded-lg transition-colors">
                    <?php esc_html_e( 'Acquista Licenza', 'ipv-pro-vendor' ); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Execute downgrade
     */
    public function ajax_execute_downgrade() {
        check_ajax_referer( 'ipv_downgrade_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => 'Non autorizzato' ] );
        }

        global $wpdb;

        $license_key = sanitize_text_field( $_POST['license_key'] ?? '' );
        $target_plan_slug = sanitize_text_field( $_POST['target_plan'] ?? '' );

        if ( empty( $license_key ) || empty( $target_plan_slug ) ) {
            wp_send_json_error( [ 'message' => 'Parametri mancanti' ] );
        }

        // Verify license belongs to user
        $license = $this->get_user_license();

        if ( ! $license || $license->license_key !== $license_key ) {
            wp_send_json_error( [ 'message' => 'Licenza non valida' ] );
        }

        $plans_manager = IPV_Vendor_Plans_Manager::instance();
        $target_plan = $plans_manager->get_plan( $target_plan_slug );
        $current_plan = $plans_manager->get_plan( $license->variant_slug );

        if ( ! $target_plan ) {
            wp_send_json_error( [ 'message' => 'Piano non trovato' ] );
        }

        // Verify it's actually a downgrade (no payment required)
        $current_price = $current_plan ? $current_plan['price'] : 0;
        if ( $target_plan['price'] > $current_price ) {
            wp_send_json_error( [ 'message' => 'Questo richiede un pagamento. Usa la sezione upgrade.' ] );
        }

        // Execute downgrade
        $old_plan = $license->variant_slug;
        $current_credits_remaining = (int) $license->credits_remaining;
        $new_credits_total = $target_plan['credits'];
        $new_credits_remaining = min( $current_credits_remaining, $new_credits_total );

        $wpdb->update(
            $wpdb->prefix . 'ipv_licenses',
            [
                'variant_slug' => $target_plan_slug,
                'credits_total' => $new_credits_total,
                'credits_monthly' => $new_credits_total,
                'credits_remaining' => $new_credits_remaining
            ],
            [ 'id' => $license->id ],
            [ '%s', '%d', '%d', '%d' ],
            [ '%d' ]
        );

        // Log change
        $wpdb->insert(
            $wpdb->prefix . 'ipv_credit_ledger',
            [
                'license_key' => $license->license_key,
                'type' => 'downgrade',
                'amount' => $new_credits_remaining - $current_credits_remaining,
                'balance_after' => $new_credits_remaining,
                'ref_type' => 'plan_change',
                'ref_id' => $target_plan_slug,
                'note' => sprintf( 'Downgrade da %s a %s', $old_plan, $target_plan_slug ),
                'created_at' => current_time( 'mysql' )
            ],
            [ '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s' ]
        );

        // Refresh license object with new data
        $license->variant_slug = $target_plan_slug;
        $license->credits_remaining = $new_credits_remaining;

        // Trigger email notification
        do_action( 'ipv_plan_downgraded', $license, $old_plan, $target_plan_slug );

        wp_send_json_success([
            'message' => 'Piano aggiornato con successo',
            'new_plan' => $target_plan_slug,
            'new_credits' => $new_credits_remaining
        ]);
    }

    /**
     * Handle upgrade order completion
     */
    public function handle_upgrade_order( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        // Check if this was an upgrade order
        $upgrade_license = get_post_meta( $order_id, '_ipv_upgrade_license', true );

        if ( empty( $upgrade_license ) ) {
            return;
        }

        // The upgrade will be processed by WooCommerce Integration class
        // Just add a note here
        $order->add_order_note( sprintf(
            'IPV Pro: Upgrade licenza %s completato',
            $upgrade_license
        ));
    }

    /**
     * Get product ID for plan slug
     */
    private function get_product_for_plan( $plan_slug ) {
        global $wpdb;

        return $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_ipv_plan_slug' AND meta_value = %s
            LIMIT 1",
            $plan_slug
        ) );
    }

    /**
     * Get period label
     */
    private function get_period_label( $period ) {
        $labels = [
            'day' => __( 'giorno', 'ipv-pro-vendor' ),
            'week' => __( 'settimana', 'ipv-pro-vendor' ),
            'month' => __( 'mese', 'ipv-pro-vendor' ),
            'year' => __( 'anno', 'ipv-pro-vendor' ),
            'once' => __( 'una tantum', 'ipv-pro-vendor' ),
        ];
        return $labels[ $period ] ?? $period;
    }
}

// Initialize
IPV_Vendor_Upgrade_Manager::instance();
