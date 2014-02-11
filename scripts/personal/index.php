<?php
// include_once('/nfs/rcs/www/webdocs/lifesciences-faculty/publications/manage/common.php');
require_once(dirname(dirname(__FILE__)) . '/manage/common.php');
//RAFAEL - Escrevi as proximas duas linhas
session_start();
$_SESSION['userid'] = $_SERVER['REMOTE_USER'];
if($config['readonly']===true)
{
  ?>
  <div style="padding: 50px;">
    <p>Editing this database has temporarily been disabled.</p>
    <p>If you have any queries, please <a href="mailto:<?php echo $config['webmasteremail'] ?>">contact the webmaster</a>.</p>
  </div>
  <?php
  return;
}


$dontshowowndetails=false;

if(!isset($userid))
  $userid=mysql_real_escape_string($_REQUEST['userid']);
if(!(strlen($userid)>0) && authorisedForPubs($_SERVER['REMOTE_USER']) && ($_REQUEST['action']!='newperson'))
{
  // SINCE THE USER IS AUTHORISED FOR MANAGEMENT, WE'RE GOING TO REDIRECT THEM TO THE MANAGEMENT PAGE
  ?>
  <script type="text/javascript">
  if(confirm("                      - - PUBLICATIONS SYSTEM - -   \n\nSince you're a registered administrator for the publications system, \nwould you like you jump straight to the 'Management' page \ninstead of your 'Personal' page?")) location.href='<?php echo $config['pageshomeurl'] ?>manage/';
  </script>
  <p style="margin: 20px 150px; padding: 5px; border: 1px solid rgb(204,102,0);"><strong>N.B!</strong> This is your personal 
  publications page.<br />For <strong>departmental publications management</strong> options available for 
  <em><?php echo $_SERVER['REMOTE_USER'] ?></em>, 
  please click through to 
  <a href="<?php echo $config['pageshomeurl'] ?>manage/"><?php echo $config['pageshomeurl'] ?>manage/</a></p>
  <p>&nbsp;</p>
  <?php

}

// If userid not set, OR IF USER ISN'T AN ADMINISTRATOR, then make certain that $userid is the user's own
if(!(strlen($userid)>0) || !authorisedForPubs($_SERVER['REMOTE_USER']) )
  $userid=mysql_real_escape_string($_SERVER['REMOTE_USER']);

if(!allowedToManageUser($_SERVER['REMOTE_USER'], $userid))
{
  // This is only triggered if the user IS an administrator, but not allowed to administer this particular person
  ?>
	<div class="highlypaddedmessagebox">
	<p>You do not appear to be authorised to manage this particular user's publications.</p>
	<p>If this is in error, please <a href="mailto:<?php echo $config['webmasteremail'] ?>">contact the
	support team</a>, making sure to state your user ID and the user ID of the person whose entries you wish to 
	administer.</p>
	<p>Your user ID was detected as <?php echo $_SERVER['REMOTE_USER'] ?></p>
	<p>The ID you requested to administer was detected as <?php echo $userid ?></p>
	</div>
  <?php
  
  return;
}



if(!strlen($action)) $action = $_REQUEST['action'];
  if(!isset($newpubtype))
    $newpubtype=$_REQUEST['newpubtype'];
  if(!isset($title))
    $title=$_REQUEST['title'];
  if(!isset($firstname))
    $firstname=$_REQUEST['firstname'];
  if(!isset($lastname))
    $lastname=$_REQUEST['lastname'];
  if(!isset($honorifics))
    $honorifics=$_REQUEST['honorifics'];
  if(!isset($deptid))
    $deptid=$_REQUEST['deptid'];
  if(!isset($otherdepts))
    $otherdepts=$_REQUEST['otherdepts'];
  if(!isset($email))
    $email=$_REQUEST['email'];

