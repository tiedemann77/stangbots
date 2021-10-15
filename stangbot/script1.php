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

// Lista de páginas
$pages = array("Wikipédia:Pedidos/Proteção","Wikipédia:Pedidos/Restauro","Wikipédia:Pedidos/Notificações de vandalismo","Wikipédia:Pedidos/Revisão de nomes de usuário","Wikipédia:Pedidos/Notificação de incidentes","Wikipédia:Renomeação de conta");

// Total de páginas
$total = count($pages);

// Começa a montar o feed
$text = "<noinclude>";

// Primeira parte do feed: lista de páginas
$control = 1;
foreach ($pages as $key => $value) {

  $text .= "Código " . $control . " = " . $pages[$key] . "

";
  $control++;
}

// Trecho intermediário do feed
$text .= "</noinclude><includeonly>{{#switch: {{{1}}}
";

// Segunda parte do feed: pedidos em aberto em cada página
$control = 1;
foreach ($pages as $key => $value) {

  // Conteúdo total da página
  $content = $robot->api->getContent($pages[$key], 1);

  // Lista de seções
  $sectionList = $robot->api->getSectionList($pages[$key]);

  // Precisa remover uma dessa página
  if($pages[$key]=="Wikipédia:Renomeação de conta"){
    $deleted = array_shift($sectionList);
  }

  // Número de seções
  $sectionNumber = count($sectionList);

  // Remove qualquer coisa comentada, geralmente templates de resposta
  $content = preg_replace($htmlcommentRegex, "", $content);

  // Conta o número de templates de resposta na página
  preg_match_all($closedRegex, $content, $out);
  $closed = count($out[0]);

  // Número de pedidos em aberto
  $open = $sectionNumber-$closed;

  // Se menor que 0, ocorreu algum erro então parar
  if($open<0){
    exit($robot->log->log("Número de pedidos em aberto para " . $pages[$key] . " menor que 0. Fechando...\r\n"));
  }

  echo $robot->log->log("Checando " . $pages[$key] . ": total " . $sectionNumber . "; fechados " . $closed . "; abertos " . $open . ".\r\n");

  // Adiciona linha no feed
  $text .= " | " . $control . " = " . $open . "
";

  $control++;

}

// Rodapé
$text .= " | 0
}}</includeonly>";

// Verifica se precisa atualizar o feed
$content = $robot->api->getContent("User:Stangbot/feed", 0);

if($content==$text){
  // Nada a editar, para script
  $robot->bye("Nenhuma edição precisa ser feita. Fechando...\r\n");
}

// Editando a página de pedidos
$robot->edit("User:Stangbot/feed", $text, "[[WP:Bot|bot]]: atualizando", 1, 0);

// PARA TESTE
// ADICIONAR O CONTEÚDO DA EDIÇÃO EM LOG
//$robot->log->log("Conteúdo da variável text:\r\n" . $text. "\r\n");

// Fim
$robot->bye($robot->script . " concluído!\r\n");

?>
