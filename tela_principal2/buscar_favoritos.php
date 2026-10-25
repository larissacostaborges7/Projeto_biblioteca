
<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode([
        'sucesso' => true,
        'favoritos' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $conexao->prepare(
        'SELECT id_livro
         FROM favoritos
         WHERE id_usuario = ?'
    );

    $stmt->execute([(int) $_SESSION['id_usuario']]);

    $favoritos = array_map(
        'intval',
        $stmt->fetchAll(PDO::FETCH_COLUMN)
    );

    echo json_encode([
        'sucesso' => true,
        'favoritos' => $favoritos
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Não foi possível carregar os favoritos.'
    ], JSON_UNESCAPED_UNICODE);
}
