<?php
/**
 * IPV Production System Pro - Settings
 * 
 * Versione SaaS: API keys gestite dal server cloud
 * Golden Prompt configurabile dall'utente
 *
 * @package IPV_Production_System_Pro
 * @version 10.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Settings {

    public static function register_settings() {
        // Cloud settings (no API keys - handled by server)
        register_setting( 'ipv_prod_settings_group', 'ipv_transcript_mode' );
        
        // Golden Prompt
        register_setting( 'ipv_prod_settings_group', 'ipv_golden_prompt' );
        
        // Import filters
        register_setting( 'ipv_prod_settings_group', 'ipv_min_duration_minutes' );
        register_setting( 'ipv_prod_settings_group', 'ipv_exclude_shorts' );
        
        // Channel parameters
        register_setting( 'ipv_prod_settings_group', 'ipv_default_sponsor' );
        register_setting( 'ipv_prod_settings_group', 'ipv_sponsor_link' );
        register_setting( 'ipv_prod_settings_group', 'ipv_social_telegram' );
        register_setting( 'ipv_prod_settings_group', 'ipv_social_facebook' );
        register_setting( 'ipv_prod_settings_group', 'ipv_social_instagram' );
        register_setting( 'ipv_prod_settings_group', 'ipv_social_website' );
        register_setting( 'ipv_prod_settings_group', 'ipv_contact_email' );
        register_setting( 'ipv_prod_settings_group', 'ipv_paypal_link' );
        
        // Template settings
        register_setting( 'ipv_prod_settings_group', 'ipv_primary_color' );
        register_setting( 'ipv_prod_settings_group', 'ipv_player_behavior' );
        register_setting( 'ipv_prod_settings_group', 'ipv_show_views' );
        register_setting( 'ipv_prod_settings_group', 'ipv_show_duration' );
        register_setting( 'ipv_prod_settings_group', 'ipv_show_date' );
        register_setting( 'ipv_prod_settings_group', 'ipv_show_categories' );
        register_setting( 'ipv_prod_settings_group', 'ipv_show_speakers' );
        register_setting( 'ipv_prod_settings_group', 'ipv_default_layout' );
        register_setting( 'ipv_prod_settings_group', 'ipv_videos_per_page' );
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

        // Get saved values
        $transcript_mode  = get_option( 'ipv_transcript_mode', 'auto' );
        $golden_prompt    = get_option( 'ipv_golden_prompt', '' );
        $default_sponsor  = get_option( 'ipv_default_sponsor', 'Biovital – Progetto Italia' );
        $sponsor_link     = get_option( 'ipv_sponsor_link', 'https://biovital-italia.com/?bio=17' );
        $telegram         = get_option( 'ipv_social_telegram', 'https://t.me/il_punto_divista' );
        $facebook         = get_option( 'ipv_social_facebook', 'https://facebook.com/groups/4102938329737588' );
        $instagram        = get_option( 'ipv_social_instagram', '' );
        $website          = get_option( 'ipv_social_website', '' );
        $email            = get_option( 'ipv_contact_email', '' );
        $paypal_link      = get_option( 'ipv_paypal_link', 'https://paypal.me/adrianfiorelli' );
        $min_duration     = get_option( 'ipv_min_duration_minutes', 0 );
        $exclude_shorts   = get_option( 'ipv_exclude_shorts', '0' );
        
        // License status
        $is_licensed = class_exists( 'IPV_Prod_API_Client_Optimized' ) ? IPV_Prod_API_Client_Optimized::is_license_active() : false;
        $license_info = get_option( 'ipv_license_info', [] );
        ?>
        <div class="wrap ipv-prod-wrap">
            <div class="ipv-prod-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-1">
                            <i class="bi bi-gear-fill text-white me-2"></i>
                            <?php esc_html_e( 'Settings', 'ipv-production-system-pro' ); ?>
                        </h1>
                        <p class="text-muted mb-0"><?php esc_html_e( 'Configure your video production settings', 'ipv-production-system-pro' ); ?></p>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'edit.php?post_type=ipv_video&page=ipv-production-dashboard' ) ); ?>">
                        <i class="bi bi-speedometer2 me-1"></i><?php esc_html_e( 'Dashboard', 'ipv-production-system-pro' ); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'edit.php?post_type=ipv_video&page=ipv-import' ) ); ?>">
                        <i class="bi bi-upload me-1"></i><?php esc_html_e( 'Import', 'ipv-production-system-pro' ); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="<?php echo esc_url( admin_url( 'edit.php?post_type=ipv_video&page=ipv-settings' ) ); ?>">
                        <i class="bi bi-gear me-1"></i><?php esc_html_e( 'Settings', 'ipv-production-system-pro' ); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'edit.php?post_type=ipv_video&page=ipv-license' ) ); ?>">
                        <i class="bi bi-key me-1"></i><?php esc_html_e( 'License', 'ipv-production-system-pro' ); ?>
                    </a>
                </li>
            </ul>

            <!-- Cloud Service Status -->
            <div class="card shadow-sm mb-4 <?php echo $is_licensed ? 'border-success' : 'border-warning'; ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <?php if ( $is_licensed ) : ?>
                                <h5 class="mb-1 text-success">
                                    <i class="bi bi-cloud-check me-2"></i>
                                    <?php esc_html_e( 'Cloud Services Active', 'ipv-production-system-pro' ); ?>
                                </h5>
                                <p class="mb-0 text-muted">
                                    <?php esc_html_e( 'Transcription and AI generation services are ready to use.', 'ipv-production-system-pro' ); ?>
                                    <?php if ( ! empty( $license_info['variant'] ) ) : ?>
                                        <span class="badge bg-primary ms-2"><?php echo esc_html( ucfirst( $license_info['variant'] ) ); ?></span>
                                    <?php endif; ?>
                                </p>
                            <?php else : ?>
                                <h5 class="mb-1 text-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <?php esc_html_e( 'License Required', 'ipv-production-system-pro' ); ?>
                                </h5>
                                <p class="mb-0 text-muted">
                                    <?php esc_html_e( 'Activate your license to enable transcription and AI generation.', 'ipv-production-system-pro' ); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ipv_video&page=ipv-license' ) ); ?>" 
                           class="btn <?php echo $is_licensed ? 'btn-outline-success' : 'btn-warning'; ?>">
                            <i class="bi bi-key me-1"></i>
                            <?php echo $is_licensed ? esc_html__( 'Manage License', 'ipv-production-system-pro' ) : esc_html__( 'Activate Now', 'ipv-production-system-pro' ); ?>
                        </a>
                    </div>
                </div>
            </div>

            <form method="post" action="<?php echo esc_url( admin_url( 'edit.php?post_type=ipv_video&page=ipv-settings' ) ); ?>" class="ipv-form">
                <?php wp_nonce_field( 'ipv_prod_settings_save', 'ipv_settings_nonce' ); ?>
                <input type="hidden" name="ipv_save_settings" value="1" />

                <div class="row g-4">
                    <!-- Colonna Sinistra -->
                    <div class="col-lg-6">
                        
                        <!-- Processing Options -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-sliders text-primary me-2"></i>
                                    <?php esc_html_e( 'Processing Options', 'ipv-production-system-pro' ); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Transcription Mode -->
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-mic me-1"></i>
                                        <?php esc_html_e( 'Transcription Mode', 'ipv-production-system-pro' ); ?>
                                    </label>
                                    <select name="ipv_transcript_mode" class="form-select">
                                        <option value="auto" <?php selected( $transcript_mode, 'auto' ); ?>>
                                            <?php esc_html_e( 'Auto (recommended)', 'ipv-production-system-pro' ); ?>
                                        </option>
                                        <option value="native" <?php selected( $transcript_mode, 'native' ); ?>>
                                            <?php esc_html_e( 'Native subtitles only', 'ipv-production-system-pro' ); ?>
                                        </option>
                                        <option value="generate" <?php selected( $transcript_mode, 'generate' ); ?>>
                                            <?php esc_html_e( 'Always generate', 'ipv-production-system-pro' ); ?>
                                        </option>
                                    </select>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <?php esc_html_e( 'Auto uses existing subtitles when available, otherwise generates them.', 'ipv-production-system-pro' ); ?>
                                    </div>
                                </div>

                                <!-- Import Filters -->
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-funnel me-1"></i>
                                        <?php esc_html_e( 'Import Filters', 'ipv-production-system-pro' ); ?>
                                    </label>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><?php esc_html_e( 'Min duration', 'ipv-production-system-pro' ); ?></span>
                                                <input type="number"
                                                       class="form-control"
                                                       name="ipv_min_duration_minutes"
                                                       value="<?php echo esc_attr( $min_duration ); ?>"
                                                       min="0"
                                                       step="1" />
                                                <span class="input-group-text"><?php esc_html_e( 'min', 'ipv-production-system-pro' ); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check mt-2">
                                                <input type="checkbox"
                                                       class="form-check-input"
                                                       id="ipv_exclude_shorts"
                                                       name="ipv_exclude_shorts"
                                                       value="1"
                                                       <?php checked( $exclude_shorts, '1' ); ?> />
                                                <label class="form-check-label" for="ipv_exclude_shorts">
                                                    <?php esc_html_e( 'Exclude Shorts', 'ipv-production-system-pro' ); ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <?php esc_html_e( 'Filter out short videos during import.', 'ipv-production-system-pro' ); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Channel Parameters -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-broadcast-pin text-primary me-2"></i>
                                    <?php esc_html_e( 'Channel Parameters', 'ipv-production-system-pro' ); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-award me-1"></i>
                                        <?php esc_html_e( 'Default Sponsor', 'ipv-production-system-pro' ); ?>
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           name="ipv_default_sponsor"
                                           value="<?php echo esc_attr( $default_sponsor ); ?>"
                                           placeholder="Sponsor Name" />
                                </div>

                                <div class="mb-0">
                                    <label class="form-label">
                                        <i class="bi bi-link-45deg me-1"></i>
                                        <?php esc_html_e( 'Sponsor Link', 'ipv-production-system-pro' ); ?>
                                    </label>
                                    <input type="url"
                                           class="form-control"
                                           name="ipv_sponsor_link"
                                           value="<?php echo esc_attr( $sponsor_link ); ?>"
                                           placeholder="https://..." />
                                </div>
                            </div>
                        </div>

                        <!-- Social Media -->
                        <div class="card shadow-sm">
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
                                    <input type="url" class="form-control" name="ipv_social_telegram" 
                                           value="<?php echo esc_attr( $telegram ); ?>" placeholder="https://t.me/..." />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-facebook text-primary me-1"></i>
                                        Facebook
                                    </label>
                                    <input type="url" class="form-control" name="ipv_social_facebook" 
                                           value="<?php echo esc_attr( $facebook ); ?>" placeholder="https://facebook.com/..." />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-instagram text-danger me-1"></i>
                                        Instagram
                                    </label>
                                    <input type="text" class="form-control" name="ipv_social_instagram" 
                                           value="<?php echo esc_attr( $instagram ); ?>" placeholder="@username" />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-globe2 text-success me-1"></i>
                                        <?php esc_html_e( 'Website', 'ipv-production-system-pro' ); ?>
                                    </label>
                                    <input type="url" class="form-control" name="ipv_social_website" 
                                           value="<?php echo esc_attr( $website ); ?>" placeholder="https://..." />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-paypal text-primary me-1"></i>
                                        PayPal / Donazioni
                                    </label>
                                    <input type="url" class="form-control" name="ipv_paypal_link" 
                                           value="<?php echo esc_attr( $paypal_link ); ?>" placeholder="https://paypal.me/..." />
                                </div>

                                <div class="mb-0">
                                    <label class="form-label">
                                        <i class="bi bi-envelope-fill text-warning me-1"></i>
                                        <?php esc_html_e( 'Contact Email', 'ipv-production-system-pro' ); ?>
                                    </label>
                                    <input type="email" class="form-control" name="ipv_contact_email" 
                                           value="<?php echo esc_attr( $email ); ?>" placeholder="info@..." />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Colonna Destra -->
                    <div class="col-lg-6">
                        
                        <!-- Golden Prompt -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-stars text-warning me-2"></i>
                                    <?php esc_html_e( 'Golden Prompt', 'ipv-production-system-pro' ); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info mb-3">
                                    <i class="bi bi-lightbulb me-2"></i>
                                    <strong><?php esc_html_e( 'Your AI Instructions', 'ipv-production-system-pro' ); ?></strong><br>
                                    <?php esc_html_e( 'Insert your custom prompt here. This will be used by the AI to generate descriptions, chapters, hashtags, and metadata for your videos.', 'ipv-production-system-pro' ); ?>
                                </div>
                                
                                <textarea name="ipv_golden_prompt"
                                          rows="18"
                                          class="form-control font-monospace"
                                          style="font-size: 12px;"
                                          placeholder="<?php esc_attr_e( 'Insert your custom AI prompt here...

Example:
You are an expert SEO copywriter for YouTube.
Analyze the video transcript and generate:
1. An engaging description (150-200 words)
2. Chapters with timestamps
3. Relevant hashtags

Tone: Professional but accessible.
Language: Italian.', 'ipv-production-system-pro' ); ?>"><?php echo esc_textarea( $golden_prompt ); ?></textarea>
                                
                                <div class="form-text mt-2">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <?php esc_html_e( 'A good prompt includes: desired format, tone, language, sections to generate (description, chapters, hashtags, tags).', 'ipv-production-system-pro' ); ?>
                                </div>

                                <?php if ( empty( $golden_prompt ) ) : ?>
                                    <div class="alert alert-warning mt-3 mb-0">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <?php esc_html_e( 'No Golden Prompt configured. AI generation will use a basic fallback prompt.', 'ipv-production-system-pro' ); ?>
                                    </div>
                                <?php else : ?>
                                    <div class="alert alert-success mt-3 mb-0">
                                        <i class="bi bi-check-circle me-2"></i>
                                        <?php 
                                        printf( 
                                            esc_html__( 'Golden Prompt configured: %d words, %d characters', 'ipv-production-system-pro' ),
                                            str_word_count( $golden_prompt ),
                                            strlen( $golden_prompt )
                                        ); 
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- v10.0.22 - AI Extraction Settings -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-diagram-3 text-primary me-2"></i>
                                    <?php esc_html_e( 'AI Metadata Extraction', 'ipv-production-system-pro' ); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info mb-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <?php esc_html_e( 'Choose which metadata to extract automatically from AI-generated descriptions. Disabled options will not populate the corresponding taxonomies.', 'ipv-production-system-pro' ); ?>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="ipv_extract_tags" id="ipv_extract_tags" value="1"
                                                   <?php checked( get_option( 'ipv_extract_tags', '1' ), '1' ); ?>>
                                            <label class="form-check-label" for="ipv_extract_tags">
                                                <i class="bi bi-hash me-1"></i>
                                                <?php esc_html_e( 'Extract Tags', 'ipv-production-system-pro' ); ?>
                                            </label>
                                            <div class="form-text small">
                                                <?php esc_html_e( 'Hashtags → WordPress Tags', 'ipv-production-system-pro' ); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="ipv_extract_categories" id="ipv_extract_categories" value="1"
                                                   <?php checked( get_option( 'ipv_extract_categories', '1' ), '1' ); ?>>
                                            <label class="form-check-label" for="ipv_extract_categories">
                                                <i class="bi bi-folder me-1"></i>
                                                <?php esc_html_e( 'Extract Categories', 'ipv-production-system-pro' ); ?>
                                            </label>
                                            <div class="form-text small">
                                                <?php esc_html_e( 'Topics → Video Categories', 'ipv-production-system-pro' ); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="ipv_extract_speakers" id="ipv_extract_speakers" value="1"
                                                   <?php checked( get_option( 'ipv_extract_speakers', '1' ), '1' ); ?>>
                                            <label class="form-check-label" for="ipv_extract_speakers">
                                                <i class="bi bi-person me-1"></i>
                                                <?php esc_html_e( 'Extract Speakers', 'ipv-production-system-pro' ); ?>
                                            </label>
                                            <div class="form-text small">
                                                <?php esc_html_e( 'Guests → Speakers Taxonomy', 'ipv-production-system-pro' ); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-secondary mt-3 mb-0">
                                    <i class="bi bi-lightbulb me-2"></i>
                                    <strong><?php esc_html_e( 'Tip:', 'ipv-production-system-pro' ); ?></strong>
                                    <?php esc_html_e( 'You can override these settings for individual videos in the video edit screen.', 'ipv-production-system-pro' ); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Template & Appearance -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-palette text-primary me-2"></i>
                                    <?php esc_html_e( 'Template & Appearance', 'ipv-production-system-pro' ); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Primary Color -->
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-palette-fill me-1"></i>
                                        <?php esc_html_e( 'Primary Color', 'ipv-production-system-pro' ); ?>
                                    </label>
                                    <input type="color" 
                                           class="form-control form-control-color" 
                                           name="ipv_primary_color" 
                                           value="<?php echo esc_attr( get_option( 'ipv_primary_color', '#FB0F5A' ) ); ?>" 
                                           style="width: 100px; height: 45px;" />
                                </div>

                                <!-- Video Player -->
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-play-circle me-1"></i>
                                        <?php esc_html_e( 'Video Player Behavior', 'ipv-production-system-pro' ); ?>
                                    </label>
                                    <select name="ipv_player_behavior" class="form-select">
                                        <option value="embed" <?php selected( get_option( 'ipv_player_behavior', 'embed' ), 'embed' ); ?>>
                                            <?php esc_html_e( 'Embed on page', 'ipv-production-system-pro' ); ?>
                                        </option>
                                        <option value="modal" <?php selected( get_option( 'ipv_player_behavior' ), 'modal' ); ?>>
                                            <?php esc_html_e( 'Open in modal', 'ipv-production-system-pro' ); ?>
                                        </option>
                                        <option value="youtube" <?php selected( get_option( 'ipv_player_behavior' ), 'youtube' ); ?>>
                                            <?php esc_html_e( 'Redirect to YouTube', 'ipv-production-system-pro' ); ?>
                                        </option>
                                    </select>
                                </div>

                                <!-- Display Options -->
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-eye me-1"></i>
                                        <?php esc_html_e( 'Display Options', 'ipv-production-system-pro' ); ?>
                                    </label>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="ipv_show_views" 
                                                       value="1" <?php checked( get_option( 'ipv_show_views', '1' ), '1' ); ?> id="show_views">
                                                <label class="form-check-label" for="show_views"><?php esc_html_e( 'Views', 'ipv-production-system-pro' ); ?></label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="ipv_show_duration" 
                                                       value="1" <?php checked( get_option( 'ipv_show_duration', '1' ), '1' ); ?> id="show_duration">
                                                <label class="form-check-label" for="show_duration"><?php esc_html_e( 'Duration', 'ipv-production-system-pro' ); ?></label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="ipv_show_date" 
                                                       value="1" <?php checked( get_option( 'ipv_show_date', '1' ), '1' ); ?> id="show_date">
                                                <label class="form-check-label" for="show_date"><?php esc_html_e( 'Date', 'ipv-production-system-pro' ); ?></label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="ipv_show_categories" 
                                                       value="1" <?php checked( get_option( 'ipv_show_categories', '1' ), '1' ); ?> id="show_categories">
                                                <label class="form-check-label" for="show_categories"><?php esc_html_e( 'Categories', 'ipv-production-system-pro' ); ?></label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="ipv_show_speakers" 
                                                       value="1" <?php checked( get_option( 'ipv_show_speakers', '1' ), '1' ); ?> id="show_speakers">
                                                <label class="form-check-label" for="show_speakers"><?php esc_html_e( 'Speakers', 'ipv-production-system-pro' ); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Layout -->
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-grid-3x3 me-1"></i>
                                        <?php esc_html_e( 'Default Layout', 'ipv-production-system-pro' ); ?>
                                    </label>
                                    <select name="ipv_default_layout" class="form-select">
                                        <option value="grid" <?php selected( get_option( 'ipv_default_layout', 'grid' ), 'grid' ); ?>>
                                            <?php esc_html_e( 'Grid', 'ipv-production-system-pro' ); ?>
                                        </option>
                                        <option value="list" <?php selected( get_option( 'ipv_default_layout' ), 'list' ); ?>>
                                            <?php esc_html_e( 'List', 'ipv-production-system-pro' ); ?>
                                        </option>
                                        <option value="masonry" <?php selected( get_option( 'ipv_default_layout' ), 'masonry' ); ?>>
                                            <?php esc_html_e( 'Masonry', 'ipv-production-system-pro' ); ?>
                                        </option>
                                    </select>
                                </div>

                                <!-- Videos Per Page -->
                                <div class="mb-0">
                                    <label class="form-label">
                                        <i class="bi bi-collection me-1"></i>
                                        <?php esc_html_e( 'Videos Per Page', 'ipv-production-system-pro' ); ?>
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           name="ipv_videos_per_page" 
                                           value="<?php echo esc_attr( get_option( 'ipv_videos_per_page', '12' ) ); ?>" 
                                           min="1" max="100" style="width: 100px;" />
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
                                <i class="bi bi-info-circle me-1"></i>
                                <?php esc_html_e( 'Settings are saved locally. Cloud services are managed via your license.', 'ipv-production-system-pro' ); ?>
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
        $text_fields = [
            'ipv_transcript_mode',
            'ipv_min_duration_minutes',
            'ipv_default_sponsor',
            'ipv_sponsor_link',
            'ipv_social_telegram',
            'ipv_social_facebook',
            'ipv_social_instagram',
            'ipv_social_website',
            'ipv_contact_email',
            'ipv_paypal_link',
            'ipv_primary_color',
            'ipv_player_behavior',
            'ipv_default_layout',
            'ipv_videos_per_page',
        ];

        foreach ( $text_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                $value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
                
                if ( $field === 'ipv_min_duration_minutes' ) {
                    $value = max( 0, (int) $value );
                }
                if ( $field === 'ipv_videos_per_page' ) {
                    $value = max( 1, min( 100, (int) $value ) );
                }
                
                update_option( $field, $value );
            }
        }

        // Golden Prompt (textarea)
        if ( isset( $_POST['ipv_golden_prompt'] ) ) {
            $value = sanitize_textarea_field( wp_unslash( $_POST['ipv_golden_prompt'] ) );
            update_option( 'ipv_golden_prompt', $value );
        }

        // Checkboxes
        $checkboxes = [
            'ipv_exclude_shorts',
            'ipv_show_views',
            'ipv_show_duration',
            'ipv_show_date',
            'ipv_show_categories',
            'ipv_show_speakers',
            // v10.0.22 - Extraction flags
            'ipv_extract_tags',
            'ipv_extract_categories',
            'ipv_extract_speakers',
        ];

        foreach ( $checkboxes as $checkbox ) {
            $value = isset( $_POST[ $checkbox ] ) ? '1' : '0';
            update_option( $checkbox, $value );
        }
    }
}
