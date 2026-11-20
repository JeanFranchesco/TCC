<!-- Widget de chat do Finanças Já -->
<style>
/* Variáveis */
#fj-chat-root {
    --c-bg:        #0e0e12;
    --c-surface:   #13131a;
    --c-surface2:  #1a1a24;
    --c-border:    rgba(255,255,255,0.07);
    --c-border2:   rgba(255,255,255,0.13);
    --c-orange:    #ff9100;
    --c-orange2:   #ffb347;
    --c-text:      #f0f0f5;
    --c-text2:     #9898a8;
    --c-text3:     #5a5a70;
    --c-user-bg:   linear-gradient(135deg,#ff9100,#ffb347);
    --radius:      20px;
    font-family: 'DM Sans', 'Segoe UI', sans-serif;
}

/* Botão flutuante */
#fj-chat-toggle {
    position: fixed;
    bottom: 28px;
    right: 28px;
    width: 58px;
    height: 58px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    background: linear-gradient(135deg, var(--c-orange), var(--c-orange2));
    color: #000;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 28px rgba(255,145,0,0.35), 0 0 0 0 rgba(255,145,0,0.3);
    transition: transform 0.3s cubic-bezier(0.23,1,0.32,1),
                box-shadow 0.3s;
    z-index: 10000;
    animation: fj-pulse 3s ease-in-out infinite;
}
#fj-chat-toggle:hover {
    transform: translateY(-4px) scale(1.08);
    box-shadow: 0 16px 40px rgba(255,145,0,0.45);
    animation: none;
}
#fj-chat-toggle svg { width: 24px; height: 24px; flex-shrink: 0; }

@keyframes fj-pulse {
    0%,100% { box-shadow: 0 8px 28px rgba(255,145,0,0.35), 0 0 0 0 rgba(255,145,0,0.3); }
    50%      { box-shadow: 0 8px 28px rgba(255,145,0,0.35), 0 0 0 10px rgba(255,145,0,0); }
}

/* Janela do chat */
#fj-chat-window {
    position: fixed;
    bottom: 100px;
    right: 24px;
    width: 380px;
    height: 520px;
    background: var(--c-bg);
    border: 1px solid var(--c-border2);
    border-radius: var(--radius);
    box-shadow: 0 24px 64px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,145,0,0.06);
    display: none;
    flex-direction: column;
    z-index: 9999;
    overflow: hidden;
    transform: translateY(12px) scale(0.97);
    opacity: 0;
    transition: transform 0.35s cubic-bezier(0.23,1,0.32,1),
                opacity 0.3s ease;
}
#fj-chat-window.fj-open {
    display: flex;
    transform: translateY(0) scale(1);
    opacity: 1;
}

/* Cabeçalho */
#fj-chat-header {
    background: linear-gradient(135deg, #0f0f14, #1a1a24);
    border-bottom: 1px solid var(--c-border);
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}
.fj-header-avatar {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--c-orange), var(--c-orange2));
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(255,145,0,0.3);
}
.fj-header-avatar svg { width: 18px; height: 18px; color: #000; }
.fj-header-info { flex: 1; }
.fj-header-name {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--c-text);
    font-family: 'Syne', 'DM Sans', sans-serif;
    letter-spacing: 0.01em;
}
.fj-header-status {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 2px;
}
.fj-status-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 6px rgba(16,185,129,0.7);
    animation: fj-blink 2s ease-in-out infinite;
}
@keyframes fj-blink {
    0%,100% { opacity: 1; }
    50%      { opacity: 0.4; }
}
.fj-header-status span {
    font-size: 0.68rem;
    color: var(--c-text3);
    letter-spacing: 0.05em;
}
#fj-clear-btn {
    width: 30px; height: 30px;
    border-radius: 8px;
    border: 1px solid var(--c-border2);
    background: transparent;
    color: var(--c-text3);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    flex-shrink: 0;
}
#fj-clear-btn:hover { background: rgba(255,255,255,0.06); color: var(--c-text2); }
#fj-clear-btn svg { width: 14px; height: 14px; }

/* Mensagens */
#fj-chat-messages {
    flex: 1;
    padding: 18px 16px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: var(--c-bg);
    scroll-behavior: smooth;
}
#fj-chat-messages::-webkit-scrollbar { width: 4px; }
#fj-chat-messages::-webkit-scrollbar-track { background: transparent; }
#fj-chat-messages::-webkit-scrollbar-thumb { background: var(--c-border2); border-radius: 4px; }

