document.addEventListener('DOMContentLoaded', () => {
    // Gestão de abas
    const navLinks = document.querySelectorAll('.nav-link');
    const tabContents = document.querySelectorAll('.tab-content');

    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            if (link.getAttribute('href').startsWith('../') || link.getAttribute('href').startsWith('http')) {
                return;
            }

            e.preventDefault();
            navLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(t => t.classList.remove('active'));

            link.classList.add('active');
            const targetId = link.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');
        });
    });

    // Sistema de Autocomplete com Lista Flutuante
    function configurarAutocomplete(inputId, selectId) {
        const input = document.getElementById(inputId);
        const select = document.getElementById(selectId);

        if (!input || !select) return;

        // Oculta o select nativo original
        select.style.display = 'none';

        // Cria o container da lista flutuante de sugestões
        const container = document.createElement('div');
        container.style.position = 'relative';
        container.style.width = '100%';
        input.parentNode.insertBefore(container, input);
        container.appendChild(input);

        const listaSugestoes = document.createElement('div');
        listaSugestoes.className = 'autocomplete-lista';
        container.appendChild(listaSugestoes);

        // Estilos da lista flutuante
        listaSugestoes.style.position = 'absolute';
        listaSugestoes.style.top = '100%';
        listaSugestoes.style.left = '0';
        listaSugestoes.style.right = '0';
        listaSugestoes.style.maxHeight = '200px';
        listaSugestoes.style.overflowY = 'auto';
        listaSugestoes.style.backgroundColor = '#fff';
        listaSugestoes.style.border = '1px solid #cbd5e1';
        listaSugestoes.style.borderRadius = '0 0 6px 6px';
        listaSugestoes.style.boxShadow = '0 4px 6px -1px rgba(0,0,0,0.1)';
        listaSugestoes.style.zIndex = '999';
        listaSugestoes.style.display = 'none';

        // Extrai as opções do select para um array de objetos
        const opcoes = Array.from(select.options)
            .filter(opt => opt.value !== '')
            .map(opt => ({
                valor: opt.value,
                texto: opt.textContent.trim()
            }));

        // Evento ao digitar no input
        input.addEventListener('input', () => {
            const termo = input.value.toLowerCase().trim();
            listaSugestoes.innerHTML = '';

            if (termo === '') {
                listaSugestoes.style.display = 'none';
                select.value = ''; // Limpa a seleção se apagar o texto
                return;
            }

            // Filtra as opções que contêm o texto digitado
            const filtradas = opcoes.filter(item => item.texto.toLowerCase().includes(termo));

            if (filtradas.length === 0) {
                const itemDiv = document.createElement('div');
                itemDiv.textContent = 'Nenhum resultado encontrado';
                itemDiv.style.padding = '8px 12px';
                itemDiv.style.color = '#64748b';
                itemDiv.style.fontSize = '0.9rem';
                listaSugestoes.appendChild(itemDiv);
            } else {
                filtradas.forEach(item => {
                    const itemDiv = document.createElement('div');
                    itemDiv.textContent = item.texto;
                    itemDiv.style.padding = '10px 12px';
                    itemDiv.style.cursor = 'pointer';
                    itemDiv.style.fontSize = '0.9rem';
                    itemDiv.style.borderBottom = '1px solid #f1f5f9';

                    // Efeito hover ao passar o mouse
                    itemDiv.addEventListener('mouseenter', () => {
                        itemDiv.style.backgroundColor = '#f8fafc';
                    });
                    itemDiv.addEventListener('mouseleave', () => {
                        itemDiv.style.backgroundColor = '#fff';
                    });

                    // Ao clicar na sugestão
                    itemDiv.addEventListener('click', () => {
                        input.value = item.texto;
                        select.value = item.valor; // Define o valor real no select invisível para enviar no POST
                        listaSugestoes.style.display = 'none';
                    });

                    listaSugestoes.appendChild(itemDiv);
                });
            }

            listaSugestoes.style.display = 'block';
        });

        // Fecha a lista se clicar fora
        document.addEventListener('click', (e) => {
            if (!container.contains(e.target)) {
                listaSugestoes.style.display = 'none';
            }
        });
    }

    // Ativa o autocomplete nos campos de usuário e exemplar
    configurarAutocomplete('busca_usuario', 'id_usuario');
    configurarAutocomplete('busca_exemplar', 'id_exemplar');
});