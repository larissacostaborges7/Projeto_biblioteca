
<?php
// =============================================
// TELA INICIAL — BIBLIOTECA MUNICIPAL DE ARUJÁ
// =============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexão com o banco de dados
require_once __DIR__ . '/../includes/conexao.php';

// Verifica quem está autenticado
$usuarioLogado = isset($_SESSION['id_usuario']);
$funcionarioLogado = isset($_SESSION['id_funcionario']);


// Define o nome para exibição
$nomeLogado = '';

if ($usuarioLogado) {
    $nomeLogado = $_SESSION['nome_usuario'] ?? '';
} elseif ($funcionarioLogado) {
    $nomeLogado = $_SESSION['nome_funcionario'] ?? '';
}
$estaLogado = $usuarioLogado || $funcionarioLogado;

$totalNotificacoesNaoLidas = 0;

if ($usuarioLogado) {
    try {
        $stmtNotificacoes = $conexao->prepare("
            SELECT COUNT(*)
            FROM notificacoes
            WHERE id_usuario = :id_usuario
              AND lida = 0
        ");

        $stmtNotificacoes->execute([
            'id_usuario' => $_SESSION['id_usuario']
        ]);

        $totalNotificacoesNaoLidas = (int) $stmtNotificacoes->fetchColumn();

    } catch (PDOException $e) {
        error_log('Erro ao contar notificações: ' . $e->getMessage());
    }
}
?>

        
      <button class="menu-mobile" id="menuMobile"
        aria-label="Abrir menu" aria-expanded="false"
        aria-controls="navbarAbas">☰</button>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#244e5b">
  <meta name="description" content="Conheça a Biblioteca Municipal Professora Alda Martins Soncini, em Arujá, e explore seu acervo.">
  <title>Biblioteca Municipal de Arujá</title>
  <link rel="stylesheet" href="style.css?v=5">
  
</head>
<body>

  <header class="navbar-superior">
    <div class="container navbar-interno">
      <a href="#inicio" class="logo" aria-label="Biblioteca Municipal de Arujá - Tela Inicial">
        <span class="logo-icone" aria-hidden="true">📚</span>
        <span>
          <span class="logo-nome">Biblioteca Municipal de Arujá</span>
          <span class="logo-descricao">Professora Alda Martins Soncini</span>
        </span>
      </a>

      <button class="menu-mobile" id="menuMobile"
        aria-label="Abrir menu" aria-expanded="false"
        aria-controls="navbarAbas">☰</button>
      

<a href="../tela_notificacao/index.php"
   class="botao-notificacoes"
   aria-label="Abrir notificações"
   title="Notificações não lidas: <?= $totalNotificacoesNaoLidas ?>">

    🔔

    <?php if ($totalNotificacoesNaoLidas > 0): ?>
        <span class="notificacao-contador">
            <?= $totalNotificacoesNaoLidas > 99 ? '99+' : $totalNotificacoesNaoLidas ?>
        </span>
    <?php endif; ?>

</a>

<a href="../mapa_virtual/index.php"
   class="botao-atalho-mapa"
   title="Abrir Mapa Virtual"
   aria-label="Abrir Mapa Virtual">
    <span aria-hidden="true">🗺️</span>
</a>



<div class="painel-notificacoes" id="painelNotificacoes" hidden>
  <div class="painel-notificacoes-cabecalho">
    <h2>Notificações</h2>
    <button type="button" id="fecharNotificacoes"
            aria-label="Fechar notificações">×</button>
  </div>

  <div id="listaNotificacoes" aria-live="polite">
    <p>Você não tem notificações novas.</p>
  </div>
</div>
    <button class="aba-botao ativo" data-pagina="inicio" data-i18n="inicio">🏠 Tela Inicial</button>

    <a href="../tela_acervo_usuario/tela_acervo_usuario.php"
       class="aba-botao"
       style="text-decoration:none; display:inline-flex; align-items:center;"
       data-i18n="acervo">📚 Acervo</a>

    <a href="../tela_emprestimo_usuario/index.php"
       class="aba-botao"
       style="text-decoration:none; display:inline-flex; align-items:center;"
       data-i18n="emprestimos">📖 Empréstimos</a>

    <button class="aba-botao" data-pagina="eventos" data-i18n="eventos">📅 Eventos</button>

    <button class="aba-botao" data-pagina="sobre" data-i18n="sobre">📖 Sobre Nós</button>

    <button class="aba-botao" data-pagina="perfil" data-i18n="perfil">👤 Meu Perfil</button>

    <a href="../tela_cadastro/index.php"
       class="aba-botao"
       style="text-decoration:none; display:inline-flex; align-items:center;"
       data-i18n="login">✍️ Login/Cadastre-se</a>
</nav>
    </div>
  </header>

  <main>
    <section class="pagina-conteudo ativa" id="pagina-inicio" aria-label="Tela Inicial">
      <div class="container">
        <div class="carrossel-container" aria-roledescription="carrossel"
          aria-label="Destaques da biblioteca">
          <div class="carrossel-slides" id="carrosselSlides">

            <article class="slide ativo" aria-label="Slide 1 de 4">
              <div class="slide-texto">
                <span class="etiqueta">UM UNIVERSO DE POSSIBILIDADES</span>
                <h2>Todo livro abre uma nova janela.</h2>
                <p>
                  Encontre histórias que inspiram, ideias que transformam
                  e páginas que fazem você viajar sem sair do lugar.
                </p>
                <button class="botao" data-ir="acervo">Explorar o acervo →</button>
              </div>
              <div class="slide-ilustracao" aria-hidden="true">📖</div>
            </article>

            <article class="slide" aria-label="Slide 2 de 4">
              <div class="slide-texto">
                <span class="etiqueta">AGENDA CULTURAL</span>
                <h2>Encontros que nascem da leitura.</h2>
                <p>
                  Descubra rodas de leitura, conversas literárias e
                  atividades para compartilhar o prazer de ler.
                </p>
                <button class="botao" data-ir="sobre">Conheça a biblioteca →</button>
              </div>
              <div class="slide-ilustracao" aria-hidden="true">☕</div>
            </article>

            <article class="slide" aria-label="Slide 3 de 4">
              <div class="slide-texto">
                <span class="etiqueta">NOVIDADES DO ACERVO</span>
                <h2>Sua próxima história favorita espera por você.</h2>
                <p>
                  Explore clássicos, romances, fantasia e descobertas
                  literárias para todos os momentos.
                </p>
                <button class="botao" data-ir="acervo">Descobrir livros →</button>
              </div>
              <div class="slide-ilustracao" aria-hidden="true">✨</div>
            </article>

            <article class="slide" aria-label="Slide 4 de 4">
              <div class="slide-texto">
                <span class="etiqueta">EVENTOS</span>
                <h2>Descubra oficinas, encontros literários e experiências culturais na nossa biblioteca.</h2>
                <p>
                  Participe de atividades que conectam pessoas, ideias e histórias.
                </p>
                <button class="botao" data-ir="sobre">Ver eventos →</button>
              </div>
              <div class="slide-ilustracao" aria-hidden="true">☕</div>
            </article>

            
<article class="slide" aria-label="Slide 5 de 5">
  <div class="slide-texto">
    <span class="etiqueta">NOVIDADE NA BIBLIOTECA</span>
    <h2>Encontre seu próximo livro com o Mapa Virtual!</h2>
    <p>
      Descubra em qual estante, prateleira e posição cada livro está
      organizado. Explore nosso acervo de um jeito mais fácil!
    </p>
    <a class="botao" href="../mapa_virtual/index.php">
      Explorar o mapa virtual →
    </a>
  </div>
  <div class="slide-ilustracao" aria-hidden="true">🗺️📚</div>
</article>

          </div>

          <button class="carrossel-seta carrossel-anterior"
            id="slideAnterior" aria-label="Slide anterior">‹</button>
          <button class="carrossel-seta carrossel-proximo"
            id="slideProximo" aria-label="Próximo slide">›</button>

          <div class="carrossel-indicadores" id="indicadores"
            aria-label="Selecionar slide">
            <button class="indicador ativo" aria-label="Ir para slide 1"
              aria-pressed="true"></button>
            <button class="indicador" aria-label="Ir para slide 2"
              aria-pressed="false"></button>
            <button class="indicador" aria-label="Ir para slide 3"
              aria-pressed="false"></button>
            <button class="indicador" aria-label="Ir para slide 4"
              aria-pressed="false"></button>
            <button class="indicador" aria-label="Ir para slide 5"
  aria-pressed="false"></button>

          </div>
        </div>
      </div>

      <section class="secao boas-vindas">
        <div class="container">
          <div class="boas-vindas-topo">
            <div>
              <span class="etiqueta">SEJA BEM-VINDO</span>
              <h1 class="titulo-secao">A leitura aproxima pessoas e transforma histórias.</h1>
              <p>
                A Biblioteca Municipal de Arujá é um espaço público de leitura,
                cultura, imaginação e conhecimento. Sinta-se em casa entre nossas páginas.
              </p>
            </div>
            <button
  class="botao"
  type="button"
  onclick="window.location.href='../tela_acervo_usuario/tela_acervo_usuario.php'">
  Ver todo o acervo →
</button>

<a class="botao" href="../mapa_virtual/index.php">
  🗺️ Explorar Mapa Virtual →
</a>

          </div>

          <h2 class="titulo-secao" style="font-size:1.65rem;margin-bottom:25px">
            ✦ Recomendações da semana
          </h2>
          <div class="grade-livros" id="livrosRecomendados"></div>
        </div>
      </section>
    </section>

    <section class="pagina-conteudo" id="pagina-acervo" aria-label="Acervo">
      <div class="acervo-cabecalho">
        <div class="container">
          <span class="etiqueta">ENCONTRE SUA PRÓXIMA LEITURA</span>
          <h1 class="titulo-secao">Nosso Acervo</h1>
          <p>Histórias, conhecimentos e universos inteiros esperando por você.</p>

          <form class="busca-container" id="formBusca" role="search">
            <input class="busca-input" id="campoBusca" type="search"
              placeholder="Buscar por título ou autor..."
              aria-label="Buscar livros por título ou autor">
            <button class="botao" type="submit">⌕ Buscar</button>
          </form>
        </div>
      </div>

      <section class="secao">
        <div class="container">
          <div class="filtros-acervo" aria-label="Filtrar livros">
            <button class="filtro-botao ativo" data-filtro="todos">Todos os livros</button>
            <button class="filtro-botao" data-filtro="disponivel">Disponíveis</button>
            <button class="filtro-botao" data-filtro="emprestado">Emprestados</button>
            <button class="filtro-botao" data-filtro="favoritos">♥ Favoritos</button>
          </div>

          <p class="resultado-contagem" id="resultadoContagem" aria-live="polite"></p>
          <div class="grade-livros" id="gradeAcervo"></div>
          
          <section class="acervo-lista-html" aria-labelledby="titulosAcervo">
            <h2 id="titulosAcervo" class="titulo-secao">Títulos do acervo</h2>
            <p>Confira alguns dos títulos indicados para compor o catálogo da biblioteca.</p>

           </section>
</div>
</section>
</section>
    <section class="pagina-conteudo" id="pagina-eventos" aria-label="Eventos">
      <section class="secao">
        <div class="container">
          <div class="sobre-intro">
            <div class="sobre-texto">
              <h1 class="titulo-secao">Venha conhecer e participar dos nossos eventos.</h1>
              <p>
                Nossa biblioteca promove eventos abertos ao público com o objetivo de incentivar a leitura, despertar a criatividade e aproximar a comunidade por meio da cultura e do conhecimento. Queremos oferecer momentos de convivência, troca de ideias e novas descobertas, mostrando que existe um mundo de possibilidades além das telas.
              </p>
            </div>

            <article class="eventos-card">
  <span class="etiqueta">EVENTOS DA BIBLIOTECA</span>
  <h2 class="titulo-secao" style="font-size:1.5rem">
    Próximos encontros e atividades
  </h2>

  <div id="listaEventos" aria-live="polite">
    <p>Carregando eventos...</p>
  </div>

  <p>Telefone: (11) 4653-4042</p>
  <p>Rua São Paulo, nº 110 — Jardim Planalto, Arujá – SP</p>
</article>
          </div>
        </div>
      </section>
    </section>

    <section class="pagina-conteudo" id="pagina-sobre" aria-label="Sobre Nós">
      <section class="secao">
        <div class="container">
          <div class="sobre-intro">
            <div class="sobre-texto">
              <span class="etiqueta">NOSSA ESSÊNCIA</span>
              <h1 class="titulo-secao">Um lugar onde as histórias encontram pessoas.</h1>

              <p>
                📚 Muito mais que livros: um mundo de histórias!
              </p>

              <p>
                Desde 1960, a Biblioteca Municipal Professora Alda Martins Soncini faz parte da história de Arujá. Entre páginas, sonhos e descobertas, ela se tornou um espaço onde o conhecimento atravessa gerações e a imaginação não conhece limites.
              </p>

              <p>
                Imagine quantas histórias já passaram por suas estantes e quantos sonhos começaram com um simples livro. Mais do que um lugar de leitura, a biblioteca é um convite para viajar sem sair do lugar, descobrir novos mundos e escrever os próximos capítulos da sua própria história.
              </p>

              <p>
                Entre, explore e descubra: sua próxima grande aventura pode estar esperando por você em uma página.✨
              </p>

              <p>
                📍 Onde nos encontrar?

                Rua São Paulo, nº 110 — Jardim Planalto, Arujá – SP.

                🕒 Horário de funcionamento

                De segunda a sexta-feira, das 8h às 17h.

                📞 Informações: (11) 4653-4042
              </p>

              <p>
                💌 Venha descobrir quantas histórias cabem em um só lugar. A próxima pode ser a sua!
              </p>

              <button class="botao" data-ir="acervo">Conheça nosso acervo</button>
            </div>

            <div class="sobre-ilustracao" role="img"
              aria-label="Ilustração de livros e café">📚☕</div>
          </div>

          <div class="valores-grade">
            <article class="valor-card">
              <div class="valor-icone" aria-hidden="true">🎯</div>
              <h3>Nossa missão</h3>
              <p>
                Democratizar o acesso à leitura, estimular o pensamento
                crítico e tornar o conhecimento acessível a todos.
              </p>
            </article>

            <article class="valor-card">
              <div class="valor-icone" aria-hidden="true">🌿</div>
              <h3>Nossa visão</h3>
              <p>
                Ser um espaço de referência em acolhimento, cultura e
                incentivo à formação de novos leitores.
              </p>
            </article>

            <article class="valor-card">
              <div class="valor-icone" aria-hidden="true">🤍</div>
              <h3>Nossos valores</h3>
              <p>
                Respeito, inclusão, curiosidade, criatividade, diversidade
                cultural e amor pelo conhecimento.
              </p>
            </article>
          </div>

          <div class="contato-grade">
            <article class="contato-card">
              <span class="etiqueta">VISITE-NOS</span>
              <h2 class="titulo-secao" style="font-size:1.5rem">Horário de funcionamento</h2>
              <p>Segunda a sexta: 8h às 17h</p>
            </article>

            <article class="contato-card">
              <span class="etiqueta">FALE CONOSCO</span>
              <h2 class="titulo-secao" style="font-size:1.5rem">Vamos conversar?</h2>
              <p>☎ (11) 4653-4042</p>
              <p>📍 Rua São Paulo, nº 110 — Jardim Planalto, Arujá – SP</p>
            </article>
          </div>
        </div>
      </section>
    </section>

    <section class="pagina-conteudo" id="pagina-perfil" aria-label="Perfil">
      <div class="perfil-cabecalho">
        <div class="container">
          <div class="perfil-identidade">
            <div class="avatar" aria-hidden="true">L</div>
            <div>
              <span class="etiqueta">ESPAÇO DO LEITOR</span>
              <h1>Olá, Leitor(a)! ✨</h1>
              <p>Seu cantinho literário pessoal.</p>
            </div>
          </div>

          <div class="perfil-resumo">
            <div class="estatistica-card">
              <span>Empréstimos ativos</span>
              <strong id="totalEmprestimos">0</strong>
            </div>
            <div class="estatistica-card">
              <span>Livros favoritos</span>
              <strong id="totalFavoritos">0</strong>
            </div>
            <div class="estatistica-card">
              <span>Leituras concluídas</span>
              <strong id="totalLeituras">0</strong>
            </div>
          </div>
        </div>
      </div>

      <section class="secao">
        <div class="container">
          <div class="perfil-bloco">
            <h2>📖 Meus empréstimos</h2>
            <div id="listaEmprestimos"></div>
          </div>

          <div class="perfil-bloco">
            <h2>♥ Minha lista de favoritos</h2>
            <p class="subtitulo-secao">
              Livros que você guardou para ler depois.
            </p>
            <div class="grade-livros" id="gradeFavoritos"></div>
            <div class="perfil-vazio" id="favoritosVazios" hidden>
              Você ainda não adicionou livros aos favoritos.
              Explore o acervo e clique no coração para salvar suas escolhas.
            </div>
          </div>

          <div class="perfil-bloco">
            <h2>ℹ Sobre esta conta</h2>
            <div class="perfil-vazio">
              Esta é uma área de leitor demonstrativa. As reservas e os
              favoritos são guardados neste navegador, mas não existe
              autenticação nem cadastro real de usuário.
            </div>
          </div>
        </div>
      </section>
    </section>
  </main>

  <footer class="rodape">
    <div class="container">
      <div class="rodape-superior">
        <div>
          <a href="#inicio" class="logo" data-ir="inicio">
            <span class="logo-icone" aria-hidden="true">📚</span>
            <span>
              <span class="logo-nome">Biblioteca Municipal de Arujá</span>
              <span class="logo-descricao">Professora Alda Martins Soncini</span>
            </span>
          </a>

          <p>
            Um convite para desacelerar, descobrir novas ideias e
            encontrar um pouco de você em cada história.
          </p>
        </div>

        <nav class="rodape-links" aria-label="Links do rodapé">
          <button data-ir="inicio">Início</button>
          <button data-ir="acervo">Acervo</button>
          <button data-ir="eventos">Eventos</button>
          <button data-ir="sobre">Sobre Nós</button>
          <button data-ir="perfil">Perfil</button>
        </nav>
      </div>

      <div class="rodape-final">
        © <span id="anoAtual"></span> Biblioteca Municipal Professora Alda Martins Soncini.
        Cultura, leitura e conhecimento para toda a comunidade.
      </div>
    </div>
  </footer>

  <div class="notificacao" id="notificacao" role="status"
    aria-live="polite"></div>

  <div class="modal-fundo" id="modalFundo"
    role="dialog" aria-modal="true" aria-labelledby="modalTitulo">
    <div class="modal-conteudo">
      <div class="modal-topo">
        <h2 id="modalTitulo">Detalhes do livro</h2>
        <button class="modal-fechar" id="fecharModal"
          aria-label="Fechar janela">×</button>
      </div>
      <div id="modalCorpo"></div>
      <div class="modal-acoes" id="modalAcoes"></div>
    </div>
  </div>

  <script src="script.js?v=8"></script>
</body>
</html>