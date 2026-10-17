
<?php
session_start();

require_once __DIR__ . '/../includes/conexao.php';

// Aceita somente requisições POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: servidor.php');
    exit;
}

// Recebe e trata os dados do formulário.
$cpf = preg_replace('/\D/', '', trim($_POST['cpf'] ?? ''));
$senha = $_POST['senha'] ?? '';

// Verifica os campos.
if ($cpf === '' || $senha === '') {
    header('Location: servidor.php?erro=vazio');
    exit;
}

try {
    // Busca o funcionário ativo pelo CPF.
    $sql = "SELECT
                id_funcionario,
                nome,
                cpf,
                senha,
                cargo
            FROM funcionarios
            WHERE cpf = ?
              AND ativo = 1
            LIMIT 1";

    $stmt = $conexao->prepare($sql);
    $stmt->execute([$cpf]);

    $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Confere se o funcionário existe e se a senha está correta.
    if (
        !$funcionario ||
        !password_verify($senha, $funcionario['senha'])
    ) {
        header('Location: servidor.php?erro=1');
        exit;
    }

    // Renova o identificador da sessão.
    session_regenerate_id(true);

    // Salva os dados do funcionário na sessão.
    $_SESSION['id_funcionario'] =
        (int) $funcionario['id_funcionario'];

    $_SESSION['nome_funcionario'] = $funcionario['nome'];
    $_SESSION['cargo'] = $funcionario['cargo'];

    // Redireciona para o painel do servidor.
    header('Location: ../painel/servidor.php');
    exit;

} catch (PDOException $e) {
    // Registra o erro técnico no log do servidor.
    error_log($e->getMessage());

    header('Location: servidor.php?erro=conexao');
    exit;
}