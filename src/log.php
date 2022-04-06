<?php

require_once("common.php");

// Log
class log extends common{

	public $file;
	public $stats;
	private $start;
	public $end;
	private $ready;

	public function __construct($file){
		global $settings;

		$this->file = $file;
		$this->stats = [
			"api" => 0,
			"sql" => 0,
			"duration" => 0
		];
		$settings['stats'] = $this->stats;
		$this->start = new DateTime(date("Y-m-d H:i:s"));
		$this->isDebug();
		$this->check();
		$this->log($this->start->format('d-m-Y H:i:s') . " - Iniciando log\r\n");
		$this->clear();
	}

	public function __destruct(){
		if(!isset($this->end)){
			$this->end = new DateTime(date("Y-m-d H:i:s"));
		}
		$this->log($this->end->format('d-m-Y H:i:s') . " - Fechando log\r\n");
	}

	public function bye($message){
		echo $this->log($message);
		exit();
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

	public function setStats($type){
		global $settings;
		if($type=="duration"){
			$this->end = new DateTime(date("Y-m-d H:i:s"));
			$this->stats["duration"] = $this->end->getTimestamp() - $this->start->getTimestamp();
		}else{
			$this->stats[$type]++;
		}
		$settings['stats'] = $this->stats;
	}

}

?>
