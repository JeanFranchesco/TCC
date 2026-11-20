"use strict";

// ═══════════════════════════════════════════════════════
// FinançasJá
// Servidor de Dicas Inteligentes
// v1.0
// ═══════════════════════════════════════════════════════

const express = require("express");
const axios   = require("axios");
const cors    = require("cors");

// ═══════════════════════════════════════════════════════
// CONFIGURAÇÕES
// ═══════════════════════════════════════════════════════

const CONFIG = {

    PORT: process.env.TIPS_PORT || 3001,

    OLLAMA_URL:
        process.env.OLLAMA_URL ||
        "http://localhost:11434/api/chat",

    MODEL_PRIMARY:
        process.env.MODEL_PRIMARY ||
        "gpt-oss:120b-cloud",

    MODEL_FALLBACK:
        process.env.MODEL_FALLBACK ||
        "mistral",

    TIMEOUT_MS: 25000,

    FALLBACK_TIMEOUT: 20000,

    TEMPERATURE: 0.35,

    TOP_P: 0.90,

    MAX_TOKENS: 2000

};

// ═══════════════════════════════════════════════════════
// EXPRESS
// ═══════════════════════════════════════════════════════

const app = express();

app.use(cors());

app.use(express.json({
    limit: "50kb"
}));

// ═══════════════════════════════════════════════════════
// LOGGER
// ═══════════════════════════════════════════════════════

const ICONS = {

    info: "ℹ️",
    ok: "✅",
    warn: "⚠️",
    error: "❌",
    ai: "🤖"

};

function log(tipo, mensagem, extra = "") {

    const hora = new Date().toLocaleTimeString(
        "pt-BR",
        {
            hour12: false
        }
    );

    console.log(
        `[${hora}] ${ICONS[tipo] || "•"} ${mensagem}`,
        extra
    );

}

// ═══════════════════════════════════════════════════════
// STATUS DO SERVIDOR
// ═══════════════════════════════════════════════════════

app.get("/health", (req, res) => {

    res.json({

        success: true,

        servidor: "Tips IA",

        versao: "1.0",

        modeloPrimario: CONFIG.MODEL_PRIMARY,

        modeloFallback: CONFIG.MODEL_FALLBACK,

        timestamp: new Date().toISOString()

    });

});
// ═══════════════════════════════════════════════════════
// PROMPT INTELIGENTE
// ═══════════════════════════════════════════════════════

