<?php
/* ================================================================
   metas_controller.php — Finanças Já

   ⚠️ IMPORTANTE SOBRE O BANCO: este arquivo agora grava o status
   'falhou' na coluna `status` da tabela `metas` (além dos já
   existentes 'ativa' e 'concluida'). Se essa coluna for um ENUM,
   confirme que 'falhou' está na lista de valores permitidos —
   senão o UPDATE vai falhar. Se for VARCHAR, não precisa mudar nada.
================================================================ */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();

/* Nunca mostrar erro do PHP direto na resposta — isso corrompe o
   JSON (o navegador recebe HTML de aviso + JSON grudados e o
   res.json() quebra no frontend). Os erros continuam indo pro log. */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

while (ob_get_level()) ob_end_clean();
ob_start();

/* ================================================================
   Função auxiliar: responde em JSON e encerra a execução.
================================================================ */
function responder(?mysqli $conn, int $status, array $payload): void
{
    while (ob_get_level()) ob_end_clean();

    if ($conn instanceof mysqli) {
        $conn->close();
    }

    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ================================================================
   Função auxiliar: prepare() que already valida a si mesmo.
   Evita repetir "if ($stmt === false) throw ..." umas 15 vezes.
================================================================ */
function prep(mysqli $conn, string $sql): mysqli_stmt
{
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new RuntimeException('Erro ao preparar consulta: ' . $conn->error);
    }
    return $stmt;
}

/* ================================================================
   1) Conexão
================================================================ */
$connPath = __DIR__ . '/../../Conexao/conn.php';
if (!is_file($connPath)) {
    responder(null, 500, ['success' => false, 'message' => 'Arquivo de conexão não encontrado']);
}

try {
    $conn = require $connPath;
} catch (Throwable $e) {
    error_log('metas_controller.php: ' . $e->getMessage());
    responder(null, 500, ['success' => false, 'message' => 'Erro ao conectar ao banco de dados']);
}

if (!($conn instanceof mysqli)) {
    responder(null, 500, ['success' => false, 'message' => 'Conexão inválida']);
}

/* ================================================================
   2) Autenticação
================================================================ */
$id_usuario = null;
if (!empty($_SESSION['user_id']))        $id_usuario = intval($_SESSION['user_id']);
elseif (!empty($_SESSION['id_usuario'])) $id_usuario = intval($_SESSION['id_usuario']);

if (empty($id_usuario)) {
    responder($conn, 401, ['success' => false, 'message' => 'Não autenticado']);
}

/* ================================================================
   3) Entrada
================================================================ */
$input = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (json_decode(file_get_contents('php://input'), true) ?? [])
    : $_GET;

if (!is_array($input)) $input = [];

$acao = $input['acao'] ?? '';

