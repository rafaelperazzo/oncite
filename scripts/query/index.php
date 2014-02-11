<?php
$queryenginescriptstart = microtime();
require_once(dirname(dirname(__FILE__)) . '/manage/config.inc.php');


// require_once('/nfs/rcs/www/webdocs/lifesciences-faculty/publications/manage/config.inc.php');
/*

This file provides the wherewithal to perform a generic search of the publications
database and show the results in nicely-formatted output.

ALSO has the capability to display a single publication (full info) if $pubid is set. This overrides other options.

By the way - if userhasleft=1 then the user shouldn't be included in the author listings.

Variables:
'years' = array of years to be included
'users' = array of userids to be included
'depts' = array of department codes to be included
'defaultdepts' = this is a COMMA-SEPARATED LIST, not an array. if 'depts' is not set, use this instead (used to provide ability to default to a given faculty)
'pubid' = if set then ONLY this publication is shown
'catkw' = search "categories" field, to restrict to departmentally-defined subgroups
'group' = if null then no grouping; if 'type' groups by reftype; if 'author' groups by author; else groups by year
'chc'   = if 'y' then it ONLY looks for things from 2002 onwards, with chc marker set to 1
'total' = if 'n' then it WON'T display the subtotal of how many references have been retrieved
'wordsearch' = space- or comma-separated list of words or quoted phrases to look for in title / abstract / keywords / notes
'namesearch' = space-separated list of words or quoted phrases to look for in authorlist / seriesauthorlist / secondaryauthorlist
'currentyearwarning' = if 'y' then it'll warn people that the current year might not be complete
'detailclick' = if 'n' then it DOESN'T show that "[Detail]" link
'includeunconfirmed' = if set to 'y' then it will show unconfirmed entries, but with a warning they're unconfirmed
'pubtype' = restrict to a given sort of publication (the pubtype code such as JOUR or CONF should be given)
'includeinpress' = if set to 'y' then will include publications with year=9999 - otherwise it'll EXCLUDE them
'journalsonly' = if set to 'y', then it will only list JOUR or JFUL reference types
'eprintsonly'  = if set to 'y', then it will only list entries where the URL begins with $config['eprints_prefix']
'timetaken' = if set to 'y', will tell you how many seconds the query took
'journaltitle' = expects the FULL journal title to be matched. Used primarily for disgnosing strange journal titles added by users!
'excludedeptless' = if set to 'y', forces all pubs with no explicit dept'l association to be excluded
                         - if not set, then such pubs will be included, unless of course a dept'l list is supplied.
					This is to specify whether "personal" publications should be included in the search. It 
					only excludes entries where BOTH the pending and approved associations are empty.
'stylesheet' = if you want to attach a stylesheet, supply its FULL URL here.
                          NB The "stylesheet" parameter is also a hint that we're not embedding the query
					 into some other HTML, so it also outputs <html><head><body> tags when this option is set.

'showopts' = string listing the options to display: include any or all of the words "edit,delete,dissociate,detail"
DEPRECATED:
'showeditoptions' = if 'y' then it'll show EDIT and DISSOCIATE options
'showdeleteoption' = if 'y' then it'll show the DELETE option

*/

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
  return;
}


// Make sure we're not calling the 100% unadorned script. It seems that sometimes people are doing this.
if((__FILE__ == $_ENV['PATH_TRANSLATED']) && ($_SERVER['REQUEST_METHOD']=='GET') && ($_SERVER['QUERY_STRING']==''))
{
  header('Location: '.$config['pageshomeurl']);
  exit;
}





// "LIMIT 15000" is arbitrary but it seems that the server (or the script?) 
//        can't handle ~20000, so there needs to be a limit somewhere.
//   A year's data is approx. 10000 records 
//
// Modified 29th October 2007
// Reduced to 2000 to avoid the OnCite process exceeding 16 Megabytes

$maxnumrecordstofetch = 2000;

// Adapt the query data to the way we want it
if(!isset($pubtype))
  $pubtype = preg_replace('|[^\w\d-]+|','',$_REQUEST['pubtype']);
if(!isset($group))
  $group = preg_replace('|[^\w\d-]+|','',$_REQUEST['group']);



