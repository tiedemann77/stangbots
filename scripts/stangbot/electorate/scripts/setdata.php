<?php

// Autoloader específico para esse bot
require_once __DIR__ . "/../autoloader.php";

// Requer configurações
require_once __DIR__ . "/../../settings.php";

// Autoloader geral
require_once __DIR__ . "/../../../../autoloader.php";

// Settings
$settings = [
  'credentials' => $uspw,
  'username' => "Stangbot",
  'power' => "User:Stangbot/Power",
  'script' => "set-electorate",
  'url' => "https://www.wikidata.org/w/api.php",
  'maxlag' => 4,
  'file' => __DIR__ .  "/log.log",
  'stats' => array(),
  'replicasDB' => "wikidatawiki",
  'personalDB' => "s54852__stangbots"
];

$robot = new bot();

echo $robot->log->log($robot->username . " - Iniciando " . $robot->script . "\r\n");

// Lista de itens para os estados
$states = [
  'AC' => 'Q40780',
  'AL' => 'Q40885',
  'AP' => 'Q40130',
  'AM' => 'Q40040',
  'BA' => 'Q40430',
  'CE' => 'Q40123',
  'ES' => 'Q43233',
  'GO' => 'Q41587',
  'MA' => 'Q42362',
  'MT' => 'Q42824',
  'MS' => 'Q43319',
  'MG' => 'Q39109',
  'PA' => 'Q39517',
  'PB' => 'Q38088',
  'PR' => 'Q15499',
  'PE' => 'Q40942',
  'PI' => 'Q42722',
  'RJ' => 'Q41428',
  'RN' => 'Q43255',
  'RS' => 'Q40030',
  'RO' => 'Q43235',
  'RR' => 'Q42508',
  'SC' => 'Q41115',
  'SP' => 'Q175',
  'SE' => 'Q43783',
  'TO' => 'Q43695',
  'DF' => 'Q119158'
];

echo $robot->log->log("Obtendo valores do DB (limite = 5)...\r\n");

$query = "SELECT * FROM electorate WHERE updated = 0 ORDER BY timestamp ASC LIMIT 5;";

$result = $robot->sql->personalQuery($query, $params=null);

if(!isset($result[0])){
  $robot->bye("Nenhum valor marcado como não atualizado no DB. Fechando...\r\n");
}

echo $robot->log->log("Iniciando atualização no Wikidata...\r\n");

foreach ($result as $key => $value) {

  $state = $value['state'];
  $municipality = $value['municipality'];

  // Parte 1: obter o item
  if($municipality==0){

    $item = $states[$state];
    echo $robot->log->log("Item para o estado {$state} é {$item}\r\n");

  }else{

    $query = 'SELECT DISTINCT ?item WHERE {?item p:P6555 ?statement0.?statement0 (ps:P6555) "' . $municipality . '".} LIMIT 1';

    $result2 = $robot->sql->wikidataQuery($query);

    $result2 = $result2['results']['bindings'];

    if(count($result2)==0){
      echo $robot->log->log("ERRO: Não existe item com o código do município informado ({$municipality})\r\n");
      continue;
    }

    $item = str_replace ("http://www.wikidata.org/entity/" , "" , $result2[0]['item']['value']);
    echo $robot->log->log("Item para município {$municipality} é {$item}\r\n");

  }

  // Parte 2: editar a propriedade
  $params = [
    'action'    => 'wbgetclaims',
    'entity'    => $item,
    'property'  => 'P1831'
  ];

  $result2 = $robot->api->request($params);

  if(count($result2['claims'])==0){
    // Caso a propriedade não exista, adiciona

    $data = json_encode(['amount' => $value['voters'], 'unit' => '1']);
    $reference = '{"P248":[{"snaktype":"value","property":"P248","datavalue":{"type":"wikibase-entityid","value":{"entity-type":"item","numeric-id":111590317}}}]}';

    $response = $robot->createStatement( $item , "P1831", $data, $reference, "[[WD:BOT|bot]]: add/update P1831");

    $query = "UPDATE electorate SET updated = 1 WHERE state = '$state' AND municipality = '$municipality';";
    $robot->sql->personalQuery($query, $params=null);

    echo $robot->log->log("Propriedade adicionada no item {$item};\r\n");

  }elseif(intval($result2['claims']['P1831'][0]['mainsnak']['datavalue']['value']['amount'])!=$value['voters']){
    //Caso exista, atualiza

    $data = json_encode(['amount' => $value['voters'], 'unit' => '1']);
    $robot->changeStatement( $result2['claims']['P1831'][0]['id'] , $data, "[[WD:BOT|bot]]: add/update P1831");

    $query = "UPDATE electorate SET updated = 1 WHERE state = '$state' AND municipality = '$municipality';";
    $robot->sql->personalQuery($query, $params=null);

    echo $robot->log->log("Propriedade modificada no item {$item};\r\n");

  }else{

    $query = "UPDATE electorate SET updated = 1 WHERE state = '$state' AND municipality = '$municipality';";
    $robot->sql->personalQuery($query, $params=null);

    echo $robot->log->log("Propriedade já está atualizada no item {$item};\r\n");
  }

}

$robot->bye($robot->script . " concluído!\r\n");

?>
