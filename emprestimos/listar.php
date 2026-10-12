```php
<?php
session_start();

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/proteger_acesso.php';

exigirCargo(['Administrador', 'Atendente']);

$sql = "
    SELECT
        em.id_emprestimo,
        u.nome AS nome_usuario,
        l.titulo,
        ex.codigo_patrimonio,
        f.nome AS nome_funcionario,
        em.data_emprestimo,
        em.data_prevista_devolucao,
        em.data_devolucao,
        em.status
    FROM emprestimos em
    INNER JOIN usuarios u
        ON u.id_usuario = em.id_usuario
    INNER JOIN exemplares ex
        ON ex.id_exemplar = em.id_exemplar
    INNER JOIN livros l
        ON l.id_livro = ex.id_livro
    INNER JOIN funcionarios f
        ON f.id_funcionario = em.id_funcionario
    ORDER BY em.id_emprestimo DESC
";

$stmt = $conexao->query($sql);
$emprestimos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de empréstimos</title>
</head>
<body>

    <h1>Histórico de empréstimos</h1>

    <?php if (empty($emprestimos)): ?>

        <p>Nenhum empréstimo foi registrado ainda.</p>

    <?php else: ?>

        <table border="1" cellpadding="8" cellspacing="0">
            <thead>
                <tr>
                    <th>Nº</th>
                    <th>Leitor</th>
                    <th>Livro</th>
                    <th>Patrimônio</th>
                    <th>Funcionário</th>
                    <th>Data do empréstimo</th>
                    <th>Devolução prevista</th>
                    <th>Data da devolução</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($emprestimos as $item): ?>
                    <tr>
                        <td><?= (int) $item['id_emprestimo'] ?></td>

                        <td><?= htmlspecialchars(
                            $item['nome_usuario'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></td>

                        <td><?= htmlspecialchars(
                            $item['titulo'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></td>

                        <td><?= htmlspecialchars(
                            $item['codigo_patrimonio'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></td>

                        <td><?= htmlspecialchars(
                            $item['nome_funcionario'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></td>

                        <td><?= htmlspecialchars(
                            $item['data_emprestimo'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></td>

                        <td><?= htmlspecialchars(
                            $item['data_prevista_devolucao'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></td>

                        <td>
                            <?= $item['data_devolucao']
                                ? htmlspecialchars(
                                    $item['data_devolucao'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                                : '—' ?>
                        </td>

                        <td><?= htmlspecialchars(
                            ucfirst($item['status']),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

    <p><a href="cadastrar.php">Registrar empréstimo</a></p>
    <p><a href="devolver.php">Registrar devolução</a></p>
    <p><a href="../painel/servidor.php">Voltar ao painel</a></p>

</body>
</html>
```
