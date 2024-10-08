<?php

// Classe para realizar testes
class Debug{

	private static $debug;

	private static $environment;

	private static $windows;

	private static function check(){

		self::checkDebug();

		self::checkEnvironment();

	}

	private static function checkDebug(){

		global $argv;

		if(is_array($argv)){
			$test = array_search('test', $argv);
		}else{
			$test = FALSE;
		}

		if(isset($_GET['test'])||$test){
			echo "##INICIANDO EM MODO TESTE##\r\n";
			self::setDebug(TRUE);
		}else{
			self::setDebug(FALSE);
		}

	}

	private static function checkEnvironment(){

		self::setEnvironment(php_uname('s'));

		self::checkWindows();

	}

	private static function CheckWindows(){

		if(preg_match("/(W|w)indows/",self::getEnvironment())){

			self::setWindows(TRUE);

		}else{

			self::setWindows(FALSE);

		}

	}

	public static function getEnvironment(){

		if(!isset(self::$environment)){
			self::check();
		}

		return self::$environment;

	}

	public static function isDebug(){
	// Getter
		if(!isset(self::$debug)){
			self::check();
		}

		return self::$debug;

	}

	public static function isWindows(){
	// Getter
		if(!isset(self::$windows)){
			self::check();
		}

		return self::$windows;

	}

	private static function setEnvironment($value){

		self::$environment = $value;

	}

	private static function setDebug($value){

		self::$debug = $value;

	}

	private static function setWindows($value){

		self::$windows = $value;

	}

}

?>
