<?php
/**
 * IPV Golden Prompt Admin Page
 *
 * Pagina di amministrazione per configurare i Golden Prompts delle licenze
 *
 * @package IPV_Pro_Vendor
 * @since 1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Golden_Prompt_Admin {

    private static $instance = null;
    private $manager;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->manager = IPV_Vendor_Golden_Prompt_Manager::instance();
        
        add_action( 'admin_menu', [ $this, 'add_submenu_page' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_ipv_save_golden_prompt', [ $this, 'ajax_save_golden_prompt' ] );
        add_action( 'wp_ajax_ipv_get_golden_prompt', [ $this, 'ajax_get_golden_prompt' ] );
        add_action( 'wp_ajax_ipv_toggle_golden_prompt', [ $this, 'ajax_toggle_golden_prompt' ] );
        add_action( 'wp_ajax_ipv_save_universal_template', [ $this, 'ajax_save_universal_template' ] );
        add_action( 'wp_ajax_ipv_push_golden_prompt', [ $this, 'ajax_push_to_client' ] );
    }

    /**
     * Aggiungi sottomenu
     */
    public function add_submenu_page() {
        add_submenu_page(
            'ipv-vendor',
            'Golden Prompt Manager',
            '✨ Golden Prompt',
            'manage_options',
            'ipv-golden-prompt',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'ipv-golden-prompt' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'ipv-golden-prompt-admin',
            plugins_url( 'admin/assets/css/golden-prompt-admin.css', dirname( __FILE__ ) ),
            [],
            IPV_VENDOR_VERSION
        );

        wp_enqueue_script(
            'ipv-golden-prompt-admin',
            plugins_url( 'admin/assets/js/golden-prompt-admin.js', dirname( __FILE__ ) ),
            [ 'jquery' ],
            IPV_VENDOR_VERSION,
            true
        );

        wp_localize_script( 'ipv-golden-prompt-admin', 'ipvGoldenPrompt', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'ipv_golden_prompt_nonce' ),
            'configFields' => $this->manager->get_config_fields()
        ]);
    }

    /**
     * Render main page
     */
    public function render_page() {
        // Check for license_id parameter
        $license_id = isset( $_GET['license_id'] ) ? intval( $_GET['license_id'] ) : 0;
        $view = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'list';

        if ( $license_id && $view === 'configure' ) {
            $this->render_configure_page( $license_id );
        } elseif ( $view === 'template' ) {
            $this->render_template_page();
        } else {
            $this->render_list_page();
        }
    }

    /**
     * Render lista licenze con Golden Prompt
     */
    private function render_list_page() {
        global $wpdb;

        // Get all licenses with Golden Prompt status
        $licenses = $wpdb->get_results( "
            SELECT 
                l.*,
                gp.is_active as gp_active,
                gp.updated_at as gp_updated,
                a.site_url,
                a.site_name
            FROM {$wpdb->prefix}ipv_licenses l
            LEFT JOIN {$wpdb->prefix}ipv_golden_prompts gp ON l.id = gp.license_id
            LEFT JOIN {$wpdb->prefix}ipv_activations a ON l.id = a.license_id AND a.is_active = 1
            WHERE l.status = 'active'
            ORDER BY l.created_at DESC
        " );

        ?>
        <div class="wrap ipv-golden-prompt-wrap">
            <h1>
                <span class="dashicons dashicons-edit-page"></span>
                Golden Prompt Manager
            </h1>

            <!-- ℹ️ HELP BOX - Come Funziona per il Vendor -->
            <div style="background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%); border: 1px solid #0ea5e9; border-radius: 8px; padding: 16px; margin: 20px 0;">
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <span style="font-size: 24px;">ℹ️</span>
                    <div style="flex: 1;">
                        <h3 style="margin: 0 0 8px 0; color: #0c4a6e; font-size: 16px;">Come Funziona il Golden Prompt Manager</h3>
                        <div style="font-size: 13px; color: #075985; line-height: 1.6;">
                            <p style="margin: 0 0 8px 0;"><strong>🔄 Workflow:</strong> Cliente acquista Golden Prompt → Tu attivi lo switch → Cliente configura i dati dal suo pannello → Descrizioni AI personalizzate</p>
                            <details style="margin-top: 8px;">
                                <summary style="cursor: pointer; font-weight: bold; color: #0369a1;">📖 Guida dettagliata...</summary>
                                <div style="margin-top: 8px; padding: 12px; background: white; border-radius: 4px; border: 1px solid #7dd3fc;">
                                    <p style="margin: 0 0 6px 0;"><strong>🔘 Toggle Switch (ON/OFF):</strong> Attiva o disattiva il Golden Prompt per quella licenza. Quando attivi, il cliente può configurare i suoi dati.</p>
                                    <p style="margin: 0 0 6px 0;"><strong>⚙️ Configura:</strong> Visualizza/modifica la configurazione del cliente. Puoi anche inserire un prompt completamente personalizzato.</p>
                                    <p style="margin: 0 0 6px 0;"><strong>🚀 Push:</strong> Forza la sincronizzazione del Golden Prompt verso il sito del cliente (normalmente non necessario).</p>
                                    <p style="margin: 0 0 6px 0;"><strong>📝 Template Universale:</strong> Il template base che viene personalizzato per ogni cliente. Contiene i placeholder come {NOME_CANALE}.</p>
                                    <p style="margin: 0;"><strong>⚠️ "Cliente deve configurare":</strong> Lo switch è ON ma il cliente non ha ancora inviato i suoi dati.</p>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ipv-gp-header-actions">
                <a href="<?php echo admin_url( 'admin.php?page=ipv-golden-prompt&view=template' ); ?>" class="button button-secondary">
                    📝 Modifica Template Universale
                </a>
            </div>

            <div class="ipv-gp-stats">
                <div class="stat-box">
                    <span class="stat-number"><?php echo count( $licenses ); ?></span>
                    <span class="stat-label">Licenze Totali</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo count( array_filter( $licenses, fn($l) => $l->gp_active ) ); ?></span>
                    <span class="stat-label">Golden Prompt Attivi</span>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 180px;">License Key</th>
                        <th>Email / Sito</th>
                        <th style="width: 120px;">Piano</th>
                        <th style="width: 100px;">🌟 Stato GP</th>
                        <th style="width: 150px;">Ultimo Aggiornamento</th>
                        <th style="width: 200px;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $licenses as $license ) : ?>
                        <tr>
                            <td>
                                <code style="font-size: 11px;"><?php echo esc_html( $license->license_key ); ?></code>
                            </td>
                            <td>
                                <strong><?php echo esc_html( $license->email ); ?></strong>
                                <?php if ( $license->site_url ) : ?>
                                    <br><small style="color: #666;">
                                        <a href="<?php echo esc_url( $license->site_url ); ?>" target="_blank">
                                            <?php echo esc_html( $license->site_name ?: $license->site_url ); ?>
                                        </a>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="ipv-badge ipv-badge-<?php echo esc_attr( $license->variant_slug ); ?>">
                                    <?php echo esc_html( ucfirst( $license->variant_slug ) ); ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <?php 
                                // Check if has config
                                $gp_data = $wpdb->get_row( $wpdb->prepare(
                                    "SELECT id, is_active FROM {$wpdb->prefix}ipv_golden_prompts WHERE license_id = %d",
                                    $license->id
                                ));
                                $is_active = $gp_data ? (int)$gp_data->is_active : 0;
                                ?>
                                <!-- ✅ TOGGLE SWITCH (sempre visibile!) -->
                                <label class="ipv-toggle-switch" style="position: relative; display: inline-block; width: 50px; height: 24px; vertical-align: middle;">
                                    <input type="checkbox" 
                                           class="ipv-gp-toggle-input"
                                           data-license-id="<?php echo esc_attr( $license->id ); ?>"
                                           <?php checked( $is_active, 1 ); ?>
                                           style="opacity: 0; width: 0; height: 0;">
                                    <span class="ipv-gp-toggle-slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: <?php echo $is_active ? '#4caf50' : '#ccc'; ?>; transition: 0.3s; border-radius: 24px;">
                                        <span class="ipv-gp-toggle-thumb" style="position: absolute; content: ''; height: 18px; width: 18px; left: <?php echo $is_active ? '28px' : '3px'; ?>; bottom: 3px; background-color: white; transition: 0.3s; border-radius: 50%;"></span>
                                    </span>
                                </label>
                                <span class="ipv-gp-toggle-label" style="margin-left: 8px; font-size: 11px; font-weight: bold; color: <?php echo $is_active ? '#4caf50' : '#999'; ?>; vertical-align: middle;">
                                    <?php echo $is_active ? '🟢 ON' : '🔴 OFF'; ?>
                                </span>
                                <?php if ( ! $gp_data && $is_active ) : ?>
                                    <br><small style="color: #ff9800;">⚠️ Cliente deve configurare</small>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 12px; color: #666;">
                                <?php if ( $license->gp_updated ) : ?>
                                    <?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $license->gp_updated ) ) ); ?>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url( 'admin.php?page=ipv-golden-prompt&view=configure&license_id=' . $license->id ); ?>" 
                                   class="button button-primary button-small">
                                    ⚙️ Configura
                                </a>
                                
                                <?php if ( $license->gp_active ) : ?>
                                    <button type="button" 
                                            class="button button-small ipv-push-btn" 
                                            data-license-id="<?php echo esc_attr( $license->id ); ?>"
                                            data-site-url="<?php echo esc_attr( $license->site_url ); ?>">
                                        🚀 Push
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <style>
            .ipv-golden-prompt-wrap { max-width: 1400px; }
            .ipv-gp-header-actions { margin: 20px 0; }
            .ipv-gp-stats { display: flex; gap: 20px; margin-bottom: 20px; }
            .stat-box { background: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center; }
            .stat-number { display: block; font-size: 32px; font-weight: 700; color: #667eea; }
            .stat-label { color: #666; font-size: 13px; }
            .ipv-badge { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
            .ipv-badge-starter { background: #e3f2fd; color: #1565c0; }
            .ipv-badge-professional { background: #f3e5f5; color: #7b1fa2; }
            .ipv-badge-enterprise { background: #fff3e0; color: #e65100; }
            .ipv-badge-trial { background: #f5f5f5; color: #666; }
            .ipv-status { font-size: 12px; }
            .ipv-status-active { color: #2e7d32; }
            .ipv-status-inactive { color: #999; }
            .button-small { margin-right: 5px !important; }
        </style>
        <?php
    }

    /**
     * Render pagina configurazione singola licenza
     */
    private function render_configure_page( $license_id ) {
        global $wpdb;

        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT l.*, a.site_url, a.site_name 
             FROM {$wpdb->prefix}ipv_licenses l
             LEFT JOIN {$wpdb->prefix}ipv_activations a ON l.id = a.license_id AND a.is_active = 1
             WHERE l.id = %d",
            $license_id
        ));

        if ( ! $license ) {
            wp_die( 'Licenza non trovata' );
        }

        $config_data = $this->manager->get_license_config( $license_id );
        $config = $config_data ? $config_data['config'] : [];
        $golden_prompt = $config_data ? $config_data['golden_prompt'] : '';
        $fields = $this->manager->get_config_fields();

        ?>
        <div class="wrap ipv-golden-prompt-wrap">
            <h1>
                <a href="<?php echo admin_url( 'admin.php?page=ipv-golden-prompt' ); ?>" class="page-title-action" style="margin-right: 10px;">
                    ← Torna alla lista
                </a>
                Configura Golden Prompt
            </h1>

            <!-- ℹ️ HELP BOX - Configurazione -->
            <div style="background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%); border: 1px solid #0ea5e9; border-radius: 8px; padding: 16px; margin: 20px 0;">
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <span style="font-size: 24px;">ℹ️</span>
                    <div style="flex: 1;">
                        <h3 style="margin: 0 0 8px 0; color: #0c4a6e; font-size: 16px;">Come Configurare il Golden Prompt per questa Licenza</h3>
                        <div style="font-size: 13px; color: #075985; line-height: 1.6;">
                            <p style="margin: 0 0 8px 0;"><strong>Ci sono 2 modalità di configurazione:</strong></p>
                            <details style="margin-top: 8px;">
                                <summary style="cursor: pointer; font-weight: bold; color: #0369a1;">📖 Guida alle modalità...</summary>
                                <div style="margin-top: 8px; padding: 12px; background: white; border-radius: 4px; border: 1px solid #7dd3fc;">
                                    <p style="margin: 0 0 8px 0;"><strong>🔧 Auto-Configuratore:</strong> Il cliente compila i campi dal suo pannello e i dati vengono inviati qui. Il sistema genera automaticamente il Golden Prompt usando il Template Universale.</p>
                                    <p style="margin: 0 0 8px 0;"><strong>📝 Prompt Manuale:</strong> Puoi scrivere/incollare un Golden Prompt completamente personalizzato per questo cliente. Utile per casi speciali.</p>
                                    <p style="margin: 0 0 8px 0;"><strong>👁️ Anteprima:</strong> Visualizza il Golden Prompt attuale e permette il Push manuale al sito del cliente.</p>
                                    <div style="margin-top: 10px; padding: 8px; background: #fef3c7; border-radius: 4px; border: 1px solid #fbbf24;">
                                        <p style="margin: 0; font-size: 12px; color: #92400e;"><strong>💡 Nota:</strong> Normalmente il cliente configura tutto autonomamente. Usa questa pagina solo per verificare o correggere configurazioni.</p>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ipv-license-info">
                <div class="info-box">
                    <strong>License Key:</strong> <code><?php echo esc_html( $license->license_key ); ?></code>
                </div>
                <div class="info-box">
                    <strong>Email:</strong> <?php echo esc_html( $license->email ); ?>
                </div>
                <?php if ( $license->site_url ) : ?>
                    <div class="info-box">
                        <strong>Sito:</strong> 
                        <a href="<?php echo esc_url( $license->site_url ); ?>" target="_blank">
                            <?php echo esc_html( $license->site_name ?: $license->site_url ); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="ipv-gp-tabs">
                <button type="button" class="tab-btn active" data-tab="auto-config">
                    🔧 Auto-Configuratore
                </button>
                <button type="button" class="tab-btn" data-tab="manual">
                    📝 Prompt Manuale
                </button>
                <button type="button" class="tab-btn" data-tab="preview">
                    👁️ Anteprima
                </button>
            </div>

            <!-- Tab Auto-Configuratore -->
            <div class="tab-content active" id="tab-auto-config">
                <form id="ipv-gp-auto-form">
                    <input type="hidden" name="license_id" value="<?php echo esc_attr( $license_id ); ?>">
                    
                    <div class="ipv-gp-form-grid">
                        <?php foreach ( $fields as $key => $field ) : ?>
                            <div class="form-field <?php echo $field['required'] ? 'required' : ''; ?>">
                                <label for="<?php echo esc_attr( $key ); ?>">
                                    <?php echo esc_html( $field['label'] ); ?>
                                    <?php if ( $field['required'] ) : ?>
                                        <span class="required">*</span>
                                    <?php endif; ?>
                                </label>
                                
                                <?php if ( $field['type'] === 'textarea' ) : ?>
                                    <textarea 
                                        id="<?php echo esc_attr( $key ); ?>"
                                        name="<?php echo esc_attr( $key ); ?>"
                                        placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
                                        rows="3"
                                    ><?php echo esc_textarea( $config[ $key ] ?? '' ); ?></textarea>
                                <?php else : ?>
                                    <input 
                                        type="<?php echo esc_attr( $field['type'] ); ?>"
                                        id="<?php echo esc_attr( $key ); ?>"
                                        name="<?php echo esc_attr( $key ); ?>"
                                        value="<?php echo esc_attr( $config[ $key ] ?? '' ); ?>"
                                        placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
                                    >
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="button button-primary button-large">
                            💾 Genera e Salva Golden Prompt
                        </button>
                        <button type="button" class="button button-secondary" id="preview-auto-btn">
                            👁️ Anteprima
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tab Manuale -->
            <div class="tab-content" id="tab-manual">
                <form id="ipv-gp-manual-form">
                    <input type="hidden" name="license_id" value="<?php echo esc_attr( $license_id ); ?>">
                    
                    <div class="form-field">
                        <label for="custom_prompt">Golden Prompt Personalizzato</label>
                        <p class="description">Incolla qui un Golden Prompt completamente personalizzato. Questo sovrascriverà la configurazione auto-generata.</p>
                        <textarea 
                            id="custom_prompt" 
                            name="custom_prompt" 
                            rows="30" 
                            style="font-family: monospace; font-size: 12px;"
                        ><?php echo esc_textarea( $golden_prompt ); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="button button-primary button-large">
                            💾 Salva Golden Prompt
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tab Anteprima -->
            <div class="tab-content" id="tab-preview">
                <div class="preview-container">
                    <h3>Anteprima Golden Prompt</h3>
                    <pre id="prompt-preview"><?php echo esc_html( $golden_prompt ?: 'Nessun Golden Prompt configurato' ); ?></pre>
                </div>

                <?php if ( $golden_prompt && $license->site_url ) : ?>
                    <div class="form-actions">
                        <button type="button" class="button button-primary button-large ipv-push-to-client-btn" 
                                data-license-id="<?php echo esc_attr( $license_id ); ?>"
                                data-site-url="<?php echo esc_attr( $license->site_url ); ?>">
                            🚀 Push al Client (<?php echo esc_html( parse_url( $license->site_url, PHP_URL_HOST ) ); ?>)
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <style>
            .ipv-license-info { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
            .info-box { background: #fff; padding: 10px 15px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .ipv-gp-tabs { display: flex; gap: 5px; margin-bottom: 0; border-bottom: 2px solid #667eea; }
            .tab-btn { padding: 12px 20px; border: none; background: #f0f0f0; cursor: pointer; border-radius: 6px 6px 0 0; font-weight: 500; }
            .tab-btn.active { background: #667eea; color: #fff; }
            .tab-content { display: none; background: #fff; padding: 30px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .tab-content.active { display: block; }
            .ipv-gp-form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
            .form-field { margin-bottom: 15px; }
            .form-field label { display: block; font-weight: 600; margin-bottom: 5px; }
            .form-field .required { color: #dc3545; }
            .form-field input, .form-field textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
            .form-field textarea { resize: vertical; }
            .form-actions { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
            .preview-container { background: #f8f9fa; padding: 20px; border-radius: 6px; }
            .preview-container pre { white-space: pre-wrap; font-size: 12px; max-height: 500px; overflow-y: auto; }
            @media (max-width: 768px) { .ipv-gp-form-grid { grid-template-columns: 1fr; } }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Tabs
            $('.tab-btn').on('click', function() {
                var tab = $(this).data('tab');
                $('.tab-btn').removeClass('active');
                $(this).addClass('active');
                $('.tab-content').removeClass('active');
                $('#tab-' + tab).addClass('active');
            });

            // Auto form submit
            $('#ipv-gp-auto-form').on('submit', function(e) {
                e.preventDefault();
                var $btn = $(this).find('button[type="submit"]');
                $btn.prop('disabled', true).text('Salvando...');

                $.post(ipvGoldenPrompt.ajaxUrl, {
                    action: 'ipv_save_golden_prompt',
                    nonce: ipvGoldenPrompt.nonce,
                    mode: 'auto',
                    data: $(this).serialize()
                }, function(response) {
                    $btn.prop('disabled', false).text('💾 Genera e Salva Golden Prompt');
                    if (response.success) {
                        alert('✅ Golden Prompt salvato con successo!');
                        $('#prompt-preview').text(response.data.prompt);
                        $('#custom_prompt').val(response.data.prompt);
                    } else {
                        alert('❌ Errore: ' + response.data);
                    }
                });
            });

            // Manual form submit
            $('#ipv-gp-manual-form').on('submit', function(e) {
                e.preventDefault();
                var $btn = $(this).find('button[type="submit"]');
                $btn.prop('disabled', true).text('Salvando...');

                $.post(ipvGoldenPrompt.ajaxUrl, {
                    action: 'ipv_save_golden_prompt',
                    nonce: ipvGoldenPrompt.nonce,
                    mode: 'manual',
                    license_id: $('input[name="license_id"]').val(),
                    custom_prompt: $('#custom_prompt').val()
                }, function(response) {
                    $btn.prop('disabled', false).text('💾 Salva Golden Prompt');
                    if (response.success) {
                        alert('✅ Golden Prompt salvato con successo!');
                        $('#prompt-preview').text($('#custom_prompt').val());
                    } else {
                        alert('❌ Errore: ' + response.data);
                    }
                });
            });

            // Push to client
            $('.ipv-push-to-client-btn, .ipv-push-btn').on('click', function() {
                var licenseId = $(this).data('license-id');
                var siteUrl = $(this).data('site-url');
                var $btn = $(this);

                if (!siteUrl) {
                    alert('❌ Nessun sito attivato per questa licenza');
                    return;
                }

                if (!confirm('Vuoi inviare il Golden Prompt a ' + siteUrl + '?')) {
                    return;
                }

                $btn.prop('disabled', true).text('Invio...');

                $.post(ipvGoldenPrompt.ajaxUrl, {
                    action: 'ipv_push_golden_prompt',
                    nonce: ipvGoldenPrompt.nonce,
                    license_id: licenseId,
                    site_url: siteUrl
                }, function(response) {
                    $btn.prop('disabled', false).text('🚀 Push');
                    if (response.success) {
                        alert('✅ Golden Prompt inviato con successo!');
                    } else {
                        alert('❌ Errore: ' + response.data);
                    }
                });
            });

            // Toggle activation (switch checkbox)
            $('.ipv-gp-toggle-input').on('change', function() {
                var licenseId = $(this).data('license-id');
                var newActive = $(this).is(':checked') ? 1 : 0;
                var $checkbox = $(this);
                var $row = $checkbox.closest('tr');
                var $label = $row.find('.ipv-gp-toggle-label');
                var $slider = $row.find('.ipv-gp-toggle-slider');
                var $thumb = $row.find('.ipv-gp-toggle-thumb');

                // Disable during request
                $checkbox.prop('disabled', true);

                $.post(ipvGoldenPrompt.ajaxUrl, {
                    action: 'ipv_toggle_golden_prompt',
                    nonce: ipvGoldenPrompt.nonce,
                    license_id: licenseId,
                    active: newActive
                }, function(response) {
                    $checkbox.prop('disabled', false);
                    if (response.success) {
                        // Update UI without reload
                        if (newActive) {
                            $slider.css('background-color', '#4caf50');
                            $thumb.css('left', '28px');
                            $label.css('color', '#4caf50').text('🟢 ON');
                        } else {
                            $slider.css('background-color', '#ccc');
                            $thumb.css('left', '3px');
                            $label.css('color', '#999').text('🔴 OFF');
                        }
                    } else {
                        // Revert checkbox on error
                        $checkbox.prop('checked', !newActive);
                        alert('❌ Errore: ' + response.data);
                    }
                }).fail(function() {
                    $checkbox.prop('disabled', false);
                    $checkbox.prop('checked', !newActive);
                    alert('❌ Errore di connessione');
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render pagina template universale
     */
    private function render_template_page() {
        $template = $this->manager->get_universal_template();

        ?>
        <div class="wrap ipv-golden-prompt-wrap">
            <h1>
                <a href="<?php echo admin_url( 'admin.php?page=ipv-golden-prompt' ); ?>" class="page-title-action" style="margin-right: 10px;">
                    ← Torna alla lista
                </a>
                Template Universale Golden Prompt
            </h1>

            <!-- ℹ️ HELP BOX - Template Universale -->
            <div style="background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%); border: 1px solid #0ea5e9; border-radius: 8px; padding: 16px; margin: 20px 0;">
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <span style="font-size: 24px;">ℹ️</span>
                    <div style="flex: 1;">
                        <h3 style="margin: 0 0 8px 0; color: #0c4a6e; font-size: 16px;">Template Universale - Il Cuore del Sistema Golden Prompt</h3>
                        <div style="font-size: 13px; color: #075985; line-height: 1.6;">
                            <p style="margin: 0 0 8px 0;"><strong>🔄 Come funziona:</strong> Questo template viene usato come base per generare i Golden Prompt di tutti i clienti. I placeholder vengono sostituiti con i dati specifici di ogni cliente.</p>
                            <details style="margin-top: 8px;">
                                <summary style="cursor: pointer; font-weight: bold; color: #0369a1;">📖 Guida ai Placeholder e alla Struttura...</summary>
                                <div style="margin-top: 8px; padding: 12px; background: white; border-radius: 4px; border: 1px solid #7dd3fc;">
                                    <p style="margin: 0 0 8px 0;"><strong>📝 Placeholder Dati:</strong> <code>{NOME_CANALE}</code>, <code>{HANDLE_YOUTUBE}</code>, <code>{NICCHIA}</code>, ecc. vengono sostituiti con i dati del cliente.</p>
                                    <p style="margin: 0 0 8px 0;"><strong>🔗 Placeholder Link:</strong> <code>{LINK_TELEGRAM}</code>, <code>{LINK_FACEBOOK}</code>, ecc. per i social.</p>
                                    <p style="margin: 0 0 8px 0;"><strong>🌟 Placeholder Sponsor:</strong> <code>{SPONSOR_NOME}</code>, <code>{SPONSOR_DESCRIZIONE}</code>, <code>{SPONSOR_LINK}</code> per la sezione sponsor.</p>
                                    <div style="margin-top: 10px; padding: 8px; background: #dcfce7; border-radius: 4px; border: 1px solid #86efac;">
                                        <p style="margin: 0; font-size: 12px; color: #166534;"><strong>✅ Suggerimento:</strong> Il sistema gestisce automaticamente i 18 flag del cliente, rimuovendo le sezioni disabilitate durante la compilazione.</p>
                                    </div>
                                    <div style="margin-top: 8px; padding: 8px; background: #fef3c7; border-radius: 4px; border: 1px solid #fbbf24;">
                                        <p style="margin: 0; font-size: 12px; color: #92400e;"><strong>⚠️ Attenzione:</strong> Modificare questo template influenza TUTTI i clienti. Testa sempre le modifiche su una licenza di prova prima di salvare.</p>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            </div>

            <form id="ipv-universal-template-form">
                <div class="form-field">
                    <label for="universal_template">Template Universale</label>
                    <textarea 
                        id="universal_template" 
                        name="universal_template" 
                        rows="40" 
                        style="font-family: monospace; font-size: 12px; width: 100%;"
                    ><?php echo esc_textarea( $template ); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary button-large">
                        💾 Salva Template
                    </button>
                    <button type="button" class="button button-secondary" id="reset-template-btn">
                        🔄 Ripristina Default
                    </button>
                </div>
            </form>

            <div class="placeholder-reference">
                <h3>Placeholder Disponibili</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Placeholder</th>
                            <th>Descrizione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><code>{NOME_CANALE}</code></td><td>Nome del canale YouTube</td></tr>
                        <tr><td><code>{HANDLE_YOUTUBE}</code></td><td>Handle YouTube (senza @)</td></tr>
                        <tr><td><code>{NICCHIA}</code></td><td>Nicchia/settore del canale</td></tr>
                        <tr><td><code>{LINK_TELEGRAM}</code></td><td>Link Telegram</td></tr>
                        <tr><td><code>{LINK_FACEBOOK}</code></td><td>Link Facebook</td></tr>
                        <tr><td><code>{LINK_INSTAGRAM}</code></td><td>Link Instagram</td></tr>
                        <tr><td><code>{LINK_SITO}</code></td><td>URL sito web</td></tr>
                        <tr><td><code>{LINK_DONAZIONI}</code></td><td>Link donazioni</td></tr>
                        <tr><td><code>{SPONSOR_NOME}</code></td><td>Nome sponsor</td></tr>
                        <tr><td><code>{SPONSOR_DESCRIZIONE}</code></td><td>Descrizione sponsor</td></tr>
                        <tr><td><code>{SPONSOR_LINK}</code></td><td>Link sponsor</td></tr>
                        <tr><td><code>{EMAIL_BUSINESS}</code></td><td>Email business</td></tr>
                        <tr><td><code>{BIO_CANALE}</code></td><td>Bio/descrizione canale</td></tr>
                        <tr><td><code>{HASHTAG_CANALE}</code></td><td>Hashtag principale</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <style>
            .placeholder-reference { margin-top: 30px; }
            .placeholder-reference table { max-width: 600px; }
            .placeholder-reference code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('#ipv-universal-template-form').on('submit', function(e) {
                e.preventDefault();
                var $btn = $(this).find('button[type="submit"]');
                $btn.prop('disabled', true).text('Salvando...');

                $.post(ipvGoldenPrompt.ajaxUrl, {
                    action: 'ipv_save_universal_template',
                    nonce: ipvGoldenPrompt.nonce,
                    template: $('#universal_template').val()
                }, function(response) {
                    $btn.prop('disabled', false).text('💾 Salva Template');
                    if (response.success) {
                        alert('✅ Template salvato!');
                    } else {
                        alert('❌ Errore: ' + response.data);
                    }
                });
            });

            $('#reset-template-btn').on('click', function() {
                if (confirm('Vuoi ripristinare il template di default? Le modifiche andranno perse.')) {
                    // This would need to fetch default from server
                    location.reload();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Salva Golden Prompt
     */
    public function ajax_save_golden_prompt() {
        check_ajax_referer( 'ipv_golden_prompt_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permessi insufficienti' );
        }

        $mode = sanitize_text_field( $_POST['mode'] ?? 'auto' );

        if ( $mode === 'manual' ) {
            $license_id = intval( $_POST['license_id'] );
            $custom_prompt = wp_kses_post( $_POST['custom_prompt'] );

            $result = $this->manager->save_custom_prompt( $license_id, $custom_prompt );

            if ( $result ) {
                wp_send_json_success( [ 'prompt' => $custom_prompt ] );
            } else {
                wp_send_json_error( 'Errore nel salvataggio' );
            }
        } else {
            // Parse form data
            parse_str( $_POST['data'], $form_data );
            $license_id = intval( $form_data['license_id'] );
            unset( $form_data['license_id'] );

            $result = $this->manager->save_license_config( $license_id, $form_data );

            if ( $result ) {
                $config = $this->manager->get_license_config( $license_id );
                wp_send_json_success( [ 'prompt' => $config['golden_prompt'] ] );
            } else {
                wp_send_json_error( 'Errore nel salvataggio' );
            }
        }
    }

    /**
     * AJAX: Toggle activation
     */
    public function ajax_toggle_golden_prompt() {
        check_ajax_referer( 'ipv_golden_prompt_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permessi insufficienti' );
        }

        $license_id = intval( $_POST['license_id'] );
        $active = intval( $_POST['active'] );

        $result = $this->manager->toggle_license_activation( $license_id, $active );

        if ( $result !== false ) {
            wp_send_json_success();
        } else {
            wp_send_json_error( 'Errore nell\'aggiornamento' );
        }
    }

    /**
     * AJAX: Save universal template
     */
    public function ajax_save_universal_template() {
        check_ajax_referer( 'ipv_golden_prompt_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permessi insufficienti' );
        }

        $template = wp_kses_post( $_POST['template'] );
        $this->manager->save_universal_template( $template );

        wp_send_json_success();
    }

    /**
     * AJAX: Push Golden Prompt to client
     */
    public function ajax_push_to_client() {
        check_ajax_referer( 'ipv_golden_prompt_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permessi insufficienti' );
        }

        $license_id = intval( $_POST['license_id'] );
        $site_url = esc_url_raw( $_POST['site_url'] );

        // Get license key
        global $wpdb;
        $license = $wpdb->get_row( $wpdb->prepare(
            "SELECT license_key FROM {$wpdb->prefix}ipv_licenses WHERE id = %d",
            $license_id
        ));

        if ( ! $license ) {
            wp_send_json_error( 'Licenza non trovata' );
        }

        // Get Golden Prompt
        $config = $this->manager->get_license_config( $license_id );

        if ( ! $config || empty( $config['golden_prompt'] ) ) {
            wp_send_json_error( 'Golden Prompt non configurato' );
        }

        // Send to client via REST API
        $response = wp_remote_post( trailingslashit( $site_url ) . 'wp-json/ipv-pro/v1/golden-prompt/sync', [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-License-Key' => $license->license_key
            ],
            'body' => wp_json_encode( [
                'golden_prompt' => $config['golden_prompt'],
                'updated_at' => $config['updated_at']
            ])
        ]);

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( 'Errore connessione: ' . $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 ) {
            wp_send_json_error( 'Errore dal client: ' . ( $body['message'] ?? 'Errore sconosciuto' ) );
        }

        // Log success
        if ( class_exists( 'IPV_Vendor_Audit_Log' ) ) {
            IPV_Vendor_Audit_Log::log( 'golden_prompt_pushed', [
                'license_id' => $license_id,
                'site_url' => $site_url
            ]);
        }

        wp_send_json_success( [ 'message' => 'Golden Prompt sincronizzato!' ] );
    }
}
