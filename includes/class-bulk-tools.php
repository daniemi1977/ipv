<?php
/**
 * IPV Bulk Tools - Strumenti di rigenerazione massiva
 * 
 * @version 7.5.7
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Bulk_Tools {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_submenu' ], 99 );
        
        // AJAX handlers
        add_action( 'wp_ajax_ipv_bulk_get_videos', [ __CLASS__, 'ajax_get_videos' ] );
        add_action( 'wp_ajax_ipv_bulk_regen_taxonomies', [ __CLASS__, 'ajax_regen_taxonomies' ] );
        add_action( 'wp_ajax_ipv_bulk_regen_description', [ __CLASS__, 'ajax_regen_description' ] );
        add_action( 'wp_ajax_ipv_bulk_regen_transcript', [ __CLASS__, 'ajax_regen_transcript' ] );
        add_action( 'wp_ajax_ipv_bulk_regen_thumbnail', [ __CLASS__, 'ajax_regen_thumbnail' ] );
        add_action( 'wp_ajax_ipv_bulk_clear_data', [ __CLASS__, 'ajax_clear_data' ] );
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
}

IPV_Prod_Bulk_Tools::init();