if(is_array($depts))
{
  foreach($depts as $k => $v)
    $depts[$k] = preg_replace('|[^\w\d-/]+|','',substr($v,0,8)); // Allowed in dept'l codes: words, numbers, dash, /
}
else
{
  $depts = array();
  if(is_array($_REQUEST['depts']))
    foreach($_REQUEST['depts'] as $v)
      $depts[] = preg_replace('|[^\w\d-/]+|','',substr($v,0,8)); // Allowed in dept'l codes: words, numbers, dash, /
}
if(sizeof($depts)==0) // This clause attempts to use the "defaultdepts" variable, if set
{
  if(strlen($defaultdepts)==0)
    $defaultdepts = $_REQUEST['defaultdepts'];
  if(strlen($defaultdepts)!=0)
  {
    $temp = explode(',', $defaultdepts);
    if(!is_array($temp))
      $temp = array($temp);
    foreach($temp as $v)
      $depts[] = preg_replace('|[^\w\d-/]+|','',substr($v,0,8));
  }
}
// TEMPORARY - If a department searches for "BB" they need to be able to search for "BB00"
// FIXME: At the moment this slows the search down a little by duplicating search terms
// FIXME: The next stage is to automatically CONVERT BB to BB00 and not actually search for the old term at all
// ...and then at a later date we need to turn off the translation altogether (warn PhonLin and HCS departments!)
foreach($depts as $k=>$v)
{
  if($v=='BB' || $v=='BB00')
    $depts[$k] = 'FI';
}


if(is_array($years))
{
  foreach($years as $k => $v)
    $years[$k] = preg_replace('|[^\w\d-]+|','',substr($v,0,4));
}
else
{
  $years = array();
  if(is_array($_REQUEST['years']))
    foreach($_REQUEST['years'] as $v)
      $years[] = preg_replace('|[^\w\d-]+|','',substr($v,0,4));
}
if(is_array($users))
{
  foreach($users as $k => $v)
    $users[$k] = preg_replace('|[^\w\d-]+|','',substr($v,0,7));
}
else
{
  $users = array();
  if(is_array($_REQUEST['users']))
    foreach($_REQUEST['users'] as $v)
      $users[] = preg_replace('|[^\w\d-]+|','',substr($v,0,7));
}
if(!isset($journalsonly))
  $journalsonly = $_REQUEST['journalsonly'];
if(!isset($journaltitle))
  $journaltitle = trim(rawurldecode($_REQUEST['journaltitle']));
if(!isset($format))
  $format = $_REQUEST['format'];
if(!isset($total))
  $total = $_REQUEST['total'];
if(!isset($pubid))
  $pubid = intval($_REQUEST['pubid']);
else
  $pubid = intval($pubid);
if(!isset($showeditoptions))
  $showeditoptions = $_REQUEST['showeditoptions'];
if(!isset($showdeleteoption))
  $showdeleteoption = false;
if(!isset($detailclick))
  $detailclick = $_REQUEST['detailclick'];
if(!isset($wordsearch))
  $wordsearch=trim(urldecode($_REQUEST['wordsearch']));
if(!isset($namesearch))
  $namesearch=trim(urldecode($_REQUEST['namesearch']));
if(!isset($includeunconfirmed))
  $includeunconfirmed=$_REQUEST['includeunconfirmed'];
if(!isset($includeinpress))
  $includeinpress = $_REQUEST['includeinpress'];
if(!isset($currentyearwarning))
  $currentyearwarning=$_REQUEST['currentyearwarning'];
if(!isset($timetaken))
  $timetaken=$_REQUEST['timetaken'];
if(!isset($excludedeptless))
  $excludedeptless=$_REQUEST['excludedeptless'];
if(!isset($catkw))
  $catkw=$_REQUEST['catkw'];
if(!isset($eprintsonly))
  $eprintsonly=$_REQUEST['eprintsonly'];
if(!isset($stylesheet))
  $stylesheet=htmlspecialchars(trim(urldecode($_REQUEST['stylesheet'])));
//DEL if(!isset($includepersonal))
//DEL  $eprintsonly=$_REQUEST['includepersonal'];



if(!isset($showopts))
  $showopts=$_REQUEST['showopts'];
$showopts=strtolower($showopts);
if($showdeleteoption=='y')
  $showopts .= ',delete';
if($showeditoptions=='y')
  $showopts .= ',edit,dissociate';

// Construct the SQL query
$dbcon = @mysql_connect($config['db_addr'],$config['db_user'],$config['db_pass']);
if(!$dbcon)
  die("<p>&nbsp;</p>\n<h3>Database connection error</h3>\n<p>Sorry - the webpage has been unable to connect to "
       . "the publications database. We would be grateful if you would report this to "
	   . "the <a href=\"mailto:$config[webmasteremail]\">webmaster</a> so it can be fixed as soon as possible.</p>"
	   . "\n<!-- Error message: " . mysql_error() . " -->\n<p>&nbsp;</p>");

@mysql_select_db($config['db_db']);

if($pubid>0)
  $wh = "(pubid='$pubid')";
