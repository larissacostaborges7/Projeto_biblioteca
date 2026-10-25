
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/conexao.php';

// Verifica se existe um usuário ou funcionário conectado
$usuarioLogado = isset($_SESSION['id_usuario']);
$funcionarioLogado = isset($_SESSION['id_funcionario']);
$notificacoes = [];
$totalNaoLidas = 0;

if ($usuarioLogado) {
    try {
        $stmt = $conexao->prepare("
            SELECT id_notificacao, mensagem, tipo, data_envio, lida, id_livro
            FROM notificacoes
            WHERE id_usuario = :id_usuario
            ORDER BY data_envio DESC, id_notificacao DESC
        ");

        $stmt->execute([
            'id_usuario' => $_SESSION['id_usuario']
        ]);

        $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($notificacoes as $notificacao) {
            if ((int) $notificacao['lida'] === 0) {
                $totalNaoLidas++;
            }
        }
    } catch (PDOException $e) {
        error_log('Erro ao carregar notificações: ' . $e->getMessage());
    }
}

if (!$usuarioLogado && !$funcionarioLogado) {
    header('Location: ../tela_cadastro/index.php');
    exit;
}

$nomeLogado = $usuarioLogado
    ? ($_SESSION['nome_usuario'] ?? 'Usuário')
    : ($_SESSION['nome_funcionario'] ?? 'Funcionário');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações | Biblioteca Municipal de Arujá</title>
    <link rel="stylesheet" href="style.css?v=3">
</head>
<body>
    <header>
        <a href="../tela_principal2/index.php">← Voltar ao início</a>
        <h1>Notificações</h1>
        <p>Biblioteca Municipal de Arujá — Professora Alda Martins Soncini</p>
    </header>

    <main>
        <h2>Olá, <?= htmlspecialchars($nomeLogado, ENT_QUOTES, 'UTF-8') ?>!</h2>

        
<section aria-labelledby="titulo-notificacoes">
    <div class="notificacoes-cabecalho">
        <h2 id="titulo-notificacoes">Suas notificações</h2>

        <?php if ($usuarioLogado): ?>
            <p class="resumo-notificacoes">
                <?= $totalNaoLidas ?>
                <?= $totalNaoLidas === 1 ? 'notificação não lida' : 'notificações não lidas' ?>
            </p>
        <?php endif; ?>
    </div>

    <?php if (!$usuarioLogado): ?>

        <p class="estado-vazio">
            As notificações de funcionários ainda não estão disponíveis.
        </p>

    <?php elseif (empty($notificacoes)): ?>

        <div class="estado-vazio">
            <span class="icone-vazio" aria-hidden="true">🔔</span>
            <h3>Tudo em dia!</h3>
            <p>Você não tem notificações no momento.</p>
        </div>

    <?php else: ?>

        <div class="lista-notificacoes">
            <?php foreach ($notificacoes as $notificacao): ?>
                <article class="cartao-notificacao <?= (int) $notificacao['lida'] === 0 ? 'nao-lida' : 'ja-lida' ?>">
                    <div class="notificacao-icone" aria-hidden="true">🔔</div>

                    <div class="notificacao-conteudo">
                        <p class="notificacao-mensagem">
                            <?= htmlspecialchars($notificacao['mensagem'], ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <p class="notificacao-data">
                            <?= date('d/m/Y \à\s H:i', strtotime($notificacao['data_envio'])) ?>
                        </p>

                        <span class="status-notificacao">
                            <?= (int) $notificacao['lida'] === 0 ? 'Não lida' : 'Lida' ?>
                        </span>
                        <?php if ((int) $notificacao['lida'] === 0): ?>
    <form action="marcar_lida.php" method="POST">
        <input
            type="hidden"
            name="id_notificacao"
            value="<?= (int) $notificacao['id_notificacao'] ?>"
        >

        <button type="submit" class="botao-marcar-lida">
            Marcar como lida
        </button>
    </form>
<?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</section>

    </main>
</body>
</html>
