<?php

/*
		***THIS FILE INCLUDES CODE FROM MEDIAWIKI API DEMOS THAT ARE
		LICENSED UNDER MIT LICENSE***

    COLEÇÃO DE FUNÇÕES BÁSICAS COMPARTILHADAS
    POR TODOS OS ROBÔS
*/

// Regex comuns
// Possibilidades de {{respondido}}
$closedRegex = "/(\{\{((R|r)esp){1,1}(ondid(o|a)(2){0,1}){0,1}\|.{1,}\|)|(A discussão a seguir está marcada como '{0,3}respondida'{0,3})/";

// Linhas em branco
$blanklineRegex = "/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/";

// Comentários HTML
$htmlcommentRegex = "/\<\!\-\-(?:.|\n|\r)*?-->/";

/*
INICIANDO CONVERSÃO PARA POO (TEMPORÁRIO)
*/

// Classe do robô
class bot{

	public $username;
	private $credentials;
	public $power;
	public $script;
	public $log;
	public $api;
	public $sql;
	private $CSRFToken;

	public function __construct(){
		global $settings;

		if(!isset($settings)){
			exit("Não foram encontradas configurações para o robô. Fechando...\r\n");
		}
		$this->username = $settings['username'];
		$this->credentials = $settings['credentials'];
		$this->power = $settings['power'];
		$this->script = $settings['script'];
		$this->log = new log($settings['file']);
		$this->api = new api($settings['url'], $settings['maxlag'], $this->log);
		$this->checkPower();
		$this->sql = new toolforgeSQL($settings['replicasDB'], $settings['personalDB'], $this->log);
	}

	// Destruidor, logout caso precise
	public function __destruct(){

		if(isset($this->CSRFToken)){

			$params = [
				"action" => "logout",
				"token" => $this->CSRFToken,
				"format" => "json"
			];

			$ch = curl_init();

			curl_setopt( $ch, CURLOPT_URL, $this->api->url );
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params ) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_COOKIEJAR, "/tmp/cookie.inc" );
			curl_setopt( $ch, CURLOPT_COOKIEFILE, "/tmp/cookie.inc" );

			curl_exec( $ch );
			curl_close( $ch );

		}

	}

	private function getCSRFToken(){

			$params = [
				"action" => "query",
				"meta" => "tokens",
				"type" => "login",
				"format" => "json"
			];

			$url = $this->api->url . "?" . http_build_query( $params );

			$ch = curl_init( $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_COOKIEJAR, "/tmp/cookie.inc" );
			curl_setopt( $ch, CURLOPT_COOKIEFILE, "/tmp/cookie.inc" );

			$output = curl_exec( $ch );
			curl_close( $ch );
			unset($params);

			$result = json_decode( $output, true );
			$logintoken = $result["query"]["tokens"]["logintoken"];
			unset($result);

			$params = [
				"action" => "login",
				"lgname" => $this->credentials[0],
				"lgpassword" => $this->credentials[1],
				"lgtoken" => $logintoken,
				"format" => "json"
			];

			$ch = curl_init();

			curl_setopt( $ch, CURLOPT_URL, $this->api->url );
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params ) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_COOKIEJAR, "/tmp/cookie.inc" );
			curl_setopt( $ch, CURLOPT_COOKIEFILE, "/tmp/cookie.inc" );

			curl_exec( $ch );
			curl_close( $ch );
			unset($params);

			$params = [
				"action" => "query",
				"meta" => "tokens",
				"format" => "json"
			];

			$url = $this->api->url . "?" . http_build_query( $params );

			$ch = curl_init( $url );

			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_COOKIEJAR, "/tmp/cookie.inc" );
			curl_setopt( $ch, CURLOPT_COOKIEFILE, "/tmp/cookie.inc" );

			$output = curl_exec( $ch );
			curl_close( $ch );

			$result = json_decode( $output, true );
			$this->CSRFToken = $result["query"]["tokens"]["csrftoken"];

		}

		public function edit($page, $text, $summary, $minor, $bot){

			if(!isset($this->CSRFToken)){
				$this->getCSRFToken();
			}

			$params = [
				"action" => "edit",
				"title" => $page,
				"text" => $text,
		    "summary" => $summary,
				"token" => $this->CSRFToken,
				"format" => "json"
			];

			if($minor==1){
				$params["minor"] = "1";
			}

			if($bot==1){
				$params["bot"] = "1";
			}

			$ch = curl_init();

			curl_setopt( $ch, CURLOPT_URL, $this->api->url );
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params ) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_COOKIEJAR, "/tmp/cookie.inc" );
			curl_setopt( $ch, CURLOPT_COOKIEFILE, "/tmp/cookie.inc" );

			curl_exec( $ch );
			curl_close( $ch );

	}

	private function checkPower(){

		$content = $this->api->getContent($this->power,1);

		if($content!="run"){
			exit($this->log->log("Bot desligado em " . $this->power . ", verifique. Fechando...\r\n"));
		}

	}

	public function bye($message){
		$this->log->log($message);
		$this->sql->updateStats($this->username, $this->script);
		exit($message);
	}

}

