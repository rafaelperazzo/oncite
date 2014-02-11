<?php

// Approve (or don't approve!) newly-self-registered users

// Usually this will be called from manage/index.php?action=approvepeople
//  - so don't play with the "action" parameter, always pass it back as "action=approvepeople"

require_once(dirname(dirname(__FILE__)) . '/common.php');

$submittedwaiter = $_REQUEST['updatewaiter'];
$otherdept = $_REQUEST['otherdept'];

$waiters = getNewUsersWaitingForApproval($_SERVER['REMOTE_USER']);
$depts = getDepts();
if(sizeof($waiters)==0)
{
  ?><p>No-one from your department(s) is waiting to be confirmed.</p><?php
}
else
{
  ?>
  <form action="./" method="post">
  <input type="hidden" name="action" value="approvepeople" />
  <ul>

  <?php
  $showsubmitbutton = false;
  foreach($waiters as $waiteruserid=>$waiter)
  {
    echo "\n  <li style='margin-top: 20px;'>";
	
	// Check out whether a decision has been submitted - and process it if so
	$theirdept = stripslashes(rawurldecode($submittedwaiter[$waiteruserid]));
     if(strlen($theirdept)>0 && $theirdept!=',ignore')
	{
	  $auth = getAuthorisations($_SERVER['REMOTE_USER']);
	  if(isGlobalAdmin($_SERVER['REMOTE_USER']))
	    $confirmit = 1;
	  else
	    $confirmit = (strpos($auth, ",$theirdept,")!==false) 
	                        ? 1 : 0;
	  $q = "UPDATE USERS SET deptid='" . mysql_real_escape_string($theirdept) . "', "
		  . " deptconfirmed='$confirmit' WHERE userid='" . mysql_real_escape_string($waiteruserid) . "' LIMIT 1";
	//  echo "<p>Dept = $theirdept; Auths = $auth; Query = $q</p>";
      if(mysql_query($q, connectpubsdb()))
	  {
	    echo "Thankyou - $waiter[firstname] $waiter[lastname]'s home department" .
		         ($confirmit ? ' has been confirmed.' : ' has been changed.');
        
		// If the dept has been changed then ALSO we should change the dept of any submitted pubs.
		// We should also email them to let them know.
		if($theirdept != $waiter['deptid'])
		{
		   $cpq = "SELECT pubid, deptlist, pendingdepts FROM PUBLICATIONS WHERE originator='" 
		          . mysql_real_escape_string($waiteruserid) . "' AND (pendingdepts LIKE '%,"
				. mysql_real_escape_string($waiter['deptid']) . ",%')";
		   $cpres = mysql_query($cpq, connectpubsdb());
		   if($cpres && mysql_num_rows($cpres)>0)
		   {
		     while($cprow = mysql_fetch_assoc($cpres))
			 {
			   $cpnewdeptlist = preg_replace('/,' . addslashes($waiter['deptid']) . ',/', ",$theirdept,", $cprow['deptlist']);
			   $cpnewdeptlistpending = preg_replace('/,' . addslashes($waiter['deptid']) . ',/', ",$theirdept,", $cprow['pendingdepts']);
			   mysql_query("UPDATE PUBLICATIONS SET deptlist='"
			               . mysql_real_escape_string($cpnewdeptlist) 
						. "', pendingdepts='"
			               . mysql_real_escape_string($cpnewdeptlistpending) 
						. "' WHERE pubid='$cprow[pubid]' LIMIT 1", connectpubsdb());
			   if($config['debug'])
			     echo "<p>Publication $cprow[pubid]: Deptlist changed from $cprow[deptlist] to $cpnewdeptlist</p>" . mysql_error();
			 }
		   } // End of changing-dept-of-publications
		
/*
		   $message = "Dear $waiter[firstname] $waiter[lastname],

Thank you for registering with the online publications system. 
An administrator ($_SERVER[REMOTE_USER]) has reassigned you 
from home department $waiter[deptid] (" . $depts[$waiter['deptid']]['NAME'] . ") 
to home department $theirdept (" . $depts[$theirdept]['NAME'] . ").

This is an automatically-generated message, for your information. You do not need to respond 
or take any further action - but if there are any issues surrounding your registration, please
contact the support team at $config[webmasteremail].

______________________________________________________________________________________________
$config[pageshomeurl]

";
//           echo "<pre>$message</pre>";
           if($config['automailacads'])
		     mail($waiter['email'], '[Automatic message] Your home department',$message, 
		          "From: $config[webmasteremail]\r\nReply-To: $config[webmasteremail]");
*/
		}
	  }
	  else
	  {
	    echo "Sorry, there has been a database error. Please contact the <a href='mailto:$config[webmasteremail]'>webmaster</a> if this problem continues.";
	  }
	}
	else // No decision has been submitted, so let's just write out the choices
	{
      $showsubmitbutton = true;
      echo "<strong>$waiter[title] $waiter[firstname] $waiter[lastname] $waiter[honorifics]</strong> "
	      . "(User ID <em><strong>$waiter[userid]</strong></em>, "
	      . "email address <em><strong>$waiter[email]</strong></em>)"
		  . "<br />Home department: " . $depts[$waiter['deptid']]['NAME']
		  . "\n";


    // Only show the edit list for the home department if we're allowed to edit that dept
    if(($waiter['deptconfirmed']==0) && (isGlobalAdmin($_SERVER['REMOTE_USER']) || strpos(getAuthorisations($_SERVER['REMOTE_USER']), ','.$waiter['deptid'].',')!==false)){
    ?>

		<ul><li><select name="updatewaiter[<?php echo htmlspecialchars($waiteruserid); ?>]">
		  <option value=",ignore" selected="selected">Don't approve just yet</option>
		  <option value="<?php echo htmlspecialchars($waiter['deptid']); ?>">Department is correct - approve this user</option>
		  <optgroup label="Or change department to:">
		<!--  <option value=",ignore">---------- Or change department to: ----------</option> -->
			<?php
			foreach($depts as $deptid=>$dept)
			  echo "\n	  <option value=\"" . htmlspecialchars($deptid) . "\">" 
								. htmlspecialchars($dept['NAME']) . "</option>";
			?>
			</optgroup>
		</select></li></ul>
	<?php
	}else{
	  echo  ($waiter['deptconfirmed'] ? '<em>(Confirmed)</em>' : '<em>(Not yet confirmed)</em>');
	}
	} // End of no-home-dept-choice-was-submitted
	    // Now for the secondary department processing
	    
	    if(isset($otherdept[$waiteruserid])){
	      $confirms = array();
	      $removes  = array();
	      foreach($otherdept[$waiteruserid] as $odeptid=>$decision){
		   //echo "<p>$odeptid => $decision</p>";
		   switch($decision){
		     case 'confirm':
			  $confirms[] = $odeptid;
			  break;
			case 'remove':
			  $removes[] = $odeptid;
			  break;
			default:
			  echo "Error - invalid option &quot;$decision&quot; for dept code $odeptid";
		   }
		 }
		 addUserOtherDepts($waiteruserid, $confirms); // Add (in other words, confirm) the dept ID codes that are to be confirmed
		 removeUserOtherDepts($waiteruserid, $removes); // Delete the dept ID codes that are not to be confirmed
	      echo "<br />\nThank you - decisions on secondary departmental affiliations have been stored.";
	    }else{
	    
	    // Display the options
	    $otherdepts = splitDeptListString($waiter['otherdepts']);
	    $otherdeptspending = splitDeptListString($waiter['otherdeptspending']);
	    if(sizeof($otherdepts) || sizeof($otherdeptspending)){
	      echo "<br />\nSecondary departmental affiliations:\n<ul>";
		 foreach($otherdepts as $od){
		   echo "\n       <li>" . $depts[$od]['NAME'] . ' <em>(confirmed)</em></li>';
		 }
		 foreach($otherdeptspending as $od){
		   echo "\n       <li>" . $depts[$od]['NAME'] 
		                 . (isGlobalAdmin($_SERVER['REMOTE_USER']) || (strpos(getAuthorisations($_SERVER['REMOTE_USER']), ",$od,")!==false )
					                ? ' <label><input type="radio" name="otherdept['.htmlspecialchars($waiteruserid).']['.htmlspecialchars($od).']" value="confirm" />Confirm</label>'
								 . ' <label><input type="radio" name="otherdept['.htmlspecialchars($waiteruserid).']['.htmlspecialchars($od).']" value="remove" />Remove</label>'
								 : ' <em>(not yet confirmed)</em>')
					  .'</li>';
		 }
		 echo "\n</ul>";
	    }
	    
	    } // End of has-otherdept-info-bee-submitted
//DEL - MOVED UPWARDS	} // End of no-home-dept-choice-was-submitted
	echo "</li>";
  }
  ?>
  </ul>
  <?php
  if($showsubmitbutton)
  {
    ?>
<!--  <p>NB: If you change a person's home department, please make certain you choose correctly. The user will be 
  emailed to inform them that the change has occurred. </p> -->
  <input type="submit" value="Store these decisions" />
    <?php
  }
  ?>
  </form>
  <?php
} // End of yes-there-are-people-waiting


?>