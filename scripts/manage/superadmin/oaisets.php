<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Untitled Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<style type="text/css">
dl dt {margin-top: 20px;}
dl dd {margin-bottom: 20px;}
</style>
</head>

<body>
<h2>MyOPIA - OAI import - &quot;Sets&quot; management</h2>
<?php
/*
*
* An interactive script to make sure that MyOPIA knows what sets the OAI repository is giving us, and what to do with them.
*
* The main purpose: decide on departments. But also: make sure we know which set indicates "isPublished".
*
*/
error_reporting(E_ALL ^ E_NOTICE);

require_once(dirname(dirname(dirname(__FILE__))) . '/oai-harvest/common.php');

$listsetsurl = $config['oai_baseurl'] . '?verb=ListSets';
$showmainmenu = true;

switch($_REQUEST["action2"])
{
  case 'listsets':
    $sets = listSets();
	$depts = getDepts();
	ksort($sets);
	$deptlookups = array();
	$statuslookups = array();
	$ignorelookups = array();
	// Categorise all the sets
	foreach($sets as $setSpec=>$set)
	{
	  if(trim($set['deptid'])!='')
	    $deptlookups[$setSpec] = $set;
	  elseif($set['ispublished']==1)
	    $statuslookups[$setSpec] = $set;
	  else
	    $ignorelookups[$setSpec] = $set;
	}
	
	// Now list them neatly
	?>
	<h3>Departmental lookup codes</h3>
	<ul>
	<?php
	  foreach($deptlookups as $setSpec => $set)
	    echo "\n  <li>$setSpec => <strong>$set[deptid]</strong> (" . $depts[$set['deptid']]['NAME'] . ")</li>";
	?>
	</ul>
	<h3>Status lookup codes</h3>
	<ul>
	<?php
	  foreach($statuslookups as $setSpec => $set)
	    echo "\n  <li>$setSpec => <strong>$set[ispublished]</strong></li>";
	?>
	</ul>
	<h3>Codes to be ignored</h3>
	<ul>
	<?php
	  foreach($ignorelookups as $setSpec => $set)
	    echo "\n  <li>$setSpec</li>";
	?>
	</ul>
	<hr />
	<?php
    break;
  case 'storesets':
    storeSetLookup($_POST['newassoc']);
	?>
	<p>Thank you - those associations have been stored in MyOPIA's lookup table.</p>
	<?php
    break;
  case 'getsets':

    $setsxml = '';

    if(!($fp = @fopen($listsetsurl, 'r')))
      die("Can't find file \"$listsetsurl\".");
    while($data = fread($fp, 4096))
	{
	  $setsxml .= $data;
    }
	fclose($fp);
//    echo "<pre>" ; // . htmlspecialchars($setsxml) . "</pre>";

	if(preg_match_all('|<set\s*>\s*<setSpec\s*>(.*)</setSpec>\s*<setName\s*>(.*)</setName>\s*</set>|Us', $setsxml, $matches, PREG_SET_ORDER))
	{
	  $depts = getDepts();
	  $alreadysets = listSets();
	  ?>
	  <p>All the OAI &quot;sets&quot; produced by the repository are listed below. For each one, please select 
	    how MyOPIA should interpret it. Many of them will probably be ignorable, because an OAI repository often
		has various types of set beyond the ones we want to use (department, publication status).</p>
		<form action='./' method='post'>
		<input type="hidden" name="action" value="oaisets" />
		<dl>
	  <?php
	  foreach($matches as $m)
	  {
	    if(isset($alreadysets[$m[1]]))
		{
		  echo "\n<p><strong>Already listed in the lookup table:</strong> $m[2]</p>";
		  continue;
		}
	  
	    // This check looks for subsets which we like to flatten, and ignores them completely (puts NOTHING in the table)
	    foreach($config['oai_flatten_sets'] as $flatten)
		  if(strpos($m[1], $flatten)===0 && trim($m[1])!=trim($flatten))
		  {
		    continue;
		  }
	    // This check automatically puts an "ignore" row in the table if it's a predefined ignorer...
		foreach($config['oai_ignore_sets'] as $flatten)
		  if(strpos($m[1], $flatten)===0)
		  {
		    $m[1] = $flatten;
			break;
		  }
	  
	    if(array_search($m[1], $config['oai_ignore_sets'])!==false)
		{
		  // Tell MyOPIA to ignore it
		  echo "\n\n<input type='hidden' name=\"newassoc[$m[1]]\" value=\"\" />";
		}
		else
		{
			echo "\n  <dt>" . highlightSetName($m[2]) . "</dt>
			  <dd><label>Map to MyOPIA field:
			  <select name=\"newassoc[$m[1]]\">
				<option value=\"\">Ignore this entry - doesn't indicate anything useful to MyOPIA</option>
				<option value=\"\">-----------------</option>
				<option value=\"status:pub\">Indicates that item is PUBLISHED</option>
				<option value=\"\">-----------------</option>";
			foreach($depts as $k=>$v)
			  echo "
				<option value=\"dept:$k\">$v[NAME]</option>";
			echo "</select></label></dd>";
         }
      }
	  ?>
	  </dl>
	  <input type="hidden" name="action2" value="storesets" />
	  <input type="submit" />
	  </form>
	  <?php
	  $showmainmenu = false;
	}
	else
	{
	  echo "<p>ERROR - unfortunately this system failed to get hold of the XML data correctly. Length of reply: "
	    . sizeof($setsxml) . "</p>";
	}
    break;
  case 'clearout':
    // Clear out everything from 
	$q = "DELETE FROM OAISETLOOKUP";
	mysql_query($q, connectpubsdb());
	?>
	<p>Thank you - MyOPIA's lookup table has been completely cleared out.</p>
	<?php
	break;
}

if($showmainmenu)
{
  ?>
  <p>&nbsp;</p>
  <ul>
    <li><a href='./?action=oaisets&action2=listsets'><strong>List</strong> the sets in MyOPIA's lookup table</a></li>
    <li><a href='./?action=oaisets&action2=clearout' onclick="return confirm('Are you sure you want to remove ALL the entries from the lookup table?') && confirm('Final check!\n\nAre you sure?\n\nYou\'ll need to rebuild all the associations.')"><strong>Clear</strong> out MyOPIA's lookup table</a></li>
    <li><a href='./?action=oaisets&action2=getsets'><strong>Build new lookups</strong> based on the list of sets used in the OAI repository</a></li>
  </ul>
  <p>&nbsp;</p>
  <p><em>The URL MyOPIA uses for getting the OAI repository's &quot;sets&quot; process is 
  <a href="<?php echo $listsetsurl; ?>"><?php echo $listsetsurl; ?></a></em></p>
  <?php
}

function highlightSetName($t)
{
  return preg_replace('/^(.*:)?(.*?)$/', "$1<br /><strong>$2</strong>", $t);
}
?>
</body>
</html>
