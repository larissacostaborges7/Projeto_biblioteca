<?php
session_start();

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/proteger_acesso.php';
require_once __DIR__ . '/../includes/registrar_log.php';

exigirCargo(['Administrador', 'Atendente']);

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'cadastrar_emprestimo') {
    $idUsuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
    $idExemplar = filter_input(INPUT_POST, 'id_exemplar', FILTER_VALIDATE_INT);
    $dataPrevista = trim($_POST['data_prevista_devolucao'] ?? '');
    $dataEmprestimo = date('Y-m-d');
    $idFuncionario = (int) ($_SESSION['id_funcionario'] ?? 0);

    $dataValida = DateTime::createFromFormat('!Y-m-d', $dataPrevista);
    
    if (!$idUsuario || !$idExemplar || !$dataPrevista) {
        $erro = 'Preencha todos os campos do empréstimo corretamente.';
    } elseif (!$dataValida || $dataValida->format('Y-m-d') !== $dataPrevista) {
        $erro = 'A data de devolução informada é inválida.';
    } elseif ($dataPrevista < $dataEmprestimo) {
        $erro = 'A data prevista não pode ser anterior à data do empréstimo.';
    } else {
        try {
            $conexao->beginTransaction();
            // 1. Verifica exemplar e obtém o tipo (livro ou gibi) para checagem de limite
            $stmtExemplar =$conexao->prepare("
                SELECT ex.id_exemplar, ex.status, l.tipo 
                FROM exemplares ex 
                INNER JOIN livros l ON l.id_livro = ex.id_livro 
                WHERE ex.id_exemplar = ? 
                FOR UPDATE
            ");
            $stmtExemplar->execute([$idExemplar]);
            $exemplar =$stmtExemplar->fetch(PDO::FETCH_ASSOC);

            if (!$exemplar || $exemplar['status'] !== 'disponivel') {
                throw new RuntimeException('Este exemplar não está disponível.');
            }

            $tipoItem =$exemplar['tipo'] ?? 'livro';

            // 2. Verifica limite de exemplares ativos por leitor para este tipo (máximo de 2)
            $stmtLimite =$conexao->prepare("
                SELECT COUNT(*) 
                FROM emprestimos em
                INNER JOIN exemplares ex ON ex.id_exemplar = em.id_exemplar
                INNER JOIN livros l ON l.id_livro = ex.id_livro
                WHERE em.id_usuario = ? AND em.status = 'ativo' AND l.tipo = ?
            ");
            $stmtLimite->execute([$idUsuario,$tipoItem]);
            $ativosDoTipo = (int)$stmtLimite->fetchColumn();

            $limiteMaximo = 2; // Regra: até 2 para livros e 2 para gibis
            if ($ativosDoTipo >=$limiteMaximo) {
                throw new RuntimeException("O leitor já atingiu o limite máximo de {$limiteMaximo} exemplares ativos deste tipo ({$tipoItem}).");
            }

            // 3. Insere empréstimo
            $stmt =$conexao->prepare("
                INSERT INTO emprestimos (id_usuario, id_exemplar, id_funcionario, data_emprestimo, data_prevista_devolucao, status) 
                VALUES (?, ?, ?, ?, ?, 'ativo')
            ");
            $stmt->execute([$idUsuario, $idExemplar,$idFuncionario, $dataEmprestimo,$dataPrevista]);
            $idEmprestimo = (int)$conexao->lastInsertId();

            // 4. Atualiza exemplar com verificação estrita de linha afetada
            $stmtAtualizar =$conexao->prepare("UPDATE exemplares SET status = 'emprestado' WHERE id_exemplar = ? AND status = 'disponivel'");
            $stmtAtualizar->execute([$idExemplar]);

            if ($stmtAtualizar->rowCount() !== 1) {
                throw new RuntimeException('Erro crítico: O exemplar não pôde ser atualizado ou já foi emprestado.');
            }

            // 5. Log de auditoria
            registrarLog($conexao, 'EMPRESTIMO', 'emprestimos',$idEmprestimo, "Empréstimo registrado para o exemplar ID {$idExemplar}.");
            
            $conexao->commit();$sucesso = 'Empréstimo registrado com sucesso!';
        } catch (Throwable $e) {
            if ($conexao->inTransaction()) {$conexao->rollBack();
            }
            $erro =$e->getMessage();
        }
    }
}

// Carrega Dados para Listas
$stmtEmprestimos =$conexao->query("
    SELECT em.*, u.nome AS nome_usuario, l.titulo, ex.codigo_patrimonio, f.nome AS nome_funcionario
    FROM emprestimos em
    INNER JOIN usuarios u ON u.id_usuario = em.id_usuario
    INNER JOIN exemplares ex ON ex.id_exemplar = em.id_exemplar
    INNER JOIN livros l ON l.id_livro = ex.id_livro
    INNER JOIN funcionarios f ON f.id_funcionario = em.id_funcionario
    ORDER BY em.id_emprestimo DESC
");
$emprestimos =$stmtEmprestimos->fetchAll(PDO::FETCH_ASSOC);

// Usuários e Exemplares Disponíveis
$usuarios =$conexao->query('SELECT id_usuario, nome, cpf FROM usuarios ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);
$exemplares =$conexao->query("SELECT e.id_exemplar, e.codigo_patrimonio, l.titulo FROM exemplares e INNER JOIN livros l ON l.id_livro = e.id_livro WHERE e.status = 'disponivel' ORDER BY l.titulo")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Empréstimos - Biblioteca</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Sidebar de Navegação -->
    <aside>
        <a href="#" class="brand">
            📚 <span>Biblioteca Municipal</span>
        </a>
        <nav class="nav-menu">
            <a href="#" class="nav-link active" data-target="tab-historico">📋 Histórico Geral</a>
            <a href="#" class="nav-link" data-target="tab-cadastrar">➕ Novo Empréstimo</a>
            <a href="#" class="nav-link" data-target="tab-devolver">📥 Devoluções</a>
            <a href="#" class="nav-link" data-target="tab-reservas">🔖 Reservas</a>
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
            <h1>Painel de Controle de Empréstimos</h1>
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

            <!-- ABA 1: HISTÓRICO -->
            <div id="tab-historico" class="tab-content active">
                <div class="card-container">
                    <h2 class="card-title">Histórico Geral de Empréstimos</h2>
                    <?php if (empty($emprestimos)): ?>
                        <p>Nenhum empréstimo registrado ainda.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nº</th>
                                        <th>Leitor</th>
                                        <th>Livro</th>
                                        <th>Patrimônio</th>
                                        <th>Funcionário</th>
                                        <th>Retirada</th>
                                        <th>Dev. Prevista</th>
                                        <th>Devolução</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($emprestimos as$item): ?>
                                        <tr>
                                            <td><?= (int) $item['id_emprestimo'] ?></td>
                                            <td><?= htmlspecialchars($item['nome_usuario'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($item['codigo_patrimonio'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($item['nome_funcionario'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($item['data_emprestimo'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($item['data_prevista_devolucao'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= $item['data_devolucao'] ? htmlspecialchars($item['data_devolucao'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
                                            <td>
                                                <span class="badge badge-<?= $item['status'] ?>">
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

            <!-- ABA 2: CADASTRAR EMPRÉSTIMO -->
            <div id="tab-cadastrar" class="tab-content">
                <div class="card-container">
                    <h2 class="card-title">Novo Registro de Empréstimo</h2>
                    <?php if (count($usuarios) === 0): ?>
                        <p>Não existem usuários cadastrados.</p>
                    <?php elseif (count($exemplares) === 0): ?>
                        <p>Não existem exemplares disponíveis para empréstimo no momento.</p>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="acao" value="cadastrar_emprestimo">
                            <div class="form-grid">
                                
                                <!-- CAMPO LEITOR COM AUTOCOMPLETE FLUÍDO -->
                                <div class="form-group">
                                    <label for="busca_usuario">Leitor (Digite o Nome ou CPF):</label>
                                    <input type="text" id="busca_usuario" placeholder="Digite para buscar leitor..." autocomplete="off" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    
                                    <select name="id_usuario" id="id_usuario" required>
                                        <option value="">Selecione um usuário</option>
                                        <?php foreach ($usuarios as$usuario): ?>
                                            <option value="<?= (int) $usuario['id_usuario'] ?>">
                                                <?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?> — CPF: <?= htmlspecialchars($usuario['cpf'], ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- CAMPO LIVRO/EXEMPLAR COM AUTOCOMPLETE FLUÍDO -->
                                <div class="form-group">
                                    <label for="busca_exemplar">Livro / Exemplar (Digite o Título ou Patrimônio):</label>
                                    <input type="text" id="busca_exemplar" placeholder="Digite para buscar livro..." autocomplete="off" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    
                                    <select name="id_exemplar" id="id_exemplar" required>
                                        <option value="">Selecione um exemplar disponível</option>
                                        <?php foreach ($exemplares as$exemplar): ?>
                                            <option value="<?= (int) $exemplar['id_exemplar'] ?>">
                                                <?= htmlspecialchars($exemplar['titulo'], ENT_QUOTES, 'UTF-8') ?> (Patrimônio: <?= htmlspecialchars($exemplar['codigo_patrimonio'], ENT_QUOTES, 'UTF-8') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- DATA DE PREVISÃO -->
                                <div class="form-group">
                                    <label for="data_prevista_devolucao">Data Prevista para Devolução (Padrão: 14 dias):</label>
                                    <input type="date" name="data_prevista_devolucao" id="data_prevista_devolucao" min="<?= date('Y-m-d') ?>" required style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; background-color: #fff;">
                                </div>

                            </div>
                            <button type="submit" class="btn" style="margin-top: 1rem;">Registrar Empréstimo</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ABA 3: DEVOLUÇÃO -->
            <div id="tab-devolver" class="tab-content">
                <div class="card-container">
                    <h2 class="card-title">Gerenciar Devoluções Pendentes</h2>
                    <p style="color: var(--slate-600); margin-bottom: 1.5rem;">Aqui você visualiza todos os empréstimos ativos e pode processar a devolução dos exemplares.</p>
                    <a href="devolver.php" class="btn btn-secondary">Abrir Tela Dedicada de Devoluções</a>
                </div>
            </div>

            <!-- ABA 4: RESERVAS -->
            <div id="tab-reservas" class="tab-content">
                <div class="card-container">
                    <h2 class="card-title">Controle de Reservas de Livros</h2>
                    <p style="color: var(--slate-600); margin-bottom: 1.5rem;">Visualize e gerencie a fila de reservas dos leitores.</p>
                </div>
            </div>

        </div>
    </main>

    <script src="script.js"></script>
    <script>
        // Define automaticamente a data de devolução para 14 dias no futuro ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            const dataInput = document.getElementById('data_prevista_devolucao');
            if (dataInput && !dataInput.value) {
                const hoje = new Date();
                hoje.setDate(hoje.getDate() + 14);
                const ano = hoje.getFullYear();
                const mes = String(hoje.getMonth() + 1).padStart(2, '0');
                const dia = String(hoje.getDate()).padStart(2, '0');
                dataInput.value = `${ano}-${mes}-${dia}`;
            }
        });
    </script>
</body>
</html>