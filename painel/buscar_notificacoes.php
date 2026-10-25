
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (!isset($_SESSION['id_funcionario'])) {
    http_response_code(401);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Sessão expirada. Entre novamente.'
    ]);
    exit;
}

require_once __DIR__ . '/../includes/conexao.php';

try {
    $hoje = date('Y-m-d');

    $sql = "
        SELECT
            SUM(CASE
                WHEN data_prevista_devolucao < ?
                THEN 1 ELSE 0
            END) AS atrasados,

            SUM(CASE
                WHEN data_prevista_devolucao = ?
                THEN 1 ELSE 0
            END) AS vencem_hoje,

            SUM(CASE
                WHEN data_prevista_devolucao > ?
                 AND data_prevista_devolucao <= DATE_ADD(?, INTERVAL 3 DAY)
                THEN 1 ELSE 0
            END) AS proximos
        FROM emprestimos
        WHERE status = 'ativo'
    ";

    $stmt = $conexao->prepare($sql);
    $stmt->execute([
        $hoje,
        $hoje,
        $hoje,
        $hoje
    ]);

    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    $dados = [
        'sucesso' => true,
        'atrasados' => (int) ($resultado['atrasados'] ?? 0),
        'vencem_hoje' => (int) ($resultado['vencem_hoje'] ?? 0),
        'proximos' => (int) ($resultado['proximos'] ?? 0)
    ];

    $dados['total'] =
        $dados['atrasados'] +
        $dados['vencem_hoje'] +
        $dados['proximos'];

    echo json_encode($dados);

} catch (Throwable $e) {
    error_log('Erro ao buscar notificações: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Não foi possível consultar as notificações.'
    ]);
}
