<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();

// Para testes locais: comente a verificação de sessão se quiser simular
// (mesmo login temporário de header.php/Gastos.php — id_usuario = 1)
if (!isset($_SESSION['id_usuario']) && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

/* ================================================================
   Conexão — capturando o retorno de require, igual a todo o resto
   do projeto (get_gastos.php, get_renda.php, saveRenda.php, etc.).
   Antes este arquivo era o único que fazia só "require_once" sem
   capturar $conn, deixando a variável "indefinida" para qualquer
   analisador estático e frágil caso conn.php mude no futuro.
================================================================ */
$connPath = __DIR__ . '/../../Conexao/conn.php';
if (!is_file($connPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Arquivo de conexão não encontrado']);
    exit;
}

try {
    $conn = require $connPath;
} catch (Throwable $e) {
    error_log('dashboard_data.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao conectar ao banco de dados']);
    exit;
}

if (!($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['error' => 'Conexão inválida']);
    exit;
}

$userId = (int)($_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? 0);
$firstDay = date('Y-m-01 00:00:00');
$lastDay  = date('Y-m-t 23:59:59');

// Inicializa valores padrão
$gastosTotais = 0.0;
$categories = [];
$monthly = array_fill(0, 12, 0.0);

try {
    // Gastos totais do mês
    $sql = "SELECT COALESCE(SUM(valor),0) AS total FROM gastos WHERE id_usuario = ? AND data_gasto BETWEEN ? AND ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $userId, $firstDay, $lastDay);
    $stmt->execute();
    $res = $stmt->get_result();
    $gastosTotais = (float)($res->fetch_assoc()['total'] ?? 0);

    // Gastos por categoria
    $sql = "SELECT categoria, COALESCE(SUM(valor),0) AS total FROM gastos WHERE id_usuario = ? AND data_gasto BETWEEN ? AND ? AND deleted_at IS NULL GROUP BY categoria ORDER BY total DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $userId, $firstDay, $lastDay);
    $stmt->execute();
    $res = $stmt->get_result();

    $totalByCategories = 0.0;
    while ($row = $res->fetch_assoc()) {
        $val = (float)$row['total'];
        $categories[] = ['categoria' => $row['categoria'], 'total' => $val];
        $totalByCategories += $val;
    }

    foreach ($categories as &$c) {
        $c['percent'] = $totalByCategories > 0 ? round(($c['total'] / $totalByCategories) * 100, 1) : 0;
    }
    unset($c);

    // Gastos por mês do ano atual (opcional)
    $year = date('Y');
    $sql = "SELECT MONTH(data_gasto) AS mes, COALESCE(SUM(valor),0) AS total FROM gastos WHERE id_usuario = ? AND YEAR(data_gasto) = ? AND deleted_at IS NULL GROUP BY MONTH(data_gasto) ORDER BY mes";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $year);
    $stmt->execute();
    $res = $stmt->get_result();

    // monthly: índice 0 = janeiro
    $monthly = array_fill(0, 12, 0.0);
    while ($row = $res->fetch_assoc()) {
        $m = (int)$row['mes'];
        if ($m >= 1 && $m <= 12) $monthly[$m - 1] = (float)$row['total'];
    }

} catch (Exception $e) {
    // Em caso de erro, retornamos defaults e registramos no log do servidor
    error_log('dashboard_data error: ' . $e->getMessage());
}

// Monta resposta consistente (sempre)
$response = [
    'gastos_totais' => $gastosTotais,
    'categories' => $categories,   // array vazio se não houver
    'monthly' => $monthly         // array de 12 valores (zeros se não houver)
];

echo json_encode($response);
exit;
?>