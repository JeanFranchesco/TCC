(() => {
  'use strict';

  /**
   * login.js
   * - Gerencia formulário de login/registro (frontend)
   * - Boas práticas aplicadas: validação no cliente, timeouts em fetch,
   *   feedback construído via DOM (evita innerHTML com conteúdo de servidor)
   * - Possíveis melhorias: hCaptcha/recaptcha no register, limitar tentativas
   */

  /* =========================
     CONFIG
  ========================= */
  const API_BASE = '/TCC/Codigo/php';
  const REDIRECT_OK = 'menu.php';
  const THEME_KEY = 'financas-theme';
  const REQUEST_TIMEOUT = 8000; // ms

  /* =========================
     HELPERS
  ========================= */
  // Pequena helper para obter elemento por id — preferir clareza em código
  const qs = id => document.getElementById(id);

  // Validação simples de e-mail (cliente): aceitará a maioria dos e-mails válidos
  const isValidEmail = email => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

  // Parse seguro de JSON — evita lançar exceptions no fluxo de tratamento
  const parseJsonSafe = text => {
    try {
      return JSON.parse(text);
    } catch {
      return null;
    }
  };

  /* =========================
     THEME MANAGER
  ========================= */
  const ThemeManager = {
    current: 'dark',
    toggle: null,
    icon: null,

    init() {
      this.toggle = qs('themeToggle');
      this.icon = this.toggle?.querySelector('i') || null;
      this.current = localStorage.getItem(THEME_KEY) || 'dark';
      this.apply(this.current);
      this.toggle?.addEventListener('click', () => this.switch());
    },

    apply(theme) {
      document.documentElement.classList.toggle('light-theme', theme === 'light');
      this.current = theme;
      if (this.icon) {
        this.icon.className = theme === 'light' ? 'bi bi-sun' : 'bi bi-moon-stars';
      }
    },

    switch() {
      const next = this.current === 'dark' ? 'light' : 'dark';
      localStorage.setItem(THEME_KEY, next);
      this.apply(next);
    }
  };

  /* =========================
     FORM MANAGER
  ========================= */
  const FormManager = {
    loginForm: null,
    registerForm: null,
    loginFeedback: null,
    registerFeedback: null,

    init() {
      this.loginForm = qs('loginForm');
      this.registerForm = qs('registerForm');
      this.loginFeedback = qs('loginFeedback');
      this.registerFeedback = qs('registerFeedback');

      this.bindSwitch();
      this.bindPasswordToggle();
      this.bindSubmit();
      this.initFromURL();
    },

    // feedback seguro: constrói DOM em vez de innerHTML com conteúdo do servidor
    feedback(el, msg, type = 'error') {
      if (!el) return;
      el.innerHTML = '';
      const wrapper = document.createElement('div');
      wrapper.className = 'd-flex align-items-start';
      const icon = document.createElement('i');
      icon.className = `bi me-2 ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'}`;
      const span = document.createElement('span');
      span.textContent = msg;
      wrapper.appendChild(icon);
      wrapper.appendChild(span);
      el.appendChild(wrapper);
      el.className = `feedback ${type}`;
      el.classList.remove('visually-hidden');
      setTimeout(() => el.classList.add('visually-hidden'), 5000);
    },

    showLogin() {
      this.registerForm?.classList.add('d-none');
      this.loginForm?.classList.remove('d-none');
      history.replaceState(null, '', '?show=login');
    },

    showRegister() {
      this.loginForm?.classList.add('d-none');
      this.registerForm?.classList.remove('d-none');
      history.replaceState(null, '', '?show=register');
    },

    bindSwitch() {
      qs('showRegister')?.addEventListener('click', e => { e.preventDefault(); this.showRegister(); });
      qs('showLogin')?.addEventListener('click', e => { e.preventDefault(); this.showLogin(); });
      qs('alreadyHaveAccount')?.addEventListener('click', e => { e.preventDefault(); this.showLogin(); });
    },

    bindPasswordToggle() {
      document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
          const input = qs(btn.dataset.target);
          const icon = btn.querySelector('i');
          if (!input) return;
          const visible = input.type === 'text';
          input.type = visible ? 'password' : 'text';
          icon && (icon.className = visible ? 'bi bi-eye' : 'bi bi-eye-slash');
        });
      });
    },

    async postForm(url, formData, timeout = REQUEST_TIMEOUT) {
      const controller = new AbortController();
      const id = setTimeout(() => controller.abort(), timeout);
      try {
        const res = await fetch(url, {
          method: 'POST',
          credentials: 'same-origin',
          body: formData,
          signal: controller.signal
        });
        clearTimeout(id);
        const text = await res.text();
        // tenta parse seguro
        const json = parseJsonSafe(text);
        return { ok: res.ok, status: res.status, text, json };
      } catch (err) {
        clearTimeout(id);
        throw err;
      }
    },

    bindSubmit() {
      /* ===== LOGIN ===== */
      this.loginForm?.addEventListener('submit', async e => {
        e.preventDefault();

        const email = qs('loginEmail')?.value.trim() || '';
        const senha = qs('loginPassword')?.value || '';
        const btn = e.target.querySelector('button[type="submit"]');

        if (!email || !senha) {
          return this.feedback(this.loginFeedback, 'Preencha todos os campos.');
        }
        if (!isValidEmail(email)) {
          return this.feedback(this.loginFeedback, 'E-mail inválido.');
        }
        if (email.length > 255) {
          return this.feedback(this.loginFeedback, 'Email muito longo.');
        }

        btn.disabled = true;

        const fd = new FormData();
        fd.append('email', email);
        fd.append('senha', senha);

        try {
          const result = await this.postForm(`${API_BASE}/login.php`, fd);
          // debug leve no console (não expõe senha)
          console.debug('login response status:', result.status);

          if (result.json && result.json.success) {
            this.feedback(this.loginFeedback, result.json.message || 'Login realizado!', 'success');
            const dest = result.json.redirect || REDIRECT_OK;
            setTimeout(() => (window.location.href = dest), 700);
            return;
          }

          // se servidor retornou JSON com mensagem, exibir; senão, usar texto bruto
          if (result.json && result.json.message) {
            this.feedback(this.loginFeedback, result.json.message);
          } else if (!result.ok) {
            // status não OK e sem JSON
            this.feedback(this.loginFeedback, `Erro ${result.status}: ${result.text || 'Resposta inválida do servidor.'}`);
          } else {
            // ok mas success false
            const parsed = result.json || {};
            this.feedback(this.loginFeedback, parsed.message || 'Email ou senha incorretos.');
          }

        } catch (err) {
          console.error('Erro no login fetch:', err);
          const msg = err.name === 'AbortError' ? 'Tempo de conexão esgotado.' : 'Falha ao conectar ao servidor.';
          this.feedback(this.loginFeedback, msg);
        } finally {
          btn.disabled = false;
        }
      });

      /* ===== REGISTER ===== */
      this.registerForm?.addEventListener('submit', async e => {
        e.preventDefault();

        const email = qs('regEmail')?.value.trim() || '';
        const senha = qs('regPassword')?.value || '';
        const conf = qs('regPasswordConfirm')?.value || '';
        const btn = e.target.querySelector('button[type="submit"]');

        if (!email || !senha || !conf) {
          return this.feedback(this.registerFeedback, 'Preencha todos os campos.');
        }
        if (!isValidEmail(email)) {
          return this.feedback(this.registerFeedback, 'E-mail inválido.');
        }
        if (senha.length < 6) {
          return this.feedback(this.registerFeedback, 'Senha mínima: 6 caracteres.');
        }
        if (senha !== conf) {
          return this.feedback(this.registerFeedback, 'As senhas não conferem.');
        }

        btn.disabled = true;

        const fd = new FormData();
        fd.append('email', email);
        fd.append('senha', senha);
        fd.append('senha_confirm', conf);

        try {
          const result = await this.postForm(`${API_BASE}/register.php`, fd);
          console.debug('register response status:', result.status);

          if (result.json && result.json.success) {
            this.feedback(this.registerFeedback, result.json.message || 'Conta criada com sucesso!', 'success');
            setTimeout(() => this.showLogin(), 1000);
            return;
          }

          if (result.json && result.json.message) {
            this.feedback(this.registerFeedback, result.json.message);
          } else if (!result.ok) {
            this.feedback(this.registerFeedback, `Erro ${result.status}: ${result.text || 'Resposta inválida do servidor.'}`);
          } else {
            this.feedback(this.registerFeedback, result.json?.message || 'Erro ao registrar.');
          }

        } catch (err) {
          console.error('Erro no register fetch:', err);
          const msg = err.name === 'AbortError' ? 'Tempo de conexão esgotado.' : 'Erro de conexão.';
          this.feedback(this.registerFeedback, msg);
        } finally {
          btn.disabled = false;
        }
      });
    },

    initFromURL() {
      const show = new URLSearchParams(location.search).get('show');
      show === 'register' ? this.showRegister() : this.showLogin();
    }
  };

  /* =========================
     INIT
  ========================= */
  document.addEventListener('DOMContentLoaded', () => {
    ThemeManager.init();
    FormManager.init();
    console.log('✅ Login carregado com sucesso');
  });

})();
