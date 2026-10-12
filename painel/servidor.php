```php
<?php
session_start();

if (!isset($_SESSION['id_funcionario'])) {
    header('Location: ../login/servidor.php');
    exit;
}

$nome = $_SESSION['nome_funcionario'];
$cargo = $_SESSION['cargo'];

$acessoAcervo = in_array(
    $cargo,
    ['Administrador', 'Acervo'],
    true
);

$acessoCirculacao = in_array(
    $cargo,
    ['Administrador', 'Atendente'],
    true
);

$acessoAdministrativo = $cargo === 'Administrador';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Servidor</title>
</head>
<body>

    <h1>Painel do Servidor</h1>

    <p>
        Bem-vindo(a),
        <?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>!
    </p>

    <p>
        Cargo:
        <?= htmlspecialchars($cargo, ENT_QUOTES, 'UTF-8') ?>
    </p>

    <h2>Menu</h2>

    <?php if ($acessoAcervo): ?>
        <h3>Acervo</h3>
        <p>
            <a href="../livros/listar.php">Consultar livros</a>
        </p>
    <?php endif; ?>

    <?php if ($acessoCirculacao): ?>
        <h3>Circulação</h3>

        <p>
            <a href="../emprestimos/cadastrar.php">
                Registrar empréstimo
            </a>
        </p>

        <p>
            <a href="../emprestimos/devolver.php">
                Registrar devolução
            </a>
        </p>

        <p>
            <a href="../emprestimos/listar.php">
                Histórico de empréstimos
            </a>
        </p>
    <?php endif; ?>

    <?php if ($acessoAdministrativo): ?>
        <h3>Área administrativa</h3>

        <p>
            <a href="../funcionarios/listar.php">
                Consultar funcionários
            </a>
        </p>

        <p>
            <a href="../funcionarios/cadastrar.php">
                Cadastrar funcionário
            </a>
        </p>
    <?php endif; ?>

<p>
    <a href="../funcionarios/logs.php">
        Consultar logs de auditoria
    </a>
</p>


    <p><a href="../login/sair.php">Sair do sistema</a></p>

</body>
</html>
```
