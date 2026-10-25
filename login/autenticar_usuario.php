<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($cpf) || empty($senha)) {
        header('Location: ../index.php?erro=vazio');
        exit;
    }

    $stmt = $conexao->prepare("SELECT * FROM funcionarios WHERE cpf = ?");
    $stmt->execute([$cpf]);
    $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($funcionario && password_verify($senha, $funcionario['senha'])) {
        $_SESSION['id_funcionario'] = (int) $funcionario['id_funcionario'];
        $_SESSION['nome_funcionario'] = $funcionario['nome'];
        $_SESSION['cargo'] = $funcionario['cargo'];

        header('Location: ../tela_cadastro_acervo/tela_cadastro_acervo.php');
        exit;
    } else {
        header('Location: ../index.php?erro=1');
        exit;
    }
}