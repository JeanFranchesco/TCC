<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();

// ---------- CONFIGURAÇÕES ----------
$connPath = __DIR__ . '/../../Conexao/conn.php';
// ------------------------------------

if (!is_file($connPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Arquivo de conexão não encontrado']);
    exit;
}

try {
    $conn = require $connPath;
} catch (Throwable $e) {
    error_log('Erro ao incluir conexão: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao conectar ao banco']);
    exit;
}

if (!($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Conexão inválida']);
    exit;
}

// Obter usuário logado
$id_usuario = $_SESSION['user_id'] ?? $_SESSION['id_usuario'] ?? null;
$id_usuario = $id_usuario ? intval($id_usuario) : 0;

if ($id_usuario <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Obter id do gasto
$id_gasto = 0;

if (isset($_GET['id'])) {
    $id_gasto = intval($_GET['id']);
} else {
    $raw = file_get_contents('php://input');
    $dados = json_decode($raw, true);
    if (!is_array($dados)) {
        $dados = $_POST;
    }
    $id_gasto = isset($dados['id_gasto']) ? intval($dados['id_gasto']) : 0;
}

if ($id_gasto <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

function respond(?mysqli $conn, int $code, array $payload): void {
    if ($conn instanceof mysqli) {
        $conn->close();
    }
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$conn->begin_transaction();

try {
    // Verifica se o gasto existe e pertence ao usuário
    $stmt = $conn->prepare("SELECT id_gasto FROM gastos WHERE id_gasto = ? AND id_usuario = ? LIMIT 1");
    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }

    $stmt->bind_param('ii', $id_gasto, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->rollback();
        respond($conn, 404, ['success' => false, 'message' => 'Gasto não encontrado ou sem permissão']);
    }

    $stmt->close();

    // Apaga apenas o gasto; não altera a tabela categorias
    $delete = $conn->prepare("DELETE FROM gastos WHERE id_gasto = ? AND id_usuario = ?");
    if (!$delete) {
        throw new RuntimeException($conn->error);
    }

    $delete->bind_param('ii', $id_gasto, $id_usuario);
    $delete->execute();

    if ($delete->affected_rows === 0) {
        $delete->close();
        throw new RuntimeException('Falha ao apagar gasto');
    }

    $delete->close();

    $conn->commit();

    respond($conn, 200, [
        'success' => true,
        'message' => 'Gasto removido com sucesso'
    ]);

} catch (Throwable $e) {

    $conn->rollback();
    error_log('Erro delete_gasto: ' . $e->getMessage());

    respond($conn, 500, [
        'success' => false,
        'message' => 'Erro ao apagar registro'
    ]);
}