
<?php
require_once __DIR__ . '/../includes/conexao.php';

$livrosDoBanco = [];
$categoriasDoBanco = [];
$erroConsulta = '';

try {
    // Busca todas as categorias cadastradas.
    $stmtCategorias = $conexao->query("
        SELECT id_categoria, nome
        FROM categorias
        ORDER BY id_categoria ASC
    ");

    $categoriasDoBanco = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

    // Busca os livros e suas respectivas categorias.
    $stmtLivros = $conexao->query("
        SELECT
            l.id_livro,
            l.titulo,
            l.autor,
            l.id_categoria,
            c.nome AS categoria
        FROM livros l
        LEFT JOIN categorias c
            ON l.id_categoria = c.id_categoria
        ORDER BY l.id_categoria ASC, l.id_livro ASC
    ");

    $resultados = $stmtLivros->fetchAll(PDO::FETCH_ASSOC);

    // Agrupa os livros por categoria.
    $posicoes = [];

    foreach ($resultados as $row) {
        $idCategoria = (int) ($row['id_categoria'] ?? 0);
        $idLivro = (int) $row['id_livro'];

        if (!isset($posicoes[$idCategoria])) {
            $posicoes[$idCategoria] = 0;
        }

        $posicoes[$idCategoria]++;

        // Cinco livros por prateleira, numeradas a partir de 1.
        $numeroPrateleira = (int) ceil(
            $posicoes[$idCategoria] / 5
        );

        $posicaoNaPrateleira = (($posicoes[$idCategoria] - 1) % 5) + 1;

        $cores = [
            '#142B4A',
            '#24466D',
            '#0D1D33',
            '#73552A',
            '#345B50'
        ];

        $livrosDoBanco[] = [
            'id_livro' => $idLivro,
            'titulo' => $row['titulo'] ?? '',
            'autor' => $row['autor'] ?? '',
            'id_categoria' => $idCategoria,
            'categoria' => $row['categoria'] ?? 'Sem categoria',
            'prateleira' => $numeroPrateleira,
            'posicao' => $posicaoNaPrateleira,
            'cor' => $cores[$idLivro % count($cores)]
        ];
    }
} catch (PDOException $e) {
    $erroConsulta = 'Não foi possível carregar o acervo. Verifique a conexão e as tabelas do banco de dados.';
}

$nomeBiblioteca = 'Biblioteca Municipal de Arujá';
$patrona = 'Professora Alda Martins Soncini';
$anoAtual = date('Y');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mapa Virtual do Acervo | <?php echo htmlspecialchars($nomeBiblioteca); ?></title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="style.css?v=5">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            navy: '#142B4A',
                            dark: '#0D1D33',
                            medium: '#24466D',
                            light: '#EDF2F8',
                            gold: '#C5A04F',
                            goldLight: '#E7D5A1',
                            bg: '#F4F6FA',
                            card: '#FFFFFF',
                            text: '#263449',
                            subtext: '#68768A',
                            border: '#DCE3ED'
                        }
                    },
                    fontFamily: {
                        cinzel: ['Cinzel', 'serif'],
                        sans: ['Plus Jakarta Sans', 'sans-serif']
                    }
                }
            }
        };
    </script>
</head>

<body class="min-h-screen flex flex-col">

