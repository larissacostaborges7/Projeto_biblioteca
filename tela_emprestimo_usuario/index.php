<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/conexao.php';

$naoLogado = !isset($_SESSION['id_usuario']);
$erro = '';
$meusEmprestimos = [];

if (!$naoLogado) {
    try {
        $stmt = $conexao->prepare("
            SELECT em.*, l.titulo, ex.codigo_patrimonio
            FROM emprestimos em
            INNER JOIN exemplares ex
                ON ex.id_exemplar = em.id_exemplar
            INNER JOIN livros l
                ON l.id_livro = ex.id_livro
            WHERE em.id_usuario = ?
            ORDER BY em.id_emprestimo DESC
        ");

        $stmt->execute([$_SESSION['id_usuario']]);
        $meusEmprestimos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log($e->getMessage());
        $erro = 'Não foi possível carregar seus empréstimos.';
    }
}

$total = count($meusEmprestimos);
$emDia = 0;
$atrasados = 0;

foreach ($meusEmprestimos as $item) {
    $status = strtolower($item['status'] ?? '');

    if (in_array($status, ['devolvido', 'devolvida', 'cancelado', 'cancelada'], true)) {
        continue;
    }

    $dataDevolucao = $item['data_prevista_devolucao'] ?? '';

    if ($dataDevolucao !== '' && $dataDevolucao < date('Y-m-d')) {
        $atrasados++;
    } else {
        $emDia++;
    }
}

function h($valor): string {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function dataBR($data): string {
    if (!$data) {
        return '—';
    }

    $timestamp = strtotime($data);

    return $timestamp ? date('d/m/Y', $timestamp) : h($data);
}

function classeStatus($item): string {
    $status = strtolower($item['status'] ?? '');

    if (in_array($status, ['devolvido', 'devolvida'], true)) {
        return 'badge-devolvido';
    }

    if (in_array($status, ['cancelado', 'cancelada'], true)) {
        return 'badge-cancelada';
    }

    $data = $item['data_prevista_devolucao'] ?? '';

    if ($data && $data < date('Y-m-d')) {
        return 'badge-atrasado';
    }

    return 'badge-ativo';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus empréstimos | Biblioteca Municipal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<aside class="menu-lateral">
    <a href="../tela_cadastro/index.php" class="logo">
        <span class="logo-icone">📚</span>
        <span class="logo-texto">
            <span class="logo-nome">Biblioteca Municipal</span>
            <span class="logo-descricao">Conhecimento para todos</span>
        </span>
    </a>

    <span class="menu-titulo">Área do leitor</span>
    
    <nav>
        <a href="../tela_principal2/index.html">
            <span>⌂</span> Início
        </a>

        <a href="index.php" class="ativo">
            <span>▤</span> Meus empréstimos
        </a>

        <a href="reservas.php">
            <span>▣</span> Minhas reservas
        </a>

        <a href="historico.php">
            <span>◷</span> Histórico
        </a>

        <a href="../livros/listar.php">
            <span>📚</span> Consultar acervo
        </a>
    </nav>

    <a href="../tela_cadastro/sair_usuario.php" class="botao botao-login">
        Sair da conta
    </a>
</aside>

<main class="conteudo-principal">
    <header class="cabecalho-superior">
        <div>
            <p class="texto-secundario">ÁREA DO LEITOR</p>
            <h1>Meus empréstimos</h1>
            <p>Acompanhe seus livros e as datas de devolução.</p>
        </div>

        <?php if (!$naoLogado): ?>
            <span class="perfil">
                👤 <?= h($_SESSION['nome_usuario'] ?? 'Leitor') ?>
            </span>
        <?php endif; ?>
    </header>

    <?php if ($naoLogado): ?>

        <section class="alerta-caixa">
            <h2>Entre na sua conta</h2>
            <p>Faça login como leitor para consultar seus empréstimos.</p>
            <!-- Link corrigido para a tela de cadastro/login -->
            <a href="../tela_cadastro/index.php" class="botao">Entrar na conta</a>
        </section>

    <?php elseif ($erro !== ''): ?>

        <section class="alert alerta-erro">
            <?= h($erro) ?>
        </section>

    <?php else: ?>

        <section class="cards-resumo">
            <article class="card-resumo card-azul">
                <span class="card-icone">📚</span>
                <p>Total de empréstimos</p>
                <strong><?= $total ?></strong>
                <small>Registros encontrados</small>
            </article>

            <article class="card-resumo card-verde">
                <span class="card-icone">✓</span>
                <p>Em dia</p>
                <strong><?= $emDia ?></strong>
                <small>Sem atraso na devolução</small>
            </article>

            <article class="card-resumo card-vermelho">
                <span class="card-icone">!</span>
                <p>Atrasados</p>
                <strong><?= $atrasados ?></strong>
                <small>Verifique a devolução prevista</small>
            </article>
        </section>

        <section class="painel-tabela">
            <div class="titulo-tabela">
                <div>
                    <h2>Seus empréstimos</h2>
                    <p>Consulte os detalhes dos livros retirados.</p>
                </div>
            </div>

            <?php if (empty($meusEmprestimos)): ?>

                <div class="alerta-caixa">
                    <span class="livro-vazio">📖</span>
                    <h3>Nenhum empréstimo por enquanto</h3>
                    <p>Quando você tiver empréstimos registrados, eles aparecerão aqui.</p>
                    <a href="../livros/listar.php" class="botao">
                        Explorar o acervo
                    </a>
                </div>

            <?php else: ?>

                <div class="tabela-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Patrimônio</th>
                                <th>Título</th>
                                <th>Retirada</th>
                                <th>Devolução prevista</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($meusEmprestimos as $item): ?>
                                <tr>
                                    <td><?= h($item['codigo_patrimonio'] ?? '—') ?></td>

                                    <td class="titulo-livro">
                                        <?= h($item['titulo'] ?? 'Livro sem título') ?>
                                    </td>

                                    <td><?= dataBR($item['data_emprestimo'] ?? '') ?></td>

                                    <td><?= dataBR($item['data_prevista_devolucao'] ?? '') ?></td>

                                    <td>
                                        <span class="badge <?= classeStatus($item) ?>">
                                            <?= h(ucfirst($item['status'] ?? 'Em andamento')) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <p class="total-registros">
                    <?= $total ?> registro(s) encontrado(s)
                </p>

            <?php endif; ?>

            <div class="lembrete">
                <span>ⓘ</span>
                <p>
                    <strong>Lembrete:</strong>
                    confira a data prevista de devolução de cada livro.
                    Se precisar de ajuda, procure a equipe da biblioteca.
                </p>
            </div>
        </section>

    <?php endif; ?>

    <footer class="rodape">
        Biblioteca Municipal de Arujá
        <span>•</span>
        Sistema de Gerenciamento de Bibliotecas
    </footer>
</main>

<script src="script.js"></script>
</body>
</html>