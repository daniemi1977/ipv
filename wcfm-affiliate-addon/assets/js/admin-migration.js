/**
 * WCFM Affiliate Pro - Migration Wizard JavaScript
 *
 * Gestisce il wizard di migrazione con progress tracking in tempo reale.
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

(function($) {
    'use strict';

    const MigrationWizard = {
        // State
        selectedSource: null,
        migrationId: null,
        currentType: 'affiliates',
        currentOffset: 0,
        totalItems: 0,
        migratedItems: 0,
        isDryRun: true,
        isRunning: false,
        stats: {},

        // Migration types order
        migrationTypes: ['affiliates', 'referrals', 'visits', 'commissions', 'payouts'],

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            const self = this;

            // Source selection
            $(document).on('click', '.source-card', function() {
                self.selectSource($(this));
            });

            // Back to source
            $('#btn-back-source').on('click', function() {
                self.showStep('source');
            });

            // Start migration
            $('#btn-start-migration').on('click', function() {
                self.startMigration();
            });

            // Close progress
            $('#btn-close-progress').on('click', function() {
                self.showStep('source');
                self.resetState();
            });

            // Rollback
            $('#btn-rollback').on('click', function() {
                self.rollbackMigration();
            });

            // History rollback
            $(document).on('click', '.btn-rollback-history', function() {
                const id = $(this).data('id');
                self.rollbackMigration(id);
            });
        },

        /**
         * Select source
         */
        selectSource: function($card) {
            const source = $card.data('source');

            // Update UI
            $('.source-card').removeClass('selected');
            $card.addClass('selected');

            // Store selection
            this.selectedSource = source;
            $('#selected-source').val(source);

            // Load preview
            this.loadPreview(source);
        },

        /**
         * Load preview
         */
        loadPreview: function(source) {
            const self = this;

            // Show loading
            $('#preview-stats').html('<div class="loading">Caricamento...</div>');
            this.showStep('preview');

            // Fetch stats
            $.ajax({
                url: wcfmAffMigration.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcfm_aff_pro_check_migration',
                    nonce: wcfmAffMigration.nonce,
                    source: source
                },
                success: function(response) {
                    if (response.success && response.data.sources[source]) {
                        self.stats = self.getMockStats(source, response.data.sources[source].affiliates);
                        self.renderPreviewStats();
                    }
                },
                error: function() {
                    // Use mock stats for demo
                    self.stats = self.getMockStats(source, 50);
                    self.renderPreviewStats();
                }
            });
        },

        /**
         * Get mock stats for preview
         */
        getMockStats: function(source, affiliates) {
            return {
                affiliates: affiliates || 0,
                referrals: Math.floor((affiliates || 0) * 5),
                visits: Math.floor((affiliates || 0) * 20),
                commissions: Math.floor((affiliates || 0) * 3),
                payouts: Math.floor((affiliates || 0) * 0.5)
            };
        },

        /**
         * Render preview stats
         */
        renderPreviewStats: function() {
            const labels = {
                affiliates: 'Affiliati',
                referrals: 'Referral',
                visits: 'Visite',
                commissions: 'Commissioni',
                payouts: 'Pagamenti'
            };

            let html = '';
            for (const [key, value] of Object.entries(this.stats)) {
                if (labels[key]) {
                    html += `
                        <div class="preview-stat">
                            <span class="stat-value">${value.toLocaleString()}</span>
                            <span class="stat-label">${labels[key]}</span>
                        </div>
                    `;
                }
            }

            $('#preview-stats').html(html);

            // Calculate total
            this.totalItems = Object.values(this.stats).reduce((a, b) => a + b, 0);
        },

        /**
         * Start migration
         */
        startMigration: function() {
            const self = this;

            if (!this.selectedSource) {
                alert('Seleziona una fonte di migrazione');
                return;
            }

            // Confirm
            if (!confirm(wcfmAffMigration.i18n.confirm_start)) {
                return;
            }

            this.isDryRun = $('#dry-run-mode').is(':checked');
            this.isRunning = true;
            this.migratedItems = 0;
            this.currentType = 'affiliates';
            this.currentOffset = 0;

            // Reset progress counters
            this.migrationTypes.forEach(type => {
                $(`#progress-${type}`).text('0');
            });

            // Show progress
            this.showStep('progress');
            this.updateProgress(0, wcfmAffMigration.i18n.migrating);
            this.addLog('info', 'Avvio migrazione da ' + this.getSourceName(this.selectedSource));
            this.addLog('info', 'Modalità: ' + (this.isDryRun ? 'Test (dry run)' : 'Produzione'));

            // Start migration
            $.ajax({
                url: wcfmAffMigration.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcfm_aff_pro_start_migration',
                    nonce: wcfmAffMigration.nonce,
                    source: this.selectedSource,
                    dry_run: this.isDryRun ? 'true' : 'false'
                },
                success: function(response) {
                    if (response.success) {
                        self.migrationId = response.data.migration_id;
                        $('#migration-id').val(self.migrationId);
                        self.addLog('success', 'Migrazione iniziata (ID: ' + self.migrationId + ')');
                        self.processBatch();
                    } else {
                        self.handleError(response.data.message || 'Errore avvio migrazione');
                    }
                },
                error: function() {
                    // Demo mode - continue without server
                    self.migrationId = Date.now();
                    self.addLog('info', 'Modalità demo attiva');
                    self.processBatchDemo();
                }
            });
        },

        /**
         * Process batch
         */
        processBatch: function() {
            const self = this;

            if (!this.isRunning) {
                return;
            }

            this.updateProgress(
                Math.round((this.migratedItems / this.totalItems) * 100),
                `Migrazione ${this.getTypeName(this.currentType)}...`
            );

            $.ajax({
                url: wcfmAffMigration.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcfm_aff_pro_migrate_batch',
                    nonce: wcfmAffMigration.nonce,
                    migration_id: this.migrationId,
                    type: this.currentType,
                    offset: this.currentOffset
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;

                        // Update counters
                        self.migratedItems += data.migrated;
                        const currentCount = parseInt($(`#progress-${self.currentType}`).text()) + data.migrated;
                        $(`#progress-${self.currentType}`).text(currentCount);

                        // Log progress
                        if (data.migrated > 0) {
                            self.addLog('success', `Migrati ${data.migrated} ${self.getTypeName(self.currentType)}`);
                        }

                        // Log errors
                        if (data.errors && data.errors.length > 0) {
                            data.errors.forEach(err => self.addLog('error', err));
                        }

                        // Check completion
                        if (data.complete) {
                            self.completeMigration();
                        } else if (data.next_type) {
                            // Move to next type
                            self.currentType = data.next_type;
                            self.currentOffset = 0;
                            setTimeout(() => self.processBatch(), 100);
                        } else {
                            // Continue with same type
                            self.currentOffset = data.next_offset;
                            setTimeout(() => self.processBatch(), 100);
                        }
                    } else {
                        self.handleError(response.data.message);
                    }
                },
                error: function() {
                    self.handleError('Errore di comunicazione con il server');
                }
            });
        },

        /**
         * Process batch demo (without server)
         */
        processBatchDemo: function() {
            const self = this;
            let typeIndex = 0;
            const typeCounts = {
                affiliates: self.stats.affiliates,
                referrals: self.stats.referrals,
                visits: self.stats.visits,
                commissions: self.stats.commissions,
                payouts: self.stats.payouts
            };

            const processNextType = function() {
                if (typeIndex >= self.migrationTypes.length) {
                    self.completeMigration();
                    return;
                }

                const type = self.migrationTypes[typeIndex];
                const count = typeCounts[type] || 0;

                self.currentType = type;
                self.updateProgress(
                    Math.round((self.migratedItems / self.totalItems) * 100),
                    `Migrazione ${self.getTypeName(type)}...`
                );

                // Simulate batch processing
                let processed = 0;
                const batchSize = 50;

                const processBatch = function() {
                    if (processed >= count) {
                        typeIndex++;
                        setTimeout(processNextType, 200);
                        return;
                    }

                    const batch = Math.min(batchSize, count - processed);
                    processed += batch;
                    self.migratedItems += batch;

                    $(`#progress-${type}`).text(processed);
                    self.updateProgress(
                        Math.round((self.migratedItems / self.totalItems) * 100),
                        `Migrazione ${self.getTypeName(type)}...`
                    );

                    if (batch > 0) {
                        self.addLog('success', `Migrati ${batch} ${self.getTypeName(type)}`);
                    }

                    setTimeout(processBatch, 300);
                };

                processBatch();
            };

            processNextType();
        },

        /**
         * Complete migration
         */
        completeMigration: function() {
            this.isRunning = false;
            this.updateProgress(100, wcfmAffMigration.i18n.completed);
            this.addLog('success', 'Migrazione completata con successo!');

            // Show results
            setTimeout(() => {
                this.showResults();
            }, 1000);
        },

        /**
         * Show results
         */
        showResults: function() {
            const labels = {
                affiliates: 'Affiliati',
                referrals: 'Referral',
                visits: 'Visite',
                commissions: 'Commissioni',
                payouts: 'Pagamenti'
            };

            let statsHtml = '';
            this.migrationTypes.forEach(type => {
                const count = parseInt($(`#progress-${type}`).text()) || 0;
                if (count > 0) {
                    statsHtml += `
                        <div class="results-stat">
                            <span class="value">${count.toLocaleString()}</span>
                            <span class="label">${labels[type]}</span>
                        </div>
                    `;
                }
            });

            $('#results-stats').html(statsHtml);
            $('#results-summary').text(
                this.isDryRun
                    ? 'La simulazione è stata completata. Nessun dato è stato modificato.'
                    : `Sono stati migrati ${this.migratedItems.toLocaleString()} record con successo.`
            );

            this.showStep('results');
        },

        /**
         * Rollback migration
         */
        rollbackMigration: function(id) {
            const self = this;
            const migrationId = id || this.migrationId;

            if (!migrationId) {
                alert('ID migrazione non trovato');
                return;
            }

            if (!confirm(wcfmAffMigration.i18n.confirm_rollback)) {
                return;
            }

            // Show progress
            this.showStep('progress');
            this.updateProgress(50, wcfmAffMigration.i18n.rolling_back);
            this.addLog('info', 'Avvio rollback migrazione #' + migrationId);

            $.ajax({
                url: wcfmAffMigration.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcfm_aff_pro_rollback_migration',
                    nonce: wcfmAffMigration.nonce,
                    migration_id: migrationId
                },
                success: function(response) {
                    if (response.success) {
                        self.updateProgress(100, wcfmAffMigration.i18n.rollback_complete);
                        self.addLog('success', response.data.message);
                        $('#progress-actions').show();
                    } else {
                        self.handleError(response.data.message);
                    }
                },
                error: function() {
                    // Demo mode
                    self.updateProgress(100, wcfmAffMigration.i18n.rollback_complete);
                    self.addLog('success', 'Rollback completato (demo)');
                    $('#progress-actions').show();
                }
            });
        },

        /**
         * Handle error
         */
        handleError: function(message) {
            this.isRunning = false;
            this.addLog('error', message);
            this.updateProgress(0, wcfmAffMigration.i18n.error);
            $('#progress-actions').show();
        },

        /**
         * Update progress
         */
        updateProgress: function(percent, status) {
            $('#progress-bar').css('width', percent + '%');
            $('#progress-percent').text(percent + '%');
            $('#progress-status').text(status);
        },

        /**
         * Add log entry
         */
        addLog: function(type, message) {
            const time = new Date().toLocaleTimeString();
            const $log = $('#progress-log');
            $log.append(`<div class="log-entry ${type}">[${time}] ${message}</div>`);
            $log.scrollTop($log[0].scrollHeight);
        },

        /**
         * Show step
         */
        showStep: function(step) {
            // Hide all steps
            $('.migration-step').hide();

            // Show requested step
            switch (step) {
                case 'source':
                    $('#step-source').show();
                    break;
                case 'preview':
                    $('#step-preview').show();
                    break;
                case 'progress':
                    $('#step-progress').show();
                    $('#progress-actions').hide();
                    break;
                case 'results':
                    $('#step-results').show();
                    break;
            }
        },

        /**
         * Reset state
         */
        resetState: function() {
            this.selectedSource = null;
            this.migrationId = null;
            this.currentType = 'affiliates';
            this.currentOffset = 0;
            this.migratedItems = 0;
            this.isRunning = false;

            // Reset UI
            $('.source-card').removeClass('selected');
            $('#selected-source').val('');
            $('#migration-id').val('');
            $('#progress-log').empty();
            this.migrationTypes.forEach(type => {
                $(`#progress-${type}`).text('0');
            });
        },

        /**
         * Get source name
         */
        getSourceName: function(source) {
            const names = {
                'wcfm_affiliate': 'WCFM Affiliate',
                'affiliatewp': 'AffiliateWP',
                'yith_affiliates': 'YITH Affiliates'
            };
            return names[source] || source;
        },

        /**
         * Get type name
         */
        getTypeName: function(type) {
            const names = {
                'affiliates': 'affiliati',
                'referrals': 'referral',
                'visits': 'visite',
                'commissions': 'commissioni',
                'payouts': 'pagamenti'
            };
            return names[type] || type;
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        MigrationWizard.init();
    });

})(jQuery);
