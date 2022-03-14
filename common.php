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

// Classe do robô
class bot{

	public $username;
	private $credentials;
	public $power;
	public $script;
	public $log;
	public $api;
	public $sql;
	private $tokens;
	public $login;

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
		$this->login = FALSE;
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

	public function edit($page, $text, $summary, $minor, $bot){

		$target = array($page);

		$this->doEdit("entire", $target, $text, $summary, $minor, $bot);

	}

	public function editSection($page,$section,$text,$summary,$minor,$bot){

		$target = array($page,$section);

		$this->doEdit("section", $target, $text, $summary, $minor, $bot);

	}

	private function doEdit($type,$target,$text,$summary,$minor,$bot){

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

	private function checkPower(){

		$content = $this->api->getContent($this->power,1);

		if($content!="run"){
			exit($this->log->log("Bot desligado em " . $this->power . ", verifique. Fechando...\r\n"));
		}

	}

	public function bye($message){
		$this->log->log($message);
		$this->logout();
		$this->sql->updateStats($this->username, $this->script);
		exit($message);
	}

}

// Classe da API
class api{

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
	}

	public function __destruct(){
		unlink($this->cookies);
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
				exit($this->log->log("Maxlag continua excedido após 3 tentativas. Fechando...\r\n"));
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
					exit($this->log->log("Página solicitada (" . $page . ") em modo ativo não existe. Fechando...\r\n"));
				}else{
					// Indefinido
					exit($this->log->log("Modo desconhecido para getContent (" . $mode . "). Verifique seu script. Fechando...\r\n"));
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
			exit($this->log->log("Uma (ou mais) das páginas solicitadas em getMultipleContent não existe. Fechando...\r\n"));
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

	public function log($msg){
		file_put_contents($this->file, $msg, FILE_APPEND);
		return $msg;
	}

	private function check(){
		if(!file_exists($this->file)){
			if(!fopen($this->file, 'w')){
				exit("Não foi possível criar o arquivo de log especificado. Provavelmente um erro de permissão ou o diretório não existe. Script interrompido. Por favor, crie o arquivo manualmente para prosseguir. Fechando...\r\n");
			}
		}
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

/*
FUNÇÕES AINDA A CONVERTER PARA POO
*/

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

 ?>
