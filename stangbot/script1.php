<?php

// Requer variáveis básicas
require_once("includes/globals.php");

// Requer funções básicas
require_once(__DIR__ . "/../common.php");

// Começa o log
echo logging($logdate . "
Iniciando script 1...\r\n");

// API URL
$endPoint = "https://pt.wikipedia.org/w/api.php";

// Verifica se o bot está ligado
checkPower();

// FIM DO BÁSICO
//--------------------------------------------------------------------
// INICIANDO SCRIPT EM SI

// Lista de páginas
$pages = array("Wikipédia:Pedidos/Proteção","Wikipédia:Pedidos/Restauro","Wikipédia:Pedidos/Notificações de vandalismo","Wikipédia:Pedidos/Revisão de nomes de usuário","Wikipédia:Pedidos/Notificação de incidentes","Wikipédia:Renomeação de conta");

// Total de páginas
$total = count($pages);

// Começa a montar o feed
$text = "<noinclude>";

// Primeira parte do feed: lista de páginas
$control = 1;
foreach ($pages as $key => $value) {

  $text .= "Código " . $control . " = " . $pages[$key] . "

";
  $control++;
}

// Trecho intermediário do feed
$text .= "</noinclude><includeonly>{{#switch: {{{1}}}
";

// Segunda parte do feed: pedidos em aberto em cada página
$control = 1;
foreach ($pages as $key => $value) {

  // Conteúdo total da página
  $content = getContent($pages[$key], 1);

  // Lista de seções
  $sectionList = getSectionList($pages[$key]);

  // Precisa remover uma dessa página
  if($pages[$key]=="Wikipédia:Renomeação de conta"){
    $deleted = array_shift($sectionList);
  }

  // Número de seções
  $sectionNumber = count($sectionList);

  // Remove qualquer coisa comentada, geralmente templates de resposta
  $content = preg_replace($htmlcommentRegex, "", $content);

  // Conta o número de templates de resposta na página
  preg_match_all($closedRegex, $content, $out);
  $closed = count($out[0]);

  // Número de pedidos em aberto
  $open = $sectionNumber-$closed;

  // Se menor que 0, ocorreu algum erro então parar
  if($open<0){
    exit(logging("Número de pedidos em aberto para " . $pages[$key] . " menor que 0. Fechando...\r\n"));
  }

  echo logging("Checando " . $pages[$key] . ": total " . $sectionNumber . "; fechados " . $closed . "; abertos " . $open . ".\r\n");

  // Adiciona linha no feed
  $text .= " | " . $control . " = " . $open . "
";

  $control++;

}

// Rodapé
$text .= " | 0
}}</includeonly>";

// Verifica se precisa atualizar o feed
$content = getContent("User:Stangbot/feed", 0);

if($content==$text){
  // Nada a editar, para script
  exit(logging("Nenhuma edição precisa ser feita. Fechando...\r\n"));
}

// Login step 1
$login_Token = getLoginToken();

// Login step 2
loginRequest( $login_Token );

// Obtendo edit token
$csrf_Token = getCSRFToken();

// Editando a página de pedidos
editRequest($csrf_Token, "User:Stangbot/feed", $text, "[[WP:Bot|bot]]: atualizando", 1, 0);

// Logout
logoutRequest( $csrf_Token );

// PARA TESTE
// ADICIONAR O CONTEÚDO DA EDIÇÃO EM LOG
//logging("Conteúdo da variável text:\r\n" . $text. "\r\n");

// Fechar log
echo logging("Script 1 concluído!\r\n");

?>
