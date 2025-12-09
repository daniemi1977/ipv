<?php
/**
 * IPV Remote Updates Server
 *
 * Gestisce il sistema di update automatici per il plugin client
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Remote_Updates_Server {

    private static $instance = null;

    // Path dove vengono caricati i file .zip delle versioni
    const UPDATES_DIR = 'ipv-pro-updates';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
        $this->ensure_updates_directory();
    }

    private function init_hooks() {
        // Admin menu per gestire updates
        add_action( 'admin_menu', [ $this, 'add_updates_menu' ] );

        // Handle file upload
        add_action( 'admin_post_ipv_upload_update', [ $this, 'handle_update_upload' ] );

        // Download endpoint (con license validation)
        add_action( 'init', [ $this, 'handle_download_request' ] );

        // AJAX delete version
        add_action( 'wp_ajax_ipv_delete_version', [ $this, 'ajax_delete_version' ] );
    }

    /**
     * Ensure updates directory exists
     */
    private function ensure_updates_directory() {
        $upload_dir = wp_upload_dir();
        $updates_path = $upload_dir['basedir'] . '/' . self::UPDATES_DIR;

        if ( ! file_exists( $updates_path ) ) {
            wp_mkdir_p( $updates_path );

            // Add .htaccess to protect direct access
            $htaccess = $updates_path . '/.htaccess';
            file_put_contents( $htaccess, "deny from all\n" );

            // Add index.php
            $index = $updates_path . '/index.php';
            file_put_contents( $index, "<?php // Silence is golden\n" );
        }
    }

    /**
     * Get current version info (latest)
     */
    public function get_current_version_info() {
        $versions = $this->get_all_versions();

        if ( empty( $versions ) ) {
            return false;
        }

        // Get latest version
        usort( $versions, function( $a, $b ) {
            return version_compare( $b['version'], $a['version'] );
        });

        return $versions[0];
    }

    /**
     * Get all versions
     */
    public function get_all_versions() {
        $upload_dir = wp_upload_dir();
        $updates_path = $upload_dir['basedir'] . '/' . self::UPDATES_DIR;

        if ( ! is_dir( $updates_path ) ) {
            return [];
        }

        $versions = [];
        $files = glob( $updates_path . '/ipv-production-system-pro-v*.zip' );

        foreach ( $files as $file ) {
            // Extract version from filename
            if ( preg_match( '/v(\d+\.\d+\.\d+)/', basename( $file ), $matches ) ) {
                $version = $matches[1];

                // Get changelog if exists
                $changelog = get_option( 'ipv_changelog_' . str_replace( '.', '_', $version ), '' );

                $versions[] = [
                    'version' => $version,
                    'file' => basename( $file ),
                    'size' => size_format( filesize( $file ) ),
                    'size_bytes' => filesize( $file ),
                    'date' => date_i18n( 'd/m/Y H:i', filemtime( $file ) ),
                    'timestamp' => filemtime( $file ),
                    'download_url' => home_url( '/?download-ipv-pro=1&version=' . $version ),
                    'changelog' => $changelog
                ];
            }
        }

        return $versions;
    }

    /**
     * Add admin menu
     */
    public function add_updates_menu() {
        add_submenu_page(
            'ipv-vendor-dashboard',
            __( 'Updates', 'ipv-pro-vendor' ),
            __( 'Updates', 'ipv-pro-vendor' ),
            'manage_options',
            'ipv-vendor-updates',
            [ $this, 'render_updates_page' ]
        );
    }

    /**
     * Render updates admin page
     */
    public function render_updates_page() {
        if ( isset( $_GET['uploaded'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>Versione caricata con successo!</p></div>';
        }
        if ( isset( $_GET['deleted'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>Versione eliminata con successo!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1><?php _e( 'IPV Pro - Remote Updates', 'ipv-pro-vendor' ); ?></h1>

            <div class="card" style="max-width: 800px;">
                <h2><?php _e( '📤 Upload Nuova Versione', 'ipv-pro-vendor' ); ?></h2>
                <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'ipv_upload_update' ); ?>
                    <input type="hidden" name="action" value="ipv_upload_update">

                    <table class="form-table">
                        <tr>
                            <th><?php _e( 'File ZIP', 'ipv-pro-vendor' ); ?></th>
                            <td>
                                <input type="file" name="update_file" accept=".zip" required>
                                <p class="description">
                                    <?php _e( 'Carica il file .zip del plugin client (es: ipv-production-system-pro-v10.0.0.zip)', 'ipv-pro-vendor' ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e( 'Versione', 'ipv-pro-vendor' ); ?></th>
                            <td>
                                <input type="text" name="version" placeholder="10.0.0" pattern="\d+\.\d+\.\d+" required style="width: 200px;">
                                <p class="description">
                                    <?php _e( 'Formato: X.Y.Z (es: 10.0.0)', 'ipv-pro-vendor' ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e( 'Changelog', 'ipv-pro-vendor' ); ?></th>
                            <td>
                                <textarea name="changelog" rows="6" class="large-text" placeholder="Es:&#10;- New: Feature X&#10;- Fix: Bug Y&#10;- Improved: Performance Z"></textarea>
                                <p class="description">
                                    <?php _e( 'Note di rilascio (opzionale)', 'ipv-pro-vendor' ); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">
                            <?php _e( '📤 Carica Versione', 'ipv-pro-vendor' ); ?>
                        </button>
                    </p>
                </form>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h2><?php _e( '📦 Versioni Disponibili', 'ipv-pro-vendor' ); ?></h2>

                <?php
                $versions = $this->get_all_versions();

                if ( empty( $versions ) ) {
                    echo '<p>' . __( 'Nessuna versione caricata', 'ipv-pro-vendor' ) . '</p>';
                } else {
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr>';
                    echo '<th>' . __( 'Versione', 'ipv-pro-vendor' ) . '</th>';
                    echo '<th>' . __( 'File', 'ipv-pro-vendor' ) . '</th>';
                    echo '<th>' . __( 'Dimensione', 'ipv-pro-vendor' ) . '</th>';
                    echo '<th>' . __( 'Data Upload', 'ipv-pro-vendor' ) . '</th>';
                    echo '<th>' . __( 'Changelog', 'ipv-pro-vendor' ) . '</th>';
                    echo '<th>' . __( 'Azioni', 'ipv-pro-vendor' ) . '</th>';
                    echo '</tr></thead>';
                    echo '<tbody>';

                    foreach ( $versions as $version ) {
                        $is_latest = ( $version === $versions[0] );

                        echo '<tr>';
                        echo '<td>';
                        echo '<strong>v' . esc_html( $version['version'] ) . '</strong>';
                        if ( $is_latest ) {
                            echo ' <span class="dashicons dashicons-yes-alt" style="color: #46b450;" title="Latest"></span>';
                        }
                        echo '</td>';
                        echo '<td><code>' . esc_html( $version['file'] ) . '</code></td>';
                        echo '<td>' . esc_html( $version['size'] ) . '</td>';
                        echo '<td>' . esc_html( $version['date'] ) . '</td>';
                        echo '<td>';
                        if ( ! empty( $version['changelog'] ) ) {
                            echo '<details><summary>Vedi changelog</summary><pre style="white-space: pre-wrap; margin-top: 10px; font-size: 12px;">' . esc_html( $version['changelog'] ) . '</pre></details>';
                        } else {
                            echo '—';
                        }
                        echo '</td>';
                        echo '<td>';
                        echo '<a href="' . esc_url( $version['download_url'] ) . '" class="button button-small">Download</a> ';
                        echo '<button type="button" class="button button-small button-link-delete ipv-delete-version" data-version="' . esc_attr( $version['version'] ) . '">Elimina</button>';
                        echo '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody></table>';
                }
                ?>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h2><?php _e( '🔗 Info API Update', 'ipv-pro-vendor' ); ?></h2>
                <p><strong>Update Check URL:</strong></p>
                <code style="display: block; background: #f5f5f5; padding: 10px; border-radius: 3px;"><?php echo esc_url( rest_url( 'ipv-vendor/v1/plugin-info' ) ); ?></code>

                <?php
                $current = $this->get_current_version_info();
                if ( $current ) {
                    echo '<h3 style="margin-top: 20px;">' . __( '📌 Versione Corrente (Latest)', 'ipv-pro-vendor' ) . '</h3>';
                    echo '<p><strong>v' . esc_html( $current['version'] ) . '</strong> - ' . esc_html( $current['size'] ) . ' - ' . esc_html( $current['date'] ) . '</p>';
                }
                ?>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.ipv-delete-version').on('click', function() {
                var version = $(this).data('version');
                if ( ! confirm( 'Sei sicuro di voler eliminare la versione ' + version + '?' ) ) {
                    return;
                }

                $.post(ajaxurl, {
                    action: 'ipv_delete_version',
                    version: version,
                    nonce: '<?php echo wp_create_nonce( 'ipv_delete_version' ); ?>'
                }, function(response) {
                    if ( response.success ) {
                        location.reload();
                    } else {
                        alert( 'Errore: ' + response.data );
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Handle update upload
     */
    public function handle_update_upload() {
        check_admin_referer( 'ipv_upload_update' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Non autorizzato' );
        }

        if ( empty( $_FILES['update_file'] ) ) {
            wp_die( 'Nessun file caricato' );
        }

        $version = sanitize_text_field( $_POST['version'] );
        $changelog = sanitize_textarea_field( $_POST['changelog'] );

        // Validate version format
        if ( ! preg_match( '/^\d+\.\d+\.\d+$/', $version ) ) {
            wp_die( 'Formato versione non valido. Usa X.Y.Z (es: 10.0.0)' );
        }

        $file = $_FILES['update_file'];

        // Check if ZIP
        $finfo = finfo_open( FILEINFO_MIME_TYPE );
        $mime = finfo_file( $finfo, $file['tmp_name'] );
        finfo_close( $finfo );

        if ( ! in_array( $mime, [ 'application/zip', 'application/x-zip-compressed' ] ) ) {
            wp_die( 'Il file deve essere un .zip' );
        }

        $upload_dir = wp_upload_dir();
        $updates_path = $upload_dir['basedir'] . '/' . self::UPDATES_DIR;

        // Filename: ipv-production-system-pro-vX.Y.Z.zip
        $filename = 'ipv-production-system-pro-v' . $version . '.zip';
        $destination = $updates_path . '/' . $filename;

        // Check if version already exists
        if ( file_exists( $destination ) ) {
            wp_die( 'Versione ' . $version . ' già esistente. Elimina prima la versione precedente o usa un numero di versione diverso.' );
        }

        // Move file
        if ( ! move_uploaded_file( $file['tmp_name'], $destination ) ) {
            wp_die( 'Errore durante il caricamento del file' );
        }

        // Save changelog if provided
        if ( ! empty( $changelog ) ) {
            update_option( 'ipv_changelog_' . str_replace( '.', '_', $version ), $changelog );
        }

        // Redirect back
        wp_redirect( admin_url( 'admin.php?page=ipv-vendor-updates&uploaded=1' ) );
        exit;
    }

    /**
     * AJAX delete version
     */
    public function ajax_delete_version() {
        check_ajax_referer( 'ipv_delete_version', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Non autorizzato' );
        }

        $version = sanitize_text_field( $_POST['version'] );

        if ( ! preg_match( '/^\d+\.\d+\.\d+$/', $version ) ) {
            wp_send_json_error( 'Versione non valida' );
        }

        $upload_dir = wp_upload_dir();
        $updates_path = $upload_dir['basedir'] . '/' . self::UPDATES_DIR;
        $filename = 'ipv-production-system-pro-v' . $version . '.zip';
        $file_path = $updates_path . '/' . $filename;

        if ( ! file_exists( $file_path ) ) {
            wp_send_json_error( 'File non trovato' );
        }

        if ( ! unlink( $file_path ) ) {
            wp_send_json_error( 'Errore durante l\'eliminazione del file' );
        }

        // Delete changelog
        delete_option( 'ipv_changelog_' . str_replace( '.', '_', $version ) );

        wp_send_json_success();
    }

    /**
     * Handle download request
     */
    public function handle_download_request() {
        if ( ! isset( $_GET['download-ipv-pro'] ) ) {
            return;
        }

        // Get license from query
        $license_key = isset( $_GET['license'] ) ? sanitize_text_field( $_GET['license'] ) : '';
        $version = isset( $_GET['version'] ) ? sanitize_text_field( $_GET['version'] ) : '';

        // Validate nonce
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'download_ipv_pro' ) ) {
            wp_die( 'Nonce non valido' );
        }

        // Validate license
        $license_manager = IPV_Vendor_License_Manager::instance();
        $license = $license_manager->validate_license( $license_key );

        if ( is_wp_error( $license ) ) {
            wp_die( 'License non valida: ' . $license->get_error_message() );
        }

        // Get file
        $upload_dir = wp_upload_dir();
        $updates_path = $upload_dir['basedir'] . '/' . self::UPDATES_DIR;

        if ( empty( $version ) ) {
            // Get latest version
            $latest = $this->get_current_version_info();
            if ( ! $latest ) {
                wp_die( 'Nessuna versione disponibile' );
            }
            $version = $latest['version'];
        }

        $filename = 'ipv-production-system-pro-v' . $version . '.zip';
        $file_path = $updates_path . '/' . $filename;

        if ( ! file_exists( $file_path ) ) {
            wp_die( 'File non trovato: ' . $filename );
        }

        // Log download
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'ipv_api_logs',
            [
                'license_id' => $license->id,
                'endpoint' => 'download',
                'video_id' => 'v' . $version,
                'method' => 'GET',
                'status_code' => 200,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255 )
            ],
            [ '%d', '%s', '%s', '%s', '%d', '%s', '%s' ]
        );

        // Force download
        header( 'Content-Type: application/zip' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . filesize( $file_path ) );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        readfile( $file_path );
        exit;
    }
}
