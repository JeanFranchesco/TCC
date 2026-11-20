<?php
/* get_renda.php
   Retorna a renda mensal do mês atual (0 se ainda não cadastrada). */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();

/* 1) Conexão */
$connPath = __DIR__ . '/../../Conexao/conn.php';
if (!is_file($connPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Arquivo de conexão não encontrado']);
    exit;
}
try {
    $conn = require $connPath;
} catch (Throwable $e) {
    error_log('get_renda.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao conectar ao banco de dados']);
    exit;
}
if (!($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Conexão inválida']);
    exit;
}

/* 2) Autenticação */
$id_usuario = null;
if (!empty($_SESSION['user_id']))        $id_usuario = intval($_SESSION['user_id']);
elseif (!empty($_SESSION['id_usuario'])) $id_usuario = intval($_SESSION['id_usuario']);

if (empty($id_usuario)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

/* 3) Intervalo do mês atual (calculado no servidor) */
$hoje               = new DateTimeImmutable('today');
$primeiroDiaMes     = $hoje->modify('first day of this month')->format('Y-m-d');
$primeiroDiaProximo = $hoje->modify('first day of next month')->format('Y-m-d');
$mesReferencia      = $hoje->format('Y-m');   // ex: "2026-06"

/* 4) Buscar a renda do mês atual */
try {
    $sql = "
        SELECT valor, data_receita
        FROM receitas
        WHERE id_usuario   = ?
          AND descricao    = 'Renda Mensal'
          AND data_receita >= ?
          AND data_receita <  ?
        ORDER BY data_receita DESC, id_receita DESC
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) throw new RuntimeException('Erro ao preparar: ' . $conn->error);

    $stmt->bind_param('iss', $id_usuario, $primeiroDiaMes, $primeiroDiaProximo);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Erro ao executar: ' . $err);
    }

    $resultado    = $stmt->get_result();
    $renda        = 0.0;
    $data_receita = null;

    if ($row = $resultado->fetch_assoc()) {
        $renda        = (float) $row['valor'];
        $data_receita = $row['data_receita'];
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success'        => true,
        'valor'          => $renda,          // 0.0 se não houver renda este mês
        'data'           => $data_receita,
        'mes_referencia' => $mesReferencia
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('get_renda.php exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no servidor']);
}