/* ================================================================
   FECHAMENTO AUTOMÁTICO DO MÊS ANTERIOR
   ================================================================
   Fecha (marca concluída ou falhou) as metas de meses já
   terminados que ainda estejam 'ativa'.

   Correções desta versão:
   a) A renda usada para calcular o saldo agora é a renda DAQUELE
      mês específico (data_receita dentro do período sendo fechado)
      — não mais "a renda mais recente cadastrada no sistema".
      Esse era o bug: cadastrar a renda de fevereiro mudava
      retroativamente o resultado das metas de janeiro.
   b) Toda meta processada aqui sai com um status definitivo
      ('concluida' ou 'falhou') — nunca mais fica 'ativa' pra
      sempre sendo reavaliada a cada carregamento de página com
      dados que podem ter mudado.
   c) Apostas de metas que falham agora são marcadas 'perdeu' em
      vez de ficarem 'pendente' pra sempre.
   d) A soma de gastos agora filtra "status = 'ativo'", igual ao
      resto do sistema (get_gastos.php, get_historico.php, etc).
================================================================ */
function autoFecharMesPassado(mysqli $conn, int $id_usuario): ?array
{
    $hoje = date('Y-m-d');

    /* Existe alguma meta do usuário ainda "ativa" cujo mês já terminou? */
    $stmtCheck = prep($conn, "SELECT 1 FROM metas WHERE id_usuario = ? AND status = 'ativa' AND data_fim < ? LIMIT 1");
    $stmtCheck->bind_param("is", $id_usuario, $hoje);
    $stmtCheck->execute();
    $temPendente = $stmtCheck->get_result()->num_rows > 0;
    $stmtCheck->close();

    if (!$temPendente) return null;

    /* Pega o período (mês) mais antigo ainda pendente de fechamento */
    $stmtMes = prep($conn, "SELECT DATE_FORMAT(data_inicio, '%Y-%m') as periodo FROM metas WHERE id_usuario = ? AND status = 'ativa' AND data_fim < ? ORDER BY data_inicio ASC LIMIT 1");
    $stmtMes->bind_param("is", $id_usuario, $hoje);
    $stmtMes->execute();
    $periodo = $stmtMes->get_result()->fetch_assoc()['periodo'] ?? null;
    $stmtMes->close();

    if (!$periodo) return null;

    $primeiroDia = $periodo . '-01';
    $proximoMes  = date('Y-m-01', strtotime($primeiroDia . ' +1 month'));

    /* Renda DAQUELE mês — não a mais recente do sistema (era o bug) */
    $stmt = prep($conn, "SELECT valor FROM receitas WHERE id_usuario = ? AND descricao = 'Renda Mensal' AND data_receita >= ? AND data_receita < ? ORDER BY data_receita DESC, id_receita DESC LIMIT 1");
    $stmt->bind_param('iss', $id_usuario, $primeiroDia, $proximoMes);
    $stmt->execute();
    $renda = (float) ($stmt->get_result()->fetch_assoc()['valor'] ?? 0);
    $stmt->close();

    /* Gastos DAQUELE mês, só os ativos (mesmo filtro do resto do sistema) */
    $stmt = prep($conn, "SELECT COALESCE(SUM(valor), 0) as total FROM gastos WHERE id_usuario = ? AND data_gasto >= ? AND data_gasto < ? AND status = 'ativo'");
    $stmt->bind_param('iss', $id_usuario, $primeiroDia, $proximoMes);
    $stmt->execute();
    $totalGastos = (float) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    $saldoSobrando = max(0, $renda - $totalGastos);

    $stmt = prep($conn, "SELECT * FROM metas WHERE id_usuario = ? AND status = 'ativa' AND data_inicio >= ? AND data_inicio < ? ORDER BY id_meta ASC");
    $stmt->bind_param('iss', $id_usuario, $primeiroDia, $proximoMes);
    $stmt->execute();
    $metasAtivas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $totalMetas    = count($metasAtivas);
    $completadas   = 0;
    $saldoRestante = $saldoSobrando;
    $faltando      = 0;

    foreach ($metasAtivas as $meta) {

        $necessario = (float) $meta['valor_meta'] - (float) $meta['valor_atual'];

        if ($necessario <= 0 || $saldoRestante >= $necessario) {

            /* --- Meta concluída --- */
            $saldoRestante -= max(0, $necessario);

            $upd = prep($conn, "UPDATE metas SET valor_atual = ?, status = 'concluida' WHERE id_meta = ?");
            $upd->bind_param('di', $meta['valor_meta'], $meta['id_meta']);
            $upd->execute();
            $upd->close();

            $stmtAp = prep($conn, "SELECT id_aposta, pontos_apostados FROM apostas WHERE id_meta = ? AND resultado = 'pendente'");
            $stmtAp->bind_param("i", $meta['id_meta']);
            $stmtAp->execute();
            $aposta = $stmtAp->get_result()->fetch_assoc();
            $stmtAp->close();

            $pontosBase = $aposta ? (int) $aposta['pontos_apostados'] : 0;
            $pontos     = $pontosBase > 0 ? $pontosBase * 2 : 1;

            $stmtHist = prep($conn, "INSERT INTO historico_pontos (id_usuario, pontos, tipo, descricao) VALUES (?, ?, 'ganho', 'Meta concluída automaticamente')");
            $stmtHist->bind_param("ii", $id_usuario, $pontos);
            $stmtHist->execute();
            $stmtHist->close();

            if ($aposta) {
                $up = prep($conn, "UPDATE apostas SET resultado = 'ganhou' WHERE id_aposta = ?");
                $up->bind_param("i", $aposta['id_aposta']);
                $up->execute();
                $up->close();
            }

            $completadas++;

        } else {

            /* --- Meta falhou: recebe o que sobrou de saldo (se algum),
                 mas o status vira definitivo, nunca mais fica "ativa"
                 pendurada pra ser reavaliada com dados de outro mês --- */
            $novoAtual     = (float) $meta['valor_atual'] + $saldoRestante;
            $faltandoAgora = $necessario - $saldoRestante;

            $upd = prep($conn, "UPDATE metas SET valor_atual = ?, status = 'falhou' WHERE id_meta = ?");
            $upd->bind_param('di', $novoAtual, $meta['id_meta']);
            $upd->execute();
            $upd->close();

            $stmtAp = prep($conn, "SELECT id_aposta FROM apostas WHERE id_meta = ? AND resultado = 'pendente'");
            $stmtAp->bind_param("i", $meta['id_meta']);
            $stmtAp->execute();
            $aposta = $stmtAp->get_result()->fetch_assoc();
            $stmtAp->close();

            if ($aposta) {
                $up = prep($conn, "UPDATE apostas SET resultado = 'perdeu' WHERE id_aposta = ?");
                $up->bind_param("i", $aposta['id_aposta']);
                $up->execute();
                $up->close();
            }

            $faltando      = max($faltando, $faltandoAgora);
            $saldoRestante = 0;
        }
    }

    if ($totalMetas === 0) {
        $msg  = "Nenhuma meta no mês anterior.";
        $tipo = "info";
    } elseif ($completadas === $totalMetas) {
        $msg  = "🎉 Parabéns! Você bateu todas as metas do mês anterior :3";
        $tipo = "success";
    } elseif ($completadas > 0) {
        $msg  = "🏆 Parabéns! Você bateu {$completadas} de {$totalMetas} metas do mês passado.<br>Faltou R$ " . number_format($faltando, 2, ',', '.') . " para completar as restantes.";
        $tipo = "success";
    } else {
        $msg  = "😔 Não foi dessa vez no mês anterior... Mas vamos com tudo agora!";
        $tipo = "warning";
    }

    return [
        'mensagem'  => $msg,
        'tipo'      => $tipo,
        'completed' => $completadas,
        'total'     => $totalMetas,
        'missing'   => $faltando
    ];
}

