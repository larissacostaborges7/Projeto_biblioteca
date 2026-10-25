
document.addEventListener("DOMContentLoaded", () => {
    const campoLeitor = document.getElementById("pesquisa_leitor");
    const campoExemplar = document.getElementById("pesquisa_exemplar");
    const idUsuario = document.getElementById("id_usuario");
    const idExemplar = document.getElementById("id_exemplar");
    const listaLeitores = document.getElementById("sugestoes_leitores");
    const listaExemplares = document.getElementById("sugestoes_exemplares");
    const textoLeitor = document.getElementById("leitor_selecionado");
    const textoExemplar = document.getElementById("exemplar_selecionado");
    const dataInput = document.getElementById("data_prevista_devolucao");
    const formulario = document.getElementById("formEmprestimo");

    let temporizadorLeitor;
    let temporizadorExemplar;
    let consultaLeitor = 0;
    let consultaExemplar = 0;

    function limparLista(lista) {
        lista.replaceChildren();
    }

    function criarSugestao(texto, aoSelecionar, lista) {
        const botao = document.createElement("button");
        botao.type = "button";
        botao.className = "sugestao-item";
        botao.textContent = texto;

        botao.addEventListener("click", aoSelecionar);
        lista.appendChild(botao);
    }

    async function buscar(url) {
        const resposta = await fetch(url, {
            headers: { Accept: "application/json" }
        });

        if (!resposta.ok) {
            throw new Error("Falha na pesquisa.");
        }

        return resposta.json();
    }

    campoLeitor.addEventListener("input", () => {
        idUsuario.value = "";
        textoLeitor.textContent = "";
        limparLista(listaLeitores);
        clearTimeout(temporizadorLeitor);

        const termo = campoLeitor.value.trim();
        const numeroConsulta = ++consultaLeitor;

        if (!termo) return;

        temporizadorLeitor = setTimeout(async () => {
            try {
                const dados = await buscar(
                    `buscar_leitores.php?termo=${encodeURIComponent(termo)}`
                );

                if (numeroConsulta !== consultaLeitor) return;

                limparLista(listaLeitores);

                if (!Array.isArray(dados) || dados.length === 0) {
                    criarSugestao(
                        "Nenhum leitor encontrado.",
                        () => {},
                        listaLeitores
                    );
                    return;
                }

                dados.forEach((leitor) => {
                    const nome = `${leitor.nome} — CPF: ${leitor.cpf}`;

                    criarSugestao(nome, () => {
                        campoLeitor.value = leitor.nome;
                        idUsuario.value = leitor.id_usuario;
                        textoLeitor.textContent = `Leitor selecionado: ${nome}`;
                        limparLista(listaLeitores);
                    }, listaLeitores);
                });
            } catch {
                limparLista(listaLeitores);
                criarSugestao(
                    "Não foi possível pesquisar leitores.",
                    () => {},
                    listaLeitores
                );
            }
        }, 200);
    });

    campoExemplar.addEventListener("input", () => {
        idExemplar.value = "";
        textoExemplar.textContent = "";
        limparLista(listaExemplares);
        clearTimeout(temporizadorExemplar);

        const termo = campoExemplar.value.trim();
        const numeroConsulta = ++consultaExemplar;

        if (!termo) return;

        temporizadorExemplar = setTimeout(async () => {
            try {
                const dados = await buscar(
                    `buscar_exemplares.php?termo=${encodeURIComponent(termo)}`
                );

                if (numeroConsulta !== consultaExemplar) return;

                limparLista(listaExemplares);

                if (!Array.isArray(dados) || dados.length === 0) {
                    criarSugestao(
                        "Nenhum exemplar disponível encontrado.",
                        () => {},
                        listaExemplares
                    );
                    return;
                }

                dados.forEach((livro) => {
                    const detalhes = [
                        livro.titulo,
                        `Autor: ${livro.autor}`,
                        livro.categoria ? `Categoria: ${livro.categoria}` : "",
                        livro.isbn ? `ISBN: ${livro.isbn}` : "",
                        `Patrimônio: ${livro.codigo_patrimonio}`
                    ].filter(Boolean).join(" | ");

                    criarSugestao(detalhes, () => {
                        campoExemplar.value = livro.titulo;
                        idExemplar.value = livro.id_exemplar;
                        textoExemplar.textContent =
                            `Exemplar selecionado: ${livro.codigo_patrimonio}`;
                        limparLista(listaExemplares);
                    }, listaExemplares);
                });
            } catch {
                limparLista(listaExemplares);
                criarSugestao(
                    "Não foi possível pesquisar exemplares.",
                    () => {},
                    listaExemplares
                );
            }
        }, 200);
    });

    formulario.addEventListener("submit", (evento) => {
        if (!idUsuario.value || !idExemplar.value) {
            evento.preventDefault();
            alert("Selecione um leitor e um exemplar nas sugestões.");
        }
    });

    if (dataInput) {
        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);

        const limite = new Date(hoje);
        limite.setDate(limite.getDate() + 14);

        const formatarData = (data) => {
            const ano = data.getFullYear();
            const mes = String(data.getMonth() + 1).padStart(2, "0");
            const dia = String(data.getDate()).padStart(2, "0");
            return `${ano}-${mes}-${dia}`;
        };

        dataInput.min = formatarData(hoje);
        dataInput.max = formatarData(limite);
        dataInput.value = formatarData(limite);
    }
});
