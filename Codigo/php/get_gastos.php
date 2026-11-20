<?php
/* ================================================================
   get_gastos.php — Finanças Já
   Arquivo: ../php/get_gastos.php

   Retorna os gastos do mês atual do usuário autenticado.
   O intervalo de datas é calculado 100% no servidor,
   garantindo consistência independente do relógio do cliente.
================================================================ */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();

/* ================================================================
   1) Conexão
================================================================ */
$connPath = __DIR__ . '/../../Conexao/conn.php';
if (!is_file($connPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Arquivo de conexão não encontrado']);
    exit;
}
try {
    $conn = require $connPath;
} catch (Throwable $e) {
    error_log('get_gastos.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao conectar ao banco de dados']);
    exit;
}
if (!($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Conexão inválida']);
    exit;
}

/* ================================================================
   2) Autenticação
================================================================ */
$id_usuario = null;
if (!empty($_SESSION['user_id']))        $id_usuario = intval($_SESSION['user_id']);
elseif (!empty($_SESSION['id_usuario'])) $id_usuario = intval($_SESSION['id_usuario']);

if (empty($id_usuario)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

/* ================================================================
   3) Intervalo do mês atual (fonte de verdade = servidor)
================================================================ */
$hoje               = new DateTimeImmutable('today');
$primeiroDia        = $hoje->modify('first day of this month')->format('Y-m-d');
$primeiroDiaProximo = $hoje->modify('first day of next month')->format('Y-m-d');
$mesReferencia      = $hoje->format('Y-m');   // ex: "2026-06"

/* ================================================================
   4) Buscar gastos do mês atual
================================================================ */
try {
    $sql = "
        SELECT
            g.id_gasto,
            g.id_usuario,
            g.categoria_id,
            c.nome       AS categoria_nome,
            g.descricao,
            g.valor,
            g.moeda,
            g.tipo,
            g.metodo_pagamento,
            g.data_gasto,
            g.recorrente,
            g.observacoes,
            g.status,
            g.criado_em,
            g.atualizado_em
        FROM gastos g
        LEFT JOIN categorias c ON c.id_categoria = g.categoria_id
        WHERE
            g.id_usuario = ?
            AND g.data_gasto >= ?
            AND g.data_gasto <  ?
            AND g.status = 'ativo'
        ORDER BY
            g.data_gasto DESC,
            g.criado_em  DESC
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) throw new RuntimeException('Erro ao preparar consulta: ' . $conn->error);

    $stmt->bind_param('iss', $id_usuario, $primeiroDia, $primeiroDiaProximo);
    if (!$stmt->execute()) {
        $erro = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Erro ao executar consulta: ' . $erro);
    }

    $resultado = $stmt->get_result();
    $gastos    = [];

    while ($row = $resultado->fetch_assoc()) {
        $categoriaNome = $row['categoria_nome'] ?? null;
        $gastos[] = [
            'id_gasto'         => (int)   $row['id_gasto'],
            'id_usuario'       => (int)   $row['id_usuario'],
            'categoria_id'     => $row['categoria_id'] !== null ? (int) $row['categoria_id'] : null,
            'categoria'        => $categoriaNome,   // compatibilidade frontend
            'categoria_nome'   => $categoriaNome,
            'descricao'        => $row['descricao'],
            'valor'            => (float) $row['valor'],
            'moeda'            => $row['moeda'],
            'tipo'             => $row['tipo'],
            'metodo_pagamento' => $row['metodo_pagamento'] ?? '',
            'data_gasto'       => $row['data_gasto'],
            'recorrente'       => (int)   $row['recorrente'],
            'observacoes'      => $row['observacoes'],
            'status'           => $row['status'],
            'criado_em'        => $row['criado_em'],
            'atualizado_em'    => $row['atualizado_em']
        ];
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success'        => true,
        'mes_referencia' => $mesReferencia,
        'periodo'        => [
            'inicio' => $primeiroDia,
            'fim'    => (new DateTimeImmutable($primeiroDiaProximo))->modify('-1 day')->format('Y-m-d')
        ],
        'gastos'         => $gastos
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('get_gastos.php exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no servidor']);
}