else
{
// $wh = "(LENGTH('approvedby')!=0)"; // THIS CHECK DOES NOT WORK!
$wh = '1';
if(strlen($pubtype)>0)
{
  $wh .= " AND (reftype='$pubtype')";
}
if(sizeof($depts)>0)
{
  $wh .= ' AND (';
  foreach($depts as $v)
    //RAFAEL: $wh .= "deptlist LIKE '%,$v,%' OR ";
    $wh .= "deptlist LIKE '%$v%' OR ";
  if($includeunconfirmed=='y'){
    $wh .= "(pendingdepts<>',' AND (";
    foreach($depts as $v)
      $wh .= "pendingdepts LIKE '%,$v,%' OR ";
    $wh .= "0)) OR ";
  }
  $wh .= '0)';
}
if(sizeof($users)>0)
{
  $wh .= ' AND (';
  foreach($users as $v)
    $wh .= "userlist LIKE '%$v%' OR ";
  $wh .= '0)';
}
if(sizeof($years)>0)
{
  $wh .= ' AND (';
  foreach($years as $v)
    $wh .= "YEAR='$v' OR ";
  $wh .= '0)';
}
if(strlen($catkw)>0)
{
  $wh .= ' AND (';
  $catkws = explode(',', $catkw);
  foreach($catkws as $v)
    $wh .= "catkw LIKE '%" . mysql_real_escape_string(trim($v)) . "%' OR ";
  $wh .= '0)';
}
if(substr($chc,0,1)=='y')
{
  $wh .= " AND ((chc='1' OR deptlist LIKE '%,FL,%' OR deptlist LIKE '%,FI,%') AND year>2001)";
}
}

if(strlen(preg_replace('/[, ]/','',$wordsearch))>0)
{
  preg_match_all('/[^", ]+|"[^"]+"/', str_replace('\\"','"',$wordsearch), $wsarray);
  $wh .= ' AND (';
  foreach($wsarray[0] as $v)
  {
    $vv = str_replace('*','%',trim($v,' "'));
    $rvv = '[[:<:]]'.str_replace('%','[[:alnum:]]*',$vv).'[[:>:]]';
    $wh .= "CONCAT_WS(' ', abstract, title, keywords, notes) LIKE '%$vv%' AND CONCAT_WS(' ', abstract, title, keywords, notes) REGEXP '$rvv' AND ";
  }
  $wh .= '1)';
}
if(strlen(preg_replace('/[ ]/','',$namesearch))>0)
{
  preg_match_all('/[^" ]+|"[^"]+"/', str_replace('\\"','"',$namesearch), $wsarray);
  $wh .= ' AND (';
  foreach($wsarray[0] as $v)
  {
    $vv = str_replace('*','%',trim($v,' "'));
    $rvv = '[[:<:]]'.str_replace('%','[[:alnum:]]*',$vv).'[[:>:]]';
    $wh .= "CONCAT_WS(' ', authorlist, secondaryauthorlist, seriesauthorlist) LIKE '%$vv%' AND CONCAT_WS(' ', authorlist, secondaryauthorlist, seriesauthorlist) REGEXP '$rvv' AND ";
  }
  $wh .= '1)';
}
if(strlen($journaltitle)>0)
{
  $wh .= ' AND (journal=\'' . mysql_real_escape_string($journaltitle) . '\') ';
}

if($includeinpress!='y')
  $wh .= ' AND (year!=9999)';
if($journalsonly=='y')
  $wh .= " AND (reftype='JOUR' OR reftype='JFUL')";
if($eprintsonly=='y')
  $wh .= " AND (url LIKE '".mysql_real_escape_string($config['eprints_prefix'])."%')";
//RAFAEL Comentei $wh.=
/*
if($includeunconfirmed!='y')
  $wh .= " AND (deptlist <> ',' OR year=9999 OR (deptlist=',' AND pendingdepts=','))"; // N.B. An "in press" entry never gets approved, nor a personal one
//  $wh .= " AND (deptlist RLIKE '[[:alnum:]]' OR year=9999)"; // N.B. An "in press" entry never gets approved
if($excludedeptless=='y')
  $wh .= " AND (deptlist<>',' OR pendingdepts<>',') ";
*/

$mysqltoris = array(
    'pubid' => 'ID', 
	'year' => 'Y1',
	'title' => 'T1',
	'abstract'=> 'N2',
	'authorlist' => 'A1',
	'journal' => 'JF',
	'url' => 'UR',
	'notes' => 'N1',
	'startpage' => 'SP',
	'endpage' => 'EP',
	'volume' => 'VL',
	'issue' => 'IS',
	'publisher' => 'PB',
	'issnisbn' => 'SN',
	'chapter' => 'CHAPTER',
	'reftype' => 'TY',
	'deptlist' => 'U1',
	'keywords' => 'KW',
	'address' => 'AD',
	'city' => 'CY',
	'medium' => 'M1',
	'secondarytitle' => 'T2',
	'seriestitle' => 'T3',
	'secondaryauthorlist' => 'A2',
	'seriesauthorlist' => 'A3',
	'journalabbrev' => 'JA',
	// 'DEPTID' => 'U1',
	'U2' => 'U2',
	'U3' => 'U3',
	'U4' => 'U4',
	'U5' => 'U5',
	'availability' => 'AV',
	'Y2' => 'Y2'
				);

