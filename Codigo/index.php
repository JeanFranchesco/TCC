<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Finanças Já — Controle inteligente de gastos, renda e saldo com gráficos interativos e interface moderna.">
    <meta name="theme-color" content="#0a0a0b" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#f8f9fb" media="(prefers-color-scheme: light)">
    <title>Finanças Já — Controle suas finanças com inteligência</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Manrope:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="icon" href="assets/icons/favicon.ico" type="image/x-icon">

    <!-- External CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <a class="skip-link" href="#main">Pular para o conteúdo principal</a>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M11 15a4 4 0 1 0 0-8 4 4 0 0 0 0 8m5-4a5 5 0 1 1-10 0 5 5 0 0 1 10 0"/>
                    <path d="M9.438 11.944c.047.596.518 1.06 1.363 1.116v.44h.375v-.443c.875-.061 1.386-.529 1.386-1.207 0-.618-.39-.936-1.09-1.1l-.296-.07v-1.2c.376.043.614.248.671.532h.658c-.047-.575-.54-1.024-1.329-1.073V8.5h-.375v.45c-.747.073-1.255.522-1.255 1.158 0 .562.378.92 1.007 1.066l.248.061v1.272c-.384-.058-.639-.27-.696-.563h-.668zm1.36-1.354c-.369-.085-.569-.26-.569-.522 0-.294.216-.514.572-.578v1.1zm.432.746c.449.104.655.272.655.569 0 .339-.257.571-.709.614v-1.195z"/>
                    <path d="M1 0a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h4.083q.088-.517.258-1H3a2 2 0 0 0-2-2V3a2 2 0 0 0 2-2h10a2 2 0 0 0 2 2v3.528c.38.34.717.728 1 1.154V1a1 1 0 0 0-1-1z"/>
                </svg>
                Finanças Já
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                    <a href="FrontEnd/login.php" class="btn btn-outline-warning">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Entrar
                    </a>
                    <a href="FrontEnd/login.php?show=register" class="btn btn-warning">
                        <i class="bi bi-person-plus"></i>
                        Criar Conta
                    </a>
                    <button id="themeToggle" class="btn btn-outline-warning" aria-label="Alternar tema">
                        <i class="bi bi-moon-stars"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <main id="main" class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>
                    Controle suas finanças com
                    <span class="highlight">inteligência</span>
                </h1>
                <p>Visualize seus gastos, renda e saldo em gráficos interativos. Simples, rápido e 100% gratuito.</p>
                <div class="hero-buttons">
                    <a href="FrontEnd/login.php?show=register" class="btn btn-warning">
                        <i class="bi bi-rocket-takeoff"></i>
                        Começar Gratuitamente
                    </a>
                    <a href="#benefits" class="btn btn-outline-warning">
                        <i class="bi bi-play-circle"></i>
                        Ver Benefícios
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Stats -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value" data-count="1000">0</div>
                    <div class="stat-label">Usuários Ativos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" data-count="50000">0</div>
                    <div class="stat-label">Transações Registradas</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">100%</div>
                    <div class="stat-label">Gratuito</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits -->
    <section id="benefits" class="benefits">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Por que escolher o Finanças Já?</h2>
                <p class="section-subtitle">Ferramentas poderosas para você ter controle total das suas finanças</p>
            </div>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <h3>Gráficos Interativos</h3>
                    <p>Visualize seus gastos de forma clara com gráficos dinâmicos, intuitivos e fáceis de entender. Tome decisões financeiras baseadas em dados.</p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="bi bi-lightning-charge"></i>
                    </div>
                    <h3>Rápido e Intuitivo</h3>
                    <p>Interface moderna e responsiva que facilita o controle diário das suas finanças. Registre transações em segundos.</p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h3>Seguro e Privado</h3>
                    <p>Seus dados são criptografados e protegidos. Garantimos total privacidade e segurança das suas informações financeiras.</p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="bi bi-phone"></i>
                    </div>
                    <h3>Multi-Plataforma</h3>
                    <p>Acesse de qualquer dispositivo: computador, tablet ou smartphone. Suas finanças sempre ao seu alcance.</p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h3>Histórico Completo</h3>
                    <p>Acompanhe o histórico de todas as suas transações e visualize a evolução dos seus gastos ao longo do tempo.</p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="bi bi-gift"></i>
                    </div>
                    <h3>100% Gratuito</h3>
                    <p>Sem taxas ocultas, sem limitações. Todas as funcionalidades disponíveis gratuitamente para sempre.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Charts -->
    <section class="charts-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Visualize suas finanças</h2>
                <p class="section-subtitle">Gráficos claros e informativos para você acompanhar seus gastos</p>
            </div>
            <div class="charts-grid">
                <div class="chart-card">
                    <h3>
                        <i class="bi bi-calendar-week"></i>
                        Gastos Semanais
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="graficoSemanal"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h3>
                        <i class="bi bi-calendar-month"></i>
                        Gastos Mensais
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="graficoMensal"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Comece agora a controlar suas finanças</h2>
                <p>Junte-se a milhares de usuários que já estão no controle do seu dinheiro</p>
                <a href="FrontEnd/login.php?show=register" class="btn btn-warning btn-lg animate-pulse">
                    <i class="bi bi-rocket-takeoff"></i>
                    Criar Conta Gratuita
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <small>
                Feito com <i class="bi bi-heart-fill" style="color: var(--color-accent-primary);"></i> por 
                <a href="#" target="_blank">xQxMech</a> • 
                <a href="#">Contato</a> • 
                <a href="#" target="_blank">GitHub</a> • 
                <span id="year"></span>
            </small>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="script.js"></script>
</body>
</html>