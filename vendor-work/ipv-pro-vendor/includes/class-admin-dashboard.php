<?php
/**
 * IPV Admin Dashboard
 *
 * Dashboard amministrativa per gestione licenze, analytics, stats
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Admin_Dashboard {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'redirect_legacy_urls' ] ); // v1.4.1-FIXED3
        add_action( 'admin_init', [ $this, 'handle_admin_actions' ] ); // v1.4.7
        add_action( 'admin_notices', [ $this, 'upgrade_notices' ] ); // v1.9.2
    }

    /**
     * Show upgrade notices (v1.9.2)
     */
    public function upgrade_notices() {
        $upgrade_failed = get_transient('ipv_vendor_upgrade_failed');
        
        if ($upgrade_failed && current_user_can('manage_options')) {
            $table = $upgrade_failed['table'];
            $error = $upgrade_failed['error'];
            $version = $upgrade_failed['version'];
            
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>IPV Vendor - Database Upgrade Required!</strong></p>';
            echo '<p>The automatic upgrade to v' . esc_html($version) . ' failed. Please run this SQL manually:</p>';
            echo '<pre style="background: #f0f0f1; padding: 10px; overflow-x: auto;">ALTER TABLE `' . esc_html($table) . '` ADD COLUMN `domain` varchar(255) NULL AFTER `email`;</pre>';
            echo '<p><strong>Error:</strong> ' . esc_html($error) . '</p>';
            echo '<p><a href="' . admin_url('admin.php?page=ipv-vendor-dashboard&dismiss_upgrade_notice=1') . '" class="button">I have run the SQL - Dismiss</a></p>';
            echo '</div>';
        }
        
        // Dismiss notice
        if (isset($_GET['dismiss_upgrade_notice']) && current_user_can('manage_options')) {
            delete_transient('ipv_vendor_upgrade_failed');
            wp_redirect(admin_url('admin.php?page=ipv-vendor-dashboard'));
            exit;
        }
    }

    /**
     * Redirect legacy URLs (v1.4.1-FIXED3)
     */
    public function redirect_legacy_urls() {
        // Redirect page=ipv-vendor to page=ipv-vendor-dashboard
        if (isset($_GET['page']) && $_GET['page'] === 'ipv-vendor') {
            wp_safe_redirect(admin_url('admin.php?page=ipv-vendor-dashboard'));
            exit;
        }
    }

    /**
     * Handle admin actions (v1.4.7)
     * Toggle Golden prompt status
     */
    public function handle_admin_actions() {
        global $wpdb;

        // Toggle Golden prompt
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'toggle_golden' && isset( $_GET['id'] ) ) {
            $license_id = absint( $_GET['id'] );

            // Verify nonce
            if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'toggle_golden_' . $license_id ) ) {
                wp_die( 'Security check failed' );
            }

            // Check permissions
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( 'Insufficient permissions' );
            }

            // Get license
            $license = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE id = %d",
                $license_id
            ));

            if ( ! $license || $license->variant_slug !== 'golden_prompt' ) {
                wp_die( 'Invalid license or not a Golden prompt license' );
            }

            // Get current status
            $current_status = $wpdb->get_var( $wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
                WHERE license_id = %d AND meta_key = '_golden_prompt_enabled'",
                $license_id
            ));

            // Toggle status
            $new_status = $current_status ? 0 : 1;

            // Update metadata
            $wpdb->query( $wpdb->prepare(
                "INSERT INTO {$wpdb->prefix}ipv_license_meta (license_id, meta_key, meta_value)
                VALUES (%d, '_golden_prompt_enabled', %d)
                ON DUPLICATE KEY UPDATE meta_value = %d",
                $license_id,
                $new_status,
                $new_status
            ));

            // Save timestamp
            $wpdb->query( $wpdb->prepare(
                "INSERT INTO {$wpdb->prefix}ipv_license_meta (license_id, meta_key, meta_value)
                VALUES (%d, '_golden_prompt_toggled_at', %s)
                ON DUPLICATE KEY UPDATE meta_value = %s",
                $license_id,
                current_time( 'mysql' ),
                current_time( 'mysql' )
            ));

            // Redirect back with success message
            wp_safe_redirect( add_query_arg( [
                'page' => 'ipv-vendor-licenses',
                'golden_toggled' => $new_status
            ], admin_url( 'admin.php' ) ) );
            exit;
        }

        // Generate Golden Prompt
        if ( isset( $_POST['ipv_generate_golden_submit'] ) && isset( $_POST['license_id'] ) ) {
            $license_id = absint( $_POST['license_id'] );

            // Verify nonce
            check_admin_referer( 'ipv_generate_golden_' . $license_id );

            // Check permissions
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( 'Insufficient permissions' );
            }

            // Get license
            $license = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE id = %d",
                $license_id
            ));

            if ( ! $license || $license->variant_slug !== 'golden_prompt' ) {
                wp_die( 'Invalid license or not a Golden prompt license' );
            }

            // Get form data
            $channel_name = sanitize_text_field( $_POST['channel_name'] ?? '' );
            $telegram = esc_url_raw( $_POST['telegram'] ?? '' );
            $facebook = esc_url_raw( $_POST['facebook'] ?? '' );
            $instagram = esc_url_raw( $_POST['instagram'] ?? '' );
            $website = esc_url_raw( $_POST['website'] ?? '' );
            $donations = esc_url_raw( $_POST['donations'] ?? '' );
            $sponsor_name = sanitize_text_field( $_POST['sponsor_name'] ?? '' );
            $sponsor_link = esc_url_raw( $_POST['sponsor_link'] ?? '' );
            $support_text = sanitize_textarea_field( $_POST['support_text'] ?? '• Lascia un like\n• Commenta\n• Condividi il video' );

            // Save metadata
            $metadata = [
                '_golden_channel_name' => $channel_name,
                '_golden_telegram' => $telegram,
                '_golden_facebook' => $facebook,
                '_golden_instagram' => $instagram,
                '_golden_website' => $website,
                '_golden_donations' => $donations,
                '_golden_sponsor_name' => $sponsor_name,
                '_golden_sponsor_link' => $sponsor_link,
                '_golden_support_text' => $support_text,
            ];

            foreach ( $metadata as $meta_key => $meta_value ) {
                $wpdb->query( $wpdb->prepare(
                    "INSERT INTO {$wpdb->prefix}ipv_license_meta (license_id, meta_key, meta_value)
                    VALUES (%d, %s, %s)
                    ON DUPLICATE KEY UPDATE meta_value = %s",
                    $license_id,
                    $meta_key,
                    $meta_value,
                    $meta_value
                ));
            }

            // Generate Golden Prompt file
            $this->generate_golden_prompt_file( $license_id, $metadata );

            // Redirect back with success message
            wp_safe_redirect( add_query_arg( [
                'page' => 'ipv-vendor-configure-golden',
                'license_id' => $license_id,
                'golden_generated' => 1
            ], admin_url( 'admin.php' ) ) );
            exit;
        }
    }

    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            'IPV Pro Vendor',
            'IPV Pro Vendor',
            'manage_options',
            'ipv-vendor-dashboard',
            [ $this, 'render_dashboard' ],
            'dashicons-cloud',
            30
        );

        // Dashboard (same as parent)
        add_submenu_page(
            'ipv-vendor-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'ipv-vendor-dashboard',
            [ $this, 'render_dashboard' ]
        );

        // Licenses
        add_submenu_page(
            'ipv-vendor-dashboard',
            'Licenze',
            'Licenze',
            'manage_options',
            'ipv-vendor-licenses',
            [ $this, 'render_licenses' ]
        );

        // Settings
        add_submenu_page(
            'ipv-vendor-dashboard',
            'Impostazioni',
            'Impostazioni',
            'manage_options',
            'ipv-vendor-settings',
            [ $this, 'render_settings' ]
        );

        // Change Plan (hidden, accessed via link from licenses)
        add_submenu_page(
            null, // Hidden from menu
            'Cambia Piano Licenza',
            'Cambia Piano',
            'manage_options',
            'ipv-vendor-change-plan',
            [ $this, 'render_change_plan' ]
        );

        // Upload Golden Prompt File (hidden, accessed via link from licenses)
        add_submenu_page(
            null, // Hidden from menu
            'Carica Golden Prompt',
            'Carica Golden Prompt',
            'manage_options',
            'ipv-vendor-upload-golden',
            [ $this, 'render_upload_golden' ]
        );

        // Configure Golden Prompt (hidden, accessed via link from licenses)
        add_submenu_page(
            null, // Hidden from menu
            'Configura Golden Prompt',
            'Configura Golden Prompt',
            'manage_options',
            'ipv-vendor-configure-golden',
            [ $this, 'render_configure_golden' ]
        );
    }

    public function render_dashboard() {
        global $wpdb;

        // Get stats
        $total_licenses = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_licenses" );
        $active_licenses = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_licenses WHERE status = 'active'" );
        $total_activations = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_activations WHERE is_active = 1" );
        $total_api_calls = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_api_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)" );

        // Recent licenses
        $recent_licenses = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses ORDER BY created_at DESC LIMIT 5"
        );

        ?>
        <div class="ipv-modern-page bg-gray-50 min-h-screen -ml-5 -mt-2 p-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">🎬 IPV Pro Vendor Dashboard</h1>
                <p class="text-gray-600">Panoramica generale del sistema di licenze</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="ipv-stat-card">
                    <div class="ipv-stat-icon primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                    </div>
                    <div class="ipv-stat-label">Licenze Totali</div>
                    <div class="ipv-stat-value"><?php echo number_format_i18n( $total_licenses ); ?></div>
                </div>

                <div class="ipv-stat-card">
                    <div class="ipv-stat-icon success">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ipv-stat-label">Licenze Attive</div>
                    <div class="ipv-stat-value text-green-600"><?php echo number_format_i18n( $active_licenses ); ?></div>
                </div>

                <div class="ipv-stat-card">
                    <div class="ipv-stat-icon warning">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                        </svg>
                    </div>
                    <div class="ipv-stat-label">Attivazioni Siti</div>
                    <div class="ipv-stat-value text-amber-600"><?php echo number_format_i18n( $total_activations ); ?></div>
                </div>

                <div class="ipv-stat-card">
                    <div class="ipv-stat-icon info">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="ipv-stat-label">API Calls (30gg)</div>
                    <div class="ipv-stat-value text-blue-600"><?php echo number_format_i18n( $total_api_calls ); ?></div>
                </div>
            </div>

            <!-- Recent Licenses Table -->
            <div class="ipv-card mb-8">
                <div class="ipv-card-header">
                    <h2 class="ipv-card-title">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        📋 Licenze Recenti
                    </h2>
                    <span class="ipv-badge ipv-badge-primary"><?php echo $total_licenses; ?> totali</span>
                </div>

                <!-- Leggenda Icone -->
                <div style="background: #f9fafb; padding: 12px 20px; border-bottom: 1px solid #e5e7eb; display: flex; gap: 20px; flex-wrap: wrap; font-size: 13px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 18px;">⏸️</span>
                        <span>Sospendi</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 18px;">▶️</span>
                        <span>Attiva</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 18px;">🔄</span>
                        <span>Cambia Piano</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 18px;">🗑️</span>
                        <span>Elimina</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 18px;">🌟</span>
                        <span>Golden Prompt</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="ipv-table">
                        <thead>
                            <tr>
                                <th>License Key</th>
                                <th>Email</th>
                                <th>Dominio</th>
                                <th>Piano</th>
                                <th>Status</th>
                                <th>Crediti</th>
                                <th>Data Creazione</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( empty( $recent_licenses ) ) : ?>
                                <tr><td colspan="7" class="text-center text-gray-500">Nessuna licenza trovata</td></tr>
                            <?php else : ?>
                                <?php foreach ( $recent_licenses as $license ) : ?>
                                    <tr>
                                        <td><code class="ipv-code"><?php echo esc_html( $license->license_key ); ?></code></td>
                                        <td><?php echo esc_html( $license->email ); ?></td>
                                        <td><?php echo $license->domain ? '<code style="font-size: 12px; background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 3px;">' . esc_html( $license->domain ) . '</code>' : '<span style="color: #9ca3af;">—</span>'; ?></td>
                                        <td><strong><?php echo esc_html( ucfirst( $license->variant_slug ) ); ?></strong></td>
                                        <td>
                                            <?php
                                            $badge_class = 'ipv-badge ';
                                            switch ( $license->status ) {
                                                case 'active':
                                                    $badge_class .= 'ipv-badge-success';
                                                    break;
                                                case 'cancelled':
                                                case 'expired':
                                                    $badge_class .= 'ipv-badge-danger';
                                                    break;
                                                case 'on-hold':
                                                    $badge_class .= 'ipv-badge-warning';
                                                    break;
                                                default:
                                                    $badge_class .= 'ipv-badge-default';
                                            }
                                            echo '<span class="' . $badge_class . '">' . esc_html( $license->status ) . '</span>';
                                            ?>
                                        </td>
                                        <td><?php echo esc_html( $license->credits_remaining . '/' . $license->credits_total ); ?></td>
                                        <td><?php echo date_i18n( 'd/m/Y H:i', strtotime( $license->created_at ) ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- API Endpoints Card -->
            <div class="ipv-card">
                <div class="ipv-card-header">
                    <h2 class="ipv-card-title">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                        </svg>
                        🔗 API Endpoints
                    </h2>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="font-medium text-gray-700">Health Check:</span>
                        <code class="ipv-code text-xs"><?php echo esc_url( rest_url( 'ipv-vendor/v1/health' ) ); ?></code>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="font-medium text-gray-700">License Activation:</span>
                        <code class="ipv-code text-xs"><?php echo esc_url( rest_url( 'ipv-vendor/v1/license/activate' ) ); ?></code>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="font-medium text-gray-700">Transcript Gateway:</span>
                        <code class="ipv-code text-xs"><?php echo esc_url( rest_url( 'ipv-vendor/v1/transcript' ) ); ?></code>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="font-medium text-gray-700">Plugin Info:</span>
                        <code class="ipv-code text-xs"><?php echo esc_url( rest_url( 'ipv-vendor/v1/plugin-info' ) ); ?></code>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_licenses() {
        global $wpdb;

        // Handle manual license creation
        if ( isset( $_POST['ipv_create_license'] ) ) {
            check_admin_referer( 'ipv_create_license_nonce' );

            $email = sanitize_email( $_POST['license_email'] ?? '' );
            $domain = sanitize_text_field( $_POST['license_domain'] ?? '' );
            $variant = sanitize_text_field( $_POST['license_variant'] ?? 'starter' );
            $expires_days = absint( $_POST['license_expires'] ?? 0 );
            
            // Get plan data
            $plans_manager = IPV_Vendor_Plans_Manager::instance();
            $plan = $plans_manager->get_plan( $variant );
            
            // Use plan defaults or form overrides
            $credits = ! empty( $_POST['license_credits'] ) 
                ? absint( $_POST['license_credits'] ) 
                : ( $plan['credits'] ?? 25 );
            $activations = ! empty( $_POST['license_activations'] ) 
                ? absint( $_POST['license_activations'] ) 
                : ( $plan['activations'] ?? 1 );

            if ( empty( $email ) ) {
                echo '<div class="notice notice-error"><p>Email obbligatoria!</p></div>';
            } else {
                // Generate license key
                $license_manager = IPV_Vendor_License_Manager::instance();
                $license_key = $license_manager->generate_license_key();

                // Calculate expiry
                $expires_at = $expires_days > 0 
                    ? date( 'Y-m-d H:i:s', strtotime( "+{$expires_days} days" ) ) 
                    : null;

                // Insert license
                $inserted = $wpdb->insert(
                    $wpdb->prefix . 'ipv_licenses',
                    [
                        'license_key' => $license_key,
                        'order_id' => 0,
                        'product_id' => 0,
                        'user_id' => get_current_user_id(),
                        'email' => $email,
                        'domain' => $domain,
                        'status' => 'active',
                        'variant_slug' => $variant,
                        'credits_total' => $credits,
                        'credits_remaining' => $credits,
                        'credits_reset_date' => date( 'Y-m-01', strtotime( '+1 month' ) ),
                        'activation_limit' => $activations,
                        'activation_count' => 0,
                        'expires_at' => $expires_at
                    ],
                    [ '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%d', '%s' ]
                );

                if ( $inserted ) {
                    // Redirect to avoid nonce expiration on page refresh
                    wp_safe_redirect( add_query_arg( [
                        'page' => 'ipv-vendor-licenses',
                        'license_created' => '1',
                        'license_key' => urlencode($license_key),
                        'plan_name' => urlencode($plan['name'] ?? $variant),
                        'credits' => $credits,
                        'activations' => $activations
                    ], admin_url('admin.php') ) );
                    exit;
                } else {
                    echo '<div class="notice notice-error"><p>Errore creazione licenza!</p></div>';
                }
            }
        }
        
        // Show success message after redirect
        if ( isset( $_GET['license_created'] ) && $_GET['license_created'] === '1' ) {
            $license_key = sanitize_text_field( $_GET['license_key'] ?? '' );
            $plan_name = sanitize_text_field( $_GET['plan_name'] ?? '' );
            $credits = absint( $_GET['credits'] ?? 0 );
            $activations = absint( $_GET['activations'] ?? 0 );
            
            echo '<div class="notice notice-success is-dismissible"><p>✅ Licenza creata: <code>' . esc_html( $license_key ) . '</code> (Piano: ' . esc_html( $plan_name ) . ', Crediti: ' . $credits . ', Siti: ' . $activations . ')</p></div>';
        }

        // Handle status change
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'toggle_status' && isset( $_GET['id'] ) ) {
            check_admin_referer( 'toggle_license_' . $_GET['id'] );
            
            $license_id = absint( $_GET['id'] );
            $current = $wpdb->get_var( $wpdb->prepare(
                "SELECT status FROM {$wpdb->prefix}ipv_licenses WHERE id = %d",
                $license_id
            ));
            
            $new_status = ( $current === 'active' ) ? 'suspended' : 'active';
            $wpdb->update(
                $wpdb->prefix . 'ipv_licenses',
                [ 'status' => $new_status ],
                [ 'id' => $license_id ],
                [ '%s' ],
                [ '%d' ]
            );
            
            echo '<div class="notice notice-success"><p>Status aggiornato a: ' . esc_html( $new_status ) . '</p></div>';
        }

        // Handle license deletion
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_license' && isset( $_GET['id'] ) ) {
            check_admin_referer( 'delete_license_' . $_GET['id'] );
            
            $license_id = absint( $_GET['id'] );
            
            // Get license info before deletion for logging
            $license = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE id = %d",
                $license_id
            ));
            
            if ( $license ) {
                // Delete activations first
                $wpdb->delete(
                    $wpdb->prefix . 'ipv_activations',
                    [ 'license_id' => $license_id ],
                    [ '%d' ]
                );
                
                // Delete golden prompts if any
                $wpdb->delete(
                    $wpdb->prefix . 'ipv_golden_prompts',
                    [ 'license_id' => $license_id ],
                    [ '%d' ]
                );
                
                // Delete license meta if table exists
                $meta_table = $wpdb->prefix . 'ipv_license_meta';
                if ( $wpdb->get_var( "SHOW TABLES LIKE '{$meta_table}'" ) === $meta_table ) {
                    $wpdb->delete(
                        $meta_table,
                        [ 'license_id' => $license_id ],
                        [ '%d' ]
                    );
                }
                
                // Delete the license
                $deleted = $wpdb->delete(
                    $wpdb->prefix . 'ipv_licenses',
                    [ 'id' => $license_id ],
                    [ '%d' ]
                );
                
                if ( $deleted ) {
                    echo '<div class="notice notice-success"><p>✅ Licenza <strong>' . esc_html( $license->license_key ) . '</strong> eliminata con successo!</p></div>';
                    
                    // Reset AUTO_INCREMENT to max ID + 1 (or 1 if table is empty)
                    $max_id = $wpdb->get_var( "SELECT MAX(id) FROM {$wpdb->prefix}ipv_licenses" );
                    $next_id = $max_id ? $max_id + 1 : 1;
                    $wpdb->query( "ALTER TABLE {$wpdb->prefix}ipv_licenses AUTO_INCREMENT = {$next_id}" );
                    
                    // Log deletion
                    error_log( sprintf(
                        'IPV Vendor: License %s (ID: %d) deleted by admin user %d',
                        $license->license_key,
                        $license_id,
                        get_current_user_id()
                    ));
                } else {
                    echo '<div class="notice notice-error"><p>❌ Errore durante l\'eliminazione della licenza.</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>❌ Licenza non trovata.</p></div>';
            }
        }

        // Get all licenses
        $licenses = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses ORDER BY created_at DESC"
        );

        ?>
        <div class="ipv-modern-page bg-gray-50 min-h-screen -ml-5 -mt-2 p-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">🔑 Gestione Licenze</h1>
                <p class="text-gray-600">Crea e gestisci le licenze cliente</p>
            </div>

            <!-- CREATE LICENSE FORM -->
            <div class="ipv-card mb-8 max-w-3xl">
                <div class="ipv-card-header">
                    <h2 class="ipv-card-title">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        ➕ Crea Licenza Manuale
                    </h2>
                </div>
                <form method="post" class="p-6">
                    <?php wp_nonce_field( 'ipv_create_license_nonce' ); ?>

                    <div class="space-y-6">
                        <div>
                            <label for="license_email" class="ipv-label">Email *</label>
                            <input type="email" name="license_email" id="license_email" class="ipv-input" required />
                        </div>

                        <div>
                            <label for="license_domain" class="ipv-label">Dominio</label>
                            <input type="text" name="license_domain" id="license_domain" class="ipv-input" placeholder="esempio.com" />
                            <p class="text-sm text-gray-500 mt-1">Dominio dove verrà usata la licenza (opzionale)</p>
                        </div>

                        <div>
                            <label for="license_variant" class="ipv-label">Piano</label>
                            <select name="license_variant" id="license_variant" class="ipv-select">
                                <?php
                                $plan_options = apply_filters( 'ipv_vendor_plan_options', [] );
                                foreach ( $plan_options as $slug => $label ) :
                                ?>
                                    <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $slug, 'professional' ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="license_credits" class="ipv-label">Crediti Mensili</label>
                                <input type="number" name="license_credits" id="license_credits" class="ipv-input" placeholder="Auto dal piano" min="1" max="9999" />
                                <p class="text-sm text-gray-500 mt-1">Lascia vuoto per usare il valore del piano</p>
                            </div>

                            <div>
                                <label for="license_activations" class="ipv-label">Limite Siti</label>
                                <input type="number" name="license_activations" id="license_activations" class="ipv-input" placeholder="Auto dal piano" min="1" max="100" />
                                <p class="text-sm text-gray-500 mt-1">Lascia vuoto per usare il valore del piano</p>
                            </div>
                        </div>

                        <div>
                            <label for="license_expires" class="ipv-label">Scadenza (giorni)</label>
                            <input type="number" name="license_expires" id="license_expires" value="0" class="ipv-input" min="0" max="3650" />
                            <p class="text-sm text-gray-500 mt-1">0 = Mai (subscription attiva)</p>
                        </div>

                        <div class="flex justify-end pt-4 border-t border-gray-200">
                            <button type="submit" name="ipv_create_license" class="ipv-btn ipv-btn-primary">
                                🔑 Genera Licenza
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- LICENSES TABLE -->
            <div class="ipv-card">
                <div class="ipv-card-header">
                    <h2 class="ipv-card-title">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        📋 Licenze Esistenti
                    </h2>
                    <span class="ipv-badge ipv-badge-primary"><?php echo count( $licenses ); ?> totali</span>
                </div>

                <!-- Leggenda Azioni -->
                <div style="background: #f9fafb; padding: 15px 20px; border-bottom: 1px solid #e5e7eb;">
                    <div style="display: flex; gap: 25px; flex-wrap: wrap; font-size: 14px; align-items: center;">
                        <strong style="color: #374151; margin-right: 10px;">Legenda Azioni:</strong>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 22px;">⏸️</span>
                            <span style="color: #6b7280;">Sospendi</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 22px;">▶️</span>
                            <span style="color: #6b7280;">Attiva</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 22px;">🔄</span>
                            <span style="color: #6b7280;">Cambia Piano</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 22px;">🗑️</span>
                            <span style="color: #6b7280;">Elimina</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 18px; display: inline-block; width: 44px; height: 24px; background: #10b981; border-radius: 24px; position: relative;">
                                <span style="position: absolute; width: 18px; height: 18px; background: white; border-radius: 50%; top: 3px; right: 3px;"></span>
                            </span>
                            <span style="color: #6b7280;">Golden Prompt ON</span>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
            <table class="ipv-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>License Key</th>
                        <th>Email</th>
                        <th>Piano</th>
                        <th>Status</th>
                        <th>Crediti</th>
                        <th>Attivazioni</th>
                        <th>Scadenza</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $licenses ) ) : ?>
                        <tr><td colspan="9" class="text-center text-gray-500 py-8">Nessuna licenza trovata. Crea la prima licenza sopra! ☝️</td></tr>
                    <?php else : ?>
                        <?php foreach ( $licenses as $license ) : ?>
                            <tr>
                                <td class="font-semibold text-gray-700"><?php echo esc_html( $license->id ); ?></td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <code class="ipv-code text-xs"><?php echo esc_html( $license->license_key ); ?></code>
                                        <button type="button" class="ipv-btn ipv-btn-secondary text-xs px-2 py-1" onclick="navigator.clipboard.writeText('<?php echo esc_attr( $license->license_key ); ?>'); this.textContent='✓';" title="Copia">📋</button>
                                    </div>
                                </td>
                                <td><?php echo esc_html( $license->email ); ?></td>
                                <td><strong class="text-gray-900"><?php echo esc_html( ucfirst( $license->variant_slug ) ); ?></strong></td>
                                <td>
                                    <?php
                                    $badge_class = 'ipv-badge ';
                                    switch ( $license->status ) {
                                        case 'active':
                                            $badge_class .= 'ipv-badge-success';
                                            break;
                                        case 'suspended':
                                        case 'cancelled':
                                        case 'expired':
                                            $badge_class .= 'ipv-badge-danger';
                                            break;
                                        case 'on-hold':
                                            $badge_class .= 'ipv-badge-warning';
                                            break;
                                        default:
                                            $badge_class .= 'ipv-badge-default';
                                    }
                                    echo '<span class="' . $badge_class . '">' . esc_html( $license->status ) . '</span>';
                                    ?>
                                </td>
                                <td><span class="text-gray-700"><?php echo esc_html( $license->credits_remaining . '/' . $license->credits_total ); ?></span></td>
                                <td><span class="text-gray-700"><?php echo esc_html( $license->activation_count . '/' . $license->activation_limit ); ?></span></td>
                                <td>
                                    <?php
                                    if ( $license->expires_at ) {
                                        $expires = strtotime( $license->expires_at );
                                        $is_expired = $expires < time();
                                        $badge_class = $is_expired ? 'ipv-badge ipv-badge-danger' : 'ipv-badge ipv-badge-default';
                                        echo '<span class="' . $badge_class . '">' . date_i18n( 'd/m/Y', $expires ) . '</span>';
                                    } else {
                                        echo '<span class="ipv-badge ipv-badge-success">∞ Mai</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-2">
                                    <?php
                                    $toggle_url = wp_nonce_url(
                                        admin_url( 'admin.php?page=ipv-vendor-licenses&action=toggle_status&id=' . $license->id ),
                                        'toggle_license_' . $license->id
                                    );
                                    $toggle_icon = $license->status === 'active' ? '⏸️' : '▶️';
                                    $toggle_title = $license->status === 'active' ? 'Sospendi licenza' : 'Attiva licenza';

                                    $change_plan_url = admin_url( 'admin.php?page=ipv-vendor-change-plan&license_id=' . $license->id );

                                    $delete_url = wp_nonce_url(
                                        admin_url( 'admin.php?page=ipv-vendor-licenses&action=delete_license&id=' . $license->id ),
                                        'delete_license_' . $license->id
                                    );

                                    // Check if license is Golden prompt
                                    $is_golden_prompt = ($license->variant_slug === 'golden_prompt');

                                    // Get Golden prompt status
                                    if ( $is_golden_prompt ) {
                                        $golden_enabled = $wpdb->get_var( $wpdb->prepare(
                                            "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
                                            WHERE license_id = %d AND meta_key = '_golden_prompt_enabled'",
                                            $license->id
                                        ));
                                        $golden_enabled = (bool) $golden_enabled;

                                        $toggle_golden_url = wp_nonce_url(
                                            admin_url( 'admin.php?page=ipv-vendor-licenses&action=toggle_golden&id=' . $license->id ),
                                            'toggle_golden_' . $license->id
                                        );
                                    }
                                    ?>
                                    <!-- Icone Azioni (solo icone + tooltip) -->
                                    <a href="<?php echo esc_url( $toggle_url ); ?>" 
                                       style="font-size: 24px; text-decoration: none; transition: opacity 0.2s; display: inline-block; line-height: 1;" 
                                       title="<?php echo $toggle_title; ?>"
                                       onmouseover="this.style.opacity='0.6'" 
                                       onmouseout="this.style.opacity='1'">
                                        <?php echo $toggle_icon; ?>
                                    </a>
                                    
                                    <a href="<?php echo esc_url( $change_plan_url ); ?>" 
                                       style="font-size: 24px; text-decoration: none; transition: opacity 0.2s; display: inline-block; line-height: 1;" 
                                       title="Cambia piano"
                                       onmouseover="this.style.opacity='0.6'" 
                                       onmouseout="this.style.opacity='1'">
                                        🔄
                                    </a>
                                    
                                    <?php if ( $is_golden_prompt ) : ?>
                                        <!-- Switch Golden Prompt -->
                                        <label class="ipv-switch" title="Golden Prompt: <?php echo $golden_enabled ? 'ON' : 'OFF'; ?>">
                                            <input type="checkbox" 
                                                   <?php checked( $golden_enabled, true ); ?> 
                                                   onchange="window.location.href='<?php echo esc_url( $toggle_golden_url ); ?>'" />
                                            <span class="ipv-switch-slider"></span>
                                        </label>
                                    <?php endif; ?>
                                    
                                    <a href="<?php echo esc_url( $delete_url ); ?>" 
                                       style="font-size: 24px; text-decoration: none; transition: opacity 0.2s; display: inline-block; line-height: 1;" 
                                       title="Elimina licenza"
                                       onclick="return confirm('⚠️ Sei sicuro di voler ELIMINARE questa licenza?\n\nLicense Key: <?php echo esc_js( $license->license_key ); ?>\n\nQuesta azione è IRREVERSIBILE!');"
                                       onmouseover="this.style.opacity='0.6'" 
                                       onmouseout="this.style.opacity='1'">
                                        🗑️
                                    </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings() {
        if ( isset( $_POST['ipv_save_settings'] ) ) {
            check_admin_referer( 'ipv_vendor_settings' );

            update_option( 'ipv_supadata_rotation_mode', sanitize_text_field( $_POST['supadata_rotation_mode'] ?? 'fixed' ) );
            update_option( 'ipv_youtube_api_key', sanitize_text_field( $_POST['youtube_api_key'] ?? '' ) );
            update_option( 'ipv_supadata_api_key_1', sanitize_text_field( $_POST['supadata_api_key_1'] ?? '' ) );
            update_option( 'ipv_supadata_api_key_2', sanitize_text_field( $_POST['supadata_api_key_2'] ?? '' ) );
            update_option( 'ipv_supadata_api_key_3', sanitize_text_field( $_POST['supadata_api_key_3'] ?? '' ) );
            update_option( 'ipv_openai_api_key', sanitize_text_field( $_POST['openai_api_key'] ?? '' ) );
            update_option( 'ipv_openai_model', sanitize_text_field( $_POST['openai_model'] ?? 'gpt-4o-mini' ) );
            
            // Antifrode settings
            update_option( 'ipv_rate_limit_enabled', isset( $_POST['rate_limit_enabled'] ) ? 1 : 0 );
            update_option( 'ipv_rate_limit_max_requests', intval( $_POST['rate_limit_max_requests'] ?? 100 ) );
            update_option( 'ipv_rate_limit_window', intval( $_POST['rate_limit_window'] ?? 3600 ) );
            update_option( 'ipv_block_bots_enabled', isset( $_POST['block_bots_enabled'] ) ? 1 : 0 );

            echo '<div class="notice notice-success is-dismissible"><p>Impostazioni salvate!</p></div>';
        }

        $rotation_mode      = get_option( 'ipv_supadata_rotation_mode', 'fixed' );
        $youtube_api_key    = get_option( 'ipv_youtube_api_key', '' );
        $supadata_api_key_1 = get_option( 'ipv_supadata_api_key_1', '' );
        $supadata_api_key_2 = get_option( 'ipv_supadata_api_key_2', '' );
        $supadata_api_key_3 = get_option( 'ipv_supadata_api_key_3', '' );
        $openai_api_key     = get_option( 'ipv_openai_api_key', '' );
        $openai_model       = get_option( 'ipv_openai_model', 'gpt-4o-mini' );
        
        // Antifrode settings
        $rate_limit_enabled = get_option( 'ipv_rate_limit_enabled', 1 );
        $rate_limit_max_requests = get_option( 'ipv_rate_limit_max_requests', 100 );
        $rate_limit_window = get_option( 'ipv_rate_limit_window', 3600 );
        $block_bots_enabled = get_option( 'ipv_block_bots_enabled', 1 );
        ?>
        <div class="ipv-modern-page bg-gray-50 min-h-screen -ml-5 -mt-2 p-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">⚙️ Impostazioni</h1>
                <p class="text-gray-600">Configura le chiavi API e le impostazioni di sistema</p>
            </div>

            <!-- Settings Form -->
            <div class="ipv-card max-w-4xl">
                <div class="ipv-card-header">
                    <h2 class="ipv-card-title">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        🔑 API Keys & Configurazione
                    </h2>
                </div>

                <form method="post" class="p-6">
                    <?php wp_nonce_field( 'ipv_vendor_settings' ); ?>

                    <div class="space-y-6">
                        <!-- Rotation Mode -->
                        <div>
                            <label for="supadata_rotation_mode" class="ipv-label">Modalità Rotazione SupaData Keys</label>
                            <select name="supadata_rotation_mode" id="supadata_rotation_mode" class="ipv-select">
                                <option value="fixed" <?php selected( $rotation_mode, 'fixed' ); ?>>Fissa (usa sempre key 1)</option>
                                <option value="round-robin" <?php selected( $rotation_mode, 'round-robin' ); ?>>Round-Robin (rotazione automatica)</option>
                            </select>
                            <p class="text-sm text-gray-500 mt-1">Modalità di rotazione per le 3 API keys SupaData configurate.</p>
                        </div>

                        <!-- YouTube API Key -->
                        <div>
                            <label for="youtube_api_key" class="ipv-label">YouTube API Key</label>
                            <input type="text" name="youtube_api_key" id="youtube_api_key" value="<?php echo esc_attr( $youtube_api_key ); ?>" class="ipv-input font-mono text-sm" autocomplete="off" />
                            <p class="text-sm text-gray-500 mt-1">Chiave API usata per le chiamate a YouTube Data API. Lascia vuoto per usare la costante <code class="ipv-code text-xs">YOUTUBE_API_KEY</code> definita in <code class="ipv-code text-xs">class-api-gateway.php</code>.</p>
                        </div>

                        <!-- SupaData Keys -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                SupaData API Keys
                            </h3>

                            <div>
                                <label for="supadata_api_key_1" class="ipv-label">SupaData API Key 1</label>
                                <input type="text" name="supadata_api_key_1" id="supadata_api_key_1" value="<?php echo esc_attr( $supadata_api_key_1 ); ?>" class="ipv-input font-mono text-sm" autocomplete="off" />
                                <p class="text-sm text-gray-500 mt-1">Prima chiave SupaData usata per le trascrizioni. Lascia vuoto per usare la costante <code class="ipv-code text-xs">SUPADATA_API_KEY_1</code>.</p>
                            </div>

                            <div>
                                <label for="supadata_api_key_2" class="ipv-label">SupaData API Key 2</label>
                                <input type="text" name="supadata_api_key_2" id="supadata_api_key_2" value="<?php echo esc_attr( $supadata_api_key_2 ); ?>" class="ipv-input font-mono text-sm" autocomplete="off" />
                                <p class="text-sm text-gray-500 mt-1">Seconda chiave SupaData (opzionale). Lascia vuoto per usare la costante <code class="ipv-code text-xs">SUPADATA_API_KEY_2</code>.</p>
                            </div>

                            <div>
                                <label for="supadata_api_key_3" class="ipv-label">SupaData API Key 3</label>
                                <input type="text" name="supadata_api_key_3" id="supadata_api_key_3" value="<?php echo esc_attr( $supadata_api_key_3 ); ?>" class="ipv-input font-mono text-sm" autocomplete="off" />
                                <p class="text-sm text-gray-500 mt-1">Terza chiave SupaData (opzionale). Lascia vuoto per usare la costante <code class="ipv-code text-xs">SUPADATA_API_KEY_3</code>.</p>
                            </div>
                        </div>

                        <!-- OpenAI API Key -->
                        <div>
                            <label for="openai_api_key" class="ipv-label">OpenAI API Key</label>
                            <input type="text" name="openai_api_key" id="openai_api_key" value="<?php echo esc_attr( $openai_api_key ); ?>" class="ipv-input font-mono text-sm" autocomplete="off" />
                            <p class="text-sm text-gray-500 mt-1">Chiave usata per le richieste a OpenAI. Lascia vuoto per usare la costante <code class="ipv-code text-xs">OPENAI_API_KEY</code>.</p>
                        </div>

                        <!-- OpenAI Model Selection -->
                        <div>
                            <label for="openai_model" class="ipv-label">🤖 Modello OpenAI</label>
                            <select name="openai_model" id="openai_model" class="ipv-input">
                                <option value="gpt-4o" <?php selected( $openai_model, 'gpt-4o' ); ?>>GPT-4o (Latest, Most Capable)</option>
                                <option value="gpt-4o-mini" <?php selected( $openai_model, 'gpt-4o-mini' ); ?>>GPT-4o Mini (Faster, Cheaper) - Consigliato</option>
                                <option value="gpt-4-turbo" <?php selected( $openai_model, 'gpt-4-turbo' ); ?>>GPT-4 Turbo</option>
                                <option value="gpt-4" <?php selected( $openai_model, 'gpt-4' ); ?>>GPT-4 (Legacy)</option>
                                <option value="gpt-3.5-turbo" <?php selected( $openai_model, 'gpt-3.5-turbo' ); ?>>GPT-3.5 Turbo (Cheapest)</option>
                            </select>
                            <p class="text-sm text-gray-500 mt-1">
                                Scegli il modello da usare per le richieste AI. 
                                <strong>gpt-4o-mini</strong> è il miglior compromesso qualità/prezzo per la maggior parte dei casi.
                            </p>
                        </div>

                        <!-- Sezione Sicurezza & Antifrode -->
                        <div class="pt-6 border-t border-gray-300">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <span>🛡️</span> Sicurezza & Antifrode
                            </h3>

                            <!-- Rate Limiting -->
                            <div class="space-y-4">
                                <div class="flex items-start gap-3">
                                    <input type="checkbox" name="rate_limit_enabled" id="rate_limit_enabled" value="1" <?php checked( $rate_limit_enabled, 1 ); ?> class="mt-1" />
                                    <div class="flex-1">
                                        <label for="rate_limit_enabled" class="ipv-label mb-0">Abilita Rate Limiting</label>
                                        <p class="text-sm text-gray-500 mt-1">Limita il numero di richieste API per licenza per prevenire abusi</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4 ml-6">
                                    <div>
                                        <label for="rate_limit_max_requests" class="ipv-label text-sm">Max Richieste</label>
                                        <input type="number" name="rate_limit_max_requests" id="rate_limit_max_requests" value="<?php echo esc_attr( $rate_limit_max_requests ); ?>" min="10" max="1000" class="ipv-input" />
                                        <p class="text-xs text-gray-500 mt-1">Richieste massime per finestra temporale</p>
                                    </div>

                                    <div>
                                        <label for="rate_limit_window" class="ipv-label text-sm">Finestra (secondi)</label>
                                        <input type="number" name="rate_limit_window" id="rate_limit_window" value="<?php echo esc_attr( $rate_limit_window ); ?>" min="60" max="86400" step="60" class="ipv-input" />
                                        <p class="text-xs text-gray-500 mt-1">3600 = 1 ora, 86400 = 1 giorno</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Block Bots -->
                            <div class="mt-4">
                                <div class="flex items-start gap-3">
                                    <input type="checkbox" name="block_bots_enabled" id="block_bots_enabled" value="1" <?php checked( $block_bots_enabled, 1 ); ?> class="mt-1" />
                                    <div class="flex-1">
                                        <label for="block_bots_enabled" class="ipv-label mb-0">Blocca Bot e Crawler</label>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Blocca automaticamente richieste da bot, crawler, spider e scraper basandosi su User-Agent
                                            <br><span class="text-xs text-gray-400">Blocca: bot, crawler, spider, scraper in User-Agent</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Info antifrode -->
                            <div class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-500 rounded">
                                <p class="text-sm text-blue-900">
                                    <strong>ℹ️ Sistema Antifrode Attivo:</strong><br>
                                    Il sistema monitora automaticamente pattern sospetti, tentativi di accesso non autorizzati e abusi delle API.
                                    Tutti gli eventi di sicurezza vengono registrati nel log di audit.
                                </p>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end pt-4 border-t border-gray-200">
                            <button type="submit" name="ipv_save_settings" class="ipv-btn ipv-btn-primary">
                                💾 Salva Impostazioni
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Info Card -->
            <div class="ipv-card max-w-4xl mt-6">
                <div class="p-6 bg-blue-50 border-l-4 border-blue-500">
                    <h3 class="text-lg font-semibold text-blue-900 mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        ℹ️ Configurazione Avanzata
                    </h3>
                    <p class="text-gray-700">Le costanti di fallback rimangono disponibili in <code class="ipv-code text-xs">includes/class-api-gateway.php</code>. Se compili i campi sopra, verranno usate le chiavi salvate nel database. Se lasci vuoto un campo, verrà utilizzata la costante corrispondente.</p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Change Plan Page (v1.4.4)
     * Manages license plan changes with validation rules
     */
    public function render_change_plan() {
        global $wpdb;

        // Get license ID
        $license_id = isset( $_GET['license_id'] ) ? absint( $_GET['license_id'] ) : 0;

        if ( ! $license_id ) {
            echo '<div class="wrap"><div class="notice notice-error"><p>ID licenza non valido.</p></div></div>';
            return;
        }

        // Get license data
        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE id = %d",
            $license_id
        ));

        if ( ! $license ) {
            echo '<div class="wrap"><div class="notice notice-error"><p>Licenza non trovata.</p></div></div>';
            return;
        }

        // Handle form submission
        if ( isset( $_POST['ipv_change_plan_submit'] ) ) {
            check_admin_referer( 'ipv_change_plan_' . $license_id );

            $new_plan_slug = sanitize_text_field( $_POST['new_plan_slug'] ?? '' );
            $new_billing_type = sanitize_text_field( $_POST['new_billing_type'] ?? 'monthly' );

            // Get plan data
            $plans_manager = IPV_Vendor_Plans_Manager::instance();
            $new_plan = $plans_manager->get_plan( $new_plan_slug );

            if ( ! $new_plan ) {
                echo '<div class="notice notice-error"><p>Piano non trovato.</p></div>';
            } else {
                // Find the WooCommerce product
                $product_name = 'IPV Pro - ' . $new_plan['name'] . ' (' . ($new_billing_type === 'yearly' ? 'Annuale' : 'Mensile') . ')';
                $products = wc_get_products([
                    'name' => $product_name,
                    'limit' => 1,
                    'status' => 'publish'
                ]);

                if ( empty( $products ) ) {
                    echo '<div class="notice notice-error"><p>Prodotto WooCommerce non trovato: ' . esc_html( $product_name ) . '</p></div>';
                } else {
                    $product = $products[0];

                    // Calculate new credits
                    $new_credits_total = $new_billing_type === 'yearly'
                        ? ($new_plan['credits'] * 12)
                        : $new_plan['credits'];

                    // Create WooCommerce order for tracking
                    $order = wc_create_order();
                    $order->add_product( $product, 1 );
                    $order->set_customer_id( $license->user_id );
                    $order->set_billing_email( $license->email );
                    $order->set_status( 'completed' );
                    $order->add_order_note( sprintf(
                        'Cambio piano da %s a %s (%s). Licenza: %s',
                        $license->variant_slug,
                        $new_plan_slug,
                        $new_billing_type === 'yearly' ? 'Annuale' : 'Mensile',
                        $license->license_key
                    ));
                    $order->save();

                    // Update license
                    $wpdb->update(
                        $wpdb->prefix . 'ipv_licenses',
                        [
                            'variant_slug' => $new_plan_slug,
                            'credits_total' => $new_credits_total,
                            'order_id' => $order->get_id(),
                            'product_id' => $product->get_id()
                        ],
                        [ 'id' => $license_id ],
                        [ '%s', '%d', '%d', '%d' ],
                        [ '%d' ]
                    );

                    // Add to license history (if table exists)
                    $history_table = $wpdb->prefix . 'ipv_license_history';
                    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$history_table}'" ) === $history_table ) {
                        $wpdb->insert( $history_table, [
                            'license_id' => $license_id,
                            'action' => 'plan_change',
                            'old_value' => $license->variant_slug,
                            'new_value' => $new_plan_slug . '_' . $new_billing_type,
                            'performed_by' => get_current_user_id(),
                            'notes' => 'Order ID: ' . $order->get_id()
                        ]);
                    }

                    echo '<div class="notice notice-success"><p>✅ Piano cambiato con successo! Ordine WooCommerce creato: <a href="' . admin_url('post.php?post=' . $order->get_id() . '&action=edit') . '">#' . $order->get_id() . '</a></p></div>';

                    // Refresh license data
                    $license = $wpdb->get_row( $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE id = %d",
                        $license_id
                    ));
                }
            }
        }

        // Get all plans
        $plans_manager = IPV_Vendor_Plans_Manager::instance();
        $all_plans = $plans_manager->get_plans();

        // Parse current plan and billing
        $current_plan_slug = $license->variant_slug;
        // Try to detect billing type from product_id or order_id
        $current_billing_type = 'monthly'; // default
        if ( $license->product_id ) {
            $product = wc_get_product( $license->product_id );
            if ( $product ) {
                $billing_meta = $product->get_meta( '_ipv_billing_type' );
                if ( $billing_meta ) {
                    $current_billing_type = $billing_meta;
                }
            }
        }

        // Build available options with validation
        $available_options = [];

        foreach ( $all_plans as $slug => $plan ) {
            if ( empty( $plan['is_active'] ) ) continue;

            foreach ( ['monthly', 'yearly'] as $billing ) {
                // Skip current plan
                if ( $slug === $current_plan_slug && $billing === $current_billing_type ) {
                    continue;
                }

                $option = [
                    'slug' => $slug,
                    'billing' => $billing,
                    'plan' => $plan,
                    'allowed' => false,
                    'reason' => ''
                ];

                // RULE 1: Upgrade same billing - always allowed
                if ( $billing === $current_billing_type && $slug !== $current_plan_slug ) {
                    $current_plan = $all_plans[ $current_plan_slug ] ?? null;
                    if ( $current_plan && $plan['credits'] > $current_plan['credits'] ) {
                        $option['allowed'] = true;
                        $option['type'] = 'upgrade';
                        $option['reason'] = 'Upgrade piano';
                    } elseif ( $current_plan && $plan['credits'] < $current_plan['credits'] ) {
                        // Downgrade - check credits
                        $credits_limit = $plan['credits'] * 10;
                        if ( $license->credits_remaining < $credits_limit ) {
                            $option['allowed'] = true;
                            $option['type'] = 'downgrade';
                            $option['reason'] = 'Downgrade piano';
                        } else {
                            $option['allowed'] = false;
                            $option['type'] = 'downgrade_blocked';
                            $option['reason'] = sprintf(
                                'Troppi crediti residui (%d). Massimo consentito: %d (crediti piano × 10)',
                                $license->credits_remaining,
                                $credits_limit
                            );
                        }
                    }
                }

                // RULE 2: Change billing same plan - always allowed
                if ( $slug === $current_plan_slug && $billing !== $current_billing_type ) {
                    $option['allowed'] = true;
                    $option['type'] = 'billing_change';
                    $option['reason'] = 'Cambio fatturazione';
                }

                // RULE 3: Cross-billing (different plan + different billing) - NOT allowed
                if ( $slug !== $current_plan_slug && $billing !== $current_billing_type ) {
                    $option['allowed'] = false;
                    $option['type'] = 'cross_billing';
                    $option['reason'] = 'Non puoi cambiare piano e fatturazione insieme';
                }

                $available_options[] = $option;
            }
        }

        // Render page
        ?>
        <div class="wrap">
            <h1>🔄 Cambia Piano Licenza</h1>

            <div class="card" style="max-width: 800px;">
                <h2>📋 Licenza Attuale</h2>
                <table class="form-table">
                    <tr>
                        <th>License Key:</th>
                        <td><code style="font-size: 14px; background: #f0f0f1; padding: 4px 8px;"><?php echo esc_html( $license->license_key ); ?></code></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?php echo esc_html( $license->email ); ?></td>
                    </tr>
                    <tr>
                        <th>Piano Attuale:</th>
                        <td><strong><?php echo esc_html( ucfirst( $current_plan_slug ) ); ?></strong> (<?php echo $current_billing_type === 'yearly' ? 'Annuale' : 'Mensile'; ?>)</td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td><strong style="color: <?php echo $license->status === 'active' ? '#46b450' : '#dc3232'; ?>"><?php echo esc_html( $license->status ); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Crediti:</th>
                        <td><?php echo esc_html( $license->credits_remaining . '/' . $license->credits_total ); ?></td>
                    </tr>
                    <tr>
                        <th>Attivazioni:</th>
                        <td><?php echo esc_html( $license->activation_count . '/' . $license->activation_limit ); ?></td>
                    </tr>
                </table>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>🎯 Seleziona Nuovo Piano</h2>

                <form method="post">
                    <?php wp_nonce_field( 'ipv_change_plan_' . $license_id ); ?>

                    <div style="margin: 20px 0;">
                        <?php foreach ( $available_options as $option ) :
                            $is_allowed = $option['allowed'];
                            $bg_color = $is_allowed ? '#dbeafe' : '#fee2e2';
                            $border_color = $is_allowed ? '#3b82f6' : '#dc3232';
                            $text_color = $is_allowed ? '#1e40af' : '#dc3232';

                            $price = $option['billing'] === 'yearly'
                                ? ($option['plan']['price'] * 10)
                                : $option['plan']['price'];
                            $credits = $option['billing'] === 'yearly'
                                ? ($option['plan']['credits'] * 12)
                                : $option['plan']['credits'];
                            $billing_label = $option['billing'] === 'yearly' ? 'Annuale' : 'Mensile';
                        ?>
                            <div style="padding: 15px; margin-bottom: 15px; background: <?php echo $bg_color; ?>; border-left: 4px solid <?php echo $border_color; ?>; border-radius: 4px;">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <?php if ( $is_allowed ) : ?>
                                        <input type="radio" name="new_plan_slug" value="<?php echo esc_attr( $option['slug'] ); ?>" id="plan_<?php echo esc_attr( $option['slug'] . '_' . $option['billing'] ); ?>" required />
                                        <input type="hidden" name="new_billing_type" value="<?php echo esc_attr( $option['billing'] ); ?>" />
                                    <?php else : ?>
                                        <input type="radio" disabled />
                                    <?php endif; ?>

                                    <label for="plan_<?php echo esc_attr( $option['slug'] . '_' . $option['billing'] ); ?>" style="flex: 1; cursor: <?php echo $is_allowed ? 'pointer' : 'not-allowed'; ?>;">
                                        <div style="font-size: 16px; font-weight: bold; color: #333;">
                                            <?php echo esc_html( $option['plan']['name'] ); ?> (<?php echo $billing_label; ?>)
                                        </div>
                                        <div style="color: #666; margin-top: 4px;">
                                            €<?php echo number_format( $price, 2 ); ?>/<?php echo $option['billing'] === 'yearly' ? 'anno' : 'mese'; ?>
                                            • <?php echo esc_html( $credits ); ?> video/<?php echo $option['billing'] === 'yearly' ? 'anno' : 'mese'; ?>
                                            • <?php echo esc_html( $option['plan']['activations'] ); ?> sito/i
                                        </div>
                                        <div style="color: <?php echo $text_color; ?>; margin-top: 4px; font-weight: 600;">
                                            <?php
                                            if ( isset( $option['type'] ) ) {
                                                $icons = [
                                                    'upgrade' => '⬆️',
                                                    'downgrade' => '⬇️',
                                                    'billing_change' => '🔄',
                                                    'downgrade_blocked' => '🚫',
                                                    'cross_billing' => '❌'
                                                ];
                                                echo $icons[ $option['type'] ] ?? '';
                                            }
                                            echo ' ' . esc_html( $option['reason'] );
                                            ?>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top: 20px; padding: 15px; background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px;">
                        <strong>⚠️ Nota Importante:</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <li>Verrà creato un nuovo ordine WooCommerce per tracciabilità</li>
                            <li>I crediti residui verranno mantenuti</li>
                            <li>Il limite attivazioni verrà aggiornato secondo il nuovo piano</li>
                            <li>I downgrade sono consentiti solo con meno di 10 mesi di crediti residui</li>
                        </ul>
                    </div>

                    <p class="submit" style="margin-top: 20px;">
                        <button type="submit" name="ipv_change_plan_submit" class="button button-primary button-large" onclick="return confirm('Confermi il cambio piano? Verrà creato un nuovo ordine WooCommerce.');">
                            🔄 Cambia Piano
                        </button>
                        <a href="<?php echo admin_url( 'admin.php?page=ipv-vendor-licenses' ); ?>" class="button button-large">
                            ← Annulla
                        </a>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Render Upload Golden Prompt Page (v1.4.7)
     * Admin-only page to upload Golden prompt file for a specific license
     */
    public function render_upload_golden() {
        global $wpdb;

        // Get license ID
        $license_id = isset( $_GET['license_id'] ) ? absint( $_GET['license_id'] ) : 0;

        if ( ! $license_id ) {
            echo '<div class="wrap"><div class="notice notice-error"><p>ID licenza non valido.</p></div></div>';
            return;
        }

        // Get license data
        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE id = %d",
            $license_id
        ));

        if ( ! $license ) {
            echo '<div class="wrap"><div class="notice notice-error"><p>Licenza non trovata.</p></div></div>';
            return;
        }

        // Check if license is Golden prompt
        if ( $license->variant_slug !== 'golden_prompt' ) {
            echo '<div class="wrap"><div class="notice notice-error"><p>Questa funzione è disponibile solo per licenze Golden prompt.</p></div></div>';
            return;
        }

        // Handle file upload
        if ( isset( $_POST['ipv_upload_golden_submit'] ) ) {
            check_admin_referer( 'ipv_upload_golden_' . $license_id );

            if ( ! empty( $_FILES['golden_prompt_file']['name'] ) ) {
                $file = $_FILES['golden_prompt_file'];

                // Validate file
                $allowed_types = [ 'application/zip', 'application/x-zip-compressed', 'application/octet-stream' ];
                $max_size = 50 * 1024 * 1024; // 50MB

                if ( ! in_array( $file['type'], $allowed_types ) && ! str_ends_with( $file['name'], '.zip' ) ) {
                    echo '<div class="notice notice-error"><p>❌ Solo file ZIP sono consentiti.</p></div>';
                } elseif ( $file['size'] > $max_size ) {
                    echo '<div class="notice notice-error"><p>❌ File troppo grande. Massimo 50MB.</p></div>';
                } else {
                    // Create secure upload directory
                    $upload_dir = wp_upload_dir();
                    $ipv_dir = $upload_dir['basedir'] . '/ipv-golden-prompts';

                    if ( ! file_exists( $ipv_dir ) ) {
                        wp_mkdir_p( $ipv_dir );
                        // Add .htaccess to prevent direct access
                        file_put_contents( $ipv_dir . '/.htaccess', "Options -Indexes\nDeny from all" );
                        // Add index.php to prevent directory listing
                        file_put_contents( $ipv_dir . '/index.php', "<?php\n// Silence is golden" );
                    }

                    // Generate secure filename
                    $extension = pathinfo( $file['name'], PATHINFO_EXTENSION );
                    $secure_filename = 'golden-prompt-license-' . $license->id . '-' . wp_generate_password( 16, false ) . '.' . $extension;
                    $file_path = $ipv_dir . '/' . $secure_filename;

                    // Move uploaded file
                    if ( move_uploaded_file( $file['tmp_name'], $file_path ) ) {
                        // Delete old file if exists
                        $old_file = $wpdb->get_var( $wpdb->prepare(
                            "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
                            WHERE license_id = %d AND meta_key = '_golden_prompt_file'",
                            $license->id
                        ));

                        if ( $old_file && file_exists( $old_file ) ) {
                            unlink( $old_file );
                        }

                        // Save file path in license metadata
                        $wpdb->query( $wpdb->prepare(
                            "INSERT INTO {$wpdb->prefix}ipv_license_meta (license_id, meta_key, meta_value)
                            VALUES (%d, '_golden_prompt_file', %s)
                            ON DUPLICATE KEY UPDATE meta_value = %s",
                            $license->id,
                            $file_path,
                            $file_path
                        ));

                        // Save upload timestamp
                        $wpdb->query( $wpdb->prepare(
                            "INSERT INTO {$wpdb->prefix}ipv_license_meta (license_id, meta_key, meta_value)
                            VALUES (%d, '_golden_prompt_uploaded_at', %s)
                            ON DUPLICATE KEY UPDATE meta_value = %s",
                            $license->id,
                            current_time( 'mysql' ),
                            current_time( 'mysql' )
                        ));

                        // Save original filename
                        $wpdb->query( $wpdb->prepare(
                            "INSERT INTO {$wpdb->prefix}ipv_license_meta (license_id, meta_key, meta_value)
                            VALUES (%d, '_golden_prompt_original_filename', %s)
                            ON DUPLICATE KEY UPDATE meta_value = %s",
                            $license->id,
                            sanitize_file_name( $file['name'] ),
                            sanitize_file_name( $file['name'] )
                        ));

                        echo '<div class="notice notice-success"><p>✅ File Golden prompt caricato con successo! Il cliente potrà scaricarlo quando lo abiliti.</p></div>';
                    } else {
                        echo '<div class="notice notice-error"><p>❌ Errore durante il caricamento del file.</p></div>';
                    }
                }
            } else {
                echo '<div class="notice notice-error"><p>❌ Nessun file selezionato.</p></div>';
            }
        }

        // Get current file info
        $current_file = $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
            WHERE license_id = %d AND meta_key = '_golden_prompt_file'",
            $license->id
        ));

        $uploaded_at = $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
            WHERE license_id = %d AND meta_key = '_golden_prompt_uploaded_at'",
            $license->id
        ));

        $original_filename = $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
            WHERE license_id = %d AND meta_key = '_golden_prompt_original_filename'",
            $license->id
        ));

        $golden_enabled = $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
            WHERE license_id = %d AND meta_key = '_golden_prompt_enabled'",
            $license->id
        ));

        ?>
        <div class="wrap">
            <h1>🌟 Carica Golden Prompt</h1>

            <div class="card" style="max-width: 900px;">
                <h2>Licenza #<?php echo $license->id; ?> - <?php echo esc_html( $license->email ); ?></h2>

                <div style="background: #e7f3ff; border-left: 4px solid #2271b1; padding: 15px; margin: 20px 0;">
                    <p><strong>ℹ️ Cos'è il Golden Prompt?</strong></p>
                    <p style="margin: 10px 0;">Il <strong>Golden Prompt</strong> è uno script personalizzato che ottimizza il motore di descrizioni AI per questo cliente. È un asset proprietario che non deve essere facilmente visibile o copiabile.</p>
                </div>

                <div style="background: #fef7e0; border-left: 4px solid #f7b500; padding: 15px; margin: 20px 0;">
                    <p><strong>🔒 Sicurezza:</strong></p>
                    <ul style="margin: 10px 0 0 20px;">
                        <li>Il file viene salvato in una directory protetta (<code>/wp-content/uploads/ipv-golden-prompts/</code>)</li>
                        <li>Accesso diretto bloccato tramite .htaccess</li>
                        <li>Nome file randomizzato e non indovinabile</li>
                        <li>Scaricabile solo tramite API con licenza valida</li>
                        <li>Il cliente può scaricare solo quando lo <strong>abiliti</strong> dal toggle</li>
                    </ul>
                </div>

                <?php if ( $current_file && file_exists( $current_file ) ) : ?>
                    <div style="background: #d4edda; border-left: 4px solid #46b450; padding: 15px; margin: 20px 0;">
                        <p><strong>✅ File già caricato</strong></p>
                        <table class="form-table" style="margin: 10px 0 0 0;">
                            <tr>
                                <th style="width: 200px;">Nome originale:</th>
                                <td><strong><?php echo esc_html( $original_filename ?: basename( $current_file ) ); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Dimensione:</th>
                                <td><?php echo size_format( filesize( $current_file ) ); ?></td>
                            </tr>
                            <?php if ( $uploaded_at ) : ?>
                                <tr>
                                    <th>Caricato il:</th>
                                    <td><?php echo date_i18n( 'd/m/Y H:i', strtotime( $uploaded_at ) ); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Stato:</th>
                                <td>
                                    <?php if ( $golden_enabled ) : ?>
                                        <span style="color: #46b450; font-weight: bold;">🌟 ABILITATO</span> - Il cliente può scaricare
                                    <?php else : ?>
                                        <span style="color: #dc3232; font-weight: bold;">⭐ DISABILITATO</span> - Il cliente NON può scaricare
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                        <p style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                            <em>💡 Caricando un nuovo file, il precedente verrà eliminato automaticamente.</em>
                        </p>
                    </div>
                <?php else : ?>
                    <div style="background: #fff3cd; border-left: 4px solid #ffb900; padding: 15px; margin: 20px 0;">
                        <p><strong>⚠️ Nessun file caricato</strong></p>
                        <p>Il cliente non può ancora scaricare il Golden prompt. Carica il file personalizzato qui sotto.</p>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" style="margin-top: 30px;">
                    <?php wp_nonce_field( 'ipv_upload_golden_' . $license_id ); ?>

                    <h3 style="margin-bottom: 15px;">📤 Carica/Aggiorna File</h3>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="golden_prompt_file">File Golden Prompt (ZIP)</label>
                            </th>
                            <td>
                                <input type="file" name="golden_prompt_file" id="golden_prompt_file" accept=".zip" required style="font-size: 14px;">
                                <p class="description">Seleziona il file ZIP contenente lo script/configurazione personalizzato per questo cliente. Max 50MB.</p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                        <button type="submit" name="ipv_upload_golden_submit" class="button button-primary button-large" style="height: auto; padding: 10px 20px;">
                            📤 <?php echo $current_file ? 'Aggiorna File' : 'Carica File'; ?>
                        </button>
                        <a href="<?php echo admin_url( 'admin.php?page=ipv-vendor-licenses' ); ?>" class="button button-large" style="height: auto; padding: 10px 20px; margin-left: 10px;">
                            ← Torna alle Licenze
                        </a>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Generate Golden Prompt file from configuration
     */
    private function generate_golden_prompt_file( $license_id, $metadata ) {
        global $wpdb;

        // Build Golden Prompt template
        $template = $this->build_golden_prompt_template( $metadata );

        // Create upload directory
        $upload_dir = wp_upload_dir();
        $ipv_dir = $upload_dir['basedir'] . '/ipv-golden-prompts';

        if ( ! file_exists( $ipv_dir ) ) {
            wp_mkdir_p( $ipv_dir );
            file_put_contents( $ipv_dir . '/.htaccess', "Options -Indexes\nDeny from all" );
            file_put_contents( $ipv_dir . '/index.php', "<?php\n// Silence is golden" );
        }

        // Generate secure filename
        $secure_filename = 'golden-prompt-license-' . $license_id . '-' . wp_generate_password( 16, false ) . '.txt';
        $file_path = $ipv_dir . '/' . $secure_filename;

        // Delete old file if exists
        $old_file = $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
            WHERE license_id = %d AND meta_key = '_golden_prompt_file'",
            $license_id
        ));

        if ( $old_file && file_exists( $old_file ) ) {
            unlink( $old_file );
        }

        // Save new file
        file_put_contents( $file_path, $template );

        // Save file metadata
        $wpdb->query( $wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}ipv_license_meta (license_id, meta_key, meta_value)
            VALUES (%d, '_golden_prompt_file', %s)
            ON DUPLICATE KEY UPDATE meta_value = %s",
            $license_id, $file_path, $file_path
        ));

        $wpdb->query( $wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}ipv_license_meta (license_id, meta_key, meta_value)
            VALUES (%d, '_golden_prompt_uploaded_at', %s)
            ON DUPLICATE KEY UPDATE meta_value = %s",
            $license_id, current_time( 'mysql' ), current_time( 'mysql' )
        ));

        $wpdb->query( $wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}ipv_license_meta (license_id, meta_key, meta_value)
            VALUES (%d, '_golden_prompt_original_filename', %s)
            ON DUPLICATE KEY UPDATE meta_value = %s",
            $license_id, 'golden-prompt-generated.txt', 'golden-prompt-generated.txt'
        ));

        return $file_path;
    }

    /**
     * Build Golden Prompt template with personalized data
     */
    private function build_golden_prompt_template( $metadata ) {
        $channel_name = $metadata['_golden_channel_name'] ?? 'Il Canale';
        $telegram = $metadata['_golden_telegram'] ?? '';
        $facebook = $metadata['_golden_facebook'] ?? '';
        $instagram = $metadata['_golden_instagram'] ?? '';
        $website = $metadata['_golden_website'] ?? '';
        $donations = $metadata['_golden_donations'] ?? '';
        $sponsor_name = $metadata['_golden_sponsor_name'] ?? '';
        $sponsor_link = $metadata['_golden_sponsor_link'] ?? '';
        $support_text = $metadata['_golden_support_text'] ?? '• Lascia un like\n• Commenta\n• Condividi il video';

        // Start with BASE template structure
        $template = "# GOLDEN PROMPT - $channel_name\n";
        $template .= "# Template Personalizzato - Generato automaticamente\n";
        $template .= "# Versione: 1.0\n\n";
        $template .= "---\n\n";
        $template .= "## FORMATO OUTPUT COMPLETO\n\n";
        $template .= "```\n";
        $template .= "[TITOLO VIDEO ESATTO]\n\n";

        // DESCRIZIONE
        $template .= "✨ DESCRIZIONE\n";
        $template .= "[150-200 parole. Narrativa impersonale (terza persona).\n\n";
        $template .= "Struttura in 3 paragrafi:\n";
        $template .= "- 1° Paragrafo (50-60 parole): Hook + tema principale\n";
        $template .= "- 2° Paragrafo (50-70 parole): Sviluppo contenuti\n";
        $template .= "- 3° Paragrafo (40-50 parole): Chiusura\n\n";
        $template .= "REGOLE:\n";
        $template .= "- Usa terza persona: \"viene analizzato\", \"l'episodio tratta\"\n";
        $template .= "- NO \"noi\", \"il conduttore\"\n";
        $template .= "- Testo fluido e naturale]\n\n";

        // ARGOMENTI TRATTATI
        $template .= "🗂️ ARGOMENTI TRATTATI\n";
        $template .= "[Estratti tramite analisi AI del contenuto]\n";
        $template .= "• [Argomento 1]\n";
        $template .= "• [Argomento 2]\n";
        $template .= "• [Argomento 3]\n";
        $template .= "• [... altri argomenti]\n\n";

        // OSPITI
        $template .= "👤 OSPITI\n";
        $template .= "[Estratti tramite analisi AI]\n";
        $template .= "• [Nome Ospite] — [Ruolo/Descrizione]\n";
        $template .= "oppure: Nessun ospite presente\n\n";

        // PERSONE/ENTI MENZIONATI
        $template .= "🏛️ PERSONE / ENTI MENZIONATI\n";
        $template .= "[Estratti tramite analisi AI]\n";
        $template .= "• [Nome] — [Descrizione]\n";
        $template .= "• [Nome] — [Descrizione]\n\n";

        // SPONSOR (se presente)
        if ( !empty( $sponsor_name ) && !empty( $sponsor_link ) ) {
            $template .= "🤝 SPONSOR\n";
            $template .= "$sponsor_name\n";
            $template .= "Sostieni il progetto 👉 $sponsor_link\n\n";
        }

        // SUPPORTA IL CANALE
        $template .= "💬 SUPPORTA IL CANALE\n";
        $template .= $support_text . "\n\n";

        // CAPITOLI
        $template .= "⏱️ CAPITOLI\n";
        $template .= "⚠️ DURATA TOTALE VIDEO: [inserire durata] ⚠️\n";
        $template .= "🚨 I timestamp DEVONO coprire TUTTA la durata fino alla FINE del video!\n\n";
        $template .= "ISTRUZIONI TIMESTAMP:\n";
        $template .= "- Timestamp iniziale: 00:00 — Introduzione\n";
        $template .= "- Timestamp finale DEVE essere vicino alla fine del video\n";
        $template .= "- Genera timestamp ad OGNI CAMBIO DI ARGOMENTO\n";
        $template .= "- Distribuisci uniformemente lungo TUTTA la trascrizione\n";
        $template .= "- Video > 60 min: MINIMO 15-20 timestamp\n";
        $template .= "- Video 30-60 min: MINIMO 10-15 timestamp\n";
        $template .= "- Formato: MM:SS per video < 1 ora, H:MM:SS per video ≥ 1 ora\n\n";
        $template .= "00:00 — Introduzione\n";
        $template .= "[timestamp] — [Titolo capitolo]\n";
        $template .= "[timestamp] — [Titolo capitolo]\n";
        $template .= "...\n\n";

        // LINK UTILI
        $template .= "🔧 LINK UTILI\n";
        if ( !empty( $telegram ) ) $template .= "📱 Telegram: $telegram\n";
        if ( !empty( $facebook ) ) $template .= "👥 Facebook: $facebook\n";
        if ( !empty( $instagram ) ) $template .= "📸 Instagram: $instagram\n";
        if ( !empty( $website ) ) $template .= "🌐 Sito ufficiale: $website\n";
        if ( !empty( $donations ) ) $template .= "💝 Donazioni: $donations\n";
        $template .= "\n";

        // HASHTAG
        $template .= "🏷️ HASHTAG\n";
        $template .= "[15-20 hashtag strategici su UNA RIGA]\n";
        $template .= "```\n\n";

        $template .= "---\n\n";
        $template .= "## REGOLE CRITICHE\n\n";
        $template .= "### 🚨 CAPITOLI/TIMESTAMP\n";
        $template .= "* I timestamp DEVONO coprire TUTTA la durata del video FINO ALLA FINE\n";
        $template .= "* L'ULTIMO timestamp deve essere vicino alla fine\n";
        $template .= "* NON FERMARTI A METÀ VIDEO!\n\n";
        $template .= "### 🚫 TIMESTAMP FINALE\n";
        $template .= "UN SOLO timestamp per la chiusura. NON spacchettare!\n\n";
        $template .= "❌ SBAGLIATO: 4 timestamp separati per chiusura\n";
        $template .= "✅ CORRETTO: 1 solo timestamp finale\n\n";
        $template .= "### 🏷️ HASHTAG\n";
        $template .= "15-20 hashtag su UNA RIGA, ordinati per priorità.\n\n";
        $template .= "---\n\n";
        $template .= "## OUTPUT\n\n";
        $template .= "Genera descrizione YouTube completa seguendo questo formato.\n";
        $template .= "NESSUN commento aggiuntivo.\n";

        return $template;
    }

    /**
     * Render Configure Golden Prompt page
     */
    public function render_configure_golden() {
        global $wpdb;

        // Get license ID
        $license_id = isset( $_GET['license_id'] ) ? absint( $_GET['license_id'] ) : 0;

        if ( ! $license_id ) {
            wp_die( 'License ID mancante' );
        }

        // Get license
        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE id = %d",
            $license_id
        ));

        if ( ! $license || $license->variant_slug !== 'golden_prompt' ) {
            wp_die( 'Licenza non valida o non è di tipo Golden Prompt' );
        }

        // Get current configuration
        $metadata_keys = [
            '_golden_channel_name',
            '_golden_telegram',
            '_golden_facebook',
            '_golden_instagram',
            '_golden_website',
            '_golden_donations',
            '_golden_sponsor_name',
            '_golden_sponsor_link',
            '_golden_support_text',
        ];

        $config = [];
        foreach ( $metadata_keys as $key ) {
            $value = $wpdb->get_var( $wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
                WHERE license_id = %d AND meta_key = %s",
                $license_id,
                $key
            ));
            $config[$key] = $value ?? '';
        }

        // Check if file exists
        $current_file = $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
            WHERE license_id = %d AND meta_key = '_golden_prompt_file'",
            $license_id
        ));
        $has_file = !empty($current_file) && file_exists($current_file);

        // Check if just generated
        $just_generated = isset( $_GET['golden_generated'] );

        ?>
        <div class="wrap">
            <h1>⚙️ Configura Golden Prompt - Licenza #<?php echo $license->id; ?></h1>

            <div style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
                <h2 style="margin-top: 0;">📋 Informazioni Licenza</h2>
                <p style="margin: 10px 0;">
                    <strong>Email:</strong> <?php echo esc_html( $license->email ); ?><br>
                    <strong>Chiave:</strong> <code><?php echo esc_html( $license->license_key ); ?></code><br>
                    <strong>Stato:</strong> <span style="color: <?php echo $license->status === 'active' ? '#46b450' : '#dc3232'; ?>"><?php echo esc_html( $license->status ); ?></span>
                </p>
            </div>

            <?php if ( $just_generated ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>✅ Golden Prompt generato con successo!</strong> Il file è stato creato e salvato. Ora puoi abilitarlo dal toggle nella tabella licenze.</p>
                </div>
            <?php endif; ?>

            <?php if ( $has_file ) : ?>
                <div style="background: #d4edda; border-left: 4px solid #46b450; padding: 15px; margin: 20px 0;">
                    <p><strong>✅ Golden Prompt già generato</strong></p>
                    <p>Puoi modificare la configurazione e rigenerare il file in qualsiasi momento.</p>
                </div>
            <?php else : ?>
                <div style="background: #fff3cd; border-left: 4px solid #f7b500; padding: 15px; margin: 20px 0;">
                    <p><strong>⚠️ Nessun Golden Prompt configurato</strong></p>
                    <p>Compila il form sottostante per generare il Golden Prompt personalizzato per questo cliente.</p>
                </div>
            <?php endif; ?>

            <div style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px; margin: 20px 0;">
                <h3 style="margin-top: 0;">💡 Come Funziona</h3>
                <p>Il <strong>Golden Prompt</strong> è un template personalizzato che il CLIENT plugin scaricherà automaticamente quando abilitato.</p>
                <p><strong>Configurazione automatica:</strong></p>
                <ol style="margin: 10px 0 0 20px;">
                    <li>Compila i campi sottostanti con i dati del cliente</li>
                    <li>Clicca "Genera Golden Prompt" per creare il file</li>
                    <li>Il sistema genererà automaticamente un file .txt personalizzato</li>
                    <li>Abilita il Golden Prompt dal toggle nella tabella licenze</li>
                    <li>Il CLIENT scaricherà automaticamente il template alla prossima sincronizzazione</li>
                </ol>
                <p><strong>Sezioni automatiche:</strong> Argomenti, Ospiti, Persone/Enti vengono estratti automaticamente dal CLIENT tramite AI.</p>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field( 'ipv_generate_golden_' . $license_id ); ?>
                <input type="hidden" name="license_id" value="<?php echo $license_id; ?>">

                <div style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
                    <h2 style="margin-top: 0;">🎬 Informazioni Canale</h2>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="channel_name">Nome Canale *</label>
                            </th>
                            <td>
                                <input type="text" name="channel_name" id="channel_name" class="regular-text"
                                       value="<?php echo esc_attr( $config['_golden_channel_name'] ); ?>" required>
                                <p class="description">Es: Il Punto di Vista</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
                    <h2 style="margin-top: 0;">🔗 Link Social</h2>
                    <p class="description">Inserisci i link completi (con https://). Lascia vuoto se non disponibile.</p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="telegram">📱 Telegram</label>
                            </th>
                            <td>
                                <input type="url" name="telegram" id="telegram" class="large-text"
                                       value="<?php echo esc_attr( $config['_golden_telegram'] ); ?>"
                                       placeholder="https://t.me/canale">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="facebook">👥 Facebook</label>
                            </th>
                            <td>
                                <input type="url" name="facebook" id="facebook" class="large-text"
                                       value="<?php echo esc_attr( $config['_golden_facebook'] ); ?>"
                                       placeholder="https://facebook.com/groups/...">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="instagram">📸 Instagram</label>
                            </th>
                            <td>
                                <input type="url" name="instagram" id="instagram" class="large-text"
                                       value="<?php echo esc_attr( $config['_golden_instagram'] ); ?>"
                                       placeholder="https://instagram.com/...">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="website">🌐 Sito Ufficiale</label>
                            </th>
                            <td>
                                <input type="url" name="website" id="website" class="large-text"
                                       value="<?php echo esc_attr( $config['_golden_website'] ); ?>"
                                       placeholder="https://...">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="donations">💝 Donazioni</label>
                            </th>
                            <td>
                                <input type="url" name="donations" id="donations" class="large-text"
                                       value="<?php echo esc_attr( $config['_golden_donations'] ); ?>"
                                       placeholder="https://paypal.me/...">
                            </td>
                        </tr>
                    </table>
                </div>

                <div style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
                    <h2 style="margin-top: 0;">🤝 Sponsor (Opzionale)</h2>
                    <p class="description">Se il cliente ha uno sponsor fisso, inserisci i dati qui.</p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="sponsor_name">Nome Sponsor</label>
                            </th>
                            <td>
                                <input type="text" name="sponsor_name" id="sponsor_name" class="large-text"
                                       value="<?php echo esc_attr( $config['_golden_sponsor_name'] ); ?>"
                                       placeholder="Es: Biovital – Progetto Italia">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="sponsor_link">Link Sponsor</label>
                            </th>
                            <td>
                                <input type="url" name="sponsor_link" id="sponsor_link" class="large-text"
                                       value="<?php echo esc_attr( $config['_golden_sponsor_link'] ); ?>"
                                       placeholder="https://...">
                            </td>
                        </tr>
                    </table>
                </div>

                <div style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
                    <h2 style="margin-top: 0;">💬 Supporta il Canale</h2>
                    <p class="description">Testo per la call-to-action. Usa • per gli elenchi puntati.</p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="support_text">Testo Supporto</label>
                            </th>
                            <td>
                                <textarea name="support_text" id="support_text" rows="5" class="large-text"><?php echo esc_textarea( $config['_golden_support_text'] ?: "• Lascia un like\n• Commenta\n• Condividi il video" ); ?></textarea>
                            </td>
                        </tr>
                    </table>
                </div>

                <p class="submit" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <button type="submit" name="ipv_generate_golden_submit" class="button button-primary button-large" style="height: auto; padding: 10px 20px;">
                        ✨ <?php echo $has_file ? 'Rigenera Golden Prompt' : 'Genera Golden Prompt'; ?>
                    </button>
                    <a href="<?php echo admin_url( 'admin.php?page=ipv-vendor-licenses' ); ?>" class="button button-large" style="height: auto; padding: 10px 20px; margin-left: 10px;">
                        ← Torna alle Licenze
                    </a>
                </p>
            </form>
        </div>
        <?php
    }

}
