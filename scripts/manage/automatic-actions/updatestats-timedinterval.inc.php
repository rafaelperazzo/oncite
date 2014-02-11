<?php
//error_reporting(E_ALL ^ E_NOTICE);
require_once(dirname(dirname(__FILE__)). '/common.php');
//@header("Content-type: text/plain");

$verbose = false;

$res=mysql_query("SELECT UNIX_TIMESTAMP(ts) as 'then', UNIX_TIMESTAMP() as 'now' "
                  . " FROM PUBLICATIONSTIMESTAMPS WHERE name='statsupdated'", connectpubsdb());
if(!$res)
  return;

if(mysql_num_rows($res) > 0)
{
  $row = mysql_fetch_assoc($res);
  $diff = (intval($row['now']) - intval($row['then']));
  if(($diff - 86400)>0)
  {
    if($verbose) echo "Running stats update, since last time it was run was $row[then], cf now=$row[now]\n";
    require_once('updatestats.inc.php');
//    mysql_query("UPDATE PUBLICATIONSTIMESTAMPS SET ts=NOW() WHERE name='statsupdated'", connectpubsdb());
  }
  else
  {
    if($verbose) echo "NOT running stats update, since last time it was run was $row[then]\n";
  }
}
else
{
  if($verbose) echo "Timestamp not found - running stats update and creating timestamp\n";
  require_once('updatestats.inc.php');
//  mysql_query("INSERT INTO PUBLICATIONSTIMESTAMPS SET ts=NOW(), name='statsupdated'", connectpubsdb());
}





?>