document.addEventListener("DOMContentLoaded", () => {
    console.log("Módulo do acervo inicializado com sucesso.");
});

// Função responsável por alternar a classe 'flipped' do container 3D
function virarCard(botao) {
    const cardContainer = botao.closest('.card-livro-container');
    if (cardContainer) {
        cardContainer.classList.toggle('flipped');
    }
}

let filtroStatusAtual = 'todos';

function filtrarPorStatus(status, elemento) {
    filtroStatusAtual = status;

    document.querySelectorAll('.filtro-botao').forEach(btn => btn.classList.remove('ativo'));
    if (elemento) elemento.classList.add('ativo');

    aplicarFiltros();
}

function aplicarFiltros() {
    const inputBusca = document.getElementById('inputBuscaAcervo');
    if (!inputBusca) return;

    const termo = inputBusca.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.item-livro-card');

    cards.forEach(card => {
        const titulo = card.getAttribute('data-titulo') || '';
        const autor = card.getAttribute('data-autor') || '';
        const categoria = card.getAttribute('data-categoria') || '';
        const statusCard = card.getAttribute('data-status') || '';

        const combinaTexto = titulo.includes(termo) || autor.includes(termo) || categoria.includes(termo);
        const combinaStatus = (filtroStatusAtual === 'todos') || (statusCard === filtroStatusAtual);

        if (combinaTexto && combinaStatus) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}