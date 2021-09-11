<?php

// Verifica se a conta já foi renomeada no passado via replicas
function hasRenames($name){

  $user = "CentralAuth/" . $name;
  $query = 'SELECT log_timestamp FROM logging WHERE log_type = "gblrename" AND log_title = ? ORDER BY log_id DESC LIMIT 1;';

  // Faz a consulta
  $result = replicaQuery("metawiki", $query, $user);

  // Verifica se há renomeçãoes e retorna a data da última
  if(isset($result[0]['log_timestamp'])){

    // Formatando data
    $year = substr($result[0]['log_timestamp'], 0, 4);
    $month = substr($result[0]['log_timestamp'], 4, 2);
    $day = substr($result[0]['log_timestamp'], 4, 2);

    $month = monthstoPT($month);

    $date = $day . " de " . $month . " de " . $year;

    return $date;

  }else{
    // Se não houver, retorna 0
    return 0;
  }

}

?>
