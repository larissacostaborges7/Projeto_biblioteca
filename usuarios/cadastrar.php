<?php

require_once __DIR__ . '/../includes/conexao.php';

$mensagem = '';
$nome = '';
$cpf = '';
$nascimento = '';
$email = '';
$cadastroSucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $nascimento = $_POST['data_nascimento'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
$bairro = trim($_POST['bairro'] ?? '');
$cidade = trim($_POST['cidade'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmacao = $_POST['confirmacao'] ?? '';
}
    if (
        $nome === '' || $cpf === '' || $nascimento === '' ||
        $email === '' || $senha === '' || $confirmacao === ''
    ) {
        $mensagem = 'Preencha todos os campos obrigatórios.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'Informe um e-mail válido.';

    } elseif (
        strlen($senha) < 8 ||
        !preg_match('/[A-Z]/', $senha) ||
        !preg_match('/[a-z]/', $senha) ||
        !preg_match('/[0-9]/', $senha) ||
        !preg_match('/[^a-zA-Z0-9]/', $senha)
    ) {
        $mensagem = 'A senha não atende a todos os requisitos.';

    } elseif ($senha !== $confirmacao) {
        $mensagem = 'As senhas não coincidem. Verifique e tente novamente.';

    } else {
        try {
            $hash = password_hash($senha, PASSWORD_DEFAULT);

$sql = "INSERT INTO usuarios
        (nome, cpf, data_nascimento, email, senha,
         telefone, endereco, numero, bairro, cidade)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conexao->prepare($sql);

$stmt->execute([
    $nome,
    $cpf,
    $nascimento,
    $email,
    $hash,
    $telefone ?: null,
    $endereco ?: null,
    $numero ?: null,
    $bairro ?: null,
    $cidade ?: null
]);

            $mensagem = 'Cadastro realizado com sucesso! Você já pode entrar.';
            $cadastroSucesso = true;

} catch (PDOException $erro) {
    if ($erro->getCode() === '23000') {
        $mensagem = 'Este CPF ou e-mail já está cadastrado.';
    } else {
        error_log('Erro no cadastro de usuário: ' . $erro->getMessage());
        $mensagem = 'Erro no cadastro. Confira o log de erros do PHP.';
    }
}
    }

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../asserts/style.css/cadastro.css">

    <title>Cadastro de Leitor</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 30px auto;
            padding: 20px;
        }

        input {
            box-sizing: border-box;
            width: 100%;
            padding: 10px;
            margin-top: 5px;
        }

        .campo-senha {
            display: flex;
            gap: 8px;
        }

        .campo-senha input {
            flex: 1;
            min-width: 0;
        }

        .campo-senha button {
            margin-top: 5px;
            cursor: pointer;
        }

        .requisitos {
            padding-left: 20px;
            line-height: 1.8;
        }

        .pendente {
            color: #666;
        }

        .atendido {
            color: #16803c;
        }

        .erro {
            color: #b42318;
        }

        .sucesso {
            color: #16803c;
        }

        button {
            padding: 10px 14px;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <h1>Cadastro de Leitor</h1>

    <?php if ($mensagem !== ''): ?>
        <p class="<?= $cadastroSucesso ? 'sucesso' : 'erro' ?>"
           role="status">
            <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="post" action="cadastrar.php">

        <label for="nome">Nome completo: *</label>
        <input
            type="text"
            id="nome"
            name="nome"
            maxlength="150"
            autocomplete="name"
            required
        >

        <br><br>

        <label for="cpf">CPF: *</label>
        <input
            type="text"
            id="cpf"
            name="cpf"
            maxlength="14"
            placeholder="000.000.000-00"
            required
        >

        <br><br>

        <label for="data_nascimento">Data de nascimento: *</label>
        <input
            type="date"
            id="data_nascimento"
            name="data_nascimento"
            required
        >

        <br><br>

        <label for="email">E-mail: *</label>
        <input
            type="email"
            id="email"
            name="email"
            maxlength="150"
            autocomplete="email"
            required
        >

        <br><br>

        <label for="telefone">Telefone:</label>
        <input
            type="tel"
            id="telefone"
            name="telefone"
            maxlength="20"
            autocomplete="tel"
        >

        <br><br>

        <label for="endereco">Endereço:</label>
        <input
            type="text"
            id="endereco"
            name="endereco"
            maxlength="255"
            autocomplete="street-address"
        >

        <br><br>

<label for="numero">Número:</label><br>
<input
    type="text"
    id="numero"
    name="numero"
    maxlength="10"
>
<br><br>

<label for="bairro">Bairro:</label><br>
<input
    type="text"
    id="bairro"
    name="bairro"
    maxlength="100"
>
<br><br>

<label for="cidade">Cidade:</label>
<input
    type="text"
    id="cidade"
    name="cidade"
    maxlength="100"
>
        <br><br>

        <label for="senha">Senha: *</label>

        <div class="campo-senha">
            <input
                type="password"
                id="senha"
                name="senha"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <button type="button" id="mostrarSenha">
                Mostrar
            </button>
        </div>

        <p>A senha deve cumprir todos os requisitos:</p>

        <ul class="requisitos">
            <li id="reqTamanho" class="pendente">
                ○ Pelo menos 8 caracteres
            </li>
            <li id="reqMaiuscula" class="pendente">
                ○ Uma letra maiúscula
            </li>
            <li id="reqMinuscula" class="pendente">
                ○ Uma letra minúscula
            </li>
            <li id="reqNumero" class="pendente">
                ○ Um número
            </li>
            <li id="reqEspecial" class="pendente">
                ○ Um caractere especial
            </li>
        </ul>

        <br>

        <label for="confirmacao">Confirme a senha: *</label>

        <div class="campo-senha">
            <input
                type="password"
                id="confirmacao"
                name="confirmacao"
                minlength="8"
                autocomplete="new-password"
                required
            >
        </div>

        <p id="mensagemSenha" aria-live="polite"></p>

        <button type="submit">Cadastrar</button>

    </form>

    <p>
        <a href="../login/usuario.php">Já tenho cadastro — entrar</a>
    </p>

    <p>
        <a href="../index.php">Voltar ao início</a>
    </p>

    <script>
        const senha = document.getElementById('senha');
        const confirmacao = document.getElementById('confirmacao');
        const mostrarSenha = document.getElementById('mostrarSenha');
        const mensagemSenha = document.getElementById('mensagemSenha');

        const requisitos = [
            {
                elemento: document.getElementById('reqTamanho'),
                verificar: valor => valor.length >= 8,
                texto: 'Pelo menos 8 caracteres'
            },
            {
                elemento: document.getElementById('reqMaiuscula'),
                verificar: valor => /[A-Z]/.test(valor),
                texto: 'Uma letra maiúscula'
            },
            {
                elemento: document.getElementById('reqMinuscula'),
                verificar: valor => /[a-z]/.test(valor),
                texto: 'Uma letra minúscula'
            },
            {
                elemento: document.getElementById('reqNumero'),
                verificar: valor => /[0-9]/.test(valor),
                texto: 'Um número'
            },
            {
                elemento: document.getElementById('reqEspecial'),
                verificar: valor => /[^a-zA-Z0-9]/.test(valor),
                texto: 'Um caractere especial'
            }
        ];

        function atualizarRequisitos() {
            requisitos.forEach(requisito => {
                const atendido = requisito.verificar(senha.value);

                requisito.elemento.textContent =
                    (atendido ? '✓ ' : '○ ') + requisito.texto;

                requisito.elemento.classList.toggle(
                    'atendido',
                    atendido
                );

                requisito.elemento.classList.toggle(
                    'pendente',
                    !atendido
                );
            });
        }

        function conferirSenhas() {
            if (confirmacao.value === '') {
                mensagemSenha.textContent = '';
                mensagemSenha.className = '';
                return;
            }

            if (senha.value === confirmacao.value) {
                mensagemSenha.textContent = '✓ As senhas coincidem.';
                mensagemSenha.className = 'sucesso';
            } else {
                mensagemSenha.textContent =
                    'As senhas não coincidem. Verifique e tente novamente.';
                mensagemSenha.className = 'erro';
            }
        }

        senha.addEventListener('input', () => {
            atualizarRequisitos();
            conferirSenhas();
        });

        confirmacao.addEventListener('input', conferirSenhas);

        mostrarSenha.addEventListener('click', () => {
            const exibir = senha.type === 'password';

            senha.type = exibir ? 'text' : 'password';
            confirmacao.type = exibir ? 'text' : 'password';

            mostrarSenha.textContent = exibir ? 'Ocultar' : 'Mostrar';
        });

        atualizarRequisitos();
    </script>

</body>
</html>
