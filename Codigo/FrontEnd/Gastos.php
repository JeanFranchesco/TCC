<?php
session_start();

/*
 * Login temporário para desenvolvimento.
 * Sem sessão ativa, o visitante vira o usuário 1.
 * Antes de publicar, substituir por redirecionamento para a tela de login.
 */
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['id_usuario'] = 1;
}

$page_title = 'Meus Gastos';
$show_export_button = false;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Registre e acompanhe seus gastos mensais com análise inteligente e dicas personalizadas.">
    <meta name="theme-color" content="#ff9100">
    <title>Finanças Já • <?php echo htmlspecialchars($page_title); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../CSS/variables.css">
    <link rel="stylesheet" href="../CSS/base.css">
    <link rel="stylesheet" href="../CSS/menu.css">
    <link rel="stylesheet" href="../CSS/Gastos.css">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
</head>
<body>

<!-- Cabeçalho -->
<header class="top-header" role="banner">
    <div class="max-w-7xl mx-auto px-8 py-4">
        <div class="flex justify-between items-center">

            <div class="flex items-center gap-3">
                <button id="sidebarToggle" class="btn btn-ghost lg:hidden text-2xl">
                    <i class="bi bi-list"></i>
                </button>
                <a href="menu.php" class="flex items-center gap-2">
                    <div class="w-9 h-9 bg-[#ff9100] rounded-2xl flex items-center justify-center text-white text-3xl font-bold shadow-inner">$</div>
                    <span class="text-[#ff9100] font-semibold text-3xl tracking-tighter">Finanças Já</span>
                </a>
            </div>

            <div class="flex items-center gap-3">
                <button id="themeToggle" class="w-10 h-10 rounded-2xl bg-white/10 hover:bg-white/20 flex items-center justify-center text-xl transition">
                    <i class="bi bi-moon-stars" id="themeIcon"></i>
                </button>
            </div>

        </div>
    </div>
</header>

