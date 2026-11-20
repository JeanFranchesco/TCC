/**
 * FINANÇAS JÁ — SISTEMA DE GESTÃO DE GASTOS
 * Entry point principal
 * @version 3.2.0
 */

import { StateManager }  from './gerenciadorEstado.js';
import { ThemeManager }  from './gerenciadorTema.js';
import { UIManager }     from './gerenciadorInterface.js';
import { TipsEngine }    from './motorDicas.js';

// ── Inicialização ─────────────────────────────────────────────────
async function init() {
  try {
    // StateManager.init() precisa rodar ANTES de tudo: é ele quem
    // restaura o estado salvo no localStorage (ou detecta virada de
    // mês e zera renda/gastos locais). Sem essa chamada, o estado
    // sempre começava do zero em memória a cada carregamento — e o
    // gerenciadorInterface.js dependia inteiramente do _loadServerRenda()
    // assíncrono pra corrigir isso depois, criando uma janela onde
    // outros scripts (como o chat) podiam ler um localStorage
    // desatualizado antes desse fetch terminar.
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