<?php

// Classe para realizar testes
class Debug{

	private static $status;

	private static function check(){

		global $argv;

		if(is_array($argv)){
			$test = array_search('test', $argv);
		}else{
			$test = FALSE;
		}

		if(isset($_GET['test'])||$test){
			echo "##INICIANDO EM MODO TESTE##\r\n";
			self::setStatus(TRUE);
		}else{
			self::setStatus(FALSE);
		}

	}

	private static function getStatus(){

		if(!isset(self::$status)){
			self::check();
		}

		return self::$status;
	}

	public static function isDebug(){
		return self::getStatus();
	}

	private static function setStatus($value){
		self::$status = $value;
	}

}

?>
