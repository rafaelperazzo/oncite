<?php
header("Content-type: text/plain");
require_once('common.php');

// We NEED to allow anonymous access in order to let the cron job store the list
//if(!isGlobalAdmin($REMOTE_USER))
//  die("You are not recognised as a sitewide administrator - access to this list is denied.");

$ads = getPubsAdmins();

$ret = array();
foreach($ads as $a)
{
  $addr = trim(strlen($a['email'])>0?$a['email']:$a['userid'].$config['emaildomain']);
  $ret[] = ((!$justemails) && (strlen($a['firstname'].$a['lastname'])>0)?
              "\"$a[firstname] $a[lastname]\" <$addr>"
			                 : $addr ). "\n";
}
echo implode('', $ret);

?>