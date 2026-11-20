<?php
/* ================================================================
   gastos.php — Finanças Já
   Arquivo: ../php/gastos.php

   Recebe JSON do frontend e insere um gasto no banco.

   IMPORTANTE: a data_gasto é SEMPRE gerada aqui no servidor
   com date('Y-m-d'), ignorando qualquer data enviada pelo
   frontend. Isso garante que o gasto sempre cai no mês atual
   do servidor, evitando os problemas de virada de mês e
   entradas manuais incorretas.
================================================================ */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();

/* Tipos de gasto aceitos pelo app (o resto vira 'variavel') */
const TIPOS_VALIDOS = ['fixo', 'variavel'];

/* ================================================================
   Função auxiliar: responde em JSON e encerra a execução.
   Fecha a conexão (com rollback opcional) antes de sair, para
   nunca deixar uma transação pendurada nem duplicar o padrão
   http_response_code + json_encode + exit pelo arquivo todo.
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
   Função auxiliar: busca categoria por nome ou cria uma nova.
   Deve sempre ser chamada DENTRO da transação do chamador, para
   que uma categoria recém-criada seja desfeita junto se o
   restante da operação falhar.
================================================================ */
function buscarOuCriarCategoria(mysqli $conn, string $nome): int
{
    /* 1) Busca pelo nome (case-insensitive) */
    $stmt = $conn->prepare(
        "SELECT id_categoria FROM categorias WHERE LOWER(nome) = LOWER(?) LIMIT 1"
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
         VALUES (?, 'despesa', '', NOW(), NOW(), 1)"
    );
    if (!$ins) return 0;

    $ins->bind_param('s', $nome);

    if (!$ins->execute()) {
        error_log('gastos.php buscarOuCriarCategoria insert error: ' . $ins->error);
        $ins->close();
        return 0;
    }

    $novo_id = intval($ins->insert_id);
    $ins->close();

    /* 3) Se houve race condition (chave duplicada), busca novamente */
    if ($novo_id === 0) {
        $stmt2 = $conn->prepare(
            "SELECT id_categoria FROM categorias WHERE LOWER(nome) = LOWER(?) LIMIT 1"
        );
        if ($stmt2) {
            $stmt2->bind_param('s', $nome);
            $stmt2->execute();
            $stmt2->bind_result($id2);
            $stmt2->fetch();
            $stmt2->close();
            return intval($id2 ?? 0);
        }
    }

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
    error_log('gastos.php: ' . $e->getMessage());
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

$descricao        = trim((string)($dados['descricao']        ?? ''));
$valor            = isset($dados['valor']) ? floatval($dados['valor']) : 0.0;
$tipoRecebido     = trim((string)($dados['tipo']             ?? 'variavel'));
$tipo             = in_array($tipoRecebido, TIPOS_VALIDOS, true) ? $tipoRecebido : 'variavel';
$metodo_pagamento = trim((string)($dados['metodo_pagamento'] ?? ''));
$recorrente       = !empty($dados['recorrente']) ? 1 : 0;
$observacoes      = trim((string)($dados['observacoes']      ?? ''));

/*
 * DATA GERADA PELO SERVIDOR
 * Independente do que o usuário digitou no formulário,
 * a data do gasto é sempre hoje no servidor.
 * Isso garante que o gasto seja listado no mês correto
 * tanto em get_gastos.php quanto no histórico.
 */
$data_gasto = date('Y-m-d');

/* Validações */
if ($descricao === '') {
    responder($conn, 400, ['success' => false, 'message' => 'Descrição é obrigatória']);
}
if ($valor <= 0) {
    responder($conn, 400, ['success' => false, 'message' => 'O valor deve ser maior que zero']);
}

/* ================================================================
   4) Inserir gasto + resolver categoria (tudo na mesma transação)

   A categoria é resolvida DEPOIS de abrir a transação: assim,
   se o gasto acabar não sendo salvo, uma categoria nova criada
   nessa mesma requisição é desfeita junto pelo rollback, em vez
   de ficar órfã no banco.
================================================================ */
$conn->begin_transaction();

/* --- Resolver categoria_id -----------------------------------
   Prioridade:
   a) categoria_id numérico válido existente no banco
   b) nome em categoria_nova ou categoria → busca ou cria
----------------------------------------------------------------- */
$categoria_id = null;

if (isset($dados['categoria_id']) && ctype_digit((string)$dados['categoria_id'])) {

    $tentativa = intval($dados['categoria_id']);

    /* Verifica se o id existe de fato */
    $chk = $conn->prepare("SELECT id_categoria FROM categorias WHERE id_categoria = ? LIMIT 1");
    if ($chk) {
        $chk->bind_param('i', $tentativa);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $categoria_id = $tentativa;
        }
        $chk->close();
    }

    /* Se não existir, tenta criar pelo nome enviado */
    if ($categoria_id === null) {
        $nome_cat = trim((string)($dados['categoria_nova'] ?? $dados['categoria'] ?? 'Outros'));
        if ($nome_cat === '') $nome_cat = 'Outros';
        $categoria_id = buscarOuCriarCategoria($conn, $nome_cat);
    }

} else {
    /* Sem id numérico: usa o nome */
    $nome_cat = trim((string)($dados['categoria_nova'] ?? $dados['categoria'] ?? ''));
    if ($nome_cat === '') {
        responder($conn, 400, ['success' => false, 'message' => 'Categoria não informada'], true);
    }
    $categoria_id = buscarOuCriarCategoria($conn, $nome_cat);
}

if (empty($categoria_id)) {
    responder($conn, 500, ['success' => false, 'message' => 'Não foi possível determinar a categoria'], true);
}

/* --- Inserir o gasto -------------------------------------------- */
$sql = "
    INSERT INTO gastos
        (id_usuario, categoria_id, descricao, valor, moeda, tipo,
         metodo_pagamento, data_gasto, recorrente, observacoes,
         status, criado_em, atualizado_em)
    VALUES
        (?, ?, ?, ?, 'BRL', ?, ?, ?, ?, ?, 'ativo', NOW(), NOW())
";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    error_log('gastos.php prepare error: ' . $conn->error);
    responder($conn, 500, ['success' => false, 'message' => 'Erro ao preparar inserção'], true);
}

/* tipos: i i s d s s s i s */
$stmt->bind_param(
    'iisdsssis',
    $id_usuario,
    $categoria_id,
    $descricao,
    $valor,
    $tipo,
    $metodo_pagamento,
    $data_gasto,
    $recorrente,
    $observacoes
);

if (!$stmt->execute()) {
    error_log('gastos.php execute error: ' . $stmt->error);
    $stmt->close();
    responder($conn, 500, ['success' => false, 'message' => 'Erro ao salvar o gasto'], true);
}

$inserted_id = $stmt->insert_id;
$stmt->close();
$conn->commit();

responder($conn, 200, [
    'success'      => true,
    'message'      => 'Gasto salvo com sucesso!',
    'id_gasto'     => $inserted_id,
    'categoria_id' => $categoria_id,
    'data_gasto'   => $data_gasto       // retorna a data real usada
]);