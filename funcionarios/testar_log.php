
<?php

require_once __DIR__ . '/../includes/proteger_acesso.php';

exigirCargo(['Administrador']);

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/registrar_log.php';

try {
    registrarLog(
        $conexao,
        'TESTE',
        'logs_auditoria',
        null,
        'Teste de funcionamento do registro de auditoria.'
    );

    echo 'Registro de auditoria criado com sucesso!';
} catch (PDOException $erro) {
    error_log($erro->getMessage());
    http_response_code(500);

    echo 'Não foi possível registrar o teste. Confira o log de erros do PHP.';
}