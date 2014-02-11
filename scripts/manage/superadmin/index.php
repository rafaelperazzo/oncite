<?php

/*
superadmin/index.php

This script provides all the sitewide admin features that only a site administrator
should ever use, such as:

- Globally change a department's code
- Change a department's name or its faculty

*/

require_once(dirname(dirname(__FILE__)) . '/config.inc.php');
require_once($config['homedir'] . 'manage/common.php');

// We turn error-reporting on, since this is only visible to superadmin and because it's important
error_reporting(E_ALL ^ E_NOTICE); 
echo "\n<p style='text-align:right;'>[<a href='$config[pageshomeurl]../'>Back to administration homepage</a>]</p>";

/*

FIRST OF ALL, ensure that the visitor has global admin privileges
 - we do this by checking their admin string is NOT FALSE
     and that it is equal to "" or "ALL" (i.e. not restricted to a single department)
*/
$auth = getAuthorisations($_SERVER['REMOTE_USER']);
if(!isGlobalAdmin($_SERVER['REMOTE_USER']))
{
  echo "<p>You are not recognised as a sitewide administrator.</p>";
  return;
}


$grab = array('action', 'subaction', 'deptid', 'facid', 'name', 'newid', 'deleteid', 'userid',
              'deladmin', 'newadmin', 'email', 'firstname', 'lastname', 'editadmin', 'title', 
		    'honorifics', 'userhasleft', 'otherdepts', 'origuserid', 'pubid');
foreach($grab as $g)
  $$g = $_REQUEST[$g];

$depts = getalldepts();
$facs = getallfaculties();

if($action!='')
  echo "[Click to return to <a href='./'>superadmin menu</a>]";
  
