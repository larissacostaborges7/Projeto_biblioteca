<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/conexao.php';

// Se não for POST, volta para o login
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: servidor.php');
    exit;
}

$cpf = trim($_POST['cpf'] ?? '');
$senha = $_POST['senha'] ?? '';

// Remove formatação do CPF para bater com o banco
$cpf = preg_replace('/\D/', '', $cpf);

if ($cpf === '' || $senha === '') {
    header('Location: servidor.php?erro=vazio');
    exit;
}

try {
    $sql = "SELECT id_funcionario, nome, cpf, senha, cargo
            FROM funcionarios
            WHERE cpf = ? AND ativo = TRUE
            LIMIT 1";

    $stmt = $conexao->prepare($sql);
    $stmt->execute([$cpf]);
    $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($funcionario && password_verify($senha, $funcionario['senha'])) {
        session_regenerate_id(true);

        $_SESSION['id_funcionario'] = $funcionario['id_funcionario'];
        $_SESSION['nome_funcionario'] = $funcionario['nome'];
        $_SESSION['cargo'] = $funcionario['cargo'];

        // Redireciona para o painel do servidor com sucesso
        header('Location: ../painel/servidor.php');
        exit;
    }

    // Se a senha estiver errada ou usuário não encontrado
    header('Location: servidor.php?erro=1');
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());
    header('Location: servidor.php?erro=conexao');
    exit;
}