<?php
// Página de histórico

$page_title = 'Histórico';
$show_export_button = false; // Oculta o botão Exportar

include 'layouts/header.php';
?>

<!-- CSS desta página -->
<link rel="stylesheet" href="../CSS/historico.css" />

<!-- Tela de carregamento -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner"></div>
</div>

<!-- Conteúdo principal -->
<main class="main-content container mt-4">
    <!-- Cabeçalho da página -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="bi bi-clock-history"></i>
            Histórico Financeiro
        </h1>
        <p class="page-subtitle">Acompanhe seus gastos e receitas ao longo do ano</p>
    </div>

    <!-- Seletor de ano -->
    <div class="year-selector">
        <button id="prevYear" type="button">
            <i class="bi bi-chevron-left"></i>
        </button>
        <div class="year-display" id="currentYear"></div>
        <button id="nextYear" type="button">
            <i class="bi bi-chevron-right"></i>
        </button>
    </div>

    <!-- Gráfico anual -->
    <div class="chart-card">
        <div class="chart-header">
            <h2 class="chart-title">
                <i class="bi bi-graph-up"></i>
                Gastos Anuais
            </h2>
        </div>
        <div class="chart-container">
            <canvas id="annualChart"></canvas>
        </div>
    </div>

    <!-- Grade de meses -->
    <div class="months-section">
        <h2 class="section-title">
            <i class="bi bi-calendar-month"></i>
            Visão Mensal
        </h2>
        <div class="months-grid" id="monthsGrid">
            <!-- Meses inseridos via JavaScript -->
        </div>
    </div>
</main>

<!-- Modal de detalhes do mês -->
<div class="modal fade" id="monthModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Janeiro 2024</h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="modal-summary" id="modalSummary">
                    <!-- Resumo inserido via JavaScript -->
                </div>
                <h4 class="mb-3">Transações do Mês</h4>
                <ul class="expense-list" id="expenseList">
                    <!-- Transações inseridas via JavaScript -->
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Container de toasts -->
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="../Java/historico.js"></script>

</body>
</html>