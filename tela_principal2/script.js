
let livros = [];
async function carregarLivros() {
    try {
        const resposta = await fetch("buscar_livros.php");
        const dados = await resposta.json();

        if (!dados.sucesso) {
            console.error("Erro ao carregar os livros:", dados.mensagem);
            return;
        }

        livros = dados.livros;
        atualizarTudo();

    } catch (erro) {
        console.error("Erro ao buscar os livros:", erro);
    }
}
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



    $$(".aba-botao[data-pagina]").forEach(botao => {
  botao.addEventListener("click", () => {
    const destino = botao.dataset.pagina;
    mostrarPagina(destino);
  });
});



    $$("[data-ir]").forEach(botao => {
  botao.addEventListener("click", () => {
    const destino = botao.dataset.ir;

    if (destino === "acervo") {
      window.location.href =
        "../tela_acervo_usuario/tela_acervo_usuario.php";
      return;
    }

    mostrarPagina(destino);
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

     const caminhoCapa = (livro.capa || "").replace(/^\/+/, "");

imagem.src = caminhoCapa
    ? new URL("../" + caminhoCapa, window.location.href).href
    : "";

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
    const recomendados = livros
        .filter(livro => livro.disponivel)
        .slice(0, 4);

    preencherGrade($("#livrosRecomendados"), recomendados);
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



      const mensagemVazia = $("#mensagemVazia");

if (mensagemVazia) {
    mensagemVazia.hidden = resultados.length !== 0;
}

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

   
async function alternarFavorito(id) {
  try {
    const resposta = await fetch("favoritar.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        id_livro: Number(id)
      })
    });

    const dados = await resposta.json();

    if (!resposta.ok || !dados.sucesso) {
      notificar(dados.mensagem || "Não foi possível atualizar os favoritos.");
      return;
    }

    if (dados.favoritado) {
      if (!favoritos.includes(Number(id))) {
        favoritos.push(Number(id));
      }
    } else {
      favoritos = favoritos.filter(item => Number(item) !== Number(id));
    }

    atualizarTudo();
    notificar(dados.mensagem);

  } catch (erro) {
    console.error("Erro ao atualizar favoritos:", erro);
    notificar("Não foi possível conectar aos favoritos.");
  }
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

async function inicializarPagina() {
    try {
        const resposta = await fetch("buscar_livros.php");

        if (!resposta.ok) {
            throw new Error("Falha ao buscar os livros.");
        }

        const dados = await resposta.json();

        if (!dados.sucesso || !Array.isArray(dados.livros)) {
            throw new Error("A resposta do PHP não contém os livros.");
        }

        livros = dados.livros.map(livro => ({
            ...livro,
            id: Number(livro.id),
            disponivel:
                livro.disponivel === true ||
                livro.disponivel === 1 ||
                livro.disponivel === "1"
        }));

        
const respostaFavoritos = await fetch("buscar_favoritos.php");

if (!respostaFavoritos.ok) {
    throw new Error("Não foi possível carregar os favoritos.");
}

const dadosFavoritos = await respostaFavoritos.json();

if (!dadosFavoritos.sucesso) {
    throw new Error(
        dadosFavoritos.mensagem || "Erro ao carregar favoritos."
    );
}

favoritos = dadosFavoritos.favoritos
    .map(Number)
    .filter(id => livros.some(livro => livro.id === id));

emprestimos = emprestimos.filter(id =>
    livros.some(livro => livro.id === Number(id))
);

salvarArmazenamento(CHAVE_EMPRESTIMOS, emprestimos);


        mostrarSlide(0);
        atualizarTudo();

    } catch (erro) {
        console.error("Erro ao carregar os livros:", erro);

        const grade = $("#livrosRecomendados");
        if (grade) {
            grade.textContent =
                "Não foi possível carregar as recomendações.";
        }
    }
}

inicializarPagina();


    


async function carregarEventos() {
  const lista = document.getElementById("listaEventos");

  if (!lista) return;

  try {
    const resposta = await fetch("buscar_eventos.php");

    if (!resposta.ok) {
      throw new Error("Falha ao buscar eventos.");
    }

    const eventos = await resposta.json();

    lista.replaceChildren();

    if (eventos.length === 0) {
      const mensagem = document.createElement("p");
      mensagem.textContent = "Não há eventos cadastrados no momento.";
      lista.appendChild(mensagem);
      return;
    }

    eventos.forEach((evento) => {
      const cartao = document.createElement("article");
      cartao.className = "evento-item";

      const titulo = document.createElement("h3");
      titulo.textContent = evento.titulo;

      const data = document.createElement("p");
      const [ano, mes, dia] = evento.data_evento.split("-");
      data.textContent = `📅 ${dia}/${mes}/${ano}`;

     const descricao = document.createElement("p");
descricao.textContent = evento.descricao || "Sem descrição.";

// Imagem do evento
if (evento.imagem) {
  const imagem = document.createElement("img");

  imagem.src = new URL("../" + evento.imagem.replace(/^\/+/, ""), window.location.href).href;
  imagem.alt = "Imagem do evento " + evento.titulo;
  imagem.className = "evento-imagem";
  imagem.loading = "lazy";

  imagem.addEventListener("error", () => {
    console.error("Não foi possível carregar a imagem:", imagem.src);
    imagem.hidden = true;
  });

  cartao.appendChild(imagem);
}

const botao = document.createElement("button");
botao.type = "button";
botao.className = "botao evento-saiba-mais";
botao.textContent = "Saiba mais →";

botao.addEventListener("click", () => {
  const modalFundo = document.getElementById("modalFundo");
  const modalTitulo = document.getElementById("modalTitulo");
  const modalCorpo = document.getElementById("modalCorpo");
  const modalAcoes = document.getElementById("modalAcoes");

  if (!modalFundo || !modalTitulo || !modalCorpo || !modalAcoes) {
    alert(
      `${evento.titulo}\n\nData: ${data.textContent}\n\n${evento.descricao || "Sem descrição."}`
    );
    return;
  }

  modalTitulo.textContent = evento.titulo;
  modalCorpo.replaceChildren();
  modalAcoes.replaceChildren();

  const dataCompleta = document.createElement("p");
  dataCompleta.textContent = data.textContent;

  const descricaoCompleta = document.createElement("p");
  descricaoCompleta.textContent = evento.descricao || "Sem descrição.";

  modalCorpo.append(dataCompleta, descricaoCompleta);
  modalFundo.classList.add("aberto");
  const botaoFechar = document.createElement("button");
botaoFechar.className = "botao botao-secundario";
botaoFechar.textContent = "Fechar";
botaoFechar.addEventListener("click", () => {
  modalFundo.classList.remove("aberto");
});

modalAcoes.appendChild(botaoFechar);
});

cartao.append(titulo, data, descricao, botao);
lista.appendChild(cartao);

    });
  } catch (erro) {
    lista.replaceChildren();

    const mensagem = document.createElement("p");
    mensagem.textContent =
      "Não foi possível carregar os eventos. Tente novamente mais tarde.";

    lista.appendChild(mensagem);
    console.error("Erro ao carregar eventos:", erro);
  }
}

carregarEventos();
carregarLivros();

// ==========================================
// PAINEL DE NOTIFICAÇÕES
// ==========================================

const botaoNotificacoes = document.getElementById("botaoNotificacoes");
const painelNotificacoes = document.getElementById("painelNotificacoes");
const fecharNotificacoes = document.getElementById("fecharNotificacoes");

if (botaoNotificacoes && painelNotificacoes) {
  botaoNotificacoes.addEventListener("click", () => {
    const vaiAbrir = painelNotificacoes.hidden;

    painelNotificacoes.hidden = !vaiAbrir;
    botaoNotificacoes.setAttribute("aria-expanded", String(vaiAbrir));
  });
}

if (fecharNotificacoes && painelNotificacoes) {
  fecharNotificacoes.addEventListener("click", () => {
    painelNotificacoes.hidden = true;
    botaoNotificacoes?.setAttribute("aria-expanded", "false");
  });
}

document.addEventListener("click", (evento) => {
  if (
    painelNotificacoes &&
    botaoNotificacoes &&
    !painelNotificacoes.hidden &&
    !painelNotificacoes.contains(evento.target) &&
    !botaoNotificacoes.contains(evento.target)
  ) {
    painelNotificacoes.hidden = true;
    botaoNotificacoes.setAttribute("aria-expanded", "false");
  }
});