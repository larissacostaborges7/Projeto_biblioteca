<?php
session_start();

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/proteger_acesso.php';
require_once __DIR__ . '/../includes/registrar_log.php';

exigirCargo(['Administrador', 'Atendente']);

$hoje = date('Y-m-d');

$livrosNovos = [];
$atrasados = [];
$devolucoesHoje = [];
$eventos = [];
$reservasDisponiveis = [];

/*
|--------------------------------------------------------------------------
| 1. Livros cadastrados recentemente
|--------------------------------------------------------------------------
| A coluna data_cadastro precisa existir na tabela livros.
*/
try {
    $stmtNovos = $conexao->query("
        SELECT id_livro, titulo, autor, data_cadastro
        FROM livros
        WHERE data_cadastro >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY data_cadastro DESC
        LIMIT 10
    ");

    $livrosNovos = $stmtNovos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erro ao consultar livros recentes: ' . $e->getMessage());
}

/*
|--------------------------------------------------------------------------
| 2. Empréstimos atrasados
|--------------------------------------------------------------------------
*/
$stmtAtrasados = $conexao->prepare("
    SELECT
        em.*,
        u.nome AS nome_usuario,
        u.cpf,
        l.titulo,
        ex.codigo_patrimonio
    FROM emprestimos em
    INNER JOIN usuarios u
        ON u.id_usuario = em.id_usuario
    INNER JOIN exemplares ex
        ON ex.id_exemplar = em.id_exemplar
    INNER JOIN livros l
        ON l.id_livro = ex.id_livro
    WHERE em.status = 'ativo'
      AND em.data_prevista_devolucao < ?
    ORDER BY em.data_prevista_devolucao ASC
");

$stmtAtrasados->execute([$hoje]);
$atrasados = $stmtAtrasados->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| 3. Devoluções previstas para hoje
|--------------------------------------------------------------------------
*/
$stmtHoje = $conexao->prepare("
    SELECT
        em.*,
        u.nome AS nome_usuario,
        u.cpf,
        l.titulo,
        ex.codigo_patrimonio
    FROM emprestimos em
    INNER JOIN usuarios u
        ON u.id_usuario = em.id_usuario
    INNER JOIN exemplares ex
        ON ex.id_exemplar = em.id_exemplar
    INNER JOIN livros l
        ON l.id_livro = ex.id_livro
    WHERE em.status = 'ativo'
      AND em.data_prevista_devolucao = ?
    ORDER BY u.nome ASC
");

$stmtHoje->execute([$hoje]);
$devolucoesHoje = $stmtHoje->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| 4. Próximos eventos
|--------------------------------------------------------------------------
*/
try {
    $stmtEventos = $conexao->query("
        SELECT *
        FROM eventos
        WHERE data_evento >= CURDATE()
        ORDER BY data_evento ASC
        LIMIT 5
    ");

    $eventos = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erro ao consultar eventos: ' . $e->getMessage());
}

/*
|--------------------------------------------------------------------------
| 5. Reservas disponíveis para retirada
|--------------------------------------------------------------------------
*/
try {
    $stmtDisponiveis = $conexao->query("
        SELECT
            r.*,
            u.nome AS nome_usuario,
            u.cpf,
            l.titulo
        FROM reservas r
        INNER JOIN usuarios u
            ON u.id_usuario = r.id_usuario
        INNER JOIN livros l
            ON l.id_livro = r.id_livro
        WHERE r.status = 'disponivel'
        ORDER BY r.data_reserva ASC
    ");

    $reservasDisponiveis = $stmtDisponiveis->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erro ao consultar reservas disponíveis: ' . $e->getMessage());
}

/*
|--------------------------------------------------------------------------
| 6. Total de alertas
|--------------------------------------------------------------------------
| Cada registro listado nas seções abaixo conta como um alerta.
*/
$totalAlertas =
    count($livrosNovos) +
    count($atrasados) +
    count($devolucoesHoje) +
    count($eventos) +
    count($reservasDisponiveis);

/*
|--------------------------------------------------------------------------
| Funções auxiliares de apresentação
|--------------------------------------------------------------------------
*/
function escapar($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function formatarData($data): string
{
    if (empty($data)) {
        return '—';
    }

    $timestamp = strtotime($data);

    return $timestamp !== false
        ? date('d/m/Y', $timestamp)
        : '—';
}

function calcularDiasAtraso(string $dataPrevista, string $hoje): int
{
    $prevista = new DateTime($dataPrevista);
    $dataAtual = new DateTime($hoje);

    if ($prevista >= $dataAtual) {
        return 0;
    }

    return (int) $prevista->diff($dataAtual)->days;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Central de Notificações - Biblioteca</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>

    <aside>
        <a href="#" class="brand">
            📚 <span>Biblioteca Municipal</span>
        </a>

        <nav class="nav-menu">
            <a href="index.php" class="nav-link">
                📋 Histórico Geral
            </a>

            <a href="../tela_emprestimo/index.php" class="nav-link">
                📋 Empréstimos
            </a>

            <a href="../tela_emprestimo/devolver.php" class="nav-link">
                📥 Devoluções
            </a>

            <a href="../tela_emprestimo/reservas.php" class="nav-link">
                🔖 Reservas
            </a>

            <a href="../evento/index.php" class="nav-link">
                🎉 Eventos
            </a>

            <a href="index.php" class="nav-link active">
                🔔 Notificações
                <?= $totalAlertas > 0 ? '(' . $totalAlertas . ')' : '' ?>
            </a>
        </nav>

        <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
            <a href="../painel/servidor.php" class="nav-link">
                ⬅ Voltar ao Painel
            </a>
        </div>
    </aside>

    <main>
        <header>
            <h1>Central de Notificações e Avisos</h1>

            <div class="user-info">
                Logado como:
                <strong>
                    <?= escapar($_SESSION['nome_funcionario'] ?? 'Funcionário') ?>
                </strong>
            </div>
        </header>

        <div class="content-body">

            <!-- LIVROS RECENTES -->
            <div class="card-container" style="margin-bottom: 2rem;">
                <h2 class="card-title">
                    📖 Últimos Livros Adicionados ao Acervo
                    (<?= count($livrosNovos) ?>)
                </h2>

                <?php if (empty($livrosNovos)): ?>
                    <p style="color: var(--cinza);">
                        Nenhum livro recente encontrado.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Cód.</th>
                                    <th>Título</th>
                                    <th>Autor</th>
                                    <th>Data de cadastro</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($livrosNovos as $livro): ?>
                                    <tr>
                                        <td>
                                            <?= (int) $livro['id_livro'] ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= escapar($livro['titulo']) ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?= escapar($livro['autor']) ?>
                                        </td>

                                        <td>
                                            <?= formatarData($livro['data_cadastro']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- EVENTOS -->
            <div class="card-container" style="margin-bottom: 2rem;">
                <h2 class="card-title">
                    🎉 Próximos Eventos e Avisos
                    (<?= count($eventos) ?>)
                </h2>

                <?php if (empty($eventos)): ?>
                    <p style="color: var(--cinza);">
                        Nenhum evento ou aviso programado no momento.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data do evento</th>
                                    <th>Título</th>
                                    <th>Descrição</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($eventos as $ev): ?>
                                    <?php
                                    $tituloEvento =
                                        $ev['titulo']
                                        ?? $ev['nome_evento']
                                        ?? 'Evento';
                                    ?>

                                    <tr>
                                        <td>
                                            <strong>
                                                <?= formatarData($ev['data_evento'] ?? null) ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?= escapar($tituloEvento) ?>
                                        </td>

                                        <td>
                                            <?= escapar($ev['descricao'] ?? '') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- EMPRÉSTIMOS ATRASADOS -->
            <div class="card-container" style="margin-bottom: 2rem;">
                <h2 class="card-title">
                    ⚠️ Empréstimos em Atraso
                    (<?= count($atrasados) ?>)
                </h2>

                <?php if (empty($atrasados)): ?>
                    <p style="color: var(--cinza);">
                        Nenhum empréstimo em atraso. Tudo em dia!
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Leitor</th>
                                    <th>Livro</th>
                                    <th>Patrimônio</th>
                                    <th>Data prevista</th>
                                    <th>Atraso</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($atrasados as $item): ?>
                                    <?php
                                    $diasAtraso = calcularDiasAtraso(
                                        $item['data_prevista_devolucao'],
                                        $hoje
                                    );
                                    ?>

                                    <tr>
                                        <td>
                                            <strong>
                                                <?= escapar($item['nome_usuario']) ?>
                                            </strong>
                                            <br>

                                            <small style="color: var(--cinza);">
                                                CPF: <?= escapar($item['cpf']) ?>
                                            </small>
                                        </td>

                                        <td>
                                            <?= escapar($item['titulo']) ?>
                                        </td>

                                        <td>
                                            <?= escapar($item['codigo_patrimonio']) ?>
                                        </td>

                                        <td>
                                            <?= formatarData($item['data_prevista_devolucao']) ?>
                                        </td>

                                        <td>
                                            <span class="badge badge-atrasado">
                                                <?= $diasAtraso ?>
                                                <?= $diasAtraso === 1 ? 'dia' : 'dias' ?>
                                            </span>
                                        </td>

                                        <td>
                                            <a
                                                href="../tela_emprestimo/devolver.php"
                                                class="btn"
                                                style="padding: 0.4rem 0.8rem; font-size: 0.85rem;"
                                            >
                                                Ir para Devolução
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- DEVOLUÇÕES DE HOJE -->
            <div class="card-container" style="margin-bottom: 2rem;">
                <h2 class="card-title">
                    📅 Devoluções que Vencem Hoje
                    (<?= count($devolucoesHoje) ?>)
                </h2>

                <?php if (empty($devolucoesHoje)): ?>
                    <p style="color: var(--cinza);">
                        Nenhuma devolução agendada para hoje.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Leitor</th>
                                    <th>Livro</th>
                                    <th>Patrimônio</th>
                                    <th>Data de retirada</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($devolucoesHoje as $item): ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <?= escapar($item['nome_usuario']) ?>
                                            </strong>
                                            <br>

                                            <small style="color: var(--cinza);">
                                                CPF: <?= escapar($item['cpf']) ?>
                                            </small>
                                        </td>

                                        <td>
                                            <?= escapar($item['titulo']) ?>
                                        </td>

                                        <td>
                                            <?= escapar($item['codigo_patrimonio']) ?>
                                        </td>

                                        <td>
                                            <?= formatarData($item['data_emprestimo'] ?? null) ?>
                                        </td>

                                        <td>
                                            <span class="badge badge-ativo">
                                                Vence Hoje
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- RESERVAS DISPONÍVEIS -->
            <div class="card-container" style="margin-bottom: 2rem;">
                <h2 class="card-title">
                    ✨ Livros Reservados Disponíveis
                    (<?= count($reservasDisponiveis) ?>)
                </h2>

                <?php if (empty($reservasDisponiveis)): ?>
                    <p style="color: var(--cinza);">
                        Nenhum livro reservado aguardando retirada.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Leitor</th>
                                    <th>Livro disponível</th>
                                    <th>Data da reserva</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($reservasDisponiveis as $item): ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <?= escapar($item['nome_usuario']) ?>
                                            </strong>
                                            <br>

                                            <small style="color: var(--cinza);">
                                                CPF: <?= escapar($item['cpf']) ?>
                                            </small>
                                        </td>

                                        <td>
                                            <?= escapar($item['titulo']) ?>
                                        </td>

                                        <td>
                                            <?= formatarData($item['data_reserva'] ?? null) ?>
                                        </td>

                                        <td>
                                            <a
                                                href="../tela_emprestimo/index.php"
                                                class="btn"
                                                style="padding: 0.4rem 0.8rem; font-size: 0.85rem;"
                                            >
                                                Fazer Empréstimo
                                            </a>
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

    <script src="script.js"></script>
</body>
</html>