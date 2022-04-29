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

	public function changeStatement( $id , $value , $summary ){

		if($this->isDebug()){
			echo $this->log->log("Edição: {$this->username} editou o claim {$id} ({$summary});\r\n");
			return;
		}

		$params = [
			'action' 		=> 'wbsetclaimvalue',
			'token'			=> $this->getTokens('csrf'),
			'claim' 		=> $id,
			'snaktype' 	=> 'value',
			'value' 		=> $value,
			'summary'		=> $summary
		];

		$this->api->request($params);

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

	public function createStatement( $item , $propertie , $value , $qualifier , $reference, $summary, $bot ){

		if($this->isDebug()){
			echo $this->log->log("Edição: {$this->username} editou {$item}, adicionando {$propertie} ({$summary});\r\n");
			return;
		}

		$params = [
			'action' 		=> 'wbcreateclaim',
			'token'			=> $this->getTokens('csrf'),
			'entity' 		=> $item,
			'property'	=> $propertie,
			'snaktype' 	=> 'value',
			'value' 		=> $value,
			'summary'		=> $summary,
		];

		if($bot==1){
			$params["bot"] = "1";
		}

		$result =  $this->api->request($params);

		if($result['success']==1){
			$this->createStatementReference( $result['claim']['id'] , $reference , $bot );

			if($qualifier!=NULL){
				$this->createStatementQualifier( $result['claim']['id'] , $qualifier , $bot );
			}
		}

	}

	public function createStatementReference( $id , $reference, $bot ){

		$params = [
			'action' 		=> 'wbsetreference',
			'token'			=> $this->getTokens('csrf'),
			'statement' => $id,
			'snaks'			=> $reference,
			'summary'		=> '[[WD:BOT|bot]]: adding reference',
		];

		if($bot==1){
			$params["bot"] = "1";
		}

		return $this->api->request($params);

	}

	public function createStatementQualifier( $id , $qualifier , $bot ){

		$params = [
			'action' 		=> 'wbsetqualifier',
			'token'			=> $this->getTokens('csrf'),
			'claim' 		=> $id,
			'snaktype'	=> 'value',
			'property'	=> $qualifier[0],
			'value'			=> $qualifier[1],
			'summary'		=> '[[WD:BOT|bot]]: adding qualifier'
		];

		if($bot==1){
			$params["bot"] = "1";
		}

		return $this->api->request($params);

	}

	private function doEdit($type,$target,$text,$summary,$minor,$bot){

		if($this->isDebug()){
			echo $this->log->log("Edição: " . $this->username . " editou " . $target[0] . ($type=="section" ? " (seção " . $target[1] . ")" : "") . " (" . $summary . ") Edição menor: " . $minor . "; Robô: " . $bot . ". Conteúdo salvo no log;\r\n");
			$this->log->log("Conteúdo:\r\n" . $text . "\r\n");
			return;
		}

		$params = [
			"action"	=> "edit",
			"title"		=> $target[0],
			"text"		=> $text,
			"summary" => $summary,
			"token" 	=> $this->getTokens('csrf')
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

	private function getTokens($type){

		if(isset($this->tokens[$type])){
			return $this->tokens[$type];
		}

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

		return $this->tokens[$type];

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

			$params = [
				"action" 	=> "logout",
				"token" 	=> $this->getTokens('csrf')
			];

			$this->api->request($params);

		}
	}

	public function rollback($page,$user,$summary,$bot){

		if($this->isDebug()){
			echo $this->log->log("Reversão: " . $this->username . " reverteu " . $user . " em " . $page . " (" . $summary . ") Robô: " . $bot . ";\r\n");
			return;
		}

		$params = [
			"action" 	=> "rollback",
			"title" 	=> $page,
			"user" 		=> $user,
			"summary" => $summary,
			"token" 	=> $this->getTokens('rollback')
		];

		if($bot==1){
			$params["markbot"] = "1";
		}

		$result = $this->api->request($params);

		if(isset($result['error'])){
			$this->bye("Erro ao reverter '" . $user . "' em '" . $page . "': " . $result['error']['code'] . ". Fechando...\r\n");
		}

	}

}

?>
