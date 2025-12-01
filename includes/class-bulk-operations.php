<?php
/**
 * IPV Production System Pro - Bulk Operations
 *
 * Bulk edit, scheduling, templates
 *
 * @package IPV_Production_System_Pro
 * @version 7.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Bulk_Operations {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_submenu' ], 25 );
        add_action( 'wp_ajax_ipv_bulk_edit', [ __CLASS__, 'ajax_bulk_edit' ] );
        add_action( 'wp_ajax_ipv_bulk_schedule', [ __CLASS__, 'ajax_bulk_schedule' ] );

        // Custom bulk actions
        add_filter( 'bulk_actions-edit-ipv_video', [ __CLASS__, 'register_bulk_actions' ] );
        add_filter( 'handle_bulk_actions-edit-ipv_video', [ __CLASS__, 'handle_bulk_actions' ], 10, 3 );
    }

    /**
     * Add submenu
     */
    public static function add_submenu() {
        add_submenu_page(
            'ipv-production',
            'Bulk Operations',
            '<span class="dashicons dashicons-admin-generic"></span> Bulk Ops',
            'manage_options',
            'ipv-bulk-operations',
            [ __CLASS__, 'render_page' ]
        );
    }

    /**
     * Render bulk operations page
     */
    public static function render_page() {
        ?>
        <div class="wrap">
            <h1>⚙️ Bulk Operations</h1>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Bulk Category/Tag Assignment</h2>
                <form id="ipv-bulk-edit-form">
                    <table class="form-table">
                        <tr>
                            <th>Video IDs</th>
                            <td>
                                <textarea name="video_ids" class="large-text" rows="3" placeholder="123, 456, 789"></textarea>
                                <p class="description">Inserisci gli ID video separati da virgola</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Categoria</th>
                            <td>
                                <select name="category">
                                    <option value="">-- Nessun cambio --</option>
                                    <?php
                                    $categories = get_terms( [ 'taxonomy' => 'ipv_categoria', 'hide_empty' => false ] );
                                    foreach ( $categories as $cat ) {
                                        echo '<option value="' . esc_attr( $cat->term_id ) . '">' . esc_html( $cat->name ) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Relatore</th>
                            <td>
                                <select name="speaker">
                                    <option value="">-- Nessun cambio --</option>
                                    <?php
                                    $speakers = get_terms( [ 'taxonomy' => 'ipv_relatore', 'hide_empty' => false ] );
                                    foreach ( $speakers as $speaker ) {
                                        echo '<option value="' . esc_attr( $speaker->term_id ) . '">' . esc_html( $speaker->name ) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <select name="post_status">
                                    <option value="">-- Nessun cambio --</option>
                                    <option value="publish">Pubblica</option>
                                    <option value="draft">Bozza</option>
                                    <option value="pending">In Revisione</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <p>
                        <button type="submit" class="button button-primary">⚡ Applica Modifiche</button>
                    </p>
                </form>
                <div id="ipv-bulk-result"></div>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Auto-Publish Scheduler</h2>
                <form id="ipv-bulk-schedule-form">
                    <table class="form-table">
                        <tr>
                            <th>Video IDs Draft</th>
                            <td>
                                <textarea name="draft_ids" class="large-text" rows="3" placeholder="123, 456, 789"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th>Intervallo Pubblicazione</th>
                            <td>
                                <input type="number" name="interval_hours" value="24" min="1" max="168" style="width: 80px;"> ore
                                <p class="description">Pubblica un video ogni X ore</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Data Inizio</th>
                            <td>
                                <input type="datetime-local" name="start_date" value="<?php echo date( 'Y-m-d\TH:i' ); ?>">
                            </td>
                        </tr>
                    </table>
                    <p>
                        <button type="submit" class="button button-primary">📅 Schedule Pubblicazioni</button>
                    </p>
                </form>
                <div id="ipv-schedule-result"></div>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Template Descriptions</h2>
                <p>Salva e riusa descrizioni predefinite</p>
                <?php
                $templates = get_option( 'ipv_description_templates', [] );
                ?>
                <textarea id="ipv-new-template" class="large-text" rows="5" placeholder="Scrivi il tuo template..."></textarea>
                <p>
                    <input type="text" id="ipv-template-name" placeholder="Nome template" style="width: 200px;">
                    <button type="button" id="ipv-save-template" class="button">💾 Salva Template</button>
                </p>

                <?php if ( ! empty( $templates ) ) : ?>
                    <h3>Templates Salvati</h3>
                    <ul>
                        <?php foreach ( $templates as $name => $content ) : ?>
                            <li>
                                <strong><?php echo esc_html( $name ); ?></strong>
                                <button class="button button-small ipv-use-template" data-content="<?php echo esc_attr( $content ); ?>">Usa</button>
                                <button class="button button-small button-link-delete ipv-delete-template" data-name="<?php echo esc_attr( $name ); ?>">Elimina</button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Bulk edit
            $('#ipv-bulk-edit-form').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData + '&action=ipv_bulk_edit&nonce=<?php echo wp_create_nonce( 'ipv_bulk_nonce' ); ?>',
                    success: function(response) {
                        if (response.success) {
                            $('#ipv-bulk-result').html('<div class="notice notice-success"><p>✅ ' + response.data.message + '</p></div>');
                        } else {
                            $('#ipv-bulk-result').html('<div class="notice notice-error"><p>❌ ' + response.data + '</p></div>');
                        }
                    }
                });
            });

            // Bulk schedule
            $('#ipv-bulk-schedule-form').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData + '&action=ipv_bulk_schedule&nonce=<?php echo wp_create_nonce( 'ipv_bulk_nonce' ); ?>',
                    success: function(response) {
                        if (response.success) {
                            $('#ipv-schedule-result').html('<div class="notice notice-success"><p>✅ ' + response.data.message + '</p></div>');
                        } else {
                            $('#ipv-schedule-result').html('<div class="notice notice-error"><p>❌ ' + response.data + '</p></div>');
                        }
                    }
                });
            });

            // Save template
            $('#ipv-save-template').on('click', function() {
                const content = $('#ipv-new-template').val();
                const name = $('#ipv-template-name').val();

                if (!content || !name) {
                    alert('Compila entrambi i campi');
                    return;
                }

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_save_template',
                        name: name,
                        content: content,
                        nonce: '<?php echo wp_create_nonce( 'ipv_bulk_nonce' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Template salvato!');
                            location.reload();
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Bulk edit
     */
    public static function ajax_bulk_edit() {
        check_ajax_referer( 'ipv_bulk_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $video_ids = array_map( 'intval', explode( ',', $_POST['video_ids'] ?? '' ) );
        $category = isset( $_POST['category'] ) ? absint( $_POST['category'] ) : 0;
        $speaker = isset( $_POST['speaker'] ) ? absint( $_POST['speaker'] ) : 0;
        $post_status = isset( $_POST['post_status'] ) ? sanitize_text_field( $_POST['post_status'] ) : '';

        $updated = 0;
        foreach ( $video_ids as $post_id ) {
            if ( get_post_type( $post_id ) !== 'ipv_video' ) {
                continue;
            }

            if ( $category ) {
                wp_set_object_terms( $post_id, $category, 'ipv_categoria' );
            }

            if ( $speaker ) {
                wp_set_object_terms( $post_id, $speaker, 'ipv_relatore' );
            }

            if ( $post_status ) {
                wp_update_post( [ 'ID' => $post_id, 'post_status' => $post_status ] );
            }

            $updated++;
        }

        wp_send_json_success( [ 'message' => "{$updated} video aggiornati" ] );
    }

    /**
     * AJAX: Bulk schedule
     */
    public static function ajax_bulk_schedule() {
        check_ajax_referer( 'ipv_bulk_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $draft_ids = array_map( 'intval', explode( ',', $_POST['draft_ids'] ?? '' ) );
        $interval_hours = absint( $_POST['interval_hours'] ?? 24 );
        $start_date = sanitize_text_field( $_POST['start_date'] ?? '' );

        $scheduled = 0;
        $timestamp = strtotime( $start_date );

        foreach ( $draft_ids as $post_id ) {
            if ( get_post_type( $post_id ) !== 'ipv_video' ) {
                continue;
            }

            wp_update_post( [
                'ID' => $post_id,
                'post_status' => 'future',
                'post_date' => date( 'Y-m-d H:i:s', $timestamp ),
                'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $timestamp ),
            ] );

            $timestamp += ( $interval_hours * HOUR_IN_SECONDS );
            $scheduled++;
        }

        wp_send_json_success( [ 'message' => "{$scheduled} video schedulati" ] );
    }

    /**
     * Register custom bulk actions
     */
    public static function register_bulk_actions( $actions ) {
        $actions['ipv_publish'] = 'Pubblica Selezionati';
        $actions['ipv_draft'] = 'Metti in Bozza';
        return $actions;
    }

    /**
     * Handle custom bulk actions
     */
    public static function handle_bulk_actions( $redirect, $action, $post_ids ) {
        if ( $action === 'ipv_publish' ) {
            foreach ( $post_ids as $post_id ) {
                wp_update_post( [ 'ID' => $post_id, 'post_status' => 'publish' ] );
            }
            $redirect = add_query_arg( 'bulk_published', count( $post_ids ), $redirect );
        }

        if ( $action === 'ipv_draft' ) {
            foreach ( $post_ids as $post_id ) {
                wp_update_post( [ 'ID' => $post_id, 'post_status' => 'draft' ] );
            }
            $redirect = add_query_arg( 'bulk_drafted', count( $post_ids ), $redirect );
        }

        return $redirect;
    }
}

IPV_Prod_Bulk_Operations::init();
