<?php

require_once(dirname(dirname(__FILE__)) . '/common.php');

$depts = getalldepts(); // $deptid => DEPTS table data (inc myopiastatus)

// Now run through the user's departments, and strip out the ones that they aren't allowed to administer
if(!isGlobalAdmin($_SERVER['REMOTE_USER'])){
  $mydcodes = splitDeptListString(getAuthorisations($_SERVER['REMOTE_USER']));
  foreach($depts as $deptid => $deptdata){
    if(array_search($deptid, $mydcodes)===false){
      unset($depts[$deptid]);
    }
  }
}


// If data submitted, then let's process it
$modified = array(); // Will store the list of modified ones, for alerting the administrator
foreach($depts as $dcode => $ddata){
  if(isset($_REQUEST['myopiastatus'][$dcode])){

    $q = "SELECT NAME, myopiastatus FROM DEPTS WHERE DEPTID='".mysql_real_escape_string($dcode)
                ."' LIMIT 1";
    $res = mysql_query($q, connectpubsdb());
    if($res && ($deptdetails = mysql_fetch_assoc($res))){
      if($_REQUEST['myopiastatus'][$dcode] != $deptdetails['myopiastatus']){
	   // We now know that the status has CHANGED, so add it to the list
	   $modified[$dcode] = "$deptdetails[NAME] ($dcode):\nStatus was: $deptdetails[myopiastatus]\nNew status: ".$_REQUEST['myopiastatus'][$dcode];
	 }
    }

    //echo "<p>Setting status: $dcode => ".$_REQUEST['myopiastatus'][$dcode]."</p>";
    $q = "UPDATE DEPTS SET myopiastatus='".mysql_real_escape_string($_REQUEST['myopiastatus'][$dcode])
                ."' WHERE DEPTID='".mysql_real_escape_string($dcode)
                ."' LIMIT 1";
    if($res = mysql_query($q, connectpubsdb())){
      $depts[$dcode]['myopiastatus'] = $_REQUEST['myopiastatus'][$dcode];
    }else{
      echo "<p>Database error while attempting to store status of department $dcode.</p>";
    }
  }
}
if(sizeof($_REQUEST['myopiastatus'])>0){
  ?><p class="simplepaddedmessagebox"><strong><em>Stored status information.</em></strong></p><?php
}
if(sizeof($modified)>0 && strlen($config['statusnotifyaddress'])>0){
  // Send an email to the relevant administrator
  $mailsubject = "[MyOPIA] Departmental status notification";
  $mailmsg = "Hello,

This message is to inform you that a MyOPIA administrator (userid $_SERVER[REMOTE_USER])
has altered the departmental status flag for one or more of their departments.
Details are as follows:\n\n";
  
  $mailmsg .= implode("\n\n", $modified);

  $mailmsg .="

To view the departmental statuses visit
$config[pageshomeurl]manage/?action=deptstatus

-----------------------------------------------------------------
Sent automatically by the online publications database. 
To redirect these messages please contact a system administrator.
";

  mail($config['statusnotifyaddress'], $mailsubject, $mailmsg);
///  echo "<pre>Sent mail to $config[statusnotifyaddress]:\n\n$mailsubject\n\n$mailmsg</pre>";
}




?>
<form action="./" method="post">
<input type="hidden" name="action" value="deptstatus" />
<ul>
<?php

// Output the info for the relevant departments
foreach($depts as $dcode => $ddata){
  echo "\n   <li>Department: $ddata[NAME]<br />Status: <select name='myopiastatus[$dcode]'>";
  echo "<option value='notuptodate'".($ddata['myopiastatus']=='notuptodate'?' selected="selected"':'')
             .">Not up-to-date for the latest round</option>";
  echo "<option value='entering'".($ddata['myopiastatus']=='entering'?' selected="selected"':'')
             .">Currently entering data</option>";
  echo "<option value='uptodate'".($ddata['myopiastatus']=='uptodate'?' selected="selected"':'')
             .">Up-to-date for the latest round</option>";
  echo "</select></li>";
}
?>
</ul>
<input type="submit" value="Store" />
</form>
