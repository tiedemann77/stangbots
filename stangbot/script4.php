<?php

// Requer variáveis básicas
require_once("includes/globals.php");

// Requer funções básicas
require_once(__DIR__ . "/../common.php");

// Começa o log
echo logging($logdate . "
Iniciando script 4...\r\n");

// API URL
$endPoint = "https://pt.wikipedia.org/w/api.php";

// Verifica se o bot está ligado
checkPower();

// FIM DO BÁSICO
//--------------------------------------------------------------------
// INICIANDO SCRIPT EM SI

// Páginas
$page1 = "User:Stangbot/Relatório URC 1";
$page2 = "User:Stangbot/Relatório URC 2";
$page3 = "User:Stangbot/Relatório URC 3";

// Relatório 1
function firstReport(){

  // Log
  echo logging("Gerando relatório 1...\r\n");

  // Consulta total de itens para percentagem
  $query = 'SELECT COUNT(*) FROM image WHERE img_media_type = "BITMAP" OR img_media_type = "DRAWING";';

  $result = replicaQuery("ptwiki", $query, 0, 0);

  $totalDB = $result[0];

  // Consulta para a lista
  $query = 'SELECT img_name, img_height FROM image WHERE img_height > 500 ORDER BY img_name ASC;';

  $result = replicaQuery("ptwiki", $query, 0, 0);

  $total = 0;

  // Somente se houver resultados, personaliza relatório
  if(isset($result[0])){

    // Cabeçalho
    $text = "Lista de imagens que não cumprem a [[WP:URC|política de uso restrito de conteúdo]] porque possuem mais de 500 pixels de altura. Atualizada periodicamente.

<div style=" . '"' . "float: right; width: 25%;" . '"' . ">
'''Porcentagem do total:'''
{{percentagem|-percentagem1-|FF7F50}}
</div>

'''TOTAL''': -total1-

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
|}";

    // Personaliza o cabeçalho com os totais e percentagem
    $percent = sprintf("%.2f", (($total*100)/$totalDB));
    $text = str_replace("-total1-", $total, $text);
    $text = str_replace("-percentagem1-", $percent, $text);

  }else{
    // Se não houver, texto padrão
    $text = "Lista de imagens que não cumprem a [[WP:URC|política de uso restrito de conteúdo]] porque possuem mais de 500 pixels de altura. Atualizada periodicamente.

<div style=" . '"' . "float: right; width: 25%;" . '"' . ">
'''Porcentagem do total:'''
{{percentagem|0|FF7F50}}
</div>

'''TOTAL''': 0

'''Última atualização''': {{REVISIONDAY2}}-{{REVISIONMONTH}}-{{REVISIONYEAR}}

== Lista ==
{| class=" . '"' . "wikitable sortable" . '"' . "
|+
!Arquivo
!Altura (em pixels)
|-
|{{nenhum}}
|{{nenhum}}
|}";
  }

  return array($text, $total);

}

// Relatório 2
function secondReport(){

  echo logging("Gerando relatório 2...\r\n");

  $query = 'SELECT COUNT(*) FROM image;';

  $result = replicaQuery("ptwiki", $query, 0, 0);

  $totalDB = $result[0];

  $query = 'SELECT img_name, img_height, oi_name, oi_archive_name FROM image, oldimage WHERE img_name = oi_name ORDER BY img_name ASC;';

  $result = replicaQuery("ptwiki", $query, 0, 0);

  $total = 0;

  if(isset($result[0])){

    $text = "Lista de arquivos carregados por meio da [[WP:URC|política de uso restrito de conteúdo]] que possuem versões antigas. De acordo com a política, versões antigas desses arquivos [[Wikipédia:Conteúdo_restrito#Versões_anteriores|devem ser eliminadas]]. Atualizada periodicamente.

<div style=" . '"' . "float: right; width: 25%;" . '"' . ">
'''Porcentagem do total:'''
{{percentagem|-percentagem1-|FF7F50}}
</div>

'''TOTAL''': -total1-

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
|}";

    $percent = sprintf("%.2f", (($total*100)/$totalDB));
    $text = str_replace("-total1-", $total, $text);
    $text = str_replace("-percentagem1-", $percent, $text);
  }else{
    // Sem resultados, texto padrão
    $text = "Lista de arquivos carregados por meio da [[WP:URC|política de uso restrito de conteúdo]] que possuem versões antigas. De acordo com a política, versões antigas desses arquivos [[Wikipédia:Conteúdo_restrito#Versões_anteriores|devem ser eliminadas]]. Atualizada periodicamente.

<div style=" . '"' . "float: right; width: 25%;" . '"' . ">
'''Porcentagem do total:'''
{{percentagem|0|FF7F50}}
</div>

'''TOTAL''': 0

'''Última atualização''': {{REVISIONDAY2}}-{{REVISIONMONTH}}-{{REVISIONYEAR}}

== Lista ==
{| class=" . '"' . "wikitable sortable" . '"' . "
|+
!Arquivo
|-
|{{nenhum}}
|}";
  }

  return array($text, $total);

}

