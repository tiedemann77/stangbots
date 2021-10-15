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
  'script' => "Script 3",
  'url' => "https://pt.wikipedia.org/w/api.php",
  'maxlag' => 4,
  'file' => __DIR__ .  "/../log.log",
  'stats' => array(),
  'replicasDB' => "ptwiki",
  'personalDB' => "s54852__stangbots"
];

$robot = new bot();

echo $robot->log->log($robot->username . " - Iniciando " . $robot->script . "\r\n");

// Página base
$BasePage = "Wikipédia:Pedidos a verificadores";

// Prefixo dos arquivos
$archivePage = $BasePage . "/Arquivo/";

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
$archiveContent = $robot->api->getContent($page, 1);

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

// Edita a página
$robot->edit($page, $archiveContent, "[[WP:Bot|bot]]: substituindo predefinições", 1, 1);

// PARA TESTE
// Registra em log ao invés de editar
//$robot->log->log("Content of archiveContent string:\r\n" . $archiveContent . "\r\n");

// Fim
$robot->bye($robot->script . " concluído!\r\n");

?>
