```php
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login do Usuário</title>
</head>
<body>

    <h1>Login do Usuário</h1>
    <p>Acesse sua conta da Biblioteca Municipal de Arujá.</p>

    <form action="autenticar_usuario.php" method="post">

        <label for="cpf">CPF:</label>
        <input
            type="text"
            id="cpf"
            name="cpf"
            maxlength="14"
            placeholder="Digite seu CPF"
            required
        >

        <br><br>

        <label for="senha">Senha:</label>
        <input
            type="password"
            id="senha"
            name="senha"
            placeholder="Digite sua senha"
            required
        >

        <br><br>

        <button type="submit">Entrar</button>

    </form>

    <p><a href="../index.php">Voltar ao início</a></p>

</body>
</html>
```
