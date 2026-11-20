<?php
$page_title = "Painel";
$show_export_button = true;
include 'layouts/header.php';
?>

<!-- Loading overlay -->
<div id="loadingOverlay" class="loading-overlay" aria-hidden="true">
    <div class="loading-spinner"></div>
</div>

<!-- Main dashboard -->
<main id="mainContent" class="container py-4" role="main">
    <div class="dashboard-container">
        <div class="row g-0 h-100">

            <!-- ===================== SIDEBAR ===================== -->
            <aside
                id="sidebar"
                class="col-12 col-lg-3 sidebar"
                aria-labelledby="sidebarTitle"
                role="navigation">

                <div class="sidebar-content">

                    <!-- Brand -->
                    <div class="sidebar-brand">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="brand-icon" viewBox="0 0 16 16" aria-hidden="true">
                            <path fill-rule="evenodd" d="M11 15a4 4 0 1 0 0-8 4 4 0 0 0 0 8m5-4a5 5 0 1 1-10 0 5 5 0 0 1 10 0"/>
                            <path d="M9.438 11.944c.047.596.518 1.06 1.363 1.116v.44h.375v-.443c.875-.061 1.386-.529 1.386-1.207 0-.618-.39-.936-1.09-1.1l-.296-.07v-1.2c.376.043.614.248.671.532h.658c-.047-.575-.54-1.024-1.329-1.073V8.5h-.375v.45c-.747.073-1.255.522-1.255 1.158 0 .562.378.92 1.007 1.066l.248.061v1.272c-.384-.058-.639-.27-.696-.563h-.668zm1.36-1.354c-.369-.085-.569-.26-.569-.522 0-.294.216-.514.572-.578v1.1zm.432.746c.449.104.655.272.655.569 0 .339-.257.571-.709.614v-1.195z"/>
                            <path d="M1 0a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h4.083q.088-.517.258-1H3a2 2 0 0 0-2-2V3a2 2 0 0 0 2-2h10a2 2 0 0 0 2 2v3.528c.38.34.717.728 1 1.154V1a1 1 0 0 0-1-1z"/>
                        </svg>
                        <h2 id="sidebarTitle" class="brand-title">Finanças Já</h2>
                    </div>

                    <!-- Nav -->
                    <nav class="sidebar-nav" aria-label="Menu principal">
                        <a href="Gastos.php" class="nav-link" data-page="gastos">
                            <i class="bi bi-pie-chart" aria-hidden="true"></i>
                            <span>Meus Gastos</span>
                            <i class="bi bi-chevron-right nav-arrow" aria-hidden="true"></i>
                        </a>
                        <a href="historico.php" class="nav-link" data-page="historico">
                            <i class="bi bi-clock-history" aria-hidden="true"></i>
                            <span>Histórico</span>
                            <i class="bi bi-chevron-right nav-arrow" aria-hidden="true"></i>
                        </a>
                        <a href="metas.php" class="nav-link" data-page="metas">
                            <i class="bi bi-trophy" aria-hidden="true"></i>
                            <span>Metas</span>
                        </a>
                        <a href="vies_cognitivo.php" class="nav-link" data-page="vies">
                            <i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i>
                            <span>Viés Cognitivo</span>
                        </a>
                    </nav>

                    <!-- User -->
                    <div class="sidebar-footer">
                        <div class="user-profile" id="userProfileTrigger" role="button" tabindex="0" aria-label="Abrir configurações de perfil">
                            <div class="user-avatar">
                                <i class="bi bi-person-circle" aria-hidden="true"></i>
                            </div>
                            <div class="user-info">
                                <div class="user-name" id="displayUserName">Usuário</div>
                                <div class="user-status" id="sidebarPlanStatus">Plano Free</div>
                            </div>
                            <i class="bi bi-chevron-right ms-auto profile-arrow" aria-hidden="true"></i>
                        </div>
                        <a href="../php/logout.php" class="nav-link nav-link-logout">
                            <i class="bi bi-box-arrow-left" aria-hidden="true"></i>
                            <span>Sair</span>
                        </a>
                    </div>

                </div>
            </aside>

            <!-- ===================== CONTEÚDO PRINCIPAL ===================== -->
            <section class="col-12 col-lg-9 main-content" aria-labelledby="dashboardTitle">
                <div class="content-wrapper">

                    <!-- Header do dashboard -->
                    <header class="dashboard-header">
                        <div>
                            <h1 id="dashboardTitle" class="dashboard-title">
                                <span class="greeting-text">Olá,</span>
                                <span class="user-name-highlight" id="headerUserName">Usuário</span>
                            </h1>
                            <p class="dashboard-subtitle" id="currentDateTime">
                                <i class="bi bi-calendar3 me-2" aria-hidden="true"></i>
                                <span id="dateTimeText">Carregando...</span>
                            </p>
                        </div>
                        <button
                            id="refreshData"
                            class="btn btn-outline-secondary btn-sm"
                            aria-label="Atualizar dados"
                            type="button">
                            <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                        </button>
                    </header>

                    <!-- ── Cards de estatísticas ── -->
                    <div class="stats-grid">

                        <article class="stat-card stat-card-balance" role="article">
                            <div class="stat-icon">
                                <i class="bi bi-wallet2" aria-hidden="true"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-label">Saldo Atual</h3>
                                <div class="stat-value" data-target="0" aria-live="polite">R$ 0,00</div>
                                <div class="stat-change neutral">
                                    <i class="bi bi-dash" aria-hidden="true"></i>
                                    <span id="saldoChange">—</span>
                                </div>
                            </div>
                        </article>

                        <article class="stat-card stat-card-income" role="article">
                            <div class="stat-icon">
                                <i class="bi bi-arrow-down-circle" aria-hidden="true"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-label">Renda Mensal</h3>
                                <div class="stat-value" data-target="0" aria-live="polite">R$ 0,00</div>
                                <div class="stat-change neutral">
                                    <i class="bi bi-dash" aria-hidden="true"></i>
                                    <span>Mês atual</span>
                                </div>
                            </div>
                        </article>

                        <article class="stat-card stat-card-expenses" role="article">
                            <div class="stat-icon">
                                <i class="bi bi-arrow-up-circle" aria-hidden="true"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-label">Gastos Totais</h3>
                                <div class="stat-value" data-target="0" aria-live="polite">R$ 0,00</div>
                                <div class="stat-change negative">
                                    <i class="bi bi-arrow-down" aria-hidden="true"></i>
                                    <span>Mês atual</span>
                                </div>
                            </div>
                        </article>

                    </div><!-- /stats-grid -->

                    <!-- ── Gráfico + Legenda ── -->
                    <div class="charts-grid">

                        <!-- Gráfico de rosca -->
                        <article class="chart-card chart-card-primary" role="article">
                            <header class="chart-header">
                                <h2 class="chart-title">
                                    <i class="bi bi-pie-chart-fill me-2" aria-hidden="true"></i>
                                    Gastos por Categoria
                                </h2>
                            </header>
                            <div class="chart-body">
                                <div class="chart-container">
                                    <canvas
                                        id="pieChart"
                                        role="img"
                                        aria-label="Gráfico de rosca mostrando distribuição de gastos por categoria">
                                    </canvas>
                                </div>
                            </div>
                        </article>

                        <!-- Legenda lateral -->
                        <article class="chart-card chart-card-legend" role="article">
                            <header class="chart-header">
                                <h2 class="chart-title">
                                    <i class="bi bi-list-ul me-2" aria-hidden="true"></i>
                                    Categorias
                                </h2>
                            </header>
                            <div class="chart-body">
                                <ul class="legend-list" id="legendList" role="list"></ul>
                                <div class="legend-tip">
                                    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                                    Clique para ocultar/mostrar categorias
                                </div>
                            </div>
                        </article>

                    </div><!-- /charts-grid -->

                    <!-- ── Ações Rápidas ── -->
                    <div class="quick-actions">
                        <h3 class="section-title">Ações Rápidas</h3>
                        <div class="actions-grid">
                            <a href="Gastos.php" class="action-btn">
                                <i class="bi bi-plus-circle" aria-hidden="true"></i>
                                <span>Adicionar Gasto</span>
                            </a>
                            <a href="Gastos.php" class="action-btn">
                                <i class="bi bi-cash-coin" aria-hidden="true"></i>
                                <span>Registrar Renda</span>
                            </a>
                            <a href="historico.php" class="action-btn">
                                <i class="bi bi-graph-up-arrow" aria-hidden="true"></i>
                                <span>Ver Histórico</span>
                            </a>
                            <button class="action-btn" type="button" id="userProfileTrigger2">
                                <i class="bi bi-gear" aria-hidden="true"></i>
                                <span>Configurações</span>
                            </button>
                        </div>
                    </div>

                </div><!-- /content-wrapper -->
            </section>

        </div>
    </div>
