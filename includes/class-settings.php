<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Settings {

    public static function register_settings() {
        register_setting( 'ipv_prod_settings_group', 'ipv_supadata_api_key' );
        register_setting( 'ipv_prod_settings_group', 'ipv_transcript_mode' );
        register_setting( 'ipv_prod_settings_group', 'ipv_openai_api_key' );
        register_setting( 'ipv_prod_settings_group', 'ipv_ai_prompt' );
        register_setting( 'ipv_prod_settings_group', 'ipv_youtube_api_key' );
        register_setting( 'ipv_prod_settings_group', 'ipv_min_duration_minutes' );
        register_setting( 'ipv_prod_settings_group', 'ipv_exclude_shorts' );
        register_setting( 'ipv_prod_settings_group', 'ipv_default_sponsor' );
        register_setting( 'ipv_prod_settings_group', 'ipv_sponsor_link' );
        register_setting( 'ipv_prod_settings_group', 'ipv_social_telegram' );
        register_setting( 'ipv_prod_settings_group', 'ipv_social_facebook' );
        register_setting( 'ipv_prod_settings_group', 'ipv_social_instagram' );
        register_setting( 'ipv_prod_settings_group', 'ipv_social_website' );
        register_setting( 'ipv_prod_settings_group', 'ipv_contact_email' );
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Save settings if form was submitted
        if ( isset( $_POST['ipv_save_settings'] ) && isset( $_POST['ipv_settings_nonce'] ) ) {
            if ( wp_verify_nonce( $_POST['ipv_settings_nonce'], 'ipv_prod_settings_save' ) ) {
                self::save_settings();
                ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong><?php esc_html_e( 'Settings saved successfully!', 'ipv-production-system-pro' ); ?></strong>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php
            }
        }

        $supadata_key     = get_option( 'ipv_supadata_api_key', '' );
        $transcript_mode  = get_option( 'ipv_transcript_mode', 'auto' );
        $openai_key       = get_option( 'ipv_openai_api_key', '' );
        $youtube_key      = get_option( 'ipv_youtube_api_key', '' );
        $default_sponsor  = get_option( 'ipv_default_sponsor', 'Biovital – Progetto Italia' );
        $sponsor_link     = get_option( 'ipv_sponsor_link', 'https://biovital-italia.com/?bio=17' );
        $telegram         = get_option( 'ipv_social_telegram', 'https://t.me/il_punto_divista' );
        $facebook         = get_option( 'ipv_social_facebook', 'https://facebook.com/groups/4102938329737588' );
        $instagram        = get_option( 'ipv_social_instagram', 'https://instagram.com/_ilpuntodivista._' );
        $website          = get_option( 'ipv_social_website', 'https://ilpuntodivistachannel.com' );
        $email            = get_option( 'ipv_contact_email', '' );
        $custom_prompt    = get_option( 'ipv_ai_prompt', '' );
        $min_duration     = get_option( 'ipv_min_duration_minutes', 0 );
        $exclude_shorts   = get_option( 'ipv_exclude_shorts', '0' );
        $paypal_link      = get_option( 'ipv_paypal_link', 'https://paypal.me/adrianfiorelli' );
        ?>
        <div class="wrap ipv-prod-wrap">
            <div class="ipv-prod-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-1">
                            <i class="bi bi-gear-fill text-white me-2"></i>
                            <?php esc_html_e( 'Settings', 'ipv-production-system-pro' ); ?>
                        </h1>
                        <p class="text-muted mb-0"><?php esc_html_e( 'Configure API keys and channel parameters', 'ipv-production-system-pro' ); ?></p>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production' ) ); ?>">
                        <i class="bi bi-speedometer2 me-1"></i><?php esc_html_e( 'Dashboard', 'ipv-production-system-pro' ); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-import' ) ); ?>">
                        <i class="bi bi-upload me-1"></i><?php esc_html_e( 'Import Video', 'ipv-production-system-pro' ); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-rss' ) ); ?>">
                        <i class="bi bi-rss me-1"></i><?php esc_html_e( 'Auto-Import RSS', 'ipv-production-system-pro' ); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-queue' ) ); ?>">
                        <i class="bi bi-list-task me-1"></i><?php esc_html_e( 'Queue', 'ipv-production-system-pro' ); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-settings' ) ); ?>">
                        <i class="bi bi-gear me-1"></i><?php esc_html_e( 'Settings', 'ipv-production-system-pro' ); ?>
                    </a>
                </li>
            </ul>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-settings' ) ); ?>" class="ipv-form">
                <?php wp_nonce_field( 'ipv_prod_settings_save', 'ipv_settings_nonce' ); ?>
                <input type="hidden" name="ipv_save_settings" value="1" />

                <div class="row g-4">
                    <!-- Colonna API Keys -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-key-fill text-primary me-2"></i>
                                    API Keys
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- SupaData API Key -->
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-cloud me-1"></i>
                                        SupaData API Key
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control ipv-validate" 
                                           name="ipv_supadata_api_key" 
                                           value="<?php echo esc_attr( $supadata_key ); ?>" 
                                           data-validate-type="required"
                                           placeholder="sk-..." />
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <?php esc_html_e( 'Required to generate video transcriptions', 'ipv-production-system-pro' ); ?>
                                    </div>
                                </div>

                                <!-- Transcription Mode -->
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-toggles me-1"></i>
                                        <?php esc_html_e( 'Transcription Mode', 'ipv-production-system-pro' ); ?>
                                    </label>
                                    <select name="ipv_transcript_mode" class="form-select">
                                        <option value="auto" <?php selected( $transcript_mode, 'auto' ); ?>>
                                            <?php esc_html_e( 'Auto (recommended)', 'ipv-production-system-pro' ); ?>
                                        </option>
                                        <option value="native" <?php selected( $transcript_mode, 'native' ); ?>>
                                            <?php esc_html_e( 'Native', 'ipv-production-system-pro' ); ?>
                                        </option>
                                        <option value="generate" <?php selected( $transcript_mode, 'generate' ); ?>>
                                            <?php esc_html_e( 'Generate', 'ipv-production-system-pro' ); ?>
                                        </option>
                                    </select>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <?php esc_html_e( 'Auto uses subtitles if available, otherwise generates them', 'ipv-production-system-pro' ); ?>
                                    </div>
                                </div>

                                <!-- OpenAI API Key -->
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-robot me-1"></i>
                                        OpenAI API Key
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control ipv-validate" 
                                           name="ipv_openai_api_key" 
                                           value="<?php echo esc_attr( $openai_key ); ?>" 
                                           data-validate-type="required"
                                           placeholder="sk-..." />
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <?php esc_html_e( 'Required to generate AI descriptions', 'ipv-production-system-pro' ); ?>
                                    </div>
                                </div>

                                <!-- YouTube API Key -->
                                <div class="mb-0">
                                    <label class="form-label">
                                        <i class="bi bi-youtube me-1"></i>
                                        YouTube Data API Key
                                        <span class="badge bg-warning text-dark"><?php esc_html_e( 'Optional', 'ipv-production-system-pro' ); ?></span>
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           name="ipv_youtube_api_key"
                                           value="<?php echo esc_attr( $youtube_key ); ?>"
                                           placeholder="AIza..." />
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <?php esc_html_e( 'For retrieving correct video titles', 'ipv-production-system-pro' ); ?>
                                    </div>

                                <!-- YouTube Import Filters -->
                                <div class="mb-4 mt-4">
                                    <label class="form-label">
                                        <i class="bi bi-clock-history me-1"></i>
                                        <?php esc_html_e( 'YouTube Import Filters', 'ipv-production-system-pro' ); ?>
                                    </label>

                                    <div class="row g-3 align-items-center">
                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <span class="input-group-text"><?php esc_html_e( 'Minimum duration', 'ipv-production-system-pro' ); ?></span>
                                                <input type="number"
                                                       class="form-control"
                                                       name="ipv_min_duration_minutes"
                                                       value="<?php echo esc_attr( $min_duration ); ?>"
                                                       min="0"
                                                       step="1" />
                                                <span class="input-group-text"><?php esc_html_e( 'min', 'ipv-production-system-pro' ); ?></span>
                                            </div>
                                            <div class="form-text">
                                                <i class="bi bi-info-circle me-1"></i>
                                                <?php esc_html_e( 'Videos shorter than this duration will not be imported (0 = disabled).', 'ipv-production-system-pro' ); ?>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-check mt-2">
                                                <input type="checkbox"
                                                       class="form-check-input"
                                                       id="ipv_exclude_shorts"
                                                       name="ipv_exclude_shorts"
                                                       value="1"
                                                       <?php checked( $exclude_shorts, '1' ); ?> />
                                                <label class="form-check-label" for="ipv_exclude_shorts">
                                                    <?php esc_html_e( 'Automatically exclude Shorts / Reels', 'ipv-production-system-pro' ); ?>
                                                </label>
                                                <div class="form-text">
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    <?php esc_html_e( 'Detected by duration (≤ 90 sec) and /shorts/ URLs.', 'ipv-production-system-pro' ); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                </div>
                            </div>
                        </div>

                        <!-- Channel Parameters -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-broadcast-pin text-primary me-2"></i>
                                    <?php esc_html_e( 'Channel Parameters', 'ipv-production-system-pro' ); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Sponsor -->
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-award me-1"></i>
                                        <?php esc_html_e( 'Default Sponsor', 'ipv-production-system-pro' ); ?>
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           name="ipv_default_sponsor"
                                           value="<?php echo esc_attr( $default_sponsor ); ?>"
                                           placeholder="Biovital – Progetto Italia" />
                                </div>

                                <!-- Sponsor Link -->
                                <div class="mb-0">
                                    <label class="form-label">
                                        <i class="bi bi-link-45deg me-1"></i>
                                        <?php esc_html_e( 'Sponsor Link', 'ipv-production-system-pro' ); ?>
                                    </label>
                                    <input type="url"
                                           class="form-control"
                                           name="ipv_sponsor_link"
                                           value="<?php echo esc_attr( $sponsor_link ); ?>"
                                           placeholder="https://www.biovital.it" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Colonna Social & Prompt -->
                    <div class="col-lg-6">
                        <!-- Social Media -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-share-fill text-primary me-2"></i>
                                    <?php esc_html_e( 'Social Media & Contacts', 'ipv-production-system-pro' ); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-telegram text-info me-1"></i>
                                        Telegram
                                    </label>
                                    <input type="url"
                                           class="form-control"
                                           name="ipv_social_telegram"
                                           value="<?php echo esc_attr( $telegram ); ?>"
                                           placeholder="https://t.me/ilpuntodivista" />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-facebook text-primary me-1"></i>
                                        Facebook
                                    </label>
                                    <input type="url"
                                           class="form-control"
                                           name="ipv_social_facebook"
                                           value="<?php echo esc_attr( $facebook ); ?>"
                                           placeholder="https://facebook.com/ilpuntodivista" />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-instagram text-danger me-1"></i>
                                        Instagram
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           name="ipv_social_instagram"
                                           value="<?php echo esc_attr( $instagram ); ?>"
                                           placeholder="@ilpuntodivista_official" />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-globe2 text-success me-1"></i>
                                        <?php esc_html_e( 'Website', 'ipv-production-system-pro' ); ?>
                                    </label>
                                    <input type="url"
                                           class="form-control"
                                           name="ipv_social_website"
                                           value="<?php echo esc_attr( $website ); ?>"
                                           placeholder="https://www.ilpuntodivista.it" />
                                </div>

                                <div class="mb-0">
                                    <label class="form-label">
                                        <i class="bi bi-envelope-fill text-warning me-1"></i>
                                        <?php esc_html_e( 'Contact Email', 'ipv-production-system-pro' ); ?>
                                    </label>
                                    <input type="email"
                                           class="form-control"
                                           name="ipv_contact_email"
                                           value="<?php echo esc_attr( $email ); ?>"
                                           placeholder="info@ilpuntodivista.it" />
                                </div>
                            </div>
                        </div>

                        <!-- Custom AI Prompt -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-code-square text-primary me-2"></i>
                                    <?php esc_html_e( 'Custom AI Prompt', 'ipv-production-system-pro' ); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info mb-3">
                                    <i class="bi bi-info-circle-fill me-2"></i>
                                    <?php esc_html_e( 'If you leave this field empty, the built-in GOLDEN PROMPT will be used (350+ lines, optimized for "Il Punto di Vista").', 'ipv-production-system-pro' ); ?>
                                </div>
                                <textarea name="ipv_ai_prompt"
                                          rows="12"
                                          class="form-control font-monospace"
                                          placeholder="<?php esc_attr_e( 'Leave empty to use the internal GOLDEN PROMPT...', 'ipv-production-system-pro' ); ?>"><?php echo esc_textarea( $custom_prompt ); ?></textarea>
                                <div class="form-text">
                                    <i class="bi bi-lightbulb me-1"></i>
                                    <?php esc_html_e( 'You can override the default prompt with your custom one', 'ipv-production-system-pro' ); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="card shadow-sm mt-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted">
                                <i class="bi bi-shield-check me-1"></i>
                                <?php esc_html_e( 'All API keys are securely saved in the database', 'ipv-production-system-pro' ); ?>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-1"></i>
                                <?php esc_html_e( 'Save Settings', 'ipv-production-system-pro' ); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    private static function save_settings() {
        $fields = [
            'ipv_supadata_api_key',
            'ipv_transcript_mode',
            'ipv_openai_api_key',
            'ipv_youtube_api_key',
            'ipv_min_duration_minutes',
            'ipv_default_sponsor',
            'ipv_sponsor_link',
            'ipv_social_telegram',
            'ipv_social_facebook',
            'ipv_social_instagram',
            'ipv_social_website',
            'ipv_contact_email',
            'ipv_ai_prompt',
        ];

        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                $value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );

                // Special handling for textarea
                if ( $field === 'ipv_ai_prompt' ) {
                    $value = sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) );
                }

                // Normalize minimum duration as integer >= 0
                if ( $field === 'ipv_min_duration_minutes' ) {
                    $value = max( 0, (int) $value );
                }

                update_option( $field, $value );
            }
        }

        // Checkbox "Exclude Shorts/Reels"
        $exclude_shorts = isset( $_POST['ipv_exclude_shorts'] ) ? '1' : '0';
        update_option( 'ipv_exclude_shorts', $exclude_shorts );
    }
}
