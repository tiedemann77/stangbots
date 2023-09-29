<?php

// Requer configurações
require_once(__DIR__ . "/cat-settings.php");

// Requer funções básicas
require_once(__DIR__ . "/../../autoloader.php");

// Settings
$settings = [
  'credentials' => $uspw,
  'username' => "RGhidini (Projeto Mais+)",
  'power' => "User:Stangbot/Power",
  'script' => "cat-a-lot",
  'url' => "https://pt.wikipedia.org/w/api.php",
  'maxlag' => 4,
  'file' => __DIR__ .  "/log.log",
  'stats' => array(),
  'replicasDB' => "ptwiki",
  'personalDB' => "s54852__stangbots"
];

$robot = new bot();

echo $robot->log->log($robot->username . " - Iniciando " . $robot->script . "\r\n");

$filename = __DIR__ .  "/list.txt";

$file = file($filename);

if(count($file)<4){
  $robot->bye("Lista concluída!\r\n");
}

$limit = 0;

while ($limit <= 2) {

  $title = str_replace("\n", "", $file[0]);

  $content = $robot->api->getContent($title,0);

  if($content===0){
    echo $robot->log->log("# " . $title . " sem conteúdo;\r\n");

    $limit++;

    $file = array_slice($file, 1);

    unlink($filename);

    file_put_contents($filename, $file);

    continue;
  }

  if(preg_match("/(#(R|r)(E|e)(D|d)(I|i)(R|r)(E|e)(C|c))/",$content)){

    echo $robot->log->log("# " . $title . " -> ");

    $title = $robot->api->resolveRedir($title);

    echo $robot->log->log($title . "\r\n");

    $content = $robot->api->getContent($title,0);

    if($content===0){
      echo $robot->log->log("# " . $title . " sem conteúdo;\r\n");

      $limit++;

      $file = array_slice($file, 1);

      unlink($filename);

      file_put_contents($filename, $file);

      continue;
    }

  }

  if(preg_match("/(\{\{(D|d)esambiguação)/",$content)){
    echo $robot->log->log("# " . $title . " é desambiguação;\r\n");

    $limit++;

    $file = array_slice($file, 1);

    unlink($filename);

    file_put_contents($filename, $file);

    continue;
  }

  $content .= "
[[Categoria:!Mais Teoria da História na Wiki (Mais Diversidade)]]";

  $robot->edit($title,$content,"adicionando temporariamente categoria de WikiConcurso",1,0);

  $limit++;

  $file = array_slice($file, 1);

  unlink($filename);

  file_put_contents($filename, $file);

}

$robot->bye($robot->script . " concluído!\r\n");

?>
