<?php
//ini_set("memory_limit","24M");
require_once('config.inc.php');
//print("AQUI!!");
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


if(!isset($action)) $action= $_REQUEST['action'];
echo "\n<!-- action=$action -->\n";

if(strlen(trim($action))!=0)
  echo "\n<p style='text-align:right;'>[<a href='$config[pageshomeurl]manage/'>Back to administration homepage</a>]</p>";

switch($action)
{
case 'edit':
case 'changepubtype':
  include_once($config['homedir'] . 'manage/edit/index.php');
  break;
case 'approvepeople':
  include_once($config['homedir'] . 'manage/approvepeople/index.php');
  break;
case 'new':
  include_once($config['homedir'] . 'manage/new/index.php');
  break;
case 'querylog':
  if(!isset($pubid)) $pubid = $_REQUEST['pubid'];
  require_once($config['homedir'] . 'manage/common.php');
  $usertype = "admin";
  require_once('superadmin/querylog.php');
  break;
case 'duplicates':
  include_once($config['homedir'] . 'manage/duplicates/index.php');
  break;
case 'risimport':
  include_once($config['homedir'] . 'manage/risimport/index.php');
  break;
case 'browsedate':
  include_once($config['homedir'] . 'manage/browsedate/index.php');
  break;
case 'deptstatus':
  include_once($config['homedir'] . 'manage/deptstatus/index.php');
  break;
case 'listallusers':
  ?><p>All users registered on the database (across all departments):</p>
  <?php
  include_once($config['homedir'] . 'people/listallusers.php');
  break;
/*
case 'people':
case 'lastnamesearch':
case 'associatepubs':
case 'storepub':
case 'storepubassocs':
  include_once($config['homedir'] . 'personal/index.php');
  break;
*/
  case 'querybynotes':
    require_once($config['homedir'] . 'manage/common.php');
    require_once($config['homedir'] . 'query/formataref.php');
    $q = "SELECT * FROM PUBLICATIONS WHERE notes LIKE '%" . mysql_real_escape_string(rawurldecode($_REQUEST['q'])) . "%' ORDER BY authorlist, secondaryauthorlist";
	$res = mysql_query($q, connectpubsdb());
	echo "\n<p>&nbsp;</p>\n<p>Found " . mysql_num_rows($res) . " publications where the &quot;Notes&quot; field says &quot;" . htmlspecialchars(rawurldecode($_REQUEST['q'])) . "&quot;:</p>";
	$showopts = 'edit,detail';
	if($res)
	{
      ?>
	  <ul class="publicationslist">
	  <?php
	  while($row = mysql_fetch_assoc($res))
	  {
	    echo "\n           <li>" . formataref($row, $_SERVER['REMOTE_USER'], '', true, true, true) . "</li>";
	  }
      ?>
	  </ul>
	  <?php
	}
    break;
case 'pubsadmins':
  include_once($config['homedir'] . 'manage/includes/publicdeptlist.inc.php');

  break;
case 'journaltitles':
  include_once($config['homedir'] . 'manage/common.php');

  // Carry out the journal renaming, if data is supplied
  $jfrom = rawurldecode(stripslashes($_REQUEST['jfrom']));
  $jto   = rawurldecode(stripslashes($_REQUEST['jto']));
  if((strlen($jto)>0) && (strlen($jfrom)>0)){
    echo "<p>Changing <strong>$jfrom</strong> to <strong>$jto</strong></p>";
    changeJournalTitle($_SERVER['REMOTE_USER'], $jfrom, $jto);
  }

  $journals = listAllJournalTitles($_SERVER['REMOTE_USER']);
  
  ?>
  <script type="text/javascript">
  function renameJournal(jfrom)
  {
    var jto = prompt("Change\n"+jfrom+"\nto:", jfrom);
	if(jto && (jto!=jfrom) && confirm("Are you sure?\nThis is a global operation and cannot be undone:\n\nChange \n" + jfrom + "\n to \n" + jto))
	{
	  location.href="./?action=<?php echo $action ?>&jfrom=" + jfrom.replace(/&/g,"%26") + "&jto=" + jto.replace(/&/g,"%26");
	}
  }
  </script>
  <ul>
  <?php
  foreach($journals as $j) {
    $jj = preg_replace ("/ \(ISSN.*/", "", $j);
    echo "\n<li>" . htmlspecialchars($j) 
	     . " &middot; [<a href='$config[pageshomeurl]manage/?action=edit&amp;journaltitle=" 
		 . rawurlencode($jj) 
	     . "'>Find occurrences in database</a>] &middot; [<a href=\"javascript:renameJournal('" 
		 . htmlspecialchars(str_replace("'", "\\'", $jj)) 
		 . "')\">Edit journal title</a>]</li>";
    }
  ?></ul>
  <?php
  break;
case 'viewpubs':
  if(!isset($userid))
    $userid=$_REQUEST['userid'];
  $users = array($userid);
  include_once($config['homedir'] . 'query/index.php');
  break;

case 'pubtype':
case 'search': // Added so that the 'detail' clickthrough works
  include_once($config['homedir'] . 'manage/common.php');
  $depts = splitDeptListString(getAuthorisations($_SERVER['REMOTE_USER']));
  if(is_array($_REQUEST['depts']))
    foreach($_REQUEST['depts'] as $v)
      $depts[] = $v;
//  $showeditoptions='y';
  $includeunconfirmed='y';
  $showopts = 'detail,edit,delete,duplication'. (singleDeptAdmin($_SERVER['REMOTE_USER'])?',notinmydept':'');
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
  
  break;
case 'listpeople':
  include_once($config['homedir'] . 'manage/common.php');

  if(!isset($namefragment))
    $namefragment=rawurldecode($_REQUEST['namefragment']);
  $namefragment = preg_replace('/\W/', ' ', $namefragment); // Replace non-word characters with empty space
  $namefragment = preg_replace('/ +/', ' ', $namefragment); // Reduce multiple spaces down to one
  if(!isset($deptid))
    $deptid=$_REQUEST['deptid'];
  $deptid = preg_replace('|[^\w/]|', '', $deptid);


  $q = 'SELECT firstname, lastname, userid, deptid FROM USERS';

  $whereclauses = array();
  if(strlen($namefragment)>0)
  {
	$namefragment = explode(' ', $namefragment);
	foreach($namefragment as $v)
	  $whereclauses[] = 'firstname LIKE \'%' . $v . '%\' OR lastname LIKE \'%' . $v . '%\'';
  }
  if(strlen($deptid)>0)
    $whereclauses[] = '(deptid=\'' . $deptid . '\' OR (otherdepts<>"," AND otherdepts LIKE "%,' . $deptid . ',%"))';

  if(!isGlobalAdmin($_SERVER['REMOTE_USER']))
  {
    $mydepts = splitDeptListString(getAuthorisations($_SERVER['REMOTE_USER']));
	$mydeptsclause = '0';
	foreach($mydepts as $v)
	  $mydeptsclause .= " OR (deptid='" . mysql_real_escape_string(trim($v)) . "' OR (otherdepts<>',' AND otherdepts LIKE '%," . $v . ",%'))";
//	  $mydeptsclause .= " OR deptid='" . mysql_real_escape_string(trim($v)) . "'";
	$whereclauses[] = $mydeptsclause;
  }

  if(sizeof($whereclauses)>0)
    $q .= " WHERE ((" . implode(") AND (", $whereclauses) . "))";

  $q .= ' ORDER BY lastname, firstname';

  if($config['debug'])
    echo "<p>$q</p>";
  $res = mysql_query($q, connectpubsdb());
  if($res)
  {
    $mysqlnumrows = mysql_num_rows($res);

    if($mysqlnumrows==0)
	  echo "<p>No users found to match criteria.</p>";
    // If $mysqlnumrows==1, then ideally we want to just show them immediately rather than giving their name
	elseif($mysqlnumrows==1)
	{
      $row = mysql_fetch_assoc($res);
	  echo "<script type=\"text/javascript\">location.href=\"./?action=people&userid=$row[userid]\"</script>";
	  die();
	}
     
	// Fetch user records 
	$userids = array();
	$users = array();
	while($row = mysql_fetch_assoc($res)){
	  $users[] = $row;
	  $userids[] = $row['userid'];
	}
	// Fetch user publication counts
	// NB Restricting counts to local depts is deactivated, since the subpage shows all of them anyway...
	$numpublished = countPubsPerUser($userids, array() /* splitDeptListString(getAuthorisations($_SERVER['REMOTE_USER'])) */ );

	if($mysqlnumrows>12)
      $numpeoplepercol = ($mysqlnumrows / 4)+2;
	else
	  $numpeoplepercol = 12;
	$curnum = 0;
	echo "<table border=\"0\"><tr><td valign=\"top\">";
	//while($row = mysql_fetch_assoc($res))
	foreach($users as $row)
	{
///	  $numpublished = countPubsPerUser($row['userid'], splitDeptListString(getAuthorisations($_SERVER['REMOTE_USER'])));
	  echo "\n  <li><a href=\"./?action=people&userid=$row[userid]\" title=\"$row[firstname] $row[lastname] ($row[userid]). Home dept: $row[deptid]\">$row[firstname] <strong>$row[lastname]</strong></a>"
	                . ($numpublished[$row['userid']]?' ('.$numpublished[$row['userid']].')':'') . "</li>";
	  if((++$curnum % $numpeoplepercol) == 0)
	    echo "\n </td><td valign=\"top\">";
	}
	echo "</td></tr></table>";
  }
  else
  {
    echo "<p>Database error - sorry! Detail " . mysql_error() . "</p><!--\n\n $q \n\n-->";
  }
  break;
case 'notinmydept':
  if(!isset($pubid)) $pubid = $_REQUEST['pubid'];
  include_once($config['homedir'] . 'manage/common.php');
  notInMyDept($pubid);
  break;
case 'issn':
  require_once('common.php');
  require_once('superadmin/issn.php');
  break;
default:
  include_once($config['homedir'] . 'manage/common.php');
  if($action=='automails')
  {
    mysql_query("UPDATE PUBLICATIONSADMINS SET pleasedontautoemail="
	        . intval($_REQUEST['disabled']) . " WHERE userid='"
			. mysql_real_escape_string($_SERVER['REMOTE_USER']) . "' LIMIT 1", connectpubsdb());
  }
  
  if(authorisedForPubs($_SERVER['REMOTE_USER']) && ($action=='storepub' || $action=='storepubassocs'))
  {
    include_once($config['homedir'] . 'manage/edit/index.php');
  }
  else if(!authorisedForPubs($_SERVER['REMOTE_USER']) || strlen($_REQUEST['userid'])>0 || ($action=='people') || ($action=='newperson'))
  {
    include_once($config['homedir'] . 'personal/index.php');
  }
  else if($action=='delete')
  {
    if(!isset($pubid)) $pubid = $_REQUEST['pubid'];
    deletepub($pubid);
  }
  else if($action=='userlesspubs')
  {
  
    // Carry out actions if submit has been pressed - store positive associations 'y' and negative associations 'n' , ignore neutrals 'x'
	if(is_array($_REQUEST['assoc']))
	{
	  foreach($_REQUEST['assoc'] as $pubid=>$alist)
	    if(is_array($alist))
		{
		  $addids = '';
		  foreach($alist as $uid=>$msg)
		    if($msg=='y'){
			  $addids .= ",$uid";
		    }elseif($msg=='n'){
		      $q = "INSERT INTO PUBLICATIONSNOTMINE SET userid='".mysql_real_escape_string($uid)."', pubid='".mysql_real_escape_string($pubid)."'";
			 echo "<p>$q</p>";
			 mysql_query($q, connectpubsdb());
		    }
		  $q = "UPDATE PUBLICATIONS SET userlist=CONCAT(userlist,'" 
		                . mysql_real_escape_string($addids) 
						. "') WHERE pubid=" . intval($pubid) . " LIMIT 1";
		  // echo "<pre>" . print_r($q, true) . "</pre>";
		  mysql_query($q, connectpubsdb());
		}
	}
  
    require_once($config['homedir'] . 'query/formataref.php');
    ?>
	<h2>Unassociated entries</h2>
	<script type="text/javascript">
	var toggleson = true;
	function toggleAuthorBoxes(){
	  if(document.getElementById){
	    var i = 0;
	    toggleson = !toggleson;
	    while(document.getElementById('togglebox'+i)){
	      document.getElementById('togglebox'+i).checked = toggleson;
		 i++;
	    }
	  }
	}
	</script>
	<form action="./" method="post">
	<input type="hidden" name="action" value="userlesspubs" />
<!--	<input type="button" value="Toggle all checkboxes" onClick="toggleAuthorBoxes()" /> -->
	<?php
    $auth = getAuthorisations($_SERVER['REMOTE_USER']);
	if(preg_match_all('/([^\s,\.]+)/', $auth, $matches, PREG_PATTERN_ORDER))
      $dcodes = $matches[1];
	else
	  $dcodes = array_keys(getDepts());
	$depts = getDepts();
	$numpubs = 0;
	
	$toggleboxcount = 0;
	
	shuffle($dcodes);
	foreach($dcodes as $dcode)
	{
	  set_time_limit(30);
	  $uns = getUserlessPubs($dcode);
	  if(sizeof($uns)==0) continue;
	  echo "\n  <h3>" . $depts[$dcode]['NAME'] . "</h3>\n\n<ul>";
	  foreach($uns as $pubid=>$un)
	  {
	    $posses = getpossibleauthorswithindept($un['authorlist'], $dcode);
		
	    // We're not interested in crosses where a DISassociation has already been recorded
	    foreach($posses as $posskey => $poss){
	      $res = mysql_query("SELECT pubid FROM PUBLICATIONSNOTMINE WHERE userid='$poss[userid]' AND pubid='$pubid'");
	      if(mysql_num_rows($res)>0)
		   unset($posses[$posskey]);
	    }

         // We're not interested in publications with no suggestions to make
         if(sizeof($posses)==0) continue;
		
	    if(++$numpubs > 50)
		{
		  echo "<p><strong>The search has been limited to 50 entries</strong>, because of speed constraints. If you submit your decisions then more will be displayed.</p>";
		  break(2);
		}
		
	    echo "\n    <li>" 
		          . formataref2($un, $_SERVER['REMOTE_USER'], '')
				  . "<br />\n";

        if(sizeof($posses)==0){
		  echo 'No matches';
	   }else{
		 foreach($posses as $poss){
		    echo "<div style='text-align: right;'><strong>$poss[firstname] $poss[lastname]</strong> ($poss[userid])"
		     . ":&nbsp;<label><input type='radio' name='assoc[$pubid][$poss[userid]]' id='togglebox".($toggleboxcount++)."' value='y' />Associate</label>"
		     . "&nbsp;&middot;&nbsp;<label><input type='radio' name='assoc[$pubid][$poss[userid]]' id='togglebox".($toggleboxcount++)."' value='x' checked='checked' />Ignore for now</label>"
		     . "&nbsp;&middot;&nbsp;<label><input type='radio' name='assoc[$pubid][$poss[userid]]' id='togglebox".($toggleboxcount++)."' value='n' />Never associate</label>"
			. "</div>";
	      }
	    }
	    echo "</li>";
	  }
	  echo "\n</ul>";
	}
	if($numpubs==0)
	{
	  ?>
	  <div class="highlypaddedmessagebox">
	  <p>No remaining entries found.</p>
	  <p>This feature searches for all publications in your department(s) with <strong>no</strong> user-ID associations, and displays those
	  for which it can suggest some potential users to associate. None fitting those two criteria were found.</p>
	  </div>
	  <?php
	}
	?>
	<input type="submit" value="Store associations" />
	</form>
	<?php
  } // End action "userlesspubs"
  else
  {

// These functions execute only RARELY - they check their own timestamp and make sure they don't waste too much time.
emailPromptsForAdmins();

// MOVED TO THE STATS VIEWING PAGE: include_once($config['homedir'].'manage/automatic-actions/updatestats-timedinterval.inc.php');




?>
<div style="border: 1px black solid; padding: 0px 10px 10px 10px; margin:3px;">
<h2>New entries</h2>
  <ul>
<?php
$numwait = getNumberWaitingForApproval($_SERVER['REMOTE_USER']);
if($numwait>0)
  echo "    <li style=\"font-size: larger; font-weight: bold;\"><a href=\"approval/\">Approve publications</a> - $numwait publications are waiting for you to approve/reject</li>";
?>
    <li><a href="./?action=new">Add a new publication to the database</a></li>
  </ul>
      <form method="get" action="./">
  <h2>Editing entries</h2>
  <ul>
    <?php
	if($tempdept = singleDeptAdmin($_SERVER['REMOTE_USER']))
	{
	  ?>
      <li><a href="./?action=edit&depts[]=<?php echo $tempdept ?>">View ALL my department's publications</a></li>
	  <?php
	}
	?>
    <li>Choose a person's entries to edit:
       <!--  <strong>N.B. It
      takes 15 seconds or so for these full listings to be generated.</strong> -->
<!--      <form method="get" action="./"> -->
	  <ol>
      <!--  <li><a href="./?action=listpeople">List all people</a></li> -->
		<li><a href="javascript:var frag=prompt('Enter name (or fragment of name) to search for:\n(or just press OK for all staff)',''); if(frag!=null){location.href='./?action=listpeople&namefragment='+frag}">Search for a person by name</a></li>
 <?php
 if($tempdept)
 {
   echo "<li><a href='./?action=listpeople&deptid=$tempdept'>List all people in my department</a></li>";
 }
 else
 {
    ?>
        <li>List all people in a department: <select onchange="if(this.value!='') location.href='./?action=listpeople&deptid='+this.value">
		  <option value=""> --- Choose a department --- </option>
		  <?php

     if(isGlobalAdmin($_SERVER['REMOTE_USER']))
       foreach(getdepts() as $k=>$v)
		  echo "\n<option value=\"$k\">$v[NAME]</option>";
     else
	 {
        $mydepts = splitDeptListString(getAuthorisations($_SERVER['REMOTE_USER']));
		foreach(getdepts() as $k=>$v)
		  if(array_search($k, $mydepts)!==false)
		  //if(preg_replace('/, \./','',$mydepts)=='' || strpos($mydepts,$k)!==false)
		    echo "\n<option value=\"$k\">$v[NAME]</option>";
     }
		  ?>
		  </select></li>
	<?php
 }
 ?>
      </ol>
<!--	  </form> -->
      <li>Choose an entry to edit from a particular year: 
<?php
$yr = array();
$mydepts = trim(getAuthorisations($_SERVER['REMOTE_USER']));

echo "\n\n<!-- \$mydepts:\n" . print_r($mydepts, true) . "\n-->\n\n";

if(preg_replace('/[, \.]/', '', $mydepts)=='')
  $mydepts=array();
else
  $mydepts=explode(',', preg_replace('/^,*(.*),*$/', "$1", $mydepts));
$mydepts2 = array();
foreach($mydepts as $k=>$v)
  if(strlen($v)>0)
    $mydepts2[$k] = '&depts[]=' . trim($v);
$mydeptsquery = implode('', $mydepts2);
if(isGlobalAdmin($_SERVER['REMOTE_USER']))
  $mydeptsquery = '';

$yearsforlists = array();
foreach(getAllYears() as $y)
  $yearsforlists[$y] = ($y==9999?$config['inpressstring']:$y);
//$yearsforlists = array_reverse($yearsforlists);

?><select onchange="if(this.value!='') location.href='./?action=edit&years%5B%5D='+this.value+'<?php echo $mydeptsquery ?>'">
		  <option value=""> --- Choose a year --- </option>
<?php
$writeout = '';
foreach($yearsforlists as $y=>$yname){
    // $yr[] = "<a href=\"./?action=edit&years%5B%5D=$y$mydeptsquery\">$yname</a>";
	$writeout = "\n<option value=\"$y\">$yname</option>" . $writeout;
}
echo $writeout;
		  ?>
</select>
</li>
        <li>List all publications of type: <select onchange="if(this.value!='') location.href='./?action=pubtype&pubtype='+this.value">
		  <option value=""> --- Choose a type --- </option>
		  <?php
		  foreach(getPubTypes() as $k=>$v)
		      echo "\n<option value=\"$k\">$v</option>";
		  ?>
		  </select></li>
         <li><a href="javascript:jumpToPubid()">Edit a specific entry (via ID number)</a></li>
    </form>
<li><form action=duplicates/advancedmerge.php method=post>
Duplicate Check by ID Number: publication 1: <input type=text name="pubid[0]">
publication 2: <input type=text name="pubid[1]">
<input type=submit value="Go"></li>
</form></li>
  </ul>
    <script type="text/javascript">
      function jumpToPubid(){
        pubid=prompt('Enter publication ID number:');
	   if(pubid!=null && pubid!='' && pubid!='undefined')
	   {
	     location.href='./?action=edit&pubid='+pubid;
	   }
	 }
    </script>
</div>
  <h2>User management</h2>
  <ul>
<?php
$waiters = getNewUsersWaitingForApproval($_SERVER['REMOTE_USER']);
if(sizeof($waiters)>0)
  echo "    <li style=\"font-size: larger; font-weight: bold;\"><a href=\"./?action=approvepeople\">Approve users</a> - " 
                . sizeof($waiters) 
                . (sizeof($waiters)==1 ? ' person has' : ' people have')
				. " signed up to the system, and identified themselves as belonging to your department(s). Please confirm the addition(s).</li>";
?>
    <li><?php addnewpersonformwithdept(null, $mydepts); ?></li>
	<li><a href="./?action=pubsadmins">List of publications administrators</a></li>
	<li><a href="./?action=listallusers">List all registered users in the database</a></li>
    <li><a href="./?action=querylog">Query the log of past user actions</a></li>
  </ul>
  <h2>Bulk operations</h2>
  <ul>
      <li><a href="risimport/?admin=1">Import publications from <strong>RIS</strong> data file</a> (Please use
        <strong>Reference Manager</strong> to create RIS data files). <!-- <strong>N.B.
        This can be a slow process</strong> as the system checks for duplicates
        as it goes through the data, and will ask you to verify whether possible
        duplicates should be entered into the database. --></li>
      <li><a href="pubmedimport/">Import publications from <strong>PubMed</strong> summary file</a>. <strong>N.B.
        This can be a slow process</strong> as the system checks for duplicates
        as it goes through the data, and will ask you to verify whether possible
        duplicates should be entered into the database.</li>
    <li><a href="duplicates/">Check for duplicates</a> - 
      You will be presented one-by-one with possible duplicates, and then you can
      choose to delete one of the duplicates or to ignore the auto-detected match.<br />
	  <label>You can also perform this check for just one year:
	  <select onchange="if(this.value!='') location.href='duplicates/?year='+this.value">
		  <option value=""> --- Choose a year --- </option>
<?php
$writeout = '';
foreach($yearsforlists as $y=>$yname){
    // $yr[] = "<a href=\"./?action=edit&years%5B%5D=$y$mydeptsquery\">$yname</a>";
	$writeout = "\n<option value=\"$y\">$yname</option>" . $writeout;
}
echo $writeout;
		  ?>
</select></label>
</li>
    <li><a href="./?action=journaltitles">Journal titles</a> - View and edit journal titles used in your department(s)
	 (e.g. to ensure uniform naming)</li>
    <li><a href="./?action=issn&subaction=blankonly">Manage journals with no ISSN data</a> (type them in yourself)</li>
    <li><a href="./?action=issn&subaction=inconsistent">Manage inconsistent ISSN entries</a> (including entries where some are blank and some aren't) </li>
    <li><a href="./?action=userlesspubs">Unassociated entries</a> - Check for entries within your department(s)
	      which have not been connected to any particular user</li>
    <li><a href="./?action=browsedate">Browse entries by date of modification</a> - Entries listed according to
       their most recent date of creation/modification</li>
    <li><a href="./?action=deptstatus">Departmental &quot;status&quot;</a> - Are your departmental data up-to-date?</li>
  <?php
    if(isGlobalAdmin($_SERVER['REMOTE_USER']))
    {
     ?>
	 <li><a href="superadmin/">Sitewide admin features</a> - 
	   e.g. editing department codes, department names.</li>
     <?php
    }
  ?>
  </ul>
  <form method="post" action="<?php echo $config['pageshomeurl'] ?>manage/" class="automailchooser">
    Automatic emails for <?php echo $_SERVER['REMOTE_USER'] ?> are 
	<em><?php
  $q = "SELECT pleasedontautoemail FROM PUBLICATIONSADMINS WHERE userid='" . mysql_real_escape_string($_SERVER['REMOTE_USER']) . "' LIMIT 1";
  $res = mysql_query($q,connectpubsdb());
  if($res && $row = mysql_fetch_assoc($res))
  {
    $disabled = intval($row['pleasedontautoemail']);
  }
  echo ($disabled==0 ? 'enabled' : 'disabled');
	?></em>.
    <input type="hidden" name="action" value="automails" />
	<input type="hidden" name="disabled" value="<?php echo (1-$disabled) ?>" />
    <input type="submit" value="<?php echo ($disabled==0 ? 'Disable' : 'Enable'); ?>" <?php if($disabled==0) echo "onclick='return confirm(\"Are you sure?\\nPlease make sure that at least one person\\nmonitors submitted publications.\")'" ?>/>
  </form>
  <?php
  }
}

?>
