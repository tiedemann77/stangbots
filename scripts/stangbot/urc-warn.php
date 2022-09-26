<?php

##
## INÍCIO DAS DECLARAÇÕES
##

// Requer configurações
require_once(__DIR__ . "/settings.php");

// Requer funções básicas
require_once(__DIR__ . "/../../autoloader.php");

// Classe para lidar com a totalidade dos uploads
class objects{

  private $objects;

  public function __construct(){
    $this->objects = array();
  }

  public function newEntry( $author, $file ){
    $this->objects[] = new logentry( $author, $file );
  }

  public function getObjects(){
    return $this->objects;
  }

  public function removeDuplicates(){

    foreach ($this->objects as $key => $value) {

      if(!isset($this->objects[$key])){
        continue;
      }

      foreach ($this->objects as $key2 => $value2) {

        if($key==$key2){
          continue;
        }

        if($value->getAuthor()==$value2->getAuthor()){
          unset($this->objects[$key2]);
        }

      }

    }

  }

  public function cleaningNoOldVersions(){

    foreach ($this->objects as $key => $value) {

      if($value->getOldVersion()===FALSE){
        unset($this->objects[$key]);
      }

    }

  }

  public function removeObject($key){
    unset($this->objects[$key]);
  }

  public function countObjects(){
    return count($this->objects);
  }

}

// Classe para lidar com cada upload em específico
class logentry{

  private $author;
  private $file;
  private $file_underline;
  private $talk;
  private $oldversion;

  public function __construct( $author, $file ){
    $this->author = $author;
    $this->file = $file;
    $this->setFileUnderline();
    $this->setTalk();
  }

  private function setFileUnderline(){
    $this->file_underline = str_replace( " " , "_" , $this->file );
  }

  private function setTalk(){
    $this->talk = "User talk:{$this->author}";
  }

  public function getTalk(){
    return $this->talk;
  }

  public function getAuthor(){
    return $this->author;
  }

  public function getFile(){
    return $this->file;
  }

  public function getFileUnderline(){
    return $this->file_underline;
  }

  public function setOldVersion(){

    if(!isset($this->oldversion)){
      $this->oldversion = TRUE;
      return TRUE;
    }

    return FALSE;

  }

  public function getOldVersion(){

    if(!isset($this->oldversion)){
      return FALSE;
    }

    return $this->oldversion;

  }

}

##
## FIM DAS DECLARAÇÕES
## INÍCIO DA EXECUÇÃO
##

// Settings
$settings = [
  'credentials' => $uspw,
  'username' => "Stangbot",
  'power' => "User:Stangbot/Power",
  'script' => "urc-warn",
  'url' => "https://pt.wikipedia.org/w/api.php",
  'maxlag' => 4,
  'file' => __DIR__ .  "/log.log",
  'replicasDB' => "ptwiki",
  'personalDB' => "s54852__stangbots"
];

$robot = new bot();

$objects = new objects();

echo $robot->log->log($robot->username . " - Iniciando " . $robot->script . "\r\n");

echo $robot->log->log("ETAPA 1/7: Consultando uploads recentes...\r\n");

$params = [
  "action"    => "query",
  "list"      => "logevents",
  "letype"    => "upload",
  "leaction"  => "upload/overwrite",
  "leend"     => date("Y-m-d H:i:s", strtotime("-1 day")),
  "lelimit"   => "500"
];

$logs = $robot->api->request($params);

if(count($logs['query']['logevents'])==0){
  $robot->bye("Nenhum reupload no período. Fechando...\r\n");
}

foreach ($logs['query']['logevents'] as $key => $value) {
  echo $robot->log->log($value['user'] . " carregou uma nova versão de " . $value['title'] . ";\r\n");
  $objects->newEntry( $value['user'], $value['title'] );
}

$objects->removeDuplicates();

echo $robot->log->log("ETAPA 2/7: Obtendo lista de arquivos com versões antigas...\r\n");

$query = 'SELECT img_name FROM image, oldimage WHERE image.img_name = oldimage.oi_name ORDER BY img_name ASC;';

