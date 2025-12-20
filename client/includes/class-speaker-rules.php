<?php
/**
 * IPV Speaker Rules - Gestione regole relatori per format
 * 
 * @version 7.5.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Speaker_Rules {

    const OPTION_KEY = 'ipv_speaker_rules';

    /**
     * Init
     */
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_submenu' ], 99 );
        add_action( 'wp_ajax_ipv_save_speaker_rules', [ __CLASS__, 'ajax_save_rules' ] );
        add_action( 'wp_ajax_ipv_delete_speaker_rule', [ __CLASS__, 'ajax_delete_rule' ] );
    }

    /**
     * Aggiungi submenu
     */
    public static function add_submenu() {
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            'Regole Relatori',
            'Regole Relatori',
            'manage_options',
            'ipv-speaker-rules',
            [ __CLASS__, 'render_page' ]
        );
    }

    /**
     * Ottieni regole salvate
     */
    public static function get_rules() {
        $rules = get_option( self::OPTION_KEY, [] );
        if ( ! is_array( $rules ) ) {
            $rules = [];
        }
        return $rules;
    }

    /**
     * Salva regole
     */
    public static function save_rules( $rules ) {
        return update_option( self::OPTION_KEY, $rules );
    }

    /**
     * Trova relatore per titolo usando le regole
     * 
     * @param string $title Titolo del video
     * @return string|null Nome relatore o null se non trovato
     */
    public static function find_speaker_by_title( $title ) {
        $rules = self::get_rules();
        $title_upper = mb_strtoupper( (string) $title );

        foreach ( $rules as $rule ) {
            if ( empty( $rule['pattern'] ) || empty( $rule['speaker'] ) ) {
                continue;
            }

            $pattern_upper = mb_strtoupper( $rule['pattern'] );
            
            if ( mb_strpos( $title_upper, $pattern_upper ) !== false ) {
                return $rule['speaker'];
            }
        }

        return null;
    }

    /**
     * Render pagina admin
     */
    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $rules = self::get_rules();
        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-groups" style="font-size:30px;width:30px;height:30px;margin-right:10px;"></span>
                Regole Relatori per Format
            </h1>

            <div style="max-width:900px;margin-top:20px;">
                
                <!-- Spiegazione -->
                <div class="card" style="margin-bottom:20px;padding:15px 20px;background:#f0f6fc;border-left:4px solid #2271b1;">
                    <p style="margin:0;"><strong>Come funziona:</strong></p>
                    <p style="margin:10px 0 0 0;">
                        Se il <strong>titolo del video</strong> contiene il testo specificato nel "Pattern", 
                        il relatore verrà automaticamente impostato con il nome indicato.<br>
                        Le regole hanno la priorità sulla ricerca automatica "con Nome Cognome".
                    </p>
                </div>

                <!-- Tabella regole -->
                <div class="card" style="padding:20px;">
                    <h2 style="margin-top:0;">📋 Regole Attive</h2>
                    
                    <table class="widefat striped" id="ipv-rules-table">
                        <thead>
                            <tr>
                                <th style="width:40%;">Pattern nel Titolo</th>
                                <th style="width:40%;">Relatore Assegnato</th>
                                <th style="width:20%;">Azioni</th>
                            </tr>
                        </thead>
                        <tbody id="ipv-rules-body">
                            <?php if ( empty( $rules ) ) : ?>
                                <tr class="ipv-no-rules">
                                    <td colspan="3" style="text-align:center;color:#666;padding:20px;">
                                        Nessuna regola configurata. Aggiungi la prima regola qui sotto.
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ( $rules as $index => $rule ) : ?>
                                    <tr data-index="<?php echo esc_attr( $index ); ?>">
                                        <td>
                                            <code style="background:#f0f0f1;padding:3px 8px;border-radius:3px;">
                                                <?php echo esc_html( $rule['pattern'] ); ?>
                                            </code>
                                        </td>
                                        <td>
                                            <strong><?php echo esc_html( $rule['speaker'] ); ?></strong>
                                        </td>
                                        <td>
                                            <button type="button" class="button button-small ipv-delete-rule" data-index="<?php echo esc_attr( $index ); ?>">
                                                <span class="dashicons dashicons-trash" style="margin-top:3px;"></span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Form nuova regola -->
                    <div style="margin-top:20px;padding-top:20px;border-top:1px solid #ddd;">
                        <h3 style="margin-top:0;">➕ Aggiungi Nuova Regola</h3>
                        
                        <div style="display:flex;gap:15px;align-items:flex-end;flex-wrap:wrap;">
                            <div style="flex:1;min-width:200px;">
                                <label for="ipv-new-pattern" style="display:block;margin-bottom:5px;font-weight:600;">
                                    Pattern nel Titolo
                                </label>
                                <input type="text" 
                                       id="ipv-new-pattern" 
                                       class="regular-text" 
                                       style="width:100%;"
                                       placeholder="es: RE-ACTION">
                                <p class="description">Il testo da cercare nel titolo (case-insensitive)</p>
                            </div>
                            
                            <div style="flex:1;min-width:200px;">
                                <label for="ipv-new-speaker" style="display:block;margin-bottom:5px;font-weight:600;">
                                    Relatore Assegnato
                                </label>
                                <input type="text" 
                                       id="ipv-new-speaker" 
                                       class="regular-text" 
                                       style="width:100%;"
                                       placeholder="es: Adrian Fiorelli">
                                <p class="description">Nome e cognome del relatore</p>
                            </div>
                            
                            <div>
                                <button type="button" id="ipv-add-rule" class="button button-primary">
                                    <span class="dashicons dashicons-plus-alt2" style="margin-top:3px;"></span>
                                    Aggiungi Regola
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Esempi -->
                <div class="card" style="margin-top:20px;padding:20px;">
                    <h2 style="margin-top:0;">💡 Esempi</h2>
                    <table class="widefat" style="max-width:700px;">
                        <thead>
                            <tr>
                                <th>Titolo Video</th>
                                <th>Pattern</th>
                                <th>Relatore</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>RE-ACTION – PIERGIORGIO CARIA...</td>
                                <td><code>RE-ACTION</code></td>
                                <td>Adrian Fiorelli</td>
                            </tr>
                            <tr>
                                <td>ANALISI DELLA SETTIMANA con ENRICO MAGNANI #14</td>
                                <td><code>ANALISI DELLA SETTIMANA</code></td>
                                <td>Enrico Magnani</td>
                            </tr>
                            <tr>
                                <td>IL NOTIZIARIO SERALE – Alessandro Sieni</td>
                                <td><code>NOTIZIARIO SERALE</code></td>
                                <td>Alessandro Sieni</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            
            // Aggiungi regola
            $('#ipv-add-rule').on('click', function() {
                var pattern = $('#ipv-new-pattern').val().trim();
                var speaker = $('#ipv-new-speaker').val().trim();
                
                if (!pattern || !speaker) {
                    alert('Inserisci sia il pattern che il relatore');
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true).text('Salvataggio...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_save_speaker_rules',
                        nonce: '<?php echo wp_create_nonce( 'ipv_speaker_rules_nonce' ); ?>',
                        pattern: pattern,
                        speaker: speaker
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Errore: ' + (response.data || 'Errore sconosciuto'));
                        }
                    },
                    error: function() {
                        alert('Errore di connessione');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-plus-alt2" style="margin-top:3px;"></span> Aggiungi Regola');
                    }
                });
            });
            
            // Elimina regola
            $(document).on('click', '.ipv-delete-rule', function() {
                if (!confirm('Eliminare questa regola?')) {
                    return;
                }
                
                var $btn = $(this);
                var index = $btn.data('index');
                
                $btn.prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_delete_speaker_rule',
                        nonce: '<?php echo wp_create_nonce( 'ipv_speaker_rules_nonce' ); ?>',
                        index: index
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Errore: ' + (response.data || 'Errore sconosciuto'));
                        }
                    },
                    error: function() {
                        alert('Errore di connessione');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
            
            // Enter per aggiungere
            $('#ipv-new-pattern, #ipv-new-speaker').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#ipv-add-rule').click();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Salva nuova regola
     */
    public static function ajax_save_rules() {
        check_ajax_referer( 'ipv_speaker_rules_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permessi insufficienti' );
        }

        $pattern = isset( $_POST['pattern'] ) ? sanitize_text_field( $_POST['pattern'] ) : '';
        $speaker = isset( $_POST['speaker'] ) ? sanitize_text_field( $_POST['speaker'] ) : '';

        if ( empty( $pattern ) || empty( $speaker ) ) {
            wp_send_json_error( 'Pattern e relatore sono obbligatori' );
        }

        $rules = self::get_rules();
        
        // Verifica duplicati
        foreach ( $rules as $rule ) {
            if ( mb_strtoupper( $rule['pattern'] ) === mb_strtoupper( $pattern ) ) {
                wp_send_json_error( 'Questo pattern esiste già' );
            }
        }

        $rules[] = [
            'pattern' => $pattern,
            'speaker' => $speaker,
        ];

        self::save_rules( $rules );

        wp_send_json_success( [ 'message' => 'Regola aggiunta' ] );
    }

    /**
     * AJAX: Elimina regola
     */
    public static function ajax_delete_rule() {
        check_ajax_referer( 'ipv_speaker_rules_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permessi insufficienti' );
        }

        $index = isset( $_POST['index'] ) ? absint( $_POST['index'] ) : -1;

        $rules = self::get_rules();

        if ( ! isset( $rules[ $index ] ) ) {
            wp_send_json_error( 'Regola non trovata' );
        }

        array_splice( $rules, $index, 1 );
        self::save_rules( $rules );

        wp_send_json_success( [ 'message' => 'Regola eliminata' ] );
    }
}

IPV_Prod_Speaker_Rules::init();