<!-- Conteúdo principal -->
<main id="mainContent" class="container py-2" role="main">
    <div class="dashboard-card mx-auto">
        <div class="content-wrapper p-4">

            <h2 class="greeting mb-4">Registrar meus gastos</h2>

            <!-- Cards de resumo -->
            <section aria-labelledby="resumoTitle">
                <h3 id="resumoTitle" class="visually-hidden">Resumo financeiro</h3>
                <div class="row g-3 mb-4">

                    <!-- Saldo atual -->
                    <div class="col-12 col-md-4">
                        <div class="stat-card" id="cardSaldo" role="status" aria-live="polite" aria-atomic="true">
                            <small class="muted">SALDO ATUAL</small>
                            <div id="saldoAtual" class="stat-value" aria-label="Saldo atual">R$ 0,00</div>
                        </div>
                    </div>

                    <!-- Renda mensal -->
                    <div class="col-12 col-md-4">
                        <div class="stat-card">
                            <small class="muted">RENDA MENSAL</small>
                            <div class="d-flex gap-2 mt-2">
                                <label for="rendaInput" class="visually-hidden">Valor da renda mensal</label>
                                <input
                                    id="rendaInput"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    placeholder="R$ 0,00"
                                    aria-label="Digite sua renda mensal"
                                    inputmode="decimal">
                                <!-- Começa como "Salvar"; gerenciadorInterface.js troca para "Alterar" quando já há renda salva -->
                                <button id="saveRenda" class="btn btn-warning" type="button">
                                    <span>Salvar</span>
                                    <span class="visually-hidden">renda mensal</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Gastos totais -->
                    <div class="col-12 col-md-4">
                        <div class="stat-card" role="status" aria-live="polite" aria-atomic="true">
                            <small class="muted">GASTOS TOTAIS</small>
                            <div id="gastosTotal" class="stat-value" aria-label="Total de gastos">R$ 0,00</div>
                        </div>
                    </div>

                </div>
            </section>

            <!-- Regra 50/30/20 -->
            <div id="regra503020Container" class="mb-4" style="display:none;">
                <div class="regra-card">

                    <!-- Cabeçalho -->
                    <div class="regra-header">
                        <div class="regra-header-left">
                            <i class="bi bi-pie-chart-fill regra-icon"></i>
                            <div>
                                <div class="regra-title">Regra 50/30/20</div>
                                <div class="regra-subtitle">Distribuição do seu mês atual</div>
                            </div>
                        </div>
                        <span id="status503020" class="regra-status-badge"></span>
                    </div>

                    <!-- Colunas -->
                    <div class="regra-cols">

                        <!-- Necessidades -->
                        <div class="regra-col">
                            <div class="regra-col-header">
                                <span class="regra-dot" style="background:#ff6b6b;"></span>
                                <span class="regra-col-name" style="color:#ff6b6b;">Necessidades</span>
                            </div>
                            <div id="pNecessidades" class="regra-percent">0%</div>
                            <div class="regra-bar-track">
                                <div id="barraNecessidades" class="regra-bar-fill" role="progressbar"
                                     style="width:0%;background:#ff6b6b;"
                                     aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="regra-meta">
                                <i class="bi bi-bullseye me-1"></i>meta ≤ <strong>50%</strong>
                            </div>
                            <div class="regra-desc">Aluguel, contas, alimentação</div>
                        </div>

                        <div class="regra-divider"></div>

                        <!-- Desejos -->
                        <div class="regra-col">
                            <div class="regra-col-header">
                                <span class="regra-dot" style="background:#ffc107;"></span>
                                <span class="regra-col-name" style="color:#ffc107;">Desejos</span>
                            </div>
                            <div id="pDesejos" class="regra-percent">0%</div>
                            <div class="regra-bar-track">
                                <div id="barraDesejos" class="regra-bar-fill" role="progressbar"
                                     style="width:0%;background:#ffc107;"
                                     aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="regra-meta">
                                <i class="bi bi-bullseye me-1"></i>meta ≤ <strong>30%</strong>
                            </div>
                            <div class="regra-desc">Lazer, roupas, streaming</div>
                        </div>

                        <div class="regra-divider"></div>

                        <!-- Poupança -->
                        <div class="regra-col">
                            <div class="regra-col-header">
                                <span class="regra-dot" style="background:#51cf66;"></span>
                                <span class="regra-col-name" style="color:#51cf66;">Poupança</span>
                            </div>
                            <div id="pPoupanca" class="regra-percent">0%</div>
                            <div class="regra-bar-track">
                                <div id="barraPoupanca" class="regra-bar-fill" role="progressbar"
                                     style="width:0%;background:#51cf66;"
                                     aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="regra-meta">
                                <i class="bi bi-bullseye me-1"></i>meta ≥ <strong>20%</strong>
                            </div>
                            <div class="regra-desc">Reserva, investimentos</div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Informações da casa -->
            <section class="content-card p-4 mb-4" aria-labelledby="casaTitle">
                <h3 id="casaTitle" class="text-orange mb-3 fw-bold h5">
                    <i class="bi bi-house-door me-2" aria-hidden="true"></i>
                    Informações da casa
                </h3>
                <div class="info-row">
                    <fieldset>
                        <legend class="legend-text mb-2">Você vive sozinha?</legend>
                        <div class="btn-group" role="group" aria-label="Opções de moradia">
                            <input type="radio" class="btn-check" name="sozinha" id="sozinhaSim" checked>
                            <label class="btn btn-outline-warning" for="sozinhaSim">Sim</label>
                            <input type="radio" class="btn-check" name="sozinha" id="sozinhaNao">
                            <label class="btn btn-outline-warning" for="sozinhaNao">Não</label>
                        </div>
                    </fieldset>
                    <div id="numPessoasWrap" class="mt-3 mt-md-0" style="display:none;">
                        <label for="numPessoas" class="visually-hidden">Número de pessoas na casa</label>
                        <input
                            id="numPessoas"
                            type="number"
                            min="2" max="20" value="2"
                            class="form-control"
                            placeholder="Quantas pessoas?"
                            aria-label="Digite o número de pessoas na casa"
                            inputmode="numeric">
                    </div>
                </div>
            </section>

            <!-- Formulário de novo gasto -->
            <section class="content-card p-4 mb-4" aria-labelledby="addGastoTitle">
                <h3 id="addGastoTitle" class="text-orange mb-3 fw-bold h5">
                    <i class="bi bi-plus-circle me-2" aria-hidden="true"></i>
                    Adicionar gasto
                </h3>
                <form id="gastoForm" class="row g-3" novalidate>

                    <div class="col-12 col-md-3">
                        <label for="categoria" class="form-label">
                            <i class="bi bi-tag me-1"></i> Categoria
                        </label>
