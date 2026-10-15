document.addEventListener('DOMContentLoaded', () => {
    // Exemplo de interatividade: Adicionar um efeito de clique ou filtro nas linhas das tabelas
    const linhasTabela = document.querySelectorAll('tbody tr');

    linhasTabela.forEach(linha => {
        linha.addEventListener('mouseenter', () => {
            linha.style.backgroundColor = '#f8fafc';
        });
        linha.addEventListener('mouseleave', () => {
            linha.style.backgroundColor = '';
        });
    });

    console.log('Central de Notificações carregada com sucesso.');
});