$mysqltoenglish = array(
	'title' => 'Title',
	'authorlist' => 'Authors',
	'year' => 'Year',
	'journal' => 'Periodical',
	'startpage' => 'Start page',
	'endpage' => 'End page',
	'volume' => 'Volume',
	'keywords' => 'Keywords',
//    'pubid' => 'ID', 
//	'originator' => 'Originator',
//	'abstract'=> 'Abstract',
//	'url' => 'URL',
//	'notes' => 'Notes',
	'issue' => 'Issue',
//	'publisher' => 'Publisher',
	'issnisbn' => 'Serial no.',
	'chapter' => 'Chapter',
//	'reftype' => 'Reference type',
//	'address' => 'Address',
//	'city' => 'City',
//	'medium' => 'Medium',
//	'secondarytitle' => 'Title (secondary)',
//	'seriestitle' => 'Series title',
//	'seriesauthorlist' => 'Authors (series)',
//	'journalabbrev' => 'Journal (abbrev)',
//	'DEPTID' => 'Department'
				);



$q = "SELECT *"
	 . " "
     . " FROM PUBLICATIONS " . (strlen($wh)>1?"WHERE ($wh)":'')
	 . " ORDER BY year DESC, authorlist ASC, secondaryauthorlist ASC, title ASC"
	 . (($pubid>0)?' LIMIT 1 ':" LIMIT $maxnumrecordstofetch");

//print($q);
//if($config['debug'] && $format!='ris' && $format!='csv' && $format!='oaipmh' && $format!='bibtex')
//   echo "\n\n<p>$q</p>";

/*
$sendingcachedversion = false;
if($usecache=='y')
{
  // Ensure the old stuff is cleared from the cache
  mysql_query("DELETE FROM QUERYCACHE WHERE (tstamp < DATE_SUB(NOW(), INTERVAL '6' HOUR))");
  // Check for a cached version of the data
  $cacheq = "SELECT html FROM QUERYCACHE WHERE sql='" . mysql_real_escape_string($q) . "' LIMIT 1";
  if(!($cacheres = mysql_query($cacheq)))
    break;
  if(mysql_num_rows($cacheres)==0)
    break;
  $row = mysql_fetch_assoc($cacheres);
  if(strlen($row['html'])<3)
    break;
  $sendingcachedversion = true;
  echo "\n\n<!-- This is the cached version of the search result -->\n\n"
           . $row['html'];
}
*/

// THIS include contains the code for turning a db entry into a nice bibliographic-style entry
require_once($config['homedir'] . 'query/formataref.php');
$res = mysql_query($q);
if(!$res)
  die("<h3>Database query error</h3><p>Sorry - the webpage has encountered a problem while querying "
       . "the publications database. We would be grateful if you would report this to "
	   . "the <a href=\"mailto:$config[webmasteremail]\">webmaster</a> so it can be fixed as soon as possible.</p>"
	   . "<p>Query: $q</p>"
	   . "<p>Error message: " . mysql_error() . "</p>");

$p = array();
while($row = mysql_fetch_assoc($res))
{
    $row['personalonly'] = (preg_match('/^[\., ]*$/', $row['deptlist'])==1)
                        && (preg_match('/^[\., ]*$/', $row['pendingdepts'])==1); // Flag to indicate pub has NO deptl associations

    // DEPRECATED - WE ARE NOW PERFORMING THE CHECK ON THE DATABASE QUERY, NOT AFTERWARDS
    ////  // If required, we can exclude publications that do not have any dept'l association
    ////  if($excludedeptless=='y' && $row['personalonly'])
    ////    continue;
    $p[] = $row;
}

$reftypes = array();
$res = @mysql_query('SELECT * FROM PUBLICATIONSREFTYPES');
if($res)
  while($row = mysql_fetch_assoc($res))
    $reftypes[$row['reftype']] = $row;



