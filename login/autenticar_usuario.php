```php
<?php
session_start();

require_once __DIR__ . '/../includes/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuario.php');
    exit;
}

$cpf = trim($_POST['cpf'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($cpf === '' || $senha === '') {
    header('Location: usuario.php?erro=1');
    exit;
}

$sql = "SELECT id_usuario, nome, cpf, senha
        FROM usuarios
        WHERE cpf = ? AND ativo = TRUE
        LIMIT 1";

$stmt = $conexao->prepare($sql);
$stmt->execute([$cpf]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario && password_verify($senha, $usuario['senha'])) {
    session_regenerate_id(true);

    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['nome_usuario'] = $usuario['nome'];

    header('Location: ../painel/usuario.php');
    exit;
}

header('Location: usuario.php?erro=1');
exit;
```
