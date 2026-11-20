/* menu.js — Painel principal (dashboard) */
import { CONFIG as APP_CONFIG } from './configuracao.js';

(() => {
  'use strict';

  /* Configuração local (as categorias vêm de configuracao.js) */
  const CONFIG = {
    animationDuration: 900,
    storageKey: 'financas-theme',
  };

  /* Utilitários */
  const Utils = {

    formatCurrency(value) {
      return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
      }).format(Number(value || 0));
    },

    formatDateTime() {
      return new Date().toLocaleDateString('pt-BR', {
        weekday: 'long',
        year   : 'numeric',
        month  : 'long',
        day    : 'numeric'
      });
    },

    showToast(message, type = 'info') {
      const container = document.getElementById('toastContainer');
      if (!container) return;
      const toast = document.createElement('div');
      toast.className = `toast show align-items-center text-white bg-${type} border-0`;
      toast.innerHTML = `
        <div class="d-flex">
          <div class="toast-body">${message}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>`;
      container.appendChild(toast);
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
      }, 3000);
    },

    animateCounter(element, target, duration = CONFIG.animationDuration) {
      if (!element) return;
      const startTime = performance.now();
      const animate = (now) => {
        const progress = Math.min((now - startTime) / duration, 1);
        const easeOut  = 1 - Math.pow(1 - progress, 4);
        element.textContent = Utils.formatCurrency(Math.round(easeOut * target));
        if (progress < 1) requestAnimationFrame(animate);
      };
      requestAnimationFrame(animate);
    },

    getInitials(name) {
      if (!name) return '?';
      const parts = name.trim().split(' ');
      return parts.length >= 2
        ? (parts[0][0] + parts[1][0]).toUpperCase()
        : name.substring(0, 2).toUpperCase();
    },

    isLightTheme() {
      return document.documentElement.classList.contains('light-theme');
    },

    /* Converte o nome de uma categoria em slug ASCII sem acentos (aceita as 15 categorias de APP_CONFIG) */
    toSlug(str) {
      if (!str) return 'outros';
      const normalized = String(str)
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
      return APP_CONFIG.CATEGORIA_ID_MAP?.[normalized] ? normalized : 'outros';
    },

    /* Rótulo de um slug (CATEGORIAS_DB + CATEGORIA_ID_MAP) */
    getCategoryLabel(slug) {
      const id = APP_CONFIG.CATEGORIA_ID_MAP?.[slug];
      return APP_CONFIG.CATEGORIAS_DB?.[id] || slug;
    },

    /* Cor de um slug (paleta do app) */
    getCategoryColor(slug) {
      return APP_CONFIG.CATEGORY_COLORS?.[slug] || '#9c9ca4';
    },

    /* Atalho para adicionar listener com segurança */
    on(id, event, handler) {
      document.getElementById(id)?.addEventListener(event, handler);
    }
  };

  /* Gerenciador de carregamento */
  const LoadingManager = {
    overlay: null,

    init() {
      this.overlay = document.getElementById('loadingOverlay');
    },

    hide() {
      if (!this.overlay) return;
      this.overlay.classList.remove('visible');
      this.overlay.classList.add('hidden');
      setTimeout(() => {
        if (this.overlay) this.overlay.style.display = 'none';
      }, 300);
    }
  };

  /* Gerenciador de tema */
  const ThemeManager = {
    currentTheme: 'dark',
    toggle      : null,
    icon        : null,

    init() {
      this.toggle       = document.getElementById('themeToggle');
      this.icon         = this.toggle?.querySelector('i') ?? this.toggle?.querySelector('.theme-icon') ?? null;
      this.currentTheme = localStorage.getItem(CONFIG.storageKey) || 'dark';
      this.apply(this.currentTheme);
      this.toggle?.addEventListener('click', () => this.switch());
    },

    apply(theme) {
      this.currentTheme = theme;
      document.documentElement.classList.toggle('light-theme', theme === 'light');
      if (this.icon) {
        this.icon.className = theme === 'light' ? 'bi bi-sun' : 'bi bi-moon-stars';
      }
      if (ChartManager.chart) ChartManager.render();
    },

    switch() {
      const next = this.currentTheme === 'dark' ? 'light' : 'dark';
      localStorage.setItem(CONFIG.storageKey, next);
      this.apply(next);
      Utils.showToast(`Tema ${next === 'dark' ? 'escuro' : 'claro'} ativado`, 'success');
    }
  };

  /* Gerenciador do gráfico */
  const ChartManager = {
    chart      : null,
    _hidden    : {},
    _categories: [],
    _ctx       : null,

    init(ctx) {
      this._ctx = ctx || null;
    },

    update(categories = []) {
      this._categories = categories;
      // Remove do mapa de ocultos as categorias que não existem mais
      const slugsAtivos = categories.map(c => Utils.toSlug(c.categoria));
      Object.keys(this._hidden).forEach(s => {
        if (!slugsAtivos.includes(s)) delete this._hidden[s];
      });
      this.render();
    },

    render() {
      if (!this._ctx) return;

      const categories = this._categories;
      const isLight    = Utils.isLightTheme();

      if (!categories.length) {
        this._destroyChart();
        this._renderEmptyLegend();
        return;
      }

      const slugs         = categories.map(c => Utils.toSlug(c.categoria));
      const labels        = slugs.map(s => Utils.getCategoryLabel(s));
      const colors        = slugs.map(s => Utils.getCategoryColor(s));
      const data          = categories.map(c => Number(c.total || 0));
      const visibleData   = data.map((v, i)  => this._hidden[slugs[i]] ? 0 : v);
      const visibleColors = colors.map((c, i) => this._hidden[slugs[i]] ? 'rgba(0,0,0,0)' : c);
      const visibleTotal  = visibleData.reduce((a, b) => a + b, 0);

      this._destroyChart();

      /* Plugin do texto central */
      const centerLabelPlugin = {
        id: 'centerLabel',
        afterDraw(chart) {
          const { ctx: c, chartArea: { top, bottom, left, right } } = chart;
          const cx = (left + right) / 2;
          const cy = (top + bottom) / 2;
          const lt = Utils.isLightTheme();

          c.save();
          c.textAlign    = 'center';
          c.textBaseline = 'middle';

          c.font      = 'bold 18px Manrope, Inter, sans-serif';
          c.fillStyle = lt ? '#1a1a1d' : '#f5f5f7';
          c.fillText(Utils.formatCurrency(visibleTotal), cx, cy - 10);

          c.font      = '600 10px Manrope, Inter, sans-serif';
          c.fillStyle = lt ? '#9e9ea3' : '#6e6e73';
          c.fillText('TOTAL', cx, cy + 10);

          c.restore();
        }
      };

      this.chart = new Chart(this._ctx, {
        type   : 'doughnut',
        plugins: [centerLabelPlugin],
        data   : {
          labels,
          datasets: [{
            data            : visibleData,
            backgroundColor : visibleColors,
            borderWidth     : 3,
            borderColor     : isLight ? '#fff' : '#141416',
            hoverOffset     : 14
          }]
        },
        options: {
          responsive         : true,
          maintainAspectRatio: false,
          cutout             : '62%',
          animation: {
            animateRotate: true,
            animateScale : true,
            duration     : 700,
            easing       : 'easeOutQuart'
          },
          plugins: {
            legend : { display: false },
            tooltip: {
              backgroundColor: isLight ? '#fff'              : '#1a1a1d',
              titleColor     : isLight ? '#111'              : '#f5f5f7',
              bodyColor      : isLight ? '#444'              : '#ccc',
              borderColor    : isLight ? '#ddd'              : 'rgba(255,255,255,0.1)',
              borderWidth    : 1,
              padding        : 12,
              cornerRadius   : 10,
              filter         : (item) => item.parsed > 0,
              callbacks: {
                label(item) {
                  const pct = visibleTotal > 0
                    ? ((item.parsed / visibleTotal) * 100).toFixed(1)
                    : '0.0';
                  return `  ${item.label}: ${Utils.formatCurrency(item.parsed)}  (${pct}%)`;
                }
              }
            }
          }
        }
      });

      this._syncHtmlLegend(slugs);
    },

    /* Sincroniza o estado oculto/visível com os itens do #legendList */
    _syncHtmlLegend(slugs) {
      const legendList = document.getElementById('legendList');
      if (!legendList) return;
      legendList.querySelectorAll('.legend-item').forEach((li, i) => {
        li.classList.toggle('inactive', !!this._hidden[slugs[i]]);
      });
    },

    _renderEmptyLegend() {
      const legendList = document.getElementById('legendList');
      if (!legendList) return;
      legendList.innerHTML = `
        <li class="legend-item">
          <div class="legend-info">
            <span class="legend-label" style="color:var(--color-text-tertiary);">
              Nenhum gasto registrado este mês
            </span>
          </div>
        </li>`;
    },

    _destroyChart() {
      if (this.chart) {
        this.chart.destroy();
        this.chart = null;
      }
    }
  };

  /* Gerenciador de perfil */
  const ProfileManager = {
    modal        : null,
    passwordModal: null,
    profileData  : {
      nome: '', email: '', telefone: '',
      notificacoes: true, emails_semanais: false, plano: 'free'
    },
    isFirstTime: false,

    init() {
      this.modal         = document.getElementById('profileModal');
      this.passwordModal = document.getElementById('passwordModal');
      this.loadProfileFromServer();
      this.bindEvents();
    },

    async loadProfileFromServer() {
      try {
        const res  = await fetch('../php/profile_controller.php?acao=get_profile');
        const data = JSON.parse(await res.text());
        if (data.success) {
          this.profileData = {
            nome            : data.nome            || '',
            email           : data.email           || '',
            telefone        : data.telefone        || '',
            notificacoes    : !!data.notificacoes,
            emails_semanais : !!data.emails_semanais,
            plano           : data.plano           || 'free'
          };
          this.isFirstTime = !this.profileData.nome || !this.profileData.nome.trim();
          this.updateUI();
          if (this.isFirstTime) this.showFirstTimeOnboarding();
        }
      } catch (e) { /* ignora o erro */ }
    },

    showFirstTimeOnboarding() {
      const title = document.getElementById('profileModalTitle');
      if (title) title.innerHTML = `<i class="bi bi-hand-wave me-2"></i> Bem-vindo!`;

      const body = this.modal?.querySelector('.profile-modal-body');
      if (body) {
        const welcome = document.createElement('div');
        welcome.className = 'alert alert-info mb-4 text-center';
        welcome.innerHTML = `<strong>Obrigado por criar sua conta!</strong><br>Como devemos te chamar?`;
        body.insertBefore(welcome, body.firstChild);
      }
      this.openProfileModal();
    },

    updateUI() {
      const name      = this.profileData.nome  || 'Usuário';
      const plano     = this.profileData.plano || 'free';
      const planLabel = plano === 'premium' ? 'Plano Premium' : 'Plano Free';

      ['displayUserName', 'headerUserName'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = name;
      });

      const sidebarStatus = document.getElementById('sidebarPlanStatus');
      if (sidebarStatus) sidebarStatus.textContent = planLabel;

      const avatarInitials    = document.getElementById('avatarInitials');
      const avatarDisplayName = document.getElementById('avatarDisplayName');
      const avatarPlanDisplay = document.getElementById('avatarPlanDisplay');
      if (avatarInitials)    avatarInitials.textContent    = Utils.getInitials(name);
      if (avatarDisplayName) avatarDisplayName.textContent = name;
      if (avatarPlanDisplay) avatarPlanDisplay.textContent = planLabel;

      const fName  = document.getElementById('profileName');
      const fEmail = document.getElementById('profileEmail');
      const fPhone = document.getElementById('profilePhone');
      if (fName)  fName.value  = name;
      if (fEmail) fEmail.value = this.profileData.email;
      if (fPhone) fPhone.value = this.profileData.telefone;

      const notif = document.getElementById('notificacoesToggle');
      const email = document.getElementById('emailsSemanaisToggle');
      if (notif) notif.checked = this.profileData.notificacoes;
      if (email) email.checked = this.profileData.emails_semanais;

      this._applyPlanSelection(plano);
    },

    _applyPlanSelection(plano) {
      const hidden = document.getElementById('selectedPlan');
      if (hidden) hidden.value = plano;
      document.getElementById('planCardFree')    ?.classList.toggle('selected', plano === 'free');
      document.getElementById('planCardPremium') ?.classList.toggle('selected', plano === 'premium');
    },

    selectPlan(plan) { this._applyPlanSelection(plan); },

    bindEvents() {
      /* Perfil */
      Utils.on('userProfileTrigger', 'click',   () => this.openProfileModal());
      Utils.on('userProfileTrigger', 'keydown',  (e) => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.openProfileModal(); }
      });
      Utils.on('profileModalClose',   'click', () => this.closeProfileModal());
      Utils.on('profileModalOverlay', 'click', () => this.closeProfileModal());
      Utils.on('cancelProfileBtn',    'click', () => this.closeProfileModal());
      Utils.on('saveProfileBtn',      'click', () => this.saveProfile());

      /* Seleção de plano */
      Utils.on('planCardFree',    'click',   () => this.selectPlan('free'));
      Utils.on('planCardPremium', 'click',   () => this.selectPlan('premium'));
      Utils.on('planCardFree',    'keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.selectPlan('free');    } });
      Utils.on('planCardPremium', 'keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.selectPlan('premium'); } });

      /* Senha */
      Utils.on('changePasswordBtn',    'click', () => this.openPasswordModal());
      Utils.on('passwordModalClose',   'click', () => this.closePasswordModal());
      Utils.on('passwordModalOverlay', 'click', () => this.closePasswordModal());
      Utils.on('cancelPasswordBtn',    'click', () => this.closePasswordModal());
      Utils.on('savePasswordBtn',      'click', () => this.changePassword());

      /* Validação em tempo real */
      Utils.on('newPassword',     'input', () => this._validatePasswordFields());
      Utils.on('confirmPassword', 'input', () => this._validatePasswordFields());

      /* Fechar com Esc */
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { this.closeProfileModal(); this.closePasswordModal(); }
      });
    },

    openProfileModal()  {
      if (!this.modal) return;
      this.modal.classList.add('show');
      document.body.style.overflow = 'hidden';
    },
    closeProfileModal() {
      if (!this.modal) return;
      this.modal.classList.remove('show');
      document.body.style.overflow = '';
    },

    async saveProfile() {
      const nome            = document.getElementById('profileName')?.value.trim()     || '';
      const email           = document.getElementById('profileEmail')?.value.trim()    || '';
      const telefone        = document.getElementById('profilePhone')?.value.trim()    || '';
      const notificacoes    = document.getElementById('notificacoesToggle')?.checked   ?? true;
      const emails_semanais = document.getElementById('emailsSemanaisToggle')?.checked ?? false;
      const plano           = document.getElementById('selectedPlan')?.value           || 'free';

      if (!nome)  { Utils.showToast('Por favor, digite seu nome',   'warning'); return; }
      if (!email) { Utils.showToast('Por favor, digite seu e-mail', 'warning'); return; }

      const btn = document.getElementById('saveProfileBtn');
      if (btn) {
        btn.disabled   = true;
        btn.innerHTML  = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';
      }

      try {
        const res  = await fetch('../php/profile_controller.php', {
          method : 'POST',
          headers: { 'Content-Type': 'application/json' },
          body   : JSON.stringify({ acao: 'update_profile', nome, email, telefone, notificacoes, emails_semanais, plano })
        });
        const data = JSON.parse(await res.text());

        if (data.success) {
          Object.assign(this.profileData, { nome, email, telefone, notificacoes, emails_semanais, plano });
          this.updateUI();
          this.closeProfileModal();
          this.isFirstTime = false;
          Utils.showToast('Alterações salvas com sucesso!', 'success');
        } else {
          Utils.showToast(data.message || 'Erro ao salvar', 'danger');
        }
      } catch (e) {
        Utils.showToast('Erro ao salvar alterações', 'danger');
      } finally {
        if (btn) {
          btn.disabled  = false;
          btn.innerHTML = '<i class="bi bi-check-lg me-2"></i>Salvar Alterações';
        }
      }
    },

    openPasswordModal() {
      this.closeProfileModal();
      if (!this.passwordModal) return;
      this.passwordModal.classList.add('show');
      document.body.style.overflow = 'hidden';
    },

    closePasswordModal() {
      if (!this.passwordModal) return;
      this.passwordModal.classList.remove('show');
      document.body.style.overflow = '';
      ['currentPassword', 'newPassword', 'confirmPassword'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
      });
      this._resetPasswordRequirements();
    },

    _validatePasswordFields() {
      const nova    = document.getElementById('newPassword')?.value    || '';
      const confirm = document.getElementById('confirmPassword')?.value || '';
      const rLen   = document.getElementById('req-length');
      const rMatch = document.getElementById('req-match');
      if (rLen) {
        rLen.querySelector('i').className = nova.length >= 6
          ? 'bi bi-check-circle-fill text-success'
          : 'bi bi-circle';
      }
      if (rMatch) {
        rMatch.querySelector('i').className = (nova.length > 0 && nova === confirm)
          ? 'bi bi-check-circle-fill text-success'
          : 'bi bi-circle';
      }
    },

    _resetPasswordRequirements() {
      ['req-length', 'req-match'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.querySelector('i').className = 'bi bi-circle';
      });
    },

    async changePassword() {
      const atual   = document.getElementById('currentPassword')?.value || '';
      const nova    = document.getElementById('newPassword')?.value     || '';
      const confirm = document.getElementById('confirmPassword')?.value || '';

      if (!atual || !nova || !confirm) {
        Utils.showToast('Preencha todos os campos', 'warning'); return;
      }
      if (nova.length < 6) {
        Utils.showToast('A nova senha deve ter no mínimo 6 caracteres', 'warning'); return;
      }
      if (nova !== confirm) {
        Utils.showToast('As senhas não coincidem', 'warning'); return;
      }

      const btn = document.getElementById('savePasswordBtn');
      if (btn) {
        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Alterando...';
      }

      try {
        const res  = await fetch('../php/profile_controller.php', {
          method : 'POST',
          headers: { 'Content-Type': 'application/json' },
          body   : JSON.stringify({ acao: 'change_password', senha_atual: atual, nova_senha: nova })
        });
        const data = JSON.parse(await res.text());
        if (data.success) {
          this.closePasswordModal();
          Utils.showToast(data.message, 'success');
        } else {
          Utils.showToast(data.message || 'Erro ao alterar senha', 'danger');
        }
      } catch (e) {
        Utils.showToast('Erro ao alterar senha', 'danger');
      } finally {
        if (btn) {
          btn.disabled  = false;
          btn.innerHTML = '<i class="bi bi-check-lg me-2"></i>Alterar Senha';
        }
      }
    }
  };

  /* Gerenciador da barra lateral */
  const SidebarManager = {
    sidebar: null,
    toggle : null,
    overlay: null,

    init() {
      this.sidebar = document.getElementById('sidebar');
      this.toggle  = document.getElementById('sidebarToggle');
      if (!this.sidebar || !this.toggle) return;

      this._createOverlay();
      this.toggle.addEventListener('click',  () => this._toggleSidebar());
      this.overlay?.addEventListener('click', () => this._closeSidebar());
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.sidebar.classList.contains('show')) this._closeSidebar();
      });
      this._setupNavLinks();
    },

    _createOverlay() {
      if (this.overlay) return;
      this.overlay = document.createElement('div');
      this.overlay.className = 'sidebar-overlay';
      this.overlay.style.cssText =
        'position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:49;' +
        'opacity:0;visibility:hidden;transition:opacity 250ms,visibility 250ms;';
      document.body.appendChild(this.overlay);
    },

    _toggleSidebar() {
      const isOpen = this.sidebar.classList.toggle('show');
      if (!this.overlay) return;
      this.overlay.style.opacity    = isOpen ? '1' : '0';
      this.overlay.style.visibility = isOpen ? 'visible' : 'hidden';
    },

    _closeSidebar() {
      this.sidebar.classList.remove('show');
      if (this.overlay) {
        this.overlay.style.opacity    = '0';
        this.overlay.style.visibility = 'hidden';
      }
    },

    _setupNavLinks() {
      this.sidebar.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', (e) => {
          if (link.querySelector('.badge-soon')) {
            e.preventDefault();
            Utils.showToast('Esta funcionalidade estará disponível em breve!', 'info');
            return;
          }
          const href = link.getAttribute('href');
          if (href && href !== '#') { this._closeSidebar(); return; }
          e.preventDefault();
        });
      });
    }
  };

  /* Gerenciador de estatísticas */
  const StatsManager = {
    animateCounters() {
      document.querySelectorAll('.stat-value[data-target]').forEach((el, i) => {
        const target = Number(el.getAttribute('data-target')) || 0;
        setTimeout(() => Utils.animateCounter(el, target), i * 120);
      });
    },

    setupRefresh() {
      Utils.on('refreshData', 'click', () => {
        const icon = document.querySelector('#refreshData i');
        if (icon) icon.style.animation = 'spin 600ms linear';
        applyDashboardData().finally(() => {
          if (icon) icon.style.animation = '';
        });
      });
    }
  };

  /* Gerenciador de data e hora */
  const DateTimeManager = {
    element: null,

    init() {
      this.element = document.getElementById('dateTimeText');
      if (!this.element) return;
      this.update();
      setInterval(() => this.update(), 60000);
    },

    update() {
      if (this.element) this.element.textContent = Utils.formatDateTime();
    }
  };

  /* Gerenciador de exportação */
  const ExportManager = {
    init() {
      Utils.on('exportCsv', 'click', () => this.exportToCSV());
    },

    exportToCSV() {
      try {
        const rows = [['Categoria', 'Percentual', 'Valor']];
        document.querySelectorAll('#legendList .legend-item').forEach(li => {
          rows.push([
            li.querySelector('.legend-label')?.textContent   || '',
            li.querySelector('.legend-percent')?.textContent || '',
            li.querySelector('.legend-value')?.textContent   || ''
          ]);
        });
        const csv  = rows.map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(',')).join('\n');
        const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = `financas-ja-${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a); a.click(); a.remove();
        URL.revokeObjectURL(url);
        Utils.showToast('Dados exportados com sucesso!', 'success');
      } catch (e) {
        Utils.showToast('Erro ao exportar dados', 'danger');
      }
    }
  };

  /* Ações rápidas: só intercepta <button> sem href; links <a> navegam normalmente */
  const QuickActionsManager = {
    init() {
      document.querySelectorAll('.action-btn').forEach(btn => {
        /* Se for link com destino real, deixa navegar */
        if (btn.tagName === 'A' && btn.getAttribute('href') && btn.getAttribute('href') !== '#') return;

        btn.addEventListener('click', () => {
          Utils.showToast(`${btn.querySelector('span')?.textContent || 'Ação'} — Em breve!`, 'info');
        });
      });
    }
  };

  /* Preenche a legenda HTML (#legendList) */
  function populateLegend(categories, slugs, colors) {
    const legendList = document.getElementById('legendList');
    if (!legendList) return;
    legendList.innerHTML = '';

    if (!categories.length) {
      ChartManager._renderEmptyLegend();
      return;
    }

    const total = categories.reduce((s, c) => s + Number(c.total || 0), 0);

    categories.forEach((c, idx) => {
      const slug  = slugs[idx];
      const color = colors[idx];
      const label = Utils.getCategoryLabel(slug) || c.categoria || 'Outros';
      const pct   = total > 0 ? Math.round((Number(c.total) / total) * 100) : 0;

      const li = document.createElement('li');
      li.className = 'legend-item';
      li.setAttribute('data-slug', slug);
      li.tabIndex  = 0;

      /* Lado esquerdo: bolinha + nome */
      const info    = document.createElement('div');
      info.className = 'legend-info';
      const dot     = document.createElement('span');
      dot.className  = 'legend-dot';
      dot.style.background = color;
      const labelEl = document.createElement('span');
      labelEl.className  = 'legend-label';
      labelEl.textContent = label;
      info.appendChild(dot);
      info.appendChild(labelEl);

      /* Lado direito: valor + percentual */
      const stats   = document.createElement('div');
      stats.className = 'legend-stats';
      const valueEl   = document.createElement('span');
      valueEl.className  = 'legend-value';
      valueEl.textContent = Utils.formatCurrency(c.total || 0);
      const percentEl = document.createElement('span');
      percentEl.className  = 'legend-percent';
      percentEl.textContent = `${pct}%`;
      stats.appendChild(valueEl);
      stats.appendChild(percentEl);

      li.appendChild(info);
      li.appendChild(stats);

      /* Toggle ocultar/mostrar no gráfico */
      const toggle = () => {
        ChartManager._hidden[slug] = !ChartManager._hidden[slug];
        li.classList.toggle('inactive', !!ChartManager._hidden[slug]);
        ChartManager.render();
      };
      li.addEventListener('click',   toggle);
      li.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
      });

      legendList.appendChild(li);
    });
  }

  /* Busca e aplica os dados do dashboard */
  async function fetchDashboardData() {
    try {
      const [rendaRes, gastosRes] = await Promise.all([
        fetch('../php/get_renda.php',  { credentials: 'same-origin' }),
        fetch('../php/get_gastos.php', { credentials: 'same-origin' })
      ]);

      const rendaText  = await rendaRes.text();
      const gastosText = await gastosRes.text();

      let rendaJson, gastosJson;
      try       { rendaJson  = JSON.parse(rendaText);  } catch (e) { return null; }
      try       { gastosJson = JSON.parse(gastosText); } catch (e) { return null; }

      if (!rendaJson.success || !gastosJson.success) return null;

      const gastos        = Array.isArray(gastosJson.gastos) ? gastosJson.gastos : [];
      const gastos_totais = gastos.reduce((sum, g) => sum + (Number(g.valor) || 0), 0);

      /* Agrupa por categoria_nome */
      const categoriesMap = {};
      gastos.forEach(g => {
        const cat = g.categoria_nome || g.categoria || 'Outros';
        if (!categoriesMap[cat]) categoriesMap[cat] = { categoria: cat, total: 0 };
        categoriesMap[cat].total += Number(g.valor) || 0;
      });

      const categories = Object.values(categoriesMap).map(c => ({
        ...c,
        percent: gastos_totais > 0 ? Math.round((c.total / gastos_totais) * 100) : 0
      }));

      return {
        gastos_totais,
        categories,
        income: Number(rendaJson.valor || 0)
      };

    } catch (err) {
      Utils.showToast('Erro ao carregar dados do servidor', 'danger');
      return null;
    }
  }

  async function applyDashboardData() {
    const data = await fetchDashboardData();

    if (!data) {
      ChartManager.update([]);
      LoadingManager.hide();
      return;
    }

    const gastosTotais = Number(data.gastos_totais || 0);
    const rendaMensal  = Number(data.income         || 0);
    const saldoAtual   = rendaMensal - gastosTotais;

    /* Atualiza os cards de estatísticas */
    const expensesEl = document.querySelector('.stat-card-expenses .stat-value');
    const incomeEl   = document.querySelector('.stat-card-income .stat-value');
    const balanceEl  = document.querySelector('.stat-card-balance .stat-value');

    if (expensesEl) {
      expensesEl.setAttribute('data-target', Math.round(gastosTotais));
      expensesEl.textContent = Utils.formatCurrency(gastosTotais);
    }
    if (incomeEl) {
      incomeEl.setAttribute('data-target', Math.round(rendaMensal));
      incomeEl.textContent = Utils.formatCurrency(rendaMensal);
    }
    if (balanceEl) {
      balanceEl.setAttribute('data-target', Math.round(saldoAtual));
      balanceEl.textContent = Utils.formatCurrency(saldoAtual);
    }

    const categories = data.categories || [];

    if (!categories.length) {
      ChartManager.update([]);
      LoadingManager.hide();
      return;
    }

    const slugs  = categories.map(c => Utils.toSlug(c.categoria));
    const colors = slugs.map(s => Utils.getCategoryColor(s));

    populateLegend(categories, slugs, colors);
    ChartManager.update(categories);
    StatsManager.animateCounters();
    LoadingManager.hide();
  }

  /* Inicialização */
  document.addEventListener('DOMContentLoaded', async () => {

    LoadingManager.init();
    ThemeManager.init();
    ProfileManager.init();
    SidebarManager.init();
    DateTimeManager.init();
    ExportManager.init();
    QuickActionsManager.init();

    /* Inicializa o gráfico */
    const canvas = document.getElementById('pieChart');
    ChartManager.init(canvas ? canvas.getContext('2d') : null);

    StatsManager.setupRefresh();
    await applyDashboardData();
    LoadingManager.hide();

  });

})();