<?php

// Requer credenciais
require_once(__DIR__ . "/settings.php");

// Requer funções básicas
require_once(__DIR__ . "/../autoloader.php");

// Configurações básicas
$settings = [
  'credentials' => $uspw,
  'username' => "BotName", // Nome de usuário
  'power' => "User:BotName/Power", // Página on/off na wiki, precisa ter como conteúdo "on" para que o robô rode
  'script' => "MyScript", // Nome do scripts, para logs e estatísticas
  'url' => "https://pt.wikipedia.org/w/api.php", // Endpoint da API (endereço para as requisições)
  'maxlag' => 4, // Usado para medir a carga do servidor e postergar a execução do programa, para a Wikipédia usar 5 ou menos
  'file' => __DIR__ .  "/log.log", // Arquivo usado para log
  'replicasDB' => "ptwiki", // Caso execute no toolforge, qual banco de dados conectar (réplicas)
  'personalDB' => "s54852__stangbots" // Caso execute no toolforge, qual banco de dados conectar (particular)
];

// Criando o robô, básico e sempre necessário
$robot = new Bot();

// Log padrão
echo $robot->log->log($robot->username . " - Iniciando " . $robot->script . "\r\n");

// Obtendo o conteúdo de uma página
$content = $robot->api->getContent("Wikipédia:Página principal", 0);

// Exibindo o conteúdo
echo $content;

// Finalizando o script
$robot->bye($robot->script . " concluído!\r\n");

?>
