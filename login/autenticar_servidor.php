
<?php
session_start();

require_once __DIR__ . '/../includes/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: servidor.php');
    exit;
}

$cpf = trim($_POST['cpf'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($cpf === '' || $senha === '') {
    header('Location: servidor.php?erro=1');
    exit;
}

$sql = "SELECT id_funcionario, nome, cpf, senha, cargo
        FROM funcionarios
        WHERE cpf = ? AND ativo = TRUE
        LIMIT 1";

$stmt = $conexao->prepare($sql);
$stmt->execute([$cpf]);

$funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

if (
    $funcionario &&
    password_verify($senha, $funcionario['senha'])
) {
    session_regenerate_id(true);

    $_SESSION['id_funcionario'] = $funcionario['id_funcionario'];
    $_SESSION['nome_funcionario'] = $funcionario['nome'];
    $_SESSION['cargo'] = $funcionario['cargo'];

    header('Location: ../painel/servidor.php');
    exit;
}

header('Location: servidor.php?erro=1');
exit;