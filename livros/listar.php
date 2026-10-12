
<?php

require_once __DIR__ . '/../includes/proteger_acesso.php';

exigirCargo(['Administrador', 'Acervo']);

require_once __DIR__ . '/../includes/conexao.php';

$pesquisa = trim($_GET['pesquisa'] ?? '');

$sql = "
    SELECT
        l.id_livro,
        l.titulo,
        l.autor,
        l.ano_publicacao,
        c.nome AS categoria,

        COUNT(e.id_exemplar) AS total_exemplares,

        COALESCE(SUM(
            CASE
                WHEN e.status = 'disponivel' THEN 1
                ELSE 0
            END
        ), 0) AS exemplares_disponiveis,

        COALESCE(SUM(
            CASE
                WHEN e.status = 'emprestado' THEN 1
                ELSE 0
            END
        ), 0) AS exemplares_emprestados

    FROM livros l

    LEFT JOIN categorias c
        ON l.id_categoria = c.id_categoria

    LEFT JOIN exemplares e
        ON l.id_livro = e.id_livro
";

if ($pesquisa !== '') {
    $sql .= "
        WHERE l.titulo LIKE :titulo
           OR l.autor LIKE :autor
           OR c.nome LIKE :categoria
    ";
}

$sql .= "
    GROUP BY
        l.id_livro,
        l.titulo,
        l.autor,
        l.ano_publicacao,
        c.nome

    ORDER BY l.titulo
";

$consulta = $conexao->prepare($sql);

if ($pesquisa !== '') {
    $termo = '%' . $pesquisa . '%';

    $consulta->execute([
        ':titulo' => $termo,
        ':autor' => $termo,
        ':categoria' => $termo
    ]);
} else {
    $consulta->execute();
}

$livros = $consulta->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livros cadastrados</title>
</head>

<body>

    <h1>Livros da Biblioteca Municipal de Arujá</h1>

    <form method="GET" action="listar.php">

        <label for="pesquisa">
            Pesquisar por título, autor ou categoria:
        </label>

        <br><br>

        <input
            type="search"
            name="pesquisa"
            id="pesquisa"
            placeholder="Digite sua pesquisa..."
            value="<?= htmlspecialchars($pesquisa, ENT_QUOTES, 'UTF-8') ?>"
        >

        <button type="submit">Pesquisar</button>

        <a href="listar.php">Limpar pesquisa</a>

    </form>

    <hr>

    <h2>
        <?= $pesquisa !== ''
            ? 'Resultados da pesquisa'
            : 'Todos os livros' ?>
    </h2>

    <p>
        <?= count($livros) ?> livro(s) encontrado(s).
    </p>

    <?php if (count($livros) > 0): ?>

        <?php foreach ($livros as $livro): ?>

            <article>

                <h3>
                    <?= htmlspecialchars(
                        $livro['titulo'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </h3>

                <p>
                    Autor:
                    <?= htmlspecialchars(
                        $livro['autor'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

                <p>
                    Ano:
                    <?= htmlspecialchars(
                        (string) ($livro['ano_publicacao'] ?? 'Não informado'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

                <p>
                    Categoria:
                    <?= htmlspecialchars(
                        $livro['categoria'] ?? 'Sem categoria',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

                <p>
                    Total de exemplares:
                    <?= (int) $livro['total_exemplares'] ?>
                </p>

                <p>
                    Exemplares disponíveis:
                    <?= (int) $livro['exemplares_disponiveis'] ?>
                </p>

                <p>
                    Exemplares emprestados:
                    <?= (int) $livro['exemplares_emprestados'] ?>
                </p>

                <hr>

            </article>

        <?php endforeach; ?>

    <?php else: ?>

        <p>Nenhum livro encontrado para essa pesquisa.</p>

    <?php endif; ?>

    <p>
        <a href="../painel/servidor.php">Voltar ao painel</a>
    </p>

</body>
</html>

