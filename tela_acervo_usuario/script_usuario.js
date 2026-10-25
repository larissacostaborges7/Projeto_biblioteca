
document.addEventListener("DOMContentLoaded", () => {
    console.log("Módulo do acervo inicializado com sucesso.");
    carregarFavoritos();
});

// Alterna a visualização do card 3D
function virarCard(botao) {
    const cardContainer = botao.closest(".card-livro-container");

    if (cardContainer) {
        cardContainer.classList.toggle("flipped");
    }
}

let filtroStatusAtual = "todos";
let livrosFavoritos = [];

async function carregarFavoritos() {
    try {
        const resposta = await fetch("../tela_principal2/buscar_favoritos.php");

        if (!resposta.ok) {
            throw new Error("Não foi possível carregar os favoritos.");
        }

        const dados = await resposta.json();
        livrosFavoritos = dados.sucesso
            ? dados.favoritos.map(Number)
            : [];

        aplicarFiltros();
    } catch (erro) {
        console.error("Erro ao carregar favoritos:", erro);
    }
}

function filtrarPorStatus(status, elemento) {
    filtroStatusAtual = status;

    document.querySelectorAll(".filtro-botao").forEach(btn => {
        btn.classList.remove("ativo");
    });

    if (elemento) {
        elemento.classList.add("ativo");
    }

    aplicarFiltros();
}

function aplicarFiltros() {
    const inputBusca = document.getElementById("inputBuscaAcervo");
    if (!inputBusca) return;

    const termo = inputBusca.value.toLowerCase().trim();
    const cards = document.querySelectorAll(".item-livro-card");

    cards.forEach(card => {
        const titulo = card.getAttribute("data-titulo") || "";
        const autor = card.getAttribute("data-autor") || "";
        const categoria = card.getAttribute("data-categoria") || "";
        const statusCard = card.getAttribute("data-status") || "";
        const idLivro = Number(card.getAttribute("data-id-livro"));

        const combinaTexto =
            titulo.includes(termo) ||
            autor.includes(termo) ||
            categoria.includes(termo);

        const combinaStatus =
            filtroStatusAtual === "todos" ||
            (filtroStatusAtual === "disponivel" && statusCard === "disponivel") ||
            (filtroStatusAtual === "indisponivel" && statusCard === "indisponivel") ||
            (filtroStatusAtual === "favoritos" && livrosFavoritos.includes(idLivro));

        card.style.display =
            combinaTexto && combinaStatus ? "" : "none";
    });

}

window.alternarFavoritoAcervo = async function (botao) {
    const idLivro = Number(botao.dataset.idLivro);

    if (!idLivro) {
        alert("Não foi possível identificar este livro.");
        return;
    }

    botao.disabled = true;

    try {
        const resposta = await fetch("../tela_principal2/favoritar.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                id_livro: idLivro
            })
        });

        const dados = await resposta.json();

        if (!resposta.ok || !dados.sucesso) {
            alert(dados.mensagem || "Não foi possível atualizar os favoritos.");
            return;
        }

        botao.classList.toggle("favoritado", dados.favoritado);
        botao.textContent = dados.favoritado ? "♥" : "♡";
        botao.setAttribute(
            "aria-label",
            dados.favoritado ? "Remover dos favoritos" : "Adicionar aos favoritos"
        );
        botao.title = dados.favoritado
            ? "Remover dos favoritos"
            : "Adicionar aos favoritos";

        if (typeof carregarFavoritos === "function") {
            await carregarFavoritos();
        }
    } catch (erro) {
        console.error("Erro ao favoritar livro:", erro);
        alert("Não foi possível conectar aos favoritos.");
    } finally {
        botao.disabled = false;
    }
    
/* AMPLIAÇÃO DAS CAPAS DO ACERVO */
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.createElement("div");
    modal.className = "modal-capa";
    modal.setAttribute("role", "dialog");
    modal.setAttribute("aria-modal", "true");
    modal.setAttribute("aria-label", "Capa ampliada do livro");

    modal.innerHTML = `
        <button type="button" class="modal-capa-fechar"
                aria-label="Fechar imagem">&times;</button>
        <img src="" alt="Capa ampliada do livro">
    `;

    document.body.appendChild(modal);

    const imagemAmpliada = modal.querySelector("img");
    const botaoFechar = modal.querySelector(".modal-capa-fechar");

    function fecharModal() {
        modal.classList.remove("aberta");
        imagemAmpliada.src = "";
    }

    document.querySelectorAll(".livro-capa img").forEach((imagem) => {
        imagem.addEventListener("click", () => {
            imagemAmpliada.src = imagem.src;
            imagemAmpliada.alt = imagem.alt || "Capa ampliada do livro";
            modal.classList.add("aberta");
        });
    });

    botaoFechar.addEventListener("click", fecharModal);

    modal.addEventListener("click", (evento) => {
        if (evento.target === modal) {
            fecharModal();
        }
    });
        document.addEventListener("keydown", (evento) => {
        if (evento.key === "Escape") {
            fecharModal();
        }
    });
});
}
// Ampliação das capas dos livros
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("modalCapa");
    const imagemAmpliada = document.getElementById("imagemCapaAmpliada");
    const botaoFechar = document.getElementById("fecharModalCapa");

    if (!modal || !imagemAmpliada || !botaoFechar) {
        console.error("Elementos da ampliação da capa não encontrados.");
        return;
    }

    function abrirCapa(imagem) {
        imagemAmpliada.src = imagem.src;
        imagemAmpliada.alt = imagem.alt || "Capa ampliada do livro";
        modal.classList.add("aberta");
        modal.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";
    }

    function fecharCapa() {
        modal.classList.remove("aberta");
        modal.setAttribute("aria-hidden", "true");
        imagemAmpliada.src = "";
        document.body.style.overflow = "";
    }

    document.querySelectorAll(".livro-capa img").forEach((imagem) => {
        imagem.addEventListener("click", () => abrirCapa(imagem));
    });

    botaoFechar.addEventListener("click", fecharCapa);

    modal.addEventListener("click", (evento) => {
        if (evento.target === modal) {
            fecharCapa();
        }
    });

    document.addEventListener("keydown", (evento) => {
        if (evento.key === "Escape") {
            fecharCapa();
        }
    });
});