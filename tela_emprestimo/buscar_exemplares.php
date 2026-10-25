
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/proteger_acesso.php';

exigirCargo(['Administrador', 'Atendente']);

$termo = trim($_GET['termo'] ?? '');

if ($termo === '') {
    echo json_encode([]);
    exit;
}

try {
    $busca = '%' . $termo . '%';

    $stmt = $conexao->prepare("
        SELECT
            e.id_exemplar,
            e.codigo_patrimonio,
            l.id_livro,
            l.titulo,
            l.autor,
            l.categoria,
            l.isbn
        FROM exemplares e
        INNER JOIN livros l ON l.id_livro = e.id_livro
        WHERE e.status = 'disponivel'
          AND (
              l.titulo LIKE ?
              OR l.autor LIKE ?
              OR l.categoria LIKE ?
              OR l.isbn LIKE ?
              OR e.codigo_patrimonio LIKE ?
          )
        ORDER BY l.titulo
        LIMIT 20
    ");

    $stmt->execute([
        $busca,
        $busca,
        $busca,
        $busca,
        $busca
    ]);

    echo json_encode(
        $stmt->fetchAll(PDO::FETCH_ASSOC),
        JSON_UNESCAPED_UNICODE
    );
} catch (Throwable $e) {
    error_log('Erro ao pesquisar exemplares: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao pesquisar exemplares.']);
}