function montarPrompt(dados = {}) {

const {
    categoria = "Outros",
    descricao = "",
    valor = 0,
    renda = 0,
    percentual = 0,
    status = "Normal",
    perfil = {},
    regra503020 = {},
    historicoFinanceiro = [],
    gastosMes = []
} = dados;

const perfilSeguro = perfil || {};

const gastosTexto = gastosMes.length
    ? gastosMes
        .map(gasto => {

            const categoria =
                gasto.categoria_nome ||
                gasto.categoria ||
                "Sem categoria";

return `
• Categoria: ${categoria}
  Descrição: ${gasto.descricao}
  Valor: R$ ${Number(gasto.valor).toFixed(2)}
`;

        })
        .join("\n")
    : "Nenhum gasto encontrado neste mês.";

const vies =
    perfilSeguro.vies_predominante ||
    perfilSeguro.vies ||
    "Não identificado";

const intensidade =
    perfilSeguro.intensidade ||
    perfilSeguro.percentual ||
    0;

const nivel =
    perfilSeguro.nivel ||
    "Não informado";

const historicoTexto = (historicoFinanceiro || [])
    .filter(item => item.receitas > 0 || item.gastos > 0)
    .map(item => {

        const meses = [
            "Janeiro","Fevereiro","Março","Abril","Maio","Junho",
            "Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"
        ];

        return `${meses[item.mes-1]}
Receitas: R$ ${item.receitas}
Gastos: R$ ${item.gastos}`;

    })
    .join("\n\n");

return `
Você é a Inteligência Artificial oficial do sistema Finanças Já.

Sua função é analisar o comportamento financeiro do usuário utilizando EXCLUSIVAMENTE as informações registradas no próprio sistema.

Você faz parte do Finanças Já.

Você NÃO é um consultor financeiro genérico.

Seu objetivo é gerar recomendações altamente personalizadas utilizando o maior número possível de informações disponíveis.

==================================================================
REGRAS ABSOLUTAS
==================================================================

• Responda SEMPRE em português do Brasil.

• Nunca utilize inglês.

• Nunca utilize Markdown.

• Nunca escreva comentários.

• Nunca escreva explicações.

• Nunca escreva texto fora do JSON.

• Nunca invente informações.

• Nunca suponha fatos que não foram informados.

• Utilize SOMENTE os dados fornecidos.

==================================================================
REGRA 50/30/20
==================================================================

Necessidades:
${regra503020.necessidades ?? 0}%

Desejos:
${regra503020.desejos ?? 0}%

Poupança:
${regra503020.poupanca ?? 0}%

Status:
${regra503020.status ?? "Não informado"}

Interprete esses dados.

Caso exista:

• excesso de necessidades;

• excesso de desejos;

• baixa poupança;

utilize essas informações durante toda a análise.

==================================================================
HISTÓRICO FINANCEIRO
==================================================================

${historicoTexto || "Histórico indisponível."}

==================================================================
USO OBRIGATÓRIO DO HISTÓRICO
==================================================================

Sempre analise o histórico financeiro.

Identifique possíveis padrões como:

• crescimento dos gastos;

• redução dos gastos;

• estabilidade financeira;

• oscilações de receitas;

• meses com maior consumo;

• meses com menor consumo;

• mudanças relevantes de comportamento.

SE existir qualquer padrão relevante, pelo menos UMA recomendação DEVE utilizá-lo.

Caso não exista nenhum padrão relevante, ignore esta seção.

Nunca invente tendências.

==================================================================
GASTOS DO MÊS
==================================================================

${gastosTexto}

Utilize esta lista para compreender o contexto financeiro atual.

Observe:

• categorias mais frequentes;

• gastos recorrentes;

• concentração de despesas;

• relação entre o novo gasto e os demais;

• padrões de consumo.

Caso exista algum padrão relevante, utilize-o em pelo menos UMA recomendação.

Caso contrário, ignore esta seção.

Nunca invente informações.

==================================================================
DADOS DO GASTO
==================================================================

Categoria:
${categoria}

Descrição:
${descricao}

Valor:
R$ ${Number(valor).toFixed(2)}

Renda mensal:
R$ ${Number(renda).toFixed(2)}

Comprometimento da renda:
${Number(percentual).toFixed(1)}%

Status financeiro:
${status}

==================================================================
PERFIL COGNITIVO
==================================================================

Viés predominante:
${vies}

Intensidade:
${intensidade}%

Nível:
${nivel}

==================================================================
COMO ANALISAR
==================================================================

A análise deve utilizar TODAS as informações relevantes disponíveis.

Prioridade da análise:

1. Descrição do gasto.

2. Histórico financeiro.

3. Gastos do mês.

4. Perfil cognitivo.

5. Regra 50/30/20.

6. Categoria.

7. Valor.

8. Renda.

9. Status financeiro.

Nunca produza recomendações baseadas apenas em um único dado quando existirem outras informações relevantes.

Sempre combine duas ou mais informações quando possível.

==================================================================
IDENTIFICAÇÃO DO GASTO
==================================================================

Antes de responder determine mentalmente:

• O gasto é obrigatório ou opcional?

• O gasto é recorrente ou eventual?

• O usuário possui controle sobre esse gasto?

• Existem informações suficientes para sugerir economia?

• Existem informações suficientes para sugerir mudanças?

Caso alguma resposta seja negativa, não invente alternativas.

==================================================================
DESPESAS OBRIGATÓRIAS
==================================================================

Caso a descrição indique despesas como:

• aluguel;

• condomínio;

• financiamento;

• mensalidade;

• energia;

• água;

• internet;

• medicamentos;

• plano de saúde;

• alimentação essencial;

• transporte para trabalho;

• transporte para faculdade;

trate esse gasto como obrigatório.

Nesse caso priorize:

• organização financeira;

• planejamento;

• prevenção;

• estabilidade financeira;

• criação de hábitos financeiros.

NÃO recomende:

• eliminar o gasto;

• trocar fornecedor;

• negociar desconto;

• procurar concorrentes;

• dividir custos;

• pegar carona;

• trocar serviço;

exceto quando isso estiver claramente indicado pela descrição.

==================================================================
USO DO PERFIL COGNITIVO
==================================================================

Caso exista perfil cognitivo identificado:

• adapte TODAS as recomendações;

• nunca apenas cite o nome do viés;

• explique naturalmente o comportamento;

• ajude o usuário a perceber padrões;

• incentive pequenas mudanças de comportamento;

• priorize mudanças comportamentais antes de economia.

==================================================================
OBJETIVO
==================================================================

Seu objetivo é desenvolver melhores hábitos financeiros ao longo do tempo.

As recomendações devem incentivar:

• organização financeira;

• planejamento;

• prevenção;

• equilíbrio financeiro;

• decisões conscientes;

• redução de desperdícios;

• combate aos vieses cognitivos.

==================================================================
PROIBIDO
==================================================================

Nunca recomendar:

• aplicativos;

• planilhas;

• registrar gastos;

• controlar despesas manualmente;

• criar planilhas;

• criar orçamento manual;

• baixar outro sistema.

Esses recursos já fazem parte do Finanças Já.

==================================================================
QUALIDADE DAS RECOMENDAÇÕES
==================================================================

As cinco recomendações devem possuir objetivos diferentes.

Obrigatoriamente:

1ª → Organização financeira.

2ª → Planejamento.

3ª → Finanças comportamentais.

4ª → Prevenção.

5ª → Reflexão antes da próxima decisão financeira.

Além disso:

• pelo menos UMA recomendação deve utilizar o histórico financeiro quando houver padrão relevante;

• pelo menos UMA recomendação deve utilizar os gastos do mês quando houver padrão relevante;

• pelo menos UMA recomendação deve utilizar o perfil cognitivo;

• pelo menos UMA recomendação deve utilizar a regra 50/30/20 quando ela indicar atenção ou situação crítica;

• sempre que possível combine duas ou mais informações na mesma recomendação.

Cada recomendação deve:

• possuir apenas UMA frase;

• possuir no máximo 25 palavras;

• ser personalizada;

• utilizar dados reais fornecidos;

• fazer sentido para aquele gasto;

• evitar repetir ideias.

==================================================================
CASOS COM POUCA INFORMAÇÃO
==================================================================

Caso a descrição não permita compreender totalmente o gasto:

• não invente;

• não suponha;

• faça recomendações conservadoras;

• utilize apenas os dados disponíveis.

==================================================================
RESPOSTA
==================================================================

Retorne SOMENTE um JSON válido.

Formato obrigatório:

{
  "tips": [
    "...",
    "...",
    "...",
    "...",
    "..."
  ]
}

Não escreva nenhum texto antes.

Não escreva nenhum texto depois.

Somente JSON.
`;

}
// ═══════════════════════════════════════════════════════
// CONVERSOR DE RESPOSTA
// ═══════════════════════════════════════════════════════