<select id="categoria" class="form-select" required>

    <option value="">Selecione uma categoria</option>

    <option value="1">🍽 Alimentação</option>

    <option value="2">🏠 Moradia</option>

    <option value="3">🚌 Transporte</option>

    <option value="4">🏥 Saúde</option>

    <option value="5">🎓 Educação</option>

    <option value="6">🎮 Lazer</option>

    <option value="7">🛍 Compras</option>

    <option value="8">💻 Tecnologia</option>

    <option value="9">📺 Assinaturas</option>

    <option value="10">🛠 Serviços</option>

    <option value="11">📄 Impostos</option>

    <option value="12">📈 Investimentos</option>

    <option value="13">🐶 Animais</option>

    <option value="14">🎁 Presentes</option>

    <option value="15">📦 Outros</option>

</select>
                    </div>

                    <div class="col-12 col-md-3 d-none" id="wrapCategoriaNova">
                        <label for="categoria_nova" class="form-label">
                            <i class="bi bi-plus-circle me-1"></i> Nova categoria
                        </label>
                        <input id="categoria_nova" type="text" class="form-control" placeholder="Ex: Internet" aria-label="Nova categoria">
                    </div>

                    <div class="col-12 col-md-5">
                        <label for="descricao" class="form-label">
                            <i class="bi bi-pencil me-1"></i> Descrição
                        </label>
                        <input id="descricao" type="text" class="form-control" placeholder="Ex: Conta de luz" maxlength="100" required>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="valor" class="form-label">
                            <i class="bi bi-currency-dollar me-1"></i> Valor (R$)
                        </label>
                        <input id="valor" type="number" step="0.01" min="0.01" class="form-control" placeholder="0,00" inputmode="decimal" required>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="tipoGasto" class="form-label">
                            <i class="bi bi-arrow-repeat me-1"></i> Tipo
                        </label>
                        <select id="tipoGasto" class="form-select">
                            <option value="variavel" selected>Variável</option>
                            <option value="fixo">Fixo</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="formaPagamento" class="form-label">
                            <i class="bi bi-credit-card me-1"></i> Pagamento
                        </label>
                        <select id="formaPagamento" class="form-select">
                            <option value="dinheiro">Dinheiro</option>
                            <option value="cartao" selected>Cartão</option>
                            <option value="pix">PIX</option>
                            <option value="boleto">Boleto</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="dataGasto" class="form-label">
                            <i class="bi bi-calendar-event me-1"></i> Data do gasto
                        </label>
                        <input id="dataGasto" type="date" class="form-control" required>
                    </div>

                    <div class="col-12 col-md-2">
                        <label for="recorrente" class="form-label d-block">
                            <i class="bi bi-arrow-clockwise me-1"></i> Recorrente
                        </label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="recorrente" role="switch">
                            <label class="form-check-label visually-hidden" for="recorrente">Gasto recorrente</label>
                        </div>
                    </div>

                    <div class="col-12 col-md-8">
                        <label for="observacao" class="form-label">
                            <i class="bi bi-journal-text me-1"></i> Observações (opcional)
                        </label>
                        <input id="observacao" type="text" class="form-control" placeholder="Adicione uma nota sobre este gasto" maxlength="200">
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label d-block opacity-0">Ação</label>
                        <button id="adicionarGasto" class="btn btn-warning w-100" type="submit">
                            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
                            <span>Adicionar</span>
                        </button>
                    </div>

                </form>
            </section>

            <!-- Gráfico -->
            <section class="chart-card p-4 mb-4" aria-labelledby="chartTitle">
                <h3 id="chartTitle" class="text-orange mb-3 h5">
                    <i class="bi bi-pie-chart-fill me-2" aria-hidden="true"></i>
                    Distribuição de gastos
                </h3>
                <div class="chart-wrapper">
                    <div id="chartContainer" class="chart-canvas-wrapper" role="img" aria-label="Gráfico de distribuição de gastos por categoria">
                        <div class="chart-empty">
                            <i class="bi bi-graph-up" aria-hidden="true"></i>
                            <p>Adicione gastos para visualizar o gráfico</p>
                        </div>
                    </div>
                </div>
                <div id="categoryStats" class="stats-grid" style="display:none;" role="region" aria-label="Estatísticas por categoria"></div>
            </section>

            <!-- Tabela de gastos -->
            <section class="content-card p-4" aria-labelledby="listaTitle">
                <h3 id="listaTitle" class="text-orange mb-3 fw-bold h5">
                    <i class="bi bi-list-ul me-2" aria-hidden="true"></i>
                    Lista de gastos
                </h3>
                <p class="info-text small mb-3">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                    <strong>Legenda:</strong> 🟢 Excelente • 🔵 Bom • 🟡 Atenção • 🔴 Crítico • Clique na descrição para ver dicas
                </p>
                <div class="table-responsive-card">
                    <table class="data-table" aria-label="Tabela de gastos registrados">
                        <thead>
                            <tr>
                                <th scope="col" style="width:60px;">Status</th>
                                <th scope="col">Categoria</th>
                                <th scope="col">Descrição</th>
                                <th scope="col">Tipo</th>
                                <th scope="col">Pagamento</th>
                                <th scope="col">Data</th>
                                <th scope="col" class="text-end is-num">Valor</th>
                                <th scope="col" class="text-end actions" style="width:100px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="listaGastos">
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-wallet2" style="font-size:3rem;opacity:0.3;"></i>
                                    <p class="mb-0 mt-2">Nenhum gasto adicionado ainda</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </div>