// Classe da API
class api{

	public $url;
	public $maxlag;
	public $log;

	public function __construct($url,$maxlag,$log){
		$this->url = $url;
		$this->maxlag = $maxlag;
		$this->log = $log;
		$this->checkLag();
	}

	private function checkLag(){

		$params = [
		  "action" => "query",
		  "titles" => "MainPage",
		  "format" => "json",
		  "maxlag" => $this->maxlag
		];

		$result = $this->request($params);

		if(isset($result["error"]["lag"])){
			exit($this->log->log("Maxlag excedido, limite: " . $this->maxlag . "; valor atual: " . $result["error"]["lag"] . ". Fechando...\r\n"));
		}

	}

	public function request($params){

		$url = $this->url . "?" . http_build_query( $params );

	  $ch = curl_init( $url );
	  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	  $output = curl_exec( $ch );
	  curl_close( $ch );
		$this->log->setStats("api");
	  return json_decode( $output, true );

	}

	// Função para obter o conteúdo de qualquer página com três modos:
	// 0 não para se inexistente; 1 para o script;
	public function getContent($page,$mode) {

		$params = [
	    "action" => "query",
	    "prop" => "revisions",
	    "titles" => $page,
	    "rvprop" => "content",
	    "rvlimit" => "1",
	    "rvslots" => "main",
	    "format" => "json"
		];

		// Requisita o conteúdo a API
		$result = $this->request($params);

	  // Verifica o resultado
	  foreach ($result['query']['pages'] as $key => $value) {

	  	// Verifica se a página existe
	  	if(isset($result['query']['pages'][$key]['revisions'])){

	    	// Retorna o conteúdo
	    	return $result['query']['pages'][$key]['revisions']['0']['slots']['main']['*'];

	  	}else{
				// Se a página não existe, verifica o modo
				// Se 0, retorna 0;
				if($mode==0){
					return 0;
				}elseif($mode==1){
					// Se 1, para o script;
					exit($this->log->log("Página solicitada (" . $page . ") em modo ativo não existe. Fechando...\r\n"));
				}else{
					// Indefinido
					exit($this->log->log("Modo desconhecido para getContent (" . $mode . "). Verifique seu script. Fechando...\r\n"));
				}
			}
		}
	}

	// Função para obter a lista de seções de uma página
	public function getSectionList($page) {

		$params = [
	    "action" => "parse",
	    "page" => $page,
	    "prop" => "sections",
	    "format" => "json"
		];

		$result = $this->request($params);

		foreach ($result['parse']['sections'] as $key => $value) {
			$sectionList[$key] = $value['line'];
		}

		return $sectionList;
	}

	// Função para obter o conteúdo de uma determinada seção em uma página
	public function getSectionContent($page,$section) {

		$params = [
			"action" => "parse",
			"page" => $page,
			"prop" => "wikitext",
			"section" => $section,
			"format" => "json"
		];

		$result = $this->request($params);

		$result = $result['parse']['wikitext']['*'];

		return $result;

	}

