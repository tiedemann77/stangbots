<?php

// Basic info
require_once("includes/globals.php");

// Basic functions
require_once(__DIR__ . "/../common.php");

// Starting log
echo logging($logdate . "
Starting script 3...\r\n");

// Project
$endPoint = "https://www.wikidata.org/w/api.php";

// On/Off check
checkPower();

// END OF THE BASIC
//--------------------------------------------------------------------
// STARTING SCRIPT

// Definitions
$page = "Wikidata:Sandbox";

$template = "{{sandbox heading}}
<!--Test your edits below this line-->";

// Time from last edit in hours, less than 24
$time = 3;

// Getting time and content from last edit
$params = [
  "action" => "query",
  "prop" => "revisions",
  "titles" => $page,
  "rvprop" => "timestamp|content",
  "rvlimit" => "1",
  "rvslots" => "main",
  "format" => "json"
];

$result = APIrequest($endPoint, $params);

// If for some reason the $page was deleted, stop
if(isset($result['query']['pages']['-1'])){
  exit(logging("The page " . $page . " does not exist. Maybe was deleted? Closing...\r\n"));
}

// Formating time and content
foreach ($result['query']['pages'] as $key => $value) {
  $lastedit = $result['query']['pages'][$key]['revisions']['0']['timestamp'];
  $content = $result['query']['pages'][$key]['revisions']['0']['slots']['main']['*'];
}

// Checking time diff between last edit and now
$timenow = new DateTime($logdate);
$lastedit = new DateTime($lastedit);

$timediff = $lastedit->diff($timenow);

$hours = $timediff->h;
$days = $timediff->d;

// If last edit is recent, less than $time
if($hours<$time&&$days==0){
  exit(logging("Last edit is recent. Closing...\r\n"));
}

// If the page is already empty
if($content==$template){
  exit(logging("Page already empty. Closing...\r\n"));
}

// If don't stop yet, let's edit
// Login step 1
$login_Token = getLoginToken();

// Login step 2
loginRequest( $login_Token );

// Edit token
$csrf_Token = getCSRFToken();

// Editing
editRequest($csrf_Token, $page, $template, "[[WD:Bot|bot]]: cleaning sandbox", 1, 1);

// Logout
logoutRequest( $csrf_Token );

// Closing log
echo logging("Script 3 done!\r\n");

?>
