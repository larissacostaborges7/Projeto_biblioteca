
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/proteger_acesso.php';

exigirCargo(['Administrador', 'Atendente']);

$termo = trim($_GET['termo'] ?? '');

if ($termo === '') {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $conexao->prepare("
        SELECT id_usuario, nome, cpf
        FROM usuarios
        WHERE nome LIKE ? OR cpf LIKE ?
        ORDER BY nome
        LIMIT 15
    ");

    $busca = '%' . $termo . '%';
    $stmt->execute([$busca, $busca]);

    echo json_encode(
        $stmt->fetchAll(PDO::FETCH_ASSOC),
        JSON_UNESCAPED_UNICODE
    );
} catch (Throwable $e) {
    error_log('Erro ao pesquisar leitores: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao pesquisar leitores.']);
}
