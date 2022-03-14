<?php

// Verifica se a conta já foi renomeada no passado via replicas
function hasRenames($name){

  global $robot;

  $user = "CentralAuth/" . $name;
  $query = 'SELECT log_timestamp FROM logging WHERE log_type = "gblrename" AND log_title = ? ORDER BY log_id DESC LIMIT 1;';

  $params = [
    array("s",$user)
  ];

  // Faz a consulta
  $result = $robot->sql->replicasQuery($query, $params);

  // Verifica se há renomeçãoes e retorna a data da última
  if(isset($result[0]['log_timestamp'])){

    // Formatando data
    $year = substr($result[0]['log_timestamp'], 0, 4);
    $month = substr($result[0]['log_timestamp'], 4, 2);
    $day = substr($result[0]['log_timestamp'], 6, 2);

    $month = monthstoPT($month);

    $date = $day . " de " . $month . " de " . $year;

    return $date;

  }else{
    // Se não houver, retorna 0
    return 0;
  }

}

// Função para verificar se a página foi editada ou se houverem renomeações relacionadas
function run($content){

  global $robot;
  global $cachefile;

  // Requer o arquivo de cache
  if(!file_exists($cachefile)){
    if(!fopen($cachefile, 'w')){
      $robot->bye("Não foi possível criar o arquivo de cache especificado. Script interrompido. Por favor, crie o arquivo manualmente para prosseguir. Fechando...\r\n");
    }
  }else{

    $cache = file_get_contents($cachefile);

    // Se o cache e o conteúdo forem iguais, verifica se houveram renomeações
    if($content===$cache){

      // Troca temporária
      $robot->api->url = "https://meta.wikimedia.org/w/api.php";

      $start = date("Y-m-d H:i:s", strtotime("-1 hour"));

      $params = [
        "action" => "query",
        "list" => "logevents",
        "letype" => "gblrename",
        "leend" => $start,
        "lelimit" => "500"
      ];

      // Faz consulta a API
      $result = $robot->api->request($params);
      // Retornando
      $robot->api->url = "https://pt.wikipedia.org/w/api.php";

      // Verifica se houveram renomeações
      if(isset($result['query']['logevents']['0'])){

        foreach ($result['query']['logevents'] as $key => $value) {

          // Verifica se houveram renomeações possivelmente relacionadas
          if(preg_match("/" . $result['query']['logevents'][$key]['params']['newuser'] . "/",$content)){
            $count = 1;
          }

        }

        // Se não, para
        if(!isset($count)){
          $robot->bye("Não há razão para rodar (mesmo cache, sem renomeação relacionada). Fechando...\r\n");
        }

      // Se não, para
      }else{
        $robot->bye("Não há razão para rodar (mesmo cache, sem nenhuma renomeação). Fechando...\r\n");
      }

    }

  }

}

?>
