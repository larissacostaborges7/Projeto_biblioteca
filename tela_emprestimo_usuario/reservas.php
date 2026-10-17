```php
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
    <link rel="stylesheet" href="style.css">
</head>
<body>

<aside class="menu-lateral">
    <a href="../painel/usuario.php" class="logo">
        <span class="logo-icone">📚</span>
        <span class="logo-texto">
            <span class="logo-nome">Biblioteca Municipal</span>
            <span class="logo-descricao">Conhecimento para todos</span>
        </span>
    </a>

    <span class="menu-titulo">Área do leitor</span>

    <nav>
        <a href="../painel/usuario.php">
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

        <a href="../livros/listar.php">
            <span>📚</span> Consultar acervo
        </a>
    </nav>

    <div class="menu-rodape">
        <a href="../login/sair_usuario.php">↪ Sair da conta</a>
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
            <a href="../login/usuario.php" class="botao">Entrar na conta</a>
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

                <label for="id_livro">Livro</label>

                <select name="id_livro" id="id_livro" required>
                    <option value="">Selecione um livro</option>

                    <?php foreach ($livros as $livro): ?>
                        <option value="<?= (int) $livro['id_livro'] ?>">
                            <?= h($livro['titulo']) ?>
                            <?= !empty($livro['autor']) ? ' — ' . h($livro['autor']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>

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

</body>
</html