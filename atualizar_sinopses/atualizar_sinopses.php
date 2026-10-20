<?php
require_once __DIR__ . '/../includes/conexao.php';

// Mapeamento dos títulos existentes para as respetivas sinopses
$sinopses = [
    'Amor & Gelato' => 'Lina viaja para a Itália para realizar o último pedido da mãe. Lá, ela descobre um diário antigo e começa a desvendar segredos de família enquanto explora Florença.',
    'O Menino do Pijama Listrado' => 'A história comovente de Bruno, filho de um oficial nazista, e sua amizade proibida com Shmuel, um menino judeu do outro lado da cerca de um campo de concentração.',
    'Como Eu Era Antes de Você' => 'Louisa Clark assume o cargo de cuidadora de Will Traynor, um homem rico e amargurado após um acidente. A convivência transforma a vida de ambos de maneira inesquecível.',
    'O Pequeno Príncipe' => 'Um piloto perdido no deserto do Saara encontra um jovem príncipe vindo de um asteroide distante, levando a uma jornada lírica sobre amor, amizade e a natureza humana.',
    'Harry Potter e a Pedra Filosofal' => 'O jovem órfão Harry Potter descobre no seu 11º aniversário que é um bruxo e é convidado a frequentar a Escola de Magia e Bruxaria de Hogwarts.',
    'Dom Casmurro' => 'Bento Santiago narra a sua história e a obsessão em comprovar a suposta traição da sua esposa Capitu com o seu melhor amigo, Escobar.',
    'O Cortiço' => 'Uma obra-prima do Naturalismo brasileiro que retrata a vida dos moradores de um cortiço no Rio de Janeiro e a influência do meio no comportamento humano.',
    'Memórias Póstumas de Brás Cubas' => 'Narrado por um defunto autor, o livro repassa a sua vida com ironia, pessimismo e reflexões afiadas sobre a sociedade brasileira do século XIX.',
    'Sherlock Holmes: Um Estudo em Vermelho' => 'O romance de estreia que apresenta a lendária parceria entre o detetive Sherlock Holmes e o Dr. John Watson resolvendo um misterioso assassinato.',
    'O Hobbit' => 'Bilbo Bolseiro, um hobbit pacato, é arrastado numa busca épica para recuperar o reino dos anões das garras do temível dragão Smaug.'
];

try {
    $stmt = $conexao->prepare("UPDATE livros SET sinopse = ? WHERE titulo = ?");
    $totalAtualizados = 0;

    foreach ($sinopses as $titulo => $sinopse) {
        $stmt->execute([$sinopse, $titulo]);
        $totalAtualizados += $stmt->rowCount();
    }

    echo "<div style='font-family: Arial; padding: 20px; background: #e8f5e9; color: #2e7d32; border-radius: 8px;'>";
    echo "<h2>✅ Sinopses atualizadas com sucesso!</h2>";
    echo "<p>Total de livros atualizados: <strong>{$totalAtualizados}</strong></p>";
    echo "<a href='../tela_acervo_usuario/tela_acervo_usuario.php' style='background: #2e7d32; color: #fff; padding: 10px 15px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Ir para o Acervo</a>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='font-family: Arial; padding: 20px; background: #ffebee; color: #c62828; border-radius: 8px;'>";
    echo "<h2>❌ Erro ao atualizar sinopses:</h2> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}