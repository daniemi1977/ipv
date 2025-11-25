<?php
/**
 * Controllo Accessi per Relatori
 *
 * Gestisce i permessi per gli utenti relatori:
 * - Possono vedere e modificare solo i loro video
 * - Basato su corrispondenza tra user e tassonomia ipv_relatore
 *
 * @package IPV_Production_System_Pro
 * @version 6.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Relatore_Access {

    /**
     * Inizializza
     */
    public static function init() {
        // Filtra lista video nell'admin per relatori
        add_filter( 'pre_get_posts', [ __CLASS__, 'filter_video_list_for_relatori' ] );

        // Controlla permessi di editing
        add_filter( 'user_has_cap', [ __CLASS__, 'check_edit_permissions' ], 10, 4 );

        // Rimuovi metabox non necessari per relatori
        add_action( 'add_meta_boxes', [ __CLASS__, 'remove_metaboxes_for_relatori' ], 999 );

        // Aggiungi info relatore nel profilo utente
        add_action( 'show_user_profile', [ __CLASS__, 'add_relatore_profile_fields' ] );
        add_action( 'edit_user_profile', [ __CLASS__, 'add_relatore_profile_fields' ] );
        add_action( 'personal_options_update', [ __CLASS__, 'save_relatore_profile_fields' ] );
        add_action( 'edit_user_profile_update', [ __CLASS__, 'save_relatore_profile_fields' ] );
    }

    /**
     * Controlla se un utente è un relatore
     */
    public static function is_relatore( $user_id = null ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return false;
        }

        // Controlla se ha il ruolo di editor o contributor
        // (puoi creare un ruolo specifico "relatore" se preferisci)
        return in_array( 'editor', $user->roles ) || in_array( 'contributor', $user->roles );
    }

    /**
     * Ottieni il termine relatore associato all'utente
     */
    public static function get_user_relatore_term( $user_id = null ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        // Prima controlla se c'è un'associazione diretta salvata
        $term_id = get_user_meta( $user_id, '_ipv_relatore_term_id', true );
        if ( $term_id ) {
            $term = get_term( $term_id, 'ipv_relatore' );
            if ( $term && ! is_wp_error( $term ) ) {
                return $term;
            }
        }

        // Altrimenti cerca per nome utente o display name
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return null;
        }

        // Prova con display name
        $term = get_term_by( 'name', $user->display_name, 'ipv_relatore' );
        if ( $term && ! is_wp_error( $term ) ) {
            // Salva l'associazione per la prossima volta
            update_user_meta( $user_id, '_ipv_relatore_term_id', $term->term_id );
            return $term;
        }

        // Prova con username (normalizzato)
        $username_normalized = ucwords( str_replace( [ '_', '-', '.' ], ' ', $user->user_login ) );
        $term = get_term_by( 'name', $username_normalized, 'ipv_relatore' );
        if ( $term && ! is_wp_error( $term ) ) {
            update_user_meta( $user_id, '_ipv_relatore_term_id', $term->term_id );
            return $term;
        }

        return null;
    }

    /**
     * Filtra i video visibili per i relatori
     */
    public static function filter_video_list_for_relatori( $query ) {
        // Solo nell'admin e per la query principale
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }

        // Solo per post type video_ipv
        if ( $query->get( 'post_type' ) !== 'video_ipv' ) {
            return;
        }

        // Solo per utenti relatori (non admin)
        if ( ! self::is_relatore() || current_user_can( 'manage_options' ) ) {
            return;
        }

        // Ottieni termine relatore
        $relatore_term = self::get_user_relatore_term();
        if ( ! $relatore_term ) {
            // Se non c'è associazione, non mostrare nessun video
            $query->set( 'post__in', [ 0 ] );
            return;
        }

        // Filtra per tassonomia relatore
        $tax_query = $query->get( 'tax_query' ) ?: [];
        $tax_query[] = [
            'taxonomy' => 'ipv_relatore',
            'field'    => 'term_id',
            'terms'    => $relatore_term->term_id,
        ];
        $query->set( 'tax_query', $tax_query );
    }

    /**
     * Controlla permessi di editing
     */
    public static function check_edit_permissions( $allcaps, $caps, $args, $user ) {
        // Solo per edit_post/delete_post
        if ( empty( $args[0] ) || ! in_array( $args[0], [ 'edit_post', 'delete_post' ] ) ) {
            return $allcaps;
        }

        // Solo per video_ipv
        $post_id = isset( $args[2] ) ? $args[2] : 0;
        if ( ! $post_id || get_post_type( $post_id ) !== 'video_ipv' ) {
            return $allcaps;
        }

        // Admin può tutto
        if ( ! empty( $allcaps['manage_options'] ) ) {
            return $allcaps;
        }

        // Solo per relatori
        if ( ! self::is_relatore( $user->ID ) ) {
            return $allcaps;
        }

        // Ottieni termine relatore
        $relatore_term = self::get_user_relatore_term( $user->ID );
        if ( ! $relatore_term ) {
            // Nessuna associazione = nessun permesso
            $allcaps['edit_post'] = false;
            $allcaps['delete_post'] = false;
            return $allcaps;
        }

        // Verifica se il video ha questo relatore
        $video_relatori = wp_get_post_terms( $post_id, 'ipv_relatore', [ 'fields' => 'ids' ] );
        if ( is_wp_error( $video_relatori ) || ! in_array( $relatore_term->term_id, $video_relatori ) ) {
            // Non è il suo video
            $allcaps['edit_post'] = false;
            $allcaps['delete_post'] = false;
        }

        return $allcaps;
    }

    /**
     * Rimuovi metabox non necessari per i relatori
     */
    public static function remove_metaboxes_for_relatori() {
        if ( ! self::is_relatore() || current_user_can( 'manage_options' ) ) {
            return;
        }

        // Rimuovi metabox che i relatori non devono toccare
        remove_meta_box( 'ipv_actions', 'video_ipv', 'side' ); // Azioni avanzate
        remove_meta_box( 'ipv_stats', 'video_ipv', 'side' ); // Statistiche (read-only)
    }

    /**
     * Aggiungi campi profilo per associazione relatore
     */
    public static function add_relatore_profile_fields( $user ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return; // Solo admin può vedere/modificare
        }

        $term_id = get_user_meta( $user->ID, '_ipv_relatore_term_id', true );
        $relatori = get_terms( [
            'taxonomy'   => 'ipv_relatore',
            'hide_empty' => false,
        ] );
        ?>
        <h2>Associazione Relatore IPV</h2>
        <table class="form-table">
            <tr>
                <th><label for="ipv_relatore_term_id">Relatore Associato</label></th>
                <td>
                    <select name="ipv_relatore_term_id" id="ipv_relatore_term_id" class="regular-text">
                        <option value="">-- Nessuna associazione --</option>
                        <?php foreach ( $relatori as $relatore ) : ?>
                            <option value="<?php echo esc_attr( $relatore->term_id ); ?>" <?php selected( $term_id, $relatore->term_id ); ?>>
                                <?php echo esc_html( $relatore->name ); ?> (<?php echo intval( $relatore->count ); ?> video)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        Associa questo utente a un relatore. L'utente potrà modificare solo i video associati a questo relatore.
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Salva campi profilo relatore
     */
    public static function save_relatore_profile_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return;
        }

        if ( isset( $_POST['ipv_relatore_term_id'] ) ) {
            $term_id = absint( $_POST['ipv_relatore_term_id'] );
            if ( $term_id > 0 ) {
                update_user_meta( $user_id, '_ipv_relatore_term_id', $term_id );
            } else {
                delete_user_meta( $user_id, '_ipv_relatore_term_id' );
            }
        }
    }
}

IPV_Prod_Relatore_Access::init();