</main>

<!-- ===================== MODAL — PERFIL ===================== -->
<div class="profile-modal" id="profileModal" role="dialog" aria-labelledby="profileModalTitle" aria-hidden="true">
    <div class="profile-modal-overlay" id="profileModalOverlay"></div>
    <div class="profile-modal-content">

        <div class="profile-modal-header">
            <h2 id="profileModalTitle" class="profile-modal-title">
                <i class="bi bi-person-gear me-2"></i>
                Configurações de Perfil
            </h2>
            <button type="button" class="profile-modal-close" id="profileModalClose" aria-label="Fechar modal">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="profile-modal-body">

            <!-- Avatar -->
            <div class="profile-section profile-avatar-section">
                <div class="profile-avatar-upload">
                    <div class="profile-avatar-preview">
                        <div class="avatar-initials" id="avatarInitials">?</div>
                    </div>
                    <div class="profile-avatar-info">
                        <div class="user-name-display" id="avatarDisplayName">Usuário</div>
                        <div class="user-plan-display"  id="avatarPlanDisplay">Plano Free</div>
                        <button type="button" class="btn btn-ghost btn-sm mt-2" disabled>
                            <i class="bi bi-upload me-2"></i>Alterar Foto (em breve)
                        </button>
                    </div>
                </div>
            </div>

            <!-- Informações pessoais -->
            <div class="profile-section">
                <h3 class="profile-section-title">
                    <i class="bi bi-person-badge me-2"></i>
                    Informações Pessoais
                </h3>
                <form id="profileForm" class="profile-form">
                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="profileName" class="form-label">
                                <i class="bi bi-person me-1"></i> Nome de usuário
                            </label>
                            <input type="text" class="form-control" id="profileName"
                                placeholder="Digite seu nome" maxlength="30">
                        </div>
                        <div class="form-group">
                            <label for="profilePhone" class="form-label">
                                <i class="bi bi-telephone me-1"></i> Telefone
                            </label>
                            <input type="tel" class="form-control" id="profilePhone"
                                placeholder="(00) 00000-0000">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="profileEmail" class="form-label">
                            <i class="bi bi-envelope me-1"></i> E-mail
                        </label>
                        <input type="email" class="form-control" id="profileEmail"
                            placeholder="seu@email.com">
                        <div class="form-hint">Usado para login e recuperação de senha</div>
                    </div>
                </form>
            </div>

            <!-- Preferências -->
            <div class="profile-section">
                <h3 class="profile-section-title">
                    <i class="bi bi-sliders me-2"></i>
                    Preferências
                </h3>
                <div class="profile-preferences">
                    <div class="preference-item">
                        <div class="preference-info">
                            <i class="bi bi-bell me-2"></i>
                            <div>
                                <div class="preference-title">Notificações</div>
                                <div class="preference-subtitle">Receba alertas sobre suas finanças</div>
                            </div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="notificacoesToggle" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="preference-item">
                        <div class="preference-info">
                            <i class="bi bi-envelope me-2"></i>
                            <div>
                                <div class="preference-title">E-mails Semanais</div>
                                <div class="preference-subtitle">Resumo semanal das suas finanças</div>
                            </div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="emailsSemanaisToggle">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Plano -->
            <div class="profile-section">
                <h3 class="profile-section-title">
                    <i class="bi bi-star me-2"></i>
                    Seu Plano
                </h3>
                <input type="hidden" id="selectedPlan" value="free">
                <div class="plans-grid-select" id="plansGridSelect">

                    <div class="plan-select-card selected" id="planCardFree" data-plan="free"
                         role="button" tabindex="0" aria-label="Selecionar Plano Free">
                        <div class="plan-card-header">
                            <span class="plan-select-badge plan-badge-free">Gratuito</span>
                            <div class="plan-checkmark" id="checkFree">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                        </div>
                        <div class="plan-select-name">Free</div>
                        <div class="plan-select-price">R$ 0,00<span>/mês</span></div>
                        <ul class="plan-select-features">
                            <li><i class="bi bi-check2"></i> Controle básico de gastos</li>
                            <li><i class="bi bi-check2"></i> Categorias padrão</li>
                            <li><i class="bi bi-check2"></i> Gráficos básicos</li>
                        </ul>
                    </div>

                    <div class="plan-select-card plan-card-premium-style" id="planCardPremium" data-plan="premium"
                         role="button" tabindex="0" aria-label="Selecionar Plano Premium">
                        <div class="plan-card-header">
                            <span class="plan-select-badge plan-badge-premium">
                                <i class="bi bi-star-fill me-1"></i>Premium
                            </span>
                            <div class="plan-checkmark" id="checkPremium">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                        </div>
                        <div class="plan-select-name">Premium</div>
                        <div class="plan-select-price">R$ 19,90<span>/mês</span></div>
                        <ul class="plan-select-features">
                            <li><i class="bi bi-check2"></i> Tudo do Free</li>
                            <li class="feat-premium"><i class="bi bi-check2"></i> Relatórios avançados</li>
                            <li class="feat-premium"><i class="bi bi-check2"></i> Exportação ilimitada</li>
                            <li class="feat-premium"><i class="bi bi-check2"></i> Metas com apostas</li>
                            <li class="feat-premium"><i class="bi bi-check2"></i> Suporte prioritário</li>
                        </ul>
                    </div>

                </div>
            </div>

            <!-- Segurança -->
            <div class="profile-section">
                <h3 class="profile-section-title">
                    <i class="bi bi-shield-lock me-2"></i>
                    Segurança
                </h3>
                <div class="profile-security-actions">
                    <button type="button" class="profile-action-item" id="changePasswordBtn">
                        <div class="profile-action-icon"><i class="bi bi-key"></i></div>
                        <div class="profile-action-content">
                            <div class="profile-action-title">Alterar Senha</div>
                            <div class="profile-action-subtitle">Última alteração há 2 meses</div>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    <button type="button" class="profile-action-item">
                        <div class="profile-action-icon"><i class="bi bi-shield-check"></i></div>
                        <div class="profile-action-content">
                            <div class="profile-action-title">Autenticação em Dois Fatores</div>
                            <div class="profile-action-subtitle">Adicione uma camada extra de segurança</div>
                        </div>
                        <span class="badge-soon">Em breve</span>
                    </button>
                </div>
            </div>

        </div><!-- /profile-modal-body -->

        <div class="profile-modal-footer">
            <button type="button" class="btn btn-ghost" id="cancelProfileBtn">Cancelar</button>
            <button type="button" class="btn btn-primary" id="saveProfileBtn">
                <i class="bi bi-check-lg me-2"></i>Salvar Alterações
            </button>
        </div>

    </div>