	// Função para verificar se uma conta global existe
	public function accountExist($account){

		$params = [
			"action" => "query",
			"meta" => "globaluserinfo",
			"guiuser" => $account,
			"format" => "json"
		];

		$result = $this->request($params);

		if(isset($result['query']['globaluserinfo']['missing'])){
			$exist = 0;
		}else{
			$exist = 1;
		}

		return $exist;

	}

	// Função para verificar se um usuário teve bloqueios no passado
	public function hasBlocks($account){

		$params = [
			"action" => "query",
			"list" => "logevents",
			"letype" => "block",
			"letitle" => "User:" . $account,
			"format" => "json"
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

		$params = [
			"action" => "antispoof",
			"username" => $account,
			"format" => "json"
		];

		// Faz consulta a API
		$result = $this->request($params);

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

		// Obtém o conteúdo da página alvo
		$content = $this->getContent($page, 1);

		//Controle para o loop
		$x = 0;

		// Verifica se a página é um redirect em loop, para evitar duplos, triplos redirects
		while($x===0) {

			if(preg_match("/(#(R|r)(E|e)(D|d)(I|i)(R|r)(E|e)(C|c)){1,1}[a-zA-Z]{0,9} {0,}\[\[/", $content)){

				// Se for, detecta a página alvo
				$page = preg_replace("/(#(R|r)(E|e)(D|d)(I|i)(R|r)(E|e)(C|c)){1,1}[a-zA-Z]{0,9} {0,}\[\[/", "", $content);
				$page = preg_replace("/\]\].*/","",$page);

				// Remove possíveis links para seções com "#"
				$explode = explode("#",$page);
				$page = $explode[0];

				// Obtém o conteúdo da nova página alvo
				$content = $this->getContent($page, 1);

				// Reinicia para testar se não é um redirect de novo

			}else{

				// Se não for um redirect, para o loop e prossegue
				$x = 1;

			}

		}

		// Retorna o título da página alvo
		return $page;

	}

}

// Log
class log{

	public $file;
	public $stats;
	private $start;
	public $end;

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
		$this->log($this->start->format('c') . " - Iniciando log\r\n");
	}

	public function __destruct(){
		if(!isset($this->end)){
			$this->end = new DateTime(date("Y-m-d H:i:s"));
		}
		$this->log($this->end->format('c') . " - Fechando log\r\n");
	}

	public function log($msg){

		if(!file_exists($this->file)){
			if(!fopen($this->file, 'w')){
				exit("Não foi possível criar o arquivo de log especificado. Script interrompido. Por favor, crie o arquivo manualmente para prosseguir. Fechando...\r\n");
			}
		}

		file_put_contents($this->file, $msg, FILE_APPEND);

		return $msg;

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

// Toolforge database
class toolforgeSQL{

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

		$this->check();

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
			$type = "";
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
					exit($this->log->log("Mais de dez parâmetros para consulta SQL. O limite máximo é dez. Fechando...\r\n"));
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
		global $settings;
		if($this->personalStatus===TRUE){
			$api = $settings["api"];
			$sql = $settings["sql"]+2;
			$duration = $settings["duration"];
			$this->log->setStats("duration");
			$last = $this->log->end->format('c');
			$query = "SELECT * FROM stats WHERE bot = '$bot' AND script_name = '$script'";
			$result = $this->personalQuery($query,$params=NULL);
			if(isset($result[0])){
				$query = "UPDATE stats SET api_requests = $api, sql_requests = $sql, duration = $duration, last = '$last' WHERE bot = '$bot' AND script_name = '$script';";
			}else{
				$query = "INSERT INTO stats (bot, api_requests, sql_requests, duration, last, script_name) VALUES ('$bot', $api, $sql, $duration, '$last', '$script');";
			}
			$result = $this->personalQuery($query,$params=NULL);
		}
	}

}

/*
FIM DA CONVERSÃO PARA POO (TEMPORÁRIO)
*/

// Login step 1: GET request to fetch login token
function getLoginToken() {
	global $endPoint;

	$params1 = [
		"action" => "query",
		"meta" => "tokens",
		"type" => "login",
		"format" => "json"
	];

	$url = $endPoint . "?" . http_build_query( $params1 );

	$ch = curl_init( $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_COOKIEJAR, "/tmp/cookie.inc" );
	curl_setopt( $ch, CURLOPT_COOKIEFILE, "/tmp/cookie.inc" );

	$output = curl_exec( $ch );
	curl_close( $ch );

	$result = json_decode( $output, true );
	return $result["query"]["tokens"]["logintoken"];
}

// Login step 2: POST request to log in
function loginRequest( $logintoken ) {
	global $endPoint;
  global $username;
  global $password;

	$params2 = [
		"action" => "login",
		"lgname" => $username,
		"lgpassword" => $password,
		"lgtoken" => $logintoken,
		"format" => "json"
	];

	$ch = curl_init();

	curl_setopt( $ch, CURLOPT_URL, $endPoint );
	curl_setopt( $ch, CURLOPT_POST, true );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params2 ) );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_COOKIEJAR, "/tmp/cookie.inc" );
	curl_setopt( $ch, CURLOPT_COOKIEFILE, "/tmp/cookie.inc" );

	$output = curl_exec( $ch );
	curl_close( $ch );

	//echo( $output );
}

