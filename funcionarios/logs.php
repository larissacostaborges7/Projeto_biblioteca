```php
<?php
session_start();

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/proteger_acesso.php';

exigirCargo(['Administrador']);

$sql = "
    SELECT
        log.id_log,
        log.acao,
        log.tabela_afetada,
        log.id_registro,
        log.descricao,
        log.data_hora,
        f.nome AS nome_funcionario
    FROM logs_auditoria log
    LEFT JOIN funcionarios f
        ON f.id_funcionario = log.id_funcionario
    ORDER BY log.data_hora DESC, log.id_log DESC
";

$stmt = $conexao->query($sql);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de auditoria</title>
</head>
<body>

    <h1>Logs de auditoria</h1>

    <p>Registro das ações realizadas no sistema.</p>

    <?php if (empty($logs)): ?>

        <p>Nenhuma ação registrada até o momento.</p>

    <?php else: ?>

        <table border="1" cellpadding="8" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Funcionário</th>
                    <th>Ação</th>
                    <th>Tabela</th>
                    <th>ID do registro</th>
                    <th>Descrição</th>
                    <th>Data e hora</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= (int) $log['id_log'] ?></td>

                        <td>
                            <?= htmlspecialchars(
                                $log['nome_funcionario'] ?? 'Funcionário não identificado',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $log['acao'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $log['tabela_afetada'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>

                        <td>
                            <?= $log['id_registro'] !== null
                                ? (int) $log['id_registro']
                                : '—' ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $log['descricao'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $log['data_hora'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
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
