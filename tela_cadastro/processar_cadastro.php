
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/registrar_log.php';

function responder(bool $sucesso, string $mensagem): void
{
    echo json_encode([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, 'Método de requisição inválido.');
}

// Captura e higieniza os campos
$tipo = $_POST['tipo'] ?? 'usuario';
$cargo = trim($_POST['cargo'] ?? '');
$nome = trim($_POST['nome'] ?? '');
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$dataNascimento = $_POST['dataNascimento'] ?? '';
$email = trim($_POST['email'] ?? '');
$celular = preg_replace('/\D/', '', $_POST['celular'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$bairro = trim($_POST['bairro'] ?? '');
$cidade = trim($_POST['cidade'] ?? '');
$senha = $_POST['senha'] ?? '';
$confirmarSenha = $_POST['confirmarSenha'] ?? '';

// Dados do responsável legal
$nomeResponsavel = trim($_POST['nomeResponsavel'] ?? '');
$cpfResponsavel = preg_replace('/\D/', '', $_POST['cpfResponsavel'] ?? '');
$emailResponsavel = trim($_POST['emailResponsavel'] ?? '');
$celularResponsavel = preg_replace(
    '/\D/',
    '',
    $_POST['celularResponsavel'] ?? ''
);

// Verifica o tipo de cadastro
if (!in_array($tipo, ['usuario', 'funcionario'], true)) {
    responder(false, 'Tipo de cadastro inválido.');
}

// Campos obrigatórios
if (
    $nome === '' ||
    $cpf === '' ||
    $dataNascimento === '' ||
    $email === '' ||
    $celular === '' ||
    $endereco === '' ||
    $bairro === '' ||
    $cidade === '' ||
    $senha === '' ||
    $confirmarSenha === ''
) {
    responder(false, 'Preencha todos os campos obrigatórios.');
}

if (strlen($cpf) !== 11) {
    responder(false, 'Informe um CPF válido.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responder(false, 'Informe um e-mail válido.');
}

if (strlen($celular) < 10 || strlen($celular) > 11) {
    responder(false, 'Confira o número do celular.');
}

// Valida a data de nascimento
$dataNasc = DateTime::createFromFormat('!Y-m-d', $dataNascimento);
$errosData = DateTime::getLastErrors();

if (
    !$dataNasc ||
    $dataNasc->format('Y-m-d') !== $dataNascimento ||
    ($errosData !== false &&
        ($errosData['warning_count'] > 0 || $errosData['error_count'] > 0))
) {
    responder(false, 'Informe uma data de nascimento válida.');
}

$hoje = new DateTime('today');

if ($dataNasc > $hoje) {
    responder(false, 'A data de nascimento não pode ser futura.');
}

$idade = $dataNasc->diff($hoje)->y;

// Apenas usuários menores de idade precisam informar responsável
if ($tipo === 'usuario' && $idade < 18) {
    if (
        $nomeResponsavel === '' ||
        strlen($cpfResponsavel) !== 11 ||
        !filter_var($emailResponsavel, FILTER_VALIDATE_EMAIL) ||
        strlen($celularResponsavel) < 10 ||
        strlen($celularResponsavel) > 11
    ) {
        responder(
            false,
            'Confira e preencha todos os dados válidos do responsável legal.'
        );
    }
}

// Requisitos da senha
if (
    strlen($senha) < 8 ||
    !preg_match('/[A-Z]/', $senha) ||
    !preg_match('/[a-z]/', $senha) ||
    !preg_match('/[0-9]/', $senha) ||
    !preg_match('/[^a-zA-Z0-9]/', $senha)
) {
    responder(false, 'A senha não atende a todos os requisitos.');
}

if ($senha !== $confirmarSenha) {
    responder(false, 'As senhas não conferem.');
}

// Somente Administrador autenticado pode cadastrar funcionários
$ehAdminLogado =
    !empty($_SESSION['id_funcionario']) &&
    ($_SESSION['cargo'] ?? '') === 'Administrador' &&
    ($_SESSION['tipo'] ?? '') === 'funcionario';

if ($tipo === 'funcionario' && !$ehAdminLogado) {
    responder(
        false,
        'Apenas o Administrador autenticado pode cadastrar funcionários.'
    );
}

// Confere se o cargo é permitido
$cargosPermitidos = ['Administrador', 'Acervo', 'Atendente'];

if (
    $tipo === 'funcionario' &&
    !in_array($cargo, $cargosPermitidos, true)
) {
    responder(false, 'Selecione um cargo válido.');
}

try {
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    if ($tipo === 'funcionario') {
        $stmt = $conexao->prepare("
            INSERT INTO funcionarios (
                nome, cpf, data_nascimento, email, celular,
                endereco, bairro, cidade, cargo, senha
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $nome,
            $cpf,
            $dataNascimento,
            $email,
            $celular,
            $endereco,
            $bairro,
            $cidade,
            $cargo,
            $senhaHash
        ]);

        $novoId = $conexao->lastInsertId();

        registrarLog(
            $conexao,
            'CADASTRO_FUNCIONARIO',
            'funcionarios',
            $novoId,
            "Novo funcionário '$nome' ($cargo) cadastrado."
        );

        responder(true, 'Funcionário cadastrado com sucesso!');
    }

    $nomeResponsavelSalvar = $idade < 18 ? $nomeResponsavel : null;
    $cpfResponsavelSalvar = $idade < 18 ? $cpfResponsavel : null;
    $emailResponsavelSalvar = $idade < 18 ? $emailResponsavel : null;
    $telefoneResponsavelSalvar = $idade < 18 ? $celularResponsavel : null;

    $stmt = $conexao->prepare("
        INSERT INTO usuarios (
            nome, cpf, data_nascimento, email, celular,
            endereco, bairro, cidade, senha,
            nome_responsavel, cpf_responsavel,
            email_responsavel, telefone_responsavel
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $nome,
        $cpf,
        $dataNascimento,
        $email,
        $celular,
        $endereco,
        $bairro,
        $cidade,
        $senhaHash,
        $nomeResponsavelSalvar,
        $cpfResponsavelSalvar,
        $emailResponsavelSalvar,
        $telefoneResponsavelSalvar
    ]);

    $novoId = $conexao->lastInsertId();

    registrarLog(
        $conexao,
        'CADASTRO_USUARIO',
        'usuarios',
        $novoId,
        "Novo usuário '$nome' cadastrado."
    );

    responder(
        true,
        'Conta criada com sucesso! Faça login para continuar.'
    );

} catch (PDOException $e) {
    if ($e->getCode() == '23000') {
        responder(
            false,
            'Este CPF ou e-mail já se encontra cadastrado.'
        );
    }

    error_log('Erro no cadastro: ' . $e->getMessage());

    responder(
        false,
        'Erro ao salvar o cadastro. Confira os dados e tente novamente.'
    );
}
