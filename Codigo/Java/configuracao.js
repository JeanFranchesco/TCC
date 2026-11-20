// ==========================================================
// FINANÇAS JÁ
// CONFIGURAÇÃO CENTRAL DO SISTEMA
// Versão 4.0.0
// ==========================================================

export const CONFIG = {

    VERSION: "4.0.0",

    // armazenamento local
    STORAGE_KEY: "financas-gastos-v4",
    OLD_STORAGE_KEYS: [
        "financas-gastos-v3"
    ],

    THEME_KEY: "financas-theme",

    ANIMATION_DURATION: 600,
    TOAST_DURATION: 3500,


// ======================================================
// CATEGORIAS
// ======================================================

CATEGORIAS_DB: {

    "1": "Alimentação",
    "2": "Moradia",
    "3": "Transporte",
    "4": "Saúde",
    "5": "Educação",
    "6": "Lazer",
    "7": "Compras",
    "8": "Tecnologia",
    "9": "Assinaturas",
    "10": "Serviços",
    "11": "Impostos",
    "12": "Investimentos",
    "13": "Animais",
    "14": "Presentes",
    "15": "Outros"

},

// ======================================================
// ID -> SLUG
// ======================================================

CATEGORIA_SLUG_MAP: {

    "1": "alimentacao",
    "2": "moradia",
    "3": "transporte",
    "4": "saude",
    "5": "educacao",
    "6": "lazer",
    "7": "compras",
    "8": "tecnologia",
    "9": "assinaturas",
    "10": "servicos",
    "11": "impostos",
    "12": "investimentos",
    "13": "animais",
    "14": "presentes",
    "15": "outros"

},

// ======================================================
// SLUG -> ID
// ======================================================

CATEGORIA_ID_MAP: {

    alimentacao: "1",
    moradia: "2",
    transporte: "3",
    saude: "4",
    educacao: "5",
    lazer: "6",
    compras: "7",
    tecnologia: "8",
    assinaturas: "9",
    servicos: "10",
    impostos: "11",
    investimentos: "12",
    animais: "13",
    presentes: "14",
    outros: "15"

},


    // ======================================================
    // TIPOS DE GASTO
    // ======================================================

    TIPOS_DB: {

        fixo: "Fixo",
        variavel: "Variável"

    },


    FORMAS_PAGAMENTO_DB: {

        dinheiro: "Dinheiro",
        cartao: "Cartão",
        pix: "PIX",
        boleto: "Boleto",
        outros: "Outros"

    },


    // ======================================================
    // STATUS FINANCEIRO
    // ======================================================

    STATUS_DB: {


        excelente: {

            icon:"🟢",
            label:"Excelente",
            color:"#2ecc71"

        },


        bom: {

            icon:"🔵",
            label:"Bom",
            color:"#3498db"

        },


        atencao: {

            icon:"🟡",
            label:"Atenção",
            color:"#ffc107"

        },


        critico: {

            icon:"🔴",
            label:"Crítico",
            color:"#ff6b6b"

        }


    },



    // ======================================================
    // LIMITES POR CATEGORIA
    // Agora considerando quantidade de moradores
    // ======================================================


CATEGORY_LIMITS:{

    alimentacao:{
        bom:20,
        alerta:35
    },

    moradia:{
        bom:30,
        alerta:40
    },

    transporte:{
        bom:15,
        alerta:25
    },

    saude:{
        bom:10,
        alerta:20
    },

    educacao:{
        bom:15,
        alerta:25
    },

    lazer:{
        bom:10,
        alerta:20
    },

    compras:{
        bom:10,
        alerta:20
    },

    tecnologia:{
        bom:8,
        alerta:15
    },

    assinaturas:{
        bom:5,
        alerta:10
    },

    servicos:{
        bom:10,
        alerta:20
    },

    impostos:{
        bom:15,
        alerta:25
    },

    investimentos:{
        bom:20,
        alerta:40
    },

    animais:{
        bom:8,
        alerta:15
    },

    presentes:{
        bom:5,
        alerta:10
    },

    outros:{
        bom:8,
        alerta:15
    }

},



    // ======================================================
    // REGRA 50/30/20
    // ======================================================


CATEGORY_GROUPS:{

    necessidades:[

        "alimentacao",
        "moradia",
        "transporte",
        "saude",
        "educacao",
        "servicos",
        "impostos",
        "animais"

    ],

    desejos:[

        "lazer",
        "compras",
        "tecnologia",
        "assinaturas",
        "presentes",
        "outros"

    ]

},



    RULE_503020_LIMITS:{


        necessidades:50,

        desejos:30,

        poupanca:20


    },



    // ======================================================
    // CORES
    // ======================================================


CATEGORY_COLORS:{

    alimentacao:"#ff6600",

    moradia:"#795548",

    transporte:"#ffc107",

    saude:"#ff4d6d",

    educacao:"#3f51b5",

    lazer:"#51cf66",

    compras:"#9c27b0",

    tecnologia:"#00bcd4",

    assinaturas:"#607d8b",

    servicos:"#8bc34a",

    impostos:"#f44336",

    investimentos:"#009688",

    animais:"#ff9800",

    presentes:"#e91e63",

    outros:"#9c9ca4"

}



};