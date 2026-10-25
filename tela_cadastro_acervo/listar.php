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
l.classificacao_etaria,
c.nome AS categoria,
        COUNT(e.id_exemplar) AS total_exemplares,
        COALESCE(SUM(CASE WHEN e.status = 'disponivel' THEN 1 ELSE 0 END), 0) AS exemplares_disponiveis,
        COALESCE(SUM(CASE WHEN e.status = 'emprestado' THEN 1 ELSE 0 END), 0) AS exemplares_emprestados
    FROM livros l
    LEFT JOIN categorias c ON l.id_categoria = c.id_categoria
    LEFT JOIN exemplares e ON l.id_livro = e.id_livro
";

if ($pesquisa !== '') {
    $sql .= " WHERE l.titulo LIKE :titulo OR l.autor LIKE :autor OR c.nome LIKE :categoria ";
}

$sql .= " GROUP BY l.id_livro, l.titulo, l.autor, l.ano_publicacao, c.nome ORDER BY l.titulo ";

$consulta = $conexao->prepare($sql);

if ($pesquisa !== '') {
    $termo = '%' . $pesquisa . '%';
    $consulta->execute([':titulo' => $termo, ':autor' => $termo, ':categoria' => $termo]);
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
    <title>Listar Livros - Biblioteca Arujá</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Estilos complementares para a listagem manter o padrão */
        .grid-livros {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        .card-livro {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .card-livro h3 {
            color: #0f172a;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }
        .card-livro p {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 6px;
        }
        .badges-status {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            margin-bottom: 15px;
            font-size: 0.85rem;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
        }
        .badge-disp { background: #d1fae5; color: #065f46; }
        .badge-emp { background: #fee2e2; color: #b91c1c; }
        .barra-pesquisa {
            display: flex;
            gap: 10px;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            margin-bottom: 25px;
        }
        .barra-pesquisa input {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
        }
    </style>
</head>
<body>

    <!-- Menu Lateral -->
    <aside class="menu-lateral">
        <a href="#" class="logo">
            <div class="logo-icone">📚</div>
            <div class="logo-texto">
                <span class="logo-nome">Biblioteca</span>
                <span class="logo-descricao">Arujá</span>
            </div>
        </a>
        <div class="menu-titulo">Navegação</div>
        <nav>
            <a href="../tela_cadastro_acervo/tela_cadastro_acervo.php">📖 Cadastrar Acervo</a>
            <a href="listar.php" class="ativo">📋 Listar Livros</a>
            <a href="../login/sair.php">🚪 Sair</a>
        </nav>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="conteudo-principal">
        
        <header class="cabecalho-superior">
            <div>
                <span class="texto-secundario">ACERVO MUNICIPAL</span>
                <h1>Consulta de Livros</h1>
                <p>Consulte as obras disponíveis e o estado dos exemplares físicos.</p>
            </div>
        </header>

        <!-- Barra de Pesquisa -->
        <form method="GET" action="listar.php" class="barra-pesquisa">
            <input 
                type="search" 
                name="pesquisa" 
                id="pesquisa" 
                placeholder="Pesquisar por título, autor ou categoria..." 
                value="<?= htmlspecialchars($pesquisa, ENT_QUOTES, 'UTF-8') ?>"
            >
            <button type="submit" class="botao" style="padding: 10px 20px;">Pesquisar</button>
            <?php if ($pesquisa !== ''): ?>
                <a href="listar.php" style="align-self: center; color: #64748b; text-decoration: none; font-weight: 600;">Limpar</a>
            <?php endif; ?>
        </form>

        <h2>
            <?= $pesquisa !== '' ? 'Resultados da pesquisa' : 'Todos os livros cadastrados' ?> 
            <span style="font-size: 0.9rem; color: #64748b; font-weight: normal;">(<?= count($livros) ?> encontrados)</span>
        </h2>

        <?php if (count($livros) > 0): ?>
            <div class="grid-livros">
                <?php foreach ($livros as $livro): ?>
                    <article class="card-livro">
                       
<div>
    <h3><?= htmlspecialchars($livro['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>
    <p><strong>Autor:</strong> <?= htmlspecialchars($livro['autor'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Ano:</strong> <?= htmlspecialchars((string)($livro['ano_publicacao'] ?? 'N/D'), ENT_QUOTES, 'UTF-8') ?></p>
     <p><strong>Classificação etária:</strong> <?= htmlspecialchars($livro['classificacao_etaria'] ?? 'Não informada', ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Categoria:</strong> <?= htmlspecialchars($livro['categoria'] ?? 'Sem categoria', ENT_QUOTES, 'UTF-8') ?></p>
</div>

<div style="margin-top: 15px;">
    <a
        href="editar.php?id=<?= (int)$livro['id_livro'] ?>"
        class="botao"
        style="display: inline-block; text-decoration: none; text-align: center;"
    >
        ✏️ Editar livro
    </a>
</div>


                        <div>
                            <div class="badges-status">
                                <span class="badge badge-disp">Disponíveis: <?= (int)$livro['exemplares_disponiveis'] ?></span>
                                <span class="badge badge-emp">Emprestados: <?= (int)$livro['exemplares_emprestados'] ?></span>
                            </div>
                            <p style="font-size: 0.8rem; color: #94a3b8; border-top: 1px solid #f1f5f9; pt-2;">Total de exemplares: <strong><?= (int)$livro['total_exemplares'] ?></strong></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="background: #fff; padding: 30px; text-align: center; border-radius: 10px; border: 1px solid #e2e8f0; color: #64748b; margin-top: 20px;">
                <p>Nenhum livro encontrado para os critérios informados.</p>
            </div>
        <?php endif; ?>

    </main>

    <script src="script.js"></script>
</body>
</html>