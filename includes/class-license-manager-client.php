<?php
/**
 * IPV License Manager Client
 *
 * Gestisce license lato client, UI attivazione, dashboard crediti
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Prod_License_Manager_Client {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Add license menu
        add_action( 'admin_menu', [ $this, 'add_license_menu' ] );

        // AJAX handlers
        add_action( 'wp_ajax_ipv_activate_license', [ $this, 'ajax_activate_license' ] );
        add_action( 'wp_ajax_ipv_deactivate_license', [ $this, 'ajax_deactivate_license' ] );
        add_action( 'wp_ajax_ipv_refresh_license', [ $this, 'ajax_refresh_license' ] );

        // Admin notices
        add_action( 'admin_notices', [ $this, 'license_notices' ] );

        // Credits dashboard widget
        add_action( 'wp_dashboard_setup', [ $this, 'add_credits_dashboard_widget' ] );
    }

    /**
     * Add license menu
     */
    public function add_license_menu() {
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            '🔑 Licenza IPV Pro',
            '🔑 Licenza',
            'manage_options',
            'ipv-license',
            [ $this, 'render_license_page' ]
        );
    }

    /**
     * Render license activation page
     */
    public function render_license_page() {
        $api_client = IPV_Prod_API_Client::instance();
        $license_key = get_option( 'ipv_license_key', '' );
        $license_info = get_option( 'ipv_license_info', [] );
        $is_activated = ! empty( $license_key );

        // Refresh license info if activated
        if ( $is_activated ) {
            $fresh_info = $api_client->get_license_info();
            if ( ! is_wp_error( $fresh_info ) && isset( $fresh_info['license'] ) ) {
                $license_info = $fresh_info['license'];
            }
        }

        ?>
        <div class="wrap ipv-license-page">
            <h1>🔑 IPV Pro - Gestione Licenza</h1>

            <?php if ( ! $is_activated ) : ?>
                <!-- ACTIVATION FORM -->
                <div class="card" style="max-width: 600px;">
                    <h2>Attiva la tua Licenza</h2>
                    <p>Inserisci la license key ricevuta via email dopo l'acquisto.</p>

                    <form id="ipv-license-form" method="post">
                        <table class="form-table">
                            <tr>
                                <th><label for="license_key">License Key</label></th>
                                <td>
                                    <input type="text"
                                           id="license_key"
                                           name="license_key"
                                           class="regular-text code"
                                           placeholder="XXXX-XXXX-XXXX-XXXX"
                                           pattern="[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}"
                                           required
                                           style="font-size: 16px; letter-spacing: 1px;">
                                    <p class="description">Formato: XXXX-XXXX-XXXX-XXXX</p>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <button type="submit" class="button button-primary button-large" id="ipv-activate-btn">
                                <span class="dashicons dashicons-yes-alt" style="margin-top: 3px;"></span>
                                Attiva Licenza
                            </button>
                        </p>

                        <div id="ipv-license-result"></div>
                    </form>

                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e5e5;">
                        <h3>❓ Non hai ancora una licenza?</h3>
                        <p><a href="https://bissolomarket.com/ipv-pro/" class="button" target="_blank">Acquista IPV Pro →</a></p>
                    </div>
                </div>

            <?php else : ?>
                <!-- LICENSE INFO -->
                <div class="card" style="max-width: 800px;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <h2 style="margin-top: 0;">Licenza Attiva</h2>
                        </div>
                        <div>
                            <?php
                            $status_labels = [
                                'active' => '<span style="background: #46b450; color: white; padding: 5px 12px; border-radius: 3px; font-weight: bold;">✓ ATTIVA</span>',
                                'cancelled' => '<span style="background: #dc3232; color: white; padding: 5px 12px; border-radius: 3px; font-weight: bold;">✗ CANCELLATA</span>',
                                'expired' => '<span style="background: #dc3232; color: white; padding: 5px 12px; border-radius: 3px; font-weight: bold;">✗ SCADUTA</span>',
                                'on-hold' => '<span style="background: #ffb900; color: white; padding: 5px 12px; border-radius: 3px; font-weight: bold;">⊗ SOSPESA</span>'
                            ];
                            echo $status_labels[ $license_info['status'] ?? 'active' ] ?? '';
                            ?>
                        </div>
                    </div>

                    <table class="widefat">
                        <tbody>
                            <tr>
                                <th style="width: 200px;">License Key:</th>
                                <td>
                                    <code style="font-size: 16px; letter-spacing: 1px; padding: 8px 12px; background: #f5f5f5; border-radius: 3px;">
                                        <?php echo esc_html( $license_key ); ?>
                                    </code>
                                </td>
                            </tr>
                            <tr>
                                <th>Piano:</th>
                                <td><strong><?php echo esc_html( ucfirst( $license_info['variant'] ?? 'Unknown' ) ); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo esc_html( $license_info['email'] ?? '' ); ?></td>
                            </tr>
                            <tr>
                                <th>Attivazioni:</th>
                                <td>
                                    <?php
                                    $count = $license_info['activation_count'] ?? 0;
                                    $limit = $license_info['activation_limit'] ?? 1;
                                    echo esc_html( $count . '/' . $limit . ' siti' );
                                    ?>
                                </td>
                            </tr>
                            <?php if ( isset( $license_info['expires_at'] ) && $license_info['expires_at'] ) : ?>
                            <tr>
                                <th>Scadenza:</th>
                                <td><?php echo date_i18n( 'd/m/Y', strtotime( $license_info['expires_at'] ) ); ?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if ( isset( $license_info['credits'] ) ) : ?>
                        <div style="margin: 20px 0;">
                            <h3>📊 Crediti Disponibili</h3>
                            <?php $this->render_credits_info( $license_info['credits'] ); ?>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e5e5;">
                        <button type="button" class="button" id="ipv-refresh-license-btn">
                            <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
                            Aggiorna Info
                        </button>
                        <button type="button" class="button button-link-delete" id="ipv-deactivate-license-btn" style="margin-left: 10px;">
                            <span class="dashicons dashicons-dismiss" style="margin-top: 3px;"></span>
                            Disattiva Licenza
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .ipv-license-page .card { padding: 20px; }
        #ipv-license-result { margin-top: 15px; }
        .ipv-credits-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 5px;
        }
        .ipv-credits-bar {
            background: rgba(255,255,255,0.3);
            height: 12px;
            border-radius: 6px;
            overflow: hidden;
            margin-top: 10px;
        }
        .ipv-credits-bar-fill {
            background: white;
            height: 100%;
            transition: width 0.3s;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Activate license
            $('#ipv-license-form').on('submit', function(e) {
                e.preventDefault();

                var $btn = $('#ipv-activate-btn');
                var $result = $('#ipv-license-result');
                var license_key = $('#license_key').val().trim().toUpperCase();

                $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Attivazione...');
                $result.html('');

                $.post(ajaxurl, {
                    action: 'ipv_activate_license',
                    license_key: license_key,
                    nonce: '<?php echo wp_create_nonce( 'ipv_license_action' ); ?>'
                }, function(response) {
                    if (response.success) {
                        $result.html('<div class="notice notice-success"><p><strong>✓ Licenza attivata con successo!</strong> Ricarico la pagina...</p></div>');
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        $result.html('<div class="notice notice-error"><p><strong>Errore:</strong> ' + response.data + '</p></div>');
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> Attiva Licenza');
                    }
                });
            });

            // Deactivate license
            $('#ipv-deactivate-license-btn').on('click', function() {
                if (!confirm('Sei sicuro di voler disattivare la licenza da questo sito?')) {
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Disattivazione...');

                $.post(ajaxurl, {
                    action: 'ipv_deactivate_license',
                    nonce: '<?php echo wp_create_nonce( 'ipv_license_action' ); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('Licenza disattivata con successo!');
                        location.reload();
                    } else {
                        alert('Errore: ' + response.data);
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-dismiss"></span> Disattiva Licenza');
                    }
                });
            });

            // Refresh license
            $('#ipv-refresh-license-btn').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Aggiornamento...');

                $.post(ajaxurl, {
                    action: 'ipv_refresh_license',
                    nonce: '<?php echo wp_create_nonce( 'ipv_license_action' ); ?>'
                }, function(response) {
                    location.reload();
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render credits info box
     */
    private function render_credits_info( $credits ) {
        $percentage = $credits['percentage'] ?? 0;
        $status = $credits['status'] ?? 'ok';

        $color = '#667eea';
        if ( $status === 'critical' ) $color = '#dc3232';
        elseif ( $status === 'low' ) $color = '#ffb900';
        ?>
        <div class="ipv-credits-box" style="background: linear-gradient(135deg, <?php echo $color; ?> 0%, #764ba2 100%);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 14px; opacity: 0.9;">Crediti Disponibili</div>
                    <div style="font-size: 36px; font-weight: bold; margin-top: 5px;">
                        <?php echo esc_html( $credits['credits_remaining'] ?? 0 ); ?> / <?php echo esc_html( $credits['credits_total'] ?? 0 ); ?>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 14px; opacity: 0.9;">Reset tra</div>
                    <div style="font-size: 24px; font-weight: bold; margin-top: 5px;">
                        <?php echo esc_html( $credits['days_until_reset'] ?? 0 ); ?> giorni
                    </div>
                    <div style="font-size: 12px; opacity: 0.8; margin-top: 3px;">
                        <?php echo esc_html( $credits['reset_date_formatted'] ?? '' ); ?>
                    </div>
                </div>
            </div>
            <div class="ipv-credits-bar">
                <div class="ipv-credits-bar-fill" style="width: <?php echo esc_attr( $percentage ); ?>%;"></div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Activate license
     */
    public function ajax_activate_license() {
        check_ajax_referer( 'ipv_license_action', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Non autorizzato' );
        }

        $license_key = strtoupper( sanitize_text_field( $_POST['license_key'] ) );

        $api_client = IPV_Prod_API_Client::instance();
        $result = $api_client->activate_license( $license_key );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( 'Licenza attivata!' );
    }

    /**
     * AJAX: Deactivate license
     */
    public function ajax_deactivate_license() {
        check_ajax_referer( 'ipv_license_action', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Non autorizzato' );
        }

        $api_client = IPV_Prod_API_Client::instance();
        $result = $api_client->deactivate_license();

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( 'Licenza disattivata!' );
    }

    /**
     * AJAX: Refresh license
     */
    public function ajax_refresh_license() {
        check_ajax_referer( 'ipv_license_action', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Non autorizzato' );
        }

        $api_client = IPV_Prod_API_Client::instance();
        $result = $api_client->get_license_info();

        wp_send_json_success( 'Info aggiornate!' );
    }

    /**
     * License notices
     */
    public function license_notices() {
        $screen = get_current_screen();

        // Only on IPV pages
        if ( ! $screen || strpos( $screen->id, 'ipv' ) === false ) {
            return;
        }

        $license_key = get_option( 'ipv_license_key', '' );

        if ( empty( $license_key ) ) {
            ?>
            <div class="notice notice-warning">
                <p><strong>⚠️ IPV Pro:</strong> License non attivata. <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video&page=ipv-license' ); ?>">Attiva ora →</a></p>
            </div>
            <?php
            return;
        }

        // Check credits
        $license_info = get_option( 'ipv_license_info', [] );
        $credits = $license_info['credits'] ?? [];
        $status = $credits['status'] ?? 'ok';

        if ( $status === 'depleted' ) {
            ?>
            <div class="notice notice-error">
                <p><strong>🚫 Crediti Esauriti:</strong> Hai finito i crediti mensili. Reset: <?php echo esc_html( $credits['reset_date_formatted'] ?? '' ); ?></p>
            </div>
            <?php
        } elseif ( $status === 'critical' ) {
            ?>
            <div class="notice notice-warning">
                <p><strong>⚠️ Crediti in esaurimento:</strong> Solo <?php echo esc_html( $credits['credits_remaining'] ?? 0 ); ?> crediti rimanenti.</p>
            </div>
            <?php
        }
    }

    /**
     * Add credits dashboard widget
     */
    public function add_credits_dashboard_widget() {
        $license_key = get_option( 'ipv_license_key', '' );

        if ( empty( $license_key ) ) {
            return;
        }

        wp_add_dashboard_widget(
            'ipv_credits_widget',
            '🎬 IPV Pro - Crediti',
            [ $this, 'render_credits_widget' ]
        );
    }

    /**
     * Render credits dashboard widget
     */
    public function render_credits_widget() {
        $license_info = get_option( 'ipv_license_info', [] );
        $credits = $license_info['credits'] ?? [];

        if ( empty( $credits ) ) {
            echo '<p>Caricamento...</p>';
            return;
        }

        $this->render_credits_info( $credits );

        echo '<p style="margin-top: 15px; text-align: center;">';
        echo '<a href="' . admin_url( 'edit.php?post_type=ipv_video&page=ipv-license' ) . '" class="button">Gestisci Licenza</a>';
        echo '</p>';
    }
}