// Login step 3: GET request to fetch CSRF token
function getCSRFToken() {
	global $endPoint;

	$params3 = [
		"action" => "query",
		"meta" => "tokens",
		"format" => "json"
	];

	$url = $endPoint . "?" . http_build_query( $params3 );

	$ch = curl_init( $url );

	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_COOKIEJAR, "/tmp/cookie.inc" );
	curl_setopt( $ch, CURLOPT_COOKIEFILE, "/tmp/cookie.inc" );

	$output = curl_exec( $ch );
	curl_close( $ch );

	$result = json_decode( $output, true );
	return $result["query"]["tokens"]["csrftoken"];
}

// Step 4: POST request to logout
function logoutRequest( $csrftoken ) {
	global $endPoint;

	$params4 = [
		"action" => "logout",
		"token" => $csrftoken,
		"format" => "json"
	];

	$ch = curl_init();

	curl_setopt( $ch, CURLOPT_URL, $endPoint );
	curl_setopt( $ch, CURLOPT_POST, true );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params4 ) );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_COOKIEJAR, "/tmp/cookie.inc" );
	curl_setopt( $ch, CURLOPT_COOKIEFILE, "/tmp/cookie.inc" );

	$output = curl_exec( $ch );
	curl_close( $ch );

}

// Função para criar logs
function logging($logmsg){

	global $logfile;

	// Requer o arquivo dos logs
	if(!file_exists($logfile)){
		if(!fopen($logfile, 'w')){
			exit("Não foi possível criar o arquivo de log especificado. Script interrompido. Por favor, crie o arquivo manualmente para prosseguir. Fechando...\r\n");
		}
	}

	file_put_contents($logfile, $logmsg, FILE_APPEND);

	return $logmsg;

}

// Verifica se o bot está on
function checkPower(){

	global $powerPage;

	$content = getContent($powerPage, 2);

	if($content=="run"){
		// Não faz nada, bot está on
	}elseif($content=="stop"){
	  exit(logging("Bot está desligado em " . $powerPage . ". Fechando...\r\n"));
	}else{
		exit(logging("Conteúdo estranho em " . $powerPage . ", verifique. Fechando...\r\n"));
	}

}

// Função para requisições simples a API com cURL
function APIrequest($endPoint, $params){

	$url = $endPoint . "?" . http_build_query( $params );

  $ch = curl_init( $url );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
  $output = curl_exec( $ch );
  curl_close( $ch );

  $result = json_decode( $output, true );

	return $result;
}

