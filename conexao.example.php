
<?php
$servidor = "localhost";
$banco = "biblioteca_aruja";
$usuario = "root";
$senha = "";

try {
    $conexao = new PDO(
        "mysql:host=$servidor;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha
    );

    $conexao->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    echo "Conexão realizada com sucesso!";
} catch (PDOException $erro) {
    die("Erro na conexão: " . $erro->getMessage());
}
?>


<?php
$servidor = "localhost";
$banco = "biblioteca_aruja";
$usuario = "root";
$senha = "";

try {
    $conexao = new PDO(
        "mysql:host=$servidor;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha
    );

    $conexao->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $sql = "SELECT titulo, autor FROM livros ORDER BY titulo";
    $consulta = $conexao->query($sql);
    $livros = $consulta->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>Livros da Biblioteca</h1>";

    foreach ($livros as $livro) {
        echo "<p>";
        echo htmlspecialchars($livro['titulo']);
        echo " — ";
        echo htmlspecialchars($livro['autor']);
        echo "</p>";
    }

} catch (PDOException $erro) {
    die("Erro: " . $erro->getMessage());
}
?>