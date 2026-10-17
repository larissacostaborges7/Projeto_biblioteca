<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel do Funcionário | Entre Páginas</title>
    <link rel="stylesheet" href="../tela3.css">
</head>
<body style="padding: 40px; text-align: center;">
    <h1>Painel do Servidor: <?= htmlspecialchars($_SESSION['nome'] ?? 'Funcionário') ?></h1>
    <p>Cargo: <strong><?= htmlspecialchars($_SESSION['cargo'] ?? 'Funcionário') ?></strong></p>
    <br>
    <a href="../index.php" class="link-tela" style="font-size: 16px;">Sair / Voltar</a>
</body>
</html>