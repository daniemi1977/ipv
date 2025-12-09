<?php
/**
 * IPV Production System Pro - QR Code Generator
 *
 * Generate QR codes for videos
 *
 * @package IPV_Production_System_Pro
 * @version 7.12.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_QR_Generator {

    public static function init() {
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_box' ] );
        add_action( 'wp_ajax_ipv_generate_qr', [ __CLASS__, 'ajax_generate_qr' ] );
    }

    /**
     * Add QR code meta box
     */
    public static function add_meta_box() {
        add_meta_box(
            'ipv_qr_code',
            '📱 QR Code',
            [ __CLASS__, 'render_meta_box' ],
            'ipv_video',
            'side',
            'default'
        );
    }

    /**
     * Render QR code meta box
     */
    public static function render_meta_box( $post ) {
        $permalink = get_permalink( $post->ID );
        $qr_code = get_post_meta( $post->ID, '_ipv_qr_code_url', true );

        ?>
        <div class="ipv-qr-code-box">
            <?php if ( $qr_code ) : ?>
                <div style="text-align: center; margin-bottom: 15px;">
                    <img src="<?php echo esc_url( $qr_code ); ?>" alt="QR Code" style="max-width: 100%; height: auto;">
                </div>
                <p>
                    <a href="<?php echo esc_url( $qr_code ); ?>" download="qr-<?php echo $post->ID; ?>.png" class="button button-secondary" style="width: 100%;">
                        ⬇️ Download QR Code
                    </a>
                </p>
            <?php else : ?>
                <p>Nessun QR code generato ancora.</p>
            <?php endif; ?>

            <p>
                <button type="button" class="button button-primary ipv-generate-qr" data-post-id="<?php echo $post->ID; ?>" style="width: 100%;">
                    <?php echo $qr_code ? '🔄 Rigenera' : '✨ Genera'; ?> QR Code
                </button>
            </p>

            <p class="description">
                URL: <code style="font-size: 11px; word-break: break-all;"><?php echo esc_html( $permalink ); ?></code>
            </p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.ipv-generate-qr').on('click', function() {
                const $btn = $(this);
                const postId = $btn.data('post-id');

                $btn.prop('disabled', true).text('⏳ Generando...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_generate_qr',
                        post_id: postId,
                        nonce: '<?php echo wp_create_nonce( 'ipv_qr_nonce' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ QR Code generato!');
                            location.reload();
                        } else {
                            alert('❌ Errore: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('❌ Errore di connessione');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('✨ Genera QR Code');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Generate QR code
     */
    public static function ajax_generate_qr() {
        check_ajax_referer( 'ipv_qr_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( 'Invalid post ID' );
        }

        $permalink = get_permalink( $post_id );
        $qr_url = self::generate_qr_code( $permalink, $post_id );

        if ( is_wp_error( $qr_url ) ) {
            wp_send_json_error( $qr_url->get_error_message() );
        }

        update_post_meta( $post_id, '_ipv_qr_code_url', $qr_url );

        wp_send_json_success( [
            'qr_url' => $qr_url,
        ] );
    }

    /**
     * Generate QR code using API
     */
    private static function generate_qr_code( $url, $post_id ) {
        // Use QR Code API (free service)
        $api_url = 'https://api.qrserver.com/v1/create-qr-code/';
        $qr_url = add_query_arg( [
            'size' => '300x300',
            'data' => $url,
            'format' => 'png',
        ], $api_url );

        // Download and save to media library
        $response = wp_remote_get( $qr_url, [ 'timeout' => 30 ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $image_data = wp_remote_retrieve_body( $response );

        if ( empty( $image_data ) ) {
            return new WP_Error( 'no_data', 'Nessun dato ricevuto dal servizio QR' );
        }

        // Save to uploads
        $upload = wp_upload_bits( 'qr-' . $post_id . '-' . time() . '.png', null, $image_data );

        if ( $upload['error'] ) {
            return new WP_Error( 'upload_error', $upload['error'] );
        }

        IPV_Prod_Logger::log( 'QR Code generated', [ 'post_id' => $post_id, 'url' => $upload['url'] ] );

        return $upload['url'];
    }

    /**
     * Get QR code for video
     */
    public static function get_qr_code( $post_id ) {
        $qr_url = get_post_meta( $post_id, '_ipv_qr_code_url', true );

        if ( ! $qr_url ) {
            // Generate on demand
            $permalink = get_permalink( $post_id );
            $qr_url = self::generate_qr_code( $permalink, $post_id );

            if ( ! is_wp_error( $qr_url ) ) {
                update_post_meta( $post_id, '_ipv_qr_code_url', $qr_url );
            }
        }

        return $qr_url;
    }
}

IPV_Prod_QR_Generator::init();
