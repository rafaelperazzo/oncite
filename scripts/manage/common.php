<?php

// Common functions for the publications database stuff

require_once('config.inc.php');
// The above script fills the array "$config" with all the config details

if($config['downformaintenance'])
{
  ?>
    <div style="padding: 100px;">
	<h2>We're doing a little maintenance....</h2>
	<p>This information is currently unavailable, as our web database is being 
      upgraded. Please bear with us!</p>
	<p>This outage is not expected to last more than two hours. There is no need to contact 
	  the webmaster, as normal service will resume shortly.</p>
	</div>
  <?php
  exit();
}

// Make sure some of the data is grabbed from the environment into the scripts' scope
$getdatatoget = array('userid');
foreach($getdatatoget as $v)
  if(isset($_REQUEST[$v]))
    $$v = $_REQUEST[$v];

$universaldatabaseconnectionthingy = null;
function connectpubsdb()
{
  global $universaldatabaseconnectionthingy, $config;

  if($universaldatabaseconnectionthingy!=null)
  {
    mysql_select_db($config['db_db'], $universaldatabaseconnectionthingy);
    return $universaldatabaseconnectionthingy;
  }

  $dbcon = mysql_connect($config['db_addr'], $config['db_user'], $config['db_pass']);
  if($dbcon)
  {
    mysql_select_db($config['db_db'], $dbcon);
    $universaldatabaseconnectionthingy = $dbcon;
  }

  return $dbcon;
}
connectpubsdb(); // Need to force a connect, so that mysql_real_escape_string has something to work on!

$allusersauthorisations = array();
function authorisedForPubs($userid)
{
  return (getAuthorisations($userid)!==false);
}
function getAuthorisations($userid)
{
  global $allusersauthorisations, $config;
  if(isset($allusersauthorisations[$userid]))
    return $allusersauthorisations[$userid];

  $q = "SELECT deptslist FROM PUBLICATIONSADMINS WHERE userid='" . mysql_real_escape_string($userid) . "' LIMIT 1";
  $res = mysql_query($q,connectpubsdb());

  if(!$res)
    return false;
  elseif(mysql_num_rows($res)<1)
    $allusersauthorisations[$userid]=false;
  else
  {
    $row = mysql_fetch_assoc($res);
    $allusersauthorisations[$userid] = $row['deptslist'];	
  }
//  echo "Authorisations:";
//  print_r($allusersauthorisations[$userid]); // DELETE THIS!
  return $allusersauthorisations[$userid];
}
function isGlobalAdmin($userid)
{
  $auth = getAuthorisations($userid);
  if(!$auth) return false;
  return (strlen(trim(preg_replace('/(\bALL\b)|[\s,\.]/','',$auth)))==0);
}
function singleDeptAdmin($userid) // If is a single dept'l admin, returns their dept code. Otherwise FALSE.
{
  $auth = getAuthorisations($userid);
  if(strlen($auth)==0)
	return false;
  elseif(preg_match_all('/[^\s,\.]+/',$auth, $ignoreme)==1)
	return preg_replace('/[\s,\.]/','',$auth);
  else
    return false;
}
function getMyDepartments($userid) // Get array containing information about each department this user can admin for
{
  $depts = getalldepts();
  
  $auth = getAuthorisations($userid);
  if(preg_match_all('/^[\s,\.]*$/',$auth, $ignoreme)==1)
    return getalldepts(); // Easy for global administrators

  $ret = array();
  $deptcodes = splitDeptListString($auth); //preg_split('/[,\.]+/', $auth);
  foreach($deptcodes as $code)
    //if(strlen(trim($code))!=0)
	  $ret[trim($code)] = $depts[trim($code)];
  return $ret;
}
function splitDeptListString($deptlist){ // Splits list up, but also makes sure its unique and no blanks
  $deptcodes = preg_split('/[,\.]+/', $deptlist);
  foreach($deptcodes as $key=>$code)
    if(strlen(trim($code))==0)
	  unset($deptcodes[$key]);
  return array_values(array_unique($deptcodes));
}

function mergeDeptListString($arr){ // Merges an array into the canonical format for storage in a string field
  $ret = ','.implode(',', array_unique($arr)).',';
  return str_replace(',,', ',', $ret);
}

function deptListToDeptNames($deptlist){ // For displaying nice readable dept names - N.B. returns an array of names
  $l = splitDeptListString($deptlist);
  $alldepts = getalldepts();
  foreach($l as $k=>$code)
    $l[$k] = $alldepts[$code]['NAME'];
  return $l;
}

function recordLsUsersTransaction($function, $data)
{
  $q = "INSERT INTO USERSTRANSACTIONS SET userid='" . mysql_real_escape_string($_SERVER['REMOTE_USER']) .
              "', function='" . mysql_real_escape_string($function) .
			  "', data='" . mysql_real_escape_string($data) . "'";
  @mysql_query($q,connectpubsdb());
}



// FIXME: The following function seems to be deprecated
// - it should eventually be deleted
function processRISfile($f)
{
  $f = explode('ER  - ',$f);
  // echo "Processing " . sizeof($f) . " publications";
  $ret = array();
  foreach($f as $v)
  {
    if(trim($v)=='') continue;
    $new = processRISentry(trim($v));
    if($new)
      $ret[] = $new;
  }
  return $ret;
}

// FIXME: The following array is currently being moved to "risimport/index.php"
// - it should eventually be deleted
$ristranslation = array(
	'TY' => 'reftype',
//	'ID' => 'RefmanID',
	'A1' => 'authorlist',
	'AU' => 'authorlist',
	'T1' => 'title',
	'TI' => 'title',
	'CT' => 'title',
	'BT' => 'title',
	'T2' => 'secondarytitle',
	'T3' => 'seriestitle',
	'A2' => 'secondaryauthorlist',
	'ED' => 'secondaryauthorlist',
	'A3' => 'seriesauthorlist',
	'Y1' => 'year',
	'PY' => 'year',
	'N1' => 'notes',
	'AB' => 'notes',
	'N2' => 'abstract',
	'KW' => 'keywords',
	'JF' => 'journal',
	'JO' => 'journalabbrev',
	'JA' => 'journalabbrev',
	'J1' => 'journalabbrev',
	'J2' => 'journalabbrev',
	'VL' => 'volume',
	'IS' => 'issue',
	'CP' => 'issue',
	'SP' => 'startpage',
	'EP' => 'endpage',
	'CY' => 'city',
	'PB' => 'publisher',
	'SN' => 'issnisbn',
	'AD' => 'address',
  	'U1' => 'deptlist',
	'UR' => 'url',
	'M1' => 'medium',
	'M2' => 'medium',
	'M3' => 'medium',
	'U2' => 'U2',
	'U3' => 'U3',
	'U4' => 'U4',
	'U5' => 'U5',
	'AV' => 'availability',
    'Y2' => 'Y2' 
				);

function insertentry($data) // Used by RIS import, PubMed import, OAI import. However, for manual adding we use storepub() instead.
{
  global $config;
  $q = "INSERT INTO PUBLICATIONS SET ";
  $inserts = array("originator='" . mysql_real_escape_string($_SERVER['REMOTE_USER']) . "'");
  $deptlist = array();
  $pendingdepts = array();
  foreach($data as $k=>$v)
  {
/*
    if($k=='deptlist' && !is_array($v))
	  $inserts[] = "deptlist='," . mysql_real_escape_string($v) . ",'";
    elseif($k=='deptlist' && !preg_match('/^,.*,$/', implode(", ",$v)))
	  $inserts[] = "deptlist='," . mysql_real_escape_string(implode(", ",$v)) . ",'";
*/

    if($k=='deptlist'){
      if(is_array($v)){
	   foreach($v as $vv)
	     $deptlist = array_merge($deptlist, splitDeptListString($vv));
	 }else{
	   $deptlist = array_merge($deptlist, splitDeptListString($v));
	 }
    }elseif($k=='pendingdepts'){
      if(is_array($v)){
	   foreach($v as $vv)
	     $pendingdepts = array_merge($pendingdepts, splitDeptListString($vv));
	 }else{
	   $pendingdepts = array_merge($pendingdepts, splitDeptListString($v));
	 }
    }elseif($k=='reftype' && (substr(trim($v[0]),0,4)=='ICOM' || substr(trim($v[0]),0,4)=='ELEC'))
	  $inserts[] = "reftype='GEN'";
    elseif($k=='reftype' && substr(trim($v[0]),0,4)=='INPR')
	{
	  $inserts[] = "reftype='GEN'";
	  $inserts[] = "year='9999'";
	  unset($data['year']);
    }
	elseif($k!='ID' && $k!='RP' && sizeof($data[$k])>0)
	{
	  if(is_array($v))
	    $v = implode(", ",$v);
	  $inserts[] = "$k='" . mysql_real_escape_string($v) . "'";
    }
  }
  
  // The 'deptlist' and 'pendingdepts' fields are treated as a special case.
  // Any codes which aren't authorised for the current user must be moved to pendingdepts
  if($config['debug']){
    echo "<p>insertentry() processing of dept codes:<br />deptlist = ".mergeDeptListString($deptlist)
         ."<br />pending = ".mergeDeptListString($pendingdepts);
  }
  if(!isGlobalAdmin($_SERVER['REMOTE_USER'])){
    if(strlen($_SERVER['REMOTE_USER'])>0)
      $udepts = splitDeptListString(getAuthorisations($_SERVER['REMOTE_USER']));
    else
      $udepts = array(); // If unidentified user - e.g. an automated OAI import, no login.

    foreach($deptlist as $k=>$v){
      if(array_search($v, $udepts)===false){ // If we don't find the desired dept code in the authorisations...
	   //echo "<p>Didn't find $v in ".implode(',',$udepts)."</p>";
	   unset($deptlist[$k]); // ...take it off the approved...
	   $pendingdepts[] = $v; // ...and add it to the unapproved.
	 }
    }
  }
//  else echo "<br />$_SERVER[REMOTE_USER] is global admin, so no change.";
  if($config['debug']){
    echo "<br />deptlist = ".mergeDeptListString($deptlist)
         ."<br />pending = ".mergeDeptListString($pendingdepts).'</p>';
  }
  $inserts[] =     "deptlist='".mysql_real_escape_string(mergeDeptListString($deptlist    ))."'";
  $inserts[] = "pendingdepts='".mysql_real_escape_string(mergeDeptListString($pendingdepts))."'";
  
  $q .= implode(', ',$inserts);
/*  if($config['debug'])
  {
    echo "<!--\n\n";
	echo $q;
	echo "\n\n-->";
  }*/
  $success = mysql_query($q, connectpubsdb());
  if(!$success)
  {
    echo "<h3>Error while inserting entry.</h3><p>Query: $q</p><p>Error detail: " . mysql_error() ."</p>";
    return false;
  }
  recordtransaction('insertentry',mysql_insert_id());
  return true;
}

function entrytoris($e)
{
  global $mysqltoris;
  $r = '';
  foreach($e as $k=>$v)
    if(isset($mysqltoris[$k]) && strlen($v)>0)
	  $r .= $mysqltoris[$k] . '  - ' . $v . "\n";
  return $r . "\n";
}

function completeentrytable($e)
{
  $r = '<table border="1">';
  foreach($e as $k=>$v)
    $r .= "<tr><th>" . ucfirst($k) . "</th><td>$v</td></tr>";
  $r .= '</table>';
  return $r;
}
function compactentrytable($e)
{
  $r = '<table border="1">';
  foreach($e as $k=>$v)
    if(strlen(trim($v))>0)
      $r .= "<tr><th>" . ucfirst($k) . "</th><td>$v</td></tr>";
  $r .= '</table>';
  return $r;
}

/*
function compactdoubleentrytable($e, $f, $dboffset=0)
{
  global $year, $config;
  $alwaysmerge = !isGlobalAdmin($_SERVER['REMOTE_USER']); // Only global admins can genuinely delete
  $r = '<table border="1" width="100%" cellspacing="0">';
  foreach($e as $k=>$v){


   if($k=='deptlist'){
        $r .= "<tr><th>Department(s)</th><td align=\"center\">" 
		             . implode(', ', deptListToDeptNames($v)). "</td><td align=\"center\">" 
		             . implode(', ', deptListToDeptNames($f[$k])). "</td></tr>";
   }
   elseif($k=='pubid'){
        $r .= "<tr><th>Record ID</th><td align=\"center\">" 
		             . "<a href='$config[pageshomeurl]manage/?pubid=$v&action=search'>" . $v. "</a></td><td align=\"center\">" 
		             . "<a href='$config[pageshomeurl]manage/?pubid=$f[$k]&action=search'>" . $f[$k]. "</td></tr>";
   }
   elseif($k!='timestamp' && $k!='oaidatestamp' && $k!='U2' && $k!= 'approvaldate' 
   				&& $k!='chc' && $k!='approvedby' && $k!='yearmonth' && $k!='yearday'
   				&& $k!='Y2')
    if(strlen(trim($v))>0 || strlen(trim($f[$k]))>0  || $k=='keywords')
      if(trim($v)==trim($f[$k]))
        $r .= "<tr><th>" . ucfirst($k) . "</th><td colspan=\"2\" align=\"center\">$v&nbsp;</td></tr>";
      else
        $r .= "<tr><th>" . ucfirst($k) . "</th><td align=\"center\">$v&nbsp;</td><td align=\"center\">" 
		             . $f[$k] . "</td></tr>";
  }
  $r .= "<tr><td>Action to take:</td><td><ul>"
       . ($alwaysmerge?'':"<li><a href=\"./?ignore=$f[pubid]&delete=$e[pubid]"
              . ($alwaysmerge?"&merge=$f[pubid]":'')
	          . "&action=duplicates&year=$year&dboffset=$dboffset\">Delete this one</a></li>")
	   . "<li><a href=\"./?ignore=$f[pubid]&merge=$f[pubid]&delete=$e[pubid]&action=duplicates&year=$year&dboffset=$dboffset\">Merge this one into the other one</a>"
	   . " <em>[<a href='javascript:alert(\"Merging will delete this record, but will copy the departmental and user associations "
	   . "to the other record so that depts/authors still have ownership of the merged record. URL, keywords, and abstract are also "
	   . "copied to the other record where appropriate.\")'>Details</a>]</em></li>"
	   . "<li><a href=\"../?action=edit&pubid=$e[pubid]\">Edit this one</a></li></ul>"
	   .  '</td><td>'
	   . "<ul>"
	   . ($alwaysmerge?'':"<li><a href=\"./?ignore=$e[pubid]&delete=$f[pubid]"
              . ($alwaysmerge?"&merge=$e[pubid]":'')
	          . "&action=duplicates&year=$year&dboffset=$dboffset\">Delete this one</a></li>")
	   . "<li><a href=\"./?ignore=$e[pubid]&merge=$e[pubid]&delete=$f[pubid]&action=duplicates&year=$year&dboffset=$dboffset\">Merge this one into the other one</a> "
	   . "<em>[<a href='javascript:alert(\"Merging will delete this record, but will copy the departmental and user associations "
	   . "to the other record so that depts/authors still have ownership of the merged record. URL, keywords, and abstract are also "
	   . "copied to the other record where appropriate.\")'>Details</a>]</em></li>"
	   . "<li><a href=\"../?action=edit&pubid=$f[pubid]\">Edit this one</a></li></ul>"
	   . "</td></tr>";

    $r .= "<tr><td>Or:</td><td colspan='2'><p>If this is <b>not</b> actually a case of duplication, "
	   . "<a href=\"./?ignore=$f[pubid]&ignore2=$e[pubid]&action=duplicates&year=$year&dboffset=$dboffset\">click here to continue looking</a>.<br />(They will be marked as not being a duplicate pair.)</p>"
       . "<p>Or to ignore this pair but to store <strong>no decision</strong> for now, "
	   . "<a href=\"./?ignore=$f[pubid]&ignore2=$e[pubid]&decision=none&action=duplicates&year=$year&dboffset=$dboffset\">click here to continue</a>.</p>"
	   . "</td></tr>";


  $r .= '</table><p>&nbsp;</p>';
  return $r;
}
*/

