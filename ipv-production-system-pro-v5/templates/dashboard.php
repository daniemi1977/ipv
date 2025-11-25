<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap ipv-prod-wrap">
    <div class="ipv-prod-header">
        <h1><i class="bi bi-play-circle-fill"></i> IPV Production System Pro v5</h1>
        <p class="text-muted">Dashboard Plugin - Gestione Video Automatizzata</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="bi bi-hourglass-split text-warning fs-2"></i>
                    <h3><?php echo intval( $stats['pending'] ); ?></h3>
                    <small>In Attesa</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="bi bi-arrow-repeat text-info fs-2"></i>
                    <h3><?php echo intval( $stats['processing'] ); ?></h3>
                    <small>In Lavorazione</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle-fill text-success fs-2"></i>
                    <h3><?php echo intval( $stats['done'] ); ?></h3>
                    <small>Completati</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-2"></i>
                    <h3><?php echo intval( $stats['error'] ); ?></h3>
                    <small>In Errore</small>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        <strong>✅ Plugin attivo e funzionante!</strong>
        <p>Cron automatici configurati per download, trascrizione e generazione SEO.</p>
    </div>
</div>
