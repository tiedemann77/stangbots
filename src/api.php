<?php

/*
		***THIS FILE INCLUDES CODE FROM MEDIAWIKI API DEMOS THAT ARE
		LICENSED UNDER MIT LICENSE***
*/

require_once("common.php");
require_once("log.php");

// Classe da API
class api extends common{

	public $url;
	public $maxlag;
	private $cookies;
	private $revids;
	public $log;

	public function __construct($url,$maxlag,$log){
		$this->url = $url;
		$this->maxlag = $maxlag;
		$this->cookies = "/tmp/stangbots_cookie_" . rand() . ".inc";
		$this->log = $log;
		$this->isDebug();
	}

	public function __destruct(){
		if(file_exists($this->cookies)){
			unlink($this->cookies);
		}
	}

	public function bye($message){
		echo $this->log->log($message);
		exit();
	}

	protected function isDebug(){

		if(!isset($this->debug)){
			$this->debug = debug::isDebug();

			if($this->debug==TRUE){
				$this->maxlag += $this->maxlag;
			}

		}

		return $this->debug;

	}

	public function request($params){

		$try = 1;

		$params["maxlag"] = $this->maxlag;

		if(!isset($params["format"])){ //Definir como padrão após transição
			$params["format"] = "json";
		}

		$result = $this->doCurl($params);

		while(isset($result["error"]["lag"])){
			echo $this->log->log("PROBLEMA: " . $try . "/3 maxlag excedido, limite: " . $this->maxlag . "; valor atual: " . number_format($result["error"]["lag"],2) . ".\r\n");

			if($try===3){
				$this->bye("Maxlag continua excedido após 3 tentativas. Fechando...\r\n");
			}

			sleep(5);

			$result = $this->doCurl($params);

			$try++;

		}

		return $result;

	}