if(sizeof($p)==0)
{
  ?><p class="publicationsnomatches">No publications found in database to match search criteria.
  [<a href="javascript:history.back();">Back</a>]</p>
  <?php
}
else
{

// NOW we choose a format for output and DO IT!

if($pubid>0 && (!$format))
{
  writedetailtable($p[0]);
}
elseif($format=='detail' || $format=='DETAIL')
{
  foreach($p as $pp)
  {
    writedetailtable($pp);
	echo "\n<hr noshade width=\"30%\" />";
  }
}
else if($format=='ris' or $format=='RIS')
{
  @header("Content-Type: text/plain");
  foreach($p as $v)
    echo qentrytoris($v);
}
else if($format=='oaipmh')
{
  require_once('outputformats/output-oaipmh.inc.php');
  output_oaipmh($p);
}
else if($format=='bibtex')
{
  require_once('outputformats/output-bibtex.inc.php');
  output_bibtex($p);
}else if($format=='table' or $format=='TABLE')
{
  echo "\n<table border=\"1\" width=\"100%\" cellspacing=\"0\">";
  echo "\n  <tr>\n    ";
  foreach($mysqltoenglish as $k=>$v)
    echo "<th>$v</th>";
  echo "\n  </tr>";
  foreach($p as $vv)
  {
    echo "\n  <tr>\n    ";
    foreach($mysqltoenglish as $k=>$v)
	{
      echo "<td valign=\"top\"><small>";
	  if(is_array($vv[$k]))
        foreach($vv[$k] as $vvv)
	      echo "$vvv<br />\n";
	  else
	    echo "$vv[$k]&nbsp;";
      echo "</small></td>";
	}
    echo "\n  </tr>";
  }
  echo "\n</table>";
}
else if($format=='csv' or $format=='CSV')
{  // CSV for downloading into Excel etc
  @header("Content-Type: text/csv");
  @header("Content-Disposition: attachment; filename=publications.csv");
  foreach($mysqltoenglish as $k=>$v)
    echo "\"$v\",";
  echo "\n";
  foreach($p as $vv)
  {
    foreach($mysqltoenglish as $k=>$v)
	{
	  if(is_array($vv[$k]))
	  {
	    echo '"';
        foreach($vv[$k] as $vvv)
	      echo str_replace('"',"''",$vvv) . ', ';
	    echo '",';
	  }
	  else
	    echo '"' . str_replace('"',"''",$vv[$k]) . '",';
	}
    echo "\n";
  }
}
else
{ // Show results as a nice, readable unordered list
  // echo "\n\n<!-- Query: $q -->\n\n";


  if($stylesheet){
    echo "\n<html><head><link rel='stylesheet' type='text/css' href='$stylesheet' /></head><body>";
  }

  if($total!='n')
    echo "\n<p class=\"publicationsnumfound\">" . sizeof($p) . " publications found.</p>";

  if(sizeof($p)==$maxnumrecordstofetch)
  {
    echo "\n<p class=\"publicationstruncated\">Your query may have matched more than $maxnumrecordstofetch records. "
	 . "However, no more than $maxnumrecordstofetch are permitted in a single query.  "
         . "Try limiting your search to a specific year.</p>";
  }

  $currentyear = strval( date('Y') );
  if($currentyearwarning=='y' && ($years==array($currentyear)  || ($group=='year' && (array_search($currentyear,$years)!==false))))
  {
    ?>
	<p style="margin: 10px; padding: 5px;" class="publicationscurrentyearwarning"><em><strong>Please note:</strong> Not all publications for 
	  the current year may have been indexed 
	  yet.</em></p>
	<?php
  }

  if(!(strlen($group)>0))
  { // If no grouping is demanded then write a single long list
    displayasingleunorderedlist($p);
  }
  elseif($group=='type')
  {

    // Get a list of reftypes out of the system
    $y = array();
    $universalpubtypes = array();
    $res = mysql_query("SELECT reftype, reftypename FROM PUBLICATIONSREFTYPES ORDER BY reftypename", $dbcon);
    if($res)
      while($row = mysql_fetch_assoc($res))
	  {
  	    $universalpubtypes[$row['reftype']] = $row['reftypename'];
		$y[$row['reftypename']] = array();
	  }
   if(isset($y['Other']))
     unset($y['Other']);

  	foreach($p as $v)
  	  $y[$universalpubtypes[$v['reftype']]][] = $v;
	displaygroupedorderedlists($y);
  }
  elseif($group=='periodical')
  {
    $y = array();
	echo "<p class=\"publicationsnb\">N.B. Grouping by periodical means that only journal articles / journals "
	   . "will be displayed - not other types of publication.</p>";
  	foreach($p as $v)
	  if(($v['reftype']=='JOUR' || $v['reftype']=='JFUL') && trim($v['journal'])!='')
  	    $y[$v['journal']][] = $v;
	ksort($y);
	displaygroupedorderedlists($y);
  }
  elseif($group=='author')
  {
    // Get a list of names of people listed as authors
    $authused = array();
	foreach($p as $v)
	  if(trim($v['userlist'])!='')
	  {
	    $temp = explode(',', $v['userlist']);
	    foreach($temp as $author)
  	      if(strlen(trim($author))>0)
	        $authused[trim($author)] = trim($author);
	  }
	$authq = "SELECT userid, firstname, lastname FROM USERS WHERE userhasleft=0 AND (userid IN (";
	foreach($authused as $v)
	  $authq .= "'" . mysql_real_escape_string($v) . "', ";
    $authq .= " '------'))";
//	$authq = "SELECT userid, firstname, lastname FROM USERS WHERE userhasleft=0 AND (0 ";
//	foreach($authused as $v)
//	  $authq .= " OR userid='" . mysql_real_escape_string($v) . "'";
//    $authq .= ")";
    if(sizeof($depts)>0)
    {
      $authq .= ' AND ((';
      foreach($depts as $v)
        $authq.= "deptid='" . substr($v,0,2) . "' OR ";
      $authq .= '0) OR (otherdepts<>"," AND (';
      foreach($depts as $v)
        $authq.= "otherdepts LIKE '%," . substr($v,0,2) . ",%' OR ";
      $authq .= ' 0)))';
    }
	$authq .= "ORDER BY lastname, firstname";
	if($config['debug'])
	  echo "<p>$authq</p>";
	$authres = mysql_query($authq, $dbcon);
	if($authres)
	{
	  if(mysql_num_rows($authres)==0)
	  {
			?>
			 <p class="publicationsnb"><em><strong>Please note:</strong>
			 The &quot;Group by <?php echo $config['institutionname'] ?> author&quot; option has been deactivated, since a list
			 of authors has not yet been set up for the department(s) you requested.</em></p>
			<?php
			 displayasingleunorderedlist($p);
	  }
	  else
	  {
		  $authornames = $authorgrouped = array();
		  while($row = mysql_fetch_assoc($authres))
		  {
			$authornames[$row['userid']] = $row['firstname'] . ' <b>' . $row['lastname'] . '</b>';
			$authorgrouped[$authornames[$row['userid']]] = array();
		  }
		  $totalpubsappearances = 0;
		  foreach($p as $v)
		  {
			$temp = explode(',', $v['userlist']);
			$thispubsappearances = 0;
			foreach($temp as $author)
			  if(strlen(trim($author))>0 && strlen($authornames[trim($author)])>0)
			  {
				$authorgrouped[$authornames[trim($author)]][] = $v;
				$thispubsappearances++; // Incremement the count for the number of times THIS citation will be displayed
				$totalpubsappearances++; // FAULTY - this should only be incremented once per publication!
			  }
			if($thispubsappearances==0)
			  $authorgrouped[' Ungrouped entries'][] = $v; // Leave the space at the front so it goes to the end of the list
		  }
	
		  if( ($totalpubsappearances/sizeof($p)) < 0.7 )
		  {
			?>
			 <p class="publicationsnb"><em><strong>Please note:</strong>
			 The &quot;Group by <?php echo $config['institutionname'] ?> author&quot; option has been deactivated for your query, since most of 
			   the publications found could not be grouped by the search algorithm.</em></p>
			<?php
			 displayasingleunorderedlist($p);
		  }
		  else
		  { /*
			?>
			 <div style="margin: 10px; padding: 5px; border: 1px solid gray;">
			 <p class="publicationsnb"><em><strong>Please note:</strong><br />
			 The &quot;Group by <?php echo $config['institutionname'] ?> author&quot; option ignores publications which have not yet been 
			  positively associated with their authors' identities - so some publications may not be shown,  
			  despite matching the search criteria.</em></p>
			 </div>
			<?php
			*/
			displaygroupedorderedlists($authorgrouped);
		  }
      } // End of did-we-find-zero-authors
	}
	else
	{
	  echo "<p>The process of grouping the results according to author failed - sorry. Please try searching without this option.</p>"
	      . "<p>Error detail: " . mysql_error() . "</p>";
	}



  }
  else
  { // ...otherwise let's group things year by year and then write a list for each one
  //  if($group=='year')
  //  {
       $y = array();
  	   foreach($p as $v)
  	     $y[$v['year']][] = $v;
	   displaygroupedorderedlists($y);
  //  }
  }

  if($stylesheet){
    echo "\n</body></html>";
  }

}

$queryenginescriptend = microtime();
list($startusec, $startsec) = explode(" ", $queryenginescriptstart); 
list($endusec, $endsec) = explode(" ", $queryenginescriptend); 
$diff = round((float)$endusec + (float)$endsec - (float)$startusec - (float)$startsec, 3); 

if($timetaken=='y' && $format!='ris' && $format!='csv' && $format!='bibtex' && $format!='oaipmh')
  echo "\n\n<p class='timetaken'>(Search took $diff seconds)</p>\n\n";

} // End of "yes, we found some results" block

