// Motor de dicas (IA via Ollama, com dicas locais como alternativa)

import { CONFIG }       from './configuracao.js';
import { StateManager } from './gerenciadorEstado.js';

export const TipsEngine = {

  // Cache

_perfilCognitivo: null,
_historicoPerfil: [],
_rendaMensal: 0,

_regra503020: {
    necessidades: 0,
    desejos: 0,
    poupanca: 0,
    status: "Desconhecido"
},

_historicoFinanceiro: null,
_gastos: [],

_cacheIA: new Map(),

  // Inicialização

async init() {

    try {

await this.carregarPerfilCognitivo();

await this.carregarRenda();

await this.carregarHistoricoFinanceiro();

await this.carregarGastos();
    } catch (err) {

        console.warn(
            '[TipsEngine] Erro durante inicialização.',
            err
        );

    }

},

  // Carrega o perfil cognitivo

  async carregarPerfilCognitivo() {

    try {

      const response = await fetch(
        '../php/buscar_perfil_cognitivo.php',
        {
          credentials: 'same-origin'
        }
      );

      if (!response.ok)
        throw new Error('Erro HTTP');

      const json = await response.json();

      if (!json.success)
        return;

      this._perfilCognitivo = json.perfil ?? null;
      this._historicoPerfil = json.historico ?? [];

    } catch (err) {

      console.error(
        '[TipsEngine] Erro ao carregar perfil:',
        err
      );

    }

  },
async carregarRenda() {

    try {

        const response = await fetch(
            "../php/get_renda.php",
            {
                credentials: "same-origin"
            }
        );

        if (!response.ok)
            throw new Error("Erro HTTP");

        const json = await response.json();

        if (!json.success)
            return;

        this._rendaMensal = Number(json.valor) || 0;

    } catch (err) {

        console.error(
            "[TipsEngine] Erro ao carregar renda:",
            err
        );

    }

},

async carregarHistoricoFinanceiro() {

    try {

        const response = await fetch(
            '../php/get_historico.php',
            {
                credentials: 'same-origin'
            }
        );

        if (!response.ok)
            throw new Error('Erro HTTP');

            const json = await response.json();

            if (!json.success)
                return;

            this._historicoFinanceiro = json.dados_anuais;

    } catch (err) {

        console.error(
            '[TipsEngine] Erro ao carregar histórico:',
            err
        );

    }

},

async carregarGastos() {

    try {

        const response = await fetch(
            "../php/get_gastos.php",
            {
                credentials: "same-origin"
            }
        );

        if (!response.ok)
            throw new Error("Erro HTTP");

        const json = await response.json();

        if (!json.success)
            return;

        this._gastos = json.gastos;

    } catch (err) {

        console.error(
            "[TipsEngine] Erro ao carregar gastos:",
            err
        );

    }

},

  // Getters

  getPerfil() {

    return this._perfilCognitivo;

  },

  getHistoricoPerfil() {

    return this._historicoPerfil;

  },

  possuiPerfil() {

    return this._perfilCognitivo !== null;

  },

  limparCacheIA() {

    this._cacheIA.clear();

  },


// Status dos gastos

STATUS: {

    excelente: {
        icon: '🟢',
        label: 'Excelente',
        color: '#2ecc71'
    },

    bom: {
        icon: '🔵',
        label: 'Bom',
        color: '#3498db'
    },

    atencao: {
        icon: '🟡',
        label: 'Atenção',
        color: '#f1c40f'
    },

    critico: {
        icon: '🔴',
        label: 'Crítico',
        color: '#e74c3c'
    }

},

// Limites das categorias (% da renda)

LIMITES: {

    alimentacao: 20,
    moradia: 35,
    transporte: 15,
    saude: 15,
    educacao: 35,
    lazer: 15,
    compras: 10,
    tecnologia: 12,
    assinaturas: 8,
    servicos: 12,
    impostos: 20,
    investimentos: 30,
    animais: 10,
    presentes: 8,
    contas: 25,
    outros: 15

},

// Informações do status

getStatusInfo(status) {

    return this.STATUS[status] || this.STATUS.bom;

},

// Nível

getNivel(status) {

    switch (status) {

        case 'excelente':
            return 1;

        case 'bom':
            return 2;

        case 'atencao':
            return 3;

        case 'critico':
            return 4;

        default:
            return 2;

    }

},

// Fator de equivalência familiar
// Usa a escala de raiz quadrada: a renda é ajustada pela raiz do número de moradores,
// já que parte dos custos (moradia, contas) é compartilhada.
// 1 pessoa = 1,00 | 2 = 1,41 | 4 = 2,00 | 9 = 3,00

getFatorEquivalenciaFamiliar() {

    const { numPessoas } = StateManager.get();

    const pessoas = Math.max(1, Number(numPessoas) || 1);

    return Math.sqrt(pessoas);

},

// Calcula o status do gasto

calculateStatus(categoria, valor, renda) {

    valor = Number(valor) || 0;
    renda = Number(renda) || 0;

    if (renda <= 0)
        return 'bom';

    const limite = this.LIMITES[categoria] || this.LIMITES.outros;

    // Renda ajustada pelo tamanho da casa: quanto mais moradores,
    // maior a margem "normal" de gasto em termos absolutos.
    const rendaAjustada = renda * this.getFatorEquivalenciaFamiliar();

    const percentual = (valor / rendaAjustada) * 100;

    // Até metade do limite
    if (percentual <= limite * 0.50)
        return 'excelente';

    // Até o limite
    if (percentual <= limite)
        return 'bom';

    // Até 30% acima do limite
    if (percentual <= limite * 1.30)
        return 'atencao';

    // Muito acima do limite
    return 'critico';

},

  // Limite da categoria

  getLimiteCategoria(categoria) {

    return this.LIMITES[categoria] || this.LIMITES.outros;

  },

  // Verifica se a categoria existe

  categoriaExiste(categoria) {

    return categoria in this.LIMITES;

  },


  // Dicas inteligentes (IA com alternativa local)
setRegra503020(regra) {

    this._regra503020 = regra;

},

  async getTipsInteligentes(gasto, nivel) {

    try {

      // Se não houver gasto válido, usa o sistema atual
      if (!gasto) {
        return this.getTips('outros', 1);
      }

      // Perfil cognitivo carregado anteriormente
      const perfil = this.getPerfil();

      // Chave para cache
      const cacheKey = JSON.stringify({
        categoria: gasto.categoria,
        nivel,
        valor: Number(gasto.valor || 0),
        perfil: perfil?.vies_predominante || 'nenhum'
      });

      // Evita chamar IA repetidamente para o mesmo gasto
      if (this._cacheIA.has(cacheKey)) {
        return this._cacheIA.get(cacheKey);
      }

      // Tenta obter resposta da IA
      const dicasIA = await this._buscarDicasIA(gasto, nivel);

      if (
        Array.isArray(dicasIA) &&
        dicasIA.length > 0
      ) {

        this._cacheIA.set(cacheKey, dicasIA);

        return dicasIA;

      }

    } catch (err) {

      console.warn(
        '[TipsEngine] Falha ao obter dicas da IA:',
        err
      );

    }

    // Caso a IA falhe, usa o sistema atual
    return this.getTips(
      gasto.categoria,
      nivel
    );

  },

  // Busca dicas personalizadas

async _buscarDicasIA(gasto, nivel) {

  try {

    const response = await fetch('http://localhost:3001/tipsIA', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
body: JSON.stringify({

    categoria: gasto.categoria,
    descricao: gasto.descricao,
    valor: Number(gasto.valor),

    renda: this._rendaMensal,

    percentual: gasto.percentual || 0,

    status: gasto.status,

   perfil: this.getPerfil(),

regra503020: this._regra503020,

historicoFinanceiro: this._historicoFinanceiro,

gastosMes: this._gastos

})

    });

    if (!response.ok)
      throw new Error('Erro HTTP');

    const json = await response.json();

    if (
      json &&
      Array.isArray(json.tips)
    ) {

      return json.tips;

    }

  } catch (err) {

    console.error(
      '[TipsEngine] Erro IA:',
      err
    );

  }

  return null;

},
// Banco de dicas locais (usado quando a IA está indisponível)

