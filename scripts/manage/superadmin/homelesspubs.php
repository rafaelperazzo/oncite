<?php
// NB: $action = homelesspubs
require_once($config['homedir'] . 'query/formataref.php');

?>
<h2>Homeless publications</h2>
<?php

if(is_array($allocatedept))
{
  $fixed = 0;
  foreach($allocatedept as $pubid => $deptid)
  {
    if(strlen($deptid)>0)
      if(mysql_query("UPDATE PUBLICATIONS SET pendingdepts='," . mysql_real_escape_string($deptid) 
	                   . ",' WHERE PUBID=" . intval($pubid) . " LIMIT 1", connectpubsdb()))
	    $fixed++;
  }
  echo "\n<p>$fixed publications have been allocated to a department.</p>";

} // End of datasubmitted
else
{
    $depts = getDepts();
    $q = "SELECT * FROM PUBLICATIONS WHERE (deptlist REGEXP '^[\.,]*$') AND (pendingdepts REGEXP '^[\.,]*$') AND (userlist REGEXP '^[\.,]*$')"
	       . (intval($_REQUEST['year'])>0? " AND (year=" . intval($_REQUEST['year']) . ") ":'')
	       . " ORDER BY year desc, authorlist ASC";
	$res = mysql_query($q, connectpubsdb());
	
	?>
	<form action="./" method="post">
	<input type="hidden" name="action" value="homelesspubs" />
	<?php
	
	if($res)
	{
	  echo "\n<p>" . mysql_num_rows($res) . " found:</p>";
	  echo "\n<ul class='publicationsul'>";
	  while($pub = mysql_fetch_assoc($res))
	  {
	    
		echo "\n  <li>" 
	           . formataref2($pub, $_SERVER['REMOTE_USER'], '')
		       . "<br />\n<select name='allocatedept[$pub[pubid]]'>"
			   . getDeptsChooser($pub['userlist'])
		       . "</select> <br />$pub[userlist]"
			   . "</li>";
	  }
	  echo "\n</ul>";
	}
	else
	  echo "<p>Database error - sorry. Please contact the site administrator if this continues.</p>";
	
	?>
	<input type="submit" value="Store decisions" />
	</form>
	<?php
} // End of nodatassubmitted

function getDeptsChooser($userlist)
{
    global $depts;
    if(preg_match('/^[,\.]*(\w+)[\.,]*/', $userlist, $singleuser))
	{
	  $q = "SELECT deptid FROM USERS WHERE userid='" . mysql_real_escape_string($singleuser[1]) . "' LIMIT 1";
	  $res = mysql_query($q, connectpubsdb());
	  $row = mysql_fetch_assoc($res);
	  $homedept = $row['deptid'];
	}
	$deptChoices = '<option value="">Choose:</option>';
	foreach($depts as $dept)
	  $deptChoices .= "\n  <option value='$dept[DEPTID]'" . ($homedept==$dept['DEPTID']?' selected="selected"':'') . ">$dept[NAME]</option>";
    return $deptChoices;
}

?>