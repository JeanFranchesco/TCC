<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();

/* ================================================================
   Função auxiliar: responde em JSON e encerra a execução.
   Fecha a conexão (com rollback opcional) antes de sair.
================================================================ */
function responder(?mysqli $conn, int $status, array $payload, bool $rollback = false): void
{
    if ($conn instanceof mysqli) {
        if ($rollback) {
            $conn->rollback();
        }
        $conn->close();
    }

    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ======================================================
   1) Carregar conexão
====================================================== */
$connPath = __DIR__ . '/../../Conexao/conn.php';
if (!is_file($connPath)) {
    responder(null, 500, ['success' => false, 'message' => 'Arquivo de conexão não encontrado']);
}

try {
    $conn = require $connPath;
} catch (Throwable $e) {
    error_log('saveRenda.php: falha ao incluir conn.php: ' . $e->getMessage());
    responder(null, 500, ['success' => false, 'message' => 'Erro ao incluir arquivo de conexão']);
}

if (!($conn instanceof mysqli)) {
    responder(null, 500, ['success' => false, 'message' => 'Conexão inválida']);
}

/* ======================================================
   2) Validar usuário autenticado
====================================================== */
$id_usuario = $_SESSION['user_id'] ?? $_SESSION['id_usuario'] ?? null;
if (empty($id_usuario)) {
    responder($conn, 401, ['success' => false, 'message' => 'Usuário não autenticado']);
}
$id_usuario = intval($id_usuario);

/* ======================================================
   3) Receber valor do frontend
====================================================== */
$input = json_decode(file_get_contents('php://input'), true);
$valor = isset($input['valor']) ? floatval($input['valor']) : 0.0;

if ($valor <= 0) {
    responder($conn, 400, ['success' => false, 'message' => 'Valor inválido']);
}

/* ======================================================
   4) Calcular intervalo do mês atual
   — UPDATE se já existe renda NESTE MÊS
   — INSERT se não existe ainda neste mês
   Isso garante que cada mês tem seu próprio registro,
   preservando o histórico e zerando corretamente na virada.
====================================================== */
$descricao          = 'Renda Mensal';
$tipo               = 'fixa';
$hoje               = new DateTimeImmutable('today');
$data_receita       = $hoje->format('Y-m-d');
$primeiroDiaMes     = $hoje->modify('first day of this month')->format('Y-m-d');
$primeiroDiaProximo = $hoje->modify('first day of next month')->format('Y-m-d');

/* Verificar + gravar dentro de uma transação, para que duas
   requisições simultâneas nunca criem dois registros de renda
   para o mesmo mês (condição de corrida no check-then-insert). */
$conn->begin_transaction();

try {
    $check = $conn->prepare("
        SELECT id_receita
        FROM receitas
        WHERE id_usuario   = ?
          AND descricao    = ?
          AND data_receita >= ?
          AND data_receita <  ?
        LIMIT 1
        FOR UPDATE
    ");

    if (!$check) {
        throw new RuntimeException('Erro ao preparar consulta: ' . $conn->error);
    }

    $check->bind_param('isss', $id_usuario, $descricao, $primeiroDiaMes, $primeiroDiaProximo);
    $check->execute();
    $resultadoCheck = $check->get_result();

    if ($resultadoCheck->num_rows > 0) {
        /* ── Existe neste mês → UPDATE ── */
        $linhaExistente = $resultadoCheck->fetch_assoc();
        $check->close();

        $id_receita = (int) $linhaExistente['id_receita'];

        $stmt = $conn->prepare("UPDATE receitas SET valor = ?, data_receita = ? WHERE id_receita = ?");
        if (!$stmt) {
            throw new RuntimeException('Erro ao preparar update: ' . $conn->error);
        }
        $stmt->bind_param('dsi', $valor, $data_receita, $id_receita);
        $mensagem = 'Renda mensal atualizada com sucesso';

    } else {
        /* ── Não existe neste mês → INSERT (novo registro mensal) ── */
        $check->close();

        $stmt = $conn->prepare("
            INSERT INTO receitas (id_usuario, descricao, valor, data_receita, tipo)
            VALUES (?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            throw new RuntimeException('Erro ao preparar insert: ' . $conn->error);
        }
        $stmt->bind_param('isdss', $id_usuario, $descricao, $valor, $data_receita, $tipo);
        $mensagem = 'Renda mensal salva com sucesso';
    }

    if (!$stmt->execute()) {
        throw new RuntimeException('Erro ao salvar a renda: ' . $stmt->error);
    }

    $stmt->close();
    $conn->commit();

    responder($conn, 200, ['success' => true, 'message' => $mensagem]);

} catch (Throwable $e) {
    error_log('saveRenda.php error: ' . $e->getMessage());
    responder($conn, 500, ['success' => false, 'message' => 'Erro ao salvar a renda'], true);
}