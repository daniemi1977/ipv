<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_YouTube_Importer {

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $notice = '';
        if ( isset( $_GET['success'] ) && '1' === $_GET['success'] ) {
            $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
            $edit_link = $post_id ? get_edit_post_link( $post_id, 'raw' ) : '';
            $notice = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>' . esc_html__( 'Video imported and published!', 'ipv-production-system-pro' ) . '</strong>
                ' . ( $edit_link ? '<a href="' . esc_url( $edit_link ) . '" class="alert-link">' . esc_html__( 'Edit video', 'ipv-production-system-pro' ) . '</a>' : '' ) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        } elseif ( isset( $_GET['error'] ) ) {
            $msg = sanitize_text_field( wp_unslash( $_GET['error'] ) );
            $notice = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>' . esc_html__( 'Error:', 'ipv-production-system-pro' ) . '</strong> ' . esc_html( $msg ) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        }

        ?>
        <div class="wrap ipv-prod-wrap">
            <div class="ipv-prod-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-1">
                            <i class="bi bi-upload text-white me-2"></i>
                            <?php esc_html_e( 'Import YouTube Video', 'ipv-production-system-pro' ); ?>
                        </h1>
                        <p class="text-muted mb-0"><?php esc_html_e( 'Add videos to the production queue', 'ipv-production-system-pro' ); ?></p>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production' ) ); ?>">
                        <i class="bi bi-speedometer2 me-1"></i><?php esc_html_e( 'Dashboard', 'ipv-production-system-pro' ); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-import' ) ); ?>">
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
            </ul>

            <?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

            <div class="row g-4">
                <!-- Import Form -->
                <div class="col-lg-7">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-plus-circle-fill text-primary me-2"></i>
                                <?php esc_html_e( 'Add New Video', 'ipv-production-system-pro' ); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ipv-form">
                                <?php wp_nonce_field( 'ipv_simple_import_nonce' ); ?>
                                <input type="hidden" name="action" value="ipv_simple_import" />

                                <div class="mb-4">
                                    <label for="youtube_url" class="form-label">
                                        <i class="bi bi-youtube text-danger me-1"></i>
                                        <?php esc_html_e( 'YouTube URL', 'ipv-production-system-pro' ); ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="url"
                                           id="youtube_url"
                                           name="youtube_url"
                                           class="form-control form-control-lg"
                                           placeholder="https://www.youtube.com/watch?v=..."
                                           required />
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <?php esc_html_e( 'Paste the video URL - it will be imported and published immediately!', 'ipv-production-system-pro' ); ?>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-lightning me-1"></i>
                                        <?php esc_html_e( 'Import and Publish', 'ipv-production-system-pro' ); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="card shadow-sm mt-4">
                        <div class="card-body">
                            <h6 class="mb-3">
                                <i class="bi bi-lightbulb-fill text-warning me-2"></i>
                                <?php esc_html_e( 'Supported URL formats', 'ipv-production-system-pro' ); ?>
                            </h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <code>https://www.youtube.com/watch?v=VIDEO_ID</code>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <code>https://youtu.be/VIDEO_ID</code>
                                </li>
                                <li>
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <code>https://www.youtube.com/watch?v=VIDEO_ID&t=123s</code>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Info -->
                <div class="col-lg-5">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-info-circle-fill text-info me-2"></i>
                                <?php esc_html_e( 'How It Works', 'ipv-production-system-pro' ); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="ipv-process-step mb-4">
                                <div class="d-flex">
                                    <div class="ipv-step-number me-3">
                                        <div class="badge bg-primary rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                            1
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php esc_html_e( 'Video Import', 'ipv-production-system-pro' ); ?></h6>
                                        <p class="text-muted mb-0 small">
                                            <?php esc_html_e( 'The video is added to the queue and the CPT is created', 'ipv-production-system-pro' ); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="ipv-process-step mb-4">
                                <div class="d-flex">
                                    <div class="ipv-step-number me-3">
                                        <div class="badge bg-info rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                            2
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php esc_html_e( 'Transcription Generation', 'ipv-production-system-pro' ); ?></h6>
                                        <p class="text-muted mb-0 small">
                                            <?php esc_html_e( 'SupaData API extracts the video transcription', 'ipv-production-system-pro' ); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="ipv-process-step mb-4">
                                <div class="d-flex">
                                    <div class="ipv-step-number me-3">
                                        <div class="badge bg-warning rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                            3
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php esc_html_e( 'AI Generation', 'ipv-production-system-pro' ); ?></h6>
                                        <p class="text-muted mb-0 small">
                                            <?php esc_html_e( 'OpenAI generates the description using the Golden Prompt', 'ipv-production-system-pro' ); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="ipv-process-step">
                                <div class="d-flex">
                                    <div class="ipv-step-number me-3">
                                        <div class="badge bg-success rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                            4
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php esc_html_e( 'Publication', 'ipv-production-system-pro' ); ?></h6>
                                        <p class="text-muted mb-0 small">
                                            <?php esc_html_e( 'The description is saved in the post ready to use', 'ipv-production-system-pro' ); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="card shadow-sm mt-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-graph-up text-success me-2"></i>
                                <?php esc_html_e( 'Quick Statistics', 'ipv-production-system-pro' ); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $stats = IPV_Prod_Queue::get_stats();
                            $total = array_sum( $stats );
                            ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted"><?php esc_html_e( 'Total Videos', 'ipv-production-system-pro' ); ?></span>
                                <span class="badge bg-primary fs-6"><?php echo intval( $total ); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted"><?php esc_html_e( 'In Queue', 'ipv-production-system-pro' ); ?></span>
                                <span class="badge bg-warning text-dark fs-6"><?php echo intval( $stats['pending'] ); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted"><?php esc_html_e( 'Completed', 'ipv-production-system-pro' ); ?></span>
                                <span class="badge bg-success fs-6"><?php echo intval( $stats['done'] ); ?></span>
                            </div>
                        </div>
                        <div class="card-footer bg-light text-center">
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-queue' ) ); ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye me-1"></i>
                                <?php esc_html_e( 'View Full Queue', 'ipv-production-system-pro' ); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public static function handle_form_submit() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Non autorizzato.' );
        }

        if ( ! isset( $_POST['ipv_prod_import_video_nonce'] ) || ! wp_verify_nonce( $_POST['ipv_prod_import_video_nonce'], 'ipv_prod_import_video' ) ) {
            wp_die( 'Nonce non valido.' );
        }

        $url    = isset( $_POST['ipv_youtube_url'] ) ? esc_url_raw( wp_unslash( $_POST['ipv_youtube_url'] ) ) : '';
        $source = isset( $_POST['ipv_source'] ) ? sanitize_text_field( wp_unslash( $_POST['ipv_source'] ) ) : 'manual';

        if ( empty( $url ) ) {
            self::redirect_with_error( 'URL mancante.' );
        }

        $video_id = self::extract_video_id( $url );
        if ( ! $video_id ) {
            self::redirect_with_error( 'Impossibile estrarre il Video ID dall\'URL fornito.' );
        }

        IPV_Prod_Queue::enqueue( $video_id, $url, $source );

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'               => 'ipv-production-import',
                    'ipv_import_success' => '1',
                ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    protected static function redirect_with_error( $msg ) {
        wp_safe_redirect(
            add_query_arg(
                [
                    'page'              => 'ipv-production-import',
                    'ipv_import_error'  => rawurlencode( $msg ),
                ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    protected static function extract_video_id( $url ) {
        // Standard YouTube URL
        if ( preg_match( '/[?&]v=([^&]+)/', $url, $matches ) ) {
            return $matches[1];
        }

        // Short YouTube URL
        if ( preg_match( '/youtu\.be\/([^?&]+)/', $url, $matches ) ) {
            return $matches[1];
        }

        return false;
    }
}

add_action( 'admin_post_ipv_prod_import_video', [ 'IPV_Prod_YouTube_Importer', 'handle_form_submit' ] );
