
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['id_funcionario']) ||
    ($_SESSION['tipo'] ?? '') !== 'funcionario'
) {
    header('Location: ../tela_cadastro/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar leitor | Biblioteca de Arujá</title>
    <link rel="stylesheet" href="../tela_principal2/style.css">
   <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="pagina">
<header class="cabecalho">
    <div class="logo">📚 Biblioteca <span>Entre Páginas</span></div>
    <p>Biblioteca Municipal de Arujá — Professora Alda Martins Soncini</p>
</header>

<main class="cadastro-presencial">
    <h1>Cadastro presencial de leitor</h1>
    <p>Preencha os dados da pessoa atendida na biblioteca.</p>

    <div class="aviso">
        Confira os dados com o leitor antes de salvar. Para menores de
        18 anos, será necessário informar os dados do responsável legal.
    </div>

    <form id="formCadastro" novalidate>
        <input type="hidden" name="tipo" value="usuario">

        <h2>Dados do leitor</h2>

        <div class="grupo-campos">
            <div class="campo completo">
                <label for="nome">Nome completo *</label>
                <input id="nome" name="nome" required maxlength="150">
            </div>

            <div class="campo">
                <label for="cpf">CPF *</label>
                <input id="cpf" name="cpf" required inputmode="numeric"
                       maxlength="14" placeholder="000.000.000-00">
            </div>

            <div class="campo">
                <label for="dataNascimento">Data de nascimento *</label>
                <input id="dataNascimento" name="dataNascimento"
                       type="date" required>
            </div>

            <div class="campo">
                <label for="email">E-mail do leitor *</label>
                <input id="email" name="email" type="email" required>
            </div>

            <div class="campo">
                <label for="celular">Celular *</label>
                <input id="celular" name="celular" required
                       inputmode="tel" maxlength="15">
            </div>

            <div class="campo completo">
                <label for="endereco">Endereço *</label>
                <input id="endereco" name="endereco" required>
            </div>

            <div class="campo">
                <label for="bairro">Bairro *</label>
                <input id="bairro" name="bairro" required>
            </div>

            <div class="campo">
                <label for="cidade">Cidade *</label>
                <input id="cidade" name="cidade" required>
            </div>
        </div>

        <section id="secaoResponsavel" hidden>
            <h2>Dados do responsável legal</h2>

            <div class="grupo-campos">
                <div class="campo completo">
                    <label for="nomeResponsavel">Nome completo do responsável *</label>
                    <input id="nomeResponsavel" name="nomeResponsavel">
                </div>

                <div class="campo">
                    <label for="cpfResponsavel">CPF do responsável *</label>
                    <input id="cpfResponsavel" name="cpfResponsavel"
                           inputmode="numeric" maxlength="14">
                </div>

                <div class="campo">
                    <label for="emailResponsavel">E-mail do responsável *</label>
                    <input id="emailResponsavel" name="emailResponsavel"
                           type="email">
                </div>

                <div class="campo completo">
                    <label for="celularResponsavel">Celular do responsável *</label>
                    <input id="celularResponsavel" name="celularResponsavel"
                           inputmode="tel" maxlength="15">
                </div>
            </div>
        </section>

       
<h2>Senha de acesso ao site</h2>
<p>Crie uma senha que atenda a todos os requisitos abaixo.</p>

<div class="grupo-campos">
    <div class="campo">
        <label for="senha">Senha inicial *</label>

        <div class="senha-container">
            <input
                id="senha"
                name="senha"
                type="password"
                autocomplete="new-password"
                required
            >
            <button
                type="button"
                class="alternar-senha"
                data-alvo="senha"
                aria-label="Mostrar senha"
                aria-pressed="false"
            >👁</button>
        </div>
    </div>

    <div class="campo">
        <label for="confirmarSenha">Confirmar senha *</label>

        <div class="senha-container">
            <input
                id="confirmarSenha"
                name="confirmarSenha"
                type="password"
                autocomplete="new-password"
                required
            >
            <button
                type="button"
                class="alternar-senha"
                data-alvo="confirmarSenha"
                aria-label="Mostrar confirmação da senha"
                aria-pressed="false"
            >👁</button>
        </div>

        <small id="mensagemConfirmacao" aria-live="polite"></small>
    </div>
</div>

<div class="requisitos-senha">
    <p>A senha precisa ter:</p>

    <ul>
        <li id="reqTamanho">○ Pelo menos 8 caracteres</li>
        <li id="reqMaiuscula">○ Uma letra maiúscula</li>
        <li id="reqMinuscula">○ Uma letra minúscula</li>
        <li id="reqNumero">○ Um número</li>
        <li id="reqEspecial">○ Um caractere especial</li>
    </ul>
</div>


        <div class="acoes">
            <button type="submit">Cadastrar leitor</button>
            <a href="../painel/servidor.php">Voltar ao painel</a>
        </div>

        <p id="mensagem" role="status" aria-live="polite"></p>
    </form>
</main>

<script src="script.js" defer></script>

</body>
</html>