tips: {

  alimentacao: {
    nivel1: [
      '💡 Prepare refeições em casa — economize até 60% comparado a delivery.',
      '📋 Faça uma lista de compras detalhada antes de ir ao mercado e siga ela à risca.',
      '🛒 Prefira produtos de marca própria — qualidade similar, preço significativamente menor.',
      '🍎 Compre frutas e verduras da época: mais baratas, frescas e nutritivas.',
      '🥘 Cozinhe em maior quantidade e congele porções individuais para a semana toda.'
    ],

    nivel2: [
      '⚠️ Seus gastos com alimentação estão acima do recomendado (meta: até 20% da renda).',
      '📊 Pesquise preços em diferentes supermercados e aproveite promoções semanais.',
      '🏪 Atacados e feiras livres costumam ser 20–40% mais baratos que supermercados comuns.',
      '🍽️ Limite refeições fora de casa a no máximo 2 vezes por semana.',
      '📱 Use aplicativos de cashback (Méliuz, Beblue) e cupons de desconto antes de comprar.'
    ],

    nivel3: [
      '🚨 CRÍTICO: alimentação consumindo mais de 30% da renda — ação urgente necessária!',
      '⛔ Suspenda delivery e restaurantes imediatamente até reequilibrar o orçamento.',
      '🥘 Priorize alimentos básicos e nutritivos: arroz, feijão, ovos, frango, legumes.',
      '📅 Defina um orçamento semanal fixo em dinheiro — retire o valor e não extrapole.',
      '💡 Considere uma pequena horta em casa para verduras e temperos: custo quase zero.'
    ]
  },

  energia: {

    nivel1: [
      '💡 Desligue luzes e aparelhos sempre que sair do cômodo — hábito simples, economia real.',
      '🌡️ Ar-condicionado a 24 °C consome até 20% menos que a 20 °C.',
      '🔦 Substitua todas as lâmpadas por LED — duram 15× mais e consomem 80% menos.',
      '🌞 Aproveite a luz natural durante o dia; abra cortinas antes de ligar luzes.',
      '🔌 Retire aparelhos da tomada quando não estiver usando — o modo standby consome.'
    ],

    nivel2: [
      '⚠️ Conta de energia acima do esperado para o seu perfil.',
      '❄️ Ar-condicionado pode representar até 30% da conta — use com timer e limpe os filtros.',
      '🌡️ Configure desligamento automático durante a madrugada e no trabalho.',
      '🔍 Verifique se há aparelhos velhos e ineficientes: geladeiras antigas são vilãs.',
      '💻 Desligue computadores e monitores completamente — não use apenas o modo sleep.'
    ],

    nivel3: [
      '🚨 Consumo de energia em nível crítico — verifique possível vazamento ou erro de leitura.',
      '📞 Solicite vistoria gratuita da distribuidora de energia da sua região.',
      '🔧 Faça manutenção preventiva em geladeira e ar-condicionado (gás, filtros, vedações).',
      '☀️ Pesquise financiamento de energia solar — em média, o retorno ocorre em 4–6 anos.',
      '📊 Instale um medidor de consumo individual por tomada para identificar o vilão.'
    ]
  },

  transporte: {

    nivel1: [
      '🚶 Caminhe ou use bicicleta para trajetos de até 5 km — saúde + economia.',
      '🚌 Transporte público custa em média 3–4× menos que carro ou app por km rodado.',
      '⛽ Combine destinos num único percurso para reduzir combustível e desgaste.',
      '🚗 Calibre os pneus mensalmente — pneus murchos aumentam consumo em até 8%.',
      '🅿️ Planeje a rota com antecedência para evitar estacionamentos pagos e multas.'
    ],

    nivel2: [
      '⚠️ Gastos com transporte consumindo mais do que o recomendado (meta: até 15% da renda).',
      '🚕 Reduza o uso de apps de corrida para situações sem alternativa.',
      '🚴 Carpooling com colegas de trabalho pode cortar seus custos pela metade.',
      '📍 Avalie se morar mais perto do trabalho reduziria seu custo total de vida.',
      '🏍️ Para quem precisa de veículo, moto consome ~3× menos que carro.'
    ],

    nivel3: [
      '🚨 Transporte em nível crítico — comprometendo outras áreas essenciais do orçamento.',
      '📍 Considere seriamente mudança de residência para reduzir deslocamento.',
      '🚲 Investir numa bicicleta boa se paga em poucos meses de economia.',
      '💼 Negocie home office parcial (2–3 dias/semana) com seu empregador.',
      '🚇 Compre passes mensais ou VT negociado — sempre mais barato que pagar avulso.'
    ]
  },

  lazer: {

    nivel1: [
      '🎮 Reserve um valor fixo mensal para lazer e evite gastos impulsivos.',
      '🌳 Aproveite parques, praças e eventos gratuitos da sua cidade.',
      '🎬 Procure promoções de cinema, teatro e streaming compartilhado.',
      '📅 Planeje passeios com antecedência para evitar gastos inesperados.',
      '👨‍👩‍👧 Momentos simples com família e amigos também são lazer.'
    ],

    nivel2: [
      '⚠️ Seus gastos com lazer estão acima do recomendado.',
      '💰 Defina um teto mensal e acompanhe os gastos semanalmente.',
      '🍕 Evite sair todos os finais de semana.',
      '🎟️ Aproveite programas gratuitos ou com desconto.',
      '📱 Cancele assinaturas que você quase não utiliza.'
    ],

    nivel3: [
      '🚨 Lazer está comprometendo seu orçamento.',
      '⛔ Suspenda temporariamente gastos não essenciais.',
      '📉 Priorize reorganizar sua vida financeira.',
      '🎯 Estabeleça uma meta antes de voltar aos gastos com entretenimento.',
      '💡 Lazer não precisa significar gastar dinheiro.'
    ]
  },

  saude: {

    nivel1: [
      '💊 Continue cuidando da sua saúde preventiva.',
      '🏃 Exercícios físicos ajudam a reduzir gastos futuros.',
      '🥗 Alimentação equilibrada evita despesas médicas.',
      '😴 Dormir bem também faz parte do investimento em saúde.',
      '🚶 Caminhadas são gratuitas e trazem muitos benefícios.'
    ],

    nivel2: [
      '⚠️ Avalie se todos os gastos com saúde são realmente necessários.',
      '💊 Compare preços de medicamentos em diferentes farmácias.',
      '🏥 Utilize programas de desconto quando disponíveis.',
      '🩺 Mantenha exames preventivos em dia.',
      '📋 Organize receitas e tratamentos para evitar desperdícios.'
    ],

    nivel3: [
      '🚨 Seus gastos com saúde estão elevados.',
      '👨‍⚕️ Verifique possibilidade de atendimento pelo SUS ou convênio.',
      '💊 Consulte medicamentos genéricos quando possível.',
      '📊 Organize todos os gastos médicos para identificar excessos.',
      '⚕️ Nunca interrompa tratamentos por questões financeiras sem orientação médica.'
    ]
  },

  educacao: {

    nivel1: [
      '📚 Continue investindo no seu desenvolvimento.',
      '🎓 Cursos gratuitos podem complementar sua formação.',
      '📖 Leia livros sobre educação financeira.',
      '💻 Aproveite plataformas gratuitas de aprendizado.',
      '🎯 Educação costuma gerar retorno no longo prazo.'
    ],

    nivel2: [
      '⚠️ Avalie se todos os cursos contratados estão sendo utilizados.',
      '📚 Compare plataformas antes de assinar.',
      '🎓 Procure bolsas e descontos.',
      '💻 Priorize cursos com maior retorno profissional.',
      '📋 Organize um plano anual de estudos.'
    ],

    nivel3: [
      '🚨 Gastos elevados com educação.',
      '📉 Priorize formações realmente importantes.',
      '💡 Utilize conteúdos gratuitos temporariamente.',
      '🎓 Negocie mensalidades quando possível.',
      '📚 Evite comprar cursos por impulso.'
    ]
  },

  contas: {

    nivel1: [
      '📄 Continue pagando suas contas em dia.',
      '📅 Utilize débito automático para evitar multas.',
      '💡 Revise mensalmente seus contratos.',
      '📱 Organize vencimentos em um calendário.',
      '💰 Pequenas economias fazem diferença no longo prazo.'
    ],

    nivel2: [
      '⚠️ Suas despesas fixas estão aumentando.',
      '📞 Negocie planos de internet e telefone.',
      '📺 Revise assinaturas pouco utilizadas.',
      '⚡ Procure reduzir consumo de água e energia.',
      '📋 Analise contratos anualmente.'
    ],

    nivel3: [
      '🚨 Contas fixas comprometendo seu orçamento.',
      '📞 Renegocie imediatamente contratos.',
      '💳 Priorize quitar contas com juros maiores.',
      '📉 Elimine serviços desnecessários.',
      '📊 Faça um plano de redução de despesas fixas.'
    ]
  },

  compras: {

    nivel1: [
      '🛒 Sempre faça uma lista antes de comprar.',
      '⏳ Espere 24 horas antes de realizar compras por impulso.',
      '💰 Compare preços em diferentes lojas.',
      '🏷️ Aproveite promoções apenas quando realmente precisar.',
      '📦 Evite comprar apenas porque está em promoção.'
    ],

    nivel2: [
      '⚠️ Seus gastos com compras estão aumentando.',
      '💳 Evite parcelamentos desnecessários.',
      '🛍️ Defina um orçamento mensal para compras.',
      '📊 Analise se cada compra é necessidade ou desejo.',
      '📱 Utilize comparadores de preço antes de finalizar.'
    ],

    nivel3: [
      '🚨 Compras impulsivas podem comprometer seu orçamento.',
      '⛔ Suspenda compras não essenciais por alguns dias.',
      '📉 Reveja seus hábitos de consumo.',
      '🎯 Estabeleça metas antes de comprar.',
      '💡 Priorize apenas necessidades.'
    ]
  },

  investimentos: {

    nivel1: [
      '📈 Excelente! Continue investindo regularmente.',
      '💰 Mantenha sua reserva de emergência.',
      '📊 Diversifique seus investimentos.',
      '📚 Continue estudando educação financeira.',
      '🎯 Invista pensando no longo prazo.'
    ],

    nivel2: [
      '⚠️ Avalie se sua carteira está diversificada.',
      '📉 Evite investir apenas por recomendações.',
      '🧠 Tome decisões baseadas em estratégia.',
      '📊 Revise seus objetivos financeiros.',
      '💼 Faça aportes constantes sempre que possível.'
    ],

    nivel3: [
      '🚨 Evite investir movido pela emoção.',
      '🛑 Não tente recuperar perdas rapidamente.',
      '📚 Estude antes de realizar novos investimentos.',
      '💡 Reavalie seu perfil de risco.',
      '🎯 Priorize estabilidade financeira.'
    ]
  },

  outros: {

    nivel1: [
      '💡 Continue acompanhando seus gastos regularmente.',
      '📊 Registrar despesas ajuda no controle financeiro.',
      '📅 Revise seu orçamento todos os meses.',
      '🎯 Pequenos ajustes geram grandes resultados.',
      '💰 Criar bons hábitos vale mais que grandes cortes.'
    ],

    nivel2: [
      '⚠️ Alguns gastos podem estar passando despercebidos.',
      '📋 Revise seu extrato bancário com frequência.',
      '📊 Identifique despesas recorrentes.',
      '💡 Procure oportunidades de economia.',
      '🎯 Organize melhor suas prioridades.'
    ],

    nivel3: [
      '🚨 Seu orçamento precisa de atenção.',
      '📉 Reduza gastos não essenciais.',
      '💰 Priorize despesas importantes.',
      '📊 Faça um planejamento financeiro.',
      '🎯 Reavalie seus objetivos financeiros.'
    ]
  }

},

// Dicas locais (usadas quando a IA está indisponível)

getTips(categoria, nivel = 'nivel1') {

    if (!categoria) {
        categoria = 'outros';
    }

    // Normaliza categoria
    categoria = String(categoria)
        .toLowerCase()
        .trim();

    // Caso não exista a categoria
    if (!this.tips[categoria]) {
        categoria = 'outros';
    }

    // Garante um nível válido
    if (!['nivel1', 'nivel2', 'nivel3'].includes(nivel)) {
        nivel = 'nivel1';
    }

    const dicas = this.tips[categoria]?.[nivel];

    if (!Array.isArray(dicas) || dicas.length === 0) {
        return this.tips.outros.nivel1;
    }

    // Embaralha as dicas para não repetir sempre
    const embaralhadas = [...dicas].sort(() => Math.random() - 0.5);

    // Retorna até 3 dicas
    return embaralhadas.slice(0, 3);

}

};