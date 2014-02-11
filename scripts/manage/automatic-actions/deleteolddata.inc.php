<?php

/*

Automatic actions: Delete old data

This clears out old transaction data 
(entries in PUBLICATIONSTRANS older than 4 months)
and email data
(entries in PUBLICATIONSEMAILSSENT older than 12 months)

It should be called fairly rarely - e.g. every 2 weeks

*/

require_once(dirname(dirname(__FILE__)) . '/common.php');
error_reporting(E_ALL ^ E_NOTICE);

$ret = "deleteolddata.inc.php executed at timestamp " . time() . ":\n";

// First PUBLICATIONSTRANS
$res = mysql_query("DELETE FROM PUBLICATIONSTRANS "
   . " WHERE (timestamp < DATE_SUB(NOW(), INTERVAL '4' MONTH))", connectpubsdb());
$ret .= "  Delete old transactions: ";
if($res)
  $ret .= mysql_affected_rows() . " rows affected.\n";
else
  $ret .= "ERROR: " . mysql_error() . "\n";

// Then PUBLICATIONSEMAILSSENT
$res = mysql_query("DELETE FROM PUBLICATIONSEMAILSSENT "
   . " WHERE (tstamp < DATE_SUB(NOW(), INTERVAL '12' MONTH))", connectpubsdb());
$ret .= "  Delete old emails: ";
if($res)
  $ret .= mysql_affected_rows() . " rows affected.\n";
else
  $ret .= "ERROR: " . mysql_error() . "\n";


print($ret);
return $ret . "\n";

?>