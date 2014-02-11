<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once('common.php');

$datestampforquery = getDatestampForQueryingOai();

if($interactive!='n')
  echo "<p>MyOPIA is retrieving data " . ($datestampforquery==''?'for all time - no earliest limit':"from $datestampforquery onwards") . ".</p>";
else
{
  @header('Content-Type: text/plain');
  echo "\n\n-------------------------------\n" .  date('d/m/Y H:i:s') . "
MyOPIA OAI import retrieving data " . ($datestampforquery==''?'for all time - no earliest limit':"from $datestampforquery onwards")
. "\n";
}


$depttranslate = $_REQUEST['depttranslate'];

switch($_REQUEST['action2'])
{
  case 'resume':
    resumeGetRecentRecords($_REQUEST['token']);
    break;
  default:
    getRecentRecords();
    break;
}



?>