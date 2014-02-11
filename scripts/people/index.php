<?php

if(!isset($userid))
  $userid = $_REQUEST['userid'];

if(strlen($userid)>0)
{
  $users = array($userid);
 // include_once('/nfs/rcs/www/webdocs/lifesciences-faculty/publications/query/index.php');
  require_once(dirname(dirname(dirname(__FILE__))) . '/query/index.php');
}
else
{
// include_once('/nfs/rcs/www/webdocs/lifesciences-faculty/publications/manage/common.php');
require_once(dirname(dirname(__FILE__)) . '/common.php');

$x = "SELECT userlist FROM PUBLICATIONS";
$res = mysql_query($x, connectpubsdb());
$users = array();
while($row = mysql_fetch_assoc($res))
{
  $temp = explode(',',$row['userlist']);
  foreach($temp as $v)
    if(strlen(trim($v))>5)
      $users[trim($v)]++;
}
$x = "SELECT firstname, lastname, userid FROM USERS ORDER BY lastname, firstname";
$res = mysql_query($x, connectpubsdb());
$usernames = array();
while($row = mysql_fetch_assoc($res))
{
  $usernames[trim($row['userid'])]=$row;
}
$output = array();
$output2 = array();
foreach($usernames as $k=>$v)
{
  if($users[$k]>0)
    $output[$usernames[$k]['lastname'] . $usernames[$k]['firstname'] . $k] = 
           "\n<li><a href=\"./?userid=$k\">" . $usernames[$k]['firstname']
		  ." <b>" . $usernames[$k]['lastname'] ."</b></a> (" . intval($users[$k])
		  . " publication" . ($users[$k]!=1?'s':'') . " listed)"
		  . "<a href=\"../personal/?userid=$k\">.</a></li>";
/*  else
    $output2[$usernames[$k]['lastname'] . $usernames[$k]['firstname'] . $k] =
            "\n<li><a href=\"../personal/?userid=$k\">" . $usernames[$k]['firstname']
		  ." <b>" . $usernames[$k]['lastname'] ."</b></a></li>";
*/
}
// ksort($output);
echo "\n<ul>";
foreach($output as $v)
  echo $v;
echo "\n</ul>";
/*
echo "<h2>Users with no publications connected:</h2>";
echo "\n<ul>";
foreach($output2 as $v)
  echo $v;
echo "\n</ul>";
*/

} // End of userid-isn't-set
?>
