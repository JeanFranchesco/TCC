<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0a0b" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#f8f9fb" media="(prefers-color-scheme: light)">
    <title>Login | Finanças</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Manrope:wght@400;600;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/login.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M11 15a4 4 0 1 0 0-8 4 4 0 0 0 0 8m5-4a5 5 0 1 1-10 0 5 5 0 0 1 10 0"/>
                    <path d="M9.438 11.944c.047.596.518 1.06 1.363 1.116v.44h.375v-.443c.875-.061 1.386-.529 1.386-1.207 0-.618-.39-.936-1.09-1.1l-.296-.07v-1.2c.376.043.614.248.671.532h.658c-.047-.575-.54-1.024-1.329-1.073V8.5h-.375v.45c-.747.073-1.255.522-1.255 1.158 0 .562.378.92 1.007 1.066l.248.061v1.272c-.384-.058-.639-.27-.696-.563h-.668zm1.36-1.354c-.369-.085-.569-.26-.569-.522 0-.294.216-.514.572-.578v1.1zm.432.746c.449.104.655.272.655.569 0 .339-.257.571-.709.614v-1.195z"/>
                    <path d="M1 0a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h4.083q.088-.517.258-1H3a2 2 0 0 0-2-2V3a2 2 0 0 0 2-2h10a2 2 0 0 0 2 2v3.528c.38.34.717.728 1 1.154V1a1 1 0 0 0-1-1z"/>
                </svg>
                Finanças Já
            </a>
            <div class="d-flex align-items-center gap-2">
                <a href="../index.php" class="btn btn-outline-warning">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-sm-inline">Voltar</span>
                </a>
                <button id="themeToggle" class="btn btn-outline-warning" aria-label="Alternar tema">
                    <i class="bi bi-moon-stars"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="page-center">
        <div class="login-container">
            <div class="row g-0">
                <!-- Left Side -->
                <div class="col-12 col-md-6 login-left">
                    <div class="login-left-content">
                        <div class="feature-icon">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <h2>Organize suas finanças com praticidade</h2>
                        <p>Controle seus gastos, acompanhe sua renda e alcance seus objetivos financeiros de forma simples e eficiente.</p>
                        <ul class="features-list">
                            <li>
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Gráficos interativos e intuitivos</span>
                            </li>
                            <li>
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Controle total dos seus gastos</span>
                            </li>
                            <li>
                                <i class="bi bi-check-circle-fill"></i>
                                <span>100% gratuito e seguro</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Right Side -->
                <div class="col-12 col-md-6 login-right">
                    <div class="logo-section">
                        <svg class="logo-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M11 15a4 4 0 1 0 0-8 4 4 0 0 0 0 8m5-4a5 5 0 1 1-10 0 5 5 0 0 1 10 0"/>
                            <path d="M9.438 11.944c.047.596.518 1.06 1.363 1.116v.44h.375v-.443c.875-.061 1.386-.529 1.386-1.207 0-.618-.39-.936-1.09-1.1l-.296-.07v-1.2c.376.043.614.248.671.532h.658c-.047-.575-.54-1.024-1.329-1.073V8.5h-.375v.45c-.747.073-1.255.522-1.255 1.158 0 .562.378.92 1.007 1.066l.248.061v1.272c-.384-.058-.639-.27-.696-.563h-.668zm1.36-1.354c-.369-.085-.569-.26-.569-.522 0-.294.216-.514.572-.578v1.1zm.432.746c.449.104.655.272.655.569 0 .339-.257.571-.709.614v-1.195z"/>
                            <path d="M1 0a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h4.083q.088-.517.258-1H3a2 2 0 0 0-2-2V3a2 2 0 0 0 2-2h10a2 2 0 0 0 2 2v3.528c.38.34.717.728 1 1.154V1a1 1 0 0 0-1-1z"/>
                        </svg>
                        <div class="brand-title">Finanças Já</div>
                    </div>


          <!-- Login Form (envio via JS) -->
          <form id="loginForm" class="form-wrapper" novalidate>
            <div id="loginFeedback" class="visually-hidden" role="alert" aria-live="polite"></div>

            <div class="form-group">
              <label for="loginEmail" class="form-label">Email</label>
              <input id="loginEmail" name="email" type="email" class="form-control" placeholder="seu@email.com"
                     autocomplete="email" required>
            </div>

            <div class="form-group">
              <label for="loginPassword" class="form-label">Senha</label>
              <div style="position: relative;">
                <input id="loginPassword" name="senha" type="password" class="form-control" placeholder="••••••••"
                       autocomplete="current-password" required>
                <button type="button" class="password-toggle" data-target="loginPassword" aria-label="Mostrar senha">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <div class="btn-group-form">
              <button type="submit" class="btn btn-warning">
                <i class="bi bi-box-arrow-in-right"></i> Entrar
              </button>
              <button type="button" id="showRegister" class="btn btn-outline-warning">
                <i class="bi bi-person-plus"></i> Criar Conta
              </button>
            </div>

            <a href="#" class="small-link">Esqueceu sua senha?</a>
          </form>

<!-- Register Form (envio via JS) -->
          <form id="registerForm" class="form-wrapper d-none" novalidate>
            <h4 class="form-title">Criar Conta</h4>
            <div id="registerFeedback" class="visually-hidden" role="alert" aria-live="polite"></div>

            <div class="form-group">
              <label for="regEmail" class="form-label">Email</label>
              <input id="regEmail" name="email" type="email" class="form-control" placeholder="seu@email.com"
                     autocomplete="email" required>
            </div>

            <div class="form-group">
              <label for="regPassword" class="form-label">Senha</label>
              <div style="position: relative;">
                <input id="regPassword" name="senha" type="password" class="form-control" placeholder="Mínimo 6 caracteres"
                       autocomplete="new-password" minlength="6" required>
                <button type="button" class="password-toggle" data-target="regPassword" aria-label="Mostrar senha">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <div class="form-group">
              <label for="regPasswordConfirm" class="form-label">Confirme sua senha</label>
              <div style="position: relative;">
                <input id="regPasswordConfirm" name="senha_confirm" type="password" class="form-control"
                       placeholder="Digite a senha novamente" autocomplete="new-password" minlength="6" required>
                <button type="button" class="password-toggle" data-target="regPasswordConfirm" aria-label="Mostrar senha">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <div class="btn-group-form">
              <button type="submit" class="btn btn-warning">
                <i class="bi bi-person-plus"></i> Criar Conta
              </button>
              <button type="button" id="showLogin" class="btn btn-outline-warning">
                <i class="bi bi-arrow-left"></i> Voltar
              </button>
            </div>

            <a href="#" class="small-link" id="alreadyHaveAccount">Já tem uma conta? Entre aqui</a>
          </form>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Java/login.js"></script>
</body>
</html>