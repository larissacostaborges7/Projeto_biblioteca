<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Ajuste do caminho correto para a pasta includes
require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/registrar_log.php';
// Verifica se existe um Administrador autenticado na sessão
$ehAdminLogado = isset($_SESSION['cargo']) && strtolower($_SESSION['cargo']) === 'administrador';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método de requisição inválido.']);
    exit;
}

// Captura e higieniza os campos enviados
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

// Dados do responsável legal (se menor de idade)
$nomeResponsavel = trim($_POST['nomeResponsavel'] ?? '');
$cpfResponsavel = preg_replace('/\D/', '', $_POST['cpfResponsavel'] ?? '');
$emailResponsavel = trim($_POST['emailResponsavel'] ?? '');
$celularResponsavel = preg_replace('/\D/', '', $_POST['celularResponsavel'] ?? '');

// Validações de segurança
if (empty($nome) || empty($cpf) || empty($email) || empty($senha)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Por favor, preencha todos os campos obrigatórios.']);
    exit;
}

if ($senha !== $confirmarSenha) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'As senhas não conferem.']);
    exit;
}

// RESTRIÇÃO CRÍTICA: Somente o Administrador logado pode cadastrar novo funcionário
if ($tipo === 'funcionario' && !$ehAdminLogado) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Apenas o Administrador pode cadastrar novos funcionários.']);
    exit;
}

try {
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    if ($tipo === 'funcionario') {
        $stmt = $conexao->prepare("
            INSERT INTO funcionarios (nome, cpf, data_nascimento, email, celular, endereco, bairro, cidade, cargo, senha)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nome, $cpf, $dataNascimento, $email, $celular, $endereco, $bairro, $cidade, $cargo, $senhaHash]);
        $novoId = $conexao->lastInsertId();

        registrarLog($conexao, 'CADASTRO_FUNCIONARIO', 'funcionarios', $novoId, "Novo funcionário '$nome' ($cargo) cadastrado.");

        echo json_encode(['sucesso' => true, 'mensagem' => 'Funcionário cadastrado com sucesso!']);
    } else {
        $stmt = $conexao->prepare("
            INSERT INTO usuarios (nome, cpf, data_nascimento, email, celular, endereco, bairro, cidade, senha, nome_responsavel, cpf_responsavel, email_responsavel, celular_responsavel)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $nome, $cpf, $dataNascimento, $email, $celular, $endereco, $bairro, $cidade, $senhaHash,
            $nomeResponsavel, $cpfResponsavel, $emailResponsavel, $celularResponsavel
        ]);
        $novoId = $conexao->lastInsertId();

        registrarLog($conexao, 'CADASTRO_USUARIO', 'usuarios', $novoId, "Novo usuário '$nome' cadastrado.");

        echo json_encode(['sucesso' => true, 'mensagem' => 'Conta criada com sucesso! Faça login para continuar.']);
    }
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Este CPF ou E-mail já se encontra cadastrado.']);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar registro no banco de dados.']);
    }
}