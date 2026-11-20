<?php
session_start();

// Variáveis do cabeçalho
$page_title = 'Análise de Viés Cognitivo';
$show_export_button = false;

// Lógica do teste
$mostrar = $_GET['teste'] ?? 'intro';

$vieses = [
    'aversao_perda'    => 'Aversão à Perda',
    'efeito_manada'    => 'Efeito Manada',
    'excesso_confianca'=> 'Excesso de Confiança',
    'vies_emocional'   => 'Viés Emocional'
];

$descricoes = [
    'aversao_perda'    => 'Tendência a evitar perdas mesmo quando isso reduz ganhos potenciais.',
    'efeito_manada'    => 'Tendência a seguir decisões coletivas sem análise própria.',
    'excesso_confianca'=> 'Confiança elevada na própria capacidade de decisão.',
    'vies_emocional'   => 'Influência de estados emocionais nas decisões financeiras.'
];

$dicas = [
    'aversao_perda'    => 'Estabeleça regras automáticas de saída e foque no conjunto da carteira, não em posições isoladas.',
    'efeito_manada'    => 'Justifique suas decisões com critérios próprios antes de observar o que outros estão fazendo.',
    'excesso_confianca'=> 'Revise decisões passadas periodicamente e busque ativamente opiniões contrárias às suas.',
    'vies_emocional'   => 'Evite decisões no calor do momento. Implemente a regra das 24 horas antes de qualquer mudança de posição.'
];

/*
   Categorias (nichos)
   Cada categoria contextualiza as mesmas 4 tendências cognitivas em um cenário diferente.
   Para adicionar uma categoria: incluir em $categorias, em $perguntas_triagem
   e as 8 perguntas em $perguntas_categoria.
*/
$categorias = [
    'investidor' => [
        'nome'      => 'Investidor',
        'icon'      => 'fa-chart-line',
        'cor'       => '#ff9100',
        'descricao' => 'Decisões envolvendo investimentos, renda variável e risco de mercado.',
    ],
    'viagem' => [
        'nome'      => 'Viagem',
        'icon'      => 'fa-plane',
        'cor'       => '#38bdf8',
        'descricao' => 'Decisões financeiras relacionadas a viagens, passagens e experiências.',
    ],
    'jogos' => [
        'nome'      => 'Jogos',
        'icon'      => 'fa-gamepad',
        'cor'       => '#a78bfa',
        'descricao' => 'Decisões financeiras dentro do universo gamer: compras, lançamentos e itens.',
    ],
    'consumo' => [
        'nome'      => 'Consumo',
        'icon'      => 'fa-bag-shopping',
        'cor'       => '#f472b6',
        'descricao' => 'Decisões de compra do dia a dia: roupas, delivery, apps e assinaturas.',
    ],
];

/* Perguntas rápidas da triagem: cada opção aponta para uma categoria */
$perguntas_triagem = [
    ['pergunta' => 'Quando você pensa em usar uma reserva financeira extra, o que vem primeiro à cabeça?', 'opcoes' => [
        'investidor' => 'Onde investir esse dinheiro pra ele render mais.',
        'viagem'     => 'Planejar uma viagem ou experiência pra aproveitar.',
        'jogos'      => 'Seria uma boa chance de comprar aquele jogo que eu quero.',
        'consumo'    => 'Aproveitar pra comprar umas coisas que já tava de olho.'
    ]],
    ['pergunta' => 'Você costuma acompanhar com mais frequência:', 'opcoes' => [
        'investidor' => 'Notícias sobre bolsa, câmbio e investimentos.',
        'viagem'     => 'Promoções de passagens, hospedagem e roteiros.',
        'jogos'      => 'Lançamentos, promoções e novidades de jogos.',
        'consumo'    => 'Promoções e novidades de lojas, apps de compra e delivery.'
    ]],
    ['pergunta' => 'Seu maior objetivo financeiro no momento é:', 'opcoes' => [
        'investidor' => 'Fazer o patrimônio crescer no longo prazo.',
        'viagem'     => 'Juntar dinheiro pra próxima viagem.',
        'jogos'      => 'Montar a coleção ou o setup de jogos que eu quero.',
        'consumo'    => 'Conseguir comprar as coisas que eu quero sem estourar o mês.'
    ]],
    ['pergunta' => 'Ao pensar em "gastar demais", o que mais te preocupa?', 'opcoes' => [
        'investidor' => 'Tomar uma decisão de investimento precipitada.',
        'viagem'     => 'Estourar o orçamento durante uma viagem.',
        'jogos'      => 'Gastar mais do que devia em jogos, DLCs ou itens.',
        'consumo'    => 'Gastar mais do que devia em compras do dia a dia ou delivery.'
    ]],
    ['pergunta' => 'Se recebesse um dinheiro inesperado hoje, você:', 'opcoes' => [
        'investidor' => 'Aplicaria em ativos financeiros.',
        'viagem'     => 'Reservaria pra uma viagem dos sonhos.',
        'jogos'      => 'Compraria um jogo ou equipamento novo na hora.',
        'consumo'    => 'Aproveitaria pra comprar algo que já tava de olho.'
    ]],
];

