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

        // Analytics
        add_submenu_page(
            'ipv-vendor-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'ipv-vendor-analytics',
            [ $this, 'render_analytics' ]
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
        <div class="wrap">
            <h1>🎬 IPV Pro Vendor Dashboard</h1>

            <div class="ipv-stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0;">
                <div class="ipv-stat-box" style="background: white; padding: 20px; border-left: 4px solid #667eea; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="color: #666; font-size: 14px;">Licenze Totali</div>
                    <div style="font-size: 32px; font-weight: bold; color: #667eea;"><?php echo number_format_i18n( $total_licenses ); ?></div>
                </div>
                <div class="ipv-stat-box" style="background: white; padding: 20px; border-left: 4px solid #46b450; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="color: #666; font-size: 14px;">Licenze Attive</div>
                    <div style="font-size: 32px; font-weight: bold; color: #46b450;"><?php echo number_format_i18n( $active_licenses ); ?></div>
                </div>
                <div class="ipv-stat-box" style="background: white; padding: 20px; border-left: 4px solid #ffb900; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="color: #666; font-size: 14px;">Attivazioni Siti</div>
                    <div style="font-size: 32px; font-weight: bold; color: #ffb900;"><?php echo number_format_i18n( $total_activations ); ?></div>
                </div>
                <div class="ipv-stat-box" style="background: white; padding: 20px; border-left: 4px solid #00a0d2; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="color: #666; font-size: 14px;">API Calls (30gg)</div>
                    <div style="font-size: 32px; font-weight: bold; color: #00a0d2;"><?php echo number_format_i18n( $total_api_calls ); ?></div>
                </div>
            </div>

            <div class="card">
                <h2>📋 Licenze Recenti</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>License Key</th>
                            <th>Email</th>
                            <th>Piano</th>
                            <th>Status</th>
                            <th>Crediti</th>
                            <th>Data Creazione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( empty( $recent_licenses ) ) : ?>
                            <tr><td colspan="6">Nessuna licenza trovata</td></tr>
                        <?php else : ?>
                            <?php foreach ( $recent_licenses as $license ) : ?>
                                <tr>
                                    <td><code><?php echo esc_html( $license->license_key ); ?></code></td>
                                    <td><?php echo esc_html( $license->email ); ?></td>
                                    <td><?php echo esc_html( ucfirst( $license->variant_slug ) ); ?></td>
                                    <td>
                                        <?php
                                        $status_colors = [
                                            'active' => '#46b450',
                                            'cancelled' => '#dc3232',
                                            'expired' => '#dc3232',
                                            'on-hold' => '#ffb900'
                                        ];
                                        $color = $status_colors[ $license->status ] ?? '#666';
                                        echo '<span style="color: ' . $color . '; font-weight: bold;">' . esc_html( $license->status ) . '</span>';
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

            <div class="card" style="margin-top: 20px;">
                <h2>🔗 API Endpoints</h2>
                <table class="widefat">
                    <tbody>
                        <tr>
                            <th style="width: 200px;">Health Check:</th>
                            <td><code><?php echo esc_url( rest_url( 'ipv-vendor/v1/health' ) ); ?></code></td>
                        </tr>
                        <tr>
                            <th>License Activation:</th>
                            <td><code><?php echo esc_url( rest_url( 'ipv-vendor/v1/license/activate' ) ); ?></code></td>
                        </tr>
                        <tr>
                            <th>Transcript Gateway:</th>
                            <td><code><?php echo esc_url( rest_url( 'ipv-vendor/v1/transcript' ) ); ?></code></td>
                        </tr>
                        <tr>
                            <th>Plugin Info:</th>
                            <td><code><?php echo esc_url( rest_url( 'ipv-vendor/v1/plugin-info' ) ); ?></code></td>
                        </tr>
                    </tbody>
                </table>
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
                        'status' => 'active',
                        'variant_slug' => $variant,
                        'credits_total' => $credits,
                        'credits_remaining' => $credits,
                        'credits_reset_date' => date( 'Y-m-01', strtotime( '+1 month' ) ),
                        'activation_limit' => $activations,
                        'activation_count' => 0,
                        'expires_at' => $expires_at
                    ],
                    [ '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%d', '%s' ]
                );

                if ( $inserted ) {
                    echo '<div class="notice notice-success"><p>✅ Licenza creata: <code>' . esc_html( $license_key ) . '</code> (Piano: ' . esc_html( $plan['name'] ?? $variant ) . ', Crediti: ' . $credits . ', Siti: ' . $activations . ')</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>Errore creazione licenza!</p></div>';
                }
            }
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

        // Get all licenses
        $licenses = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses ORDER BY created_at DESC"
        );

        ?>
        <div class="wrap">
            <h1>🔑 Gestione Licenze</h1>

            <!-- CREATE LICENSE FORM -->
            <div class="card" style="max-width: 600px; margin-bottom: 20px;">
                <h2>➕ Crea Licenza Manuale</h2>
                <form method="post">
                    <?php wp_nonce_field( 'ipv_create_license_nonce' ); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="license_email">Email *</label></th>
                            <td><input type="email" name="license_email" id="license_email" class="regular-text" required /></td>
                        </tr>
                        <tr>
                            <th><label for="license_variant">Piano</label></th>
                            <td>
                                <select name="license_variant" id="license_variant">
                                    <?php 
                                    $plan_options = apply_filters( 'ipv_vendor_plan_options', [] );
                                    foreach ( $plan_options as $slug => $label ) :
                                    ?>
                                        <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $slug, 'professional' ); ?>>
                                            <?php echo esc_html( $label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="license_credits">Crediti Mensili</label></th>
                            <td>
                                <input type="number" name="license_credits" id="license_credits" placeholder="Auto dal piano" min="1" max="9999" />
                                <p class="description">Lascia vuoto per usare il valore del piano</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="license_activations">Limite Siti</label></th>
                            <td>
                                <input type="number" name="license_activations" id="license_activations" placeholder="Auto dal piano" min="1" max="100" />
                                <p class="description">Lascia vuoto per usare il valore del piano</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="license_expires">Scadenza (giorni)</label></th>
                            <td>
                                <input type="number" name="license_expires" id="license_expires" value="0" min="0" max="3650" />
                                <p class="description">0 = Mai (subscription attiva)</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" name="ipv_create_license" class="button button-primary">🔑 Genera Licenza</button>
                    </p>
                </form>
            </div>

            <!-- LICENSES TABLE -->
            <h2>📋 Licenze Esistenti (<?php echo count( $licenses ); ?>)</h2>
            <table class="wp-list-table widefat fixed striped">
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
                        <tr><td colspan="9">Nessuna licenza trovata. Crea la prima licenza sopra! ☝️</td></tr>
                    <?php else : ?>
                        <?php foreach ( $licenses as $license ) : ?>
                            <tr>
                                <td><?php echo esc_html( $license->id ); ?></td>
                                <td>
                                    <code style="font-size: 12px; background: #f0f0f1; padding: 2px 6px;"><?php echo esc_html( $license->license_key ); ?></code>
                                    <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('<?php echo esc_attr( $license->license_key ); ?>'); this.textContent='✓';" title="Copia">📋</button>
                                </td>
                                <td><?php echo esc_html( $license->email ); ?></td>
                                <td><strong><?php echo esc_html( ucfirst( $license->variant_slug ) ); ?></strong></td>
                                <td>
                                    <?php
                                    $status_colors = [
                                        'active' => '#46b450',
                                        'suspended' => '#dc3232',
                                        'cancelled' => '#dc3232',
                                        'expired' => '#dc3232',
                                        'on-hold' => '#ffb900'
                                    ];
                                    $color = $status_colors[ $license->status ] ?? '#666';
                                    echo '<span style="color: ' . $color . '; font-weight: bold;">' . esc_html( $license->status ) . '</span>';
                                    ?>
                                </td>
                                <td><?php echo esc_html( $license->credits_remaining . '/' . $license->credits_total ); ?></td>
                                <td><?php echo esc_html( $license->activation_count . '/' . $license->activation_limit ); ?></td>
                                <td>
                                    <?php
                                    if ( $license->expires_at ) {
                                        $expires = strtotime( $license->expires_at );
                                        $color = $expires < time() ? '#dc3232' : '#666';
                                        echo '<span style="color: ' . $color . ';">' . date_i18n( 'd/m/Y', $expires ) . '</span>';
                                    } else {
                                        echo '<span style="color: #46b450;">∞ Mai</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $toggle_url = wp_nonce_url(
                                        admin_url( 'admin.php?page=ipv-vendor-licenses&action=toggle_status&id=' . $license->id ),
                                        'toggle_license_' . $license->id
                                    );
                                    $toggle_text = $license->status === 'active' ? '⏸️ Sospendi' : '▶️ Attiva';

                                    $change_plan_url = admin_url( 'admin.php?page=ipv-vendor-change-plan&license_id=' . $license->id );

                                    // Check if license is Golden prompt
                                    $is_golden_prompt = ($license->variant_slug === 'golden_prompt');

                                    // Get Golden prompt status and file
                                    if ( $is_golden_prompt ) {
                                        $golden_enabled = $wpdb->get_var( $wpdb->prepare(
                                            "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
                                            WHERE license_id = %d AND meta_key = '_golden_prompt_enabled'",
                                            $license->id
                                        ));
                                        $golden_enabled = (bool) $golden_enabled;

                                        $golden_file = $wpdb->get_var( $wpdb->prepare(
                                            "SELECT meta_value FROM {$wpdb->prefix}ipv_license_meta
                                            WHERE license_id = %d AND meta_key = '_golden_prompt_file'",
                                            $license->id
                                        ));
                                        $has_file = !empty($golden_file) && file_exists($golden_file);

                                        $toggle_golden_url = wp_nonce_url(
                                            admin_url( 'admin.php?page=ipv-vendor-licenses&action=toggle_golden&id=' . $license->id ),
                                            'toggle_golden_' . $license->id
                                        );
                                        $configure_golden_url = admin_url( 'admin.php?page=ipv-vendor-configure-golden&license_id=' . $license->id );

                                        $golden_text = $golden_enabled ? '🌟 Disabilita' : '⭐ Abilita';
                                        $golden_style = $golden_enabled ? 'background: #46b450; color: white; border-color: #46b450;' : 'background: #f7b500; color: white; border-color: #f7b500;';
                                    }
                                    ?>
                                    <a href="<?php echo esc_url( $toggle_url ); ?>" class="button button-small"><?php echo $toggle_text; ?></a>
                                    <a href="<?php echo esc_url( $change_plan_url ); ?>" class="button button-small" style="background: #667eea; color: white; border-color: #667eea;">🔄 Cambia Piano</a>
                                    <?php if ( $is_golden_prompt ) : ?>
                                        <a href="<?php echo esc_url( $configure_golden_url ); ?>" class="button button-small" style="background: #9b59b6; color: white; border-color: #9b59b6;" title="<?php echo $has_file ? 'Configurato ✓' : 'Da configurare'; ?>">
                                            ⚙️ <?php echo $has_file ? 'Modifica' : 'Configura'; ?>
                                        </a>
                                        <a href="<?php echo esc_url( $toggle_golden_url ); ?>" class="button button-small" style="<?php echo $golden_style; ?>"><?php echo $golden_text; ?></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_analytics() {
        ?>
        <div class="wrap">
            <h1>📊 Analytics</h1>
            <div class="card">
                <h2>Analytics Avanzate</h2>
                <p>Questa sezione mostrerà grafici e statistiche dettagliate sull'uso del sistema.</p>
                <p><em>Dashboard analytics in fase di sviluppo...</em></p>
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

            echo '<div class="notice notice-success is-dismissible"><p>Impostazioni salvate!</p></div>';
        }

        $rotation_mode      = get_option( 'ipv_supadata_rotation_mode', 'fixed' );
        $youtube_api_key    = get_option( 'ipv_youtube_api_key', '' );
        $supadata_api_key_1 = get_option( 'ipv_supadata_api_key_1', '' );
        $supadata_api_key_2 = get_option( 'ipv_supadata_api_key_2', '' );
        $supadata_api_key_3 = get_option( 'ipv_supadata_api_key_3', '' );
        $openai_api_key     = get_option( 'ipv_openai_api_key', '' );
        ?>
        <div class="wrap">
            <h1>⚙️ Impostazioni</h1>

            <form method="post">
                <?php wp_nonce_field( 'ipv_vendor_settings' ); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">Modalità Rotazione SupaData Keys</th>
                        <td>
                            <select name="supadata_rotation_mode">
                                <option value="fixed" <?php selected( $rotation_mode, 'fixed' ); ?>>Fissa (usa sempre key 1)</option>
                                <option value="round-robin" <?php selected( $rotation_mode, 'round-robin' ); ?>>Round-Robin (rotazione automatica)</option>
                            </select>
                            <p class="description">Modalità di rotazione per le 3 API keys SupaData configurate.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">YouTube API Key</th>
                        <td>
                            <input type="text" name="youtube_api_key" value="<?php echo esc_attr( $youtube_api_key ); ?>" class="regular-text" autocomplete="off" />
                            <p class="description">Chiave API usata per le chiamate a YouTube Data API. Lascia vuoto per usare la costante <code>YOUTUBE_API_KEY</code> definita in <code>class-api-gateway.php</code>.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">SupaData API Key 1</th>
                        <td>
                            <input type="text" name="supadata_api_key_1" value="<?php echo esc_attr( $supadata_api_key_1 ); ?>" class="regular-text" autocomplete="off" />
                            <p class="description">Prima chiave SupaData usata per le trascrizioni. Lascia vuoto per usare la costante <code>SUPADATA_API_KEY_1</code>.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">SupaData API Key 2</th>
                        <td>
                            <input type="text" name="supadata_api_key_2" value="<?php echo esc_attr( $supadata_api_key_2 ); ?>" class="regular-text" autocomplete="off" />
                            <p class="description">Seconda chiave SupaData (opzionale). Lascia vuoto per usare la costante <code>SUPADATA_API_KEY_2</code>.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">SupaData API Key 3</th>
                        <td>
                            <input type="text" name="supadata_api_key_3" value="<?php echo esc_attr( $supadata_api_key_3 ); ?>" class="regular-text" autocomplete="off" />
                            <p class="description">Terza chiave SupaData (opzionale). Lascia vuoto per usare la costante <code>SUPADATA_API_KEY_3</code>.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">OpenAI API Key</th>
                        <td>
                            <input type="text" name="openai_api_key" value="<?php echo esc_attr( $openai_api_key ); ?>" class="regular-text" autocomplete="off" />
                            <p class="description">Chiave usata per le richieste a OpenAI. Lascia vuoto per usare la costante <code>OPENAI_API_KEY</code>.</p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="ipv_save_settings" class="button button-primary">Salva Impostazioni</button>
                </p>
            </form>

            <div class="card" style="margin-top: 20px;">
                <h3>ℹ️ Configurazione avanzata</h3>
                <p>Le costanti di fallback rimangono disponibili in <code>includes/class-api-gateway.php</code>. Se compili i campi sopra, verranno usate le chiavi salvate nel database. Se lasci vuoto un campo, verrà utilizzata la costante corrispondente.</p>
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
