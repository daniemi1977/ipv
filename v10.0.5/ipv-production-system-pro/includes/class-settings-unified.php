<?php
/**
 * IPV Production System Pro - Settings Unificato
 * Unifica: Server, Golden Prompt, Lingua, Generale
 * @version 10.0.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Prod_Settings_Unified {
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ], 20 );
        add_action( 'admin_post_ipv_settings_save', [ __CLASS__, 'handle_save' ] );
    }

    public static function add_menu() {
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            __( 'Impostazioni', 'ipv-production-system-pro' ),
            '⚙️ ' . __( 'Impostazioni', 'ipv-production-system-pro' ),
            'manage_options',
            'ipv-settings',
            [ __CLASS__, 'render' ]
        );
    }

    public static function render() {
        $tab = $_GET['settab'] ?? 'server';
        ?>
        <div class="wrap">
            <h1>⚙️ <?php _e( 'Impostazioni', 'ipv-production-system-pro' ); ?></h1>
            <?php settings_errors( 'ipv_settings' ); ?>
            
            <nav class="nav-tab-wrapper">
                <a href="?post_type=ipv_video&page=ipv-settings&settab=server" class="nav-tab <?php echo $tab === 'server' ? 'nav-tab-active' : ''; ?>">🌐 Server</a>
                <a href="?post_type=ipv_video&page=ipv-settings&settab=golden" class="nav-tab <?php echo $tab === 'golden' ? 'nav-tab-active' : ''; ?>">✨ Golden Prompt</a>
                <a href="?post_type=ipv_video&page=ipv-settings&settab=language" class="nav-tab <?php echo $tab === 'language' ? 'nav-tab-active' : ''; ?>">🌍 Lingua</a>
                <a href="?post_type=ipv_video&page=ipv-settings&settab=general" class="nav-tab <?php echo $tab === 'general' ? 'nav-tab-active' : ''; ?>">⚙️ Generale</a>
            </nav>

            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="background:#fff;padding:20px;border:1px solid #ccc;border-top:0;">
                <?php wp_nonce_field( 'ipv_settings_save', 'ipv_nonce' ); ?>
                <input type="hidden" name="action" value="ipv_settings_save">
                <input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
                
                <?php
                switch ( $tab ) {
                    case 'server': self::render_server_tab(); break;
                    case 'golden': self::render_golden_tab(); break;
                    case 'language': self::render_language_tab(); break;
                    case 'general': self::render_general_tab(); break;
                }
                ?>
                
                <p><button type="submit" class="button button-primary">💾 Salva Impostazioni</button></p>
            </form>
        </div>
        <?php
    }

    private static function render_server_tab() {
        $server_url = get_option( 'ipv_api_server_url', '' );
        ?>
        <h2>🌐 Configurazione Server</h2>
        <table class="form-table">
            <tr>
                <th><label for="server_url">Server URL</label></th>
                <td>
                    <input type="url" name="server_url" id="server_url" value="<?php echo esc_attr( $server_url ); ?>" class="regular-text" placeholder="https://aiedintorni.it">
                    <p class="description">URL del server vendor che gestisce le licenze e le API.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    private static function render_golden_tab() {
        $golden_prompt = get_option( 'ipv_golden_prompt', '' );
        ?>
        <h2>✨ Golden Prompt AI</h2>
        <table class="form-table">
            <tr>
                <th><label for="golden_prompt">Prompt AI Personalizzato</label></th>
                <td>
                    <textarea name="golden_prompt" id="golden_prompt" rows="15" class="large-text"><?php echo esc_textarea( $golden_prompt ); ?></textarea>
                    <p class="description">Prompt usato per generare le descrizioni AI dei video. Lascia vuoto per usare il prompt predefinito.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    private static function render_language_tab() {
        $plugin_language = get_option( 'ipv_plugin_language', 'auto' );
        $transcript_language = get_option( 'ipv_transcript_language', 'it' );
        ?>
        <h2>🌍 Impostazioni Lingua</h2>
        <table class="form-table">
            <tr>
                <th><label for="plugin_language">Lingua Plugin</label></th>
                <td>
                    <select name="plugin_language" id="plugin_language">
                        <option value="auto" <?php selected( $plugin_language, 'auto' ); ?>>Auto (WordPress)</option>
                        <option value="it_IT" <?php selected( $plugin_language, 'it_IT' ); ?>>Italiano</option>
                        <option value="en_US" <?php selected( $plugin_language, 'en_US' ); ?>>English</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="transcript_language">Lingua Trascrizioni</label></th>
                <td>
                    <select name="transcript_language" id="transcript_language">
                        <option value="it" <?php selected( $transcript_language, 'it' ); ?>>Italiano</option>
                        <option value="en" <?php selected( $transcript_language, 'en' ); ?>>English</option>
                        <option value="es" <?php selected( $transcript_language, 'es' ); ?>>Español</option>
                        <option value="fr" <?php selected( $transcript_language, 'fr' ); ?>>Français</option>
                        <option value="de" <?php selected( $transcript_language, 'de' ); ?>>Deutsch</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    private static function render_general_tab() {
        $auto_publish = get_option( 'ipv_auto_publish_imports', false );
        ?>
        <h2>⚙️ Impostazioni Generali</h2>
        <table class="form-table">
            <tr>
                <th>Pubblicazione Automatica</th>
                <td>
                    <label>
                        <input type="checkbox" name="auto_publish_imports" value="1" <?php checked( $auto_publish ); ?>>
                        Pubblica automaticamente i video importati (invece di salvarli come bozze)
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function handle_save() {
        check_admin_referer( 'ipv_settings_save', 'ipv_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

        $tab = sanitize_text_field( $_POST['tab'] ?? 'server' );

        switch ( $tab ) {
            case 'server':
                update_option( 'ipv_api_server_url', esc_url_raw( $_POST['server_url'] ?? '' ) );
                break;
            case 'golden':
                update_option( 'ipv_golden_prompt', sanitize_textarea_field( $_POST['golden_prompt'] ?? '' ) );
                break;
            case 'language':
                update_option( 'ipv_plugin_language', sanitize_text_field( $_POST['plugin_language'] ?? 'auto' ) );
                update_option( 'ipv_transcript_language', sanitize_text_field( $_POST['transcript_language'] ?? 'it' ) );
                break;
            case 'general':
                update_option( 'ipv_auto_publish_imports', ! empty( $_POST['auto_publish_imports'] ) );
                break;
        }

        add_settings_error( 'ipv_settings', 'success', '✅ Impostazioni salvate!', 'success' );
        set_transient( 'settings_errors', get_settings_errors(), 30 );
        wp_safe_redirect( admin_url( 'edit.php?post_type=ipv_video&page=ipv-settings&settab=' . $tab ) );
        exit;
    }
}
