
<?php
session_start();

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/proteger_acesso.php';
require_once __DIR__ . '/../includes/registrar_log.php';

exigirCargo(['Administrador', 'Atendente']);

$erro = '';
$sucesso = '';
$listaEventos = [];

// ==================================================
// CADASTRAR EVENTO
// ==================================================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['acao'] ?? '') === 'cadastrar_evento'
) {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $dataEvento = trim($_POST['data_evento'] ?? '');

    $caminhoImagem = null;
    $arquivoSalvo = null;

    if ($titulo === '' || $dataEvento === '') {
        $erro = 'Preencha o título e a data do evento.';
    } else {
        try {
            // Valida a data recebida
            $dataValida = DateTime::createFromFormat('!Y-m-d', $dataEvento);

            if (
                !$dataValida
                || $dataValida->format('Y-m-d') !== $dataEvento
            ) {
                throw new RuntimeException('Informe uma data válida.');
            }

            // Processa a imagem, caso tenha sido selecionada
            if (
                isset($_FILES['imagem_evento'])
                && $_FILES['imagem_evento']['error'] !== UPLOAD_ERR_NO_FILE
            ) {
                $arquivo = $_FILES['imagem_evento'];

                if ($arquivo['error'] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException(
                        'Não foi possível enviar a imagem. Tente novamente.'
                    );
                }

                if ($arquivo['size'] > 5 * 1024 * 1024) {
                    throw new RuntimeException(
                        'A imagem deve ter no máximo 5 MB.'
                    );
                }

                if (!is_uploaded_file($arquivo['tmp_name'])) {
                    throw new RuntimeException('Arquivo enviado inválido.');
                }

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($arquivo['tmp_name']);

                $tiposPermitidos = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

                if (!isset($tiposPermitidos[$mime])) {
                    throw new RuntimeException(
                        'Envie uma imagem JPG, PNG ou WEBP.'
                    );
                }

                $pastaUploads = __DIR__ . '/../uploads_evento/';

                if (
                    !is_dir($pastaUploads)
                    && !mkdir($pastaUploads, 0755, true)
                    && !is_dir($pastaUploads)
                ) {
                    throw new RuntimeException(
                        'Não foi possível criar a pasta de imagens.'
                    );
                }

                $nomeArquivo = bin2hex(random_bytes(16))
                    . '.' . $tiposPermitidos[$mime];

                $arquivoSalvo = $pastaUploads . $nomeArquivo;

                if (
                    !move_uploaded_file(
                        $arquivo['tmp_name'],
                        $arquivoSalvo
                    )
                ) {
                    $arquivoSalvo = null;

                    throw new RuntimeException(
                        'Não foi possível salvar a imagem.'
                    );
                }

                $caminhoImagem = 'uploads_evento/' . $nomeArquivo;
            }

            // Identifica o funcionário logado
            $idFuncionario = $_SESSION['id_funcionario'] ?? null;

            if (!$idFuncionario) {
                throw new RuntimeException(
                    'Não foi possível identificar o funcionário logado.'
                );
            }

            // Salva os dados no banco
            $stmt = $conexao->prepare("
                INSERT INTO eventos
                    (titulo, descricao, data_evento, id_funcionario, imagem)
                VALUES
                    (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $titulo,
                $descricao,
                $dataEvento,
                $idFuncionario,
                $caminhoImagem
            ]);

            
$idEvento = (int) $conexao->lastInsertId();

// Cria uma notificação para cada usuário cadastrado
$stmtNotificacoes = $conexao->prepare("
    INSERT INTO notificacoes
        (id_usuario, mensagem, tipo, id_livro)
    SELECT
        id_usuario,
        ?,
        'novidade',
        NULL
    FROM usuarios
");

$mensagemNotificacao = "Novo evento na Biblioteca: " . $titulo;
$stmtNotificacoes->execute([$mensagemNotificacao]);

registrarLog(
    $conexao,
    'CADASTRO_EVENTO',
    'eventos',
    $idEvento,
    "Evento '$titulo' cadastrado."
);

$sucesso = 'Evento cadastrado e notificações enviadas aos usuários!';

        } catch (Throwable $e) {
            // Remove a imagem se o cadastro não for concluído
            if ($arquivoSalvo !== null && is_file($arquivoSalvo)) {
                unlink($arquivoSalvo);
            }

            error_log('Erro ao cadastrar evento: ' . $e->getMessage());

            $erro = 'Não foi possível cadastrar o evento. Confira os dados e tente novamente.';
        }
    }
}

// ==================================================
// EXCLUIR EVENTO
// ==================================================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['acao'] ?? '') === 'excluir_evento'
) {
    $idEvento = filter_input(
        INPUT_POST,
        'id_evento',
        FILTER_VALIDATE_INT
    );

    if (!$idEvento || $idEvento < 1) {
        $erro = 'Evento inválido.';
    } else {
        try {
            // Busca o evento antes da exclusão
            $stmtBusca = $conexao->prepare("
                SELECT titulo, imagem
                FROM eventos
                WHERE id_evento = ?
            ");
            $stmtBusca->execute([$idEvento]);
            $evento = $stmtBusca->fetch(PDO::FETCH_ASSOC);

            if (!$evento) {
                throw new RuntimeException('Evento não encontrado.');
            }

            // Exclui primeiro o registro do banco
            $stmtDel = $conexao->prepare("
                DELETE FROM eventos
                WHERE id_evento = ?
            ");
            $stmtDel->execute([$idEvento]);

            registrarLog(
                $conexao,
                'EXCLUSAO_EVENTO',
                'eventos',
                $idEvento,
                "Evento '{$evento['titulo']}' removido."
            );

            // Remove a imagem somente depois de excluir o registro
            if (!empty($evento['imagem'])) {
                $nomeImagem = basename($evento['imagem']);

                // Impede que o caminho saia da pasta de imagens
                if (
                    strpos($evento['imagem'], 'uploads_evento/') === 0
                    && $nomeImagem !== ''
                ) {
                    $arquivoImagem = __DIR__
                        . '/../uploads_evento/'
                        . $nomeImagem;

                    if (is_file($arquivoImagem)) {
                        unlink($arquivoImagem);
                    }
                }
            }

            $sucesso = 'Evento removido com sucesso!';
        } catch (Throwable $e) {
            error_log('Erro ao excluir evento: ' . $e->getMessage());

            $erro = 'Não foi possível remover o evento.';
        }
    }
}

// ==================================================
// BUSCAR EVENTOS CADASTRADOS
// ==================================================
try {
    $stmtEventos = $conexao->query("
        SELECT
            e.id_evento,
            e.titulo,
            e.descricao,
            e.data_evento,
            e.imagem,
            e.id_funcionario,
            f.nome AS nome_funcionario
        FROM eventos e
        LEFT JOIN funcionarios f
            ON f.id_funcionario = e.id_funcionario
        ORDER BY e.data_evento ASC, e.id_evento DESC
    ");

    $listaEventos = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Erro ao listar eventos: ' . $e->getMessage());
    $erro = 'Não foi possível carregar a lista de eventos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gerenciamento de Eventos - Biblioteca</title>

    <link rel="stylesheet" href="../notificacoes/style.css">

    <style>
        .campo-evento {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--cinza-claro, #ccc);
            border-radius: 6px;
            box-sizing: border-box;
            font: inherit;
        }

        .form-linha {
            display: flex;
            gap: 20px;
        }

        .form-campo {
            flex: 1;
            min-width: 0;
        }

        .form-campo label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .preview-evento {
            display: none;
            margin-top: 12px;
        }

        .preview-evento img {
            max-width: 300px;
            max-height: 200px;
            object-fit: contain;
            border-radius: 8px;
        }

        .imagem-tabela {
            width: 100px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
        }

        .mensagem-vazia {
            text-align: center;
            padding: 20px;
        }

        .tabela-eventos {
            width: 100%;
            border-collapse: collapse;
        }

        .tabela-eventos th,
        .tabela-eventos td {
            padding: 12px;
            text-align: left;
            vertical-align: middle;
        }

        .alerta-evento {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alerta-erro {
            background: #fde8e8;
            color: #9b1c1c;
        }

        .alerta-sucesso {
            background: #e6f4ea;
            color: #1e7e34;
        }

        .btn-excluir {
            background: #9b1c1c;
            padding: 8px 12px;
            font-size: 0.85rem;
            border: none;
            cursor: pointer;
        }

        @media (max-width: 700px) {
            .form-linha {
                flex-direction: column;
            }

            .table-responsive {
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>

    <!-- Menu lateral -->
    <aside>
        <a href="#" class="brand">
            📚 <span>Biblioteca Municipal</span>
        </a>

        <nav class="nav-menu">
            <a href="../tela_emprestimo/registrar.php" class="nav-link">
                📋 Registrar empréstimo
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

            <a href="index.php" class="nav-link active">
                🎉 Eventos
            </a>
        </nav>

        <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
            <a href="../painel/servidor.php" class="nav-link">
                ⬅ Voltar ao Painel
            </a>
        </div>
    </aside>

    <!-- Conteúdo principal -->
    <main>
        <header>
            <h1>Gerenciamento de Eventos e Avisos</h1>

            <div class="user-info">
                Logado como:
                <strong>
                    <?= htmlspecialchars(
                        $_SESSION['nome_funcionario'] ?? 'Funcionário',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
            </div>
        </header>

        <div class="content-body">

            <!-- Mensagens -->
            <?php if ($erro !== ''): ?>
                <div class="alerta-evento alerta-erro" role="alert">
                    <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($sucesso !== ''): ?>
                <div class="alerta-evento alerta-sucesso" role="status">
                    <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <!-- Formulário de cadastro -->
            <div class="card-container">
                <h2 class="card-title">
                    ➕ Cadastrar Novo Evento / Aviso
                </h2>

                <form
                    method="POST"
                    enctype="multipart/form-data"
                    style="margin-top: 15px; display: flex; flex-direction: column; gap: 15px;"
                >
                    <input
                        type="hidden"
                        name="acao"
                        value="cadastrar_evento"
                    >

                    <div class="form-linha">
                        <div class="form-campo" style="flex: 2;">
                            <label for="titulo">
                                Título do Evento *
                            </label>

                            <input
                                class="campo-evento"
                                type="text"
                                id="titulo"
                                name="titulo"
                                required
                                maxlength="255"
                                placeholder="Ex.: Feira de Troca de Livros"
                            >
                        </div>

                        <div class="form-campo">
                            <label for="data_evento">
                                Data do Evento *
                            </label>

                            <input
                                class="campo-evento"
                                type="date"
                                id="data_evento"
                                name="data_evento"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-campo">
                        <label for="descricao">
                            Descrição / Detalhes
                        </label>

                        <textarea
                            class="campo-evento"
                            id="descricao"
                            name="descricao"
                            rows="3"
                            placeholder="Informações sobre horário, local e público-alvo..."
                        ></textarea>
                    </div>

                    <div class="form-campo">
                        <label for="imagem_evento">
                            Imagem do Evento
                        </label>

                        <input
                            class="campo-evento"
                            type="file"
                            id="imagem_evento"
                            name="imagem_evento"
                            accept="image/jpeg,image/png,image/webp"
                        >

                        <small>
                            Formatos permitidos: JPG, PNG e WEBP.
                            Tamanho máximo: 5 MB.
                        </small>

                        <div
                            id="preview_container"
                            class="preview-evento"
                        >
                            <p>Pré-visualização da imagem:</p>

                            <img
                                id="preview_imagem"
                                src=""
                                alt="Pré-visualização da imagem do evento"
                            >
                        </div>
                    </div>

                    <div>
                        <button
                            type="submit"
                            class="btn"
                            style="cursor: pointer; border: none; padding: 10px 20px;"
                        >
                            Salvar Evento
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabela de eventos -->
            <div class="card-container">
                <h2 class="card-title">
                    📅 Eventos Programados
                </h2>

                <div class="table-responsive">
                    <table class="tabela-eventos">
                        <thead>
                            <tr>
                                <th>Imagem</th>
                                <th>Data</th>
                                <th>Título</th>
                                <th>Descrição</th>
                                <th>Cadastrado por</th>
                                <th>Ação</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($listaEventos)): ?>
                                <tr>
                                    <td colspan="6" class="mensagem-vazia">
                                        Nenhum evento cadastrado.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listaEventos as $ev): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($ev['imagem'])): ?>
                                                <img
                                                    class="imagem-tabela"
                                                    src="../<?= htmlspecialchars(
                                                        $ev['imagem'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                                                    alt="Imagem do evento"
                                                    loading="lazy"
                                                >
                                            <?php else: ?>
                                                <span>Sem imagem</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php
                                            $dataFormatada = strtotime(
                                                $ev['data_evento']
                                            );
                                            ?>

                                            <?= $dataFormatada
                                                ? htmlspecialchars(
                                                    date('d/m/Y', $dataFormatada),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                )
                                                : 'Data inválida'
                                            ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= htmlspecialchars(
                                                    $ev['titulo'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $ev['descricao'] ?? '-',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $ev['nome_funcionario'] ?? 'Sistema',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Tem certeza que deseja remover este evento?');"
                                                style="margin: 0;"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="acao"
                                                    value="excluir_evento"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id_evento"
                                                    value="<?= (int) $ev['id_evento'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-excluir"
                                                >
                                                    Excluir
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <!-- Pré-visualização da imagem -->
    <script>
        const campoImagem = document.getElementById('imagem_evento');
        const previewContainer = document.getElementById('preview_container');
        const previewImagem = document.getElementById('preview_imagem');

        campoImagem.addEventListener('change', function () {
            const arquivo = this.files[0];

            if (!arquivo) {
                previewImagem.src = '';
                previewContainer.style.display = 'none';
                return;
            }

            const tiposPermitidos = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            if (!tiposPermitidos.includes(arquivo.type)) {
                alert('Selecione uma imagem JPG, PNG ou WEBP.');
                this.value = '';
                previewImagem.src = '';
                previewContainer.style.display = 'none';
                return;
            }

            if (arquivo.size > 5 * 1024 * 1024) {
                alert('A imagem deve ter no máximo 5 MB.');
                this.value = '';
                previewImagem.src = '';
                previewContainer.style.display = 'none';
                return;
            }

            const leitor = new FileReader();

            leitor.onload = function (evento) {
                previewImagem.src = evento.target.result;
                previewContainer.style.display = 'block';
            };

            leitor.readAsDataURL(arquivo);
        });
    </script>

</body>
</html>
