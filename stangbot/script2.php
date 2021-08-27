<?php

// Requer variáveis básicas
require_once("includes/globals.php");

// Requer funções básicas
require_once(__DIR__ . "/../common.php");

// Começa o log
echo logging($logdate . "
Iniciando script 2...\r\n");

// Verifica se o bot está ligado
checkPower();

// FIM DO BÁSICO
//--------------------------------------------------------------------
// INICIANDO SCRIPT EM SI

// Página do relatório
$page = "User:Stanglavine/Testes";

// Seis meses anteriores
$start = date("Y-m-d h:m:s", strtotime("-6 months"));

// Obtendo lista de sysops
$params = [
  "action" => "query",
  "list" => "allusers",
  "augroup" => "sysop",
  "aulimit" => "150",
  "format" => "json"
];

// Faz consulta a API
$result = APIrequest($endPoint, $params);

$sysops = $result['query']['allusers'];

// Removendo usuário do filtro de abusos
$newkey = 0;
foreach ($sysops as $key => $value) {
  if($sysops[$key]['name']!="Filtro de edições"){
    $temp[$newkey]['name'] = $sysops[$key]['name'];
    $newkey++;
  }
}

$sysops = $temp;

unset($params);

// Contando logs por usuário
// Se passar de 14, para porque já cumpre com a política

// Parâmetros básicos para os logs
$params = [
  "action" => "query",
  "list" => "logevents",
  "leprop" => "ids",
  "leend" => $start,
  "lelimit" => "15",
  "format" => "json"
];

foreach ($sysops as $key => $value) {

  $params["leuser"] = $sysops[$key]['name'];

  // Bloqueios
  echo logging("Checando bloqueios para " . $sysops[$key]['name'] . "\r\n");

  $params["letype"] = "block";

  $result = APIrequest($endPoint, $params);

  $totals[$sysops[$key]['name']] = count($result['query']['logevents']);

  if($totals[$sysops[$key]['name']]>14){
    continue;
  }

  // Eliminações
  echo logging("Checando eliminações para " . $sysops[$key]['name'] . "\r\n");

  $params["letype"] = "delete";

  $result = APIrequest($endPoint, $params);

  $totals[$sysops[$key]['name']] = $totals[$sysops[$key]['name']] + count($result['query']['logevents']);

  if($totals[$sysops[$key]['name']]>14){
    continue;
  }

  // Proteções
  echo logging("Checando proteções para " . $sysops[$key]['name'] . "\r\n");

  $params["letype"] = "protect";

  $result = APIrequest($endPoint, $params);

  $totals[$sysops[$key]['name']] = $totals[$sysops[$key]['name']] + count($result['query']['logevents']);

  if($totals[$sysops[$key]['name']]>14){
    continue;
  }

  // Privilégios
  echo logging("Checando privilégios para " . $sysops[$key]['name'] . "\r\n");

  $params["letype"] = "rights";

  $result = APIrequest($endPoint, $params);

  $totals[$sysops[$key]['name']] = $totals[$sysops[$key]['name']] + count($result['query']['logevents']);

  if($totals[$sysops[$key]['name']]>14){
    continue;
  }

  // Mensagens em massa
  echo logging("Checando mensagens em massa para " . $sysops[$key]['name'] . "\r\n");

  $params["letype"] = "massmessage";

  $result = APIrequest($endPoint, $params);

  $totals[$sysops[$key]['name']] = $totals[$sysops[$key]['name']] + count($result['query']['logevents']);

  if($totals[$sysops[$key]['name']]>14){
    continue;
  }

  // Filtro de abusos
  echo logging("Checando filtros de abuso para " . $sysops[$key]['name'] . "\r\n");

  $params["letype"] = "abusefilter";

  $result = APIrequest($endPoint, $params);

  $totals[$sysops[$key]['name']] = $totals[$sysops[$key]['name']] + count($result['query']['logevents']);

  if($totals[$sysops[$key]['name']]>14){
    continue;
  }

}

unset($params);

// Obtém contribuições daqueles que não cumpriram o requisito anterior

// Parâmetros básicos para as contribuições
$params = [
  "action" => "query",
  "list" => "usercontribs",
  "ucprop" => "ids",
  "uclimit" => "500",
  "ucend" => $start,
  "format" => "json"
];

// Domínio Wikipédia
$params["ucnamespace"] = "4";

// Domínio Wikipédia
foreach ($totals as $key => $value) {
  if ($totals[$key]<15){

    echo logging("Contando edições no domínio Wikipédia para " . $key . "\r\n");

    $params["ucuser"] = $key;

    $result = APIrequest($endPoint, $params);

    $wpedits[$key] = count($result['query']['usercontribs']);

  }else{
    $wpedits[$key] = " ----";
  }
}

// Domínio MediaWiki
$params["ucnamespace"] = "8";

// Novamente, em loop para cada usuário
foreach ($totals as $key => $value) {
  if ($totals[$key]<15){

    echo logging("Contando edições no domínio MediaWiki para " . $key . "\r\n");;

    $params["ucuser"] = $key;

    $result = APIrequest($endPoint, $params);

    $mwedits[$key] = count($result['query']['usercontribs']);

  }else{
    $mwedits[$key] = " ----";
  }
}

// Cabeçalho do relatório
$text = '== Atividade dos administradores no último semestre ==
{| class="wikitable"
|-
| style="background:#ccffcc" | Ativo
| style="background:#f5deb3" | Possivelmente inativo
! style="background:#ffcccc" | Provavelmente inativo
|}
{| class="wikitable sortable center"
|+
!Administrador
!Logs<ref>Logs: bloqueio, eliminação, proteção, privilégios de usuário, mensagens em massa e gestão de filtros de abuso.</ref>
!Edições no domínio "Wikipédia"
!Edições no domínio "MediaWiki"
';

// Cada linha do relatório
foreach ($totals as $key => $value) {

  if($totals[$key]>14)
  {
    $text .= '|-style="background:#ccffcc"
|{{user2|' . $key . '}}
';
  }elseif(($totals[$key]+$wpedits[$key]+$mwedits[$key])>14){
    $text .= '|-style="background:#f5deb3"
|{{user2|' . $key . '}} ([[Especial:Privilégios/' . $key . '|gerenciar]])
';
  }else{
    $text .= '|-style="background:#ffcccc"
|{{user2|' . $key . '}} ([[Especial:Privilégios/' . $key . '|gerenciar]])
';
  }

  if($totals[$key]>14){
    $text .= "|>15
";
  }else{
    $text .= "|" . $totals[$key] . "
";
  }

  // Corrigindo para URL
  $keyURL = str_replace(" ","+", $key);
  $dateURL = date("Y-m-d", strtotime("-6 months"));

  if($wpedits[$key]!=0&&$wpedits[$key]!=" ----"){
    $text .= "|" . $wpedits[$key] . " ([https://pt.wikipedia.org/w/index.php?target=" . $keyURL . "&namespace=4&tagfilter=&start=" . $dateURL . "&end=&limit=5000&title=Especial:Contribuições ver])
";
  }else{
    $text .= "|" . $wpedits[$key] . "
";
  }

  if($mwedits[$key]!=0&&$mwedits[$key]!=" ----"){
    $text .= "|" . $mwedits[$key] . " ([https://pt.wikipedia.org/w/index.php?target=" . $keyURL . "&namespace=8&tagfilter=&start=" . $dateURL . "&end=&limit=5000&title=Especial:Contribuições ver])
";
  }else{
    $text .= "|" . $mwedits[$key] . "
";
  }

}

// Rodapé do relatório
$text .= "|}
'''Última atualização: ~~~~~'''

===Notas===";

//Checando se precisa atualizar desde o último relatório
$content = getContent($page, 0);
if($content==$text){
  exit(logging("Nenhuma edição precisa ser feita. Fechando...\r\n"));
}

// Login step 1
$login_Token = getLoginToken();

// Login step 2
loginRequest( $login_Token );

// Obtendo edit token
$csrf_Token = getCSRFToken();

// Editando
editRequest($csrf_Token, $page, $text, "atualizando estatísticas sobre administradores");

// Logout
logoutRequest( $csrf_Token );

// PARA TESTE
// ADICIONAR O CONTEÚDO DA EDIÇÃO EM LOG
//logging("Conteúdo da variável text:\r\n" . $text. "\r\n");

// Fechar log
echo logging("Script 2 concluído!\r\n");

?>
