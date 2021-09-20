<?php

// Requer variáveis básicas
require_once("includes/globals.php");

// Requer funções básicas
require_once(__DIR__ . "/../common.php");

// Começa o log
echo logging($logdate . "
Iniciando script 3...\r\n");

// Projeto
$endPoint = "https://www.wikidata.org/w/api.php";

// Verifica se o bot está ligado
checkPower();

// FIM DO BÁSICO
//--------------------------------------------------------------------
// INICIANDO SCRIPT EM SI

// Variáveis
$page = "Wikidata:Sandbox";

$template = "{{sandbox heading}}
<!--Test your edits below this line-->";

$time = 3;

// Obtém a data da última edição
$params = [
  "action" => "query",
  "prop" => "revisions",
  "titles" => $page,
  "rvprop" => "timestamp",
  "rvlimit" => "1",
  "rvslots" => "main",
  "format" => "json"
];

$result = APIrequest($endPoint, $params);

foreach ($result['query']['pages'] as $key => $value) {
  $lastedit = $result['query']['pages'][$key]['revisions']['0']['timestamp'];
}

// Verificando se já passou 1 hora desde a última edição
$timenow = new DateTime($logdate);
$lastedit = new DateTime($lastedit);

// Verificando a diferença
$timediff = $lastedit->diff($timenow);

$hours = $timediff->h;

if($hours<$time){
  exit(logging("Última edição a menos de " . $time . " hora(s). Fechando...\r\n"));
}

$content = getContent($page, 1);

if($content==$template){
  exit(logging("A página já está vazia. Fechando...\r\n"));
}

// Login step 1
$login_Token = getLoginToken();

// Login step 2
loginRequest( $login_Token );

// Obtendo edit token
$csrf_Token = getCSRFToken();

// Editando a página de pedidos
editRequest($csrf_Token, $page, $template, "[[WD:Bot|bot]]: cleaning sandbox", 1, 0);

// Logout
logoutRequest( $csrf_Token );

// PARA TESTE
// ADICIONAR O CONTEÚDO DA EDIÇÃO EM LOG
//logging("Conteúdo da variável text:\r\n" . $text. "\r\n");

// Fechar log
echo logging("Script 3 concluído!\r\n");

?>
