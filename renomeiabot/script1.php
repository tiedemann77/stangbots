<?php

// Requer funções para esse script em específico
require_once(__DIR__ . "/includes/functions_1.php");

// Requer configurações
require_once(__DIR__ . "/settings.php");

// Requer funções básicas
require_once(__DIR__ . "/../common.php");

// Settings
$settings = [
  'credentials' => $uspw,
  'username' => "RenomeiaBot",
  'power' => "User:RenomeiaBot/Power",
  'script' => "Script 1",
  'url' => "https://pt.wikipedia.org/w/api.php",
  'maxlag' => 4,
  'file' => __DIR__ .  "/../log.log",
  'stats' => array(),
  'replicasDB' => "metawiki",
  'personalDB' => "s54852__stangbots"
];

$robot = new bot();

echo $robot->log->log($robot->username . " - Iniciando " . $robot->script . "\r\n");

// Página base
$BasePage = "Wikipédia:Renomeação de conta";

// Arquivo de cache
$cachefile = __DIR__ .  "/temp/cache.txt";

// Carrega expressões
$expressions = json_decode($robot->api->getContent("User:RenomeiaBot/expressões.json",1), true);

// Obtém o conteúdo total da página
$content = $robot->api->getContent($BasePage, 1);

// Verifica se há necessidade do bot rodar
run($content);

// Obtém a lista de seções (pedidos)
$sectionList = $robot->api->getSectionList($BasePage);

// Exclui a primeira seção, irrelevante
$deleted = array_shift($sectionList);

// Contando o número de seções
$sectionNumber = count($sectionList);

// Se a página está vazia, para
if($sectionNumber == 0){
  $robot->bye("A página está vazia. Fechando...\r\n");
}

// Log
echo $robot->log->log("São " . $sectionNumber . " seções no total, requisitando cada uma delas, pode demorar...\r\n");

// Controles para o próximo loop
$control = 0;
$control2 = 2; //primeira seção nesse caso sempre é a 2

// Adiciona o conteúdo de cada seção numa array com todos os pedidos
while ($control < $sectionNumber) {

  $requests[$control] = $robot->api->getSectionContent($BasePage, $control2);

  // Segue para o próximo
  $control++;
  $control2++;

  // Log
  echo $robot->log->log("Requisitando seção número " . $control . ";\r\n");

}

// Log
echo $robot->log->log("Removendo pedidos já fechados...");

// Controles para o próximo loop
$control = 0;
$control2 = 0;

// Remove os pedidos já fechados
while ($control < $sectionNumber) {

  $temp2 = preg_match($closedRegex, $requests[$control]);

  if($temp2==0){
    $temp[$control2] = $requests[$control];
    $control2++;
  }

  $control++;

}

// Temos agora apenas pedidos em aberto e o número deles
$openrequests = $temp;
$requestNumber = count($openrequests);

// Log
echo $robot->log->log(" ... são " . $requestNumber . " pedidos ainda em aberto, verificando e fechando os que já foram atendidos (etapa 1).\r\n");

// Controles para o próximo loop
$control = 0;
$control2 = 1; // Somente para log

// Número de pedidos fechados pelo script
$numberClosed = 0;

// ETAPA 1: Loop para fechar pedidos já atendidos
while ($control < $requestNumber) {

  // Primeiro, os nomes de usuário
  preg_match("/=== .* ===/", $openrequests[$control], $out);

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
  echo $robot->log->log("Verificando pedido número " . $control2 . "...\r\n");

  // Troca temporária
  $robot->api->url = "https://meta.wikimedia.org/w/api.php";

  $params = [
		"action" => "query",
		"list" => "logevents",
		"letype" => "gblrename",
		"letitle" => "Special:CentralAuth/" . $newname,
		"format" => "json"
	];

  // Faz consulta a API
  $result = $robot->api->request($params);
  // Retorna
  $robot->api->url = "https://pt.wikipedia.org/w/api.php";

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
        $newrequest[$control] = $openrequests[$control];
        $header = $out[0] . "
{{Respondido2|feito|texto=";

        $newrequest[$control] = str_replace($out[0],$header,$newrequest[$control]);
        $newrequest[$control] = $newrequest[$control] . "
::{{subst:feito|Pedido atendido:}} a conta foi renomeada por " . $renamer . ". ~~~~}}";

        // Log
        echo $robot->log->log( "ATENDIDO: " . $actualname . " foi renomeada para " . $newname . " por " . $renamer . ";\r\n");

        $editedrequests[$control] = $newrequest[$control];

        $numberClosed++;

      }

    }

  }

  if(!isset($editedrequests[$control])){
    $editedrequests[$control] = $openrequests[$control];
  }

  // Vida que segue, reinicia o loop para o próximo pedido
  $control++;
  $control2++;

}

