<?php
/**
 * IPV Production System Pro - Golden Prompt Manager
 *
 * Manage AI golden prompt (upload, edit, download)
 *
 * @package IPV_Production_System_Pro
 * @version 8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Golden_Prompt_Manager {

    const OPTION_KEY = 'ipv_golden_prompt';
    const DEFAULT_PROMPT_FILE = 'default-golden-prompt.txt';

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_settings_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_action( 'wp_ajax_ipv_download_golden_prompt', [ __CLASS__, 'ajax_download_default_prompt' ] );
        add_action( 'wp_ajax_ipv_reset_golden_prompt', [ __CLASS__, 'ajax_reset_prompt' ] );
    }

    /**
     * Add settings page to admin menu
     */
    public static function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            'Golden Prompt AI',
            '✨ Golden Prompt',
            'manage_options',
            'ipv-golden-prompt',
            [ __CLASS__, 'render_settings_page' ]
        );
    }

    /**
     * Register settings
     */
    public static function register_settings() {
        register_setting( 'ipv_golden_prompt_settings', self::OPTION_KEY, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => self::get_default_prompt(),
        ] );
    }

    /**
     * Render settings page
     */
    public static function render_settings_page() {
        $current_prompt = get_option( self::OPTION_KEY, self::get_default_prompt() );
        $is_default = ( $current_prompt === self::get_default_prompt() );
        $word_count = str_word_count( $current_prompt );
        $char_count = strlen( $current_prompt );

        ?>
        <div class="wrap">
            <h1>✨ Golden Prompt AI Manager</h1>
            <p class="description">
                Gestisci il prompt principale usato per tutte le generazioni AI (riassunti, descrizioni, tag, ecc.)
            </p>

            <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; border-radius: 4px;">
                <h2>📊 Statistiche Prompt Corrente</h2>
                <p>
                    <strong>Parole:</strong> <?php echo number_format( $word_count ); ?> |
                    <strong>Caratteri:</strong> <?php echo number_format( $char_count ); ?> |
                    <strong>Stato:</strong>
                    <?php if ( $is_default ) : ?>
                        <span style="color: #2271b1;">🔵 Default</span>
                    <?php else : ?>
                        <span style="color: #00a32a;">✅ Personalizzato</span>
                    <?php endif; ?>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'ipv_golden_prompt_settings' );
                do_settings_sections( 'ipv_golden_prompt_settings' );
                ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ipv_golden_prompt">Golden Prompt</label>
                        </th>
                        <td>
                            <textarea
                                name="<?php echo esc_attr( self::OPTION_KEY ); ?>"
                                id="ipv_golden_prompt"
                                rows="20"
                                class="large-text code"
                                style="font-family: monospace; font-size: 13px;"
                            ><?php echo esc_textarea( $current_prompt ); ?></textarea>
                            <p class="description">
                                Questo prompt viene usato come base per tutte le richieste AI. Supporta variabili come {title}, {description}, {duration}, ecc.
                            </p>
                        </td>
                    </tr>
                </table>

                <div style="margin: 20px 0;">
                    <?php submit_button( '💾 Salva Golden Prompt', 'primary', 'submit', false ); ?>

                    <button type="button" class="button button-secondary" id="ipv-download-default" style="margin-left: 10px;">
                        ⬇️ Scarica Prompt Default
                    </button>

                    <?php if ( ! $is_default ) : ?>
                        <button type="button" class="button button-secondary" id="ipv-reset-prompt" style="margin-left: 10px;">
                            🔄 Ripristina Default
                        </button>
                    <?php endif; ?>
                </div>
            </form>

            <div style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px; margin: 20px 0;">
                <h3>💡 Suggerimenti</h3>
                <ul>
                    <li><strong>Variabili disponibili:</strong> {title}, {description}, {duration}, {channel}, {tags}</li>
                    <li><strong>Formato:</strong> Usa markdown per formattare il prompt</li>
                    <li><strong>Lunghezza:</strong> Prompt più lunghi = risposte più dettagliate (ma più costose)</li>
                    <li><strong>Testing:</strong> Testa sempre le modifiche su alcuni video prima di applicare globalmente</li>
                    <li><strong>Backup:</strong> Scarica sempre il prompt corrente prima di modifiche importanti</li>
                </ul>
            </div>

            <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; border-radius: 4px;">
                <h3>📝 Esempio di Personalizzazione</h3>
                <pre style="background: #f6f7f7; padding: 15px; overflow-x: auto;"><code>Sei un esperto di content marketing per video.

