<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Caminho do arquivo de conexão
$connPath = __DIR__ . '/../../Conexao/conn.php';
if (!is_file($connPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Arquivo de conexão não encontrado. Verifique o caminho: ' . $connPath
    ]);
    exit;
}

try {
    $conn = require $connPath;
} catch (Throwable $e) {
    error_log('register.php: falha ao incluir conn.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao incluir arquivo de conexão']);
    exit;
}

if (!($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Conexão inválida. Verifique conn.php.'
    ]);
    exit;
}

// Lê os dados do POST
$email = trim((string) ($_POST['email'] ?? ''));
$senha = (string) ($_POST['senha'] ?? '');
$senha_confirm = (string) ($_POST['senha_confirm'] ?? '');

// Validações
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit;
}

if (strlen($senha) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Senha mínima: 6 caracteres']);
    exit;
}

if ($senha !== $senha_confirm) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'As senhas não conferem']);
    exit;
}

// Debug (APP_DEBUG; desligado por padrão)
$debug = filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN);

try {
    // Verifica se o email já existe
    $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ? LIMIT 1");
    if ($stmt === false) {
        throw new RuntimeException('Erro no prepare (select): ' . $conn->error);
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email já cadastrado']);
        exit;
    }
    $stmt->close();

    // Insere o usuário com a senha em hash
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO usuarios (email, senha, status, role, criado_em) VALUES (?, ?, 'ativo', 'user', NOW())");
    if ($stmt === false) {
        throw new RuntimeException('Erro no prepare (insert): ' . $conn->error);
    }
    $stmt->bind_param('ss', $email, $hash);
    $ok = $stmt->execute();
    if ($ok === false) {
        throw new RuntimeException('Erro ao executar insert: ' . $stmt->error);
    }

    $insertId = $conn->insert_id;
    $stmt->close();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Conta criada com sucesso',
        'id_usuario' => $insertId
    ]);
    exit;

} catch (Throwable $e) {
    error_log('register.php exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $debug ? $e->getMessage() : 'Erro interno do servidor'
    ]);
    exit;
}