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

/* Vieses reconhecidos pelo app (mesma lista de vies_cognitivo.php).
   Sem essa whitelist, um valor qualquer podia ser gravado no banco
   e depois quebrar a tela de resultado, que faz $vieses[$vies]. */
const VIESES_VALIDOS = ['aversao_perda', 'efeito_manada', 'excesso_confianca', 'vies_emocional'];
const NIVEIS_VALIDOS = ['Baixo', 'Médio', 'Alto'];

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
    error_log('salvar_perfil_cognitivo.php: ' . $e->getMessage());
    responder(null, 500, ['success' => false, 'message' => 'Erro ao conectar ao banco']);
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
    responder($conn, 401, ['success' => false, 'message' => 'Usuário não autenticado']);
}

/* ================================================================
   3) Receber e validar JSON
   ================================================================
   Com declare(strict_types=1), funções como trim() exigem que o
   argumento já seja string — se $data['vies'] vier como número,
   array ou null (json_decode aceita qualquer tipo em JSON válido),
   um trim() direto nele lança TypeError, que antes NÃO era pego
   pelo try/catch (esse código rodava antes do try começar) e
   derrubava o endpoint com erro fatal cru em vez de um JSON de
   erro. Agora tudo é validado como string antes de qualquer coisa.
================================================================ */
$data = json_decode(file_get_contents("php://input"), true);

if (
    !is_array($data) ||
    !isset($data['vies'], $data['percentual'], $data['nivel']) ||
    !is_string($data['vies']) ||
    !is_string($data['nivel'])
) {
    responder($conn, 400, ['success' => false, 'message' => 'Dados incompletos']);
}

$vies        = trim($data['vies']);
$intensidade = is_numeric($data['percentual']) ? (float) $data['percentual'] : -1.0;
$nivel       = trim($data['nivel']);

if (!in_array($vies, VIESES_VALIDOS, true)) {
    responder($conn, 400, ['success' => false, 'message' => 'Viés informado é inválido']);
}
if ($intensidade < 0 || $intensidade > 100) {
    responder($conn, 400, ['success' => false, 'message' => 'Percentual inválido']);
}
if (!in_array($nivel, NIVEIS_VALIDOS, true)) {
    responder($conn, 400, ['success' => false, 'message' => 'Nível informado é inválido']);
}

/* ================================================================
   4) Salvar (perfil atual + histórico), tudo na mesma transação
================================================================ */
$conn->begin_transaction();

try {

    /* Verifica se já existe perfil */
    $stmt = prep($conn, "SELECT id_perfil FROM perfil_cognitivo WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->store_result();
    $jaExiste = $stmt->num_rows > 0;
    $stmt->close();

    if ($jaExiste) {

        $update = prep($conn, "
            UPDATE perfil_cognitivo
            SET vies_predominante = ?, intensidade = ?, nivel = ?
            WHERE id_usuario = ?
        ");
        $update->bind_param("sdsi", $vies, $intensidade, $nivel, $id_usuario);

        if (!$update->execute()) {
            $erro = $update->error;
            $update->close();
            throw new RuntimeException('Erro ao atualizar perfil: ' . $erro);
        }
        $update->close();

    } else {

        $insert = prep($conn, "
            INSERT INTO perfil_cognitivo (id_usuario, vies_predominante, intensidade, nivel)
            VALUES (?, ?, ?, ?)
        ");
        $insert->bind_param("isds", $id_usuario, $vies, $intensidade, $nivel);

        if (!$insert->execute()) {
            $erro = $insert->error;
            $insert->close();
            throw new RuntimeException('Erro ao criar perfil: ' . $erro);
        }
        $insert->close();
    }

    /* Sempre salva no histórico */
    $historico = prep($conn, "
        INSERT INTO historico_vies (id_usuario, vies_identificado, intensidade, nivel)
        VALUES (?, ?, ?, ?)
    ");
    $historico->bind_param("isds", $id_usuario, $vies, $intensidade, $nivel);

    if (!$historico->execute()) {
        $erro = $historico->error;
        $historico->close();
        throw new RuntimeException('Erro ao salvar histórico: ' . $erro);
    }
    $historico->close();

    $conn->commit();

    responder($conn, 200, ['success' => true]);

} catch (Throwable $e) {
    $conn->rollback();
    error_log('salvar_perfil_cognitivo.php error: ' . $e->getMessage());
    responder($conn, 500, ['success' => false, 'message' => 'Erro ao salvar perfil']);
}