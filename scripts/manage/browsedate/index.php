<?php

require_once(dirname(dirname(__FILE__)) . '/common.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/query/formataref.php');

$start = intval($_REQUEST['start']);
$period = intval($_REQUEST['period']);

// Reset time period choices if not completely specified
if(!($start>0))
  $start = time();
if(!($period>0))
  $period = 86400 * 7; // Week-by-week is the default period


// Output the navigation buttons
$future = $start + $period;
$past   = $start - $period;
$pastpast   = $start - $period - $period;
$dateformatter = 'D jS M Y';

$navbuttons = "<p class='simplepaddedmessagebox'>";
$navbuttons .= "Displaying: <strong>".date($dateformatter, $past)."</strong> to <strong>".date($dateformatter, $start) . "</strong>";
$navbuttons .= "&nbsp;&middot;&nbsp;<a href='./?action=browsedate&start=$past&period=$period'>[Earlier" /* . ": ".date($dateformatter, $pastpast)." to ".date($dateformatter, $past)*/ . "]</a>";
if(($start+1000) < time())
  $navbuttons .= "&nbsp;&middot;&nbsp;<a href='./?action=browsedate&start=$future&period=$period'>[Later" . /* ": ".date($dateformatter, $start)." to ".date($dateformatter, $future) . */ "]</a></p>";
echo $navbuttons;

// Build the query
$q = "SELECT *, UNIX_TIMESTAMP(timestamp) AS utstamp FROM PUBLICATIONS WHERE ((timestamp<FROM_UNIXTIME($start)) AND (timestamp>FROM_UNIXTIME($past)))";
// Add the user's deptid stuff into the query, if they aren't a global admin
 if(!isGlobalAdmin($_SERVER['REMOTE_USER'])){
  $q .= " AND (0 ";
  foreach(splitDeptListString(getAuthorisations($_SERVER['REMOTE_USER'])) as $dcode){
    $q .= " OR deptlist LIKE '%,".mysql_real_escape_string($dcode).",%' ";
  }
  $q .= ") ";
}
$q .= " ORDER BY timestamp DESC";


$res = mysql_query($q, connectpubsdb());
if(!$res){
  ?><p>Error while querying database.</p><?php
}elseif(mysql_num_rows($res)==0){
  ?><p>No records found in this date range.</p><?php
}else{
  ?>
  <p><?php echo mysql_num_rows($res) ?> publication records <em><strong>entered or modified</strong></em> during the requested date range:</p>
  <ul><?php
  $showopts = 'edit,detail';
  while($row = mysql_fetch_assoc($res)){
    echo "\n  <li style='margin-bottom: 4px;'>"
           .formataref2($row, $_SERVER['REMOTE_USER'])
		 ."<br /><em>&nbsp;&nbsp;&nbsp;Created/modified: "
		 .date($dateformatter.', G:i', $row['utstamp']) 
		 .". Originator: ";
    $userinfo = getUserInfo($row['originator']);
    echo "<a href=\"mailto:$userinfo[email]\">$userinfo[firstname] $userinfo[lastname]</a>";
    echo "</em></li>";
  }
  ?></ul><?php
  echo $navbuttons;
}


?>