<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/proteger_acesso.php';
require_once __DIR__ . '/../includes/registrar_log.php';

// Restringe o acesso apenas para Administradores
exigirCargo(['Administrador']);
// Filtro de busca por texto ou ação
$busca = trim(filter_input(INPUT_GET, 'busca', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

try {
    if (!empty($busca)) {
        $stmtLogs = $conexao->prepare("
            SELECT l.*, f.nome AS nome_funcionario
            FROM logs_auditoria l
            LEFT JOIN funcionarios f ON f.id_funcionario = l.id_funcionario
            WHERE l.acao LIKE ? OR l.descricao LIKE ? OR f.nome LIKE ? OR l.tabela_afetada LIKE ?
            ORDER BY l.criado_em DESC
            LIMIT 100
        ");
        $param = "%$busca%";
        $stmtLogs->execute([$param, $param, $param, $param]);
    } else {
        $stmtLogs = $conexao->query("
            SELECT l.*, f.nome AS nome_funcionario
            FROM logs_auditoria l
            LEFT JOIN funcionarios f ON f.id_funcionario = l.id_funcionario
            ORDER BY l.ORDER BY l.data_hora DESC
LIMIT 100 DESC
            LIMIT 100
        ");
    }
    $listaLogs = $stmtLogs ? $stmtLogs->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable $e) {
    $listaLogs = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Logs - Biblioteca</title>
    <link rel="stylesheet" href="../notificacoes/style.css">
</head>
<body>

    <!-- Sidebar de Navegação -->
    <aside>
        <a href="#" class="brand">
            📚 <span>Biblioteca Municipal</span>
        </a>
        <nav class="nav-menu">
           <a href="../tela_emprestimo/registrar.php" class="nav-link">📋 Registrar empréstimo</a>
            <a href="../tela_emprestimo/devolver.php" class="nav-link">📥 Devoluções</a>
            <a href="../tela_emprestimo/reservas.php" class="nav-link">🔖 Reservas</a>
            <a href="../notificacoes/index.php" class="nav-link">🔔 Notificações</a>
            <a href="../evento/index.php" class="nav-link">🎉 Eventos</a>
            <a href="index.php" class="nav-link active">📜 Logs do Sistema</a>
        </nav>
        <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
            <a href="../painel/servidor.php" class="nav-link">⬅ Voltar ao Painel</a>
        </div>
    </aside>

    <!-- Conteúdo Principal -->
    <main>
        <header>
            <h1>Registros de Atividades (Logs de Auditoria)</h1>
            <div class="user-info">
                Logado como: <strong><?= htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Funcionário', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </header>

        <div class="content-body">

            <!-- Card de Filtro e Busca -->
            <div class="card-container">
                <h2 class="card-title">🔍 Filtrar Registros</h2>
                <form method="GET" style="display: flex; gap: 10px; margin-top: 15px;">
                    <input type="text" name="busca" value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por ação, descrição, tabela ou funcionário..." style="flex: 1; padding: 10px; border-radius: 6px; border: 1px solid var(--cinza-claro);">
                    <button type="submit" class="btn" style="padding: 10px 20px; cursor: pointer; border: none;">Filtrar</button>
                    <?php if (!empty($busca)): ?>
                        <a href="index.php" class="btn" style="background: var(--cinza); padding: 10px 15px; text-decoration: none;">Limpar</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Tabela de Logs -->
            <div class="card-container">
                <h2 class="card-title">📋 Últimas Atividades Registradas (<?= count($listaLogs) ?>)</h2>

                <?php if (empty($listaLogs)): ?>
                    <p style="color: var(--cinza);">Nenhum registro de log encontrado.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data / Hora</th>
                                    <th>Ação</th>
                                    <th>Tabela Afetada</th>
                                    <th>Descrição</th>
                                    <th>Responsável</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listaLogs as $log): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($log['criado_em'] ?? 'now')), ENT_QUOTES, 'UTF-8') ?></strong></td>
                                        <td>
                                            <span class="badge badge-ativo" style="font-size: 0.8rem; padding: 0.3rem 0.6rem;">
                                                <?= htmlspecialchars($log['acao'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td><code><?= htmlspecialchars($log['tabela_afetada'] ?? '-', ENT_QUOTES, 'UTF-8') ?></code></td>
                                        <td><?= htmlspecialchars($log['descricao'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($log['nome_funcionario'] ?? 'Sistema / Automático', ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

</body>
</html>