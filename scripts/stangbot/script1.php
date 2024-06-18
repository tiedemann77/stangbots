<?php

// Requer configurações
require_once(__DIR__ . "/settings.php");

// Requer funções básicas
require_once(__DIR__ . "/../../autoloader.php");

// Settings
$settings = [
  'credentials' => $uspw,
  'username' => "Stangbot",
  'power' => "User:Stangbot/Power",
  'script' => "Script 1",
  'url' => "https://pt.wikipedia.org/w/api.php",
  'maxlag' => 4,
  'file' => __DIR__ .  "/log.log",
  'replicasDB' => "ptwiki",
  'personalDB' => "s54852__stangbots"
];

$robot = new bot();

echo $robot->log->log($robot->username . " - Iniciando " . $robot->script . "\r\n");

$list = "User:Stangbot/páginas.json";

$pages = json_decode($robot->api->getContent($list,1), true);

foreach ($pages as $key => $value) {

  if($key==="feed"){
    continue;
  }

  $content[] = $pages[$key]['título'];

}

$template = '<noinclude>= Painel de pedidos em aberto =
{| class="wikitable sortable center"
|+
!Código
!Página
!Número de pedidos[[replacekey1]]
|}</noinclude><includeonly>{{#switch: {{{1}}}[[replacekey2]]
 | 0
}}</includeonly>';

$content = $robot->api->getMultipleContent($content);
$replacekey1 = "";
$replacekey2 = "";

foreach ($pages as $key => $value) {
  // Ignora o feed
  if($key==="feed"){
    continue;
  }

  $sections = $robot->api->getSectionList($pages[$key]['título']);

  if($sections==0){
    $total = 0;
  }else{
    $total = count($sections)-$pages[$key]['ignorar_seções'];
  }

  // Remove qualquer coisa comentada, geralmente templates de resposta
  $content[$pages[$key]['título']] = preg_replace($htmlcommentRegex,"",$content[$pages[$key]['título']]);

  // Conta o número de templates de resposta na página
  $closed = preg_match_all($closedRegex,$content[$pages[$key]['título']]);

  $open = $total-$closed;

  if($open<0){
    $robot->log->log("PROBLEMA: número de pedidos em aberto para " . $pages[$key]['título'] . " menor que 0. Verifique a página.\r\n");
    $open = "-1";
  }

  $replacekey1 .= "
|-
|" . $key . "
|" . $pages[$key]['título'] . "
|" . $open;

  $replacekey2 .= "
 | " . $key . " = " . $open . "";

 // Finaliza o loop
 echo $robot->log->log($pages[$key]['título'] . ": total " . $total . "; fechados " . $closed . "; abertos " . $open . ".\r\n");
 unset($content[$pages[$key]['título']]);
}

// Aplica novo conteúdo no template
$template = str_replace("[[replacekey1]]",$replacekey1,$template);
$template = str_replace("[[replacekey2]]",$replacekey2,$template);

// Editando
$robot->edit($pages['feed']['título'],$template,"[[WP:Bot|bot]]: atualizando",1,1);

$robot->bye($robot->script . " concluído!\r\n");

?>
