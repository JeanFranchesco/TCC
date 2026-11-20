<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();

/* Tipos de gasto aceitos pelo app (o resto vira 'variavel') */
const TIPOS_VALIDOS = ['fixo', 'variavel'];

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

/* ================================================================
   Função auxiliar: valida se a string é uma data real no formato
   Y-m-d (rejeita coisas como "2024-02-31" ou lixo qualquer).
================================================================ */
function dataValida(string $data): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d !== false && $d->format('Y-m-d') === $data;
}

/* ================================================================
   Função auxiliar: busca categoria por nome ou cria uma nova.
   Mesma lógica usada em gastos.php (case-insensitive, filtrando
   por tipo 'despesa'), para as duas rotas nunca criarem categorias
   duplicadas por causa de maiúscula/minúscula.

   Deve sempre ser chamada DENTRO da transação do chamador.
================================================================ */
function buscarOuCriarCategoria(mysqli $conn, string $nome, string $descricao = ''): int
{
    /* 1) Busca pelo nome (case-insensitive) */
    $stmt = $conn->prepare(
        "SELECT id_categoria FROM categorias WHERE LOWER(nome) = LOWER(?) AND tipo = 'despesa' LIMIT 1"
    );
    if ($stmt) {
        $stmt->bind_param('s', $nome);
        $stmt->execute();
        $stmt->bind_result($id_encontrado);
        $achou = $stmt->fetch();
        $stmt->close();
        if ($achou && $id_encontrado) return intval($id_encontrado);
    }

    /* 2) Não encontrou → cria nova categoria */
    $ins = $conn->prepare(
        "INSERT INTO categorias (nome, tipo, descricao, criado_em, atualizado_em, ativo)
         VALUES (?, 'despesa', ?, NOW(), NOW(), 1)"
    );
    if (!$ins) return 0;

    $ins->bind_param('ss', $nome, $descricao);

    if (!$ins->execute()) {
        error_log('update_gasto.php buscarOuCriarCategoria insert error: ' . $ins->error);
        $ins->close();

        /* Se falhou por nome duplicado (corrida entre requisições), busca de novo */
        $stmt2 = $conn->prepare(
            "SELECT id_categoria FROM categorias WHERE LOWER(nome) = LOWER(?) AND tipo = 'despesa' LIMIT 1"
        );
        if ($stmt2) {
            $stmt2->bind_param('s', $nome);
            $stmt2->execute();
            $stmt2->bind_result($id2);
            $stmt2->fetch();
            $stmt2->close();
            return intval($id2 ?? 0);
        }

        return 0;
    }

    $novo_id = intval($ins->insert_id);
    $ins->close();

    return $novo_id;
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
    error_log('update_gasto.php: falha ao incluir conn.php: ' . $e->getMessage());
    responder(null, 500, ['success' => false, 'message' => 'Erro ao incluir arquivo de conexão']);
}

if (!($conn instanceof mysqli)) {
    responder(null, 500, ['success' => false, 'message' => 'Conexão inválida']);
}

/* ================================================================
   2) Autenticação
================================================================ */
$id_usuario = null;
if (!empty($_SESSION['user_id'])) {
    $id_usuario = intval($_SESSION['user_id']);
} elseif (!empty($_SESSION['id_usuario'])) {
    $id_usuario = intval($_SESSION['id_usuario']);
}

if (empty($id_usuario)) {
    responder($conn, 401, ['success' => false, 'message' => 'Usuário não autenticado']);
}

/* ================================================================
   3) Receber e validar JSON
================================================================ */
$raw   = file_get_contents('php://input');
$dados = json_decode($raw, true);

if (!is_array($dados)) {
    responder($conn, 400, ['success' => false, 'message' => 'Dados inválidos ou JSON mal formado']);
}

$id_gasto = isset($dados['id_gasto']) ? intval($dados['id_gasto']) : 0;
if ($id_gasto <= 0) {
    responder($conn, 400, ['success' => false, 'message' => 'ID do gasto inválido']);
}

/* Normaliza campos */
$descricao        = trim((string)($dados['descricao'] ?? ''));
$valor            = isset($dados['valor']) ? floatval($dados['valor']) : 0.0;
$tipoRecebido     = trim((string)($dados['tipo'] ?? 'variavel'));
$tipo             = in_array($tipoRecebido, TIPOS_VALIDOS, true) ? $tipoRecebido : 'variavel';
$metodo_pagamento = trim((string)($dados['metodo_pagamento'] ?? ''));
$data_gasto       = trim((string)($dados['data_gasto'] ?? ''));
$recorrente       = !empty($dados['recorrente']) ? 1 : 0;
$observacoes      = trim((string)($dados['observacoes'] ?? ''));

