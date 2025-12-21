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

        // Checkout: License selector for addons/upgrades
        add_action( 'woocommerce_before_order_notes', [ $this, 'add_license_selector_to_checkout' ] );
        add_action( 'woocommerce_checkout_process', [ $this, 'validate_license_selector' ] );
        add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'save_license_selector' ] );
    }

    /**
     * On order completed - generate/update license
     */
    public function on_order_completed( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Check if already processed
        if ( $order->get_meta( '_ipv_license_processed' ) ) {
            error_log( 'IPV Vendor: Order ' . $order_id . ' already processed, skipping' );
            return;
        }

        // Process each IPV Pro product in order
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

            // Get license behavior
            $behavior = get_post_meta( $product_id, '_ipv_license_behavior', true );
            if ( empty( $behavior ) ) {
                $behavior = 'new'; // Default
            }

            $license_manager = IPV_Vendor_License_Manager::instance();
            $user_id = $order->get_user_id();
            $user_email = $order->get_billing_email();

            switch ( $behavior ) {
                case 'addon':
                    // Add-on: Associate to existing license
                    $result = $this->process_addon( $order, $product_id, $user_id, $user_email );
                    break;

                case 'upgrade':
                    // Upgrade/Downgrade: Modify existing license plan
                    $result = $this->process_upgrade( $order, $product_id, $user_id, $user_email );
                    break;

                case 'new':
                default:
                    // New license: Create new
                    $result = $license_manager->create_license_from_order( $order_id, $product_id );
                    break;
            }

            if ( $result ) {
                $order->add_order_note( sprintf(
                    'IPV Pro: %s processato con successo (%s)',
                    $product->get_name(),
                    $behavior
                ));
            } else {
                $order->add_order_note( sprintf(
                    'ERRORE IPV Pro: %s non processato',
                    $product->get_name()
                ));
            }
        }

        // Mark as processed
        $order->update_meta_data( '_ipv_license_processed', 'yes' );
        $order->save();
    }

    /**
     * Process Add-on purchase (Golden Prompt, Extra Credits, etc.)
     */
    private function process_addon( $order, $product_id, $user_id, $user_email ) {
        global $wpdb;

        $addon_type = get_post_meta( $product_id, '_ipv_addon_type', true );
        $credits_to_add = (int) get_post_meta( $product_id, '_ipv_credits_total', true );

        // Check if user selected a specific license at checkout
        $selected_license = $order->get_meta( '_ipv_target_license' );

        if ( $selected_license ) {
            // Use selected license
            $license = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE license_key = %s",
                $selected_license
            ));
        } else {
            // Find user's active license (most recent)
            $license = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ipv_licenses 
                WHERE (user_id = %d OR email = %s) AND status = 'active'
                ORDER BY created_at DESC LIMIT 1",
                $user_id,
                $user_email
            ));
        }

        if ( ! $license ) {
            error_log( 'IPV Vendor: No active license found for user ' . $user_id . ' to attach addon' );
            $order->add_order_note( '⚠️ Add-on acquistato ma nessuna licenza attiva trovata per l\'utente. Creare prima una licenza base.' );
            return false;
        }

        // Process based on addon type
        switch ( $addon_type ) {
            case 'golden_prompt':
                // ✅ Enable Golden Prompt for license (cliente configurerà dal suo pannello)
                global $wpdb;
                $wpdb->replace(
                    $wpdb->prefix . 'ipv_golden_prompts',
                    [
                        'license_id' => $license->id,
                        'config_json' => '{}',
                        'golden_prompt' => '',
                        'is_active' => 1
                    ],
                    [ '%d', '%s', '%s', '%d' ]
                );

                error_log( sprintf(
                    'IPV Vendor: Golden Prompt ENABLED for license %s (order %d) - Cliente configurerà dal suo pannello WordPress',
                    $license->license_key,
                    $order->get_id()
                ));

                $order->add_order_note( sprintf(
                    '✅ Golden Prompt attivato per licenza: %s<br>' .
                    '📋 Il cliente configurerà il Golden Prompt dal suo pannello WordPress:<br>' .
                    'IPV Videos → ✨ Golden Prompt Settings',
                    $license->license_key
                ));
                break;

            case 'extra_credits':
                // Add extra credits to license
                $new_credits_extra = (int) $license->credits_extra + $credits_to_add;
                $new_credits_remaining = (int) $license->credits_remaining + $credits_to_add;

                $wpdb->update(
                    $wpdb->prefix . 'ipv_licenses',
                    [
                        'credits_extra' => $new_credits_extra,
                        'credits_remaining' => $new_credits_remaining
                    ],
                    [ 'id' => $license->id ],
                    [ '%d', '%d' ],
                    [ '%d' ]
                );

                error_log( sprintf(
                    'IPV Vendor: Added %d extra credits to license %s (order %d)',
                    $credits_to_add,
                    $license->license_key,
                    $order->get_id()
                ));

                $order->add_order_note( sprintf(
                    '✅ Aggiunti %d crediti extra alla licenza: %s',
                    $credits_to_add,
                    $license->license_key
                ));
                break;

            case 'extra_sites':
                // Add extra site activations
                $sites_to_add = max( 1, $credits_to_add ); // Use credits field for sites count
                $new_limit = (int) $license->activation_limit + $sites_to_add;

                $wpdb->update(
                    $wpdb->prefix . 'ipv_licenses',
                    [ 'activation_limit' => $new_limit ],
                    [ 'id' => $license->id ],
                    [ '%d' ],
                    [ '%d' ]
                );

                error_log( sprintf(
                    'IPV Vendor: Added %d extra sites to license %s (order %d)',
                    $sites_to_add,
                    $license->license_key,
                    $order->get_id()
                ));

                $order->add_order_note( sprintf(
                    '✅ Aggiunti %d siti extra alla licenza: %s (nuovo limite: %d)',
                    $sites_to_add,
                    $license->license_key,
                    $new_limit
                ));
                break;
        }

        // Store which license was updated
        $order->update_meta_data( '_ipv_license_key', $license->license_key );
        $order->update_meta_data( '_ipv_addon_type', $addon_type );
        $order->save();

        return true;
    }

    /**
     * Process Upgrade/Downgrade purchase
     */
    private function process_upgrade( $order, $product_id, $user_id, $user_email ) {
        global $wpdb;

        // Get new plan details
        $new_plan = get_post_meta( $product_id, '_ipv_plan_slug', true );
        $new_credits = (int) get_post_meta( $product_id, '_ipv_credits_total', true );
        $new_activation_limit = (int) get_post_meta( $product_id, '_ipv_activation_limit', true );

        if ( empty( $new_plan ) ) {
            error_log( 'IPV Vendor: No plan specified for upgrade product ' . $product_id );
            return false;
        }

        // Check if user selected a specific license at checkout
        $selected_license = $order->get_meta( '_ipv_target_license' );

        if ( $selected_license ) {
            $license = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE license_key = %s",
                $selected_license
            ));
        } else {
            // Find user's active license (most recent)
            $license = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ipv_licenses 
                WHERE (user_id = %d OR email = %s) AND status = 'active'
                ORDER BY created_at DESC LIMIT 1",
                $user_id,
                $user_email
            ));
        }

        if ( ! $license ) {
            error_log( 'IPV Vendor: No active license found for user ' . $user_id . ' to upgrade' );
            $order->add_order_note( '⚠️ Upgrade acquistato ma nessuna licenza attiva trovata. Creare prima una licenza base.' );
            return false;
        }

        $old_plan = $license->variant_slug;

        // Update license with new plan
        $update_data = [
            'variant_slug' => $new_plan,
            'credits_total' => $new_credits,
            'credits_monthly' => $new_credits,
        ];

        // Only update activation limit if specified and greater than current
        if ( $new_activation_limit > 0 ) {
            $update_data['activation_limit'] = max( $license->activation_limit, $new_activation_limit );
        }

        // For upgrades, add credits difference immediately
        $credits_diff = $new_credits - (int) $license->credits_total;
        if ( $credits_diff > 0 ) {
            $update_data['credits_remaining'] = (int) $license->credits_remaining + $credits_diff;
        }

        $wpdb->update(
            $wpdb->prefix . 'ipv_licenses',
            $update_data,
            [ 'id' => $license->id ],
            array_fill( 0, count( $update_data ), '%d' ),
            [ '%d' ]
        );

        // Fix the format specifiers
        $format = [];
        foreach ( $update_data as $key => $value ) {
            $format[] = is_numeric( $value ) ? '%d' : '%s';
        }

        $wpdb->update(
            $wpdb->prefix . 'ipv_licenses',
            $update_data,
            [ 'id' => $license->id ],
            $format,
            [ '%d' ]
        );

        error_log( sprintf(
            'IPV Vendor: License %s upgraded from %s to %s (order %d)',
            $license->license_key,
            $old_plan,
            $new_plan,
            $order->get_id()
        ));

        $order->add_order_note( sprintf(
            '✅ Licenza %s aggiornata: %s → %s (crediti: %d)',
            $license->license_key,
            $old_plan,
            $new_plan,
            $new_credits
        ));

        // Store which license was updated
        $order->update_meta_data( '_ipv_license_key', $license->license_key );
        $order->update_meta_data( '_ipv_upgrade_from', $old_plan );
        $order->update_meta_data( '_ipv_upgrade_to', $new_plan );
        $order->save();

        return true;
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

        echo '<hr style="margin: 15px 0;">';
        echo '<p style="font-weight: bold; color: #667eea;">⚙️ Comportamento Licenza</p>';

        // Product Type - NEW/ADDON/UPGRADE
        woocommerce_wp_select([
            'id' => '_ipv_license_behavior',
            'label' => __( 'Tipo Operazione', 'ipv-pro-vendor' ),
            'options' => [
                'new' => 'Nuova Licenza (crea nuova)',
                'addon' => 'Add-on (associa a licenza esistente)',
                'upgrade' => 'Upgrade/Downgrade (modifica piano esistente)',
            ],
            'desc_tip' => true,
            'description' => __( 'Nuova = crea licenza. Add-on = aggiunge funzionalità a licenza esistente. Upgrade = cambia piano licenza esistente.', 'ipv-pro-vendor' )
        ]);

        // Add-on type (only for addons)
        woocommerce_wp_select([
            'id' => '_ipv_addon_type',
            'label' => __( 'Tipo Add-on', 'ipv-pro-vendor' ),
            'options' => [
                '' => '-- Seleziona --',
                'golden_prompt' => 'Golden Prompt',
                'extra_credits' => 'Crediti Extra',
                'extra_sites' => 'Siti Extra',
            ],
            'desc_tip' => true,
            'description' => __( 'Solo per prodotti Add-on: specifica quale funzionalità aggiunge', 'ipv-pro-vendor' )
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

        // NEW: License behavior
        if ( isset( $_POST['_ipv_license_behavior'] ) ) {
            update_post_meta( $post_id, '_ipv_license_behavior', sanitize_text_field( $_POST['_ipv_license_behavior'] ) );
        }

        // NEW: Add-on type
        if ( isset( $_POST['_ipv_addon_type'] ) ) {
            update_post_meta( $post_id, '_ipv_addon_type', sanitize_text_field( $_POST['_ipv_addon_type'] ) );
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

    /**
     * Add license selector to checkout for addons/upgrades
     */
    public function add_license_selector_to_checkout( $checkout ) {
        // Check if cart contains addon or upgrade products
        $needs_license_selector = false;
        $has_golden_prompt = false;
        $addon_product_name = '';

        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $product_id = $cart_item['product_id'];
            $behavior = get_post_meta( $product_id, '_ipv_license_behavior', true );
            $addon_type = get_post_meta( $product_id, '_ipv_addon_type', true );
            
            if ( in_array( $behavior, [ 'addon', 'upgrade' ] ) ) {
                $needs_license_selector = true;
                $product = wc_get_product( $product_id );
                $addon_product_name = $product ? $product->get_name() : '';
                
                // Check if it's Golden Prompt addon
                if ( $addon_type === 'golden_prompt' ) {
                    $has_golden_prompt = true;
                }
                break;
            }
        }

        if ( ! $needs_license_selector ) {
            return;
        }

        // Get user's active licenses
        $user_id = get_current_user_id();
        
        global $wpdb;
        $licenses = [];
        
        if ( $user_id ) {
            $licenses = $wpdb->get_results( $wpdb->prepare(
                "SELECT license_key, variant_slug, credits_remaining, credits_total, created_at
                FROM {$wpdb->prefix}ipv_licenses 
                WHERE user_id = %d AND status = 'active'
                ORDER BY created_at DESC",
                $user_id
            ));
        }

        // Start output
        echo '<div id="ipv-license-selector-wrapper" style="padding: 20px; background: #f8f9fa; border: 2px solid #667eea; border-radius: 8px; margin-bottom: 20px;">';
        echo '<h3 style="margin-top: 0; color: #667eea;">🔑 Seleziona la tua Licenza IPV Pro <abbr class="required" title="obbligatorio">*</abbr></h3>';
        
        if ( ! empty( $addon_product_name ) ) {
            echo '<p style="margin-bottom: 15px;">Stai acquistando <strong>' . esc_html( $addon_product_name ) . '</strong>. Indica la licenza a cui associarlo:</p>';
        }

        if ( empty( $licenses ) ) {
            // No licenses found - show error and block
            echo '<div class="woocommerce-error" style="padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;">';
            echo '<strong>⚠️ Attenzione:</strong> Non hai ancora una licenza IPV Pro attiva!<br>';
            echo 'Per acquistare questo add-on devi prima avere una licenza base.<br><br>';
            echo '<a href="' . esc_url( home_url( '/shop/' ) ) . '" class="button" style="background: #667eea; color: white;">Acquista una Licenza Base</a>';
            echo '</div>';
            echo '<input type="hidden" name="ipv_target_license" value="">';
            echo '<input type="hidden" name="ipv_no_license_error" value="1">';
        } elseif ( count( $licenses ) === 1 ) {
            // Only one license - auto-select but show it
            echo '<div style="padding: 15px; background: #d4edda; border: 1px solid #28a745; border-radius: 5px;">';
            echo '<strong>✅ La tua licenza:</strong><br>';
            echo '<code style="font-size: 16px; background: white; padding: 8px 12px; display: inline-block; margin-top: 8px; border-radius: 4px;">' . esc_html( $licenses[0]->license_key ) . '</code>';
            echo ' <span style="color: #666;">(Piano: ' . esc_html( ucfirst( $licenses[0]->variant_slug ) ) . ')</span>';
            echo '</div>';
            echo '<input type="hidden" name="ipv_target_license" value="' . esc_attr( $licenses[0]->license_key ) . '">';
        } else {
            // Multiple licenses - show selector (required)
            echo '<p style="color: #666; margin-bottom: 10px;">Hai più licenze attive. Seleziona quella da aggiornare:</p>';
            
            echo '<select name="ipv_target_license" id="ipv_target_license" class="select" required style="width: 100%; padding: 12px; font-size: 14px; border: 2px solid #667eea; border-radius: 5px;">';
            echo '<option value="">-- Seleziona una licenza --</option>';
            
            foreach ( $licenses as $license ) {
                $created = date_i18n( 'd/m/Y', strtotime( $license->created_at ) );
                $label = sprintf(
                    '%s - Piano: %s - Crediti: %d/%d',
                    $license->license_key,
                    ucfirst( $license->variant_slug ),
                    $license->credits_remaining,
                    $license->credits_total
                );
                echo '<option value="' . esc_attr( $license->license_key ) . '">' . esc_html( $label ) . '</option>';
            }
            
            echo '</select>';
        }
        
        echo '</div>';
    }
    
    /**
     * Save selected license to order meta
     */
    public function save_license_selector( $order_id ) {
        $order = wc_get_order( $order_id );
        
        if ( ! empty( $_POST['ipv_target_license'] ) ) {
            $order->update_meta_data( '_ipv_target_license', sanitize_text_field( $_POST['ipv_target_license'] ) );
        }
        
        $order->save();
    }
}
