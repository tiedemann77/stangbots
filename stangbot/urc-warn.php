<?php

// Requer configurações
require_once(__DIR__ . "/settings.php");

// Requer funções básicas
require_once(__DIR__ . "/../common.php");

// Settings
$settings = [
  'credentials' => $uspw,
  'username' => "Stangbot",
  'power' => "User:Stangbot/Power",
  'script' => "urc-warn",
  'url' => "https://pt.wikipedia.org/w/api.php",
  'maxlag' => 4,
  'file' => __DIR__ .  "/../log.log",
  'stats' => array(),
  'replicasDB' => "ptwiki",
  'personalDB' => "s54852__stangbots"
];

$robot = new bot();

echo $robot->log->log($robot->username . " - Iniciando " . $robot->script . "\r\n");

$limit = date("Y-m-d H:i:s", strtotime("-1 day"));

echo $robot->log->log("Consultando uploads recentes...\r\n");

$params = [
  "action"    => "query",
  "list"      => "logevents",
  "letype"    => "upload",
  "leaction"  => "upload/overwrite",
  "leend"     => $limit,
  "lelimit"   => "500"
];

$logs = $robot->api->request($params);

if(count($logs['query']['logevents'])==0){
  $robot->bye("Nenhum reupload no período. Fechando...\r\n");
}

foreach ($logs['query']['logevents'] as $key => $value) {
  echo $robot->log->log($value['user'] . " carregou uma nova versão de " . $value['title'] . ";\r\n");
  $reuploads[] = [
    'user' => $value['user'],
    'title' => $value['title']
  ];
}

echo $robot->log->log("Removendo usuários duplicados...\r\n");

$reuploads = array_intersect_key($reuploads, array_unique(array_column($reuploads, 'user')));

echo $robot->log->log("Obtendo lista de arquivos com versões antigas...\r\n");

$query = 'SELECT img_name FROM image, oldimage WHERE image.img_name = oldimage.oi_name ORDER BY img_name ASC;';

$result = $robot->sql->replicasQuery($query, $params=NULL);

if(!isset($result[0])){
  $robot->bye("Não há nenhuma imagem com versões antigas. Fechando...\r\n");
}

echo $robot->log->log("Verificando se os uploads recentes contém versões antigas...\r\n");

foreach ($reuploads as $key => $value) {
  foreach ($result as $key2 => $value2) {
    $temp_title = str_replace(" ","_",$value['title']);
    $temp_img = "Ficheiro:" . $value2['img_name'];
    if(preg_match("/" . $temp_title . "/", $temp_img)){
      echo $robot->log->log($value['title'] . ", carregado por " . $value['user'] . " contém versão antiga;\r\n");
      $reuploads_v1[] = [
        'user' => $value['user'],
        'title' => $value['title']
      ];
    }
  }
}

if(!isset($reuploads_v1)){
  $robot->bye("Nenhuma imagem carregada recentemente contém versões antigas. Fechando...\r\n");
}

echo $robot->log->log("Obtendo afluentes da predefinição {{URC reduzido}}...\r\n");

$transclusions = $robot->api->transclusions("Predefinição:URC reduzido");

if(count($transclusions['Predefinição:URC reduzido'])>0){
  foreach ($reuploads_v1 as $key => $value) {
    foreach ($transclusions['Predefinição:URC reduzido'] as $key2 => $value2) {
      if(preg_match("/" . $value['title'] . "/", $value2)){
        echo $robot->log->log($value['title'] . " foi marcado com {{URC reduzido}}, desconsiderando;\r\n");
          if(count($reuploads_v1)==1){
            $reuploads_v1 = array();
          }else{
            array_slice($reuploads_v1,$key,1);
          }
      }
    }
  }
}

if(count($reuploads_v1)==0){
  $robot->bye("Todas as imagens carregadas recentemente com versões antigas estão marcadas com {{URC reduzido}}. Fechando...\r\n");
}

echo $robot->log->log("Checando lista de usuários que optaram por não receber mensagens...\r\n");

$links = $robot->api->linksOnPage("Usuário(a):Stangbot/URC-Aviso/Descadastro");

if(count($links['Usuário(a):Stangbot/URC-Aviso/Descadastro'])>0){
  foreach ($reuploads_v1 as $key => $value) {
    foreach ($links['Usuário(a):Stangbot/URC-Aviso/Descadastro'] as $key2 => $value2) {
      if(preg_match("/" . $value['user'] . "/", $value2)){
        echo $robot->log->log($value['user'] . " optou por não receber mensagens, desconsiderando;\r\n");
        if(count($reuploads_v1)==1){
          $reuploads_v1 = array();
        }else{
          array_slice($reuploads_v1,$key,1);
        }
      }
    }
  }
}

if(count($reuploads_v1)==0){
  $robot->bye("Todas os usuários optaram por não receber mensagens. Fechando...\r\n");
}

echo $robot->log->log("Verificando se os usuários já receberam mensagens nos últimos 5 dias...\r\n");

foreach ($reuploads_v1 as $key => $value) {
  $talk = "User talk:" . $value["user"];

  $params = [
    "action"  =>  "query",
    "prop"    =>  "revisions",
    "titles"  =>  $talk,
    "rvprop"  =>  "user",
    "rvend"   =>  date("Y-m-d H:i:s", strtotime("-5 day")),
    "rvlimit" =>  "500"
  ];

  $result = $robot->api->request($params);

  $result = $result["query"]["pages"];

  foreach ($result as $key2 => $value2) {
    if(isset($value2["revisions"])){
      $users = array_unique(array_column($value2["revisions"], 'user'));
      foreach ($users as $key3 => $value3) {
        if(preg_match("/" . $robot->username . "/", $value3)){
          echo $robot->log->log($value['user'] . " já recebeu alertas nos últimos 5 dias, desconsiderando;\r\n");
          if(count($reuploads_v1)==1){
            $reuploads_v1 = array();
          }else{
            array_slice($reuploads_v1,$key,1);
          }
        }
      }
    }
  }
}

if(count($reuploads_v1)==0){
  $robot->bye("Todas os usuários já foram avisados nos últimos 5 dias. Fechando...\r\n");
}

echo $robot->log->log("Mandando mensagens...\r\n");

foreach ($reuploads_v1 as $key => $value) {
  $robot->editSection("User talk:" . $value["user"],"new","{{subst:Usuário(a):Stangbot/URC-Aviso/Predefinição|" . $value['title'] . "}}","Aviso sobre seu upload recente",1,0);
  echo $robot->log->log($value["user"] . " avisado!\r\n");
}

$robot->bye($robot->script . " concluído!\r\n");

?>
