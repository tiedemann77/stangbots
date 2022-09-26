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
  'script' => "Script 4",
  'url' => "https://pt.wikipedia.org/w/api.php",
  'maxlag' => 4,
  'file' => __DIR__ .  "/log.log",
  'replicasDB' => "ptwiki",
  'personalDB' => "s54852__stangbots"
];

$robot = new bot();

echo $robot->log->log($robot->username . " - Iniciando " . $robot->script . "\r\n");

// Páginas
$pages = [
    "Ajuda:Conteúdo restrito/Lista de imagens com dimensões excessivas",
    "Ajuda:Conteúdo restrito/Lista de arquivos com múltiplas versões",
    "Ajuda:Conteúdo restrito/Lista de áudios com duração excessiva"
];

// Relatório 1
function firstReport(){

  global $robot;

  // Log
  echo $robot->log->log("Gerando lista 1...\r\n");

  // Consulta total de itens para percentagem
  $query = 'SELECT COUNT(*) FROM image WHERE img_media_type = "BITMAP" OR img_media_type = "DRAWING";';

  $result = $robot->sql->replicasQuery($query, $params=NULL);

  $totalDB = $result[0][0];

  // Consulta para a lista
  $query = 'SELECT img_name, img_height FROM image WHERE img_height > 500 ORDER BY img_name ASC;';

  $result = $robot->sql->replicasQuery($query, $params=NULL);

  $total = 0;

  // Somente se houver resultados, personaliza relatório
  if(isset($result[0])){

    // Cabeçalho
    $text = "Esta é uma página de manutenção atualizada periodicamente que lista imagens em desacordo com a [[WP:URC|política de uso restrito de conteúdo]] porque possuem mais de 500 pixels de altura.

<div style=" . '"' . "float: right; width: 25%;" . '"' . ">
'''Percentagem do total:'''
{{percentagem|-percentagem1-|FF7F50}}
</div>

'''Imagens listadas''': -total1-

'''Última atualização''': {{REVISIONDAY2}}-{{REVISIONMONTH}}-{{REVISIONYEAR}}

== Lista ==
{| class=" . '"' . "wikitable sortable" . '"' . "
|+
!Arquivo
!Altura (em pixels)";

    // Insere um arquivo por linha
    foreach ($result as $key => $value) {
      $text .= "
|-
|[[:Ficheiro:" . $result[$key][0] . "|" . $result[$key][0] . "]]
|" . $result[$key][1];
      $total++;
    }

    // Rodapé do relatório
    $text .= "
|}

[[Categoria:!Relatórios de manutenção para arquivos de uso restrito]]";

    // Personaliza o cabeçalho com os totais e percentagem
    $percent = sprintf("%.2f", (($total*100)/$totalDB));
    $text = str_replace("-total1-", $total, $text);
    $text = str_replace("-percentagem1-", $percent, $text);

  }else{
    // Se não houver, texto padrão
    $text = "Esta é uma página de manutenção atualizada periodicamente que lista imagens em desacordo com a [[WP:URC|política de uso restrito de conteúdo]] porque possuem mais de 500 pixels de altura.

<div style=" . '"' . "float: right; width: 25%;" . '"' . ">
'''Percentagem do total:'''
{{percentagem|0|FF7F50}}
</div>

'''Imagens listadas''': 0

'''Última atualização''': {{REVISIONDAY2}}-{{REVISIONMONTH}}-{{REVISIONYEAR}}

== Lista ==
{| class=" . '"' . "wikitable sortable" . '"' . "
|+
!Arquivo
!Altura (em pixels)
|-
|{{nenhum}}
|{{nenhum}}
|}

[[Categoria:!Relatórios de manutenção para arquivos de uso restrito]]";
  }

  return array($text, $total);

}

// Relatório 2
function secondReport(){

  global $robot;

  echo $robot->log->log("Gerando lista 2...\r\n");

  $query = 'SELECT COUNT(*) FROM image;';

  $result = $robot->sql->replicasQuery($query, $params=NULL);

  $totalDB = $result[0][0];

  $query = 'SELECT img_name, img_height, oi_name, oi_archive_name FROM image, oldimage WHERE img_name = oi_name ORDER BY img_name ASC;';

  $result = $robot->sql->replicasQuery($query, $params=NULL);

  $total = 0;

  if(isset($result[0])){

    $text = "Esta é uma página de manutenção atualizada periodicamente que lista arquivos carregados por meio da [[WP:URC|política de uso restrito de conteúdo]] que possuem mais de uma versão. De acordo com a política, versões anteriores desses arquivos [[Wikipédia:Conteúdo_restrito#Versões_anteriores|devem ser eliminadas]].

<div style=" . '"' . "float: right; width: 25%;" . '"' . ">
'''Percentagem do total:'''
{{percentagem|-percentagem1-|FF7F50}}
</div>

'''Arquivos listados''': -total1-

'''Última atualização''': {{REVISIONDAY2}}-{{REVISIONMONTH}}-{{REVISIONYEAR}}

== Lista ==
{| class=" . '"' . "wikitable sortable" . '"' . "
|+
!Arquivo";

    foreach ($result as $key => $value) {
      $temp = preg_quote($result[$key][0]);
      if(!preg_match("/" . $temp . "/", $text)){
        $text .= "
|-
|[[:Ficheiro:" . $result[$key][0] . "|" . $result[$key][0] . "]]";
        $total++;
      }
    }

    $text .= "
|}

[[Categoria:!Relatórios de manutenção para arquivos de uso restrito]]";

    $percent = sprintf("%.2f", (($total*100)/$totalDB));
    $text = str_replace("-total1-", $total, $text);
    $text = str_replace("-percentagem1-", $percent, $text);
  }else{
    // Sem resultados, texto padrão
    $text = "Esta é uma página de manutenção atualizada periodicamente que lista arquivos carregados por meio da [[WP:URC|política de uso restrito de conteúdo]] que possuem mais de uma versão. De acordo com a política, versões anteriores desses arquivos [[Wikipédia:Conteúdo_restrito#Versões_anteriores|devem ser eliminadas]].

<div style=" . '"' . "float: right; width: 25%;" . '"' . ">
'''Percentagem do total:'''
{{percentagem|0|FF7F50}}
</div>

'''Arquivos listados''': 0

'''Última atualização''': {{REVISIONDAY2}}-{{REVISIONMONTH}}-{{REVISIONYEAR}}

== Lista ==
{| class=" . '"' . "wikitable sortable" . '"' . "
|+
!Arquivo
|-
|{{nenhum}}
|}

[[Categoria:!Relatórios de manutenção para arquivos de uso restrito]]";
  }

  return array($text, $total);

}

