    /* ==========================================

       1. DADOS DEMONSTRATIVOS DO ACERVO

       Capas obtidas do serviço Open Library.

       Os livros, estados e reservas são exemplos.

    ========================================== */
document.querySelector('[data-ir="acervo"]').addEventListener('click', function() {
    window.location.href = '../Projeto_biblioteca/tela_acrevo_usuario/tela_acervo_usuario.php';
});


    const livros = [

      {

        id: 1,

        titulo: "Dom Casmurro",

        autor: "Machado de Assis",

        categoria: "Clássico brasileiro",

        disponivel: true,

        capa: "https://covers.openlibrary.org/b/isbn/9788594318241-M.jpg",

        descricao: "Um dos grandes clássicos da literatura brasileira. Bentinho revisita sua história de amor com Capitu, entre memórias, dúvidas e ciúmes."

      },

      {

        id: 2,

        titulo: "O Pequeno Príncipe",

        autor: "Antoine de Saint-Exupéry",

        categoria: "Fábula",

        disponivel: true,

        capa: "https://covers.openlibrary.org/b/isbn/9780156012195-M.jpg",

        descricao: "Uma narrativa poética sobre amizade, afeto e o modo como enxergamos o mundo."

      },

      {

        id: 3,

        titulo: "Orgulho e Preconceito",

        autor: "Jane Austen",

        categoria: "Romance",

        disponivel: false,

        capa: "https://covers.openlibrary.org/b/isbn/9780141439518-M.jpg",

        descricao: "Uma história de relações, primeiras impressões e convenções sociais na Inglaterra do século XIX."

      },

      {

        id: 4,

        titulo: "A Biblioteca da Meia-Noite",

        autor: "Matt Haig",

        categoria: "Ficção",

        disponivel: true,

        capa: "https://covers.openlibrary.org/b/isbn/9780525559474-M.jpg",

        descricao: "Entre possibilidades de vidas diferentes, Nora explora escolhas, arrependimentos e novos caminhos."

      },

      {

        id: 5,

        titulo: "Harry Potter e a Pedra Filosofal",

        autor: "J. K. Rowling",

        categoria: "Fantasia",

        disponivel: true,

        capa: "https://covers.openlibrary.org/b/isbn/9780590353427-M.jpg",

        descricao: "O início da jornada de um jovem que descobre um mundo de magia, amizade e mistérios."

      },

      {

        id: 6,

        titulo: "A Hora da Estrela",

        autor: "Clarice Lispector",

        categoria: "Literatura brasileira",

        disponivel: false,

        capa: "https://covers.openlibrary.org/b/isbn/9788520925687-M.jpg",

        descricao: "A trajetória de Macabéa é narrada em uma obra que provoca reflexões sobre existência e desigualdade."

      },

      {

        id: 7,

        titulo: "O Hobbit",

        autor: "J. R. R. Tolkien",

        categoria: "Fantasia",

        disponivel: true,

        capa: "https://covers.openlibrary.org/b/isbn/9780547928227-M.jpg",

        descricao: "Bilbo Bolseiro deixa sua vida tranquila para participar de uma aventura repleta de perigos e descobertas."

      },

      {

        id: 8,

        titulo: "1984",

        autor: "George Orwell",

        categoria: "Ficção distópica",

        disponivel: true,

        capa: "https://covers.openlibrary.org/b/isbn/9780451524935-M.jpg",

        descricao: "Uma distopia sobre vigilância, manipulação da informação e liberdade individual."

      },

      {

        id: 9,

        titulo: "Capitães da Areia",

        autor: "Jorge Amado",

        categoria: "Literatura brasileira",

        disponivel: true,

        capa: "https://covers.openlibrary.org/b/isbn/9788535911695-M.jpg",

        descricao: "A vida de meninos em situação de abandono nas ruas de Salvador, marcada por amizade e desigualdade."

      },

      {

        id: 10,

        titulo: "A Revolução dos Bichos",

        autor: "George Orwell",

        categoria: "Sátira",

        disponivel: false,

        capa: "https://covers.openlibrary.org/b/isbn/9780451526342-M.jpg",

        descricao: "Uma fábula política sobre poder, manipulação e os rumos de uma revolução."

      },

      {

        id: 11,

        titulo: "Mulherzinhas",

        autor: "Louisa May Alcott",

        categoria: "Romance",

        disponivel: true,

        capa: "https://covers.openlibrary.org/b/isbn/9780147514011-M.jpg",

        descricao: "Acompanhe as irmãs March enquanto amadurecem, enfrentam desafios e descobrem seus próprios sonhos."

      },

      {

        id: 12,

        titulo: "Coraline",

        autor: "Neil Gaiman",

        categoria: "Fantasia",

        disponivel: true,

        capa: "https://covers.openlibrary.org/b/isbn/9780380807345-M.jpg",

        descricao: "Uma garota descobre uma versão inquietante de sua casa e precisa reunir coragem para enfrentar seus segredos."

      }

    ];



    /* ==========================================

       2. ESTADO DA APLICAÇÃO

       Os dados locais são apenas uma simulação.

    ========================================== */



    const CHAVE_FAVORITOS = "entrePaginas_favoritos";

    const CHAVE_EMPRESTIMOS = "entrePaginas_emprestimos";



    function lerArmazenamento(chave, padrao) {

      try {

        const valor = localStorage.getItem(chave);

        return valor ? JSON.parse(valor) : padrao;

      } catch (erro) {

        return padrao;

      }

    }



    function salvarArmazenamento(chave, valor) {

      try {

        localStorage.setItem(chave, JSON.stringify(valor));

      } catch (erro) {

        console.warn("Não foi possível salvar os dados neste navegador.");

      }

    }



    let favoritos = lerArmazenamento(CHAVE_FAVORITOS, []);

    let emprestimos = lerArmazenamento(CHAVE_EMPRESTIMOS, []);

    let filtroAtual = "todos";

    let buscaAtual = "";

    let slideAtual = 0;

    let temporizadorCarrossel;

    let temporizadorNotificacao;



    favoritos = favoritos.filter(id => livros.some(livro => livro.id === id));

    emprestimos = emprestimos.filter(id => livros.some(livro => livro.id === id));



    const $ = seletor => document.querySelector(seletor);

    const $$ = seletor => [...document.querySelectorAll(seletor)];



    /* ==========================================

       3. TROCA DE ABAS SEM RECARREGAR A PÁGINA

    ========================================== */



    function mostrarPagina(nome) {

      const pagina = document.getElementById("pagina-" + nome);

      if (!pagina) return;



      $$(".pagina-conteudo").forEach(elemento => {

        elemento.classList.remove("ativa");

        elemento.setAttribute("aria-hidden", "true");

      });



      pagina.classList.add("ativa");

      pagina.setAttribute("aria-hidden", "false");



      $$(".aba-botao").forEach(botao => {

        const ativo = botao.dataset.pagina === nome;

        botao.classList.toggle("ativo", ativo);

        botao.setAttribute("aria-current", ativo ? "page" : "false");

      });



      $("#navbarAbas").classList.remove("aberto");

      $("#menuMobile").setAttribute("aria-expanded", "false");

      $("#menuMobile").setAttribute("aria-label", "Abrir menu");



      if (nome === "perfil") atualizarPerfil();



      window.scrollTo({ top: 0, behavior: "smooth" });

    }



    $$(".aba-botao").forEach(botao => {

      botao.addEventListener("click", () => {

        mostrarPagina(botao.dataset.pagina);

      });

    });



    $$("[data-ir]").forEach(botao => {

      botao.addEventListener("click", () => {

        mostrarPagina(botao.dataset.ir);

      });

    });



    $("#menuMobile").addEventListener("click", () => {

      const menu = $("#navbarAbas");

      const aberto = menu.classList.toggle("aberto");



      $("#menuMobile").setAttribute("aria-expanded", String(aberto));

      $("#menuMobile").setAttribute(

        "aria-label", aberto ? "Fechar menu" : "Abrir menu"

      );

    });



    /* ==========================================

       4. NOTIFICAÇÕES

    ========================================== */



    function notificar(mensagem) {

      const caixa = $("#notificacao");

      caixa.textContent = mensagem;

      caixa.classList.add("visivel");



      clearTimeout(temporizadorNotificacao);



      temporizadorNotificacao = setTimeout(() => {

        caixa.classList.remove("visivel");

      }, 3200);

    }



    /* ==========================================

       5. CARROSSEL AUTOMÁTICO E MANUAL

    ========================================== */



    const slides = $$(".slide");

    const indicadores = $$(".indicador");

    const carrossel = $(".carrossel-container");



    function mostrarSlide(indice) {

      slideAtual = (indice + slides.length) % slides.length;



      slides.forEach((slide, i) => {

        const ativo = i === slideAtual;

        slide.classList.toggle("ativo", ativo);

        slide.setAttribute("aria-hidden", String(!ativo));

      });



      indicadores.forEach((indicador, i) => {

        const ativo = i === slideAtual;

        indicador.classList.toggle("ativo", ativo);

        indicador.setAttribute("aria-pressed", String(ativo));

      });

    }



    function iniciarCarrossel() {

      clearInterval(temporizadorCarrossel);

      temporizadorCarrossel = setInterval(() => {

        mostrarSlide(slideAtual + 1);

      }, 6000);

    }



    function reiniciarCarrossel() {

      iniciarCarrossel();

    }



    $("#slideAnterior").addEventListener("click", () => {

      mostrarSlide(slideAtual - 1);

      reiniciarCarrossel();

    });



    $("#slideProximo").addEventListener("click", () => {

      mostrarSlide(slideAtual + 1);

      reiniciarCarrossel();

    });



    indicadores.forEach((indicador, indice) => {

      indicador.addEventListener("click", () => {

        mostrarSlide(indice);

        reiniciarCarrossel();

      });

    });



    carrossel.addEventListener("mouseenter", () => {

      clearInterval(temporizadorCarrossel);

    });



    carrossel.addEventListener("mouseleave", iniciarCarrossel);



    carrossel.addEventListener("focusin", () => {

      clearInterval(temporizadorCarrossel);

    });



    carrossel.addEventListener("focusout", iniciarCarrossel);



    iniciarCarrossel();



    /* ==========================================

       6. CRIAÇÃO DOS CARDS DOS LIVROS

    ========================================== */



    function obterLivro(id) {

      return livros.find(livro => livro.id === id);

    }



    function criarCardLivro(livro) {

      const favorito = favoritos.includes(livro.id);

      const reservado = emprestimos.includes(livro.id);



      const card = document.createElement("article");

      card.className = "card-livro";



      const capa = document.createElement("div");

      capa.className = "livro-capa";



      const imagem = document.createElement("img");

      imagem.src = livro.capa;

      imagem.alt = "Capa do livro " + livro.titulo;

      imagem.loading = "lazy";



      const fallback = document.createElement("div");

      fallback.className = "livro-fallback";

      fallback.hidden = true;



      const simbolo = document.createElement("span");

      simbolo.textContent = "📖";



      const tituloFallback = document.createElement("span");

      tituloFallback.textContent = livro.titulo;



      fallback.append(simbolo, tituloFallback);



      imagem.addEventListener("error", () => {

        imagem.hidden = true;

        fallback.hidden = false;

      });



      capa.append(imagem, fallback);



      const info = document.createElement("div");

      info.className = "livro-info";



      const titulo = document.createElement("h3");

      titulo.textContent = livro.titulo;



      const autor = document.createElement("p");

      autor.className = "livro-autor";

      autor.textContent = livro.autor;



      const status = document.createElement("span");

      status.className = "status-livro " +

        (livro.disponivel ? "disponivel" : "emprestado");



      status.textContent = reservado

        ? "Reservado por você"

        : livro.disponivel ? "Disponível" : "Emprestado";



      const acoes = document.createElement("div");

      acoes.className = "livro-acoes";



      const botaoDetalhes = document.createElement("button");

      botaoDetalhes.className = "botao";

      botaoDetalhes.textContent = "Ver detalhes";

      botaoDetalhes.addEventListener("click", () => abrirDetalhes(livro.id));



      const botaoFavorito = document.createElement("button");

      botaoFavorito.className = "favorito-botao" +

        (favorito ? " marcado" : "");

      botaoFavorito.textContent = favorito ? "♥" : "♡";

      botaoFavorito.setAttribute(

        "aria-label",

        favorito ? "Remover dos favoritos" : "Adicionar aos favoritos"

      );

      botaoFavorito.setAttribute("aria-pressed", String(favorito));



      botaoFavorito.addEventListener("click", () => alternarFavorito(livro.id));



      acoes.append(botaoDetalhes, botaoFavorito);

      info.append(titulo, autor, status, acoes);

      card.append(capa, info);



      return card;

    }



    function preencherGrade(elemento, lista) {

      elemento.replaceChildren();

      lista.forEach(livro => elemento.appendChild(criarCardLivro(livro)));

    }



    function atualizarRecomendados() {

      preencherGrade($("#livrosRecomendados"), livros.slice(0, 4));

    }



    /* ==========================================

       7. PESQUISA E FILTROS DO ACERVO

    ========================================== */



    function obterLivrosFiltrados() {

      return livros.filter(livro => {

        const texto = (livro.titulo + " " + livro.autor + " " +

          livro.categoria).toLocaleLowerCase("pt-BR");



        const combinaBusca = texto.includes(

          buscaAtual.toLocaleLowerCase("pt-BR")

        );



        let combinaFiltro = true;



        if (filtroAtual === "disponivel") {

          combinaFiltro = livro.disponivel && !emprestimos.includes(livro.id);

        } else if (filtroAtual === "emprestado") {

          combinaFiltro = !livro.disponivel || emprestimos.includes(livro.id);

        } else if (filtroAtual === "favoritos") {

          combinaFiltro = favoritos.includes(livro.id);

        }



        return combinaBusca && combinaFiltro;

      });

    }



    function atualizarAcervo() {

      const resultados = obterLivrosFiltrados();



      preencherGrade($("#gradeAcervo"), resultados);



      $("#resultadoContagem").textContent =

        resultados.length + (resultados.length === 1

          ? " livro encontrado"

          : " livros encontrados");



      $("#mensagemVazia").hidden = resultados.length !== 0;

      $("#gradeAcervo").hidden = resultados.length === 0;

    }



    $("#formBusca").addEventListener("submit", evento => {

      evento.preventDefault();

      buscaAtual = $("#campoBusca").value.trim();

      atualizarAcervo();

    });



    $("#campoBusca").addEventListener("input", evento => {

      buscaAtual = evento.target.value.trim();

      atualizarAcervo();

    });



    $$(".filtro-botao").forEach(botao => {

      botao.addEventListener("click", () => {

        filtroAtual = botao.dataset.filtro;



        $$(".filtro-botao").forEach(item => {

          item.classList.toggle("ativo", item === botao);

        });



        atualizarAcervo();

      });

    });



    /* ==========================================

       8. FAVORITOS

    ========================================== */



    function alternarFavorito(id) {

      if (favoritos.includes(id)) {

        favoritos = favoritos.filter(item => item !== id);

        notificar("Livro removido dos favoritos.");

      } else {

        favoritos.push(id);

        notificar("Livro adicionado aos favoritos! ♥");

      }



      salvarArmazenamento(CHAVE_FAVORITOS, favoritos);

      atualizarTudo();

    }



    /* ==========================================

       9. MODAL DE DETALHES E RESERVAS

    ========================================== */



    let livroModalAtual = null;

    let focoAnterior = null;



    function abrirModal() {

      focoAnterior = document.activeElement;

      $("#modalFundo").classList.add("aberto");

      $("#fecharModal").focus();

    }



    function fecharModal() {

      $("#modalFundo").classList.remove("aberto");

      livroModalAtual = null;



      if (focoAnterior && typeof focoAnterior.focus === "function") {

        focoAnterior.focus();

      }

    }



    function abrirDetalhes(id) {

      const livro = obterLivro(id);

      if (!livro) return;



      livroModalAtual = id;



      $("#modalTitulo").textContent = livro.titulo;



      const corpo = $("#modalCorpo");

      corpo.replaceChildren();



      const autor = document.createElement("p");

      autor.textContent = "Autor(a): " + livro.autor;



      const categoria = document.createElement("p");

      categoria.textContent = "Categoria: " + livro.categoria;



      const descricao = document.createElement("p");

      descricao.textContent = livro.descricao;



      const status = document.createElement("p");

      status.textContent = emprestimos.includes(id)

        ? "Você já reservou este livro."

        : livro.disponivel

          ? "Este livro está disponível para reserva."

          : "Este livro está emprestado no momento.";



      corpo.append(autor, categoria, descricao, status);



      const acoes = $("#modalAcoes");

      acoes.replaceChildren();



      const botaoFechar = document.createElement("button");

      botaoFechar.className = "botao botao-secundario";

      botaoFechar.textContent = "Fechar";

      botaoFechar.addEventListener("click", fecharModal);

      acoes.appendChild(botaoFechar);



      if (livro.disponivel && !emprestimos.includes(id)) {

        const botaoReservar = document.createElement("button");

        botaoReservar.className = "botao";

        botaoReservar.textContent = "Reservar livro";

        botaoReservar.addEventListener("click", () => reservarLivro(id));

        acoes.appendChild(botaoReservar);

      } else if (emprestimos.includes(id)) {

        const botaoCancelar = document.createElement("button");

        botaoCancelar.className = "botao";

        botaoCancelar.textContent = "Cancelar reserva";

        botaoCancelar.addEventListener("click", () => cancelarReserva(id));

        acoes.appendChild(botaoCancelar);

      }



      abrirModal();

    }



    function reservarLivro(id) {

      const livro = obterLivro(id);

      if (!livro) return;



      if (!livro.disponivel) {

        notificar("Este livro está emprestado no momento.");

        fecharModal();

        return;

      }



      if (emprestimos.includes(id)) {

        notificar("Você já reservou este livro.");

        fecharModal();

        return;

      }



      emprestimos.push(id);

      salvarArmazenamento(CHAVE_EMPRESTIMOS, emprestimos);



      fecharModal();

      atualizarTudo();

      notificar("Reserva simulada realizada: " + livro.titulo);

    }



    function cancelarReserva(id) {

      emprestimos = emprestimos.filter(item => item !== id);

      salvarArmazenamento(CHAVE_EMPRESTIMOS, emprestimos);



      fecharModal();

      atualizarTudo();

      notificar("Reserva simulada cancelada.");

    }



    $("#fecharModal").addEventListener("click", fecharModal);



    $("#modalFundo").addEventListener("click", evento => {

      if (evento.target === $("#modalFundo")) fecharModal();

    });



    document.addEventListener("keydown", evento => {

      if (evento.key === "Escape") fecharModal();

    });



    /* ==========================================

       10. PERFIL DO LEITOR

    ========================================== */



    function atualizarPerfil() {

      $("#totalEmprestimos").textContent = emprestimos.length;

      $("#totalFavoritos").textContent = favoritos.length;



      const listaEmprestimos = $("#listaEmprestimos");

      listaEmprestimos.replaceChildren();



      if (emprestimos.length === 0) {

        const vazio = document.createElement("div");

        vazio.className = "perfil-vazio";

        vazio.textContent =

          "Você ainda não tem reservas. Explore o acervo para escolher sua próxima leitura.";

        listaEmprestimos.appendChild(vazio);

      } else {

        emprestimos.forEach(id => {

          const livro = obterLivro(id);

          if (!livro) return;



          const item = document.createElement("article");

          item.className = "emprestimo-item";



          const texto = document.createElement("div");

          const titulo = document.createElement("h3");

          titulo.textContent = livro.titulo;



          const autor = document.createElement("p");

          autor.textContent = livro.autor + " · Reserva demonstrativa";



          const status = document.createElement("p");

          status.textContent = "Reserva registrada neste navegador.";



          texto.append(titulo, autor, status);



          const botao = document.createElement("button");

          botao.className = "botao botao-secundario";

          botao.textContent = "Cancelar reserva";

          botao.addEventListener("click", () => cancelarReserva(id));



          item.append(texto, botao);

          listaEmprestimos.appendChild(item);

        });

      }



      const livrosFavoritos = favoritos.map(obterLivro).filter(Boolean);



      preencherGrade($("#gradeFavoritos"), livrosFavoritos);

      $("#gradeFavoritos").hidden = livrosFavoritos.length === 0;

      $("#favoritosVazios").hidden = livrosFavoritos.length !== 0;

    }



    /* ==========================================

       11. ATUALIZAÇÃO GERAL DA INTERFACE

    ========================================== */



    function atualizarTudo() {

      atualizarRecomendados();

      atualizarAcervo();

      atualizarPerfil();

    }



    /* ==========================================

       12. INICIALIZAÇÃO

    ========================================== */



    $("#anoAtual").textContent = new Date().getFullYear();



    mostrarSlide(0);

    atualizarTudo();



  