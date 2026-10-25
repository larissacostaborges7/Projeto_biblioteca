
<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Faça login como usuário para favoritar livros.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);

$idLivro = filter_var(
    $dados['id_livro'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$idLivro || $idLivro <= 0) {
    http_response_code(400);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Livro inválido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $idUsuario = (int) $_SESSION['id_usuario'];

    $stmt = $conexao->prepare(
        'SELECT id_favorito
         FROM favoritos
         WHERE id_usuario = ? AND id_livro = ?'
    );

    $stmt->execute([$idUsuario, $idLivro]);
    $favorito = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($favorito) {
        $stmt = $conexao->prepare(
            'DELETE FROM favoritos
             WHERE id_usuario = ? AND id_livro = ?'
        );

        $stmt->execute([$idUsuario, $idLivro]);

        $favoritado = false;
        $mensagem = 'Livro removido dos favoritos.';
    } else {
        $stmt = $conexao->prepare(
            'INSERT INTO favoritos (id_usuario, id_livro)
             VALUES (?, ?)'
        );

        $stmt->execute([$idUsuario, $idLivro]);

        $favoritado = true;
        $mensagem = 'Livro adicionado aos favoritos.';
    }

    echo json_encode([
        'sucesso' => true,
        'favoritado' => $favoritado,
        'mensagem' => $mensagem
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Não foi possível atualizar os favoritos.'
    ], JSON_UNESCAPED_UNICODE);
}
