
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_funcionario'])) {
    header('Location: ../tela_cadastro/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Funcionário | Entre Páginas</title>
    <link rel="stylesheet" href="../tela_principal2/style.css">
<link rel="stylesheet" href="painel.css?v=2">
</head>
    <header class="painel-topo">
        <div>
            <h1>Biblioteca Municipal de Arujá</h1>
            <p>Professora Alda Martins Soncini</p>
        </div>

        <div class="funcionario-info">
            <span>
                Olá, <?= htmlspecialchars($_SESSION['nome_funcionario'] ?? 'Funcionário', ENT_QUOTES, 'UTF-8') ?>
            </span>
            <strong>
                <?= htmlspecialchars($_SESSION['cargo'] ?? 'Funcionário', ENT_QUOTES, 'UTF-8') ?>
            </strong>
           <a href="../includes/logout.php">Sair</a>
        </div>
    </header>

    <main class="painel-conteudo">
        <section class="boas-vindas">
            <h2>Painel do Funcionário</h2>
            <p>Acesse rapidamente as tarefas da biblioteca.</p>
        </section>

        <section class="busca-global">
    <label for="buscaAcervo">Buscar no acervo</label>

    <div class="busca-campo">
        <input
            type="search"
            id="buscaAcervo"
            placeholder="Título, autor, ISBN ou código do livro..."
            autocomplete="off"
        >

        <button type="button" id="btnBuscarAcervo">
            Buscar
        </button>
    </div>

    <div
        id="resultadosBuscaAcervo"
        class="resultados-busca-acervo"
        aria-live="polite"
    ></div>
</section>
        <section class="atalhos">
            <h2>Atendimento rápido</h2>

            <div class="atalhos-grid">
                
<?php if (($_SESSION['cargo'] ?? '') === 'Administrador'): ?>
    <a href="../gerenciar_funcionarios/index.php" class="atalho-card">
        <span>👥</span>
        <h3>Gerenciar funcionários</h3>
        <p>Cadastrar novos funcionários e administrar os acessos.</p>
    </a>
<?php endif; ?>

                <a href="../tela_emprestimo/registrar.php" class="atalho-card">
                    <span>📚</span>
                    <h3>Registrar empréstimo</h3>
                    <p>Registrar a retirada de livros.</p>
                </a>

                <a href="../tela_cadastro_acervo/tela_cadastro_acervo.php" class="atalho-card">
    <span>📚</span>
    <h3>Cadastrar livros</h3>
    <p>Adicionar livros e exemplares ao acervo.</p>
</a>

                <a href="../tela_emprestimo/devolver.php" class="atalho-card">
                    <span>↩️</span>
                    <h3>Devoluções pendentes</h3>
                    <p>Acessar a área de circulação.</p>
                </a>
                <a href="../tela_emprestimo/historico.php" class="atalho-card">
    <span>📋</span>
    <h3>Histórico de empréstimos</h3>
    <p>Consultar empréstimos ativos e anteriores.</p>
</a>

                <a href="../tela_cadastro_leitor/index.php" class="atalho-card">
                    <span>👤</span>
                    <h3>Cadastro de leitor</h3>
                    <p>Acessar a área de cadastro.</p>
                </a>

                
<a href="../tela_acervo_funcionario/tela_acervo_funcionario.php" class="atalho-card">
    <span>📖</span>
    <h3>Ver acervo disponível</h3>
    <p>Consultar os livros cadastrados.</p>
</a>


                <a href="../tela_emprestimo/reservas.php" class="atalho-card">
                    <span>🔖</span>
                    <h3>Ver reservas</h3>
                    <p>Consultar reservas de livros.</p>
                </a>

                 
<a href="../evento/index.php" class="atalho-card">
    <span>📅</span>
    <h3>Cadastrar evento</h3>
    <p>Organizar atividades da biblioteca.</p>
</a>


<a href="../mapa_virtual/index.php" class="atalho-card card-mapa-virtual">
    <span>🗺️</span>
    <h3>Mapa Virtual do Acervo</h3>
    <p>Encontre a localização dos livros por estante, prateleira e posição.</p>
</a>


<a href="../logs/index.php" class="atalho-card">
    <span>📋</span>
    <h3>Logs do Sistema</h3>
    <p>Consultar as ações e alterações realizadas no sistema.</p>
</a>

<a href="../notificacoes/index.php" class="atalho-card">
    <span>🔔</span>
    <h3>Notificações</h3>
    <p>Consultar avisos, próximos eventos e alertas da biblioteca.</p>
</a>

            </div>
        </section>

        <section class="painel-informacoes">
            <h2>Organização do dia</h2>
            <p>
                Em breve: alertas de atraso, reservas prontas,
                livros para guardar e estatísticas da biblioteca.
            </p>
        </section>
    
    </main>

    <!-- POP-UP DE NOTIFICAÇÕES -->
    <div
        id="popupNotificacoes"
        class="popup-notificacoes"
        role="dialog"
        aria-labelledby="tituloPopupNotificacoes"
        aria-describedby="textoPopupNotificacoes"
        aria-hidden="true"
    >
        <div class="popup-notificacoes-conteudo">
            <button
                type="button"
                id="fecharPopupNotificacoes"
                class="popup-notificacoes-fechar"
                aria-label="Fechar notificações"
            >
                &times;
            </button>

            <span class="popup-notificacoes-icone">🔔</span>

            <h2 id="tituloPopupNotificacoes">
                Atenção aos empréstimos!
            </h2>

            <div id="textoPopupNotificacoes">
                <p>Verificando os empréstimos da biblioteca...</p>
            </div>

            <a
                href="../notificacoes/index.php"
                class="popup-notificacoes-botao"
            >
                Ver todas as notificações
            </a>
        </div>
    </div>

    
    <script>
(() => {
    const campo = document.getElementById('buscaAcervo');
    const botao = document.getElementById('btnBuscarAcervo');
    const resultados = document.getElementById('resultadosBuscaAcervo');

    if (!campo || !botao || !resultados) return;

    let temporizador;
    let controlador;

    async function buscarAcervo() {
        const termo = campo.value.trim();

        if (controlador) controlador.abort();
        resultados.replaceChildren();

        if (termo.length === 0) {
            botao.disabled = false;
            return;
        }

        if (termo.length < 2) {
            resultados.textContent = 'Digite mais uma letra para buscar.';
            return;
        }

        controlador = new AbortController();
        botao.disabled = true;
        resultados.textContent = 'Buscando...';

        try {
            const resposta = await fetch(
                'buscar_acervo.php?q=' + encodeURIComponent(termo),
                {
                    cache: 'no-store',
                    signal: controlador.signal
                }
            );

            const dados = await resposta.json();

            if (!resposta.ok || !dados.sucesso) {
                resultados.textContent =
                    dados.mensagem || 'Não foi possível realizar a busca.';
                return;
            }

            resultados.replaceChildren();

            if (!dados.livros.length) {
                resultados.textContent = 'Nenhum livro encontrado.';
                return;
            }

            dados.livros.forEach(livro => {
                const item = document.createElement('article');
                item.className = 'item-resultado-acervo';

                const titulo = document.createElement('h3');
                titulo.textContent = livro.titulo || 'Título não informado';
                item.appendChild(titulo);

                const autor = document.createElement('p');
                autor.textContent = 'Autor: ' + (livro.autor || 'Não informado');
                item.appendChild(autor);

                if (livro.isbn) {
                    const isbn = document.createElement('p');
                    isbn.textContent = 'ISBN: ' + livro.isbn;
                    item.appendChild(isbn);
                }

                if (livro.codigo_patrimonio) {
                    const tombo = document.createElement('p');
                    tombo.textContent = 'Tombo: ' + livro.codigo_patrimonio;
                    item.appendChild(tombo);
                }

                if (livro.status) {
                    const status = document.createElement('p');
                    status.textContent = 'Status: ' + livro.status;
                    item.appendChild(status);
                }

                resultados.appendChild(item);
            });

        } catch (erro) {
            if (erro.name !== 'AbortError') {
                console.error('Erro na busca:', erro);
                resultados.textContent = 'Erro ao buscar os livros. Tente novamente.';
            }
        } finally {
            botao.disabled = false;
        }
    }

    campo.addEventListener('input', () => {
        clearTimeout(temporizador);
        temporizador = setTimeout(buscarAcervo, 250);
    });

    botao.addEventListener('click', buscarAcervo);

    campo.addEventListener('keydown', evento => {
        if (evento.key === 'Enter') {
            evento.preventDefault();
            clearTimeout(temporizador);
            buscarAcervo();
        }
    });
})();
</script>
</body>
</html>