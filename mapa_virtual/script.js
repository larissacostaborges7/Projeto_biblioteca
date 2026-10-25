
let livroSelecionado = null;


document.addEventListener("DOMContentLoaded", () => {
    if (typeof libraryData !== "undefined") {
        renderizarMapa(libraryData);
    }

    const botaoFechar = document.getElementById("closeModalBtn");
    const botaoFecharRodape = document.getElementById("closeModalFooterBtn");

    if (botaoFechar) {
        botaoFechar.addEventListener("click", closeModal);
    }

    if (botaoFecharRodape) {
        botaoFecharRodape.addEventListener("click", closeModal);
    }

    const campoPesquisa = document.getElementById("searchInput");
    const botaoLimpar = document.getElementById("clearSearchBtn");

    if (campoPesquisa) {
        campoPesquisa.addEventListener("input", handleSearch);
    }

    if (botaoLimpar) {
        botaoLimpar.addEventListener("click", clearSearch);
    }
});


// Evita que títulos e autores sejam interpretados como HTML.
function escaparHTML(valor) {
    return String(valor ?? "").replace(/[&<>"']/g, caractere => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;"
    })[caractere]);
}

function renderizarMapa(livros) {
    const container = document.getElementById("bookcasesGrid");
    container.innerHTML = "";

    // Agrupa os livros pelo identificador da categoria.
    const categorias = new Map();

    livros.forEach(livro => {
        const idCategoria = Number(livro.id_categoria);

        if (!categorias.has(idCategoria)) {
            categorias.set(idCategoria, {
                nome: livro.categoria || "Sem categoria",
                livros: []
            });
        }

        categorias.get(idCategoria).livros.push(livro);
    });

    const categoriasOrdenadas = [...categorias.entries()]
        .sort((a, b) => a[0] - b[0]);

    categoriasOrdenadas.forEach(([idCategoria, dados]) => {
        const estante = document.createElement("div");

        estante.className =
            "wood-shelf-frame rounded-2xl p-4 flex flex-col relative shadow-2xl transition-all duration-300";

        estante.id = `bookcase-${idCategoria}`;

        dados.livros.sort(
            (a, b) => Number(a.id) - Number(b.id)
        );

        let conteudo = `
            <div>
                <div class="border-b border-brand-gold/40 pb-3 mb-4 flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-brand-gold/20 text-brand-gold border border-brand-gold/40 flex items-center justify-center">
                        <i class="fa-solid fa-book-open"></i>
                    </div>
                    <div>
                        <h3 class="font-cinzel text-sm font-bold text-white">
                            Estante ${String(idCategoria).padStart(2, "0")}
                        </h3>
                        <p class="text-[11px] text-brand-goldLight">
                            ${escaparHTML(dados.nome)}
                        </p>
                    </div>
                </div>
                <div class="space-y-4">
        `;

        // Cinco livros por prateleira, ordenados pelo ID.
        const quantidadePrateleiras = Math.ceil(
            dados.livros.length / 5
        );

        for (let numeroPrateleira = 1;
             numeroPrateleira <= quantidadePrateleiras;
             numeroPrateleira++) {

            const inicio = (numeroPrateleira - 1) * 5;

            const livrosPrateleira = dados.livros.slice(
                inicio,
                inicio + 5
            );

            conteudo += `
                <div>
                    <div class="flex items-center justify-between px-1 mb-1">
                        <span class="text-[10px] font-bold tracking-wider text-brand-goldLight uppercase">
                            Prateleira ${numeroPrateleira}
                        </span>
                        <span class="text-[9px] font-mono text-brand-subtext">
                            ${livrosPrateleira.length} livros
                        </span>
                    </div>

                    <div class="shelf-niche rounded-lg px-2 pt-5 pb-1 flex items-end justify-between min-h-[145px] relative">
            `;

            livrosPrateleira.forEach(livro => {
                const emprestado = livro.status === "emprestado";

                const corStatus = emprestado
                    ? "border-rose-500/80"
                    : "border-emerald-500/80";

                const corLivro = livro.cor || "#142B4A";

                conteudo += `
                    <div
                      data-livro-id="${Number(livro.id_livro ?? livro.id)}"
                        class="book-spine w-[18%] h-[115px] rounded-t flex flex-col justify-between py-2 px-0.5 text-center text-white border-t-2 border-x ${corStatus}"
                        style="background: linear-gradient(180deg, ${escaparHTML(corLivro)} 0%, #0D1D33 100%);"
                        title="#${Number(livro.id)} - ${escaparHTML(livro.titulo)}"
                        role="button"
                        tabindex="0"
                        aria-label="Ver detalhes de ${escaparHTML(livro.titulo)}"
                    >
                        <div class="spine-ridge top-2"></div>
                        <div class="spine-ridge bottom-8"></div>

                        <div class="text-[9px] font-mono font-bold text-brand-goldLight bg-black/40 rounded mx-0.5">
                            ${Number(livro.id)}
                        </div>

                        <div class="my-auto px-0.5">
                            <p class="text-[9px] font-bold font-cinzel leading-tight text-white/90 uppercase"
                               style="writing-mode: vertical-rl; transform: rotate(180deg); margin: 0 auto; max-height: 55px; overflow: hidden;">
                                ${escaparHTML(livro.titulo)}
                            </p>
                        </div>

                        <div class="flex justify-center">
                            <span class="w-2 h-2 rounded-full ${
                                emprestado ? "bg-rose-500" : "bg-emerald-400"
                            }"></span>
                        </div>
                    </div>
                `;
            });

            conteudo += `
                    </div>
                    <div class="shelf-plank rounded-b-md"></div>
                </div>
            `;
        }

        conteudo += "</div></div>";

        estante.innerHTML = conteudo;
        container.appendChild(estante);
    });

    // Adiciona os eventos de clique e teclado aos livros.
   
container.querySelectorAll("[data-livro-id]").forEach(elemento => {
    const abrir = () => abrirFichaLivro(elemento.getAttribute("data-livro-id"));


        elemento.addEventListener("click", abrir);

        elemento.addEventListener("keydown", evento => {
            if (evento.key === "Enter" || evento.key === " ") {
                evento.preventDefault();
                abrir();
            }
        });
    });
}


