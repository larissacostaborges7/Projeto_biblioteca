
<?php
session_start();

require_once __DIR__ . '/../includes/conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login/usuario.php');
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

$sql = "SELECT
            em.id_emprestimo,
            l.titulo,
            ex.codigo_patrimonio,
            em.data_emprestimo,
            em.data_prevista_devolucao,
            em.data_devolucao,
            em.status
        FROM emprestimos em
        INNER JOIN exemplares ex
            ON em.id_exemplar = ex.id_exemplar
        INNER JOIN livros l
            ON ex.id_livro = l.id_livro
        WHERE em.id_usuario = ?
        ORDER BY em.id_emprestimo DESC";

$stmt = $conexao->prepare($sql);
$stmt->execute([$id_usuario]);
$emprestimos = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatarData($data) {
    if (empty($data)) {
        return 'Não registrada';
    }

    return date('d/m/Y', strtotime($data));
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Histórico de Empréstimos</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <h1>Histórico de Empréstimos</h1>
        <p>Consulte os empréstimos vinculados à sua conta.</p>
    </header>

    <nav>
        <a href="../painel/usuario.php">Início</a>
        <a href="index.php">Meus empréstimos</a>
        <a href="reservas.php">Minhas reservas</a>
        <a href="historico.php">Histórico</a>
        <a href="../livros/listar.php">Consultar livros</a>
        <a href="../login/sair_usuario.php">Sair</a>
    </nav>

    <main>
        <h2>Meus empréstimos anteriores e atuais</h2>

        <?php if (count($emprestimos) > 0): ?>

            <div class="tabela-container">
                <table>
                    <thead>
                        <tr>
                            <th>Livro</th>
                            <th>Patrimônio</th>
                            <th>Data do empréstimo</th>
                            <th>Devolução prevista</th>
                            <th>Data da devolução</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($emprestimos as $emprestimo): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars(
                                        $emprestimo['titulo'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $emprestimo['codigo_patrimonio'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= formatarData(
                                        $emprestimo['data_emprestimo']
                                    ) ?>
                                </td>

                                <td>
                                    <?= formatarData(
                                        $emprestimo['data_prevista_devolucao']
                                    ) ?>
                                </td>

                                <td>
                                    <?= formatarData(
                                        $emprestimo['data_devolucao']
                                    ) ?>
                                </td>

                                <td>
                                    <?php
                                    $status = $emprestimo['status'];

                                    $nomesStatus = [
                                        'ativo' => 'Em andamento',
                                        'devolvido' => 'Devolvido',
                                        'atrasado' => 'Atrasado'
                                    ];
                                    ?>

                                    <?= htmlspecialchars(
                                        $nomesStatus[$status] ?? $status,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>

            <p>Você ainda não possui empréstimos registrados.</p>

        <?php endif; ?>

        <p>
            <a href="index.php">Voltar para meus empréstimos</a>
        </p>
    </main>

</body>
</html>
