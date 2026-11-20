// Gerenciador de interface

import { CONFIG }       from './configuracao.js';
import { StateManager } from './gerenciadorEstado.js';
import { TipsEngine }   from './motorDicas.js';
import { ChartManager } from './gerenciadorGraficos.js';
import { Utils }        from './utilitarios.js';

export const UIManager = {

    elements: {},

    // Inicialização

    async init() {

        this._cacheElements();

        await this._loadInitialData();

        this._attachEventListeners();

    },

    // Cache dos elementos

    _cacheElements() {

        const $ = Utils.$;

        this.elements = {

            rendaInput: $('#rendaInput'),
            saveRendaBtn: $('#saveRenda'),

            categoriaEl: $('#categoria'),
            descricaoEl: $('#descricao'),
            valorEl: $('#valor'),
            tipoGastoEl: $('#tipoGasto'),
            formaPagamentoEl: $('#formaPagamento'),
            dataGastoEl: $('#dataGasto'),
            recorrenteEl: $('#recorrente'),
            observacaoEl: $('#observacao'),

            gastoForm: $('#gastoForm'),

            listaGastosEl: $('#listaGastos'),

            gastosTotalEl: $('#gastosTotal'),
            saldoAtualEl: $('#saldoAtual'),
            cardSaldo: $('#cardSaldo'),

            sozinhaSim: $('#sozinhaSim'),
            sozinhaNao: $('#sozinhaNao'),

            numPessoasWrap: $('#numPessoasWrap'),
            numPessoas: $('#numPessoas'),

            pNecessidades: $('#pNecessidades'),
            pDesejos: $('#pDesejos'),
            pPoupanca: $('#pPoupanca'),

            status503020: $('#status503020'),

            barraNecessidades: $('#barraNecessidades'),
            barraDesejos: $('#barraDesejos'),
            barraPoupanca: $('#barraPoupanca'),

            regra503020Container: $('#regra503020Container')

        };

    },

    // Carregamento inicial

    async _loadInitialData() {

        await this._loadServerRenda();

        await this._loadServerGastos();

        if (this.elements.rendaInput) {

            this.elements.rendaInput.value =
                (StateManager.get().renda || 0).toFixed(2);

        }

        this._updateViveSozinhoUI();

        this.refreshUI();

    },

    // Carrega a renda

    async _loadServerRenda() {

        try {

            const response = await fetch('../php/get_renda.php', {

                credentials: 'same-origin'

            });

            if (!response.ok)
                return;

            const json = await response.json();

            if (!json?.success)
                return;

            const rendaServidor = Number(

                json.valor ??
                json.renda ??
                0

            );

            StateManager.set('renda', rendaServidor);

            this._updateSaveBtn(rendaServidor > 0);

        }
        catch (erro) {

            console.error(
                '[UIManager] Erro ao carregar renda:',
                erro
            );

        }

    },

    // Texto do botão de renda

    _updateSaveBtn(temRenda) {

        const btn = this.elements.saveRendaBtn;

        if (!btn)
            return;

        btn.innerHTML = temRenda

            ? `
                <span>Alterar</span>
                <span class="visually-hidden">
                    renda mensal
                </span>
              `

            : `
                <span>Salvar</span>
                <span class="visually-hidden">
                    renda mensal
                </span>
              `;

    },


    // Carrega os gastos do servidor

    async _loadServerGastos() {

        try {

            const response = await fetch(
                '../php/get_gastos.php',
                {
                    credentials: 'same-origin'
                }
            );

            if (!response.ok)
                return;

            const json = await response.json();

            if (!json?.success || !Array.isArray(json.gastos))
                return;

            const state = StateManager.get();

            const mesServidor = json.mes_referencia ?? null;
            const mesLocal    = state.mesReferencia ?? null;

            // Detecta a virada do mês

            const mudouMes = (

                mesServidor &&
                mesLocal &&
                mesServidor !== mesLocal

            );

            if (mudouMes) {

                StateManager.update({

                    gastos: [],
                    renda: 0,
                    mesReferencia: mesServidor

                });

                if (this.elements.rendaInput)
                    this.elements.rendaInput.value = '';

                this._updateSaveBtn(false);

                this._showNewMonthBanner(
                    mesServidor,
                    json.periodo
                );

            }

            // Converte a resposta do PHP para o formato interno

            const gastos = json.gastos.map(gasto => {

                const categoriaId = String(
                    gasto.categoria_id || 'nova'
                );

                const slug =

                    CONFIG.CATEGORIA_SLUG_MAP[categoriaId]
                    || 'outros';

                return {

                    id_gasto: Number(gasto.id_gasto),

                    id_usuario: Number(

                        gasto.id_usuario ||
                        state.idUsuario

                    ),

                    categoria: slug,

                    descricao:
                        gasto.descricao || '',

                    valor:
                        Number(gasto.valor || 0),

                    data_gasto:
                        gasto.data_gasto ||
                        new Date().toISOString(),

                    status:

                        TipsEngine.calculateStatus(

                            slug,

                            Number(gasto.valor || 0),

                            state.renda

                        ),

                    tipo:
                        gasto.tipo || 'variavel',

                    forma_pagamento:
                        gasto.metodo_pagamento || '',

                    moeda:
                        gasto.moeda || 'BRL',

                    recorrente:
                        Boolean(Number(gasto.recorrente)),

                    observacao:
                        gasto.observacoes ?? null,

                    created_at:
                        gasto.criado_em ?? null

                };

            });

            StateManager.update({

                gastos,

                mesReferencia:

                    mesServidor ??
                    state.mesReferencia

            });

        }
        catch (erro) {

            console.error(

                '[UIManager] Erro ao carregar gastos:',

                erro

            );

        }

    },

    // Banner de novo mês

    _showNewMonthBanner(mesReferencia, periodo) {

        document
            .querySelector('#newMonthBanner')
            ?.remove();

        const [ano, mes] =

            (mesReferencia || '').split('-');

        const nomeMes =

            new Date(

                Number(ano),

                Number(mes) - 1,

                1

            ).toLocaleDateString(

                'pt-BR',

                {

                    month: 'long',

                    year: 'numeric'

                }

            );

        const fimPeriodo = periodo?.fim

            ? new Date(
                periodo.fim + 'T00:00:00'
              ).toLocaleDateString(

                'pt-BR',

                {

                    day: '2-digit',

                    month: '2-digit'

                }

              )

            : '';

        const banner = document.createElement('div');

        banner.id = 'newMonthBanner';

        banner.style.cssText = `

            position:fixed;
            top:80px;
            left:50%;
            transform:translateX(-50%);
            z-index:9999;

            width:min(500px,92vw);

            padding:1.3rem;

            border-radius:16px;

            background:linear-gradient(
                135deg,
                #ff9100,
                #ffb347
            );

            color:#fff;

            box-shadow:
                0 8px 30px rgba(0,0,0,.25);

            display:flex;

            gap:1rem;

            align-items:flex-start;

        `;

        banner.innerHTML = `

            <i
                class="bi bi-stars"
                style="font-size:2rem;">
            </i>

            <div style="flex:1">

                <strong
                    style="
                        display:block;
                        margin-bottom:.4rem;
                    ">

                    🎉 Bem-vindo a
                    ${nomeMes.charAt(0).toUpperCase() + nomeMes.slice(1)}

                </strong>

                <span
                    style="
                        font-size:.9rem;
                        opacity:.95;
                    ">

                    Os dados do mês anterior foram arquivados.

                    Informe sua nova renda para iniciar este mês.

                    ${

                        fimPeriodo

                        ? `<br><b>Mês termina em ${fimPeriodo}.</b>`

                        : ''

                    }

                </span>

            </div>

            <button

                onclick="this.parentElement.remove()"

                style="
                    background:none;
                    border:none;
                    color:white;
                    font-size:1.4rem;
                    cursor:pointer;
                ">

                &times;

            </button>

        `;

        document.body.appendChild(banner);

        setTimeout(() => {

            banner.remove();

        }, 8000);

    },

    // Atualização geral da interface

    refreshUI() {

        this._renderList();

        this._updateTotals();

        this._updateRegra503020();

        ChartManager.render();

    },


  // Eventos
  _attachEventListeners() {
    const el = this.elements;

    // Renda
    el.saveRendaBtn?.addEventListener('click', () => this._saveRenda());

    el.rendaInput?.addEventListener('keypress', e => {
      if (e.key === 'Enter') {
        e.preventDefault();
        this._saveRenda();
      }
    });

    // Cadastro de gasto
    el.gastoForm?.addEventListener('submit', e => {
      e.preventDefault();
      this._addGasto();
    });

    // Mora sozinho
    el.sozinhaSim?.addEventListener('change', () => {
      StateManager.update({
        viveSozinho: true,
        numPessoas: 1
      });

      this._updateViveSozinhoUI();
      this._updateRegra503020();
    });

    el.sozinhaNao?.addEventListener('change', () => {
      const pessoas = parseInt(el.numPessoas?.value) || 2;

      StateManager.update({
        viveSozinho: false,
        numPessoas: pessoas
      });

      this._updateViveSozinhoUI();
      this._updateRegra503020();
    });

    el.numPessoas?.addEventListener('change', () => {
      const pessoas = Math.max(
        1,
        parseInt(el.numPessoas.value) || 1
      );

      StateManager.set('numPessoas', pessoas);

      this._updateRegra503020();
    });

    // Atualiza a regra quando a renda muda
    el.rendaInput?.addEventListener('input', () => {
      const valor = parseFloat(el.rendaInput.value);

      if (!isNaN(valor) && valor >= 0) {
        StateManager.set('renda', valor);
        this._updateRegra503020();
      }
    });
  },


  // Salvar renda
  async _saveRenda() {
    const el = this.elements;
    const novaRenda = parseFloat(el.rendaInput?.value);

    if (isNaN(novaRenda) || novaRenda < 0) {
      return Utils.showToast(
        'Por favor, insira um valor válido para a renda.',
        'warning'
      );
    }

    // Evita requisições duplicadas
    if (this._savingRenda) return;
    this._savingRenda = true;

    const btn = el.saveRendaBtn;
    const textoOriginal = btn?.innerHTML;

    try {

      // Atualiza o estado local imediatamente
      StateManager.set('renda', novaRenda);

      const state = StateManager.get();

      // Recalcula o status de todos os gastos
      const gastosAtualizados = state.gastos.map(gasto => ({
        ...gasto,
        status: TipsEngine.calculateStatus(
          gasto.categoria,
          Number(gasto.valor || 0),
          novaRenda
        )
      }));

      StateManager.update({
        gastos: gastosAtualizados
      });

      // Atualiza toda a interface
      this.refreshUI();

      // Feedback visual
      if (btn) {
        btn.disabled = true;
        btn.innerHTML =
          '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';
      }

      const response = await fetch('../php/saveRenda.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          valor: novaRenda
        })
      });

      const json = await response.json();

      if (!response.ok || !json.success) {
        throw new Error(
          json?.message || 'Erro ao salvar renda.'
        );
      }

      this._updateSaveBtn(true);

      if (btn) {
        btn.innerHTML =
          '<i class="bi bi-check-lg me-1"></i>Salvo!';
      }

      Utils.showToast(
        json.message || 'Renda salva com sucesso!',
        'success'
      );

      // Atualiza novamente caso o servidor tenha retornado um valor tratado
      if (json.valor !== undefined) {
        StateManager.set('renda', Number(json.valor));
        this.refreshUI();
      }

    } catch (erro) {

      console.error('[UI] saveRenda:', erro);

      Utils.showToast(
        erro.message || 'Erro ao salvar renda.',
        'error'
      );

    } finally {

      this._savingRenda = false;

      if (btn) {

        setTimeout(() => {

          btn.disabled = false;

          this._updateSaveBtn(
            (StateManager.get().renda || 0) > 0
          );

        }, 1500);

      }

    }
  },