$remainOpen = $requestNumber-$numberClosed;

// Log
echo $robot->log->log($numberClosed . " pedidos foram atendidos, sobraram " . $remainOpen . " em aberto. Fazendo checagens adicionais nesses pedidos (etapa 2)...\r\n");

// Controles para o próximo loop
$control = 0;
$control2 = 1; //Somente para logs

// ETAPA 2: Processando pedidos que continuam em aberto
while ($control < $requestNumber) {

  $temp2 = preg_match($closedRegex, $editedrequests[$control]);

  if($temp2==1){
    echo $robot->log->log("Pedido número " . $control2 . " já foi atendido;\r\n");
  }else{

    // Primeiro, os nomes de usuário
    preg_match("/=== .* ===/", $editedrequests[$control], $out);

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
    echo $robot->log->log("Fazendo checagens no pedido número " . $control2 . ";\r\n");

    // Checa se o novo nome está em uso e não é um pedido de abandono, condição que fecha
    $exist = $robot->api->accountExist($newname);
    $vanish = preg_match($expressions['abandono']['regex'],$editedrequests[$control]);

    // Fecha pedido, comenta pedido
    // No começo, tudo é igual
    $newrequest[$control] = $editedrequests[$control];

    // Se o novo nome de usuário e o antigo são iguais
    if($newname==$actualname){

      $header = $out[0] . "
{{Respondido2|negado|texto=";

      $newrequest[$control] = str_replace($out[0],$header,$newrequest[$control]);
      $newrequest[$control] = $newrequest[$control] . "
::{{subst:negado|Negado automaticamente}} {{ping|" . $actualname . "}} Olá! O nome de usuário que você escolheu (" . $newname . ") é igual ao seu nome de usuário atual, por isso não é possível realizar a renomeação. Por favor, abra outro pedido escolhendo um novo nome de usuário que seja diferente do seu atual. Obrigado! ~~~~}}";

      // Log
      echo $robot->log->log("Novo nome (" . $newname . ") é igual ao antigo;\r\n");

    }elseif($exist==1&&$vanish==0){
      // Se o novo nome já existe e não é abandono, fecha direto
      $header = $out[0] . "
{{Respondido2|negado|texto=";

      $newrequest[$control] = str_replace($out[0],$header,$newrequest[$control]);
      $newrequest[$control] = $newrequest[$control] . "
::{{subst:negado|Negado automaticamente}} {{ping|" . $actualname . "}} Olá! O nome de usuário que você escolheu (" . $newname . ") já está em uso. Se " . $newname . " não possui edições ([[Especial:Administração de contas globais/" . $newname . "|verifique aqui]]), ele pode ser elegível para [[m:Special:MyLanguage/USURP|usurpação]]. No entanto, na maioria dos casos o mais recomendado é escolher outro nome que não conste [[Especial:Administração de contas globais|nesta lista]] e abrir um novo pedido. Obrigado! ~~~~}}";

      // Log
      echo $robot->log->log( $newname . " já está em uso;\r\n");

    }else{
      // Se já há notas do bot no pedido, ignorar para evitar comentários repetidos
      $temp2 = preg_match("/(RenomeiaBot)/", $newrequest[$control]);

      if($temp2==0){

        // Se o pedido não foi fechado ou comentado ainda, faz as outras checagens
        // Verifica se o usuário teve bloqueios no passado
        $blocks = $robot->api->hasBlocks($actualname);

        // Verifica nomes similares, caso o nome escolhido não exista
        if($exist==0){
          $antispoof = $robot->api->antispoof($newname,$actualname);
        }else{
          $antispoof = 0;
        }

        // Verifica renomeações anteriores
        $renames = hasRenames($actualname);

        // Se é um pedido de abandono com nome já em uso
        if($exist==1&&$vanish==1){

          $newrequest[$control] = $newrequest[$control] . "
::'''Nota automática:''' o novo nome de usuário escolhido [[Especial:Administração de contas globais/" . $newname . "|já está em uso]]. Porém, como esse parece ser um pedido de [[Wikipédia:Direito a desaparecer|esquecimento]], a solicitação não foi negada automaticamente. Renomeador: lembre-se de configurar outro novo nome de usuário quando processar esse pedido. ~~~~";

          // Log
          echo $robot->log->log($newname . " já está em uso, porém é um pedido de esquecimento. Adicionando nota;\r\n");

        }

        // Se o usuário tem bloqueios
        if($blocks==1){

          // Pequeno ajuste para espaço na URL
          $nameURL = str_replace(" ","+",$actualname);
          $newrequest[$control] = $newrequest[$control] . "
::'''Nota automática:''' a conta " . $actualname . " já foi [https://pt.wikipedia.org/wiki/Especial:Registo?type=block&page=User:" . $nameURL . " bloqueada] no passado. ~~~~";

          // Log
          echo $robot->log->log( $actualname . " já foi bloqueado no passado;\r\n");

        }

        // Se já teve renomeações no passado
        if($renames!="0"){

          $newrequest[$control] = $newrequest[$control] . "
::'''Nota automática:''' a conta " . $actualname . " já foi renomeada no passado. A última renomeação ocorreu em " . $renames . ". ~~~~";

          // Log
          echo $robot->log->log( $actualname . " já foi renomeado no passado;\r\n");

        }

        // Se o antispoof disparar
        if($antispoof!="0"){

          // Pequeno ajuste para espaço na URL
          $nameURL = str_replace(" ","+",$newname);

          $newrequest[$control] = $newrequest[$control] . "
::'''Nota automática:''' o nome de usuário escolhido (" . $newname . ") é muito similar a [[Especial:Administração de contas globais/" . $antispoof . "|" . $antispoof . "]] ou a outros que [https://meta.wikimedia.org/w/api.php?action=antispoof&username=" . $nameURL . "&format=json já estão em uso]. ~~~~";

          // Log
          echo $robot->log->log( $newname . " é muito similar a " . $antispoof . " ou outros;\r\n");

        }

      }

    }

    $editedrequests[$control] = $newrequest[$control];

  }

  $control++;
  $control2++;

}

// Controle para o próximo loop
$control = 0;

// Para separar o novo do antigo
$newcontent = $content;

// Log
echo $robot->log->log("Iniciando edições...\r\n");

while ($control < $requestNumber) {

  // Faz a substituição, com proteção contra seções ignoradas por estarem mal-formatadas
  if(isset($editedrequests[$control])){
    $newcontent = str_replace($openrequests[$control], $editedrequests[$control], $newcontent);
  }

  $control++;

}

// Se os dois foram iguais, nenhuma edição precisa ser feita, parar
if($newcontent==$content){

  // Salva o novo conteúdo, para evitar múltiplas consultas para conteúdo não alterado
  file_put_contents($cachefile, $content);

  $robot->bye("Nenhuma edição precisa ser feita. Fechando...\r\n");

}

// Editando a página de pedidos
$robot->edit($BasePage, $newcontent, "[[WP:Bot|bot]]: processando pedidos", 0, 0);

// Depois da edição, obtém o conteúdo de novo por causa da assinatura
$content = $robot->api->getContent($BasePage, 1);

// Salva o novo conteúdo, para evitar múltiplas consultas para conteúdo não alterado
file_put_contents($cachefile, $content);

// PARA TESTE
// ADICIONAR O CONTEÚDO DA EDIÇÃO EM LOG
//$robot->log->log("Conteúdo da variável content:\r\n" . $newcontent . "\r\n");

// Fim
$robot->bye($robot->script . " concluído!\r\n");

?>
