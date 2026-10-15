<?php
session_start();

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/proteger_acesso.php';
require_once __DIR__ . '/../includes/registrar_log.php';

// Permite o acesso para qualquer funcionário logado (Administradores, Atendentes, etc.)
exigirCargo(['Administrador', 'Atendente', 'Bibliotecário', 'Funcionário']); 
// Nota: Se a sua função de validação aceitar qualquer usuário logado, você pode verificar como está configurado no seu 'proteger_acesso.php'.

$erro = '';
$sucesso = '';

// Processamento do Formulário (Cadastrar Evento)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'cadastrar_evento') {
    $titulo = trim(filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_SPECIAL_CHARS));
    $descricao = trim(filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_SPECIAL_CHARS));
    $dataEvento = filter_input(INPUT_POST, 'data_evento', FILTER_DEFAULT);

    if (empty($titulo) || empty($dataEvento)) {
        $erro = 'Por favor, preencha o título e a data do evento.';
    } else {
        try {
            $stmt = $conexao->prepare("
                INSERT INTO eventos (titulo, descricao, data_evento, id_funcionario) 
                VALUES (?, ?, ?, ?)
            ");
            $idFuncionario = $_SESSION['id_funcionario'] ?? null;
            $stmt->execute([$titulo, $descricao, $dataEvento, $idFuncionario]);

            $idEvento = $conexao->lastInsertId();
            registrarLog($conexao, 'CADASTRO_EVENTO', 'eventos', $idEvento, "Evento '$titulo' cadastrado.");

            $sucesso = 'Evento cadastrado com sucesso! Ele já está visível na Central de Notificações.';
        } catch (Throwable $e) {
            $erro = 'Erro ao cadastrar evento: ' . $e->getMessage();
        }
    }
}

// Processamento de Exclusão de Evento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'excluir_evento') {
    $idEvento = filter_input(INPUT_POST, 'id_evento', FILTER_VALIDATE_INT);

    if ($idEvento) {
        try {
            $stmtDel = $conexao->prepare("DELETE FROM eventos WHERE id_evento = ?");
            $stmtDel->execute([$idEvento]);

            registrarLog($conexao, 'EXCLUSAO_EVENTO', 'eventos', $idEvento, "Evento ID $idEvento removido.");
            $sucesso = 'Evento removido com sucesso!';
        } catch (Throwable $e) {
            $erro = 'Erro ao remover evento: ' . $e->getMessage();
        }
    }
}

// Buscar Próximos Eventos Cadastrados
try {
    $stmtEventos = $conexao->query("
        SELECT e.*, f.nome AS nome_funcionario 
        FROM eventos e
        LEFT JOIN funcionarios f ON f.id_funcionario = e.id_funcionario
        ORDER BY e.data_evento ASC
    ");
    $listaEventos = $stmtEventos ? $stmtEventos->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable $e) {
    $listaEventos = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Eventos - Biblioteca</title>
    <link rel="stylesheet" href="../notificacoes/style.css">
</head>
<body>

    <!-- Sidebar de Navegação -->
    <aside>
        <a href="#" class="brand">
            📚 <span>Biblioteca Municipal</span>
        </a>
        <nav class="nav-menu">
            <a href="../tela_emprestimo/index.php" class="nav-link">📋 Empréstimos</a>
            <a href="../tela_emprestimo/devolver.php" class="nav-link">📥 Devoluções</a>
            <a href="../tela_emprestimo/reservas.php" class="nav-link">🔖 Reservas</a>
            <a href="../notificacoes/index.php" class="nav-link">🔔 Notificações</a>
            <a href="index.php" class="nav-link active">🎉 Eventos</a>
        </nav>
        <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
            <a href="../painel/servidor.php" class="nav-link">⬅ Voltar ao Painel</a>
        </div>
    </aside>

    <!-- Conteúdo Principal -->
    <main>
        <header>
            <h1>Gerenciamento de Eventos e Avisos</h1>
            <div class="user-info">
                Logado como: <strong><?= htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Funcionário', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </header>

        <div class="content-body">

            <?php if ($erro !== ''): ?>
                <div class="alert alert-error" style="background: #fde8e8; color: #9b1c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px;"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($sucesso !== ''): ?>
                <div class="alert alert-success" style="background: #e6f4ea; color: #1e7e34; padding: 12px; border-radius: 6px; margin-bottom: 20px;"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <!-- Formulário de Cadastro -->
            <div class="card-container">
                <h2 class="card-title">➕ Cadastrar Novo Evento / Aviso</h2>
                
                <form method="POST" style="margin-top: 15px; display: flex; flex-direction: column; gap: 15px;">
                    <input type="hidden" name="acao" value="cadastrar_evento">

                    <div style="display: flex; gap: 20px;">
                        <div style="flex: 2;">
                            <label style="display: block; font-weight: 600; margin-bottom: 5px;">Título do Evento *</label>
                            <input type="text" name="titulo" required placeholder="Ex: Feira de Troca de Livros" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--cinza-claro);">
                        </div>

                        <div style="flex: 1;">
                            <label style="display: block; font-weight: 600; margin-bottom: 5px;">Data do Evento *</label>
                            <input type="date" name="data_evento" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--cinza-claro);">
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">Descrição / Detalhes</label>
                        <textarea name="descricao" rows="3" placeholder="Informações sobre horário, local interno, público-alvo..." style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--cinza-claro); font-family: inherit;"></textarea>
                    </div>

                    <div>
                        <button type="submit" class="btn" style="cursor: pointer; border: none; padding: 10px 20px;">Salvar Evento</button>
                    </div>
                </form>
            </div>

            <!-- Lista de Eventos Cadastrados -->
            <div class="card-container">
                <h2 class="card-title">📅 Eventos Programados</h2>

                <?php if (empty($listaEventos)): ?>
                    <p style="color: var(--cinza);">Nenhum evento cadastrado até o momento.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Título</th>
                                    <th>Descrição</th>
                                    <th>Cadastrado por</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listaEventos as $ev): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars(date('d/m/Y', strtotime($ev['data_evento'])), ENT_QUOTES, 'UTF-8') ?></strong></td>
                                        <td><strong><?= htmlspecialchars($ev['titulo'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                        <td><?= htmlspecialchars($ev['descricao'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($ev['nome_funcionario'] ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Tem certeza que deseja remover este evento?');" style="margin: 0;">
                                                <input type="hidden" name="acao" value="excluir_evento">
                                                <input type="hidden" name="id_evento" value="<?= (int) $ev['id_evento'] ?>">
                                                <button type="submit" class="btn" style="background: #9b1c1c; padding: 0.4rem 0.8rem; font-size: 0.85rem; border: none; cursor: pointer;">Excluir</button>
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

</body>
</html>