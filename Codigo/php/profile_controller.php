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
    error_log('profile_controller.php: ' . $e->getMessage());
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

try {

switch ($acao) {

    /* ============================================================
       GET PROFILE — retorna todos os dados do perfil do usuário
    ============================================================ */
    case 'get_profile':
        $stmt = $conn->prepare(
            "SELECT nome, email, telefone, notificacoes, emails_semanais, plano
             FROM usuarios
             WHERE id_usuario = ?"
        );
        if ($stmt === false) {
            throw new RuntimeException('Erro ao preparar consulta: ' . $conn->error);
        }

        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        responder($conn, 200, [
            'success'         => true,
            'nome'            => $result['nome']            ?? '',
            'email'           => $result['email']           ?? '',
            'telefone'        => $result['telefone']        ?? '',
            'notificacoes'    => (bool)($result['notificacoes']    ?? 1),
            'emails_semanais' => (bool)($result['emails_semanais'] ?? 0),
            'plano'           => $result['plano']           ?? 'free'
        ]);

    /* ============================================================
       UPDATE PROFILE — salva nome, email, telefone, preferências e plano
    ============================================================ */
    case 'update_profile':
        $nome            = mb_substr(trim((string)($input['nome']     ?? '')), 0, 100);
        $email           = mb_substr(trim((string)($input['email']    ?? '')), 0, 150);
        $telefone        = mb_substr(trim((string)($input['telefone'] ?? '')), 0, 30);
        $notificacoes    = !empty($input['notificacoes'])    ? 1 : 0;
        $emails_semanais = !empty($input['emails_semanais']) ? 1 : 0;

        /* whitelist de valores permitidos para o plano */
        $plano_input = $input['plano'] ?? 'free';
        $plano       = in_array($plano_input, ['free', 'premium'], true) ? $plano_input : 'free';

        /* validações básicas */
        if ($nome === '' || $email === '') {
            responder($conn, 400, ['success' => false, 'message' => 'Nome e e-mail são obrigatórios']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            responder($conn, 400, ['success' => false, 'message' => 'E-mail inválido']);
        }

        /* verifica se o e-mail já está em uso por outro usuário */
        $checkStmt = $conn->prepare(
            "SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?"
        );
        if ($checkStmt === false) {
            throw new RuntimeException('Erro ao preparar verificação de e-mail: ' . $conn->error);
        }

        $checkStmt->bind_param("si", $email, $id_usuario);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($checkResult) {
            responder($conn, 409, ['success' => false, 'message' => 'Este e-mail já está em uso por outra conta']);
        }

        /* atualiza todos os campos incluindo o plano */
        $stmt = $conn->prepare(
            "UPDATE usuarios
             SET nome = ?, email = ?, telefone = ?,
                 notificacoes = ?, emails_semanais = ?, plano = ?
             WHERE id_usuario = ?"
        );
        if ($stmt === false) {
            throw new RuntimeException('Erro ao preparar atualização: ' . $conn->error);
        }

        $stmt->bind_param("sssiisi", $nome, $email, $telefone,
                          $notificacoes, $emails_semanais, $plano, $id_usuario);

        if (!$stmt->execute()) {
            $erro = $stmt->error;
            $stmt->close();
            throw new RuntimeException('Erro ao salvar alterações: ' . $erro);
        }
        $stmt->close();

        $_SESSION['nome_usuario'] = $nome;
        $_SESSION['plano']        = $plano;

        responder($conn, 200, [
            'success' => true,
            'message' => 'Perfil atualizado com sucesso!',
            'plano'   => $plano
        ]);

    /* ============================================================
       CHANGE PASSWORD — altera a senha do usuário
    ============================================================ */
    case 'change_password':
        $senhaAtual = (string)($input['senha_atual'] ?? '');
        $novaSenha  = (string)($input['nova_senha']  ?? '');

        if ($senhaAtual === '' || $novaSenha === '') {
            responder($conn, 400, ['success' => false, 'message' => 'Preencha todos os campos']);
        }
        if (strlen($novaSenha) < 6) {
            responder($conn, 400, ['success' => false, 'message' => 'A nova senha deve ter no mínimo 6 caracteres']);
        }

        /* busca hash atual */
        $stmt = $conn->prepare("SELECT senha FROM usuarios WHERE id_usuario = ?");
        if ($stmt === false) {
            throw new RuntimeException('Erro ao preparar consulta de senha: ' . $conn->error);
        }

        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$result || !password_verify($senhaAtual, $result['senha'])) {
            responder($conn, 401, ['success' => false, 'message' => 'Senha atual incorreta']);
        }

        $hashNova = password_hash($novaSenha, PASSWORD_DEFAULT);
        $stmt     = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id_usuario = ?");
        if ($stmt === false) {
            throw new RuntimeException('Erro ao preparar troca de senha: ' . $conn->error);
        }

        $stmt->bind_param("si", $hashNova, $id_usuario);

        if (!$stmt->execute()) {
            $erro = $stmt->error;
            $stmt->close();
            throw new RuntimeException('Erro ao alterar a senha: ' . $erro);
        }
        $stmt->close();

        responder($conn, 200, ['success' => true, 'message' => 'Senha alterada com sucesso!']);

    /* ============================================================
       DEFAULT — ação inválida
    ============================================================ */
    default:
        responder($conn, 400, ['success' => false, 'message' => 'Ação inválida']);
}

} catch (Throwable $e) {
    /* Erros internos: log detalhado no servidor, mensagem genérica pro cliente */
    error_log('profile_controller.php error: ' . $e->getMessage());
    responder($conn, 500, ['success' => false, 'message' => 'Erro interno ao processar a solicitação']);
}