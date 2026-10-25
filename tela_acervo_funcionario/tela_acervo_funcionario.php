
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/proteger_acesso.php';

// Permite que o Administrador, o funcionário do Acervo
// e o Atendente consultem os livros.
exigirCargo(['Administrador', 'Acervo', 'Atendente']);

$livros = [];
$erro = '';

try {
    $stmt = $conexao->query("
        SELECT
            l.id_livro,
            l.titulo,
            l.autor,
            l.sinopse,
            l.editora,
            l.classificacao_etaria,
            l.ano_publicacao,
            l.capa,
            c.nome AS nome_categoria,
            COUNT(ex.id_exemplar) AS total_exemplares,
            SUM(
                CASE
                    WHEN ex.status = 'disponivel' THEN 1
                    ELSE 0
                END
            ) AS exemplares_disponiveis
        FROM livros l
        LEFT JOIN categorias c
            ON c.id_categoria = l.id_categoria
        LEFT JOIN exemplares ex
            ON ex.id_livro = l.id_livro
        GROUP BY l.id_livro
        ORDER BY l.titulo ASC
    ");

    $livros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erro ao carregar acervo do funcionário: ' . $e->getMessage());
    $erro = 'Não foi possível carregar o acervo. Tente novamente.';
}

function escaparAcervo($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acervo do Funcionário - Biblioteca Municipal de Arujá</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<aside class="sidebar">
    <a href="../painel/servidor.php" class="brand">
        📚 <span>Biblioteca Municipal</span>
    </a>

    <nav class="nav-menu">
        <a href="../painel/servidor.php" class="nav-link">
            🏠 Painel do Funcionário
        </a>

        <a href="tela_acervo_funcionario.php" class="nav-link active">
            📚 Consultar Acervo
        </a>

        <a href="../tela_emprestimo/registrar.php" class="nav-link">
            📋 Registrar Empréstimo
        </a>

        <a href="../tela_emprestimo/devolver.php" class="nav-link">
            📥 Devoluções
        </a>

        <a href="../tela_emprestimo/reservas.php" class="nav-link">
            🔖 Reservas
        </a>

        <a href="../notificacoes/index.php" class="nav-link">
            🔔 Notificações
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="../painel/servidor.php" class="nav-link">
            ⬅ Voltar ao Painel
        </a>
    </div>
</aside>

<main class="main-content">
    <header class="page-header">
        <div>
            <h1>Consulta ao Acervo</h1>
            <p>Pesquise livros e confira a disponibilidade de exemplares.</p>
        </div>

        <div class="user-info">
            Funcionário:
            <strong>
                <?= escaparAcervo($_SESSION['nome_funcionario'] ?? 'Funcionário') ?>
            </strong>
        </div>
    </header>

    <section class="content-body">

        <?php if ($erro !== ''): ?>
            <div class="alert-error">
                <?= escaparAcervo($erro) ?>
            </div>
        <?php else: ?>

            <div class="resumo-acervo">
                <span>📚 Livros cadastrados</span>
                <strong><?= count($livros) ?></strong>
            </div>

            <div class="busca-container">
                <input
                    type="search"
                    id="buscaAcervo"
                    placeholder="Pesquisar por título, autor ou categoria..."
                    aria-label="Pesquisar livros"
                >
            </div>

            
<div class="grade-livros" id="gradeLivros">

    <?php foreach ($livros as $livro): ?>
        <article class="livro-card">

            <div class="livro-capa">
                <?php if (!empty($livro['capa'])): ?>
                    <img
                        src="../uploads/<?= escaparAcervo(basename($livro['capa'])) ?>"
                        alt="Capa de <?= escaparAcervo($livro['titulo']) ?>"
                        loading="lazy"
                    >
                <?php else: ?>
                    <div class="capa-indisponivel">
                        📚
                        <span>Sem capa</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="livro-info">

                <h2><?= escaparAcervo($livro['titulo']) ?></h2>

                <p>
                    <strong>Autor:</strong>
                    <?= escaparAcervo($livro['autor'] ?: 'Não informado') ?>
                </p>

                <p>
                    <strong>Categoria:</strong>
                    <?= escaparAcervo($livro['nome_categoria'] ?: 'Não informada') ?>
                </p>

                <p>
    <strong>Classificação etária:</strong>
    <?= escaparAcervo($livro['classificacao_etaria'] ?: 'Não informada') ?>
</p>

                <p class="livro-sinopse">
                    <strong>Sinopse:</strong>
                    <?= escaparAcervo($livro['sinopse'] ?: 'Sinopse não cadastrada.') ?>
                </p>

                <div class="disponibilidade">
                    <span>
                        Total de exemplares:
                        <?= (int) $livro['total_exemplares'] ?>
                    </span>

                    <span>
                        Disponíveis:
                        <?= (int) ($livro['exemplares_disponiveis'] ?? 0) ?>
                    </span>
                </div>

            </div>
        </article>

    <?php endforeach; ?>

</div>


            <p id="mensagemVazia" class="mensagem-vazia" hidden>
                Nenhum livro corresponde à sua pesquisa.
            </p>

        <?php endif; ?>

    </section>
</main>
<script src="script.js"></script>
</body>
</html>
