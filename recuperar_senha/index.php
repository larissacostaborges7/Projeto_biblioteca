
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar senha | Entre Páginas</title>
    <link rel="stylesheet" href="../tela_cadastro/style.css">
    <style>
        .recuperacao {
            width: min(100% - 32px, 430px);
            margin: 50px auto;
            padding: 28px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 5px 24px rgba(0, 0, 0, .10);
            box-sizing: border-box;
        }

        .recuperacao .campo {
            margin: 20px 0;
        }

        .recuperacao input,
        .recuperacao select {
            width: 100%;
            box-sizing: border-box;
        }

        .voltar {
            display: block;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <main class="recuperacao">
        <div class="icone-livro" aria-hidden="true">🔐</div>

        <h1>Recuperar senha</h1>
        <p>Informe o tipo da sua conta e o e-mail cadastrado na biblioteca.</p>

        <form id="formRecuperacao">
            <div class="campo">
                <label for="tipo_conta">Tipo de conta</label>
                <select id="tipo_conta" name="tipo_conta" required>
                    <option value="usuario">Usuário da biblioteca</option>
                    <option value="funcionario">Funcionário</option>
                </select>
            </div>

            <div class="campo">
                <label for="email">E-mail cadastrado</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    autocomplete="email"
                    placeholder="seuemail@exemplo.com"
                    maxlength="150"
                    required
                >
            </div>

            <p id="mensagemRecuperacao" role="status" aria-live="polite"></p>

            <button type="submit" class="btn-principal">
                Solicitar recuperação
            </button>
        </form>

        <a class="voltar" href="../tela_cadastro/index.php">
            Voltar para o login
        </a>
    </main>

    <script>
        const formRecuperacao = document.getElementById('formRecuperacao');
        const mensagemRecuperacao = document.getElementById('mensagemRecuperacao');

        formRecuperacao.addEventListener('submit', async function (evento) {
            evento.preventDefault();

            mensagemRecuperacao.textContent = 'Processando solicitação...';

            try {
                const resposta = await fetch('solicitar.php', {
                    method: 'POST',
                    body: new FormData(formRecuperacao)
                });

                const dados = await resposta.json();

                mensagemRecuperacao.textContent = dados.mensagem
                    || 'Se os dados corresponderem a uma conta, você receberá instruções por e-mail.';
            } catch (erro) {
                mensagemRecuperacao.textContent =
                    'Não foi possível processar a solicitação. Tente novamente.';
            }
        });
    </script>
</body>
</html>
