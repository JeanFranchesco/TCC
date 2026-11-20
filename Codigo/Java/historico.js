// Histórico financeiro

// Dados de configuração
const MONTH_NAMES = [
    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
];

const EXPENSE_CATEGORIES = {
    'Alimentação': { icon: 'bi-basket', color: '#ff9100' },
    'Transporte': { icon: 'bi-car-front', color: '#2f86d6' },
    'Lazer': { icon: 'bi-controller', color: '#ffb03b' },
    'Saúde': { icon: 'bi-heart-pulse', color: '#ef4444' },
    'Educação': { icon: 'bi-book', color: '#8b5cf6' },
    'Moradia': { icon: 'bi-house', color: '#10b981' },
    'Outros': { icon: 'bi-three-dots', color: '#6b7280' }
};

// Utilitários
const Utils = {
    formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    },
    formatDate(dateStr) {
        const [year, month, day] = dateStr.split('-');
        return `${day}/${month}`;
    },
    showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
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
    }
};

// Gerenciador de tema
const ThemeManager = {
    currentTheme: 'dark',
    init() {
        this.currentTheme = localStorage.getItem('financas-theme') || 'dark';
        this.apply(this.currentTheme);
        document.getElementById('themeToggle').addEventListener('click', () => this.switch());
    },
    apply(theme) {
        this.currentTheme = theme;
        const html = document.documentElement;
        const icon = document.getElementById('themeIcon');
        if (theme === 'light') {
            html.classList.add('light-theme');
            icon.className = 'bi bi-sun';
        } else {
            html.classList.remove('light-theme');
            icon.className = 'bi bi-moon-stars';
        }
        if (window.annualChart) ChartManager.updateChartTheme();
    },
    switch() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        localStorage.setItem('financas-theme', newTheme);
        this.apply(newTheme);
        Utils.showToast(`Tema ${newTheme === 'dark' ? 'escuro' : 'claro'} ativado`, 'success');
    }
};

// Gerenciador do gráfico
const ChartManager = {
    chart: null,
    currentYear: new Date().getFullYear(),
    init() {
        const ctx = document.getElementById('annualChart').getContext('2d');
        this.createChart(ctx, []);
        this.setupYearNavigation();
        document.getElementById('currentYear').textContent = this.currentYear;
    },
    createChart(ctx, data) {
        const isDark = ThemeManager.currentTheme === 'dark';
        this.chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: MONTH_NAMES,
                datasets: [{
                    label: 'Gastos',
                    data: data.map(m => m.gastos || 0),
                    backgroundColor: isDark ? 'rgba(239, 68, 68, 0.8)' : 'rgba(239, 68, 68, 0.7)',
                    borderColor: '#ef4444',
                    borderWidth: 2,
                    borderRadius: 8,
                    hoverBackgroundColor: '#ef4444'
                }, {
                    label: 'Renda',
                    data: data.map(m => m.receitas || 0),
                    backgroundColor: isDark ? 'rgba(16, 185, 129, 0.8)' : 'rgba(16, 185, 129, 0.7)',
                    borderColor: '#10b981',
                    borderWidth: 2,
                    borderRadius: 8,
                    hoverBackgroundColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: isDark ? '#f5f5f7' : '#1a1a1d',
                            font: { size: 13, weight: '600' },
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        callbacks: {
                            label: (context) => `${context.dataset.label}: ${Utils.formatCurrency(context.parsed.y)}`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: isDark ? '#b8b8bb' : '#4a4a4e',
                            font: { size: 12 }
                        }
                    },
                    y: {
                        grid: {
                            color: isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.08)',
                            drawBorder: false
                        },
                        ticks: {
                            color: isDark ? '#b8b8bb' : '#4a4a4e',
                            font: { size: 12 },
                            callback: (value) => Utils.formatCurrency(value)
                        }
                    }
                },
                animation: {
                    duration: 800,
                    easing: 'easeInOutQuart'
                }
            }
        });
        window.annualChart = this.chart;
    },
    updateChartTheme() {
        if (!this.chart) return;
        const isDark = ThemeManager.currentTheme === 'dark';
        this.chart.data.datasets[0].backgroundColor = isDark ? 'rgba(239, 68, 68, 0.8)' : 'rgba(239, 68, 68, 0.7)';
        this.chart.data.datasets[1].backgroundColor = isDark ? 'rgba(16, 185, 129, 0.8)' : 'rgba(16, 185, 129, 0.7)';
        this.chart.options.plugins.legend.labels.color = isDark ? '#f5f5f7' : '#1a1a1d';
        this.chart.options.scales.x.ticks.color = isDark ? '#b8b8bb' : '#4a4a4e';
        this.chart.options.scales.y.ticks.color = isDark ? '#b8b8bb' : '#4a4a4e';
        this.chart.options.scales.y.grid.color = isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.08)';
        this.chart.update('none');
    },
    setupYearNavigation() {
        document.getElementById('prevYear').addEventListener('click', () => { this.currentYear--; this.updateYear(); });
        document.getElementById('nextYear').addEventListener('click', () => { this.currentYear++; this.updateYear(); });
    },
    async updateYear() {
        document.getElementById('currentYear').textContent = this.currentYear;
        await MonthsManager.loadAndRender(this.currentYear);
        Utils.showToast(`Ano alterado para ${this.currentYear}`, 'info');
    }
};

