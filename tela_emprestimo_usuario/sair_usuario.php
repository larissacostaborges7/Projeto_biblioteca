<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpa todas as variáveis de sessão
$_SESSION = [];

// Destrói a sessão no servidor
session_destroy();

// Redireciona para a página principal correta (ajustado para ../tela_principal2/index.php ou conforme o teu projeto)
header('Location: ../tela_principal2/index.php');
exit;