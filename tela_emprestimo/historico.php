
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/proteger_acesso.php';

exigirCargo(['Administrador', 'Atendente']);

$stmtEmprestimos = $conexao->query("
    SELECT
        em.*,
        u.nome AS nome_usuario,
        l.titulo,
        ex.codigo_patrimonio,
        f.nome AS nome_funcionario
    FROM emprestimos em
    INNER JOIN usuarios u ON u.id_usuario = em.id_usuario
    INNER JOIN exemplares ex ON ex.id_exemplar = em.id_exemplar
    INNER JOIN livros l ON l.id_livro = ex.id_livro
    INNER JOIN funcionarios f ON f.id_funcionario = em.id_funcionario
    ORDER BY em.id_emprestimo DESC
");

$emprestimos = $stmtEmprestimos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Empréstimos | Biblioteca</title>
    <link rel="stylesheet" href="style_historico.css">
</head>
<body>

    <aside>
        <a href="../painel/servidor.php" class="brand">
            📚 <span>Biblioteca Municipal</span>
        </a>

        <nav class="nav-menu">
            <a href="historico.php" class="nav-link active">
                📋 Histórico Geral
            </a>

            <a href="registrar.php" class="nav-link">
                ➕ Novo Empréstimo
            </a>

            <a href="devolver.php" class="nav-link">
                📥 Devoluções
            </a>

            <a href="reservas.php" class="nav-link">
                🔖 Reservas
            </a>

            <a href="../notificacoes/index.php" class="nav-link">
                🔔 Notificações
            </a>

            <a href="../evento/index.php" class="nav-link">
                🎉 Eventos
            </a>
        </nav>

        <div style="margin-top:auto; padding-top:1rem; border-top:1px solid rgba(255,255,255,0.1)">
            <a href="../painel/servidor.php" class="nav-link">
                ⬅ Voltar ao Painel
            </a>
        </div>
    </aside>

    <main>
        <header>
            <h1>Histórico Geral de Empréstimos</h1>

            <div class="user-info">
                Logado como:
                <strong>
                    <?= htmlspecialchars(
                        $_SESSION['nome_funcionario'] ?? 'Funcionário',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
            </div>
        </header>

        <div class="content-body">
            <div class="card-container">
                <h2 class="card-title">Todos os empréstimos</h2>

                <p>
                    Consulte os empréstimos registrados, os leitores,
                    os exemplares e as datas de devolução.
                </p>

                <div style="margin-bottom: 1rem;">
                    <input
                        type="search"
                        id="buscarHistorico"
                        placeholder="Buscar leitor, livro, patrimônio ou funcionário..."
                        style="width:100%; max-width:500px; padding:0.75rem; border:1px solid #cbd5e1; border-radius:6px;"
                    >
                </div>

                <?php if (empty($emprestimos)): ?>
                    <p>Nenhum empréstimo registrado ainda.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="tabelaHistorico">
                            <thead>
                                <tr>
                                    <th>Nº</th>
                                    <th>Leitor</th>
                                    <th>Livro</th>
                                    <th>Patrimônio</th>
                                    <th>Funcionário</th>
                                    <th>Retirada</th>
                                    <th>Devolução prevista</th>
                                    <th>Devolução realizada</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($emprestimos as $item): ?>
                                    <tr>
                                        <td><?= (int) $item['id_emprestimo'] ?></td>

                                        <td>
                                            <?= htmlspecialchars($item['nome_usuario'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($item['codigo_patrimonio'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($item['nome_funcionario'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($item['data_emprestimo'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($item['data_prevista_devolucao'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>

                                        <td>
                                            <?= !empty($item['data_devolucao'])
                                                ? htmlspecialchars($item['data_devolucao'], ENT_QUOTES, 'UTF-8')
                                                : '—' ?>
                                        </td>

                                        <td>
                                            <span class="badge badge-<?= htmlspecialchars($item['status'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars(ucfirst($item['status']), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        const campoBusca = document.getElementById('buscarHistorico');
        const tabela = document.getElementById('tabelaHistorico');

        if (campoBusca && tabela) {
            campoBusca.addEventListener('input', function () {
                const termo = this.value.toLocaleLowerCase('pt-BR');

                tabela.querySelectorAll('tbody tr').forEach(function (linha) {
                    linha.style.display = linha.textContent
                        .toLocaleLowerCase('pt-BR')
                        .includes(termo) ? '' : 'none';
                });
            });
        }
    </script>
</body>
</html>
