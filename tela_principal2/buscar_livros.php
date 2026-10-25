
<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/conexao.php';

try {
    $sql = "
        SELECT
            l.id_livro AS id,
            l.titulo,
            l.autor,
            COALESCE(l.categoria, 'Sem categoria') AS categoria,
            COALESCE(l.sinopse, '') AS descricao,
            COALESCE(l.capa, '') AS capa,
            CASE
                WHEN COUNT(e.id_exemplar) > 0
                 AND SUM(
                     CASE
                         WHEN e.status = 'disponivel' THEN 1
                         ELSE 0
                     END
                 ) > 0
                THEN 1
                ELSE 0
            END AS disponivel
        FROM livros l
        LEFT JOIN exemplares e
            ON e.id_livro = l.id_livro
        GROUP BY
            l.id_livro,
            l.titulo,
            l.autor,
            l.categoria,
            l.sinopse,
            l.capa
        ORDER BY l.id_livro DESC
    ";

    $stmt = $conexao->query($sql);
    $livros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($livros as &$livro) {
        $livro['id'] = (int) $livro['id'];
        $livro['disponivel'] = (bool) $livro['disponivel'];

        if (empty($livro['capa'])) {
            $livro['capa'] = '';
        }
    }
    unset($livro);

    echo json_encode([
        'sucesso' => true,
        'livros' => $livros
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Não foi possível carregar o acervo.'
    ], JSON_UNESCAPED_UNICODE);
}
