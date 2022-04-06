<?php

// Carregando classes
spl_autoload_register(function($class) {

	$file = __DIR__ . "/src/" . $class . ".php";

	if(file_exists( $file )) {
		require_once($file);
	}

});

// Carregando regex comuns
require_once("regex.php");

?>