</div>
<!-- ===================== FIM MODAL PERFIL ===================== -->

<!-- ===================== MODAL — SENHA ===================== -->
<div class="profile-modal" id="passwordModal" role="dialog" aria-labelledby="passwordModalTitle" aria-hidden="true">
    <div class="profile-modal-overlay" id="passwordModalOverlay"></div>
    <div class="profile-modal-content profile-modal-small">

        <div class="profile-modal-header">
            <h2 id="passwordModalTitle" class="profile-modal-title">
                <i class="bi bi-key me-2"></i>
                Alterar Senha
            </h2>
            <button type="button" class="profile-modal-close" id="passwordModalClose" aria-label="Fechar modal">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="profile-modal-body">
            <form id="passwordForm" class="profile-form">
                <div class="form-group">
                    <label for="currentPassword" class="form-label">
                        <i class="bi bi-lock me-2"></i>Senha Atual
                    </label>
                    <input type="password" class="form-control" id="currentPassword"
                        placeholder="Digite sua senha atual" required>
                </div>
                <div class="form-group">
                    <label for="newPassword" class="form-label">
                        <i class="bi bi-lock-fill me-2"></i>Nova Senha
                    </label>
                    <input type="password" class="form-control" id="newPassword"
                        placeholder="Digite a nova senha" required minlength="6">
                    <div class="form-hint">Mínimo de 6 caracteres</div>
                </div>
                <div class="form-group">
                    <label for="confirmPassword" class="form-label">
                        <i class="bi bi-lock-fill me-2"></i>Confirmar Nova Senha
                    </label>
                    <input type="password" class="form-control" id="confirmPassword"
                        placeholder="Confirme a nova senha" required>
                </div>
                <div class="password-requirements">
                    <div class="requirement-title">Requisitos da senha:</div>
                    <div class="requirement-item" id="req-length">
                        <i class="bi bi-circle"></i>
                        <span>Mínimo de 6 caracteres</span>
                    </div>
                    <div class="requirement-item" id="req-match">
                        <i class="bi bi-circle"></i>
                        <span>As senhas devem corresponder</span>
                    </div>
                </div>
            </form>
        </div>

        <div class="profile-modal-footer">
            <button type="button" class="btn btn-ghost" id="cancelPasswordBtn">Cancelar</button>
            <button type="button" class="btn btn-primary" id="savePasswordBtn">
                <i class="bi bi-check-lg me-2"></i>Alterar Senha
            </button>
        </div>

    </div>
</div>
<!-- ===================== FIM MODAL SENHA ===================== -->

<?php if (file_exists(__DIR__ . '/chatbot.php')) include 'chatbot.php'; ?>

<!-- Toast -->
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" aria-live="polite" aria-atomic="true"></div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script type="module" src="../Java/menu.js"></script>

<script>
/* Atalho: botão "Configurações" nas Ações Rápidas também abre o modal */
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('userProfileTrigger2')
        ?.addEventListener('click', () => {
            document.getElementById('profileModal')?.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
});
</script>

</body>
</html>