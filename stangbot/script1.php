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
  'script' => "Script 1",
  'url' => "https://pt.wikipedia.org/w/api.php",
  'maxlag' => 4,
  'file' => __DIR__ .  "/../log.log",
  'stats' => array(),
  'replicasDB' => "ptwiki",
  'personalDB' => "s54852__stangbots"
];

$robot = new bot();

echo $robot->log->log($robot->username . " - Iniciando " . $robot->script . "\r\n");

$feed = "Usuário(a):Stangbot/feed";

$pages = array("Wikipédia:Pedidos/Proteção","Wikipédia:Pedidos/Restauro","Wikipédia:Pedidos/Notificações de vandalismo","Wikipédia:Pedidos/Revisão de nomes de usuário","Wikipédia:Pedidos/Notificação de incidentes","Wikipédia:Renomeação de conta", $feed);

$template = '<noinclude>= Painel de pedidos em aberto =
{| class="wikitable sortable center"
|+
!Código
!Página
!Número de pedidos[[replacekey1]]
|}</noinclude><includeonly>{{#switch: {{{1}}}[[replacekey2]]
 | 0
}}</includeonly>';

$content = $robot->api->getMultipleContent($pages);
$code = 1;
$replacekey1 = "";
$replacekey2 = "";

foreach ($pages as $key => $value) {
  // Ignora o feed
  if($pages[$key]===$feed){
    continue;
  }

  $sections = $robot->api->getSectionList($pages[$key]);

  // Precisa remover uma seção dessa página
  if($pages[$key]=="Wikipédia:Renomeação de conta"){
    $deleted = array_shift($sections);
  }

  $total = count($sections);

  // Remove qualquer coisa comentada, geralmente templates de resposta
  $content[$pages[$key]] = preg_replace($htmlcommentRegex,"",$content[$pages[$key]]);

  // Conta o número de templates de resposta na página
  $closed = preg_match_all($closedRegex,$content[$pages[$key]]);

  $open = $total-$closed;

  if($open<0){
    $robot->bye("Número de pedidos em aberto para " . $pages[$key] . " menor que 0. Fechando...\r\n");
  }

  $replacekey1 .= "
|-
|" . $code . "
|" . $pages[$key] . "
|" . $open;

  $replacekey2 .= "
 | " . $code . " = " . $open . "";

 // Finaliza o loop
 echo $robot->log->log($pages[$key] . ": total " . $total . "; fechados " . $closed . "; abertos " . $open . ".\r\n");
 unset($content[$pages[$key]]);
 $code++;
}

// Aplica novo conteúdo no template
$template = str_replace("[[replacekey1]]",$replacekey1,$template);
$template = str_replace("[[replacekey2]]",$replacekey2,$template);

if($content[$feed]==$template){
  $robot->bye("Nenhuma edição precisa ser feita. Fechando...\r\n");
}

// Editando
$robot->edit($feed,$template,"[[WP:Bot|bot]]: atualizando",1,0);

$robot->bye($robot->script . " concluído!\r\n");

?>
