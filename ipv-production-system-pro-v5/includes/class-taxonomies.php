<?php
/**
 * Tassonomie migliorate per IPV Production System Pro v5
 *
 * Due macro aree:
 * 1. Relatori (ipv_relatore) - Per identificare i relatori/ospiti
 * 2. Argomenti (ipv_argomento) - Per categorizzare i contenuti
 *
 * @package IPV_Production_System_Pro
 * @version 5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Taxonomies {

    /**
     * Registra tutte le tassonomie
     */
    public static function register() {
        self::register_relatori_taxonomy();
        self::register_argomenti_taxonomy();
        self::register_anno_taxonomy();
    }

    /**
     * Tassonomia: Relatori
     * Per identificare automaticamente i relatori/ospiti nei video
     */
    public static function register_relatori_taxonomy() {
        register_taxonomy( 'ipv_relatore', 'video_ipv', [
            'labels' => [
                'name'                       => 'Relatori',
                'singular_name'              => 'Relatore',
                'search_items'               => 'Cerca Relatori',
                'popular_items'              => 'Relatori Popolari',
                'all_items'                  => 'Tutti i Relatori',
                'edit_item'                  => 'Modifica Relatore',
                'view_item'                  => 'Vedi Relatore',
                'update_item'                => 'Aggiorna Relatore',
                'add_new_item'               => 'Aggiungi Nuovo Relatore',
                'new_item_name'              => 'Nome Nuovo Relatore',
                'separate_items_with_commas' => 'Separa relatori con virgole',
                'add_or_remove_items'        => 'Aggiungi o rimuovi relatori',
                'choose_from_most_used'      => 'Scegli dai relatori più usati',
                'not_found'                  => 'Nessun relatore trovato',
                'no_terms'                   => 'Nessun relatore',
                'items_list_navigation'      => 'Navigazione lista relatori',
                'items_list'                 => 'Lista relatori',
                'back_to_items'              => '← Torna ai relatori',
            ],
            'description'       => 'Relatori, ospiti e intervistati presenti nei video',
            'public'            => true,
            'publicly_queryable' => true,
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'show_in_nav_menus' => true,
            'show_in_rest'      => true,
            'show_tagcloud'     => true,
            'show_in_quick_edit' => true,
            'show_admin_column' => true,
            'rewrite'           => [
                'slug'         => 'relatore',
                'with_front'   => false,
                'hierarchical' => false,
            ],
            'query_var'         => true,
            'meta_box_cb'       => 'post_tags_meta_box', // Stile tag
        ] );
    }

    /**
     * Tassonomia: Argomenti
     * Per categorizzare i contenuti per tema/argomento
     */
    public static function register_argomenti_taxonomy() {
        register_taxonomy( 'ipv_argomento', 'video_ipv', [
            'labels' => [
                'name'                       => 'Argomenti',
                'singular_name'              => 'Argomento',
                'search_items'               => 'Cerca Argomenti',
                'popular_items'              => 'Argomenti Popolari',
                'all_items'                  => 'Tutti gli Argomenti',
                'edit_item'                  => 'Modifica Argomento',
                'view_item'                  => 'Vedi Argomento',
                'update_item'                => 'Aggiorna Argomento',
                'add_new_item'               => 'Aggiungi Nuovo Argomento',
                'new_item_name'              => 'Nome Nuovo Argomento',
                'separate_items_with_commas' => 'Separa argomenti con virgole',
                'add_or_remove_items'        => 'Aggiungi o rimuovi argomenti',
                'choose_from_most_used'      => 'Scegli dagli argomenti più usati',
                'not_found'                  => 'Nessun argomento trovato',
                'no_terms'                   => 'Nessun argomento',
                'items_list_navigation'      => 'Navigazione lista argomenti',
                'items_list'                 => 'Lista argomenti',
                'back_to_items'              => '← Torna agli argomenti',
            ],
            'description'       => 'Argomenti e tematiche trattate nei video',
            'public'            => true,
            'publicly_queryable' => true,
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'show_in_nav_menus' => true,
            'show_in_rest'      => true,
            'show_tagcloud'     => true,
            'show_in_quick_edit' => true,
            'show_admin_column' => true,
            'rewrite'           => [
                'slug'         => 'argomento',
                'with_front'   => false,
                'hierarchical' => false,
            ],
            'query_var'         => true,
            'meta_box_cb'       => 'post_tags_meta_box', // Stile tag
        ] );
    }

    /**
     * Tassonomia: Anno
     * Per filtrare video per anno di pubblicazione
     */
    public static function register_anno_taxonomy() {
        register_taxonomy( 'ipv_anno', 'video_ipv', [
            'labels' => [
                'name'                  => 'Anno',
                'singular_name'         => 'Anno',
                'search_items'          => 'Cerca Anno',
                'all_items'             => 'Tutti gli Anni',
                'edit_item'             => 'Modifica Anno',
                'view_item'             => 'Vedi Anno',
                'update_item'           => 'Aggiorna Anno',
                'add_new_item'          => 'Aggiungi Nuovo Anno',
                'new_item_name'         => 'Nuovo Anno',
                'not_found'             => 'Nessun anno trovato',
                'no_terms'              => 'Nessun anno',
                'items_list_navigation' => 'Navigazione lista anni',
                'items_list'            => 'Lista anni',
                'back_to_items'         => '← Torna agli anni',
            ],
            'description'       => 'Anno di pubblicazione del video',
            'public'            => true,
            'publicly_queryable' => true,
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'show_in_nav_menus' => true,
            'show_in_rest'      => true,
            'show_tagcloud'     => false,
            'show_in_quick_edit' => false,
            'show_admin_column' => true,
            'rewrite'           => [
                'slug'         => 'anno',
                'with_front'   => false,
                'hierarchical' => false,
            ],
            'query_var'         => true,
        ] );
    }

    /**
     * Ottieni tutti i termini per il filtro
     */
    public static function get_all_relatori() {
        return get_terms( [
            'taxonomy'   => 'ipv_relatore',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ] );
    }

    /**
     * Ottieni tutti gli argomenti
     */
    public static function get_all_argomenti() {
        return get_terms( [
            'taxonomy'   => 'ipv_argomento',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ] );
    }

    /**
     * Ottieni tutti gli anni
     */
    public static function get_all_anni() {
        return get_terms( [
            'taxonomy'   => 'ipv_anno',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'DESC',
        ] );
    }
}
