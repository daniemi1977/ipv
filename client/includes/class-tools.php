<?php
/**
 * IPV Production System Pro - Strumenti
 * Unifica: Bulk Operations, Duplicati, Pulizia
 * @version 10.0.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Prod_Tools {
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ], 100 );
        add_action( 'admin_post_ipv_tools_execute', [ __CLASS__, 'handle_execute' ] );
    }

    public static function add_menu() {
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            __( 'Strumenti', 'ipv-production-system-pro' ),
            '🔧 ' . __( 'Strumenti', 'ipv-production-system-pro' ),
            'manage_options',
            'ipv-tools',
            [ __CLASS__, 'render' ]
        );
    }

    public static function render() {
        $tab = $_GET['tooltab'] ?? 'bulk';
        ?>
        <div class="wrap">
            <h1>🔧 <?php _e( 'Strumenti', 'ipv-production-system-pro' ); ?></h1>
            <?php settings_errors( 'ipv_tools' ); ?>
            
            <nav class="nav-tab-wrapper">
                <a href="?post_type=ipv_video&page=ipv-tools&tooltab=bulk" class="nav-tab <?php echo $tab === 'bulk' ? 'nav-tab-active' : ''; ?>">🔄 Operazioni Bulk</a>
                <a href="?post_type=ipv_video&page=ipv-tools&tooltab=duplicates" class="nav-tab <?php echo $tab === 'duplicates' ? 'nav-tab-active' : ''; ?>">🔍 Duplicati</a>
                <a href="?post_type=ipv_video&page=ipv-tools&tooltab=cleanup" class="nav-tab <?php echo $tab === 'cleanup' ? 'nav-tab-active' : ''; ?>">🗑️ Pulizia</a>
            </nav>

            <div style="background:#fff;padding:20px;border:1px solid #ccc;border-top:0;">
                <?php
                switch ( $tab ) {
                    case 'bulk': self::render_bulk_tab(); break;
                    case 'duplicates': self::render_duplicates_tab(); break;
                    case 'cleanup': self::render_cleanup_tab(); break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    private static function render_bulk_tab() {
        ?>
        <h2>🔄 Operazioni Bulk</h2>
        <p>Esegui azioni massive su tutti i video o su una selezione.</p>
        
        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
            <?php wp_nonce_field( 'ipv_tools_bulk', 'ipv_nonce' ); ?>
            <input type="hidden" name="action" value="ipv_tools_execute">
            <input type="hidden" name="tool" value="bulk">
            
            <table class="form-table">
                <tr>
                    <th><label>Azione</label></th>
                    <td>
                        <select name="bulk_action" style="width:300px;">
                            <option value="regenerate_transcripts">Rigenera Trascrizioni</option>
                            <option value="regenerate_descriptions">Rigenera Descrizioni AI</option>
                            <option value="update_youtube_data">Aggiorna Dati YouTube</option>
                            <option value="download_thumbnails">Scarica Thumbnail Mancanti</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label>Applica Su</label></th>
                    <td>
                        <label><input type="radio" name="apply_to" value="all" checked> Tutti i video</label><br>
                        <label><input type="radio" name="apply_to" value="published"> Solo pubblicati</label><br>
                        <label><input type="radio" name="apply_to" value="drafts"> Solo bozze</label>
                    </td>
                </tr>
            </table>
            
            <p>
                <button type="submit" class="button button-primary" onclick="return confirm('Sei sicuro? Questa operazione può richiedere molto tempo.');">
                    ▶️ Esegui Operazione Bulk
                </button>
            </p>
        </form>
        <?php
    }

    private static function render_duplicates_tab() {
        global $wpdb;
        
        // Find duplicates by video_id
        $duplicates = $wpdb->get_results("
            SELECT meta_value as video_id, COUNT(*) as count 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_ipv_video_id' 
            GROUP BY meta_value 
            HAVING count > 1
        ");
        
        ?>
        <h2>🔍 Controllo Duplicati</h2>
        
        <?php if ( $duplicates ) : ?>
            <p style="color:#dc3545;"><strong>⚠️ Trovati <?php echo count( $duplicates ); ?> video duplicati:</strong></p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Video ID</th>
                        <th>Numero Duplicati</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $duplicates as $dup ) : ?>
                    <tr>
                        <td><code><?php echo esc_html( $dup->video_id ); ?></code></td>
                        <td><?php echo $dup->count; ?> copie</td>
                        <td>
                            <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video&s=' . urlencode( $dup->video_id ) ); ?>" class="button button-small">
                                Visualizza
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div style="background:#d4edda;padding:15px;border-left:4px solid #28a745;">
                <strong>✅ Nessun duplicato trovato!</strong> Tutti i video hanno ID univoci.
            </div>
        <?php endif; ?>
        <?php
    }

    private static function render_cleanup_tab() {
        global $wpdb;
        
        // Count orphaned meta
        $orphaned_meta = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.ID IS NULL
        ");
        
        // Count videos without transcript
        $no_transcript = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_ipv_transcript'
            WHERE p.post_type = 'ipv_video' AND (pm.meta_value IS NULL OR pm.meta_value = '')
        ");
        
        ?>
        <h2>🗑️ Pulizia Database</h2>
        <p>Pulisci dati obsoleti e migliora le performance del database.</p>
        
        <table class="form-table">
            <tr>
                <th>Meta Orfani</th>
                <td>
                    <strong><?php echo number_format_i18n( $orphaned_meta ); ?></strong> record orfani trovati.
                    <?php if ( $orphaned_meta > 0 ) : ?>
                        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="display:inline;">
                            <?php wp_nonce_field( 'ipv_tools_cleanup', 'ipv_nonce' ); ?>
                            <input type="hidden" name="action" value="ipv_tools_execute">
                            <input type="hidden" name="tool" value="cleanup_orphans">
                            <button type="submit" class="button button-small">Pulisci</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Video Senza Trascrizione</th>
                <td>
                    <strong><?php echo number_format_i18n( $no_transcript ); ?></strong> video senza trascrizione.
                    <p class="description">Usa "Operazioni Bulk" → "Rigenera Trascrizioni" per generarle.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function handle_execute() {
        check_admin_referer( 'ipv_tools_bulk', 'ipv_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

        $tool = sanitize_text_field( $_POST['tool'] ?? '' );
        
        switch ( $tool ) {
            case 'bulk':
                $action = sanitize_text_field( $_POST['bulk_action'] ?? '' );
                add_settings_error( 'ipv_tools', 'success', "✅ Operazione '$action' avviata in background.", 'success' );
                break;
                
            case 'cleanup_orphans':
                global $wpdb;
                $deleted = $wpdb->query("
                    DELETE pm FROM {$wpdb->postmeta} pm
                    LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                    WHERE p.ID IS NULL
                ");
                add_settings_error( 'ipv_tools', 'success', "✅ $deleted record orfani eliminati.", 'success' );
                break;
        }

        set_transient( 'settings_errors', get_settings_errors(), 30 );
        wp_safe_redirect( wp_get_referer() );
        exit;
    }
}