function displaygroupedorderedlists($p)
{
  global $config;
  $sizeofp = sizeof($p);
  if($sizeofp>1)
  {
    echo "\n<table border=\"0\" cellspacing=\"10\"><tr><td valign=\"top\">";
	$maxpercolumn = intval($sizeofp/4)+1;
	$i = 0;
    echo "\n<ul class=\"publicationsgroupanchorlinks\">";
    foreach($p as $k => $v)
    if(sizeof($v)>0)
	{
	  if(($sizeofp > 12) && (($i++) % $maxpercolumn == 0))
	    echo "\n</ul></td>\n<td valign=\"top\">\n<ul class=\"publicationsgroupanchorlinks\">";
	  if(intval($k)==9999) $k = $config['inpressstring'];
	  echo "\n  <li><a href=\"#anchor" . rawurlencode(strip_tags(stripslashes($k))) . "\">" . stripslashes($k) . "</a></li>";
	}
	echo "\n</ul>";
	echo "\n</td></tr></table>";
  }
  foreach($p as $k => $v)
  if(sizeof($v)>0)
  {
    if(intval($k)==9999) $k = $config['inpressstring'];
    echo "\n<h2 class=\"publicationsgroupanchors\"><a name=\"anchor" . rawurlencode(strip_tags(stripslashes($k))) . "\"></a>" . stripslashes($k) . "</h2>";
    displayasingleunorderedlist($v);
  }
}

