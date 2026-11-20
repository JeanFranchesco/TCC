// Gerenciador de tema

import { CONFIG }       from './configuracao.js';
import { ChartManager } from './gerenciadorGraficos.js';

export const ThemeManager = {

  init() {
    const themeToggle = document.querySelector('#themeToggle');
    const themeIcon   = document.querySelector('#themeIcon');

    // Aplica o tema salvo (ou escuro por padrão)
    const savedTheme = localStorage.getItem(CONFIG.THEME_KEY) || 'dark';
    this.applyTheme(savedTheme, themeIcon);

    if (!themeToggle) return;

    themeToggle.addEventListener('click', () => {
      const isLight  = document.documentElement.classList.contains('light-theme');
      const newTheme = isLight ? 'dark' : 'light';

      localStorage.setItem(CONFIG.THEME_KEY, newTheme);
      this.applyTheme(newTheme, themeIcon);

      // Re-renderiza o gráfico para adaptar cores ao novo tema
      ChartManager.render();
    });
  },

  applyTheme(theme, iconEl) {
    const html = document.documentElement;

    if (theme === 'light') {
      html.classList.add('light-theme');
      if (iconEl) {
        iconEl.className = 'bi bi-sun-fill';
        iconEl.closest('button')?.setAttribute('title', 'Mudar para tema escuro');
      }
    } else {
      html.classList.remove('light-theme');
      if (iconEl) {
        iconEl.className = 'bi bi-moon-stars';
        iconEl.closest('button')?.setAttribute('title', 'Mudar para tema claro');
      }
    }
  }
};