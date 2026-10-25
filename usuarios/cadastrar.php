```php
<?php
require_once __DIR__ . '/../includes/conexao.php';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $nascimento = $_POST['data_nascimento'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmacao = $_POST['confirmacao'] ?? '';

    if (
        $nome === '' || $cpf === '' || $nascimento === '' ||
        $email === '' || $senha === '' || $confirmacao === ''
    ) {
        $mensagem = 'Preencha todos os campos obrigatórios.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'Informe um e-mail válido.';

    } elseif (
        strlen($senha) < 8 ||
        !preg_match('/[A-Z]/', $senha) ||
        !preg_match('/[a-z]/', $senha) ||
        !preg_match('/[0-9]/', $senha) ||
        !preg_match('/[^a-zA-Z0-9]/', $senha)
    ) {
        $mensagem = 'A senha precisa ter 8 caracteres ou mais, '
            . 'com maiúscula, minúscula, número e caractere especial.';

    } elseif ($senha !== $confirmacao) {
        $mensagem = 'As senhas não coincidem.';

    } else {
        try {
            $hash = password_hash($senha, PASSWORD_DEFAULT);

            $sql = "INSERT INTO usuarios
                    (nome, cpf, data_nascimento, email, senha,
                     telefone, endereco)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conexao->prepare($sql);
            $stmt->execute([
                $nome,
                $cpf,
                $nascimento,
                $email,
                $hash,
                $telefone ?: null,
                $endereco ?: null
            ]);

            $mensagem = 'Cadastro realizado com sucesso! Você já pode entrar.';

        } catch (PDOException $erro) {
            if ($erro->getCode() === '23000') {
                $mensagem = 'Este CPF ou e-mail já está cadastrado.';
            } else {
                $mensagem = 'Não foi possível concluir o cadastro.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Leitor | Entre Páginas</title>

    <link rel="stylesheet" href="../assets/css/cadastro.css">
</head>
<body>

    <h1>Cadastro de Leitor</h1>

    <?php if ($mensagem !== ''): ?>
        <p><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post">
        <label for="nome">Nome completo: *</label><br>
        <input type="text" id="nome" name="nome" maxlength="150" required>
        <br><br>

        <label for="cpf">CPF: *</label><br>
        <input type="text" id="cpf" name="cpf" maxlength="14"
               placeholder="000.000.000-00" required>
        <br><br>

        <label for="data_nascimento">Data de nascimento: *</label><br>
        <input type="date" id="data_nascimento" name="data_nascimento" required>
        <br><br>

        <label for="email">E-mail: *</label><br>
        <input type="email" id="email" name="email" maxlength="150" required>
        <br><br>

        <label for="telefone">Telefone:</label><br>
        <input type="tel" id="telefone" name="telefone" maxlength="20">
        <br><br>

        <label for="endereco">Endereço:</label><br>
        <input type="text" id="endereco" name="endereco" maxlength="255">
        <br><br>

        <label for="senha">Senha: *</label><br>
        <input type="password" id="senha" name="senha"
               minlength="8" autocomplete="new-password" required>
        <br><br>

        <label for="confirmacao">Confirme a senha: *</label><br>
        <input type="password" id="confirmacao" name="confirmacao"
               minlength="8" autocomplete="new-password" required>

        <p>A senha deve ter pelo menos 8 caracteres, incluindo
           maiúscula, minúscula, número e caractere especial.</p>

        <button type="submit">Cadastrar</button>
    </form>

    <p><a href="../login/usuario.php">Já tenho cadastro — entrar</a></p>
    <p><a href="../index.php">Voltar ao início</a></p>

    <script src="../assets/js/cadastro.js"></script>
</body>
</html>
```