	private function doCurl($params){
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($ch, CURLOPT_USERAGENT, "A bot by User Stanglavine");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookies);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookies);

		$result = json_decode(curl_exec($ch),true);
		curl_close($ch);
		$this->log->setStats("api");
		return $result;
	}

	public function getRevids() {
		return $this->revids;
	}

	// Função para obter o conteúdo de qualquer página com dois modos:
	// 0 não para se inexistente; 1 para o script;
	public function getContent($page,$mode) {

		$params = [
	    "action" => "query",
	    "prop" => "revisions",
	    "titles" => $page,
	    "rvprop" => "content|ids",
	    "rvlimit" => "1",
	    "rvslots" => "main"
		];

		// Requisita o conteúdo a API
		$result = $this->request($params);

	  // Verifica o resultado
	  foreach ($result['query']['pages'] as $key => $value) {

	  	// Verifica se a página existe
	  	if(isset($result['query']['pages'][$key]['revisions'])){

				$this->revids[$page] = $result['query']['pages'][$key]['revisions']['0']['revid'];

	    	// Retorna o conteúdo
	    	return $result['query']['pages'][$key]['revisions']['0']['slots']['main']['*'];

	  	}else{
				// Se a página não existe, verifica o modo
				// Se 0, retorna 0;
				if($mode==0){
					return 0;
				}elseif($mode==1){
					// Se 1, para o script;
					$this->bye("Página solicitada (" . $page . ") em modo ativo não existe. Fechando...\r\n");
				}else{
					// Indefinido
					$this->bye("Modo desconhecido para getContent (" . $mode . "). Verifique seu script. Fechando...\r\n");
				}
			}
		}
	}

	// Função para obter o conteúdo de múltiplas páginas
	public function getMultipleContent($pages) {

		foreach ($pages as $key => $value) {
			if($key===0){
				$titles = $pages[$key];
			}else{
				$titles .= "|" . $pages[$key];
			}
		}

		$params = [
			"action" => "query",
			"prop" => "revisions",
			"titles" => $titles,
			"rvprop" => "content|ids",
			"rvslots" => "main"
		];

		$result = $this->request($params);

		if(isset($result['query']['pages'][-1])){
			$this->bye("Uma (ou mais) das páginas solicitadas em getMultipleContent não existe. Fechando...\r\n");
		}

		foreach ($result['query']['pages'] as $key => $value) {

			$this->revids[$result['query']['pages'][$key]['title']] = $result['query']['pages'][$key]['revisions']['0']['revid'];

			$content[$result['query']['pages'][$key]['title']] = $result['query']['pages'][$key]['revisions']['0']['slots']['main']['*'];
		}

		return $content;
	}

	// Função para obter a lista de seções de uma página
	public function getSectionList($page) {

		$params = [
	    "action" => "parse",
	    "page" => $page,
	    "prop" => "sections"
		];

		$result = $this->request($params);

		foreach ($result['parse']['sections'] as $key => $value) {
			$sectionList[$key] = $value['line'];
		}

		if(!isset($sectionList)){
			return 0;
		}

		return $sectionList;
	}

	// Função para obter o conteúdo de uma determinada seção em uma página
	public function getSectionContent($page,$section) {

		$params = [
			"action" => "parse",
			"page" => $page,
			"prop" => "wikitext|revid",
			"section" => $section
		];

		$result = $this->request($params);

		$this->revids[$page] = $result['parse']['revid'];

		$result = $result['parse']['wikitext']['*'];

		return $result;

	}

	// Função para verificar se uma conta global existe
	public function accountExist($account){

		$params = [
			"action" => "query",
			"meta" => "globaluserinfo",
			"guiuser" => $account
		];

		$result = $this->request($params);

		if(isset($result['query']['globaluserinfo']['name'])){
			return 1;
		}

		// Padrão
		return 0;

	}

	// Função para verificar se um usuário teve bloqueios no passado
	public function hasBlocks($account){

		$params = [
			"action" => "query",
			"list" => "logevents",
			"letype" => "block",
			"letitle" => "User:" . $account
		];

		$result = $this->request($params);

		if(isset($result['query']['logevents'][0])){
			$hasblocks = 1;
		}else{
			$hasblocks = 0;
		}

		return $hasblocks;

	}

	// Função para verificar antispoof, retorna 0 ou o primeiro nome caso dispare
	public function antispoof($account,$ignore){

		// Troca temporariamente a URL da API
		$old = $this->url;
		$this->url = "https://meta.wikimedia.org/w/api.php";

		$params = [
			"action" => "antispoof",
			"username" => $account
		];

		// Faz consulta a API
		$result = $this->request($params);
		//Retorna
		$this->url = $old;

		if($result['antispoof']['result']=="pass"){
			$antispoof = 0;
		}else{
			$antispoof = $result['antispoof']['users'][0];
			if($antispoof==$ignore){
				if(isset($result['antispoof']['users'][1])){
					$antispoof = $result['antispoof']['users'][1];
				}else {
					$antispoof = 0;
				}
			}
		}

		return $antispoof;

	}

	// Função para detectar o destino de redirects
	public function resolveRedir($page){

		$params = [
			'action'		=> 'query',
			'titles'		=> $page,
			'redirects'	=> 'true'
		];

		$result = $this->request($params);

		if(isset($result['query']['redirects'])){
			$last = end($result['query']['redirects']);
			$page = $last['to'];
		}

		return $page;

	}

	// Transclusões
	public function transclusions($pages){

		$params = [
			'action'		=> 'query',
			'prop'			=> 'transcludedin',
			'titles'		=> $pages,
			'tilimit'		=> '500'
		];

		$result = $this->request($params);

		$result = $result['query']['pages'];

		foreach ($result as $key => $value) {

			$transclusions[$result[$key]['title']] = array();

			foreach ($result[$key]['transcludedin'] as $key2 => $value2) {
				$transclusions[$result[$key]['title']][] = $value2['title'];
			}

		}

		return $transclusions;

	}

	// Links na(s) página(s)
	public function linksOnPage($pages){

		$params = [
			'action'	=> 'query',
			'titles'	=> $pages,
			'prop'		=> 'links',
			'pllimit'	=> '500'
		];

		$result = $this->request($params);

		$result = $result['query']['pages'];

		foreach ($result as $key => $value) {

			$links[$value['title']] = array();

			foreach ($value['links'] as $key2 => $value2) {
				$links[$value['title']][] = $value2['title'];
			}

		}

		return $links;

	}

}

?>
