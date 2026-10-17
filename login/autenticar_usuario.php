
<?php
session_start();

require_once __DIR__ . '/../includes/conexao.php';

// Aceita somente requisições POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuario.php');
    exit;
}

// Recebe e trata os dados do formulário.
$cpf = preg_replace('/\D/', '', trim($_POST['cpf'] ?? ''));
$senha = $_POST['senha'] ?? '';

// Valida os campos.
if ($cpf === '' || $senha === '') {
    header('Location: usuario.php?erro=vazio');
    exit;
}

try {
    // Busca o leitor pelo CPF.
    $sql = "SELECT id_usuario, nome, cpf, senha
            FROM usuarios
            WHERE cpf = ?
            LIMIT 1";

    $stmt = $conexao->prepare($sql);
    $stmt->execute([$cpf]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verifica o usuário e a senha.
    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        header('Location: usuario.php?erro=1');
        exit;
    }

    // Gera um novo identificador de sessão.
    session_regenerate_id(true);

    // Salva os dados do leitor na sessão.
    $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
    $_SESSION['nome_usuario'] = $usuario['nome'];

    // Redireciona para o painel do leitor.
    header('Location: ../painel/usuario.php');
    exit;

} catch (PDOException $e) {
    // Registra detalhes técnicos apenas no log do servidor.
    error_log($e->getMessage());

    header('Location: usuario.php?erro=conexao');
    exit;
}