// Gerenciador de meses
const MonthsManager = {
    async loadAndRender(year) {
        const loadingOverlay = document.getElementById('loadingOverlay');
        loadingOverlay.classList.remove('hidden');

        try {
            const response = await fetch(`../php/get_historico.php?ano=${year}`);
            const result = await response.json();

            if (!result.success) throw new Error(result.message || 'Erro ao carregar histórico');

            const dadosAnuais = result.dados_anuais || [];

            // Atualiza o gráfico com as rendas de todos os meses
            ChartManager.chart.data.datasets[0].data = dadosAnuais.map(m => m.gastos || 0);
            ChartManager.chart.data.datasets[1].data = dadosAnuais.map(m => m.receitas || 0);
            ChartManager.chart.update();

            // Renderiza cartões dos 12 meses
            const grid = document.getElementById('monthsGrid');
            grid.innerHTML = dadosAnuais.map(monthData => {
                const monthIndex = monthData.mes - 1;
                const balance = (monthData.receitas || 0) - (monthData.gastos || 0);
                const isPositive = balance >= 0;
                const statusClass = isPositive ? 'positive' : 'negative';
                const statusIcon = isPositive ? 'bi-arrow-up-circle' : 'bi-arrow-down-circle';

                return `
                    <div class="month-card ${statusClass}" data-month="${monthIndex}">
                        <div class="month-header">
                            <div class="month-name">${MONTH_NAMES[monthIndex]}</div>
                            <div class="month-status"><i class="bi ${statusIcon}"></i></div>
                        </div>
                        <div class="month-balance">${Utils.formatCurrency(balance)}</div>
                        <div class="month-details">
                            <div class="month-detail-item">
                                <div class="month-detail-label">Renda</div>
                                <div class="month-detail-value">${Utils.formatCurrency(monthData.receitas || 0)}</div>
                            </div>
                            <div class="month-detail-item">
                                <div class="month-detail-label">Gastos</div>
                                <div class="month-detail-value">${Utils.formatCurrency(monthData.gastos || 0)}</div>
                            </div>
                        </div>
                    </div>`;
            }).join('');

            this.attachEventListeners();
        } catch (error) {
            Utils.showToast('Erro ao carregar dados anuais: ' + error.message, 'danger');
            console.error(error);
        } finally {
            loadingOverlay.classList.add('hidden');
        }
    },

    attachEventListeners() {
        document.querySelectorAll('.month-card').forEach(card => {
            card.addEventListener('click', () => {
                const monthIndex = parseInt(card.dataset.month);
                this.showMonthDetails(monthIndex);
            });
        });
    },

    async showMonthDetails(monthIndex) {
        const year = ChartManager.currentYear;
        const mes = monthIndex + 1;

        const loadingOverlay = document.getElementById('loadingOverlay');
        loadingOverlay.classList.remove('hidden');

        try {
            const response = await fetch(`../php/get_historico.php?ano=${year}&mes=${mes}`);
            const result = await response.json();

            if (!result.success) throw new Error(result.message || 'Erro ao carregar mês');

            const summary = result.summary;
            const transacoes = result.transacoes;

            document.getElementById('modalTitle').textContent = `${MONTH_NAMES[monthIndex]} ${year}`;

            const balance = summary.total_receitas - summary.total_gastos;
            document.getElementById('modalSummary').innerHTML = `
                <div class="summary-item income">
                    <div class="summary-label">Renda</div>
                    <div class="summary-value">${Utils.formatCurrency(summary.total_receitas)}</div>
                </div>
                <div class="summary-item expense">
                    <div class="summary-label">Gastos</div>
                    <div class="summary-value">${Utils.formatCurrency(summary.total_gastos)}</div>
                </div>
                <div class="summary-item balance">
                    <div class="summary-label">Saldo</div>
                    <div class="summary-value">${Utils.formatCurrency(balance)}</div>
                </div>`;

            let listHTML = '';

            transacoes.gastos.forEach(g => {
                const cat = EXPENSE_CATEGORIES[g.categoria_nome] || EXPENSE_CATEGORIES['Outros'];
                listHTML += `
                    <li class="expense-item">
                        <div class="expense-info">
                            <div class="expense-icon" style="color:${cat.color}">
                                <i class="bi ${cat.icon}"></i>
                            </div>
                            <div class="expense-details">
                                <div class="expense-category">${g.descricao} <small>(${g.categoria_nome || 'Sem categoria'})</small></div>
                                <div class="expense-date">${Utils.formatDate(g.data_gasto)}</div>
                            </div>
                        </div>
                        <div class="expense-amount text-danger">${Utils.formatCurrency(g.valor)}</div>
                    </li>`;
            });

            transacoes.receitas.forEach(r => {
                listHTML += `
                    <li class="expense-item">
                        <div class="expense-info">
                            <div class="expense-icon text-success">
                                <i class="bi bi-arrow-up-circle"></i>
                            </div>
                            <div class="expense-details">
                                <div class="expense-category">${r.descricao} <small>(Receita)</small></div>
                                <div class="expense-date">${Utils.formatDate(r.data_receita)}</div>
                            </div>
                        </div>
                        <div class="expense-amount text-success">${Utils.formatCurrency(r.valor)}</div>
                    </li>`;
            });

            if (listHTML === '') {
                listHTML = `<li class="text-center text-muted py-4">Nenhuma transação neste mês.</li>`;
            }

            document.getElementById('expenseList').innerHTML = listHTML;

            const modal = new bootstrap.Modal(document.getElementById('monthModal'));
            modal.show();

        } catch (error) {
            Utils.showToast('Erro ao carregar detalhes do mês: ' + error.message, 'danger');
            console.error(error);
        } finally {
            loadingOverlay.classList.add('hidden');
        }
    }
};

// Inicialização
document.addEventListener('DOMContentLoaded', async () => {
    ThemeManager.init();
    ChartManager.init();
    await MonthsManager.loadAndRender(ChartManager.currentYear);
});