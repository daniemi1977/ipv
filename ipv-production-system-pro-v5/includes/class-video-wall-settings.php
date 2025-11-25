<?php
/**
 * Video Wall Settings Panel
 * Pannello di configurazione per il Video Wall
 *
 * @package IPV_Production_System_Pro
 * @version 5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Video_Wall_Settings {

    /**
     * Inizializza
     */
    public static function init() {
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    /**
     * Registra le impostazioni
     */
    public static function register_settings() {
        register_setting( 'ipv_wall_settings_group', 'ipv_wall_per_page' );
        register_setting( 'ipv_wall_settings_group', 'ipv_wall_layout' );
        register_setting( 'ipv_wall_settings_group', 'ipv_wall_columns' );
        register_setting( 'ipv_wall_settings_group', 'ipv_wall_show_filters' );
        register_setting( 'ipv_wall_settings_group', 'ipv_wall_filter_position' );
        register_setting( 'ipv_wall_settings_group', 'ipv_wall_show_search' );
        register_setting( 'ipv_wall_settings_group', 'ipv_wall_show_year' );
        register_setting( 'ipv_wall_settings_group', 'ipv_wall_show_speaker' );
        register_setting( 'ipv_wall_settings_group', 'ipv_wall_show_topic' );
    }

    /**
     * Render pagina impostazioni
     */
    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Salva le impostazioni
        if ( isset( $_POST['ipv_save_wall_settings'] ) ) {
            check_admin_referer( 'ipv_wall_settings_save' );
            self::save_settings();
            ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Impostazioni Video Wall salvate!</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php
        }

        // Ottieni le impostazioni
        $per_page        = get_option( 'ipv_wall_per_page', 5 );
        $layout          = get_option( 'ipv_wall_layout', 'grid' );
        $columns         = get_option( 'ipv_wall_columns', 3 );
        $show_filters    = get_option( 'ipv_wall_show_filters', 'yes' );
        $filter_position = get_option( 'ipv_wall_filter_position', 'top' );
        $show_search     = get_option( 'ipv_wall_show_search', 'yes' );
        $show_year       = get_option( 'ipv_wall_show_year', 'yes' );
        $show_speaker    = get_option( 'ipv_wall_show_speaker', 'yes' );
        $show_topic      = get_option( 'ipv_wall_show_topic', 'yes' );

        ?>
        <div class="wrap ipv-prod-wrap">
            <div class="ipv-prod-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-1">
                            <i class="bi bi-grid-3x3-gap-fill text-white me-2"></i>
                            Configurazione Video Wall
                        </h1>
                        <p class="text-muted mb-0">Personalizza l'aspetto e il comportamento del Video Wall</p>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-dashboard' ) ); ?>">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-import' ) ); ?>">
                        <i class="bi bi-upload me-1"></i>Importa Video
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-rss' ) ); ?>">
                        <i class="bi bi-rss me-1"></i>Auto-Import RSS
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-queue' ) ); ?>">
                        <i class="bi bi-list-task me-1"></i>Coda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-wall' ) ); ?>">
                        <i class="bi bi-grid-3x3-gap me-1"></i>Video Wall
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-settings' ) ); ?>">
                        <i class="bi bi-gear me-1"></i>Impostazioni
                    </a>
                </li>
            </ul>

            <form method="post" action="" class="ipv-form">
                <?php wp_nonce_field( 'ipv_wall_settings_save' ); ?>
                <input type="hidden" name="ipv_save_wall_settings" value="1" />

                <div class="row g-4">
                    <!-- Colonna Sinistra -->
                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-layout-text-window-reverse me-2"></i>Layout & Visualizzazione</h5>
                            </div>
                            <div class="card-body">
                                <!-- Video per pagina -->
                                <div class="mb-4">
                                    <label for="ipv_wall_per_page" class="form-label fw-bold">
                                        <i class="bi bi-collection me-1"></i>Video per pagina
                                    </label>
                                    <input type="number"
                                           class="form-control"
                                           id="ipv_wall_per_page"
                                           name="ipv_wall_per_page"
                                           value="<?php echo esc_attr( $per_page ); ?>"
                                           min="1"
                                           max="100" />
                                    <div class="form-text">Numero di video da mostrare per pagina (1-100)</div>
                                </div>

                                <!-- Layout -->
                                <div class="mb-4">
                                    <label for="ipv_wall_layout" class="form-label fw-bold">
                                        <i class="bi bi-grid me-1"></i>Tipo di Layout
                                    </label>
                                    <select class="form-select" id="ipv_wall_layout" name="ipv_wall_layout">
                                        <option value="grid" <?php selected( $layout, 'grid' ); ?>>Griglia (Grid)</option>
                                        <option value="list" <?php selected( $layout, 'list' ); ?>>Lista (List)</option>
                                    </select>
                                    <div class="form-text">Scegli come visualizzare i video</div>
                                </div>

                                <!-- Colonne -->
                                <div class="mb-4">
                                    <label for="ipv_wall_columns" class="form-label fw-bold">
                                        <i class="bi bi-columns me-1"></i>Numero di Colonne
                                    </label>
                                    <select class="form-select" id="ipv_wall_columns" name="ipv_wall_columns">
                                        <option value="2" <?php selected( $columns, 2 ); ?>>2 Colonne</option>
                                        <option value="3" <?php selected( $columns, 3 ); ?>>3 Colonne</option>
                                        <option value="4" <?php selected( $columns, 4 ); ?>>4 Colonne</option>
                                    </select>
                                    <div class="form-text">Numero di colonne nel layout griglia (solo per layout Grid)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Colonna Destra -->
                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtri</h5>
                            </div>
                            <div class="card-body">
                                <!-- Mostra filtri -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">
                                        <i class="bi bi-toggles me-1"></i>Mostra Filtri
                                    </label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="ipv_wall_show_filters"
                                               name="ipv_wall_show_filters"
                                               value="yes"
                                               <?php checked( $show_filters, 'yes' ); ?> />
                                        <label class="form-check-label" for="ipv_wall_show_filters">
                                            Abilita sezione filtri
                                        </label>
                                    </div>
                                </div>

                                <!-- Filtri specifici -->
                                <div class="border-start border-3 border-primary ps-3 mb-4">
                                    <label class="form-label fw-bold mb-3">Filtri da Mostrare:</label>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="ipv_wall_show_search"
                                               name="ipv_wall_show_search"
                                               value="yes"
                                               <?php checked( $show_search, 'yes' ); ?> />
                                        <label class="form-check-label" for="ipv_wall_show_search">
                                            <i class="bi bi-search me-1"></i>Ricerca testuale
                                        </label>
                                    </div>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="ipv_wall_show_year"
                                               name="ipv_wall_show_year"
                                               value="yes"
                                               <?php checked( $show_year, 'yes' ); ?> />
                                        <label class="form-check-label" for="ipv_wall_show_year">
                                            <i class="bi bi-calendar me-1"></i>Filtro Anno
                                        </label>
                                    </div>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="ipv_wall_show_speaker"
                                               name="ipv_wall_show_speaker"
                                               value="yes"
                                               <?php checked( $show_speaker, 'yes' ); ?> />
                                        <label class="form-check-label" for="ipv_wall_show_speaker">
                                            <i class="bi bi-person me-1"></i>Filtro Relatore
                                        </label>
                                    </div>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="ipv_wall_show_topic"
                                               name="ipv_wall_show_topic"
                                               value="yes"
                                               <?php checked( $show_topic, 'yes' ); ?> />
                                        <label class="form-check-label" for="ipv_wall_show_topic">
                                            <i class="bi bi-tag me-1"></i>Filtro Argomento
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shortcode Info -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-code-slash me-2"></i>Come Usare il Video Wall</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">Usa lo shortcode seguente per inserire il Video Wall in qualsiasi pagina o post:</p>

                        <div class="alert alert-dark mb-4">
                            <h6 class="fw-bold mb-3">Shortcode Base (usa le impostazioni salvate):</h6>
                            <code class="fs-5">[ipv_video_wall]</code>
                            <button type="button" class="btn btn-sm btn-outline-light float-end" onclick="navigator.clipboard.writeText('[ipv_video_wall]')">
                                <i class="bi bi-clipboard me-1"></i>Copia
                            </button>
                        </div>

                        <div class="alert alert-secondary">
                            <h6 class="fw-bold mb-3">Shortcode Personalizzato (ignora impostazioni):</h6>
                            <code class="d-block mb-2">[ipv_video_wall per_page="12" layout="grid" columns="3" show_filters="yes"]</code>
                            <small class="text-muted">
                                <strong>Parametri disponibili:</strong><br>
                                • <code>per_page</code>: Numero video per pagina (default: 12)<br>
                                • <code>layout</code>: "grid" o "list" (default: grid)<br>
                                • <code>columns</code>: 2, 3 o 4 (default: 3)<br>
                                • <code>show_filters</code>: "yes" o "no" (default: yes)
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save me-2"></i>Salva Impostazioni Video Wall
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Salva le impostazioni
     */
    protected static function save_settings() {
        $fields = [
            'ipv_wall_per_page'        => 'absint',
            'ipv_wall_layout'          => 'sanitize_text_field',
            'ipv_wall_columns'         => 'absint',
            'ipv_wall_show_filters'    => 'sanitize_text_field',
            'ipv_wall_filter_position' => 'sanitize_text_field',
            'ipv_wall_show_search'     => 'sanitize_text_field',
            'ipv_wall_show_year'       => 'sanitize_text_field',
            'ipv_wall_show_speaker'    => 'sanitize_text_field',
            'ipv_wall_show_topic'      => 'sanitize_text_field',
        ];

        foreach ( $fields as $field => $sanitize_func ) {
            if ( isset( $_POST[ $field ] ) ) {
                $value = call_user_func( $sanitize_func, wp_unslash( $_POST[ $field ] ) );
                update_option( $field, $value );
            } else {
                // Checkbox: se non settato = 'no'
                if ( strpos( $field, 'show_' ) !== false ) {
                    update_option( $field, 'no' );
                }
            }
        }
    }
}

// Inizializza
IPV_Prod_Video_Wall_Settings::init();
