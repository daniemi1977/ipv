<?php
/**
 * IPV Customer Portal
 *
 * Interfaccia frontend per i clienti (My Account extensions)
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

    private function __construct() {
        // Add license tab to My Account
        add_filter( 'woocommerce_account_menu_items', [ $this, 'add_license_menu_item' ] );
        add_action( 'init', [ $this, 'add_endpoints' ] );
        add_action( 'woocommerce_account_ipv-licenses_endpoint', [ $this, 'render_licenses_page' ] );

        // Dashboard widget
        add_action( 'woocommerce_account_dashboard', [ $this, 'render_dashboard_widget' ] );
    }

    public function add_endpoints() {
        add_rewrite_endpoint( 'ipv-licenses', EP_ROOT | EP_PAGES );
    }

    public function add_license_menu_item( $items ) {
        // Insert before "Logout"
        $logout = $items['customer-logout'];
        unset( $items['customer-logout'] );

        $items['ipv-licenses'] = 'Licenze IPV Pro';
        $items['customer-logout'] = $logout;

        return $items;
    }

    public function render_licenses_page() {
        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            return;
        }

        global $wpdb;

        // Get user's licenses
        $licenses = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));

        ?>
        <h2>🔑 Le Mie Licenze IPV Pro</h2>

        <?php if ( empty( $licenses ) ) : ?>
            <p>Non hai ancora licenze IPV Pro. <a href="<?php echo home_url( '/ipv-pro/' ); ?>">Acquista ora!</a></p>
        <?php else : ?>
            <?php foreach ( $licenses as $license ) :
                $credits_manager = IPV_Vendor_Credits_Manager::instance();
                $credits_info = $credits_manager->get_credits_info( $license );

                // Get activations
                $activations = $wpdb->get_results( $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}ipv_activations WHERE license_id = %d ORDER BY activated_at DESC",
                    $license->id
                ));

                $download_url = wp_nonce_url(
                    home_url( '/?download-ipv-pro=1&license=' . $license->license_key ),
                    'download_ipv_pro'
                );
                ?>
                <div class="woocommerce-ipv-license-box" style="background: #f9f9f9; border: 1px solid #e5e5e5; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <div>
                            <h3 style="margin: 0 0 10px 0; color: #667eea;">
                                Piano <?php echo esc_html( ucfirst( $license->variant_slug ) ); ?>
                            </h3>
                            <div style="background: white; padding: 10px 15px; border-radius: 3px; font-family: monospace; font-size: 18px; letter-spacing: 1px; display: inline-block;">
                                <?php echo esc_html( $license->license_key ); ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <?php
                            $status_labels = [
                                'active' => '<span style="background: #46b450; color: white; padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold;">✓ ATTIVA</span>',
                                'cancelled' => '<span style="background: #dc3232; color: white; padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold;">✗ CANCELLATA</span>',
                                'expired' => '<span style="background: #dc3232; color: white; padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold;">✗ SCADUTA</span>',
                                'on-hold' => '<span style="background: #ffb900; color: white; padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold;">⊗ SOSPESA</span>'
                            ];
                            echo $status_labels[ $license->status ] ?? esc_html( $license->status );
                            ?>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 20px 0;">
                        <div style="background: white; padding: 15px; border-radius: 3px;">
                            <div style="color: #666; font-size: 12px; margin-bottom: 5px;">CREDITI DISPONIBILI</div>
                            <div style="font-size: 24px; font-weight: bold; color: <?php echo $credits_info['status'] === 'critical' ? '#dc3232' : '#667eea'; ?>;">
                                <?php echo esc_html( $credits_info['credits_remaining'] ); ?> / <?php echo esc_html( $credits_info['credits_total'] ); ?>
                            </div>
                            <div style="background: #f0f0f0; height: 8px; border-radius: 4px; margin-top: 10px; overflow: hidden;">
                                <div style="background: <?php echo $credits_info['status'] === 'critical' ? '#dc3232' : '#667eea'; ?>; height: 100%; width: <?php echo $credits_info['percentage']; ?>%;"></div>
                            </div>
                        </div>

                        <div style="background: white; padding: 15px; border-radius: 3px;">
                            <div style="color: #666; font-size: 12px; margin-bottom: 5px;">RESET CREDITI</div>
                            <div style="font-size: 18px; font-weight: bold; color: #333;">
                                <?php echo esc_html( $credits_info['reset_date_formatted'] ); ?>
                            </div>
                            <div style="color: #666; font-size: 12px; margin-top: 5px;">
                                tra <?php echo esc_html( $credits_info['days_until_reset'] ); ?> giorni
                            </div>
                        </div>

                        <div style="background: white; padding: 15px; border-radius: 3px;">
                            <div style="color: #666; font-size: 12px; margin-bottom: 5px;">SITI ATTIVATI</div>
                            <div style="font-size: 24px; font-weight: bold; color: #333;">
                                <?php echo esc_html( $license->activation_count ); ?> / <?php echo esc_html( $license->activation_limit ); ?>
                            </div>
                        </div>
                    </div>

                    <?php if ( ! empty( $activations ) ) : ?>
                        <details style="margin: 15px 0;">
                            <summary style="cursor: pointer; font-weight: bold; color: #667eea;">
                                📍 Siti Attivi (<?php echo count( $activations ); ?>)
                            </summary>
                            <table style="width: 100%; margin-top: 10px; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: white;">
                                        <th style="text-align: left; padding: 8px; border-bottom: 2px solid #e5e5e5;">Sito</th>
                                        <th style="text-align: left; padding: 8px; border-bottom: 2px solid #e5e5e5;">Attivato il</th>
                                        <th style="text-align: left; padding: 8px; border-bottom: 2px solid #e5e5e5;">Ultimo Check</th>
                                        <th style="text-align: left; padding: 8px; border-bottom: 2px solid #e5e5e5;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $activations as $activation ) : ?>
                                        <tr style="background: white;">
                                            <td style="padding: 8px; border-bottom: 1px solid #f0f0f0;">
                                                <strong><?php echo esc_html( $activation->site_name ?: $activation->site_url ); ?></strong><br>
                                                <small style="color: #666;"><?php echo esc_html( $activation->site_url ); ?></small>
                                            </td>
                                            <td style="padding: 8px; border-bottom: 1px solid #f0f0f0;">
                                                <?php echo date_i18n( 'd/m/Y H:i', strtotime( $activation->activated_at ) ); ?>
                                            </td>
                                            <td style="padding: 8px; border-bottom: 1px solid #f0f0f0;">
                                                <?php echo date_i18n( 'd/m/Y H:i', strtotime( $activation->last_checked_at ) ); ?>
                                            </td>
                                            <td style="padding: 8px; border-bottom: 1px solid #f0f0f0;">
                                                <?php echo $activation->is_active ? '<span style="color: #46b450;">● Attivo</span>' : '<span style="color: #dc3232;">○ Inattivo</span>'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </details>
                    <?php endif; ?>

                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e5e5;">
                        <a href="<?php echo esc_url( $download_url ); ?>" class="button" style="background: #667eea; color: white; border: none; margin-right: 10px;">
                            📥 Scarica Plugin
                        </a>
                        <a href="<?php echo home_url( '/docs/ipv-pro/' ); ?>" class="button">
                            📖 Documentazione
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php
    }

    public function render_dashboard_widget() {
        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            return;
        }

        global $wpdb;

        // Get user's active licenses
        $active_licenses = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_licenses WHERE user_id = %d AND status = 'active'",
            $user_id
        ));

        if ( $active_licenses == 0 ) {
            return;
        }

        ?>
        <div class="woocommerce-ipv-dashboard-widget" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
            <h3 style="color: white; margin-top: 0;">🎬 IPV Production System Pro</h3>
            <p style="margin: 10px 0;">Hai <strong><?php echo $active_licenses; ?></strong> licenza/e attiva/e</p>
            <a href="<?php echo wc_get_account_endpoint_url( 'ipv-licenses' ); ?>" class="button" style="background: white; color: #667eea; border: none; font-weight: bold;">
                Gestisci Licenze →
            </a>
        </div>
        <?php
    }
}
