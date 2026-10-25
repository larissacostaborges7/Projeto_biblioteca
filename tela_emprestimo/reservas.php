
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/proteger_acesso.php';
require_once __DIR__ . '/../includes/registrar_log.php';

exigirCargo(['Administrador', 'Atendente']);

$erro = '';
$sucesso = '';

function h($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function voltarParaReservas(): void
{
    header('Location: reservas.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Registrar empréstimo a partir de uma reserva
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'registrar_emprestimo') {
        $idReserva = filter_input(
            INPUT_POST,
            'id_reserva',
            FILTER_VALIDATE_INT
        );

        try {
            if (!$idReserva || $idReserva < 1) {
                throw new RuntimeException('Reserva inválida.');
            }

            $idFuncionario = (int) ($_SESSION['id_funcionario'] ?? 0);

            if ($idFuncionario < 1) {
                throw new RuntimeException(
                    'Não foi possível identificar o funcionário logado.'
                );
            }

            $conexao->beginTransaction();

            /*
             * Bloqueia a reserva enquanto ela está sendo processada,
             * evitando que duas pessoas a atendam ao mesmo tempo.
             */
            $stmtReserva = $conexao->prepare("
                SELECT
                    r.id_reserva,
                    r.id_usuario,
                    r.id_livro,
                    r.status,
                    u.nome AS nome_usuario,
                    l.titulo
                FROM reservas r
                INNER JOIN usuarios u
                    ON u.id_usuario = r.id_usuario
                INNER JOIN livros l
                    ON l.id_livro = r.id_livro
                WHERE r.id_reserva = ?
                FOR UPDATE
            ");

            $stmtReserva->execute([$idReserva]);
            $reserva = $stmtReserva->fetch(PDO::FETCH_ASSOC);

            if (!$reserva) {
                throw new RuntimeException('Reserva não encontrada.');
            }

            if ($reserva['status'] !== 'ativa') {
                throw new RuntimeException(
                    'Esta reserva não está mais ativa.'
                );
            }

            /*
             * Procura um exemplar disponível do livro reservado.
             * Confirme a estrutura e os valores da tabela exemplares.
             */
            $stmtExemplar = $conexao->prepare("
                SELECT e.id_exemplar
                FROM exemplares e
                WHERE e.id_livro = ?
                  AND e.status = 'disponivel'
                  AND NOT EXISTS (
                      SELECT 1
                      FROM emprestimos em
                      WHERE em.id_exemplar = e.id_exemplar
                        AND em.status = 'ativo'
                  )
                ORDER BY e.id_exemplar
                LIMIT 1
                FOR UPDATE
            ");

            $stmtExemplar->execute([(int) $reserva['id_livro']]);
            $exemplar = $stmtExemplar->fetch(PDO::FETCH_ASSOC);

            if (!$exemplar) {
                throw new RuntimeException(
                    'Não há exemplar disponível para este livro.'
                );
            }

            /*
             * O prazo padrão fica em 14 dias.
             * Altere este valor se a biblioteca utilizar outro prazo.
             */
            $dataEmprestimo = date('Y-m-d');
            $dataPrevista = date(
                'Y-m-d',
                strtotime('+14 days')
            );

            $stmtEmprestimo = $conexao->prepare("
                INSERT INTO emprestimos (
                    id_usuario,
                    id_exemplar,
                    id_funcionario,
                    data_emprestimo,
                    data_prevista_devolucao,
                    status
                ) VALUES (?, ?, ?, ?, ?, 'ativo')
            ");

            $stmtAtualizarExemplar = $conexao->prepare("
    UPDATE exemplares
    SET status = 'emprestado'
    WHERE id_exemplar = ?
      AND status = 'disponivel'
");

$stmtAtualizarExemplar->execute([
    (int) $exemplar['id_exemplar']
]);

if ($stmtAtualizarExemplar->rowCount() !== 1) {
    throw new RuntimeException(
        'Não foi possível atualizar o status do exemplar.'
    );
}

            /*
             * Só finaliza a reserva depois de inserir o empréstimo.
             */
            $stmtAtualizarReserva = $conexao->prepare("
                UPDATE reservas
                SET status = 'atendida'
                WHERE id_reserva = ?
                  AND status = 'ativa'
            ");

            $stmtAtualizarReserva->execute([$idReserva]);

            if ($stmtAtualizarReserva->rowCount() !== 1) {
                throw new RuntimeException(
                    'Não foi possível finalizar a reserva.'
                );
            }

            $idEmprestimo = (int) $conexao->lastInsertId();

            registrarLog(
                $conexao,
                'EMPRESTIMO_POR_RESERVA',
                'emprestimos',
                $idEmprestimo,
                'Empréstimo registrado a partir da reserva '
                    . $idReserva
                    . '. Exemplar: '
                    . (int) $exemplar['id_exemplar']
            );

            $conexao->commit();

            $sucesso =
                'Empréstimo registrado para '
                . $reserva['nome_usuario']
                . '. A reserva foi marcada como atendida.';

        } catch (Throwable $e) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }

            $erro = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Não foi possível registrar o empréstimo. '
                    . 'Confira a estrutura das tabelas do banco.';
        }
    }

    if ($acao === 'cancelar_reserva') {
        $idReserva = filter_input(
            INPUT_POST,
            'id_reserva',
            FILTER_VALIDATE_INT
        );

        try {
            if (!$idReserva || $idReserva < 1) {
                throw new RuntimeException('Reserva inválida.');
            }

            $stmt = $conexao->prepare("
                UPDATE reservas
                SET status = 'cancelada'
                WHERE id_reserva = ?
                  AND status = 'ativa'
            ");

            $stmt->execute([$idReserva]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'A reserva não existe ou não está mais ativa.'
                );
            }

            registrarLog(
                $conexao,
                'CANCELAR_RESERVA_FUNCIONARIO',
                'reservas',
                $idReserva,
                'Funcionário cancelou a reserva.'
            );

            $sucesso = 'Reserva cancelada com sucesso.';

        } catch (Throwable $e) {
            $erro = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Não foi possível cancelar a reserva.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| Consultar todas as reservas
|--------------------------------------------------------------------------
*/
$stmtReservas = $conexao->query("
    SELECT
        r.id_reserva,
        r.data_reserva,
        r.data_validade,
        r.status,
        u.id_usuario,
        u.nome AS nome_usuario,
        l.id_livro,
        l.titulo,
        l.autor
    FROM reservas r
    INNER JOIN usuarios u
        ON u.id_usuario = r.id_usuario
    INNER JOIN livros l
        ON l.id_livro = r.id_livro
    ORDER BY
        CASE r.status
            WHEN 'ativa' THEN 1
            WHEN 'atendida' THEN 2
            WHEN 'expirada' THEN 3
            WHEN 'cancelada' THEN 4
            ELSE 5
        END,
        r.data_reserva ASC
");

$reservas = $stmtReservas->fetchAll(PDO::FETCH_ASSOC);

$totalAtivas = 0;
$totalAtendidas = 0;
$totalOutras = 0;

foreach ($reservas as $reserva) {
    if ($reserva['status'] === 'ativa') {
        $totalAtivas++;
    } elseif ($reserva['status'] === 'atendida') {
        $totalAtendidas++;
    } else {
        $totalOutras++;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gerenciar Reservas - Biblioteca Municipal</title>

    <link rel="stylesheet" href="style_reservas.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f1f5f9;
            color: #1e293b;
            font-family: Arial, sans-serif;
        }

        main {
            width: min(1200px, 96%);
            margin: 32px auto;
        }

        .cabecalho {
            margin-bottom: 24px;
        }

        .cabecalho h1 {
            margin-bottom: 8px;
        }

        .cabecalho p {
            color: #64748b;
            line-height: 1.5;
        }

        .resumo {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .cartao,
        .painel {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .06);
        }

        .numero {
            display: block;
            margin-top: 8px;
            font-size: 28px;
            font-weight: bold;
        }

        .filtros {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        input,
        select {
            min-height: 40px;
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
        }

        .filtros input {
            flex: 1;
            min-width: 220px;
        }

        .tabela {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        th,
        td {
            padding: 13px 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #f8fafc;
            white-space: nowrap;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }

        .status-ativa {
            background: #dcfce7;
            color: #166534;
        }

        .status-atendida {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-cancelada,
        .status-expirada {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn {
            display: inline-block;
            border: 0;
            border-radius: 6px;
            padding: 9px 12px;
            margin: 2px 0;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
        }

        .btn-emprestimo {
            background: #2563eb;
            color: #fff;
        }

        .btn-cancelar {
            background: #fee2e2;
            color: #991b1b;
        }

        .alerta {
            margin-bottom: 18px;
            padding: 14px;
            border-radius: 8px;
            line-height: 1.5;
        }

        .alerta-erro {
            color: #991b1b;
            background: #fee2e2;
        }

        .alerta-sucesso {
            color: #166534;
            background: #dcfce7;
        }

        .vazio {
            padding: 24px;
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 700px) {
            .resumo {
                grid-template-columns: 1fr;
            }

            main {
                width: 94%;
                margin: 20px auto;
            }
        }
    </style>
</head>

<body>
<main>
    <header class="cabecalho">
        <h1>Gerenciamento de Reservas</h1>

        <p>
            Consulte as reservas dos leitores e registre o empréstimo
            quando o leitor vier retirar o livro na biblioteca.
        </p>

        <p>
            Funcionário:
            <strong><?= h($_SESSION['nome_funcionario'] ?? 'Funcionário') ?></strong>
        </p>
    </header>

    <?php if ($erro !== ''): ?>
        <div class="alerta alerta-erro" role="alert">
            <?= h($erro) ?>
        </div>
    <?php endif; ?>

    <?php if ($sucesso !== ''): ?>
        <div class="alerta alerta-sucesso" role="status">
            <?= h($sucesso) ?>
        </div>
    <?php endif; ?>

    <section class="resumo">
        <div class="cartao">
            Reservas aguardando atendimento
            <span class="numero"><?= $totalAtivas ?></span>
        </div>

        <div class="cartao">
            Reservas atendidas
            <span class="numero"><?= $totalAtendidas ?></span>
        </div>

        <div class="cartao">
            Canceladas ou expiradas
            <span class="numero"><?= $totalOutras ?></span>
        </div>
    </section>

    <section class="painel">
        <h2>Reservas dos leitores</h2>

        <div class="filtros">
            <input
                type="search"
                id="busca"
                placeholder="Pesquisar leitor, livro ou código da reserva..."
                autocomplete="off"
            >

            <select id="filtroStatus">
                <option value="">Todos os status</option>
                <option value="ativa">Ativas</option>
                <option value="atendida">Atendidas</option>
                <option value="cancelada">Canceladas</option>
                <option value="expirada">Expiradas</option>
            </select>
        </div>

        <?php if (empty($reservas)): ?>
            <div class="vazio">
                Nenhuma reserva foi registrada até o momento.
            </div>
        <?php else: ?>
            <div class="tabela">
                <table id="tabelaReservas">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Leitor</th>
                            <th>Livro</th>
                            <th>Data da reserva</th>
                            <th>Validade</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($reservas as $item): ?>
                        <?php
                            $status = $item['status'];
                            $statusClasse = in_array(
                                $status,
                                ['ativa', 'atendida', 'cancelada', 'expirada'],
                                true
                            ) ? $status : 'expirada';
                        ?>

                        <tr
                            data-status="<?= h($status) ?>"
                            data-pesquisa="<?= h(
                                $item['id_reserva'] . ' ' .
                                $item['nome_usuario'] . ' ' .
                                $item['id_usuario'] . ' ' .
                                $item['titulo'] . ' ' .
                                ($item['autor'] ?? '')
                            ) ?>"
                        >
                            <td>#<?= (int) $item['id_reserva'] ?></td>

                            <td>
                                <strong><?= h($item['nome_usuario']) ?></strong>
                                <br>
                                <small>
                                    Código do leitor:
                                    <?= (int) $item['id_usuario'] ?>
                                </small>
                            </td>

                            <td>
                                <strong><?= h($item['titulo']) ?></strong>
                                <br>
                                <small><?= h($item['autor'] ?? '') ?></small>
                            </td>

                            <td>
                                <?= h(date(
                                    'd/m/Y',
                                    strtotime($item['data_reserva'])
                                )) ?>
                            </td>

                            <td>
                                <?php if (!empty($item['data_validade'])): ?>
                                    <?= h(date(
                                        'd/m/Y',
                                        strtotime($item['data_validade'])
                                    )) ?>
                                <?php else: ?>
                                    Não definida
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="status status-<?= h($statusClasse) ?>">
                                    <?= h(ucfirst($status)) ?>
                                </span>
                            </td>

                            <td>
                                <?php if ($status === 'ativa'): ?>
                                    <form
                                        method="post"
                                        onsubmit="return confirm(
                                            'Confirmar o registro do empréstimo deste livro?'
                                        );"
                                    >
                                        <input
                                            type="hidden"
                                            name="acao"
                                            value="registrar_emprestimo"
                                        >

                                        <input
                                            type="hidden"
                                            name="id_reserva"
                                            value="<?= (int) $item['id_reserva'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-emprestimo"
                                        >
                                            Registrar empréstimo
                                        </button>
                                    </form>

                                    <form
                                        method="post"
                                        onsubmit="return confirm(
                                            'Tem certeza de que deseja cancelar esta reserva?'
                                        );"
                                    >
                                        <input
                                            type="hidden"
                                            name="acao"
                                            value="cancelar_reserva"
                                        >

                                        <input
                                            type="hidden"
                                            name="id_reserva"
                                            value="<?= (int) $item['id_reserva'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-cancelar"
                                        >
                                            Cancelar reserva
                                        </button>
                                    </form>
                                <?php elseif ($status === 'atendida'): ?>
                                    Empréstimo já registrado
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p id="semResultados" class="vazio" hidden>
                Nenhuma reserva corresponde à pesquisa.
            </p>
        <?php endif; ?>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const busca = document.getElementById('busca');
    const filtroStatus = document.getElementById('filtroStatus');
    const linhas = Array.from(
        document.querySelectorAll('#tabelaReservas tbody tr')
    );
    const semResultados = document.getElementById('semResultados');

    function filtrarReservas() {
        const termo = busca.value.trim().toLocaleLowerCase('pt-BR');
        const statusEscolhido = filtroStatus.value;
        let visiveis = 0;

        linhas.forEach(function (linha) {
            const texto = (
                linha.dataset.pesquisa || ''
            ).toLocaleLowerCase('pt-BR');

            const status = linha.dataset.status || '';

            const correspondeTexto = texto.includes(termo);
            const correspondeStatus =
                statusEscolhido === '' || status === statusEscolhido;

            const mostrar = correspondeTexto && correspondeStatus;

            linha.hidden = !mostrar;

            if (mostrar) {
                visiveis++;
            }
        });

        if (semResultados) {
            semResultados.hidden = visiveis !== 0;
        }
    }

    busca.addEventListener('input', filtrarReservas);
    filtroStatus.addEventListener('change', filtrarReservas);
});
</script>
</body>
</html>
