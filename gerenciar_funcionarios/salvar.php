
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Retorna ao formulário exibindo uma mensagem de erro.
 */
function voltarComErro(string $mensagem): void
{
    $_SESSION['erro_cadastro_funcionario'] = $mensagem;

    header('Location: index.php');
    exit;
}

/**
 * Valida os dígitos verificadores de um CPF.
 */
function cpfValido(string $cpf): bool
{
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    for ($posicao = 9; $posicao <= 10; $posicao++) {
        $soma = 0;

        for ($i = 0; $i < $posicao; $i++) {
            $soma += (int) $cpf[$i] * (($posicao + 1) - $i);
        }

        $resto = ($soma * 10) % 11;
        $digito = ($resto === 10) ? 0 : $resto;

        if ($digito !== (int) $cpf[$posicao]) {
            return false;
        }
    }

    return true;
}

// 1. Verifica o login.
if (!isset($_SESSION['id_funcionario'])) {
    header('Location: ../tela_cadastro/index.php');
    exit;
}

// 2. Somente o Administrador pode cadastrar funcionários.
if (($_SESSION['cargo'] ?? '') !== 'Administrador') {
    http_response_code(403);
    exit('Acesso negado.');
}

// 3. Aceita somente envios pelo formulário.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 4. Confere o token de segurança.
$tokenFormulario = $_POST['csrf_token'] ?? '';
$tokenSessao = $_SESSION['csrf_funcionarios'] ?? '';

if (
    !is_string($tokenFormulario) ||
    !is_string($tokenSessao) ||
    $tokenFormulario === '' ||
    $tokenSessao === '' ||
    !hash_equals($tokenSessao, $tokenFormulario)
) {
    voltarComErro(
        'O formulário expirou ou é inválido. Tente novamente.'
    );
}

// 5. Confere os dados recebidos.
$camposTexto = [
    'nome',
    'cpf',
    'data_nascimento',
    'email',
    'telefone',
    'endereco',
    'cep',
    'numero',
    'bairro',
    'cidade',
    'cargo'
];

foreach ($camposTexto as $campo) {
    if (
        isset($_POST[$campo]) &&
        !is_string($_POST[$campo])
    ) {
        voltarComErro('Um dos campos enviados é inválido.');
    }
}

