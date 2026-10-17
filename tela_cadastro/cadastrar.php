<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/proteger_acesso.php';
require_once __DIR__ . '/../includes/registrar_log.php';

// Garantir acesso a funcionários/administradores
exigirCargo(['Administrador', 'Atendente', 'Bibliotecário', 'Funcionário']);

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Exemplo de captura e sanitização dos campos
    $nome  = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));

    if (empty($nome) || empty($email)) {
        $erro = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Por favor, insira um e-mail válido.';
    } else {
        try {
            // Exemplo de inserção na base de dados
            $stmt = $conexao->prepare("INSERT INTO usuarios (nome, email) VALUES (?, ?)");
            $stmt->execute([$nome, $email]);
            $novoId = $conexao->lastInsertId();

            // Registo no log de auditoria
            registrarLog($conexao, 'CADASTRO_USUARIO', 'usuarios', $novoId, "Cadastrado usuário '$nome'");

            $sucesso = 'Registo efetuado com sucesso!';
        } catch (PDOException $e) {
            $erro = 'Erro ao salvar o registo: ' . $e->getMessage();
        }
    }
}
?>