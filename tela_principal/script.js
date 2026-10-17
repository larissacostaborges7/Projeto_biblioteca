
/* ==========================================
   1. ACERVO E ESTADO DA APLICAÇÃO
========================================== */

let livros = [
    {
        id: 1,
        titulo: "Dom Casmurro",
        autor: "Machado de Assis",
        categoria: "Clássico brasileiro",
        disponivel: true,
        capa: "https://covers.openlibrary.org/b/isbn/9788594318241-M.jpg",
        descricao: "Um clássico da literatura brasileira."
    },
    {
        id: 2,
        titulo: "O Pequeno Príncipe",
        autor: "Antoine de Saint-Exupéry",
        categoria: "Fábula",
        disponivel: true,
        capa: "https://covers.openlibrary.org/b/isbn/9780156012195-M.jpg",
        descricao: "Uma narrativa sobre amizade e afeto."
    },
    {
        id: 3,
        titulo: "Orgulho e Preconceito",
        autor: "Jane Austen",
        categoria: "Romance",
        disponivel: false,
        capa: "https://covers.openlibrary.org/b/isbn/9780141439518-M.jpg",
        descricao: "Uma história sobre relações e convenções sociais."
    },
    {
        id: 4,
        titulo: "A Biblioteca da Meia-Noite",
        autor: "Matt Haig",
        categoria: "Ficção",
        disponivel: true,
        capa: "https://covers.openlibrary.org/b/isbn/9780525559474-M.jpg",
        descricao: "Uma história sobre escolhas e possibilidades."
    },
    {
        id: 5,
        titulo: "Harry Potter e a Pedra Filosofal",
        autor: "J. K. Rowling",
        categoria: "Fantasia",
        disponivel: true,
        capa: "https://covers.openlibrary.org/b/isbn/9780590353427-M.jpg",
        descricao: "O início de uma jornada pelo mundo da magia."
    },
    {
        id: 6,
        titulo: "A Hora da Estrela",
        autor: "Clarice Lispector",
        categoria: "Literatura brasileira",
        disponivel: false,
        capa: "https://covers.openlibrary.org/b/isbn/9788520925687-M.jpg",
        descricao: "Uma obra sobre existência e desigualdade."
    },
    {
        id: 7,
        titulo: "O Hobbit",
        autor: "J. R. R. Tolkien",
        categoria: "Fantasia",
        disponivel: true,
        capa: "https://covers.openlibrary.org/b/isbn/9780547928227-M.jpg",
        descricao: "Uma aventura repleta de perigos e descobertas."
    },
    {
        id: 8,
        titulo: "1984",
        autor: "George Orwell",
        categoria: "Ficção distópica",
        disponivel: true,
        capa: "https://covers.openlibrary.org/b/isbn/9780451524935-M.jpg",
        descricao: "Uma distopia sobre vigilância e liberdade."
    },
    {
        id: 9,
        titulo: "Capitães da Areia",
        autor: "Jorge Amado",
        categoria: "Literatura brasileira",
        disponivel: true,
        capa: "https://covers.openlibrary.org/b/isbn/9788535911695-M.jpg",
        descricao: "Uma história sobre amizade e desigualdade."
    },
    {
        id: 10,
        titulo: "A Revolução dos Bichos",
        autor: "George Orwell",
        categoria: "Sátira",
        disponivel: false,
        capa: "https://covers.openlibrary.org/b/isbn/9780451526342-M.jpg",
        descricao: "Uma fábula política sobre poder e manipulação."
    },
    {
        id: 11,
        titulo: "Mulherzinhas",
        autor: "Louisa May Alcott",
        categoria: "Romance",
        disponivel: true,
        capa: "https://covers.openlibrary.org/b/isbn/9780147514011-M.jpg",
        descricao: "Acompanhe as irmãs March e seus sonhos."
    },
    {
        id: 12,
        titulo: "Coraline",
        autor: "Neil Gaiman",
        categoria: "Fantasia",
        disponivel: true,
        capa: "https://covers.openlibrary.org/b/isbn/9780380807345-M.jpg",
        descricao: "Uma garota descobre segredos em uma casa misteriosa."
    }
];

const usuarioLogado = window.USUARIO_LOGADO === true;
const idUsuario = window.ID_USUARIO ?? null;

const prefixoUsuario = usuarioLogado && idUsuario !== null
    ? "entrePaginas_usuario_" + idUsuario + "_"
    : "entrePaginas_visitante_";

const CHAVE_FAVORITOS = prefixoUsuario + "favoritos";
const CHAVE_EMPRESTIMOS = prefixoUsuario + "emprestimos";

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
        console.warn("Não foi possível salvar os dados.");
    }
}

