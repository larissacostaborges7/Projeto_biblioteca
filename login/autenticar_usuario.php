<?php
session_start();

require_once __DIR__ . '/../includes/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuario.php');
    exit;
}

$cpfBruto = trim($_POST['cpf'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($cpfBruto === '' || $senha === '') {
    header('Location: usuario.php?erro=1');
    exit;
}

// Opcional: Remove pontos e traços do CPF para coincidir com o formato salvo no banco
$cpf = preg_replace('/\D/', '', $cpfBruto);

// Consulta segura ao banco de dados
$sql = "SELECT id_usuario, nome, cpf, senha 
        FROM usuarios 
        WHERE cpf = ? 
        LIMIT 1";

$stmt = $conexao->prepare($sql);
$stmt->execute([$cpf]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Verifica se o usuário existe e se a senha bate
if ($usuario && password_verify($senha, $usuario['senha'])) {
    session_regenerate_id(true);

    $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
    $_SESSION['nome_usuario'] = $usuario['nome'];

    header('Location: ../painel/usuario.php');
    exit;
}

// Se falhar o login, redireciona com erro
header('Location: usuario.php?erro=1');
exit;