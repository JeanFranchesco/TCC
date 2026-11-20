<?php
/* ================================================================
   get_historico.php — Finanças Já
   Arquivo: ../php/get_historico.php

   Modos de uso:
   - Sem parâmetros ou só ?ano=YYYY  → retorna totais mensais do ano
   - ?ano=YYYY&mes=MM               → retorna detalhes do mês
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
    error_log('get_historico.php: ' . $e->getMessage());
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
   3) Parâmetros de entrada
================================================================ */
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : (int) date('Y');
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : null;

/* ================================================================
   4) Consulta
================================================================ */
try {

    if ($mes !== null) {

        /* ────────────────────────────────────────────────────────
           Modo MENSAL: detalhes de gastos + receitas de um mês
        ──────────────────────────────────────────────────────── */
        $primeiroDia           = sprintf('%04d-%02d-01', $ano, $mes);
        $primeiroDiaProximoMes = date('Y-m-01', strtotime($primeiroDia . ' +1 month'));

        /* Totais */
        $stmtGastosSum = $conn->prepare(
            "SELECT COALESCE(SUM(valor), 0) AS total
             FROM gastos
             WHERE id_usuario = ? AND data_gasto >= ? AND data_gasto < ? AND status = 'ativo'"
        );
        $stmtGastosSum->bind_param('iss', $id_usuario, $primeiroDia, $primeiroDiaProximoMes);
        $stmtGastosSum->execute();
        $totalGastos = (float) $stmtGastosSum->get_result()->fetch_assoc()['total'];
        $stmtGastosSum->close();

        $stmtRecSum = $conn->prepare(
            "SELECT COALESCE(SUM(valor), 0) AS total
             FROM receitas
             WHERE id_usuario = ? AND data_receita >= ? AND data_receita < ?"
        );
        $stmtRecSum->bind_param('iss', $id_usuario, $primeiroDia, $primeiroDiaProximoMes);
        $stmtRecSum->execute();
        $totalReceitas = (float) $stmtRecSum->get_result()->fetch_assoc()['total'];
        $stmtRecSum->close();

        /* Detalhes de gastos */
        $stmtGastos = $conn->prepare("
            SELECT
                g.id_gasto, g.id_usuario, g.categoria_id,
                c.nome AS categoria_nome,
                g.descricao, g.valor, g.moeda, g.tipo,
                g.metodo_pagamento, g.data_gasto, g.recorrente,
                g.observacoes, g.status, g.criado_em, g.atualizado_em
            FROM gastos g
            LEFT JOIN categorias c ON c.id_categoria = g.categoria_id
            WHERE g.id_usuario = ?
              AND g.data_gasto >= ?
              AND g.data_gasto < ?
              AND g.status = 'ativo'
            ORDER BY g.data_gasto DESC, g.criado_em DESC
        ");
        $stmtGastos->bind_param('iss', $id_usuario, $primeiroDia, $primeiroDiaProximoMes);
        $stmtGastos->execute();
        $resGastos = $stmtGastos->get_result();
        $gastos = [];
        while ($row = $resGastos->fetch_assoc()) {
            $catNome  = $row['categoria_nome'] ?? null;
            $gastos[] = [
                'id_gasto'         => (int)   $row['id_gasto'],
                'id_usuario'       => (int)   $row['id_usuario'],
                'categoria_id'     => $row['categoria_id'] !== null ? (int) $row['categoria_id'] : null,
                'categoria'        => $catNome,
                'categoria_nome'   => $catNome,
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
        $stmtGastos->close();

        /* Detalhes de receitas */
        $stmtRec = $conn->prepare("
            SELECT id_receita, id_usuario, descricao, valor, data_receita, tipo, criado_em
            FROM receitas
            WHERE id_usuario = ?
              AND data_receita >= ?
              AND data_receita < ?
            ORDER BY data_receita DESC
        ");
        $stmtRec->bind_param('iss', $id_usuario, $primeiroDia, $primeiroDiaProximoMes);
        $stmtRec->execute();
        $resRec   = $stmtRec->get_result();
        $receitas = [];
        while ($row = $resRec->fetch_assoc()) {
            $receitas[] = [
                'id_receita'   => (int)   $row['id_receita'],
                'id_usuario'   => (int)   $row['id_usuario'],
                'descricao'    => $row['descricao'],
                'valor'        => (float) $row['valor'],
                'data_receita' => $row['data_receita'],
                'tipo'         => $row['tipo'],
                'criado_em'    => $row['criado_em']
            ];
        }
        $stmtRec->close();

        $conn->close();

        echo json_encode([
            'success' => true,
            'summary' => [
                'total_gastos'   => $totalGastos,
                'total_receitas' => $totalReceitas
            ],
            'transacoes' => [
                'gastos'   => $gastos,
                'receitas' => $receitas
            ]
        ], JSON_UNESCAPED_UNICODE);

    } else {

        /* ────────────────────────────────────────────────────────
           Modo ANUAL: totais de gastos e receitas por mês
        ──────────────────────────────────────────────────────── */
        $stmtAnual = $conn->prepare("
            SELECT
                m.mes,
                COALESCE(g.gastos,   0) AS gastos,
                COALESCE(r.receitas, 0) AS receitas
            FROM (
                SELECT 1  AS mes UNION SELECT 2  UNION SELECT 3  UNION SELECT 4
                UNION SELECT 5  UNION SELECT 6  UNION SELECT 7  UNION SELECT 8
                UNION SELECT 9  UNION SELECT 10 UNION SELECT 11 UNION SELECT 12
            ) m
            LEFT JOIN (
                SELECT MONTH(data_gasto) AS mes, SUM(valor) AS gastos
                FROM gastos
                WHERE YEAR(data_gasto) = ? AND id_usuario = ? AND status = 'ativo'
                GROUP BY MONTH(data_gasto)
            ) g ON g.mes = m.mes
            LEFT JOIN (
                SELECT MONTH(data_receita) AS mes, SUM(valor) AS receitas
                FROM receitas
                WHERE YEAR(data_receita) = ? AND id_usuario = ?
                GROUP BY MONTH(data_receita)
            ) r ON r.mes = m.mes
            ORDER BY m.mes
        ");

        $stmtAnual->bind_param('iiii', $ano, $id_usuario, $ano, $id_usuario);
        $stmtAnual->execute();
        $resAnual = $stmtAnual->get_result();

        $dadosAnuais = [];
        while ($row = $resAnual->fetch_assoc()) {
            $dadosAnuais[] = [
                'mes'      => (int)   $row['mes'],
                'gastos'   => (float) $row['gastos'],
                'receitas' => (float) $row['receitas']
            ];
        }
        $stmtAnual->close();
        $conn->close();

        echo json_encode([
            'success'      => true,
            'dados_anuais' => $dadosAnuais
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Throwable $e) {
    error_log('get_historico.php exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no servidor']);
}