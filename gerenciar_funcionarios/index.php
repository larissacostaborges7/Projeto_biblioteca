
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verifica se o funcionário está logado.
if (!isset($_SESSION['id_funcionario'])) {
    header('Location: ../tela_cadastro/index.php');
    exit;
}

// 2. Somente Administrador pode acessar esta página.
if (($_SESSION['cargo'] ?? '') !== 'Administrador') {
    http_response_code(403);
    exit('Acesso negado. Somente o Administrador pode gerenciar funcionários.');
}

// 3. Cria o token de segurança do formulário.
if (empty($_SESSION['csrf_funcionarios'])) {
    $_SESSION['csrf_funcionarios'] = bin2hex(random_bytes(32));
}

// 4. Recupera mensagens temporárias.
$erro = $_SESSION['erro_cadastro_funcionario'] ?? '';
$sucesso = $_GET['status'] ?? '';

unset($_SESSION['erro_cadastro_funcionario']);

function escapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gerenciar Funcionários | Entre Páginas</title>

    <link rel="stylesheet" href="../tela_principal2/style.css">
    <link rel="stylesheet" href="style.css?v=2">
</head>

<body>

<header class="painel-topo">
    <div>
        <h1>Biblioteca Municipal de Arujá</h1>
        <p>Professora Alda Martins Soncini</p>
    </div>

    <div class="funcionario-info">
        <span>
            Olá,
            <?= escapar($_SESSION['nome_funcionario'] ?? 'Administrador') ?>
        </span>

        <strong>Administrador</strong>

        <a href="../includes/logout.php">Sair</a>
    </div>
</header>

<main class="gerenciar-container">

    <div class="gerenciar-cabecalho">
        <div>
            <h2>Gerenciar funcionários</h2>
            <p>
                Gerencie a equipe e os acessos ao sistema da biblioteca.
            </p>
        </div>

        <a class="botao-voltar" href="../painel_funcionario/index.php">
            Voltar ao painel
        </a>
    </div>

    <?php if ($sucesso === 'sucesso'): ?>
        <div class="mensagem-sucesso" role="status">
            Funcionário cadastrado com sucesso!
        </div>
    <?php endif; ?>

    <?php if ($erro !== ''): ?>
        <div class="mensagem-erro" role="alert">
            <?= escapar($erro) ?>
        </div>
    <?php endif; ?>

    <section class="funcionarios-aviso">
        <h3>Cadastro de funcionário</h3>

        <p>
            Preencha os dados abaixo para cadastrar um novo integrante
            da equipe da biblioteca.
        </p>

        <form
            action="salvar.php"
            method="POST"
            id="formFuncionario"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= escapar($_SESSION['csrf_funcionarios']) ?>"
            >

            <div class="campo-formulario">
                <label for="nome">Nome completo *</label>
                <input
                    type="text"
                    id="nome"
                    name="nome"
                    maxlength="150"
                    autocomplete="name"
                    required
                >
            </div>

            <div class="campo-formulario">
                <label for="cpf">CPF *</label>
                <input
                    type="text"
                    id="cpf"
                    name="cpf"
                    placeholder="000.000.000-00"
                    maxlength="14"
                    inputmode="numeric"
                    autocomplete="off"
                    required
                >
            </div>

            <div class="campo-formulario">
                <label for="data_nascimento">Data de nascimento</label>
                <input
                    type="date"
                    id="data_nascimento"
                    name="data_nascimento"
                    autocomplete="bday"
                >
            </div>

            <div class="campo-formulario">
                <label for="email">E-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="150"
                    autocomplete="email"
                >
            </div>

            <div class="campo-formulario">
                <label for="telefone">Telefone</label>
                <input
                    type="tel"
                    id="telefone"
                    name="telefone"
                    maxlength="20"
                    autocomplete="tel"
                >
            </div>

            <div class="campo-formulario">
                <label for="cep">CEP</label>
                <input
                    type="text"
                    id="cep"
                    name="cep"
                    maxlength="9"
                    placeholder="00000-000"
                    autocomplete="postal-code"
                >
            </div>

            <div class="campo-formulario">
                <label for="endereco">Endereço</label>
                <input
                    type="text"
                    id="endereco"
                    name="endereco"
                    maxlength="255"
                    autocomplete="street-address"
                >
            </div>

            <div class="campo-formulario">
                <label for="numero">Número</label>
                <input
                    type="text"
                    id="numero"
                    name="numero"
                    maxlength="20"
                >
            </div>

            <div class="campo-formulario">
                <label for="bairro">Bairro</label>
                <input
                    type="text"
                    id="bairro"
                    name="bairro"
                    maxlength="100"
                >
            </div>

            <div class="campo-formulario">
                <label for="cidade">Cidade</label>
                <input
                    type="text"
                    id="cidade"
                    name="cidade"
                    maxlength="100"
                    autocomplete="address-level2"
                >
            </div>

            <div class="campo-formulario">
                <label for="cargo">Cargo *</label>
                <select id="cargo" name="cargo" required>
                    <option value="">Selecione o cargo</option>
                    <option value="Administrador">Administrador</option>
                    <option value="Acervo">Acervo</option>
                    <option value="Atendente">Atendente</option>
                </select>
            </div>

            
<div class="campo-formulario">
    <label for="senha">Senha provisória *</label>

    <div class="senha-container">
        <input
            type="password"
            id="senha"
            name="senha"
            minlength="8"
            maxlength="1024"
            autocomplete="new-password"
            required
        >

        <button type="button" class="botao-olho" data-toggle-senha="senha"
            aria-label="Mostrar senha">
            Mostrar
        </button>
    </div>

    <ul class="requisitos-senha" id="requisitosSenha">
        <li data-regra="tamanho">✗ Pelo menos 8 caracteres</li>
        <li data-regra="maiuscula">✗ Uma letra maiúscula</li>
        <li data-regra="minuscula">✗ Uma letra minúscula</li>
        <li data-regra="numero">✗ Um número</li>
        <li data-regra="especial">✗ Um caractere especial</li>
    </ul>
</div>

<div class="campo-formulario">
    <label for="confirmar_senha">Confirmar senha *</label>

    <div class="senha-container">
        <input
            type="password"
            id="confirmar_senha"
            name="confirmar_senha"
            minlength="8"
            maxlength="1024"
            autocomplete="new-password"
            required
        >

        <button type="button" class="botao-olho"
            data-toggle-senha="confirmar_senha"
            aria-label="Mostrar confirmação de senha">
            Mostrar
        </button>
    </div>

    <p id="mensagemConfirmacao" class="mensagem-confirmacao" aria-live="polite">
        Digite a senha nos dois campos para confirmar.
    </p>
</div>


            <button type="submit" class="botao-cadastrar">
                Cadastrar funcionário
            </button>
        </form>
    </section>

</main>
<script src="script.js" defer></script>
</body>
</html>
