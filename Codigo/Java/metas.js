// Metas do mês

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // Elementos do DOM
    const btnCriar     = document.getElementById('btnCriar');
    const listaMetas   = document.getElementById('listaMetas');
    const rankingEl    = document.getElementById('ranking');
    const loading      = document.getElementById('loading');
    const mensagemEl   = document.getElementById('mensagem');
    const resumoEl     = document.getElementById('resumoMes');

    if (!btnCriar || !listaMetas || !rankingEl || !loading || !mensagemEl || !resumoEl) {
        console.error('❌ Algum elemento do DOM não foi encontrado. Verifique os IDs no HTML.');
        return;
    }

    // Gerenciador de tema
    const ThemeManager = {
        currentTheme: 'dark',
        toggle: null,
        icon: null,

        init() {
            this.toggle = document.getElementById('themeToggle');
            this.icon = this.toggle ? this.toggle.querySelector('i') : null;
            this.currentTheme = localStorage.getItem('financas-theme') || 'dark';
            this.apply(this.currentTheme);
            this.toggle?.addEventListener('click', () => this.switchTheme());
        },

        apply(theme) {
            this.currentTheme = theme;
            const html = document.documentElement;

            if (theme === 'light') html.classList.add('light-theme');
            else html.classList.remove('light-theme');

            if (this.icon) {
                this.icon.className = theme === 'light'
                    ? 'bi bi-sun text-xl'
                    : 'bi bi-moon-stars text-xl';
            }
        },

        switchTheme() {
            const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('financas-theme', newTheme);
            this.apply(newTheme);
            Promise.all([carregarMetas(), carregarRanking(), carregarResumo()]);
            mostrarMsg(`Tema ${newTheme === 'dark' ? 'escuro' : 'claro'} ativado! ✨`, 'success');
        }
    };

    // Utilitários
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = String(str ?? '');
        return div.innerHTML;
    }

    function mostrarMsg(texto, tipo = 'success') {
        const configs = {
            success: { bg: '#10b981', icon: 'fa-solid fa-check' },
            warning: { bg: '#f59e0b', icon: 'fa-solid fa-triangle-exclamation' },
            error:   { bg: '#ef4444', icon: 'fa-solid fa-xmark' }
        };
        const c = configs[tipo] || configs.success;

        mensagemEl.innerHTML = `
            <div style="
                display:inline-flex; align-items:center; gap:10px;
                background:${c.bg}; color:#fff;
                padding:14px 24px; border-radius:100px;
                font-size:14px; font-weight:600;
                box-shadow: 0 8px 32px rgba(0,0,0,.35);
                animation: slideDown .3s ease;
            ">
                <i class="${c.icon}"></i>
                ${texto}
            </div>`;

        setTimeout(() => mensagemEl.innerHTML = '', 5500);
    }

    function toggleLoading(show) {
        loading.classList.toggle('hidden', !show);
    }

    function lancarConfetti() {
        if (typeof confetti === 'function') {
            confetti({ particleCount: 300, spread: 100, origin: { y: 0.6 } });
            setTimeout(() => confetti({ particleCount: 200, spread: 120, origin: { y: 0.7 } }), 300);
        }
    }

    function formatarData(dateStr) {
        return new Date(dateStr).toLocaleDateString('pt-BR', {
            day: '2-digit', month: '2-digit', year: 'numeric'
        });
    }

    // Progresso circular
    function criarProgresso(percent) {
        // Circunferência para r=36 → 2π×36 ≈ 226.2
        const circunferencia = 226.2;
        const dashArray = (percent / 100) * circunferencia;
        const isConcluida = percent >= 100;

        const gradId   = `grad_${Math.random().toString(36).slice(2, 7)}`;
        const cor1     = isConcluida ? '#059669' : '#7c3aed';
        const cor2     = isConcluida ? '#34d399' : '#a78bfa';
        const textCor  = isConcluida ? '#34d399' : '#f4f4f5';
        const subCor   = isConcluida ? '#6ee7b7' : '#71717a';

        return `
        <div style="position:relative; width:88px; height:88px; flex-shrink:0;">
            <svg width="88" height="88" viewBox="0 0 88 88" style="transform:rotate(-90deg);">
                <defs>
                    <linearGradient id="${gradId}" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%"   stop-color="${cor1}"/>
                        <stop offset="100%" stop-color="${cor2}"/>
                    </linearGradient>
                </defs>
                <circle cx="44" cy="44" r="36"
                    fill="none"
                    stroke="rgba(255,255,255,0.06)"
                    stroke-width="7"/>
                <circle cx="44" cy="44" r="36"
                    fill="none"
                    stroke="url(#${gradId})"
                    stroke-width="7"
                    stroke-dasharray="${dashArray} ${circunferencia}"
                    stroke-linecap="round"/>
            </svg>
            <div style="
                position:absolute; inset:0;
                display:flex; flex-direction:column;
                align-items:center; justify-content:center;
                font-size:16px; font-weight:700;
                color:${textCor}; letter-spacing:-0.5px;
                line-height:1;
            ">
                ${percent}<span style="font-size:9px; font-weight:500; color:${subCor}; margin-top:2px;">%</span>
            </div>
        </div>`;
    }

    // Card de meta
    function criarCardMeta(m) {
        const perc        = Math.min(Math.round((parseFloat(m.valor_atual) / parseFloat(m.valor_meta)) * 100), 100);
        const isConcluida = m.status === 'concluida';

        const valorAtualFmt = parseFloat(m.valor_atual).toLocaleString('pt-BR', { style:'currency', currency:'BRL' });
        const valorMetaFmt  = parseFloat(m.valor_meta).toLocaleString('pt-BR',  { style:'currency', currency:'BRL' });

        // Barra de progresso
        const barCor = isConcluida
            ? 'linear-gradient(90deg,#059669,#34d399)'
            : 'linear-gradient(90deg,#7c3aed,#a78bfa)';

        // Badge de status
        const badge = isConcluida
            ? `<span style="
                display:inline-flex; align-items:center; gap:6px;
                background:rgba(16,185,129,.1); color:#34d399;
                border:1px solid rgba(16,185,129,.25);
                font-size:10px; font-weight:700; letter-spacing:1.5px;
                padding:6px 14px; border-radius:100px; white-space:nowrap; flex-shrink:0;">
                <svg width="7" height="7" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                CONCLUÍDA
              </span>`
            : `<span style="
                display:inline-flex; align-items:center; gap:6px;
                background:rgba(234,179,8,.1); color:#fbbf24;
                border:1px solid rgba(234,179,8,.25);
                font-size:10px; font-weight:700; letter-spacing:1.5px;
                padding:6px 14px; border-radius:100px; white-space:nowrap; flex-shrink:0;">
                <svg width="7" height="7" viewBox="0 0 7 7"><circle cx="3.5" cy="3.5" r="3.5" fill="#fbbf24"/></svg>
                EM ANDAMENTO
              </span>`;

        // Cor do valor atual
        const valorCor = isConcluida ? '#34d399' : '#a78bfa';

        // Ação (botão ou banner)
        const action = isConcluida
            ? `<div style="
                display:flex; align-items:center; gap:12px;
                padding:14px 18px;
                background:rgba(16,185,129,.07);
                border:1px solid rgba(16,185,129,.2);
                border-radius:14px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2.5">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <div>
                    <p style="font-size:13px; font-weight:600; color:#34d399; margin:0;">Meta finalizada automaticamente</p>
                    <p style="font-size:11px; color:rgba(110,231,183,.7); margin:4px 0 0;">Parabéns pela conquista!</p>
                </div>
              </div>`
            : `<button onclick="deletar(${m.id_meta})" style="
                width:100%; padding:14px;
                background:rgba(239,68,68,.08);
                border:1px solid rgba(239,68,68,.2);
                border-radius:14px;
                color:#f87171;
                font-size:12px; font-weight:700; letter-spacing:1.5px;
                cursor:pointer; text-transform:uppercase;
                display:flex; align-items:center; justify-content:center; gap:8px;
                transition:background .2s, border-color .2s;"
                onmouseover="this.style.background='rgba(239,68,68,.14)'; this.style.borderColor='rgba(239,68,68,.4)'"
                onmouseout="this.style.background='rgba(239,68,68,.08)'; this.style.borderColor='rgba(239,68,68,.2)'">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6l-1 14H6L5 6"/>
                    <path d="M10 11v6M14 11v6"/>
                    <path d="M9 6V4h6v2"/>
                </svg>
                Desistir da meta
              </button>`;

        // Cor do glow
        const glowCor = isConcluida
            ? 'rgba(52,211,153,.12)'
            : 'rgba(139,92,246,.15)';

        return `
        <div class="meta-card-premium" data-id="${m.id_meta}" style="
            position:relative;
            background:linear-gradient(135deg,rgba(255,255,255,.06) 0%,rgba(255,255,255,.02) 100%);
            border:1px solid rgba(255,255,255,.08);
            border-radius:24px;
            padding:2rem;
            margin-bottom:1rem;
            overflow:hidden;
            transition:border-color .25s, transform .25s;
            cursor:default;">

            <!-- Glow decorativo -->
            <div style="
                position:absolute; top:-40px; right:-40px;
                width:140px; height:140px;
                background:radial-gradient(circle, ${glowCor} 0%, transparent 70%);
                pointer-events:none; border-radius:50%;"></div>

            <!-- Topo: título + badge -->
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.75rem;">
                <h4 style="font-size:18px; font-weight:600; color:#f4f4f5; line-height:1.3; flex:1; margin:0;">
                    ${escapeHtml(m.titulo)}
                </h4>
                ${badge}
            </div>

            <!-- Progresso: anel + valores -->
            <div style="display:flex; align-items:center; gap:1.5rem; margin-bottom:1.25rem;">
                ${criarProgresso(perc)}
                <div style="flex:1;">
                    <div style="font-size:32px; font-weight:800; font-variant-numeric:tabular-nums; letter-spacing:-1.5px; color:${valorCor}; line-height:1;">
                        ${valorAtualFmt}
                    </div>
                    <div style="font-size:13px; color:#52525b; margin:4px 0 2px;">de um total de</div>
                    <div style="font-size:18px; font-weight:600; font-variant-numeric:tabular-nums; color:#a1a1aa; letter-spacing:-0.5px;">
                        ${valorMetaFmt}
                    </div>
                </div>
            </div>

            <!-- Barra fina de progresso -->
            <div style="width:100%; height:5px; background:rgba(255,255,255,.07); border-radius:100px; overflow:hidden; margin-bottom:1.25rem;">
                <div style="width:${perc}%; height:100%; border-radius:100px; background:${barCor}; transition:width .6s cubic-bezier(.4,0,.2,1);"></div>
            </div>

            <!-- Datas -->
            <div style="font-size:11px; color:#3f3f46; display:flex; align-items:center; gap:6px; margin-bottom:1.25rem;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#3f3f46" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8"  y1="2" x2="8"  y2="6"/>
                    <line x1="3"  y1="10" x2="21" y2="10"/>
                </svg>
                ${formatarData(m.data_inicio)} — ${formatarData(m.data_fim)}
                <span style="margin-left:auto; font-size:11px; font-weight:600; color:#71717a;">${perc}% completo</span>
            </div>

            <!-- Ação -->
            ${action}
        </div>`;
    }

    // Brilho ao passar o mouse nos cards
    function ativarHoverGlow() {
        document.querySelectorAll('.meta-card-premium').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.borderColor = 'rgba(139,92,246,.35)';
                card.style.transform   = 'translateY(-2px)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.borderColor = 'rgba(255,255,255,.08)';
                card.style.transform   = 'translateY(0)';
            });
            card.addEventListener('mousemove', e => {
                const r  = card.getBoundingClientRect();
                const mx = ((e.clientX - r.left) / r.width  * 100).toFixed(1) + '%';
                const my = ((e.clientY - r.top)  / r.height * 100).toFixed(1) + '%';
                card.style.setProperty('--mx', mx);
                card.style.setProperty('--my', my);
            });
        });
    }

    // Resumo financeiro
    async function carregarResumo() {
        try {
            const res  = await fetch(`../php/metas_controller.php?acao=resumo_mes&_t=${Date.now()}`);
            const data = await res.json();
            if (!data.success) return;

            const renda  = parseFloat(data.renda).toLocaleString('pt-BR',  { style:'currency', currency:'BRL' });
            const gastos = parseFloat(data.gastos).toLocaleString('pt-BR', { style:'currency', currency:'BRL' });
            const saldo  = parseFloat(data.saldo).toLocaleString('pt-BR',  { style:'currency', currency:'BRL' });

            resumoEl.innerHTML = `
                <div style="display:flex; flex-direction:column; align-items:center; gap:6px;">
                    <i class="fa-solid fa-money-bill-wave" style="font-size:24px; color:#10b981;"></i>
                    <span style="font-size:10px; letter-spacing:2px; color:#52525b; text-transform:uppercase;">Renda mensal</span>
                    <span style="font-size:28px; font-weight:800; color:#10b981; font-variant-numeric:tabular-nums; letter-spacing:-1px;">${renda}</span>
                </div>
                <div style="display:flex; flex-direction:column; align-items:center; gap:6px;">
                    <i class="fa-solid fa-cart-shopping" style="font-size:24px; color:#f97316;"></i>
                    <span style="font-size:10px; letter-spacing:2px; color:#52525b; text-transform:uppercase;">Gastos do mês</span>
                    <span style="font-size:28px; font-weight:800; color:#f97316; font-variant-numeric:tabular-nums; letter-spacing:-1px;">${gastos}</span>
                </div>
                <div style="display:flex; flex-direction:column; align-items:center; gap:6px;">
                    <i class="fa-solid fa-piggy-bank" style="font-size:24px; color:#a78bfa;"></i>
                    <span style="font-size:10px; letter-spacing:2px; color:#52525b; text-transform:uppercase;">Saldo sobrando</span>
                    <span style="font-size:36px; font-weight:900; color:#a78bfa; font-variant-numeric:tabular-nums; letter-spacing:-1.5px;">${saldo}</span>
                    <span style="font-size:11px; color:#52525b;">${data.metas_ativas} meta(s) ativa(s)</span>
                </div>`;
        } catch (e) {
            console.error('Erro ao carregar resumo:', e);
        }
    }

    // Criar meta
    btnCriar.addEventListener('click', async () => {
        const titulo = document.getElementById('titulo').value.trim();
        const valor  = document.getElementById('valor_meta').value;

        if (!titulo || !valor || parseFloat(valor) <= 0) {
            mostrarMsg('Preencha todos os campos corretamente', 'error');
            return;
        }

        const pointsRes  = await fetch(`../php/metas_controller.php?acao=get_pontos_atual&_t=${Date.now()}`);
        const pointsData = await pointsRes.json();
        const pontosAtuais = pointsData.pontos || 0;
        const aposta = pontosAtuais > 0 ? Math.max(1, Math.floor(pontosAtuais / 2)) : 0;

        const confirmMsg = pontosAtuais === 0
            ? `Você tem 0 pontos no momento.\n\nEsta meta custará 0 pontos para criar.\nSe você completá-la → ganha 1 ponto!\nSe desistir → não perde nada.\n\nDeseja lançar a meta?`
            : `Você tem ${pontosAtuais} pontos.\n\nEsta meta irá apostar ${aposta} pontos (metade).\nSe você completá-la → ganha ${aposta * 2} pontos de volta!\nSe desistir → perde a aposta.\n\nDeseja lançar a meta?`;

        if (!confirm(confirmMsg)) return;

        const originalText = btnCriar.innerHTML;
        btnCriar.disabled  = true;
        btnCriar.innerHTML = `<i class="fa-solid fa-spinner animate-spin"></i> LANÇANDO...`;

        toggleLoading(true);

        try {
            const res = await fetch('../php/metas_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ acao: 'criar', titulo, valor_meta: parseFloat(valor) })
            });

            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Falha ao criar meta');

            mostrarMsg(`Meta lançada! Você apostou ${aposta} pontos nela 🚀`, 'success');
            lancarConfetti();
            await Promise.all([carregarMetas(), carregarResumo(), carregarRanking()]);
        } catch (e) {
            console.error('Erro ao criar meta:', e);
            mostrarMsg(e.message || 'Erro desconhecido', 'error');
        } finally {
            toggleLoading(false);
            btnCriar.disabled  = false;
            btnCriar.innerHTML = originalText;
        }
    });

    // Carregar metas
    async function carregarMetas() {
        toggleLoading(true);

        try {
            const res  = await fetch(`../php/metas_controller.php?acao=listar&_t=${Date.now()}`);
            const data = await res.json();

            if (data.fechamento_automatico) {
                const f = data.fechamento_automatico;
                mostrarMsg(f.mensagem, f.tipo || 'success');
                if (f.completed === f.total && f.total > 0) lancarConfetti();
            }

            if (!data.metas || data.metas.length === 0) {
                listaMetas.innerHTML = `
                    <div style="
                        display:flex; flex-direction:column; align-items:center;
                        justify-content:center; padding:6rem 2rem; text-align:center;">
                        <div style="
                            width:80px; height:80px;
                            background:rgba(255,255,255,.04);
                            border:1px solid rgba(255,255,255,.08);
                            border-radius:20px;
                            display:flex; align-items:center; justify-content:center;
                            margin-bottom:1.5rem;">
                            <i class="fa-solid fa-trophy" style="font-size:36px; color:#3f3f46;"></i>
                        </div>
                        <p style="font-size:22px; font-weight:600; color:#d4d4d8; margin:0 0 8px;">Nenhuma meta neste mês ainda...</p>
                        <p style="font-size:15px; color:#52525b; max-width:300px; line-height:1.6;">
                            Crie sua primeira conquista épica e acompanhe o progresso em tempo real.
                        </p>
                        <button onclick="abrirModalNovaMeta()" style="
                            margin-top:2rem; padding:12px 28px;
                            background:rgba(139,92,246,.12);
                            border:1px solid rgba(139,92,246,.3);
                            border-radius:100px; color:#a78bfa;
                            font-size:13px; font-weight:600; letter-spacing:1px;
                            cursor:pointer; display:flex; align-items:center; gap:8px;
                            transition:background .2s;"
                            onmouseover="this.style.background='rgba(139,92,246,.2)'"
                            onmouseout="this.style.background='rgba(139,92,246,.12)'">
                            <i class="fa-solid fa-plus"></i>
                            CRIAR NOVA META
                        </button>
                    </div>`;
                return;
            }

            listaMetas.innerHTML = data.metas.map(m => criarCardMeta(m)).join('');
            ativarHoverGlow();

        } catch (e) {
            console.error('Erro ao carregar metas:', e);
            mostrarMsg('Erro ao carregar metas', 'error');
        } finally {
            toggleLoading(false);
        }
    }

    // Excluir meta
    window.deletar = async function (id) {
        if (!confirm('Desistir dessa meta?\n\nVocê vai PERDER todos os pontos que apostou nela.')) return;

        toggleLoading(true);

        try {
            const res = await fetch('../php/metas_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ acao: 'deletar', id_meta: id })
            });

            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Falha ao desistir');

            mostrarMsg('Meta removida com sucesso ✅', 'success');
        } catch (e) {
            mostrarMsg('Erro ao desistir da meta', 'error');
        } finally {
            toggleLoading(false);
            await Promise.all([carregarMetas(), carregarResumo(), carregarRanking()]);
        }
    };

    // Ranking global
    async function carregarRanking() {
        try {
            const res  = await fetch(`../php/ranking_controller.php?_t=${Date.now()}`);
            const data = await res.json();
            if (!data.success) return;

            const ranking = data.ranking || [];
            const user    = data.user || { posicao: 1, pontos: 0, nome: 'Você' };

            const top3 = ranking.slice(0, 3).map((item, i) => {
                const pos    = i + 1;
                const isUser = item.nome === user.nome && parseInt(item.pontos_total) === parseInt(user.pontos);
                const medal  = pos === 1 ? '🥇' : pos === 2 ? '🥈' : '🥉';
                const ring   = pos === 1 ? 'border:2px solid rgba(167,139,250,.5); transform:scale(1.06);' : '';

                return `
                <div style="
                    background:linear-gradient(135deg,rgba(255,255,255,.06),rgba(255,255,255,.02));
                    border:1px solid rgba(255,255,255,.08);
                    border-radius:20px; padding:1.5rem;
                    display:flex; flex-direction:column; align-items:center; text-align:center;
                    transition:transform .2s, border-color .2s;
                    ${ring}">
                    <span style="font-size:36px; margin-bottom:10px;">${medal}</span>
                    <div style="
                        width:48px; height:48px;
                        background:linear-gradient(135deg,#3f3f46,#27272a);
                        border:1px solid rgba(255,255,255,.15);
                        border-radius:14px;
                        display:flex; align-items:center; justify-content:center;
                        font-size:20px; font-weight:700; color:#fff;
                        margin-bottom:10px;">
                        ${item.nome ? item.nome.charAt(0).toUpperCase() : '?'}
                    </div>
                    <div style="font-size:15px; font-weight:600; color:#f4f4f5;">${escapeHtml(item.nome)}</div>
                    ${isUser ? `<span style="margin-top:6px; font-size:10px; font-weight:700; background:#10b981; color:#fff; padding:3px 12px; border-radius:100px; letter-spacing:1px;">VOCÊ</span>` : ''}
                    <div style="margin-top:12px; font-size:32px; font-weight:900; color:#a78bfa; font-variant-numeric:tabular-nums; letter-spacing:-1px;">
                        ${item.pontos_total}
                    </div>
                    <div style="font-size:10px; letter-spacing:1.5px; color:#52525b; text-transform:uppercase;">pontos</div>
                </div>`;
            }).join('');

            const lista = ranking.slice(3).map((item, i) => {
                const pos    = i + 4;
                const isUser = item.nome === user.nome && parseInt(item.pontos_total) === parseInt(user.pontos);

                return `
                <div style="
                    display:flex; align-items:center; gap:16px;
                    padding:16px 20px;
                    background:linear-gradient(135deg,rgba(255,255,255,.04),rgba(255,255,255,.01));
                    border:1px solid rgba(255,255,255,.06);
                    border-radius:16px;
                    margin-bottom:8px;
                    transition:border-color .2s;"
                    onmouseover="this.style.borderColor='rgba(139,92,246,.3)'"
                    onmouseout="this.style.borderColor='rgba(255,255,255,.06)'">
                    <span style="font-size:14px; font-weight:700; color:#52525b; width:28px; flex-shrink:0;">#${pos}</span>
                    <div style="
                        width:36px; height:36px; flex-shrink:0;
                        background:linear-gradient(135deg,#3f3f46,#27272a);
                        border:1px solid rgba(255,255,255,.1);
                        border-radius:10px;
                        display:flex; align-items:center; justify-content:center;
                        font-size:15px; font-weight:700; color:#fff;">
                        ${item.nome ? item.nome.charAt(0).toUpperCase() : '?'}
                    </div>
                    <div style="flex:1;">
                        <span style="font-size:15px; font-weight:500; color:#d4d4d8;">${escapeHtml(item.nome)}</span>
                        ${isUser ? `<span style="margin-left:10px; font-size:10px; font-weight:700; background:#10b981; color:#fff; padding:2px 10px; border-radius:100px; letter-spacing:1px;">VOCÊ</span>` : ''}
                    </div>
                    <div style="font-size:24px; font-weight:800; color:#a78bfa; font-variant-numeric:tabular-nums; letter-spacing:-0.5px;">
                        ${item.pontos_total}
                    </div>
                </div>`;
            }).join('');

            rankingEl.innerHTML = `
                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px;">
                    ${top3}
                </div>
                <div>${lista}</div>`;

        } catch (e) {
            console.error('Erro ao carregar ranking:', e);
            rankingEl.innerHTML = `<p style="color:#f87171; text-align:center; padding:2rem;">Erro ao carregar ranking</p>`;
        }
    }

    // Inicialização
    ThemeManager.init();
    carregarMetas();
    carregarRanking();
    carregarResumo();
});