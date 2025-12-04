<?php
/**
 * Video Wall Settings - Admin configuration page
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Video_Wall_Settings {

    public static function init() {
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function register_settings() {
        register_setting( 'ipv_video_wall_settings', 'ipv_wall_per_page', [
            'type'              => 'integer',
            'default'           => 5,
            'sanitize_callback' => 'absint',
        ]);

        register_setting( 'ipv_video_wall_settings', 'ipv_wall_layout', [
            'type'              => 'string',
            'default'           => '2-3',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        register_setting( 'ipv_video_wall_settings', 'ipv_wall_pagination_type', [
            'type'              => 'string',
            'default'           => 'load_more',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        register_setting( 'ipv_video_wall_settings', 'ipv_wall_columns', [
            'type'              => 'integer',
            'default'           => 3,
            'sanitize_callback' => 'absint',
        ]);

        register_setting( 'ipv_video_wall_settings', 'ipv_wall_show_filters', [
            'type'              => 'string',
            'default'           => 'yes',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        register_setting( 'ipv_video_wall_settings', 'ipv_wall_enabled_filters', [
            'type'              => 'array',
            'default'           => [ 'categories', 'speakers', 'tags', 'search', 'sort' ],
            'sanitize_callback' => [ __CLASS__, 'sanitize_enabled_filters' ],
        ]);

        add_settings_section(
            'ipv_video_wall_section',
            'Impostazioni Video Wall',
            [ __CLASS__, 'render_section_description' ],
            'ipv_video_wall_settings'
        );

        add_settings_field(
            'ipv_wall_layout',
            'Layout Video',
            [ __CLASS__, 'render_layout_field' ],
            'ipv_video_wall_settings',
            'ipv_video_wall_section'
        );

        add_settings_field(
            'ipv_wall_per_page',
            'Video per pagina',
            [ __CLASS__, 'render_per_page_field' ],
            'ipv_video_wall_settings',
            'ipv_video_wall_section'
        );

        add_settings_field(
            'ipv_wall_pagination_type',
            'Tipo paginazione',
            [ __CLASS__, 'render_pagination_type_field' ],
            'ipv_video_wall_settings',
            'ipv_video_wall_section'
        );

        add_settings_field(
            'ipv_wall_columns',
            'Numero colonne (se layout griglia)',
            [ __CLASS__, 'render_columns_field' ],
            'ipv_video_wall_settings',
            'ipv_video_wall_section'
        );

        add_settings_field(
            'ipv_wall_show_filters',
            'Mostra filtri',
            [ __CLASS__, 'render_show_filters_field' ],
            'ipv_video_wall_settings',
            'ipv_video_wall_section'
        );

        add_settings_field(
            'ipv_wall_enabled_filters',
            'Filtri da visualizzare',
            [ __CLASS__, 'render_enabled_filters_field' ],
            'ipv_video_wall_settings',
            'ipv_video_wall_section'
        );
    }

    public static function render_section_description() {
        echo '<p>Configura le impostazioni predefinite per il Video Wall. Usa lo shortcode <code>[ipv_video_wall]</code> per visualizzare la griglia di video.</p>';
        echo '<p><strong>Nota:</strong> I video YouTube Shorts (durata inferiore a 60 secondi) sono automaticamente esclusi dalla visualizzazione.</p>';
    }

    public static function render_layout_field() {
        $value = get_option( 'ipv_wall_layout', '2-3' );
        ?>
        <select name="ipv_wall_layout">
            <option value="2-3" <?php selected( $value, '2-3' ); ?>>2+3 (2 video sopra, 3 sotto) - Default</option>
            <option value="grid" <?php selected( $value, 'grid' ); ?>>Griglia uniforme (usa numero colonne)</option>
        </select>
        <p class="description">Scegli il layout di visualizzazione dei video</p>
        <?php
    }

    public static function render_per_page_field() {
        $value = get_option( 'ipv_wall_per_page', 5 );
        ?>
        <input type="number" name="ipv_wall_per_page" value="<?php echo esc_attr( $value ); ?>" min="1" max="50" class="small-text">
        <p class="description">Numero di video da visualizzare per pagina/caricamento (default: 5 per layout 2+3)</p>
        <?php
    }

    public static function render_pagination_type_field() {
        $value = get_option( 'ipv_wall_pagination_type', 'load_more' );
        ?>
        <label>
            <input type="radio" name="ipv_wall_pagination_type" value="load_more" <?php checked( $value, 'load_more' ); ?>>
            Bottone "Carica altri" (default)
        </label>
        <br>
        <label style="margin-top: 10px; display: inline-block;">
            <input type="radio" name="ipv_wall_pagination_type" value="pagination" <?php checked( $value, 'pagination' ); ?>>
            Paginazione tradizionale (Precedente/Successivo)
        </label>
        <p class="description">Tipo di navigazione tra i video</p>
        <?php
    }

    public static function render_columns_field() {
        $value = get_option( 'ipv_wall_columns', 3 );
        ?>
        <select name="ipv_wall_columns">
            <option value="2" <?php selected( $value, 2 ); ?>>2 colonne</option>
            <option value="3" <?php selected( $value, 3 ); ?>>3 colonne</option>
            <option value="4" <?php selected( $value, 4 ); ?>>4 colonne</option>
        </select>
        <p class="description">Numero di colonne quando il layout è impostato su "Griglia uniforme"</p>
        <?php
    }

    public static function render_show_filters_field() {
        $value = get_option( 'ipv_wall_show_filters', 'yes' );
        ?>
        <label>
            <input type="radio" name="ipv_wall_show_filters" value="yes" <?php checked( $value, 'yes' ); ?>>
            Sì
        </label>
        <label style="margin-left: 20px;">
            <input type="radio" name="ipv_wall_show_filters" value="no" <?php checked( $value, 'no' ); ?>>
            No
        </label>
        <p class="description">Mostra i filtri per categoria e relatore sopra la griglia video</p>
        <?php
    }

    public static function render_enabled_filters_field() {
        $enabled = get_option( 'ipv_wall_enabled_filters', [ 'categories', 'speakers', 'tags', 'search', 'sort' ] );
        $available_filters = [
            'categories' => 'Categorie (ipv_categoria)',
            'speakers'   => 'Relatori (ipv_relatore)',
            'tags'       => 'Tag (post_tag)',
            'search'     => 'Cerca nei video',
            'sort'       => 'Ordina per',
        ];
        ?>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; max-width: 500px;">
            <?php foreach ( $available_filters as $key => $label ) : ?>
                <label style="display: flex; align-items: center;">
                    <input type="checkbox"
                           name="ipv_wall_enabled_filters[]"
                           value="<?php echo esc_attr( $key ); ?>"
                           <?php checked( in_array( $key, $enabled, true ) ); ?>>
                    <span style="margin-left: 8px;"><?php echo esc_html( $label ); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <p class="description">Seleziona quali filtri mostrare nella Video Wall. I filtri appaiono nell'ordine: Categories, Speakers, Tags, Sort, Search.</p>
        <?php
    }

    public static function sanitize_enabled_filters( $value ) {
        if ( ! is_array( $value ) ) {
            return [ 'categories', 'speakers', 'tags', 'search', 'sort' ];
        }
        $valid_filters = [ 'categories', 'speakers', 'tags', 'search', 'sort' ];
        return array_values( array_intersect( $value, $valid_filters ) );
    }

    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( isset( $_GET['settings-updated'] ) ) {
            add_settings_error( 'ipv_video_wall_messages', 'ipv_video_wall_message', 'Impostazioni salvate.', 'updated' );
        }

        settings_errors( 'ipv_video_wall_messages' );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <div class="ipv-settings-header">
                <h2>Video Wall</h2>
                <p>Gestisci la visualizzazione dei video con layout 2+3 (2 video sopra, 3 video sotto)</p>
            </div>

            <form action="options.php" method="post">
                <?php
                settings_fields( 'ipv_video_wall_settings' );
                do_settings_sections( 'ipv_video_wall_settings' );
                submit_button( 'Salva impostazioni' );
                ?>
            </form>

            <div class="ipv-settings-info" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-left: 4px solid #2271b1;">
                <h3>Come usare il Video Wall</h3>
                <p>Inserisci lo shortcode in qualsiasi pagina o post:</p>
                <pre style="background: #fff; padding: 10px; border: 1px solid #ddd;">[ipv_video_wall]</pre>

                <h4 style="margin-top: 20px;">Parametri opzionali:</h4>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><code>per_page="5"</code> - Numero di video per pagina</li>
                    <li><code>show_filters="yes"</code> - Mostra/nascondi filtri (yes/no)</li>
                    <li><code>categoria="123"</code> - Filtra per ID categoria</li>
                    <li><code>relatore="456"</code> - Filtra per ID relatore</li>
                </ul>

                <h4 style="margin-top: 20px;">Esempio con parametri:</h4>
                <pre style="background: #fff; padding: 10px; border: 1px solid #ddd;">[ipv_video_wall per_page="10" show_filters="no"]</pre>

                <h4 style="margin-top: 20px;">Layout 2+3:</h4>
                <p>Il layout predefinito mostra 5 video per pagina con pattern 2+3:</p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li>Prima riga: 2 video affiancati (50% ciascuno)</li>
                    <li>Seconda riga: 3 video affiancati (33% ciascuno)</li>
                </ul>

                <h4 style="margin-top: 20px;">Filtro YouTube Shorts:</h4>
                <p>I video con durata inferiore a 60 secondi sono automaticamente esclusi dalla visualizzazione per evitare YouTube Shorts.</p>
            </div>
        </div>
        <?php
    }
}

IPV_Prod_Video_Wall_Settings::init();
