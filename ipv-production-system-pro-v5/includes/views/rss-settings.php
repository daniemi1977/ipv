<?php
/**
 * Template pagina impostazioni RSS
 *
 * @package IPV_Production_System_Pro
 * @version 5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap ipv-prod-wrap">
    <div class="ipv-prod-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-1">
                    <i class="bi bi-rss-fill text-white me-2"></i>
                    Auto-Import RSS
                </h1>
                <p class="text-muted mb-0">Configura l'importazione automatica da feed RSS YouTube</p>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-dashboard' ) ); ?>">
                <i class="bi bi-speedometer2 me-1"></i>Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-import' ) ); ?>">
                <i class="bi bi-upload me-1"></i>Importa Video
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-rss' ) ); ?>">
                <i class="bi bi-rss me-1"></i>Auto-Import RSS
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-queue' ) ); ?>">
                <i class="bi bi-list-task me-1"></i>Coda
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-wall' ) ); ?>">
                <i class="bi bi-grid-3x3-gap me-1"></i>Video Wall
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-settings' ) ); ?>">
                <i class="bi bi-gear me-1"></i>Impostazioni
            </a>
        </li>
    </ul>

    <?php
    // Mostra risultato test se presente
    if ( isset( $test_result ) ) {
        $alert_class = $test_result['success'] ? 'alert-success' : 'alert-danger';
        $icon_class = $test_result['success'] ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
        ?>
        <div class="alert <?php echo esc_attr( $alert_class ); ?> alert-dismissible fade show" role="alert">
            <i class="bi <?php echo esc_attr( $icon_class ); ?> me-2"></i>
            <strong><?php echo esc_html( $test_result['message'] ); ?></strong>
            <?php if ( isset( $test_result['video_count'] ) ) : ?>
                <br><small>Trovati <?php echo intval( $test_result['video_count'] ); ?> video nel feed</small>
                <?php if ( isset( $test_result['latest_video'] ) ) : ?>
                    <br><small>Ultimo video ID: <?php echo esc_html( $test_result['latest_video'] ); ?></small>
                <?php endif; ?>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php
    }

    // Mostra risultato importazione manuale se presente
    if ( isset( $import_result ) ) {
        ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong><?php echo esc_html( $import_result['message'] ); ?></strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php
    }
    ?>

    <div class="row g-4">
        <!-- Colonna Sinistra: Configurazione -->
        <div class="col-lg-8">
            <form method="post" action="">
                <?php wp_nonce_field( 'ipv_rss_settings_save' ); ?>
                <input type="hidden" name="ipv_save_rss_settings" value="1" />

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-gear-fill me-2"></i>Configurazione Feed RSS</h5>
                    </div>
                    <div class="card-body">
                        <!-- Feed URL -->
                        <div class="mb-4">
                            <label for="ipv_rss_feed_url" class="form-label fw-bold">
                                <i class="bi bi-link-45deg me-1"></i>URL Feed RSS YouTube
                            </label>
                            <input type="url"
                                   class="form-control form-control-lg"
                                   id="ipv_rss_feed_url"
                                   name="ipv_rss_feed_url"
                                   value="<?php echo esc_attr( $feed_url ); ?>"
                                   placeholder="https://www.youtube.com/feeds/videos.xml?channel_id=..." />
                            <div class="form-text">
                                Inserisci l'URL del feed RSS del canale YouTube.
                                <a href="https://www.youtube.com/feeds/videos.xml?channel_id=UCYourChannelID" target="_blank" rel="noopener">
                                    Formato: https://www.youtube.com/feeds/videos.xml?channel_id=UCYourChannelID
                                </a>
                            </div>
                        </div>

                        <!-- Abilita/Disabilita -->
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="ipv_rss_enabled"
                                       name="ipv_rss_enabled"
                                       value="1"
                                       <?php checked( $rss_enabled, true ); ?> />
                                <label class="form-check-label fw-bold" for="ipv_rss_enabled">
                                    <i class="bi bi-power me-1"></i>Abilita Auto-Import RSS
                                </label>
                            </div>
                            <div class="form-text">Attiva l'importazione automatica periodica dal feed RSS</div>
                        </div>

                        <!-- Frequenza -->
                        <div class="mb-4">
                            <label for="ipv_rss_schedule" class="form-label fw-bold">
                                <i class="bi bi-clock me-1"></i>Frequenza Importazione
                            </label>
                            <select class="form-select" id="ipv_rss_schedule" name="ipv_rss_schedule">
                                <option value="every_30_minutes" <?php selected( $rss_schedule, 'every_30_minutes' ); ?>>
                                    Ogni 30 minuti
                                </option>
                                <option value="hourly" <?php selected( $rss_schedule, 'hourly' ); ?>>
                                    Ogni ora
                                </option>
                                <option value="every_6_hours" <?php selected( $rss_schedule, 'every_6_hours' ); ?>>
                                    Ogni 6 ore
                                </option>
                                <option value="twicedaily" <?php selected( $rss_schedule, 'twicedaily' ); ?>>
                                    Due volte al giorno
                                </option>
                                <option value="daily" <?php selected( $rss_schedule, 'daily' ); ?>>
                                    Una volta al giorno
                                </option>
                            </select>
                            <div class="form-text">Con quale frequenza controllare il feed per nuovi video</div>
                        </div>

                        <!-- Limite -->
                        <div class="mb-4">
                            <label for="ipv_rss_import_limit" class="form-label fw-bold">
                                <i class="bi bi-hash me-1"></i>Limite Video per Importazione
                            </label>
                            <input type="number"
                                   class="form-control"
                                   id="ipv_rss_import_limit"
                                   name="ipv_rss_import_limit"
                                   value="<?php echo esc_attr( $import_limit ); ?>"
                                   min="1"
                                   max="50" />
                            <div class="form-text">Numero massimo di video da importare ad ogni controllo (1-50)</div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg me-2">
                    <i class="bi bi-save me-2"></i>Salva Impostazioni
                </button>
            </form>

            <!-- Test Feed -->
            <form method="post" action="" class="d-inline-block">
                <?php wp_nonce_field( 'ipv_rss_test' ); ?>
                <input type="hidden" name="ipv_test_rss_feed" value="1" />
                <input type="hidden" name="ipv_rss_feed_url" value="<?php echo esc_attr( $feed_url ); ?>" />
                <button type="submit" class="btn btn-info btn-lg me-2">
                    <i class="bi bi-speedometer2 me-2"></i>Testa Feed
                </button>
            </form>

            <!-- Importazione Manuale -->
            <form method="post" action="" class="d-inline-block">
                <?php wp_nonce_field( 'ipv_rss_manual' ); ?>
                <input type="hidden" name="ipv_manual_rss_import" value="1" />
                <button type="submit" class="btn btn-success btn-lg" <?php disabled( empty( $feed_url ) ); ?>>
                    <i class="bi bi-download me-2"></i>Importa Ora
                </button>
            </form>
        </div>

        <!-- Colonna Destra: Info e Statistiche -->
        <div class="col-lg-4">
            <!-- Statistiche -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-bar-chart-fill me-2"></i>Statistiche</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Ultimo Controllo:</strong><br>
                        <span class="text-muted">
                            <?php
                            echo $stats['last_check'] !== 'Mai'
                                ? esc_html( date_i18n( 'd/m/Y H:i', strtotime( $stats['last_check'] ) ) )
                                : 'Mai';
                            ?>
                        </span>
                    </div>

                    <div class="mb-3">
                        <strong>Totale Importati:</strong><br>
                        <span class="badge bg-success fs-6"><?php echo intval( $stats['total_imported'] ); ?></span>
                    </div>

                    <div class="mb-3">
                        <strong>Totale Skipped:</strong><br>
                        <span class="badge bg-secondary fs-6"><?php echo intval( $stats['total_skipped'] ); ?></span>
                    </div>

                    <div class="mb-3">
                        <strong>Ultima Importazione:</strong><br>
                        <span class="badge bg-info fs-6"><?php echo intval( $stats['last_imported'] ); ?> video</span>
                    </div>

                    <?php if ( $next_run ) : ?>
                        <div class="mb-3">
                            <strong>Prossimo Controllo:</strong><br>
                            <span class="text-muted">
                                <?php echo esc_html( date_i18n( 'd/m/Y H:i', $next_run ) ); ?>
                            </span>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-warning mb-0">
                            <small><i class="bi bi-exclamation-triangle me-1"></i>Cron non attivo</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Guida -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-question-circle-fill me-2"></i>Come Funziona</h5>
                </div>
                <div class="card-body">
                    <ol class="small mb-0">
                        <li class="mb-2">Inserisci l'URL del feed RSS del tuo canale YouTube</li>
                        <li class="mb-2">Abilita l'auto-import e scegli la frequenza</li>
                        <li class="mb-2">Testa il feed per verificare che funzioni</li>
                        <li class="mb-2">Salva le impostazioni</li>
                        <li class="mb-0">Il sistema controllerà automaticamente il feed e importerà i nuovi video</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
