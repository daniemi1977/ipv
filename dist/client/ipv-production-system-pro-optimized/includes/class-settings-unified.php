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
        <div class="ipv-modern-page bg-gray-50 min-h-screen -ml-5 -mt-2 p-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">⚙️ <?php _e( 'Impostazioni', 'ipv-production-system-pro' ); ?></h1>
                <p class="text-gray-600"><?php _e( 'Configura le impostazioni del sistema di produzione video', 'ipv-production-system-pro' ); ?></p>
            </div>

            <?php settings_errors( 'ipv_settings' ); ?>

            <!-- Modern Tabs -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="flex space-x-8" aria-label="Tabs">
                    <a href="?post_type=ipv_video&page=ipv-settings&settab=server"
                       class="<?php echo $tab === 'server' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                        </svg>
                        Server
                    </a>
                    <a href="?post_type=ipv_video&page=ipv-settings&settab=golden"
                       class="<?php echo $tab === 'golden' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        Golden Prompt
                    </a>
                    <a href="?post_type=ipv_video&page=ipv-settings&settab=language"
                       class="<?php echo $tab === 'language' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                        </svg>
                        Lingua
                    </a>
                    <a href="?post_type=ipv_video&page=ipv-settings&settab=general"
                       class="<?php echo $tab === 'general' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Generale
                    </a>
                </nav>
            </div>

            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" class="space-y-6">
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

                <!-- Save Button -->
                <div class="ipv-card bg-white sticky bottom-4 shadow-lg">
                    <div class="p-4 flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <?php _e( 'Salva le modifiche per applicare le impostazioni', 'ipv-production-system-pro' ); ?>
                        </div>
                        <button type="submit" class="ipv-btn-primary flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                            </svg>
                            <?php _e( 'Salva Impostazioni', 'ipv-production-system-pro' ); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    private static function render_server_tab() {
        $server_url = get_option( 'ipv_api_server_url', '' );
        ?>
        <div class="ipv-card bg-gradient-to-br from-blue-50 to-indigo-50 border-blue-200">
            <div class="ipv-card-header bg-blue-100 border-b border-blue-200">
                <h2 class="ipv-card-title text-blue-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                    </svg>
                    <?php _e( 'Configurazione Server', 'ipv-production-system-pro' ); ?>
                </h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <label for="server_url" class="ipv-label">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                            </svg>
                            <?php _e( 'Server URL', 'ipv-production-system-pro' ); ?>
                        </label>
                        <input type="url"
                               name="server_url"
                               id="server_url"
                               value="<?php echo esc_attr( $server_url ); ?>"
                               class="ipv-input font-mono text-sm"
                               placeholder="https://your-server.com">
                        <p class="mt-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <?php _e( 'URL del server vendor che gestisce le licenze e le API', 'ipv-production-system-pro' ); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private static function render_golden_tab() {
        $golden_prompt = get_option( 'ipv_golden_prompt', '' );
        $word_count = str_word_count( $golden_prompt );
        $char_count = strlen( $golden_prompt );
        ?>
        <div class="ipv-card bg-gradient-to-br from-yellow-50 to-amber-50 border-yellow-200">
            <div class="ipv-card-header bg-yellow-100 border-b border-yellow-200">
                <h2 class="ipv-card-title text-yellow-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                    <?php _e( 'Golden Prompt AI', 'ipv-production-system-pro' ); ?>
                </h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="text-sm text-blue-800">
                                <p class="font-semibold"><?php _e( 'Il tuo Prompt AI Personalizzato', 'ipv-production-system-pro' ); ?></p>
                                <p class="mt-1"><?php _e( 'Questo prompt sarà usato dall\'AI per generare descrizioni, capitoli, hashtag e metadati per i tuoi video', 'ipv-production-system-pro' ); ?></p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="golden_prompt" class="ipv-label">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            <?php _e( 'Prompt AI Personalizzato', 'ipv-production-system-pro' ); ?>
                        </label>
                        <textarea name="golden_prompt"
                                  id="golden_prompt"
                                  rows="15"
                                  class="ipv-input font-mono text-sm resize-y"
                                  placeholder="<?php esc_attr_e( 'Inserisci il tuo prompt AI personalizzato qui...&#10;&#10;Esempio:&#10;Sei un esperto copywriter SEO per YouTube.&#10;Analizza la trascrizione del video e genera:&#10;1. Una descrizione accattivante (150-200 parole)&#10;2. Capitoli con timestamp&#10;3. Hashtag rilevanti&#10;&#10;Tono: Professionale ma accessibile.&#10;Lingua: Italiano.', 'ipv-production-system-pro' ); ?>"><?php echo esc_textarea( $golden_prompt ); ?></textarea>

                        <?php if ( ! empty( $golden_prompt ) ) : ?>
                            <div class="mt-3 flex items-center gap-4 text-sm">
                                <div class="flex items-center gap-1 text-green-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span><?php printf( __( '%d parole', 'ipv-production-system-pro' ), $word_count ); ?></span>
                                </div>
                                <div class="flex items-center gap-1 text-blue-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                    </svg>
                                    <span><?php printf( __( '%d caratteri', 'ipv-production-system-pro' ), $char_count ); ?></span>
                                </div>
                            </div>
                        <?php else : ?>
                            <p class="mt-2 text-sm text-amber-600">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <?php _e( 'Nessun prompt configurato. L\'AI userà un prompt predefinito di base', 'ipv-production-system-pro' ); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private static function render_language_tab() {
        $plugin_language = get_option( 'ipv_plugin_language', 'auto' );
        $transcript_language = get_option( 'ipv_transcript_language', 'it' );
        ?>
        <div class="ipv-card bg-gradient-to-br from-green-50 to-emerald-50 border-green-200">
            <div class="ipv-card-header bg-green-100 border-b border-green-200">
                <h2 class="ipv-card-title text-green-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                    </svg>
                    <?php _e( 'Impostazioni Lingua', 'ipv-production-system-pro' ); ?>
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="plugin_language" class="ipv-label">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            <?php _e( 'Lingua Plugin', 'ipv-production-system-pro' ); ?>
                        </label>
                        <select name="plugin_language" id="plugin_language" class="ipv-input">
                            <option value="auto" <?php selected( $plugin_language, 'auto' ); ?>>🌐 Auto (WordPress)</option>
                            <option value="it_IT" <?php selected( $plugin_language, 'it_IT' ); ?>>🇮🇹 Italiano</option>
                            <option value="en_US" <?php selected( $plugin_language, 'en_US' ); ?>>🇬🇧 English</option>
                        </select>
                        <p class="mt-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <?php _e( 'Lingua interfaccia amministrazione', 'ipv-production-system-pro' ); ?>
                        </p>
                    </div>

                    <div>
                        <label for="transcript_language" class="ipv-label">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                            </svg>
                            <?php _e( 'Lingua Trascrizioni', 'ipv-production-system-pro' ); ?>
                        </label>
                        <select name="transcript_language" id="transcript_language" class="ipv-input">
                            <option value="it" <?php selected( $transcript_language, 'it' ); ?>>🇮🇹 Italiano</option>
                            <option value="en" <?php selected( $transcript_language, 'en' ); ?>>🇬🇧 English</option>
                            <option value="es" <?php selected( $transcript_language, 'es' ); ?>>🇪🇸 Español</option>
                            <option value="fr" <?php selected( $transcript_language, 'fr' ); ?>>🇫🇷 Français</option>
                            <option value="de" <?php selected( $transcript_language, 'de' ); ?>>🇩🇪 Deutsch</option>
                        </select>
                        <p class="mt-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <?php _e( 'Lingua predefinita per generazione trascrizioni', 'ipv-production-system-pro' ); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private static function render_general_tab() {
        $auto_publish = get_option( 'ipv_auto_publish_imports', false );
        ?>
        <div class="ipv-card bg-gradient-to-br from-purple-50 to-indigo-50 border-purple-200">
            <div class="ipv-card-header bg-purple-100 border-b border-purple-200">
                <h2 class="ipv-card-title text-purple-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <?php _e( 'Impostazioni Generali', 'ipv-production-system-pro' ); ?>
                </h2>
            </div>
            <div class="p-6">
                <div class="space-y-6">
                    <div class="bg-white rounded-lg border border-purple-200 p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 mt-1">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <label class="flex items-start cursor-pointer">
                                    <input type="checkbox"
                                           name="auto_publish_imports"
                                           value="1"
                                           <?php checked( $auto_publish ); ?>
                                           class="mt-1 h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                    <span class="ml-3">
                                        <span class="block text-sm font-semibold text-gray-900">
                                            <?php _e( 'Pubblicazione Automatica', 'ipv-production-system-pro' ); ?>
                                        </span>
                                        <span class="block text-sm text-gray-600 mt-1">
                                            <?php _e( 'Pubblica automaticamente i video importati invece di salvarli come bozze', 'ipv-production-system-pro' ); ?>
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-amber-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div class="text-sm text-amber-800">
                                <p class="font-semibold"><?php _e( 'Attenzione', 'ipv-production-system-pro' ); ?></p>
                                <p class="mt-1"><?php _e( 'Se attivi la pubblicazione automatica, i video saranno immediatamente visibili sul tuo sito. Assicurati di rivedere le descrizioni generate prima di importare.', 'ipv-production-system-pro' ); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
