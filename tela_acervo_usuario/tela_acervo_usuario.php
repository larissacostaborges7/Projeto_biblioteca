<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/conexao.php';

$logado = isset($_SESSION['id_usuario']);
$erro = '';
$livros = [];

try {
    $stmt = $conexao->query("
        SELECT 
            l.id_livro,
            l.titulo,
            l.autor,
            l.sinopse,
            l.isbn,
            l.editora,
            l.ano_publicacao,
            l.classificacao_etaria,
            l.id_categoria,
            l.capa,
            c.nome AS nome_categoria,
            COUNT(ex.id_exemplar) AS total_exemplares,
            SUM(CASE WHEN ex.status = 'disponivel' THEN 1 ELSE 0 END) AS exemplares_disponiveis
        FROM livros l
        LEFT JOIN categorias c ON c.id_categoria = l.id_categoria
        LEFT JOIN exemplares ex ON ex.id_livro = l.id_livro
        GROUP BY l.id_livro
        ORDER BY l.id_livro ASC
    ");
    $livros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = 'Erro ao carregar o acervo: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acervo Completo - Biblioteca Municipal de Arujá</title>
    <link rel="stylesheet" href="style_usuario.css?v=6">
</head>
<body>

    <!-- Sidebar de Navegação Lateral -->
    <aside>
        <a href="../tela_principal2/index.php" class="brand">
            📚 <span>Biblioteca Arujá</span>
        </a>
        <nav class="nav-menu">
           <a href="#" class="nav-link active">📚 Acervo Completo</a>
<a href="#"
   class="nav-link"
   onclick="filtrarPorStatus('favoritos', this); return false;">
    ♡ Meus Favoritos
</a>

<a href="../tela_emprestimo_usuario/index.php" class="nav-link">
    📖 Meus Empréstimos
</a>

<a href="../tela_emprestimo_usuario/reservas.php" class="nav-link">
    ▣ Minhas Reservas
</a>

<a href="../mapa_virtual/index.php" class="nav-link">
    🗺️ Explorar Mapa Virtual
</a>

<a href="../tela_principal2/index.php" class="nav-link">
    🏠 Página Inicial
</a>
        </nav>
        <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
            <a href="../tela_principal2/index.php" class="nav-link">⬅ Voltar ao Site</a>
        </div>
    </aside>

    <!-- Conteúdo Principal -->
    <main>
        <header>
            <h1>Acervo da Biblioteca</h1>
            <div class="user-info">
                <?php if ($logado): ?>
                    Logado como: <strong><?= htmlspecialchars($_SESSION['nome_usuario'] ?? 'Leitor(a)', ENT_QUOTES, 'UTF-8') ?></strong>
                <?php else: ?>
                    <a href="../tela_cadastro/index.php" class="botao" style="font-size: 0.85rem; padding: 6px 12px; text-decoration: none;">Entrar / Criar Conta</a>
                <?php endif; ?>
            </div>
        </header>

        <div class="content-body">
            
            <?php if (!empty($erro)): ?>
                <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php else: ?>

                <!-- BARRA DE BUSCA E FILTROS -->
                <div class="card-container" style="margin-bottom: 25px;">
                    <div class="busca-container" style="margin-bottom: 15px;">
                        <input 
                            type="text" 
                            id="inputBuscaAcervo" 
                            class="busca-input" 
                            placeholder="🔍 Pesquise por título, autor, categoria..." 
                            onkeyup="aplicarFiltros()"
                        >
                    </div>

                    <div class="filtros-acervo">
                        <button type="button" class="filtro-botao ativo" onclick="filtrarPorStatus('todos', this)">Todos (<?= count($livros) ?>)</button>
                        <button type="button" class="filtro-botao" onclick="filtrarPorStatus('disponivel', this)">Apenas Disponíveis</button>
                        <button type="button" class="filtro-botao" onclick="filtrarPorStatus('indisponivel', this)">Indisponíveis / Sem Exemplar</button>
                        <button type="button" class="filtro-botao"
        onclick="filtrarPorStatus('favoritos', this)">
    ♡ Meus Favoritos
</button>
                    </div>
                </div>

                <div class="grade-livros" id="gridLivros">
                    <?php if (empty($livros)): ?>
                        <div class="mensagem-vazia" style="grid-column: 1 / -1;">
                            Nenhum livro encontrado no banco de dados.
                        </div>
                    <?php else: ?>
                        <?php foreach ($livros as $livro): ?>
                            <?php 
                                $qtdDisponivel = (int)($livro['exemplares_disponiveis'] ?? 0);
                                $temExemplar = $qtdDisponivel > 0;
                                $statusClasse = $temExemplar ? 'disponivel' : 'indisponivel';
                                $categoriaTexto = $livro['nome_categoria'] ?? ($livro['id_categoria'] ? 'Cat. #' . $livro['id_categoria'] : '');
                                
                            $nomeCapa = $livro['capa'] ?? '';
$caminhoFisicoCapa = __DIR__ . '/../' . $nomeCapa;
$temImagemCapa = !empty($nomeCapa) && file_exists($caminhoFisicoCapa);
                                $sinopseCompleta = trim($livro['sinopse'] ?? '');
                                if (empty($sinopseCompleta)) {
                                    $sinopseCompleta = 'Sinopse não cadastrada no sistema.';
                                }

                                // Link e texto ajustados para RESERVA
                                $linkReserva = $logado 
                                    ? "../tela_emprestimo_usuario/reservas.php?livro_id=" . (int)$livro['id_livro'] 
                                    : "../tela_cadastro/index.php";

                                $textoBotaoReserva = $temExemplar ? 'Reservar' : 'Fila de Reserva';
                            ?>
                            
                            <!-- CONTAINER DO FLIP CARD 3D -->
                            <div class="card-livro-container item-livro-card" 
                              data-id-livro="<?= (int)$livro['id_livro'] ?>"
                                 data-titulo="<?= htmlspecialchars(mb_strtolower($livro['titulo']), ENT_QUOTES, 'UTF-8') ?>"
                                 data-autor="<?= htmlspecialchars(mb_strtolower($livro['autor'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                 data-categoria="<?= htmlspecialchars(mb_strtolower($categoriaTexto), ENT_QUOTES, 'UTF-8') ?>"
                                 data-status="<?= $temExemplar ? 'disponivel' : 'indisponivel' ?>">
                                
                                <div class="card-livro-flipper">
                                    
                                    <!-- FRENTE DO CARD -->
                                    <div class="card-frente">
                                        <div class="livro-capa">
                                            <?php if ($temImagemCapa): ?>
                                                
<img src="../<?= htmlspecialchars($nomeCapa, ENT_QUOTES, 'UTF-8') ?>" 
     alt="Capa de <?= htmlspecialchars($livro['titulo'], ENT_QUOTES, 'UTF-8') ?>">

                                            <?php else: ?>
                                                <div class="livro-fallback">
                                                    <span style="font-size: 2.5rem; display: block;">📖</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="livro-info">
                                            <h3><?= htmlspecialchars($livro['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>
                                            <p class="livro-autor">Autor: <?= htmlspecialchars($livro['autor'] ?? 'Desconhecido', ENT_QUOTES, 'UTF-8') ?></p>
                                            
                                            <?php if (!empty($categoriaTexto)): ?>
    <div style="margin-bottom: 8px;">
        <span class="etiqueta">
            <?= htmlspecialchars($categoriaTexto, ENT_QUOTES, 'UTF-8') ?>
        </span>
    </div>
<?php endif; ?>

<div style="margin-bottom: 10px;">
    <span class="etiqueta">
        🔞 <?= htmlspecialchars(
            $livro['classificacao_etaria'] ?? 'Classificação não informada',
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </span>
</div>

                                            <!-- BOTÕES DA FRENTE -->
                                           
<div class="botoes-card">
    <button type="button" class="btn-detalhes" onclick="virarCard(this)">
        🔍 Detalhes
    </button>

    <a href="<?= $linkReserva ?>" class="btn-acao">
        <?= $textoBotaoReserva ?>
    </a>

    <button
        type="button"
        class="btn-favoritar"
        data-id-livro="<?= (int)$livro['id_livro'] ?>"
        aria-label="Adicionar aos favoritos"
        title="Adicionar aos favoritos"
        onclick="alternarFavoritoAcervo(this)">
        ♡
    </button>
</div>

                                        </div>
                                    </div>

                                    <!-- VERSO DO CARD (Sinopse) -->
                                    <div class="card-verso">
                                        <h4>Sinopse</h4>
                                        <div class="sinopse-texto">
                                            "<?= htmlspecialchars($sinopseCompleta, ENT_QUOTES, 'UTF-8') ?>"
                                        </div>

                                        <div class="verso-rodape">
                                            <span class="status-livro <?= $statusClasse ?>">
                                                <?= $temExemplar ? "✅ Disponível ({$qtdDisponivel} ex.)" : '❌ Indisponível' ?>
                                            </span>
                                            <button type="button" class="btn-voltar" onclick="virarCard(this)">
                                                ⬅ Voltar à Capa
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            <?php endif; ?>

        </div>
    </main>
<!-- Janela para ampliar a capa do livro -->
<div id="modalCapa" class="modal-capa" aria-hidden="true">
    <button
        type="button"
        id="fecharModalCapa"
        class="modal-capa-fechar"
        aria-label="Fechar capa ampliada">
        &times;
    </button>

    <img id="imagemCapaAmpliada" src="" alt="Capa ampliada do livro">
</div>
    <script src="script_usuario.js?v=3"></script>
</body>
</html>