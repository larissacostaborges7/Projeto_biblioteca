<?php
header('Content-Type: application/json');

try {
    $pdo = new PDO('mysql:host=localhost;dbname=biblioteca;charset=utf8', 'root', '');
    
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $stmt = $pdo->prepare("UPDATE notificacoes SET lida = 1 WHERE id = :id");
        $stmt->execute([':id' => $id]);

        echo json_encode(['sucesso' => true]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['sucesso' => false]);
}

echo json_encode(['sucesso' => false]);
?>