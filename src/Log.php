<?php

require_once("Common.php");
require_once("Debug.php");

// Log
class Log extends Common{

	public 	$file;
	private $ready;
	public 	$stats;

	public function __construct($file,$stats){
		$this->file = $file;
		$this->stats = $stats;
		$this->isDebug();
		$this->check();
		$this->log($this->stats->getStart()->format('d-m-Y H:i:s') . " - Iniciando log\r\n");
		$this->log("Ambiente de execução: ". debug::getEnvironment('host') . " - " . debug::getEnvironment('os') . " - " . debug::getEnvironment('release') . " - " . debug::getEnvironment('version') . " - " . debug::getEnvironment('machine') . "\r\n");
		$this->clear();
	}

	public function __destruct(){
		$this->log($this->stats->getEnd()->format('d-m-Y H:i:s') . " - Fechando log\r\n");
	}

	public function bye($message){
		echo $this->log($message);
		exit();
	}

	private function check(){

		if(!file_exists($this->file)){
			if(!fopen($this->file, 'w')){
				$this->ready = FALSE;
				$this->bye("Não foi possível criar o arquivo de log especificado. Provavelmente um erro de permissão ou o diretório não existe. Script interrompido. Por favor, crie o arquivo manualmente para prosseguir. Fechando...\r\n");
			}
		}

		$this->ready = TRUE;

	}

	private function clear(){
		if(filesize($this->file)>4194304){ //4 MB
			$this->log("MANUTENÇÃO: " . $this->file . " está com " . number_format((filesize($this->file)/1024/1024),2,".",",") . " MB, realizando limpeza\r\n");
			$file = file($this->file);
			$delete = round(count($file)/10); //10%
			$file = array_slice($file, $delete);
			file_put_contents($this->file, $file);
			unset($file);
		}
	}

	protected function isDebug(){

		if(!isset($this->debug)){
			$this->debug = debug::isDebug();

			if($this->debug==TRUE){
				$this->file .= ".test";
			}

		}

		return $this->debug;

	}

	public function log($msg){

		if($this->isDebug()){
			$msg = "(MODO TESTE) $msg";
		}

		if($this->ready==TRUE){
			file_put_contents($this->file, $msg, FILE_APPEND);
		}

		return $msg;

	}

}

?>
