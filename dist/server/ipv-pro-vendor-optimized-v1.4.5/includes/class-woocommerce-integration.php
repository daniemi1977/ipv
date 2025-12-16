<?php
/**
 * IPV WooCommerce Integration
 *
 * Integra il sistema licenze con WooCommerce e Subscriptions
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_WooCommerce_Integration {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        // Quando ordine completato/processing
        add_action( 'woocommerce_order_status_completed', [ $this, 'on_order_completed' ] );
        add_action( 'woocommerce_order_status_processing', [ $this, 'on_order_completed' ] );

        // Subscription hooks
        add_action( 'woocommerce_subscription_status_active', [ $this, 'on_subscription_active' ] );
        add_action( 'woocommerce_subscription_status_cancelled', [ $this, 'on_subscription_cancelled' ] );
        add_action( 'woocommerce_subscription_status_expired', [ $this, 'on_subscription_cancelled' ] );
        add_action( 'woocommerce_subscription_status_on-hold', [ $this, 'on_subscription_on_hold' ] );

        // Product fields (admin)
        add_action( 'woocommerce_product_options_general_product_data', [ $this, 'add_product_fields' ] );
        add_action( 'woocommerce_process_product_meta', [ $this, 'save_product_fields' ] );

        // Add license info to order emails
        add_action( 'woocommerce_email_after_order_table', [ $this, 'add_license_to_email' ], 10, 2 );

        // Add license column to My Account > Orders
        add_filter( 'woocommerce_my_account_my_orders_columns', [ $this, 'add_license_column_to_orders' ] );
        add_action( 'woocommerce_my_account_my_orders_column_license-key', [ $this, 'display_license_in_orders' ] );

        // Add license info to order details page
        add_action( 'woocommerce_order_details_after_order_table', [ $this, 'display_license_in_order_details' ] );
    }

    /**
     * On order completed - generate license
     */
    public function on_order_completed( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Check if already has license
        if ( $order->get_meta( '_ipv_license_key' ) ) {
            error_log( 'IPV Vendor: Order ' . $order_id . ' already has license, skipping' );
            return; // Already generated
        }

        // Generate license for each IPV Pro product in order
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $product = wc_get_product( $product_id );

            if ( ! $product ) {
                continue;
            }

            // Check if it's an IPV Pro product
            $is_ipv_product = get_post_meta( $product_id, '_ipv_is_license_product', true );
            if ( $is_ipv_product !== 'yes' ) {
                continue;
            }

            // Generate license
            $license_manager = IPV_Vendor_License_Manager::instance();
            $license_key = $license_manager->create_license_from_order( $order_id, $product_id );

            if ( $license_key ) {
                error_log( sprintf(
                    'IPV Vendor: License %s created for order %d (product: %s)',
                    $license_key,
                    $order_id,
                    $product->get_name()
                ));

                // Add order note
                $order->add_order_note( sprintf(
                    'IPV Pro License generata: %s',
                    $license_key
                ));
            } else {
                error_log( 'IPV Vendor: Failed to create license for order ' . $order_id );

                // Add order note
                $order->add_order_note( 'ERRORE: Generazione license IPV Pro fallita' );
            }
        }
    }

    /**
     * On subscription active
     */
    public function on_subscription_active( $subscription ) {
        $parent_order_id = $subscription->get_parent_id();
        if ( ! $parent_order_id ) {
            return;
        }

        error_log( sprintf(
            'IPV Vendor: Subscription %d activated, checking parent order %d',
            $subscription->get_id(),
            $parent_order_id
        ));

        // Generate license if not exists
        $this->on_order_completed( $parent_order_id );

        // If license exists and was cancelled, reactivate it
        $order = wc_get_order( $parent_order_id );
        if ( $order ) {
            $license_key = $order->get_meta( '_ipv_license_key' );
            if ( $license_key ) {
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'ipv_licenses',
                    [ 'status' => 'active' ],
                    [ 'license_key' => $license_key ],
                    [ '%s' ],
                    [ '%s' ]
                );

                error_log( 'IPV Vendor: License ' . $license_key . ' reactivated' );
            }
        }
    }

    /**
     * On subscription cancelled/expired
     */
    public function on_subscription_cancelled( $subscription ) {
        $parent_order_id = $subscription->get_parent_id();
        if ( ! $parent_order_id ) {
            return;
        }

        $order = wc_get_order( $parent_order_id );
        if ( ! $order ) {
            return;
        }

        $license_key = $order->get_meta( '_ipv_license_key' );
        if ( ! $license_key ) {
            return;
        }

        // Cancel license
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'ipv_licenses',
            [ 'status' => 'cancelled' ],
            [ 'license_key' => $license_key ],
            [ '%s' ],
            [ '%s' ]
        );

        error_log( sprintf(
            'IPV Vendor: License %s cancelled (subscription %d)',
            $license_key,
            $subscription->get_id()
        ));

        // Add order note
        $order->add_order_note( sprintf(
            'IPV Pro License cancellata: %s (subscription %s)',
            $license_key,
            $subscription->get_status()
        ));
    }

    /**
     * On subscription on-hold
     */
    public function on_subscription_on_hold( $subscription ) {
        $parent_order_id = $subscription->get_parent_id();
        if ( ! $parent_order_id ) {
            return;
        }

        $order = wc_get_order( $parent_order_id );
        if ( ! $order ) {
            return;
        }

        $license_key = $order->get_meta( '_ipv_license_key' );
        if ( ! $license_key ) {
            return;
        }

        // Put license on hold (block usage but don't cancel)
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'ipv_licenses',
            [ 'status' => 'on-hold' ],
            [ 'license_key' => $license_key ],
            [ '%s' ],
            [ '%s' ]
        );

        error_log( sprintf(
            'IPV Vendor: License %s on-hold (subscription %d)',
            $license_key,
            $subscription->get_id()
        ));
    }

    /**
     * Add custom fields to product edit screen
     */
    public function add_product_fields() {
        global $post;

        // v1.3.1 - Check if WooCommerce functions are available
        if ( ! function_exists( 'woocommerce_wp_checkbox' ) ||
             ! function_exists( 'woocommerce_wp_select' ) ||
             ! function_exists( 'woocommerce_wp_text_input' ) ) {

            // Load WooCommerce meta box functions if not loaded
            $wc_functions_file = WP_PLUGIN_DIR . '/woocommerce/includes/admin/wc-meta-box-functions.php';
            if ( file_exists( $wc_functions_file ) ) {
                include_once $wc_functions_file;
            } else {
                echo '<div class="options_group" style="padding: 12px;"><p style="color: red;">⚠️ WooCommerce meta box functions not available. Please ensure WooCommerce is active.</p></div>';
                return;
            }
        }

        echo '<div class="options_group ipv_license_fields" style="border-top: 1px solid #eee; margin-top: 12px; padding-top: 12px;">';

        echo '<h4 style="padding-left: 12px; color: #667eea;">⚙️ IPV Pro License Settings</h4>';

        // Enable license generation
        woocommerce_wp_checkbox([
            'id' => '_ipv_is_license_product',
            'label' => __( 'IPV Pro License Product', 'ipv-pro-vendor' ),
            'description' => __( 'Genera automaticamente license key all\'acquisto', 'ipv-pro-vendor' )
        ]);

        // Plan slug - dinamico dal Plans Manager
        $plan_options = apply_filters( 'ipv_vendor_plan_options', [] );
        woocommerce_wp_select([
            'id' => '_ipv_plan_slug',
            'label' => __( 'Piano IPV Pro', 'ipv-pro-vendor' ),
            'options' => $plan_options,
            'desc_tip' => true,
            'description' => __( 'Seleziona il piano IPV Pro corrispondente. Configura i piani in IPV Pro Vendor → Piani SaaS', 'ipv-pro-vendor' )
        ]);

        // Credits total - v1.4.2-FIXED10: Etichetta generica per tutti i tipi di piano
        woocommerce_wp_text_input([
            'id' => '_ipv_credits_total',
            'label' => __( 'Crediti Totali', 'ipv-pro-vendor' ),
            'desc_tip' => true,
            'description' => __( 'Numero di crediti inclusi nel piano (mensili per subscription, una tantum per trial/extra)', 'ipv-pro-vendor' ),
            'type' => 'number',
            'custom_attributes' => [
                'step' => '1',
                'min' => '0'
            ]
        ]);

        // Activation limit
        woocommerce_wp_text_input([
            'id' => '_ipv_activation_limit',
            'label' => __( 'Limite Attivazioni', 'ipv-pro-vendor' ),
            'desc_tip' => true,
            'description' => __( 'Numero massimo di siti dove può essere attivata la licenza', 'ipv-pro-vendor' ),
            'type' => 'number',
            'custom_attributes' => [
                'step' => '1',
                'min' => '1'
            ]
        ]);

        echo '</div>';
    }

    /**
     * Save custom product fields
     */
    public function save_product_fields( $post_id ) {
        $is_license = isset( $_POST['_ipv_is_license_product'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, '_ipv_is_license_product', $is_license );

        if ( isset( $_POST['_ipv_plan_slug'] ) ) {
            update_post_meta( $post_id, '_ipv_plan_slug', sanitize_text_field( $_POST['_ipv_plan_slug'] ) );
        }

        if ( isset( $_POST['_ipv_credits_total'] ) ) {
            update_post_meta( $post_id, '_ipv_credits_total', absint( $_POST['_ipv_credits_total'] ) );
        }

        if ( isset( $_POST['_ipv_activation_limit'] ) ) {
            update_post_meta( $post_id, '_ipv_activation_limit', absint( $_POST['_ipv_activation_limit'] ) );
        }
    }

    /**
     * Add license to order email
     */
    public function add_license_to_email( $order, $sent_to_admin ) {
        if ( $sent_to_admin ) {
            return; // Don't show to admin
        }

        $license_key = $order->get_meta( '_ipv_license_key' );
        if ( ! $license_key ) {
            return;
        }

        $download_url = wp_nonce_url(
            home_url( '/?download-ipv-pro=1&license=' . $license_key ),
            'download_ipv_pro'
        );

        echo '<div style="margin-top: 30px; padding: 20px; background: #f5f5f5; border: 2px solid #667eea; border-radius: 5px;">';
        echo '<h2 style="color: #667eea; margin-top: 0;">🔑 La tua License Key IPV Pro</h2>';
        echo '<p style="font-size: 24px; font-weight: bold; letter-spacing: 2px; color: #333; font-family: monospace; background: white; padding: 15px; border-radius: 3px; text-align: center;">' . esc_html( $license_key ) . '</p>';
        echo '<p style="margin-bottom: 0;"><a href="' . esc_url( $download_url ) . '" style="background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">📥 Scarica Plugin</a></p>';
        echo '</div>';
    }

    /**
     * Add license column to My Account > Orders
     */
    public function add_license_column_to_orders( $columns ) {
        $new_columns = [];

        foreach ( $columns as $key => $name ) {
            $new_columns[ $key ] = $name;

            // Add license column after "Total"
            if ( $key === 'order-total' ) {
                $new_columns['license-key'] = __( 'License Key', 'ipv-pro-vendor' );
            }
        }

        return $new_columns;
    }

    /**
     * Display license in My Account > Orders
     */
    public function display_license_in_orders( $order ) {
        $license_key = $order->get_meta( '_ipv_license_key' );

        if ( $license_key ) {
            echo '<code style="background: #f5f5f5; padding: 5px 10px; border-radius: 3px; font-size: 12px;">' . esc_html( $license_key ) . '</code>';
        } else {
            echo '—';
        }
    }

    /**
     * Display license in order details page
     */
    public function display_license_in_order_details( $order ) {
        $license_key = $order->get_meta( '_ipv_license_key' );
        if ( ! $license_key ) {
            return;
        }

        $download_url = wp_nonce_url(
            home_url( '/?download-ipv-pro=1&license=' . $license_key ),
            'download_ipv_pro'
        );

        // Get license info from database
        global $wpdb;
        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE license_key = %s",
            $license_key
        ));

        if ( ! $license ) {
            return;
        }

        $credits_manager = IPV_Vendor_Credits_Manager::instance();
        $credits_info = $credits_manager->get_credits_info( $license );

        ?>
        <section class="woocommerce-ipv-license" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border: 1px solid #e5e5e5; border-radius: 5px;">
            <h2 class="woocommerce-column__title" style="color: #667eea;">🔑 IPV Pro License</h2>

            <table class="woocommerce-table shop_table ipv_license_details">
                <tbody>
                    <tr>
                        <th>License Key:</th>
                        <td>
                            <code style="background: white; padding: 8px 12px; border-radius: 3px; font-size: 16px; letter-spacing: 1px; display: inline-block;"><?php echo esc_html( $license_key ); ?></code>
                        </td>
                    </tr>
                    <tr>
                        <th>Piano:</th>
                        <td><strong><?php echo esc_html( ucfirst( $license->variant_slug ) ); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <?php
                            $status_labels = [
                                'active' => '<span style="color: #46b450;">✓ Attiva</span>',
                                'cancelled' => '<span style="color: #dc3232;">✗ Cancellata</span>',
                                'expired' => '<span style="color: #dc3232;">✗ Scaduta</span>',
                                'on-hold' => '<span style="color: #ffb900;">⊗ Sospesa</span>'
                            ];
                            echo $status_labels[ $license->status ] ?? esc_html( $license->status );
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Crediti:</th>
                        <td>
                            <strong><?php echo esc_html( $credits_info['credits_remaining'] ); ?></strong> / <?php echo esc_html( $credits_info['credits_total'] ); ?>
                            (<?php echo esc_html( $credits_info['percentage'] ); ?>%)
                        </td>
                    </tr>
                    <tr>
                        <th>Reset crediti:</th>
                        <td><?php echo esc_html( $credits_info['reset_date_formatted'] ); ?></td>
                    </tr>
                    <tr>
                        <th>Attivazioni:</th>
                        <td><?php echo esc_html( $license->activation_count ); ?> / <?php echo esc_html( $license->activation_limit ); ?></td>
                    </tr>
                    <?php if ( $license->expires_at ) : ?>
                    <tr>
                        <th>Scadenza:</th>
                        <td><?php echo date_i18n( 'd/m/Y', strtotime( $license->expires_at ) ); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <p style="margin-top: 20px;">
                <a href="<?php echo esc_url( $download_url ); ?>" class="button" style="background: #667eea; color: white; border: none;">
                    📥 Scarica Plugin
                </a>
                <a href="<?php echo esc_url( home_url( '/docs/ipv-pro/' ) ); ?>" class="button" style="margin-left: 10px;">
                    📖 Documentazione
                </a>
            </p>
        </section>
        <?php
    }
}
