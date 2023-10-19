<?php

// Requer configurações
require_once(__DIR__ . "/mt-settings.php");

// Requer funções básicas
require_once(__DIR__ . "/../../autoloader.php");

// Autoloader específico para esse script
require_once __DIR__ . "/autoloader.php";

// Settings
$settings = [
  'credentials' => $uspw,
  'username' => "Projeto Mais Teoria da História na Wiki",
  'power' => "User:Projeto Mais Teoria da História na Wiki/Power",
  'script' => "mt-new-pages",
  'url' => "https://pt.wikipedia.org/w/api.php",
  'maxlag' => 4,
  'file' => __DIR__ .  "/mt-log.log",
  'stats' => array(),
  'replicasDB' => "ptwiki",
  'personalDB' => "s54852__stangbots"
];

$robot = new bot();

echo $robot->log->log($robot->username . " - Iniciando " . $robot->script . "\r\n");

$day = 10;

while( $day < 18){

  //Definições
  $period['start'] = "2023-10-" . $day . " 23:59:59";
  $period['end'] = "2023-10-" . $day . " 00:00:00";
  $yesterday = $day . "-10-2023";
  
  
  // Importando configurações do serviço
  echo $robot->log->log("Lendo configurações do serviço de monitoramento...\r\n");
  
  $jobs = json_decode($robot->api->getContent("User:" . $settings['username'] . "/Monitoramento/Settings", 0), true);
  
  echo $robot->log->log("Foram encontrados " . count($jobs) . " eventos para monitorar.\r\n");
  
  echo $robot->log->log("Será checado o dia anterior ({$yesterday}).\r\n");
  
  
  foreach($jobs as $key => $value){
  
    echo $robot->log->log("Iniciando checagem de {$value['evento']}\r\n");
  
    echo $robot->log->log("Efetuando download da lista de inscritos do Outreach Dashboard...\r\n");
  
    $download = new Download($value['evento'], $value['outreachdashboard'], $robot->log);
  
    if(!$download->getFile()){
  
      $robot->bye("Erro ao realizar o download do arquivo. Fechando...\r\n");
  
    };
  
    // Lendo arquivo CSV e salvando lista de usuários em array e em página wiki
    echo $robot->log->log("Lendo arquivo csv...\r\n");
    
    $file_path = __DIR__ . "/temp/" . $value['evento'] . ".csv";
  
    $file = fopen($file_path, 'r');
  
    // Onde salvar os valores
    $users = array();
  
    $content = "
Essa é uma lista de usuáries inscrites no evento {$value['evento']}, sincronizada automaticamente a partir do Outreach Dashboard. Por favor, não faça modificações manuais nesta página pois elas serão eliminadas na próxima atualização :)
  
'''Última atualização''': {{REVISIONDAY2}}-{{REVISIONMONTH}}-{{REVISIONYEAR}}
  
== Lista de inscrites ==";
  
    while (($row = fgetcsv($file, 0, ",")) !== false) {
  
      if($row[0]!="username"){
        $users[] = $row[0];
        $content .= "
  # {$row[0]}";
      }
  
    }
  
    fclose($file);
  
    unlink($file_path);
  
    echo $robot->log->log("Total de inscritos: " . count($users) . "\r\n");
  
    echo $robot->log->log("Salvando lista de usuários onwiki...\r\n");
  
    if($robot->api->getContent("User:" . $settings['username'] . "/Monitoramento/" . $value['evento'] . "/Participantes", 0)!==$content){
      $robot->edit("User:" . $settings['username'] . "/Monitoramento/" . $value['evento'] . "/Participantes",$content,"atualizando lista de participantes",1,0);
    }else{
      echo $robot->log->log("A lista de usuários onwiki já estava atualizada.\r\n");
    }
  
    //Iniciamos a checagem de cada editor
    $report = "== {$yesterday} ==
Esses foram os '''artigos criados e movidos''' pelos participantes do evento {$value['evento']}. Total de inscrites: " . count($users) . ".
  
<small>{{ping|RGhidini (Projeto Mais+)}} uma atualização está disponível, jovem padawan. ~~~~</small>
  ";
  
    foreach($users as $key2 => $user){
  
  	//Checando criações de artigos no DP
  	echo $robot->log->log("Checando criações de {$user} em {$value['evento']}...\r\n");
      $creations = new Creations($user, $robot, $period);
  
      $creations_report = $creations->returnReport();
  	
  	//Checando movimentações de páginas para o DP
  	echo $robot->log->log("Checando movimentações de {$user} em {$value['evento']}...\r\n");
      $moves = new Moves($user, $robot, $period);
  
      $moves_report = $moves->returnReport();
  	
      if($creations_report!=FALSE||$moves_report!=FALSE){
        $report .= "
=== {$user} ===";
      }
  
      if($creations_report!=FALSE){
        $report .= $creations_report . "
  ";
      }
  
      if($moves_report!=FALSE){
        $report .= $moves_report . "
  ";
      }
  
    }
  
    echo $robot->log->log("Relatório de {$value['evento']} pronto!\r\n");
    
    $old_report = $robot->api->getContent("User:" . $settings['username'] . "/Monitoramento/" . $value['evento'] . "/Novas páginas", 0);
    
    if($old_report===0){
  	  
      $robot->edit("User:" . $settings['username'] . "/Monitoramento/" . $value['evento'] . "/Novas páginas",$report,"atualizando relatório",1,0);
  	
  	echo $robot->log->log("Relatório criado.\r\n");
  		
    }else{
  	  
  	$report .= "
" . $old_report;
  
      $robot->edit("User:" . $settings['username'] . "/Monitoramento/" . $value['evento'] . "/Novas páginas",$report,"atualizando relatório",1,0);
  	
      echo $robot->log->log("Relatório atualizado.\r\n");
  	
    }
  
  }
  
  $day++;

}

$robot->bye($robot->script . " concluído!\r\n");

?>
