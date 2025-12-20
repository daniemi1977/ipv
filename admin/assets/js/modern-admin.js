/**
 * IPV Pro - Modern Admin JavaScript
 * Toast Notifications, Charts, Interactive Components
 *
 * @version 1.5.0
 */

(function($) {
    'use strict';

    // ========================================
    // TOAST NOTIFICATIONS
    // ========================================

    window.ipvShowToast = function(message, type = 'success', duration = 4000) {
        const container = document.getElementById('ipv-toast-container');
        if (!container) return;

        const icons = {
            success: '<svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
            error: '<svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>',
            warning: '<svg class="w-5 h-5 text-yellow-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
            info: '<svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
        };

        const toast = document.createElement('div');
        toast.className = `ipv-toast ipv-toast-${type}`;
        toast.innerHTML = `
            ${icons[type] || icons.info}
            <div class="ipv-toast-content">${message}</div>
            <button class="ipv-toast-close" onclick="this.closest('.ipv-toast').remove()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;

        container.appendChild(toast);

        // Auto remove after duration
        setTimeout(() => {
            toast.classList.add('removing');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    };

    // ========================================
    // CHARTS - Dashboard Analytics
    // ========================================

    window.ipvInitCharts = function() {
        // MRR Chart (Monthly Recurring Revenue)
        const mrrCanvas = document.getElementById('ipv-mrr-chart');
        if (mrrCanvas && typeof Chart !== 'undefined') {
            const ctx = mrrCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
                    datasets: [{
                        label: 'MRR (€)',
                        data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], // Will be populated via AJAX
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#1f2937',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return '€' + context.parsed.y.toLocaleString('it-IT', {minimumFractionDigits: 2});
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '€' + value;
                                }
                            },
                            grid: {
                                color: '#f3f4f6'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Plans Distribution Chart
        const plansCanvas = document.getElementById('ipv-plans-chart');
        if (plansCanvas && typeof Chart !== 'undefined') {
            const ctx = plansCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Trial', 'Starter', 'Professional', 'Business', 'Golden Prompt'],
                    datasets: [{
                        data: [0, 0, 0, 0, 0], // Will be populated via AJAX
                        backgroundColor: [
                            '#94a3b8', // Gray
                            '#3b82f6', // Blue
                            '#8b5cf6', // Purple
                            '#f59e0b', // Amber
                            '#10b981'  // Green
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1f2937',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 12,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Credits Usage Chart
        const creditsCanvas = document.getElementById('ipv-credits-chart');
        if (creditsCanvas && typeof Chart !== 'undefined') {
            const ctx = creditsCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'],
                    datasets: [{
                        label: 'Crediti Usati',
                        data: [0, 0, 0, 0, 0, 0, 0], // Will be populated via AJAX
                        backgroundColor: '#3b82f6',
                        borderRadius: 6
                    }, {
                        label: 'Crediti Acquistati',
                        data: [0, 0, 0, 0, 0, 0, 0], // Will be populated via AJAX
                        backgroundColor: '#10b981',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 15
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1f2937',
                            padding: 12
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f3f4f6'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    };

    // ========================================
    // AJAX - Load Dashboard Stats
    // ========================================

    window.ipvLoadDashboardStats = function() {
        if (typeof ipvModern === 'undefined') return;

        $.ajax({
            url: ipvModern.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ipv_get_dashboard_stats',
                nonce: ipvModern.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Update stats cards
                    if (response.data.stats) {
                        const stats = response.data.stats;
                        $('#ipv-stat-mrr').text('€' + (stats.mrr || 0).toLocaleString('it-IT', {minimumFractionDigits: 2}));
                        $('#ipv-stat-arr').text('€' + (stats.arr || 0).toLocaleString('it-IT', {minimumFractionDigits: 2}));
                        $('#ipv-stat-licenses').text(stats.active_licenses || 0);
                        $('#ipv-stat-credits').text((stats.total_credits || 0).toLocaleString('it-IT'));
                    }

                    // Update charts will be implemented via Chart.js data update
                }
            },
            error: function() {
                ipvShowToast(ipvModern.i18n.error, 'error');
            }
        });
    };

    // ========================================
    // CONFIRM DIALOG
    // ========================================

    window.ipvConfirm = function(message, callback) {
        if (confirm(message || ipvModern.i18n.confirmDelete)) {
            callback();
        }
    };

    // ========================================
    // COPY TO CLIPBOARD
    // ========================================

    window.ipvCopyToClipboard = function(text, successMessage) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                ipvShowToast(successMessage || 'Copiato negli appunti!', 'success', 2000);
            }).catch(function() {
                ipvShowToast('Impossibile copiare', 'error', 2000);
            });
        } else {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                ipvShowToast(successMessage || 'Copiato negli appunti!', 'success', 2000);
            } catch (err) {
                ipvShowToast('Impossibile copiare', 'error', 2000);
            }
            document.body.removeChild(textarea);
        }
    };

    // ========================================
    // INITIALIZE ON DOCUMENT READY
    // ========================================

    $(document).ready(function() {
        // Initialize charts if present
        if ($('.ipv-chart-container canvas').length > 0) {
            ipvInitCharts();
        }

        // Load dashboard stats
        if ($('#ipv-dashboard-analytics').length > 0) {
            ipvLoadDashboardStats();
        }

        // Add copy button functionality
        $('.ipv-copy-btn').on('click', function(e) {
            e.preventDefault();
            const text = $(this).data('copy');
            const message = $(this).data('message') || 'Copiato!';
            ipvCopyToClipboard(text, message);
        });

        // Smooth scroll for anchor links
        $('a[href^="#"]').on('click', function(e) {
            const target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 32
                }, 500);
            }
        });

        // Auto-hide WordPress notices on IPV pages
        if ($('.ipv-modern-page').length > 0) {
            setTimeout(function() {
                $('.notice').not('.ipv-toast').fadeOut();
            }, 3000);
        }
    });

})(jQuery);