function extrairDicas(respostaIA) {

    if (!respostaIA)
        return null;

    try {

        const json = JSON.parse(respostaIA);

        if (
            json &&
            Array.isArray(json.tips) &&
            json.tips.length > 0
        ) {

            return json.tips
                .map(t => String(t).trim())
                .filter(Boolean);

        }

    }
    catch (erro) {

        log(
            "warn",
            "Resposta da IA não veio em JSON."
        );

    }

    return null;

}
// ═══════════════════════════════════════════════════════
// COMUNICAÇÃO COM O OLLAMA
// ═══════════════════════════════════════════════════════

async function chamarOllama(model, prompt, timeout) {

    const { data } = await axios.post(

        CONFIG.OLLAMA_URL,

        {

            model,

            messages: [

                {
                    role: "user",
                    content: prompt
                }

            ],

            stream: false,

            options: {

                temperature: CONFIG.TEMPERATURE,

                top_p: CONFIG.TOP_P,

                num_predict: CONFIG.MAX_TOKENS

            }

        },

        {

            timeout

        }

    );

    return data?.message?.content?.trim() || "";

}

// ═══════════════════════════════════════════════════════
// GERAÇÃO DAS DICAS
// ═══════════════════════════════════════════════════════

async function gerarDicasIA(dados) {

    console.log("\n========== DADOS RECEBIDOS ==========\n");
    console.log(JSON.stringify(dados, null, 2));

    const prompt = montarPrompt(dados);

    console.log("\n========== PROMPT ==========\n");
    console.log(prompt);

    log(
        "ai",
        `Gerando dicas para categoria "${dados.categoria}"`
    );

    // ============================
    // MODELO PRINCIPAL
    // ============================

    try {

        const resposta = await chamarOllama(

            CONFIG.MODEL_PRIMARY,

            prompt,

            CONFIG.TIMEOUT_MS

        );

        const dicas = extrairDicas(resposta);

        if (dicas && dicas.length > 0) {

            log(
                "ok",
                "Resposta obtida pelo modelo principal."
            );

            return {

                success: true,

                source: "primary",

                tips: dicas

            };

        }

        log(
            "warn",
            "Modelo principal respondeu, porém o JSON era inválido."
        );

    }

    catch (erro) {

        log(
            "warn",
            "Falha no modelo principal.",
            erro.message
        );

    }

    // ============================
    // FALLBACK
    // ============================

    try {

        const resposta = await chamarOllama(

            CONFIG.MODEL_FALLBACK,

            prompt,

            CONFIG.FALLBACK_TIMEOUT

        );

        const dicas = extrairDicas(resposta);

        if (dicas && dicas.length > 0) {

            log(
                "ok",
                "Resposta obtida pelo modelo de fallback."
            );

            return {

                success: true,

                source: "fallback",

                tips: dicas

            };

        }

    }

    catch (erro) {

        log(
            "error",
            "Fallback também falhou.",
            erro.message
        );

    }

    // ============================
    // IA INDISPONÍVEL
    // ============================

    return {

        success: false,

        source: "local",

        tips: []

    };

}
// ═══════════════════════════════════════════════════════
// ROTA DE DICAS INTELIGENTES
// ═══════════════════════════════════════════════════════

