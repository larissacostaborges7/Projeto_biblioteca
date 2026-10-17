/* ==========================================
   SCRIPT DA TELA DE EMPRÉSTIMOS E RESERVAS
========================================== */

document.addEventListener("DOMContentLoaded", () => {
  // Inicializa as funções da página assim que o HTML carregar
  carregarDadosEmprestimos();
});

// 1. Função para buscar e exibir empréstimos, histórico e reservas
async function carregarDadosEmprestimos() {
  try {
    const resposta = await fetch('reservas.php');
    const dados = await resposta.json();

    if (dados.sucesso) {
      // Podes atualizar os elementos HTML da tua tela_emprestimo/index.php aqui
      console.log("Dados carregados com sucesso:", dados);
    } else {
      console.warn("Aviso:", dados.mensagem);
    }
  } catch (erro) {
    console.error("Erro ao comunicar com o servidor:", erro);
  }
}

// 2. Função para processar a devolução de um livro
async function realizarDevolucao(idEmprestimo) {
  if (!confirm("Tens a certeza de que pretendes devolver este livro?")) return;

  const formData = new FormData();
  formData.append('id_emprestimo', idEmprestimo);

  try {
    const resposta = await fetch('devolver.php', {
      method: 'POST',
      body: formData
    });
    const dados = await resposta.json();

    if (dados.sucesso) {
      alert("Devolução registada com sucesso!");
      location.reload(); // Atualiza a página para refletir as alterações
    } else {
      alert("Erro ao devolver: " + dados.mensagem);
    }
  } catch (erro) {
    console.error("Erro na requisição de devolução:", erro);
  }
}