<?php

/*
		***THIS FILE INCLUDES CODE FROM MEDIAWIKI API DEMOS THAT ARE
		LICENSED UNDER MIT LICENSE***
*/

require_once("common.php");
require_once("Curl.php");
require_once("debug.php");
require_once("log.php");
require_once("stats.php");

// Classe da API
class api extends common {

	private 	$cookies;
	private 	$editChecksum;
	public 		$log;
	public 		$maxlag;
	private 	$revids;
	public		$stats;
	public 		$url;

	public function __construct($url,$maxlag,$log,$stats){
		$this->url = $url;
		$this->maxlag = $maxlag;
		$this->stats = $stats;
		$this->log = $log;
		$this->setCookieFile();
		$this->isDebug();
		$this->startEditChecksum();
	}

	public function __destruct(){

		$this->unsetCookieFile();

	}

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

	public function antispoof($account,$ignore){

		// Troca temporariamente a URL da API
		$old = $this->url;
		$this->change("https://meta.wikimedia.org/w/api.php");

		$params = [
			"action" => "antispoof",
			"username" => $account
		];

		// Faz consulta a API
		$result = $this->request($params);
		//Retorna
		$this->change($old);

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

	public function bye($message){
		echo $this->log->log($message);
		exit();
	}

	public function change( $url ){
		$this->url = $url;
	}

	public function compareEditChecksum( $page, $content ){

		if(is_array($page)){

			$title		= $page['title'];
			$section	= $page['section'];

		}else{

			$title 		= $page;
			$section	= 'entire';

		}

		if(!isset($this->editChecksum[$title][$section])){

			return FALSE;

		}

		$contentChecksum = sha1($content);

		if($this->editChecksum[$title][$section]===$contentChecksum){

			return TRUE;

		}else{

			return FALSE;

		}

	}

	public function continuousRequest($params){

		$content = array();

		$result = $this->request($params);

		$dynamicKey = array_keys($result['query'])[0];

		foreach($result['query'][$dynamicKey] as $key => $value){
			$content[] = $value;
		}

		while(isset($result['continue'])){

			$dynamicKey = array_keys($result['continue'])[0];

			$params[$dynamicKey] = $result['continue'][$dynamicKey];

			$result = $this->request($params);

			$dynamicKey = array_keys($result['query'])[0];

			foreach($result['query'][$dynamicKey] as $key => $value){
				$content[] = $value;
			}

		}

		return $content;

	}

	private function doPostCurl($params){

		$this->stats->increaseStats("api");

		return Curl::doPostCookies( $this->url , $params , $this->cookies );

	}
	
	public function getActiveUsers(){
	
		$params = [
			"action"		=> "query",
			"list"			=> "allusers",
			"auactiveusers" => TRUE,
			"aulimit" 		=> "500"
		];

		$result = $this->continuousRequest($params);
		
		foreach($result as $key => $value){
			
			$users[] = $value['name'];
			
		}
				
		return $users;
	
	}

	public function getContent($page,$mode) {

		// $mode = 1 para o script, invocando $this->bye; $mode = 0 não para, retornando 0

		$params = [
	    	"action"	=> "query",
	    	"prop"		=> "revisions",
	    	"rvlimit"	=> "1",
	    	"rvprop"	=> "content|ids",
	    	"rvslots"	=> "main",
	    	"titles"	=> $page
		];

		$result = $this->request($params);

		$pageId = array_keys($result['query']['pages'])[0];

		if($pageId!=-1){

			$this->revids[$page] = $result['query']['pages'][$pageId]['revisions']['0']['revid'];

			$content = $result['query']['pages'][$pageId]['revisions']['0']['slots']['main']['*'];

			$this->updateEditChecksum( $page , $content );

			return $content;

		}else{

			if($mode==0){

				return 0;

			}elseif($mode==1){

				$this->bye("Página solicitada (" . $page . ") em modo ativo não existe. Fechando...\r\n");

			}else{

				$this->bye("Modo desconhecido para getContent (" . $mode . "). Verifique seu script. Fechando...\r\n");

			}

		}

	}

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

			$this->updateEditChecksum( $result['query']['pages'][$key]['title'] , $result['query']['pages'][$key]['revisions']['0']['slots']['main']['*'] );

		}

		return $content;
	}
	
	public function getUsersByGroups( $groups ) {
	
		$params = [
			"action"	=> "query",
			"list"		=> "allusers",
			"augroup" 	=> $groups,
			"aulimit" 	=> "500"
		];	

		$result = $this->continuousRequest($params);
		
		foreach($result as $key => $value){
			
			$users[] = $value['name'];
			
		}
		
		return $users;
	
	}

	public function getSectionContent($page,$section) {

		$params = [
			"action" => "parse",
			"page" => $page,
			"prop" => "wikitext|revid",
			"section" => $section
		];

		$result = $this->request($params);

		$this->revids[$page] = $result['parse']['revid'];

		$content = $result['parse']['wikitext']['*'];

		$this->updateEditChecksum(array('title'	=>	$page , 'section'	=>	$section), $content);

		return $content;

	}

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

	public function getRevids() {
		return $this->revids;
	}

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

	protected function isDebug(){

		if(!isset($this->debug)){
			$this->debug = debug::isDebug();

			if($this->debug==TRUE){
				$this->maxlag += $this->maxlag;
			}

		}

		return $this->debug;

	}

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

	public function linksToPage($page,$namespace){

		$pages = array();

		$params = [
		  'action' 			=> 'query',
		  'list' 			=> 'backlinks',
		  'bltitle' 		=> $page,
		  'bllimit' 		=> '500',
		];

		if($namespace!=NULL){
			$params['blnamespace'] = $namespace;
		}

		$result = $this->continuousRequest($params);

		foreach($result as $key => $value){
			$pages[] = $value['title'];
		}

		return $pages;

	}

	public function pagesFromCategory($category,$namespace){

		$pages = array();

		$params = [
		  'action' 			=> 'query',
		  'list' 			=> 'categorymembers',
		  'cmtitle' 		=> $category,
		  'cmlimit' 		=> '500',
		];

		if($namespace!=NULL){
			$params['cmnamespace'] = $namespace;
		}

		$result = $this->continuousRequest($params);

		foreach($result as $key => $value){
			$pages[] = $value['title'];
		}

		return $pages;

	}

	public function request($params){

		$try = 1;

		$params["maxlag"] = $this->maxlag;

		if(!isset($params["format"])){ //Definir como padrão após transição
			$params["format"] = "json";
		}

		$result = $this->doPostCurl($params);

		while(isset($result["error"]["lag"])){
			echo $this->log->log("PROBLEMA: " . $try . "/3 maxlag excedido, limite: " . $this->maxlag . "; valor atual: " . number_format($result["error"]["lag"],2) . ".\r\n");

			if($try===3){
				$this->bye("Maxlag continua excedido após 3 tentativas. Fechando...\r\n");
			}

			sleep(5);

			$result = $this->doPostCurl($params);

			$try++;

		}

		return $result;

	}

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

	private function startEditChecksum(){

		$this->editChecksum = array();

	}

	private function setCookieFile(){

		$this->cookies = "/tmp/stangbots_cookie_" . rand() . ".inc";

	}

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

	private function unsetCookieFile(){

		if(file_exists($this->cookies)){

			unlink($this->cookies);
			
		}

	}

	private function updateEditChecksum( $page, $content ){

		if(is_array($page)){

			$this->editChecksum[$page['title']][$page['section']] =	sha1($content);

		}else{

			$this->editChecksum[$page]['entire'] =	sha1($content);

		}

	}

}

?>
