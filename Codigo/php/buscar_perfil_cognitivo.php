<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();

$connPath = __DIR__ . '/../../Conexao/conn.php';

if (!is_file($connPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Arquivo de conexão não encontrado']);
    exit;
}

try {
    $conn = require $connPath;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao conectar ao banco']);
    exit;
}

if (!($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Conexão inválida']);
    exit;
}

// Usuário logado
$id_usuario = $_SESSION['user_id'] ?? $_SESSION['id_usuario'] ?? 0;
$id_usuario = intval($id_usuario);

if ($id_usuario <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {

    // Perfil atual

    $perfil = null;

    $stmt = $conn->prepare("
        SELECT vies_predominante, intensidade, nivel, atualizado_em
        FROM perfil_cognitivo
        WHERE id_usuario = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $perfil = $row;
    }

    $stmt->close();

    // Histórico

    $historico = [];

    $stmt = $conn->prepare("
        SELECT vies_identificado, intensidade, nivel, data_analise
        FROM historico_vies
        WHERE id_usuario = ?
        ORDER BY data_analise DESC
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $historico[] = $row;
    }

    $stmt->close();

    // Resposta

    echo json_encode([
        'success' => true,
        'perfil' => $perfil,
        'historico' => $historico
    ]);

    $conn->close();

} catch (Throwable $e) {

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar dados'
    ]);
}