/* ================================================================
   4) Ações
================================================================ */
try {

switch ($acao) {

    /* ============================================================
       PONTOS ATUAIS
    ============================================================ */
    case 'get_pontos_atual':
        $stmt = prep($conn, "SELECT COALESCE(SUM(pontos), 0) as pontos FROM historico_pontos WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        responder($conn, 200, ['success' => true, 'pontos' => (int) ($result['pontos'] ?? 0)]);

    /* ============================================================
       CRIAR META
    ============================================================ */
    case 'criar':
        $titulo     = mb_substr(trim((string) ($input['titulo'] ?? '')), 0, 150);
        $valor_meta = isset($input['valor_meta']) ? (float) $input['valor_meta'] : 0.0;

        if ($titulo === '' || $valor_meta <= 0) {
            responder($conn, 400, ['success' => false, 'message' => 'Dados incompletos']);
        }

        $data_inicio = date('Y-m-01');
        $data_fim    = date('Y-m-t');

        $stmtPontos = prep($conn, "SELECT COALESCE(SUM(pontos), 0) as total FROM historico_pontos WHERE id_usuario = ?");
        $stmtPontos->bind_param("i", $id_usuario);
        $stmtPontos->execute();
        $pontosAtuais = (int) ($stmtPontos->get_result()->fetch_assoc()['total'] ?? 0);
        $stmtPontos->close();

        $aposta = $pontosAtuais > 0 ? (int) max(1, floor($pontosAtuais / 2)) : 0;

        if ($aposta > 0) {
            $pontos_aposta = -$aposta;
            $stmtHist = prep($conn, "INSERT INTO historico_pontos (id_usuario, pontos, tipo, descricao) VALUES (?, ?, 'aposta', 'Aposta em nova meta')");
            $stmtHist->bind_param("ii", $id_usuario, $pontos_aposta);
            $stmtHist->execute();
            $stmtHist->close();
        }

        $stmt = prep($conn, "INSERT INTO metas (id_usuario, titulo, valor_meta, data_inicio, data_fim, valor_atual, status) VALUES (?, ?, ?, ?, ?, 0, 'ativa')");
        $stmt->bind_param("isdss", $id_usuario, $titulo, $valor_meta, $data_inicio, $data_fim);

        if (!$stmt->execute()) {
            $erro = $stmt->error;
            $stmt->close();
            throw new RuntimeException('Erro ao inserir meta: ' . $erro);
        }
        $id_meta = $stmt->insert_id;
        $stmt->close();

        $stmtAposta = prep($conn, "INSERT INTO apostas (id_meta, id_usuario, pontos_apostados, resultado) VALUES (?, ?, ?, 'pendente')");
        $stmtAposta->bind_param("iii", $id_meta, $id_usuario, $aposta);
        $stmtAposta->execute();
        $stmtAposta->close();

        responder($conn, 200, ['success' => true, 'aposta' => $aposta]);

    /* ============================================================
       DELETAR META
    ============================================================ */
    case 'deletar':
        $id_meta = isset($input['id_meta']) ? intval($input['id_meta']) : 0;

        if ($id_meta <= 0) {
            responder($conn, 400, ['success' => false, 'message' => 'ID da meta inválido']);
        }

        $stmtAp = prep($conn, "DELETE FROM apostas WHERE id_meta = ? AND id_usuario = ?");
        $stmtAp->bind_param("ii", $id_meta, $id_usuario);
        $stmtAp->execute();
        $stmtAp->close();

        $stmt = prep($conn, "DELETE FROM metas WHERE id_meta = ? AND id_usuario = ?");
        $stmt->bind_param("ii", $id_meta, $id_usuario);
        $stmt->execute();
        $afetou = $stmt->affected_rows > 0;
        $stmt->close();

        if (!$afetou) {
            responder($conn, 404, ['success' => false, 'message' => 'Meta não encontrada']);
        }

        responder($conn, 200, ['success' => true]);

    /* ============================================================
       LISTAR METAS DO MÊS ATUAL
    ============================================================ */
    case 'listar':
        $fechamento  = autoFecharMesPassado($conn, $id_usuario);
        $primeiroDia = date('Y-m-01');
        $proximoMes  = date('Y-m-01', strtotime('+1 month'));

        $stmt = prep($conn, "SELECT * FROM metas WHERE id_usuario = ? AND data_inicio >= ? AND data_inicio < ? ORDER BY id_meta ASC");
        $stmt->bind_param("iss", $id_usuario, $primeiroDia, $proximoMes);
        $stmt->execute();
        $metas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        responder($conn, 200, ['success' => true, 'metas' => $metas, 'fechamento_automatico' => $fechamento]);

    /* ============================================================
       RESUMO FINANCEIRO DO MÊS ATUAL
    ============================================================ */
    case 'resumo_mes':
        $fechamento  = autoFecharMesPassado($conn, $id_usuario);
        $primeiroDia = date('Y-m-01');
        $proximoMes  = date('Y-m-01', strtotime('+1 month'));

        $stmt = prep($conn, "SELECT valor FROM receitas WHERE id_usuario = ? AND descricao = 'Renda Mensal' AND data_receita >= ? AND data_receita < ? ORDER BY data_receita DESC, id_receita DESC LIMIT 1");
        $stmt->bind_param('iss', $id_usuario, $primeiroDia, $proximoMes);
        $stmt->execute();
        $renda = (float) ($stmt->get_result()->fetch_assoc()['valor'] ?? 0);
        $stmt->close();

        $stmt = prep($conn, "SELECT COALESCE(SUM(valor), 0) as total FROM gastos WHERE id_usuario = ? AND data_gasto >= ? AND data_gasto < ? AND status = 'ativo'");
        $stmt->bind_param('iss', $id_usuario, $primeiroDia, $proximoMes);
        $stmt->execute();
        $totalGastos = (float) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();

        $saldo = max(0, $renda - $totalGastos);

        $stmt = prep($conn, "SELECT COUNT(*) as total FROM metas WHERE id_usuario = ? AND status = 'ativa' AND data_inicio >= ? AND data_inicio < ?");
        $stmt->bind_param('iss', $id_usuario, $primeiroDia, $proximoMes);
        $stmt->execute();
        $metasAtivas = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();

        responder($conn, 200, [
            'success'               => true,
            'renda'                 => $renda,
            'gastos'                => $totalGastos,
            'saldo'                 => $saldo,
            'metas_ativas'          => $metasAtivas,
            'fechamento_automatico' => $fechamento
        ]);

    /* ============================================================
       AÇÃO INVÁLIDA
    ============================================================ */
    default:
        responder($conn, 400, ['success' => false, 'message' => 'Ação inválida']);
}

} catch (Throwable $e) {
    error_log('metas_controller.php error: ' . $e->getMessage());
    responder($conn, 500, ['success' => false, 'message' => 'Erro interno ao processar a solicitação']);
}