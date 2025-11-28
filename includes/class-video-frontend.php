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
        /* Container principale - FULL WIDTH viewport breakout */
        body.single-ipv_video .ipv-video-embed-container,
        body .ipv-video-embed-container {
            width: 100vw !important;
            max-width: 100vw !important;
            position: relative !important;
            left: 50% !important;
            right: 50% !important;
            margin-left: -50vw !important;
            margin-right: -50vw !important;
            margin-top: 0 !important;
            margin-bottom: 40px !important;
            padding: 0 !important;
            display: block !important;
            clear: both !important;
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
                margin-bottom: 30px !important;
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
     * Rimuove featured image per ipv_video
     */
    public static function remove_featured_image( $html, $post_id ) {
        if ( get_post_type( $post_id ) === 'ipv_video' ) {
            return '';
        }
        return $html;
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
        /* Nasconde featured image e immagini in evidenza */
        body.single-ipv_video .post-thumbnail,
        body.single-ipv_video .entry-thumbnail,
        body.single-ipv_video .featured-image,
        body.single-ipv_video .wp-post-image,
        body.single-ipv_video .attachment-post-thumbnail {
            display: none !important;
        }

        /* Nasconde tag e categorie cliccabili */
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
        body.single-ipv_video .taxonomy-links {
            display: none !important;
        }

        /* Nasconde anche i link alle tassonomie custom */
        body.single-ipv_video .ipv_categoria-links,
        body.single-ipv_video .ipv_relatore-links,
        body.single-ipv_video .term-links {
            display: none !important;
        }

        /* Mobile: stesse regole */
        @media (max-width: 768px) {
            body.single-ipv_video .post-thumbnail,
            body.single-ipv_video .entry-thumbnail,
            body.single-ipv_video .featured-image,
            body.single-ipv_video .entry-meta,
            body.single-ipv_video .post-meta,
            body.single-ipv_video .entry-footer,
            body.single-ipv_video .post-categories,
            body.single-ipv_video .post-tags {
                display: none !important;
            }
        }
        </style>
        <?php
    }
}

IPV_Prod_Video_Frontend::init();
