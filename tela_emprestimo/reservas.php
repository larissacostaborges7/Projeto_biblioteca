<?php
session_start();

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/proteger_acesso.php'; // Protege para garantir que é um leitor logado
require_once __DIR__ . '/../includes/registrar_log.php';

// Exige que o usuário logado seja um Leitor (ajuste o cargo conforme sua regra de permissão)
exigirCargo(['Leitor', 'Usuario']); 

$idUsuarioLogado = (int) ($_SESSION['id_usuario'] ?? 0);
$erro = '';
$sucesso = '';

// Processamento de Ações de Reserva via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // 1. Cadastrar Nova Reserva (Usando direto o ID da sessão do leitor)
    if ($acao === 'cadastrar_reserva') {
        $idLivro = filter_input(INPUT_POST, 'id_livro', FILTER_VALIDATE_INT);
        $dataReserva = date('Y-m-d');

        if (!$idUsuarioLogado || !$idLivro) {
            $erro = 'Selecione um livro válido para realizar a reserva.';
        } else {
            try {
                // Verifica se o leitor já tem reserva ativa para este livro
                $stmtVerif = $conexao->prepare("SELECT id_reserva FROM reservas WHERE id_usuario = ? AND id_livro = ? AND status = 'ativa'");
                $stmtVerif->execute([$idUsuarioLogado, $idLivro]);
                if ($stmtVerif->fetch()) {
                    throw new RuntimeException('Você já possui uma reserva ativa para este livro.');
                }

                $stmt = $conexao->prepare("INSERT INTO reservas (id_usuario, id_livro, data_reserva, status) VALUES (?, ?, ?, 'ativa')");
                $stmt->execute([$idUsuarioLogado, $idLivro]);
                $idReserva = (int) $conexao->lastInsertId();

                registrarLog($conexao, 'RESERVA_LEITOR', 'reservas', $idReserva, 'Leitor realizou reserva de livro.');
                $sucesso = 'Livro reservado com sucesso!';
            } catch (Throwable $e) {
                $erro = $e->getMessage();
            }
        }
    }

    // 2. Cancelar a própria Reserva
    if ($acao === 'cancelar_reserva') {
        $idReserva = filter_input(INPUT_POST, 'id_reserva', FILTER_VALIDATE_INT);
        if ($idReserva) {
            try {
                // Garante que o leitor só cancela a reserva que pertence a ele mesmo
                $stmt = $conexao->prepare("UPDATE reservas SET status = 'cancelada' WHERE id_reserva = ? AND id_usuario = ? AND status = 'ativa'");
                $stmt->execute([$idReserva, $idUsuarioLogado]);

                if ($stmt->rowCount() > 0) {
                    registrarLog($conexao, 'CANCELAR_RESERVA_LEITOR', 'reservas', $idReserva, 'Leitor cancelou sua reserva.');
                    $sucesso = 'Reserva cancelada com sucesso!';
                } else {
                    throw new RuntimeException('Não foi possível cancelar esta reserva.');
                }
            } catch (Throwable $e) {
                $erro = $e->getMessage();
            }
        }
    }
}

