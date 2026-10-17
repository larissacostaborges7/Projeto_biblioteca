<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Se não estiver logado, manda de volta para o login
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel do Usuário | Entre Páginas</title>
    <link rel="stylesheet" href="../tela3.css">
</head>
<body style="padding: 40px; text-align: center;">
    <h1>Bem-vindo, <?= htmlspecialchars($_SESSION['nome'] ?? 'Usuário') ?>!</h1>
    <p>Você fez login com sucesso no sistema da biblioteca.</p>
    <br>
    <a href="../index.php" class="link-tela" style="font-size: 16px;">Sair / Voltar</a>
</body>
</html>