/* Balões */
.fj-msg {
    max-width: 82%;
    padding: 10px 14px;
    border-radius: 16px;
    font-size: 0.855rem;
    line-height: 1.6;
    word-wrap: break-word;
    animation: fj-msgIn 0.3s cubic-bezier(0.23,1,0.32,1) both;
}
@keyframes fj-msgIn {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Bot */
.fj-msg.fj-bot {
    background: var(--c-surface2);
    border: 1px solid var(--c-border);
    color: var(--c-text);
    border-bottom-left-radius: 4px;
    align-self: flex-start;
}
.fj-msg.fj-bot p { margin: 0 0 6px; }
.fj-msg.fj-bot p:last-child { margin: 0; }
.fj-msg.fj-bot strong { color: var(--c-orange); font-weight: 600; }
.fj-msg.fj-bot code {
    background: rgba(255,145,0,0.1);
    color: var(--c-orange2);
    padding: 1px 6px;
    border-radius: 4px;
    font-size: 0.8rem;
}
.fj-msg.fj-bot ul, .fj-msg.fj-bot ol {
    padding-left: 18px;
    margin: 4px 0;
}

/* Usuário */
.fj-msg.fj-user {
    background: linear-gradient(135deg, var(--c-orange), var(--c-orange2));
    color: #000;
    border-bottom-right-radius: 4px;
    align-self: flex-end;
    font-weight: 500;
}

/* Indicador de digitação */
.fj-typing {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 12px 16px;
    background: var(--c-surface2);
    border: 1px solid var(--c-border);
    border-radius: 16px;
    border-bottom-left-radius: 4px;
    align-self: flex-start;
    max-width: 80px;
}
.fj-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: var(--c-text3);
    animation: fj-bounce 1.2s ease-in-out infinite;
}
.fj-dot:nth-child(2) { animation-delay: 0.2s; }
.fj-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes fj-bounce {
    0%,60%,100% { transform: translateY(0); background: var(--c-text3); }
    30%          { transform: translateY(-6px); background: var(--c-orange); }
}

/* Campo de entrada */
#fj-chat-input {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: var(--c-surface);
    border-top: 1px solid var(--c-border);
    flex-shrink: 0;
}
#fj-message {
    flex: 1;
    border: 1px solid var(--c-border2);
    padding: 10px 14px;
    border-radius: 12px;
    background: var(--c-surface2);
    color: var(--c-text);
    font-size: 0.875rem;
    font-family: inherit;
    outline: none;
    transition: border-color 0.2s;
    min-width: 0;
}
#fj-message::placeholder { color: var(--c-text3); }
#fj-message:focus { border-color: rgba(255,145,0,0.4); }

#fj-send-btn {
    width: 40px; height: 40px;
    border-radius: 11px;
    border: none;
    background: linear-gradient(135deg, var(--c-orange), var(--c-orange2));
    color: #000;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: transform 0.2s, box-shadow 0.2s, opacity 0.2s;
    box-shadow: 0 4px 12px rgba(255,145,0,0.25);
}
#fj-send-btn:hover  { transform: scale(1.08); box-shadow: 0 6px 18px rgba(255,145,0,0.35); }
#fj-send-btn:active { transform: scale(0.96); }
#fj-send-btn:disabled { opacity: 0.45; cursor: not-allowed; transform: none; }
#fj-send-btn svg { width: 17px; height: 17px; }

/* Divisor de data */
.fj-date-sep {
    text-align: center;
    font-size: 0.62rem;
    color: var(--c-text3);
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin: 4px 0;
}
</style>

<!-- Raiz do widget -->
<div id="fj-chat-root">

    <!-- Botão flutuante -->
    <button id="fj-chat-toggle" title="Abrir coach financeiro" aria-label="Abrir chat do coach financeiro" aria-expanded="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
    </button>

    <!-- Janela do chat -->
    <div id="fj-chat-window">

        <!-- Cabeçalho -->
        <div id="fj-chat-header">
            <div class="fj-header-avatar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                </svg>
            </div>
            <div class="fj-header-info">
                <div class="fj-header-name">Coach Financeiro IA</div>
                <div class="fj-header-status">
                    <div class="fj-status-dot"></div>
                    <span>Online · pronto para ajudar</span>
                </div>
            </div>
            <button id="fj-clear-btn" title="Limpar conversa">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/>
                </svg>
            </button>
        </div>

        <!-- Mensagens -->
        <div id="fj-chat-messages" role="log" aria-live="polite"></div>

        <!-- Campo de entrada -->
        <div id="fj-chat-input">
            <input type="text" id="fj-message" placeholder="Pergunte sobre finanças..." autocomplete="off" maxlength="500" aria-label="Digite sua mensagem">
            <button id="fj-send-btn" title="Enviar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
            </button>
        </div>

    </div>
