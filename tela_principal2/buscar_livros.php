<?php
header('Content-Type: application/json; charset=utf-8');

// Inclui o arquivo de conexão PDO que criamos
require_once __DIR__ . '/includes/conexao.php';

try {
    // Busca todos os livros cadastrados no MySQL
    $stmt = $conexao->query("
        SELECT 
            id, 
            titulo, 
            autor, 
            categoria, 
            (quantidade_disponivel > 0) AS disponivel, 
            capa, 
            descricao 
        FROM livros 
        ORDER BY id DESC
    ");
    
    $livros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Converte os tipos de dados para o padrão esperado pelo JavaScript
    foreach ($livros as &$livro) {
        $livro['id'] = (int)$livro['id'];
        $livro['disponivel'] = (bool)$livro['disponivel'];
        // Caso a capa esteja vazia no banco, define uma capa padrão ou fallback
        if (empty($livro['capa'])) {
            $livro['capa'] = "https://covers.openlibrary.org/b/isbn/9788594318241-M.jpg";
        }
    }

    echo json_encode(['sucesso' => true, 'livros' => $livros]);
} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao carregar acervo: ' . $e->getMessage()]);
}
?>