switch($action)
{
  case 'newdept':
		if(strlen($facid)>0 && strlen($name)>0 && strlen($deptid)>0)
		{
		// Set the name and faculty to whatever was chosen
		$q = "INSERT INTO DEPTS SET FACULTYID='"
					 . mysql_real_escape_string(stripslashes($facid)) . "', NAME='"
					 . mysql_real_escape_string(stripslashes($name)) . "', DEPTID='"
					 . mysql_real_escape_string(stripslashes($deptid)) . "'";
		$result = mysql_query($q, connectpubsdb());
	    // echo "<p>Error for query $q: " . mysql_error() . '</p>';

        if($result)
		{
		  ?><p>Department created.</p><?php
		}
		else
		{
		  ?><p>Sorry, there was an error while creating the department. Technical details: <?php 
		  echo mysql_error() ?></p><?php
		}

			if($result && strlen($newadmin)>3)
			{
			  // 'newadmin'==userid, 'deptid'==array of dept id codes
			  // print_r("DEPTID = " . $deptid);
			  $q = "INSERT INTO PUBLICATIONSADMINS SET userid='" 
							   . mysql_real_escape_string(stripslashes($newadmin)) . "', email='" 
							   . mysql_real_escape_string(stripslashes($email)) . "', deptslist='," 
							   . mysql_real_escape_string(stripslashes($deptid)) . ",'";
			  mysql_query($q, connectpubsdb());
			  // echo "<p>Error for query $q: " . mysql_error() . '</p>';
			  $q = "INSERT INTO USERS SET userid='" 
							   . mysql_real_escape_string(stripslashes($newadmin)) . "', email='" 
							   . mysql_real_escape_string(stripslashes($email)) . "', firstname='" 
							   . mysql_real_escape_string(stripslashes($firstname)) . "', lastname='" 
							   . mysql_real_escape_string(stripslashes($lastname)) . "' "
							   . (sizeof($deptid)==1 ? ", deptid='" 
							   . mysql_real_escape_string($deptid) . "', deptconfirmed=1" : '');
			  mysql_query($q, connectpubsdb());
			  // echo "<p>Error for query $q: " . mysql_error() . '</p>';
			}


		}
		else
		{
	?>
		<form method="post" action="./">
		<h3>New department <?php echo $deptid ?>:</h3>
		<p><label>Department code: 
	    <input name="deptid" type="text" value="" size="6" maxlength="8" /></label>
		</p>
		<p><label>Department name: 
		  <input type="text" name="name" value="" /></label>
		</p>
		<p><label>Belongs to faculty: 
		  <select name="facid">
		    <option value="0"<?php if($depts[$deptid]['FACULTYID']==0) echo " selected "?>>No faculty</option>
            <?php
			  foreach($facs as $fac)
			    echo "\n<option value='$fac[FACULTYID]'>$fac[TITLE]</option>";
			?>
	      </select></label>
</p>
		<p>If you wish to add a <strong>departmental publications administrator</strong> at the same time (optional):</p>
		<p><label>User ID: 
		  <input name="newadmin" type="text" value="" size="9" maxlength="7" /></label>
		  <label>First name: 
		  <input name="firstname" type="text" value="" size="12" maxlength="64" /></label>
		  <label>Last name: 
		  <input name="lastname" type="text" value="" size="24" maxlength="64" /></label>
		  <label>Email address: 
		  <input name="email" type="text" value="" size="32" maxlength="128" /></label>
		  </p>
		<p>
          <input name="action" type="hidden" value="newdept" />
		  <input type="submit" name="Submit" value="Submit">
		</p>
		</form>
   <?php
    }
    break;
  case 'globaldeptcode':
    // If data has been passed in, then carry out the desired action
    if(strlen($deleteid)>0)
	{
	  echo "<p>Intending to delete $deleteid</p>";
	  $deptid = preg_quote($deleteid);
	  $escdeptid = mysql_real_escape_string($deleteid);
	  $deptstrings = array();
	  $convstrings = array();
	  $q3 = "SELECT DISTINCT deptlist FROM PUBLICATIONS WHERE deptlist LIKE '%$escdeptid%'";
	  $res3 = mysql_query($q3, connectpubsdb());
	  while($row = mysql_fetch_assoc($res3))
	  {
	    $deptstrings[$row['deptlist']] = $row['deptlist'];
	  }
	  // Now we've fetched all the strings (e.g. "CK,FC,BB" or "BB" or "FF,BB,FE00" if we're looking for "BB")
	  // We need to change just the "BB" bit to the desired replacement (i.e. nothing!)
	  // We have to use regular expressions rather than ordinary string matching to make sure
	  //    that (for example) we can change the department code "BB" without touching the dept code "ABBA"
	  foreach($deptstrings as $k=>$v)
	  {
	    $deptstrings[$k] = preg_replace("|(?<![a-zA-Z0-9/])$deptid(?![a-zA-Z0-9/])|", '', $v);
        $convstrings[$k] = "UPDATE PUBLICATIONS SET deptlist='$deptstrings[$v]' WHERE deptlist='$k'";
 	    if($config['debug'])
  	      echo "\n<pre>$convstrings[$k]</pre>";
	    if($res = mysql_query($convstrings[$k], connectpubsdb()))
	      echo "\n<p class='happymessage'>Purged code from one portion of dept'l codes in PUBLICATIONS table. Affected " . mysql_affected_rows() . " rows.</p>";
	    else
	      echo "\n<p class='badmessage'><b>FAILED</b> to purge code from one portion of dept'l codes in PUBLICATIONS table. Error: " . mysql_error() . "</p>";
	  }
	} // End of code to delete a string from the data
    elseif(strlen($deptid)>0 && strlen($newid)>0)
	{
      echo "\n\n<div class='simplepaddedborder'>";
	  $escdeptid = preg_replace('|[^\w-/]|','',$deptid);
	  $escnewid =  preg_replace('|[^\w-/]|','',$newid);

$updatequeries = array(
'DEPTS' =>                     "UPDATE LOW_PRIORITY DEPTS              SET DEPTID='$escnewid' WHERE DEPTID='$escdeptid' LIMIT 1",
'USERS' =>                     "UPDATE LOW_PRIORITY USERS              SET deptid='$escnewid' WHERE deptid='$escdeptid'",
'OAISETLOOKUP' =>              "UPDATE LOW_PRIORITY OAISETLOOKUP       SET deptid='$escnewid' WHERE deptid='$escdeptid'",
'USERS (2ry depts)' =>         "UPDATE LOW_PRIORITY USERS              SET otherdepts=REPLACE(otherdepts, ',$escdeptid,', ',$escnewid,') WHERE otherdepts LIKE '%,$escdeptid,%'",
'USERS (2ry depts pending)' => "UPDATE LOW_PRIORITY USERS              SET otherdeptspending=REPLACE(otherdeptspending, ',$escdeptid,', ',$escnewid,') WHERE otherdeptspending LIKE '%,$escdeptid,%'",
'PUBLICATIONS' =>              "UPDATE LOW_PRIORITY PUBLICATIONS       SET deptlist=REPLACE(deptlist, ',$escdeptid,', ',$escnewid,') WHERE deptlist LIKE '%,$escdeptid,%'",
'PUBLICATIONS (pending)' =>    "UPDATE LOW_PRIORITY PUBLICATIONS       SET pendingdepts=REPLACE(pendingdepts, ',$escdeptid,', ',$escnewid,') WHERE pendingdepts LIKE '%,$escdeptid,%'",
'RAEADMINS' =>                 "UPDATE LOW_PRIORITY RAEADMINS          SET deptlist=REPLACE(deptlist, ',$escdeptid,', ',$escnewid,') WHERE deptlist LIKE '%,$escdeptid,%'",
'RAEGROUPS' =>                 "UPDATE LOW_PRIORITY RAEGROUPS          SET deptid='$escnewid' WHERE deptid='$escdeptid'",
'PUBLICATIONSADMINS' =>        "UPDATE LOW_PRIORITY PUBLICATIONSADMINS SET deptslist=REPLACE(deptslist, ',$escdeptid,', ',$escnewid,') WHERE deptslist LIKE '%,$escdeptid,%'"
);

      foreach($updatequeries as $qname=>$query) {
	   if($config['debug'])
	     echo "\n<pre>$query</pre>";
	   if($res = mysql_query($query, connectpubsdb()))
	   {
	     echo "\n<p class='happymessage'>Updated dept'l code in $qname. Affected " . mysql_affected_rows() . " rows.</p>";
	   }
	   else
	   {
	     echo "\n<p class='badmessage'><b>FAILED</b> to update dept'l code in $qname. Error: " . mysql_error() . "</p>";
	   }
      }
      // Now optimise the tables
      mysql_query("OPTIMIZE TABLE DEPTS, PUBLICATIONSADMINS , USERS, PUBLICATIONS ");
      mysql_query("OPTIMIZE TABLE RAEADMINS, RAEGROUPS");

      echo "\n</div>";
      $depts = refreshalldepts();
	}
	
    // Now collect up and display all the department codes
	?>
	<p>N.B. <strong>You must have Javascript enabled</strong> for this page to work correctly!</p>
	<script type="text/javascript">
	function recodePrompt(oldid)
	{
	  var newid = prompt("Please enter the new code to replace " + oldid + ":", oldid);
	  if(newid && (oldid!=newid) && confirm("Are you sure?\nThis is a global operation and cannot be undone:\n\n  Change " + oldid + " to " + newid))
	  {
	    location.href="./?action=<?php echo $action ?>&deptid=" + oldid.replace(/\+/g,"%2B") + "&newid=" + newid.replace(/\+/g,"%2B");
	  }
	}
	function purgePrompt(oldid)
	{
	  if(confirm("Are you sure?\nThis is a global operation and cannot be undone:\n\n  Delete " + oldid + " from all publication records.\n\nThis will NOT delete a departmental record - only remove references to it from publications."))
	  {
	    location.href="./?action=<?php echo $action ?>&deleteid=" + oldid.replace(/\+/g,"%2B");
	  }
	}
	</script>
	<?php
    $codes = array();
	foreach($depts as $k=>$v)
	  $codes[$k] = true;
	$res = mysql_query("SELECT DISTINCT deptlist FROM PUBLICATIONS ORDER BY deptlist", connectpubsdb());
	while($row = mysql_fetch_assoc($res))
	{
	  $temp = preg_split('/[, \.]/', trim($row['deptlist']));
	  foreach($temp as $code)
	    if(trim($code)!='')
	      $codes[trim($code)] = true;
	}
    echo "\n<ul>";
    foreach($codes as $code=>$truevalue)
	{
	  echo "\n  <li>$code ("
	   . (isset($depts[$code]) ? $depts[$code]['NAME']: '<b>Unknown department</b>')
	   . ") &middot; <a href='$config[scriptshomeurl]query/?depts[]=$code&group=year'>Look 'em up!</a>"
	   . " &middot; <a href='javascript:recodePrompt(\"$code\")'>Globally change this code</a>"
	   . (isset($depts[$code]) ? " &middot; <a href='javascript:purgePrompt(\"$code\")'>Globally purge this code from publications</a>" : '')
	   . "</li>";
	}	
    echo "\n</ul>";
	
    break;



  case 'deptattribs':
    $showoveralllist = true;
    // If data has been passed in, then carry out the desired action
    if(strlen($deptid)>0)
	{
	  if(strlen($facid)>0 && strlen($name)>0)
	  {
	    // Set the name and faculty to whatever was chosen
		$q = "UPDATE DEPTS SET FACULTYID='"
		           . mysql_real_escape_string($facid) . "', NAME='"
		           . mysql_real_escape_string($name) . "' WHERE DEPTID='"
		           . mysql_real_escape_string($deptid) . "' LIMIT 1";
        mysql_query($q, connectpubsdb());
	  }
	  else
	  {
	    // List the options to change for this department
		?>
		<form method="post" action="./">
		<h3>Editing department <?php echo $deptid ?>:</h3>
		<p>Department name: 
		  <input type="text" name="name" value="<?php echo htmlspecialchars($depts[$deptid]['NAME']) ?>" />
		</p>
		<p>Belongs to faculty: 
		  <select name="facid">
		    <option value="0"<?php if($depts[$deptid]['FACULTYID']==0) echo " selected "?>>No faculty</option>
            <?php
			  foreach($facs as $fac)
			    echo "\n<option value='$fac[FACULTYID]'"
				           . ($depts[$deptid]['FACULTYID']==$fac['FACULTYID']?' selected ':'')
						   . ">$fac[TITLE]</option>";
			?>
	      </select>
		</p>
		<p>
          <input name="action" type="hidden" value="deptattribs" />
          <input name="deptid" type="hidden" value="<?php echo htmlspecialchars($deptid) ?>" />
		  <input type="submit" name="Submit" value="Submit">
		</p>
		</form>
		<?php
		$showoveralllist = false;
	  }
	}

    $depts = refreshalldepts();
    // Now display all the department attributes
	// This array will group departments into faculties
$deptsperfac = groupDeptsByFaculties($depts, $facs);
/*
    $deptsperfac = array(0 => array());
    foreach($facs as $fac)
	{
	  $deptsperfac[$fac['FACULTYID']] = array(); // Initialise each element
	}
	foreach($depts as $k=>$v)
	{
	  if(isset($facs[$v['FACULTYID']]))
	    $deptsperfac[$v['FACULTYID']][] = $v;
	  else
	    $deptsperfac[0][] = $v; // Ones without faculty go in element zero
	}
*/	
	if($showoveralllist)
	{
		foreach($deptsperfac as $k=>$thisfac)
		{
		  if($k==0)
			echo "\n<h2>Departments with no assigned faculty</h2>";
		  else
			echo "\n<h2>" . $facs[$k]['TITLE'] . "</h2>";
		  echo "\n  <ol>";
		  foreach($thisfac as $d)
		  {
			echo "\n    <li>$d[NAME] &middot; <a href='./?action=$action&deptid=$d[DEPTID]'>Edit</a></li>";
		  }
		  echo "\n  </ol>";
		}
	}
	
    break;
  case 'admins':
    include_once($config['homedir'] . 'manage/superadmin/admins.php');
    break;
  case 'deluser':
    include_once($config['homedir'] . 'manage/superadmin/deluser.php');
    break;
  case 'user':
    include_once($config['homedir'] . 'manage/superadmin/user.php');
    break;
  case 'oaisets':
    include_once($config['homedir'] . 'manage/superadmin/oaisets.php');
    break;
  case 'oaiduplicates':
    include_once($config['homedir'] . 'manage/superadmin/oaiduplicates.php');
    break;
  case 'deptstrings':
    include_once($config['homedir'] . 'manage/superadmin/deptstrings.php');
    break;
  case 'mergeusers':
    include_once($config['homedir'] . 'manage/superadmin/mergeusers.php');
    break;
  case 'oaiimport':
    // $interactive = true; // Tell the script that it's interactive rather than automated mode
    unset($interactive); // Make sure the script doesn't think $interactive==n
    ?>
	<div style="border: 1px solid black; padding: 4px; margin: 4px;">
	<?php
    include_once($config['homedir'] . 'oai-harvest/index.php');
    ?>
	</div>
	<?php
    break;
  case 'oaiviewrecent':
    $q = "SELECT  notes, COUNT(*) AS count FROM PUBLICATIONS WHERE notes LIKE 'Imported via OAI%' AND (timestamp > DATE_SUB(CURDATE(), INTERVAL '3' MONTH)) GROUP BY notes";
	$res = mysql_query($q, connectpubsdb());
	if(!$res)
	  echo mysql_error();
	else
	{
	  $dates = array();
	  while($row = mysql_fetch_assoc($res))
	    $dates[$row['notes']] = $row['count'];
	  natcasesort($dates);
	  ?>
	  <h2>Recent OAI import batches</h2>
	  <p>These are identified by the text in the &quot;Notes&quot; field, which stores a record of the date/time of
	  import. Click on an entry in the list to view all the records which were imported.</p>
	  <ul>
	  <?php
	  foreach($dates as $adate => $acount)
	    echo "\n      <li>$adate: <a href=\"$config[pageshomeurl]manage/?action=querybynotes&q=" . rawurlencode($adate) . "\">$acount records</a></li>";
	  ?>
	  </ul>
	  <?php
	}
    break;
  case 'cntrl':
    require_once('cntrl.php');
    break;
  case 'homelesspubs':
    require_once('homelesspubs.php');
    break;
  case 'homelessusers':
    require_once('homelessusers.php');
    break;
  case 'keywords':
    require_once('keywords.php');
    break;
  case 'querylog':
    $usertype = "superadmin";
    require_once('querylog.php');
    break;
  case 'issn':
    require_once('issn.php');
    break;
  default:
    ?>
	<p>Please chooose an option:</p>
    <ol>
	<li>Department tools:</li>
	<ul class="publicationsul">
	  <li><a href="./?action=globaldeptcode">Globally edit departmental codes</a> (or just look at them)</li>
	  <li><a href="./?action=deptattribs">Edit a department's attributes</a> (such as name, faculty)</li>
	  <li><a href="./?action=admins">Add/remove publications administrators</a></li>
	  <li><a href="./?action=newdept">Add a new department</a></li>
    </ul>
	<li>User tools:
	  <ul>
         <li><a href="./?action=user">Manage user details</a> (includes deletion)</li>
         <li><a href="./?action=mergeusers">Merge two user accounts</a>
         - Do not run this function without the  agreement of RSR Support!</li>
 <!--        <li><a href="./?action=deluser">Delete a user</a></li>  -->
	  </ul>
	</li>
	<li><a href="./?action=querylog">Query the log of past user actions </a></li>
	<li>OAI-related tools:</li>
	<ul class="publicationsul">
	  <li><a href="./?action=oaiimport">Perform an OAI import right now</a> (this may take a couple of minutes)</li>
	  <li><a href="./?action=oaisets">Manage the OAI &quot;sets&quot; used during automated OAI import</a></li>
	  <li><a href="./?action=oaiviewrecent">View data imported over past 3 months</a></li>
	  <li><a href="./?action=oaiduplicates">Merge duplicate OAI records (with exact same OAI ID)</a></li>
    </ul>
	<li>Data cleaning:</li>
	<ul class="publicationsul">
	  <li><a href="./?action=deptstrings">Clean dept strings</a> (strings of codes stored in main PUBLICATIONS table)</li>
	  <li><a href="./?action=keywords">Manage keywords</a> (NB: slow)</li>
	  <li><a href="./?action=cntrl">Check titles for RefMan control characters</a></li>
	  <li><a href="./?action=homelessusers">Users without a department - assign a department to them</a></li>
	  <li><a href="./?action=homelesspubs">Publications with no department/user association - assign a department to them</a>
			 <br />Specific years:
			 <?php
		     $q = "SELECT year, COUNT(*) AS count FROM PUBLICATIONS WHERE (deptlist REGEXP '^[\.,]*$') AND (userlist REGEXP '^[\.,]*$') GROUP BY year ORDER BY year ASC";
			 $res = mysql_query($q, connectpubsdb());
			 while($row = mysql_fetch_assoc($res))
			   echo " &middot; <a href=\"./?action=homelesspubs&year=$row[year]\">" . ($row['year']==9999 ? $config['inpressstring'] : $row['year']) . "</a>($row[count])";

			 ?></li>
	</ul>
	<li>Manage ISSN / journal-name associations:</li>
	<ul class="publicationsul">
	  <li><a href="./?action=issn&subaction=fixissns">Fix ISSNs</a> (coerce to to uppercase, insert dash) </li>
	  <li><a href="./?action=issn&subaction=fixabbrevs">Fix journal abbreviations</a> (coerce to to uppercase with no punctuation) </li>
	  <li><a href="./?action=issn&subaction=issnfromabbrev">Deduce ISSN numbers from journal abbreviations</a></li>
	  <li><a href="./?action=issn&subaction=inconsistent">Manage inconsistent entries</a> (including entries where some are blank and some aren't) </li>
	  <li><a href="./?action=issn&subaction=blank">Manage entries with no ISSN data</a> (type them in yourself)</li>
	</ul>
  </ol>
	<?php
    break;
}



?>