let favoritos = lerArmazenamento(CHAVE_FAVORITOS, []);
let emprestimos = lerArmazenamento(CHAVE_EMPRESTIMOS, []);

let filtroAtual = "todos";
let buscaAtual = "";
let slideAtual = 0;
let temporizadorCarrossel;
let temporizadorNotificacao;
let livroModalAtual = null;
let focoAnterior = null;

const $ = seletor => document.querySelector(seletor);
const $$ = seletor => [...document.querySelectorAll(seletor)];

favoritos = Array.isArray(favoritos) ? favoritos : [];
emprestimos = Array.isArray(emprestimos) ? emprestimos : [];

/* ==========================================
   2. NAVEGAÇÃO ENTRE AS ABAS
========================================== */

function mostrarPagina(nome) {
    const pagina = document.getElementById("pagina-" + nome);
    if (!pagina) return;

    $$(".pagina-conteudo").forEach(elemento => {
        const ativa = elemento === pagina;

        elemento.classList.toggle("ativa", ativa);
        elemento.hidden = !ativa;
        elemento.setAttribute("aria-hidden", String(!ativa));
    });

    $$(".aba-botao").forEach(botao => {
        const ativo = botao.dataset.pagina === nome;

        botao.classList.toggle("ativo", ativo);
        botao.setAttribute("aria-current", ativo ? "page" : "false");
    });

    const menu = $("#navbarAbas");
    const botaoMenu = $("#menuMobile");

    if (menu) menu.classList.remove("aberto");

    if (botaoMenu) {
        botaoMenu.setAttribute("aria-expanded", "false");
        botaoMenu.setAttribute("aria-label", "Abrir menu");
    }

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

$("#menuMobile")?.addEventListener("click", () => {
    const menu = $("#navbarAbas");
    if (!menu) return;

    const aberto = menu.classList.toggle("aberto");

    $("#menuMobile").setAttribute("aria-expanded", String(aberto));
    $("#menuMobile").setAttribute(
        "aria-label",
        aberto ? "Fechar menu" : "Abrir menu"
    );
});

/* ==========================================
   3. NOTIFICAÇÕES
========================================== */

function notificar(mensagem) {
    const caixa = $("#notificacao");
    if (!caixa) return;

    caixa.textContent = mensagem;
    caixa.classList.add("visivel");

    clearTimeout(temporizadorNotificacao);

    temporizadorNotificacao = setTimeout(() => {
        caixa.classList.remove("visivel");
    }, 3200);
}

/* ==========================================
   4. CARROSSEL
========================================== */

const slides = $$(".slide");
const indicadores = $$(".indicador");
const carrossel = $(".carrossel-container");

function mostrarSlide(indice) {
    if (slides.length === 0) return;

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

    if (slides.length > 1) {
        temporizadorCarrossel = setInterval(() => {
            mostrarSlide(slideAtual + 1);
        }, 6000);
    }
}

$("#slideAnterior")?.addEventListener("click", () => {
    mostrarSlide(slideAtual - 1);
    iniciarCarrossel();
});

$("#slideProximo")?.addEventListener("click", () => {
    mostrarSlide(slideAtual + 1);
    iniciarCarrossel();
});

indicadores.forEach((indicador, indice) => {
    indicador.addEventListener("click", () => {
        mostrarSlide(indice);
        iniciarCarrossel();
    });
});

carrossel?.addEventListener("mouseenter", () => {
    clearInterval(temporizadorCarrossel);
});

carrossel?.addEventListener("mouseleave", iniciarCarrossel);

/* ==========================================
   5. CARDS DOS LIVROS
========================================== */

function obterLivro(id) {
    return livros.find(livro => String(livro.id) === String(id));
}

function criarCardLivro(livro) {
    const favorito = favoritos.includes(livro.id);
    const reservado = emprestimos.includes(livro.id);

    const card = document.createElement("article");
    card.className = "card-livro";

    const capa = document.createElement("div");
    capa.className = "livro-capa";

    const imagem = document.createElement("img");
    imagem.src = livro.capa || "";
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

    const detalhes = document.createElement("button");
    detalhes.className = "botao";
    detalhes.textContent = "Ver detalhes";
    detalhes.addEventListener("click", () => abrirDetalhes(livro.id));

    const botaoFavorito = document.createElement("button");
    botaoFavorito.className = "favorito-botao" +
        (favorito ? " marcado" : "");
    botaoFavorito.textContent = favorito ? "♥" : "♡";
    botaoFavorito.setAttribute("aria-pressed", String(favorito));
    botaoFavorito.setAttribute(
        "aria-label",
        favorito ? "Remover dos favoritos" : "Adicionar aos favoritos"
    );

    botaoFavorito.addEventListener("click", () => {
        alternarFavorito(livro.id);
    });

    acoes.append(detalhes, botaoFavorito);
    info.append(titulo, autor, status, acoes);
    card.append(capa, info);

    return card;
}

function preencherGrade(elemento, lista) {
    if (!elemento) return;

    elemento.replaceChildren();

    lista.forEach(livro => {
        elemento.appendChild(criarCardLivro(livro));
    });
}

function atualizarRecomendados() {
    preencherGrade($("#livrosRecomendados"), livros.slice(0, 4));
}

/* ==========================================
   6. PESQUISA E FILTROS
========================================== */

function obterLivrosFiltrados() {
    return livros.filter(livro => {
        const texto = (
            livro.titulo + " " +
            livro.autor + " " +
            livro.categoria
        ).toLocaleLowerCase("pt-BR");

        const combinaBusca = texto.includes(
            buscaAtual.toLocaleLowerCase("pt-BR")
        );

        let combinaFiltro = true;

        if (filtroAtual === "disponivel") {
            combinaFiltro = livro.disponivel &&
                !emprestimos.includes(livro.id);
        } else if (filtroAtual === "emprestado") {
            combinaFiltro = !livro.disponivel ||
                emprestimos.includes(livro.id);
        } else if (filtroAtual === "favoritos") {
            combinaFiltro = favoritos.includes(livro.id);
        }

        return combinaBusca && combinaFiltro;
    });
}

function atualizarAcervo() {
    const resultados = obterLivrosFiltrados();

    preencherGrade($("#gradeAcervo"), resultados);

    const contagem = $("#resultadoContagem");

    if (contagem) {
        contagem.textContent = resultados.length +
            (resultados.length === 1
                ? " livro encontrado"
                : " livros encontrados");
    }

    if ($("#mensagemVazia")) {
        $("#mensagemVazia").hidden = resultados.length !== 0;
    }

    if ($("#gradeAcervo")) {
        $("#gradeAcervo").hidden = resultados.length === 0;
    }
}

$("#formBusca")?.addEventListener("submit", evento => {
    evento.preventDefault();
    buscaAtual = $("#campoBusca")?.value.trim() || "";
    atualizarAcervo();
});

$("#campoBusca")?.addEventListener("input", evento => {
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
   7. FAVORITOS
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
   8. MODAL E RESERVAS DEMONSTRATIVAS
========================================== */

function abrirModal() {
    const modal = $("#modalFundo");
    if (!modal) return;

    focoAnterior = document.activeElement;
    modal.hidden = false;
    modal.classList.add("aberto");

    $("#fecharModal")?.focus();
}

function fecharModal() {
    const modal = $("#modalFundo");

    if (modal) {
        modal.classList.remove("aberto");
        modal.hidden = true;
    }

    livroModalAtual = null;

    if (focoAnterior?.focus) {
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
    descricao.textContent = livro.descricao || "";

    const status = document.createElement("p");
    status.textContent = emprestimos.includes(id)
        ? "Você já reservou este livro."
        : livro.disponivel
            ? "Este livro está disponível para reserva."
            : "Este livro está emprestado no momento.";

    corpo.append(autor, categoria, descricao, status);

    const acoes = $("#modalAcoes");
    acoes.replaceChildren();

    const fechar = document.createElement("button");
    fechar.className = "botao botao-secundario";
    fechar.textContent = "Fechar";
    fechar.addEventListener("click", fecharModal);
    acoes.appendChild(fechar);

    if (livro.disponivel && !emprestimos.includes(id)) {
        const reservar = document.createElement("button");
        reservar.className = "botao";
        reservar.textContent = "Reservar livro";
        reservar.addEventListener("click", () => reservarLivro(id));
        acoes.appendChild(reservar);
    } else if (emprestimos.includes(id)) {
        const cancelar = document.createElement("button");
        cancelar.className = "botao";
        cancelar.textContent = "Cancelar reserva";
        cancelar.addEventListener("click", () => cancelarReserva(id));
        acoes.appendChild(cancelar);
    }

    abrirModal();
}

function reservarLivro(id) {
    if (!usuarioLogado) {
        notificar("Faça login para solicitar uma reserva.");
        fecharModal();
        window.location.href = "../tela_cadastro/index.php";
        return;
    }

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
    notificar("Reserva demonstrativa realizada: " + livro.titulo);
}

function cancelarReserva(id) {
    emprestimos = emprestimos.filter(item => item !== id);
    salvarArmazenamento(CHAVE_EMPRESTIMOS, emprestimos);

    fecharModal();
    atualizarTudo();
    notificar("Reserva demonstrativa cancelada.");
}

$("#fecharModal")?.addEventListener("click", fecharModal);

$("#modalFundo")?.addEventListener("click", evento => {
    if (evento.target === $("#modalFundo")) fecharModal();
});

document.addEventListener("keydown", evento => {
    if (evento.key === "Escape") fecharModal();
});

/* ==========================================
   9. PERFIL DO LEITOR
========================================== */

function atualizarPerfil() {
    if ($("#totalEmprestimos")) {
        $("#totalEmprestimos").textContent = emprestimos.length;
    }

    if ($("#totalFavoritos")) {
        $("#totalFavoritos").textContent = favoritos.length;
    }

    if ($("#totalLeituras")) {
        $("#totalLeituras").textContent = "0";
    }

    const lista = $("#listaEmprestimos");

    if (lista) {
        lista.replaceChildren();

        if (emprestimos.length === 0) {
            const vazio = document.createElement("div");
            vazio.className = "perfil-vazio";
            vazio.textContent =
                "Você ainda não tem reservas. Explore o acervo!";
            lista.appendChild(vazio);
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
                autor.textContent = livro.autor;

                const status = document.createElement("p");
                status.textContent = "Reserva demonstrativa.";

                texto.append(titulo, autor, status);

                const botao = document.createElement("button");
                botao.className = "botao botao-secundario";
                botao.textContent = "Cancelar reserva";
                botao.addEventListener("click", () => {
                    cancelarReserva(id);
                });

                item.append(texto, botao);
                lista.appendChild(item);
            });
        }
    }

    const livrosFavoritos = favoritos.map(obterLivro).filter(Boolean);

    preencherGrade($("#gradeFavoritos"), livrosFavoritos);

    if ($("#gradeFavoritos")) {
        $("#gradeFavoritos").hidden = livrosFavoritos.length === 0;
    }

    if ($("#favoritosVazios")) {
        $("#favoritosVazios").hidden = livrosFavoritos.length !== 0;
    }
}

/* ==========================================
   10. CARREGAR LIVROS DO BANCO DE DADOS
========================================== */

async function carregarLivrosDoBanco() {
    try {
        const resposta = await fetch("buscar_livros.php");

        if (!resposta.ok) {
            throw new Error("Resposta HTTP " + resposta.status);
        }

        const dados = await resposta.json();

        if (!dados.sucesso || !Array.isArray(dados.livros)) {
            throw new Error("Formato de resposta inválido.");
        }

        livros = dados.livros.map(livro => ({
            ...livro,
            id: Number(livro.id ?? livro.id_livro),
            titulo: livro.titulo ?? livro.nome ?? "",
            autor: livro.autor ?? "",
            categoria: livro.categoria ?? "",
            disponivel: livro.disponivel === true ||
                livro.disponivel === 1 ||
                livro.disponivel === "1",
            capa: livro.capa ?? "",
            descricao: livro.descricao ?? ""
        }));

        favoritos = favoritos.filter(id => obterLivro(id));
        emprestimos = emprestimos.filter(id => obterLivro(id));

        atualizarTudo();
    } catch (erro) {
        console.error("Erro ao carregar o acervo:", erro);
        atualizarTudo();
        notificar("Não foi possível carregar o acervo do servidor.");
    }
}

/* ==========================================
   11. ATUALIZAÇÃO GERAL
========================================== */

function atualizarTudo() {
    atualizarRecomendados();
    atualizarAcervo();
    atualizarPerfil();
}

/* ==========================================
   12. NOTIFICAÇÕES DO SITE
========================================== */

const botaoSino = $("#botaoSino");
const painelNotificacoes = $("#painelNotificacoes");

if (botaoSino && painelNotificacoes) {
    botaoSino.addEventListener("click", evento => {
        evento.stopPropagation();

        const abrir = painelNotificacoes.hidden;
        painelNotificacoes.hidden = !abrir;
    });

    document.addEventListener("click", () => {
        painelNotificacoes.hidden = true;
    });

    painelNotificacoes.addEventListener("click", evento => {
        evento.stopPropagation();
    });
}

/* ==========================================
   13. INICIALIZAÇÃO
========================================== */

if ($("#anoAtual")) {
    $("#anoAtual").textContent = new Date().getFullYear();
}

mostrarSlide(0);
iniciarCarrossel();
atualizarTudo();
carregarLivrosDoBanco();
