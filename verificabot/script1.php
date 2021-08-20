<?php

/*
    ESSE SCRIPT ATUALIZA [[Wikipedia:Pedidos a verificadores]] E [[Wikipedia:Pedidos a verificadores/Recentes]]
*/

// Requer variáveis básicas
require_once("includes/globals.php");

// Requer funções básicas
require_once(__DIR__ . "/../common.php");

// Requer funções para esse script em específico
require_once("includes/functions_1.php");

// Começa o log
echo logging($logdate . "
Iniciando script 1...\r\n");

// Verifica se o bot está ligado
checkPower();

// FIM DO BÁSICO
//--------------------------------------------------------------------
// INICIANDO SCRIPT EM SI

// Cria array para armazenar redirects, utilizado em mais de uma função
$redirects = array();

// Obtendo o conteúdo da página de pedidos
$contentBase = getContent($BasePage, 1);

// Obtendo os casos listados na página de pedidos
$OpenCases = getOpenCasesList($contentBase);

// Verificando se algum caso em aberto foi fechado
$ClosedCases = getClosedCases($OpenCases);

// Remove casos fechados da página de pedidos
$newContentBase = updateCaseList($OpenCases, $ClosedCases, $contentBase);

// Adiciona casos fechados na página de recentes
$newContentRecents = updateRecentsList( $ClosedCases );

// Se o script não parou até agora, há edições a fazer

// Login step 1
$login_Token = getLoginToken();

// Login step 2
loginRequest( $login_Token );

// Obtendo edit token
$csrf_Token = getCSRFToken();

// Editando a página de pedidos
editRequest($csrf_Token, $BasePage, $newContentBase, "[[WP:Bot|bot]]: removendo casos encerrados");

// Editando a página de recentes
editRequest($csrf_Token, $recentsPage, $newContentRecents, "[[WP:Bot|bot]]: adicionando casos encerrados recentemente");

// PARA TESTE
// ADICIONAR O CONTEÚDO DA EDIÇÃO EM LOG
//logging("Content of newContentBase string:\r\n" . $newContentBase . "\r\n");
//logging("Content of newContentRecents string:\r\n" . $newContentRecents . "\r\n");

// Fechar log
echo logging("Script 1 concluído!\r\n");

?>
