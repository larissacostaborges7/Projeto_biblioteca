```php
<?php
session_start();

require_once __DIR__ . '/../includes/conexao.php';

header('Content-Type: application/json; charset=utf-8');

function responder(bool $sucesso, string $mensagem): void
{
    echo json_encode([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    responder(false, 'Método não permitido.');
}

$cpfBruto = trim($_POST['cpf'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($cpfBruto === '' || $senha === '') {
    responder(false, 'Preencha o CPF e a senha.');
}

$cpf = preg_replace('/\D/', '', $cpfBruto);

$sql = "SELECT id_usuario, nome, senha
        FROM usuarios
        WHERE cpf = ?
        LIMIT 1";

$stmt = $conexao->prepare($sql);
$stmt->execute([$cpf]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || !password_verify($senha, $usuario['senha'])) {
    responder(false, 'CPF ou senha incorretos.');
}

session_regenerate_id(true);

$_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
$_SESSION['nome_usuario'] = $usuario['nome'];

responder(true, 'Login realizado com sucesso!');
```
