<?php
/**
 * IPV Production System Pro - Taxonomy Manager
 *
 * Gestione centralizzata delle tassonomie del CPT ipv_video
 *
 * @package IPV_Production_System_Pro
 * @version 9.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Taxonomy_Manager {

    /**
     * Init
     */
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_submenu' ], 100 );
        add_action( 'wp_ajax_ipv_create_taxonomy_term', [ __CLASS__, 'ajax_create_term' ] );
        add_action( 'wp_ajax_ipv_delete_taxonomy_term', [ __CLASS__, 'ajax_delete_term' ] );
    }

    /**
     * Aggiungi submenu
     */
    public static function add_submenu() {
        add_submenu_page(
            'ipv-production',
            __( 'Taxonomies', 'ipv-production-system-pro' ),
            '🏷️ ' . __( 'Taxonomies', 'ipv-production-system-pro' ),
            'manage_options',
            'ipv-production-taxonomies',
            [ __CLASS__, 'render_page' ]
        );
    }

    /**
     * Ottieni le tassonomie del CPT
     */
    public static function get_video_taxonomies() {
        return [
            'ipv_categoria' => [
                'name' => __( 'Video Categories', 'ipv-production-system-pro' ),
                'icon' => 'bi-folder',
                'color' => 'primary',
                'hierarchical' => true,
                'description' => __( 'Organize videos by topic or theme', 'ipv-production-system-pro' ),
            ],
            'ipv_relatore' => [
                'name' => __( 'Speakers / Guests', 'ipv-production-system-pro' ),
                'icon' => 'bi-person-video3',
                'color' => 'success',
                'hierarchical' => false,
                'description' => __( 'Tag videos with speakers or guests', 'ipv-production-system-pro' ),
            ],
            'post_tag' => [
                'name' => __( 'Tags', 'ipv-production-system-pro' ),
                'icon' => 'bi-tags',
                'color' => 'info',
                'hierarchical' => false,
                'description' => __( 'General tags for videos', 'ipv-production-system-pro' ),
            ],
        ];
    }

    /**
     * Render pagina
     */
    public static function render_page() {
        $taxonomies = self::get_video_taxonomies();
        $post_type = IPV_Prod_CPT::POST_TYPE;
        ?>
        <div class="wrap ipv-prod-wrap">
            <div class="ipv-prod-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-1">
                            <i class="bi bi-diagram-3 text-white me-2"></i>
                            <?php esc_html_e( 'Taxonomies', 'ipv-production-system-pro' ); ?>
                        </h1>
                        <p class="text-muted mb-0">
                            <?php esc_html_e( 'Manage categories, speakers, and tags for your videos', 'ipv-production-system-pro' ); ?>
                        </p>
                    </div>
                    <div>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $post_type ) ); ?>" class="btn btn-light">
                            <i class="bi bi-arrow-left me-1"></i>
                            <?php esc_html_e( 'Back to Videos', 'ipv-production-system-pro' ); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
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
                    <a class="nav-link active" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-taxonomies' ) ); ?>">
                        <i class="bi bi-diagram-3 me-1"></i><?php esc_html_e( 'Taxonomies', 'ipv-production-system-pro' ); ?>
                    </a>
                </li>
            </ul>

            <!-- Quick Stats -->
            <div class="row g-4 mb-4">
                <?php foreach ( $taxonomies as $taxonomy_slug => $tax_info ) : 
                    $terms = get_terms( [
                        'taxonomy' => $taxonomy_slug,
                        'hide_empty' => false,
                    ] );
                    $term_count = ! is_wp_error( $terms ) ? count( $terms ) : 0;
                ?>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100 border-<?php echo esc_attr( $tax_info['color'] ); ?> border-top border-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title mb-1">
                                        <i class="bi <?php echo esc_attr( $tax_info['icon'] ); ?> text-<?php echo esc_attr( $tax_info['color'] ); ?> me-2"></i>
                                        <?php echo esc_html( $tax_info['name'] ); ?>
                                    </h5>
                                    <p class="text-muted small mb-2"><?php echo esc_html( $tax_info['description'] ); ?></p>
                                </div>
                                <span class="badge bg-<?php echo esc_attr( $tax_info['color'] ); ?> fs-5"><?php echo esc_html( $term_count ); ?></span>
                            </div>
                            <div class="mt-3">
                                <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . $taxonomy_slug . '&post_type=' . $post_type ) ); ?>" 
                                   class="btn btn-sm btn-outline-<?php echo esc_attr( $tax_info['color'] ); ?>">
                                    <i class="bi bi-pencil me-1"></i>
                                    <?php esc_html_e( 'Manage', 'ipv-production-system-pro' ); ?>
                                </a>
                                <button type="button" class="btn btn-sm btn-<?php echo esc_attr( $tax_info['color'] ); ?> ipv-quick-add-term" 
                                        data-taxonomy="<?php echo esc_attr( $taxonomy_slug ); ?>"
                                        data-bs-toggle="modal" data-bs-target="#addTermModal">
                                    <i class="bi bi-plus-lg me-1"></i>
                                    <?php esc_html_e( 'Add New', 'ipv-production-system-pro' ); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Taxonomy Tables -->
            <div class="row g-4">
                <?php foreach ( $taxonomies as $taxonomy_slug => $tax_info ) : 
                    $terms = get_terms( [
                        'taxonomy' => $taxonomy_slug,
                        'hide_empty' => false,
                        'orderby' => 'count',
                        'order' => 'DESC',
                    ] );
                ?>
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="bi <?php echo esc_attr( $tax_info['icon'] ); ?> text-<?php echo esc_attr( $tax_info['color'] ); ?> me-2"></i>
                                <?php echo esc_html( $tax_info['name'] ); ?>
                            </h6>
                            <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . $taxonomy_slug . '&post_type=' . $post_type ) ); ?>" 
                               class="btn btn-sm btn-link p-0">
                                <?php esc_html_e( 'View All', 'ipv-production-system-pro' ); ?> →
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <?php if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) : ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th><?php esc_html_e( 'Name', 'ipv-production-system-pro' ); ?></th>
                                            <th class="text-center" style="width:60px;"><?php esc_html_e( 'Count', 'ipv-production-system-pro' ); ?></th>
                                            <th style="width:50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $displayed = array_slice( $terms, 0, 10 );
                                        foreach ( $displayed as $term ) : 
                                        ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo esc_url( admin_url( 'term.php?taxonomy=' . $taxonomy_slug . '&tag_ID=' . $term->term_id . '&post_type=' . $post_type ) ); ?>">
                                                    <?php echo esc_html( $term->name ); ?>
                                                </a>
                                                <?php if ( $tax_info['hierarchical'] && $term->parent > 0 ) : 
                                                    $parent = get_term( $term->parent, $taxonomy_slug );
                                                ?>
                                                    <small class="text-muted d-block">└ <?php echo esc_html( $parent->name ); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark"><?php echo esc_html( $term->count ); ?></span>
                                            </td>
                                            <td>
                                                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $post_type . '&' . $taxonomy_slug . '=' . $term->slug ) ); ?>" 
                                                   class="btn btn-sm btn-link p-0" title="<?php esc_attr_e( 'View Videos', 'ipv-production-system-pro' ); ?>">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if ( count( $terms ) > 10 ) : ?>
                                <div class="card-footer bg-light text-center py-2">
                                    <small class="text-muted">
                                        <?php printf( 
                                            esc_html__( '+ %d more terms', 'ipv-production-system-pro' ), 
                                            count( $terms ) - 10 
                                        ); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            <?php else : ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <?php esc_html_e( 'No terms yet', 'ipv-production-system-pro' ); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Quick Links -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-link-45deg text-secondary me-2"></i>
                        <?php esc_html_e( 'Quick Links', 'ipv-production-system-pro' ); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $post_type ) ); ?>" class="btn btn-outline-primary w-100">
                                <i class="bi bi-collection-play me-1"></i>
                                <?php esc_html_e( 'All Videos', 'ipv-production-system-pro' ); ?>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . $post_type ) ); ?>" class="btn btn-outline-success w-100">
                                <i class="bi bi-plus-circle me-1"></i>
                                <?php esc_html_e( 'Add New Video', 'ipv-production-system-pro' ); ?>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-import' ) ); ?>" class="btn btn-outline-warning w-100">
                                <i class="bi bi-cloud-download me-1"></i>
                                <?php esc_html_e( 'Import Video', 'ipv-production-system-pro' ); ?>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-bulk' ) ); ?>" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-tools me-1"></i>
                                <?php esc_html_e( 'Bulk Tools', 'ipv-production-system-pro' ); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Add Term -->
        <div class="modal fade" id="addTermModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle me-2"></i>
                            <?php esc_html_e( 'Add New Term', 'ipv-production-system-pro' ); ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="ipv-add-term-form">
                            <?php wp_nonce_field( 'ipv_taxonomy_nonce', 'ipv_tax_nonce' ); ?>
                            <input type="hidden" name="taxonomy" id="ipv-term-taxonomy" value="">
                            
                            <div class="mb-3">
                                <label class="form-label"><?php esc_html_e( 'Name', 'ipv-production-system-pro' ); ?> *</label>
                                <input type="text" class="form-control" name="term_name" id="ipv-term-name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><?php esc_html_e( 'Slug', 'ipv-production-system-pro' ); ?></label>
                                <input type="text" class="form-control" name="term_slug" id="ipv-term-slug" placeholder="<?php esc_attr_e( 'Auto-generated if empty', 'ipv-production-system-pro' ); ?>">
                            </div>
                            
                            <div class="mb-3" id="ipv-term-parent-wrap" style="display:none;">
                                <label class="form-label"><?php esc_html_e( 'Parent', 'ipv-production-system-pro' ); ?></label>
                                <select class="form-select" name="term_parent" id="ipv-term-parent">
                                    <option value="0"><?php esc_html_e( '— None —', 'ipv-production-system-pro' ); ?></option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><?php esc_html_e( 'Description', 'ipv-production-system-pro' ); ?></label>
                                <textarea class="form-control" name="term_description" id="ipv-term-description" rows="2"></textarea>
                            </div>
                        </form>
                        <div id="ipv-term-message" style="display:none;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <?php esc_html_e( 'Cancel', 'ipv-production-system-pro' ); ?>
                        </button>
                        <button type="button" class="btn btn-primary" id="ipv-save-term">
                            <i class="bi bi-check-lg me-1"></i>
                            <?php esc_html_e( 'Add Term', 'ipv-production-system-pro' ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var taxonomyInfo = <?php echo json_encode( $taxonomies ); ?>;
            
            // Quick Add button
            $('.ipv-quick-add-term').on('click', function() {
                var taxonomy = $(this).data('taxonomy');
                var info = taxonomyInfo[taxonomy];
                
                $('#ipv-term-taxonomy').val(taxonomy);
                $('#ipv-term-name').val('');
                $('#ipv-term-slug').val('');
                $('#ipv-term-description').val('');
                $('#ipv-term-parent').val('0');
                $('#ipv-term-message').hide();
                
                // Show/hide parent field for hierarchical taxonomies
                if (info && info.hierarchical) {
                    $('#ipv-term-parent-wrap').show();
                    loadParentTerms(taxonomy);
                } else {
                    $('#ipv-term-parent-wrap').hide();
                }
                
                $('.modal-title').html('<i class="bi bi-plus-circle me-2"></i> ' + (info ? info.name : 'Add Term'));
            });
            
            // Load parent terms
            function loadParentTerms(taxonomy) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_create_taxonomy_term',
                        nonce: $('#ipv_tax_nonce').val(),
                        get_terms: true,
                        taxonomy: taxonomy
                    },
                    success: function(response) {
                        if (response.success && response.data.terms) {
                            var $select = $('#ipv-term-parent');
                            $select.find('option:not(:first)').remove();
                            $.each(response.data.terms, function(i, term) {
                                $select.append('<option value="' + term.term_id + '">' + term.name + '</option>');
                            });
                        }
                    }
                });
            }
            
            // Save term
            $('#ipv-save-term').on('click', function() {
                var $btn = $(this);
                var $msg = $('#ipv-term-message');
                var originalText = $btn.html();
                
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> <?php echo esc_js( __( 'Saving...', 'ipv-production-system-pro' ) ); ?>');
                $msg.hide();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_create_taxonomy_term',
                        nonce: $('#ipv_tax_nonce').val(),
                        taxonomy: $('#ipv-term-taxonomy').val(),
                        term_name: $('#ipv-term-name').val(),
                        term_slug: $('#ipv-term-slug').val(),
                        term_parent: $('#ipv-term-parent').val(),
                        term_description: $('#ipv-term-description').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $msg.removeClass('alert-danger').addClass('alert alert-success')
                                .html('<i class="bi bi-check-circle me-1"></i> ' + response.data.message)
                                .fadeIn();
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            $msg.removeClass('alert-success').addClass('alert alert-danger')
                                .html('<i class="bi bi-exclamation-triangle me-1"></i> ' + response.data)
                                .fadeIn();
                        }
                    },
                    error: function() {
                        $msg.removeClass('alert-success').addClass('alert alert-danger')
                            .html('<i class="bi bi-exclamation-triangle me-1"></i> <?php echo esc_js( __( 'Connection error', 'ipv-production-system-pro' ) ); ?>')
                            .fadeIn();
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Crea nuovo termine
     */
    public static function ajax_create_term() {
        check_ajax_referer( 'ipv_taxonomy_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorized', 'ipv-production-system-pro' ) );
        }

        $taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_key( $_POST['taxonomy'] ) : '';
        
        // Get terms request
        if ( ! empty( $_POST['get_terms'] ) ) {
            $terms = get_terms( [
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            ] );
            
            wp_send_json_success( [ 'terms' => ! is_wp_error( $terms ) ? $terms : [] ] );
        }

        $term_name = isset( $_POST['term_name'] ) ? sanitize_text_field( $_POST['term_name'] ) : '';
        $term_slug = isset( $_POST['term_slug'] ) ? sanitize_title( $_POST['term_slug'] ) : '';
        $term_parent = isset( $_POST['term_parent'] ) ? intval( $_POST['term_parent'] ) : 0;
        $term_description = isset( $_POST['term_description'] ) ? sanitize_textarea_field( $_POST['term_description'] ) : '';

        if ( empty( $term_name ) ) {
            wp_send_json_error( __( 'Term name is required', 'ipv-production-system-pro' ) );
        }

        if ( ! taxonomy_exists( $taxonomy ) ) {
            wp_send_json_error( __( 'Invalid taxonomy', 'ipv-production-system-pro' ) );
        }

        $args = [
            'description' => $term_description,
            'parent' => $term_parent,
        ];

        if ( ! empty( $term_slug ) ) {
            $args['slug'] = $term_slug;
        }

        $result = wp_insert_term( $term_name, $taxonomy, $args );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( [
            'message' => __( 'Term created successfully!', 'ipv-production-system-pro' ),
            'term_id' => $result['term_id'],
        ] );
    }
}

// Init
IPV_Prod_Taxonomy_Manager::init();