function editableentrytable($e)
{
  $r = '<table border="1">';
  foreach($e as $k=>$v)
    $r .= "<tr><th>" . ucfirst($k) . "</th><td><input type=\"text\" name=\"$k\" value=\"$v\" size=\"100\" maxlength=\"255\" /></td></tr>";
  $r .= '</table>';
  return $r;
}

$universaldeptlist = array();
function getdepts()
{
  global $universaldeptlist;
  if(sizeof($universaldeptlist)>1) return $universaldeptlist;
  $res = mysql_query("SELECT * FROM DEPTS ORDER BY NAME", connectpubsdb());

  while($row = mysql_fetch_assoc($res))
    $universaldeptlist[$row['DEPTID']] = $row;
  return $universaldeptlist;
}
$universallsdeptlist = array();
function getlsdepts()
{
  global $universallsdeptlist;
  if(sizeof($universallsdeptlist)>1) return $universallsdeptlist;
  $res = mysql_query("SELECT * FROM DEPTS WHERE FACULTYID='1'", connectpubsdb());

  while($row = mysql_fetch_assoc($res))
    $universallsdeptlist[$row['DEPTID']] = $row;
  return $universallsdeptlist;
}

function getdeptsinfaculty($facultyid)
{
  $id = mysql_real_escape_string($facultyid);
  $res = mysql_query("SELECT * FROM DEPTS WHERE FACULTYID='$id'", connectpubsdb());
  while($row = mysql_fetch_assoc($res))
    $ret[$row['DEPTID']] = $row;
  return $ret;
}

$universalpeoplelist=null;
function getallpeople()
{
  global $universalpeoplelist;
  if($universalpeoplelist!=null)
    return $universalpeoplelist;

  $res = mysql_query("SELECT USERID, TITLE, FIRSTNAME, LASTNAME FROM USERS ORDER BY LASTNAME, FIRSTNAME", connectpubsdb());
  $ret = array();
  while($row = mysql_fetch_assoc($res))
    $ret[$row['USERID']] = $row;
  $universalpeoplelist = $ret;
  return $ret;
}

$universaldeptlist2=null;
function getalldepts()
{
  global $universaldeptlist2;
  if($universaldeptlist2!=null)
    return $universaldeptlist2;

  $res = mysql_query("SELECT * FROM DEPTS ORDER BY NAME", connectpubsdb());
  $ret = array();
  while($row = mysql_fetch_assoc($res))
    $ret[$row['DEPTID']] = $row;
  $universaldeptlist2 = $ret;
  return $ret;
}
function refreshalldepts()
{
  // If the info in getalldepts is stale, we can force a fresh grab from the database
  global $universaldeptlist2;
  $universaldeptlist2 = null;
  return getalldepts();

}
function getalldeptsnames()
{
  global $universaldeptlist2;
  if($universaldeptlist2!=null)
    return $universaldeptlist2;

  $res = mysql_query("SELECT * FROM DEPTS ORDER BY NAME", connectpubsdb());
  $ret = array();
  while($row = mysql_fetch_assoc($res))
    $ret[$row['DEPTID']] = $row['NAME'];
  $universaldeptlist2 = $ret;
  return $ret;
}

$universalfaclist=null;
function getallfaculties()
{
  global $universalfaclist;
  if($universalfaclist!=null)
    return $universalfaclist;

  $res = mysql_query("SELECT * FROM FACULTIES ORDER BY TITLE", connectpubsdb());
  $ret = array();
  while($row = mysql_fetch_assoc($res))
    $ret[$row['FACULTYID']] = $row;
  $universalfaclist = $ret;
  return $ret;
}

function specialcharslink($formname, $fieldname)
{
  global $config;
  $windowname = preg_replace('/[^a-z0-9]/', '', strtolower($formname.$fieldname));
  ?>
  <a href="<?php 
  // The URL MUST have the site removed so that window-to-window referral works even in HTTPS mode
  $reladdr = preg_replace('|^https?://.*?(?=/)|', '', $config['scriptshomeurl']);
  echo $reladdr; ?>special-characters.htm" style="font-size: small; font-style: italic;" onclick="<?php echo $windowname ?>=window.open('<?php echo $reladdr; ?>charpal.php?formname=<?php echo htmlspecialchars($formname); ?>&fieldname=<?php echo htmlspecialchars($fieldname); ?>&content='+document.getElementById('<?php echo htmlspecialchars($fieldname); ?>').value,'<?php echo $windowname ?>',config='height=400,width=600,top=300,left=100,resizable=yes,toolbar=no,status=no,location=no,menubar=no,scrollbars=yes,directories=no'); if(window.focus) {<?php echo $windowname ?>.focus();} return false;" title="Special characters"><img src="../chars.gif" alt="Special characters" border="0" align="absmiddle" /></a>

  <?php
}

function chooseNewPublicationTypeForm()
{
  global $userid, $config, $_REQUEST, $_SERVER;
  if(strlen($_REQUEST['returnurl'])>5)
    $returnurl = rawurlencode($_REQUEST['returnurl']);
  else
    $returnurl = rawurlencode($_SERVER['HTTP_REFERER']);
  if(strlen($_REQUEST['returnname'])>2)
    $returnname = rawurlencode($_REQUEST['returnname']);
  else
    $returnname = 'the page you came from';
  $p = getPubTypesAllData();
  $cols = 3;
  $rowspercol = ceil(sizeof($p)/$cols);
  $count = 1;
  ?>
  <form action="./" method="post" name="pubform" id="pubform">
  <table border="0"><tr><td valign="top" align="left">
  <ul>
    <?php
	foreach($p as $k=>$v)
	{
	  echo "\n  <li><a href=\"./?action=new&newpubtype=$k" 
	                  . (strlen($userid)>0?'&userid='.rawurlencode($userid):'')
					  . '&returnurl=' . $returnurl
					  . '&returnname=' . $returnname
					  . "\">" . htmlspecialchars($v['reftypename']) 
					  . "</a></li>";
	  if(($count++ % $rowspercol) == 0)
	    echo "\n </ul></td><td valign=\"top\" align=\"left\"><ul>";
    }
	?>
  </ul>
  </td></tr></table>
  </form>
  <?php
}

function singlePublicationForm($p)
{
  global $userid, $newpubtype, $config, $showapprovechoices;

  require('includes/singlePublicationForm.inc.php');
}