</div><!-- /fj-chat-root -->

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3/dist/purify.min.js"></script>
<script>
if (!window.chatWidgetInitialized) {
    window.chatWidgetInitialized = true;

    /* Referências */
    let perfilCognitivo = null;
    const HISTORY_KEY = "chatHistory";
    // Renda e gastos são buscados no servidor a cada mensagem, sem depender do localStorage
    const ESTADO_STORAGE_KEY = "financas-gastos-v4"; // Mesma chave de CONFIG.STORAGE_KEY

    async function buscarEstadoFinanceiro() {
        try {
            const [rendaResp, gastosResp] = await Promise.all([
                fetch("../php/get_renda.php",  { credentials: "same-origin" }),
                fetch("../php/get_gastos.php", { credentials: "same-origin" })
            ]);

            const rendaJson  = rendaResp.ok  ? await rendaResp.json()  : null;
            const gastosJson = gastosResp.ok ? await gastosResp.json() : null;

            const renda = (rendaJson?.success)
                ? (Number(rendaJson.valor) || 0)
                : 0;

            const gastos = (gastosJson?.success && Array.isArray(gastosJson.gastos))
                ? gastosJson.gastos
                : [];

            return { renda, gastos };

        } catch (err) {
            // Sem conexão com o servidor, usa o localStorage como alternativa
            return lerEstadoFinanceiroLocal();
        }
    }

    function lerEstadoFinanceiroLocal() {
        try {
            const raw = localStorage.getItem(ESTADO_STORAGE_KEY);
            if (!raw) return { renda: 0, gastos: [] };
            const dados = JSON.parse(raw);
            return {
                renda:  Number(dados.renda) || 0,
                gastos: Array.isArray(dados.gastos) ? dados.gastos : []
            };
        } catch (err) {
            return { renda: 0, gastos: [] };
        }
    }

    // URL da API do chat; pode ser sobrescrita definindo window.CHAT_API_URL antes deste script
    const CHAT_API_URL = window.CHAT_API_URL
        || `${location.protocol}//${location.hostname}:3000/chat`;

    // Busca o perfil cognitivo do usuário (mesmo endpoint do motorDicas.js)
    async function carregarPerfilCognitivo() {
        try {
            const resp = await fetch("../php/buscar_perfil_cognitivo.php", {
                credentials: "same-origin"
            });
            if (!resp.ok) return;
            const json = await resp.json();
            if (json.success) perfilCognitivo = json.perfil ?? null;
        } catch (err) {
            // Sem perfil, o chat funciona normalmente
        }
    }
    carregarPerfilCognitivo();

    // Busca o histórico anual (totais por mês) uma única vez, ao carregar o chat
    let historicoAnual = null;
    let historicoAno   = new Date().getFullYear();

    async function carregarHistorico() {
        try {
            const resp = await fetch(`../php/get_historico.php?ano=${historicoAno}`, {
                credentials: "same-origin"
            });
            if (!resp.ok) return;
            const json = await resp.json();
            if (json.success) historicoAnual = json.dados_anuais ?? null;
        } catch (err) {
            // Sem histórico, o chat funciona normalmente
        }
    }
    carregarHistorico();

    // Busca a distribuição por categoria dos 3 últimos meses fechados, em paralelo, ao abrir o chat
    const QTD_MESES_CATEGORIA_HISTORICO = 3;
    let historicoCategoriasPorMes = [];

    async function carregarCategoriasHistorico() {
        const hoje  = new Date();
        const meses = [];

        for (let i = 1; i <= QTD_MESES_CATEGORIA_HISTORICO; i++) {
            const d = new Date(hoje.getFullYear(), hoje.getMonth() - i, 1);
            meses.push({ ano: d.getFullYear(), mes: d.getMonth() + 1 });
        }

        const respostas = await Promise.all(
            meses.map(({ ano, mes }) =>
                fetch(`../php/get_historico.php?ano=${ano}&mes=${mes}`, { credentials: "same-origin" })
                    .then(r => r.ok ? r.json() : null)
                    .catch(() => null)
            )
        );

        const resultado = [];

        respostas.forEach((json, i) => {
            const gastosMes = json?.success ? json.transacoes?.gastos : null;
            if (!Array.isArray(gastosMes) || !gastosMes.length) return;

            const categorias = {};
            gastosMes.forEach(g => {
                const valor = Number(g.valor);
                if (!Number.isFinite(valor) || valor <= 0) return;
                const nome = g.categoria_nome || g.categoria || "Outros";
                categorias[nome] = (categorias[nome] || 0) + valor;
            });

            if (Object.keys(categorias).length) {
                resultado.push({ ano: meses[i].ano, mes: meses[i].mes, categorias });
            }
        });

        historicoCategoriasPorMes = resultado;
    }
    carregarCategoriasHistorico();

    const chatWindow   = document.getElementById("fj-chat-window");
    const chatMessages = document.getElementById("fj-chat-messages");
    const input        = document.getElementById("fj-message");
    const sendBtn      = document.getElementById("fj-send-btn");
    const clearBtn     = document.getElementById("fj-clear-btn");
    const toggleBtn    = document.getElementById("fj-chat-toggle");

    let isSending = false;

    /* Histórico */
    function loadHistory() {
        chatMessages.innerHTML = "";
        let history = JSON.parse(localStorage.getItem(HISTORY_KEY)) || [];

        // Remove duplicatas consecutivas
        const clean = [];
        for (let i = 0; i < history.length; i++) {
            if (i === 0 || JSON.stringify(history[i]) !== JSON.stringify(history[i-1])) {
                clean.push(history[i]);
            }
        }
        clean.forEach(m => addMessage(m.text, m.isUser, false));
        localStorage.setItem(HISTORY_KEY, JSON.stringify(clean));
    }

    function saveToHistory(text, isUser) {
        let history = JSON.parse(localStorage.getItem(HISTORY_KEY)) || [];
        const last = history[history.length - 1];
        if (last && last.text === text && last.isUser === isUser) return;
        history.push({ text, isUser });
        if (history.length > 100) history.shift();
        localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
    }

    /* Renderização de mensagens */
    function addMessage(text, isUser = false, animate = true) {
        const msg = document.createElement("div");
        msg.className = `fj-msg ${isUser ? "fj-user" : "fj-bot"}`;
        if (!animate) msg.style.animation = "none";

        if (isUser) {
            msg.textContent = text;
        } else {
            const html = marked.parse(text);
            msg.innerHTML = window.DOMPurify ? DOMPurify.sanitize(html) : html;
        }

        chatMessages.appendChild(msg);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        return msg;
    }

    /* Indicador de digitação */
    function showTyping() {
        const t = document.createElement("div");
        t.className = "fj-typing";
        t.id = "fj-typing-indicator";
        t.innerHTML = '<div class="fj-dot"></div><div class="fj-dot"></div><div class="fj-dot"></div>';
        chatMessages.appendChild(t);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        return t;
    }

    /* Envio de mensagem */
    async function sendMessage(auto = null) {
        if (isSending) return;
        const message = (auto || input.value || "").trim();
        if (!message) return;

        isSending = true;
        sendBtn.disabled = true;
        input.disabled = true;

        if (!auto) {
            addMessage(message, true);
            saveToHistory(message, true);
            input.value = "";
        }

        const typing = showTyping();

        try {
            const state = await buscarEstadoFinanceiro();
            const contexto = {
                renda: state.renda,
                gastos: state.gastos,
                historico: historicoAnual,
                historicoAno: historicoAno,
                historicoCategorias: historicoCategoriasPorMes
            };

            let history = JSON.parse(localStorage.getItem(HISTORY_KEY)) || [];
            history = history.slice(-6);

            const controller = new AbortController();
            const timeoutId  = setTimeout(() => controller.abort(), 20000);

            const res = await fetch(CHAT_API_URL, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    message,
                    contexto,
                    perfilCognitivo,
                    history
                }),
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            const data = await res.json();
            typing.remove();
            const reply = data.reply || "Sem resposta no momento.";
            addMessage(reply);
            saveToHistory(reply, false);

        } catch (err) {
            typing.remove();
            const msg = err.name === "AbortError"
                ? "O servidor demorou muito para responder. Tente novamente."
                : "Erro ao conectar com o servidor. Verifique sua conexão.";
            addMessage(msg);
        } finally {
            isSending = false;
            sendBtn.disabled = false;
            input.disabled = false;
            input.focus();
        }
    }

    /* Abrir e fechar */
    toggleBtn.addEventListener("click", () => {
        const isOpen = chatWindow.classList.contains("fj-open");

        if (isOpen) {
            chatWindow.classList.remove("fj-open");
            toggleBtn.setAttribute("aria-expanded", "false");
            setTimeout(() => { chatWindow.style.display = "none"; }, 320);
        } else {
            chatWindow.style.display = "flex";
            toggleBtn.setAttribute("aria-expanded", "true");
            requestAnimationFrame(() => chatWindow.classList.add("fj-open"));

            if (chatMessages.children.length === 0) {
                setTimeout(() => {
                    addMessage("Olá! 👋 Sou seu **Coach Financeiro IA**.\n\nPosso te ajudar com análise de gastos, metas, investimentos e muito mais. Como posso te ajudar hoje?");
                }, 200);
            }
        }
    });

    /* Eventos */
    sendBtn.addEventListener("click", () => sendMessage());
    input.addEventListener("keypress", e => { if (e.key === "Enter" && !e.shiftKey) sendMessage(); });

    clearBtn.addEventListener("click", () => {
        if (confirm("Limpar toda a conversa?")) {
            localStorage.removeItem(HISTORY_KEY);
            chatMessages.innerHTML = "";
        }
    });

    /* Inicialização */
    loadHistory();
}
</script>