// Validação do formulário (aviso e foco)
_focusWarn(mensagem, elemento) {

  Utils.showToast(mensagem, 'warning');

  if (!elemento) return;

  elemento.classList.add('is-invalid');

  elemento.focus({ preventScroll: false });

  if (typeof elemento.select === 'function') {
    elemento.select();
  }

  // Remove o destaque de erro assim que o usuário começar a corrigir
  const limparDestaque = () => {
    elemento.classList.remove('is-invalid');
    elemento.removeEventListener('input', limparDestaque);
  };

  elemento.addEventListener('input', limparDestaque, { once: true });

},



// Limpa o formulário após salvar um gasto, deixando-o pronto para o próximo lançamento
_resetForm() {

  const el = this.elements;

  if (el.descricaoEl) el.descricaoEl.value = '';

  if (el.valorEl) el.valorEl.value = '';

  if (el.observacaoEl) el.observacaoEl.value = '';

  if (el.recorrenteEl) el.recorrenteEl.checked = false;

  // Categoria volta para a opção em branco/padrão do <select>
  if (el.categoriaEl) el.categoriaEl.selectedIndex = 0;

  // Campo de categoria nova: some e limpa, igual ao padrão já usado
  // no formulário de edição (#edit_categoria_nova)
  const categoriaNovaEl = Utils.$('#categoria_nova');
  if (categoriaNovaEl) {
    categoriaNovaEl.value = '';
    categoriaNovaEl.style.display = 'none';
  }

  // Tipo e forma de pagamento voltam para os valores mais comuns,
  // já que a maioria dos lançamentos seguidos costuma repetir isso
  if (el.tipoGastoEl) el.tipoGastoEl.value = 'variavel';

  if (el.formaPagamentoEl) el.formaPagamentoEl.selectedIndex = 0;

  // Data volta para hoje, pronta para o próximo lançamento
  if (el.dataGastoEl) el.dataGastoEl.value = StateManager.getTodayISO();

  // Remove qualquer destaque de erro deixado de uma validação anterior
  [
    el.descricaoEl,
    el.valorEl,
    el.dataGastoEl
  ].forEach(campo => campo?.classList.remove('is-invalid'));

  // Foca de volta na descrição, pronto para o próximo gasto
  el.descricaoEl?.focus();

},