/* Validações */
if ($descricao === '') {
    responder($conn, 400, ['success' => false, 'message' => 'Descrição é obrigatória']);
}
if ($valor <= 0) {
    responder($conn, 400, ['success' => false, 'message' => 'Valor inválido']);
}
if ($data_gasto === '' || !dataValida($data_gasto)) {
    responder($conn, 400, ['success' => false, 'message' => 'Data do gasto inválida']);
}

/* ================================================================
   4) Atualizar gasto + resolver categoria (tudo na mesma transação)

   Assim como em gastos.php, a categoria só é resolvida depois de
   abrir a transação: se o UPDATE falhar depois, uma categoria nova
   criada aqui é desfeita junto pelo rollback.
================================================================ */
if (!$conn->begin_transaction()) {
    error_log('update_gasto.php begin_transaction error: ' . $conn->error);
    responder($conn, 500, ['success' => false, 'message' => 'Erro ao iniciar transação']);
}

try {

    /* Verifica se o gasto pertence ao usuário */
    $checkStmt = $conn->prepare("SELECT id_gasto FROM gastos WHERE id_gasto = ? AND id_usuario = ? LIMIT 1");
    if ($checkStmt === false) {
        throw new RuntimeException('Erro ao preparar verificação: ' . $conn->error);
    }

    $checkStmt->bind_param('ii', $id_gasto, $id_usuario);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows === 0) {
        $checkStmt->close();
        responder($conn, 404, ['success' => false, 'message' => 'Gasto não encontrado ou sem permissão'], true);
    }
    $checkStmt->close();

    /* --- Resolver categoria_id -------------------------------- */
    $categoria_id = isset($dados['categoria_id']) ? intval($dados['categoria_id']) : null;

    if ($categoria_id === 0 || $categoria_id === null) {

        /* Sem id numérico: usa o nome */
        $categoria_input = trim((string)($dados['categoria'] ?? ''));

        if ($categoria_input === '' || $categoria_input === 'nova') {
            throw new InvalidArgumentException('Categoria não informada');
        }

        $categoria_descricao = trim((string)($dados['categoria_descricao'] ?? ''));
        $categoria_id = buscarOuCriarCategoria($conn, $categoria_input, $categoria_descricao);

    } else {

        /* Valida que a categoria_id enviada existe de verdade */
        $chk = $conn->prepare("SELECT id_categoria FROM categorias WHERE id_categoria = ? LIMIT 1");
        if ($chk === false) {
            throw new RuntimeException('Erro ao preparar validação de categoria: ' . $conn->error);
        }

        $chk->bind_param('i', $categoria_id);
        $chk->execute();
        $chk->store_result();
        $existe = $chk->num_rows > 0;
        $chk->close();

        if (!$existe) {
            throw new InvalidArgumentException('Categoria não encontrada');
        }
    }

    if (empty($categoria_id)) {
        throw new RuntimeException('Não foi possível determinar a categoria');
    }

    /* --- Atualiza o gasto --------------------------------------- */
    $sql = "UPDATE gastos
            SET categoria_id = ?, descricao = ?, valor = ?, tipo = ?,
                metodo_pagamento = ?, data_gasto = ?, recorrente = ?,
                observacoes = ?, atualizado_em = NOW()
            WHERE id_gasto = ? AND id_usuario = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new RuntimeException('Erro ao preparar update: ' . $conn->error);
    }

    /* Tipos: i (categoria_id), s (descricao), d (valor), s (tipo), s (metodo),
       s (data), i (recorrente), s (observacoes), i (id_gasto), i (id_usuario) */
    $stmt->bind_param(
        'isdsssisii',
        $categoria_id,
        $descricao,
        $valor,
        $tipo,
        $metodo_pagamento,
        $data_gasto,
        $recorrente,
        $observacoes,
        $id_gasto,
        $id_usuario
    );

    if (!$stmt->execute()) {
        throw new RuntimeException('Erro ao executar update: ' . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        /* Pode ser porque nada mudou (dados idênticos) — não é necessariamente erro,
           mas mantemos o comportamento original de tratar como falha explícita. */
        throw new RuntimeException('Nenhum gasto foi atualizado');
    }

    $stmt->close();

    if (!$conn->commit()) {
        throw new RuntimeException('Erro ao fazer commit: ' . $conn->error);
    }

    responder($conn, 200, [
        'success'      => true,
        'message'      => 'Gasto atualizado com sucesso!',
        'id_gasto'     => $id_gasto,
        'categoria_id' => $categoria_id
    ]);

} catch (InvalidArgumentException $e) {
    /* Erros de validação: seguros para mostrar ao usuário */
    responder($conn, 400, ['success' => false, 'message' => $e->getMessage()], true);

} catch (Throwable $e) {
    /* Erros internos: log detalhado no servidor, mensagem genérica pro cliente */
    error_log('update_gasto.php error: ' . $e->getMessage());
    responder($conn, 500, ['success' => false, 'message' => 'Erro ao atualizar gasto'], true);
}