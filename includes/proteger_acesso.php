

<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Verifica se o funcionário está autenticado.
 */
function exigirLogin(): void
{
    if (empty($_SESSION['id_funcionario'])) {
        header('Location: /biblioteca_aruja/login/servidor.php');
        exit;
    }
}

/**
 * Permite o acesso somente aos cargos autorizados.
 */
function exigirCargo(array $cargosPermitidos): void
{
    exigirLogin();

    $cargo = $_SESSION['cargo'] ?? '';

    if (!in_array($cargo, $cargosPermitidos, true)) {
        http_response_code(403);

        exit(
            'Acesso negado. Seu cargo não tem permissão ' .
            'para acessar esta página.'
        );
    }
}