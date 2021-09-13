<?php

// Requer variáveis básicas
require_once("includes/globals.php");

// Requer funções básicas
require_once(__DIR__ . "/../common.php");

// Começa o log
echo logging($logdate . "
Iniciando script 2...\r\n");

// Verifica se o bot está ligado
checkPower();

// FIM DO BÁSICO
//--------------------------------------------------------------------
// INICIANDO SCRIPT EM SI

// Obtém o conteúdo total da página
$content = getContent($BasePage, 1);

// Obtém a lista de seções (pedidos)
$sectionList = getSectionList($BasePage);

// Exclui a primeira seção, irrelevante
$deleted = array_shift($sectionList);

// Contando o número de seções
$sectionNumber = count($sectionList);

// Log
echo logging("São " . $sectionNumber . " seções no total, requisitando cada uma delas, pode demorar...\r\n");

// Controles para o próximo loop
$control = 0;
$first = 2; //primeira seção sempre é a 2

// Adiciona o conteúdo de cada seção numa array com todos os pedidos
while ($control < $sectionNumber) {

  $request[$control] = getSectionContent($BasePage, $first);

  // Segue para o próximo
  $control++;
  $first++;

  // Log
  echo logging("Requisitando seção número " . $control . ";\r\n");

}

// Log
echo logging("Removendo pedidos já fechados...");

// Controles para o próximo loop
$control = 0;
$control2 = 0;

// Remove os pedidos já respondidos
while ($control < $sectionNumber) {

  $temp2 = preg_match("/(\{\{(R|r)espondido(2){0,1}\|.{1,}\|)|(A discussão a seguir está marcada como respondida)/", $request[$control]);

  if($temp2==0){
    $temp[$control2] = $request[$control];
    $control2++;
  }
  $control++;
}

// Temos agora apenas pedidos aberto e o número deles
$oldrequest = $temp;
$requestNumber = count($oldrequest);

// Log
echo logging(" ... são " . $requestNumber . " pedidos ainda em aberto, verificando cada um deles.\r\n");

// Controles para o próximo loop
$control = 0;
$control2 = 1; // Somente para log

// Loop aonde a mágica acontece
while ($control < $requestNumber) {

  // Primeiro, os nomes de usuário
  preg_match("/=== .* ===/", $oldrequest[$control], $out);

  $explode = explode("→", $out[0]);

  // Verifica se o título da seção é válido
  $countexp = count($explode);

  // Se não for, simplesmente ignora e segue para o próximo loop
  if($countexp!="2"){
    $control++;
    $control2++;
    continue;
  }

  $actualname = str_replace("=== ","",$explode[0]);
  $actualname = str_replace(' <span style="width:200px;">',"",$actualname);
  $newname = str_replace("</span> ","",$explode[1]);
  $newname = str_replace(" ===","",$newname);

  // Log
  echo logging("Verificando seção número " . $control2 . "...\r\n");

  $endPoint = "https://meta.wikimedia.org/w/api.php";

  $params = [
		"action" => "query",
		"list" => "logevents",
		"letype" => "gblrename",
		"letitle" => "Special:CentralAuth/" . $newname,
		"format" => "json"
	];

  // Faz consulta a API
  $result = APIrequest($endPoint, $params);

  // Verificando se há registro de renonomeação
  if(isset($result['query']['logevents'][0])){

    $old = $result['query']['logevents'][0]['params']['olduser'];
    $new = $result['query']['logevents'][0]['params']['newuser'];

    // Se o registro equivale ao pedido
    if($old===$actualname&&$new===$newname){
      $timestamp = substr($result['query']['logevents'][0]['timestamp'], 0, 10);

      $timestamp = new DateTime($timestamp);

      // Obtém a data atual
      $day = date("d");
      $month = date("m");
      $year = date("Y");

      $dateNow = $year . "-" . $month . "-" . $day;

      // Convertendo em date object
      $dateNow = new DateTime($dateNow);

      // Verificando a diferença
      $dateInterval = $timestamp->diff($dateNow);

      $dateInterval = $dateInterval->days;

      // Se foi nos últimos dois dias
      if($dateInterval<3){

        $renamer = $result['query']['logevents'][0]['user'];

        // Fecha pedido
        $newrequest[$control] = $oldrequest[$control];
        $header = $out[0] . "
{{Respondido2|feito|texto=";

        $newrequest[$control] = str_replace($out[0],$header,$newrequest[$control]);
        $newrequest[$control] = $newrequest[$control] . "
::{{subst:feito|Pedido atendido}} a conta foi renomeada por " . $renamer . ". ~~~~}}";

        // Log
        echo logging( $actualname . " foi renomeada para " . $newname . " por " . $renamer . ";\r\n");

      }

    }

  }
  // Vida que segue, reinicia o loop para o próximo pedido
  $control++;
  $control2++;

}

// Controle para o próximo loop
$control = 0;

// Para separar o novo do antigo
$newcontent = $content;

// Log
echo logging("Iniciando edições...\r\n");

while ($control < $requestNumber) {

  // Faz a substituição, com proteção contra seções ignoradas por estarem mal-formatadas
  if(isset($newrequest[$control])){
    $newcontent = str_replace($oldrequest[$control], $newrequest[$control], $newcontent);
  }

  $control++;

}

// Se os dois foram iguais, nenhuma edição precisa ser feita, parar
if($newcontent==$content){
  exit(logging("Nenhuma edição precisa ser feita. Fechando...\r\n"));
}

// Login step 1
$login_Token = getLoginToken();

// Login step 2
loginRequest( $login_Token );

// Obtendo edit token
$csrf_Token = getCSRFToken();

// Editando a página de pedidos
editRequest($csrf_Token, $BasePage, $newcontent, "[[WP:Bot|bot]]: fechando pedidos atendidos", 1, 0);

// Logout
logoutRequest( $csrf_Token );

// PARA TESTE
// ADICIONAR O CONTEÚDO DA EDIÇÃO EM LOG
//logging("Conteúdo da variável content:\r\n" . $newcontent . "\r\n");

// Depois da edição, atualiza o cache
$content = getContent($BasePage, 1);

// Salva o novo conteúdo, para evitar múltiplas consultas para conteúdo não alterado
file_put_contents($contentfile, $content);

// Fechar log
echo logging("Script 2 concluído!\r\n");

?>
