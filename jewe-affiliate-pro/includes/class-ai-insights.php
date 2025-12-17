<?php
/**
 * AI Insights Handler
 *
 * @package JEWE_Affiliate_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class JEWE_Affiliate_AI_Insights {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function init_hooks() {
        // Schedule daily insight generation
        if (!wp_next_scheduled('jewe_generate_ai_insights')) {
            wp_schedule_event(time(), 'daily', 'jewe_generate_ai_insights');
        }
        add_action('jewe_generate_ai_insights', [$this, 'generate_all_insights']);
    }

    /**
     * Generate insights for all active affiliates
     */
    public function generate_all_insights() {
        if (get_option('jewe_affiliate_ai_insights_enabled', 'yes') !== 'yes') {
            return;
        }

        $affiliates = JEWE_Affiliate_Database::get_affiliates(['status' => 'active', 'limit' => 1000]);

        foreach ($affiliates as $affiliate) {
            $this->generate_insights($affiliate->id);
        }
    }

    /**
     * Generate insights for specific affiliate
     */
    public function generate_insights($affiliate_id) {
        $insights = [];

        // Performance analysis
        $performance = $this->analyze_performance($affiliate_id);
        if ($performance) {
            $insights[] = $performance;
        }

        // Conversion optimization
        $conversion = $this->analyze_conversion($affiliate_id);
        if ($conversion) {
            $insights[] = $conversion;
        }

        // Traffic analysis
        $traffic = $this->analyze_traffic($affiliate_id);
        if ($traffic) {
            $insights[] = $traffic;
        }

        // MLM opportunity
        $mlm = $this->analyze_mlm_opportunity($affiliate_id);
        if ($mlm) {
            $insights[] = $mlm;
        }

        // Tier progress
        $tier = $this->analyze_tier_progress($affiliate_id);
        if ($tier) {
            $insights[] = $tier;
        }

        // Save insights
        $this->save_insights($affiliate_id, $insights);

        return $insights;
    }

    /**
     * Analyze performance
     */
    private function analyze_performance($affiliate_id) {
        $stats_30 = JEWE_Affiliate::get_stats($affiliate_id, '30days');
        $stats_prev = JEWE_Affiliate::get_stats($affiliate_id, '60days'); // Gets 60 days total

        // Compare current 30 days with previous 30 days
        $earnings_change = 0;
        if ($stats_prev['earnings_total'] > $stats_30['earnings_total']) {
            $prev_30_earnings = $stats_prev['earnings_total'] - $stats_30['earnings_total'];
            if ($prev_30_earnings > 0) {
                $earnings_change = (($stats_30['earnings_total'] - $prev_30_earnings) / $prev_30_earnings) * 100;
            }
        }

        $insight = [
            'type' => 'performance',
            'score' => 0,
            'title' => '',
            'message' => '',
            'recommendations' => [],
        ];

        if ($stats_30['earnings_total'] == 0) {
            $insight['score'] = 20;
            $insight['title'] = __('Inizia a guadagnare!', 'jewe-affiliate-pro');
            $insight['message'] = __('Non hai ancora guadagnato commissioni questo mese. Ecco come iniziare:', 'jewe-affiliate-pro');
            $insight['recommendations'] = [
                __('Condividi il tuo link sui social media', 'jewe-affiliate-pro'),
                __('Crea contenuti che parlano dei prodotti', 'jewe-affiliate-pro'),
                __('Usa i QR code per promozioni offline', 'jewe-affiliate-pro'),
            ];
        } elseif ($earnings_change > 20) {
            $insight['score'] = 90;
            $insight['title'] = __('Ottima performance!', 'jewe-affiliate-pro');
            $insight['message'] = sprintf(
                __('I tuoi guadagni sono aumentati del %d%% rispetto al mese scorso!', 'jewe-affiliate-pro'),
                round($earnings_change)
            );
            $insight['recommendations'] = [
                __('Continua con la strategia attuale', 'jewe-affiliate-pro'),
                __('Considera di investire di più nei canali che funzionano', 'jewe-affiliate-pro'),
            ];
        } elseif ($earnings_change < -20) {
            $insight['score'] = 40;
            $insight['title'] = __('Performance in calo', 'jewe-affiliate-pro');
            $insight['message'] = sprintf(
                __('I tuoi guadagni sono diminuiti del %d%%. Analizziamo cosa è cambiato.', 'jewe-affiliate-pro'),
                abs(round($earnings_change))
            );
            $insight['recommendations'] = [
                __('Rivedi le tue fonti di traffico principali', 'jewe-affiliate-pro'),
                __('Prova nuovi canali di promozione', 'jewe-affiliate-pro'),
                __('Aggiorna i tuoi contenuti promozionali', 'jewe-affiliate-pro'),
            ];
        } else {
            $insight['score'] = 60;
            $insight['title'] = __('Performance stabile', 'jewe-affiliate-pro');
            $insight['message'] = __('Le tue performance sono stabili. Ecco come migliorare:', 'jewe-affiliate-pro');
            $insight['recommendations'] = [
                __('Sperimenta con nuovi tipi di contenuto', 'jewe-affiliate-pro'),
                __('Aumenta la frequenza di pubblicazione', 'jewe-affiliate-pro'),
            ];
        }

        return $insight;
    }

    /**
     * Analyze conversion rate
     */
    private function analyze_conversion($affiliate_id) {
        $stats = JEWE_Affiliate::get_stats($affiliate_id, '30days');

        $insight = [
            'type' => 'conversion',
            'score' => 0,
            'title' => '',
            'message' => '',
            'recommendations' => [],
        ];

        if ($stats['total_clicks'] < 10) {
            $insight['score'] = 30;
            $insight['title'] = __('Aumenta il traffico', 'jewe-affiliate-pro');
            $insight['message'] = __('Hai pochi click. Concentrati prima sul generare più traffico.', 'jewe-affiliate-pro');
            $insight['recommendations'] = [
                __('Condividi i link più frequentemente', 'jewe-affiliate-pro'),
                __('Usa più canali di promozione', 'jewe-affiliate-pro'),
            ];
        } elseif ($stats['conversion_rate'] < 1) {
            $insight['score'] = 35;
            $insight['title'] = __('Tasso di conversione basso', 'jewe-affiliate-pro');
            $insight['message'] = sprintf(
                __('Il tuo tasso di conversione è %s%%. La media è 2-3%%.', 'jewe-affiliate-pro'),
                $stats['conversion_rate']
            );
            $insight['recommendations'] = [
                __('Rivedi il target del tuo pubblico', 'jewe-affiliate-pro'),
                __('Migliora la qualità dei contenuti', 'jewe-affiliate-pro'),
                __('Usa call-to-action più efficaci', 'jewe-affiliate-pro'),
            ];
        } elseif ($stats['conversion_rate'] >= 3) {
            $insight['score'] = 95;
            $insight['title'] = __('Eccellente tasso di conversione!', 'jewe-affiliate-pro');
            $insight['message'] = sprintf(
                __('Il tuo tasso di conversione del %s%% è superiore alla media!', 'jewe-affiliate-pro'),
                $stats['conversion_rate']
            );
            $insight['recommendations'] = [
                __('Scala questa strategia con più traffico', 'jewe-affiliate-pro'),
                __('Documenta cosa funziona per replicarlo', 'jewe-affiliate-pro'),
            ];
        } else {
            $insight['score'] = 65;
            $insight['title'] = __('Conversione nella media', 'jewe-affiliate-pro');
            $insight['message'] = sprintf(
                __('Il tuo tasso di conversione è %s%%, in linea con la media.', 'jewe-affiliate-pro'),
                $stats['conversion_rate']
            );
            $insight['recommendations'] = [
                __('Test A/B sui tuoi contenuti', 'jewe-affiliate-pro'),
                __('Ottimizza le landing page', 'jewe-affiliate-pro'),
            ];
        }

        return $insight;
    }

    /**
     * Analyze traffic sources
     */
    private function analyze_traffic($affiliate_id) {
        $traffic = JEWE_Affiliate_Tracking::get_traffic_by_source($affiliate_id, '30days');

        if (empty($traffic)) {
            return null;
        }

        $insight = [
            'type' => 'traffic',
            'score' => 50,
            'title' => __('Analisi traffico', 'jewe-affiliate-pro'),
            'message' => '',
            'recommendations' => [],
            'data' => $traffic,
        ];

        // Find best converting source
        $best_source = null;
        $best_rate = 0;

        foreach ($traffic as $source) {
            if ($source->clicks >= 5) {
                $rate = $source->conversions / $source->clicks;
                if ($rate > $best_rate) {
                    $best_rate = $rate;
                    $best_source = $source;
                }
            }
        }

        if ($best_source && $best_rate > 0.02) {
            $insight['score'] = 75;
            $insight['message'] = sprintf(
                __('Il tuo canale migliore è %s con %d%% di conversione.', 'jewe-affiliate-pro'),
                $best_source->source,
                round($best_rate * 100)
            );
            $insight['recommendations'][] = sprintf(
                __('Investi di più su %s', 'jewe-affiliate-pro'),
                $best_source->source
            );
        } else {
            $insight['message'] = __('Analizza le tue fonti di traffico per capire cosa funziona meglio.', 'jewe-affiliate-pro');
        }

        // Check for diversification
        if (count($traffic) < 3) {
            $insight['recommendations'][] = __('Diversifica le tue fonti di traffico', 'jewe-affiliate-pro');
        }

        return $insight;
    }

    /**
     * Analyze MLM opportunity
     */
    private function analyze_mlm_opportunity($affiliate_id) {
        if (get_option('jewe_affiliate_mlm_enabled', 'yes') !== 'yes') {
            return null;
        }

        $mlm_stats = JEWE_Affiliate_MLM::get_stats($affiliate_id);

        $insight = [
            'type' => 'mlm',
            'score' => 50,
            'title' => __('Opportunità Team', 'jewe-affiliate-pro'),
            'message' => '',
            'recommendations' => [],
        ];

        if (!$mlm_stats || $mlm_stats->direct_referrals == 0) {
            $insight['score'] = 30;
            $insight['message'] = __('Non hai ancora invitato altri affiliati. Costruisci il tuo team per guadagnare di più!', 'jewe-affiliate-pro');
            $insight['recommendations'] = [
                __('Invita amici e colleghi a diventare affiliati', 'jewe-affiliate-pro'),
                __('Guadagna commissioni anche dalle loro vendite', 'jewe-affiliate-pro'),
            ];
        } elseif ($mlm_stats->team_size < 5) {
            $insight['score'] = 50;
            $insight['message'] = sprintf(
                __('Hai %d affiliati nel team. Continua a crescere!', 'jewe-affiliate-pro'),
                $mlm_stats->team_size
            );
            $insight['recommendations'] = [
                __('Supporta i tuoi affiliati per farli crescere', 'jewe-affiliate-pro'),
                __('Condividi le tue strategie vincenti', 'jewe-affiliate-pro'),
            ];
        } else {
            $insight['score'] = 80;
            $insight['message'] = sprintf(
                __('Il tuo team di %d affiliati ha generato €%s!', 'jewe-affiliate-pro'),
                $mlm_stats->team_size,
                number_format($mlm_stats->team_earnings, 2)
            );
            $insight['recommendations'] = [
                __('Organizza formazione per il team', 'jewe-affiliate-pro'),
                __('Premia i top performer', 'jewe-affiliate-pro'),
            ];
        }

        return $insight;
    }

    /**
     * Analyze tier progress
     */
    private function analyze_tier_progress($affiliate_id) {
        $progress = JEWE_Affiliate_Gamification::get_tier_progress($affiliate_id);

        if (!$progress || $progress['is_max_tier']) {
            return [
                'type' => 'tier',
                'score' => 100,
                'title' => __('Livello Massimo!', 'jewe-affiliate-pro'),
                'message' => __('Hai raggiunto il livello massimo. Sei un top affiliate!', 'jewe-affiliate-pro'),
                'recommendations' => [],
            ];
        }

        $insight = [
            'type' => 'tier',
            'score' => max($progress['earnings_progress'], $progress['referrals_progress']),
            'title' => sprintf(__('Progresso verso %s', 'jewe-affiliate-pro'), $progress['next_tier']->name),
            'message' => '',
            'recommendations' => [],
        ];

        if ($progress['earnings_progress'] >= 80 || $progress['referrals_progress'] >= 80) {
            $insight['message'] = __('Sei quasi al prossimo livello!', 'jewe-affiliate-pro');

            if ($progress['earnings_needed'] > 0) {
                $insight['recommendations'][] = sprintf(
                    __('Ti mancano €%s in guadagni', 'jewe-affiliate-pro'),
                    number_format($progress['earnings_needed'], 2)
                );
            }
            if ($progress['referrals_needed'] > 0) {
                $insight['recommendations'][] = sprintf(
                    __('Ti mancano %d referral', 'jewe-affiliate-pro'),
                    $progress['referrals_needed']
                );
            }
        } else {
            $insight['message'] = sprintf(
                __('Livello attuale: %s. Prossimo: %s', 'jewe-affiliate-pro'),
                $progress['current_tier']->name,
                $progress['next_tier']->name
            );
            $insight['recommendations'][] = sprintf(
                __('Guadagna altri €%s per salire di livello', 'jewe-affiliate-pro'),
                number_format($progress['earnings_needed'], 2)
            );
        }

        return $insight;
    }

    /**
     * Save insights to database
     */
    private function save_insights($affiliate_id, $insights) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_ai_insights';

        // Delete old insights
        $wpdb->delete($table, ['affiliate_id' => $affiliate_id]);

        // Insert new insights
        $expires = date('Y-m-d H:i:s', strtotime('+1 day'));

        foreach ($insights as $insight) {
            $wpdb->insert($table, [
                'affiliate_id' => $affiliate_id,
                'insight_type' => $insight['type'],
                'insight_data' => json_encode($insight),
                'score' => $insight['score'],
                'expires_at' => $expires,
            ]);
        }
    }

    /**
     * Get insights for affiliate
     */
    public static function get_insights($affiliate_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_ai_insights';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table
             WHERE affiliate_id = %d AND expires_at > NOW()
             ORDER BY score DESC",
            $affiliate_id
        ));

        $insights = [];
        foreach ($results as $row) {
            $insights[] = json_decode($row->insight_data, true);
        }

        // If no cached insights, generate new ones
        if (empty($insights)) {
            $generator = self::instance();
            $insights = $generator->generate_insights($affiliate_id);
        }

        return $insights;
    }

    /**
     * Get overall score
     */
    public static function get_overall_score($affiliate_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_ai_insights';

        return floatval($wpdb->get_var($wpdb->prepare(
            "SELECT AVG(score) FROM $table WHERE affiliate_id = %d AND expires_at > NOW()",
            $affiliate_id
        )) ?: 50);
    }
}
