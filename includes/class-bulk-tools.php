<?php
/**
 * IPV Bulk Tools - Strumenti di rigenerazione massiva
 * v7.9.19: Integrato tool immagini orfane
 * 
 * @version 7.9.19
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Bulk_Tools {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_submenu' ], 99 );
        
        // AJAX handlers video
        add_action( 'wp_ajax_ipv_bulk_get_videos', [ __CLASS__, 'ajax_get_videos' ] );
        add_action( 'wp_ajax_ipv_bulk_regen_taxonomies', [ __CLASS__, 'ajax_regen_taxonomies' ] );
        add_action( 'wp_ajax_ipv_bulk_regen_description', [ __CLASS__, 'ajax_regen_description' ] );
        add_action( 'wp_ajax_ipv_bulk_regen_transcript', [ __CLASS__, 'ajax_regen_transcript' ] );
        add_action( 'wp_ajax_ipv_bulk_regen_thumbnail', [ __CLASS__, 'ajax_regen_thumbnail' ] );
        add_action( 'wp_ajax_ipv_bulk_clear_data', [ __CLASS__, 'ajax_clear_data' ] );
        
        // AJAX handlers immagini orfane
        add_action( 'wp_ajax_ipv_scan_orphan_images', [ __CLASS__, 'ajax_scan_orphan_images' ] );
        add_action( 'wp_ajax_ipv_delete_orphan_images', [ __CLASS__, 'ajax_delete_orphan_images' ] );
    }

    public static function add_submenu() {
        add_submenu_page(
            'ipv-production',
            'Strumenti Bulk',
            '🔧 Strumenti Bulk',
            'manage_options',
            'ipv-bulk-tools',
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Statistiche
        global $wpdb;
        $total = wp_count_posts( 'ipv_video' )->publish;
        $with_desc = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='ipv_video' AND post_status='publish' AND post_content != ''" );
        $with_transcript = $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key='_ipv_transcript' AND meta_value != ''" );
        $with_tags = $wpdb->get_var( "SELECT COUNT(DISTINCT tr.object_id) FROM {$wpdb->term_relationships} tr JOIN {$wpdb->posts} p ON tr.object_id = p.ID WHERE p.post_type='ipv_video'" );
        
        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-admin-tools" style="font-size:30px;width:30px;height:30px;margin-right:10px;"></span>
                Strumenti Bulk - IPV Production
            </h1>

            <div style="max-width:1000px;margin-top:20px;">
                
                <!-- Statistiche -->
                <div class="ipv-stats-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin-bottom:25px;">
                    <div class="card" style="padding:20px;text-align:center;margin:0;">
                        <div style="font-size:32px;font-weight:bold;color:#2271b1;"><?php echo esc_html( $total ); ?></div>
                        <div style="color:#666;">Video Totali</div>
                    </div>
                    <div class="card" style="padding:20px;text-align:center;margin:0;">
                        <div style="font-size:32px;font-weight:bold;color:#00a32a;"><?php echo esc_html( $with_desc ); ?></div>
                        <div style="color:#666;">Con Descrizione AI</div>
                    </div>
                    <div class="card" style="padding:20px;text-align:center;margin:0;">
                        <div style="font-size:32px;font-weight:bold;color:#dba617;"><?php echo esc_html( $with_transcript ); ?></div>
                        <div style="color:#666;">Con Trascrizione</div>
                    </div>
                    <div class="card" style="padding:20px;text-align:center;margin:0;">
                        <div style="font-size:32px;font-weight:bold;color:#9b59b6;"><?php echo esc_html( $with_tags ); ?></div>
                        <div style="color:#666;">Con Tassonomie</div>
                    </div>
                </div>

                <!-- Strumenti -->
                <div class="ipv-tools-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:20px;">
                    
                    <!-- Rigenera Tassonomie -->
                    <div class="card" style="padding:0;margin:0;">
                        <div style="padding:15px 20px;background:#f0f6fc;border-bottom:1px solid #c3c4c7;">
                            <h2 style="margin:0;display:flex;align-items:center;gap:10px;">
                                <span style="font-size:24px;">🏷️</span>
                                Rigenera Tassonomie
                            </h2>
                        </div>
                        <div style="padding:20px;">
                            <p>Estrae dalla descrizione AI esistente:</p>
                            <ul style="margin:10px 0 15px 20px;">
                                <li><strong>Hashtag</strong> → Tag WordPress</li>
                                <li><strong>Argomenti</strong> → Categorie Video</li>
                                <li><strong>Ospiti</strong> → Relatori</li>
                            </ul>
                            <p class="description">⚡ Veloce - Non chiama API esterne</p>
                            <button type="button" class="button button-primary ipv-bulk-btn" data-action="taxonomies" style="margin-top:10px;">
                                Rigenera Tassonomie
                            </button>
                        </div>
                    </div>

                    <!-- Rigenera Descrizioni AI -->
                    <div class="card" style="padding:0;margin:0;">
                        <div style="padding:15px 20px;background:#fef8ee;border-bottom:1px solid #dba617;">
                            <h2 style="margin:0;display:flex;align-items:center;gap:10px;">
                                <span style="font-size:24px;">✨</span>
                                Rigenera Descrizioni AI
                            </h2>
                        </div>
                        <div style="padding:20px;">
                            <p>Rigenera le descrizioni AI per i video che hanno già la trascrizione.</p>
                            <p class="description" style="color:#b32d2e;">⚠️ Usa crediti OpenAI - Lento</p>
                            <p class="description">Utile dopo aggiornamento Golden Prompt</p>
                            <button type="button" class="button button-secondary ipv-bulk-btn" data-action="description" style="margin-top:10px;">
                                Rigenera Descrizioni
                            </button>
                        </div>
                    </div>

                    <!-- Rigenera Trascrizioni -->
                    <div class="card" style="padding:0;margin:0;">
                        <div style="padding:15px 20px;background:#fef0f0;border-bottom:1px solid #d63638;">
                            <h2 style="margin:0;display:flex;align-items:center;gap:10px;">
                                <span style="font-size:24px;">📝</span>
                                Rigenera Trascrizioni
                            </h2>
                        </div>
                        <div style="padding:20px;">
                            <p>Riscarica le trascrizioni da SupaData per tutti i video.</p>
                            <p class="description" style="color:#b32d2e;">⚠️ Usa crediti SupaData - Molto lento</p>
                            <p class="description">Solo per video senza trascrizione o con errori</p>
                            <button type="button" class="button button-secondary ipv-bulk-btn" data-action="transcript" style="margin-top:10px;">
                                Rigenera Trascrizioni
                            </button>
                        </div>
                    </div>

                    <!-- Rigenera Thumbnail -->
                    <div class="card" style="padding:0;margin:0;">
                        <div style="padding:15px 20px;background:#f0fdf4;border-bottom:1px solid #00a32a;">
                            <h2 style="margin:0;display:flex;align-items:center;gap:10px;">
                                <span style="font-size:24px;">🖼️</span>
                                Rigenera Thumbnail
                            </h2>
                        </div>
                        <div style="padding:20px;">
                            <p>Riscarica le immagini in evidenza da YouTube per i video che ne sono privi.</p>
                            <p class="description">⚡ Veloce - Solo download immagini</p>
                            <button type="button" class="button button-secondary ipv-bulk-btn" data-action="thumbnail" style="margin-top:10px;">
                                Rigenera Thumbnail
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Sezione Immagini Orfane (full width) -->
                <div class="card" style="margin-top:20px;padding:0;">
                    <div style="padding:15px 20px;background:#fef0f0;border-bottom:1px solid #d63638;">
                        <h2 style="margin:0;display:flex;align-items:center;gap:10px;">
                            <span style="font-size:24px;">🗑️</span>
                            Immagini Orfane
                        </h2>
                    </div>
                    <div style="padding:20px;">
                        <p>Trova e elimina immagini dalla Media Library non associate a nessun post, pagina o CPT.</p>
                        
                        <div style="margin:15px 0;">
                            <button type="button" id="ipv-scan-orphans" class="button button-primary">
                                🔍 Scansiona Immagini Orfane
                            </button>
                            <span id="ipv-orphan-scan-status" style="margin-left:15px;display:none;">
                                <span class="spinner is-active" style="float:none;"></span>
                                Scansione in corso...
                            </span>
                        </div>

                        <!-- Risultati Scansione -->
                        <div id="ipv-orphan-results" style="display:none;">
                            <div style="margin:15px 0;padding:15px;background:#fef8e7;border-left:4px solid #ffb900;border-radius:4px;">
                                <strong>Trovate: </strong><span id="ipv-orphan-count">0</span> immagini orfane
                                (<span id="ipv-orphan-size">0 KB</span> di spazio recuperabile)
                            </div>

                            <div style="margin:15px 0;">
                                <button type="button" id="ipv-orphan-select-all" class="button">☑️ Seleziona Tutte</button>
                                <button type="button" id="ipv-orphan-deselect-all" class="button">☐ Deseleziona</button>
                                <button type="button" id="ipv-orphan-delete" class="button" style="background:#d63638;border-color:#d63638;color:#fff;">
                                    🗑️ Elimina Selezionate (<span id="ipv-orphan-selected">0</span>)
                                </button>
                            </div>

                            <table class="wp-list-table widefat fixed striped" id="ipv-orphan-table">
                                <thead>
                                    <tr>
                                        <th style="width:40px;"><input type="checkbox" id="ipv-orphan-check-all"></th>
                                        <th style="width:80px;">Anteprima</th>
                                        <th>Nome File</th>
                                        <th style="width:100px;">Dimensione</th>
                                        <th style="width:100px;">Data</th>
                                        <th style="width:60px;">ID</th>
                                    </tr>
                                </thead>
                                <tbody id="ipv-orphan-tbody"></tbody>
                            </table>
                        </div>

                        <!-- Nessuna immagine orfana -->
                        <div id="ipv-orphan-none" style="display:none;margin:15px 0;padding:15px;background:#d4edda;border-left:4px solid #28a745;border-radius:4px;">
                            ✅ <strong>Ottimo!</strong> Non ci sono immagini orfane.
                        </div>
                    </div>
                </div>

                <!-- Progress Box -->
                <div id="ipv-bulk-progress" class="card" style="margin-top:25px;padding:0;display:none;">
                    <div style="padding:15px 20px;background:#f6f7f7;border-bottom:1px solid #c3c4c7;">
                        <h2 style="margin:0;" id="ipv-bulk-title">⏳ Elaborazione in corso...</h2>
                    </div>
                    <div style="padding:20px;">
                        <div class="ipv-progress-bar" style="background:#e0e0e0;border-radius:4px;height:30px;overflow:hidden;">
                            <div id="ipv-progress-fill" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);height:100%;width:0%;transition:width 0.3s;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:bold;">
                                0%
                            </div>
                        </div>
                        <p id="ipv-progress-status" style="margin:15px 0 0 0;">Preparazione...</p>
                        
                        <div id="ipv-progress-log" style="background:#1d2327;color:#f0f0f1;padding:15px;border-radius:4px;max-height:300px;overflow-y:auto;font-family:monospace;font-size:12px;margin-top:15px;white-space:pre-wrap;"></div>
                        
                        <div style="margin-top:15px;">
                            <button type="button" id="ipv-bulk-stop" class="button button-secondary" style="display:none;">
                                ⏹️ Ferma Elaborazione
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Results Box -->
                <div id="ipv-bulk-results" class="card" style="margin-top:25px;padding:20px;display:none;background:#f0fdf4;border-left:4px solid #00a32a;">
                    <h2 style="margin-top:0;">✅ Completato!</h2>
                    <div id="ipv-results-content"></div>
                </div>

            </div>
        </div>

        <style>
        .ipv-bulk-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        #ipv-progress-log::-webkit-scrollbar {
            width: 8px;
        }
        #ipv-progress-log::-webkit-scrollbar-track {
            background: #2c3338;
        }
        #ipv-progress-log::-webkit-scrollbar-thumb {
            background: #50575e;
            border-radius: 4px;
        }
        /* Stili Immagini Orfane */
        .ipv-orphan-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .ipv-orphan-thumb:hover {
            transform: scale(2);
            position: relative;
            z-index: 100;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        #ipv-orphan-table tbody tr:hover {
            background: #f0f6fc;
        }
        #ipv-orphan-table tbody tr.selected {
            background: #fff3cd;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var isRunning = false;
            var shouldStop = false;
            var videoList = [];
            var currentIndex = 0;
            var currentAction = '';
            var results = { success: 0, errors: 0, skipped: 0 };

            // Click su bottoni azione
            $('.ipv-bulk-btn').on('click', function() {
                if (isRunning) return;
                
                currentAction = $(this).data('action');
                var actionName = getActionName(currentAction);
                
                if (!confirm('Vuoi avviare "' + actionName + '" per tutti i video?\n\nQuesta operazione potrebbe richiedere tempo.')) {
                    return;
                }

                startBulkProcess();
            });

            // Stop
            $('#ipv-bulk-stop').on('click', function() {
                shouldStop = true;
                $(this).prop('disabled', true).text('Fermando...');
            });

            function getActionName(action) {
                var names = {
                    'taxonomies': 'Rigenera Tassonomie',
                    'description': 'Rigenera Descrizioni AI',
                    'transcript': 'Rigenera Trascrizioni',
                    'thumbnail': 'Rigenera Thumbnail'
                };
                return names[action] || action;
            }

            function startBulkProcess() {
                isRunning = true;
                shouldStop = false;
                results = { success: 0, errors: 0, skipped: 0 };
                
                $('.ipv-bulk-btn').prop('disabled', true);
                $('#ipv-bulk-progress').slideDown();
                $('#ipv-bulk-results').hide();
                $('#ipv-bulk-stop').show().prop('disabled', false).text('⏹️ Ferma Elaborazione');
                $('#ipv-bulk-title').text('⏳ ' + getActionName(currentAction) + ' in corso...');
                $('#ipv-progress-log').html('');
                $('#ipv-progress-fill').css('width', '0%').text('0%');
                
                addLog('🚀 Avvio ' + getActionName(currentAction) + '...');
                addLog('Recupero lista video...');

                // Ottieni lista video
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_bulk_get_videos',
                        nonce: '<?php echo wp_create_nonce( 'ipv_bulk_tools_nonce' ); ?>',
                        bulk_action: currentAction
                    },
                    success: function(response) {
                        if (response.success && response.data.videos) {
                            videoList = response.data.videos;
                            currentIndex = 0;
                            addLog('✓ Trovati ' + videoList.length + ' video da elaborare\n');
                            processNext();
                        } else {
                            addLog('❌ Errore: ' + (response.data || 'Nessun video trovato'));
                            finishProcess();
                        }
                    },
                    error: function() {
                        addLog('❌ Errore di connessione');
                        finishProcess();
                    }
                });
            }

            function processNext() {
                if (shouldStop) {
                    addLog('\n⏹️ Elaborazione fermata dall\'utente');
                    finishProcess();
                    return;
                }

                if (currentIndex >= videoList.length) {
                    finishProcess();
                    return;
                }

                var video = videoList[currentIndex];
                var percent = Math.round(((currentIndex + 1) / videoList.length) * 100);
                
                $('#ipv-progress-fill').css('width', percent + '%').text(percent + '%');
                $('#ipv-progress-status').text('Elaborazione ' + (currentIndex + 1) + ' di ' + videoList.length + ': ' + video.title.substring(0, 40) + '...');

                var ajaxAction = 'ipv_bulk_regen_' + currentAction;

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: ajaxAction,
                        nonce: '<?php echo wp_create_nonce( 'ipv_bulk_tools_nonce' ); ?>',
                        post_id: video.id
                    },
                    timeout: 120000, // 2 minuti per richiesta
                    success: function(response) {
                        if (response.success) {
                            var msg = '✅ [' + video.id + '] ' + video.title.substring(0, 40);
                            if (response.data.details) {
                                msg += ' → ' + response.data.details;
                            }
                            addLog(msg);
                            results.success++;
                        } else {
                            addLog('⚠️ [' + video.id + '] ' + (response.data || 'Errore'));
                            if (response.data && response.data.indexOf('skip') !== -1) {
                                results.skipped++;
                            } else {
                                results.errors++;
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        addLog('❌ [' + video.id + '] Timeout o errore: ' + error);
                        results.errors++;
                    },
                    complete: function() {
                        currentIndex++;
                        var delay = (currentAction === 'description' || currentAction === 'transcript') ? 1000 : 100;
                        setTimeout(processNext, delay);
                    }
                });
            }

            function finishProcess() {
                isRunning = false;
                shouldStop = false;
                
                $('.ipv-bulk-btn').prop('disabled', false);
                $('#ipv-bulk-stop').hide();
                $('#ipv-progress-fill').css('width', '100%').text('100%');
                $('#ipv-progress-status').text('Completato!');
                $('#ipv-bulk-title').text('✅ ' + getActionName(currentAction) + ' completato');
                
                var html = '<table class="widefat" style="max-width:300px;">';
                html += '<tr><td>✅ Elaborati con successo:</td><td><strong>' + results.success + '</strong></td></tr>';
                html += '<tr><td>⏭️ Saltati:</td><td>' + results.skipped + '</td></tr>';
                html += '<tr><td>❌ Errori:</td><td>' + results.errors + '</td></tr>';
                html += '<tr><td><strong>Totale processati:</strong></td><td><strong>' + videoList.length + '</strong></td></tr>';
                html += '</table>';
                
                $('#ipv-results-content').html(html);
                $('#ipv-bulk-results').slideDown();
                
                addLog('\n════════════════════════════════');
                addLog('COMPLETATO: ' + results.success + ' ok, ' + results.skipped + ' saltati, ' + results.errors + ' errori');
            }

            function addLog(msg) {
                var $log = $('#ipv-progress-log');
                var time = new Date().toLocaleTimeString('it-IT');
                $log.append('[' + time + '] ' + msg + '\n');
                $log.scrollTop($log[0].scrollHeight);
            }

            // ========== IMMAGINI ORFANE ==========
            var orphanImages = [];

            // Scansiona
            $('#ipv-scan-orphans').on('click', function() {
                var $btn = $(this);
                var $status = $('#ipv-orphan-scan-status');
                
                $btn.prop('disabled', true);
                $status.show();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_scan_orphan_images',
                        nonce: '<?php echo wp_create_nonce("ipv_orphan_images"); ?>'
                    },
                    success: function(response) {
                        $btn.prop('disabled', false);
                        $status.hide();
                        
                        if (response.success) {
                            orphanImages = response.data.orphans;
                            
                            if (response.data.count > 0) {
                                $('#ipv-orphan-count').text(response.data.count);
                                $('#ipv-orphan-size').text(response.data.total_size);
                                renderOrphanTable(orphanImages);
                                $('#ipv-orphan-results').show();
                                $('#ipv-orphan-none').hide();
                            } else {
                                $('#ipv-orphan-results').hide();
                                $('#ipv-orphan-none').show();
                            }
                        } else {
                            alert('Errore: ' + response.data);
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false);
                        $status.hide();
                        alert('Errore di connessione');
                    }
                });
            });

            function renderOrphanTable(images) {
                var $tbody = $('#ipv-orphan-tbody');
                $tbody.empty();
                
                images.forEach(function(img) {
                    var thumbHtml = img.thumb_url 
                        ? '<img src="' + img.thumb_url + '" class="ipv-orphan-thumb">'
                        : '<span style="color:#999;">-</span>';
                    
                    var row = '<tr data-id="' + img.id + '">' +
                        '<td><input type="checkbox" class="ipv-orphan-check" value="' + img.id + '"></td>' +
                        '<td>' + thumbHtml + '</td>' +
                        '<td><a href="' + img.full_url + '" target="_blank">' + img.filename + '</a></td>' +
                        '<td>' + img.size + '</td>' +
                        '<td>' + img.date + '</td>' +
                        '<td>' + img.id + '</td>' +
                    '</tr>';
                    $tbody.append(row);
                });
                
                updateOrphanCount();
            }

            function updateOrphanCount() {
                var count = $('.ipv-orphan-check:checked').length;
                $('#ipv-orphan-selected').text(count);
                
                $('#ipv-orphan-tbody tr').each(function() {
                    $(this).toggleClass('selected', $(this).find('.ipv-orphan-check').is(':checked'));
                });
            }

            $(document).on('change', '.ipv-orphan-check', updateOrphanCount);
            
            $('#ipv-orphan-check-all').on('change', function() {
                $('.ipv-orphan-check').prop('checked', $(this).is(':checked'));
                updateOrphanCount();
            });

            $('#ipv-orphan-select-all').on('click', function() {
                $('.ipv-orphan-check').prop('checked', true);
                $('#ipv-orphan-check-all').prop('checked', true);
                updateOrphanCount();
            });

            $('#ipv-orphan-deselect-all').on('click', function() {
                $('.ipv-orphan-check').prop('checked', false);
                $('#ipv-orphan-check-all').prop('checked', false);
                updateOrphanCount();
            });

            // Elimina selezionate
            $('#ipv-orphan-delete').on('click', function() {
                var ids = [];
                $('.ipv-orphan-check:checked').each(function() {
                    ids.push($(this).val());
                });
                
                if (ids.length === 0) {
                    alert('Seleziona almeno un\'immagine');
                    return;
                }
                
                if (!confirm('Eliminare ' + ids.length + ' immagini?\n\nAzione IRREVERSIBILE!')) {
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true).text('🗑️ Eliminazione...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_delete_orphan_images',
                        nonce: '<?php echo wp_create_nonce("ipv_orphan_images"); ?>',
                        ids: ids
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).html('🗑️ Elimina Selezionate (<span id="ipv-orphan-selected">0</span>)');
                        
                        if (response.success) {
                            alert(response.data.message);
                            
                            ids.forEach(function(id) {
                                $('#ipv-orphan-tbody tr[data-id="' + id + '"]').fadeOut(300, function() {
                                    $(this).remove();
                                    var remaining = $('#ipv-orphan-tbody tr').length;
                                    $('#ipv-orphan-count').text(remaining);
                                    if (remaining === 0) {
                                        $('#ipv-orphan-results').hide();
                                        $('#ipv-orphan-none').show();
                                    }
                                    updateOrphanCount();
                                });
                            });
                        } else {
                            alert('Errore: ' + response.data);
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).html('🗑️ Elimina Selezionate (<span id="ipv-orphan-selected">0</span>)');
                        alert('Errore di connessione');
                    }
                });
            });

            // Click su riga seleziona checkbox
            $(document).on('click', '#ipv-orphan-tbody tr td:not(:first-child)', function() {
                var $cb = $(this).closest('tr').find('.ipv-orphan-check');
                $cb.prop('checked', !$cb.is(':checked'));
                updateOrphanCount();
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Ottieni lista video
     */
    public static function ajax_get_videos() {
        check_ajax_referer( 'ipv_bulk_tools_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permessi insufficienti' );
        }

        $bulk_action = isset( $_POST['bulk_action'] ) ? sanitize_text_field( $_POST['bulk_action'] ) : '';

        $args = [
            'post_type'      => 'ipv_video',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        // Filtra in base all'azione
        if ( $bulk_action === 'thumbnail' ) {
            // Solo video senza thumbnail
            $args['meta_query'] = [
                [
                    'key'     => '_thumbnail_id',
                    'compare' => 'NOT EXISTS',
                ],
            ];
        }

        $posts = get_posts( $args );
        $videos = [];

        foreach ( $posts as $post ) {
            // Per tassonomie/descrizione, serve che abbiano contenuto
            if ( in_array( $bulk_action, [ 'taxonomies' ] ) ) {
                if ( empty( $post->post_content ) ) {
                    continue;
                }
            }
            
            // Per descrizione, serve trascrizione
            if ( $bulk_action === 'description' ) {
                $transcript = get_post_meta( $post->ID, '_ipv_transcript', true );
                if ( empty( $transcript ) ) {
                    continue;
                }
            }

            $videos[] = [
                'id'    => $post->ID,
                'title' => $post->post_title,
            ];
        }

        wp_send_json_success( [ 'videos' => $videos ] );
    }

    /**
     * AJAX: Rigenera tassonomie singolo video
     */
    public static function ajax_regen_taxonomies() {
        check_ajax_referer( 'ipv_bulk_tools_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permessi insufficienti' );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        if ( ! $post_id ) {
            wp_send_json_error( 'ID mancante' );
        }

        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== 'ipv_video' ) {
            wp_send_json_error( 'Video non trovato' );
        }

        $description = $post->post_content;
        $title       = $post->post_title;

        if ( empty( $description ) ) {
            wp_send_json_error( 'skip - Nessuna descrizione AI' );
        }

        // Pulisci tassonomie esistenti
        wp_set_object_terms( $post_id, [], 'post_tag', false );
        wp_set_object_terms( $post_id, [], 'ipv_categoria', false );
        wp_set_object_terms( $post_id, [], 'ipv_relatore', false );

        // Rigenera
        if ( class_exists( 'IPV_Prod_AI_Generator' ) ) {
            IPV_Prod_AI_Generator::extract_and_save_hashtags( $post_id, $description );
            IPV_Prod_AI_Generator::extract_and_save_speakers( $post_id, $title, $description );
            IPV_Prod_AI_Generator::extract_and_save_categories( $post_id, $description );

            $tags  = count( wp_get_object_terms( $post_id, 'post_tag' ) );
            $cats  = count( wp_get_object_terms( $post_id, 'ipv_categoria' ) );
            $rels  = count( wp_get_object_terms( $post_id, 'ipv_relatore' ) );

            wp_send_json_success( [ 
                'details' => "Tag:{$tags} Cat:{$cats} Rel:{$rels}"
            ] );
        } else {
            wp_send_json_error( 'Classe AI Generator non trovata' );
        }
    }

    /**
     * AJAX: Rigenera descrizione AI singolo video
     */
    public static function ajax_regen_description() {
        check_ajax_referer( 'ipv_bulk_tools_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permessi insufficienti' );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        if ( ! $post_id ) {
            wp_send_json_error( 'ID mancante' );
        }

        if ( class_exists( 'IPV_Prod_AI_Generator' ) ) {
            $result = IPV_Prod_AI_Generator::generate_and_save( $post_id );
            
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( $result->get_error_message() );
            }

            wp_send_json_success( [ 'details' => 'Descrizione rigenerata' ] );
        } else {
            wp_send_json_error( 'Classe AI Generator non trovata' );
        }
    }

    /**
     * AJAX: Rigenera trascrizione singolo video
     */
    public static function ajax_regen_transcript() {
        check_ajax_referer( 'ipv_bulk_tools_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permessi insufficienti' );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        if ( ! $post_id ) {
            wp_send_json_error( 'ID mancante' );
        }

        $video_id = get_post_meta( $post_id, '_ipv_video_id', true );
        if ( empty( $video_id ) ) {
            wp_send_json_error( 'skip - ID YouTube mancante' );
        }

        if ( class_exists( 'IPV_Prod_Supadata' ) ) {
            $mode = get_option( 'ipv_transcript_mode', 'auto' );
            $transcript = IPV_Prod_Supadata::get_transcript( $video_id, $mode );
            
            if ( is_wp_error( $transcript ) ) {
                wp_send_json_error( $transcript->get_error_message() );
            }

            update_post_meta( $post_id, '_ipv_transcript', $transcript );
            
            $words = str_word_count( $transcript );
            wp_send_json_success( [ 'details' => "{$words} parole" ] );
        } else {
            wp_send_json_error( 'Classe SupaData non trovata' );
        }
    }

    /**
     * AJAX: Rigenera thumbnail singolo video
     */
    public static function ajax_regen_thumbnail() {
        check_ajax_referer( 'ipv_bulk_tools_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permessi insufficienti' );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        if ( ! $post_id ) {
            wp_send_json_error( 'ID mancante' );
        }

        // Se ha già thumbnail, salta
        if ( has_post_thumbnail( $post_id ) ) {
            wp_send_json_error( 'skip - Ha già thumbnail' );
        }

        $video_id = get_post_meta( $post_id, '_ipv_video_id', true );
        if ( empty( $video_id ) ) {
            wp_send_json_error( 'skip - ID YouTube mancante' );
        }

        // Prova a scaricare thumbnail
        $thumbnail_url = get_post_meta( $post_id, '_ipv_yt_thumbnail_url', true );
        if ( empty( $thumbnail_url ) ) {
            $thumbnail_url = "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg";
        }

        if ( class_exists( 'IPV_Prod_Queue' ) ) {
            $result = IPV_Prod_Queue::set_featured_image_from_youtube( $post_id, $thumbnail_url );
            
            if ( $result ) {
                wp_send_json_success( [ 'details' => 'Thumbnail scaricata' ] );
            } else {
                wp_send_json_error( 'Errore download thumbnail' );
            }
        } else {
            wp_send_json_error( 'Classe Queue non trovata' );
        }
    }

    // ========== IMMAGINI ORFANE ==========

    /**
     * Trova immagini orfane nella Media Library
     */
    public static function find_orphan_images() {
        global $wpdb;

        // 1. Tutte le immagini
        $attachments = get_posts( [
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'post_status'    => 'inherit',
            'fields'         => 'ids',
        ] );

        if ( empty( $attachments ) ) {
            return [];
        }

        $orphans = [];

        // 2. Featured images
        $featured_ids = $wpdb->get_col( "
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_thumbnail_id' 
            AND meta_value != ''
        " );
        $featured_ids = array_map( 'intval', $featured_ids );

        // 3. Attachment con parent
        $attached_ids = $wpdb->get_col( "
            SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' AND post_parent > 0
        " );
        $attached_ids = array_map( 'intval', $attached_ids );

        // 4. Immagini nei contenuti
        $content_images = [];
        $all_posts = $wpdb->get_results( "
            SELECT ID, post_content FROM {$wpdb->posts} 
            WHERE post_status IN ('publish', 'draft', 'private', 'pending')
            AND post_content LIKE '%wp-image-%'
        " );

        foreach ( $all_posts as $post ) {
            preg_match_all( '/wp-image-(\d+)/', $post->post_content, $matches );
            if ( ! empty( $matches[1] ) ) {
                $content_images = array_merge( $content_images, $matches[1] );
            }
        }
        $content_images = array_map( 'intval', array_unique( $content_images ) );

        // 5. ID nei meta
        $meta_images = [];
        $meta_results = $wpdb->get_col( "
            SELECT DISTINCT meta_value FROM {$wpdb->postmeta} 
            WHERE meta_value REGEXP '^[0-9]+$' AND CAST(meta_value AS UNSIGNED) > 0
        " );

        foreach ( $meta_results as $meta_val ) {
            if ( is_numeric( $meta_val ) && $meta_val > 0 ) {
                $meta_images[] = intval( $meta_val );
            }
        }

        // 6. Combina tutti gli ID usati
        $used_ids = array_unique( array_merge(
            $featured_ids,
            $attached_ids,
            $content_images,
            $meta_images
        ) );

        // 7. Trova orfane
        foreach ( $attachments as $attachment_id ) {
            if ( ! in_array( $attachment_id, $used_ids, true ) ) {
                $orphans[] = $attachment_id;
            }
        }

        return $orphans;
    }

    /**
     * AJAX: Scansiona immagini orfane
     */
    public static function ajax_scan_orphan_images() {
        check_ajax_referer( 'ipv_orphan_images', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permessi insufficienti' );
        }

        $orphans = self::find_orphan_images();
        $orphan_data = [];

        foreach ( $orphans as $attachment_id ) {
            $file_path = get_attached_file( $attachment_id );
            $file_size = file_exists( $file_path ) ? filesize( $file_path ) : 0;
            $thumb_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
            $full_url  = wp_get_attachment_url( $attachment_id );

            $orphan_data[] = [
                'id'        => $attachment_id,
                'filename'  => basename( $file_path ),
                'size'      => size_format( $file_size ),
                'size_raw'  => $file_size,
                'thumb_url' => $thumb_url ?: '',
                'full_url'  => $full_url ?: '',
                'date'      => get_the_date( 'd/m/Y', $attachment_id ),
            ];
        }

        $total_size = array_sum( array_column( $orphan_data, 'size_raw' ) );

        wp_send_json_success( [
            'orphans'    => $orphan_data,
            'count'      => count( $orphan_data ),
            'total_size' => size_format( $total_size ),
        ] );
    }

    /**
     * AJAX: Elimina immagini orfane
     */
    public static function ajax_delete_orphan_images() {
        check_ajax_referer( 'ipv_orphan_images', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permessi insufficienti' );
        }

        $ids = isset( $_POST['ids'] ) ? array_map( 'intval', (array) $_POST['ids'] ) : [];

        if ( empty( $ids ) ) {
            wp_send_json_error( 'Nessuna immagine selezionata' );
        }

        $deleted = 0;

        foreach ( $ids as $attachment_id ) {
            if ( get_post_type( $attachment_id ) === 'attachment' ) {
                if ( wp_delete_attachment( $attachment_id, true ) ) {
                    $deleted++;
                }
            }
        }

        wp_send_json_success( [
            'deleted' => $deleted,
            'message' => sprintf( 'Eliminate %d immagini', $deleted ),
        ] );
    }
}

// Disabilitato - non necessario per v9.0.0
// IPV_Prod_Bulk_Tools::init();
