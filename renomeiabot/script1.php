<?php

// Requer variáveis básicas
require_once("includes/globals.php");

// Requer funções básicas
require_once(__DIR__ . "/../common.php");

// Começa o log
echo logging($logdate . "
Iniciando script 1...\r\n");

// Verifica se o bot está ligado
checkPower();

// FIM DO BÁSICO
//--------------------------------------------------------------------
// INICIANDO SCRIPT EM SI

// Obtém o conteúdo total da página
$content = getContent($BasePage, 1);

// Obtém o conteúdo da página salvo
$savedcontent = file_get_contents($contentfile);

// Se iguais, não houveram edições, parar script
if($content==$savedcontent){
  $logmsg = logging("A página não foi editada. Fechando...\r\n");
  exit($logmsg);
}

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
echo logging(" ... são " . $requestNumber . " pedidos ainda em aberto, verificando cada um deles. Apenas fatos relevantes serão logados.\r\n");

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
    continue;
  }

  $actualname = str_replace("=== ","",$explode[0]);
  $actualname = str_replace(' <span style="width:200px;">',"",$actualname);
  $newname = str_replace("</span> ","",$explode[1]);
  $newname = str_replace(" ===","",$newname);

  // Log
  echo logging("Fazendo checagens da seção número " . $control2 . ";\r\n");

  // Checa se o novo nome está em uso
  $exist = accountExist($newname);

  // Verifica se o usuário teve bloqueios no passado
  $blocks = hasBlocks($actualname);

  // Verifica nomes similares
  $antispoof = antispoof($newname);

  // Fecha pedido, comenta pedido
  // No começo, tudo é igual
  $newrequest[$control] = $oldrequest[$control];

  // Se o novo nome já existe, fecha direto
  if($exist==1){

    $header = $out[0] . "
{{Respondido2|negado|texto=";

    $newrequest[$control] = str_replace($out[0],$header,$newrequest[$control]);
    $newrequest[$control] = $newrequest[$control] . "
::{{subst:negado|(automático) Negado}} {{ping|" . $actualname . "}} Olá! O nome de usuário que você escolheu (" . $newname . ") já está em uso. Se " . $newname . " não possui ediçoes ([[Especial:Administração de contas globais/" . $newname . "|verifique aqui]]), ele pode ser elegível para [[m:Special:MyLanguage/USURP|usurpação]]. No entanto, na maioria dos casos o mais recomendado é escolher outro nome que não conste [[Especial:Administração de contas globais|nesta lista]] e abrir um novo pedido. Obrigado! ~~~~}}";

    // Log
    echo logging( $newname . " já está em uso;\r\n");

  }else{
    // A primeira condição fecha qualquer pedido, então qualquer checagem adicional deve ser feita dentro do else


    // Se já há notas do bot no pedido, ignorar para evitar comentários repetidos
    $temp3 = preg_match("/(RenomeiaBot)/", $newrequest[$control]);

    if($temp3==0){

      // Se o usuário tem bloqueios, adiciona uma nota no pedido
      if($blocks==1){

        // Pequeno ajuste para espaço na URL
        $nameURL = str_replace(" ","+",$actualname);
        $newrequest[$control] = $newrequest[$control] . "
::'''Nota automática:''' a conta " . $actualname . " já foi [https://pt.wikipedia.org/wiki/Especial:Registo?type=block&page=User:" . $nameURL . " bloqueada] no passado. ~~~~";

        // Log
        echo logging( $actualname . " já foi bloqueado no passado;\r\n");

      }

      // Se o antispoof disparar
      if($antispoof!="0"){

        // Pequeno ajuste para espaço na URL
        $nameURL = str_replace(" ","+",$newname);

        $newrequest[$control] = $newrequest[$control] . "
::'''Nota automática:''' o nome de usuário " . $newname . " é muito similar a [[Especial:Administração de contas globais/" . $antispoof . "|" . $antispoof . "]] ou a outros que [https://meta.wikimedia.org/w/api.php?action=antispoof&username=" . $nameURL . "&format=json já estão em uso]. ~~~~";

        // Log
        echo logging( $newname . " é muito similar a " . $antispoof . " ou outros;\r\n");

      }

    }

  }

  // Vida que segue, reinicia o loop para o próximo pedido
  $control++;

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

// Se os dois foram iguais, nenhuma edição precisa ser feita, atualizar o arquivo e parar
if($newcontent==$content){

  // Salva o novo conteúdo, para evitar múltiplas consultas para conteúdo não alterado
  file_put_contents($contentfile, $content);

  // Para o script
  $logmsg = logging("Nenhuma edição precisa ser feita. Fechando...\r\n");
  exit($logmsg);
}


// Login step 1
//$login_Token = getLoginToken();

// Login step 2
//loginRequest( $login_Token );

// Obtendo edit token
//$csrf_Token = getCSRFToken();

// Editando a página de pedidos
//editRequest($csrf_Token, $BasePage, $newcontent, "[[WP:Bot|bot]]: processando pedidos");

// PARA TESTE
// ADICIONAR O CONTEÚDO DA EDIÇÃO EM LOG
logging("Conteúdo da variável content:\r\n" . $newcontent . "\r\n");

// Depois da edição, obtém o conteúdo de novo por causa da assinatura
$content = getContent($BasePage, 1);

// Salva o novo conteúdo, para evitar múltiplas consultas para conteúdo não alterado
file_put_contents($contentfile, $content);

// Fechar log
echo logging("Script 1 concluído!\r\n");


?>
