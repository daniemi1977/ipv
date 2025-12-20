<?php
/**
 * IPV Production System Pro - Queue Dashboard
 * 
 * Visualizza e gestisce la coda di importazione video
 * v10.0.23 - Aggiunto bottone cancella per singoli job
 *
 * @package IPV_Production_System_Pro
 * @version 10.0.23
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Queue_Dashboard {

    public static function init() {
        add_submenu_page(
            'ipv-production',
            __( 'Coda Importazione', 'ipv-production-system-pro' ),
            __( 'Coda', 'ipv-production-system-pro' ),
            'manage_options',
            'ipv-queue-dashboard',
            [ __CLASS__, 'render' ]
        );

        // AJAX handlers
        add_action( 'wp_ajax_ipv_queue_delete', [ __CLASS__, 'ajax_delete_job' ] );
        add_action( 'wp_ajax_ipv_queue_delete_all', [ __CLASS__, 'ajax_delete_all' ] );
        add_action( 'wp_ajax_ipv_queue_retry', [ __CLASS__, 'ajax_retry_job' ] );
    }

    /**
     * Render the queue dashboard
     */
    public static function render() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ipv_prod_queue';
        
        // Get filter
        $filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'all';
        
        // Build query
        $where = '';
        if ( $filter !== 'all' ) {
            $where = $wpdb->prepare( " WHERE status = %s", $filter );
        }
        
        // Get jobs
        $jobs = $wpdb->get_results( "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT 100" );
        
        // Get stats
        $stats = IPV_Prod_Queue::get_stats();
        
        // Get min duration setting
        $min_duration = get_option( 'ipv_min_duration_minutes', 0 );
        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-list-view" style="vertical-align: middle;"></span>
                <?php esc_html_e( 'Coda Importazione Video', 'ipv-production-system-pro' ); ?>
            </h1>

            <!-- Stats Cards -->
            <div style="display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap;">
                <div class="ipv-stat-card" style="background: #fff; padding: 15px 25px; border-radius: 8px; border-left: 4px solid #f59e0b; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="font-size: 28px; font-weight: bold; color: #f59e0b;"><?php echo esc_html( $stats['pending'] ); ?></div>
                    <div style="color: #666;">In Attesa</div>
                </div>
                <div class="ipv-stat-card" style="background: #fff; padding: 15px 25px; border-radius: 8px; border-left: 4px solid #3b82f6; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="font-size: 28px; font-weight: bold; color: #3b82f6;"><?php echo esc_html( $stats['processing'] ); ?></div>
                    <div style="color: #666;">In Elaborazione</div>
                </div>
                <div class="ipv-stat-card" style="background: #fff; padding: 15px 25px; border-radius: 8px; border-left: 4px solid #22c55e; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="font-size: 28px; font-weight: bold; color: #22c55e;"><?php echo esc_html( $stats['done'] ); ?></div>
                    <div style="color: #666;">Completati</div>
                </div>
                <div class="ipv-stat-card" style="background: #fff; padding: 15px 25px; border-radius: 8px; border-left: 4px solid #ef4444; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="font-size: 28px; font-weight: bold; color: #ef4444;"><?php echo esc_html( $stats['error'] ); ?></div>
                    <div style="color: #666;">Errori</div>
                </div>
                <div class="ipv-stat-card" style="background: #fff; padding: 15px 25px; border-radius: 8px; border-left: 4px solid #94a3b8; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="font-size: 28px; font-weight: bold; color: #94a3b8;"><?php echo esc_html( $stats['skipped'] ); ?></div>
                    <div style="color: #666;">Saltati</div>
                </div>
            </div>

            <!-- Info Box -->
            <div style="background: #e0f2fe; border: 1px solid #7dd3fc; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px;">
                <strong>ℹ️ Impostazioni attive:</strong>
                Durata minima: <strong><?php echo $min_duration > 0 ? $min_duration . ' minuti' : 'Nessuna (tutti i video)'; ?></strong>
                — I video più corti vengono automaticamente saltati.
                <a href="<?php echo admin_url( 'admin.php?page=ipv-settings' ); ?>">Modifica nelle Impostazioni →</a>
            </div>

            <!-- Filters -->
            <div style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <span>Filtra:</span>
                <a href="<?php echo add_query_arg( 'status', 'all' ); ?>" 
                   class="button <?php echo $filter === 'all' ? 'button-primary' : ''; ?>">
                    Tutti
                </a>
                <a href="<?php echo add_query_arg( 'status', 'pending' ); ?>" 
                   class="button <?php echo $filter === 'pending' ? 'button-primary' : ''; ?>">
                    ⏳ In Attesa (<?php echo $stats['pending']; ?>)
                </a>
                <a href="<?php echo add_query_arg( 'status', 'processing' ); ?>" 
                   class="button <?php echo $filter === 'processing' ? 'button-primary' : ''; ?>">
                    ⚙️ Elaborazione
                </a>
                <a href="<?php echo add_query_arg( 'status', 'done' ); ?>" 
                   class="button <?php echo $filter === 'done' ? 'button-primary' : ''; ?>">
                    ✅ Completati
                </a>
                <a href="<?php echo add_query_arg( 'status', 'error' ); ?>" 
                   class="button <?php echo $filter === 'error' ? 'button-primary' : ''; ?>">
                    ❌ Errori (<?php echo $stats['error']; ?>)
                </a>
                <a href="<?php echo add_query_arg( 'status', 'skipped' ); ?>" 
                   class="button <?php echo $filter === 'skipped' ? 'button-primary' : ''; ?>">
                    ⏭️ Saltati
                </a>
                
                <span style="margin-left: auto;">
                    <button type="button" id="ipv-delete-completed" class="button" style="color: #666;">
                        🗑️ Pulisci Completati
                    </button>
                    <button type="button" id="ipv-delete-all-pending" class="button" style="color: #dc2626;">
                        ⚠️ Cancella Tutti in Attesa
                    </button>
                </span>
            </div>

            <!-- Queue Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th style="width: 120px;">Video ID</th>
                        <th>URL</th>
                        <th style="width: 80px;">Fonte</th>
                        <th style="width: 110px;">Stato</th>
                        <th style="width: 50px;">Tentativi</th>
                        <th style="width: 120px;">Creato</th>
                        <th style="width: 200px;">Errore</th>
                        <th style="width: 100px;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $jobs ) ) : ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 30px; color: #666;">
                                <?php esc_html_e( 'Nessun job in coda', 'ipv-production-system-pro' ); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $jobs as $job ) : ?>
                            <tr data-job-id="<?php echo esc_attr( $job->id ); ?>">
                                <td><code><?php echo esc_html( $job->id ); ?></code></td>
                                <td>
                                    <a href="https://youtube.com/watch?v=<?php echo esc_attr( $job->video_id ); ?>" target="_blank" title="Apri su YouTube">
                                        <?php echo esc_html( $job->video_id ); ?>
                                    </a>
                                </td>
                                <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <a href="<?php echo esc_url( $job->video_url ); ?>" target="_blank">
                                        <?php echo esc_html( $job->video_url ); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php
                                    $source_labels = [
                                        'manual' => '✋ Manuale',
                                        'rss'    => '📡 RSS',
                                        'bulk'   => '📦 Bulk',
                                        'channel' => '📺 Canale',
                                    ];
                                    echo $source_labels[ $job->source ] ?? $job->source;
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $status_badges = [
                                        'pending'    => '<span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:10px;font-size:12px;">⏳ In attesa</span>',
                                        'processing' => '<div style="display:flex;align-items:center;gap:8px;">
                                                            <span style="background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:10px;font-size:12px;">⚙️ Elaborazione</span>
                                                            <div style="width:80px;height:4px;background:#e0e7ff;border-radius:2px;overflow:hidden;">
                                                                <div class="ipv-progress-bar" style="width:100%;height:100%;background:linear-gradient(90deg,#3b82f6 25%,transparent 25%,transparent 50%,#3b82f6 50%,#3b82f6 75%,transparent 75%,transparent);background-size:20px 20px;animation:ipv-progress 1s linear infinite;"></div>
                                                            </div>
                                                         </div>',
                                        'done'       => '<span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:10px;font-size:12px;">✅ Completato</span>',
                                        'error'      => '<span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:10px;font-size:12px;">❌ Errore</span>',
                                        'skipped'    => '<span style="background:#f1f5f9;color:#475569;padding:2px 8px;border-radius:10px;font-size:12px;">⏭️ Saltato</span>',
                                    ];
                                    echo $status_badges[ $job->status ] ?? $job->status;
                                    ?>
                                </td>
                                <td style="text-align: center;"><?php echo esc_html( $job->attempts ); ?></td>
                                <td><?php echo esc_html( human_time_diff( strtotime( $job->created_at ), current_time( 'timestamp' ) ) ); ?> fa</td>
                                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo esc_attr( $job->last_error ); ?>">
                                    <?php echo $job->last_error ? esc_html( substr( $job->last_error, 0, 50 ) ) . '...' : '—'; ?>
                                </td>
                                <td>
                                    <?php if ( in_array( $job->status, [ 'pending', 'error', 'skipped' ] ) ) : ?>
                                        <button type="button" class="button button-small ipv-retry-job" data-id="<?php echo esc_attr( $job->id ); ?>" title="Riprova">
                                            🔄
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="button button-small ipv-delete-job" data-id="<?php echo esc_attr( $job->id ); ?>" title="Elimina" style="color: #dc2626;">
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <p class="description" style="margin-top: 15px;">
                Mostrando max 100 job. I job completati vengono mantenuti per riferimento.
            </p>
        </div>

        <style>
        @keyframes ipv-progress {
            0% { background-position: 0 0; }
            100% { background-position: 20px 0; }
        }

        .ipv-auto-refresh-notice {
            position: fixed;
            top: 32px;
            right: 20px;
            background: #fff;
            border-left: 4px solid #3b82f6;
            padding: 12px 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 4px;
            z-index: 9999;
            display: none;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var nonce = '<?php echo wp_create_nonce( 'ipv_queue_action' ); ?>';

            // Auto-refresh ogni 10 secondi se ci sono job in elaborazione
            var processingJobs = $('td:contains("⚙️ Elaborazione")').length;
            if (processingJobs > 0) {
                // Mostra notifica auto-refresh
                $('body').append('<div class="ipv-auto-refresh-notice">🔄 Auto-refresh attivo (10s)</div>');
                $('.ipv-auto-refresh-notice').fadeIn().delay(2000).fadeOut();

                setTimeout(function() {
                    location.reload();
                }, 10000); // 10 secondi
            }

            // Delete single job
            $('.ipv-delete-job').on('click', function() {
                var btn = $(this);
                var jobId = btn.data('id');

                if (!confirm('Eliminare questo job dalla coda?')) return;

                btn.prop('disabled', true).text('...');

                $.post(ajaxurl, {
                    action: 'ipv_queue_delete',
                    job_id: jobId,
                    nonce: nonce
                }, function(response) {
                    if (response.success) {
                        btn.closest('tr').fadeOut(300, function() { $(this).remove(); });
                    } else {
                        alert('Errore: ' + response.data.message);
                        btn.prop('disabled', false).text('🗑️');
                    }
                });
            });

            // Retry job
            $('.ipv-retry-job').on('click', function() {
                var btn = $(this);
                var jobId = btn.data('id');

                btn.prop('disabled', true).text('...');

                $.post(ajaxurl, {
                    action: 'ipv_queue_retry',
                    job_id: jobId,
                    nonce: nonce
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Errore: ' + response.data.message);
                        btn.prop('disabled', false).text('🔄');
                    }
                });
            });

            // Delete all completed
            $('#ipv-delete-completed').on('click', function() {
                if (!confirm('Eliminare tutti i job completati?')) return;

                var btn = $(this);
                btn.prop('disabled', true).text('...');

                $.post(ajaxurl, {
                    action: 'ipv_queue_delete_all',
                    status: 'done',
                    nonce: nonce
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Errore: ' + response.data.message);
                        btn.prop('disabled', false).text('🗑️ Pulisci Completati');
                    }
                });
            });

            // Delete all pending
            $('#ipv-delete-all-pending').on('click', function() {
                if (!confirm('⚠️ ATTENZIONE!\n\nEliminare TUTTI i job in attesa?\nQuesta azione non può essere annullata.')) return;

                var btn = $(this);
                btn.prop('disabled', true).text('...');

                $.post(ajaxurl, {
                    action: 'ipv_queue_delete_all',
                    status: 'pending',
                    nonce: nonce
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Errore: ' + response.data.message);
                        btn.prop('disabled', false).text('⚠️ Cancella Tutti in Attesa');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Delete single job
     */
    public static function ajax_delete_job() {
        check_ajax_referer( 'ipv_queue_action', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Permessi insufficienti' ] );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ipv_prod_queue';
        $job_id = intval( $_POST['job_id'] );

        $deleted = $wpdb->delete( $table, [ 'id' => $job_id ], [ '%d' ] );

        if ( $deleted ) {
            wp_send_json_success( [ 'message' => 'Job eliminato' ] );
        } else {
            wp_send_json_error( [ 'message' => 'Impossibile eliminare il job' ] );
        }
    }

    /**
     * AJAX: Delete all jobs by status
     */
    public static function ajax_delete_all() {
        check_ajax_referer( 'ipv_queue_action', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Permessi insufficienti' ] );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ipv_prod_queue';
        $status = sanitize_text_field( $_POST['status'] );

        $deleted = $wpdb->delete( $table, [ 'status' => $status ], [ '%s' ] );

        wp_send_json_success( [ 'message' => "Eliminati {$deleted} job", 'count' => $deleted ] );
    }

    /**
     * AJAX: Retry job
     */
    public static function ajax_retry_job() {
        check_ajax_referer( 'ipv_queue_action', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Permessi insufficienti' ] );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ipv_prod_queue';
        $job_id = intval( $_POST['job_id'] );

        $updated = $wpdb->update(
            $table,
            [
                'status'     => 'pending',
                'attempts'   => 0,
                'last_error' => '',
                'updated_at' => current_time( 'mysql' ),
            ],
            [ 'id' => $job_id ],
            [ '%s', '%d', '%s', '%s' ],
            [ '%d' ]
        );

        if ( $updated ) {
            wp_send_json_success( [ 'message' => 'Job rimesso in coda' ] );
        } else {
            wp_send_json_error( [ 'message' => 'Impossibile ripristinare il job' ] );
        }
    }
}

IPV_Queue_Dashboard::init();
