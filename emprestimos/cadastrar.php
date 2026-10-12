```php
<?php
session_start();

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/proteger_acesso.php';
require_once __DIR__ . '/../includes/registrar_log.php';

exigirCargo(['Administrador', 'Atendente']);

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idUsuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
    $idExemplar = filter_input(INPUT_POST, 'id_exemplar', FILTER_VALIDATE_INT);
    $dataPrevista = trim($_POST['data_prevista_devolucao'] ?? '');

    $dataEmprestimo = date('Y-m-d');
    $idFuncionario = (int) $_SESSION['id_funcionario'];

    $dataValida = DateTime::createFromFormat('!Y-m-d', $dataPrevista);
    $dataValidaCorretamente = $dataValida
        && $dataValida->format('Y-m-d') === $dataPrevista;

    if (!$idUsuario || !$idExemplar || !$dataValidaCorretamente) {
        $erro = 'Preencha todos os campos corretamente.';
    } elseif ($dataPrevista < $dataEmprestimo) {
        $erro = 'A data prevista não pode ser anterior à data do empréstimo.';
    } else {
        try {
            $conexao->beginTransaction();

            // Confere se o usuário existe.
            $stmtUsuario = $conexao->prepare(
                'SELECT id_usuario FROM usuarios WHERE id_usuario = ? LIMIT 1'
            );
            $stmtUsuario->execute([$idUsuario]);

            if (!$stmtUsuario->fetch()) {
                throw new RuntimeException('Usuário não encontrado.');
            }

            // Bloqueia o exemplar durante a operação e confere sua disponibilidade.
            $stmtExemplar = $conexao->prepare(
                "SELECT id_exemplar, status
                 FROM exemplares
                 WHERE id_exemplar = ?
                 FOR UPDATE"
            );
            $stmtExemplar->execute([$idExemplar]);
            $exemplar = $stmtExemplar->fetch(PDO::FETCH_ASSOC);

            if (!$exemplar || $exemplar['status'] !== 'disponivel') {
                throw new RuntimeException('Este exemplar não está disponível.');
            }

            // Registra o empréstimo.
            $stmt = $conexao->prepare(
                "INSERT INTO emprestimos
                    (id_usuario, id_exemplar, id_funcionario,
                     data_emprestimo, data_prevista_devolucao, status)
                 VALUES (?, ?, ?, ?, ?, 'ativo')"
            );

            $stmt->execute([
                $idUsuario,
                $idExemplar,
                $idFuncionario,
                $dataEmprestimo,
                $dataPrevista
            ]);

            $idEmprestimo = (int) $conexao->lastInsertId();

            // Atualiza a situação do exemplar.
            $stmtAtualizar = $conexao->prepare(
                "UPDATE exemplares
                 SET status = 'emprestado'
                 WHERE id_exemplar = ? AND status = 'disponivel'"
            );
            $stmtAtualizar->execute([$idExemplar]);

            if ($stmtAtualizar->rowCount() !== 1) {
                throw new RuntimeException(
                    'Não foi possível atualizar a disponibilidade do exemplar.'
                );
            }

            registrarLog(
                $conexao,
                'EMPRESTIMO',
                'emprestimos',
                $idEmprestimo,
                'Empréstimo registrado.'
            );

            $conexao->commit();
            $sucesso = 'Empréstimo registrado com sucesso!';

        } catch (Throwable $erroOperacao) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }

            if ($erroOperacao instanceof RuntimeException) {
                $erro = $erroOperacao->getMessage();
            } else {
                error_log($erroOperacao->getMessage());
                $erro = 'Não foi possível registrar o empréstimo. Confira os dados e tente novamente.';
            }
        }
    }
}

// Carrega os usuários cadastrados.
$stmtUsuarios = $conexao->query(
    'SELECT id_usuario, nome, cpf
     FROM usuarios
     ORDER BY nome'
);
$usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

// Carrega apenas exemplares disponíveis.
$stmtExemplares = $conexao->query(
    "SELECT e.id_exemplar, e.codigo_patrimonio, l.titulo
     FROM exemplares e
     INNER JOIN livros l ON l.id_livro = e.id_livro
     WHERE e.status = 'disponivel'
     ORDER BY l.titulo, e.codigo_patrimonio"
);
$exemplares = $stmtExemplares->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar empréstimo</title>
</head>
<body>

    <h1>Registrar empréstimo</h1>

    <?php if ($erro !== ''): ?>
        <p style="color: red;">
            <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($sucesso !== ''): ?>
        <p style="color: green;">
            <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if (count($usuarios) === 0): ?>
        <p>Não existem usuários cadastrados.</p>
    <?php elseif (count($exemplares) === 0): ?>
        <p>Não existem exemplares disponíveis para empréstimo.</p>
    <?php else: ?>

        <form method="POST">
            <label for="id_usuario">Usuário:</label><br>
            <select name="id_usuario" id="id_usuario" required>
                <option value="">Selecione um usuário</option>

                <?php foreach ($usuarios as $usuario): ?>
                    <option
                        value="<?= (int) $usuario['id_usuario'] ?>"
                        <?= (string) ($_POST['id_usuario'] ?? '') === (string) $usuario['id_usuario'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?>
                        — CPF:
                        <?= htmlspecialchars($usuario['cpf'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <br><br>

            <label for="id_exemplar">Livro / exemplar:</label><br>
            <select name="id_exemplar" id="id_exemplar" required>
                <option value="">Selecione um exemplar</option>

                <?php foreach ($exemplares as $exemplar): ?>
                    <option
                        value="<?= (int) $exemplar['id_exemplar'] ?>"
                        <?= (string) ($_POST['id_exemplar'] ?? '') === (string) $exemplar['id_exemplar'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($exemplar['titulo'], ENT_QUOTES, 'UTF-8') ?>
                        — <?= htmlspecialchars($exemplar['codigo_patrimonio'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <br><br>

            <label for="data_prevista_devolucao">
                Data prevista para devolução:
            </label><br>
            <input
                type="date"
                name="data_prevista_devolucao"
                id="data_prevista_devolucao"
                min="<?= date('Y-m-d') ?>"
                value="<?= htmlspecialchars($_POST['data_prevista_devolucao'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <br><br>

            <button type="submit">Registrar empréstimo</button>
        </form>

    <?php endif; ?>

    <p><a href="../painel/servidor.php">Voltar ao painel</a></p>

</body>
</html>
```
