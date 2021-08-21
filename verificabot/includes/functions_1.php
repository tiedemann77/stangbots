<?php

// Função que obtém os casos listados na paǵina de pedidos
function getOpenCasesList($content) {

	// Obtendo nome do usuário)
  preg_match_all("/\|.{0,}\|/", $content, $out);

  $out2 = array();

  foreach ($out as $key => $value) {
  	$newvalue = str_replace("|","",$value);
   	$out2[$key] = $newvalue;
  }

  // Verificando se a última entrada (exemplo) está presente ou não
  $counttotal = count($out2[0]);
  $counttotal = $counttotal-1;

  if($out2[0][$counttotal]=="Insira o nome aqui"){
    // Se sim, desconsiderar o exemplo "Insira o nome aqui"
    $deleted = array_pop($out2[0]);
  }

	// Contando casos listados
	$numberOpenCases = count($out2[0]);

	// Se é 0, sem casos abertos, parar script
	if ($numberOpenCases===0) {
		$logmsg = logging("Não há casos abertos. Fechando...\r\n");
		exit($logmsg);
	}else {
		// Se não, retorna lista de casos
		return $out2;
	}

}

// Função para verificar quais casos abertos foram fechados
function getClosedCases($opencases) {

  global $BasePage;

  global $redirects;

  // Criando array
  $ClosedCases = array();

  // Controle para o loop
  $control = 0;

  // Loop para verificar cada subpágina de cada caso
  foreach ($opencases[0] as $key => $value) {

    // Título da página
    $CasePage = $BasePage . "/Caso/" . $value;

    // Nome do usuário
    $CaseTitle = $value;

		// Obtém conteúdo da subpágina
		$content = getContent($CasePage, 0);

    //Controles para o próximo loop
    $x = 0;
    $y = 0;

    // Verifica se é um redirect em loop, para evitar duplos, triplos redirects
    while($x == "0") {

      if(preg_match("/(#(R|r)(E|e)(D|d)(I|i)(R|r)(E|e)(C|c)){1,1}[a-zA-Z]{0,9} {0,}\[\[Wikipédia:Pedidos a verificadores\/Caso\//", $content)){

        // Se y == 0, é a primeira vez que passa, salvar redirect original
        if($y=="0"){

          // Se for um redirect, salva o título
          $redirect = $CaseTitle;

        }

        // Se for, detecta a página alvo
        $title = preg_replace("/(#(R|r)(E|e)(D|d)(I|i)(R|r)(E|e)(C|c)){1,1}[a-zA-Z]{0,9} {0,}\[\[Wikipédia:Pedidos a verificadores\/Caso\//", "", $content);
        $title = preg_replace("/\]\].*/","",$title);

        // Reseta as variáveis para o destino do redirect
        $CaseTitle = $title;
        $CasePage = $BasePage . "/Caso/" . $CaseTitle;

        // Obtém o conteúdo da nova página alvo
        $content = getContent($CasePage, 0);

        // Controle 2
        $y = 1;

        // Reinicia para testar se não é um redirect de novo

      }else{

        // Se não for um redirect, para o loop e prossegue
        $x = 1;

      }

    }

    // Se a página do caso existe, verifica se está aberto ou fechado
    if($content!="0"){

      // Conta seções com o padrão dd mm yyyy
      preg_match_all("/== [0-9]{1,2} .{4,9} [0-9]{4,4} ==/", $content, $out);
      $numberSections = count($out[0]);

      // Se o número de seções = 0 é uma página mal-configurada, ignorar
      if($numberSections!="0"){

        // Conta templates de resposta
        preg_match_all("/\{\{(R|r)espondido(2){0,1}\|.{1,}\|/", $content, $out2);
        $numberResponses = count($out2[0]);

        // Se forem iguais, caso concluído
        if ($numberResponses===$numberSections) {

				      // Salva nome do usuário do caso concluído
              $ClosedCases[0][$control] = $CaseTitle;

				      // Verificando os resultados
				      preg_match_all("/(R|r)espondido(2){0,1}\|{1,1}.{4,15}\|{1,1}/", $content, $result);

				      // Apenas o último resultado é relevante
				      $result = array_pop($result[0]);

				      // Limpando resultado, propositalmente complexo por segurança
				      $result = str_ireplace("respondido2","",$result);
				      $result = str_ireplace("respondido","",$result);
				      $result = str_replace("|","",$result);

				      // Salvando resultado do caso concluído
				      $ClosedCases[1][$control] = $result;

              // Se o original for um redirect, salvar o redirect também
              if(isset($redirect)){
                $redirects[$control] = $redirect;

                //Destruindo para próxima verificação
                unset($redirect);
              }

				      // Atualiza para o próximo loop
				      $control++;
				}
      }
    }
  }

	// Se nenhum caso foi fechado ($control==0), parar script
	if ($control==0) {

		$logmsg = logging("Não há nenhum caso concluído para remover. Fechando...\r\n");
		exit($logmsg);

	}

  // Retorna a lista de casos concluídos
  return $ClosedCases;

}

