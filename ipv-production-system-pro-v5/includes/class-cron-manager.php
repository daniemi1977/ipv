<?php
/**
 * Cron Manager Migliorato per IPV Production System Pro v5
 *
 * Gestisce tutti i cron job del plugin in modo centralizzato:
 * - Download automatico (RSS)
 * - Trascrizione automatica
 * - Generazione descrizione SEO
 * - Processamento coda
 *
 * @package IPV_Production_System_Pro
 * @version 5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Cron_Manager {

    /**
     * Intervalli cron personalizzati
     */
    public static function add_schedules( $schedules ) {
        // Ogni minuto (per processing coda)
        if ( ! isset( $schedules['minute'] ) ) {
            $schedules['minute'] = [
                'interval' => 60,
                'display'  => __( 'Ogni minuto', 'ipv-production-system-pro' ),
            ];
        }

        // Ogni 5 minuti
        if ( ! isset( $schedules['every_5_minutes'] ) ) {
            $schedules['every_5_minutes'] = [
                'interval' => 300,
                'display'  => __( 'Ogni 5 minuti', 'ipv-production-system-pro' ),
            ];
        }

        // Ogni 15 minuti
        if ( ! isset( $schedules['every_15_minutes'] ) ) {
            $schedules['every_15_minutes'] = [
                'interval' => 900,
                'display'  => __( 'Ogni 15 minuti', 'ipv-production-system-pro' ),
            ];
        }

        // Ogni 30 minuti
        if ( ! isset( $schedules['every_30_minutes'] ) ) {
            $schedules['every_30_minutes'] = [
                'interval' => 1800,
                'display'  => __( 'Ogni 30 minuti', 'ipv-production-system-pro' ),
            ];
        }

        // Ogni ora
        if ( ! isset( $schedules['hourly'] ) ) {
            $schedules['hourly'] = [
                'interval' => 3600,
                'display'  => __( 'Ogni ora', 'ipv-production-system-pro' ),
            ];
        }

        // Ogni 6 ore
        if ( ! isset( $schedules['every_6_hours'] ) ) {
            $schedules['every_6_hours'] = [
                'interval' => 21600,
                'display'  => __( 'Ogni 6 ore', 'ipv-production-system-pro' ),
            ];
        }

        return $schedules;
    }

    /**
     * Attiva tutti i cron job
     */
    public static function activate() {
        // 1. Process Queue (ogni minuto)
        if ( ! wp_next_scheduled( 'ipv_prod_process_queue' ) ) {
            wp_schedule_event( time() + 60, 'minute', 'ipv_prod_process_queue' );
            IPV_Prod_Logger::log( 'Cron Manager: Attivato process_queue (ogni minuto)' );
        }

        // 2. RSS Auto-Import (se abilitato)
        $rss_enabled = get_option( 'ipv_rss_enabled', false );
        if ( $rss_enabled ) {
            self::activate_rss_cron();
        }

        // 3. Auto-trascrizione (processamento video senza trascrizione)
        if ( ! wp_next_scheduled( 'ipv_prod_auto_transcribe' ) ) {
            wp_schedule_event( time() + 300, 'every_15_minutes', 'ipv_prod_auto_transcribe' );
            IPV_Prod_Logger::log( 'Cron Manager: Attivato auto_transcribe (ogni 15 min)' );
        }

        // 4. Auto-generazione descrizione (video con trascrizione ma senza descrizione AI)
        if ( ! wp_next_scheduled( 'ipv_prod_auto_generate_desc' ) ) {
            wp_schedule_event( time() + 600, 'every_15_minutes', 'ipv_prod_auto_generate_desc' );
            IPV_Prod_Logger::log( 'Cron Manager: Attivato auto_generate_desc (ogni 15 min)' );
        }

        // Registra hook actions
        add_action( 'ipv_prod_auto_transcribe', [ __CLASS__, 'auto_transcribe_videos' ] );
        add_action( 'ipv_prod_auto_generate_desc', [ __CLASS__, 'auto_generate_descriptions' ] );
    }

    /**
     * Disattiva tutti i cron job
     */
    public static function deactivate() {
        // Process Queue
        $timestamp = wp_next_scheduled( 'ipv_prod_process_queue' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'ipv_prod_process_queue' );
            IPV_Prod_Logger::log( 'Cron Manager: Disattivato process_queue' );
        }

        // RSS Import
        self::deactivate_rss_cron();

        // Auto-trascrizione
        $timestamp = wp_next_scheduled( 'ipv_prod_auto_transcribe' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'ipv_prod_auto_transcribe' );
            IPV_Prod_Logger::log( 'Cron Manager: Disattivato auto_transcribe' );
        }

        // Auto-generazione descrizione
        $timestamp = wp_next_scheduled( 'ipv_prod_auto_generate_desc' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'ipv_prod_auto_generate_desc' );
            IPV_Prod_Logger::log( 'Cron Manager: Disattivato auto_generate_desc' );
        }
    }

    /**
     * Attiva RSS cron
     */
    public static function activate_rss_cron() {
        $schedule = get_option( 'ipv_rss_schedule', 'hourly' );

        if ( ! wp_next_scheduled( 'ipv_prod_rss_import' ) ) {
            wp_schedule_event( time() + 300, $schedule, 'ipv_prod_rss_import' );
            IPV_Prod_Logger::log( 'Cron Manager: Attivato RSS import (' . $schedule . ')' );
        }
    }

    /**
     * Disattiva RSS cron
     */
    public static function deactivate_rss_cron() {
        $timestamp = wp_next_scheduled( 'ipv_prod_rss_import' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'ipv_prod_rss_import' );
            IPV_Prod_Logger::log( 'Cron Manager: Disattivato RSS import' );
        }
    }

    /**
     * Auto-trascrizione: Trova video senza trascrizione e la genera
     */
    public static function auto_transcribe_videos() {
        $videos = get_posts( [
            'post_type'      => 'video_ipv',
            'post_status'    => 'any',
            'posts_per_page' => 5, // Processa max 5 video per run
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => '_ipv_video_id',
                    'compare' => 'EXISTS',
                ],
                [
                    'key'     => '_ipv_transcript',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ] );

        if ( empty( $videos ) ) {
            return;
        }

        $success_count = 0;
        $error_count   = 0;

        foreach ( $videos as $video ) {
            $video_id = get_post_meta( $video->ID, '_ipv_video_id', true );

            if ( empty( $video_id ) ) {
                continue;
            }

            // Genera trascrizione
            $mode       = get_option( 'ipv_transcript_mode', 'auto' );
            $transcript = IPV_Prod_Supadata::get_transcript( $video_id, $mode );

            if ( is_wp_error( $transcript ) ) {
                $error_count++;
                IPV_Prod_Logger::log( 'Auto-Transcribe: Errore video ' . $video->ID, [
                    'error' => $transcript->get_error_message(),
                ] );
                continue;
            }

            update_post_meta( $video->ID, '_ipv_transcript', $transcript );
            $success_count++;
        }

        if ( $success_count > 0 ) {
            IPV_Prod_Logger::log( 'Auto-Transcribe: Completato', [
                'success' => $success_count,
                'errors'  => $error_count,
            ] );
        }
    }

    /**
     * Auto-generazione descrizione: Trova video con trascrizione ma senza descrizione AI
     */
    public static function auto_generate_descriptions() {
        $videos = get_posts( [
            'post_type'      => 'video_ipv',
            'post_status'    => 'any',
            'posts_per_page' => 3, // Processa max 3 video per run (API costosa)
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => '_ipv_transcript',
                    'compare' => 'EXISTS',
                ],
                [
                    'key'     => '_ipv_ai_description',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ] );

        if ( empty( $videos ) ) {
            return;
        }

        $success_count = 0;
        $error_count   = 0;

        foreach ( $videos as $video ) {
            $transcript = get_post_meta( $video->ID, '_ipv_transcript', true );

            if ( empty( $transcript ) ) {
                continue;
            }

            // Genera descrizione AI
            $title = get_the_title( $video->ID );
            $desc  = IPV_Prod_AI_Generator::generate_description( $title, $transcript );

            if ( is_wp_error( $desc ) ) {
                $error_count++;
                IPV_Prod_Logger::log( 'Auto-Generate-Desc: Errore video ' . $video->ID, [
                    'error' => $desc->get_error_message(),
                ] );
                continue;
            }

            // Salva descrizione
            wp_update_post( [
                'ID'           => $video->ID,
                'post_content' => $desc,
            ] );
            update_post_meta( $video->ID, '_ipv_ai_description', $desc );

            // Trigger auto-tagger
            IPV_Prod_Auto_Tagger::auto_tag_video( $video->ID, get_post( $video->ID ) );

            $success_count++;
        }

        if ( $success_count > 0 ) {
            IPV_Prod_Logger::log( 'Auto-Generate-Desc: Completato', [
                'success' => $success_count,
                'errors'  => $error_count,
            ] );
        }
    }

    /**
     * Ottieni lo stato di tutti i cron
     */
    public static function get_cron_status() {
        return [
            'process_queue' => [
                'name'      => 'Process Queue',
                'active'    => (bool) wp_next_scheduled( 'ipv_prod_process_queue' ),
                'next_run'  => wp_next_scheduled( 'ipv_prod_process_queue' ),
                'schedule'  => 'minute',
            ],
            'rss_import' => [
                'name'      => 'RSS Auto-Import',
                'active'    => (bool) wp_next_scheduled( 'ipv_prod_rss_import' ),
                'next_run'  => wp_next_scheduled( 'ipv_prod_rss_import' ),
                'schedule'  => get_option( 'ipv_rss_schedule', 'hourly' ),
            ],
            'auto_transcribe' => [
                'name'      => 'Auto-Transcribe',
                'active'    => (bool) wp_next_scheduled( 'ipv_prod_auto_transcribe' ),
                'next_run'  => wp_next_scheduled( 'ipv_prod_auto_transcribe' ),
                'schedule'  => 'every_15_minutes',
            ],
            'auto_generate_desc' => [
                'name'      => 'Auto-Generate Descriptions',
                'active'    => (bool) wp_next_scheduled( 'ipv_prod_auto_generate_desc' ),
                'next_run'  => wp_next_scheduled( 'ipv_prod_auto_generate_desc' ),
                'schedule'  => 'every_15_minutes',
            ],
        ];
    }

    /**
     * Forza l'esecuzione di un cron specifico
     */
    public static function force_run( $cron_name ) {
        switch ( $cron_name ) {
            case 'process_queue':
                IPV_Prod_Queue::process_queue();
                break;

            case 'rss_import':
                IPV_Prod_RSS_Importer::process_rss_feed();
                break;

            case 'auto_transcribe':
                self::auto_transcribe_videos();
                break;

            case 'auto_generate_desc':
                self::auto_generate_descriptions();
                break;

            default:
                return new WP_Error( 'invalid_cron', 'Cron job non valido' );
        }

        IPV_Prod_Logger::log( 'Cron Manager: Esecuzione forzata ' . $cron_name );

        return true;
    }
}
