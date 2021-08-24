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

// Total de páginas
$total = count($pages);

$texto = "<noinclude>";

// Controle para o próximo loop
$control = 0;
$control2 = 1;

// Loop para listar as páginas
while ($control<$total) {

  $texto = $texto . "Parâmetro " . $control2 . " = " . $pages[$control] . "

";

  $control++;
  $control2++;

}

$texto = $texto . "</noinclude><includeonly>{{#switch: {{{1}}}
";

// Controle para o próximo loop
$control = 0;
$control2 = 1;

// Loop para processar cada página
while ($control<$total) {

  echo "Checando " . $pages[$control] . ": ";

  // Obtém o conteúdo de cada página
  $content = getContent($pages[$control], 1);

  // Obtém a lista de seções (pedidos)
  $sectionList = getSectionList($pages[$control]);

  // Fix temporário
  if($pages[$control]=="Wikipédia:Renomeação de conta"){
    $deleted = array_shift($sectionList);
  }

  // Contando o número de seções
  $sectionNumber = count($sectionList);

  // Conta o número de seções respondidas
  $temp = preg_match_all("/(\{\{(R|r)espondido(2){0,1}\|.{1,}\|)|(A discussão a seguir está marcada como respondida)/", $content, $out2);
  $closed = count($out2[0]);

  $temp2 = preg_match_all("/<!--\n{{Respondido2\|feito\|texto=/", $content, $out3);
  $fix = count($out3[0]);

  $closed = $closed-($fix*3);

  $temp3 = preg_match_all("/<!--{{Respondido\|feito\/negado\|texto= -->/", $content, $out4);
  $fix2 = count($out4[0]);

  $closed = $closed-$fix2;

  $open = $sectionNumber-$closed;

  echo "total " . $sectionNumber . "; fechadas " . $closed . "; abertas " . $open . "\r\n";

  $texto = $texto . " | " . $control2 . " = " . $open . "
";

  $control++;
  $control2++;
}

$texto = $texto .  " | 0
}}</includeonly>";

// Obtém o conteúdo do feed
$content = getContent("User:Stangbot/feed", 1);

if($content==$texto){
  // Nada a editar, para script
  $logmsg = logging("Nenhuma edição precisa ser feita. Fechando...\r\n");
  exit($logmsg);
}

// Login step 1
$login_Token = getLoginToken();

// Login step 2
loginRequest( $login_Token );

// Obtendo edit token
$csrf_Token = getCSRFToken();

// Editando a página de pedidos
editRequest($csrf_Token, "User:Stangbot/feed", $texto, "atualizando");

// PARA TESTE
// ADICIONAR O CONTEÚDO DA EDIÇÃO EM LOG
//logging("Conteúdo da variável texto:\r\n" . $texto. "\r\n");

// Fechar log
echo logging("Script 1 concluído!\r\n");

?>
