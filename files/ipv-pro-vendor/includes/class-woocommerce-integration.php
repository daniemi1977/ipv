<?php
/**
 * IPV WooCommerce Integration
 *
 * Gestisce l'integrazione con WooCommerce per acquisto crediti e upgrade
 *
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_WooCommerce_Integration {

    private static $instance = null;

    /**
     * Prodotti che richiedono licenza
     */
    private $credit_product_slugs = [
        'golden_prompt',
        'extra_credits_10',
        'extra_credits_100',
    ];

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Checkout: Aggiungi campo licenza
        add_action( 'woocommerce_before_order_notes', [ $this, 'add_license_field' ] );

        // Checkout: Valida campo licenza
        add_action( 'woocommerce_checkout_process', [ $this, 'validate_license_field' ] );

        // Checkout: Salva licenza nell'ordine
        add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'save_license_to_order' ] );

        // Ordine completato: Processa crediti
        add_action( 'woocommerce_order_status_completed', [ $this, 'process_credits_order' ], 10 );
        add_action( 'woocommerce_order_status_processing', [ $this, 'process_credits_order' ], 10 );

        // Admin: Mostra licenza nell'ordine
        add_action( 'woocommerce_admin_order_data_after_billing_address', [ $this, 'display_license_in_admin' ] );

        // Email: Aggiungi info licenza
        add_action( 'woocommerce_email_order_meta', [ $this, 'add_license_to_email' ], 10, 3 );

        // Prodotto: Aggiungi meta box per slug piano
        add_action( 'woocommerce_product_options_general_product_data', [ $this, 'add_product_plan_field' ] );
        add_action( 'woocommerce_process_product_meta', [ $this, 'save_product_plan_field' ] );

        // AJAX: Verifica licenza in tempo reale
        add_action( 'wp_ajax_ipv_verify_license', [ $this, 'ajax_verify_license' ] );
        add_action( 'wp_ajax_nopriv_ipv_verify_license', [ $this, 'ajax_verify_license' ] );

        // Carica script checkout
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_checkout_scripts' ] );
    }

    /**
     * Verifica se il carrello contiene prodotti che richiedono licenza
     */
    private function cart_needs_license() {
        if ( ! WC()->cart ) {
            return false;
        }

        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $product = $cart_item['data'];
            $plan_slug = $product->get_meta( '_ipv_plan_slug' );

            if ( in_array( $plan_slug, $this->credit_product_slugs, true ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se il carrello contiene un upgrade (ha già licenza nell'URL)
     */
    private function cart_has_upgrade_license() {
        return ! empty( WC()->session->get( 'ipv_upgrade_license' ) );
    }

    /**
     * Aggiungi campo licenza nel checkout
     */
    public function add_license_field( $checkout ) {
        // Se è un upgrade, la licenza è già disponibile
        if ( $this->cart_has_upgrade_license() ) {
            $license_key = WC()->session->get( 'ipv_upgrade_license' );
            ?>
            <div class="ipv-license-upgrade-notice" style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <h3 style="margin: 0 0 10px 0; color: #155724;">
                    <?php esc_html_e( 'Upgrade Piano IPV Pro', 'ipv-pro-vendor' ); ?>
                </h3>
                <p style="margin: 0; color: #155724;">
                    <?php esc_html_e( 'I crediti verranno aggiunti alla tua licenza:', 'ipv-pro-vendor' ); ?>
                    <strong><?php echo esc_html( $this->mask_license_key( $license_key ) ); ?></strong>
                </p>
                <input type="hidden" name="ipv_license_key" value="<?php echo esc_attr( $license_key ); ?>">
            </div>
            <?php
            return;
        }

        // Se non serve licenza, esci
        if ( ! $this->cart_needs_license() ) {
            return;
        }

        // Mostra campo per inserire licenza
        ?>
        <div id="ipv-license-field-wrapper" style="margin-bottom: 20px;">
            <h3><?php esc_html_e( 'Licenza IPV Pro', 'ipv-pro-vendor' ); ?></h3>

            <p class="form-row form-row-wide" id="ipv_license_key_field">
                <label for="ipv_license_key">
                    <?php esc_html_e( 'Inserisci la tua License Key', 'ipv-pro-vendor' ); ?>
                    <abbr class="required" title="required">*</abbr>
                </label>
                <span class="woocommerce-input-wrapper">
                    <input type="text"
                           class="input-text"
                           name="ipv_license_key"
                           id="ipv_license_key"
                           placeholder="XXXX-XXXX-XXXX-XXXX"
                           value="<?php echo esc_attr( $checkout->get_value( 'ipv_license_key' ) ); ?>"
                           style="text-transform: uppercase;">
                    <span id="ipv-license-status" style="display: block; margin-top: 8px;"></span>
                </span>
            </p>

            <p class="ipv-license-help" style="font-size: 13px; color: #666; margin-top: 10px;">
                <?php esc_html_e( 'La License Key si trova nel tuo', 'ipv-pro-vendor' ); ?>
                <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'ipv-licenses' ) ); ?>">
                    <?php esc_html_e( 'Account > Licenze IPV', 'ipv-pro-vendor' ); ?>
                </a>
                <?php esc_html_e( 'oppure nell\'email di conferma acquisto.', 'ipv-pro-vendor' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Maschera la license key per sicurezza
     */
    private function mask_license_key( $key ) {
        if ( strlen( $key ) < 8 ) {
            return $key;
        }
        return substr( $key, 0, 4 ) . '-****-****-' . substr( $key, -4 );
    }

    /**
     * Valida campo licenza
     */
    public function validate_license_field() {
        // Se upgrade, la licenza è già validata
        if ( $this->cart_has_upgrade_license() ) {
            return;
        }

        // Se non serve licenza, esci
        if ( ! $this->cart_needs_license() ) {
            return;
        }

        $license_key = isset( $_POST['ipv_license_key'] ) ? sanitize_text_field( $_POST['ipv_license_key'] ) : '';

        if ( empty( $license_key ) ) {
            wc_add_notice(
                __( 'Inserisci la tua License Key IPV Pro per aggiungere i crediti.', 'ipv-pro-vendor' ),
                'error'
            );
            return;
        }

        // Verifica che la licenza esista e sia attiva
        global $wpdb;

        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses
            WHERE license_key = %s AND status = 'active'",
            strtoupper( $license_key )
        ) );

        if ( ! $license ) {
            wc_add_notice(
                __( 'La License Key inserita non è valida o non è attiva.', 'ipv-pro-vendor' ),
                'error'
            );
            return;
        }

        // Verifica che la licenza appartenga all'utente (se loggato)
        if ( is_user_logged_in() ) {
            $current_user = wp_get_current_user();
            if ( $license->user_id && $license->user_id != get_current_user_id() &&
                 $license->email !== $current_user->user_email ) {
                wc_add_notice(
                    __( 'Questa License Key non appartiene al tuo account.', 'ipv-pro-vendor' ),
                    'error'
                );
            }
        }
    }

    /**
     * Salva licenza nell'ordine
     */
    public function save_license_to_order( $order_id ) {
        // Prima controlla se c'è licenza upgrade nella sessione
        $upgrade_license = WC()->session->get( 'ipv_upgrade_license' );
        if ( $upgrade_license ) {
            update_post_meta( $order_id, '_ipv_license_key', sanitize_text_field( $upgrade_license ) );
            update_post_meta( $order_id, '_ipv_is_upgrade', 'yes' );
            WC()->session->set( 'ipv_upgrade_license', null );
            return;
        }

        // Altrimenti usa il campo del form
        if ( ! empty( $_POST['ipv_license_key'] ) ) {
            $license_key = strtoupper( sanitize_text_field( $_POST['ipv_license_key'] ) );
            update_post_meta( $order_id, '_ipv_license_key', $license_key );
        }
    }

    /**
     * Processa ordine crediti
     */
    public function process_credits_order( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Evita doppia elaborazione
        if ( $order->get_meta( '_ipv_credits_processed' ) === 'yes' ) {
            return;
        }

        $license_key = $order->get_meta( '_ipv_license_key' );
        if ( empty( $license_key ) ) {
            return;
        }

        global $wpdb;

        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE license_key = %s",
            $license_key
        ) );

        if ( ! $license ) {
            $order->add_order_note( __( 'IPV: Licenza non trovata per aggiunta crediti.', 'ipv-pro-vendor' ) );
            return;
        }

        $plans_manager = IPV_Vendor_Plans_Manager::instance();
        $credits_manager = IPV_Vendor_Credits_Manager::instance();
        $total_credits_added = 0;
        $is_upgrade = $order->get_meta( '_ipv_is_upgrade' ) === 'yes';

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }

            $plan_slug = $product->get_meta( '_ipv_plan_slug' );
            $quantity = $item->get_quantity();

            if ( empty( $plan_slug ) ) {
                continue;
            }

            $plan = $plans_manager->get_plan( $plan_slug );
            if ( ! $plan ) {
                continue;
            }

            // Se è un upgrade di piano
            if ( $is_upgrade && in_array( $plan_slug, [ 'starter', 'professional', 'business' ] ) ) {
                $old_plan = $license->variant_slug;

                // Aggiorna il piano della licenza
                $wpdb->update(
                    $wpdb->prefix . 'ipv_licenses',
                    [
                        'variant_slug' => $plan_slug,
                        'credits_total' => $plan['credits'],
                        'credits_monthly' => $plan['credits'],
                        'credits_remaining' => $plan['credits'],
                        'activation_limit' => $plan['activations'],
                    ],
                    [ 'id' => $license->id ],
                    [ '%s', '%d', '%d', '%d', '%d' ],
                    [ '%d' ]
                );

                // Log nel ledger
                $wpdb->insert(
                    $wpdb->prefix . 'ipv_credit_ledger',
                    [
                        'license_key' => $license->license_key,
                        'type' => 'upgrade',
                        'amount' => $plan['credits'],
                        'balance_after' => $plan['credits'],
                        'ref_type' => 'order',
                        'ref_id' => $order_id,
                        'note' => sprintf( 'Upgrade da %s a %s', $old_plan, $plan_slug ),
                        'created_at' => current_time( 'mysql' ),
                    ],
                    [ '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s' ]
                );

                // Aggiorna oggetto licenza per notifica
                $license->variant_slug = $plan_slug;
                $license->credits_remaining = $plan['credits'];

                // Trigger notifica upgrade
                do_action( 'ipv_plan_upgraded', $license, $old_plan, $plan_slug );

                $order->add_order_note( sprintf(
                    __( 'IPV: Piano aggiornato da %s a %s. Crediti: %d', 'ipv-pro-vendor' ),
                    $old_plan,
                    $plan_slug,
                    $plan['credits']
                ) );

                continue;
            }

            // Se è acquisto crediti (Golden Prompt o Extra)
            if ( in_array( $plan_slug, $this->credit_product_slugs ) ) {
                $credits_to_add = $plan['credits'] * $quantity;

                $result = $credits_manager->add_credits(
                    $license,
                    $credits_to_add,
                    sprintf( 'Acquisto %s (Ordine #%d)', $plan['name'], $order_id ),
                    'purchase',
                    (string) $order_id
                );

                if ( ! is_wp_error( $result ) ) {
                    $total_credits_added += $credits_to_add;

                    // Refresh license
                    $license = $wpdb->get_row( $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE license_key = %s",
                        $license_key
                    ) );

                    // Trigger notifica Golden Prompt
                    if ( $plan_slug === 'golden_prompt' ) {
                        do_action( 'ipv_golden_prompt_purchased', $license, $order );
                    }

                    $order->add_order_note( sprintf(
                        __( 'IPV: Aggiunti %d crediti (%s) alla licenza %s', 'ipv-pro-vendor' ),
                        $credits_to_add,
                        $plan['name'],
                        $this->mask_license_key( $license_key )
                    ) );
                }
            }
        }

        // Marca come elaborato
        $order->update_meta_data( '_ipv_credits_processed', 'yes' );
        $order->save();

        if ( $total_credits_added > 0 ) {
            $order->add_order_note( sprintf(
                __( 'IPV: Totale crediti aggiunti: %d', 'ipv-pro-vendor' ),
                $total_credits_added
            ) );
        }
    }

    /**
     * Mostra licenza nell'admin ordine
     */
    public function display_license_in_admin( $order ) {
        $license_key = $order->get_meta( '_ipv_license_key' );

        if ( $license_key ) {
            echo '<p><strong>' . esc_html__( 'Licenza IPV Pro:', 'ipv-pro-vendor' ) . '</strong><br>';
            echo '<code>' . esc_html( $license_key ) . '</code></p>';

            if ( $order->get_meta( '_ipv_is_upgrade' ) === 'yes' ) {
                echo '<p><em>' . esc_html__( 'Ordine di Upgrade Piano', 'ipv-pro-vendor' ) . '</em></p>';
            }

            if ( $order->get_meta( '_ipv_credits_processed' ) === 'yes' ) {
                echo '<p style="color: green;">' . esc_html__( 'Crediti elaborati', 'ipv-pro-vendor' ) . '</p>';
            }
        }
    }

    /**
     * Aggiungi info licenza all'email ordine
     */
    public function add_license_to_email( $order, $sent_to_admin, $plain_text ) {
        $license_key = $order->get_meta( '_ipv_license_key' );

        if ( ! $license_key ) {
            return;
        }

        if ( $plain_text ) {
            echo "\n" . esc_html__( 'Licenza IPV Pro:', 'ipv-pro-vendor' ) . ' ' . esc_html( $license_key ) . "\n";
        } else {
            echo '<h2>' . esc_html__( 'Licenza IPV Pro', 'ipv-pro-vendor' ) . '</h2>';
            echo '<p>' . esc_html__( 'I crediti sono stati aggiunti alla licenza:', 'ipv-pro-vendor' ) . ' <strong>' . esc_html( $license_key ) . '</strong></p>';
        }
    }

    /**
     * Aggiungi campo slug piano al prodotto
     */
    public function add_product_plan_field() {
        woocommerce_wp_select([
            'id' => '_ipv_plan_slug',
            'label' => __( 'Piano IPV Pro', 'ipv-pro-vendor' ),
            'description' => __( 'Associa questo prodotto a un piano IPV Pro', 'ipv-pro-vendor' ),
            'desc_tip' => true,
            'options' => [
                '' => __( 'Nessuno', 'ipv-pro-vendor' ),
                'trial' => 'Trial',
                'starter' => 'Starter',
                'professional' => 'Professional',
                'business' => 'Business',
                'golden_prompt' => 'Golden Prompt',
                'extra_credits_10' => 'Crediti Extra 10',
                'extra_credits_100' => 'Crediti Extra 100',
            ],
        ]);
    }

    /**
     * Salva campo slug piano
     */
    public function save_product_plan_field( $post_id ) {
        $plan_slug = isset( $_POST['_ipv_plan_slug'] ) ? sanitize_text_field( $_POST['_ipv_plan_slug'] ) : '';
        update_post_meta( $post_id, '_ipv_plan_slug', $plan_slug );
    }

    /**
     * AJAX: Verifica licenza in tempo reale
     */
    public function ajax_verify_license() {
        $license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( $_POST['license_key'] ) : '';

        if ( empty( $license_key ) ) {
            wp_send_json_error( [ 'message' => __( 'Inserisci una License Key', 'ipv-pro-vendor' ) ] );
        }

        global $wpdb;

        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT l.*,
                    (SELECT name FROM {$wpdb->prefix}ipv_plans WHERE slug = l.variant_slug LIMIT 1) as plan_name
             FROM {$wpdb->prefix}ipv_licenses l
             WHERE l.license_key = %s",
            strtoupper( $license_key )
        ) );

        if ( ! $license ) {
            wp_send_json_error( [ 'message' => __( 'License Key non trovata', 'ipv-pro-vendor' ) ] );
        }

        if ( $license->status !== 'active' ) {
            wp_send_json_error( [
                'message' => sprintf( __( 'Licenza non attiva (stato: %s)', 'ipv-pro-vendor' ), $license->status )
            ] );
        }

        // Verifica proprietà se loggato
        if ( is_user_logged_in() ) {
            $current_user = wp_get_current_user();
            if ( $license->user_id && $license->user_id != get_current_user_id() &&
                 $license->email !== $current_user->user_email ) {
                wp_send_json_error( [
                    'message' => __( 'Questa licenza non appartiene al tuo account', 'ipv-pro-vendor' )
                ] );
            }
        }

        $plans_manager = IPV_Vendor_Plans_Manager::instance();
        $plan = $plans_manager->get_plan( $license->variant_slug );

        wp_send_json_success([
            'message' => __( 'Licenza valida', 'ipv-pro-vendor' ),
            'plan' => $plan['name'] ?? ucfirst( $license->variant_slug ),
            'credits' => $license->credits_remaining,
        ]);
    }

    /**
     * Enqueue script per checkout
     */
    public function enqueue_checkout_scripts() {
        if ( ! is_checkout() ) {
            return;
        }

        if ( ! $this->cart_needs_license() || $this->cart_has_upgrade_license() ) {
            return;
        }

        wp_add_inline_script( 'wc-checkout', "
            jQuery(document).ready(function($) {
                var verifyTimeout;

                $('#ipv_license_key').on('input', function() {
                    var key = $(this).val().trim();
                    var statusEl = $('#ipv-license-status');

                    clearTimeout(verifyTimeout);

                    if (key.length < 8) {
                        statusEl.html('');
                        return;
                    }

                    statusEl.html('<span style=\"color: #666;\">Verifica in corso...</span>');

                    verifyTimeout = setTimeout(function() {
                        $.ajax({
                            url: '" . admin_url( 'admin-ajax.php' ) . "',
                            type: 'POST',
                            data: {
                                action: 'ipv_verify_license',
                                license_key: key
                            },
                            success: function(response) {
                                if (response.success) {
                                    statusEl.html(
                                        '<span style=\"color: #28a745;\">✓ ' + response.data.message + '</span>' +
                                        '<br><small style=\"color: #666;\">Piano: ' + response.data.plan +
                                        ' | Crediti: ' + response.data.credits + '</small>'
                                    );
                                } else {
                                    statusEl.html('<span style=\"color: #dc3545;\">✗ ' + response.data.message + '</span>');
                                }
                            },
                            error: function() {
                                statusEl.html('<span style=\"color: #dc3545;\">Errore di connessione</span>');
                            }
                        });
                    }, 500);
                });
            });
        " );
    }
}

// Gestione licenza upgrade da URL
add_action( 'wp', function() {
    if ( is_checkout() && isset( $_GET['ipv_upgrade_license'] ) ) {
        $license_key = sanitize_text_field( $_GET['ipv_upgrade_license'] );

        // Verifica licenza
        global $wpdb;
        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE license_key = %s AND status = 'active'",
            $license_key
        ) );

        if ( $license ) {
            // Salva vecchio piano per notifica
            WC()->session->set( 'ipv_upgrade_license', $license_key );
            WC()->session->set( 'ipv_old_plan', $license->variant_slug );
        }
    }
});

// Salva vecchio piano nell'ordine
add_action( 'woocommerce_checkout_update_order_meta', function( $order_id ) {
    $old_plan = WC()->session->get( 'ipv_old_plan' );
    if ( $old_plan ) {
        update_post_meta( $order_id, '_ipv_old_plan', $old_plan );
        WC()->session->set( 'ipv_old_plan', null );
    }
}, 20 );

// Initialize
IPV_Vendor_WooCommerce_Integration::instance();