/*
   Perguntas principais por categoria.
   A chave de cada opção (ex.: 'aversao_perda') define a pontuação;
   o texto exibido não nomeia o viés.
*/
$perguntas_categoria = [
    'investidor' => [
        ['pergunta' => 'Após uma queda relevante no mercado, qual ação você tende a considerar primeiro?', 'opcoes' => [
            'aversao_perda'    => 'Ajusto a posição na hora, prefiro sair logo dessa situação.',
            'excesso_confianca'=> 'Mantenho como está, não vejo motivo pra mudar o que decidi.',
            'efeito_manada'    => 'Dou uma olhada em quem também investe pra ver o que estão fazendo.',
            'vies_emocional'   => 'Acabo decidindo ali na hora, conforme o que sinto no momento.'
        ]],
        ['pergunta' => 'Ao analisar um novo ativo, o que mais pesa na sua decisão?', 'opcoes' => [
            'aversao_perda'    => 'Se vejo chance real de dar errado, já penso duas vezes.',
            'excesso_confianca'=> 'Quando bate aquele estalo de que é isso, eu já vou.',
            'efeito_manada'    => 'Olho se é algo que está bombando entre outros investidores.',
            'vies_emocional'   => 'Depende bastante do que eu tô sentindo naquele dia.'
        ]],
        ['pergunta' => 'Quando um investimento não performa como esperado:', 'opcoes' => [
            'aversao_perda'    => 'Prefiro esperar melhorar antes de mexer em qualquer coisa.',
            'excesso_confianca'=> 'Ainda acho que fiz a escolha certa, é só questão de tempo.',
            'efeito_manada'    => 'Fico de olho se outras pessoas também estão saindo.',
            'vies_emocional'   => 'Mexe comigo, e acabo agindo conforme o momento.'
        ]],
        ['pergunta' => 'Em relação a tendências do mercado:', 'opcoes' => [
            'efeito_manada'    => 'Acabo entrando quando vejo aquilo esquentando geral.',
            'excesso_confianca'=> 'Prefiro seguir meu próprio raciocínio, sem depender do que tá em alta.',
            'vies_emocional'   => 'Entro mais quando tô empolgado com alguma coisa.',
            'aversao_perda'    => 'Fico receoso e prefiro ficar de fora.'
        ]],
        ['pergunta' => 'Mudanças na estratégia financeira costumam acontecer quando:', 'opcoes' => [
            'excesso_confianca'=> 'Encontro algo que faz sentido pra mim e já mudo.',
            'vies_emocional'   => 'Bate uma vontade forte e eu acabo mudando de ideia.',
            'efeito_manada'    => 'Vejo o cenário mudando entre quem eu acompanho.',
            'aversao_perda'    => 'Fico com receio de perder e prefiro me proteger.'
        ]],
        ['pergunta' => 'Ao consumir conteúdo financeiro (vídeos, posts, análises):', 'opcoes' => [
            'efeito_manada'    => 'Gosto de ver o que a maioria está achando sobre aquilo.',
            'excesso_confianca'=> 'Leio mais pra confirmar o que eu já pensava mesmo.',
            'vies_emocional'   => 'Acabo sendo levado pelo jeito que a pessoa fala.',
            'aversao_perda'    => 'Prefiro conteúdo que fale de formas mais seguras de agir.'
        ]],
        ['pergunta' => 'Sobre revisar decisões financeiras passadas:', 'opcoes' => [
            'excesso_confianca'=> 'Não vejo muita necessidade, sigo em frente.',
            'aversao_perda'    => 'Prefiro não voltar em decisões que não deram certo.',
            'vies_emocional'   => 'Depende do dia se eu tô a fim de pensar nisso.',
            'efeito_manada'    => 'Gosto de comparar com o que outras pessoas fizeram.'
        ]],
        ['pergunta' => 'Seu comportamento financeiro ao longo do tempo:', 'opcoes' => [
            'vies_emocional'   => 'Muda bastante dependendo de como eu tô.',
            'excesso_confianca'=> 'Segue sempre a mesma linha que eu decidi desde o início.',
            'aversao_perda'    => 'É sempre com o pé atrás, evitando me arriscar.',
            'efeito_manada'    => 'Vai mudando conforme o que vejo acontecendo ao redor.'
        ]],
    ],
    'viagem' => [
        ['pergunta' => 'Você vê o preço de uma passagem pro seu destino dos sonhos subir de repente. O que você faz?', 'opcoes' => [
            'aversao_perda'    => 'Compro na hora, com medo de o preço ainda subir mais.',
            'excesso_confianca'=> 'Espero, tenho certeza que vai abaixar de novo.',
            'efeito_manada'    => 'Vejo se mais gente também tá de olho nesse destino.',
            'vies_emocional'   => 'Decido ali na hora, no impulso do momento.'
        ]],
        ['pergunta' => 'Na hora de escolher um destino pra próxima viagem, o que mais pesa?', 'opcoes' => [
            'aversao_perda'    => 'Evito lugares que possam sair caro demais ou dar imprevisto.',
            'excesso_confianca'=> 'Vou com o que eu já pesquisei e decidi, sem pensar muito duas vezes.',
            'efeito_manada'    => 'Olho o que tá bombando nas redes antes de decidir.',
            'vies_emocional'   => 'Escolho o que combina com o meu momento agora.'
        ]],
        ['pergunta' => 'Uma viagem planejada não sai como esperado (atraso, gasto extra). Você:', 'opcoes' => [
            'aversao_perda'    => 'Evito cancelar, já que gastei tanto até aqui.',
            'excesso_confianca'=> 'Acho que consigo resolver e ajustar o roteiro sem problema.',
            'efeito_manada'    => 'Vejo como outros viajantes estão lidando com a mesma situação.',
            'vies_emocional'   => 'Reajo mais pelo humor do momento do que por lógica.'
        ]],
        ['pergunta' => 'Sobre destinos que estão "bombando" no momento:', 'opcoes' => [
            'efeito_manada'    => 'Fico com vontade de ir só por estarem em alta.',
            'excesso_confianca'=> 'Prefiro seguir meu próprio roteiro, sem me importar com a moda.',
            'vies_emocional'   => 'Decido pelo que me emociona mais na hora.',
            'aversao_perda'    => 'Evito, com medo de gastar e me arrepender depois.'
        ]],
        ['pergunta' => 'O orçamento de uma viagem costuma mudar quando:', 'opcoes' => [
            'excesso_confianca'=> 'Encontro algo que julgo que vale muito a pena.',
            'vies_emocional'   => 'Sinto um desejo forte ali na hora e acabo mudando.',
            'efeito_manada'    => 'Vejo recomendações de outros viajantes e me animo.',
            'aversao_perda'    => 'Preciso cortar algo pra não estourar o que já planejei.'
        ]],
        ['pergunta' => 'Ao pesquisar sobre um destino antes de ir:', 'opcoes' => [
            'efeito_manada'    => 'Valorizo bastante o que a maioria recomenda.',
            'excesso_confianca'=> 'Confio na minha própria intuição de viajante.',
            'vies_emocional'   => 'Me deixo levar pelas fotos e histórias que vejo.',
            'aversao_perda'    => 'Foco em não cair em nenhuma armadilha ou gasto à toa.'
        ]],
        ['pergunta' => 'Sobre repensar viagens passadas que custaram caro:', 'opcoes' => [
            'excesso_confianca'=> 'Nem penso muito, confio nas escolhas que fiz.',
            'aversao_perda'    => 'Evito lembrar, prefiro nem pensar no valor que gastei.',
            'vies_emocional'   => 'Depende de como eu tô me sentindo ao relembrar.',
            'efeito_manada'    => 'Comparo com o que outras pessoas viveram na mesma viagem.'
        ]],
        ['pergunta' => 'Seu jeito de gastar em viagem ao longo do tempo:', 'opcoes' => [
            'vies_emocional'   => 'Varia bastante, conforme meu humor e vontade do momento.',
            'excesso_confianca'=> 'É consistente com o que eu já planejo desde o início.',
            'aversao_perda'    => 'É sempre cauteloso, focado em não perder dinheiro.',
            'efeito_manada'    => 'Segue o que tá na moda entre quem também viaja.'
        ]],
    ],
    'jogos' => [
        ['pergunta' => 'Aquele jogo que você mais ama anuncia uma sequência ou remaster de surpresa. O que rola?', 'opcoes' => [
            'excesso_confianca'=> 'Já garanto a compra, nem preciso ver gameplay pra saber que vou gostar.',
            'vies_emocional'   => 'Não penso muito, compro na hora e depois vejo.',
            'aversao_perda'    => 'Espero baixar de preço ou entrar numa promoção antes de soltar o dinheiro.',
            'efeito_manada'    => 'Dou uma olhada em quem já jogou antes de decidir.'
        ]],
        ['pergunta' => 'Um amigo te chama pra jogar algo que você ainda não tem. Como você reage?', 'opcoes' => [
            'efeito_manada'    => 'Compro na hora, assim já entro pra jogar com ele.',
            'excesso_confianca'=> 'Só entro se, depois de pesquisar, achar que realmente vale a pena.',
            'aversao_perda'    => 'Prefiro esperar baixar de preço antes de topar.',
            'vies_emocional'   => 'Depende do quanto eu tô afim naquele momento.'
        ]],
        ['pergunta' => 'Na hora de decidir se compra um jogo no dia do lançamento:', 'opcoes' => [
            'aversao_perda'    => 'Prefiro esperar uns dias, com medo de me arrepender gastando caro.',
            'excesso_confianca'=> 'Já sei que vou curtir, não perco tempo pesquisando muito.',
            'efeito_manada'    => 'Vejo o que a galera tá achando nos primeiros dias.',
            'vies_emocional'   => 'Se bate a vontade na hora, eu compro sem pensar duas vezes.'
        ]],
        ['pergunta' => 'Sobre DLCs, itens ou passe de temporada de um jogo que você joga:', 'opcoes' => [
            'efeito_manada'    => 'Acabo comprando quando vejo que a maioria da galera tem.',
            'excesso_confianca'=> 'Compro o que acho que vale, sem me preocupar com o que os outros dizem.',
            'vies_emocional'   => 'Compro quando tô empolgado jogando, sem muito planejamento.',
            'aversao_perda'    => 'Evito, prefiro não arriscar gastar em algo que talvez eu não use.'
        ]],
        ['pergunta' => 'Depois de uma grande promoção de jogos (tipo Steam Sale), como fica seu comportamento?', 'opcoes' => [
            'excesso_confianca'=> 'Compro o que já tinha decidido, sem me empolgar com a promoção em si.',
            'vies_emocional'   => 'Acabo levando mais coisa do que planejava, empolgado com os preços.',
            'efeito_manada'    => 'Levo o que vejo todo mundo comprando e comentando.',
            'aversao_perda'    => 'Só compro o que tenho certeza que vou jogar, pra não desperdiçar.'
        ]],
        ['pergunta' => 'Ao pesquisar sobre um jogo antes de comprar:', 'opcoes' => [
            'efeito_manada'    => 'Gosto de ver a nota e os comentários da maioria.',
            'excesso_confianca'=> 'Confio no meu gosto, quase não leio review de ninguém.',
            'vies_emocional'   => 'Me deixo levar pelo trailer e pela vibe que ele passa.',
            'aversao_perda'    => 'Foco em saber se realmente vale o preço antes de arriscar.'
        ]],
        ['pergunta' => 'Quando um jogo que você comprou não te agrada como esperava:', 'opcoes' => [
            'excesso_confianca'=> 'Acho que só preciso jogar mais um pouco pra pegar o jeito.',
            'aversao_perda'    => 'Fico incomodado(a) em largar, já que gastei nele.',
            'vies_emocional'   => 'Largo na hora, dependendo de como eu tô me sentindo com ele.',
            'efeito_manada'    => 'Vejo se outras pessoas também estão achando ruim.'
        ]],
        ['pergunta' => 'Seu jeito de gastar com jogos ao longo do tempo:', 'opcoes' => [
            'vies_emocional'   => 'Varia bastante, depende muito do meu humor e da fase.',
            'excesso_confianca'=> 'Segue sempre o que eu já decidi que vale a pena comprar.',
            'aversao_perda'    => 'É mais cauteloso, sempre pensando duas vezes antes de gastar.',
            'efeito_manada'    => 'Acompanha o que tá em alta entre quem eu jogo junto.'
        ]],
    ],
    'consumo' => [
        ['pergunta' => 'Você vê um produto que queria com desconto por tempo limitado. O que você faz?', 'opcoes' => [
            'aversao_perda'    => 'Compro na hora, com medo de perder a promoção.',
            'excesso_confianca'=> 'Não corro, sei que vou achar outra promoção parecida depois.',
            'efeito_manada'    => 'Vejo se outras pessoas também estão comprando antes de decidir.',
            'vies_emocional'   => 'Decido ali na hora, no calor da oferta.'
        ]],
        ['pergunta' => 'Na hora de escolher onde pedir comida ou o que comprar num app:', 'opcoes' => [
            'aversao_perda'    => 'Evito pedir de lugares que já me decepcionaram, mesmo que pareça bom.',
            'excesso_confianca'=> 'Vou direto no que eu já gosto, sem procurar muito mais.',
            'efeito_manada'    => 'Olho o que está mais pedido ou avaliado por outras pessoas.',
            'vies_emocional'   => 'Escolho pelo que dá mais vontade na hora.'
        ]],
        ['pergunta' => 'Depois de comprar algo que não usou como esperava:', 'opcoes' => [
            'aversao_perda'    => 'Guardo mesmo assim, já que paguei por aquilo.',
            'excesso_confianca'=> 'Acho que só não usei ainda, mas ainda vale a pena.',
            'efeito_manada'    => 'Vejo se outras pessoas também não usaram o que compraram parecido.',
            'vies_emocional'   => 'Já esqueço rápido e sigo pra próxima coisa que me chamar atenção.'
        ]],
        ['pergunta' => 'Sobre roupas ou itens que estão "em alta" nas redes:', 'opcoes' => [
            'efeito_manada'    => 'Acabo comprando porque parece que todo mundo tem.',
            'excesso_confianca'=> 'Só compro se realmente combina comigo, sem me importar com moda.',
            'vies_emocional'   => 'Compro quando me identifico com aquilo na hora.',
            'aversao_perda'    => 'Evito, com medo de gastar em algo que passa rápido.'
        ]],
        ['pergunta' => 'Assinaturas de apps ou serviços que você paga por mês:', 'opcoes' => [
            'excesso_confianca'=> 'Assino o que acho que vale, sem pesquisar muito outras opções.',
            'vies_emocional'   => 'Assino quando tô empolgado com alguma novidade.',
            'efeito_manada'    => 'Assino o que vejo mais gente comentando e usando.',
            'aversao_perda'    => 'Evito assinar coisa demais, pra não desperdiçar dinheiro.'
        ]],
        ['pergunta' => 'Ao pesquisar antes de comprar algo online:', 'opcoes' => [
            'efeito_manada'    => 'Gosto de ver a nota e os comentários da maioria.',
            'excesso_confianca'=> 'Confio no meu gosto, decido rápido sem pesquisar tanto.',
            'vies_emocional'   => 'Me deixo levar pela foto ou pelo anúncio.',
            'aversao_perda'    => 'Foco em não cair em furada, comparando bastante antes.'
        ]],
        ['pergunta' => 'Sobre repensar compras antigas que não valeram tanto a pena:', 'opcoes' => [
            'excesso_confianca'=> 'Nem penso muito, sigo comprando do meu jeito.',
            'aversao_perda'    => 'Evito lembrar, prefiro nem pensar no que gastei.',
            'vies_emocional'   => 'Depende do meu humor se eu quero pensar nisso.',
            'efeito_manada'    => 'Comparo com o que outras pessoas também compraram e usaram.'
        ]],
        ['pergunta' => 'Seu jeito de gastar no dia a dia (compras, apps, delivery):', 'opcoes' => [
            'vies_emocional'   => 'Varia bastante, depende do meu humor e da vontade do momento.',
            'excesso_confianca'=> 'Segue sempre o que eu já decidi que vale a pena.',
            'aversao_perda'    => 'É mais cauteloso, sempre pensando duas vezes antes de gastar.',
            'efeito_manada'    => 'Acompanha o que está em alta entre quem eu sigo ou conheço.'
        ]],
    ],
];

