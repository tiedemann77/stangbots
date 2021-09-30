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

// Relatório 1
function firstReport(){

  $query = 'SELECT img_name, img_height FROM image WHERE img_height > 500 ORDER BY img_name ASC LIMIT 50;';

  // Faz a consulta
  $result = replicaQuery("ptwiki", $query, 0, 0);

  // Somente se houver resultados, personaliza relatório
  if(isset($result[0])){

    // Cabeçalho
    $text = "Lista de imagens que não cumprem a [[WP:URC|política de uso restrito de conteúdo]] porque possuem mais de 500 pixels de altura. Atualizada periodicamente.

'''Última atualização''': ~~~~~

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
    }

    // Rodapé do relatório
    $text .= "
|}";
  }else{
    // Se não houver, texto padrão
    $text = "Lista de imagens que não cumprem a [[WP:URC|política de uso restrito de conteúdo]] porque possuem mais de 500 pixels de altura. Atualizada periodicamente.

'''Última atualização''': ~~~~~

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

  return $text;

}

// Relatório 2
function secondReport(){

  $query = 'SELECT img_name, img_height, oi_name, oi_archive_name FROM image, oldimage WHERE img_name = oi_name AND img_height < 501 AND img_height != 0 ORDER BY img_name ASC LIMIT 50;';

  // Faz a consulta
  $result = replicaQuery("ptwiki", $query, 0, 0);

  // Somente se houverem resultados
  if(isset($result[0])){

    // Cabeçalho do relatório
    $text = "Lista de imagens carregadas por meio da [[WP:URC|política de uso restrito de conteúdo]] que possuem versões antigas. Pela política, versões antigas dessas imagens [[Wikipédia:Conteúdo_restrito#Versões_anteriores|devem ser eliminadas]]. Atualizada periodicamente.

'''Última atualização''': ~~~~~

== Lista ==
{| class=" . '"' . "wikitable sortable" . '"' . "
|+
!Arquivo";

    // Insere cada linha
    foreach ($result as $key => $value) {
      $temp = preg_quote($result[$key][0]);
      if(!preg_match("/" . $temp . "/", $text)){
        $text .= "
|-
|[[:Ficheiro:" . $result[$key][0] . "|" . $result[$key][0] . "]]";
      }
    }

    // Rodapé do relatório
    $text .= "
|}";
  }else{
    // Sem resultados, texto padrão
    $text = "Lista de imagens carregadas por meio da [[WP:URC|política de uso restrito de conteúdo]] que possuem versões antigas. Pela política, versões antigas dessas imagens [[Wikipédia:Conteúdo_restrito#Versões_anteriores|devem ser eliminadas]]. Atualizada periodicamente.

'''Última atualização''': ~~~~~

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

  return $text;

}

$text1 = firstReport();
$text2 = secondReport();

// Login step 1
//$login_Token = getLoginToken();

// Login step 2
//loginRequest( $login_Token );

// Obtendo edit token
//$csrf_Token = getCSRFToken();

// Editando
//editRequest($csrf_Token, "User:Stangbot/Relatório URC 1", $text1, "[[WP:Bot|bot]]: atualizando relatório", 0, 0);
//editRequest($csrf_Token, "User:Stangbot/Relatório URC 2", $text2, "[[WP:Bot|bot]]: atualizando relatório", 0, 0);

// Logout
//logoutRequest( $csrf_Token );

// PARA TESTE
// ADICIONAR O CONTEÚDO DA EDIÇÃO EM LOG
logging("Conteúdo da variável text1:\r\n" . $text1. "\r\n");
logging("Conteúdo da variável text1:\r\n" . $text2. "\r\n");

// Fechar log
echo logging("Script 4 concluído!\r\n");

?>
