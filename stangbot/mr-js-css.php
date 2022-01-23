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
  'script' => "mr-js-css",
  'url' => "https://pt.wikipedia.org/w/api.php",
  'maxlag' => 4,
  'file' => __DIR__ .  "/../log.log",
  'stats' => array(),
  'replicasDB' => "ptwiki",
  'personalDB' => "s54852__stangbots"
];

$robot = new bot();

echo $robot->log->log($robot->username . " - Iniciando " . $robot->script . "\r\n");

$feed = "User:Stangbot/feed-js-css";

$limit = 6;

$domain = array(
  "",
  "Discussão:",
  "User:",
  "User talk:",
  "Wikipédia:",
  "Wikipédia Discussão:",
  "Ficheiro:",
  "Ficheiro Discussão:",
  "MediaWiki:",
  "MediaWiki Discussão:",
  "Predefinição:",
  "Predefinição Discussão:",
  "Ajuda:",
  "Ajuda Discussão:",
  "Categoria:",
  "Categoria Discussão:",
  100 => "Portal:",
  101 => "Portal Discussão:",
  104 => "Livro:",
  105 => "Livro Discussão:",
  446 => "Education Program:",
  447 => "Education Program talk:",
  710 => "TimedText:",
  711 => "TimedText talk:",
  828 => "Módulo:",
  829 => "Módulo Discussão:"
);

echo $robot->log->log("Consultando as últimas " . $limit . " MRs sobre .js...\r\n");

// Consulta para jss
$query = "SELECT page_namespace, page_title, page_latest, rev_timestamp, actor_name FROM page, revision, actor WHERE page_content_model = 'javascript' AND page_latest = rev_id AND rev_actor = actor_id ORDER BY page_latest DESC LIMIT $limit;";

$result = $robot->sql->replicasQuery($query, $params=NULL);

// Cabeçalho
$text = '<div style="background: #F2F2F2; border-top: 1px solid #ccd2d9; border-bottom: 1px solid #ccd2d9; text-align: center;">[[File:Faenza-text-x-javascript.svg|17px]] Edições recentes (.js)</div><small>';

$control = 0;
// Insere um arquivo por linha
foreach ($result as $key => $value) {

  if($control==0){
    $text .= "
" . date( "d-m-Y H:i", strtotime($value['rev_timestamp'])) . " - [[Especial:Diff/" . $value['page_latest'] . "|" . $domain[$value['page_namespace']] . $value['page_title'] . "]] por " . $value['actor_name'];
    $control++;
  }else{
    $text .= "
----
" . date( "d-m-Y H:i", strtotime($value['rev_timestamp'])) . " - [[Especial:Diff/" . $value['page_latest'] . "|" . $domain[$value['page_namespace']] . $value['page_title'] . "]] por " . $value['actor_name'];
    $control++;
  }

}

// Rodapé
$text .= "
</small>";

echo $robot->log->log("Consultando as últimas " . $limit . " MRs sobre .css...\r\n");

// Consulta para css
$query = "SELECT page_namespace, page_title, page_latest, rev_timestamp, actor_name FROM page, revision, actor WHERE page_content_model = 'css' AND page_latest = rev_id AND rev_actor = actor_id ORDER BY page_latest DESC LIMIT $limit;";

$result = $robot->sql->replicasQuery($query, $params=NULL);

// Cabeçalho
$text2 = '
<div style="background: #F2F2F2; border-top: 1px solid #ccd2d9; border-bottom: 1px solid #ccd2d9; text-align: center;">[[File:Crystal Clear app stylesheet.png|17px]] Edições recentes (.css)</div><small>';

$control = 0;
// Insere um arquivo por linha
foreach ($result as $key => $value) {

  if($control==0){
    $text2 .= "
" . date( "d-m-Y H:i", strtotime($value['rev_timestamp'])) . " - [[Especial:Diff/" . $value['page_latest'] . "|" . $domain[$value['page_namespace']] . $value['page_title'] . "]] por " . $value['actor_name'];
    $control++;
  }else{
    $text2 .= "
----
" . date( "d-m-Y H:i", strtotime($value['rev_timestamp'])) . " - [[Especial:Diff/" . $value['page_latest'] . "|" . $domain[$value['page_namespace']] . $value['page_title'] . "]] por " . $value['actor_name'];
    $control++;
  }

}

// Rodapé
$text2 .= "
</small>";

// Juntando
$text .= $text2;

// Checa se é necessário fazer edições
$content = $robot->api->getContent($feed, 0);

if($text===$content){
  $robot->bye("Feed já está atualizado. Fechando...\r\n");
}

echo $robot->log->log("Editando feed...\r\n");
$robot->edit($feed, $text, "[[WP:Bot|bot]]: atualizando edições recentes", 1, 0);

// PARA TESTE
// ADICIONAR O CONTEÚDO DA EDIÇÃO EM LOG
//$robot->log->log("Conteúdo da variável text:\r\n" . $text. "\r\n");

// Fechar log
$robot->bye($robot->script . " concluído!\r\n");

?>