// Carrega apenas as reservas do leitor logado
$stmtReservas = $conexao->prepare("
    SELECT r.*, l.titulo, l.autor 
    FROM reservas r
    INNER JOIN livros l ON l.id_livro = r.id_livro
    WHERE r.id_usuario = ?
    ORDER BY r.status DESC, r.data_reserva ASC
");
$stmtReservas->execute([$idUsuarioLogado]);
$reservas = $stmtReservas->fetchAll(PDO::FETCH_ASSOC);

// Carrega lista de livros disponíveis para a busca dinâmica
$livros = $conexao->query('SELECT id_livro, titulo, autor, isbn, ano_publicacao FROM livros ORDER BY titulo')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Reservas - Biblioteca Municipal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <main style="max-width: 900px; margin: 2rem auto; padding: 0 1rem;">
        <header style="margin-bottom: 2rem;">
            <h1>Minhas Reservas de Livros</h1>
            <div class="user-info">
                Olá, <strong><?= htmlspecialchars($_SESSION['nome_usuario'] ?? 'Leitor', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </header>

        <?php if ($erro !== ''): ?>
            <div class="alert alert-error" style="background:#fee2e2; color:#b91c1c; padding:1rem; border-radius:6px; margin-bottom:1rem;"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($sucesso !== ''): ?>
            <div class="alert alert-success" style="background:#dcfce7; color:#15803d; padding:1rem; border-radius:6px; margin-bottom:1rem;"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <!-- Formulário de Nova Reserva (Sem pedir dados do usuário) -->
        <div class="card-container" style="background:#fff; padding:1.5rem; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.1); margin-bottom: 2rem;">
            <h2 class="card-title" style="margin-bottom: 1rem;">Reservar Novo Livro</h2>
            <form method="POST">
                <input type="hidden" name="acao" value="cadastrar_reserva">
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="busca_livro" style="display:block; margin-bottom:0.5rem; font-weight:600;">Pesquisar Livro (Título, Autor, ISBN ou Código):</label>
                    <input type="text" id="busca_livro" placeholder="Digite para buscar o livro dinamicamente..." autocomplete="off" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; margin-bottom: 0.5rem;">
                    
                    <select name="id_livro" id="id_livro" required style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; background-color: #fff;">
                        <option value="">Selecione um livro da lista</option>
                        <?php foreach ($livros as $l): ?>
                            <option value="<?= (int) $l['id_livro'] ?>" 
                                    data-titulo="<?= strtolower(htmlspecialchars($l['titulo'], ENT_QUOTES, 'UTF-8')) ?>"
                                    data-autor="<?= strtolower(htmlspecialchars($l['autor'] ?? '', ENT_QUOTES, 'UTF-8')) ?>"
                                    data-isbn="<?= strtolower(htmlspecialchars($l['isbn'] ?? '', ENT_QUOTES, 'UTF-8')) ?>"
                                    data-ano="<?= strtolower(htmlspecialchars($l['ano_publicacao'] ?? '', ENT_QUOTES, 'UTF-8')) ?>"
                                    data-id="<?= (int) $l['id_livro'] ?>">
                                <?= htmlspecialchars($l['titulo'], ENT_QUOTES, 'UTF-8') ?> <?= !empty($l['autor']) ? '— ' . htmlspecialchars($l['autor'], ENT_QUOTES, 'UTF-8') : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn" style="background:#2563eb; color:#fff; padding:0.6rem 1.2rem; border:none; border-radius:6px; cursor:pointer;">Confirmar Reserva</button>
            </form>
        </div>

        <!-- Listagem das Reservas do Próprio Leitor -->
        <div class="card-container" style="background:#fff; padding:1.5rem; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
            <h2 class="card-title" style="margin-bottom: 1rem;">Meus Livros Reservados</h2>
            <?php if (empty($reservas)): ?>
                <p>Você não possui nenhuma reserva no momento.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="border-bottom:1px solid #e2e8f0; text-align:left;">
                                <th style="padding:0.75rem;">Livro</th>
                                <th style="padding:0.75rem;">Data da Reserva</th>
                                <th style="padding:0.75rem;">Status</th>
                                <th style="padding:0.75rem;">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservas as $item): ?>
                                <tr style="border-bottom:1px solid #f1f5f9;">
                                    <td style="padding:0.75rem;">
                                        <strong><?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                        <small style="color: #64748b;"><?= htmlspecialchars($item['autor'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td style="padding:0.75rem;"><?= htmlspecialchars($item['data_reserva'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="padding:0.75rem;">
                                        <span style="padding:0.2rem 0.5rem; border-radius:4px; font-size:0.85rem; background: <?= $item['status'] === 'ativa' ? '#dcfce7; color:#15803d;' : '#fee2e2; color:#b91c1c;' ?>">
                                            <?= htmlspecialchars(ucfirst($item['status']), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td style="padding:0.75rem;">
                                        <?php if ($item['status'] === 'ativa'): ?>
                                            <form method="POST" onsubmit="return confirm('Deseja cancelar sua reserva?');" style="margin: 0;">
                                                <input type="hidden" name="acao" value="cancelar_reserva">
                                                <input type="hidden" name="id_reserva" value="<?= (int) $item['id_reserva'] ?>">
                                                <button type="submit" style="background:#ef4444; color:#fff; border:none; padding:0.3rem 0.6rem; border-radius:4px; cursor:pointer; font-size:0.8rem;">Cancelar</button>
                                            </form>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Script de Pesquisa Dinâmica -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputBusca = document.getElementById('busca_livro');
            const selectLivro = document.getElementById('id_livro');
            
            if (inputBusca && selectLivro) {
                const optionsOriginais = Array.from(selectLivro.options);

                inputBusca.addEventListener('input', function() {
                    const termo = inputBusca.value.toLowerCase().trim();
                    selectLivro.innerHTML = '';
                    
                    const optionsFiltradas = optionsOriginais.filter(opt => {
                        if (opt.value === '') return true;
                        
                        const titulo = opt.getAttribute('data-titulo') || '';
                        const autor = opt.getAttribute('data-autor') || '';
                        const isbn = opt.getAttribute('data-isbn') || '';
                        const ano = opt.getAttribute('data-ano') || '';
                        const id = opt.getAttribute('data-id') || '';

                        return titulo.includes(termo) || 
                               autor.includes(termo) || 
                               isbn.includes(termo) || 
                               ano.includes(termo) || 
                               id.includes(termo);
                    });

                    optionsFiltradas.forEach(opt => selectLivro.appendChild(opt));
                    
                    if (optionsFiltradas.length === 2) {
                        selectLivro.selectedIndex = 1;
                    } else {
                        selectLivro.selectedIndex = 0;
                    }
                });
            }
        });
    </script>
</body>
</html>