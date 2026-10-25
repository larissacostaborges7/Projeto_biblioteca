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
        // Redireciona para a página principal unificada na raiz do projeto
        header('Location: /biblioteca_aruja/index.php');
        exit;
    }
}

/**
 * Permite o acesso somente aos cargos autorizados.
 */

function exigirCargo(array $cargosPermitidos): void
{
    exigirLogin();

    $cargoAtual = trim($_SESSION['cargo'] ?? '');

    $permitidosNormalizados = array_map(
        static fn($cargo) => strtolower(trim($cargo)),
        $cargosPermitidos
    );

    $cargoAtualNormalizado = strtolower($cargoAtual);

    if (!in_array($cargoAtualNormalizado, $permitidosNormalizados, true)) {
        http_response_code(403);

        exit(
            'Acesso negado. O seu cargo (' .
            htmlspecialchars($cargoAtual, ENT_QUOTES, 'UTF-8') .
            ') não tem permissão para acessar esta página.'
        );
    }
}