<div class="flex-grow max-w-[1600px] w-full mx-auto p-3 md:p-6">

    <header class="bg-brand-navy rounded-2xl shadow-xl border border-brand-medium p-6 mb-6 text-white">
        <div class="flex flex-col md:flex-row items-center justify-between gap-5">

            <div class="flex items-center gap-4 text-center md:text-left">
                <div class="w-16 h-16 rounded-full bg-brand-dark border-2 border-brand-gold flex items-center justify-center">
                    <i class="fa-solid fa-landmark text-3xl text-brand-gold"></i>
                </div>

                <div>
                    <span class="text-xs uppercase tracking-widest text-brand-goldLight">
                        Mapa virtual do acervo
                    </span>

                    <h1 class="font-cinzel text-2xl md:text-3xl font-bold mt-1">
                        <?php echo htmlspecialchars($nomeBiblioteca); ?>
                    </h1>

                    <p class="font-cinzel text-brand-goldLight italic">
                        <?php echo htmlspecialchars($patrona); ?>
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap justify-center gap-3 text-xs">
                <span class="bg-brand-dark p-3 rounded-lg">
                    <i class="fa-solid fa-book-open text-brand-gold mr-1"></i>
                    <?php echo count($livrosDoBanco); ?> livros
                </span>

                <span class="bg-brand-dark p-3 rounded-lg">
                    <i class="fa-solid fa-layer-group text-brand-gold mr-1"></i>
                    <?php echo count($categoriasDoBanco); ?> categorias
                </span>
            </div>
        </div>
    </header>

    <?php if ($erroConsulta !== ''): ?>
        <div class="bg-red-100 text-red-800 rounded-xl p-4 mb-6">
            <?php echo htmlspecialchars($erroConsulta); ?>
        </div>
    <?php endif; ?>

    <section class="bg-white rounded-xl shadow-md border border-brand-border p-4 mb-6">
        <label for="searchInput" class="block text-sm font-semibold mb-2">
            Pesquisar no acervo
        </label>

        <div class="flex gap-2">
            <input
                type="search"
                id="searchInput"
                placeholder="Título, autor ou número do livro..."
                class="w-full px-4 py-3 bg-brand-bg border border-brand-border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-gold"
            >
            <button
                type="button"
                id="clearSearchBtn"
                class="px-4 rounded-lg bg-brand-navy text-white"
                aria-label="Limpar pesquisa"
                title="Limpar pesquisa"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>


        <p class="text-xs text-brand-subtext mt-2">
            Selecione um livro para consultar sua categoria e localização virtual.
        </p>
    </section>

      <a href="../tela_principal2/index.php" class="botao-voltar-mapa">
    ← Voltar
</a>

    <main>
        <div id="bookcasesGrid"
             class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        </div>

        <p id="emptyMessage" class="hidden text-center text-brand-subtext p-8">
            Nenhum livro corresponde à pesquisa.
        </p>
    </main>

    <footer class="mt-10 py-5 border-t border-brand-border text-center text-xs text-brand-subtext">
        © <?php echo $anoAtual; ?>
        <strong><?php echo htmlspecialchars($nomeBiblioteca); ?></strong>
        — <?php echo htmlspecialchars($patrona); ?>.
        Mapa virtual para orientação no acervo.
    </footer>
</div>

<!-- Janela de localização do livro -->
<div id="bookModal"
     class="fixed inset-0 z-50 bg-brand-dark/80 flex items-center justify-center p-4 opacity-0 pointer-events-none transition-opacity">

    <section id="modalContainer"
             class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 relative scale-95 transition-transform">

        <button type="button"
                id="closeModalBtn"
                class="absolute top-4 right-4 text-xl text-brand-subtext"
                aria-label="Fechar">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <span class="text-xs uppercase tracking-widest text-brand-gold">
            Localização do livro
        </span>

        <h2 id="modalTitle"
            class="font-cinzel text-2xl font-bold text-brand-navy mt-3 pr-6">
        </h2>

        <p id="modalAuthor" class="text-sm text-brand-subtext mt-2"></p>

        <div class="bg-brand-bg rounded-xl p-4 mt-5 space-y-3">
            <p>
                <strong>Identificação:</strong>
                <span id="modalNumericCode"></span>
            </p>

            <p>
                <strong>Categoria:</strong>
                <span id="modalCategory"></span>
            </p>

            <p>
                <strong>Estante:</strong>
                <span id="modalShelf"></span>
            </p>

            <p>
                <strong>Prateleira:</strong>
                <span id="modalTier"></span>
            </p>

            <p>
                <strong>Posição:</strong>
                <span id="modalPosition"></span>
            </p>
        </div>

        <p class="text-xs text-brand-subtext mt-4">
            A localização é virtual e serve como referência para encontrar o livro.
        </p>

        <button type="button"
                id="closeModalFooterBtn"
                class="w-full mt-5 bg-brand-navy text-white rounded-lg py-3">
            Fechar
        </button>
    </section>
</div>

<script>
    const libraryData = <?php
        echo json_encode(
            $livrosDoBanco,
            JSON_UNESCAPED_UNICODE |
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_QUOT |
            JSON_HEX_AMP
        );
    ?>;

    const categoryData = <?php
        echo json_encode(
            $categoriasDoBanco,
            JSON_UNESCAPED_UNICODE |
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_QUOT |
            JSON_HEX_AMP
        );
    ?>;
</script>

<script src="script.js?v=3"></script>

</body>
</html>