if($action=='updatename')
{
  updatename(stripslashes($userid), stripslashes($title), stripslashes($firstname), stripslashes($lastname), stripslashes($honorifics), stripslashes($email));

  // Update the user's dept code - only relevant for multi-dept'l or global administrators
  if((strlen($deptid)>0) && (sizeof($mydepts = getMyDepartments($_SERVER['REMOTE_USER']))>1) && isset($mydepts[$deptid])){
    $q = "UPDATE USERS SET deptid='"
	          . mysql_real_escape_string($deptid) . "' WHERE userid='"
	          . mysql_real_escape_string($userid) . "' LIMIT 1";
    mysql_query($q, connectpubsdb());
  }

  // Add "additional" depts list for the user
  if(is_array($otherdepts)){
    // Remove existing depts (the ones the user is allowed to remove, at least)
    removeUserOtherDepts(stripslashes($userid), array_keys(getalldepts()));
    // Then add all that are requested
    addUserOtherDepts(stripslashes($userid), $otherdepts);
  }
}
else if($action=='newperson' && authorisedForPubs($_SERVER['REMOTE_USER']))
{
  // If the userID==$_SERVER['REMOTE_USER'] this indicates that no user ID was supplied (in most cases)
  // So we need to generate a user ID for the newly-created person
  if($userid==$_SERVER['REMOTE_USER'])
  {

    // First thing to do is check the email address.
    // For adding new people (if we don't know their user ID, that is), we require the email address to be unique.
    // This helps prevent multiple accounts being added all referring to the same person - although doesn't make it impossible, of course!
    $res = mysql_query("SELECT userid FROM USERS WHERE email='" . mysql_real_escape_string(stripslashes($email)) . "' LIMIT 1", connectpubsdb());
	if($res && mysql_num_rows($res)!=0)
	{
	  ?>
	    <div class="highlypaddedmessagebox">
		<p>The email address you entered is already in the database - 
		this usually indicates that the person you wished to add is already registered.</p>
		<p>You cannot register a user with the same email address as another. If this is a problem please contact 
		<a href="mailto:<?php echo $config['webmasteremail'] ?>">the support team</a>.</p>
		</div>
	  <?php
	  return;
	}
	    

    $brandnewid='';
	while($brandnewid=='')
	{
	  $newnumbers = rand(1,999999);
	  $res = mysql_query("SELECT userid FROM USERS WHERE userid='x$newnumbers' LIMIT 1", connectpubsdb());
	  if($res && mysql_num_rows($res)==0)
	    $brandnewid = "x$newnumbers";
	}
	$userid = $brandnewid;
  }


  // First check if the user ID already exists
  $res = mysql_query("SELECT * FROM USERS LEFT JOIN DEPTS USING (deptid) WHERE userid='$userid' LIMIT 1", connectpubsdb());
//	echo mysql_error();
  if($res && mysql_num_rows($res)==1)
  {
    $person = mysql_fetch_assoc($res);
    ?>
	<div class="highlypaddedmessagebox">
	<p>The user ID you specified is already recorded in the database, as belonging to &quot;<strong><?php echo 
	htmlspecialchars("$person[firstname] $person[lastname]") ?></strong>&quot; (Department: <?php echo 
	htmlspecialchars("$person[NAME]") ?>).</p>
	<p>If you are authorised to manage this person's publications then you can 
	<a href="<?php echo $config['pageshomeurl'] ?>manage/?action=people&userid=<?php echo urlencode($userid) ?>">click here to visit <?php echo 
	htmlspecialchars("$person[firstname] $person[lastname]") ?>'s management page</a>.</p>
	<p>If there are any problems (e.g. incorrect department allocation), please 
	<a href="mailto:<?php echo $config['webmasteremail'] ?>">contact
	the support team</a>.</p>
	</div>
	<?php
	
	return; // This exits the current script (i.e. this include()d file)
	
	
  }
  else
  {  // Add a new person
    addnewpersonwithdept(stripslashes($userid), stripslashes($title), stripslashes($firstname), stripslashes($lastname), stripslashes($honorifics), stripslashes($deptid), stripslashes($email));
    
    
    // NOT DONE:
    
    // Run addUserOtherDepts(stripslashes($userid), $otherdepts) to add the secondary department affiliations
    addUserOtherDepts(stripslashes($userid), $otherdepts);
    
    
    
    echo "<p class='simplepaddedmessagebox'>Created a new user record, with user ID <strong>$userid</strong>.</p>";
  }
}

