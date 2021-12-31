<?php

// Requer configurações
require_once(__DIR__ . "/settings.php");

// Requer funções básicas
require_once(__DIR__ . "/../common.php");

// Settings
$settings = [
  'credentials' => $uspw,
  'username' => "VerificaBot",
  'power' => "User:VerificaBot/Power",
  'script' => "Script 4",
  'url' => "https://pt.wikipedia.org/w/api.php",
  'maxlag' => 4,
  'file' => __DIR__ .  "/../log.log",
  'stats' => array(),
  'replicasDB' => "ptwiki",
  'personalDB' => "s54852__stangbots"
];

$robot = new bot();

//Necessário para aumentar os limites de consulta
$robot->login();

echo $robot->log->log($robot->username . " - Iniciando " . $robot->script . "\r\n");

// Página base
$BasePage = "Wikipédia:Pedidos a verificadores";

// Prefixo dos casos
$prefix = $BasePage . "/Caso/";

// Parâmetros para buscar lista de casos
$params = [
  "action" => "query",
  "list" => "prefixsearch",
  "pslimit" => 500,
  "pssearch" => $prefix,
  "format" => "json"
];

$result = $robot->api->request($params);

// O limite é de 500 por consulta, caso houver mais, fazer nova consulta
if(!isset($result['continue'])){
  foreach ($result['query']['prefixsearch'] as $key => $value) {
    $cases[$key] = $value['title'];
  }
}else{
  foreach ($result['query']['prefixsearch'] as $key => $value) {
    $cases[$key] = $value['title'];
  }

  while(isset($result['continue'])){

    $params["psoffset"] = $result['continue']['psoffset'];

    $result = $robot->api->request($params);

    $control = count($cases);

    foreach ($result['query']['prefixsearch'] as $key => $value) {
      $cases[$control] = $value['title'];
      $control++;
    }

  }

}

// Conta o número de casos
$count = count($cases);

// O limite aqui também é de 500, caso necessite de mais, fazer novas consultas
if($count<=500){
  $contents = $robot->api->getMultipleContent($cases);
}else{

  $log = 0;

  while($log<$count){

    $end = $log+500;

    $control = 0;
    while($log<$end){
      if(isset($cases[$log])){
        $casestemp[$control] = $cases[$log];
      }
      $log++;
      $control++;
    }

    $contenttemp = $robot->api->getMultipleContent($casestemp);

    foreach ($contenttemp as $key => $value) {
      $contents[$key] = $contenttemp[$key];
    }

  }

  unset($casestemp);
  unset($contenttemp);

}

// Processa o conteúdo verificando quais estão em aberto
foreach ($contents as $key => $value) {

  preg_match_all("/(==|== )[0-9]{1,2} .{4,9} [0-9]{4,4}.*( ==|==)/", $value, $out);
  $numberSections = count($out[0]);

  if($numberSections!="0"){

    preg_match_all($closedRegex, $value, $out2);
    $numberResponses = count($out2[0]);

    if($numberResponses<$numberSections){
      $opencases[] = $key;
    }

  }

}

unset($contents);
unset($cases);

// Se nenhum está em aberto
if(!isset($opencases)){
  $robot->bye("Não foi encontrado nenhum pedido em aberto. Fechando...\r\n");
}

// Ordem alfabética
$opencases = array_reverse($opencases);

// Formatação
foreach ($opencases as $key => $value) {
  $opencases[$key] = str_replace("Wikipédia:Pedidos a verificadores/Caso/","",$opencases[$key]);
}

// Começando a fazer as alterações necessárias na página principal
$contentBase = $robot->api->getSectionContent($BasePage, 1);

// Remover qualquer coisa que esteja comentanda (normalmente exemplos)
$newContentBase = preg_replace($htmlcommentRegex, "", $contentBase);

$textkey = "|}";

foreach ($opencases as $key => $value) {

  if(!preg_match("/{{Wikipédia:Pedidos a verificadores\/Listar\|$value\|.{1,}/",$contentBase)){
    $textkey = "{{Wikipédia:Pedidos a verificadores/Listar|" . $value . "|~~~~~  <small>(inserido por VerificaBot)<small>}}
" . $textkey;
  }

}

$newContentBase = str_replace("|}", $textkey, $newContentBase);

// Reinsere o exemplo
$textKey = "!(Re)Aberto em";

$example = "
<!--{{Wikipédia:Pedidos a verificadores/Listar|Usuário Exemplo|~~~~~}}-->";
$example = $textKey . $example;

$newContentBase = str_replace($textKey, $example, $newContentBase);

// Filtrando, para remover linhas em branco
$newContentBase = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $newContentBase);

if($newContentBase==$contentBase){
  $robot->bye("Nenhuma edição precisa ser feita. Fechando...\r\n");
}

// Editando a página de pedidos
$robot->editSection($BasePage, 1, $newContentBase, "[[WP:Bot|bot]]: adicionando casos não listados", 0, 1);

// PARA TESTE
// ADICIONAR O CONTEÚDO DA EDIÇÃO EM LOG
//$robot->log->log("Content of newContentBase string:\r\n" . $newContentBase . "\r\n");

// Fim
$robot->bye($robot->script . " concluído!\r\n");

?>
