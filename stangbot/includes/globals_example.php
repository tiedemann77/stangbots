<?php

// Bot username obtido em Special:BotPasswords (alguma@coisa)
$username = "";

// Bot username normal
$username2 = "";

// Bot password obtido em Special:BotPasswords
$password = "";

// Página liga/desliga
$powerPage = "User:" . $username2 . "/Power";

// Parâmetro maxlag, (5 ou menos para Wikimedia)
$maxlag = 4;

// Timezone UTC
date_default_timezone_set('UTC');

// Arquivo e data para logs
$logfile = __DIR__ .  "/../temp/log.log";
$logdate =  date("Y-m-d H:i:s");

// Se está ou não no Toolforge (para réplicas)
$toolforge = 0;

?>
