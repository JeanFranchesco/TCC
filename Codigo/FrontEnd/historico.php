<?php
// ================================================
// HISTÓRICO.PHP - Versão padronizada
// ================================================

$page_title = 'Histórico';
$show_export_button = false;   // ← botão Exportar fica escondido nesta página

include 'layouts/header.php';
?>

<!-- CSS específico desta página -->
<link rel="stylesheet" href="../CSS/historico.css" />

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner"></div>
</div>

<!-- Main Content -->
<main class="main-content container mt-4">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="bi bi-clock-history"></i>
            Histórico Financeiro
        </h1>
        <p class="page-subtitle">Acompanhe seus gastos e receitas ao longo do ano</p>
    </div>

    <!-- Year Selector -->
    <div class="year-selector">
        <button id="prevYear" type="button">
            <i class="bi bi-chevron-left"></i>
        </button>
        <div class="year-display" id="currentYear"></div>
        <button id="nextYear" type="button">
            <i class="bi bi-chevron-right"></i>
        </button>
    </div>

    <!-- Annual Chart -->
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

    <!-- Months Grid -->
    <div class="months-section">
        <h2 class="section-title">
            <i class="bi bi-calendar-month"></i>
            Visão Mensal
        </h2>
        <div class="months-grid" id="monthsGrid">
            <!-- Months will be inserted here by JavaScript -->
        </div>
    </div>
</main>

<!-- Month Details Modal -->
<div class="modal fade" id="monthModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Janeiro 2024</h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="modal-summary" id="modalSummary">
                    <!-- Summary will be inserted here -->
                </div>
                <h4 class="mb-3">Transações do Mês</h4>
                <ul class="expense-list" id="expenseList">
                    <!-- Expenses will be inserted here -->
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="../Java/historico.js"></script>

</body>
</html>