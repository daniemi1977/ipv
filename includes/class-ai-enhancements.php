<?php
/**
 * IPV Production System Pro - AI Enhancements
 *
 * Summary generation, auto-tagging, topic extraction
 *
 * @package IPV_Production_System_Pro
 * @version 7.11.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_AI_Enhancements {

    public static function init() {
        // AJAX handlers
        add_action( 'wp_ajax_ipv_generate_summary', [ __CLASS__, 'ajax_generate_summary' ] );
        add_action( 'wp_ajax_ipv_auto_tag', [ __CLASS__, 'ajax_auto_tag' ] );
        add_action( 'wp_ajax_ipv_extract_topics', [ __CLASS__, 'ajax_extract_topics' ] );

        // Auto-generate summary on post save (if enabled)
        add_action( 'save_post_ipv_video', [ __CLASS__, 'auto_generate_summary_on_save' ], 20, 2 );

        // Add meta box for AI tools
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_ai_meta_box' ] );
    }

    /**
     * Add AI tools meta box to video edit page
     */
    public static function add_ai_meta_box() {
        add_meta_box(
            'ipv_ai_tools',
            '🤖 AI Tools',
            [ __CLASS__, 'render_ai_meta_box' ],
            'ipv_video',
            'side',
            'high'
        );
    }

    /**
     * Render AI tools meta box
     */
    public static function render_ai_meta_box( $post ) {
        $summary = get_post_meta( $post->ID, '_ipv_ai_summary', true );
        ?>
        <div class="ipv-ai-tools">
            <p>
                <button type="button" class="button button-secondary ipv-generate-summary" data-post-id="<?php echo $post->ID; ?>">
                    📝 Genera Summary (SEO)
                </button>
            </p>
            <?php if ( $summary ) : ?>
                <div class="ipv-summary-preview" style="background: #f0f0f0; padding: 10px; border-radius: 4px; margin-top: 10px;">
                    <strong>Summary:</strong>
                    <p style="font-size: 12px; margin: 5px 0 0 0;"><?php echo esc_html( $summary ); ?></p>
                </div>
            <?php endif; ?>

            <p style="margin-top: 15px;">
                <button type="button" class="button button-secondary ipv-auto-tag" data-post-id="<?php echo $post->ID; ?>">
                    🏷️ Auto-Tag da Transcript
                </button>
            </p>

            <p>
                <button type="button" class="button button-secondary ipv-extract-topics" data-post-id="<?php echo $post->ID; ?>">
                    🔍 Estrai Topics
                </button>
            </p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.ipv-generate-summary').on('click', function() {
                var $btn = $(this);
                var postId = $btn.data('post-id');
                $btn.prop('disabled', true).text('Generando...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_generate_summary',
                        post_id: postId,
                        nonce: '<?php echo wp_create_nonce( 'ipv_ai_nonce' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Summary generato con successo!');
                            location.reload();
                        } else {
                            alert('❌ Errore: ' + response.data);
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('📝 Genera Summary (SEO)');
                    }
                });
            });

            $('.ipv-auto-tag').on('click', function() {
                var $btn = $(this);
                var postId = $btn.data('post-id');
                $btn.prop('disabled', true).text('Taggando...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_auto_tag',
                        post_id: postId,
                        nonce: '<?php echo wp_create_nonce( 'ipv_ai_nonce' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Tags generati: ' + response.data.tags.join(', '));
                            location.reload();
                        } else {
                            alert('❌ Errore: ' + response.data);
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('🏷️ Auto-Tag da Transcript');
                    }
                });
            });

            $('.ipv-extract-topics').on('click', function() {
                var $btn = $(this);
                var postId = $btn.data('post-id');
                $btn.prop('disabled', true).text('Estraendo...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_extract_topics',
                        post_id: postId,
                        nonce: '<?php echo wp_create_nonce( 'ipv_ai_nonce' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Topics: ' + response.data.topics.join(', '));
                        } else {
                            alert('❌ Errore: ' + response.data);
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('🔍 Estrai Topics');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Generate summary
     */
    public static function ajax_generate_summary() {
        check_ajax_referer( 'ipv_ai_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( 'Invalid post ID' );
        }

        $summary = self::generate_summary( $post_id );

        if ( is_wp_error( $summary ) ) {
            wp_send_json_error( $summary->get_error_message() );
        }

        update_post_meta( $post_id, '_ipv_ai_summary', $summary );

        wp_send_json_success( [
            'summary' => $summary,
            'length' => strlen( $summary ),
        ] );
    }

    /**
     * Generate SEO summary from transcript
     *
     * v10.0.0 CLOUD EDITION: Usa API Client
     */
    public static function generate_summary( $post_id ) {
        // v10.0: Usa API Client
        if ( ! class_exists( 'IPV_Prod_API_Client' ) ) {
            return new WP_Error( 'no_api_client', 'API Client non disponibile. Aggiorna il plugin alla v10.0+' );
        }

        $transcript = get_post_meta( $post_id, '_ipv_transcript', true );

        if ( empty( $transcript ) ) {
            return new WP_Error( 'no_transcript', 'Trascrizione non disponibile' );
        }

        $video_title = get_the_title( $post_id );

        // Truncate transcript for API limits (max 4000 chars)
        $transcript_excerpt = substr( $transcript, 0, 4000 );

        $prompt = "Genera un summary SEO-friendly di massimo 160 caratteri per questo video. Deve essere coinvolgente e contenere keywords rilevanti.\n\nTrascrizione:\n{$transcript_excerpt}\n\nSummary:";

        IPV_Prod_Logger::log( 'AI Summary: Chiamata via API Client', [ 'post_id' => $post_id ] );

        $api_client = IPV_Prod_API_Client::instance();
        $summary = $api_client->generate_description( $prompt, $video_title, '' );

        if ( is_wp_error( $summary ) ) {
            IPV_Prod_Logger::log( 'AI Summary: Errore', [ 'error' => $summary->get_error_message() ] );
            return $summary;
        }

        $summary = trim( $summary );

        // Ensure max 160 chars
        if ( strlen( $summary ) > 160 ) {
            $summary = substr( $summary, 0, 157 ) . '...';
        }

        IPV_Prod_Logger::log( 'AI Summary generated via vendor', [ 'post_id' => $post_id, 'length' => strlen( $summary ) ] );

        return $summary;
    }

    /**
     * AJAX: Auto-tag from transcript
     */
    public static function ajax_auto_tag() {
        check_ajax_referer( 'ipv_ai_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( 'Invalid post ID' );
        }

        $tags = self::auto_tag_from_transcript( $post_id );

        if ( is_wp_error( $tags ) ) {
            wp_send_json_error( $tags->get_error_message() );
        }

        // Set tags
        wp_set_post_tags( $post_id, $tags, false );

        wp_send_json_success( [
            'tags' => $tags,
            'count' => count( $tags ),
        ] );
    }

    /**
     * Auto-tag video from transcript
     */
    private static function auto_tag_from_transcript( $post_id ) {
        $transcript = get_post_meta( $post_id, '_ipv_transcript', true );

        if ( empty( $transcript ) ) {
            return new WP_Error( 'no_transcript', 'Trascrizione non disponibile' );
        }

        // Extract keywords using simple word frequency
        $words = str_word_count( strtolower( $transcript ), 1, 'àèéìòù' );

        // Stop words italiani
        $stop_words = [ 'il', 'lo', 'la', 'i', 'gli', 'le', 'un', 'uno', 'una', 'di', 'da', 'in', 'con', 'su', 'per', 'tra', 'fra', 'a', 'del', 'dello', 'della', 'dei', 'degli', 'delle', 'al', 'allo', 'alla', 'ai', 'agli', 'alle', 'nel', 'nello', 'nella', 'nei', 'negli', 'nelle', 'sul', 'sullo', 'sulla', 'sui', 'sugli', 'sulle', 'e', 'è', 'o', 'che', 'chi', 'cui', 'non', 'più', 'anche', 'come', 'ma', 'se', 'ci', 'si', 'ne', 'questo', 'questa', 'questi', 'queste', 'quello', 'quella', 'quelli', 'quelle', 'sono', 'stato', 'essere', 'avere', 'fare', 'dire', 'tutto', 'tutti', 'molto', 'cosa', 'poi', 'ancora', 'già', 'sempre', 'ora', 'qui', 'dove', 'quando', 'quindi', 'però', 'mentre', 'dopo', 'prima', 'solo', 'suo', 'sua', 'loro', 'mio', 'tuo', 'nostro', 'vostro' ];

        $freq = [];
        foreach ( $words as $word ) {
            if ( strlen( $word ) > 4 && ! in_array( $word, $stop_words, true ) && ! is_numeric( $word ) ) {
                if ( ! isset( $freq[ $word ] ) ) {
                    $freq[ $word ] = 0;
                }
                $freq[ $word ]++;
            }
        }

        // Sort by frequency
        arsort( $freq );

        // Get top 10 tags
        $tags = array_slice( array_keys( $freq ), 0, 10 );

        // Capitalize first letter
        $tags = array_map( 'ucfirst', $tags );

        IPV_Prod_Logger::log( 'Auto-tags generated', [ 'post_id' => $post_id, 'count' => count( $tags ) ] );

        return $tags;
    }

    /**
     * AJAX: Extract topics
     */
    public static function ajax_extract_topics() {
        check_ajax_referer( 'ipv_ai_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( 'Invalid post ID' );
        }

        $topics = self::extract_topics( $post_id );

        if ( is_wp_error( $topics ) ) {
            wp_send_json_error( $topics->get_error_message() );
        }

        wp_send_json_success( [
            'topics' => $topics,
            'count' => count( $topics ),
        ] );
    }

    /**
     * Extract main topics from transcript
     */
    private static function extract_topics( $post_id ) {
        $transcript = get_post_meta( $post_id, '_ipv_transcript', true );

        if ( empty( $transcript ) ) {
            return new WP_Error( 'no_transcript', 'Trascrizione non disponibile' );
        }

        // Simple topic extraction using sentence analysis
        $sentences = preg_split( '/[.!?]+/', $transcript );

        $topics = [];
        foreach ( $sentences as $sentence ) {
            $sentence = trim( $sentence );
            if ( strlen( $sentence ) < 20 ) {
                continue; // Skip short sentences
            }

            // Extract noun phrases (simplified)
            $words = explode( ' ', strtolower( $sentence ) );
            if ( count( $words ) > 3 ) {
                // Take first 3-4 words as potential topic
                $topic = implode( ' ', array_slice( $words, 0, 4 ) );
                $topic = ucfirst( trim( $topic ) );

                if ( strlen( $topic ) > 10 && ! in_array( $topic, $topics, true ) ) {
                    $topics[] = $topic;
                }
            }

            if ( count( $topics ) >= 5 ) {
                break; // Max 5 topics
            }
        }

        return $topics;
    }

    /**
     * Auto-generate summary on post save (if transcript exists)
     */
    public static function auto_generate_summary_on_save( $post_id, $post ) {
        // Skip if autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Skip if summary already exists
        $existing_summary = get_post_meta( $post_id, '_ipv_ai_summary', true );
        if ( ! empty( $existing_summary ) ) {
            return;
        }

        // Only if transcript exists
        $transcript = get_post_meta( $post_id, '_ipv_transcript', true );
        if ( empty( $transcript ) ) {
            return;
        }

        // Generate summary in background (don't block save)
        $summary = self::generate_summary( $post_id );

        if ( ! is_wp_error( $summary ) ) {
            update_post_meta( $post_id, '_ipv_ai_summary', $summary );
        }
    }
}

IPV_Prod_AI_Enhancements::init();