/* Nível do viés dominante: "Alto" a partir de 50% e "Médio" a partir de 25% */
function nivel_por_percentual(float $percentual): string
{
    return match(true) {
        $percentual >= 50 => 'Alto',
        $percentual >= 25 => 'Médio',
        default            => 'Baixo'
    };
}

/* Ranking dos vieses pela contagem real de respostas. Em empate, vale a ordem de $ordemDesempate */
function ranquear_vieses(array $scores, array $ordemDesempate): array
{
    $original = $scores;
    $posicao  = array_flip($ordemDesempate);

    uksort($scores, fn(string $a, string $b): int =>
        [$original[$b], $posicao[$a]] <=> [$original[$a], $posicao[$b]]
    );

    return array_keys($scores);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo_teste'] ?? '';

    if ($tipo === 'triagem') {

        $scoresCat = array_fill_keys(array_keys($categorias), 0);
        $totalRespondidas = 0;
        foreach ($perguntas_triagem as $i => $q) {
            $num = $i + 1;
            $resposta = $_POST["t$num"] ?? null;
            if (isset($scoresCat[$resposta])) {
                $scoresCat[$resposta]++;
                $totalRespondidas++;
            }
        }

        // Sem respostas válidas, não há o que apurar
        if ($totalRespondidas === 0) {
            header("Location: ?teste=triagem");
            exit;
        }

        // Categorias empatadas no topo, na ordem de $categorias; havendo empate, o usuário escolhe qual seguir
        $maiorVoto           = max($scoresCat);
        $categoriasEmpatadas = array_keys(array_filter($scoresCat, fn(int $v): bool => $v === $maiorVoto));
        $empate              = count($categoriasEmpatadas) > 1;

        // Ordena por votos (maior primeiro), mantendo a contagem original
        $scoresOrdenados = $scoresCat;
        arsort($scoresOrdenados);
        $categoriasOrdenadas  = array_keys($scoresOrdenados);
        $categoriaSugerida    = $categoriasEmpatadas[0];
        $votosVencedor        = $maiorVoto;
        $votosSegundoColocado = $scoresOrdenados[$categoriasOrdenadas[1]] ?? 0;

        // Margem de votos entre o 1º e o 2º colocado (0 em caso de empate)
        $margem = $votosVencedor - $votosSegundoColocado;

        $percentualCat = round(($votosVencedor / $totalRespondidas) * 100);

        $_SESSION['categoria_sugerida'] = [
            'categoria'    => $categoriaSugerida,
            'empate'       => $empate,
            'empatadas'    => $categoriasEmpatadas,
            'percentual'   => $percentualCat,
            'distribuicao' => $scoresCat, // votos por categoria, para o placar
            'margem'       => $margem,
            'total'        => $totalRespondidas,
        ];
        header("Location: ?teste=resultado_triagem");
        exit;

    } elseif ($tipo === 'categoria') {

        $categoriaSelecionada = $_POST['categoria'] ?? '';

        // Valida a categoria recebida via POST
        if (!array_key_exists($categoriaSelecionada, $categorias)) {
            header("Location: ?teste=triagem");
            exit;
        }

        $perguntasDaCategoria = $perguntas_categoria[$categoriaSelecionada];
        $scores = array_fill_keys(array_keys($vieses), 0);
        foreach ($perguntasDaCategoria as $i => $q) {
            $num = $i + 1;
            $resposta = $_POST["p$num"] ?? null;
            if (isset($scores[$resposta])) $scores[$resposta]++;
        }

        $total = array_sum($scores);

        if ($total === 0) {
            header("Location: ?teste=perguntas&categoria=" . urlencode($categoriaSelecionada));
            exit;
        }

        // Percentuais apenas para exibição, arredondados individualmente
        $percentuais = [];
        foreach ($scores as $k => $v) {
            $percentuais[$k] = (int) round(($v / $total) * 100);
        }

        // Ranking pela contagem real de respostas; empates seguem a ordem de $vieses
        $ranking = ranquear_vieses($scores, array_keys($vieses));
        $top     = array_slice($ranking, 0, 3);

        $_SESSION['resultado_geral'] = [
            'categoria'  => $categoriaSelecionada,
            'scores'     => $percentuais,
            'dominante'  => $top[0],
            'secundario' => $top[1] ?? null,
            'terciario'  => $top[2] ?? null,
        ];
        header("Location: ?teste=resultado_geral");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ff9100">
    <title>Finanças Já • <?php echo htmlspecialchars($page_title); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        /* Variáveis */
        :root {
            --bg:          #07070a;
            --bg2:         #0e0e12;
            --surface:     #13131a;
            --surface2:    #1a1a24;
            --border:      rgba(255,255,255,0.06);
            --border2:     rgba(255,255,255,0.12);
            --orange:      #ff9100;
            --orange2:     #ffb347;
            --orange-dim:  rgba(255,145,0,0.12);
            --orange-glow: rgba(255,145,0,0.28);
            --text:        #f0f0f5;
            --text2:       #9898a8;
            --text3:       #5a5a70;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-weight: 300;
            min-height: 100vh;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 40% at 50% -10%, rgba(255,145,0,0.07) 0%, transparent 70%),
                radial-gradient(ellipse 40% 60% at 90% 50%, rgba(139,92,246,0.04) 0%, transparent 60%);
            pointer-events: none;
            z-index: 0;
        }

        .container { position: relative; z-index: 1; }

        /* Tipografia */
        h1,h2,h3,h4,h5 { font-family: 'Syne', sans-serif; }

        .eyebrow {
            font-size: 0.65rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--orange);
            font-weight: 500;
        }

        .hero-title {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(3rem, 8vw, 5.5rem);
            line-height: 0.95;
            letter-spacing: -0.03em;
            background: linear-gradient(160deg, #fff 20%, var(--orange) 85%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Utilitários */
        .glass {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            backdrop-filter: blur(12px);
        }
        .divider { height: 1px; background: var(--border); margin: 40px 0; }

        /* Botões */
        .btn-cta,
        .btn-cta:link,
        .btn-cta:visited,
        .btn-cta:hover,
        .btn-cta:focus,
        .btn-cta:active,
        button.btn-cta,
        a.btn-cta {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 10px !important;
            background: linear-gradient(135deg, #ff9100 0%, #ffb347 100%) !important;
            color: #000000 !important;
            font-family: 'Syne', sans-serif !important;
            font-weight: 700 !important;
            font-size: 0.95rem !important;
            letter-spacing: 0.02em !important;
            border: none !important;
            padding: 16px 36px !important;
            border-radius: 14px !important;
            text-decoration: none !important;
            cursor: pointer !important;
            box-shadow: 0 4px 20px rgba(255,145,0,0.25) !important;
            transition: transform 0.3s cubic-bezier(0.23,1,0.32,1), box-shadow 0.3s !important;
            -webkit-text-fill-color: #000000 !important;
            opacity: 1 !important;
        }
        .btn-cta:hover,
        button.btn-cta:hover,
        a.btn-cta:hover {
            transform: translateY(-4px) scale(1.02) !important;
            box-shadow: 0 16px 40px rgba(255,145,0,0.35) !important;
        }
        .btn-cta:active,
        button.btn-cta:active {
            transform: translateY(0) scale(0.99) !important;
        }
        .btn-cta.full,
        button.btn-cta.full { width: 100% !important; }

        /* Ícones herdam a cor do botão */
        .btn-cta i,
        .btn-cta svg {
            color: #000000 !important;
            -webkit-text-fill-color: #000000 !important;
        }

        .btn-outline-custom {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: transparent;
            color: var(--text2) !important;
            border: 1px solid var(--border2);
            font-family: 'DM Sans', sans-serif;
            font-weight: 400;
            font-size: 0.88rem;
            padding: 14px 28px;
            border-radius: 12px;
            text-decoration: none !important;
            cursor: pointer;
            transition: all 0.25s;
        }
        .btn-outline-custom:hover {
            border-color: var(--orange);
            color: var(--orange) !important;
            background: var(--orange-dim);
        }

        .btn-link-dim {
            display: inline-block;
            background: none;
            border: none;
            color: var(--text3);
            font-size: 0.85rem;
            cursor: pointer;
            text-decoration: none;
            transition: color 0.2s;
            padding: 6px 0;
        }
        .btn-link-dim:hover { color: var(--text2); }

        /* Cards da introdução */
        .bias-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
            height: 100%;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.35s cubic-bezier(0.23,1,0.32,1);
        }
        .bias-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--card-accent, var(--orange)), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .bias-card:hover { border-color: var(--border2); transform: translateY(-5px); box-shadow: 0 24px 48px rgba(0,0,0,0.45); }
        .bias-card:hover::after { opacity: 1; }

        .bias-icon {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        /* Card de desempate */
        .tie-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            cursor: default;
        }
        .tie-card::after { opacity: 1; }
        .tie-card .btn-cta { margin-top: auto; }

        /* Formulário */
        .q-block {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 30px 32px;
            margin-bottom: 14px;
            transition: border-color 0.3s;
        }
        .q-block:has(input:checked) {
            border-color: rgba(255,145,0,0.3);
            background: rgba(255,145,0,0.02);
        }
        .q-num {
            font-size: 0.65rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--orange);
            display: block;
            margin-bottom: 12px;
            font-weight: 500;
        }
        .q-text {
            font-family: 'Syne', sans-serif;
            font-size: 1.05rem;
            font-weight: 600;
            margin-bottom: 20px;
            line-height: 1.4;
        }
        .radio-opt {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 12px 14px;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.2s;
            margin-bottom: 4px;
        }
        .radio-opt:hover { background: rgba(255,255,255,0.04); }
        .radio-opt input[type="radio"] {
            width: 18px; height: 18px;
            accent-color: var(--orange);
            flex-shrink: 0;
            margin-top: 2px;
            cursor: pointer;
        }
        .radio-opt label { color: var(--text2); font-size: 0.9rem; line-height: 1.5; cursor: pointer; transition: color 0.2s; }
        .radio-opt:has(input:checked) label { color: var(--text); }

        /* Progresso */
        .progress-track {
            height: 3px;
            background: var(--surface2);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 40px;
        }
        .progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, var(--orange), var(--orange2));
            border-radius: 10px;
            transition: width 0.5s cubic-bezier(0.23,1,0.32,1);
            box-shadow: 0 0 10px var(--orange-glow);
        }

        /* Top 3 */
        .rank-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 22px 26px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            margin-bottom: 14px;
            transition: all 0.3s cubic-bezier(0.23,1,0.32,1);
        }
        .rank-card.is-first {
            background: linear-gradient(135deg, var(--surface) 55%, rgba(255,145,0,0.05));
            border-color: rgba(255,145,0,0.3);
        }
        .rank-card:hover { transform: translateX(6px); border-color: var(--border2); box-shadow: 0 8px 24px rgba(0,0,0,0.3); }
        .rank-card.is-first:hover { border-color: rgba(255,145,0,0.55); }

        .rank-num {
            width: 36px; height: 36px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .rank-bar-track { height: 4px; background: var(--border2); border-radius: 10px; overflow: hidden; margin-top: 12px; }
        .rank-bar-fill  { height: 100%; border-radius: 10px; }

        /* Centro do gráfico de rosca */
        .donut-center {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            text-align: center;
            padding: 0 16px;
        }

        /* Controle deslizante */
        .range-wrap input[type="range"] {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 4px;
            background: var(--border2);
            border-radius: 10px;
            outline: none;
            cursor: pointer;
        }
        .range-wrap input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 22px; height: 22px;
            border-radius: 50%;
            background: var(--orange);
            cursor: pointer;
            box-shadow: 0 0 0 4px rgba(255,145,0,0.2);
            transition: box-shadow 0.2s;
        }
        .range-wrap input[type="range"]::-webkit-slider-thumb:hover { box-shadow: 0 0 0 8px rgba(255,145,0,0.15); }
        .range-wrap input[type="range"]::-moz-range-thumb {
            width: 22px; height: 22px;
            border-radius: 50%;
            background: var(--orange);
            border: none;
            cursor: pointer;
        }

        /* Resultado final */
        .score-ring {
            width: 148px; height: 148px;
            border-radius: 50%;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            border: 2px solid;
            margin: 0 auto 24px;
            position: relative;
            gap: 2px;
        }
        /* Anel externo */
        .score-ring::before {
            content: '';
            position: absolute;
            inset: -6px;
            border-radius: 50%;
            border: 1px solid currentColor;
            opacity: 0.18;
        }
        .score-ring::after {
            content: '';
            position: absolute;
            inset: -18px;
            border-radius: 50%;
            background: currentColor;
            opacity: 0.05;
            filter: blur(20px);
        }

        /* Resumo do resultado */
        .report-card {
            background: linear-gradient(135deg, var(--surface2), var(--surface));
            border: 1px solid var(--border2);
            border-radius: 22px;
            padding: 28px 32px;
            margin-bottom: 24px;
        }

        .vies-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 40px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 0.78rem;
            border: 1px solid;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            background: none;
        }
        .vies-pill:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.25); }

        .nivel-badge {
            display: inline-block;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 0.68rem;
            letter-spacing: 0.1em;
            padding: 4px 12px;
            border-radius: 20px;
        }

        .mitigacao-box {
            background: rgba(255,145,0,0.07);
            border: 1px solid rgba(255,145,0,0.2);
            border-radius: 16px;
            padding: 22px;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.78);
            backdrop-filter: blur(10px);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }
        .modal-overlay.open { opacity: 1; pointer-events: all; }
        .modal-box {
            background: var(--surface2);
            border: 1px solid var(--border2);
            border-radius: 28px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            max-height: 92vh;
            overflow-y: auto;
            transform: translateY(28px) scale(0.97);
            transition: transform 0.35s cubic-bezier(0.23,1,0.32,1);
            position: relative;
        }
        .modal-overlay.open .modal-box { transform: translateY(0) scale(1); }
        .modal-close-btn {
            position: absolute; top: 18px; right: 18px;
            width: 36px; height: 36px;
            border-radius: 10px;
            background: var(--surface);
            border: 1px solid var(--border2);
            color: var(--text2);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .modal-close-btn:hover { background: var(--border2); color: var(--text); }

        /* Animações */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(22px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .anim   { animation: fadeUp 0.6s cubic-bezier(0.23,1,0.32,1) both; }
        .d1 { animation-delay: 0.08s; }
        .d2 { animation-delay: 0.16s; }
        .d3 { animation-delay: 0.24s; }
        .d4 { animation-delay: 0.32s; }
        .d5 { animation-delay: 0.40s; }

        @media print {
            .no-print { display: none !important; }
            body { background: white; color: black; }
        }
        @media (max-width: 768px) {
            .hero-title { font-size: 3rem; }
            .modal-box { padding: 28px 20px; }
            .report-card { padding: 22px; }
        }
    </style>
</head>
<body>

    <?php include_once 'layouts/header.php'; ?>

    <!-- Modal global -->
    <div class="modal-overlay" id="viesModal" onclick="handleOverlayClick(event)">
        <div class="modal-box">
            <button class="modal-close-btn" onclick="closeModal()"><i class="fas fa-times fa-sm"></i></button>
            <div id="modalBody"></div>
        </div>
    </div>

    <div class="container py-5" style="max-width:880px;">

    <?php
    $bias_meta = [
        'aversao_perda'    => ['cor'=>'#ef4444', 'icon'=>'fa-shield-halved'],
        'efeito_manada'    => ['cor'=>'#f59e0b', 'icon'=>'fa-people-group'],
        'excesso_confianca'=> ['cor'=>'#8b5cf6', 'icon'=>'fa-crown'],
        'vies_emocional'   => ['cor'=>'#10b981', 'icon'=>'fa-heart-pulse'],
    ];

    // Total de perguntas da tela atual, usado na barra de progresso
    $progressoTotal = 8;
    if ($mostrar === 'triagem') {
        $progressoTotal = count($perguntas_triagem);
    } elseif ($mostrar === 'perguntas' && isset($_GET['categoria']) && isset($perguntas_categoria[$_GET['categoria']])) {
        $progressoTotal = count($perguntas_categoria[$_GET['categoria']]);
    }
    ?>

    <!-- Introdução -->
    <?php if ($mostrar === 'intro'): ?>

        <div class="text-center mb-5 mt-4 anim">
            <p class="eyebrow mb-3">Análise Comportamental Avançada</p>
            <h1 class="hero-title mb-4">InvestMind</h1>
            <p style="color:var(--text2);max-width:560px;margin:0 auto 40px;font-size:1rem;line-height:1.75;">
                Mapeie sua arquitetura de decisão. Identifique padrões irracionais e proteja seu capital contra impulsos biológicos.
            </p>
            <a href="?teste=triagem" class="btn-cta">
                <i class="fas fa-compass fa-sm"></i> Iniciar Triagem de Perfil
            </a>
        </div>

        <div class="divider"></div>
        <p class="eyebrow text-center mb-4 anim d1">Vieses Mapeados</p>

        <div class="row g-3 mb-5">
            <?php $d = 1; foreach ($vieses as $key => $nome): $m = $bias_meta[$key]; ?>
            <div class="col-md-6 anim d<?= $d++ ?>">
                <div class="bias-card" style="--card-accent:<?= $m['cor'] ?>;" onclick="openViesModal('<?= $key ?>')">
                    <div class="d-flex align-items-start gap-3">
                        <div class="bias-icon" style="background:<?= $m['cor'] ?>18;color:<?= $m['cor'] ?>;">
                            <i class="fas <?= $m['icon'] ?>"></i>
                        </div>
                        <div>
                            <h5 style="font-weight:700;font-size:1rem;margin-bottom:8px;"><?= $nome ?></h5>
                            <p style="color:var(--text2);font-size:0.85rem;line-height:1.6;margin:0;"><?= $descricoes[$key] ?></p>
                        </div>
                    </div>
                    <div style="margin-top:16px;display:flex;align-items:center;gap:6px;color:<?= $m['cor'] ?>;font-size:0.75rem;font-weight:600;">
                        <span>Saiba mais</span>
                        <i class="fas fa-arrow-right fa-xs"></i>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    <!-- Triagem -->
    <?php elseif ($mostrar === 'triagem'): ?>

        <div style="max-width:700px;margin:0 auto;">
            <div class="text-center mb-5 anim">
                <p class="eyebrow mb-2">Etapa 1 de 2</p>
                <h2 style="font-weight:800;font-size:2rem;">Triagem de Perfil</h2>
                <p style="color:var(--text2);font-size:0.9rem;margin-top:10px;">
                    Antes de mapear seus vieses, vamos descobrir qual cenário financeiro combina mais com você agora.
                </p>
            </div>

            <div class="progress-track"><div class="progress-fill" id="progressBar"></div></div>

            <form method="POST">
                <input type="hidden" name="tipo_teste" value="triagem">
                <?php foreach ($perguntas_triagem as $i => $q): $num = $i + 1; ?>
                <div class="q-block anim" style="animation-delay:<?= $i * 0.06 ?>s">
                    <span class="q-num">Pergunta <?= $num ?> / <?= count($perguntas_triagem) ?></span>
                    <p class="q-text"><?= htmlspecialchars($q['pergunta']) ?></p>
                    <?php foreach ($q['opcoes'] as $val => $label): ?>
                    <div class="radio-opt">
                        <input type="radio" name="t<?= $num ?>" value="<?= $val ?>" id="t<?= $num ?>_<?= $val ?>" required>
                        <label for="t<?= $num ?>_<?= $val ?>"><?= htmlspecialchars($label) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>

                <div class="mt-4">
                    <button type="submit" class="btn-cta full" style="padding:18px 36px;font-size:1rem;">
                        <i class="fas fa-compass fa-sm"></i> Descobrir meu Perfil
                    </button>
                </div>
            </form>
        </div>

    <!-- Resultado da triagem -->
    <?php elseif ($mostrar === 'resultado_triagem' && isset($_SESSION['categoria_sugerida'])): ?>
        <?php
        $sugestao = $_SESSION['categoria_sugerida'];
        $catKey   = $sugestao['categoria'];
        $catInfo  = $categorias[$catKey] ?? null;

        // Se a categoria salva não existir mais, volta para a triagem
        if ($catInfo === null) {
            header("Location: ?teste=triagem");
            exit;
        }

        // Categorias empatadas no topo; com 2 ou mais, o usuário escolhe qual seguir
        $empatadas = array_values(array_filter(
            $sugestao['empatadas'] ?? [$catKey],
            fn($k): bool => isset($categorias[$k])
        ));
        $empate = count($empatadas) > 1;
        ?>

        <?php if ($empate): ?>

        <div style="max-width:760px;margin:0 auto;">
            <div class="text-center mb-5 anim">
                <p class="eyebrow mb-2">Resultado da Triagem</p>
                <h2 style="font-weight:800;font-size:2.1rem;">Deu empate!</h2>
                <p style="color:var(--text2);font-size:0.9rem;line-height:1.7;max-width:480px;margin:12px auto 0;">
                    Suas respostas dividiram igualmente entre <?= count($empatadas) ?> perfis. Escolha qual deles você quer explorar agora — você pode voltar e testar o outro depois.
                </p>
            </div>

            <div class="row g-3 justify-content-center">
                <?php foreach ($empatadas as $idx => $ek): $einfo = $categorias[$ek]; ?>
                <div class="col-md-6 anim d<?= min($idx + 1, 5) ?>">
                    <div class="bias-card tie-card" style="--card-accent:<?= $einfo['cor'] ?>;">
                        <div class="bias-icon mb-3" style="width:60px;height:60px;font-size:1.4rem;background:<?= $einfo['cor'] ?>18;color:<?= $einfo['cor'] ?>;">
                            <i class="fas <?= $einfo['icon'] ?>"></i>
                        </div>
                        <h3 style="font-weight:800;font-size:1.35rem;margin-bottom:8px;">Perfil <?= htmlspecialchars($einfo['nome']) ?></h3>
                        <p style="color:var(--text2);font-size:0.85rem;line-height:1.65;margin-bottom:16px;">
                            <?= htmlspecialchars($einfo['descricao']) ?>
                        </p>
                        <span class="nivel-badge mb-4" style="background:<?= $einfo['cor'] ?>18;color:<?= $einfo['cor'] ?>;">
                            <?= (int) ($sugestao['distribuicao'][$ek] ?? 0) ?>/<?= (int) ($sugestao['total'] ?? 0) ?> votos · <?= $sugestao['percentual'] ?>%
                        </span>
                        <a href="?teste=perguntas&categoria=<?= urlencode($ek) ?>" class="btn-cta full">
                            <i class="fas fa-brain fa-sm"></i> Escolher <?= htmlspecialchars($einfo['nome']) ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="glass p-4 mt-4 anim d2">
                <p class="eyebrow mb-3" style="color:var(--text3);">Placar completo da triagem</p>
                <?php foreach ($categorias as $ck => $cinfo):
                    $votos    = $sugestao['distribuicao'][$ck] ?? 0;
                    $totalTri = $sugestao['total'] ?? 0;
                    $pct      = $totalTri > 0 ? round($votos / $totalTri * 100) : 0;
                ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:0.82rem;color:var(--text2);"><?= htmlspecialchars($cinfo['nome']) ?></span>
                        <span style="font-size:0.82rem;color:var(--text3);"><?= $votos ?>/<?= $totalTri ?> · <?= $pct ?>%</span>
                    </div>
                    <div class="rank-bar-track">
                        <div class="rank-bar-fill" style="width:<?= $pct ?>%;background:<?= $cinfo['cor'] ?>;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="d-grid gap-2 mt-4 anim d3">
                <a href="?teste=triagem" class="btn-outline-custom">
                    <i class="fas fa-rotate-right fa-sm"></i> Refazer a triagem
                </a>
            </div>
        </div>

        <?php else: ?>

        <div style="max-width:620px;margin:0 auto;">
            <div class="text-center mb-5 anim">
                <p class="eyebrow mb-2">Resultado da Triagem</p>
                <h2 style="font-weight:800;font-size:2.1rem;">Seu perfil combina com:</h2>
            </div>

            <div class="glass p-4 p-md-5 text-center anim d1">
                <div class="bias-icon mx-auto mb-3" style="width:64px;height:64px;font-size:1.5rem;background:<?= $catInfo['cor'] ?>18;color:<?= $catInfo['cor'] ?>;">
                    <i class="fas <?= $catInfo['icon'] ?>"></i>
                </div>
                <h3 style="font-weight:800;font-size:1.6rem;margin-bottom:8px;">Perfil <?= htmlspecialchars($catInfo['nome']) ?></h3>
                <p style="color:var(--text2);font-size:0.9rem;line-height:1.7;max-width:420px;margin:0 auto 18px;">
                    <?= htmlspecialchars($catInfo['descricao']) ?>
                </p>
                <span class="nivel-badge" style="background:<?= $catInfo['cor'] ?>18;color:<?= $catInfo['cor'] ?>;">
                    <?= $sugestao['percentual'] ?>% de afinidade nas suas respostas
                </span>
            </div>

            <?php if (($sugestao['margem'] ?? 99) <= 1): ?>
            <div class="glass p-3 mt-3 anim d2" style="border-color:rgba(255,145,0,0.3);">
                <p style="color:var(--text2);font-size:0.82rem;line-height:1.6;margin:0;">
                    <i class="fas fa-triangle-exclamation me-2" style="color:var(--orange);"></i>
                    Esse resultado ficou disputado — outra categoria quase empatou com <?= htmlspecialchars($catInfo['nome']) ?> nas suas respostas. Dá uma olhada no placar abaixo; se quiser um resultado mais claro, vale tentar a triagem de novo.
                </p>
            </div>
            <?php endif; ?>

            <div class="glass p-4 mt-3 anim d2">
                <p class="eyebrow mb-3" style="color:var(--text3);">Placar completo da triagem</p>
                <?php foreach ($categorias as $ck => $cinfo):
                    $votos    = $sugestao['distribuicao'][$ck] ?? 0;
                    $totalTri = $sugestao['total'] ?? 0;
                    $pct      = $totalTri > 0 ? round($votos / $totalTri * 100) : 0;
                ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:0.82rem;color:var(--text2);"><?= htmlspecialchars($cinfo['nome']) ?></span>
                        <span style="font-size:0.82rem;color:var(--text3);"><?= $votos ?>/<?= $totalTri ?> · <?= $pct ?>%</span>
                    </div>
                    <div class="rank-bar-track">
                        <div class="rank-bar-fill" style="width:<?= $pct ?>%;background:<?= $cinfo['cor'] ?>;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="d-grid gap-2 mt-4 anim d2">
                <a href="?teste=perguntas&categoria=<?= urlencode($catKey) ?>" class="btn-cta full" style="padding:18px 36px;">
                    <i class="fas fa-brain fa-sm"></i> Responder perguntas do perfil <?= htmlspecialchars($catInfo['nome']) ?>
                </a>
                <a href="?teste=triagem" class="btn-outline-custom">
                    <i class="fas fa-rotate-right fa-sm"></i> Tentar a triagem novamente
                </a>
            </div>
        </div>

        <?php endif; ?>

    <!-- Perguntas da categoria -->
    <?php elseif ($mostrar === 'perguntas' && isset($_GET['categoria'])): ?>
        <?php
        $categoriaAtual = $_GET['categoria'];

        // Valida a categoria da URL antes de usar
        if (!array_key_exists($categoriaAtual, $categorias)) {
            header("Location: ?teste=triagem");
            exit;
        }

        $perguntasAtuais = $perguntas_categoria[$categoriaAtual];
        $catInfo         = $categorias[$categoriaAtual];
        ?>
        <div style="max-width:700px;margin:0 auto;">
            <div class="text-center mb-5 anim">
                <p class="eyebrow mb-2">Etapa 2 de 2 · Perfil <?= htmlspecialchars($catInfo['nome']) ?></p>
                <h2 style="font-weight:800;font-size:2rem;">Mapeamento Cognitivo</h2>
                <p style="color:var(--text2);font-size:0.9rem;margin-top:10px;">Responda com honestidade — não existe resposta certa ou errada.</p>
            </div>

            <div class="progress-track"><div class="progress-fill" id="progressBar"></div></div>

            <form method="POST">
                <input type="hidden" name="tipo_teste" value="categoria">
                <input type="hidden" name="categoria" value="<?= htmlspecialchars($categoriaAtual) ?>">
                <?php foreach ($perguntasAtuais as $i => $q): $num = $i + 1; ?>
                <div class="q-block anim" style="animation-delay:<?= $i * 0.06 ?>s">
                    <span class="q-num">Questão <?= $num ?> / <?= count($perguntasAtuais) ?></span>
                    <p class="q-text"><?= htmlspecialchars($q['pergunta']) ?></p>
                    <?php foreach ($q['opcoes'] as $val => $label): ?>
                    <div class="radio-opt">
                        <input type="radio" name="p<?= $num ?>" value="<?= $val ?>" id="p<?= $num ?>_<?= $val ?>" required>
                        <label for="p<?= $num ?>_<?= $val ?>"><?= htmlspecialchars($label) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>

                <div class="mt-4">
                    <button type="submit" class="btn-cta full" style="padding:18px 36px;font-size:1rem;">
                        <i class="fas fa-chart-line fa-sm"></i> Gerar Diagnóstico Final
                    </button>
                </div>
            </form>
        </div>

    <!-- Resultado final -->
    <?php elseif ($mostrar === 'resultado_geral' && isset($_SESSION['resultado_geral'])): ?>
        <?php
        $r       = $_SESSION['resultado_geral'];
        $catInfo = $categorias[$r['categoria']] ?? null;

        $labels_ch = array_values($vieses);
        $data_ch   = [];
        foreach (array_keys($vieses) as $k) $data_ch[] = $r['scores'][$k] ?? 0;

        // Reaproveita o ranking calculado no POST
        $top3      = array_values(array_filter([$r['dominante'], $r['secundario'] ?? null, $r['terciario'] ?? null]));
        $opacities = [1, 0.75, 0.55];

        $dominante            = $r['dominante'];
        $percentualDominante  = $r['scores'][$dominante] ?? 0;
        $nivelDominante       = nivel_por_percentual((float) $percentualDominante);

        $nivel_cores = ['Alto'=>'#ef4444', 'Médio'=>'#ff9100', 'Baixo'=>'#10b981'];
        $nivel_bg    = ['Alto'=>'rgba(239,68,68,0.1)', 'Médio'=>'rgba(255,145,0,0.1)', 'Baixo'=>'rgba(16,185,129,0.1)'];
        $nivel_txt   = [
            'Alto'  => 'Este viés exerce forte influência nas suas decisões. Ação imediata recomendada.',
            'Médio' => 'Influência moderada. Atenção situacional pode ajudar a mitigar.',
            'Baixo' => 'Baixa influência identificada. Continue monitorando seu comportamento.'
        ];
        $cor = $nivel_cores[$nivelDominante];

        // Aviso quando o 2º colocado está a até 1 pergunta de distância do dominante
        $percentualSecundario = ($r['secundario'] ?? null) !== null ? ($r['scores'][$r['secundario']] ?? 0) : 0;
        $margemFinalApertada  = ($r['secundario'] ?? null) !== null && ($percentualDominante - $percentualSecundario) <= 13;
        ?>

        <script>
        window.perfilCognitivo = { vies:"<?= $vieses[$dominante] ?>", percentual:<?= $percentualDominante ?>, nivel:"<?= $nivelDominante ?>" };
        localStorage.setItem("perfilCognitivo", JSON.stringify(window.perfilCognitivo));
        fetch("../php/salvar_perfil_cognitivo.php", {
            method:"POST",
            headers:{"Content-Type":"application/json"},
            body: JSON.stringify({ vies:"<?= $dominante ?>", percentual:<?= $percentualDominante ?>, nivel:"<?= $nivelDominante ?>" })
        }).then(r=>r.json()).then(d=>console.log("Perfil salvo:",d)).catch(e=>console.error(e));
        </script>

        <div class="text-center mb-5 anim">
            <p class="eyebrow mb-2">Relatório Final<?php if ($catInfo): ?> · Perfil <?= htmlspecialchars($catInfo['nome']) ?><?php endif; ?></p>
            <h2 style="font-weight:800;font-size:2.2rem;line-height:1.1;">Sua Assinatura Cognitiva</h2>
            <p style="color:var(--text2);margin-top:12px;font-size:0.9rem;">
                Clique em cada viés para entender o que ele significa e como mitigá-lo.
            </p>
        </div>

        <div class="row g-4 align-items-start mb-4">
            <!-- Top 3 -->
            <div class="col-lg-7 anim d1">
                <p class="eyebrow mb-3">Top 3 Vieses Identificados</p>
                <?php foreach ($top3 as $idx => $key):
                    $m     = $bias_meta[$key];
                    $score = $r['scores'][$key] ?? 0;
                    $rNum  = $idx + 1;
                ?>
                <div class="rank-card <?= $rNum===1 ? 'is-first' : '' ?> anim" style="animation-delay:<?= 0.1 + $idx*0.1 ?>s;opacity:<?= $opacities[$idx] ?>;"
                     onclick="openViesModal('<?= $key ?>')">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rank-num" style="background:<?= $m['cor'] ?>18;color:<?= $m['cor'] ?>;"><?= $rNum ?></div>
                        <div class="flex-grow-1" style="min-width:0;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                    <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:0.95rem;"><?= $vieses[$key] ?></span>
                                    <?php if ($rNum === 1): ?>
                                    <span style="font-size:0.58rem;letter-spacing:0.12em;text-transform:uppercase;background:rgba(255,145,0,0.13);color:var(--orange);padding:3px 9px;border-radius:20px;font-weight:700;border:1px solid rgba(255,145,0,0.25);white-space:nowrap;">● Dominante</span>
                                    <?php endif; ?>
                                </div>
                                <span style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.1rem;color:<?= $m['cor'] ?>;flex-shrink:0;margin-left:12px;"><?= $score ?>%</span>
                            </div>
                            <div class="rank-bar-track">
                                <div class="rank-bar-fill" style="width:<?= $score ?>%;background:<?= $m['cor'] ?>;"></div>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right" style="color:var(--text3);font-size:0.7rem;flex-shrink:0;"></i>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-3">
                        <div style="width:26px;height:26px;border-radius:8px;background:<?= $m['cor'] ?>15;color:<?= $m['cor'] ?>;display:flex;align-items:center;justify-content:center;font-size:0.7rem;flex-shrink:0;">
                            <i class="fas <?= $m['icon'] ?>"></i>
                        </div>
                        <span style="color:var(--text3);font-size:0.78rem;line-height:1.4;"><?= $descricoes[$key] ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Gráfico -->
            <div class="col-lg-5 anim d2">
                <div class="glass p-4 text-center mb-3">
                    <p class="eyebrow mb-3" style="color:var(--text3);">Distribuição dos Vieses</p>
                    <div style="position:relative;height:230px;">
                        <canvas id="chartGeral"></canvas>
                        <div class="donut-center">
                            <div style="font-size:0.5rem;color:var(--text3);text-transform:uppercase;letter-spacing:0.22em;font-weight:600;margin-bottom:8px;font-family:'DM Sans',sans-serif;">Predominante</div>
                            <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1rem;color:var(--orange);line-height:1.15;max-width:100px;word-break:break-word;hyphens:none;letter-spacing:-0.01em;"><?= $vieses[$dominante] ?></div>
                            <div style="width:24px;height:1.5px;background:linear-gradient(90deg,transparent,var(--orange),transparent);margin:8px auto;opacity:0.6;"></div>
                            <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.4rem;color:var(--text);letter-spacing:-0.02em;line-height:1;"><?= $percentualDominante ?><span style="font-size:0.75rem;opacity:0.5;font-weight:600;">%</span></div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center gap-3 mt-3">
                        <?php
                        $chart_colors = [
                            'aversao_perda'    => '#ef4444',
                            'efeito_manada'    => '#f59e0b',
                            'excesso_confianca'=> '#8b5cf6',
                            'vies_emocional'   => '#10b981',
                        ];
                        foreach ($vieses as $k => $nome):
                        ?>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="width:10px;height:10px;border-radius:3px;background:<?= $chart_colors[$k] ?>;flex-shrink:0;"></div>
                            <span style="font-size:0.72rem;color:var(--text2);"><?= $nome ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="glass p-3">
                    <p style="color:var(--text2);font-size:0.82rem;line-height:1.65;margin:0;">
                        <i class="fas fa-circle-info me-2" style="color:var(--orange);"></i>
                        Esta distribuição reflete seus <strong style="color:var(--text);">gatilhos automáticos</strong> de decisão<?php if ($catInfo): ?> dentro do cenário de <strong style="color:var(--text);"><?= htmlspecialchars($catInfo['nome']) ?></strong><?php endif; ?>. Toque nos cards para entender cada viés.
                    </p>
                </div>
            </div>
        </div>

        <!-- Pontuação, radar e dica -->
        <div class="row g-4">
            <div class="col-lg-4 anim d2">
                <div class="glass p-4 text-center h-100 d-flex flex-column align-items-center justify-content-center">
                    <p class="eyebrow mb-4" style="color:var(--text3);">Vulnerabilidade ao Viés Principal</p>
                    <div class="score-ring" style="border-color:<?= $cor ?>;color:<?= $cor ?>;">
                        <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.9rem;color:<?= $cor ?>;line-height:1;letter-spacing:-0.03em;"><?= $percentualDominante ?><span style="font-size:1rem;opacity:0.8;">%</span></div>
                        <div style="width:28px;height:1px;background:currentColor;opacity:0.25;margin:6px 0;"></div>
                        <div style="font-size:0.55rem;color:<?= $cor ?>;letter-spacing:0.14em;font-weight:700;text-transform:uppercase;opacity:0.75;">Risco <?= $nivelDominante ?></div>
                    </div>
                    <h5 style="font-weight:700;font-size:1rem;margin-bottom:8px;"><?= $vieses[$dominante] ?></h5>
                    <p style="color:var(--text2);font-size:0.82rem;line-height:1.6;margin:0;"><?= $nivel_txt[$nivelDominante] ?></p>
                    <?php if ($margemFinalApertada): ?>
                    <p style="color:var(--text3);font-size:0.74rem;line-height:1.5;margin-top:12px;">
                        <i class="fas fa-circle-info me-1"></i>Ficou perto do 2º colocado (<?= $vieses[$r['secundario']] ?>, <?= $percentualSecundario ?>%) — vale ficar de olho nos dois.
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-8 anim d3">
                <div class="glass p-4 mb-3">
                    <p class="eyebrow mb-3" style="color:var(--text3);">Mapa Comportamental (Radar)</p>
                    <div style="height:260px;position:relative;">
                        <canvas id="chartRadarFinal"></canvas>
                    </div>
                </div>
                <div class="mitigacao-box">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="fas fa-shield-halved" style="color:var(--orange);"></i>
                        <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:0.82rem;text-transform:uppercase;letter-spacing:0.08em;color:var(--orange);">Estratégia de Mitigação</span>
                    </div>
                    <p style="color:var(--text2);font-size:0.88rem;line-height:1.7;margin:0;"><?= $dicas[$dominante] ?></p>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Botões -->
        <div class="d-flex gap-3 justify-content-center flex-wrap no-print">
            <button onclick="window.print()" class="btn-outline-custom">
                <i class="fas fa-file-pdf" style="color:var(--orange);"></i> Exportar Relatório
            </button>
            <a href="?teste=triagem" class="btn-outline-custom">
                <i class="fas fa-compass fa-sm"></i> Testar outra Categoria
            </a>
            <a href="?teste=intro" class="btn-cta">
                <i class="fas fa-rotate-right fa-sm"></i> Nova Avaliação
            </a>
        </div>

        <script>
        new Chart(document.getElementById('chartGeral'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($labels_ch) ?>,
                datasets: [{
                    data: <?= json_encode($data_ch) ?>,
                    backgroundColor: ['#ef444499','#f59e0b99','#8b5cf699','#10b98199'],
                    borderColor:     ['#ef4444',  '#f59e0b',  '#8b5cf6',  '#10b981'  ],
                    borderWidth: 2,
                    hoverOffset: 10
                }]
            },
            options: {
                maintainAspectRatio: false,
                layout: { padding: 0 },
                plugins: { legend: { display: false } },
                cutout: '70%'
            }
        });

        new Chart(document.getElementById('chartRadarFinal'), {
            type: 'radar',
            data: {
                labels: <?= json_encode($labels_ch) ?>,
                datasets: [{
                    label: 'Assinatura Cognitiva',
                    data: <?= json_encode($data_ch) ?>,
                    fill: true,
                    backgroundColor: 'rgba(255,145,0,0.13)',
                    borderColor: '#ff9100',
                    pointBackgroundColor: '#ff9100',
                    pointBorderColor: '#07070a',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    borderWidth: 2
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    r: {
                        angleLines: { color:'rgba(255,255,255,0.05)' },
                        grid:       { color:'rgba(255,255,255,0.05)' },
                        pointLabels: { color:'#9898a8', font:{size:10,family:'DM Sans',weight:'500'} },
                        ticks: { display:false },
                        suggestedMin: 0, suggestedMax: 100
                    }
                },
                plugins: { legend: { display:false } }
            }
        });
        </script>

    <?php endif; ?>

    </div>

    <!-- Rodapé -->
    <footer class="text-center py-5 mt-5 no-print" style="border-top:1px solid var(--border);">
        <p style="color:var(--text3);font-size:0.75rem;margin-bottom:4px;">InvestMind | Behavior Analysis © 2026</p>
        <p style="color:var(--text3);font-size:0.65rem;opacity:0.55;">Baseado na Teoria da Perspectiva (Kahneman & Tversky) e Heurísticas de Julgamento.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    /* Dados dos vieses */
    const VIES = {
        aversao_perda: {
            titulo:'Aversão à Perda', origem:'Kahneman & Tversky, 1979', cor:'#ef4444', icon:'fa-shield-halved',
            descricao:'O impacto emocional de uma perda é aproximadamente duas vezes maior do que o prazer de um ganho equivalente. Isso leva investidores a manter ativos em queda por muito tempo ou abandonar posições vencedoras prematuramente.',
            sinais:['Segurar ativos perdedores por medo de concretizar o prejuízo','Vender posições lucrativas cedo demais','Evitar investimentos por foco excessivo no risco','Sentir mais dor na perda do que prazer no ganho equivalente'],
            mitigacao:'Defina regras automáticas de stop-loss antes de entrar em qualquer posição. Avalie a carteira como um todo. Use journaling de decisões para identificar padrões recorrentes.'
        },
        efeito_manada: {
            titulo:'Efeito Manada', origem:'Banerjee, 1992', cor:'#f59e0b', icon:'fa-people-group',
            descricao:'Comportamento de imitar decisões de grupos sem análise independente. Em mercados financeiros, manifesta-se como compras em picos de euforia e vendas em momentos de pânico coletivo, amplificando bolhas e crashes.',
            sinais:['Comprar ativos em alta porque "todo mundo está comprando"','Vender em pânico quando o mercado cai coletivamente','Sentir desconforto ao tomar decisões contrárias ao consenso','Confiar em tendências populares sem análise própria'],
            mitigacao:'Documente critérios de investimento antes de agir. Questione: "Eu compraria isso se ninguém soubesse?" Crie regras que exijam pesquisa própria antes de seguir recomendações externas.'
        },
        excesso_confianca: {
            titulo:'Excesso de Confiança', origem:'Odean, 1998', cor:'#8b5cf6', icon:'fa-crown',
            descricao:'Superestimação das próprias habilidades analíticas e da precisão das previsões. Investidores afetados tendem a operar em excesso, diversificar pouco e subestimar riscos, acreditando ter vantagem informacional.',
            sinais:['Acreditar consistentemente que bate o mercado','Tomar posições concentradas com alta convicção','Raramente revisar ou questionar análises anteriores','Atribuir acertos à habilidade própria e erros ao "azar"'],
            mitigacao:'Mantenha um diário de previsões e compare com resultados reais ao longo do tempo. Use métricas objetivas de performance. Busque ativamente opiniões contrárias antes de decidir.'
        },
        vies_emocional: {
            titulo:'Viés Emocional', origem:'Loewenstein et al., 2001', cor:'#10b981', icon:'fa-heart-pulse',
            descricao:'Estados emocionais como medo, euforia, ansiedade ou esperança influenciam decisões financeiras de forma não racional. Decisões sob forte carga emocional raramente refletem análise objetiva de risco e retorno.',
            sinais:['Tomar decisões impulsivas após notícias negativas','Comportamento financeiro inconsistente com o humor do dia','Comprar ativos por entusiasmo sem due diligence adequada','Sentir ansiedade paralisante ao tomar decisões'],
            mitigacao:'Implemente a regra de 24 horas: nunca decida sob emoção intensa. Crie checklists de decisão objetivos. Pratique mindfulness financeiro — observe a emoção sem agir sobre ela.'
        }
    };

    /* Modal */
    function openViesModal(key) {
        const d = VIES[key]; if (!d) return;
        const sinaisHTML = d.sinais.map(s => `
            <div style="display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.05);">
                <div style="width:6px;height:6px;border-radius:50%;background:${d.cor};flex-shrink:0;margin-top:7px;"></div>
                <span style="color:#9898a8;font-size:0.87rem;line-height:1.5;">${s}</span>
            </div>`).join('');

        document.getElementById('modalBody').innerHTML = `
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:22px;padding-right:40px;">
                <div style="width:52px;height:52px;border-radius:14px;background:${d.cor}18;color:${d.cor};display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">
                    <i class="fas ${d.icon}"></i>
                </div>
                <div>
                    <div style="font-size:0.65rem;letter-spacing:0.15em;text-transform:uppercase;color:${d.cor};font-weight:600;margin-bottom:4px;">${d.origem}</div>
                    <h3 style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.4rem;color:#f0f0f5;margin:0;">${d.titulo}</h3>
                </div>
            </div>
            <p style="color:#9898a8;font-size:0.9rem;line-height:1.75;margin-bottom:26px;">${d.descricao}</p>
            <div style="margin-bottom:26px;">
                <p style="font-size:0.65rem;letter-spacing:0.15em;text-transform:uppercase;color:#5a5a70;font-weight:600;margin-bottom:12px;">Sinais de Alerta</p>
                ${sinaisHTML}
            </div>
            <div style="background:${d.cor}0d;border:1px solid ${d.cor}33;border-radius:14px;padding:20px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                    <i class="fas fa-shield-halved" style="color:${d.cor};"></i>
                    <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.08em;color:${d.cor};">Estratégia de Mitigação</span>
                </div>
                <p style="color:#b8b8c8;font-size:0.87rem;line-height:1.65;margin:0;">${d.mitigacao}</p>
            </div>`;

        document.getElementById('viesModal').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('viesModal').classList.remove('open');
        document.body.style.overflow = '';
    }

    function handleOverlayClick(e) {
        if (e.target === document.getElementById('viesModal')) closeModal();
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    /* Barra de progresso */
    document.querySelectorAll('input[type="radio"]').forEach(r => {
        r.addEventListener('change', () => {
            const checked = document.querySelectorAll('input[type="radio"]:checked').length;
            const pb = document.getElementById('progressBar');
            if (pb) pb.style.width = (checked / <?= $progressoTotal ?> * 100) + '%';
        });
    });

    /* Torna toda a caixa da opção clicável, não só o input e o texto */
    document.querySelectorAll('.radio-opt').forEach(opt => {
        opt.addEventListener('click', (e) => {
            if (e.target.tagName === 'INPUT') return; // o input já trata o clique
            const input = opt.querySelector('input[type="radio"], input[type="checkbox"]');
            if (input && !input.disabled) input.click();
        });
    });

    /* Rótulos do controle deslizante */
    function updateRangeVal(num, val) {
        const el = document.getElementById('rangeVal' + num);
        if (el) el.textContent = val;
    }
    </script>

    <?php include 'chatbot.php'; ?>
</body>
</html>