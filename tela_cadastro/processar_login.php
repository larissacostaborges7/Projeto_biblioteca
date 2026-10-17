<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Ajuste do caminho correto para a pasta includes
require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/registrar_log.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método de requisição inválido.']);
    exit;
}

$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$senha = $_POST['senha'] ?? '';

if (empty($cpf) || empty($senha)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Por favor, informe o CPF e a senha.']);
    exit;
}

try {
    // 1. Procura na tabela de funcionários
    $stmt = $conexao->prepare("SELECT * FROM funcionarios WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = ? LIMIT 1");
    $stmt->execute([$cpf]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    $tipoUsuario = 'funcionario';

    // 2. Se não achar, procura na tabela de leitores/usuários comuns
    if (!$usuario) {
        $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = ? LIMIT 1");
        $stmt->execute([$cpf]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        $tipoUsuario = 'usuario';
    }

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // Registra os dados do usuário na Sessão
        $_SESSION['id_usuario'] = $usuario['id_funcionario'] ?? $usuario['id_usuario'];
        $_SESSION['id_funcionario'] = $usuario['id_funcionario'] ?? null;
        $_SESSION['nome_funcionario'] = $usuario['nome'];
        $_SESSION['nome'] = $usuario['nome'];
        $_SESSION['tipo'] = $tipoUsuario;
        $_SESSION['cargo'] = $usuario['cargo'] ?? 'Leitor';

        // Registra o log de acesso
        registrarLog(
            $conexao,
            'LOGIN',
            $tipoUsuario === 'funcionario' ? 'funcionarios' : 'usuarios',
            $_SESSION['id_usuario'],
            "Usuário '{$usuario['nome']}' efetuou login."
        );

        // Se estiveres a usar caminhos relativos a partir de processar_login.php:
$redirecionarPara = ($tipoUsuario === 'funcionario') ? '../painel/servidor.php' : '../painel/usuario.php';

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Login realizado com sucesso!',
            'redirecionar' => $redirecionarPara
        ]);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'CPF ou senha incorretos.']);
    }
} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno de servidor.']);
}