function displayasingleunorderedlist($p)
{
  global $userid, $showopts, $showeditoptions, $detailclick, $showdeleteoption, $config;
  echo "\n<ul class=\"publicationsul\">";
  foreach($p as $v)
  {
    if(intval($v['year'])==9999)
	  $v['year'] = $config['inpressstring'];
    echo "\n<li id=\"oncite" . $v['pubid'] . "\">";
//  echo "\n\n<!-- \$showopts in displayasingleunorderedlist = $showopts -->\n\n";
    echo formataref2($v, $userid, $detailclick, $showopts);
//    echo formataref($v, $userid, $showeditoptions, $detailclick, $showdeleteoption=='y');
    echo "</li>";
  }
echo "\n</ul>";
}


function qentrytoris($e)
{
  global $mysqltoris;
  $r = "TY  - $e[reftype]\r\n";
  
  if($e['U2']=='')
    $e['U2']='M'; // This should be removed when this isn't just for Life Sci!

  // The different entries for year/month/day/other will need to be recombined into a single 'year' entry
  $buildyear = intval($e['year']);
  $buildyear .= '/';
  if($e['yearmonth']>0)
    $buildyear .= intval($e['yearmonth']);
  $buildyear .= '/';
  if($e['yearday']>0)
    $buildyear .= intval($e['yearday']);
  $buildyear .= '/';
  if(strlen($e['yearother'])>0)
    $buildyear .= trim($e['yearother']);
  $e['year'] = $buildyear;
  unset($e['yearmonth']);
  unset($e['yearday']);
  unset($e['yearother']);

  if(strlen($e['keywords'])>0)  // ...we need to split things onto separate lines
  {
	$temp = explode(',',$e['keywords']);
	foreach($temp as $vv)
	  if(trim($vv)!='')
        $r .= $mysqltoris['keywords'] . '  - ' . trim($vv) . "\r\n";
  }
  // We need to split authors onto separate lines
//  if(strlen($e['authorlist'])>0 && preg_match_all('/\w\w+?, ?\S+?\.?/',$e['authorlist'],$temp))
  if(strlen($e['authorlist'])>0 && preg_match_all('/[^\.,]{2,64},( ?[^\.,]+?\.?){1,4}/',$e['authorlist'],$temp))
  {
	foreach($temp[0] as $vv)
   	  $r .= $mysqltoris['authorlist'] . '  - ' . preg_replace('/^([^\.,]{2,64},) ?([^ \.,]+?\.?)$/',"$1$2", trim($vv)) . "\r\n";
    unset($e['authorlist']);
  }
  if(strlen($e['secondaryauthorlist'])>0 && preg_match_all('/[^\.,]{2,64},( ?[^ \.,]+?\.?){1,4}/',$e['secondaryauthorlist'],$temp))  // ...we need to split things onto separate lines
  {
	foreach($temp[0] as $vv)
   	  $r .= $mysqltoris['secondaryauthorlist'] . '  - ' . preg_replace('/^([^\.,]{2,64},) ?([^ \.,]+?\.?)$/',"$1$2",trim($vv)) . "\r\n";
    unset($e['secondaryauthorlist']);
  }
  if(strlen($e['seriesauthorlist'])>0 && preg_match_all('/[^\.,]{2,64},( ?[^ \.,]+?\.?){1,4}/',$e['seriesauthorlist'],$temp))  // ...we need to split things onto separate lines
  {
	foreach($temp[0] as $vv)
   	  $r .= $mysqltoris['seriesauthorlist'] . '  - ' . preg_replace('/^([^\.,]{2,64},) ?([^ \.,]+?\.?)$/',"$1$2",trim($vv)) . "\r\n";
    unset($e['seriesauthorlist']);
  }
  // For conferences (refereed/unrefereed) we nee to move the paper title on to the U5 field... sigh...
  if(trim($e['reftype'])=='CONF' || trim($e['reftype'])=='CASE')
  {
	$r .= 'U5  - ' . $e['secondarytitle'] . "\r\n";
    unset($e['secondarytitle']);
  }

  // Using "unset" instead of skipping fields - to see if this is more efficient (N.B. it does seem to be)
  unset($e['timestamp']);
  unset($e['reftype']);
  unset($e['userlist']);
  unset($e['keywords']);

  foreach($e as $k=>$v)
  {
    if(strlen($v)==0 || !isset($mysqltoris[$k])) continue;
	
	$r .= $mysqltoris[$k] . '  - ' . $v . "\r\n";
  }
  return "\r\n" . $r. "ER  - \r\n";
}