app.post("/tipsIA", async (req, res) => {

    try {

const {
    categoria,
    descricao,
    valor,
    renda,
    percentual,
    status,
    perfil,
    regra503020,
    historicoFinanceiro
} = req.body;

        // =====================================
        // VALIDAÇÕES
        // =====================================

        if (!categoria) {

            return res.status(400).json({

                success: false,

                message: "Categoria não informada."

            });

        }

        if (valor === undefined || valor === null) {

            return res.status(400).json({

                success: false,

                message: "Valor do gasto não informado."

            });

        }

        log(

            "info",

            `Solicitação recebida (${categoria})`

        );

        // =====================================
        // GERA DICAS
        // =====================================

const resultado = await gerarDicasIA({

    categoria,
    descricao,
    valor,
    renda,
    percentual,
    status,
    perfil,
    regra503020,
    historicoFinanceiro

});

        // =====================================
        // SUCESSO
        // =====================================

        if (resultado.success) {

            return res.json({

                success: true,

                source: resultado.source,

                tips: resultado.tips

            });

        }

        // =====================================
        // IA INDISPONÍVEL
        // =====================================

        return res.status(503).json({

            success: false,

            source: "local",

            message: "IA indisponível.",

            tips: []

        });

    }

    catch (erro) {

        log(

            "error",

            "Erro na rota /tipsIA",

            erro.message

        );

        return res.status(500).json({

            success: false,

            message: "Erro interno do servidor.",

            tips: []

        });

    }

});
// ═══════════════════════════════════════════════════════
// HEALTH CHECK
// ═══════════════════════════════════════════════════════

app.get("/health", (_req, res) => {

    res.json({

        success: true,

        servidor: "FinançasJá - Tips IA",

        versao: "1.0.0",

        modeloPrimario: CONFIG.MODEL_PRIMARY,

        modeloFallback: CONFIG.MODEL_FALLBACK,

        timestamp: new Date().toISOString()

    });

});

// ═══════════════════════════════════════════════════════
// ROTA NÃO ENCONTRADA
// ═══════════════════════════════════════════════════════

app.use((_req, res) => {

    res.status(404).json({

        success: false,

        message: "Rota não encontrada."

    });

});

// ═══════════════════════════════════════════════════════
// TRATAMENTO GLOBAL DE ERROS
// ═══════════════════════════════════════════════════════

app.use((erro, _req, res, _next) => {

    log(

        "error",

        "Erro interno",

        erro.message

    );

    res.status(500).json({

        success: false,

        message: "Erro interno do servidor."

    });

});

// ═══════════════════════════════════════════════════════
// INICIALIZAÇÃO
// ═══════════════════════════════════════════════════════

app.listen(CONFIG.PORT, () => {

    console.clear();

    console.log(`
╔══════════════════════════════════════════════════════╗
║          FinançasJá • Tips IA v1.0                  ║
╠══════════════════════════════════════════════════════╣
║ 🚀 Porta........: ${String(CONFIG.PORT).padEnd(28)}║
║ 🤖 Modelo.......: ${CONFIG.MODEL_PRIMARY.padEnd(28)}║
║ 🔄 Fallback.....: ${CONFIG.MODEL_FALLBACK.padEnd(28)}║
║ 🌡 Temperatura..: ${String(CONFIG.TEMPERATURE).padEnd(28)}║
║ 📡 Endpoint.....: /tipsIA                          ║
║ ❤️ Health.......: /health                          ║
╚══════════════════════════════════════════════════════╝
`);

});