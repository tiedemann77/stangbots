<?php

/*
		***THIS FILE INCLUDES CODE FROM MEDIAWIKI API DEMOS THAT ARE
		LICENSED UNDER MIT LICENSE***
*/

require_once("common.php");
require_once("debug.php");
require_once("log.php");

// Toolforge database
class toolforgeSQL extends common{

	private $replicasDB;
	private $replicasConnection;
	private $replicasStatus;
	private $personalDB;
	private $personalConnection;
	private $personalStatus;
	public $log;

	public function __construct($replicasDB,$personalDB,$log){

		$this->log = $log;

		if($replicasDB!=NULL){
			$this->replicasDB = $replicasDB;
		}
		if($personalDB!=NULL){
			$this->personalDB = $personalDB;
		}

		$this->replicasStatus = FALSE;
		$this->personalStatus = FALSE;
		$this->isDebug();
		$this->check();

	}

	public function bye($message){
		echo $this->log->log($message);
		exit();
	}

	protected function isDebug(){

		if(!isset($this->debug)){
			$this->debug = debug::isDebug();
		}

		return $this->debug;

	}

	private function check(){

		$ts_pw = posix_getpwuid(posix_getuid());
		if(!file_exists($ts_pw['dir'] . "/replica.my.cnf")){
			echo $this->log->log("Erro de conexão com a base de dados do Toolforge: arquivo de configuração não existe. \r\n");
		}else{
			$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/replica.my.cnf");
			if(!isset($ts_mycnf['user'])||!isset($ts_mycnf['password'])){
				echo $this->log->log("Erro de conexão com a base de dados do Toolforge: arquivo de configuração ilegível. \r\n");
			}else{
				if(isset($this->replicasDB)){
					$this->checkReplicas($ts_mycnf['user'],$ts_mycnf['password']);
				}
				if(isset($this->personalDB)){
					$this->checkPersonal($ts_mycnf['user'],$ts_mycnf['password']);
				}
			}
		}

	}

	private function checkReplicas($user,$pass){

		$this->replicasConnection = new mysqli($this->replicasDB . '.analytics.db.svc.wikimedia.cloud', $user, $pass, $this->replicasDB . '_p');
		if($this->replicasConnection->connect_error){
			echo $this->log->log("Erro de conexão com a base de dados do Toolforge: " . $this->replicasConnection->connect_error . "\r\n");
		}else{
			$this->replicasStatus = TRUE;
		}

	}

	private function checkPersonal($user,$pass){

		$this->personalConnection = new mysqli('tools.db.svc.wikimedia.cloud', $user, $pass, $this->personalDB);
		if($this->personalConnection->connect_error){
			echo $this->log->log("Erro de conexão com a base de dados do Toolforge: " . $this->personalConnection->connect_error . "\r\n");
		}else{
			$this->personalStatus = TRUE;
		}

	}

	public function getReplicasStatus(){
		return $this->replicasStatus;
	}

	public function getPersonalStatus(){
		return $this->personalStatus;
	}

	public function replicasQuery($query,$params){

		if($this->replicasStatus){
			$stmt = $this->replicasConnection->prepare($query);
			return $this->query($stmt,$params);
		}else{
			return 0;
		}

	}

	public function personalQuery($query,$params){

		if($this->personalStatus){
			$stmt = $this->personalConnection->prepare($query);
			return $this->query($stmt,$params);
		}else{
			return 0;
		}

	}

	private function query($stmt,$params){
		if(is_array($params)){
			$types = "";
			foreach ($params as $key => $value) {
				$types .= $params[$key][0];
			}
			$count = count($params);
			switch ($count) {
				case 1:
					$stmt->bind_param($types, $params[0][1]);
					break;
				case 2:
					$stmt->bind_param($types, $params[0][1], $params[1][1]);
					break;
				case 3:
					$stmt->bind_param($types, $params[0][1], $params[1][1], $params[2][1]);
					break;
				case 4:
					$stmt->bind_param($types, $params[0][1], $params[1][1], $params[2][1], $params[3][1]);
					break;
				case 5:
					$stmt->bind_param($types, $params[0][1], $params[1][1], $params[2][1], $params[3][1], $params[4][1]);
					break;
				case 6:
					$stmt->bind_param($types, $params[0][1], $params[1][1], $params[2][1], $params[3][1], $params[4][1], $params[5][1]);
					break;
				case 7:
					$stmt->bind_param($types, $params[0][1], $params[1][1], $params[2][1], $params[3][1], $params[4][1], $params[5][1], $params[6][1]);
					break;
				case 8:
					$stmt->bind_param($types, $params[0][1], $params[1][1], $params[2][1], $params[3][1], $params[4][1], $params[5][1], $params[6][1], $params[7][1]);
					break;
				case 9:
					$stmt->bind_param($types, $params[0][1], $params[1][1], $params[2][1], $params[3][1], $params[4][1], $params[5][1], $params[6][1], $params[7][1], $params[8][1]);
					break;
				case 10:
					$stmt->bind_param($types, $params[0][1], $params[1][1], $params[2][1], $params[3][1], $params[4][1], $params[5][1], $params[6][1], $params[7][1], $params[8][1], $params[9][1]);
					break;
				default:
					$this->bye("Mais de dez parâmetros para consulta SQL. O limite máximo é dez. Fechando...\r\n");
					break;
			}
		}
		$stmt->execute();
		$this->log->setStats("sql");
		$result = $stmt->get_result();
		if(gettype($result)!="boolean"){
			return $result->fetch_all(MYSQLI_BOTH);
		}
	}

	public function updateStats($bot,$script){

		if($this->isDebug()){
			return;
		}

		if($this->personalStatus===TRUE){
			global $settings;
			global $manualRun;
			if($manualRun===TRUE){
				$manual = 0;
			}else{
				$manual = 1;
			}
			$query = "SELECT * FROM stats WHERE bot = '$bot' AND script_name = '$script'";
			$result = $this->personalQuery($query,$params=NULL);
			$api = $settings["stats"]["api"];
			$sql = $settings["stats"]["sql"]+1;
			$this->log->setStats("duration");
			$duration = $settings["stats"]["duration"];
			$last = $this->log->end->format('d-m-Y H:i:s');
			$memory = number_format(((memory_get_peak_usage()/1024)/1024),2,".",",");
			if(isset($result[0])){
				$query = "UPDATE stats SET api_requests = $api, sql_requests = $sql, duration = $duration, last = '$last', do_manual = $manual, memory = '$memory' WHERE bot = '$bot' AND script_name = '$script';";
			}else{
				$query = "INSERT INTO stats (bot, api_requests, sql_requests, duration, last, script_name, do_manual, memory) VALUES ('$bot', $api, $sql, $duration, '$last', '$script', 1, '$memory');";
			}
			$this->personalQuery($query,$params=NULL);
		}
	}

}

?>