function handleSearch() {
    const campo = document.getElementById("searchInput");
    const botaoLimpar = document.getElementById("clearSearchBtn");

    if (!campo || !botaoLimpar) return;

    const consulta = campo.value
        .toLocaleLowerCase("pt-BR")
        .trim();

    botaoLimpar.classList.toggle("hidden", consulta === "");

    libraryData.forEach((livro) => {
        const idLivro = livro.id_livro ?? livro.id;
        const lombada = document.getElementById(`book-spine-${idLivro}`);

        if (!lombada) return;

        const titulo = String(livro.titulo ?? "")
            .toLocaleLowerCase("pt-BR");

        const autor = String(livro.autor ?? "")
            .toLocaleLowerCase("pt-BR");

        const categoria = String(livro.categoria ?? "")
            .toLocaleLowerCase("pt-BR");

        const numero = String(idLivro ?? "");

        const encontrado =
            titulo.includes(consulta) ||
            autor.includes(consulta) ||
            categoria.includes(consulta) ||
            numero.includes(consulta);

        lombada.classList.toggle(
            "book-highlight",
            consulta !== "" && encontrado
        );

        lombada.style.opacity =
            consulta === "" || encontrado ? "1" : "0.3";
    });
}


function clearSearch() {
    document.getElementById("searchInput").value = "";
    handleSearch();
}


function abrirFichaLivro(idLivro) {
    livroSelecionado = libraryData.find(
        livro => Number(livro.id_livro ?? livro.id) === Number(idLivro)
    );

    if (!livroSelecionado) {
        console.error("Livro não encontrado:", idLivro);
        return;
    }

    const livro = livroSelecionado;
    const categoria = livro.categoria || "Sem categoria";

    const prateleira = Math.ceil(
        Number(livro.numero_categoria ?? livro.posicao ?? 1) / 5
    );

    document.getElementById("modalTitle").textContent =
        livro.titulo || "Título não informado";

    document.getElementById("modalAuthor").textContent =
        `por ${livro.autor || "Autor não informado"}`;

    document.getElementById("modalNumericCode").textContent =
        `#${livro.id_livro ?? livro.id}`;

    document.getElementById("modalCategory").textContent = categoria;

    document.getElementById("modalShelf").textContent =
        categoria;

    document.getElementById("modalTier").textContent =
        prateleira;

    document.getElementById("modalPosition").textContent =
        livro.posicao ?? "Não definida";

    document.getElementById("bookModal").classList.remove(
        "opacity-0",
        "pointer-events-none"
    );

    document.getElementById("modalContainer").classList.remove("scale-95");
    document.getElementById("modalContainer").classList.add("scale-100");
}


function closeModal() {
    document.getElementById("bookModal").classList.add(
        "opacity-0",
        "pointer-events-none"
    );

    document.getElementById("modalContainer").classList.remove("scale-100");
    document.getElementById("modalContainer").classList.add("scale-95");
}

function toggleBookFlip() {
    document.getElementById("card3D").classList.toggle("flipped");
}

function atualizarStatusFicha() {
    if (!livroSelecionado) return;

    const emprestado = livroSelecionado.status === "emprestado";
    const etiqueta = document.getElementById("modalStatusBadge");
    const botao = document.getElementById("modalActionBtn");

    etiqueta.className = emprestado
        ? "text-xs font-semibold px-2.5 py-1 rounded-full bg-rose-100 text-rose-800 border border-rose-300"
        : "text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300";

    etiqueta.textContent = emprestado ? "Emprestado" : "Disponível";

    botao.innerHTML = emprestado
        ? '<i class="fa-solid fa-rotate-left"></i><span>Simular Devolução</span>'
        : '<i class="fa-solid fa-bookmark"></i><span>Simular Empréstimo</span>';
}

function toggleBookStatus() {
    if (!livroSelecionado) return;

    // Simulação local: não altera o banco de dados.
    livroSelecionado.status =
        livroSelecionado.status === "disponivel"
            ? "emprestado"
            : "disponivel";

    renderizarMapa(libraryData);
    handleSearch();

    // Mantém a ficha aberta e atualiza o status.
    atualizarStatusFicha();
}
