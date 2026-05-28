/**
 * Mydyma TCS — Logs & Stats Dashboard
 */
(function($) {
    'use strict';

    const D = typeof MydymaTcsLogStats !== 'undefined' ? MydymaTcsLogStats : null;
    if (!D) return;

    function renderStats(data) {
        const stats = data.stats;
        const total = data.total;
        
        if (total === 0) {
            $('#mydyma-taiwan-commerce-suite-logs-root').html(`
                <div class="mydyma-taiwan-commerce-suite-empty-state">
                    <span class="dashicons dashicons-chart-pie"></span>
                    <p>今日尚無訂單數據</p>
                    <p style="font-size:12px;color:#aaa">數據將在今日產生首筆訂單後顯示</p>
                </div>
            `);
            return;
        }

        const items = [
            { label: '個人雲端', val: stats.personal, color: '#10b981' },
            { label: '手機載具', val: stats.carrier_phone, color: '#3b82f6' },
            { label: '自然人憑證', val: stats.carrier_cert, color: '#8b5cf6' },
            { label: '捐贈發票', val: stats.donate, color: '#f59e0b' },
            { label: '公司統編', val: stats.company, color: '#ef4444' },
            { label: '未設定', val: stats.none, color: '#94a3b8' }
        ].filter(i => i.val > 0);

        // Calculate SVG Pie Chart paths
        let cumulativePercent = 0;
        const paths = items.map(item => {
            const startX = Math.cos(2 * Math.PI * cumulativePercent);
            const startY = Math.sin(2 * Math.PI * cumulativePercent);
            cumulativePercent += item.val / total;
            const endX = Math.cos(2 * Math.PI * cumulativePercent);
            const endY = Math.sin(2 * Math.PI * cumulativePercent);
            const largeArcFlag = (item.val / total) > 0.5 ? 1 : 0;
            
            return `<path d="M 0 0 L ${startX} ${startY} A 1 1 0 ${largeArcFlag} 1 ${endX} ${endY} Z" fill="${item.color}" />`;
        }).join('');

        const chartHtml = `
            <div class="tw-stats-container">
                <div class="tw-stats-card">
                    <div class="tw-chart-wrap">
                        <svg viewBox="-1 -1 2 2" style="transform: rotate(-90deg)">
                            ${paths}
                        </svg>
                    </div>
                    <div class="tw-stats-info">
                        <h3>今日發票概況 <span class="tw-date-badge">${data.date}</span></h3>
                        <div class="tw-total-count">總計 ${total} 筆訂單</div>
                        <div class="tw-legend-list">
                            ${items.map(item => `
                                <div class="tw-legend-item">
                                    <span class="tw-dot" style="background:${item.color}"></span>
                                    <span class="tw-label">${item.label}</span>
                                    <span class="tw-val">${item.val} (${Math.round(item.val/total*100)}%)</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#mydyma-taiwan-commerce-suite-logs-root').html(chartHtml);
    }

    function init() {
        $('#mydyma-taiwan-commerce-suite-logs-root').html('<div class="mydyma-taiwan-commerce-suite-spinner active"></div> 正在讀取數據...');
        
        $.post(D.ajaxUrl, {
            action: 'mydyma_tcs_get_stats',
            nonce: D.nonce
        }).done(function(res) {
            if (res.success) {
                renderStats(res.data);
            } else {
                $('#mydyma-taiwan-commerce-suite-logs-root').text('無法載入數據');
            }
        });
    }

    $(function() {
        if ($('#mydyma-taiwan-commerce-suite-logs-root').length) {
            init();
        }
    });

})(jQuery);