$nome = trim($_POST['nome'] ?? '');
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$dataNascimento = trim($_POST['data_nascimento'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$cep = trim($_POST['cep'] ?? '');
$numero = trim($_POST['numero'] ?? '');
$bairro = trim($_POST['bairro'] ?? '');
$cidade = trim($_POST['cidade'] ?? '');
$cargo = trim($_POST['cargo'] ?? '');

$senha = $_POST['senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';

if (!is_string($senha) || !is_string($confirmarSenha)) {
    voltarComErro('Os campos de senha são inválidos.');
}

// 6. Valida os campos obrigatórios.
if (
    $nome === '' ||
    $cpf === '' ||
    $cargo === '' ||
    $senha === '' ||
    $confirmarSenha === ''
) {
    voltarComErro('Preencha todos os campos obrigatórios.');
}

if (strlen($nome) > 150) {
    voltarComErro('O nome deve ter no máximo 150 caracteres.');
}

// 7. Valida o CPF.
if (!cpfValido($cpf)) {
    voltarComErro('Informe um CPF válido.');
}

$cpf = substr($cpf, 0, 3) . '.' .
       substr($cpf, 3, 3) . '.' .
       substr($cpf, 6, 3) . '-' .
       substr($cpf, 9, 2);

// 8. Confere os cargos permitidos.
$cargosPermitidos = [
    'Administrador',
    'Acervo',
    'Atendente'
];

if (!in_array($cargo, $cargosPermitidos, true)) {
    voltarComErro('Selecione um cargo válido.');
}

// 9. Valida o e-mail.
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    voltarComErro('Informe um e-mail válido.');
}

if (strlen($email) > 150) {
    voltarComErro('O e-mail deve ter no máximo 150 caracteres.');
}

// 10. Valida a data de nascimento, se informada.
if ($dataNascimento !== '') {
    $data = DateTime::createFromFormat('!Y-m-d', $dataNascimento);
    $errosData = DateTime::getLastErrors();

    if (
        !$data ||
        $data->format('Y-m-d') !== $dataNascimento ||
        ($errosData !== false &&
            ($errosData['warning_count'] > 0 ||
             $errosData['error_count'] > 0))
    ) {
        voltarComErro('Informe uma data de nascimento válida.');
    }

    if ($data > new DateTime('today')) {
        voltarComErro('A data de nascimento não pode ser futura.');
    }
}

// 11. Confere os requisitos da senha.
if (strlen($senha) < 8) {
    voltarComErro('A senha deve ter pelo menos 8 caracteres.');
}

if (!preg_match('/[A-Z]/', $senha)) {
    voltarComErro('A senha precisa conter uma letra maiúscula.');
}

if (!preg_match('/[a-z]/', $senha)) {
    voltarComErro('A senha precisa conter uma letra minúscula.');
}

if (!preg_match('/[0-9]/', $senha)) {
    voltarComErro('A senha precisa conter um número.');
}

if (!preg_match('/[^a-zA-Z0-9]/', $senha)) {
    voltarComErro('A senha precisa conter um caractere especial.');
}

if ($senha !== $confirmarSenha) {
    voltarComErro('As senhas não coincidem.');
}

// Evita senhas excessivamente longas.
if (strlen($senha) > 1024) {
    voltarComErro('A senha é muito longa.');
}

// 12. Transforma campos opcionais vazios em NULL.
$dataNascimento = $dataNascimento !== '' ? $dataNascimento : null;
$email = $email !== '' ? $email : null;
$telefone = $telefone !== '' ? $telefone : null;
$endereco = $endereco !== '' ? $endereco : null;
$cep = $cep !== '' ? $cep : null;
$numero = $numero !== '' ? $numero : null;
$bairro = $bairro !== '' ? $bairro : null;
$cidade = $cidade !== '' ? $cidade : null;

// 13. Cadastra no banco de dados.
try {
    require_once __DIR__ . '/../includes/conexao.php';

    // Verifica CPF duplicado.
    $verificar = $conexao->prepare(
        'SELECT id_funcionario
         FROM funcionarios
         WHERE cpf = :cpf
         LIMIT 1'
    );

    $verificar->execute(['cpf' => $cpf]);

    if ($verificar->fetch()) {
        voltarComErro(
            'Já existe um funcionário cadastrado com esse CPF.'
        );
    }

    // Verifica e-mail duplicado, se informado.
    if ($email !== null) {
        $verificarEmail = $conexao->prepare(
            'SELECT id_funcionario
             FROM funcionarios
             WHERE email = :email
             LIMIT 1'
        );

        $verificarEmail->execute(['email' => $email]);

        if ($verificarEmail->fetch()) {
            voltarComErro(
                'Já existe um funcionário cadastrado com esse e-mail.'
            );
        }
    }

    // Armazena somente o hash da senha.
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    if ($senhaHash === false) {
        throw new RuntimeException('Não foi possível proteger a senha.');
    }

    $sql = 'INSERT INTO funcionarios (
                nome,
                cpf,
                data_nascimento,
                email,
                telefone,
                endereco,
                cep,
                numero,
                bairro,
                cidade,
                senha,
                cargo
            ) VALUES (
                :nome,
                :cpf,
                :data_nascimento,
                :email,
                :telefone,
                :endereco,
                :cep,
                :numero,
                :bairro,
                :cidade,
                :senha,
                :cargo
            )';

    $stmt = $conexao->prepare($sql);

    $stmt->execute([
        'nome' => $nome,
        'cpf' => $cpf,
        'data_nascimento' => $dataNascimento,
        'email' => $email,
        'telefone' => $telefone,
        'endereco' => $endereco,
        'cep' => $cep,
        'numero' => $numero,
        'bairro' => $bairro,
        'cidade' => $cidade,
        'senha' => $senhaHash,
        'cargo' => $cargo
    ]);

    // Renova o token após o cadastro.
    $_SESSION['csrf_funcionarios'] = bin2hex(random_bytes(32));

    header('Location: index.php?status=sucesso');
    exit;

} catch (PDOException $erro) {
    // SQLSTATE 23000 costuma indicar violação de chave única.
    if ($erro->getCode() === '23000') {
        voltarComErro(
            'Não foi possível cadastrar. Verifique se o CPF ou e-mail já está em uso.'
        );
    }

    error_log(
        'Erro ao cadastrar funcionário: ' . $erro->getMessage()
    );

    voltarComErro(
        'Ocorreu um erro ao salvar o cadastro. Tente novamente.'
    );

} catch (Throwable $erro) {
    error_log(
        'Erro inesperado no cadastro de funcionário: ' .
        $erro->getMessage()
    );

    voltarComErro(
        'Ocorreu um erro inesperado. Tente novamente.'
    );
}
