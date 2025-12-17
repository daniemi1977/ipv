/**
 * WCFM Affiliate Pro - Network Tree Visualization
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

(function($) {
    'use strict';

    const NetworkTree = {
        svg: null,
        g: null,
        zoom: null,
        tree: null,
        root: null,
        width: 0,
        height: 0,
        selectedNode: null,

        // Colors for status
        statusColors: {
            root: '#6366f1',
            active: '#00897b',
            pending: '#f59e0b',
            suspended: '#ef4444',
            rejected: '#ef4444',
            inactive: '#9ca3af'
        },

        init: function() {
            this.bindEvents();
            this.loadTree();
        },

        bindEvents: function() {
            // Search functionality
            this.initSearch('#move-source-search', '#move-source-results', 'source');
            this.initSearch('#move-target-search', '#move-target-results', 'target');

            // Clear selections
            $('#move-source-clear').on('click', () => this.clearSelection('source'));
            $('#move-target-clear').on('click', () => this.clearSelection('target'));

            // Move to root checkbox
            $('#move-to-root').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#move-target-search').prop('disabled', true).val('');
                    $('#move-target-selected').addClass('hidden');
                    $('#move-target-id').val('');
                } else {
                    $('#move-target-search').prop('disabled', false);
                }
                NetworkTree.updateMoveButton();
            });

            // Move affiliate button
            $('#move-affiliate-btn').on('click', () => this.moveAffiliate());

            // Zoom controls
            $('#tree-zoom-in').on('click', () => this.zoomIn());
            $('#tree-zoom-out').on('click', () => this.zoomOut());
            $('#tree-reset').on('click', () => this.resetZoom());
            $('#tree-fullscreen').on('click', () => this.toggleFullscreen());

            // Window resize
            $(window).on('resize', () => this.handleResize());
        },

        initSearch: function(inputSelector, resultsSelector, type) {
            const $input = $(inputSelector);
            const $results = $(resultsSelector);
            let timeout;

            $input.on('input', function() {
                const query = $(this).val();

                clearTimeout(timeout);

                if (query.length < 2) {
                    $results.addClass('hidden').empty();
                    return;
                }

                timeout = setTimeout(() => {
                    NetworkTree.searchAffiliates(query, type, $results);
                }, 300);
            });

            $input.on('focus', function() {
                if ($results.children().length > 0) {
                    $results.removeClass('hidden');
                }
            });

            // Hide results on click outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest(inputSelector).length && !$(e.target).closest(resultsSelector).length) {
                    $results.addClass('hidden');
                }
            });
        },

        searchAffiliates: function(query, type, $results) {
            const excludeId = type === 'target' ? $('#move-source-id').val() : null;

            $.ajax({
                url: wcfmAffProNetwork.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcfm_aff_pro_search_affiliates',
                    nonce: wcfmAffProNetwork.nonce,
                    search: query,
                    exclude: excludeId
                },
                success: function(response) {
                    if (response.success && response.data.results.length > 0) {
                        let html = '';
                        response.data.results.forEach(function(aff) {
                            const statusClass = aff.status === 'active' ? 'bg-emerald-500' :
                                               aff.status === 'pending' ? 'bg-amber-500' : 'bg-gray-400';
                            html += `
                                <div class="affiliate-search-result flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer transition"
                                     data-id="${aff.id}" data-name="${aff.name}" data-email="${aff.email}" data-avatar="${aff.avatar}" data-type="${type}">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                        <span class="font-semibold text-gray-600">${aff.avatar}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 truncate">${aff.name}</p>
                                        <p class="text-sm text-gray-500 truncate">${aff.email}</p>
                                    </div>
                                    <span class="w-2 h-2 rounded-full ${statusClass}"></span>
                                </div>
                            `;
                        });
                        $results.html(html).removeClass('hidden');

                        // Bind click events
                        $results.find('.affiliate-search-result').on('click', function() {
                            NetworkTree.selectSearchResult($(this));
                        });
                    } else {
                        $results.html(`<div class="p-4 text-center text-gray-500">${wcfmAffProNetwork.i18n.no_results}</div>`).removeClass('hidden');
                    }
                }
            });
        },

        selectSearchResult: function($item) {
            const type = $item.data('type');
            const id = $item.data('id');
            const name = $item.data('name');
            const email = $item.data('email');
            const avatar = $item.data('avatar');

            $(`#move-${type}-id`).val(id);
            $(`#move-${type}-search`).val('');
            $(`#move-${type}-results`).addClass('hidden');
            $(`#move-${type}-avatar`).text(avatar);
            $(`#move-${type}-name`).text(name);
            $(`#move-${type}-email`).text(email);
            $(`#move-${type}-selected`).removeClass('hidden');

            this.updateMoveButton();
        },

        clearSelection: function(type) {
            $(`#move-${type}-id`).val('');
            $(`#move-${type}-selected`).addClass('hidden');
            this.updateMoveButton();
        },

        updateMoveButton: function() {
            const sourceId = $('#move-source-id').val();
            const targetId = $('#move-target-id').val();
            const moveToRoot = $('#move-to-root').is(':checked');

            const canMove = sourceId && (targetId || moveToRoot);
            $('#move-affiliate-btn').prop('disabled', !canMove);
        },

        moveAffiliate: function() {
            const sourceId = $('#move-source-id').val();
            const targetId = $('#move-target-id').val();
            const moveToRoot = $('#move-to-root').is(':checked');

            if (!confirm(wcfmAffProNetwork.i18n.confirm_move)) {
                return;
            }

            const $btn = $('#move-affiliate-btn');
            $btn.prop('disabled', true).find('span').html(`
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                ${wcfmAffProNetwork.i18n.loading}
            `);

            $.ajax({
                url: wcfmAffProNetwork.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcfm_aff_pro_move_affiliate',
                    nonce: wcfmAffProNetwork.nonce,
                    affiliate_id: sourceId,
                    new_parent_id: targetId,
                    move_to_root: moveToRoot ? 'true' : 'false'
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast('success', wcfmAffProNetwork.i18n.move_success);
                        this.clearSelection('source');
                        this.clearSelection('target');
                        $('#move-to-root').prop('checked', false);
                        this.loadTree();
                    } else {
                        this.showToast('error', response.data?.message || wcfmAffProNetwork.i18n.move_error);
                    }
                },
                error: () => {
                    this.showToast('error', wcfmAffProNetwork.i18n.move_error);
                },
                complete: () => {
                    $btn.prop('disabled', false).find('span').html(`
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        Sposta Affiliato
                    `);
                    this.updateMoveButton();
                }
            });
        },

        loadTree: function() {
            $('#tree-loading').removeClass('hidden');

            $.ajax({
                url: wcfmAffProNetwork.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcfm_aff_pro_get_network_tree',
                    nonce: wcfmAffProNetwork.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.renderTree(response.data.tree);
                    }
                },
                complete: () => {
                    $('#tree-loading').addClass('hidden');
                }
            });
        },

        renderTree: function(data) {
            const container = document.getElementById('network-tree-container');
            this.width = container.clientWidth;
            this.height = container.clientHeight;

            // Clear existing
            d3.select('#network-tree-svg').selectAll('*').remove();

            this.svg = d3.select('#network-tree-svg')
                .attr('width', this.width)
                .attr('height', this.height);

            // Create zoom behavior
            this.zoom = d3.zoom()
                .scaleExtent([0.1, 3])
                .on('zoom', (event) => {
                    this.g.attr('transform', event.transform);
                });

            this.svg.call(this.zoom);

            // Create container group
            this.g = this.svg.append('g')
                .attr('transform', `translate(${this.width / 2}, 80)`);

            // Create tree layout
            this.tree = d3.tree()
                .size([this.width - 200, this.height - 160])
                .separation((a, b) => (a.parent === b.parent ? 1.5 : 2));

            // Create hierarchy
            this.root = d3.hierarchy(data);

            // Calculate positions
            this.tree(this.root);

            // Draw links
            this.g.selectAll('.link')
                .data(this.root.links())
                .join('path')
                .attr('class', 'link')
                .attr('fill', 'none')
                .attr('stroke', '#e5e7eb')
                .attr('stroke-width', 2)
                .attr('d', d3.linkVertical()
                    .x(d => d.x - this.width / 2 + 100)
                    .y(d => d.y)
                );

            // Draw nodes
            const nodes = this.g.selectAll('.node')
                .data(this.root.descendants())
                .join('g')
                .attr('class', 'node')
                .attr('transform', d => `translate(${d.x - this.width / 2 + 100}, ${d.y})`)
                .style('cursor', 'pointer')
                .on('click', (event, d) => this.handleNodeClick(event, d))
                .on('mouseover', (event, d) => this.handleNodeHover(event, d, true))
                .on('mouseout', (event, d) => this.handleNodeHover(event, d, false));

            // Node circles
            nodes.append('circle')
                .attr('r', d => d.data.id === 'root' ? 30 : 24)
                .attr('fill', d => this.statusColors[d.data.status] || this.statusColors.inactive)
                .attr('stroke', '#fff')
                .attr('stroke-width', 3)
                .style('filter', 'drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1))');

            // Node icons/text
            nodes.append('text')
                .attr('dy', '0.35em')
                .attr('text-anchor', 'middle')
                .attr('fill', '#fff')
                .attr('font-weight', '600')
                .attr('font-size', d => d.data.id === 'root' ? '12px' : '11px')
                .text(d => {
                    if (d.data.id === 'root') return '???';
                    const name = d.data.name || '';
                    return name.substring(0, 2).toUpperCase();
                });

            // Node labels
            nodes.append('text')
                .attr('dy', '45px')
                .attr('text-anchor', 'middle')
                .attr('fill', '#374151')
                .attr('font-size', '11px')
                .attr('font-weight', '500')
                .text(d => {
                    const name = d.data.name || '';
                    return name.length > 12 ? name.substring(0, 12) + '...' : name;
                });

            // Child count badge
            nodes.filter(d => d.children && d.children.length > 0)
                .append('circle')
                .attr('cx', 18)
                .attr('cy', -18)
                .attr('r', 10)
                .attr('fill', '#3b82f6')
                .attr('stroke', '#fff')
                .attr('stroke-width', 2);

            nodes.filter(d => d.children && d.children.length > 0)
                .append('text')
                .attr('x', 18)
                .attr('y', -14)
                .attr('text-anchor', 'middle')
                .attr('fill', '#fff')
                .attr('font-size', '10px')
                .attr('font-weight', '600')
                .text(d => d.children.length);

            // Initial zoom to fit
            this.fitToScreen();
        },

        handleNodeClick: function(event, d) {
            event.stopPropagation();

            if (d.data.id === 'root') {
                $('#node-details-panel').addClass('hidden');
                return;
            }

            this.selectedNode = d;

            // Highlight selected node
            this.g.selectAll('.node circle').attr('stroke', '#fff');
            d3.select(event.currentTarget).select('circle')
                .attr('stroke', '#fbbf24')
                .attr('stroke-width', 4);

            // Load details
            this.loadNodeDetails(d.data.id);
        },

        handleNodeHover: function(event, d, isHover) {
            if (d.data.id === 'root') return;

            const scale = isHover ? 1.1 : 1;
            d3.select(event.currentTarget)
                .transition()
                .duration(200)
                .attr('transform', `translate(${d.x - this.width / 2 + 100}, ${d.y}) scale(${scale})`);
        },

        loadNodeDetails: function(affiliateId) {
            const $panel = $('#node-details-panel');
            const $content = $('#node-details-content');

            $content.html(`
                <div class="flex justify-center py-8">
                    <div class="w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
                </div>
            `);
            $panel.removeClass('hidden');

            $.ajax({
                url: wcfmAffProNetwork.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcfm_aff_pro_get_affiliate_details',
                    nonce: wcfmAffProNetwork.nonce,
                    affiliate_id: affiliateId
                },
                success: (response) => {
                    if (response.success) {
                        this.renderNodeDetails(response.data.affiliate);
                    }
                }
            });
        },

        renderNodeDetails: function(affiliate) {
            const statusClasses = {
                active: 'bg-emerald-100 text-emerald-700',
                pending: 'bg-amber-100 text-amber-700',
                suspended: 'bg-red-100 text-red-700',
                rejected: 'bg-red-100 text-red-700'
            };

            const statusClass = statusClasses[affiliate.status] || 'bg-gray-100 text-gray-700';
            const statusLabel = affiliate.status.charAt(0).toUpperCase() + affiliate.status.slice(1);

            let parentHtml = '';
            if (affiliate.parent) {
                parentHtml = `
                    <div class="mt-4 p-3 bg-blue-50 rounded-xl">
                        <p class="text-xs text-blue-600 font-medium mb-1">Upline (Genitore)</p>
                        <p class="font-medium text-blue-900">${affiliate.parent.display_name}</p>
                        <p class="text-sm text-blue-700">${affiliate.parent.user_email}</p>
                    </div>
                `;
            }

            $('#node-details-content').html(`
                <div class="text-center mb-6">
                    <div class="w-20 h-20 mx-auto rounded-full bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center shadow-lg">
                        <span class="text-2xl font-bold text-white">${(affiliate.display_name || 'A').substring(0, 2).toUpperCase()}</span>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">${affiliate.display_name || 'N/A'}</h3>
                    <p class="text-gray-500">${affiliate.user_email}</p>
                    <span class="inline-block mt-2 px-3 py-1 text-xs font-semibold rounded-full ${statusClass}">${statusLabel}</span>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-500">Codice</span>
                        <span class="font-mono font-medium text-gray-900">${affiliate.affiliate_code}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-500">Livello MLM</span>
                        <span class="font-medium text-gray-900">${affiliate.level || 0}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-500">Downline Diretti</span>
                        <span class="font-medium text-gray-900">${affiliate.direct_downline || 0}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-500">Referral Totali</span>
                        <span class="font-medium text-gray-900">${affiliate.referrals_count || 0}</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-gray-500">Guadagni Totali</span>
                        <span class="font-semibold text-primary">${affiliate.earnings_formatted}</span>
                    </div>
                </div>

                ${parentHtml}

                <div class="mt-6 flex gap-2">
                    <button type="button" class="flex-1 py-2 px-3 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition"
                            onclick="jQuery('#move-source-search').val('${affiliate.display_name}').trigger('input')">
                        Seleziona per Spostare
                    </button>
                    <a href="admin.php?page=wcfm-affiliate-affiliates&action=edit&id=${affiliate.id}"
                       class="py-2 px-3 text-sm bg-primary text-white rounded-lg hover:bg-primary-dark transition">
                        Modifica
                    </a>
                </div>
            `);
        },

        zoomIn: function() {
            this.svg.transition().duration(300).call(this.zoom.scaleBy, 1.3);
        },

        zoomOut: function() {
            this.svg.transition().duration(300).call(this.zoom.scaleBy, 0.7);
        },

        resetZoom: function() {
            this.fitToScreen();
        },

        fitToScreen: function() {
            if (!this.root) return;

            const bounds = this.g.node().getBBox();
            const fullWidth = this.width;
            const fullHeight = this.height;
            const width = bounds.width;
            const height = bounds.height;
            const midX = bounds.x + width / 2;
            const midY = bounds.y + height / 2;

            if (width === 0 || height === 0) return;

            const scale = 0.85 / Math.max(width / fullWidth, height / fullHeight);
            const translate = [fullWidth / 2 - scale * midX, fullHeight / 2 - scale * midY];

            this.svg.transition()
                .duration(500)
                .call(this.zoom.transform, d3.zoomIdentity
                    .translate(translate[0], translate[1])
                    .scale(scale));
        },

        toggleFullscreen: function() {
            const container = document.getElementById('network-tree-container');

            if (!document.fullscreenElement) {
                container.requestFullscreen().then(() => {
                    setTimeout(() => this.handleResize(), 100);
                });
            } else {
                document.exitFullscreen().then(() => {
                    setTimeout(() => this.handleResize(), 100);
                });
            }
        },

        handleResize: function() {
            const container = document.getElementById('network-tree-container');
            this.width = container.clientWidth;
            this.height = container.clientHeight;

            if (this.svg) {
                this.svg.attr('width', this.width).attr('height', this.height);
                this.fitToScreen();
            }
        },

        showToast: function(type, message) {
            const $toast = $('#toast-notification');
            const iconHtml = type === 'success'
                ? '<svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                : '<svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';

            $('#toast-icon').html(iconHtml);
            $('#toast-message').text(message);

            $toast.removeClass('translate-y-24 opacity-0');

            setTimeout(() => {
                $toast.addClass('translate-y-24 opacity-0');
            }, 4000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('#wcfm-aff-pro-network-app').length) {
            NetworkTree.init();
        }
    });

})(jQuery);
