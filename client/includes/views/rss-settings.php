<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap ipv-wrap">
    <div class="ipv-header">
        <h1>📡 Auto-Import RSS</h1>
        <p>Importa automaticamente nuovi video dal tuo canale YouTube</p>
    </div>

    <?php if ( isset( $test_result ) ) : ?>
        <div class="ipv-notice ipv-notice-<?php echo $test_result['success'] ? 'success' : 'danger'; ?>">
            <strong>Test Feed:</strong> <?php echo esc_html( $test_result['message'] ); ?>
            <?php if ( $test_result['success'] ) : ?>
                <br><small>Trovati <?php echo intval( $test_result['video_count'] ); ?> video nel feed</small>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ( isset( $import_result ) ) : ?>
        <div class="ipv-notice ipv-notice-success">
            <strong><?php echo esc_html( $import_result['message'] ); ?></strong>
        </div>
    <?php endif; ?>

    <div class="ipv-row">
        <div class="ipv-col-8">
            <div class="ipv-card ipv-card-primary">
                <div class="ipv-card-header">
                    <h3>⚙️ Configurazione Feed RSS</h3>
                </div>
                <div class="ipv-card-body">
                    <form method="post" action="">
                        <?php wp_nonce_field( 'ipv_rss_settings_save' ); ?>
                        <input type="hidden" name="ipv_save_rss_settings" value="1" />

                        <div class="ipv-form-group">
                            <label class="ipv-flex ipv-items-center ipv-gap-1">
                                <input type="checkbox" name="ipv_rss_enabled" id="ipv_rss_enabled" value="1" <?php checked( $rss_enabled, true ); ?> />
                                <strong>Abilita Auto-Import RSS</strong>
                            </label>
                            <p class="ipv-form-hint">Quando attivo, il sistema controlla automaticamente il feed e importa nuovi video</p>
                        </div>

                        <div class="ipv-form-group">
                            <label class="ipv-form-label">URL Feed RSS YouTube <span class="ipv-text-danger">*</span></label>
                            <input type="url" class="ipv-form-input" name="ipv_rss_feed_url" value="<?php echo esc_attr( $feed_url ); ?>" placeholder="https://www.youtube.com/feeds/videos.xml?channel_id=UC..." required />
                            <p class="ipv-form-hint">Formato: <code>https://www.youtube.com/feeds/videos.xml?channel_id=TUO_CHANNEL_ID</code></p>
                        </div>

                        <div class="ipv-form-group">
                            <label class="ipv-form-label">Frequenza Controllo</label>
                            <select name="ipv_rss_schedule" class="ipv-form-select">
                                <option value="every_30_minutes" <?php selected( $rss_schedule, 'every_30_minutes' ); ?>>Ogni 30 minuti (più frequente)</option>
                                <option value="hourly" <?php selected( $rss_schedule, 'hourly' ); ?>>Ogni ora (consigliato)</option>
                                <option value="every_6_hours" <?php selected( $rss_schedule, 'every_6_hours' ); ?>>Ogni 6 ore</option>
                                <option value="twicedaily" <?php selected( $rss_schedule, 'twicedaily' ); ?>>Due volte al giorno</option>
                                <option value="daily" <?php selected( $rss_schedule, 'daily' ); ?>>Una volta al giorno</option>
                            </select>
                            <p class="ipv-form-hint">Quanto spesso controllare il feed per nuovi video</p>
                        </div>

                        <div class="ipv-form-group">
                            <label class="ipv-form-label">Limite Video per Controllo</label>
                            <input type="number" class="ipv-form-input" name="ipv_rss_import_limit" value="<?php echo intval( $import_limit ); ?>" min="1" max="50" />
                            <p class="ipv-form-hint">Numero massimo di video da importare ad ogni controllo (consigliato: 10)</p>
                        </div>

                        <button type="submit" class="ipv-btn ipv-btn-primary">💾 Salva Configurazione</button>
                    </form>
                </div>
            </div>

            <div class="ipv-card ipv-mt-3">
                <div class="ipv-card-header">
                    <h3>🧪 Test & Importazione Manuale</h3>
                </div>
                <div class="ipv-card-body">
                    <div class="ipv-flex ipv-gap-2 ipv-flex-wrap">
                        <form method="post" action="" style="display: inline;">
                            <?php wp_nonce_field( 'ipv_rss_test_feed' ); ?>
                            <input type="hidden" name="ipv_test_feed" value="1" />
                            <button type="submit" class="ipv-btn ipv-btn-secondary">🔍 Testa Feed</button>
                        </form>
                        <form method="post" action="" style="display: inline;">
                            <?php wp_nonce_field( 'ipv_rss_import_now' ); ?>
                            <input type="hidden" name="ipv_import_now" value="1" />
                            <button type="submit" class="ipv-btn ipv-btn-success">▶️ Importa Ora</button>
                        </form>
                    </div>
                    <p class="ipv-form-hint ipv-mt-1"><strong>Testa Feed:</strong> Verifica che il feed sia valido | <strong>Importa Ora:</strong> Forza l'importazione immediata</p>
                </div>
            </div>
        </div>

        <div class="ipv-col-4">
            <div class="ipv-card ipv-card-success">
                <div class="ipv-card-header">
                    <h3>📊 Statistiche RSS</h3>
                </div>
                <div class="ipv-card-body">
                    <div class="ipv-flex ipv-justify-between ipv-items-center ipv-mb-2">
                        <span class="ipv-text-muted">Stato</span>
                        <span class="ipv-badge <?php echo $rss_enabled ? 'ipv-badge-success' : 'ipv-badge-muted'; ?>"><?php echo $rss_enabled ? '✓ Attivo' : '○ Disattivo'; ?></span>
                    </div>
                    <div class="ipv-flex ipv-justify-between ipv-items-center ipv-mb-2">
                        <span class="ipv-text-muted">Ultimo Controllo</span>
                        <strong><?php echo $stats['last_check'] === 'Mai' ? 'Mai' : esc_html( mysql2date( 'd/m/Y H:i', $stats['last_check'] ) ); ?></strong>
                    </div>
                    <div class="ipv-flex ipv-justify-between ipv-items-center ipv-mb-2">
                        <span class="ipv-text-muted">Video Importati</span>
                        <span class="ipv-badge ipv-badge-info"><?php echo intval( $stats['total_imported'] ); ?></span>
                    </div>
                    <div class="ipv-flex ipv-justify-between ipv-items-center ipv-mb-2">
                        <span class="ipv-text-muted">Video Saltati</span>
                        <span class="ipv-badge ipv-badge-warning"><?php echo intval( $stats['total_skipped'] ); ?></span>
                    </div>
                    <?php if ( $next_run ) : ?>
                    <div class="ipv-flex ipv-justify-between ipv-items-center">
                        <span class="ipv-text-muted">Prossimo Controllo</span>
                        <strong><?php echo esc_html( human_time_diff( $next_run ) ); ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ipv-card ipv-card-info ipv-mt-3">
                <div class="ipv-card-header">
                    <h3>❓ Come Funziona</h3>
                </div>
                <div class="ipv-card-body">
                    <div class="ipv-steps">
                        <div class="ipv-step">
                            <div class="ipv-step-number">1</div>
                            <div class="ipv-step-content">
                                <h4>Monitoraggio Automatico</h4>
                                <p>Il sistema controlla il feed RSS alla frequenza impostata</p>
                            </div>
                        </div>
                        <div class="ipv-step">
                            <div class="ipv-step-number">2</div>
                            <div class="ipv-step-content">
                                <h4>Rilevamento Nuovi Video</h4>
                                <p>Identifica automaticamente i video non ancora importati</p>
                            </div>
                        </div>
                        <div class="ipv-step">
                            <div class="ipv-step-number">3</div>
                            <div class="ipv-step-content">
                                <h4>Importazione Automatica</h4>
                                <p>Aggiunge i nuovi video alla coda per il processamento</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ipv-card ipv-mt-3">
                <div class="ipv-card-header">
                    <h3>ℹ️ Come Trovare il Feed</h3>
                </div>
                <div class="ipv-card-body">
                    <p class="ipv-mb-2"><strong>1. Trova il tuo Channel ID:</strong></p>
                    <p class="ipv-text-muted ipv-mb-2" style="font-size: 13px;">Vai su YouTube → Il tuo canale → L'URL contiene il Channel ID (inizia con "UC", 24 caratteri).</p>
                    <p class="ipv-mb-2"><strong>2. Costruisci il feed URL:</strong></p>
                    <code style="display: block; font-size: 12px; word-break: break-all; padding: 10px; background: var(--ipv-bg); border-radius: 4px;">https://www.youtube.com/feeds/videos.xml?channel_id=<span class="ipv-text-primary">TUO_CHANNEL_ID</span></code>
                    <p class="ipv-mt-2 ipv-text-muted" style="font-size: 12px;">💡 Puoi anche trovare il Channel ID nelle impostazioni avanzate del tuo canale YouTube.</p>
                </div>
            </div>
        </div>
    </div>
</div>
