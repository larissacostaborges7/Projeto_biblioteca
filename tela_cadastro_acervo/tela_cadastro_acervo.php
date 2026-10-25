
<?php
require_once __DIR__ . '/../includes/proteger_acesso.php';
require_once __DIR__ . '/../includes/conexao.php';

exigirCargo(['Administrador', 'Acervo']);

$pdo = $conexao;

$mensagemAlerta = '';
$tipoAlerta = '';
$livros = [];

try {
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['cadastrar_livro'])
    ) {
        $titulo = trim($_POST['titulo'] ?? '');
        $autor = trim($_POST['autor'] ?? '');
        $sinopse = trim($_POST['sinopse'] ?? '');
$isbn = trim($_POST['isbn'] ?? '');
        $editora = trim($_POST['editora'] ?? '');
        $ano = filter_var(
            $_POST['ano'] ?? '',
            FILTER_VALIDATE_INT
        );

        $classificacoesPermitidas = [
    'Livre',
    '10 anos',
    '12 anos',
    '14 anos',
    '16 anos',
    '18 anos'
];

$classificacaoEtaria = trim(
    $_POST['classificacao_etaria'] ?? ''
);

if (
    $classificacaoEtaria !== '' &&
    !in_array(
        $classificacaoEtaria,
        $classificacoesPermitidas,
        true
    )
) {
    throw new RuntimeException(
        'Selecione uma classificação etária válida.'
    );
}
       $idCategoria = filter_input(
    INPUT_POST,
    'categoria',
    FILTER_VALIDATE_INT
);

        $exemplares = filter_var(
            $_POST['exemplares'] ?? '',
            FILTER_VALIDATE_INT
        );

        if ($titulo === '' || $autor === '') {
            throw new RuntimeException(
                'Preencha o título e o autor.'
            );
        }

        if (
            $ano === false ||
            ($ano !== null && $ano !== 0 &&
            ($ano < 1 || $ano > (int) date('Y')))
        ) {
            throw new RuntimeException(
                'Informe um ano de publicação válido.'
            );
        }

      if ($idCategoria === false || $idCategoria === null || $idCategoria < 1) {
    throw new RuntimeException(
        'Selecione uma categoria válida.'
    );
}

$stmtCategoria = $pdo->prepare("
    SELECT COUNT(*)
    FROM categorias
    WHERE id_categoria = :id_categoria
");

$stmtCategoria->execute([
    ':id_categoria' => $idCategoria
]);

if ((int) $stmtCategoria->fetchColumn() === 0) {
    throw new RuntimeException(
        'A categoria selecionada não existe.'
    );
}
$caminhoCapa = null;

if (
    isset($_FILES['capa']) &&
    $_FILES['capa']['error'] !== UPLOAD_ERR_NO_FILE
) {
    if ($_FILES['capa']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(
            'Ocorreu um erro ao enviar a capa.'
        );
    }

    if ($_FILES['capa']['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException(
            'A capa deve ter no máximo 5 MB.'
        );
    }

    $arquivoTemporario = $_FILES['capa']['tmp_name'];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $tipoImagem = $finfo->file($arquivoTemporario);

    $tiposPermitidos = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($tiposPermitidos[$tipoImagem])) {
        throw new RuntimeException(
            'Envie uma capa em JPG, PNG ou WEBP.'
        );
    }

    $pastaCapas = __DIR__ . '/uploads/';

    if (
        !is_dir($pastaCapas) ||
        !is_writable($pastaCapas)
    ) {
        throw new RuntimeException(
            'A pasta uploads não existe ou não permite gravação.'
        );
    }

    $nomeArquivo = bin2hex(random_bytes(16))
        . '.' . $tiposPermitidos[$tipoImagem];

    if (
        !move_uploaded_file(
            $arquivoTemporario,
            $pastaCapas . $nomeArquivo
        )
    ) {
        throw new RuntimeException(
            'Não foi possível salvar a capa.'
        );
    }

    $caminhoCapa = 'uploads/' . $nomeArquivo;
}
        $pdo->beginTransaction();

      $stmt = $pdo->prepare("
    INSERT INTO livros
        (
            titulo,
            autor,
            sinopse,
            isbn,
            editora,
            ano_publicacao,
            classificacao_etaria,
            id_categoria,
            capa
        )
    VALUES
        (
            :titulo,
            :autor,
            :sinopse,
            :isbn,
            :editora,
            :ano,
            :classificacao_etaria,
            :id_categoria,
            :capa
        )
");

      $stmt->execute([
    ':titulo' => $titulo,
    ':autor' => $autor,
    ':sinopse' => $sinopse !== '' ? $sinopse : null,
    ':isbn' => $isbn !== '' ? $isbn : null,
    ':editora' => $editora !== '' ? $editora : null,
    ':ano' => $ano ?: null,
    ':classificacao_etaria' => $classificacaoEtaria !== ''
    ? $classificacaoEtaria
    : null,
    ':id_categoria' => $idCategoria,
    ':capa' => $caminhoCapa
]);

        $idLivro = (int) $pdo->lastInsertId();

        $stmtExemplar = $pdo->prepare("
            INSERT INTO exemplares (id_livro)
            VALUES (:id_livro)
        ");

        for ($i = 0; $i < $exemplares; $i++) {
            $stmtExemplar->execute([
                ':id_livro' => $idLivro
            ]);
        }

        $pdo->commit();

        $mensagemAlerta =
            'Livro e exemplares cadastrados com sucesso!';
        $tipoAlerta = 'alerta-sucesso';

    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e instanceof RuntimeException) {
        $mensagemAlerta = $e->getMessage();
    } else {
        error_log($e->getMessage());
        $mensagemAlerta =
            'Não foi possível cadastrar o livro. Verifique os dados e tente novamente.';
    }

    $tipoAlerta = 'alerta-erro';
}

try {
    $livros = $pdo->query("
        SELECT
            l.id_livro,
            l.titulo,
            l.autor,
            l.sinopse,
            l.isbn,
            l.editora,
            l.ano_publicacao,
l.classificacao_etaria,
            l.id_categoria,
            c.nome AS nome_categoria,
            l.capa,
            COUNT(DISTINCT ex.id_exemplar) AS total_exemplares,
            COUNT(DISTINCT CASE
                WHEN ex.status = 'disponivel'
                THEN ex.id_exemplar
            END) AS exemplares_disponiveis
        FROM livros l
LEFT JOIN categorias c
    ON c.id_categoria = l.id_categoria
LEFT JOIN exemplares ex
    ON ex.id_livro = l.id_livro
        GROUP BY l.id_livro
        ORDER BY l.id_livro ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $totalTitulos = (int) $pdo->query("
        SELECT COUNT(*) FROM livros
    ")->fetchColumn();

    $totalExemplares = (int) $pdo->query("
        SELECT COUNT(*) FROM exemplares
    ")->fetchColumn();

    $totalCategorias = (int) $pdo->query("
    SELECT COUNT(DISTINCT id_categoria)
    FROM livros
    WHERE id_categoria IS NOT NULL
")->fetchColumn();

} catch (Throwable $e) {
    error_log($e->getMessage());

    $livros = [];
    $totalTitulos = 0;
    $totalExemplares = 0;
    $totalCategorias = 0;

    if ($mensagemAlerta === '') {
        $mensagemAlerta =
            'Não foi possível carregar os dados do acervo.';
        $tipoAlerta = 'alerta-erro';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Acervo - Biblioteca</title>
    <link rel="stylesheet" href="style.css?v=3">
</head>
<body>

    <!-- Menu Lateral -->
    <aside class="menu-lateral">
        <a href="#" class="logo">
            <div class="logo-icone">📚</div>
            <div class="logo-texto">
                <span class="logo-nome">Biblioteca</span>
                <span class="logo-descricao">Sistema de Acervo</span>
            </div>
        </a>
        <div class="menu-titulo">Navegação</div>
        <nav>
            <a href="#" class="ativo">📖 Cadastrar Acervo</a>
            <a href="listar.php">📋 Listar Livros</a>
            <a href="../painel/servidor.php">⬅ Voltar ao painel</a>
            <a href="../includes/logout.php">🚪 Sair do sistema</a>
        </nav>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="conteudo-principal">
        
        <!-- Bloco de Notificações Ativas -->
        <div id="container-notificacoes">
            <?php if (!empty($notificacoes)): ?>
                <?php foreach ($notificacoes as $notif): ?>
                    <div class="caixa-notificacao" id="notif-<?= $notif['id'] ?>">
                        <div>
                            🔔 <strong>Aviso do Sistema:</strong> <?= htmlspecialchars($notif['mensagem']) ?>
                        </div>
                        <button class="botao-ignorar" onclick="marcarComoLida(<?= $notif['id'] ?>)">
                            Não ler mensagem novamente
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <header class="cabecalho-superior">
            <div>
                <span class="texto-secundario">GERENCIAMENTO</span>
                <h1>Cadastro de Acervo</h1>
                <p>Adicione novas obras e mantenha o sistema atualizado.</p>
            </div>
        </header>

        <!-- Cards de Resumo -->
        <section class="cards-resumo">
            <div class="card-resumo">
                <p>Total de Títulos</p>
                <strong><?= $totalTitulos ?></strong>
            </div>
            <div class="card-resumo" style="border-left-color: #319c73;">
                <p>Exemplares Físicos</p>
                <strong><?= $totalExemplares ?></strong>
            </div>
            <div class="card-resumo" style="border-left-color: #0879ad;">
                <p>Categorias Únicas</p>
                <strong><?= $totalCategorias ?></strong>
            </div>
        </section>

        <!-- Alertas de Feedback -->
        <?php if (!empty($mensagemAlerta)): ?>
            <div class="<?= $tipoAlerta ?>"><?= $mensagemAlerta ?></div>
        <?php endif; ?>

        <!-- Formulário de Cadastro -->
        <section class="painel-formulario">
            <h2>Novo Registro de Livro</h2>
         <form action="" method="POST" enctype="multipart/form-data"></form>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="titulo">Título da Obra *</label>
                        <input type="text" id="titulo" name="titulo" required placeholder="Ex: Dom Casmurro">
                    </div>
                    <div class="campo">
    <label for="capa">Capa do livro:</label>
    <input
        type="file"
        name="capa"
        id="capa"
        accept=".jpg,.jpeg,.png,.webp"
    >
    <small>Formatos permitidos: JPG, PNG e WEBP.</small>
</div>
                    <div class="form-group">
                        <label for="autor">Autor *</label>
                        <input type="text" id="autor" name="autor" required placeholder="Ex: Machado de Assis">
                    </div>
                    <div class="form-group">
    <label for="isbn">ISBN</label>
    <input
        type="text"
        id="isbn"
        name="isbn"
        maxlength="20"
        placeholder="Ex: 9788535914849"
    >
</div>

<div class="form-group">
    <label for="sinopse">Sinopse do livro</label>
    <textarea
        id="sinopse"
        name="sinopse"
        rows="5"
        placeholder="Escreva um resumo sobre a obra..."
    ></textarea>
</div>
                    <div class="form-group">
                        <label for="editora">Editora</label>
                        <input type="text" id="editora" name="editora" placeholder="Ex: Companhia das Letras">
                    </div>
                    <div class="form-group">
                        <label for="ano">Ano de Publicação</label>
                        <input type="number" id="ano" name="ano" placeholder="Ex: 1899">
                    </div>

                    
<div class="form-group">
    <label for="classificacao_etaria">Classificação por faixa etária</label>
    <select
        id="classificacao_etaria"
        name="classificacao_etaria"
    >
        <option value="">Selecione uma classificação</option>
        <option value="Livre">Livre</option>
        <option value="10 anos">10 anos</option>
        <option value="12 anos">12 anos</option>
        <option value="14 anos">14 anos</option>
        <option value="16 anos">16 anos</option>
        <option value="18 anos">18 anos</option>
    </select>
    <small>Confira a indicação etária da edição antes de cadastrar.</small>
</div>

                    <div class="form-group">
    <label for="categoria">Categoria / Gênero</label>
    <select id="categoria" name="categoria" required>
        <option value="">Selecione uma categoria</option>
        <?php
        $categorias = $pdo->query("
            SELECT id_categoria, nome
            FROM categorias
            ORDER BY nome ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($categorias as $cat):
        ?>
            <option value="<?= (int) $cat['id_categoria'] ?>">
                <?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
                    <div class="form-group">
                        <label for="exemplares">Quantidade de Exemplares</label>
                        <input type="number" id="exemplares" name="exemplares" value="1" min="1">
                    </div>
                </div>
                <button type="submit" name="cadastrar_livro" class="botao">Salvar no Acervo</button>
            </form>
        </section>

    </main>

    <script src="script.js"></script>
</body>
</html>