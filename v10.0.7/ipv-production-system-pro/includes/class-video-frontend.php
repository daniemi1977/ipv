<?php
/**
 * IPV Video Frontend
 * Inserisce embed YouTube nel contenuto del CPT ipv_video
 * Fix margini neri: CSS con specificità massima per sovrascrivere tema
 * v7.9.17: Fix duplicazione embed con flag anti-duplicazione condiviso
 * v7.9.20: Fix barra grigia Related Posts
 *
 * @version 7.9.20
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Video_Frontend {

    /**
     * Flag per evitare duplicazione embed tra diversi hook
     */
    private static $embed_rendered = false;

    public static function init() {
        // Inserisci embed YouTube all'inizio del contenuto
        add_filter( 'the_content', [ __CLASS__, 'prepend_youtube_embed' ], 5 );

        // Hook alternativo per temi che non usano the_content standard
        // NOTA: Disabilitato per evitare duplicazione con temi come Influencers
        // add_action( 'loop_start', [ __CLASS__, 'maybe_output_embed' ] );
        
        // Inietta stili CSS
        add_action( 'wp_head', [ __CLASS__, 'inject_embed_styles' ] );

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

        // Evita duplicazione se già renderizzato
        if ( self::$embed_rendered ) {
            return $content;
        }

        $post_id = get_the_ID();
        $yt_id   = get_post_meta( $post_id, '_ipv_video_id', true );

        if ( empty( $yt_id ) ) {
            return $content;
        }

        // Segna come già renderizzato
        self::$embed_rendered = true;

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
        /* Lazy loading styles */
        .ipv-lazy-load-thumbnail {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            cursor: pointer !important;
            background-size: cover !important;
            background-position: center !important;
            transition: filter 0.3s ease !important;
        }

        .ipv-lazy-load-thumbnail:hover {
            filter: brightness(1.1) !important;
        }

        .ipv-play-button {
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            width: 80px !important;
            height: 56px !important;
            background: rgba(255, 0, 0, 0.9) !important;
            border-radius: 14px !important;
            transition: all 0.3s ease !important;
            z-index: 10 !important;
        }

        .ipv-lazy-load-thumbnail:hover .ipv-play-button {
            background: rgba(255, 0, 0, 1) !important;
            transform: translate(-50%, -50%) scale(1.1) !important;
        }

        .ipv-play-button::before {
            content: "" !important;
            position: absolute !important;
            top: 50% !important;
            left: 55% !important;
            transform: translate(-50%, -50%) !important;
            width: 0 !important;
            height: 0 !important;
            border-style: solid !important;
            border-width: 12px 0 12px 20px !important;
            border-color: transparent transparent transparent #fff !important;
        }
        </style>
        <div class="ipv-video-embed-container">
            <div class="ipv-embed-wrapper">
                <div class="ipv-lazy-load-thumbnail"
                     data-video-id="' . esc_attr( $yt_id ) . '"
                     style="background-image: url(https://i.ytimg.com/vi/' . esc_attr( $yt_id ) . '/maxresdefault.jpg);">
                    <div class="ipv-play-button"></div>
                </div>
            </div>
        </div>
        <script>
        (function() {
            var thumbnail = document.querySelector(".ipv-lazy-load-thumbnail");
            if (thumbnail) {
                thumbnail.addEventListener("click", function() {
                    var videoId = this.getAttribute("data-video-id");
                    var iframe = document.createElement("iframe");
                    iframe.setAttribute("src", "https://www.youtube.com/embed/" + videoId + "?autoplay=1&rel=0&modestbranding=1&showinfo=0");
                    iframe.setAttribute("allow", "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share");
                    iframe.setAttribute("allowfullscreen", "");
                    iframe.style.cssText = "position: absolute !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; border: 0 !important;";
                    this.parentNode.replaceChild(iframe, this);
                });
            }
        })();
        </script>
        ';

        return $embed_html . $content;
    }

    /**
     * Inietta CSS per embed nel <head>
     */
    public static function inject_embed_styles() {
        if ( ! is_singular( 'ipv_video' ) ) {
            return;
        }
        ?>
        <style>
        /* Container principale - width 100% dell'area contenuto (non viewport) */
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
        <?php
    }

    /**
     * Output embed all'inizio del loop per temi che non usano the_content standard
     * NOTA: Disabilitato di default, usare solo se the_content non funziona
     */
    public static function maybe_output_embed( $query ) {
        // Solo per main query di ipv_video singolo
        if ( ! $query->is_main_query() || ! is_singular( 'ipv_video' ) ) {
            return;
        }

        // Evita duplicazione con prepend_youtube_embed
        if ( self::$embed_rendered ) {
            return;
        }

        $post_id = get_the_ID();
        $yt_id   = get_post_meta( $post_id, '_ipv_video_id', true );

        if ( empty( $yt_id ) ) {
            return;
        }

        // Segna come già renderizzato
        self::$embed_rendered = true;

        // Output dell'embed
        ?>
        <div class="ipv-video-embed-container">
            <div class="ipv-embed-wrapper">
                <iframe
                    src="https://www.youtube.com/embed/<?php echo esc_attr( $yt_id ); ?>?rel=0&modestbranding=1&showinfo=0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                    loading="lazy">
                </iframe>
            </div>
        </div>
        <?php
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
     * Nasconde tag, categorie, metadati e featured image per ipv_video singolo
     */
    public static function hide_tags_and_meta() {
        if ( ! is_singular( 'ipv_video' ) ) {
            return;
        }
        ?>
        <style>
        /* =====================================================
         * NASCONDE FEATURED IMAGE SOLO NEL SINGOLO POST
         * La featured image rimane visibile negli archivi/related posts
         * Selettori specifici per tema Influencers
         * ===================================================== */
        
        /* TEMA INFLUENCERS - Featured image nel post principale */
        body.single-ipv_video .bt-main-post > .bt-post > .bt-post--featured,
        body.single-ipv_video .bt-main-post > article > .bt-post--featured,
        body.single-ipv_video .bt-main-post .bt-post > .bt-post--featured,
        body.single-ipv_video article.bt-post > .bt-post--featured:first-of-type {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            max-height: 0 !important;
            overflow: hidden !important;
            opacity: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* ESCLUSIONE ESPLICITA - Related Posts devono MANTENERE le thumbnail */
        body.single-ipv_video .bt-related-posts .bt-post--featured,
        body.single-ipv_video .bt-related-posts .bt-post--inner .bt-post--featured,
        body.single-ipv_video .bt-related-posts .bt-cover-image {
            display: block !important;
            visibility: visible !important;
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
            opacity: 1 !important;
        }
        
        body.single-ipv_video .bt-related-posts .bt-post--featured img,
        body.single-ipv_video .bt-related-posts .bt-cover-image img {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            height: auto !important;
            max-height: none !important;
        }

        /* FIX BARRA GRIGIA - Related Posts */
        body.single-ipv_video .bt-related-posts .bt-cover-image {
            padding-bottom: 0 !important;
            background: transparent !important;
        }
        body.single-ipv_video .bt-related-posts .bt-cover-image::before,
        body.single-ipv_video .bt-related-posts .bt-cover-image::after,
        body.single-ipv_video .bt-related-posts .bt-post--featured::before,
        body.single-ipv_video .bt-related-posts .bt-post--featured::after {
            display: none !important;
            content: none !important;
        }
        body.single-ipv_video .bt-related-posts .bt-post--featured a {
            display: block !important;
            line-height: 0 !important;
        }
        body.single-ipv_video .bt-related-posts .bt-cover-image img {
            position: relative !important;
            width: 100% !important;
            height: auto !important;
            object-fit: cover !important;
        }

        /* Fallback per altri temi - solo nel main content, non nei widget/related */
        body.single-ipv_video .bt-main-post-col > .bt-main-post .post-thumbnail,
        body.single-ipv_video .bt-main-post-col > .bt-main-post .featured-image,
        body.single-ipv_video .bt-main-post-col > .bt-main-post .entry-thumbnail,
        body.single-ipv_video .bt-main-post-col > .bt-main-post .wp-post-image {
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
