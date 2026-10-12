
<?php

require_once __DIR__ . '/../includes/proteger_acesso.php';

exigirCargo(['Administrador']);

require_once __DIR__ . '/../includes/conexao.php';

$sql = "
    SELECT
        id_funcionario,
        nome,
        cpf,
        email,
        cargo,
        ativo
    FROM funcionarios
    ORDER BY nome
";

$consulta = $conexao->query($sql);
$funcionarios = $consulta->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funcionários cadastrados</title>
</head>
<body>

    <h1>Funcionários da Biblioteca Municipal de Arujá</h1>

    <p>
        <a href="../painel/servidor.php">Voltar ao painel</a>
    </p>

    <?php if (count($funcionarios) > 0): ?>

        <table border="1" cellpadding="8">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>E-mail</th>
                    <th>Cargo</th>
                    <th>Situação</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($funcionarios as $funcionario): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($funcionario['nome'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($funcionario['cpf'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($funcionario['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($funcionario['cargo'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= $funcionario['ativo'] ? 'Ativo' : 'Inativo' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php else: ?>
        <p>Nenhum funcionário cadastrado.</p>
    <?php endif; ?>

</body>
</html>