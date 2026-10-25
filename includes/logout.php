
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Limpa todos os dados da sessão
$_SESSION = [];

// Encerra a sessão atual
if (ini_get('session.use_cookies')) {
    $parametros = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $parametros['path'],
        $parametros['domain'],
        $parametros['secure'],
        $parametros['httponly']
    );
}

session_destroy();

// Volta para a tela de login
header('Location: ../tela_cadastro/index.php');