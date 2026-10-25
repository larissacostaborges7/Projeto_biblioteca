
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/proteger_acesso.php';
require_once __DIR__ . '/../includes/registrar_log.php';

exigirCargo(['Administrador', 'Atendente']);

$erro = '';
$sucesso = '';

$nomeSelecionado = '';
$cpfSelecionado = '';
$tituloSelecionado = '';
$patrimonioSelecionado = '';

$hoje = new DateTimeImmutable('today');
$limite = $hoje->modify('+14 days');

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['acao'] ?? '') === 'cadastrar_emprestimo') {

    $idUsuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
    $idExemplar = filter_input(INPUT_POST, 'id_exemplar', FILTER_VALIDATE_INT);
    $dataPrevista = trim($_POST['data_prevista_devolucao'] ?? '');
    $dataValida = DateTimeImmutable::createFromFormat('!Y-m-d', $dataPrevista);
    $errosData = DateTimeImmutable::getLastErrors();

    $dataCorreta = $dataValida !== false
        && ($errosData === false
            || ($errosData['warning_count'] === 0
                && $errosData['error_count'] === 0))
        && $dataValida->format('Y-m-d') === $dataPrevista;

    if (!$idUsuario || !$idExemplar) {
        $erro = 'Pesquise e selecione um leitor e um exemplar.';
    } elseif (!$dataCorreta) {
        $erro = 'Informe uma data de devolução válida.';
    } elseif ($dataValida < $hoje || $dataValida > $limite) {
        $erro = 'A devolução deve ser prevista entre hoje e os próximos 14 dias.';
    } else {
        $idFuncionario = (int) ($_SESSION['id_funcionario'] ?? 0);

        if ($idFuncionario <= 0) {
            $erro = 'Não foi possível identificar o funcionário logado.';
        } else {
            try {
                $conexao->beginTransaction();

                $stmt = $conexao->prepare("
                    SELECT id_usuario, nome, cpf
                    FROM usuarios
                    WHERE id_usuario = ?
                ");
                $stmt->execute([$idUsuario]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$usuario) {
                    throw new RuntimeException('O leitor selecionado não existe.');
                }

                $stmt = $conexao->prepare("
                    SELECT
                        e.id_exemplar,
                        e.codigo_patrimonio,
                        e.status,
                        l.titulo
                    FROM exemplares e
                    INNER JOIN livros l ON l.id_livro = e.id_livro
                    WHERE e.id_exemplar = ?
                    FOR UPDATE
                ");
                $stmt->execute([$idExemplar]);
                $exemplar = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$exemplar || $exemplar['status'] !== 'disponivel') {
                    throw new RuntimeException(
                        'Este exemplar não está disponível.'
                    );
                }

                $stmt = $conexao->prepare("
                    SELECT COUNT(*)
                    FROM emprestimos
                    WHERE id_usuario = ?
                      AND status = 'ativo'
                ");
                $stmt->execute([$idUsuario]);
                $emprestimosAtivos = (int) $stmt->fetchColumn();

                if ($emprestimosAtivos >= 2) {
                    throw new RuntimeException(
                        'Este leitor já atingiu o limite de 2 empréstimos ativos.'
                    );
                }

                $stmt = $conexao->prepare("
                    INSERT INTO emprestimos (
                        id_usuario,
                        id_exemplar,
                        id_funcionario,
                        data_emprestimo,
                        data_prevista_devolucao,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, 'ativo')
                ");

                $stmt->execute([
                    $idUsuario,
                    $idExemplar,
                    $idFuncionario,
                    $hoje->format('Y-m-d'),
                    $dataValida->format('Y-m-d')
                ]);

                $idEmprestimo = (int) $conexao->lastInsertId();

                $stmt = $conexao->prepare("
                    UPDATE exemplares
                    SET status = 'emprestado'
                    WHERE id_exemplar = ?
                      AND status = 'disponivel'
                ");
                $stmt->execute([$idExemplar]);

                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException(
                        'Não foi possível atualizar a disponibilidade do exemplar.'
                    );
                }

                registrarLog(
                    $conexao,
                    'EMPRESTIMO',
                    'emprestimos',
                    $idEmprestimo,
                    "Empréstimo registrado para o exemplar ID {$idExemplar}."
                );

                $conexao->commit();

                $sucesso = 'Empréstimo registrado com sucesso!';
            } catch (Throwable $e) {
                if ($conexao->inTransaction()) {
                    $conexao->rollBack();
                }

                error_log('Erro ao registrar empréstimo: ' . $e->getMessage());

                $erro = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'Não foi possível registrar o empréstimo. Verifique os dados e tente novamente.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar empréstimo | Biblioteca</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>
<main>
    <h1>Registrar empréstimo</h1>
    <p>Pesquise o leitor e o exemplar disponível para continuar.</p>

    <?php if ($erro !== ''): ?>
        <p class="alert alert-error" role="alert">
            <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($sucesso !== ''): ?>
        <p class="alert alert-success" role="status">
            <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="registrar.php" id="formEmprestimo">
        <input type="hidden" name="acao" value="cadastrar_emprestimo">

        <label for="pesquisa_leitor">Leitor</label>
        <input
            type="text"
            id="pesquisa_leitor"
            placeholder="Digite o nome ou CPF..."
            autocomplete="off"
            required
        >
        <div id="sugestoes_leitores" class="sugestoes"></div>
        <p id="leitor_selecionado" class="selecionado"></p>
        <input type="hidden" name="id_usuario" id="id_usuario" required>

        <label for="pesquisa_exemplar">Livro / exemplar disponível</label>
        <input
            type="text"
            id="pesquisa_exemplar"
            placeholder="Título, autor, categoria, ISBN ou patrimônio..."
            autocomplete="off"
            required
        >
        <div id="sugestoes_exemplares" class="sugestoes"></div>
        <p id="exemplar_selecionado" class="selecionado"></p>
        <input type="hidden" name="id_exemplar" id="id_exemplar" required>

        <label for="data_prevista_devolucao">
            Data prevista para devolução
        </label>
        <input
            type="date"
            name="data_prevista_devolucao"
            id="data_prevista_devolucao"
            min="<?= $hoje->format('Y-m-d') ?>"
            max="<?= $limite->format('Y-m-d') ?>"
            value="<?= $limite->format('Y-m-d') ?>"
            required
        >

        <button type="submit">Registrar empréstimo</button>
    </form>

    <p><a href="../painel/servidor.php">Voltar ao painel</a></p>
</main>

<script src="script.js" defer></script>
</body>
</html>
