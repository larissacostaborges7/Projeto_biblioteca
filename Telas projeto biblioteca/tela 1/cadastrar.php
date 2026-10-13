<?php

// ===============================
// CONFIGURAÇÕES
// ===============================

header("Content-Type: application/json; charset=UTF-8");


// ===============================
// CONEXÃO COM O BANCO
// ===============================

$host = "localhost";
$banco = "biblioteca";
$usuario = "root";
$senhaBanco = "";

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senhaBanco
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $erro) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Erro ao conectar com o banco de dados."
    ]);

    exit;

}


// ===============================
// VERIFICAR SE A REQUISIÇÃO É POST
// ===============================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Requisição inválida."
    ]);

    exit;
}


// ===============================
// RECEBER OS DADOS
// ===============================

$tipo = $_POST["tipo"] ?? "";
$nome = $_POST["nome"] ?? "";
$cpf = $_POST["cpf"] ?? "";
$dataNascimento = $_POST["dataNascimento"] ?? "";
$email = $_POST["email"] ?? "";
$celular = $_POST["celular"] ?? "";
$telefone = $_POST["telefone"] ?? "";
$endereco = $_POST["endereco"] ?? "";
$bairro = $_POST["bairro"] ?? "";
$cidade = $_POST["cidade"] ?? "";
$cargo = $_POST["cargo"] ?? "";
$senha = $_POST["senha"] ?? "";


// ===============================
// LIMPAR ESPAÇOS
// ===============================

$nome = trim($nome);
$cpf = trim($cpf);
$email = trim($email);
$celular = trim($celular);
$telefone = trim($telefone);
$endereco = trim($endereco);
$bairro = trim($bairro);
$cidade = trim($cidade);
$cargo = trim($cargo);


// ===============================
// VALIDAR CAMPOS OBRIGATÓRIOS
// ===============================

if (
    empty($tipo) ||
    empty($nome) ||
    empty($cpf) ||
    empty($dataNascimento) ||
    empty($email) ||
    empty($celular) ||
    empty($endereco) ||
    empty($bairro) ||
    empty($cidade) ||
    empty($senha)
) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Preencha todos os campos obrigatórios."
    ]);

    exit;
}


// ===============================
// VALIDAR TIPO DE CADASTRO
// ===============================

if (
    $tipo !== "usuario" &&
    $tipo !== "funcionario"
) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Tipo de cadastro inválido."
    ]);

    exit;
}


// ===============================
// VALIDAR CPF
// ===============================

if (!preg_match("/^[0-9]{11}$/", $cpf)) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "CPF deve conter 11 números."
    ]);

    exit;
}


// ===============================
// VALIDAR CELULAR
// ===============================

if (!preg_match("/^[0-9]{10,11}$/", $celular)) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Celular inválido."
    ]);

    exit;
}


// ===============================
// VALIDAR TELEFONE
// ===============================

if (
    !empty($telefone) &&
    !preg_match("/^[0-9]{10,11}$/", $telefone)
) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Telefone inválido."
    ]);

    exit;
}


// ===============================
// VALIDAR EMAIL
// ===============================

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "E-mail inválido."
    ]);

    exit;
}


// ===============================
// FUNCIONÁRIO PRECISA TER CARGO
// ===============================

if ($tipo === "funcionario") {

    if (empty($cargo)) {

        echo json_encode([
            "sucesso" => false,
            "mensagem" => "Selecione o cargo do funcionário."
        ]);

        exit;
    }

    if (
        $cargo !== "atendente" &&
        $cargo !== "acervo" &&
        $cargo !== "administracao"
    ) {

        echo json_encode([
            "sucesso" => false,
            "mensagem" => "Cargo inválido."
        ]);

        exit;
    }

} else {

    // Usuário comum não possui cargo
    $cargo = null;

}


// ===============================
// VERIFICAR SE CPF JÁ EXISTE
// ===============================

$sql = "SELECT id FROM usuarios WHERE cpf = :cpf";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":cpf" => $cpf
]);

if ($stmt->fetch()) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Este CPF já está cadastrado."
    ]);

    exit;
}


// ===============================
// VERIFICAR SE EMAIL JÁ EXISTE
// ===============================

$sql = "SELECT id FROM usuarios WHERE email = :email";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":email" => $email
]);

if ($stmt->fetch()) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Este e-mail já está cadastrado."
    ]);

    exit;
}


// ===============================
// CRIPTOGRAFAR A SENHA
// ===============================

$senhaHash = password_hash(
    $senha,
    PASSWORD_DEFAULT
);


// ===============================
// INSERIR NO BANCO
// ===============================

$sql = "
    INSERT INTO usuarios (
        tipo,
        nome,
        cpf,
        data_nascimento,
        email,
        celular,
        telefone,
        endereco,
        bairro,
        cidade,
        cargo,
        senha
    )

    VALUES (
        :tipo,
        :nome,
        :cpf,
        :data_nascimento,
        :email,
        :celular,
        :telefone,
        :endereco,
        :bairro,
        :cidade,
        :cargo,
        :senha
    )
";


$stmt = $pdo->prepare($sql);


// ===============================
// EXECUTAR O CADASTRO
// ===============================

try {

    $stmt->execute([

        ":tipo" => $tipo,

        ":nome" => $nome,

        ":cpf" => $cpf,

        ":data_nascimento" => $dataNascimento,

        ":email" => $email,

        ":celular" => $celular,

        ":telefone" => $telefone ?: null,

        ":endereco" => $endereco,

        ":bairro" => $bairro,

        ":cidade" => $cidade,

        ":cargo" => $cargo,

        ":senha" => $senhaHash

    ]);


    // ===============================
    // SUCESSO
    // ===============================

    echo json_encode([

        "sucesso" => true,

        "mensagem" => "Cadastro realizado com sucesso!"

    ]);


} catch (PDOException $erro) {

    // ===============================
    // ERRO AO CADASTRAR
    // ===============================

    echo json_encode([

        "sucesso" => false,

        "mensagem" => "Erro ao realizar o cadastro."

    ]);

}

?>