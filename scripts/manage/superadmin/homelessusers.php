<?php
// NB: $action = homelessusers
//require_once($config['homedir'] . 'query/formataref.php');

?>
<h2>Users with no assigned department</h2>
<?php

if(is_array($allocatedept))
{
  $fixed = 0;
  foreach($allocatedept as $uid => $deptid)
  {
    if(strlen($deptid)>0)
      if(mysql_query("UPDATE USERS SET deptid='" . mysql_real_escape_string($deptid) 
	                   . "', deptconfirmed=1 WHERE USERID='" . mysql_real_escape_string($uid) . "' LIMIT 1", connectpubsdb()))
	    $fixed++;
	  else
	    echo mysql_error();
  }
  echo "\n<p>$fixed users have been allocated to a department.</p>";

} // End of datasubmitted
else
{
    $depts = getDepts();
    $q = "SELECT * FROM USERS WHERE deptid=''";
	$res = mysql_query($q, connectpubsdb());
	
	?>
	<form action="./" method="post">
	<input type="hidden" name="action" value="homelessusers" />
	<?php
	
	if($res)
	{
		$deptChoices = '<option value="">Choose:</option>';
		foreach($depts as $dept)
		  $deptChoices .= "\n  <option value='$dept[DEPTID]'>$dept[NAME]</option>";

	  echo "\n<p>" . mysql_num_rows($res) . " found:</p>";
	  echo "\n<ul class='publicationsul'>";
	  while($u = mysql_fetch_assoc($res))
	  {
	    
		echo "\n  <li><a href=\"http://www.ucl.ac.uk/directory/request/?submit=Submit&name=$u[firstname]+$u[lastname]\" target=\"_blank\">$u[firstname] $u[lastname]</a> ($u[userid])<br />\n<select name='allocatedept[$u[userid]]'>"
			   . $deptChoices
		       . "</select><br /><pre>";
        passthru("ypcat passwd | grep -vi \"account\"| grep -vi \"Web acc\"| grep -i " . escapeshellarg($u['userid']));
//			   . system("ypcat passwd | grep -i \"$u[firstname]\" | grep -i \"$u[lastname]\"")
	    echo "</pre></li>";
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

?>