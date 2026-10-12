```php
<?php
session_start();

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/proteger_acesso.php';
require_once __DIR__ . '/../includes/registrar_log.php';

exigirCargo(['Administrador', 'Atendente']);

if (empty($_SESSION['token_devolucao'])) {
    $_SESSION['token_devolucao'] = bin2hex(random_bytes(32));
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $idEmprestimo = filter_input(
        INPUT_POST,
        'id_emprestimo',
        FILTER_VALIDATE_INT
    );

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['token_devolucao'], $token)
    ) {
        $erro = 'Solicitação inválida. Atualize a página e tente novamente.';
    } elseif (!$idEmprestimo || $idEmprestimo < 1) {
        $erro = 'Empréstimo inválido.';
    } else {
        try {
            $conexao->beginTransaction();

            // Localiza e bloqueia o empréstimo.
            $stmt = $conexao->prepare(
                "SELECT id_emprestimo, id_exemplar, status
                 FROM emprestimos
                 WHERE id_emprestimo = ?
                 FOR UPDATE"
            );
            $stmt->execute([$idEmprestimo]);
            $emprestimo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$emprestimo) {
                throw new RuntimeException('Empréstimo não encontrado.');
            }

            if ($emprestimo['status'] !== 'ativo') {
                throw new RuntimeException(
                    'Este empréstimo não está ativo ou já foi devolvido.'
                );
            }

            // Bloqueia e verifica o exemplar relacionado.
            $stmtExemplar = $conexao->prepare(
                "SELECT id_exemplar, status
                 FROM exemplares
                 WHERE id_exemplar = ?
                 FOR UPDATE"
            );
            $stmtExemplar->execute([
                $emprestimo['id_exemplar']
            ]);
            $exemplar = $stmtExemplar->fetch(PDO::FETCH_ASSOC);

            if (!$exemplar || $exemplar['status'] !== 'emprestado') {
                throw new RuntimeException(
                    'O exemplar não está marcado como emprestado. Confira o acervo antes de continuar.'
                );
            }

            // Registra a data e conclui o empréstimo.
            $stmtAtualizar = $conexao->prepare(
                "UPDATE emprestimos
                 SET data_devolucao = CURDATE(),
                     status = 'devolvido'
                 WHERE id_emprestimo = ?
                   AND status = 'ativo'"
            );
            $stmtAtualizar->execute([$idEmprestimo]);

            if ($stmtAtualizar->rowCount() !== 1) {
                throw new RuntimeException(
                    'Não foi possível concluir a devolução.'
                );
            }

            // Libera o exemplar novamente.
            $stmtLiberar = $conexao->prepare(
                "UPDATE exemplares
                 SET status = 'disponivel'
                 WHERE id_exemplar = ?
                   AND status = 'emprestado'"
            );
            $stmtLiberar->execute([
                $emprestimo['id_exemplar']
            ]);

            if ($stmtLiberar->rowCount() !== 1) {
                throw new RuntimeException(
                    'Não foi possível liberar o exemplar.'
                );
            }

            // Registra a operação no log de auditoria.
            registrarLog(
                $conexao,
                'DEVOLUCAO',
                'emprestimos',
                (int) $idEmprestimo,
                'Devolução registrada pelo funcionário logado.'
            );

            $conexao->commit();

            // Impede o reenvio do mesmo formulário ao atualizar.
            $_SESSION['token_devolucao'] = bin2hex(random_bytes(32));

            header('Location: devolver.php?sucesso=1');
            exit;

        } catch (Throwable $e) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }

            if ($e instanceof RuntimeException) {
                $erro = $e->getMessage();
            } else {
                error_log($e->getMessage());
                $erro = 'Não foi possível registrar a devolução. Tente novamente.';
            }
        }
    }
}

if (isset($_GET['sucesso'])) {
    $sucesso = 'Devolução registrada com sucesso!';
}

// Lista empréstimos ativos com os dados do leitor e do livro.
$sql = "
    SELECT
        em.id_emprestimo,
        u.nome AS nome_usuario,
        l.titulo,
        ex.codigo_patrimonio,
        em.data_emprestimo,
        em.data_prevista_devolucao
    FROM emprestimos em
    INNER JOIN usuarios u
        ON u.id_usuario = em.id_usuario
    INNER JOIN exemplares ex
        ON ex.id_exemplar = em.id_exemplar
    INNER JOIN livros l
        ON l.id_livro = ex.id_livro
    WHERE em.status = 'ativo'
    ORDER BY em.data_prevista_devolucao, l.titulo
";

$stmt = $conexao->query($sql);
$emprestimos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devolução de livros</title>
</head>
<body>

    <h1>Devolução de livros</h1>

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

    <?php if (empty($emprestimos)): ?>
        <p>Não existem empréstimos ativos no momento.</p>
    <?php else: ?>

        <table border="1" cellpadding="8" cellspacing="0">
            <thead>
                <tr>
                    <th>Empréstimo</th>
                    <th>Leitor</th>
                    <th>Livro</th>
                    <th>Patrimônio</th>
                    <th>Data do empréstimo</th>
                    <th>Devolução prevista</th>
                    <th>Ação</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($emprestimos as $item): ?>
                    <tr>
                        <td><?= (int) $item['id_emprestimo'] ?></td>

                        <td>
                            <?= htmlspecialchars(
                                $item['nome_usuario'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $item['titulo'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $item['codigo_patrimonio'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $item['data_emprestimo'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $item['data_prevista_devolucao'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>

                        <td>
                            <form method="POST"
                                  onsubmit="return confirm('Confirmar a devolução deste livro?');">

                                <input
                                    type="hidden"
                                    name="token"
                                    value="<?= htmlspecialchars(
                                        $_SESSION['token_devolucao'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="id_emprestimo"
                                    value="<?= (int) $item['id_emprestimo'] ?>"
                                >

                                <button type="submit">
                                    Registrar devolução
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

    <p>
        <a href="../painel/servidor.php">Voltar ao painel</a>
    </p>

</body>
</html>
```
