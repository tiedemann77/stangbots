<?php

// Requer funções para esse script em específico
require_once(__DIR__ . "/includes/functions_1.php");

// Requer configurações
require_once(__DIR__ . "/settings.php");

// Requer funções básicas
require_once(__DIR__ . "/../../autoloader.php");

// Settings
$settings = [
  'credentials' => $uspw,
  'username' => "VerificaBot",
  'power' => "User:VerificaBot/Power",
  'script' => "Script 1",
  'url' => "https://pt.wikipedia.org/w/api.php",
  'maxlag' => 4,
  'file' => __DIR__ .  "/log.log",
  'replicasDB' => "ptwiki",
  'personalDB' => "s54852__stangbots"
];

$robot = new bot();

echo $robot->log->log($robot->username . " - Iniciando " . $robot->script . "\r\n");

// Página base
$BasePage = "Wikipédia:Pedidos a verificadores";

// Página listando os pedidos concluídos recentemente
$recentsPage = $BasePage . "/Recentes";

// Cria array para armazenar redirects, utilizado em mais de uma função
$redirects = array();

// Obtendo o conteúdo da página de pedidos
$contentBase = $robot->api->getSectionContent($BasePage, 1);

// Remover qualquer coisa que esteja comentanda (normalmente exemplos) para pegar somente os casos
$contentBase = preg_replace($htmlcommentRegex, "", $contentBase);

// Obtendo os casos listados na página de pedidos
$OpenCases = getOpenCasesList($contentBase);

// Verificando se algum caso em aberto foi fechado
$ClosedCases = getClosedCases($OpenCases);

// Reinsere o exemplo
$textKey = "!(Re)Aberto em";

$example = "
<!--{{Wikipédia:Pedidos a verificadores/Listar|Usuário Exemplo|~~~~~}}-->";
$example = $textKey . $example;

$contentBase = str_replace($textKey, $example, $contentBase);

// Remove casos fechados da página de pedidos
$newContentBase = updateCaseList($OpenCases, $ClosedCases, $contentBase);

// Adiciona casos fechados na página de recentes
$newContentRecents = updateRecentsList( $ClosedCases );

// Se o script não parou até agora, há edições a fazer

// Editando a página de pedidos
$robot->editSection($BasePage, 1, $newContentBase, "[[WP:Bot|bot]]: removendo casos encerrados", 1, 1);

// Editando a página de recentes
$robot->edit($recentsPage, $newContentRecents, "[[WP:Bot|bot]]: adicionando casos encerrados recentemente", 1, 1);

// PARA TESTE
// ADICIONAR O CONTEÚDO DA EDIÇÃO EM LOG
//$robot->log->log("Content of newContentBase string:\r\n" . $newContentBase . "\r\n");
//$robot->log->log("Content of newContentRecents string:\r\n" . $newContentRecents . "\r\n");

// Fim
$robot->bye($robot->script . " concluído!\r\n");

?>
