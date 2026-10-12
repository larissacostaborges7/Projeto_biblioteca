
<?php

require_once __DIR__ . '/../includes/proteger_acesso.php';

exigirCargo(['Administrador']);

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/registrar_log.php';

$mensagem = '';
$tipoMensagem = '';

$nome = '';
$cpf = '';
$dataNascimento = '';
$email = '';
$telefone = '';
$cep = '';
$endereco = '';
$numero = '';
$bairro = '';
$cidade = '';
$cargo = '';

$cargosPermitidos = [
    'Administrador',
    'Acervo',
    'Atendente'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $dataNascimento = trim($_POST['data_nascimento'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $cep = trim($_POST['cep'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';
    $cargo = $_POST['cargo'] ?? '';

    // Validar campos obrigatórios
    if (
        $nome === '' ||
        $cpf === '' ||
        $dataNascimento === '' ||
        $email === '' ||
        $senha === '' ||
        $confirmarSenha === '' ||
        !in_array($cargo, $cargosPermitidos, true)
    ) {
        $mensagem = 'Preencha todos os campos obrigatórios.';

    // Validar tamanho dos campos
    } elseif (
        strlen($nome) > 150 ||
        strlen($cpf) > 14 ||
        strlen($email) > 150 ||
        strlen($telefone) > 20 ||
        strlen($cep) > 9 ||
        strlen($endereco) > 255 ||
        strlen($numero) > 20 ||
        strlen($bairro) > 100 ||
        strlen($cidade) > 100
    ) {
        $mensagem = 'Um ou mais campos ultrapassam o tamanho permitido.';

    // Validar e-mail
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'Informe um e-mail válido.';

    // Confirmar senha
    } elseif ($senha !== $confirmarSenha) {
        $mensagem = 'As senhas não coincidem.';

    // Validar data de nascimento
    } else {
        $dataValida = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $dataNascimento
        );

        $errosData = DateTimeImmutable::getLastErrors();

        if (
            !$dataValida ||
            $dataValida->format('Y-m-d') !== $dataNascimento ||
            (
                $errosData !== false &&
                (
                    $errosData['warning_count'] > 0 ||
                    $errosData['error_count'] > 0
                )
            ) ||
            $dataValida > new DateTimeImmutable('today')
        ) {
            $mensagem = 'Informe uma data de nascimento válida.';
        }
    }

    // Validar força da senha
    if ($mensagem === '') {
        if (
            strlen($senha) < 8 ||
            !preg_match('/[A-Z]/', $senha) ||
            !preg_match('/[a-z]/', $senha) ||
            !preg_match('/[0-9]/', $senha) ||
            !preg_match('/[^a-zA-Z0-9]/', $senha)
        ) {
            $mensagem = 'A senha deve ter pelo menos 8 caracteres, '
                . 'uma letra maiúscula, uma minúscula, '
                . 'um número e um caractere especial.';
        }
    }

    // Salvar cadastro e auditoria na mesma transação
    if ($mensagem === '') {
        try {
            $hash = password_hash($senha, PASSWORD_DEFAULT);

            $conexao->beginTransaction();

            $sql = "INSERT INTO funcionarios
                (
                    nome, cpf, data_nascimento, email,
                    telefone, cep, endereco, numero,
                    bairro, cidade, senha, cargo
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conexao->prepare($sql);

            $stmt->execute([
                $nome,
                $cpf,
                $dataNascimento,
                $email,
                $telefone !== '' ? $telefone : null,
                $cep !== '' ? $cep : null,
                $endereco !== '' ? $endereco : null,
                $numero !== '' ? $numero : null,
                $bairro !== '' ? $bairro : null,
                $cidade !== '' ? $cidade : null,
                $hash,
                $cargo
            ]);

            $idNovoFuncionario = (int) $conexao->lastInsertId();

            registrarLog(
                $conexao,
                'CADASTRO',
                'funcionarios',
                $idNovoFuncionario,
                'Cadastro de funcionário realizado.'
            );

            $conexao->commit();

            $mensagem = 'Funcionário cadastrado com sucesso!';
            $tipoMensagem = 'sucesso';

            // Limpar os campos após o cadastro bem-sucedido
            $nome = '';
            $cpf = '';
            $dataNascimento = '';
            $email = '';
            $telefone = '';
            $cep = '';
            $endereco = '';
            $numero = '';
            $bairro = '';
            $cidade = '';
            $cargo = '';

        } catch (PDOException $erro) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }

            error_log($erro->getMessage());

            if ($erro->getCode() === '23000') {
                $mensagem = 'Não foi possível cadastrar. '
                    . 'O CPF ou e-mail pode já estar cadastrado.';
            } else {
                $mensagem = 'O cadastro não foi concluído. '
                    . 'Tente novamente.';
            }

            $tipoMensagem = 'erro';
        }
    }

    if ($mensagem !== '' && $tipoMensagem === '') {
        $tipoMensagem = 'erro';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Funcionários</title>
</head>
<body>

    <h1>Cadastro de Funcionários</h1>

    <p>
        <a href="../painel/servidor.php">Voltar ao painel</a>
    </p>

    <?php if ($mensagem !== ''): ?>
        <p role="status"
           style="color: <?= $tipoMensagem === 'sucesso' ? 'green' : 'red' ?>;">
            <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="post" action="cadastrar.php">

        <label for="nome">Nome completo:</label>
        <input
            type="text"
            id="nome"
            name="nome"
            maxlength="150"
            value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>"
            autocomplete="name"
            required
        >
        <br><br>

        <label for="cpf">CPF:</label>
        <input
            type="text"
            id="cpf"
            name="cpf"
            maxlength="14"
            placeholder="000.000.000-00"
            value="<?= htmlspecialchars($cpf, ENT_QUOTES, 'UTF-8') ?>"
            required
        >
        <br><br>

        <label for="data_nascimento">Data de nascimento:</label>
        <input
            type="date"
            id="data_nascimento"
            name="data_nascimento"
            value="<?= htmlspecialchars($dataNascimento, ENT_QUOTES, 'UTF-8') ?>"
            required
        >
        <br><br>

        <label for="email">E-mail:</label>
        <input
            type="email"
            id="email"
            name="email"
            maxlength="150"
            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
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
            placeholder="(11) 99999-9999"
            value="<?= htmlspecialchars($telefone, ENT_QUOTES, 'UTF-8') ?>"
            autocomplete="tel"
        >
        <br><br>

        <label for="cep">CEP:</label>
        <input
            type="text"
            id="cep"
            name="cep"
            maxlength="9"
            placeholder="00000-000"
            value="<?= htmlspecialchars($cep, ENT_QUOTES, 'UTF-8') ?>"
            autocomplete="postal-code"
        >
        <br><br>

        <label for="endereco">Endereço:</label>
        <input
            type="text"
            id="endereco"
            name="endereco"
            maxlength="255"
            value="<?= htmlspecialchars($endereco, ENT_QUOTES, 'UTF-8') ?>"
            autocomplete="street-address"
        >
        <br><br>

        <label for="numero">Número:</label>
        <input
            type="text"
            id="numero"
            name="numero"
            maxlength="20"
            placeholder="Ex.: 123 ou S/N"
            value="<?= htmlspecialchars($numero, ENT_QUOTES, 'UTF-8') ?>"
        >
        <br><br>

        <label for="bairro">Bairro:</label>
        <input
            type="text"
            id="bairro"
            name="bairro"
            maxlength="100"
            value="<?= htmlspecialchars($bairro, ENT_QUOTES, 'UTF-8') ?>"
        >
        <br><br>

        <label for="cidade">Cidade:</label>
        <input
            type="text"
            id="cidade"
            name="cidade"
            maxlength="100"
            value="<?= htmlspecialchars($cidade, ENT_QUOTES, 'UTF-8') ?>"
            autocomplete="address-level2"
        >
        <br><br>

        <label for="senha">Senha:</label>
        <div>
            <input
                type="password"
                id="senha"
                name="senha"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <button type="button" id="mostrarSenha">
                👁️ Mostrar senha
            </button>
        </div>

        <ul id="requisitosSenha">
            <li id="reqTamanho">○ Pelo menos 8 caracteres</li>
            <li id="reqMaiuscula">○ Uma letra maiúscula</li>
            <li id="reqMinuscula">○ Uma letra minúscula</li>
            <li id="reqNumero">○ Um número</li>
            <li id="reqEspecial">○ Um caractere especial</li>
        </ul>

        <label for="confirmar_senha">Confirmar senha:</label>
        <input
            type="password"
            id="confirmar_senha"
            name="confirmar_senha"
            autocomplete="new-password"
            required
        >

        <p id="mensagemConfirmacao" aria-live="polite"></p>
        <br>

        <label for="cargo">Cargo:</label>
        <select id="cargo" name="cargo" required>
            <option value="">Selecione o cargo</option>
            <option value="Administrador"
                <?= $cargo === 'Administrador' ? 'selected' : '' ?>>
                Administrador
            </option>
            <option value="Acervo"
                <?= $cargo === 'Acervo' ? 'selected' : '' ?>>
                Acervo
            </option>
            <option value="Atendente"
                <?= $cargo === 'Atendente' ? 'selected' : '' ?>>
                Atendente
            </option>
        </select>
        <br><br>

        <button type="submit">Cadastrar funcionário</button>

    </form>

    <script>
    const senha = document.getElementById('senha');
    const confirmarSenha = document.getElementById('confirmar_senha');
    const mostrarSenha = document.getElementById('mostrarSenha');
    const mensagemConfirmacao =
        document.getElementById('mensagemConfirmacao');

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
            const valido = requisito.verificar(senha.value);

            requisito.elemento.textContent =
                (valido ? '✓ ' : '○ ') + requisito.texto;

            requisito.elemento.style.color =
                valido ? 'green' : 'inherit';
        });
    }

    function verificarConfirmacao() {
        if (confirmarSenha.value === '') {
            mensagemConfirmacao.textContent = '';
            return;
        }

        if (senha.value === confirmarSenha.value) {
            mensagemConfirmacao.textContent =
                '✓ As senhas coincidem.';
            mensagemConfirmacao.style.color = 'green';
        } else {
            mensagemConfirmacao.textContent =
                '✗ As senhas estão diferentes.';
            mensagemConfirmacao.style.color = 'red';
        }
    }

    senha.addEventListener('input', () => {
        atualizarRequisitos();
        verificarConfirmacao();
    });

    confirmarSenha.addEventListener('input', verificarConfirmacao);

    mostrarSenha.addEventListener('click', () => {
        const mostrar = senha.type === 'password';

        senha.type = mostrar ? 'text' : 'password';
        confirmarSenha.type = mostrar ? 'text' : 'password';

        mostrarSenha.textContent = mostrar
            ? '🙈 Ocultar senha'
            : '👁️ Mostrar senha';
    });

    atualizarRequisitos();
    </script>

</body>
</html>