<?php
/**
 * IPV Duplicate Checker
 * Tool per identificare post e media duplicati
 *
 * @version 7.9.9
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Duplicate_Checker {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_admin_page' ], 99 );
        add_action( 'wp_ajax_ipv_delete_duplicate_posts', [ __CLASS__, 'ajax_delete_duplicates' ] );
    }

    public static function add_admin_page() {
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            'Controllo Duplicati',
            '🔍 Duplicati',
            'manage_options',
            'ipv-duplicate-checker',
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function render_page() {
        ?>
        <div class="wrap">
            <h1>🔍 Controllo Duplicati - IPV Production System</h1>

            <div style="max-width: 1200px;">
                <!-- POST DUPLICATI -->
                <div style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #dc3545;">
                    <h2>📹 Post Duplicati</h2>
                    <?php self::check_duplicate_posts(); ?>
                </div>

                <!-- MEDIA DUPLICATI -->
                <div style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #ffc107;">
                    <h2>🖼️ Media Duplicati</h2>
                    <?php self::check_duplicate_media(); ?>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.ipv-delete-dups').on('click', function() {
                var $btn = $(this);
                var postIds = $btn.data('post-ids');
                var videoId = $btn.data('video-id');
                var idsArray = postIds.toString().split(',');

                var confirmMsg = 'ATTENZIONE: Stai per eliminare ' + (idsArray.length - 1) + ' post duplicati.\n\n';
                confirmMsg += 'Video ID YouTube: ' + videoId + '\n';
                confirmMsg += 'Verrà mantenuto il post ID ' + idsArray[0] + ' (il più vecchio)\n';
                confirmMsg += 'Verranno eliminati: ' + idsArray.slice(1).join(', ') + '\n\n';
                confirmMsg += 'QUESTA AZIONE È IRREVERSIBILE!\n\nContinuare?';

                if (!confirm(confirmMsg)) {
                    return;
                }

                $btn.prop('disabled', true).text('⏳ Eliminazione...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_delete_duplicate_posts',
                        nonce: '<?php echo wp_create_nonce( 'ipv_duplicate_checker_nonce' ); ?>',
                        post_ids: postIds
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ ' + response.data.message);
                            // Rimuovi la riga dalla tabella
                            $btn.closest('tr').fadeOut(400, function() {
                                $(this).remove();
                            });
                        } else {
                            alert('❌ Errore: ' + response.data);
                            $btn.prop('disabled', false).text('Elimina Duplicati');
                        }
                    },
                    error: function() {
                        alert('❌ Errore di connessione');
                        $btn.prop('disabled', false).text('Elimina Duplicati');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Controlla post duplicati
     */
    private static function check_duplicate_posts() {
        global $wpdb;

        echo '<h3>Controllo per Titolo Duplicato</h3>';

        $duplicates_by_title = $wpdb->get_results("
            SELECT post_title, COUNT(*) as count, GROUP_CONCAT(ID ORDER BY ID) as post_ids
            FROM {$wpdb->posts}
            WHERE post_type = 'ipv_video'
            AND post_status = 'publish'
            GROUP BY post_title
            HAVING count > 1
            ORDER BY count DESC
        ");

        if ( empty( $duplicates_by_title ) ) {
            echo '<p style="color: #28a745;">✅ Nessun post duplicato per titolo trovato.</p>';
        } else {
            echo '<p style="color: #dc3545;">⚠️ Trovati <strong>' . count( $duplicates_by_title ) . '</strong> titoli duplicati:</p>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Titolo</th><th>Numero Duplicati</th><th>ID Posts</th><th>Azioni</th></tr></thead>';
            echo '<tbody>';

            foreach ( $duplicates_by_title as $dup ) {
                $ids = explode( ',', $dup->post_ids );
                echo '<tr>';
                echo '<td><strong>' . esc_html( $dup->post_title ) . '</strong></td>';
                echo '<td><span style="background: #dc3545; color: white; padding: 2px 8px; border-radius: 3px;">' . $dup->count . '</span></td>';
                echo '<td>';
                foreach ( $ids as $id ) {
                    echo '<a href="' . get_edit_post_link( $id ) . '" target="_blank">ID: ' . $id . '</a> ';
                }
                echo '</td>';
                echo '<td><a href="' . admin_url( 'edit.php?post_type=ipv_video&s=' . urlencode( $dup->post_title ) ) . '" class="button button-small">Vedi Tutti</a></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        echo '<hr>';
        echo '<h3>Controllo per Video ID YouTube Duplicato</h3>';

        $duplicates_by_video_id = $wpdb->get_results("
            SELECT meta_value as video_id, COUNT(*) as count, GROUP_CONCAT(post_id ORDER BY post_id) as post_ids
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_ipv_video_id'
            AND meta_value != ''
            GROUP BY meta_value
            HAVING count > 1
            ORDER BY count DESC
        ");

        if ( empty( $duplicates_by_video_id ) ) {
            echo '<p style="color: #28a745;">✅ Nessun video YouTube duplicato trovato.</p>';
        } else {
            echo '<p style="color: #dc3545;">⚠️ Trovati <strong>' . count( $duplicates_by_video_id ) . '</strong> video YouTube duplicati:</p>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Video ID YouTube</th><th>Numero Duplicati</th><th>Posts</th><th>Azioni</th></tr></thead>';
            echo '<tbody>';

            foreach ( $duplicates_by_video_id as $dup ) {
                $ids = explode( ',', $dup->post_ids );
                echo '<tr>';
                echo '<td><code>' . esc_html( $dup->video_id ) . '</code><br>';
                echo '<a href="https://www.youtube.com/watch?v=' . esc_attr( $dup->video_id ) . '" target="_blank">▶️ Vedi su YouTube</a></td>';
                echo '<td><span style="background: #dc3545; color: white; padding: 2px 8px; border-radius: 3px;">' . $dup->count . '</span></td>';
                echo '<td>';
                foreach ( $ids as $post_id ) {
                    $post = get_post( $post_id );
                    if ( $post ) {
                        echo '<div style="margin: 5px 0;">';
                        echo '<a href="' . get_edit_post_link( $post_id ) . '" target="_blank"><strong>' . esc_html( $post->post_title ) . '</strong></a> ';
                        echo '<small>(ID: ' . $post_id . ')</small>';
                        echo '</div>';
                    }
                }
                echo '</td>';
                echo '<td><button class="button button-small button-link-delete ipv-delete-dups" data-post-ids="' . esc_attr( $dup->post_ids ) . '" data-video-id="' . esc_attr( $dup->video_id ) . '">Elimina Duplicati</button></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }
    }

    /**
     * Controlla media duplicati
     */
    private static function check_duplicate_media() {
        global $wpdb;

        echo '<h3>Controllo per Nome File Duplicato</h3>';

        $duplicates_by_filename = $wpdb->get_results("
            SELECT
                SUBSTRING_INDEX(guid, '/', -1) as filename,
                COUNT(*) as count,
                GROUP_CONCAT(ID ORDER BY ID) as attachment_ids,
                GROUP_CONCAT(guid ORDER BY ID SEPARATOR '|||') as urls
            FROM {$wpdb->posts}
            WHERE post_type = 'attachment'
            AND post_mime_type LIKE 'image/%'
            GROUP BY filename
            HAVING count > 1
            ORDER BY count DESC
            LIMIT 100
        ");

        if ( empty( $duplicates_by_filename ) ) {
            echo '<p style="color: #28a745;">✅ Nessuna immagine duplicata per nome file trovata (primi 100).</p>';
        } else {
            echo '<p style="color: #ffc107;">⚠️ Trovate <strong>' . count( $duplicates_by_filename ) . '</strong> immagini con nome file duplicato:</p>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Nome File</th><th>Duplicati</th><th>Anteprime</th><th>Info</th></tr></thead>';
            echo '<tbody>';

            foreach ( $duplicates_by_filename as $dup ) {
                $ids = explode( ',', $dup->attachment_ids );
                $urls = explode( '|||', $dup->urls );

                echo '<tr>';
                echo '<td><strong>' . esc_html( $dup->filename ) . '</strong></td>';
                echo '<td><span style="background: #ffc107; color: #000; padding: 2px 8px; border-radius: 3px;">' . $dup->count . '</span></td>';
                echo '<td><div style="display: flex; gap: 10px; flex-wrap: wrap;">';

                foreach ( $ids as $index => $att_id ) {
                    $thumb = wp_get_attachment_image_src( $att_id, 'thumbnail' );
                    $full = wp_get_attachment_image_src( $att_id, 'full' );

                    if ( $thumb ) {
                        echo '<div style="text-align: center;">';
                        echo '<a href="' . esc_url( $full[0] ) . '" target="_blank">';
                        echo '<img src="' . esc_url( $thumb[0] ) . '" style="max-width: 100px; border: 2px solid #ddd; border-radius: 4px;">';
                        echo '</a><br>';
                        echo '<small>ID: ' . $att_id . '</small><br>';
                        echo '<small>' . $full[1] . 'x' . $full[2] . '</small>';
                        echo '</div>';
                    }
                }

                echo '</div></td>';
                echo '<td>';
                foreach ( $ids as $att_id ) {
                    $file_size = filesize( get_attached_file( $att_id ) );
                    echo '<div><a href="' . get_edit_post_link( $att_id ) . '" target="_blank">ID: ' . $att_id . '</a> - ' . size_format( $file_size ) . '</div>';
                }
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        echo '<hr>';
        echo '<h3>Controllo per Dimensione File Identica</h3>';

        $duplicates_by_size = $wpdb->get_results("
            SELECT
                pm.meta_value as file_size,
                COUNT(*) as count,
                GROUP_CONCAT(pm.post_id ORDER BY pm.post_id) as attachment_ids
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_wp_attachment_metadata'
            AND p.post_type = 'attachment'
            AND p.post_mime_type LIKE 'image/%'
            GROUP BY file_size
            HAVING count > 1
            ORDER BY count DESC
            LIMIT 50
        ", ARRAY_A);

        if ( empty( $duplicates_by_size ) ) {
            echo '<p style="color: #28a745;">✅ Nessuna immagine duplicata per dimensione trovata (primi 50).</p>';
        } else {
            echo '<p style="color: #17a2b8;">ℹ️ Trovate <strong>' . count( $duplicates_by_size ) . '</strong> gruppi di immagini con dimensione identica.</p>';
            echo '<p><small>Nota: Stessa dimensione non significa necessariamente immagine duplicata. Controllare visivamente.</small></p>';
        }
    }

    /**
     * AJAX: Elimina post duplicati (mantiene solo il più vecchio)
     */
    public static function ajax_delete_duplicates() {
        check_ajax_referer( 'ipv_duplicate_checker_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permessi insufficienti' );
        }

        $post_ids_raw = isset( $_POST['post_ids'] ) ? sanitize_text_field( $_POST['post_ids'] ) : '';
        if ( empty( $post_ids_raw ) ) {
            wp_send_json_error( 'Nessun ID fornito' );
        }

        $post_ids = array_map( 'intval', explode( ',', $post_ids_raw ) );
        if ( count( $post_ids ) < 2 ) {
            wp_send_json_error( 'Servono almeno 2 post per eliminare duplicati' );
        }

        // Mantieni il più vecchio (primo ID), elimina gli altri
        $keep_id = $post_ids[0];
        $to_delete = array_slice( $post_ids, 1 );

        $deleted = [];
        $errors = [];

        foreach ( $to_delete as $post_id ) {
            $result = wp_delete_post( $post_id, true ); // true = force delete, skip trash
            if ( $result ) {
                $deleted[] = $post_id;
            } else {
                $errors[] = $post_id;
            }
        }

        wp_send_json_success( [
            'kept' => $keep_id,
            'deleted' => $deleted,
            'deleted_count' => count( $deleted ),
            'errors' => $errors,
            'message' => sprintf(
                'Mantenuto post ID %d, eliminati %d duplicati',
                $keep_id,
                count( $deleted )
            )
        ] );
    }
}

IPV_Prod_Duplicate_Checker::init();