// Função para obter o conteúdo de qualquer página com três modos:
// 0 não para se inexistente; 1 para o script; 2 mesmo que o 1 + maxlag test
function getContent( $page, $mode) {
	global $endPoint;

	global $maxlag;

	$params = [
    "action" => "query",
    "prop" => "revisions",
    "titles" => $page,
    "rvprop" => "content",
    "rvlimit" => "1",
    "rvslots" => "main",
    "format" => "json"
	];

	// Adiciona teste de maxlag se mode=2
	if($mode==2){
		$params["maxlag"] = $maxlag;
	}

	// Requisita o conteúdo a API
	$result = APIrequest($endPoint, $params);

	// Verifica se há um erro de maxlag
	if(isset($result["error"]["lag"])){
		exit(logging("Maxlag excedido, limite: " . $maxlag . "; valor atual: " . $result["error"]["lag"] . ". Fechando...\r\n"));
	}

  // Obtém o ID da página
  foreach ($result['query']['pages'] as $key => $value) {
      $pageID = $key;
  }

  // Verifica se a página existe
  if(isset($result['query']['pages'][$pageID]['revisions'])){

    // Salvando conteúdo
    $content = $result['query']['pages'][$pageID]['revisions']['0']['slots']['main']['*'];

    // Retorna o conteúdo
    return $content;
  }else{
		// Se a página não existe, verifica o modo
		// Se 0, retorna 0;
		if($mode==0){
			return 0;
		}elseif ($mode==1||$mode==2) {
			// Se 1 ou 2, para o script;
			exit(logging("Página solicitada (" . $page . ") em modo ativo não existe. Fechando...\r\n"));
		}else{
			// Indefinido
			exit(logging("Modo desconhecido para getContent (" . $mode . "). Verifique seu script. Fechando...\r\n"));
		}
	}
}

// Função para editar páginas, substituindo o conteúdo
function editRequest( $csrftoken, $page, $text, $summary, $minor, $bot ) {
	global $endPoint;

	$params = [
		"action" => "edit",
		"title" => $page,
		"text" => $text,
    "summary" => $summary,
		"token" => $csrftoken,
		"format" => "json"
	];

	if($minor==1){
		$params["minor"] = "1";
	}

	if($bot==1){
		$params["bot"] = "1";
	}

	$ch = curl_init();

	curl_setopt( $ch, CURLOPT_URL, $endPoint );
	curl_setopt( $ch, CURLOPT_POST, true );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params ) );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_COOKIEJAR, "/tmp/cookie.inc" );
	curl_setopt( $ch, CURLOPT_COOKIEFILE, "/tmp/cookie.inc" );

	$output = curl_exec( $ch );
	curl_close( $ch );

}

// Função para converter meses em números para por extenso
function monthstoPT($month){
  switch ($month) {
    case "01":
      $month = "janeiro";
      break;
    case "02":
      $month = "fevereiro";
      break;
    case "03":
      $month = "março";
      break;
    case "04":
      $month = "abril";
      break;
    case "05":
      $month = "maio";
      break;
    case "06":
      $month = "junho";
      break;
    case "07":
      $month = "julho";
      break;
    case "08":
      $month = "agosto";
      break;
    case "09":
      $month = "setembro";
      break;
    case "10":
      $month = "outubro";
      break;
    case "11":
      $month = "novembro";
      break;
    case "12":
      $month = "dezembro";
      break;
    default:
      $month = "Error!";
  }

  return $month;
}

// Função para converter meses por extenso para números
function monthsfromPT($month){
  switch ($month) {
    case "janeiro":
      $month = "01";
      break;
    case "fevereiro":
      $month = "02";
      break;
    case "março":
      $month = "03";
      break;
    case "abril":
      $month = "04";
      break;
    case "maio":
      $month = "05";
      break;
    case "junho":
      $month = "06";
      break;
    case "julho":
      $month = "07";
      break;
    case "agosto":
      $month = "08";
      break;
    case "setembro":
      $month = "09";
      break;
    case "outubro":
      $month = "10";
      break;
    case "novembro":
      $month = "11";
      break;
    case "dezembro":
      $month = "12";
      break;
    default:
      $month = "Error!";
  }

  return $month;
}