// Relatório 3
function thirdReport(){

  global $robot;

  echo $robot->log->log("Gerando lista 3...\r\n");

  $query = 'SELECT COUNT(*) FROM image WHERE img_media_type = "AUDIO";';

  $result = $robot->sql->replicasQuery($query, $params=NULL);

  $totalDB = $result[0][0];

  $query = 'SELECT img_name, img_metadata FROM image WHERE img_media_type = "AUDIO" ORDER BY img_name ASC;';

  $result = $robot->sql->replicasQuery($query, $params=NULL);

  $total = 0;

  if(isset($result[0])){

    $text = "Esta é uma página de manutenção atualizada periodicamente que lista arquivos de áudio em desacordo com a [[WP:URC|política de uso restrito de conteúdo]] porque possuem mais de 30 segundos de duração (excessos menores que 1 segundo são ignorados).

<div style=" . '"' . "float: right; width: 25%;" . '"' . ">
'''Percentagem do total:'''
{{percentagem|-percentagem1-|FF7F50}}
</div>

'''Arquivos listados''': -total1-

'''Última atualização''': {{REVISIONDAY2}}-{{REVISIONMONTH}}-{{REVISIONYEAR}}

== Lista ==
{| class=" . '"' . "wikitable sortable" . '"' . "
|+
!Arquivo
!Duração (segundos)";

    // Insere cada linha se for maior que 31
    foreach ($result as $key => $value) {
      $name = $result[$key][0];
      $metadata = json_decode($result[$key][1],true);
      $lenght = $metadata['data']['length'];
      if($lenght>31){
        $text .= "
|-
|[[:Ficheiro:" . $name . "|" . $name . "]]
|" . $lenght;
        $total++;
      }
    }

    $text .= "
|}

[[Categoria:!Relatórios de manutenção para arquivos de uso restrito]]";

    $percent = sprintf("%.2f", (($total*100)/$totalDB));
    $text = str_replace("-total1-", $total, $text);
    $text = str_replace("-percentagem1-", $percent, $text);
  }

  if(!isset($result[0])||$total==0){
    $text = "Esta é uma página de manutenção atualizada periodicamente que lista arquivos de áudio em desacordo com a [[WP:URC|política de uso restrito de conteúdo]] porque possuem mais de 30 segundos de duração (excessos menores que 1 segundo são ignorados).

<div style=" . '"' . "float: right; width: 25%;" . '"' . ">
'''Percentagem do total:'''
{{percentagem|0|FF7F50}}
</div>

'''Arquivos listados''': 0

'''Última atualização''': {{REVISIONDAY2}}-{{REVISIONMONTH}}-{{REVISIONYEAR}}

== Lista ==
{| class=" . '"' . "wikitable sortable" . '"' . "
|+
!Arquivo
!Duração (segundos)
|-
|{{nenhum}}
|{{nenhum}}
|}

[[Categoria:!Relatórios de manutenção para arquivos de uso restrito]]";
  }

  return array($text, $total);

}

// Obtém conteúdo atual das páginas
$content = $robot->api->getMultipleContent($pages);

// Processa relatório por relatório
$report = firstReport();

if($report[0]!==$content[$pages[0]]){
  echo $robot->log->log("Editando relatório 1...\r\n");
  $robot->edit($pages[0], $report[0], "[[WP:Bot|bot]]: atualizando lista (" . $report[1] . " entradas)", 0, 1);
}else{
  echo $robot->log->log("Relatório 1 já está atualizado...\r\n");
}

unset($content[$pages[0]]);

$report = secondReport();

if($report[0]!==$content[$pages[1]]){
  echo $robot->log->log("Editando relatório 2...\r\n");
  $robot->edit($pages[1], $report[0], "[[WP:Bot|bot]]: atualizando lista (" . $report[1] . " entradas)", 0, 1);
}else{
  echo $robot->log->log("Relatório 2 já está atualizado...\r\n");
}

unset($content[$pages[1]]);

$report = thirdReport();

if($report[0]!==$content[$pages[2]]){
  echo $robot->log->log("Editando relatório 3...\r\n");
  $robot->edit($pages[2], $report[0], "[[WP:Bot|bot]]: atualizando lista (" . $report[1] . " entradas)", 0, 1);
}else{
  echo $robot->log->log("Relatório 3 já está atualizado...\r\n");
}

unset($content[$pages[2]]);

// Fechar log
$robot->bye($robot->script . " concluído!\r\n");

?>
