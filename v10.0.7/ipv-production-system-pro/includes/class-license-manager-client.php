<?php
/**
 * IPV Production System Pro - License Manager (Client-Side)
 *
 * Gestisce l'attivazione e visualizzazione della licenza
 *
 * @package IPV_Production_System_Pro
 * @version 10.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_License_Manager_Client {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_license_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'handle_license_actions' ] );
        add_action( 'wp_ajax_ipv_test_server_connection', [ __CLASS__, 'ajax_test_connection' ] );
        add_action( 'wp_ajax_ipv_refresh_license', [ __CLASS__, 'ajax_refresh_license' ] );
        
        // Notice se licenza non attiva
        add_action( 'admin_notices', [ __CLASS__, 'license_notice' ] );
    }

    /**
     * Add license page to admin menu
     */
    public static function add_license_page() {
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            __( 'Licenza', 'ipv-production-system-pro' ),
            '🔑 ' . __( 'Licenza', 'ipv-production-system-pro' ),
            'manage_options',
            'ipv-license',
            [ __CLASS__, 'render_license_page' ]
        );

        // Aggiungi anche pagina Server Settings
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            __( 'Server', 'ipv-production-system-pro' ),
            '🌐 ' . __( 'Server', 'ipv-production-system-pro' ),
            'manage_options',
            'ipv-server',
            [ __CLASS__, 'render_server_page' ]
        );
    }

    /**
     * Show notice if license not active
     */
    public static function license_notice() {
        // Solo nelle pagine IPV
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'ipv' ) === false ) {
            return;
        }

        if ( IPV_Prod_API_Client::is_license_active() ) {
            return;
        }

        ?>
        <div class="notice notice-warning">
            <p>
                <strong>IPV Production System Pro:</strong>
                <?php _e( 'Licenza non attiva. Alcune funzionalità sono disabilitate.', 'ipv-production-system-pro' ); ?>
                <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video&page=ipv-license' ); ?>">
                    <?php _e( 'Attiva ora →', 'ipv-production-system-pro' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Handle license form actions
     */
    public static function handle_license_actions() {
        if ( ! isset( $_POST['ipv_license_action'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['ipv_license_nonce'] ?? '', 'ipv_license_action' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $api = IPV_Prod_API_Client::instance();
        $action = sanitize_text_field( $_POST['ipv_license_action'] );

        switch ( $action ) {
            case 'activate':
                $license_key = sanitize_text_field( $_POST['license_key'] ?? '' );
                if ( empty( $license_key ) ) {
                    add_settings_error( 'ipv_license', 'empty_key', __( 'Inserisci una license key valida.', 'ipv-production-system-pro' ), 'error' );
                    return;
                }

                $result = $api->activate_license( $license_key );
                if ( is_wp_error( $result ) ) {
                    add_settings_error( 'ipv_license', 'activation_error', $result->get_error_message(), 'error' );
                } else {
                    add_settings_error( 'ipv_license', 'activation_success', __( '✅ Licenza attivata con successo!', 'ipv-production-system-pro' ), 'success' );
                }
                break;

            case 'deactivate':
                $result = $api->deactivate_license();
                if ( is_wp_error( $result ) ) {
                    add_settings_error( 'ipv_license', 'deactivation_error', $result->get_error_message(), 'error' );
                } else {
                    add_settings_error( 'ipv_license', 'deactivation_success', __( 'Licenza deattivata.', 'ipv-production-system-pro' ), 'info' );
                }
                break;

            case 'save_server':
                $server_url = esc_url_raw( $_POST['server_url'] ?? '' );
                update_option( 'ipv_api_server_url', $server_url );
                add_settings_error( 'ipv_license', 'server_saved', __( 'Server URL salvato.', 'ipv-production-system-pro' ), 'success' );
                break;
        }
    }

    /**
     * Render license page
     */
    public static function render_license_page() {
        $license_key = get_option( 'ipv_license_key', '' );
        $license_info = get_option( 'ipv_license_info', [] );
        $is_active = IPV_Prod_API_Client::is_license_active();
        $activated_at = get_option( 'ipv_license_activated_at', 0 );

        ?>
        <div class="wrap">
            <h1>🔑 <?php _e( 'Gestione Licenza', 'ipv-production-system-pro' ); ?></h1>

            <?php settings_errors( 'ipv_license' ); ?>

            <div style="max-width: 800px;">

                <?php if ( $is_active ) : ?>
                    <!-- Licenza Attiva -->
                    <div style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border: 1px solid #28a745; border-radius: 8px; padding: 25px; margin: 20px 0;">
                        <h2 style="color: #155724; margin-top: 0;">
                            <span class="dashicons dashicons-yes-alt" style="color: #28a745;"></span>
                            <?php _e( 'Licenza Attiva', 'ipv-production-system-pro' ); ?>
                        </h2>

                        <table class="form-table" style="margin: 0;">
                            <tr>
                                <th><?php _e( 'License Key', 'ipv-production-system-pro' ); ?></th>
                                <td>
                                    <code style="font-size: 14px; padding: 5px 10px; background: #fff;">
                                        <?php echo esc_html( substr( $license_key, 0, 4 ) . '-****-****-' . substr( $license_key, -4 ) ); ?>
                                    </code>
                                </td>
                            </tr>
                            <?php if ( ! empty( $license_info['variant'] ) ) : ?>
                            <tr>
                                <th><?php _e( 'Piano', 'ipv-production-system-pro' ); ?></th>
                                <td><strong><?php echo esc_html( ucfirst( $license_info['variant'] ) ); ?></strong></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ( ! empty( $license_info['email'] ) ) : ?>
                            <tr>
                                <th><?php _e( 'Email', 'ipv-production-system-pro' ); ?></th>
                                <td><?php echo esc_html( $license_info['email'] ); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ( ! empty( $license_info['expires_at'] ) ) : ?>
                            <tr>
                                <th><?php _e( 'Scadenza', 'ipv-production-system-pro' ); ?></th>
                                <td><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $license_info['expires_at'] ) ) ); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ( $activated_at ) : ?>
                            <tr>
                                <th><?php _e( 'Attivata il', 'ipv-production-system-pro' ); ?></th>
                                <td><?php echo esc_html( date_i18n( 'd/m/Y H:i', $activated_at ) ); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>

                        <!-- Crediti -->
                        <?php if ( ! empty( $license_info['credits'] ) ) : 
                            $credits = $license_info['credits'];
                            $percentage = $credits['percentage'] ?? 0;
                            $bar_color = $percentage > 50 ? '#28a745' : ( $percentage > 20 ? '#ffc107' : '#dc3545' );
                        ?>
                        <div style="margin-top: 20px; padding: 15px; background: #fff; border-radius: 4px;">
                            <h4 style="margin-top: 0;">📊 <?php _e( 'Crediti Mensili', 'ipv-production-system-pro' ); ?></h4>
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="flex: 1;">
                                    <div style="background: #e9ecef; border-radius: 4px; height: 20px; overflow: hidden;">
                                        <div style="background: <?php echo $bar_color; ?>; height: 100%; width: <?php echo $percentage; ?>%;"></div>
                                    </div>
                                </div>
                                <div>
                                    <strong><?php echo esc_html( $credits['credits_remaining'] ?? 0 ); ?></strong> / 
                                    <?php echo esc_html( $credits['credits_total'] ?? 0 ); ?>
                                </div>
                            </div>
                            <?php if ( ! empty( $credits['reset_date_formatted'] ) ) : ?>
                            <p style="margin-bottom: 0; color: #666; font-size: 12px;">
                                <?php printf( __( 'Reset: %s', 'ipv-production-system-pro' ), esc_html( $credits['reset_date_formatted'] ) ); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div style="margin-top: 20px;">
                            <button type="button" class="button" id="ipv-refresh-license">
                                <span class="dashicons dashicons-update" style="vertical-align: middle;"></span>
                                <?php _e( 'Aggiorna Info', 'ipv-production-system-pro' ); ?>
                            </button>

                            <form method="post" style="display: inline; margin-left: 10px;">
                                <?php wp_nonce_field( 'ipv_license_action', 'ipv_license_nonce' ); ?>
                                <input type="hidden" name="ipv_license_action" value="deactivate">
                                <button type="submit" class="button" onclick="return confirm('<?php _e( 'Sei sicuro di voler deattivare la licenza?', 'ipv-production-system-pro' ); ?>');">
                                    <?php _e( 'Deattiva Licenza', 'ipv-production-system-pro' ); ?>
                                </button>
                            </form>
                        </div>
                    </div>

                <?php else : ?>
                    <!-- Licenza Non Attiva -->
                    <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 25px; margin: 20px 0;">
                        <h2 style="color: #856404; margin-top: 0;">
                            <span class="dashicons dashicons-warning" style="color: #ffc107;"></span>
                            <?php _e( 'Licenza Non Attiva', 'ipv-production-system-pro' ); ?>
                        </h2>
                        <p><?php _e( 'Inserisci la tua license key per attivare tutte le funzionalità.', 'ipv-production-system-pro' ); ?></p>

                        <form method="post">
                            <?php wp_nonce_field( 'ipv_license_action', 'ipv_license_nonce' ); ?>
                            <input type="hidden" name="ipv_license_action" value="activate">

                            <table class="form-table">
                                <tr>
                                    <th><label for="license_key"><?php _e( 'License Key', 'ipv-production-system-pro' ); ?></label></th>
                                    <td>
                                        <input type="text" 
                                               name="license_key" 
                                               id="license_key" 
                                               class="regular-text" 
                                               placeholder="XXXX-XXXX-XXXX-XXXX"
                                               style="font-family: monospace; text-transform: uppercase;"
                                               pattern="[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}"
                                               required>
                                        <p class="description">
                                            <?php _e( 'La license key ti è stata inviata via email dopo l\'acquisto.', 'ipv-production-system-pro' ); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p>
                                <button type="submit" class="button button-primary button-hero">
                                    <span class="dashicons dashicons-yes" style="vertical-align: middle;"></span>
                                    <?php _e( 'Attiva Licenza', 'ipv-production-system-pro' ); ?>
                                </button>
                            </p>
                        </form>
                    </div>

                    <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin: 20px 0;">
                        <h3 style="margin-top: 0;">💡 <?php _e( 'Non hai ancora una licenza?', 'ipv-production-system-pro' ); ?></h3>
                        <p><?php _e( 'Attiva una licenza per sbloccare:', 'ipv-production-system-pro' ); ?></p>
                        <ul style="list-style: disc; margin-left: 20px;">
                            <li><?php _e( 'Trascrizioni automatiche video', 'ipv-production-system-pro' ); ?></li>
                            <li><?php _e( 'Descrizioni generate con AI', 'ipv-production-system-pro' ); ?></li>
                            <li><?php _e( 'Import massivo da YouTube', 'ipv-production-system-pro' ); ?></li>
                            <li><?php _e( 'Aggiornamenti automatici', 'ipv-production-system-pro' ); ?></li>
                            <li><?php _e( 'Supporto prioritario', 'ipv-production-system-pro' ); ?></li>
                        </ul>
                        <p class="description">
                            <?php _e( 'Contatta il tuo fornitore di licenze per ottenere una license key.', 'ipv-production-system-pro' ); ?>
                        </p>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#ipv-refresh-license').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).find('.dashicons').addClass('spin');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_refresh_license',
                        nonce: '<?php echo wp_create_nonce( 'ipv_refresh_license' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data || 'Errore aggiornamento');
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false).find('.dashicons').removeClass('spin');
                    }
                });
            });
        });
        </script>
        <style>
            @keyframes spin { 100% { transform: rotate(360deg); } }
            .spin { animation: spin 1s linear infinite; }
        </style>
        <?php
    }

    /**
     * Render server settings page
     */
    public static function render_server_page() {
        $server_url = get_option( 'ipv_api_server_url', '' );
        $default_server = IPV_Prod_API_Client::DEFAULT_SERVER;

        ?>
        <div class="wrap">
            <h1>🌐 <?php _e( 'Configurazione Server', 'ipv-production-system-pro' ); ?></h1>

            <?php settings_errors( 'ipv_license' ); ?>

            <div style="max-width: 700px;">
                <form method="post">
                    <?php wp_nonce_field( 'ipv_license_action', 'ipv_license_nonce' ); ?>
                    <input type="hidden" name="ipv_license_action" value="save_server">

                    <table class="form-table">
                        <tr>
                            <th><label for="server_url"><?php _e( 'Server URL', 'ipv-production-system-pro' ); ?></label></th>
                            <td>
                                <input type="url" 
                                       name="server_url" 
                                       id="server_url" 
                                       class="regular-text" 
                                       value="<?php echo esc_attr( $server_url ); ?>"
                                       placeholder="<?php echo esc_attr( $default_server ); ?>">
                                <p class="description">
                                    <?php printf( __( 'Lascia vuoto per usare il server di default: %s', 'ipv-production-system-pro' ), '<code>' . esc_html( $default_server ) . '</code>' ); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <p>
                        <button type="submit" class="button button-primary">
                            <?php _e( 'Salva', 'ipv-production-system-pro' ); ?>
                        </button>
                        <button type="button" class="button" id="ipv-test-connection" style="margin-left: 10px;">
                            <?php _e( 'Testa Connessione', 'ipv-production-system-pro' ); ?>
                        </button>
                    </p>
                </form>

                <div id="ipv-connection-result" style="margin-top: 20px; display: none;"></div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#ipv-test-connection').on('click', function() {
                var $btn = $(this);
                var $result = $('#ipv-connection-result');
                
                $btn.prop('disabled', true).text('<?php _e( 'Testando...', 'ipv-production-system-pro' ); ?>');
                $result.hide();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_test_server_connection',
                        nonce: '<?php echo wp_create_nonce( 'ipv_test_connection' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html(
                                '<div style="background: #d4edda; border: 1px solid #28a745; padding: 15px; border-radius: 4px;">' +
                                '<strong>✅ Connessione OK!</strong><br>' +
                                'Server: ' + response.data.server + '<br>' +
                                'Tempo risposta: ' + response.data.response_time +
                                '</div>'
                            ).show();
                        } else {
                            $result.html(
                                '<div style="background: #f8d7da; border: 1px solid #dc3545; padding: 15px; border-radius: 4px;">' +
                                '<strong>❌ Connessione Fallita</strong><br>' +
                                (response.data || 'Errore sconosciuto') +
                                '</div>'
                            ).show();
                        }
                    },
                    error: function() {
                        $result.html(
                            '<div style="background: #f8d7da; border: 1px solid #dc3545; padding: 15px; border-radius: 4px;">' +
                            '<strong>❌ Errore di rete</strong>' +
                            '</div>'
                        ).show();
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('<?php _e( 'Testa Connessione', 'ipv-production-system-pro' ); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Test server connection
     */
    public static function ajax_test_connection() {
        check_ajax_referer( 'ipv_test_connection', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $api = IPV_Prod_API_Client::instance();
        $result = $api->test_connection();

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( __( 'Impossibile connettersi al server', 'ipv-production-system-pro' ) );
        }
    }

    /**
     * AJAX: Refresh license info
     */
    public static function ajax_refresh_license() {
        check_ajax_referer( 'ipv_refresh_license', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $api = IPV_Prod_API_Client::instance();
        $result = $api->get_license_info();

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success();
    }
}

IPV_Prod_License_Manager_Client::init();
