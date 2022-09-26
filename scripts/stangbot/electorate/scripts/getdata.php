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
  'script' => "get-electorate",
  'url' => "https://www.wikidata.org/w/api.php",
  'maxlag' => 4,
  'file' => __DIR__ .  "/log.log",
  'replicasDB' => "wikidatawiki",
  'personalDB' => "s54852__stangbots"
];

$robot = new bot();

echo $robot->log->log($robot->username . " - Iniciando " . $robot->script . "\r\n");

$robot->log->log("Selecionando estado...");

$states = array(
  '01' => 'AC',
  '02' => 'AL',
  '03' => 'AP',
  '04' => 'AM',
  '05' => 'BA',
  '06' => 'CE',
  '07' => 'ES',
  '08' => 'GO',
  '09' => 'MA',
  '10' => 'MT',
  '11' => 'MS',
  '12' => 'MG',
  '13' => 'PA',
  '14' => 'PB',
  '15' => 'PR',
  '16' => 'PE',
  '17' => 'PI',
  '18' => 'RJ',
  '19' => 'RN',
  '20' => 'RS',
  '21' => 'RO',
  '22' => 'RR',
  '23' => 'SC',
  '24' => 'SP',
  '25' => 'SE',
  '26' => 'TO',
  '27' => 'DF'
);

$day = $robot->stats->getStart()->format('d');

$state = $states[$day];

$robot->log->log("...{$state}\r\n");

$file = new File($state, $robot->log);

echo $robot->log->log("Iniciando processamento...\r\n");

$data = $file->process();

$query = "SELECT * FROM electorate WHERE state = '$state';";

$result = $robot->sql->personalQuery($query, $params=null);

$timestamp = time();

if(!isset($result[0])) {

    foreach ($data[$state] as $key => $value) {

        $query = "INSERT INTO electorate (state, municipality, voters, updated, timestamp) VALUES ('$state', '$key', '$value', 0, '$timestamp');";

        $robot->sql->personalQuery($query, $params=null);

    }

}else{

    foreach ($data[$state] as $key => $value) {

        foreach ($result as $key2 => $value2) {

            if($value2['state']==$state&&$value2['municipality']==$key) {

                if($value!=$value2['voters']) {

                  if($value['updated']==0){
                    $query = "UPDATE electorate SET voters = '$value' WHERE state = '$state' AND municipality = '$key';";
                  }else{
                    $query = "UPDATE electorate SET voters = '$value', updated = 0, timestamp = '$timestamp' WHERE state = '$state' AND municipality = '$key';";
                  }

                    $robot->sql->personalQuery($query, $params=null);

                }

                $found = true;

                break;

            }

        }

        if(!isset($found)) {
            $query = "INSERT INTO electorate (state, municipality, voters, updated, timestamp) VALUES ('$state', '$key', '$value', 0, '$timestamp');";
            $robot->sql->personalQuery($query, $params=null);
        }else{
            unset($found);
        }

    }

}

$robot->bye($robot->script . " concluído!\r\n");

?>