// Função para obter a lista de seções de uma página
function getSectionList( $page ) {

	global $endPoint;

	$params = [
    "action" => "parse",
    "page" => $page,
    "prop" => "sections",
    "format" => "json"
	];

	// Faz consulta a API
	$result = APIrequest($endPoint, $params);

	foreach ($result['parse']['sections'] as $key => $value) {

		$sectionList[$key] = $value['line'];

	}

	return $sectionList;
}

// Função para obter o conteúdo de uma determinada seção em uma página
function getSectionContent( $page, $section ) {

	global $endPoint;

	$params = [
		"action" => "parse",
		"page" => $page,
		"prop" => "wikitext",
		"section" => $section,
		"format" => "json"
	];

	// Faz consulta a API
	$result = APIrequest($endPoint, $params);

	$result = $result['parse']['wikitext']['*'];

	return $result;

}

// Função para verificar se uma conta global existe
function accountExist($account){

	global $endPoint;

	$params = [
		"action" => "query",
		"meta" => "globaluserinfo",
		"guiuser" => $account,
		"format" => "json"
	];

	// Faz consulta a API
	$result = APIrequest($endPoint, $params);

	if(isset($result['query']['globaluserinfo']['missing'])){
		$exist = 0;
	}else{
		$exist = 1;
	}

	return $exist;

}

// Função para verificar se um usuário teve bloqueios no passado
function hasBlocks($account){

	global $endPoint;

	$params = [
		"action" => "query",
		"list" => "logevents",
		"letype" => "block",
		"letitle" => "User:" . $account,
		"format" => "json"
	];

	// Faz consulta a API
	$result = APIrequest($endPoint, $params);

	if(isset($result['query']['logevents'][0])){
		$hasblocks = 1;
	}else{
		$hasblocks = 0;
	}

	return $hasblocks;

}

// Função para verificar antispoof, retorna 0 ou o primeiro nome caso dispare
function antispoof($account,$ignore){

	// Antispoof precisa ser checado no meta
	$endPoint = "https://meta.wikimedia.org/w/api.php";

	$params = [
		"action" => "antispoof",
		"username" => $account,
		"format" => "json"
	];

	// Faz consulta a API
	$result = APIrequest($endPoint, $params);

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
function resolveRedir($page){

	// Obtém o conteúdo da página alvo
	$content = getContent($page, 1);

	//Controle para o loop
	$x = 0;

	// Verifica se a página é um redirect em loop, para evitar duplos, triplos redirects
	while($x == "0") {

		if(preg_match("/(#(R|r)(E|e)(D|d)(I|i)(R|r)(E|e)(C|c)){1,1}[a-zA-Z]{0,9} {0,}\[\[/", $content)){

			// Se for, detecta a página alvo
			$page = preg_replace("/(#(R|r)(E|e)(D|d)(I|i)(R|r)(E|e)(C|c)){1,1}[a-zA-Z]{0,9} {0,}\[\[/", "", $content);
			$page = preg_replace("/\]\].*/","",$page);

			// Remove possíveis links para seções com "#"
			$explode = explode("#",$page);
			$page = $explode[0];

			// Obtém o conteúdo da nova página alvo
			$content = getContent($page, 1);

			// Reinicia para testar se não é um redirect de novo

		}else{

			// Se não for um redirect, para o loop e prossegue
			$x = 1;

		}

		// Retorna o título da página alvo
		return $page;

	}
}

// Função para fazer consultas nas réplicas no Toolforge
function replicaQuery($project, $query, $param, $hasParam){

	global $toolforge;

	if($toolforge===1){

		$ts_pw = posix_getpwuid(posix_getuid());
		$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/replica.my.cnf");
		$mysqli = new mysqli($project . '.analytics.db.svc.wikimedia.cloud', $ts_mycnf['user'], $ts_mycnf['password'], $project . '_p');
		unset($ts_mycnf, $ts_pw);

		$stmt = $mysqli->prepare($query);
		if($hasParam!=0){
			$stmt->bind_param('s', $param);
		}
		$stmt->execute();
		$result = $stmt->get_result();
		return $result->fetch_all(MYSQLI_BOTH);

	}else{
		return 0;
	}

}

 ?>
