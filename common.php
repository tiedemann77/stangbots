<?php

/*
		***THIS FILE INCLUDES CODE FROM MEDIAWIKI API DEMOS THAT ARE
		LICENSED UNDER MIT LICENSE***

    COLEÇÃO DE FUNÇÕES BÁSICAS COMPARTILHADAS
    POR TODOS OS ROBÔS
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
		exit("Arquivo de log não existe. Script interrompido. Por favor, crie '" . $logfile . "' para prosseguir. Fechando...\r\n");
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
function replicaQuery($project, $query, $param){

	global $toolforge;

	if($toolforge===0){
		exit(logging("O script requer acesso ao replicas mas não parece estar rodando no Toolforge, verifique. Fechando...\r\n"));
	}

	$ts_pw = posix_getpwuid(posix_getuid());
	$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/replica.my.cnf");
	$mysqli = new mysqli($project . '.analytics.db.svc.wikimedia.cloud', $ts_mycnf['user'], $ts_mycnf['password'], $project . '_p');
	unset($ts_mycnf, $ts_pw);

	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('s', $param);
	$stmt->execute();
	$result = $stmt->get_result();
	return $result->fetch_all(MYSQLI_BOTH);
}

 ?>
