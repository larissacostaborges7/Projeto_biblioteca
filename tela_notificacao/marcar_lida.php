
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/conexao.php';

// Somente usuários cadastrados podem marcar suas notificações
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../tela_cadastro/index.php');
    exit;
}

// Aceita somente requisições POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$idNotificacao = filter_input(INPUT_POST, 'id_notificacao', FILTER_VALIDATE_INT);

if (!$idNotificacao || $idNotificacao < 1) {
    header('Location: index.php');
    exit;
}

try {
    $stmt = $conexao->prepare("
        UPDATE notificacoes
        SET lida = 1
        WHERE id_notificacao = :id_notificacao
          AND id_usuario = :id_usuario
    ");

    $stmt->execute([
        'id_notificacao' => $idNotificacao,
        'id_usuario' => $_SESSION['id_usuario']
    ]);
} catch (PDOException $e) {
    error_log('Erro ao marcar notificação como lida: ' . $e->getMessage());
}

header('Location: index.php');
exit;