if(!(($res = mysql_query("SELECT * FROM USERS WHERE USERID='$userid' LIMIT 1", connectpubsdb())) && mysql_num_rows($res)>0))
{
  if(!authorisedForPubs($_SERVER['REMOTE_USER']))
  {
     require_once($config['homedir'] . 'manage/includes/notregistered.inc.php');
/*
  ?>
  <p style="margin: 50px;">Your user ID, <?php echo $userid ?>, has not been found in the online database. To be added to the 
    list of staff, please <a href="mailto:<?php echo $config['webmasteremail'] ?>">email the webmaster</a> - giving your <strong>full name</strong>, 
	<strong>user ID</strong>, and <strong>department</strong> - and I will add you to the database.</p>
  <?php
*/
  }
}
else
{
  $userinfo = mysql_fetch_assoc($res);

if($action=='associatepubs' && (isset($_REQUEST['associate']) || isset($_REQUEST['notmine'])))
{
  include_once($config['homedir'] . "manage/people/index.php");
}
else if($action=='associatepubs')
{
  echo "<p>Searching for unmatched publications...</p>";
  $action='lastnamesearch';
  $theinitial = substr($userinfo['firstname'],0,1);
  $lastname = $userinfo['lastname'];
/* DELETEME
  $lastname = $userinfo['lastname'] . ',' . $theinitial
        . '|' . $userinfo['lastname'] . ',_' . $theinitial
        . '|' . $userinfo['lastname'] . ',__' . $theinitial
        . '|' . $userinfo['lastname'] . ',___' . $theinitial
        . '|' . $userinfo['lastname'] . ',____' . $theinitial;
*/
  include_once($config['homedir'] . "manage/people/index.php");
  ?>
  <p><strong>To add a new publication to the database, choose the type you wish to add:</strong></p>
  <center>
  <?php
   chooseNewPublicationTypeForm();
   // singlePublicationForm(null);
  ?>
  </center>
<ul>
<li><a href="../manage/risimport/?admin=0">Import publications from <strong>RIS</strong> data file</a>
(Please use <strong>Reference Manager</strong> to create RIS data files).
</ul>
  <?php
}
else if(strlen($newpubtype)>0)
{
   singlePublicationForm(null);
}
else if($action=='storepub')
{
  storepub();
}
else if($action=='storepubassocs')
{
  storepubassocs();
}
else if($action=='changepubtype')
{
  $q = "SELECT * FROM PUBLICATIONS "
   . " WHERE pubid='$pubid' LIMIT 1";
  $res = mysql_query($q, connectpubsdb());
  $p = mysql_fetch_assoc($res);
  
  require_once($config['homedir'] . 'manage/includes/changePubTypeForm.inc.php');
}
else if($action=='edit')
{
  // First check if a forced pubtype change has been supplied, and carry it out
  if(isset($_REQUEST['changepubtype']))
    changePubType($pubid, stripslashes($_REQUEST['changepubtype']));

  $res = mysql_query("SELECT * FROM PUBLICATIONS WHERE pubid='" . intval($_REQUEST['pubid']). "' LIMIT 1",
  				connectpubsdb());
  if($res)
    $publication = mysql_fetch_assoc($res);
  singlePublicationForm($publication);
	if($pubid>0){
  	  ?>
	  <p style="margin: 20px; color: gray; font-style: italic; "><a href="./?action=changepubtype&pubid=<?php echo $pubid ?>&userid=<?php echo $userid ?>">
	  Change publication type<br />for this record</a></p>
	  <?php
	}
}
else if($action=='delete')
{
  if(!isset($pubid)) $pubid = $_REQUEST['pubid'];
  deletepub($pubid);
}
else if($action=='dissociate')
{
  if(!isset($pubid)) $pubid = $_REQUEST['pubid'];
  dissociatepub($pubid);
}
else if(!$dontshowowndetails)
{
?>
<p><?php echo $userinfo['title']?> 
<?php echo $userinfo['firstname']?> 
<?php echo $userinfo['lastname']?> 
<?php echo $userinfo['honorifics']?> 
(<?php echo $userinfo['email']?>) [<a href="javascript:revealupdatenamedetailsform();">Edit these details</a>]</p>
<form action="./" id="updatenamedetailsform">
<?php
$deptchoose = false;
if(sizeof($mydepts = getMyDepartments($_SERVER['REMOTE_USER']))>1){
  $deptchoose = "\n<select name='deptid'>";
  foreach($mydepts as $did=>$dept)
    $deptchoose .= "\n  <option value='$did'"
	                  . ($did==$userinfo['deptid'] ? ' selected="selected"': '') . ">$dept[NAME]</option>";
  $deptchoose .= "\n</select>";
}
?>
<table border="0" cellspacing="0" cellpadding="0">
  <tr>
    <th scope="col">Title</th>
    <th scope="col">Forename(s)</th>
    <th scope="col">Surname</th>
    <th scope="col">Honorifics</th>
    <th scope="col">Email address</th>
    <?php if($deptchoose) { ?><th scope="col">Home department</th><?php } ?>
    <th scope="col">&nbsp;</th>
  </tr>
  <tr>
    <td valign="top"><input name="title" type="text" value="<?php echo $userinfo['title']?>" size="<?php echo max(strlen($userinfo['title']),5)+1 ?>" maxlength="64"></td>
    <td valign="top"><input name="firstname" type="text" value="<?php echo $userinfo['firstname']?>" size="<?php echo max(strlen($userinfo['firstname']),5)+3 ?>" maxlength="128"></td>
    <td valign="top"><input name="lastname" type="text" value="<?php echo $userinfo['lastname']?>" size="<?php echo max(strlen($userinfo['lastname']),5)+3 ?>" maxlength="128"></td>
    <td valign="top"><input name="honorifics" type="text" value="<?php echo $userinfo['honorifics']?>" size="<?php echo max(strlen($userinfo['honorifics']),5)+2 ?>" maxlength="128"></td>
    <td valign="top"><input name="email" type="text" value="<?php echo $userinfo['email']?>" size="<?php echo max(strlen($userinfo['email']),15)+2 ?>" maxlength="128"></td>
    <?php if($deptchoose) { ?><td valign="top"><?php echo $deptchoose ?>
    <label style="display: block; float: left;">Additional department(s) (if jointly-appointed): <br />
    <select name="otherdepts[]" id="otherdepts[]" size="2" multiple="multiple"><option value="">No additional departments</option>
      <?php
	     $alldepts = getalldepts();
		$otherdepts = splitDeptListString($userinfo['otherdepts']);
//		print_r($otherdepts);
		foreach($alldepts as $k=>$v)
		  echo '<option value="' . htmlspecialchars($k) . '"'.(array_search($k,$otherdepts)===false?'':' selected="selected"').'>' . htmlspecialchars($v['NAME']) . '</option>'; 
      ?>
    </select>    </label></td><?php } ?>
    <td valign="top"><input type="submit" value="Store amended details">
	<input type="hidden" name="userid" value="<?php echo $userid ?>" />
	<input type="hidden" name="action" value="updatename" /></td>
  </tr>
</table>
</form>
<script type="text/javascript">
<!--
function revealupdatenamedetailsform()
{
   if(document.getElementById)
   {
     document.getElementById("updatenamedetailsform").style.display="block";
   }
}
if(document.getElementById)
  document.getElementById("updatenamedetailsform").style.display="none";
//-->
</script>
<p>Your publications listings are displayed below. To add a new listing, follow the link at 
the bottom of the page.</p>
<p>&nbsp;</p>
<?php
  $users = array($userid);
//  $showeditoptions = true;
  $showopts = 'detail,edit,dissociate' . (authorisedForPubs($_SERVER['REMOTE_USER'])?',delete,duplication':'') 
                  . (singleDeptAdmin($_SERVER['REMOTE_USER'])?',notinmydept':'')
                  . (isGlobalAdmin($_SERVER['REMOTE_USER'])?',superadmin':'')
			   ;
  $group = $_REQUEST['group'];
  if(!$group)
    $group = 'y';
  $includeunconfirmed='y';
  $includeinpress='y';
  $showdeleteoption = (authorisedForPubs($_SERVER['REMOTE_USER'])?'y':'n');
//  if(authorisedForPubs($_SERVER['REMOTE_USER']) && !isGlobalAdmin($_SERVER['REMOTE_USER'])){ // If not global admin, should only show the user's publications within their dept(s)
//    $depts = splitDeptListString(getAuthorisations($_SERVER['REMOTE_USER']));
//  }
  include_once($config['homedir'] . 'query/index.php');

  // The normal "detail" clickthrough doesn't show user or department associations (it's a generic public view).
  // For signed-in users we want to show them the dept'l and user associations.
  // NOTE: We're making use of the fact that query/index.php loads its data into $p
  //   and doesn't destroy it afterwards.
  if($action=='search' && sizeof($p)==1){
    $pusers = splitDeptListString($p[0]['userlist']);
    $pdepts = deptListToDeptNames($p[0]['deptlist']);
    $pdeptspending = deptListToDeptNames($p[0]['pendingdeptlist']);
   
    echo "\n<p><strong>User associations:</strong><br />";
    echo sizeof($pusers)==0        ? '<em>None</em>' : implode(', ', $pusers);
    echo "</p>";
    echo "\n<p><strong>Department associations confirmed:</strong><br />";
    echo sizeof($pdepts)==0        ? '<em>None</em>' : implode(', ', $pdepts);
    echo "</p>";
    echo "\n<p><strong>Department associations pending:</strong><br />";
    echo sizeof($pdeptspending)==0 ? '<em>None</em>' : implode(', ', $pdeptspending);
    echo "</p>";
  }
?>
<p style="border: 1px solid gray; text-align: center; padding: 10px; margin: 10px;"><a href="./?action=associatepubs&userid=<?php 
echo $userid ?>&dummy=<?php echo time();

if(strlen($_REQUEST['returnurl'])>0)
  echo '&returnurl=' . $_REQUEST['returnurl'];
if(strlen($_REQUEST['returnname'])>0)
  echo '&returnname=' . $_REQUEST['returnname'];

 ?>">Add 
a publication to your listings</a></p>
<p>&nbsp;</p>
<?php

}  // End of "action" switch

} // End of "user-is-in-the-database" switch
?>