// Adicionar gasto
async _addGasto() {
  const el = this.elements;

  const descricao = el.descricaoEl?.value.trim();
  const valor = parseFloat(el.valorEl?.value);
  const data = el.dataGastoEl?.value;

  if (!descricao) {
    return this._focusWarn('Digite uma descrição para o gasto.', el.descricaoEl);
  }

  if (isNaN(valor) || valor <= 0) {
    return this._focusWarn('Informe um valor válido.', el.valorEl);
  }

  if (!data) {
    return this._focusWarn('Selecione a data do gasto.', el.dataGastoEl);
  }

  const categoriaSelecionada = el.categoriaEl?.value;

  const payload = {
    descricao: descricao,
    valor: Number(valor),
    tipo: el.tipoGastoEl?.value || 'variavel',
    metodo_pagamento: el.formaPagamentoEl?.value || 'outros',
    data_gasto: data,
    recorrente: el.recorrenteEl?.checked ? 1 : 0,
    observacoes: el.observacaoEl?.value.trim() || ''
  };

  // Categoria existente ou nova
  if (
    categoriaSelecionada &&
    /^[0-9]+$/.test(String(categoriaSelecionada))
  ) {
    payload.categoria_id = Number(categoriaSelecionada);
  } else {
    payload.categoria_nova =
      categoriaSelecionada === 'nova'
        ? (Utils.$('#categoria_nova')?.value.trim() || '')
        : categoriaSelecionada;
  }

  try {

    const response = await fetch('/TCC/Codigo/php/gastos.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const texto = await response.text();

    let json;

    try {
      json = JSON.parse(texto);
    } catch (e) {
      console.error(texto);
      return Utils.showToast(
        'Resposta inválida do servidor.',
        'error'
      );
    }

    if (!response.ok || !json.success) {
      return Utils.showToast(
        json.message || 'Erro ao cadastrar gasto.',
        'error'
      );
    }

    const state = StateManager.get();

    const slugCategoria =
      CONFIG.CATEGORIA_SLUG_MAP[
        String(json.categoria_id)
      ] || 'outros';

    const novoGasto = {

      id_gasto: Number(json.id_gasto),

      id_usuario: state.idUsuario,

      categoria: slugCategoria,

      descricao: descricao,

      valor: Number(valor),

      data_gasto: Utils.parseDateToISO(data),

      status: TipsEngine.calculateStatus(
        slugCategoria,
        Number(valor),
        state.renda
      ),

      tipo: payload.tipo,

      forma_pagamento: payload.metodo_pagamento,

      moeda: 'BRL',

      recorrente: !!payload.recorrente,

      observacao: payload.observacoes || null,

      created_at: new Date().toISOString()
    };

    state.gastos.push(novoGasto);

    StateManager.save();

    this.refreshUI();

    this._resetForm();

    Utils.showToast(
      json.message || 'Gasto cadastrado com sucesso!',
      'success'
    );

  } catch (erro) {

    console.error('[UIManager]', erro);

    Utils.showToast(
      'Erro de comunicação com o servidor.',
      'error'
    );
  }
},



// Lista de gastos
_renderList() {

    const tabela = this.elements.listaGastosEl;

    if (!tabela) return;

    const { gastos } = StateManager.get();

    if (!Array.isArray(gastos) || gastos.length === 0) {

        tabela.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">

                    <i class="bi bi-wallet2"
                       style="font-size:3rem;opacity:.35;">
                    </i>

                    <p class="mt-3 mb-0">
                        Nenhum gasto cadastrado.
                    </p>

                </td>
            </tr>
        `;

        return;
    }

    const listaOrdenada = [...gastos].sort((a, b) => {

        return new Date(b.data_gasto) - new Date(a.data_gasto);

    });

    tabela.innerHTML = listaOrdenada.map(gasto => {

        const status = TipsEngine.getStatusInfo(gasto.status);

        const categoriaId =
            CONFIG.CATEGORIA_ID_MAP[gasto.categoria];

        const categoriaNome =
            CONFIG.CATEGORIAS_DB[categoriaId] ||
            gasto.categoria;

        const tipoNome =
            CONFIG.TIPOS_DB[gasto.tipo] ||
            gasto.tipo;

        const pagamentoNome =
            CONFIG.FORMAS_PAGAMENTO_DB[gasto.forma_pagamento] ||
            gasto.forma_pagamento;

        const corCategoria =
            CONFIG.CATEGORY_COLORS[gasto.categoria] ||
            '#6c757d';

        const recorrente = gasto.recorrente
            ? `<i class="bi bi-arrow-repeat ms-1 text-muted"
                  title="Gasto recorrente"></i>`
            : '';

        const observacao = gasto.observacao
            ? `
                <br>

                <small class="text-muted fst-italic">

                    ${Utils.escapeHtml(gasto.observacao)}

                </small>
            `
            : '';

        return `

            <tr data-id="${gasto.id_gasto}">

                <td class="text-center">

                    <span class="status-badge"
                          title="${status.label}">

                        ${status.icon}

                    </span>

                </td>

                <td>

                    <span class="badge"
                          style="
                                background:${corCategoria};
                                color:#fff;
                          ">

                        ${Utils.escapeHtml(categoriaNome)}

                    </span>

                </td>

                <td>

                    <span
                        class="expense-description"

                        data-id="${gasto.id_gasto}"

                        role="button"

                        tabindex="0"

                        title="Clique para visualizar dicas">

                        ${Utils.escapeHtml(gasto.descricao)}

                        ${recorrente}

                    </span>

                    ${observacao}

                </td>

                <td>

                    <span class="badge bg-secondary">

                        ${Utils.escapeHtml(tipoNome)}

                    </span>

                </td>

                <td>

                    <span class="badge bg-info text-dark">

                        ${Utils.escapeHtml(pagamentoNome)}

                    </span>

                </td>

                <td class="text-nowrap">

                    ${Utils.formatDate(gasto.data_gasto)}

                </td>

                <td class="text-end fw-bold is-num">

                    ${Utils.formatCurrency(gasto.valor)}

                </td>

                <td class="text-nowrap">

                    <button

                        class="btn btn-sm btn-outline-warning edit-btn"

                        data-id="${gasto.id_gasto}"

                        title="Editar">

                        <i class="bi bi-pencil"></i>

                    </button>

                    <button

                        class="btn btn-sm btn-outline-danger remove-btn"

                        data-id="${gasto.id_gasto}"

                        title="Excluir">

                        <i class="bi bi-trash"></i>

                    </button>

                </td>

            </tr>

        `;

    }).join('');

    this._attachListEvents();

},

  _attachListEvents() {
    // Clique na descrição → abre modal de dicas
    Utils.$$('.expense-description').forEach(item => {

      item.addEventListener('click', () => {
        this._showTipsModal(Number(item.dataset.id));
      });

      item.addEventListener('keypress', e => {
        if (e.key === 'Enter') {
          this._showTipsModal(Number(item.dataset.id));
        }
      });

    });

    // Botões editar
    Utils.$$('.edit-btn').forEach(btn => {

      btn.addEventListener('click', () => {
        this._openEditModal(Number(btn.dataset.id));
      });

    });

    // Botões remover
    Utils.$$('.remove-btn').forEach(btn => {

      btn.addEventListener('click', () => {
        this._removeItem(Number(btn.dataset.id));
      });

    });
  },


  // Modal de dicas
   async _showTipsModal(id) {
    const { gastos } = StateManager.get();
    const gasto = gastos.find(g => Number(g.id_gasto) === Number(id));

    if (!gasto) {
      Utils.showToast('Gasto não encontrado.', 'warning');
      return;
    }

    const statusInfo = TipsEngine.getStatusInfo(gasto.status);
    const nivel      = TipsEngine.getNivel(gasto.status);
    const dicas = await TipsEngine.getTipsInteligentes(gasto,nivel);


    const categoriaId =
      CONFIG.CATEGORIA_ID_MAP[gasto.categoria];

    const categoriaNome =
      CONFIG.CATEGORIAS_DB[categoriaId] ||
      gasto.categoria;

    const modalBody = Utils.$('#modalBody');

    if (!modalBody) return;

    const dicasHtml = dicas.map((texto, indice) => `
        <div class="d-flex align-items-start gap-3 mb-3">

            <div style="
                width:32px;
                height:32px;
                min-width:32px;
                border-radius:50%;
                background:${statusInfo.color};
                color:#fff;
                display:flex;
                justify-content:center;
                align-items:center;
                font-weight:700;
            ">
                ${indice + 1}
            </div>

            <div style="
                flex:1;
                color:var(--text-secondary);
                line-height:1.6;
            ">
                ${texto}
            </div>

        </div>
    `).join('');

    modalBody.innerHTML = `

        <div class="text-center mb-4">

            <div style="
                font-size:3.5rem;
                margin-bottom:.8rem;
            ">
                ${statusInfo.icon}
            </div>

            <h4 class="fw-bold mb-2">
                ${Utils.escapeHtml(gasto.descricao)}
            </h4>

            <div class="mb-2 text-muted">

                <span class="badge"
                    style="
                        background:${CONFIG.CATEGORY_COLORS[gasto.categoria] || '#6c757d'};
                        color:#fff;
                    ">
                    ${Utils.escapeHtml(categoriaNome)}
                </span>

            </div>

            <div
                style="
                    font-size:2rem;
                    font-weight:700;
                "
                class="mb-3">

                ${Utils.formatCurrency(gasto.valor)}

            </div>

            <span
                class="badge px-3 py-2"
                style="
                    background:${statusInfo.color}20;
                    color:${statusInfo.color};
                    border:1px solid ${statusInfo.color};
                    font-size:.95rem;
                ">

                ${statusInfo.icon}
                ${statusInfo.label}

            </span>

        </div>

        <hr>

        <div>

            <h5 class="mb-3">

                <i class="bi bi-lightbulb-fill me-2"></i>

                Recomendações personalizadas

            </h5>

            ${dicasHtml}

        </div>

    `;

    const modalElement = Utils.$('#tipsModal');

    if (!modalElement) return;

    const modal =
        bootstrap.Modal.getOrCreateInstance(modalElement);

    modal.show();

  },


  // Editar gasto
  _openEditModal(id) {

    const { gastos } = StateManager.get();

    const gasto = gastos.find(g =>
      Number(g.id_gasto) === Number(id)
    );

    if (!gasto) {
      Utils.showToast('Gasto não encontrado.', 'warning');
      return;
    }

    const editId              = Utils.$('#editId');
    const editCategoria       = Utils.$('#editCategoria');
    const editDescricao       = Utils.$('#editDescricao');
    const editValor           = Utils.$('#editValor');
    const editTipo            = Utils.$('#editTipo');
    const editFormaPagamento  = Utils.$('#editFormaPagamento');
    const editData            = Utils.$('#editData');
    const editRecorrente      = Utils.$('#editRecorrente');
    const editObservacao      = Utils.$('#editObservacao');

    if (!editId || !editCategoria || !editDescricao ||
        !editValor || !editTipo || !editFormaPagamento ||
        !editData || !editRecorrente || !editObservacao) {

      Utils.showToast('Modal de edição não encontrado.', 'error');
      return;
    }

    // Converte slug → id do select
    let categoriaSelecionada = 'nova';

    for (const [idCategoria, slug] of Object.entries(CONFIG.CATEGORIA_SLUG_MAP)) {

      if (slug === gasto.categoria) {
        categoriaSelecionada = idCategoria;
        break;
      }

    }

    editId.value             = gasto.id_gasto;
    editCategoria.value      = categoriaSelecionada;
    editDescricao.value      = gasto.descricao;
    editValor.value          = Number(gasto.valor).toFixed(2);
    editTipo.value           = gasto.tipo;
    editFormaPagamento.value = gasto.forma_pagamento;

    editData.value = gasto.data_gasto
      ? gasto.data_gasto.split('T')[0]
      : '';

    editRecorrente.checked = Boolean(gasto.recorrente);

    editObservacao.value = gasto.observacao || '';

    // Caso exista categoria personalizada
    const categoriaNova = Utils.$('#edit_categoria_nova');

    if (categoriaNova) {

      if (categoriaSelecionada === 'nova') {
        categoriaNova.style.display = '';
        categoriaNova.value = gasto.categoria || '';
      } else {
        categoriaNova.style.display = 'none';
        categoriaNova.value = '';
      }

    }

    const modalElement = Utils.$('#editModal');

    if (!modalElement) {
      Utils.showToast('Modal não encontrado.', 'error');
      return;
    }

    const modal =
      bootstrap.Modal.getOrCreateInstance(modalElement);

    modal.show();

    const saveBtn = Utils.$('#saveEdit');

    if (saveBtn) {

      saveBtn.onclick = () => {
        this._saveEdit(modal);
      };

    }

  },


  // Salvar edição
  async _saveEdit(modal) {

    const id = Number(Utils.$('#editId')?.value);

    const state = StateManager.get();

    const gasto = state.gastos.find(g =>
      Number(g.id_gasto) === id
    );

    if (!gasto) {
      Utils.showToast('Gasto não encontrado.', 'warning');
      return;
    }

    const descricao = Utils.$('#editDescricao')?.value.trim();
    const valor     = parseFloat(Utils.$('#editValor')?.value);
    const data      = Utils.$('#editData')?.value;

    if (!descricao) {
      return Utils.showToast('Informe a descrição.', 'warning');
    }

    if (isNaN(valor) || valor <= 0) {
      return Utils.showToast('Informe um valor válido.', 'warning');
    }

    if (!data) {
      return Utils.showToast('Selecione uma data.', 'warning');
    }

    const categoriaSelecionada = Utils.$('#editCategoria')?.value;

    const payload = {

      id_gasto: id,

      descricao: descricao,

      valor: Number(valor),

      tipo: Utils.$('#editTipo')?.value || 'variavel',

      metodo_pagamento:
        Utils.$('#editFormaPagamento')?.value || 'outros',

      data_gasto: data,

      recorrente:
        Utils.$('#editRecorrente')?.checked ? 1 : 0,

      observacoes:
        Utils.$('#editObservacao')?.value.trim() || ''

    };

    if (
      categoriaSelecionada &&
      /^[0-9]+$/.test(String(categoriaSelecionada))
    ) {

      payload.categoria_id = Number(categoriaSelecionada);

    } else {

      payload.categoria_nova =
        categoriaSelecionada === 'nova'
          ? (Utils.$('#edit_categoria_nova')?.value.trim() || '')
          : categoriaSelecionada;

    }

    try {

      Utils.showToast('Salvando alterações...', 'info');

      const response = await fetch('/TCC/Codigo/php/update_gasto.php', {

        method: 'POST',

        credentials: 'same-origin',

        headers: {
          'Content-Type': 'application/json'
        },

        body: JSON.stringify(payload)

      });

      const json = await response.json();

      if (!json?.success) {

        return Utils.showToast(
          json.message || 'Erro ao atualizar gasto.',
          'error'
        );

      }

      let categoriaSlug = gasto.categoria;

      if (payload.categoria_id) {

        categoriaSlug =
          CONFIG.CATEGORIA_SLUG_MAP[
            String(payload.categoria_id)
          ] || 'outros';

      } else if (payload.categoria_nova) {

        categoriaSlug = payload.categoria_nova;

      }

      Object.assign(gasto, {

        categoria: categoriaSlug,

        descricao: descricao,

        valor: Number(valor),

        tipo: payload.tipo,

        forma_pagamento: payload.metodo_pagamento,

        data_gasto: Utils.parseDateToISO(data),

        recorrente: Boolean(payload.recorrente),

        observacao: payload.observacoes || null,

        status: TipsEngine.calculateStatus(
          categoriaSlug,
          Number(valor),
          state.renda
        )

      });

      StateManager.save();

      this.refreshUI();

      modal.hide();

      Utils.showToast(
        json.message || 'Gasto atualizado com sucesso!',
        'success'
      );

    } catch (error) {

      console.error('[UI] saveEdit:', error);

      Utils.showToast(
        'Erro de rede ao atualizar o gasto.',
        'error'
      );

    }

  },

  // Remover gasto
  async _removeItem(id) {

    const state = StateManager.get();

    const gasto = state.gastos.find(g =>
      Number(g.id_gasto) === Number(id)
    );

    if (!gasto) {
      Utils.showToast('Gasto não encontrado.', 'warning');
      return;
    }

    const confirmar = confirm(
      `Deseja realmente remover o gasto?\n\n` +
      `${gasto.descricao}\n` +
      `${Utils.formatCurrency(gasto.valor)}`
    );

    if (!confirmar) return;

    try {

      Utils.showToast('Removendo gasto...', 'info');

      const response = await fetch('/TCC/Codigo/php/delete_gasto.php', {

        method: 'POST',

        credentials: 'same-origin',

        headers: {
          'Content-Type': 'application/json'
        },

        body: JSON.stringify({
          id_gasto: Number(id)
        })

      });

      const json = await response.json();

      if (!json?.success) {

        return Utils.showToast(
          json.message || 'Erro ao remover gasto.',
          'error'
        );

      }

      state.gastos = state.gastos.filter(g =>
        Number(g.id_gasto) !== Number(id)
      );

      StateManager.save();

      this.refreshUI();

      Utils.showToast(
        json.message || 'Gasto removido com sucesso!',
        'success'
      );

    } catch (error) {

      console.error('[UI] removeItem:', error);

      Utils.showToast(
        'Erro de rede ao remover gasto.',
        'error'
      );

    }

  },

  // Regra 50/30/20

  _updateViveSozinhoUI() {

    const {
      viveSozinho,
      numPessoas
    } = StateManager.get();

    const el = this.elements;

    if (viveSozinho === false) {

      if (el.sozinhaNao)
        el.sozinhaNao.checked = true;

      if (el.sozinhaSim)
        el.sozinhaSim.checked = false;

      if (el.numPessoasWrap)
        el.numPessoasWrap.style.display = 'block';

    } else {

      if (el.sozinhaSim)
        el.sozinhaSim.checked = true;

      if (el.sozinhaNao)
        el.sozinhaNao.checked = false;

      if (el.numPessoasWrap)
        el.numPessoasWrap.style.display = 'none';

    }

    if (el.numPessoas) {
      el.numPessoas.value = Number(numPessoas || 1);
    }

  },

  // Classificação de faixas (ótimo, atenção, crítico)
  // Fonte única dos limites do 50/30/20

  _TIER_CORES: {
    otimo: '#51cf66',
    atencao: '#ffc107',
    critico: '#ff6b6b'
  },

  _TIER_STATUS: {
    otimo: { texto: '🟢 Ótimo', fundo: 'rgba(81,207,102,.15)' },
    atencao: { texto: '🟡 Atenção', fundo: 'rgba(255,193,7,.15)' },
    critico: { texto: '🔴 Crítico', fundo: 'rgba(255,107,107,.15)' }
  },

  _TIER_ORDEM: ['otimo', 'atencao', 'critico'],

  // Classifica um percentual numa faixa.
  // invertido = true -> valores MENORES são piores (caso da poupança).
  _classificarFaixa(valor, limiteBom, limiteRuim, invertido = false) {

    if (invertido) {

      if (valor < limiteRuim)
        return 'critico';

      if (valor < limiteBom)
        return 'atencao';

      return 'otimo';

    }

    if (valor > limiteRuim)
      return 'critico';

    if (valor > limiteBom)
      return 'atencao';

    return 'otimo';

  },

  _piorTier(tiers) {

    return tiers.reduce((pior, atual) =>

      this._TIER_ORDEM.indexOf(atual) > this._TIER_ORDEM.indexOf(pior)
        ? atual
        : pior,

      'otimo'

    );

  },

  _updateRegra503020() {

    const {
      renda,
      gastos
    } = StateManager.get();

    const el = this.elements;

    if (!el.regra503020Container)
      return;

    if (!renda || renda <= 0) {

      el.regra503020Container.style.display = 'none';
      return;

    }

    let necessidades = 0;
    let desejos = 0;
    let total = 0;

    gastos.forEach(gasto => {

      const categoria = gasto.categoria || 'outros';
      const valor = Number(gasto.valor) || 0;

      total += valor;

      if (CONFIG.CATEGORY_GROUPS.necessidades.includes(categoria)) {

        necessidades += valor;

      } else if (CONFIG.CATEGORY_GROUPS.desejos.includes(categoria)) {

        desejos += valor;

      }

    });

    const poupanca = Math.max(0, renda - total);

    const percentualNec = (necessidades / renda) * 100;
    const percentualDes = (desejos / renda) * 100;
    const percentualPou = (poupanca / renda) * 100;

    // Classifica as três faixas uma única vez.
    const tierNec = this._classificarFaixa(percentualNec, 50, 58);
    const tierDes = this._classificarFaixa(percentualDes, 30, 40);
    const tierPou = this._classificarFaixa(percentualPou, 20, 10, true);

    const atualizarBarra = (barra, valor, tier) => {

      if (!barra)
        return;

      const porcentagem = Math.min(valor, 100);

      barra.style.width = porcentagem + '%';
      barra.setAttribute('aria-valuenow', porcentagem.toFixed(1));
      barra.style.background = this._TIER_CORES[tier];

    };

    if (el.pNecessidades)
      el.pNecessidades.textContent = percentualNec.toFixed(1) + '%';

    if (el.pDesejos)
      el.pDesejos.textContent = percentualDes.toFixed(1) + '%';

    if (el.pPoupanca)
      el.pPoupanca.textContent = percentualPou.toFixed(1) + '%';

    atualizarBarra(el.barraNecessidades, percentualNec, tierNec);
    atualizarBarra(el.barraDesejos, percentualDes, tierDes);
    atualizarBarra(el.barraPoupanca, percentualPou, tierPou);

    // O status geral é sempre a pior das três faixas.
    const tierGeral = this._piorTier([tierNec, tierDes, tierPou]);
    const { texto: status, fundo } = this._TIER_STATUS[tierGeral];
    const cor = this._TIER_CORES[tierGeral];

    if (el.status503020) {

      el.status503020.textContent = status;
      el.status503020.style.color = cor;
      el.status503020.style.borderColor = cor;
      el.status503020.style.background = fundo;

    }

    // Atualiza o cache do TipsEngine
    TipsEngine.setRegra503020({

      necessidades: percentualNec.toFixed(1),

      desejos: percentualDes.toFixed(1),

      poupanca: percentualPou.toFixed(1),

      status

    });

    el.regra503020Container.style.display = 'block';

  },

  // Totais

  _updateTotals() {

    const {
      renda,
      gastos
    } = StateManager.get();

    const total = gastos.reduce(
      (soma, gasto) => soma + Number(gasto.valor || 0),
      0
    );

    const saldo = Number(renda || 0) - total;

    if (this.elements.gastosTotalEl)
      Utils.animateValue(
        this.elements.gastosTotalEl,
        total
      );

    if (this.elements.saldoAtualEl)
      Utils.animateValue(
        this.elements.saldoAtualEl,
        saldo
      );

    const cardSaldo = this.elements.cardSaldo;
    const saldoAtual = this.elements.saldoAtualEl;

    if (cardSaldo && saldoAtual) {

      if (saldo < 0) {

        cardSaldo.classList.add('border-danger');
        saldoAtual.style.color = '#ff6b6b';

      } else {

        cardSaldo.classList.remove('border-danger');
        saldoAtual.style.color = '#51cf66';

      }

    }

  }

};