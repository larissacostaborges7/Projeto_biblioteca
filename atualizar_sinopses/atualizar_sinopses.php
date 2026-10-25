<?php
require_once __DIR__ . '/../includes/conexao.php';

// Mapeamento dos títulos existentes para as respetivas sinopses

   
$sinopses = [
    '1984' => 'Em uma sociedade totalitária, Winston Smith vive sob a vigilância constante do Grande Irmão. Ao questionar o regime, começa a desafiar um sistema que controla pensamentos, informações e comportamentos.',
    'A Batalha do Labirinto' => 'Percy Jackson e seus amigos enfrentam uma nova missão ligada ao Labirinto de Dédalo, uma estrutura cheia de perigos e caminhos imprevisíveis que pode ser usada pelos inimigos do Olimpo.',
    'A Bolsa Amarela' => 'Raquel guarda em uma bolsa amarela três grandes vontades: crescer, ser menino e ser escritora. A partir de situações imaginativas, a história aborda desejos, identidade e liberdade na infância.',
    'A Borboleta Azul' => 'Uma narrativa infantil que acompanha uma borboleta azul e suas descobertas, explorando a natureza e despertando a curiosidade e a sensibilidade dos leitores.',
    'A Esperança' => 'No desfecho da trilogia Jogos Vorazes, Katniss Everdeen participa da luta contra o governo opressor de Panem, enquanto enfrenta escolhas difíceis, perdas e as consequências da guerra.',
    'A Hora da Estrela' => 'Macabéa é uma jovem nordestina que vive no Rio de Janeiro em condições humildes. Narrada por Rodrigo S. M., sua trajetória revela questões de pobreza, invisibilidade social e existência.',
    'A Maldição do Titã' => 'Percy Jackson parte em uma missão perigosa para ajudar seus amigos e enfrentar uma ameaça ligada aos titãs, enquanto descobre mais sobre as profecias e os conflitos do mundo mitológico.',
    'A Metamorfose' => 'Gregor Samsa acorda transformado em uma criatura monstruosa e precisa lidar com a reação de sua família e com o isolamento. A obra aborda alienação, relações familiares e exclusão.',
    'Amor & Gelato' => 'Após a morte da mãe, Lina viaja para a Itália para cumprir seu último desejo. Ao explorar Florença e ler um diário deixado por ela, descobre segredos familiares e vive novas experiências.',
    'Capitães da Areia' => 'Em Salvador, um grupo de meninos abandonados vive nas ruas e enfrenta a pobreza e o preconceito. A narrativa acompanha suas relações, conflitos e sonhos em uma sociedade desigual.',
    'Cascão' => 'Histórias em quadrinhos protagonizadas por Cascão, personagem conhecido por evitar água e viver aventuras com seus amigos da Turma da Mônica.',
    'Cebolinha' => 'Histórias em quadrinhos protagonizadas por Cebolinha, que costuma criar planos para enfrentar a Mônica e vive aventuras divertidas com sua turma.',
    'Chico Bento' => 'Histórias em quadrinhos que acompanham Chico Bento em seu cotidiano na roça, mostrando suas amizades, sua relação com a natureza e situações bem-humoradas da vida no campo.',
    'Como Eu Era Antes de Você' => 'Louisa Clark começa a trabalhar como cuidadora de Will Traynor, um homem que ficou tetraplégico após um acidente. A convivência transforma a vida dos dois e levanta questões sobre escolhas e autonomia.',
    'Conto ou não conto?' => 'Uma história infantil sobre situações em que contar ou guardar um segredo pode gerar dúvidas e consequências, convidando o leitor a refletir sobre confiança e responsabilidade.',
    'Death Note - Volume 1' => 'Light Yagami encontra um caderno sobrenatural que permite matar quem tiver o nome escrito nele. Ao decidir usá-lo para combater o crime, inicia um caminho marcado por dilemas morais e consequências inesperadas.',
    'Demon Slayer - Volume 1' => 'Tanjiro Kamado retorna para casa e encontra sua família atacada por demônios. Sua irmã Nezuko sobrevive, mas sofre uma transformação, levando Tanjiro a iniciar uma jornada para protegê-la e enfrentar essas criaturas.',
    'Dom Casmurro' => 'Bento Santiago relembra sua juventude, seu relacionamento com Capitu e suas suspeitas de traição. A narrativa de Machado de Assis deixa em aberto a confiabilidade do narrador e a interpretação dos acontecimentos.',
    'Em Chamas' => 'Após sobreviver aos Jogos Vorazes, Katniss Everdeen se torna símbolo de resistência. Ao retornar à arena em uma edição especial dos jogos, enfrenta novos perigos e percebe o crescimento da revolta contra o Capitólio.',
    'Frankenstein' => 'Victor Frankenstein cria uma criatura por meio de experiências científicas, mas rejeita sua criação. Abandonada e isolada, ela busca compreensão e passa a confrontar seu criador, levantando questões sobre responsabilidade e preconceito.',
    'Fullmetal Alchemist - Volume 1 - Edição Especial' => 'Os irmãos Edward e Alphonse Elric tentam recuperar o que perderam após uma experiência proibida de alquimia. A busca os coloca diante dos limites dessa ciência e dos custos de tentar desafiar suas leis.',
    'Haikyu!! - Volume 1' => 'Shoyo Hinata sonha em se tornar um grande jogador de vôlei, apesar de sua baixa estatura. Ao ingressar no ensino médio, encontra novos desafios e rivais que o ajudam a desenvolver suas habilidades.',
    'Harry Potter e a Câmara Secreta' => 'Harry retorna a Hogwarts e descobre que uma força misteriosa ameaça os alunos. Com a ajuda de seus amigos, investiga os ataques e os segredos ligados à Câmara Secreta.',
    'Harry Potter e a Ordem da Fênix' => 'Harry enfrenta a descrença do mundo mágico sobre o retorno de Voldemort e encontra resistência dentro de Hogwarts. Com seus amigos, busca aprender a se defender e compreender a ameaça crescente.',
    'Harry Potter e a Pedra Filosofal' => 'Harry Potter descobre que é bruxo e começa a estudar em Hogwarts. Lá, faz amizades, conhece o mundo mágico e se envolve em um mistério relacionado à Pedra Filosofal.',
    'My Hero Academia - Volume 1' => 'Izuku Midoriya vive em um mundo onde a maioria das pessoas possui superpoderes, mas nasceu sem uma individualidade. Apesar disso, sonha em ser herói e tem a oportunidade de mudar seu destino.',
    'Naruto - Volume 1' => 'Naruto Uzumaki é um jovem ninja que deseja se tornar Hokage e conquistar o reconhecimento de sua vila. No início de sua jornada, enfrenta desafios, aprende sobre trabalho em equipe e começa a desenvolver seu potencial.',
    'One Piece - Volume 1' => 'Monkey D. Luffy parte em busca do lendário tesouro One Piece e do sonho de se tornar o Rei dos Piratas. Sua aventura começa com encontros inesperados e a busca por companheiros para sua tripulação.',
    'Reinações de Narizinho' => 'No Sítio do Picapau Amarelo, Narizinho, Emília e seus amigos vivem aventuras que misturam fantasia, humor e elementos do folclore e da cultura brasileira.',
    'Sherlock Holmes: Um Estudo em Vermelho' => 'Sherlock Holmes e o Dr. Watson investigam um assassinato misterioso em Londres. A investigação apresenta os métodos de observação e dedução que caracterizam o famoso detetive.',
    'Spy x Family - Volume 1' => 'Para cumprir uma missão secreta, o espião Twilight precisa formar uma família de fachada. Ele adota Anya e se casa com Yor, sem conhecer todos os segredos de cada integrante dessa família incomum.',
    'Tokyo Revengers - Volume 1' => 'Takemichi Hanagaki descobre que pode voltar ao passado e tenta mudar acontecimentos trágicos envolvendo pessoas importantes para ele. Sua jornada o aproxima de conflitos entre grupos de delinquentes.',
    'Turma da Mônica' => 'Histórias em quadrinhos que acompanham Mônica, Cebolinha, Cascão, Magali e seus amigos em brincadeiras, conflitos cotidianos e aventuras cheias de humor.',
    'Harry Potter e as Relíquias da Morte' => 'Harry, Rony e Hermione partem em uma missão perigosa para encontrar e destruir as Horcruxes de Voldemort, enquanto o confronto final entre o bem e o mal se aproxima.',

    'Harry Potter e o Cálice de Fogo' => 'Harry participa inesperadamente do Torneio Tribruxo, uma competição mágica perigosa que traz desafios inesperados e sinais do retorno de Lord Voldemort.',

    'Harry Potter e o Enigma do Príncipe' => 'Enquanto conhece mais sobre o passado de Voldemort, Harry recebe aulas particulares de Dumbledore e tenta compreender os segredos que podem ajudar a derrotar o bruxo das trevas.',

    'Harry Potter e o Prisioneiro de Azkaban' => 'No terceiro ano em Hogwarts, Harry descobre que Sirius Black, um prisioneiro fugitivo de Azkaban, parece estar ligado ao seu passado.',

    'It: A Coisa' => 'Em Derry, um grupo de amigos enfrenta uma presença assustadora que assume diferentes formas e parece estar ligada aos desaparecimentos de crianças da cidade.',

    'Jogos Vorazes' => 'Em uma sociedade dividida em distritos, Katniss Everdeen se oferece para participar de uma competição mortal televisionada para proteger sua irmã.',

    'Jujutsu Kaisen - Volume 1' => 'Yuji Itadori entra em contato com o mundo das maldições após um acontecimento sobrenatural e passa a enfrentar perigos ligados a forças malignas.',

    'Magali' => 'As histórias em quadrinhos acompanham Magali, uma menina conhecida pelo enorme apetite, em aventuras divertidas com seus amigos.',

    'Meu Pé de Laranja Lima' => 'Zezé, um menino imaginativo e sensível, enfrenta dificuldades familiares e encontra conforto na amizade e na imaginação.',

    'O Homem de Giz' => 'Uma série de desenhos misteriosos feitos com giz conecta acontecimentos da infância de um grupo de amigos a segredos que voltam a assombrá-los na vida adulta.',

    'O Ladrão de Raios' => 'Percy Jackson descobre ser filho de um deus grego e embarca em uma jornada para encontrar o raio-mestre de Zeus e evitar uma guerra entre os deuses.',

    'O Mar de Monstros' => 'Percy Jackson e seus amigos atravessam o perigoso Mar de Monstros em busca do Velocino de Ouro, que pode salvar o acampamento dos semideuses.',

    'O Menino que Desenhava Monstros' => 'Um menino que tem medo de sair de casa passa a desenhar criaturas que parecem ganhar vida, provocando acontecimentos inquietantes ao seu redor.',

    'O Morro dos Ventos Uivantes' => 'A história acompanha a relação intensa e conflituosa entre Heathcliff e Catherine, marcada por paixão, ressentimento e consequências que atravessam gerações.',

    'O Saci' => 'Pedrinho conhece o Saci e vive aventuras na mata, descobrindo criaturas e mistérios do folclore brasileiro.',

    'O Último Olimpiano' => 'Percy Jackson e seus aliados se preparam para defender o Olimpo de uma grande ofensiva liderada por Cronos.',

    'Orgulho e Preconceito' => 'Elizabeth Bennet e Fitzwilliam Darcy precisam enfrentar julgamentos precipitados, diferenças sociais e o próprio orgulho para compreender seus sentimentos.'
];


