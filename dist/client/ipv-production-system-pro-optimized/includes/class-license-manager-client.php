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
        // v10.0.24 - Menu registration moved to Menu Manager
        // add_action( 'admin_menu', [ __CLASS__, 'add_license_page' ] );
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

        // v10.3.1-FIXED2: Use correct class name
        if ( class_exists('IPV_Prod_API_Client_Optimized') && IPV_Prod_API_Client_Optimized::is_license_active() ) {
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

        $api = IPV_Prod_API_Client_Optimized::instance();
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
        $is_active = IPV_Prod_API_Client_Optimized::is_license_active();
        $activated_at = get_option( 'ipv_license_activated_at', 0 );

        ?>
        <div class="ipv-modern-page bg-gray-50 min-h-screen -ml-5 -mt-2 p-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">🔑 <?php _e( 'Gestione Licenza', 'ipv-production-system-pro' ); ?></h1>
                <p class="text-gray-600"><?php _e( 'Gestisci e monitora la tua licenza IPV Pro', 'ipv-production-system-pro' ); ?></p>
            </div>

            <?php settings_errors( 'ipv_license' ); ?>

            <div class="max-w-4xl">

                <?php if ( $is_active ) : ?>
                    <!-- Licenza Attiva -->
                    <div class="ipv-card mb-8 bg-gradient-to-br from-green-50 to-emerald-50 border-green-200">
                        <div class="ipv-card-header bg-green-100 border-b border-green-200">
                            <h2 class="ipv-card-title text-green-800">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <?php _e( 'Licenza Attiva', 'ipv-production-system-pro' ); ?>
                            </h2>
                        </div>

                        <div class="p-6 space-y-6">
                            <!-- License Info Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="ipv-label"><?php _e( 'License Key', 'ipv-production-system-pro' ); ?></label>
                                    <code class="ipv-code block">
                                        <?php echo esc_html( substr( $license_key, 0, 4 ) . '-****-****-' . substr( $license_key, -4 ) ); ?>
                                    </code>
                                </div>

                                <?php if ( ! empty( $license_info['variant'] ) ) : ?>
                                <div>
                                    <label class="ipv-label"><?php _e( 'Piano', 'ipv-production-system-pro' ); ?></label>
                                    <p id="ipv-license-variant" class="text-lg font-bold text-gray-900"><?php echo esc_html( ucfirst( $license_info['variant'] ) ); ?></p>
                                </div>
                                <?php endif; ?>

                                <?php if ( ! empty( $license_info['email'] ) ) : ?>
                                <div>
                                    <label class="ipv-label"><?php _e( 'Email', 'ipv-production-system-pro' ); ?></label>
                                    <p id="ipv-license-email" class="text-gray-900"><?php echo esc_html( $license_info['email'] ); ?></p>
                                </div>
                                <?php endif; ?>

                                <?php if ( ! empty( $license_info['expires_at'] ) ) : ?>
                                <div>
                                    <label class="ipv-label"><?php _e( 'Scadenza', 'ipv-production-system-pro' ); ?></label>
                                    <p id="ipv-license-expires" class="text-gray-900"><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $license_info['expires_at'] ) ) ); ?></p>
                                </div>
                                <?php endif; ?>

                                <?php if ( $activated_at ) : ?>
                                <div>
                                    <label class="ipv-label"><?php _e( 'Attivata il', 'ipv-production-system-pro' ); ?></label>
                                    <p class="text-gray-900"><?php echo esc_html( date_i18n( 'd/m/Y H:i', $activated_at ) ); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Crediti -->
                            <?php if ( ! empty( $license_info['credits'] ) ) :
                                $credits = $license_info['credits'];
                                $percentage = $credits['percentage'] ?? 0;
                                $bar_color_class = $percentage > 50 ? 'bg-green-500' : ( $percentage > 20 ? 'bg-yellow-500' : 'bg-red-500' );
                            ?>
                            <div class="ipv-card">
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                    <?php _e( 'Crediti Mensili', 'ipv-production-system-pro' ); ?>
                                </h4>
                                <div class="flex items-center gap-4">
                                    <div class="flex-1">
                                        <div class="bg-gray-200 rounded-full h-4 overflow-hidden">
                                            <div id="ipv-credits-bar" class="<?php echo $bar_color_class; ?> h-full transition-all" style="width: <?php echo $percentage; ?>%;"></div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-2xl font-bold text-gray-900" id="ipv-credits-remaining"><?php echo esc_html( $credits['credits_remaining'] ?? 0 ); ?></span>
                                        <span class="text-gray-500">/</span>
                                        <span class="text-lg text-gray-600" id="ipv-credits-total"><?php echo esc_html( $credits['credits_total'] ?? 0 ); ?></span>
                                    </div>
                                </div>
                                <?php if ( ! empty( $credits['reset_date_formatted'] ) ) : ?>
                                <p class="text-sm text-gray-600 mt-2" id="ipv-credits-reset-container">
                                    <?php printf( __( 'Reset: %s', 'ipv-production-system-pro' ), '<span id="ipv-credits-reset">' . esc_html( $credits['reset_date_formatted'] ) . '</span>' ); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Actions -->
                            <div class="flex flex-wrap gap-3 pt-4 border-t border-green-200">
                                <button type="button" class="ipv-btn ipv-btn-secondary" id="ipv-refresh-license">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    <?php _e( 'Aggiorna Info', 'ipv-production-system-pro' ); ?>
                                </button>

                                <form method="post" class="inline-block">
                                    <?php wp_nonce_field( 'ipv_license_action', 'ipv_license_nonce' ); ?>
                                    <input type="hidden" name="ipv_license_action" value="deactivate">
                                    <button type="submit" class="ipv-btn ipv-btn-danger" onclick="return confirm('<?php _e( 'Sei sicuro di voler deattivare la licenza?', 'ipv-production-system-pro' ); ?>');">
                                        <?php _e( 'Deattiva Licenza', 'ipv-production-system-pro' ); ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                <?php else : ?>
                    <!-- Licenza Non Attiva -->
                    <div class="ipv-card mb-8 bg-gradient-to-br from-yellow-50 to-amber-50 border-yellow-200">
                        <div class="ipv-card-header bg-yellow-100 border-b border-yellow-200">
                            <h2 class="ipv-card-title text-yellow-800">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <?php _e( 'Licenza Non Attiva', 'ipv-production-system-pro' ); ?>
                            </h2>
                        </div>

                        <div class="p-6">
                            <p class="text-gray-700 mb-6"><?php _e( 'Inserisci la tua license key per attivare tutte le funzionalità.', 'ipv-production-system-pro' ); ?></p>

                            <form method="post">
                                <?php wp_nonce_field( 'ipv_license_action', 'ipv_license_nonce' ); ?>
                                <input type="hidden" name="ipv_license_action" value="activate">

                                <div class="space-y-4">
                                    <div>
                                        <label for="license_key" class="ipv-label"><?php _e( 'License Key', 'ipv-production-system-pro' ); ?></label>
                                        <input type="text"
                                               name="license_key"
                                               id="license_key"
                                               class="ipv-input font-mono uppercase"
                                               placeholder="XXXX-XXXX-XXXX-XXXX"
                                               pattern="[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}"
                                               required>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <?php _e( 'La license key ti è stata inviata via email dopo l\'acquisto.', 'ipv-production-system-pro' ); ?>
                                        </p>
                                    </div>

                                    <button type="submit" class="ipv-btn ipv-btn-primary text-lg px-8 py-3">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <?php _e( 'Attiva Licenza', 'ipv-production-system-pro' ); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Benefici -->
                    <div class="ipv-card bg-blue-50 border-blue-200">
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <?php _e( 'Non hai ancora una licenza?', 'ipv-production-system-pro' ); ?>
                            </h3>
                            <p class="text-gray-700 mb-4"><?php _e( 'Attiva una licenza per sbloccare:', 'ipv-production-system-pro' ); ?></p>
                            <ul class="space-y-2">
                                <li class="flex items-center gap-2 text-gray-700">
                                    <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <?php _e( 'Trascrizioni automatiche video', 'ipv-production-system-pro' ); ?>
                                </li>
                                <li class="flex items-center gap-2 text-gray-700">
                                    <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <?php _e( 'Descrizioni generate con AI', 'ipv-production-system-pro' ); ?>
                                </li>
                                <li class="flex items-center gap-2 text-gray-700">
                                    <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <?php _e( 'Import massivo da YouTube', 'ipv-production-system-pro' ); ?>
                                </li>
                                <li class="flex items-center gap-2 text-gray-700">
                                    <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <?php _e( 'Aggiornamenti automatici', 'ipv-production-system-pro' ); ?>
                                </li>
                                <li class="flex items-center gap-2 text-gray-700">
                                    <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <?php _e( 'Supporto prioritario', 'ipv-production-system-pro' ); ?>
                                </li>
                            </ul>
                            <p class="text-sm text-gray-600 mt-4">
                                <?php _e( 'Contatta il tuo fornitore di licenze per ottenere una license key.', 'ipv-production-system-pro' ); ?>
                            </p>
                        </div>
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
                        if (response.success && response.data) {
                            // v10.0.11 - Aggiorna i campi via AJAX senza reload
                            var data = response.data;

                            // Aggiorna Piano
                            if (data.variant && $('#ipv-license-variant').length) {
                                $('#ipv-license-variant').text(data.variant.charAt(0).toUpperCase() + data.variant.slice(1));
                            }

                            // Aggiorna Email
                            if (data.email && $('#ipv-license-email').length) {
                                $('#ipv-license-email').text(data.email);
                            }

                            // Aggiorna Scadenza
                            if (data.expires_at && $('#ipv-license-expires').length) {
                                var expiresDate = new Date(data.expires_at);
                                var formatted = expiresDate.toLocaleDateString('it-IT');
                                $('#ipv-license-expires').text(formatted);
                            }

                            // Aggiorna Crediti
                            if (data.credits) {
                                var credits = data.credits;

                                if (credits.credits_remaining !== undefined && $('#ipv-credits-remaining').length) {
                                    $('#ipv-credits-remaining').text(credits.credits_remaining);
                                }

                                if (credits.credits_total !== undefined && $('#ipv-credits-total').length) {
                                    $('#ipv-credits-total').text(credits.credits_total);
                                }

                                if (credits.percentage !== undefined && $('#ipv-credits-bar').length) {
                                    var percentage = credits.percentage;
                                    var barColor = percentage > 50 ? '#28a745' : (percentage > 20 ? '#ffc107' : '#dc3545');
                                    $('#ipv-credits-bar').css({
                                        'width': percentage + '%',
                                        'background': barColor
                                    });
                                }

                                if (credits.reset_date_formatted && $('#ipv-credits-reset').length) {
                                    $('#ipv-credits-reset').text(credits.reset_date_formatted);
                                }
                            }

                            // Mostra feedback visivo
                            $btn.after('<span class="ipv-success-msg" style="color: #28a745; margin-left: 10px;"><span class="dashicons dashicons-yes"></span> Aggiornato!</span>');
                            setTimeout(function() {
                                $('.ipv-success-msg').fadeOut(function() { $(this).remove(); });
                            }, 3000);

                        } else {
                            alert(response.data || 'Errore aggiornamento');
                        }
                    },
                    error: function() {
                        alert('Errore di connessione. Riprova.');
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
        $default_server = IPV_Prod_API_Client_Optimized::DEFAULT_SERVER;

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

        $api = IPV_Prod_API_Client_Optimized::instance();
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

        $api = IPV_Prod_API_Client_Optimized::instance();
        $result = $api->get_license_info();

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        // v10.0.11 - Ritorna i dati aggiornati per update AJAX (no reload)
        wp_send_json_success( $result );
    }
}

IPV_Prod_License_Manager_Client::init();
