<?php
/**
 * IPV Pro Vendor - License Backup & Export System
 *
 * Sistema completo per backup, export e import delle licenze
 *
 * @package IPV_Pro_Vendor
 * @version 1.0.21
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Vendor_License_Backup {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        add_action( 'admin_menu', [ $this, 'add_menu' ], 99 );
        add_action( 'admin_init', [ $this, 'handle_actions' ] );
        add_action( 'ipv_vendor_daily_backup', [ $this, 'auto_backup' ] );
        
        // Schedule daily backup if not already scheduled
        if ( ! wp_next_scheduled( 'ipv_vendor_daily_backup' ) ) {
            wp_schedule_event( time(), 'daily', 'ipv_vendor_daily_backup' );
        }
    }

    /**
     * Add submenu page
     */
    public function add_menu() {
        add_submenu_page(
            'ipv-vendor',
            '💾 Backup Licenze',
            '💾 Backup Licenze',
            'manage_options',
            'ipv-license-backup',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Handle export/import actions
     */
    public function handle_actions() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Export JSON
        if ( isset( $_GET['ipv_export'] ) && $_GET['ipv_export'] === 'json' && wp_verify_nonce( $_GET['_wpnonce'], 'ipv_export_licenses' ) ) {
            $this->export_json();
        }

        // Export CSV
        if ( isset( $_GET['ipv_export'] ) && $_GET['ipv_export'] === 'csv' && wp_verify_nonce( $_GET['_wpnonce'], 'ipv_export_licenses' ) ) {
            $this->export_csv();
        }

        // Import
        if ( isset( $_POST['ipv_import_licenses'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ipv_import_licenses' ) ) {
            $this->import_licenses();
        }

        // Manual backup
        if ( isset( $_GET['ipv_manual_backup'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'ipv_manual_backup' ) ) {
            $this->create_backup();
            wp_redirect( admin_url( 'admin.php?page=ipv-license-backup&backup_created=1' ) );
            exit;
        }

        // Restore backup
        if ( isset( $_POST['ipv_restore_backup'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ipv_restore_backup' ) ) {
            $this->restore_backup( sanitize_text_field( $_POST['backup_file'] ) );
        }

        // Delete backup
        if ( isset( $_GET['ipv_delete_backup'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'ipv_delete_backup' ) ) {
            $this->delete_backup( sanitize_text_field( $_GET['ipv_delete_backup'] ) );
            wp_redirect( admin_url( 'admin.php?page=ipv-license-backup&backup_deleted=1' ) );
            exit;
        }
    }

    /**
     * Export licenses as JSON
     */
    private function export_json() {
        global $wpdb;

        $licenses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ipv_licenses ORDER BY created_at DESC", ARRAY_A );
        $activations = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ipv_activations ORDER BY activated_at DESC", ARRAY_A );

        $export_data = [
            'export_date' => current_time( 'mysql' ),
            'export_version' => '1.0.21',
            'site_url' => home_url(),
            'licenses_count' => count( $licenses ),
            'activations_count' => count( $activations ),
            'licenses' => $licenses,
            'activations' => $activations
        ];

        $filename = 'ipv-licenses-backup-' . date( 'Y-m-d-His' ) . '.json';

        header( 'Content-Type: application/json' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        echo json_encode( $export_data, JSON_PRETTY_PRINT );
        exit;
    }

    /**
     * Export licenses as CSV
     */
    private function export_csv() {
        global $wpdb;

        $licenses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ipv_licenses ORDER BY created_at DESC", ARRAY_A );

        $filename = 'ipv-licenses-' . date( 'Y-m-d-His' ) . '.csv';

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $output = fopen( 'php://output', 'w' );

        // UTF-8 BOM for Excel
        fprintf( $output, chr(0xEF) . chr(0xBB) . chr(0xBF) );

        // Header row
        if ( ! empty( $licenses ) ) {
            fputcsv( $output, array_keys( $licenses[0] ) );
        }

        // Data rows
        foreach ( $licenses as $license ) {
            fputcsv( $output, $license );
        }

        fclose( $output );
        exit;
    }

    /**
     * Import licenses from JSON
     */
    private function import_licenses() {
        global $wpdb;

        if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
            add_settings_error( 'ipv_backup', 'no_file', '❌ Nessun file selezionato', 'error' );
            return;
        }

        $file_content = file_get_contents( $_FILES['import_file']['tmp_name'] );
        $import_data = json_decode( $file_content, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            add_settings_error( 'ipv_backup', 'invalid_json', '❌ File JSON non valido', 'error' );
            return;
        }

        if ( empty( $import_data['licenses'] ) ) {
            add_settings_error( 'ipv_backup', 'no_licenses', '❌ Nessuna licenza trovata nel file', 'error' );
            return;
        }

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        $mode = sanitize_text_field( $_POST['import_mode'] ?? 'skip' );

        foreach ( $import_data['licenses'] as $license ) {
            // Check if license already exists
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}ipv_licenses WHERE license_key = %s",
                $license['license_key']
            ));

            if ( $existing ) {
                if ( $mode === 'skip' ) {
                    $skipped++;
                    continue;
                } elseif ( $mode === 'update' ) {
                    // Update existing
                    unset( $license['id'] );
                    $result = $wpdb->update(
                        $wpdb->prefix . 'ipv_licenses',
                        $license,
                        [ 'license_key' => $license['license_key'] ]
                    );
                    if ( $result !== false ) {
                        $imported++;
                    } else {
                        $errors++;
                    }
                    continue;
                }
            }

            // Insert new license
            unset( $license['id'] ); // Remove ID to auto-increment
            $result = $wpdb->insert( $wpdb->prefix . 'ipv_licenses', $license );

            if ( $result ) {
                $imported++;
            } else {
                $errors++;
            }
        }

        // Import activations if present and requested
        if ( ! empty( $import_data['activations'] ) && ! empty( $_POST['import_activations'] ) ) {
            foreach ( $import_data['activations'] as $activation ) {
                // Find license ID by key
                $license_id = $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}ipv_licenses WHERE license_key = (
                        SELECT license_key FROM {$wpdb->prefix}ipv_licenses WHERE id = %d LIMIT 1
                    )",
                    $activation['license_id']
                ));

                if ( $license_id ) {
                    unset( $activation['id'] );
                    $activation['license_id'] = $license_id;
                    $wpdb->insert( $wpdb->prefix . 'ipv_activations', $activation );
                }
            }
        }

        add_settings_error( 
            'ipv_backup', 
            'import_complete', 
            sprintf( '✅ Import completato: %d importate, %d saltate, %d errori', $imported, $skipped, $errors ),
            $errors > 0 ? 'warning' : 'success'
        );
    }

    /**
     * Create automatic backup
     */
    public function auto_backup() {
        $this->create_backup( 'auto' );
    }

    /**
     * Create backup file
     */
    public function create_backup( $type = 'manual' ) {
        global $wpdb;

        $backup_dir = $this->get_backup_dir();
        
        if ( ! file_exists( $backup_dir ) ) {
            wp_mkdir_p( $backup_dir );
            file_put_contents( $backup_dir . '.htaccess', 'deny from all' );
            file_put_contents( $backup_dir . 'index.php', '<?php // Silence is golden' );
        }

        $licenses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ipv_licenses", ARRAY_A );
        $activations = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ipv_activations", ARRAY_A );
        $ledger = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ipv_credit_ledger", ARRAY_A );

        $backup_data = [
            'backup_type' => $type,
            'backup_date' => current_time( 'mysql' ),
            'backup_version' => '1.0.21',
            'site_url' => home_url(),
            'licenses' => $licenses,
            'activations' => $activations,
            'credit_ledger' => $ledger
        ];

        $filename = sprintf( 'backup-%s-%s.json', $type, date( 'Y-m-d-His' ) );
        $filepath = $backup_dir . $filename;

        file_put_contents( $filepath, json_encode( $backup_data, JSON_PRETTY_PRINT ) );

        // Keep only last 30 backups
        $this->cleanup_old_backups( 30 );

        // Log backup
        error_log( sprintf( '[IPV Vendor] Backup created: %s (%d licenses)', $filename, count( $licenses ) ) );

        return $filepath;
    }

    /**
     * Restore from backup
     */
    private function restore_backup( $filename ) {
        global $wpdb;

        $backup_dir = $this->get_backup_dir();
        $filepath = $backup_dir . basename( $filename );

        if ( ! file_exists( $filepath ) ) {
            add_settings_error( 'ipv_backup', 'file_not_found', '❌ File backup non trovato', 'error' );
            return;
        }

        $backup_data = json_decode( file_get_contents( $filepath ), true );

        if ( json_last_error() !== JSON_ERROR_NONE || empty( $backup_data['licenses'] ) ) {
            add_settings_error( 'ipv_backup', 'invalid_backup', '❌ File backup non valido', 'error' );
            return;
        }

        // Create a backup before restoring
        $this->create_backup( 'pre-restore' );

        $restored = 0;

        foreach ( $backup_data['licenses'] as $license ) {
            // Check if exists
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}ipv_licenses WHERE license_key = %s",
                $license['license_key']
            ));

            if ( $existing ) {
                // Update
                unset( $license['id'] );
                $wpdb->update(
                    $wpdb->prefix . 'ipv_licenses',
                    $license,
                    [ 'license_key' => $license['license_key'] ]
                );
            } else {
                // Insert
                unset( $license['id'] );
                $wpdb->insert( $wpdb->prefix . 'ipv_licenses', $license );
            }
            $restored++;
        }

        add_settings_error( 
            'ipv_backup', 
            'restore_complete', 
            sprintf( '✅ Restore completato: %d licenze ripristinate', $restored ),
            'success'
        );
    }

    /**
     * Delete a backup file
     */
    private function delete_backup( $filename ) {
        $backup_dir = $this->get_backup_dir();
        $filepath = $backup_dir . basename( $filename );

        if ( file_exists( $filepath ) ) {
            unlink( $filepath );
        }
    }

    /**
     * Get list of backup files
     */
    private function get_backups() {
        $backup_dir = $this->get_backup_dir();
        $backups = [];

        if ( ! file_exists( $backup_dir ) ) {
            return $backups;
        }

        $files = glob( $backup_dir . 'backup-*.json' );
        
        foreach ( $files as $file ) {
            $data = json_decode( file_get_contents( $file ), true );
            $backups[] = [
                'filename' => basename( $file ),
                'filepath' => $file,
                'size' => filesize( $file ),
                'date' => $data['backup_date'] ?? date( 'Y-m-d H:i:s', filemtime( $file ) ),
                'type' => $data['backup_type'] ?? 'unknown',
                'licenses_count' => count( $data['licenses'] ?? [] )
            ];
        }

        // Sort by date descending
        usort( $backups, function( $a, $b ) {
            return strtotime( $b['date'] ) - strtotime( $a['date'] );
        });

        return $backups;
    }

    /**
     * Cleanup old backups
     */
    private function cleanup_old_backups( $keep = 30 ) {
        $backups = $this->get_backups();

        if ( count( $backups ) <= $keep ) {
            return;
        }

        // Remove oldest backups
        $to_delete = array_slice( $backups, $keep );
        foreach ( $to_delete as $backup ) {
            if ( file_exists( $backup['filepath'] ) ) {
                unlink( $backup['filepath'] );
            }
        }
    }

    /**
     * Get backup directory
     */
    private function get_backup_dir() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/ipv-backups/';
    }

    /**
     * Get license statistics
     */
    private function get_stats() {
        global $wpdb;

        return [
            'total' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_licenses" ),
            'active' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_licenses WHERE status = 'active'" ),
            'expired' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_licenses WHERE status = 'expired'" ),
            'revoked' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_licenses WHERE status = 'revoked'" ),
            'activations' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_activations WHERE is_active = 1" ),
        ];
    }

    /**
     * Render admin page
     */
    public function render_page() {
        $stats = $this->get_stats();
        $backups = $this->get_backups();
        ?>
        <div class="wrap">
            <h1>💾 Backup & Export Licenze</h1>

            <?php settings_errors( 'ipv_backup' ); ?>

            <?php if ( isset( $_GET['backup_created'] ) ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p>✅ Backup creato con successo!</p>
                </div>
            <?php endif; ?>

            <?php if ( isset( $_GET['backup_deleted'] ) ) : ?>
                <div class="notice notice-info is-dismissible">
                    <p>🗑️ Backup eliminato.</p>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="card" style="max-width: 100%; margin-bottom: 20px;">
                <h2>📊 Statistiche Licenze</h2>
                <table class="widefat" style="max-width: 400px;">
                    <tr><td><strong>Totale Licenze</strong></td><td><?php echo $stats['total']; ?></td></tr>
                    <tr><td>✅ Attive</td><td><?php echo $stats['active']; ?></td></tr>
                    <tr><td>⏰ Scadute</td><td><?php echo $stats['expired']; ?></td></tr>
                    <tr><td>🚫 Revocate</td><td><?php echo $stats['revoked']; ?></td></tr>
                    <tr><td>🌐 Attivazioni</td><td><?php echo $stats['activations']; ?></td></tr>
                </table>
            </div>

            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <!-- Export -->
                <div class="card" style="flex: 1; min-width: 300px;">
                    <h2>📤 Esporta Licenze</h2>
                    <p>Scarica un backup delle tue licenze.</p>
                    
                    <p>
                        <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=ipv-license-backup&ipv_export=json' ), 'ipv_export_licenses' ); ?>" class="button button-primary">
                            📥 Esporta JSON (Completo)
                        </a>
                    </p>
                    <p>
                        <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=ipv-license-backup&ipv_export=csv' ), 'ipv_export_licenses' ); ?>" class="button">
                            📊 Esporta CSV (Excel)
                        </a>
                    </p>
                    <p class="description">
                        JSON include licenze + attivazioni + è importabile.<br>
                        CSV è solo per visualizzazione in Excel.
                    </p>
                </div>

                <!-- Import -->
                <div class="card" style="flex: 1; min-width: 300px;">
                    <h2>📥 Importa Licenze</h2>
                    <p>Ripristina licenze da un file JSON esportato.</p>
                    
                    <form method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field( 'ipv_import_licenses' ); ?>
                        
                        <p>
                            <input type="file" name="import_file" accept=".json" required>
                        </p>
                        
                        <p>
                            <label>
                                <input type="radio" name="import_mode" value="skip" checked>
                                Salta licenze esistenti
                            </label><br>
                            <label>
                                <input type="radio" name="import_mode" value="update">
                                Aggiorna licenze esistenti
                            </label>
                        </p>
                        
                        <p>
                            <label>
                                <input type="checkbox" name="import_activations" value="1">
                                Importa anche attivazioni
                            </label>
                        </p>
                        
                        <p>
                            <button type="submit" name="ipv_import_licenses" class="button button-primary">
                                📤 Importa
                            </button>
                        </p>
                    </form>
                </div>

                <!-- Manual Backup -->
                <div class="card" style="flex: 1; min-width: 300px;">
                    <h2>🔄 Backup Automatici</h2>
                    <p>Il sistema crea automaticamente un backup giornaliero.</p>
                    
                    <p>
                        <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=ipv-license-backup&ipv_manual_backup=1' ), 'ipv_manual_backup' ); ?>" class="button button-primary">
                            💾 Crea Backup Ora
                        </a>
                    </p>
                    
                    <p class="description">
                        I backup vengono salvati in:<br>
                        <code>/wp-content/uploads/ipv-backups/</code><br>
                        Ultimi 30 backup mantenuti.
                    </p>
                </div>
            </div>

            <!-- Backup List -->
            <div class="card" style="margin-top: 20px; max-width: 100%;">
                <h2>📁 Backup Disponibili (<?php echo count( $backups ); ?>)</h2>
                
                <?php if ( empty( $backups ) ) : ?>
                    <p>Nessun backup trovato. Crea il primo backup!</p>
                <?php else : ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Licenze</th>
                                <th>Dimensione</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $backups as $backup ) : ?>
                                <tr>
                                    <td><code><?php echo esc_html( $backup['filename'] ); ?></code></td>
                                    <td><?php echo esc_html( $backup['date'] ); ?></td>
                                    <td>
                                        <?php 
                                        $type_labels = [
                                            'auto' => '🤖 Auto',
                                            'manual' => '👤 Manuale',
                                            'pre-restore' => '⚠️ Pre-Restore'
                                        ];
                                        echo $type_labels[ $backup['type'] ] ?? $backup['type'];
                                        ?>
                                    </td>
                                    <td><?php echo $backup['licenses_count']; ?></td>
                                    <td><?php echo size_format( $backup['size'] ); ?></td>
                                    <td>
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field( 'ipv_restore_backup' ); ?>
                                            <input type="hidden" name="backup_file" value="<?php echo esc_attr( $backup['filename'] ); ?>">
                                            <button type="submit" name="ipv_restore_backup" class="button button-small" onclick="return confirm('Sei sicuro di voler ripristinare questo backup?');">
                                                🔄 Ripristina
                                            </button>
                                        </form>
                                        <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=ipv-license-backup&ipv_delete_backup=' . urlencode( $backup['filename'] ) ), 'ipv_delete_backup' ); ?>" 
                                           class="button button-small" 
                                           onclick="return confirm('Eliminare questo backup?');"
                                           style="color: #a00;">
                                            🗑️
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Instructions -->
            <div class="card" style="margin-top: 20px; max-width: 100%;">
                <h2>📖 Istruzioni</h2>
                <h4>Come aggiornare il plugin senza perdere licenze:</h4>
                <ol>
                    <li><strong>PRIMA:</strong> Crea un backup manuale o esporta JSON</li>
                    <li>Vai su Plugin → Aggiungi nuovo → Carica plugin</li>
                    <li>Seleziona il nuovo ZIP</li>
                    <li><strong>IMPORTANTE:</strong> Clicca "Sostituisci il plugin attuale"</li>
                    <li>NON disattivare e eliminare il plugin!</li>
                </ol>
                
                <h4>In caso di emergenza:</h4>
                <ol>
                    <li>Vai su questa pagina</li>
                    <li>Seleziona un backup dalla lista</li>
                    <li>Clicca "Ripristina"</li>
                </ol>
            </div>
        </div>
        <?php
    }
}
