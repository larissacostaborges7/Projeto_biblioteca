<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/conexao.php';

$naoLogado = !isset($_SESSION['id_usuario']);
$erro = '';
$sucesso = '';
$reservas = [];
$livros = [];

function h($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

if (!$naoLogado) {
    $idUsuario = (int) $_SESSION['id_usuario'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $acao = $_POST['acao'] ?? '';

        if ($acao === 'cadastrar_reserva') {
            $idLivro = filter_input(INPUT_POST, 'id_livro', FILTER_VALIDATE_INT);

            if (!$idLivro) {
                $erro = 'Selecione um livro válido.';
            } else {
                try {
                    $stmt = $conexao->prepare(
                        "SELECT id_reserva
                         FROM reservas
                         WHERE id_usuario = ?
                           AND id_livro = ?
                           AND status = 'ativa'"
                    );
                    $stmt->execute([$idUsuario, $idLivro]);

                    if ($stmt->fetch()) {
                        $erro = 'Você já possui uma reserva ativa para este livro.';
                    } else {
                        $stmt = $conexao->prepare(
                            "INSERT INTO reservas
                             (id_usuario, id_livro, data_reserva, status)
                             VALUES (?, ?, ?, 'ativa')"
                        );

                        $stmt->execute([
                            $idUsuario,
                            $idLivro,
                            date('Y-m-d')
                        ]);

                        header('Location: reservas.php?sucesso=1');
                        exit;
                    }
                } catch (PDOException $e) {
                    error_log($e->getMessage());
                    $erro = 'Não foi possível registrar a reserva.';
                }
            }
        }

        if ($acao === 'cancelar_reserva') {
            $idReserva = filter_input(
                INPUT_POST,
                'id_reserva',
                FILTER_VALIDATE_INT
            );

            if ($idReserva) {
                try {
                    $stmt = $conexao->prepare(
                        "UPDATE reservas
                         SET status = 'cancelada'
                         WHERE id_reserva = ?
                           AND id_usuario = ?
                           AND status = 'ativa'"
                    );

                    $stmt->execute([$idReserva, $idUsuario]);

                    if ($stmt->rowCount() > 0) {
                        header('Location: reservas.php?cancelada=1');
                        exit;
                    }

                    $erro = 'Não foi possível cancelar esta reserva.';
                } catch (PDOException $e) {
                    error_log($e->getMessage());
                    $erro = 'Ocorreu um erro ao cancelar a reserva.';
                }
            } else {
                $erro = 'Reserva inválida.';
            }
        }
    }

    if (isset($_GET['sucesso'])) {
        $sucesso = 'Livro reservado com sucesso!';
    } elseif (isset($_GET['cancelada'])) {
        $sucesso = 'Reserva cancelada com sucesso!';
    }

    try {
        $stmt = $conexao->prepare(
            "SELECT r.*, l.titulo, l.autor
             FROM reservas r
             INNER JOIN livros l ON l.id_livro = r.id_livro
             WHERE r.id_usuario = ?
             ORDER BY r.data_reserva DESC, r.id_reserva DESC"
        );

        $stmt->execute([$idUsuario]);
        $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $livros = $conexao->query(
            "SELECT id_livro, titulo, autor
             FROM livros
             ORDER BY titulo"
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $erro = 'Não foi possível carregar as reservas.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas reservas | Biblioteca Municipal</title>
    <link rel="stylesheet" href="style.css?v=3">
</head>
<body>

<aside class="menu-lateral">
    <a href="../tela_principal2/index.php" class="logo">
        <span class="logo-icone">📚</span>
        <span class="logo-texto">
            <span class="logo-nome">Biblioteca Municipal</span>
            <span class="logo-descricao">Conhecimento para todos</span>
        </span>
    </a>

    <span class="menu-titulo">Área do leitor</span>

    <nav>
        <a href="../tela_principal2/index.php">
            <span>⌂</span> Início
        </a>

        <a href="index.php">
            <span>▤</span> Meus empréstimos
        </a>

        <a href="reservas.php" class="ativo">
            <span>▣</span> Minhas reservas
        </a>

        <a href="historico.php">
            <span>◷</span> Histórico
        </a>

        <a href="../tela_acervo_usuario/tela_acervo_usuario.php">
            <span>📚</span> Consultar acervo
        </a>
    </nav>

    <div class="menu-rodape">
        <a href="../tela_cadastro/sair_usuario.php">↪ Sair da conta</a>
    </div>
</aside>

<main class="conteudo-principal">
    <header class="cabecalho-superior">
        <div>
            <p class="texto-secundario">ÁREA DO LEITOR</p>
            <h1>Minhas reservas</h1>
            <p>Reserve livros e acompanhe suas solicitações.</p>
        </div>

        <?php if (!$naoLogado): ?>
            <span class="perfil">
                👤 <?= h($_SESSION['nome_usuario'] ?? 'Leitor') ?>
            </span>
        <?php endif; ?>
    </header>

    <?php if ($naoLogado): ?>

        <section class="alerta-caixa">
            <h2>Entre na sua conta</h2>
            <p>Faça login como leitor para consultar suas reservas.</p>
            <a href="../tela_cadastro/index.php" class="botao">Entrar na conta</a>
        </section>

    <?php else: ?>

        <?php if ($erro !== ''): ?>
            <div class="alerta-caixa alerta-erro">
                <?= h($erro) ?>
            </div>
        <?php endif; ?>

        <?php if ($sucesso !== ''): ?>
            <div class="alerta-caixa alerta-sucesso">
                <?= h($sucesso) ?>
            </div>
        <?php endif; ?>

        <section class="painel-tabela">
            <div class="titulo-tabela">
                <div>
                    <h2>Reservar um livro</h2>
                    <p>Escolha uma obra do acervo.</p>
                </div>
            </div>

            <form method="POST" class="form-reserva">
                <input type="hidden" name="acao" value="cadastrar_reserva">

               
<label for="busca_livro">Pesquisar livro</label>

<input
    type="search"
    id="busca_livro"
    placeholder="Digite o título ou o autor..."
    autocomplete="off"
    aria-controls="sugestoes_livros"
    aria-autocomplete="list"
>

<div id="sugestoes_livros" class="sugestoes-livros"></div>

<input type="hidden" name="id_livro" id="id_livro" required>


                <button type="submit" class="botao">
                    Confirmar reserva
                </button>
            </form>
        </section>

        <section class="painel-tabela">
            <div class="titulo-tabela">
                <div>
                    <h2>Seus livros reservados</h2>
                    <p>Acompanhe o status das suas reservas.</p>
                </div>
            </div>

            <?php if (empty($reservas)): ?>

                <div class="alerta-caixa">
                    <h3>Nenhuma reserva encontrada</h3>
                    <p>Quando você reservar um livro, ele aparecerá aqui.</p>
                </div>

            <?php else: ?>

                <div class="tabela-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Livro</th>
                                <th>Data da reserva</th>
                                <th>Status</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservas as $item): ?>
                                <tr>
                                    <td class="titulo-livro">
                                        <?= h($item['titulo']) ?>
                                        <?php if (!empty($item['autor'])): ?>
                                            <br>
                                            <small><?= h($item['autor']) ?></small>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php
                                        $data = $item['data_reserva'] ?? '';
                                        echo $data
                                            ? h(date('d/m/Y', strtotime($data)))
                                            : '—';
                                        ?>
                                    </td>

                                    <td>
                                        <span class="badge <?= ($item['status'] ?? '') === 'ativa' ? 'badge-ativo' : 'badge-cancelada' ?>">
                                            <?= h(ucfirst($item['status'] ?? 'Indefinido')) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if (($item['status'] ?? '') === 'ativa'): ?>
                                            <form method="POST"
                                                  onsubmit="return confirm('Deseja cancelar esta reserva?');">
                                                <input type="hidden" name="acao" value="cancelar_reserva">
                                                <input type="hidden" name="id_reserva" value="<?= (int) $item['id_reserva'] ?>">
                                                <button type="submit" class="botao-cancelar">
                                                    Cancelar
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </section>

    <?php endif; ?>

    <footer class="rodape">
        Biblioteca Municipal de Arujá
        <span>•</span>
        Sistema de Gerenciamento de Bibliotecas
    </footer>
</main>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const campoBusca = document.getElementById('busca_livro');
    const campoIdLivro = document.getElementById('id_livro');
    const sugestoes = document.getElementById('sugestoes_livros');

    const livros = <?= json_encode(
        array_map(
            fn($livro) => [
                'id' => (int) $livro['id_livro'],
                'titulo' => $livro['titulo'],
                'autor' => $livro['autor'] ?? ''
            ],
            $livros
        ),
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) ?>;

    function mostrarSugestoes() {
        const termo = campoBusca.value.trim().toLocaleLowerCase('pt-BR');

        sugestoes.replaceChildren();
        campoIdLivro.value = '';

        if (termo.length === 0) {
            sugestoes.hidden = true;
            return;
        }

        const encontrados = livros
            .filter(livro =>
                livro.titulo.toLocaleLowerCase('pt-BR').includes(termo) ||
                livro.autor.toLocaleLowerCase('pt-BR').includes(termo)
            )
            .slice(0, 10);

        if (encontrados.length === 0) {
            const mensagem = document.createElement('p');
            mensagem.textContent = 'Nenhum livro encontrado.';
            sugestoes.appendChild(mensagem);
            sugestoes.hidden = false;
            return;
        }

        encontrados.forEach(livro => {
            const botao = document.createElement('button');
            botao.type = 'button';
            botao.className = 'sugestao-livro';
            botao.textContent = livro.titulo +
                (livro.autor ? ' — ' + livro.autor : '');

            botao.addEventListener('click', () => {
                campoBusca.value = livro.titulo;
                campoIdLivro.value = livro.id;
                sugestoes.replaceChildren();
                sugestoes.hidden = true;
            });

            sugestoes.appendChild(botao);
        });

        sugestoes.hidden = false;
    }

    campoBusca.addEventListener('input', mostrarSugestoes);

    campoBusca.addEventListener('keydown', evento => {
        if (evento.key === 'Escape') {
            sugestoes.hidden = true;
        }
    });

    campoBusca.addEventListener('change', () => {
        if (!campoIdLivro.value) {
            campoBusca.setCustomValidity(
                'Selecione um livro nas sugestões.'
            );
        } else {
            campoBusca.setCustomValidity('');
        }
    });

    campoBusca.addEventListener('input', () => {
        campoBusca.setCustomValidity('');
    });

    document.querySelector('.form-reserva').addEventListener('submit', evento => {
        if (!campoIdLivro.value) {
            evento.preventDefault();
            campoBusca.setCustomValidity(
                'Digite e selecione um livro nas sugestões.'
            );
            campoBusca.reportValidity();
        }
    });

    sugestoes.hidden = true;
});
</script>


</body>
</html>