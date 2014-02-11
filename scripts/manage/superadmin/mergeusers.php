<?php

// $action=='mergeusers'
// This script defines some handy functions, towards the bottom of the page

// Grab some named variables from the environment
$grabthese = array('useridfrom', 'useridto', 'sure');
foreach($grabthese as $varname){
  $$varname = mysql_real_escape_string($_REQUEST[$varname]);
}

if($useridfrom && $useridto) {

  $userfrom = mergeusers_getuserinfo($useridfrom);
  $userto = mergeusers_getuserinfo($useridto);
  
  // Complain bitterly if either user ID is not found
  if(!$userfrom){
    die("<p>$useridfrom not found.</p>");
  }
  if(!$userto){
    die("<p>$useridto not found.</p>");
  }


  if($sure){
  
    merge_users($useridfrom, $useridto);
  
  } else {
    // Present info about the two user accounts
    
    ?><form method="post" action="./">
    <input type="hidden" name="action" value="mergeusers" />
    <input type="hidden" name="useridfrom" value="<?php echo $useridfrom ?>" />
    <input type="hidden" name="useridto" value="<?php echo $useridto ?>" />
    <input type="hidden" name="sure" value="yes" />
    <p>Merging this account:</p>
    <ul><li><?php echo mergeusers_formatuserinfo($userfrom) ?></li></ul>
    <p>into this account:</p>
    <ul><li><?php echo mergeusers_formatuserinfo($userto) ?></li></ul>
    <p>When this has been performed, the account <em><?php echo $userfrom['userid'] ?></em>
    will be DELETED and the account <em><?php echo $userto['userid'] ?></em> will be 
    the account kept. <strong>If you are sure</strong> then submit this form.
    If not, simply go back to the main admin page to cancel.</p>
    <input type="submit" value="I'm sure" />
    </form><?php
    
  } // End "is the admin sure about this?"

} else {

  ?><form method="post" action="./">
  <input type="hidden" name="action" value="mergeusers" />
  <p><label>&quot;Main&quot; user ID: <input type="text" name="useridto" size="7" maxlength="7" /></label></p>
  <p><label>User ID for merging into the above: <input type="text" name="useridfrom" size="7" maxlength="7" /></label></p>
  <p>You will be shown the details and asked to confirm before the actual merge is carried out.</p>
  <input type="submit" value="Submit" />
  </form><?php

} // End of "are the user IDs specified?"




function mergeusers_getuserinfo($userid){ // The value of $userid MUST ALREADY HAVE BEEN ESCAPED
  $q = "SELECT USERS.* FROM USERS WHERE userid='$userid' LIMIT 1";
  $res = mysql_query($q);
  if(mysql_num_rows($res)==0)
    return false;
  $data = mysql_fetch_assoc($res);
  $q = "SELECT COUNT(pubid) AS numpubs FROM PUBLICATIONS WHERE userlist LIKE ',$userid,'";
  $res = mysql_query($q);
  if($res){
    $row = mysql_fetch_assoc($res);
    $data['numpubs'] = $row['numpubs'];
  }
  return $data;
}


function mergeusers_formatuserinfo($user){
  return "<strong>$user[userid]</strong> - $user[title] $user[firstname] $user[lastname]";
}

function merge_users($useridfrom, $useridto){ // The user IDs MUST BE DATABASE SAFE, i.e. already escaped
  global $config;
  echo "<p>At this point I would merge $useridfrom into $useridto.</p>";

  // User ID data is found in "simple" fashion in the following fields:
  $simplereplace = array('DEPTS'=>'hod', 'FACULTIES'=>'dean', 'PUBLICATIONSADMINS'=>'userid', 
     'PUBLICATIONSNOTMINE'=>'userid', 'RAEADMINS'=>'userid',
     'RAEENTRIES'=>'userid', 'RAEMYGROUPS'=>'userid', 'RAEMYSTUDENTS'=>'userid', 'RAEMYASSISTANTS'=>'userid', 
	'RAEMYGRANTS'=>'userid', 'RAEMYPUBS'=>'userid');

  foreach($simplereplace as $table=>$field){
    $q = "UPDATE $table SET $field='$useridto' where $field='$useridfrom'";
    if($config['debug']){
      echo "<p>$q</p>";
    }
    mysql_query($q);
  }

  // It's found in comma-separated fashion in the following:
  // PUBLICATIONS.userlist
  $q = "UPDATE PUBLICATIONS SET userlist=REPLACE(userlist, '$useridfrom', '$useridto') where userlist LIKE '%$useridfrom%'";
  if($config['debug']){
    echo "<p>$q</p>";
  }
  $res = mysql_query($q);
  if(!$res){
    die("Error while updating publications associations: ".mysql_error());
  }
  
  // Finally, deal with the USERS.userid field which is now alone, having lost all its associations...
  $q = "DELETE FROM USERS WHERE userid='$useridfrom' LIMIT 1";
  if($config['debug']){
    echo "<p>$q</p>";
  }
  mysql_query($q);

  // Store a record in PUBLICATIONSTRANS
  recordtransaction("usermerge:$useridfrom>$useridto", 0);

  // Then report back
  ?><p style="padding: 50px;">Merge has been carried out.</p><?php

}



?>