<?php

// Função que obtém os casos listados na paǵina de pedidos
function getOpenCasesList($content) {
  global $robot;

	// Obtendo nome do usuário)
  preg_match_all("/\|.{0,}\|/", $content, $out);

  $out2 = array();

  foreach ($out as $key => $value) {
  	$out2[$key] = str_replace("|","",$value);
  }

	// Contando casos listados
	$numberOpenCases = count($out2[0]);

	// Se é 0, sem casos abertos, parar script
	if ($numberOpenCases===0) {
		$robot->bye("Não há casos em aberto. Fechando...\r\n");
	}else {
		// Se não, retorna lista de casos
		return $out2;
	}

}

// Função para verificar quais casos abertos foram fechados
function getClosedCases($opencases) {

  global $robot;

  global $BasePage;

  global $closedRegex;

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

    // Resolve possíveis redirecionamentos
    $newPage = $robot->api->resolveRedir($CasePage);

    if($CasePage!=$newPage){

      $redirect = $CaseTitle;

      $CaseTitle = str_replace($BasePage . "/Caso/", "", $newPage);

      $CasePage = $BasePage . "/Caso/" . $CaseTitle;

    }

		// Obtém conteúdo da subpágina
		$content = $robot->api->getContent($CasePage, 0);

    // Se a página do caso existe, verifica se está aberto ou fechado
    if($content!="0"){

      // Conta seções com o padrão dd mm yyyy
      preg_match_all("/== [0-9]{1,2} .{4,9} [0-9]{4,4} .*==/", $content, $out);
      $numberSections = count($out[0]);

      // Se o número de seções = 0 é uma página mal-configurada, ignorar
      if($numberSections!="0"){

        // Conta templates de resposta
        preg_match_all($closedRegex, $content, $out2);
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
		$robot->bye("Não há nenhum caso concluído para remover. Fechando...\r\n");
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
      $newContent = '= Investigações em andamento =
{| class="wikitable sortable center"
|+
!Caso
!(Re)Aberto em
<!--{{Wikipédia:Pedidos a verificadores/Listar|Usuário Exemplo|~~~~~}}-->
|-
|{{nenhum}}
|{{nenhum}}
|}';
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

  global $robot;

	global $recentsPage;

  // No primeiro momento, novo conteúdo é igual ao antigo
  $newContentRecents = $robot->api->getContent($recentsPage, 1);

	// Verifica se a página de recentes está vazia
	if (preg_match("/\{\{nenhum\}\}/", $newContentRecents)) {

		// Se sim, usa o template vazio para começar
		$newContentRecents = '<noinclude>__NOTOC__
= Investigações encerradas recentemente =</noinclude>
{| class="wikitable sortable center"
|+
!Caso
!Encerrado em
!Resultado
|}';

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
