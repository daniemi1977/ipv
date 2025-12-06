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

        // Get all licenses
        $licenses = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses ORDER BY created_at DESC"
        );

        ?>
        <div class="wrap">
            <h1>🔑 Gestione Licenze</h1>

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
                        <tr><td colspan="9">Nessuna licenza trovata</td></tr>
                    <?php else : ?>
                        <?php foreach ( $licenses as $license ) : ?>
                            <tr>
                                <td><?php echo esc_html( $license->id ); ?></td>
                                <td><code><?php echo esc_html( $license->license_key ); ?></code></td>
                                <td><?php echo esc_html( $license->email ); ?></td>
                                <td><strong><?php echo esc_html( ucfirst( $license->variant_slug ) ); ?></strong></td>
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
                                <td><?php echo esc_html( $license->activation_count . '/' . $license->activation_limit ); ?></td>
                                <td>
                                    <?php
                                    if ( $license->expires_at ) {
                                        echo date_i18n( 'd/m/Y', strtotime( $license->expires_at ) );
                                    } else {
                                        echo 'Mai (subscription)';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url( 'admin.php?page=ipv-vendor-licenses&action=view&id=' . $license->id ); ?>" class="button button-small">Dettagli</a>
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

            echo '<div class="notice notice-success is-dismissible"><p>Impostazioni salvate!</p></div>';
        }

        $rotation_mode = get_option( 'ipv_supadata_rotation_mode', 'fixed' );
        ?>
        <div class="wrap">
            <h1>⚙️ Impostazioni</h1>

            <form method="post">
                <?php wp_nonce_field( 'ipv_vendor_settings' ); ?>

                <table class="form-table">
                    <tr>
                        <th>Modalità Rotazione SupaData Keys</th>
                        <td>
                            <select name="supadata_rotation_mode">
                                <option value="fixed" <?php selected( $rotation_mode, 'fixed' ); ?>>Fissa (usa sempre key 1)</option>
                                <option value="round-robin" <?php selected( $rotation_mode, 'round-robin' ); ?>>Round-Robin (rotazione automatica)</option>
                            </select>
                            <p class="description">Modalità di rotazione per le 3 API keys SupaData configurate.</p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="ipv_save_settings" class="button button-primary">Salva Impostazioni</button>
                </p>
            </form>

            <div class="card" style="margin-top: 20px;">
                <h3>⚠️ Configurazione API Keys</h3>
                <p>Le API keys sono configurate in <code>includes/class-api-gateway.php</code></p>
                <p><strong>IMPORTANTE:</strong> Modifica le costanti nel file prima del deploy:</p>
                <ul>
                    <li><code>YOUTUBE_API_KEY</code></li>
                    <li><code>SUPADATA_API_KEY_1, _2, _3</code></li>
                    <li><code>OPENAI_API_KEY</code></li>
                </ul>
            </div>
        </div>
        <?php
    }
}
