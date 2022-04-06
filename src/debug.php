<?php

// Classe para realizar testes
class debug{

	private static $status;

	private function check(){

		global $argv;

		if(isset($_GET['test'])||array_search('test', $argv)){
			echo "##INICIANDO EM MODO TESTE##\r\n";
			self::setStatus(TRUE);
		}else{
			self::setStatus(FALSE);
		}

	}

	private function getStatus(){

		if(!isset(self::$status)){
			self::check();
		}

		return self::$status;
	}

	public static function isDebug(){
		 return self::getStatus();
	}

	private function setStatus($value){
		self::$status = $value;
	}

}

?>
