/**
 * Ponto de entrada da página de gastos
 */

import { StateManager }  from './gerenciadorEstado.js';
import { ThemeManager }  from './gerenciadorTema.js';
import { UIManager }     from './gerenciadorInterface.js';
import { TipsEngine }    from './motorDicas.js';

// Inicialização
async function init() {
  try {
    // Restaura o estado salvo (ou zera ao virar o mês) antes de carregar a interface
    StateManager.init();

    ThemeManager.init();
    await TipsEngine.init();
    await UIManager.init();
  } catch (error) {
    console.error('❌ Erro ao inicializar o sistema:', error);
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}