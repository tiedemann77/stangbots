<?php

// Settings
require_once(__DIR__ . "/settings.php");

// Basic functions
require_once(__DIR__ . "/../../autoloader.php");

// Settings
$settings = [
  'credentials' => $uspw,
  'username' => "Stangbot",
  'power' => "User:Stangbot/Power",
  'script' => "Script 3",
  'url' => "https://www.wikidata.org/w/api.php",
  'maxlag' => 4,
  'file' => __DIR__ .  "/log.log",
  'replicasDB' => "wikidatawiki",
  'personalDB' => "s54852__stangbots"
];

//Creating bot
$robot = new Bot();

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
  "rvslots" => "main"
];

$result = $robot->api->request($params);

// If for some reason the $page was deleted, stop
if(isset($result['query']['pages']['-1'])){
  $robot->bye("The page " . $page . " does not exist. Maybe was deleted? Closing...\r\n");
}

// Formating time and content
foreach ($result['query']['pages'] as $key => $value) {
  $lastedit = $result['query']['pages'][$key]['revisions']['0']['timestamp'];
  $content = $result['query']['pages'][$key]['revisions']['0']['slots']['main']['*'];
}

// If the page is already empty
if($content==$template){
  $robot->bye("Page already empty. Closing...\r\n");
}

// Checking time diff between last edit and now
$timenow = new DateTime(date("Y-m-d H:i:s"));
$lastedit = new DateTime($lastedit);

$timediff = $lastedit->diff($timenow);

$hours = $timediff->h;
$days = $timediff->d;

// If last edit is recent, less than $time
if($hours<$time&&$days==0){
  $robot->bye("Last edit is recent. Closing...\r\n");
}

// If don't stop yet, let's edit
// Editing
$robot->edit($page, $template, "[[WD:Bot|bot]]: cleaning sandbox", 1, 1);

// Closing log
$robot->bye($robot->script . " done!\r\n");

?>
