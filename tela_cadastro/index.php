<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se há um Administrador logado
$ehAdminLogado = isset($_SESSION['cargo']) && strtolower($_SESSION['cargo']) === 'administrador';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <link rel="stylesheet" href="style.css">


    <title>Entre Páginas | Biblioteca</title>

    


    
</head>
<body>

    <main class="pagina">
        <header class="cabecalho">
            <div class="logo">Entre <span>Páginas</span></div>
            <p>Seu próximo capítulo começa aqui.</p>
        </header>

        <section class="flip-card" id="cartao">
            <div class="flip-card-inner" id="cartaoInterno">

                <!-- FRENTE: LOGIN -->
                <section class="face face-front">
                    <div class="conteudo">
                        <div class="icone-livro">📚</div>

                        <h1>Bem-vindo de volta!</h1>
                        <p class="subtitulo">
                            Entre na sua conta e continue sua jornada literária.
                        </p>

                        <form id="formLogin">
                            <div class="campo">
                                <label for="cpfLogin">CPF</label>
                                <input
                                    type="text"
                                    id="cpfLogin"
                                    name="cpf"
                                    placeholder="Digite seu CPF"
                                    maxlength="14"
                                    autocomplete="username"
                                    required
                                >
                            </div>

                            <div class="campo">
                                <label for="senhaLogin">Senha</label>
                                <div class="senha-container">
                                    <input
                                        type="password"
                                        id="senhaLogin"
                                        name="senha"
                                        placeholder="Digite sua senha"
                                        autocomplete="current-password"
                                        required
                                    >
                                    <button
                                        type="button"
                                        class="btn-olho"
                                        data-target="senhaLogin"
                                        aria-label="Mostrar senha"
                                    >◉</button>
                                </div>
                            </div>

                            <p id="mensagemLogin" class="mensagem" role="status"></p>

                            <button type="submit" class="btn-principal">
                                Entrar
                            </button>
                        </form>

                        <p class="troca-tela">
                            Ainda não tem uma conta?
                            <button type="button" class="link-tela" id="abrirCadastro">
                                Cadastre-se
                            </button>
                        </p>
                    </div>
                </section>

                <!-- VERSO: CADASTRO -->
                <section class="face face-back">
                    <div class="conteudo">
                        <div class="icone-livro">✨</div>

                        <h1>Crie sua conta</h1>
                        <p class="subtitulo">
                            Faça parte da comunidade Entre Páginas.
                        </p>

                        <form id="formCadastro">

                            <div class="campo">
                                <label for="tipo">Tipo de cadastro</label>
                                <select id="tipo" name="tipo" required>
                                    <option value="usuario">Usuário</option>
                                    <option value="funcionario">Funcionário</option>
                                </select>
                            </div>

                            <div class="campo" id="campoCargo" hidden>
                                <label for="cargo">Cargo</label>
                                <select id="cargo" name="cargo">
                                    <option value="">Selecione seu cargo</option>
                                    <option value="bibliotecario">Bibliotecário(a)</option>
                                    <option value="atendente">Atendente</option>
                                    <option value="administrador">Administrador(a)</option>
                                </select>
                            </div>

                            <div class="campo">
                                <label for="nome">Nome completo</label>
                                <input
                                    type="text"
                                    id="nome"
                                    name="nome"
                                    placeholder="Seu nome completo"
                                    autocomplete="name"
                                    required
                                >
                            </div>

                            <div class="campo">
                                <label for="cpf">CPF</label>
                                <input
                                    type="text"
                                    id="cpf"
                                    name="cpf"
                                    placeholder="000.000.000-00"
                                    maxlength="14"
                                    inputmode="numeric"
                                    required
                                >
                            </div>

                            <div class="campo">
                                <label for="dataNascimento">Data de nascimento</label>
                                <input
                                    type="date"
                                    id="dataNascimento"
                                    name="dataNascimento"
                                    required
                                >
                            </div>

                            <!-- RESPONSÁVEL LEGAL -->
                            <div id="secaoResponsavel" class="secao-responsavel" hidden>
                                <h2>Dados do responsável legal</h2>
                                <p>Como você é menor de 18 anos, preencha os dados do responsável.</p>

                                <div class="campo">
                                    <label for="nomeResponsavel">Nome do responsável</label>
                                    <input
                                        type="text"
                                        id="nomeResponsavel"
                                        name="nomeResponsavel"
                                        placeholder="Nome completo"
                                    >
                                </div>

                                <div class="campo">
                                    <label for="cpfResponsavel">CPF do responsável</label>
                                    <input
                                        type="text"
                                        id="cpfResponsavel"
                                        name="cpfResponsavel"
                                        placeholder="000.000.000-00"
                                        maxlength="14"
                                        inputmode="numeric"
                                    >
                                </div>

                                <div class="campo">
                                    <label for="emailResponsavel">E-mail do responsável</label>
                                    <input
                                        type="email"
                                        id="emailResponsavel"
                                        name="emailResponsavel"
                                        placeholder="responsavel@email.com"
                                    >
                                </div>

                                <div class="campo">
                                    <label for="celularResponsavel">Celular do responsável</label>
                                    <input
                                        type="tel"
                                        id="celularResponsavel"
                                        name="celularResponsavel"
                                        placeholder="(11) 99999-9999"
                                        maxlength="15"
                                        inputmode="numeric"
                                    >
                                </div>
                            </div>

                            <div class="campo">
                                <label for="email">E-mail</label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    placeholder="seuemail@email.com"
                                    autocomplete="email"
                                    required
                                >
                            </div>

                            <div class="campo">
                                <label for="celular">Celular</label>
                                <input
                                    type="tel"
                                    id="celular"
                                    name="celular"
                                    placeholder="(11) 99999-9999"
                                    maxlength="15"
                                    inputmode="numeric"
                                    required
                                >
                            </div>

                            <div class="campo">
                                <label for="endereco">Endereço</label>
                                <input
                                    type="text"
                                    id="endereco"
                                    name="endereco"
                                    placeholder="Rua, número e complemento"
                                    autocomplete="street-address"
                                    required
                                >
                            </div>

                            <div class="campo">
                                <label for="bairro">Bairro</label>
                                <input
                                    type="text"
                                    id="bairro"
                                    name="bairro"
                                    placeholder="Seu bairro"
                                    required
                                >
                            </div>

                            <div class="campo">
                                <label for="cidade">Cidade</label>
                                <input
                                    type="text"
                                    id="cidade"
                                    name="cidade"
                                    placeholder="Sua cidade"
                                    autocomplete="address-level2"
                                    required
                                >
                            </div>

                            <div class="campo">
                                <label for="senha">Crie uma senha</label>

                                <div class="senha-container">
                                    <input
                                        type="password"
                                        id="senha"
                                        name="senha"
                                        placeholder="Crie uma senha segura"
                                        autocomplete="new-password"
                                        required
                                    >
                                    <button
                                        type="button"
                                        class="btn-olho"
                                        data-target="senha"
                                        aria-label="Mostrar senha"
                                    >◉</button>
                                </div>

                                <div class="requisitos">
                                    <p id="tamanho">○ Pelo menos 8 caracteres</p>
                                    <p id="maiuscula">○ Uma letra maiúscula</p>
                                    <p id="minuscula">○ Uma letra minúscula</p>
                                    <p id="numero">○ Um número</p>
                                    <p id="especial">○ Um caractere especial</p>
                                </div>
                            </div>

                            <div class="campo">
                                <label for="confirmarSenha">Confirme sua senha</label>

                                <div class="senha-container">
                                    <input
                                        type="password"
                                        id="confirmarSenha"
                                        name="confirmarSenha"
                                        placeholder="Digite a senha novamente"
                                        autocomplete="new-password"
                                        required
                                    >
                                    <button
                                        type="button"
                                        class="btn-olho"
                                        data-target="confirmarSenha"
                                        aria-label="Mostrar senha"
                                    >◉</button>
                                </div>
                            </div>

                            <p id="mensagemCadastro" class="mensagem" role="status"></p>

                            <button type="submit" class="btn-principal">
                                Criar minha conta
                            </button>
                        </form>

                        <p class="troca-tela">
                            Já tem uma conta?
                            <button type="button" class="link-tela" id="abrirLogin">
                                Fazer login
                            </button>
                        </p>
                    </div>
                </section>

            </div>
        </section>

        <footer>
            <p>Entre Páginas © 2026 · Uma biblioteca de possibilidades.</p>
        </footer>
    </main>
  
    <script src="tela3.js"></script>

</body>
</html>