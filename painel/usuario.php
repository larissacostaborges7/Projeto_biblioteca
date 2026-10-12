```php
<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login/usuario.php');
    exit;
}

$nome = $_SESSION['nome_usuario'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Leitor</title>
</head>
<body>

    <h1>Área do Leitor</h1>

    <p>
        Olá,
        <?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>!
    </p>

    <h2>O que você deseja fazer?</h2>

    <ul>
        <li><a href="../livros/listar.php">Consultar livros do acervo</a></li>
        <li>Consultar meus empréstimos (em desenvolvimento)</li>
        <li>Consultar minhas reservas (em desenvolvimento)</li>
        <li>Consultar minhas notificações (em desenvolvimento)</li>
    </ul>

    <p><a href="../login/sair_usuario.php">Sair da conta</a></p>

</body>
</html>
```
