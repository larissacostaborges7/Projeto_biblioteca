
<?php
session_start();

// Se o funcionário já estiver conectado, vai direto ao painel.
if (isset($_SESSION['id_funcionario'])) {
    header('Location: ../painel/servidor.php');
    exit;
}

$cpf = '';
$erro = '';

if (isset($_GET['erro'])) {
    $erro = 'CPF ou senha inválidos. Confira os dados e tente novamente.';
}

if (isset($_GET['vazio'])) {
    $erro = 'Preencha o CPF e a senha para continuar.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login do Servidor</title>
</head>
<body>

    <h1>Login do Servidor</h1>

    <p>Acesso exclusivo para funcionários da biblioteca.</p>

    <?php if ($erro !== ''): ?>
        <p style="color: red;">
            <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form action="autenticar_servidor.php" method="POST">
        <label for="cpf">CPF:</label><br>
        <input
            type="text"
            id="cpf"
            name="cpf"
            value="<?= htmlspecialchars($cpf, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Digite seu CPF"
            autocomplete="username"
            required
        >

        <br><br>

        <label for="senha">Senha:</label><br>
        <input
            type="password"
            id="senha"
            name="senha"
            placeholder="Digite sua senha"
            autocomplete="current-password"
            required
        >

        <br><br>

        <button type="submit">Entrar</button>
    </form>

    <p>
        <a href="../index.php">Voltar ao início</a>
    </p>
    <p>
    Ainda não tem cadastro?
    <a href="../tela_cadastro/index.php">Criar minha conta</a>
</p>

</body>
</html>