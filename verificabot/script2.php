<?php

/*
    ESTE SCRIPT ATUALIZA OS ARQUIVOS EM WP:PV/Arquivo/Ano/Mês
    A PARTIR DE WP:PV/Recentes
*/

// Requer variáveis básicas
require_once("includes/globals.php");

// Requer funções básicas
require_once(__DIR__ . "/../common.php");

// Requer funções para esse script em específico
require_once("includes/functions_2.php");

// Começa o log
echo logging($logdate . "
Iniciando script 2...\r\n");

// Verifica se o bot está ligado
checkPower();

// FIM DO BÁSICO
//--------------------------------------------------------------------
// INICIANDO SCRIPT EM SI

// Idade para arquivar (em dias)
$older = 15;

// Obtendo conteúdo da página de recentes
$recentsContent = getContent($recentsPage, 1);

// Verificando se há casos antigos para arquivar
$olderCases = getOldCases($older,$recentsContent);

// Remove os casos da página de recentes
$newrecentsContent = removingRecentList($olderCases, $recentsContent);

// Separa os casos a arquivar por mês, [0] = anterior, [1] = atual
$olderCasesFilter = separeMonths($olderCases);

// Obtém a data atual
$month = date("m");
$year = date("Y");

// Converte mês para texto
$monthPT = monthstoPT($month);

// Página de arquivo do mês
$currentPage = $archivePage . $year . "/" . $month;

// Definindo o mês anterior
if($month=="01"){
  $previousMonth = 12;
}else{
  $previousMonth = $month-1;
}

// Ajustando formato com "0" para menores de 10
if($previousMonth<10){
  $previousMonth = "0" . $previousMonth;
}

// Converte mês para texto
$previousMonthPT = monthstoPT($previousMonth);

// Caso seja dezembro, reduzir o ano também;
if($previousMonth=="12"){
    $previousYear = $year-1;
}else{
    $previousYear = $year;
}

// Página do arquivo anterior
$previousPage = $archivePage . $previousYear . "/" . $previousMonth;

// Código base para novas páginas
$source = '__NOTOC__
{{Wikipédia:Pedidos a verificadores/Cabeçalho}}
= Investigações encerradas em month de year =
{| class="wikitable sortable center"
|+
!Caso
!Encerrado em
!Resultado
|}';

// Verifica se há casos para arquivar no mês anterior
if(isset($olderCasesFilter[0])){

  // Obtém o conteúdo do arquivo se existe
  $content = getContent($previousPage, 0);

  // Caso não, cria um novo do zero
  if($content=="0"){

    $newPreviousContent = archiveNew( $source, $olderCasesFilter[0], $previousMonth, $previousMonthPT, $previousYear);

  }else{
    // Caso sim, apenas adiciona os novos arquivos

    $newPreviousContent = archiveOld( $content, $olderCasesFilter[0]);

  }

}

// Verifica se há casos para arquivar no mês atual
if(isset($olderCasesFilter[1])){

  // Obtém o conteúdo do arquivo se existe
  $content = getContent($currentPage, 0);

  // Caso não, cria um novo do zero
  if($content=="0"){

    $newCurrentContent = archiveNew( $source, $olderCasesFilter[1], $month, $monthPT, $year);

  }else{
    // Caso sim, apenas adiciona os novos arquivos

    $newCurrentContent = archiveOld( $content, $olderCasesFilter[1]);

  }

}

// Se o script não parou até agora, há edições por fazer

// Login step 1
$login_Token = getLoginToken();

// Login step 2
loginRequest( $login_Token );

// Obtém edit token
$csrf_Token = getCSRFToken();

// Edita a página de recentes
editRequest($csrf_Token, $recentsPage, $newrecentsContent, "[[WP:Bot|bot]]: arquivando casos antigos", 1, 1);

// PARA TESTE
// Registra em log ao invés de editar
//logging("Content of newrecentsContent string:\r\n" . $newrecentsContent . "\r\n");

// Edita o arquivo do mês anterior, se necessário
if(isset($newPreviousContent)){
  editRequest($csrf_Token, $previousPage, $newPreviousContent, "[[WP:Bot|bot]]: arquivando casos antigos", 1, 1);
  // PARA TESTE
  // Registra em log ao invés de editar
  //logging("Content of newPreviousContent string:\r\n" . $newPreviousContent . "\r\n");
}

// Edita o arquivo do mês atual, se necessário
if(isset($newCurrentContent)){
  editRequest($csrf_Token, $currentPage, $newCurrentContent, "[[WP:Bot|bot]]: arquivando casos antigos", 1, 1);
  // PARA TESTE
  // Registra em log ao invés de editar
  //logging("Content of newCurrentContent string:\r\n" . $newCurrentContent . "\r\n");
}

// Logout
logoutRequest( $csrf_Token );

// Fecha o log
echo logging("Script 2 concluído!\r\n");

?>