try {
    $stmt = $conexao->prepare(
        "UPDATE livros SET sinopse = ? WHERE titulo = ?"
    );

    $stmtVerificar = $conexao->prepare(
        "SELECT id_livro FROM livros WHERE titulo = ?"
    );

    $totalEncontrados = 0;
    $totalNaoEncontrados = 0;
    $titulosNaoEncontrados = [];

    foreach ($sinopses as $titulo => $sinopse) {
        $stmtVerificar->execute([$titulo]);
        $livrosEncontrados = $stmtVerificar->fetchAll(PDO::FETCH_COLUMN);

        if (count($livrosEncontrados) === 0) {
            $totalNaoEncontrados++;
            $titulosNaoEncontrados[] = $titulo;
            continue;
        }

        $stmt->execute([$sinopse, $titulo]);
        $totalEncontrados += count($livrosEncontrados);
    }

    echo "<div style='font-family: Arial; padding: 20px;'>";
    echo "<h2>Conferência das sinopses</h2>";
    echo "<p>Livros encontrados: <strong>{$totalEncontrados}</strong></p>";
    echo "<p>Títulos não encontrados: <strong>{$totalNaoEncontrados}</strong></p>";

    if (!empty($titulosNaoEncontrados)) {
        echo "<h3>Confira estes títulos no banco:</h3><ul>";

        foreach ($titulosNaoEncontrados as $titulo) {
            echo "<li>" . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . "</li>";
        }

        echo "</ul>";
    }

    echo "<p>Conferência concluída.</p>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='font-family: Arial; padding: 20px; color: #c62828;'>";
    echo "<h2>Erro ao atualizar sinopses</h2>";
    echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    echo "</div>";
}
