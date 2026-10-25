
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/registrar_log.php';

function responderLogin(array $dados): void
{
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    responderLogin([
        'sucesso' => false,
        'mensagem' => 'Método de requisição inválido.'
    ]);
}

$cpf = preg_replace('/\D/', '', (string) ($_POST['cpf'] ?? ''));
$senha = (string) ($_POST['senha'] ?? '');

if ($cpf === '' || $senha === '') {
    responderLogin([
        'sucesso' => false,
        'mensagem' => 'Por favor, informe o CPF e a senha.'
    ]);
}

if (strlen($cpf) !== 11) {
    responderLogin([
        'sucesso' => false,
        'mensagem' => 'CPF ou senha incorretos.'
    ]);
}

try {
    // Primeiro verifica se o CPF pertence a um funcionário.
    $stmt = $conexao->prepare(
        "SELECT id_funcionario, nome, cpf, senha, cargo, ativo
         FROM funcionarios
         WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?
         LIMIT 1"
    );

    $stmt->execute([$cpf]);
    $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($funcionario) {
        // Funcionários inativos não podem entrar.
        if (
            (int) $funcionario['ativo'] !== 1 ||
            !password_verify($senha, $funcionario['senha'])
        ) {
            responderLogin([
                'sucesso' => false,
                'mensagem' => 'CPF ou senha incorretos, ou conta inativa.'
            ]);
        }

        $cargo = (string)$funcionario['cargo'];

$destino = '../painel/servidor.php';
        session_regenerate_id(true);

        // Remove dados antigos de uma eventual sessão de leitor.
        unset(
            $_SESSION['id_usuario'],
            $_SESSION['nome'],
            $_SESSION['tipo']
        );

        $_SESSION['id_funcionario'] = (int) $funcionario['id_funcionario'];
        $_SESSION['nome_funcionario'] = $funcionario['nome'];
        $_SESSION['cargo'] = $cargo;
        $_SESSION['tipo'] = 'funcionario';

        registrarLog(
            $conexao,
            'LOGIN',
            'funcionarios',
            (int) $funcionario['id_funcionario'],
            "Funcionário '{$funcionario['nome']}' efetuou login."
        );

        responderLogin([
            'sucesso' => true,
            'mensagem' => 'Login realizado com sucesso!',
            'redirecionar' => $destino
        ]);
    }

    // Se não for funcionário, verifica a conta de leitor.
    $stmt = $conexao->prepare(
        "SELECT id_usuario, nome, cpf, senha
         FROM usuarios
         WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?
         LIMIT 1"
    );

    $stmt->execute([$cpf]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        responderLogin([
            'sucesso' => false,
            'mensagem' => 'CPF ou senha incorretos.'
        ]);
    }

    session_regenerate_id(true);

    // Remove os dados de funcionário antes de iniciar a sessão de leitor.
    unset(
        $_SESSION['id_funcionario'],
        $_SESSION['nome_funcionario'],
        $_SESSION['cargo']
    );

    $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
    $_SESSION['nome'] = $usuario['nome'];
    $_SESSION['tipo'] = 'usuario';

    registrarLog(
        $conexao,
        'LOGIN',
        'usuarios',
        (int) $usuario['id_usuario'],
        "Leitor '{$usuario['nome']}' efetuou login."
    );

    responderLogin([
        'sucesso' => true,
        'mensagem' => 'Login realizado com sucesso!',
        'redirecionar' => '../tela_principal2/index.php'
    ]);

} catch (Throwable $e) {
    error_log('Erro no processamento do login: ' . $e->getMessage());

    http_response_code(500);

    responderLogin([
        'sucesso' => false,
        'mensagem' => 'Não foi possível realizar o login agora. Tente novamente.'
    ]);
}