function writedetailtable($p)
{
  global $reftypes, $config;
  echo '<table border="1" align="center" class="publicationsdetailtable">';
  echo "<tr><th>Reference&nbsp;type</th><td>" . $reftypes[$p['reftype']]['reftypename'] . "</td></tr>";
  foreach($p as $k=>$v)
    if(strlen($v)>0)
      switch($k)
	  {
	    case('userlist'):
	    case('deptlist'):
// Do display pubid	    case('pubid'):
	    case('timestamp'):
	    case('chc'):
	    case('originator'):
		case('approvaldate'):
	    case('U2'):
	    case('U3'):
	    case('U4'):
	    case('U5'):
	    case('availability'):
	    case('yearmonth'):
	    case('yearday'):
	    case('yearother'):
	    case('approvedby'):
	    case('eprint'):
	    case('oaiid'):
	    case('reftype'):
	    case('personalonly'):
	    case('catkw'):
	    case('pendingdepts'):
	    case('anydepts'):
	    case('anypending'):
		  break; // i.e. don't display these ones
	    case('Y2'):
		  $y2 = explode('/', $v);
		  $y2out = '';
		  if(strlen($y2[3])>0)
		    $y2out .= "$y2[3], ";
		  if(strlen($y2[2])>0)
		    $y2out .= intval($y2[2]) . '/' . intval($y2[1]) . '/' . intval($y2[0]);
		  elseif(strlen($y2[1])>0)
		    $y2out .=  intval($y2[1]) . '/' . intval($y2[0]);
		  elseif(strlen($y2[0])>0)
		    $y2out .= $y2[0];
		  if(strlen($y2out)>0)
		  {
		    if($reftypes[$p['reftype']]['DISPLAY'.$k]=='Y')
		      echo "<tr><th>Secondary date:</th><td>$y2out</td></tr>";
		    elseif($reftypes[$p['reftype']]['DISPLAY'.$k]!='')
		      echo "<tr><th>" . $reftypes[$p['reftype']]['DISPLAY'.$k] . "</th><td>$y2out</td></tr>";
		  }
		  break;
	    case('pubid'):
		  echo "<tr><th>ID #" . "</th><td>$v</td></tr>";
		  break;
	    case('authorlist'):
		  echo "<tr><th>Authors" . "</th><td>".str_replace('.,', '., ', $v)."</td></tr>";
		  break;
	    case('url'):
		  echo "<tr><th>Web link" . "</th><td>$v " . buildthelinktext($v) . "</td></tr>";
		  break;
		case('year'):
		  echo "<tr><th>"
		     . ($reftypes[$p['reftype']]['DISPLAYyear']=='Y' ? 
			            ($p['yearday'].$p['yearmonth'].$p['yearother']=='' ? 'Year':'Date')
					 : $reftypes[$p['reftype']]['DISPLAYyear']);
		  $yout = '';
		  if(strlen($p['yearother'])>0)
		    $yout .= "$p[yearother], ";
		  if((intval($p['yearday'])>0) && (intval($p['yearmonth'])>0))
		    $yout .= "$p[yearday]/$p[yearmonth]/";
		  elseif(intval($p['yearmonth'])>0)
		    $yout .= "$p[yearmonth]/";

		  echo "</th><td>" . ($v==9999?$config['inpressstring']:"$yout$v") . "</td></tr>";
		  break;
        case 'DOI':
		  echo "<tr><th>DOI</th><td>" . translateDoiToLink($v) . "</td></tr>";
		  break;
		default:
	//	  if(isset($reftypes[$p[0]['reftype']]['DISPLAY'.$k])
	//	        && $reftypes[$p[0]['reftype']]['DISPLAY'.$k]=='')
	//	    break;
		  if($reftypes[$p['reftype']]['DISPLAY'.$k]!='Y'
		      && strlen($reftypes[$p['reftype']]['DISPLAY'.$k])>0)
		    echo "<tr><th>" . $reftypes[$p['reftype']]['DISPLAY'.$k] . "</th><td>$v</td></tr>";
		  else // if($reftypes[$p[0]['reftype']]['DISPLAY'.$k]=='Y')
		    echo "<tr><th>" . ucwords($k) . "</th><td>$v</td></tr>";
	      break;
	  }
  echo '</table>';
}

?>
