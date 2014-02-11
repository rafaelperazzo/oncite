<?php

/*

Automatic actions: Update statistics table

Simply looks at every publication in turn and tallies up the year/dept/reftype info

It should be called every 24 hours or so, I'd guess

*/

require_once(dirname(dirname(__FILE__)) . '/common.php');
//error_reporting(E_ALL ^ E_NOTICE);
//@header("Content-type: text/plain");

$ret = "updatestats.inc.php executed at timestamp " . time() . ":\n";

// OLD: This "approvedby" clause can be dropped when the pendingdepts method is firmly established
$q = "SELECT year, deptlist, reftype FROM PUBLICATIONS WHERE (NOT(approvedby='')) ORDER BY pubid";

$tallies = array(); // [year][dept][reftype]

$res = mysql_query($q, connectpubsdb());

while($row = mysql_fetch_assoc($res))
{
  $deptlist = splitDeptListString($row['deptlist']);
  foreach($deptlist as $d)
    if(trim($d)!='')
	  $tallies[$row['year']][trim($d)][$row['reftype']]++;
}

mysql_query("DELETE FROM STATSSUBTOTALS", connectpubsdb());
foreach($tallies as $year=>$stuff)
{
  foreach($stuff as $deptid=>$stuff2)
  {
    foreach($stuff2 as $reftype=>$count)
	{
//	  echo "Year $year, dept $deptid, type $reftype: $count\n";
	  $q = "INSERT INTO STATSSUBTOTALS SET deptid='" . mysql_real_escape_string($deptid) 
	                         . "', year='" . mysql_real_escape_string($year) 
	                         . "', reftype='" . mysql_real_escape_string($reftype) 
	                         . "', count='" . mysql_real_escape_string($count) 
	                         . "'";
      mysql_query($q, connectpubsdb());
	}
  }
}
storeDatestampForStatsUpdate();


function storeDatestampForStatsUpdate()
{
  $q = "SELECT UNIX_TIMESTAMP(ts) AS uts FROM PUBLICATIONSTIMESTAMPS WHERE name='statsupdated' LIMIT 1";
  $res = mysql_query($q, connectpubsdb());
  if(mysql_num_rows($res)==0)
    $q = "INSERT INTO PUBLICATIONSTIMESTAMPS SET name='statsupdated', ts=NOW()";
  else
    $q = "UPDATE PUBLICATIONSTIMESTAMPS SET ts=NOW() WHERE name='statsupdated' LIMIT 1";
  $res = mysql_query($q, connectpubsdb());
}


?>