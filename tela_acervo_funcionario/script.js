
const campoBusca = document.getElementById('buscaAcervo');
const gradeLivros = document.getElementById('gradeLivros');
const mensagemVazia = document.getElementById('mensagemVazia');

if (campoBusca && gradeLivros) {
    campoBusca.addEventListener('input', function () {
        const pesquisa = campoBusca.value
            .trim()
            .toLocaleLowerCase('pt-BR');

        const livros = gradeLivros.querySelectorAll('.livro-card');
        let encontrados = 0;

        livros.forEach(function (livro) {
            const conteudo = livro.textContent
                .toLocaleLowerCase('pt-BR');

            const corresponde = conteudo.includes(pesquisa);

            livro.hidden = !corresponde;

            if (corresponde) {
                encontrados++;
            }
        });

        if (mensagemVazia) {
            mensagemVazia.hidden = encontrados > 0;
        }
    });
}
