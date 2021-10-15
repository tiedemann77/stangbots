<?php

// Requer configurações
require_once(__DIR__ . "/settings.php");

// Requer funções básicas
require_once(__DIR__ . "/../common.php");

// Settings
$settings = [
  'credentials' => $uspw,
  'username' => "Stangbot",
  'power' => "User:Stangbot/Power",
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

// Página do relatório
$page = "Wikipédia:Burocratas/Atividade dos administradores";

// Seis meses anteriores
$start = date("Y-m-d", strtotime("-6 months")) . " 00:00:00";

// Obtendo lista de sysops
$params = [
  "action" => "query",
  "list" => "allusers",
  "augroup" => "sysop",
  "aulimit" => "150",
  "format" => "json"
];

// Faz consulta a API
$result = $robot->api->request($params);

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
  echo $robot->log->log("Checando bloqueios para " . $sysops[$key]['name'] . "\r\n");

  $params["letype"] = "block";

  $result = $robot->api->request($params);

  $totals[$sysops[$key]['name']] = count($result['query']['logevents']);

  if($totals[$sysops[$key]['name']]>14){
    continue;
  }

  // Eliminações (mais complexo pois precisa remover delete_redir)
  echo $robot->log->log("Checando eliminações para " . $sysops[$key]['name'] . "\r\n");

  $params["letype"] = "delete";

  $params["lelimit"] = "250";

  $params["leprop"] = "type";

  $result = $robot->api->request($params);

  $totals[$sysops[$key]['name']] += count($result['query']['logevents']);

  $note[$sysops[$key]['name']] = "";

  if(isset($result['query']['logevents'])){
    $count_delredir = 0;
    foreach ($result['query']['logevents'] as $key2 => $value2) {
      if($result['query']['logevents'][$key2]['action']=="delete_redir"){
        $count_delredir++;
      }
    }

    if($count_delredir>235){
      $note[$sysops[$key]['name']] = "<ref>Os registros de eliminações para " . $sysops[$key]['name'] . " podem não ter sido completamente contabilizados. Verifique manualmente antes de tomar qualquer decisão.</ref>";
    }

    $totals[$sysops[$key]['name']] -= $count_delredir;
  }

  if($totals[$sysops[$key]['name']]>14){
    continue;
  }

  // Proteções
  echo $robot->log->log("Checando proteções para " . $sysops[$key]['name'] . "\r\n");

  // A partir daqui, podemos voltar com valores mais restritos
  $params["lelimit"] = "15";
  $params["leprop"] = "ids";

  $params["letype"] = "protect";

  $result = $robot->api->request($params);

  $totals[$sysops[$key]['name']] += count($result['query']['logevents']);

  if($totals[$sysops[$key]['name']]>14){
    continue;
  }

  // Privilégios
  echo $robot->log->log("Checando privilégios para " . $sysops[$key]['name'] . "\r\n");

  $params["letype"] = "rights";

  $result = $robot->api->request($params);

  $totals[$sysops[$key]['name']] += count($result['query']['logevents']);

  if($totals[$sysops[$key]['name']]>14){
    continue;
  }

  // Mensagens em massa
  echo $robot->log->log("Checando mensagens em massa para " . $sysops[$key]['name'] . "\r\n");

  $params["letype"] = "massmessage";

  $result = $robot->api->request($params);

  $totals[$sysops[$key]['name']] += count($result['query']['logevents']);

  if($totals[$sysops[$key]['name']]>14){
    continue;
  }

  // Filtro de abusos
  echo $robot->log->log("Checando filtros de abuso para " . $sysops[$key]['name'] . "\r\n");

  $params["letype"] = "abusefilter";

  $result = $robot->api->request($params);

  $totals[$sysops[$key]['name']] += count($result['query']['logevents']);

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

    echo $robot->log->log("Contando edições no domínio Wikipédia para " . $key . "\r\n");

    $params["ucuser"] = $key;

    $result = $robot->api->request($params);

    $wpedits[$key] = count($result['query']['usercontribs']);

    if($wpedits[$key]==500){
      $wpedits_text[$key] = "+500";
    }else{
      $wpedits_text[$key] = $wpedits[$key];
    }

  }else{
    $wpedits[$key] = 0;
    $wpedits_text[$key] = " ----";
  }
}

// Domínio MediaWiki
$params["ucnamespace"] = "8";

// Novamente, em loop para cada usuário
foreach ($totals as $key => $value) {
  if ($totals[$key]<15){

    echo $robot->log->log("Contando edições no domínio MediaWiki para " . $key . "\r\n");;

    $params["ucuser"] = $key;

    $result = $robot->api->request($params);

    $mwedits[$key] = count($result['query']['usercontribs']);

    if($mwedits[$key]==500){
      $mwedits_text[$key] = "+500";
    }else{
      $mwedits_text[$key] = $mwedits[$key];
    }

  }else{
    $mwedits[$key] = 0;
    $mwedits_text[$key] = " ----";
  }
}

// Cabeçalho do relatório
$text = '== Atividade dos administradores no último semestre ==
{| class="wikitable"
|-
| style="background:#ccffcc" | Ativo(a)
| style="background:#f5deb3" | Possivelmente inativo(a)
! style="background:#ffcccc" | Provavelmente inativo(a)
|}
{| class="wikitable sortable center"
|+
!Administrador(a)
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
    $text .= "|" . $totals[$key] . $note[$key] . "
";
  }

  // Corrigindo para URL
  $keyURL = str_replace(" ","+", $key);
  $dateURL = date("Y-m-d", strtotime("-6 months"));

  if($wpedits[$key]!=0){
    $text .= "|" . $wpedits_text[$key] . " ([https://pt.wikipedia.org/w/index.php?target=" . $keyURL . "&namespace=4&tagfilter=&start=" . $dateURL . "&end=&limit=5000&title=Especial:Contribuições ver])
";
  }else{
    $text .= "|" . $wpedits_text[$key] . "
";
  }

  if($mwedits[$key]!=0){
    $text .= "|" . $mwedits_text[$key] . " ([https://pt.wikipedia.org/w/index.php?target=" . $keyURL . "&namespace=8&tagfilter=&start=" . $dateURL . "&end=&limit=5000&title=Especial:Contribuições ver])
";
  }else{
    $text .= "|" . $mwedits_text[$key] . "
";
  }

}

// Rodapé do relatório
$text .= "|}
'''Última atualização: ~~~~~'''

===Notas===";

//Checando se precisa atualizar desde o último relatório
$content = $robot->api->getContent($page, 0);
if($content===$text){
  $robot->bye("Nenhuma edição precisa ser feita. Fechando...\r\n");
}

// Editando
$robot->edit($page, $text, "[[WP:Bot|bot]]: atualizando estatísticas sobre administradores", 0, 0);

// PARA TESTE
// ADICIONAR O CONTEÚDO DA EDIÇÃO EM LOG
//$robot->log->log("Conteúdo da variável text:\r\n" . $text. "\r\n");

// Fechar log
$robot->bye($robot->script . " concluído!\r\n");

?>
