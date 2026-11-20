// ========== GERENCIADOR DE GRÁFICOS ==========
// Finanças Já v3.2.0

import { CONFIG }       from './configuracao.js';
import { StateManager } from './gerenciadorEstado.js';
import { Utils }        from './utilitarios.js';

export const ChartManager = {
  chart : null,
  _hidden: {},   // slug → true quando a fatia está oculta

  // ── Soma gastos agrupados por slug ────────────────────────────────
  getCategoryTotals() {
    const { gastos } = StateManager.get();
    const totals = {};
    gastos.forEach(g => {
      const slug = g.categoria || 'outros';
      totals[slug] = (totals[slug] || 0) + Number(g.valor || 0);
    });
    return totals;
  },

  _isLightTheme() {
    return document.documentElement.classList.contains('light-theme');
  },

  // ── Render principal ──────────────────────────────────────────────
  render() {
    const chartContainer  = Utils.$('#chartContainer');
    const categoryStatsEl = Utils.$('#categoryStats');
    if (!chartContainer) return;

    const totals     = this.getCategoryTotals();
    const categories = Object.keys(totals);

    // Estado vazio
    if (categories.length === 0) {
      chartContainer.innerHTML = `
        <div class="chart-empty">
          <i class="bi bi-graph-up" aria-hidden="true"></i>
          <p>Adicione gastos para visualizar o gráfico</p>
        </div>`;
      if (categoryStatsEl) categoryStatsEl.style.display = 'none';
      this._destroyChart();
      this._hidden = {};
      return;
    }

    // Remove slugs que já não existem do estado hidden
    Object.keys(this._hidden).forEach(s => {
      if (!categories.includes(s)) delete this._hidden[s];
    });

    // Garante canvas
    let canvas = Utils.$('#gastosChart');
    if (!canvas) {
      chartContainer.innerHTML = '<canvas id="gastosChart"></canvas>';
      canvas = Utils.$('#gastosChart');
    }

    const isLight     = this._isLightTheme();
    const ctx         = canvas.getContext('2d');
    const data        = categories.map(c => totals[c]);
    const colors      = categories.map(c => CONFIG.CATEGORY_COLORS[c] || '#6c757d');
    const labels      = categories.map(c => CONFIG.CATEGORIAS_DB[CONFIG.CATEGORIA_ID_MAP?.[c]] || c);
    const total       = data.reduce((a, b) => a + b, 0);
    const legendColor = isLight ? '#333' : '#ffffff';

    // Fatias ocultas ficam com valor 0 para sumir do donut
    const visibleData   = data.map((v, i) => this._hidden[categories[i]] ? 0 : v);
    const visibleColors = colors.map((c, i) => this._hidden[categories[i]] ? 'rgba(0,0,0,0)' : c);
    const visibleTotal  = visibleData.reduce((a, b) => a + b, 0);

    this._destroyChart();

    // ── Plugin: label central animado ─────────────────────────────────
    const self = this;

    const centerLabelPlugin = {
      id: 'centerLabel',
      afterDraw(chart) {
        const { ctx: c, chartArea: { top, bottom, left, right } } = chart;
        const cx = (left + right) / 2;
        const cy = (top + bottom) / 2;
        const lt = self._isLightTheme();

        c.save();

        // Valor total
        c.font         = 'bold 18px Inter, sans-serif';
        c.fillStyle    = lt ? '#1a1a1d' : '#f5f5f7';
        c.textAlign    = 'center';
        c.textBaseline = 'middle';
        c.fillText(Utils.formatCurrency(visibleTotal), cx, cy - 10);

        // Rótulo "TOTAL"
        c.font      = '600 10px Inter, sans-serif';
        c.fillStyle = lt ? '#9e9ea3' : '#6e6e73';
        c.fillText('TOTAL', cx, cy + 10);

        c.restore();
      }
    };

    // ── Plugin: desenha linha de strikethrough sobre legendas ocultas ─
    const strikethroughPlugin = {
      id: 'strikethroughLegend',
      afterDraw(chart) {
        const { ctx: c, legend } = chart;
        if (!legend?.legendItems?.length) return;

        legend.legendItems.forEach(item => {
          if (!item.hidden) return;

          // Chart.js popula x/y em cada legendItem após o layout
          const lx = item.left;
          const ly = item.top;
          if (lx == null || ly == null) return;

          const boxW  = legend.options.labels.boxWidth  ?? 14;
          const pad   = legend.options.labels.padding   ?? 18;
          const textX = lx + boxW + pad / 2;
          const textY = ly + (item.height ?? 20) / 2;

          c.save();
          c.font = `600 13px Inter, sans-serif`;
          const textW = c.measureText(item.text).width;

          c.strokeStyle = self._isLightTheme() ? '#666' : '#999';
          c.lineWidth   = 1.8;
          c.lineCap     = 'round';
          c.beginPath();
          c.moveTo(textX,          textY);
          c.lineTo(textX + textW,  textY);
          c.stroke();
          c.restore();
        });
      }
    };

    this.chart = new Chart(ctx, {
      type   : 'doughnut',
      plugins: [centerLabelPlugin, strikethroughPlugin],
      data   : {
        labels,
        datasets: [{
          data            : visibleData,
          backgroundColor : visibleColors,
          borderWidth     : 3,
          borderColor     : isLight ? '#fff' : '#141416',
          hoverOffset     : 14
        }]
      },
      options: {
        responsive          : true,
        maintainAspectRatio : false,
        cutout              : '62%',
        animation: {
          animateRotate: true,
          animateScale : true,
          duration     : 700,
          easing       : 'easeOutQuart'
        },
        plugins: {
          legend: {
            position: 'right',
            onClick(_e, legendItem) {
              const index = legendItem.index;
              const slug  = categories[index];
              if (slug === undefined) return;
              self._hidden[slug] = !self._hidden[slug];
              self.render();
            },
            labels: {
              padding     : 18,
              boxWidth    : 14,
              boxHeight   : 14,
              borderRadius: 4,
              font        : { size: 13, weight: '600', family: "'Inter', sans-serif" },
              color       : legendColor,
              generateLabels() {
                return labels.map((label, i) => {
                  const value   = data[i];
                  const percent = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                  const hidden  = !!self._hidden[categories[i]];
                  return {
                    text     : `${label}  ${percent}%`,
                    fillStyle: hidden ? 'rgba(128,128,128,0.3)' : colors[i],
                    fontColor: hidden
                      ? (self._isLightTheme() ? '#aaa' : '#555')
                      : legendColor,
                    hidden,
                    index : i,
                    height: 20
                  };
                });
              }
            }
          },
          tooltip: {
            backgroundColor: isLight ? '#fff'  : '#1a1a1d',
            titleColor     : isLight ? '#111'  : '#f5f5f7',
            bodyColor      : isLight ? '#444'  : '#ccc',
            borderColor    : isLight ? '#ddd'  : 'rgba(255,255,255,0.1)',
            borderWidth    : 1,
            padding        : 12,
            cornerRadius   : 10,
            filter         : (item) => item.parsed > 0,
            callbacks: {
              label(item) {
                const value   = item.parsed;
                const percent = visibleTotal > 0
                  ? ((value / visibleTotal) * 100).toFixed(1)
                  : '0.0';
                return `  ${item.label}: ${Utils.formatCurrency(value)}  (${percent}%)`;
              }
            }
          }
        }
      }
    });

    this._updateCategoryStats(totals, total, categoryStatsEl);
  },

  // ── Cards de estatísticas abaixo do gráfico ──────────────────────
  _updateCategoryStats(totals, total, containerEl) {
    if (!containerEl) return;

    const categories = Object.keys(totals).sort((a, b) => totals[b] - totals[a]);

    containerEl.innerHTML = categories.map(cat => {
      const value   = totals[cat];
      const percent = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
      const catId   = CONFIG.CATEGORIA_ID_MAP?.[cat];
      const label   = CONFIG.CATEGORIAS_DB[catId] || cat;
      const color   = CONFIG.CATEGORY_COLORS[cat] || '#6c757d';
      const hidden  = !!this._hidden[cat];

      return `
        <div class="stat-item" style="${hidden
          ? 'opacity:0.35;filter:grayscale(1);transition:opacity .25s,filter .25s;'
          : 'transition:opacity .25s,filter .25s;'}">
          <div class="stat-item-dot" style="background:${color}"></div>
          <div class="stat-item-label">${Utils.escapeHtml(label)}</div>
          <div class="stat-item-value">${Utils.formatCurrency(value)}</div>
          <div class="stat-item-percentage">${percent}% do total</div>
        </div>`;
    }).join('');

    containerEl.style.display = 'grid';
  },

  // ── Destroi com segurança ─────────────────────────────────────────
  _destroyChart() {
    if (this.chart) {
      this.chart.destroy();
      this.chart = null;
    }
  }
};