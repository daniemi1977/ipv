<?php
/**
 * IPV Production System Pro - Language Manager
 *
 * Gestione multilingua del plugin con editor .po live
 *
 * @package IPV_Production_System_Pro
 * @version 9.1.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Language_Manager {

    const SUPPORTED_LANGUAGES = [
        'en_US' => 'English (US)',
        'it_IT' => 'Italiano',
        'de_DE' => 'Deutsch',
        'es_ES' => 'Español',
        'fr_FR' => 'Français',
        'pt_PT' => 'Português',
        'ru_RU' => 'Русский',
    ];

    const OPTION_KEY = 'ipv_plugin_language';

    public static function init() {
        add_action( 'plugins_loaded', [ __CLASS__, 'load_plugin_language' ], 1 );
        add_action( 'admin_menu', [ __CLASS__, 'add_submenu' ], 100 );
        
        // AJAX handlers
        add_action( 'wp_ajax_ipv_save_language', [ __CLASS__, 'ajax_save_language' ] );
        add_action( 'wp_ajax_ipv_load_po_file', [ __CLASS__, 'ajax_load_po_file' ] );
        add_action( 'wp_ajax_ipv_save_po_file', [ __CLASS__, 'ajax_save_po_file' ] );
        add_action( 'wp_ajax_ipv_compile_mo', [ __CLASS__, 'ajax_compile_mo' ] );
    }

    public static function load_plugin_language() {
        $plugin_locale = self::get_plugin_locale();
        
        if ( $plugin_locale && $plugin_locale !== 'auto' ) {
            unload_textdomain( 'ipv-production-system-pro' );
            $mo_file = IPV_PROD_PLUGIN_DIR . 'languages/ipv-production-system-pro-' . $plugin_locale . '.mo';
            if ( file_exists( $mo_file ) ) {
                load_textdomain( 'ipv-production-system-pro', $mo_file );
            }
        }
    }

    public static function get_plugin_locale() {
        return get_option( self::OPTION_KEY, 'auto' );
    }

    public static function add_submenu() {
        add_submenu_page(
            'ipv-production',
            __( 'Language', 'ipv-production-system-pro' ),
            '🌐 ' . __( 'Language', 'ipv-production-system-pro' ),
            'manage_options',
            'ipv-production-language',
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function render_page() {
        $current_locale = self::get_plugin_locale();
        $wp_locale = get_locale();
        $translations_status = self::check_all_translations();
        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
        ?>
        <div class="wrap ipv-prod-wrap">
            <div class="ipv-prod-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-1">
                            <i class="bi bi-translate text-white me-2"></i>
                            <?php esc_html_e( 'Language', 'ipv-production-system-pro' ); ?>
                        </h1>
                        <p class="text-muted mb-0">
                            <?php esc_html_e( 'Manage plugin language and translations', 'ipv-production-system-pro' ); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
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
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-settings' ) ); ?>">
                        <i class="bi bi-gear me-1"></i><?php esc_html_e( 'Settings', 'ipv-production-system-pro' ); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-language' ) ); ?>">
                        <i class="bi bi-translate me-1"></i><?php esc_html_e( 'Language', 'ipv-production-system-pro' ); ?>
                    </a>
                </li>
            </ul>

            <!-- Sub-tabs -->
            <ul class="nav nav-pills mb-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo $active_tab === 'settings' ? 'active' : ''; ?>" 
                       href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-language&tab=settings' ) ); ?>">
                        <i class="bi bi-sliders me-1"></i><?php esc_html_e( 'Settings', 'ipv-production-system-pro' ); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $active_tab === 'editor' ? 'active' : ''; ?>" 
                       href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-language&tab=editor' ) ); ?>">
                        <i class="bi bi-pencil-square me-1"></i><?php esc_html_e( 'Editor .po', 'ipv-production-system-pro' ); ?>
                    </a>
                </li>
            </ul>

            <?php if ( $active_tab === 'editor' ) : ?>
                <?php self::render_editor_tab( $translations_status ); ?>
            <?php else : ?>
                <?php self::render_settings_tab( $current_locale, $wp_locale, $translations_status ); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_settings_tab( $current_locale, $wp_locale, $translations_status ) {
        ?>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-globe2 text-primary me-2"></i><?php esc_html_e( 'Plugin Language', 'ipv-production-system-pro' ); ?></h5>
                    </div>
                    <div class="card-body">
                        <form id="ipv-language-form">
                            <?php wp_nonce_field( 'ipv_language_nonce', 'ipv_lang_nonce' ); ?>
                            
                            <div class="mb-4">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="ipv_language" id="lang_auto" value="auto" <?php checked( $current_locale, 'auto' ); ?>>
                                    <label class="form-check-label" for="lang_auto">
                                        <i class="bi bi-magic me-1"></i><?php esc_html_e( 'Auto (follow WordPress)', 'ipv-production-system-pro' ); ?>
                                        <span class="badge bg-secondary ms-2"><?php echo esc_html( $wp_locale ); ?></span>
                                    </label>
                                </div>
                                <hr class="my-3">
                                <?php foreach ( self::SUPPORTED_LANGUAGES as $locale => $name ) : 
                                    $has_translation = isset( $translations_status[ $locale ] ) && $translations_status[ $locale ]['exists'];
                                ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="ipv_language" 
                                               id="lang_<?php echo esc_attr( $locale ); ?>" 
                                               value="<?php echo esc_attr( $locale ); ?>" 
                                               <?php checked( $current_locale, $locale ); ?>
                                               <?php disabled( ! $has_translation && $locale !== 'en_US' ); ?>>
                                        <label class="form-check-label" for="lang_<?php echo esc_attr( $locale ); ?>">
                                            <?php echo esc_html( self::get_flag_emoji( $locale ) . ' ' . $name ); ?>
                                            <?php if ( $locale === 'en_US' ) : ?>
                                                <span class="badge bg-success ms-2">Default</span>
                                            <?php elseif ( $has_translation ) : ?>
                                                <span class="badge bg-<?php echo $translations_status[ $locale ]['percentage'] >= 80 ? 'success' : 'warning'; ?> ms-2">
                                                    <?php echo esc_html( $translations_status[ $locale ]['percentage'] ); ?>%
                                                </span>
                                            <?php endif; ?>
                                            <?php if ( $current_locale === $locale ) : ?>
                                                <span class="badge bg-primary ms-2">Active</span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="ipv-save-language">
                                <i class="bi bi-check-lg me-1"></i><?php esc_html_e( 'Save Language', 'ipv-production-system-pro' ); ?>
                            </button>
                        </form>
                        <div id="ipv-language-message" class="mt-3" style="display:none;"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-text text-info me-2"></i><?php esc_html_e( 'Translation Files', 'ipv-production-system-pro' ); ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr><th>Language</th><th>.po</th><th>.mo</th><th>Strings</th><th></th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ( self::SUPPORTED_LANGUAGES as $locale => $name ) : 
                                    if ( $locale === 'en_US' ) continue;
                                    $s = $translations_status[ $locale ] ?? [];
                                ?>
                                <tr>
                                    <td><?php echo esc_html( self::get_flag_emoji( $locale ) . ' ' . $name ); ?></td>
                                    <td><i class="bi bi-<?php echo ( $s['po_exists'] ?? false ) ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'; ?>"></i></td>
                                    <td><i class="bi bi-<?php echo ( $s['mo_exists'] ?? false ) ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'; ?>"></i></td>
                                    <td><?php echo isset( $s['translated'] ) ? '<span class="badge bg-' . ( $s['percentage'] >= 80 ? 'success' : 'warning' ) . '">' . $s['translated'] . '/' . $s['total'] . '</span>' : '—'; ?></td>
                                    <td>
                                        <?php if ( $s['po_exists'] ?? false ) : ?>
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-language&tab=editor&locale=' . $locale ) ); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#ipv-language-form').on('submit', function(e) {
                e.preventDefault();
                var $btn = $('#ipv-save-language').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
                $.post(ajaxurl, {
                    action: 'ipv_save_language',
                    nonce: $('#ipv_lang_nonce').val(),
                    language: $('input[name="ipv_language"]:checked').val()
                }, function(r) {
                    if (r.success) {
                        $('#ipv-language-message').removeClass('alert-danger').addClass('alert alert-success').html('<i class="bi bi-check-circle me-1"></i>' + r.data.message).fadeIn();
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        $('#ipv-language-message').addClass('alert alert-danger').html(r.data).fadeIn();
                    }
                    $btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i>Save Language');
                });
            });
        });
        </script>
        <?php
    }

    private static function render_editor_tab( $translations_status ) {
        $selected_locale = isset( $_GET['locale'] ) ? sanitize_key( $_GET['locale'] ) : 'it_IT';
        if ( ! array_key_exists( $selected_locale, self::SUPPORTED_LANGUAGES ) || $selected_locale === 'en_US' ) {
            $selected_locale = 'it_IT';
        }
        ?>
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="bi bi-pencil-square text-warning me-2"></i><?php esc_html_e( 'Translation Editor', 'ipv-production-system-pro' ); ?></h5>
                <div class="d-flex gap-2 align-items-center">
                    <select id="ipv-po-locale" class="form-select form-select-sm" style="width:auto;">
                        <?php foreach ( self::SUPPORTED_LANGUAGES as $locale => $name ) : 
                            if ( $locale === 'en_US' ) continue; ?>
                            <option value="<?php echo esc_attr( $locale ); ?>" <?php selected( $selected_locale, $locale ); ?>>
                                <?php echo esc_html( self::get_flag_emoji( $locale ) . ' ' . $name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-sm btn-primary" id="ipv-load-po">
                        <i class="bi bi-arrow-clockwise me-1"></i><?php esc_html_e( 'Load', 'ipv-production-system-pro' ); ?>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php wp_nonce_field( 'ipv_po_editor_nonce', 'ipv_po_nonce' ); ?>
                
                <div class="row mb-3">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="ipv-po-search" placeholder="<?php esc_attr_e( 'Search...', 'ipv-production-system-pro' ); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" id="ipv-po-filter">
                            <option value="all"><?php esc_html_e( 'All', 'ipv-production-system-pro' ); ?></option>
                            <option value="translated"><?php esc_html_e( 'Translated', 'ipv-production-system-pro' ); ?></option>
                            <option value="untranslated"><?php esc_html_e( 'Untranslated', 'ipv-production-system-pro' ); ?></option>
                        </select>
                    </div>
                    <div class="col-md-4 text-end"><span id="ipv-po-stats" class="text-muted small"></span></div>
                </div>

                <div class="table-responsive" style="max-height:450px;overflow-y:auto;">
                    <table class="table table-sm table-hover" id="ipv-po-table">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width:5%">#</th>
                                <th style="width:45%"><?php esc_html_e( 'Original', 'ipv-production-system-pro' ); ?></th>
                                <th style="width:45%"><?php esc_html_e( 'Translation', 'ipv-production-system-pro' ); ?></th>
                                <th style="width:5%"></th>
                            </tr>
                        </thead>
                        <tbody id="ipv-po-tbody">
                            <tr><td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-translate fs-1 d-block mb-2"></i>
                                <?php esc_html_e( 'Select language and click Load', 'ipv-production-system-pro' ); ?>
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="text-muted small" id="ipv-po-file-info"></span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success btn-sm" id="ipv-save-po" disabled>
                        <i class="bi bi-save me-1"></i><?php esc_html_e( 'Save .po', 'ipv-production-system-pro' ); ?>
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="ipv-compile-mo" disabled>
                        <i class="bi bi-gear me-1"></i><?php esc_html_e( 'Compile .mo', 'ipv-production-system-pro' ); ?>
                    </button>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var poData = [], currentLocale = '<?php echo esc_js( $selected_locale ); ?>', hasChanges = false;

            function loadPoFile(locale) {
                currentLocale = locale;
                $('#ipv-po-tbody').html('<tr><td colspan="4" class="text-center py-5"><span class="spinner-border"></span></td></tr>');
                $.post(ajaxurl, { action: 'ipv_load_po_file', nonce: $('#ipv_po_nonce').val(), locale: locale }, function(r) {
                    if (r.success) {
                        poData = r.data.entries;
                        renderTable();
                        $('#ipv-save-po, #ipv-compile-mo').prop('disabled', false);
                        $('#ipv-po-file-info').html('<i class="bi bi-file-earmark-text me-1"></i>' + r.data.filename);
                        hasChanges = false;
                    } else {
                        $('#ipv-po-tbody').html('<tr><td colspan="4" class="text-center py-5 text-danger">' + r.data + '</td></tr>');
                    }
                });
            }

            function renderTable() {
                var search = $('#ipv-po-search').val().toLowerCase(), filter = $('#ipv-po-filter').val(), html = '', count = 0;
                $.each(poData, function(i, e) {
                    var match = !search || e.msgid.toLowerCase().indexOf(search) !== -1 || (e.msgstr && e.msgstr.toLowerCase().indexOf(search) !== -1);
                    var filt = filter === 'all' || (filter === 'translated' && e.msgstr) || (filter === 'untranslated' && !e.msgstr);
                    if (match && filt) {
                        count++;
                        var cls = e.msgstr ? 'table-success' : 'table-warning';
                        html += '<tr class="' + cls + '"><td class="text-muted small">' + (i+1) + '</td>';
                        html += '<td><code class="small text-dark" style="word-break:break-all">' + $('<div>').text(e.msgid).html() + '</code></td>';
                        html += '<td><input type="text" class="form-control form-control-sm ipv-msgstr" data-index="' + i + '" value="' + $('<div>').text(e.msgstr||'').html() + '"></td>';
                        html += '<td><i class="bi bi-' + (e.msgstr ? 'check-circle-fill text-success' : 'circle text-warning') + '"></i></td></tr>';
                    }
                });
                $('#ipv-po-tbody').html(html || '<tr><td colspan="4" class="text-center py-4 text-muted">No strings found</td></tr>');
                var trans = poData.filter(function(e){return e.msgstr;}).length;
                $('#ipv-po-stats').text(count + '/' + poData.length + ' shown • ' + trans + ' translated (' + (poData.length ? Math.round(trans/poData.length*100) : 0) + '%)');
            }

            $('#ipv-load-po').on('click', function() { loadPoFile($('#ipv-po-locale').val()); });
            $('#ipv-po-locale').on('change', function() { window.location.href = '<?php echo admin_url( 'admin.php?page=ipv-production-language&tab=editor&locale=' ); ?>' + $(this).val(); });
            $('#ipv-po-search, #ipv-po-filter').on('input change', renderTable);

            $(document).on('input', '.ipv-msgstr', function() {
                var i = $(this).data('index');
                poData[i].msgstr = $(this).val();
                hasChanges = true;
                var $row = $(this).closest('tr');
                $row.toggleClass('table-success', !!$(this).val()).toggleClass('table-warning', !$(this).val());
                $row.find('td:last i').toggleClass('bi-check-circle-fill text-success', !!$(this).val()).toggleClass('bi-circle text-warning', !$(this).val());
                var trans = poData.filter(function(e){return e.msgstr;}).length;
                $('#ipv-po-stats').text($('#ipv-po-tbody tr').length + '/' + poData.length + ' shown • ' + trans + ' translated (' + Math.round(trans/poData.length*100) + '%)');
            });

            $('#ipv-save-po').on('click', function() {
                var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                $.post(ajaxurl, { action: 'ipv_save_po_file', nonce: $('#ipv_po_nonce').val(), locale: currentLocale, entries: JSON.stringify(poData) }, function(r) {
                    alert(r.success ? 'Saved!' : 'Error: ' + r.data);
                    hasChanges = false;
                    $btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Save .po');
                });
            });

            $('#ipv-compile-mo').on('click', function() {
                var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                $.post(ajaxurl, { action: 'ipv_compile_mo', nonce: $('#ipv_po_nonce').val(), locale: currentLocale }, function(r) {
                    alert(r.success ? 'Compiled! Reload page to see changes.' : 'Error: ' + r.data);
                    $btn.prop('disabled', false).html('<i class="bi bi-gear me-1"></i>Compile .mo');
                });
            });

            $(window).on('beforeunload', function() { if (hasChanges) return 'Unsaved changes!'; });
            <?php if ( isset( $_GET['locale'] ) ) : ?>loadPoFile('<?php echo esc_js( $selected_locale ); ?>');<?php endif; ?>
        });
        </script>
        <?php
    }

    // AJAX Handlers
    public static function ajax_save_language() {
        check_ajax_referer( 'ipv_language_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $lang = sanitize_text_field( $_POST['language'] ?? 'auto' );
        if ( $lang !== 'auto' && ! array_key_exists( $lang, self::SUPPORTED_LANGUAGES ) ) wp_send_json_error( 'Invalid' );
        update_option( self::OPTION_KEY, $lang );
        wp_send_json_success( [ 'message' => __( 'Saved! Reloading...', 'ipv-production-system-pro' ) ] );
    }

    public static function ajax_load_po_file() {
        check_ajax_referer( 'ipv_po_editor_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        // Non usare sanitize_key che converte in lowercase
        $locale = preg_replace( '/[^a-zA-Z0-9_]/', '', $_POST['locale'] ?? '' );
        if ( ! array_key_exists( $locale, self::SUPPORTED_LANGUAGES ) ) {
            wp_send_json_error( 'Invalid locale: ' . $locale );
        }
        $po_file = IPV_PROD_PLUGIN_DIR . 'languages/ipv-production-system-pro-' . $locale . '.po';
        if ( ! file_exists( $po_file ) ) {
            wp_send_json_error( 'File not found: ' . $po_file );
        }
        wp_send_json_success( [ 'entries' => self::parse_po_file( $po_file ), 'filename' => basename( $po_file ) ] );
    }

    public static function ajax_save_po_file() {
        check_ajax_referer( 'ipv_po_editor_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $locale = preg_replace( '/[^a-zA-Z0-9_]/', '', $_POST['locale'] ?? '' );
        $entries = json_decode( stripslashes( $_POST['entries'] ?? '[]' ), true );
        if ( ! array_key_exists( $locale, self::SUPPORTED_LANGUAGES ) ) {
            wp_send_json_error( 'Invalid locale' );
        }
        $po_file = IPV_PROD_PLUGIN_DIR . 'languages/ipv-production-system-pro-' . $locale . '.po';
        
        $original = file_get_contents( $po_file );
        preg_match( '/^(.*?)\n\nmsgid/s', $original, $m );
        $header = $m[1] ?? '';
        
        $content = $header . "\n\n";
        foreach ( $entries as $e ) {
            $content .= 'msgid "' . addcslashes( $e['msgid'], '"\\' ) . "\"\n";
            $content .= 'msgstr "' . addcslashes( $e['msgstr'] ?? '', '"\\' ) . "\"\n\n";
        }
        
        if ( file_put_contents( $po_file, $content ) === false ) wp_send_json_error( 'Write failed' );
        wp_send_json_success();
    }

    public static function ajax_compile_mo() {
        check_ajax_referer( 'ipv_po_editor_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $locale = preg_replace( '/[^a-zA-Z0-9_]/', '', $_POST['locale'] ?? '' );
        if ( ! array_key_exists( $locale, self::SUPPORTED_LANGUAGES ) ) {
            wp_send_json_error( 'Invalid locale' );
        }
        $po_file = IPV_PROD_PLUGIN_DIR . 'languages/ipv-production-system-pro-' . $locale . '.po';
        $mo_file = IPV_PROD_PLUGIN_DIR . 'languages/ipv-production-system-pro-' . $locale . '.mo';
        if ( ! file_exists( $po_file ) ) wp_send_json_error( 'PO not found' );
        
        $entries = self::parse_po_file( $po_file );
        $mo = self::compile_mo( $entries );
        if ( file_put_contents( $mo_file, $mo ) === false ) wp_send_json_error( 'Compile failed' );
        wp_send_json_success();
    }

    private static function parse_po_file( $file ) {
        $content = file_get_contents( $file );
        $entries = [];
        preg_match_all( '/msgid\s+"(.*)"\s*\nmsgstr\s+"(.*)"/m', $content, $matches, PREG_SET_ORDER );
        foreach ( $matches as $m ) {
            if ( empty( $m[1] ) ) continue;
            $entries[] = [ 'msgid' => stripcslashes( $m[1] ), 'msgstr' => stripcslashes( $m[2] ) ];
        }
        return $entries;
    }

    private static function compile_mo( $entries ) {
        $offsets = []; $ids = ''; $strs = '';
        foreach ( $entries as $e ) {
            $id = $e['msgid']; $str = $e['msgstr'] ?: $id;
            $offsets[] = [ strlen($ids), strlen($id), strlen($strs), strlen($str) ];
            $ids .= $id . "\0"; $strs .= $str . "\0";
        }
        $n = count($offsets);
        $o_off = 28; $t_off = $o_off + $n * 8;
        $s_off = $t_off + $n * 8;
        
        $out = pack('V', 0x950412de) . pack('V', 0) . pack('V', $n);
        $out .= pack('V', $o_off) . pack('V', $t_off) . pack('V', 0) . pack('V', 0);
        
        $pos = $s_off;
        foreach ($offsets as $o) { $out .= pack('VV', $o[1], $pos + $o[0]); }
        $pos += strlen($ids);
        foreach ($offsets as $o) { $out .= pack('VV', $o[3], $pos + $o[2]); }
        
        return $out . $ids . $strs;
    }

    public static function check_all_translations() {
        $status = [];
        foreach ( self::SUPPORTED_LANGUAGES as $locale => $name ) {
            if ( $locale === 'en_US' ) { $status[$locale] = ['exists'=>true,'po_exists'=>true,'mo_exists'=>true,'percentage'=>100]; continue; }
            $po = IPV_PROD_PLUGIN_DIR . 'languages/ipv-production-system-pro-' . $locale . '.po';
            $mo = IPV_PROD_PLUGIN_DIR . 'languages/ipv-production-system-pro-' . $locale . '.mo';
            $po_exists = file_exists($po); $mo_exists = file_exists($mo);
            $entries = $po_exists ? self::parse_po_file($po) : [];
            $total = count($entries);
            $trans = count(array_filter($entries, function($e){return !empty($e['msgstr']);}));
            $status[$locale] = [
                'exists' => $mo_exists, 'po_exists' => $po_exists, 'mo_exists' => $mo_exists,
                'percentage' => $total ? round($trans/$total*100) : 0, 'translated' => $trans, 'total' => $total
            ];
        }
        return $status;
    }

    public static function get_flag_emoji( $locale ) {
        return ['en_US'=>'🇺🇸','it_IT'=>'🇮🇹','de_DE'=>'🇩🇪','es_ES'=>'🇪🇸','fr_FR'=>'🇫🇷','pt_PT'=>'🇵🇹','ru_RU'=>'🇷🇺'][$locale] ?? '🌐';
    }
}

IPV_Prod_Language_Manager::init();