function storepub()
{
  global $_REQUEST, $userid, $chc, $config;
  $pubid = intval($_REQUEST['pubid']);
  // Take all the data you want from the environment, and tidy it up  
  $simpledatawanted = array('reftype','title','url','keywords','abstract',//'issnisbn',
 				'journal','startpage','endpage','volume','issue','publisher','chapter',
				'secondarytitle','seriestitle',
				'address','city','medium','yearmonth','yearday','yearother','notes','DOI','catkw');
  $insertclause = array();

  if(!($pubid>0)) // If we're storing a NEW publication rather than an edited one
  {
    $insertclause[] = "originator='" . mysql_real_escape_string(substr(trim($_SERVER['REMOTE_USER']),0,8)) . "'";
    if($chc)
	  $chcplease = true;
	$insertclause[]="userlist=('" . mysql_real_escape_string(substr(trim($userid),0,512)) . "')";

   // NEW-STYLE: Add the entry-approval data to the clause. This is mainly for safety, in case the user doesn't bother to submit the second form.
   if($sd = singleDeptAdmin($_SERVER['REMOTE_USER'])){
     if($_REQUEST['autoapprove']!='0'){
       $insertclause[] = "approvedby='" . mysql_real_escape_string($_SERVER['REMOTE_USER']) . "'";
       $insertclause[] = "deptlist='," . mysql_real_escape_string($sd) . ",'";
	}else{
       $insertclause[] = "pendingdepts='," . mysql_real_escape_string($sd) . ",'";
	}
   }elseif(!authorisedforpubs($_SERVER['REMOTE_USER'])){
     $res = mysql_query('SELECT deptid FROM USERS WHERE userid=\''.mysql_real_escape_string($_SERVER['REMOTE_USER']).'\' LIMIT 1', connectpubsdb());
	$row = mysql_fetch_assoc($res);
     $insertclause[] = "pendingdepts='," . mysql_real_escape_string($row['deptid']) . ",'";
     if($chcplease)
	  $insertclause[] = "chc='1'";
   }else{
     $insertclause[] = "approvedby='". mysql_real_escape_string($_SERVER['REMOTE_USER']) . "'";
   }

  }



  foreach($simpledatawanted as $v)
    if(strlen(trim($_REQUEST[$v]))>0)
	  $insertclause[] = "$v=('" . mysql_real_escape_string(stripslashes(trim($_REQUEST[$v]))) . "')";
    else
	  $insertclause[] = "$v=('')";

  // Author-lists are slightly different - we want to apply our own special tidaying
  foreach(array('authorlist', 'secondaryauthorlist', 'seriesauthorlist') as $v)
    if(strlen(trim($_REQUEST[$v]))>0)
	  $insertclause[] = "$v=('" . mysql_real_escape_string(tidyAuthorListString(stripslashes(trim($_REQUEST[$v])))) . "')";
    else
	  $insertclause[] = "$v=('')";



  // Now handle the year data
  $theyear = intval($_REQUEST['year']);
  if(substr(trim($_REQUEST['inpress']),0,1)=='y')
    $theyear=9999;
  elseif(preg_match('/^\d\d$/', $_REQUEST['year'])) // Two-digit year - expand it to a four-digit year!
  {
    $theyear = substr(date('Y'), 0, 2) .  $_REQUEST['year'];
	if($theyear > date('Y'))
	  $theyear -= 100;
  }
  elseif($theyear<1000 || $theyear > 4000)
    $theyear=9999;
  $insertclause[] = "year=('$theyear')";

  $issnisbn = fixIssnIsbn(mysql_real_escape_string($_REQUEST['issnisbn']));
  $insertclause[] = "issnisbn=('$issnisbn')";


  // Now for the Y2 data - which comes across as four separate fields Y2d, Y2m, Y2y, Y2o and becomes a RIS 1990/12/12/other string
  $Y2d = intval($_REQUEST['Y2d']);
  $Y2m = intval($_REQUEST['Y2m']);
  $Y2y = intval($_REQUEST['Y2y']);
  if($Y2d==0) $Y2d='';
  if($Y2m==0) $Y2m='';
  if(preg_match('/^\d\d$/', $_REQUEST['Y2y'])) // Two-digit year - expand it to a four-digit year!
  {
    $Y2y = substr(date('Y'), 0, 2) .  $_REQUEST['Y2y'];
	if($Y2y > date('Y'))
	  $Y2y -= 100;
  }
  elseif(intval($_REQUEST['Y2y']) > 1000)
    $Y2y = intval($_REQUEST['Y2y']);
  else
    $Y2y = '';
  $Y2o = mysql_real_escape_string(trim($_REQUEST['Y2o']));
  $insertclause[] = "Y2=('$Y2y/$Y2m/$Y2d/$Y2o')";

  $insertclause[] = "eprint=(" . intval($_REQUEST['eprint']) . ")";
  
  $insertclause = implode(', ',$insertclause);
  
  // Insert the information into the database
  if($pubid>0)
  {
    // Update something that currently exists
	$q = "UPDATE PUBLICATIONS SET $insertclause WHERE pubid='$pubid' LIMIT 1";
	$res = mysql_query($q, connectpubsdb());
    if($res)
	{
	  $ress = mysql_query("SELECT deptlist, userlist, pendingdepts, chc FROM PUBLICATIONS"
	  				. " WHERE pubid='$pubid' LIMIT 1",connectpubsdb());
      if($ress)
	  {
	    $temp = mysql_fetch_assoc($ress);
		$chc = $temp['chc'];
		$pendingdepts = $temp['pendingdepts'];
		$deptlist = $temp['deptlist'];
		$userlist = $temp['userlist'];
	  }
	}
  }
  else
  {
    // Insert new record
	$q = "INSERT INTO PUBLICATIONS SET $insertclause";
	$res = mysql_query($q, connectpubsdb());
    // Make sure to get from MySQL what the pubid was!
    if($res) $pubid = mysql_insert_id(connectpubsdb());
	$userlist = $userid;
  }
  if($config['debug'])
  {
    echo "<p>Debug info: \$insertclause:<br />$insertclause</p>";
  }
  recordtransaction('storepub',$pubid);

  if(!$res)
  {
  ?>
  <p>Sorry - there has been an error storing the publication information. Please use your browser's
  Back button to try again. If the error persists, <a href="mailto:<?php echo $config['webmasteremail'] ?>">contact 
  the webmaster</a>.</p>
  <p>Database query: <?php echo $q ?></p>
  <?php
  }
  else
  {  
  // Check out the "possible authors"
  $possibleauthors = getpossibleauthors(stripslashes($_REQUEST['secondaryauthorlist'] 
                                         . ',' . $_REQUEST['authorlist']
										 . ',' . $_REQUEST['seriesauthorlist']), $userlist);

  // But remove those who have already said they're nothing to do with it...
  $q = "SELECT userid FROM PUBLICATIONSNOTMINE WHERE pubid='$pubid'";
  if($res = mysql_query($q, connectpubsdb())){
    $notmines = array();
    while($row = mysql_fetch_assoc($res))
      $notmines[$row['userid']] = true;
    foreach($possibleauthors as $k=>$v){
      if($notmines[$v['userid']])
	   unset($possibleauthors[$k]);
    }
  }

  // Present a form for editing the personal and departmental associations
  ?>
<script type="text/javascript">
function checkAssocForm(f){ // This will check to make sure that at least some of the boxes are ticked!

  // Look at the group of checkboxes with name="deptassocs[]"
  // Also at the single checkbox with name="personalonly" id="personalonly" value="y" 

  var anythingticked = false;

  if(f.personalonly.checked){
    anythingticked = true;
  }else{
    for (var i=0;i<f.length;i++){
	  current = f.elements[i];
  	  if(current.name=='deptassocs[]' && current.checked)
	    anythingticked = true;
    }
  }

  // If (no departments are ticked) AND (the personalonly box is not ticked) then...
  if(!anythingticked)
  {
    // ...complain
    alert('Please tick a box to associate with at least one department.\n\nAlternatively, tick the box which confirms that this is a "non-<?php 
    echo htmlspecialchars($config['institutionname']); ?>" publication.');
    showDeptsPanel();
    return false;
  }

  return true;
} // End of checkAssocForm(f)
</script>
  <form action="./" method="post" name="peopleform" onsubmit="return checkAssocForm(this)">
<input type="hidden" name="returnurl" value="<?php echo htmlspecialchars($_REQUEST['returnurl']) ?>" />
<input type="hidden" name="returnname" value="<?php echo htmlspecialchars($_REQUEST['returnname']) ?>" />
<input type="hidden" name="autoapprove" value="<?php echo htmlspecialchars($_REQUEST['autoapprove']) ?>" />
<input name="action" type="hidden" value="storepubassocs" />
<input name="userid" type="hidden" value="<?php echo $userid ?>" />
<input name="pubid" type="hidden" value="<?php echo $pubid ?>" />
<?php

if($_REQUEST['reftype']=='JOUR' && empty($_REQUEST['url']) )
  echo "<div style=\"margin: 20px; padding: 20px; border: 1px green solid;\">Aha! You have entered a journal article, "
        . "without a web link.<br /><a href=\"" 
		. $config['scriptshomeurl'] . "manage/pubmedlocate/?title=" 
        . rawurlencode(stripslashes($_REQUEST['title'])) 
		. "&pubid=$pubid\" target=\"_blank\" onclick='window.open(\"" 
		. $config['scriptshomeurl'] 
		. "manage/pubmedlocate/?title=" 
        . rawurlencode(stripslashes($_REQUEST['title'])) 
		. "&pubid=$pubid\",\"pubmedlocator\",\"width=440,height=400,\"); return false'>Click here if "
		. "you'd like to perform an automatic <strong>PubMed</strong> lookup</a> to cross-reference "
		. "the entry to.</div>";


// If someone doesn't submit the ISSN, then let's try and suggest one.
if($_REQUEST['reftype']=='JOUR' && $_REQUEST['journal'] && empty($_REQUEST['issnisbn']) ){
  // Look to see if any matching ISSNs exist in the database - and suggest it if so.
  $issnq = "SELECT issnisbn FROM PUBLICATIONS WHERE reftype='JOUR' AND journal='".mysql_real_escape_string($_REQUEST['journal'])."' AND issnisbn<>'' LIMIT 20";
  $issns = array();
  if($issnres = mysql_query($issnq, connectpubsdb())){
    while($issneg = mysql_fetch_assoc($issnres)){
      $issns[$issneg['issnisbn']]++;
    }
  }
  if(sizeof($issns)>0){
    asort($issns);
    $issns = array_keys($issns);
    $issn = array_pop($issns); // OK, so now we have the most commonly-found ISSN
    echo "<div style=\"margin: 20px; padding: 20px; border: 1px green solid;\">Suggested ISSN: $issn <label><input type='checkbox' name='issn' value='"
             .htmlspecialchars($issn)."' checked='checked' />Add this code</label></div>";
  }  
} // End of ISSN checker


?>
<h2>Thank you. Please now associate the publication you have just entered with the appropriate people 
   and department(s).</h2>
  <h1>A<?php if(strpos($userlist,$_SERVER['REMOTE_USER'])!==false) echo "dditional a"; ?>uthors<!-- 
  to associate with <i><?php echo stripslashes($_REQUEST['title']); ?></i>--></h1>

<p>The following <?php echo $config['institutionname'] ?> people have been detected as possible matches to the 
authors/editors listed. Please 
confirm the ones which are genuine matches by ticking the boxes.</p>
<ul>
<?php
$allThemDepts = getalldepts();

$alreadymentioneddepts = array(); // List deptids here AS KEYS for which a tick-box has already been output

if(sizeof($possibleauthors)==0)
{
  echo "\n<p><i>(No matches found)</i></p>\n";
}
else{
  foreach($possibleauthors as $v){
    $tickthebox = strpos($userlist, $v['userid'])!==false;
    echo "\n  <li>" 
       . "<label><input type=\"checkbox\" name=\"associatetheseauthors[]\" value=\"$v[userid]\" "
	   . ($tickthebox?' checked="checked" ':'') 
	   . "onclick=\"if(document.getElementById && document.getElementById('deptassocs" 
	   . htmlspecialchars($v['userid']) 
	   . "')){document.getElementById('deptassocs" 
	   . htmlspecialchars($v['userid']) 
	   . "').checked=this.checked;}\""
	   . "/>"
	   . htmlspecialchars($v['title']) . " ". htmlspecialchars($v['firstname']) . " " 
	   . htmlspecialchars($v['lastname']) . " " . htmlspecialchars($v['honorifics']) 
	   . "</label> (" . htmlspecialchars($v['userid'])
	   . (strlen($v['NAME'])>0?' &middot; '
	          .
			//(!isset($alreadymentioneddepts[$v['deptid']]) ?
			   '<label><i>Department:<input type="checkbox" name="deptassocs[]" id="deptassocs' 
			             . htmlspecialchars($v['userid']) 
					   . '" value="' . htmlspecialchars($v['deptid']) 
					   . '" ' 
					   . (((strpos($deptlist,",$v[deptid],")!==false) || $tickthebox)?' checked="checked"':'') 
					   . ' onchange="if(this.checked && document.getElementById){document.getElementById(\'personalonly\').checked = false;}"/>' 
					   . htmlspecialchars($v['NAME']) . '</i></label>' 
			//	  : '<i>Department: '.htmlspecialchars($v['NAME']).'</i>')
	     :'');
    if($v['otherdepts'].$v['otherdeptspending']!=','){
      $ods = splitDeptListString($v['otherdepts'].$v['otherdeptspending']);
	 foreach($ods as $od){
        $alreadymentioneddepts[$od] = true;
	   echo '<label><i><input type="checkbox" name="deptassocs[]" id="deptassocs' 
			             . htmlspecialchars($v['userid']) 
					   . '" value="' . htmlspecialchars($od) 
					   . '" ' 
					   . (((strpos($deptlist,",$od,")!==false) || $tickthebox)?' checked="checked"':'') 
					   . ' onchange="if(this.checked && document.getElementById){document.getElementById(\'personalonly\').checked = false;}"/>' 
					   . htmlspecialchars($allThemDepts[$od]['NAME']) . '</i></label>';

	 }
    }
    echo ")"
	   . "</li>";
    $alreadymentioneddepts[$v['deptid']] = true;
  }
}
?>
</ul>
   <p>&nbsp;   </p>
   <h1>Departments<!-- to associate with <i><?php echo stripslashes($_REQUEST['title']); ?></i>--></h1>
<div id="clickToSeeDepartments" style="display:none; padding: 20px;">
<a href="javascript:showDeptsPanel()">Click here to edit <?php echo sizeof($alreadymentioneddepts)>0 ? ' additional ' : ' the ' ?> departments associated with this publication.</a>
</div>
<label><strong>If the entry should not appear</strong> in departmental/<?php 
echo $config['institutionname'] ?> listings (e.g. if published when employed outside of <?php 
echo $config['institutionname'] ?>), tick this 
box:<input type="checkbox" name="personalonly" id="personalonly" value="y" <?php

 if(preg_match('/^[\., ]+$/', $deptlist.$pendingdepts)) // If this entry has NO DEPARTMENTS AT ALL associated with it.
// if(!strlen(str_replace(',','',$deptlist)))
   echo "checked='checked' ";

?> /></label> <em>[<a target="_blank" href="<?php echo $config['authorguidanceurl'] ?>">Guidance</a>]</em>
<div id="departmentsChoosingPanel" style="display:block;">
   <p>You may make multiple selections on this list. <br />
   <span style="font-weight: bold; color:rgb(0,51,0);">Please ONLY select the 
   departments/centres which contain an 
   author/editor/creator of the work.</span>
</p>
<?php
$i=1;
$deptstochoose='';

// Get this person's own dept'l and faculty ID
$personaldeptid = '';
$res = @mysql_query("SELECT deptid FROM USERS WHERE userid='". mysql_real_escape_string($_SERVER['REMOTE_USER'])."' LIMIT 1",connectpubsdb());
if($res)
{
  $temp = mysql_fetch_assoc($res);
  $personaldeptid = $temp['deptid'];
}
$personalfacultyid = '';
$res = mysql_query("SELECT FACULTYID FROM DEPTS WHERE deptid='$personaldeptid' LIMIT 1",connectpubsdb());
if($res)
{
  $temp = mysql_fetch_assoc($res);
  $personalfacultyid = $temp['FACULTYID'];
}
if($config['debug'])
{
  echo "<p>DEBUG: personaldeptid = $personaldeptid</p>";
  echo "<p>DEBUG: personalfacultyid = $personalfacultyid</p>";
}
 

if(strlen(str_replace(',','',$deptlist)))
  $deptstochoose = ",$deptlist,";
elseif(!authorisedForPubs($_SERVER['REMOTE_USER']))
  $deptstochoose = ",$personaldeptid,";
elseif(singleDeptAdmin($_SERVER['REMOTE_USER']))
  $deptstochoose = ','.singleDeptAdmin($_SERVER['REMOTE_USER']).',';
// We DON'T want multi-dept admins to automatically have all their boxes ticked!
//elseif(strlen(str_replace(',','',getAuthorisations($_SERVER['REMOTE_USER']))))
//  $deptstochoose = getAuthorisations($_SERVER['REMOTE_USER']);
else
  $deptstochoose = '';

if($config['debug'])
{
  echo "<p>DEBUG: deptstochoose = $deptstochoose</p>";
}


// NOW: Go through the deptslist and add it to the deptsCentresOutput array. Also add the CHC to this array.
// deptsCentresOutput structure is [plain english name] => [HTML select icon]

$deptsCentresOutput = array();

// Add the DEPARTMENTS to the array
//$allThemDepts = getalldepts();
foreach($allThemDepts as $k => $v)
{
//  if(!isset($alreadymentioneddepts[$v['DEPTID']]))
    $deptsCentresOutput[$v['NAME']] =  "<label><input type=\"checkbox\" name=\"deptassocs[]\" value=\"$v[DEPTID]\""
               . (((strlen($deptstochoose)==0) || (strpos($deptstochoose,",$v[DEPTID],")===false))?'':' checked="checked"')
			   . " />" . ($v["FACULTYID"]==$personalfacultyid?"<b>$v[NAME]</b>":$v['NAME']) . "</label>";
}

// Add the CHC to the array
$deptsCentresOutput['CHC'] = "<label><input type=\"checkbox\" name=\"chc\" value=\"1\" " . ($chc?'checked="checked" ':'')
                       . "/><b>CHC (Centre for Human Communication)</b></label>";

// Sort the array by its keys
ksort($deptsCentresOutput);

// Output the array in a tabular format
$cols = 4;
$rows = ceil(sizeof($deptsCentresOutput)/$cols);
$deptsCentresOutput = array_values($deptsCentresOutput);
echo "\n<table>";
for($i=0; $i<$rows; $i++)
{
  echo "\n  <tr>";
  for($j=0; $j<$cols; $j++)
  {
    echo "\n    <td width=\"25%\">" . $deptsCentresOutput[$i+($j*$rows)] . "</td>";
  }
  echo "</tr>";
}
echo "</table>";

echo "\n<input type=\"hidden\" name=\"dummy\" value=\"" . time() . "\" />";
?>
</div>
<script type="text/javascript">
function showDeptsPanel()
{
  //if(document.getElementById)
  {
	document.getElementById("clickToSeeDepartments").style.display = 'none';
	document.getElementById("departmentsChoosingPanel").style.display = 'block';
  }
}
function hideDeptsPanel()
{
 // if(document.getElementById)
  {
	document.getElementById("clickToSeeDepartments").style.display = 'block';
	document.getElementById("departmentsChoosingPanel").style.display = 'none';
  }
}
hideDeptsPanel();
</script>
<p align="center"><input type="submit" value="Store associations"></p></form>
  <?php
  } // End of did-we-save-it-OK
}

function sortappropriateadmins($a, $b) // Puts departmental admins first, cross-dept admins next, then generic admins
{
  if( strlen(trim($a['deptslist'])) == strlen(trim($b['deptslist'])) )
    return 0;
  else if(trim($a['deptslist'])=='')
    return 10;
  else if(trim($b['deptslist'])=='')
    return -10;
  else
    return strlen(trim($a['deptslist'])) - strlen(trim($b['deptslist']));
}

function storepubassocs()
{
  global $_REQUEST, $userid, $config;

  if(!isset($userschosen))
    $userschosen = $_REQUEST['userschosen'];
  if(!isset($associatetheseauthors))
    $associatetheseauthors = $_REQUEST['associatetheseauthors'];
  if(is_array($associatetheseauthors))
    $associatetheseauthors = implode(',',$associatetheseauthors);
  if(strlen($associatetheseauthors)>0)
    $userschosen .= ",$associatetheseauthors";

  if(!isset($chc))
    $chc = $_REQUEST['chc'];
  if(!isset($pubid))
    $pubid = $_REQUEST['pubid'];
  if(!isset($deptassocs))
    $deptassocs = $_REQUEST['deptassocs'];

  $personalonly = $_REQUEST['personalonly']=='y';
  $autoapprove = $_REQUEST['autoapprove']=='1' || strlen($_REQUEST['autoapprove'])==0;
  $issn = mysql_real_escape_string($_REQUEST['issn']);
  
  $userlist = preg_replace('/[^\w,]/','',$userschosen);
  $chc = intval($chc);
  $pubid = intval($pubid);
  $deptlist = $deptassocs;

  // NEW approval method - we need to find out what the current approval/nonapprovals are in order to determine the new ones
  $res = mysql_query("SELECT deptlist, pendingdepts FROM PUBLICATIONS WHERE pubid=$pubid LIMIT 1", connectpubsdb());
  if(!$res) return false;
  $p = mysql_fetch_assoc($res);
  $pendingdepts = splitDeptListString($p['pendingdepts']);
  $livedepts = splitDeptListString($p['deptlist']);
  $authdepts = splitDeptListString(getAuthorisations($_SERVER['REMOTE_USER']));
  $isglobaladmin = isGlobalAdmin($_SERVER['REMOTE_USER']);

  if($config['debug']){
    echo "<pre>storePubAssocs():
	INPUT:
	      \$deptlist: ,".mergeDeptListString($deptlist).",
	     \$authdepts: ,".mergeDeptListString($authdepts).",
	  \$pendingdepts: ,".mergeDeptListString($pendingdepts).",
	     \$livedepts: ,".mergeDeptListString($livedepts).",
	  \$personalonly: $_REQUEST[personalonly]
	   \$autoapprove: $_REQUEST[autoapprove]
	   \$autoapprove: $autoapprove
	  </pre>";
  }

  $currentuserinfo = getUserInfo($_SERVER['REMOTE_USER']);

  if(is_array($livedepts))
  foreach($livedepts as $k=>$v){ // For each of the live depts...
    if(($isglobaladmin || array_search($v, $authdepts)!==false) 
                 && @array_search($v, $deptlist)===false){ // ...if we're authorised to modify this department but it ain't listed...
	 unset($livedepts[$k]); // ...remove it.
    }
  }
  if(is_array($pendingdepts))
  foreach($pendingdepts as $k=>$v){ // For each of the pending depts...
    if(($isglobaladmin || array_search($v, $authdepts)!==false || $v==$currentuserinfo['deptid']) 
                 && @array_search($v, $deptlist)===false){ // ...if we're authorised to modify this department OR IT'S OUR HOME DEPARTMENT but it ain't listed...
      unset($pendingdepts[$k]); // ...remove it.
	 // NB: Important to allow home dept users to unset this association since it's automatically set for them by storepub().
    }
  }

  if(is_array($deptlist))
  foreach($deptlist as $k=>$v){ // For each of the depts SUBMITTED in the form as being associated

    if(array_search($v, $livedepts)!==false)
      continue;  // Do nothing if already live

    if($autoapprove && ($isglobaladmin || array_search($v, $authdepts)!==false)){ // If we're authorised to modify this department AND we want to autoapprove
     // Make sure the code is added to the live list, and is not in the pending list
     $livedepts[] = $v;
	foreach($pendingdepts as $kk=>$vv)
	  if($vv==$v)
	    unset($pendingdepts[$kk]);
    }else{
      // Make sure the code is added to the pending list (because either we aren't authorised or we aren't autoapproving)
	 $pendingdepts[] = $v;
    }
  
  } // End of loop through submitted depts

  // New: For ordinary mortals (authors), any change automatically de-approves the record.
  if(!authorisedForPubs($_SERVER['REMOTE_USER'])){
    $pendingdepts = array_merge($pendingdepts, $livedepts);
    $livedepts = array();
  }

  $newpendingdepts = mysql_real_escape_string(mergeDeptListString($pendingdepts));
  $newlivedepts = mysql_real_escape_string(mergeDeptListString($livedepts));

  if($config['debug']){
    echo "<pre>storePubAssocs():
	OUTPUT:
	  \$newpendingdepts: $newpendingdepts
	     \$newlivedepts: $newlivedepts
	  </pre>";
  }

  $q = "UPDATE PUBLICATIONS SET userlist=('$userlist'), chc=($chc) ".(strlen($issn)>0 ? ", issnisbn='$issn' " : '')
           . ", deptlist=('" . ($personalonly ? ',' : $newlivedepts) . "') "
           . ", pendingdepts=('" . ($personalonly ? ',' : $newpendingdepts) . "') "
           . " WHERE pubid=$pubid LIMIT 1";
  $res = mysql_query($q, connectpubsdb());

  recordtransaction('storepubassocs',$pubid);
//  echo "\n\n<!-- \n$q \n\nThe query returned " . ($res?'true':'false') . "\n\n" . mysql_error() . "\n-->";
  ?>
 <div style="padding: 20px;">
  <p>Thankyou - the publication has been stored. Record ID number is <?php

  // Output the ID number
  echo $pubid.'.';

  /* 
  if(!authorisedForPubs($_SERVER['REMOTE_USER']))
  {
    $q = "SELECT approvedby FROM PUBLICATIONS WHERE pubid=$pubid LIMIT 1";
    $res = mysql_query($q, connectpubsdb());
    if($res)
    {
      $approvalness = mysql_fetch_assoc($res);
	  $approvalness = $approvalness['approvedby'];
    }
    else
      $approvalness = 'FAIL';
//    echo "[Approval code: $approvalness] ";
    if(!(strlen($approvalness)>4))
      echo "The publication will need to be verified by the appropriate departmental/faculty administrator(s) "
	   . "before it appears in the public listings. ";
  }else
  */
  if(sizeof($livedepts)>0)
	  echo " <a href='$config[pageshomeurl]index.php?action=search&pubid=$pubid'>Public view of the record.</a>";
  elseif(!$personalonly)
       echo " The publication will need to be verified by the appropriate departmental/faculty administrator(s) "
	   . "before it appears in the public listings. ";
  ?></p>
  
  <?php
  if(strlen($_REQUEST['returnurl'])>5 && strlen($_REQUEST['returnname'])>2)
  {
    ?>
    <p>You may wish to return to  
    <a href="<?php echo htmlspecialchars($_REQUEST['returnurl']) ?>"><?php echo htmlspecialchars($_REQUEST['returnname']) ?></a>.</p>
    <?php
  }
  else 
  if(authorisedForPubs($_SERVER['REMOTE_USER']))
  {
    ?>
    <p>You may wish to return to  
    <a href="<?php echo $config['pageshomeurl'] ?>manage/?dummy=<?php echo time() ?>">the publications management homepage</a>.</p>
    <?php
  }
  if(strlen($userid>0))
  {
    ?>
    <p>You may wish to return to  
    <a href="<?php echo $config['pageshomeurl'] ?>personal/?userid=<?php echo $userid . '&dummy=' . time() ?>">your personal publications page</a>.</p>
    <?php
  }
  else
  {
    ?>
    <p>You may wish to return to  
    <a href="<?php echo $config['pageshomeurl'] ?>personal/?dummy=<?php echo time() ?>">your personal publications page</a>.</p>
    <?php
  }
  ?>
 </div>
  <p><strong>Or to add another</strong> new publication entry, choose one of the options below:</p>
  <?php
  chooseNewPublicationTypeForm();

}

function deletepub($pubid)
{
  global $userid, $config;
  $pubid = intval($pubid);

  $res = mysql_query("SELECT * FROM PUBLICATIONS WHERE pubid='$pubid' LIMIT 1",connectpubsdb());
  if(!$res)
    return false;
  $p = mysql_fetch_assoc($res);

  if(($p['originator']==$_SERVER['REMOTE_USER']) && ((preg_match('/^[\.,]*$/', $p['deptlist'])==1) || $p['approvedby']==''))
     return utterlyDeletePub($pubid); // People are allowed to delete their own publications if they're personal only, or if they're as-yet-unapproved 

  if(!authorisedForPubs($_SERVER['REMOTE_USER']))
    return dissociatepub($pubid);
  elseif(isGlobalAdmin($_SERVER['REMOTE_USER']))
    return utterlyDeletePub($pubid); // Global admins will always completely delete, since they're authorised for every dept
  else
  {
    // NEW deletion functionality - only deletes from the depts that the current user is admin for.
    // Will only completely delete if there are no departments left!
    // Has similarities to the new rejectPub() mechanism
    $pendingdepts = splitDeptListString($p['pendingdepts']);
    $livedepts = splitDeptListString($p['deptlist']);
    $udepts = splitDeptListString(getAuthorisations($_SERVER['REMOTE_USER']));

    // Now go through the two lists and remove any dept codes that this user is allowed to
    $numremoved = 0;
    foreach($pendingdepts as $k=>$v){
      if(array_search($v, $udepts)!==false){
        unset($pendingdepts[$k]);
        $numremoved++;
      }
    }
    foreach($livedepts as $k=>$v){
      if(array_search($v, $udepts)!==false){
        unset($livedepts[$k]);
        $numremoved++;
      }
    }
    // At end of loop, if removals have occurred and list of depts is completely empty, allow total deletion.
    if(($numremoved!=0) && sizeof($pendingdepts)==0 && sizeof($livedepts)==0){
      return utterlyDeletePub($pubid);
    }else{ // Otherwise simply update the list of pending depts
      $res = mysql_query('UPDATE PUBLICATIONS SET pendingdepts=\',' . mysql_real_escape_string(implode(',', $pendingdepts)) 
	            . ',\',deptlist=\',' . mysql_real_escape_string(implode(',', $livedepts)) 
			  . ",' WHERE pubid=$pubid LIMIT 1", connectpubsdb());

      if($config['debug']){
        echo '<br />After deletion (really, removal of relevant depts):<br />Pending list was ' 
	                  . $p['pendingdepts'] 
				   . '<br />Approved list was ' 
				   . $p['deptlist']
				   . '<br />Pending list is ' 
	                  . htmlspecialchars(implode(',', $pendingdepts)) 
				   . '<br />Approved list is ' 
				   . htmlspecialchars(implode(',', $livedepts));
      }
	 return $res;
    }

  }
}

function utterlyDeletepub($pubid) // Don't call this function - use deletePub() instead, which will pass to this if appropriate. This function performs NO checks of user authentication etc
{

  // I REPEAT - DON'T EVER CALL THIS FUNCTION. CALL deletePub() INSTEAD, WHICH WILL MAKE THE NECESSARY CHECKS.

  global $userid, $config;
  $pubid = intval($pubid);

  // Make sure that the record doesn't exist in the RAEMYPUBS table (if indeed that table exists)
  $q = "SELECT 1 FROM RAEMYPUBS WHERE pubid=$pubid LIMIT 1";
  $res = mysql_query($q, connectpubsdb());
  if($res && (mysql_num_rows($res)!=0)){
    echo "<p>Unable to delete record - it is listed in an RSR personal record. Use duplicate-merge options instead, if appropriate.</p>";
    return false;
  }


    if(@mysql_query("DELETE FROM PUBLICATIONS WHERE pubid='$pubid' LIMIT 1",connectpubsdb()))
    {
      recordtransaction('deletepub',$pubid);
      ?>
	  <h2>Publication entry #<?php echo $pubid ?> deleted</h2>
   	  <p>Thank you - the publication has been deleted.</p>
	  <ul>
		<?php if(strlen($_GET['returnurl'])>0) {echo "<li><a href=\"" . urldecode($_GET['returnurl']) . "\">Back to the previous screen</a></li>";} ?>
	    <li><a href="./?userid=<?php echo $userid . '&dummy=' . time() ?>">Your personal 
		  publications page</a></li>
	  </ul>
  	  <?php
	  return true;
    }
    else
    {
      ?>
	  <h2>Sorry</h2>
	  <p>Please accept our apologies - there has been a database error while trying to delete 
	  the publication entry. Please contact 
	  the <a href="mailto:<?php echo $config['webmasteremail'] ?>">webmaster</a> 
	  and quote the error message given below:</p>
	  <p><?php echo mysql_error() ?></p>
	  <?php
	  return false;
    }

}

function dissociatepub($pubid)
{
  global $userid, $config;
  $pubid = intval($pubid);
  $auth=false;
  if(authorisedForPubs($_SERVER['REMOTE_USER']))
    $auth = true;
  $res = @mysql_query("SELECT userlist FROM PUBLICATIONS WHERE pubid='$pubid' LIMIT 1",connectpubsdb());
  if($res)
  {
	$row = mysql_fetch_assoc($res);
	$userlist = $row['userlist'];
	if(strpos($userlist,$userid)!==false)
	  $auth=true;
  }
  if(!$auth)
  {
    ?>
	<h2>Sorry</h2>
	<p>Your user ID doesn't appear to be listed with this publication (i.e. you can't dissociate yourself 
	from it since you're not associated with it!). If this is in error, 
	please contact the <a href="mailto:<?php echo $config['webmasteremail'] ?>">webmaster</a> 
	to fix the mistake.</p>
	<?php
    return;
  }
  // Now change the userlist so it doesn't include our user
  $userlist = mysql_real_escape_string(str_replace(',,',',',str_replace($userid,'',$userlist)));
  if(@mysql_query("UPDATE PUBLICATIONS SET userlist='$userlist' WHERE pubid='$pubid' LIMIT 1",connectpubsdb()))
  {
    mysql_query("INSERT INTO PUBLICATIONSNOTMINE SET pubid='$pubid', userid='"
         .mysql_real_escape_string($userid)."'", connectpubsdb());
    recordtransaction('dissociate', $pubid);
    ?>
	<h2>Publication entry #<?php echo $pubid ?> dissociated</h2>
	<p>Thank you - the publication has been removed from your listings. You may wish to return to  
  <a href="./?userid=<?php echo $userid . '&dummy=' . time() ?>">your personal publications page</a>.</p>
	<?php
  }
  else
  {
    ?>
	<h2>Sorry</h2>
	<p>Please accept our apologies - there has been a database error while trying to dissociate 
	the publication entry. Please contact 
	the <a href="mailto:<?php echo $config['webmasteremail'] ?>">webmaster</a> 
	and quote the error message given below:</p>
	<p><?php echo mysql_error() ?></p>
	<?php
  }
}

function recordtransaction($action, $pubid)
{
  global $config;
  mysql_query("INSERT INTO PUBLICATIONSTRANS SET userid='" . mysql_real_escape_string($_SERVER['REMOTE_USER']) . "', "
             . "ipaddress='" . mysql_real_escape_string($_SERVER['REMOTE_ADDR']) 
			 . "', action='" . mysql_real_escape_string($action) 
			 . "', pubid='" . mysql_real_escape_string($pubid) . "'",connectpubsdb());
}

function addnewperson($userid, $title, $firstname, $lastname, $honorifics)
{
  return addnewpersonwithdept($userid, $title, $firstname, $lastname, $honorifics, '', '');
}
function addnewpersonwithdept($userid, $title, $firstname, $lastname, $honorifics, $deptid, $email)
{
  $q = "INSERT INTO USERS SET userid='" . mysql_real_escape_string($userid) 
                         . "', title='" . mysql_real_escape_string($title) 
                         . "', firstname='" . mysql_real_escape_string($firstname)
						 . "', lastname='" . mysql_real_escape_string($lastname)
						 . "', email='" . mysql_real_escape_string($email)
						 . "', deptid='" . mysql_real_escape_string($deptid)
						 . (strlen($deptid)>0?"', deptconfirmed='1":'')
						 . "', honorifics='" . mysql_real_escape_string($honorifics) . "'";
  $res = @mysql_query($q, connectpubsdb());
  if($res)
  {
    recordtransaction("addnewperson:$userid",0);
    recordLsUsersTransaction('LS:addnewperson', $q);
  }
  return $res;
}

function updatename($userid, $title, $firstname, $lastname, $honorifics, $email)
{
  $q = "UPDATE USERS SET title='" . mysql_real_escape_string($title) 
                         . "', firstname='" . mysql_real_escape_string($firstname)
						 . "', lastname='" . mysql_real_escape_string($lastname)
						 . "', email='" . mysql_real_escape_string($email)
						 . "', honorifics='" . mysql_real_escape_string($honorifics) . "' WHERE userid='$userid' LIMIT 1";
  $res = mysql_query($q, connectpubsdb());
  recordtransaction('updatename',0);
  recordLsUsersTransaction('LS:updatename', $q);
  return $res;
}

function addnewpersonform($userinfo)
{
  addnewpersonformwithdept($userinfo, '');
}
function addnewpersonformwithdept($userinfo, $deptslist)
{
  global $config;
  $alldepts = getalldepts();

   // echo "\n\n<!-- \$deptslist:\n" . print_r($deptslist, true) . "\n-->\n\n";


  if(isGlobalAdmin($_SERVER['REMOTE_USER']))  // If a global administrator then let them do all
  {
/*
	$deptslistb = array();
    $res = mysql_query("SELECT deptid FROM DEPTS ORDER BY name", connectpubsdb());
	if($res)
	{
	  while($row = mysql_fetch_assoc($res))
	    $deptslistb[] = $row['deptid'];
	}
	
	*/
	$deptslistb = array_keys(getdepts());
//	print_r($deptslistb);
  }
  elseif(!is_array($deptslist))
    $deptslistb = splitDeptListString($deptslist);
  else
    $deptslistb = $deptslist;
  foreach($deptslistb as $k=>$v)
  {
    $deptslistb[$k] = trim($v);
	if(trim($v)=='')
	  unset($deptslistb[$k]);
  }

   echo "\n\n<!-- \$deptslistb:\n" . print_r($deptslistb, true) . "\n-->\n\n";
  ?>
<!--  <form id="newpersonform" onsubmit="idregex = /^\w{7}$/; if(!(this.userid.value.match(idregex))){alert('The Information Systems user ID is required.\n(It\'s a 7-character code such as \'ucabcde\'.)\n\nContact your departmental Computer Rep if you\nneed more information about user IDs.'); return false;} if(this.userid.value=='' || this.firstname.value=='' || this.lastname.value=='' || this.email.value=='') {alert('Required fields:\n\n - User ID\n - Forename(s)\n - Surname\n - Email address\n\nIf you don\'t know the person\'s user ID, contact your Computer Rep.'); return false;} else if(this.userid.value.length!=7){alert('The user ID must be the 7-character UCL user ID');return false;} return true;"> -->
  <form id="newpersonform" onsubmit="if(this.firstname.value=='' || this.lastname.value=='' || this.email.value=='') {alert('Required fields:\n\n - Forename(s)\n - Surname\n - Email address\n\nIf you don\'t know the person\'s user ID, contact your Computer Rep.'); return false;} else if(this.userid.value.length!=7){return confirm('Are you SURE you want to add a person without specifying their 7-character user ID?\n\nThis will prevent them from being able to manage their publications listing themselves.\n\nYour computer rep should be able to supply you with staff user IDs.');} return true;">
  <p><a name="addnewperson"></a>To add a new person, enter their <label><?php echo $config['institutionname'] ?> user ID:
    <input type="text" name="userid" maxlength="7">
</label> - and then <a href="javascript:revealnewpersondetails()">fill in the rest of their details</a>.
  </p>
  
  <div id="newpersondetails" style="margin: 5px 5px 5px 20px; padding: 1px; ">
  <label style="display: block; float: left;">Title: <br />
    <input name="title" type="text" value="<?php echo $userinfo['title']?>" size="<?php echo max(strlen($userinfo['title']),5)+1 ?>" maxlength="64"></label>
  <label style="display: block; float: left;">Forename(s): <br />
    <input name="firstname" type="text" value="<?php echo $userinfo['firstname']?>" size="<?php echo max(strlen($userinfo['firstname']),5)+3 ?>" maxlength="128"></label>
  <label style="display: block; float: left;">Surname: <br />
    <input name="lastname" type="text" value="<?php echo $userinfo['lastname']?>" size="<?php echo max(strlen($userinfo['lastname']),5)+3 ?>" maxlength="128"></label>
  <label style="display: block; float: left;">Honorifics: <br />
    <input name="honorifics" type="text" value="<?php echo $userinfo['honorifics']?>" size="<?php echo max(strlen($userinfo['honorifics']),5)+2 ?>" maxlength="128"></label>
  <label style="display: block; float: left;">Email: <br />
    <input name="email" type="text" value="<?php echo $userinfo['email']?>" size="<?php echo max(strlen($userinfo['email']),15)+2 ?>" maxlength="128"></label>
<?php
	  if(sizeof($deptslistb)>1)
	  {
?>
  <label style="display: block; float: left;">Home department: <br />
	 <select name="deptid">
	 <?php
		foreach($deptslistb as $v)
		  echo '<option value="' . htmlspecialchars($v) . '">' . htmlspecialchars($alldepts[$v]['NAME']) . '</option>'; 
	 ?>
	 </select>
    </label>
<?php
	  }
	  elseif(sizeof($deptslistb)==1)
	    echo '<input type="hidden" name="deptid" value="' . htmlspecialchars($deptslistb[0]) . '"/>';
?>
  <label style="display: block; float: left;">Additional department(s) (if jointly-appointed): <br />
    <select name="otherdepts[]" id="otherdepts[]" size="2" multiple="multiple"><option value="" selected="selected">No additional departments</option>
      <?php
		foreach($alldepts as $k=>$v)
		  echo '<option value="' . htmlspecialchars($k) . '">' . htmlspecialchars($v['NAME']) . '</option>'; 
      ?>
    </select>    </label>
    <input type="submit" value="Add new person" style="display: block;clear:both;"/>
    <input type="hidden" name="action" value="newperson" />
   </div>
  
<!--
  <table border="0" cellspacing="0" cellpadding="0" align="center" id="newpersondetails">
    <tr>
      <th scope="col">Title</th>
      <th scope="col">Forename(s)</th>
      <th scope="col">Surname</th>
      <th scope="col">Honorifics</th>
    </tr>
    <tr>
      <td><input name="title" type="text" value="<?php echo $userinfo['title']?>" size="<?php echo max(strlen($userinfo['title']),5)+1 ?>" maxlength="64">
      </td>
      <td><input name="firstname" type="text" value="<?php echo $userinfo['firstname']?>" size="<?php echo max(strlen($userinfo['firstname']),5)+3 ?>" maxlength="128">
      </td>
      <td><input name="lastname" type="text" value="<?php echo $userinfo['lastname']?>" size="<?php echo max(strlen($userinfo['lastname']),5)+3 ?>" maxlength="128">
      </td>
      <td><input name="honorifics" type="text" value="<?php echo $userinfo['honorifics']?>" size="<?php echo max(strlen($userinfo['honorifics']),5)+2 ?>" maxlength="128">
      </td>
    </tr>
    <tr>
      <th scope="col" colspan="2">Email address</th>
      <?php if(sizeof($deptslistb)>1) 
	     echo '<th scope="col" colspan="2">Home department</th>'; 
      ?>
      <th scope="col">&nbsp;</th>
    </tr>
    <tr>
      <td colspan="2"><input name="email" type="text" value="<?php echo $userinfo['email']?>" size="<?php echo max(strlen($userinfo['email']),15)+2 ?>" maxlength="128">
      </td>
      <?php
	  if(sizeof($deptslistb)>1)
	  {
	    echo '<td colspan="2"><select name="deptid">';
		foreach($deptslistb as $v)
		  echo '<option value="' . htmlspecialchars($v) . '">' . htmlspecialchars($alldepts[$v]['NAME']) . '</option>'; 
	    echo '</select></td>'; 
	  }
	  elseif(sizeof($deptslistb)==1)
	    echo '<input type="hidden" name="deptid" value="' . htmlspecialchars($deptslistb[0]) . '"/>';
	  ?>
      <td><input type="submit" value="Add new person">
          <input type="hidden" name="action" value="newperson" />
      </td>
    </tr>
    <tr>
      <th scope="col" colspan="2"></th>
      <th scope="col" colspan="2"><label for="otherdepts[]">Additional department(s) (if jointly-appointed)</label></th>
      <th scope="col">&nbsp;</th>
    </tr>
    <tr>
      <td colspan="2">
      </td>
      <?php
//	  if(sizeof($deptslistb)>1)
//	  {
	    echo '<td colspan="2"><select name="otherdepts[]" id="otherdepts[]" size="2" multiple="multiple"><option value="" selected="selected">No additional departments</option>';
//		foreach($deptslistb as $v)
//		  echo '<option value="' . htmlspecialchars($v) . '">' . htmlspecialchars($alldepts[$v]['NAME']) . '</option>'; 
		foreach($alldepts as $k=>$v)
		  echo '<option value="' . htmlspecialchars($k) . '">' . htmlspecialchars($v['NAME']) . '</option>'; 
	    echo '</select></td>'; 
//	  }
	  ?>
      <td></td>
    </tr>
  </table>
-->
<script type="text/javascript">
<!--
function revealnewpersondetails()
{
   if(document.getElementById)
   {
     document.getElementById("newpersondetails").style.display="block";
   }
}
if(document.getElementById)
  document.getElementById("newpersondetails").style.display="none";
//-->
</script>
  </form>
  <?php
}

$universalallknownjournals = array();
function getAllKnownJournals()
{
  global $universalallknownjournals, $config;
  if(sizeof($universalallknownjournals)>0)
    return $universalallknownjournals;

  $universalallknownjournals = array();
  $res = mysql_query("SELECT DISTINCT journal FROM PUBLICATIONS WHERE (NOT (journal=''))"
            . " AND (reftype='JOUR' OR reftype='JFUL') ORDER BY journal",connectpubsdb());
  if($res)
    while($row = mysql_fetch_assoc($res))
	  $universalallknownjournals[] = $row['journal'];

  return $universalallknownjournals;
}

$universalplentyknownjournals = array();
function getPlentyOfKnownJournals()
{
  global $universalplentyknownjournals, $config;
  if(sizeof($universalplentyknownjournals)>0)
    return $universalplentyknownjournals;

  $universalplentyknownjournals = array();
  $res = mysql_query("SELECT DISTINCT journal FROM PUBLICATIONS WHERE (NOT (journal=''))"
            . " AND (reftype='JOUR' OR reftype='JFUL') ORDER BY journal LIMIT 200",connectpubsdb());
  if($res)
    while($row = mysql_fetch_assoc($res))
	  $universalplentyknownjournals[] = $row['journal'];

  return $universalplentyknownjournals;
}

function getAllUsersJournals($userid, $deptid='')
{
  $myjournals = array();
  $q = "SELECT DISTINCT issnisbn, journal FROM PUBLICATIONS WHERE journal<>'' "
//            . " AND (reftype='JOUR' OR reftype='JFUL') "
			. " AND (userlist LIKE '%" . mysql_real_escape_string($userid) . "%') "
//			. " ORDER BY pubid DESC "
//			. " LIMIT 50"
			;
  $res = mysql_query($q, connectpubsdb());
  if(!$res)
    return array();
	
  $numfound = mysql_num_rows($res);

  while($row = mysql_fetch_assoc($res))
    $myjournals[$row['issnisbn']] = $row['journal'];

  if($numfound<35){
    $temp = mysql_query("SELECT deptid FROM USERS WHERE userid='" . mysql_real_escape_string($userid) . "' LIMIT 1", connectpubsdb());
	if($temp && ($temp=mysql_fetch_assoc($temp)) && (($deptid=$temp['deptid'])!='')){
      $q = "SELECT DISTINCT issnisbn, journal FROM PUBLICATIONS WHERE journal<>'' AND issnisbn<>'' "
//            . " AND (reftype='JOUR' OR reftype='JFUL') "
			. " AND (deptlist LIKE '%," . mysql_real_escape_string($deptid) . ",%') "
//			. " ORDER BY pubid DESC "
			. " LIMIT " . (50 - $numfound)
			;
      $res = mysql_query($q, connectpubsdb());
      while($row = mysql_fetch_assoc($res))
        $myjournals[$row['issnisbn']] = $row['journal'];
    }
  }

  asort($myjournals);

  return array_unique($myjournals);
}

function getAllOriginatedJournals($userid)
{
  // This function is useful for getting a list of journals added/originated by a given administrator
  $myjournals = array();
  $q = "SELECT DISTINCT issnisbn, journal FROM PUBLICATIONS WHERE journal<>'' AND issnisbn<>'' "
//            . " AND (reftype='JOUR' OR reftype='JFUL') "
			. " AND (originator='" . mysql_real_escape_string($userid) . "') ORDER BY pubid DESC LIMIT 100";
  $res = mysql_query($q, connectpubsdb());
  if(!$res)
  {
    // echo mysql_error();
	return $myjournals;
  }
  $numfound = mysql_num_rows($res);

  while($row = mysql_fetch_assoc($res))
    $myjournals[$row['issnisbn']] = $row['journal'];

  if($numfound<95 && ($deptcode = singleDeptAdmin($userid)))
  {
    $q = "SELECT DISTINCT issnisbn, journal FROM PUBLICATIONS WHERE journal<>'' AND issnisbn<>'' "
//            . " AND (reftype='JOUR' OR reftype='JFUL') "
			. " AND (deptlist LIKE '%," . mysql_real_escape_string($deptcode) . ",%' "
			. " OR pendingdepts LIKE '%," . mysql_real_escape_string($deptcode) . ",%') "
			. " ORDER BY pubid DESC LIMIT " . (100 - $numfound);
    $res = mysql_query($q, connectpubsdb());
  }

  while($row = mysql_fetch_assoc($res))
    $myjournals[$row['issnisbn']] = $row['journal'];

  natcasesort($myjournals);

  return array_unique($myjournals);
}


$universalpubtypes = array();
function getPubTypes()
{
  global $universalpubtypes;
  if(sizeof($universalpubtypes)>0)
    return $universalpubtypes;

  $universalpubtypes = array();
  $res = mysql_query("SELECT reftype, reftypename FROM PUBLICATIONSREFTYPES ORDER BY reftypename", connectpubsdb());
  if($res)
    while($row = mysql_fetch_assoc($res))
	  $universalpubtypes[$row['reftype']] = $row['reftypename'];

  return $universalpubtypes;
}

$universalpubtypestwo = array();
function getPubTypesAllData()
{
  global $universalpubtypestwo;
  if(sizeof($universalpubtypestwo)>0)
    return $universalpubtypestwo;

  $universalpubtypestwo = array();
  $res = mysql_query("SELECT * FROM PUBLICATIONSREFTYPES ORDER BY reftypename", connectpubsdb());
  if($res)
    while($row = mysql_fetch_assoc($res))
	  $universalpubtypestwo[$row['reftype']] = $row;

  return $universalpubtypestwo;
}

/*

   DELETEME - This "help" function should be deleted since it's way way outdated. Relies on an outdated model of ref types.

$universalpubtypeshelp = array();
function getPubTypesHelp()
{
  global $universalpubtypeshelp;
  if(sizeof($universalpubtypeshelp)>0)
    return $universalpubtypeshelp;

  $universalpubtypeshelp = array();
  $res = mysql_query("SELECT reftype, helptext FROM PUBLICATIONSREFTYPES ORDER BY reftypename", connectpubsdb());
  if($res)
    while($row = mysql_fetch_assoc($res))
	  $universalpubtypeshelp[$row['reftype']] = $row['helptext'];

  return $universalpubtypeshelp;
}

*/


function getNumberWaitingForApproval($userid)
{
  global $config;
  $deptslistar = splitDeptListString(getAuthorisations($userid));

  $deptsquery = '(0';
  $deptsNEWquery = '(0';
  foreach($deptslistar as $v){
      $deptsquery .= " OR deptlist LIKE '%," . mysql_real_escape_string(trim($v)) . ",%'";
      $deptsNEWquery .= " OR pendingdepts LIKE '%," . mysql_real_escape_string(trim($v)) . ",%'";
  }
  $deptsquery .= ')';
  $deptsNEWquery .= ')';

  if(isGlobalAdmin($userid)){
    $q = 'SELECT COUNT(pubid) as num FROM PUBLICATIONS WHERE year!=9999 AND ('
//                  . ' ( approvedby=\'\' AND deptlist RLIKE \'[[:alnum:]]\' ) OR '
                  . ' pendingdepts RLIKE \'[[:alnum:]]\' '
					. ") "
					;
  }else{
    $q = 'SELECT COUNT(pubid) as num FROM PUBLICATIONS WHERE year!=9999 AND ('
  //                     . " (approvedby='' AND $deptsquery) OR "
				   . " $deptsNEWquery"
					. ')'
					;
  }

  if($config['debug'])
    echo "<p>$q</p>";

  $res = mysql_query($q,connectpubsdb());
  if($res)
  {
	$row = mysql_fetch_assoc($res);
	return intval($row['num']);
  }

  return false;
}

function getNewUsersWaitingForApproval($userid, $primaryonly=false)
{
  global $config;
  $deptslist = getAuthorisations($userid);
  if(!$deptslist) return array(); // If not authorised then no-one to approve, of course

  $deptslistar = explode(',', $deptslist);

  $deptsquery = '(0';
  $odeptsquery = '(0';
  foreach($deptslistar as $v)
    if(trim($v)!=''){
      $deptsquery .= " OR deptid='" . mysql_real_escape_string(trim($v)) . "'";
      $odeptsquery .= " OR otherdeptspending LIKE '%," . mysql_real_escape_string(trim($v)) . ",%'";
    }
  $deptsquery .= ')';
  $odeptsquery .= ')';

  $q = "SELECT * FROM USERS WHERE (deptconfirmed=0 AND (NOT (deptid='')) "
                    . (isGlobalAdmin($userid)?'':" AND $deptsquery")
					. ") "
					. ($primaryonly?'':
					    " OR (otherdeptspending<>','".(isGlobalAdmin($userid)?'':" AND $odeptsquery") . ")"
					  )
					. " ORDER BY lastname, firstname"
					;

  if($config['debug']) echo "<p>$q</p>";

  $res = mysql_query($q,connectpubsdb());
  if($res)
  {
    $ret = array();
	while($row = mysql_fetch_assoc($res))
	  $ret[$row['userid']] = $row;
	return $ret;
  }

  return array();
}

// Retrieve publications that a dept'l administrator needs to approve, based on a publication record's dept'l association
function getPubsWaitingForApproval($userid)
{
  global $config;
  $userid = mysql_real_escape_string($userid);
  $q = "SELECT deptslist FROM PUBLICATIONSADMINS WHERE userid='$userid'";
  $res = mysql_query($q,connectpubsdb());
  if($res && ($deptslist=mysql_fetch_assoc($res)))
    $deptslist = $deptslist['deptslist'];

  $deptslistar = explode(',', $deptslist);

  $deptsquery = '(0';
  $deptsNEWquery = '(0';
  foreach($deptslistar as $v)
    if(trim($v)!='')
	{
      $deptsquery .= " OR deptlist LIKE '%," . mysql_real_escape_string(trim($v)) . ",%'";
      $deptsNEWquery .= " OR pendingdepts LIKE '%," . mysql_real_escape_string(trim($v)) . ",%'";
	}
  $deptsquery .= ')';
  $deptsNEWquery .= ')';

//  $q = "SELECT PUBLICATIONS.*, UNIX_TIMESTAMP(PUBLICATIONS.timestamp) AS modified, USERS.deptid AS userdeptid FROM PUBLICATIONS "
//                    . " LEFT JOIN USERS ON (PUBLICATIONS.originator = USERS.userid) "
//					. " WHERE approvedby='' AND year!=9999"
//                   . (isGlobalAdmin($userid)?' AND deptlist RLIKE \'[[:alnum:]]\'':" AND ($deptsquery OR $depts2query)")
//					. " ORDER BY timestamp, title, authorlist"
//					;

  // The following queries include the OLD and NEW methods of checking for duplicates.
  // The "approvedby" version is deprecated and should be removed eventually, for speed improvement.
  if(isGlobalAdmin($userid)){
    $q = "SELECT *, UNIX_TIMESTAMP(timestamp) AS utstamp FROM PUBLICATIONS WHERE year!=9999 AND (pendingdepts RLIKE '[[:alnum:]]')"
					. " ORDER BY timestamp, title, authorlist"
					;
  }else{
    $q = "SELECT *, UNIX_TIMESTAMP(timestamp) AS utstamp FROM PUBLICATIONS WHERE year!=9999 AND ($deptsNEWquery)"
					. " ORDER BY timestamp, title, authorlist"
					;
  }


  if($config['debug']) echo "<p>$q</p>";

  $res = mysql_query($q,connectpubsdb());
  if($res)
  {
    $pubs = array();
    while($pub=mysql_fetch_assoc($res))
	  $pubs[] = $pub;
	return $pubs;
  }

  return false;
}

function approvePub($pubid)
{
  global $config;
  $pubid = intval($pubid);
  if($pubid<1 || !authorisedForPubs($_SERVER['REMOTE_USER'])) return false;

  // Get the list of departments administered by the user, as an array of dept codes
  if(!isGlobalAdmin($_SERVER['REMOTE_USER'])){
    $udepts = splitDeptListString(getAuthorisations($_SERVER['REMOTE_USER']));
  }

  // Find out which of those are in the list of pending codes
  $q = "SELECT pendingdepts, deptlist FROM PUBLICATIONS WHERE pubid=$pubid LIMIT 1";
  $res = mysql_query($q, connectpubsdb());
  if(!$res){
    return false;
  }
  $pubdata = mysql_fetch_assoc($res);
  $pdepts = $pubdata['pendingdepts'];
  $ldepts = $pubdata['deptlist'];
  
  if($config['debug']){
    echo "<p>Before approval:<br />Pending list is $pdepts<br />Approved list is $ldepts";
  }
  
  // Add the relevant pending codes to the live codes
  // ...and remove the relevant pending codes from the pending list
  if(isGlobalAdmin($_SERVER['REMOTE_USER'])){
    $ldepts = mergeDeptListString(splitDeptListString($pdepts.','.$ldepts)); // Neat way to combine uniquely all codes back into one string
    $pdepts = ',';
  }else{
    foreach($udepts as $deptcode){
      if(strpos($pdepts, ",$deptcode,")!==false){
        // Now remove it from pending and add it to live
  	   $pdepts = str_replace(",$deptcode,", ',', $pdepts);
  	   $ldepts .= "$deptcode,";
      }
    }
  }
  
  if($config['debug']){
    echo "<br />After approval:<br />Pending list is $pdepts<br />Approved list is $ldepts</p>";
  }
  
  // Store the OLD-STYLE (approvedby) and NEW-STYLE (pendingdepts vs deptlist) approval information
  $q = "UPDATE PUBLICATIONS SET approvedby=('" . mysql_real_escape_string($_SERVER['REMOTE_USER']) 
           . "'), pendingdepts=('" . mysql_real_escape_string($pdepts) 
           . "'), deptlist=('" . mysql_real_escape_string($ldepts) 
           . "'), approvaldate=(NOW()) WHERE pubid=$pubid LIMIT 1";
  $res = mysql_query($q,connectpubsdb());
  if($res)
    recordtransaction('approvepub',$pubid);
  else
    echo mysql_error();
  return $res;
}
/*
function approvePub($pubid)
{
  $pubid = intval($pubid);
  if($pubid<1 || !authorisedForPubs($_SERVER['REMOTE_USER'])) return false;
  
  $q = "UPDATE PUBLICATIONS SET approvedby=('" . mysql_real_escape_string($_SERVER['REMOTE_USER']) 
           . "'), approvaldate=(NOW()) WHERE pubid=$pubid LIMIT 1";
  $res = mysql_query($q,connectpubsdb());
  if($res)
    recordtransaction('approvepub',$pubid);
  return $res;
}
*/
function rejectPub($pubid, $msgtoauthor=''){ // Reject dept'l associations, and if no dept'l/user assocs are left, delete the record entirely and email the user to say so
  global $config;
  $pubid = intval($pubid);
  if($pubid<1 || !authorisedForPubs($_SERVER['REMOTE_USER'])) return false;
  
  $q = "SELECT * FROM PUBLICATIONS LEFT JOIN USERS ON (userid=originator) WHERE pubid=$pubid LIMIT 1";
  $res = mysql_query($q,connectpubsdb());
  if(!$res){echo mysql_error(); return false;}
    
  $p = mysql_fetch_assoc($res); // Fetch the publication (and user) data into an array

  $totallydelete = false;

  if($config['debug']){
    echo "<br />Before rejection:<br />Pending list is $p[pendingdepts]<br />Approved list is $p[deptlist]";
  }
  
  if(isGlobalAdmin($_SERVER['REMOTE_USER'])){
    $totallydelete = true; // Global admins, simply delete
  }else{
    $udepts = splitDeptListString(getAuthorisations($_SERVER['REMOTE_USER']));
    // NEW method for rejecting (uses "pendingdepts")
    // Go through list of pendingdepts and remove all of those that the current user is allowed to.
    // At the end, if deletions have been made and no depts remain, then we DELETE the record and inform the user.

    // Now go through each of the pendingdepts, removing them if the user is allowed to
    $pendingdepts = splitDeptListString($p['pendingdepts']);
    $numremoved = 0;
    foreach($pendingdepts as $k=>$v){
      if(array_search($v, $udepts)!==false){
        unset($pendingdepts[$k]);
        $numremoved++;
      }
    }
    // At end of loop, if removals have occurred and list of depts is completely empty, AND if there are no associated users, allow total deletion.
    if(($numremoved!=0) && sizeof($pendingdepts)==0 && (!preg_match('/\w/', $p['deptlist'])) && (!preg_match('/\w/', $p['userlist']))){
      $totallydelete = true;
    }else{ // Otherwise simply update the list of pending depts
      //echo "Updating pendingdepts list to " . mysql_real_escape_string(implode(',', $pendingdepts));
      $res = mysql_query('UPDATE PUBLICATIONS SET pendingdepts=\'' . mergeDeptListString($pendingdepts) . "' WHERE pubid=$pubid LIMIT 1", connectpubsdb());
      $totallydelete = false;

      if($config['debug']){
	   echo mysql_error();
        echo '<br />After rejection:<br />Pending list is ' . mergeDeptListString($pendingdepts);
      }
    }
  }

  if($totallydelete){
    if($config['debug'])
      echo "<p>Deleting entry completely</p>";
    $q = "DELETE FROM PUBLICATIONS WHERE pubid=$pubid LIMIT 1";
    $res = mysql_query($q,connectpubsdb());
    if($res){
      recordtransaction('rejectpub',$pubid);

      // At this point we might also like to email the originator
	 if($config['automailacads'] && ($_SERVER['REMOTE_USER'] != $p['originator'])){ // Assume that if it's the current user then they don't need emailing

				 $mailmessage = 'This is an automatic email to notify you that the publication you recorded in the\r\n'
				     . 'online publications database'
					. ' has been deleted by the staff member with user ID "' . $_SERVER['REMOTE_USER']
					. "\".\r\n\r\n"
					. ($msgtoauthor ? "The reason indicated was: \"" . $msgtoauthor . "\"\r\n\r\n": '')
//					. "The most common reason for this is the elimination of duplicate records in the database.\r\n\r\n"
					. "The details from the record are given below:\r\n\r\n";
				 foreach($p as $k=>$vv)
				   if($vv != '' && $vv != '0')
					 $mailmessage .= "\"$k\": $vv\r\n";
				 $mailmessage .= "\r\n-------\r\nSent by the online publications database\r\n"
						. $config['pageshomeurl'] . "\r\nSite administrator: " . $config['webmasteremail'] . "\r\n";
				 mail($v['originator'] . $config['emaildomain'], 
					'Automatic notification: publication removed from database', 
					$mailmessage, 
					'From: ' . $_SERVER['REMOTE_USER'] . $config['emaildomain'] . "\r\n" .
					'Cc: '   . $_SERVER['REMOTE_USER'] . $config['emaildomain']
					);
//                      echo "<p>Message that would have been sent to academic:\n\n$mailmessage</p>";


	 }

    }
  }
  return $res; // Will return result either from totaldelete, or from updatelisting
 
//  $q = "DELETE FROM PUBLICATIONS WHERE pubid=$pubid LIMIT 1";
//  $res = mysql_query($q,connectpubsdb());
//  if($res)
//    recordtransaction('rejectpub',$pubid);
//  return $res;
}

$universalyearslist = array();
function getAllYears()
{
  if(sizeof($universalyearslist)>0)
    return $universalyearslist;

  $q = "SELECT DISTINCTROW year FROM PUBLICATIONS ORDER BY year";
  $res = mysql_query($q,connectpubsdb());
  if(!$res)
    return false;

  while($row = mysql_fetch_assoc($res))
    $universalyearslist[] = $row['year'];

  return $universalyearslist;
}

/*   DELETEME - This function doesn't suit a multi-dept-admin environment
function renameJournal($from, $to)
{
  $q = "UPDATE PUBLICATIONS SET journal=('" . mysql_real_escape_string($to) . "') WHERE journal='" . mysql_real_escape_string($from) . "'";
  $res = mysql_query($q, connectpubsdb());
//  echo "\$res=$res; " . mysql_error() . "    " . $q;
  return mysql_affected_rows();
}
*/

$allthepubsadmins = array();
function getPubsAdmins()
{
  global $allthepubsadmins;
  if(sizeof($allthepubsadmins)>0)
    return $allthepubsadmins;
  $q = "SELECT PUBLICATIONSADMINS.*, UNIX_TIMESTAMP(PUBLICATIONSADMINS.lastautoemailed) as ulastautoemailed, "
     . "USERS.lastname, USERS.firstname FROM PUBLICATIONSADMINS "
     . "LEFT JOIN USERS USING (userid) "
	 . "ORDER BY USERS.lastname, USERS.firstname";
  $res = mysql_query($q, connectpubsdb());
  if(!$res) return false;
  while($row = mysql_fetch_assoc($res))
  {
/*

REMOVED: I've had to remove the ability to know how many pubs each admin has entered, because it was far far far too slow!

    $qq = "SELECT COUNT(1) FROM PUBLICATIONS WHERE originator='$row[userid]'";
	if(($resres = mysql_query($qq)) && $rowrow = mysql_fetch_row($resres))
	  $row['number_originated'] = $rowrow[0];
*/
    $allthepubsadmins[] = $row;
  }
  return $allthepubsadmins;
}

$usedkeywordsarray = array();
function getUsedKeywords($userid) // '' for all users
{
  global $usedkeywordsarray;
  if(isset($usedkeywordsarray[$userid]))
    return $usedkeywordsarray[$userid];
  $q = "SELECT DISTINCTROW keywords FROM PUBLICATIONS";
  if(strlen($userid)>0)
    $q .= " WHERE userlist LIKE '%" . mysql_real_escape_string($userid) . "%'";
  $res = mysql_query($q, connectpubsdb());
  if(!$res) return array();
  $newkeywords = array();
  while($row = mysql_fetch_assoc($res))
  {
    $row = explode(',', $row);
	foreach($row as $v)
	  $newkeywords[trim($v)] = true;
  }
  $usedkeywordsarray[$userid] = array_keys($newkeywords);
  return $usedkeywordsarray[$userid];
}

function lastnamesearch($ln, $initial, $userid)
{
  global $config;
  $searchstring = "authorlist RLIKE '[[:<:]]" 
        . mysql_real_escape_string($ln) 
	   . "[[:>:]],[[:space:]]*" 
	   . mysql_real_escape_string($initial) . "'";

  mysql_query("UPDATE USERS SET pubslastupdated=null WHERE userid='"
	             . mysql_real_escape_string($userid) . "' LIMIT 1", connectpubsdb());

  $notmine = array();
  $q = "SELECT pubid FROM PUBLICATIONSNOTMINE WHERE userid='$userid'";
  $res = mysql_query($q, connectpubsdb());
  if($res)
    while($row = mysql_fetch_assoc($res))
	  $notmine[] = "pubid='" . $row['pubid'] . "'";


  $q = "SELECT * FROM PUBLICATIONS WHERE ("
      . $searchstring
      . ") AND NOT (userlist LIKE '%$userid%')"
	  . (sizeof($notmine)>0?" AND NOT (" . implode(' OR ', $notmine) . ")":'');

  if($config['debug'])
    echo "<p>$q</p>";

  $res = mysql_query($q, connectpubsdb());

  if(!$res || mysql_num_rows($res)==0)
    return array();

  $finds = array();
  while($row = mysql_fetch_assoc($res))
    $finds[] = $row;

  return $finds;
}

function getpossibleauthors($text, $definites)
{
  // Split the text at commas, then assume that somewhere in there we have appropriate names
  $t = explode(',', $text);

  $defs = explode(',', $definites);
  $whereclauses = array();
  foreach($defs as $v)
    if(trim($v)!='')
      $whereclauses[] = "userid='" . mysql_real_escape_string(trim($v)) . "'";

  $matches = array();
  for($i=0; $i<(sizeof($t)-1); $i++)
  {
    $lastname = mysql_real_escape_string(trim($t[$i]));
	if($lastname=='')
	  continue;
	$initial = mysql_real_escape_string(substr(trim($t[$i+1]), 0,1));
	$whereclauses[] = "lastname LIKE '$lastname'"; // " AND firstname LIKE '$initial%'";
  }
  $q = "SELECT USERS.*, DEPTS.NAME FROM USERS LEFT JOIN DEPTS USING (deptid) WHERE (("
              . implode(') OR (', $whereclauses)
			  . ")) ORDER BY lastname, firstname";
  $res = mysql_query($q, connectpubsdb());
  if($res)
	while($row = mysql_fetch_assoc($res))
	  $matches[] = $row;
  
  return $matches;
}

function getpossibleauthorswithindept($text, $deptid)
{
  // Split the text at commas, then assume that somewhere in there we have appropriate names
  $t = explode(',', $text);

  $matches = array();
  $whereclauses = array();
  for($i=0; $i<(sizeof($t)-1); $i++)
  {
    $lastname = mysql_real_escape_string(trim($t[$i]));
	if($lastname=='')
	  continue;
	$initial = mysql_real_escape_string(substr(trim($t[$i+1]), 0,1));
	$whereclauses[] = "lastname LIKE '$lastname'"; // " AND firstname LIKE '$initial%'";
  }
  if(sizeof($whereclauses)==0)
    return array();
  $q = "SELECT * FROM USERS WHERE deptid='" . mysql_real_escape_string($deptid) . "' AND (("
              . implode(') OR (', $whereclauses)
			  . ")) ORDER BY lastname, firstname";
  $res = mysql_query($q, connectpubsdb());
  if($res)
	while($row = mysql_fetch_assoc($res))
	  $matches[] = $row;
  echo mysql_error();
  return $matches;
}

/*
The following function does this:
(1) Firstly it makes sure it only runs every 6 hours at most, by storing a datestamp in mysql
(2) It looks up all the publications which are unapproved, but were entered more than a day ago
(3) For each publication, it tries to work out who's the best person to prompt about this
(4) The admins chosen from point (3) are emailed - UNLESS they were emailed within the past three days
*/
function emailPromptsForAdmins()
{
  global $config;

  if(!$config['automailadmins']) // Do nothing unless automailing is turned on
    return;


  // Check to see if this function's been called recently - do nothing at all if so
  $res=mysql_query("SELECT UNIX_TIMESTAMP(ts) as 'then', UNIX_TIMESTAMP() as 'now' "
                  . " FROM PUBLICATIONSTIMESTAMPS WHERE name='lastemailprompt'", connectpubsdb());
  if($res && ($row=mysql_fetch_assoc($res)))
  {
    $diff = intval($row['now']) - intval($row['then']);
	if($diff<21600) // 6 hours = 21600 seconds
	  return; // We don't need to bother with this since someone else did it recently!
  }
  else
    return; // Failed to look up the info
  echo "<p align=\"center\">&middot;&middot;</p>";
  $admins = getPubsAdmins();
  usort($admins, 'sortappropriateadmins');
//  if($config['debug'])
//  {
//    echo "\n<!-- \nAll admins: sorted appropriately:\n";
//	print_r($admins);
//    echo "\n -->\n";
//  }
  
  // For each publication that needs approving...
  // ...decide who the best administrator to email is
  $res=mysql_query("SELECT originator, deptlist, pendingdepts, userlist, deptid "
				 . " FROM PUBLICATIONS "
                 . " LEFT JOIN USERS ON (PUBLICATIONS.originator = USERS.userid) "
				 . " WHERE year!=9999 "
				 . " AND (pendingdepts RLIKE '[[:alnum:]]' " // NEW METHOD for un-approval
				 //. " OR (approvedby='' AND deptlist RLIKE '[[:alnum:]]') " // OLD METHOD for un-approval - DEPRECATE THIS later
                 . ")"
			  . " AND PUBLICATIONS.timestamp<FROM_UNIXTIME(" . (time()-86400) . ")"
			  , connectpubsdb());
  $deptsadminstoemail = array(); // Will collect a set of user IDs
  if(!$res)
  {
    if($config['debug'])
	  echo "<p>MySQL error. Error: " . mysql_error() . "<br />Query: $q</p>";
    return; // Failed to look up the info
  }
  while($row=mysql_fetch_assoc($res))
  {
    if($config['debug'])
    {
	  echo "\n<!-- One row from the query which finds unapproved publications:\n" . print_r($row,true) . "\n-->";
    }
    
    // NEW method of deciding on approval emails - ALL the depts listed in "pendingdepts" need separately contacting
    if(preg_match('/\w/', $row['pendingdepts'])){ // Test of whether the field has any content
      $thesedepts = splitDeptListString($row['pendingdepts']);
      foreach($thesedepts as $dd)
      {
	   $deptsadminstoemail[] = trim($dd);
      } 
	 continue;
    }
    
    // OLD method of deciding on approval emails - by looking at originating user and/or deptlist
    // The following code can eventually be deprecated and SIMPLY DELETED without problem
    if(strlen(trim($row['deptid']))>0) // If the originator's department is listed
    {
	  $deptsadminstoemail[] = trim($row['deptid']);
	  continue;
    }
    $thesedepts = splitDeptListString($row['deptlist']);
    foreach($thesedepts as $dd)
    {
	 $deptsadminstoemail[] = trim($dd);
	 continue;
    } 
    // End of OLD method

    $deptsadminstoemail[] = 'anyone-nomatchesfound';
  }

  $deptsadminstoemail = array_unique($deptsadminstoemail);
  
  if($config['debug'])
     echo "\n\n<!-- The array \$deptsadminstoemail now looks like this:\n" . print_r($deptsadminstoemail, true) . "\n-->\n";

  // only email the administrators who haven't been emailed for the past three days
  $emailto = array();
  foreach($deptsadminstoemail as $k => $v)
  {
    foreach($admins as $kk => $vv)
	{
	  if($vv['pleasedontautoemail']!=0)
	    continue;

	  $timedelta = intval($vv['ulastautoemailed']) - (time()-604800);
	  if($config['debug'])
	  {
	    echo "\n\n<!--\n\nDiagnostics for emailpromptsforadmins():\n\n"
		    . "ID: $vv[userid]\nulastautoemailed: $vv[ulastautoemailed]\n   One week ago = " . (time()-604800)
			. "\nSo delta = $timedelta"
			. " -->";
	  }
	  if(intval($vv['pleasedontautoemail'])==0 && (strpos($vv['deptslist'], ",$v,")!==false || trim(str_replace(',', '', $vv['deptslist']))=='')
	            && ($timedelta<0))
	  {
        if(strlen($vv['email'])>0)
	      $emailto[] = stripslashes($vv['email']);
        else
	      $emailto[] = stripslashes($vv['userid']) . $config['emaildomain'];
	    mysql_query("UPDATE PUBLICATIONSADMINS SET lastautoemailed=NOW() WHERE userid='$vv[userid]' LIMIT 1", connectpubsdb());
		break;
	  }
    }
  }
  $emailto = array_unique($emailto);
  if($config['debug'])
  {
    echo "\n<!-- \nAll the departments we've decided to email:\n";
	print_r($deptsadminstoemail);
    echo "\n -->\n";
  }
  if($config['debug'])
  {
    echo "\n<!-- \nAll the email addresses we've decided to email:\n";
	print_r($emailto);
    echo "\n -->\n";
  }
  
  $msgtitle = '[Automatic msg:] Publications approval';
  $mainmsg = 'Hello,

This automatic email is to notify you that publications are waiting 
on the publications database to be authorised/rejected. You can 
view the publications by logging directly in to the following page:

http://' . $_SERVER["SERVER_NAME"] . $config['pageshomeurl'] . 'manage/approval/?source=email

If you should not be receiving these emails, or if you have a 
technical query, please contact '
. $config['webmasteremail'] .
'

_____________________________________________________________
Sent by the online publications database @ ' . $config['institutionname'] . '
';
  foreach($emailto as $e)
  {
    @mysql_query("INSERT INTO PUBLICATIONSEMAILSSENT SET address='" . mysql_real_escape_string($e) . "'", connectpubsdb());
    mail($e, $msgtitle, $mainmsg /*, "Cc: $config[webmasteremail]\r\n" */);
  }
  mysql_query("UPDATE PUBLICATIONSTIMESTAMPS SET ts=NOW() WHERE name='lastemailprompt'", connectpubsdb());
}

function pubIsNotForDepartment($pubid, $deptid)
{
  $pubid = intval($pubid);
  $deptid = preg_replace('/\W/', '', $deptid);
  $res = mysql_query("SELECT deptlist FROM PUBLICATIONS WHERE pubid=$pubid LIMIT 1", connectpubsdb());
  if(!$res)
    return false;
  $row = mysql_fetch_assoc($res);
  $row = $row['deptlist'];
  $newlist = preg_replace('/,+/', ',', preg_replace("/\b$deptid\b/", '', $row));
  $q = "UPDATE PUBLICATIONS SET deptlist='$newlist' WHERE pubid=$pubid LIMIT 1";
  return mysql_query($q, connectpubsdb());
}

function notInMyDept($pubid)
{
  $singledept = singleDeptAdmin($_SERVER['REMOTE_USER']);
  if(!$singledept)
  {
	  ?><p><strong>Parameter error.</strong> Sorry! Unable to carry out the action. Please contact 
	    the webmaster if this is problematic. (Unable to determine which department code to use.)</p><?php
	  return;
  }
  if(pubIsNotForDepartment($pubid, $singledept))
  {
    ?><p><strong>Thank you.</strong> The publication has been dissociated from your department.</p><?php
  }
  else
  {
    ?><p><strong>Database error.</strong> Sorry! Unable to carry out the action. Please contact 
	    the webmaster if this is problematic.</p><?php
  }
}

function getUserFullName($userid)
{
	$userid = preg_replace('/\W/','',substr($userid,0,8));
	if($res = mysql_query("SELECT title, firstname, lastname FROM USERS WHERE USERID='$userid' LIMIT 1", connectpubsdb()))
	{
	  $v = mysql_fetch_assoc($res);
	  return "$v[title] $v[firstname] $v[lastname]";
	}
	return false;
}

function getUserBiblioName($userid)
{
	$userid = preg_replace('/\W/','',substr($userid,0,8));
	if($res = mysql_query("SELECT lastname, firstname FROM USERS WHERE USERID='$userid' LIMIT 1", connectpubsdb()))
	{
	  $v = mysql_fetch_assoc($res);
	  return "$v[lastname]," . substr($v['firstname'],0,1) . ".";
	}
	return false;
}

$globaluserinfolist = array();
function getUserInfo($userid) // Get user info - it caches the info so it's OK to call it over and over again
{
     global $globaluserinfolist;
	if(isset($globaluserinfolist[$userid]))
	  return $globaluserinfolist[$userid];

	$userid = preg_replace('/\W/','',substr($userid,0,8));
	if($res = mysql_query("SELECT * FROM USERS WHERE USERID='$userid' LIMIT 1", connectpubsdb()))
	{
	  $globaluserinfolist[$userid] = mysql_fetch_assoc($res);
	  return $globaluserinfolist[$userid];
	}
	return false;
}

function groupDeptsByFaculties($depts, $facs)
{
    $deptsperfac = array(0 => array());
    foreach($facs as $fac)
	  $deptsperfac[$fac['FACULTYID']] = array(); // Initialise each element

	foreach($depts as $k=>$v)
	{
	  if(isset($facs[$v['FACULTYID']]))
	    $deptsperfac[$v['FACULTYID']][$k] = $v;
	  else
	    $deptsperfac[0][$k] = $v; // Ones without faculty go in element zero
	}
	return $deptsperfac;
}

function groupAdminsByDeptAndFac($admins, $depts, $facs)
{
  // N.B. This function ONLY returns the single-department administrators
    $deptsperfac = array(0 => array());
    foreach($facs as $fac)
	  $deptsperfac[$fac['FACULTYID']] = array(); // Initialise each element

	foreach($depts as $k=>$v)
	{
	  // Add onto the department ("$v") an array [admins]
	  foreach($admins as $adk=>$admin)
	    if(trim(str_replace(',','',$admin['deptslist']))==$k)
		  $v['admins'][$admin['userid']] = $admin;

      // Now add the department (with its administrators) onto the faculty
	  if(isset($facs[$v['FACULTYID']]))
	    $deptsperfac[$v['FACULTYID']][$k] = $v;
	  else
	    $deptsperfac[0][$k] = $v; // Ones without faculty go in element zero
	}
	return $deptsperfac;
}

// For the purposes of managing journal titles (avoiding overlap etc)
// Only really for admins
function listAllJournalTitles($userid)
{
  $depts = getAuthorisations($userid);
  if($depts===false) return false;

  if((str_replace(',','',$depts)=='') || ($depts=='ALL'))
    $dwh = '(1)';
  else
  {
    $dwh = array();
	$depts = preg_split('/[\., ]+/', $depts);
	foreach($depts as $d)
	  if(strlen($d)>0)
	    $dwh[] = "deptlist LIKE '%," . mysql_real_escape_string($d) . ",%'";
    $dwh = ' (' . implode(' OR ', $dwh) . ') ';
  }

  $q = "SELECT DISTINCT journal, issnisbn FROM PUBLICATIONS WHERE $dwh AND (reftype='JOUR' OR reftype='JFUL') AND (journal NOT LIKE '') ORDER BY journal";
  $res = mysql_query($q, connectpubsdb());
  if(!$res) return false;
//  echo "<p>Found " . mysql_num_rows($res) . " journal titles</p>";
  $ret = array();
  while($row = mysql_fetch_assoc($res))
    $ret[] = $row['journal'] . ($row['issnisbn'] ? " (ISSN=" . $row['issnisbn'] . ")" : "");
  return $ret;
}
function changeJournalTitle($userid, $jfrom, $jto)
{
  $depts = getAuthorisations($userid);
  if($depts===false) 
    return false;

  if((str_replace(',','',$depts)=='') || ($depts=='ALL'))
    $dwh = '(1)';
  else
  {
    $dwh = array();
	$depts = preg_split('/[\., ]+/', $depts);
	foreach($depts as $d)
	  if(strlen($d)>0)
	    $dwh[] = "deptlist LIKE '%," . mysql_real_escape_string($d) . ",%'";
    $dwh = ' (' . implode(' OR ', $dwh) . ') ';
  }

  $q = "UPDATE PUBLICATIONS SET journal='" 
              . mysql_real_escape_string($jto)
			  . "' WHERE $dwh AND (reftype='JOUR' OR reftype='JFUL') AND (journal = '" 
              . mysql_real_escape_string($jfrom)
			  . "')";
  $res = mysql_query($q, connectpubsdb());
}

$global_refmanconversions = array(
                    '/'.chr(16).'(.*?)'.chr(17).'/' => "<i>\\1</i>",
                    '/'.chr(20).'(.*?)'.chr(25).'/' => "<sup>\\1</sup>",
                    '/'.chr(22).'(.*?)'.chr(19).'/' => "<sub>\\1</sub>",
                    '/'.chr(21).'(.*?)'.chr(23).'/' => "<b>\\1</b>", // I'm making an assumption here - not 100% sure that 21.....23 means bold
                    // 5 and 6 deliminate greek eg "g" => gamma
					'/'.chr(5).'(.*?)a(.*?)'.chr(6).'/' => "<sub>\\1&alpha;\\2</sub>",
					'/'.chr(5).'(.*?)b(.*?)'.chr(6).'/' => "<sub>\\1&beta;\\2</sub>",
					'/'.chr(5).'(.*?)c(.*?)'.chr(6).'/' => "<sub>\\1&chi;\\2</sub>",
					'/'.chr(5).'(.*?)d(.*?)'.chr(6).'/' => "<sub>\\1&delta;\\2</sub>",
					'/'.chr(5).'(.*?)e(.*?)'.chr(6).'/' => "<sub>\\1&epsilon;\\2</sub>",
					'/'.chr(5).'(.*?)f(.*?)'.chr(6).'/' => "<sub>\\1&phi;\\2</sub>",
					'/'.chr(5).'(.*?)g(.*?)'.chr(6).'/' => "<sub>\\1&gamma;\\2</sub>",
					'/'.chr(5).'(.*?)h(.*?)'.chr(6).'/' => "<sub>\\1&eta;\\2</sub>",
					'/'.chr(5).'(.*?)i(.*?)'.chr(6).'/' => "<sub>\\1&iota;\\2</sub>",
					'/'.chr(5).'(.*?)j(.*?)'.chr(6).'/' => "<sub>\\1&thetasym;\\2</sub>",
					'/'.chr(5).'(.*?)k(.*?)'.chr(6).'/' => "<sub>\\1&kappa;\\2</sub>",
					'/'.chr(5).'(.*?)l(.*?)'.chr(6).'/' => "<sub>\\1&lambda;\\2</sub>",
					'/'.chr(5).'(.*?)m(.*?)'.chr(6).'/' => "<sub>\\1&mu;\\2</sub>",
					'/'.chr(5).'(.*?)n(.*?)'.chr(6).'/' => "<sub>\\1&nu;\\2</sub>",
					'/'.chr(5).'(.*?)o(.*?)'.chr(6).'/' => "<sub>\\1&omicron;\\2</sub>",
					'/'.chr(5).'(.*?)p(.*?)'.chr(6).'/' => "<sub>\\1&pi;\\2</sub>",
					'/'.chr(5).'(.*?)q(.*?)'.chr(6).'/' => "<sub>\\1&theta;\\2</sub>",
					'/'.chr(5).'(.*?)r(.*?)'.chr(6).'/' => "<sub>\\1&rho;\\2</sub>",
					'/'.chr(5).'(.*?)s(.*?)'.chr(6).'/' => "<sub>\\1&sigma;\\2</sub>",
					'/'.chr(5).'(.*?)t(.*?)'.chr(6).'/' => "<sub>\\1&tau;\\2</sub>",
					'/'.chr(5).'(.*?)u(.*?)'.chr(6).'/' => "<sub>\\1&upsilon;\\2</sub>",
					'/'.chr(5).'(.*?)v(.*?)'.chr(6).'/' => "<sub>\\1&piv;\\2</sub>",
					'/'.chr(5).'(.*?)w(.*?)'.chr(6).'/' => "<sub>\\1&omega;\\2</sub>",
					'/'.chr(5).'(.*?)x(.*?)'.chr(6).'/' => "<sub>\\1&xi;\\2</sub>",
					'/'.chr(5).'(.*?)y(.*?)'.chr(6).'/' => "<sub>\\1&psi;\\2</sub>",
					'/'.chr(5).'(.*?)z(.*?)'.chr(6).'/' => "<sub>\\1&zeta;\\2</sub>",
//					'/'.chr(5).'/' => '', // 5 and 6 deliminate greek eg "g" => gamma
//					'/'.chr(6).'/' => '', 
					'/'.chr(2).'/' => '',
					'/'.chr(4).'/' => '',   // 2 & 4 are the strange ones that RefMan seems to add for reasons obscure to me
					'/'.chr(9).'/' => ' '  // Tab sign is meaningless in this context so convert it to a space
					);
$global_refmanfrom = array_keys($global_refmanconversions);
$global_refmanto = array_values($global_refmanconversions);
function convertRefManControlChars($in)
{
  global $global_refmanconversions, $global_refmanfrom, $global_refmanto;
  return preg_replace($global_refmanfrom, $global_refmanto, $in);
}

function getUserlessPubs($deptid)
{
  $q = "SELECT * FROM PUBLICATIONS WHERE (deptlist LIKE '%," . mysql_real_escape_string($deptid) 
                    . ",%' OR pendingdepts LIKE '%," . mysql_real_escape_string($deptid) 
                    . ",%') AND NOT (userlist RLIKE '[[:alnum:]]') ORDER BY timestamp DESC";

  $ret = array();
  $res = mysql_query($q, connectpubsdb());
  if($res)
    while($row = mysql_fetch_assoc($res))
	  $ret[$row['pubid']] = $row;
  return $ret;
}

function allowedToManageUser($adminid, $userid, $primarydeptonly=false)
{
  global $config;
  if($userid==$adminid) return true; // People are allowed to manage their own stuff!

  if(isGlobalAdmin($adminid)) return true; // Global admins can do anything

  $auth = getAuthorisations($adminid);

//REMOVE - HoDs WON'T PASS THIS TEST!             if(!$auth) return false; // If not an admin at all, they can't manage another person's stuff
  
  $q = "SELECT deptid, otherdepts FROM USERS WHERE userid='" . mysql_real_escape_string($userid) . "' LIMIT 1";
  $res = mysql_query($q, connectpubsdb());
  if(!$res) return false;
  
  if(mysql_num_rows($res)==0) return true; // An administrator IS allowed to manage a user that doesn't have a record yet

  $row = mysql_fetch_assoc($res);
  $mydept = $row['deptid'];
  $otherdepts = $row['otherdepts'];

  if(strpos($auth, ",$mydept,")!==false) // An administrator for the user's home dept IS allowed to manage them
    return true;
  if(!$primarydeptonly)
    foreach(splitDeptListString($otherdepts) as $od)
      if(strpos($auth, ",$od,")!==false) // An administrator for the user's secondary dept IS allowed to manage them
        return true;

  $deptstosearch = $primarydeptonly 
                ? "'$mydept'"
			 : "'" . implode("', '", splitDeptListString($otherdepts . ',' . $mydept)) . "'";
  $q = "SELECT deptid FROM DEPTS WHERE hod='" . mysql_real_escape_string($adminid) 
             . "' AND (deptid IN ($deptstosearch))";
  //if($config['debug'])
  //  echo "<p>The HoD-search query is $q</p>";
  $res = mysql_query($q);
  if($res && (mysql_num_rows($res)!=0))
    return true; // A HoD for one of the user's departments IS allowed to manage their data

  return false;
}

// Tidy up ISSN codes (in particular) so that they're in the database in a regular format
function fixIssnIsbn($issnisbn){
  // Remove "ISBN " etc from the start of the data
  $issnisbn = preg_replace('/^IS[SB]N[ :-]*(.*)$/', "$1", $issnisbn);
  // Coerce ISSNs to the most standard format we can...
  $issnisbn = strtoupper(preg_replace('/^\s*(\d{4})-?(\d{3}[0-9Xx])\s*$/', "$1-$2", $issnisbn));
  return $issnisbn;
}

function changePubType($pubid, $newtype){
  mysql_query("UPDATE PUBLICATIONS SET reftype='"
	          . mysql_real_escape_string(stripslashes($newtype)) 
			  . "' WHERE pubid='".intval($pubid)."' LIMIT 1", connectpubsdb());
  recordtransaction("changepubtype:$newtype", $pubid);
}


function allIssnsWithImpactFactors(){ // Return associative array issn=>impactfactor
  $q = "SELECT issnisbn, jif FROM IMPACTFACTORS";
  $res = mysql_query($q, connectpubsdb());
  if(!$res) return array();
  $ret = array();
  while($row = mysql_fetch_assoc($res))
    $ret[$row['issnisbn']] = $row['jif'];
  return $ret;
}

function allIssnsWithJabbrevs(){ // Return associative array issn=>jabbrev
  $q = "SELECT issnisbn, jabbrev FROM IMPACTFACTORS";
  $res = mysql_query($q, connectpubsdb());
  if(!$res) return array();
  $ret = array();
  while($row = mysql_fetch_assoc($res))
    $ret[$row['issnisbn']] = $row['jabbrev'];
  return $ret;
}


function addUserOtherDepts($userid, $reqdepts, $approve=true){ // Will add OR APPROVE a user's secondary dept'l associations - user record must exist
  // $depts is an array of deptid codes

  global $config;

  // NB:  MAKE SURE that the user's home department is NOT included.
  // We need to be as sure as possible (for search optimisation) that most people have a purely blank entry in this column

  $mydepts = isGlobalAdmin($_SERVER['REMOTE_USER']) ? array_keys(getalldepts()) : splitDeptListString(getAuthorisations($_SERVER['REMOTE_USER']));

  // Split the array of $reqdepts into the ones authorised, and the ones unauthorised.
  if($approve){
    $reqdepts_auth = array_intersect($reqdepts, $mydepts);
    $reqdepts_unauth = array_diff($reqdepts, $mydepts);
  }else{ // Don't publicise any of the associations at all just yet
    $reqdepts_auth = array();
    $reqdepts_unauth = $reqdepts;
  }
  
  // Fetch the user's current dept info
  $q = "SELECT deptid, otherdepts, otherdeptspending FROM USERS WHERE userid='".mysql_real_escape_string($userid)."' LIMIT 1";
  $res = mysql_query($q, connectpubsdb());
  if(!($res && ($current = mysql_fetch_assoc($res)))){
    return false;
  }
  
  $current['otherdepts'] = splitDeptListString($current['otherdepts']);
  $current['otherdeptspending'] = splitDeptListString($current['otherdeptspending']);
  
/*
  if($config['debug']){
    echo "<pre>addUserOtherDepts($userid, [".implode(', ',$reqdepts)."]):
    \$reqdepts: ".print_r($reqdepts, true) . "
    \$mydepts: ".print_r($mydepts, true) . "
    \$current[deptid]: ".print_r($current['deptid'], true) . "
    \$current[otherdepts]: ".print_r($current['otherdepts'], true) . "
    \$current[otherdeptspending]: ".print_r($current['otherdeptspending'], true) . "
    \$reqdepts_auth: ".print_r($reqdepts_auth, true) . "
    \$reqdepts_unauth: ".print_r($reqdepts_unauth, true) . "
    </pre>";
  }
*/
  
  // The result we want is the following:
  //   otherdepts        = current[otherdepts]        & reqdepts_auth    & (NOT deptid)
  // followed by:
  //   otherdeptspending = current[otherdeptspending] & reqdepts_noauth  & (NOT deptid) & (NOT otherdepts)
  
  $otherdepts = array_diff(array_merge($current['otherdepts'], $reqdepts_auth), 
                           array($current['deptid']));

  $otherdeptspending = array_diff(array_merge($current['otherdeptspending'], $reqdepts_unauth), 
                           array_merge(array($current['deptid']), $otherdepts));

  
  // Now store
  $q = "UPDATE USERS SET otherdepts='".mysql_real_escape_string(mergeDeptListString($otherdepts))
                 ."', otherdeptspending='".mysql_real_escape_string(mergeDeptListString($otherdeptspending))
                 ."' WHERE userid='".mysql_real_escape_string($userid)
                 ."'";  
  if($config['debug']){
    echo "<pre>RESULTS:
    \$otherdepts: ".print_r($otherdepts, true) . "
    \$otherdeptspending: ".print_r($otherdeptspending, true) . "
    \$q: $q
    </pre>";
  }
  return mysql_query($q);
}



function removeUserOtherDepts($userid, $reqdepts){ // Will remove a user's secondary dept'l associations, if authorised
  // $depts is an array of deptid codes
  global $config;

  $mydepts = isGlobalAdmin($_SERVER['REMOTE_USER']) ? array_keys(getalldepts()) : splitDeptListString(getAuthorisations($_SERVER['REMOTE_USER']));

  // Now narrow $depts down to a list of only the ones the current user is allowed to manage!
  $reqdepts_auth = array_intersect($reqdepts, $mydepts);

  // Fetch the user's current dept info
  $q = "SELECT deptid, otherdepts, otherdeptspending FROM USERS WHERE userid='".mysql_real_escape_string($userid)."' LIMIT 1";
  $res = mysql_query($q, connectpubsdb());
  if(!($res && ($current = mysql_fetch_assoc($res)))){
    return false;
  }
  
  $current['otherdepts'] = splitDeptListString($current['otherdepts']);
  $current['otherdeptspending'] = splitDeptListString($current['otherdeptspending']);

  // Now remove from the live AND the pending list, any that are requested-and-authorised for removal.
  // (Also make sure the home dept isn't in the otherdepts list anywhere! Just in case...)
  $otherdepts = array_diff(array_diff($current['otherdepts'], $reqdepts_auth), 
                           array($current['deptid']));
  $otherdeptspending = array_diff(array_diff($current['otherdeptspending'], $reqdepts_auth), 
                           array($current['deptid']));
  // Now store
  $q = "UPDATE USERS SET otherdepts='".mysql_real_escape_string(mergeDeptListString($otherdepts))
                 ."', otherdeptspending='".mysql_real_escape_string(mergeDeptListString($otherdeptspending))
                 ."' WHERE userid='".mysql_real_escape_string($userid)
                 ."'";  
  if($config['debug']){
    echo "<pre>removeUserOtherDepts() RESULTS:
    \$otherdepts: ".print_r($otherdepts, true) . "
    \$otherdeptspending: ".print_r($otherdeptspending, true) . "
    \$q: $q
    </pre>";
  }
  return mysql_query($q);
}

function countPubsPerUser($userids, $authdepts){ // Num pubs associated with a set of users - only counts the pubs a specific admin is allowed to see
  // If NO authdepts are mentioned, assume all of them - this is the case for global admins
  global $config;
  if(!$userids) 
    return array();
  
  $userid = mysql_real_escape_string($userid);
  $q = "SELECT COUNT(*) AS cnt, userlist FROM PUBLICATIONS WHERE ";
  foreach($userids as $userid)
    $userclauses[] = "userlist LIKE '%$userid%'";
  $q .= '('.implode(' OR ', $userclauses).')';
  if(sizeof($authdepts)>0)
  {
    $deptclauses = array();
    foreach($authdepts as $v){
      $deptclause = "(deptlist LIKE '%,$v,%' OR ";
      $deptclause .= "(pendingdepts<>',' AND ";
      $deptclause .= "pendingdepts LIKE '%,$v,%'";
      $deptclause .= "))";
	 $deptclauses[] = $deptclause;
    }
    $q .= ' AND (' . implode(' OR ', $deptclauses) . ')';
  }
  $q .= ' GROUP BY userlist';
  if($config['debug'])
    echo "<p>$q<p>";
  if(!($res = mysql_query($q, connectpubsdb())))
    return false;
  $counts = array();
  while($row = mysql_fetch_assoc($res)){
    $users = splitDeptListString($row['userlist']);
    foreach($users as $u)
      $counts[$u] += $row['cnt'];
  }
  return $counts;
}

function canUserEditPub($p, $userid){ // Supply the publication DATA, not the id code
  global $config;
  // If global admin
  if(isGlobalAdmin($userid))
    return true;
  
  // If the current user is one of the authors
  if(strpos($p['userlist'], $userid)!==false)
    return true;
  
  // If can admin one relevant dept
  $mydepts = splitDeptListString(getAuthorisations($userid));
  if(preg_match('/^[,]*$/', $p['deptlist'].$p['pendingdepts'])) {
    // It's a personal publication, so departmental 'associations' must
    // be gleaned from the departments of the people in the userlist
    $q1 = "SELECT deptid, otherdepts from USERS WHERE userid IN ('" . str_replace(",", "','" ,$p['userlist']) . "')";
    $res1 = mysql_query($q1,connectpubsdb());
    $depts = '';
    while ($row1 = mysql_fetch_assoc($res1))
      $depts .= ',' . $row1['deptid'].$row1['otherdepts'];
    $pubdepts = splitDeptListString($depts);
    if($config['debug']){
      echo "<p>", $p['userlist'], "<p>", $q1, "<p>", $depts, "<p>";
      print_r($pubdepts);
    }
  }
  else
    // It's not a personal publication, so can use proper associations
    $pubdepts = array_merge(splitDeptListString($p['deptlist']), splitDeptListString($p['pendingdepts']));
  if(sizeof(array_intersect($mydepts, $pubdepts))!=0){
    return true;
  }
  
  return false;
}

function tidyAuthorListString($str) { // Given a string destined for the "authorlist" field or "secondaryauthorlist" field, correct the spacing etc
  return preg_replace('/ *, */', ',', $str);
}

?>
