<?php

// Requer funções para esse script em específico
require_once(__DIR__ . "/includes/functions_2.php");

// Requer configurações
require_once(__DIR__ . "/settings.php");

// Requer funções básicas
require_once(__DIR__ . "/../common.php");

// Settings
$settings = [
  'credentials' => $uspw,
  'username' => "VerificaBot",
  'power' => "User:VerificaBot/Power",
  'script' => "Script 2",
  'url' => "https://pt.wikipedia.org/w/api.php",
  'maxlag' => 4,
  'file' => __DIR__ .  "/../log.log",
  'stats' => array(),
  'replicasDB' => "ptwiki",
  'personalDB' => "s54852__stangbots"
];

$robot = new bot();

echo $robot->log->log($robot->username . " - Iniciando " . $robot->script . "\r\n");

// Página base
$BasePage = "Wikipédia:Pedidos a verificadores";

// Página listando os pedidos concluídos recentemente
$recentsPage = $BasePage . "/Recentes";

// Prefixo dos arquivos
$archivePage = $BasePage . "/Arquivo/";

// Idade para arquivar (em dias)
$older = 15;

// Obtendo conteúdo da página de recentes
$recentsContent = $robot->api->getContent($recentsPage, 1);

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
  $content = $robot->api->getContent($previousPage, 0);

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
  $content = $robot->api->getContent($currentPage, 0);

  // Caso não, cria um novo do zero
  if($content=="0"){

    $newCurrentContent = archiveNew( $source, $olderCasesFilter[1], $month, $monthPT, $year);

  }else{
    // Caso sim, apenas adiciona os novos arquivos

    $newCurrentContent = archiveOld( $content, $olderCasesFilter[1]);

  }

}

// Se o script não parou até agora, há edições por fazer

// Edita a página de recentes
$robot->edit($recentsPage, $newrecentsContent, "[[WP:Bot|bot]]: arquivando casos antigos", 1, 1);

// PARA TESTE
//$robot->log->log("Content of newrecentsContent string:\r\n" . $newrecentsContent . "\r\n");

// Edita o arquivo do mês anterior, se necessário
if(isset($newPreviousContent)){
  $robot->edit($previousPage, $newPreviousContent, "[[WP:Bot|bot]]: arquivando casos antigos", 1, 1);
  // PARA TESTE
  //$robot->log->log("Content of newPreviousContent string:\r\n" . $newPreviousContent . "\r\n");
}

// Edita o arquivo do mês atual, se necessário
if(isset($newCurrentContent)){
  $robot->edit($currentPage, $newCurrentContent, "[[WP:Bot|bot]]: arquivando casos antigos", 1, 1);
  // PARA TESTE
  //$robot->log->log("Content of newCurrentContent string:\r\n" . $newCurrentContent . "\r\n");
}

// Fim
$robot->bye($robot->script . " concluído!\r\n");

?>
