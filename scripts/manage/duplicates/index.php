<?php
// Date in the past
@header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
// always modified
@header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
// HTTP/1.1
@header("Cache-Control: no-store, no-cache, must-revalidate");
@header("Cache-Control: post-check=0, pre-check=0", false); 
// HTTP/1.0
@header("Pragma: no-cache");
?>
<html>
<head>
<title>Publications database - duplicate detection system</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body>
<?php
require_once(dirname(dirname(__FILE__)) . '/config.inc.php');
require_once('duplicatelib.inc.php');
include_once($config['homedir'] . 'manage/common.php');
echo "\n<p style='text-align:right;'>[<a href='$config[pageshomeurl]manage/'>Back to administration homepage</a>]</p>";

$year = intval($_REQUEST['year']);
$merge = intval($_REQUEST['merge']);
$delete = intval($_REQUEST['delete']);
$ignore = intval($_REQUEST['ignore']);
$ignore2 = intval($_REQUEST['ignore2']);
$dboffset = max(0, intval($_REQUEST['dboffset'])); // in order to speed up searching, ask the MySQL query not to start from the start each time
$decision = $_REQUEST['decision'];

// NB - If user is not global admin, then we don't allow the simple deletion, so this will be their choice every time.
if($delete>0 && $merge>0)
{
  $res = duplicate_merge($merge, $delete);
  echo "<p>Deletion of entry #$delete, with codes added to entry #$merge, was " . ($res?'':'NOT ') . "successfully carried out.</p>";
}
else if($delete>0)
{
  $res = mysql_query("DELETE FROM PUBLICATIONS WHERE pubid='$delete' LIMIT 1", connectpubsdb());
  echo "<p>Entry #$delete was " . ($res?'':'NOT ') . "successfully deleted from the database.</p>";
}
else if($ignore>0 && $ignore2>0)
{
  if($decision != 'none')
  {
    $res = mysql_query("INSERT INTO PUBLICATIONSNONDUPLICATES SET firstid='" . 
             min($ignore,$ignore2) . "', secondid='" . 
             max($ignore,$ignore2) . "'", connectpubsdb());
  }
}

echo "<p>Searching...</p>\n\n";

// THE MAIN QUERY - gets the entire dataset. Even for a departmental user, it's the whole dataset we want to check.
$q = "SELECT * FROM PUBLICATIONS WHERE 1 ";
$mydeptcodes = array_keys(getMyDepartments($_SERVER['REMOTE_USER']));
if($year>0)
{
  $q .= " AND (year='$year') ";
}
$q .= " ORDER BY title DESC";
$q .= " LIMIT $dboffset,18446744073709551615";

if($config['debug'])
   echo "<p>DEBUG: Query = $q </p>";
$res = mysql_query($q, connectpubsdb());
$p = array();
$totalpublications = mysql_num_rows($res) + $dboffset;
$lastone = '';
if(intval($_REQUEST['ignore'])>0)
  $ignored = false;
else
  $ignored = true;
$foundone = false;

$count=0;
while($v = mysql_fetch_assoc($res))
{
  // Here we are iterating through the MySQL result set (i.e. every publication in 
  //  the database [for the given year if supplied]) and each adjacent pair is
  //  checked to see if it bears a resemblance.
  // How to restrict this to departmental-only publications? If a non-global
  //  admin is using this page, then ONE out of the two records must 
  //  contain one of their "home" deptcodes. This allows them to check whether 
  //  their publication has a duplicate somewhere else in the world.


  if(  $ignored
        &&
	   // If global administrator, or alternatively if either record lies within admin's domain
	   (isGlobalAdmin($_SERVER['REMOTE_USER']) || recordIsInOneOfMyDepts($lastone, $mydeptcodes) || recordIsInOneOfMyDepts($v, $mydeptcodes))
		&&
	   is_array($lastone) // If we do actually have two records in hand
        &&
	   !(marked_as_nonduplicate($v, $lastone)) // If not already marked as non-duplicate
        &&
        judge_duplicateness($v, $lastone)
	)
  {
    echo "\n<h2>Possible duplicate found: </h2>\n";
	echo "<p>(" . intval(100000*($count+$dboffset)/$totalpublications)/1000 . "% of the way through database)</p>";
    echo compactdoubleentrytable($lastone, $v, $dboffset + $count - 8); // The -8 gives a bit of a buffer in case someone has added/removed a publication while you've been looking
	$foundone = true;
    break;
  }
  else if(intval($v['pubid'])==intval($_REQUEST['ignore']))
    $ignored=true;
  $lastone=$v;
  $count++;
}

if(!$foundone)
{
  ?>
  <p style="padding: 50px">No duplicates found in database<?php if($year>0) echo " for year $year" ?>.</p>
  <?php
}

?>
</body>
</html>