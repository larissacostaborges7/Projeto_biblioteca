
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_funcionario'])) {
    http_response_code(401);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Sessão expirada. Entre novamente.'
    ]);
    exit;
}

require_once __DIR__ . '/../includes/conexao.php';

$termo = trim($_GET['q'] ?? '');

if ($termo === '') {
    echo json_encode([
        'sucesso' => true,
        'livros' => []
    ]);
    exit;
}

try {
    $busca = '%' . $termo . '%';

    $sql = "
        SELECT DISTINCT
            l.id_livro,
            l.titulo,
            l.autor,
            l.isbn,
            ex.codigo_patrimonio,
            ex.status
        FROM livros l
        LEFT JOIN exemplares ex
            ON ex.id_livro = l.id_livro
        WHERE l.titulo LIKE ?
           OR l.autor LIKE ?
           OR l.isbn LIKE ?
           OR ex.codigo_patrimonio LIKE ?
        ORDER BY l.titulo ASC
        LIMIT 20
    ";

    $stmt = $conexao->prepare($sql);
    $stmt->execute([$busca, $busca, $busca, $busca]);

    echo json_encode([
        'sucesso' => true,
        'livros' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log('Erro na busca rápida do acervo: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Não foi possível realizar a busca.'
    ], JSON_UNESCAPED_UNICODE);
}
