<?php
/**
 * IPV Video Frontend
 * Inserisce embed YouTube nel contenuto del CPT ipv_video
 * Fix margini neri: CSS con specificità massima per sovrascrivere tema
 *
 * @version 7.7.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Video_Frontend {

    public static function init() {
        // Inserisci embed YouTube all'inizio del contenuto
        add_filter( 'the_content', [ __CLASS__, 'prepend_youtube_embed' ], 5 );

        // Rimuovi featured image per ipv_video
        add_filter( 'post_thumbnail_html', [ __CLASS__, 'remove_featured_image' ], 10, 2 );

        // Rimuovi tag e categorie cliccabili
        add_action( 'wp_head', [ __CLASS__, 'hide_tags_and_meta' ] );

        // Sostituisci views WordPress con views YouTube
        add_filter( 'get_post_metadata', [ __CLASS__, 'replace_views_with_youtube' ], 10, 4 );

        // Hook aggiuntivi per temi che usano filtri custom
        add_filter( 'post_views', [ __CLASS__, 'filter_post_views' ], 999, 2 );
        add_filter( 'the_views', [ __CLASS__, 'filter_post_views' ], 999, 2 );

        // Forza update views su load del post
        add_action( 'wp', [ __CLASS__, 'force_youtube_views_on_post' ] );
    }

    /**
     * Inserisce l'embed YouTube all'inizio del contenuto per ipv_video
     */
    public static function prepend_youtube_embed( $content ) {
        // Solo per single ipv_video nel loop principale
        if ( ! is_singular( 'ipv_video' ) || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        $post_id = get_the_ID();
        $yt_id   = get_post_meta( $post_id, '_ipv_video_id', true );

        if ( empty( $yt_id ) ) {
            return $content;
        }

        // CSS inline con altissima specificità per evitare conflitti con il tema
        $embed_html = '
        <style>
        /* Container principale - width 100% dell\'area contenuto (non viewport) */
        body.single-ipv_video .ipv-video-embed-container,
        body .ipv-video-embed-container {
            width: 100% !important;
            max-width: 100% !important;
            position: relative !important;
            margin: 0 auto 40px auto !important;
            padding: 0 !important;
            display: block !important;
            clear: both !important;
            overflow: visible !important;
            min-height: 0 !important;
        }

        /* Fix per contenitori del tema che potrebbero tagliare */
        body.single-ipv_video .entry-content,
        body.single-ipv_video .post-content,
        body.single-ipv_video article,
        body.single-ipv_video .hentry {
            overflow: visible !important;
        }

        /* Wrapper responsive 16:9 - aspect ratio perfetto */
        body.single-ipv_video .ipv-video-embed-container .ipv-embed-wrapper,
        body .ipv-video-embed-container .ipv-embed-wrapper {
            position: relative !important;
            width: 100% !important;
            max-width: 100% !important;
            padding-bottom: 56.25% !important; /* 16:9 aspect ratio */
            padding-top: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            height: 0 !important;
            overflow: hidden !important;
            border-radius: 0 !important;
            background: #000 !important;
            box-shadow: none !important;
            margin: 0 !important;
        }

        /* iframe - riempie completamente il wrapper senza margini */
        body.single-ipv_video .ipv-video-embed-container .ipv-embed-wrapper iframe,
        body .ipv-video-embed-container .ipv-embed-wrapper iframe,
        body.single-ipv_video iframe[src*="youtube"],
        body.single-ipv_video iframe[src*="vimeo"] {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            max-width: 100% !important;
            max-height: 100% !important;
            border: 0 !important;
            border-radius: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            display: block !important;
        }

        /* Mobile responsive - mantieni full width anche su mobile */
        @media (max-width: 768px) {
            body.single-ipv_video .ipv-video-embed-container,
            body .ipv-video-embed-container {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 auto 30px auto !important;
                padding: 0 !important;
                position: relative !important;
                overflow: visible !important;
                min-height: 0 !important;
                height: auto !important;
                max-height: none !important;
            }

            body.single-ipv_video .ipv-video-embed-container .ipv-embed-wrapper,
            body .ipv-video-embed-container .ipv-embed-wrapper {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: relative !important;
                width: 100% !important;
                padding-bottom: 56.25% !important;
                height: 0 !important;
                overflow: hidden !important;
            }

            body.single-ipv_video .ipv-video-embed-container iframe,
            body .ipv-video-embed-container iframe {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
            }

            /* Forza visibilità anche sui contenitori padre */
            body.single-ipv_video .entry-content,
            body.single-ipv_video .post-content,
            body.single-ipv_video article {
                overflow: visible !important;
                max-height: none !important;
            }
        }
        </style>
        <div class="ipv-video-embed-container">
            <div class="ipv-embed-wrapper">
                <iframe
                    src="https://www.youtube.com/embed/' . esc_attr( $yt_id ) . '?rel=0&modestbranding=1&showinfo=0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                    loading="lazy">
                </iframe>
            </div>
        </div>
        ';

        return $embed_html . $content;
    }

    /**
     * Rimuove featured image per ipv_video SOLO nel post principale
     */
    public static function remove_featured_image( $html, $post_id ) {
        // Rimuovi solo se siamo nel main query del post singolo
        if ( get_post_type( $post_id ) === 'ipv_video' && is_singular( 'ipv_video' ) && is_main_query() ) {
            return '';
        }
        return $html;
    }

    /**
     * Sostituisce views WordPress con views YouTube per ipv_video
     */
    public static function replace_views_with_youtube( $value, $object_id, $meta_key, $single ) {
        // Solo per ipv_video
        if ( get_post_type( $object_id ) !== 'ipv_video' ) {
            return $value;
        }

        // Chiavi meta usate dai temi per contare le views
        $view_keys = [
            'post_views_count',
            'views',
            '_post_views_count',
            'wpb_post_views_count',
            'post_view_count',
            'wpb_views',
        ];

        if ( in_array( $meta_key, $view_keys, true ) ) {
            // Recupera views YouTube
            $youtube_views = get_post_meta( $object_id, '_ipv_yt_views', true );

            if ( ! empty( $youtube_views ) ) {
                return $single ? $youtube_views : [ $youtube_views ];
            }
        }

        return $value;
    }

    /**
     * Filtro per views custom dei temi
     */
    public static function filter_post_views( $views, $post_id = null ) {
        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }

        if ( get_post_type( $post_id ) === 'ipv_video' ) {
            $youtube_views = get_post_meta( $post_id, '_ipv_yt_views', true );
            if ( ! empty( $youtube_views ) ) {
                return $youtube_views;
            }
        }

        return $views;
    }

    /**
     * Forza sovrascrittura views WordPress con YouTube all'apertura del post
     */
    public static function force_youtube_views_on_post() {
        if ( ! is_singular( 'ipv_video' ) ) {
            return;
        }

        $post_id = get_the_ID();
        $youtube_views = get_post_meta( $post_id, '_ipv_yt_views', true );

        if ( ! empty( $youtube_views ) ) {
            // Sovrascrive tutte le chiavi views possibili con il valore YouTube
            $view_keys = [
                'post_views_count',
                'views',
                '_post_views_count',
                'wpb_post_views_count',
                'post_view_count',
                'wpb_views',
            ];

            foreach ( $view_keys as $key ) {
                update_post_meta( $post_id, $key, $youtube_views );
            }
        }
    }

    /**
     * Nasconde tag, categorie e metadati per ipv_video
     */
    public static function hide_tags_and_meta() {
        if ( ! is_singular( 'ipv_video' ) ) {
            return;
        }
        ?>
        <style>
        /* Nasconde featured image e immagini in evidenza DEL POST PRINCIPALE */
        body.single-ipv_video article.ipv_video .post-thumbnail,
        body.single-ipv_video article.ipv_video .entry-thumbnail,
        body.single-ipv_video article.ipv_video .featured-image,
        body.single-ipv_video article.ipv_video .wp-post-image,
        body.single-ipv_video article.ipv_video .attachment-post-thumbnail,
        body.single-ipv_video .hentry .post-thumbnail,
        body.single-ipv_video .hentry .entry-thumbnail,
        body.single-ipv_video .entry-header .post-thumbnail,
        body.single-ipv_video .entry-header .featured-image {
            display: none !important;
        }

        /* Nasconde tag e categorie cliccabili (tema Influencer + standard) */
        body.single-ipv_video .entry-meta,
        body.single-ipv_video .post-meta,
        body.single-ipv_video .entry-footer,
        body.single-ipv_video .post-categories,
        body.single-ipv_video .post-tags,
        body.single-ipv_video .cat-links,
        body.single-ipv_video .tags-links,
        body.single-ipv_video .tag-links,
        body.single-ipv_video .entry-categories,
        body.single-ipv_video .entry-tags,
        body.single-ipv_video .taxonomy-links,
        body.single-ipv_video .bt-post-tags,
        body.single-ipv_video .post-views,
        body.single-ipv_video .reading-time,
        body.single-ipv_video .comment-count,
        body.single-ipv_video .comments-link {
            display: none !important;
        }

        /* Nasconde anche i link alle tassonomie custom */
        body.single-ipv_video .ipv_categoria-links,
        body.single-ipv_video .ipv_relatore-links,
        body.single-ipv_video .term-links {
            display: none !important;
        }

        /* Mobile: stesse regole + sidebar nascosta */
        @media (max-width: 768px) {
            body.single-ipv_video article.ipv_video .post-thumbnail,
            body.single-ipv_video article.ipv_video .entry-thumbnail,
            body.single-ipv_video article.ipv_video .featured-image,
            body.single-ipv_video .entry-meta,
            body.single-ipv_video .post-meta,
            body.single-ipv_video .entry-footer,
            body.single-ipv_video .post-categories,
            body.single-ipv_video .post-tags,
            body.single-ipv_video .bt-post-tags {
                display: none !important;
            }

            /* Nascondi sidebar su mobile */
            body.single-ipv_video .sidebar,
            body.single-ipv_video #sidebar,
            body.single-ipv_video .widget-area,
            body.single-ipv_video aside,
            body.single-ipv_video .secondary,
            body.single-ipv_video #secondary,
            body.single-ipv_video .sidebar-primary,
            body.single-ipv_video .site-sidebar {
                display: none !important;
            }

            /* Contenuto a full width quando sidebar è nascosta */
            body.single-ipv_video .content,
            body.single-ipv_video .site-content,
            body.single-ipv_video .main-content,
            body.single-ipv_video #primary,
            body.single-ipv_video article {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 auto !important;
            }
        }
        </style>
        <?php
    }
}

IPV_Prod_Video_Frontend::init();