// Função para remover casos encerrados da lista de casos
function updateCaseList( $OpenCases, $ClosedCases, $contentBase ){

    // Array com os redirects
    global $redirects;

    // No primeiro momento, novo conteúdo é igual ao antigo
    $newContent = $contentBase;

    // Obtém o número de casos listados
    $numberOpen = count($OpenCases[0]);

    // Obtém o número de casos fechados
    $numberClosed = count($ClosedCases[0]);

    // Se forem iguais, remove-se todos então simplesmente usar o template padrão com {{nenhum}};
    if($numberOpen===$numberClosed){
      $newContent = '__NOTOC__
{{Wikipédia:Pedidos a verificadores/Cabeçalho}}
{{Wikipédia:Pedidos a verificadores/Caixa3}}
= Investigações em andamento =
{| class="wikitable sortable center"
|+
!Caso
!(Re)Aberto em
|-
|{{nenhum}}
|{{nenhum}}
<!--{{Wikipédia:Pedidos a verificadores/Listar|Insira o nome aqui|~~~~~}}-->
|}
{{Wikipédia:Pedidos a verificadores/Recentes}}';
    }else {
      // Caso não, remover linhas específicas
      // Loop para remover cada linha
      foreach ($ClosedCases[0] as $key => $value) {
        $newContent = preg_replace("/{{Wikipédia:Pedidos a verificadores\/Listar\|$value\|.{1,}/",NULL,$newContent);
      }

      // Loop para remover redirects (que estão em array separado) da lista, se houver
      foreach ($redirects as $key => $value) {
        $newContent = preg_replace("/{{Wikipédia:Pedidos a verificadores\/Listar\|$value\|.{1,}/",NULL,$newContent);
      }

			// Filtrando, para remover linhas em branco
			$newContent = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $newContent);

    }

    // Retorna o novo conteúdo da página principal
    return $newContent;

}

// Função para adicionar casos encerrados na lista de recentes
function updateRecentsList($ClosedCases){

	global $recentsPage;

  // No primeiro momento, novo conteúdo é igual ao antigo
  $newContentRecents = getContent($recentsPage, 1);

	// Verifica se a página de recentes está vazia
	if (preg_match("/\{\{nenhum\}\}/", $newContentRecents)) {

		// Se sim, usa o template vazio para começar
		$newContentRecents = '__NOTOC__
<noinclude>{{Wikipédia:Pedidos a verificadores/Cabeçalho}}</noinclude>
= Investigações encerradas recentemente =
{| class="wikitable sortable center"
|+
!Caso
!Encerrado em
!Resultado
|}
{{Wikipédia:Pedidos a verificadores/Recentes}}';

	}

  // Obtém a data
  $day = date("d");
  $month = date("m");
  $year = date("Y");

  // Mês por extenso
	$month = monthstoPT($month);

  // Data de conclusão do caso
  $date =  $day . " de " . $month . " de " . $year;

  // Montar linha com o nome do caso e data
  foreach ($ClosedCases[0] as $key => $value) {

    $newEntry[$key] = "{{Wikipédia:Pedidos a verificadores/ListarArquivo|" . $value . "|" . $date . "|";

  }

  // Adicionar resultado na linha anterior
  foreach ($ClosedCases[1] as $key => $value) {

    $newEntry[$key] = $newEntry[$key] . $ClosedCases[1][$key] . "}}";

  }

  // Adicionar as novas linhas na página de recentes
  // Chave para buscar e substituir, para adicionar no topo: manter a quebra de linha
  $textKey = "!Resultado
";

  // Montando: manter a quebra de linha
  foreach ($ClosedCases[0] as $key => $value) {

    $textKey = $textKey . $newEntry[$key] . "
";
  }

  // Substituindo e adicionando
  $newContentRecents = str_replace("!Resultado", $textKey, $newContentRecents);

  // Removendo linhas em branco
  $newContentRecents = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $newContentRecents);

  return $newContentRecents;
}

?>
