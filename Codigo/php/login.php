<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();

$connPath = __DIR__ . '/../../Conexao/conn.php';
if (!is_file($connPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Arquivo de conexão não encontrado', 'path' => $connPath]);
    exit;
}

try {
    $conn = require $connPath;
} catch (Throwable $e) {
    error_log('login.php: falha ao incluir conn.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao incluir arquivo de conexão']);
    exit;
}

if (!($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Conexão inválida. Verifique conn.php.']);
    exit;
}

// Recebe dados do formulário (application/x-www-form-urlencoded ou multipart/form-data)
$email = trim((string)($_POST['email'] ?? ''));
$senha = (string)($_POST['senha'] ?? '');

if ($email === '' || $senha === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Preencha email e senha.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email inválido.']);
    exit;
}

try {
    $sql = "SELECT id_usuario, senha, status, role, nome FROM usuarios WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new RuntimeException('Erro no prepare: ' . $conn->error);
    }
    $stmt->bind_param('s', $email);
    if (!$stmt->execute()) {
        throw new RuntimeException('Erro ao executar: ' . $stmt->error);
    }

    $usuario = null;
    if (method_exists($stmt, 'get_result')) {
        $res = $stmt->get_result();
        $usuario = ($res && $res->num_rows === 1) ? $res->fetch_assoc() : null;
    } else {
        $stmt->bind_result($id_usuario, $hashSenha, $status, $role, $nome);
        if ($stmt->fetch()) {
            $usuario = [
                'id_usuario' => $id_usuario,
                'senha' => $hashSenha,
                'status' => $status,
                'role' => $role,
                'nome' => $nome ?? ''
            ];
        }
    }
    $stmt->close();

    if (!$usuario) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Email não encontrado.']);
        exit;
    }

    if (($usuario['status'] ?? '') !== 'ativo') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Conta inativa.']);
        exit;
    }

    if (!password_verify($senha, $usuario['senha'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Email ou senha incorretos.']);
        exit;
    }

    // Segurança: renova id da sessão
    session_regenerate_id(true);

    // Padroniza chaves de sessão para compatibilidade com outros scripts
    $_SESSION['id_usuario'] = (int)$usuario['id_usuario'];
    $_SESSION['user_id'] = (int)$usuario['id_usuario'];
    $_SESSION['role'] = $usuario['role'] ?? '';
    $_SESSION['user_name'] = $usuario['nome'] ?? '';

    // Rehash da senha se necessário
    if (password_needs_rehash($usuario['senha'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($senha, PASSWORD_DEFAULT);
        $uSql = "UPDATE usuarios SET senha = ? WHERE id_usuario = ?";
        $uStmt = $conn->prepare($uSql);
        if ($uStmt) {
            $uStmt->bind_param('si', $newHash, $usuario['id_usuario']);
            $uStmt->execute();
            $uStmt->close();
        }
    }

    // Atualiza último login e IP (não crítico)
    $sqlUpdate = "UPDATE usuarios SET ultimo_login = NOW(), last_login_ip = ? WHERE id_usuario = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    if ($stmtUpdate) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmtUpdate->bind_param('si', $ip, $usuario['id_usuario']);
        $stmtUpdate->execute();
        $stmtUpdate->close();
    } else {
        error_log('login.php: falha ao preparar update: ' . $conn->error);
    }

    // Resposta JSON (frontend pode usar redirect)
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login realizado com sucesso!',
        'id_usuario' => (int)$usuario['id_usuario'],
        'user_name' => $usuario['nome'] ?? '',
        'redirect' => '/TCC/Codigo/FrontEnd/menu.php'
    ]);
    exit;

} catch (Throwable $e) {
    error_log('login.php exception: ' . $e->getMessage());
    http_response_code(500);
    $debug = filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN);
    echo json_encode(['success' => false, 'message' => $debug ? $e->getMessage() : 'Erro interno no servidor.']);
    exit;
}
