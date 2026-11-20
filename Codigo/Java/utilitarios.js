// ========== UTILITÁRIOS ==========
// Finanças Já v3.1.0

import { CONFIG } from './configuracao.js';

export const Utils = {
  // ── Seletores ─────────────────────────────────────────────────────
  $  : (sel) => document.querySelector(sel),
  $$ : (sel) => document.querySelectorAll(sel),

  // ── Formatação de moeda ───────────────────────────────────────────
  formatter: new Intl.NumberFormat('pt-BR', {
    style   : 'currency',
    currency: 'BRL'
  }),

  formatCurrency(value = 0) {
    const n = Number(value);
    return this.formatter.format(isNaN(n) ? 0 : n);
  },

  // ── Formatação de data ────────────────────────────────────────────
  formatDate(dateString) {
    if (!dateString) return '—';
    // Garante que a string seja tratada como UTC para evitar off-by-one
    const raw  = String(dateString).split('T')[0];
    const [y, m, d] = raw.split('-');
    if (!y || !m || !d) return '—';
    return `${d}/${m}/${y}`;
  },

  // ── Converte "YYYY-MM-DD" → ISO completo ─────────────────────────
  parseDateToISO(dateString) {
    if (!dateString) return new Date().toISOString();
    const date = new Date(`${dateString}T00:00:00`);
    return isNaN(date.getTime()) ? new Date().toISOString() : date.toISOString();
  },

  // ── Sanitização HTML ──────────────────────────────────────────────
  escapeHtml(str = '') {
    const div = document.createElement('div');
    div.textContent = String(str);
    return div.innerHTML;
  },

  // ── Animação de valor numérico (easing quartic) ───────────────────
  animateValue(element, endValue, duration = CONFIG?.ANIMATION_DURATION ?? 800) {
    if (!element) return;

    const safeEnd   = Number(endValue) || 0;
    const rawText   = element.textContent || '';
    const cleaned   = rawText.replace(/[^\d,\-]/g, '').replace(/\./g, '').replace(',', '.');
    const startValue = Number(cleaned) || 0;

    const startTime = performance.now();

    const update = (now) => {
      const elapsed  = now - startTime;
      const progress = Math.min(elapsed / duration, 1);
      const eased    = 1 - Math.pow(1 - progress, 4);
      const current  = startValue + (safeEnd - startValue) * eased;

      element.textContent = this.formatCurrency(current);

      if (progress < 1) {
        requestAnimationFrame(update);
      } else {
        element.textContent = this.formatCurrency(safeEnd);
      }
    };

    requestAnimationFrame(update);
  },

  // ── Toast de notificação ──────────────────────────────────────────
  showToast(message, type = 'success') {
    const toastEl = this.$('#notificationToast');
    if (!toastEl) return;

    const toastBody   = toastEl.querySelector('.toast-body');
    const toastHeader = toastEl.querySelector('.toast-header');
    const icon        = toastHeader?.querySelector('i');

    if (!toastBody || !toastHeader || !icon) return;

    const icons = {
      success: 'bi-check-circle-fill text-success',
      error  : 'bi-x-circle-fill text-danger',
      warning: 'bi-exclamation-triangle-fill text-warning',
      info   : 'bi-info-circle-fill text-info'
    };

    icon.className       = `bi ${icons[type] ?? icons.success} me-2`;
    toastBody.textContent = String(message);

    if (typeof bootstrap === 'undefined' || !bootstrap.Toast) return;

    const toast = new bootstrap.Toast(toastEl, {
      autohide: true,
      delay   : CONFIG?.TOAST_DURATION ?? 3500
    });
    toast.show();
  },

  // ── Debounce simples ──────────────────────────────────────────────
  debounce(fn, delay = 300) {
    let timer;
    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => fn.apply(this, args), delay);
    };
  },

  // ── Gera ID único temporário (fallback antes de obter ID do BD) ───
  tempId() {
    return Date.now() + Math.floor(Math.random() * 1000);
  }
};