$result = $robot->sql->replicasQuery($query, $params=NULL);

if(!isset($result[0])){
  $robot->bye("Não há nenhuma imagem com versões antigas. Fechando...\r\n");
}

echo $robot->log->log("ETAPA 3/7: Verificando se os uploads recentes contém versões antigas...\r\n");

foreach ($objects->getObjects() as $key => $value) {
  foreach ($result as $key2 => $value2) {
    $temp_img = "Ficheiro:" . $value2['img_name'];
    if(preg_match("/" . $value->getFileUnderline() . "/", $temp_img)){
      if($value->setOldVersion()){
        echo $robot->log->log("Encontrada versão antiga de " . $value->getFile() . ", carregada por " . $value->getAuthor() . ";\r\n");
      }
    }
  }
}

$objects->cleaningNoOldVersions();

if($objects->countObjects()==0){
  $robot->bye("Nenhuma imagem carregada recentemente contém versões antigas. Fechando...\r\n");
}

echo $robot->log->log("ETAPA 4/7: Obtendo afluentes da predefinição {{URC reduzido}}...\r\n");

$transclusions = $robot->api->transclusions("Predefinição:URC reduzido");

if(count($transclusions['Predefinição:URC reduzido'])>0){
  foreach ($objects->getObjects() as $key => $value) {
    foreach ($transclusions['Predefinição:URC reduzido'] as $key2 => $value2) {
      if(preg_match("/" . $value->getFile() . "/", $value2)){
        echo $robot->log->log($value->getFile() . ", carregado por " . $value->getAuthor() . " foi marcado com {{URC reduzido}}, desconsiderando;\r\n");
        $objects->removeObject($key);
      }
    }
  }
}

if($objects->countObjects()==0){
  $robot->bye("Todas as imagens carregadas recentemente com versões antigas estão marcadas com {{URC reduzido}}. Fechando...\r\n");
}

echo $robot->log->log("ETAPA 5/7: Checando lista de usuários que optaram por não receber mensagens...\r\n");

$links = $robot->api->linksOnPage("Usuário(a):Stangbot/URC-Aviso/Descadastro");

if(count($links['Usuário(a):Stangbot/URC-Aviso/Descadastro'])>0){
  foreach ($objects->getObjects() as $key => $value) {
    foreach ($links['Usuário(a):Stangbot/URC-Aviso/Descadastro'] as $key2 => $value2) {
      if(preg_match("/" . $value->getAuthor() . "/", $value2)){
        echo $robot->log->log($value->getAuthor() . " optou por não receber mensagens, desconsiderando;\r\n");
        $objects->removeObject($key);
      }
    }
  }
}

if($objects->countObjects()==0){
  $robot->bye("Todas os usuários optaram por não receber mensagens. Fechando...\r\n");
}

echo $robot->log->log("ETAPA 6/7: Verificando se os usuários já receberam mensagens nos últimos 5 dias...\r\n");

foreach ($objects->getObjects() as $key => $value) {

  $params = [
    "action"  =>  "query",
    "prop"    =>  "revisions",
    "titles"  =>  $value->getTalk(),
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
          echo $robot->log->log($value->getAuthor() . " já recebeu alertas nos últimos 5 dias, desconsiderando;\r\n");
          $objects->removeObject($key);
        }
      }
    }
  }
}

if($objects->countObjects()==0){
  $robot->bye("Todas os usuários já foram avisados nos últimos 5 dias. Fechando...\r\n");
}

echo $robot->log->log("ETAPA 7/7: Mandando mensagens...\r\n");

foreach ($objects->getObjects() as $key => $value) {
  $robot->editSection($value->getTalk(),"new","{{subst:Usuário(a):Stangbot/URC-Aviso/Predefinição|" . $value->getFile() . "}}","Aviso sobre seu (re)upload recente",0,1);
  echo $robot->log->log($value->getAuthor() . " avisado!\r\n");
}

$robot->bye($robot->script . " concluído!\r\n");

?>
