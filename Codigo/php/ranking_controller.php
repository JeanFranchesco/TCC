<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();

/* ================================================================
   Função auxiliar: responde em JSON e encerra a execução.
================================================================ */
function responder(?mysqli $conn, int $status, array $payload): void
{
    if ($conn instanceof mysqli) {
        $conn->close();
    }
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

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
    error_log('ranking_controller.php: ' . $e->getMessage());
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
   3) Ranking
================================================================ */
try {

    $stmt = prep($conn, "
        SELECT u.nome, COALESCE(SUM(h.pontos), 0) as pontos_total
        FROM usuarios u
        LEFT JOIN historico_pontos h ON u.id_usuario = h.id_usuario
        GROUP BY u.id_usuario, u.nome
        ORDER BY pontos_total DESC
        LIMIT 10
    ");
    $stmt->execute();
    $ranking = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $user_stmt = prep($conn, "SELECT COALESCE(SUM(h.pontos), 0) as pontos_total FROM historico_pontos h WHERE h.id_usuario = ?");
    $user_stmt->bind_param("i", $id_usuario);
    $user_stmt->execute();
    $user_points = (int) ($user_stmt->get_result()->fetch_assoc()['pontos_total'] ?? 0);
    $user_stmt->close();

    $name_stmt = prep($conn, "SELECT nome FROM usuarios WHERE id_usuario = ?");
    $name_stmt->bind_param("i", $id_usuario);
    $name_stmt->execute();
    $user_name = $name_stmt->get_result()->fetch_assoc()['nome'] ?? 'Você';
    $name_stmt->close();

    $rank_stmt = prep($conn, "
        SELECT COUNT(*) + 1 as posicao
        FROM (
            SELECT id_usuario
            FROM historico_pontos
            GROUP BY id_usuario
            HAVING SUM(pontos) > ?
        ) as higher
    ");
    $rank_stmt->bind_param("i", $user_points);
    $rank_stmt->execute();
    $user_rank = (int) ($rank_stmt->get_result()->fetch_assoc()['posicao'] ?? 1);
    $rank_stmt->close();

    responder($conn, 200, [
        'success' => true,
        'ranking' => $ranking,
        'user'    => [
            'posicao' => $user_rank,
            'pontos'  => $user_points,
            'nome'    => $user_name
        ]
    ]);

} catch (Throwable $e) {
    error_log('ranking_controller.php error: ' . $e->getMessage());
    responder($conn, 500, ['success' => false, 'message' => 'Erro ao carregar ranking']);
}