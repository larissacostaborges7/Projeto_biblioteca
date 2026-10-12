
<?php

function registrarLog(
    PDO $conexao,
    string $acao,
    string $tabelaAfetada,
    ?int $idRegistro = null,
    ?string $descricao = null
): void {
    $idFuncionario = $_SESSION['id_funcionario'] ?? null;

    $sql = "
        INSERT INTO logs_auditoria
            (id_funcionario, acao, tabela_afetada, id_registro, descricao)
        VALUES
            (:id_funcionario, :acao, :tabela_afetada, :id_registro, :descricao)
    ";

    $stmt = $conexao->prepare($sql);

    $stmt->execute([
        ':id_funcionario' => $idFuncionario,
        ':acao' => $acao,
        ':tabela_afetada' => $tabelaAfetada,
        ':id_registro' => $idRegistro,
        ':descricao' => $descricao
    ]);
}