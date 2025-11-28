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
        /* Container principale - width 100% senza margini laterali */
        body.single-ipv_video .ipv-video-embed-container,
        body .ipv-video-embed-container {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 0 30px 0 !important;
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
            border-radius: 12px !important;
            background: #000 !important;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2) !important;
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
            border-radius: 12px !important;
            margin: 0 !important;
            padding: 0 !important;
            display: block !important;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            body.single-ipv_video .ipv-video-embed-container,
            body .ipv-video-embed-container {
                margin-bottom: 20px !important;
            }
            body.single-ipv_video .ipv-video-embed-container .ipv-embed-wrapper,
            body .ipv-video-embed-container .ipv-embed-wrapper {
                border-radius: 8px !important;
            }
            body.single-ipv_video .ipv-video-embed-container .ipv-embed-wrapper iframe,
            body .ipv-video-embed-container .ipv-embed-wrapper iframe {
                border-radius: 8px !important;
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
}

IPV_Prod_Video_Frontend::init();
