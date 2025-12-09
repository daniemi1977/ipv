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
                                    ?>
                                    <a href="<?php echo esc_url( $toggle_url ); ?>" class="button button-small"><?php echo $toggle_text; ?></a>
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

}
