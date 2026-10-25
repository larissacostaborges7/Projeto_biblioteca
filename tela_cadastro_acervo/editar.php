
<?php
require_once __DIR__ . '/../includes/proteger_acesso.php';
require_once __DIR__ . '/../includes/conexao.php';

exigirCargo(['Administrador', 'Acervo']);

$pdo = $conexao;

// ==================================================
// 1. VALIDAR O ID DO LIVRO
// ==================================================

$idLivro = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idLivro || $idLivro < 1) {
    exit('Identificador do livro inválido.');
}

// ==================================================
// 2. FUNÇÃO PARA BUSCAR O LIVRO
// ==================================================

function buscarLivro(PDO $pdo, int $idLivro): array|false
{
    $stmt = $pdo->prepare("
        SELECT
            l.id_livro,
            l.titulo,
            l.autor,
            l.sinopse,
            l.isbn,
            l.editora,
            l.ano_publicacao,
            l.id_categoria,
            c.nome AS nome_categoria
        FROM livros l
        LEFT JOIN categorias c
            ON c.id_categoria = l.id_categoria
        WHERE l.id_livro = :id
    ");

    $stmt->execute([':id' => $idLivro]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$livro = buscarLivro($pdo, $idLivro);

if (!$livro) {
    exit('Livro não encontrado.');
}

// ==================================================
// 3. MENSAGENS
// ==================================================

$mensagem = '';
$tipoMensagem = '';

// ==================================================
// 4. PROCESSAR A EDIÇÃO
// ==================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $autor = trim($_POST['autor'] ?? '');
    $sinopse = trim($_POST['sinopse'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $editora = trim($_POST['editora'] ?? '');
    $anoTexto = trim($_POST['ano'] ?? '');

    $idCategoria = filter_input(
        INPUT_POST,
        'categoria',
        FILTER_VALIDATE_INT
    );

    // Validar título e autor
    if ($titulo === '' || $autor === '') {
        $mensagem = 'Preencha o título e o autor.';
        $tipoMensagem = 'alerta-erro';
    }

    // Validar ano de publicação
    $ano = null;

    if ($mensagem === '' && $anoTexto !== '') {
        $anoValidado = filter_var(
            $anoTexto,
            FILTER_VALIDATE_INT
        );

        if (
            $anoValidado === false ||
            $anoValidado < 1 ||
            $anoValidado > (int) date('Y')
        ) {
            $mensagem = 'Informe um ano de publicação válido.';
            $tipoMensagem = 'alerta-erro';
        } else {
            $ano = $anoValidado;
        }
    }

    // Validar categoria e atualizar
    if ($mensagem === '') {
        try {
            if (
                $idCategoria === false ||
                $idCategoria === null ||
                $idCategoria < 1
            ) {
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

            // Atualizar o livro
            $stmt = $pdo->prepare("
                UPDATE livros
                SET
                    titulo = :titulo,
                    autor = :autor,
                    sinopse = :sinopse,
                    isbn = :isbn,
                    editora = :editora,
                    ano_publicacao = :ano,
                    id_categoria = :id_categoria
                WHERE id_livro = :id
            ");

            $stmt->execute([
                ':titulo' => $titulo,
                ':autor' => $autor,
                ':sinopse' => $sinopse !== '' ? $sinopse : null,
                ':isbn' => $isbn !== '' ? $isbn : null,
                ':editora' => $editora !== '' ? $editora : null,
                ':ano' => $ano,
                ':id_categoria' => $idCategoria,
                ':id' => $idLivro
            ]);

            // Recarregar os dados atualizados
            $livro = buscarLivro($pdo, $idLivro);

            if (!$livro) {
                throw new RuntimeException(
                    'Não foi possível recarregar o livro.'
                );
            }

            $mensagem = 'Livro atualizado com sucesso!';
            $tipoMensagem = 'alerta-sucesso';

        } catch (Throwable $e) {
            error_log($e->getMessage());

            if ($e instanceof RuntimeException) {
                $mensagem = $e->getMessage();
            } else {
                $mensagem = 'Não foi possível atualizar o livro.';
            }

            $tipoMensagem = 'alerta-erro';
        }
    }
}

// ==================================================
// 5. CARREGAR AS CATEGORIAS
// ==================================================

$categorias = $pdo->query("
    SELECT id_categoria, nome
    FROM categorias
    ORDER BY nome ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Livro - Biblioteca</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<main class="conteudo-principal">

    <header class="cabecalho-superior">
        <h1>Editar livro</h1>
        <p>Corrija os dados do livro cadastrado.</p>
    </header>

    <section class="painel-formulario">

        <?php if ($mensagem !== ''): ?>
            <div class="<?= htmlspecialchars($tipoMensagem, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form
            action="editar.php?id=<?= (int) $livro['id_livro'] ?>"
            method="POST"
        >

            <div class="form-grid">

                <div class="form-group">
                    <label for="titulo">Título *</label>
                    <input
                        type="text"
                        id="titulo"
                        name="titulo"
                        value="<?= htmlspecialchars($livro['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="autor">Autor *</label>
                    <input
                        type="text"
                        id="autor"
                        name="autor"
                        value="<?= htmlspecialchars($livro['autor'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="isbn">ISBN</label>
                    <input
                        type="text"
                        id="isbn"
                        name="isbn"
                        maxlength="20"
                        value="<?= htmlspecialchars($livro['isbn'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="editora">Editora</label>
                    <input
                        type="text"
                        id="editora"
                        name="editora"
                        value="<?= htmlspecialchars($livro['editora'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="ano">Ano de publicação</label>
                    <input
                        type="number"
                        id="ano"
                        name="ano"
                        min="1"
                        max="<?= date('Y') ?>"
                        value="<?= htmlspecialchars((string) ($livro['ano_publicacao'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="categoria">Categoria *</label>

                    <select id="categoria" name="categoria" required>
                        <option value="">Selecione uma categoria</option>

                        <?php foreach ($categorias as $cat): ?>
                            <option
                                value="<?= (int) $cat['id_categoria'] ?>"
                                <?= (int) ($livro['id_categoria'] ?? 0) === (int) $cat['id_categoria'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="sinopse">Sinopse</label>
                    <textarea
                        id="sinopse"
                        name="sinopse"
                        rows="6"
                    ><?= htmlspecialchars($livro['sinopse'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

            </div>

            <button type="submit" class="botao">
                Salvar alterações
            </button>

            <a href="listar.php">Cancelar e voltar</a>

        </form>

    </section>

</main>

</body>
</html>
