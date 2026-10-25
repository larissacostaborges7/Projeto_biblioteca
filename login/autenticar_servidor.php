
<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../includes/conexao.php';

/**
 * Encaminha o funcionário conforme o cargo cadastrado no banco.
 */
function redirecionarFuncionario(string $cargo): void
{
    switch ($cargo) {
        case 'Acervo':
            header('Location: ../tela_cadastro_acervo/tela_cadastro_acervo.php');
            exit;

        case 'Atendente':
    header('Location: ../painel/servidor.php');
    exit;

        case 'Administrador':
            // Não encaminha para um painel que não está sendo utilizado.
            // Por enquanto, mostra uma mensagem até definirmos a tela adequada.
            http_response_code(403);
            echo 'O acesso do Administrador precisa de uma tela inicial configurada.';
            exit;

        default:
            http_response_code(403);
            echo 'Cargo não reconhecido. Entre em contato com a administração.';
            exit;
    }
}

// Este arquivo aceita somente o envio do formulário por POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../tela_cadastro/index.php');
    exit;
}

$cpf = preg_replace('/\D/', '', (string) ($_POST['cpf'] ?? ''));
$senha = (string) ($_POST['senha'] ?? '');

if ($cpf === '' || $senha === '') {
    header('Location: ../tela_cadastro/index.php?erro=vazio');
    exit;
}

if (strlen($cpf) !== 11) {
    header('Location: ../tela_cadastro/index.php?erro=1');
    exit;
}

try {
    $stmt = $conexao->prepare(
        'SELECT id_funcionario, nome, cpf, senha, cargo, ativo
         FROM funcionarios
         WHERE cpf = :cpf
         LIMIT 1'
    );

    $stmt->execute(['cpf' => $cpf]);
    $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Confere se a conta existe, está ativa e a senha corresponde ao hash.
    if (
        !$funcionario ||
        (int) $funcionario['ativo'] !== 1 ||
        !password_verify($senha, $funcionario['senha'])
    ) {
        header('Location: ../tela_cadastro/index.php?erro=1');
        exit;
    }

    // Evita reutilização do identificador de sessão após o login.
    session_regenerate_id(true);

    $_SESSION['id_funcionario'] = (int) $funcionario['id_funcionario'];
    $_SESSION['nome_funcionario'] = $funcionario['nome'];
    $_SESSION['cargo'] = $funcionario['cargo'];

    redirecionarFuncionario((string) $funcionario['cargo']);

} catch (PDOException $e) {
    error_log('Erro no login do funcionário: ' . $e->getMessage());

    header('Location: ../tela_cadastro/index.php?erro=conexao');
    exit;
}