Analizza questo video:
- Titolo: {title}
- Durata: {duration}
- Descrizione: {description}

Genera:
1. Riassunto coinvolgente (100-150 parole)
2. 5-10 tag SEO-friendly
3. Call-to-action persuasiva

Tono: Professionale ma accessibile
Target: Professionisti del settore</code></pre>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Download default prompt
            $('#ipv-download-default').on('click', function() {
                window.location.href = ajaxurl + '?action=ipv_download_golden_prompt&nonce=<?php echo wp_create_nonce( 'ipv_golden_prompt' ); ?>';
            });

            // Reset to default
            $('#ipv-reset-prompt').on('click', function() {
                if (!confirm('Sei sicuro di voler ripristinare il prompt di default? Le modifiche correnti andranno perse.')) {
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true).text('🔄 Ripristinando...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_reset_golden_prompt',
                        nonce: '<?php echo wp_create_nonce( 'ipv_golden_prompt' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Prompt ripristinato al default!');
                            location.reload();
                        } else {
                            alert('❌ Errore: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('❌ Errore di connessione');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('🔄 Ripristina Default');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Get default golden prompt
     */
    public static function get_default_prompt() {
        $file = IPV_PROD_PLUGIN_DIR . self::DEFAULT_PROMPT_FILE;

        if ( file_exists( $file ) ) {
            return file_get_contents( $file );
        }

        // Fallback prompt
        return "Sei un esperto di content marketing e SEO per video.\n\nAnalizza questo video e genera contenuti ottimizzati.\n\nVideo: {title}\nDurata: {duration}\nDescrizione: {description}\n\nGenera:\n1. Riassunto SEO-friendly (150 parole max)\n2. 8-10 tag rilevanti\n3. Call-to-action coinvolgente";
    }

    /**
     * Get current golden prompt
     */
    public static function get_current_prompt() {
        return get_option( self::OPTION_KEY, self::get_default_prompt() );
    }

    /**
     * Replace variables in prompt
     */
    public static function process_prompt( $video_data ) {
        $prompt = self::get_current_prompt();

        $replacements = [
            '{title}' => $video_data['title'] ?? '',
            '{description}' => $video_data['description'] ?? '',
            '{duration}' => $video_data['duration'] ?? '',
            '{duration_formatted}' => $video_data['duration_formatted'] ?? '',
            '{channel}' => $video_data['channel'] ?? '',
            '{tags}' => isset( $video_data['tags'] ) ? implode( ', ', $video_data['tags'] ) : '',
            '{video_id}' => $video_data['video_id'] ?? '',
            '{source}' => $video_data['source'] ?? 'youtube',
        ];

        return str_replace( array_keys( $replacements ), array_values( $replacements ), $prompt );
    }

    /**
     * AJAX: Download default prompt
     */
    public static function ajax_download_default_prompt() {
        check_ajax_referer( 'ipv_golden_prompt', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $default_prompt = self::get_default_prompt();
        $filename = 'ipv-golden-prompt-default-' . date( 'Y-m-d' ) . '.txt';

        header( 'Content-Type: text/plain' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . strlen( $default_prompt ) );

        echo $default_prompt;
        exit;
    }

    /**
     * AJAX: Reset to default prompt
     */
    public static function ajax_reset_prompt() {
        check_ajax_referer( 'ipv_golden_prompt', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $default_prompt = self::get_default_prompt();
        update_option( self::OPTION_KEY, $default_prompt );

        IPV_Prod_Logger::log( 'Golden prompt reset to default', [ 'user_id' => get_current_user_id() ] );

        wp_send_json_success();
    }

    /**
     * Upload custom prompt from file
     */
    public static function upload_prompt_from_file( $file_path ) {
        if ( ! file_exists( $file_path ) ) {
            return new WP_Error( 'file_not_found', 'File not found' );
        }

        $content = file_get_contents( $file_path );

        if ( empty( $content ) ) {
            return new WP_Error( 'empty_file', 'File is empty' );
        }

        update_option( self::OPTION_KEY, $content );

        IPV_Prod_Logger::log( 'Golden prompt uploaded from file', [ 'file' => basename( $file_path ) ] );

        return true;
    }
}

IPV_Prod_Golden_Prompt_Manager::init();
