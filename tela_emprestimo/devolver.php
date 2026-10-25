<?php
session_start();

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/proteger_acesso.php';
require_once __DIR__ . '/../includes/registrar_log.php';

exigirCargo(['Administrador', 'Atendente']);

$erro = '';
$sucesso = '';

// Processamento da Devolução via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'registrar_devolucao') {
    $idEmprestimo = filter_input(INPUT_POST, 'id_emprestimo', FILTER_VALIDATE_INT);
    $dataDevolucao = date('Y-m-d');

    if (!$idEmprestimo) {
        $erro = 'Empréstimo inválido para devolução.';
    } else {
        try {
            $conexao->beginTransaction();

            // 1. Busca o empréstimo ativo e o exemplar associado
            $stmtEmp = $conexao->prepare("
                SELECT id_emprestimo, id_exemplar, status 
                FROM emprestimos 
                WHERE id_emprestimo = ? AND status = 'ativo' 
                FOR UPDATE
            ");
            $stmtEmp->execute([$idEmprestimo]);
            $emprestimo = $stmtEmp->fetch(PDO::FETCH_ASSOC);

            if (!$emprestimo) {
                throw new RuntimeException('Este empréstimo não foi encontrado ou já foi devolvido.');
            }

            $idExemplar = (int) $emprestimo['id_exemplar'];

            // 2. Atualiza o empréstimo para concluído/devolvido
            $stmtUpdateEmp = $conexao->prepare("
                UPDATE emprestimos 
                SET status = 'devolvido', data_devolucao = ? 
                WHERE id_emprestimo = ?
            ");
            $stmtUpdateEmp->execute([$dataDevolucao, $idEmprestimo]);

            // 3. Torna o exemplar disponível novamente
            $stmtUpdateEx = $conexao->prepare("
                UPDATE exemplares 
                SET status = 'disponivel' 
                WHERE id_exemplar = ?
            ");
            $stmtUpdateEx->execute([$idExemplar]);

            // 4. VERIFICA SE HÁ RESERVA ATIVA PARA ESTE LIVRO
            // 4. A devolução não altera automaticamente as reservas.
// A reserva continuará ativa até o funcionário registrar
// o empréstimo quando o leitor comparecer à biblioteca.

            // 5. Registra o log da devolução
            registrarLog($conexao, 'DEVOLUCAO', 'emprestimos', $idEmprestimo, 'Livro devolvido e exemplar disponibilizado.');
            
            $conexao->commit();

           $sucesso = 'Devolução registrada com sucesso! O exemplar já está disponível novamente no acervo.';
         } catch (Throwable $e) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }

            $erro = $e->getMessage();
        }
    }
}
// Carrega Empréstimos Ativos para Devolução
$stmtAtivos = $conexao->query("
    SELECT em.*, u.nome AS nome_usuario, u.cpf, l.titulo, ex.codigo_patrimonio, f.nome AS nome_funcionario
    FROM emprestimos em
    INNER JOIN usuarios u ON u.id_usuario = em.id_usuario
    INNER JOIN exemplares ex ON ex.id_exemplar = em.id_exemplar
    INNER JOIN livros l ON l.id_livro = ex.id_livro
    INNER JOIN funcionarios f ON f.id_funcionario = em.id_funcionario
    WHERE em.status = 'ativo'
    ORDER BY em.data_prevista_devolucao ASC
");
$emprestimosAtivos = $stmtAtivos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Devoluções - Biblioteca</title>
    <link rel="stylesheet" href="style_devolucao.css">
</head>
<body>

    <!-- Sidebar de Navegação -->
    <aside>
        <a href="#" class="brand">
            📚 <span>Biblioteca Municipal</span>
        </a>
        <nav class="nav-menu">
            <a href="index.php" class="nav-link">📋 Histórico Geral</a>
            <a href="index.php" class="nav-link">➕ Novo Empréstimo</a>
            <a href="devolver.php" class="nav-link active">📥 Devoluções</a>
            <a href="reservas.php" class="nav-link">🔖 Reservas</a>
            <a href="../notificacoes/index.php" class="nav-link">🔔 Notificações</a>
            <a href="../evento/index.php" class="nav-link">🎉 Eventos</a>
        </nav>
        <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
            <a href="../painel/servidor.php" class="nav-link">⬅ Voltar ao Painel</a>
        </div>
    </aside>

    <!-- Conteúdo Principal -->
    <main>
        <header>
            <h1>Painel de Controle de Devoluções</h1>
            <div class="user-info">
                Logado como: <strong><?= htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Funcionário', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </header>

        <div class="content-body">

            <?php if ($erro !== ''): ?>
                <div class="alert alert-error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($sucesso !== ''): ?>
                <div class="alert alert-success"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <div class="form-group" style="margin-bottom: 1rem;">
    <label for="buscaDevolucao">Buscar empréstimo:</label>
    <input
        type="search"
        id="buscaDevolucao"
        placeholder="Digite o nome do leitor, título ou patrimônio..."
        autocomplete="off"
        style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px;"
    >
    <small id="mensagemBusca" style="color: var(--cinza);"></small>
</div>

                <?php if (empty($emprestimosAtivos)): ?>
                    <p>Não há empréstimos pendentes de devolução no momento.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nº</th>
                                    <th>Leitor</th>
                                    <th>Livro</th>
                                    <th>Patrimônio</th>
                                    <th>Retirada</th>
                                    <th>Dev. Prevista</th>
                                    <th>Situação</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($emprestimosAtivos as $item): ?>
                                    <?php 
                                        $atrasado = ($item['data_prevista_devolucao'] < date('Y-m-d')); 
                                    ?>
                                    <tr>
                                        <td><?= (int) $item['id_emprestimo'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($item['nome_usuario'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                            <small style="color: var(--cinza);">CPF: <?= htmlspecialchars($item['cpf'], ENT_QUOTES, 'UTF-8') ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($item['codigo_patrimonio'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($item['data_emprestimo'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($item['data_prevista_devolucao'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <?php if ($atrasado): ?>
                                                <span class="badge badge-atrasado">Atrasado</span>
                                            <?php else: ?>
                                                <span class="badge badge-ativo">No Prazo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Confirma a devolução deste exemplar?');" style="margin: 0;">
                                                <input type="hidden" name="acao" value="registrar_devolucao">
                                                <input type="hidden" name="id_emprestimo" value="<?= (int) $item['id_emprestimo'] ?>">
                                                <button type="submit" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Registrar Devolução</button>
                                            </form>
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
document.addEventListener('DOMContentLoaded', function () {
    const campoBusca = document.getElementById('buscaDevolucao');
    const mensagemBusca = document.getElementById('mensagemBusca');

    const tabela = document.querySelector('.table-responsive table');

    if (!campoBusca || !tabela) {
        return;
    }

    const linhas = Array.from(tabela.querySelectorAll('tbody tr'));

    campoBusca.addEventListener('input', function () {
        const termo = campoBusca.value
            .trim()
            .toLocaleLowerCase('pt-BR');

        let encontrados = 0;

        linhas.forEach(function (linha) {
            const texto = linha.textContent
                .toLocaleLowerCase('pt-BR');

            const corresponde = texto.includes(termo);

            linha.style.display = corresponde ? '' : 'none';

            if (corresponde) {
                encontrados++;
            }
        });

        if (termo === '') {
            mensagemBusca.textContent = '';
        } else if (encontrados === 0) {
            mensagemBusca.textContent = 'Nenhum empréstimo encontrado.';
        } else {
            mensagemBusca.textContent =
                encontrados === 1
                    ? '1 empréstimo encontrado.'
                    : encontrados + ' empréstimos encontrados.';
        }
    });
});
</script>
</body>
</html>