</main>

<!-- Modal de dicas -->
<div class="modal fade" id="tipsModal" tabindex="-1" aria-labelledby="tipsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title text-orange h5" id="tipsModalLabel">
                    <i class="bi bi-lightbulb me-2" aria-hidden="true"></i>
                    Dicas para este gasto
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="modalBody" role="document"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-warning" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de edição -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title text-orange h5" id="editModalLabel">
                    <i class="bi bi-pencil-square me-2" aria-hidden="true"></i>
                    Editar gasto
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="editForm" class="row g-3">
                    <input type="hidden" id="editId">
                    <div class="col-12 col-md-6">
                        <label for="editCategoria" class="form-label">Categoria</label>
                        <select id="editCategoria" class="form-select" required>
                            <option value="1">🍽 Alimentação</option>
                            <option value="2">🏠 Moradia</option>
                            <option value="3">🚌 Transporte</option>
                            <option value="4">🏥 Saúde</option>
                            <option value="5">🎓 Educação</option>
                            <option value="6">🎮 Lazer</option>
                            <option value="7">🛍 Compras</option>
                            <option value="8">💻 Tecnologia</option>
                            <option value="9">📺 Assinaturas</option>
                            <option value="10">🛠 Serviços</option>
                            <option value="11">📄 Impostos</option>
                            <option value="12">📈 Investimentos</option>
                            <option value="13">🐶 Animais</option>
                            <option value="14">🎁 Presentes</option>
                            <option value="15">📦 Outros</option>
                            <option value="nova">Outra / Adicionar</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="editDescricao" class="form-label">Descrição</label>
                        <input type="text" class="form-control" id="editDescricao" required maxlength="100">
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="editValor" class="form-label">Valor (R$)</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="editValor" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="editTipo" class="form-label">Tipo</label>
                        <select id="editTipo" class="form-select">
                            <option value="variavel">Variável</option>
                            <option value="fixo">Fixo</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="editFormaPagamento" class="form-label">Pagamento</label>
                        <select id="editFormaPagamento" class="form-select">
                            <option value="dinheiro">Dinheiro</option>
                            <option value="cartao">Cartão</option>
                            <option value="pix">PIX</option>
                            <option value="boleto">Boleto</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="editData" class="form-label">Data do gasto</label>
                        <input type="date" class="form-control" id="editData" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="editRecorrente" class="form-label d-block">Recorrente</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="editRecorrente">
                            <label class="form-check-label" for="editRecorrente">Gasto recorrente</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="editObservacao" class="form-label">Observações</label>
                        <textarea class="form-control" id="editObservacao" rows="2" maxlength="200"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="saveEdit">
                    <i class="bi bi-check-lg me-2"></i>Salvar Alterações
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de resultado do mês -->
<div class="modal fade" id="modalMensagem" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-warning">Resultado do Mês</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p id="modalTexto" class="fs-5 fw-semibold"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="bi bi-check-circle-fill text-success me-2"></i>
            <strong class="me-auto">Finanças Já</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fechar notificação"></button>
        </div>
        <div class="toast-body"></div>
    </div>
</div>

<!-- Scripts. A lógica de renda, saldo e gastos fica em gerenciadorInterface.js; não adicionar scripts inline para isso -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script type="module" src="../Java/gastos.js"></script>

<script>
/* Exibe o campo de nova categoria apenas quando "nova" está selecionada */
document.getElementById('categoria')?.addEventListener('change', function () {
    const wrap  = document.getElementById('wrapCategoriaNova');
    const input = document.getElementById('categoria_nova');
    const isNova = this.value === 'nova';
    wrap?.classList.toggle('d-none', !isNova);
    if (input) input.required = isNova;
    if (!isNova && input) input.value = '';
});
</script>

<?php if (file_exists(__DIR__ . '/chatbot.php')) include_once 'chatbot.php'; ?>
</body>
</html>