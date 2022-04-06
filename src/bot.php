<?php

/*
		***THIS FILE INCLUDES CODE FROM MEDIAWIKI API DEMOS THAT ARE
		LICENSED UNDER MIT LICENSE***
*/

require_once("api.php");
require_once("common.php");
require_once("debug.php");
require_once("log.php");
require_once("stats.php");
require_once("toolforgeSQL.php");

// Classe do robô
class bot extends common{

	public 	$api;
	private $credentials;
	public 	$log;
	private $login;
	public 	$power;
	public 	$script;
	public 	$sql;
	public 	$stats;
	private $tokens;
	public 	$username;

	public function __construct(){
		global $settings;

		if(!isset($settings)){
			exit("Não foram encontradas configurações para o robô. Fechando...\r\n");
		}
		$this->username = $settings['username'];
		$this->credentials = $settings['credentials'];
		$this->power = $settings['power'];
		$this->script = $settings['script'];
		$this->stats = new stats();
		$this->log = new log($settings['file'],$this->stats);
		$this->api = new api($settings['url'], $settings['maxlag'], $this->log, $this->stats);
		$this->isDebug();
		$this->sql = new toolforgeSQL($settings['replicasDB'], $settings['personalDB'], $this->log, $this->stats);
		$this->login = FALSE;
		$this->checkPower();
	}

	public function bye($message){
		echo $this->log->log($message);
		$this->logout();
		$this->sql->updateStats($this->username, $this->script);
		exit();
	}

	private function checkPower(){

		if($this->isDebug()){
			return;
		}

		$content = $this->api->getContent($this->power,1);

		if($content!="run"){
			$this->bye("Bot desligado em " . $this->power . ", verifique. Fechando...\r\n");
		}

	}

	private function doEdit($type,$target,$text,$summary,$minor,$bot){

		if($this->isDebug()){
			echo $this->log->log("Edição: " . $this->username . " editou " . $target[0] . ($type=="section" ? " (seção " . $target[1] . ")" : "") . " (" . $summary . ") Edição menor: " . $minor . "; Robô: " . $bot . ". Conteúdo salvo no log;\r\n");
			$this->log->log("Conteúdo:\r\n" . $text . "\r\n");
			return;
		}

		if(!isset($this->tokens['csrf'])){
			$this->getTokens();
		}

		$params = [
			"action" => "edit",
			"title" => $target[0],
			"text" => $text,
			"summary" => $summary,
			"token" => $this->tokens['csrf']
		];

		if($type=="section"){
			$params["section"] = $target[1];
		}

		if($minor==1){
			$params["minor"] = "1";
		}

		if($bot==1){
			$params["bot"] = "1";
		}

		$revids = $this->api->getRevids();
		if(isset($revids[$target[0]])){
			$params["baserevid"] = $revids[$target[0]];
		}

		$result = $this->api->request($params);

		if(isset($result['error'])){
			$this->bye("Erro ao editar '" . $target[0] . "': " . $result['error']['code'] . ". Fechando...\r\n");
		}

	}

	public function edit($page, $text, $summary, $minor, $bot){

		$target = array($page);

		$this->doEdit("entire", $target, $text, $summary, $minor, $bot);

	}

	public function editSection($page,$section,$text,$summary,$minor,$bot){

		$target = array($page,$section);

		$this->doEdit("section", $target, $text, $summary, $minor, $bot);

	}

	private function getTokens(){
		if($this->login===FALSE){
			$this->login();
		}

		$params = [
			"action" => "query",
			"meta" => "tokens",
			"type" => "csrf|deleteglobalaccount|patrol|rollback|setglobalaccountstatus|userrights|watch"
		];

		$result = $this->api->request($params);

		$this->tokens['csrf'] = $result['query']['tokens']['csrftoken'];
		$this->tokens['deleteglobalaccount'] = $result['query']['tokens']['deleteglobalaccounttoken'];
		$this->tokens['patrol'] = $result['query']['tokens']['patroltoken'];
		$this->tokens['rollback'] = $result['query']['tokens']['rollbacktoken'];
		$this->tokens['setglobalaccountstatus'] = $result['query']['tokens']['setglobalaccountstatustoken'];
		$this->tokens['userrights'] = $result['query']['tokens']['userrightstoken'];
		$this->tokens['watch'] = $result['query']['tokens']['watchtoken'];

	}

	protected function isDebug(){

		if(!isset($this->debug)){

			$this->debug = debug::isDebug();

			if($this->debug==TRUE){
				$this->script .= '_teste';
			}

		}

		return $this->debug;

	}

	public function login(){
		// Estapa 1
		$params = [
			"action" => "query",
			"meta" => "tokens",
			"type" => "login"
		];

		$result = $this->api->request($params);
		unset($params);

		// Etapa 2
		$params = [
			"action" => "login",
			"lgname" => $this->credentials[0],
			"lgpassword" => $this->credentials[1],
			"lgtoken" => $result["query"]["tokens"]["logintoken"]
		];

		$result = $this->api->request($params);
		if($result['login']['result']==="Success"){
			$this->login = TRUE;
		}else{
			$this->bye("Erro durante o login. Razão: '" . $result['login']['reason'] . "'. Fechando...\r\n");
		}

	}

	public function logout(){
		if($this->login===TRUE){
			if(!isset($this->tokens['csrf'])){
				$this->getTokens();
			}
			$params = [
				"action" => "logout",
				"token" => $this->tokens['csrf']
			];
			$this->api->request($params);
		}
	}

}

?>