// Relatório 3
function thirdReport(){

  echo logging("Gerando relatório 3...\r\n");

  $query = 'SELECT COUNT(*) FROM image WHERE img_media_type = "AUDIO";';

  $result = replicaQuery("ptwiki", $query, 0, 0);

  $totalDB = $result[0];

  $query = 'SELECT img_name, img_metadata FROM image WHERE img_media_type = "AUDIO" ORDER BY img_name ASC;';

  $result = replicaQuery("ptwiki", $query, 0, 0);

  $total = 0;

  if(isset($result[0])){

    $text = "Lista de arquivos de áudio em desacordo com a [[WP:URC|política de uso restrito de conteúdo]] porque possuem mais de 30 segundos de duração (excessos menores que 1 segundo são ignorados). Atualizada periodicamente.

<div style=" . '"' . "float: right; width: 25%;" . '"' . ">
'''Porcentagem do total:'''
{{percentagem|-percentagem1-|FF7F50}}
</div>

'''TOTAL''': -total1-

'''Última atualização''': {{REVISIONDAY2}}-{{REVISIONMONTH}}-{{REVISIONYEAR}}

== Lista ==
{| class=" . '"' . "wikitable sortable" . '"' . "
|+
!Arquivo
!Duração (segundos)";

    // Insere cada linha se for maior que 31
    foreach ($result as $key => $value) {
      $name = $result[$key][0];
      $metadata = unserialize($result[$key][1]);
      $lenght = $metadata['length'];
      if($lenght>31){
        $text .= "
|-
|[[:Ficheiro:" . $name . "|" . $name . "]]
|" . $lenght;
        $total++;
      }
    }

    $text .= "
|}";

    $percent = sprintf("%.2f", (($total*100)/$totalDB));
    $text = str_replace("-total1-", $total, $text);
    $text = str_replace("-percentagem1-", $percent, $text);
  }

  if(!isset($result[0])||$total==0){
    $text = "Lista de arquivos de áudio em desacordo com a [[WP:URC|política de uso restrito de conteúdo]] porque possuem mais de 30 segundos de duração (excessos menores que 1 segundo são ignorados). Atualizada periodicamente.

<div style=" . '"' . "float: right; width: 25%;" . '"' . ">
'''Porcentagem do total:'''
{{percentagem|0|FF7F50}}
</div>

'''TOTAL''': 0

'''Última atualização''': {{REVISIONDAY2}}-{{REVISIONMONTH}}-{{REVISIONYEAR}}

== Lista ==
{| class=" . '"' . "wikitable sortable" . '"' . "
|+
!Arquivo
!Duração (segundos)
|-
|{{nenhum}}
|{{nenhum}}
|}";
  }

  return array($text, $total);

}

// Executa as funções
$report1 = firstReport();
$report2 = secondReport();
$report3 = thirdReport();

// Checa se é necessário fazer edições
$content1 = getContent($page1, 0);
$content2 = getContent($page2, 0);
$content3 = getContent($page3, 0);

if($report1[0]!==$content1||$report2[0]!==$content2||$report3[0]!==$content3){

  // Login step 1
  $login_Token = getLoginToken();

  // Login step 2
  loginRequest( $login_Token );

  // Obtendo edit token
  $csrf_Token = getCSRFToken();

  if($report1[0]!==$content1){
    echo logging("Editando relatório 1...\r\n");
    editRequest($csrf_Token, $page1, $report1[0], "[[WP:Bot|bot]]: atualizando relatório (" . $report1[1] . " entradas)", 0, 0);
  }

  if($report2[0]!==$content2){
    echo logging("Editando relatório 2...\r\n");
    editRequest($csrf_Token, $page2, $report2[0], "[[WP:Bot|bot]]: atualizando relatório (" . $report2[1] . " entradas)", 0, 0);
  }

  if($report3[0]!==$content3){
    echo logging("Editando relatório 3...\r\n");
    editRequest($csrf_Token, $page3, $report3[0], "[[WP:Bot|bot]]: atualizando relatório (" . $report3[1] . " entradas)", 0, 0);
  }

  // Logout
  logoutRequest( $csrf_Token );

}else{
  exit(logging("Todos os relatórios já estão atualizados. Fechando...\r\n"));
}

// PARA TESTE
// ADICIONAR O CONTEÚDO DA EDIÇÃO EM LOG
//logging("Conteúdo da variável text1:\r\n" . $text1. "\r\n");
//logging("Conteúdo da variável text1:\r\n" . $text2. "\r\n");

// Fechar log
echo logging("Script 4 concluído!\r\n");

?>
