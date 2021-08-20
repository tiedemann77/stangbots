<?php

/*
    ESTE SCRIPT APLICA SUBST: NOS ARQUIVOS
*/

// Requer variáveis básicas
require_once("includes/globals.php");

// Requer funções básicas
require_once("../../common.php");

// Começa o log
echo logging($logdate . "
Iniciando script 3...\r\n");

// Verifica se o bot está ligado
checkPower();

// FIM DO BÁSICO
//--------------------------------------------------------------------
// INICIANDO SCRIPT EM SI

// Obtém a data atual
$month = date("m");
$year = date("Y");

// Configura para dois meses anteriores
if($month=="01"){
  $archiveMonth = 11;
  $archiveYear = $year-1;
}elseif($month=="02"){
  $archiveMonth = 12;
  $archiveYear = $year-1;
}else{
  $archiveMonth = $month-2;
  $archiveYear = $year;
}

// Ajustando formato com "0" para menores de 10
if($archiveMonth<10){
  $archiveMonth = "0" . $archiveMonth;
}

// Obtém página do arquivo
$page = $archivePage . $archiveYear . "/" . $archiveMonth;

// Obtendo conteúdo da página de recentes
$archiveContent = getContent($page, 1);

// Substituições...
$archiveContent = str_ireplace("{{Wikipédia:Pedidos a verificadores/ListarArquivo|","{{subst:Wikipédia:Pedidos a verificadores/ListarArquivo2|",$archiveContent);
$archiveContent = str_ireplace("|confirmado}}","|{{subst:confirmado}}}}",$archiveContent);
$archiveContent = str_ireplace("|sem relação}}","|{{subst:sem relação}}}}",$archiveContent);
$archiveContent = str_ireplace("|rejeitado}}","|{{subst:rejeitado}}}}",$archiveContent);
$archiveContent = str_ireplace("|desnecessário}}","|{{subst:desnecessário}}}}",$archiveContent);
$archiveContent = str_ireplace("|possível}}","|{{subst:possível}}}}",$archiveContent);
$archiveContent = str_ireplace("|dormente}}","|{{subst:dormente}}}}",$archiveContent);
$archiveContent = str_ireplace("|provável}}","|{{subst:provável}}}}",$archiveContent);
$archiveContent = str_ireplace("|improvável}}","|{{subst:improvável}}}}",$archiveContent);
$archiveContent = str_ireplace("|inconclusivo}}","|{{subst:inconclusivo}}}}",$archiveContent);
$archiveContent = str_ireplace("|possivável}}","|{{subst:possivável}}}}",$archiveContent);
$archiveContent = str_ireplace("|negado}}","|{{subst:negado}}}}",$archiveContent);
$archiveContent = str_ireplace("|pato}}","|{{subst:pato}}}}",$archiveContent);


// Login para edição

// Login step 1
$login_Token = getLoginToken();

// Login step 2
loginRequest( $login_Token );

// Obtém edit token
$csrf_Token = getCSRFToken();

// Edita a página
editRequest($csrf_Token, $page, $archiveContent, "[[WP:Bot|bot]]: substituindo predefinições");

// PARA TESTE
// Registra em log ao invés de editar
//logging("Content of archiveContent string:\r\n" . $archiveContent . "\r\n");

// Fecha o log
echo logging("Script 3 concluído!\r\n");

?>
