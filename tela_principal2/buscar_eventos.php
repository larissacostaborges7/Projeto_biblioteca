
<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/conexao.php';

try {
    $stmt = $conexao->query("
        SELECT
            titulo,
            descricao,
            data_evento,
            imagem
        FROM eventos
        ORDER BY data_evento ASC
    ");

    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(
        $eventos,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        'erro' => 'Não foi possível carregar os eventos.'
    ], JSON_UNESCAPED_UNICODE);
}
